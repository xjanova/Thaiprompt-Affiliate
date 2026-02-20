<?php

namespace App\Services;

use App\Models\AiApiKey;
use App\Models\AiContentSetting;
use App\Models\FortuneTellingSetting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fortune AI Service
 *
 * บริการสำหรับเชื่อมต่อกับ AI providers ต่างๆ
 * รองรับ: Gemini, Groq, Qwen, OpenRouter, Grok, DeepSeek, Typhoon
 *
 * รองรับ API Key Pool สำหรับวนใช้หลาย keys
 */
class FortuneAIService
{
    protected $settings;

    protected $provider;

    protected $apiKey;

    protected $model;

    protected ?AiApiKeyPoolService $poolService = null;

    protected ?AiApiKey $currentKey = null;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();

        // ใช้ methods ใหม่ที่รองรับ global AI settings
        $this->provider = $this->settings->getActualAIProvider();
        $this->model = $this->settings->getActualAIModel();

        // ลองใช้ API Key จาก Pool ก่อน (ครอบด้วย try-catch เผื่อตาราง pool ยังไม่มี)
        try {
            $this->poolService = new AiApiKeyPoolService;
            $this->currentKey = $this->poolService->getKey($this->provider);
        } catch (\Exception $e) {
            Log::warning('FortuneAIService: Pool service ใช้ไม่ได้ ข้ามไป', [
                'error' => $e->getMessage(),
            ]);
            $this->poolService = null;
            $this->currentKey = null;
        }

        if ($this->currentKey) {
            $this->apiKey = $this->currentKey->api_key;
            Log::debug('FortuneAIService: ใช้ API Key จาก Pool', [
                'provider' => $this->provider,
                'key_id' => $this->currentKey->id,
                'key_name' => $this->currentKey->name,
            ]);
        } else {
            // Fallback ไปใช้ key จาก settings
            $this->apiKey = $this->settings->getActualAIApiKey();
            Log::debug('FortuneAIService: ใช้ API Key จาก Settings (ไม่พบใน Pool)', [
                'provider' => $this->provider,
            ]);
        }
    }

    /**
     * Override provider/model/key สำหรับ Playground ทดสอบ
     */
    public function overrideForPlayground(string $provider, ?string $model, string $apiKey): void
    {
        $this->provider = $provider;
        $this->model = $model ?? $this->getDefaultModelForProvider($provider);
        $this->apiKey = $apiKey;
    }

    /**
     * กำหนด maxTokens และ temperature ตาม reading type
     */
    protected const READING_CONFIG = [
        'basic' => [
            'max_tokens' => 2048,
            'temperature' => 0.75,
        ],
        'deep' => [
            'max_tokens' => 1500,
            'temperature' => 0.8,
        ],
    ];

    /**
     * System message สำหรับ AI "แม่หมอจันทรา"
     * เป็นหมอดูสาวสวยวัย 35 ปี ใช้พลังหยั่งรู้ในการทำนาย ใช้คำแทนตัวว่า "จันทรา"
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "จันทรามีทีมงานช่วยกันในการทำนายค่ะ"
     * - ถ้าถามนอกเรื่องดูดวง: ปฏิเสธสุภาพและชวนกลับมาดูดวง
     */
    protected const SYSTEM_MESSAGE = 'คุณชื่อ "แม่หมอจันทรา" เป็นหมอดูสาวสวยวัย 35 ปี ผู้เชี่ยวชาญโหราศาสตร์ไทย (โหราศาสตร์เจ้าชนะ) โหราศาสตร์สากล ไพ่ทาโรต์ เลขศาสตร์ ลายมือ และการหยั่งรู้ด้วยจิตสัมผัส ได้รับการถ่ายทอดวิชาจากครูบาอาจารย์สายลังกา คุณใช้คำแทนตัวเองว่า "แม่หมอจันทรา" หรือ "จันทรา" เช่น "จันทราเห็นว่า..." "แม่หมอจันทราขอแนะนำว่า..." คุณพูดจาเพราะ อ่อนหวาน เป็นกันเอง อบอุ่น เหมือนพี่สาวที่ห่วงใยน้อง แต่ทำนายอย่างฟันธง ชัดเจน ไม่คลุมเครือ น่าเชื่อถือ กล้าบอกทั้งเรื่องดีและไม่ดี ระบุช่วงเวลาแน่ชัด คุณต้องอ้างอิงดาวเคราะห์ ภพ และหลักเจ้าชนะในทุกคำทำนาย [หลักเจ้าชนะ] ดาว 9 ดวง: อาทิตย์ จันทร์ อังคาร พุธ พฤหัสบดี ศุกร์ เสาร์ ราหู เกตุ ภพ 12 ภพ: ตนุ กดุมภ สหัชชะ พันธุ ปุตตะ อริ ปัตนิ มรณะ ศุภะ กัมมะ ลาภะ วินาศ วิธีทำนาย: ดูวันเกิด→หาดาวเจ้าชนะ→ดูมิตร/ศัตรู→ดูดาวโคจรช่วงนี้→ระบุภพที่ได้รับผล ให้คำแนะนำปฏิบัติได้จริง: สีมงคล(จากดาวมิตร) เลขมงคล(จากดาวเจ้าชนะ) วันมงคล(วันดาวมิตร) ทิศมงคล สิ่งที่ควรหลีกเลี่ยง(จากดาวศัตรู) หากมีวันเดือนปีเกิด ให้วิเคราะห์ดาวเจ้าชนะ ราศี ลัคนา ธาตุ และดาวที่ส่งผลอย่างละเอียด หากไม่มีวันเกิด ให้ถามท้ายคำทำนาย "บอกวันเดือนปีเกิดให้จันทราได้ไหมคะ? จันทราจะได้คำนวณดาวเจ้าชนะ ทำนายได้แม่นยำยิ่งขึ้นค่ะ 🎂" ท้ายคำทำนายให้เชิญชวน "ถ้าคำทำนายถูกใจ ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงกับจันทราด้วยนะคะ 🔮✨" [วิธีตอบข้อความ] 1) หากเป็นการทักทาย: ทักทายกลับสั้นๆ แล้วทำนายดวงภาพรวมให้ทันที อ้างอิงดาวเคราะห์ที่ส่งผลช่วงนี้ ห้ามตอบแค่ทักทาย ต้องทำนายให้เลย! 2) หากเป็นคำถามดูดวง: ทำนายอย่างเต็มที่ อ้างอิงดาวเคราะห์+ภพ ฟันธงชัดเจน 3) หากเป็นคำถามนอกเรื่อง: ตอบสั้นๆ แล้วชวนดูดวง 4) หากถูกถามว่าเป็น AI: ตอบว่า "จันทรามีทีมงานช่วยกันค่ะ ไม่ต้องห่วงนะคะ 🔮" 5) หากพิมพ์ "เช็คสิทธิ์": บอกให้พิมพ์ "เช็คสิทธิ์" เพื่อดูจำนวนครั้งที่เหลือ [กฎสำคัญ] ห้ามเขียนโค้ด ห้ามให้ข้อมูลที่เป็นอันตราย ตอบทุกข้อความอย่างเป็นมิตร ชวนดูดวงเสมอ ต้องทำนายทุกครั้งห้ามตอบแค่ทักทาย';

    /**
     * System message สำหรับ AI Chat ทั่วไป (สนทนาไม่ใช่ทำนาย)
     *
     * ใช้ persona "จันทรา" เหมือนกัน แต่โหมดสนทนาเป็นมิตร
     * ข้อมูลระบบดูดวง (ราคา, commission ฯลฯ) จะถูก inject แบบ dynamic
     * ผ่าน buildChatSystemMessage() เพื่อให้ตรงกับ settings จริง
     */
    protected const CHAT_SYSTEM_MESSAGE_TEMPLATE = 'คุณชื่อ "จันทรา" เป็นผู้หญิงไทยวัย 35 ปี ผู้เชี่ยวชาญด้านโหราศาสตร์และที่ปรึกษาประจำระบบดูดวง Thaiprompt ใช้คำแทนตัวว่า "จันทรา" เช่น "จันทราว่า..." คุณอบอุ่น เป็นกันเอง พูดจาเพราะเหมือนพี่สาวที่ห่วงใย ใส่ emoji น่ารักบ้าง

