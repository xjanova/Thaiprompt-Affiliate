<?php

namespace App\Services\BotAutomation;

use App\Models\BotAutomation\BotAutomation;
use App\Models\BotAutomation\BotContentTemplate;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\Log;

class BotContentGenerationService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Generate content for automation
     */
    public function generateContent(BotAutomation $automation): array
    {
        return match($automation->content_source) {
            'custom' => $this->generateFromCustom($automation),
            'template' => $this->generateFromTemplate($automation),
            'ai_generated' => $this->generateFromAI($automation),
            default => throw new \Exception("Unknown content source: {$automation->content_source}"),
        };
    }

    /**
     * Generate from custom content
     */
    protected function generateFromCustom(BotAutomation $automation): array
    {
        return [
            'text' => $automation->custom_content ?? '',
            'media_urls' => [],
            'hashtags' => [],
            'source' => 'custom',
        ];
    }

    /**
     * Generate from template
     */
    protected function generateFromTemplate(BotAutomation $automation): array
    {
        if (!$automation->template) {
            throw new \Exception("Template not found for automation");
        }

        $template = $automation->template;
        $variables = $this->prepareTemplateVariables($automation);

        $content = $template->render($variables);

        $template->incrementUsage();

        return [
            'text' => $content,
            'media_urls' => $template->media_urls ?? [],
            'hashtags' => $template->hashtags ?? [],
            'source' => 'template',
            'template_id' => $template->id,
        ];
    }

    /**
     * Generate from AI
     */
    protected function generateFromAI(BotAutomation $automation): array
    {
        if (!$automation->ai_generation_prompt) {
            throw new \Exception("AI generation prompt not provided");
        }

        try {
            // Get AI bot profile or use default
            $botProfile = $automation->aiBotProfile;

            if (!$botProfile) {
                throw new \Exception("AI bot profile not configured");
            }

            // Prepare prompt with context
            $prompt = $this->prepareAiPrompt($automation);

            // Generate content using AI service
            $response = $this->aiService->generateText([
                'bot_profile_id' => $botProfile->id,
                'prompt' => $prompt,
                'max_tokens' => $botProfile->max_tokens ?? 500,
                'temperature' => $botProfile->temperature ?? 0.7,
            ]);

            // Extract hashtags from generated content
            $hashtags = $this->extractHashtags($response['text']);

            return [
                'text' => $response['text'],
                'media_urls' => [],
                'hashtags' => $hashtags,
                'source' => 'ai_generated',
                'tokens_used' => $response['tokens_used'] ?? 0,
                'cost' => $response['cost'] ?? 0,
                'model' => $response['model'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            Log::error("AI content generation failed", [
                'automation_id' => $automation->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to custom content if available
            if ($automation->custom_content) {
                return $this->generateFromCustom($automation);
            }

            throw $e;
        }
    }

    /**
     * Prepare template variables
     */
    protected function prepareTemplateVariables(BotAutomation $automation): array
    {
        $user = $automation->user;

        return [
            'user_name' => $user->name ?? '',
            'user_email' => $user->email ?? '',
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i'),
            'day' => now()->format('l'),
            'month' => now()->format('F'),
            'year' => now()->format('Y'),
            // Add more variables as needed
        ];
    }

    /**
     * Prepare AI prompt with context
     */
    protected function prepareAiPrompt(BotAutomation $automation): string
    {
        $basePrompt = $automation->ai_generation_prompt;

        // Add context based on automation type
        $context = match($automation->automation_type) {
            'scheduled_post' => "สร้างโพสต์โซเชียลมีเดียที่น่าสนใจและดึงดูดผู้ติดตาม",
            'customer_support' => "ตอบคำถามลูกค้าอย่างมืออาชีพและเป็นมิตร",
            'sales_assistant' => "แนะนำสินค้าและปิดการขายอย่างมีประสิทธิภาพ",
            'engagement' => "สร้างการมีส่วนร่วมกับผู้ติดตาม",
            default => "",
        };

        return "{$context}\n\n{$basePrompt}";
    }

    /**
     * Extract hashtags from text
     */
    protected function extractHashtags(string $text): array
    {
        preg_match_all('/#(\w+)/', $text, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Generate content for specific platform
     */
    public function generateForPlatform(BotAutomation $automation, string $platformCode): array
    {
        $baseContent = $this->generateContent($automation);

        // Customize content based on platform
        return match($platformCode) {
            'tiktok' => $this->customizeForTikTok($baseContent),
            'facebook' => $this->customizeForFacebook($baseContent),
            'instagram' => $this->customizeForInstagram($baseContent),
            'twitter' => $this->customizeForTwitter($baseContent),
            'line' => $this->customizeForLine($baseContent),
            default => $baseContent,
        };
    }

    /**
     * Customize content for TikTok
     */
    protected function customizeForTikTok(array $content): array
    {
        // TikTok-specific customization
        // Max 2200 characters, trending hashtags
        $content['text'] = mb_substr($content['text'], 0, 2200);
        return $content;
    }

    /**
     * Customize content for Facebook
     */
    protected function customizeForFacebook(array $content): array
    {
        // Facebook-specific customization
        // Max 63,206 characters
        return $content;
    }

    /**
     * Customize content for Instagram
     */
    protected function customizeForInstagram(array $content): array
    {
        // Instagram-specific customization
        // Max 2200 characters, max 30 hashtags
        $content['text'] = mb_substr($content['text'], 0, 2200);
        $content['hashtags'] = array_slice($content['hashtags'], 0, 30);
        return $content;
    }

    /**
     * Customize content for Twitter
     */
    protected function customizeForTwitter(array $content): array
    {
        // Twitter-specific customization
        // Max 280 characters
        $content['text'] = mb_substr($content['text'], 0, 280);
        return $content;
    }

    /**
     * Customize content for LINE
     */
    protected function customizeForLine(array $content): array
    {
        // LINE-specific customization
        // Max 5000 characters
        $content['text'] = mb_substr($content['text'], 0, 5000);
        return $content;
    }
}
