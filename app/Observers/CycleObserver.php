<?php

namespace App\Observers;

use App\Models\Cycle;
use App\Models\TrackingStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CycleObserver
{
    public function created(Cycle $cycle)
    {
        if ($cycle->start_date && $cycle->end_date) {
            $cycle->period_duration = Carbon::parse($cycle->start_date)
                ->diffInDays(Carbon::parse($cycle->end_date)) + 1;
        }
    }

    public function updated(Cycle $cycle)
    {
        if ($cycle->start_date && $cycle->end_date) {
            $cycle->period_duration = Carbon::parse($cycle->start_date)
                ->diffInDays(Carbon::parse($cycle->end_date)) + 1;
        }
    }

    public function saved(Cycle $cycle)
    {
        // Check tracking status dulu
        $trackingStatus = TrackingStatus::where('user_id', $cycle->user_id)
            ->latest()
            ->first();

        // Cari siklus sebelumnya
        $previousCycle = Cycle::where('user_id', $cycle->user_id)
            ->where('id', '<', $cycle->id)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc')
            ->first();

        if ($previousCycle) {
            // Jangan hitung cycle_length jika ada jeda pause/resume
            $shouldCalculate = true;

            // Jika ada resumed_at, cek apakah previous cycle sebelum resume
            if ($trackingStatus && $trackingStatus->resumed_at) {
                $resumedAt = Carbon::parse($trackingStatus->resumed_at);
                $previousStart = Carbon::parse($previousCycle->start_date);
                
                // Jika previous cycle sebelum resume, SKIP calculation
                if ($previousStart->lt($resumedAt)) {
                    $shouldCalculate = false;
                    Log::info('Skipping cycle_length calculation (previous cycle before resume)', [
                        'cycle_id' => $cycle->id,
                        'previous_cycle_date' => $previousStart->toDateString(),
                        'resumed_at' => $resumedAt->toDateString(),
                    ]);
                }
            }

            if ($shouldCalculate) {
                $cycleLength = Carbon::parse($previousCycle->start_date)
                    ->diffInDays(Carbon::parse($cycle->start_date));

                // Validasi: cycle_length harus masuk akal (21-45 hari)
                if ($cycleLength >= 21 && $cycleLength <= 45) {
                    $cycle->updateQuietly(['cycle_length' => $cycleLength]);
                    
                    Log::info('Cycle length calculated', [
                        'cycle_id' => $cycle->id,
                        'cycle_length' => $cycleLength,
                        'from' => $previousCycle->start_date,
                        'to' => $cycle->start_date,
                    ]);
                } else {
                    Log::warning('Cycle length out of range, not saved', [
                        'cycle_id' => $cycle->id,
                        'calculated_length' => $cycleLength,
                        'valid_range' => '21-45 days',
                    ]);
                }
            } else {
                // Set cycle_length = NULL untuk cycle pertama post-resume
                $cycle->updateQuietly(['cycle_length' => null]);
                
                Log::info('First cycle after resume, cycle_length set to NULL', [
                    'cycle_id' => $cycle->id,
                ]);
            }
        }
    }
}