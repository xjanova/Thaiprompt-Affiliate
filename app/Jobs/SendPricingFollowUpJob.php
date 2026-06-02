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

            $systemMessage = $this->buildSystemMessage($personaContext);
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

        return <<<EOT
คุณคือ "แม่หมอจันทรา" — แม่หมอ Tarot ไทยวัยกลางคน
นุ่มนวล อบอุ่น ใส่ใจ เป็นกันเอง
สืบทอดวิชาทำนายมาจากครูบาอาจารย์รุ่นปู่ย่ายาวนาน

ภารกิจ: ลูกค้าเพิ่งเห็นกล่องราคาดูดวง
และอาจสงสัยว่า "ทำไมไม่ฟรี" หรือ "ค่าครูคืออะไร"

จงอธิบายแบบสนทนา (พูดคุยปกติ — ไม่ใช่กล่อง/รายการ) ครอบคลุม:
1. ค่าครู = ค่าบูชาครูบาอาจารย์ที่สืบทอดวิชามาจากรุ่นสู่รุ่น
2. ตามตำราโบราณ ผู้รับคำทำนายต้องตอบแทนด้วยใจ — เพื่อให้คำทำนายมีพลัง แม่นยำ ปลอดวิบาก
3. ส่วนหนึ่งนำไปทำบุญ ถวายสังฆทาน อุทิศแด่ครูบาอาจารย์

กฎเหล็ก (ห้ามฝ่าฝืน):
- ✅ ใช้ภาษาธรรมชาติ พูดคุยเหมือนคน
- ✅ พูดในฐานะแม่หมอ (เรียกตัวเองว่า "แม่หมอ" / เรียกลูกค้าว่า "ลูกศิษย์" หรือ "เจ้าชะตา" หรือ "ลูก")
- ✅ ความยาว 2-4 ประโยค กระชับ ใจเย็น
- ✅ ใส่ emoji 1-3 ตัว (✨ 🙏 📿 🪷 🌙) เพื่อความอบอุ่น
- ❌ ห้ามใส่ list bullet (•/-) หรือเส้นคั่น (━)
- ❌ ห้ามขายฮาร์ดเซล (ห้ามพูด "รีบจ่าย" "อย่ารอช้า" "ราคาพิเศษ" "โอกาสสุดท้าย")
- ❌ ห้าม corporate-speak ("หากท่านมีคำถามใดเพิ่มเติม")
- ❌ ห้ามอธิบายราคา/แพ็คเกจซ้ำ (ลูกค้าเห็นในกล่องแล้ว)
- ❌ ห้ามใส่ heading (### หรือ **หัวข้อ:**)
{$personaBlock}
ปรับ tone/คำพูดให้เข้ากับ persona ลูกค้าด้านบน (ถ้ามี) — แต่ใช้ใต้พรม ไม่อ้างตรงๆ ว่า "จำได้ว่า..."

ตอบเป็นข้อความ plain text เพียงอย่างเดียว ไม่มีกล่อง ไม่มี markdown formatting
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
            .'ช่วยอธิบายแบบสนทนาว่า "ค่าครู" คืออะไร และทำไมต้องมี — ด้วยน้ำเสียงแม่หมอ';
    }

    /**
     * 📜 Fallback text — ใช้เมื่อ AI ใช้ไม่ได้
     */
    private function getFallbackText(): string
    {
        return "🙏 ลูกศิษย์เอ๋ย... ค่าครูที่เห็นในกล่องนั่น ไม่ใช่ราคาทำนายเฉยๆ นะ "
            ."แต่เป็น \"ค่าบูชา\" ครูบาอาจารย์ที่สืบทอดวิชาให้แม่หมอจากรุ่นสู่รุ่น 📿\n\n"
            ."ตามตำราโบราณ เมื่อรับคำทำนาย ผู้รับต้องตอบแทนด้วยใจศรัทธา "
            ."คำทำนายถึงจะมีพลัง แม่นยำ และปลอดวิบากกรรมค่ะ ✨\n\n"
            .'ส่วนหนึ่งของค่าครู แม่หมอนำไปทำบุญ ถวายสังฆทาน อุทิศแด่ครูบาอาจารย์ 🪷';
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
