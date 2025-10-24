<?php

namespace App\Http\Controllers;

use App\Models\HealthCondition;
use App\Models\UserHealthCondition;
use Illuminate\Http\Request;

class HealthProfileController extends Controller
{
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
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada kondisi kesehatan yang disimpan (dilewati)',
            ]);
        }

        // Sinkronkan relasi dengan data baru
        $user->healthConditions()->sync($validated['health_condition_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Kondisi kesehatan berhasil diperbarui',
            'data' => $user->healthConditions()->get(),
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
