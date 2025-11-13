<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TPIX Token System Configuration
    |--------------------------------------------------------------------------
    |
    | Complete configuration for TPIX native blockchain and token ecosystem.
    | This includes blockchain connection, token creation, DEX, staking,
    | and all system-wide settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Blockchain Network Settings
    |--------------------------------------------------------------------------
    |
    | TPIX native blockchain configuration using Polygon Edge framework.
    |
    */

    'blockchain' => [
        'rpc_url' => env('TPIX_RPC_URL', 'http://localhost:8545'),
        'ws_url' => env('TPIX_WS_URL', 'ws://localhost:8546'),
        'chain_id' => env('TPIX_CHAIN_ID', 7000),
        'network_name' => 'TPIX Blockchain',
        'currency_symbol' => 'TPIX',
        'currency_decimals' => 18,
        'block_time' => 2, // seconds
        'required_confirmations' => env('TPIX_CONFIRMATIONS', 5),

        // Explorer
        'explorer_url' => env('TPIX_EXPLORER_URL', 'http://localhost:4000'),
        'explorer_name' => 'TPIX Explorer',

        // Network status
        'is_testnet' => env('TPIX_IS_TESTNET', false),
        'network_version' => '1.0.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Native TPIX Coin Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the native TPIX cryptocurrency.
    |
    */

    'native_coin' => [
        'name' => 'TPIX',
        'symbol' => 'TPIX',
        'decimals' => 18,
        'total_supply' => '7000000000', // 7 billion TPIX (fixed supply)
        'is_mintable' => false, // Cannot mint more after genesis
        'initial_price_usd' => env('TPIX_INITIAL_PRICE_USD', '0.10'),

        // Genesis allocation (how 7 billion is distributed)
        'genesis_allocation' => [
            'team' => '1400000000',        // 20% - 1.4 billion
            'development' => '700000000',   // 10% - 700 million
            'liquidity' => '2100000000',    // 30% - 2.1 billion
            'staking_rewards' => '1400000000', // 20% - 1.4 billion
            'ecosystem' => '700000000',     // 10% - 700 million
            'public_sale' => '700000000',   // 10% - 700 million
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Creation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for user-created ERC20 tokens on TPIX blockchain.
    |
    */

    'token_creation' => [
        // Fees
        'creation_fee_tpix' => env('TPIX_TOKEN_CREATION_FEE', '100'), // 100 TPIX to create token
        'deployment_fee_tpix' => env('TPIX_TOKEN_DEPLOYMENT_FEE', '50'), // 50 TPIX to deploy
        'verification_fee_tpix' => env('TPIX_TOKEN_VERIFICATION_FEE', '200'), // 200 TPIX for verification badge

        // Limits
        'min_total_supply' => '1',
        'max_total_supply' => '1000000000000', // 1 trillion max
        'min_name_length' => 3,
        'max_name_length' => 100,
        'min_symbol_length' => 2,
        'max_symbol_length' => 20,

        // Rate limits
        'max_tokens_per_user' => env('TPIX_MAX_TOKENS_PER_USER', 10),
        'max_tokens_per_day' => env('TPIX_MAX_TOKENS_PER_DAY', 5),

        // Default token settings
        'default_decimals' => 18,
        'allow_custom_decimals' => true,
        'min_decimals' => 0,
        'max_decimals' => 18,

        // Features availability
        'features' => [
            'burnable' => true,
            'mintable' => true,
            'pausable' => true,
            'freezable' => true,
            'snapshot' => false,
            'permit' => true,
            'votes' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DEX (Decentralized Exchange) Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the AMM-based decentralized exchange.
    |
    */

    'dex' => [
        // Core AMM settings
        'factory_address' => env('TPIX_DEX_FACTORY_ADDRESS', '0x0000000000000000000000000000000000000000'),
        'router_address' => env('TPIX_DEX_ROUTER_ADDRESS', '0x0000000000000000000000000000000000000000'),
        'init_code_hash' => env('TPIX_DEX_INIT_CODE_HASH', ''),

        // Trading fees
        'swap_fee_percent' => env('TPIX_DEX_SWAP_FEE', '0.3'), // 0.3% fee
        'protocol_fee_percent' => env('TPIX_DEX_PROTOCOL_FEE', '0.05'), // 0.05% to protocol
        'lp_fee_percent' => '0.25', // Remaining 0.25% to LPs

        // Slippage settings
        'default_slippage_tolerance' => env('TPIX_DEX_DEFAULT_SLIPPAGE', '0.5'), // 0.5%
        'max_slippage_tolerance' => env('TPIX_DEX_MAX_SLIPPAGE', '50'), // 50%

        // Liquidity settings
        'min_liquidity' => '1000', // Minimum liquidity (sqrt(amount0 * amount1) - MINIMUM_LIQUIDITY)
        'minimum_liquidity_lock' => '1000', // First 1000 LP tokens burned

        // Price impact warnings
        'price_impact_warning_threshold' => env('TPIX_DEX_PRICE_IMPACT_WARNING', '5'), // 5%
        'price_impact_critical_threshold' => env('TPIX_DEX_PRICE_IMPACT_CRITICAL', '15'), // 15%

        // Pool creation
        'pool_creation_fee_tpix' => env('TPIX_DEX_POOL_CREATION_FEE', '10'), // 10 TPIX
        'allow_public_pool_creation' => env('TPIX_DEX_ALLOW_PUBLIC_POOLS', true),

        // Features
        'enable_flash_swaps' => env('TPIX_DEX_ENABLE_FLASH_SWAPS', false),
        'enable_limit_orders' => env('TPIX_DEX_ENABLE_LIMIT_ORDERS', false),
        'enable_farming' => env('TPIX_DEX_ENABLE_FARMING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Staking Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for token staking pools and rewards.
    |
    */

    'staking' => [
        // Pool settings
        'min_stake_amount' => env('TPIX_STAKING_MIN_AMOUNT', '1'),
        'max_pools_per_token' => env('TPIX_STAKING_MAX_POOLS', 5),
        'pool_creation_fee_tpix' => env('TPIX_STAKING_POOL_FEE', '50'),

        // Lock periods (in seconds)
        'available_lock_periods' => [
            'flexible' => 0,           // No lock
            '7_days' => 604800,        // 7 days
            '30_days' => 2592000,      // 30 days
            '90_days' => 7776000,      // 90 days
            '180_days' => 15552000,    // 180 days
            '365_days' => 31536000,    // 365 days
        ],

        // APY ranges
        'min_apy' => env('TPIX_STAKING_MIN_APY', '5'), // 5%
        'max_apy' => env('TPIX_STAKING_MAX_APY', '200'), // 200%

        // Reward distribution
        'reward_distribution_interval' => env('TPIX_STAKING_REWARD_INTERVAL', 3600), // Every hour
        'compound_enabled' => env('TPIX_STAKING_COMPOUND_ENABLED', true),
        'auto_compound_fee' => env('TPIX_STAKING_AUTO_COMPOUND_FEE', '0.1'), // 0.1%

        // Early withdrawal penalties
        'early_withdrawal_penalty' => env('TPIX_STAKING_EARLY_PENALTY', '10'), // 10%
        'penalty_goes_to' => 'remaining_stakers', // or 'burn' or 'protocol'
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral System Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for referral rewards and codes.
    |
    */

    'referral' => [
        // Rewards
        'referrer_reward_percent' => env('TPIX_REFERRAL_REFERRER_REWARD', '5'), // 5% to referrer
        'referee_reward_percent' => env('TPIX_REFERRAL_REFEREE_REWARD', '2'), // 2% to referee
        'max_reward_per_referral_tpix' => env('TPIX_REFERRAL_MAX_REWARD', '1000'), // Max 1000 TPIX

        // Code settings
        'code_length' => 8,
        'code_prefix' => 'TPIX',
        'code_expiry_days' => env('TPIX_REFERRAL_CODE_EXPIRY', 0), // 0 = never expires
        'max_uses_per_code' => env('TPIX_REFERRAL_MAX_USES', 0), // 0 = unlimited

        // Verification
        'require_email_verification' => env('TPIX_REFERRAL_REQUIRE_EMAIL', true),
        'require_sms_verification' => env('TPIX_REFERRAL_REQUIRE_SMS', false),
        'verification_code_expiry_minutes' => 15,

        // Limits
        'max_referrals_per_user' => env('TPIX_REFERRAL_MAX_PER_USER', 0), // 0 = unlimited
        'min_transaction_for_reward' => env('TPIX_REFERRAL_MIN_TX', '10'), // Min 10 TPIX transaction
    ],

    /*
    |--------------------------------------------------------------------------
    | CoinMarketCap Integration
    |--------------------------------------------------------------------------
    |
    | Settings for CoinMarketCap API integration.
    |
    */

    'coinmarketcap' => [
        'api_key' => env('COINMARKETCAP_API_KEY', ''),
        'api_url' => 'https://pro-api.coinmarketcap.com/v1',
        'sandbox_mode' => env('COINMARKETCAP_SANDBOX', false),
        'sandbox_url' => 'https://sandbox-api.coinmarketcap.com/v1',

        // Sync settings
        'auto_sync_enabled' => env('TPIX_CMC_AUTO_SYNC', true),
        'sync_interval_minutes' => env('TPIX_CMC_SYNC_INTERVAL', 5), // Every 5 minutes
        'max_tokens_per_sync' => 100,

        // Rate limiting
        'requests_per_day' => env('COINMARKETCAP_DAILY_LIMIT', 333), // Free tier: 10k/month ≈ 333/day
        'delay_between_requests_ms' => 300, // 300ms delay

        // Cache
        'cache_duration_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Coin Control Settings
    |--------------------------------------------------------------------------
    |
    | Admin controls for minting, burning, freezing tokens.
    |
    */

    'coin_control' => [
        // Permissions
        'require_admin_approval' => env('TPIX_COIN_CONTROL_REQUIRE_APPROVAL', true),
        'max_mint_per_day' => env('TPIX_COIN_CONTROL_MAX_MINT_DAILY', '1000000'), // 1M max
        'max_burn_per_day' => env('TPIX_COIN_CONTROL_MAX_BURN_DAILY', '1000000'),

        // Audit trail
        'log_all_actions' => true,
        'require_two_factor' => env('TPIX_COIN_CONTROL_REQUIRE_2FA', true),
        'require_reason' => true,

        // Cooling period
        'mint_cooldown_hours' => env('TPIX_COIN_CONTROL_MINT_COOLDOWN', 24), // 24 hours
        'burn_cooldown_hours' => env('TPIX_COIN_CONTROL_BURN_COOLDOWN', 24),

        // Emergency controls
        'allow_emergency_pause' => true,
        'emergency_pause_duration_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    |
    | Gas and transaction configuration.
    |
    */

    'transactions' => [
        // Gas settings
        'default_gas_limit' => env('TPIX_DEFAULT_GAS_LIMIT', 300000),
        'default_gas_price' => env('TPIX_DEFAULT_GAS_PRICE', '1000000000'), // 1 Gwei in TPIX
        'gas_price_multiplier' => env('TPIX_GAS_PRICE_MULTIPLIER', '1.1'), // 10% higher

        // Limits
        'max_gas_limit' => 10000000,
        'max_gas_price' => '100000000000', // 100 Gwei

        // Timeout
        'transaction_timeout_seconds' => env('TPIX_TX_TIMEOUT', 300), // 5 minutes
        'max_retries' => 3,
        'retry_delay_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security and fraud prevention settings.
    |
    */

    'security' => [
        // Wallet security
        'require_pin_for_transfers' => env('TPIX_REQUIRE_PIN', false),
        'require_2fa_for_large_amounts' => env('TPIX_REQUIRE_2FA', false),
        'large_amount_threshold' => env('TPIX_LARGE_AMOUNT', '10000'), // 10k TPIX

        // Rate limiting (per hour)
        'rate_limits' => [
            'token_creation' => 5,
            'token_transfer' => 20,
            'token_trade' => 50,
            'dex_swap' => 50,
            'liquidity_operation' => 50,
            'staking_operation' => 30,
        ],

        // Fraud prevention
        'enable_fraud_detection' => env('TPIX_FRAUD_DETECTION', true),
        'max_failed_transactions' => 5,
        'suspicious_activity_cooldown_hours' => 24,

        // Contract security
        'require_contract_verification' => env('TPIX_REQUIRE_CONTRACT_VERIFICATION', true),
        'blacklist_enabled' => true,
        'whitelist_mode' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Settings
    |--------------------------------------------------------------------------
    |
    | Cache configuration for performance optimization.
    |
    */

    'cache' => [
        'enabled' => env('TPIX_CACHE_ENABLED', true),
        'driver' => env('TPIX_CACHE_DRIVER', 'redis'),

        // TTL settings (in seconds)
        'ttl' => [
            'token_short' => 60,       // 1 minute
            'token_medium' => 300,     // 5 minutes
            'token_long' => 3600,      // 1 hour
            'price_data' => 60,        // 1 minute
            'pool_data' => 60,         // 1 minute
            'statistics' => 300,       // 5 minutes
            'user_portfolio' => 60,    // 1 minute
        ],

        // Cache warming
        'warm_cache_enabled' => true,
        'warm_cache_tokens_limit' => 100, // Top 100 tokens
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for notifications and alerts.
    |
    */

    'notifications' => [
        // Channels
        'channels' => ['database', 'mail'],
        'enable_sms' => env('TPIX_ENABLE_SMS_NOTIFICATIONS', false),
        'enable_push' => env('TPIX_ENABLE_PUSH_NOTIFICATIONS', false),

        // Events to notify
        'notify_on' => [
            'token_deployed' => true,
            'token_approved' => true,
            'token_rejected' => true,
            'token_transferred' => true,
            'token_minted' => true,
            'address_frozen' => true,
            'referral_reward' => true,
            'stake_unlocked' => true,
            'swap_completed' => true,
            'liquidity_added' => true,
        ],

        // Thresholds
        'notify_large_transfers' => env('TPIX_NOTIFY_LARGE_TRANSFERS', '10000'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Wallet Settings
    |--------------------------------------------------------------------------
    |
    | Admin wallet for system operations (fees collection, etc).
    |
    */

    'admin' => [
        'wallet_address' => env('TPIX_ADMIN_WALLET_ADDRESS', ''),
        'private_key' => env('TPIX_ADMIN_PRIVATE_KEY', ''),
        'fee_collection_address' => env('TPIX_FEE_COLLECTION_ADDRESS', ''),

        // Treasury
        'treasury_address' => env('TPIX_TREASURY_ADDRESS', ''),
        'treasury_allocation_percent' => 10, // 10% of fees go to treasury
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | REST API configuration.
    |
    */

    'api' => [
        'version' => 'v1',
        'prefix' => 'api/v1/tpix',

        // Rate limiting (uses security.rate_limits)
        'rate_limit_enabled' => true,

        // Pagination
        'default_per_page' => 20,
        'max_per_page' => 100,

        // Response
        'include_debug_info' => env('TPIX_API_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    |
    | User interface configuration.
    |
    */

    'ui' => [
        'theme' => 'modern', // modern, classic, dark
        'default_language' => 'th',
        'available_languages' => ['th', 'en'],

        // Display settings
        'tokens_per_page' => 20,
        'show_featured_tokens' => true,
        'show_trending_tokens' => true,
        'show_new_tokens' => true,

        // Charts
        'enable_price_charts' => true,
        'chart_default_timeframe' => '24h',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features.
    |
    */

    'features' => [
        'token_creation' => env('TPIX_FEATURE_TOKEN_CREATION', true),
        'dex_trading' => env('TPIX_FEATURE_DEX_TRADING', true),
        'staking' => env('TPIX_FEATURE_STAKING', true),
        'referrals' => env('TPIX_FEATURE_REFERRALS', true),
        'coinmarketcap_integration' => env('TPIX_FEATURE_CMC', true),
        'coin_control' => env('TPIX_FEATURE_COIN_CONTROL', true),
        'nft_support' => env('TPIX_FEATURE_NFT', false),
        'governance' => env('TPIX_FEATURE_GOVERNANCE', false),
        'bridge' => env('TPIX_FEATURE_BRIDGE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | Settings for system maintenance.
    |
    */

    'maintenance' => [
        'enabled' => env('TPIX_MAINTENANCE_MODE', false),
        'message' => 'TPIX Token System is currently under maintenance. We\'ll be back soon!',
        'allowed_ips' => [],
        'retry_after' => 3600, // 1 hour
    ],

];
