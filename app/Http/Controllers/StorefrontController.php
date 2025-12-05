<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorStore;
use App\Models\StoreBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Storefront Controller - จัดการหน้าร้านค้าหลักแบบ AliExpress
 *
 * คลาสนี้จัดการหน้าร้านค้าหลักที่มีการออกแบบแบบ AliExpress
 * รองรับ Flash Deals, Category Showcase, Featured Stores
 */
class StorefrontController extends Controller
{
    /**
     * แสดงหน้าร้านค้าหลัก (Storefront Index)
     *
     * รวม: Banner Carousel, Flash Deals, Categories, Featured Stores, Products
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึงข้อมูล Banners สำหรับ Carousel
        $banners = $this->getBanners();

        // ดึงข้อมูล Categories พร้อม children และ products count
        $categories = ProductCategory::active()
            ->root()
            ->with(['children' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->withCount(['products' => function ($query) {
                $query->active()->visible()->inStock();
            }])
            ->orderBy('sort_order')
            ->get();

        // ดึง Flash Deals (สินค้าลดราคา หรือ featured)
        $flashDeals = $this->getFlashDeals();
        $flashDealEndTime = now()->endOfDay()->toIso8601String();

        // ดึง Featured Stores
        $featuredStores = VendorStore::where('is_active', true)
            ->where('is_featured_home', true)
            ->with(['products' => function ($query) {
                $query->active()->visible()->inStock()->latest()->take(3);
            }])
            ->orderBy('rating_average', 'desc')
            ->take(7) // 1 for official + 6 vendor stores
            ->get();

        // ดึงสินค้าตามตัวกรอง
        $products = $this->getFilteredProducts($request);

        // สถิติ
        $stats = $this->getStats();

        return view('storefront.index', compact(
            'banners',
            'categories',
            'flashDeals',
            'flashDealEndTime',
            'featuredStores',
            'products',
            'stats'
        ));
    }

    /**
     * ดึงข้อมูล Banners สำหรับ Carousel
     *
     * @return \Illuminate\Support\Collection
     */
    private function getBanners()
    {
        // ลองดึงจาก database ก่อน
        $dbBanners = collect();

        if (class_exists('App\Models\StoreBanner')) {
            $dbBanners = StoreBanner::where('is_active', true)
                ->where('location', 'homepage')
                ->orderBy('sort_order')
                ->get()
                ->map(function ($banner) {
                    return [
                        'image' => $banner->image_url,
                        'title' => $banner->title,
                        'subtitle' => $banner->subtitle,
                        'badge' => $banner->badge,
                        'highlight' => $banner->highlight_text,
                        'highlight_label' => $banner->highlight_label,
                        'cta_text' => $banner->cta_text,
                        'cta_url' => $banner->cta_url,
                        'gradient' => $banner->gradient,
                    ];
                });
        }

        // ถ้าไม่มี banners ใน database ใช้ default
        if ($dbBanners->isEmpty()) {
            return collect([
                [
                    'image' => null,
                    'gradient' => 'from-orange-500 via-red-500 to-pink-600',
                    'badge' => 'Flash Sale',
                    'title' => 'ลดกระหน่ำสุดพิเศษ',
                    'subtitle' => 'ช้อปสินค้าคุณภาพ ราคาดีที่สุด ส่งฟรีทั่วไทย',
                    'highlight' => 'สูงสุด 70%',
                    'highlight_label' => 'ส่วนลด',
                    'cta_text' => 'ช้อปเลย',
                    'cta_url' => route('shop.index'),
                ],
                [
                    'image' => null,
                    'gradient' => 'from-purple-600 via-pink-500 to-red-500',
                    'badge' => 'สินค้าใหม่',
                    'title' => 'คอลเลคชั่นใหม่ประจำเดือน',
                    'subtitle' => 'สินค้ามาใหม่ล่าสุด อัพเดททุกสัปดาห์',
                    'highlight' => '500+',
                    'highlight_label' => 'รายการใหม่',
                    'cta_text' => 'ดูสินค้าใหม่',
                    'cta_url' => route('shop.index', ['sort_by' => 'newest']),
                ],
                [
                    'image' => null,
                    'gradient' => 'from-blue-600 via-indigo-500 to-purple-600',
                    'badge' => 'Official Store',
                    'title' => 'สินค้าจากทางการ',
                    'subtitle' => 'รับประกันคุณภาพ 100% พร้อมบริการหลังการขาย',
                    'highlight' => '100%',
                    'highlight_label' => 'ของแท้',
                    'cta_text' => 'เข้าชม Official Store',
                    'cta_url' => route('official-shop.index'),
                ],
            ]);
        }

        return $dbBanners;
    }

