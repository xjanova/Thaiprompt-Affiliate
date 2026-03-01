<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineRichMenu;
use App\Services\ThaipromptRichMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ThaipromptRichMenuController — จัดการ Rich Menu สำหรับ Thaiprompt OA หลัก
 *
 * รองรับ 2 โหมด:
 * - Config Editor: แก้ไขปุ่ม/สี/ข้อความ → auto-generate ภาพ GD
 * - Custom Upload: อัปโหลดภาพ PNG เอง + วาดพื้นที่คลิก
 */
class ThaipromptRichMenuController extends Controller
{
    /**
     * แสดงหน้า Rich Menu Editor สำหรับ Thaiprompt OA
     */
    public function editor()
    {
        $activeMenu = LineRichMenu::where('name', 'thaiprompt-main')
            ->where('is_active', true)
            ->latest()
            ->first();

        $service = new ThaipromptRichMenuService;
        $defaultConfig = $service->getDefaultEditorConfig();

        return view('admin.rich-menu.thaiprompt-editor', compact('activeMenu', 'defaultConfig'));
    }

    /**
     * โหลด editor config ปัจจุบัน
     */
    public function loadConfig(): JsonResponse
    {
        try {
            $service = new ThaipromptRichMenuService;

            $activeMenu = LineRichMenu::where('name', 'thaiprompt-main')
                ->where('is_active', true)
                ->latest()
                ->first();

            if ($activeMenu && !empty($activeMenu->editor_config)) {
                $config = $activeMenu->editor_config;
                $editorMode = $activeMenu->editor_mode ?? 'config';
                $imagePath = $activeMenu->image_path;
            } else {
                $config = $service->getDefaultEditorConfig();
                $editorMode = 'config';
                $imagePath = null;
            }

            return response()->json([
                'success' => true,
                'config' => $config,
                'editor_mode' => $editorMode,
                'image_url' => $imagePath ? Storage::disk('public')->url($imagePath) : null,
                'active_menu_id' => $activeMenu?->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('ThaipromptRichMenu: โหลด config ล้มเหลว', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Deploy Rich Menu ไป LINE API
     */
    public function deploy(Request $request): JsonResponse
    {
        try {
            $service = new ThaipromptRichMenuService;
            $editorMode = $request->input('editor_mode', 'config');
            $config = $request->input('config');
            $customImagePath = $request->input('custom_image_path');
            $customAreas = $request->input('custom_areas');

            $result = $service->generateAndDeploy(
                config: $config,
                customImagePath: $customImagePath,
                customAreas: $customAreas,
                editorMode: $editorMode
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Deploy Rich Menu ไทยพร้อมสำเร็จ!',
                    'rich_menu_id' => $result['rich_menu_id'],
                    'image_url' => $result['menu']->image_path
                        ? Storage::disk('public')->url($result['menu']->image_path)
                        : null,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'ไม่ทราบสาเหตุ',
            ], 500);

        } catch (\Throwable $e) {
            Log::error('ThaipromptRichMenu: Deploy ล้มเหลว', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * อัปโหลดภาพ PNG สำหรับ custom mode
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        try {
            $path = $request->file('image')->store('rich-menus/thaiprompt', 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
