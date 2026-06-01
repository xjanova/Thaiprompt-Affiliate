<?php

namespace App\Services;

use App\Models\FortuneReading;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * สร้างภาพ composite Celtic Cross spread (10 ใบเรียงตามแบบมาตรฐาน)
 *
 * ใช้ GD library — รับ FortuneReading ที่มี celtic_cards 10 ใบ
 * Output: ไฟล์ JPEG ที่ storage/app/public/celtic-spreads/{reading_id}.jpg
 *
 * Layout:
 *               [10]
 *               [9]
 *      [3]      [8]      ← Staff (right column, bottom-up: 7,8,9,10)
 *               [7]
 * [5] [1+2] [6]
 *      [4]
 *
 * Position 2 ทับ Position 1 หมุน 90° (ลายไพ่ "cross")
 * ไพ่กลับหัว = หมุน 180°
 */
class CelticSpreadImageGenerator
{
    // Canvas
    protected const CANVAS_W = 1200;

    protected const CANVAS_H = 1500;

    // Card size (standard tarot proportion 5:8)
    protected const CARD_W = 130;

    protected const CARD_H = 208;

    // Position coordinates (center x, center y) — Cross + Staff
    protected const POSITIONS = [
        // Cross (left side, around center 380, 750)
        1 => ['x' => 380, 'y' => 750, 'rotation' => 0],   // ใจกลาง
        2 => ['x' => 380, 'y' => 750, 'rotation' => 90],  // ทับ 1 หมุน 90° (lying horizontal)
        3 => ['x' => 380, 'y' => 460, 'rotation' => 0],   // เหนือ
        4 => ['x' => 380, 'y' => 1040, 'rotation' => 0],  // ใต้
        5 => ['x' => 180, 'y' => 750, 'rotation' => 0],   // ซ้าย (อดีต)
        6 => ['x' => 580, 'y' => 750, 'rotation' => 0],   // ขวา (อนาคต)
        // Staff (right column, bottom-up: 7,8,9,10)
        7 => ['x' => 920, 'y' => 1230, 'rotation' => 0],
        8 => ['x' => 920, 'y' => 990, 'rotation' => 0],
        9 => ['x' => 920, 'y' => 750, 'rotation' => 0],
        10 => ['x' => 920, 'y' => 510, 'rotation' => 0],
    ];

    /**
     * สร้างภาพ spread + บันทึก path ใน reading
     *
     * @return string|null URL ของภาพ (asset path) หรือ null ถ้าล้มเหลว
     */
    public function generate(FortuneReading $reading): ?string
    {
        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            Log::warning('CelticSpreadImage: ไพ่ไม่ครบ 10 ใบ ข้าม', [
                'reading_id' => $reading->id,
                'count' => count($cards),
            ]);

            return null;
        }

