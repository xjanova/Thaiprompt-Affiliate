<?php

namespace App\Jobs;

use App\Models\FortuneCustomerPersona;
use App\Services\Fortune\CustomerPersonaService;
use App\Services\FortuneAIService;
use App\Models\FortuneTellingSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 👤 (2026-05-14) Extract Customer Persona (async)
 *
 * วิเคราะห์ข้อความลูกค้า → JSON persona → merge เข้า DB
 *
 * Strategy:
 *  1. แยก system message (task definition + schema) + user message (data to analyze)
 *  2. ใช้ chatWithCustomSystemPrompt — raw provider call, ไม่ append chat directive
 *  3. ลอง direct chat key ก่อน — ถ้าว่าง fallback ไป Pool (acquireKeyAnyProvider 'chat')
 *  4. Parse JSON → merge เข้า persona record (additive)
 *  5. Invalidate cache → AI inject ใหม่จะเห็น update
 *
 * Cost control:
 *  - Throttle ใน Service::dispatchExtraction() (30 นาที/user)
 *  - Temperature 0.3 — output structured JSON
 *  - Failure ไม่ทำให้ chat flow แตก (non-blocking)
 *
 * Review v2 (2026-05-14):
 *  - เปลี่ยนจาก generateChatResponse → chatWithCustomSystemPrompt (กัน chat directive pollute output)
 *  - เพิ่ม Pool fallback ผ่าน override params ใน chatWithCustomSystemPrompt
 *  - System+User message แยกชัดเจน + few-shot examples ใน system
 */
class ExtractCustomerPersonaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(
        public string $platform,
        public string $userId,
        public string $messageText,
        public ?string $displayName = null,
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);

        Log::info('ExtractCustomerPersonaJob: เริ่มประมวลผล', [
            'platform' => $this->platform,
            'user_id' => $this->userId,
            'message_length' => mb_strlen($this->messageText),
        ]);

        // 🔍 Load หรือสร้าง persona record
        $persona = FortuneCustomerPersona::getOrCreate(
            $this->platform,
            $this->userId,
            $this->displayName
        );

        // 📝 Build extraction prompt — แยก system + user แทน
        //   เดิม: ใช้ generateChatResponse — โดน chat directive ทั้งชุด → AI ตอบ chat-style
        //   ใหม่: ใช้ chatWithCustomSystemPrompt — system msg ตรงๆ ไม่มี directive
        $existingContext = $this->buildExistingContext($persona);
        [$systemMessage, $userMessage] = $this->buildExtractionMessages($existingContext, $this->messageText);

        // 🤖 Call AI — ลอง direct chat key ก่อน, fallback ไป Pool ถ้า empty
        try {
            $settings = FortuneTellingSetting::getSettings();
            $aiService = new FortuneAIService($settings);

            $directKey = $settings->getChatAIApiKey();
            if (! empty($directKey)) {
                // Path 1: direct chat key
                $result = $aiService->chatWithCustomSystemPrompt(
                    $systemMessage,
                    $userMessage,
                    [
                        'temperature' => 0.3, // ต่ำ — ต้องการ output structured
                        'max_tokens' => 600,
                    ]
                );
            } else {
                // Path 2: Pool-direct mode (direct key ว่าง) — acquire chat purpose key + call raw
                $result = $this->callViaPool($aiService, $settings, $systemMessage, $userMessage);
                if ($result === null) {
                    Log::debug('ExtractCustomerPersonaJob: ไม่มี chat key (direct/Pool) — skip', [
                        'platform' => $this->platform,
                        'user_id' => $this->userId,
                    ]);

                    return;
                }
            }

            $responseText = $result['response'] ?? '';

            if (empty($responseText)) {
                Log::warning('ExtractCustomerPersonaJob: AI ตอบกลับว่าง', [
                    'platform' => $this->platform,
                    'user_id' => $this->userId,
                ]);

                return;
            }

            // 📦 Parse JSON
            $extracted = $this->parseExtractedJson($responseText);

            if (empty($extracted)) {
                Log::debug('ExtractCustomerPersonaJob: parse JSON ไม่สำเร็จ หรือไม่มีข้อมูล', [
                    'response_preview' => mb_substr($responseText, 0, 200),
                ]);

                return;
            }

            // 🔄 Merge เข้า persona (additive)
            $persona->mergeData($extracted);

            // 📝 Regenerate markdown
            $persona->note_markdown = $persona->toObsidianMarkdown();

            $persona->save();

            // 🔥 Invalidate cache
            app(CustomerPersonaService::class)->invalidateCache(
                $this->platform,
                $this->userId
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('ExtractCustomerPersonaJob: สำเร็จ', [
                'platform' => $this->platform,
                'user_id' => $this->userId,
                'persona_id' => $persona->id,
                'observation_count' => $persona->observation_count,
                'duration_ms' => $duration,
                'extracted_keys' => array_keys($extracted),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ExtractCustomerPersonaJob: ล้มเหลว (non-blocking)', [
                'platform' => $this->platform,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 300),
            ]);
        }
    }

    /**
     * 🏊 Path 2: Pool-direct mode — acquire chat purpose key + call raw provider
     *
     * ใช้เมื่อ admin ไม่ตั้ง direct chat_ai_api_key (Pool-direct setup)
     * Pool service จัดการ provider routing + load balancing ให้
     *
     * @return array|null  ['response' => string] หรือ null ถ้าไม่มี key
     */
    private function callViaPool(
        FortuneAIService $aiService,
        FortuneTellingSetting $settings,
        string $systemMessage,
        string $userMessage
    ): ?array {
        try {
            $poolService = app(\App\Services\AiApiKeyPoolService::class);
            $key = $poolService->acquireKeyAnyProvider('chat');

            if (! $key) {
                return null;
            }

            // ใช้ override params ของ chatWithCustomSystemPrompt — ส่ง Pool key/provider/model
            //   เดิม: overrideForPlayground modifies $this->provider (DEEP path) ไม่ส่งผลกับ chat
            //   ใหม่: pass อย่าง explicit ผ่าน params → chat path เห็น Pool key ตรงๆ
            return $aiService->chatWithCustomSystemPrompt(
                $systemMessage,
                $userMessage,
                [
                    'temperature' => 0.3,
                    'max_tokens' => 600,
                ],
                $key->provider,
                $key->model,
                $key->api_key
            );
        } catch (\Throwable $e) {
            Log::warning('ExtractCustomerPersonaJob: Pool fallback ล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 📋 สร้าง context ของ persona ปัจจุบัน เพื่อให้ AI ไม่ extract ซ้ำของเดิม
     */
    private function buildExistingContext(FortuneCustomerPersona $persona): string
    {
        $parts = [];

        if (! empty($persona->traits)) {
            $parts[] = 'traits ที่รู้แล้ว: ' . implode(', ', $persona->traits);
        }
        if (! empty($persona->likes)) {
            $parts[] = 'likes ที่รู้แล้ว: ' . implode(', ', $persona->likes);
        }
        if (! empty($persona->dislikes)) {
            $parts[] = 'dislikes ที่รู้แล้ว: ' . implode(', ', $persona->dislikes);
        }
        $demo = $persona->demographics ?? [];
        if (! empty($demo)) {
            $demoFlat = [];
            foreach ($demo as $k => $v) {
                if ($v && $v !== 'unknown') {
                    $demoFlat[] = "{$k}={$v}";
                }
            }
            if (! empty($demoFlat)) {
                $parts[] = 'demographics ที่รู้แล้ว: ' . implode(', ', $demoFlat);
            }
        }

        return empty($parts) ? '(ไม่มีข้อมูลเดิม — ลูกค้าใหม่)' : implode("\n", $parts);
    }

    /**
     * 🎯 สร้าง [system_message, user_message] tuple สำหรับ extraction
     *
     * Pattern: แยก system (task definition + schema) ออกจาก user (data to analyze)
     *   - System: บทบาท + schema + กฎ JSON only
     *   - User: existing context + new message
     *
     * @return array [string $systemMessage, string $userMessage]
     */
    private function buildExtractionMessages(string $existingContext, string $messageText): array
    {
        $systemMessage = <<<'SYSTEM'
You are a Customer Persona Extraction system. Your ONLY job is to analyze customer messages and output structured JSON.

🚫 STRICT RULES:
1. Output JSON ONLY — no explanations, no markdown code blocks, no greetings
2. Do NOT respond conversationally — you are a data extraction service, not a chat bot
3. If unsure about a field → use "unknown" or empty array []
4. Do NOT invent information not present in the message
5. Reply in Thai for content arrays (traits/likes/dislikes/themes) but use English enum values

📋 JSON Schema (output EXACTLY this structure):
{
  "traits": ["บุคลิก1", "บุคลิก2"],
  "likes": ["สิ่งที่ชอบ1"],
  "dislikes": ["สิ่งที่ไม่ชอบ1"],
  "conversation_themes": ["หัวข้อที่คุย1"],
  "demographics": {
    "age_range": "18-25"|"26-35"|"36-45"|"46-55"|"56+"|"unknown",
    "gender_hint": "male"|"female"|"non_binary"|"unknown",
    "job_hint": "freelance"|"employee"|"business_owner"|"student"|"unemployed"|"unknown",
    "location_hint": "bangkok"|"north"|"northeast"|"south"|"central"|"unknown"
  },
  "communication_style": {
    "tone": "warm"|"casual"|"formal"|"emotional"|"reserved",
    "pace": "fast"|"slow"|"medium",
    "formality": "informal"|"polite"|"formal",
    "emoji_usage": "high"|"medium"|"low"|"none"
  },
  "risk_flags": {
    "mental_fragile": true|false,
    "over_emotional": true|false,
    "quiet_listener": true|false,
    "abusive_tone": true|false,
    "decline_pusher": true|false,
    "scam_victim": true|false,
    "complaint_prone": true|false,
    "disruptive_troll": true|false,
    "hostile_superior": true|false,
    "tarot_literate": true|false
  },
  "topic_tags": ["english-tag-1", "english-tag-2"]
}

🚩 RISK FLAGS DETECTION (CRITICAL — set true ONLY if strong evidence):
- mental_fragile: ลูกค้าพูดถึง self-harm/ฆ่าตัวตาย/ดื้อยา/ซึมเศร้ารุนแรง/หมดหวัง/อยากตาย
- over_emotional: ภาษาทุกข์หนัก ร้องไห้ "ทำยังไง" "ไม่ไหวแล้ว" หลายคำในข้อความเดียว
- quiet_listener: ตอบสั้นเสมอ ≤3 ตัวอักษร เช่น "อืม" "ค่ะ" "ครับ" ติดต่อกัน
- abusive_tone: คำหยาบ ด่า ก้าวร้าว ต่อว่าบอท/แอดมิน
- decline_pusher: บอกว่า "ไม่พร้อม/ไว้คราวหน้า/ไม่จ่าย" แล้วยังถามต่อให้ลดราคา/ฟรี
- scam_victim: เล่าโดนหลอกเงิน/มิจฉาชีพ/กู้เงินแล้วโดนโกง
- complaint_prone: บ่น/ติ/โกรธบริการ/ผิดหวัง ในข้อความนี้
- disruptive_troll: มากวนป่วน/เพ้อเจ้อ/เล่าเรื่องเหนือธรรมชาติฟุ้งซ่าน/ส่งข้อความไม่เกี่ยวดูดวง+provoke 3+ turn
                    (e.g. "เจอผีบอก..." / random one-liners / probing บอท)
- hostile_superior: โต้แย้งหาเรื่อง/คิดว่ารู้เหนือกว่า/กล่าวหา "หลอก/มั่ว/ปลอม"/philosophical แบบเหยียดบอท
                    (e.g. "หาแดกง่ายนะ" / "เริ่มมั่วแล้ว" / "ดูที่เจตนานะ" + คำหยาบ)
- tarot_literate: ลูกค้าเป็น tarot reader/เรียนไพ่/รู้ความหมายไพ่ — บ่นบอท "อ่านเหมือนตำรา/เพิ่งหัด/ขั้นต้น"
                   หรือพูดถึงประสบการณ์อ่านไพ่เอง (e.g. "ตอนเรียนไพ่ก็อ่านแบบนี้" / "เคยอ่านไพ่มาก่อน"
                   / "อ่านไพ่เอง" / "เห็นไพ่แล้วตีความเองได้" / "เป็นเพื่อนร่วมศาสตร์")

⚠️ STICKY: false = ไม่มี evidence ในข้อความนี้ (ไม่ใช่ "ลบ flag")
    System จะไม่ทับ true เดิม → ปลอดภัย flag false ทุกครั้งที่ไม่มี evidence

🎯 EXAMPLES:
Input: "เพิ่งเลิกกับแฟน เครียดมาก ทำงานบริษัทแต่อยากออก อายุ 28"
Output:
{"traits":["เครียด","ลังเล"],"likes":[],"dislikes":["บริษัท"],"conversation_themes":["ความรัก","งาน"],"demographics":{"age_range":"26-35","gender_hint":"unknown","job_hint":"employee","location_hint":"unknown"},"communication_style":{"tone":"emotional","pace":"medium","formality":"informal","emoji_usage":"none"},"risk_flags":{"mental_fragile":false,"over_emotional":false,"quiet_listener":false,"abusive_tone":false,"decline_pusher":false,"scam_victim":false,"complaint_prone":false},"topic_tags":["love","work-stress","career-change"]}

Input: "เคยคิดฆ่าตัวตาย 3 ครั้ง กินยานอนหลับทุกวันจนดื้อยา เงินเหลือแค่ 4 บาท"
Output:
{"traits":["สิ้นหวัง","กดดัน"],"likes":[],"dislikes":[],"conversation_themes":["การเงิน","สุขภาพจิต"],"demographics":{"age_range":"unknown","gender_hint":"unknown","job_hint":"unknown","location_hint":"unknown"},"communication_style":{"tone":"emotional","pace":"slow","formality":"polite","emoji_usage":"none"},"risk_flags":{"mental_fragile":true,"over_emotional":true,"quiet_listener":false,"abusive_tone":false,"decline_pusher":false,"scam_victim":false,"complaint_prone":false},"topic_tags":["crisis","financial-distress","mental-health"]}

Input: "ดีค่ะ หมอ ❤️❤️ ขอดูดวงด่วน"
Output:
{"traits":["รีบ","อบอุ่น"],"likes":[],"dislikes":[],"conversation_themes":[],"demographics":{"age_range":"unknown","gender_hint":"female","job_hint":"unknown","location_hint":"unknown"},"communication_style":{"tone":"warm","pace":"fast","formality":"polite","emoji_usage":"high"},"risk_flags":{"mental_fragile":false,"over_emotional":false,"quiet_listener":false,"abusive_tone":false,"decline_pusher":false,"scam_victim":false,"complaint_prone":false,"disruptive_troll":false,"hostile_superior":false,"tarot_literate":false},"topic_tags":[]}

Input: "เหมือนคนเพิ่งเรียนอ่านไพ่เลย ตอนเรียนใหม่ก็อ่านแบบนี้ค่ะ"
Output:
{"traits":["มีประสบการณ์","วิจารณ์ตรง"],"likes":["ทาโรต์"],"dislikes":["ตำรา"],"conversation_themes":["ไพ่","วิจารณ์การทำนาย"],"demographics":{"age_range":"unknown","gender_hint":"unknown","job_hint":"unknown","location_hint":"unknown"},"communication_style":{"tone":"reserved","pace":"medium","formality":"informal","emoji_usage":"none"},"risk_flags":{"mental_fragile":false,"over_emotional":false,"quiet_listener":false,"abusive_tone":false,"decline_pusher":false,"scam_victim":false,"complaint_prone":true,"disruptive_troll":false,"hostile_superior":false,"tarot_literate":true},"topic_tags":["tarot-reader","critic"]}

OUTPUT JSON NOW. NOTHING ELSE.
SYSTEM;

        $userMessage = "Existing persona data (DO NOT repeat in output):\n{$existingContext}\n\n"
            ."New customer message to analyze:\n\"{$messageText}\"\n\n"
            .'Output JSON:';

        return [$systemMessage, $userMessage];
    }

    /**
     * 🧩 Parse JSON จาก AI response (robust — ยอม markdown code block)
     */
    private function parseExtractedJson(string $response): array
    {
        // ลบ markdown code block (ถ้ามี)
        $clean = trim($response);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/```\s*$/', '', $clean);

        // หา JSON object first
        if (preg_match('/\{.*\}/s', $clean, $m)) {
            $clean = $m[0];
        }

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            return [];
        }

        // Sanitize — remove "unknown" values from arrays
        foreach (['traits', 'likes', 'dislikes', 'conversation_themes', 'topic_tags'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                $decoded[$key] = array_values(array_filter($decoded[$key], function ($v) {
                    return ! empty($v) && strtolower((string) $v) !== 'unknown';
                }));
            }
        }

        // (2026-05-15) Filter "unknown" จาก object fields ด้วย — กัน data corruption
        //   เคสเดิม: รอบ 1 เก็บ {age_range:26-35, gender:female}.
        //   รอบ 2 AI extract {age_range:unknown, gender:unknown} → array_merge ทับของเดิม
        //   → persona ลืมข้อมูลที่รู้แล้ว
        //   แก้: filter unknown ก่อน merge — ถ้า filter หมด → unset key ทั้งหมด (mergeData skip)
        foreach (['demographics', 'communication_style'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                $decoded[$key] = array_filter($decoded[$key], function ($v) {
                    return $v !== null && $v !== '' && strtolower((string) $v) !== 'unknown';
                });
                if (empty($decoded[$key])) {
                    unset($decoded[$key]);
                }
            }
        }

        return $decoded;
    }

    public function displayName(): string
    {
        return "ExtractCustomerPersona[{$this->platform}:{$this->userId}]";
    }

    public function tags(): array
    {
        return [
            'persona-extraction',
            "platform:{$this->platform}",
            "user:{$this->userId}",
        ];
    }
}
