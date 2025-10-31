<?php

return [
    /*
    |--------------------------------------------------------------------------
    | License API Configuration
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับเชื่อมต่อ License Server (xman4289.com)
    |
    */

    'api_url' => env('LICENSE_API_URL', 'https://xman4289.com/wp-json/tp-license/v1'),

    'license_key' => env('LICENSE_KEY'),

    'installation_id' => env('LICENSE_INSTALLATION_ID'),

    /*
    |--------------------------------------------------------------------------
    | License Validation Settings
    |--------------------------------------------------------------------------
    |
    | ตั้งค่าการตรวจสอบ License
    |
    */

    'validate_on_boot' => env('LICENSE_VALIDATE_ON_BOOT', true),

    'cache_duration' => env('LICENSE_CACHE_DURATION', 604800), // 7 days

    'check_interval' => env('LICENSE_CHECK_INTERVAL', 86400), // 1 day

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | ระยะเวลาผ่อนผัน (วัน) หลัง License หมดอายุ
    |
    */

    'grace_period_days' => env('LICENSE_GRACE_PERIOD', 7),

    /*
    |--------------------------------------------------------------------------
    | Add-ons Configuration
    |--------------------------------------------------------------------------
    |
    | รายการ Add-ons ที่รองรับ
    |
    */

    'addons' => [
        'mlm' => [
            'name' => 'MLM Add-on',
            'slug' => 'mlm',
            'license_key' => env('ADDON_MLM_LICENSE_KEY'),
            'enabled' => env('ADDON_MLM_ENABLED', false),
        ],
        'payment-gateway' => [
            'name' => 'Payment Gateway Add-on',
            'slug' => 'payment-gateway',
            'license_key' => env('ADDON_PAYMENT_LICENSE_KEY'),
            'enabled' => env('ADDON_PAYMENT_ENABLED', false),
        ],
        'analytics' => [
            'name' => 'Advanced Analytics Add-on',
            'slug' => 'analytics',
            'license_key' => env('ADDON_ANALYTICS_LICENSE_KEY'),
            'enabled' => env('ADDON_ANALYTICS_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline Mode
    |--------------------------------------------------------------------------
    |
    | อนุญาตให้ทำงานแบบ offline (สำหรับ development)
    |
    */

    'allow_offline' => env('LICENSE_ALLOW_OFFLINE', false),

    /*
    |--------------------------------------------------------------------------
    | Developer Mode
    |--------------------------------------------------------------------------
    |
    | โหมด Developer (ไม่ตรวจสอบ License)
    |
    */

    'developer_mode' => env('LICENSE_DEVELOPER_MODE', false),
];
