<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Checker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the SMS Payment Checker integration.
    | These values should be set in your .env file.
    |
    */

    // Maximum time difference allowed for request timestamps (in seconds)
    'timestamp_tolerance' => env('SMSCHECKER_TIMESTAMP_TOLERANCE', 300),

    // Default expiry time for unique amounts (in minutes)
    'unique_amount_expiry' => env('SMSCHECKER_AMOUNT_EXPIRY', 30),

    // Maximum number of pending unique amounts per base amount
    'max_pending_per_amount' => env('SMSCHECKER_MAX_PENDING', 99),

    // Rate limiting: max notifications per device per minute
    'rate_limit_per_minute' => env('SMSCHECKER_RATE_LIMIT', 30),

    // Supported banks
    'supported_banks' => [
        'KBANK' => 'Kasikorn Bank',
        'SCB' => 'Siam Commercial Bank',
        'KTB' => 'Krungthai Bank',
        'BBL' => 'Bangkok Bank',
        'GSB' => 'Government Savings Bank',
        'BAY' => 'Bank of Ayudhya',
        'TTB' => 'TMBThanachart Bank',
        'PROMPTPAY' => 'PromptPay',
    ],

    // Nonce expiry (in hours) - nonces older than this are cleaned up
    'nonce_expiry_hours' => env('SMSCHECKER_NONCE_EXPIRY', 24),

    // Auto-confirm matched payments
    'auto_confirm_matched' => env('SMSCHECKER_AUTO_CONFIRM', true),

    // Notification on match
    'notify_on_match' => env('SMSCHECKER_NOTIFY_ON_MATCH', true),

    // Log level: debug, info, warning
    'log_level' => env('SMSCHECKER_LOG_LEVEL', 'info'),
];
