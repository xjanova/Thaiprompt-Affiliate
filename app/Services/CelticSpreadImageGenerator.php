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
     * @return string|null  URL ของภาพ (asset path) หรือ null ถ้าล้มเหลว
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
                mkdir($dir, 0755, true);
            }

            imagejpeg($canvas, $absolutePath, 90);
            imagedestroy($canvas);

            // 6. อัพเดต reading
            $reading->update(['celtic_summary_image_path' => $relativePath]);

            $url = asset('storage/' . $relativePath);

            Log::info('CelticSpreadImage: สร้างสำเร็จ', [
                'reading_id' => $reading->id,
                'path' => $relativePath,
                'size' => is_file($absolutePath) ? filesize($absolutePath) : 0,
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
            imagettftext($canvas, 36, 0, 60, 80, $gold, $fontPath, '🔮 Celtic Cross Tarot Reading');
            imagettftext($canvas, 22, 0, 60, 130, $cream, $fontPath, 'แม่หมอจันทรา ✦ ดูดวงไพ่ยิปซีเต็มสำรับ');
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
            // fallback: วาด placeholder (สี่เหลี่ยมสีแสดงว่าโหลดไม่ได้)
            $this->drawPlaceholderCard($canvas, $coords['x'], $coords['y'], $coords['rotation'], $position);

            return;
        }

        // Resize ไพ่ให้ขนาดมาตรฐาน
        $cardW = self::CARD_W;
        $cardH = self::CARD_H;
        $resized = imagecreatetruecolor($cardW, $cardH);
        imagecopyresampled(
            $resized, $cardImg,
            0, 0, 0, 0,
            $cardW, $cardH,
            imagesx($cardImg), imagesy($cardImg)
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
    protected function drawPlaceholderCard($canvas, int $x, int $y, int $rotation, int $position): void
    {
        $w = self::CARD_W;
        $h = self::CARD_H;
        if ($rotation !== 0) {
            // swap w/h สำหรับ 90°
            [$w, $h] = [$h, $w];
        }

        $px = $x - intdiv($w, 2);
        $py = $y - intdiv($h, 2);

        $bg = imagecolorallocate($canvas, 60, 30, 90);
        $border = imagecolorallocate($canvas, 200, 170, 80);
        imagefilledrectangle($canvas, $px, $py, $px + $w, $py + $h, $bg);
        imagerectangle($canvas, $px, $py, $px + $w, $py + $h, $border);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagestring($canvas, 4, $px + 10, $py + ($h / 2) - 5, "POS {$position}", $white);
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
                $text .= ' ⤵';
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
            '⤵ = ไพ่กลับหัว',
        ];

        $y = $startY;
        foreach ($legend as $i => $line) {
            imagettftext($canvas, 14, 0, 60, $y, $cream, $fontPath, $line);
            $y += 28;
        }

        // ✨ watermark
        $gold = imagecolorallocate($canvas, 220, 180, 100);
        imagettftext($canvas, 14, 0, 60, self::CANVAS_H - 50, $gold, $fontPath, '✨ thaiprompt.online ✦ แม่หมอจันทรา');
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

            $tempPath = tempnam(sys_get_temp_dir(), 'celtic_card_') . '.img';
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
            return storage_path('app/public/' . substr($path, strlen('/storage/')));
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
            public_path('fonts/Sarabun-Bold.ttf'),
            public_path('fonts/Kanit-Bold.ttf'),
            public_path('fonts/THSarabun.ttf'),
            resource_path('fonts/Sarabun-Bold.ttf'),
            resource_path('fonts/Kanit-Bold.ttf'),
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
