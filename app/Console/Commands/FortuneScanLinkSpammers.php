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
                            {--min-days=1 : ต้องส่งสแปมในจำนวนวันต่างกันอย่างน้อยเท่าไร (ความถี่ — ใส่ 2 = ส่งหลายวัน)}
                            {--wl-min=5 : ราง 2 — คนที่ whitelist แล้วแต่ยังยิงลิงก์ กี่ครั้งถึงเข้าข่าย (0 = ปิดราง 2)}
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
        $minDays = max(1, (int) $this->option('min-days'));
        $wlMin = max(0, (int) $this->option('wl-min'));
        $limit = max(1, (int) $this->option('limit'));
        $execute = (bool) $this->option('execute');

        $this->info('🔎 สแกน contact ที่ส่งแต่ลิงก์/รูป (facebook) — min='.$min.' / days='.$days.' / min-days='.$minDays
            .' / wl-min='.($wlMin > 0 ? $wlMin : 'ปิด'));
        $this->newLine();

        // ราง 1 (เดิม): ไม่เคยคุยเลย ส่งแต่ลิงก์/รูป
        $candidates = FortuneContactSignal::query()
            ->forPlatform('facebook')
            ->suspects($min, $minDays)
            ->where('last_seen_at', '>=', now()->subDays($days))
            ->orderByDesc('link_image_count')
            ->limit($limit)
            ->get();

        // 🔁 ราง 2 (2026-08-08): เคยคุยจริง/ถูก whitelist แล้ว แต่ยังยิงลิงก์ข้ามวัน
        //   นับเฉพาะ "ลิงก์" ไม่นับรูป → ลูกค้าส่งสลิปซ้ำๆ ไม่มีวันเข้าเกณฑ์นี้
        $wlCandidates = collect();
        if ($wlMin > 0) {
            $wlCandidates = FortuneContactSignal::query()
                ->forPlatform('facebook')
                ->whitelistedSpammers($wlMin, $minDays)
                ->where('last_seen_at', '>=', now()->subDays($days))
                ->whereNotIn('id', $candidates->pluck('id')->all())
                ->orderByDesc('wl_link_count')
                ->limit($limit)
                ->get();
        }

        // จำไว้ว่าใครมาจากราง 2 (ใช้เขียนเหตุผลแบนให้ตรง — ห้าม setAttribute ใส่ model ตรงๆ
        // เพราะ ->update() ทีหลังจะพยายาม save คอลัมน์ที่ไม่มีจริง)
        $wlTrackIds = $wlCandidates->pluck('id')->flip();

        $candidates = $candidates->concat($wlCandidates);

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่พบผู้ต้องสงสัย');

            return self::SUCCESS;
        }

        $rows = [];
        $actionable = [];

        foreach ($candidates as $c) {
            // 🛡️ Safety net ชั้นสุดท้าย: เคยจ่ายเงิน / แจ้งโอน / ส่งสลิป → ข้าม (กันพลาดเด็ดขาด)
            $hasPaid = FortuneReading::where('facebook_user_id', $c->platform_user_id)
                ->where(function ($q) {
                    $q->where('is_paid', true)
                        ->orWhere('transfer_reported', true)
                        ->orWhereNotNull('paid_at')
                        ->orWhereNotNull('slip_received_at');
                })
                ->exists();

            $rows[] = [
                $c->id,
                $c->platform_user_id,
                mb_substr($c->display_name ?? '-', 0, 16),
                $c->link_image_count,
                $c->link_caption_count,
                $c->active_days,
                $c->wl_link_count.'/'.$c->wl_link_days.'ว',
                $c->real_text_count,
                $c->inbound_total,
                optional($c->last_seen_at)->format('m-d H:i'),
                $hasPaid ? '⚠️PAID' : (isset($wlTrackIds[$c->id]) ? 'spam(wl)' : 'spam'),
                mb_substr($c->last_sample ?? '', 0, 24),
            ];

            if (! $hasPaid) {
                $actionable[] = $c;
            }
        }

        $this->table(
            ['ID', 'PSID', 'ชื่อ', 'ลิงก์/รูป', 'caption', 'วัน', 'ลิงก์หลังwl', 'คุยจริง', 'รวม', 'ล่าสุด', 'สถานะ', 'ตัวอย่าง'],
            $rows
        );
        $this->newLine();
        $this->info('พบผู้ต้องสงสัย '.$candidates->count().' ราย / จะดำเนินการ '.count($actionable).' ราย');

        // log ไว้เสมอ (ให้ scheduled dry-run ทิ้งร่องรอยใน laravel.log ได้)
        Log::info('FortuneScanLinkSpammers: scan', [
            'execute' => $execute,
            'min' => $min,
            'days' => $days,
            'wl_min' => $wlMin,
            'suspects' => $candidates->count(),
            'suspects_wl_track' => $wlCandidates->count(),
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
        $bannedPsids = [];

        foreach ($actionable as $c) {
            $psid = $c->platform_user_id;

            // 🛡️ (2026-06-04) เก็บชื่อ FB ถ้ายังไม่มี (display_name = null) — ให้ ban record + audit เห็นว่าใคร
            //   ดึงเฉพาะตอนจะแบน (suspects ไม่กี่คน/วัน) — ไม่ยิง Graph API ใน flow ข้อความปกติ
            if (empty($c->display_name)) {
                try {
                    $prof = $fbService->getUserProfile($psid);
                    $name = is_array($prof) ? ($prof['name'] ?? null) : null;
                    if (! empty($name)) {
                        $c->display_name = $name;
                    }
                } catch (\Throwable $e) {
                    // non-blocking — ไม่มีชื่อก็แบนต่อได้
                }
            }

            $reason = isset($wlTrackIds[$c->id])
                ? 'auto: ยิงลิงก์ต่อเนื่องหลังเคยคุย ('.$c->wl_link_count.' ครั้ง / '.$c->wl_link_days.' วัน)'
                : 'auto: ส่งแต่ลิงก์/รูป ไม่เคยพิมพ์คุย ('.$c->link_image_count.' ครั้ง)';

            // 1) บอทเลิกคุย (ถาวร) — ใช้ระบบ ban เดิม
            $banService->ban('facebook', $psid, null, $reason, null, $c->display_name);
            $banned++;
            $bannedPsids[] = $psid;

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
            'psids' => $bannedPsids,
        ]);

        return self::SUCCESS;
    }
}