        try {
            // 1. สร้าง canvas พื้นหลัง gradient
            $canvas = $this->createCanvas();

            // 2. วาดชื่อ + วันที่ด้านบน
            $this->drawHeader($canvas);

            // 3. วาดไพ่ทั้ง 10 ใบ
            for ($pos = 1; $pos <= 10; $pos++) {
                $card = $cards[$pos] ?? null;
                if (! $card) {
                    continue;
                }

                $this->drawCard($canvas, $card, $pos);
            }

            // 4. วาด position numbers + ชื่อตำแหน่ง
            $this->drawLegend($canvas);

            // 5. บันทึกไฟล์
            $relativePath = "celtic-spreads/{$reading->id}.jpg";
            $absolutePath = storage_path("app/public/{$relativePath}");
            $dir = dirname($absolutePath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            // 🩹 (2026-05-05) Verify directory writable + file write
            if (! is_writable($dir)) {
                Log::error('CelticSpreadImage: directory ไม่สามารถเขียนได้', [
                    'reading_id' => $reading->id,
                    'dir' => $dir,
                ]);
                imagedestroy($canvas);

                return null;
            }

            $writeOk = @imagejpeg($canvas, $absolutePath, 90);
            imagedestroy($canvas);

            if (! $writeOk || ! is_file($absolutePath) || filesize($absolutePath) < 1024) {
                Log::error('CelticSpreadImage: บันทึกไฟล์ล้มเหลว / ไฟล์เล็กผิดปกติ', [
                    'reading_id' => $reading->id,
                    'path' => $absolutePath,
                    'write_ok' => $writeOk,
                    'exists' => is_file($absolutePath),
                    'size' => is_file($absolutePath) ? filesize($absolutePath) : 0,
                ]);

                return null;
            }

            // 6. อัพเดต reading
            $reading->update(['celtic_summary_image_path' => $relativePath]);

            // 🩹 (2026-05-05) URL with cache buster (mtime) — กัน FB cache ภาพเก่า
            //   เคสจริง: FB cache รูป URL → ลูกค้าเห็นรูปเก่าตอนเปิดไพ่ แทนรูปครบ 10 ใบ
            //   Fix: ?v={mtime} → unique URL ทุกครั้งที่ regen
            $cacheBuster = filemtime($absolutePath) ?: time();
            $url = asset('storage/'.$relativePath).'?v='.$cacheBuster;

            Log::info('CelticSpreadImage: สร้างสำเร็จ', [
                'reading_id' => $reading->id,
                'path' => $relativePath,
                'size' => filesize($absolutePath),
                'url' => $url,
            ]);

            return $url;
        } catch (Exception $e) {
            Log::error('CelticSpreadImage: ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * สร้าง canvas พื้นหลัง gradient (deep purple → indigo + ดาวเล็กๆ)
     */
    protected function createCanvas()
    {
        $canvas = imagecreatetruecolor(self::CANVAS_W, self::CANVAS_H);

        // Gradient bg (vertical: top dark purple → bottom darker indigo)
        for ($y = 0; $y < self::CANVAS_H; $y++) {
            $ratio = $y / self::CANVAS_H;
            $r = (int) (20 + (50 * (1 - $ratio)));
            $g = (int) (10 + (20 * (1 - $ratio)));
            $b = (int) (45 + (60 * (1 - $ratio)));
            $color = imagecolorallocate($canvas, $r, $g, $b);
            imageline($canvas, 0, $y, self::CANVAS_W, $y, $color);
        }

        // โรย "ดาว" สุ่ม
        $star = imagecolorallocatealpha($canvas, 255, 240, 200, 50);
        for ($i = 0; $i < 80; $i++) {
            $sx = random_int(0, self::CANVAS_W - 1);
            $sy = random_int(0, self::CANVAS_H - 1);
            $size = random_int(1, 3);
            imagefilledellipse($canvas, $sx, $sy, $size, $size, $star);
        }

        return $canvas;
    }

    /**
     * วาด header: "Celtic Cross — แม่หมอจันทรา"
     */
    protected function drawHeader($canvas): void
    {
        $gold = imagecolorallocate($canvas, 230, 195, 100);
        $cream = imagecolorallocate($canvas, 240, 230, 200);
        $fontPath = $this->findThaiFont();

        if ($fontPath) {
            imagettftext($canvas, 36, 0, 60, 80, $gold, $fontPath, 'Celtic Cross Tarot Reading');
            imagettftext($canvas, 22, 0, 60, 130, $cream, $fontPath, 'แม่หมอจันทรา • ดูดวงไพ่ยิปซีเต็มสำรับ');
        } else {
            imagestring($canvas, 5, 60, 60, 'Celtic Cross Tarot Reading', $gold);
        }
    }

    /**
     * วาดไพ่ 1 ใบที่ตำแหน่ง position
     *
     * @param  array  $card  ['image_url', 'is_reversed', 'card_name_th', ...]
     * @param  int  $position  1-10
     */
    protected function drawCard($canvas, array $card, int $position): void
    {
        $coords = self::POSITIONS[$position] ?? null;
        if (! $coords) {
            return;
        }

        $imageUrl = $card['image_url'] ?? null;
        $isReversed = ! empty($card['is_reversed']);

        // โหลด card image
        $cardImg = $this->loadImageFromUrl($imageUrl);
        if (! $cardImg) {
            // fallback: ไพ่ยังไม่มีรูป (เช่น Minor Arcana 56 ใบที่ยังไม่อัปโหลด / SVG ที่ GD อ่านไม่ได้)
            //   → วาดการ์ดหลังไพ่ + ชื่อไพ่ไทย แทนกล่องเปล่า ให้ดูตั้งใจ
            $this->drawPlaceholderCard($canvas, $coords['x'], $coords['y'], $coords['rotation'], $position, $card);

            return;
        }

        // Resize ไพ่ — รักษาอัตราส่วน (contain) ไม่บีบ ไม่ครอป
        //   🩹 (2026-06-02, user) เดิมยัด source เต็มใบลง 130×208 ตายตัว → ไพ่ที่ไม่ใช่ 5:8
        //     (เช่น webp 400×600 = 2:3) ถูกบีบแนวนอน ~6% = ภาพเพี้ยน
        //   ใหม่: สเกลตาม min(W/srcW, H/srcH) → การ์ดคงสัดส่วนจริง (เล็กลงพอดีกรอบ)
        //     ขั้นถัดไป center ที่ (x,y) ด้วย imagesx/imagesy($resized) อยู่แล้ว → วางกึ่งกลางตำแหน่งถูกต้อง
        $srcW = imagesx($cardImg);
        $srcH = imagesy($cardImg);
        $scale = min(self::CARD_W / $srcW, self::CARD_H / $srcH);
        $drawW = max(1, (int) round($srcW * $scale));
        $drawH = max(1, (int) round($srcH * $scale));
        $resized = imagecreatetruecolor($drawW, $drawH);
        imagecopyresampled(
            $resized, $cardImg,
            0, 0, 0, 0,
            $drawW, $drawH,
            $srcW, $srcH
        );
        imagedestroy($cardImg);

        // กลับหัว 180°
        if ($isReversed) {
            $rotated = imagerotate($resized, 180, 0);
            imagedestroy($resized);
            $resized = $rotated;
        }

        // หมุนตาม position rotation (90° สำหรับ position 2)
        $totalRotation = $coords['rotation'];
        if ($totalRotation !== 0) {
            $rotated = imagerotate($resized, $totalRotation, 0);
            imagedestroy($resized);
            $resized = $rotated;
        }

        // Compose ไปที่ canvas (จัดให้ตรงกลางที่ x,y)
        $finalW = imagesx($resized);
        $finalH = imagesy($resized);
        $px = $coords['x'] - intdiv($finalW, 2);
        $py = $coords['y'] - intdiv($finalH, 2);

        imagecopy($canvas, $resized, $px, $py, 0, 0, $finalW, $finalH);
        imagedestroy($resized);

        // วาดเลข position ใต้ไพ่
        $this->drawPositionNumber($canvas, $coords['x'], $coords['y'] + (self::CARD_H / 2) + 22, $position, $isReversed);
    }

    /**
     * วาด placeholder card ถ้าโหลดรูปไพ่ไม่ได้
     */
    protected function drawPlaceholderCard($canvas, int $x, int $y, int $rotation, int $position, array $card = []): void
    {
        $w = self::CARD_W;
        $h = self::CARD_H;
        if ($rotation !== 0) {
            // swap w/h สำหรับ 90°
            [$w, $h] = [$h, $w];
        }

        $px = $x - intdiv($w, 2);
        $py = $y - intdiv($h, 2);

        // 🎴 (2026-06-02) การ์ดหลังไพ่ลึกลับ + ขอบทอง 2 ชั้น — ดูตั้งใจ ไม่เหมือน error box
        $bg = imagecolorallocate($canvas, 48, 26, 78);
        $border = imagecolorallocate($canvas, 210, 175, 95);
        imagefilledrectangle($canvas, $px, $py, $px + $w, $py + $h, $bg);
        imagerectangle($canvas, $px, $py, $px + $w, $py + $h, $border);
        imagerectangle($canvas, $px + 4, $py + 4, $px + $w - 4, $py + $h - 4, $border);

        $fontPath = $this->findThaiFont();
        $cardName = trim((string) ($card['card_name_th'] ?? ''));
        $isReversed = ! empty($card['is_reversed']);

        if ($fontPath && $cardName !== '') {
            // • ดาวบน
            $gold = imagecolorallocate($canvas, 220, 185, 110);
            $bbTop = imagettfbbox(15, 0, $fontPath, '•');
            imagettftext($canvas, 15, 0, $x - intdiv($bbTop[2] - $bbTop[0], 2), $py + 32, $gold, $fontPath, '•');

            // ชื่อไพ่ไทย — wrap กลางการ์ด (กล่องแนวนอน [rotation 90°] กว้างกว่า → ตัวอักษร/บรรทัดมากขึ้น)
            $cream = imagecolorallocate($canvas, 242, 232, 208);
            $perLine = $rotation !== 0 ? 16 : 9;
            $lines = $this->wrapThaiText($cardName, $perLine);
            $lineH = 26;
            $startY = $y - intdiv((count($lines) - 1) * $lineH, 2);
            foreach ($lines as $i => $line) {
                $bb = imagettfbbox(13, 0, $fontPath, $line);
                $tw = $bb[2] - $bb[0];
                imagettftext($canvas, 13, 0, $x - intdiv($tw, 2), $startY + ($i * $lineH), $cream, $fontPath, $line);
            }

            // ตำแหน่ง + กลับหัว ด้านล่าง
            $tag = "[{$position}]".($isReversed ? '  (กลับหัว)' : '');
            $tagColor = $isReversed ? imagecolorallocate($canvas, 255, 150, 150) : $gold;
            $bbTag = imagettfbbox(12, 0, $fontPath, $tag);
            imagettftext($canvas, 12, 0, $x - intdiv($bbTag[2] - $bbTag[0], 2), $py + $h - 16, $tagColor, $fontPath, $tag);
        } else {
            // ไม่มีฟอนต์/ชื่อ → fallback ข้อความ bitmap
            $white = imagecolorallocate($canvas, 235, 230, 245);
            imagestring($canvas, 4, $px + 10, (int) ($py + ($h / 2) - 5), "POS {$position}", $white);
        }
    }

    /**
     * ตัดข้อความเป็นหลายบรรทัดให้พอดีการ์ด
     *   - มีช่องว่าง (อังกฤษ เช่น "Three of Wands") → ตัดตามคำ
     *   - ไทยล้วน (ไม่มีช่องว่าง) → ตัดตามจำนวนตัวอักษร (~$perLine)
     *
     * @return array<int,string> สูงสุด 4 บรรทัด
     */
    protected function wrapThaiText(string $text, int $perLine = 9): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // มีช่องว่าง + ยาวเกิน → ตัดตามคำ
        if (mb_strpos($text, ' ') !== false && mb_strlen($text) > $perLine) {
            $words = preg_split('/\s+/u', $text) ?: [$text];
            $lines = [];
            $cur = '';
            foreach ($words as $wd) {
                $try = $cur === '' ? $wd : $cur.' '.$wd;
                if (mb_strlen($try) > $perLine && $cur !== '') {
                    $lines[] = $cur;
                    $cur = $wd;
                } else {
                    $cur = $try;
                }
            }
            if ($cur !== '') {
                $lines[] = $cur;
            }

            return array_slice($lines, 0, 4);
        }

        // ไทยล้วน → ตัดตามจำนวนตัวอักษร
        $chars = mb_str_split($text);
        $lines = [];
        for ($i = 0; $i < count($chars); $i += $perLine) {
            $lines[] = implode('', array_slice($chars, $i, $perLine));
        }

        return array_slice($lines, 0, 4);
    }

    /**
     * วาดเลขตำแหน่งใต้ไพ่
     */
    protected function drawPositionNumber($canvas, int $x, int $y, int $position, bool $isReversed): void
    {
        $fontPath = $this->findThaiFont();
        $gold = imagecolorallocate($canvas, 255, 215, 90);
        $red = imagecolorallocate($canvas, 255, 130, 130);

        if ($fontPath) {
            $text = "[{$position}]";
            if ($isReversed) {
                $text .= ' (กลับหัว)';
            }
            imagettftext($canvas, 14, 0, $x - 20, $y, $gold, $fontPath, $text);
        }
    }

    /**
     * วาดคำอธิบาย legend ด้านล่าง (1=หัวใจ, 2=อุปสรรค, ฯลฯ)
     */
    protected function drawLegend($canvas): void
    {
        $fontPath = $this->findThaiFont();
        if (! $fontPath) {
            return;
        }

        $cream = imagecolorallocate($canvas, 230, 220, 195);
        $startY = self::CANVAS_H - 200;

        $legend = [
            '1=หัวใจของเรื่อง  •  2=อุปสรรค  •  3=เป้าหมาย  •  4=รากฐาน  •  5=อดีต',
            '6=อนาคตอันใกล้  •  7=ตัวเจ้าชะตา  •  8=อิทธิพลภายนอก  •  9=ความหวัง&ความกลัว  •  10=ผลลัพธ์',
            '(กลับหัว) = ไพ่วางหัวลง ความหมายเปลี่ยน',
        ];

        $y = $startY;
        foreach ($legend as $i => $line) {
            imagettftext($canvas, 14, 0, 60, $y, $cream, $fontPath, $line);
            $y += 28;
        }

        // watermark
        $gold = imagecolorallocate($canvas, 220, 180, 100);
        imagettftext($canvas, 14, 0, 60, self::CANVAS_H - 50, $gold, $fontPath, 'thaiprompt.online • แม่หมอจันทรา');
    }

    /**
     * โหลดรูปจาก URL → GD resource
     */
    protected function loadImageFromUrl(?string $url)
    {
        if (! $url) {
            return null;
        }

        try {
            // ถ้าเป็น URL ภายใน server (เริ่มด้วย / หรือ asset URL ของเรา) → อ่านจาก disk
            $localPath = $this->resolveLocalPath($url);
            if ($localPath && is_file($localPath)) {
                return $this->imageCreateFromFile($localPath);
            }

            // ดาวน์โหลด HTTP
            $response = Http::timeout(15)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'celtic_card_').'.img';
            file_put_contents($tempPath, $response->body());
            $img = $this->imageCreateFromFile($tempPath);
            @unlink($tempPath);

            return $img;
        } catch (Exception $e) {
            return null;
        }
    }

    protected function imageCreateFromFile(string $path)
    {
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    /**
     * แปลง URL → local path ถ้าเป็นภาพใน storage ของเรา (ประหยัด HTTP)
     */
    protected function resolveLocalPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! $path) {
            return null;
        }

        // /storage/foo.jpg → storage/app/public/foo.jpg
        if (str_starts_with($path, '/storage/')) {
            return storage_path('app/public/'.substr($path, strlen('/storage/')));
        }

        // /images/... → public/images/...
        if (str_starts_with($path, '/images/')) {
            return public_path(ltrim($path, '/'));
        }

        return null;
    }

    /**
     * หา TTF font ภาษาไทย (เหมือน DailyHoroscopeAutoPostService)
     */
    protected function findThaiFont(): ?string
    {
        $candidates = [
            // ⭐ (2026-06-02) ฟอนต์ไทยที่ฝังมากับ repo จริง — ต้องมาก่อน!
            //   เดิม list หาแต่ Sarabun/Kanit ที่ไม่มีบน prod → คืน null → ข้อความไทยทั้งหมด
            //   (subtitle/legend/เลขตำแหน่ง) หายเงียบๆ — เห็นแต่หัวอังกฤษ bitmap
            resource_path('fonts/NotoSansThai-Bold.ttf'),
            public_path('fonts/Sarabun-Bold.ttf'),
            public_path('fonts/Kanit-Bold.ttf'),
            public_path('fonts/THSarabun.ttf'),
            resource_path('fonts/Sarabun-Bold.ttf'),
            resource_path('fonts/Kanit-Bold.ttf'),
            resource_path('fonts/DejaVuSans.ttf'),
            '/usr/share/fonts/truetype/sarabun/Sarabun-Bold.ttf',
            '/usr/share/fonts/truetype/thai/TlwgTypist.ttf',
            'C:/Windows/Fonts/Tahoma.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
