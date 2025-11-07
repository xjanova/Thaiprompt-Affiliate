<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    protected ImageUploadService $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * Display all seller's products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category'])
            ->where('seller_id', auth()->id());

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Stock filter
        if ($request->filled('stock')) {
            if ($request->stock === 'in_stock') {
                $query->inStock();
            } elseif ($request->stock === 'low_stock') {
                $query->lowStock();
            } elseif ($request->stock === 'out_of_stock') {
                $query->outOfStock();
            }
        }

        $products = $query->latest()->paginate(20);

        // Statistics
        $stats = [
            'total' => Product::where('seller_id', auth()->id())->count(),
            'active' => Product::where('seller_id', auth()->id())->where('is_active', true)->count(),
            'out_of_stock' => Product::where('seller_id', auth()->id())->outOfStock()->count(),
            'low_stock' => Product::where('seller_id', auth()->id())->lowStock()->count(),
        ];

        return view('seller.products.index', compact('products', 'stats'));
    }

    /**
     * Show create product form
     */
    public function create()
    {
        $categories = ProductCategory::active()->orderBy('name')->get();
        return view('seller.products.create', compact('categories'));
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'brand' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'sku' => 'nullable|string|unique:products,sku',
            'track_inventory' => 'boolean',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'pv_value' => 'nullable|numeric|min:0',
            'customer_cashback' => 'nullable|numeric|min:0',
            'cashback_percentage' => 'nullable|numeric|min:0|max:100',
            'main_image' => 'nullable|image|max:5120',
            'images.*' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Handle main image upload with optimization
            $mainImageUrl = null;
            if ($request->hasFile('main_image')) {
                $mainImageUrl = $this->imageUploadService->uploadImage(
                    $request->file('main_image'),
                    'products',
                    1200,
                    1200,
                    90
                );
            }

            // Create product
            $product = Product::create([
                'seller_id' => auth()->id(),
                'category_id' => $request->category_id,
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'sku' => $request->sku ?: 'PRD-' . strtoupper(Str::random(8)),
                'description' => $request->description,
                'short_description' => $request->short_description,
                'price' => $request->price,
                'compare_at_price' => $request->compare_at_price,
                'cost_price' => $request->cost_price,
                'stock_quantity' => $request->stock_quantity,
                'track_inventory' => $request->track_inventory ?? true,
                'stock_status' => $request->stock_quantity > 0 ? 'in_stock' : 'out_of_stock',
                'brand' => $request->brand,
                'weight' => $request->weight,
                'dimensions' => $request->dimensions,
                'commission_rate' => $request->commission_rate ?? 10.00,
                'customer_cashback' => $request->customer_cashback ?? 0,
                'cashback_percentage' => $request->cashback_percentage ?? 0,
                'main_image_url' => $mainImageUrl,
                'is_active' => true,
                'published_at' => now(),
            ]);

            // Create PV for default MLM plan if specified
            if ($request->filled('pv_value') && $request->pv_value > 0) {
                // Get default or first active MLM plan
                $defaultPlan = \App\Models\MlmPlan::where('is_active', true)->first();
                if ($defaultPlan) {
                    \App\Models\MlmProductPv::create([
                        'product_id' => $product->id,
                        'mlm_plan_id' => $defaultPlan->id,
                        'pv_value' => $request->pv_value,
                        'use_global_rate' => true,
                        'show_pv_on_product_page' => true,
                        'show_commission_preview' => true,
                    ]);
                }
            }

            // Handle additional images with optimization
            if ($request->hasFile('images')) {
                $sortOrder = 1;
                foreach ($request->file('images') as $image) {
                    $imageUrl = $this->imageUploadService->uploadImage(
                        $image,
                        'products',
                        1200,
                        1200,
                        85
                    );
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imageUrl,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('seller.products.index')
                ->with('success', 'เพิ่มสินค้าเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Show edit product form
     */
    public function edit($id)
    {
        $product = Product::with('images')
            ->where('id', $id)
            ->where('seller_id', auth()->id())
            ->firstOrFail();

        $categories = ProductCategory::active()->orderBy('name')->get();

        return view('seller.products.edit', compact('product', 'categories'));
    }

    /**
     * Update product
     */
    public function update(Request $request, $id)
    {
        $product = Product::where('id', $id)
            ->where('seller_id', auth()->id())
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'brand' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'track_inventory' => 'boolean',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'pv_value' => 'nullable|numeric|min:0',
            'customer_cashback' => 'nullable|numeric|min:0',
            'cashback_percentage' => 'nullable|numeric|min:0|max:100',
            'main_image' => 'nullable|image|max:5120',
            'images.*' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Handle main image upload
            if ($request->hasFile('main_image')) {
                // Delete old image if exists
                if ($product->main_image_url) {
                    $this->imageUploadService->deleteImage($product->main_image_url);
                }
                $product->main_image_url = $this->imageUploadService->uploadImage(
                    $request->file('main_image'),
                    'products',
                    1200,
                    1200,
                    90
                );
            }

            // Update product
            $product->update([
                'category_id' => $request->category_id,
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'sku' => $request->sku ?: $product->sku,
                'description' => $request->description,
                'short_description' => $request->short_description,
                'price' => $request->price,
                'compare_at_price' => $request->compare_at_price,
                'cost_price' => $request->cost_price,
                'stock_quantity' => $request->stock_quantity,
                'track_inventory' => $request->track_inventory ?? true,
                'stock_status' => $request->stock_quantity > 0 ? 'in_stock' : 'out_of_stock',
                'brand' => $request->brand,
                'weight' => $request->weight,
                'dimensions' => $request->dimensions,
                'commission_rate' => $request->commission_rate ?? $product->commission_rate,
                'customer_cashback' => $request->customer_cashback ?? 0,
                'cashback_percentage' => $request->cashback_percentage ?? 0,
            ]);

            // Update or create PV for default MLM plan if specified
            if ($request->filled('pv_value')) {
                $defaultPlan = \App\Models\MlmPlan::where('is_active', true)->first();
                if ($defaultPlan) {
                    $existingPv = $product->getMlmPv($defaultPlan->id);

                    if ($existingPv) {
                        // Update existing PV
                        $existingPv->update([
                            'pv_value' => $request->pv_value,
                        ]);
                    } else {
                        // Create new PV
                        \App\Models\MlmProductPv::create([
                            'product_id' => $product->id,
                            'mlm_plan_id' => $defaultPlan->id,
                            'pv_value' => $request->pv_value,
                            'use_global_rate' => true,
                            'show_pv_on_product_page' => true,
                            'show_commission_preview' => true,
                        ]);
                    }
                }
            } elseif ($request->has('pv_value') && $request->pv_value == 0) {
                // Delete PV if value is 0
                $defaultPlan = \App\Models\MlmPlan::where('is_active', true)->first();
                if ($defaultPlan) {
                    $existingPv = $product->getMlmPv($defaultPlan->id);
                    if ($existingPv) {
                        $existingPv->delete();
                    }
                }
            }

            // Handle deleted images
            if ($request->filled('deleted_images')) {
                $deletedIds = explode(',', $request->input('deleted_images'));
                $imagesToDelete = ProductImage::whereIn('id', $deletedIds)
                    ->where('product_id', $product->id)
                    ->get();

                foreach ($imagesToDelete as $img) {
                    $this->imageUploadService->deleteImage($img->image_url);
                    $img->delete();
                }
            }

            // Handle additional images
            if ($request->hasFile('images')) {
                $sortOrder = $product->images()->max('sort_order') ?? 0;
                $sortOrder++;
                foreach ($request->file('images') as $image) {
                    $imageUrl = $this->imageUploadService->uploadImage(
                        $image,
                        'products',
                        1200,
                        1200,
                        85
                    );
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imageUrl,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('seller.products.index')
                ->with('success', 'อัพเดตสินค้าเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete product
     */
    public function destroy($id)
    {
        $product = Product::where('id', $id)
            ->where('seller_id', auth()->id())
            ->firstOrFail();

        // Delete all product images
        if ($product->main_image_url) {
            $this->imageUploadService->deleteImage($product->main_image_url);
        }

        foreach ($product->images as $image) {
            $this->imageUploadService->deleteImage($image->image_url);
        }

        $product->delete();

        return redirect()->route('seller.products.index')
            ->with('success', 'ลบสินค้าเรียบร้อยแล้ว');
    }

    /**
     * Toggle product status
     */
    public function toggleStatus($id)
    {
        $product = Product::where('id', $id)
            ->where('seller_id', auth()->id())
            ->firstOrFail();

        $product->is_active = !$product->is_active;
        $product->save();

        $status = $product->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        return back()->with('success', $status . 'สินค้าเรียบร้อยแล้ว');
    }

    /**
     * Delete product image
     */
    public function deleteImage($productId, $imageId)
    {
        $product = Product::where('id', $productId)
            ->where('seller_id', auth()->id())
            ->firstOrFail();

        $image = ProductImage::where('id', $imageId)
            ->where('product_id', $product->id)
            ->firstOrFail();

        // Delete file using service
        $this->imageUploadService->deleteImage($image->image_url);

        // Delete record
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบรูปภาพเรียบร้อยแล้ว'
        ]);
    }

    /**
     * Update stock quantity
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product = Product::where('id', $id)
            ->where('seller_id', auth()->id())
            ->firstOrFail();

        $product->stock_quantity = $request->stock_quantity;
        $product->stock_status = $request->stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'อัพเดตสต็อกเรียบร้อยแล้ว',
        ]);
    }
}
