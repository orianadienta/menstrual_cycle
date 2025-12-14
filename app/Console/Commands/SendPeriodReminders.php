<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendPeriodReminderJob;
use Illuminate\Support\Facades\Log;

class SendPeriodReminders extends Command
{
    protected $signature = 'reminders:send-period';
    protected $description = 'Dispatch period reminder job to queue (manual trigger)';

    public function handle()
    {
        $this->info('Dispatching SendPeriodReminderJob to queue...');

        try {
            SendPeriodReminderJob::dispatch();

            Log::info('SendPeriodReminderJob dispatched manually');
            $this->info('✓ Job dispatched successfully');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch SendPeriodReminderJob', [
                'error' => $e->getMessage(),
            ]);

            $this->error('✗ Failed to dispatch: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
