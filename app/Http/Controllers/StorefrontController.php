<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StoreBanner;
use App\Models\VendorStore;
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
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึงข้อมูล Banners สำหรับ Carousel
        $banners = $this->getBanners();

        // ดึงข้อมูล Categories พร้อม children และ products count
        $categories = $this->getCategories();

        // ดึง Flash Deals (สินค้าลดราคา หรือ featured)
        $flashDeals = $this->getFlashDeals();
        $flashDealEndTime = now()->endOfDay()->toIso8601String();

        // ดึง Featured Stores
        $featuredStores = $this->getFeaturedStores();

        // ดึงสินค้าตามตัวกรอง
        $products = $this->getFilteredProducts($request);

        // สถิติ
        $stats = $this->getStats();

        // ── โหมดการแสดงผลของหน้า ─────────────────────────────────────────────
        // 'home'   = ยังไม่เลือกอะไร → โชว์เต็มรูปแบบ (แบนเนอร์ใหญ่/ดีลเด็ด/หมวดหมู่/สิทธิประโยชน์)
        // 'browse' = กำลังเลือกดูอะไรอยู่ (หมวด/ค้นหา/แท็ก/ประเภทร้าน)
        //            → ตัดของตกแต่งหน้าแรกออกให้หมด เหลือ "ของที่เขากำลังหา" เต็มหน้า
        //
        // เหตุผล: แบนเนอร์ใหญ่ + ดีลเด็ด + โชว์เคสหมวด ซ้ำอยู่ทุกหน้าทำให้
        // คนเลือกหมวดแล้วต้องเลื่อนผ่านของเดิม ~1,500px กว่าจะเจอสินค้าที่ขอดู
        $activeCategory = $this->resolveActiveCategory($request);
        $browseMode = ($activeCategory
            || $request->filled('search')
            || $request->filled('q')
            || $request->filled('tag')
            || ($request->filled('shop_type') && $request->get('shop_type') !== 'all'))
            ? 'browse'
            : 'home';

        // ภาพปกหมวด (3 ชั้น: รูปแอดมิน → โมเสกจากสินค้าจริง → ไอคอน)
        $categoryCover = $activeCategory
            ? app(\App\Services\CategoryImageService::class)->cover($activeCategory)
            : null;

        return view('storefront.index', compact(
            'banners',
            'categories',
            'flashDeals',
            'flashDealEndTime',
            'featuredStores',
            'products',
            'stats',
            'browseMode',
            'activeCategory',
            'categoryCover'
        ));
    }

    /**
     * ดึงหมวดหมู่ระดับบนสุด พร้อมจำนวนสินค้า (แคช 10 นาที)
     *
     * ⚠️ หมายเหตุเรื่องการนับสินค้า (สำคัญมาก):
     * ProductCategory::products() เป็นความสัมพันธ์ hasMany(Product, 'category_id')
     * ซึ่งผูก "โดยตรง" เท่านั้น ดังนั้น withCount(['products']) บนหมวดแม่
     * จะนับเฉพาะสินค้าที่ตั้ง category_id = หมวดแม่ตรง ๆ
     * ถ้าสินค้าทั้งหมดถูกผูกไว้กับหมวดลูก หมวดแม่จะแสดงเลข 0 เสมอ
     *
     * แก้โดย: นับสินค้าของหมวดลูกไปพร้อมกัน (eager count ใน closure ของ children)
     * แล้วเพิ่มแอตทริบิวต์ใหม่ total_products_count = หมวดตัวเอง + หมวดลูกทุกหมวด
     * (คง products_count เดิมไว้ไม่แตะต้อง เพื่อไม่ให้ Blade เดิมพัง)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCategories()
    {
        return Cache::remember('storefront_categories_v2', 600, function () {
            $categories = ProductCategory::active()
                ->root()
                ->with(['children' => function ($query) {
                    // นับสินค้าของหมวดลูกไปด้วย เพื่อนำมารวมเป็นยอดรวมของหมวดแม่
                    $query->active()
                        ->orderBy('sort_order')
                        ->withCount(['products' => function ($q) {
                            $q->publicVisible()->inStock();
                        }]);
                }])
                ->withCount(['products' => function ($query) {
                    // นับเฉพาะสินค้าที่ผูกกับหมวดนี้โดยตรง (ของเดิม - ห้ามลบ)
                    $query->publicVisible()->inStock();
                }])
                ->orderBy('sort_order')
                ->get();

            // รวมยอดสินค้าทั้งสาขา = ของหมวดตัวเอง + ของหมวดลูกทั้งหมด
            $categories->each(function ($category) {
                $childrenTotal = $category->relationLoaded('children')
                    ? (int) $category->children->sum('products_count')
                    : 0;

                $category->setAttribute(
                    'total_products_count',
                    (int) ($category->products_count ?? 0) + $childrenTotal
                );
            });

            return $categories;
        });
    }

    /**
     * ดึงร้านค้าแนะนำสำหรับหน้าแรก (แคช 10 นาที)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getFeaturedStores()
    {
        return Cache::remember('storefront_featured_stores_v2', 600, function () {
            return VendorStore::where('is_active', true)
                ->where('is_featured_home', true)
                ->with(['products' => function ($query) {
                    $query->publicVisible()->inStock()->latest()->take(3);
                }])
                // ⚠️ ต้องมี withCount ด้วย — การ์ดร้าน (store-card) อ่าน $store->products_count
                //    เพื่อโชว์ "N สินค้า" ถ้าไม่นับมาให้ ตัวเลขจะหายไปจากหน้าแรกทั้งแถบ
                //    (with(['products' => take(3)]) ให้มาแค่ 3 ชิ้นสำหรับรูปตัวอย่าง ไม่ใช่จำนวนจริง)
                ->withCount(['products' => function ($query) {
                    $query->publicVisible()->inStock();
                }])
                ->orderBy('rating_average', 'desc')
                ->take(7) // 1 for official + 6 vendor stores
                ->get();
        });
    }

    /**
     * ดึงข้อมูล Banners สำหรับ Carousel (แคช 10 นาที)
     *
     * ใช้ scope ที่มีอยู่แล้วของ StoreBanner:
     * - forHomepage()        → location = 'homepage' และ store_id เป็น null
     *                          (กัน banner ของร้านค้าหลุดมาโผล่หน้าแรก)
     * - currentlyDisplaying() → is_active = true และอยู่ในช่วง start_at / end_at
     *                          (กัน banner หมดอายุ หรือยังไม่ถึงเวลาแสดง)
     * - ordered()            → เรียงตาม sort_order
     *
     * @return \Illuminate\Support\Collection
     */
    private function getBanners()
    {
        // แคชได้ปลอดภัยเพราะไม่ขึ้นกับ request ใด ๆ
        // ⚠️ ห้ามเปลี่ยนชื่อคีย์นี้ — Admin\StorefrontSettingsController เรียก
        //    Cache::forget('storefront_banners') อยู่ 5 จุด (เพิ่ม/แก้/สลับ/ลบแบนเนอร์)
        //    ถ้าใช้คีย์อื่น แอดมินแก้แบนเนอร์แล้วหน้าร้านจะไม่เปลี่ยนนาน 10 นาที
        return Cache::remember('storefront_banners', 600, function () {
            // ลองดึงจาก database ก่อน
            $dbBanners = collect();

            if (class_exists('App\Models\StoreBanner')) {
                $dbBanners = StoreBanner::forHomepage()
                    ->currentlyDisplaying()
                    ->ordered()
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

            // ถ้าไม่มี banners ใน database ใช้ default (ติดตั้งใหม่ต้องมี hero เสมอ)
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
                        'cta_url' => route('storefront.index'),
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
                        'cta_url' => route('storefront.index', ['sort_by' => 'newest']),
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
        });
    }

    /**
     * ดึงสินค้า Flash Deals
     *
     * ⚠️ แก้บั๊กลำดับความสำคัญของ OR:
     * เดิมเขียน ->where(ลดราคา)->orWhere(featured) โดยไม่ครอบวงเล็บรวม
     * ทำให้ SQL กลายเป็น "(เงื่อนไขพื้นฐาน AND ลดราคา) OR (featured)"
     * ส่งผลให้สินค้าที่ถูกลบ (soft delete) / ซ่อน / ถูกบล็อก / ของหมด
     * หลุดเข้ามาผ่านสาขา featured
     * แก้โดยครอบทั้งสองสาขาไว้ใน where() ก้อนเดียว เพื่อให้เงื่อนไขพื้นฐาน
     * (publicVisible + inStock) บังคับใช้กับทั้งสองสาขา
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getFlashDeals()
    {
        // bump เป็น v2 เพื่อทิ้ง cache เก่าที่มีสินค้าหลุดเงื่อนไขค้างอยู่
        return Cache::remember('storefront_flash_deals_v2', 300, function () {
            return Product::with(['category', 'mlmProductPv'])
                ->publicVisible()
                ->inStock()
                ->where(function ($query) {
                    // สินค้าลดราคา
                    $query->where(function ($q) {
                        $q->whereNotNull('compare_at_price')
                            ->whereColumn('compare_at_price', '>', 'price');
                    })
                        // หรือสินค้า featured
                        ->orWhere('is_featured', true);
                })
                ->orderByRaw('CASE WHEN compare_at_price > price THEN (compare_at_price - price) / compare_at_price ELSE 0 END DESC')
                ->take(12)
                ->get();
        });
    }

    /**
     * ดึงสินค้าตามตัวกรอง
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function getFilteredProducts(Request $request)
    {
        $query = Product::with(['category', 'seller', 'mlmProductPv'])
            ->publicVisible()
            ->inStock();

        // กรองตามประเภทร้าน
        $shopType = $request->get('shop_type');
        if ($shopType === 'official') {
            $query->officialShop();
        } elseif ($shopType === 'premium') {
            $query->notOfficialShop()
                ->where('rating_average', '>=', 4.5)
                ->where('rating_count', '>', 0);
        }

        // ค้นหา - รองรับทั้ง ?search= (ของหน้านี้) และ ?q= (ที่หน้าอื่นส่งมา)
        $search = $request->filled('search') ? $request->get('search') : $request->get('q');
        // กัน input เป็น array (เช่น ?q[]=x) ไม่ให้หลุดไปแคสต์เป็น string
        $search = is_scalar($search) ? trim((string) $search) : '';

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // กรองตามหมวดหมู่
        // ⚠️ เดิมใช้ whereHas('category', slug) ซึ่งจับได้เฉพาะสินค้าที่ผูกกับหมวดนั้นโดยตรง
        // ถ้า slug เป็นหมวดแม่ (สินค้าจริงอยู่ในหมวดลูก) จะได้ผลลัพธ์ 0 รายการ
        // แก้โดยแปลง slug → id ของหมวดนั้น + id ของหมวดลูกหลานทั้งหมด แล้วกรองด้วย whereIn
        if ($request->filled('category')) {
            // กัน input เป็น array (เช่น ?category[]=x) ไม่ให้หลุดไปแคสต์เป็น string
            $categorySlug = $request->get('category');
            $categoryIds = is_scalar($categorySlug)
                ? $this->resolveCategoryTreeIds((string) $categorySlug)
                : [];

            if (! empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            } else {
                // ไม่พบหมวดหมู่ตาม slug → ไม่คืนสินค้าใด ๆ (พฤติกรรมเดิม)
                $query->whereRaw('1 = 0');
            }
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
     * แปลง slug ของหมวดหมู่ → รายการ id ของหมวดนั้นเอง + หมวดลูกหลานทุกชั้น
     *
     * ใช้กับตัวกรอง ?category=<slug> ซึ่งเป็น CTA หลักของ mega menu
     * (หมวดแม่ต้องแสดงสินค้าของหมวดลูกด้วย ไม่ใช่คืน 0 รายการ)
     *
     * @param  string  $slug  slug ของหมวดหมู่
     * @return array<int> รายการ id ทั้งสาขา (ว่าง = ไม่พบหมวดหมู่)
     */
    /**
     * หาหมวดที่กำลังเลือกดูอยู่ (จาก ?category=slug) พร้อมหมวดลูกและหมวดแม่
     *
     * ใช้ทำ "หัวหมวด" บนหน้าเลือกดู — ต้องมี children ไว้ทำชิปหมวดย่อย
     * และ parent ไว้ทำ breadcrumb
     *
     * @return \App\Models\ProductCategory|null  null = ไม่ได้เลือกหมวด หรือ slug ไม่ตรงกับหมวดไหน
     */
    private function resolveActiveCategory(Request $request): ?ProductCategory
    {
        $slug = $request->get('category');

        // กัน ?category[]=x (array) และ slug ที่หน้าตาเป็นไปไม่ได้ — เหมือนที่ทำใน resolveCategoryTreeIds
        if (! is_scalar($slug)) {
            return null;
        }
        $slug = trim((string) $slug);
        if ($slug === '' || ! preg_match('/^[\pL\pN._-]{1,120}$/u', $slug)) {
            return null;
        }

        return ProductCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'parent:id,name,slug',
                'children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->select('id', 'name', 'slug', 'parent_id'),
            ])
            ->first();
    }

    private function resolveCategoryTreeIds(string $slug): array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return [];
        }

        // ⚠️ $slug มาจาก ?category= ของผู้ใช้โดยตรง → ถ้าแคชทุกค่าที่ส่งมา
        //    คนยิง /storefront?category=<สุ่ม> รัวๆ จะเขียนคีย์ใหม่ไม่จำกัด (cache flooding)
        //    กัน 2 ชั้น: (1) รับเฉพาะรูปแบบ slug ที่เป็นไปได้จริง (2) ไม่แคช "ผลลัพธ์ว่าง"
        if (! preg_match('/^[\pL\pN._-]{1,120}$/u', $slug)) {
            return [];
        }

        $cacheKey = 'storefront_category_tree_'.md5($slug);
        if (($cached = Cache::get($cacheKey)) !== null) {
            return $cached;
        }

        $resolve = function () use ($slug) {
            $rootId = ProductCategory::where('slug', $slug)->value('id');

            if (! $rootId) {
                return [];
            }

            $ids = [(int) $rootId];
            $frontier = [(int) $rootId];
            $depth = 0;

            // เดินลงหมวดลูกทีละชั้น (BFS) - จำกัดความลึก 10 ชั้น กันข้อมูลวนลูป
            while (! empty($frontier) && $depth < 10) {
                $childIds = ProductCategory::whereIn('parent_id', $frontier)
                    ->whereNotIn('id', $ids)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (empty($childIds)) {
                    break;
                }

                $ids = array_merge($ids, $childIds);
                $frontier = $childIds;
                $depth++;
            }

            return $ids;
        };

        $ids = $resolve();

        // แคชเฉพาะกรณีเจอหมวดจริง (10 นาที) — หมวดที่ไม่มีอยู่จะไม่กินพื้นที่แคช
        if (! empty($ids)) {
            Cache::put($cacheKey, $ids, 600);
        }

        return $ids;
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
                'official' => Product::active()->visible()->inStock()->officialShop()->count(),
                'premium' => Product::active()->visible()->inStock()
                    ->notOfficialShop()
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
     * @param  string  $slug  - Store slug
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
            ->publicVisible()
            ->inStock()
            ->latest()
            ->paginate(24);

        // ดึงหมวดหมู่ที่มีสินค้าในร้าน
        $storeCategories = ProductCategory::whereHas('products', function ($query) use ($store) {
            $query->where('seller_id', $store->user_id)
                ->publicVisible()
                ->inStock();
        })
            ->withCount(['products' => function ($query) use ($store) {
                $query->where('seller_id', $store->user_id)
                    ->publicVisible()
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickSearch(Request $request)
    {
        // ⚠️ endpoint สาธารณะ ไม่ต้องล็อกอิน — ถ้ายิง ?q[]=a มาจะได้ array
        //    แล้ว strlen(array) = TypeError → 500 ทั้งหน้า ต้องกันไว้ก่อนเสมอ
        $search = $request->get('q', '');
        $search = is_scalar($search) ? (string) $search : '';

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $products = Product::publicVisible()
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadMoreProducts(Request $request)
    {
        $products = $this->getFilteredProducts($request);

        // 🔗 ลิงก์ติดตามรายบุคคลสำหรับสินค้าที่โหลดเพิ่ม (infinite scroll)
        //    ⚠️ ถ้าไม่ทำตรงนี้ จะติดตามได้แค่ 30 ชิ้นแรกที่เรนเดอร์จากเบลด
        //       ทุกชิ้นที่ลูกค้าเลื่อนลงไปเจอจะใช้ลิงก์ Lazada ดิบ = ไม่รู้ว่าใครกด
        //       → พอเงินเข้าจริงก็จับคู่ไม่ได้ว่าใครควรได้ค่าคอม (เสียสิทธิ์ลูกค้า)
        //    ทำเป็น batch ครั้งเดียวเหมือนฝั่งเบลด ไม่วนใน map()
        $goCodes = [];
        $authUser = $request->user();
        if ($authUser instanceof \App\Models\User) {
            $goCodes = \App\Models\MarketplaceAffiliateLink::shortCodesForProducts(
                $authUser,
                $products instanceof \Illuminate\Contracts\Pagination\Paginator || method_exists($products, 'getCollection')
                    ? $products->getCollection()
                    : collect($products)
            );
        }

        // เตรียมข้อมูลสินค้าสำหรับ JSON response
        $productData = $products->map(function ($product) use ($goCodes) {
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

            // สินค้า affiliate ต้องลิงก์ออกไปยัง affiliate_url เท่านั้น
            // ถ้าลิงก์เข้าหน้าตะกร้าภายใน = ไม่ได้ค่าคอมมิชชั่นเลย (บั๊กเสียรายได้จริง)
            $isAffiliate = (bool) $product->is_affiliate;
            $affiliateUrl = $product->affiliate_url;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                // ⚠️ price / compare_at_price / rating_average เป็น decimal:2
                // ถ้าไม่ cast จะกลายเป็น string ใน JSON → .toFixed() ฝั่ง JS พัง
                'price' => (float) $product->price,
                'compare_at_price' => $product->compare_at_price !== null
                    ? (float) $product->compare_at_price
                    : null,
                'main_image_url' => $product->main_image_url ?: asset('images/no-image.png'),
                'discount' => $discount,
                'is_featured' => (bool) $product->is_featured,
                'is_official' => ! $product->seller_id,
                'rating_average' => (float) ($product->rating_average ?? 0),
                'rating_count' => (int) ($product->rating_count ?? 0),
                'sales_count' => (int) ($product->sales_count ?? 0),
                'pv' => $totalPv,
                'commission_rate' => (float) ($product->commission_rate ?? 0),
                'free_shipping' => $product->price >= 500,
                'is_affiliate' => $isAffiliate,
                'affiliate_url' => $affiliateUrl,
                'external_platform' => $product->external_platform,
                // มี short_code = วิ่งผ่าน /go/{code} เพื่อบันทึกว่าใครกด แล้วค่อยเด้งไป Lazada
                // ไม่มี (เช่น ไม่ได้ล็อกอิน) = ใช้ลิงก์ดิบตามเดิม — ยังได้ค่าคอมเข้าร้าน แค่ระบุตัวคนไม่ได้
                'url' => ($isAffiliate && $affiliateUrl)
                    ? (isset($goCodes[$product->id])
                        ? route('affiliate.go', $goCodes[$product->id])
                        : $affiliateUrl)
                    : route('shop.show', $product->slug ?: $product->id),
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
     * @return \Illuminate\View\View
     */
    public function stores(Request $request)
    {
        // ดึงร้านค้าทั้งหมดพร้อม filter
        $query = VendorStore::where('is_active', true)
            ->with(['products' => function ($query) {
                $query->publicVisible()->inStock()->latest()->take(4);
            }])
            ->withCount(['products' => function ($query) {
                $query->publicVisible()->inStock();
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
            'total_products' => Product::publicVisible()->inStock()->count(),
        ];

        return view('storefront.stores', compact(
            'stores',
            'categories',
            'stats',
            'sortBy'
        ));
    }
}
