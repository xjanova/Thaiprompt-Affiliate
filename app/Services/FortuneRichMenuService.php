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
 * 🎨 (2026-07-25) เมนูหมอจันทรา V3 — พื้นหลังรูปจากเว็บจันทรา + ตัวหนังสือใหญ่ (ผู้สูงอายุ):
 * 🔮 ดูดวง (เลือกแพคเกจในแชท) | 📚 ดูย้อนหลัง (3 บิลล่าสุด) | 📲 โหลดแอพ
 * 🌐 เว็บไซต์ จันทรา.online | 🌳 ผังงาน | 🎬 วิดีโอแผนรายได้
 */
class FortuneRichMenuService
{
    protected const WIDTH = 2500;

    protected const HEIGHT = 1686;

    protected const ROW_HEIGHT = 843;

    protected const COL_WIDTH_1 = 833;

    protected const COL_WIDTH_2 = 834;

    protected const COL_WIDTH_3 = 833;

    /** 🌐 เว็บจันทรา (จันทรา.online = xn--82c4af5bzdj.online) — ปลายทางปุ่ม เว็บไซต์/โหลดแอพ/วิดีโอแผน */
    protected const JUNTRA_BASE_URL = 'https://xn--82c4af5bzdj.online';

    protected FortuneTellingSetting $settings;

