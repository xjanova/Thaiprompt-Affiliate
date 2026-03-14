<?php

namespace App\Services;

use App\Models\LineRichMenu;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ThaipromptRichMenuService — สร้าง Rich Menu + deploy สำหรับ Thaiprompt OA หลัก
 *
 * ใช้ pattern เดียวกับ FortuneRichMenuService (GD auto-generate + LINE API)
 * รองรับ 2 โหมด: config mode (auto-generate) + custom mode (อัปโหลดเอง)
 *
 * Layout 6 ปุ่ม (2500x1686):
 * 🛒 ตลาดสด | 🤖 AI Bots | 💰 MLM
 * 🔮 ดูดวง   | 📊 แดชบอร์ด | ℹ️ ช่วยเหลือ
 */
class ThaipromptRichMenuService
{
    protected const WIDTH = 2500;
    protected const HEIGHT = 1686;
    protected const ROW_HEIGHT = 843;
    protected const COL_WIDTH = 833;

    protected const LINE_API = 'https://api.line.me/v2/bot';

    protected string $channelAccessToken;

    public function __construct()
    {
        // ใช้ LINE Channel Access Token ของ Thaiprompt OA หลัก
        $this->channelAccessToken = config('services.line.channel_access_token', env('LINE_CHANNEL_ACCESS_TOKEN', ''));
    }

