<?php

namespace App\Console\Commands;

use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneLocaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 (2026-06-23, owner) ตามให้ลูกค้า "เริ่มถามคำถาม" หลังระบบพร้อมให้ถาม
 *
 * owner spec 2026-06-23:
 *   "ถ้าลูกค้าถึงขั้นตอนให้ถามแต่ไม่มาถามภายใน 1 นาทีหลังระบบพร้อมให้ถาม
 *    ก็ส่งกล่องข้อความไปตามให้เริ่มถามคำถาม เวลาจะเริ่มจับหลังจากถาม ตามราคาแพคเกจ"
 *   + owner เลือก: "ตาม 1 ครั้ง แล้วค้างไว้" (ไม่ปิด session เอง ถ้ายังเงียบต่อ)
 *
 * เคสที่จัดการ (ทั้ง Deep 39 + Celtic 99):
 *   - Pro Session เปิดอยู่ (pro_session_active) + รอคำถามแรก (pro_session_awaiting_first_question)
 *   - พร้อมให้ถามจริงแล้ว (pro_session_ready_at) เกิน N วินาที (default 60)
 *   - ยังไม่เคยตาม (pro_session_nudge_sent = false)
 *   - ไม่อยู่ระหว่างรอวันเกิด Celtic (celtic_birthdate_pending)
 *
 * Action: push กล่อง "พิมพ์คำถามได้เลย" ผ่าน POST_PURCHASE_UPDATE (ฟรีตาม FB policy) + mark nudge_sent
 *
 * Schedule: ทุก 1 นาที (registered ใน routes/console.php)
 *
 * Usage:
 *   php artisan fortune:pro-session-nudge          # รันจริง
 *   php artisan fortune:pro-session-nudge --dry    # dry run
 *   php artisan fortune:pro-session-nudge --id=123 # เฉพาะ reading
 */
class FortuneProSessionNudge extends Command
{
    protected $signature = 'fortune:pro-session-nudge
                            {--dry : Dry run — แสดง list ที่จะตาม แต่ไม่ส่งจริง}
                            {--limit=30 : จำนวนสูงสุดต่อรอบ}
                            {--id= : process เฉพาะ reading ID}';

    protected $description = 'ตามให้ลูกค้าเริ่มถามคำถาม (Pro Session) ถ้าเงียบเกิน 1 นาทีหลังระบบพร้อมให้ถาม';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $limit = (int) $this->option('limit');
        $specificId = $this->option('id');

        $settings = FortuneTellingSetting::getSettings();
        $conversationService = new FortuneConversationService($settings);

        $candidates = $conversationService->getProSessionsAwaitingNudge(
            $limit > 0 ? $limit : 30,
            $specificId ? (int) $specificId : null
        );

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่มี Pro Session ที่ต้องตามถาม');

            return 0;
        }

        $this->info("🔔 พบ {$candidates->count()} session ที่จะตามถาม:");

        if ($dry) {
            foreach ($candidates as $r) {
                $type = (string) $r->getConversationState('pro_session_type', 'deep');
                $this->line("  #{$r->id} [{$type}] ready_at={$r->getConversationState('pro_session_ready_at')}");
            }
            $this->warn('Dry run — ไม่ได้ส่งจริง');

            return 0;
        }

        $channelManager = new FortuneChannelManager($settings);
        $sentCount = 0;
        $failed = 0;

        foreach ($candidates as $reading) {
            try {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id ?? '') ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (empty($userId)) {
                    $this->warn("  #{$reading->id} skip — ไม่มี user_id");

                    continue;
                }

                // 🛡️ double-check idempotency (กัน race ถ้า command รันคู่กัน)
                //   🔔 (2026-06-30) เลิกเช็ค pro_session_nudge_sent (ตอนนี้ตามซ้ำได้ทุก interval)
                //   — ระยะเว้น interval คุมโดย getProSessionsAwaitingNudge (isProSessionAwaitingNudge) แล้ว
                $fresh = $reading->fresh();
                if (! $fresh->getConversationState('pro_session_awaiting_first_question', false)
                    || $fresh->getConversationState('pro_session_pending_exit', false)) {
                    $this->warn("  #{$reading->id} skip — ลูกค้าถามแล้ว/กำลังขอออก");

                    continue;
                }

                // 🌐 restore locale ก่อน push (queue/cron ไม่มี request context)
                try {
                    $storedLocale = FortuneLocaleService::getStored($platform, $userId) ?? FortuneLocaleService::LOCALE_TH;
                    FortuneLocaleService::setCurrent($storedLocale);
                } catch (\Throwable $e) {
                    FortuneLocaleService::setCurrent(FortuneLocaleService::LOCALE_TH);
                }

                $response = $conversationService->buildProSessionNudgeMessage($reading);

                // mark "ตามแล้ว" ก่อน push — กันส่งซ้ำถ้า push retry/รันคู่ (owner: ตามครั้งเดียว)
                $conversationService->markProSessionNudgeSent($reading);

                $sent = $channelManager->sendResponse($platform, $userId, $response, [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                if ($sent) {
                    $this->info("  ✅ #{$reading->id} ตามถามสำเร็จ ({$platform})");
                    $sentCount++;
                    Log::info('Fortune ProSession Nudge: ตามถามสำเร็จ', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'type' => $reading->getConversationState('pro_session_type', 'deep'),
                    ]);
                } else {
                    // push fail (FB 24hr/LINE quota) — mark แล้ว ไม่ตามซ้ำ (ลูกค้ากลับมาเองได้)
                    $this->warn("  ⚠️ #{$reading->id} push fail (mark ตามแล้ว ไม่ส่งซ้ำ)");
                    Log::warning('Fortune ProSession Nudge: push fail', [
                        'reading_id' => $reading->id, 'platform' => $platform,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->error("  ❌ #{$reading->id} exception: {$e->getMessage()}");
                $failed++;
                Log::error('Fortune ProSession Nudge: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: ตามถาม {$sentCount} | failed {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
