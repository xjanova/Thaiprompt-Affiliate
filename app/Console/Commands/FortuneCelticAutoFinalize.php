<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\CelticCrossService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Services\FortuneLocaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🌟 (2026-05-05) Auto-finalize Celtic sessions ที่หมดเวลาคุย — push Grand Finale ให้ลูกค้า
 *
 * User spec 2026-05-05:
 *   "หากยังถามไม่ครบแต่ยุติลงก่อน...หลุดหมดเวลาคุย ให้เข้าโฟลว์บทสรุปเอง
 *    และส่งคำทำนายสุดท้ายไปให้อัตโนมัติ เมื่อหมดเวลา"
 *
 * เคสที่ command นี้จัดการ (proactive finalization):
 *   - Celtic reading ใน CELTIC_AWAITING_QUESTION/CELTIC_QA_PROMPT
 *   - QA window หมดอายุ (canAskMoreCeltic() = false)
 *   - มี celtic_questions_used >= 1 (ลูกค้าได้ถามอย่างน้อย 1 คำถาม)
 *   - ยังไม่ได้ส่ง grand_finale (conversation_state.celtic_grand_finale_at IS NULL)
 *
 * Action:
 *   1. เรียก endCelticSession($reading, 'time_expired') → generate Grand Finale
 *   2. push closing message ผ่าน FortuneChannelManager (POST_PURCHASE_UPDATE message_tag)
 *   3. mark conversation_state.celtic_grand_finale_at เพื่อ idempotency
 *
 * Schedule: every 5 minutes (registered ใน Kernel.php)
 *
 * Usage:
 *   php artisan fortune:celtic-auto-finalize          # รันจริง
 *   php artisan fortune:celtic-auto-finalize --dry    # dry run
 *   php artisan fortune:celtic-auto-finalize --limit=10
 */
class FortuneCelticAutoFinalize extends Command
{
    protected $signature = 'fortune:celtic-auto-finalize
                            {--dry : Dry run — แสดง list ที่จะ process แต่ไม่ส่งจริง}
                            {--limit=20 : จำนวนสูงสุดที่จะ process ต่อรอบ (กัน AI overload)}
                            {--id= : process เฉพาะ reading ID (admin manual recovery)}';

    protected $description = 'Auto-finalize Celtic sessions ที่หมดเวลา + push Grand Finale ให้ลูกค้าอัตโนมัติ';

