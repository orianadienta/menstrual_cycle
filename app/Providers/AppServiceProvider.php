<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Cycle;
use App\Observers\CycleObserver;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Messaging;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Messaging::class, function ($app) {
            $path = storage_path('app/firebase_credentials.json');
            $factory = (new Factory)->withServiceAccount($path);
            return $factory->createMessaging();
        });
    }

    public function boot(): void
    {
        Cycle::observe(CycleObserver::class);
     
    }
}