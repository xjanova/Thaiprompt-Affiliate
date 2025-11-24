<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Food Passport Events
        \App\Events\FoodPassport\ProductJourneyCompletedEvent::class => [
            \App\Listeners\FoodPassport\AssessCarbonCreditEligibilityListener::class,
        ],

        \App\Events\FoodPassport\CarbonCreditTradedEvent::class => [
            \App\Listeners\FoodPassport\UpdateMarketplaceStatisticsListener::class,
        ],

        \App\Events\FoodPassport\QualityCheckFailedEvent::class => [
            \App\Listeners\FoodPassport\NotifyStakeholdersOfQualityIssueListener::class,
        ],

        // AI Rental GPU System Events
        \App\Events\AiRental\DeploymentCreated::class => [
            \App\Listeners\AiRental\NotifyUserOfDeploymentCreated::class,
        ],

        \App\Events\AiRental\DeploymentStatusChanged::class => [
            \App\Listeners\AiRental\LogDeploymentStatusChange::class,
        ],

        \App\Events\AiRental\DeploymentHealthCheckFailed::class => [
            \App\Listeners\AiRental\HandleHealthCheckFailure::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
