<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Cloudflare Turnstile, Google APIs, etc.
    |
    */

    'cloudflare' => [
        // Turnstile (CAPTCHA)
        'turnstile' => [
            'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY', ''),
            'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY', ''),
            'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ],
        // Cloudflare API (Cache Purge, DNS, Security, etc.)
        'zone_id' => env('CLOUDFLARE_ZONE_ID', 'd552b4a77bf4783bf6cbfd6a07d3f349'),
        'api_token' => env('CLOUDFLARE_API_TOKEN', '3fc13fcba9b6add1ee59f2504f092bddec540'),
    ],

    'google' => [
        'api_key' => env('GOOGLE_API_KEY', ''),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/google-credentials.json')),
        'translate' => [
            'enabled' => env('GOOGLE_TRANSLATE_ENABLED', false),
            'api_key' => env('GOOGLE_TRANSLATE_API_KEY', ''),
            'project_id' => env('GOOGLE_TRANSLATE_PROJECT_ID', ''),
            'credentials' => env('GOOGLE_TRANSLATE_CREDENTIALS', storage_path('app/google-credentials.json')),
        ],
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | LINE Configuration
    |--------------------------------------------------------------------------
    */

    'line' => [
        'channel_id' => env('LINE_CHANNEL_ID'),
        'channel_secret' => env('LINE_CHANNEL_SECRET'),
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'fresh_market_add_friend_url' => env('LINE_FRESH_MARKET_ADD_FRIEND_URL'),
        'thaiprompt_add_friend_url' => env('LINE_THAIPROMPT_ADD_FRIEND_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    */

    'stripe' => [
        'api_key' => env('STRIPE_API_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
    ],

    'omise' => [
        'public_key' => env('OMISE_PUBLIC_KEY'),
        'secret_key' => env('OMISE_SECRET_KEY'),
    ],

    'promptpay' => [
        'merchant_id' => env('PROMPTPAY_MERCHANT_ID'),
        'webhook_secret' => env('PROMPTPAY_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM) - สำหรับ SMS Checker Push Notifications
    |--------------------------------------------------------------------------
    */

    'firebase' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'project_id' => env('FCM_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),
    ],

    'paysolutions' => [
        'merchant_id' => env('PAYSOLUTIONS_MERCHANT_ID'),
        'api_key' => env('PAYSOLUTIONS_API_KEY'),
        'secret_key' => env('PAYSOLUTIONS_SECRET_KEY'),
        'webhook_secret' => env('PAYSOLUTIONS_WEBHOOK_SECRET'),
        'api_url' => env('PAYSOLUTIONS_API_URL', 'https://api.paysolutions.asia'),
        'sandbox_url' => env('PAYSOLUTIONS_SANDBOX_URL', 'https://sandbox-api.paysolutions.asia'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking / Shipping Provider API Keys
    |--------------------------------------------------------------------------
    |
    | API keys สำหรับเชื่อมต่อระบบติดตามพัสดุกับขนส่งชั้นนำของไทย
    | ตั้งค่าใน .env เมื่อต้องการเปิดใช้งาน realtime tracking
    |
    */

    /*
    |--------------------------------------------------------------------------
    | YouTube API
    |--------------------------------------------------------------------------
    */

    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Service API Keys
    |--------------------------------------------------------------------------
    */

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'api_endpoint' => env('DEEPSEEK_API_ENDPOINT', 'https://api.deepseek.com/v1'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security / Threat Intelligence API Keys
    |--------------------------------------------------------------------------
    */

    'proxycheck' => [
        'api_key' => env('PROXYCHECK_API_KEY', ''),
    ],

    'abuseipdb' => [
        'api_key' => env('ABUSEIPDB_API_KEY', ''),
    ],

    'ipqualityscore' => [
        'api_key' => env('IPQUALITYSCORE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Huggingface
    |--------------------------------------------------------------------------
    */

    'google_cloud' => [
        'translate_api_key' => env('GOOGLE_CLOUD_TRANSLATE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking / Shipping Provider API Keys
    |--------------------------------------------------------------------------
    |
    | API keys สำหรับเชื่อมต่อระบบติดตามพัสดุกับขนส่งชั้นนำของไทย
    | ตั้งค่าใน .env เมื่อต้องการเปิดใช้งาน realtime tracking
    |
    */

    'tracking' => [
        'thaipost_token' => env('TRACKING_THAIPOST_TOKEN', ''),
        'kerry_api_key' => env('TRACKING_KERRY_API_KEY', ''),
        'flash_api_key' => env('TRACKING_FLASH_API_KEY', ''),
        'jt_api_key' => env('TRACKING_JT_API_KEY', ''),
        'ninjavan_key' => env('TRACKING_NINJAVAN_KEY', ''),
        'scg_api_key' => env('TRACKING_SCG_API_KEY', ''),
        'best_api_key' => env('TRACKING_BEST_API_KEY', ''),
        'dhl_key' => env('TRACKING_DHL_KEY', ''),
    ],

];
