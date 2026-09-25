<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderMessage;
use App\Models\OrderTrackingHistory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Services\ImageUploadService;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ECommerceController extends Controller
{
    protected ImageUploadService $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * Show E-commerce Dashboard
     */
    public function dashboard()
    {
        // Main statistics
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'out_of_stock' => Product::where('stock_status', 'out_of_stock')->count(),
            // "ใกล้หมด" = สต็อก <= เกณฑ์ (ไม่มีค่า low_stock ใน enum stock_status)
            'low_stock' => Product::lowStock()->count(),

            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),

            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'monthly_revenue' => Order::where('payment_status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount'),
            'total_customers' => Order::distinct('user_id')->count('user_id'),

            'total_categories' => ProductCategory::count(),
            'total_reviews' => ProductReview::count(),
            'average_rating' => ProductReview::avg('rating'),
        ];

        // Growth statistics
        $thisMonthOrders = Order::whereMonth('created_at', now()->month)->count();
        $lastMonthOrders = Order::whereMonth('created_at', now()->subMonth()->month)->count();
        $orderGrowth = $lastMonthOrders > 0 ? (($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 : 0;

        $thisMonthRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)->sum('total_amount');
        $lastMonthRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', now()->subMonth()->month)->sum('total_amount');
        $revenueGrowth = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        // Monthly revenue chart (last 12 months)
        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_amount) as total, COUNT(*) as orders')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->reverse();

        // Order status breakdown
        $orderStatusData = [
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        // Top selling products
        $topProducts = Product::with('images')
            ->withCount(['orderItems as total_sales' => function ($query) {
                $query->select(DB::raw('SUM(quantity)'));
            }])
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();

        // Recent orders
        $recentOrders = Order::with(['user', 'items.product.images'])
            ->latest()
            ->limit(10)
            ->get();

        // Low stock products
        $lowStockProducts = Product::with('images')
            ->where('track_inventory', true)
            ->where('stock_quantity', '<=', DB::raw('low_stock_threshold'))
            ->orderBy('stock_quantity', 'asc')
            ->limit(10)
            ->get();

        // 💰 เงินที่แบ่งจากออเดอร์ร้านค้า (กระเป๋าแพลตฟอร์ม) — แสดง GP / VAT / กองทุนผู้แนะนำ ให้เห็นชัด
        $moneySplit = $this->orderMoneySplit();

        // 🏷️ สถานะโปรฯ GP ฟรี + อัตรากลาง (แก้ได้ที่หน้าตั้งค่าส่วนแบ่งรายได้)
        $gpInfo = null;
        try {
            $engine = app(\App\Services\Pricing\PricingEngine::class);
            $gpInfo = [
                'promo_active' => $engine->gpPromoActive(),
                'promo_ends_at' => $engine->gpPromoEndsAt(),
                'default_rate' => $engine->defaultGpRate(),
                'min_rate' => $engine->minGpRate(),
                'referral_pool_percent' => $engine->referralPoolPercent(),
                'mlm_enabled' => $engine->mlmEnabled(),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('E-commerce dashboard GP info failed', ['error' => $e->getMessage()]);
        }

        return view('admin.ecommerce.dashboard', compact(
            'stats',
            'orderGrowth',
            'revenueGrowth',
            'monthlyRevenue',
            'orderStatusData',
            'topProducts',
            'recentOrders',
            'lowStockProducts',
            'moneySplit',
            'gpInfo'
        ));
    }

    /**
     * สรุปเงินที่แบ่งจากออเดอร์ร้านค้า (PlatformTransaction source Order) แยกตามประเภท
     *
     * @return array{gp: float, vat: float, referral_pool: float, seller_escrow: float, official_shop: float, refunds: float}
     */
    private function orderMoneySplit(?string $from = null, ?string $to = null): array
    {
        $empty = ['gp' => 0.0, 'vat' => 0.0, 'referral_pool' => 0.0, 'seller_escrow' => 0.0, 'official_shop' => 0.0, 'refunds' => 0.0];

        try {
            $query = \App\Models\PlatformTransaction::query()
                ->where('source_type', 'Order')
                ->selectRaw('sub_type, type, SUM(amount) as total')
                ->groupBy('sub_type', 'type');

            if ($from && $to) {
                $query->whereBetween('created_at', [$from, $to.' 23:59:59']);
            }

            $rows = $query->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Order money split query failed', ['error' => $e->getMessage()]);

            return $empty;
        }

        $sum = fn (string $subType, string $type = 'income') => round((float) $rows
            ->where('sub_type', $subType)->where('type', $type)->sum('total'), 2);

        return [
            'gp' => $sum('order_fee'),
            'vat' => $sum('vat_collection'),
            'referral_pool' => $sum('mlm_commission_pool'),
            'seller_escrow' => $sum(\App\Services\SellerPayoutService::ESCROW_HOLD_SUB_TYPE),
            'official_shop' => $sum('admin_shop_sale'),
            'refunds' => round((float) $rows->whereIn('type', ['refund', 'expense'])->sum('total'), 2),
        ];
    }

    /**
     * List all products
     */
    public function products(Request $request)
    {
        $query = Product::with(['category', 'seller', 'images', 'store']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by stock status ("ใกล้หมด" คำนวณจากจำนวนเทียบเกณฑ์ ไม่ได้เก็บในคอลัมน์)
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low_stock') {
                $query->lowStock();
            } else {
                $query->where('stock_status', $request->stock_status);
            }
        }

        // กรองตามร้าน (ลิงก์จากหน้าร้านค้า)
        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->store_id);
        }

        // เรียงลำดับ (whitelist)
        [$sortBy, $sortOrder] = $this->safeSort($request, ['created_at', 'name', 'price', 'stock_quantity', 'sales_count'], 'created_at');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20)->withQueryString();
        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.ecommerce.products.index', compact('products', 'categories'));
    }

    /**
     * แสดงรายการสินค้าที่ถูกบล็อก
     *
     * @return \Illuminate\View\View
     */
    public function blockedProducts(Request $request)
    {
        $query = Product::with(['category', 'seller', 'images', 'blockedByUser'])
            ->blocked(); // ใช้ scope blocked()

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('block_reason', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // เรียงลำดับ (whitelist)
        [$sortBy, $sortOrder] = $this->safeSort($request, ['blocked_at', 'name', 'created_at'], 'blocked_at');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20)->withQueryString();
        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.ecommerce.products.blocked', compact('products', 'categories'));
    }

    /**
     * Store new product
     */
    public function storeProduct(Request $request)
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
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'pv_value' => 'nullable|numeric|min:0',
            'customer_cashback' => 'nullable|numeric|min:0',
            'cashback_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'track_inventory' => 'boolean',
            'main_image' => 'nullable|image|max:5120',
            'images.*' => 'nullable|image|max:5120',
            'shipping_method' => 'nullable|in:free,flat_rate,weight_based,store_default',
            'shipping_fee' => 'nullable|numeric|min:0',
            'shipping_weight_kg' => 'nullable|numeric|min:0',
            'free_shipping_min_amount' => 'nullable|numeric|min:0',
            'free_shipping_min_amount_weight' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Handle main image upload
            if ($request->hasFile('main_image')) {
                $validated['main_image_url'] = $this->imageUploadService->uploadImage(
                    $request->file('main_image'),
                    'products',
                    1200,
                    1200,
                    90
                );
            }

            // Generate slug
            $validated['slug'] = \Str::slug($validated['name']);

            // Set seller_id to first user or current user
            $validated['seller_id'] = auth()->id() ?? 1;

            // ตั้งค่าการจัดส่ง
            $validated['shipping_method'] = $request->shipping_method ?? 'store_default';
            // products.shipping_fee เป็น NOT NULL DEFAULT 0 — ส่ง null (วิธีส่งแบบอื่นที่ไม่กรอกค่าส่ง) ทำให้บันทึกพัง
            $validated['shipping_fee'] = $request->filled('shipping_fee') ? round((float) $request->shipping_fee, 2) : 0;
            $validated['shipping_weight_kg'] = $request->shipping_weight_kg;
            $validated['free_shipping_min_amount'] = $request->free_shipping_min_amount
                ?? $request->free_shipping_min_amount_weight;

            // Set defaults
            $validated['is_active'] = $request->has('is_active');
            $validated['is_featured'] = $request->has('is_featured');
            $validated['is_hidden'] = $request->has('is_hidden');
            $validated['track_inventory'] = $request->has('track_inventory');
            $validated['stock_status'] = $validated['track_inventory'] && ($validated['stock_quantity'] ?? 0) > 0 ? 'in_stock' : 'out_of_stock';
            $validated['commission_rate'] = $validated['commission_rate'] ?? 10.00;
            $validated['customer_cashback'] = $validated['customer_cashback'] ?? 0;
            $validated['cashback_percentage'] = $validated['cashback_percentage'] ?? 0;
            $validated['published_at'] = now();

            $product = Product::create($validated);

            // Create PV for default MLM plan if specified
            if ($request->filled('pv_value') && $request->pv_value > 0) {
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

            // Handle additional images
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

            return redirect()->route('admin.ecommerce.products.index')
                ->with('success', 'เพิ่มสินค้าเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $this->failMessage($e));
        }
    }

    /**
     * Show product details
     */
    public function showProduct(Product $product)
    {
        $product->load(['category', 'seller', 'images', 'reviews.user']);

        // Get product statistics
        $stats = [
            'total_sales' => $product->orderItems()->sum('quantity'),
            'total_revenue' => $product->orderItems()->sum('total'), // Use 'total' column instead of 'price'
            'total_reviews' => $product->reviews()->count(),
            'average_rating' => $product->reviews()->avg('rating'),
        ];

        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.ecommerce.products.show', compact('product', 'stats', 'categories'));
    }

    /**
     * Show edit product form
     */
    public function editProduct(Product $product)
    {
        $product->load('images');
        $categories = ProductCategory::active()->orderBy('name')->get();

        return view('admin.ecommerce.products.edit', compact('product', 'categories'));
    }

    /**
     * Update product
     */
    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku,'.$product->id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            // products.stock_quantity / low_stock_threshold เป็น NOT NULL — ว่างแล้วเคยพังเป็น QueryException
            // พร้อมข้อความกว้าง ๆ · สต็อกต้องกรอก · เกณฑ์ใกล้หมดเว้นว่าง = ใช้ค่าเดิมของสินค้า
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_hidden' => 'boolean',
            'category_id' => 'required|exists:product_categories,id',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'pv_value' => 'nullable|numeric|min:0',
            'customer_cashback' => 'nullable|numeric|min:0',
            'cashback_percentage' => 'nullable|numeric|min:0|max:100',
            'brand' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'main_image' => 'nullable|image|max:5120',
            'images.*' => 'nullable|image|max:5120',
            'shipping_method' => 'nullable|in:free,flat_rate,weight_based,store_default',
            'shipping_fee' => 'nullable|numeric|min:0',
            'shipping_weight_kg' => 'nullable|numeric|min:0',
            'free_shipping_min_amount' => 'nullable|numeric|min:0',
            'free_shipping_min_amount_weight' => 'nullable|numeric|min:0',
            // 🏷️ อัตรา GP ที่แอดมินกำหนดเฉพาะสินค้านี้ (ว่าง = ใช้อัตราตามแพ็กเกจร้าน) — ไม่ mass-assign
            'admin_gp_rate' => 'nullable|numeric|min:0|max:100',
        ], [
            'admin_gp_rate.numeric' => 'อัตรา GP ต้องเป็นตัวเลข',
            'admin_gp_rate.max' => 'อัตรา GP ต้องไม่เกิน 100%',
            'stock_quantity.required' => 'กรุณากรอกจำนวนสต็อก (ใส่ 0 ได้ถ้าหมด)',
            'stock_quantity.integer' => 'จำนวนสต็อกต้องเป็นจำนวนเต็ม',
            'stock_quantity.min' => 'จำนวนสต็อกต้องไม่ติดลบ',
            'low_stock_threshold.integer' => 'เกณฑ์แจ้งเตือนสต็อกใกล้หมดต้องเป็นจำนวนเต็ม',
            'low_stock_threshold.min' => 'เกณฑ์แจ้งเตือนสต็อกใกล้หมดต้องไม่ติดลบ',
        ]);
        unset($validated['admin_gp_rate']);
        if (array_key_exists('low_stock_threshold', $validated) && $validated['low_stock_threshold'] === null) {
            unset($validated['low_stock_threshold']);
        }

        DB::beginTransaction();

        try {
            // อัตรา GP รายสินค้า (เฉพาะเมื่อฟอร์มส่งช่องนี้มา — ฟอร์มอื่นที่ไม่มีช่องนี้จะไม่ล้างค่าเดิม)
            if ($request->has('admin_gp_rate')) {
                $product->admin_gp_rate = $request->filled('admin_gp_rate')
                    ? round((float) $request->input('admin_gp_rate'), 2)
                    : null;
            }

            // Handle main image upload
            if ($request->hasFile('main_image')) {
                // Delete old image if exists
                if ($product->main_image_url) {
                    $this->imageUploadService->deleteImage($product->main_image_url);
                }
                $validated['main_image_url'] = $this->imageUploadService->uploadImage(
                    $request->file('main_image'),
                    'products',
                    1200,
                    1200,
                    90
                );
            }

            // Handle checkboxes
            $validated['is_active'] = $request->has('is_active');
            $validated['is_featured'] = $request->has('is_featured');
            $validated['is_hidden'] = $request->has('is_hidden');
            $validated['track_inventory'] = $request->has('track_inventory');

            // Update slug if name changed (with uniqueness check)
            if ($validated['name'] !== $product->name) {
                $baseSlug = \Str::slug($validated['name']);
                $slug = $baseSlug;
                $counter = 1;

                // ตรวจสอบว่า slug ซ้ำหรือไม่ (ยกเว้นสินค้าปัจจุบัน)
                while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $validated['slug'] = $slug;
            }

            // Update stock status
            // 🐛 products.stock_status เป็น enum(in_stock, out_of_stock, on_backorder) — ไม่มี 'low_stock'
            //    เดิมเขียน 'low_stock' เมื่อสต็อก <= เกณฑ์ → SQL error บันทึกสินค้าไม่ได้เลย
            //    "ใกล้หมด" ดูจาก stock_quantity เทียบ low_stock_threshold (Product::scopeLowStock) แทน
            if (isset($validated['track_inventory']) && $validated['track_inventory']) {
                $qty = $validated['stock_quantity'] ?? $product->stock_quantity;
                $validated['stock_status'] = $qty <= 0 ? 'out_of_stock' : 'in_stock';
            }

            // Set cashback defaults if not provided
            $validated['customer_cashback'] = $validated['customer_cashback'] ?? 0;
            $validated['cashback_percentage'] = $validated['cashback_percentage'] ?? 0;

            // ตั้งค่าการจัดส่ง
            $validated['shipping_method'] = $request->shipping_method ?? 'store_default';
            // products.shipping_fee เป็น NOT NULL DEFAULT 0 — ส่ง null (วิธีส่งแบบอื่นที่ไม่กรอกค่าส่ง) ทำให้บันทึกพัง
            $validated['shipping_fee'] = $request->filled('shipping_fee') ? round((float) $request->shipping_fee, 2) : 0;
            $validated['shipping_weight_kg'] = $request->shipping_weight_kg;
            $validated['free_shipping_min_amount'] = $request->free_shipping_min_amount
                ?? $request->free_shipping_min_amount_weight;

            $product->update($validated);

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
            if ($request->has('deleted_images')) {
                $deletedIds = $request->input('deleted_images');

                // Support both array and comma-separated string
                if (is_string($deletedIds)) {
                    $deletedIds = explode(',', $deletedIds);
                }

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

            return redirect()->back()->with('success', 'อัพเดทสินค้าเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $this->failMessage($e));
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.ecommerce.products.index')
            ->with('success', 'ลบสินค้าเรียบร้อยแล้ว');
    }

    /**
     * List all orders
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'items', 'store']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // กรองวิธีจัดส่ง (พัสดุ / ไรเดอร์)
        if (in_array($request->get('delivery_method'), [Order::DELIVERY_PARCEL, Order::DELIVERY_RIDER], true)) {
            $query->where('delivery_method', $request->get('delivery_method'));
        }

        // เรียงลำดับ — รับเฉพาะคอลัมน์ที่อนุญาต (ค่าแปลกเคยทำให้หน้า 500)
        [$sortBy, $sortOrder] = $this->safeSort($request, ['created_at', 'total_amount', 'order_number', 'status', 'payment_status'], 'created_at');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate(20)->withQueryString();

        // ตัวเลขสรุปบนหัวหน้า (นับทั้งระบบ ไม่ขึ้นกับตัวกรอง)
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'to_fulfil' => Order::whereIn('status', ['paid', 'processing'])->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'unpaid' => Order::where('payment_status', 'pending')->whereNotIn('status', Order::TERMINAL_STATUSES)->count(),
            'unread_messages' => Order::where('has_unread_messages', true)->count(),
        ];

        return view('admin.ecommerce.orders.index', compact('orders', 'stats'));
    }

    /**
     * บันทึก error ลง log แล้วคืนข้อความไทยให้ผู้ใช้ (ไม่เปิดเผยข้อความ exception ดิบ)
     */
    private function failMessage(\Throwable $e): string
    {
        \Illuminate\Support\Facades\Log::error('Admin e-commerce action failed', [
            'route' => request()->route()?->getName(),
            'error' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);

        return 'เกิดข้อผิดพลาด ระบบยังไม่ได้บันทึกการเปลี่ยนแปลง กรุณาลองใหม่อีกครั้ง';
    }

    /**
     * อ่านคอลัมน์/ทิศทางการเรียงจาก query string แบบปลอดภัย
     *
     * @param  array<int, string>  $allowed  คอลัมน์ที่อนุญาต
     * @return array{0: string, 1: string}
     */
    private function safeSort(Request $request, array $allowed, string $default): array
    {
        $sortBy = (string) $request->get('sort_by', $default);
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        return [in_array($sortBy, $allowed, true) ? $sortBy : $default, $sortOrder];
    }

    /**
     * Show order details
     */
    public function showOrder(Order $order)
    {
        $order->load(['user', 'items.product', 'items.product.images', 'items.seller', 'shippingAddress', 'store', 'shippingProvider']);

        // 💰 การแบ่งเงินของออเดอร์นี้ (GP / VAT / ค่าแนะนำ / สุทธิผู้ขาย) — มีเมื่อออเดอร์ชำระแล้วและแบ่งเงินแล้ว
        $ledgers = \App\Models\EarningsLedger::withTrashed()
            ->with('user:id,name,email')
            ->where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->orderBy('id')
            ->get();

        $platformTransactions = \App\Models\PlatformTransaction::with('wallet')
            ->where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->orderBy('id')
            ->get();

        // 🧾 ประวัติการเปลี่ยนสถานะชำระเงินด้วยมือ (audit CC-17)
        $paymentAudits = \App\Models\AccountingActivityLog::with('user:id,name')
            ->where('loggable_type', Order::class)
            ->where('loggable_id', $order->id)
            ->where('action', 'order.payment_status_changed')
            ->latest('id')
            ->limit(20)
            ->get();

        // 🛵 งานไรเดอร์ (เฉพาะออเดอร์ส่งด้วยไรเดอร์)
        $riderSummary = null;
        try {
            $riderSummary = \App\Services\Shop\ShopPresenter::riderSummary($order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin order rider summary failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        $admin = auth()->user();
        $isSuperAdmin = (bool) ($admin->is_super_admin ?? false) || ($admin->role ?? null) === 'super_admin';

        return view('admin.ecommerce.orders.show', compact(
            'order',
            'ledgers',
            'platformTransactions',
            'paymentAudits',
            'riderSummary',
            'isSuperAdmin'
        ));
    }

    /**
     * อัพเดทสถานะคำสั่งซื้อ
     *
     * จัดการ cancelled/refunded อย่างถูกต้อง:
     * - cancelled: เรียก Order::cancel() → restore stock + refund ถ้าจ่ายแล้ว
     * - refunded: เรียก RefundService → คืนเงิน + clawback commission/cashback
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled,refunded',
            'admin_notes' => 'nullable|string',
        ]);

        $newStatus = $validated['status'];
        $adminNotes = isset($validated['admin_notes']) ? trim((string) $validated['admin_notes']) : null;
        $adminNotes = $adminNotes === '' ? null : $adminNotes;

        // บันทึก admin notes — "ต่อท้าย" ประวัติเดิมพร้อมวันเวลา/ผู้บันทึก หลังทำรายการสำเร็จเท่านั้น
        // (เดิมเขียนทับทั้งคอลัมน์ก่อนตรวจสิทธิ์ → บรรทัด audit การเปลี่ยนสถานะชำระเงินและโน้ตเก่าหาย)
        $appendNote = function (string $label) use ($order, $adminNotes): void {
            if ($adminNotes) {
                $this->appendAdminNote($order, $label.': '.$adminNotes);
            }
        };

        // จัดการ "ยกเลิก" อย่างถูกต้อง
        if ($newStatus === 'cancelled') {
            if (! $order->canBeCancelled()) {
                return redirect()->back()->with('error', 'ไม่สามารถยกเลิกคำสั่งซื้อในสถานะนี้ได้ (สถานะปัจจุบัน: '.$order->status_label.')');
            }
            $order->cancel($adminNotes ?? 'ยกเลิกโดย Admin', auth()->id(), 'admin');
            $appendNote('ยกเลิกคำสั่งซื้อ');

            return redirect()->back()->with('success', 'ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว'.(in_array($order->fresh()->status, ['refunded']) ? ' (คืนเงินแล้ว)' : ''));
        }

        // จัดการ "คืนเงิน" อย่างถูกต้อง
        if ($newStatus === 'refunded') {
            if (! $order->canBeRefunded()) {
                return redirect()->back()->with('error', 'ไม่สามารถคืนเงินคำสั่งซื้อในสถานะนี้ได้ (สถานะปัจจุบัน: '.$order->status_label.')');
            }

            try {
                $refundService = app(RefundService::class);
                $refundService->processFullRefund($order, auth()->id(), $adminNotes ?? 'คืนเงินโดย Admin');
                $appendNote('คืนเงินเต็มจำนวน');

                return redirect()->back()->with('success', 'คืนเงินคำสั่งซื้อเรียบร้อยแล้ว');
            } catch (\DomainException $e) {
                // ข้อความไทยจาก RefundService (ยังไม่จ่ายเงิน / กระเป๋าลูกค้าถูกระงับ ฯลฯ)
                return redirect()->back()->with('error', $e->getMessage());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Admin refund failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->back()->with('error', 'คืนเงินไม่สำเร็จ ระบบยกเลิกรายการทั้งหมดแล้ว (ยังไม่มีเงินเคลื่อนไหว) กรุณาลองใหม่หรือแจ้งผู้ดูแลระบบ');
            }
        }

        // อัพเดทสถานะปกติ (pending, processing, shipped, completed)
        $oldStatus = (string) $order->status;
        $order->update(['status' => $newStatus]);
        $appendNote('สถานะ '.$oldStatus.' → '.$newStatus);

        if ($newStatus === 'shipped' && ! $order->shipped_at) {
            $order->update(['shipped_at' => now()]);
        }

        if ($newStatus === 'completed' && ! $order->delivered_at) {
            $order->update(['delivered_at' => now()]);
        }

        return redirect()->back()->with('success', 'อัพเดทสถานะคำสั่งซื้อเรียบร้อยแล้ว');
    }

    /**
     * เปลี่ยนสถานะการชำระเงินด้วยมือ (แอดมิน)
     *
     * 🔒 (2026-09-25) audit CC-17: เดิมเปลี่ยนได้อิสระโดยไม่มีเหตุผล/หลักฐาน/บันทึก —
     *    ตั้ง 'paid' = แบ่งเงินให้ผู้ขาย (เงินจริง) / 'refunded' แค่เปลี่ยนป้ายไม่คืนเงินจริง
     * ตอนนี้:
     *  - ต้องใส่เหตุผลทุกครั้ง, ตั้ง 'paid' ต้องมีเลขอ้างอิง (สลิป/ธุรกรรม) และต้องเป็น super admin
     *  - 'refunded' วิ่งผ่าน RefundService (คืนเงินจริง + ดึงคอม/รายได้ผู้ขายคืน) และต้องเป็น super admin
     *  - ห้ามย้อน paid → pending/failed (เงินถูกแบ่งแล้ว ต้องคืนเงินผ่านระบบคืนเงินเท่านั้น)
     *  - บันทึก audit ลง accounting_activity_logs + log + admin_notes ของออเดอร์
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'reason' => 'required|string|min:5|max:1000',
            'payment_reference' => 'nullable|string|max:255|required_if:payment_status,paid',
        ], [
            'payment_status.required' => 'กรุณาเลือกสถานะการชำระเงิน',
            'payment_status.in' => 'สถานะการชำระเงินไม่ถูกต้อง',
            'reason.required' => 'กรุณาระบุเหตุผลในการเปลี่ยนสถานะการชำระเงิน',
            'reason.min' => 'เหตุผลต้องมีอย่างน้อย 5 ตัวอักษร',
            'payment_reference.required_if' => 'การยืนยันว่าชำระแล้วต้องระบุเลขอ้างอิงสลิปหรือธุรกรรม',
        ]);

        $admin = auth()->user();
        $newStatus = $validated['payment_status'];
        $reason = trim($validated['reason']);
        $reference = isset($validated['payment_reference']) ? trim((string) $validated['payment_reference']) : null;
        $oldStatus = (string) $order->payment_status;
        $isSuperAdmin = (bool) ($admin->is_super_admin ?? false) || ($admin->role ?? null) === 'super_admin';

        if ($oldStatus === $newStatus) {
            return redirect()->back()->with('info', 'สถานะการชำระเงินเป็นค่านี้อยู่แล้ว');
        }

        if (in_array($newStatus, ['paid', 'refunded'], true) && ! $isSuperAdmin) {
            return redirect()->back()->with('error', 'การยืนยันรับเงินหรือคืนเงินด้วยมือทำได้เฉพาะผู้ดูแลระบบสูงสุด (Super Admin)');
        }

        if (in_array($oldStatus, ['paid', 'refunded'], true) && in_array($newStatus, ['pending', 'failed'], true)) {
            return redirect()->back()->with('error', 'ออเดอร์นี้ชำระเงิน/คืนเงินไปแล้ว ย้อนสถานะไม่ได้ ถ้าต้องการคืนเงินให้เลือก "คืนเงินแล้ว"');
        }

        if ($oldStatus === 'refunded') {
            return redirect()->back()->with('error', 'ออเดอร์นี้คืนเงินไปแล้ว เปลี่ยนสถานะการชำระเงินไม่ได้');
        }

        // 'refunded' → คืนเงินจริงผ่าน RefundService (ต้องจ่ายแล้วเท่านั้น)
        if ($newStatus === 'refunded') {
            if ($oldStatus !== 'paid') {
                return redirect()->back()->with('error', 'คืนเงินได้เฉพาะออเดอร์ที่ชำระเงินแล้ว');
            }

            try {
                app(RefundService::class)->processFullRefund($order, (int) $admin->id, $reason);
            } catch (\DomainException $e) {
                return redirect()->back()->with('error', $e->getMessage());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Admin payment-status refund failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->back()->with('error', 'คืนเงินไม่สำเร็จ ระบบยกเลิกรายการทั้งหมดแล้ว กรุณาลองใหม่หรือแจ้งผู้ดูแลระบบ');
            }

            $this->auditPaymentStatusChange($order, $oldStatus, 'refunded', $reason, $reference);

            return redirect()->back()->with('success', 'คืนเงินคำสั่งซื้อเข้ากระเป๋าลูกค้าเรียบร้อยแล้ว');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $newStatus, $reference, $oldStatus) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ((string) $locked->payment_status !== $oldStatus) {
                    throw new \DomainException('สถานะการชำระเงินถูกเปลี่ยนโดยผู้อื่นระหว่างนี้ กรุณารีเฟรชหน้าแล้วลองใหม่');
                }

                $locked->payment_status = $newStatus;
                if ($newStatus === 'paid') {
                    $locked->paid_at = $locked->paid_at ?? now();
                    $locked->payment_reference = $reference;
                    if ($locked->status === 'pending') {
                        $locked->status = 'paid';
                    }
                }
                // save() → OrderObserver: 'paid' = จ่ายเงินคืนลูกค้า + แบ่งเงินผู้ขาย (ทำครั้งเดียว)
                $locked->save();
                $order->setRawAttributes($locked->getAttributes(), true);
            });
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Admin payment-status update failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'อัปเดตสถานะการชำระเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        $this->auditPaymentStatusChange($order, $oldStatus, $newStatus, $reason, $reference);

        return redirect()->back()->with('success', 'อัพเดทสถานะการชำระเงินเรียบร้อยแล้ว (บันทึกเหตุผลไว้แล้ว)');
    }

    /**
     * ต่อท้ายบันทึกของแอดมินในออเดอร์ (ไม่เขียนทับของเดิม)
     *
     * รูปแบบบรรทัด: [Y-m-d H:i] แอดมิน #id: ข้อความ — อ่านค่าล่าสุดจาก DB ก่อนต่อ
     * (กันทับบรรทัดที่ auditPaymentStatusChange หรือแอดมินคนอื่นเพิ่งเขียน) แล้วซิงค์ค่าเข้าโมเดลในหน่วยความจำ
     */
    private function appendAdminNote(Order $order, string $note): void
    {
        $note = trim($note);
        if ($note === '') {
            return;
        }

        $line = '['.now()->format('Y-m-d H:i').'] แอดมิน #'.(auth()->id() ?? '-').': '.$note;
        $current = (string) Order::whereKey($order->id)->value('admin_notes');
        $updated = trim($current."\n".$line);

        Order::whereKey($order->id)->update(['admin_notes' => $updated]);
        $order->setAttribute('admin_notes', $updated);
        $order->syncOriginalAttribute('admin_notes');
    }

    /**
     * บันทึก audit การเปลี่ยนสถานะการชำระเงินด้วยมือ: accounting_activity_logs + log + admin_notes
     */
    private function auditPaymentStatusChange(Order $order, string $from, string $to, string $reason, ?string $reference): void
    {
        $admin = auth()->user();
        $context = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'admin_id' => $admin?->id,
            'from' => $from,
            'to' => $to,
            'reason' => $reason,
            'payment_reference' => $reference,
            'ip' => request()->ip(),
        ];

        \Illuminate\Support\Facades\Log::warning('Admin changed order payment status manually', $context);

        try {
            \App\Models\AccountingActivityLog::create([
                'user_id' => $admin?->id,
                'loggable_type' => Order::class,
                'loggable_id' => $order->id,
                'action' => 'order.payment_status_changed',
                'description' => mb_substr("เปลี่ยนสถานะการชำระเงิน {$from} → {$to}: {$reason}", 0, 2000),
                'old_values' => ['payment_status' => $from],
                'new_values' => ['payment_status' => $to, 'payment_reference' => $reference],
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Payment status audit log write failed', $context + ['error' => $e->getMessage()]);
        }

        try {
            $note = '['.now()->format('Y-m-d H:i').'] แอดมิน #'.($admin?->id ?? '-')
                ." เปลี่ยนสถานะชำระเงิน {$from} → {$to}: {$reason}"
                .($reference ? " (อ้างอิง: {$reference})" : '');
            Order::whereKey($order->id)->update([
                'admin_notes' => trim(((string) Order::whereKey($order->id)->value('admin_notes'))."\n".$note),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Payment status admin note write failed', $context + ['error' => $e->getMessage()]);
        }
    }

    /**
     * List all categories
     */
    public function categories(Request $request)
    {
        $query = ProductCategory::withCount('products');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->with('parent:id,name')->orderBy('name')->paginate(20)->withQueryString();

        // ทุกหมวด (ไม่แบ่งหน้า) สำหรับเลือกหมวดแม่ในฟอร์ม — เดิมใช้เฉพาะหน้าปัจจุบัน
        $allCategories = ProductCategory::orderBy('name')->get(['id', 'name', 'parent_id']);

        return view('admin.ecommerce.categories.index', compact('categories', 'allCategories'));
    }

    /**
     * Store new category
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'sort_order' => 'nullable|integer',
            'category_image' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Handle image upload
            if ($request->hasFile('category_image')) {
                $validated['image_url'] = $this->imageUploadService->uploadImage(
                    $request->file('category_image'),
                    'categories',
                    800,
                    800,
                    90
                );
            }

            // Handle checkbox properly
            $validated['is_active'] = $request->has('is_active');

            // Auto-generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = \Str::slug($validated['name']);
            }

            ProductCategory::create($validated);

            DB::commit();

            return redirect()->back()->with('success', 'สร้างหมวดหมู่เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $this->failMessage($e));
        }
    }

    /**
     * Update category
     */
    public function updateCategory(Request $request, ProductCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug,'.$category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'sort_order' => 'nullable|integer',
            'category_image' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Handle image upload
            if ($request->hasFile('category_image')) {
                // Delete old image if exists
                if ($category->image_url) {
                    $this->imageUploadService->deleteImage($category->image_url);
                }
                $validated['image_url'] = $this->imageUploadService->uploadImage(
                    $request->file('category_image'),
                    'categories',
                    800,
                    800,
                    90
                );
            }

            // Handle checkbox properly
            $validated['is_active'] = $request->has('is_active');

            // Auto-generate slug if name changed and slug is empty
            if ($validated['name'] !== $category->name && empty($validated['slug'])) {
                $validated['slug'] = \Str::slug($validated['name']);
            }

            $category->update($validated);

            DB::commit();

            return redirect()->back()->with('success', 'อัพเดทหมวดหมู่เรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $this->failMessage($e));
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory(ProductCategory $category)
    {
        // Check if category has products
        if ($category->products()->count() > 0) {
            return redirect()->back()->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีสินค้าอยู่');
        }

        $category->delete();

        return redirect()->back()->with('success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
    }

    /**
     * List all reviews
     */
    public function reviews(Request $request)
    {
        $query = ProductReview::with(['product', 'user']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        // เรียงลำดับ (whitelist)
        [$sortBy, $sortOrder] = $this->safeSort($request, ['created_at', 'rating'], 'created_at');
        $query->orderBy($sortBy, $sortOrder);

        $reviews = $query->paginate(20)->withQueryString();

        return view('admin.ecommerce.reviews.index', compact('reviews'));
    }

    /**
     * Update review status
     */
    public function updateReviewStatus(Request $request, ProductReview $review)
    {
        $validated = $request->validate([
            'is_approved' => 'required|boolean',
        ]);

        $review->update($validated);

        return redirect()->back()->with('success', 'อัพเดทสถานะรีวิวเรียบร้อยแล้ว');
    }

    /**
     * Delete review
     */
    public function deleteReview(ProductReview $review)
    {
        $review->delete();

        return redirect()->back()->with('success', 'ลบรีวิวเรียบร้อยแล้ว');
    }

    /**
     * แสดงหน้ารายงานยอดขาย E-commerce
     *
     * รวมข้อมูล:
     * - สรุปยอดขายรวม
     * - กราฟแนวโน้มยอดขาย
     * - สินค้าขายดี
     * - หมวดหมู่ที่มียอดขายสูงสุด
     * - สถานะคำสั่งซื้อ
     * - สถิติลูกค้า
     *
     * @return \Illuminate\View\View
     */
    public function reports(Request $request)
    {
        // รองรับ period presets หรือ custom date range
        $period = $request->get('period', 'month');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // ถ้าไม่ได้กำหนด date range ให้ใช้ period presets
        if (! $dateFrom || ! $dateTo) {
            $dates = $this->getDateRangeFromPeriod($period);
            $dateFrom = $dates['start'];
            $dateTo = $dates['end'];
        }

        // สรุปยอดขายรายวัน สำหรับกราฟ
        $salesReport = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // สินค้าขายดี Top 10 - ใช้ subquery เพื่อหลีกเลี่ยง GROUP BY issue กับ MySQL ONLY_FULL_GROUP_BY
        $salesSubquery = OrderItem::query()
            ->select('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as total_sales')
            ->selectRaw('SUM(order_items.subtotal) as total_revenue')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo.' 23:59:59'])
            ->groupBy('order_items.product_id');

        $topProducts = Product::query()
            ->leftJoinSub($salesSubquery, 'sales_data', function ($join) {
                $join->on('products.id', '=', 'sales_data.product_id');
            })
            ->select('products.*')
            ->selectRaw('COALESCE(sales_data.total_sales, 0) as total_sales')
            ->selectRaw('COALESCE(sales_data.total_revenue, 0) as total_revenue')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        // ยอดขายตามหมวดหมู่ - ใช้ subquery เพื่อหลีกเลี่ยง GROUP BY issue กับ MySQL ONLY_FULL_GROUP_BY
        $categorySalesSubquery = OrderItem::query()
            ->select('products.category_id')
            ->selectRaw('SUM(order_items.quantity) as total_sales')
            ->selectRaw('SUM(order_items.subtotal) as total_revenue')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo.' 23:59:59'])
            ->groupBy('products.category_id');

        $categoryPerformance = ProductCategory::query()
            ->leftJoinSub($categorySalesSubquery, 'category_sales', function ($join) {
                $join->on('product_categories.id', '=', 'category_sales.category_id');
            })
            ->select('product_categories.*')
            ->selectRaw('COALESCE(category_sales.total_sales, 0) as total_sales')
            ->selectRaw('COALESCE(category_sales.total_revenue, 0) as total_revenue')
            ->orderByDesc('total_revenue')
            ->get();

        // คำนวณ % สำหรับแต่ละหมวดหมู่
        $totalCategoryRevenue = $categoryPerformance->sum('total_revenue') ?: 1;
        $categoryPerformance = $categoryPerformance->map(function ($cat) use ($totalCategoryRevenue) {
            $cat->percentage = round(($cat->total_revenue / $totalCategoryRevenue) * 100, 1);

            return $cat;
        });

        // สถานะคำสั่งซื้อ
        $orderStatusDistribution = Order::whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                $statusLabels = [
                    'pending' => 'รอดำเนินการ',
                    'processing' => 'กำลังดำเนินการ',
                    'shipped' => 'จัดส่งแล้ว',
                    'delivered' => 'ส่งถึงแล้ว',
                    'completed' => 'สำเร็จ',
                    'cancelled' => 'ยกเลิก',
                    'refunded' => 'คืนเงิน',
                ];
                $statusColors = [
                    'pending' => 'yellow',
                    'processing' => 'blue',
                    'shipped' => 'indigo',
                    'delivered' => 'teal',
                    'completed' => 'green',
                    'cancelled' => 'red',
                    'refunded' => 'gray',
                ];
                $item->label = $statusLabels[$item->status] ?? $item->status;
                $item->color = $statusColors[$item->status] ?? 'gray';

                return $item;
            });

        // สถิติลูกค้า
        $customerStats = [
            'total_customers' => Order::whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->distinct('user_id')
                ->count('user_id'),
            'returning_customers' => Order::whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),
            'new_customers' => User::whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->whereHas('orders')
                ->count(),
        ];

        // สรุปสถิติ
        $summary = [
            'total_orders' => Order::whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])->count(),
            'completed_orders' => Order::where('status', 'completed')
                ->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])->count(),
            'pending_orders' => Order::where('status', 'pending')
                ->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')
                ->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])->count(),
            'total_revenue' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->sum('total_amount'),
            'average_order_value' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->avg('total_amount') ?? 0,
            'total_items_sold' => DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.payment_status', 'paid')
                ->whereBetween('orders.created_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->sum('order_items.quantity'),
        ];

        // คำนวณการเติบโตเทียบกับช่วงก่อนหน้า
        $daysDiff = now()->parse($dateFrom)->diffInDays(now()->parse($dateTo));
        $prevDateFrom = now()->parse($dateFrom)->subDays($daysDiff + 1)->format('Y-m-d');
        $prevDateTo = now()->parse($dateFrom)->subDay()->format('Y-m-d');

        $prevRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$prevDateFrom, $prevDateTo.' 23:59:59'])
            ->sum('total_amount');

        $prevOrders = Order::whereBetween('created_at', [$prevDateFrom, $prevDateTo.' 23:59:59'])->count();

        $summary['revenue_growth'] = $prevRevenue > 0
            ? round((($summary['total_revenue'] - $prevRevenue) / $prevRevenue) * 100, 1)
            : 0;

        $summary['orders_growth'] = $prevOrders > 0
            ? round((($summary['total_orders'] - $prevOrders) / $prevOrders) * 100, 1)
            : 0;

        // 💰 GP / VAT / กองทุนผู้แนะนำ ในช่วงวันที่เลือก
        $moneySplit = $this->orderMoneySplit($dateFrom, $dateTo);

        return view('admin.ecommerce.reports', compact(
            'moneySplit',
            'salesReport',
            'topProducts',
            'categoryPerformance',
            'orderStatusDistribution',
            'customerStats',
            'summary',
            'dateFrom',
            'dateTo',
            'period'
        ));
    }

    /**
     * แปลง period preset เป็น date range
     */
    private function getDateRangeFromPeriod(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'yesterday' => [
                'start' => now()->subDay()->format('Y-m-d'),
                'end' => now()->subDay()->format('Y-m-d'),
            ],
            'week' => [
                'start' => now()->subDays(6)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'month' => [
                'start' => now()->subDays(29)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'quarter' => [
                'start' => now()->subDays(89)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'year' => [
                'start' => now()->subYear()->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            default => [
                'start' => now()->subDays(29)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
        };
    }

    /**
     * บล็อกสินค้า (Admin)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function blockProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'block_reason' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            // อัปเดตสถานะบล็อก
            $product->update([
                'is_blocked' => true,
                'blocked_at' => now(),
                'blocked_by' => auth()->id(),
                'block_reason' => $validated['block_reason'],
                'is_active' => false, // ปิดการแสดงสินค้าอัตโนมัติ
            ]);

            // ส่งการแจ้งเตือนให้ร้านค้า
            if ($product->seller) {
                $product->seller->notify(new \App\Notifications\ProductBlockedNotification($product));
            }

            // บันทึก Activity Log
            activity()
                ->performedOn($product)
                ->causedBy(auth()->user())
                ->withProperties([
                    'reason' => $validated['block_reason'],
                    'seller_id' => $product->seller_id,
                ])
                ->log('บล็อกสินค้า');

            DB::commit();

            return redirect()->back()->with('success', 'บล็อกสินค้าเรียบร้อยแล้ว และแจ้งเตือนร้านค้าแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $this->failMessage($e));
        }
    }

    /**
     * ปลดบล็อกสินค้า (Admin)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unblockProduct(Product $product)
    {
        DB::beginTransaction();

        try {
            // อัปเดตสถานะปลดบล็อก
            $product->update([
                'is_blocked' => false,
                'unblocked_at' => now(),
                'unblocked_by' => auth()->id(),
                // หมายเหตุ: ไม่ต้องเปิด is_active อัตโนมัติ ให้ร้านค้าเปิดเอง
            ]);

            // ส่งการแจ้งเตือนให้ร้านค้า
            if ($product->seller) {
                $product->seller->notify(new \App\Notifications\ProductUnblockedNotification($product));
            }

            // บันทึก Activity Log
            activity()
                ->performedOn($product)
                ->causedBy(auth()->user())
                ->withProperties([
                    'seller_id' => $product->seller_id,
                ])
                ->log('ปลดบล็อกสินค้า');

            DB::commit();

            return redirect()->back()->with('success', 'ปลดบล็อกสินค้าเรียบร้อยแล้ว และแจ้งเตือนร้านค้าแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $this->failMessage($e));
        }
    }

    // =====================================================
    // Order Tracking Management
    // =====================================================

    /**
     * แสดงหน้าจัดการ Tracking ของ Order
     */
    public function orderTracking(Order $order)
    {
        // ⚠️ OrderTrackingHistory มี relation ชื่อ creator (ไม่มี user) — เดิมโหลด trackingHistory.user ทำให้หน้านี้ 500 ทุกครั้ง
        $order->load([
            'user',
            'items.product.images',
            'shippingProvider',
            'trackingHistory.creator',
            'messages.sender',
        ]);

        $shippingProviders = ShippingProvider::active()->ordered()->get();

        return view('admin.ecommerce.orders.tracking', compact('order', 'shippingProviders'));
    }

    /**
     * อัพเดทข้อมูล Tracking
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrderTracking(Request $request, Order $order)
    {
        $validated = $request->validate([
            'shipping_provider_id' => 'required|exists:shipping_providers,id',
            'tracking_number' => 'required|string|max:100',
            'estimated_delivery_at' => 'nullable|date',
            'admin_notes' => 'nullable|string|max:500',
        ], [
            'shipping_provider_id.required' => 'กรุณาเลือกบริษัทขนส่ง',
            'tracking_number.required' => 'กรุณากรอกหมายเลขพัสดุ',
        ]);

        DB::beginTransaction();

        try {
            // อัพเดทข้อมูล Order (บันทึกของแอดมิน "ต่อท้าย" ประวัติเดิม ไม่เขียนทับ)
            $order->update([
                'shipping_provider_id' => $validated['shipping_provider_id'],
                'tracking_number' => $validated['tracking_number'],
                'estimated_delivery_at' => $validated['estimated_delivery_at'] ?? null,
            ]);
            if (! empty($validated['admin_notes'])) {
                $this->appendAdminNote($order, 'จัดส่ง (เลขพัสดุ '.$validated['tracking_number'].'): '.$validated['admin_notes']);
            }

            $provider = ShippingProvider::find($validated['shipping_provider_id']);

            // ถ้าเพิ่งใส่ tracking ครั้งแรก ให้เปลี่ยนสถานะเป็น shipped
            // 🐛 เดิมเรียก markAsShipped(auth()->id()) → เลขพัสดุถูกเขียนทับเป็น id แอดมิน
            //    และ createEntry(..., auth()->id()) ส่ง int แทน array → TypeError หน้า 500
            if (in_array($order->status, ['paid', 'processing', 'confirmed'], true)) {
                $order->markAsShipped($validated['tracking_number'], $provider?->name, $provider?->id);
            } else {
                OrderTrackingHistory::createEntry($order, 'shipped', 'อัปเดตเลขพัสดุ', [
                    'description' => 'หมายเลขพัสดุ: '.$validated['tracking_number'],
                    'tracking_number' => $validated['tracking_number'],
                    'shipping_provider' => $provider?->name,
                    'created_by' => auth()->id(),
                    'created_by_type' => 'admin',
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'อัพเดทข้อมูลการจัดส่งเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $this->failMessage($e));
        }
    }

    /**
     * เพิ่ม Tracking History
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addTrackingHistory(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,in_transit,out_for_delivery,delivered',
            'description' => 'required|string|max:500',
            'location' => 'nullable|string|max:255',
        ], [
            'status.required' => 'กรุณาเลือกสถานะ',
            'status.in' => 'สถานะการจัดส่งไม่ถูกต้อง',
            'description.required' => 'กรุณากรอกรายละเอียด',
        ]);

        $labels = [
            'pending' => 'รอดำเนินการ',
            'processing' => 'กำลังเตรียมสินค้า',
            'shipped' => 'จัดส่งแล้ว',
            'in_transit' => 'อยู่ระหว่างขนส่ง',
            'out_for_delivery' => 'กำลังนำส่ง',
            'delivered' => 'ส่งถึงแล้ว',
        ];

        // 🐛 เดิมส่ง (int ผู้ใช้, location) เป็นอาร์กิวเมนต์ที่ 4-5 แต่ createEntry รับ array → TypeError หน้า 500
        OrderTrackingHistory::createEntry($order, $validated['status'], $labels[$validated['status']] ?? $validated['status'], [
            'description' => $validated['description'],
            'location' => $validated['location'] ?? null,
            'created_by' => auth()->id(),
            'created_by_type' => 'admin',
        ]);

        // อัพเดทสถานะ Order ตาม tracking status (ใช้ markAsDelivered ให้รายการสินค้าเปลี่ยนตาม)
        if ($validated['status'] === 'delivered' && ! in_array($order->status, Order::TERMINAL_STATUSES, true)) {
            $order->markAsDelivered();
        }

        return redirect()->back()->with('success', 'เพิ่มประวัติการจัดส่งเรียบร้อยแล้ว');
    }

    /**
     * ดึงข้อความของ Order (AJAX)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderMessages(Order $order)
    {
        $messages = $order->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * ส่งข้อความในนามของ Admin
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendOrderMessage(Request $request, Order $order)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ], [
            'message.required' => 'กรุณากรอกข้อความ',
        ]);

        OrderMessage::send(
            $order,
            auth()->id(),
            'admin',
            $validated['message']
        );

        return redirect()->back()->with('success', 'ส่งข้อความเรียบร้อยแล้ว');
    }

    /**
     * ทำเครื่องหมายข้อความว่าอ่านแล้ว
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markOrderMessagesRead(Order $order)
    {
        OrderMessage::where('order_id', $order->id)
            ->where('sender_type', '!=', 'admin')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'read_by' => auth()->id(),
            ]);

        // อัพเดทสถานะ unread ของ order
        $hasUnread = OrderMessage::where('order_id', $order->id)
            ->where('is_read', false)
            ->exists();
        $order->update(['has_unread_messages' => $hasUnread]);

        return response()->json(['success' => true]);
    }

    /**
     * รายการ Order ที่มีข้อความยังไม่ได้อ่าน
     *
     * @return \Illuminate\View\View
     */
    public function ordersWithUnreadMessages()
    {
        $orders = Order::with(['user', 'items.product.images'])
            ->where('has_unread_messages', true)
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return view('admin.ecommerce.orders.unread-messages', compact('orders'));
    }
}
