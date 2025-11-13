<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TPIX Blockchain Configuration
    |--------------------------------------------------------------------------
    | Configuration for TPIX token blockchain integration with Food Passport
    | - NFT creation for Food Passports
    | - TPIX as gas fees
    | - Carbon Credit tokenization
    */

    // Blockchain Network
    'network' => [
        'name' => env('TPIX_NETWORK_NAME', 'TPIX Chain'),
        'chain_id' => env('TPIX_CHAIN_ID', 88888), // Custom chain ID
        'rpc_url' => env('TPIX_RPC_URL', 'https://rpc.tpix.network'),
        'explorer_url' => env('TPIX_EXPLORER_URL', 'https://explorer.tpix.network'),
        'symbol' => 'TPIX',
    ],

    // Smart Contracts
    'contracts' => [
        // Food Passport NFT Contract
        'food_passport_nft' => [
            'address' => env('TPIX_FOOD_PASSPORT_CONTRACT'),
            'abi_path' => resource_path('blockchain/abi/FoodPassportNFT.json'),
            'gas_limit' => 300000,
        ],

        // Carbon Credit Token Contract (ERC-20)
        'carbon_credit_token' => [
            'address' => env('TPIX_CARBON_CREDIT_CONTRACT'),
            'abi_path' => resource_path('blockchain/abi/CarbonCreditToken.json'),
            'gas_limit' => 200000,
        ],

        // Quality Certificate NFT Contract
        'quality_certificate_nft' => [
            'address' => env('TPIX_QUALITY_CERT_CONTRACT'),
            'abi_path' => resource_path('blockchain/abi/QualityCertificateNFT.json'),
            'gas_limit' => 250000,
        ],

        // Journey Record Contract (for traceability)
        'journey_record' => [
            'address' => env('TPIX_JOURNEY_CONTRACT'),
            'abi_path' => resource_path('blockchain/abi/JourneyRecord.json'),
            'gas_limit' => 150000,
        ],
    ],

    // Gas Fee Configuration (in TPIX)
    'gas' => [
        'price_in_tpix' => env('TPIX_GAS_PRICE', 0.0001), // TPIX per gas unit
        'default_limit' => 250000,

        // Different gas limits for different operations
        'limits' => [
            'create_passport_nft' => 300000,
            'add_journey_stage' => 150000,
            'issue_carbon_credit' => 200000,
            'trade_carbon_credit' => 180000,
            'issue_certificate' => 250000,
        ],
    ],

    // Wallet Configuration
    'wallet' => [
        // Platform wallet for gas fees and commission
        'platform_address' => env('TPIX_PLATFORM_WALLET'),
        'platform_private_key' => env('TPIX_PLATFORM_PRIVATE_KEY'),

        // Gas subsidy - platform pays gas for users
        'gas_subsidy_enabled' => env('TPIX_GAS_SUBSIDY', true),
        'subsidy_limit_per_user_daily' => 100, // Max 100 TPIX per user per day
    ],

    // NFT Metadata
    'nft' => [
        // IPFS gateway for NFT metadata
        'ipfs_gateway' => env('TPIX_IPFS_GATEWAY', 'https://ipfs.tpix.network'),
        'ipfs_api_key' => env('TPIX_IPFS_API_KEY'),

        // Metadata storage
        'metadata_base_uri' => env('TPIX_METADATA_URI', 'ipfs://'),

        // NFT attributes for Food Passport
        'food_passport_attributes' => [
            'passport_id',
            'product_type',
            'farmer_name',
            'carbon_score',
            'quality_score',
            'certification_status',
            'blockchain_verified',
        ],
    ],

    // Carbon Credit Token Configuration
    'carbon_credit' => [
        'symbol' => 'TPCC', // Thai Prompt Carbon Credit
        'decimals' => 18,
        'initial_supply' => 0, // Minted on demand

        // 1 TPCC = 1 ton CO2 equivalent
        'credit_to_co2_ratio' => 1.0,

        // Trading on TPIX DEX
        'dex_enabled' => true,
        'liquidity_pool_address' => env('TPIX_CARBON_POOL'),
    ],

    // Integration Features
    'features' => [
        'auto_mint_nft_on_product_create' => true,
        'auto_record_journey_on_blockchain' => true,
        'auto_issue_carbon_credit_token' => true,
        'enable_carbon_credit_trading' => true,
        'enable_quality_certificate_nft' => true,
        'enable_gas_subsidy' => true,
    ],

    // Event Listening
    'events' => [
        'listen_to_blockchain_events' => true,
        'event_polling_interval' => 15, // seconds

        // Events to listen for
        'tracked_events' => [
            'FoodPassportMinted',
            'JourneyStageAdded',
            'CarbonCreditIssued',
            'CarbonCreditTraded',
            'QualityCertificateIssued',
        ],
    ],

    // Cache Configuration
    'cache' => [
        'enabled' => true,
        'ttl' => 300, // 5 minutes
        'prefix' => 'tpix_blockchain:',
    ],

    // Development/Testing
    'testnet' => [
        'enabled' => env('TPIX_TESTNET', false),
        'faucet_url' => 'https://faucet.tpix.network',
        'test_accounts' => [
            'farmer' => env('TPIX_TEST_FARMER_WALLET'),
            'processor' => env('TPIX_TEST_PROCESSOR_WALLET'),
            'retailer' => env('TPIX_TEST_RETAILER_WALLET'),
        ],
    ],
];