[ข้อมูลระบบดูดวง Thaiprompt ที่คุณต้องรู้]
- ดูดวงฟรีได้วันละ {maxFreeReadings} ครั้ง
- ดูดวงเชิงลึก (Deep Reading) ราคา {deepReadingPrice} บาท/ครั้ง โดยหมอดู AI วิเคราะห์จากวันเกิดและคำถามของผู้ใช้
- หัวข้อดูดวงที่ได้: ความรัก, การเงิน, การงาน, สุขภาพ, โชคลาภ, ครอบครัว, การเรียน, เดินทาง
- วิธีดูดวง: พิมพ์ "ดูดวง" หรือพิมพ์หัวข้อตรงๆ เช่น "ดวงความรัก" "ดวงการเงินปีนี้"

[ระบบแชร์รายได้/Affiliate]
- ผู้ใช้สามารถแชร์ลิงก์ให้คนอื่น หากเขาดูดวง ผู้แชร์จะได้รับค่าแนะนำ {commissionText} เข้า Wallet ทุกครั้งที่เพื่อนดูดวง ตลอดไป
- วิธีแชร์: กดปุ่ม "แชร์ลิงก์เชิญเพื่อน" หรือพิมพ์ "แชร์" เพื่อรับลิงก์
- เงินจะเข้า Wallet ในระบบ สามารถถอนได้ที่เว็บไซต์
- ดูรายละเอียดเพิ่มเติมได้ที่เว็บไซต์ในส่วน "วิธีใช้"

