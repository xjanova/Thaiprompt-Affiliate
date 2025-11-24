<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            'low_stock' => Product::where('stock_status', 'low_stock')->count(),

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
            ->withCount(['orderItems as total_sales' => function($query) {
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

        return view('admin.ecommerce.dashboard', compact(
            'stats',
            'orderGrowth',
            'revenueGrowth',
            'monthlyRevenue',
            'orderStatusData',
            'topProducts',
            'recentOrders',
            'lowStockProducts'
        ));
    }

    /**
     * List all products
     */
    public function products(Request $request)
    {
        $query = Product::with(['category', 'seller', 'images']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
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

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20);
        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.ecommerce.products.index', compact('products', 'categories'));
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

            // Set defaults
            $validated['is_active'] = $request->has('is_active');
            $validated['is_featured'] = $request->has('is_featured');
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
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'category_id' => 'nullable|exists:product_categories,id',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'pv_value' => 'nullable|numeric|min:0',
            'customer_cashback' => 'nullable|numeric|min:0',
            'cashback_percentage' => 'nullable|numeric|min:0|max:100',
            'brand' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
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
            $validated['track_inventory'] = $request->has('track_inventory');

            // Update slug if name changed
            if ($validated['name'] !== $product->name) {
                $validated['slug'] = \Str::slug($validated['name']);
            }

            // Update stock status
            if (isset($validated['track_inventory']) && $validated['track_inventory']) {
                $qty = $validated['stock_quantity'] ?? $product->stock_quantity;
                $threshold = $validated['low_stock_threshold'] ?? $product->low_stock_threshold ?? 10;

                if ($qty <= 0) {
                    $validated['stock_status'] = 'out_of_stock';
                } elseif ($qty <= $threshold) {
                    $validated['stock_status'] = 'low_stock';
                } else {
                    $validated['stock_status'] = 'in_stock';
                }
            }

            // Set cashback defaults if not provided
            $validated['customer_cashback'] = $validated['customer_cashback'] ?? 0;
            $validated['cashback_percentage'] = $validated['cashback_percentage'] ?? 0;

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
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
        $query = Order::with(['user', 'items.product']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
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

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate(20);

        return view('admin.ecommerce.orders.index', compact('orders'));
    }

    /**
     * Show order details
     */
    public function showOrder(Order $order)
    {
        $order->load(['user', 'items.product', 'items.product.images']);

        return view('admin.ecommerce.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled,refunded',
            'admin_notes' => 'nullable|string',
        ]);

        $order->update($validated);

        // If status is shipped, update shipped_at timestamp
        if ($validated['status'] === 'shipped' && !$order->shipped_at) {
            $order->update(['shipped_at' => now()]);
        }

        // If status is completed, update delivered_at timestamp
        if ($validated['status'] === 'completed' && !$order->delivered_at) {
            $order->update(['delivered_at' => now()]);
        }

        return redirect()->back()->with('success', 'อัพเดทสถานะคำสั่งซื้อเรียบร้อยแล้ว');
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order->update($validated);

        // If payment is marked as paid, update paid_at timestamp
        if ($validated['payment_status'] === 'paid' && !$order->paid_at) {
            $order->update(['paid_at' => now()]);
        }

        return redirect()->back()->with('success', 'อัพเดทสถานะการชำระเงินเรียบร้อยแล้ว');
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

        $categories = $query->orderBy('name')->paginate(20);

        return view('admin.ecommerce.categories.index', compact('categories'));
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
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update category
     */
    public function updateCategory(Request $request, ProductCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug,' . $category->id,
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
            return back()->withInput()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
            $query->where(function($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('product', function($q2) use ($search) {
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

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $reviews = $query->paginate(20);

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
     * Show reports page
     */
    public function reports(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Sales report
        $salesReport = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top products
        $topProducts = Product::withCount(['orderItems as total_sales' => function($query) use ($dateFrom, $dateTo) {
                $query->select(DB::raw('SUM(quantity)'))
                    ->whereHas('order', function($q) use ($dateFrom, $dateTo) {
                        $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                    });
            }])
            ->orderBy('total_sales', 'desc')
            ->limit(20)
            ->get();

        // Category performance
        $categoryPerformance = ProductCategory::withCount(['products as total_sales' => function($query) use ($dateFrom, $dateTo) {
                $query->select(DB::raw('SUM(order_items.quantity)'))
                    ->join('order_items', 'products.id', '=', 'order_items.product_id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$dateFrom, $dateTo]);
            }])
            ->orderBy('total_sales', 'desc')
            ->get();

        return view('admin.ecommerce.reports', compact(
            'salesReport',
            'topProducts',
            'categoryPerformance',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * บล็อกสินค้า (Admin)
     *
     * @param Request $request
     * @param Product $product
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ปลดบล็อกสินค้า (Admin)
     *
     * @param Product $product
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
