<?php
namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\PredictionReminderService;
use Illuminate\Support\Facades\Log;

class SendPeriodReminderJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = [10, 30, 60];
    // public $queue = 'default';

    public function __construct() {}

    /**
     * Execute job
     * 
     * FLOW:
     * 1. Job di-process dari queue
     * 2. Call PredictionReminderService
     * 3. Service cari prediksi hari ini dan queue notifications
     */
    public function handle(PredictionReminderService $reminderService)
    {
        Log::info('SendPeriodReminderJob started', [
            'timestamp' => now(),
        ]);

        try {
            $reminderService->sendPeriodReminder();

            Log::info('SendPeriodReminderJob completed successfully', [
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('SendPeriodReminderJob execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempts' => $this->attempts(),
            ]);

            // Re-throw untuk trigger retry
            throw $e;
        }
    }

    /**
     * Handle job failure (after all retries exhausted)
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SendPeriodReminderJob permanently failed', [
            'error' => $exception->getMessage(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Display name untuk monitoring
     */
    public function displayName()
    {
        return 'Send Period Reminder';
    }
}