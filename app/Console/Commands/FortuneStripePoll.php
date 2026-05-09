<?php

namespace App\Console\Commands;

use App\Services\Fortune\FortuneStripeService;
use Illuminate\Console\Command;

/**
 * 💳 fortune:stripe-poll — Polling fallback สำหรับ Stripe webhook (2026-05-09)
 *
 * Use case:
 *   - Stripe webhook ตก / network error / firewall block → บิลจ่ายแล้วลูกค้าไม่ได้คำทำนาย
 *   - Polling นี้ดึง session status จาก Stripe API → ถ้า paid → trigger flow เอง
 *
 * Schedule: every 5 min ใน routes/console.php
 *
 * Usage:
 *   php artisan fortune:stripe-poll              # ปกติ (max age 7200s = 2hr)
 *   php artisan fortune:stripe-poll --max-age=3600  # 1hr
 */
class FortuneStripePoll extends Command
{
    protected $signature = 'fortune:stripe-poll {--max-age=7200 : อายุสูงสุดของ session ที่จะ poll (วินาที)}';

    protected $description = 'Poll Stripe Checkout sessions ที่ pending → recover ถ้า webhook ตก';

    public function handle(): int
    {
        $maxAge = (int) $this->option('max-age');

        $service = new FortuneStripeService();
        if (! $service->isEnabled()) {
            $this->info('⚠️  Stripe payment ไม่ได้เปิดใช้งาน — skip poll');
            return self::SUCCESS;
        }

        $this->info("🔍 กำลัง poll Stripe sessions (max age {$maxAge}s)...");

        $stats = $service->pollPendingSessions($maxAge);

        $this->info("✅ Done — processed: {$stats['processed']}, paid: {$stats['paid']}, expired: {$stats['expired']}");

        return self::SUCCESS;
    }
}
