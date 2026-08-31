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
// ⏰ (2026-06-12) บิลอายุ 3 ชม. (bill_payment_timeout_minutes) → เตือน 3 จังหวะ
// ════════════════════════════════════════════════════════════════
// สแกน every minute — 2 จุด:
//   1) AWAITING_PAYMENT_METHOD อายุ 2-10 นาที (ลูกค้าเห็นปุ่ม QR/บัตร แต่ไม่กด) — one-shot เดิม
//   2) PENDING_PAYMENT / CELTIC_PENDING_PAYMENT — 3 จังหวะตลอดอายุบิล:
//      stage 1 = นาที 2-15 / stage 2 = ~45-60% ของอายุบิล / stage 3 = 35 นาทีสุดท้าย (ครั้งสุดท้าย)
// → dispatch SendBillReminderJob (AI + RAG admin Q&A few-shot + persona — โทนต่างกันทุก stage)
// → mark bill_reminder_stage (เก็บ bill_reminder_sent_at ไว้ compat)
//
// Dedup safe: command + job ต่างเช็ค bill_reminder_stage
Schedule::command('fortune:bill-reminder')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-bill-reminder')
    ->runInBackground();

// ════════════════════════════════════════════════════════════════
// 🛒 (2026-06-30) Lazada Hub — Auto-import คิวสินค้า (ค่อยๆ ดึงทีละน้อย)
// ════════════════════════════════════════════════════════════════
// อ่านคิว lazada_auto_import_queue → scrape ทีละ 5 รายการทุก 5 นาที จนครบ
// เปิด/ปิดด้วย setting lazada_auto_sync_enabled (command เช็คเอง + return ถ้าปิด/คิวว่าง = ถูกมาก)
Schedule::command('lazada-hub:auto-import --limit=5')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('lazada-hub-auto-import')
    ->runInBackground();

// ════════════════════════════════════════════════════════════════
// 💬 (2026-06-19) Realtime warroom chat log — midnight cleanup
// ════════════════════════════════════════════════════════════════
// The chat log (Redis) keeps only TODAY's conversation for the warroom /chat
// transcript, then is wiped at midnight to save memory. Keys are TTL'd to ~00:30
// (self-expire); this command is the explicit purge that frees memory at 00:05
// Bangkok and sweeps any days a run was missed.
Schedule::command('fortune:chatlog:purge')
    ->dailyAt('00:05')
    ->timezone('Asia/Bangkok')
    ->onOneServer()
    ->name('fortune-chatlog-purge')
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

// 💳 E-commerce order Stripe — poll session ที่ลูกค้าจ่ายแล้วแต่ไม่ได้กลับมา (ปิดแท็บ)
//   ตัวสำรองของ success_url return + webhook → กันออเดอร์ค้าง pending ทั้งที่จ่ายแล้ว
Schedule::command('order:stripe-poll --minutes=180')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('order-stripe-poll')
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

// ════════════════════════════════════════════════════════════════
// 🔔 (2026-06-20) Fortune Flow Nudge — กระตุ้นขั้นเลือกแพคเกจ / กล่องกติกา
// ════════════════════════════════════════════════════════════════
// user spec: เลือกแพคเกจ/กล่องกติกาก่อนกดโอนค่าบูชาครู เงียบ 1 นาที → ส่งกล่องกระตุ้น
//   (ครั้งเดียวต่อ step) + เงียบ 30 นาที → ออกจากโฟลว์เงียบๆ (เฉพาะที่ยังไม่สร้างบิล)
// ⚠️ ที่นี่ (ไม่ใช่ Kernel.php) เพราะ Laravel 11 ไม่ register Kernel schedule
Schedule::command('fortune:flow-nudge --limit=50')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->onOneServer()
    ->name('fortune-flow-nudge')
    ->runInBackground();

