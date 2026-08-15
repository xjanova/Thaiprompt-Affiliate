<?php

namespace App\Services;

use App\Models\AiGenProvider;
use App\Models\FortuneDailyHoroscopePost;
use App\Models\FortuneTellingSetting;
use App\Models\TarotCard;
use App\Services\AiGen\CloudflareAiProvider;
use App\Services\Fortune\FacebookContentPolicy;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * บริการสร้างและโพสดวงประจำวันอัตโนมัติ (รายเจ้าของวันเกิด)
 *
 * Flow ของแต่ละชั่วโมง (01:00–07:00):
 * 1. สุ่มไพ่ทาโรต์ของวันนั้น
 * 2. สร้าง caption สั้นๆ ตรงประเด็น (เน้นจุดเด่นของวันเกิด)
 * 3. สร้างรูปประกอบ (composite ไพ่ + overlay text)
 * 4. POST /me/feed → Facebook Page
 * 5. บันทึก fb_post_id + posted_at
 *
 * Idempotent — มี unique constraint (post_date, day_of_birth)
 */
class DailyHoroscopeAutoPostService
{
    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * สร้างและโพสในรอบเดียว — ใช้จาก scheduler
     *
     * @param  int  $dayOfBirth  1=จันทร์ ... 7=อาทิตย์
     * @param  Carbon|null  $date  วันที่ดวง (default: today)
     * @param  bool  $force  true = ลบโพสเก่า (FB + DB) ก่อน republish
     */
    public function generateAndPublish(int $dayOfBirth, ?Carbon $date = null, bool $force = false): array
    {
        $date = $date ?? now();

        // ตรวจ duplicate (idempotent)
        // 🏬 (2026-08-15) ต้องถามเฉพาะสาขาที่กำลังทำงานอยู่
        //    เดิมถามรวมทุกสาขา → สาขาแรกโพสเสร็จ สาขาที่เหลือเห็น "โพสแล้ว" แล้วข้ามเงียบ
        $existing = FortuneDailyHoroscopePost::forCurrentFortunePage()
            ->where('post_date', $date->toDateString())
            ->where('day_of_birth', $dayOfBirth)
            ->first();

        // --force: ลบโพสเก่าบน FB + DB ก่อน republish
        if ($force && $existing) {
            if ($existing->fb_post_id) {
                $this->deleteFromFacebook($existing->fb_post_id);
            }

            // ลบไฟล์ภาพเก่าด้วย (กันรกใน storage)
            if ($existing->image_path) {
                Storage::disk('public')->delete($existing->image_path);
            }

            Log::info('DailyHoroscopeAutoPost: --force ลบโพสเก่า', [
                'post_id' => $existing->id,
                'fb_post_id' => $existing->fb_post_id,
                'day_of_birth' => $dayOfBirth,
                'date' => $date->toDateString(),
            ]);

            $existing->delete();
            $existing = null;
        }

        if ($existing && $existing->status === FortuneDailyHoroscopePost::STATUS_POSTED) {
            return [
                'success' => true,
                'message' => 'โพสแล้ว ข้าม',
                'post_id' => $existing->fb_post_id,
            ];
        }

        $post = $existing ?: FortuneDailyHoroscopePost::create([
            'post_date' => $date->toDateString(),
            'day_of_birth' => $dayOfBirth,
            'status' => FortuneDailyHoroscopePost::STATUS_PENDING,
        ]);

        try {
            // 1. สุ่มไพ่ + AI caption
            $post->update(['status' => FortuneDailyHoroscopePost::STATUS_GENERATING]);
            $this->generateContent($post);

            // 2. สร้างรูป
            $this->generateImage($post);

            // 3. โพส FB
            $post->update(['status' => FortuneDailyHoroscopePost::STATUS_PUBLISHING]);
            $result = $this->publishToFacebook($post);

            $post->markPosted($result['post_id'] ?? null, $result['post_url'] ?? null);

            Log::info('DailyHoroscopeAutoPost: โพสสำเร็จ', [
                'post_id' => $post->id,
                'fb_post_id' => $post->fb_post_id,
                'day_of_birth' => $dayOfBirth,
                'date' => $date->toDateString(),
            ]);

            return [
                'success' => true,
                'message' => 'โพสสำเร็จ',
                'post_id' => $post->fb_post_id,
                'url' => $post->fb_post_url,
            ];
        } catch (Exception $e) {
            $post->markFailed($e->getMessage());

            Log::error('DailyHoroscopeAutoPost: ล้มเหลว', [
                'post_id' => $post->id,
                'day_of_birth' => $dayOfBirth,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 1. สุ่มไพ่ทาโรต์ + สร้าง caption สั้นๆ
     */
    protected function generateContent(FortuneDailyHoroscopePost $post): void
    {
        // สุ่มไพ่ active 1 ใบ
        $card = TarotCard::where('is_active', true)->inRandomOrder()->first();
        if (! $card) {
            throw new Exception('ไม่พบไพ่ทาโรต์ในระบบ — กรุณา seed tarot_cards table');
        }

        // สุ่มกลับด้าน 30% (รักษาสมดุลดวงดี/แย่)
        $isReversed = (mt_rand(1, 100) <= 30);

        // (ลบ $dayName/$dayEmoji ที่ประกาศแล้วไม่ได้ใช้ออก — generateCaption หาเองจาก $post)
        $cardMeaning = $isReversed
            ? ($card->reversed_meaning_th ?: $card->upright_meaning_th)
            : $card->upright_meaning_th;

        // สร้าง caption ผ่าน AI (สั้นๆ ตรงประเด็น)
        $caption = $this->generateCaption($post, $card, $isReversed, $cardMeaning);

        $post->update([
            'tarot_card_id' => $card->id,
            'is_reversed' => $isReversed,
            'caption' => $caption,
            'generated_at' => now(),
        ]);
    }

    /**
     * สร้าง caption สั้นกระชับด้วย AI (fallback ถ้า AI ล้มเหลว → template-based)
     */
    protected function generateCaption(
        FortuneDailyHoroscopePost $post,
        TarotCard $card,
        bool $isReversed,
        string $meaning
    ): string {
        $dayName = FortuneDailyHoroscopePost::DAY_NAMES[$post->day_of_birth];
        $dateStr = $post->post_date->locale('th')->translatedFormat('j F Y');
        $cardName = $card->name_th ?: $card->name_en;
        $position = $isReversed ? 'กลับด้าน' : 'ตั้งตรง';

        $prompt = "เขียน caption Facebook สั้นกระชับ 4-6 บรรทัด (ภาษาไทย) สำหรับโพสดวงประจำวัน:\n\n"
            ."วันนี้: {$dateStr}\n"
            ."สำหรับ: ผู้เกิดวัน{$dayName}\n"
            ."ไพ่ที่สุ่มได้: {$cardName} ({$position})\n"
            .'ความหมาย: '.mb_substr($meaning, 0, 200)."\n\n"
            ."กฎ:\n"
            ."1. ขึ้นต้นด้วย 'ดวงคนเกิดวัน{$dayName} | {$dateStr}'\n"
            ."2. บอกจุดเด่นชัดๆ 2-3 ข้อสั้น (การงาน/การเงิน/ความรัก)\n"
            ."3. ฟันธงเลย ไม่อ้อมค้อม ไม่ใช้คำว่า 'อาจจะ' 'น่าจะ'\n"
            ."4. ปิดท้ายด้วยข้อแนะนำ 1 ข้อ + CTA 'อยากดูเชิงลึกทักแชทมาเลย'\n"
            .'5. '.FacebookContentPolicy::noEmojiRule()
            .'6. ใส่แฮชแท็กได้ไม่เกิน '.FacebookContentPolicy::MAX_HASHTAGS." อัน\n"
            .'7. ห้ามยาวเกิน 350 ตัวอักษร';

        try {
            $aiService = new FortuneAIService($this->settings);
            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: null,
                readingType: 'basic',
                birthDate: null,
                userContext: "daily_horoscope:day{$post->day_of_birth}",
            );

            // 📘 ด่านกวาดท้ายทาง — โมเดลใส่อีโมจิ/แฮชแท็กเกินกลับมาแม้สั่งห้ามใน prompt แล้ว
            //    caption ตัวนี้ AI เขียนแฮชแท็กเอง (ไม่มีระบบเติมท้ายให้) จึงต้องตัดที่นี่
            $text = FacebookContentPolicy::clean(
                FacebookContentPolicy::capHashtagsInText((string) ($result['response'] ?? ''))
            );
            if ($text !== '' && mb_strlen($text) >= 50) {
                return mb_substr($text, 0, 600); // cap เผื่อ AI โอเวอร์
            }
        } catch (Exception $e) {
            Log::warning('DailyHoroscopeAutoPost: AI caption ล้มเหลว → fallback template', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback template (กรณี AI ล้มเหลว) — เขียนด้วยมือ จึงไม่มีอีโมจิตั้งแต่ต้นทาง
        // แฮชแท็ก 3 อันพอดีเพดาน (ดู FacebookContentPolicy::MAX_HASHTAGS)
        return FacebookContentPolicy::clean(
            "ดวงคนเกิดวัน{$dayName} | {$dateStr}\n"
            ."ไพ่วันนี้: {$cardName} ({$position})\n\n"
            .mb_substr($meaning, 0, 180)."\n\n"
            .'อยากดูเชิงลึกแม่นๆ ทักแชทมาคุยกับแม่หมอจันทราได้เลย'
        )."\n\n".'#ดวงประจำวัน #ไพ่ทาโรต์ #ดูดวงฟรี';
    }

    /**
     * 2. สร้างรูปประกอบ (composite ไพ่ + overlay text)
     *
     * ใช้ GD library — ไม่ต้องพึ่ง 3rd party
     * Output: storage/app/public/fortune-daily/{date}/{day}.jpg
     */
    protected function generateImage(FortuneDailyHoroscopePost $post): void
    {
        try {
            // 🥇 Primary: Cloudflare Workers AI (FLUX-1-schnell)
            //    เร็ว ~3-5s + เสถียร + ฟรี ~40 ภาพ/วัน + รวม Account ID + token เดียว
            $imageUrl = $this->generateImageWithCloudflare($post);

            // 🥈 Fallback 1: Pollinations.ai (ฟรี ไม่ต้อง API key)
            if (! $imageUrl) {
                Log::info('DailyHoroscopeAutoPost: Cloudflare AI ไม่พร้อม → ลอง Pollinations.ai', [
                    'post_id' => $post->id,
                ]);
                $imageUrl = $this->generateImageWithPollinations($post);
            }

            // 🥉 Fallback 2: GD library (เสมอเสมอ — สร้างจากข้อมูลในระบบ)
            if (! $imageUrl) {
                Log::info('DailyHoroscopeAutoPost: Pollinations.ai ไม่พร้อม → fallback GD', [
                    'post_id' => $post->id,
                ]);
                $imageUrl = $this->generateImageWithGD($post);
            }

            if ($imageUrl) {
                $post->update([
                    'image_url' => $imageUrl,
                    'status' => FortuneDailyHoroscopePost::STATUS_READY,
                ]);
            }
        } catch (Exception $e) {
            Log::warning('DailyHoroscopeAutoPost: สร้างรูปล้มเหลว — โพสแบบ text-only', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🚀 สร้างรูปด้วย Cloudflare Workers AI (FLUX-1-schnell) — Primary
     *
     * ใช้ provider ที่มีอยู่แล้วในระบบ (slug: cloudflare-ai)
     * fallback credential: DB config → config('services.cloudflare.*')
     *
     * Returns: URL ของรูป หรือ null ถ้าล้มเหลว
     */
    protected function generateImageWithCloudflare(FortuneDailyHoroscopePost $post): ?string
    {
        try {
            // ดึง provider จาก DB (ถ้าไม่มี slug 'cloudflare-ai' = ไม่ได้ seed → ข้าม)
            $providerModel = AiGenProvider::where('slug', 'cloudflare-ai')->first();

            if (! $providerModel) {
                Log::info('DailyHoroscopeAutoPost: ไม่พบ AiGenProvider slug=cloudflare-ai — รัน AiGenSeeder ก่อน');

                return null;
            }

            // เช็ค credential ก่อนเรียก (ประหยัด HTTP call ถ้า config ว่าง)
            $cfProvider = new CloudflareAiProvider($providerModel);
            if (! $cfProvider->isConfigured()) {
                Log::info('DailyHoroscopeAutoPost: Cloudflare AI ยังไม่ได้ตั้งค่า — เช็ค .env: CLOUDFLARE_API_TOKEN + CLOUDFLARE_ACCOUNT_ID', [
                    'post_id' => $post->id,
                ]);

                return null;
            }

            $card = $post->tarotCard;
            $dayName = FortuneDailyHoroscopePost::DAY_NAMES[$post->day_of_birth];

            // 🎨 ภาพ: scene เฉพาะวันเกิด — ไม่อิงไพ่ทาโรต์ (user feedback 2026-04)
            //    ใช้ "Thai mystical landscape + วัตถุมงคล/ธรรมชาติ" ที่สวยงามแบบภาพถ่าย
            //    หลีกเลี่ยง: tarot card, ตัวอักษร, complex composition
            $dayScenes = [
                1 => 'serene moonlit Thai temple courtyard at midnight, single white lotus floating in reflecting pool, soft silver mist, distant pagoda silhouette',
                2 => 'dramatic crimson sunset over Thai mountain range, ancient stone pavilion silhouette, warm fire glow, embers rising into sky',
                3 => 'emerald jungle dawn around hidden Thai forest temple, sunlight rays cutting through morning mist, stone naga statue covered in moss',
                4 => 'majestic golden hour over Thai grand temple, amber light through ornate windows, floating gold flecks, peaceful atmosphere',
                5 => 'turquoise ocean meets pink sunset sky, bouquet of fresh tropical flowers on wooden boat, peaceful fishing village in distance',
                6 => 'deep indigo starry night over Thai stupa, milky way galaxy visible, single bright shooting star, ethereal cosmic atmosphere',
                7 => 'radiant golden sunrise behind grand Thai pagoda, sun rays bursting through clouds, lotus pond glowing in foreground, hopeful warm light',
            ];
            $scene = $dayScenes[$post->day_of_birth] ?? 'mystical Thai temple at golden hour';

            // Prompt ใหม่ — pure landscape photography, no tarot, no abstract symbols
            //   เน้น: realistic photography style + Thai cultural element + ความงาม
            $prompt = "{$scene}, "
                .'professional landscape photography, '
                .'National Geographic style, '
                .'soft volumetric lighting, perfect composition, '
                .'rich saturated colors, photorealistic, ultra sharp, 8k detail, '
                .'wide cinematic shot, magazine cover quality, '
                .'no text, no people, no faces';

            // seed deterministic (วัน + วันเกิด) — เผื่อรัน publish ซ้ำ ภาพเดียวกัน
            $seed = ($post->day_of_birth * 1000) + (int) $post->post_date->format('Ymd');

            $result = $cfProvider->generateImage($prompt, [
                'model' => 'flux-1-schnell',
                'size' => '1024x1024',
                'steps' => 8, // เพิ่มจาก 4 → 8 steps คุณภาพดีขึ้นชัดเจน (ยังเร็วพอ ~4-6s)
                'seed' => $seed,
            ]);

            if (! ($result['success'] ?? false) || empty($result['images'][0]['url'])) {
                Log::warning('DailyHoroscopeAutoPost: Cloudflare AI fail', [
                    'post_id' => $post->id,
                    'error' => $result['error'] ?? 'unknown',
                ]);

                return null;
            }

            $sourceUrl = $result['images'][0]['url'];

            // คัดลอก image จาก ai-gen storage path → fortune-daily/{date}/day-{n}.jpg (ตาม convention)
            $relativePath = "fortune-daily/{$post->post_date->format('Y-m-d')}/day-{$post->day_of_birth}.jpg";
            $absolutePath = storage_path("app/public/{$relativePath}");
            $dir = dirname($absolutePath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // ดึงไฟล์จาก URL (relative URL ใน app เดียวกัน — ใช้ Storage หรือ readfile)
            // CloudflareAiProvider เซฟไว้ใน Storage::disk('public') → เราอ่านผ่าน path ตรง
            try {
                $sourcePath = parse_url($sourceUrl, PHP_URL_PATH);
                // ตัด /storage/ ออกเพื่อหา path ใน disk
                if (str_starts_with($sourcePath, '/storage/')) {
                    $diskRelative = substr($sourcePath, strlen('/storage/'));
                    if (Storage::disk('public')->exists($diskRelative)) {
                        $binary = Storage::disk('public')->get($diskRelative);
                        file_put_contents($absolutePath, $binary);
                    } else {
                        // fallback: ดาวน์โหลดผ่าน HTTP
                        $response = Http::timeout(30)->get($sourceUrl);
                        if (! $response->successful()) {
                            return null;
                        }
                        file_put_contents($absolutePath, $response->body());
                    }
                } else {
                    // absolute URL → HTTP fetch
                    $response = Http::timeout(30)->get($sourceUrl);
                    if (! $response->successful()) {
                        return null;
                    }
                    file_put_contents($absolutePath, $response->body());
                }
            } catch (Exception $copyEx) {
                Log::warning('DailyHoroscopeAutoPost: copy CF image fail', ['error' => $copyEx->getMessage()]);

                return null;
            }

            $post->update(['image_path' => $relativePath]);

            Log::info('DailyHoroscopeAutoPost: Cloudflare AI สำเร็จ', [
                'post_id' => $post->id,
                'day' => $post->day_of_birth,
                'card' => $card?->name_en ?: 'unknown',
                'size' => is_file($absolutePath) ? filesize($absolutePath) : 0,
            ]);

            return asset('storage/'.$relativePath);
        } catch (Exception $e) {
            Log::warning('DailyHoroscopeAutoPost: Cloudflare AI exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🎨 สร้างรูปด้วย Pollinations.ai (AI image — ฟรี ไม่ต้อง API key)
     *
     * Endpoint: https://image.pollinations.ai/prompt/{encoded_prompt}?{params}
     * Model: FLUX (default) — คุณภาพ photorealistic
     *
     * Returns: URL ของรูป (Pollinations จะ return รูปทันทีจาก URL)
     *          หรือ null ถ้าไม่สามารถ generate ได้
     */
    protected function generateImageWithPollinations(FortuneDailyHoroscopePost $post): ?string
    {
        $card = $post->tarotCard;
        $dayName = FortuneDailyHoroscopePost::DAY_NAMES[$post->day_of_birth];

        // 🎨 ภาพ: scene เฉพาะวันเกิด — ไม่อิงไพ่ทาโรต์ (เหมือน Cloudflare path)
        $dayScenes = [
            1 => 'serene moonlit Thai temple courtyard at midnight, single white lotus floating in reflecting pool, soft silver mist, distant pagoda silhouette',
            2 => 'dramatic crimson sunset over Thai mountain range, ancient stone pavilion silhouette, warm fire glow, embers rising into sky',
            3 => 'emerald jungle dawn around hidden Thai forest temple, sunlight rays cutting through morning mist, stone naga statue covered in moss',
            4 => 'majestic golden hour over Thai grand temple, amber light through ornate windows, floating gold flecks, peaceful atmosphere',
            5 => 'turquoise ocean meets pink sunset sky, bouquet of fresh tropical flowers on wooden boat, peaceful fishing village in distance',
            6 => 'deep indigo starry night over Thai stupa, milky way galaxy visible, single bright shooting star, ethereal cosmic atmosphere',
            7 => 'radiant golden sunrise behind grand Thai pagoda, sun rays bursting through clouds, lotus pond glowing in foreground, hopeful warm light',
        ];
        $scene = $dayScenes[$post->day_of_birth] ?? 'mystical Thai temple at golden hour';

        $prompt = "{$scene}, "
            .'professional landscape photography, '
            .'National Geographic style, '
            .'soft volumetric lighting, perfect composition, '
            .'rich saturated colors, photorealistic, ultra sharp, 8k detail, '
            .'wide cinematic shot, magazine cover quality, '
            .'no text, no people, no faces';

        // Encode prompt + params
        $encoded = rawurlencode(mb_substr($prompt, 0, 800));
        $seed = ($post->day_of_birth * 1000) + (int) $post->post_date->format('Ymd');
        // ใช้ model=turbo (Stable Diffusion turbo) เร็วกว่า flux ~6 เท่า
        // (5-10s/รูป แทน 30-60s) คุณภาพยังดีพอใช้สำหรับโพส FB
        $url = "https://image.pollinations.ai/prompt/{$encoded}"
            ."?width=1080&height=1080&seed={$seed}&nologo=true&model=turbo&enhance=true";

        // Download รูป + เก็บไว้บน server เอง (กัน Pollinations link หาย)
        try {
            $response = Http::timeout(45) // turbo เร็ว ~5-10s — ตั้ง 45s buffer
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);

            if (! $response->successful() || empty($response->body())) {
                Log::warning('Pollinations.ai: response ล้มเหลว', [
                    'status' => $response->status(),
                    'url' => $url,
                ]);

                return null;
            }

            $relativePath = "fortune-daily/{$post->post_date->format('Y-m-d')}/day-{$post->day_of_birth}.jpg";
            $absolutePath = storage_path("app/public/{$relativePath}");
            $dir = dirname($absolutePath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($absolutePath, $response->body());

            $post->update(['image_path' => $relativePath]);

            Log::info('DailyHoroscopeAutoPost: Pollinations.ai สร้างรูปสำเร็จ', [
                'post_id' => $post->id,
                'day' => $post->day_of_birth,
                'card' => $card?->name_en ?: 'unknown',
                'size' => strlen($response->body()),
            ]);

            return asset('storage/'.$relativePath);
        } catch (Exception $e) {
            Log::warning('Pollinations.ai: error', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);

            return null;
        }
    }

    /**
     * 🛠 Fallback — สร้างรูปด้วย GD (เก่า) ถ้า Pollinations ล้มเหลว
     */
    protected function generateImageWithGD(FortuneDailyHoroscopePost $post): ?string
    {
        try {
            $card = $post->tarotCard;
            $dayName = FortuneDailyHoroscopePost::DAY_NAMES[$post->day_of_birth];
            $dateStr = $post->post_date->locale('th')->translatedFormat('j M Y');

            $themeColors = [
                1 => [255, 230, 100], 2 => [255, 90, 90], 3 => [120, 220, 120],
                4 => [255, 160, 60], 5 => [100, 180, 255], 6 => [180, 100, 220],
                7 => [255, 100, 30],
            ];
            $color = $themeColors[$post->day_of_birth] ?? [200, 200, 200];

            $img = imagecreatetruecolor(1080, 1080);
            for ($y = 0; $y < 1080; $y++) {
                $ratio = $y / 1080;
                $r = (int) (30 + (60 * (1 - $ratio)));
                $g = (int) (10 + (20 * (1 - $ratio)));
                $b = (int) (60 + (100 * (1 - $ratio)));
                imageline($img, 0, $y, 1080, $y, imagecolorallocate($img, $r, $g, $b));
            }
            imagefilledellipse($img, 540, 320, 700, 700,
                imagecolorallocatealpha($img, $color[0], $color[1], $color[2], 80));

            $gold = imagecolorallocate($img, 255, 215, 0);
            $white = imagecolorallocate($img, 255, 255, 255);
            $cream = imagecolorallocate($img, 255, 240, 200);
            $fontPath = $this->findThaiFont();

            if ($fontPath) {
                imagettftext($img, 50, 0, 80, 100, $gold, $fontPath, "ดวงคนเกิดวัน{$dayName}");
                imagettftext($img, 28, 0, 80, 150, $cream, $fontPath, $dateStr);
                $cardName = $card?->name_th ?: ($card?->name_en ?: 'ไพ่ทาโรต์');
                $position = $post->is_reversed ? '(กลับด้าน)' : '(ตั้งตรง)';
                imagettftext($img, 60, 0, 80, 580, $white, $fontPath, $cardName);
                imagettftext($img, 32, 0, 80, 640, $cream, $fontPath, $position);
                imagettftext($img, 28, 0, 80, 1000, $gold, $fontPath, '✨ แม่หมอจันทรา • thaiprompt');

                // 🌙 (2026-05-23) CTA text ตาม toggle — ไม่ฮาร์ดโค้ด "ดูดวงเชิงลึก" ถ้า Deep ปิด
                $settings = \App\Models\FortuneTellingSetting::getSettings();
                $deepEnabled = $settings->isDeepReadingEnabled();
                $celticEnabled = (bool) ($settings->enable_celtic_cross ?? false);
                $ctaText = $deepEnabled
                    ? 'ดูดวงเชิงลึกทักแชทมาเลย'
                    : ($celticEnabled ? 'ทักแชทดูไพ่ Celtic 10 ใบ' : 'ทักแชทดูดวงได้เลย');
                imagettftext($img, 22, 0, 80, 1040, $cream, $fontPath, $ctaText);
            } else {
                imagestring($img, 5, 80, 100, "Day: {$dayName}", $gold);
                imagestring($img, 4, 80, 580, ($card?->name_en ?: 'Tarot'), $white);
                imagestring($img, 3, 80, 1000, 'thaiprompt fortune', $gold);
            }

            $relativePath = "fortune-daily/{$post->post_date->format('Y-m-d')}/day-{$post->day_of_birth}.jpg";
            $absolutePath = storage_path("app/public/{$relativePath}");
            if (! is_dir(dirname($absolutePath))) {
                mkdir(dirname($absolutePath), 0755, true);
            }
            imagejpeg($img, $absolutePath, 92);
            imagedestroy($img);

            $post->update(['image_path' => $relativePath]);

            return asset('storage/'.$relativePath);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * หา TTF font ภาษาไทยที่มีในระบบ
     */
    protected function findThaiFont(): ?string
    {
        $candidates = [
            public_path('fonts/Sarabun-Bold.ttf'),
            public_path('fonts/Kanit-Bold.ttf'),
            public_path('fonts/THSarabun.ttf'),
            resource_path('fonts/Sarabun-Bold.ttf'),
            resource_path('fonts/Kanit-Bold.ttf'),
            '/usr/share/fonts/truetype/sarabun/Sarabun-Bold.ttf',
            '/usr/share/fonts/truetype/thai/TlwgTypist.ttf',
            'C:/Windows/Fonts/Tahoma.ttf', // dev
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * 3. โพสไป Facebook Page
     */
    protected function publishToFacebook(FortuneDailyHoroscopePost $post): array
    {
        // ✅ Refresh จาก DB ตรงๆ — กัน static cache ของ getSettings() stale
        // (ใน flow มีการเรียก AI หลายครั้ง อาจมี side-effect)
        $fresh = FortuneTellingSetting::query()->first();
        if (! $fresh) {
            throw new Exception('ไม่พบ FortuneTellingSetting ใน DB');
        }

        $pageId = $fresh->facebook_page_id;
        $pageToken = $fresh->facebook_page_token
            ?? $fresh->facebook_page_access_token
            ?? $fresh->getRawOriginal('facebook_page_token');

        // Debug — ลง log ค่าความยาว (ไม่เผย secret)
        Log::info('DailyHoroscopeAutoPost: ตรวจ FB credentials ก่อน publish', [
            'has_page_id' => ! empty($pageId),
            'page_id_len' => is_string($pageId) ? strlen($pageId) : 0,
            'has_token' => ! empty($pageToken),
            'token_len' => is_string($pageToken) ? strlen($pageToken) : 0,
        ]);

        if (empty($pageId) || empty($pageToken)) {
            throw new Exception(sprintf(
                'ไม่พบ Facebook Page ID หรือ Page Access Token ใน settings (page_id_len=%d, token_len=%d)',
                is_string($pageId) ? strlen($pageId) : 0,
                is_string($pageToken) ? strlen($pageToken) : 0,
            ));
        }

        $caption = $post->caption ?: 'ดวงประจำวัน';

        // ถ้ามีรูป → /photos endpoint
        if (! empty($post->image_url)) {
            $endpoint = "https://graph.facebook.com/v21.0/{$pageId}/photos";
            $response = Http::timeout(60)->post($endpoint, [
                'url' => $post->image_url,
                'caption' => $caption,
                'access_token' => $pageToken,
            ]);
        } else {
            // text-only → /feed endpoint
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
            'response' => $data,
        ];
    }

    /**
     * ลบโพสบน Facebook Page (best-effort)
     *
     * เรียก Graph API DELETE /{fb_post_id}?access_token=...
     * ไม่ throw exception — ถ้าลบไม่สำเร็จยังให้ flow ดำเนินต่อ
     * (อาจเป็นเพราะ token หมดอายุ หรือ post ถูกลบไปแล้ว)
     *
     * @param  string  $fbPostId  รูปแบบ "{pageId}_{postId}" ตามที่ FB คืนกลับมา
     */
    protected function deleteFromFacebook(string $fbPostId): bool
    {
        try {
            $fresh = FortuneTellingSetting::query()->first();
            if (! $fresh) {
                Log::warning('DailyHoroscopeAutoPost: deleteFromFacebook ข้าม — ไม่พบ settings');

                return false;
            }

            $pageToken = $fresh->facebook_page_token
                ?? $fresh->facebook_page_access_token
                ?? $fresh->getRawOriginal('facebook_page_token');

            if (empty($pageToken)) {
                Log::warning('DailyHoroscopeAutoPost: deleteFromFacebook ข้าม — ไม่พบ page token');

                return false;
            }

            $endpoint = "https://graph.facebook.com/v21.0/{$fbPostId}";
            $response = Http::timeout(30)->delete($endpoint, [
                'access_token' => $pageToken,
            ]);

            $success = $response->successful() && ($response->json('success') === true);

            Log::info('DailyHoroscopeAutoPost: deleteFromFacebook', [
                'fb_post_id' => $fbPostId,
                'http_status' => $response->status(),
                'success' => $success,
                'response' => $response->json(),
            ]);

            return $success;
        } catch (Exception $e) {
            Log::warning('DailyHoroscopeAutoPost: deleteFromFacebook exception', [
                'fb_post_id' => $fbPostId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
