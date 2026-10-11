<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Support\FontFile;
use App\Support\ThaiFontText;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use GdImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * สร้างภาพ composite Celtic Cross spread (10 ใบเรียงตามแบบมาตรฐาน) — ภาพที่ระลึกตอนสรุปจบบิล 99
 *
 * ใช้ GD library (prod ไม่มี Imagick · memory_limit 128M) — รับ FortuneReading ที่มี celtic_cards 10 ใบ
 * Output: ไฟล์ JPEG ที่ storage/app/public/celtic-spreads/{reading_id}.jpg
 *
 * Layout:
 *                              [10]
 *        [3]                   [9]
 *   [5]  [1]  [2]  [6]         [8]     ← Staff (คอลัมน์ขวา อ่านจากล่างขึ้นบน 7,8,9,10)
 *        [4]                   [7]
 *
 * Position 2 (ไพ่ขวาง หมุน 90°) วางถัดจาก Position 1 ทางขวา — แยกกัน ไม่ทับ (owner สั่ง 2026-06-08)
 * ไพ่กลับหัว = หมุน 180° + ป้าย "กลับหัว" บนไพ่
 *
 * 🎨 (2026-10-11, owner: "ภาพเรียงไพ่ตอนสรุปจบของ 99 สร้างให้ชัดสวยกว่านี้ได้ไหม")
 *   เดิม: ไพ่ใบละ 117×208 บนผ้าใบ 1200×1500 (ที่ว่างเยอะ ไพ่เล็กจนดูไม่ออก) · ไม่มีชื่อไพ่
 *         · คำอธิบายตำแหน่งกองอยู่ท้ายภาพ · "หัวใจของเรื่อง" วรรณยุกต์จมหายเป็น "เรือง"
 *   ใหม่: ไพ่ 200×356 (ต้นฉบับ 536×960 + ชาร์ปหลังย่อ) · ชื่อตำแหน่ง + ชื่อไพ่ใต้ทุกใบ · เหรียญเลขทอง
 *         · พื้นหลังไล่แสง + วงแหวนรอบกางเขน + เงาไพ่ · วรรณยุกต์บนสระบนยกลอย (ThaiFontText::draw — GD ไม่ทำ shaping)
 *   ⚠️ LINE ใช้ URL เดียวกันเป็น previewImageUrl (ต้อง ≤ 1MB) → saveJpeg ลดคุณภาพจนไฟล์ ≤ MAX_BYTES
 *   ⚠️ ห้ามใส่ชื่อลูกค้าในภาพ — URL เดาได้ (celtic-spreads/{id}.jpg) ใครก็เปิดได้
 */
class CelticSpreadImageGenerator
{
    // Canvas (2:3 — เฟซบุ๊ก/LINE เปิดภาพแนวตั้งเต็มจอได้ · ไพ่กินความกว้างภาพ ~14% ต่อใบ เดิม ~10%)
    protected const CANVAS_W = 1440;

    protected const CANVAS_H = 2200;

    // Card size — อัตราส่วน 9:16 ตรงกับไพ่ที่ออกแบบใหม่ (536×960) · drawCard สเกลแบบ contain ไม่ครอป ไม่บีบ
    protected const CARD_W = 200;

    protected const CARD_H = 356;

    // ขนาดไฟล์สูงสุด — LINE previewImageUrl รับไม่เกิน 1MB (เผื่อไว้นิดหน่อย)
    public const MAX_BYTES = 950_000;

    // จุดกึ่งกลางวงแหวนรอบกางเขน (กึ่งกลางระหว่างใบ 5 กับใบ 6 · แถวกลาง)
    protected const CROSS_CENTER = ['x' => 588, 'y' => 1162];

    // Position coordinates (center x, center y) + ความกว้างสูงสุดของป้ายชื่อใต้ไพ่
    //   แนวนอน: ขอบ 65 · ห่างกัน 30 · ระหว่างกางเขนกับคอลัมน์ขวา 64
    //   แนวตั้ง: ไพ่ห่างกัน 456 (ไพ่ 356 + ป้ายชื่อ/ช่องว่าง 100) · กางเขนจัดกึ่งกลางเทียบคอลัมน์ขวา
    protected const POSITIONS = [
        // Cross — แถวกลาง 5 · 1 · 2(ขวาง) · 6 · บน 3 · ล่าง 4
        1 => ['x' => 395, 'y' => 1162, 'rotation' => 0, 'label_w' => 224],   // ใจกลาง
        2 => ['x' => 703, 'y' => 1162, 'rotation' => 90, 'label_w' => 380],  // ไพ่ขวาง (หมุน 90°) ข้างใบ 1
        3 => ['x' => 395, 'y' => 706, 'rotation' => 0, 'label_w' => 340],    // เหนือ
        4 => ['x' => 395, 'y' => 1618, 'rotation' => 0, 'label_w' => 340],   // ใต้
        5 => ['x' => 165, 'y' => 1162, 'rotation' => 0, 'label_w' => 224],   // ซ้าย (อดีต)
        6 => ['x' => 1011, 'y' => 1162, 'rotation' => 0, 'label_w' => 224],  // ขวา (อนาคต)
        // Staff (คอลัมน์ขวา ล่างขึ้นบน 7,8,9,10)
        7 => ['x' => 1275, 'y' => 1846, 'rotation' => 0, 'label_w' => 264],
        8 => ['x' => 1275, 'y' => 1390, 'rotation' => 0, 'label_w' => 264],
        9 => ['x' => 1275, 'y' => 934, 'rotation' => 0, 'label_w' => 264],
        10 => ['x' => 1275, 'y' => 478, 'rotation' => 0, 'label_w' => 264],
    ];

