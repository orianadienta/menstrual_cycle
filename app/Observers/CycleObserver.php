<?php

namespace App\Observers;

use App\Models\Cycle;
use Illuminate\Support\Carbon;

class CycleObserver
{
    /**
     * Handle the Cycle "created" event.
     */
    public function created(Cycle $cycle)
    {
        if ($cycle->start_date && $cycle->end_date) {
            $cycle->period_duration = Carbon::parse($cycle->start_date)
                ->diffInDays(Carbon::parse($cycle->end_date)) + 1;
        }
    }

    /**
     * Handle the Cycle "updated" event.
     */
    public function updated(Cycle $cycle)
    {
        if ($cycle->start_date && $cycle->end_date) {
            $cycle->period_duration = Carbon::parse($cycle->start_date)
                ->diffInDays(Carbon::parse($cycle->end_date)) + 1;
        }
    }

        /**
     * Handle the Cycle "saved" event.
     * Hitung cycle_length setelah data tersimpan
     */
    public function saved(Cycle $cycle)
    {
        // Cari siklus sebelumnya untuk hitung cycle_length
        $previousCycle = Cycle::where('user_id', $cycle->user_id)
            ->where('id', '<', $cycle->id)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc')
            ->first();

        if ($previousCycle) {
            $cycleLength = Carbon::parse($previousCycle->start_date)
                ->diffInDays(Carbon::parse($cycle->start_date));
            
            // Update cycle_length tanpa trigger observer lagi
            $cycle->updateQuietly(['cycle_length' => $cycleLength]);
        }
    }

    /**
     * Handle the Cycle "deleted" event.
     */
    // public function deleted(Cycle $cycle): void
    // {
    //     //
    // }

    // /**
    //  * Handle the Cycle "restored" event.
    //  */
    // public function restored(Cycle $cycle): void
    // {
    //     //
    // }

    // /**
    //  * Handle the Cycle "force deleted" event.
    //  */
    // public function forceDeleted(Cycle $cycle): void
    // {
    //     //
    // }
}
