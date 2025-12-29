<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FlowAccount API Configuration
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับเชื่อมต่อกับ FlowAccount Open API
    | https://developers.flowaccount.com/api-reference
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Environment Settings - การตั้งค่า Environment
    |--------------------------------------------------------------------------
    */

    // ใช้ Sandbox หรือ Production
    'use_sandbox' => env('FLOWACCOUNT_USE_SANDBOX', true),

    // Sandbox URL
    'sandbox_url' => env('FLOWACCOUNT_SANDBOX_URL', 'https://openapi.flowaccount.com/sandbox'),

    // Production URL
    'production_url' => env('FLOWACCOUNT_PRODUCTION_URL', 'https://openapi.flowaccount.com/v3-alpha'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Settings - การตั้งค่า OAuth
    |--------------------------------------------------------------------------
    */

    // Client ID (ได้จากการลงทะเบียน App กับ FlowAccount)
    'client_id' => env('FLOWACCOUNT_CLIENT_ID'),

    // Client Secret
    'client_secret' => env('FLOWACCOUNT_CLIENT_SECRET'),

    // Redirect URI สำหรับ OAuth callback
    'redirect_uri' => env('FLOWACCOUNT_REDIRECT_URI', env('APP_URL').'/admin/accounting/flowaccount/callback'),

    // OAuth Scope
    'oauth_scope' => env('FLOWACCOUNT_OAUTH_SCOPE', 'read write'),

    /*
    |--------------------------------------------------------------------------
    | API Settings - การตั้งค่า API
    |--------------------------------------------------------------------------
    */

    // Culture/Language สำหรับ API
    'culture' => env('FLOWACCOUNT_CULTURE', 'th-TH'),

    // HTTP Timeout (seconds)
    'timeout' => env('FLOWACCOUNT_TIMEOUT', 30),

    // จำนวนครั้งที่จะ retry เมื่อ request ล้มเหลว
    'retry_attempts' => env('FLOWACCOUNT_RETRY_ATTEMPTS', 3),

    // Page size สำหรับ pagination
    'page_size' => env('FLOWACCOUNT_PAGE_SIZE', 50),

    /*
    |--------------------------------------------------------------------------
    | Auto Sync Settings - การตั้งค่า Auto Sync
    |--------------------------------------------------------------------------
    */

    // เปิดใช้งาน Auto Sync หรือไม่
    'auto_sync' => env('FLOWACCOUNT_AUTO_SYNC', false),

    // ความถี่ในการ sync อัตโนมัติ (วินาที) - default 1 ชั่วโมง
    'sync_interval' => env('FLOWACCOUNT_SYNC_INTERVAL', 3600),

    // เวลาที่จะรัน auto sync (24-hour format) - เฉพาะ daily sync
    'sync_time' => env('FLOWACCOUNT_SYNC_TIME', '00:00'),

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles - เปิด/ปิดฟีเจอร์
    |--------------------------------------------------------------------------
    */

    'features' => [
        // ซิงค์ใบแจ้งหนี้
        'sync_invoices' => env('FLOWACCOUNT_SYNC_INVOICES', true),

        // ซิงค์ค่าใช้จ่าย
        'sync_expenses' => env('FLOWACCOUNT_SYNC_EXPENSES', true),

        // ซิงค์ผู้ติดต่อ
        'sync_contacts' => env('FLOWACCOUNT_SYNC_CONTACTS', true),

        // ซิงค์สินค้า
        'sync_products' => env('FLOWACCOUNT_SYNC_PRODUCTS', true),

        // ซิงค์ใบเสนอราคา
        'sync_quotations' => env('FLOWACCOUNT_SYNC_QUOTATIONS', true),

        // ซิงค์ใบวางบิล
        'sync_billing_notes' => env('FLOWACCOUNT_SYNC_BILLING_NOTES', true),

        // ซิงค์ใบเสร็จรับเงิน
        'sync_receipts' => env('FLOWACCOUNT_SYNC_RECEIPTS', true),

        // ซิงค์ใบกำกับภาษี
        'sync_tax_invoices' => env('FLOWACCOUNT_SYNC_TAX_INVOICES', true),

        // ซิงค์ใบกำกับภาษีเงินสด
        'sync_cash_invoices' => env('FLOWACCOUNT_SYNC_CASH_INVOICES', true),

        // ซิงค์ใบแจ้งหนี้ลูกหนี้
        'sync_receivable_invoices' => env('FLOWACCOUNT_SYNC_RECEIVABLE_INVOICES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Types - ประเภทเอกสาร
    |--------------------------------------------------------------------------
    */

    'document_types' => [
        'tax_invoice' => [
            'name' => 'ใบกำกับภาษี',
            'endpoint' => 'tax-invoices',
            'enabled' => true,
        ],
        'cash_invoice' => [
            'name' => 'ใบกำกับภาษี/ใบเสร็จรับเงิน (เงินสด)',
            'endpoint' => 'cash-invoices',
            'enabled' => true,
        ],
        'receivable_invoice' => [
            'name' => 'ใบแจ้งหนี้',
            'endpoint' => 'receivable-invoices',
            'enabled' => true,
        ],
        'quotation' => [
            'name' => 'ใบเสนอราคา',
            'endpoint' => 'quotations',
            'enabled' => true,
        ],
        'billing_note' => [
            'name' => 'ใบวางบิล',
            'endpoint' => 'billing-notes',
            'enabled' => true,
        ],
        'receipt' => [
            'name' => 'ใบเสร็จรับเงิน',
            'endpoint' => 'receipts',
            'enabled' => true,
        ],
        'expense' => [
            'name' => 'ค่าใช้จ่าย',
            'endpoint' => 'expenses',
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | VAT Types - ประเภท VAT
    |--------------------------------------------------------------------------
    */

    'vat_types' => [
        0 => ['name' => 'ไม่คิด VAT', 'rate' => 0],
        1 => ['name' => 'คิด VAT แยก 7%', 'rate' => 7],
        7 => ['name' => 'คิด VAT รวม 7%', 'rate' => 7],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings - การตั้งค่า Log
    |--------------------------------------------------------------------------
    */

    'logging' => [
        // เก็บ log การ sync
        'enabled' => env('FLOWACCOUNT_LOG_ENABLED', true),

        // เก็บ request data ใน log (อาจมีข้อมูลสำคัญ)
        'log_request_data' => env('FLOWACCOUNT_LOG_REQUEST_DATA', false),

        // เก็บ response data ใน log
        'log_response_data' => env('FLOWACCOUNT_LOG_RESPONSE_DATA', false),

        // จำนวนวันที่เก็บ log (0 = ไม่ลบ)
        'retention_days' => env('FLOWACCOUNT_LOG_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings - การตั้งค่า Webhook (ถ้ามี)
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        // เปิดใช้งาน webhook หรือไม่
        'enabled' => env('FLOWACCOUNT_WEBHOOK_ENABLED', false),

        // Webhook secret สำหรับ verify request
        'secret' => env('FLOWACCOUNT_WEBHOOK_SECRET'),

        // Webhook URL ที่ FlowAccount จะส่ง request มา
        'url' => env('FLOWACCOUNT_WEBHOOK_URL', env('APP_URL').'/api/flowaccount/webhook'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling - การจัดการ Error
    |--------------------------------------------------------------------------
    */

    'error_handling' => [
        // ส่ง notification เมื่อ sync ล้มเหลว
        'notify_on_failure' => env('FLOWACCOUNT_NOTIFY_ON_FAILURE', false),

        // Email ที่จะส่ง notification
        'notification_email' => env('FLOWACCOUNT_NOTIFICATION_EMAIL'),

        // จำนวนครั้งที่จะ retry เมื่อ sync ล้มเหลว
        'max_retries' => env('FLOWACCOUNT_MAX_RETRIES', 3),
    ],
];
