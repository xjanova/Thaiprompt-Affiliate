<?php

namespace App\Http\Controllers\FreshMarket;

use App\Exceptions\FreshMarketException;
use App\Http\Controllers\Controller;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketReferral;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\ServiceProvider;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AppBannerService;
use App\Services\FreshMarketService;
use App\Services\FreshMarketShopPresenceService;
use App\Services\Pricing\PricingEngine;
use App\Support\TaladsodWebUi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HomeController - Frontend ตลาดสดไทยพร๊อม
 *
 * หน้าเว็บสาธารณะ: หน้าแรก, ค้นหา, สินค้า, ผู้ขาย
 * หน้าผู้ซื้อ: ออเดอร์ของฉัน, ยกเลิก, ยืนยันรับ, รีวิว
 * หน้าผู้ขาย: สมัคร, แดชบอร์ด, จัดการออเดอร์ร้าน, ตั้งค่าร้าน, ลงขาย
 *
 * ตรรกะเงิน/สถานะทั้งหมดอยู่ใน FreshMarketService — controller แค่ตรวจสิทธิ์ + แปลงผลเป็นหน้าเว็บ
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
     * Landing Page สำหรับผู้ซื้อ (รับ ?ref=token จากลิงก์แนะนำเพื่อน)
     */
    public function landingBuyer(Request $request)
    {
        // บันทึกที่มาเพื่อ redirect ไป LINE ตลาดสดหลังสมัคร
        session(['register_origin' => 'taladsod']);

        if ($request->filled('ref') && preg_match('/^[A-Za-z0-9]{32}$/', (string) $request->get('ref'))) {
            session(['taladsod_referral_token' => $request->get('ref')]);
        }

        return view('taladsod.landing-buyer');
    }

    /**
     * Landing Page สำหรับผู้ขาย/ผู้ให้บริการ
     */
    public function landingSeller()
    {
        session(['register_origin' => 'taladsod']);

        return view('taladsod.landing-seller');
    }

    /**
     * Landing Page สำหรับไรเดอร์/ช่างบริการ
     */
    public function landingRider()
    {
        session(['register_origin' => 'taladsod']);

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

        // สินค้าแนะนำ (Featured) — โหลดคอลัมน์สถานะหน้าร้านด้วย เพื่อแสดงป้ายเปิด/ปิดให้ถูกต้อง
        $featuredListings = FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->where('is_featured', true)
            ->with('seller:'.FreshMarketSeller::SUMMARY_COLUMNS)
            ->withCount('optionGroups')
            ->latest()
            ->limit(8)
            ->get();

        // สินค้าใหม่ล่าสุด
        $latestListings = FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->with('seller:'.FreshMarketSeller::SUMMARY_COLUMNS)
            ->withCount('optionGroups')
            ->latest()
            ->limit(12)
            ->get();

        // แบนเนอร์แคมเปญ (แอดมินจัดการที่ /admin/app-banners — ตำแหน่ง taladsod) + ร้านที่เปิดอยู่ตอนนี้
        $banners = $this->webBanners('taladsod');
        $openShops = $this->openShopCards(12);
        $gpFree = $this->gpPromoActive();
        $nearbyShopsUrl = route('taladsod.api.nearby-shops');

        // ผู้ขายแนะนำ — เฉพาะร้านที่มีสินค้าจริงและผู้ซื้อเห็นได้
        $topSellers = FreshMarketSeller::active()
            ->where('total_listings', '>', 0)
            ->when(! FreshMarketListing::autoApproveSellers(), fn ($q) => $q->where('is_verified', true))
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
            'serviceProviders',
            'banners',
            'openShops',
            'gpFree',
            'nearbyShopsUrl'
        ));
    }

    /**
     * API (เว็บ): ร้านที่เปิดอยู่ใกล้คุณ — GET /taladsod/api/nearby-shops?lat&lng&radius
     *
     * รวมรถเข็น/ตลาดนัดที่เปิดอยู่ (ใช้ตำแหน่งตอนนี้) · ร้านปิดไม่ส่งตำแหน่งออกไป
     */
    public function nearbyShops(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.5|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => 'ตำแหน่งไม่ถูกต้อง กรุณาเปิด GPS แล้วลองใหม่',
            ], 422);
        }

        $radius = $request->filled('radius') ? (float) $request->input('radius') : 10.0;

        try {
            $shops = app(FreshMarketShopPresenceService::class)->nearbyShops(
                (float) $request->input('lat'),
                (float) $request->input('lng'),
                $radius,
                $request->user()
            );
        } catch (\Throwable $e) {
            Log::error('FreshMarket web: ค้นหาร้านใกล้ฉันล้มเหลว', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'code' => 'SERVER_ERROR',
                'message' => 'ค้นหาร้านใกล้คุณไม่สำเร็จ กรุณาลองใหม่',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => count($shops) > 0 ? 'ดึงร้านใกล้คุณสำเร็จ' : 'ยังไม่มีร้านที่เปิดอยู่ใกล้คุณ',
            'data' => [
                'shops' => $shops,
                'count' => count($shops),
                'radius_km' => $radius,
            ],
        ]);
    }

    /**
     * หน้าค้นหาสินค้า (รองรับ category เป็น slug หรือ id, เลือกหมวดหมู่อย่างเดียวได้โดยไม่ต้องมีพิกัด)
     */
    public function search(Request $request)
    {
        $categories = FreshMarketCategory::active()->root()->orderBy('sort_order')->get();
        $settings = FreshMarketSetting::getSettings();
        $category = $request->get('category');
        $currentCategory = $category
            ? (is_numeric($category)
                ? FreshMarketCategory::find((int) $category)
                : FreshMarketCategory::where('slug', (string) $category)->first())
            : null;

        $listings = collect();

        if ($request->filled('lat') && $request->filled('lng')) {
            $listings = $this->marketService->searchListings(
                $request->float('lat'),
                $request->float('lng'),
                $request->float('radius', (float) $settings->default_search_radius_km),
                [
                    'query' => $request->get('q'),
                    'category_id' => $category,
                    'min_price' => $request->get('min_price'),
                    'max_price' => $request->get('max_price'),
                    'sort' => $request->get('sort', 'distance'),
                    'organic' => $request->boolean('organic'),
                ]
            );
        } elseif ($request->filled('q') || $category) {
            // ค้นหาตามคำค้น/หมวดหมู่ (ไม่มีพิกัด)
            $listings = FreshMarketListing::visibleToBuyers()
                ->inStock()
                ->when($request->filled('q'), fn ($q) => $q->search((string) $request->get('q')))
                ->when($category, fn ($q) => $q->inCategory($category))
                ->with('seller:'.FreshMarketSeller::SUMMARY_COLUMNS)
                ->latest()
                ->limit(50)
                ->get();
        }

        return view('taladsod.search', compact('listings', 'categories', 'settings', 'currentCategory'));
    }

    /**
     * หน้ารายละเอียดสินค้า (รับ slug หรือ id — id จะ redirect ไป slug ถาวร)
     */
    public function listing(string $slug)
    {
        if (ctype_digit($slug)) {
            $byId = FreshMarketListing::find((int) $slug);

            if ($byId) {
                return redirect()->route('taladsod.listing', $byId->slug, 301);
            }
        }

        $listing = FreshMarketListing::where('slug', $slug)
            ->with(['seller', 'category', 'optionGroups.options'])
            ->firstOrFail();

        // สินค้าของร้านที่ถูกซ่อน/ระงับ → เจ้าของร้านยังดูได้ คนอื่น 404
        $isOwner = auth()->check() && (int) ($listing->seller?->user_id) === (int) auth()->id();
        if (! $isOwner && ! $listing->sellerIsVisible()) {
            abort(404);
        }

        $listing->incrementViews();

        // รีวิวของสินค้านี้ (จาก orders ที่มี buyer_rating — รวมออเดอร์หลายรายการที่มีสินค้านี้)
        $withThisListing = function ($q) use ($listing) {
            $q->where('listing_id', $listing->id)
                ->orWhereIn('id', \Illuminate\Support\Facades\DB::table('fresh_market_order_items')
                    ->select('order_id')
                    ->where('listing_id', $listing->id));
        };

        $reviews = FreshMarketOrder::where($withThisListing)
            ->withBuyerReview()
            ->with('buyer:id,name')
            ->latest()
            ->limit(10)
            ->get();

        $reviewStats = FreshMarketOrder::where($withThisListing)
            ->withBuyerReview()
            ->selectRaw('AVG(buyer_rating) as avg_rating, COUNT(*) as total')
            ->first();

        // ตัวเลือกสินค้า (ฟอร์มสั่งซื้อ/หยิบใส่ตะกร้า) — ราคาจริงคำนวณฝั่งเซิร์ฟเวอร์ตอนสั่ง
        $optionGroups = app(\App\Services\FreshMarketOptionService::class)->groupsForApi($listing, true);

        // ค่าส่งโดยประมาณ (ตัวเลขชุดเดียวกับ DeliveryFeeCalculator) — ค่าจริงคำนวณที่ /taladsod/api/delivery-quote
        $deliveryBaseRate = (float) Setting::get('rider.base_fee', 30);
        $deliveryPerKm = (float) Setting::get('rider.per_km_fee', 10);
        $paymentMethods = $this->marketService->availablePaymentMethods();
        $riderEnabled = (bool) FreshMarketSetting::getSettings()->rider_enabled;

        $relatedListings = FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->where('id', '!=', $listing->id)
            ->where(function ($q) use ($listing) {
                $q->where('seller_id', $listing->seller_id)
                    ->orWhere('category_id', $listing->category_id);
            })
            ->with('seller:'.FreshMarketSeller::SUMMARY_COLUMNS)
            ->withCount('optionGroups')
            ->limit(8)
            ->get();

        // การ์ดร้านบนหน้าสินค้า: เปิด/ปิด + ตำแหน่งตอนเปิด + ปุ่มติดตามร้าน
        $presenceService = app(FreshMarketShopPresenceService::class);
        $shopPresence = $listing->seller ? $listing->seller->presencePayload($isOwner) : null;
        $isFollowing = $listing->seller ? $presenceService->isFollowing(auth()->user(), $listing->seller) : false;
        $followersCount = $listing->seller ? $presenceService->followersCount($listing->seller) : 0;
        $shopEndpoints = $listing->seller ? [
            'location' => route('taladsod.shop.location', $listing->seller->id),
            'follow' => route('taladsod.shop.follow', $listing->seller->id),
            'unfollow' => route('taladsod.shop.unfollow', $listing->seller->id),
        ] : null;

        return view('taladsod.listing', compact(
            'listing', 'relatedListings', 'reviews', 'reviewStats',
            'deliveryBaseRate', 'deliveryPerKm', 'paymentMethods', 'riderEnabled', 'isOwner', 'optionGroups',
            'shopPresence', 'isFollowing', 'followersCount', 'shopEndpoints'
        ));
    }

    /**
     * หน้าโปรไฟล์ผู้ขาย
     */
    public function seller(int $id)
    {
        $seller = FreshMarketSeller::with('user:id,name')->findOrFail($id);

        $isOwner = auth()->check() && (int) $seller->user_id === (int) auth()->id();
        if (! $isOwner && ! $seller->isVisibleToBuyers()) {
            abort(404);
        }

        $listings = $seller->listings()
            ->active()
            ->inStock()
            ->with('category:id,name,icon')
            ->withCount('optionGroups')
            ->latest()
            ->paginate(24);

        // ร้านรถเข็น/ตลาดนัด: สถานะเปิด/ปิด + ตำแหน่งตอนเปิด (poll ที่ shopEndpoints.location) + ติดตามร้าน
        $presenceService = app(\App\Services\FreshMarketShopPresenceService::class);
        $presence = $seller->presencePayload($isOwner);
        $isFollowing = $presenceService->isFollowing(auth()->user(), $seller);
        $followersCount = $presenceService->followersCount($seller);
        $shopEndpoints = [
            'location' => route('taladsod.shop.location', $seller->id),
            'follow' => route('taladsod.shop.follow', $seller->id),
            'unfollow' => route('taladsod.shop.unfollow', $seller->id),
        ];

        return view('taladsod.seller', compact(
            'seller', 'listings', 'isOwner', 'presence', 'isFollowing', 'followersCount', 'shopEndpoints'
        ));
    }

    /**
     * หน้าสินค้าตามหมวดหมู่
     */
    public function category(string $slug)
    {
        $category = FreshMarketCategory::where('slug', $slug)->firstOrFail();

        $listings = FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->inCategory($category->id)
            ->with('seller:'.FreshMarketSeller::SUMMARY_COLUMNS)
            ->withCount('optionGroups')
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
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
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
                'id' => (int) $l->id,
                'slug' => $l->slug,
                'title' => $l->title,
                'price' => (float) $l->price,
                'unit' => $l->unit,
                'image' => $l->primary_image,
                // ตำแหน่งร้านตอนนี้ (ร้านเคลื่อนที่ที่เปิดอยู่ = ตำแหน่งปัจจุบัน)
                'latitude' => $l->displayCoordinates()['latitude'],
                'longitude' => $l->displayCoordinates()['longitude'],
                'distance_km' => round((float) ($l->distance_km ?? 0), 1),
                'shop_is_open' => $l->seller ? $l->seller->isOpenNow() : null,
                'shop_name' => $l->seller?->shop_name,
                'rating' => (float) ($l->seller?->rating_average ?? 0),
                'cashback' => (float) $l->cashback_amount,
                'is_organic' => (bool) $l->is_organic,
            ])->values(),
            'count' => $listings->count(),
        ]);
    }

    /**
     * API: รายการสินค้า (สำหรับ search component) — category รับ id หรือ slug
     */
    public function apiListings(Request $request): JsonResponse
    {
        $query = FreshMarketListing::visibleToBuyers()->inStock()
            ->with('seller:'.FreshMarketSeller::SUMMARY_COLUMNS);

        if ($request->filled('lat') && $request->filled('lng')) {
            $query->nearby(
                $request->float('lat'),
                $request->float('lng'),
                min($request->float('radius', 10), 50)
            );
        }

        if ($request->filled('q')) {
            $query->search((string) $request->get('q'));
        }

        if ($request->filled('category')) {
            $query->inCategory((string) $request->get('category'));
        }

        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->priceRange(
                $request->filled('min_price') ? $request->float('min_price') : null,
                $request->filled('max_price') ? $request->float('max_price') : null
            );
        }

        match ($request->get('sort', 'newest')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('order_count', 'desc'),
            'distance' => null,
            default => $query->latest(),
        };

        $listings = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $listings->map(fn ($l) => [
                'id' => (int) $l->id,
                'slug' => $l->slug,
                'title' => $l->title,
                'price' => (float) $l->price,
                'unit' => $l->unit,
                'image' => $l->primary_image,
                'distance_km' => round((float) ($l->distance_km ?? 0), 1),
                'shop_is_open' => $l->seller ? $l->seller->isOpenNow() : null,
                'shop_name' => $l->seller?->shop_name,
                'rating' => (float) ($l->seller?->rating_average ?? 0),
                'is_organic' => (bool) $l->is_organic,
            ])->values(),
        ]);
    }

    /**
     * API: คำนวณค่าส่งไรเดอร์ (ใช้ในฟอร์มสั่งซื้อก่อนกดยืนยัน)
     *
     * GET /taladsod/api/delivery-quote?listing_id=&latitude=&longitude=&quantity=
     */
    public function deliveryQuote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'listing_id' => 'required|integer',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'quantity' => 'nullable|integer|min:1|max:999',
        ]);

        $listing = FreshMarketListing::visibleToBuyers()->with('seller')->find($validated['listing_id']);

        if (! $listing) {
            return response()->json(['success' => false, 'code' => 'LISTING_UNAVAILABLE', 'message' => 'ไม่พบสินค้า'], 404);
        }

        $quote = $this->marketService->quoteDelivery($listing, (float) $validated['latitude'], (float) $validated['longitude']);
        $subtotal = round((float) $listing->price * (int) ($validated['quantity'] ?? 1), 2);

        return response()->json([
            'success' => $quote['available'],
            'code' => $quote['code'],
            'message' => $quote['message'] ?? 'คำนวณค่าส่งสำเร็จ',
            'data' => array_merge($quote, [
                'subtotal' => $subtotal,
                'grand_total' => round($subtotal + ($quote['available'] ? $quote['total_fee'] : 0), 2),
            ]),
        ]);
    }

    // ===== หน้าผู้ซื้อ (ต้อง login) =====

    /**
     * ออเดอร์ของฉัน (กรองสถานะได้ ?status=)
     */
    public function orders(Request $request)
    {
        $statusFilter = FreshMarketOrder::normalizeStatus($request->get('status'));

        $orders = FreshMarketOrder::where('buyer_id', auth()->id())
            ->statusFilter($statusFilter)
            ->with(['seller:id,shop_name', 'listing:id,title,slug,unit,main_image_url,images', 'items'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statuses = collect(FreshMarketOrder::STATUSES)
            ->mapWithKeys(fn ($s) => [$s => FreshMarketOrder::statusLabel($s)])
            ->all();

        return view('taladsod.orders', compact('orders', 'statusFilter', 'statuses'));
    }

    /**
     * รายละเอียดออเดอร์ (ผู้ซื้อ) — ผู้ขายของออเดอร์นี้ถูกพาไปหน้าจัดการออเดอร์ร้าน
     */
    public function orderDetail(FreshMarketOrder $order)
    {
        if ((int) $order->buyer_id !== (int) auth()->id()) {
            $seller = $this->currentSeller();

            if ($seller && (int) $order->seller_id === (int) $seller->id) {
                return redirect()->route('taladsod.seller.orders.show', $order);
            }

            abort(403);
        }

        $order->load(['seller', 'listing', 'riderJob.rider', 'items']);
        $allowedActions = $order->allowedActions('buyer');
        $canReview = $order->canBeReviewed();

        return view('taladsod.order-detail', compact('order', 'allowedActions', 'canReview'));
    }

    /**
     * สถานะออเดอร์ล่าสุด (JSON) — หน้ารายละเอียดออเดอร์ของผู้ซื้อ poll เพื่อรีเฟรชเมื่อร้าน/ไรเดอร์อัปเดต
     *
     * GET /taladsod/orders/{order}/status → {success, message, data{id, order_status, status_label, payment_status, allowed_actions[], rider_job_status, updated_at}}
     */
    public function orderStatus(FreshMarketOrder $order): JsonResponse
    {
        if ((int) $order->buyer_id !== (int) auth()->id()) {
            return response()->json(['success' => false, 'code' => 'ORDER_NOT_FOUND', 'message' => 'ไม่พบออเดอร์'], 404);
        }

        $order->loadMissing('riderJob');

        return response()->json([
            'success' => true,
            'message' => 'ดึงสถานะออเดอร์สำเร็จ',
            'data' => [
                'id' => (int) $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->order_status,
                'status_label' => $order->status_label,
                'payment_status' => $order->payment_status,
                'allowed_actions' => $order->allowedActions('buyer'),
                'rider_job_status' => $order->riderJob?->status,
                'updated_at' => $order->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * สั่งซื้อสินค้าจากหน้ารายละเอียด
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'listing_id' => 'required|integer|exists:fresh_market_listings,id',
            'quantity' => 'required|integer|min:1|max:999',
            // ตัวเลือกสินค้า (id) + โน้ตถึงร้านของรายการนี้ — ราคาคำนวณฝั่งเซิร์ฟเวอร์
            'option_ids' => 'nullable|array|max:300',
            'option_ids.*' => 'integer|min:1',
            'item_note' => 'nullable|string|max:255',
            'delivery_type' => 'required|in:pickup,rider',
            'payment_method' => 'nullable|in:wallet,cod',
            'buyer_latitude' => 'required_if:delivery_type,rider|nullable|numeric|between:-90,90',
            'buyer_longitude' => 'required_if:delivery_type,rider|nullable|numeric|between:-180,180',
            'delivery_address' => 'required_if:delivery_type,rider|nullable|string|max:500',
            'delivery_notes' => 'nullable|string|max:500',
        ], [
            'buyer_latitude.required_if' => 'กรุณาปักหมุดตำแหน่งจัดส่ง',
            'buyer_longitude.required_if' => 'กรุณาปักหมุดตำแหน่งจัดส่ง',
            'delivery_address.required_if' => 'กรุณากรอกที่อยู่จัดส่ง',
        ]);

        $listing = FreshMarketListing::with('seller')->find($validated['listing_id']);

        if (! $listing) {
            return back()->withInput()->with('error', 'สินค้านี้ไม่พร้อมขายแล้ว');
        }

        $this->claimSessionReferral();

        try {
            $order = $this->marketService->createOrder(auth()->user(), $listing, array_merge($validated, [
                // ไม่ได้เลือกตัวเลือกมา = [] → สินค้าที่มีกลุ่มบังคับจะแจ้งให้เลือกก่อน
                'option_ids' => $validated['option_ids'] ?? [],
                // เว็บที่ยังไม่มีตัวเลือกวิธีจ่าย → เก็บเงินปลายทางก่อน (ถ้าเปิดอยู่)
                'default_payment_method' => 'cod',
                'channel' => 'web',
            ]));

            return redirect()
                ->route('taladsod.orders.show', $order)
                ->with('success', 'สั่งซื้อสำเร็จ! หมายเลข: '.$order->order_number);
        } catch (FreshMarketException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('FreshMarket web: สั่งซื้อล้มเหลว', [
                'user_id' => auth()->id(),
                'listing_id' => $validated['listing_id'],
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'เกิดข้อผิดพลาดในการสั่งซื้อ กรุณาลองใหม่');
        }
    }

    /**
     * ผู้ซื้อยกเลิกออเดอร์ (ได้เฉพาะตอนร้านยังไม่รับ)
     */
    public function cancelOrder(Request $request, FreshMarketOrder $order): RedirectResponse
    {
        if ((int) $order->buyer_id !== (int) auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        return $this->runOrderAction(function () use ($order, $validated) {
            $this->marketService->cancelOrder($order, (string) ($validated['reason'] ?? ''), 'buyer', auth()->user());

            return redirect()->route('taladsod.orders.show', $order)->with('success', 'ยกเลิกออเดอร์เรียบร้อยแล้ว');
        });
    }

    /**
     * ยืนยันรับสินค้าแล้ว (→ completed + ปล่อยเงินให้ร้าน) พร้อมให้คะแนนได้ในครั้งเดียว
     */
    public function confirmOrder(Request $request, FreshMarketOrder $order): RedirectResponse
    {
        if ((int) $order->buyer_id !== (int) auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'buyer_rating' => 'nullable|integer|min:1|max:5',
            'buyer_review' => 'nullable|string|max:1000',
            'rider_rating' => 'nullable|integer|min:1|max:5',
        ]);

        return $this->runOrderAction(function () use ($order, $validated) {
            $completed = $this->marketService->completeOrder($order, 'buyer', auth()->user());

            if (! empty($validated['buyer_rating'])) {
                try {
                    $this->marketService->rateOrder(
                        $completed,
                        auth()->user(),
                        (int) $validated['buyer_rating'],
                        $validated['buyer_review'] ?? null,
                        isset($validated['rider_rating']) ? (int) $validated['rider_rating'] : null
                    );
                } catch (FreshMarketException $e) {
                    // ยืนยันรับสำเร็จแล้ว คะแนนซ้ำไม่ต้องทำให้ล้ม
                }
            }

            return redirect()
                ->route('taladsod.orders.show', $order)
                ->with('success', 'ยืนยันรับสินค้าสำเร็จ! ขอบคุณที่อุดหนุนค่ะ');
        });
    }

    /**
     * บันทึกรีวิว/ให้ดาว (ร้าน + ไรเดอร์)
     */
    public function storeReview(Request $request, FreshMarketOrder $order): RedirectResponse
    {
        if ((int) $order->buyer_id !== (int) auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'buyer_rating' => 'required|integer|min:1|max:5',
            'buyer_review' => 'nullable|string|max:1000',
            'rider_rating' => 'nullable|integer|min:1|max:5',
            'rider_review' => 'nullable|string|max:1000',
        ]);

        return $this->runOrderAction(function () use ($order, $validated) {
            $this->marketService->rateOrder(
                $order,
                auth()->user(),
                (int) $validated['buyer_rating'],
                $validated['buyer_review'] ?? null,
                isset($validated['rider_rating']) ? (int) $validated['rider_rating'] : null,
                $validated['rider_review'] ?? null
            );

            return back()->with('success', 'ขอบคุณสำหรับรีวิว!');
        });
    }

    // ===== ผู้ขาย =====

    /**
     * สมัครเป็นผู้ขาย
     */
    public function registerSeller()
    {
        if ($this->currentSeller()) {
            return redirect()->route('taladsod.seller.dashboard');
        }

        return view('taladsod.register-seller');
    }

    /**
     * บันทึกการสมัครผู้ขาย (ต้องปักหมุดร้าน + ยอมรับเงื่อนไข)
     */
    public function storeSellerRegistration(Request $request)
    {
        if ($this->currentSeller()) {
            return redirect()->route('taladsod.seller.dashboard')->with('info', 'คุณสมัครเป็นผู้ขายไว้แล้ว');
        }

        $validated = $request->validate([
            'shop_name' => 'required|string|max:200',
            'shop_description' => 'nullable|string|max:1000',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'address' => 'required|string|max:500',
            'province' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'sub_district' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'agree_terms' => 'accepted',
        ], [
            'latitude.required' => 'กรุณาปักหมุดตำแหน่งร้าน (ใช้คำนวณระยะทางและเรียกไรเดอร์)',
            'longitude.required' => 'กรุณาปักหมุดตำแหน่งร้าน (ใช้คำนวณระยะทางและเรียกไรเดอร์)',
            'agree_terms.accepted' => 'กรุณายอมรับเงื่อนไขการขาย',
            'phone.regex' => 'รูปแบบเบอร์โทรไม่ถูกต้อง',
        ] + $this->thaiRuleMessages(), $this->thaiAttributes());

        unset($validated['agree_terms']);

        try {
            $seller = $this->marketService->registerSeller(auth()->user(), $validated);
        } catch (FreshMarketException $e) {
            return redirect()->route('taladsod.seller.dashboard')->with('info', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('FreshMarket web: สมัครผู้ขายล้มเหลว', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'สมัครไม่สำเร็จ กรุณาลองใหม่');
        }

        $message = $seller->is_verified
            ? 'สมัครเป็นผู้ขายสำเร็จ! 🎉'
            : 'สมัครเป็นผู้ขายสำเร็จ! ร้านของคุณรอแอดมินยืนยันก่อนสินค้าจะแสดงต่อผู้ซื้อ';

        return redirect()->route('taladsod.seller.dashboard')->with('success', $message);
    }

    /**
     * แผงควบคุมผู้ขาย
     */
    public function sellerDashboard()
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')
                ->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        $seller->load(['listings' => fn ($q) => $q->latest()->limit(10)]);

        $recentOrders = FreshMarketOrder::where('seller_id', $seller->id)
            ->with(['buyer:id,name', 'listing:id,title,unit', 'items'])
            ->latest()
            ->limit(10)
            ->get();

        $pendingOrders = FreshMarketOrder::where('seller_id', $seller->id)->pending()->count();
        $activeOrders = FreshMarketOrder::where('seller_id', $seller->id)
            ->whereIn('order_status', [
                FreshMarketOrder::STATUS_ACCEPTED, FreshMarketOrder::STATUS_PREPARING,
                FreshMarketOrder::STATUS_READY, FreshMarketOrder::STATUS_DELIVERING,
            ])->count();

        // view เดิมอ่านแบบ $stats->key → ส่งเป็น object
        $stats = (object) [
            'total_listings' => (int) $seller->total_listings,
            'total_sales' => (int) $seller->total_sales,
            'total_revenue' => (float) $seller->total_revenue,
            'pending_orders' => $pendingOrders,
            'active_orders' => $activeOrders,
            'rating' => (float) $seller->rating_average,
            'rating_count' => (int) $seller->rating_count,
        ];

        $gpDebt = $seller->outstandingGpDebt();
        $maxCashbackPercent = $this->marketService->maxCashbackPercent($seller);
        $referralLinks = $this->referralLinksFor($seller);
        $subscription = [
            'type' => $seller->subscription_type,
            'expires_at' => $seller->subscription_expires_at,
            'is_paid' => $seller->hasPaidSubscription(),
            'monthly_fee' => (float) FreshMarketSetting::getSettings()->monthly_subscription_fee,
            'fee_mode' => FreshMarketSetting::getSettings()->fee_mode,
            'can_create_listing' => $seller->canCreateListing(),
        ];

        // ร้านรถเข็น/ตลาดนัด: การ์ด "เปิดร้านที่นี่วันนี้ / ส่งตำแหน่งสด / ปิดร้าน" (AJAX ไปที่ presenceEndpoints)
        $presence = array_merge($seller->presencePayload(true), [
            'has_fixed_location' => $seller->hasPickupLocation(),
            'followers_count' => app(\App\Services\FreshMarketShopPresenceService::class)->followersCount($seller),
            'live_send_interval_seconds' => 30,
        ]);
        $presenceEndpoints = [
            'presence' => route('taladsod.seller.presence'),
            'open' => route('taladsod.seller.open'),
            'location' => route('taladsod.seller.location'),
            'close' => route('taladsod.seller.close'),
            'mobile_mode' => route('taladsod.seller.mobile-mode'),
        ];

        // อัตรา GP ตลาดสดตอนนี้ + โปรฯ GP ฟรีช่วงเปิดตัว (การ์ดค่าธรรมเนียมบนแดชบอร์ด)
        $gpRate = $this->currentFreshGpRate($seller);
        $gpFree = $this->gpPromoActive();

        return view('taladsod.seller-dashboard', compact(
            'seller', 'recentOrders', 'stats', 'gpDebt', 'maxCashbackPercent', 'referralLinks', 'subscription', 'pendingOrders',
            'presence', 'presenceEndpoints', 'gpRate', 'gpFree'
        ));
    }

    /**
     * ออเดอร์ของร้าน (ฝั่งผู้ขาย)
     */
    public function sellerOrders(Request $request)
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        $statusFilter = FreshMarketOrder::normalizeStatus($request->get('status'));

        $orders = FreshMarketOrder::where('seller_id', $seller->id)
            ->statusFilter($statusFilter)
            ->with(['buyer:id,name', 'listing:id,title,slug,unit,main_image_url,images', 'riderJob', 'items'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = FreshMarketOrder::where('seller_id', $seller->id)
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->map(fn ($v) => (int) $v)
            ->all();

        $statuses = collect(FreshMarketOrder::STATUSES)
            ->mapWithKeys(fn ($s) => [$s => FreshMarketOrder::statusLabel($s)])
            ->all();

        return view('taladsod.seller-orders', compact('seller', 'orders', 'statusFilter', 'statusCounts', 'statuses'));
    }

    /**
     * รายละเอียดออเดอร์ (ฝั่งผู้ขาย) + ปุ่มที่ทำได้
     */
    public function sellerOrderShow(FreshMarketOrder $order)
    {
        $seller = $this->currentSeller();

        if (! $seller || (int) $order->seller_id !== (int) $seller->id) {
            abort(403);
        }

        $order->load(['buyer', 'listing', 'riderJob.rider', 'items']);
        $allowedActions = $order->allowedActions('seller');
        $actionLabels = collect($allowedActions)->mapWithKeys(fn ($a) => [$a => FreshMarketOrder::actionLabel($a)])->all();

        return view('taladsod.seller-order-detail', compact('seller', 'order', 'allowedActions', 'actionLabels'));
    }

    /**
     * ผู้ขายเปลี่ยนสถานะออเดอร์: accept | prepare | ready | handover | cancel (ต้องมีเหตุผล)
     */
    public function sellerOrderAction(Request $request, FreshMarketOrder $order): RedirectResponse
    {
        $seller = $this->currentSeller();

        if (! $seller || (int) $order->seller_id !== (int) $seller->id) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:accept,prepare,ready,handover,cancel',
            'reason' => 'required_if:action,cancel|nullable|string|max:500',
            // กดจากหน้ารายการออเดอร์ → กลับไปหน้ารายการ (คงตัวกรองสถานะไว้)
            'return_to' => 'nullable|in:list,detail',
            'return_status' => 'nullable|string|max:30',
        ], [
            'reason.required_if' => 'กรุณาระบุเหตุผลที่ยกเลิก',
            'reason.max' => 'เหตุผลยาวได้ไม่เกิน 500 ตัวอักษร',
            'action.*' => 'คำสั่งไม่ถูกต้อง',
        ]);

        return $this->runOrderAction(function () use ($order, $validated) {
            $this->marketService->applyAction($order, $validated['action'], 'seller', auth()->user(), [
                'reason' => $validated['reason'] ?? '',
            ]);

            $message = FreshMarketOrder::actionLabel($validated['action']).'เรียบร้อยแล้ว';

            if (($validated['return_to'] ?? null) === 'list') {
                $status = FreshMarketOrder::normalizeStatus($validated['return_status'] ?? null);

                return redirect()->route('taladsod.seller.orders', $status ? ['status' => $status] : [])
                    ->with('success', $message.' (#'.$order->order_number.')');
            }

            return redirect()->route('taladsod.seller.orders.show', $order)->with('success', $message);
        });
    }

    /**
     * ตัวเลขออเดอร์ร้านล่าสุด (JSON) — หน้าออเดอร์/แดชบอร์ดผู้ขาย poll ทุก ~20 วินาทีเพื่อรีเฟรชเมื่อมีออเดอร์ใหม่
     *
     * GET /taladsod/seller/orders-poll → {success, data{pending, active, latest_order_id, last_updated_at, counts{status: n}, server_time}}
     */
    public function sellerOrdersPoll(): JsonResponse
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return response()->json(['success' => false, 'code' => 'NOT_SELLER', 'message' => 'คุณยังไม่ได้สมัครเป็นผู้ขาย'], 403);
        }

        $counts = FreshMarketOrder::where('seller_id', $seller->id)
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->map(fn ($v) => (int) $v)
            ->all();

        $active = 0;
        foreach ([FreshMarketOrder::STATUS_ACCEPTED, FreshMarketOrder::STATUS_PREPARING, FreshMarketOrder::STATUS_READY, FreshMarketOrder::STATUS_DELIVERING] as $status) {
            $active += (int) ($counts[$status] ?? 0);
        }

        $lastUpdated = FreshMarketOrder::where('seller_id', $seller->id)->max('updated_at');

        return response()->json([
            'success' => true,
            'message' => 'ดึงข้อมูลออเดอร์ร้านสำเร็จ',
            'data' => [
                'pending' => (int) ($counts[FreshMarketOrder::STATUS_PENDING] ?? 0),
                'active' => $active,
                'latest_order_id' => (int) (FreshMarketOrder::where('seller_id', $seller->id)->max('id') ?? 0),
                'last_updated_at' => $lastUpdated ? \Illuminate\Support\Carbon::parse($lastUpdated)->toIso8601String() : null,
                'counts' => (object) $counts,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * รายการสินค้าทั้งหมดของร้าน (ฝั่งผู้ขาย) — กรอง ?status=selling|hidden|sold_out|suspended
     */
    public function sellerListings(Request $request)
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        $statusFilter = in_array($request->get('status'), ['selling', 'hidden', 'sold_out', 'suspended'], true)
            ? (string) $request->get('status')
            : 'all';

        $applyFilter = function ($query, string $filter) {
            return match ($filter) {
                'selling' => $query->where('status', 'active')->where('is_available', true),
                'hidden' => $query->where('status', 'active')->where('is_available', false),
                'sold_out' => $query->where('status', 'sold_out'),
                'suspended' => $query->where('status', 'suspended'),
                default => $query,
            };
        };

        $listings = $applyFilter($seller->listings(), $statusFilter)
            ->with('category:id,name,icon')
            ->withCount('optionGroups')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [];
        foreach (['all', 'selling', 'hidden', 'sold_out', 'suspended'] as $filter) {
            $counts[$filter] = $applyFilter($seller->listings(), $filter)->count();
        }

        $canCreate = $seller->canCreateListing();
        $gpRate = $this->currentFreshGpRate($seller);

        return view('taladsod.seller-listings', compact('seller', 'listings', 'statusFilter', 'counts', 'canCreate', 'gpRate'));
    }

    /**
     * รายได้ร้าน (ฝั่งผู้ขาย): ยอดวันนี้/7 วัน/เดือนนี้/ทั้งหมด + กราฟ 14 วัน + เงินรอเข้า + GP ค้าง + ประวัติรับเงิน
     */
    public function sellerEarnings(Request $request)
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        $completed = fn () => FreshMarketOrder::where('seller_id', $seller->id)
            ->where('order_status', FreshMarketOrder::STATUS_COMPLETED);

        $sum = function ($from) use ($completed) {
            $row = $completed()
                ->when($from, fn ($q) => $q->where('completed_at', '>=', $from))
                ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as gross, COALESCE(SUM(platform_fee), 0) as gp, COALESCE(SUM(seller_earning), 0) as net')
                ->first();

            return [
                'orders' => (int) ($row->orders ?? 0),
                'gross' => round((float) ($row->gross ?? 0), 2),
                'gp' => round((float) ($row->gp ?? 0), 2),
                'net' => round((float) ($row->net ?? 0), 2),
            ];
        };

        $periods = [
            'today' => ['label' => 'วันนี้', 'data' => $sum(now()->startOfDay())],
            'week' => ['label' => '7 วันล่าสุด', 'data' => $sum(now()->subDays(6)->startOfDay())],
            'month' => ['label' => 'เดือนนี้', 'data' => $sum(now()->startOfMonth())],
            'all' => ['label' => 'ทั้งหมด', 'data' => $sum(null)],
        ];

        // กราฟรายได้สุทธิ 14 วันล่าสุด (วันที่ไม่มีขาย = 0)
        $fromDay = now()->subDays(13)->startOfDay();
        $byDay = $completed()
            ->where('completed_at', '>=', $fromDay)
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as orders, COALESCE(SUM(seller_earning), 0) as net')
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($r) => (string) $r->d);

        $daily = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $fromDay->copy()->addDays($i);
            $key = $day->toDateString();
            $daily[] = [
                'date' => $key,
                'label' => TaladsodWebUi::shortDay($day),
                'orders' => (int) ($byDay[$key]->orders ?? 0),
                'net' => round((float) ($byDay[$key]->net ?? 0), 2),
            ];
        }

        // เงินที่กำลังจะได้ (ออเดอร์ที่ยังไม่จบ): จ่ายผ่าน wallet ระบบถือไว้ / เก็บเงินปลายทาง
        $activeStatuses = [
            FreshMarketOrder::STATUS_PENDING, FreshMarketOrder::STATUS_ACCEPTED, FreshMarketOrder::STATUS_PREPARING,
            FreshMarketOrder::STATUS_READY, FreshMarketOrder::STATUS_DELIVERING, FreshMarketOrder::STATUS_DELIVERED,
        ];
        $pendingRow = FreshMarketOrder::where('seller_id', $seller->id)
            ->whereIn('order_status', $activeStatuses)
            ->selectRaw("COUNT(*) as orders,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN seller_earning ELSE 0 END), 0) as held_net,
                COALESCE(SUM(CASE WHEN payment_method = 'cod' AND payment_status <> 'paid' THEN total_amount ELSE 0 END), 0) as cod_to_collect")
            ->first();
        $pending = [
            'orders' => (int) ($pendingRow->orders ?? 0),
            'held_net' => round((float) ($pendingRow->held_net ?? 0), 2),
            'cod_to_collect' => round((float) ($pendingRow->cod_to_collect ?? 0), 2),
        ];

        $gpDebt = $seller->outstandingGpDebt();
        $gpRate = $this->currentFreshGpRate($seller);
        $gpFree = $this->gpPromoActive();
        $gpFreeUntil = null;
        try {
            $gpFreeUntil = $gpFree ? app(PricingEngine::class)->gpPromoEndsAt() : null;
        } catch (\Throwable $e) {
            $gpFreeUntil = null;
        }

        $walletBalance = round((float) (Wallet::where('user_id', auth()->id())->value('balance') ?? 0), 2);

        $payouts = WalletTransaction::where('user_id', auth()->id())
            ->where('reference_type', FreshMarketService::REF_PAYOUT)
            ->latest('id')
            ->limit(10)
            ->get(['id', 'amount', 'description', 'reference_id', 'created_at']);

        $recentCompleted = $completed()
            ->with('items')
            ->latest('completed_at')
            ->limit(15)
            ->get();

        return view('taladsod.seller-earnings', compact(
            'seller', 'periods', 'daily', 'pending', 'gpDebt', 'gpRate', 'gpFree', 'gpFreeUntil',
            'walletBalance', 'payouts', 'recentCompleted'
        ));
    }

    /**
     * ตั้งค่าร้าน (ที่อยู่ พิกัด เบอร์โทร)
     */
    public function sellerProfile()
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        return view('taladsod.seller-profile', compact('seller'));
    }

    /**
     * บันทึกตั้งค่าร้าน
     */
    public function updateSellerProfile(Request $request): RedirectResponse
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        $validated = $request->validate([
            'shop_name' => 'required|string|max:200',
            'shop_description' => 'nullable|string|max:1000',
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'address' => 'required|string|max:500',
            'province' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'sub_district' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], [
            'latitude.required' => 'กรุณาปักหมุดตำแหน่งร้าน',
            'longitude.required' => 'กรุณาปักหมุดตำแหน่งร้าน',
            'phone.regex' => 'รูปแบบเบอร์โทรไม่ถูกต้อง',
        ] + $this->thaiRuleMessages(), $this->thaiAttributes());

        $seller->update($validated);

        return redirect()->route('taladsod.seller.profile')->with('success', 'บันทึกข้อมูลร้านเรียบร้อยแล้ว');
    }

    /**
     * สมัคร/ต่ออายุสมาชิกรายเดือน (หักจาก wallet)
     */
    public function subscribe(): RedirectResponse
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        return $this->runOrderAction(function () use ($seller) {
            $updated = $this->marketService->subscribeSeller($seller);

            return redirect()->route('taladsod.seller.dashboard')->with(
                'success',
                'ต่ออายุสมาชิกสำเร็จ ใช้งานได้ถึง '.$updated->subscription_expires_at?->format('d/m/Y')
            );
        });
    }

    /**
     * ฟอร์มลงขายสินค้า (Web)
     */
    public function createListing()
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        if (! $seller->canCreateListing()) {
            return redirect()->route('taladsod.seller.dashboard')
                ->with('error', $this->marketService->listingLimitMessage());
        }

        $categories = FreshMarketCategory::active()->orderBy('sort_order')->get();
        $maxCashbackPercent = $this->marketService->maxCashbackPercent($seller);
        // อัตรา GP ตลาดสดตอนนี้ (แสดง "ราคา − GP = รับจริง" บนฟอร์ม)
        $gpRate = $this->currentFreshGpRate($seller);
        $gpFree = $this->gpPromoActive();

        return view('taladsod.create-listing', compact('seller', 'categories', 'maxCashbackPercent', 'gpRate', 'gpFree'));
    }

    /**
     * บันทึกสินค้าใหม่ (รับทั้งชื่อช่อง quantity_available และ quantity จากฟอร์มเดิม)
     */
    public function storeListing(Request $request)
    {
        $seller = $this->currentSeller();

        if (! $seller) {
            return redirect()->route('taladsod.register-seller')->with('info', 'กรุณาสมัครเป็นผู้ขายก่อนค่ะ');
        }

        if (! $request->filled('quantity_available') && $request->filled('quantity')) {
            $request->merge(['quantity_available' => $request->input('quantity')]);
        }

        \App\Services\FreshMarketOptionService::normalizeGroupsInput($request);

        $maxCashback = $this->marketService->maxCashbackPercent($seller);
        // ทำตามสั่ง (track_stock = 0) ไม่ต้องกรอกจำนวน
        $tracksStock = ! $request->has('track_stock') || $request->boolean('track_stock');

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:fresh_market_categories,id',
            'price' => 'required|numeric|min:1|max:1000000',
            'compare_at_price' => 'nullable|numeric|gt:price',
            'unit' => 'required|string|max:50',
            'track_stock' => 'nullable|boolean',
            'quantity_available' => ($tracksStock ? 'required' : 'nullable').'|integer|min:'.($tracksStock ? 1 : 0).'|max:100000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_organic' => 'boolean',
            'freshness_level' => 'nullable|string|in:สด,สดมาก,ผลิตวันนี้',
            'cashback_percentage' => 'nullable|numeric|min:0|max:'.$maxCashback,
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
        ] + \App\Services\FreshMarketOptionService::groupsRules(), [
            'quantity_available.required' => 'กรุณาระบุจำนวนสินค้าที่มีขาย',
            'quantity_available.min' => 'จำนวนสินค้าต้องมีอย่างน้อย 1',
            'cashback_percentage.max' => 'แคชแบ็คตั้งได้ไม่เกิน '.$maxCashback.'%',
            'compare_at_price.gt' => 'ราคาก่อนลดต้องมากกว่าราคาขาย',
            'images.max' => 'อัปโหลดรูปสินค้าได้ไม่เกิน 5 รูป',
            'images.*.image' => 'รูปสินค้าต้องเป็นไฟล์รูปภาพ',
            'images.*.max' => 'รูปสินค้าแต่ละรูปต้องไม่เกิน 5MB',
        ] + \App\Services\FreshMarketOptionService::validationMessages() + $this->thaiRuleMessages(), $this->thaiAttributes());

        $optionGroups = $validated['option_groups'] ?? [];
        unset($validated['option_groups']);
        $validated['track_stock'] = $tracksStock;

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

        try {
            // สินค้า + กลุ่มตัวเลือกบันทึกพร้อมกัน
            $listing = \Illuminate\Support\Facades\DB::transaction(function () use ($seller, $validated, $optionGroups, $request) {
                $listing = $this->marketService->createListing($seller, $validated);

                if (! empty($optionGroups)) {
                    app(\App\Services\FreshMarketOptionService::class)->syncGroups($listing, $optionGroups, $request);
                }

                return $listing;
            });
        } catch (FreshMarketException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('FreshMarket web: ลงขายล้มเหลว', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'ลงขายไม่สำเร็จ กรุณาลองใหม่');
        }

        return redirect()->route('taladsod.seller.dashboard')
            ->with('success', "ลงขาย \"{$listing->title}\" สำเร็จ! 🎉");
    }

    /**
     * ฟอร์มแก้ไขสินค้า
     */
    public function editListing(FreshMarketListing $listing)
    {
        $seller = $this->currentSeller();

        if (! $seller || (int) $listing->seller_id !== (int) $seller->id) {
            abort(403);
        }

        $categories = FreshMarketCategory::active()->orderBy('sort_order')->get();
        $maxCashbackPercent = $this->marketService->maxCashbackPercent($seller);
        // กลุ่มตัวเลือกปัจจุบัน (รวมที่ปิดขาย) สำหรับเติมฟอร์มแก้ไข
        $listing->load('optionGroups.options');
        $optionGroups = app(\App\Services\FreshMarketOptionService::class)->groupsForApi($listing, true);
        $gpRate = $this->currentFreshGpRate($seller);
        $gpFree = $this->gpPromoActive();

        return view('taladsod.edit-listing', compact('listing', 'seller', 'categories', 'maxCashbackPercent', 'optionGroups', 'gpRate', 'gpFree'));
    }

    /**
     * อัพเดทสินค้า (เติมสต็อกแล้ว sold_out จะกลับมาขายอัตโนมัติ — ดู FreshMarketListing::booted)
     */
    public function updateListing(Request $request, FreshMarketListing $listing)
    {
        $seller = $this->currentSeller();

        if (! $seller || (int) $listing->seller_id !== (int) $seller->id) {
            abort(403);
        }

        if (! $request->filled('quantity_available') && $request->filled('quantity')) {
            $request->merge(['quantity_available' => $request->input('quantity')]);
        }

        $maxCashback = $this->marketService->maxCashbackPercent($seller);

        \App\Services\FreshMarketOptionService::normalizeGroupsInput($request);
        $tracksStock = $request->has('track_stock') ? $request->boolean('track_stock') : $listing->tracksStock();

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:fresh_market_categories,id',
            'price' => 'required|numeric|min:1|max:1000000',
            'compare_at_price' => 'nullable|numeric|gt:price',
            'unit' => 'required|string|max:50',
            'track_stock' => 'nullable|boolean',
            'quantity_available' => ($tracksStock ? 'required' : 'nullable').'|integer|min:0|max:100000',
            'is_organic' => 'boolean',
            'is_available' => 'boolean',
            'freshness_level' => 'nullable|string|in:สด,สดมาก,ผลิตวันนี้',
            'cashback_percentage' => 'nullable|numeric|min:0|max:'.$maxCashback,
            // ฟอร์มที่มีส่วนตัวเลือกต้องส่ง option_groups_present=1 (ส่งกลุ่มว่าง = ลบตัวเลือกทั้งหมด)
            'option_groups_present' => 'nullable|boolean',
            // รูปสินค้า: เพิ่มรูปใหม่ / ลบรูปเดิม / เลือกรูปหลัก (รวมไม่เกิน 5 รูป)
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
            'remove_images' => 'nullable|array|max:10',
            'remove_images.*' => 'string|max:255',
            'main_image' => 'nullable|string|max:255',
        ] + \App\Services\FreshMarketOptionService::groupsRules(), [
            'cashback_percentage.max' => 'แคชแบ็คตั้งได้ไม่เกิน '.$maxCashback.'%',
            'compare_at_price.gt' => 'ราคาก่อนลดต้องมากกว่าราคาขาย',
            'images.max' => 'อัปโหลดรูปสินค้าได้ไม่เกิน 5 รูป',
            'images.*.image' => 'รูปสินค้าต้องเป็นไฟล์รูปภาพ',
            'images.*.max' => 'รูปสินค้าแต่ละรูปต้องไม่เกิน 5MB',
        ] + \App\Services\FreshMarketOptionService::validationMessages() + $this->thaiRuleMessages(), $this->thaiAttributes());

        $syncGroups = $request->has('option_groups') || $request->boolean('option_groups_present');
        $optionGroups = $validated['option_groups'] ?? [];
        unset($validated['option_groups'], $validated['option_groups_present']);
        $validated['track_stock'] = $tracksStock;

        // รูปสินค้า: รูปเดิมที่เหลือ + รูปใหม่ (ลบได้เฉพาะรูปของสินค้านี้เอง)
        $currentImages = array_values(array_filter((array) ($listing->images ?? []), fn ($u) => is_string($u) && $u !== ''));
        $removeImages = array_values(array_intersect($currentImages, (array) ($validated['remove_images'] ?? [])));
        $keptImages = array_values(array_diff($currentImages, $removeImages));
        $files = $request->file('images');
        $newFiles = is_array($files) ? array_values(array_filter($files)) : ($files ? [$files] : []);
        $mainChoice = $validated['main_image'] ?? null;
        unset($validated['images'], $validated['remove_images'], $validated['main_image']);

        if (count($keptImages) + count($newFiles) > 5) {
            return back()->withInput()->withErrors(['images' => 'รูปสินค้ารวมได้ไม่เกิน 5 รูป กรุณาลบรูปเดิมก่อนเพิ่มรูปใหม่']);
        }

        if (($validated['quantity_available'] ?? null) === null) {
            unset($validated['quantity_available']);
        }

        // สินค้าที่แอดมินระงับ ร้านเปิดเองไม่ได้
        if ($listing->status === 'suspended') {
            unset($validated['is_available']);
        }

        $uploaded = [];

        try {
            foreach ($newFiles as $file) {
                $uploaded[] = '/storage/'.$file->store('fresh-market', 'public');
            }

            $finalImages = array_values(array_merge($keptImages, $uploaded));
            $currentMain = $listing->main_image_url;
            $validated['images'] = $finalImages;
            $validated['main_image_url'] = match (true) {
                $mainChoice !== null && in_array($mainChoice, $finalImages, true) => $mainChoice,
                $currentMain !== null && in_array($currentMain, $finalImages, true) => $currentMain,
                default => $finalImages[0] ?? null,
            };

            DB::transaction(function () use ($listing, $validated, $syncGroups, $optionGroups, $request) {
                $listing->update($validated);

                if ($syncGroups) {
                    app(\App\Services\FreshMarketOptionService::class)->syncGroups($listing, $optionGroups, $request);
                }
            });
        } catch (FreshMarketException $e) {
            $this->deletePublicImages($uploaded);

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->deletePublicImages($uploaded);
            Log::error('FreshMarket web: แก้ไขสินค้าล้มเหลว', ['listing_id' => $listing->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'บันทึกสินค้าไม่สำเร็จ กรุณาลองใหม่');
        }

        // บันทึกสำเร็จแล้วจึงลบไฟล์รูปที่เอาออก
        $this->deletePublicImages($removeImages);

        return redirect()->route('taladsod.seller.listings')
            ->with('success', 'บันทึกสินค้า "'.$listing->title.'" สำเร็จ');
    }

    /**
     * ลบสินค้า (แล้วนับโควต้าลงขายใหม่)
     */
    public function destroyListing(Request $request, FreshMarketListing $listing)
    {
        $seller = $this->currentSeller();

        if (! $seller || (int) $listing->seller_id !== (int) $seller->id) {
            abort(403);
        }

        // ลบจากหน้ารายการสินค้า → กลับหน้ารายการสินค้า
        $backRoute = $request->input('return_to') === 'listings' ? 'taladsod.seller.listings' : 'taladsod.seller.dashboard';

        // รวมออเดอร์หลายรายการที่มีสินค้านี้อยู่ด้วย
        $hasActiveOrders = FreshMarketOrder::active()
            ->where(function ($q) use ($listing) {
                $q->where('listing_id', $listing->id)
                    ->orWhereIn('id', \Illuminate\Support\Facades\DB::table('fresh_market_order_items')
                        ->select('order_id')
                        ->where('listing_id', $listing->id));
            })
            ->exists();

        if ($hasActiveOrders) {
            return redirect()->route($backRoute)
                ->with('error', 'สินค้านี้มีออเดอร์ที่ยังไม่เสร็จ กรุณาจัดการออเดอร์ให้เสร็จก่อนลบ');
        }

        $listing->delete();
        $seller->refreshStats();

        return redirect()->route($backRoute)
            ->with('success', 'ลบสินค้าสำเร็จ');
    }

    /**
     * ผู้ใช้ที่ยังไม่ login กดปุ่มที่ต้อง login (สั่งซื้อ/ติดตามร้าน) → ผ่านหน้า login แล้วกลับมาหน้าเดิม
     *
     * GET /taladsod/login-continue?to=/taladsod/listing/xxx (รับเฉพาะ path ภายใต้ /taladsod กัน open redirect)
     */
    public function loginContinue(Request $request): RedirectResponse
    {
        $to = is_string($request->query('to')) ? (string) $request->query('to') : '';

        if (! preg_match('#^/taladsod(/[A-Za-z0-9._~\-/]*)?$#', $to) || str_contains($to, '//') || str_contains($to, '..')) {
            $to = '/taladsod';
        }

        return redirect()->to($to);
    }

    // ===== Helpers =====

    /**
     * แบนเนอร์แคมเปญสำหรับหน้าเว็บ (รูปไม่มีตัวหนังสือ — เว็บวางข้อความทับ)
     *
     * @return array<int, array<string, mixed>> MobileBanner::toAppApi() + href (ลิงก์ปลายทางบนเว็บ)
     */
    protected function webBanners(string $placement): array
    {
        try {
            $rows = app(AppBannerService::class)->activeFor($placement);
        } catch (\Throwable $e) {
            Log::warning('FreshMarket web: อ่านแบนเนอร์ล้มเหลว', ['placement' => $placement, 'error' => $e->getMessage()]);

            return [];
        }

        $banners = [];
        foreach ($rows as $row) {
            if (empty($row['image_url'])) {
                continue;
            }

            $banners[] = array_merge($row, ['href' => TaladsodWebUi::bannerHref($row)]);
        }

        return array_slice($banners, 0, 8);
    }

    /**
     * ร้านที่เปิดอยู่ตอนนี้ (แสดงก่อนผู้ซื้อแชร์ตำแหน่ง) — ร้านที่ส่งตำแหน่งสด/เพิ่งเปิดขึ้นก่อน
     *
     * @return array<int, array<string, mixed>>
     */
    protected function openShopCards(int $limit = 12): array
    {
        try {
            $presence = app(FreshMarketShopPresenceService::class);
            $viewer = auth()->user();

            $shops = FreshMarketSeller::active()
                ->when(! FreshMarketListing::autoApproveSellers(), fn ($q) => $q->where('is_verified', true))
                ->where('total_listings', '>', 0)
                ->where('is_open', true)
                ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()))
                ->orderByDesc('live_location_sharing')
                ->orderByDesc('opened_at')
                ->limit($limit * 2)
                ->get()
                ->filter(fn (FreshMarketSeller $s) => $s->isOpenNow())
                ->take($limit)
                ->values();

            $followed = $viewer
                ? \App\Models\FreshMarketShopFollower::where('user_id', $viewer->id)
                    ->whereIn('seller_id', $shops->pluck('id'))
                    ->pluck('seller_id')->map(fn ($id) => (int) $id)->all()
                : [];

            return $shops->map(fn (FreshMarketSeller $s) => array_merge(
                $presence->shopCard($s, null, in_array((int) $s->id, $followed, true)),
                ['url' => route('taladsod.seller', $s->id), 'province' => $s->province]
            ))->all();
        } catch (\Throwable $e) {
            Log::warning('FreshMarket web: อ่านร้านที่เปิดอยู่ล้มเหลว', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * โปรฯ "GP ฟรีช่วงเปิดตัว" ยังมีผลอยู่หรือไม่ (อ่านไม่ได้ = ถือว่าไม่มีโปรฯ)
     */
    protected function gpPromoActive(): bool
    {
        try {
            return app(PricingEngine::class)->gpPromoActive();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * อัตรา GP ตลาดสดที่ร้านนี้โดนหักตอนนี้ (%)
     */
    protected function currentFreshGpRate(FreshMarketSeller $seller): float
    {
        try {
            $probe = new FreshMarketListing(['seller_id' => $seller->id]);
            $probe->setRelation('seller', $seller);

            return round((float) $this->marketService->gpRateFor($probe), 2);
        } catch (\Throwable $e) {
            Log::warning('FreshMarket web: อ่านอัตรา GP ล้มเหลว', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);

            return 0.0;
        }
    }

    /**
     * ลบไฟล์รูปสินค้าบน disk public (เฉพาะไฟล์ในโฟลเดอร์ fresh-market ของเราเอง)
     *
     * ลบเฉพาะไฟล์ที่ไม่มีสินค้า/ตัวเลือก/รายการในออเดอร์ไหนใช้แล้ว — รูปในออเดอร์เก่าและใบเสร็จไม่หาย
     *
     * @param  array<int, string>  $urls  รูปแบบ /storage/fresh-market/xxx.jpg
     */
    protected function deletePublicImages(array $urls): void
    {
        $images = app(\App\Services\FreshMarketOptionService::class);

        foreach ($urls as $url) {
            if (is_string($url)) {
                $images->deleteListingImageIfUnused($url);
            }
        }
    }

    /**
     * ข้อความ validation ภาษาไทยแบบทั่วไป (ใช้ต่อท้ายข้อความเฉพาะช่อง — ข้อความเฉพาะช่องมาก่อนเสมอ)
     *
     * @return array<string, string|array<string, string>>
     */
    protected function thaiRuleMessages(): array
    {
        return [
            'required' => 'กรุณากรอก:attribute',
            'required_if' => 'กรุณากรอก:attribute',
            'accepted' => 'กรุณายอมรับ:attribute',
            'string' => ':attributeไม่ถูกต้อง',
            'numeric' => ':attributeต้องเป็นตัวเลข',
            'integer' => ':attributeต้องเป็นจำนวนเต็ม',
            'boolean' => ':attributeไม่ถูกต้อง',
            'array' => ':attributeไม่ถูกต้อง',
            'exists' => 'กรุณาเลือก:attributeที่มีอยู่ในระบบ',
            'in' => ':attributeไม่ถูกต้อง',
            'image' => ':attributeต้องเป็นไฟล์รูปภาพ',
            'regex' => 'รูปแบบ:attributeไม่ถูกต้อง',
            'between' => [
                'numeric' => ':attributeไม่ถูกต้อง',
                'string' => ':attributeไม่ถูกต้อง',
                'array' => ':attributeไม่ถูกต้อง',
                'file' => ':attributeไม่ถูกต้อง',
            ],
            'gt' => [
                'numeric' => ':attributeต้องมากกว่า :value',
                'string' => ':attributeไม่ถูกต้อง',
                'array' => ':attributeไม่ถูกต้อง',
                'file' => ':attributeไม่ถูกต้อง',
            ],
            'min' => [
                'numeric' => ':attributeต้องไม่น้อยกว่า :min',
                'string' => ':attributeต้องยาวอย่างน้อย :min ตัวอักษร',
                'array' => ':attributeต้องมีอย่างน้อย :min รายการ',
                'file' => ':attributeมีขนาดเล็กเกินไป',
            ],
            'max' => [
                'numeric' => ':attributeต้องไม่เกิน :max',
                'string' => ':attributeยาวได้ไม่เกิน :max ตัวอักษร',
                'array' => ':attributeมีได้ไม่เกิน :max รายการ',
                'file' => ':attributeต้องมีขนาดไม่เกิน :max KB',
            ],
        ];
    }

    /**
     * ชื่อช่องภาษาไทยสำหรับข้อความ validation
     *
     * @return array<string, string>
     */
    protected function thaiAttributes(): array
    {
        return [
            'title' => 'ชื่อสินค้า',
            'description' => 'รายละเอียดสินค้า',
            'category_id' => 'หมวดหมู่',
            'price' => 'ราคาขาย',
            'compare_at_price' => 'ราคาก่อนลด',
            'unit' => 'หน่วยขาย',
            'track_stock' => 'การตัดสต็อก',
            'quantity_available' => 'จำนวนที่มีขาย',
            'is_organic' => 'สินค้าอินทรีย์',
            'is_available' => 'สถานะเปิดขาย',
            'freshness_level' => 'ระดับความสด',
            'cashback_percentage' => 'เงินคืน',
            'images' => 'รูปสินค้า',
            'images.*' => 'รูปสินค้า',
            'shop_name' => 'ชื่อร้าน',
            'shop_description' => 'คำอธิบายร้าน',
            'phone' => 'เบอร์โทร',
            'address' => 'ที่อยู่ร้าน',
            'province' => 'จังหวัด',
            'district' => 'อำเภอ/เขต',
            'sub_district' => 'ตำบล/แขวง',
            'latitude' => 'พิกัดร้าน',
            'longitude' => 'พิกัดร้าน',
            'agree_terms' => 'เงื่อนไขการขาย',
            'option_groups' => 'กลุ่มตัวเลือก',
            'option_groups.*.name' => 'ชื่อกลุ่มตัวเลือก',
            'option_groups.*.min_select' => 'จำนวนขั้นต่ำที่ต้องเลือก',
            'option_groups.*.max_select' => 'จำนวนสูงสุดที่เลือกได้',
            'option_groups.*.options' => 'ตัวเลือก',
            'option_groups.*.options.*.name' => 'ชื่อตัวเลือก',
            'option_groups.*.options.*.price_delta' => 'ราคาเพิ่มของตัวเลือก',
            'option_groups.*.options.*.image' => 'รูปตัวเลือก',
        ];
    }

    /**
     * ร้านของผู้ใช้ที่ล็อกอินอยู่
     */
    protected function currentSeller(): ?FreshMarketSeller
    {
        return auth()->check()
            ? FreshMarketSeller::where('user_id', auth()->id())->first()
            : null;
    }

    /**
     * ลิงก์แนะนำเพื่อนของร้าน (ใช้ซ้ำได้ — แต่ละคนที่กดจะได้แถว referral ของตัวเอง)
     *
     * @return array{token: string, web_url: string, line_url: ?string}|null
     */
    protected function referralLinksFor(FreshMarketSeller $seller): ?array
    {
        try {
            $referral = FreshMarketReferral::masterForSeller($seller);
            $basicId = ltrim((string) Setting::get('fresh_market.line_basic_id', ''), '@');

            return [
                'token' => $referral->referral_token,
                'web_url' => route('taladsod.landing.buyer', ['ref' => $referral->referral_token]),
                'line_url' => $basicId !== '' ? $referral->generateDeepLink($basicId) : null,
            ];
        } catch (\Throwable $e) {
            Log::warning('FreshMarket: สร้างลิงก์แนะนำล้มเหลว', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ผูกผู้ซื้อกับลิงก์แนะนำที่เปิดมาจากหน้า landing (?ref=) — ทำครั้งเดียวแล้วล้าง session
     */
    protected function claimSessionReferral(): void
    {
        $token = session('taladsod_referral_token');

        if (! $token || ! auth()->check()) {
            return;
        }

        try {
            FreshMarketReferral::claim((string) $token, auth()->user()->line_user_id ?? null, auth()->user());
        } catch (\Throwable $e) {
            Log::warning('FreshMarket: ผูกลิงก์แนะนำล้มเหลว', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);
        } finally {
            session()->forget('taladsod_referral_token');
        }
    }

    /**
     * รัน action ที่อาจโยน FreshMarketException แล้วแปลงเป็น redirect พร้อมข้อความไทย
     */
    protected function runOrderAction(callable $callback): RedirectResponse
    {
        try {
            return $callback();
        } catch (FreshMarketException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('FreshMarket web: ทำรายการออเดอร์ล้มเหลว', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);

            return back()->with('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        }
    }
}
