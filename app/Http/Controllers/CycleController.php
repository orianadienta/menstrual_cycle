<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cycle;
use App\Services\PredictionService;
use App\Services\RecommendationService;
use Illuminate\Support\Carbon;

class CycleController extends Controller
{
    protected $predictionService;
    protected $recommendationService;

    public function __construct(PredictionService $predictionService, RecommendationService $recommendationService)
    {
        $this->predictionService = $predictionService;
        $this->recommendationService = $recommendationService;
    }

    public function index(Request $request)
    {
        $cycles = $request->user()->cycles()->get();
        return response()->json($cycles);
    }

    public function markPeriod(Request $request, PredictionService $predictionService) 
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = $request->user();
        $profile = $user->cycleProfile;
        $periodDuration = $profile->initial_period_duration ?? 7;
        $endDate = $request->end_date ?? now()->parse($request->start_date)->addDays($periodDuration - 1);

        $cycle = Cycle::create([
            'user_id' => $user->id,
            'start_date' => $request->start_date,
            'end_date' => $endDate,
            'period_duration' => Carbon::parse($request->start_date)->diffInDays(Carbon::parse($endDate)) + 1,
        ]);

        // generate prediksi baru setelah pencatatan menstruasi
        $newPrediction = $this->predictionService->generatePrediction($user->id);

        // generate rekomendasi setiap perubahan siklus
        $this->recommendationService->clearCache($user->id);
        $recommendations = $this->recommendationService->generateRecommendations($user->id);

        return response()->json([
            'message' => 'Cycle berhasil dicatat',
            'data' => [
                'cycle' => $cycle,
                'next_prediction' => $newPrediction,
                // 'recommendations' => $recommendations,
            ],
        ]);
    }

    // update siklus pertanggal atau perhari
    public function updateMarkPeriod(Request $request, Cycle $cycle) 
    {
        $request->validate([
            'date' => 'required|date',
            'is_menstruating' => 'required|boolean',
        ]);

        $date = \Carbon\Carbon::parse($request->date);

        // untuk kondisi durasi menstruasi yang lebih lama
        if ($request->is_menstruating) {
            if(!$cycle->end_date || $date->greaterThan($cycle->end_date)) {
                $cycle->end_date = $date;
                $cycle->period_duration = $cycle->start_date->diffInDays($cycle->end_date) + 1;
                $cycle->save();
            }
        } else{
            if ($cycle->end_date && $cycle->end_date->equalTo($date)) {
                $cycle->end_date = $cycle->end_date->copy()->subDay();
                $cycle->period_duration = $cycle->start_date->diffInDays($cycle->end_date) + 1;
                $cycle->save();
                }
            }

        // generate prediksi baru setelah update
        $newPrediction = $this->predictionService->generatePrediction($cycle->user_id);

        // generate ulang rekomendasi kalau update siklus
        $this->recommendationService->clearCache($cycle->user_id);
        $recommendations = $this->recommendationService->generateRecommendations($cycle->user_id);

        return response()->json([
            'message' => 'Catatan berhasil diperbarui',
            'data' => [
                'cycle' => $cycle,
                'next_prediction' => $newPrediction,
            ],
        ]);
    }


    public function getCycleHistory(Request $request)
    {
        $user = $request->user();
        
        // Ambil 6 siklus terakhir yang punya cycle_length
        $cycles = Cycle::where('user_id', $user->id)
            ->whereNotNull('end_date')
            ->whereNotNull('cycle_length') // Harus punya cycle length
            ->orderBy('start_date', 'desc')
            ->take(6)
            ->get();

        if ($cycles->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada riwayat siklus',
                'data' => [],
            ]);
        }

        // Group by tahun
        $history = $cycles->groupBy(function($cycle) {
            return \Carbon\Carbon::parse($cycle->start_date)->year;
        })->map(function($yearCycles, $year) {
            $cycleList = $yearCycles->map(function($cycle) {
                $startDate = \Carbon\Carbon::parse($cycle->start_date);
                
                // Hitung next start date berdasarkan cycle length
                $nextStartDate = $startDate->copy()->addDays($cycle->cycle_length);
                
                return [
                    'id' => $cycle->id,
                    'display' => $startDate->format('d M') . ' - ' . 
                                $nextStartDate->format('d M') . 
                                ' (' . $cycle->cycle_length . ' hari)',
                    'start_date' => $startDate->format('Y-m-d'),
                    'next_cycle_date' => $nextStartDate->format('Y-m-d'),
                    'cycle_length' => $cycle->cycle_length,
                    'period_duration' => $cycle->period_duration,
                ];
            })->values()->all();

            return [
                'year' => $year,
                'cycles' => $cycleList,
                'total_cycles' => count($cycleList),
            ];
        })->values()->all();

        return response()->json([
            'message' => 'Riwayat siklus berhasil diambil',
            'data' => $history,
        ]);
    }
}
