<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ════════════════════════════════════════════════════════════════
// 🚨 (2026-05-03) Celtic Cross Auto-Recovery
// ════════════════════════════════════════════════════════════════
// เคสที่กัน: ลูกค้าจ่าย 99฿ แล้วบอทเงียบ ไม่ส่งให้เลือกไพ่
// สาเหตุ: FB push fail (24hr expired) ตอน SMS confirmation hook
//
// สแกนทุก 5 นาที — paid + ค้างที่ celtic_pending_payment / picking ไม่มีไพ่ > 5 นาที
// → re-push first card prompt ผ่าน MESSAGE_TAG=POST_PURCHASE_UPDATE
Schedule::command('fortune:celtic-recover --auto --minutes=5')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('celtic-auto-recovery')
    ->runInBackground();

// ════════════════════════════════════════════════════════════════
// 🔮 Daily Horoscope Auto-Post — โพสดวงประจำวัน 7 วันเกิด (ระบบเดิม)
// ════════════════════════════════════════════════════════════════
// 01:00 → จันทร์ | 02:00 → อังคาร | ... | 07:00 → อาทิตย์
//
// Toggle: fortune_telling_settings.daily_horoscope_per_day_enabled
//   command จะเช็ค toggle เองภายในและข้ามถ้าปิด — ปลอดภัยกว่า
//   เช็คตอน boot route file (ซึ่งอาจรันก่อน DB พร้อม)
//
// withoutOverlapping(15) — กัน overlap 15 นาที (เผื่อ AI ช้า)
// onOneServer() — กัน multi-server รันซ้ำ
foreach (range(1, 7) as $dayOfBirth) {
    $hour = $dayOfBirth; // 1=01:00, 2=02:00, ..., 7=07:00

    Schedule::command("fortune:daily-horoscope:publish {$dayOfBirth}")
        ->dailyAt(sprintf('%02d:00', $hour))
        ->timezone('Asia/Bangkok')
        ->withoutOverlapping(15)
        ->onOneServer()
        ->name("daily-horoscope-day-{$dayOfBirth}")
        ->runInBackground()
        ->when(function () {
            // เช็ค toggle จาก DB (default: false → ปิด)
            try {
                return (bool) \App\Models\FortuneTellingSetting::query()
                    ->value('daily_horoscope_per_day_enabled');
            } catch (\Throwable $e) {
                return false;
            }
        });
}

// ════════════════════════════════════════════════════════════════
// 🌙 Mystic Content Auto-Post — โพสคอนเทนต์สายมู/แก้เคล็ด/สิ่งลี้ลับ
// ════════════════════════════════════════════════════════════════
// รันทุกชั่วโมง 00 นาที — command auto-detect ว่าตรง slot ไหน
// admin ตั้ง slot ใน fortune_telling_settings.mystic_content_schedule
//   เช่น ["08:00", "20:00"] = โพสตอน 8 โมงเช้าและ 2 ทุ่ม
//
// Toggle: fortune_telling_settings.mystic_content_enabled
//   command จะเช็ค toggle ภายในเองอีกชั้น
Schedule::command('fortune:mystic:publish')
    ->hourlyAt(0)
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(20)
    ->onOneServer()
    ->name('mystic-content-publish')
    ->runInBackground()
    ->when(function () {
        try {
            return (bool) \App\Models\FortuneTellingSetting::query()
                ->value('mystic_content_enabled');
        } catch (\Throwable $e) {
            return false;
        }
    });
