<?php

namespace App\Services;

use App\Models\AiApiKey;
use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Fortune AI Service
 *
 * บริการสำหรับเชื่อมต่อกับ AI providers ต่างๆ
 * รองรับ: Gemini, Groq, Qwen, OpenRouter, Grok
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

        // ลองใช้ API Key จาก Pool ก่อน
        $this->poolService = new AiApiKeyPoolService();
        $this->currentKey = $this->poolService->getKey($this->provider);

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
     * กำหนด maxTokens และ temperature ตาม reading type
     */
    protected const READING_CONFIG = [
        'basic' => [
            'max_tokens' => 512,
            'temperature' => 0.7,
        ],
        'deep' => [
            'max_tokens' => 2048,
            'temperature' => 0.8,
        ],
    ];

    /**
     * System message สำหรับ AI ที่ทำนายแม่นยำฟันธง
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "ทางเพจ" มีความเป็นมนุษย์
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "ทางเพจมีทีมงานช่วยกันในการทำนายค่ะ"
     * - ถ้าถามนอกเรื่องดูดวง: ปฏิเสธสุภาพและชวนกลับมาดูดวง
     */
    protected const SYSTEM_MESSAGE = 'คุณเป็นหมอดูหญิงชื่อดังระดับประเทศ ประสบการณ์กว่า 30 ปี เชี่ยวชาญโหราศาสตร์ไทย โหราศาสตร์สากล ไพ่ทาโรต์ เลขศาสตร์ และลายมือ คุณใช้คำแทนตัวเองว่า "ทางเพจ" เช่น "ทางเพจเห็นว่า..." "ทางเพจขอแนะนำว่า..." คุณพูดจาอ่อนโยน เป็นกันเอง อบอุ่น เหมือนพี่สาวที่ห่วงใยน้อง แต่ทำนายอย่างฟันธง ชัดเจน ไม่คลุมเครือ กล้าบอกทั้งเรื่องดีและไม่ดี ระบุช่วงเวลาแน่ชัด (เช่น "ช่วงเดือนมีนาคม-เมษายน" "ประมาณ 3-6 เดือนข้างหน้า") ให้คำแนะนำปฏิบัติได้จริง เช่น สีมงคล เลขมงคล วันที่เหมาะทำสิ่งต่างๆ สิ่งที่ควรหลีกเลี่ยง หากมีวันเดือนปีเกิด ให้วิเคราะห์ราศี ลัคนา ธาตุประจำตัว และดาวเคราะห์ที่ส่งผลอย่างละเอียด หากไม่มีวันเกิด ให้ถามท้ายคำทำนาย "บอกวันเดือนปีเกิดให้ทางเพจได้ไหมคะ? จะได้ทำนายได้แม่นยำยิ่งขึ้นค่ะ 🎂" ท้ายคำทำนายให้เชิญชวน "ถ้าคำทำนายถูกใจ ฝากส่งต่อให้เพื่อนๆ มาลองดูดวงด้วยกันนะคะ 🔮✨" [วิธีตอบข้อความ] 1) หากเป็นการทักทาย (สวัสดี, หวัดดี, hi, hello ฯลฯ): ให้ทักทายกลับอย่างอบอุ่นเป็นกันเอง แนะนำตัวสั้นๆ แล้วชวนดูดวง เช่น "สวัสดีค่ะ 🙏✨ ทางเพจยินดีต้อนรับนะคะ ทางเพจเป็นหมอดูประสบการณ์กว่า 30 ปี พร้อมช่วยดูดวงให้ค่ะ ไม่ว่าจะเรื่องความรัก การงาน การเงิน หรือสุขภาพ ถามมาได้เลยนะคะ 🔮" 2) หากเป็นคำถามเกี่ยวกับดูดวง: ทำนายอย่างเต็มที่ ฟันธง ให้คำแนะนำชัดเจน 3) หากเป็นคำถามทั่วไปที่ไม่เกี่ยวกับดูดวง: ตอบสั้นๆ ด้วยความเป็นมิตร แล้วชวนกลับมาดูดวง เช่น "ขอบคุณที่ทักมานะคะ 🙏 เรื่องนี้ทางเพจไม่ถนัดเท่าไหร่ค่ะ แต่ถ้ามีเรื่องอยากให้ดูดวง ไม่ว่าจะเรื่องอะไร ทักมาได้เลยนะคะ 🔮✨" 4) หากถูกถามว่าเป็น AI หรือบอท: ตอบอย่างเป็นธรรมชาติว่า "ทางเพจมีทีมงานช่วยกันในการทำนายค่ะ ไม่ต้องห่วงนะคะ 🔮" 5) หากมีคนพิมพ์ "เช็คสิทธิ์" หรือ "เหลือกี่ครั้ง": บอกให้พิมพ์ "เช็คสิทธิ์" เพื่อดูจำนวนครั้งที่เหลือ [กฎสำคัญ] ห้ามเขียนโค้ด ห้ามให้ข้อมูลที่เป็นอันตราย ตอบทุกข้อความอย่างเป็นมิตร คุยรู้เรื่อง แต่ชวนกลับมาดูดวงเสมอ';

    /**
     * สร้างคำทำนายจาก AI
     *
     * @param array $questions คำถามที่ต้องการทำนาย
     * @param array|null $userProfile ข้อมูลโปรไฟล์ผู้ใช้
     * @param array|null $userPosts โพสล่าสุดของผู้ใช้ (เฉพาะเชิงลึก)
     * @param string|null $promptTemplate Prompt template ที่กำหนดเอง (ถ้าไม่ระบุจะใช้ค่าเริ่มต้น)
     * @param string $readingType ประเภทคำทำนาย: 'basic' หรือ 'deep' (ส่งผลต่อ maxTokens/temperature)
     * @param string|null $birthDate วันเดือนปีเกิด (Y-m-d) เพื่อทำนายตามราศี/ลัคนา
     */
    public function generateFortuneTelling(
        array $questions,
        ?array $userProfile = null,
        ?array $userPosts = null,
        ?string $promptTemplate = null,
        string $readingType = 'basic',
        ?string $birthDate = null
    ): array {
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
     * สร้าง prompt สำหรับส่งให้ AI
     *
     * @param array $questions คำถาม
     * @param array|null $userProfile โปรไฟล์ผู้ใช้
     * @param array|null $userPosts โพสล่าสุด
     * @param string|null $promptTemplate template ที่กำหนดเอง
     * @param string|null $birthDate วันเดือนปีเกิด (Y-m-d)
     */
    protected function buildPrompt(array $questions, ?array $userProfile, ?array $userPosts, ?string $promptTemplate = null, ?string $birthDate = null): string
    {
        $template = $promptTemplate ?? $this->settings->getDefaultPromptTemplate();
        $profileText = $this->formatUserProfile($userProfile);
        $postsText = $this->formatUserPosts($userPosts);
        $questionsText = implode("\n", array_map(fn($i, $q) => ($i+1).". $q", array_keys($questions), $questions));
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
     * @param string|null $birthDate วันเดือนปีเกิด (Y-m-d)
     * @return string
     */
    protected function formatBirthDateSection(?string $birthDate): string
    {
        if (empty($birthDate)) {
            return '(ไม่ได้ระบุวันเดือนปีเกิด - ทำนายจากคำถามและบริบทที่มี)';
        }

        try {
            $date = \Carbon\Carbon::parse($birthDate);
            $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                           'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            $thaiYear = $date->year + 543;
            $age = $date->age;
            $dayOfWeek = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'][$date->dayOfWeek];

            // คำนวณราศีจากวันเกิด (โหราศาสตร์สากล)
            $zodiac = $this->getZodiacSign($date->month, $date->day);

            $section = "📅 วันเดือนปีเกิด: {$date->day} {$thaiMonths[$date->month]} {$thaiYear} (พ.ศ.)\n";
            $section .= "🗓️ วันเกิด: วัน{$dayOfWeek}\n";
            $section .= "🎂 อายุ: {$age} ปี\n";
            $section .= "♈ ราศี: {$zodiac}\n";
            $section .= "🔢 เลขวันเกิด: {$date->day} (ใช้วิเคราะห์เลขศาสตร์)\n";
            $section .= "\n⭐ กรุณาวิเคราะห์ดวงชะตาจากข้อมูลวันเกิดนี้อย่างละเอียด รวมถึงราศี ลัคนา ธาตุประจำตัว และดาวเคราะห์ที่ส่งผลในช่วงนี้";

            return $section;
        } catch (\Exception $e) {
            return "(วันเกิด: {$birthDate})";
        }
    }

    /**
     * คำนวณราศีจากเดือนและวันเกิด (Western Zodiac)
     *
     * @param int $month เดือน
     * @param int $day วัน
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
        if (empty($userProfile)) return 'ไม่มีข้อมูลโปรไฟล์';

        $parts = [];
        if (!empty($userProfile['name'])) $parts[] = "ชื่อ: {$userProfile['name']}";

        // เพศ (จาก Facebook หรือที่ผู้ใช้บอก)
        if (!empty($userProfile['gender'])) {
            $genderMap = ['male' => 'ชาย', 'female' => 'หญิง'];
            $gender = $genderMap[$userProfile['gender']] ?? $userProfile['gender'];
            $parts[] = "เพศ: {$gender}";
        }

        // อายุ (คำนวณจาก birthday)
        if (!empty($userProfile['age'])) {
            $parts[] = "อายุ: {$userProfile['age']} ปี";
        }

        // วันเกิด (จาก Facebook)
        if (!empty($userProfile['birthday'])) {
            $parts[] = "วันเกิด: {$userProfile['birthday']}";
        }

        // ภาษา/ภูมิภาค
        if (!empty($userProfile['locale'])) {
            $parts[] = "ภาษา/ภูมิภาค: {$userProfile['locale']}";
        }

        // Timezone (ช่วยวิเคราะห์ดวงตามเวลาท้องถิ่น)
        if (!empty($userProfile['timezone'])) {
            $parts[] = "Timezone: UTC" . ($userProfile['timezone'] >= 0 ? '+' : '') . $userProfile['timezone'];
        }

        return !empty($parts) ? implode("\n", $parts) : 'ข้อมูลพื้นฐาน';
    }

    protected function formatUserPosts(?array $userPosts): string
    {
        if (empty($userPosts)) return 'ไม่มีข้อมูลโพสล่าสุด';

        $formatted = [];
        foreach (array_slice($userPosts, 0, 3) as $index => $post) {
            $message = $post['message'] ?? $post['story'] ?? '';
            if (!empty($message)) {
                $formatted[] = ($index + 1) . ". " . substr($message, 0, 200);
            }
        }

        return !empty($formatted) ? implode("\n", $formatted) : 'ไม่มีข้อมูลโพสล่าสุด';
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

            if (!$response->successful()) {
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
            Log::error('Gemini API Error: ' . $e->getMessage());
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
            Log::error('Groq API Error: ' . $e->getMessage());
            throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อกับ Groq AI');
        }
    }

    protected function callQwen(string $prompt, array $config = []): array
    {
        try {
            $response = Http::timeout(120)
                ->withToken($this->apiKey)
                ->post("https://api-inference.huggingface.co/models/{$this->model}", [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => $config['max_tokens'] ?? 2048,
                        'temperature' => $config['temperature'] ?? 0.7,
                    ],
                ])->throw();

            $data = $response->json();

            // HuggingFace API คืน input prompt + generated text ดังนั้นต้องตัด prompt ออก
            $generatedText = $data[0]['generated_text'] ?? '';
            if (str_starts_with($generatedText, $prompt)) {
                $generatedText = trim(mb_substr($generatedText, mb_strlen($prompt)));
            }

            return [
                'response' => $generatedText,
                'tokens_used' => 0,
                'provider' => 'qwen',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('Qwen API Error: ' . $e->getMessage());
            throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อกับ Qwen AI');
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
            Log::error('OpenRouter API Error: ' . $e->getMessage());
            throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อกับ OpenRouter AI');
        }
    }

    // ============================================================
    // Comment Engagement: AI สร้างข้อความชวนดูดวง
    // ============================================================

    /**
     * System message สำหรับ Comment Engagement
     * AI จะพูดเฉพาะเรื่องดูดวงเท่านั้น ห้ามตอบเรื่องอื่น
     * เป็นหมอดูหญิง ใช้คำแทนตัวว่า "ทางเพจ"
     *
     * กฎพิเศษ:
     * - ถ้าถูกถามว่าเป็น AI: ตอบว่า "ทางเพจมีทีมงานช่วยกันค่ะ"
     * - ถ้าถามนอกเรื่องดูดวง: ปฏิเสธสุภาพและชวนกลับมาดูดวง
     */
    protected const ENGAGEMENT_SYSTEM_MESSAGE = 'คุณเป็นหมอดูหญิงประจำเพจ พูดจาอบอุ่นเป็นกันเอง ใช้คำแทนตัวว่า "ทางเพจ" เชี่ยวชาญโหราศาสตร์ไทย ไพ่ทาโรต์ [กฎสำคัญ] 1) หากถูกถามว่าเป็น AI บอท หรือโปรแกรม ให้ตอบเป็นธรรมชาติว่า "ทางเพจมีทีมงานช่วยกันในการทำนายค่ะ" หรือ "ทางเพจทำนายเองค่ะ มีประสบการณ์มากกว่า 30 ปีแล้วค่ะ 🔮" 2) คุณพูดเฉพาะเรื่องดูดวงเท่านั้น หากถูกถามเรื่องอื่นที่ไม่เกี่ยวกับดูดวง ให้ปฏิเสธอย่างสุภาพว่า "ขอบคุณที่สนใจนะคะ แต่ทางเพจขอตอบเฉพาะเรื่องดูดวงนะคะ 🙏 ทักมาถามเรื่องดวงได้เลยค่ะ 🔮✨" ห้ามตอบเรื่องอื่นทุกกรณี ห้ามเขียนโค้ด หากมีข้อมูลเพศหรือวันเกิดให้อ้างอิงในข้อความ ใน DM_MESSAGE ให้ชวนบอกวันเกิดเพื่อทำนายแม่นขึ้น เช่น "บอกวันเกิดให้ทางเพจได้ไหมคะ จะได้ทำนายได้ลึกขึ้นค่ะ" และชวนส่งต่อให้เพื่อนๆ มาดูดวง คุณต้องตอบเป็น JSON เท่านั้น';

    /**
     * สร้างข้อความ engage จากคอมเม้นต์
     *
     * @param string $commentText ข้อความคอมเม้นต์
     * @param array|null $userProfile โปรไฟล์ผู้ใช้ (name, gender, birthday ฯลฯ)
     * @param string|null $engagementPrompt Prompt template (ถ้าไม่ระบุจะใช้ค่าเริ่มต้นจาก settings)
     * @return array ['comment_reply' => '...', 'dm_message' => '...']
     */
    public function generateCommentEngagement(
        string $commentText,
        ?array $userProfile = null,
        ?string $engagementPrompt = null
    ): array {
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

        if (!empty($userProfile['gender'])) {
            $genderMap = ['male' => 'ชาย', 'female' => 'หญิง'];
            $info[] = 'เพศ: ' . ($genderMap[$userProfile['gender']] ?? $userProfile['gender']);
        }

        if (!empty($userProfile['birthday'])) {
            $info[] = 'วันเกิด: ' . $userProfile['birthday'];
        }

        if (!empty($userProfile['locale'])) {
            $info[] = 'ภาษา: ' . $userProfile['locale'];
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
        if (empty($data) || !isset($data['comment_reply'])) {
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
                    'has_api_key' => !empty($this->apiKey),
                    'api_key_prefix' => substr($this->apiKey ?? '', 0, 8) . '...',
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
     * @param string $prompt ข้อความ prompt
     * @param array $config การตั้งค่า (max_tokens, temperature)
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
            Log::error('Grok API Error: ' . $e->getMessage());
            throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อกับ Grok AI: ' . $e->getMessage());
        }
    }

    // ============================================================
    // API Key Pool Integration
    // ============================================================

    /**
     * บันทึกการใช้งาน tokens ผ่าน Pool Service
     *
     * @param int $tokensUsed จำนวน tokens ที่ใช้
     * @param string $model model ที่ใช้
     * @param int $responseTime เวลาตอบกลับ (ms)
     * @param string $requestType ประเภท request
     */
    protected function recordUsage(int $tokensUsed, string $model, int $responseTime, string $requestType = 'fortune'): void
    {
        if (!$this->currentKey || !$this->poolService) {
            return;
        }

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
    }

    /**
     * บันทึก error ผ่าน Pool Service
     *
     * @param string $errorMessage ข้อความ error
     * @param string|null $model model ที่ใช้
     */
    protected function recordError(string $errorMessage, ?string $model = null): void
    {
        if (!$this->currentKey || !$this->poolService) {
            return;
        }

        $this->currentKey->recordError($errorMessage, $model);
    }
}