    /**
     * ดึงสินค้า Flash Deals
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getFlashDeals()
    {
        return Cache::remember('storefront_flash_deals', 300, function () {
            return Product::with(['category', 'mlmProductPv'])
                ->active()
                ->visible()
                ->inStock()
                ->where(function ($query) {
                    // สินค้าลดราคา
                    $query->whereNotNull('compare_at_price')
                        ->whereColumn('compare_at_price', '>', 'price');
                })
                ->orWhere(function ($query) {
                    // หรือสินค้า featured
                    $query->where('is_featured', true)
                        ->active()
                        ->inStock();
                })
                ->orderByRaw('CASE WHEN compare_at_price > price THEN (compare_at_price - price) / compare_at_price ELSE 0 END DESC')
                ->take(12)
                ->get();
        });
    }

    /**
     * ดึงสินค้าตามตัวกรอง
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function getFilteredProducts(Request $request)
    {
        $query = Product::with(['category', 'seller', 'mlmProductPv'])
            ->active()
            ->visible()
            ->inStock();

        // กรองตามประเภทร้าน
        $shopType = $request->get('shop_type');
        if ($shopType === 'official') {
            $query->whereNull('seller_id');
        } elseif ($shopType === 'premium') {
            $query->whereNotNull('seller_id')
                ->where('rating_average', '>=', 4.5)
                ->where('rating_count', '>', 0);
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

        // กรองตาม tag
        if ($request->filled('tag')) {
            $tag = $request->tag;
            $query->where(function ($q) use ($tag) {
                // ค้นหาใน JSON array - รองรับทั้ง MySQL และ MariaDB
                $q->whereJsonContains('tags', $tag)
                    ->orWhere('tags', 'like', "%\"{$tag}\"%");
            });
        }

        // กรองตามช่วงราคา
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
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

        return $query->paginate(30);
    }

    /**
     * ดึงสถิติสำหรับแสดงผล
     *
     * @return array
     */
    private function getStats()
    {
        return Cache::remember('storefront_stats', 600, function () {
            return [
                'all' => Product::active()->visible()->inStock()->count(),
                'official' => Product::active()->visible()->inStock()->whereNull('seller_id')->count(),
                'premium' => Product::active()->visible()->inStock()
                    ->whereNotNull('seller_id')
                    ->where('rating_average', '>=', 4.5)
                    ->where('rating_count', '>', 0)
                    ->count(),
                'stores' => VendorStore::where('is_active', true)->count() + 1, // +1 for official
            ];
        });
    }