    public function handle(): int
    {
        $dry = $this->option('dry');
        $limit = (int) $this->option('limit');
        $specificId = $this->option('id');

        $candidates = $this->findExpiredCelticSessions($specificId, $limit);

        if ($candidates->isEmpty()) {
            $this->info('✅ ไม่มี Celtic session ที่ต้อง auto-finalize');
            return 0;
        }

        // 🩹 (2026-05-24) Fix: ดึง max_q เป็น local var ไม่ attach กับ model
        //   เดิม: $r->settings_max_q = ... → Eloquent dirty-track → save SQL ERROR
        //         (Column not found: 'settings_max_q') → cron ค้างทุกรอบ ลูกค้าไม่ได้ Grand Finale
        $maxQRawDisplay = (int) (FortuneTellingSetting::getSettings()->celtic_cross_max_questions ?? 0);
        $maxQDisplay = $maxQRawDisplay > 0 ? (string) $maxQRawDisplay : '∞'; // (2026-06-07) 0 = ไม่จำกัด

        $this->info("🔍 พบ {$candidates->count()} session ที่จะ finalize:");
        $this->table(
            ['ID', 'User', 'Status', 'Q used', 'Q1 answered', 'Updated'],
            $candidates->map(fn ($r) => [
                $r->id,
                ($r->facebook_user_name ?? '-').' ('.($r->platform ?? '?').')',
                $r->conversation_status,
                ($r->celtic_questions_used ?? 0).'/'.$maxQDisplay,
                $r->celtic_first_answered_at?->diffForHumans() ?? '?',
                $r->updated_at?->diffForHumans() ?? '?',
            ])->toArray()
        );

        if ($dry) {
            $this->warn('Dry run — ไม่ได้ finalize จริง');
            return 0;
        }

        $settings = FortuneTellingSetting::getSettings();
        $conversationService = new FortuneConversationService($settings);
        $channelManager = new FortuneChannelManager($settings);

        $finalized = 0;
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

                // 🛡️ Double-check idempotency — กัน double-finalize ถ้า command รันคู่กัน
                $freshReading = $reading->fresh();
                if ($freshReading->getConversationState('celtic_grand_finale_at')) {
                    $this->warn("  #{$reading->id} skip — finalize ไปแล้ว");
                    $skipped++;
                    continue;
                }

                // 🛡️ (2026-05-05 review) Retry guard — กัน AI cost ระเบิดถ้า Grand Finale fail ซ้ำ
                //   เคสจริง: AI down 1 ชม → command รันทุก 5 นาที = 12 fail/ชม × cost
                //   Logic: ถ้า fail >= 3 ครั้ง → skip ตลอด (admin ตรวจ + reset flag เอง)
                $finaleFailCount = (int) $freshReading->getConversationState('celtic_grand_finale_fail_count', 0);
                if ($finaleFailCount >= 3) {
                    $this->warn("  #{$reading->id} skip — finalize fail >= 3 ครั้ง (admin ตรวจ + reset flag)");
                    $skipped++;
                    continue;
                }

                // 🌐 Restore locale ก่อน push (queue worker ไม่มี request context)
                try {
                    $storedLocale = FortuneLocaleService::getStored($platform, $userId)
                        ?? FortuneLocaleService::LOCALE_TH;
                    FortuneLocaleService::setCurrent($storedLocale);
                } catch (\Throwable $e) {
                    FortuneLocaleService::setCurrent(FortuneLocaleService::LOCALE_TH);
                }

                // เรียก endCelticSession — ครั้งนี้ Grand Finale generate ทุกครั้ง (per 2026-05-05 fix)
                $response = $conversationService->endCelticSession($reading, 'time_expired');

                // 🛡️ (2026-05-05 review) ถ้า Grand Finale generate ไม่สำเร็จ → นับ fail
                //   เพื่อ retry guard skip หลังครบ 3 ครั้ง — กัน AI cost ระเบิด
                if (empty($response['has_grand_finale'])) {
                    $reading->setConversationState('celtic_grand_finale_fail_count', $finaleFailCount + 1);
                    $reading->setConversationState('celtic_grand_finale_last_fail_at', now()->toIso8601String());
                }

                // Push ผ่าน POST_PURCHASE_UPDATE — ฟรีตาม FB policy
                $sent = $channelManager->sendResponse($platform, $userId, $response, [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                if ($sent) {
                    $this->info("  ✅ #{$reading->id} finalize + push สำเร็จ ({$platform})");
                    $finalized++;
                    Log::info('Celtic Auto-Finalize: push Grand Finale สำเร็จ', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                        'has_grand_finale' => $response['has_grand_finale'] ?? false,
                    ]);
                } else {
                    // Push fail (FB 24hr expired / LINE quota)
                    //   → endCelticSession ทำงานแล้ว (state=COMPLETED + Grand Finale บันทึกใน DB)
                    //   → ลูกค้ากลับมาทัก → handleViewLastReading จะแสดง summary จาก conversation_state
                    $this->warn("  ⚠️ #{$reading->id} push fail (state ปิดแล้ว, summary บันทึกใน DB)");
                    $finalized++;
                    Log::warning('Celtic Auto-Finalize: push fail แต่ session ปิดแล้ว', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->error("  ❌ #{$reading->id} exception: {$e->getMessage()}");
                $failed++;
                Log::error('Celtic Auto-Finalize: exception', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                    'trace' => mb_substr($e->getTraceAsString(), 0, 500),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: finalized {$finalized} | skipped {$skipped} | failed {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    /**
     * หา Celtic readings ที่หมดเวลา QA + ยังไม่ finalize
     */
    protected function findExpiredCelticSessions(?string $specificId, int $limit): \Illuminate\Support\Collection
    {
        $settings = FortuneTellingSetting::getSettings();
        $qaWindow = (int) ($settings->celtic_cross_qa_window_minutes ?? 15);
        $qaCutoff = now()->subMinutes($qaWindow);

        $query = FortuneReading::query()
            ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('is_paid', true)
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                FortuneReading::STATUS_CELTIC_QA_PROMPT,
            ])
            // ลูกค้าได้ถามอย่างน้อย 1 ข้อ (ไม่ใช่หายไปทันทีหลังเปิดไพ่)
            ->where('celtic_questions_used', '>=', 1)
            // celtic_first_answered_at + qa_window < now → QA window หมดอายุ
            //   ใช้ celtic_first_answered_at (มี value แน่นอนเมื่อ celtic_questions_used >= 1)
            //   แทน updated_at — แม่นยำกว่า เพราะ updated_at อาจเปลี่ยนจาก setConversationState
            ->whereNotNull('celtic_first_answered_at')
            ->where('celtic_first_answered_at', '<=', $qaCutoff);

        if ($specificId) {
            $query->where('id', (int) $specificId);
        } else {
            // กรอง readings ที่ finalize ไปแล้ว (ใช้ JSON path)
            $query->where(function ($q) {
                $q->whereNull('conversation_state')
                    ->orWhere(function ($q2) {
                        $q2->whereRaw("JSON_EXTRACT(conversation_state, '$.celtic_grand_finale_at') IS NULL");
                    });
            });
            $query->limit($limit);
        }

        $readings = $query->orderBy('updated_at', 'asc')->get();

        // 🩹 (2026-05-24) ลบ block "$r->settings_max_q = ..." — ทำให้ Eloquent save SQL ERROR
        //   max_q สำหรับ display อยู่ใน handle() แล้ว (local var $maxQDisplay)

        return $readings;
    }
}
