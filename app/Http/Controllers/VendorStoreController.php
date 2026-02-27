<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StoreLayoutSetting;
use App\Models\VendorStore;
use App\Services\CashbackService;
use App\Services\ShippingService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class VendorStoreController extends Controller
{
    /**
     * แสดงรายการร้านค้า vendor ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // สร้าง query สำหรับดึงข้อมูลร้านค้า
        $query = VendorStore::where('is_active', true)
            ->with(['owner', 'package']);

        // ค้นหาร้านค้า
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('store_description', 'like', "%{$search}%")
                    ->orWhere('store_slug', 'like', "%{$search}%");
            });
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'name':
                $query->orderBy('store_name', 'asc');
                break;
            case 'popular':
                $query->orderBy('total_orders', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating_average', 'desc');
                break;
            default: // latest
                $query->orderBy('created_at', 'desc');
        }

        $stores = $query->paginate(12)->withQueryString();

        return view('vendor-store.index', compact('stores'));
    }

    /**
     * Display a vendor's public storefront
     */
    public function show(Request $request, string $slug)
    {
        $store = VendorStore::where('store_slug', $slug)
            ->where('is_active', true)
            ->with(['owner', 'package'])
            ->firstOrFail();

        // Build products query
        $query = Product::where('seller_id', $store->user_id)
            ->where('is_active', true)
            ->with(['category', 'images']);

        // Category filter
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sort
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating_average', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default: // latest
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(12)->withQueryString();

        // Get categories for this store's products
        $categories = ProductCategory::whereHas('products', function ($q) use ($store) {
            $q->where('seller_id', $store->user_id)
                ->where('is_active', true);
        })->get();

        // Store statistics
        $stats = [
            'total_products' => Product::where('seller_id', $store->user_id)
                ->where('is_active', true)
                ->count(),
            'total_sales' => $store->total_orders ?? 0,
            'rating' => $store->rating_average ?? 0,
            'rating_count' => $store->rating_count ?? 0,
        ];

        // ดึงหรือสร้าง layout settings — ทุกร้านมี StoreLayoutSetting เสมอ
        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($store->user_id);

        // ดึงสินค้าแนะนำ (featured products)
        $featuredProducts = null;
        if ($layoutSettings->show_featured_products) {
            $featuredProducts = Product::where('seller_id', $store->user_id)
                ->where('is_active', true)
                ->where('is_featured', true)
                ->with(['category', 'images'])
                ->take($layoutSettings->featured_products_count ?? 8)
                ->get();

            // ถ้าไม่มีสินค้า featured ให้ดึงสินค้าล่าสุดแทน
            if ($featuredProducts->isEmpty()) {
                $featuredProducts = Product::where('seller_id', $store->user_id)
                    ->where('is_active', true)
                    ->with(['category', 'images'])
                    ->latest()
                    ->take($layoutSettings->featured_products_count ?? 8)
                    ->get();
            }
        }

        return view('vendor-store.show-custom', compact(
            'store', 'products', 'categories', 'stats', 'layoutSettings', 'featuredProducts'
        ));
    }

    /**
     * แสดงรายละเอียดสินค้าในบริบทของร้านค้า — ใช้ธีมร้านค้าทั้งหมด
     *
     * @param string $storeSlug slug ของร้านค้า
     * @param string $productSlug slug ของสินค้า
     * @return \Illuminate\View\View
     */
    public function showProduct(string $storeSlug, string $productSlug)
    {
        // ดึงข้อมูลร้านค้า
        $store = VendorStore::where('store_slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        // ดึงสินค้า — ต้องเป็นของร้านนี้เท่านั้น
        $product = Product::with([
            'category',
            'seller',
            'images',
            'variants',
            'approvedReviews.user',
        ])
            ->where('seller_id', $store->user_id)
            ->where(function ($query) use ($productSlug) {
                $query->where('slug', $productSlug);
                if (is_numeric($productSlug)) {
                    $query->orWhere('id', $productSlug);
                }
            })
            ->firstOrFail();

        // เพิ่ม view count
        $product->incrementViewCount();

        // สินค้าที่เกี่ยวข้อง (จากร้านเดียวกัน)
        $relatedProducts = Product::with(['category', 'seller', 'images'])
            ->where('seller_id', $store->user_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, function ($q) use ($product) {
                $q->where('category_id', $product->category_id);
            })
            ->take(8)
            ->get();

        // เช็คว่า user ซื้อสินค้านี้หรือยัง (สำหรับรีวิว)
        $hasPurchased = false;
        if (auth()->check()) {
            $hasPurchased = $product->orderItems()
                ->whereHas('order', function ($q) {
                    $q->where('user_id', auth()->id())
                        ->where('status', 'completed');
                })
                ->exists();
        }

        // คำนวณ Cashback
        $cashbackInfo = (new CashbackService(new WalletService))
            ->calculateProductCashback($product, $product->price, 1);

        // ข้อมูลค่าจัดส่ง
        $shippingInfo = (new ShippingService)->getShippingDisplayInfo($product);

        // ดึง layout settings ของร้าน
        $layoutSettings = StoreLayoutSetting::getOrCreateForUser($store->user_id);

        return view('vendor-store.product-show', compact(
            'store',
            'product',
            'relatedProducts',
            'hasPurchased',
            'cashbackInfo',
            'shippingInfo',
            'layoutSettings'
        ));
    }
}
