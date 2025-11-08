<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SoftwareProduct;
use App\Models\SoftwareProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SoftwareProductManagementController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = SoftwareProduct::with('category');

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('sort_order')->orderBy('name')->paginate(20);
        $categories = SoftwareProductCategory::ordered()->get();

        return view('admin.software-products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $categories = SoftwareProductCategory::active()->ordered()->get();
        return view('admin.software-products.create', compact('categories'));
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:software_product_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:software_products,slug',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'base_price' => 'required|numeric|min:0',
            'price_label' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'main_image_url' => 'nullable|string',
            'gallery_images' => 'nullable|array',
            'technical_specs' => 'nullable|array',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'is_customizable' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'requires_consultation' => 'boolean',
            'estimated_delivery_days' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'tags' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['currency'] = $validated['currency'] ?? 'THB';

        $product = SoftwareProduct::create($validated);

        return redirect()
            ->route('admin.software-products.show', $product)
            ->with('success', 'สร้างสินค้าเรียบร้อยแล้ว');
    }

    /**
     * Display the specified product
     */
    public function show(SoftwareProduct $softwareProduct)
    {
        $softwareProduct->load(['category', 'options.values', 'quotations']);
        return view('admin.software-products.show', compact('softwareProduct'));
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(SoftwareProduct $softwareProduct)
    {
        $categories = SoftwareProductCategory::ordered()->get();
        return view('admin.software-products.edit', compact('softwareProduct', 'categories'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, SoftwareProduct $softwareProduct)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:software_product_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:software_products,slug,' . $softwareProduct->id,
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'base_price' => 'required|numeric|min:0',
            'price_label' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'main_image_url' => 'nullable|string',
            'gallery_images' => 'nullable|array',
            'technical_specs' => 'nullable|array',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'is_customizable' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'requires_consultation' => 'boolean',
            'estimated_delivery_days' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'tags' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $softwareProduct->update($validated);

        return redirect()
            ->route('admin.software-products.show', $softwareProduct)
            ->with('success', 'อัพเดทสินค้าเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified product
     */
    public function destroy(SoftwareProduct $softwareProduct)
    {
        $softwareProduct->delete();

        return redirect()
            ->route('admin.software-products.index')
            ->with('success', 'ลบสินค้าเรียบร้อยแล้ว');
    }
}
