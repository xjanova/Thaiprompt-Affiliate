<?php

/**
 * ชำระเงินด้วยเหรียญ TPIX บนเชน TPIX (chain id 4289)
 *
 * ⚠️ config เก่า (tpix.php / crypto.php / tpix-blockchain.php) ชี้เชนเก่า 7000/88888 ที่ไม่มีแล้ว
 *    ระบบชำระเงินใช้ไฟล์นี้ไฟล์เดียว — ค่าทั้งหมดตรวจกับเชนจริงแล้ว 2026-10-13
 *
 * ค่าที่แอดมินปรับได้ (เปิด/ปิด, กระเป๋ารับเงิน, จำนวน confirmation) อยู่ในตาราง settings
 * ผ่าน TpixPaySettings — ค่าในไฟล์นี้เป็นค่าเริ่มต้นและค่าทางเทคนิคของเชน
 */
return [
    // เชน TPIX — เหรียญหลัก TPIX ทศนิยม 18 ค่าแก๊ส 0 บล็อกละ ~2 วินาที
    'chain_id' => 4289,
    'chain_id_hex' => '0x10c1',
    'chain_name' => 'TPIX Chain',
    'decimals' => 18,

    // Cloudflare ปฏิเสธคำขอที่ไม่มี User-Agent (403/429) → ต้องส่งทุกครั้ง
    'rpc_url' => env('TPIX_PAY_RPC_URL', 'https://rpc.tpix.online'),
    'rpc_backup_url' => env('TPIX_PAY_RPC_BACKUP_URL', 'https://rpc1.tpix.online'),
    'explorer_url' => env('TPIX_PAY_EXPLORER_URL', 'https://explorer.tpix.online'),
    'user_agent' => 'Mozilla/5.0 (compatible; Thaiprompt-TpixPay/1.0; +https://main.thaiprompt.online)',
    'http_timeout' => 8,

    // ราคาจริงจากพูล TPIX DEX (WTPIX/USDT_TPIX) — อ่าน getReserves บนเชนตรง
    'dex' => [
        'pair' => '0x3684E0eBD1bd7eF7B7783e695639BADCD04BC4f0',
        'wtpix' => '0x0A3dB168aF6b85e61D81CA78C608BDadAC832DFF',
        'usdt' => '0x0A4Ee87551eF041f3EecFBBfdcfFC1B46a4878f1',
        'usdt_decimals' => 6,
        // พูลบางกว่านี้ (ฝั่ง USDT) = ราคาไม่น่าเชื่อถือ → ไม่ออกใบเสนอยอด
        'min_pool_usd' => (float) env('TPIX_PAY_MIN_POOL_USD', 5),
    ],

    // อัตรา USD → THB (ไม่มีบนเชน) — ห้ามใช้ค่าตายตัวคิดเงิน ถ้าดึงไม่ได้และค่าที่เก็บไว้เก่าเกิน fx_max_age_hours = ไม่ออกใบเสนอยอด
    'fx_urls' => [
        'https://open.er-api.com/v6/latest/USD',
        'https://api.exchangerate-api.com/v4/latest/USD',
    ],
    'fx_cache_minutes' => 30,
    'fx_max_age_hours' => 24,

    // แคชราคาสำหรับแสดงผล (วินาที) — หน้าสินค้าห้ามยิง RPC ทุกครั้งที่เรนเดอร์
    'display_cache_seconds' => 120,

    // ใบเสนอยอดใช้ราคาที่ต่ำกว่าระหว่าง "ราคาตอนนี้" กับ "ค่าเฉลี่ยย้อนหลัง" — กันคนปั่นพูลบางให้ได้ยอดเหรียญน้อย
    'twap_minutes' => 30,
    'twap_min_snapshots' => 3,

    // ตัวสแกนบล็อก (tpix:pay-scan) — อ่านกี่บล็อกต่อรอบ / ต่อคำขอ
    'scan_max_blocks' => 900,
    'scan_batch' => 40,

    // ใบเสนอยอด
    'quote_ttl_minutes' => 30,
    // หลังหมดเวลา ยังกันยอดเดิมไว้อีกช่วงหนึ่ง (เงินที่โอนช้ายังจับคู่ได้ ไม่ไปชนใบใหม่)
    'late_grace_minutes' => 120,
    // นาฬิกาเชนกับเซิร์ฟเวอร์ต่างกันได้เล็กน้อย
    'clock_skew_seconds' => 30,

    // เทสต์อัตโนมัติไม่ยิงเชน/อัตราแลกเปลี่ยนจริง (เทสต์ที่จำลองเครือข่ายเปิดเองด้วย config())
    'allow_network_in_tests' => false,

    // ค่าเริ่มต้นของการตั้งค่าแอดมิน
    'defaults' => [
        'enabled' => false,
        'receive_address' => '',
        'confirmations' => 3,
        // เพดาน (บาท) — 0 = ไม่จำกัด · พูล DEX ยังบาง เหรียญที่รับมาขายคืนเป็นเงินบาทตามราคานี้ได้ไม่หมด
        'max_per_payment_thb' => 20000,
        'max_per_user_day_thb' => 50000,
        // ยอดต่อรายการไม่เกินกี่เท่าของมูลค่าฝั่ง USDT ในพูล DEX (พูลบาง = ปั่นราคาง่าย) — 0 = ไม่จำกัด
        'max_pool_ratio' => 5,
    ],
];
