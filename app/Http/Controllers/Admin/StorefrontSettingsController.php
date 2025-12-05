<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreBanner;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Admin Storefront Settings Controller
 *
 * จัดการการตั้งค่าหน้า Storefront ของระบบ
 * รวมถึง Banners, Layout Settings, และ Customization
 */
class StorefrontSettingsController extends Controller
{
    /**
     * แสดงหน้าตั้งค่า Storefront
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึง Banners ทั้งหมด
        $banners = StoreBanner::forHomepage()
            ->ordered()
            ->get();

        // ดึงหมวดหมู่สำหรับ dropdown
        $categories = ProductCategory::active()
            ->root()
            ->orderBy('name')
            ->get();

        return view('admin.storefront.settings', compact('banners', 'categories'));
    }

    /**
     * แสดงหน้าจัดการ Banners
     *
     * @return \Illuminate\View\View
     */
    public function banners()
    {
        $banners = StoreBanner::forHomepage()
            ->ordered()
            ->withTrashed()
            ->get();

        return view('admin.storefront.banners.index', compact('banners'));
    }

    /**
     * แสดงฟอร์มสร้าง Banner
     *
     * @return \Illuminate\View\View
     */
    public function createBanner()
    {
        $categories = ProductCategory::active()
            ->root()
            ->orderBy('name')
            ->get();

        $gradientOptions = $this->getGradientOptions();

        return view('admin.storefront.banners.create', compact('categories', 'gradientOptions'));
    }

