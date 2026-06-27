<?php

namespace App\Services;

use App\Models\AiGenProvider;
use App\Models\FortuneMysticPost;
use App\Models\FortuneMysticTopic;
use App\Models\FortuneTellingSetting;
use App\Services\AiGen\CloudflareAiProvider;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * บริการสร้างและโพสคอนเทนต์สายมูอัตโนมัติ
 *
 * Flow ของแต่ละ slot:
 * 1. สุ่ม topic (least-recently-used) + sub_topic
 * 2. WebSearchService.search() — Tavily/Brave/DDG หาแหล่งอ้างอิง
 * 3. Gemini (ผ่าน FortuneAIService — provider หลัก, fallback Pool อัตโนมัติ) เขียนใหม่เป็นบทความสไตล์แม่หมอจันทรา
 * 4. AI image gen (Cloudflare AI → Pollinations → GD fallback) ตาม image_keywords
 * 5. POST /me/photos → Facebook Page
 * 6. บันทึก fb_post_id + sources + posted_at
 *
 * Idempotent — unique (post_date, slot_hour) — กันโพสซ้ำใน slot เดียว
 */
class MysticContentAutoPostService
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
     * สร้างและโพสในรอบเดียว — ใช้จาก scheduler/command
     *
     * @param  int  $slotHour  ชั่วโมงของ slot (0-23) เช่น 8, 20
     * @param  Carbon|null  $date  วันที่โพส (default: today)
     * @param  bool  $force  true = ลบโพสเก่า (FB + DB) ก่อน republish
     */
    public function generateAndPublish(int $slotHour, ?Carbon $date = null, bool $force = false): array
    {
        $date = $date ?? now('Asia/Bangkok');

        // Idempotent — เช็ค unique (post_date, slot_hour)
        $existing = FortuneMysticPost::where('post_date', $date->toDateString())
            ->where('slot_hour', $slotHour)
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

        if ($existing && $existing->status === FortuneMysticPost::STATUS_POSTED) {
            return [
                'success' => true,
                'message' => 'โพสแล้ว ข้าม',
                'post_id' => $existing->fb_post_id,
            ];
        }

        $post = $existing ?: FortuneMysticPost::create([
            'post_date' => $date->toDateString(),
            'slot_hour' => $slotHour,
            'status' => FortuneMysticPost::STATUS_PENDING,
        ]);

        try {
            // 1. สุ่ม topic + sub_topic
            $topic = FortuneMysticTopic::pickNext();
            if (! $topic) {
                throw new Exception('ไม่มีหมวดคอนเทนต์สายมู — รัน FortuneMysticTopicSeeder ก่อน');
            }

            $subTopic = $topic->pickSubTopic() ?: $topic->name_th;
            $hashtagCount = (int) ($this->settings->mystic_content_hashtag_count ?? 6);
            $hashtags = $topic->pickHashtags($hashtagCount);

            $post->update([
                'topic_id' => $topic->id,
                'sub_topic' => $subTopic,
                'hashtags' => $hashtags,
            ]);

            // 2. Web search หาแหล่งอ้างอิง
            $post->update(['status' => FortuneMysticPost::STATUS_RESEARCHING]);
            $searchQuery = $topic->pickSearchQuery($subTopic);
            $searchResult = $this->webSearch->search($searchQuery, 5);

            $post->update([
                'sources' => $searchResult['results'],
                'search_provider' => $searchResult['provider'],
                'researched_at' => now(),
            ]);

            // 3. AI rewrite เป็นบทความ + caption
            $post->update(['status' => FortuneMysticPost::STATUS_GENERATING]);
            $caption = $this->generateCaption($post, $topic, $subTopic, $searchResult['results'], $hashtags);
            $post->update([
                'caption' => $caption,
                'generated_at' => now(),
            ]);

            // 4. AI image gen
            $this->generateImage($post, $topic, $subTopic);

            // 5. โพส FB
            $post->update(['status' => FortuneMysticPost::STATUS_PUBLISHING]);
            $result = $this->publishToFacebook($post);

            $post->markPosted($result['post_id'] ?? null, $result['post_url'] ?? null);

            // อัพเดต topic usage tracking — รอบหน้าจะหมุนไปหมวดอื่น
            $topic->markUsed();

            Log::info('MysticAutoPost: โพสสำเร็จ', [
                'post_id' => $post->id,
                'fb_post_id' => $post->fb_post_id,
                'topic' => $topic->slug,
                'sub_topic' => $subTopic,
                'slot' => "{$date->toDateString()} {$slotHour}:00",
                'search_provider' => $searchResult['provider'],
                'sources_count' => count($searchResult['results']),
            ]);

            return [
                'success' => true,
                'message' => 'โพสสำเร็จ',
                'post_id' => $post->fb_post_id,
                'url' => $post->fb_post_url,
                'topic' => $topic->name_th,
                'sub_topic' => $subTopic,
            ];
        } catch (Exception $e) {
            $post->markFailed($e->getMessage());

            Log::error('MysticAutoPost: ล้มเหลว', [
                'post_id' => $post->id,
                'slot_hour' => $slotHour,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 3. AI rewrite — Gemini (provider หลัก) เขียนเป็นบทความ "สไตล์แม่หมอจันทรา"
     *
     * โดยใช้ search results เป็น reference (ไม่ก็อปคำต่อคำ)
     */
    protected function generateCaption(
        FortuneMysticPost $post,
        FortuneMysticTopic $topic,
        string $subTopic,
        array $sources,
        array $hashtags
    ): string {
        $minLen = (int) ($this->settings->mystic_content_caption_min ?? 400);
        $maxLen = (int) ($this->settings->mystic_content_caption_max ?? 700);
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';

        // เตรียม reference content จาก sources (max 3 บทความ ≈ 2000 ตัวอักษร/อัน)
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
            $referenceText = '(ไม่มีข้อมูลอ้างอิง — ใช้ความรู้ทั่วไปและประสบการณ์ของแม่หมอจันทรา)';
        }

        $hashtagLine = implode(' ', $hashtags);

        $prompt = "คุณคือ \"{$brandName}\" หมอดูสายมูที่มีผู้ติดตามจำนวนมากบน Facebook\n\n"
            ."📌 หมวด: {$topic->name_th} {$topic->emoji}\n"
            ."📌 หัวข้อวันนี้: {$subTopic}\n\n"
            ."📚 ข้อมูลอ้างอิงจากเว็บที่น่าเชื่อถือ:\n{$referenceText}\n"
            ."──────────────────────\n"
            ."✍️ ภารกิจ: เขียนโพสต์ Facebook ภาษาไทย ความยาว {$minLen}-{$maxLen} ตัวอักษร\n\n"
            ."กฎเข้ม (ห้ามฝ่าฝืน):\n"
            ."1. ห้ามก็อปคำต่อคำจากแหล่งอ้างอิง — ต้องเรียบเรียงใหม่ทั้งหมดด้วยภาษาตัวเอง\n"
            ."2. ขึ้นต้นด้วยประโยคติดหู ดึงคนหยุด scroll (เช่น \"รู้ไหมว่า...\", \"คุณเคยสงสัยไหม...\")\n"
            ."3. มีสาระจริง ให้ความรู้ — ไม่ใช่แค่อ้อมๆ\n"
            ."4. ใช้ภาษาเข้าใจง่าย เหมือนเล่าให้เพื่อนฟัง ไม่ใช่บทความวิชาการ\n"
            ."5. มี emoji 3-5 ตัว (ไม่เยอะเกินไป)\n"
            ."6. ปิดท้ายด้วย CTA แบบไม่กดดัน เช่น \"ใครเคยลองบ้าง คอมเมนต์เล่าให้ฟังหน่อย\" หรือ \"ทักแชทปรึกษาแม่หมอได้นะ\"\n"
            ."7. ห้ามมีลิงก์ ห้ามมีคำว่า \"กดไลค์\" \"กดแชร์\" (Facebook ลด reach)\n"
            ."8. ห้ามใช้คำเชิญชวนรุนแรงเช่น \"ห้ามพลาด\" \"คลิกเลย\" — เน้นความรู้สึกอบอุ่น\n"
            ."9. ความยาวต้องอยู่ในช่วง {$minLen}-{$maxLen} ตัวอักษร (ไม่รวม hashtag)\n"
            ."10. **ห้าม**ใส่ hashtag ในเนื้อหา — ระบบจะเพิ่มให้ทีหลัง\n"
            ."11. **ห้าม**ใส่ markdown (** หรือ ##) ใช้ plain text เท่านั้น\n\n"
            .'เริ่มเขียนเลย:';

        try {
            // 🌟 ใช้ Gemini เป็น provider หลักสำหรับคอนเทนต์สายมู
            //    (ถ้าไม่มี Gemini key พร้อมใช้ใน Pool → fallback ไป provider อื่นอัตโนมัติ)
            $aiService = new FortuneAIService($this->settings, preferredProvider: 'gemini');
            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: null,
                readingType: 'basic',
                birthDate: null,
                userContext: "mystic_content:{$topic->slug}",
            );

            $body = trim($result['response'] ?? '');

            // ลบ hashtag ที่ AI อาจใส่มาเอง (กันซ้ำกับ hashtag ที่ระบบเติมท้าย)
            //   (?? $body กัน preg_replace คืน null ตอน PCRE error → ไม่ให้ TypeError ปลายทาง)
            $body = preg_replace('/#[^\s#]+/u', '', $body) ?? $body;

            // ลบ markdown ที่ AI อาจหลุดมา (**, ##) — แต่ "คงบรรทัดใหม่" ไว้เพื่อให้อ่านง่าย
            $body = preg_replace('/\*{1,3}|#{1,6}/u', '', $body) ?? $body;

            // ✅ Normalize เฉพาะช่องว่าง "แนวนอน" — เก็บ \n ไว้รักษาย่อหน้า/อิโมจิขึ้นบรรทัด
            //    (เดิม preg_replace('/\s+/u', ' ') ยุบ \n หมด → กลายเป็นพืดเดียว คนอ่านงง)
            $body = $this->normalizeWhitespace($body);

            if ($body === '' || mb_strlen($body) < 100) {
                throw new Exception('AI body สั้นเกินไป ('.mb_strlen($body).' ตัวอักษร)');
            }

            // ✅ ตัด "เฉพาะตอนยาวเกินเพดานจริงๆ" และตัดที่จุดจบประโยค/ย่อหน้าเท่านั้น
            //    กันอาการข้อความขาดกลางประโยค (เดิม mb_substr(maxLen+50).'...' ตัดดิบกลางคำ)
            $body = $this->trimToSentenceBoundary($body, $maxLen);

            return $body."\n\n".$hashtagLine;
        } catch (Exception $e) {
            Log::warning('MysticAutoPost: AI rewrite ล้มเหลว → fallback template', [
                'error' => $e->getMessage(),
            ]);

            // Fallback template (ใช้ snippets ตรงๆ ผสม)
            return $this->fallbackTemplate($topic, $subTopic, $sources, $hashtagLine);
        }
    }

    /**
     * Fallback template — ใช้เมื่อ AI rewrite ล้มเหลว
     */
    protected function fallbackTemplate(
        FortuneMysticTopic $topic,
        string $subTopic,
        array $sources,
        string $hashtagLine
    ): string {
        $intro = "{$topic->emoji} {$subTopic}\n\n";
        $body = '';

        foreach (array_slice($sources, 0, 2) as $src) {
            $snippet = trim($src['snippet'] ?? '');
            if ($snippet !== '') {
                $body .= '✨ '.mb_substr($snippet, 0, 250)."\n\n";
            }
        }

        if ($body === '') {
            $body = "วันนี้มาพูดถึงเรื่อง {$subTopic} กัน — เป็นเรื่องที่หลายคนสงสัยและต้องการคำตอบ "
                ."หากใครมีประสบการณ์หรือคำถาม คอมเมนต์มาได้เลย แม่หมอยินดีตอบทุกท่าน 🙏\n\n";
        }

        $cta = "💬 ใครเคยมีประสบการณ์แบบนี้ คอมเมนต์เล่าได้นะ — แม่หมอจันทราคอยอยู่\n\n";

        return $intro.$body.$cta.$hashtagLine;
    }

    /**
     * Normalize ช่องว่างโดย "รักษาการขึ้นบรรทัดใหม่" — เพื่อให้โพสเป็นย่อหน้า อ่านง่าย
     *
     * - ยุบ space/tab ซ้ำในบรรทัดเดียวกัน (ไม่ยุ่งกับ \n)
     * - ตัดช่องว่างหัว-ท้ายของแต่ละบรรทัด
     * - ยุบบรรทัดว่างเกิน 2 → เหลือ 1 บรรทัดว่าง (คั่นย่อหน้าพอดี ไม่โหว่)
     *
     * @param  string  $text  เนื้อหาดิบจาก AI
     * @return string  เนื้อหาที่จัดช่องว่างแล้ว แต่ยังคงย่อหน้าไว้ครบ
     */
    protected function normalizeWhitespace(string $text): string
    {
        // รวมรูปแบบ line ending ให้เป็น \n อย่างเดียว
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // ยุบ space/tab/non-breaking space ที่ติดกันในบรรทัด (ไม่แตะ \n)
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;

        // ตัดช่องว่างหัว-ท้ายรอบ ๆ การขึ้นบรรทัดใหม่
        $text = preg_replace('/[ \t]*\n[ \t]*/u', "\n", $text) ?? $text;

        // บรรทัดว่างเกิน 2 → เหลือ 2 (\n\n = 1 บรรทัดว่างคั่นย่อหน้า)
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * ตัดข้อความให้จบ "ที่ประโยค/ย่อหน้าสมบูรณ์" เมื่อยาวเกินเพดาน — ไม่ตัดกลางประโยค
     *
     * เป้าหมายหลัก: "ข้อความออกครบ"
     * - ถ้ายังอยู่ในเพดาน (max + เผื่อ) → คืนทั้งหมด ไม่ตัดอะไรเลย
     * - ถ้าเกินจริง → ถอยหลังหาจุดจบที่ใกล้สุด ลำดับ: ย่อหน้า > บรรทัด > .!?ฯ > เว้นวรรค
     * - "ไม่เติม ..." เพราะจุดไข่ปลาทำให้ดูเหมือนข้อความขาด
     *
     * @param  string  $text  เนื้อหา (normalize แล้ว)
     * @param  int  $maxLen  ความยาวเป้าหมายสูงสุด (ตัวอักษร, ไม่รวม hashtag)
     */
    protected function trimToSentenceBoundary(string $text, int $maxLen): string
    {
        // เพดานจริง — เผื่อให้ยาวกว่าเป้าได้พอควร (Facebook รับข้อความยาวอยู่แล้ว)
        $ceiling = max($maxLen + 250, (int) round($maxLen * 1.4));
        if (mb_strlen($text) <= $ceiling) {
            return $text;
        }

        $head = mb_substr($text, 0, $ceiling);
        $minKeep = (int) round($maxLen * 0.5);

        // 1) จบที่ "ย่อหน้า/บรรทัดใหม่" — จุดตัดธรรมชาติที่สุดของโพส
        foreach (["\n\n", "\n"] as $brk) {
            $pos = mb_strrpos($head, $brk);
            if ($pos !== false && $pos >= $minKeep) {
                return rtrim(mb_substr($head, 0, $pos));
            }
        }

        // 2) จบที่เครื่องหมายจบประโยค (ตามด้วยช่องว่าง = จุดจบจริง)
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

        // 3) สุดท้าย: ตัดที่เว้นวรรคคำสุดท้าย (อย่างน้อยไม่ตัดกลางคำ) — ไม่เติม ...
        $pos = mb_strrpos($head, ' ');
        if ($pos !== false && $pos >= $minKeep) {
            return rtrim(mb_substr($head, 0, $pos));
        }

        return rtrim($head);
    }

    /**
     * 4. สร้างรูปประกอบ — ใช้ pattern เดียวกับ DailyHoroscopeAutoPostService
     *
     * Cloudflare AI → Pollinations → GD fallback
     */
    protected function generateImage(
        FortuneMysticPost $post,
        FortuneMysticTopic $topic,
        string $subTopic
    ): void {
        $imagePromptSeed = $topic->pickImagePromptSeed();

        try {
            // Cloudflare AI (primary)
            $url = $this->generateImageWithCloudflare($post, $topic, $imagePromptSeed);
            if ($url) {
                $post->update(['image_url' => $url, 'image_provider' => 'cloudflare-ai']);

                return;
            }

            // Pollinations (fallback)
            $url = $this->generateImageWithPollinations($post, $topic, $imagePromptSeed);
            if ($url) {
                $post->update(['image_url' => $url, 'image_provider' => 'pollinations']);

                return;
            }
        } catch (Exception $e) {
            Log::warning('MysticAutoPost: AI image fail — โพสแบบ text-only', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function generateImageWithCloudflare(
        FortuneMysticPost $post,
        FortuneMysticTopic $topic,
        string $imagePromptSeed
    ): ?string {
        try {
            $providerModel = AiGenProvider::where('slug', 'cloudflare-ai')->first();
            if (! $providerModel) {
                return null;
            }

            $cfProvider = new CloudflareAiProvider($providerModel);
            if (! $cfProvider->isConfigured()) {
                return null;
            }

            $prompt = "{$imagePromptSeed}, "
                .'mystical thai cultural setting, '
                .'professional photography, soft volumetric lighting, '
                .'rich saturated colors, photorealistic, ultra sharp, 8k detail, '
                .'magazine cover quality, '
                .'no text, no watermark, no people faces';

            // seed = topic_id + slot_hour + date — รัน publish ซ้ำได้รูปเดียวกัน
            $seed = ((int) $topic->id * 10000) + ((int) $post->slot_hour * 100)
                + ((int) $post->post_date->format('Ymd') % 100);

            $result = $cfProvider->generateImage($prompt, [
                'model' => 'flux-1-schnell',
                'size' => '1024x1024',
                'steps' => 8,
                'seed' => $seed,
            ]);

            if (! ($result['success'] ?? false) || empty($result['images'][0]['url'])) {
                return null;
            }

            $sourceUrl = $result['images'][0]['url'];
            $relativePath = $this->savedImagePath($post);
            $absolutePath = $this->ensureDir($relativePath);

            // Cloudflare provider เซฟไว้ใน Storage::disk('public') แล้ว
            $sourcePath = parse_url($sourceUrl, PHP_URL_PATH);
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
            Log::warning('MysticAutoPost: Cloudflare AI exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function generateImageWithPollinations(
        FortuneMysticPost $post,
        FortuneMysticTopic $topic,
        string $imagePromptSeed
    ): ?string {
        $prompt = "{$imagePromptSeed}, "
            .'mystical thai cultural setting, '
            .'professional photography, soft volumetric lighting, '
            .'rich saturated colors, photorealistic, ultra sharp, 8k detail, '
            .'magazine cover quality, '
            .'no text, no watermark';

        $encoded = rawurlencode(mb_substr($prompt, 0, 800));
        $seed = ((int) $topic->id * 10000) + ((int) $post->slot_hour * 100)
            + ((int) $post->post_date->format('Ymd') % 100);
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
     * Path สำหรับเซฟรูป → fortune-mystic/{date}/slot-{hour}.jpg
     */
    protected function savedImagePath(FortuneMysticPost $post): string
    {
        return sprintf(
            'fortune-mystic/%s/slot-%02d.jpg',
            $post->post_date->format('Y-m-d'),
            $post->slot_hour
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
     * 5. โพสไป Facebook Page (graph.facebook.com)
     */
    protected function publishToFacebook(FortuneMysticPost $post): array
    {
        $fresh = FortuneTellingSetting::query()->first();
        if (! $fresh) {
            throw new Exception('ไม่พบ FortuneTellingSetting ใน DB');
        }

        $pageId = $fresh->facebook_page_id;
        $pageToken = $fresh->facebook_page_token
            ?? $fresh->facebook_page_access_token
            ?? $fresh->getRawOriginal('facebook_page_token');

        if (empty($pageId) || empty($pageToken)) {
            throw new Exception('ไม่พบ Facebook Page ID หรือ Page Access Token ใน settings');
        }

        $caption = $post->caption ?: 'คอนเทนต์สายมูประจำวัน';

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
            $fresh = FortuneTellingSetting::query()->first();
            if (! $fresh) {
                return false;
            }

            $pageToken = $fresh->facebook_page_token
                ?? $fresh->getRawOriginal('facebook_page_token');

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
