<?php

namespace App\Services;

use App\Models\AiApiKey;
use App\Models\AiGenProvider;
use App\Models\FortuneContentCampaign;
use App\Models\FortuneContentPost;
use App\Models\FortuneMysticTopic;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FacebookContentPolicy;
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
 *    — เลือก provider/model เจนบทความรายแคมเปญได้ (text_provider/text_model)
 * 4. 🎨 ภาพประกอบ (ปิดรายแคมเปญได้ผ่าน generate_image):
 *    - prompt: AI อ่าน caption เขียนเอง (ติ๊ก match_caption) + custom prompt + preset options
 *    - โมเดล: เลือกได้รายแคมเปญ (image_provider_choice "slug:model")
 *      ล้ม → fallback chain เดิม Cloudflare FLUX → Pollinations
 * 5. POST ไป Facebook Page (page เดิม ใช้ token ร่วมกันทุกแคมเปญ)
 * 6. บันทึก audit trail ครบ (sources, image_prompt, fb_post_id)
 *
 * Idempotent — unique (campaign_id, post_date, slot_time) กันโพสซ้ำ
 */
class ContentCampaignAutoPostService
{
    /**
     * 🖼️ Provider เจนภาพที่ให้เลือกรายแคมเปญ — slug (ตาราง ai_gen_providers) → class
     *
     * แสดงใน dropdown เฉพาะตัวที่ตั้งค่าคีย์แล้ว (isConfigured) เท่านั้น
     *
     * ⛔ 'freepik' ไม่อยู่ในลิสต์โดยเจตนา — กฎ ABSOLUTE BAN ห้ามใช้โมเดลกินเครดิต
     *    (ดู rule_freepik_unlimited_only) ห้ามเพิ่มกลับเข้ามา
     * ⛔ 'kling-ai' ไม่อยู่เช่นกัน — เน้นวิดีโอ + คิดเครดิตต่อภาพไม่ชัดเจน
     */
    public const IMAGE_PROVIDER_CLASSES = [
        'cloudflare-ai' => \App\Services\AiGen\CloudflareAiProvider::class,
        'openai' => \App\Services\AiGen\OpenaiProvider::class,
        'together-ai' => \App\Services\AiGen\TogetherAiProvider::class,
        'grok' => \App\Services\AiGen\GrokProvider::class,
        // ⛔ 'stability-ai' ตัดออก — StabilityAiProvider ส่ง JSON แต่ API v2beta ต้องการ
        //    multipart/form-data → ล้มทุกครั้ง (เพิ่มกลับได้เมื่อแก้ provider แล้ว)
        'fal-ai' => \App\Services\AiGen\FalAiProvider::class,
        'bfl' => \App\Services\AiGen\BflProvider::class,
        'replicate' => \App\Services\AiGen\ReplicateProvider::class,
        'ideogram' => \App\Services\AiGen\IdeogramProvider::class,
        'leonardo-ai' => \App\Services\AiGen\LeonardoAiProvider::class,
        'huggingface' => \App\Services\AiGen\HuggingfaceProvider::class,
    ];

    /**
     * ป้ายบอกค่าใช้จ่ายต่อ provider — ต่อท้ายชื่อกลุ่มใน dropdown ให้แอดมินรู้ก่อนเลือก
     * (แคมเปญ = โพส scheduled ทุกวัน — เลือกตัวเสียเงินคือจ่ายซ้ำเงียบๆ ทุก slot)
     */
    protected const IMAGE_PROVIDER_COST_HINTS = [
        'cloudflare-ai' => 'ฟรี ~40 ภาพ/วัน',
        'huggingface' => 'ฟรี tier',
        'together-ai' => 'ฟรีเฉพาะ flux-schnell-free',
    ];

    /**
     * โมเดล Pollinations ที่ให้เลือก — เรียกผ่าน URL ตรง ไม่ต้องมีคีย์ (ฟรีเสมอ)
     * แยกจาก IMAGE_PROVIDER_CLASSES เพราะไม่ผ่าน AiGenProvider registry
     */
    public const POLLINATIONS_MODELS = ['turbo', 'flux'];

    /**
     * 🏊 โมเดลเจนภาพที่ใช้ "คีย์จาก AI Key Pool" (ตาราง ai_api_keys) โดยตรง
     *
     * คนละที่กับ AiGen registry — คีย์ Gemini/OpenAI ของเราอยู่ใน pool (เทสผ่านแล้ว)
     * จึงเปิดเส้นทางนี้ให้เจนภาพด้วยคีย์เดียวกับที่ใช้เขียนบทความได้เลย
     *
     * ✅ ทุก model ID ยืนยันสดกับ API จริงด้วยคีย์ prod แล้ว 2026-07-12
     *    (Gemini: GET /v1beta/models · OpenAI: GET /v1/models — กฎ verify-model-ids-live)
     *    ห้ามเพิ่ม ID ใหม่โดยไม่ยิงทดสอบก่อน
     */
    public const POOL_IMAGE_MODELS = [
        'pool-gemini' => [
            'gemini-2.5-flash-image' => 'gemini-2.5-flash-image (Nano Banana)',
            'gemini-3.1-flash-image' => 'gemini-3.1-flash-image (Nano Banana รุ่นใหม่)',
            'gemini-3.1-flash-lite-image' => 'gemini-3.1-flash-lite-image (เบา/ถูกสุด)',
            'gemini-3-pro-image' => 'gemini-3-pro-image (Nano Banana Pro 💰)',
        ],
        'pool-openai' => [
            'gpt-image-2' => 'gpt-image-2 (ใหม่สุด 💰)',
            'gpt-image-1.5' => 'gpt-image-1.5 💰',
            'gpt-image-1' => 'gpt-image-1 💰',
            'gpt-image-1-mini' => 'gpt-image-1-mini (ถูกสุดของ OpenAI)',
        ],
    ];

