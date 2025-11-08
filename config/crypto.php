<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cryptocurrency Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the configuration options for the cryptocurrency
    | payment gateway system including wallet management, trading, and
    | blockchain integration settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Auto-Approval Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic approval of withdrawal requests based on risk score
    | and amount limits.
    |
    */

    'auto_approve_enabled' => env('CRYPTO_AUTO_APPROVE_ENABLED', true),
    'max_auto_approve_amount' => env('CRYPTO_MAX_AUTO_APPROVE_AMOUNT', 100000), // THB
    'max_risk_score_for_auto_approve' => env('CRYPTO_MAX_RISK_SCORE', 70),

    /*
    |--------------------------------------------------------------------------
    | Blockchain Network RPC URLs
    |--------------------------------------------------------------------------
    |
    | Configure RPC endpoints for different blockchain networks.
    |
    */

    'networks' => [
        'ethereum' => [
            'rpc_url' => env('ETHEREUM_RPC_URL', 'https://ethereum.publicnode.com'),
            'chain_id' => 1,
            'explorer' => 'https://etherscan.io',
            'required_confirmations' => env('ETHEREUM_CONFIRMATIONS', 12),
        ],
        'bsc' => [
            'rpc_url' => env('BSC_RPC_URL', 'https://bsc-dataseed.binance.org'),
            'chain_id' => 56,
            'explorer' => 'https://bscscan.com',
            'required_confirmations' => env('BSC_CONFIRMATIONS', 15),
        ],
        'polygon' => [
            'rpc_url' => env('POLYGON_RPC_URL', 'https://polygon-rpc.com'),
            'chain_id' => 137,
            'explorer' => 'https://polygonscan.com',
            'required_confirmations' => env('POLYGON_CONFIRMATIONS', 128),
        ],
        'bitcoin' => [
            'rpc_url' => env('BITCOIN_RPC_URL', ''),
            'chain_id' => null,
            'explorer' => 'https://blockchain.info',
            'required_confirmations' => env('BITCOIN_CONFIRMATIONS', 6),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deposit Detection Settings
    |--------------------------------------------------------------------------
    |
    | Configure how often the system scans for new deposits.
    |
    */

    'deposit_scan_interval' => env('CRYPTO_DEPOSIT_SCAN_INTERVAL', 30), // seconds
    'deposit_scan_block_range' => env('CRYPTO_DEPOSIT_SCAN_BLOCK_RANGE', 1000),
    'deposit_min_confirmations' => env('CRYPTO_DEPOSIT_MIN_CONFIRMATIONS', 1),

    /*
    |--------------------------------------------------------------------------
    | Withdrawal Processing Settings
    |--------------------------------------------------------------------------
    |
    | Configure withdrawal processing intervals and limits.
    |
    */

    'withdrawal_process_interval' => env('CRYPTO_WITHDRAWAL_PROCESS_INTERVAL', 60), // seconds
    'withdrawal_batch_size' => env('CRYPTO_WITHDRAWAL_BATCH_SIZE', 50),
    'withdrawal_max_retries' => env('CRYPTO_WITHDRAWAL_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Transaction Fee Settings
    |--------------------------------------------------------------------------
    |
    | Configure platform fees for various transaction types.
    |
    */

    'fees' => [
        'deposit_fee_percent' => env('CRYPTO_DEPOSIT_FEE_PERCENT', 0), // %
        'withdrawal_fee_percent' => env('CRYPTO_WITHDRAWAL_FEE_PERCENT', 0.5), // %
        'exchange_fee_percent' => env('CRYPTO_EXCHANGE_FEE_PERCENT', 1.0), // %
        'minimum_network_fee' => env('CRYPTO_MIN_NETWORK_FEE', 0.001), // in native token
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Limits
    |--------------------------------------------------------------------------
    |
    | Configure limits for wallet creation and balances.
    |
    */

    'wallet' => [
        'max_wallets_per_user' => env('CRYPTO_MAX_WALLETS_PER_USER', 5),
        'custodial_wallet_enabled' => env('CRYPTO_CUSTODIAL_ENABLED', true),
        'external_wallet_enabled' => env('CRYPTO_EXTERNAL_ENABLED', true),
        'pin_length' => 6,
        'pin_max_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Trading Settings
    |--------------------------------------------------------------------------
    |
    | Configure trading and exchange parameters.
    |
    */

    'trading' => [
        'min_trade_amount_thb' => env('CRYPTO_MIN_TRADE_AMOUNT', 100), // THB
        'max_trade_amount_thb' => env('CRYPTO_MAX_TRADE_AMOUNT', 10000000), // THB
        'price_update_interval' => env('CRYPTO_PRICE_UPDATE_INTERVAL', 60), // seconds
        'slippage_tolerance' => env('CRYPTO_SLIPPAGE_TOLERANCE', 0.5), // %
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration options.
    |
    */

    'security' => [
        'require_kyc_for_withdrawal' => env('CRYPTO_REQUIRE_KYC', true),
        'require_2fa_for_withdrawal' => env('CRYPTO_REQUIRE_2FA', false),
        'max_daily_withdrawal_thb' => env('CRYPTO_MAX_DAILY_WITHDRAWAL', 1000000), // THB
        'suspicious_activity_threshold' => 3, // failed attempts
        'nonce_expiry_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Price Data Providers
    |--------------------------------------------------------------------------
    |
    | Configure cryptocurrency price data sources.
    |
    */

    'price_providers' => [
        'default' => env('CRYPTO_PRICE_PROVIDER', 'coingecko'),
        'coingecko' => [
            'api_url' => 'https://api.coingecko.com/api/v3',
            'api_key' => env('COINGECKO_API_KEY', ''),
        ],
        'binance' => [
            'api_url' => 'https://api.binance.com/api/v3',
            'api_key' => env('BINANCE_API_KEY', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching for blockchain data and prices.
    |
    */

    'cache' => [
        'price_ttl' => env('CRYPTO_PRICE_CACHE_TTL', 60), // seconds
        'balance_ttl' => env('CRYPTO_BALANCE_CACHE_TTL', 30), // seconds
        'block_number_ttl' => env('CRYPTO_BLOCK_CACHE_TTL', 10), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notification preferences for crypto events.
    |
    */

    'notifications' => [
        'deposit_confirmed' => true,
        'withdrawal_processed' => true,
        'withdrawal_rejected' => true,
        'large_transaction_alert' => env('CRYPTO_LARGE_TX_ALERT', 100000), // THB
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
        'trading_enabled' => env('CRYPTO_TRADING_ENABLED', true),
        'staking_enabled' => env('CRYPTO_STAKING_ENABLED', false),
        'lending_enabled' => env('CRYPTO_LENDING_ENABLED', false),
        'nft_enabled' => env('CRYPTO_NFT_ENABLED', false),
    ],

];
