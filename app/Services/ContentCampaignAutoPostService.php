<?php

namespace App\Services;

use App\Models\AiGenProvider;
use App\Models\FortuneContentCampaign;
use App\Models\FortuneContentPost;
use App\Models\FortuneMysticTopic;
use App\Models\FortuneTellingSetting;
use App\Services\AiGen\CloudflareAiProvider;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * บริการโพสคอนเทนต์อัตโนมัติแบบหลายแคมเปญ (generalize จากระบบสายมูเดิม)
 *
 * แต่ละแคมเปญทำงานอิสระ: คนละแนว คนละเวลา — แต่ใช้ pipeline ร่วมกันทั้งหมด
 *
 * Flow ต่อ 1 slot ของ 1 แคมเปญ:
 * 1. เลือกหัวข้อ — inline pool ของแคมเปญ หรือ bridge ไปคลังสายมูเดิม (LRU)
 * 2. WebSearchService (ถ้าแคมเปญเปิด) — Tavily/Brave/DDG หาแหล่งอ้างอิง
 * 3. AI เขียน caption ตามแนวแคมเปญ (custom prompt หรือ auto-build จาก keywords)
 * 4. 🎨 AI อ่าน caption แล้ว "เขียน image prompt เอง" ให้ภาพเข้ากับเนื้อหา
 *    (ไม่ใช้ keyword ตายตัว) → Cloudflare AI → Pollinations
 * 5. POST ไป Facebook Page (page เดิม ใช้ token ร่วมกันทุกแคมเปญ)
 * 6. บันทึก audit trail ครบ (sources, image_prompt, fb_post_id)
 *
 * Idempotent — unique (campaign_id, post_date, slot_time) กันโพสซ้ำ
 */
class ContentCampaignAutoPostService
{
    protected FortuneTellingSetting $settings;

    protected WebSearchService $webSearch;

    public function __construct(
        ?FortuneTellingSetting $settings = null,
        ?WebSearchService $webSearch = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->webSearch = $webSearch ?? app(WebSearchService::class);
    }

