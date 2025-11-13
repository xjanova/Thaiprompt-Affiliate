<?php

namespace App\Services;

use App\Services\AI\AiServiceFactory;
use App\Models\AiProvider;
use App\Models\AiModel;
use Illuminate\Support\Facades\Log;

class PromptToWebService
{
    protected $aiServiceFactory;

    public function __construct(AiServiceFactory $aiServiceFactory)
    {
        $this->aiServiceFactory = $aiServiceFactory;
    }

    /**
     * Generate a web page from a prompt using AI
     *
     * @param string $prompt User's description of the desired web page
     * @param string|null $providerName AI provider to use (default: auto-select)
     * @param string|null $modelName Specific model to use
     * @return array Generated HTML, CSS, JS and metadata
     */
    public function generateWebPage(string $prompt, ?string $providerName = null, ?string $modelName = null): array
    {
        try {
            // Check if prompt requests complex system
            $complexityCheck = $this->checkPromptComplexity($prompt);
            if (!$complexityCheck['allowed']) {
                throw new \Exception($complexityCheck['message']);
            }

            // Get AI provider and model
            $provider = $this->getProvider($providerName);
            $model = $this->getModel($provider, $modelName);

            if (!$provider || !$model) {
                throw new \Exception('ไม่พบ AI provider หรือ model ที่ระบุ');
            }

            // Create AI service instance
            $aiService = $this->aiServiceFactory->create($provider->name);

            // Build the system prompt for web generation
            $systemPrompt = $this->buildSystemPrompt();

            // Build the user prompt
            $userPrompt = $this->buildUserPrompt($prompt);

            // Generate using AI
            $response = $aiService->generateCompletion(
                $userPrompt,
                $model->model_identifier,
                [
                    'system' => $systemPrompt,
                    'temperature' => 0.7,
                    'max_tokens' => 4000,
                ]
            );

            // Parse the AI response to extract HTML, CSS, JS
            $parsedContent = $this->parseAiResponse($response['content']);

            return [
                'success' => true,
                'html' => $parsedContent['html'],
                'css' => $parsedContent['css'],
                'js' => $parsedContent['js'],
                'title' => $parsedContent['title'],
                'description' => $parsedContent['description'],
                'full_content' => $this->buildFullHtmlPage($parsedContent),
                'tokens_used' => $response['tokens_used'] ?? 0,
                'cost' => $response['cost'] ?? 0,
                'provider' => $provider->name,
                'model' => $model->model_identifier,
            ];

        } catch (\Exception $e) {
            Log::error('Prompt to Web generation error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'html' => '',
                'css' => '',
                'js' => '',
            ];
        }
    }

    /**
     * Build system prompt for web page generation
     */
    protected function buildSystemPrompt(): string
    {
        return <<<EOT
You are an expert web developer specializing in creating SIMPLE, CLEAN, and beautiful web pages.

IMPORTANT: Create SIMPLE pages only. Keep it minimal and easy to understand.

Your task is to generate simple web pages based on user descriptions. Follow these guidelines:

1. Create SIMPLE, clean HTML5 code (keep it minimal - 1-2 sections max)
2. Use Tailwind CSS for styling (already included via CDN)
3. Use simple, clean design with plenty of white space
4. Choose ONE main color scheme and stick to it
5. Keep layout simple: header, main content, footer (if needed)
6. Use simple animations only (CSS transitions, no complex JavaScript)
7. Support Thai language properly (Tailwind includes Thai fonts)
8. Make it responsive but keep the structure simple

STYLE GUIDELINES:
- Use Tailwind utility classes (bg-*, text-*, p-*, m-*, rounded-*, etc.)
- Simple gradient backgrounds are OK (bg-gradient-to-*)
- Use shadow-* for depth
- Keep content centered and clean
- Maximum 2-3 sections per page

Return your response in this exact format:

```html
<!-- TITLE: [Simple Page Title] -->
<!-- DESCRIPTION: [Brief description] -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[Page Title]</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Minimal custom CSS only if absolutely needed */
    </style>
</head>
<body class="bg-gray-50">
    <!-- Simple, clean HTML content here -->
    <!-- Keep it minimal: 1-2 main sections -->

    <script>
        // Simple JavaScript only if needed (avoid if possible)
    </script>
</body>
</html>
```

REMEMBER:
- KEEP IT SIMPLE - no complex layouts
- KEEP IT CLEAN - lots of white space
- KEEP IT MINIMAL - 1-2 sections maximum
- Use Tailwind classes primarily

Return ONLY the HTML code. No explanations or markdown outside the HTML.
EOT;
    }

