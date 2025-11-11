<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use App\Services\PredictionService;
use App\Models\Cycle;
use App\Models\Recommendations;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RecommendationDisplayController extends Controller
{
    protected $recommendationService;
    protected $predictionService;

    public function __construct(RecommendationService $recommendationService, PredictionService $predictionService)
    {
        $this->recommendationService = $recommendationService;
        $this->predictionService = $predictionService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $nextPrediction = $this->predictionService->getLatestPrediction($user->id);
        
        $currentCycle = Cycle::where('user_id', $user->id)
            ->whereNull('end_date')
            ->latest('start_date')
            ->first();

        $recentCycles = Cycle::where('user_id', $user->id)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc')
            ->take(6)
            ->get();

        $averageCycleLength = $recentCycles->average('cycle_length')
            ? round($recentCycles->average('cycle_length')) : null;

        $topRecommendations = Recommendations::where('user_id', $user->id)
            ->whereIn('priority', ['urgent', 'high'])
            ->orderByRaw("
                CASE 
                    WHEN priority = 'urgent' THEN 1
                    WHEN priority = 'high' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();


        $totalRecommendations = Recommendations::where('user_id', $user->id)->count();

        $lastRecommendation = Recommendations::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'next_prediction' => $nextPrediction,
                'current_cycle' => $currentCycle,
                'ststistics' => [
                    'average_cycle_length' => $averageCycleLength,
                    'recent_cycles_count' => $recentCycles->count(),
                ],

                'recommendations' => [
                    'items' => $topRecommendations,
                    'total' => $totalRecommendations,
                    'has_more' =>$totalRecommendations > 2,
                    'last_updated' => $lastRecommendation?->created_at,
                    'last_updated_human' => $lastRecommendation?->created_at?->diffForHumans(),
                ],
            ],
            
        ]);

    }

    public function allRecommendations(Request $request)
    {
        $user = $request->user();

        $recommendations = Recommendations::where('user_id', $user->id)
            ->orderByRaw("
                CASE 
                    WHEN priority = 'urgent' THEN 1
                    WHEN priority = 'high' THEN 2
                    WHEN priority = 'medium' THEN 3
                    WHEN priority = 'low' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('created_at', 'desc')
            ->get();


        // Group by category
        $grouped = $recommendations->groupBy('category')->map(function($items, $category) {
            return [
                'category' => $category,
                'items' => $items,
                'count' => $items->count(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'all' => $recommendations,
                'grouped_by_category' => $grouped,
                'total' => $recommendations->count(),
            ],
        ]);
    }


    public function refreshRecommendations(Request $request)
    {
        $user = $request->user();
        
        // Clear cache
        $this->recommendationService->clearCache($user->id);
        
        // Generate fresh recommendations
        $recommendations = $this->recommendationService->generateRecommendations($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi berhasil diperbarui',
            'data' => [
                'recommendations' => $recommendations,
                'total' => count($recommendations),
                'last_updated' => now(),
                'last_updated_human' => 'Baru saja',
            ],
        ]);
    }
}
