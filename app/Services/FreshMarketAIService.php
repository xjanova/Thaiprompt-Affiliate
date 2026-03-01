<?php

namespace App\Services;

use App\Models\AiApiKey;
use App\Models\FreshMarketSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketAIService - บริการ AI สำหรับตลาดสดไทยพร้อม
 *
 * ใช้ Groq Pool เดียวกับระบบดูดวง (AiApiKeyPoolService)
 * AI ชื่อ "พี่ตลาด" - ผู้ช่วยตลาดสด
 * ตาม pattern ของ FortuneAIService
 */
class FreshMarketAIService
{
    protected FreshMarketSetting $settings;

    protected string $provider;

    protected string $apiKey;

    protected string $model;

    protected ?AiApiKeyPoolService $poolService = null;

    protected ?AiApiKey $currentKey = null;

    public function __construct(?FreshMarketSetting $settings = null)
    {
        $this->settings = $settings ?? FreshMarketSetting::getSettings();

        // ดึง provider/model จาก settings
        $this->provider = $this->settings->ai_provider ?? 'groq';
        $this->model = $this->settings->ai_model ?? 'llama-3.3-70b-versatile';

        // ใช้ API Key Pool (เดียวกับดูดวง)
        try {
            $this->poolService = new AiApiKeyPoolService;
            $this->currentKey = $this->poolService->getKey($this->provider);
        } catch (\Exception $e) {
            Log::warning('FreshMarketAI: Pool service ไม่พร้อม', ['error' => $e->getMessage()]);
            $this->poolService = null;
            $this->currentKey = null;
        }

        $this->apiKey = $this->currentKey?->api_key ?? '';
    }

