<?php

namespace App\Http\Controllers;

use App\Models\AiBotProfile;
use App\Models\SoftwareProduct;
use App\Models\TradingBotPackage;
use App\Models\TradingStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Unified Marketplace Controller
 *
 * รวม Marketplace ทั้งหมดในที่เดียว:
 * - AI Chatbots
 * - Trading Bots
 * - Software Products
 *
 * ใช้ V3 Design System
 */
class UnifiedMarketplaceController extends Controller
{
    /**
     * แสดงหน้า Unified Marketplace
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึงพารามิเตอร์จาก request
        $category = $request->get('category', 'all'); // all, chatbot, trading, software
        $sort = $request->get('sort', 'popular'); // popular, newest, price_low, price_high
        $search = $request->get('search');

        // สร้าง query สำหรับแต่ละประเภท
        $products = $this->getProducts($category, $sort, $search);

        // สถิติ
        $stats = $this->getMarketplaceStats();

        // หมวดหมู่
        $categories = $this->getCategories();

        return view('marketplace.unified.index', compact('products', 'stats', 'categories', 'category', 'sort'));
    }

    /**
     * แสดงสินค้าตามหมวดหมู่
     *
     * @param string $category
     * @return \Illuminate\View\View
     */
    public function category(string $category)
    {
        $products = $this->getProducts($category, 'popular', null);
        $stats = $this->getMarketplaceStats();
        $categories = $this->getCategories();

        return view('marketplace.unified.index', compact('products', 'stats', 'categories', 'category'));
    }

    /**
     * ค้นหาสินค้า
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $search = $request->get('q');
        $category = $request->get('category', 'all');

        $products = $this->getProducts($category, 'popular', $search);
        $stats = $this->getMarketplaceStats();
        $categories = $this->getCategories();

        return view('marketplace.unified.index', compact('products', 'stats', 'categories', 'category', 'search'));
    }

    /**
     * แสดงรายละเอียดสินค้า
     *
     * @param string $type chatbot|trading|software
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function show(string $type, string $slug)
    {
        $product = $this->findProduct($type, $slug);

        if (!$product) {
            abort(404);
        }

        // เพิ่ม view count
        $this->incrementViewCount($type, $product);

        // สินค้าที่เกี่ยวข้อง
        $relatedProducts = $this->getRelatedProducts($type, $product, 4);

        return view('marketplace.unified.show', compact('product', 'type', 'relatedProducts'));
    }

    /**
     * ดึงสินค้าทั้งหมดตามเงื่อนไข
     *
     * @param string $category
     * @param string $sort
     * @param string|null $search
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function getProducts(string $category, string $sort, ?string $search)
    {
        $products = collect();

        // AI Chatbots
        if ($category === 'all' || $category === 'chatbot') {
            $chatbots = AiBotProfile::where('is_public', true)
                ->where('is_active', true)
                ->when($search, function($query, $search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->get()
                ->map(function($bot) {
                    return [
                        'id' => $bot->id,
                        'type' => 'chatbot',
                        'name' => $bot->name,
                        'description' => $bot->description,
                        'price' => $bot->rental_price_per_month,
                        'price_label' => '฿' . number_format($bot->rental_price_per_month, 0) . '/เดือน',
                        'image' => $bot->avatar_url ?? '/images/default-bot.png',
                        'slug' => $bot->id,
                        'is_featured' => false,
                        'created_at' => $bot->created_at,
                    ];
                });

            $products = $products->merge($chatbots);
        }

        // Trading Bot Packages
        if ($category === 'all' || $category === 'trading') {
            $tradingPackages = TradingBotPackage::where('is_active', true)
                ->when($search, function($query, $search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->get()
                ->map(function($package) {
                    return [
                        'id' => $package->id,
                        'type' => 'trading',
                        'name' => $package->name,
                        'description' => $package->description,
                        'price' => $package->price,
                        'price_label' => '฿' . number_format($package->price, 0),
                        'image' => $package->image_url ?? '/images/default-trading.png',
                        'slug' => $package->id,
                        'is_featured' => $package->is_featured ?? false,
                        'created_at' => $package->created_at,
                    ];
                });

            $products = $products->merge($tradingPackages);
        }

        // Software Products
        if ($category === 'all' || $category === 'software') {
            $software = SoftwareProduct::where('is_active', true)
                ->when($search, function($query, $search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('short_description', 'like', "%{$search}%");
                    });
                })
                ->get()
                ->map(function($soft) {
                    return [
                        'id' => $soft->id,
                        'type' => 'software',
                        'name' => $soft->name,
                        'description' => $soft->short_description ?? $soft->description,
                        'price' => $soft->base_price,
                        'price_label' => $soft->price_label ?? ('฿' . number_format($soft->base_price, 0)),
                        'image' => $soft->main_image_url ?? '/images/default-software.png',
                        'slug' => $soft->slug,
                        'is_featured' => $soft->is_featured,
                        'created_at' => $soft->created_at,
                    ];
                });

            $products = $products->merge($software);
        }

        // เรียงลำดับ
        $products = $this->sortProducts($products, $sort);

        // Pagination manually
        $perPage = 12;
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $paginatedItems = $products->slice($offset, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $products->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginator;
    }

    /**
     * เรียงลำดับสินค้า
     *
     * @param \Illuminate\Support\Collection $products
     * @param string $sort
     * @return \Illuminate\Support\Collection
     */
    protected function sortProducts($products, string $sort)
    {
        switch ($sort) {
            case 'newest':
                return $products->sortByDesc('created_at');
            case 'price_low':
                return $products->sortBy('price');
            case 'price_high':
                return $products->sortByDesc('price');
            case 'popular':
            default:
                // สุ่มเรียงหรือใช้ logic ตาม featured
                return $products->sortByDesc('is_featured');
        }
    }

