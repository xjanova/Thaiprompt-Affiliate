<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\FreshMarketException;
use App\Http\Controllers\Controller;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Setting;
use App\Services\FreshMarketService;
use App\Support\FreshMarketValidationText;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Fresh Market API Controller (ตลาดสดไทยพร๊อม)
 *
 * รูปแบบตอบกลับ: {"success": bool, "message": "ข้อความไทย", "data": ...}
 * ผิดพลาด: {"success": false, "message": "...", "code": "UPPER_SNAKE"} + HTTP status ที่ถูกต้อง
 * ตัวเลขทุกตัวเป็น JSON number (ไม่ใช่ string ทศนิยม)
 *
 * ตรรกะเงิน/สถานะทั้งหมดอยู่ใน FreshMarketService — ที่นี่แค่ตรวจสิทธิ์ + แปลงผล
 */
class FreshMarketApiController extends Controller
{
    protected FreshMarketService $marketService;

    public function __construct()
    {
        $this->marketService = new FreshMarketService;
    }

    // ===== Public Endpoints (ไม่ต้อง auth) =====

    /**
     * GET /api/v1/fresh-market/config
     * ค่าตั้งที่แอปต้องรู้ก่อนแสดงฟอร์มสั่งซื้อ
     */
    public function config(): JsonResponse
    {
        $settings = FreshMarketSetting::getSettings();

        return $this->ok([
            'payment_methods' => $this->marketService->availablePaymentMethods(),
            'rider_enabled' => (bool) $settings->rider_enabled,
            'cashback_enabled' => (bool) $settings->cashback_enabled,
            'rider_max_distance_km' => (float) Setting::get('rider.max_distance_km', 15),
            'pending_expiry_minutes' => (int) Setting::get('fresh_market.pending_expiry_minutes', 30),
            'auto_complete_hours' => (int) Setting::get('fresh_market.auto_complete_hours', 24),
            'default_search_radius_km' => (float) $settings->default_search_radius_km,
            'max_search_radius_km' => (float) $settings->max_search_radius_km,
            'brand_name' => $settings->brand_name,
        ]);
    }

    /**
     * GET /api/v1/fresh-market/categories
     */
    public function categories(): JsonResponse
    {
        $categories = FreshMarketCategory::active()
            ->root()
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return $this->ok($categories->map(fn ($c) => [
            'id' => (int) $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'icon' => $c->icon,
            'image_url' => $c->image_url,
            'children' => $c->children->map(fn ($child) => [
                'id' => (int) $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'icon' => $child->icon,
            ])->values(),
        ])->values());
    }

    /**
     * GET /api/v1/fresh-market/listings
     * query: lat, lng, radius, q, category_id (id หรือ slug), min_price, max_price, organic, sort, per_page
     */
    public function listings(Request $request): JsonResponse
    {
        $query = FreshMarketListing::visibleToBuyers()->inStock()
            ->with('seller:'.FreshMarketSeller::SUMMARY_COLUMNS, 'category:id,name,icon')
            ->withCount('optionGroups');

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

        $category = $request->get('category_id', $request->get('category'));
        if ($category !== null && $category !== '') {
            $query->inCategory((string) $category);
        }

        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->priceRange(
                $request->filled('min_price') ? $request->float('min_price') : null,
                $request->filled('max_price') ? $request->float('max_price') : null
            );
        }

        if ($request->boolean('organic')) {
            $query->where('is_organic', true);
        }

        match ($request->get('sort', 'newest')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('order_count', 'desc'),
            'distance' => null,
            default => $query->latest(),
        };

        $perPage = max(1, min($request->integer('per_page', 20), 50));
        $listings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'ดึงรายการสินค้าสำเร็จ',
            'data' => collect($listings->items())->map(fn ($l) => $this->listingSummary($l))->values(),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/fresh-market/listings/{id}
     */
    public function showListing(int $id): JsonResponse
    {
        $listing = FreshMarketListing::with(['seller', 'category', 'optionGroups.options'])->find($id);

        if (! $listing || ! $listing->sellerIsVisible()) {
            return $this->error('LISTING_NOT_FOUND', 'ไม่พบสินค้า', 404);
        }

        $listing->incrementViews();

        $related = FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->where('id', '!=', $listing->id)
            ->where(function ($q) use ($listing) {
                $q->where('seller_id', $listing->seller_id)
                    ->orWhere('category_id', $listing->category_id);
            })
            ->with('seller:id,shop_name')
            ->limit(6)
            ->get();

        $data = array_merge($this->listingSummary($listing), [
            'tags' => $listing->tags,
            'delivery_radius_km' => (float) $listing->delivery_radius_km,
            'cashback_percentage' => (float) $listing->cashback_percentage,
            'view_count' => (int) $listing->view_count,
            'order_count' => (int) $listing->order_count,
            'is_available_for_purchase' => $listing->isAvailableForPurchase(),
            // กลุ่มตัวเลือก (รวมตัวเลือกที่หมดชั่วคราว is_available=false ให้แอปแสดงเป็นปุ่มปิด)
            'option_groups' => app(\App\Services\FreshMarketOptionService::class)->groupsForApi($listing, true),
            'seller' => $listing->seller ? [
                'id' => (int) $listing->seller->id,
                'shop_name' => $listing->seller->shop_name,
                'shop_description' => $listing->seller->shop_description,
                'shop_image' => $listing->seller->shop_image,
                'rating_average' => (float) $listing->seller->rating_average,
                'rating_count' => (int) $listing->seller->rating_count,
                'total_sales' => (int) $listing->seller->total_sales,
                'latitude' => $listing->seller->latitude !== null ? (float) $listing->seller->latitude : null,
                'longitude' => $listing->seller->longitude !== null ? (float) $listing->seller->longitude : null,
                'province' => $listing->seller->province,
                'is_verified' => (bool) $listing->seller->is_verified,
                'user_id' => (int) $listing->seller->user_id,
                // ร้านเคลื่อนที่: พิกัดด้านบน = ตำแหน่งตอนเปิดร้านเท่านั้น (ปิดอยู่ = null)
                'is_mobile' => $listing->seller->isMobileShop(),
                'is_open' => $listing->seller->isOpenNow(),
                'presence' => $listing->seller->presencePayload(),
            ] : null,
        ]);

        // ร้านเคลื่อนที่: ไม่เปิดเผยที่อยู่ที่ลงทะเบียน (อาจเป็นบ้าน) — ใช้ตำแหน่งตอนเปิดร้าน
        if ($listing->seller && $listing->seller->isMobileShop()) {
            $location = $listing->seller->publicLocation();
            $data['seller']['latitude'] = $location['latitude'] ?? null;
            $data['seller']['longitude'] = $location['longitude'] ?? null;
        }

        return response()->json([
            'success' => true,
            'message' => 'ดึงข้อมูลสินค้าสำเร็จ',
            'data' => $data,
            'related' => $related->map(fn ($r) => [
                'id' => (int) $r->id,
                'slug' => $r->slug,
                'title' => $r->title,
                'price' => (float) $r->price,
                'unit' => $r->unit,
                'main_image_url' => $r->primary_image,
                'shop_name' => $r->seller?->shop_name,
            ])->values(),
        ]);
    }

