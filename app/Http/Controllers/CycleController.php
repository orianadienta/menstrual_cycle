<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cycle;
use App\Models\TrackingStatus;
use App\Services\PredictionService;
use App\Services\RecommendationService;
use Illuminate\Support\Carbon;

class CycleController extends Controller
{
    public function __construct(
        private PredictionService $predictionService,
        private RecommendationService $recommendationService
    ) {}

    public function index(Request $request)
    {
        return response()->json($request->user()->cycles);
    }

    public function markPeriod(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today',
        ]);

        $user = $request->user();

        // Auto-resume if paused
        $tracked = TrackingStatus::where('user_id', $user->id)->latest()->first();
        if ($tracked?->status === 'paused') {
            TrackingStatus::create([
                'user_id' => $user->id,
                'status' => 'active',
                'resumed_at' => now(),
            ]);
        }

        // Calculate end date
        $startDate = Carbon::parse($request->start_date);
        $periodDuration = $user->cycleProfile?->initial_period_duration ?? 5;
        $endDate = $request->end_date 
            ? Carbon::parse($request->end_date)
            : $startDate->copy()->addDays($periodDuration - 1);

        // Create cycle
        $cycle = Cycle::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period_duration' => $startDate->diffInDays($endDate) + 1,
        ]);

        // Generate prediction & recommendations
        $prediction = $this->predictionService->generatePrediction($user->id);
        $this->recommendationService->clearCache($user->id);
        $this->recommendationService->generateRecommendations($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Cycle berhasil dicatat',
            'data' => [
                'cycle' => $cycle,
                'next_prediction' => $prediction,
            ],
        ]);
    }

    public function updateMarkPeriod(Request $request, Cycle $cycle)
    {
        $request->validate([
            'start_date' => 'nullable|date|before_or_equal:today',
            'end_date' => 'nullable|date|before_or_equal:today',
            'date' => 'nullable|date|before_or_equal:today',
            'is_menstruating' => 'required|boolean',
        ]);

        if ($request->is_menstruating) {
            $startDate = $request->start_date 
                ? Carbon::parse($request->start_date)
                : Carbon::parse($cycle->start_date);

            $endDate = $request->end_date 
                ? Carbon::parse($request->end_date)
                : ($request->date 
                    ? Carbon::parse($request->date)
                    : $startDate->copy()->addDays($cycle->user->cycleProfile?->initial_period_duration ?? 5 - 1));

            // Normalize dates
            if ($endDate->isBefore($startDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            $cycle->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'period_duration' => $startDate->diffInDays($endDate) + 1,
            ]);
        }

        // Generate prediction & recommendations
        $prediction = $this->predictionService->generatePrediction($cycle->user_id);
        $this->recommendationService->clearCache($cycle->user_id);
        $this->recommendationService->generateRecommendations($cycle->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Catatan berhasil diperbarui',
            'data' => [
                'cycle' => $cycle,
                'next_prediction' => $prediction,
            ],
        ]);
    }

    public function getCycleHistory(Request $request)
    {
        $cycles = Cycle::where('user_id', $request->user()->id)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc')
            ->take(6)
            ->get()
            ->groupBy(fn($c) => Carbon::parse($c->start_date)->year);

        if ($cycles->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada riwayat siklus',
                'data' => [],
            ]);
        }

        $history = $cycles->map(fn($cycles, $year) => [
            'year' => $year,
            'cycles' => $cycles->map(fn($c) => [
                'id' => $c->id,
                'display' => Carbon::parse($c->start_date)->format('d M') . ' - ' .
                    Carbon::parse($c->start_date)->addDays($c->cycle_length ?? 28)->format('d M') .
                    ' (' . ($c->cycle_length ?? 28) . ' hari)',
                'start_date' => $c->start_date,
                'cycle_length' => $c->cycle_length ?? 28,
                'period_duration' => $c->period_duration,
            ])->values(),
            'total_cycles' => $cycles->count(),
        ])->values();

        return response()->json([
            'message' => 'Riwayat siklus berhasil diambil',
            'data' => $history,
        ]);
    }
}