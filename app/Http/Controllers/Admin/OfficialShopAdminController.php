<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Official Shop Admin Controller
 *
 * จัดการสินค้าของระบบ (Official Shop) ใน Admin Panel
 * สินค้าที่สร้างจากที่นี่จะมี seller_id = null
 * เพื่อแสดงในหน้า Official Shop Premium V3
 */
class OfficialShopAdminController extends Controller
{
    protected ImageUploadService $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * แสดง Dashboard ของ Official Shop
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // สถิติสินค้า Official Shop (seller_id = null)
        $stats = [
            'total_products' => Product::whereNull('seller_id')->count(),
            'active_products' => Product::whereNull('seller_id')->where('is_active', true)->count(),
            'featured_products' => Product::whereNull('seller_id')->where('is_featured', true)->count(),
            'out_of_stock' => Product::whereNull('seller_id')->where('stock_status', 'out_of_stock')->count(),
            'total_views' => Product::whereNull('seller_id')->sum('view_count'),
            'total_sales' => Product::whereNull('seller_id')->sum('sales_count'),
        ];

        // สินค้าขายดี
        $topProducts = Product::whereNull('seller_id')
            ->with('category')
            ->orderBy('sales_count', 'desc')
            ->take(5)
            ->get();

        // สินค้าที่เพิ่มล่าสุด
        $recentProducts = Product::whereNull('seller_id')
            ->with('category')
            ->latest()
            ->take(5)
            ->get();

        // หมวดหมู่ที่มีสินค้า Official
        $categoriesWithProducts = ProductCategory::withCount(['products' => function ($q) {
            $q->whereNull('seller_id')->where('is_active', true);
        }])
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->take(10)
            ->get();

