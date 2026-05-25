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
// 🛑 (2026-05-22 #5) DISABLED per user request — "ปิดระบบตามคนที่ยังไม่ได้รับคำทำนาย"
//   เหตุผล: re-push "เลือกไพ่ใบแรก" ซ้ำๆ สร้างปัญหา (annoy + FB quota)
//   ลูกค้า paid + Celtic ค้าง = admin manual ผ่าน /admin/fortune/billing
// Schedule::command('fortune:celtic-recover --auto --minutes=5')
//     ->everyFiveMinutes()
//     ->withoutOverlapping(10)
//     ->onOneServer()
//     ->name('celtic-auto-recovery')
//     ->runInBackground();

// ════════════════════════════════════════════════════════════════
// 💸 (2026-05-14) Bill Reminder — ทวงลูกค้าที่สร้างบิลแล้วยังไม่โอน
// 🌙 (2026-05-24) ขยาย: ครอบ AWAITING_PAYMENT_METHOD + กระตุ้นภายใน 2 นาทีถ้า idle
// ════════════════════════════════════════════════════════════════
// สแกน every minute — 2 จุด:
//   1) AWAITING_PAYMENT_METHOD อายุ 2-10 นาที (ลูกค้าเห็นปุ่ม QR/บัตร แต่ไม่กด)
//   2) PENDING_PAYMENT / CELTIC_PENDING_PAYMENT อายุ 2-10 นาที (สร้างบิลแล้วไม่โอน)
// → dispatch SendBillReminderJob (AI + RAG admin Q&A few-shot + persona)
// → mark bill_reminder_sent_at — ส่งครั้งเดียว/บิล (กัน spam)
//
// Dedup safe: command + job ต่างเช็ค bill_reminder_sent_at
Schedule::command('fortune:bill-reminder')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-bill-reminder')
    ->runInBackground();

// ════════════════════════════════════════════════════════════════
// 🚨 (2026-05-13) Deep 39฿ Pay-First Auto-Recovery
// ════════════════════════════════════════════════════════════════
// เคสที่กัน: ลูกค้าจ่าย 39฿ แล้วระบบไม่ขอวันเดือนปีเกิดต่อ
// สาเหตุ: handleDeepPayFirstPaymentMatched ส่ง push "ขอวันเกิด" ครั้งแรก
//         แต่ Facebook push fail silent (24hr window expired / rate limit / transient)
//         status=collecting_birthdate ใน DB แต่ลูกค้าไม่เห็นข้อความ
//
// เคสจริง: #2499 (Saksit, FTU-260513-F2933) จ่าย 39.66฿ → status ถูกต้อง
//         แต่ลูกค้าไม่เห็น message → admin ต้อง manual recover
//
// สแกนทุก 5 นาที — paid + collecting_birthdate + paid_at > 3 นาที (เผื่อ initial push)
// → re-push "ขอวันเกิด" ผ่าน POST_PURCHASE_UPDATE
// → dedup: ถ้า birthdate_resent_at < 30 นาที → skip (รอลูกค้าตอบ)
// 🚨 (2026-05-14) เพิ่ม --hours=24 (เดิม 2) — กัน orphan ค้างนานกว่า 2 ชม.
//   เคส #2545: ลูกค้าจ่าย → expire 30 min ก่อน fix → orphan COMPLETED 8 ชม.
//   ต้อง window กว้างขึ้น recover ได้
// 🛑 (2026-05-22 #5) DISABLED per user request — "ปิดระบบตามคนที่ยังไม่ได้รับคำทำนาย"
//   เหตุผล: re-push "ขอวันเกิด" ซ้ำๆ สร้างปัญหา (ลูกค้าทิ้งแล้วก็ทิ้งเลย)
//   ลูกค้า paid + ไม่กรอกวันเกิด = admin manual ผ่าน /admin/fortune/billing
// Schedule::command('fortune:recover-paid-no-birthdate --auto --hours=24 --min-age-minutes=3')
//     ->everyFiveMinutes()
//     ->withoutOverlapping(10)
//     ->onOneServer()
//     ->name('deep-pay-first-auto-recovery')
//     ->runInBackground();

// 💳 (2026-05-09) Stripe webhook fallback — poll session ที่ pending recover ถ้า webhook ตก
//   Stripe webhook ตก/firewall block → บิลจ่ายแล้วลูกค้าไม่ได้คำทำนาย
//   poll Checkout Session API เอง → ถ้า paid → trigger flow
Schedule::command('fortune:stripe-poll --max-age=7200')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('fortune-stripe-poll')
    ->runInBackground();

