<?php

namespace App\Http\Controllers;

use App\Models\HealthCondition;
use App\Models\UserHealthCondition;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class HealthProfileController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function setupHealthCondition(Request $request)
    {
        $validated = $request->validate([
            'health_condition_ids' => 'nullable|array', // bisa kosong (kalau dilewati)
            'health_condition_ids.*' => 'exists:health_conditions,id',
        ]);

        $user = $request->user();

        // Kalau user memilih untuk lewati (tidak kirim kondisi apapun)
        if (empty($validated['health_condition_ids'])) {
            // Kosongkan semua relasi kondisi kesehatan
            $user->healthConditions()->sync([]);

            $this->recommendationService->clearCache($user->id);
            $recommendations = $this->recommendationService->generateRecommendations($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Tidak ada kondisi kesehatan yang disimpan (dilewati)',
                // 'recommendations' => $recommendations,
            ]);
        }

        // Sinkronkan relasi dengan data baru
        $user->healthConditions()->sync($validated['health_condition_ids']);

        $this->recommendationService->clearCache($user->id);
        $recommendations = $this->recommendationService->generateRecommendations($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Kondisi kesehatan berhasil diperbarui',
            'data' => [
                'health_conditions' => $user->healthConditions()->get(),
                // 'recommendations' => $recommendations, 
            ], 
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $conditions = $user->healthConditions()->get();

        return response()->json([
            'success' => true,
            'data' => $conditions,
        ]);
    }


    // untuk ambil data kondisi kesehatan yang tersedia
    public function getHealthConditions()
    {
        try {
            $conditions = HealthCondition::all(['id', 'condition_name', 'description']);
            
            return response()->json([
                'success' => true,
                'data' => $conditions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch health conditions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


// class HealthProfileController extends Controller
// {
//     public function setupHealthCondition(Request $request)
//     {
//         $request->validate([
//             'health_condition_id' => 'nullable|exists:health_conditions,id',
//         ]);

//         $userId = $request->user()->id;

//         //lewati pengisian kondisi kesehatan
//         if (!$request->health_condition_id) {
//             return response()->json([
//                 'success' => true,
//                 'message' => 'Tidak ada kondisi kesehatan yang disimpan (dilewati)',
//             ]);
//         }

//         // cek apakah kondisi sudah ada, supaya gak duplikat
//         $exists = UserHealthCondition::where('user_id', $userId)
//             ->where('health_condition_id', $request->health_condition_id)
//             ->exists();

//         if ($exists) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Kondisi ini telah ditambahkan sebelumnya',
//             ], 409);
//         }

//         // simpan kondisi kesehatan baru
//         $userCondition = UserHealthCondition::create([
//             'user_id' => $userId,
//             'health_condition_id' => $request->health_condition_id,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Kondisi kesehatan berhasil disimpan',
//             'data' => $userCondition,
//         ], 201);
//     }

//     public function index(Request $request)
//     {
//         $userId = $request->user()->id;
//         $conditions = UserHealthCondition::with('healthCondition')
//             ->where('user_id', $userId)
//             ->get();

//         return response()->json([
//             'success' => true,
//             'data' => $conditions,
//         ]);
//     }
// }
