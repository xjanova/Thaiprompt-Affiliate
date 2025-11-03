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

];
