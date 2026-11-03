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
    | Juntra (จันทรา.online) server-to-server — /api/v1/juntra/server/*
    |--------------------------------------------------------------------------
    |
    | 🚦 (2026-11-03 FB web-only) ใช้โดย App\Support\JuntraServerRateLimits (ต่อนาที)
    | ทุกคำขอมาจาก IP เดียว (เซิร์ฟเวอร์เว็บ) — แชทจึงนับต่อลูกค้า (user_ref) + เพดานรวม
    | chat_total ต้อง ≥ peak ข้อความ FB/นาที × 1.5 (fortune:fb-web-only-preflight ตรวจให้)
    | total = ด่านรวมทั้งกลุ่ม — ระบบใช้ค่าไม่ต่ำกว่าผลรวมของถังย่อยเสมอ
    |
    */
    'juntra_server' => [
        'total' => env('RATE_LIMIT_JUNTRA_SERVER_TOTAL', 2400),
        'chat_per_user' => env('RATE_LIMIT_JUNTRA_CHAT_PER_USER', 60),
        'chat_total' => env('RATE_LIMIT_JUNTRA_CHAT_TOTAL', 1200),
        'pay' => env('RATE_LIMIT_JUNTRA_PAY', 120),
        'read' => env('RATE_LIMIT_JUNTRA_READ', 60),
        'affiliate' => env('RATE_LIMIT_JUNTRA_AFFILIATE', 300),
        'voice' => env('RATE_LIMIT_JUNTRA_VOICE', 120),
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
