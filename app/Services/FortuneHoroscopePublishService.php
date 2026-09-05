<?php

namespace App\Services;

use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortuneHoroscopeContent;
use App\Models\FortuneHoroscopePost;
use App\Services\Fortune\DailyArticleMirror;
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

        // 🐛 (2026-08-19) ลิงก์โพสที่แนบท้ายกล่องคำทำนายกดไม่ได้ — สร้าง URL ผิดรูป
        //
        //   เดิม: $data['id'] ?? $data['post_id'] แล้วต่อเป็น facebook.com/{id} ตรงๆ
        //   แต่ /{page}/photos คืน `id` = **เลขรูป** ส่วน `post_id` = `{page}_{post}` ตัวจริง
        //   หยิบ `id` ก่อนจึงได้ facebook.com/<เลขรูป> ซึ่งไม่ใช่ URL โพส = ลิงก์ตาย
        //   (ค่าจริงบน prod 19 ส.ค. `1648113817326010` ไม่มี `_` = เลขรูปล้วน)
        //
        //   /feed ไม่มี `post_id` แต่ `id` เป็น `{page}_{post}` อยู่แล้ว → เรียง post_id ก่อน
        //   ครอบทั้ง 2 endpoint ได้ด้วยบรรทัดเดียว
        $compositeId = $data['post_id'] ?? $data['id'] ?? null;

        return [
            'post_id' => $compositeId,
            'post_url' => $this->buildFacebookPostUrl($compositeId, $pageId),
            'response' => $data,
        ];
    }

    /**
     * ประกอบ URL โพส Facebook ที่กดได้จริง
     *
     * Graph คืนไอดีมา 2 แบบ:
     *   - `{page_id}_{post_id}`  (มาจาก /feed หรือฟิลด์ post_id ของ /photos) → รูปแบบมาตรฐาน
     *   - เลขล้วน                 (เผื่อ API เปลี่ยน/ตอบไม่ครบ) → ประกอบกับ page id ที่ถืออยู่
     *
     * ทั้งสองทางออกมาเป็น `facebook.com/{page}/posts/{post}` ซึ่งเปิดได้ทั้งคนล็อกอิน
     * และไม่ล็อกอิน — ต่างจาก `facebook.com/{id}` เปล่าๆ ที่ FB ตีความเป็นโปรไฟล์
     *
     * @return string|null null = ไม่มีไอดี (ผู้เรียกจะไม่แนบลิงก์ ดีกว่าแนบลิงก์เสีย)
     */
    protected function buildFacebookPostUrl(?string $compositeId, string $pageId): ?string
    {
        if (empty($compositeId)) {
            return null;
        }

        $parts = explode('_', $compositeId);

        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return "https://www.facebook.com/{$parts[0]}/posts/{$parts[1]}";
        }

        return "https://www.facebook.com/{$pageId}/posts/{$compositeId}";
    }

    /**
     * 💣 (2026-08-26) กันไม่ให้ broadcast กลืนโควต้า push ทั้งเดือน
     *
     * broadcast คิดเงินแบบ "จำนวนผู้ติดตาม × จำนวน message object" ไม่ใช่ 1 ครั้ง
     * (เพจ 500 คน + รูป 1 + ข้อความ 1 = 1,000 ข้อความ = เกินโควต้า 300 ทันที)
     *
     * นโยบาย: ต้องมีโควต้าเหลือ "มากกว่า" ที่ broadcast จะกิน + กันชนให้ลูกค้าที่จ่ายเงิน
     * ดึงโควต้าไม่ได้ → ปล่อยผ่าน (fail-open — ห้ามให้คอนเทนต์ตายเพราะเช็คไม่ได้)
     *
     * @param  string  $token  channel access token ของแคมเปญ (อาจคนละ OA กับบอทดูดวง)
     * @param  int  $objectCount  จำนวน message object ที่จะยิง
     *
     * @throws Exception เมื่อโควต้าไม่พอ — publish จะถูกบันทึกเป็น failed พร้อมเหตุผล
     */
    protected function assertLineBroadcastAffordable(string $token, int $objectCount): void
    {
        try {
            $quotaRes = Http::withToken($token)->timeout(10)
                ->get('https://api.line.me/v2/bot/message/quota');
            $usedRes = Http::withToken($token)->timeout(10)
                ->get('https://api.line.me/v2/bot/message/quota/consumption');
            $followersRes = Http::withToken($token)->timeout(10)
                ->get('https://api.line.me/v2/bot/insight/followers', ['date' => now()->subDay()->format('Ymd')]);

            if (! $quotaRes->successful() || ! $usedRes->successful()) {
                return; // fail-open
            }

            $quotaData = $quotaRes->json();

            // type=none = ไม่จำกัด → ยิงได้เลย
            if (($quotaData['type'] ?? null) === 'none') {
                return;
            }

            $limit = (int) ($quotaData['value'] ?? 0);
            $used = (int) ($usedRes->json('totalUsage') ?? 0);
            $remaining = max(0, $limit - $used);

            // ประมาณผู้ติดตาม — ดึงไม่ได้ให้ถือว่า 1 (fail-open) แต่ยังกันเคสโควต้าหมดสนิท
            $followers = (int) ($followersRes->successful()
                ? ($followersRes->json('followers') ?? 0)
                : 0);
            $estimatedCost = max(1, $followers) * max(1, $objectCount);

            // ⚠️ ห้ามใช้ env() — prod รัน config:cache แล้ว env() คืน null
            $reserve = \App\Services\LineFortuneService::PUSH_RESERVE_FOR_PAID;

            if ($remaining < ($estimatedCost + $reserve)) {
                $msg = sprintf(
                    'โควต้า LINE ไม่พอสำหรับ broadcast — เหลือ %d, ต้องใช้ประมาณ %d (ผู้ติดตาม %d × %d กล่อง) + กันไว้ให้ลูกค้าที่จ่ายเงิน %d',
                    $remaining, $estimatedCost, $followers, $objectCount, $reserve
                );

                Log::warning('FortuneHoroscope: ยกเลิก LINE broadcast — โควต้าไม่พอ', [
                    'remaining' => $remaining,
                    'estimated_cost' => $estimatedCost,
                    'followers' => $followers,
                    'objects' => $objectCount,
                    'reserve' => $reserve,
                ]);

                throw new Exception($msg);
            }
        } catch (Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
            // เช็คโควต้าพังเอง → ปล่อยผ่าน (fail-open)
            Log::debug('FortuneHoroscope: เช็คโควต้า LINE ไม่สำเร็จ ปล่อยผ่าน', ['error' => $e->getMessage()]);
        }
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

        // 💣 (2026-08-26) broadcast คิดโควต้า "ต่อผู้ติดตาม × จำนวน message object"
        //   ไม่ใช่ 1 ครั้งอย่างที่ incrementLineUsage() นับ — เปิดสวิตช์นี้ทั้งที่ผู้ติดตามเยอะ
        //   = โควต้าทั้งเดือนหายในโพสเดียว แล้วลูกค้าที่จ่ายเงินจะไม่มีโควต้าเหลือรับคำทำนาย
        //   (เหตุการณ์ 2026-08-25: โควต้า 300/300 หมด → Celtic 99฿ ส่งไม่ออก)
        //   ⇒ เช็คโควต้าจริงจาก LINE ก่อนยิงเสมอ
        $this->assertLineBroadcastAffordable($channelAccessToken, count($messages));

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
        // 🌙 (2026-09-05) วางพุธกลางคืน (index 7) ต่อจากพุธกลางวัน ไม่ใช่ท้ายสุดหลังเสาร์ —
        //    คนเกิดวันพุธไล่สายตาหาบล็อกของตัวเองอยู่ตรงนั้น ถ้าไปอยู่ท้ายโพสจะไม่มีใครเห็น
        //    (เรียงตาม birth_day ตรง ๆ จะได้ … เสาร์ แล้วค่อยพุธกลางคืน = ผิดที่)
        $contents = $contents->sortBy(fn ($c) => (int) $c->birth_day === FortuneChartService::WEDNESDAY_NIGHT
            ? 3.5
            : (float) $c->birth_day)->values();

        foreach ($contents as $content) {
            // 🌙 index 7 = พุธกลางคืน (ราหู) — วันเกิดที่ 8 ตามตำราไทย
            $dayEmojis = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣', '🌘'];
            $emoji = $dayEmojis[$content->birth_day] ?? '⭐';

            $parts[] = "{$emoji} 【คนเกิดวัน{$content->birth_day_name}】";

            // 🌙 (2026-09-05) บนโพสสาธารณะเราไม่รู้เวลาเกิดของคนอ่าน — ต้องให้เขา
            //    เลือกบล็อกเองตามธรรมเนียมโหรไทย ไม่ใช่เดาแทน
            //    ขอบเขตคือ "วันโหร" (เปลี่ยนวันตอนย่ำรุ่ง 06:00) ไม่ใช่เที่ยงคืนแบบปฏิทิน
            //    ⇒ พุธ 18:00 → พฤหัสบดี 05:59 (ดู ThaiAstrologyService::thaiWeekday)
            if ((int) $content->birth_day === FortuneChartService::WEDNESDAY_NIGHT) {
                $parts[] = '(เกิดวันพุธหลัง 18:00 น. ถึงเช้ามืดวันพฤหัสบดี 06:00 น. '
                    .'ตำราไทยถือเป็นพุธกลางคืน ดาวเจ้าเรือนคือราหู อ่านบล็อกนี้)';
            }

            if ($content->ai_prediction) {
                // 🕐 บล็อก [ช่วงเวลา] ถูกสั่งให้ AI เขียนไว้เพื่อ**กล่องแชท** โดยเฉพาะ
                //    (ยิง AI รอบเดียวแล้วแบ่งกันใช้ — ดู FortuneHoroscopeService::periodBlockRequirement)
                //    โพสบนเพจตัดทิ้ง เพื่อให้หน้าตาโพสเหมือนเดิมเป๊ะและไม่ยาวเกิน
                $parts[] = app(DailyArticleMirror::class)->splitPeriodBlock((string) $content->ai_prediction)[0];
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
            // LINE — พฤติกรรมเดิม (อีโมจิครบ แฮชแท็กเต็ม)
            // 🚫 (2026-09-05) ยกเว้นคำขอไลก์/แชร์/แท็ก ที่ถอดทุกช่องทางตามคำสั่งเจ้าของ
            //    (บน LINE ไม่มีเรื่อง reach แต่ก็ไม่ใช่สิ่งที่เราอยากพูดกับลูกค้าอยู่ดี)
            if (! empty($hashtags)) {
                $parts[] = $hashtags;
            }

            return FacebookContentPolicy::stripEngagementBait(implode("\n", $parts));
        }

        // 📘 ลบอีโมจิทั้งโพส — ครอบทั้งอีโมจิประจำวันเกิดที่ฝังในโค้ด (☀️🌙🔴…)
        //    เทมเพลต header/footer ที่แอดมินพิมพ์เอง และป้ายของมงคล (🎨🔢🧭)
        //    ⚠️ เส้นคั่น ━━━ กับวงเล็บ 【】 อยู่นอกช่วงที่ลบ จึงยังอยู่ครบ
        $body = FacebookContentPolicy::clean(implode("\n", $parts));
        $hashtags = FacebookContentPolicy::capHashtagLine($hashtags);

        return $hashtags !== '' ? $body."\n\n".$hashtags : $body;
    }
}