    /**
     * Build user prompt for specific web page request
     */
    protected function buildUserPrompt(string $userRequest): string
    {
        return <<<EOT
สร้างหน้าเว็บตามคำอธิบายนี้:

{$userRequest}

กรุณาสร้างหน้าเว็บที่สวยงาม ทันสมัย และใช้งานได้จริง รองรับภาษาไทย และทำงานได้ดีบนทุกอุปกรณ์
EOT;
    }

    /**
     * Parse AI response to extract HTML, CSS, JS components
     */
    protected function parseAiResponse(string $response): array
    {
        $result = [
            'html' => '',
            'css' => '',
            'js' => '',
            'title' => 'Generated Page',
            'description' => 'AI Generated Web Page',
        ];

        // Extract title from comment
        if (preg_match('/<!--\s*TITLE:\s*(.+?)\s*-->/i', $response, $matches)) {
            $result['title'] = trim($matches[1]);
        }

        // Extract description from comment
        if (preg_match('/<!--\s*DESCRIPTION:\s*(.+?)\s*-->/i', $response, $matches)) {
            $result['description'] = trim($matches[1]);
        }

        // Remove markdown code blocks if present
        $response = preg_replace('/```html\s*/i', '', $response);
        $response = preg_replace('/```\s*$/i', '', $response);

        // Extract CSS
        if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $response, $matches)) {
            $result['css'] = trim($matches[1]);
        }

        // Extract JavaScript
        if (preg_match('/<script[^>]*>(.*?)<\/script>/is', $response, $matches)) {
            // Skip external script tags
            if (strpos($matches[0], 'src=') === false) {
                $result['js'] = trim($matches[1]);
            }
        }

        // Extract body content (or full HTML if no body tag)
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $response, $matches)) {
            $result['html'] = trim($matches[1]);
        } else {
            // If no body tag, use the entire response as HTML
            $result['html'] = $response;
        }

        return $result;
    }

    /**
     * Build complete HTML page from parsed components
     */
    protected function buildFullHtmlPage(array $content): string
    {
        $html = <<<EOT
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{$content['description']}">
    <title>{$content['title']}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        {$content['css']}
    </style>
</head>
<body>
    {$content['html']}

    <script>
        {$content['js']}
    </script>
</body>
</html>
EOT;

        return $html;
    }

    /**
     * Check if the prompt requests complex system features
     */
    protected function checkPromptComplexity(string $prompt): array
    {
        $lowerPrompt = mb_strtolower($prompt);

        // Keywords that indicate complex systems
        $complexKeywords = [
            // Database related
            'database', 'ฐานข้อมูล', 'mysql', 'mongodb', 'sql', 'crud',
            // Backend related
            'backend', 'api', 'rest api', 'graphql', 'server', 'เซิร์ฟเวอร์',
            // Authentication
            'login', 'เข้าสู่ระบบ', 'ล็อกอิน', 'authentication', 'auth', 'register', 'สมัครสมาชิก',
            'user system', 'ระบบสมาชิก', 'ระบบผู้ใช้',
            // E-commerce
            'payment', 'ชำระเงิน', 'cart', 'ตะกร้า', 'checkout', 'order', 'สั่งซื้อ',
            // Real-time features
            'real-time', 'realtime', 'websocket', 'chat', 'แชท',
            // File upload
            'upload', 'อัพโหลด', 'file upload',
            // Complex functionality
            'dashboard', 'แดชบอร์ด', 'admin panel', 'cms',
            'search', 'ค้นหา', 'filter', 'กรอง',
        ];

        foreach ($complexKeywords as $keyword) {
            if (strpos($lowerPrompt, $keyword) !== false) {
                return [
                    'allowed' => false,
                    'message' => "⚠️ ขออภัยครับ ระบบนี้รองรับเฉพาะการสร้างหน้าเว็บแบบง่ายๆ (Landing Page, Presentation Page) เท่านั้น\n\n" .
                                "ไม่รองรับการสร้าง:\n" .
                                "- ระบบที่ต้องใช้ฐานข้อมูล\n" .
                                "- ระบบ Backend/API\n" .
                                "- ระบบ Login/สมาชิก\n" .
                                "- ระบบ E-commerce\n" .
                                "- ระบบที่ซับซ้อนอื่นๆ\n\n" .
                                "💡 แนะนำ: ลองขอหน้าเว็บแบบง่ายๆ เช่น:\n" .
                                "- หน้า Landing Page แนะนำธุรกิจ\n" .
                                "- หน้าแสดงข้อมูลบริษัท\n" .
                                "- หน้าแสดงโปรโมชั่น\n" .
                                "- หน้าแสดงรายละเอียดสินค้า (แบบ static)"
                ];
            }
        }

        return [
            'allowed' => true,
            'message' => ''
        ];
    }

    /**
     * Get AI provider (auto-select if not specified)
     */
    protected function getProvider(?string $providerName = null): ?AiProvider
    {
        if ($providerName) {
            return AiProvider::where('name', $providerName)
                ->where('is_active', true)
                ->first();
        }

        // Auto-select: prefer Claude, then OpenAI, then others
        $preferredProviders = ['claude', 'openai', 'gemini', 'deepseek'];

        foreach ($preferredProviders as $name) {
            $provider = AiProvider::where('name', $name)
                ->where('is_active', true)
                ->first();

            if ($provider) {
                return $provider;
            }
        }

        // Fallback: get any active provider
        return AiProvider::where('is_active', true)->first();
    }

    /**
     * Get AI model (auto-select if not specified)
     */
    protected function getModel(AiProvider $provider, ?string $modelName = null): ?AiModel
    {
        if ($modelName) {
            return AiModel::where('provider_id', $provider->id)
                ->where('model_identifier', $modelName)
                ->where('is_active', true)
                ->first();
        }

        // Auto-select: prefer models good for content generation
        // For Claude: prefer sonnet or opus
        // For OpenAI: prefer GPT-4 or GPT-3.5-turbo

        $preferredModels = [
            'claude-3-sonnet',
            'claude-3-opus',
            'gpt-4',
            'gpt-3.5-turbo',
            'gemini-pro',
        ];

        foreach ($preferredModels as $modelPrefix) {
            $model = AiModel::where('provider_id', $provider->id)
                ->where('model_identifier', 'like', $modelPrefix . '%')
                ->where('is_active', true)
                ->first();

            if ($model) {
                return $model;
            }
        }

        // Fallback: get any active model for this provider
        return AiModel::where('provider_id', $provider->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Improve an existing web page based on feedback
     */
    public function improveWebPage(string $currentHtml, string $feedback, ?string $providerName = null): array
    {
        try {
            $provider = $this->getProvider($providerName);
            $model = $this->getModel($provider);

            if (!$provider || !$model) {
                throw new \Exception('ไม่พบ AI provider หรือ model ที่ระบุ');
            }

            $aiService = $this->aiServiceFactory->create($provider->name);

            $systemPrompt = $this->buildSystemPrompt();

            $userPrompt = <<<EOT
ปรับปรุงหน้าเว็บนี้ตามข้อเสนอแนะ:

หน้าเว็บปัจจุบัน:
```html
{$currentHtml}
```

ข้อเสนอแนะในการปรับปรุง:
{$feedback}

กรุณาสร้างหน้าเว็บใหม่ที่ดีขึ้นตามข้อเสนอแนะ
EOT;

            $response = $aiService->generateCompletion(
                $userPrompt,
                $model->model_identifier,
                [
                    'system' => $systemPrompt,
                    'temperature' => 0.7,
                    'max_tokens' => 4000,
                ]
            );

            $parsedContent = $this->parseAiResponse($response['content']);

            return [
                'success' => true,
                'html' => $parsedContent['html'],
                'css' => $parsedContent['css'],
                'js' => $parsedContent['js'],
                'title' => $parsedContent['title'],
                'description' => $parsedContent['description'],
                'full_content' => $this->buildFullHtmlPage($parsedContent),
                'tokens_used' => $response['tokens_used'] ?? 0,
                'cost' => $response['cost'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('Prompt to Web improvement error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
