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

// 🕵️ (2026-05-30) Auto-scan DM spammer (คนที่ส่งแต่ลิงก์/รูป ไม่เคยคุย)
//   รายวัน 09:00 — DRY-RUN เท่านั้น (ไม่แบนอัตโนมัติ) → log ผู้ต้องสงสัยไว้ใน laravel.log
//   แอดมินรีวิวแล้วลงมือแบน+block จริงเอง: php artisan fortune:scan-link-spammers --execute
//   ⚠️ ตั้งใจไม่ใส่ --execute ใน schedule — กันแบนพลาดโดยไม่มีคนรีวิว (มนุษย์ตัดสินใจขั้นสุดท้าย)
Schedule::command('fortune:scan-link-spammers --min=3 --days=7')
    ->dailyAt('09:00')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->name('fortune-scan-link-spammers')
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

// ════════════════════════════════════════════════════════════════
// 🚨 (2026-05-25) GHOST SCHEDULES RESCUE — Migrate Kernel.php → routes/console.php
// ════════════════════════════════════════════════════════════════
// Discovery (2026-05-25 Patch E.fix): app/Console/Kernel.php::schedule()
// ไม่ถูกเรียกใน Laravel 11 เพราะ bootstrap/app.php ไม่มี withKernel(Kernel::class)
// → 30+ schedules ใน Kernel.php silent dead code ตั้งแต่ L11 upgrade
//
// Migration policy:
//   ✅ ย้ายทั้งหมดที่ command มีอยู่จริงใน artisan list (verified)
//   ❌ Skip: snake-game:spawn-items + line:cleanup-conversations (commands ไม่มี)
//   ✅ Pattern: ->onOneServer()->name()->runInBackground() — เหมือน schedules ที่มีอยู่
//   ✅ Naming convention: <module>-<purpose> (e.g. fortune-check-pending)
// ════════════════════════════════════════════════════════════════

// ────────────────────────────────────────────────────────────────
// 🔥 CRITICAL — ลูกค้าจ่ายแล้วต้องได้คำทำนาย (revenue impact)
// ────────────────────────────────────────────────────────────────

// 1) Fortune Check Pending — ตรวจสอบบิล Deep 39฿ จ่ายแล้วไม่มี deep_response
//    ทุกนาที — retry ProcessDeepFortuneReadingJob (safety net ถ้า queue worker ตก)
Schedule::command('fortune:check-pending')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-check-pending')
    ->runInBackground();

// 2) Fortune Expire Stuck Paid — last-resort safety net
//    บิลจ่ายแล้วเกิน 24 ชม. ยังไม่ได้คำทำนาย → admin_review + LINE alert
//    ครอบ Deep 39฿ + Celtic 99฿ ทั้งคู่
Schedule::command('fortune:expire-stuck-paid --hours=24 --limit=50')
    ->everySixHours()
    ->withoutOverlapping(30)
    ->onOneServer()
    ->name('fortune-expire-stuck-paid')
    ->runInBackground();

// 3) Fortune Celtic Auto-Finalize — push Grand Finale ลูกค้า Celtic 99฿
//    user spec: ลูกค้าจ่าย 99 บาท ต้องได้ summary ทุกครั้ง ไม่ว่าจบยังไง
//    ทุก 5 นาที สแกน QA window หมดอายุ → push สรุปสุดท้าย
Schedule::command('fortune:celtic-auto-finalize --limit=20')
    ->everyFiveMinutes()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->name('fortune-celtic-auto-finalize')
    ->runInBackground();

// 3b) Fortune Celtic Re-Deliver — 🐛 (2026-05-28) หลักประกันลูกค้าได้รับคำทำนายเสมอ
//     เคส FTU-260528-E8815: AI ตอบสำเร็จ + บันทึก DB แต่ push แรกไม่ถึงลูกค้า (เห็นแค่ "ติดขัด")
//     ทุกนาที — หา question ที่ answered แต่ delivered_at null (ภายใน 2 ชม.) → re-push
//     ChannelManager set delivered_at ตอน push สำเร็จ → cron จับเฉพาะที่ค้างจริง
Schedule::command('fortune:celtic-redeliver --limit=30')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-celtic-redeliver')
    ->runInBackground();

// 4) Fortune Expire Conversations — ปิด orphan conversations + ล้าง takeover หมดเวลา
//    ทุก 5 นาที — กัน conversation ค้างไม่ปิด
Schedule::command('fortune:expire-conversations')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('fortune-expire-conversations')
    ->runInBackground();

// 5) AI Recheck Critical — auto-unban critical API keys ที่ probe สำเร็จ
//    AI ใช้ไม่ได้ = ทำนายไม่ได้ → keys ที่ banned ต้องลอง unban อัตโนมัติ
//    Exponential backoff: 1h → 4h → 12h → 24h → 72h(cap)
Schedule::command('ai:recheck-critical --limit=20')
    ->hourly()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->name('ai-recheck-critical')
    ->runInBackground();

