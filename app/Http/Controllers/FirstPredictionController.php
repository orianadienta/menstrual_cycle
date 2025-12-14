<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PredictedCycle;
use App\Models\TrackingStatus;
use Illuminate\Support\Facades\Log;

class FirstPredictionController extends Controller
{
    /**
     * Get first/initial prediction (yang di-generate saat setup cycle profile)
     * GET /api/first-prediction
     */
    public function show(Request $request)
    {
        $user = $request->user();

        $trackingStatus = TrackingStatus::where('user_id', $user->id)
            ->latest()
            ->first();
        
        if ($trackingStatus && $trackingStatus->status === 'paused') {
            Log::warning('User is paused, no prediction available', [
                'user_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Tracking saat ini dijeda, tidak ada prediksi awal.',
                'data' => null,
            ], 404);
        }

        // Ini adalah prediksi yang dibuat saat setup cycle profile
        $prediction = PredictedCycle::where('user_id', $user->id)
            ->oldest('generated_at')  // ← OLDEST = FIRST prediction
            ->first();

        if (!$prediction) {
            Log::warning('No first prediction found', [
                'user_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada prediksi awal yang tercatat. Silakan setup cycle profile terlebih dahulu.',
                'data' => null,
            ], 404);
        }

        Log::info('First prediction retrieved', [
            'prediction_id' => $prediction->id,
            'predicted_start_date' => $prediction->predicted_start_date,
            'generated_at' => $prediction->generated_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prediksi awal berhasil diambil',
            'data' => $prediction,
        ]);
    }
}