<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ServiceCategoryController - จัดการหมวดหมู่บริการ (Admin)
 *
 * CRUD สำหรับหมวดหมู่บริการ
 */
class ServiceCategoryController extends Controller
{
    /**
     * แสดงรายการหมวดหมู่ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $categories = ServiceCategory::withCount('services')
            ->ordered()
            ->paginate(15);

        return view('admin.service-categories.index', [
            'categories' => $categories,
            'pageTitle' => 'จัดการหมวดหมู่บริการ',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างหมวดหมู่ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.service-categories.create', [
            'pageTitle' => 'สร้างหมวดหมู่บริการใหม่',
        ]);
    }

    /**
     * บันทึกหมวดหมู่ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:service_categories,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'image' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // สร้าง slug อัตโนมัติถ้าไม่ระบุ
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = ServiceCategory::create($validated);

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'สร้างหมวดหมู่สำเร็จ');
    }

    /**
     * แสดงรายละเอียดหมวดหมู่
     *
     * @param ServiceCategory $serviceCategory
     * @return \Illuminate\View\View
     */
    public function show(ServiceCategory $serviceCategory)
    {
        $serviceCategory->loadCount('services', 'activeServices');

        return view('admin.service-categories.show', [
            'category' => $serviceCategory,
            'pageTitle' => 'หมวดหมู่: ' . $serviceCategory->name,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไข
     *
     * @param ServiceCategory $serviceCategory
     * @return \Illuminate\View\View
     */
    public function edit(ServiceCategory $serviceCategory)
    {
        return view('admin.service-categories.edit', [
            'category' => $serviceCategory,
            'pageTitle' => 'แก้ไขหมวดหมู่: ' . $serviceCategory->name,
        ]);
    }

    /**
     * อัพเดทหมวดหมู่
     *
     * @param Request $request
     * @param ServiceCategory $serviceCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:service_categories,slug,' . $serviceCategory->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'image' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $serviceCategory->update($validated);

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'อัพเดทหมวดหมู่สำเร็จ');
    }

    /**
     * ลบหมวดหมู่
     *
     * @param ServiceCategory $serviceCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ServiceCategory $serviceCategory)
    {
        // ตรวจสอบว่ามีบริการในหมวดหมู่นี้หรือไม่
        if ($serviceCategory->services()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบหมวดหมู่ได้ เนื่องจากมีบริการอยู่ในหมวดหมู่นี้');
        }

        $serviceCategory->delete();

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'ลบหมวดหมู่สำเร็จ');
    }

    /**
     * เปลี่ยนสถานะ active/inactive
     *
     * @param ServiceCategory $serviceCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive(ServiceCategory $serviceCategory)
    {
        $serviceCategory->is_active = !$serviceCategory->is_active;
        $serviceCategory->save();

        return response()->json([
            'success' => true,
            'is_active' => $serviceCategory->is_active,
            'message' => $serviceCategory->is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว',
        ]);
    }

    /**
     * เปลี่ยนสถานะ featured
     *
     * @param ServiceCategory $serviceCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFeatured(ServiceCategory $serviceCategory)
    {
        $serviceCategory->is_featured = !$serviceCategory->is_featured;
        $serviceCategory->save();

        return response()->json([
            'success' => true,
            'is_featured' => $serviceCategory->is_featured,
            'message' => $serviceCategory->is_featured ? 'ตั้งเป็นแนะนำแล้ว' : 'ยกเลิกแนะนำแล้ว',
        ]);
    }

    /**
     * จัดเรียงลำดับ (Drag & Drop)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:service_categories,id',
        ]);

        foreach ($validated['order'] as $index => $categoryId) {
            ServiceCategory::where('id', $categoryId)->update([
                'sort_order' => $index + 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'จัดเรียงลำดับสำเร็จ',
        ]);
    }
}
