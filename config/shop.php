<?php

/**
 * การตั้งค่าระบบร้านค้า (Shop Configuration)
 *
 * รวบรวมการตั้งค่าเกี่ยวกับระบบร้านค้า, Official Shop, และ E-commerce
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Official Shop Settings
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับร้านค้าทางการ (Official Shop)
    | สินค้าของ Official Shop จะใช้ seller_id เป็น ID ของ user นี้
    |
    */
    'official_shop' => [
        // Email ของ Official Shop Seller (ใช้หา user)
        'seller_email' => env('OFFICIAL_SHOP_EMAIL', 'official-shop@thaiprompt.com'),

        // ชื่อร้าน
        'name' => env('OFFICIAL_SHOP_NAME', 'Official Shop'),

        // คำอธิบายร้าน
        'description' => env('OFFICIAL_SHOP_DESCRIPTION', 'ร้านค้าทางการของระบบ สินค้าคุณภาพสูง รับประกันแท้ 100%'),

        // Logo URL (ถ้าไม่มีจะใช้ default)
        'logo' => env('OFFICIAL_SHOP_LOGO', null),

        // Banner URL (ถ้าไม่มีจะใช้ default)
        'banner' => env('OFFICIAL_SHOP_BANNER', null),

        // อัตราคอมมิชชั่นเริ่มต้น (%)
        'default_commission_rate' => env('OFFICIAL_SHOP_COMMISSION_RATE', 25.00),

        // อัตราคอมมิชชั่นสูงสุด (%)
        'max_commission_rate' => env('OFFICIAL_SHOP_MAX_COMMISSION_RATE', 40.00),

        // PV เริ่มต้น (% ของราคา)
        'default_pv_percentage' => env('OFFICIAL_SHOP_PV_PERCENTAGE', 10.00),
    ],

];
