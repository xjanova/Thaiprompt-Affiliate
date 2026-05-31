<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 🧾 (2026-05-31) Verify Slip Fallback Job
 *
 * นัดทำงานหลังลูกค้าส่งสลิป (หน่วง default 60 วิ) — ถ้า SMS checker ยังไม่ตัดบิล
 * → เรียก SlipOK ตรวจสลิปที่เก็บไว้ → ถ้าผ่านก็ตัดบิล + push คำตอบ/เปิดไพ่
 *
 * ประหยัดโควตา: ถ้า SMS ตัดไปแล้ว (is_paid=true) → ไม่เรียก SlipOK
 *
 * หมายเหตุ: นี่เป็น best-effort timer — path "ลูกค้าพิมพ์/ถามเข้ามา" (on-ping) เป็น
 *   synchronous ใน webhook จึงทำงานแม้ queue worker จะมี backlog
 */
class VerifySlipFallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(public int $readingId) {}

    public function handle(): void
    {
        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            return;
        }

        // SMS ตัดไปก่อนแล้ว / ตรวจสลิปไปแล้ว → จบ (ไม่เปลืองโควตา SlipOK)
        if ($reading->is_paid || $reading->slipok_verified_at || empty($reading->slip_image_path)) {
            return;
        }

        try {
            $settings = FortuneTellingSetting::getSettings();
            $conversationService = new FortuneConversationService($settings);

            // หา platform + userId สำหรับ push
            $userId = $reading->facebook_user_id ?: ($reading->line_user_id ?: $reading->platform_user_id);
            if (empty($userId)) {
                return;
            }
            $platform = $reading->platform
                ?? (preg_match('/^U[0-9a-f]{32}$/i', (string) $userId) ? 'line' : 'facebook');

            $result = $conversationService->trySlipOkVerifyForReading($reading, $platform, $userId);

            if ($result !== null) {
                $channelManager = new FortuneChannelManager($settings);
                $channelManager->sendResponse($platform, $userId, $result, [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);

                Log::info('SlipOK fallback job: ส่งผลตรวจสลิป', [
                    'reading_id' => $reading->id,
                    'action' => $result['action'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('VerifySlipFallbackJob: ล้มเหลว (non-blocking)', [
                'reading_id' => $this->readingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
