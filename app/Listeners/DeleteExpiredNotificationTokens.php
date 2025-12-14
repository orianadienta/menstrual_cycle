<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationFailed;
use NotificationChannels\Fcm\FcmChannel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class DeleteExpiredNotificationTokens
{
    /**
     * Handle notification failure event
     */
    public function handle(NotificationFailed $event): void
    {
        // Hanya handle FCM channel failures
        if ($event->channel !== FcmChannel::class) {
            return;
        }

        try {
            // Log full event data for debugging
            Log::warning('Notification failed event received', [
                'channel' => $event->channel,
                'notifiable_id' => $event->notifiable->id,
                'notification_class' => get_class($event->notification),
                'data_keys' => array_keys($event->data ?? []),
            ]);

            $report = Arr::get($event->data, 'report');
            
            if (!$report) {
                Log::warning('No report in notification failed event', [
                    'event_data' => $event->data,
                ]);
                return;
            }

            $target = $report->target();
            
            if (!$target) {
                Log::warning('No target in notification report', [
                    'report_class' => get_class($report),
                ]);
                return;
            }

            $tokenValue = $target->value();

            // Get error details
            $errorMessage = 'Unknown error';
            $errorCode = null;
            
            if (method_exists($report, 'error') && $report->error()) {
                $error = $report->error();
                $errorMessage = $error->getMessage();
                $errorCode = $error->getCode();
            }

            Log::warning('FCM notification failed - will delete expired token', [
                'user_id' => $event->notifiable->id,
                'token' => substr($tokenValue, 0, 20) . '...',
                'error_message' => $errorMessage,
                'error_code' => $errorCode,
                'full_error' => json_encode($event->data['error'] ?? 'N/A'),
            ]);

            // CRITICAL: Jangan hapus token kalau error bukan karena invalid token
            // Only delete if error is:
            // - INVALID_ARGUMENT (invalid token)
            // - UNREGISTERED (device uninstalled app)
            // - NOT_FOUND (token expired)
            $shouldDelete = in_array($errorCode, [
                'INVALID_ARGUMENT',
                'UNREGISTERED', 
                'NOT_FOUND',
            ]) || str_contains($errorMessage, 'invalid') 
               || str_contains($errorMessage, 'unregistered')
               || str_contains($errorMessage, 'not found');

            if (!$shouldDelete) {
                Log::info('Token not deleted - error is not token-related', [
                    'error_message' => $errorMessage,
                    'error_code' => $errorCode,
                ]);
                return;
            }

            // Hapus token yang gagal dari database
            $deleted = $event->notifiable->deviceTokens()
                ->where('token', $tokenValue)
                ->delete();

            if ($deleted) {
                Log::info('Expired FCM token deleted', [
                    'user_id' => $event->notifiable->id,
                    'token' => substr($tokenValue, 0, 20) . '...',
                    'reason' => $errorMessage,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error handling notification failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}