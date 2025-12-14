<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class PeriodReminderNotification extends Notification implements ShouldQueue
{
    public $predictedCycle;
    
    // Queue settings
    public $queue = 'default';
    public $connection = 'database';
    public $delay = 0;
    public $tries = 3;
    public $timeout = 60;

    public function __construct($predictedCycle)
    {
        $this->predictedCycle = $predictedCycle;
    }

    public function via($notifiable)
    {
        if (!$notifiable->canReceiveNotifications()) {
            Log::warning('User cannot receive notifications', [
                'user_id' => $notifiable->id,
            ]);
            return [];
        }

        // Get tokens for debugging
        $tokens = $notifiable->routeNotificationForFcm();
        
        Log::info('Preparing to send notification', [
            'user_id' => $notifiable->id,
            'token_count' => count($tokens),
            'first_token_preview' => !empty($tokens) ? substr($tokens[0], 0, 30) . '...' : 'none',
        ]);

        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        // Safe Carbon conversion
        $predictedDate = $this->predictedCycle->predicted_start_date;
        if (!$predictedDate instanceof \Carbon\Carbon) {
            $predictedDate = \Carbon\Carbon::parse($predictedDate);
        }

        Log::info('Building FCM message', [
            'user_id' => $notifiable->id,
            'predicted_cycle_id' => $this->predictedCycle->id,
            'predicted_date' => $predictedDate->toDateString(),
        ]);

        // ✅ FIXED: Removed invalid 'priority' field from android.notification
        return (new FcmMessage(notification: new FcmNotification(
            title: 'Pengingat Menstruasi',
            body: 'Hari ini Anda diperkirakan menstruasi. Jangan lupa catat siklusmu!',
        )))
        ->data([
            'type' => 'period_reminder',
            'predicted_cycle_id' => (string)$this->predictedCycle->id,
            'predicted_date' => $predictedDate->toDateString(),
        ])
        ->custom([
            'android' => [
                'notification' => [
                    'color' => '#FF1493',
                    'sound' => 'default',
                    'channel_id' => 'period_reminders',
                    // ❌ REMOVED: 'priority' is not valid here
                ],
                'priority' => 'high', // ✅ MOVED HERE: priority goes in android root, not notification
                'fcm_options' => [
                    'analytics_label' => 'period_reminder',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
                'fcm_options' => [
                    'analytics_label' => 'period_reminder',
                ],
            ],
        ]);
    }

    /**
     * Handle notification failure
     */
    public function failed($notifiable, $exception = null)
    {
        Log::error('Period reminder notification failed', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id,
            'predicted_cycle_id' => optional($this->predictedCycle)->id,
            'exception_class' => $exception ? get_class($exception) : 'null',
            'exception_message' => $exception ? $exception->getMessage() : 'null',
            'exception_code' => $exception ? $exception->getCode() : 'null',
        ]);
    }
}