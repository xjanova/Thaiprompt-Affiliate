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
 * สร้างเมนูหมอจันทรา 6 ปุ่ม (V2 ตัวหนังสือใหญ่ขึ้น):
 * 🔮 ดูดวง | ✨ ดูดวงละเอียด | 📖 ดูคำทำนายล่าสุด
 * 📊 สถานะ/สิทธิ์ | ⚠️ แจ้งปัญหาโอน | ℹ️ วิธีใช้งาน
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
     * @return LineFortuneService
     */
    public function getLineService(): LineFortuneService
    {
        return $this->lineService;
    }

    /**
     * สร้างภาพ Rich Menu + deploy ไป LINE API + ตั้งเป็น default
     *
     * รองรับ 2 โหมด:
     * - config mode: สร้างภาพจาก editor config (auto-generate ด้วย GD)
     * - custom mode: ใช้ภาพที่อัปโหลดเอง + areas ที่กำหนด
     *
     * @param  array|null  $config  Editor config (null = ใช้ default)
     * @param  string|null  $customImagePath  Path ภาพ PNG ที่อัปโหลด (mode custom)
     * @param  array|null  $customAreas  Areas สำหรับ custom mode (null = ใช้ default)
     * @param  string  $editorMode  โหมด: 'config' หรือ 'custom'
     * @return array ผลลัพธ์ ['success', 'rich_menu_id', 'menu', 'error']
     */
    public function generateAndDeploy(?array $config = null, ?string $customImagePath = null, ?array $customAreas = null, string $editorMode = 'config'): array
    {
        try {
            // 1. สร้าง/ดึงภาพ PNG ตามโหมด
            if ($editorMode === 'custom' && $customImagePath) {
                // Custom mode: ใช้ภาพที่อัปโหลด
                $pngData = Storage::disk('public')->get($customImagePath);
                $areas = $customAreas ?? $this->getRichMenuAreas();
            } else {
                // Config mode: สร้างภาพจาก config
                $pngData = $config ? $this->generateImageFromConfig($config) : $this->generateImage();
                $areas = $this->getAreasFromConfig($config);
            }

            if (empty($pngData)) {
                return ['success' => false, 'error' => 'ไม่สามารถสร้างภาพ Rich Menu ได้'];
            }

            // 2. สร้าง Rich Menu structure บน LINE Platform
            $richMenuData = [
                'size' => ['width' => self::WIDTH, 'height' => self::HEIGHT],
                'selected' => true,
                'name' => 'หมอจันทรา - เมนูดูดวง v2',
                'chatBarText' => '🔮 เมนูดูดวง',
                'areas' => $areas,
            ];

            $richMenuId = $this->lineService->createRichMenu($richMenuData);
            if (! $richMenuId) {
                return ['success' => false, 'error' => 'สร้าง Rich Menu บน LINE ไม่สำเร็จ'];
            }

            // 3. อัปโหลดภาพ PNG
            $uploaded = $this->lineService->uploadRichMenuImage($richMenuId, $pngData);
            if (! $uploaded) {
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

            // 7. บันทึกลง DB (รวม editor_config + editor_mode)
            $menu = LineRichMenu::create([
                'rich_menu_id' => $richMenuId,
                'name' => 'fortune-telling-bot',
                'description' => 'Rich Menu สำหรับบอทหมอจันทรา',
                'size' => 'full',
                'selected' => true,
                'chat_bar_text' => '🔮 เมนูดูดวง',
                'areas' => $areas,
                'editor_config' => $config ?? $this->getDefaultEditorConfig(),
                'editor_mode' => $editorMode,
                'image_path' => $imagePath,
                'is_default' => true,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            Log::info('FortuneRichMenu: Deploy สำเร็จ', [
                'rich_menu_id' => $richMenuId,
                'menu_id' => $menu->id,
                'editor_mode' => $editorMode,
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
     * คืน default editor config สำหรับ Rich Menu 6 ปุ่ม
     *
     * ใช้เมื่อ: โหลด editor ครั้งแรก / ไม่มี config ใน DB
     *
     * @return array Editor config
     */
    public function getDefaultEditorConfig(): array
    {
        $price = number_format($this->lineService->getDeepReadingPrice(), 0);
        // 🌙 (2026-05-23) Deep ปิด — default config ตั้งปุ่มแรกเป็น Celtic แทน
        //   admin โหลด editor ครั้งแรก / fresh install จะไม่เห็น "ดูดวงเชิงลึก 39 บาท" ที่ทำให้ลูกค้าสับสน
        //   ⚠️ ปุ่มจริงบน Rich Menu ปัจจุบัน = เก่า — ต้อง redeploy หลัง toggle เปลี่ยน
        $settings = \App\Models\FortuneTellingSetting::getSettings();
        $deepEnabled = $settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($settings->enable_celtic_cross ?? false);
        $celticPrice = 99;
        try {
            $celticPrice = (int) app(\App\Services\CelticCrossService::class)->getPrice();
        } catch (\Throwable $e) {
            // default 99
        }

        $secondButton = $deepEnabled
            ? [
                'label' => 'ดูดวงเชิงลึก',
                'subtitle' => "{$price} บาท",
                'extra_text' => 'วิเคราะห์เจาะลึก + ดวงชะตา',
                'icon' => 'star',
                'bg_color' => '#8C6400',
                'text_color' => '#FFFFFF',
                'subtitle_color' => '#FFD700',
                'font_size' => 56,
                'subtitle_size' => 44,
                'action_type' => 'postback',
                'action_data' => 'action=deep_reading',
                'display_text' => 'ดูดวง',
                'glow' => false,
            ]
            : ($celticEnabled ? [
                // 🌙 (2026-05-23) Deep ปิด แต่ Celtic เปิด — โชว์ Celtic Cross แทน
                'label' => 'ไพ่ยิปซี 10 ใบ',
                'subtitle' => "{$celticPrice} บาท",
                'extra_text' => 'Celtic Cross โบราณดั้งเดิม',
                'icon' => 'star',
                'bg_color' => '#8C6400',
                'text_color' => '#FFFFFF',
                'subtitle_color' => '#FFD700',
                'font_size' => 56,
                'subtitle_size' => 44,
                'action_type' => 'postback',
                'action_data' => 'action=celtic_cross',
                'display_text' => 'celtic',
                'glow' => false,
            ] : [
                // Deep + Celtic ปิดทั้งคู่ — ปุ่มสำรอง "ดูคำทำนาย"
                'label' => 'ดูคำทำนาย',
                'subtitle' => 'ของฉัน',
                'extra_text' => '',
                'icon' => 'scroll',
                'bg_color' => '#8C6400',
                'text_color' => '#FFFFFF',
                'subtitle_color' => '#FFD700',
                'font_size' => 56,
                'subtitle_size' => 44,
                'action_type' => 'postback',
                'action_data' => 'action=view_last_reading',
                'display_text' => 'ดูคำทำนายล่าสุด',
                'glow' => false,
            ]);

        return [
            'theme' => [
                'bg_gradient_start' => '#0d0521',
                'bg_gradient_end' => '#1a0a3e',
                'grid_line_color' => '#4C1D95',
                'branding_text' => '~~ หมอจันทราพยากรณ์ ~~',
                'branding_color' => '#FFD700',
                'show_stars' => true,
                'show_moon' => true,
            ],
            'buttons' => [
                [
                    'label' => 'ดูดวง',
                    'subtitle' => 'ฟรี! ถามได้เลย',
                    'icon' => 'crystal_ball',
                    'bg_color' => null,
                    'text_color' => '#FFD700',
                    'subtitle_color' => '#C4B5FD',
                    'font_size' => 72,
                    'subtitle_size' => 32,
                    'action_type' => 'message',
                    'action_data' => 'ดูดวง',
                    'display_text' => '',
                    'glow' => true,
                ],
                $secondButton,
                [
                    'label' => 'ดูคำทำนาย',
                    'subtitle' => 'ล่าสุด',
                    'icon' => 'scroll',
                    'bg_color' => null,
                    'text_color' => '#FFFFFF',
                    'subtitle_color' => '#FFFFFF',
                    'font_size' => 48,
                    'subtitle_size' => 48,
                    'action_type' => 'postback',
                    'action_data' => 'action=view_last_reading',
                    'display_text' => 'ดูคำทำนายล่าสุด',
                    'glow' => false,
                ],
                // 🎯 Phase M — แทน "สิทธิ์/Wallet" ด้วย "ชวนเพื่อน" (useful + actionable)
                [
                    'label' => 'ชวนเพื่อน',
                    'subtitle' => 'รับส่วนแบ่ง',
                    'icon' => 'status',
                    'bg_color' => '#00605B',
                    'text_color' => '#FFFFFF',
                    'subtitle_color' => '#C4B5FD',
                    'font_size' => 48,
                    'subtitle_size' => 28,
                    'action_type' => 'postback',
                    'action_data' => 'action=affiliate_share',
                    'display_text' => 'ชวนเพื่อน/รับส่วนแบ่ง',
                    'glow' => false,
                ],
                [
                    'label' => 'แจ้งปัญหา',
                    'subtitle' => 'การโอนเงิน',
                    'extra_text' => 'โอนแล้วไม่ได้คำทำนาย?',
                    'icon' => 'warning',
                    'bg_color' => '#8B1A00',
                    'text_color' => '#FFA726',
                    'subtitle_color' => '#FFA726',
                    'font_size' => 44,
                    'subtitle_size' => 44,
                    'action_type' => 'postback',
                    'action_data' => 'action=report_payment',
                    'display_text' => 'แจ้งปัญหาโอน',
                    'glow' => false,
                ],
                // 🎯 Phase M — แทน "วิธีใช้งาน" ด้วย "คุยกับแม่หมอ" (ติดต่อแอดมิน)
                [
                    'label' => 'คุยกับแม่หมอ',
                    'subtitle' => 'ติดต่อแอดมิน',
                    'icon' => 'info',
                    'bg_color' => null,
                    'text_color' => '#FFFFFF',
                    'subtitle_color' => '#C4B5FD',
                    'font_size' => 44,
                    'subtitle_size' => 28,
                    'action_type' => 'postback',
                    'action_data' => 'action=talk_human',
                    'display_text' => 'คุยกับแม่หมอ',
                    'glow' => false,
                ],
            ],
        ];
    }

    /**
     * สร้าง Rich Menu areas จาก editor config
     *
     * @param  array|null  $config  Editor config (null = ใช้ default areas)
     * @return array LINE areas format
     */
    public function getAreasFromConfig(?array $config = null): array
    {
        if (! $config || empty($config['buttons'])) {
            return $this->getRichMenuAreas();
        }

        $colWidths = [self::COL_WIDTH_1, self::COL_WIDTH_2, self::COL_WIDTH_3];
        $areas = [];

        foreach ($config['buttons'] as $i => $btn) {
            $row = (int) ($i / 3);
            $col = $i % 3;
            $x = array_sum(array_slice($colWidths, 0, $col));
            $y = $row * self::ROW_HEIGHT;

            $action = match ($btn['action_type'] ?? 'postback') {
                'message' => [
                    'type' => 'message',
                    'text' => $btn['action_data'] ?? $btn['label'],
                ],
                default => [
                    'type' => 'postback',
                    'data' => $btn['action_data'] ?? "action={$btn['label']}",
                    'displayText' => $btn['display_text'] ?? $btn['label'],
                ],
            };

            $areas[] = [
                'bounds' => [
                    'x' => $x,
                    'y' => $y,
                    'width' => $colWidths[$col],
                    'height' => self::ROW_HEIGHT,
                ],
                'action' => $action,
            ];
        }

        return $areas;
    }

    /**
     * สร้างภาพ Rich Menu จาก editor config (config-driven)
     *
     * @param  array  $config  Editor config จาก getDefaultEditorConfig() หรือ custom
     * @return string PNG binary data
     */
    public function generateImageFromConfig(array $config): string
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $font = $this->getThaiFont();
        $theme = $config['theme'] ?? [];
        $buttons = $config['buttons'] ?? [];

        // === พื้นหลัง gradient ===
        $bgStart = $this->parseHex($theme['bg_gradient_start'] ?? '#0d0521');
        $bgEnd = $this->parseHex($theme['bg_gradient_end'] ?? '#1a0a3e');
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / self::HEIGHT;
            $r = (int) ($bgStart[0] + ($bgEnd[0] - $bgStart[0]) * $ratio);
            $g = (int) ($bgStart[1] + ($bgEnd[1] - $bgStart[1]) * $ratio);
            $b = (int) ($bgStart[2] + ($bgEnd[2] - $bgStart[2]) * $ratio);
            $lineColor = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::WIDTH, $y, $lineColor);
        }

        // === ดาวตกแต่ง ===
        if ($theme['show_stars'] ?? true) {
            for ($i = 0; $i < 200; $i++) {
                $sx = rand(10, self::WIDTH - 10);
                $sy = rand(10, self::HEIGHT - 10);
                $sr = rand(1, 4);
                $starAlpha = rand(40, 110);
                $starColor = imagecolorallocatealpha($img, 255, 255, 255, $starAlpha);
                imagefilledellipse($img, $sx, $sy, $sr * 2, $sr * 2, $starColor);
            }
        }

        // === พระจันทร์เสี้ยว ===
        if ($theme['show_moon'] ?? true) {
            $moonX = self::WIDTH - 250;
            $moonY = 180;
            $gold = $this->hexColor($img, '#FFD700');
            imagefilledellipse($img, $moonX, $moonY, 140, 140, $gold);
            imagefilledellipse($img, $moonX + 30, $moonY - 20, 130, 130, $this->hexColor($img, $theme['bg_gradient_end'] ?? '#1a0a3e'));
        }

        // === เส้นแบ่ง grid ===
        $gridColor = $this->hexColor($img, $theme['grid_line_color'] ?? '#4C1D95');
        $this->drawThickLine($img, 0, self::ROW_HEIGHT, self::WIDTH, self::ROW_HEIGHT, $gridColor, 3);
        $this->drawThickLine($img, self::COL_WIDTH_1, 0, self::COL_WIDTH_1, self::ROW_HEIGHT, $gridColor, 2);
        $this->drawThickLine($img, self::COL_WIDTH_1 + self::COL_WIDTH_2, 0, self::COL_WIDTH_1 + self::COL_WIDTH_2, self::ROW_HEIGHT, $gridColor, 2);
        $this->drawThickLine($img, self::COL_WIDTH_1, self::ROW_HEIGHT, self::COL_WIDTH_1, self::HEIGHT, $gridColor, 2);
        $this->drawThickLine($img, self::COL_WIDTH_1 + self::COL_WIDTH_2, self::ROW_HEIGHT, self::COL_WIDTH_1 + self::COL_WIDTH_2, self::HEIGHT, $gridColor, 2);

        // === วาดปุ่มตาม config ===
        $colWidths = [self::COL_WIDTH_1, self::COL_WIDTH_2, self::COL_WIDTH_3];
        $white = $this->hexColor($img, '#FFFFFF');
        $lightPurple = $this->hexColor($img, '#C4B5FD');

        foreach ($buttons as $i => $btn) {
            $row = (int) ($i / 3);
            $col = $i % 3;
            $xOffset = array_sum(array_slice($colWidths, 0, $col));
            $cx = $xOffset + $colWidths[$col] / 2;
            $cy = $row * self::ROW_HEIGHT + self::ROW_HEIGHT / 2;

            // พื้นหลังปุ่ม (ถ้ามี)
            if (! empty($btn['bg_color'])) {
                $bgRgb = $this->parseHex($btn['bg_color']);
                $yStart = $row * self::ROW_HEIGHT + 2;
                $yEnd = ($row + 1) * self::ROW_HEIGHT - 2;
                for ($yy = $yStart; $yy < $yEnd; $yy++) {
                    $ratio = ($yy - $yStart) / ($yEnd - $yStart);
                    $rr = (int) ($bgRgb[0] * (1 - $ratio * 0.3));
                    $gg = (int) ($bgRgb[1] * (1 - $ratio * 0.3));
                    $bb = (int) ($bgRgb[2] * (1 - $ratio * 0.3));
                    $bgGrad = imagecolorallocatealpha($img, max(0, $rr), max(0, $gg), max(0, $bb), 85);
                    imageline($img, $xOffset + 2, $yy, $xOffset + $colWidths[$col] - 2, $yy, $bgGrad);
                }
            }

            // Glow effect
            if ($btn['glow'] ?? false) {
                for ($r = 200; $r > 0; $r -= 10) {
                    $alpha = (int) (127 - (127 - 100) * $r / 200);
                    $glowColor = imagecolorallocatealpha($img, 124, 58, 237, $alpha);
                    imagefilledellipse($img, (int) $cx, (int) $cy, $r * 2, $r * 2, $glowColor);
                }
            }

            // ไอคอน
            $iconType = $btn['icon'] ?? 'info';
            $color1 = $this->hexColor($img, $btn['text_color'] ?? '#FFFFFF');
            $color2 = $this->hexColor($img, $btn['subtitle_color'] ?? '#C4B5FD');
            $iconY = ! empty($btn['extra_text']) ? $cy - 80 : $cy - 60;
            $this->drawIcon($img, $cx, $iconY, $iconType, $color1, $color2, $white);

            // ข้อความหลัก
            $textColor = $this->hexColor($img, $btn['text_color'] ?? '#FFFFFF');
            $fontSize = $btn['font_size'] ?? 48;
            $textY = ! empty($btn['extra_text']) ? $cy + 30 : $cy + 60;
            $this->drawCenteredText($img, $font, $fontSize, $cx, $textY, $btn['label'] ?? '', $textColor);

            // ข้อความรอง
            if (! empty($btn['subtitle'])) {
                $subColor = $this->hexColor($img, $btn['subtitle_color'] ?? '#C4B5FD');
                $subSize = $btn['subtitle_size'] ?? 28;
                $subY = $textY + (int) ($fontSize * 1.2);
                $this->drawCenteredText($img, $font, $subSize, $cx, $subY, $btn['subtitle'], $subColor);
            }

            // ข้อความเพิ่มเติม (บรรทัดที่ 3)
            if (! empty($btn['extra_text'])) {
                $this->drawCenteredText($img, $font, 26, $cx, $cy + 160, $btn['extra_text'], $lightPurple);
            }
        }

        // === Branding ด้านบน ===
        $brandText = $theme['branding_text'] ?? '~~ หมอจันทราพยากรณ์ ~~';
        $brandColor = $this->hexColor($img, $theme['branding_color'] ?? '#FFD700');
        $this->drawCenteredText($img, $font, 32, self::WIDTH / 2, 50, $brandText, $brandColor);

        // === Export PNG ===
        ob_start();
        imagepng($img, null, 7);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }

    /**
     * สร้างภาพ Rich Menu ด้วย default config (backward compatible)
     *
     * @return string PNG binary data
     */
    public function generateImage(): string
    {
        return $this->generateImageFromConfig($this->getDefaultEditorConfig());
    }

    /**
     * กำหนดพื้นที่คลิก 6 ปุ่มของ Rich Menu
     *
     * @return array LINE Rich Menu areas format
     */
    public function getRichMenuAreas(): array
    {
        // 🌙 (2026-05-23) Row 1 Col 2 action ตาม toggle — Deep ปิด = redirect Celtic หรือ view_last
        //   ใช้ตอน admin deploy custom mode โดยไม่ส่ง customAreas เข้ามา (rare fallback)
        $settings = \App\Models\FortuneTellingSetting::getSettings();
        $deepEnabled = $settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($settings->enable_celtic_cross ?? false);
        $secondAction = $deepEnabled
            ? ['type' => 'postback', 'data' => 'action=deep_reading', 'displayText' => 'ดูดวง']
            : ($celticEnabled
                ? ['type' => 'postback', 'data' => 'action=celtic_cross', 'displayText' => 'celtic']
                : ['type' => 'postback', 'data' => 'action=view_last_reading', 'displayText' => 'ดูคำทำนายล่าสุด']);

        return [
            // Row 1, Col 1: 🔮 ดูดวง
            [
                'bounds' => ['x' => 0, 'y' => 0, 'width' => self::COL_WIDTH_1, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'message', 'text' => 'ดูดวง'],
            ],
            // Row 1, Col 2: ✨ ดูดวงละเอียด (dynamic ตาม toggle)
            [
                'bounds' => ['x' => self::COL_WIDTH_1, 'y' => 0, 'width' => self::COL_WIDTH_2, 'height' => self::ROW_HEIGHT],
                'action' => $secondAction,
            ],
            // Row 1, Col 3: 📖 ดูคำทำนายล่าสุด
            [
                'bounds' => ['x' => self::COL_WIDTH_1 + self::COL_WIDTH_2, 'y' => 0, 'width' => self::COL_WIDTH_3, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=view_last_reading', 'displayText' => 'ดูคำทำนายล่าสุด'],
            ],
            // 🎯 Phase M — Row 2, Col 1: 📢 ชวนเพื่อน (แทน "เช็คสิทธิ์")
            [
                'bounds' => ['x' => 0, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH_1, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=affiliate_share', 'displayText' => 'ชวนเพื่อน/รับส่วนแบ่ง'],
            ],
            // Row 2, Col 2: ⚠️ แจ้งปัญหาโอน
            [
                'bounds' => ['x' => self::COL_WIDTH_1, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH_2, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=report_payment', 'displayText' => 'แจ้งปัญหาโอน'],
            ],
            // 🎯 Phase M — Row 2, Col 3: 💬 คุยกับแม่หมอ (แทน "วิธีใช้งาน")
            [
                'bounds' => ['x' => self::COL_WIDTH_1 + self::COL_WIDTH_2, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH_3, 'height' => self::ROW_HEIGHT],
                'action' => ['type' => 'postback', 'data' => 'action=talk_human', 'displayText' => 'คุยกับแม่หมอ'],
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
     * หา path ฟอนต์ภาษาไทย
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
     * แปลง hex color เป็น RGB array [r, g, b]
     *
     * ใช้สำหรับคำนวณ gradient ใน generateImageFromConfig()
     *
     * @param  string  $hex  Hex color เช่น '#FF0000' หรือ 'FF0000'
     * @return array [r, g, b]
     */
    protected function parseHex(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
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
     */
    protected function drawIcon($img, float $cx, float $cy, string $type, int $color1, int $color2, int $white): void
    {
        $x = (int) $cx;
        $y = (int) $cy;

        switch ($type) {
            case 'crystal_ball':
                $glowPurple = imagecolorallocatealpha($img, 180, 130, 255, 80);
                imagefilledellipse($img, $x, $y, 160, 160, $glowPurple);
                imagefilledellipse($img, $x, $y, 120, 120, $color1);
                $highlight = imagecolorallocatealpha($img, 255, 255, 255, 80);
                imagefilledellipse($img, $x - 18, $y - 20, 40, 30, $highlight);
                $points = [$x - 40, $y + 55, $x + 40, $y + 55, $x, $y + 85];
                imagefilledpolygon($img, $points, $color2);
                break;

            case 'star':
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
                $innerGlow = imagecolorallocatealpha($img, 255, 255, 200, 70);
                imagefilledellipse($img, $x, $y, 30, 30, $innerGlow);
                break;

            case 'scroll':
                imagefilledrectangle($img, $x - 50, $y - 50, $x + 50, $y + 50, $color1);
                for ($line = -30; $line <= 30; $line += 15) {
                    imagesetthickness($img, 2);
                    imageline($img, $x - 30, $y + $line, $x + 30, $y + $line, $color2);
                }
                imagesetthickness($img, 1);
                imagefilledellipse($img, $x, $y - 50, 110, 20, $white);
                imagefilledellipse($img, $x, $y + 50, 110, 20, $white);
                break;

            case 'chart':
                $barW = 28;
                $gap = 10;
                $baseY = $y + 50;
                imagefilledrectangle($img, $x - $barW - $gap - $barW, $baseY - 40, $x - $gap - $barW, $baseY, $color1);
                imagefilledrectangle($img, $x - $barW / 2, $baseY - 80, $x + $barW / 2, $baseY, $color2);
                imagefilledrectangle($img, $x + $gap + $barW, $baseY - 60, $x + $gap + $barW + $barW, $baseY, $color1);
                imagesetthickness($img, 3);
                imageline($img, $x - 60, $baseY, $x + 70, $baseY, $white);
                imageline($img, $x - 60, $baseY - 90, $x - 60, $baseY, $white);
                imagesetthickness($img, 1);
                break;

            case 'warning':
                $triPoints = [
                    $x, $y - 65,
                    $x - 65, $y + 45,
                    $x + 65, $y + 45,
                ];
                imagefilledpolygon($img, $triPoints, $color1);
                imagefilledrectangle($img, $x - 7, $y - 35, $x + 7, $y + 10, $white);
                imagefilledellipse($img, $x, $y + 28, 16, 16, $white);
                break;

            case 'info':
                imagefilledellipse($img, $x, $y, 110, 110, $color1);
                imagefilledellipse($img, $x, $y - 25, 18, 18, $white);
                imagefilledrectangle($img, $x - 7, $y - 10, $x + 7, $y + 35, $white);
                break;

            case 'status':
                // ไอคอนสถานะ: วงกลม + เครื่องหมายถูก + ดาว
                // วงกลมนอก
                imagefilledellipse($img, $x, $y, 120, 120, $color1);
                // วงกลมใน
                $innerColor = imagecolorallocatealpha($img, 0, 20, 40, 60);
                imagefilledellipse($img, $x, $y, 90, 90, $innerColor);
                // เครื่องหมายถูก
                imagesetthickness($img, 6);
                imageline($img, $x - 25, $y, $x - 8, $y + 18, $white);
                imageline($img, $x - 8, $y + 18, $x + 28, $y - 20, $white);
                imagesetthickness($img, 1);
                // ดาวเล็กๆ ด้านบนขวา
                imagefilledellipse($img, $x + 50, $y - 40, 16, 16, $color2);
                break;
        }
    }
}
