<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Services\CelticCrossService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Models\FortuneTellingSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🔄 Admin reset Celtic Cross reading
 *
 * Use case: ลูกค้าจ่ายแล้วแต่ flow ไม่สมบูรณ์ (เช่น ระบบบัค, network drop)
 *           → admin ต้องการให้ลูกค้าเริ่มดูใหม่ตั้งแต่เลือกไพ่
 *
 * Usage:
 *   php artisan fortune:celtic-reset 1211
 *   php artisan fortune:celtic-reset 1211 --no-notify  (reset เฉยๆ ไม่ส่งแจ้ง)
 *
 * Action:
 * - ล้าง celtic_cards (ไพ่ที่เลือกไว้)
 * - ล้าง celtic_questions (Q&A ทั้งหมด)
 * - reset celtic_questions_used = 0
 * - set conversation_status → CELTIC_PICKING
 * - ส่ง DM แจ้งลูกค้าให้กดเปิดไพ่ใบที่ 1 ใหม่
 */
class FortuneCelticReset extends Command
{
    protected $signature = 'fortune:celtic-reset
                            {id : Reading ID ที่จะ reset}
                            {--no-notify : ไม่ส่ง DM แจ้งลูกค้า}';

    protected $description = 'Reset Celtic Cross reading ให้ลูกค้าเริ่มเปิดไพ่ใหม่ (ใช้เมื่อ flow ไม่สมบูรณ์)';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $noNotify = (bool) $this->option('no-notify');

        $reading = FortuneReading::find($id);
        if (! $reading) {
            $this->error("ไม่พบ reading #{$id}");

            return 1;
        }

        if ($reading->reading_type !== FortuneReading::READING_TYPE_CELTIC_CROSS) {
            $this->error("Reading #{$id} ไม่ใช่ Celtic Cross (type: {$reading->reading_type})");

            return 1;
        }

        if (! $reading->is_paid) {
            $this->error("Reading #{$id} ยังไม่ได้ชำระเงิน — reset ไม่ได้");

            return 1;
        }

        $this->info("🔍 Reading #{$id}:");
        $this->table(
            ['Field', 'Value'],
            [
                ['User', $reading->facebook_user_name ?? '-'],
                ['Status', $reading->conversation_status],
                ['Picked', $reading->getCelticPickedCount() . '/10'],
                ['Q used', $reading->celtic_questions_used . ''],
                ['Updated', $reading->updated_at?->diffForHumans()],
            ]
        );

        if (! $this->confirm('ยืนยัน reset reading นี้?', true)) {
            $this->warn('ยกเลิก');

            return 0;
        }

        try {
            // 1. ล้างไพ่ที่เลือก
            try {
                app(CelticCrossService::class)->resetPickedCards($reading);
            } catch (\Throwable $e) {
                // ถ้า resetPickedCards ไม่อนุญาต → ทำ manual
                $reading->setConversationState('celtic_cards', []);
            }

            // 2. ล้าง Q&A
            $reading->celticQuestions()->delete();

            // 3. Reset counters + status
            $reading->update([
                'celtic_questions_used' => 0,
                'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
            ]);

            // 4. ล้าง state อื่นที่อาจค้าง
            $reading->setConversationState('reading_sent_directly', false);
            $reading->setConversationState('celtic_qa_started_at', null);
            // 🆕 (2026-05-17) Console reset → คืนโควต้า "สับใหม่" ให้ลูกค้า
            $reading->setConversationState('celtic_shuffle_count', 0);

            $reading->refresh();

            $this->info("✅ Reset reading #{$id} สำเร็จ");
            $this->info("   ปัจจุบัน: status=celtic_picking, picked=0/10, q_used=0");

            // 5. แจ้งลูกค้า
            if (! $noNotify) {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->platform_user_id ?? $reading->facebook_user_id) ? 'line' : 'facebook');
                $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

                if (! empty($userId)) {
                    $settings = FortuneTellingSetting::getSettings();
                    $conversationService = new FortuneConversationService($settings);
                    $channelManager = new FortuneChannelManager($settings);

                    // 🆕 (2026-05-04) ใช้ buildCelticResumeResponse(fromAdminReset=true)
                    //   หัวข้อจะแจ้งชัดเจนว่า "แอดมินรีเซ็ตให้แล้ว — ใช้ค่าครูเดิม ไม่ต้องจ่ายซ้ำ"
                    //   หาก reading state ไม่ใช่ CELTIC_PICKING (เพราะ reset เพิ่ง force ไปแล้ว)
                    //   ก็ยังทำงานถูก — switch case ใน helper รองรับ
                    $response = $conversationService->buildCelticResumeResponse($reading->fresh(), true);

                    $sent = $channelManager->sendResponse($platform, $userId, $response, [
                        'from_admin' => true,
                        'message_tag' => 'POST_PURCHASE_UPDATE',
                    ]);

                    if ($sent) {
                        $this->info("📤 แจ้งลูกค้าผ่าน {$platform} สำเร็จ");
                    } else {
                        $this->warn("⚠️ ส่ง DM ไม่สำเร็จ — ลูกค้าจะเห็นเมื่อทักกลับ (resume guard ใน startCelticCrossFlow ก็ครอบคลุม)");
                    }
                }
            }

            Log::info('Celtic admin reset success', [
                'reading_id' => $id,
                'user_id' => $reading->facebook_user_id,
                'no_notify' => $noNotify,
            ]);

            return 0;
        } catch (\Throwable $e) {
            $this->error("❌ Reset ล้มเหลว: {$e->getMessage()}");
            Log::error('Celtic admin reset failed', [
                'reading_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }
}
