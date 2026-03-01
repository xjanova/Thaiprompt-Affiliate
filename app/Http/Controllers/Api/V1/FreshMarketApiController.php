<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Services\FreshMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fresh Market API Controller
 *
 * REST API สำหรับระบบตลาดสดไทยพร้อม
 * ใช้สำหรับ mobile app หรือ external integrations
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
     * GET /api/v1/fresh-market/categories
     * รายการหมวดหมู่ทั้งหมด
     */
    public function categories(): JsonResponse
    {
        $categories = FreshMarketCategory::active()
            ->root()
            ->orderBy('sort_order')
            ->with('children')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'icon' => $c->icon,
                'image_url' => $c->image_url,
                'children' => $c->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'icon' => $child->icon,
                ]),
            ]),
        ]);
    }

    /**
     * GET /api/v1/fresh-market/listings
     * รายการสินค้า (filter: lat, lng, radius, category, price, q, sort)
     */
    public function listings(Request $request): JsonResponse
    {
        $query = FreshMarketListing::active()->inStock()
            ->with('seller:id,shop_name,rating_average', 'category:id,name,icon');

        // ค้นหาตามพิกัด
        if ($request->filled('lat') && $request->filled('lng')) {
            $query->nearby(
                $request->float('lat'),
                $request->float('lng'),
                $request->float('radius', 10)
            );
        }

        // ค้นหาตามคำค้น
        if ($request->filled('q')) {
            $query->search($request->get('q'));
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category_id')) {
            $query->inCategory($request->integer('category_id'));
        }

        // กรองตามราคา
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->priceRange(
                $request->filled('min_price') ? $request->float('min_price') : null,
                $request->filled('max_price') ? $request->float('max_price') : null
            );
        }

        // กรองสินค้าอินทรีย์
        if ($request->boolean('organic')) {
            $query->where('is_organic', true);
        }

        // จัดเรียง
        match ($request->get('sort', 'newest')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('order_count', 'desc'),
            'distance' => null, // already sorted by nearby scope
            default => $query->latest(),
        };

        $perPage = min($request->integer('per_page', 20), 50);
        $listings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $listings->through(fn ($l) => [
                'id' => $l->id,
                'slug' => $l->slug,
                'title' => $l->title,
                'description' => $l->description,
                'price' => $l->price,
                'compare_at_price' => $l->compare_at_price,
                'unit' => $l->unit,
                'quantity_available' => $l->quantity_available,
                'main_image_url' => $l->main_image_url,
                'images' => $l->images,
                'is_organic' => $l->is_organic,
                'is_featured' => $l->is_featured,
                'freshness_level' => $l->freshness_level,
                'latitude' => $l->latitude,
                'longitude' => $l->longitude,
                'distance_km' => isset($l->distance_km) ? round($l->distance_km, 1) : null,
                'cashback_amount' => $l->cashback_amount,
                'seller' => $l->seller ? [
                    'id' => $l->seller->id,
                    'shop_name' => $l->seller->shop_name,
                    'rating_average' => $l->seller->rating_average,
                ] : null,
                'category' => $l->category ? [
                    'id' => $l->category->id,
                    'name' => $l->category->name,
                    'icon' => $l->category->icon,
                ] : null,
            ]),
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
     * รายละเอียดสินค้า
     */
    public function showListing(int $id): JsonResponse
    {
        $listing = FreshMarketListing::with(['seller', 'category'])->find($id);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสินค้า',
            ], 404);
        }

        $listing->incrementViews();

        // สินค้าที่เกี่ยวข้อง
        $related = FreshMarketListing::active()
            ->inStock()
            ->where('id', '!=', $listing->id)
            ->where(function ($q) use ($listing) {
                $q->where('seller_id', $listing->seller_id)
                    ->orWhere('category_id', $listing->category_id);
            })
            ->with('seller:id,shop_name')
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $listing->id,
                'slug' => $listing->slug,
                'title' => $listing->title,
                'description' => $listing->description,
                'price' => $listing->price,
                'compare_at_price' => $listing->compare_at_price,
                'unit' => $listing->unit,
                'quantity_available' => $listing->quantity_available,
                'main_image_url' => $listing->main_image_url,
                'images' => $listing->images,
                'is_organic' => $listing->is_organic,
                'is_featured' => $listing->is_featured,
                'freshness_level' => $listing->freshness_level,
                'tags' => $listing->tags,
                'latitude' => $listing->latitude,
                'longitude' => $listing->longitude,
                'delivery_radius_km' => $listing->delivery_radius_km,
                'cashback_amount' => $listing->cashback_amount,
                'cashback_percentage' => $listing->cashback_percentage,
                'view_count' => $listing->view_count,
                'order_count' => $listing->order_count,
                'seller' => $listing->seller ? [
                    'id' => $listing->seller->id,
                    'shop_name' => $listing->seller->shop_name,
                    'shop_description' => $listing->seller->shop_description,
                    'shop_image' => $listing->seller->shop_image,
                    'rating_average' => $listing->seller->rating_average,
                    'rating_count' => $listing->seller->rating_count,
                    'total_sales' => $listing->seller->total_sales,
                    'latitude' => $listing->seller->latitude,
                    'longitude' => $listing->seller->longitude,
                    'phone' => $listing->seller->phone,
                ] : null,
                'category' => $listing->category ? [
                    'id' => $listing->category->id,
                    'name' => $listing->category->name,
                    'icon' => $listing->category->icon,
                ] : null,
            ],
            'related' => $related->map(fn ($r) => [
                'id' => $r->id,
                'slug' => $r->slug,
                'title' => $r->title,
                'price' => $r->price,
                'unit' => $r->unit,
                'main_image_url' => $r->main_image_url,
                'shop_name' => $r->seller?->shop_name,
            ]),
        ]);
    }

    /**
     * GET /api/v1/fresh-market/nearby
     * สินค้าใกล้ตัว (ต้องส่ง lat, lng)
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
                'is_organic' => $l->is_organic,
                'cashback' => $l->cashback_amount,
            ]),
            'count' => $listings->count(),
        ]);
    }

    /**
     * GET /api/v1/fresh-market/sellers/{id}
     * รายละเอียดผู้ขาย
     */
    public function showSeller(int $id): JsonResponse
    {
        $seller = FreshMarketSeller::active()->find($id);

        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ขาย',
            ], 404);
        }

        $listings = $seller->listings()
            ->active()
            ->inStock()
            ->with('category:id,name,icon')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $seller->id,
                'shop_name' => $seller->shop_name,
                'shop_description' => $seller->shop_description,
                'shop_image' => $seller->shop_image,
                'rating_average' => $seller->rating_average,
                'rating_count' => $seller->rating_count,
                'total_sales' => $seller->total_sales,
                'latitude' => $seller->latitude,
                'longitude' => $seller->longitude,
                'province' => $seller->province,
                'is_verified' => $seller->is_verified,
            ],
            'listings' => $listings->map(fn ($l) => [
                'id' => $l->id,
                'slug' => $l->slug,
                'title' => $l->title,
                'price' => $l->price,
                'unit' => $l->unit,
                'main_image_url' => $l->main_image_url,
                'is_organic' => $l->is_organic,
                'category' => $l->category?->name,
            ]),
        ]);
    }

    // ===== Auth Endpoints (ต้อง login) =====

    /**
     * POST /api/v1/fresh-market/listings
     * สร้างรายการสินค้าใหม่
     */
    public function storeListing(Request $request): JsonResponse
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->first();

        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'คุณยังไม่ได้สมัครเป็นผู้ขาย',
            ], 403);
        }

        if (!$seller->canCreateListing()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณลงขายสินค้าเต็มจำนวนแล้ว กรุณาอัพเกรดแพ็กเกจ',
            ], 403);
        }

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
            'freshness_level' => 'nullable|string|in:สด,สดมาก,ผลิตวันนี้',
            'tags' => 'nullable|array',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
        ]);

        // อัพโหลดรูปภาพ
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('fresh-market', 'public');
                $imageUrls[] = '/storage/' . $path;
            }
        }

        $validated['images'] = $imageUrls;
        $validated['main_image_url'] = $imageUrls[0] ?? null;
        $validated['created_via'] = 'api';

        $listing = $this->marketService->createListing($seller, $validated);

        return response()->json([
            'success' => true,
            'message' => 'ลงขายสินค้าสำเร็จ',
            'data' => [
                'id' => $listing->id,
                'slug' => $listing->slug,
                'title' => $listing->title,
                'price' => $listing->price,
                'status' => $listing->status,
            ],
        ], 201);
    }

    /**
     * PUT /api/v1/fresh-market/listings/{id}
     * อัพเดทสินค้า
     */
    public function updateListing(Request $request, int $id): JsonResponse
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->first();

        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ขาย'], 403);
        }

        $listing = FreshMarketListing::where('id', $id)->where('seller_id', $seller->id)->first();

        if (!$listing) {
            return response()->json(['success' => false, 'message' => 'ไม่พบสินค้า หรือคุณไม่มีสิทธิ์แก้ไข'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'sometimes|exists:fresh_market_categories,id',
            'price' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
            'quantity_available' => 'sometimes|integer|min:0',
            'is_organic' => 'boolean',
            'is_available' => 'boolean',
            'freshness_level' => 'nullable|string|in:สด,สดมาก,ผลิตวันนี้',
        ]);

        $listing->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสินค้าสำเร็จ',
            'data' => $listing->fresh(),
        ]);
    }

    /**
     * POST /api/v1/fresh-market/orders
     * สร้างคำสั่งซื้อ
     */
    public function storeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'listing_id' => 'required|exists:fresh_market_listings,id',
            'quantity' => 'required|integer|min:1',
            'delivery_type' => 'required|in:pickup,rider,shipping',
            'delivery_address' => 'required_if:delivery_type,rider,shipping|string|max:500',
            'delivery_notes' => 'nullable|string|max:500',
            'buyer_latitude' => 'nullable|numeric',
            'buyer_longitude' => 'nullable|numeric',
            'payment_method' => 'required|in:escrow,cod,transfer,wallet',
        ]);

        $listing = FreshMarketListing::active()->inStock()->find($validated['listing_id']);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'สินค้านี้ไม่พร้อมขาย',
            ], 400);
        }

        if ($listing->quantity_available < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'สินค้าไม่เพียงพอ (เหลือ ' . $listing->quantity_available . ' ' . $listing->unit . ')',
            ], 400);
        }

        try {
            $order = $this->marketService->createOrder(auth()->user(), $listing, $validated);

            return response()->json([
                'success' => true,
                'message' => 'สั่งซื้อสำเร็จ',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'delivery_fee' => $order->delivery_fee,
                    'order_status' => $order->order_status,
                    'payment_method' => $order->payment_method,
                    'cashback_amount' => $order->cashback_amount,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/fresh-market/orders
     * ออเดอร์ของฉัน
     */
    public function orders(Request $request): JsonResponse
    {
        $query = FreshMarketOrder::where('buyer_id', auth()->id())
            ->with(['seller:id,shop_name', 'listing:id,title,main_image_url']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('order_status', $request->get('status'));
        }

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders->through(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'listing_title' => $o->listing?->title,
                'listing_image' => $o->listing?->main_image_url,
                'quantity' => $o->quantity,
                'total_amount' => $o->total_amount,
                'delivery_fee' => $o->delivery_fee,
                'order_status' => $o->order_status,
                'payment_status' => $o->payment_status,
                'delivery_type' => $o->delivery_type,
                'shop_name' => $o->seller?->shop_name,
                'cashback_amount' => $o->cashback_amount,
                'created_at' => $o->created_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/fresh-market/orders/{id}
     * รายละเอียดออเดอร์
     */
    public function showOrder(int $id): JsonResponse
    {
        $order = FreshMarketOrder::where('id', $id)
            ->where('buyer_id', auth()->id())
            ->with(['seller', 'listing', 'riderJob'])
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'ไม่พบออเดอร์'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'quantity' => $order->quantity,
                'unit_price' => $order->unit_price,
                'total_amount' => $order->total_amount,
                'platform_fee' => $order->platform_fee,
                'delivery_fee' => $order->delivery_fee,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'delivery_type' => $order->delivery_type,
                'delivery_address' => $order->delivery_address,
                'delivery_notes' => $order->delivery_notes,
                'tracking_number' => $order->tracking_number,
                'cashback_amount' => $order->cashback_amount,
                'buyer_rating' => $order->buyer_rating,
                'seller_rating' => $order->seller_rating,
                'accepted_at' => $order->accepted_at?->toISOString(),
                'delivered_at' => $order->delivered_at?->toISOString(),
                'completed_at' => $order->completed_at?->toISOString(),
                'cancelled_at' => $order->cancelled_at?->toISOString(),
                'cancel_reason' => $order->cancel_reason,
                'listing' => $order->listing ? [
                    'id' => $order->listing->id,
                    'title' => $order->listing->title,
                    'main_image_url' => $order->listing->main_image_url,
                    'unit' => $order->listing->unit,
                ] : null,
                'seller' => $order->seller ? [
                    'id' => $order->seller->id,
                    'shop_name' => $order->seller->shop_name,
                    'phone' => $order->seller->phone,
                ] : null,
                'rider_job' => $order->riderJob ? [
                    'id' => $order->riderJob->id,
                    'status' => $order->riderJob->status,
                    'rider_name' => $order->riderJob->rider?->name,
                ] : null,
                'created_at' => $order->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * PUT /api/v1/fresh-market/orders/{id}/status
     * อัพเดทสถานะ (ผู้ขาย: accept/prepare/ready/deliver, ผู้ซื้อ: confirm/cancel)
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:accept,prepare,ready,deliver,confirm,cancel',
            'cancel_reason' => 'required_if:action,cancel|string|max:500',
        ]);

        $order = FreshMarketOrder::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'ไม่พบออเดอร์'], 404);
        }

        // ตรวจสอบสิทธิ์
        $seller = FreshMarketSeller::where('user_id', auth()->id())->first();
        $isBuyer = $order->buyer_id === auth()->id();
        $isSeller = $seller && $order->seller_id === $seller->id;

        if (!$isBuyer && !$isSeller) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์'], 403);
        }

        try {
            $action = $request->get('action');

            // ผู้ขาย actions
            if ($isSeller && in_array($action, ['accept', 'prepare', 'ready', 'deliver'])) {
                match ($action) {
                    'accept' => $this->marketService->acceptOrder($order),
                    'prepare' => $order->update(['order_status' => 'preparing', 'preparing_at' => now()]),
                    'ready' => $order->update(['order_status' => 'ready', 'ready_at' => now()]),
                    'deliver' => $order->update(['order_status' => 'delivering']),
                };
            }

            // ผู้ซื้อ actions
            if ($isBuyer && $action === 'confirm') {
                $this->marketService->completeOrder($order);
            }

            if (($isBuyer || $isSeller) && $action === 'cancel') {
                $this->marketService->cancelOrder(
                    $order,
                    $request->get('cancel_reason'),
                    $isBuyer ? 'buyer' : 'seller'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทสถานะสำเร็จ',
                'data' => [
                    'order_status' => $order->fresh()->order_status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/v1/fresh-market/seller/orders
     * ออเดอร์ของผู้ขาย
     */
    public function sellerOrders(Request $request): JsonResponse
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->first();

        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ขาย'], 403);
        }

        $query = FreshMarketOrder::where('seller_id', $seller->id)
            ->with(['buyer:id,name', 'listing:id,title,main_image_url']);

        if ($request->filled('status')) {
            $query->where('order_status', $request->get('status'));
        }

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders->through(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'buyer_name' => $o->buyer?->name,
                'listing_title' => $o->listing?->title,
                'quantity' => $o->quantity,
                'total_amount' => $o->total_amount,
                'seller_earning' => $o->seller_earning,
                'order_status' => $o->order_status,
                'delivery_type' => $o->delivery_type,
                'created_at' => $o->created_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/fresh-market/seller/dashboard
     * สถิติผู้ขาย
     */
    public function sellerDashboard(): JsonResponse
    {
        $seller = FreshMarketSeller::where('user_id', auth()->id())->first();

        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ขาย'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shop_name' => $seller->shop_name,
                'is_verified' => $seller->is_verified,
                'subscription_type' => $seller->subscription_type,
                'subscription_expires_at' => $seller->subscription_expires_at?->toISOString(),
                'stats' => [
                    'total_listings' => $seller->total_listings,
                    'total_sales' => $seller->total_sales,
                    'total_revenue' => $seller->total_revenue,
                    'rating_average' => $seller->rating_average,
                    'rating_count' => $seller->rating_count,
                    'pending_orders' => FreshMarketOrder::where('seller_id', $seller->id)->pending()->count(),
                ],
            ],
        ]);
    }
}
