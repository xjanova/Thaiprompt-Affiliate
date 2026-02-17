<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineRichMenu;
use App\Services\FortuneRichMenuService;
use Illuminate\Http\Request;
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
                return response()->json([
                    'success' => true,
                    'message' => 'Deploy Rich Menu สำเร็จ!',
                    'rich_menu_id' => $result['rich_menu_id'],
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
}
