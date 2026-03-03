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
 *
 * v2: State-aware prompts — AI รู้สถานะปัจจุบัน
 * ตอบเฉพาะสิ่งที่เกี่ยวข้องกับขั้นตอนที่กำลังทำ
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

        $this->provider = $this->settings->ai_provider ?? 'groq';
        $this->model = $this->settings->ai_model ?? 'llama-3.3-70b-versatile';

        // ใช้ API Key Pool
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
     * สร้างคำตอบจาก AI (state-aware)
     *
     * @param string $userMessage ข้อความจากผู้ใช้
     * @param array $conversationHistory ประวัติสนทนา
     * @param array $context บริบท (role, conversation_state, nearby_listings, etc.)
     * @return array{response: string, tokens_used: int, provider: string, model: string}
     */
    public function generateResponse(string $userMessage, array $conversationHistory = [], array $context = []): array
    {
        $state = $context['conversation_state'] ?? 'idle';
        $systemPrompt = $this->buildStateAwarePrompt($state, $context);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // เพิ่มประวัติสนทนา (จำกัดตาม settings)
        foreach (array_slice($conversationHistory, -12) as $msg) {
            $messages[] = $msg;
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $result = $this->callAI($messages);

            return $result;
        } catch (\Exception $e) {
            Log::warning('FreshMarketAI: callAI ล้มเหลว, ลอง key ถัดไป', [
                'error' => $e->getMessage(),
            ]);

            return $this->retryWithNextKey($messages, $e);
        }
    }

    /**
     * สกัดข้อมูลสินค้าจากข้อความเดียว (สำหรับ listing_details state)
     *
     * @param string $message ข้อความจากผู้ขาย
     * @param array $existingData ข้อมูลที่มีอยู่แล้ว (จาก context)
     * @return array|null {title, price, unit, description, category_hint, is_organic, complete}
     */
    public function parseListingDetailsFromText(string $message, array $existingData = []): ?array
    {
        $existingJson = ! empty($existingData) ? json_encode(array_filter([
            'title' => $existingData['title'] ?? null,
            'price' => $existingData['price'] ?? null,
            'unit' => $existingData['unit'] ?? null,
        ]), JSON_UNESCAPED_UNICODE) : 'ไม่มี';

        $systemPrompt = <<<PROMPT
คุณคือระบบสกัดข้อมูลสินค้าจากข้อความแชท ตอบเป็น JSON เท่านั้น

ข้อมูลที่มีอยู่แล้ว: {$existingJson}

จากข้อความผู้ขาย สกัดข้อมูล:
{
  "title": "ชื่อสินค้า",
  "price": 0,
  "unit": "หน่วย (กก./ถุง/กำ/ชิ้น/ลูก/แพ็ค/กล่อง/ขวด)",
  "description": "คำอธิบายสั้นๆ (ถ้ามี)",
  "category_hint": "หมวดหมู่ (ผักสด/ผลไม้/เนื้อสัตว์/อาหารทะเล/ของแห้ง/ขนม/เครื่องดื่ม/อาหารปรุงสำเร็จ)",
  "is_organic": false
}

กฎ:
- ราคา: ถ้าผู้ขายพิมพ์ "กิโลละ 120" → price=120, unit="กก."
- ถ้าพิมพ์ "ถุงละ 50" → price=50, unit="ถุง"
- ถ้าพิมพ์ "ออแกนิก" หรือ "ปลอดสาร" → is_organic=true
- ถ้าข้อมูลไม่พอ (ไม่มีชื่อหรือราคา) ตอบ null
- ตอบเฉพาะ JSON เท่านั้น ห้ามมีข้อความอื่น
PROMPT;

        try {
            $result = $this->callAI([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ], 500);

            $response = trim($result['response']);

            if ($response === 'null' || $response === '{}') {
                return null;
            }

            $response = preg_replace('/^```json\s*|```$/m', '', $response);
            $data = json_decode(trim($response), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('FreshMarketAI: parse listing details JSON ล้มเหลว', [
                    'response' => $response,
                ]);

                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('FreshMarketAI: Error parsing listing details', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * แปลงข้อความแชทเป็นข้อมูลสินค้า (backward compatibility)
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

            if ($response === 'null') {
                return null;
            }

            $response = preg_replace('/^```json\s*|```$/m', '', $response);
            $data = json_decode(trim($response), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
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
            return ['query' => $message, 'intent' => 'search'];
        }
    }

    // ===== Internal Methods =====

    /**
     * สร้าง System Prompt ตามสถานะปัจจุบัน (State-Aware)
     *
     * AI รู้ว่ากำลังอยู่ขั้นตอนไหน และตอบเฉพาะสิ่งที่เกี่ยวข้อง
     */
    protected function buildStateAwarePrompt(string $state, array $context = []): string
    {
        $basePrompt = $this->settings->ai_system_prompt
            ?? 'คุณคือ "พี่ตลาด" ผู้ช่วย AI ของตลาดสดไทยพร้อม ตอบภาษาไทย สั้นกระชับ ใจดี เป็นกันเอง';

        // === Bot Identity & Personality ===
        $botName = $this->settings->bot_name ?? 'พี่ตลาด';
        $personality = $this->settings->bot_personality ?? '';
        $style = $this->settings->bot_response_style ?? 'friendly';

        $base = "คุณคือ \"{$botName}\" สไตล์: {$style}";
        if ($personality) {
            $base .= "\nบุคลิกภาพ: {$personality}";
        }
        $base .= "\n\n".$basePrompt;

        // === AI Scope / Boundaries ===
        if ($scopeDesc = $this->settings->ai_scope_description) {
            $base .= "\n\nขอบเขตการตอบ: {$scopeDesc}";
        }
        if ($allowedTopics = $this->settings->ai_allowed_topics) {
            if (is_array($allowedTopics) && ! empty($allowedTopics)) {
                $base .= "\nหัวข้อที่ตอบได้: ".implode(', ', $allowedTopics);
            }
        }
        if ($blockedTopics = $this->settings->ai_blocked_topics) {
            if (is_array($blockedTopics) && ! empty($blockedTopics)) {
                $offTopicMsg = $this->settings->ai_off_topic_message ?? 'ขอโทษค่ะ หัวข้อนี้อยู่นอกขอบเขตบริการค่ะ';
                $base .= "\nหัวข้อที่ห้ามตอบ: ".implode(', ', $blockedTopics);
                $base .= "\nเมื่อถูกถามเรื่องที่ห้าม ตอบว่า: \"{$offTopicMsg}\"";
            }
        }

        // === Data Access Controls ===
        $dataAccessParts = [];
        if ($this->settings->ai_can_access_listings ?? true) {
            $dataAccessParts[] = 'รายการสินค้า';
        }
        if ($this->settings->ai_can_access_pricing ?? true) {
            $dataAccessParts[] = 'ราคา';
        }
        if (! ($this->settings->ai_can_access_orders ?? false)) {
            $dataAccessParts[] = 'ห้ามเปิดเผยข้อมูลคำสั่งซื้อ';
        }
        if (! ($this->settings->ai_can_access_user_profile ?? false)) {
            $dataAccessParts[] = 'ห้ามเปิดเผยข้อมูลส่วนตัวผู้ใช้';
        }
        if (! empty($dataAccessParts)) {
            $base .= "\n\nสิทธิ์ข้อมูล: ".implode(', ', $dataAccessParts);
        }

        // === AI Dynamic Buttons (Structured JSON Response) ===
        if ($this->settings->ai_can_suggest_buttons ?? true) {
            $maxButtons = $this->settings->ai_max_buttons ?? 4;
            $base .= "\n\nตอบ JSON: {\"text\":\"ข้อความ\",\"buttons\":[\"ปุ่ม1\",\"🔙 กลับเมนู\"]}";
            $base .= "\nปุ่มสูงสุด {$maxButtons} ไม่เกิน 20 ตัวอักษร ปุ่มสุดท้ายเป็น \"🔙 กลับเมนู\" เสมอ";
        }

        $statePrompt = match ($state) {
            'idle' => 'idle: แนะนำ "ลงขาย" หรือ "อยากซื้อ" ตอบสั้นไม่เกิน 2 บรรทัด',
            'seller_phone' => 'รอเบอร์โทร 10 หลัก โฟกัสเรื่องเบอร์เท่านั้น',
            'seller_otp' => 'รอ OTP 6 หลัก พิมพ์ "ส่งใหม่" ขอรหัสใหม่ได้',
            'listing_photos' => 'รับรูปสินค้า ('.($context['listing_data']['image_count'] ?? 0).' รูปแล้ว) พิมพ์ "เสร็จ" เมื่อครบ',
            'listing_details' => 'รอข้อมูลสินค้า: ชื่อ ราคา หน่วย ตอบไทยปกติไม่ต้อง JSON',
            'listing_location' => 'รอพิกัดร้าน กดส่งตำแหน่งใน LINE',
            'listing_review' => 'ยืนยันก่อนลงขาย ถามว่า "ยืนยัน" หรือ "แก้ไข" ข้อมูล: '.json_encode($context['listing_data'] ?? [], JSON_UNESCAPED_UNICODE),
            'search_location' => 'รอตำแหน่งผู้ซื้อ บอกให้ส่งตำแหน่ง',
            'search_browsing' => 'แสดงผลค้นหา ช่วยแนะนำสินค้า'.(! empty($context['nearby_listings']) ? "\n{$context['nearby_listings']}" : ''),
            'order_quantity' => 'รอจำนวนสั่งซื้อ: '.($context['order_data']['listing_title'] ?? '').' ฿'.($context['order_data']['listing_price'] ?? 0).'/'.($context['order_data']['listing_unit'] ?? '').' ตอบไทยปกติ',
            'order_review' => 'ตรวจสอบคำสั่งซื้อ ถาม "ยืนยัน" หรือ "ยกเลิก"',
            default => $state,
        };

        // เพิ่มบริบทผู้ใช้
        $contextParts = [];
        if (! empty($context['role'])) {
            $contextParts[] = "ผู้ใช้: {$context['role']}";
        }
        if (! empty($context['nearby_listings']) && $state === 'idle') {
            $contextParts[] = "สินค้าใกล้ตัว:\n{$context['nearby_listings']}";
        }

        $prompt = $base."\n\n--- สถานะ ---\n".$statePrompt;
        if (! empty($contextParts)) {
            $prompt .= "\n\n--- บริบท ---\n".implode("\n", $contextParts);
        }

        return $prompt;
    }

    /**
     * Backward compatibility: buildSystemPrompt
     */
    protected function buildSystemPrompt(array $context = []): string
    {
        $state = $context['conversation_state'] ?? 'idle';

        return $this->buildStateAwarePrompt($state, $context);
    }

    /**
     * เรียก AI API
     */
    protected function callAI(array $messages, int $maxTokens = 400): array
    {
        $endpoint = $this->getApiEndpoint();
        $headers = $this->getApiHeaders();

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => (float) ($this->settings->bot_temperature ?? 0.7),
        ];

        $response = Http::withHeaders($headers)
            ->connectTimeout(5)
            ->timeout(15)
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
     * ลอง key ถัดไป
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

            return $result;
        } catch (\Exception $e) {
            Log::error('FreshMarketAI: Retry ก็ล้มเหลว', [
                'error' => $e->getMessage(),
                'original_error' => $previousError->getMessage(),
            ]);

            return [
                'response' => 'ขอโทษค่ะ ตอนนี้ระบบ AI ไม่พร้อมใช้งาน กรุณาลองใหม่อีกครั้งค่ะ 🙏',
                'tokens_used' => 0,
                'provider' => $this->provider,
                'model' => $this->model,
            ];
        }
    }

    protected function getApiEndpoint(): string
    {
        return match ($this->provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'openai' => 'https://api.openai.com/v1/chat/completions',
            default => 'https://api.groq.com/openai/v1/chat/completions',
        };
    }

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
