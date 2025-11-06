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
        'turnstile' => [
            'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY', ''),
            'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY', ''),
            'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ],
    ],

    'google' => [
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/google-credentials.json')),
        'translate' => [
            'enabled' => env('GOOGLE_TRANSLATE_ENABLED', false),
            'api_key' => env('GOOGLE_TRANSLATE_API_KEY', ''),
            'project_id' => env('GOOGLE_TRANSLATE_PROJECT_ID', ''),
            'credentials' => env('GOOGLE_TRANSLATE_CREDENTIALS', storage_path('app/google-credentials.json')),
        ],
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

    'paysolutions' => [
        'merchant_id' => env('PAYSOLUTIONS_MERCHANT_ID'),
        'api_key' => env('PAYSOLUTIONS_API_KEY'),
        'secret_key' => env('PAYSOLUTIONS_SECRET_KEY'),
        'webhook_secret' => env('PAYSOLUTIONS_WEBHOOK_SECRET'),
        'api_url' => env('PAYSOLUTIONS_API_URL', 'https://api.paysolutions.asia'),
        'sandbox_url' => env('PAYSOLUTIONS_SANDBOX_URL', 'https://sandbox-api.paysolutions.asia'),
    ],

];
