<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotAutoContentPost;
use App\Models\ChatbotPlatformIntegration;
use App\Services\AI\AiServiceFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Auto Content Posting Service
 *
 * บริการสำหรับสร้างและโพสต์คอนเทนต์อัตโนมัติ
 *
 * ⚠️ (2026-09-14) เดิม postToLine/Facebook/Instagram/Telegram/Discord เป็นตัวหลอก — เขียน log แล้วตอบ "สำเร็จ"
 *    ทั้งที่ไม่ได้ส่งอะไรออกไป → โพสต์ถูกมาร์ก posted และตั้งรอบถัดไปต่อเรื่อย ๆ
 *    ตอนนี้ทุกแพลตฟอร์มต้อง "ส่งจริง" หรือคืน success=false พร้อมเหตุผลภาษาไทย — ห้ามรายงานสำเร็จลอย ๆ อีก
 */
class AutoContentService
{
    /** ชื่อแพลตฟอร์มที่แสดงให้ผู้ใช้เห็น — และเป็นรายการแพลตฟอร์มที่รู้จัก */
    public const PLATFORM_LABELS = [
        'line' => 'LINE',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'telegram' => 'Telegram',
        'discord' => 'Discord',
    ];

    /** Telegram chat_id: ตัวเลข (กลุ่ม/แชนแนลขึ้นต้น -100) หรือ @ชื่อแชนแนลสาธารณะ */
    public const TELEGRAM_CHAT_PATTERN = '/^(-?\d{1,20}|@[A-Za-z][A-Za-z0-9_]{4,31})$/';

    /** Discord channel id = snowflake ตัวเลข 17-20 หลัก — กันค่าแปลก ๆ ไปต่อท้าย path ของ API */
    public const DISCORD_CHANNEL_PATTERN = '/^\d{17,20}$/';

    /** รูปแบบเดียวกับหน้าตั้งค่า Telegram ของบอทแม่หมอ (FortuneChannelController) — token อยู่ใน URL */
    protected const TELEGRAM_TOKEN_PATTERN = '/^\d{5,20}:[A-Za-z0-9_-]{20,100}$/';

    /** token ของ Discord อยู่ใน header — ห้ามมีช่องว่าง/ขึ้นบรรทัด */
    protected const DISCORD_TOKEN_PATTERN = '/^[A-Za-z0-9._-]{30,200}$/';

    protected const HTTP_TIMEOUT = 15;

    /** เพดานความยาวต่อข้อความ นับแบบ UTF-16 ตามที่แพลตฟอร์มนับ (อีโมจิ = 2) */
    protected const TELEGRAM_MAX_TEXT = 4000; // เพดานจริง 4096

    protected const TELEGRAM_MAX_CAPTION = 1024;

    protected const DISCORD_MAX_TEXT = 2000;

    protected const LINE_MAX_TEXT = 5000;

    /** broadcast ของ LINE ส่งได้ครั้งละไม่เกิน 5 ข้อความ */
    protected const LINE_MAX_MESSAGES = 5;

    protected const FACEBOOK_GRAPH = 'https://graph.facebook.com/v21.0';