    // สีหลัก (RGB)
    protected const GOLD = [232, 196, 116];

    protected const GOLD_MUTED = [206, 170, 104];

    protected const CREAM = [248, 238, 218];

    protected const LAVENDER = [196, 182, 228];

    protected ?string $fontPath = null;

    protected bool $fontResolved = false;

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
            $canvas = $this->render($cards, $this->readingDate($reading));

            // บันทึกไฟล์
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

            $writeOk = $this->saveJpeg($canvas, $absolutePath);
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

            // อัพเดต reading
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
        } catch (\Throwable $e) {
            Log::error('CelticSpreadImage: ล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * วาดภาพทั้งใบ (แยกจาก generate ให้พรีวิว/ทดสอบได้โดยไม่แตะ DB และไฟล์)
     *
     * @param  array<int,array>  $cards  ไพ่ตาม position 1-10 (['image_url','is_reversed','card_name_th',...])
     * @param  CarbonInterface|null  $date  วันที่ดูดวง (แสดงใต้หัวภาพ) — null = ไม่แสดงวันที่
     */
    public function render(array $cards, ?CarbonInterface $date = null): GdImage
    {
        // 1. พื้นหลังไล่แสง + ดาว + วงแหวนรอบกางเขน
        $canvas = $this->createCanvas();
        $this->drawStars($canvas);
        $this->drawCrossHalo($canvas);

        // 2. หัวภาพ
        $this->drawHeader($canvas, $date);

        // 3. ไพ่ทั้ง 10 ใบ (+ เงา · เหรียญเลข · ป้ายกลับหัว · ชื่อตำแหน่ง/ชื่อไพ่)
        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }

            $this->drawCard($canvas, $card, $pos);
        }

        // 4. ลายน้ำท้ายภาพ
        $this->drawFooter($canvas);

        return $canvas;
    }

    /**
     * บันทึก JPEG — ลดคุณภาพทีละขั้นจนไฟล์ ≤ MAX_BYTES (LINE preview ≤ 1MB)
     */
    protected function saveJpeg(GdImage $canvas, string $path): bool
    {
        foreach ([90, 85, 80, 74] as $quality) {
            if (! @imagejpeg($canvas, $path, $quality)) {
                return false;
            }

            clearstatcache(true, $path);
            if (filesize($path) <= self::MAX_BYTES) {
                return true;
            }
        }

        // ยังใหญ่เกิน → เก็บไฟล์คุณภาพต่ำสุดไว้ (เฟซบุ๊กรับได้ · LINE อาจไม่ขึ้นตัวอย่าง)
        Log::warning('CelticSpreadImage: ไฟล์ใหญ่เกินขีด LINE preview', ['path' => $path, 'size' => filesize($path)]);

        return true;
    }

    /**
     * วันที่ดูดวงของบิล (เวลาไทย) — ใช้วันจ่ายเงิน ถ้าไม่มีใช้วันเปิดบิล
     */
    protected function readingDate(FortuneReading $reading): ?CarbonInterface
    {
        $raw = $reading->paid_at ?? $reading->created_at;
        if (! $raw) {
            return null;
        }

        try {
            return Carbon::parse($raw)->setTimezone('Asia/Bangkok');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * แปลงวันที่เป็นรูปแบบไทย เช่น "11 ตุลาคม 2569"
     */
    protected function thaiDate(CarbonInterface $date): string
    {
        $months = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];

        return $date->day.' '.$months[$date->month].' '.($date->year + 543);
    }

    /**
     * สร้าง canvas พื้นหลัง — ม่วงเข้มไล่ลงน้ำเงินกลางคืน + แสงเรืองหลังกางเขน + ขอบมืด (vignette)
     *
     * คำนวณสีทีละพิกเซลบนภาพเล็ก (÷10) แล้วขยายแบบ bilinear — เนียนและเร็ว (ไม่วนลูป 2.9 ล้านพิกเซลใน PHP)
     */
    protected function createCanvas(): GdImage
    {
        $sw = intdiv(self::CANVAS_W, 10);
        $sh = intdiv(self::CANVAS_H, 10);
        $small = imagecreatetruecolor($sw, $sh);

        $gx = self::CROSS_CENTER['x'] / 10;
        $gy = self::CROSS_CENTER['y'] / 10;

        for ($y = 0; $y < $sh; $y++) {
            $t = $y / ($sh - 1);
            for ($x = 0; $x < $sw; $x++) {
                // ไล่แนวตั้ง: ม่วงบน → น้ำเงินกลางคืนล่าง
                $r = 40 - 28 * $t;
                $g = 20 - 12 * $t;
                $b = 76 - 46 * $t;

                // แสงเรืองม่วงหลังกางเขน
                $d = hypot(($x - $gx) / 62, ($y - $gy) / 62);
                $glow = max(0.0, 1 - $d) ** 2;
                $r += 52 * $glow;
                $g += 26 * $glow;
                $b += 92 * $glow;

                // แสงอ่อนหลังหัวภาพ
                $d2 = hypot(($x - $sw / 2) / 70, ($y - 14) / 26);
                $glow2 = max(0.0, 1 - $d2) ** 2;
                $r += 34 * $glow2;
                $g += 20 * $glow2;
                $b += 44 * $glow2;

                // ขอบมืด
                $v = hypot(($x - $sw / 2) / ($sw / 2), ($y - $sh / 2) / ($sh / 2));
                $vig = 1 - 0.38 * min(1.0, $v * $v * 0.7);

                imagesetpixel($small, $x, $y, imagecolorallocate(
                    $small,
                    (int) max(0, min(255, $r * $vig)),
                    (int) max(0, min(255, $g * $vig)),
                    (int) max(0, min(255, $b * $vig)),
                ));
            }
        }

        $canvas = imagescale($small, self::CANVAS_W, self::CANVAS_H, IMG_BILINEAR_FIXED);
        imagedestroy($small);

        if (! $canvas instanceof GdImage) {
            // imagescale ล้ม (ไม่น่าเกิด) → พื้นสีเรียบ ภาพยังออกได้
            $canvas = imagecreatetruecolor(self::CANVAS_W, self::CANVAS_H);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 24, 12, 48));
        }

        imagealphablending($canvas, true);

        return $canvas;
    }

    /**
     * โรยดาว — จุดเล็กหลายระดับความสว่าง + ดาวประกาย 4 แฉกไม่กี่ดวง
     */
    protected function drawStars(GdImage $canvas): void
    {
        for ($i = 0; $i < 170; $i++) {
            $sx = random_int(0, self::CANVAS_W - 1);
            $sy = random_int(0, self::CANVAS_H - 1);
            $alpha = random_int(35, 105);
            $color = imagecolorallocatealpha($canvas, 255, 244, 214, $alpha);
            if (random_int(0, 9) < 7) {
                imagesetpixel($canvas, $sx, $sy, $color);
            } else {
                imagefilledellipse($canvas, $sx, $sy, 3, 3, $color);
            }
        }

        // ดาวประกาย — วางขอบภาพ (ไม่ไปทับไพ่/ข้อความ)
        $sparkles = [[80, 330, 22], [1392, 236, 18], [120, 1760, 16], [1400, 2150, 20], [300, 2150, 14], [700, 360, 14], [60, 2000, 12]];
        foreach ($sparkles as [$sx, $sy, $size]) {
            $this->drawSparkle($canvas, $sx, $sy, $size);
        }
    }

    /**
     * ดาวประกาย 4 แฉก (supersample → ขอบเนียน)
     */
    protected function drawSparkle(GdImage $canvas, int $cx, int $cy, int $size): void
    {
        $box = $size * 2 + 4;
        $this->stamp($canvas, $box, $box, $cx - intdiv($box, 2), $cy - intdiv($box, 2), function (GdImage $im, int $ss) use ($box, $size) {
            $c = $box * $ss / 2;
            $outer = $size * $ss;
            $inner = $outer * 0.16;
            imagefilledellipse($im, (int) $c, (int) $c, (int) ($outer * 0.9), (int) ($outer * 0.9), imagecolorallocatealpha($im, 255, 226, 160, 112));
            imagefilledpolygon($im, [
                $c, $c - $outer, $c + $inner, $c - $inner, $c + $outer, $c, $c + $inner, $c + $inner,
                $c, $c + $outer, $c - $inner, $c + $inner, $c - $outer, $c, $c - $inner, $c - $inner,
            ], imagecolorallocatealpha($im, 255, 240, 200, 18));
        });
    }

    /**
     * วงแหวนทองบางๆ รอบกางเขน + จุดประดับ 24 จุด — อยู่หลังไพ่ (เห็นเป็นช่วงๆ ระหว่างไพ่)
     */
    protected function drawCrossHalo(GdImage $canvas): void
    {
        $cx = self::CROSS_CENTER['x'];
        $cy = self::CROSS_CENTER['y'];

        $this->drawAaRing($canvas, $cx, $cy, 520, self::GOLD, 0.32);
        $this->drawAaRing($canvas, $cx, $cy, 534, self::GOLD, 0.16);
        $this->drawAaRing($canvas, $cx, $cy, 300, self::GOLD, 0.12);

        for ($i = 0; $i < 24; $i++) {
            $a = 2 * M_PI * $i / 24;
            $dx = (int) round($cx + 527 * cos($a));
            $dy = (int) round($cy + 527 * sin($a));
            // จุดที่ตกชิดขอบไพ่จะโผล่ครึ่งดวงดูเหมือนพลาด → ข้าม (จุดใต้ไพ่ถูกไพ่ทับอยู่แล้ว)
            if ($this->isNearCard($dx, $dy, 14)) {
                continue;
            }
            $big = $i % 6 === 0;
            $d = $big ? 9 : 5;
            $this->stamp($canvas, $d + 2, $d + 2, $dx - intdiv($d + 2, 2), $dy - intdiv($d + 2, 2), function (GdImage $im, int $ss) use ($d, $big) {
                $c = (int) (($d + 2) * $ss / 2);
                imagefilledellipse($im, $c, $c, $d * $ss, $d * $ss, imagecolorallocatealpha($im, 236, 202, 128, $big ? 30 : 70));
            });
        }
    }

    /**
     * จุด ($x,$y) อยู่ในกรอบไพ่ใบใดใบหนึ่ง (ขยายออก $pad px) หรือไม่
     */
    protected function isNearCard(int $x, int $y, int $pad): bool
    {
        foreach (self::POSITIONS as $p) {
            [$w, $h] = $p['rotation'] !== 0 ? [self::CARD_H, self::CARD_W] : [self::CARD_W, self::CARD_H];
            if (abs($x - $p['x']) <= $w / 2 + $pad && abs($y - $p['y']) <= $h / 2 + $pad) {
                return true;
            }
        }

        return false;
    }

    /**
     * วงกลมเส้นบางขอบเนียน — วาดเป็นเส้นสั้นๆ ด้วย imageantialias
     *
     * GD วาดเส้น antialias ได้เฉพาะสีทึบ → ผสมสีทองกับพื้นหลังตรงจุดนั้นเอง ($opacity) แล้วค่อยวาด
     */
    protected function drawAaRing(GdImage $canvas, int $cx, int $cy, int $radius, array $rgb, float $opacity): void
    {
        $hasAa = function_exists('imageantialias');
        if ($hasAa) {
            imageantialias($canvas, true);
        }

        $steps = max(120, (int) ceil(2 * M_PI * $radius / 5));
        $px = $cx + $radius;
        $py = (float) $cy;
        for ($i = 1; $i <= $steps; $i++) {
            $a = 2 * M_PI * $i / $steps;
            $nx = $cx + $radius * cos($a);
            $ny = $cy + $radius * sin($a);
            $mx = (int) round(($px + $nx) / 2);
            $my = (int) round(($py + $ny) / 2);

            if ($mx >= 0 && $my >= 0 && $mx < self::CANVAS_W && $my < self::CANVAS_H) {
                $bg = imagecolorat($canvas, $mx, $my);
                $color = imagecolorallocate(
                    $canvas,
                    (int) round((($bg >> 16) & 0xFF) * (1 - $opacity) + $rgb[0] * $opacity),
                    (int) round((($bg >> 8) & 0xFF) * (1 - $opacity) + $rgb[1] * $opacity),
                    (int) round(($bg & 0xFF) * (1 - $opacity) + $rgb[2] * $opacity),
                );
                imageline($canvas, (int) round($px), (int) round($py), (int) round($nx), (int) round($ny), $color);
            }

            $px = $nx;
            $py = $ny;
        }

        if ($hasAa) {
            imageantialias($canvas, false);
        }
    }

    /**
     * วาด header: CELTIC CROSS + หัวเรื่องไทย + แม่หมอจันทรา · วันที่ + เส้นคั่นประดับ
     */
    protected function drawHeader(GdImage $canvas, ?CarbonInterface $date): void
    {
        $font = $this->findThaiFont();
        $cx = intdiv(self::CANVAS_W, 2);
        $gold = $this->color($canvas, self::GOLD);

        if (! $font) {
            imagestring($canvas, 5, 60, 60, 'Celtic Cross Tarot Reading', $gold);

            return;
        }

        $this->drawSpacedCentered($canvas, 17, $cx, 92, $gold, $font, 'CELTIC CROSS TAROT', 7);
        $this->drawCenteredText($canvas, 40, $cx, 160, $this->color($canvas, self::CREAM), $font, 'ไพ่ 10 ใบของเจ้าชะตา');

        $sub = 'แม่หมอจันทรา';
        if ($date) {
            $sub .= '  ·  ดูดวงวันที่ '.$this->thaiDate($date);
        }
        $this->drawCenteredText($canvas, 16, $cx, 206, $this->color($canvas, self::LAVENDER), $font, $sub);

        $this->drawOrnamentDivider($canvas, $cx, 240, 260);
    }

    /**
     * เส้นคั่นประดับ: ──── ◆ ──── (เส้นจางออกปลาย)
     */
    protected function drawOrnamentDivider(GdImage $canvas, int $cx, int $y, int $halfWidth): void
    {
        for ($i = 0; $i < $halfWidth - 14; $i++) {
            // จางออกไปทางปลาย
            $alpha = (int) round(40 + 87 * ($i / ($halfWidth - 14)));
            $color = imagecolorallocatealpha($canvas, self::GOLD[0], self::GOLD[1], self::GOLD[2], min(127, $alpha));
            imagesetpixel($canvas, $cx + 14 + $i, $y, $color);
            imagesetpixel($canvas, $cx - 14 - $i, $y, $color);
        }

        $this->stamp($canvas, 16, 16, $cx - 8, $y - 8, function (GdImage $im, int $ss) {
            $c = 8 * $ss;
            $r = 7 * $ss;
            imagefilledpolygon($im, [$c, $c - $r, $c + $r, $c, $c, $c + $r, $c - $r, $c], imagecolorallocate($im, ...self::GOLD));
        });
    }

    /**
     * วาดลายน้ำท้ายภาพ
     */
    protected function drawFooter(GdImage $canvas): void
    {
        $font = $this->findThaiFont();
        if (! $font) {
            return;
        }

        $cx = intdiv(self::CANVAS_W, 2);
        $this->drawOrnamentDivider($canvas, $cx, self::CANVAS_H - 80, 200);
        $this->drawCenteredText($canvas, 14, $cx, self::CANVAS_H - 38, $this->color($canvas, self::GOLD_MUTED), $font, 'thaiprompt.online  ·  แม่หมอจันทรา');
    }

    /**
     * วาดไพ่ 1 ใบที่ตำแหน่ง position (+ เงา · กรอบ · เหรียญเลข · ป้ายกลับหัว · ป้ายชื่อใต้ไพ่)
     *
     * @param  array  $card  ['image_url', 'is_reversed', 'card_name_th', ...]
     * @param  int  $position  1-10
     */
    protected function drawCard(GdImage $canvas, array $card, int $position): void
    {
        $coords = self::POSITIONS[$position] ?? null;
        if (! $coords) {
            return;
        }

        $isReversed = ! empty($card['is_reversed']);
        $face = $this->buildCardFace($card['image_url'] ?? null, $isReversed, $coords['rotation']);

        if ($face) {
            $w = imagesx($face);
            $h = imagesy($face);
        } else {
            // ไพ่ยังไม่มีรูป / GD อ่านไม่ได้ (เช่น SVG) → การ์ดหลังไพ่ + ชื่อไพ่ไทย ขนาดเท่าไพ่จริง
            [$w, $h] = $coords['rotation'] !== 0 ? [self::CARD_H, self::CARD_W] : [self::CARD_W, self::CARD_H];
        }

        $left = $coords['x'] - intdiv($w, 2);
        $top = $coords['y'] - intdiv($h, 2);

        $this->drawCardShadow($canvas, $left, $top, $w, $h);

        if ($face) {
            imagecopy($canvas, $face, $left, $top, 0, 0, $w, $h);
            imagedestroy($face);
        } else {
            $this->drawPlaceholderCard($canvas, $left, $top, $w, $h, $card);
        }

        // กรอบทองบางรอบไพ่ (เว้นช่อง 3px) — ให้ไพ่ดูเหมือนวางบนผ้าปู
        imagerectangle($canvas, $left - 4, $top - 4, $left + $w + 3, $top + $h + 3, imagecolorallocatealpha($canvas, self::GOLD[0], self::GOLD[1], self::GOLD[2], 78));

        if ($isReversed) {
            $this->drawReversedTag($canvas, $left + intdiv($w, 2), $top + $h - 14);
        }

        $this->drawPositionBadge($canvas, $left + 2, $top + 2, $position);
        $this->drawCardLabel($canvas, $coords['x'], $top + $h, $position, $card, $coords['label_w']);
    }

    /**
     * โหลดรูปไพ่ → ย่อแบบ contain ให้พอดี CARD_W×CARD_H + ชาร์ป + หมุน (กลับหัว 180° / ไพ่ขวาง 90°)
     *
     * 🩹 (2026-06-02, user) เดิมยัด source เต็มใบลงกรอบตายตัว → ไพ่ที่สัดส่วนไม่ตรงถูกบีบ = ภาพเพี้ยน
     *   ใช้ min(W/srcW, H/srcH) → การ์ดคงสัดส่วนจริง
     */
    protected function buildCardFace(?string $imageUrl, bool $isReversed, int $rotation): ?GdImage
    {
        $src = $this->loadImageFromUrl($imageUrl);
        if (! $src) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $scale = min(self::CARD_W / $srcW, self::CARD_H / $srcH);
        $drawW = max(1, (int) round($srcW * $scale));
        $drawH = max(1, (int) round($srcH * $scale));

        $face = imagecreatetruecolor($drawW, $drawH);
        imagecopyresampled($face, $src, 0, 0, 0, 0, $drawW, $drawH, $srcW, $srcH);
        imagedestroy($src);

        // ย่อ ~2.7 เท่า (536→200) ภาพจะนุ่มไป → ชาร์ปเบาๆ ให้ลายเส้นไพ่คม
        imageconvolution($face, [[-1, -1, -1], [-1, 24, -1], [-1, -1, -1]], 16, 0);

        $angle = ($isReversed ? 180 : 0) + $rotation;
        if ($angle % 360 !== 0) {
            $rotated = imagerotate($face, $angle, 0);
            imagedestroy($face);
            $face = $rotated ?: null;
        }

        return $face;
    }

    /**
     * เงานุ่มใต้ไพ่ — ซ้อนสี่เหลี่ยมโปร่งใสหลายชั้น (แทน blur ที่ GD ทำไม่ได้ดี)
     */
    protected function drawCardShadow(GdImage $canvas, int $left, int $top, int $w, int $h): void
    {
        for ($i = 16; $i >= 1; $i--) {
            $color = imagecolorallocatealpha($canvas, 4, 0, 14, 119);
            imagefilledrectangle($canvas, $left - $i, $top - $i + 7, $left + $w + $i, $top + $h + $i + 7, $color);
        }
    }

    /**
     * เหรียญเลขตำแหน่ง — ขอบทอง ใจกลางม่วงเข้ม เลขทอง (วางทับมุมซ้ายบนของไพ่)
     */
    protected function drawPositionBadge(GdImage $canvas, int $cx, int $cy, int $position): void
    {
        $font = $this->findThaiFont();
        $box = 54;

        $this->stamp($canvas, $box, $box, $cx - intdiv($box, 2), $cy - intdiv($box, 2), function (GdImage $im, int $ss) use ($box, $font, $position) {
            $c = (int) ($box * $ss / 2);
            imagefilledellipse($im, $c, $c + 2 * $ss, 50 * $ss, 50 * $ss, imagecolorallocatealpha($im, 0, 0, 0, 80)); // เงา
            imagefilledellipse($im, $c, $c, 48 * $ss, 48 * $ss, imagecolorallocate($im, 128, 92, 36));               // ขอบทองเข้ม
            imagefilledellipse($im, $c, $c, 44 * $ss, 44 * $ss, imagecolorallocate($im, 240, 206, 128));             // ทอง
            imagefilledellipse($im, $c, $c, 36 * $ss, 36 * $ss, imagecolorallocate($im, 46, 22, 78));                // ใจกลาง

            if ($font) {
                $text = (string) $position;
                $size = ($position >= 10 ? 14 : 16) * $ss;
                $bb = imagettfbbox($size, 0, $font, $text);
                $tw = $bb[2] - $bb[0];
                $th = $bb[1] - $bb[7];
                imagettftext($im, $size, 0, (int) round($c - $tw / 2 - $bb[0]), (int) round($c + $th / 2), imagecolorallocate($im, ...self::GOLD), $font, $text);
            }
        });
    }

    /**
     * ป้าย "กลับหัว" สีกุหลาบเข้ม วางบนขอบล่างของไพ่ (กึ่งกลาง $cx · ขอบล่างป้ายที่ $bottom)
     */
    protected function drawReversedTag(GdImage $canvas, int $cx, int $bottom): void
    {
        $font = $this->findThaiFont();
        if (! $font) {
            return;
        }

        $w = 100;
        $h = 30;
        $this->stamp($canvas, $w, $h, $cx - intdiv($w, 2), $bottom - $h, function (GdImage $im, int $ss) use ($w, $h, $font) {
            $this->fillPill($im, 0, 0, $w * $ss, $h * $ss, imagecolorallocate($im, 236, 150, 170));
            $this->fillPill($im, 2 * $ss, 2 * $ss, ($w - 2) * $ss, ($h - 2) * $ss, imagecolorallocate($im, 112, 22, 54));

            $size = 13 * $ss;
            $bb = imagettfbbox($size, 0, $font, 'กลับหัว');
            $tw = $bb[2] - $bb[0];
            imagettftext($im, $size, 0, (int) round(($w * $ss - $tw) / 2 - $bb[0]), (int) round($h * $ss / 2 + 6 * $ss), imagecolorallocate($im, 255, 222, 230), $font, 'กลับหัว');
        }, 3);
    }

    /**
     * เติมรูปทรงแคปซูล (สี่เหลี่ยมหัวมน) — สีทึบเท่านั้น (สีโปร่งใสจะซ้อนเข้มตรงรอยต่อ)
     */
    protected function fillPill(GdImage $im, int $x1, int $y1, int $x2, int $y2, int $color): void
    {
        $h = $y2 - $y1;
        $r = intdiv($h, 2);
        imagefilledellipse($im, $x1 + $r, $y1 + $r, $h, $h, $color);
        imagefilledellipse($im, $x2 - $r, $y1 + $r, $h, $h, $color);
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2 - 1, $color);
    }

    /**
     * ป้ายใต้ไพ่: ชื่อตำแหน่ง (ทองหม่น) + ชื่อไพ่ไทย (ครีม ตัวใหญ่) — ย่อฟอนต์เองถ้ายาวเกินช่อง
     */
    protected function drawCardLabel(GdImage $canvas, int $cx, int $cardBottom, int $position, array $card, int $maxWidth): void
    {
        $font = $this->findThaiFont();
        if (! $font) {
            return;
        }

        $positionName = FortuneReading::CELTIC_POSITIONS[$position]['name'] ?? '';
        $cardName = ThaiFontText::safe((string) ($card['card_name_th'] ?? ''), ThaiFontText::safe((string) ($card['card_name_en'] ?? ''), ''));

        if ($positionName !== '') {
            $this->drawCenteredText($canvas, 14, $cx, $cardBottom + 33, $this->color($canvas, self::GOLD_MUTED), $font, $positionName, $maxWidth, 10);
        }
        if ($cardName !== '') {
            $this->drawCenteredText($canvas, 18, $cx, $cardBottom + 64, $this->color($canvas, self::CREAM), $font, $cardName, $maxWidth, 12);
        }
    }

    /**
     * วาด placeholder card ถ้าโหลดรูปไพ่ไม่ได้
     *
     * 🎴 (2026-06-02) การ์ดหลังไพ่ลึกลับ + ขอบทอง 2 ชั้น + ชื่อไพ่ไทยกลางการ์ด — ดูตั้งใจ ไม่เหมือน error box
     */
    protected function drawPlaceholderCard(GdImage $canvas, int $px, int $py, int $w, int $h, array $card = []): void
    {
        $bg = imagecolorallocate($canvas, 48, 26, 78);
        $border = imagecolorallocate($canvas, 210, 175, 95);
        imagefilledrectangle($canvas, $px, $py, $px + $w - 1, $py + $h - 1, $bg);
        imagerectangle($canvas, $px, $py, $px + $w - 1, $py + $h - 1, $border);
        imagerectangle($canvas, $px + 5, $py + 5, $px + $w - 6, $py + $h - 6, $border);

        $font = $this->findThaiFont();
        $cardName = ThaiFontText::safe((string) ($card['card_name_th'] ?? ''), '');
        if (! $font || $cardName === '') {
            return;
        }

        // ชื่อไพ่ไทยบรรทัดเดียวกลางการ์ด (ย่อฟอนต์ให้พอดี) — ไทยไม่มีช่องว่างระหว่างคำ ตัดบรรทัดเองจะขาดกลางคำ
        $this->drawCenteredText($canvas, 16, $px + intdiv($w, 2), $py + intdiv($h, 2) + 8, $this->color($canvas, self::CREAM), $font, $cardName, $w - 28, 10);
    }

    /**
     * วาดข้อความกึ่งกลางที่ $cx — ถ้ากำหนด $maxWidth จะย่อฟอนต์ทีละ 0.5pt จนพอดี (ไม่ต่ำกว่า $minSize)
     */
    protected function drawCenteredText(GdImage $img, float $size, int $cx, int $y, int $color, string $font, string $text, ?int $maxWidth = null, float $minSize = 10): void
    {
        [$base] = ThaiFontText::liftStackedToneMarks($text);

        if ($maxWidth) {
            while ($size > $minSize && $this->bboxWidth($size, $font, $base) > $maxWidth) {
                $size -= 0.5;
            }
        }

        $bb = imagettfbbox($size, 0, $font, $base);
        $left = min($bb[0], $bb[6]);
        $width = max($bb[2], $bb[4]) - $left;

        ThaiFontText::draw($img, $size, (int) round($cx - $width / 2 - $left), $y, $color, $font, $text);
    }

    /**
     * วาดตัวอักษรละตินแบบเว้นระยะ (letter-spacing) กึ่งกลางที่ $cx
     */
    protected function drawSpacedCentered(GdImage $img, float $size, int $cx, int $y, int $color, string $font, string $text, int $spacing): void
    {
        $chars = mb_str_split($text);
        $advances = array_map(fn (string $ch) => ThaiFontText::advance($size, $font, $ch), $chars);
        $total = array_sum($advances) + $spacing * (count($chars) - 1);

        $x = (int) round($cx - $total / 2);
        foreach ($chars as $i => $ch) {
            if ($ch !== ' ') {
                imagettftext($img, $size, 0, $x, $y, $color, $font, $ch);
            }
            $x += $advances[$i] + $spacing;
        }
    }

    protected function bboxWidth(float $size, string $font, string $text): int
    {
        $bb = imagettfbbox($size, 0, $font, $text);

        return (int) (max($bb[2], $bb[4]) - min($bb[0], $bb[6]));
    }

    /**
     * วาดรูปทรงเล็กๆ แบบ supersample (วาดใหญ่ ×$ss บนพื้นโปร่งใส แล้วย่อลงมาแปะ) → ขอบเนียน
     * GD วาดวงกลม/โพลิกอนขอบหยัก และ imageantialias ใช้กับสีโปร่งใสไม่ได้
     *
     * @param  callable(GdImage,int):void  $draw  รับภาพขนาดใหญ่ + ตัวคูณ $ss
     */
    protected function stamp(GdImage $canvas, int $w, int $h, int $x, int $y, callable $draw, int $ss = 4): void
    {
        $big = imagecreatetruecolor($w * $ss, $h * $ss);
        imagealphablending($big, false);
        imagefilledrectangle($big, 0, 0, $w * $ss - 1, $h * $ss - 1, imagecolorallocatealpha($big, 0, 0, 0, 127));
        imagealphablending($big, true);

        $draw($big, $ss);

        $small = imagecreatetruecolor($w, $h);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagecopyresampled($small, $big, 0, 0, 0, 0, $w, $h, $w * $ss, $h * $ss);
        imagedestroy($big);

        imagecopy($canvas, $small, $x, $y, 0, 0, $w, $h);
        imagedestroy($small);
    }

    protected function color(GdImage $img, array $rgb): int
    {
        return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
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

        $img = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default => null,
        };

        // PNG/GIF แบบ palette → แปลงเป็น truecolor ก่อน (imageconvolution/imagerotate ทำงานกับ truecolor)
        if ($img instanceof GdImage && ! imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }

        return $img ?: null;
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
     * หา TTF font ภาษาไทย (เหมือน DailyHoroscopeAutoPostService) — จำผลไว้ (ถูกเรียกหลายสิบครั้งต่อภาพ)
     *
     * เช็คด้วย FontFile::isReal() ไม่ใช่ is_file() — (2026-09-12) resources/fonts/DejaVuSans.ttf เคยเป็นหน้า HTML
     * ไฟล์ที่มีอยู่แต่ไม่ใช่ฟอนต์ต้องถูกข้าม ไม่ใช่ถูกคืนไปให้ GD วาดข้อความว่างเปล่า
     */
    protected function findThaiFont(): ?string
    {
        if ($this->fontResolved) {
            return $this->fontPath;
        }

        $this->fontResolved = true;

        return $this->fontPath = FontFile::firstReal([
            // ⭐ (2026-06-02) ฟอนต์ไทยที่ฝังมากับ repo จริง — ต้องมาก่อน!
            //   เดิม list หาแต่ Sarabun/Kanit ที่ไม่มีบน prod → คืน null → ข้อความไทยทั้งหมด
            //   (subtitle/legend/เลขตำแหน่ง) หายเงียบๆ — เห็นแต่หัวอังกฤษ bitmap
            resource_path('fonts/NotoSansThai-Bold.ttf'),
            public_path('fonts/Sarabun-Bold.ttf'),
            public_path('fonts/Kanit-Bold.ttf'),
            public_path('fonts/THSarabun.ttf'),
            resource_path('fonts/Sarabun-Bold.ttf'),
            resource_path('fonts/Kanit-Bold.ttf'),
            '/usr/share/fonts/truetype/sarabun/Sarabun-Bold.ttf',
            '/usr/share/fonts/truetype/thai/TlwgTypist.ttf',
            'C:/Windows/Fonts/Tahoma.ttf',
            // DejaVu Sans ไม่มีอักษรไทย — อยู่ท้ายสุด ให้อย่างน้อยตัวละติน/ตัวเลขขึ้นเมื่อหาฟอนต์ไทยไม่เจอเลย
            resource_path('fonts/DejaVuSans.ttf'),
        ]);
    }
}
