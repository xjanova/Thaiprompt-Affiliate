<?php

namespace App\Services;

use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortuneHoroscopeContent;
use App\Models\FortuneHoroscopePost;
use App\Services\Fortune\FacebookContentPolicy;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FortuneHoroscopePublishService
 *
 * จัดการโพสดวงรายวันลง Facebook Page / LINE OA
 * ใช้ pattern เดียวกับ BotPlatformService
 */
class FortuneHoroscopePublishService
{
    /**
     * สร้างและโพสเนื้อหาสำหรับแคมเปญ
     *
     * @return array สรุปผล ['posts_created' => int, 'posts_published' => int, 'errors' => array]
     */
    public function createAndPublishPosts(FortuneHoroscopeCampaign $campaign, Carbon $targetDate): array
    {
        // ใช้ getActivePlatforms() ที่เช็ค LINE quota ด้วย
        $platforms = $campaign->getActivePlatforms();
        $errors = [];
        $postsCreated = 0;
        $postsPublished = 0;

        if (empty($platforms)) {
            return ['posts_created' => 0, 'posts_published' => 0, 'errors' => ['ไม่มี platform ที่เปิดใช้']];
        }

        // ดึงเนื้อหาที่สร้างแล้ว
        $contents = FortuneHoroscopeContent::where('campaign_id', $campaign->id)
            ->whereDate('target_date', $targetDate)
            ->where('status', FortuneHoroscopeContent::STATUS_GENERATED)
            ->orderBy('birth_day')
            ->get();

        if ($contents->isEmpty()) {
            return ['posts_created' => 0, 'posts_published' => 0, 'errors' => ['ไม่มีเนื้อหาที่พร้อมโพส']];
        }

        foreach ($platforms as $platform) {
            try {
                // ตรวจสอบว่ามีโพสแล้วหรือยัง
                $existingPost = FortuneHoroscopePost::where('campaign_id', $campaign->id)
                    ->whereDate('target_date', $targetDate)
                    ->where('platform', $platform)
                    ->whereIn('status', [FortuneHoroscopePost::STATUS_POSTED, FortuneHoroscopePost::STATUS_POSTING])
                    ->first();

                if ($existingPost) {
                    Log::info("FortuneHoroscope: มีโพสอยู่แล้วสำหรับ {$platform}", [
                        'post_id' => $existingPost->id,
                    ]);

                    continue;
                }

                // รวมเนื้อหาทั้ง 7 วัน — กฎห้ามอีโมจิ/แฮชแท็ก ≤3 ใช้กับ "โพส FB" เท่านั้น
                // (เจ้าของสั่งเจาะจงช่องทาง FB — LINE ยังคงรูปแบบเดิมทุกอย่าง)
                $postContent = $this->composePostContent(
                    $campaign,
                    $contents,
                    $targetDate,
                    $platform === FortuneHoroscopePost::PLATFORM_FACEBOOK
                );
                $imageUrls = $contents->pluck('image_url')->filter()->values()->toArray();

                // สร้าง post record
                $post = FortuneHoroscopePost::updateOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'target_date' => $targetDate->toDateString(),
                        'platform' => $platform,
                    ],
                    [
                        'post_content' => $postContent,
                        'image_urls' => $imageUrls,
                        'status' => FortuneHoroscopePost::STATUS_PENDING,
                        'error_message' => null,
                    ]
                );
                $postsCreated++;

                // โพสจริง
                $this->publish($post);
                $postsPublished++;

            } catch (Exception $e) {
                $errors[] = "{$platform}: ".$e->getMessage();
                Log::error("FortuneHoroscope: โพสล้มเหลว {$platform}", [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // อัพเดทสถานะแคมเปญ
        if ($postsPublished > 0) {
            $campaign->update(['last_posted_at' => now(), 'last_error' => null]);
        }

        return [
            'posts_created' => $postsCreated,
            'posts_published' => $postsPublished,
            'errors' => $errors,
        ];
    }

    /**
     * โพส 1 post ลง platform
     */
    public function publish(FortuneHoroscopePost $post): void
    {
        $campaign = $post->campaign;
        $post->markPosting();

        try {
            $result = match ($post->platform) {
                FortuneHoroscopePost::PLATFORM_FACEBOOK => $this->publishToFacebook($post, $campaign),
                FortuneHoroscopePost::PLATFORM_LINE => $this->publishToLine($post, $campaign),
                default => throw new Exception("Platform ไม่รองรับ: {$post->platform}"),
            };

            $post->markPosted(
                $result['post_id'] ?? null,
                $result['post_url'] ?? null,
                $result['response'] ?? null
            );

            Log::info("FortuneHoroscope: โพสสำเร็จ {$post->platform}", [
                'post_id' => $post->id,
                'platform_post_id' => $result['post_id'] ?? null,
            ]);

        } catch (Exception $e) {
            $post->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * โพสลง Facebook Page (text + image)
     *
     * ใช้ Graph API v18.0 ตาม pattern จาก BotPlatformService
     */
    protected function publishToFacebook(FortuneHoroscopePost $post, FortuneHoroscopeCampaign $campaign): array
    {
        $pageId = $campaign->getFacebookPageId();
        $pageToken = $campaign->getFacebookPageToken();

        if (empty($pageId) || empty($pageToken)) {
            throw new Exception('ไม่มี Facebook Page ID หรือ Page Token');
        }

        $imageUrls = $post->image_urls ?? [];

        // ถ้ามีรูป ใช้ /photos endpoint (รูปแรก + caption)
        if (! empty($imageUrls)) {
            $endpoint = "https://graph.facebook.com/v21.0/{$pageId}/photos";
            $response = Http::timeout(60)->post($endpoint, [
                'url' => $imageUrls[0],
                'caption' => $post->post_content,
                'access_token' => $pageToken,
            ]);
        } else {
            // ไม่มีรูป ใช้ /feed endpoint
            $endpoint = "https://graph.facebook.com/v21.0/{$pageId}/feed";
            $response = Http::timeout(60)->post($endpoint, [
                'message' => $post->post_content,
                'access_token' => $pageToken,
            ]);
        }

        if (! $response->successful()) {
            $error = $response->json('error.message', 'Facebook API error: HTTP '.$response->status());
            throw new Exception($error);
        }

        $data = $response->json();
        $postId = $data['id'] ?? $data['post_id'] ?? null;

        return [
            'post_id' => $postId,
            'post_url' => $postId ? "https://www.facebook.com/{$postId}" : null,
            'response' => $data,
        ];
    }

    /**
     * โพสลง LINE OA (broadcast)
     *
     * ใช้ Messaging API broadcast ตาม pattern จาก BotPlatformService
     */
    protected function publishToLine(FortuneHoroscopePost $post, FortuneHoroscopeCampaign $campaign): array
    {
        $channelAccessToken = $campaign->getLineChannelAccessToken();

        if (empty($channelAccessToken)) {
            throw new Exception('ไม่มี LINE Channel Access Token');
        }

        $messages = [];
        $imageUrls = $post->image_urls ?? [];

        // ส่งรูปก่อน (ถ้ามี)
        if (! empty($imageUrls)) {
            $messages[] = [
                'type' => 'image',
                'originalContentUrl' => $imageUrls[0],
                'previewImageUrl' => $imageUrls[0],
            ];
        }

        // ส่งข้อความ (LINE จำกัด 5000 ตัวอักษร ตัดถ้ายาวเกิน)
        $text = mb_substr($post->post_content, 0, 5000);
        $messages[] = [
            'type' => 'text',
            'text' => $text,
        ];

        $endpoint = 'https://api.line.me/v2/bot/message/broadcast';
        $response = Http::timeout(60)
            ->withHeaders([
                'Authorization' => "Bearer {$channelAccessToken}",
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, ['messages' => $messages]);

        if (! $response->successful()) {
            $error = $response->json('message', 'LINE API error: HTTP '.$response->status());
            throw new Exception($error);
        }

        // อัพเดทโควต้า LINE ที่ใช้ไป
        $campaign->incrementLineUsage();

        // เตือนถ้าโควต้าใกล้หมด
        if ($campaign->isLineQuotaLow()) {
            Log::warning('FortuneHoroscope: LINE โควต้าใกล้หมด', [
                'campaign_id' => $campaign->id,
                'remaining' => $campaign->line_quota_remaining,
                'threshold' => $campaign->line_quota_warning_threshold,
            ]);
        }

        return [
            'post_id' => null,
            'post_url' => null,
            'response' => $response->json() ?: ['status' => 'sent'],
        ];
    }

    /**
     * รวมเนื้อหาทุกวันเกิดเป็น 1 โพส (พร้อม Smart Marketing)
     *
     * โครงสร้างโพส:
     * 1. Header (กำหนดเอง / default)
     * 2. เนื้อหาดวง 7 วันเกิด + สีมงคล/เลขมงคล
     * 3. Engagement Hook (กระตุ้นคอมเมนต์/แชร์)
     * 4. CTA (Call-to-Action ชวนทักดูดวง)
     * 5. Footer (กำหนดเอง)
     * 6. Smart Hashtags (auto + custom)
     *
     * @param  bool  $applyFacebookPolicy  true = ใช้กฎโพส FB (ห้ามอีโมจิ + แฮชแท็ก ≤3)
     *                                     เจ้าของสั่งกฎนี้เจาะจงช่องทาง FB — LINE ใช้ของเดิม
     *                                     default = true เพราะ FB คือช่องทางหลักและเป็นสิ่งที่
     *                                     หน้าพรีวิวของแอดมินควรเห็น
     */
    public function composePostContent(
        FortuneHoroscopeCampaign $campaign,
        Collection $contents,
        Carbon $targetDate,
        bool $applyFacebookPolicy = true
    ): string {
        $thaiDate = $targetDate->format('d/m/').($targetDate->year + 543);
        $parts = [];

        // === 1. Header ===
        $header = $campaign->post_header_template ?? '🔮✨ ดวงรายวัน {target_date} ✨🔮';
        $parts[] = str_replace('{target_date}', $thaiDate, $header);
        $parts[] = '';

        // === 2. เนื้อหาแต่ละวันเกิด ===
        foreach ($contents as $content) {
            $dayEmojis = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣'];
            $emoji = $dayEmojis[$content->birth_day] ?? '⭐';

            $parts[] = "{$emoji} 【คนเกิดวัน{$content->birth_day_name}】";

            if ($content->ai_prediction) {
                $parts[] = $content->ai_prediction;
            }

            if ($campaign->include_lucky_info) {
                $luckyParts = [];
                if ($content->lucky_color) {
                    $luckyParts[] = "🎨 สี: {$content->lucky_color}";
                }
                if ($content->lucky_number) {
                    $luckyParts[] = "🔢 เลข: {$content->lucky_number}";
                }
                if ($content->lucky_direction) {
                    $luckyParts[] = "🧭 ทิศ: {$content->lucky_direction}";
                }
                if (! empty($luckyParts)) {
                    $parts[] = implode(' | ', $luckyParts);
                }
            }

            $parts[] = '';
        }

        // === 3. Engagement Hook (สุ่มข้อความกระตุ้น) ===
        if ($campaign->enable_engagement_hooks && $contents->isNotEmpty()) {
            // สุ่ม birthDay สำหรับ engagement hook
            $randomContent = $contents->random();
            $engagementHook = $campaign->generateEngagementHook($randomContent->birth_day);
            if (! empty($engagementHook)) {
                $parts[] = '━━━━━━━━━━━━━━━━━━━━━━';
                $parts[] = $engagementHook;
                $parts[] = '';
            }
        }

        // === 4. CTA (Call-to-Action) ===
        $cta = $campaign->getCta();
        if (! empty($cta)) {
            $parts[] = $cta;
            $parts[] = '';
        }

        // === 5. Footer (กำหนดเอง) ===
        $footer = $campaign->post_footer_template ?? '';
        if (! empty($footer)) {
            $parts[] = str_replace('{target_date}', $thaiDate, $footer);
            $parts[] = '';
        }

        // === 6. Smart Hashtags ===
        // 📘 generateSmartHashtags ยิงได้ 8-12 อัน (core 4 + custom + วันเกิด + เดือน)
        //    ตัดที่นี่ ไม่ไปแก้ในโมเดล เพราะ "ไม่เกิน 3" เป็นกฎของ **โพส FB**
        //    ไม่ใช่กฎของตัวสร้างแฮชแท็ก — ปล่อยโมเดลเป็นตัวสร้างล้วน ๆ ไว้
        $hashtags = $campaign->generateSmartHashtags($targetDate);

        if (! $applyFacebookPolicy) {
            // LINE — พฤติกรรมเดิม 100% (อีโมจิครบ แฮชแท็กเต็ม)
            if (! empty($hashtags)) {
                $parts[] = $hashtags;
            }

            return implode("\n", $parts);
        }

        // 📘 ลบอีโมจิทั้งโพส — ครอบทั้งอีโมจิประจำวันเกิดที่ฝังในโค้ด (☀️🌙🔴…)
        //    เทมเพลต header/footer ที่แอดมินพิมพ์เอง และป้ายของมงคล (🎨🔢🧭)
        //    ⚠️ เส้นคั่น ━━━ กับวงเล็บ 【】 อยู่นอกช่วงที่ลบ จึงยังอยู่ครบ
        $body = FacebookContentPolicy::clean(implode("\n", $parts));
        $hashtags = FacebookContentPolicy::capHashtagLine($hashtags);

        return $hashtags !== '' ? $body."\n\n".$hashtags : $body;
    }
}
