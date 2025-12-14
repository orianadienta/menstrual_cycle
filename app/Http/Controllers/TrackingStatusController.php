<?php

namespace App\Http\Controllers;

use App\Models\TrackingStatus;
use App\Models\PredictedCycle;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackingStatusController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function getStatus(Request $request)
    {
        $status = TrackingStatus::where('user_id', $request->user()->id)
            ->latest()
            ->first();

        if (!$status) {
            // Default: active
            $status = TrackingStatus::create([
                'user_id' => $request->user()->id,
                'status' => 'active',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    // public function pause(Request $request)
    // {
    //     $request->validate([
    //         'reason' => 'required|in:pregnancy,menopause,breastfeeding,medical,other',
    //         'notes' => 'nullable|string|max:500',
    //     ]);

    //     $user = $request->user();

    //     $status = TrackingStatus::create([
    //         'user_id' => $user->id,
    //         'status' => 'paused',
    //         'pause_reason' => $request->reason,
    //         'paused_at' => now(),
    //         'notes' => $request->notes,
    //     ]);

    //     // KEEP predictions 
    //     // Berguna untuk tracking history

    //     Log::info('Tracking paused', [
    //         'user_id' => $user->id,
    //         'reason' => $request->reason,
    //         'paused_at' => $status->paused_at,
    //     ]);

    //     $this->recommendationService->clearCache($user->id);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Tracking berhasil dijeda',
    //         'data' => $status,
    //     ]);
    // }

    public function pause(Request $request)
    {
        $request->validate([
            'reason' => 'required|in:pregnancy,menopause,breastfeeding,medical,other',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $status = TrackingStatus::create([
            'user_id' => $user->id,
            'status' => 'paused',
            'pause_reason' => $request->reason,
            'paused_at' => now(),
            'notes' => $request->notes,
        ]);

        // Hapus semua predictions saat pause
        PredictedCycle::where('user_id', $user->id)->delete();
        
        $this->recommendationService->clearCache($user->id);

        Log::info('Tracking paused & predictions deleted', [
            'user_id' => $user->id,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tracking berhasil dijeda',
            'data' => $status,
        ]);
    }
    

    public function resume(Request $request)
    {
        $user = $request->user();

        $pausedStatus = TrackingStatus::where('user_id', $user->id)
            ->where('status', 'paused')
            ->latest()
            ->first();

        if ($pausedStatus) {
            $pausedStatus->update([
                'status' => 'active',
                'resumed_at' => now(),
            ]);
            $status = $pausedStatus;
            
            Log::info('Tracking resumed (updated existing)', [
                'user_id' => $user->id,
                'resumed_at' => $status->resumed_at,
            ]);
        } else {
            $status = TrackingStatus::create([
                'user_id' => $user->id,
                'status' => 'active',
                'resumed_at' => now(),
            ]);
            
            Log::info('Tracking resumed (created new)', [
                'user_id' => $user->id,
                'resumed_at' => $status->resumed_at,
            ]);
        }

        $this->recommendationService->clearCache($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Tracking berhasil diaktifkan kembali. Catat siklus menstruasi untuk memulai prediksi baru.',
            'data' => [
                'status' => $status->status,
                'resumed_at' => $status->resumed_at->format('Y-m-d H:i:s'),
                'next_steps' => 'Catat menstruasi Anda untuk membuat prediksi baru',
            ],
        ]);
    }
}
