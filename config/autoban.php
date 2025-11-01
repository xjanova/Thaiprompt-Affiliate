<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto-Ban Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic IP banning settings for various security events.
    |
    */

    // Enable/Disable Auto-Ban
    'enabled' => env('AUTO_BAN_ENABLED', true),

    // Failed Login Auto-Ban
    'failed_login' => [
        'enabled' => env('AUTO_BAN_FAILED_LOGIN_ENABLED', true),
        'threshold' => env('AUTO_BAN_FAILED_LOGIN_THRESHOLD', 10), // จำนวนครั้งที่ล้มเหลว
        'time_window' => env('AUTO_BAN_FAILED_LOGIN_TIME_WINDOW', 30), // นาที
        'ban_duration' => env('AUTO_BAN_FAILED_LOGIN_BAN_DURATION', 1440), // นาที (24 ชั่วโมง)
    ],

    // Turnstile Failure Auto-Ban
    'turnstile_failure' => [
        'enabled' => env('AUTO_BAN_TURNSTILE_ENABLED', true),
        'threshold' => env('AUTO_BAN_TURNSTILE_THRESHOLD', 15),
        'time_window' => env('AUTO_BAN_TURNSTILE_TIME_WINDOW', 60),
        'ban_duration' => env('AUTO_BAN_TURNSTILE_BAN_DURATION', 720), // 12 ชั่วโมง
    ],

    // Rate Limit Exceeded Auto-Ban
    'rate_limit' => [
        'enabled' => env('AUTO_BAN_RATE_LIMIT_ENABLED', true),
        'threshold' => env('AUTO_BAN_RATE_LIMIT_THRESHOLD', 20),
        'time_window' => env('AUTO_BAN_RATE_LIMIT_TIME_WINDOW', 60),
        'ban_duration' => env('AUTO_BAN_RATE_LIMIT_BAN_DURATION', 480), // 8 ชั่วโมง
    ],

    // General Suspicious Activity Auto-Ban
    'suspicious_activity' => [
        'enabled' => env('AUTO_BAN_SUSPICIOUS_ENABLED', true),
        'threshold' => env('AUTO_BAN_SUSPICIOUS_THRESHOLD', 25),
        'time_window' => env('AUTO_BAN_SUSPICIOUS_TIME_WINDOW', 120),
        'ban_duration' => env('AUTO_BAN_SUSPICIOUS_BAN_DURATION', 2880), // 48 ชั่วโมง
    ],

    // Notification Settings
    'notifications' => [
        'enabled' => env('AUTO_BAN_NOTIFICATIONS_ENABLED', true),
        'email' => [
            'enabled' => env('AUTO_BAN_EMAIL_ENABLED', true),
            'recipients' => env('AUTO_BAN_EMAIL_RECIPIENTS', 'admin@example.com'),
        ],
        'slack' => [
            'enabled' => env('AUTO_BAN_SLACK_ENABLED', false),
            'webhook_url' => env('AUTO_BAN_SLACK_WEBHOOK'),
        ],
    ],

    // Whitelist IPs (never auto-ban)
    'whitelist' => [
        '127.0.0.1',
        '::1',
    ],
];
