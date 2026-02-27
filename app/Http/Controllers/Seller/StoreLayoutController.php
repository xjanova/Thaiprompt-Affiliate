<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\StoreLayoutSetting;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Store Layout Controller
 *
 * จัดการการตั้งค่า Layout ของร้านค้าผู้เช่า (Seller Store)
 * รองรับ: สี, แบนเนอร์, สไลด์, sections, และ custom styles
 *
 * @author TP-Affiliate
 */
class StoreLayoutController extends Controller
{
    /**
     * แสดงหน้า Layout Editor
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = auth()->user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (! $store) {
            return redirect()->route('seller.store.settings')
                ->with('error', 'กรุณาตั้งค่าร้านค้าก่อนปรับแต่ง Layout');
        }

        // ดึงหรือสร้าง layout settings
        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);

        // ดึง options สำหรับ form
        $layoutStyles = StoreLayoutSetting::getLayoutStyles();
        $headerStyles = StoreLayoutSetting::getHeaderStyles();
        $sliderEffects = StoreLayoutSetting::getSliderEffects();
        $defaultSectionsOrder = StoreLayoutSetting::getDefaultSectionsOrder();
        $defaultSectionsVisibility = StoreLayoutSetting::getDefaultSectionsVisibility();

        return view('seller.store.layout-editor', compact(
            'store',
            'layoutSettings',
            'layoutStyles',
            'headerStyles',
            'sliderEffects',
            'defaultSectionsOrder',
            'defaultSectionsVisibility'
        ));
    }

    /**
     * บันทึกการตั้งค่า Layout ทั่วไป (Colors, Header, Footer)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveGeneral(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            // Theme Colors
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'accent_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'background_color' => 'nullable|string|max:7',
            'header_bg_color' => 'nullable|string|max:7',
            'footer_bg_color' => 'nullable|string|max:7',
            // Header Section
            'header_style' => 'nullable|string|in:gradient,solid,image,transparent',
            'header_height' => 'nullable|integer|min:100|max:500',
            'show_store_logo' => 'nullable|boolean',
            'show_store_name' => 'nullable|boolean',
            'show_store_description' => 'nullable|boolean',
            'show_store_stats' => 'nullable|boolean',
            // Layout Style
            'layout_style' => 'nullable|string|in:modern,classic,minimal,bold,lazada,shopee,aliexpress',
            'product_card_style' => 'nullable|string|in:default,minimal,detailed',
            'products_per_row' => 'nullable|integer|min:2|max:6',
            'sidebar_position' => 'nullable|string|in:left,right,none',
            'show_sidebar' => 'nullable|boolean',
            // Footer
            'show_footer' => 'nullable|boolean',
            'footer_content' => 'nullable|string',
            'show_contact_info' => 'nullable|boolean',
            'show_social_links' => 'nullable|boolean',
        ]);

        // แปลง checkboxes เป็น boolean
        $booleanFields = [
            'show_store_logo', 'show_store_name', 'show_store_description',
            'show_store_stats', 'show_sidebar', 'show_footer',
            'show_contact_info', 'show_social_links',
        ];

        foreach ($booleanFields as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = (bool) $validated[$field];
            }
        }

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าทั่วไปสำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * บันทึกการตั้งค่า Slider
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSlider(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'slider_enabled' => 'nullable|boolean',
            'slider_autoplay_speed' => 'nullable|integer|min:1000|max:10000',
            'slider_show_arrows' => 'nullable|boolean',
            'slider_show_dots' => 'nullable|boolean',
            'slider_effect' => 'nullable|string|in:slide,fade,cube',
            'slider_images' => 'nullable|array',
            'slider_images.*.image' => 'nullable|string',
            'slider_images.*.link' => 'nullable|string',
            'slider_images.*.title' => 'nullable|string|max:255',
            'slider_images.*.subtitle' => 'nullable|string|max:500',
        ]);

        // แปลง checkboxes เป็น boolean
        $booleanFields = ['slider_enabled', 'slider_show_arrows', 'slider_show_dots'];
        foreach ($booleanFields as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = (bool) $validated[$field];
            }
        }

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่า Slider สำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * อัพโหลดรูปภาพ Slider
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSliderImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $user = auth()->user();

        try {
            $imagePath = $this->convertAndSaveAsWebP(
                $request->file('image'),
                'stores/sliders/'.$user->id,
                1920,
                800
            );

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดรูปภาพสำเร็จ',
                'path' => $imagePath,
                'url' => Storage::url($imagePath),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'อัพโหลดรูปภาพไม่สำเร็จ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบรูปภาพ Slider
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSliderImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $user = auth()->user();
        $path = $request->input('path');

        // ตรวจสอบว่าเป็นรูปของ user นี้
        if (! str_contains($path, 'stores/sliders/'.$user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบรูปภาพนี้ได้',
            ], 403);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json([
            'success' => true,
            'message' => 'ลบรูปภาพสำเร็จ',
        ]);
    }

    /**
     * อัพโหลดรูปภาพ Header
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadHeaderImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $user = auth()->user();

        try {
            // ลบรูปเก่า (ถ้ามี)
            $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
            if ($layoutSettings->header_image && Storage::disk('public')->exists($layoutSettings->header_image)) {
                Storage::disk('public')->delete($layoutSettings->header_image);
            }

            $imagePath = $this->convertAndSaveAsWebP(
                $request->file('image'),
                'stores/headers/'.$user->id,
                1920,
                600
            );

            // อัพเดท layout settings
            $layoutSettings->update(['header_image' => $imagePath]);

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดรูปภาพ Header สำเร็จ',
                'path' => $imagePath,
                'url' => Storage::url($imagePath),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'อัพโหลดรูปภาพไม่สำเร็จ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * บันทึกการตั้งค่า Featured Products
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveFeatured(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'show_featured_products' => 'nullable|boolean',
            'featured_title' => 'nullable|string|max:255',
            'featured_products_count' => 'nullable|integer|min:4|max:24',
        ]);

        if (isset($validated['show_featured_products'])) {
            $validated['show_featured_products'] = (bool) $validated['show_featured_products'];
        }

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าสินค้าแนะนำสำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * บันทึกการตั้งค่า Categories Section
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCategories(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'show_categories' => 'nullable|boolean',
            'categories_title' => 'nullable|string|max:255',
            'categories_style' => 'nullable|string|in:grid,list,carousel',
        ]);

        if (isset($validated['show_categories'])) {
            $validated['show_categories'] = (bool) $validated['show_categories'];
        }

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าหมวดหมู่สำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * บันทึกการตั้งค่า Promotion Banner
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePromotion(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'show_promotion_banner' => 'nullable|boolean',
            'promotion_link' => 'nullable|string|max:500',
            'promotion_text' => 'nullable|string|max:255',
        ]);

        if (isset($validated['show_promotion_banner'])) {
            $validated['show_promotion_banner'] = (bool) $validated['show_promotion_banner'];
        }

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าโปรโมชั่นสำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * อัพโหลดรูปภาพ Promotion
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPromotionImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $user = auth()->user();

        try {
            // ลบรูปเก่า (ถ้ามี)
            $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
            if ($layoutSettings->promotion_image && Storage::disk('public')->exists($layoutSettings->promotion_image)) {
                Storage::disk('public')->delete($layoutSettings->promotion_image);
            }

            $imagePath = $this->convertAndSaveAsWebP(
                $request->file('image'),
                'stores/promotions/'.$user->id,
                1920,
                400
            );

            // อัพเดท layout settings
            $layoutSettings->update(['promotion_image' => $imagePath]);

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดรูปภาพโปรโมชั่นสำเร็จ',
                'path' => $imagePath,
                'url' => Storage::url($imagePath),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'อัพโหลดรูปภาพไม่สำเร็จ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * บันทึกการตั้งค่า Social Links
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSocialLinks(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.line' => 'nullable|string|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.twitter' => 'nullable|url|max:255',
            'social_links.tiktok' => 'nullable|url|max:255',
            'social_links.youtube' => 'nullable|url|max:255',
        ]);

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึก Social Links สำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * บันทึกการตั้งค่า SEO
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSeo(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่า SEO สำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * บันทึกการตั้งค่า Custom CSS/JS
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCustomCode(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'custom_css' => 'nullable|string|max:10000',
            'custom_js' => 'nullable|string|max:10000',
        ]);

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึก Custom Code สำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * บันทึกลำดับ Sections
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSectionsOrder(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'sections_order' => 'nullable|array',
            'sections_visibility' => 'nullable|array',
        ]);

        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);
        $layoutSettings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกลำดับ Sections สำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * เผยแพร่ Layout
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function publish()
    {
        $user = auth()->user();
        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);

        $layoutSettings->publish();

        return response()->json([
            'success' => true,
            'message' => 'เผยแพร่ Layout ร้านค้าสำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * ยกเลิกการเผยแพร่ Layout
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unpublish()
    {
        $user = auth()->user();
        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);

        $layoutSettings->unpublish();

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการเผยแพร่ Layout สำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * รีเซ็ต Layout กลับเป็นค่าเริ่มต้น
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset()
    {
        $user = auth()->user();
        $layoutSettings = StoreLayoutSetting::where('user_id', $user->id)->first();

        if ($layoutSettings) {
            // ลบรูปภาพทั้งหมด
            if ($layoutSettings->header_image && Storage::disk('public')->exists($layoutSettings->header_image)) {
                Storage::disk('public')->delete($layoutSettings->header_image);
            }
            if ($layoutSettings->promotion_image && Storage::disk('public')->exists($layoutSettings->promotion_image)) {
                Storage::disk('public')->delete($layoutSettings->promotion_image);
            }
            // ลบรูป slider
            if ($layoutSettings->slider_images) {
                foreach ($layoutSettings->slider_images as $slide) {
                    if (isset($slide['image']) && Storage::disk('public')->exists($slide['image'])) {
                        Storage::disk('public')->delete($slide['image']);
                    }
                }
            }

            // ลบ layout settings
            $layoutSettings->delete();
        }

        // สร้างใหม่ด้วยค่าเริ่มต้น
        $newSettings = StoreLayoutSetting::getOrCreateForUser($user->id);

        return response()->json([
            'success' => true,
            'message' => 'รีเซ็ต Layout เป็นค่าเริ่มต้นสำเร็จ',
            'data' => $newSettings,
        ]);
    }

    /**
     * ใช้เทมเพลต Layout ที่เลือก
     *
     * จะเขียนทับเฉพาะค่าสี, header style, product card, layout settings
     * แต่ไม่เขียนทับ: รูปภาพ, ข้อความ, social links, SEO, custom code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required|string|in:modern,classic,minimal,bold,lazada,shopee,aliexpress',
        ]);

        $user = auth()->user();
        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);

        $success = $layoutSettings->applyPreset($request->template);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบเทมเพลตที่เลือก',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'ใช้เทมเพลต "' . $request->template . '" สำเร็จ',
            'data' => $layoutSettings->fresh(),
        ]);
    }

    /**
     * ดึงข้อมูล Preview (สำหรับ iframe preview)
     *
     * @return \Illuminate\View\View
     */
    public function preview()
    {
        $user = auth()->user();
        $store = VendorStore::where('user_id', $user->id)->first();
        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($user->id);

        return view('seller.store.layout-preview', compact('store', 'layoutSettings'));
    }

