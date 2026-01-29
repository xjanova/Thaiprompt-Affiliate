<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneCategory;
use Illuminate\Http\Request;

/**
 * Fortune Categories Controller
 *
 * จัดการหมวดหมู่การทำนาย
 */
class FortuneCategoriesController extends Controller
{
    /**
     * แสดงรายการหมวดหมู่
     */
    public function index()
    {
        $categories = FortuneCategory::ordered()->paginate(15);

        return view('admin.fortune.categories.index', [
            'categories' => $categories,
            'pageTitle' => 'จัดการหมวดหมู่การทำนาย',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างหมวดหมู่ใหม่
     */
    public function create()
    {
        return view('admin.fortune.categories.create', [
            'pageTitle' => 'เพิ่มหมวดหมู่การทำนาย',
        ]);
    }

    /**
     * บันทึกหมวดหมู่ใหม่
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:fortune_categories,slug',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:20',
            'description' => 'nullable|string',
            'prompt_context' => 'nullable|string',
            'example_questions' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'required|integer|min:0',
        ]);

        FortuneCategory::create($validated);

        return redirect()
            ->route('admin.fortune.categories.index')
            ->with('success', 'เพิ่มหมวดหมู่สำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไขหมวดหมู่
     */
    public function edit(FortuneCategory $category)
    {
        return view('admin.fortune.categories.edit', [
            'category' => $category,
            'pageTitle' => 'แก้ไขหมวดหมู่การทำนาย',
        ]);
    }

    /**
     * อัพเดทหมวดหมู่
     */
    public function update(Request $request, FortuneCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:fortune_categories,slug,' . $category->id,
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:20',
            'description' => 'nullable|string',
            'prompt_context' => 'nullable|string',
            'example_questions' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'required|integer|min:0',
        ]);

        $category->update($validated);

        return redirect()
            ->route('admin.fortune.categories.index')
            ->with('success', 'อัพเดทหมวดหมู่สำเร็จ');
    }

    /**
     * ลบหมวดหมู่
     */
    public function destroy(FortuneCategory $category)
    {
        $category->delete();

        return redirect()
            ->route('admin.fortune.categories.index')
            ->with('success', 'ลบหมวดหมู่สำเร็จ');
    }
}
