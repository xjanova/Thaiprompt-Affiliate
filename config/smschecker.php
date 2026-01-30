<?php

return [
    /*
    |--------------------------------------------------------------------------
    | การตั้งค่า SMS Checker
    |--------------------------------------------------------------------------
    |
    | การตั้งค่าสำหรับระบบชำระเงินผ่าน SMS Payment Checker
    | กำหนดค่าผ่านไฟล์ .env
    |
    */

    // เวลาที่ยอมรับได้สำหรับ request timestamp (วินาที)
    // request ที่เก่ากว่านี้จะถูกปฏิเสธ
    'timestamp_tolerance' => env('SMSCHECKER_TIMESTAMP_TOLERANCE', 300),

    // เวลาหมดอายุสำหรับ unique amounts (นาที)
    'unique_amount_expiry' => env('SMSCHECKER_AMOUNT_EXPIRY', 30),

    // จำนวนสูงสุดของ unique amounts ที่ pending ได้ต่อราคาเดียวกัน
    // ค่าสูงสุด 99 (suffix 01-99)
    'max_pending_per_amount' => env('SMSCHECKER_MAX_PENDING', 99),

    // จำกัด rate: จำนวน notifications สูงสุดต่ออุปกรณ์ต่อนาที
    'rate_limit_per_minute' => env('SMSCHECKER_RATE_LIMIT', 30),

    // ธนาคารที่รองรับ
    'supported_banks' => [
        'KBANK' => 'ธนาคารกสิกรไทย',
        'SCB' => 'ธนาคารไทยพาณิชย์',
        'KTB' => 'ธนาคารกรุงไทย',
        'BBL' => 'ธนาคารกรุงเทพ',
        'GSB' => 'ธนาคารออมสิน',
        'BAY' => 'ธนาคารกรุงศรีอยุธยา',
        'TTB' => 'ธนาคารทีทีบี',
        'PROMPTPAY' => 'พร้อมเพย์',
    ],

    // เวลาหมดอายุของ nonces (ชั่วโมง) - nonces เก่ากว่านี้จะถูกลบ
    'nonce_expiry_hours' => env('SMSCHECKER_NONCE_EXPIRY', 24),

    // ยืนยัน payment อัตโนมัติเมื่อจับคู่สำเร็จ
    'auto_confirm_matched' => env('SMSCHECKER_AUTO_CONFIRM', true),

    // ส่ง notification เมื่อจับคู่สำเร็จ
    'notify_on_match' => env('SMSCHECKER_NOTIFY_ON_MATCH', true),

    // ระดับ log: debug, info, warning
    'log_level' => env('SMSCHECKER_LOG_LEVEL', 'info'),
];