    /**
     * GET /api/v1/fresh-market/nearby?lat&lng&radius
     */
    public function nearby(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $listings = $this->marketService->searchListings(
            (float) $data['lat'],
            (float) $data['lng'],
            isset($data['radius']) ? (float) $data['radius'] : 10.0
        );

        return response()->json([
            'success' => true,
            'message' => 'ดึงสินค้าใกล้คุณสำเร็จ',
            'data' => $listings->map(fn ($l) => $this->listingSummary($l))->values(),
            'count' => $listings->count(),
        ]);
    }

    /**
     * GET /api/v1/fresh-market/sellers/{id}
     */
    public function showSeller(int $id): JsonResponse
    {
        $seller = FreshMarketSeller::find($id);

        if (! $seller || ! $seller->isVisibleToBuyers()) {
            return $this->error('SELLER_NOT_FOUND', 'ไม่พบข้อมูลผู้ขาย', 404);
        }

        $listings = $seller->listings()
            ->active()
            ->inStock()
            ->with('category:id,name,icon')
            ->withCount('optionGroups')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'ดึงข้อมูลร้านสำเร็จ',
            'data' => [
                'id' => (int) $seller->id,
                'shop_name' => $seller->shop_name,
                'shop_description' => $seller->shop_description,
                'shop_image' => $seller->shop_image,
                'rating_average' => (float) $seller->rating_average,
                'rating_count' => (int) $seller->rating_count,
                'total_sales' => (int) $seller->total_sales,
                // ร้านเคลื่อนที่ = ตำแหน่งตอนเปิดร้านเท่านั้น (ปิดอยู่ = null) · ร้านประจำ = ที่อยู่ร้าน
                'latitude' => $seller->isMobileShop()
                    ? ($seller->publicLocation()['latitude'] ?? null)
                    : ($seller->latitude !== null ? (float) $seller->latitude : null),
                'longitude' => $seller->isMobileShop()
                    ? ($seller->publicLocation()['longitude'] ?? null)
                    : ($seller->longitude !== null ? (float) $seller->longitude : null),
                'province' => $seller->province,
                'is_verified' => (bool) $seller->is_verified,
                'is_mobile' => $seller->isMobileShop(),
                'is_open' => $seller->isOpenNow(),
                'presence' => $seller->presencePayload(),
            ],
            'listings' => $listings->map(fn ($l) => [
                'id' => (int) $l->id,
                'slug' => $l->slug,
                'title' => $l->title,
                'price' => (float) $l->price,
                'unit' => $l->unit,
                'main_image_url' => $l->primary_image,
                'is_organic' => (bool) $l->is_organic,
                'category' => $l->category?->name,
                'track_stock' => $l->tracksStock(),
                'max_order_quantity' => (int) $l->max_order_quantity,
                'has_options' => (int) ($l->option_groups_count ?? 0) > 0,
            ])->values(),
        ]);
    }

    // ===== Auth: ผู้ซื้อ =====

    /**
     * GET /api/v1/fresh-market/delivery-quote?listing_id&latitude&longitude&quantity
     */
    public function deliveryQuote(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'listing_id' => 'required|integer',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'quantity' => 'nullable|integer|min:1|max:999',
            'option_ids' => 'nullable|array|max:300',
            'option_ids.*' => 'integer|min:1',
        ]);

        $listing = FreshMarketListing::visibleToBuyers()->with('seller')->find($data['listing_id']);

        if (! $listing) {
            return $this->error('LISTING_NOT_FOUND', 'ไม่พบสินค้า', 404);
        }

        $quote = $this->marketService->quoteDelivery($listing, (float) $data['latitude'], (float) $data['longitude']);
        $quantity = (int) ($data['quantity'] ?? 1);
        $subtotal = round((float) $listing->price * $quantity, 2);

        // รวมราคาตัวเลือกที่เลือก (ตัวเลือกยังไม่ครบ/ไม่ถูกต้อง → ใช้ราคาสินค้าเปล่าไปก่อน ตอนสั่งจริงจะตรวจอีกครั้ง)
        if (! empty($data['option_ids'])) {
            try {
                $subtotal = app(\App\Services\FreshMarketOptionService::class)
                    ->resolveLine($listing, $data['option_ids'], $quantity)['line_total'];
            } catch (FreshMarketException $e) {
                // ข้าม — แสดงยอดสินค้าเปล่า
            }
        }
        $payload = array_merge($quote, [
            'subtotal' => $subtotal,
            'grand_total' => round($subtotal + ($quote['available'] ? $quote['total_fee'] : 0), 2),
        ]);

        if (! $quote['available']) {
            return response()->json([
                'success' => false,
                'message' => $quote['message'],
                'code' => $quote['code'],
                'data' => $payload,
            ], 422);
        }

        return $this->ok($payload, 'คำนวณค่าส่งสำเร็จ');
    }

    /**
     * POST /api/v1/fresh-market/orders — สั่งซื้อ (ได้ออเดอร์เดียวต่อร้าน)
     *
     * รูปแบบที่รับ (ราคาคำนวณฝั่งเซิร์ฟเวอร์เสมอ):
     *  1) จากตะกร้า : seller_id (ไม่ส่ง items / listing_id) → สั่งทุกรายการของร้านนั้นในตะกร้า แล้วล้างตะกร้าร้านนั้น
     *  2) ส่งรายการเอง: items[] {listing_id, quantity, option_ids[]?, note?} (+ seller_id ถ้าส่งต้องตรงกับร้านของสินค้า)
     *  3) แบบเดิม     : listing_id, quantity, option_ids[]?, item_note?
     * ร่วมกัน: delivery_type (pickup|rider), payment_method (wallet|cod), delivery_notes?,
     *          delivery_address + buyer_latitude + buyer_longitude (บังคับเมื่อ rider — รับชื่อ address/lat/lng ด้วย)
     */
    public function storeOrder(Request $request): JsonResponse
    {
        // ชื่อช่องสั้นที่แอปอาจส่ง → ชื่อจริง
        $aliases = [
            'address' => 'delivery_address',
            'lat' => 'buyer_latitude',
            'latitude' => 'buyer_latitude',
            'lng' => 'buyer_longitude',
            'longitude' => 'buyer_longitude',
            'note' => 'delivery_notes',
        ];
        foreach ($aliases as $from => $to) {
            if ($request->filled($from) && ! $request->filled($to)) {
                $request->merge([$to => $request->input($from)]);
            }
        }

        $mode = $request->has('items') ? 'items' : ($request->filled('listing_id') ? 'single' : 'cart');

        $rules = [
            'delivery_type' => 'required|in:pickup,rider',
            'delivery_address' => 'required_if:delivery_type,rider|nullable|string|max:500',
            'delivery_notes' => 'nullable|string|max:500',
            'buyer_latitude' => 'required_if:delivery_type,rider|nullable|numeric|between:-90,90',
            'buyer_longitude' => 'required_if:delivery_type,rider|nullable|numeric|between:-180,180',
            'payment_method' => 'required|in:wallet,cod,escrow',
        ];

        $rules += match ($mode) {
            'items' => [
                'seller_id' => 'nullable|integer|min:1',
                'items' => 'required|array|min:1|max:'.FreshMarketService::MAX_ORDER_LINES,
                'items.*.listing_id' => 'required|integer|min:1',
                'items.*.quantity' => 'required|integer|min:1|max:999',
                'items.*.option_ids' => 'nullable|array|max:300',
                'items.*.option_ids.*' => 'integer|min:1',
                'items.*.note' => 'nullable|string|max:255',
            ],
            'single' => [
                'listing_id' => 'required|integer',
                'quantity' => 'required|integer|min:1|max:999',
                'option_ids' => 'nullable|array|max:300',
                'option_ids.*' => 'integer|min:1',
                'item_note' => 'nullable|string|max:255',
            ],
            default => [
                'seller_id' => 'required|integer|min:1',
            ],
        };

        $data = $this->validateRequest($request, $rules, [
            'delivery_address.required_if' => 'กรุณากรอกที่อยู่จัดส่ง',
            'buyer_latitude.required_if' => 'กรุณาปักหมุดตำแหน่งจัดส่ง',
            'buyer_longitude.required_if' => 'กรุณาปักหมุดตำแหน่งจัดส่ง',
            'seller_id.required' => 'กรุณาเลือกร้านที่จะสั่ง หรือส่งรายการสินค้า',
            'items.required' => 'ยังไม่มีสินค้าในรายการสั่งซื้อ',
            'items.max' => 'สั่งได้สูงสุด '.FreshMarketService::MAX_ORDER_LINES.' รายการต่อออเดอร์',
            'items.*.quantity.min' => 'จำนวนสินค้าต้องอย่างน้อย 1',
            'items.*.quantity.max' => 'จำนวนต่อรายการสูงสุด 999',
        ]);

        $orderData = array_merge(
            array_intersect_key($data, array_flip([
                'delivery_type', 'delivery_address', 'delivery_notes', 'buyer_latitude', 'buyer_longitude', 'payment_method',
            ])),
            ['channel' => 'api']
        );

        return $this->handle(function () use ($request, $data, $mode, $orderData) {
            $buyer = $request->user();

            if ($mode === 'single') {
                $listing = FreshMarketListing::with('seller')->find($data['listing_id']);

                if (! $listing) {
                    return $this->error('LISTING_NOT_FOUND', 'ไม่พบสินค้า', 404);
                }

                $order = $this->marketService->createOrder($buyer, $listing, array_merge($orderData, [
                    'quantity' => (int) $data['quantity'],
                    // ไม่ส่ง option_ids = null → สินค้าที่มีกลุ่มบังคับจะตอบ OPTION_REQUIRED
                    'option_ids' => $data['option_ids'] ?? null,
                    'item_note' => $data['item_note'] ?? null,
                ]));
            } elseif ($mode === 'items') {
                $firstListing = FreshMarketListing::find((int) $data['items'][0]['listing_id']);

                if (! $firstListing) {
                    return $this->error('LISTING_NOT_FOUND', 'ไม่พบสินค้า', 404);
                }

                $sellerId = (int) $firstListing->seller_id;

                if (! empty($data['seller_id']) && (int) $data['seller_id'] !== $sellerId) {
                    return $this->error('MIXED_SELLERS', 'สั่งได้ทีละร้าน กรุณาแยกออเดอร์ตามร้าน', 422);
                }

                $order = $this->marketService->createOrderFromItems($buyer, $sellerId, $data['items'], $orderData);
            } else {
                $order = app(\App\Services\FreshMarketCartService::class)->checkout($buyer, (int) $data['seller_id'], $orderData);
            }

            return $this->ok($order->toApiArray('buyer'), 'สั่งซื้อสำเร็จ', 201);
        });
    }

    /**
     * GET /api/v1/fresh-market/orders?status=
     * ออเดอร์ของฉัน (ฝั่งผู้ซื้อ)
     */
    public function orders(Request $request): JsonResponse
    {
        $orders = FreshMarketOrder::where('buyer_id', $request->user()->id)
            ->statusFilter($request->get('status'))
            ->with(['seller', 'listing', 'riderJob.rider', 'items'])
            ->latest()
            ->paginate(15);

        return $this->paginated($orders, 'buyer', 'ดึงรายการออเดอร์สำเร็จ');
    }

    /**
     * GET /api/v1/fresh-market/orders/{id}
     * ผู้ซื้อหรือผู้ขายของออเดอร์นี้ดูได้ (มุมมองต่างกัน)
     */
    public function showOrder(Request $request, int $id): JsonResponse
    {
        $order = FreshMarketOrder::find($id);

        if (! $order) {
            return $this->error('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        $role = $this->roleFor($order, $request);

        if (! $role) {
            return $this->error('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        return $this->ok(array_merge($order->toApiArray($role), ['viewer_role' => $role]));
    }

    /**
     * PUT /api/v1/fresh-market/orders/{id}/status  (คงไว้เพื่อความเข้ากันได้)
     * body: action (accept|prepare|ready|handover|deliver|confirm|cancel), cancel_reason / reason
     * role ถูกหาจากผู้ใช้เอง: ผู้ขายทำได้ accept/prepare/ready/handover/cancel, ผู้ซื้อทำได้ confirm/cancel
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'action' => 'required|in:accept,prepare,ready,handover,deliver,confirm,cancel',
            'cancel_reason' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:500',
        ]);

        $order = FreshMarketOrder::find($id);

        if (! $order) {
            return $this->error('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        $role = $this->roleFor($order, $request);

        if (! $role) {
            return $this->error('FORBIDDEN', 'ไม่มีสิทธิ์จัดการออเดอร์นี้', 403);
        }

        $action = $data['action'] === 'deliver' ? 'handover' : $data['action'];
        $sellerActions = ['accept', 'prepare', 'ready', 'handover', 'cancel'];
        $buyerActions = ['confirm', 'cancel'];

        if (($role === 'seller' && ! in_array($action, $sellerActions, true))
            || ($role === 'buyer' && ! in_array($action, $buyerActions, true))) {
            return $this->error('FORBIDDEN', 'คุณไม่มีสิทธิ์ทำรายการนี้กับออเดอร์', 403);
        }

        $reason = (string) ($data['reason'] ?? $data['cancel_reason'] ?? '');

        if ($action === 'cancel' && $role === 'seller' && trim($reason) === '') {
            return $this->error('VALIDATION_ERROR', 'กรุณาระบุเหตุผลที่ยกเลิก', 422);
        }

        return $this->handle(function () use ($order, $action, $role, $request, $reason) {
            $updated = $this->marketService->applyAction($order, $action, $role, $request->user(), ['reason' => $reason]);

            return $this->ok($updated->toApiArray($role), 'อัพเดทสถานะสำเร็จ');
        });
    }

    /**
     * POST /api/v1/fresh-market/orders/{id}/cancel  body: reason?
     * ผู้ซื้อยกเลิกได้เฉพาะตอนร้านยังไม่รับ (เงินที่จ่ายผ่าน wallet คืนเข้า wallet)
     */
    public function cancelOrder(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, ['reason' => 'nullable|string|max:500']);
        $order = FreshMarketOrder::where('id', $id)->where('buyer_id', $request->user()->id)->first();

        if (! $order) {
            return $this->error('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        return $this->handle(function () use ($order, $request, $data) {
            $updated = $this->marketService->cancelOrder($order, (string) ($data['reason'] ?? ''), 'buyer', $request->user());

            return $this->ok($updated->toApiArray('buyer'), 'ยกเลิกออเดอร์เรียบร้อยแล้ว');
        });
    }

    /**
     * POST /api/v1/fresh-market/orders/{id}/confirm
     * body: rating? (1-5), review?, rider_rating? (1-5), rider_review?
     */
    public function confirmOrder(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'rating' => 'nullable|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'rider_rating' => 'nullable|integer|min:1|max:5',
            'rider_review' => 'nullable|string|max:1000',
        ]);

        $order = FreshMarketOrder::where('id', $id)->where('buyer_id', $request->user()->id)->first();

        if (! $order) {
            return $this->error('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        return $this->handle(function () use ($order, $request, $data) {
            $completed = $this->marketService->completeOrder($order, 'buyer', $request->user());

            if (! empty($data['rating'])) {
                try {
                    $completed = $this->marketService->rateOrder(
                        $completed,
                        $request->user(),
                        (int) $data['rating'],
                        $data['review'] ?? null,
                        isset($data['rider_rating']) ? (int) $data['rider_rating'] : null,
                        $data['rider_review'] ?? null
                    );
                } catch (FreshMarketException $e) {
                    // ยืนยันรับสำเร็จแล้ว — คะแนนซ้ำไม่ทำให้คำขอนี้ล้ม
                }
            }

            return $this->ok($completed->toApiArray('buyer'), 'ยืนยันรับสินค้าสำเร็จ');
        });
    }

    /**
     * POST /api/v1/fresh-market/orders/{id}/review
     * body: rating (1-5), review?, rider_rating? (1-5), rider_review?
     */
    public function reviewOrder(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'rider_rating' => 'nullable|integer|min:1|max:5',
            'rider_review' => 'nullable|string|max:1000',
        ]);

        $order = FreshMarketOrder::where('id', $id)->where('buyer_id', $request->user()->id)->first();

        if (! $order) {
            return $this->error('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        return $this->handle(function () use ($order, $request, $data) {
            $rated = $this->marketService->rateOrder(
                $order,
                $request->user(),
                (int) $data['rating'],
                $data['review'] ?? null,
                isset($data['rider_rating']) ? (int) $data['rider_rating'] : null,
                $data['rider_review'] ?? null
            );

            return $this->ok($rated->toApiArray('buyer'), 'ขอบคุณสำหรับรีวิว');
        });
    }

    // ===== Auth: ผู้ขาย =====

    /**
     * POST /api/v1/fresh-market/seller/register
     * body: shop_name, phone, address, latitude, longitude, shop_description?, province?, district?, sub_district?, agree_terms (true)
     */
    public function registerSeller(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
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
        ] + FreshMarketValidationText::messages(), FreshMarketValidationText::attributes());

        unset($data['agree_terms']);

        return $this->handle(function () use ($request, $data) {
            $seller = $this->marketService->registerSeller($request->user(), $data);

            return $this->ok($this->sellerProfileData($seller), $seller->is_verified
                ? 'สมัครเป็นผู้ขายสำเร็จ'
                : 'สมัครเป็นผู้ขายสำเร็จ รอแอดมินยืนยันร้านก่อนสินค้าจะแสดง', 201);
        });
    }

    /**
     * GET /api/v1/fresh-market/seller/profile
     */
    public function sellerProfile(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        return $this->ok($this->sellerProfileData($seller));
    }

    /**
     * PUT /api/v1/fresh-market/seller/profile
     */
    public function updateSellerProfile(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $data = $this->validateRequest($request, [
            'shop_name' => 'sometimes|required|string|max:200',
            'shop_description' => 'nullable|string|max:1000',
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'address' => 'sometimes|required|string|max:500',
            'province' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'sub_district' => 'nullable|string|max:100',
            'latitude' => 'required_with:longitude|numeric|between:-90,90',
            'longitude' => 'required_with:latitude|numeric|between:-180,180',
            // ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด) — สลับแล้วต้องกด "เปิดร้านที่นี่วันนี้" ใหม่
            'is_mobile' => 'sometimes|boolean',
        ], [
            'phone.regex' => 'รูปแบบเบอร์โทรไม่ถูกต้อง',
            'latitude.required_with' => 'กรุณาปักหมุดตำแหน่งร้าน',
            'longitude.required_with' => 'กรุณาปักหมุดตำแหน่งร้าน',
        ] + FreshMarketValidationText::messages(), FreshMarketValidationText::attributes());

        $mobile = array_key_exists('is_mobile', $data) ? (bool) $data['is_mobile'] : null;
        unset($data['is_mobile']);

        $seller->update($data);

        if ($mobile !== null) {
            app(\App\Services\FreshMarketShopPresenceService::class)->setMobile($seller->fresh(), $mobile);
        }

        return $this->ok($this->sellerProfileData($seller->fresh()), 'บันทึกข้อมูลร้านเรียบร้อยแล้ว');
    }

    /**
     * POST /api/v1/fresh-market/seller/subscribe — ต่ออายุสมาชิกรายเดือน (หักจาก wallet)
     */
    public function subscribe(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        return $this->handle(function () use ($seller) {
            $updated = $this->marketService->subscribeSeller($seller);

            return $this->ok($this->sellerProfileData($updated), 'ต่ออายุสมาชิกสำเร็จ');
        });
    }

    /**
     * POST /api/v1/fresh-market/listings (multipart)
     */
    public function storeListing(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        // ร้านถูกระงับ / ลงขายครบโควต้าแล้ว → ตอบก่อนรับไฟล์รูป (ไม่ให้มีไฟล์ค้างบน disk)
        if (! $seller->is_active || $seller->is_suspended) {
            return $this->error('SELLER_SUSPENDED', 'ร้านของคุณถูกระงับหรือปิดอยู่ ไม่สามารถลงขายได้', 403);
        }

        if (! $seller->canCreateListing()) {
            return $this->error('LISTING_LIMIT', $this->marketService->listingLimitMessage(), 403);
        }

        if (! $request->filled('quantity_available') && $request->filled('quantity')) {
            $request->merge(['quantity_available' => $request->input('quantity')]);
        }

        \App\Services\FreshMarketOptionService::normalizeGroupsInput($request);

        $maxCashback = $this->marketService->maxCashbackPercent($seller);
        // ทำตามสั่ง (track_stock=false) ไม่ต้องกรอกจำนวน
        $tracksStock = ! $request->has('track_stock') || $request->boolean('track_stock');

        $data = $this->validateRequest($request, [
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
            'tags' => 'nullable|array',
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
        ] + \App\Services\FreshMarketOptionService::validationMessages() + FreshMarketValidationText::messages(), FreshMarketValidationText::attributes());

        $optionGroups = $data['option_groups'] ?? null;
        unset($data['option_groups']);
        $data['track_stock'] = $tracksStock;

        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('fresh-market', 'public');
                $imageUrls[] = '/storage/'.$path;
            }
        }

        $data['images'] = $imageUrls;
        $data['main_image_url'] = $imageUrls[0] ?? null;
        $data['created_via'] = 'api';

        return $this->handle(function () use ($seller, $data, $optionGroups, $request, $imageUrls) {
            $optionService = app(\App\Services\FreshMarketOptionService::class);

            try {
                // สินค้า + ตัวเลือกบันทึกพร้อมกัน (ตัวเลือกพัง = ไม่มีสินค้าที่ขายได้โดยไม่มีตัวเลือกบังคับ)
                $listing = \Illuminate\Support\Facades\DB::transaction(function () use ($seller, $data, $optionGroups, $request, $optionService) {
                    $listing = $this->marketService->createListing($seller, $data);

                    if (is_array($optionGroups) && ! empty($optionGroups)) {
                        $optionService->syncGroups($listing, $optionGroups, $request);
                    }

                    return $listing;
                });
            } catch (\Throwable $e) {
                // บันทึกไม่สำเร็จ → ลบไฟล์รูปที่เพิ่งอัปโหลด (ไม่มีสินค้าไหนใช้) แล้วส่งต่อให้ handle() ตอบข้อความไทย
                foreach ($imageUrls as $url) {
                    $optionService->deleteListingImageIfUnused($url);
                }

                throw $e;
            }

            return $this->ok(array_merge($this->listingSummary($listing), [
                'status' => $listing->status,
                'option_groups' => $optionService->groupsForApi($listing, true),
            ]), 'ลงขายสินค้าสำเร็จ', 201);
        });
    }

    /**
     * PUT /api/v1/fresh-market/listings/{id}
     * เติมสต็อก (quantity_available > 0) ให้สินค้าที่ของหมดกลับมาขายอัตโนมัติ
     */
    public function updateListing(Request $request, int $id): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $listing = FreshMarketListing::where('id', $id)->where('seller_id', $seller->id)->first();

        if (! $listing) {
            return $this->error('LISTING_NOT_FOUND', 'ไม่พบสินค้า หรือคุณไม่มีสิทธิ์แก้ไข', 404);
        }

        $maxCashback = $this->marketService->maxCashbackPercent($seller);

        \App\Services\FreshMarketOptionService::normalizeGroupsInput($request);

        $data = $this->validateRequest($request, [
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'sometimes|exists:fresh_market_categories,id',
            'price' => 'sometimes|numeric|min:1|max:1000000',
            'compare_at_price' => 'nullable|numeric',
            'unit' => 'sometimes|string|max:50',
            'track_stock' => 'sometimes|boolean',
            'quantity_available' => 'sometimes|integer|min:0|max:100000',
            'is_organic' => 'boolean',
            'is_available' => 'boolean',
            'freshness_level' => 'nullable|string|in:สด,สดมาก,ผลิตวันนี้',
            'cashback_percentage' => 'nullable|numeric|min:0|max:'.$maxCashback,
            'tags' => 'sometimes|nullable|array',
        ] + \App\Services\FreshMarketOptionService::groupsRules(), [
            'cashback_percentage.max' => 'แคชแบ็คตั้งได้ไม่เกิน '.$maxCashback.'%',
        ] + \App\Services\FreshMarketOptionService::validationMessages() + FreshMarketValidationText::messages(), FreshMarketValidationText::attributes());

        // ราคาก่อนลด (ถ้าส่งมา) ต้องมากกว่าราคาขาย — เทียบราคาใหม่ถ้าส่งมาด้วย ไม่งั้นเทียบราคาเดิม (เหมือนฟอร์มเว็บ)
        if (array_key_exists('compare_at_price', $data) && $data['compare_at_price'] !== null) {
            $effectivePrice = (float) ($data['price'] ?? $listing->price);

            if ((float) $data['compare_at_price'] <= $effectivePrice) {
                return $this->error('VALIDATION_ERROR', 'ราคาก่อนลดต้องมากกว่าราคาขาย', 422);
            }
        }

        // ส่ง option_groups มา = แทนที่ตัวเลือกทั้งชุด (ไม่ส่ง = ไม่แตะ, [] = ลบทั้งหมด)
        $hasGroups = $request->has('option_groups');
        $optionGroups = $data['option_groups'] ?? [];
        unset($data['option_groups']);

        if ($listing->status === 'suspended') {
            unset($data['is_available']);
        }

        return $this->handle(function () use ($listing, $data, $hasGroups, $optionGroups, $request) {
            $optionService = app(\App\Services\FreshMarketOptionService::class);

            \Illuminate\Support\Facades\DB::transaction(function () use ($listing, $data, $hasGroups, $optionGroups, $request, $optionService) {
                $listing->update($data);

                if ($hasGroups) {
                    $optionService->syncGroups($listing, is_array($optionGroups) ? $optionGroups : [], $request);
                }
            });

            $fresh = $listing->fresh(['optionGroups.options']);

            return $this->ok(array_merge($this->listingSummary($fresh), [
                'status' => $fresh->status,
                'is_available' => (bool) $fresh->is_available,
                'option_groups' => $optionService->groupsForApi($fresh, true),
                'updated_at' => $fresh->updated_at?->toIso8601String(),
            ]), 'อัพเดทสินค้าสำเร็จ');
        });
    }

    /**
     * GET /api/v1/fresh-market/seller/orders?status=
     */
    public function sellerOrders(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $orders = FreshMarketOrder::where('seller_id', $seller->id)
            ->statusFilter($request->get('status'))
            ->with(['buyer', 'listing', 'seller', 'riderJob.rider', 'items'])
            ->latest()
            ->paginate(15);

        return $this->paginated($orders, 'seller', 'ดึงรายการออเดอร์ร้านสำเร็จ');
    }

    /**
     * GET /api/v1/fresh-market/seller/orders/{id}
     */
    public function sellerOrderShow(Request $request, int $id): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $order = FreshMarketOrder::where('id', $id)->where('seller_id', $seller->id)->first();

        if (! $order) {
            return $this->error('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        return $this->ok($order->toApiArray('seller'));
    }

    /**
     * POST /api/v1/fresh-market/seller/orders/{id}/action
     * body: action (accept|prepare|ready|handover|cancel), reason (บังคับเมื่อ cancel)
     */
    public function sellerOrderAction(Request $request, int $id): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $data = $this->validateRequest($request, [
            'action' => 'required|in:accept,prepare,ready,handover,cancel',
            'reason' => 'required_if:action,cancel|nullable|string|max:500',
        ], [
            'reason.required_if' => 'กรุณาระบุเหตุผลที่ยกเลิก',
        ]);

        $order = FreshMarketOrder::where('id', $id)->where('seller_id', $seller->id)->first();

        if (! $order) {
            return $this->error('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        return $this->handle(function () use ($order, $data, $request) {
            $updated = $this->marketService->applyAction($order, $data['action'], 'seller', $request->user(), [
                'reason' => $data['reason'] ?? '',
            ]);

            return $this->ok($updated->toApiArray('seller'), FreshMarketOrder::actionLabel($data['action']).'เรียบร้อยแล้ว');
        });
    }

    /**
     * GET /api/v1/fresh-market/seller/dashboard
     */
    public function sellerDashboard(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $countsByStatus = FreshMarketOrder::where('seller_id', $seller->id)
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->map(fn ($v) => (int) $v)
            ->all();

        return $this->ok(array_merge($this->sellerProfileData($seller), [
            'stats' => [
                'total_listings' => (int) $seller->total_listings,
                'total_sales' => (int) $seller->total_sales,
                'total_revenue' => (float) $seller->total_revenue,
                'rating_average' => (float) $seller->rating_average,
                'rating_count' => (int) $seller->rating_count,
                'pending_orders' => (int) ($countsByStatus[FreshMarketOrder::STATUS_PENDING] ?? 0),
                'orders_by_status' => $countsByStatus,
            ],
        ]));
    }

    // ===== Helpers =====

    /**
     * ข้อมูลสินค้าแบบย่อ (ตัวเลขเป็น number ทั้งหมด)
     */
    protected function listingSummary(FreshMarketListing $l): array
    {
        // พิกัดที่แสดง = ตำแหน่งร้านตอนนี้ (ร้านเคลื่อนที่ที่ปิดอยู่ = null) — ดู FreshMarketListing::displayCoordinates
        $coords = $l->displayCoordinates();
        $seller = $l->relationLoaded('seller') ? $l->seller : null;
        $shopOpen = $seller ? $seller->isOpenNow() : null;

        return [
            'id' => (int) $l->id,
            'slug' => $l->slug,
            'title' => $l->title,
            'description' => $l->description,
            'price' => (float) $l->price,
            'compare_at_price' => $l->compare_at_price !== null ? (float) $l->compare_at_price : null,
            'unit' => $l->unit,
            'quantity_available' => (int) $l->quantity_available,
            'main_image_url' => $l->primary_image,
            'images' => $l->images ?? [],
            'is_organic' => (bool) $l->is_organic,
            'is_featured' => (bool) $l->is_featured,
            'freshness_level' => $l->freshness_level,
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'distance_km' => isset($l->distance_km) ? round((float) $l->distance_km, 1) : null,
            // ร้านปิด (รถเข็นยังไม่เปิดวันนี้ / กดปิดร้าน) → แสดงได้แต่สั่งไม่ได้ (null = ไม่ได้โหลดข้อมูลร้าน)
            'shop_is_open' => $shopOpen,
            'can_order' => $shopOpen,
            'cashback_amount' => (float) $l->cashback_amount,
            // ทำตามสั่ง (track_stock=false) ไม่ดูจำนวนคงเหลือ · max_order_quantity = จำนวนสูงสุดที่เลือกได้
            'track_stock' => $l->tracksStock(),
            'max_order_quantity' => (int) $l->max_order_quantity,
            // มีตัวเลือก → แอปต้องเปิดหน้าเลือกตัวเลือกก่อนหยิบใส่ตะกร้า (null = ไม่ทราบ ให้เปิดหน้ารายละเอียด)
            'has_options' => $l->relationLoaded('optionGroups')
                ? $l->optionGroups->isNotEmpty()
                : (isset($l->option_groups_count) ? (int) $l->option_groups_count > 0 : null),
            'seller' => $seller ? [
                'id' => (int) $seller->id,
                'shop_name' => $seller->shop_name,
                'rating_average' => (float) $seller->rating_average,
                'is_mobile' => $seller->isMobileShop(),
                'is_open' => $shopOpen,
                'closed_message' => $shopOpen ? null : FreshMarketSeller::CLOSED_MESSAGE,
                'location_label' => $shopOpen ? $seller->location_label : null,
            ] : null,
            'category' => $l->relationLoaded('category') && $l->category ? [
                'id' => (int) $l->category->id,
                'name' => $l->category->name,
                'icon' => $l->category->icon,
            ] : null,
        ];
    }

    /**
     * ข้อมูลร้านของผู้ขาย (สำหรับเจ้าของร้าน)
     */
    protected function sellerProfileData(FreshMarketSeller $seller): array
    {
        $settings = FreshMarketSetting::getSettings();

        return [
            'id' => (int) $seller->id,
            'shop_name' => $seller->shop_name,
            'shop_description' => $seller->shop_description,
            'shop_image' => $seller->shop_image,
            'phone' => $seller->phone,
            'address' => $seller->address,
            'province' => $seller->province,
            'district' => $seller->district,
            'sub_district' => $seller->sub_district,
            'latitude' => $seller->latitude !== null ? (float) $seller->latitude : null,
            'longitude' => $seller->longitude !== null ? (float) $seller->longitude : null,
            'has_pickup_location' => $seller->hasPickupLocation(),
            'status' => $seller->status_key,
            'status_label' => $seller->status_label,
            'is_verified' => (bool) $seller->is_verified,
            'is_visible_to_buyers' => $seller->isVisibleToBuyers(),
            'subscription_type' => $seller->subscription_type,
            'subscription_expires_at' => $seller->subscription_expires_at?->toIso8601String(),
            'has_paid_subscription' => $seller->hasPaidSubscription(),
            'monthly_subscription_fee' => (float) $settings->monthly_subscription_fee,
            'fee_mode' => $settings->fee_mode,
            'can_create_listing' => $seller->canCreateListing(),
            'max_cashback_percent' => $this->marketService->maxCashbackPercent($seller),
            'outstanding_gp_debt' => $seller->outstandingGpDebt(),
            // ร้านรถเข็น/ตลาดนัด: เปิด/ปิด + ตำแหน่งวันนี้ (จัดการที่ POST /seller/open|location|close)
            'is_mobile' => $seller->isMobileShop(),
            'is_open' => $seller->isOpenNow(),
            'presence' => $seller->presencePayload(true),
        ];
    }

    /**
     * ร้านของผู้ใช้ปัจจุบัน
     */
    protected function sellerOf(Request $request): ?FreshMarketSeller
    {
        return FreshMarketSeller::where('user_id', $request->user()->id)->first();
    }

    /**
     * บทบาทของผู้ใช้ต่อออเดอร์: buyer | seller | null (ไม่เกี่ยวข้อง)
     */
    protected function roleFor(FreshMarketOrder $order, Request $request): ?string
    {
        $userId = (int) $request->user()->id;

        if ((int) $order->buyer_id === $userId) {
            return 'buyer';
        }

        $seller = $this->sellerOf($request);

        return $seller && (int) $order->seller_id === (int) $seller->id ? 'seller' : null;
    }

    /**
     * ตรวจ request แล้วคืนข้อมูลที่ผ่าน — ไม่ผ่านตอบ 422 แบบมาตรฐานของแอป
     */
    protected function validateRequest(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422));
        }

        return $validator->validated();
    }

    /**
     * รันงานที่อาจโยน FreshMarketException → แปลงเป็น JSON ภาษาไทย (exception อื่น log แล้วตอบข้อความกลาง)
     */
    protected function handle(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (FreshMarketException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), $e->httpStatus());
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('FreshMarketAPI: ทำรายการล้มเหลว', [
                'user_id' => auth()->id(),
                'path' => request()->path(),
                'error' => $e->getMessage(),
            ]);

            return $this->error('SERVER_ERROR', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    protected function ok(mixed $data, string $message = 'สำเร็จ', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], $status);
    }

    /**
     * ตอบรายการออเดอร์แบบแบ่งหน้า
     */
    protected function paginated($orders, string $role, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => collect($orders->items())->map(fn (FreshMarketOrder $o) => $o->toApiArray($role))->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }
}