    /**
     * บันทึก Banner ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeBanner(Request $request)
    {
        $validated = $request->validate([
            'location' => 'required|in:homepage,category',
            'category_slug' => 'nullable|required_if:location,category|string|max:100',
            'title' => 'nullable|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'badge' => 'nullable|string|max:50',
            'highlight_text' => 'nullable|string|max:50',
            'highlight_label' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:5120', // 5MB
            'gradient' => 'nullable|string|max:200',
            'cta_text' => 'nullable|string|max:50',
            'cta_url' => 'nullable|url|max:500',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        // อัพโหลดรูปภาพ
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        // สร้าง Banner
        $banner = StoreBanner::create($validated);

        // ล้าง cache
        Cache::forget('storefront_banners');

        return redirect()
            ->route('admin.storefront.banners.index')
            ->with('success', 'สร้าง Banner สำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไข Banner
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function editBanner($id)
    {
        $banner = StoreBanner::findOrFail($id);

        $categories = ProductCategory::active()
            ->root()
            ->orderBy('name')
            ->get();

        $gradientOptions = $this->getGradientOptions();

        return view('admin.storefront.banners.edit', compact('banner', 'categories', 'gradientOptions'));
    }

    /**
     * อัพเดท Banner
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBanner(Request $request, $id)
    {
        $banner = StoreBanner::findOrFail($id);

        $validated = $request->validate([
            'location' => 'required|in:homepage,category',
            'category_slug' => 'nullable|required_if:location,category|string|max:100',
            'title' => 'nullable|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'badge' => 'nullable|string|max:50',
            'highlight_text' => 'nullable|string|max:50',
            'highlight_label' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:5120',
            'gradient' => 'nullable|string|max:200',
            'cta_text' => 'nullable|string|max:50',
            'cta_url' => 'nullable|url|max:500',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        // อัพโหลดรูปภาพใหม่
        if ($request->hasFile('image')) {
            // ลบรูปเก่า
            if ($banner->image_url) {
                $oldPath = str_replace('/storage/', '', $banner->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('banners', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $banner->update($validated);

        // ล้าง cache
        Cache::forget('storefront_banners');

        return redirect()
            ->route('admin.storefront.banners.index')
            ->with('success', 'อัพเดท Banner สำเร็จ');
    }

    /**
     * ลบ Banner
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyBanner($id)
    {
        $banner = StoreBanner::findOrFail($id);

        // ลบรูปภาพ
        if ($banner->image_url) {
            $oldPath = str_replace('/storage/', '', $banner->image_url);
            Storage::disk('public')->delete($oldPath);
        }

        $banner->forceDelete();

        // ล้าง cache
        Cache::forget('storefront_banners');

        return redirect()
            ->route('admin.storefront.banners.index')
            ->with('success', 'ลบ Banner สำเร็จ');
    }

    /**
     * เปลี่ยนสถานะ Banner
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleBannerStatus($id)
    {
        $banner = StoreBanner::findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();

        // ล้าง cache
        Cache::forget('storefront_banners');

        return response()->json([
            'success' => true,
            'is_active' => $banner->is_active,
            'message' => $banner->is_active ? 'เปิดใช้งาน Banner แล้ว' : 'ปิดใช้งาน Banner แล้ว',
        ]);
    }

    /**
     * อัพเดทลำดับ Banners
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderBanners(Request $request)
    {
        $request->validate([
            'banners' => 'required|array',
            'banners.*.id' => 'required|exists:store_banners,id',
            'banners.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->banners as $item) {
            StoreBanner::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        // ล้าง cache
        Cache::forget('storefront_banners');

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทลำดับ Banner สำเร็จ',
        ]);
    }

    /**
     * อัพเดทการตั้งค่าธีมและสี
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'required|string|max:20',
            'accent_color' => 'required|string|max:20',
        ]);

        // บันทึกการตั้งค่าสี
        set_setting('storefront_primary_color', $validated['primary_color'], 'string', 'storefront');
        set_setting('storefront_secondary_color', $validated['secondary_color'], 'string', 'storefront');
        set_setting('storefront_accent_color', $validated['accent_color'], 'string', 'storefront');

        // ล้าง cache
        Cache::forget('storefront_settings');

        return redirect()
            ->route('admin.storefront.index')
            ->with('success', 'อัพเดทการตั้งค่าธีมสำเร็จ');
    }

    /**
     * อัพเดทการตั้งค่าเลย์เอาต์
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateLayout(Request $request)
    {
        $validated = $request->validate([
            'products_per_row' => 'required|in:4,5,6',
            'show_flash_deals' => 'boolean',
            'show_featured_stores' => 'boolean',
            'show_categories' => 'boolean',
            'show_pv_on_products' => 'boolean',
            'show_commission_on_products' => 'boolean',
        ]);

        // บันทึกการตั้งค่าเลย์เอาต์
        set_setting('storefront_products_per_row', $validated['products_per_row'], 'string', 'storefront');
        set_setting('storefront_show_flash_deals', $request->has('show_flash_deals') ? '1' : '0', 'boolean', 'storefront');
        set_setting('storefront_show_featured_stores', $request->has('show_featured_stores') ? '1' : '0', 'boolean', 'storefront');
        set_setting('storefront_show_categories', $request->has('show_categories') ? '1' : '0', 'boolean', 'storefront');
        set_setting('storefront_show_pv', $request->has('show_pv_on_products') ? '1' : '0', 'boolean', 'storefront');
        set_setting('storefront_show_commission', $request->has('show_commission_on_products') ? '1' : '0', 'boolean', 'storefront');

        // ล้าง cache
        Cache::forget('storefront_settings');

        return redirect()
            ->route('admin.storefront.index')
            ->with('success', 'อัพเดทการตั้งค่าเลย์เอาต์สำเร็จ');
    }

    /**
     * ดึงรายการ Gradient Options
     *
     * @return array
     */
    private function getGradientOptions(): array
    {
        return [
            'from-orange-500 via-red-500 to-pink-600' => 'Orange → Red → Pink (Flash Sale)',
            'from-purple-600 via-pink-500 to-red-500' => 'Purple → Pink → Red (Premium)',
            'from-blue-600 via-indigo-500 to-purple-600' => 'Blue → Indigo → Purple (Official)',
            'from-green-500 via-emerald-500 to-teal-500' => 'Green → Emerald → Teal (Success)',
            'from-yellow-400 via-orange-500 to-red-500' => 'Yellow → Orange → Red (Hot)',
            'from-cyan-500 via-blue-500 to-indigo-500' => 'Cyan → Blue → Indigo (Tech)',
            'from-pink-500 via-rose-500 to-red-500' => 'Pink → Rose → Red (Love)',
            'from-violet-600 via-purple-600 to-indigo-600' => 'Violet → Purple → Indigo (Luxury)',
        ];
    }
}