    /**
     * สร้างคำตอบจาก AI
     *
     * @param  string  $userMessage  ข้อความจากผู้ใช้
     * @param  array  $conversationHistory  ประวัติสนทนา [{role, content}]
     * @param  array  $context  บริบทเพิ่มเติม (สินค้าใกล้ตัว, สถานะ)
     * @return array{response: string, tokens_used: int, provider: string, model: string}
     */
    public function generateResponse(string $userMessage, array $conversationHistory = [], array $context = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($context);

        // เตรียม messages สำหรับ AI
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // เพิ่มประวัติสนทนา (จำกัดไม่เกิน 10 คู่)
        foreach (array_slice($conversationHistory, -20) as $msg) {
            $messages[] = $msg;
        }

        // เพิ่มข้อความปัจจุบัน
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $result = $this->callAI($messages);

            // บันทึกการใช้งาน key (สำเร็จ)
            if ($this->currentKey && $this->poolService) {
                $this->poolService->markKeyUsed($this->currentKey, true);
            }

            return $result;
        } catch (\Exception $e) {
            // บันทึก error
            if ($this->currentKey && $this->poolService) {
                $this->poolService->markKeyUsed($this->currentKey, false, $e->getMessage());
            }

            // ลอง key ถัดไป
            return $this->retryWithNextKey($messages, $e);
        }
    }

    /**
     * แปลงข้อความแชทเป็นข้อมูลสินค้า (AI Parse Listing)
     *
     * @param  array  $chatMessages  ข้อความที่มีรายละเอียดสินค้า
     * @return array|null ข้อมูลสินค้า (title, price, unit, description) หรือ null ถ้าแปลงไม่ได้
     */
    public function parseListingFromChat(array $chatMessages): ?array
    {
        $systemPrompt = <<<'PROMPT'
คุณคือระบบแปลงข้อความแชทเป็นข้อมูลสินค้า ให้ตอบเป็น JSON เท่านั้น

จากข้อความที่ผู้ขายพิมพ์มา สกัดข้อมูลต่อไปนี้:
{
  "title": "ชื่อสินค้า",
  "price": 0,
  "unit": "หน่วย (กก., ถุง, กำ, ชิ้น, ลูก, แพ็ค)",
  "description": "คำอธิบายสั้นๆ",
  "category_hint": "หมวดหมู่ที่น่าจะเป็น (ผักสด/ผลไม้/เนื้อสัตว์/อาหารทะเล/ของแห้ง/ขนม/เครื่องดื่ม/อาหารปรุงสำเร็จ)",
  "is_organic": false,
  "freshness_level": "สด"
}

ถ้าข้อมูลไม่เพียงพอ ตอบ null
ตอบเฉพาะ JSON ห้ามมีข้อความอื่น
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($chatMessages as $msg) {
            $messages[] = $msg;
        }

        try {
            $result = $this->callAI($messages, 500);
            $response = trim($result['response']);

            // แปลง JSON
            if ($response === 'null') {
                return null;
            }

            // ลบ markdown code block ถ้ามี
            $response = preg_replace('/^```json\s*|```$/m', '', $response);

            $data = json_decode(trim($response), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('FreshMarketAI: แปลง JSON listing ไม่สำเร็จ', [
                    'response' => $response,
                ]);

                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('FreshMarketAI: Error parsing listing', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * แปลงความต้องการผู้ซื้อเป็น search filters
     *
     * @param  string  $message  ข้อความจากผู้ซื้อ
     * @return array search filters (query, category, max_price, etc.)
     */
    public function parseSearchIntent(string $message): array
    {
        $systemPrompt = <<<'PROMPT'
จากข้อความผู้ซื้อ สกัด search filters เป็น JSON:
{
  "query": "คำค้นหา (ถ้ามี)",
  "category_hint": "หมวดหมู่ (ผักสด/ผลไม้/เนื้อสัตว์/อาหารทะเล/ของแห้ง/ขนม/เครื่องดื่ม/อาหารปรุงสำเร็จ/null)",
  "max_price": null,
  "prefer_organic": false,
  "quantity_hint": null,
  "intent": "search|browse|order|question"
}

ตอบเฉพาะ JSON เท่านั้น
PROMPT;

        try {
            $result = $this->callAI([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ], 300);

            $response = trim($result['response']);
            $response = preg_replace('/^```json\s*|```$/m', '', $response);

            $data = json_decode(trim($response), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['query' => $message, 'intent' => 'search'];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('FreshMarketAI: Error parsing search intent', ['error' => $e->getMessage()]);

            return ['query' => $message, 'intent' => 'search'];
        }
    }

    // ===== Internal Methods =====

    /**
     * สร้าง System Prompt พร้อมบริบท
     */
    protected function buildSystemPrompt(array $context = []): string
    {
        $basePrompt = $this->settings->ai_system_prompt ?? 'คุณคือ "พี่ตลาด" ผู้ช่วย AI ของตลาดสดไทยพร้อม ตอบภาษาไทย';

        // เพิ่มบริบท
        $contextParts = [];

        if (! empty($context['role'])) {
            $contextParts[] = "ผู้ใช้คนนี้เป็น: {$context['role']}";
        }

        if (! empty($context['conversation_state'])) {
            $contextParts[] = "สถานะการสนทนา: {$context['conversation_state']}";
        }

        if (! empty($context['nearby_listings'])) {
            $contextParts[] = "สินค้าใกล้ตัวที่มี:\n{$context['nearby_listings']}";
        }

        if (! empty($context['current_order'])) {
            $contextParts[] = "ออเดอร์ปัจจุบัน:\n{$context['current_order']}";
        }

        if (! empty($contextParts)) {
            $basePrompt .= "\n\n--- บริบทปัจจุบัน ---\n".implode("\n", $contextParts);
        }

        return $basePrompt;
    }

    /**
     * เรียก AI API (Groq / OpenRouter / etc.)
     */
    protected function callAI(array $messages, int $maxTokens = 1024): array
    {
        $endpoint = $this->getApiEndpoint();
        $headers = $this->getApiHeaders();

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
        ];

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new \Exception("AI API error: {$response->status()} - {$response->body()}");
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $data['usage']['total_tokens'] ?? 0;

        return [
            'response' => $content,
            'tokens_used' => $tokensUsed,
            'provider' => $this->provider,
            'model' => $this->model,
        ];
    }

    /**
     * ลอง key ถัดไปเมื่อ key ปัจจุบันมีปัญหา
     */
    protected function retryWithNextKey(array $messages, \Exception $previousError): array
    {
        if (! $this->poolService) {
            throw $previousError;
        }

        try {
            $this->currentKey = $this->poolService->getKey($this->provider);
            if (! $this->currentKey) {
                throw $previousError;
            }

            $this->apiKey = $this->currentKey->api_key;
            $result = $this->callAI($messages);

            $this->poolService->markKeyUsed($this->currentKey, true);

            return $result;
        } catch (\Exception $e) {
            Log::error('FreshMarketAI: Retry ก็ล้มเหลว', [
                'error' => $e->getMessage(),
                'original_error' => $previousError->getMessage(),
            ]);

            // ตอบ fallback
            return [
                'response' => 'ขอโทษค่ะ ตอนนี้ระบบ AI ไม่พร้อมใช้งาน กรุณาลองใหม่อีกครั้งค่ะ 🙏',
                'tokens_used' => 0,
                'provider' => $this->provider,
                'model' => $this->model,
            ];
        }
    }

    /**
     * API Endpoint ตาม provider
     */
    protected function getApiEndpoint(): string
    {
        return match ($this->provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'openai' => 'https://api.openai.com/v1/chat/completions',
            default => 'https://api.groq.com/openai/v1/chat/completions',
        };
    }

    /**
     * Headers ตาม provider
     */
    protected function getApiHeaders(): array
    {
        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ];

        if ($this->provider === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
            $headers['X-Title'] = 'ตลาดสดไทยพร้อม';
        }

        return $headers;
    }
}
