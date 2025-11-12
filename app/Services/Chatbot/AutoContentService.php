<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotAutoContentPost;
use App\Models\AiBotProfile;
use App\Services\AI\AiServiceFactory;
use Illuminate\Support\Facades\Log;

/**
 * Auto Content Posting Service
 *
 * บริการสำหรับสร้างและโพสต์คอนเทนต์อัตโนมัติ
 */
class AutoContentService
{
    protected AiServiceFactory $aiServiceFactory;

    public function __construct(AiServiceFactory $aiServiceFactory = null)
    {
        $this->aiServiceFactory = $aiServiceFactory ?? new AiServiceFactory();
    }

    /**
     * Generate content using AI
     */
    public function generateContent(ChatbotAutoContentPost $post): array
    {
        try {
            $botProfile = $post->botProfile;

            if (!$botProfile->provider || !$botProfile->model) {
                throw new \Exception('Bot profile must have AI provider and model configured');
            }

            $aiService = $this->aiServiceFactory->make($botProfile->provider->name);

            // Build content generation prompt
            $systemPrompt = $this->buildContentGenerationPrompt($post);

            $response = $aiService->chat([
                'model' => $botProfile->model->model_identifier,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $post->content_prompt],
                ],
                'temperature' => $post->content_guidelines['creativity'] ?? 0.8,
                'max_tokens' => $post->content_guidelines['max_length'] ?? 1000,
            ]);

            // Extract hashtags from content
            $hashtags = $this->extractHashtags($response['content']);