    /**
     * map slug pool → provider ในตาราง ai_api_keys + ชื่อกลุ่มบน dropdown
     */
    protected const POOL_IMAGE_GROUPS = [
        'pool-gemini' => ['provider' => 'gemini', 'group' => 'Google Gemini — คีย์จาก AI Pool (flash มี free tier)'],
        'pool-openai' => ['provider' => 'openai', 'group' => 'OpenAI gpt-image — คีย์จาก AI Pool 💰 เสียเงินต่อภาพ'],
    ];

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

        // Idempotent — เช็ค unique (fortune_page_id, campaign_id, post_date, slot_time)
        // 🏬 (2026-08-15) กรองสาขาด้วย ไม่งั้นสาขาที่ 2 เห็นของสาขาแรกแล้วข้าม
        $existing = FortuneContentPost::forCurrentFortunePage()
            ->where('campaign_id', $campaign->id)
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

            // 4. AI image — ปิดรายแคมเปญได้ (generate_image=false → โพส text-only โดยเจตนา)
            if ($campaign->generate_image ?? true) {
                $this->generateImage($campaign, $post, $mysticTopic);
            }

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
        // 📘 เพดานแฮชแท็กของแบรนด์ทับค่าที่ตั้งไว้เสมอ (เจ้าของสั่ง ไม่เกิน 3)
        //    ค่าในแคมเปญ/ตั้งค่ายังเก็บไว้เหมือนเดิม แค่ใช้ไม่เกินเพดาน
        $hashtagCount = min(
            $campaign->hashtag_count ?? (int) ($this->settings->mystic_content_hashtag_count ?? 6),
            FacebookContentPolicy::MAX_HASHTAGS
        );

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
        // 🏬 (2026-08-15) นับเฉพาะสาขาตัวเอง — คนดูเพจ B ไม่เคยเห็นโพสของเพจ A
        //    ถ้านับรวมทุกสาขา เนื้อหาจะถูกบังคับให้ต่างกันไปเรื่อยๆ ทั้งที่ไม่จำเป็น
        $recentTopics = FortuneContentPost::forCurrentFortunePage()
            ->where('campaign_id', $campaign->id)
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
            .'5. '.FacebookContentPolicy::noEmojiRule()
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
            $body = $this->generateText($prompt, "content_campaign:{$campaign->slug}", $campaign);

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