    /**
     * สร้างและโพส 1 slot ของ 1 แคมเปญ
     *
     * @param  FortuneContentCampaign  $campaign  แคมเปญที่จะโพส
     * @param  string  $slotTime  เวลา slot "HH:MM" เช่น "08:30"
     * @param  Carbon|null  $date  วันที่โพส (default: today)
     * @param  bool  $force  true = ลบโพสเก่า (FB + DB) ก่อน republish
     */
    public function generateAndPublish(
        FortuneContentCampaign $campaign,
        string $slotTime,
        ?Carbon $date = null,
        bool $force = false
    ): array {
        $date = $date ?? now('Asia/Bangkok');

        // Normalize slot ผ่าน normalizer กลาง — กัน unique key เพี้ยนจากการแปลงคนละที่
        $slotTime = FortuneContentCampaign::normalizeSlot($slotTime) ?? $slotTime;

        // Idempotent — เช็ค unique (campaign_id, post_date, slot_time)
        $existing = FortuneContentPost::where('campaign_id', $campaign->id)
            ->where('post_date', $date->toDateString())
            ->where('slot_time', $slotTime)
            ->first();

        if ($force && $existing) {
            if ($existing->fb_post_id) {
                $this->deleteFromFacebook($existing->fb_post_id);
            }
            if ($existing->image_path) {
                Storage::disk('public')->delete($existing->image_path);
            }
            $existing->delete();
            $existing = null;
        }

        if ($existing && $existing->status === FortuneContentPost::STATUS_POSTED) {
            return [
                'success' => true,
                'message' => 'โพสแล้ว ข้าม',
                'post_id' => $existing->fb_post_id,
            ];
        }

        // 🛡️ In-progress guard — row ค้างสถานะระหว่างทาง + เพิ่งถูกแตะใน 15 นาที = มี process
        //    อื่นกำลังทำอยู่ (scheduler ชน publish-now / mutex 20 นาทีหมดอายุกลางงาน) → ข้าม
        //    (failed = retry ได้เสมอ / ค้างเกิน 15 นาที = process ตายแล้ว ทำต่อได้)
        if ($existing && ! $force
            && $existing->status !== FortuneContentPost::STATUS_FAILED
            && $existing->updated_at && $existing->updated_at->gt(now()->subMinutes(15))) {
            return [
                'success' => true,
                'message' => "กำลังดำเนินการอยู่ (status: {$existing->status}) ข้าม",
                'post_id' => null,
            ];
        }

        try {
            $post = $existing ?: FortuneContentPost::create([
                'campaign_id' => $campaign->id,
                'post_date' => $date->toDateString(),
                'slot_time' => $slotTime,
                'status' => FortuneContentPost::STATUS_PENDING,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // 🛡️ Race: scheduler กับ admin กดโพสพร้อมกัน — unique (campaign, date, slot) ชน
            //    = อีก process สร้าง row ไปแล้ว → ถือว่าเขากำลังทำ ข้ามเงียบๆ
            Log::info('ContentCampaign: slot ถูกจองโดย process อื่นแล้ว ข้าม', [
                'campaign' => $campaign->slug,
                'slot' => "{$date->toDateString()} {$slotTime}",
            ]);

            return [
                'success' => true,
                'message' => 'มี process อื่นกำลังโพส slot นี้อยู่ ข้าม',
                'post_id' => null,
            ];
        }

        try {
            // 1. เลือกหัวข้อ + hashtags ตามแหล่งของแคมเปญ
            [$subTopic, $hashtags, $mysticTopic] = $this->pickTopic($campaign);

            // 2. Web search หาแหล่งอ้างอิง (ปิดรายแคมเปญได้)
            $sources = [];
            $searchProvider = 'none';
            $researchedAt = null;
            if ($campaign->use_web_search) {
                $post->update(['status' => FortuneContentPost::STATUS_RESEARCHING]);
                $searchQuery = $mysticTopic
                    ? $mysticTopic->pickSearchQuery($subTopic)
                    : $campaign->pickSearchQuery($subTopic);
                $searchResult = $this->webSearch->search($searchQuery, 5);
                $sources = $searchResult['results'] ?? [];
                $searchProvider = $searchResult['provider'] ?? 'none';
                $researchedAt = now(); // เซ็ตเฉพาะตอน search จริง — audit trail ไม่หลอก
            }

            // รวมเป็น update เดียว (ลด round-trip)
            $post->update([
                'sub_topic' => $subTopic,
                'mystic_topic_id' => $mysticTopic?->id,
                'hashtags' => $hashtags,
                'sources' => $sources,
                'search_provider' => $searchProvider,
                'researched_at' => $researchedAt,
            ]);

            // 3. AI เขียน caption ตามแนวแคมเปญ
            $post->update(['status' => FortuneContentPost::STATUS_GENERATING]);
            $caption = $this->generateCaption($campaign, $post, $subTopic, $sources, $hashtags, $mysticTopic);
            $post->update([
                'caption' => $caption,
                'generated_at' => now(),
            ]);

            // 4. AI image — prompt สร้างจากเนื้อหาอัตโนมัติ (bridge สายมู: fallback = keywords หมวดเดิม)
            $this->generateImage($campaign, $post, $mysticTopic);

            // 5. โพส FB
            $post->update(['status' => FortuneContentPost::STATUS_PUBLISHING]);
            $result = $this->publishToFacebook($post);

            $post->markPosted($result['post_id'] ?? null, $result['post_url'] ?? null);

            // อัพเดตสถิติ — แคมเปญ + LRU ของหมวดสายมู (ถ้าใช้ bridge)
            $campaign->markPosted();
            $mysticTopic?->markUsed();

            Log::info('ContentCampaign: โพสสำเร็จ', [
                'post_id' => $post->id,
                'fb_post_id' => $post->fb_post_id,
                'campaign' => $campaign->slug,
                'sub_topic' => $subTopic,
                'slot' => "{$date->toDateString()} {$slotTime}",
                'search_provider' => $searchProvider,
            ]);

            return [
                'success' => true,
                'message' => 'โพสสำเร็จ',
                'post_id' => $post->fb_post_id,
                'url' => $post->fb_post_url,
                'campaign' => $campaign->name_th,
                'sub_topic' => $subTopic,
            ];
        } catch (Exception $e) {
            $post->markFailed($e->getMessage());

            Log::error('ContentCampaign: ล้มเหลว', [
                'post_id' => $post->id,
                'campaign' => $campaign->slug,
                'slot_time' => $slotTime,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 1. เลือกหัวข้อ + hashtags ตาม topic_source ของแคมเปญ
     *
     * - inline: สุ่มจาก pool ในแคมเปญ (pool ว่าง = null ให้ AI เลือกแง่มุมเอง)
     * - mystic_topics: bridge ไปคลังสายมูเดิม — LRU rotation + pool ต่อหมวด (ไม่เสียของเดิม)
     *
     * @return array{0: string|null, 1: array<string>, 2: FortuneMysticTopic|null}
     */
    protected function pickTopic(FortuneContentCampaign $campaign): array
    {
        $hashtagCount = $campaign->hashtag_count
            ?? (int) ($this->settings->mystic_content_hashtag_count ?? 6);

        if ($campaign->topic_source === FortuneContentCampaign::SOURCE_MYSTIC_TOPICS) {
            $topic = FortuneMysticTopic::pickNext();
            if (! $topic) {
                throw new Exception('ไม่มีหมวดคอนเทนต์สายมู — รัน FortuneMysticTopicSeeder ก่อน');
            }

            $subTopic = $topic->pickSubTopic() ?: $topic->name_th;
            $hashtags = $topic->pickHashtags($hashtagCount);

            return [$subTopic, $hashtags, $topic];
        }

        return [$campaign->pickSubTopic(), $campaign->pickHashtags($hashtagCount), null];
    }

    /**
     * 3. AI เขียน caption ตามแนวแคมเปญ
     *
     * - แคมเปญมี content_prompt → ใช้เป็นแกน (รองรับ placeholder {sub_topic} {min_len} {max_len})
     * - ไม่มี → auto-build จาก ชื่อแนว + description + keywords
     * - ฉีดรายการหัวข้อโพสล่าสุดของแคมเปญ กัน AI เขียนซ้ำเรื่องเดิม
     */
    protected function generateCaption(
        FortuneContentCampaign $campaign,
        FortuneContentPost $post,
        ?string $subTopic,
        array $sources,
        array $hashtags,
        ?FortuneMysticTopic $mysticTopic
    ): string {
        $minLen = $campaign->caption_min ?: (int) ($this->settings->mystic_content_caption_min ?? 400);
        $maxLen = $campaign->caption_max ?: (int) ($this->settings->mystic_content_caption_max ?? 700);
        $persona = $campaign->persona ?: ($this->settings->fortune_brand_name ?: 'แม่หมอจันทรา');

        // Reference จาก web search (max 3 แหล่ง)
        $referenceText = '';
        foreach (array_slice($sources, 0, 3) as $i => $src) {
            $idx = $i + 1;
            $title = $src['title'] ?? '';
            $content = $src['content'] ?? $src['snippet'] ?? '';
            if ($content === '') {
                continue;
            }
            $referenceText .= "แหล่งที่ {$idx}: {$title}\n";
            $referenceText .= mb_substr($content, 0, 1500)."\n\n";
        }
        if ($referenceText === '') {
            $referenceText = '(ไม่มีข้อมูลอ้างอิง — เขียนจากความรู้และประสบการณ์ของตัวเอง)';
        }

        // กันเขียนซ้ำ: ดึงหัวข้อโพสล่าสุด 5 โพสของแคมเปญนี้
        $recentTopics = FortuneContentPost::where('campaign_id', $campaign->id)
            ->where('id', '!=', $post->id)
            ->where('status', FortuneContentPost::STATUS_POSTED)
            ->latest('posted_at')
            ->limit(5)
            ->pluck('sub_topic')
            ->filter()
            ->values()
            ->all();
        $recentText = ! empty($recentTopics)
            ? "\n⛔ หัวข้อที่เพิ่งโพสไปแล้ว (ห้ามซ้ำ/ใกล้เคียง): ".implode(' | ', $recentTopics)."\n"
            : '';

        // ── แกนแนวคอนเทนต์ (bridge สายมู: ใส่ชื่อหมวดเดิมให้ AI เหมือนระบบเก่า)
        $mysticLine = $mysticTopic ? "หมวด: {$mysticTopic->name_th} {$mysticTopic->emoji}\n" : '';
        if (! empty(trim((string) $campaign->content_prompt))) {
            // Custom prompt ที่ admin เขียนเอง — แทน placeholder
            $genreCore = strtr($campaign->content_prompt, [
                '{sub_topic}' => $subTopic ?? '(เลือกแง่มุมเอง)',
                '{min_len}' => (string) $minLen,
                '{max_len}' => (string) $maxLen,
            ]);
        } else {
            // Auto-build จากข้อมูลแคมเปญ
            $keywords = implode(', ', $campaign->keywords ?? []);
            $genreCore = "แนวคอนเทนต์: {$campaign->name_th} {$campaign->emoji}\n"
                .$mysticLine
                .($campaign->description_th ? "ทิศทาง: {$campaign->description_th}\n" : '')
                .($keywords !== '' ? "คีย์เวิร์ดแนวนี้: {$keywords}\n" : '')
                .($subTopic
                    ? "หัวข้อวันนี้: {$subTopic}\n"
                    : "หัวข้อวันนี้: เลือกแง่มุมที่น่าสนใจเองจากแนวข้างต้น 1 เรื่อง (เรื่องเดียว โฟกัสชัด)\n");
        }

        $prompt = "คุณคือ \"{$persona}\" ผู้เขียนคอนเทนต์ที่มีผู้ติดตามจำนวนมากบน Facebook\n\n"
            ."📌 {$genreCore}\n"
            .$recentText
            ."\n📚 ข้อมูลอ้างอิง:\n{$referenceText}\n"
            ."──────────────────────\n"
            ."✍️ ภารกิจ: เขียนโพสต์ Facebook ภาษาไทย ความยาว {$minLen}-{$maxLen} ตัวอักษร\n\n"
            ."กฎเข้ม (ห้ามฝ่าฝืน):\n"
            ."1. ห้ามก็อปคำต่อคำจากแหล่งอ้างอิง — เรียบเรียงใหม่ทั้งหมดด้วยภาษาตัวเอง\n"
            ."2. ขึ้นต้นด้วยประโยคติดหู ดึงคนหยุด scroll\n"
            ."3. มีสาระ/แง่คิดจริง — ไม่ใช่น้ำท่วมทุ่ง\n"
            ."4. ภาษาเข้าใจง่าย เหมือนเล่าให้เพื่อนฟัง แบ่งย่อหน้าสั้นๆ อ่านสบาย\n"
            ."5. มี emoji 3-5 ตัว (ไม่เยอะเกิน)\n"
            ."6. ปิดท้ายชวนคุยแบบไม่กดดัน เช่น \"ใครเคยเจอแบบนี้ คอมเมนต์เล่าให้ฟังหน่อย\"\n"
            ."7. ห้ามมีลิงก์ ห้ามคำว่า \"กดไลค์\" \"กดแชร์\" (Facebook ลด reach)\n"
            ."8. ห้ามคำเชิญชวนรุนแรง \"ห้ามพลาด\" \"คลิกเลย\" — เน้นอบอุ่นจริงใจ\n"
            ."9. ความยาว {$minLen}-{$maxLen} ตัวอักษร (ไม่รวม hashtag)\n"
            ."10. **ห้าม**ใส่ hashtag ในเนื้อหา — ระบบเพิ่มให้ทีหลัง\n"
            ."11. **ห้าม**ใส่ markdown (** หรือ ##) ใช้ plain text เท่านั้น\n"
            .($subTopic === null
                ? "12. บรรทัดแรกสุดให้ขึ้นด้วย \"หัวข้อ: <ชื่อเรื่องที่เลือก>\" แล้วค่อยเว้นบรรทัดเขียนโพส — ระบบจะตัดบรรทัดนี้ออกเอง\n"
                : '')
            ."\nเริ่มเขียนเลย:";

        $hashtagLine = implode(' ', $hashtags);

        try {
            $body = $this->generateText($prompt, "content_campaign:{$campaign->slug}");

            // ถ้าให้ AI เลือกหัวข้อเอง — ดึงบรรทัด "หัวข้อ: ..." ออกมาเก็บเป็น sub_topic
            if ($subTopic === null && preg_match('/^หัวข้อ\s*[:：]\s*(.+)$/mu', $body, $m)) {
                $extracted = trim(mb_substr($m[1], 0, 490));
                if ($extracted !== '') {
                    $post->update(['sub_topic' => $extracted]);
                }
                $body = trim(preg_replace('/^หัวข้อ\s*[:：].+$/mu', '', $body, 1) ?? $body);
            }

            // ลบ hashtag ที่ AI ใส่เอง (กันซ้ำกับที่ระบบเติมท้าย)
            $body = preg_replace('/#[^\s#]+/u', '', $body) ?? $body;

            // ลบ markdown หลุด (**, ##) — คงบรรทัดใหม่
            $body = preg_replace('/\*{1,3}|#{1,6}/u', '', $body) ?? $body;

            // Normalize ช่องว่างแนวนอน — เก็บ \n รักษาย่อหน้า
            $body = $this->normalizeWhitespace($body);

            if ($body === '' || mb_strlen($body) < 100) {
                throw new Exception('AI body สั้นเกินไป ('.mb_strlen($body).' ตัวอักษร)');
            }

            // ตัดเฉพาะตอนยาวเกินเพดาน และตัดที่จุดจบประโยค/ย่อหน้าเท่านั้น
            $body = $this->trimToSentenceBoundary($body, $maxLen);

            return $hashtagLine !== '' ? $body."\n\n".$hashtagLine : $body;
        } catch (Exception $e) {
            Log::warning('ContentCampaign: AI caption ล้มเหลว → fallback template', [
                'campaign' => $campaign->slug,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackTemplate($campaign, $subTopic, $sources, $hashtagLine);
        }
    }

    /**
     * ⭐ จุดเรียก AI text ที่เดียวของ service — Gemini หลัก, fallback pool อัตโนมัติ
     *
     * ใช้ทั้ง caption และ image prompt — เปลี่ยน provider/พารามิเตอร์ แก้ที่นี่ที่เดียว
     */
    protected function generateText(string $prompt, string $userContext): string
    {
        $aiService = new FortuneAIService($this->settings, preferredProvider: 'gemini');
        $result = $aiService->generateWithRetryAndFallback(
            questions: [$prompt],
            userProfile: null,
            userPosts: null,
            promptTemplate: null,
            readingType: 'basic',
            birthDate: null,
            userContext: $userContext,
        );

        return trim($result['response'] ?? '');
    }

    /**
     * Fallback template — ใช้เมื่อ AI เขียน caption ล้มเหลว
     */
    protected function fallbackTemplate(
        FortuneContentCampaign $campaign,
        ?string $subTopic,
        array $sources,
        string $hashtagLine
    ): string {
        $title = $subTopic ?: $campaign->name_th;
        $intro = "{$campaign->emoji} {$title}\n\n";
        $body = '';

        foreach (array_slice($sources, 0, 2) as $src) {
            $snippet = trim($src['snippet'] ?? '');
            if ($snippet !== '') {
                $body .= '✨ '.mb_substr($snippet, 0, 250)."\n\n";
            }
        }

        if ($body === '') {
            $body = "วันนี้มาชวนคุยเรื่อง {$title} กัน — เรื่องใกล้ตัวที่หลายคนกำลังเจอ "
                ."ใครมีประสบการณ์หรือมุมมอง คอมเมนต์แลกเปลี่ยนกันได้เลย 🙏\n\n";
        }

        $cta = "💬 ใครเคยเจอแบบนี้ คอมเมนต์เล่าให้ฟังหน่อยนะ\n\n";

        return $intro.$body.$cta.($hashtagLine !== '' ? $hashtagLine : '');
    }

    /**
     * 4. สร้างรูปประกอบ — image prompt "สร้างจากเนื้อหาโพสอัตโนมัติ"
     *
     * ขั้นแรกให้ AI อ่าน caption แล้วเขียน image prompt ภาษาอังกฤษเอง
     * (แนวภาพจึงเข้ากับเรื่องเสมอ ไม่ต้องตั้ง prompt เอง)
     * ล้ม → fallback: keywords หมวดสายมูเดิม (bridge) หรือ keywords ของแคมเปญ
     */
    protected function generateImage(
        FortuneContentCampaign $campaign,
        FortuneContentPost $post,
        ?FortuneMysticTopic $mysticTopic = null
    ): void {
        $imagePrompt = $this->buildImagePromptFromCaption($campaign, $post)
            ?: ($mysticTopic?->pickImagePromptSeed() ?: $campaign->fallbackImageSeed());

        // เติมสไตล์เสริมของแคมเปญ + มาตรฐานคุณภาพ (pattern เดิมที่ผ่าน anti-shadowban มาแล้ว)
        $styleHint = trim((string) $campaign->image_style_hint);
        $fullPrompt = $imagePrompt.', '
            .($styleHint !== '' ? "{$styleHint}, " : '')
            .'professional photography, soft volumetric lighting, '
            .'rich saturated colors, photorealistic, ultra sharp, 8k detail, '
            .'magazine cover quality, '
            .'no text, no watermark, no people faces';

        $post->update(['image_prompt' => $fullPrompt]);

        // Cloudflare AI (primary) → Pollinations (fallback) — helper ทั้งคู่ catch ภายในเอง
        $url = $this->generateImageWithCloudflare($post, $fullPrompt);
        if ($url) {
            $post->update(['image_url' => $url, 'image_provider' => 'cloudflare-ai']);

            return;
        }

        $url = $this->generateImageWithPollinations($post, $fullPrompt);
        if ($url) {
            $post->update(['image_url' => $url, 'image_provider' => 'pollinations']);

            return;
        }

        // ทั้ง 2 provider ล้ม → โพสแบบ text-only (log ไว้ให้ตามรอย)
        Log::warning('ContentCampaign: image gen ล้มทั้ง 2 provider — โพสแบบ text-only', [
            'campaign' => $campaign->slug,
            'post_id' => $post->id,
        ]);
    }

    /**
     * 🎨 ให้ AI อ่าน caption แล้วเขียน image prompt ภาษาอังกฤษให้เข้ากับเนื้อหา
     *
     * @return string|null  image prompt (อังกฤษ, 1 บรรทัด) หรือ null ถ้าล้มเหลว
     */
    protected function buildImagePromptFromCaption(
        FortuneContentCampaign $campaign,
        FortuneContentPost $post
    ): ?string {
        $caption = trim((string) $post->caption);
        if ($caption === '') {
            return null;
        }

        $prompt = "อ่านโพสต์ Facebook ภาษาไทยด้านล่าง แล้วเขียน image generation prompt เป็นภาษาอังกฤษ 1 บรรทัด\n"
            ."สำหรับสร้างภาพประกอบโพสต์ให้ 'อารมณ์และเรื่องราวตรงกับเนื้อหา'\n\n"
            ."กฎ:\n"
            ."1. ภาษาอังกฤษเท่านั้น ยาว 15-40 คำ\n"
            ."2. บรรยายฉาก/บรรยากาศ/แสง/อารมณ์ เป็นรูปธรรม (ไม่ใช่นามธรรมลอยๆ)\n"
            ."3. ห้ามมีตัวหนังสือในภาพ ห้ามใบหน้าคนชัดๆ (silhouette/มุมหลังได้)\n"
            ."4. ตอบเฉพาะ prompt อย่างเดียว ไม่ต้องอธิบาย ไม่ต้องมีเครื่องหมายคำพูด\n\n"
            ."โพสต์:\n".mb_substr($caption, 0, 1200);

        try {
            $raw = $this->generateText($prompt, "content_campaign_img:{$campaign->slug}");
            if ($raw === '') {
                return null;
            }

            // เอาเฉพาะบรรทัดแรกที่มีเนื้อหา + ล้าง markdown/เครื่องหมายคำพูด
            $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
            $line = '';
            foreach ($lines as $l) {
                $l = trim($l, " \t\"'`*");
                if ($l !== '') {
                    $line = $l;
                    break;
                }
            }

            // ตัด prefix ที่ AI ชอบเติม เช่น "Prompt:" / "Image prompt:"
            $line = preg_replace('/^(image\s*)?prompt\s*[:：]\s*/iu', '', $line) ?? $line;
            $line = trim($line);

            // sanity: ต้องเป็นอังกฤษเป็นหลัก + ยาวพอ (กัน AI ตอบไทย/ตอบสั้น)
            $asciiRatio = strlen($line) > 0 ? (strlen(preg_replace('/[^\x20-\x7E]/', '', $line) ?? '') / strlen($line)) : 0;
            if (mb_strlen($line) < 15 || $asciiRatio < 0.8) {
                return null;
            }

            return mb_substr($line, 0, 500);
        } catch (Exception $e) {
            Log::warning('ContentCampaign: AI image prompt ล้มเหลว → ใช้ fallback keywords', [
                'campaign' => $campaign->slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function generateImageWithCloudflare(FortuneContentPost $post, string $prompt): ?string
    {
        try {
            $providerModel = AiGenProvider::where('slug', 'cloudflare-ai')->first();
            if (! $providerModel) {
                return null;
            }

            $cfProvider = new CloudflareAiProvider($providerModel);
            if (! $cfProvider->isConfigured()) {
                return null;
            }

            $result = $cfProvider->generateImage($prompt, [
                'model' => 'flux-1-schnell',
                'size' => '1024x1024',
                'steps' => 8,
                'seed' => $this->imageSeed($post),
            ]);

            if (! ($result['success'] ?? false) || empty($result['images'][0]['url'])) {
                return null;
            }

            $sourceUrl = $result['images'][0]['url'];
            $relativePath = $this->savedImagePath($post);
            $absolutePath = $this->ensureDir($relativePath);

            // Cloudflare provider เซฟใน Storage::disk('public') แล้ว — copy มาที่ path ของเรา
            $sourcePath = (string) parse_url($sourceUrl, PHP_URL_PATH);
            if (str_starts_with($sourcePath, '/storage/')) {
                $diskRelative = substr($sourcePath, strlen('/storage/'));
                if (Storage::disk('public')->exists($diskRelative)) {
                    file_put_contents($absolutePath, Storage::disk('public')->get($diskRelative));
                } else {
                    return null;
                }
            } else {
                $resp = Http::timeout(30)->get($sourceUrl);
                if (! $resp->successful()) {
                    return null;
                }
                file_put_contents($absolutePath, $resp->body());
            }

            $post->update(['image_path' => $relativePath]);

            return asset('storage/'.$relativePath);
        } catch (Exception $e) {
            Log::warning('ContentCampaign: Cloudflare AI exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function generateImageWithPollinations(FortuneContentPost $post, string $prompt): ?string
    {
        $encoded = rawurlencode(mb_substr($prompt, 0, 800));
        $seed = $this->imageSeed($post);
        $url = "https://image.pollinations.ai/prompt/{$encoded}"
            ."?width=1080&height=1080&seed={$seed}&nologo=true&model=turbo&enhance=true";

        try {
            $response = Http::timeout(45)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);

            if (! $response->successful() || empty($response->body())) {
                return null;
            }

            $relativePath = $this->savedImagePath($post);
            $absolutePath = $this->ensureDir($relativePath);
            file_put_contents($absolutePath, $response->body());

            $post->update(['image_path' => $relativePath]);

            return asset('storage/'.$relativePath);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Seed deterministic ต่อ (campaign, date, slot) — รัน publish ซ้ำได้รูปเดิม
     */
    protected function imageSeed(FortuneContentPost $post): int
    {
        $slotMin = 0;
        if (preg_match('/^(\d{2}):(\d{2})$/', (string) $post->slot_time, $m)) {
            $slotMin = ((int) $m[1]) * 60 + (int) $m[2];
        }

        return ((int) $post->campaign_id * 100000)
            + ($slotMin * 10)
            + ((int) $post->post_date->format('Ymd') % 100);
    }

    /**
     * Path เซฟรูป → content-campaigns/{date}/c{campaign}-{HHMM}.jpg
     */
    protected function savedImagePath(FortuneContentPost $post): string
    {
        return sprintf(
            'content-campaigns/%s/c%d-%s.jpg',
            $post->post_date->format('Y-m-d'),
            $post->campaign_id,
            str_replace(':', '', (string) $post->slot_time)
        );
    }

    protected function ensureDir(string $relativePath): string
    {
        $absolutePath = storage_path("app/public/{$relativePath}");
        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $absolutePath;
    }

    /**
     * Normalize ช่องว่างโดยรักษา \n (ย่อหน้า) — pattern เดียวกับสายมูเดิม
     */
    protected function normalizeWhitespace(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]*\n[ \t]*/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * ตัดข้อความให้จบที่ประโยค/ย่อหน้าสมบูรณ์เมื่อยาวเกินเพดาน — ไม่ตัดกลางประโยค
     * (pattern เดียวกับสายมูเดิม — ผ่านการแก้ caption truncation แล้ว)
     */
    protected function trimToSentenceBoundary(string $text, int $maxLen): string
    {
        $ceiling = max($maxLen + 250, (int) round($maxLen * 1.4));
        if (mb_strlen($text) <= $ceiling) {
            return $text;
        }

        $head = mb_substr($text, 0, $ceiling);
        $minKeep = (int) round($maxLen * 0.5);

        foreach (["\n\n", "\n"] as $brk) {
            $pos = mb_strrpos($head, $brk);
            if ($pos !== false && $pos >= $minKeep) {
                return rtrim(mb_substr($head, 0, $pos));
            }
        }

        $best = false;
        foreach (['. ', '! ', '? ', 'ฯ ', '。'] as $needle) {
            $pos = mb_strrpos($head, $needle);
            if ($pos !== false) {
                $end = $pos + mb_strlen($needle);
                if ($best === false || $end > $best) {
                    $best = $end;
                }
            }
        }
        if ($best !== false && $best >= $minKeep) {
            return rtrim(mb_substr($head, 0, $best));
        }

        $pos = mb_strrpos($head, ' ');
        if ($pos !== false && $pos >= $minKeep) {
            return rtrim(mb_substr($head, 0, $pos));
        }

        return rtrim($head);
    }

    /**
     * ⭐ Resolve Page ID + token ที่เดียว — publish/delete ใช้ chain เดียวกันเป๊ะ
     *
     * (แยก 2 copy แล้ว chain diverge = --force ลบโพสเก่าไม่ได้เงียบๆ → โพสซ้ำบนเพจ)
     *
     * @return array{0: string|null, 1: string|null}  [pageId, pageToken]
     */
    protected function resolvePageCredentials(): array
    {
        $fresh = FortuneTellingSetting::query()->first();
        if (! $fresh) {
            return [null, null];
        }

        $pageToken = $fresh->facebook_page_token
            ?? $fresh->facebook_page_access_token
            ?? $fresh->getRawOriginal('facebook_page_token');

        return [$fresh->facebook_page_id, $pageToken];
    }

    /**
     * 5. โพสไป Facebook Page — ใช้ page + token เดิมร่วมกันทุกแคมเปญ
     */
    protected function publishToFacebook(FortuneContentPost $post): array
    {
        [$pageId, $pageToken] = $this->resolvePageCredentials();

        if (empty($pageId) || empty($pageToken)) {
            throw new Exception('ไม่พบ Facebook Page ID หรือ Page Access Token ใน settings');
        }

        $caption = $post->caption ?: 'คอนเทนต์ประจำวัน';

        if (! empty($post->image_url)) {
            $endpoint = "https://graph.facebook.com/v21.0/{$pageId}/photos";
            $response = Http::timeout(60)->post($endpoint, [
                'url' => $post->image_url,
                'caption' => $caption,
                'access_token' => $pageToken,
            ]);
        } else {
            $endpoint = "https://graph.facebook.com/v21.0/{$pageId}/feed";
            $response = Http::timeout(60)->post($endpoint, [
                'message' => $caption,
                'access_token' => $pageToken,
            ]);
        }

        if (! $response->successful()) {
            $error = $response->json('error.message', 'Facebook API error: HTTP '.$response->status());
            throw new Exception($error);
        }

        $data = $response->json();
        $postId = $data['post_id'] ?? $data['id'] ?? null;

        return [
            'post_id' => $postId,
            'post_url' => $postId ? "https://www.facebook.com/{$postId}" : null,
        ];
    }

    /**
     * ลบโพสบน FB (best-effort, สำหรับ --force republish)
     */
    protected function deleteFromFacebook(string $fbPostId): bool
    {
        try {
            [, $pageToken] = $this->resolvePageCredentials();

            if (empty($pageToken)) {
                return false;
            }

            $response = Http::timeout(30)->delete(
                "https://graph.facebook.com/v21.0/{$fbPostId}",
                ['access_token' => $pageToken]
            );

            return $response->successful() && ($response->json('success') === true);
        } catch (Exception $e) {
            return false;
        }
    }
}
