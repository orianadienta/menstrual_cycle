<?php

namespace App\Services;

use App\Models\PredictedCycle;
use App\Notifications\PeriodReminderNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PredictionReminderService
{
    public function sendPeriodReminder()
    {
        $today = Carbon::today();

        Log::info('Period reminder process started', [
            'date' => $today->toDateString(),
        ]);

        // Cari semua prediksi hari ini
        $predictions = PredictedCycle::with('user')
            ->where('predicted_start_date', $today)
            ->get();

        Log::info('Found predictions for today', [
            'count' => $predictions->count(),
            'prediction_ids' => $predictions->pluck('id')->toArray(),
            'user_ids' => $predictions->pluck('user_id')->toArray(),
        ]);

        if ($predictions->isEmpty()) {
            Log::info('No predictions found for today');
            return;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($predictions as $prediction) {
            $user = $prediction->user;

            Log::info('Processing prediction', [
                'prediction_id' => $prediction->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            // Cek apakah user punya device tokens
            if (!$user->canReceiveNotifications()) {
                Log::info('User has no active device tokens - SKIPPED', [
                    'user_id' => $user->id,
                    'token_count' => $user->deviceTokens()->count(),
                ]);
                $skipped++;
                continue;
            }

            try {
                // Log sebelum queue
                $tokens = $user->routeNotificationForFcm();
                Log::info('Queueing notification', [
                    'user_id' => $user->id,
                    'predicted_cycle_id' => $prediction->id,
                    'device_count' => count($tokens),
                    'first_token' => !empty($tokens) ? substr($tokens[0], 0, 30) . '...' : 'none',
                ]);

                // Queue notification via FCM
                $user->notify(new PeriodReminderNotification($prediction));

                Log::info('Period reminder notification queued successfully', [
                    'user_id' => $user->id,
                    'predicted_cycle_id' => $prediction->id,
                ]);

                $sent++;

            } catch (\Exception $e) {
                Log::error('Failed to queue period reminder', [
                    'user_id' => $user->id,
                    'predicted_cycle_id' => $prediction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $skipped++;
            }
        }

        Log::info('Period reminder process completed', [
            'total_predictions' => $predictions->count(),
            'sent' => $sent,
            'skipped' => $skipped,
        ]);
    }
}