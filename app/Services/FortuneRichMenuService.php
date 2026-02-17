<?php

namespace App\Services;

use App\Models\FortuneTellingSetting;
use App\Models\LineRichMenu;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FortuneRichMenuService — สร้างภาพ Rich Menu + deploy ไป LINE API
 *
 * สร้างเมนูแม่หมอจันทรา 6 ปุ่ม:
 * 🔮 ดูดวง | ✨ ดูดวงละเอียด | 📖 ดูคำทำนายล่าสุด
 * 📊 เช็คสิทธิ์ | ⚠️ แจ้งปัญหาโอน | ℹ️ วิธีใช้งาน
 */
class FortuneRichMenuService
{
    protected const WIDTH = 2500;

    protected const HEIGHT = 1686;

    protected const ROW_HEIGHT = 843;

    protected const COL_WIDTH_1 = 833;

    protected const COL_WIDTH_2 = 834;

    protected const COL_WIDTH_3 = 833;

    protected FortuneTellingSetting $settings;

    protected LineFortuneService $lineService;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->lineService = new LineFortuneService($this->settings);
    }

    /**
     * ดึง LineFortuneService instance
     *
     * ใช้สำหรับเรียก API method โดยตรง เช่น setDefaultRichMenu
     *
     * @return LineFortuneService
     */
    public function getLineService(): LineFortuneService
    {
        return $this->lineService;
    }

    /**
     * สร้างภาพ Rich Menu + deploy ไป LINE API + ตั้งเป็น default
     *
     * @return array ผลลัพธ์ ['success', 'rich_menu_id', 'menu', 'error']
     */
    public function generateAndDeploy(): array
    {
        try {
            // 1. สร้างภาพ PNG
            $pngData = $this->generateImage();

            if (empty($pngData)) {
                return ['success' => false, 'error' => 'ไม่สามารถสร้างภาพ Rich Menu ได้'];
            }

            // 2. สร้าง Rich Menu structure บน LINE Platform
            $richMenuData = [
                'size' => ['width' => self::WIDTH, 'height' => self::HEIGHT],
                'selected' => true,
                'name' => 'แม่หมอจันทรา - เมนูดูดวง',
                'chatBarText' => '🔮 เมนูดูดวง',
                'areas' => $this->getRichMenuAreas(),
            ];

            $richMenuId = $this->lineService->createRichMenu($richMenuData);
            if (! $richMenuId) {
                return ['success' => false, 'error' => 'สร้าง Rich Menu บน LINE ไม่สำเร็จ'];
            }

            // 3. อัปโหลดภาพ PNG
            $uploaded = $this->lineService->uploadRichMenuImage($richMenuId, $pngData);
            if (! $uploaded) {
                // ลบ Rich Menu ที่สร้างไว้
                $this->lineService->deleteRichMenu($richMenuId);

                return ['success' => false, 'error' => 'อัปโหลดภาพ Rich Menu ไม่สำเร็จ'];
            }

            // 4. ตั้งเป็น default สำหรับทุก user
            $this->lineService->setDefaultRichMenu($richMenuId);

            // 5. บันทึก image ลง storage
            $imagePath = $this->saveImageToStorage($pngData);

            // 6. ยกเลิก Rich Menu เก่าใน DB
            LineRichMenu::where('name', 'fortune-telling-bot')
                ->where('is_active', true)
                ->update(['is_active' => false, 'is_default' => false]);

            // 7. บันทึกลง DB
            $menu = LineRichMenu::create([
                'rich_menu_id' => $richMenuId,
                'name' => 'fortune-telling-bot',
                'description' => 'Rich Menu สำหรับบอทแม่หมอจันทรา (auto-generated)',
                'size' => 'full',
                'selected' => true,
                'chat_bar_text' => '🔮 เมนูดูดวง',
                'areas' => $this->getRichMenuAreas(),
                'image_path' => $imagePath,
                'is_default' => true,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            Log::info('FortuneRichMenu: Deploy สำเร็จ', [
                'rich_menu_id' => $richMenuId,
                'menu_id' => $menu->id,
            ]);

            return [
                'success' => true,
                'rich_menu_id' => $richMenuId,
                'menu' => $menu,
            ];

        } catch (\Throwable $e) {
            Log::error('FortuneRichMenu: Deploy ล้มเหลว', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * สร้างภาพ Rich Menu ด้วย GD library
     *
     * ธีม: จักรวาลม่วง-ทอง + ดาว + พระจันทร์เสี้ยว
     *
     * @return string PNG binary data
     */
    public function generateImage(): string
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $font = $this->getThaiFont();

        // === สี ===
        $deepPurple = $this->hexColor($img, '#0d0521');
        $purple1 = $this->hexColor($img, '#1a0a3e');
        $purple2 = $this->hexColor($img, '#2d1070');
        $purple3 = $this->hexColor($img, '#3B1585');
        $purpleBright = $this->hexColor($img, '#7C3AED');
        $gold = $this->hexColor($img, '#FFD700');
        $goldDark = $this->hexColor($img, '#B8860B');
        $white = $this->hexColor($img, '#FFFFFF');
        $lightPurple = $this->hexColor($img, '#C4B5FD');
        $orange = $this->hexColor($img, '#FF6B35');
        $redOrange = $this->hexColor($img, '#D84315');
        $lightGray = $this->hexColor($img, '#E0E0E0');
        $gridLine = $this->hexColor($img, '#4C1D95');

        // === พื้นหลัง gradient ===
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / self::HEIGHT;
            $r = (int) (13 + (26 - 13) * $ratio);
            $g = (int) (5 + (10 - 5) * $ratio);
            $b = (int) (33 + (62 - 33) * $ratio);
            $lineColor = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::WIDTH, $y, $lineColor);
        }

        // === ดาวตกแต่ง ===
        for ($i = 0; $i < 200; $i++) {
            $sx = rand(10, self::WIDTH - 10);
            $sy = rand(10, self::HEIGHT - 10);
            $sr = rand(1, 4);
            $starAlpha = rand(40, 110);
            $starColor = imagecolorallocatealpha($img, 255, 255, 255, $starAlpha);
            imagefilledellipse($img, $sx, $sy, $sr * 2, $sr * 2, $starColor);
        }

        // === พระจันทร์เสี้ยว (มุมขวาบน) ===
        $moonX = self::WIDTH - 250;
        $moonY = 180;
        imagefilledellipse($img, $moonX, $moonY, 140, 140, $gold);
        imagefilledellipse($img, $moonX + 30, $moonY - 20, 130, 130, $this->hexColor($img, '#1a0a3e'));

        // === เส้นแบ่ง grid ===
        // เส้นแนวนอนกลาง
        $this->drawThickLine($img, 0, self::ROW_HEIGHT, self::WIDTH, self::ROW_HEIGHT, $gridLine, 3);
        // เส้นแนวตั้ง Row 1
        $this->drawThickLine($img, self::COL_WIDTH_1, 0, self::COL_WIDTH_1, self::ROW_HEIGHT, $gridLine, 2);
        $this->drawThickLine($img, self::COL_WIDTH_1 + self::COL_WIDTH_2, 0, self::COL_WIDTH_1 + self::COL_WIDTH_2, self::ROW_HEIGHT, $gridLine, 2);
        // เส้นแนวตั้ง Row 2
        $this->drawThickLine($img, self::COL_WIDTH_1, self::ROW_HEIGHT, self::COL_WIDTH_1, self::HEIGHT, $gridLine, 2);
        $this->drawThickLine($img, self::COL_WIDTH_1 + self::COL_WIDTH_2, self::ROW_HEIGHT, self::COL_WIDTH_1 + self::COL_WIDTH_2, self::HEIGHT, $gridLine, 2);

        // === ปุ่ม 1: ดูดวง (Row1, Col1) — สว่างที่สุด ===
        $btn1Cx = self::COL_WIDTH_1 / 2;
        $btn1Cy = self::ROW_HEIGHT / 2;
        // Glow effect
        for ($r = 200; $r > 0; $r -= 10) {
            $alpha = (int) (127 - (127 - 100) * $r / 200);
            $glowColor = imagecolorallocatealpha($img, 124, 58, 237, $alpha);
            imagefilledellipse($img, (int) $btn1Cx, (int) $btn1Cy, $r * 2, $r * 2, $glowColor);
        }
        // ไอคอน: วงกลมลูกแก้วม่วง
        $this->drawIcon($img, $btn1Cx, $btn1Cy - 100, 'crystal_ball', $purpleBright, $gold, $white);
        $this->drawCenteredText($img, $font, 62, $btn1Cx, $btn1Cy + 50, 'ดูดวง', $gold);
        $this->drawCenteredText($img, $font, 28, $btn1Cx, $btn1Cy + 120, 'ฟรี! ไม่มีค่าใช้จ่าย', $lightPurple);

        // === ปุ่ม 2: ดูดวงละเอียด (Row1, Col2) — สีทอง ===
        $btn2Cx = self::COL_WIDTH_1 + self::COL_WIDTH_2 / 2;
        $btn2Cy = self::ROW_HEIGHT / 2;
        // พื้นหลัง gradient ทอง
        for ($y = 0; $y < self::ROW_HEIGHT; $y++) {
            $ratio = $y / self::ROW_HEIGHT;
            $r2 = (int) (140 + (180 - 140) * $ratio);
            $g2 = (int) (100 + (130 - 100) * $ratio);
            $b2 = (int) (5 + (20 - 5) * $ratio);
            $goldGrad = imagecolorallocatealpha($img, $r2, $g2, $b2, 90);
            imageline($img, self::COL_WIDTH_1 + 2, $y, self::COL_WIDTH_1 + self::COL_WIDTH_2 - 2, $y, $goldGrad);
        }
        // ไอคอน: ดาวทอง
        $this->drawIcon($img, $btn2Cx, $btn2Cy - 100, 'star', $gold, $goldDark, $white);
        $this->drawCenteredText($img, $font, 48, $btn2Cx, $btn2Cy + 30, 'ดูดวงละเอียด', $white);
        $price = number_format($this->lineService->getDeepReadingPrice(), 0);
        $this->drawCenteredText($img, $font, 36, $btn2Cx, $btn2Cy + 100, "{$price} บาท", $gold);
        $this->drawCenteredText($img, $font, 24, $btn2Cx, $btn2Cy + 160, 'วิเคราะห์เจาะลึก + ดวงชะตา', $lightPurple);

        // === ปุ่ม 3: ดูคำทำนายล่าสุด (Row1, Col3) ===
        $btn3Cx = self::COL_WIDTH_1 + self::COL_WIDTH_2 + self::COL_WIDTH_3 / 2;
        $btn3Cy = self::ROW_HEIGHT / 2;
        // ไอคอน: หนังสือ/ม้วน
        $this->drawIcon($img, $btn3Cx, $btn3Cy - 80, 'scroll', $lightPurple, $purple2, $white);
        $this->drawCenteredText($img, $font, 38, $btn3Cx, $btn3Cy + 50, 'ดูคำทำนาย', $white);
        $this->drawCenteredText($img, $font, 38, $btn3Cx, $btn3Cy + 110, 'ล่าสุด', $white);

        // === ปุ่ม 4: เช็คสิทธิ์ (Row2, Col1) ===
        $btn4Cx = self::COL_WIDTH_1 / 2;
        $btn4Cy = self::ROW_HEIGHT + self::ROW_HEIGHT / 2;
        // ไอคอน: กราฟ/บาร์
        $this->drawIcon($img, $btn4Cx, $btn4Cy - 80, 'chart', $purpleBright, $lightPurple, $white);
        $this->drawCenteredText($img, $font, 38, $btn4Cx, $btn4Cy + 50, 'เช็คสิทธิ์', $white);
        $this->drawCenteredText($img, $font, 24, $btn4Cx, $btn4Cy + 110, 'ดูรอบฟรีที่เหลือ', $lightPurple);

        // === ปุ่ม 5: แจ้งปัญหาโอน (Row2, Col2) — สีส้ม/แดงเด่น ===
        $btn5Cx = self::COL_WIDTH_1 + self::COL_WIDTH_2 / 2;
        $btn5Cy = self::ROW_HEIGHT + self::ROW_HEIGHT / 2;
        // พื้นหลังส้มเรือง
        for ($y = self::ROW_HEIGHT + 2; $y < self::HEIGHT - 2; $y++) {
            $ratio = ($y - self::ROW_HEIGHT) / self::ROW_HEIGHT;
            $r5 = (int) (100 + (60 - 100) * $ratio);
            $g5 = (int) (20 + (10 - 20) * $ratio);
            $b5 = (int) (10 + (30 - 10) * $ratio);
            $orangeGrad = imagecolorallocatealpha($img, $r5, $g5, $b5, 85);
            imageline($img, self::COL_WIDTH_1 + 2, $y, self::COL_WIDTH_1 + self::COL_WIDTH_2 - 2, $y, $orangeGrad);
        }
        // ไอคอน: สามเหลี่ยมเตือน
        $this->drawIcon($img, $btn5Cx, $btn5Cy - 100, 'warning', $orange, $redOrange, $white);
        $this->drawCenteredText($img, $font, 36, $btn5Cx, $btn5Cy + 20, 'แจ้งปัญหา', $this->hexColor($img, '#FFA726'));
        $this->drawCenteredText($img, $font, 36, $btn5Cx, $btn5Cy + 80, 'การโอนเงิน', $this->hexColor($img, '#FFA726'));
        $this->drawCenteredText($img, $font, 22, $btn5Cx, $btn5Cy + 140, 'โอนแล้วไม่ได้คำทำนาย?', $lightGray);

        // === ปุ่ม 6: วิธีใช้งาน (Row2, Col3) ===
        $btn6Cx = self::COL_WIDTH_1 + self::COL_WIDTH_2 + self::COL_WIDTH_3 / 2;
        $btn6Cy = self::ROW_HEIGHT + self::ROW_HEIGHT / 2;
        // ไอคอน: วงกลม i
        $this->drawIcon($img, $btn6Cx, $btn6Cy - 80, 'info', $purpleBright, $lightPurple, $white);
        $this->drawCenteredText($img, $font, 38, $btn6Cx, $btn6Cy + 50, 'วิธีใช้งาน', $white);
        $this->drawCenteredText($img, $font, 24, $btn6Cx, $btn6Cy + 110, 'คู่มือ & ช่วยเหลือ', $lightPurple);

        // === Branding ด้านบน ===
        $this->drawCenteredText($img, $font, 30, self::WIDTH / 2, 50, '~~ แม่หมอจันทราพยากรณ์ ~~', $gold);

        // === Export PNG ===
        ob_start();
        imagepng($img, null, 7);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }

    /**
     * กำหนดพื้นที่คลิก 6 ปุ่มของ Rich Menu
     *
     * @return array LINE Rich Menu areas format
     */
    public function getRichMenuAreas(): array
    {
        return [
            // Row 1, Col 1: 🔮 ดูดวง
            [
                'bounds' => ['x' => 0, 'y' => 0, 'width' => self::COL_WIDTH_1, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'message', 'text' => 'ดูดวง'],
            ],
            // Row 1, Col 2: ✨ ดูดวงละเอียด
            [
                'bounds' => ['x' => self::COL_WIDTH_1, 'y' => 0, 'width' => self::COL_WIDTH_2, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=deep_reading', 'displayText' => 'ดูดวงละเอียด'],
            ],
            // Row 1, Col 3: 📖 ดูคำทำนายล่าสุด
            [
                'bounds' => ['x' => self::COL_WIDTH_1 + self::COL_WIDTH_2, 'y' => 0, 'width' => self::COL_WIDTH_3, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=view_last_reading', 'displayText' => 'ดูคำทำนายล่าสุด'],
            ],
            // Row 2, Col 1: 📊 เช็คสิทธิ์
            [
                'bounds' => ['x' => 0, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH_1, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=check_remaining', 'displayText' => 'เช็คสิทธิ์'],
            ],
            // Row 2, Col 2: ⚠️ แจ้งปัญหาโอน
            [
                'bounds' => ['x' => self::COL_WIDTH_1, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH_2, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=report_payment', 'displayText' => 'แจ้งปัญหาโอน'],
            ],
            // Row 2, Col 3: ℹ️ วิธีใช้งาน
            [
                'bounds' => ['x' => self::COL_WIDTH_1 + self::COL_WIDTH_2, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH_3, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=help', 'displayText' => 'วิธีใช้งาน'],
            ],
        ];
    }

    // === Private Helper Methods ===

    /**
     * บันทึกภาพลง storage
     */
    protected function saveImageToStorage(string $pngData): ?string
    {
        try {
            $filename = 'fortune-rich-menu-'.Str::random(8).'.png';
            $path = "rich-menus/{$filename}";
            Storage::disk('public')->put($path, $pngData);

            return $path;
        } catch (\Throwable $e) {
            Log::error('FortuneRichMenu: บันทึกภาพล้มเหลว', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * หา path ฟอนต์ภาษาไทย (pattern จาก FortuneChartService)
     */
    protected function getThaiFont(): string
    {
        $localFont = resource_path('fonts/NotoSansThai-Bold.ttf');
        if (@file_exists($localFont)) {
            return $localFont;
        }

        $systemPaths = [
            '/usr/share/fonts/truetype/noto/NotoSansThai-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($systemPaths as $path) {
            if (@file_exists($path)) {
                return $path;
            }
        }

        return $localFont;
    }

    /**
     * สร้างสีจาก hex
     */
    protected function hexColor($img, string $hex): int
    {
        $hex = ltrim($hex, '#');

        return imagecolorallocate($img, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }

    /**
     * วาดข้อความจัดกลาง (ใช้ @ suppression สำหรับ imagettfbbox)
     */
    protected function drawCenteredText($img, string $font, float $size, float $x, float $y, string $text, int $color): void
    {
        if (empty($font) || ! @file_exists($font)) {
            return;
        }

        $bbox = @imagettfbbox($size, 0, $font, $text);
        if ($bbox !== false) {
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            $drawX = $x - $textWidth / 2;
            $drawY = $y + $textHeight / 2;
        } else {
            $drawX = $x;
            $drawY = $y;
        }

        @imagettftext($img, $size, 0, (int) $drawX, (int) $drawY, $color, $font, $text);
    }

    /**
     * วาดเส้นหนา
     */
    protected function drawThickLine($img, int $x1, int $y1, int $x2, int $y2, int $color, int $thickness = 1): void
    {
        imagesetthickness($img, $thickness);
        imageline($img, $x1, $y1, $x2, $y2, $color);
        imagesetthickness($img, 1);
    }

    /**
     * วาดไอคอนแบบ vector (ใช้ GD shapes แทน emoji)
     *
     * @param  resource  $img  GD image
     * @param  float  $cx  จุดกลาง X
     * @param  float  $cy  จุดกลาง Y
     * @param  string  $type  ชนิดไอคอน
     * @param  int  $color1  สีหลัก
     * @param  int  $color2  สีรอง
     * @param  int  $white  สีขาว
     */
    protected function drawIcon($img, float $cx, float $cy, string $type, int $color1, int $color2, int $white): void
    {
        $x = (int) $cx;
        $y = (int) $cy;

        switch ($type) {
            case 'crystal_ball':
                // วงกลมลูกแก้ว + ขาตั้ง
                // Glow รอบนอก
                $glowPurple = imagecolorallocatealpha($img, 180, 130, 255, 80);
                imagefilledellipse($img, $x, $y, 160, 160, $glowPurple);
                // ลูกแก้วหลัก
                imagefilledellipse($img, $x, $y, 120, 120, $color1);
                // Highlight
                $highlight = imagecolorallocatealpha($img, 255, 255, 255, 80);
                imagefilledellipse($img, $x - 18, $y - 20, 40, 30, $highlight);
                // ขาตั้ง (สามเหลี่ยมคว่ำ)
                $points = [$x - 40, $y + 55, $x + 40, $y + 55, $x, $y + 85];
                imagefilledpolygon($img, $points, $color2);
                break;

            case 'star':
                // ดาว 5 แฉก
                $outerR = 65;
                $innerR = 30;
                $starPoints = [];
                for ($i = 0; $i < 10; $i++) {
                    $angle = deg2rad(-90 + $i * 36);
                    $r = ($i % 2 === 0) ? $outerR : $innerR;
                    $starPoints[] = $x + (int) ($r * cos($angle));
                    $starPoints[] = $y + (int) ($r * sin($angle));
                }
                imagefilledpolygon($img, $starPoints, $color1);
                // Inner glow
                $innerGlow = imagecolorallocatealpha($img, 255, 255, 200, 70);
                imagefilledellipse($img, $x, $y, 30, 30, $innerGlow);
                break;

            case 'scroll':
                // ม้วนกระดาษ/หนังสือ
                // กรอบหนังสือ
                imagefilledrectangle($img, $x - 50, $y - 50, $x + 50, $y + 50, $color1);
                // เส้นบรรทัด
                for ($line = -30; $line <= 30; $line += 15) {
                    imagesetthickness($img, 2);
                    imageline($img, $x - 30, $y + $line, $x + 30, $y + $line, $color2);
                }
                imagesetthickness($img, 1);
                // ม้วนบน-ล่าง
                imagefilledellipse($img, $x, $y - 50, 110, 20, $white);
                imagefilledellipse($img, $x, $y + 50, 110, 20, $white);
                break;

            case 'chart':
                // กราฟแท่ง 3 แท่ง
                $barW = 28;
                $gap = 10;
                $baseY = $y + 50;
                // แท่ง 1 (สั้น)
                imagefilledrectangle($img, $x - $barW - $gap - $barW, $baseY - 40, $x - $gap - $barW, $baseY, $color1);
                // แท่ง 2 (สูง)
                imagefilledrectangle($img, $x - $barW / 2, $baseY - 80, $x + $barW / 2, $baseY, $color2);
                // แท่ง 3 (กลาง)
                imagefilledrectangle($img, $x + $gap + $barW, $baseY - 60, $x + $gap + $barW + $barW, $baseY, $color1);
                // เส้นแกน
                imagesetthickness($img, 3);
                imageline($img, $x - 60, $baseY, $x + 70, $baseY, $white);
                imageline($img, $x - 60, $baseY - 90, $x - 60, $baseY, $white);
                imagesetthickness($img, 1);
                break;

            case 'warning':
                // สามเหลี่ยมเตือน
                $triPoints = [
                    $x, $y - 65,      // ยอด
                    $x - 65, $y + 45,  // ซ้ายล่าง
                    $x + 65, $y + 45,  // ขวาล่าง
                ];
                imagefilledpolygon($img, $triPoints, $color1);
                // เครื่องหมาย !
                imagefilledrectangle($img, $x - 7, $y - 35, $x + 7, $y + 10, $white);
                imagefilledellipse($img, $x, $y + 28, 16, 16, $white);
                break;

            case 'info':
                // วงกลม i
                imagefilledellipse($img, $x, $y, 110, 110, $color1);
                // จุดบน i
                imagefilledellipse($img, $x, $y - 25, 18, 18, $white);
                // แท่ง i
                imagefilledrectangle($img, $x - 7, $y - 10, $x + 7, $y + 35, $white);
                break;
        }
    }
}
