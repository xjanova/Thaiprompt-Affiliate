<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryManagementController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index()
    {
        $categories = LearningCategory::withCount('articles')
            ->ordered()
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:learning_categories,slug',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = LearningCategory::create($validated);

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'หมวดหมู่ถูกสร้างเรียบร้อยแล้ว');
    }

    /**
     * Show the form for editing a category
     */
    public function edit(LearningCategory $category)
    {
        $category->loadCount('articles');

        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, LearningCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:learning_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'หมวดหมู่ถูกอัพเดตเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified category
     */
    public function destroy(LearningCategory $category)
    {
        // Check if category has articles
        if ($category->articles()->count() > 0) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีบทความได้');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'หมวดหมู่ถูกลบเรียบร้อยแล้ว');
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:learning_categories,id',
            'categories.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['categories'] as $categoryData) {
            LearningCategory::where('id', $categoryData['id'])
                ->update(['order' => $categoryData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'เรียงลำดับหมวดหมู่เรียบร้อยแล้ว',
        ]);
    }
}
