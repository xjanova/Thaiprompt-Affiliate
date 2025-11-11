<?php

namespace App\Services\TrendDetection;

use App\Models\ViralTrend;
use App\Models\AiProvider;
use App\Models\AiModel;
use App\Services\AI\AiServiceFactory;
use Illuminate\Support\Facades\Log;

class TrendContentGeneratorService
{
    /**
     * Generate content for a viral trend using AI
     */
    public function generateContent(
        ViralTrend $trend,
        string $contentType = 'blog',
        ?AiProvider $aiProvider = null,
        ?AiModel $aiModel = null
    ): array {
        try {
            // Get AI provider and model
            if (!$aiProvider || !$aiModel) {
                $defaultProvider = $this->getDefaultProvider();
                $aiProvider = $aiProvider ?? $defaultProvider['provider'];
                $aiModel = $aiModel ?? $defaultProvider['model'];
            }

            // Create AI service
            $aiService = AiServiceFactory::create($aiProvider, $aiModel);

            // Generate content based on type
            $content = match($contentType) {
                'blog' => $this->generateBlogPost($trend, $aiService),
                'social' => $this->generateSocialPost($trend, $aiService),
                'summary' => $this->generateSummary($trend, $aiService),
                'newsletter' => $this->generateNewsletter($trend, $aiService),
                default => throw new \Exception("Unsupported content type: {$contentType}")
            };

            // Store generated content
            $trend->storeGeneratedContent([
                'type' => $contentType,
                'content' => $content,
                'ai_provider' => $aiProvider->name,
                'ai_model' => $aiModel->name,
                'generated_at' => now()->toDateTimeString(),
            ]);

            return [
                'success' => true,
                'content' => $content,
                'metadata' => [
                    'type' => $contentType,
                    'provider' => $aiProvider->name,
                    'model' => $aiModel->name,
                ],
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate content for trend {$trend->id}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate blog post
     */
    protected function generateBlogPost(ViralTrend $trend, $aiService): array
    {
        $context = $this->buildContext($trend);

        $prompt = $this->buildPrompt([
            'type' => 'blog',
            'context' => $context,
            'instructions' => [
                'เขียนบทความบล็อกเกี่ยวกับเทรนด์ไวรัลนี้',
                'ความยาวประมาณ 800-1200 คำ',
                'เขียนเป็นภาษาไทยที่เป็นกันเอง อ่านง่าย',
                'ใส่หัวข้อย่อย (H2, H3) ให้เหมาะสม',
                'ให้ข้อมูลเชิงลึกและวิเคราะห์',
                'จบด้วย Call-to-Action',
            ],
        ]);

        $response = $aiService->chat([
            ['role' => 'system', 'content' => 'คุณเป็นนักเขียนคอนเทนต์มืออาชีพที่เชี่ยวชาญการเขียนเกี่ยวกับเทรนด์และไวรัลโซเชียล'],
            ['role' => 'user', 'content' => $prompt],
        ], [
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        $generatedText = $response['message'] ?? '';

        return [
            'title' => $this->extractTitle($generatedText) ?? $trend->title,
            'content' => $generatedText,
            'excerpt' => $this->generateExcerpt($generatedText),
            'tags' => $this->extractTags($trend),
            'seo_title' => $this->generateSeoTitle($trend),
            'meta_description' => $this->generateMetaDescription($trend),
        ];
    }

    /**
     * Generate social media post
     */
    protected function generateSocialPost(ViralTrend $trend, $aiService): array
    {
        $context = $this->buildContext($trend);

        $platforms = ['facebook', 'twitter', 'instagram', 'tiktok'];
        $posts = [];

        foreach ($platforms as $platform) {
            $prompt = $this->buildPrompt([
                'type' => 'social',
                'platform' => $platform,
                'context' => $context,
                'instructions' => $this->getSocialPlatformInstructions($platform),
            ]);

            $response = $aiService->chat([
                ['role' => 'system', 'content' => "คุณเป็นผู้เชี่ยวชาญด้าน {$platform} marketing"],
                ['role' => 'user', 'content' => $prompt],
            ], [
                'temperature' => 0.8,
                'max_tokens' => 500,
            ]);

            $posts[$platform] = [
                'text' => $response['message'] ?? '',
                'hashtags' => $this->generateHashtags($trend, $platform),
            ];
        }

        return $posts;
    }

    /**
     * Generate summary
     */
    protected function generateSummary(ViralTrend $trend, $aiService): array
    {
        $context = $this->buildContext($trend);

        $prompt = "สรุปเทรนด์ไวรัลนี้ให้กระชับ ชัดเจน ภายใน 3-5 ประโยค:\n\n{$context}";

        $response = $aiService->chat([
            ['role' => 'system', 'content' => 'คุณเป็นผู้เชี่ยวชาญด้านการสรุปข้อมูลให้กระชับและตรงประเด็น'],
            ['role' => 'user', 'content' => $prompt],
        ], [
            'temperature' => 0.5,
            'max_tokens' => 300,
        ]);

        return [
            'summary' => $response['message'] ?? '',
            'key_points' => $this->extractKeyPoints($trend),
        ];
    }

    /**
     * Generate newsletter content
     */
    protected function generateNewsletter(ViralTrend $trend, $aiService): array
    {
        $context = $this->buildContext($trend);

        $prompt = $this->buildPrompt([
            'type' => 'newsletter',
            'context' => $context,
            'instructions' => [
                'เขียนคอนเทนต์สำหรับ Newsletter รายสัปดาห์',
                'หัวข้อที่น่าสนใจ (Subject Line)',
                'เนื้อหาประมาณ 300-500 คำ',
                'เน้นความน่าสนใจและมีประโยชน์',
                'ใส่ลิงก์และ CTA',
            ],
        ]);

        $response = $aiService->chat([
            ['role' => 'system', 'content' => 'คุณเป็นนักเขียน Newsletter มืออาชีพ'],
            ['role' => 'user', 'content' => $prompt],
        ], [
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        return [
            'subject' => $this->extractSubjectLine($response['message'] ?? ''),
            'content' => $response['message'] ?? '',
            'preview_text' => $this->generatePreviewText($trend),
        ];
    }

    /**
     * Build context for AI
     */
    protected function buildContext(ViralTrend $trend): string
    {
        $keyword = $trend->keyword;
        $analytics = $trend->analytics ?? [];

        $context = "ชื่อเทรนด์: {$trend->title}\n";
        $context .= "คีย์เวิร์ด: {$keyword->keyword}\n";
        $context .= "คะแนนไวรัล: {$trend->viral_score}\n";
        $context .= "จำนวนการพูดถึง: {$trend->total_mentions} ครั้ง\n";
        $context .= "อัตราการเติบโต: {$keyword->growth_rate}%\n";
        $context .= "สถานะ: {$trend->status}\n";
        $context .= "ความเร็ว: {$trend->velocity} ครั้ง/ชั่วโมง\n\n";

        if (isset($analytics['top_posts'])) {
            $context .= "โพสต์ยอดนิยม:\n";
            foreach (array_slice($analytics['top_posts'], 0, 3) as $post) {
                $context .= "- {$post['title']} (Engagement: {$post['engagement']})\n";
            }
            $context .= "\n";
        }

        if ($keyword->related_keywords) {
            $context .= "คีย์เวิร์ดที่เกี่ยวข้อง: " . implode(', ', $keyword->related_keywords) . "\n";
        }

        return $context;
    }

    /**
     * Build prompt
     */
    protected function buildPrompt(array $config): string
    {
        $prompt = "สร้างคอนเทนต์ประเภท: {$config['type']}\n\n";

        if (isset($config['platform'])) {
            $prompt .= "แพลตฟอร์ม: {$config['platform']}\n\n";
        }

        $prompt .= "ข้อมูลเทรนด์:\n{$config['context']}\n\n";

        $prompt .= "คำแนะนำ:\n";
        foreach ($config['instructions'] as $instruction) {
            $prompt .= "- {$instruction}\n";
        }

        return $prompt;
    }

    /**
     * Get social platform specific instructions
     */
    protected function getSocialPlatformInstructions(string $platform): array
    {
        return match($platform) {
            'facebook' => [
                'ความยาว 40-80 คำ',
                'เน้นการสร้าง engagement',
                'ถามคำถามเพื่อกระตุ้นให้แสดงความคิดเห็น',
            ],
            'twitter' => [
                'ความยาวไม่เกิน 280 ตัวอักษร',
                'กระชับ เข้าใจง่าย',
                'ใส่ hashtag 2-3 ตัว',
            ],
            'instagram' => [
                'เน้น visual storytelling',
                'ใช้ emoji ให้เหมาะสม',
                'hashtag 10-15 ตัว',
            ],
            'tiktok' => [
                'เน้นความสนุก trendy',
                'ใช้ภาษาวัยรุ่น',
                'กระตุ้นให้ดู video',
            ],
            default => ['เขียนให้เหมาะกับแพลตฟอร์ม'],
        };
    }

    /**
     * Get default AI provider
     */
    protected function getDefaultProvider(): array
    {
        $provider = AiProvider::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->first();

        if (!$provider) {
            throw new \Exception('No active AI provider found');
        }

        $model = AiModel::where('ai_provider_id', $provider->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->first();

        if (!$model) {
            throw new \Exception('No active AI model found');
        }

        return ['provider' => $provider, 'model' => $model];
    }

    /**
     * Extract title from generated text
     */
    protected function extractTitle(string $text): ?string
    {
        if (preg_match('/^#\s*(.+)$/m', $text, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Generate excerpt
     */
    protected function generateExcerpt(string $text, int $length = 160): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/^#.+$/m', '', $text); // Remove headers
        $text = trim($text);

        return mb_substr($text, 0, $length) . '...';
    }

    /**
     * Extract tags
     */
    protected function extractTags(ViralTrend $trend): array
    {
        $tags = [$trend->keyword->keyword];

        if ($trend->keyword->related_keywords) {
            $tags = array_merge($tags, array_slice($trend->keyword->related_keywords, 0, 5));
        }

        return $tags;
    }

    /**
     * Generate SEO title
     */
    protected function generateSeoTitle(ViralTrend $trend): string
    {
        return mb_substr($trend->title . " - เทรนด์ไวรัล " . date('Y'), 0, 60);
    }

    /**
     * Generate meta description
     */
    protected function generateMetaDescription(ViralTrend $trend): string
    {
        return mb_substr($trend->description, 0, 160);
    }

    /**
     * Generate hashtags
     */
    protected function generateHashtags(ViralTrend $trend, string $platform): array
    {
        $hashtags = ['#' . str_replace(' ', '', $trend->keyword->keyword)];

        if ($trend->keyword->related_keywords) {
            foreach (array_slice($trend->keyword->related_keywords, 0, 4) as $keyword) {
                $hashtags[] = '#' . str_replace(' ', '', $keyword);
            }
        }

        $hashtags[] = '#Trending';
        $hashtags[] = '#Viral';

        return array_slice($hashtags, 0, $platform === 'instagram' ? 15 : 5);
    }

    /**
     * Extract key points
     */
    protected function extractKeyPoints(ViralTrend $trend): array
    {
        $analytics = $trend->analytics ?? [];

        return [
            "พูดถึง {$trend->total_mentions} ครั้ง",
            "เติบโต {$trend->keyword->growth_rate}%",
            "สถานะ: {$trend->status}",
            "ความเร็ว: {$trend->velocity} ครั้ง/ชั่วโมง",
        ];
    }

    /**
     * Extract subject line from newsletter content
     */
    protected function extractSubjectLine(string $content): string
    {
        if (preg_match('/Subject:\s*(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return 'Trending Now: ' . date('F Y');
    }

    /**
     * Generate preview text
     */
    protected function generatePreviewText(ViralTrend $trend): string
    {
        return mb_substr("เช็คเทรนด์ไวรัลล่าสุด: {$trend->title}", 0, 100);
    }
}