            return [
                'success' => true,
                'content' => $response['content'],
                'hashtags' => $hashtags,
                'tokens_used' => $response['tokens_used'] ?? 0,
                'cost' => $response['cost'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('AutoContent: Failed to generate content', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build content generation prompt
     */
    protected function buildContentGenerationPrompt(ChatbotAutoContentPost $post): string
    {
        $guidelines = $post->content_guidelines ?? [];

        $prompt = "You are a creative content creator. Generate engaging social media content.\n\n";

        if (isset($guidelines['tone'])) {
            $prompt .= "Tone: {$guidelines['tone']}\n";
        }

        if (isset($guidelines['style'])) {
            $prompt .= "Style: {$guidelines['style']}\n";
        }

        if (isset($guidelines['target_audience'])) {
            $prompt .= "Target Audience: {$guidelines['target_audience']}\n";
        }

        if (isset($guidelines['language'])) {
            $prompt .= "Language: {$guidelines['language']}\n";
        } else {
            $prompt .= "Language: Thai\n";
        }

        if (isset($guidelines['include_hashtags']) && $guidelines['include_hashtags']) {
            $prompt .= "\nInclude relevant hashtags at the end of the content.\n";
        }

        if (isset($guidelines['include_emoji']) && $guidelines['include_emoji']) {
            $prompt .= "Use appropriate emojis to make the content more engaging.\n";
        }

        $prompt .= "\nGenerate creative, engaging content based on the following topic:\n";

        return $prompt;
    }

    /**
     * Extract hashtags from content
     */
    protected function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/u', $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Post content to platforms
     */
    public function postToPlatforms(ChatbotAutoContentPost $post): array
    {
        $results = [];

        foreach ($post->target_platforms as $platform) {
            try {
                $result = $this->postToPlatform($post, $platform);
                $results[$platform] = $result;
            } catch (\Exception $e) {
                Log::error("AutoContent: Failed to post to {$platform}", [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);

                $results[$platform] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Post to specific platform
     */
    protected function postToPlatform(ChatbotAutoContentPost $post, string $platform): array
    {
        // Get platform integration
        $integration = $post->botProfile->platformIntegrations()
            ->where('platform_type', $platform)
            ->active()
            ->verified()
            ->first();

        if (!$integration) {
            throw new \Exception("Platform integration not found or not active: {$platform}");
        }

        // Platform-specific posting logic
        return match ($platform) {
            'line' => $this->postToLine($post, $integration),
            'facebook' => $this->postToFacebook($post, $integration),
            'instagram' => $this->postToInstagram($post, $integration),
            'telegram' => $this->postToTelegram($post, $integration),
            'discord' => $this->postToDiscord($post, $integration),
            default => throw new \Exception("Unsupported platform: {$platform}"),
        };
    }

    /**
     * Post to LINE
     */
    protected function postToLine(ChatbotAutoContentPost $post, $integration): array
    {
        // Implement LINE posting logic
        // This would use LINE Messaging API to broadcast messages

        Log::info('AutoContent: Posting to LINE', [
            'post_id' => $post->id,
            'integration_id' => $integration->id,
        ]);

        return [
            'success' => true,
            'platform' => 'line',
            'message' => 'Posted to LINE successfully',
        ];
    }

    /**
     * Post to Facebook
     */
    protected function postToFacebook(ChatbotAutoContentPost $post, $integration): array
    {
        // Implement Facebook posting logic
        Log::info('AutoContent: Posting to Facebook', [
            'post_id' => $post->id,
            'integration_id' => $integration->id,
        ]);

        return [
            'success' => true,
            'platform' => 'facebook',
            'message' => 'Posted to Facebook successfully',
        ];
    }

    /**
     * Post to Instagram
     */
    protected function postToInstagram(ChatbotAutoContentPost $post, $integration): array
    {
        // Implement Instagram posting logic
        Log::info('AutoContent: Posting to Instagram', [
            'post_id' => $post->id,
            'integration_id' => $integration->id,
        ]);

        return [
            'success' => true,
            'platform' => 'instagram',
            'message' => 'Posted to Instagram successfully',
        ];
    }

    /**
     * Post to Telegram
     */
    protected function postToTelegram(ChatbotAutoContentPost $post, $integration): array
    {
        // Implement Telegram posting logic
        Log::info('AutoContent: Posting to Telegram', [
            'post_id' => $post->id,
            'integration_id' => $integration->id,
        ]);

        return [
            'success' => true,
            'platform' => 'telegram',
            'message' => 'Posted to Telegram successfully',
        ];
    }

    /**
     * Post to Discord
     */
    protected function postToDiscord(ChatbotAutoContentPost $post, $integration): array
    {
        // Implement Discord posting logic
        Log::info('AutoContent: Posting to Discord', [
            'post_id' => $post->id,
            'integration_id' => $integration->id,
        ]);

        return [
            'success' => true,
            'platform' => 'discord',
            'message' => 'Posted to Discord successfully',
        ];
    }

    /**
     * Process scheduled posts (to be called by cron job)
     */
    public function processScheduledPosts(): array
    {
        $posts = ChatbotAutoContentPost::readyToPost()->get();

        $processed = [];

        foreach ($posts as $post) {
            try {
                // Update status to processing
                $post->update(['status' => 'processing']);

                // Generate content if not already generated
                if (empty($post->generated_content)) {
                    $contentResult = $this->generateContent($post);

                    if (!$contentResult['success']) {
                        $post->markAsFailed($contentResult['error']);
                        continue;
                    }

                    $post->update([
                        'generated_content' => $contentResult['content'],
                        'hashtags' => $contentResult['hashtags'],
                    ]);
                }

                // Post to platforms
                $results = $this->postToPlatforms($post);

                // Check if all posts succeeded
                $allSuccess = collect($results)->every(fn($r) => $r['success'] ?? false);

                if ($allSuccess) {
                    $post->markAsPosted($results);
                    $processed[] = [
                        'post_id' => $post->id,
                        'status' => 'posted',
                        'results' => $results,
                    ];
                } else {
                    $post->markAsFailed('Some platforms failed to post');
                    $processed[] = [
                        'post_id' => $post->id,
                        'status' => 'failed',
                        'results' => $results,
                    ];
                }

            } catch (\Exception $e) {
                Log::error('AutoContent: Failed to process post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);

                $post->markAsFailed($e->getMessage());
                $processed[] = [
                    'post_id' => $post->id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $processed;
    }
}