    /**
     * Generate content using AI
     *
     * ⚠️ เดิมเรียก $factory->make() ซึ่งไม่มีอยู่จริงใน AiServiceFactory + ส่ง chat() ผิดรูป
     *    → Error (ไม่ใช่ Exception) หลุด catch กลายเป็น 500 — สร้างคอนเทนต์ไม่เคยทำงานเลย
     *    ตอนนี้ใช้ createFromBot() + chat($messages, $options) แบบเดียวกับ AiService
     */
    public function generateContent(ChatbotAutoContentPost $post): array
    {
        $botProfile = $post->botProfile;

        if (! $botProfile->provider || ! $botProfile->model) {
            return [
                'success' => false,
                'error' => 'บอทยังไม่ได้เลือกผู้ให้บริการ AI และโมเดล — ตั้งค่าที่หน้าจัดการบอทก่อน',
            ];
        }

        try {
            $aiService = AiServiceFactory::createFromBot($botProfile);

            $response = $aiService->chat([
                ['role' => 'system', 'content' => $this->buildContentGenerationPrompt($post)],
                ['role' => 'user', 'content' => $post->content_prompt],
            ], [
                'temperature' => $post->content_guidelines['creativity'] ?? 0.8,
                'max_tokens' => $post->content_guidelines['max_length'] ?? 1000,
            ]);

            // chat() ไม่ throw — ล้มแล้วคืน success=false พร้อมข้อความ error ของผู้ให้บริการ
            $content = trim((string) ($response['content'] ?? ''));
            if (($response['success'] ?? true) === false || $content === '') {
                throw new \RuntimeException((string) ($response['error'] ?? 'AI returned empty content'));
            }

            return [
                'success' => true,
                'content' => $content,
                'hashtags' => $this->extractHashtags($content),
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            ];

        } catch (\Throwable $e) {
            Log::error('AutoContent: Failed to generate content', [
                'post_id' => $post->id,
                'exception' => $e::class,
                'error' => $this->redact($e->getMessage()),
            ]);

            // ข้อความ error ของผู้ให้บริการ AI อาจมีคีย์ API (บางส่วน) หรือรายละเอียดภายใน — ห้ามส่งถึงผู้ใช้
            return [
                'success' => false,
                'error' => 'สร้างคอนเทนต์ด้วย AI ไม่สำเร็จ กรุณาตรวจการตั้งค่าผู้ให้บริการและโมเดลของบอทแล้วลองใหม่',
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
        // \p{M} = สระบน/ล่าง/วรรณยุกต์ของไทย — ใช้ \w อย่างเดียว "#โปรโมชัน" จะถูกตัดเหลือ "#โปรโมช"
        preg_match_all('/#([\p{L}\p{M}\p{N}_]+)/u', $content, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * โพสต์ไปทุกแพลตฟอร์มเป้าหมาย แล้วบันทึกผลลงโพสต์ — ใช้ทั้งปุ่ม "โพสต์เลย" และรอบตั้งเวลา
     *
     * มาร์ก posted เฉพาะเมื่อ "ทุก" แพลตฟอร์มส่งสำเร็จจริง ไม่งั้น failed พร้อมสรุปภาษาไทยว่าอันไหนพังเพราะอะไร
     *
     * @return array{success: bool, message: string, results: array<string, array<string, mixed>>}
     */
    public function publish(ChatbotAutoContentPost $post): array
    {
        $results = $this->postToPlatforms($post);

        if ($results === []) {
            // collect([])->every() = true — เคยทำให้โพสต์ที่ไม่มีปลายทางถูกมาร์กว่าโพสต์แล้ว
            $message = 'ยังไม่ได้เลือกแพลตฟอร์มที่จะโพสต์';
            $post->markAsFailed($message);

            return ['success' => false, 'message' => $message, 'results' => []];
        }

        if (collect($results)->every(fn ($result) => $result['success'] ?? false)) {
            $post->markAsPosted($results);

            return ['success' => true, 'message' => 'โพสต์คอนเทนต์สำเร็จ', 'results' => $results];
        }

        $message = $this->describeFailures($results);
        $post->markAsFailed($message, $results);

        return ['success' => false, 'message' => $message, 'results' => $results];
    }

    /**
     * Post content to platforms
     *
     * @return array<string, array<string, mixed>> ผลรายแพลตฟอร์ม (key = platform)
     */
    public function postToPlatforms(ChatbotAutoContentPost $post): array
    {
        $results = [];

        // ['line', 'line'] = broadcast ซ้ำสองรอบ — ส่งแพลตฟอร์มละครั้งเดียว
        $platforms = array_unique(array_map('strval', (array) $post->target_platforms));

        foreach ($platforms as $platform) {
            try {
                $results[$platform] = $this->postToPlatform($post, $platform);
            } catch (\Throwable $e) {
                // ห้ามส่งข้อความ exception ดิบกลับไป — อาจมี URL ที่ฝัง token หรือรายละเอียดภายใน
                Log::error("AutoContent: Failed to post to {$platform}", [
                    'post_id' => $post->id,
                    'exception' => $e::class,
                    'error' => $this->redact($e->getMessage()),
                ]);

                $results[$platform] = $this->failure(
                    $platform,
                    'exception',
                    'เกิดข้อผิดพลาดระหว่างโพสต์ไป '.$this->label($platform).' กรุณาลองใหม่อีกครั้ง',
                );
            }
        }

        return $results;
    }

    /**
     * Post to specific platform
     */
    protected function postToPlatform(ChatbotAutoContentPost $post, string $platform): array
    {
        if (! array_key_exists($platform, self::PLATFORM_LABELS)) {
            return $this->failure($platform, 'unsupported_platform', 'ยังไม่รองรับการโพสต์อัตโนมัติไปแพลตฟอร์มนี้');
        }

        $label = $this->label($platform);
        $content = trim((string) $post->generated_content);

        if ($content === '') {
            return $this->failure($platform, 'empty_content', 'ยังไม่มีเนื้อหาให้โพสต์ กรุณาสร้างคอนเทนต์ก่อน');
        }

        // Get platform integration
        $integration = $post->botProfile->platformIntegrations()
            ->where('platform_type', $platform)
            ->active()
            ->verified()
            ->first();

        if (! $integration) {
            return $this->failure($platform, 'integration_missing', "ยังไม่ได้เชื่อมต่อ {$label} หรือการเชื่อมต่อยังไม่ผ่านการยืนยัน");
        }

        if (! $integration->can_post_content) {
            return $this->failure($platform, 'posting_disabled', "การเชื่อมต่อ {$label} ยังไม่ได้เปิดสิทธิ์ \"โพสต์คอนเทนต์\"");
        }

        // Platform-specific posting logic
        return match ($platform) {
            'line' => $this->postToLine($post, $integration, $content),
            'facebook' => $this->postToFacebook($post, $integration, $content),
            'instagram' => $this->postToInstagram($post, $integration, $content),
            'telegram' => $this->postToTelegram($post, $integration, $content),
            'discord' => $this->postToDiscord($post, $integration, $content),
        };
    }

    /**
     * Post to LINE — broadcast ถึงเพื่อนทุกคนของ LINE OA (กินโควตาข้อความรายเดือน)
     */
    protected function postToLine(ChatbotAutoContentPost $post, ChatbotPlatformIntegration $integration, string $content): array
    {
        $token = (string) $integration->line_channel_access_token;

        if ($token === '') {
            return $this->failure('line', 'missing_token', 'ยังไม่ได้ใส่ Channel Access Token ของ LINE');
        }

        $chunks = $this->splitText($content, self::LINE_MAX_TEXT);

        if (count($chunks) > self::LINE_MAX_MESSAGES) {
            return $this->failure('line', 'content_too_long', 'เนื้อหายาวเกินกว่าที่ LINE ส่งได้ในครั้งเดียว (ไม่เกิน 25,000 ตัวอักษร)');
        }

        try {
            $response = Http::withToken($token)
                ->timeout(self::HTTP_TIMEOUT)
                ->asJson()
                ->post('https://api.line.me/v2/bot/message/broadcast', [
                    'messages' => array_map(fn (string $text) => ['type' => 'text', 'text' => $text], $chunks),
                ]);
        } catch (\Throwable $e) {
            return $this->unreachable($post, 'line', $e, [$token]);
        }

        if ($response->successful()) {
            return $this->success('line', 'โพสต์ไป LINE สำเร็จ (broadcast ถึงเพื่อนทุกคน)', [
                'request_id' => $response->header('X-Line-Request-Id') ?: null,
            ]);
        }

        $error = match ($response->status()) {
            401 => 'Channel Access Token ของ LINE ไม่ถูกต้องหรือหมดอายุ',
            403 => 'บัญชี LINE OA นี้ไม่มีสิทธิ์ส่ง broadcast',
            429 => 'โควตาข้อความ LINE ของเดือนนี้หมด หรือส่งถี่เกินไป',
            default => 'LINE ปฏิเสธการโพสต์ (รหัส '.$response->status().')',
        };

        return $this->rejected($post, 'line', $response, $error, [$token]);
    }

    /**
     * Post to Facebook — โพสต์ลงฟีดเพจ (โทเค็นเพจต้องมีสิทธิ์ pages_manage_posts)
     */
    protected function postToFacebook(ChatbotAutoContentPost $post, ChatbotPlatformIntegration $integration, string $content): array
    {
        $pageId = (string) $integration->fb_page_id;
        $token = (string) $integration->access_token;

        if ($pageId === '' || $token === '') {
            return $this->failure('facebook', 'missing_token', 'ยังไม่ได้ใส่ Page ID หรือ Page Access Token ของ Facebook');
        }

        if (! preg_match('/^\d{5,25}$/', $pageId)) {
            return $this->failure('facebook', 'target_invalid', 'Page ID ของ Facebook ต้องเป็นตัวเลข');
        }

        $image = $this->firstImageUrl($post);

        // token ส่งใน body (form) ไม่ใช่ query string — จะได้ไม่ติดไปกับ URL ในข้อความ error
        $request = Http::asForm()->timeout(self::HTTP_TIMEOUT);

        try {
            $response = $image !== null
                ? $request->post(self::FACEBOOK_GRAPH."/{$pageId}/photos", ['url' => $image, 'caption' => $content, 'access_token' => $token])
                : $request->post(self::FACEBOOK_GRAPH."/{$pageId}/feed", ['message' => $content, 'access_token' => $token]);
        } catch (\Throwable $e) {
            return $this->unreachable($post, 'facebook', $e, [$token]);
        }

        if ($response->successful() && $response->json('id')) {
            return $this->success('facebook', 'โพสต์ลงเพจ Facebook สำเร็จ', [
                'platform_post_id' => (string) ($response->json('post_id') ?? $response->json('id')),
            ]);
        }

        $error = match ((int) $response->json('error.code')) {
            190 => 'Page Access Token ของ Facebook หมดอายุหรือไม่ถูกต้อง',
            10, 200 => 'Facebook ไม่อนุญาตให้โพสต์ — โทเค็นเพจต้องมีสิทธิ์ pages_manage_posts',
            368 => 'Facebook ระงับการโพสต์ของเพจนี้ชั่วคราว',
            4, 17, 32, 613 => 'Facebook จำกัดความถี่ชั่วคราว กรุณาลองใหม่ภายหลัง',
            default => 'Facebook ปฏิเสธการโพสต์ (รหัส '.($response->json('error.code') ?? $response->status()).')',
        };

        return $this->rejected($post, 'facebook', $response, $error, [$token]);
    }

    /**
     * Post to Instagram
     */
    protected function postToInstagram(ChatbotAutoContentPost $post, ChatbotPlatformIntegration $integration, string $content): array
    {
        // Instagram Graph API โพสต์ได้เฉพาะรูป/วิดีโอ และต้องใช้ IG Business Account ID
        // ซึ่งการเชื่อมต่อนี้ไม่ได้เก็บไว้ (มีแค่ fb_page_id) → บอกตรง ๆ ว่ายังไม่รองรับ
        return $this->failure('instagram', 'not_supported', 'ยังไม่รองรับการโพสต์อัตโนมัติไป Instagram');
    }

    /**
     * Post to Telegram — sendMessage (หรือ sendPhoto ถ้ามีรูป) ไปยังแชท/แชนแนลที่ตั้งไว้
     */
    protected function postToTelegram(ChatbotAutoContentPost $post, ChatbotPlatformIntegration $integration, string $content): array
    {
        $token = (string) $integration->telegram_bot_token;

        if (! preg_match(self::TELEGRAM_TOKEN_PATTERN, $token)) {
            return $this->failure('telegram', 'invalid_token', 'โทเค็นบอท Telegram ไม่ถูกต้อง — ต้องเป็นแบบ 123456789:AAxxxx… ที่ได้จาก @BotFather');
        }

        $chatId = $this->postTarget($post, $integration, 'telegram', 'chat_id');

        if ($chatId === null) {
            return $this->failure('telegram', 'target_missing', 'ยังไม่ได้ตั้งแชทหรือแชนแนลปลายทางของ Telegram (chat_id)');
        }

        if (! preg_match(self::TELEGRAM_CHAT_PATTERN, $chatId)) {
            return $this->failure('telegram', 'target_invalid', 'chat_id ของ Telegram ไม่ถูกต้อง — ใช้ตัวเลข เช่น -1001234567890 หรือ @ชื่อแชนแนล');
        }

        $endpoint = 'https://api.telegram.org/bot'.$token.'/';
        $chunks = $this->splitText($content, self::TELEGRAM_MAX_TEXT);
        $image = $this->firstImageUrl($post);
        $calls = [];

        if ($image !== null) {
            // caption ยาวได้ 1,024 — เนื้อหาสั้นพอก็ใส่เป็น caption ไม่งั้นส่งรูปก่อนแล้วตามด้วยข้อความ
            $photo = ['chat_id' => $chatId, 'photo' => $image];
            if (count($chunks) === 1 && $this->textUnits($chunks[0]) <= self::TELEGRAM_MAX_CAPTION) {
                $photo['caption'] = array_shift($chunks);
            }
            $calls[] = ['sendPhoto', $photo];
        }

        foreach ($chunks as $chunk) {
            // ไม่ใส่ parse_mode — เนื้อหาจาก AI มีอักขระที่ทำให้ Markdown/HTML ของ Telegram พังได้
            $calls[] = ['sendMessage', ['chat_id' => $chatId, 'text' => $chunk]];
        }

        $messageIds = [];

        foreach ($calls as [$method, $params]) {
            try {
                $response = Http::timeout(self::HTTP_TIMEOUT)->asJson()->post($endpoint.$method, $params);
            } catch (\Throwable $e) {
                return $this->unreachable($post, 'telegram', $e, [$token], $this->partial($messageIds, count($calls)));
            }

            if (! $response->successful() || $response->json('ok') !== true) {
                $description = (string) $response->json('description');
                $error = match (true) {
                    $response->status() === 401 => 'โทเค็นบอท Telegram ไม่ถูกต้องหรือถูกยกเลิก',
                    $response->status() === 403 => 'บอทไม่มีสิทธิ์โพสต์ในแชทนี้ — เพิ่มบอทเป็นแอดมินของแชนแนลก่อน',
                    $response->status() === 429 => 'Telegram จำกัดความถี่ชั่วคราว กรุณาลองใหม่ภายหลัง',
                    str_contains($description, 'chat not found') => 'ไม่พบแชทปลายทาง — ตรวจ chat_id และเพิ่มบอทเข้าแชทก่อน',
                    default => 'Telegram ปฏิเสธการโพสต์ (รหัส '.$response->status().')',
                };

                return $this->rejected($post, 'telegram', $response, $error, [$token], $this->partial($messageIds, count($calls)));
            }

            $messageIds[] = $response->json('result.message_id');
        }

        return $this->success('telegram', 'โพสต์ไป Telegram สำเร็จ', ['message_ids' => $messageIds]);
    }

    /**
     * Post to Discord — POST /channels/{id}/messages ด้วย bot token
     */
    protected function postToDiscord(ChatbotAutoContentPost $post, ChatbotPlatformIntegration $integration, string $content): array
    {
        $token = (string) $integration->discord_bot_token;

        if (! preg_match(self::DISCORD_TOKEN_PATTERN, $token)) {
            return $this->failure('discord', 'invalid_token', 'โทเค็นบอท Discord ไม่ถูกต้อง');
        }

        $channelId = $this->postTarget($post, $integration, 'discord', 'channel_id');

        if ($channelId === null) {
            return $this->failure('discord', 'target_missing', 'ยังไม่ได้ตั้งห้องปลายทางของ Discord (channel_id)');
        }

        if (! preg_match(self::DISCORD_CHANNEL_PATTERN, $channelId)) {
            return $this->failure('discord', 'target_invalid', 'channel_id ของ Discord ต้องเป็นตัวเลข 17-20 หลัก');
        }

        $endpoint = "https://discord.com/api/v10/channels/{$channelId}/messages";
        $chunks = $this->splitText($content, self::DISCORD_MAX_TEXT);
        $image = $this->firstImageUrl($post);
        $messageIds = [];

        foreach ($chunks as $index => $chunk) {
            // allowed_mentions ว่าง = เนื้อหาจาก AI ที่มี @everyone / @here / <@id> จะไม่ปิงใครเลย
            $payload = ['content' => $chunk, 'allowed_mentions' => ['parse' => []]];
            if ($index === 0 && $image !== null) {
                $payload['embeds'] = [['image' => ['url' => $image]]];
            }

            try {
                $response = Http::withHeaders(['Authorization' => 'Bot '.$token])
                    ->timeout(self::HTTP_TIMEOUT)
                    ->asJson()
                    ->post($endpoint, $payload);
            } catch (\Throwable $e) {
                return $this->unreachable($post, 'discord', $e, [$token], $this->partial($messageIds, count($chunks)));
            }

            if (! $response->successful()) {
                $error = match ($response->status()) {
                    401 => 'โทเค็นบอท Discord ไม่ถูกต้องหรือถูกรีเซ็ต',
                    403 => 'บอทไม่มีสิทธิ์ส่งข้อความในห้องนี้ — เชิญบอทเข้าเซิร์ฟเวอร์และเปิดสิทธิ์ Send Messages',
                    404 => 'ไม่พบห้อง Discord ปลายทาง — ตรวจ channel_id',
                    429 => 'Discord จำกัดความถี่ชั่วคราว กรุณาลองใหม่ภายหลัง',
                    default => 'Discord ปฏิเสธการโพสต์ (รหัส '.$response->status().')',
                };

                return $this->rejected($post, 'discord', $response, $error, [$token], $this->partial($messageIds, count($chunks)));
            }

            $messageIds[] = (string) $response->json('id');
        }

        return $this->success('discord', 'โพสต์ไป Discord สำเร็จ', ['message_ids' => $messageIds]);
    }

    /**
     * Process scheduled posts (to be called by cron job)
     */
    public function processScheduledPosts(): array
    {
        $posts = ChatbotAutoContentPost::readyToPost()->get();

        $processed = [];

        foreach ($posts as $post) {
            // รอบตั้งเวลาซ้อนกัน (cron ช้า / รันมือ) ห้ามส่งโพสต์เดียวกันซ้ำ — จองไม่ได้ = มีรอบอื่นทำอยู่
            if (! $post->claimForPosting()) {
                continue;
            }

            try {
                // Generate content if not already generated
                if (empty($post->generated_content)) {
                    $contentResult = $this->generateContent($post);

                    if (! $contentResult['success']) {
                        $post->markAsFailed($contentResult['error']);
                        $processed[] = [
                            'post_id' => $post->id,
                            'status' => 'failed',
                            'error' => $contentResult['error'],
                        ];

                        continue;
                    }

                    $post->update([
                        'generated_content' => $contentResult['content'],
                        'hashtags' => $contentResult['hashtags'],
                    ]);
                }

                $outcome = $this->publish($post);

                $processed[] = [
                    'post_id' => $post->id,
                    'status' => $outcome['success'] ? 'posted' : 'failed',
                    'results' => $outcome['results'],
                ];

            } catch (\Throwable $e) {
                Log::error('AutoContent: Failed to process post', [
                    'post_id' => $post->id,
                    'exception' => $e::class,
                    'error' => $this->redact($e->getMessage()),
                ]);

                $error = 'เกิดข้อผิดพลาดระหว่างประมวลผลโพสต์ กรุณาลองใหม่อีกครั้ง';
                $post->markAsFailed($error);
                $processed[] = [
                    'post_id' => $post->id,
                    'status' => 'error',
                    'error' => $error,
                ];
            }
        }

        return $processed;
    }

    /**
     * สรุปผลที่ไม่สำเร็จเป็นภาษาไทย — แสดงให้ผู้ใช้และเก็บลง error_message
     *
     * @param  array<string, array<string, mixed>>  $results
     */
    protected function describeFailures(array $results): string
    {
        $failed = [];
        $succeeded = [];

        foreach ($results as $platform => $result) {
            if ($result['success'] ?? false) {
                $succeeded[] = $this->label((string) $platform);
            } else {
                $failed[] = $this->label((string) $platform).' — '.($result['error'] ?? 'ไม่ทราบสาเหตุ');
            }
        }

        $message = 'โพสต์ไม่สำเร็จ: '.implode(' · ', $failed);

        if ($succeeded !== []) {
            $message .= ' (โพสต์สำเร็จแล้ว: '.implode(', ', $succeeded).')';
        }

        return $message;
    }

    /**
     * ปลายทางของโพสต์ — โพสต์กำหนดเองได้ (platform_specific_config.telegram.chat_id)
     * ไม่งั้นใช้ค่าตั้งของการเชื่อมต่อ (platform_settings.post_chat_id / post_channel_id)
     */
    protected function postTarget(ChatbotAutoContentPost $post, ChatbotPlatformIntegration $integration, string $platform, string $key): ?string
    {
        $value = data_get($post->platform_specific_config, "{$platform}.{$key}")
            ?? data_get($integration->platform_settings, "post_{$key}");

        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * รูปแรกของโพสต์ (generated_media) — รับเฉพาะ https เพราะแพลตฟอร์มต้องดึงรูปจาก URL นี้เอง
     */
    protected function firstImageUrl(ChatbotAutoContentPost $post): ?string
    {
        $first = collect($post->generated_media ?? [])->first();
        $url = is_array($first) ? ($first['url'] ?? null) : $first;

        if (! is_string($url) || ! str_starts_with(strtolower($url), 'https://')) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    /**
     * แบ่งข้อความให้แต่ละชิ้นไม่เกิน $limit (นับแบบ UTF-16) โดยตัดที่ย่อหน้า > บรรทัด > ช่องว่าง ก่อนตัดกลางคำ
     *
     * @return list<string>
     */
    protected function splitText(string $text, int $limit): array
    {
        $chunks = [];
        $rest = trim($text);

        while ($rest !== '') {
            if ($this->textUnits($rest) <= $limit) {
                $chunks[] = $rest;
                break;
            }

            $head = $this->takeUnits($rest, $limit);
            $cut = mb_strlen($head);

            foreach (["\n\n", "\n", ' '] as $separator) {
                $position = mb_strrpos($head, $separator);

                // ตัดที่ตัวคั่นเฉพาะเมื่อไม่ทำให้ชิ้นสั้นเกินครึ่ง — ไม่งั้นได้ข้อความสั้น ๆ หลายชิ้น
                if ($position !== false && $position >= intdiv($cut, 2)) {
                    $cut = $position;
                    break;
                }
            }

            $chunks[] = rtrim(mb_substr($rest, 0, $cut));
            $rest = ltrim(mb_substr($rest, $cut));
        }

        return $chunks;
    }

    /**
     * ความยาวแบบ UTF-16 (Telegram/Discord นับแบบนี้ — อีโมจิหนึ่งตัว = 2)
     */
    protected function textUnits(string $text): int
    {
        return intdiv(strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')), 2);
    }

    /**
     * ส่วนหัวของข้อความที่ยาวไม่เกิน $limit หน่วย UTF-16 (ไม่ตัดกลางอักขระ)
     */
    protected function takeUnits(string $text, int $limit): string
    {
        $units = 0;
        $head = '';

        foreach (mb_str_split($text) as $character) {
            $size = strlen($character) === 4 ? 2 : 1; // อักขระ 4 ไบต์ใน UTF-8 = surrogate pair ใน UTF-16

            if ($units + $size > $limit) {
                break;
            }

            $units += $size;
            $head .= $character;
        }

        return $head;
    }

    protected function label(string $platform): string
    {
        return self::PLATFORM_LABELS[$platform] ?? 'แพลตฟอร์มที่ไม่รู้จัก';
    }

    protected function success(string $platform, string $message, array $extra = []): array
    {
        Log::info('AutoContent: posted', ['platform' => $platform] + array_intersect_key($extra, ['message_ids' => true, 'platform_post_id' => true]));

        return ['success' => true, 'platform' => $platform, 'message' => $message] + $extra;
    }

    protected function failure(string $platform, string $code, string $error, array $extra = []): array
    {
        return ['success' => false, 'platform' => $platform, 'error_code' => $code, 'error' => $error] + $extra;
    }

    /**
     * ข้อความหลายชิ้นส่งไปได้บางส่วนแล้วพัง — บอกผู้ใช้ว่าไปแล้วกี่ชิ้น (ลองใหม่ = ชิ้นแรก ๆ จะซ้ำ)
     *
     * @param  list<mixed>  $sentIds
     */
    protected function partial(array $sentIds, int $total): array
    {
        return $sentIds === [] ? [] : ['partial' => true, 'sent_parts' => count($sentIds), 'total_parts' => $total, 'message_ids' => $sentIds];
    }

    /**
     * API ตอบกลับมาว่าไม่รับ — log สถานะ + body (ตัด token ออก) แล้วคืนข้อความภาษาไทย
     */
    protected function rejected(ChatbotAutoContentPost $post, string $platform, Response $response, string $error, array $secrets, array $extra = []): array
    {
        Log::warning("AutoContent: {$platform} rejected post", [
            'post_id' => $post->id,
            'status' => $response->status(),
            'body' => $this->redact(mb_substr($response->body(), 0, 500), $secrets),
        ]);

        if (($extra['partial'] ?? false) === true) {
            $error .= " (ส่งไปแล้ว {$extra['sent_parts']} จาก {$extra['total_parts']} ส่วน)";
        }

        return $this->failure($platform, 'api_error', $error, ['status' => $response->status()] + $extra);
    }

    /**
     * ต่อ API ไม่ได้ / หมดเวลา — ข้อความ exception ของ Guzzle มี URL เต็ม (Telegram ฝัง token ใน URL) ต้องตัดออกก่อน log
     */
    protected function unreachable(ChatbotAutoContentPost $post, string $platform, \Throwable $e, array $secrets, array $extra = []): array
    {
        Log::warning("AutoContent: {$platform} unreachable", [
            'post_id' => $post->id,
            'exception' => $e::class,
            'error' => $this->redact($e->getMessage(), $secrets),
        ]);

        $label = $this->label($platform);
        $error = $e instanceof ConnectionException
            ? "เชื่อมต่อ {$label} ไม่สำเร็จหรือหมดเวลา กรุณาลองใหม่อีกครั้ง"
            : "เกิดข้อผิดพลาดระหว่างโพสต์ไป {$label} กรุณาลองใหม่อีกครั้ง";

        if (($extra['partial'] ?? false) === true) {
            $error .= " (ส่งไปแล้ว {$extra['sent_parts']} จาก {$extra['total_parts']} ส่วน)";
        }

        return $this->failure($platform, $e instanceof ConnectionException ? 'timeout' : 'exception', $error, $extra);
    }

    /**
     * ตัดค่าลับออกจากข้อความก่อน log — ค่าที่รู้ + รูปแบบ token ที่พบบ่อย เผื่อหลุดมาจากที่อื่น
     *
     * @param  list<string>  $secrets
     */
    protected function redact(string $text, array $secrets = []): string
    {
        foreach ($secrets as $secret) {
            if ($secret !== '') {
                $text = str_replace($secret, '***', $text);
            }
        }

        return preg_replace([
            '/\d{5,20}:[A-Za-z0-9_-]{20,}/',   // Telegram bot token
            '/\bsk-[A-Za-z0-9_-]{8,}/',       // คีย์ OpenAI และที่คล้ายกัน
            '/(Bearer|Bot)\s+[A-Za-z0-9._-]{16,}/i',
        ], ['***', 'sk-***', '$1 ***'], $text) ?? $text;
    }
}
