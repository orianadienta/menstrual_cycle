<?php

namespace App\Services;

use App\Models\CycleProfile;
use App\Models\PredictedCycle;
use Illuminate\Support\Carbon;

class PredictionService
{
    public function generateInitialPrediction($userId)
    {
        $profile = CycleProfile::where('user_id', $userId)->first();

        if (!$profile || !$profile->last_period_start) {
            return null;
        }

        $cycleLength = $profile->initial_cycle_length ?? 28;
        $periodDuration = $profile->initial_period_duration ?? 5;

        $predictedStart = Carbon::parse($profile->last_period_start)->addDays($cycleLength);
        $predictedEnd = $predictedStart->copy()->addDays($periodDuration - 1);
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
        ]);
    }
}