// ════════════════════════════════════════════════════════════════
// 🔔 (2026-05-25 Patch E) Fortune Remind Stuck Celtic — Soft reminder
// ════════════════════════════════════════════════════════════════
// เคสจริง R3711 (2026-05-25 06:59 → stuck 4hr celtic_picking 0 user msg)
//   หาบิล Celtic 99฿ ที่จ่ายแล้วเงียบ 30 min - 6 hr → ส่ง DM soft reminder
//   ครั้งเดียวต่อ reading (mark conversation_state.celtic_stuck_reminder_sent_at)
//
// Soft tone: "หมอจันทรารอเปิดไพ่ให้คุณ{name}อยู่นะคะ" + ไม่ annoy
// ถ้าลูกค้ายังเงียบ 24hr → fortune:expire-stuck-paid (legacy in Kernel — ghost!) รับช่วงต่อ
//
// ⚠️ NOTE: schedule นี้อยู่ที่นี่ (ไม่ใช่ Kernel.php) เพราะ Laravel 11 bootstrap/app.php
//    ไม่ register Kernel.php — schedule ใน Kernel.php ทั้งหมดเป็น dead code
Schedule::command('fortune:remind-stuck-celtic --min-minutes=30 --max-hours=6 --limit=20')
    ->everyFifteenMinutes()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->name('fortune-remind-stuck-celtic')
    ->runInBackground();

// 🛡️ (2026-05-10) Auto-scan คอมเม้นต์สแปม (link spam moderation)
//   รันทุกชั่วโมง — incremental (เฉพาะคอมใหม่ตั้งแต่ scan ล่าสุด)
//   ครอบคลุม: posts + Reels, per-post=unlimited (รองรับโพสไวรัลคอมหมื่นๆ)
//   only when admin เปิด auto_hide_link_comments
Schedule::command('fortune:scan-old-comments --since-last --execute --all --posts=200 --reels=200')
    ->hourly()
    ->withoutOverlapping(55)
    ->onOneServer()
    ->name('fortune-link-scan-hourly')
    ->runInBackground()
    ->when(function () {
        try {
            return (bool) \App\Models\FortuneTellingSetting::query()
                ->value('auto_hide_link_comments');
        } catch (\Throwable $e) {
            return false;
        }
    });

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
// 🩹 (2026-05-13) เปลี่ยนจาก hourlyAt(0) → everyFiveMinutes
//   user report: "ยังตั้งเวลาโพสไม่ได้" — เพราะ slot ที่ไม่ใช่ HH:00 (เช่น 08:30)
//   ไม่ trigger เพราะ scheduler รันแค่นาที :00
//   ใหม่: รันทุก 5 นาที — command auto-detect ว่าตรง slot ใน window 5 นาทีไหม
//
// admin ตั้ง slot ใน fortune_telling_settings.mystic_content_schedule
//   เช่น ["08:00", "08:30", "20:15"] = โพสตามเวลาที่ตั้งจริง (รองรับ HH:MM)
//
// Toggle: fortune_telling_settings.mystic_content_enabled
//   command จะเช็ค toggle ภายในเองอีกชั้น
Schedule::command('fortune:mystic:publish')
    ->everyFiveMinutes()
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

// ════════════════════════════════════════════════════════════════
// 🤖 Bot Automation — รัน automation ที่ถึงเวลาตาม next_execution_at
// ════════════════════════════════════════════════════════════════
// ปัญหาก่อนแก้: command `bot:process-automations` มีอยู่แต่ไม่ถูกลงทะเบียน
// scheduler เลย ทำให้ "โพสเดี๋ยวนี้" (manual) ใช้ได้ แต่ auto-post ไม่ยิง
//
// ทุกนาที — เช็ค BotAutomation::dueForExecution() (active + next_execution_at <= now)
//   แล้วเรียก executeAutomation() ผ่าน BotAutomationService
//
// withoutOverlapping(5) — กัน overlap 5 นาที (เผื่อ AI gen content ช้า)
// onOneServer() — กัน multi-server รันซ้ำ
Schedule::command('bot:process-automations')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('bot-process-automations')
    ->runInBackground();

// ════════════════════════════════════════════════════════════════
// 📤 Bot Scheduled Posts — โพสที่ admin ตั้งเวลาเฉพาะเจาะจงไว้
// ════════════════════════════════════════════════════════════════
// ทุกนาที — เช็ค BotScheduledPost::dueForPublishing() แล้ว dispatch
//   PublishScheduledPostJob ทีละโพส (ผ่าน queue)
Schedule::job(new \App\Jobs\BotAutomation\ProcessScheduledPostsJob)
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('bot-scheduled-posts-publish');