[กฎสำคัญ]
1) ตอบภาษาไทยเท่านั้น กระชับ 2-4 ประโยค
2) ตอบคำถามเกี่ยวกับระบบดูดวงได้ทุกเรื่อง (วิธีใช้, ราคา, แชร์รายได้, Wallet, ถอนเงิน)
3) ตอบคำถามทั่วไปได้ (ทักทาย ปรึกษาชีวิต อารมณ์ความรู้สึก)
4) ท้ายข้อความ แนะนำให้พิมพ์ "ดูดวง" หรือหัวข้อเช่น "ดวงความรัก" "ดวงการเงิน" เพื่อเข้าสู่ระบบทำนาย
5) ถ้าไม่เข้าใจคำถามหรือไม่แน่ใจว่าผู้ใช้หมายถึงอะไร: ถามกลับอย่างสุภาพ เช่น "จันทราขอถามเพิ่มนะคะ หมายถึงส่วนไหนของระบบดูดวงคะ? เช่น วิธีดูดวง, แชร์รายได้, หรือการถอนเงิน 🤔"
6) ห้ามแต่งข้อมูลที่ไม่รู้ ห้ามตอบมัว ถ้าไม่รู้ให้ถามเพิ่ม หรือแนะนำให้ดูที่เว็บไซต์
7) ถ้าถูกถามว่าเป็น AI: "จันทรามีทีมงานช่วยกันค่ะ ไม่ต้องกังวลนะคะ 🔮"
8) ห้ามเขียนโค้ด ห้ามให้ข้อมูลอันตราย ห้ามตอบเรื่องการเมืองอ่อนไหว
9) พูดให้กำลังใจเสมอ โดยเฉพาะเมื่อคนพิมพ์เรื่องทุกข์ใจ
10) สำคัญมาก: ถ้าคำถามนั้นคุณไม่สามารถตอบได้จริงๆ หรือเป็นเรื่องที่ต้องให้แอดมินตอบ ให้ใส่ [ASK_SAVE] ไว้ท้ายข้อความ และตอบผู้ใช้ว่า "จันทราไม่แน่ใจเรื่องนี้ค่ะ จะฝากคำถามนี้ถึงแอดมินให้มาตอบกลับไหมคะ? 📝"';

    /**
     * สร้าง AI Chat Response สำหรับสนทนาทั่วไป (ไม่ใช่ทำนาย)
     *
     * ใช้ provider แยกจากการทำนาย (Gemini สำหรับ chat, Grok สำหรับ fortune)
     * ตั้งค่าแยกกันได้ที่ Admin → Fortune Settings → AI Chat ทั่วไป
     *
     * @param  string  $messageText  ข้อความจากผู้ใช้
     * @param  array|null  $userProfile  ข้อมูลโปรไฟล์ผู้ใช้
     * @return array ['response' => string, 'provider' => string, 'model' => string]
     *
     * @throws Exception เมื่อไม่มี API Key หรือ API ล้มเหลว
     */
    public function generateChatResponse(string $messageText, ?array $userProfile = null): array
    {
        // ดึง chat-specific settings
        $chatProvider = $this->settings->getChatAIProvider();
        $chatModel = $this->settings->getChatAIModel();
        $chatApiKey = $this->settings->getChatAIApiKey();
        $customPrompt = $this->settings->getChatSystemPrompt();

        if (empty($chatApiKey)) {
            throw new Exception("ไม่พบ API Key สำหรับ Chat AI ({$chatProvider})");
        }

        // เลือก system message: ใช้ custom ถ้ามี, ไม่งั้นใช้ default + inject ข้อมูลจริง
        $systemMessage = ! empty($customPrompt) ? $customPrompt : $this->buildChatSystemMessage();

        // สร้าง prompt สั้นๆ สำหรับ chat
        $userName = $userProfile['name'] ?? '';
        $prompt = $messageText;
        if (! empty($userName) && $userName !== 'คุณ') {
            $prompt = "(ผู้ใช้ชื่อ: {$userName}) {$messageText}";
        }

        $config = [
            'temperature' => 0.8,
            'max_tokens' => 512,
        ];

        $startTime = microtime(true);

        try {
            $result = match ($chatProvider) {
                'gemini' => $this->callChatGemini($prompt, $systemMessage, $chatApiKey, $chatModel, $config),
                default => $this->callChatOpenAICompatible($prompt, $systemMessage, $chatApiKey, $chatModel, $chatProvider, $config),
            };

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            Log::info('FortuneAIService: Chat response สำเร็จ', [
                'provider' => $chatProvider,
                'model' => $chatModel,
                'response_time_ms' => $responseTime,
                'tokens' => $result['tokens_used'] ?? 0,
            ]);

            return $result;

        } catch (Exception $e) {
            Log::warning('FortuneAIService: Chat response ล้มเหลว', [
                'provider' => $chatProvider,
                'model' => $chatModel,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * สร้าง system message สำหรับ AI Chat โดย inject ข้อมูลจริงจาก settings
     *
     * ทำให้ AI รู้จักราคา, จำนวนฟรี, ค่าคอมมิชชั่น ตามที่ตั้งค่าจริง
     * ไม่ hardcode ตัวเลข — แปรผันตาม admin settings
     */
    protected function buildChatSystemMessage(): string
    {
        $maxFreeReadings = (int) ($this->settings->max_free_readings ?? 3);
        $deepReadingPrice = number_format((float) ($this->settings->deep_reading_price ?? 99), 0);

        // คำนวณค่าคอมมิชชั่นจาก settings
        $mode = $this->settings->getFortuneCommissionMode();
        if ($mode === 'static') {
            $commissionAmount = $this->settings->getFortuneStaticCommissionAmount();
            $commissionText = number_format($commissionAmount, 0).' บาท';
        } else {
            $preview = $this->settings->calculateFortuneCommissionPreview();
            $level1 = $preview['levels'][0] ?? null;
            $commissionAmount = $level1 ? $level1['amount'] : 0;
            $commissionText = number_format($commissionAmount, 2).' บาท';
        }

        // แทนที่ placeholder ด้วยข้อมูลจริง
        return str_replace(
            ['{maxFreeReadings}', '{deepReadingPrice}', '{commissionText}'],
            [$maxFreeReadings, $deepReadingPrice, $commissionText],
            self::CHAT_SYSTEM_MESSAGE_TEMPLATE
        );
    }

    /**
     * เรียก Gemini API สำหรับ Chat (ใช้ system message + API key เฉพาะ chat)
     */
    protected function callChatGemini(string $prompt, string $systemMessage, string $apiKey, string $model, array $config): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(30)->post($url, [
            'system_instruction' => [
                'parts' => [['text' => $systemMessage]],
            ],
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => $config['temperature'] ?? 0.8,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => $config['max_tokens'] ?? 512,
            ],
        ]);

        if (! $response->successful()) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? $response->body();
            throw new Exception("Gemini Chat API Error: {$errorMessage}");
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($text)) {
            throw new Exception('Gemini Chat: ไม่ได้รับคำตอบ (empty response)');
        }

        return [
            'response' => $text,
            'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            'provider' => 'gemini',
            'model' => $model,
        ];
    }

    /**
     * เรียก OpenAI-compatible API สำหรับ Chat (Groq, Grok, DeepSeek, Typhoon, etc.)
     */
    protected function callChatOpenAICompatible(string $prompt, string $systemMessage, string $apiKey, string $model, string $provider, array $config): array
    {
        $url = match ($provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'grok' => 'https://api.x.ai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'deepseek' => 'https://api.deepseek.com/chat/completions',
            'typhoon' => 'https://api.opentyphoon.ai/v1/chat/completions',
            'qwen' => 'https://router.huggingface.co/v1/chat/completions',
            default => throw new Exception("Chat provider '{$provider}' ไม่รองรับ"),
        };

        $headers = ['Authorization' => "Bearer {$apiKey}"];
        if ($provider === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
        }

        $response = Http::timeout(30)
            ->withHeaders($headers)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $config['temperature'] ?? 0.8,
                'max_tokens' => $config['max_tokens'] ?? 512,
            ])->throw();

        $data = $response->json();
        $text = $data['choices'][0]['message']['content'] ?? '';

        if (empty($text)) {
            throw new Exception("Chat {$provider}: ไม่ได้รับคำตอบ (empty response)");
        }

        return [
            'response' => $text,
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'provider' => $provider,
            'model' => $model,
        ];
    }

    /**
     * สร้างคำทำนายจาก AI
     *
     * @param  array  $questions  คำถามที่ต้องการทำนาย
     * @param  array|null  $userProfile  ข้อมูลโปรไฟล์ผู้ใช้
     * @param  array|null  $userPosts  โพสล่าสุดของผู้ใช้ (เฉพาะเชิงลึก)
     * @param  string|null  $promptTemplate  Prompt template ที่กำหนดเอง (ถ้าไม่ระบุจะใช้ค่าเริ่มต้น)
     * @param  string  $readingType  ประเภทคำทำนาย: 'basic' หรือ 'deep' (ส่งผลต่อ maxTokens/temperature)
     * @param  string|null  $birthDate  วันเดือนปีเกิด (Y-m-d) เพื่อทำนายตามราศี/ลัคนา
     */
    public function generateFortuneTelling(
        array $questions,
        ?array $userProfile = null,
        ?array $userPosts = null,
        ?string $promptTemplate = null,
        string $readingType = 'basic',
        ?string $birthDate = null
    ): array {
        // ตรวจสอบว่ามี API Key ก่อนเรียก AI
        if (empty($this->apiKey)) {
            Log::error('FortuneAIService: ไม่มี API Key สำหรับ provider', [
                'provider' => $this->provider,
                'model' => $this->model,
            ]);
            throw new Exception("ไม่พบ API Key สำหรับ {$this->provider} - กรุณาตั้งค่า API Key ในระบบ Admin");
        }

        $prompt = $this->buildPrompt($questions, $userProfile, $userPosts, $promptTemplate, $birthDate);
        $config = self::READING_CONFIG[$readingType] ?? self::READING_CONFIG['basic'];

        $startTime = microtime(true);

        try {
            $result = match ($this->provider) {
                'gemini' => $this->callGemini($prompt, $config),
                'groq' => $this->callGroq($prompt, $config),
                'grok' => $this->callGrok($prompt, $config),
                'qwen' => $this->callQwen($prompt, $config),
                'openrouter' => $this->callOpenRouter($prompt, $config),
                'deepseek' => $this->callDeepSeek($prompt, $config),
                'typhoon' => $this->callTyphoon($prompt, $config),
                default => throw new Exception("AI Provider '{$this->provider}' ไม่รองรับ"),
            };

            // บันทึกการใช้งาน tokens ผ่าน Pool
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->recordUsage($result['tokens_used'] ?? 0, $result['model'] ?? $this->model, $responseTime, $readingType);

            return $result;
        } catch (Exception $e) {
            // บันทึก error ผ่าน Pool
            $this->recordError($e->getMessage(), $this->model);
            throw $e;
        }
    }

    /**
     * สร้างคำทำนายพร้อม retry + สลับ provider อัตโนมัติ
     *
     * ลองต่อ AI หลายครั้ง ถ้า provider หลักล้มเหลว จะลองสลับไป provider อื่นอัตโนมัติ
     * ไม่ใช้ fallback ข้อความเขียนไว้ล่วงหน้า - ต้องต่อ AI ให้ได้จริง
     *
     * ลำดับการลอง:
     * 1. Provider หลัก - ลอง 2 ครั้ง (เว้น 2 วินาที)
     * 2. Provider สำรองจาก API Key Pool
     * 3. Provider สำรองจาก Global AI Settings
     *
     * @param  array  $questions  คำถาม
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้
     * @param  array|null  $userPosts  โพสล่าสุด
     * @param  string|null  $promptTemplate  Prompt template
     * @param  string  $readingType  ประเภทคำทำนาย: 'basic' หรือ 'deep'
     * @param  string|null  $birthDate  วันเดือนปีเกิด
     * @return array ผลลัพธ์จาก AI
     *
     * @throws Exception เมื่อทุก provider ล้มเหลวหมด
     */
    public function generateWithRetryAndFallback(
        array $questions,
        ?array $userProfile = null,
        ?array $userPosts = null,
        ?string $promptTemplate = null,
        string $readingType = 'basic',
        ?string $birthDate = null
    ): array {
        $errors = [];
        $prompt = $this->buildPrompt($questions, $userProfile, $userPosts, $promptTemplate, $birthDate);
        $config = self::READING_CONFIG[$readingType] ?? self::READING_CONFIG['basic'];
        $startTime = microtime(true);

        // ขั้นที่ 1: ลอง provider หลัก 3 ครั้ง (หน่วงนานขึ้นถ้าเจอ 429 rate limit)
        $maxRetries = 3;
        if (! empty($this->apiKey)) {
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    Log::info("FortuneAI Retry: ลอง {$this->provider} ครั้งที่ {$attempt}");

                    $result = $this->callProviderDirect(
                        $this->provider, $this->apiKey, $this->model, $prompt, $config
                    );

                    // บันทึกการใช้งาน tokens
                    $responseTime = (int) ((microtime(true) - $startTime) * 1000);
                    $this->recordUsage($result['tokens_used'] ?? 0, $result['model'] ?? $this->model, $responseTime, $readingType);

                    return $result;
                } catch (Exception $e) {
                    $errors[] = "{$this->provider} (ครั้งที่ {$attempt}): {$e->getMessage()}";
                    Log::warning("FortuneAI Retry: {$this->provider} ล้ม ครั้งที่ {$attempt}", [
                        'error' => $e->getMessage(),
                    ]);
                    if ($attempt < $maxRetries) {
                        // ถ้าเจอ 429 rate limit → หน่วง 60 วินาที ให้ quota reset
                        // ถ้า error อื่น → หน่วง 5 วินาที
                        $is429 = str_contains($e->getMessage(), '429');
                        $delaySeconds = $is429 ? 60 : 5;
                        Log::info("FortuneAI Retry: รอ {$delaySeconds} วินาทีก่อนลองใหม่", [
                            'is_rate_limit' => $is429,
                            'attempt' => $attempt,
                        ]);
                        sleep($delaySeconds);
                    }
                }
            }
        } else {
            $errors[] = "{$this->provider}: ไม่มี API Key";
        }

        // ขั้นที่ 2: ลองสลับไป provider อื่นอัตโนมัติ
        $backupProviders = $this->getBackupProviders();

        if (! empty($backupProviders)) {
            Log::info('FortuneAI: สลับไป backup providers', [
                'primary_failed' => $this->provider,
                'backup_count' => count($backupProviders),
                'backup_providers' => array_column($backupProviders, 'provider'),
            ]);
        }

        foreach ($backupProviders as $backup) {
            try {
                Log::info("FortuneAI Backup: ลอง {$backup['provider']} ({$backup['model']})");

                $result = $this->callProviderDirect(
                    $backup['provider'], $backup['api_key'], $backup['model'], $prompt, $config
                );

                // สำเร็จ!
                Log::info("FortuneAI Backup: {$backup['provider']} สำเร็จ!", [
                    'tokens_used' => $result['tokens_used'] ?? 0,
                ]);

                return $result;
            } catch (Exception $e) {
                $errors[] = "{$backup['provider']}: {$e->getMessage()}";
                Log::warning("FortuneAI Backup: {$backup['provider']} ล้มเช่นกัน", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ทุก provider ล้มหมด
        $errorSummary = implode(' | ', array_slice($errors, 0, 5));
        Log::error('FortuneAI: ทุก provider ล้มหมด', ['errors' => $errors]);
        throw new Exception('ไม่สามารถเชื่อมต่อ AI ได้ (ลองแล้ว '.count($errors)." ครั้ง): {$errorSummary}");
    }

    /**
     * เรียก AI provider โดยตรง พร้อม override api_key/model ชั่วคราว
     *
     * @param  string  $provider  ชื่อ provider
     * @param  string  $apiKey  API Key ที่ใช้
     * @param  string  $model  ชื่อ model
     * @param  string  $prompt  ข้อความ prompt
     * @param  array  $config  การตั้งค่า
     * @return array ผลลัพธ์
     */
    protected function callProviderDirect(string $provider, string $apiKey, string $model, string $prompt, array $config): array
    {
        // บันทึก original values
        $origApiKey = $this->apiKey;
        $origModel = $this->model;

        // Override ชั่วคราว
        $this->apiKey = $apiKey;
        $this->model = $model;

        try {
            return match ($provider) {
                'gemini' => $this->callGemini($prompt, $config),
                'groq' => $this->callGroq($prompt, $config),
                'grok' => $this->callGrok($prompt, $config),
                'qwen' => $this->callQwen($prompt, $config),
                'openrouter' => $this->callOpenRouter($prompt, $config),
                'deepseek' => $this->callDeepSeek($prompt, $config),
                'typhoon' => $this->callTyphoon($prompt, $config),
                default => throw new Exception("Provider '{$provider}' ไม่รองรับ"),
            };
        } finally {
            // คืนค่า original เสมอ
            $this->apiKey = $origApiKey;
            $this->model = $origModel;
        }
    }

    /**
     * ดึง backup providers จาก Pool + Global Settings
     * ข้ามตัวที่เป็น provider หลัก
     *
     * @return array [['provider' => '...', 'api_key' => '...', 'model' => '...'], ...]
     */
    protected function getBackupProviders(): array
    {
        $backups = [];
        $currentProvider = $this->provider;
        $addedProviders = [$currentProvider]; // เก็บ list provider ที่เพิ่มแล้ว ป้องกันซ้ำ

        // 1) ดึงจาก API Key Pool
        if ($this->poolService) {
            try {
                $providers = ['gemini', 'groq', 'grok', 'qwen', 'openrouter', 'deepseek', 'typhoon'];
                foreach ($providers as $provider) {
                    if (in_array($provider, $addedProviders)) {
                        continue;
                    }
                    $key = $this->poolService->getKey($provider);
                    if ($key && ! empty($key->api_key)) {
                        $backups[] = [
                            'provider' => $provider,
                            'api_key' => $key->api_key,
                            'model' => $this->getDefaultModelForProvider($provider),
                        ];
                        $addedProviders[] = $provider;
                    }
                }
            } catch (Exception $e) {
                Log::debug('FortuneAI: Pool service ดึง backup ไม่ได้', ['error' => $e->getMessage()]);
            }
        }

        // 2) ดึงจาก Global AI Settings (Gemini key มักจะมีอยู่)
        try {
            if (! in_array('gemini', $addedProviders)) {
                $geminiKey = AiContentSetting::getValue('gemini_api_key');
                if (! empty($geminiKey)) {
                    $geminiModel = AiContentSetting::getValue('gemini_model', 'gemini-2.0-flash');
                    $backups[] = [
                        'provider' => 'gemini',
                        'api_key' => $geminiKey,
                        'model' => $geminiModel,
                    ];
                    $addedProviders[] = 'gemini';
                }
            }

            // OpenRouter จาก claude key
            if (! in_array('openrouter', $addedProviders)) {
                $claudeKey = AiContentSetting::getValue('claude_api_key');
                if (! empty($claudeKey)) {
                    $backups[] = [
                        'provider' => 'openrouter',
                        'api_key' => $claudeKey,
                        'model' => AiContentSetting::getValue('claude_model', 'anthropic/claude-3-haiku'),
                    ];
                    $addedProviders[] = 'openrouter';
                }
            }
        } catch (Exception $e) {
            Log::debug('FortuneAI: Global settings ดึง backup ไม่ได้', ['error' => $e->getMessage()]);
        }

        // 3) ดึงจาก fortune settings เอง (กรณี use_global_ai_settings = false อาจมี key อื่นอยู่)
        if (! empty($this->settings->ai_api_key) && ! empty($this->settings->ai_provider)) {
            if (! in_array($this->settings->ai_provider, $addedProviders)) {
                $backups[] = [
                    'provider' => $this->settings->ai_provider,
                    'api_key' => $this->settings->ai_api_key,
                    'model' => $this->settings->ai_model ?: $this->getDefaultModelForProvider($this->settings->ai_provider),
                ];
            }
        }

        return $backups;
    }

    /**
     * ดึง default model สำหรับแต่ละ provider
     */
    protected function getDefaultModelForProvider(string $provider): string
    {
        return match ($provider) {
            'gemini' => 'gemini-2.0-flash',
            'groq' => 'llama-3.3-70b-versatile',
            'grok' => 'grok-2-latest',
            'qwen' => 'Qwen/Qwen2.5-72B-Instruct',
            'openrouter' => 'anthropic/claude-3-haiku',
            'deepseek' => 'deepseek-chat',
            'typhoon' => 'typhoon-v2-70b-instruct',
            default => 'gemini-2.0-flash',
        };
    }

    /**
     * สร้าง prompt สำหรับส่งให้ AI
     *
     * @param  array  $questions  คำถาม
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้
     * @param  array|null  $userPosts  โพสล่าสุด
     * @param  string|null  $promptTemplate  template ที่กำหนดเอง
     * @param  string|null  $birthDate  วันเดือนปีเกิด (Y-m-d)
     */
    protected function buildPrompt(array $questions, ?array $userProfile, ?array $userPosts, ?string $promptTemplate = null, ?string $birthDate = null): string
    {
        $template = $promptTemplate ?? $this->settings->getDefaultPromptTemplate();
        $profileText = $this->formatUserProfile($userProfile);
        $postsText = $this->formatUserPosts($userPosts);
        $questionsText = implode("\n", array_map(fn ($i, $q) => ($i + 1).". $q", array_keys($questions), $questions));
        $birthDateSection = $this->formatBirthDateSection($birthDate);

        return str_replace(
            ['{user_profile}', '{user_posts}', '{questions}', '{birth_date_section}'],
            [$profileText, $postsText, $questionsText, $birthDateSection],
            $template
        );
    }

    /**
     * สร้างส่วนวิเคราะห์วันเดือนปีเกิด
     *
     * @param  string|null  $birthDate  วันเดือนปีเกิด (Y-m-d)
     */
    protected function formatBirthDateSection(?string $birthDate): string
    {
        if (empty($birthDate)) {
            return '(ไม่ได้ระบุวันเดือนปีเกิด - ทำนายจากคำถามและบริบทที่มี ใช้หลักเจ้าชนะเมื่อได้รับวันเกิดภายหลัง)';
        }

        try {
            $date = \Carbon\Carbon::parse($birthDate);
            $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            $thaiYear = $date->year + 543;
            $age = $date->age;
            $dayOfWeekIndex = $date->dayOfWeek; // 0=อาทิตย์, 1=จันทร์, ...6=เสาร์
            $dayOfWeek = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'][$dayOfWeekIndex];

            // คำนวณราศีจากวันเกิด (โหราศาสตร์สากล)
            $zodiac = $this->getZodiacSign($date->month, $date->day);

            // === คำนวณโหราศาสตร์ไทย (เจ้าชนะ) ===
            $planetInfo = $this->getPlanetByDayOfWeek($dayOfWeekIndex);

            $section = "📅 วันเดือนปีเกิด: {$date->day} {$thaiMonths[$date->month]} {$thaiYear} (พ.ศ.)\n";
            $section .= "🗓️ วันเกิด: วัน{$dayOfWeek}\n";
            $section .= "🎂 อายุ: {$age} ปี\n";
            $section .= "♈ ราศี: {$zodiac}\n";
            $section .= "\n=== โหราศาสตร์ไทย (เจ้าชนะ) ===\n";
            $section .= "⭐ ดาวประจำวันเกิด (เจ้าชนะ): {$planetInfo['planet']}\n";
            $section .= "🔥 ธาตุ: {$planetInfo['element']}\n";
            $section .= "🤝 ดาวมิตร: {$planetInfo['friends']}\n";
            $section .= "⚔️ ดาวศัตรู: {$planetInfo['enemies']}\n";
            $section .= "🎨 สีมงคล: {$planetInfo['lucky_color']}\n";
            $section .= "🚫 สีอัปมงคล: {$planetInfo['unlucky_color']}\n";
            $section .= "🔢 เลขมงคล: {$planetInfo['lucky_number']}\n";
            $section .= "📅 วันมงคล: {$planetInfo['lucky_days']}\n";
            $section .= "📅 วันอัปมงคล: {$planetInfo['unlucky_days']}\n";
            $section .= "💎 ลักษณะนิสัย: {$planetInfo['personality']}\n";
            $section .= "\n⭐ กรุณาวิเคราะห์ดวงชะตาจากข้อมูลวันเกิดนี้อย่างละเอียด โดยใช้หลักเจ้าชนะ อ้างอิงดาวเจ้าชนะ ดาวมิตร ดาวศัตรู ภพที่ดาวโคจรผ่าน และดาวที่ส่งผลในช่วงนี้";

            return $section;
        } catch (\Exception $e) {
            return "(วันเกิด: {$birthDate})";
        }
    }

    /**
     * คำนวณดาวเคราะห์ประจำวันเกิดตามหลักเจ้าชนะ
     *
     * @param  int  $dayOfWeek  0=อาทิตย์, 1=จันทร์, ...6=เสาร์
     * @return array ข้อมูลดาวเคราะห์
     */
    protected function getPlanetByDayOfWeek(int $dayOfWeek): array
    {
        $planets = [
            0 => [ // อาทิตย์
                'planet' => 'ดาวอาทิตย์ (☉)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ดาวพฤหัสบดี, ดาวอังคาร',
                'enemies' => 'ดาวเสาร์, ราหู',
                'lucky_color' => 'แดง, ส้ม, ทอง',
                'unlucky_color' => 'ดำ, ม่วงเข้ม',
                'lucky_number' => '1, 6, 9',
                'lucky_days' => 'วันพฤหัสบดี, วันอังคาร',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'มีอำนาจ เป็นผู้นำ มีศักดิ์ศรี มั่นใจ กล้าตัดสินใจ',
            ],
            1 => [ // จันทร์
                'planet' => 'ดาวจันทร์ (☽)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวพุธ, ดาวศุกร์',
                'enemies' => 'ราหู, ดาวเสาร์',
                'lucky_color' => 'ขาว, ครีม, เงิน',
                'unlucky_color' => 'ดำ, น้ำเงินเข้ม',
                'lucky_number' => '2, 5, 7',
                'lucky_days' => 'วันพุธ, วันศุกร์',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'อ่อนโยน มีเมตตา จิตใจดี อารมณ์อ่อนไหว รักครอบครัว',
            ],
            2 => [ // อังคาร
                'planet' => 'ดาวอังคาร (♂)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ดาวอาทิตย์, ดาวพฤหัสบดี',
                'enemies' => 'ดาวพุธ, ดาวเสาร์',
                'lucky_color' => 'ชมพู, แดงอ่อน, ส้ม',
                'unlucky_color' => 'เขียว, ดำ',
                'lucky_number' => '3, 6, 9',
                'lucky_days' => 'วันอาทิตย์, วันพฤหัสบดี',
                'unlucky_days' => 'วันเสาร์, วันพุธ',
                'personality' => 'กล้าหาญ ร้อนแรง ทะเยอทะยาน มีพลัง ไม่ยอมแพ้',
            ],
            3 => [ // พุธ
                'planet' => 'ดาวพุธ (☿)',
                'element' => 'ธาตุดิน',
                'friends' => 'ดาวจันทร์, ดาวศุกร์',
                'enemies' => 'ราหู, ดาวอังคาร',
                'lucky_color' => 'เขียว, เขียวอ่อน',
                'unlucky_color' => 'แดง, ชมพูเข้ม',
                'lucky_number' => '4, 2, 7',
                'lucky_days' => 'วันจันทร์, วันศุกร์',
                'unlucky_days' => 'วันอังคาร',
                'personality' => 'ฉลาด มีไหวพริบ พูดเก่ง ค้าขายเก่ง ปรับตัวเก่ง',
            ],
            4 => [ // พฤหัสบดี
                'planet' => 'ดาวพฤหัสบดี (♃)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวอาทิตย์, ดาวอังคาร',
                'enemies' => 'ราหู, ดาวเสาร์',
                'lucky_color' => 'ส้ม, เหลือง, ทอง',
                'unlucky_color' => 'ดำ, ม่วงเข้ม',
                'lucky_number' => '5, 1, 3',
                'lucky_days' => 'วันอาทิตย์, วันอังคาร',
                'unlucky_days' => 'วันเสาร์',
                'personality' => 'มีปัญญา ใจกว้าง โชคดี ศรัทธา รักความยุติธรรม ผู้ทรงคุณธรรม',
            ],
            5 => [ // ศุกร์
                'planet' => 'ดาวศุกร์ (♀)',
                'element' => 'ธาตุน้ำ',
                'friends' => 'ดาวพุธ, ดาวจันทร์',
                'enemies' => 'ดาวอาทิตย์, ดาวอังคาร',
                'lucky_color' => 'ฟ้า, ฟ้าอ่อน, ขาว',
                'unlucky_color' => 'แดง, ส้มเข้ม',
                'lucky_number' => '6, 2, 4',
                'lucky_days' => 'วันพุธ, วันจันทร์',
                'unlucky_days' => 'วันอาทิตย์, วันอังคาร',
                'personality' => 'รักสวยรักงาม มีเสน่ห์ มีศิลปะ รักความหรูหรา โรแมนติก',
            ],
            6 => [ // เสาร์
                'planet' => 'ดาวเสาร์ (♄)',
                'element' => 'ธาตุไฟ',
                'friends' => 'ราหู, ดาวพฤหัสบดี',
                'enemies' => 'ดาวอาทิตย์, ดาวอังคาร',
                'lucky_color' => 'ม่วง, ดำ, น้ำเงินเข้ม',
                'unlucky_color' => 'แดง, ส้ม',
                'lucky_number' => '7, 5, 8',
                'lucky_days' => 'วันพฤหัสบดี',
                'unlucky_days' => 'วันอาทิตย์, วันอังคาร',
                'personality' => 'อดทน มุ่งมั่น รอบคอบ มีวินัย จริงจัง ทำอะไรทำจริง',
            ],
        ];

        return $planets[$dayOfWeek] ?? $planets[0];
    }

    /**
     * คำนวณราศีจากเดือนและวันเกิด (Western Zodiac)
     *
     * @param  int  $month  เดือน
     * @param  int  $day  วัน
     * @return string ชื่อราศี
     */
    protected function getZodiacSign(int $month, int $day): string
    {
        $signs = [
            ['name' => 'มังกร (Capricorn)', 'end_month' => 1, 'end_day' => 19],
            ['name' => 'กุมภ์ (Aquarius)', 'end_month' => 2, 'end_day' => 18],
            ['name' => 'มีน (Pisces)', 'end_month' => 3, 'end_day' => 20],
            ['name' => 'เมษ (Aries)', 'end_month' => 4, 'end_day' => 19],
            ['name' => 'พฤษภ (Taurus)', 'end_month' => 5, 'end_day' => 20],
            ['name' => 'เมถุน (Gemini)', 'end_month' => 6, 'end_day' => 20],
            ['name' => 'กรกฎ (Cancer)', 'end_month' => 7, 'end_day' => 22],
            ['name' => 'สิงห์ (Leo)', 'end_month' => 8, 'end_day' => 22],
            ['name' => 'กันย์ (Virgo)', 'end_month' => 9, 'end_day' => 22],
            ['name' => 'ตุลย์ (Libra)', 'end_month' => 10, 'end_day' => 22],
            ['name' => 'พิจิก (Scorpio)', 'end_month' => 11, 'end_day' => 21],
            ['name' => 'ธนู (Sagittarius)', 'end_month' => 12, 'end_day' => 21],
        ];

        foreach ($signs as $sign) {
            if ($month === $sign['end_month'] && $day <= $sign['end_day']) {
                return $sign['name'];
            }
            if ($month < $sign['end_month']) {
                return $sign['name'];
            }
        }

        return 'มังกร (Capricorn)'; // ธันวาคม 22-31
    }

    protected function formatUserProfile(?array $userProfile): string
    {
        if (empty($userProfile)) {
            return 'ไม่มีข้อมูลโปรไฟล์';
        }

        $parts = [];
        if (! empty($userProfile['name'])) {
            $parts[] = "ชื่อ: {$userProfile['name']}";
        }

        // เพศ (จาก Facebook หรือที่ผู้ใช้บอก)
        if (! empty($userProfile['gender'])) {
            $genderMap = ['male' => 'ชาย', 'female' => 'หญิง'];
            $gender = $genderMap[$userProfile['gender']] ?? $userProfile['gender'];
            $parts[] = "เพศ: {$gender}";
        }

        // อายุ (คำนวณจาก birthday)
        if (! empty($userProfile['age'])) {
            $parts[] = "อายุ: {$userProfile['age']} ปี";
        }

        // วันเกิด (จาก Facebook)
        if (! empty($userProfile['birthday'])) {
            $parts[] = "วันเกิด: {$userProfile['birthday']}";
        }

        // ภาษา/ภูมิภาค
        if (! empty($userProfile['locale'])) {
            $parts[] = "ภาษา/ภูมิภาค: {$userProfile['locale']}";
        }

        // Timezone (ช่วยวิเคราะห์ดวงตามเวลาท้องถิ่น)
        if (! empty($userProfile['timezone'])) {
            $parts[] = 'Timezone: UTC'.($userProfile['timezone'] >= 0 ? '+' : '').$userProfile['timezone'];
        }

        return ! empty($parts) ? implode("\n", $parts) : 'ข้อมูลพื้นฐาน';
    }

    protected function formatUserPosts(?array $userPosts): string
    {
        if (empty($userPosts)) {
            return 'ไม่มีข้อมูลโพสล่าสุด';
        }

        $formatted = [];
        foreach (array_slice($userPosts, 0, 3) as $index => $post) {
            $message = $post['message'] ?? $post['story'] ?? '';
            if (! empty($message)) {
                $formatted[] = ($index + 1).'. '.substr($message, 0, 200);
            }
        }

        return ! empty($formatted) ? implode("\n", $formatted) : 'ไม่มีข้อมูลโพสล่าสุด';
    }

    protected function callGemini(string $prompt, array $config = []): array
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(60)->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => self::SYSTEM_MESSAGE]],
                ],
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => $config['temperature'] ?? 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => $config['max_tokens'] ?? 2048,
                ],
            ]);

            if (! $response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $response->body();
                $errorCode = $errorBody['error']['code'] ?? $response->status();
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'model' => $this->model,
                ]);
                throw new Exception("Gemini API Error ({$errorCode}): {$errorMessage}");
            }

            $data = $response->json();

            if (empty($data['candidates'][0]['content']['parts'][0]['text'] ?? null)) {
                Log::error('Gemini API: Empty response', ['data' => $data]);
                $blockReason = $data['promptFeedback']['blockReason'] ?? null;
                if ($blockReason) {
                    throw new Exception("Gemini API: Prompt blocked - {$blockReason}");
                }
                throw new Exception('Gemini API: ไม่ได้รับคำตอบจาก AI (empty response)');
            }

            return [
                'response' => $data['candidates'][0]['content']['parts'][0]['text'],
                'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                'provider' => 'gemini',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('Gemini API Error: '.$e->getMessage());
            throw $e;
        }
    }

    protected function callGroq(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'provider' => 'groq',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('Groq API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);
            throw new Exception("Groq API Error: {$e->getMessage()}");
        }
    }

    protected function callQwen(string $prompt, array $config = []): array
    {
        try {
            // ใช้ HuggingFace Router API (OpenAI-compatible chat format)
            // ให้คุณภาพคำทำนายดีกว่า text generation API เดิม
            $response = Http::timeout(120)
                ->withToken($this->apiKey)
                ->post('https://router.huggingface.co/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                    'temperature' => $config['temperature'] ?? 0.7,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'provider' => 'qwen',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('Qwen API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);
            throw new Exception("Qwen API Error: {$e->getMessage()}");
        }
    }

    protected function callOpenRouter(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(60)
                ->withToken($this->apiKey)
                ->withHeaders(['HTTP-Referer' => config('app.url')])
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'provider' => 'openrouter',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('OpenRouter API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);
            throw new Exception("OpenRouter API Error: {$e->getMessage()}");
        }
    }

    // ============================================================
    // Comment Engagement: AI สร้างข้อความชวนดูดวง
    // ============================================================

    /**
     * System message สำหรับ Comment Engagement ของ "แม่หมอจันทรา"
     * AI จะพูดเฉพาะเรื่องดูดวงเท่านั้น ห้ามตอบเรื่องอื่น
     * เป็นหมอดูสาวสวยวัย 35 ปี ใช้คำแทนตัวว่า "จันทรา"
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "จันทรามีทีมงานช่วยกันค่ะ"
     * - ถ้าถามนอกเรื่องดูดวง: ปฏิเสธสุภาพและชวนกลับมาดูดวง
     */
    protected const ENGAGEMENT_SYSTEM_MESSAGE = 'คุณชื่อ "แม่หมอจันทรา" เป็นหมอดูสาวสวยวัย 35 ปี พูดจาเพราะ อบอุ่นเป็นกันเอง น่าเชื่อถือ ใช้คำแทนตัวว่า "จันทรา" เชี่ยวชาญโหราศาสตร์ไทย ไพ่ทาโรต์ และการหยั่งรู้ด้วยจิตสัมผัส [กฎสำคัญ] 1) หากถูกถามว่าเป็น AI บอท หรือโปรแกรม ให้ตอบเป็นธรรมชาติว่า "จันทรามีทีมงานช่วยกันในการทำนายค่ะ" หรือ "จันทราทำนายเองค่ะ ใช้พลังหยั่งรู้ในการทำนายค่ะ 🔮" 2) คุณพูดเฉพาะเรื่องดูดวงเท่านั้น หากถูกถามเรื่องอื่นที่ไม่เกี่ยวกับดูดวง ให้ปฏิเสธอย่างสุภาพว่า "ขอบคุณที่สนใจนะคะ แต่จันทราขอตอบเฉพาะเรื่องดูดวงนะคะ 🙏 ทักมาถามเรื่องดวงได้เลยค่ะ 🔮✨" ห้ามตอบเรื่องอื่นทุกกรณี ห้ามเขียนโค้ด หากมีข้อมูลเพศหรือวันเกิดให้อ้างอิงในข้อความ ใน DM_MESSAGE ให้ชวนบอกวันเกิดเพื่อทำนายแม่นขึ้น เช่น "บอกวันเกิดให้จันทราได้ไหมคะ จะได้ทำนายได้ลึกขึ้นค่ะ" และชวนส่งต่อให้เพื่อนๆ มาดูดวงกับจันทรา คุณต้องตอบเป็น JSON เท่านั้น';

    /**
     * สร้างข้อความ engage จากคอมเม้นต์
     *
     * @param  string  $commentText  ข้อความคอมเม้นต์
     * @param  array|null  $userProfile  โปรไฟล์ผู้ใช้ (name, gender, birthday ฯลฯ)
     * @param  string|null  $engagementPrompt  Prompt template (ถ้าไม่ระบุจะใช้ค่าเริ่มต้นจาก settings)
     * @return array ['comment_reply' => '...', 'dm_message' => '...']
     */
    public function generateCommentEngagement(
        string $commentText,
        ?array $userProfile = null,
        ?string $engagementPrompt = null
    ): array {
        // ตรวจสอบ API Key ก่อนเรียก AI
        if (empty($this->apiKey)) {
            Log::error('FortuneAIService: ไม่มี API Key สำหรับ comment engagement', [
                'provider' => $this->provider,
            ]);
            throw new Exception("ไม่พบ API Key สำหรับ {$this->provider}");
        }

        $prompt = $engagementPrompt ?? $this->settings->getCommentEngagementPrompt();

        // แทนที่ placeholders ใน prompt
        $name = $userProfile['name'] ?? $userProfile['first_name'] ?? 'คุณ';
        $profileInfo = $this->formatProfileForEngagement($userProfile);

        $prompt = str_replace(
            ['{comment}', '{name}', '{profile_info}'],
            [$commentText, $name, $profileInfo],
            $prompt
        );

        $config = [
            'max_tokens' => 400,
            'temperature' => 0.8,
        ];

        $result = match ($this->provider) {
            'gemini' => $this->callGemini($prompt, $config),
            'groq' => $this->callGroq($prompt, $config),
            'grok' => $this->callGrok($prompt, $config),
            'qwen' => $this->callQwen($prompt, $config),
            'openrouter' => $this->callOpenRouter($prompt, $config),
            'deepseek' => $this->callDeepSeek($prompt, $config),
            'typhoon' => $this->callTyphoon($prompt, $config),
            default => throw new Exception("AI Provider '{$this->provider}' ไม่รองรับ"),
        };

        // Parse JSON response จาก AI
        return $this->parseEngagementResponse($result['response'] ?? '');
    }

    /**
     * แปลงข้อมูลโปรไฟล์เป็นข้อความสำหรับ prompt
     */
    protected function formatProfileForEngagement(?array $userProfile): string
    {
        if (empty($userProfile)) {
            return '(ไม่มีข้อมูลโปรไฟล์)';
        }

        $info = [];

        if (! empty($userProfile['gender'])) {
            $genderMap = ['male' => 'ชาย', 'female' => 'หญิง'];
            $info[] = 'เพศ: '.($genderMap[$userProfile['gender']] ?? $userProfile['gender']);
        }

        if (! empty($userProfile['birthday'])) {
            $info[] = 'วันเกิด: '.$userProfile['birthday'];
        }

        if (! empty($userProfile['locale'])) {
            $info[] = 'ภาษา: '.$userProfile['locale'];
        }

        return empty($info) ? '(ไม่มีข้อมูลเพิ่มเติม)' : implode(', ', $info);
    }

    /**
     * Parse JSON response จาก AI สำหรับ engagement
     *
     * @return array ['comment_reply' => '...', 'dm_message' => '...']
     */
    protected function parseEngagementResponse(string $response): array
    {
        // ลอง parse JSON โดยตรง
        $data = json_decode($response, true);

        // ถ้า parse ไม่ได้ ลองหา JSON ใน response
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/\{[^}]*"comment_reply"[^}]*\}/s', $response, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        // ถ้ายังไม่ได้ ใช้ค่า default
        if (empty($data) || ! isset($data['comment_reply'])) {
            Log::warning('AI engagement response ไม่ใช่ JSON ที่ถูกต้อง', ['response' => $response]);

            return [
                'comment_reply' => '🔮 สนใจดูดวงไหมคะ? ทักมาใน inbox ได้เลยนะคะ ✨',
                'dm_message' => $this->settings->getCommentDmTemplate(),
            ];
        }

        return [
            'comment_reply' => $data['comment_reply'] ?? '🔮 สนใจดูดวงไหมคะ? ทักมาใน inbox ได้เลยนะคะ ✨',
            'dm_message' => $data['dm_message'] ?? $this->settings->getCommentDmTemplate(),
        ];
    }

    public function testConnection(): array
    {
        // ตรวจสอบการตั้งค่าเบื้องต้น
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => "ไม่พบ API Key สำหรับ {$this->provider} - กรุณาตั้งค่า API Key ก่อน",
                'debug' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'has_api_key' => false,
                    'use_global' => $this->settings->use_global_ai_settings ?? false,
                ],
            ];
        }

        try {
            $result = $this->generateFortuneTelling(['ทดสอบการเชื่อมต่อ AI'], null, null);

            return [
                'success' => true,
                'message' => "เชื่อมต่อกับ {$this->provider} ({$this->model}) สำเร็จ",
                'preview' => $result['response'] ?? '',
                'debug' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'response_length' => mb_strlen($result['response'] ?? ''),
                ],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => [
                    'provider' => $this->provider,
                    'model' => $this->model,
                    'has_api_key' => ! empty($this->apiKey),
                    'api_key_prefix' => substr($this->apiKey ?? '', 0, 8).'...',
                    'use_global' => $this->settings->use_global_ai_settings ?? false,
                ],
            ];
        }
    }

    // ============================================================
    // Grok API (xAI)
    // ============================================================

    /**
     * เรียก Grok API (xAI)
     *
     * @param  string  $prompt  ข้อความ prompt
     * @param  array  $config  การตั้งค่า (max_tokens, temperature)
     * @return array ผลลัพธ์
     */
    protected function callGrok(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(90)
                ->withToken($this->apiKey)
                ->post('https://api.x.ai/v1/chat/completions', [
                    'model' => $this->model ?: 'grok-2-latest',
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'provider' => 'grok',
                'model' => $this->model ?: 'grok-2-latest',
            ];
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            Log::error('Grok API Error', [
                'error' => $errorMsg,
                'model' => $this->model ?: 'grok-2-latest',
                'has_api_key' => ! empty($this->apiKey),
                'api_key_prefix' => substr($this->apiKey ?? '', 0, 8).'...',
            ]);
            throw new Exception("Grok API Error: {$errorMsg}");
        }
    }

    // ============================================================
    // DeepSeek API
    // ============================================================

    /**
     * เรียก DeepSeek API
     *
     * DeepSeek ใช้ OpenAI-compatible API
     * ราคาถูกมาก + มี sign-up credits 5M tokens ฟรี
     *
     * @param  string  $prompt  ข้อความ prompt
     * @param  array  $config  การตั้งค่า (max_tokens, temperature)
     * @return array ผลลัพธ์
     */
    protected function callDeepSeek(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(90)
                ->withToken($this->apiKey)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => $this->model ?: 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'provider' => 'deepseek',
                'model' => $this->model ?: 'deepseek-chat',
            ];
        } catch (Exception $e) {
            Log::error('DeepSeek API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model ?: 'deepseek-chat',
            ]);
            throw new Exception("DeepSeek API Error: {$e->getMessage()}");
        }
    }

    // ============================================================
    // Typhoon API (SCB 10X - เชี่ยวชาญภาษาไทย)
    // ============================================================

    /**
     * เรียก Typhoon API (SCB 10X)
     *
     * Typhoon เป็น LLM ที่ออกแบบมาเฉพาะสำหรับภาษาไทย
     * ใช้ OpenAI-compatible API format
     *
     * @param  string  $prompt  ข้อความ prompt
     * @param  array  $config  การตั้งค่า (max_tokens, temperature)
     * @return array ผลลัพธ์
     */
    protected function callTyphoon(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(90)
                ->withToken($this->apiKey)
                ->post('https://api.opentyphoon.ai/v1/chat/completions', [
                    'model' => $this->model ?: 'typhoon-v2-70b-instruct',
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => $config['temperature'] ?? 0.7,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                ])->throw();

            $data = $response->json();

            return [
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                'provider' => 'typhoon',
                'model' => $this->model ?: 'typhoon-v2-70b-instruct',
            ];
        } catch (Exception $e) {
            Log::error('Typhoon API Error', [
                'error' => $e->getMessage(),
                'model' => $this->model ?: 'typhoon-v2-70b-instruct',
            ]);
            throw new Exception("Typhoon API Error: {$e->getMessage()}");
        }
    }

    // ============================================================
    // Playground: ทดสอบสนทนากับ AI แบบอิสระ
    // ============================================================

    /**
     * สนทนากับ AI สำหรับ Playground (Admin ทดสอบ)
     *
     * รับ messages array แบบ chat history เพื่อรองรับการสนทนาต่อเนื่อง
     *
     * @param  array  $messages  ประวัติสนทนา [['role' => 'user'|'assistant', 'content' => '...'], ...]
     * @param  string  $readingType  ประเภท: 'basic' หรือ 'deep'
     * @return array ผลลัพธ์
     */
    public function playgroundChat(array $messages, string $readingType = 'basic'): array
    {
        if (empty($this->apiKey)) {
            throw new Exception("ไม่พบ API Key สำหรับ {$this->provider} - กรุณาตั้งค่า API Key ก่อน");
        }

        $config = self::READING_CONFIG[$readingType] ?? self::READING_CONFIG['basic'];
        $startTime = microtime(true);

        // สร้าง messages array พร้อม system message
        $chatMessages = [
            ['role' => 'system', 'content' => self::SYSTEM_MESSAGE],
        ];
        foreach ($messages as $msg) {
            $chatMessages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? '',
            ];
        }

        // Gemini ใช้ API format ต่างจาก OpenAI-compatible
        if ($this->provider === 'gemini') {
            return $this->playgroundGemini($chatMessages, $config);
        }

        // สำหรับ provider ที่ใช้ OpenAI-compatible API
        $url = match ($this->provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'grok' => 'https://api.x.ai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'deepseek' => 'https://api.deepseek.com/chat/completions',
            'typhoon' => 'https://api.opentyphoon.ai/v1/chat/completions',
            default => throw new Exception("Provider '{$this->provider}' ไม่รองรับ Playground"),
        };

        $headers = ['Authorization' => "Bearer {$this->apiKey}"];
        if ($this->provider === 'openrouter') {
            $headers['HTTP-Referer'] = config('app.url');
        }

        $response = Http::timeout(90)
            ->withHeaders($headers)
            ->post($url, [
                'model' => $this->model,
                'messages' => $chatMessages,
                'temperature' => $config['temperature'] ?? 0.75,
                'max_tokens' => $config['max_tokens'] ?? 2048,
            ])->throw();

        $data = $response->json();
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'response' => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'provider' => $this->provider,
            'model' => $this->model,
            'response_time_ms' => $responseTime,
        ];
    }

    /**
     * Playground สำหรับ Gemini (ใช้ API format ต่างจาก OpenAI)
     *
     * @param  array  $chatMessages  messages array พร้อม system message
     * @param  array  $config  การตั้งค่า
     * @return array ผลลัพธ์
     */
    protected function playgroundGemini(array $chatMessages, array $config): array
    {
        $startTime = microtime(true);
        $systemMessage = '';
        $contents = [];

        foreach ($chatMessages as $msg) {
            if ($msg['role'] === 'system') {
                $systemMessage = $msg['content'];

                continue;
            }
            $geminiRole = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $config['temperature'] ?? 0.75,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => $config['max_tokens'] ?? 2048,
            ],
        ];

        if (! empty($systemMessage)) {
            $body['system_instruction'] = [
                'parts' => [['text' => $systemMessage]],
            ];
        }

        $response = Http::timeout(90)->post($url, $body)->throw();
        $data = $response->json();
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        if (empty($data['candidates'][0]['content']['parts'][0]['text'] ?? null)) {
            throw new Exception('Gemini Playground: ไม่ได้รับคำตอบจาก AI');
        }

        return [
            'response' => $data['candidates'][0]['content']['parts'][0]['text'],
            'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            'provider' => 'gemini',
            'model' => $this->model,
            'response_time_ms' => $responseTime,
        ];
    }

    // ============================================================
    // API Key Pool Integration
    // ============================================================

    /**
     * บันทึกการใช้งาน tokens ผ่าน Pool Service
     *
     * @param  int  $tokensUsed  จำนวน tokens ที่ใช้
     * @param  string  $model  model ที่ใช้
     * @param  int  $responseTime  เวลาตอบกลับ (ms)
     * @param  string  $requestType  ประเภท request
     */
    protected function recordUsage(int $tokensUsed, string $model, int $responseTime, string $requestType = 'fortune'): void
    {
        if (! $this->currentKey || ! $this->poolService) {
            return;
        }

        try {
            // ประมาณการแยก input/output tokens (ถ้าไม่มีข้อมูล)
            $inputTokens = (int) ($tokensUsed * 0.3);
            $outputTokens = $tokensUsed - $inputTokens;

            $this->currentKey->recordUsage(
                $inputTokens,
                $outputTokens,
                $model,
                $responseTime,
                $requestType
            );
        } catch (\Exception $e) {
            Log::warning('FortuneAIService: บันทึก usage ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * บันทึก error ผ่าน Pool Service
     *
     * @param  string  $errorMessage  ข้อความ error
     * @param  string|null  $model  model ที่ใช้
     */
    protected function recordError(string $errorMessage, ?string $model = null): void
    {
        if (! $this->currentKey || ! $this->poolService) {
            return;
        }

        try {
            $this->currentKey->recordError($errorMessage, $model);
        } catch (\Exception $e) {
            Log::warning('FortuneAIService: บันทึก error ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }
}
