<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Commission;
use App\Observers\CommissionObserver;

class MembershipRetentionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Commission Observer
        Commission::observe(CommissionObserver::class);
    }
}
