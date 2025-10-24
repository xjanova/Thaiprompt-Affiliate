<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NFC Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for NFC payment system
    |
    */

    'nfc' => [
        'enabled' => env('NFC_ENABLED', true),
        'timeout' => env('NFC_TIMEOUT', 30), // seconds
        'card_types' => [
            'standard' => [
                'name' => 'Standard Card',
                'limits' => [
                    'daily' => 10000,
                    'transaction' => 5000,
                ],
            ],
            'premium' => [
                'name' => 'Premium Card',
                'limits' => [
                    'daily' => 50000,
                    'transaction' => 25000,
                ],
            ],
            'vip' => [
                'name' => 'VIP Card',
                'limits' => [
                    'daily' => 100000,
                    'transaction' => 50000,
                ],
            ],
        ],
        'transaction_fee' => 0, // No fee for NFC payments
        'auto_balance_check' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Shop Verification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for shop verification system
    |
    */

    'verification' => [
        'enabled' => env('VERIFICATION_ENABLED', true),
        'auto_approve' => env('VERIFICATION_AUTO_APPROVE', false),
        'document_storage' => env('VERIFICATION_DOCUMENT_DISK', 'public'),
        'max_document_size' => env('MAX_DOCUMENT_SIZE', 5120), // KB
        'allowed_formats' => ['pdf', 'jpg', 'jpeg', 'png'],
        'review_turnaround_hours' => 48,

        'badges' => [
            'bronze' => [
                'name' => 'Bronze Verified',
                'icon' => '🥉',
                'color' => '#CD7F32',
                'requirements' => [
                    'id_card' => true,
                ],
                'benefits' => [
                    'Basic trust badge',
                    'Verification icon',
                ],
            ],
            'silver' => [
                'name' => 'Silver Verified',
                'icon' => '🥈',
                'color' => '#C0C0C0',
                'requirements' => [
                    'id_card' => true,
                    'business_registration' => true,
                    'tax_certificate' => true,
                ],
                'benefits' => [
                    'Enhanced visibility',
                    'Priority support',
                    'Featured in search',
                ],
            ],
            'gold' => [
                'name' => 'Gold Verified',
                'icon' => '🥇',
                'color' => '#FFD700',
                'requirements' => [
                    'id_card' => true,
                    'business_registration' => true,
                    'tax_certificate' => true,
                    'bank_verification' => true,
                ],
                'benefits' => [
                    'Top placement',
                    'Premium support',
                    'Marketing features',
                    'Reduced commission',
                ],
            ],
            'platinum' => [
                'name' => 'Platinum Verified',
                'icon' => '💎',
                'color' => '#E5E4E2',
                'requirements' => [
                    'id_card' => true,
                    'business_registration' => true,
                    'tax_certificate' => true,
                    'business_license' => true,
                    'bank_verification' => true,
                ],
                'benefits' => [
                    'Highest placement',
                    'VIP support',
                    'All premium features',
                    'Lowest commission',
                    'Exclusive badge',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Version Update Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic version checking and updates
    |
    */

    'version' => [
        'enabled' => env('VERSION_CHECK_ENABLED', true),
        'github_repo' => env('GITHUB_REPO', 'xjanova/Thaiprompt-Affiliate'),
        'check_interval_hours' => env('VERSION_CHECK_INTERVAL', 24),
        'auto_check' => env('VERSION_AUTO_CHECK', true),
        'github_api_url' => 'https://api.github.com',
        'github_token' => env('GITHUB_TOKEN', null), // Optional for private repos
        'notification_channels' => ['mail', 'database'],
        'critical_update_force' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | MLM Tree Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MLM tree visualization
    |
    */

    'mlm_tree' => [
        'enabled' => true,
        'max_depth' => env('MLM_TREE_MAX_DEPTH', 5),
        'node_width' => 120,
        'node_height' => 80,
        'vertical_spacing' => 150,
        'horizontal_spacing' => 50,
        'animation_duration' => 750, // milliseconds
        'lazy_load' => true,
        'cache_ttl' => 300, // seconds

        'sales_tracking' => [
            'minimum_sales' => 1000, // Monthly minimum to maintain status
            'grace_period_days' => 7,
            'warning_threshold' => 0.8, // Warn at 80% of minimum
        ],

        'colors' => [
            'verified' => '#10b981',
            'not_maintaining' => '#ef4444',
            'normal' => '#cbd5e0',
            'inactive' => '#9ca3af',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile Image Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for user profile images
    |
    */

    'profile_images' => [
        'default_avatar' => '/images/default-avatar.png',
        'storage_disk' => 'public',
        'upload_path' => 'avatars',
        'max_size' => 2048, // KB
        'allowed_formats' => ['jpg', 'jpeg', 'png'],
        'resize' => [
            'enabled' => true,
            'width' => 200,
            'height' => 200,
            'quality' => 85,
        ],
        'line_sync' => [
            'enabled' => true,
            'auto_update' => true,
            'cache_ttl' => 86400, // 24 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable features globally
    |
    */

    'flags' => [
        'nfc_payments' => env('FEATURE_NFC_PAYMENTS', true),
        'shop_verification' => env('FEATURE_SHOP_VERIFICATION', true),
        'version_updates' => env('FEATURE_VERSION_UPDATES', true),
        'mlm_tree' => env('FEATURE_MLM_TREE', true),
        'line_integration' => env('FEATURE_LINE_INTEGRATION', true),
        'custom_avatars' => env('FEATURE_CUSTOM_AVATARS', true),
    ],

];
