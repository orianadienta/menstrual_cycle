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
            $serviceAccount = [
                'type' => 'service_account',
                'project_id' => env('FIREBASE_PROJECT_ID'),
                'client_email' => env('FIREBASE_CLIENT_EMAIL'),
                'private_key' => str_replace(
                    '\\n',
                    "\n",
                    env('FIREBASE_PRIVATE_KEY')
                ),
            ];

            $factory = (new Factory)->withServiceAccount($serviceAccount);

            return $factory->createMessaging();
        });
    }

    public function boot(): void
    {
        Cycle::observe(CycleObserver::class);
     
    }
}