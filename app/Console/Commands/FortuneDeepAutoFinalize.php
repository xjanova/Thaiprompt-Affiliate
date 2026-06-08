<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneLocaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🌙 (2026-06-08) Auto-finalize Deep 39 Pro Sessions ที่หมดเวลาคุย — push ข้อความ "หมดเวลา" ให้ลูกค้า
 *
 * User spec 2026-06-08:
 *   "เพิ่มกล่องบอกเวลาที่เหลือของการทำนายใน 39 บาท เมื่อหมดเวลาก็ต้องบอกว่าหมดเวลาทำนาย
 *    ขอบคุณลูกค้า และพิมพ์อ่านคำทำนายย้อนหลังได้ แต่ไม่มีสรุป แบบ 99"
 *
 * ต่างจาก fortune:celtic-auto-finalize:
 *   - ไม่มี Grand Finale / บทสรุป (39 ไม่มีสรุปแบบ 99) — แค่ขอบคุณ + ชวนอ่านย้อนหลัง
 *   - ไม่เรียก AI — ข้อความ static (ประหยัด + เร็ว)
 *
 * Discriminator (timestamp-based ใน FortuneConversationService::isDeepProSessionTimedOutUnnotified):
 *   - reading_type = deep, is_paid = true
 *   - pro_session_started_at + window (deep_reading_qa_window_minutes/7) < now
 *   - pro_session_timeout_notified != true (idempotent)
 *   ไม่พึ่ง pro_session_active flag (isInProSession เคลียร์ทิ้งเมื่อหมดเวลา) → ครอบทั้งลูกค้าเงียบ + ที่พิมพ์ต่อ
 *
 * Schedule: every 3 minutes (routes/console.php) — window 7 นาที จึงแจ้งภายใน ~3 นาทีหลังหมดเวลา
 *
 * Usage:
 *   php artisan fortune:deep-auto-finalize          # รันจริง
 *   php artisan fortune:deep-auto-finalize --dry     # dry run
 *   php artisan fortune:deep-auto-finalize --limit=10
 */
class FortuneDeepAutoFinalize extends Command
{
    protected $signature = 'fortune:deep-auto-finalize
                            {--dry : Dry run — แสดง list ที่จะ process แต่ไม่ส่งจริง}
                            {--limit=30 : จำนวนสูงสุดที่จะ process ต่อรอบ}
                            {--id= : process เฉพาะ reading ID (admin manual)}';

    protected $description = 'แจ้ง "หมดเวลาทำนาย" ให้ลูกค้า Deep 39 ที่หมดช่วงคุยกับแม่หมอ (ไม่มีบทสรุปแบบ 99)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $limit = (int) $this->option('limit');
        $specificId = $this->option('id');

        $settings = FortuneTellingSetting::getSettings();
        $conversationService = new FortuneConversationService($settings);

        $candidates = $conversationService->getTimedOutDeepProSessions($limit, $specificId ? (int) $specificId : null);

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่มี Deep 39 session ที่ต้องแจ้งหมดเวลา');

            return 0;
        }

        $this->info("🔍 พบ {$candidates->count()} session ที่จะแจ้งหมดเวลา:");
        $this->table(
            ['ID', 'User', 'Status', 'Paid'],
            $candidates->map(fn ($r) => [
                $r->id,
                ($r->facebook_user_name ?? '-').' ('.($r->platform ?? '?').')',
                $r->conversation_status,
                $r->paid_at?->diffForHumans() ?? '?',
            ])->toArray()
        );

        if ($dry) {
            $this->warn('Dry run — ไม่ได้ส่งจริง');

            return 0;
        }

        $channelManager = new FortuneChannelManager($settings);
        $notified = 0;
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

                // 🌐 Restore locale ก่อน push (queue/cron ไม่มี request context)
                try {
                    $storedLocale = FortuneLocaleService::getStored($platform, $userId)
                        ?? FortuneLocaleService::LOCALE_TH;
                    FortuneLocaleService::setCurrent($storedLocale);
                } catch (\Throwable $e) {
                    FortuneLocaleService::setCurrent(FortuneLocaleService::LOCALE_TH);
                }

                // สร้างข้อความ + mark notified (idempotent) — ไม่มีบทสรุป
                $response = $conversationService->finalizeDeepProSessionTimeout($reading);

                $sent = $channelManager->sendResponse($platform, $userId, $response, [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                if ($sent) {
                    $this->info("  ✅ #{$reading->id} แจ้งหมดเวลา + push สำเร็จ ({$platform})");
                } else {
                    // push fail (FB 24hr / LINE quota) — flag ถูก mark แล้ว ลูกค้ายังพิมพ์ "อ่านคำทำนายล่าสุด" ได้
                    $this->warn("  ⚠️ #{$reading->id} push fail (mark notified แล้ว)");
                }
                $notified++;
            } catch (\Throwable $e) {
                $this->error("  ❌ #{$reading->id} exception: {$e->getMessage()}");
                $failed++;
                Log::error('Deep Auto-Finalize: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                    'trace' => mb_substr($e->getTraceAsString(), 0, 500),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: notified {$notified} | skipped {$skipped} | failed {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
