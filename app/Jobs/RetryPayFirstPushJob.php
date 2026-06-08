<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 🛟 (2026-05-13) Retry push "ขอวันเกิด" — async กัน webhook block
 *
 * เคสที่กัน:
 *   SMS webhook ตัดบิล 39฿ → ส่ง push "ขอวันเกิด" → FB return false/exception
 *   เดิม: sleep + retry 3 ครั้ง inline = block PHP-FPM worker 7 วินาที
 *   ใหม่: ลอง inline 1 ครั้ง → fail → dispatch job นี้ → retry async
 *
 * Job retry strategy:
 *   - $tries = 3 (retry max 3 ครั้ง)
 *   - $backoff = [10, 30] (10s, 30s — รวม 40 วินาทีก่อนยอมแพ้)
 *   - ถ้าทุก attempt fail → log + scheduler `deep-pay-first-auto-recovery` รับช่วง
 *
 * Idempotency:
 *   - เช็ค is_paid + status=collecting_birthdate + birth_date NULL ก่อน push
 *   - mark birthdate_resent_at หลังสำเร็จ — กัน scheduler ส่งซ้ำ
 */
class RetryPayFirstPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Exponential-ish backoff: 10s → 30s ระหว่าง retry
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function __construct(
        public int $readingId,
        public string $platform,
        public string $userId,
        public string $messageText,
        // 🔁 (2026-06-08) action ปลายทาง — default = ขอวันเกิด (เดิม) / reuse ส่ง 'awaiting_tarot_intention'
        public string $action = 'collecting_birthdate',
    ) {}

    public function handle(): void
    {
        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            Log::warning('RetryPayFirstPushJob: reading not found', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }

        // 🛡️ Idempotency — ลูกค้าอาจดำเนินการต่อไปแล้ว ระหว่าง job อยู่ใน queue
        $reading->refresh();
        if (! $reading->is_paid) {
            Log::info('RetryPayFirstPushJob: skip — ไม่ใช่บิลที่จ่ายแล้ว', [
                'reading_id' => $reading->id,
            ]);

            return;
        }

        // 🔁 (2026-06-08) reuse flow (มีวันเกิดเดิม → ข้ามไป COLLECTING_TAROT): birth_date ถูกตั้งโดยตั้งใจ
        //   → ต้อง guard ด้วย "ทำนายเสร็จ/จบแล้ว" แทน birth_date (ไม่งั้น push ยืนยันตัดบิลจะถูก skip)
        //   ส่วน flow ขอวันเกิดปกติ → guard ด้วย birth_date เดิม (ลูกค้าให้วันเกิดแล้ว = ไม่ต้อง push ซ้ำ)
        $isReuseFlow = $this->action !== 'collecting_birthdate';
        if ($isReuseFlow) {
            if (! empty($reading->deep_response)
                || $reading->conversation_status === FortuneReading::STATUS_COMPLETED) {
                Log::info('RetryPayFirstPushJob: skip (reuse) — reading เสร็จแล้ว', [
                    'reading_id' => $reading->id,
                ]);

                return;
            }
        } elseif (! empty($reading->birth_date)) {
            Log::info('RetryPayFirstPushJob: skip — ได้วันเกิดแล้ว', [
                'reading_id' => $reading->id,
            ]);

            return;
        }

        // 🛡️ ถ้า scheduler/manual resend ส่งไปแล้วใน 5 นาที — skip
        $lastResent = $reading->getConversationState('birthdate_resent_at');
        if ($lastResent) {
            try {
                $resentAt = \Carbon\Carbon::parse($lastResent);
                if ($resentAt->gt(now()->subMinutes(5))) {
                    Log::info('RetryPayFirstPushJob: skip — recent resend within 5 min', [
                        'reading_id' => $reading->id,
                        'last_resent_at' => $lastResent,
                    ]);

                    return;
                }
            } catch (\Throwable $e) {
                // parse fail → continue
            }
        }

        $settings = FortuneTellingSetting::getSettings();
        $channelManager = new FortuneChannelManager($settings);

        try {
            $pushSent = $channelManager->sendResponse($this->platform, $this->userId, [
                'action' => $this->action,
                'message' => $this->messageText,
                'reading' => $reading,
            ], [
                'from_admin' => true,
                'message_tag' => 'POST_PURCHASE_UPDATE',
            ]);

            if ($pushSent) {
                $reading->setConversationState('birthdate_resent_at', now()->toIso8601String());
                Log::info('RetryPayFirstPushJob: push สำเร็จ (async retry)', [
                    'reading_id' => $reading->id,
                    'attempt' => $this->attempts(),
                ]);

                return;
            }

            // คืน false — throw เพื่อให้ job retry ตาม backoff
            throw new \RuntimeException('sendResponse returned false');
        } catch (\Throwable $e) {
            Log::warning('RetryPayFirstPushJob: push fail — job จะ retry หรือ scheduler รับช่วง', [
                'reading_id' => $reading->id,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'error' => $e->getMessage(),
            ]);
            // Re-throw → Laravel queue จะ retry ตาม backoff()
            // ถ้าครบ tries แล้ว → failed job → scheduler `deep-pay-first-auto-recovery` รับช่วง
            throw $e;
        }
    }

    /**
     * เรียกเมื่อ job fail ครบทุก retry
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RetryPayFirstPushJob: fail ครบ tries — scheduler รับช่วงต่อ', [
            'reading_id' => $this->readingId,
            'platform' => $this->platform,
            'error' => $exception->getMessage(),
        ]);
    }
}
