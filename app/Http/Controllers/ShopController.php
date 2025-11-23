<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CashbackService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * แสดงสินค้าทั้งหมด (หน้าแรกของร้านค้า)
     *
     * รองรับการกรองตามร้านระบบและร้านผู้เช่าคุณภาพสูง (5 ดาว)
     */
    public function index(Request $request)
    {
        // ✨ REDIRECT: ถ้าเป็น shop_type=official ให้ redirect ไปยังร้านของระบบใหม่
        if ($request->filled('shop_type') && $request->shop_type === 'official') {
            // สร้าง query parameters ใหม่ (ยกเว้น shop_type)
            $params = $request->except('shop_type');

            // Redirect ไปยัง official shop พร้อม query parameters
            return redirect()->route('official-shop.index', $params);
        }

        $query = Product::with(['category', 'seller'])
            ->active()
            ->inStock();

        // ตัวกรองประเภทร้าน (สำหรับ premium หรืออื่นๆ)
        if ($request->filled('shop_type')) {
            $shopType = $request->shop_type;

            if ($shopType === 'premium') {
                // ร้านผู้เช่า 5 ดาว (คะแนนเฉลี่ย >= 4.5 และมี seller)
                $query->whereNotNull('seller_id')
                      ->where('rating_average', '>=', 4.5)
                      ->where('rating_count', '>', 0); // ต้องมีรีวิวอย่างน้อย 1 รีวิว
            }
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // กรองตามช่วงราคา
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // กรองตามยี่ห้อ
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort_by', 'newest');
        switch ($sortBy) {
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating_average', 'desc');
                break;
            case 'newest':
            default:
                $query->latest('published_at');
                break;
        }

        $products = $query->paginate(24);

        // Get categories for filter
        $categories = ProductCategory::active()
            ->root()
            ->orderBy('sort_order')
            ->get();

        // Get unique brands
        $brands = Product::active()
            ->whereNotNull('brand')
            ->distinct('brand')
            ->pluck('brand')
            ->sort();

        // สินค้าแนะนำ
        $featuredProducts = Product::with(['category', 'seller'])
            ->active()
            ->featured()
            ->inStock()
            ->take(8)
            ->get();

        // สถิติสำหรับตัวกรอง (V3)
        $stats = [
            'all' => Product::active()->inStock()->count(),
            'official' => Product::active()->inStock()->whereNull('seller_id')->count(),
            'premium' => Product::active()->inStock()
                ->whereNotNull('seller_id')
                ->where('rating_average', '>=', 4.5)
                ->where('rating_count', '>', 0)
                ->count(),
        ];

        return view('shop.index', compact(
            'products',
            'categories',
            'brands',
            'featuredProducts',
            'stats'
        ));
    }

    /**
     * Display product details
     */
    public function show($slug)
    {
        $product = Product::with([
            'category',
            'seller',
            'images',
            'variants',
            'approvedReviews.user'
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        // Related products
        $relatedProducts = Product::with(['category', 'seller'])
            ->active()
            ->inStock()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(8)
            ->get();

        // Check if user has purchased this product (for review)
        $hasPurchased = false;
        if (auth()->check()) {
            $hasPurchased = $product->orderItems()
                ->whereHas('order', function ($q) {
                    $q->where('user_id', auth()->id())
                      ->where('status', 'completed');
                })
                ->exists();
        }

        // Calculate potential cashback
        $cashbackService = new CashbackService(new WalletService());
        $cashbackInfo = $cashbackService->calculateProductCashback($product, $product->price, 1);

        return view('shop.show', compact(
            'product',
            'relatedProducts',
            'hasPurchased',
            'cashbackInfo'
        ));
    }

    /**
     * Get products by category
     */
    public function category($slug, Request $request)
    {
        $category = ProductCategory::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $query = Product::with(['category', 'seller'])
            ->active()
            ->inStock()
            ->where('category_id', $category->id);

        // Apply same filters and sorting as index
        // ... (similar to index method)

        $products = $query->paginate(24);

        return view('shop.category', compact('category', 'products'));
    }

    /**
     * Quick search API
     */
    public function quickSearch(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $products = Product::active()
            ->inStock()
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'slug', 'price', 'main_image_url')
            ->take(10)
            ->get();

        return response()->json($products);
    }
}
