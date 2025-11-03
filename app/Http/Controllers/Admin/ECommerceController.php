<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ECommerceController extends Controller
{
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
        $topProducts = Product::withCount(['orderItems as total_sales' => function($query) {
                $query->select(DB::raw('SUM(quantity)'));
            }])
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();

        // Recent orders
        $recentOrders = Order::with(['user', 'items.product'])
            ->latest()
            ->limit(10)
            ->get();

        // Low stock products
        $lowStockProducts = Product::where('track_inventory', true)
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
        $query = Product::with(['category', 'seller']);

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
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'track_inventory' => 'boolean',
        ]);

        // Generate slug
        $validated['slug'] = \Str::slug($validated['name']);

        // Set seller_id to first user or current user
        $validated['seller_id'] = auth()->id() ?? 1;

        // Set defaults
        $validated['is_active'] = $request->has('is_active');
        $validated['is_featured'] = $request->has('is_featured');
        $validated['track_inventory'] = $request->has('track_inventory');
        $validated['stock_status'] = $validated['track_inventory'] && ($validated['stock_quantity'] ?? 0) > 0 ? 'in_stock' : 'out_of_stock';

        Product::create($validated);

        return redirect()->route('admin.ecommerce.products.index')
            ->with('success', 'เพิ่มสินค้าเรียบร้อยแล้ว');
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
     * Update product
     */
    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
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
        ]);

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

        $product->update($validated);

        return redirect()->back()->with('success', 'อัพเดทสินค้าเรียบร้อยแล้ว');
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
            'image_url' => 'nullable|string|max:500',
        ]);

        // Handle checkbox properly
        $validated['is_active'] = $request->has('is_active');

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = \Str::slug($validated['name']);
        }

        ProductCategory::create($validated);

        return redirect()->back()->with('success', 'สร้างหมวดหมู่เรียบร้อยแล้ว');
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
            'image_url' => 'nullable|string|max:500',
        ]);

        // Handle checkbox properly
        $validated['is_active'] = $request->has('is_active');

        // Auto-generate slug if name changed and slug is empty
        if ($validated['name'] !== $category->name && empty($validated['slug'])) {
            $validated['slug'] = \Str::slug($validated['name']);
        }

        $category->update($validated);

        return redirect()->back()->with('success', 'อัพเดทหมวดหมู่เรียบร้อยแล้ว');
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
}
