<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneLocaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🤝 (2026-08-29 FTU-260829-M9469) ปิดช่วง "คุยต่อหลังบทสรุป" ของ Celtic 99 — กล่าวลา + อวยพร
 *
 * สเปกเจ้าของ 2026-08-29:
 *   "บทสรุปยัง 15 นาทีหรือตามค่าที่ตั้งเหมือนเดิม เพียงแต่ยังคุยต่อได้ ในเรื่องการทำนายรอบเดียวกัน
 *    ไม่เกิน 30 นาที จากคำถามแรก หรือลูกค้ามีสัญญาณวางสายเอง เช่นขอบคุณ บอทก็กล่าวลาและอวยพร
 *    เพื่อความประทับใจที่สุด"
 *
 * ทางออกจากช่วงคุยต่อมี 3 ทาง — command นี้รับผิดชอบ 2 ทางที่ลูกค้าไม่ได้พิมพ์อะไร:
 *   1. ลูกค้าลาเอง ("ขอบคุณ")   → ดักที่ webhook (handleCelticAftercareMessage) ไม่ใช่ที่นี่
 *   2. เงียบเกิน idle (10 นาที)  → ✅ ตัวนี้
 *   3. ครบเพดานรวม (30 นาที)     → ✅ ตัวนี้
 *
 * ⚠️ ไม่ generate Grand Finale ซ้ำ — ลูกค้าได้บทสรุปไปแล้วตอนนาทีที่ 15
 *    (บิลจริงใช้ 32,376 tokens ต่อบทสรุป 1 ครั้ง) ตรงนี้เป็นคำอวยพรสคริปต์ ไม่ผ่าน AI
 *
 * Schedule: ทุก 2 นาที (routes/console.php) — ถี่กว่า prosession-clear-stale (10 นาที)
 *   เพื่อให้ได้กล่าวลาก่อนตัวกวาด flag เสมอ
 *
 * Usage:
 *   php artisan fortune:celtic-aftercare-close           # รันจริง
 *   php artisan fortune:celtic-aftercare-close --dry     # dry run
 *   php artisan fortune:celtic-aftercare-close --id=123  # เฉพาะ reading (admin recovery)
 */
class FortuneCelticAftercareClose extends Command
{
    protected $signature = 'fortune:celtic-aftercare-close
                            {--dry : Dry run — แสดงรายการที่จะปิด แต่ไม่ส่งจริง}
                            {--limit=50 : จำนวนสูงสุดต่อรอบ}
                            {--id= : process เฉพาะ reading ID (ข้ามการเช็คเวลา)}';

    protected $description = 'ปิดช่วงคุยต่อหลังบทสรุป Celtic 99 — กล่าวลา+อวยพร เมื่อเงียบครบ idle หรือครบเพดานรวม';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $limit = (int) $this->option('limit');
        $specificId = $this->option('id');

        $settings = FortuneTellingSetting::getSettings();

        if (! $settings->isCelticAftercareEnabled()) {
            $this->info('⏸  ช่วงคุยต่อหลังบทสรุปถูกปิดอยู่ (celtic_aftercare_enabled = false)');

            return 0;
        }

        $conversationService = new FortuneConversationService($settings);
        $channelManager = new FortuneChannelManager($settings);

        $candidates = $conversationService->findCelticAftercareToClose(
            $limit > 0 ? $limit : 50,
            $specificId ? (int) $specificId : null
        );

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่มีช่วงคุยต่อที่ต้องปิด');

            return 0;
        }

        $totalMin = $settings->getCelticAftercareTotalMinutes();

        $this->info("🔍 พบ {$candidates->count()} รายการที่ถึงเวลากล่าวลา:");

        if ($dry) {
            foreach ($candidates as $r) {
                $this->line("  #{$r->id} ({$r->bill_reference}) last_msg="
                    .($r->getConversationState('celtic_aftercare_last_msg_at') ?? '-'));
            }
            $this->warn('Dry run — ไม่ได้ปิดจริง');

            return 0;
        }

        $closed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($candidates as $reading) {
            try {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id ?? '') ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (empty($userId)) {
                    $this->warn("  #{$reading->id} skip — ไม่มี user_id");
                    $skipped++;

                    continue;
                }

                // 🛡️ idempotency — กัน 2 รอบ cron ชนกันแล้วลูกค้าได้คำอวยพร 2 กล่อง
                $fresh = $reading->fresh();
                if ((bool) $fresh->getConversationState('celtic_aftercare_farewelled', false)) {
                    $this->warn("  #{$reading->id} skip — กล่าวลาไปแล้ว");
                    $skipped++;

                    continue;
                }

                // เหตุผลปิด: ครบเพดานรวม vs เงียบนาน (ข้อความอวยพรต่างกันเล็กน้อย)
                $reason = ($fresh->celtic_first_answered_at
                    && $fresh->celtic_first_answered_at->copy()->addMinutes($totalMin)->isPast())
                    ? 'total_cap'
                    : 'idle';

                // 🌐 Restore locale ก่อน push (queue worker ไม่มี request context)
                try {
                    $storedLocale = FortuneLocaleService::getStored($platform, $userId)
                        ?? FortuneLocaleService::LOCALE_TH;
                    FortuneLocaleService::setCurrent($storedLocale);
                } catch (\Throwable $e) {
                    FortuneLocaleService::setCurrent(FortuneLocaleService::LOCALE_TH);
                }

                $response = $conversationService->closeCelticAftercarePublic($fresh, $reason);

                if ($response === null) {
                    $this->warn("  #{$reading->id} skip — ปิดไปแล้วระหว่างทาง");
                    $skipped++;

                    continue;
                }

                // Push ผ่าน POST_PURCHASE_UPDATE — ฟรีตาม FB policy (ลูกค้าจ่ายเงินแล้ว)
                $sent = $channelManager->sendResponse($platform, $userId, $response, [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                $closed++;

                if ($sent) {
                    $this->info("  ✅ #{$reading->id} กล่าวลา + push สำเร็จ ({$platform}, {$reason})");
                } else {
                    // Push fail (FB 24hr / LINE quota) — session ปิดแล้ว flag เคลียร์แล้ว
                    //   ไม่ retry: คำอวยพรไม่ใช่เนื้อหาที่ลูกค้าจ่ายเงินซื้อ (บทสรุปส่งไปแล้วตอน 15 นาที)
                    $this->warn("  ⚠️ #{$reading->id} push fail แต่ปิด session แล้ว ({$reason})");
                }

                Log::info('Celtic Aftercare Close: ปิดช่วงคุยต่อ', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'platform' => $platform,
                    'reason' => $reason,
                    'pushed' => $sent,
                ]);
            } catch (\Throwable $e) {
                $this->error("  ❌ #{$reading->id} exception: {$e->getMessage()}");
                $failed++;
                Log::error('Celtic Aftercare Close: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                    'trace' => mb_substr($e->getTraceAsString(), 0, 500),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: closed {$closed} | skipped {$skipped} | failed {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