// ────────────────────────────────────────────────────────────────
// 💰 PAYMENT / SMS CHECKER
// ────────────────────────────────────────────────────────────────

// 6) SMS Checker Cleanup — ยกเลิก orders หมดเวลาชำระ (30 นาที) + ล้าง expired amounts
Schedule::command('smschecker:cleanup')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('smschecker-cleanup')
    ->runInBackground();

// 6.1) Purge Slip Archive — ลบรูปสลิปที่ archive ไว้เกิน 30 วัน (PDPA retention)
//      รูปสลิปมีชื่อ/เลขบัญชีลูกค้า → เก็บ debug ได้ 30 วันแล้วลบอัตโนมัติ (user 2026-06-03)
Schedule::command('fortune:purge-slip-archive --days=30')
    ->dailyAt('03:30')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->name('fortune-purge-slip-archive')
    ->runInBackground();

// 7) Crypto Scan Deposits — สแกน blockchain หา deposits ใหม่ (TPIX)
Schedule::command('crypto:scan-deposits')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('crypto-scan-deposits')
    ->runInBackground();

// 8) Crypto Process Withdrawals — process pending withdrawals
Schedule::command('crypto:process-withdrawals')
    ->everyTwoMinutes()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('crypto-process-withdrawals')
    ->runInBackground();

// ────────────────────────────────────────────────────────────────
// 🌟 FORTUNE BUSINESS (marketing / cleanup / horoscope)
// ────────────────────────────────────────────────────────────────

// 9) Fortune Marketing Send — แคมเปญดูดวงตามเวลาที่ตั้งไว้
Schedule::command('fortune:marketing-send')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('fortune-marketing-send')
    ->runInBackground();

// 10) Fortune Cleanup Free Readings — ล้าง DB คำทำนายฟรีเก่า (daily 03:00)
Schedule::command('fortune:cleanup-free')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('fortune-cleanup-free')
    ->runInBackground();

// 11) Fortune Resync Cancelled Bills — backfill FCM ให้แอพ smschecker (daily 06:00)
Schedule::command('fortune:resync-cancelled-bills --days=30 --limit=500')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('fortune-resync-cancelled-bills')
    ->runInBackground();

