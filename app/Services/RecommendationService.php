<?php

namespace App\Services;

use App\Models\Cycle;
use App\Models\SymptomLog;
use App\Models\UserHealthCondition;
use App\Models\Recommendations;
use App\Models\TrackingStatus;
use App\Constants\RecommendationContent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    const Figo_cycle_min = 24;
    const Figo_cycle_max = 38;
    const Figo_variation_max = 9;

    const CACHE_DURATION = 3600; // 1 hour

    // FIX: Generate rekom pakai cache dengan logic yang benar
    public function generateRecommendations($userId)
    {
        $cacheKey = "recommendations_user_{$userId}";

        // Check cache dulu - kalau ada, return langsung
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            Log::info("Recommendations dari cache untuk user {$userId}");
            return $cached;
        }

        // Generate baru
        $analysis = $this->analyzeUserCondition($userId);
        $recommendations = $this->buildRecommendations($analysis);

        // Save ke database
        $this->saveRecommendations($userId, $recommendations);

        // Cache selama 1 jam
        Cache::put($cacheKey, $recommendations, now()->addSeconds(self::CACHE_DURATION));
        
        Log::info("Recommendations di-generate & di-cache untuk user {$userId}, total: " . count($recommendations));

        return $recommendations;
    }

    private function analyzeUserCondition($userId)
    {
        $trackingStatus = TrackingStatus::where('user_id', $userId)
            ->latest()
            ->first();

        // CHECK: Kalau paused, return kondisi khusus
        if ($trackingStatus && $trackingStatus->status === 'paused') {
            return [
                'cycles' => collect([]),
                'cycles_count' => 0,
                'cycle_status' => 'paused',
                'pause_reason' => $trackingStatus->pause_reason,
                'has_symptoms' => false,
                'symptoms' => collect([]),
                'has_conditions' => false,
                'conditions' => collect([]),
            ];
        }

        // Query cycles
        $query = Cycle::where('user_id', $userId)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc');
        
        // LOGIC: Kalau ada resume, HANYA ambil cycle setelah resume
        if ($trackingStatus && $trackingStatus->resumed_at) {
            $query->where('start_date', '>=', $trackingStatus->resumed_at);
        }
        elseif ($trackingStatus && $trackingStatus->paused_at) {
            $query->where('start_date', '<', $trackingStatus->paused_at);
        }
        
        $cycles = $query->take(6)->get();

        // Symptoms dari 1 bulan terakhir
        $symptoms = SymptomLog::with('symptom.category')
            ->where('user_id', $userId)
            ->where('log_date', '>=', Carbon::now()->subMonths(1))
            ->get();

        $conditions = UserHealthCondition::with('healthCondition')
            ->where('user_id', $userId)
            ->get();

        return [
            'cycles' => $cycles,
            'cycles_count' => $cycles->count(),
            'cycle_status' => $this->getCycleStatus($cycles),
            'has_symptoms' => $symptoms->isNotEmpty(),
            'symptoms' => $symptoms,
            'has_conditions' => $conditions->isNotEmpty(),
            'conditions' => $conditions,
            'is_post_resume' => $trackingStatus && $trackingStatus->resumed_at ? true : false,
        ];
    }

    private function getCycleStatus($cycles)
    {
        if ($cycles->count() < 3) {
            return 'insufficient_data';
        }

        $cycleLengths = $cycles->pluck('cycle_length')
            ->filter()
            ->values()
            ->toArray();

        if (empty($cycleLengths)) {
            return 'no_data';
        }

        $average = array_sum($cycleLengths) / count($cycleLengths);
        $variation = $this->calculateVariation($cycleLengths);

        $isRegular = $average >= self::Figo_cycle_min &&
                     $average <= self::Figo_cycle_max &&
                     $variation <= self::Figo_variation_max;
        return $isRegular ? 'regular' : 'irregular';
    }

    private function calculateVariation($values)
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($val) => pow($val - $mean, 2), $values);
        $variance = array_sum($squaredDiffs) / count($values);

        return sqrt($variance);
    }

    private function buildRecommendations($analysis)
    {
        $recommendations = [];
        
        $cycleStatus = $analysis['cycle_status'];
        $hasSymptoms = $analysis['has_symptoms'];
        $hasConditions = $analysis['has_conditions'];
        $isPostResume = $analysis['is_post_resume'] ?? false;

        // Post-resume dengan data kurang
        if ($isPostResume && $analysis['cycles_count'] < 3) {
            $recommendations[] = [
                'title' => 'Mulai Tracking Kembali',
                'content' => 'Catat minimal 3 siklus menstruasi untuk mendapatkan prediksi dan laporan yang akurat.',
                'category' => 'tracking',
                'priority' => 'high',
            ];
            return $recommendations;
        }

        // Handle paused status
        if ($cycleStatus === 'paused') {
            $recommendations[] = $this->getPausedRecommendation($analysis['pause_reason']);
            return $recommendations;
        }

        // === KONDISI 1: Insufficient Data (< 3 cycles) ===
        if ($cycleStatus === 'insufficient_data') {
            $recommendations[] = RecommendationContent::ONBOARDING;
            return $recommendations;
        }

        // === KONDISI 2: Sehat (Regular + No Symptoms + No Conditions) ===
        if ($cycleStatus === 'regular' && !$hasSymptoms && !$hasConditions) {
            $recommendations[] = RecommendationContent::HEALTHY_CYCLE;
            $recommendations[] = RecommendationContent::HEALTHY_LIFESTYLE;
            $recommendations[] = RecommendationContent::TRACK_SYMPTOMS_PROMPT;
            return $recommendations;
        }

        // === KONDISI 3: Regular Cycle + Has Symptoms ===
        if ($cycleStatus === 'regular' && $hasSymptoms) {
            $recommendations[] = RecommendationContent::SYMPTOM_GENERAL;
            $this->addSymptomRecommendations($analysis['symptoms'], $recommendations);
        }

        // === KONDISI 4: Has Health Conditions ===
        if ($hasConditions) {
            $recommendations[] = RecommendationContent::CONDITION_MONITORING;
            $this->addConditionRecommendations($analysis['conditions'], $recommendations);
        }

        // === KONDISI 5: Irregular Cycle (PRIORITAS TINGGI!) ===
        if ($cycleStatus === 'irregular') {
            $recommendations[] = RecommendationContent::IRREGULAR_ALERT;
            $recommendations[] = RecommendationContent::IRREGULAR_LIFESTYLE;
            
            if ($hasSymptoms) {
                $recommendations[] = RecommendationContent::IRREGULAR_URGENT;
                $recommendations[] = RecommendationContent::CONSULTATION_PREP;
            } else {
                $recommendations[] = RecommendationContent::IRREGULAR_MEDICAL_ADVICE;
            }
        }

        return $recommendations;
    }

    private function addSymptomRecommendations($symptoms, &$recommendations)
    {
        $categories = $symptoms->pluck('symptom.category.category_name')->unique();
        
        foreach ($categories as $category) {
            if (isset(RecommendationContent::SYMPTOM_CATEGORIES[$category])) {
                $recommendations[] = RecommendationContent::SYMPTOM_CATEGORIES[$category];
            }
        }
    }

    private function addConditionRecommendations($conditions, &$recommendations)
    {
        foreach ($conditions as $userCondition) {
            $conditionName = $userCondition->healthCondition->condition_name;
            
            if (isset(RecommendationContent::CONDITIONS[$conditionName])) {
                $recommendations[] = RecommendationContent::CONDITIONS[$conditionName];
            }
        }
    }

    private function saveRecommendations($userId, $recommendations)
    {
        // Hapus recommendations lama (> 3 hari) untuk prevent accumulation
        Recommendations::where('user_id', $userId)
            ->where('created_at', '<', Carbon::now()->subDays(3))
            ->delete();

        // Save recommendations baru (hindari duplikat)
        foreach ($recommendations as $rec) {
            // Check apakah sudah ada recommendation yang sama dalam 24 jam terakhir
            $exists = Recommendations::where('user_id', $userId)
                ->where('title', $rec['title'])
                ->where('category', $rec['category'])
                ->where('priority', $rec['priority'])
                ->where('created_at', '>', Carbon::now()->subDay())
                ->exists();
            
            if (!$exists) {
                Recommendations::create([
                    'user_id' => $userId,
                    'title' => $rec['title'],
                    'content' => $rec['content'],
                    'category' => $rec['category'],
                    'priority' => $rec['priority'],
                ]);
            }
        }
    }

    public function clearCache($userId)
    {
        $cacheKey = "recommendations_user_{$userId}";
        Cache::forget($cacheKey);
        Log::info("Cache cleared untuk user {$userId}");
    }

    public function getActiveRecommendations($userId, $limit = 5)
    {
        return Recommendations::where('user_id', $userId)
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecommendationsByPriority($userId, $priority)
    {
        return Recommendations::where('user_id', $userId)
            ->where('priority', $priority)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRecommendationsByCategory($userId, $category)
    {
        return Recommendations::where('user_id', $userId)
            ->where('category', $category)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getPausedRecommendation($reason)
    {
        $content = match($reason) {
            'pregnancy' => 'Tracking dijeda selama kehamilan. Aktifkan kembali setelah menstruasi kembali normal pasca melahirkan.',
            'breastfeeding' => 'Tracking dijeda selama menyusui. Menstruasi mungkin tidak teratur saat menyusui.',
            'menopause' => 'Tracking dijeda. Konsultasikan dengan dokter mengenai perubahan hormonal.',
            'medical' => 'Tracking dijeda karena alasan medis. Ikuti saran dokter Anda.',
            default => 'Tracking dijeda. Catat menstruasi untuk melanjutkan tracking.',
        };

        return [
            'title' => 'Tracking Siklus Dijeda',
            'content' => $content,
            'category' => 'info',
            'priority' => 'low',
        ];
    }
}