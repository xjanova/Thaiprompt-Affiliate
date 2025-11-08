<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SoftwareProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SoftwareCategoryController extends Controller
{
    public function index()
    {
        $categories = SoftwareProductCategory::withCount('softwareProducts')
            ->ordered()
            ->paginate(20);

        return view('admin.software-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.software-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:software_product_categories,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'image_url' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = SoftwareProductCategory::create($validated);

        return redirect()
            ->route('admin.software-categories.index')
            ->with('success', 'สร้างหมวดหมู่เรียบร้อยแล้ว');
    }

    public function edit(SoftwareProductCategory $softwareCategory)
    {
        return view('admin.software-categories.edit', compact('softwareCategory'));
    }

    public function update(Request $request, SoftwareProductCategory $softwareCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:software_product_categories,slug,' . $softwareCategory->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'image_url' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $softwareCategory->update($validated);

        return redirect()
            ->route('admin.software-categories.index')
            ->with('success', 'อัพเดทหมวดหมู่เรียบร้อยแล้ว');
    }

    public function destroy(SoftwareProductCategory $softwareCategory)
    {
        if ($softwareCategory->softwareProducts()->count() > 0) {
            return back()->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีสินค้าอยู่ได้');
        }

        $softwareCategory->delete();

        return redirect()
            ->route('admin.software-categories.index')
            ->with('success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
    }
}
