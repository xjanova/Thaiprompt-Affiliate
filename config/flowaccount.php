<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FlowAccount API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for FlowAccount API integration
    |
    */

    'use_sandbox' => env('FLOWACCOUNT_USE_SANDBOX', true),

    'sandbox_url' => env('FLOWACCOUNT_SANDBOX_URL', 'https://openapi.flowaccount.com/sandbox'),

    'production_url' => env('FLOWACCOUNT_PRODUCTION_URL', 'https://openapi.flowaccount.com/v3-alpha'),

    'client_id' => env('FLOWACCOUNT_CLIENT_ID'),

    'client_secret' => env('FLOWACCOUNT_CLIENT_SECRET'),

    'redirect_uri' => env('FLOWACCOUNT_REDIRECT_URI', env('APP_URL') . '/admin/accounting/flowaccount/callback'),

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'auto_sync' => env('FLOWACCOUNT_AUTO_SYNC', false),

    'sync_interval' => env('FLOWACCOUNT_SYNC_INTERVAL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    'features' => [
        'sync_invoices' => env('FLOWACCOUNT_SYNC_INVOICES', true),
        'sync_expenses' => env('FLOWACCOUNT_SYNC_EXPENSES', true),
        'sync_contacts' => env('FLOWACCOUNT_SYNC_CONTACTS', true),
        'sync_products' => env('FLOWACCOUNT_SYNC_PRODUCTS', true),
    ],
];
