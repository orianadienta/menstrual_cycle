<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CycleReportService;
use App\Models\Cycle;
use App\Observers\CycleObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(CycleReportService::class, function ($app) {
        //     return new CycleReportService();
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cycle::observe(CycleObserver::class);
    }
}
