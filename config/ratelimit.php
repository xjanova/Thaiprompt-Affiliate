<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for various endpoints to prevent abuse.
    |
    */

    'enabled' => env('RATE_LIMITING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Login Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum login attempts allowed within the decay time.
    |
    */
    'login' => [
        'max_attempts' => env('RATE_LIMIT_LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('RATE_LIMIT_LOGIN_DECAY_MINUTES', 15),
        'lockout_minutes' => env('RATE_LIMIT_LOGIN_LOCKOUT_MINUTES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for API endpoints (requests per minute).
    |
    */
    'api' => [
        'default' => env('RATE_LIMIT_API_DEFAULT', 60), // 60 requests per minute
        'authenticated' => env('RATE_LIMIT_API_AUTHENTICATED', 120), // 120 requests per minute
        'guest' => env('RATE_LIMIT_API_GUEST', 30), // 30 requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Submission Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Prevent form spam and abuse.
    |
    */
    'forms' => [
        'withdrawal' => [
            'max_attempts' => env('RATE_LIMIT_WITHDRAWAL_MAX_ATTEMPTS', 3),
            'decay_minutes' => env('RATE_LIMIT_WITHDRAWAL_DECAY_MINUTES', 60),
        ],
        'password_change' => [
            'max_attempts' => env('RATE_LIMIT_PASSWORD_CHANGE_MAX_ATTEMPTS', 3),
            'decay_minutes' => env('RATE_LIMIT_PASSWORD_CHANGE_DECAY_MINUTES', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Login Tracking
    |--------------------------------------------------------------------------
    |
    | Track failed login attempts for security monitoring.
    |
    */
    'track_failed_logins' => env('TRACK_FAILED_LOGINS', true),
    'failed_login_retention_days' => env('FAILED_LOGIN_RETENTION_DAYS', 30),

];