    /**
     * สร้าง Rich Menu + deploy ไป LINE API + ตั้งเป็น default
     *
     * @param array|null $config Editor config (null = default)
     * @param string|null $customImagePath Path ภาพ PNG ที่อัปโหลด
     * @param array|null $customAreas Areas สำหรับ custom mode
     * @param string $editorMode โหมด: 'config' หรือ 'custom'
     * @return array ผลลัพธ์ ['success', 'rich_menu_id', 'menu', 'error']
     */
    public function generateAndDeploy(?array $config = null, ?string $customImagePath = null, ?array $customAreas = null, string $editorMode = 'config'): array
    {
        try {
            if (empty($this->channelAccessToken)) {
                return ['success' => false, 'error' => 'ไม่พบ LINE Channel Access Token สำหรับ Thaiprompt OA'];
            }

            // 1. สร้าง/ดึงภาพ PNG ตามโหมด
            if ($editorMode === 'custom' && $customImagePath) {
                $pngData = Storage::disk('public')->get($customImagePath);
                $areas = $customAreas ?? $this->getDefaultAreas();
            } else {
                $pngData = $this->generateImage($config ?? $this->getDefaultEditorConfig());
                $areas = $this->getAreasFromConfig($config);
            }

            if (empty($pngData)) {
                return ['success' => false, 'error' => 'ไม่สามารถสร้างภาพ Rich Menu ได้'];
            }

            // 2. สร้าง Rich Menu structure บน LINE Platform
            $richMenuData = [
                'size' => ['width' => self::WIDTH, 'height' => self::HEIGHT],
                'selected' => true,
                'name' => 'ไทยพร๊อม - เมนูหลัก',
                'chatBarText' => '📱 เมนูไทยพร๊อม',
                'areas' => $areas,
            ];

            $richMenuId = $this->createRichMenu($richMenuData);
            if (!$richMenuId) {
                return ['success' => false, 'error' => 'สร้าง Rich Menu บน LINE ไม่สำเร็จ'];
            }

            // 3. อัปโหลดภาพ PNG
            $uploaded = $this->uploadRichMenuImage($richMenuId, $pngData);
            if (!$uploaded) {
                $this->deleteRichMenu($richMenuId);
                return ['success' => false, 'error' => 'อัปโหลดภาพ Rich Menu ไม่สำเร็จ'];
            }

            // 4. ตั้งเป็น default สำหรับทุก user
            $this->setDefaultRichMenu($richMenuId);

            // 5. บันทึก image ลง storage
            $imagePath = $this->saveImageToStorage($pngData);

            // 6. ยกเลิก Rich Menu เก่าใน DB
            LineRichMenu::where('name', 'thaiprompt-main')
                ->where('is_active', true)
                ->update(['is_active' => false, 'is_default' => false]);

            // 7. บันทึกลง DB
            $menu = LineRichMenu::create([
                'rich_menu_id' => $richMenuId,
                'name' => 'thaiprompt-main',
                'description' => 'Rich Menu สำหรับ Thaiprompt OA หลัก',
                'size' => 'full',
                'selected' => true,
                'chat_bar_text' => '📱 เมนูไทยพร๊อม',
                'areas' => $areas,
                'editor_config' => $config ?? $this->getDefaultEditorConfig(),
                'editor_mode' => $editorMode,
                'image_path' => $imagePath,
                'is_default' => true,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            Log::info('ThaipromptRichMenu: Deploy สำเร็จ', [
                'rich_menu_id' => $richMenuId,
                'menu_id' => $menu->id,
            ]);

            return [
                'success' => true,
                'rich_menu_id' => $richMenuId,
                'menu' => $menu,
            ];

        } catch (\Throwable $e) {
            Log::error('ThaipromptRichMenu: Deploy ล้มเหลว', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * คืน default editor config สำหรับ Rich Menu 6 ปุ่ม ไทยพร๊อม
     */
    public function getDefaultEditorConfig(): array
    {
        $appUrl = config('app.url', 'https://main.thaiprompt.online');

        return [
            'theme' => [
                'bg_gradient_start' => '#0f172a',
                'bg_gradient_end' => '#1e3a5f',
                'grid_line_color' => '#334155',
                'branding_text' => '~~ ไทยพร๊อม ~~',
                'branding_color' => '#60A5FA',
            ],
            'buttons' => [
                [
                    'label' => 'ตลาดสด',
                    'subtitle' => 'ช๊อปปิ้งของสด',
                    'icon' => 'shopping_cart',
                    'text_color' => '#22C55E',
                    'subtitle_color' => '#86EFAC',
                    'font_size' => 64,
                    'subtitle_size' => 28,
                    'action_type' => 'uri',
                    'action_data' => "{$appUrl}/taladsod",
                ],
                [
                    'label' => 'AI Bots',
                    'subtitle' => 'แชทบอท AI',
                    'icon' => 'robot',
                    'text_color' => '#A78BFA',
                    'subtitle_color' => '#C4B5FD',
                    'font_size' => 64,
                    'subtitle_size' => 28,
                    'action_type' => 'uri',
                    'action_data' => "{$appUrl}/ai-marketplace",
                ],
                [
                    'label' => 'MLM',
                    'subtitle' => 'สร้างรายได้',
                    'icon' => 'money',
                    'text_color' => '#FBBF24',
                    'subtitle_color' => '#FDE68A',
                    'font_size' => 64,
                    'subtitle_size' => 28,
                    'action_type' => 'uri',
                    'action_data' => "{$appUrl}/user/affiliates",
                ],
                [
                    'label' => 'ดูดวง',
                    'subtitle' => 'ดูดวงไทยพร๊อม',
                    'icon' => 'crystal_ball',
                    'text_color' => '#F472B6',
                    'subtitle_color' => '#F9A8D4',
                    'font_size' => 64,
                    'subtitle_size' => 28,
                    'action_type' => 'uri',
                    'action_data' => "{$appUrl}/fortune",
                ],
                [
                    'label' => 'แดชบอร์ด',
                    'subtitle' => 'ข้อมูลของฉัน',
                    'icon' => 'chart',
                    'text_color' => '#38BDF8',
                    'subtitle_color' => '#7DD3FC',
                    'font_size' => 64,
                    'subtitle_size' => 28,
                    'action_type' => 'uri',
                    'action_data' => "{$appUrl}/user/dashboard",
                ],
                [
                    'label' => 'ช่วยเหลือ',
                    'subtitle' => 'วิธีใช้งาน',
                    'icon' => 'info',
                    'text_color' => '#94A3B8',
                    'subtitle_color' => '#CBD5E1',
                    'font_size' => 64,
                    'subtitle_size' => 28,
                    'action_type' => 'message',
                    'action_data' => 'ช่วยเหลือ',
                ],
            ],
        ];
    }

    /**
     * คืน areas สำหรับ Rich Menu 6 ปุ่ม (default)
     */
    public function getDefaultAreas(): array
    {
        $appUrl = config('app.url', 'https://main.thaiprompt.online');

        return [
            // Row 1
            ['bounds' => ['x' => 0, 'y' => 0, 'width' => self::COL_WIDTH, 'height' => self::ROW_HEIGHT],
             'action' => ['type' => 'uri', 'label' => 'ตลาดสด', 'uri' => "{$appUrl}/taladsod"]],
            ['bounds' => ['x' => self::COL_WIDTH, 'y' => 0, 'width' => self::COL_WIDTH + 1, 'height' => self::ROW_HEIGHT],
             'action' => ['type' => 'uri', 'label' => 'AI Bots', 'uri' => "{$appUrl}/ai-marketplace"]],
            ['bounds' => ['x' => self::COL_WIDTH * 2 + 1, 'y' => 0, 'width' => self::COL_WIDTH, 'height' => self::ROW_HEIGHT],
             'action' => ['type' => 'uri', 'label' => 'MLM', 'uri' => "{$appUrl}/user/affiliates"]],
            // Row 2
            ['bounds' => ['x' => 0, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH, 'height' => self::ROW_HEIGHT],
             'action' => ['type' => 'uri', 'label' => 'ดูดวง', 'uri' => "{$appUrl}/fortune"]],
            ['bounds' => ['x' => self::COL_WIDTH, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH + 1, 'height' => self::ROW_HEIGHT],
             'action' => ['type' => 'uri', 'label' => 'แดชบอร์ด', 'uri' => "{$appUrl}/user/dashboard"]],
            ['bounds' => ['x' => self::COL_WIDTH * 2 + 1, 'y' => self::ROW_HEIGHT, 'width' => self::COL_WIDTH, 'height' => self::ROW_HEIGHT],
             'action' => ['type' => 'message', 'label' => 'ช่วยเหลือ', 'text' => 'ช่วยเหลือ']],
        ];
    }

    /**
     * สร้าง areas จาก editor config
     */
    protected function getAreasFromConfig(?array $config): array
    {
        if (!$config || empty($config['buttons'])) {
            return $this->getDefaultAreas();
        }

        $appUrl = config('app.url', 'https://main.thaiprompt.online');
        $areas = [];
        $colWidths = [self::COL_WIDTH, self::COL_WIDTH + 1, self::COL_WIDTH];

        foreach ($config['buttons'] as $i => $button) {
            $row = intdiv($i, 3);
            $col = $i % 3;
            $x = array_sum(array_slice($colWidths, 0, $col));

            $action = match ($button['action_type'] ?? 'message') {
                'uri' => ['type' => 'uri', 'label' => $button['label'], 'uri' => $button['action_data']],
                'postback' => ['type' => 'postback', 'label' => $button['label'], 'data' => $button['action_data']],
                default => ['type' => 'message', 'label' => $button['label'], 'text' => $button['action_data']],
            };

            $areas[] = [
                'bounds' => [
                    'x' => $x,
                    'y' => $row * self::ROW_HEIGHT,
                    'width' => $colWidths[$col],
                    'height' => self::ROW_HEIGHT,
                ],
                'action' => $action,
            ];
        }

        return $areas;
    }

    /**
     * สร้างภาพ Rich Menu ด้วย GD Library (2500x1686)
     */
    protected function generateImage(array $config): string
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        // Background gradient
        $bgStart = $this->hexToRgb($config['theme']['bg_gradient_start'] ?? '#0f172a');
        $bgEnd = $this->hexToRgb($config['theme']['bg_gradient_end'] ?? '#1e3a5f');

        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / self::HEIGHT;
            $r = (int)($bgStart[0] + ($bgEnd[0] - $bgStart[0]) * $ratio);
            $g = (int)($bgStart[1] + ($bgEnd[1] - $bgStart[1]) * $ratio);
            $b = (int)($bgStart[2] + ($bgEnd[2] - $bgStart[2]) * $ratio);
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::WIDTH, $y, $color);
        }

        // Grid lines
        $gridColor = $this->allocateColor($img, $config['theme']['grid_line_color'] ?? '#334155');
        $colWidths = [self::COL_WIDTH, self::COL_WIDTH + 1, self::COL_WIDTH];

        // แนวนอน (กลาง)
        imagesetthickness($img, 2);
        imageline($img, 0, self::ROW_HEIGHT, self::WIDTH, self::ROW_HEIGHT, $gridColor);

        // แนวตั้ง
        $x1 = $colWidths[0];
        $x2 = $colWidths[0] + $colWidths[1];
        imageline($img, $x1, 0, $x1, self::HEIGHT, $gridColor);
        imageline($img, $x2, 0, $x2, self::HEIGHT, $gridColor);
        imagesetthickness($img, 1);

        // วาดข้อความแต่ละปุ่ม
        $fontPath = $this->getFontPath();
        $buttons = $config['buttons'] ?? [];

        foreach ($buttons as $i => $button) {
            $row = intdiv($i, 3);
            $col = $i % 3;
            $cellX = array_sum(array_slice($colWidths, 0, $col));
            $cellY = $row * self::ROW_HEIGHT;
            $cellW = $colWidths[$col];
            $cellH = self::ROW_HEIGHT;
            $centerX = $cellX + $cellW / 2;
            $centerY = $cellY + $cellH / 2;

            // ข้อความหลัก
            $fontSize = $button['font_size'] ?? 64;
            $textColor = $this->allocateColor($img, $button['text_color'] ?? '#FFFFFF');
            $this->drawCenteredText($img, $fontSize, $centerX, $centerY - 40, $button['label'] ?? '', $textColor, $fontPath);

            // Subtitle
            if (!empty($button['subtitle'])) {
                $subSize = $button['subtitle_size'] ?? 28;
                $subColor = $this->allocateColor($img, $button['subtitle_color'] ?? '#94A3B8');
                $this->drawCenteredText($img, $subSize, $centerX, $centerY + 60, $button['subtitle'], $subColor, $fontPath);
            }
        }

        // Branding text (ด้านล่างกลาง)
        if (!empty($config['theme']['branding_text'])) {
            $brandColor = $this->allocateColor($img, $config['theme']['branding_color'] ?? '#60A5FA');
            $this->drawCenteredText($img, 20, self::WIDTH / 2, self::HEIGHT - 15, $config['theme']['branding_text'], $brandColor, $fontPath);
        }

        // Export PNG
        ob_start();
        imagepng($img, null, 6);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }

    /**
     * บันทึกภาพลง storage
     */
    protected function saveImageToStorage(string $pngData): string
    {
        $filename = 'rich-menus/thaiprompt-main-' . now()->format('Ymd-His') . '.png';
        Storage::disk('public')->put($filename, $pngData);

        return $filename;
    }

    /**
     * ดึง font path สำหรับ GD
     */
    protected function getFontPath(): string
    {
        $candidates = [
            resource_path('fonts/Kanit-SemiBold.ttf'),
            resource_path('fonts/Kanit-Bold.ttf'),
            resource_path('fonts/NotoSansThai-SemiBold.ttf'),
            public_path('fonts/Kanit-SemiBold.ttf'),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fallback: ใช้ font ระบบ
        return '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    }

    /**
     * วาดข้อความตรงกลาง
     */
    protected function drawCenteredText($img, int $size, float $centerX, float $centerY, string $text, $color, string $fontPath): void
    {
        if (empty($text)) return;

        $bbox = imagettfbbox($size, 0, $fontPath, $text);
        if ($bbox === false) return;

        $textWidth = abs($bbox[2] - $bbox[0]);
        $textHeight = abs($bbox[7] - $bbox[1]);
        $x = $centerX - $textWidth / 2;
        $y = $centerY + $textHeight / 2;

        imagettftext($img, $size, 0, (int)$x, (int)$y, $color, $fontPath, $text);
    }

    /**
     * แปลง hex color เป็น RGB array
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * สร้าง GD color จาก hex
     */
    protected function allocateColor($img, string $hex, int $alpha = 0): int
    {
        [$r, $g, $b] = $this->hexToRgb($hex);

        if ($alpha > 0) {
            return imagecolorallocatealpha($img, $r, $g, $b, $alpha);
        }

        return imagecolorallocate($img, $r, $g, $b);
    }

    // ===== LINE API Methods =====

    /**
     * สร้าง Rich Menu บน LINE Platform
     */
    public function createRichMenu(array $data): ?string
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->connectTimeout(5)
                ->post(self::LINE_API . '/richmenu', $data);

            if ($response->successful()) {
                return $response->json('richMenuId');
            }

            Log::error('ThaipromptRichMenu: สร้าง Rich Menu ไม่สำเร็จ', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('ThaipromptRichMenu: Exception สร้าง Rich Menu', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * อัปโหลดภาพ Rich Menu
     */
    public function uploadRichMenuImage(string $richMenuId, string $pngBinary): bool
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->withHeaders(['Content-Type' => 'image/png'])
                ->timeout(30)
                ->connectTimeout(10)
                ->withBody($pngBinary, 'image/png')
                ->post("https://api-data.line.me/v2/bot/richmenu/{$richMenuId}/content");

            if ($response->successful()) {
                return true;
            }

            Log::error('ThaipromptRichMenu: อัปโหลดภาพไม่สำเร็จ', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('ThaipromptRichMenu: Exception อัปโหลดภาพ', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ตั้ง Rich Menu เป็น default
     */
    public function setDefaultRichMenu(string $richMenuId): bool
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->connectTimeout(5)
                ->post(self::LINE_API . "/user/all/richmenu/{$richMenuId}");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('ThaipromptRichMenu: Exception ตั้ง default', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ลบ Rich Menu จาก LINE Platform
     */
    public function deleteRichMenu(string $richMenuId): bool
    {
        try {
            $response = Http::withToken($this->channelAccessToken)
                ->timeout(10)
                ->delete(self::LINE_API . "/richmenu/{$richMenuId}");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('ThaipromptRichMenu: Exception ลบ Rich Menu', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
