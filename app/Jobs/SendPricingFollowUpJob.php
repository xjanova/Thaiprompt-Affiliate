<?php

namespace App\Jobs;

use App\Models\FortuneCustomerPersona;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🙏 (2026-05-14) ส่งคำอธิบาย "ค่าครู" แบบสนทนา หลังกล่องราคา
 *
 * Triggered: หลัง FortuneConversationService::presentPricingMenu() ส่งกล่องราคา
 *
 * Behavior:
 *   - Cache 7 วัน — ลูกค้าคนเดียวกัน ฟังครั้งเดียวพอ (กันสแปม)
 *   - Load FortuneCustomerPersona → inject เป็น context ให้ AI ปรับ tone
 *   - AI generate ข้อความในเสียงแม่หมอจันทรา (สนทนา ไม่ใช่กล่อง)
 *   - Fallback hardcoded text ถ้า AI ล้ม
 *   - Delay 4s ให้กล่องราคาขึ้นก่อน (dispatch->delay())
 *
 * คำสั่ง AI:
 *   - ใช้ chatWithCustomSystemPrompt (raw — ไม่โดน chat directive ปนเปื้อน)
 *   - Pool fallback ถ้า direct key ว่าง
 *   - Temperature 0.85 — ต้องการความเป็นธรรมชาติ
 */
class SendPricingFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(
        public string $platform,
        public string $userId,
        public array $pricing = [],
    ) {}

    public function handle(): void
    {
        // 🛡️ Cache guard — 1 ครั้ง/ลูกค้า/7 วัน
        $cacheKey = $this->getCacheKey();
        if (Cache::has($cacheKey)) {
            Log::debug('SendPricingFollowUpJob: skip — ลูกค้าฟัง explanation แล้วใน 7 วัน', [
                'platform' => $this->platform,
                'user_id' => $this->userId,
            ]);

            return;
        }

        // Load persona (optional — null OK)
        $persona = FortuneCustomerPersona::findByPlatformUser($this->platform, $this->userId);
        $personaContext = $persona ? $persona->toAiContextBlock() : '';

        // ลอง AI ก่อน → fallback hardcoded
        $message = $this->generateAiExplanation($personaContext) ?? $this->getFallbackText();

        try {
            $this->sendMessage($message);

            // Mark sent (TTL 7 วัน)
            Cache::put($cacheKey, now()->timestamp, now()->addDays(7));

            Log::info('SendPricingFollowUpJob: ส่งสำเร็จ', [
                'platform' => $this->platform,
                'user_id' => $this->userId,
                'has_persona' => $persona !== null,
                'used_fallback' => str_starts_with($message, '🙏 ลูกศิษย์'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SendPricingFollowUpJob: ส่ง message ล้มเหลว (non-blocking)', [
                'platform' => $this->platform,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🤖 AI generate คำอธิบาย — null ถ้าใช้ไม่ได้ (caller จะ fallback)
     */
    private function generateAiExplanation(string $personaContext): ?string
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $aiService = new FortuneAIService($settings);

            $deepPrice = (int) ($this->pricing['deep_price'] ?? 39);
            $celticPrice = (int) ($this->pricing['celtic_price'] ?? 99);
            $celticEnabled = (bool) ($this->pricing['celtic_enabled'] ?? false);
            // 🌙 (2026-05-23) prefer flag จาก pricing array (มาจาก presentPricingMenu)
            //   fallback อ่าน settings ตรงถ้า key ไม่มี (backward compat)
            $deepEnabled = isset($this->pricing['deep_enabled'])
                ? (bool) $this->pricing['deep_enabled']
                : $settings->isDeepReadingEnabled();

            $systemMessage = \App\Services\Fortune\FortunePageIdentity::appendTo(
                $this->buildSystemMessage($personaContext)
            );
            $userMessage = $this->buildUserMessage($deepPrice, $celticPrice, $celticEnabled, $deepEnabled);

            // 📚 (2026-06-02) Inject RAG admin Q&A — เลียน tone อธิบาย "ราคา/ค่าครู" ของแอดมินจริง
            //   proactive ไม่มี customer message → ใช้ query สังเคราะห์ (ครอบหมวด pre_purchase/pre_payment)
            try {
                $ragQuery = 'ราคาดูดวงเท่าไหร่ ทำไมไม่ฟรี ค่าครูคืออะไร โอนเงินยังไง';
                $systemMessage = $aiService->injectAdminQARagFewShot($systemMessage, $ragQuery, null);
            } catch (\Throwable $e) {
                // RAG ล้ม → ใช้ system message เดิม (ไม่ break job)
                Log::debug('SendPricingFollowUpJob: RAG inject ล้ม fallback no-RAG', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }

            $directKey = $settings->getChatAIApiKey();

            if (! empty($directKey)) {
                // Path 1: direct chat key
                $result = $aiService->chatWithCustomSystemPrompt(
                    $systemMessage,
                    $userMessage,
                    ['temperature' => 0.85, 'max_tokens' => 400]
                );
            } else {
                // Path 2: Pool fallback
                $result = $this->callViaPool($aiService, $systemMessage, $userMessage);
                if ($result === null) {
                    return null;
                }
            }

            $text = trim($result['response'] ?? '');

            // Guard — short response แสดงว่า AI ล้ม
            if (empty($text) || mb_strlen($text) < 30) {
                Log::debug('SendPricingFollowUpJob: AI ตอบสั้นเกินไป — ใช้ fallback', [
                    'response_preview' => mb_substr($text, 0, 100),
                ]);

                return null;
            }

            // Sanitize — remove box characters AI อาจ leak มา
            $text = preg_replace('/━+/u', '', $text);
            $text = preg_replace('/^[\s\n]*[•\-\*]\s+/mu', '', $text);

            return trim($text);
        } catch (\Throwable $e) {
            Log::warning('SendPricingFollowUpJob: AI ล้มเหลว — ใช้ fallback', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🏊 Pool fallback — acquire chat key + raw call
     */
    private function callViaPool(FortuneAIService $aiService, string $systemMessage, string $userMessage): ?array
    {
        try {
            $poolService = app(\App\Services\AiApiKeyPoolService::class);
            $key = $poolService->acquireKeyAnyProvider('chat');

            if (! $key) {
                return null;
            }

            return $aiService->chatWithCustomSystemPrompt(
                $systemMessage,
                $userMessage,
                ['temperature' => 0.85, 'max_tokens' => 400],
                $key->provider,
                $key->model,
                $key->api_key
            );
        } catch (\Throwable $e) {
            Log::warning('SendPricingFollowUpJob: Pool fallback ล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 📝 System message — บุคลิก + ภารกิจ + กฎเหล็ก + persona context
     */
    private function buildSystemMessage(string $personaContext): string
    {
        $personaBlock = ! empty($personaContext) ? "\n\n{$personaContext}\n" : '';

        // 🗣️ (2026-06-02) ตอบ "เหมือนแอดมินตัวจริงตอบ" — อิงตัวอย่างจริงจาก RAG admin เป็นหลัก
        //   ไม่แต่ง persona อบอุ่น/บทกวีเอง (user: "ไปดูที่แอดมินตอบไว้ ตอบคล้ายแอดมินเลย")
        return <<<EOT
คุณคือ "แอดมินเพจดูดวงแม่หมอจันทรา" กำลังพิมพ์ตอบลูกค้าใน DM ด้วยตัวเอง

สถานการณ์: ลูกค้าเพิ่งเห็นกล่องราคา อาจสงสัยว่า "ทำไมไม่ฟรี" หรือ "ค่าครูคืออะไร"

วิธีตอบ (สำคัญที่สุด):
- ด้านล่างจะมี "📚 ตัวอย่างคำตอบจริงของแอดมิน" → ตอบให้ "เหมือนแอดมินตัวจริงตอบ":
  ใช้สำนวน น้ำเสียง และแนวทางเดียวกับตัวอย่าง ยึดวิธีแอดมินเป็นหลัก ไม่ต้องแต่งเติมเอง
- ใจความที่ต้องสื่อ (สั้น ๆ ตามที่แอดมินเคยตอบ): ค่าครู = ค่าบูชาครูบาอาจารย์ / ส่วนหนึ่งทำบุญ — ไม่ต้องร่ายยาว
- ถ้าไม่มีตัวอย่างให้ดู → อธิบายสั้น ๆ เป็นกันเอง

กฎเหล็ก (ห้ามฝ่าฝืน):
- ✅ สั้น 1-3 ประโยค เหมือนคนพิมพ์แชทจริง (ไม่มี ━ ไม่มี bullet ไม่มี heading)
- ✅ แม่หมอเป็นผู้หญิง — ใช้ "ค่ะ/นะคะ" เท่านั้น ห้าม "ครับ/ผม" เด็ดขาด
- ❌ ห้ามฮาร์ดเซล ("รีบจ่าย" "อย่ารอช้า" "ราคาพิเศษ" "โอกาสสุดท้าย")
- ❌ ห้ามอธิบายราคา/แพ็คเกจซ้ำ (ลูกค้าเห็นในกล่องแล้ว)
- ❌ ห้าม corporate-speak ("หากท่านมีคำถามใดเพิ่มเติม")
{$personaBlock}
ถ้ามีข้อมูล persona ลูกค้าด้านบน ปรับน้ำเสียงให้เข้ากันได้ แต่ไม่อ้างตรง ๆ ว่า "จำได้ว่า..."

ตอบเป็นข้อความ plain text เพียงอย่างเดียว ไม่มีกล่อง ไม่มี markdown
EOT;
    }

    private function buildUserMessage(int $deepPrice, int $celticPrice, bool $celticEnabled, bool $deepEnabled = true): string
    {
        // 🌙 (2026-05-23) Dynamic — ใส่เฉพาะ tier ที่ enable
        $parts = [];
        if ($deepEnabled) {
            $parts[] = "{$deepPrice} บาท (ดูดวงเชิงลึก)";
        }
        if ($celticEnabled) {
            $parts[] = "{$celticPrice} บาท (ไพ่ Celtic Cross 10 ใบ)";
        }
        $packages = empty($parts) ? '(ไม่มีแพ็คเกจ)' : 'แพ็คเกจ '.implode(' และ ', $parts);

        return "ลูกค้าเพิ่งเห็นกล่องราคา: {$packages}\n"
            .'ตอบเหมือนแอดมินตอบลูกค้าที่ถามเรื่องราคา/ค่าครู (ดูตัวอย่าง 📚 ถ้ามี) สั้น ๆ';
    }

    /**
     * 📜 Fallback text — ใช้เมื่อ AI ใช้ไม่ได้
     */
    private function getFallbackText(): string
    {
        return '🙏 ลูกศิษย์เอ๋ย... ค่าครูที่เห็นในกล่องนั่น ไม่ใช่ราคาทำนายเฉยๆ นะ '
            ."แต่เป็น \"ค่าบูชา\" ครูบาอาจารย์ที่สืบทอดวิชาให้แม่หมอจากรุ่นสู่รุ่น 📿\n\n"
            .'ตามตำราโบราณ เมื่อรับคำทำนาย ผู้รับต้องตอบแทนด้วยใจศรัทธา '
            ."คำทำนายถึงจะมีพลัง แม่นยำ และปลอดวิบากกรรมค่ะ ✨\n\n"
            .'ค่าครูนี้คือค่าบูชาครูบาอาจารย์ ผู้บันทึกและสืบทอดศาสตร์การพยากรณ์ดีๆ ไว้ให้เรายาวนานกว่า 5,000 ปีค่ะ 🙏';
    }

    /**
     * 📤 Send via FB หรือ LINE
     */
    private function sendMessage(string $message): void
    {
        if ($this->platform === 'facebook') {
            $fbService = app(\App\Services\FacebookWebhookService::class);
            // 💬 ลูกค้าเพิ่งพิมพ์ → อยู่ใน 24hr window — ไม่ต้องใช้ message_tag
            $fbService->sendMessage($this->userId, $message);
        } elseif ($this->platform === 'line') {
            $lineService = app(\App\Services\LineFortuneService::class);
            $lineService->sendMessage($this->userId, $message);
        } else {
            Log::warning('SendPricingFollowUpJob: platform ไม่รู้จัก', [
                'platform' => $this->platform,
            ]);
        }
    }

    private function getCacheKey(): string
    {
        return "fortune:cafru_explained:{$this->platform}:{$this->userId}";
    }

    public function displayName(): string
    {
        return "PricingFollowUp[{$this->platform}:{$this->userId}]";
    }

    public function tags(): array
    {
        return [
            'fortune-pricing-followup',
            "platform:{$this->platform}",
        ];
    }
}
