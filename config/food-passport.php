<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Food Passport System Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('FOOD_PASSPORT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | QR Code Settings
    |--------------------------------------------------------------------------
    */

    'qr' => [
        'domain' => env('FOOD_PASSPORT_QR_DOMAIN', env('APP_URL')),
        'size' => 512,
        'margin' => 2,
        'format' => 'png',
        'error_correction' => 'H', // L, M, Q, H
    ],

    /*
    |--------------------------------------------------------------------------
    | Blockchain Settings
    |--------------------------------------------------------------------------
    */

    'blockchain' => [
        'enabled' => env('BLOCKCHAIN_ENABLED', false),
        'network' => env('BLOCKCHAIN_NETWORK', 'bsc-testnet'),

        'networks' => [
            'ethereum' => [
                'rpc_url' => env('ETHEREUM_RPC_URL', 'https://mainnet.infura.io/v3/'),
                'chain_id' => 1,
                'explorer' => 'https://etherscan.io',
                'contract_address' => env('FOOD_PASSPORT_CONTRACT_ETH'),
            ],
            'bsc' => [
                'rpc_url' => env('BSC_RPC_URL', 'https://bsc-dataseed.binance.org/'),
                'chain_id' => 56,
                'explorer' => 'https://bscscan.com',
                'contract_address' => env('FOOD_PASSPORT_CONTRACT_BSC'),
            ],
            'bsc-testnet' => [
                'rpc_url' => env('BSC_TESTNET_RPC_URL', 'https://data-seed-prebsc-1-s1.binance.org:8545/'),
                'chain_id' => 97,
                'explorer' => 'https://testnet.bscscan.com',
                'contract_address' => env('FOOD_PASSPORT_CONTRACT_BSC_TESTNET'),
            ],
            'polygon' => [
                'rpc_url' => env('POLYGON_RPC_URL', 'https://polygon-rpc.com/'),
                'chain_id' => 137,
                'explorer' => 'https://polygonscan.com',
                'contract_address' => env('FOOD_PASSPORT_CONTRACT_POLYGON'),
            ],
        ],

        'gas_limit' => [
            'register_product' => 200000,
            'add_journey' => 150000,
            'add_quality_check' => 150000,
            'record_carbon' => 150000,
        ],

        'confirmation_blocks' => 3,
        'retry_attempts' => 3,
        'retry_delay' => 5, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | IPFS Settings
    |--------------------------------------------------------------------------
    */

    'ipfs' => [
        'enabled' => env('IPFS_ENABLED', false),
        'host' => env('IPFS_HOST', '127.0.0.1'),
        'port' => env('IPFS_PORT', 5001),
        'protocol' => env('IPFS_PROTOCOL', 'http'),
        'gateway' => env('IPFS_GATEWAY', 'https://ipfs.io/ipfs/'),

        // Alternative: Use Pinata or Infura IPFS
        'pinata' => [
            'api_key' => env('PINATA_API_KEY'),
            'secret_key' => env('PINATA_SECRET_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Carbon Credit Settings
    |--------------------------------------------------------------------------
    */

    'carbon' => [
        'enabled' => env('CARBON_CREDIT_ENABLED', true),
        'marketplace_enabled' => env('CARBON_MARKETPLACE_ENABLED', true),

        // Default value per ton CO2 in THB
        'credit_value_per_ton' => 250,

        // Baseline emissions (kg CO2 per ton of product)
        'baselines' => [
            'vegetables' => 30.0,
            'fruits' => 25.0,
            'rice' => 40.0,
            'meat' => 150.0,
            'seafood' => 35.0,
            'dairy' => 60.0,
        ],

        // Emission factors
        'emission_factors' => [
            'transport' => [
                'diesel_truck_light' => 0.25,
                'diesel_truck_medium' => 0.68,
                'diesel_truck_heavy' => 1.2,
                'electric_vehicle' => 0.15,
                'ship' => 0.01,
                'air_freight' => 1.2,
            ],
            'farming' => [
                'organic_vegetables' => 1.0,
                'conventional_vegetables' => 3.0,
                'organic_rice' => 2.5,
                'conventional_rice' => 4.0,
            ],
            'processing' => [
                'cold_storage' => 0.2,
                'packaging_plastic' => 6.0,
                'packaging_paper' => 2.5,
            ],
        ],

        // Tier system rewards
        'tiers' => [
            'Platinum' => [
                'threshold' => 500, // tons CO2
                'bonus_multiplier' => 1.20,
                'badge_color' => '#E5E4E2',
            ],
            'Gold' => [
                'threshold' => 100,
                'bonus_multiplier' => 1.15,
                'badge_color' => '#FFD700',
            ],
            'Silver' => [
                'threshold' => 50,
                'bonus_multiplier' => 1.10,
                'badge_color' => '#C0C0C0',
            ],
            'Bronze' => [
                'threshold' => 10,
                'bonus_multiplier' => 1.05,
                'badge_color' => '#CD7F32',
            ],
            'Starter' => [
                'threshold' => 0,
                'bonus_multiplier' => 1.00,
                'badge_color' => '#808080',
            ],
        ],

        // Trading fee (percentage)
        'trading_fee' => 0.05, // 5%

        // Credit expiry (in months)
        'credit_expiry_months' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Control Settings
    |--------------------------------------------------------------------------
    */

    'quality' => [
        'pass_threshold' => 70, // Minimum score to pass
        'excellent_threshold' => 90, // Threshold for excellent rating

        'checkpoint_types' => [
            'visual' => 'Visual Inspection',
            'laboratory' => 'Laboratory Test',
            'sensor' => 'Sensor Monitoring',
            'certification' => 'Certification Audit',
            'audit' => 'Quality Audit',
        ],

        'standards' => [
            'ISO 22000' => 'Food Safety Management',
            'HACCP' => 'Hazard Analysis Critical Control Points',
            'GMP' => 'Good Manufacturing Practice',
            'GAP' => 'Good Agricultural Practice',
            'Organic Thailand' => 'Thai Organic Standard',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Certification Settings
    |--------------------------------------------------------------------------
    */

    'certification' => [
        'types' => [
            'Organic' => 'Organic Certification',
            'Fair Trade' => 'Fair Trade Certified',
            'GMP' => 'Good Manufacturing Practice',
            'HACCP' => 'HACCP Certified',
            'ISO 22000' => 'ISO 22000 Certified',
            'Carbon Neutral' => 'Carbon Neutral Certified',
        ],

        'expiry_warning_days' => 30, // Warn when cert expires in 30 days
        'auto_renewal_days' => 7, // Auto-renew if requested 7 days before expiry
    ],

    /*
    |--------------------------------------------------------------------------
    | Stakeholder Roles
    |--------------------------------------------------------------------------
    */

    'roles' => [
        'farmer' => 'Farmer / Producer',
        'processor' => 'Processor',
        'quality_inspector' => 'Quality Inspector',
        'distributor' => 'Distributor',
        'retailer' => 'Retailer',
        'certifier' => 'Certification Body',
        'consumer' => 'Consumer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Journey Stages
    |--------------------------------------------------------------------------
    */

    'stages' => [
        'farm' => [
            'name' => 'Farm / Origin',
            'icon' => '🌱',
            'color' => '#22c55e',
        ],
        'processing' => [
            'name' => 'Processing',
            'icon' => '🏭',
            'color' => '#3b82f6',
        ],
        'storage' => [
            'name' => 'Storage',
            'icon' => '📦',
            'color' => '#8b5cf6',
        ],
        'transport' => [
            'name' => 'Transportation',
            'icon' => '🚚',
            'color' => '#f59e0b',
        ],
        'distribution' => [
            'name' => 'Distribution Center',
            'icon' => '🏪',
            'color' => '#06b6d4',
        ],
        'retail' => [
            'name' => 'Retail',
            'icon' => '🏬',
            'color' => '#ec4899',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'line' => [
            'enabled' => env('LINE_NOTIFICATIONS_ENABLED', true),
            'journey_updates' => true,
            'quality_results' => true,
            'carbon_credits' => true,
            'certifications' => true,
        ],

        'email' => [
            'enabled' => env('EMAIL_NOTIFICATIONS_ENABLED', true),
            'expiry_warnings' => true,
            'quality_failures' => true,
        ],

        'push' => [
            'enabled' => env('PUSH_NOTIFICATIONS_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Reporting
    |--------------------------------------------------------------------------
    */

    'analytics' => [
        'track_consumer_scans' => true,
        'track_engagement' => true,
        'generate_reports' => true,

        'retention_days' => [
            'consumer_scans' => 365,
            'journey_history' => null, // Keep forever
            'quality_records' => null, // Keep forever
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => true,
        'ttl' => [
            'product' => 3600, // 1 hour
            'journey' => 1800, // 30 minutes
            'quality' => 3600, // 1 hour
            'carbon' => 3600, // 1 hour
            'dashboard' => 300, // 5 minutes
            'leaderboard' => 1800, // 30 minutes
        ],
    ],

    'queue' => [
        'blockchain_recording' => 'high',
        'notifications' => 'default',
        'analytics' => 'low',
    ],

];
