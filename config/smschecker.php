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

    // เปิด/ปิดระบบ SMS Checker (unique amount + auto matching)
    // ถ้าเปิด จะสร้าง unique amount สำหรับ promptpay/bank_transfer เสมอ
    'enabled' => env('SMSCHECKER_ENABLED', true),

    // เวลาที่ยอมรับได้สำหรับ request timestamp (วินาที)
    // request ที่เก่ากว่านี้จะถูกปฏิเสธ
    'timestamp_tolerance' => env('SMSCHECKER_TIMESTAMP_TOLERANCE', 300),

    // เวลาหมดอายุสำหรับ unique amounts (นาที)
    // ตั้งเป็น 30 นาที - หลังจากหมดเวลาระบบจะยกเลิกบิลอัตโนมัติ
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
        'TTB' => 'ธนาคารทหารไทยธนชาต',
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

    /*
    |--------------------------------------------------------------------------
    | Sync Settings (Polling-based)
    |--------------------------------------------------------------------------
    |
    | ตั้งค่าสำหรับการ sync ข้อมูลระหว่าง Android app และ Server
    | ใช้ระบบ Polling แทน Push notifications เพื่อความเป็นส่วนตัว
    |
    */
    'sync' => [
        // ระยะเวลา sync (วินาที) - แอพ Android จะ poll ทุก X วินาที
        // ปรับเป็น 60 วินาที เพราะใช้ match-only mode แล้ว (ถามเซิร์ฟเวอร์เมื่อ SMS เข้ามา)
        'interval' => env('SMSCHECKER_SYNC_INTERVAL', 60),

        // Timeout สำหรับ sync request (วินาที)
        'timeout' => env('SMSCHECKER_SYNC_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | ยอดเงินพิเศษ (Special Amounts)
    |--------------------------------------------------------------------------
    |
    | ยอดเงินที่ระบบจะตรวจจับและสร้าง order พิเศษอัตโนมัติ
    | เมื่อ SMS ตรวจพบยอดที่ตรงกัน จะสร้างรายการโดยไม่ต้องรอ admin
    |
    | ถ้ายังไม่ใช่สมาชิก → สร้างเป็น "บิลลอย" (floating bill) รอ admin ยืนยัน
    |
    */
    'special_amounts' => [
        29.99 => [
            'type' => 'fortune_reading',
            'name' => 'ดูดวงเชิงลึก',
            'description' => 'บริการดูดวงผ่าน Facebook Messenger',
            'reading_type' => 'deep',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ข้อความอธิบายลูกค้า (Customer Explanation)
    |--------------------------------------------------------------------------
    |
    | ข้อความอธิบายให้ลูกค้าเข้าใจว่าทำไมยอดโอนมีจุดทศนิยม
    | ใช้แสดงในหน้าชำระเงินและ Facebook Messenger
    |
    */
    'customer_explanation' => [
        'title' => 'ทำไมยอดโอนมีจุดทศนิยม?',
        'message' => 'ยอดเงินที่ท่านต้องโอนจะมีทศนิยม เช่น ฿500.37 แทนที่จะเป็น ฿500.00 เพื่อให้ระบบตรวจสอบการชำระเงินได้อัตโนมัติและรวดเร็ว โดยไม่ต้องรอแอดมินยืนยัน',
        'note' => 'กรุณาโอนตามยอดที่แสดงทุกประการ (รวมจุดทศนิยม) หากโอนยอดไม่ตรง ระบบจะไม่สามารถจับคู่อัตโนมัติได้ และจะต้องรอแอดมินตรวจสอบด้วยตนเอง',
        'messenger_text' => "💰 กรุณาโอนเงินตามยอดที่แจ้ง (รวมสตางค์)\n\n⚡ ทำไมยอดมีจุดทศนิยม?\nเพื่อให้ระบบยืนยันอัตโนมัติ ไม่ต้องรอแอดมิน\n\n⚠️ โอนตามยอดที่แจ้งเท่านั้น หากยอดไม่ตรง จะต้องรอแอดมินตรวจสอบ",
    ],
];
