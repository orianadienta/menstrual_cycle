<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PredictedCycle;

class PredictionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $prediction = PredictedCycle::where('user_id', $user->id)
            ->latest('predicted_start_date')
            ->first();


        return response()->json([
            'success' => true,
            'message' => 'Prediksi terbaru berhasil diambil',
            'data' => $prediction,
        ]);

    }
}