    /**
     * ค้นหาสินค้าตาม type และ slug
     *
     * @param string $type
     * @param string $slug
     * @return mixed
     */
    protected function findProduct(string $type, string $slug)
    {
        switch ($type) {
            case 'chatbot':
                return AiBotProfile::where('id', $slug)
                    ->where('is_public', true)
                    ->where('is_active', true)
                    ->first();

            case 'trading':
                return TradingBotPackage::where('id', $slug)
                    ->where('is_active', true)
                    ->first();

            case 'software':
                return SoftwareProduct::where('slug', $slug)
                    ->where('is_active', true)
                    ->first();

            default:
                return null;
        }
    }

    /**
     * เพิ่ม view count
     *
     * @param string $type
     * @param mixed $product
     * @return void
     */
    protected function incrementViewCount(string $type, $product)
    {
        if ($type === 'software' && isset($product->view_count)) {
            $product->increment('view_count');
        }
        // สำหรับ type อื่นๆ ถ้ามี view_count field
    }

    /**
     * ดึงสินค้าที่เกี่ยวข้อง
     *
     * @param string $type
     * @param mixed $product
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    protected function getRelatedProducts(string $type, $product, int $limit = 4)
    {
        // Logic สำหรับดึงสินค้าที่เกี่ยวข้อง
        // ตอนนี้ดึงแบบ random จาก type เดียวกัน
        return $this->getProducts($type, 'popular', null)->take($limit);
    }

    /**
     * ดึงสถิติ marketplace
     *
     * @return array
     */
    protected function getMarketplaceStats()
    {
        return [
            'total_products' => AiBotProfile::where('is_public', true)->count()
                + TradingBotPackage::where('is_active', true)->count()
                + SoftwareProduct::where('is_active', true)->count(),
            'total_chatbots' => AiBotProfile::where('is_public', true)->count(),
            'total_trading' => TradingBotPackage::where('is_active', true)->count(),
            'total_software' => SoftwareProduct::where('is_active', true)->count(),
        ];
    }

    /**
     * ดึงรายการหมวดหมู่
     *
     * @return array
     */
    protected function getCategories()
    {
        return [
            ['id' => 'all', 'name' => 'ทั้งหมด', 'icon' => 'fa-th', 'count' => null],
            ['id' => 'chatbot', 'name' => 'AI Chatbot', 'icon' => 'fa-robot', 'count' => AiBotProfile::where('is_public', true)->count()],
            ['id' => 'trading', 'name' => 'Trading Bot', 'icon' => 'fa-chart-line', 'count' => TradingBotPackage::where('is_active', true)->count()],
            ['id' => 'software', 'name' => 'ซอฟต์แวร์', 'icon' => 'fa-laptop-code', 'count' => SoftwareProduct::where('is_active', true)->count()],
        ];
    }
}
