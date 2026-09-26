<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

/**
 * ตอบ 503 ระหว่างเว็บปิดซ่อม — ยกเว้น webhook/callback จากระบบภายนอก
 *
 * 🛠️ (2026-09-26) deploy.sh ปิดเว็บจริงตั้งแต่ STEP 3 (`artisan down`) ถึง STEP 20 (~60-90 วิ)
 *    path ในรายการนี้ต้องรับได้ตลอด เพราะต้นทางส่งซ้ำไม่ได้หรือไม่แน่นอน
 *    (LINE ไม่ส่ง webhook ซ้ำ · SMS เงินเข้า · payment gateway · เซิร์ฟเวอร์ จันทรา.online)
 *
 * ⚠️ ต้องเป็นคลาสนี้ (ชื่อนี้ namespace นี้) เท่านั้น — `php artisan down` ของ Laravel 11
 *    `make(App\Http\Middleware\PreventRequestsDuringMaintenance::class)` แล้วฝัง getExcludedPaths()
 *    ลงไฟล์ storage/framework/down ที่ public/index.php เช็คก่อนบูตแอป
 *    ถ้าไม่มีคลาสนี้ ไฟล์ down ได้ `"except": []` เสมอ ⇒ webhook โดน 503 ตั้งแต่ก่อนถึง Laravel
 *    ฝั่ง HTTP ใช้คลาสนี้แทนตัวของ framework ผ่าน $middleware->replace() ใน bootstrap/app.php
 *
 * ⚠️ รายการถูกฝังตอนสั่ง `down` ⇒ เพิ่ม path ใหม่แล้วมีผลตั้งแต่ deploy "รอบถัดไป"
 */
class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * path ที่เข้าได้แม้เว็บปิดซ่อม
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhook/*',              // web.php: Facebook, LINE fortune/taladsod, Telegram, Stripe (fortune/order)
        'api/webhook/*',          // api.php: LINE, payment gateway, bot/*, chatbot/*
        'api/webhooks/*',         // api.php: GitHub release
        'api/v1/sms-payment/*',   // แอป SMS Checker แจ้งเงินเข้า
        'api/v1/juntra/server/*', // เซิร์ฟเวอร์ จันทรา.online (server-to-server)
        'payment/callback/*',     // web.php: payment gateway callback
    ];
}
