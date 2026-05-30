<?php

namespace App\Console\Commands;

use App\Models\FortuneContactSignal;
use App\Models\FortuneReading;
use App\Services\FacebookWebhookService;
use App\Services\FortuneBanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ สแกนหา contact ที่ "ส่งแต่ลิงก์/รูป ไม่เคยพิมพ์คุยจริง" → แบน + block บน Facebook
 *
 * Usage:
 *   php artisan fortune:scan-link-spammers                  # dry-run (แค่แสดงรายชื่อ)
 *   php artisan fortune:scan-link-spammers --min=3 --days=7
 *   php artisan fortune:scan-link-spammers --execute        # แบนจริง + block บน FB Page
 *
 * --execute จะทำ 2 อย่างต่อ 1 คน:
 *   1) สร้าง FortuneUserBan → บอทเลิกคุยด้วย (ถาวร)
 *   2) เรียก Facebook /{page-id}/blocked → ห้ามส่ง DM + ห้ามคอมเมนต์/โพสบนเพจ
 *
 * 🛡️ กัน false-positive: ข้ามทุกคนที่ whitelist (เคยคุยจริง/เคยจ่าย) อัตโนมัติ
 *   + เช็คซ้ำว่าไม่มี paid reading ก่อนลงมือ (safety net ชั้นสุดท้าย)
 */
class FortuneScanLinkSpammers extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:scan-link-spammers
                            {--min=3 : จำนวนลิงก์/รูปขั้นต่ำที่ถือว่าเป็นสแปม}
                            {--days=7 : ดูเฉพาะที่ส่งข้อความภายในกี่วันล่าสุด}
                            {--limit=200 : จำกัดจำนวนรายการสูงสุด}
                            {--execute : ลงมือแบน + block บน FB จริง (default: dry-run)}';

    /**
     * @var string
     */
    protected $description = 'หา contact ที่ส่งแต่ลิงก์/รูป ไม่เคยคุย → แบน + block บน Facebook Page';

    /**
     * รันคำสั่ง
     */
    public function handle(FortuneBanService $banService, FacebookWebhookService $fbService): int
    {
        $min = max(1, (int) $this->option('min'));
        $days = max(1, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));
        $execute = (bool) $this->option('execute');

        $this->info('🔎 สแกน contact ที่ส่งแต่ลิงก์/รูป (facebook) — min='.$min.' / days='.$days);
        $this->newLine();

        $candidates = FortuneContactSignal::query()
            ->forPlatform('facebook')
            ->suspects($min)
            ->where('last_seen_at', '>=', now()->subDays($days))
            ->orderByDesc('link_image_count')
            ->limit($limit)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่พบผู้ต้องสงสัย');

            return self::SUCCESS;
        }

        $rows = [];
        $actionable = [];

        foreach ($candidates as $c) {
            // 🛡️ Safety net ชั้นสุดท้าย: ถ้าเคยมี paid reading → ข้าม (กันพลาดเด็ดขาด)
            $hasPaid = FortuneReading::where('facebook_user_id', $c->platform_user_id)
                ->where('is_paid', true)
                ->exists();

            $rows[] = [
                $c->id,
                $c->platform_user_id,
                mb_substr($c->display_name ?? '-', 0, 18),
                $c->link_image_count,
                $c->real_text_count,
                $c->inbound_total,
                optional($c->last_seen_at)->format('m-d H:i'),
                $hasPaid ? '⚠️ PAID-skip' : 'spam',
                mb_substr($c->last_sample ?? '', 0, 30),
            ];

            if (! $hasPaid) {
                $actionable[] = $c;
            }
        }

        $this->table(
            ['ID', 'PSID', 'ชื่อ', 'ลิงก์/รูป', 'คุยจริง', 'รวม', 'ล่าสุด', 'สถานะ', 'ตัวอย่าง'],
            $rows
        );
        $this->newLine();
        $this->info('พบผู้ต้องสงสัย '.$candidates->count().' ราย / จะดำเนินการ '.count($actionable).' ราย');

        // log ไว้เสมอ (ให้ scheduled dry-run ทิ้งร่องรอยใน laravel.log ได้)
        Log::info('FortuneScanLinkSpammers: scan', [
            'execute' => $execute,
            'min' => $min,
            'days' => $days,
            'suspects' => $candidates->count(),
            'actionable' => count($actionable),
            'psids' => collect($actionable)->pluck('platform_user_id')->all(),
        ]);

        if (! $execute) {
            $this->warn('🧪 DRY-RUN — ยังไม่แบนจริง. เพิ่ม --execute เพื่อแบน + block บน FB');

            return self::SUCCESS;
        }

        // ===== EXECUTE: แบน + block จริง =====
        $banned = 0;
        $blocked = 0;
        $blockFailed = 0;

        foreach ($actionable as $c) {
            $psid = $c->platform_user_id;
            $reason = 'auto: ส่งแต่ลิงก์/รูป ไม่เคยพิมพ์คุย ('.$c->link_image_count.' ครั้ง)';

            // 1) บอทเลิกคุย (ถาวร) — ใช้ระบบ ban เดิม
            $banService->ban('facebook', $psid, null, $reason, null, $c->display_name);
            $banned++;

            // 2) block บน FB Page จริง (ห้าม DM + ห้ามโพส/คอมเมนต์)
            if ($fbService->blockPageUser($psid)) {
                $blocked++;
            } else {
                $blockFailed++;
                $this->warn('  ⚠️ block FB ล้มเหลว PSID='.$psid.' ('.($fbService->lastFetchError ?? 'unknown').')');
            }

            $c->update([
                'status' => 'banned',
                'banned_at' => now(),
            ]);
        }

        $this->newLine();
        $this->info("✅ แบนบอท {$banned} ราย | block FB สำเร็จ {$blocked} ราย | block ล้มเหลว {$blockFailed} ราย");

        Log::warning('FortuneScanLinkSpammers: executed', [
            'banned' => $banned,
            'blocked' => $blocked,
            'block_failed' => $blockFailed,
        ]);

        return self::SUCCESS;
    }
}
