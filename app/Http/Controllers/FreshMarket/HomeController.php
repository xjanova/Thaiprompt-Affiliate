<?php

namespace App\Http\Controllers\FreshMarket;

use App\Http\Controllers\Controller;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\ServiceProvider;
use App\Services\FreshMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HomeController - Frontend ตลาดสดไทยพร้อม
 *
 * หน้าเว็บสาธารณะ: หน้าแรก, ค้นหา, สินค้า, ผู้ขาย, ออเดอร์, ลงขาย
 */
class HomeController extends Controller
{
    protected FreshMarketService $marketService;

    public function __construct()
    {
        $this->marketService = new FreshMarketService;
    }

    // ===== Landing Pages — Onboarding ก่อนเพิ่มเพื่อน LINE =====

    /**
     * Landing Page สำหรับผู้ซื้อ
     */
    public function landingBuyer()
    {
        return view('taladsod.landing-buyer');
    }

    /**
     * Landing Page สำหรับผู้ขาย/ผู้ให้บริการ
     */
    public function landingSeller()
    {
        return view('taladsod.landing-seller');
    }

    /**
     * Landing Page สำหรับไรเดอร์/ช่างบริการ
     */
    public function landingRider()
    {
        return view('taladsod.landing-rider');
    }

    // ===== หน้าสาธารณะ =====

    /**
     * หน้าแรกตลาดสด
     */
    public function index()
    {
        $settings = FreshMarketSetting::getSettings();
        $categories = FreshMarketCategory::active()->root()->orderBy('sort_order')->get();

        // สินค้าแนะนำ (Featured)
        $featuredListings = FreshMarketListing::active()
            ->inStock()
            ->where('is_featured', true)
            ->with('seller:id,shop_name,rating_average')
            ->latest()
            ->limit(8)
            ->get();

        // สินค้าใหม่ล่าสุด
        $latestListings = FreshMarketListing::active()
            ->inStock()
            ->with('seller:id,shop_name,rating_average')
            ->latest()
            ->limit(12)
            ->get();

        // ผู้ขายแนะนำ
        $topSellers = FreshMarketSeller::active()
            ->orderByDesc('rating_average')
            ->limit(6)
            ->get();

        // ช่างบริการ (ตลาดช่าง)
        $serviceProviders = ServiceProvider::where('is_active', true)
            ->where('status', 'available')
            ->orderByDesc('rating')
            ->limit(8)
            ->get();

        return view('taladsod.home', compact(
            'settings',
            'categories',
            'featuredListings',
            'latestListings',
            'topSellers',
            'serviceProviders'
        ));
    }

    /**
     * หน้าค้นหาสินค้า
     */
    public function search(Request $request)
    {
        $categories = FreshMarketCategory::active()->root()->orderBy('sort_order')->get();
        $settings = FreshMarketSetting::getSettings();

        // ถ้ามีพิกัด → ค้นหาใกล้ตัว
        $listings = collect();

        if ($request->filled('lat') && $request->filled('lng')) {
            $listings = $this->marketService->searchListings(
                $request->float('lat'),
                $request->float('lng'),
                $request->float('radius', $settings->default_search_radius_km),
                [
                    'query' => $request->get('q'),
                    'category_id' => $request->get('category'),
                    'min_price' => $request->get('min_price'),
                    'max_price' => $request->get('max_price'),
                    'sort' => $request->get('sort', 'distance'),
                    'organic' => $request->boolean('organic'),
                ]
            );
        } elseif ($request->filled('q')) {
            // ค้นหาตามคำค้น (ไม่มีพิกัด)
            $listings = FreshMarketListing::active()
                ->inStock()
                ->search($request->get('q'))
                ->with('seller:id,shop_name,rating_average')
                ->latest()
                ->limit(50)
                ->get();
        }

        return view('taladsod.search', compact('listings', 'categories', 'settings'));
    }

    /**
     * หน้ารายละเอียดสินค้า
     */
    public function listing(string $slug)
    {
        $listing = FreshMarketListing::where('slug', $slug)
            ->with(['seller', 'category'])
            ->firstOrFail();

        $listing->incrementViews();

        // สินค้าใกล้เคียง (จาก seller เดียวกันหรือหมวดเดียวกัน)
        $relatedListings = FreshMarketListing::active()
            ->inStock()
            ->where('id', '!=', $listing->id)
            ->where(function ($q) use ($listing) {
                $q->where('seller_id', $listing->seller_id)
                    ->orWhere('category_id', $listing->category_id);
            })
            ->with('seller:id,shop_name')
            ->limit(6)
            ->get();

        return view('taladsod.listing', compact('listing', 'relatedListings'));
    }

