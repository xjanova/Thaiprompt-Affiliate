<?php

namespace App\Jobs;

use App\Models\FortuneReading;
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

        // แจ้ง admin ผ่าน LineAlertService
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

            // ตั้ง flag ใน reading — UI จะแสดง badge "🚨 AI ค้าง" ได้
            $reading->setConversationState('ai_stuck_alert_at', now()->toIso8601String());
            $reading->setConversationState('ai_stuck_alert_purpose', $this->aiPurpose);

            Log::warning('FortuneAiStuckAlertJob: แจ้ง admin สำเร็จ', [
                'reading_id' => $reading->id,
                'purpose' => $this->aiPurpose,
            ]);
        } catch (\Throwable $e) {
            Log::error('FortuneAiStuckAlertJob: แจ้ง admin ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
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