// 12) Fortune Horoscope Process Generate — สร้างเนื้อหาดวง (ทุก 15 นาที)
Schedule::command('fortune:horoscope-process --generate --sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping(20)
    ->onOneServer()
    ->name('fortune-horoscope-generate')
    ->runInBackground();

// 13) Fortune Horoscope Process Publish — โพสเนื้อหาที่พร้อม (ทุก 5 นาที)
Schedule::command('fortune:horoscope-process --publish')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('fortune-horoscope-publish')
    ->runInBackground();

// 14) Horoscope Public Generate Daily — AI สร้างดวง 12 ราศี + 7 วันเกิด (06:00)
Schedule::command('horoscope:generate-daily')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('horoscope-generate-daily')
    ->runInBackground();

// ────────────────────────────────────────────────────────────────
// 🛒 E-COMMERCE (orders / earnings / payouts)
// ────────────────────────────────────────────────────────────────

// 15) Orders Process Distribution — paid orders ยังไม่ได้ distribute (ทุก 5 นาที)
Schedule::command('orders:process-distribution --limit=50')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('orders-process-distribution')
    ->runInBackground();

// 16) Earnings Release Pending — ปล่อย earnings ที่ถึงเวลา available (ทุก 10 นาที)
Schedule::command('earnings:release-pending --limit=100')
    ->everyTenMinutes()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->name('earnings-release-pending')
    ->runInBackground();

// 17) Payouts Process Scheduled — process payout requests ที่ scheduled
Schedule::command('payouts:process-scheduled')
    ->everyFifteenMinutes()
    ->withoutOverlapping(20)
    ->onOneServer()
    ->name('payouts-process-scheduled')
    ->runInBackground();

// ────────────────────────────────────────────────────────────────
// 📊 ANALYTICS / DEBT / LINE / INVESTMENT
// ────────────────────────────────────────────────────────────────

// 18) Analytics Collect — system metrics ทุก 5 นาที
Schedule::command('analytics:collect')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('analytics-collect')
    ->runInBackground();

// 19) Analytics Cleanup — ล้าง analytics เก่า > 30 วัน (daily 02:00)
Schedule::command('analytics:collect --cleanup')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('analytics-cleanup')
    ->runInBackground();

// 20) Debt Collect Batch — เก็บหนี้อัตโนมัติจาก earnings available (ทุก 30 นาที)
Schedule::command('debt:collect-batch --limit=50')
    ->everyThirtyMinutes()
    ->withoutOverlapping(35)
    ->onOneServer()
    ->name('debt-collect-batch')
    ->runInBackground();

// 21) LINE Cleanup Tokens — ล้าง LINE access tokens หมดอายุ (daily 03:30)
Schedule::command('line:cleanup-tokens --force')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('line-cleanup-tokens')
    ->runInBackground();

// 22) Investment Distribute ROI — ROI to staking positions (daily 00:05)
Schedule::command('investment:distribute-roi')
    ->dailyAt('00:05')
    ->withoutOverlapping(60)
    ->onOneServer()
    ->name('investment-distribute-roi')
    ->runInBackground();

// 23) Investment Distribute ROI Retry — retry ROI ที่ fail (ทุก 6 ชั่วโมง)
Schedule::command('investment:distribute-roi --retry')
    ->everySixHours()
    ->withoutOverlapping(60)
    ->onOneServer()
    ->name('investment-distribute-roi-retry')
    ->runInBackground();

// 24) Threat Intelligence Update — DB-driven schedule
//    Settings: threat_auto_update_enabled / threat_update_frequency / threat_update_time
//    Frequency: hourly | daily | weekly | custom (cron expression)
$threatEnabled = false;
$threatFrequency = 'daily';
$threatTime = '03:00';
$threatCustomCron = '';
$threatDayOfWeek = 0;
try {
    $threatEnabled = (bool) \App\Models\Setting::get('threat_auto_update_enabled', 'boolean', true);
    $threatFrequency = (string) \App\Models\Setting::get('threat_update_frequency', 'string', 'daily');
    $threatTime = (string) \App\Models\Setting::get('threat_update_time', 'string', '03:00');
    $threatCustomCron = (string) \App\Models\Setting::get('threat_update_cron', 'string', '');
    $threatDayOfWeek = (int) \App\Models\Setting::get('threat_update_day', 'integer', 0);
} catch (\Throwable $e) {
    // DB ไม่พร้อม (CI ก่อน migrate) → skip ลงทะเบียน schedule นี้
    $threatEnabled = false;
}

if ($threatEnabled) {
    $threatCmd = Schedule::command('threat:update')
        ->withoutOverlapping(60)
        ->onOneServer()
        ->name('threat-update')
        ->runInBackground();

    switch ($threatFrequency) {
        case 'hourly':
            $threatCmd->hourly();
            break;
        case 'weekly':
            $threatCmd->weeklyOn($threatDayOfWeek, $threatTime);
            break;
        case 'custom':
            if (! empty($threatCustomCron)) {
                $threatCmd->cron($threatCustomCron);
            } else {
                $threatCmd->dailyAt($threatTime);
            }
            break;
        case 'daily':
        default:
            $threatCmd->dailyAt($threatTime);
    }
}

// ────────────────────────────────────────────────────────────────
// 🤖 AI RENTAL GPU SYSTEM
// ────────────────────────────────────────────────────────────────

// 25) AI Rental Health Check — health checks ทุก 5 นาที
Schedule::command('ai-rental:check-health --frequency=5min')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('ai-rental-check-health')
    ->runInBackground();

// 26) AI Rental Reset Budgets — รีเซ็ต budget limits (daily 00:00)
Schedule::command('ai-rental:reset-budgets')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('ai-rental-reset-budgets')
    ->runInBackground();

// 27) AI Rental Cleanup Alerts — auto-resolve alerts หมดอายุ (hourly)
Schedule::command('ai-rental:cleanup-alerts')
    ->hourly()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->name('ai-rental-cleanup-alerts')
    ->runInBackground();

// 28) AI Rental Cleanup Logs — ลบ audit logs > 90 วัน (daily 02:00)
Schedule::command('ai-rental:cleanup-logs --days=90')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('ai-rental-cleanup-logs')
    ->runInBackground();

// ────────────────────────────────────────────────────────────────
// 🎬 VIDEO AUTOMATION SYSTEM
// ────────────────────────────────────────────────────────────────

// 29) Video Automation Schedules — schedule processing (ทุก 5 นาที)
Schedule::command('video-automation:process --schedules --limit=5')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('video-automation-schedules')
    ->runInBackground();

// 30) Video Automation Pending — pending jobs (ทุก 2 นาที)
Schedule::command('video-automation:process --pending --limit=3')
    ->everyTwoMinutes()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('video-automation-pending')
    ->runInBackground();

// 31) Video Automation Retry — failed jobs retry (ทุก 30 นาที)
Schedule::command('video-automation:process --retry --limit=5')
    ->everyThirtyMinutes()
    ->withoutOverlapping(35)
    ->onOneServer()
    ->name('video-automation-retry')
    ->runInBackground();

// 32) Video Automation Cleanup — ลบไฟล์ต้นฉบับหลังโพสสำเร็จ (hourly)
Schedule::command('video-automation:process --cleanup --limit=10')
    ->hourly()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->name('video-automation-cleanup')
    ->runInBackground();

// ════════════════════════════════════════════════════════════════
// ⚠️ DROPPED (commands ไม่อยู่ใน artisan list)
//   - snake-game:spawn-items     — command file ไม่พบ
//   - line:cleanup-conversations — command file ไม่พบ
// ถ้าต้องการกลับมา → restore command file ก่อน แล้วเพิ่ม Schedule ที่นี่
// ════════════════════════════════════════════════════════════════