            // 📘 ด่านกวาดท้ายทาง — โมเดลใส่อีโมจิกลับมาแม้สั่งห้ามใน prompt แล้ว
            $body = FacebookContentPolicy::clean($body);

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
     * ⭐ จุดเรียก AI text ที่เดียวของ service — เปลี่ยน provider/พารามิเตอร์ แก้ที่นี่ที่เดียว
     *
     * ใช้ทั้ง caption และ image prompt
     * - แคมเปญตั้ง text_provider → ใช้เป็น provider หลัก (ไม่ตั้ง = gemini เดิม)
     * - แคมเปญตั้ง text_model → บังคับโมเดลนั้นกับ key ของ provider ที่เลือกเท่านั้น
     *   (key provider อื่นใน fallback chain ยังใช้โมเดลของตัวเอง — model ข้าม provider ไม่ได้)
     */
    protected function generateText(string $prompt, string $userContext, ?FortuneContentCampaign $campaign = null): string
    {
        $provider = trim((string) ($campaign->text_provider ?? '')) ?: 'gemini';
        $model = trim((string) ($campaign->text_model ?? ''));

        $aiService = new FortuneAIService($this->settings, preferredProvider: $provider);
        $result = $aiService->generateWithRetryAndFallback(
            questions: [$prompt],
            userProfile: null,
            userPosts: null,
            promptTemplate: null,
            readingType: 'basic',
            birthDate: null,
            userContext: $userContext,
            modelOverrides: $model !== '' ? [$provider => $model] : null,
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
        // 📘 เทมเพลตนี้เขียนด้วยมือ จึงต้องไม่มีอีโมจิตั้งแต่ต้นทาง (clean() ยังกวาดซ้ำให้อีกชั้น)
        $title = $subTopic ?: $campaign->name_th;
        $intro = "{$title}\n\n";
        $body = '';

        foreach (array_slice($sources, 0, 2) as $src) {
            $snippet = trim($src['snippet'] ?? '');
            if ($snippet !== '') {
                $body .= mb_substr($snippet, 0, 250)."\n\n";
            }
        }

        if ($body === '') {
            $body = "วันนี้มาชวนคุยเรื่อง {$title} กัน — เรื่องใกล้ตัวที่หลายคนกำลังเจอ "
                ."ใครมีประสบการณ์หรือมุมมอง คอมเมนต์แลกเปลี่ยนกันได้เลย\n\n";
        }

        $cta = "ใครเคยเจอแบบนี้ คอมเมนต์เล่าให้ฟังหน่อยนะ\n\n";

        return FacebookContentPolicy::clean($intro.$body.$cta)
            .($hashtagLine !== '' ? "\n\n".$hashtagLine : '');
    }

    /**
     * 4. สร้างรูปประกอบ — prompt ประกอบจาก 3 ชั้น + โมเดลเลือกได้รายแคมเปญ
     *
     * ชั้น prompt:
     * 1. แกนภาพ: AI อ่าน caption เขียนเอง (ติ๊ก match_caption) โดยผสาน custom prompt
     *    ของแอดมินเป็นข้อกำหนดบังคับ / ไม่ติ๊ก = ใช้ custom prompt ตรงๆ
     *    / ไม่มีทั้งคู่ = keywords หมวดสายมูเดิม (bridge) หรือ keywords แคมเปญ
     * 2. สไตล์เสริม (image_style_hint)
     * 3. Preset options ที่ติ๊กไว้ (photorealistic, no text ฯลฯ — null = ชุด default เดิม)
     *
     * โมเดล: image_provider_choice "slug:model" → ลองตัวที่เลือกก่อน
     * ล้ม/ไม่ตั้ง → chain เดิม Cloudflare FLUX → Pollinations
     */
    protected function generateImage(
        FortuneContentCampaign $campaign,
        FortuneContentPost $post,
        ?FortuneMysticTopic $mysticTopic = null
    ): void {
        $options = $campaign->resolvedImageOptions();

        // Custom prompt ของแอดมิน — รองรับ placeholder {sub_topic}
        $customPrompt = trim(strtr((string) $campaign->image_custom_prompt, [
            '{sub_topic}' => (string) ($post->sub_topic ?? ''),
        ]));

        // ชั้น 1: แกนภาพ
        $imagePrompt = null;
        if (in_array('match_caption', $options, true)) {
            $imagePrompt = $this->buildImagePromptFromCaption(
                $campaign,
                $post,
                $customPrompt !== '' ? $customPrompt : null,
                $options,
            );
        }
        if ($imagePrompt === null || $imagePrompt === '') {
            $imagePrompt = $customPrompt !== ''
                ? $customPrompt
                : ($mysticTopic?->pickImagePromptSeed() ?: $campaign->fallbackImageSeed());
        }

        // ชั้น 2+3: สไตล์เสริม + preset fragments (ชุด default = pattern เดิมที่ผ่าน anti-shadowban มาแล้ว)
        $styleHint = trim((string) $campaign->image_style_hint);
        $fullPrompt = implode(', ', array_filter(array_merge(
            [$imagePrompt, $styleHint],
            $campaign->imageOptionFragments(),
        ), fn ($part) => trim((string) $part) !== ''));

        $post->update(['image_prompt' => $fullPrompt]);

        // โมเดลที่แอดมินเลือก — ลองก่อน ล้มค่อย fallback chain (โพสมีภาพสำคัญกว่าตรงโมเดล)
        $choice = trim((string) $campaign->image_provider_choice);
        if ($choice !== '' && $choice !== 'auto' && str_contains($choice, ':')) {
            [$slug, $model] = explode(':', $choice, 2);

            if (isset(self::POOL_IMAGE_MODELS[$slug])) {
                $url = $this->generateImageWithPoolKey($post, $slug, $model, $fullPrompt);
            } elseif ($slug === 'pollinations') {
                $url = $this->generateImageWithPollinations($post, $fullPrompt, $model);
            } else {
                $url = $this->generateImageWithAiGenProvider($post, $slug, $model, $fullPrompt);
            }

            if ($url) {
                $post->update(['image_url' => $url, 'image_provider' => $choice]);

                return;
            }

            Log::warning('ContentCampaign: โมเดลภาพที่เลือกล้มเหลว → fallback chain อัตโนมัติ', [
                'campaign' => $campaign->slug,
                'choice' => $choice,
            ]);
        }

        // Chain เดิม: Cloudflare FLUX (primary) → Pollinations (fallback) — catch ภายในเอง
        // (ข้ามถ้าตัวที่แอดมินเลือกคือ CF flux ตัวเดียวกันที่เพิ่งล้ม — ไม่ยิงซ้ำเสีย timeout ฟรี)
        if ($choice !== 'cloudflare-ai:flux-1-schnell') {
            $url = $this->generateImageWithAiGenProvider($post, 'cloudflare-ai', 'flux-1-schnell', $fullPrompt);
            if ($url) {
                $post->update(['image_url' => $url, 'image_provider' => 'cloudflare-ai']);

                return;
            }
        }

        $url = $this->generateImageWithPollinations($post, $fullPrompt);
        if ($url) {
            $post->update(['image_url' => $url, 'image_provider' => 'pollinations']);

            return;
        }

        // ทุก provider ล้ม → โพสแบบ text-only (log ไว้ให้ตามรอย)
        Log::warning('ContentCampaign: image gen ล้มทุก provider — โพสแบบ text-only', [
            'campaign' => $campaign->slug,
            'post_id' => $post->id,
        ]);
    }

    /**
     * 🎨 ให้ AI อ่าน caption แล้วเขียน image prompt ภาษาอังกฤษให้เข้ากับเนื้อหา
     *
     * @param  string|null  $adminRequirements  custom prompt ของแอดมิน — AI ต้องผสานเข้าไปใน prompt
     * @param  array<string>  $options  preset options ที่ติ๊กไว้ (คุมกฎ no_text/no_faces ใน instruction)
     * @return string|null image prompt (อังกฤษ, 1 บรรทัด) หรือ null ถ้าล้มเหลว
     */
    protected function buildImagePromptFromCaption(
        FortuneContentCampaign $campaign,
        FortuneContentPost $post,
        ?string $adminRequirements = null,
        array $options = FortuneContentCampaign::DEFAULT_IMAGE_OPTIONS
    ): ?string {
        $caption = trim((string) $post->caption);
        if ($caption === '') {
            return null;
        }

        // กฎห้ามตัวหนังสือ/หน้าคน — ตามที่แอดมินติ๊กไว้เท่านั้น (default = ห้ามทั้งคู่ เหมือนเดิม)
        $restrictions = [];
        if (in_array('no_text', $options, true)) {
            $restrictions[] = 'ห้ามมีตัวหนังสือในภาพ';
        }
        if (in_array('no_faces', $options, true)) {
            $restrictions[] = 'ห้ามใบหน้าคนชัดๆ (silhouette/มุมหลังได้)';
        }

        $adminLine = trim((string) $adminRequirements) !== ''
            ? "\n📌 ข้อกำหนดภาพจากแอดมิน (ต้องผสานเข้าไปใน prompt ด้วย):\n".trim($adminRequirements)."\n"
            : '';

        // ประกอบกฎแบบ renumber อัตโนมัติ — ข้อ restriction หายไปเลขต้องไม่กระโดด
        $rules = array_merge(
            [
                'ภาษาอังกฤษเท่านั้น ยาว 15-40 คำ',
                'บรรยายฉาก/บรรยากาศ/แสง/อารมณ์ เป็นรูปธรรม (ไม่ใช่นามธรรมลอยๆ)',
            ],
            ! empty($restrictions) ? [implode(' ', $restrictions)] : [],
            ['ตอบเฉพาะ prompt อย่างเดียว ไม่ต้องอธิบาย ไม่ต้องมีเครื่องหมายคำพูด'],
        );
        $ruleText = '';
        foreach ($rules as $i => $rule) {
            $ruleText .= ($i + 1).'. '.$rule."\n";
        }

        $prompt = "อ่านโพสต์ Facebook ภาษาไทยด้านล่าง แล้วเขียน image generation prompt เป็นภาษาอังกฤษ 1 บรรทัด\n"
            ."สำหรับสร้างภาพประกอบโพสต์ให้ 'อารมณ์และเรื่องราวตรงกับเนื้อหา'\n"
            .$adminLine
            ."\nกฎ:\n"
            .$ruleText
            ."\nโพสต์:\n".mb_substr($caption, 0, 1200);

        try {
            $raw = $this->generateText($prompt, "content_campaign_img:{$campaign->slug}", $campaign);
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

    /**
     * 🎛️ รายการโมเดลเจนภาพที่ "พร้อมใช้จริง" สำหรับ dropdown หน้า admin
     *
     * เงื่อนไขปรากฏในลิสต์: provider อยู่ใน registry (ai_gen_providers) + is_active
     * + ตั้งค่าคีย์แล้ว (isConfigured) — คีย์หาย/ยังไม่ตั้ง = ไม่โผล่ให้เลือก
     * Pollinations พิเศษ: เรียก URL ตรงไม่ต้องมีคีย์ → พร้อมเสมอ
     *
     * @return array<array{value:string,label:string,group:string}>
     */
    public static function availableImageModels(): array
    {
        $options = [
            ['value' => 'auto', 'label' => 'อัตโนมัติ (Cloudflare FLUX → Pollinations)', 'group' => 'ค่าเริ่มต้นระบบ'],
        ];

        // โมเดลจากคีย์ AI Key Pool — Gemini Nano Banana / OpenAI gpt-image
        // แสดงเมื่อคีย์ "เจนภาพได้จริง" (probe + cache) ไม่ใช่แค่มีคีย์:
        // Gemini free tier เจนภาพไม่ได้ (quota=0) — ถ้าโชว์ทั้งที่ใช้ไม่ได้ แอดมินเลือกแล้ว
        // ทุกโพสจะ fallback เงียบๆ ตลอด
        try {
            foreach (self::POOL_IMAGE_GROUPS as $slug => $info) {
                if (! self::poolImageCapable($slug)) {
                    continue;
                }
                foreach (self::POOL_IMAGE_MODELS[$slug] as $modelKey => $label) {
                    $options[] = [
                        'value' => "{$slug}:{$modelKey}",
                        'label' => $label,
                        'group' => $info['group'],
                    ];
                }
            }
        } catch (Exception $e) {
            // migration คอลัมน์เทสยังไม่รัน ฯลฯ — ข้ามกลุ่ม pool ไป dropdown ยังใช้ได้
        }

        try {
            $providers = AiGenProvider::active()
                ->whereIn('type', ['image', 'both'])
                ->whereIn('slug', array_keys(self::IMAGE_PROVIDER_CLASSES))
                ->orderBy('priority')
                ->get();

            foreach ($providers as $providerModel) {
                $class = self::IMAGE_PROVIDER_CLASSES[$providerModel->slug];
                try {
                    $instance = new $class($providerModel);
                    if (! $instance->isConfigured()) {
                        continue;
                    }
                    $costHint = self::IMAGE_PROVIDER_COST_HINTS[$providerModel->slug] ?? '💰 เสียเงินต่อภาพ';
                    foreach (self::providerModelKeys($class) as $modelKey) {
                        $options[] = [
                            'value' => "{$providerModel->slug}:{$modelKey}",
                            'label' => $modelKey,
                            'group' => "{$providerModel->name} — {$costHint}",
                        ];
                    }
                } catch (Exception $e) {
                    continue; // provider ตัวเดียวพัง — ไม่ควรล้มทั้ง dropdown
                }
            }
        } catch (Exception $e) {
            Log::warning('ContentCampaign: โหลดรายการโมเดลภาพไม่สำเร็จ', ['error' => $e->getMessage()]);
        }

        // Pollinations — ฟรี ไม่ต้องมีคีย์ ใช้ได้เสมอ
        foreach (self::POLLINATIONS_MODELS as $modelKey) {
            $options[] = [
                'value' => "pollinations:{$modelKey}",
                'label' => $modelKey,
                'group' => 'Pollinations.ai (ฟรี ไม่ต้องมีคีย์)',
            ];
        }

        return $options;
    }

    /**
     * อ่านรายชื่อโมเดลจาก const MODELS ของ provider class (protected → ใช้ reflection)
     * class ไม่มี MODELS → คืน ['default'] (ตอนเรียกจะไม่ส่งพารามิเตอร์ model)
     *
     * @return array<string>
     */
    protected static function providerModelKeys(string $class): array
    {
        try {
            $reflection = new \ReflectionClass($class);
            if ($reflection->hasConstant('MODELS')) {
                $models = $reflection->getConstant('MODELS');
                if (is_array($models) && $models !== []) {
                    return array_keys($models);
                }
            }
        } catch (\Throwable $e) {
            // ตกไปใช้ default ด้านล่าง
        }

        return ['default'];
    }

    /**
     * เจนภาพผ่าน AiGenProvider registry ตัวใดก็ได้ในลิสต์ที่รองรับ
     *
     * ใช้ทั้งโมเดลที่แอดมินเลือก และ Cloudflare ใน fallback chain — ทางเดินเดียวกัน
     *
     * @param  string  $slug  slug ใน ai_gen_providers (ต้องอยู่ใน IMAGE_PROVIDER_CLASSES)
     * @param  string  $model  model key ของ provider นั้น ('default' = ไม่ส่ง ให้ provider เลือกเอง)
     * @return string|null URL ภาพที่เซฟแล้ว หรือ null ถ้าล้มเหลว
     */
    protected function generateImageWithAiGenProvider(
        FortuneContentPost $post,
        string $slug,
        string $model,
        string $prompt
    ): ?string {
        try {
            $class = self::IMAGE_PROVIDER_CLASSES[$slug] ?? null;
            if (! $class) {
                return null;
            }

            $providerModel = AiGenProvider::where('slug', $slug)->first();
            if (! $providerModel) {
                return null;
            }

            $instance = new $class($providerModel);
            if (! $instance->isConfigured()) {
                return null;
            }

            // 🛡️ ยืนยัน model กับลิสต์ของ provider class — ค่าแปลกปลอม (POST ตรง/มือแก้ DB)
            //    บังคับกลับเป็น default: กัน BFL ต่อ model ดิบเข้า URL path โดยแนบคีย์จริงไปด้วย
            if (! in_array($model, self::providerModelKeys($class), true)) {
                $model = 'default';
            }

            $parameters = [
                'seed' => $this->imageSeed($post),
            ];
            if ($model !== '' && $model !== 'default') {
                $parameters['model'] = $model;
            }
            // ขนาดภาพ — grok: xAI images API ไม่รองรับ size (reject ทั้ง request)
            //           fal-ai: รับเฉพาะ enum ('square_hd' ฯลฯ) ไม่ใช่ "WxH"
            if ($slug === 'fal-ai') {
                $parameters['size'] = 'square_hd';
            } elseif ($slug !== 'grok') {
                $parameters['size'] = '1024x1024';
            }
            // Cloudflare FLUX: default 4 steps ภาพหยาบ — คงค่า 8 เดิมของระบบไว้
            if ($slug === 'cloudflare-ai') {
                $parameters['steps'] = 8;
            }

            $result = $instance->generateImage($prompt, $parameters);

            if (! ($result['success'] ?? false) || empty($result['images'][0]['url'])) {
                Log::warning('ContentCampaign: image provider ตอบไม่สำเร็จ', [
                    'provider' => $slug,
                    'model' => $model,
                    'error' => $result['error'] ?? 'unknown',
                ]);

                return null;
            }

            return $this->storeImageFromUrl($post, $result['images'][0]['url']);
        } catch (Exception $e) {
            Log::warning('ContentCampaign: image provider exception', [
                'provider' => $slug,
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ไฟล์เก็บผล probe ความสามารถเจนภาพของคีย์ pool
     *
     * ⚠️ ใช้ไฟล์แทน cache() โดยเจตนา — deploy.sh รัน `artisan cache:clear` ทุก deploy
     * (repo นี้ push = auto-deploy เกือบทุกวัน) ถ้าเก็บใน cache ผล positive จะไม่เคยรอด
     * → probe Gemini จ่ายเงินเจนภาพจริง + หน้า admin ช้า ซ้ำทุกรอบ deploy
     */
    protected const POOL_CAP_FILE = 'content-campaigns/pool-image-capability.json';

    /**
     * 🏊 เช็คว่าคีย์ใน pool "เจนภาพได้จริง" ไหม
     *
     * - pool-openai: GET /v1/models (ฟรี) — มี gpt-image-* ในลิสต์ = บัญชีเข้าถึง image API
     * - pool-gemini: ยิงเจนภาพจิ๋วจริง (shape เดียวกับตอนโพสจริง รวม seed) —
     *   คีย์ free tier 429 ทันที (quota=0, ไม่เสียเงิน) / คีย์ billing เจนสำเร็จ
     *   (~1-2฿ ต่อ 3 วัน — ยอมรับได้ แลกกับ dropdown ที่ไม่หลอก)
     * - ผลเก็บลงไฟล์: ใช้ได้ 3 วัน / ใช้ไม่ได้ 6 ชม. / คลุมเครือ (5xx, timeout) 15 นาที
     *   (คลุมเครือต้อง cache สั้นๆ ด้วย — ไม่งั้นช่วง provider ล่ม จะยิง probe ทุก page load)
     */
    protected static function poolImageCapable(string $slug): bool
    {
        $provider = self::POOL_IMAGE_GROUPS[$slug]['provider'] ?? null;
        if ($provider === null || ! AiApiKey::forProvider($provider)->available()->exists()) {
            return false;
        }

        $state = self::readPoolCapabilityState($slug);
        if ($state !== null && ($state['expires_at'] ?? 0) > time()) {
            return (bool) ($state['capable'] ?? false);
        }

        $capable = $slug === 'pool-gemini'
            ? self::probeGeminiImageQuota()
            : self::probeOpenAiImageAccess();

        $ttlSeconds = $capable === true
            ? 3 * 86400
            : ($capable === false ? 6 * 3600 : 900); // null = คลุมเครือ → เช็คใหม่ใน 15 นาที

        self::writePoolCapabilityState($slug, (bool) $capable, $ttlSeconds);

        return (bool) $capable;
    }

    /**
     * อ่านผล probe จากไฟล์ — คืน null ถ้าไม่มี/อ่านไม่ได้/format ผิด
     *
     * @return array{capable: bool, expires_at: int}|null
     */
    protected static function readPoolCapabilityState(string $slug): ?array
    {
        try {
            if (! Storage::disk('local')->exists(self::POOL_CAP_FILE)) {
                return null;
            }
            $data = json_decode((string) Storage::disk('local')->get(self::POOL_CAP_FILE), true);
            $state = $data[$slug] ?? null;

            return (is_array($state) && isset($state['capable'], $state['expires_at'])) ? $state : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * บันทึกผล probe ลงไฟล์ (เก็บรวมทุก slug ในไฟล์เดียว)
     */
    protected static function writePoolCapabilityState(string $slug, bool $capable, int $ttlSeconds): void
    {
        try {
            $data = [];
            if (Storage::disk('local')->exists(self::POOL_CAP_FILE)) {
                $decoded = json_decode((string) Storage::disk('local')->get(self::POOL_CAP_FILE), true);
                $data = is_array($decoded) ? $decoded : [];
            }
            $data[$slug] = [
                'capable' => $capable,
                'expires_at' => time() + $ttlSeconds,
                'checked_at' => time(),
            ];
            Storage::disk('local')->put(self::POOL_CAP_FILE, json_encode($data, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            // เขียนไฟล์ไม่ได้ — probe รอบหน้าใหม่ ไม่ใช่เหตุให้หน้า admin พัง
        }
    }

    /**
     * Probe: คีย์ Gemini ตัวไหนเจนภาพได้จริงไหม (ไล่สูงสุด 3 คีย์ตาม priority)
     *
     * ใช้ shape เดียวกับ request จริงตอนโพส (responseModalities + seed) — probe ผ่าน
     * แล้วโพสจริงต้องผ่านด้วย ไม่ใช่ probe หลอก
     *
     * @return bool|null null = คลุมเครือ (network/5xx) — caller cache สั้นๆ เท่านั้น
     */
    protected static function probeGeminiImageQuota(): ?bool
    {
        $keys = AiApiKey::forProvider('gemini')->available()->orderByDesc('priority')->limit(3)->get();
        $probeModel = (string) array_key_first(self::POOL_IMAGE_MODELS['pool-gemini']);
        $sawDefinitiveNo = false;

        foreach ($keys as $key) {
            try {
                $baseUrl = rtrim($key->resolveBaseUrl() ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
                $response = Http::timeout(30)
                    ->withHeaders(['x-goog-api-key' => $key->api_key])
                    ->post("{$baseUrl}/models/{$probeModel}:generateContent", [
                        'contents' => [['parts' => [['text' => 'tiny golden lotus, warm light']]]],
                        'generationConfig' => [
                            'responseModalities' => ['TEXT', 'IMAGE'],
                            'seed' => 12345,
                        ],
                    ]);

                foreach ($response->json('candidates.0.content.parts', []) as $part) {
                    if (! empty($part['inlineData']['data'])) {
                        return true;
                    }
                }
                if (in_array($response->status(), [400, 403, 429], true)) {
                    $sawDefinitiveNo = true; // ตอบชัดว่าใช้ไม่ได้ (เช่น free tier quota=0)
                }
                // 5xx/overloaded → ไม่นับทั้งสองทาง (คลุมเครือ)
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $sawDefinitiveNo ? false : null;
    }

    /**
     * Probe: บัญชี OpenAI เข้าถึง image API ไหม — GET /v1/models (ฟรี ไม่เจนจริง)
     *
     * @return bool|null null = คลุมเครือ — caller ห้าม cache
     */
    protected static function probeOpenAiImageAccess(): ?bool
    {
        $keys = AiApiKey::forProvider('openai')->available()->orderByDesc('priority')->limit(3)->get();
        $sawDefinitiveNo = false;

        foreach ($keys as $key) {
            try {
                $baseUrl = rtrim($key->resolveBaseUrl() ?: 'https://api.openai.com/v1', '/');
                $response = Http::timeout(15)->withToken($key->api_key)->get("{$baseUrl}/models");

                if ($response->successful()) {
                    $ids = collect($response->json('data', []))->pluck('id');
                    if ($ids->contains(fn ($id) => str_starts_with((string) $id, 'gpt-image-'))) {
                        return true;
                    }
                    $sawDefinitiveNo = true;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $sawDefinitiveNo ? false : null;
    }

    /**
     * 🏊 เจนภาพด้วยคีย์จาก AI Key Pool (Gemini Nano Banana / OpenAI gpt-image)
     *
     * ไล่ลองสูงสุด 3 คีย์ตาม priority — คีย์ free tier ของ Gemini จะ 429 ทันที (ไม่เสียเวลา)
     * ล้มเหลวทุกกรณีคืน null → caller fallback chain ต่อ โพสไม่มีวันตายเพราะภาพ
     *
     * @param  string  $slug  'pool-gemini' | 'pool-openai'
     */
    protected function generateImageWithPoolKey(
        FortuneContentPost $post,
        string $slug,
        string $model,
        string $prompt
    ): ?string {
        $models = self::POOL_IMAGE_MODELS[$slug] ?? [];
        $provider = self::POOL_IMAGE_GROUPS[$slug]['provider'] ?? null;
        if ($provider === null || $models === []) {
            return null;
        }

        // 🛡️ whitelist model — ค่าแปลกปลอม (POST ตรง/มือแก้ DB) บังคับตัวแรกของลิสต์
        if (! isset($models[$model])) {
            $model = (string) array_key_first($models);
        }

        $keys = AiApiKey::forProvider($provider)->available()->orderByDesc('priority')->limit(3)->get();
        if ($keys->isEmpty()) {
            Log::warning('ContentCampaign: pool ไม่มีคีย์พร้อมใช้สำหรับเจนภาพ', [
                'provider' => $provider,
                'model' => $model,
            ]);

            return null;
        }

        foreach ($keys as $key) {
            try {
                $b64 = $provider === 'gemini'
                    ? $this->callGeminiImageApi($key, $model, $prompt, $this->imageSeed($post))
                    : $this->callOpenAiImageApi($key, $model, $prompt);

                if ($b64) {
                    return $this->storeImageFromBase64($post, $b64);
                }
            } catch (Exception $e) {
                Log::warning('ContentCampaign: pool image exception — ลองคีย์ถัดไป', [
                    'slug' => $slug,
                    'model' => $model,
                    'key_id' => $key->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Gemini image (generateContent + responseModalities IMAGE) → base64 PNG
     *
     * ใช้ header x-goog-api-key — ไม่ฝังคีย์ใน URL (กันหลุดใน log/exception message)
     */
    protected function callGeminiImageApi(AiApiKey $key, string $model, string $prompt, int $seed): ?string
    {
        $baseUrl = rtrim($key->resolveBaseUrl() ?: 'https://generativelanguage.googleapis.com/v1beta', '/');

        $response = Http::timeout(90)
            ->withHeaders(['x-goog-api-key' => $key->api_key])
            ->post("{$baseUrl}/models/{$model}:generateContent", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    // TEXT+IMAGE = ชุดที่โมเดลภาพทุกรุ่นรองรับ (IMAGE เดี่ยวบางรุ่น reject)
                    'responseModalities' => ['TEXT', 'IMAGE'],
                    'seed' => $seed,
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('ContentCampaign: Gemini image API ตอบไม่สำเร็จ', [
                'model' => $model,
                'status' => $response->status(),
                'error' => $response->json('error.message', 'unknown'),
            ]);

            return null;
        }

        foreach ($response->json('candidates.0.content.parts', []) as $part) {
            if (! empty($part['inlineData']['data'])) {
                return $part['inlineData']['data'];
            }
        }

        return null;
    }

    /**
     * OpenAI gpt-image (images/generations) → base64 PNG
     *
     * ⚠️ ห้ามส่ง response_format — ตระกูล gpt-image ไม่รับพารามิเตอร์นี้ (คืน b64 เป็น default)
     */
    protected function callOpenAiImageApi(AiApiKey $key, string $model, string $prompt): ?string
    {
        $baseUrl = rtrim($key->resolveBaseUrl() ?: 'https://api.openai.com/v1', '/');

        $response = Http::timeout(120)
            ->withToken($key->api_key)
            ->post("{$baseUrl}/images/generations", [
                'model' => $model,
                'prompt' => mb_substr($prompt, 0, 4000),
                'n' => 1,
                'size' => '1024x1024',
            ]);

        if (! $response->successful()) {
            Log::warning('ContentCampaign: OpenAI image API ตอบไม่สำเร็จ', [
                'model' => $model,
                'status' => $response->status(),
                'error' => $response->json('error.message', 'unknown'),
            ]);

            return null;
        }

        return $response->json('data.0.b64_json') ?: null;
    }

    /**
     * เซฟภาพ base64 ที่ path ของ campaign → คืน public URL
     */
    protected function storeImageFromBase64(FortuneContentPost $post, string $b64): ?string
    {
        $binary = base64_decode($b64, true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $relativePath = $this->savedImagePath($post);
        $absolutePath = $this->ensureDir($relativePath);
        file_put_contents($absolutePath, $binary);

        $post->update(['image_path' => $relativePath]);

        return asset('storage/'.$relativePath);
    }

    /**
     * ดึงภาพจาก URL ผลลัพธ์ของ provider มาเซฟที่ path ของ campaign
     *
     * Provider ส่วนใหญ่เซฟใน Storage::disk('public') แล้วคืน asset URL → copy จาก disk ตรง
     * บาง provider คืน URL ภายนอก → download ผ่าน HTTP
     */
    protected function storeImageFromUrl(FortuneContentPost $post, string $sourceUrl): ?string
    {
        $relativePath = $this->savedImagePath($post);
        $absolutePath = $this->ensureDir($relativePath);

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
            if (! $resp->successful() || empty($resp->body())) {
                return null;
            }
            file_put_contents($absolutePath, $resp->body());
        }

        $post->update(['image_path' => $relativePath]);

        return asset('storage/'.$relativePath);
    }

    /**
     * Pollinations — เรียก URL ตรง ฟรี ไม่ต้องมีคีย์ (fallback สุดท้าย + เลือกเป็นโมเดลหลักได้)
     *
     * @param  string  $model  ต้องอยู่ใน POLLINATIONS_MODELS — ค่าแปลกปลอมถูกบังคับกลับเป็น turbo
     */
    protected function generateImageWithPollinations(FortuneContentPost $post, string $prompt, string $model = 'turbo'): ?string
    {
        if (! in_array($model, self::POLLINATIONS_MODELS, true)) {
            $model = 'turbo';
        }

        $encoded = rawurlencode(mb_substr($prompt, 0, 800));
        $seed = $this->imageSeed($post);
        $url = "https://image.pollinations.ai/prompt/{$encoded}"
            ."?width=1080&height=1080&seed={$seed}&nologo=true&model={$model}&enhance=true";

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
     * @return array{0: string|null, 1: string|null} [pageId, pageToken]
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
