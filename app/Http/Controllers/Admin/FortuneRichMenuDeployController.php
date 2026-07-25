<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Models\LineRichMenu;
use App\Services\FortuneRichMenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Rich Menu Deploy Controller
 *
 * จัดการ deploy Rich Menu สำหรับบอทแม่หมอจันทรา
 */
class FortuneRichMenuDeployController extends Controller
{
    /**
     * แสดงหน้า Rich Menu Deploy
     */
    public function index()
    {
        // Rich Menu ที่ active อยู่
        $activeMenu = LineRichMenu::where('name', 'fortune-telling-bot')
            ->where('is_active', true)
            ->latest()
            ->first();

        // ประวัติ deploy ล่าสุด 10 รายการ
        $deployHistory = LineRichMenu::where('name', 'fortune-telling-bot')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.fortune.rich-menu.index', compact(
            'activeMenu',
            'deployHistory'
        ));
    }

    /**
     * Preview ภาพ Rich Menu (สร้างเฉพาะภาพ ไม่ deploy)
     */
    public function preview()
    {
        try {
            $service = new FortuneRichMenuService;
            $pngData = $service->generateImage();

            if (empty($pngData)) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่สามารถสร้างภาพ Rich Menu ได้',
                ], 500);
            }

            // บันทึกภาพ preview ชั่วคราว
            $previewPath = 'fortune/rich-menu/preview-'.time().'.png';
            \Illuminate\Support\Facades\Storage::disk('public')->put($previewPath, $pngData);

            return response()->json([
                'success' => true,
                'preview_url' => \Illuminate\Support\Facades\Storage::disk('public')->url($previewPath),
            ]);

        } catch (\Throwable $e) {
            Log::error('FortuneRichMenu: Preview ล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deploy Rich Menu ไป LINE Platform
     */
    public function deploy(Request $request)
    {
        try {
            $service = new FortuneRichMenuService;
            $result = $service->generateAndDeploy();

            if ($result['success']) {
                // 🔗 (2026-07-25) แจ้งจำนวนคนที่ถูกผลักเมนูใหม่ให้ (ทับ per-user link เดิม)
                $synced = (int) ($result['synced_users'] ?? 0);

                return response()->json([
                    'success' => true,
                    'message' => 'Deploy Rich Menu สำเร็จ!'
                        .($synced > 0 ? " · อัปเดตให้ลูกค้า {$synced} คนแล้ว" : ''),
                    'rich_menu_id' => $result['rich_menu_id'],
                    'synced_users' => $synced,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Deploy ไม่สำเร็จ',
            ], 500);

        } catch (\Throwable $e) {
            Log::error('FortuneRichMenu: Deploy ล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Re-set Rich Menu ที่มีอยู่เป็น default อีกครั้ง (ไม่สร้างใหม่)
     *
     * ใช้กรณีที่ Rich Menu ไม่ขึ้น ให้ลอง re-set default
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reSetDefault()
    {
        try {
            // ดึง Rich Menu ID ที่ active ใน DB
            $activeMenu = LineRichMenu::where('name', 'fortune-telling-bot')
                ->where('is_active', true)
                ->latest()
                ->first();

            if (! $activeMenu || ! $activeMenu->rich_menu_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่พบ Rich Menu ที่ active ใน DB — กรุณา Deploy ใหม่',
                ], 404);
            }

            $richMenuId = $activeMenu->rich_menu_id;

            // เรียก LINE API เพื่อ set default อีกครั้ง
            $service = new FortuneRichMenuService;
            $lineService = $service->getLineService();
            $result = $lineService->setDefaultRichMenu($richMenuId);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => "Re-set default Rich Menu สำเร็จ! ({$richMenuId})",
                    'rich_menu_id' => $richMenuId,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Re-set default ไม่สำเร็จ — LINE API อาจมีปัญหา',
            ], 500);

        } catch (\Throwable $e) {
            Log::error('FortuneRichMenu: Re-set default ล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * เช็คสถานะ Rich Menu จาก LINE API โดยตรง
     *
     * ตรวจสอบว่า default Rich Menu ที่ตั้งอยู่บน LINE Platform ตรงกับ DB หรือไม่
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkLineStatus()
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $token = $settings->line_channel_access_token ?? config('services.line.channel_token');

            // 1. เช็ค default Rich Menu ปัจจุบัน
            $defaultResponse = Http::withToken($token)
                ->timeout(30)->connectTimeout(15)
                ->get('https://api.line.me/v2/bot/user/all/richmenu');

            $defaultRichMenuId = null;
            if ($defaultResponse->successful()) {
                $defaultRichMenuId = $defaultResponse->json('richMenuId');
            }

            // 2. เช็ค Rich Menu ทั้งหมดบน LINE
            $listResponse = Http::withToken($token)
                ->timeout(30)->connectTimeout(15)
                ->get('https://api.line.me/v2/bot/richmenu/list');

            $richMenus = [];
            if ($listResponse->successful()) {
                $richMenus = $listResponse->json('richmenus', []);
            }

            // 3. เช็คจาก DB
            $activeMenu = LineRichMenu::where('name', 'fortune-telling-bot')
                ->where('is_active', true)
                ->latest()
                ->first();

            // 4. เปรียบเทียบ
            $dbId = $activeMenu?->rich_menu_id;
            $isMatched = $defaultRichMenuId && $dbId && $defaultRichMenuId === $dbId;

            return response()->json([
                'success' => true,
                'line_default_rich_menu_id' => $defaultRichMenuId,
                'db_active_rich_menu_id' => $dbId,
                'is_matched' => $isMatched,
                'total_rich_menus_on_line' => count($richMenus),
                'rich_menus' => collect($richMenus)->map(fn ($m) => [
                    'richMenuId' => $m['richMenuId'],
                    'name' => $m['name'] ?? '-',
                    'selected' => $m['selected'] ?? false,
                ])->toArray(),
            ]);

        } catch (\Throwable $e) {
            Log::error('FortuneRichMenu: เช็คสถานะ LINE ล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
