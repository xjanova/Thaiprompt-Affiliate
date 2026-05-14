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
 *  1. ขอ AI Pool key (chat purpose) จาก FortuneTellingSetting
 *  2. Build extraction prompt — schema-strict JSON output
 *  3. Parse JSON → merge เข้า persona record
 *  4. Invalidate cache → AI inject ใหม่จะเห็น update
 *
 * Cost control:
 *  - Throttle ใน Service::dispatchExtraction() (30 นาที/user)
 *  - ใช้ generateChatResponse() ของ FortuneAIService ที่มี Pool acquire อยู่แล้ว
 *  - Failure ไม่ทำให้ chat flow แตก (non-blocking)
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

        // 📝 Build extraction prompt
        $existingContext = $this->buildExistingContext($persona);
        $extractionPrompt = $this->buildExtractionPrompt($existingContext, $this->messageText);

        // 🤖 Call AI (ใช้ generateChatResponse — มี Pool acquire + provider router อยู่แล้ว)
        try {
            $settings = FortuneTellingSetting::getSettings();
            $aiService = new FortuneAIService($settings);

            // Override system message ด้วย extraction prompt — ส่ง messageText เป็น user message
            //   generateChatResponse จะ append directives เยอะ → เราต้องใช้ inline approach
            //   เรียก raw ผ่าน method ที่ accept system override
            //   ง่ายสุด: send extraction prompt as "userProfile.persona_extraction" hint + message = full prompt
            $result = $aiService->generateChatResponse(
                $extractionPrompt,
                ['name' => $this->displayName ?? '', 'persona_extraction_mode' => true]
            );

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
     * 🎯 สร้าง prompt สำหรับ extraction
     *
     * ให้ AI ตอบ JSON เท่านั้น — ห้ามมีคำอธิบาย
     */
    private function buildExtractionPrompt(string $existingContext, string $messageText): string
    {
        return <<<PROMPT
[👤 PERSONA EXTRACTION TASK — ห้ามตอบเหมือนแชทปกติ]

หน้าที่: วิเคราะห์ข้อความของลูกค้า → extract เป็น JSON

ข้อมูลที่รู้แล้ว (ห้ามใส่ซ้ำ):
{$existingContext}

ข้อความของลูกค้าที่ต้องวิเคราะห์:
"{$messageText}"

ภารกิจของคุณ:
1. อ่านข้อความ → สังเกตบุคลิก, ความชอบ, ไม่ชอบ, demographics, สไตล์การคุย
2. **ตอบ JSON เท่านั้น** — ห้ามมีคำอธิบาย, ห้ามมี markdown code block, ห้าม "ผมเข้าใจแล้ว"
3. ถ้าไม่แน่ใจ → ใส่ "unknown" หรือ array ว่าง [] — ห้ามเดามั่ว

Schema ที่ต้องตอบ (JSON เท่านั้น):
```
{
  "traits": ["..."],
  "likes": ["..."],
  "dislikes": ["..."],
  "conversation_themes": ["..."],
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
  "topic_tags": ["love", "work-stress", "family", ...]
}
```

⚠️ **เน้นย้ำ — ตอบ JSON ดิบเท่านั้น ห้ามมีอะไรอย่างอื่น**:
PROMPT;
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
