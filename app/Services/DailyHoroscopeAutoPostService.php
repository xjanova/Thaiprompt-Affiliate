<?php

namespace App\Services;

use App\Models\FortuneDailyHoroscopePost;
use App\Models\FortuneTellingSetting;
use App\Models\TarotCard;
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
     */
    public function generateAndPublish(int $dayOfBirth, ?Carbon $date = null): array
    {
        $date = $date ?? now();

        // ตรวจ duplicate (idempotent)
        $existing = FortuneDailyHoroscopePost::where('post_date', $date->toDateString())
            ->where('day_of_birth', $dayOfBirth)
            ->first();

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

        $dayName = FortuneDailyHoroscopePost::DAY_NAMES[$post->day_of_birth] ?? '?';
        $dayEmoji = FortuneDailyHoroscopePost::DAY_EMOJI[$post->day_of_birth] ?? '✨';

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
        $dayEmoji = FortuneDailyHoroscopePost::DAY_EMOJI[$post->day_of_birth];
        $dateStr = $post->post_date->locale('th')->translatedFormat('j F Y');
        $cardName = $card->name_th ?: $card->name_en;
        $position = $isReversed ? 'กลับด้าน' : 'ตั้งตรง';

        $prompt = "เขียน caption Facebook สั้นกระชับ 4-6 บรรทัด (ภาษาไทย) สำหรับโพสดวงประจำวัน:\n\n"
            . "วันนี้: {$dateStr}\n"
            . "สำหรับ: ผู้เกิดวัน{$dayName}\n"
            . "ไพ่ที่สุ่มได้: {$cardName} ({$position})\n"
            . "ความหมาย: " . mb_substr($meaning, 0, 200) . "\n\n"
            . "กฎ:\n"
            . "1. ขึ้นต้นด้วย {$dayEmoji} 'ดวงคนเกิดวัน{$dayName} | {$dateStr}'\n"
            . "2. บอกจุดเด่นชัดๆ 2-3 ข้อสั้น (การงาน/การเงิน/ความรัก)\n"
            . "3. ฟันธงเลย ไม่อ้อมค้อม ไม่ใช้คำว่า 'อาจจะ' 'น่าจะ'\n"
            . "4. ปิดท้ายด้วยข้อแนะนำ 1 ข้อ + CTA 'อยากดูเชิงลึกทักแชทมาเลย'\n"
            . "5. ห้ามมี emoji เยอะเกิน 5 ตัว\n"
            . "6. ห้ามยาวเกิน 350 ตัวอักษร";

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

            $text = trim($result['response'] ?? '');
            if ($text !== '' && mb_strlen($text) >= 50) {
                return mb_substr($text, 0, 600); // cap เผื่อ AI โอเวอร์
            }
        } catch (Exception $e) {
            Log::warning('DailyHoroscopeAutoPost: AI caption ล้มเหลว → fallback template', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback template (กรณี AI ล้มเหลว)
        return "{$dayEmoji} ดวงคนเกิดวัน{$dayName} | {$dateStr}\n"
            . "🃏 ไพ่วันนี้: {$cardName} ({$position})\n\n"
            . mb_substr($meaning, 0, 180) . "\n\n"
            . "💎 อยากดูเชิงลึกแม่นๆ ทักแชทมาคุยกับแม่หมอจันทราได้เลย ✨\n"
            . '#ดวงประจำวัน #ไพ่ทาโรต์ #ดูดวงฟรี';
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
            $card = $post->tarotCard;
            $dayName = FortuneDailyHoroscopePost::DAY_NAMES[$post->day_of_birth];
            $dayEmoji = FortuneDailyHoroscopePost::DAY_EMOJI[$post->day_of_birth];
            $dateStr = $post->post_date->locale('th')->translatedFormat('j M Y');

            // สี theme ตามวันเกิด (สีไทยโบราณ)
            $themeColors = [
                1 => [255, 230, 100], // จันทร์ - เหลืองนวล
                2 => [255, 90, 90],   // อังคาร - แดง
                3 => [120, 220, 120], // พุธ - เขียว
                4 => [255, 160, 60],  // พฤหัส - ส้ม
                5 => [100, 180, 255], // ศุกร์ - ฟ้า
                6 => [180, 100, 220], // เสาร์ - ม่วง
                7 => [255, 100, 30],  // อาทิตย์ - แดงส้ม
            ];
            $color = $themeColors[$post->day_of_birth] ?? [200, 200, 200];

            // สร้างรูปขนาด 1080x1080 (Instagram/FB square)
            $width = 1080;
            $height = 1080;
            $img = imagecreatetruecolor($width, $height);

            // Background gradient (ม่วงเข้ม → ดำ)
            for ($y = 0; $y < $height; $y++) {
                $ratio = $y / $height;
                $r = (int) (30 + (60 * (1 - $ratio)));
                $g = (int) (10 + (20 * (1 - $ratio)));
                $b = (int) (60 + (100 * (1 - $ratio)));
                $bgColor = imagecolorallocate($img, $r, $g, $b);
                imageline($img, 0, $y, $width, $y, $bgColor);
            }

            // วงกลมสีตาม theme ทับ background (deco)
            $themeRgb = imagecolorallocatealpha($img, $color[0], $color[1], $color[2], 80);
            imagefilledellipse($img, $width / 2, 320, 700, 700, $themeRgb);

            // ตัวอักษรสีทอง
            $gold = imagecolorallocate($img, 255, 215, 0);
            $white = imagecolorallocate($img, 255, 255, 255);
            $cream = imagecolorallocate($img, 255, 240, 200);

            // Font (ใช้ default GD font — ไม่ต้องโหลด TTF)
            // หา TTF font ภาษาไทยถ้ามี
            $fontPath = $this->findThaiFont();

            if ($fontPath) {
                // Header: "ดวงคนเกิดวันจันทร์"
                $header = "ดวงคนเกิดวัน{$dayName}";
                imagettftext($img, 50, 0, 80, 100, $gold, $fontPath, $header);

                // Date
                imagettftext($img, 28, 0, 80, 150, $cream, $fontPath, $dateStr);

                // Card name (ใหญ่กลางภาพ)
                $cardName = $card?->name_th ?: ($card?->name_en ?: 'ไพ่ทาโรต์');
                $position = $post->is_reversed ? '(กลับด้าน)' : '(ตั้งตรง)';
                imagettftext($img, 60, 0, 80, 580, $white, $fontPath, $cardName);
                imagettftext($img, 32, 0, 80, 640, $cream, $fontPath, $position);

                // Footer: brand
                imagettftext($img, 28, 0, 80, 1000, $gold, $fontPath, '✨ แม่หมอจันทรา • thaiprompt');
                imagettftext($img, 22, 0, 80, 1040, $cream, $fontPath, 'ดูดวงเชิงลึกทักแชทมาเลย');
            } else {
                // Fallback: GD built-in font (ไม่รองรับภาษาไทยดี — แสดงเป็น box แทน)
                imagestring($img, 5, 80, 100, "Day: {$dayName}", $gold);
                imagestring($img, 4, 80, 580, ($card?->name_en ?: 'Tarot'), $white);
                imagestring($img, 3, 80, 1000, 'thaiprompt fortune', $gold);
            }

            // Save to storage
            $relativePath = "fortune-daily/{$post->post_date->format('Y-m-d')}/day-{$post->day_of_birth}.jpg";
            $absolutePath = storage_path("app/public/{$relativePath}");
            $dir = dirname($absolutePath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            imagejpeg($img, $absolutePath, 92);
            imagedestroy($img);

            $publicUrl = asset('storage/' . $relativePath);

            $post->update([
                'image_path' => $relativePath,
                'image_url' => $publicUrl,
                'status' => FortuneDailyHoroscopePost::STATUS_READY,
            ]);
        } catch (Exception $e) {
            Log::warning('DailyHoroscopeAutoPost: สร้างรูปล้มเหลว — โพสแบบ text-only', [
                'error' => $e->getMessage(),
            ]);
            // ไม่ throw — ยังสามารถ post text-only ได้
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
        $pageId = $this->settings->facebook_page_id;
        $pageToken = $this->settings->facebook_page_token ?? $this->settings->facebook_page_access_token;

        if (empty($pageId) || empty($pageToken)) {
            throw new Exception('ไม่พบ Facebook Page ID หรือ Page Access Token ใน settings');
        }

        $caption = $post->caption ?: 'ดวงประจำวัน';

        // ถ้ามีรูป → /photos endpoint
        if (! empty($post->image_url)) {
            $endpoint = "https://graph.facebook.com/v18.0/{$pageId}/photos";
            $response = Http::timeout(60)->post($endpoint, [
                'url' => $post->image_url,
                'caption' => $caption,
                'access_token' => $pageToken,
            ]);
        } else {
            // text-only → /feed endpoint
            $endpoint = "https://graph.facebook.com/v18.0/{$pageId}/feed";
            $response = Http::timeout(60)->post($endpoint, [
                'message' => $caption,
                'access_token' => $pageToken,
            ]);
        }

        if (! $response->successful()) {
            $error = $response->json('error.message', 'Facebook API error: HTTP ' . $response->status());
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
}
