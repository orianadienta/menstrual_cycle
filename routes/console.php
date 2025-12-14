<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendPeriodReminderJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Dispatch period reminder job setiap hari jam tertentu
 * 
 * FLOW:
 * 1. Scheduler trigger di jam tertentu
 * 2. Dispatch SendPeriodReminderJob ke queue
 * 3. Queue worker process job
 * 4. Job call PredictionReminderService
 * 5. Service queue notifications ke FCM
 * 6. Queue worker process notifications
 * 7. Send via FCM ke devices
 */
Schedule::job(new SendPeriodReminderJob())
    ->dailyAt('15:02')
    ->timezone('Asia/Makassar')
    ->name('dispatch-period-reminder-job')
    ->onOneServer()
    ->onSuccess(function () {
        Log::info('Period reminder job dispatched successfully');
    })
    ->onFailure(function () {
        Log::error('Period reminder job dispatch failed');
    });