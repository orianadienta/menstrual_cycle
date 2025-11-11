<?php

namespace App\Services;

use App\Models\Cycle;
use App\Models\SymptomLog;
use App\Models\UserHealthCondition;
use App\Models\Recommendations;
use App\Constants\RecommendationContent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    const Figo_cycle_min = 24;
    const Figo_cycle_max = 38;
    const Figo_variation_max = 9;

    // generate rekom pakai cache
    public function generateRecommendations($userId)
    {
        $cacheKey = "recommendations_user_{$userId}";

        return Cache::remember($cacheKey, 3600, function() use ($userId){
            $analysis = $this->analyzeUserCondition($userId);
            $recommendations = $this->buildRecommendations($analysis);

            $this->saveRecommendations($userId, $recommendations);
            return $recommendations;
        });
    }

    private function analyzeUserCondition($userId)
    {
        $cycles = Cycle::where('user_id', $userId)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc')
            ->take(6)
            ->get();

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
        ];
    }


    // private function analyzeUserCondition($userId)
    // {
    //     $cycles = Cycle::where('user_id', $userId)
    //         ->whereNotNull('end_date')
    //         ->orderBy('start_date', 'desc')
    //         ->take(6)
    //         ->get();

    //     // 🔹 Ambil gejala dari bulan terbaru aja
    //     $latestSymptomDate = SymptomLog::where('user_id', $userId)->max('log_date');
    //     $symptoms = collect();

    //     if ($latestSymptomDate) {
    //         $latestMonth = Carbon::parse($latestSymptomDate)->month;
    //         $latestYear = Carbon::parse($latestSymptomDate)->year;

    //         $symptoms = SymptomLog::with('symptom.category')
    //             ->where('user_id', $userId)
    //             ->whereMonth('log_date', $latestMonth)
    //             ->whereYear('log_date', $latestYear)
    //             ->get();
    //     }

    //     $conditions = UserHealthCondition::with('healthCondition')
    //         ->where('user_id', $userId)
    //         ->get();

    //     return [
    //         'cycles' => $cycles,
    //         'cycles_count' => $cycles->count(),
    //         'cycle_status' => $this->getCycleStatus($cycles),
    //         'has_symptoms' => $symptoms->isNotEmpty(),
    //         'symptoms' => $symptoms,
    //         'has_conditions' => $conditions->isNotEmpty(),
    //         'conditions' => $conditions,
    //     ];
    // }


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


    // build rekomendasi berdasarkan kondisi user
    private function buildRecommendations($analysis)
    {
        $recommendations = [];
        
        $cycleStatus = $analysis['cycle_status'];
        $hasSymptoms = $analysis['has_symptoms'];
        $hasConditions = $analysis['has_conditions'];

        // === KONDISI 1: Insufficient Data (< 3 cycles) ===
        if ($cycleStatus === 'insufficient') {
            $recommendations[] = RecommendationContent::ONBOARDING;
            return $recommendations; // Early return
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
            
            // Tambah rekomendasi per kategori symptom
            $this->addSymptomRecommendations($analysis['symptoms'], $recommendations);
        }

        // === KONDISI 4: Has Health Conditions ===
        if ($hasConditions) {
            $recommendations[] = RecommendationContent::CONDITION_MONITORING;
            
            // Tambah rekomendasi per kondisi kesehatan
            $this->addConditionRecommendations($analysis['conditions'], $recommendations);
        }

        // === KONDISI 5: Irregular Cycle (PRIORITAS TINGGI!) ===
        if ($cycleStatus === 'irregular') {
            // Alert utama
            $recommendations[] = RecommendationContent::IRREGULAR_ALERT;
            $recommendations[] = RecommendationContent::IRREGULAR_LIFESTYLE;
            
            // Kalau ada symptoms juga = URGENT
            if ($hasSymptoms) {
                $recommendations[] = RecommendationContent::IRREGULAR_URGENT;
                $recommendations[] = RecommendationContent::CONSULTATION_PREP;
            } else {
                $recommendations[] = RecommendationContent::IRREGULAR_MEDICAL_ADVICE;
            }
        }

        return $recommendations;
    }

    /**
     * Tambah rekomendasi spesifik per kategori symptom
     */
    private function addSymptomRecommendations($symptoms, &$recommendations)
    {
        $categories = $symptoms->pluck('symptom.category.category_name')->unique();
        
        foreach ($categories as $category) {
            if (isset(RecommendationContent::SYMPTOM_CATEGORIES[$category])) {
                $recommendations[] = RecommendationContent::SYMPTOM_CATEGORIES[$category];
            }
        }
    }

    /**
     * Tambah rekomendasi spesifik per health condition
     */
    private function addConditionRecommendations($conditions, &$recommendations)
    {
        foreach ($conditions as $userCondition) {
            $conditionName = $userCondition->healthCondition->condition_name;
            
            if (isset(RecommendationContent::CONDITIONS[$conditionName])) {
                $recommendations[] = RecommendationContent::CONDITIONS[$conditionName];
            }
        }
    }

    /**
     * Save recommendations ke database
     */
    private function saveRecommendations($userId, $recommendations)
    {
        // Hapus recommendations lama (> 7 hari)
        Recommendations::where('user_id', $userId)
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->delete();

        // Save recommendations baru (hindari duplikat)
        foreach ($recommendations as $rec) {
            // Check apakah sudah ada recommendation yang sama dalam 3 hari terakhir
            $exists = Recommendations::where('user_id', $userId)
                ->where('category', $rec['category'])
                ->where('priority', $rec['priority'])
                ->where('title', $rec['title'])
                ->where('created_at', '>', Carbon::now()->subDays(3))
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

    /**
     * Clear cache (panggil saat ada perubahan data)
     */
    public function clearCache($userId)
    {
        $cacheKey = "recommendations_user_{$userId}";
        Cache::forget($cacheKey);
    }

    /**
     * Get active recommendations untuk display di home
     */
    public function getActiveRecommendations($userId, $limit = 5)
    {
        return Recommendations::where('user_id', $userId)
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recommendations by priority (untuk filter)
     */
    public function getRecommendationsByPriority($userId, $priority)
    {
        return Recommendations::where('user_id', $userId)
            ->where('priority', $priority)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recommendations by category (untuk filter)
     */
    public function getRecommendationsByCategory($userId, $category)
    {
        return Recommendations::where('user_id', $userId)
            ->where('category', $category)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}