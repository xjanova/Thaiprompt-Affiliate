<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTakeoverLog;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\LineAlertService;
use App\Services\LineFortuneService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * จัดการ recovery หลัง AI ดูดวงล้มเหลวถาวร
 *
 * 🚨 (2026-05-09) Extracted จาก ProcessDeepFortuneReadingJob::failed() เพราะ
 *    `dispatchSmart()` priority 1 = `fastcgi_finish_request + Artisan::call('fortune:process-deep')`
 *    บน production php-fpm — Job::handle() ไม่ถูกเรียก → Job::failed() ไม่ run →
 *    C5 fix (2026-05-03 #2) ทั้งชุด (admin alert + customer notify + gen_processing clear)
 *    ไม่ทำงาน. ลูกค้าจ่ายเงินแล้ว AI ตาย → เงียบ 5 นาที → ไม่มีใครรู้
 *
 *    Service นี้ idempotent ผ่าน flag `ai_failed_alert` ใน reading conversation_state
 *    (กัน Job::failed + Artisan command catch ยิงซ้ำในกรณี race rare)
 *
 * Recovery 4 ขั้นตอน:
 *   1. Cache::forget gen_processing/gen_announce — กัน customer ติด silent skip 5 นาที
 *   2. STATUS_COMPLETED — กันบิลค้างที่ paid
 *   3. Alert admin — LINE OA + FortuneTakeoverLog row + ai_failed_alert flag
 *   4. Push customer notification — กัน trust loss (ลูกค้าจ่ายแล้ว ต้องรู้ว่ามีคนช่วย)
 */
class FortuneAiFailureRecovery
{
    /**
     * Run recovery หลัง AI ล้มเหลว (idempotent ผ่าน ai_failed_alert flag)
     *
     * @param  int  $readingId  FortuneReading id
     * @param  string  $platform  'line' | 'facebook'
     * @param  string|null  $userId  Platform user id (PSID/LINE userId)
     * @param  Throwable  $exception  AI exception
     * @param  int  $attempts  retry count (1 = first attempt fail, ใช้แค่ใน log/alert)
     */
    public function handle(int $readingId, string $platform, ?string $userId, Throwable $exception, int $attempts = 1): void
    {
        // Idempotency: ถ้า alert ส่งแล้วในบิลนี้ ข้าม (กัน race ระหว่าง Job::failed + Artisan command)
        $reading = FortuneReading::find($readingId);
        if ($reading && $reading->getConversationState('ai_failed_alert', false)) {
            Log::info('FortuneAiFailureRecovery: ข้าม — alert ส่งไปแล้ว', [
                'reading_id' => $readingId,
                'attempts' => $attempts,
            ]);

            return;
        }

        // 1. Clear Quiet Period flag — ลูกค้าจะได้พิมพ์ติดต่อแอดมินได้
        $this->clearGenProcessingCache($userId);

        // 2. Set status COMPLETED (กันบิลค้าง)
        $this->markCompleted($readingId);

        // 3. Alert admin (LINE + takeover log + ai_failed_alert flag)
        $this->alertAdmin($readingId, $platform, $userId, $exception, $attempts);

        // 4. Push customer notification
        $this->pushCustomerNotification($readingId, $platform, $userId);
    }

    /**
     * Clear gen_processing cache — เลิก silent skip 5 นาที
     */
    protected function clearGenProcessingCache(?string $userId): void
    {
        if (empty($userId)) {
            return;
        }

        try {
            Cache::forget("fortune:gen_processing:{$userId}");
            Cache::forget("fortune:gen_announce:{$userId}");
        } catch (Throwable $e) {
            // non-blocking
        }
    }

    /**
     * Set status COMPLETED — กันบิลค้างที่ paid
     */
    protected function markCompleted(int $readingId): void
    {
        try {
            $reading = FortuneReading::find($readingId);
            if ($reading && $reading->conversation_status !== FortuneReading::STATUS_COMPLETED) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            }
        } catch (Throwable $e) {
            Log::error('FortuneAiFailureRecovery: mark completed ล้มเหลว', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Alert admin — LINE OA + FortuneTakeoverLog + ai_failed_alert flag
     */
    protected function alertAdmin(int $readingId, string $platform, ?string $userId, Throwable $exception, int $attempts): void
    {
        // 1. LINE alert (best-effort)
        try {
            app(LineAlertService::class)->alertSystemError(
                'AI ดูดวงล้มเหลวถาวร — ลูกค้าจ่ายเงินแล้ว ต้อง recover ด่วน',
                [
                    'reading_id' => $readingId,
                    'platform' => $platform,
                    'user_id' => $userId,
                    'error' => mb_substr($exception->getMessage(), 0, 200),
                    'attempts' => $attempts,
                    'admin_action' => 'ไปที่ /admin/fortune/billing → กด retry — ลูกค้าได้ failure notification แล้ว',
                ]
            );
        } catch (Throwable $e) {
            Log::warning('FortuneAiFailureRecovery: LINE alert ล้มเหลว (non-blocking)', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);
        }

        // 2. Takeover log + 3. flag
        try {
            $reading = FortuneReading::find($readingId);
            if (! $reading) {
                return;
            }

            FortuneTakeoverLog::create([
                'fortune_reading_id' => $reading->id,
                'user_id' => null,
                'action' => FortuneTakeoverLog::ACTION_MESSAGE,
                'reason' => 'ai_failed_after_retries',
                'message' => 'AI ล้มเหลวถาวร — ต้อง admin recover (ลูกค้าจ่ายเงินแล้ว)',
                'platform' => $platform,
                'metadata' => [
                    'alert_type' => 'ai_failed_after_retries',
                    'error' => mb_substr($exception->getMessage(), 0, 500),
                    'attempts' => $attempts,
                    'requires_admin_action' => true,
                ],
            ]);

            $reading->setConversationState('ai_failed_alert', true);
            $reading->setConversationState('ai_failed_alert_at', now()->toIso8601String());
            $reading->setConversationState('ai_failed_alert_error', mb_substr($exception->getMessage(), 0, 200));
        } catch (Throwable $e) {
            Log::warning('FortuneAiFailureRecovery: takeover log ล้มเหลว (non-blocking)', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Push failure notification ให้ลูกค้า — กัน trust loss
     *
     * - LINE: Flex Message พร้อมปุ่ม "คุยกับแม่หมอ" / "เช็คสถานะ"
     * - Facebook: text + message_tag POST_PURCHASE_UPDATE (ข้าม 24hr policy)
     */
    protected function pushCustomerNotification(int $readingId, string $platform, ?string $userId): void
    {
        if (empty($userId) || ! in_array($platform, ['line', 'facebook'], true)) {
            return;
        }

        try {
            $reading = FortuneReading::find($readingId);
            if (! $reading) {
                return;
            }

            // กัน push ซ้ำภายในบิลเดียว
            if ($reading->getConversationState('ai_failed_customer_notified', false)) {
                return;
            }

            // 🌙 (2026-06-06) user spec: "อย่าแจ้งลูกค้าว่าเอไอขัดข้องเด็ดขาด" — โทนนุ่ม คงคำมั่น+ปุ่มไว้
            $message = "🌙 ขออภัยค่ะ คำทำนายของคุณใช้เวลามากกว่าปกติเล็กน้อย\n\n"
                ."ทีมงานแม่หมอกำลังดูแลให้อยู่ และจะส่งคำทำนายให้ครบแน่นอนค่ะ\n\n"
                ."💬 กดปุ่ม 'คุยกับแม่หมอ' เพื่อให้ทีมงานช่วยโดยตรง\n"
                .'🔮 ขอเวลาสักครู่นะคะ — คำทำนายของคุณไม่หายไปไหนค่ะ';

            $settings = FortuneTellingSetting::getSettings();

            if ($platform === 'line') {
                $lineService = new LineFortuneService($settings);

                $richContent = [
                    'alt_text' => '🌙 คำทำนายกำลังมา — กดคุยกับแม่หมอได้ค่ะ',
                    'contents' => [
                        'type' => 'bubble',
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'contents' => [
                                ['type' => 'text', 'text' => '🌙 คำทำนายของคุณกำลังมาค่ะ', 'weight' => 'bold', 'size' => 'lg', 'color' => '#D97706'],
                                ['type' => 'text', 'text' => $message, 'wrap' => true, 'size' => 'sm', 'margin' => 'md'],
                            ],
                        ],
                        'footer' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'spacing' => 'sm',
                            'contents' => [
                                // 🧹 (2026-07-25, owner) ลบปุ่ม "คุยกับแม่หมอ" ทุกจุด — ใช้ keyword detection แทน
                                [
                                    'type' => 'button',
                                    'style' => 'primary',
                                    'color' => '#7C3AED',
                                    'action' => ['type' => 'message', 'label' => '🔍 เช็คสถานะ', 'text' => 'เช็คสถานะ'],
                                ],
                            ],
                        ],
                    ],
                ];

                $lineService->sendRichMessage($userId, $richContent);
            } else {
                // Facebook — text + POST_PURCHASE_UPDATE tag (ข้าม 24hr policy)
                // 🩹 (self-review H3) ต้องมี force_tag=true เพื่อ FB sendMessage บังคับ
                //    messaging_type=MESSAGE_TAG ทันที (ไม่ลอง RESPONSE first ที่ fail past 24h)
                //    เคสจริง: Job retry หลายชั่วโมง → ลูกค้าออกจาก 24h window → ต้อง MESSAGE_TAG
                $fbService = new FacebookWebhookService($settings);
                $fbService->sendMessage($userId, $message, [
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                    'force_tag' => true,
                    'from_admin' => true,
                ]);
            }

            $reading->setConversationState('ai_failed_customer_notified', true);
            $reading->setConversationState('ai_failed_customer_notified_at', now()->toIso8601String());

            Log::info('FortuneAiFailureRecovery: customer push ส่งแล้ว', [
                'reading_id' => $readingId,
                'platform' => $platform,
            ]);
        } catch (Throwable $e) {
            Log::error('FortuneAiFailureRecovery: customer push ล้มเหลว', [
                'reading_id' => $readingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
