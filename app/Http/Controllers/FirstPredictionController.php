<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PredictedCycle;

class FirstPredictionController extends Controller
{
    public function show(Request $request)
    {
        $prediction = PredictedCycle::where ('user_id', $request->user()->id)
            ->latest('generated_at')
            ->first();

        if (!$prediction) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada prediksi yang tercatat.',
            ], 404);
        }

        return response()->json([
                'success' => true,
                'data' => $prediction,
            ]);
    }

}