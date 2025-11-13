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
You are an expert web developer specializing in creating beautiful, modern, and responsive web pages.

Your task is to generate complete web pages based on user descriptions. Follow these guidelines:

1. Create clean, semantic HTML5 code
2. Use modern CSS with responsive design (mobile-first approach)
3. Include inline CSS in a <style> tag
4. Add interactive JavaScript if needed in a <script> tag
5. Use Tailwind CSS utility classes when appropriate
6. Make the design visually appealing with good color schemes
7. Ensure accessibility (proper heading structure, alt text, ARIA labels)
8. Support Thai language properly (use appropriate fonts)

Return your response in this exact format:

```html
<!-- TITLE: [Page Title Here] -->
<!-- DESCRIPTION: [Brief description of the page] -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[Page Title]</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Your CSS here */
    </style>
</head>
<body>
    <!-- Your HTML content here -->

    <script>
        // Your JavaScript here (if needed)
    </script>
</body>
</html>
```

Important: Return ONLY the HTML code with embedded CSS and JavaScript. Do not include any explanations or markdown outside the HTML.
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