// 🎧 (2026-06-21) เสียง "เลือกไพ่ใบต่อไป" (Celtic 99) — เงียบเกิน celtic_pick_voice_delay_sec
//   วินาทีหลังเปิดไพ่ใบที่แล้ว → ส่งเสียงกระตุ้น 1 ครั้ง/ใบ (กดก่อนครบเวลา=ไม่เล่น)
//   self-gates: ปิดเองถ้า system_voice_enabled=false / delay=0 / คลิป card_pick_howto ปิด
// ⚠️ ที่นี่ (ไม่ใช่ Kernel.php) เพราะ Laravel 11 ไม่ register Kernel schedule
Schedule::command('fortune:celtic-pick-voice-nudge --limit=80')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->onOneServer()
    ->name('fortune-celtic-pick-voice-nudge')
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
//   🚫 (2026-06-04, user directive "เอา A แต่แบนคนสแปมชัด") เปิด --execute แบนอัตโนมัติ
//   แต่ "ขันเกณฑ์ให้ฟันเฉพาะสแปมชัด" กัน false-ban:
//     --min-days=2 → ต้องส่งสแปม "ข้ามวัน" (≥2 วันต่างกัน) = ตั้งใจ ไม่ใช่ส่งรูปชุดเดียววันเดียว
//     + audio ไม่นับเป็นสแปมแล้ว (FortuneContactSignalService::isMedia) — ตัดคนส่งเสียงทิ้ง
//   🛡️ Safety nets ที่ยังทำงาน: whitelist อัตโนมัติ (เคยพิมพ์คุย/กดปุ่ม/จ่ายเงิน)
//     + re-check paid history ก่อนแบนทุกคน (ในคำสั่ง) → ลูกค้าจริงไม่มีวันโดน
//   รีวิวย้อนหลังได้ที่ laravel.log ("FortuneScanLinkSpammers: executed" + psids) / ตาราง fortune_contact_signals (status=banned)
//   🔁 (2026-08-08) เพิ่ม --wl-min=5 = "ราง 2" จับคนที่เคยคุยจริง/ถูก whitelist แล้ว
//     แต่ยังยิงลิงก์ ≥5 ครั้ง ข้ามวัน ≥2 วัน (เคสจริง อุดม ศรีโปฎก ยิงลิงก์แชร์ 13 ครั้งใน 11 นาที
//     แต่ whitelisted=1 ตลอดชีพ ระบบแตะไม่ได้เลย)
//     🛡️ ราง 2 นับเฉพาะ "ลิงก์" ไม่นับรูป/วิดีโอ → คนส่งสลิปซ้ำๆ ไม่มีวันเข้าเกณฑ์
//     🛡️ counter wl_link_* เริ่มที่ 0 ทุกแถวตอน migrate → ไม่มีการแบนย้อนหลัง
Schedule::command('fortune:scan-link-spammers --min=3 --days=7 --min-days=2 --wl-min=5 --execute')
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
// 📣 Content Campaigns — โพสคอนเทนต์อัตโนมัติแบบหลายแคมเปญ (2026-07-10)
// ════════════════════════════════════════════════════════════════
// Generalize จากระบบสายมูเดิม: แต่ละแคมเปญ (กำลังใจ/กฎแห่งกรรม/จิตวิทยา/สายมู ฯลฯ)
// มีตารางเวลาของตัวเองใน fortune_content_campaigns.schedule — อิสระจากกัน
//
// รันทุก 5 นาที → command loop แคมเปญที่เปิด แล้ว match slot ใน window 5 นาที
// Toggle: is_enabled รายแคมเปญ (ไม่มี global toggle) — when() เช็คว่ามีแคมเปญเปิดอย่างน้อย 1
Schedule::command('fortune:content:publish')
    ->everyFiveMinutes()
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(20)
    ->onOneServer()
    ->name('content-campaigns-publish')
    ->runInBackground()
    ->when(function () {
        try {
            return \App\Models\FortuneContentCampaign::query()
                ->where('is_enabled', true)
                ->exists();
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

// 3-bis) Fortune Celtic Aftercare Close — 🤝 (2026-08-29 FTU-260829-M9469) กล่าวลา+อวยพรปิดช่วงคุยต่อ
//     บทสรุปยิงที่นาทีที่ 15 ตามเดิม แต่แม่หมอไม่วางสายทันที — ยังคุยต่อเรื่องรอบเดิมได้
//     ตัวนี้ปิดให้เมื่อ "เงียบครบ idle (10 นาที)" หรือ "ครบเพดานรวม (30 นาที จากคำถามแรก)"
//     (ทางที่ 3 คือลูกค้าพิมพ์ "ขอบคุณ" เอง — ดักที่ webhook ไม่ต้องรอ cron)
//     ⏱ ทุก 2 นาที — ต้องถี่กว่า prosession-clear-stale (10 นาที) ไม่งั้นตัวกวาด flag
//        จะล้างเซสชันทิ้งเงียบ ๆ ก่อน แล้วลูกค้าไม่ได้คำอวยพรส่งท้ายเลย
Schedule::command('fortune:celtic-aftercare-close --limit=50')
    ->everyTwoMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('fortune-celtic-aftercare-close')
    ->runInBackground();

// 3a) Fortune Deep Auto-Finalize — 🌙 (2026-06-08) แจ้ง "หมดเวลาทำนาย" ลูกค้า Deep 39฿
//     window คุย 7 นาที (deep_reading_qa_window_minutes) → ทุก 3 นาทีสแกน session ที่หมดเวลา
//     ส่ง "หมดเวลา + ขอบคุณ + อ่านคำทำนายย้อนหลังได้" — ไม่มีบทสรุปแบบ Celtic 99 (user spec)
Schedule::command('fortune:deep-auto-finalize --limit=30')
    ->everyThreeMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('fortune-deep-auto-finalize')
    ->runInBackground();

// 3a-bis) Fortune Pro Session Clear-Stale — 🧹 (2026-07-08) safety-net กวาด flag pro_session_active ค้าง
//     ต้นตอ incident 82-customer: Celtic finale ผ่าน max_questions_reached/ai_signal คง flag ไว้ให้ linger
//     ถ้าลูกค้าเงียบหลัง finale → ไม่มี cron ไหนกวาด (auto-finalize จับเฉพาะ status ที่ยังไม่ completed)
//     → flag ค้างถาวร → isInPrediction บล็อก "ดูดวง" ครั้งใหม่ ("ระบบกำลังดำเนินการ")
//     ทุก 10 นาที สแกน completed + pro_session_active=true ที่ window หมดเวลา → clear (time-bound, ไม่แตะที่ยัง linger)
Schedule::command('fortune:prosession-clear-stale --limit=200')
    ->everyTenMinutes()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->name('fortune-prosession-clear-stale')
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

// 3b-summary) Fortune Celtic SUMMARY Re-Deliver — 🛟 (2026-08-31) ตามส่ง "บทสรุป 99฿" โดยเฉพาะ
//     🐛 ทำไมต้องแยกคำสั่ง: ตัวกู้บทสรุปของเดิมถูกวางไว้ **ข้างในลูปคำถามที่ยังไม่ส่ง**
//        ของ fortune:celtic-redeliver (บรรทัด ~109) แต่ก่อนเข้าลูปมี `if ($candidates->isEmpty()) return 0;`
//        ⇒ พอคำถามส่งครบทุกข้อ (ปกติมาก เพราะ parked delivery ส่งคืนฟรีตอนลูกค้าทัก)
//        ลูปว่าง → return ก่อน → **ตัวกู้บทสรุปไม่เคยทำงาน**
//        ตาข่ายขาดตรงเคสที่ต้องใช้ที่สุด: คำถามครบแต่บทสรุปหาย (เคสจริง reading 11901)
//     เจ้าของ 2026-08-31: "เมื่อบทสรุปเสร็จ ควรส่งให้ลูกค้า แต่ถ้าต้อง Push ก็ให้พุชไป"
//     ⇒ บทสรุป = ของสำคัญที่ push ถูกสงวนไว้ให้ · โควต้าหมด = ข้ามโดยไม่นับ attempt (รอ push กลับมา)
Schedule::command('fortune:celtic-summary-redeliver --limit=20')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-celtic-summary-redeliver')
    ->runInBackground();

// 3b-bis) Fortune Celtic Answer Recover — 🛟 (2026-07-08) กู้ "ถามแล้วไม่มีคำตอบ" (คนละจุดกับ redeliver)
//     เคส Siripon Schröter: buffered question job (tries=1) หายตอน deploy รีสตาร์ท worker → เงียบ 9 นาที
//     A) awaiting + buffer celtic_q ค้างเกิน grace → re-dispatch job (flush-lock กัน double-answer)
//     B) generating ค้าง >5 นาที (job timeout 180s = ตายแน่) → revert awaiting + nudge ให้พิมพ์ใหม่
//     ทุกนาที — buffer TTL 5 นาที จึงต้องจับให้ทัน; ไม่แตะ hot path (idempotent ทั้งคู่)
Schedule::command('fortune:celtic-answer-recover --limit=50')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-celtic-answer-recover')
    ->runInBackground();

// 3b-ter) Fortune Pro Session Answer Recover — 🛟 (2026-08-17) คู่แฝดของ 3b-bis ฝั่ง Deep 39
//     เพิ่มพร้อม settle window ของ Pro Session — กลไก buffer แบบเดียวกันนี้เคยทำลูกค้าเงียบ
//     มาแล้วจริงฝั่ง Celtic (job tries=1 หายตอน deploy รีสตาร์ท worker → ไม่มีใคร flush)
//     มี buffer 'deep_qa' ค้างเกิน grace + session ยังเปิด → re-dispatch job (idempotent)
//     ไม่มีเคส "generating ค้าง" เพราะ Pro Session ไม่มี state แยก (handleProSession sync ในตัว job)
Schedule::command('fortune:pro-session-answer-recover --limit=50')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-pro-session-answer-recover')
    ->runInBackground();

// 3b-quater) Fortune Bubble Recover — 🛟 (2026-08-28) กู้ "คำทำนายส่งไปครึ่งเดียว" ของสายบับเบิ้ล
//     กล่องแรกส่ง sync + markDelivered() แล้ว ⇒ cron redeliver เดิมมองว่าครบ มองไม่เห็นเคสนี้เลย
//     worker ตายตรงนั้น = ลูกค้าที่จ่ายเงินได้ท่อนแรกท่อนเดียว **ถาวร ไม่มี error ที่ไหน**
//     อ่านธง bubble_pending บน conversation_state (MySQL — deploy ล้างไม่ได้) แล้วเทที่เหลือรวดเดียว
//     grace = (กล่องมากสุด × ระยะห่างมากสุด) + 90s ⇒ ไม่แย่งส่งทับลูกโซ่ที่ยังวิ่งปกติ
Schedule::command('fortune:bubble-recover --limit=50')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-bubble-recover')
    ->runInBackground();

// 3c) Fortune Pro Session Nudge — 🔔 (2026-06-30, owner) ตามให้ลูกค้าเริ่มถามคำถาม
//     owner spec: ลูกค้ายังไม่ถามเลย → ตามทุก interval (default 10 นาที) ระหว่างสแตนบาย (default 30 นาที)
//     ครบสแตนบายไม่ถาม → auto-finalize สรุปให้ (เดิม: ตามครั้งเดียว → เปลี่ยนเป็นตามซ้ำทุก interval)
//     ทั้ง Deep 39 (หลังส่งคำทำนาย) + Celtic 99 (หลังพื้นดวง Q1) — เวลา QA เริ่มจับหลังถามจริง
//     cron รันทุกนาที แต่ isProSessionAwaitingNudge คุมระยะเว้น interval เอง
Schedule::command('fortune:pro-session-nudge --limit=30')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('fortune-pro-session-nudge')
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

// 6.2) (2026-06-06) Prune Saved Questions — ล้างคำถามรอแอดมินที่จบงาน/เป็น noise (ประหยัด DB)
//      - noise: ai_failed/fallback ที่ลูกค้าไม่ได้กดขอแอดมิน + ไม่เคยตอบ เกิน 48 ชม. → ลบ
//      - done:  ตอบแล้ว+ส่งถึงผู้ใช้แล้ว เกิน 7 วัน → ลบ (Q&A capture เข้า fortune_admin_qa RAG แล้ว ไม่หาย)
//      - ไม่แตะ: pending ที่ลูกค้าฝากจริง (ai_cannot_answer/user_initiated ยังไม่ตอบ)
Schedule::command('fortune:prune-saved-questions --failed-hours=48 --replied-days=7')
    ->dailyAt('03:45')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->name('fortune-prune-saved-questions')
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

// 12) Fortune Horoscope Process Generate — สร้างเนื้อหาดวง (ทุก 5 นาที)
//
// ⏱️ (2026-08-19) 15 นาที → 5 นาที เพื่อลดเวลาที่โพสออกช้ากว่าเวลาที่ตั้งไว้
//    วัดจริง 19 ส.ค.: campaign ตั้ง schedule_time=00:01 แต่ tick 00:00 ไม่ได้สร้าง
//    (ยังหาสาเหตุไม่ได้ — `runInBackground()` ทิ้ง output ลง /dev/null หมด)
//    จึงไปสร้างเอาที่ tick 00:15 (00:15:03-00:15:58) แล้ว --publish (*/5) โพส 00:20
//    = ช้ากว่าที่ตั้งไว้ 19 นาที
//
//    เปลี่ยนเป็น */5 → ถ้า tick แรกพลาด รอบถัดไปห่างแค่ 5 นาที ไม่ใช่ 15
//    ทำให้ worst case เหลือ ~10 นาที โดยไม่ต้องรู้ว่าอะไรทำให้ tick แรกพลาด
//
// 💰 ไม่เปลือง AI/เงิน: `readyToGenerate()` กรองด้วย
//    `whereDate('last_generated_at','<',วันนี้)` ⇒ วันละ 1 ครั้งเท่านั้น
//    รอบที่ไม่ถึงคิวคือ query เปล่าแล้วจบ (ดู FortuneHoroscopeProcess::processGenerate)
Schedule::command('fortune:horoscope-process --generate --sync')
    ->everyFiveMinutes()
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

// 14) Horoscope Public Generate Daily — AI สร้างดวง 12 ราศี + 7 วันเกิด (00:01)
//
// 🕛 (2026-08-06) ย้าย 06:00 → 00:01 ตามคำสั่งเจ้าของ
//    "ขยับเป็น 00:01 ของวันใหม่ เพื่อจะได้ทำนายกลบรอยต่อ จะได้มีข้อมูลไว้ให้กับลูกค้า"
//
//    เดิม: เที่ยงคืน–06:00 = ช่องโหว่ 6 ชั่วโมง — บทความของ "วันนี้" ยังไม่ถูกสร้าง
//    ลูกค้าที่ทักมาช่วงดึก/เช้ามืดจึงไม่มีดวงประจำวันให้ส่ง
//    (FortuneGreetingService::buildDailyHoroscopeBox() หา target_date = วันนี้ → ไม่เจอ)
//    ใหม่: ตัดขึ้นต้นวันเลย — 00:01 สร้างเสร็จราว 00:02 (prod ใช้เวลา ~1 นาที/รอบ)
//
//    ⏱️ timezone ระบุชัดกันพลาด — command ใช้ Carbon::now('Asia/Bangkok')->startOfDay()
//       เป็น target_date อยู่แล้ว ถ้า scheduler ตีความคนละโซนจะได้บทความของวันผิด
//
// 🚨 (2026-08-08) แก้ 2 จุดที่ทำให้ "วันนี้ไม่มีบทความ" แล้วไม่มีใครรู้
//
//    1) `withoutOverlapping()` เปล่า ๆ = mutex อายุ **1440 นาที (24 ชม.)**
//       งานที่รันวันละครั้งเวลาเดิมเป๊ะ + mutex 24 ชม. = ระเบิดเวลา:
//       ถ้ารอบไหนถูกฆ่ากลางคัน (OOM / deploy restart php-fpm / SSH ตัด) ตัว
//       `schedule:finish` จะไม่ถูกเรียก → mutex ค้างเต็ม 24 ชม. → **รอบพรุ่งนี้
//       เวลา 00:01 ถูกข้ามเงียบ ๆ** (ขอบเวลาชนกันพอดี = race ที่แพ้บ่อย)
//       ⇒ ตั้ง 30 นาที: ยาวพอกันซ้อน (รอบจริง ~1-4 นาที) สั้นพอไม่กินวันถัดไป
//
//    2) `runInBackground()` + cron `>> /dev/null 2>&1` = output หายหมด
//       รอบที่ล้มจึงไม่เหลือหลักฐานเลยสักบรรทัด (2026-08-08 ต้องเดาสาเหตุ
//       เพราะ deploy 03:07 ไป truncate laravel.log ทับช่วง 00:01 พอดี)
//       ⇒ appendOutputTo แยกไฟล์ + onFailure log ให้ค้นเจอด้วย exit code
Schedule::command('horoscope:generate-daily')
    ->dailyAt('00:01')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->name('horoscope-generate-daily')
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/horoscope-daily.log'))
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error(
            '🔮 horoscope:generate-daily ล้ม (exit != 0) — ดูรายละเอียดที่ storage/logs/horoscope-daily.log '
            .'· ตัวกู้อัตโนมัติ fortune:daily-preflight --heal จะลองซ้ำให้ 00:20 และ 06:00'
        );
    });

// 14b) 🩺 Preflight ดวงรายวัน — ยามเฝ้าประตูของข้อ 14 (00:20 และ 06:00)
//
// 🚨 (2026-08-08) เหตุผลที่ต้องมี — เคสจริงที่เพิ่งเจอ:
//    วันที่ 2026-08-08 บทความ 7 วันเกิดไม่ถูกสร้างเลย (0 แถว) ตั้งแต่เที่ยงคืน
//    ทุกด่านของโหมด daily ถาม `dailyArticlesReadyToday()` เหมือนกันหมด →
//    isDailyServing()=false → DM คอมเมนต์/กดไลก์ **fallback ไปชุดขายแบบเก่า
//    อย่างสุภาพ** จนดูเหมือนไม่มีอะไรพัง เจ้าของจับได้เองตอนบ่ายหลังยิงไปแล้ว 465 DM
//
//    ⚠️ บทเรียน: fallback ที่ออกแบบมาดี = ความล้มเหลวที่มองไม่เห็น
//       ฟีเจอร์ที่ gate ด้วย "ข้อมูลของวันนี้ต้องพร้อม" ต้องมียามเฝ้าเสมอ
//
//    --heal  = ขาดบทความ → สั่ง horoscope:generate-daily ซ้ำให้เอง (idempotent,
//              ตัว command ข้ามวันที่มีอยู่แล้ว ไม่เผา AI ซ้ำ)
//    --alert = แจ้งแอดมินทาง LINE OA + Log::error (ต่อให้ heal สำเร็จก็ยังแจ้ง
//              เพื่อให้รู้ว่ารอบ 00:01 มีปัญหา ไม่ใช่เงียบแล้วซ่อมเงียบ)
//
// 🕐 00:20 = หลังรอบ 00:01 เสร็จแน่ ๆ (รอบจริงใช้ ~1-4 นาที)
// 🕕 06:00 = ตาข่ายชั้นสอง เผื่อ 00:20 ก็ยังล้ม (เช่น AI key หมดโควตาช่วงดึก)
foreach (['00:20', '06:00'] as $preflightAt) {
    Schedule::command('fortune:daily-preflight --heal --alert')
        ->dailyAt($preflightAt)
        ->timezone('Asia/Bangkok')
        ->withoutOverlapping(20)
        ->onOneServer()
        ->name('fortune-daily-preflight-'.str_replace(':', '', $preflightAt))
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/horoscope-daily.log'));
}

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
// 🌸 (2026-08-04) ความจำบทสนทนาของน้อง Eve — เพดานเก็บ 7 วัน
// ════════════════════════════════════════════════════════════════
// ลบแถวที่เกิน 7 วัน + ย่อเทิร์นเก่าของแถวที่ยาวเกิน (กัน prompt บวมตามอายุ)
// ผู้ที่ไม่ได้ล็อกอินไม่มีแถวในตารางนี้ตั้งแต่แรก จึงไม่มีอะไรให้กวาด
//
// ⚠️ ที่นี่ (ไม่ใช่ Kernel.php) เพราะ Laravel 11 ไม่ register Kernel schedule
// หมายเหตุ: EveAssistantController::loadMemberMemory() ลบแถวหมดอายุให้เองตอนอ่าน
//   ถ้า cron ตัวนี้ไม่ได้รัน = เปลืองพื้นที่เท่านั้น ความจำเก่าไม่มีทางหลุดเข้า prompt
Schedule::command('eve:memory-maintain --limit=500')
    ->dailyAt('03:20')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->name('eve-memory-maintain')
    ->runInBackground();

// ════════════════════════════════════════════════════════════════
// 🪪 (2026-08-19) กวาดบิลที่ชื่อลูกค้าหาย — ตาข่ายชั้นสุดท้าย
// ════════════════════════════════════════════════════════════════
// ที่มา: บิล FTU-260819-Z4534 แอดมินเปิดแล้วไม่เห็นชื่อ เพราะ Graph `/{PSID}` คืน 400
//   เฉพาะบางบัญชี (prod: 4.2% ของบิล FB) — ไม่ใช่ token พัง ไม่ใช่ App Review
//   FacebookWebhookService กู้ให้สดตอนคุยแล้วผ่าน conversations API
//
// ทำไมยังต้องมี cron ซ้ำอีก: ตอนลูกค้าทักครั้งแรกสุด "เธรดแชท" อาจยังไม่ถูกสร้าง
//   → conversations API ก็ยังหาไม่เจอในวินาทีนั้น และ negative cache กันยิงซ้ำอีก 3 ชม.
//   รอบนี้จึงตามเก็บคนที่ตกหล่นให้ครบภายใน 1 ชม. (ไม่ต้องรอลูกค้าทักใหม่)
//
// ⚠️ limit ต่ำไว้ (30/รอบ) + --sleep คั่น — conversations API กินโควต้า rate limit มากกว่า profile API
Schedule::command('fortune:backfill-fb-names --limit=30 --days=14 --paid-first --sleep=500')
    ->hourly()
    ->withoutOverlapping(20)
    ->onOneServer()
    ->name('fortune-backfill-fb-names');

// ════════════════════════════════════════════════════════════════
// ⚠️ DROPPED (commands ไม่อยู่ใน artisan list)
//   - snake-game:spawn-items     — command file ไม่พบ
//   - line:cleanup-conversations — command file ไม่พบ
// ถ้าต้องการกลับมา → restore command file ก่อน แล้วเพิ่ม Schedule ที่นี่
// ════════════════════════════════════════════════════════════════
