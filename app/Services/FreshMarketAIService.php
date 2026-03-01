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

        // เพิ่มประวัติสนทนา (จำกัดไม่เกิน 10 คู่)
        foreach (array_slice($conversationHistory, -20) as $msg) {
            $messages[] = $msg;
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $result = $this->callAI($messages);

            if ($this->currentKey && $this->poolService) {
                $this->poolService->markKeyUsed($this->currentKey, true);
            }

            return $result;
        } catch (\Exception $e) {
            if ($this->currentKey && $this->poolService) {
                $this->poolService->markKeyUsed($this->currentKey, false, $e->getMessage());
            }

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
        $base = $this->settings->ai_system_prompt
            ?? 'คุณคือ "พี่ตลาด" ผู้ช่วย AI ของตลาดสดไทยพร้อม ตอบภาษาไทย สั้นกระชับ ใจดี เป็นกันเอง';

        $statePrompt = match ($state) {
            'idle' => implode("\n", [
                'ผู้ใช้ยังไม่ได้เริ่มทำอะไร',
                'แนะนำว่าจะ "ลงขาย" สินค้า หรือ "อยากซื้อ" ของ',
                'ถ้าผู้ใช้ถามเรื่องสินค้า ชวนให้ส่งตำแหน่งเพื่อหาของใกล้ตัว',
                'ตอบสั้นๆ กระชับ ไม่เกิน 3 บรรทัด',
            ]),

            'seller_phone' => implode("\n", [
                'สถานะ: รอเบอร์โทรศัพท์เพื่อยืนยันตัวตน',
                'ผู้ใช้ต้องพิมพ์เบอร์มือถือ 10 หลัก (เช่น 0812345678)',
                'ถ้าพิมพ์ผิดรูปแบบ แนะนำให้พิมพ์ใหม่',
                'ห้ามถามเรื่องอื่น โฟกัสเรื่องเบอร์โทรเท่านั้น',
            ]),

            'seller_otp' => implode("\n", [
                'สถานะ: รอรหัส OTP 6 หลัก (ส่งทาง SMS)',
                'ผู้ใช้ต้องพิมพ์ตัวเลข 6 หลักที่ได้รับ',
                'ถ้ารหัสผิด แนะนำให้พิมพ์ "ส่งใหม่" เพื่อขอรหัสใหม่',
                'พิมพ์ "เปลี่ยนเบอร์" ได้ถ้าต้องการเปลี่ยน',
            ]),

            'listing_photos' => implode("\n", [
                'สถานะ: กำลังรับรูปสินค้า (ขั้นตอนที่ 1/5)',
                'รูปที่ได้รับ: '.($context['listing_data']['image_count'] ?? 0).' รูป',
                'กระตุ้นให้ส่งรูปมาอีก หรือพิมพ์ "เสร็จ" เมื่อส่งรูปครบ',
                'ห้ามถามรายละเอียดสินค้า ห้ามถามราคา ให้รอรูปก่อน',
            ]),

            'listing_details' => implode("\n", [
                'สถานะ: รอข้อมูลสินค้า (ขั้นตอนที่ 2/5)',
                'ต้องการ: ชื่อสินค้า, ราคา, หน่วย (กก./ถุง/กำ/ชิ้น)',
                'ถ้าผู้ใช้พิมพ์ไม่ครบ ถามเฉพาะส่วนที่ขาด',
                'ตอบเป็นภาษาไทยปกติ ไม่ต้องตอบเป็น JSON',
            ]),

            'listing_location' => implode("\n", [
                'สถานะ: รอพิกัดร้าน (ขั้นตอนที่ 3/5)',
                'บอกผู้ใช้ให้กดปุ่มส่งตำแหน่ง (Location) ใน LINE',
                'ถ้าพิมพ์ "ข้าม" ใช้พิกัดร้านที่บันทึกไว้',
                'ห้ามถามเรื่องอื่น โฟกัสเรื่องพิกัดเท่านั้น',
            ]),

            'listing_review' => implode("\n", [
                'สถานะ: ยืนยันก่อนลงขาย (ขั้นตอนที่ 4/5)',
                'แสดงสรุปข้อมูลสินค้า ถามว่า "ยืนยัน" หรือ "แก้ไข"',
                'ข้อมูล: '.json_encode($context['listing_data'] ?? [], JSON_UNESCAPED_UNICODE),
            ]),

            'search_location' => implode("\n", [
                'สถานะ: รอตำแหน่งผู้ซื้อ (ขั้นตอนที่ 1/2)',
                'บอกให้ส่งตำแหน่งเพื่อหาสินค้าใกล้ตัว',
                'ห้ามเสนอสินค้า เพราะยังไม่รู้ตำแหน่ง',
            ]),

            'search_browsing' => implode("\n", [
                'สถานะ: แสดงผลค้นหา (ขั้นตอนที่ 2/2)',
                'ผู้ใช้กำลังดูสินค้า ช่วยแนะนำเพิ่มเติมได้',
                'ถ้าถามเรื่องสินค้า ตอบจากรายการที่มี',
                ! empty($context['nearby_listings']) ? "สินค้าใกล้ตัว:\n{$context['nearby_listings']}" : '',
            ]),

            'order_quantity' => implode("\n", [
                'สถานะ: รอจำนวนสั่งซื้อ (ขั้นตอนที่ 2/4)',
                'สินค้า: '.($context['order_data']['listing_title'] ?? '').
                    ' ฿'.($context['order_data']['listing_price'] ?? 0).
                    '/'.($context['order_data']['listing_unit'] ?? ''),
                'ถามจำนวนและวิธีรับ (นัดรับ/ส่ง)',
                'ตอบภาษาไทยปกติ ไม่ต้อง JSON',
            ]),

            'order_review' => implode("\n", [
                'สถานะ: ตรวจสอบคำสั่งซื้อ (ขั้นตอนที่ 3/4)',
                'ถามว่า "ยืนยัน" หรือ "ยกเลิก"',
            ]),

            default => "สถานะ: {$state}",
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

            $this->poolService->markKeyUsed($this->currentKey, true);

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
