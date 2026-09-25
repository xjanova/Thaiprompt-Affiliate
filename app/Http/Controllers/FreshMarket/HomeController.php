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
use App\Services\FreshMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // สินค้าแนะนำ (Featured)
        $featuredListings = FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->where('is_featured', true)
            ->with('seller:id,shop_name,rating_average')
            ->latest()
            ->limit(8)
            ->get();

        // สินค้าใหม่ล่าสุด
        $latestListings = FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->with('seller:id,shop_name,rating_average')
            ->latest()
            ->limit(12)
            ->get();

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
            'serviceProviders'
        ));
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
                ->with('seller:id,shop_name,rating_average')
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
            ->with(['seller', 'category'])
            ->firstOrFail();

        // สินค้าของร้านที่ถูกซ่อน/ระงับ → เจ้าของร้านยังดูได้ คนอื่น 404
        $isOwner = auth()->check() && (int) ($listing->seller?->user_id) === (int) auth()->id();
        if (! $isOwner && ! $listing->sellerIsVisible()) {
            abort(404);
        }

        $listing->incrementViews();

        // รีวิวของสินค้านี้ (จาก orders ที่มี buyer_rating)
        $reviews = FreshMarketOrder::where('listing_id', $listing->id)
            ->withBuyerReview()
            ->with('buyer:id,name')
            ->latest()
            ->limit(10)
            ->get();

        $reviewStats = FreshMarketOrder::where('listing_id', $listing->id)
            ->withBuyerReview()
            ->selectRaw('AVG(buyer_rating) as avg_rating, COUNT(*) as total')
            ->first();

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
            ->with('seller:id,shop_name,rating_average')
            ->limit(6)
            ->get();

        return view('taladsod.listing', compact(
            'listing', 'relatedListings', 'reviews', 'reviewStats',
            'deliveryBaseRate', 'deliveryPerKm', 'paymentMethods', 'riderEnabled', 'isOwner'
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

        $listings = FreshMarketListing::visibleToBuyers()
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
                'latitude' => $l->latitude !== null ? (float) $l->latitude : null,
                'longitude' => $l->longitude !== null ? (float) $l->longitude : null,
                'distance_km' => round((float) ($l->distance_km ?? 0), 1),
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
            ->with('seller:id,shop_name,rating_average');

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
            ->with(['seller:id,shop_name', 'listing:id,title,slug,unit,main_image_url,images'])
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

        $order->load(['seller', 'listing', 'riderJob.rider']);
        $allowedActions = $order->allowedActions('buyer');
        $canReview = $order->canBeReviewed();

        return view('taladsod.order-detail', compact('order', 'allowedActions', 'canReview'));
    }

    /**
     * สั่งซื้อสินค้าจากหน้ารายละเอียด
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'listing_id' => 'required|integer|exists:fresh_market_listings,id',
            'quantity' => 'required|integer|min:1|max:999',
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
        ]);

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
            ->with(['buyer:id,name', 'listing:id,title,unit'])
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

        return view('taladsod.seller-dashboard', compact(
            'seller', 'recentOrders', 'stats', 'gpDebt', 'maxCashbackPercent', 'referralLinks', 'subscription', 'pendingOrders'
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
            ->with(['buyer:id,name', 'listing:id,title,slug,unit,main_image_url,images', 'riderJob'])
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

        $order->load(['buyer', 'listing', 'riderJob.rider']);
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
        ], [
            'reason.required_if' => 'กรุณาระบุเหตุผลที่ยกเลิก',
        ]);

        return $this->runOrderAction(function () use ($order, $validated) {
            $this->marketService->applyAction($order, $validated['action'], 'seller', auth()->user(), [
                'reason' => $validated['reason'] ?? '',
            ]);

            return redirect()->route('taladsod.seller.orders.show', $order)
                ->with('success', FreshMarketOrder::actionLabel($validated['action']).'เรียบร้อยแล้ว');
        });
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
        ]);

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

        return view('taladsod.create-listing', compact('seller', 'categories', 'maxCashbackPercent'));
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

        $maxCashback = $this->marketService->maxCashbackPercent($seller);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:fresh_market_categories,id',
            'price' => 'required|numeric|min:1|max:1000000',
            'compare_at_price' => 'nullable|numeric|gt:price',
            'unit' => 'required|string|max:50',
            'quantity_available' => 'required|integer|min:1|max:100000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_organic' => 'boolean',
            'freshness_level' => 'nullable|string|in:สด,สดมาก,ผลิตวันนี้',
            'cashback_percentage' => 'nullable|numeric|min:0|max:'.$maxCashback,
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
        ], [
            'quantity_available.required' => 'กรุณาระบุจำนวนสินค้าที่มีขาย',
            'quantity_available.min' => 'จำนวนสินค้าต้องมีอย่างน้อย 1',
            'cashback_percentage.max' => 'แคชแบ็คตั้งได้ไม่เกิน '.$maxCashback.'%',
        ]);

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
            $listing = $this->marketService->createListing($seller, $validated);
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

        return view('taladsod.edit-listing', compact('listing', 'seller', 'categories', 'maxCashbackPercent'));
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

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:fresh_market_categories,id',
            'price' => 'required|numeric|min:1|max:1000000',
            'compare_at_price' => 'nullable|numeric|gt:price',
            'unit' => 'required|string|max:50',
            'quantity_available' => 'required|integer|min:0|max:100000',
            'is_organic' => 'boolean',
            'is_available' => 'boolean',
            'freshness_level' => 'nullable|string|in:สด,สดมาก,ผลิตวันนี้',
            'cashback_percentage' => 'nullable|numeric|min:0|max:'.$maxCashback,
        ], [
            'cashback_percentage.max' => 'แคชแบ็คตั้งได้ไม่เกิน '.$maxCashback.'%',
        ]);

        // สินค้าที่แอดมินระงับ ร้านเปิดเองไม่ได้
        if ($listing->status === 'suspended') {
            unset($validated['is_available']);
        }

        $listing->update($validated);

        return redirect()->route('taladsod.seller.dashboard')
            ->with('success', 'อัพเดทสินค้าสำเร็จ');
    }

    /**
     * ลบสินค้า (แล้วนับโควต้าลงขายใหม่)
     */
    public function destroyListing(FreshMarketListing $listing)
    {
        $seller = $this->currentSeller();

        if (! $seller || (int) $listing->seller_id !== (int) $seller->id) {
            abort(403);
        }

        $hasActiveOrders = FreshMarketOrder::where('listing_id', $listing->id)->active()->exists();

        if ($hasActiveOrders) {
            return redirect()->route('taladsod.seller.dashboard')
                ->with('error', 'สินค้านี้มีออเดอร์ที่ยังไม่เสร็จ กรุณาจัดการออเดอร์ให้เสร็จก่อนลบ');
        }

        $listing->delete();
        $seller->refreshStats();

        return redirect()->route('taladsod.seller.dashboard')
            ->with('success', 'ลบสินค้าสำเร็จ');
    }

    // ===== Helpers =====

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
