<?php

namespace App\Jobs;

use App\Models\FortuneReading;
use App\Services\Fortune\FortuneRecipient;
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

        // 🛟 (2026-09-07) อย่าเชื่อ platform ที่ติดมากับ payload — ยืนยันกับตัวบิลก่อนเสมอ
        //   1. ตัวบิลคือแหล่งความจริง (`platform` column) — caller ที่เดาช่องทางเองจะถูกลบล้างที่นี่
        //   2. job ที่เข้าคิวไว้ **ก่อน** deploy ยังถือค่าผิดติดตัวมา ⇒ ถ้าไม่ซ่อม ของค้างในคิว
        //      จะยิงผิดช่องทางต่ออีกหลายนาทีหลังโค้ดใหม่ขึ้น
        //   เคสจริง reading 12537 / FTU-260907-C5731: ping 2 กล่องยิงเข้า Facebook Send API
        //   ด้วย LINE userId → 400 `(#100) Param recipient[id]...` ×2 → กล่องหายถาวร
        ['platform' => $platform, 'user_id' => $userId] = FortuneRecipient::resolve($reading);

        // บิลไม่มี id ให้ใช้ (แถวเก่า/ข้อมูลไม่ครบ) → ค่อยถอยไปใช้ payload ที่ซ่อมแล้ว
        if ($userId === '') {
            ['platform' => $platform, 'user_id' => $userId] = FortuneRecipient::normalize(
                $this->platform,
                $this->userId
            );
        }

        if ($platform !== $this->platform) {
            Log::warning('FortuneAiPingJob: payload ระบุช่องทางผิด — ซ่อมจากรูปทรงของ user id', [
                'reading_id' => $this->readingId,
                'payload_platform' => $this->platform,
                'resolved_platform' => $platform,
                'stage_seconds' => $this->stageSeconds,
            ]);
        }

        if ($userId === '') {
            return;
        }

        // ดึงข้อความตาม stage
        $message = $this->getMessageForStage();
        if (! $message) {
            return;
        }

        // ส่งข้อความ (best-effort, non-blocking)
        try {
            $sent = $this->sendMessage($platform, $userId, $message);

            // 📌 ธง "ส่งแล้ว" ต้องมาจากผลส่งจริงเท่านั้น — ของเดิม log "สำเร็จ" ทุกครั้ง
            //    เพราะ sendMessage() คืน false ไม่ได้ throw ⇒ log บอกว่าส่งได้ทั้งที่ 400
            //    (เคส 12537: "ส่ง ping สำเร็จ" ตามหลัง "ส่งข้อความล้มเหลวหลังลอง 2 ครั้ง" ทันที)
            Log::log($sent ? 'info' : 'warning', 'FortuneAiPingJob: ส่ง ping '.($sent ? 'สำเร็จ' : 'ไม่สำเร็จ'), [
                'reading_id' => $this->readingId,
                'platform' => $platform,
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
     *
     * ⚠️ ฝั่ง LINE ไม่ได้ใช้ "ข้อความ" ก้อนนี้ส่งจริง (ส่งเป็น typing dots แทน — ดู sendMessage())
     *   ค่าที่คืนออกไปทำหน้าที่เป็น **ด่านว่าสเตจนี้ควรส่งสัญญาณไหม** เท่านั้น
     */
    private function getMessageForStage(): ?string
    {
        // 💸 (2026-07-25) LINE เคยตัดสเตจ 10 วินาทีทิ้ง เพราะกล่อง ping ไปติดกับกล่อง
        //   "🃏 ได้ไพ่..." ที่เพิ่งส่งไปไม่กี่วินาที — ลูกค้าเห็น 2 กล่องซ้อนโดยไม่ได้ข้อมูลใหม่
        //   (เจ้าของ 2026-07-25: "ทำไมมีการส่งกล่องข้อความซ้ำหลายกล่อง")
        //
        // ✅ (2026-09-07) ฝั่ง LINE ไม่ส่งกล่องข้อความอีกแล้ว — เป็น typing dots (ดู sendMessage())
        //   ไม่มีอะไรให้ซ้อนอีก จึงเปิดครบทั้ง 3 สเตจได้ และดีกว่าเดิมด้วย:
        //   dots โชว์ได้ครั้งละ 60 วิ ⇒ ต่ออายุที่ 10/30/60 = คลุมยาวถึง ~120 วิ
        //   (Deep 39 ใช้ gen 30-90 วิ เมื่อมี completeness-gate retry)

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
     * ส่งสัญญาณ "แม่หมอยังทำงานอยู่" ตามช่องทาง
     *
     * 💸 ฝั่ง LINE ใช้ typing dots ไม่ใช่กล่องข้อความ
     *   `showLoadingAnimation()` **ฟรี ไม่กินโควต้า และไม่กิน replyToken** (คนละ endpoint)
     *   ส่วน `LineFortuneService::sendMessage()` วิ่งเข้า `pushMessage()` ตรง ๆ = กินโควต้า
     *   ที่มีแค่ **300 ครั้ง/เดือน** — และกล่อง "กำลังคิด" อยู่ในรายการ **ห้าม push เด็ดขาด**
     *   📖 .claude/LINE_MESSAGING_RULES.md กฎข้อ 1 (ท่าเดียวกับ sendCelticThinkingAck)
     *
     *   ⚠️ ก่อน 2026-09-07 ลูกค้า LINE ถูกติดป้าย platform='facebook' ⇒ ping ไม่เคยถึงฝั่ง LINE เลย
     *      พอแก้การแยกช่องทางแล้ว ถ้าไม่เปลี่ยนมาใช้ loading animation ตรงนี้ด้วย
     *      บิล Celtic ของลูกค้า LINE จะเริ่ม push ping 2 กล่อง/คำถาม = เผาโควต้าเดือนละไม่กี่ร้อยทิ้ง
     *
     * @return bool ผลส่งจริง (ห้ามเดา — คนเรียกเอาไปลง log)
     */
    private function sendMessage(string $platform, string $userId, string $message): bool
    {
        if ($platform === FortuneRecipient::PLATFORM_FACEBOOK) {
            $fbService = app(\App\Services\FacebookWebhookService::class);

            // ใช้ POST_PURCHASE_UPDATE — ลูกค้าจ่ายแล้ว/อยู่ในระหว่างขอคำถาม → ในกรอบ 24hr อยู่แล้ว
            //   ใช้ tag เพื่อเป็น safety net หาก 24hr expired (เคส Celtic Q&A นาน)
            return (bool) $fbService->sendMessage($userId, $message, [
                'message_tag' => 'POST_PURCHASE_UPDATE',
                'from_admin' => true,
            ]);
        }

        if ($platform === FortuneRecipient::PLATFORM_LINE) {
            // ต่ออายุ typing dots อีก 60 วิ — ฟรี และไม่มีกล่องซ้อนให้ลูกค้ารำคาญ
            return (bool) app(\App\Services\LineFortuneService::class)
                ->showLoadingAnimation($userId, 60);
        }

        Log::warning('FortuneAiPingJob: platform ไม่รู้จัก', [
            'reading_id' => $this->readingId,
            'platform' => $platform,
        ]);

        return false;
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
