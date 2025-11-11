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


// ohh, jadi itu prediksi harus ditampilkan semua ya. terus klo ga dibatasin ini bisa? dan ini hasilnya berarti kalau bulannya udah lewat otomatis prediksi sebelumnya masih bisa keliatan? kalau di clue aku liat kalau bulannya udah lewat ya gak muncul lagi nanti prediksinya. cuman yang akan datang aja. tapi aku jadi bingung kalau misalnya telat mentsruasinya sampai prediksinya kelewatan di bulan it masa hlang? berarti prediksinya hilang kalau udha ada data baru aja?