    /**
     * แปลงและบันทึกรูปภาพเป็นรูปแบบ WebP
     *
     * @param  \Illuminate\Http\UploadedFile  $file  ไฟล์รูปภาพที่อัปโหลด
     * @param  string  $directory  โฟลเดอร์ที่จะบันทึก
     * @param  int  $maxWidth  ความกว้างสูงสุด
     * @param  int  $maxHeight  ความสูงสูงสุด
     * @return string เส้นทางไฟล์ที่บันทึก
     */
    private function convertAndSaveAsWebP($file, $directory, $maxWidth = 1920, $maxHeight = 1080)
    {
        try {
            // สร้างชื่อไฟล์ unique
            $filename = Str::random(40).'.webp';
            $fullPath = $directory.'/'.$filename;

            // สร้าง ImageManager ด้วย GD driver (Intervention Image v3)
            $manager = new ImageManager(new Driver);

            // โหลดรูปภาพโดยใช้ Intervention Image v3 API
            $image = $manager->read($file->getPathname());

            // ปรับขนาดรูปภาพโดยรักษาสัดส่วน (ไม่ขยายถ้าเล็กกว่า)
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scaleDown(width: $maxWidth, height: $maxHeight);
            }

            // เข้ารหัสเป็น WebP ที่คุณภาพ 85
            $encodedImage = $image->toWebp(quality: 85);

            // บันทึกลง storage
            Storage::disk('public')->put($fullPath, (string) $encodedImage);

            return $fullPath;
        } catch (\Exception $e) {
            // Fallback: ถ้าแปลง WebP ไม่สำเร็จ ให้บันทึกไฟล์ต้นฉบับ
            \Log::error('WebP conversion failed: '.$e->getMessage());

            // บันทึกไฟล์ต้นฉบับโดยไม่แปลง
            $originalPath = $file->store($directory, 'public');

            return $originalPath;
        }
    }
}
