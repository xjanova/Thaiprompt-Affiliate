<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SpamDetectionService;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SpamDetectionService::class, function ($app) {
            return new SpamDetectionService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
