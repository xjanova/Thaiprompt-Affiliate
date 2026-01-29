<?php

namespace App\Services;

use App\Models\FortuneTellingSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Fortune AI Service
 *
 * บริการสำหรับเชื่อมต่อกับ AI providers ต่างๆ
 * รองรับ: Gemini, Groq, Qwen, OpenRouter
 */
class FortuneAIService
{
    protected $settings;
    protected $provider;
    protected $apiKey;
    protected $model;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();

        // ใช้ methods ใหม่ที่รองรับ global AI settings
        $this->provider = $this->settings->getActualAIProvider();
        $this->apiKey = $this->settings->getActualAIApiKey();
        $this->model = $this->settings->getActualAIModel();
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
     * สร้างคำทำนายจาก AI
     *
     * @param array $questions คำถามที่ต้องการทำนาย
     * @param array|null $userProfile ข้อมูลโปรไฟล์ผู้ใช้
     * @param array|null $userPosts โพสล่าสุดของผู้ใช้ (เฉพาะเชิงลึก)
     * @param string|null $promptTemplate Prompt template ที่กำหนดเอง (ถ้าไม่ระบุจะใช้ค่าเริ่มต้น)
     * @param string $readingType ประเภทคำทำนาย: 'basic' หรือ 'deep' (ส่งผลต่อ maxTokens/temperature)
     */
    public function generateFortuneTelling(
        array $questions,
        ?array $userProfile = null,
        ?array $userPosts = null,
        ?string $promptTemplate = null,
        string $readingType = 'basic'
    ): array {
        $prompt = $this->buildPrompt($questions, $userProfile, $userPosts, $promptTemplate);
        $config = self::READING_CONFIG[$readingType] ?? self::READING_CONFIG['basic'];

        return match ($this->provider) {
            'gemini' => $this->callGemini($prompt, $config),
            'groq' => $this->callGroq($prompt, $config),
            'qwen' => $this->callQwen($prompt, $config),
            'openrouter' => $this->callOpenRouter($prompt, $config),
            default => throw new Exception("AI Provider '{$this->provider}' ไม่รองรับ"),
        };
    }

    /**
     * สร้าง prompt สำหรับส่งให้ AI
     *
     * @param array $questions คำถาม
     * @param array|null $userProfile โปรไฟล์ผู้ใช้
     * @param array|null $userPosts โพสล่าสุด
     * @param string|null $promptTemplate template ที่กำหนดเอง
     */
    protected function buildPrompt(array $questions, ?array $userProfile, ?array $userPosts, ?string $promptTemplate = null): string
    {
        $template = $promptTemplate ?? $this->settings->getDefaultPromptTemplate();
        $profileText = $this->formatUserProfile($userProfile);
        $postsText = $this->formatUserPosts($userPosts);
        $questionsText = implode("\n", array_map(fn($i, $q) => ($i+1).". $q", array_keys($questions), $questions));

        return str_replace(
            ['{user_profile}', '{user_posts}', '{questions}'],
            [$profileText, $postsText, $questionsText],
            $template
        );
    }

    protected function formatUserProfile(?array $userProfile): string
    {
        if (empty($userProfile)) return 'ไม่มีข้อมูลโปรไฟล์';
        
        $parts = [];
        if (!empty($userProfile['name'])) $parts[] = "ชื่อ: {$userProfile['name']}";
        if (!empty($userProfile['gender'])) $parts[] = "เพศ: {$userProfile['gender']}";
        if (!empty($userProfile['age'])) $parts[] = "อายุ: {$userProfile['age']} ปี";

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
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => $config['temperature'] ?? 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => $config['max_tokens'] ?? 2048,
                ],
            ])->throw();

            $data = $response->json();

            return [
                'response' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
                'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                'provider' => 'gemini',
                'model' => $this->model,
            ];
        } catch (Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
            throw new Exception('เกิดข้อผิดพลาดในการเชื่อมต่อกับ Gemini AI');
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
                        ['role' => 'system', 'content' => 'คุณเป็นหมอดูมืออาชีพ'],
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

            return [
                'response' => $data[0]['generated_text'] ?? '',
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
                        ['role' => 'system', 'content' => 'คุณเป็นหมอดูมืออาชีพ'],
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

    public function testConnection(): array
    {
        try {
            $result = $this->generateFortuneTelling(['ทดสอบการเชื่อมต่อ AI'], null, null);
            return ['success' => true, 'message' => "เชื่อมต่อกับ {$this->provider} สำเร็จ"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
