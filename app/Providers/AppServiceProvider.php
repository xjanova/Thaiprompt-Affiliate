<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register WalletService as singleton
        $this->app->singleton(\App\Services\WalletService::class);

        // Register MembershipRetentionService as singleton
        $this->app->singleton(\App\Services\MembershipRetentionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Tailwind pagination views
        Paginator::useTailwind();

        // Register Commission Observer for Retention System
        \App\Models\Commission::observe(\App\Observers\CommissionObserver::class);
    }
}