    protected LineFortuneService $lineService;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
        $this->lineService = new LineFortuneService($this->settings);
    }

    /**
     * ดึง LineFortuneService instance
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
                'name' => 'หมอจันทรา - เมนูดูดวง v3',
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
     * คืน default editor config สำหรับ Rich Menu 6 ปุ่ม (V3 — 2026-07-25)
     *
     * ใช้เมื่อ: โหลด editor ครั้งแรก / ไม่มี config ใน DB / กด Deploy จากหน้า admin
     *
     * V3 layout (user spec):
     * แถวบน:  🔮 ดูดวง (เลือกแพคเกจ 39/99 ในแชท — กล่อง Flex กติกา) | 📚 ดูย้อนหลัง (3 บิล) | 📲 โหลดแอพ
     * แถวล่าง: 🌐 เว็บไซต์ จันทรา.online | 🌳 ผังงาน | 🎬 วิดีโอแผนรายได้
     * พื้นหลัง = รูป hero จากเว็บจันทรา + ตัวหนังสือใหญ่ (ลูกค้าส่วนมากสูงอายุ)
     *
     * @return array Editor config
     */
    public function getDefaultEditorConfig(): array
    {
        $price = number_format($this->lineService->getDeepReadingPrice(), 0);
        $celticPrice = 99;
        try {
            $celticPrice = (int) app(\App\Services\CelticCrossService::class)->getPrice();
        } catch (\Throwable $e) {
            // default 99
        }

        $settings = \App\Models\FortuneTellingSetting::getSettings();
        $deepEnabled = $settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($settings->enable_celtic_cross ?? false);

        // 💬 subtitle ปุ่มดูดวง — บอกราคาตามแพคเกจที่เปิดจริง (กดแล้วไปเลือกในแชท)
        $fortuneSubtitle = match (true) {
            $deepEnabled && $celticEnabled => "เลือกแพคเกจ {$price} / {$celticPrice} บาท",
            $celticEnabled => "ไพ่ Celtic {$celticPrice} บาท",
            $deepEnabled => "พื้นดวง {$price} บาท",
            default => 'แตะเพื่อเริ่ม',
        };

        return [
            'theme' => [
                'bg_gradient_start' => '#0a0612',
                'bg_gradient_end' => '#241038',
                // 🖼️ พื้นหลังรูปจากเว็บจันทรา (relative จาก resource_path) — ไม่มีไฟล์ = fallback gradient
                'bg_image' => 'images/fortune/richmenu-bg.jpg',
                // ความทึบ overlay ดำทับรูป (GD alpha 0=ทึบสุด..127=ใส) — ให้ตัวหนังสืออ่านชัด
                'overlay_alpha' => 58,
                'grid_line_color' => '#A8854A',
                'branding_text' => '~ หมอจันทราพยากรณ์ · จันทรา.online ~',
                'branding_color' => '#F6E3A8',
                'show_stars' => true,
                'show_moon' => false,
            ],
            'buttons' => [
                // แถวบน ─────────────────────────────
                [
                    'label' => 'ดูดวง',
                    'subtitle' => $fortuneSubtitle,
                    'icon' => 'crystal_ball',
                    'bg_color' => null,
                    'text_color' => '#F6E3A8',
                    'subtitle_color' => '#FFFFFF',
                    'font_size' => 92,
                    'subtitle_size' => 40,
                    'action_type' => 'message',
                    'action_data' => 'ดูดวง',
                    'display_text' => '',
                    'glow' => true,
                ],
                [
                    'label' => 'ดูย้อนหลัง',
                    'subtitle' => 'คำทำนาย 3 ครั้งล่าสุด',
                    'icon' => 'scroll',
                    'bg_color' => null,
                    'text_color' => '#FFFFFF',
                    'subtitle_color' => '#E7C97A',
                    'font_size' => 68,
                    'subtitle_size' => 38,
                    'action_type' => 'postback',
                    'action_data' => 'action=view_history',
                    'display_text' => 'บิลของฉัน',
                    'glow' => false,
                ],
                [
                    'label' => 'โหลดแอพ',
                    'subtitle' => 'Juntra App ฟรี',
                    'icon' => 'phone',
                    'bg_color' => null,
                    'text_color' => '#FFFFFF',
                    'subtitle_color' => '#E7C97A',
                    'font_size' => 68,
                    'subtitle_size' => 38,
                    'action_type' => 'uri',
                    'action_data' => self::JUNTRA_BASE_URL.'/app',
                    'display_text' => '',
                    'glow' => false,
                ],
                // แถวล่าง ─────────────────────────────
                [
                    'label' => 'เว็บไซต์',
                    'subtitle' => 'จันทรา.online',
                    'icon' => 'globe',
                    'bg_color' => null,
                    'text_color' => '#FFFFFF',
                    'subtitle_color' => '#E7C97A',
                    'font_size' => 68,
                    'subtitle_size' => 40,
                    'action_type' => 'uri',
                    'action_data' => self::JUNTRA_BASE_URL,
                    'display_text' => '',
                    'glow' => false,
                ],
                [
                    'label' => 'ผังงาน',
                    'subtitle' => 'สายงาน · รายได้ของฉัน',
                    'icon' => 'network',
                    'bg_color' => null,
                    'text_color' => '#F6E3A8',
                    'subtitle_color' => '#FFFFFF',
                    'font_size' => 76,
                    'subtitle_size' => 38,
                    'action_type' => 'postback',
                    'action_data' => 'action=view_network',
                    'display_text' => 'ผังงานของฉัน',
                    'glow' => false,
                ],
                [
                    'label' => 'วิดีโอแผน',
                    'subtitle' => 'แนะนำเพื่อน มีรายได้',
                    'icon' => 'play',
                    'bg_color' => null,
                    'text_color' => '#FFFFFF',
                    'subtitle_color' => '#E7C97A',
                    'font_size' => 68,
                    'subtitle_size' => 38,
                    'action_type' => 'uri',
                    'action_data' => self::JUNTRA_BASE_URL.'/#plan',
                    'display_text' => '',
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
                // 🌐 (2026-07-25) ปุ่มลิงก์ — เว็บไซต์/โหลดแอพ/วิดีโอแผน (เปิด browser ใน LINE)
                'uri' => [
                    'type' => 'uri',
                    'label' => mb_substr($btn['label'] ?? 'เปิดลิงก์', 0, 20),
                    'uri' => $btn['action_data'] ?? self::JUNTRA_BASE_URL,
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

        // === พื้นหลัง ===
        // 🖼️ (2026-07-25) โหมดรูปภาพ — ใช้รูป hero จากเว็บจันทรา (2500×1686 เตรียมไว้แล้ว)
        //   + overlay ดำโปร่งให้ตัวหนังสืออ่านชัด / ไม่มีไฟล์หรือโหลดพัง → fallback gradient เดิม
        $usedBgImage = false;
        $bgImageRel = trim((string) ($theme['bg_image'] ?? ''));
        if ($bgImageRel !== '') {
            $bgImagePath = resource_path($bgImageRel);
            if (@is_file($bgImagePath)) {
                $bgImg = @imagecreatefromstring((string) @file_get_contents($bgImagePath));
                if ($bgImg !== false) {
                    // cover-crop ให้เต็ม 2500×1686 (รูปเตรียมมาพอดีอยู่แล้ว — สเกลเผื่อกรณีขนาดอื่น)
                    $srcW = imagesx($bgImg);
                    $srcH = imagesy($bgImg);
                    $scale = max(self::WIDTH / $srcW, self::HEIGHT / $srcH);
                    $scaledW = (int) ceil($srcW * $scale);
                    $scaledH = (int) ceil($srcH * $scale);
                    $offX = (int) (($scaledW - self::WIDTH) / 2);
                    $offY = (int) (($scaledH - self::HEIGHT) / 2);
                    imagecopyresampled($img, $bgImg, -$offX, -$offY, 0, 0, $scaledW, $scaledH, $srcW, $srcH);
                    imagedestroy($bgImg);

                    // overlay ดำม่วงโปร่ง — ยิ่ง alpha น้อยยิ่งทึบ (default 58 ≈ อ่านตัวหนังสือชัด)
                    $overlayAlpha = max(0, min(127, (int) ($theme['overlay_alpha'] ?? 58)));
                    $overlay = imagecolorallocatealpha($img, 10, 6, 18, $overlayAlpha);
                    imagefilledrectangle($img, 0, 0, self::WIDTH, self::HEIGHT, $overlay);
                    $usedBgImage = true;
                }
            }
        }

        if (! $usedBgImage) {
            // fallback: gradient เดิม
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

        // === Export ===
        // 🖼️ (2026-07-25) พื้นหลังรูปถ่าย → JPEG q85 (PNG photo จะเกิน 1MB — LINE limit)
        //   gradient ล้วน → PNG เหมือนเดิม (คมกว่า เล็กพอ)
        ob_start();
        if ($usedBgImage) {
            imagejpeg($img, null, 85);
        } else {
            imagepng($img, null, 7);
        }
        $pngData = ob_get_clean();

        // 🛡️ เกิน 1MB (LINE limit) → ลด quality ลงจนกว่าจะผ่าน (รูป noisy + ดาว 200 ดวงอาจดันขนาด)
        if ($usedBgImage && strlen($pngData) >= 1000000) {
            foreach ([70, 55] as $quality) {
                ob_start();
                imagejpeg($img, null, $quality);
                $pngData = ob_get_clean();
                if (strlen($pngData) < 1000000) {
                    break;
                }
            }
            Log::warning('FortuneRichMenu: ภาพเกิน 1MB — ลด JPEG quality อัตโนมัติ', [
                'final_size_kb' => (int) (strlen($pngData) / 1024),
            ]);
        }

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
     * กำหนดพื้นที่คลิก 6 ปุ่มของ Rich Menu (V3 fallback)
     *
     * 🎨 (2026-07-25) ใช้ตอน admin deploy custom mode โดยไม่ส่ง customAreas (rare fallback)
     *   — สร้างจาก default config เดียวกับปุ่มที่วาด เพื่อกัน areas/ภาพไม่ตรงกัน
     *
     * @return array LINE Rich Menu areas format
     */
    public function getRichMenuAreas(): array
    {
        return $this->getAreasFromConfig($this->getDefaultEditorConfig());
    }

    // === Private Helper Methods ===

    /**
     * บันทึกภาพลง storage
     */
    protected function saveImageToStorage(string $pngData): ?string
    {
        try {
            // 🖼️ (2026-07-25) นามสกุลตาม magic bytes — JPEG (พื้นหลังรูป) หรือ PNG (gradient)
            $ext = str_starts_with($pngData, "\xFF\xD8") ? 'jpg' : 'png';
            $filename = 'fortune-rich-menu-'.Str::random(8).'.'.$ext;
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

                // 🎨 (2026-07-25) ไอคอนชุด V3 — โหลดแอพ / เว็บไซต์ / ผังงาน / วิดีโอแผน
            case 'phone':
                // มือถือ: กรอบมน + จอ + ปุ่ม home + ลูกศรดาวน์โหลด
                imagefilledrectangle($img, $x - 42, $y - 68, $x + 42, $y + 68, $color1);
                $screen = imagecolorallocatealpha($img, 10, 6, 18, 30);
                imagefilledrectangle($img, $x - 32, $y - 52, $x + 32, $y + 44, $screen);
                imagefilledellipse($img, $x, $y + 56, 14, 14, $white);
                // ลูกศรดาวน์โหลดกลางจอ
                imagesetthickness($img, 6);
                imageline($img, $x, $y - 30, $x, $y + 14, $color2);
                imagesetthickness($img, 1);
                imagefilledpolygon($img, [$x - 16, $y + 6, $x + 16, $y + 6, $x, $y + 28], $color2);
                break;

            case 'globe':
                // ลูกโลก: วงกลม + เส้นศูนย์สูตร/เมริเดียน
                imagesetthickness($img, 5);
                imageellipse($img, $x, $y, 120, 120, $color1);
                imageellipse($img, $x, $y, 60, 120, $color1);
                imageline($img, $x - 60, $y, $x + 60, $y, $color1);
                imagearc($img, $x, $y - 78, 150, 90, 35, 145, $color1);
                imagearc($img, $x, $y + 78, 150, 90, 215, 325, $color1);
                imagesetthickness($img, 1);
                imagefilledellipse($img, $x + 42, $y - 42, 14, 14, $color2);
                break;

            case 'network':
                // ผังสายงาน: โหนดบน 1 → ล่าง 3 (โครงสร้าง MLM)
                imagesetthickness($img, 5);
                imageline($img, $x, $y - 35, $x - 55, $y + 35, $color2);
                imageline($img, $x, $y - 35, $x, $y + 35, $color2);
                imageline($img, $x, $y - 35, $x + 55, $y + 35, $color2);
                imagesetthickness($img, 1);
                imagefilledellipse($img, $x, $y - 50, 46, 46, $color1);
                imagefilledellipse($img, $x - 55, $y + 50, 36, 36, $color1);
                imagefilledellipse($img, $x, $y + 50, 36, 36, $color1);
                imagefilledellipse($img, $x + 55, $y + 50, 36, 36, $color1);
                break;

            case 'play':
                // ปุ่มเล่นวิดีโอ: วงกลม + สามเหลี่ยม play
                imagefilledellipse($img, $x, $y, 130, 130, $color1);
                $innerPlay = imagecolorallocatealpha($img, 10, 6, 18, 40);
                imagefilledellipse($img, $x, $y, 106, 106, $innerPlay);
                imagefilledpolygon($img, [$x - 18, $y - 30, $x - 18, $y + 30, $x + 34, $y], $color2);
                break;
        }
    }
}
