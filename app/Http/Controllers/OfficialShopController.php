<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CashbackService;
use App\Services\WalletService;
use Illuminate\Http\Request;

/**
 * Official Shop Controller
 *
 * จัดการหน้าร้านของระบบ (Admin/Official Shop)
 * แยกออกจากร้านผู้เช่าโดยสิ้นเชิง
 */
class OfficialShopController extends Controller
{
    /**
     * แสดงหน้าร้านของระบบ
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Query สินค้าของระบบเท่านั้น (seller_id เป็น null)
        $query = Product::with(['category'])
            ->active()
            ->inStock()
            ->whereNull('seller_id'); // เฉพาะสินค้าของระบบ

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

        $products = $query->paginate(24)->withQueryString();

        // Get categories for filter (เฉพาะที่มีสินค้าของระบบ)
        $categories = ProductCategory::active()
            ->whereHas('products', function ($q) {
                $q->active()->inStock()->whereNull('seller_id');
            })
            ->root()
            ->orderBy('sort_order')
            ->get();

        // Get unique brands from official products
        $brands = Product::active()
            ->inStock()
            ->whereNull('seller_id')
            ->whereNotNull('brand')
            ->distinct('brand')
            ->pluck('brand')
            ->sort();

        // สินค้าแนะนำของระบบ
        $featuredProducts = Product::with(['category'])
            ->active()
            ->featured()
            ->inStock()
            ->whereNull('seller_id')
            ->take(8)
            ->get();

        // สถิติร้านของระบบ
        $stats = [
            'official' => Product::active()->inStock()->whereNull('seller_id')->count(),
            'featured' => Product::active()->featured()->inStock()->whereNull('seller_id')->count(),
            'categories' => $categories->count(),
        ];

        return view('shop.official.index', compact(
            'products',
            'categories',
            'brands',
            'featuredProducts',
            'stats'
        ));
    }

    /**
     * แสดงรายละเอียดสินค้าของระบบ
     *
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function show($slug)
    {
        $product = Product::with([
            'category',
            'images',
            'variants',
            'approvedReviews.user'
        ])
            ->whereNull('seller_id') // ต้องเป็นสินค้าของระบบ
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        // Related products (จากระบบเท่านั้น)
        $relatedProducts = Product::with(['category'])
            ->active()
            ->inStock()
            ->whereNull('seller_id')
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

        return view('shop.official.show', compact(
            'product',
            'relatedProducts',
            'hasPurchased',
            'cashbackInfo'
        ));
    }

    /**
     * แสดงสินค้าตามหมวดหมู่ (ของระบบ)
     *
     * @param string $slug
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function category($slug, Request $request)
    {
        $category = ProductCategory::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $query = Product::with(['category'])
            ->active()
            ->inStock()
            ->whereNull('seller_id') // เฉพาะของระบบ
            ->where('category_id', $category->id);

        // Apply same filters and sorting as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
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

        $products = $query->paginate(24)->withQueryString();

        // Stats
        $stats = [
            'official' => $query->count(),
        ];

        return view('shop.official.category', compact('category', 'products', 'stats'));
    }

    /**
     * Quick search API (เฉพาะสินค้าของระบบ)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickSearch(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $products = Product::active()
            ->inStock()
            ->whereNull('seller_id') // เฉพาะของระบบ
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'slug', 'price', 'main_image_url')
            ->take(10)
            ->get();

        return response()->json($products);
    }

    /**
     * แสดงสินค้าแนะนำของระบบ
     *
     * @return \Illuminate\View\View
     */
    public function featured()
    {
        $products = Product::with(['category'])
            ->active()
            ->featured()
            ->inStock()
            ->whereNull('seller_id')
            ->latest('published_at')
            ->paginate(24);

        $stats = [
            'official' => Product::active()->inStock()->whereNull('seller_id')->count(),
            'featured' => $products->total(),
        ];

        return view('shop.official.featured', compact('products', 'stats'));
    }
}
