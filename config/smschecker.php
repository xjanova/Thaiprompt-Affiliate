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
    // 🔧 (2026-05-21) ขยายจาก 30 นาที → 24 ชั่วโมง (1440 นาที)
    //   user spec: "ต้องเก็บไว้อย่างน้อย 24 ชม. สำคัญที่สุด"
    //   เคสบั๊ก: ลูกค้าจำนวนมากโอนช้ากว่า 30 นาที (รอเช็คยอด, รอเงินเดือนออก, เครื่องดับ)
    //   ลูกค้าไม่รู้ว่าเกิน 30 นาทีแล้วจ่ายไม่ได้ — โอนแล้วระบบไม่ตัด → ต้อง admin force fix
    //   ขยาย 24 ชม. ให้ลูกค้ามีเวลาเหลือเฟือ + Path 1 ของ findMatch() หา UPA active ตรงๆ
    //   ไม่กระทบ suffix space (max 99/ราคา) — ปกติบิลต่อชั่วโมงน้อยกว่ามาก
    'unique_amount_expiry' => env('SMSCHECKER_AMOUNT_EXPIRY', 1440),

    // จำนวนสูงสุดของ unique amounts ที่ pending ได้ต่อราคาเดียวกัน
    // ค่าสูงสุด 99 (suffix 01-99)
    'max_pending_per_amount' => env('SMSCHECKER_MAX_PENDING', 99),

    // จำกัด rate: จำนวน notifications สูงสุดต่ออุปกรณ์ต่อนาที
    // ตั้ง 120 เพื่อไม่ block FCM token sync และ debug reports
    'rate_limit_per_minute' => env('SMSCHECKER_RATE_LIMIT', 120),

    // ธนาคารที่รองรับ (15 ธนาคาร - ตรงกับ Android SmsChecker v1.9.1)
    'supported_banks' => [
        'KBANK' => 'ธนาคารกสิกรไทย',
        'SCB' => 'ธนาคารไทยพาณิชย์',
        'KTB' => 'ธนาคารกรุงไทย',
        'BBL' => 'ธนาคารกรุงเทพ',
        'GSB' => 'ธนาคารออมสิน',
        'BAY' => 'ธนาคารกรุงศรีอยุธยา',
        'TTB' => 'ธนาคารทหารไทยธนชาต',
        'PROMPTPAY' => 'พร้อมเพย์',
        'CIMB' => 'ธนาคารซีไอเอ็มบี ไทย',
        'KKP' => 'ธนาคารเกียรตินาคินภัทร',
        'LH' => 'ธนาคารแลนด์ แอนด์ เฮ้าส์',
        'TISCO' => 'ธนาคารทิสโก้',
        'UOB' => 'ธนาคารยูโอบี',
        'ICBC' => 'ธนาคารไอซีบีซี (ไทย)',
        'BAAC' => 'ธนาคารเพื่อการเกษตรและสหกรณ์การเกษตร',
    ],

    // เวลาหมดอายุของ nonces (ชั่วโมง) - nonces เก่ากว่านี้จะถูกลบ
    'nonce_expiry_hours' => env('SMSCHECKER_NONCE_EXPIRY', 24),

    /*
    |--------------------------------------------------------------------------
    | Default Approval Mode
    |--------------------------------------------------------------------------
    |
    | โหมดอนุมัติเริ่มต้นสำหรับอุปกรณ์ใหม่
    | สามารถเปลี่ยนแปลงได้ต่ออุปกรณ์ผ่าน Android app
    |
    | Options:
    | - 'auto': อนุมัติอัตโนมัติเมื่อยอดตรงกัน 100%
    | - 'manual': ทุกรายการต้องอนุมัติด้วยตัวเอง
    | - 'smart': auto สำหรับ exact match, manual สำหรับ partial/suspicious
    |
    */
    'default_approval_mode' => env('SMSCHECKER_DEFAULT_APPROVAL_MODE', 'auto'),

    // ยืนยัน payment อัตโนมัติเมื่อจับคู่สำเร็จ (ผ่าน /orders/match endpoint)
    'auto_confirm_matched' => env('SMSCHECKER_AUTO_CONFIRM_MATCHED', true),

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
        // ปรับเป็น 300 วินาที (5 นาที) เพราะใช้ FCM push เป็นตัวหลัก
        // periodic sync เป็น backup เท่านั้น
        'interval' => env('SMSCHECKER_SYNC_INTERVAL', 300),

        // Timeout สำหรับ sync request (วินาที)
        'timeout' => env('SMSCHECKER_SYNC_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | ตั้งค่าการแจ้งเตือนเมื่อจับคู่การชำระเงินสำเร็จ
    |
    */
    'notifications' => [
        // ส่ง LINE notification เมื่อจับคู่การชำระเงินได้
        'line_on_match' => env('SMSCHECKER_LINE_ON_MATCH', true),

        // ส่ง email notification เมื่อจับคู่การชำระเงินได้
        'email_on_match' => env('SMSCHECKER_EMAIL_ON_MATCH', false),

        // ส่ง FCM push notification เมื่อจับคู่การชำระเงินได้
        'fcm_on_match' => env('SMSCHECKER_FCM_ON_MATCH', true),

        // ส่ง FCM push notification เมื่อมีบิลใหม่
        'fcm_on_new_order' => env('SMSCHECKER_FCM_ON_NEW_ORDER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Settings
    |--------------------------------------------------------------------------
    |
    | ตั้งค่าสำหรับ WebSocket/Pusher broadcasting (real-time updates)
    |
    */
    'websocket' => [
        // เปิด/ปิด real-time broadcasting
        'enabled' => env('SMSCHECKER_WEBSOCKET_ENABLED', true),

        // Channel prefix สำหรับ WebSocket
        'channel_prefix' => env('SMSCHECKER_CHANNEL_PREFIX', 'sms-checker'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Orphan Transaction Settings
    |--------------------------------------------------------------------------
    |
    | ตั้งค่าสำหรับจัดการ orphan transactions (SMS ที่ไม่ตรงกับบิลใดๆ ณ ขณะรับ)
    |
    */
    'orphan' => [
        // เก็บ orphan transactions กี่วันก่อนหมดอายุ
        'retention_days' => env('SMSCHECKER_ORPHAN_RETENTION_DAYS', 7),

        // ช่วงเวลา (นาที) ที่ยอมรับสำหรับค้นหาบิลที่ตรงกัน (grace period)
        'match_window_minutes' => env('SMSCHECKER_ORPHAN_MATCH_WINDOW', 60),

        // ค่าเผื่อทศนิยมสูงสุดสำหรับ amount matching
        'amount_tolerance' => env('SMSCHECKER_ORPHAN_AMOUNT_TOLERANCE', 0.01),
    ],

    /*
    |--------------------------------------------------------------------------
    | ยอดเงินพิเศษ (Special Amounts)
    |--------------------------------------------------------------------------
    |
    | ยอดเงินที่ระบบจะตรวจจับและสร้าง order พิเศษอัตโนมัติ
    | เมื่อ SMS ตรวจพบยอดที่ตรงกัน จะสร้างรายการโดยไม่ต้องรอ admin
    |
    | หมายเหตุ: ระบบดูดวงใช้ UniquePaymentAmount (49 + unique decimal)
    | ไม่ใช้ special_amounts แบบ fixed amount อีกต่อไป
    |
    */
    'special_amounts' => [
        // ไม่มีการใช้งาน special amounts แบบ fixed
        // ทุกการชำระเงินใช้ unique decimal ผ่าน UniquePaymentAmount
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