    /**
     * แสดงหน้าร้านค้าของแต่ละร้าน
     *
     * @param string $slug - Store slug
     * @return \Illuminate\View\View
     */
    public function showStore($slug)
    {
        $store = VendorStore::where('store_slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // เพิ่ม visit count
        $store->incrementVisitCount();

        // ดึงสินค้าของร้าน
        $products = Product::with(['category', 'mlmProductPv'])
            ->where('seller_id', $store->user_id)
            ->active()
            ->visible()
            ->inStock()
            ->latest()
            ->paginate(24);

        // ดึงหมวดหมู่ที่มีสินค้าในร้าน
        $storeCategories = ProductCategory::whereHas('products', function ($query) use ($store) {
            $query->where('seller_id', $store->user_id)
                ->active()
                ->visible()
                ->inStock();
        })
            ->withCount(['products' => function ($query) use ($store) {
                $query->where('seller_id', $store->user_id)
                    ->active()
                    ->visible()
                    ->inStock();
            }])
            ->get();

        // ดึง Banners ของร้าน (ถ้ามี)
        $storeBanners = $this->getStoreBanners($store);

        return view('storefront.store', compact(
            'store',
            'products',
            'storeCategories',
            'storeBanners'
        ));
    }

    /**
     * ดึง Banners ของร้าน
     *
     * @param VendorStore $store
     * @return \Illuminate\Support\Collection
     */
    private function getStoreBanners(VendorStore $store)
    {
        // ถ้ามี custom banners
        if (class_exists('App\Models\StoreBanner')) {
            $banners = StoreBanner::where('store_id', $store->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            if ($banners->isNotEmpty()) {
                return $banners->map(function ($banner) {
                    return [
                        'image' => $banner->image_url,
                        'title' => $banner->title,
                        'subtitle' => $banner->subtitle,
                        'cta_text' => $banner->cta_text,
                        'cta_url' => $banner->cta_url,
                    ];
                });
            }
        }

        // Default banner จาก store_banner
        if ($store->store_banner) {
            return collect([
                [
                    'image' => $store->store_banner,
                    'title' => $store->store_name,
                    'subtitle' => $store->store_description,
                    'cta_text' => 'ดูสินค้าทั้งหมด',
                    'cta_url' => '#products',
                ],
            ]);
        }

        // No banners
        return collect();
    }

    /**
     * Quick Search API
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
            ->visible()
            ->inStock()
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'slug', 'price', 'main_image_url')
            ->take(8)
            ->get();

        return response()->json($products);
    }

    /**
     * Load More Products API - สำหรับ Infinite Scroll
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadMoreProducts(Request $request)
    {
        $products = $this->getFilteredProducts($request);

        // เตรียมข้อมูลสินค้าสำหรับ JSON response
        $productData = $products->map(function ($product) {
            $discount = 0;
            if ($product->compare_at_price && $product->compare_at_price > $product->price) {
                $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
            }

            // คำนวณ PV
            $totalPv = 0;
            if ($product->mlmProductPv && $product->mlmProductPv->count() > 0) {
                foreach ($product->mlmProductPv as $pv) {
                    $totalPv += $pv->pv_value;
                }
                $totalPv = $totalPv / $product->mlmProductPv->count();
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'compare_at_price' => $product->compare_at_price,
                'main_image_url' => $product->main_image_url ?? 'https://via.placeholder.com/300',
                'discount' => $discount,
                'is_featured' => $product->is_featured,
                'is_official' => !$product->seller_id,
                'rating_average' => $product->rating_average ?? 0,
                'rating_count' => $product->rating_count ?? 0,
                'sales_count' => $product->sales_count ?? 0,
                'pv' => $totalPv,
                'commission_rate' => $product->commission_rate ?? 0,
                'free_shipping' => $product->price >= 500,
                'url' => route('shop.show', $product->slug),
            ];
        });

        return response()->json([
            'products' => $productData,
            'has_more' => $products->hasMorePages(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }

    /**
     * แสดงหน้ารายการร้านค้าทั้งหมด (Stores Listing)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function stores(Request $request)
    {
        // ดึงร้านค้าทั้งหมดพร้อม filter
        $query = VendorStore::where('is_active', true)
            ->with(['products' => function ($query) {
                $query->active()->visible()->inStock()->latest()->take(4);
            }])
            ->withCount(['products' => function ($query) {
                $query->active()->visible()->inStock();
            }]);

        // Filter by search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('store_description', 'like', "%{$search}%");
            });
        }

        // Filter by featured
        if ($request->get('featured')) {
            $query->where('is_featured_home', true);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'rating');
        switch ($sortBy) {
            case 'newest':
                $query->latest();
                break;
            case 'products':
                $query->orderByDesc('products_count');
                break;
            case 'name':
                $query->orderBy('store_name');
                break;
            case 'rating':
            default:
                $query->orderByDesc('rating_average');
                break;
        }

        $stores = $query->paginate(12);

        // ดึง Categories สำหรับ filter
        $categories = ProductCategory::active()
            ->root()
            ->orderBy('sort_order')
            ->get();

        // สถิติ
        $stats = [
            'total_stores' => VendorStore::where('is_active', true)->count(),
            'featured_stores' => VendorStore::where('is_active', true)->where('is_featured_home', true)->count(),
            'total_products' => Product::active()->visible()->inStock()->count(),
        ];

        return view('storefront.stores', compact(
            'stores',
            'categories',
            'stats',
            'sortBy'
        ));
    }
}
