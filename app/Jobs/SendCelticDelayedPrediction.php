<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 🆕 (2026-05-17) SendCelticDelayedPrediction
 *
 * ส่งคำทำนาย Celtic 99฿ แบบ delay 60-120 วินาที ให้ลูกค้ารู้สึกเหมือนหมอจันทรากำลังพิมพ์เอง
 *
 * Why:
 *   user spec: "ในการดูดวงแบบ 99 บาท ช่วงทำนาย ให้เอไอมีการเว้นระยะการตอบ 1-2 นาทีให้เหมือนคนพิมพ์"
 *   AI ตอบไว 30-60s → ส่งทันทีดูเหมือนเครื่องตอบ → ไม่มีความขลัง
 *   ใช้ Queue delay (Laravel native) → ไม่ block worker
 *
 * Flow:
 *   1. Trait ดับช่วง askQuestion เสร็จ → dispatch Job นี้พร้อม delay random 60-120s
 *      + ส่ง typing_on indicator ทันที (FB only — LINE OA ไม่รองรับ)
 *      + เก็บ status = CELTIC_GENERATING (ไม่กลับ AWAITING) → กัน user ถามใหม่ทับ
 *   2. Job รันหลัง delay:
 *      - update status = CELTIC_AWAITING_QUESTION
 *      - ส่ง typing_off (FB)
 *      - ส่งคำทำนายจริง พร้อม quick reply "🛑 ยุติการทำนาย"
 */
class SendCelticDelayedPrediction implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Retry max 2 ครั้ง — message delivery ไม่ควร retry นานเกินไป (ลูกค้ารอ)
     */
    public int $tries = 2;

    /**
     * Timeout 60s — job ตัวเองส่ง message ใช้เวลาไม่กี่วินาที
     */
    public int $timeout = 60;

    public function __construct(
        public int $readingId,
        public string $message,
        public string $platform,
        public string $userId,
    ) {
    }

    public function handle(): void
    {
        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            Log::warning('SendCelticDelayedPrediction: ไม่พบ reading', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        // เปลี่ยน status กลับ AWAITING ก่อนส่งจริง — ปล่อยให้ user ถามใหม่ได้
        if ($reading->conversation_status === FortuneReading::STATUS_CELTIC_GENERATING) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
        }

        $settings = FortuneTellingSetting::getSettings();

        try {
            if ($this->platform === 'facebook') {
                $fb = new FacebookWebhookService($settings);

                // typing_off ก่อนส่งข้อความจริง
                $fb->sendTypingIndicator($this->userId, false);
                usleep(300000); // 300ms — กัน race

                // ส่งคำทำนาย + quick reply "ยุติการทำนาย"
                $fb->sendMessage($this->userId, $this->message, [
                    'quick_replies' => [
                        ['label' => '🛑 ยุติการทำนาย', 'text' => 'ยุติการทำนาย'],
                    ],
                ]);
            } else {
                // LINE
                $line = new LineFortuneService($settings);
                $line->sendMessage($this->userId, $this->message, [
                    'quick_replies' => [
                        ['label' => '🛑 ยุติการทำนาย', 'text' => 'ยุติการทำนาย'],
                    ],
                ]);
            }
        } catch (Throwable $e) {
            Log::error('SendCelticDelayedPrediction: ส่งล้มเหลว', [
                'reading_id' => $this->readingId,
                'platform' => $this->platform,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