    /**
     * หน้าโปรไฟล์ผู้ขาย
     */
    public function seller(int $id)
    {
        $seller = FreshMarketSeller::with('user:id,name')
            ->findOrFail($id);

        $listings = $seller->listings()
            ->active()
            ->inStock()
            ->with('category:id,name,icon')
            ->latest()
            ->paginate(12);

        return view('taladsod.seller', compact('seller', 'listings'));
    }

    /**
     * หน้าสินค้าตามหมวดหมู่
     */
    public function category(string $slug)
    {
        $category = FreshMarketCategory::where('slug', $slug)->firstOrFail();

        $listings = FreshMarketListing::active()
            ->inStock()
            ->inCategory($category->id)
            ->with('seller:id,shop_name,rating_average')
            ->latest()
            ->paginate(20);

        return view('taladsod.search', [
            'listings' => $listings,
            'categories' => FreshMarketCategory::active()->root()->orderBy('sort_order')->get(),
            'settings' => FreshMarketSetting::getSettings(),
            'currentCategory' => $category,
        ]);
    }

    // ===== AJAX APIs =====

    /**
     * API: สินค้าใกล้ตัว
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $listings = $this->marketService->searchListings(
            $request->float('lat'),
            $request->float('lng'),
            $request->float('radius', 10)
        );

        return response()->json([
            'success' => true,
            'data' => $listings->map(fn ($l) => [
                'id' => $l->id,
                'slug' => $l->slug,
                'title' => $l->title,
                'price' => $l->price,
                'unit' => $l->unit,
                'image' => $l->primary_image,
                'latitude' => $l->latitude,
                'longitude' => $l->longitude,
                'distance_km' => round($l->distance_km ?? 0, 1),
                'shop_name' => $l->seller?->shop_name,
                'rating' => $l->seller?->rating_average,
                'cashback' => $l->cashback_amount,
                'is_organic' => $l->is_organic,
            ]),
            'count' => $listings->count(),
        ]);
    }

    /**
     * API: รายการสินค้า (สำหรับ search component)
     */
    public function apiListings(Request $request): JsonResponse
    {
        $query = FreshMarketListing::active()->inStock()
            ->with('seller:id,shop_name,rating_average');

        if ($request->filled('lat') && $request->filled('lng')) {
            $query->nearby(
                $request->float('lat'),
                $request->float('lng'),
                $request->float('radius', 10)
            );
        }

        if ($request->filled('q')) {
            $query->search($request->get('q'));
        }

        if ($request->filled('category')) {
            $query->inCategory($request->integer('category'));
        }

        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->priceRange(
                $request->filled('min_price') ? $request->float('min_price') : null,
                $request->filled('max_price') ? $request->float('max_price') : null
            );
        }

        // Sort
        match ($request->get('sort', 'newest')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('order_count', 'desc'),
            default => $query->latest(),
        };

