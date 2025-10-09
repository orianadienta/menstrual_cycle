<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CycleProfile;
use App\Services\PredictionService;
use Illuminate\Support\Carbon;
use App\Models\PredictedCycle;


class CycleProfileController extends Controller
{
    protected $predictionService;

    public function __construct(PredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    public function setup(Request $request)
    {
        $request->validate([
            'last_period_start' => 'required|date|before_or_equal:today',
            'initial_cycle_length' => 'nullable|integer|min:21|max:45',
            'initial_period_duration' => 'nullable|integer|min:2|max:10',
            'is_regular' => 'required|string|in:regular,irregular,unknown'
        ]);

        // $userId = 1;
        $userId = $request->user()->id;

        // Cek apakah user sudah punya profile sebelumnya
        $existing = CycleProfile::where('user_id', $userId)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Profile sudah ada untuk user ini.'
            ], 409);
        }

        // Simpan profile baru
        $profile = CycleProfile::create([
            'user_id' => $userId,
            'last_period_start' => $request->last_period_start,
            'initial_cycle_length' => $request->initial_cycle_length ?? 28, //prediksi default siklus normal
            'initial_period_duration' => $request->initial_period_duration ?? 6, //prediksi default durasi menstruasi
            'is_regular' => $request->is_regular,
        ]);

        // Generate prediksi awal
        $prediction = $this->predictionService->generateInitialPrediction($userId);

        return response()->json([
            'success' => true,
            'message' => 'Cycle profile berhasil dibuat',
            'data' => [
                'profile' => $profile->withoutRelations(),
                'prediction' => $prediction
            ]
        ], 201);
    }
}


// class CycleProfileController extends Controller
// {
//     public function setup(Request $request)
//     {
//         $request->validate([
//             'last_period_start' => 'required|date|before_or_equal:today',
//             'initial_cycle_length' => 'nullable|integer|min:21|max:45',
//             'initial_period_duration' => 'nullable|integer|min:2|max:10',
//             'is_regular' => 'required|string|in:regular,irregular,unknown'
//         ]);

//         $userId = $request->user()->id;

//         $profile = CycleProfile::create([
//             'user_id' => $userId,
//             'last_period_start' => $request->last_period_start,
//             'initial_cycle_length' => $request->initial_cycle_length ?? 28,
//             'initial_period_duration' => $request->initial_period_duration ?? 6,
//             'is_regular' => $request->is_regular,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Cycle profile berhasil dibuat',
//             'data' => $profile
//         ], 201);
//     }
// }