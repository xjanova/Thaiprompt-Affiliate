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
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Food Passport Authorization Policies
        FoodProduct::class => FoodProductPolicy::class,
        QualityCheckpoint::class => QualityCheckpointPolicy::class,
        CarbonCredit::class => CarbonCreditPolicy::class,
        FoodCertification::class => FoodCertificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Additional authorization logic can be added here
        // For example: Gates, Before callbacks, etc.
    }
}
