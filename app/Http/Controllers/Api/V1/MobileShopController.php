<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\VendorStore;
use App\Services\Shop\ShopCartService;
use App\Services\Shop\ShopCheckoutService;
use App\Services\Shop\ShopPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * ร้านค้าในแอปมือถือ: สินค้า / ร้าน / ตะกร้า / checkout (ย้ายออกจาก MobileApiController 2026-09-25)
 *
 * แก้ audit: SHOP-01..05, 08, 09, 16..19, 21..23, 29
 * - ตะกร้าใน DB คือแหล่งข้อมูลเดียว — ทุก action ตอบตะกร้าทั้งใบกลับไป (แอปแทน state ด้วยค่านี้)
 * - รายการสินค้ากรองสินค้าที่ซ่อน/ถูกบล็อก/ยังไม่เผยแพร่/ร้านถูกระงับออก · sort มี whitelist · per_page ไม่เกิน 50
 * - ไม่ส่ง PV/คอมมิชชั่น และไม่มีเรตติ้ง/รีวิวสมมติ
 */
class MobileShopController extends Controller
{
    private const SORTS = [
        'created_at' => 'created_at',
        'newest' => 'created_at',
        'price' => 'price',
        'name' => 'name',
        'sales_count' => 'sales_count',
        'popular' => 'sales_count',
        'rating' => 'rating_average',
    ];

    public function __construct(
        private readonly ShopCartService $carts,
        private readonly ShopCheckoutService $checkoutService,
    ) {}

    // =====================================================
    // สินค้า
    // =====================================================

    /**
     * GET /api/v1/products?category=&search=&store_id=&sort=&order=&per_page=&page=
     */
    public function products(Request $request): JsonResponse
    {
        $query = $this->catalogQuery()->with(['category', 'store']);

        if ($request->filled('category')) {
            $query->where('category_id', (int) $request->input('category'));
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $this->applySearch($query, $request->input('search'));
        $this->applySort($query, $request);

        $products = $query->paginate($this->perPage($request), ['*'], 'page', max(1, (int) $request->input('page', 1)));

        return response()->json([
            'success' => true,
            'data' => $products->getCollection()->map(fn (Product $p) => ShopPresenter::product($p))->values(),
            'pagination' => ShopPresenter::pagination($products),
        ]);
    }

    /**
     * GET /api/v1/products/categories
     */
    public function categories(): JsonResponse
    {
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products as products_count' => fn ($q) => $q->publicVisible()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'slug' => (string) $c->slug,
                'icon' => $c->icon,
                'image' => ShopPresenter::imageUrl($c->image_url),
                'parent_id' => $c->parent_id ? (int) $c->parent_id : null,
                'products_count' => (int) $c->products_count,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /**
     * GET /api/v1/products/{id} — รายละเอียดสินค้า (SHOP-05: ใช้ shape เดียวกับรายการ + ข้อมูลเพิ่ม)
     */
    public function product(int $id): JsonResponse
    {
        $product = $this->catalogQuery()
            ->with(['category', 'store', 'images'])
            ->find($id);

        if (! $product) {
            return ShopException::make(ShopException::PRODUCT_NOT_FOUND, 'ไม่พบสินค้า หรือสินค้านี้ปิดการขายแล้ว', 404)->toJsonResponse();
        }

        $store = $product->resolveStore();

        $reviews = ProductReview::where('product_id', $product->id)
            ->where('is_approved', true)
            ->with('user:id,name')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (ProductReview $r) => [
                'id' => (int) $r->id,
                'rating' => (int) $r->rating,
                'title' => $r->title,
                'comment' => $r->comment,
                'reviewer' => $this->maskName($r->user?->name),
                'is_verified_purchase' => (bool) $r->is_verified_purchase,
                'seller_response' => $r->seller_response,
                'created_at' => $r->created_at?->toISOString(),
            ])
            ->values();

        $data = ShopPresenter::product($product, $store) + [
            'description_full' => $product->description,
            'attributes' => $product->getAttribute('attributes'),
            'weight' => $product->weight !== null ? (float) $product->weight : null,
            'reviews' => $reviews,
            'store_detail' => $store ? ShopPresenter::store($store) : null,
            'purchase_block_reason' => $product->purchaseBlockReason(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    // =====================================================
    // ร้านค้า
    // =====================================================

    /**
     * GET /api/v1/mobile/stores/official — ร้านที่ยืนยันตัวตนแล้ว
     */
    public function officialStores(): JsonResponse
    {
        $stores = $this->storeQuery()
            ->where('is_verified', true)
            ->orderByDesc('rating_average')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stores->map(fn (VendorStore $s) => $this->storeListItem($s, true))->values(),
        ]);
    }

    /**
     * GET /api/v1/mobile/stores/featured — ร้านแนะนำที่แอดมินเลือก
     */
    public function featuredStores(): JsonResponse
    {
        $stores = $this->storeQuery()
            ->where('is_featured_home', true)
            ->orderBy('featured_home_order')
            ->orderByDesc('rating_average')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stores->map(fn (VendorStore $s) => $this->storeListItem($s, (bool) $s->is_verified))->values(),
        ]);
    }

