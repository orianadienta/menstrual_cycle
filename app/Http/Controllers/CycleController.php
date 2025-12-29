<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cycle;
use App\Models\TrackingStatus;
use App\Services\PredictionService;
use App\Services\RecommendationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CycleController extends Controller
{
    // Constants untuk logging & monitoring
    const NORMAL_CYCLE_MIN = 21;
    const NORMAL_PERIOD_MAX = 10;
    
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
        // Basic validation only
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

        $duration = $startDate->diffInDays($endDate) + 1;

        // Log unusual patterns for medical analysis (tidak reject)
        $this->logUnusualPattern($user->id, $startDate, $endDate, $duration);

        // Create cycle - accept all valid data
        $cycle = Cycle::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period_duration' => $duration,
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
        ]);

        $startDate = $request->start_date 
            ? Carbon::parse($request->start_date)
            : Carbon::parse($cycle->start_date);

        $endDate = $request->end_date 
            ? Carbon::parse($request->end_date)
            : $startDate->copy()->addDays($cycle->user->cycleProfile?->initial_period_duration ?? 5 - 1);

        // Normalize dates
        if ($endDate->isBefore($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $duration = $startDate->diffInDays($endDate) + 1;

        // Log unusual patterns for medical analysis (tidak reject)
        $this->logUnusualPattern($cycle->user_id, $startDate, $endDate, $duration);

        $cycle->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period_duration' => $duration,
        ]);

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

    /**
     * Log unusual period patterns for medical analysis
     * This doesn't reject the data, just logs for monitoring
     */
    private function logUnusualPattern(int $userId, Carbon $startDate, Carbon $endDate, int $duration)
    {
        $warnings = [];

        // Check for long period duration
        if ($duration > self::NORMAL_PERIOD_MAX) {
            $warnings[] = "Long period duration: {$duration} days";
        }

        // Check for short cycle gap
        $lastCycle = Cycle::where('user_id', $userId)
            ->where('id', '!=', request()->route('cycle')?->id) // Exclude current cycle if updating
            ->orderBy('end_date', 'desc')
            ->first();

        if ($lastCycle) {
            $daysGap = Carbon::parse($lastCycle->end_date)->diffInDays($startDate);
            
            if ($daysGap < self::NORMAL_CYCLE_MIN) {
                $warnings[] = "Short cycle gap: {$daysGap} days";
            }
        }

        // Log if there are any unusual patterns
        if (!empty($warnings)) {
            Log::channel('daily')->info('Unusual period pattern detected', [
                'user_id' => $userId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'duration' => $duration,
                'warnings' => $warnings,
                'timestamp' => now(),
            ]);
        }
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
    /**
 * Delete a cycle record
 */
    public function destroy(Request $request, Cycle $cycle)
    {
        // Authorization check
        if ($cycle->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this cycle',
            ], 403);
        }

        // Check if this is a predicted cycle
        if ($cycle->is_predicted) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete predicted cycles',
            ], 400);
        }

        try {
            // Store cycle info for logging
            $cycleInfo = [
                'id' => $cycle->id,
                'start_date' => $cycle->start_date,
                'end_date' => $cycle->end_date,
                'period_duration' => $cycle->period_duration,
            ];

            // Delete the cycle
            $cycle->delete();

            // Regenerate predictions after deletion
            $prediction = $this->predictionService->generatePrediction($request->user()->id);
            
            // Clear and regenerate recommendations
            $this->recommendationService->clearCache($request->user()->id);
            $this->recommendationService->generateRecommendations($request->user()->id);

            // Log deletion
            Log::channel('daily')->info('Cycle deleted by user', [
                'user_id' => $request->user()->id,
                'cycle_info' => $cycleInfo,
                'timestamp' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cycle berhasil dihapus',
                'data' => [
                    'next_prediction' => $prediction,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete cycle', [
                'user_id' => $request->user()->id,
                'cycle_id' => $cycle->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus cycle',
            ], 500);
        }
    }
    
}
