<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
     *
     * หมายเหตุ: CommissionObserver ถูกลบออกแล้วเนื่องจากระบบ Affiliate ถูกแทนที่ด้วย MLM
     */
    public function boot(): void
    {
        // Previously registered Commission Observer
        // Removed as Affiliate system has been replaced by MLM system
    }
}