    /**
     * GET /api/v1/mobile/stores/{storeId}
     */
    public function store(string $storeId): JsonResponse
    {
        $store = $this->storeQuery()->find((int) $storeId);
        if (! $store) {
            return ShopException::make(ShopException::STORE_NOT_FOUND, 'ไม่พบร้านค้า', 404)->toJsonResponse();
        }

        return response()->json([
            'success' => true,
            'data' => ShopPresenter::store($store, $store->public_products_count ?? null),
        ]);
    }

    /**
     * GET /api/v1/mobile/stores/{storeId}/products?page=&per_page= (SHOP-18)
     */
    public function storeProducts(Request $request, string $storeId): JsonResponse
    {
        $store = $this->storeQuery()->find((int) $storeId);
        if (! $store) {
            return ShopException::make(ShopException::STORE_NOT_FOUND, 'ไม่พบร้านค้า', 404)->toJsonResponse();
        }

        $query = $this->catalogQuery()
            ->with(['category', 'store'])
            ->where(function (Builder $q) use ($store) {
                $q->where('store_id', $store->id)
                    ->orWhere(fn (Builder $q2) => $q2->whereNull('store_id')->where('seller_id', $store->user_id));
            });

        $this->applySearch($query, $request->input('search'));
        $this->applySort($query, $request);

        $products = $query->paginate($this->perPage($request), ['*'], 'page', max(1, (int) $request->input('page', 1)));

        return response()->json([
            'success' => true,
            'data' => $products->getCollection()->map(fn (Product $p) => ShopPresenter::product($p, $store))->values(),
            'pagination' => ShopPresenter::pagination($products),
        ]);
    }

