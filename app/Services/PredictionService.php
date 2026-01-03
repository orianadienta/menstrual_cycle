<?php

namespace App\Services;

use App\Models\Cycle;
use App\Models\CycleProfile;
use App\Models\PredictedCycle;
use App\Models\TrackingStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    private const DEFAULT_CYCLE_LENGTH = 28;
    private const DEFAULT_PERIOD_DURATION = 5;
    private const MIN_CYCLE_LENGTH = 21;
    private const MAX_CYCLE_LENGTH = 45;

    public function generatePrediction($userId)
    {
        Log::info('Starting prediction generation', ['user_id' => $userId]);

        $trackingStatus = TrackingStatus::where('user_id', $userId)->latest()->first();

        if ($trackingStatus?->status === 'paused') {
            PredictedCycle::where('user_id', $userId)->delete();
            Log::info('User paused, predictions deleted', ['user_id' => $userId]);
            return null;
        }

        // hapus prediksi lama sebelum generate yang baru
        PredictedCycle::where('user_id', $userId)->delete();
        Log::info('Old predictions deleted before generating new one', ['user_id' => $userId]);


        $query = Cycle::where('user_id', $userId)
            ->whereNotNull('end_date')
            ->orderBy('start_date', 'desc');

        if ($trackingStatus?->resumed_at) {
            $cyclesAfterResume = Cycle::where('user_id', $userId)
                ->whereNotNull('end_date')
                ->where('start_date', '>=', $trackingStatus->resumed_at)
                ->orderBy('start_date', 'desc')
                ->take(3)
                ->get();

            if ($cyclesAfterResume->isNotEmpty()) {
                $recentCycles = $cyclesAfterResume;
                Log::info('Using cycles after resume', [
                    'cycles_count' => $recentCycles->count(),
                    'cycles' => $recentCycles->pluck('start_date', 'cycle_length')->toArray(),
                ]);
            } else {
                $recentCycles = $query->take(3)->get();
                Log::info('No cycles after resume, using latest', [
                    'cycles_count' => $recentCycles->count(),
                ]);
            }
        } else {
            $recentCycles = $query->take(3)->get();
            Log::info('Normal mode', ['cycles_count' => $recentCycles->count()]);
        }

        if ($recentCycles->isEmpty()) {
            Log::warning('No cycles found');
            return null;
        }

        $latestCycle = $recentCycles->first();
        $cycleLength = $this->calculateCycleLength($recentCycles, $trackingStatus, $userId);
        $periodDuration = $this->calculatePeriodDuration($latestCycle, $userId);

        $predictedStart = Carbon::parse($latestCycle->start_date)->addDays($cycleLength);
        $predictedEnd = $predictedStart->copy()->addDays($periodDuration - 1);
        $ovulationDate = $predictedStart->copy()->subDays(14);
        $fertileStart = $ovulationDate->copy()->subDays(5);
        $fertileEnd = $ovulationDate->copy();

        Log::info('Prediction calculated', [
            'latest_cycle' => $latestCycle->start_date,
            'predicted_start' => $predictedStart->toDateString(),
            'cycle_length' => $cycleLength,
            'period_duration' => $periodDuration,
        ]);

        try {
            $prediction = PredictedCycle::create([
                'user_id' => $userId,
                'predicted_start_date' => $predictedStart,
                'predicted_end_date' => $predictedEnd,
                'ovulation_date' => $ovulationDate,
                'fertile_window_start' => $fertileStart,
                'fertile_window_end' => $fertileEnd,
                'cycle_length' => $cycleLength,
                'period_duration' => $periodDuration,
                'generated_at' => now(),
            ]);

            Log::info('Prediction created', ['prediction_id' => $prediction->id]);
            return $prediction;

        } catch (\Exception $e) {
            Log::error('Prediction creation failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function calculateCycleLength($recentCycles, $trackingStatus, $userId)
    {
        // Post-resume logic
        if ($trackingStatus?->resumed_at) {
            $cyclesWithLength = $recentCycles->filter(fn($c) => 
                $c->cycle_length !== null && 
                $c->cycle_length >= self::MIN_CYCLE_LENGTH && 
                $c->cycle_length <= self::MAX_CYCLE_LENGTH
            );
            
            if ($cyclesWithLength->count() >= 2) {
                $avgLength = round($cyclesWithLength->avg('cycle_length'));
                Log::info('Post-resume: average from new cycles', [
                    'average' => $avgLength,
                    'cycles' => $cyclesWithLength->pluck('cycle_length')->toArray(),
                ]);
                return $avgLength;
            }
            
            // Fallback ke cycle profile
            $cycleProfile = CycleProfile::where('user_id', $userId)->first();
            if ($cycleProfile && $cycleProfile->initial_cycle_length) {
                $profileLength = $cycleProfile->initial_cycle_length;
                
                if ($profileLength >= self::MIN_CYCLE_LENGTH && $profileLength <= self::MAX_CYCLE_LENGTH) {
                    Log::info('Post-resume: using cycle profile', [
                        'profile_length' => $profileLength,
                    ]);
                    return $profileLength;
                }
            }
            
            Log::info('Post-resume: using default', [
                'default' => self::DEFAULT_CYCLE_LENGTH,
            ]);
            return self::DEFAULT_CYCLE_LENGTH;
        }

        // Normal mode: rata-rata dari cycles dengan data valid
        $cyclesWithLength = $recentCycles->filter(fn($c) => 
            $c->cycle_length !== null && 
            $c->cycle_length >= self::MIN_CYCLE_LENGTH && 
            $c->cycle_length <= self::MAX_CYCLE_LENGTH
        );

        if ($cyclesWithLength->count() >= 2) {
            $avgLength = round($cyclesWithLength->avg('cycle_length'));
            Log::info('Normal: average from history', [
                'average' => $avgLength,
                'cycles' => $cyclesWithLength->pluck('cycle_length')->toArray(),
            ]);
            return $avgLength;
        } elseif ($cyclesWithLength->count() === 1) {
            $cycleLength = $cyclesWithLength->first()->cycle_length;
            Log::info('Normal: single cycle', ['length' => $cycleLength]);
            return $cycleLength;
        }

        Log::info('Normal: default (no history)', [
            'default' => self::DEFAULT_CYCLE_LENGTH,
        ]);
        return self::DEFAULT_CYCLE_LENGTH;
    }

    private function calculatePeriodDuration($latestCycle, $userId)
    {
        $periodDuration = $latestCycle->period_duration;

        if ($periodDuration && $periodDuration >= 2 && $periodDuration <= 10) {
            Log::info('Using stored period duration', ['duration' => $periodDuration]);
            return $periodDuration;
        }

        if ($latestCycle->start_date && $latestCycle->end_date) {
            $calculated = Carbon::parse($latestCycle->start_date)
                ->diffInDays(Carbon::parse($latestCycle->end_date)) + 1;
            
            if ($calculated >= 2 && $calculated <= 10) {
                Log::info('Calculated period duration', ['duration' => $calculated]);
                return $calculated;
            }
        }

        Log::warning('Using default period duration', [
            'default' => self::DEFAULT_PERIOD_DURATION,
        ]);
        return self::DEFAULT_PERIOD_DURATION;
    }
}