        return view('admin.official-shop.dashboard', compact(
            'stats',
            'topProducts',
            'recentProducts',
            'categoriesWithProducts'
        ));
    }

    /**
     * แสดงรายการสินค้า Official Shop
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images'])
            ->whereNull('seller_id'); // เฉพาะสินค้าของระบบ

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'featured') {
                $query->where('is_featured', true);
            }
        }

        // กรองตามสถานะสต็อก
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20)->withQueryString();

        // หมวดหมู่สำหรับ filter
        $categories = ProductCategory::active()
            ->orderBy('name')
            ->get();

        // สถิติ
        $stats = [
            'total' => Product::whereNull('seller_id')->count(),
            'active' => Product::whereNull('seller_id')->where('is_active', true)->count(),
            'featured' => Product::whereNull('seller_id')->where('is_featured', true)->count(),
            'out_of_stock' => Product::whereNull('seller_id')->where('stock_status', 'out_of_stock')->count(),
        ];

        return view('admin.official-shop.products.index', compact(
            'products',
            'categories',
            'stats'
        ));
    }

    /**
     * แสดงฟอร์มสร้างสินค้าใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = ProductCategory::active()
            ->orderBy('name')
            ->get();

        return view('admin.official-shop.products.create', compact('categories'));
    }

    /**
     * บันทึกสินค้าใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'pv_value' => 'nullable|numeric|min:0',
            'customer_cashback' => 'nullable|numeric|min:0',
            'cashback_percentage' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string|max:100',
            'main_image' => 'nullable|image|max:5120',
            'images.*' => 'nullable|image|max:5120',
            // Coin purchase fields
            'price_coins' => 'nullable|numeric|min:0',
            'allow_coin_purchase' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            // อัพโหลดรูปหลัก
            if ($request->hasFile('main_image')) {
                $validated['main_image_url'] = $this->imageUploadService->uploadImage(
                    $request->file('main_image'),
                    'products/official',
                    1200,
                    1200,
                    90
                );
            }

            // สร้าง slug
            $validated['slug'] = Str::slug($validated['name']);

            // ตรวจสอบ slug ซ้ำ
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Product::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            // ⭐ IMPORTANT: seller_id = null สำหรับ Official Shop
            $validated['seller_id'] = null;

            // ตั้งค่าเริ่มต้น
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['is_hidden'] = $request->boolean('is_hidden', false);
            $validated['track_inventory'] = $request->boolean('track_inventory', true);
            $validated['allow_coin_purchase'] = $request->boolean('allow_coin_purchase', false);

            // คำนวณสถานะสต็อก
            if ($validated['track_inventory']) {
                $stockQty = $validated['stock_quantity'] ?? 0;
                $lowThreshold = $validated['low_stock_threshold'] ?? 5;

                if ($stockQty <= 0) {
                    $validated['stock_status'] = 'out_of_stock';
                } elseif ($stockQty <= $lowThreshold) {
                    $validated['stock_status'] = 'low_stock';
                } else {
                    $validated['stock_status'] = 'in_stock';
                }
            } else {
                $validated['stock_status'] = 'in_stock';
            }

            $validated['published_at'] = now();

            // สร้างสินค้า
            $product = Product::create($validated);

            // อัพโหลดรูปเพิ่มเติม
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $imageUrl = $this->imageUploadService->uploadImage(
                        $image,
                        'products/official',
                        1200,
                        1200,
                        90
                    );

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imageUrl,
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.official-shop.products.index')
                ->with('success', 'เพิ่มสินค้า Official Shop สำเร็จ: ' . $product->name);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดสินค้า
     *
     * @param Product $product
     * @return \Illuminate\View\View
     */
    public function show(Product $product)
    {
        // ตรวจสอบว่าเป็นสินค้า Official Shop
        if ($product->seller_id !== null) {
            abort(403, 'สินค้านี้ไม่ใช่สินค้าของ Official Shop');
        }

        $product->load(['category', 'images', 'approvedReviews.user']);

        return view('admin.official-shop.products.show', compact('product'));
    }

    /**
     * แสดงฟอร์มแก้ไขสินค้า
     *
     * @param Product $product
     * @return \Illuminate\View\View
     */
    public function edit(Product $product)
    {
        // ตรวจสอบว่าเป็นสินค้า Official Shop
        if ($product->seller_id !== null) {
            abort(403, 'สินค้านี้ไม่ใช่สินค้าของ Official Shop');
        }

        $product->load('images');

        $categories = ProductCategory::active()
            ->orderBy('name')
            ->get();

        return view('admin.official-shop.products.edit', compact('product', 'categories'));
    }

    /**
     * อัพเดทสินค้า
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Product $product)
    {
        // ตรวจสอบว่าเป็นสินค้า Official Shop
        if ($product->seller_id !== null) {
            abort(403, 'สินค้านี้ไม่ใช่สินค้าของ Official Shop');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'pv_value' => 'nullable|numeric|min:0',
            'customer_cashback' => 'nullable|numeric|min:0',
            'cashback_percentage' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string|max:100',
            'main_image' => 'nullable|image|max:5120',
            'images.*' => 'nullable|image|max:5120',
            'delete_images' => 'nullable|array',
            // Coin purchase fields
            'price_coins' => 'nullable|numeric|min:0',
            'allow_coin_purchase' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            // อัพโหลดรูปหลักใหม่
            if ($request->hasFile('main_image')) {
                // ลบรูปเก่า
                if ($product->main_image_url) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $product->main_image_url));
                }

                $validated['main_image_url'] = $this->imageUploadService->uploadImage(
                    $request->file('main_image'),
                    'products/official',
                    1200,
                    1200,
                    90
                );
            }

            // อัพเดท slug ถ้าชื่อเปลี่ยน
            if ($product->name !== $validated['name']) {
                $validated['slug'] = Str::slug($validated['name']);

                // ตรวจสอบ slug ซ้ำ
                $originalSlug = $validated['slug'];
                $counter = 1;
                while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // ตั้งค่า
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['is_hidden'] = $request->boolean('is_hidden', false);
            $validated['track_inventory'] = $request->boolean('track_inventory', true);
            $validated['allow_coin_purchase'] = $request->boolean('allow_coin_purchase', false);

            // คำนวณสถานะสต็อก
            if ($validated['track_inventory']) {
                $stockQty = $validated['stock_quantity'] ?? 0;
                $lowThreshold = $validated['low_stock_threshold'] ?? 5;

                if ($stockQty <= 0) {
                    $validated['stock_status'] = 'out_of_stock';
                } elseif ($stockQty <= $lowThreshold) {
                    $validated['stock_status'] = 'low_stock';
                } else {
                    $validated['stock_status'] = 'in_stock';
                }
            }

            // อัพเดทสินค้า
            $product->update($validated);

            // ลบรูปที่เลือก
            if ($request->filled('delete_images')) {
                foreach ($request->delete_images as $imageId) {
                    $image = ProductImage::find($imageId);
                    if ($image && $image->product_id === $product->id) {
                        Storage::disk('public')->delete(str_replace('/storage/', '', $image->image_url));
                        $image->delete();
                    }
                }
            }

            // อัพโหลดรูปเพิ่มเติม
            if ($request->hasFile('images')) {
                $maxSort = $product->images()->max('sort_order') ?? 0;

                foreach ($request->file('images') as $index => $image) {
                    $imageUrl = $this->imageUploadService->uploadImage(
                        $image,
                        'products/official',
                        1200,
                        1200,
                        90
                    );

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imageUrl,
                        'sort_order' => $maxSort + $index + 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.official-shop.products.index')
                ->with('success', 'อัพเดทสินค้า Official Shop สำเร็จ: ' . $product->name);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบสินค้า
     *
     * @param Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product)
    {
        // ตรวจสอบว่าเป็นสินค้า Official Shop
        if ($product->seller_id !== null) {
            abort(403, 'สินค้านี้ไม่ใช่สินค้าของ Official Shop');
        }

        $productName = $product->name;

        DB::beginTransaction();

        try {
            // ลบรูปภาพ
            if ($product->main_image_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $product->main_image_url));
            }

            foreach ($product->images as $image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $image->image_url));
                $image->delete();
            }

            // ลบสินค้า (Soft delete ถ้ามี)
            $product->delete();

            DB::commit();

            return redirect()
                ->route('admin.official-shop.products.index')
                ->with('success', 'ลบสินค้าสำเร็จ: ' . $productName);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Toggle สถานะ Active/Inactive
     *
     * @param Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(Product $product)
    {
        if ($product->seller_id !== null) {
            abort(403, 'สินค้านี้ไม่ใช่สินค้าของ Official Shop');
        }

        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return back()->with('success', "{$status}สินค้า: {$product->name}");
    }

    /**
     * Toggle สถานะ Featured
     *
     * @param Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleFeatured(Product $product)
    {
        if ($product->seller_id !== null) {
            abort(403, 'สินค้านี้ไม่ใช่สินค้าของ Official Shop');
        }

        $product->update(['is_featured' => !$product->is_featured]);

        $status = $product->is_featured ? 'เพิ่มเป็น' : 'นำออกจาก';

        return back()->with('success', "{$status}สินค้าแนะนำ: {$product->name}");
    }

    /**
     * Import สินค้าจากที่มีอยู่ให้เป็น Official Shop
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importToOfficial(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $count = 0;

        foreach ($request->product_ids as $productId) {
            $product = Product::find($productId);

            if ($product && $product->seller_id !== null) {
                $product->update(['seller_id' => null]);
                $count++;
            }
        }

        return back()->with('success', "นำเข้าสินค้าเป็น Official Shop สำเร็จ {$count} รายการ");
    }
}