    /**
     * GET /api/v1/mobile/premium-store — ร้านทางการของแพลตฟอร์ม (ตัวเลขจริง ไม่มีค่าสมมติ)
     */
    public function premiumStore(): JsonResponse
    {
        $sellerId = Product::getOfficialSellerId();

        $official = $this->catalogQuery()->where('seller_id', $sellerId);
        $ratingCount = (int) (clone $official)->sum('rating_count');
        $weighted = (float) (clone $official)->selectRaw('COALESCE(SUM(rating_average * rating_count), 0) as w')->value('w');

        $features = config('shop.official_shop.features');
        if (! is_array($features) || $features === []) {
            $features = ['ร้านค้าทางการของแพลตฟอร์ม', 'ชำระเงินปลอดภัยผ่านระบบ'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => 'premium',
                'sellerId' => $sellerId,
                'seller_id' => $sellerId,
                'name' => config('shop.official_shop.name', 'Thaiprompt Shop'),
                'description' => config('shop.official_shop.description', 'ร้านค้าทางการของระบบ'),
                'logo' => config('shop.official_shop.logo') ?: asset('images/premium-store-logo.png'),
                'banner' => config('shop.official_shop.banner') ?: asset('images/premium-store-banner.png'),
                'rating' => $ratingCount > 0 ? round($weighted / $ratingCount, 2) : 0.0,
                'ratingCount' => $ratingCount,
                'rating_count' => $ratingCount,
                'isOfficial' => true,
                'isPremium' => true,
                'productCount' => $this->catalogQuery()->count(),
                'officialProductCount' => (clone $official)->count(),
                'featuredCount' => $this->catalogQuery()->where('is_featured', true)->count(),
                'verified' => true,
                'features' => array_values(array_filter($features, 'is_string')),
            ],
        ]);
    }

    /**
     * GET /api/v1/mobile/premium-store/products?page=&limit=&category=&featured=&search=&official_only=
     */
    public function premiumStoreProducts(Request $request): JsonResponse
    {
        $sellerId = Product::getOfficialSellerId();
        $query = $this->catalogQuery()->with(['category', 'store']);

        if ($request->boolean('official_only')) {
            $query->where('seller_id', $sellerId);
        } else {
            $query->orderByRaw('CASE WHEN seller_id = ? THEN 0 ELSE 1 END', [$sellerId]);
        }

        if ($request->filled('category')) {
            $query->where('category_id', (int) $request->input('category'));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $this->applySearch($query, $request->input('search'));

        $query->orderByDesc('is_featured')->orderByDesc('created_at');

        $products = $query->paginate($this->perPage($request, 10), ['*'], 'page', max(1, (int) $request->input('page', 1)));

        return response()->json([
            'success' => true,
            'data' => $products->getCollection()->map(fn (Product $p) => ShopPresenter::product($p))->values(),
            'pagination' => ShopPresenter::pagination($products),
        ]);
    }

    // =====================================================
    // ตะกร้า
    // =====================================================

    /**
     * GET /api/v1/cart?address_id=&delivery_method=parcel|rider&coupon_code=
     */
    public function cart(Request $request): JsonResponse
    {
        return $this->respond(fn () => $this->cartPayload($request));
    }

    /**
     * POST /api/v1/cart/items {product_id, quantity?, attributes?} (และ POST /cart/add เดิม)
     */
    public function addItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|min:1',
            'quantity' => 'nullable|integer|min:1|max:'.ShopCartService::MAX_LINE_QUANTITY,
            'attributes' => 'nullable|array|max:10',
        ], [
            'product_id.required' => 'กรุณาระบุสินค้า',
            'quantity.min' => 'จำนวนต้องมากกว่า 0',
            'quantity.max' => 'จำนวนต้องไม่เกิน '.ShopCartService::MAX_LINE_QUANTITY.' ชิ้น',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        return $this->respond(function () use ($request) {
            $this->carts->addItem(
                $request->user(),
                (int) $request->input('product_id'),
                (int) $request->input('quantity', 1),
                $request->input('attributes')
            );

            return $this->cartPayload($request, 'เพิ่มสินค้าลงตะกร้าแล้ว');
        });
    }

    /**
     * PUT /api/v1/cart/items/{itemId} {quantity} (0 = ลบ)
     */
    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:0|max:'.ShopCartService::MAX_LINE_QUANTITY,
        ], [
            'quantity.required' => 'กรุณาระบุจำนวน',
            'quantity.min' => 'จำนวนต้องไม่ต่ำกว่า 0',
            'quantity.max' => 'จำนวนต้องไม่เกิน '.ShopCartService::MAX_LINE_QUANTITY.' ชิ้น',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        return $this->respond(function () use ($request, $itemId) {
            $this->carts->updateItem($request->user(), $itemId, (int) $request->input('quantity'));

            return $this->cartPayload($request, 'อัปเดตตะกร้าแล้ว');
        });
    }

    /**
     * DELETE /api/v1/cart/items/{itemId}
     */
    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        return $this->respond(function () use ($request, $itemId) {
            $this->carts->removeItem($request->user(), $itemId);

            return $this->cartPayload($request, 'ลบสินค้าออกจากตะกร้าแล้ว');
        });
    }

    /**
     * DELETE /api/v1/cart (และ DELETE /cart/clear เดิม)
     */
    public function clear(Request $request): JsonResponse
    {
        return $this->respond(function () use ($request) {
            $this->carts->clear($request->user());

            return $this->cartPayload($request, 'ล้างตะกร้าเรียบร้อย');
        });
    }

    /**
     * POST /api/v1/cart/promo {code, address_id?, delivery_method?} — ตรวจโค้ดกับตาราง coupons จริง
     */
    public function applyPromo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'address_id' => 'nullable|integer',
            'delivery_method' => 'nullable|in:parcel,rider',
        ], [
            'code.required' => 'กรุณาใส่โค้ดส่วนลด',
            'code.max' => 'โค้ดส่วนลดยาวเกินไป',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        return $this->respond(function () use ($request) {
            $quote = $this->carts->quote($request->user(), [
                'address_id' => $request->input('address_id'),
                'delivery_method' => $request->input('delivery_method'),
                'coupon_code' => $request->input('code'),
            ]);

            if ($quote['items'] === []) {
                throw ShopException::make(ShopException::CART_EMPTY, 'ไม่มีสินค้าในตะกร้า', 409);
            }

            if ($quote['coupon'] === null) {
                $error = $quote['coupon_error'] ?? ['code' => ShopException::COUPON_INVALID, 'message' => 'โค้ดส่วนลดใช้ไม่ได้'];

                throw ShopException::make($error['code'], $error['message'], 422);
            }

            return [
                'message' => 'ใช้โค้ดส่วนลดได้ ลด '.number_format((float) $quote['coupon']['discount'], 2).' บาท',
                'data' => $quote,
            ];
        });
    }

    /**
     * POST /api/v1/cart/checkout
     * body: {address_id, payment_method: wallet|promptpay|cod, delivery_method?: parcel|rider, coupon_code?, note?}
     * header (ไม่บังคับ): Idempotency-Key
     */
    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'nullable|integer|min:1',
            'shipping_address_id' => 'nullable|integer|min:1',
            'payment_method' => 'required|string|max:30',
            'delivery_method' => 'nullable|in:parcel,rider',
            'coupon_code' => 'nullable|string|max:50',
            'promo_code' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:500',
        ], [
            'payment_method.required' => 'กรุณาเลือกวิธีชำระเงิน',
            'delivery_method.in' => 'วิธีจัดส่งไม่ถูกต้อง',
            'note.max' => 'หมายเหตุยาวเกิน 500 ตัวอักษร',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = $request->user();

        try {
            $result = $this->checkoutService->checkout($user, [
                'address_id' => $request->input('address_id') ?? $request->input('shipping_address_id'),
                'payment_method' => (string) $request->input('payment_method'),
                'delivery_method' => $request->input('delivery_method', 'parcel'),
                'coupon_code' => $request->input('coupon_code') ?? $request->input('promo_code'),
                'note' => $request->input('note'),
            ], $request->header('Idempotency-Key'));

            return response()->json([
                'success' => true,
                'message' => match ($result['payment_status']) {
                    'paid' => 'สั่งซื้อและชำระเงินสำเร็จ',
                    'cod' => 'สั่งซื้อสำเร็จ ชำระเงินสดกับไรเดอร์เมื่อได้รับสินค้า',
                    default => 'สร้างคำสั่งซื้อแล้ว กรุณาชำระเงินด้วยพร้อมเพย์',
                },
                'data' => $result,
            ], 201);
        } catch (ShopException $e) {
            return $e->toJsonResponse();
        } catch (\Throwable $e) {
            Log::error('Mobile shop checkout failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 2000),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'CHECKOUT_FAILED',
                'message' => 'ไม่สามารถสร้างคำสั่งซื้อได้ กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * สินค้าที่แสดงต่อสาธารณะได้: active + เผยแพร่แล้ว + ไม่ซ่อน + ไม่ถูกบล็อก + ร้านไม่ถูกระงับ
     */
    private function catalogQuery(): Builder
    {
        return Product::query()
            ->publicVisible()
            ->where(function (Builder $q) {
                $q->whereNull('store_id')
                    ->orWhereHas('store', fn (Builder $s) => $s->where('is_active', true)->whereNotIn('status', ['suspended', 'closed']));
            });
    }

    /**
     * ร้านที่เปิดให้บริการ + จำนวนสินค้าที่แสดงได้จริง
     */
    private function storeQuery(): Builder
    {
        return VendorStore::query()
            ->where('is_active', true)
            ->whereNotIn('status', ['suspended', 'closed'])
            ->withCount(['products as public_products_count' => fn ($q) => $q->publicVisible()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function storeListItem(VendorStore $store, bool $official): array
    {
        return [
            'id' => (string) $store->id,
            'name' => (string) $store->store_name,
            'logo' => ShopPresenter::imageUrl($store->store_logo),
            'rating' => round((float) ($store->rating_average ?? 0), 2),
            'rating_count' => (int) ($store->rating_count ?? 0),
            'isOfficial' => $official,
            'isFeatured' => (bool) $store->is_featured_home,
            'productCount' => (int) ($store->public_products_count ?? 0),
            'rider_delivery' => $store->canUseRiderDelivery(),
        ];
    }

    private function applySearch(Builder $query, $search): void
    {
        $search = is_string($search) ? trim(mb_substr($search, 0, 100)) : '';
        if ($search === '') {
            return;
        }

        $like = '%'.addcslashes($search, '%_\\').'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('short_description', 'like', $like)
                ->orWhere('brand', 'like', $like);
        });
    }

    private function applySort(Builder $query, Request $request): void
    {
        $column = self::SORTS[(string) $request->input('sort', 'created_at')] ?? 'created_at';
        $direction = strtolower((string) $request->input('order', $column === 'price' ? 'asc' : 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderByDesc('id');
    }

    private function perPage(Request $request, int $default = 20): int
    {
        $value = $request->input('per_page', $request->input('limit', $default));

        return max(1, min(50, (int) $value));
    }

    /**
     * @return array{message: string, data: array<string, mixed>}
     */
    private function cartPayload(Request $request, string $message = 'ดึงข้อมูลตะกร้าสำเร็จ'): array
    {
        return [
            'message' => $message,
            'data' => $this->carts->quote($request->user(), [
                'address_id' => $request->input('address_id'),
                'delivery_method' => $request->input('delivery_method'),
                'coupon_code' => $request->input('coupon_code'),
            ]),
        ];
    }

    /**
     * รันงานแล้วตอบ JSON มาตรฐาน — ShopException → code+ข้อความไทย, อื่นๆ → log แล้วตอบข้อความกลาง
     *
     * @param  callable(): array{message: string, data: mixed}  $work
     */
    private function respond(callable $work): JsonResponse
    {
        try {
            $result = $work();

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ]);
        } catch (ShopException $e) {
            return $e->toJsonResponse();
        } catch (\Throwable $e) {
            Log::error('Mobile shop cart error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'code' => 'CART_ERROR',
                'message' => 'ไม่สามารถดำเนินการกับตะกร้าได้ กรุณาลองใหม่',
            ], 500);
        }
    }

    private function validationError($validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'VALIDATION_ERROR',
            'message' => $validator->errors()->first() ?: 'ข้อมูลไม่ถูกต้อง',
            'errors' => $validator->errors(),
        ], 422);
    }

    private function maskName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'ผู้ซื้อ';
        }

        $first = mb_substr($name, 0, 1);

        return $first.str_repeat('*', max(2, min(5, mb_strlen($name) - 1)));
    }
}
