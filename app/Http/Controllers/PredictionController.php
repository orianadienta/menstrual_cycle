<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PredictedCycle;
use App\Models\TrackingStatus;
use App\Services\RecommendationService;
use App\Models\CycleProfile;

class PredictionController extends Controller
    {
        public function index(Request $request)
        {
            $user = $request->user();
            $trackingStatus = TrackingStatus::where('user_id', $user->id)->latest()->first();

            // Jika paused, return null
            if ($trackingStatus?->status === 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tracking saat ini dijeda',
                    'data' => null,
                ], 404);
            }

            // Get latest prediction - prioritas post-resume
            $query = PredictedCycle::where('user_id', $user->id);

            if ($trackingStatus?->resumed_at) {
                $query->where('created_at', '>=', $trackingStatus->resumed_at);
            }

            $prediction = $query->latest('created_at')->first();

            if (!$prediction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada prediksi terbaru',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Prediksi terbaru berhasil diambil',
                'data' => $prediction,
            ]);
        }
    }

    class FirstPredictionController extends Controller
    {
        public function show(Request $request)
        {
            $user = $request->user();
            $trackingStatus = TrackingStatus::where('user_id', $user->id)->latest()->first();

            if ($trackingStatus?->status === 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tracking saat ini dijeda',
                    'data' => null,
                ], 404);
            }

            $prediction = PredictedCycle::where('user_id', $user->id)
                ->oldest('generated_at')
                ->first();

            if (!$prediction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada prediksi awal',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $prediction,
            ]);
        }
    }

    class TrackingStatusController extends Controller
    {
        public function __construct(private RecommendationService $recommendationService) {}

        public function getStatus(Request $request)
        {
            $status = TrackingStatus::where('user_id', $request->user()->id)
                ->latest()
                ->first();

            if (!$status) {
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

            // Delete old predictions
            PredictedCycle::where('user_id', $user->id)->delete();
            $this->recommendationService->clearCache($user->id);

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

            $status = $pausedStatus
                ? $pausedStatus->update([
                    'status' => 'active',
                    'resumed_at' => now(),
                ]) ?? $pausedStatus
                : TrackingStatus::create([
                    'user_id' => $user->id,
                    'status' => 'active',
                    'resumed_at' => now(),
                ]);

            $this->recommendationService->clearCache($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Tracking berhasil diaktifkan kembali',
                'data' => [
                    'status' => $status->status,
                    'resumed_at' => $status->resumed_at,
                ],
            ]);
        }
    }