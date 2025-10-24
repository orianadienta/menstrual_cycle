<?php
namespace App\Services;

use App\Models\Cycle;
use App\Models\CycleProfile;
use App\Models\PredictedCycle;
use Illuminate\Support\Carbon;

class PredictionService
{
    public function generatePrediction($userId)
    {
        // Ambil 2 siklus terakhir untuk kalkulasi
        $recentCycles = Cycle::where('user_id', $userId)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc')  // ← Ubah jadi sort by start_date
            ->take(2)
            ->get();

        // Default value dari cycle profile kalau ada
        $profile = CycleProfile::where('user_id', $userId)->first();

        if ($recentCycles->isEmpty() && !$profile) {
            return null; // Tidak ada data
        }

        // Ambil siklus terakhir (yang paling baru)
        $latestCycle = $recentCycles->first();
        
        // Hitung rata-rata cycle length
        if ($recentCycles->count() >= 2) {
            $previousCycle = $recentCycles->last(); // Siklus sebelumnya
            
            // Hitung selisih hari antara start_date siklus terakhir dengan sebelumnya
            $cycleLength = Carbon::parse($previousCycle->start_date)
                ->diffInDays(Carbon::parse($latestCycle->start_date));
        } else {
            // Kalau baru 1 siklus, pakai default dari profile
            $cycleLength = $profile->initial_cycle_length ?? 28;
        }

        // Hitung period duration
        $periodDuration = Carbon::parse($latestCycle->start_date)
            ->diffInDays(Carbon::parse($latestCycle->end_date)) + 1;

        if ($periodDuration <= 0) {
            $periodDuration = $profile->initial_period_duration ?? 7;
        }

        // Prediksi siklus selanjutnya = start_date siklus terakhir + cycle length
        $predictedStart = Carbon::parse($latestCycle->start_date)->addDays($cycleLength);
        $predictedEnd = $predictedStart->copy()->addDays($periodDuration - 1);

        // Ovulasi biasanya 14 hari sebelum menstruasi berikutnya
        $ovulationDate = $predictedStart->copy()->subDays(14);
        $fertileStart = $ovulationDate->copy()->subDays(5);
        $fertileEnd = $ovulationDate->copy();

        return PredictedCycle::create([
            'user_id' => $userId,
            'predicted_start_date' => $predictedStart,
            'predicted_end_date' => $predictedEnd,
            'ovulation_date' => $ovulationDate,
            'fertile_window_start' => $fertileStart,
            'fertile_window_end' => $fertileEnd,
            'generated_at' => now(),
            'source' => 'auto',
        ]);
    }
}


// public function generatePrediction($userId)
//     {
//         $lastCycle = Cycle::where('user_id', $userId)
//             ->whereNotNull('end_date')
//             ->latest('end_date')
//             ->first();

//         if (!$lastCycle) {
//             return null;
//         }

//         $cycleLength = $lastCycle->initial_cycle_length ?? 28;
//         $periodDuration = $lastCycle->initial_period_duration ?? 7;

//         $predictedStart = Carbon::parse($lastCycle->start_date)->addDays($cycleLength);
//         $predictedEnd = $predictedStart->copy()->addDays($periodDuration - 1);
//         $ovulationDate = $predictedStart->copy()->subDays(14);
//         $fertileStart = $ovulationDate->copy()->subDays(5);
//         $fertileEnd = $ovulationDate->copy();

//         return PredictedCycle::create([
//             'user_id' => $userId,
//             'predicted_start_date' => $predictedStart,
//             'predicted_end_date' => $predictedEnd,
//             'ovulation_date' => $ovulationDate,
//             'fertile_window_start' => $fertileStart,
//             'fertile_window_end' => $fertileEnd,
//             'generated_at' => now(),
//             'source' => 'auto',
//         ]);
//     }