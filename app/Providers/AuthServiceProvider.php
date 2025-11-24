<?php

namespace App\Providers;

use App\Models\CarbonCredit;
use App\Models\FoodCertification;
use App\Models\FoodProduct;
use App\Models\QualityCheckpoint;
use App\Policies\CarbonCreditPolicy;
use App\Policies\FoodCertificationPolicy;
use App\Policies\FoodProductPolicy;
use App\Policies\QualityCheckpointPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Authorization Service Provider
 *
 * Registers all Food Passport authorization policies
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * ⚠️ SECURITY: เพิ่ม policies เพื่อป้องกัน IDOR และ unauthorized access
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Food Passport Authorization Policies
        FoodProduct::class => FoodProductPolicy::class,
        QualityCheckpoint::class => QualityCheckpointPolicy::class,
        CarbonCredit::class => CarbonCreditPolicy::class,
        FoodCertification::class => FoodCertificationPolicy::class,

        // ✅ เพิ่ม Admin Resource Policies
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Wallet::class => \App\Policies\WalletPolicy::class,
        \App\Models\NFCCard::class => \App\Policies\NFCCardPolicy::class,
        \App\Models\NFCReader::class => \App\Policies\NFCReaderPolicy::class,
        \App\Models\KycVerification::class => \App\Policies\KycVerificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * ⚠️ SECURITY: เพิ่ม Gates สำหรับ permission-based authorization
     */
    public function boot(): void
    {
        // ✅ Permission-based Gates
        \Illuminate\Support\Facades\Gate::define('view_all_wallets', function ($user) {
            return $user->hasPermission('view_all_wallets')
                || $user->role === 'admin'
                || $user->is_super_admin;
        });

        \Illuminate\Support\Facades\Gate::define('manage-users', function ($user) {
            return $user->hasPermission('manage-users')
                || $user->role === 'admin'
                || $user->is_super_admin;
        });

        \Illuminate\Support\Facades\Gate::define('manage-analytics', function ($user) {
            return $user->hasPermission('manage-analytics')
                || $user->role === 'admin'
                || $user->is_super_admin;
        });

        \Illuminate\Support\Facades\Gate::define('manage-settings', function ($user) {
            return $user->hasPermission('manage-settings')
                || $user->role === 'admin'
                || $user->is_super_admin;
        });

        \Illuminate\Support\Facades\Gate::define('manage-nfc', function ($user) {
            return $user->hasPermission('manage-nfc')
                || $user->role === 'admin'
                || $user->is_super_admin;
        });

        \Illuminate\Support\Facades\Gate::define('approve-kyc', function ($user) {
            return $user->hasPermission('approve-kyc')
                || $user->role === 'admin'
                || $user->is_super_admin;
        });

        \Illuminate\Support\Facades\Gate::define('manage-wallet-transactions', function ($user) {
            return $user->hasPermission('manage-wallet-transactions')
                || $user->role === 'admin'
                || $user->is_super_admin;
        });

        // ✅ Super Admin Gate (can do anything)
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->is_super_admin) {
                return true;  // Super Admin มีสิทธิ์ทุกอย่าง
            }
        });
    }
}
