<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Services\FcmNotificationService;
use App\Services\LineAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🚨 (2026-05-14) แจ้งเตือน admin เมื่อ AI ทำงานเกิน 1 นาที
 *
 * เคสที่กัน: AI ค้าง/หลุด/timeout — ลูกค้ารอ → admin ไม่รู้
 *   - ProcessDeepFortuneReadingJob มี alertAdminAfterAiFailure() ที่ยิงตอน "fail ถาวร"
 *   - แต่ "ยังไม่ fail" (ยังอยู่ในระหว่าง retry / sync ค้าง) admin ไม่ได้รู้
 *   - Job นี้แจ้ง admin เมื่อเวลา 60s ผ่านไป แต่ AI session ยังไม่ปิด
 *
 * 📲 Channel Strategy (FCM-first → LINE fallback):
 *   1. FCM push ไป SMS Payment admin app — ฟรีไม่จำกัด, real-time
 *   2. ถ้า FCM ไม่ได้ (no tokens / disabled) → fallback LINE OA push (เสีย quota)
 *   3. ประหยัด LINE quota ~90% (admin หลักใช้ SMS Payment app อยู่แล้ว)
 *
 * Throttle: cache key per reading — กัน spam (max 1 alert / 5 นาที / reading)
 *
 * Cancel: ถ้า AI เสร็จก่อน 60s → session cache forget → job เห็น session ไม่ตรง → skip
 */
class FortuneAiStuckAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public int $readingId,
        public string $platform,
        public string $userId,
        public string $aiPurpose,
        public string $sessionId,
    ) {}

    public function handle(): void
    {
        // ตรวจว่า AI ยังทำงานอยู่ — ถ้า session คลีนแล้ว ไม่ต้อง alert
        $activeSession = Cache::get($this->getSessionCacheKey());
        if ($activeSession !== $this->sessionId) {
            Log::debug('FortuneAiStuckAlertJob: skip — AI เสร็จก่อน 60s', [
                'reading_id' => $this->readingId,
                'expected_session' => $this->sessionId,
                'active_session' => $activeSession,
            ]);

            return;
        }

        // Throttle: max 1 alert / 5 นาที / reading
        $throttleKey = "fortune:ai_stuck_alerted:{$this->readingId}";
        if (Cache::has($throttleKey)) {
            Log::debug('FortuneAiStuckAlertJob: skip — เพิ่ง alert ไม่นาน', [
                'reading_id' => $this->readingId,
            ]);

            return;
        }
        Cache::put($throttleKey, true, 300);

        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            return;
        }

        // 📲 Step 1: FCM push ไป SMS Payment admin app (ฟรีไม่จำกัด)
        //    Return true ถ้ามี token + ส่งสำเร็จ → ไม่ต้อง LINE fallback
        //    Return false ถ้าไม่มี token / FCM disabled → fallback LINE
        $fcmSent = false;
        try {
            $fcmSent = app(FcmNotificationService::class)
                ->notifyAiStuck($reading, $this->aiPurpose);
        } catch (\Throwable $fcmErr) {
            Log::warning('FortuneAiStuckAlertJob: FCM ล้มเหลว — fallback LINE', [
                'reading_id' => $reading->id,
                'error' => $fcmErr->getMessage(),
            ]);
            $fcmSent = false;
        }

        // 📲 Step 2: ถ้า FCM ไม่ได้ → fallback LINE OA push (เสีย quota)
        //    เคสที่ตก fallback: admin ยังไม่ลง app / ปิด FCM / token expired
        if (! $fcmSent) {
            try {
                $alertService = app(LineAlertService::class);
                $alertService->alertSystemError(
                    '⏰ AI ทำงานเกิน 1 นาที — ตรวจสอบด่วน',
                    [
                        'reading_id' => $reading->id,
                        'bill_ref' => $reading->bill_reference ?? '-',
                        'customer' => $reading->facebook_user_name ?? $reading->line_user_name ?? '-',
                        'platform' => $this->platform,
                        'ai_purpose' => $this->aiPurpose,
                        'reading_status' => $reading->conversation_status ?? '-',
                        'reading_type' => $reading->reading_type ?? '-',
                        'admin_action' => "ไปที่ /admin/fortune/readings/{$reading->id} — ถ้า AI ค้าง ให้กด retry",
                    ]
                );

                Log::warning('FortuneAiStuckAlertJob: LINE fallback แจ้ง admin', [
                    'reading_id' => $reading->id,
                    'purpose' => $this->aiPurpose,
                ]);
            } catch (\Throwable $lineErr) {
                Log::error('FortuneAiStuckAlertJob: ทั้ง FCM และ LINE ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'error' => $lineErr->getMessage(),
                ]);
            }
        } else {
            Log::warning('FortuneAiStuckAlertJob: FCM แจ้ง admin สำเร็จ (ประหยัด LINE quota)', [
                'reading_id' => $reading->id,
                'purpose' => $this->aiPurpose,
            ]);
        }

        // ตั้ง flag ใน reading — UI จะแสดง badge "🚨 AI ค้าง" ได้
        try {
            $reading->setConversationState('ai_stuck_alert_at', now()->toIso8601String());
            $reading->setConversationState('ai_stuck_alert_purpose', $this->aiPurpose);
            $reading->setConversationState('ai_stuck_alert_channel', $fcmSent ? 'fcm' : 'line');
        } catch (\Throwable $stateErr) {
            // ignore — non-blocking
        }
    }

    private function getSessionCacheKey(): string
    {
        return "fortune:ai_session:{$this->readingId}";
    }

    public function displayName(): string
    {
        return "FortuneAiStuckAlert[#{$this->readingId}:{$this->aiPurpose}]";
    }

    public function tags(): array
    {
        return [
            'fortune-alert',
            "reading:{$this->readingId}",
            "purpose:{$this->aiPurpose}",
        ];
    }
}
