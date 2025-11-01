<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Membership Retention Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the membership retention system
    |
    */

    // Default minimum points required per month
    'minimum_points_per_month' => env('RETENTION_MIN_POINTS', 1000),

    // Repair cost multiplier
    'repair_cost_per_point' => env('RETENTION_REPAIR_COST', 1.5),

    // Advance renewal discount (0.9 = 10% discount)
    'advance_renewal_discount' => env('RETENTION_ADVANCE_DISCOUNT', 0.9),

    // Grace period in days before expiration
    'grace_period_days' => env('RETENTION_GRACE_PERIOD', 3),

    // Warning days before expiry
    'warning_days_before_expiry' => env('RETENTION_WARNING_DAYS', 7),

    // Enable/disable retention system
    'enabled' => env('RETENTION_ENABLED', true),

    // Block commission if retention expired
    'block_commission_if_expired' => env('RETENTION_BLOCK_COMMISSION', true),

    // Auto-initialize new users
    'auto_initialize_new_users' => env('RETENTION_AUTO_INIT', true),

    // Count commission as points
    'commission_to_points_ratio' => env('RETENTION_COMMISSION_RATIO', 1.0),

    // Notification settings
    'notifications' => [
        'expiring_soon' => true,
        'expired' => true,
        'achieved' => true,
    ],
];
