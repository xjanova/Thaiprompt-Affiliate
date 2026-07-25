<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🔔 (2026-05-14) ส่งข้อความ "หมอกำลังคิด..." เป็นระยะระหว่าง AI ทำงาน
 *
 * ปัญหาที่กัน: AI ใช้เวลานาน 30-90 วินาที (OpenAI Reasoning, GPT-5, Gemini Pro)
 *   - ลูกค้าเห็น pre-reply ครั้งเดียว → คิดว่าบอทเงียบ/พัง
 *   - บางคนพิมพ์ซ้ำ → กลับเข้า state เก่า / ทำให้ flow รวน
 *
 * Solution: dispatch job หลายตัว delay 10s/30s/60s
 *   - แต่ละ job ตรวจ Cache::has("fortune:ai_session:{$readingId}") ก่อนส่ง
 *   - ถ้า session คลีนแล้ว (AI เสร็จ) → skip ส่ง
 *
 * Queue: ใช้ default queue (มี worker อยู่แล้ว)
 *   - ถ้า queue:work ไม่รัน → ping ไม่ทำงาน (graceful degradation)
 *   - ระบบยังทำงานปกติ — แค่ไม่มีข้อความ "กำลังคิด..." เพิ่มเติม
 */
class FortuneAiPingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public int $readingId,
        public string $platform,
        public string $userId,
        public int $stageSeconds,
        public string $sessionId,
    ) {}

    public function handle(): void
    {
        // 🛡️ ตรวจว่า AI session ยัง active หรือไม่
        //   - ถ้า session ID ไม่ตรง → AI เสร็จแล้ว หรือ session ใหม่ → skip
        //   - ถ้า session ไม่มีใน cache → AI เสร็จแล้ว → skip
        $activeSession = Cache::get($this->getSessionCacheKey());
        if ($activeSession !== $this->sessionId) {
            Log::debug('FortuneAiPingJob: skip — session ไม่ตรง', [
                'reading_id' => $this->readingId,
                'stage_seconds' => $this->stageSeconds,
                'expected_session' => $this->sessionId,
                'active_session' => $activeSession,
            ]);

            return;
        }

        // ตรวจ reading ยังอยู่
        $reading = FortuneReading::find($this->readingId);
        if (! $reading) {
            return;
        }

        // ดึงข้อความตาม stage
        $message = $this->getMessageForStage();
        if (! $message) {
            return;
        }

        // ส่งข้อความ (best-effort, non-blocking)
        try {
            $this->sendMessage($message);

            Log::info('FortuneAiPingJob: ส่ง ping สำเร็จ', [
                'reading_id' => $this->readingId,
                'platform' => $this->platform,
                'stage_seconds' => $this->stageSeconds,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FortuneAiPingJob: ส่ง ping ล้มเหลว (non-blocking)', [
                'reading_id' => $this->readingId,
                'stage_seconds' => $this->stageSeconds,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ข้อความ ping ตามช่วงเวลา — escalate ความรู้สึก "ใกล้เสร็จ"
     */
    private function getMessageForStage(): ?string
    {
        // 💸 (2026-07-25) LINE: ตัด ping ที่ 10 วินาทีทิ้ง — มันมาติดกับกล่อง "🃏 ได้ไพ่..."
        //   ที่เพิ่งส่งไปไม่กี่วินาที (ลูกค้าเห็น 2 กล่องซ้อนโดยไม่ได้ข้อมูลใหม่)
        //   คงไว้ที่ 30s + 60s เพราะ Deep 39 ใช้เวลา gen 30-90 วิ (มี completeness-gate retry)
        //   ถ้าตัดเหลือกล่องเดียวลูกค้าจะเงียบยาวเกินไปจนคิดว่าบอทค้าง
        //   (เจ้าของ 2026-07-25: "ทำไมมีการส่งกล่องข้อความซ้ำหลายกล่อง")
        if ($this->platform === 'line' && ! in_array($this->stageSeconds, [30, 60], true)) {
            return null;
        }

        return match ($this->stageSeconds) {
            10 => "🌙 *แม่หมอกำลังเปิดตำราดู...*\n"
                .'ขอเวลาอีกสักครู่นะคะ ✨',

            30 => "🔮 *ดวงนี้ลึกซึ้ง แม่หมอกำลังพิจารณาให้ละเอียด...*\n"
                ."ขอบคุณที่อดทนรอนะคะ 🙏\n"
                .'(อย่าเพิ่งพิมพ์ซ้ำนะคะ)',

            60 => "✨ *ใกล้ได้แล้วค่ะ...*\n"
                ."แม่หมอกำลังเรียบเรียงคำทำนายให้ครบถ้วน 📜\n"
                .'ขอบคุณที่อดทนรอ 🙏✨',

            default => null,
        };
    }

    /**
     * ส่งข้อความผ่าน FB หรือ LINE
     */
    private function sendMessage(string $message): void
    {
        if ($this->platform === 'facebook') {
            $fbService = app(\App\Services\FacebookWebhookService::class);
            // ใช้ POST_PURCHASE_UPDATE — ลูกค้าจ่ายแล้ว/อยู่ในระหว่างขอคำถาม → ในกรอบ 24hr อยู่แล้ว
            //   ใช้ tag เพื่อเป็น safety net หาก 24hr expired (เคส Celtic Q&A นาน)
            $fbService->sendMessage($this->userId, $message, [
                'message_tag' => 'POST_PURCHASE_UPDATE',
                'from_admin' => true,
            ]);
        } elseif ($this->platform === 'line') {
            $lineService = app(\App\Services\LineFortuneService::class);
            $lineService->sendMessage($this->userId, $message);
        } else {
            Log::warning('FortuneAiPingJob: platform ไม่รู้จัก', [
                'reading_id' => $this->readingId,
                'platform' => $this->platform,
            ]);
        }
    }

    private function getSessionCacheKey(): string
    {
        return "fortune:ai_session:{$this->readingId}";
    }

    public function displayName(): string
    {
        return "FortuneAiPing[#{$this->readingId}:+{$this->stageSeconds}s]";
    }

    public function tags(): array
    {
        return [
            'fortune-ping',
            "reading:{$this->readingId}",
            "stage:{$this->stageSeconds}s",
        ];
    }
}
