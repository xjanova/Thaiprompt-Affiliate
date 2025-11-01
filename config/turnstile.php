<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Cloudflare Turnstile integration.
    | You can enable/disable Turnstile for specific endpoints.
    |
    */

    'enabled' => env('CLOUDFLARE_TURNSTILE_ENABLED', true),

    'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY', ''),
    'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Bypass Admin
    |--------------------------------------------------------------------------
    |
    | When enabled, admin users will bypass all Turnstile checks.
    | Set to false to require Turnstile verification even for admins.
    |
    */
    'bypass_admin' => env('CLOUDFLARE_TURNSTILE_BYPASS_ADMIN', true),

    /*
    |--------------------------------------------------------------------------
    | Turnstile Points Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable Turnstile verification for specific endpoints.
    | These settings can be managed from the admin panel.
    |
    */
    'points' => [
        'login' => env('CLOUDFLARE_TURNSTILE_LOGIN', true),
        'register' => env('CLOUDFLARE_TURNSTILE_REGISTER', true),
        'password_change' => env('CLOUDFLARE_TURNSTILE_PASSWORD_CHANGE', true),
        'profile_update' => env('CLOUDFLARE_TURNSTILE_PROFILE_UPDATE', true),
        'withdrawal_request' => env('CLOUDFLARE_TURNSTILE_WITHDRAWAL', true),
        'affiliate_application' => env('CLOUDFLARE_TURNSTILE_AFFILIATE_APP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Turnstile Theme
    |--------------------------------------------------------------------------
    |
    | Available themes: light, dark, auto
    |
    */
    'theme' => env('CLOUDFLARE_TURNSTILE_THEME', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Turnstile Size
    |--------------------------------------------------------------------------
    |
    | Available sizes: normal, compact
    |
    */
    'size' => env('CLOUDFLARE_TURNSTILE_SIZE', 'normal'),

];