        $listings = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $listings->map(fn ($l) => [
                'id' => $l->id,
                'slug' => $l->slug,
                'title' => $l->title,
                'price' => $l->price,
                'unit' => $l->unit,
                'image' => $l->primary_image,
                'distance_km' => round($l->distance_km ?? 0, 1),
                'shop_name' => $l->seller?->shop_name,
                'rating' => $l->seller?->rating_average,
                'is_organic' => $l->is_organic,
            ]),
        ]);
    }

    // ===== หน้าที่ต้อง login =====

    /**
     * ออเดอร์ของฉัน
     */
    public function orders()
    {
        $orders = FreshMarketOrder::where('buyer_id', auth()->id())
            ->with(['seller:id,shop_name', 'listing:id,title,main_image_url'])
            ->latest()
            ->paginate(15);

        return view('taladsod.orders', compact('orders'));
    }

    /**
     * รายละเอียดออเดอร์
     */
    public function orderDetail(FreshMarketOrder $order)
    {
        // ตรวจสอบสิทธิ์
        if ($order->buyer_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['seller', 'listing', 'riderJob']);

        return view('taladsod.order-detail', compact('order'));
    }

    /**
     * สมัครเป็นผู้ขาย
     */
    public function registerSeller()
    {
        // เช็คว่าเป็นผู้ขายอยู่แล้วหรือไม่
        $existingSeller = FreshMarketSeller::where('user_id', auth()->id())->first();

        if ($existingSeller) {
            return redirect()->route('taladsod.seller.dashboard');
        }

        return view('taladsod.register-seller');
    }

    /**
     * บันทึกการสมัครผู้ขาย
     */
    public function storeSellerRegistration(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:200',
            'shop_description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'province' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $seller = FreshMarketSeller::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'subscription_type' => 'free',
            'is_active' => true,
        ]));

        return redirect()->route('taladsod.seller.dashboard')
            ->with('success', 'สมัครเป็นผู้ขายสำเร็จ! 🎉');
    }

    /**
     * แผงควบคุมผู้ขาย
     */
    public function sellerDashboard()
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->firstOrFail();
        $seller->load(['listings' => fn ($q) => $q->latest()->limit(10)]);

        $recentOrders = FreshMarketOrder::where('seller_id', $seller->id)
            ->with(['buyer:id,name', 'listing:id,title'])
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'total_listings' => $seller->total_listings,
            'total_sales' => $seller->total_sales,
            'total_revenue' => $seller->total_revenue,
            'pending_orders' => FreshMarketOrder::where('seller_id', $seller->id)->pending()->count(),
            'rating' => $seller->rating_average,
        ];

        return view('taladsod.seller-dashboard', compact('seller', 'recentOrders', 'stats'));
    }

    /**
     * ฟอร์มลงขายสินค้า (Web)
     */
    public function createListing()
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->firstOrFail();

        if (! $seller->canCreateListing()) {
            return redirect()->route('taladsod.seller.dashboard')
                ->with('error', 'คุณลงขายสินค้าเต็มจำนวนแล้ว กรุณาอัพเกรดแพ็กเกจ');
        }

        $categories = FreshMarketCategory::active()->orderBy('sort_order')->get();

        return view('taladsod.create-listing', compact('seller', 'categories'));
    }

    /**
     * บันทึกสินค้าใหม่
     */
    public function storeListing(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:fresh_market_categories,id',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'quantity_available' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_organic' => 'boolean',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
        ]);

        $seller = FreshMarketSeller::where('user_id', auth()->id())->firstOrFail();

        // อัพโหลดรูปภาพ
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('fresh-market', 'public');
                $imageUrls[] = '/storage/'.$path;
            }
        }

        $validated['images'] = $imageUrls;
        $validated['main_image_url'] = $imageUrls[0] ?? null;
        $validated['created_via'] = 'web';

        $listing = $this->marketService->createListing($seller, $validated);

        return redirect()->route('taladsod.seller.dashboard')
            ->with('success', "ลงขาย \"{$listing->title}\" สำเร็จ! 🎉");
    }

    /**
     * ฟอร์มแก้ไขสินค้า
     */
    public function editListing(FreshMarketListing $listing)
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->firstOrFail();

        if ($listing->seller_id !== $seller->id) {
            abort(403);
        }

        $categories = FreshMarketCategory::active()->orderBy('sort_order')->get();

        return view('taladsod.edit-listing', compact('listing', 'seller', 'categories'));
    }

    /**
     * อัพเดทสินค้า
     */
    public function updateListing(Request $request, FreshMarketListing $listing)
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->firstOrFail();

        if ($listing->seller_id !== $seller->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:fresh_market_categories,id',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'quantity_available' => 'required|integer|min:0',
            'is_organic' => 'boolean',
            'is_available' => 'boolean',
        ]);

        $listing->update($validated);

        return redirect()->route('taladsod.seller.dashboard')
            ->with('success', 'อัพเดทสินค้าสำเร็จ');
    }

    /**
     * ลบสินค้า
     */
    public function destroyListing(FreshMarketListing $listing)
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->firstOrFail();

        if ($listing->seller_id !== $seller->id) {
            abort(403);
        }

        $listing->delete();

        return redirect()->route('taladsod.seller.dashboard')
            ->with('success', 'ลบสินค้าสำเร็จ');
    }
}
