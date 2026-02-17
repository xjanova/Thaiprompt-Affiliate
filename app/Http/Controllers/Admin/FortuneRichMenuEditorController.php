<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineRichMenu;
use App\Services\FortuneRichMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * FortuneRichMenuEditorController — ระบบแก้ไข Rich Menu แม่หมอจันทรา
 *
 * รองรับ 2 โหมด:
 * - Config Editor: แก้ไขปุ่ม/สี/ข้อความ → auto-generate ภาพ GD
 * - Custom Upload: อัปโหลดภาพ PNG เอง + วาดพื้นที่คลิก
 */
class FortuneRichMenuEditorController extends Controller
{
    /**
     * แสดงหน้า Rich Menu Editor
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึง Rich Menu ที่ active อยู่
        $activeMenu = LineRichMenu::where('name', 'fortune-telling-bot')
            ->where('is_active', true)
            ->latest()
            ->first();

        return view('admin.fortune.rich-menu.editor', compact('activeMenu'));
    }

    /**
     * โหลด editor config ปัจจุบัน
     *
     * ลำดับ: active menu → default config
     *
     * @return JsonResponse
     */
    public function loadConfig(): JsonResponse
    {
        try {
            $service = new FortuneRichMenuService;

            // ดึงจาก active menu ก่อน
            $activeMenu = LineRichMenu::where('name', 'fortune-telling-bot')
                ->where('is_active', true)
                ->latest()
                ->first();

            if ($activeMenu && ! empty($activeMenu->editor_config)) {
                $config = $activeMenu->editor_config;
                $editorMode = $activeMenu->editor_mode ?? 'config';
                $imagePath = $activeMenu->image_path;
            } else {
                // ใช้ default config
                $config = $service->getDefaultEditorConfig();
                $editorMode = 'config';
                $imagePath = $activeMenu?->image_path;
            }

            return response()->json([
                'success' => true,
                'config' => $config,
                'editor_mode' => $editorMode,
                'image_url' => $imagePath ? Storage::disk('public')->url($imagePath) : null,
                'active_menu_id' => $activeMenu?->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('RichMenuEditor: โหลด config ล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * บันทึก config draft (ยังไม่ deploy)
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function saveConfig(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'config' => 'required|array',
                'config.theme' => 'required|array',
                'config.buttons' => 'required|array|min:1|max:6',
                'editor_mode' => 'required|in:config,custom',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $config = $request->input('config');
            $editorMode = $request->input('editor_mode', 'config');

            // ดึง active menu หรือสร้าง draft ใหม่
            $menu = LineRichMenu::where('name', 'fortune-telling-bot')
                ->where('is_active', true)
                ->latest()
                ->first();

            if ($menu) {
                // อัปเดท config ที่มีอยู่
                $menu->update([
                    'editor_config' => $config,
                    'editor_mode' => $editorMode,
                ]);
            } else {
                // สร้าง draft record ใหม่ (ยังไม่มี rich_menu_id)
                $menu = LineRichMenu::create([
                    'name' => 'fortune-telling-bot',
                    'description' => 'Rich Menu Draft — ยังไม่ deploy',
                    'size' => 'full',
                    'selected' => false,
                    'chat_bar_text' => '🔮 เมนูดูดวง',
                    'areas' => [],
                    'editor_config' => $config,
                    'editor_mode' => $editorMode,
                    'is_default' => false,
                    'is_active' => false,
                    'created_by' => auth()->id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'บันทึก config สำเร็จ',
                'menu_id' => $menu->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('RichMenuEditor: บันทึก config ล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * สร้าง preview PNG จาก config
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $config = $request->input('config');

            if (empty($config) || empty($config['buttons'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'กรุณาระบุ config (theme + buttons)',
                ], 422);
            }

            $service = new FortuneRichMenuService;
            $pngData = $service->generateImageFromConfig($config);

            if (empty($pngData)) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่สามารถสร้างภาพ preview ได้',
                ], 500);
            }

            // บันทึกภาพ preview ชั่วคราว
            $filename = 'rich-menu-preview-' . time() . '-' . Str::random(4) . '.png';
            $previewPath = "fortune/rich-menu/{$filename}";
            Storage::disk('public')->put($previewPath, $pngData);

            return response()->json([
                'success' => true,
                'preview_url' => Storage::disk('public')->url($previewPath),
            ]);

        } catch (\Throwable $e) {
            Log::error('RichMenuEditor: สร้าง preview ล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัปโหลดภาพ PNG สำหรับ custom mode
     *
     * Validate ขนาด 2500x1686 (full) หรือ 2500x843 (half)
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:png,jpg,jpeg|max:10240', // 10MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'กรุณาอัปโหลดไฟล์ PNG/JPG ขนาดไม่เกิน 10MB',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $file = $request->file('image');

            // ตรวจสอบขนาดภาพ
            $imageSize = getimagesize($file->getPathname());
            if ($imageSize === false) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่สามารถอ่านขนาดภาพได้',
                ], 422);
            }

            $width = $imageSize[0];
            $height = $imageSize[1];

            // ตรวจสอบขนาดที่ LINE รองรับ
            $validSizes = [
                [2500, 1686], // full
                [2500, 843],  // half
            ];

            $isValidSize = false;
            foreach ($validSizes as $size) {
                if ($width === $size[0] && $height === $size[1]) {
                    $isValidSize = true;
                    break;
                }
            }

            if (! $isValidSize) {
                return response()->json([
                    'success' => false,
                    'error' => "ขนาดภาพไม่ถูกต้อง ({$width}x{$height}) — ต้องเป็น 2500x1686 หรือ 2500x843",
                    'current_size' => ['width' => $width, 'height' => $height],
                    'valid_sizes' => $validSizes,
                ], 422);
            }

            // บันทึกภาพ
            $filename = 'rich-menu-custom-' . time() . '-' . Str::random(6) . '.png';
            $path = $file->storeAs('rich-menus', $filename, 'public');

            return response()->json([
                'success' => true,
                'message' => 'อัปโหลดภาพสำเร็จ',
                'image_path' => $path,
                'image_url' => Storage::disk('public')->url($path),
                'dimensions' => ['width' => $width, 'height' => $height],
            ]);

        } catch (\Throwable $e) {
            Log::error('RichMenuEditor: อัปโหลดภาพล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deploy Rich Menu ไป LINE Platform
     *
     * รองรับทั้ง config mode และ custom mode
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deploy(Request $request): JsonResponse
    {
        try {
            $editorMode = $request->input('editor_mode', 'config');
            $config = $request->input('config');
            $customImagePath = $request->input('custom_image_path');
            $customAreas = $request->input('custom_areas');

            // Validate ตามโหมด
            if ($editorMode === 'config') {
                if (empty($config) || empty($config['buttons'])) {
                    return response()->json([
                        'success' => false,
                        'error' => 'กรุณาระบุ config (theme + buttons) สำหรับ config mode',
                    ], 422);
                }
            } elseif ($editorMode === 'custom') {
                if (empty($customImagePath)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'กรุณาอัปโหลดภาพก่อน deploy (custom mode)',
                    ], 422);
                }

                if (! Storage::disk('public')->exists($customImagePath)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'ไม่พบไฟล์ภาพ กรุณาอัปโหลดใหม่',
                    ], 404);
                }
            }

            $service = new FortuneRichMenuService;
            $result = $service->generateAndDeploy(
                config: $editorMode === 'config' ? $config : null,
                customImagePath: $editorMode === 'custom' ? $customImagePath : null,
                customAreas: $editorMode === 'custom' ? $customAreas : null,
                editorMode: $editorMode,
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Deploy Rich Menu สำเร็จ!',
                    'rich_menu_id' => $result['rich_menu_id'],
                    'menu_id' => $result['menu']->id ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Deploy ไม่สำเร็จ',
            ], 500);

        } catch (\Throwable $e) {
            Log::error('RichMenuEditor: Deploy ล้มเหลว', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
