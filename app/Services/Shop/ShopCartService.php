<?php

namespace App\Services\Shop;

use App\Exceptions\RiderJobException;
use App\Exceptions\ShopException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\DeliveryFeeCalculator;
use App\Services\ShippingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ตะกร้าสินค้าฝั่ง server ของแอป (SHOP-03 — ตะกร้าใน DB เป็นแหล่งข้อมูลเดียว)
 *
 * - ราคา/ค่าส่ง/ส่วนลดคำนวณที่ server ทั้งหมด แอปแสดงตามที่ได้รับเท่านั้น
 * - แบ่งตะกร้าเป็นกลุ่มตามร้าน (1 ร้าน = 1 ออเดอร์ตอน checkout) เพื่อให้ร้านเห็นเฉพาะของตัวเอง
 *   และส่งด้วยไรเดอร์ได้ (ไรเดอร์ 1 งาน = รับของ 1 ร้าน)
 * - ค่าส่งพัสดุ = ShippingService (สูตรเดียวกับเว็บ) · ค่าส่งไรเดอร์ = DeliveryFeeCalculator (ระยะทางจริง)
 */
class ShopCartService
{
    /** จำนวนสูงสุดต่อ 1 รายการในตะกร้า */
    public const MAX_LINE_QUANTITY = 99;

    /** จำนวนรายการสูงสุดในตะกร้า (กันตะกร้าบวมจนคำนวณช้า) */
    public const MAX_LINES = 50;

    public function __construct(
        private readonly ShippingService $shipping,
        private readonly DeliveryFeeCalculator $deliveryFees,
        private readonly CouponService $coupons,
    ) {}

    // =====================================================
    // แก้ไขตะกร้า
    // =====================================================

    /**
     * ตะกร้าของผู้ใช้ (สร้างให้ถ้ายังไม่มี)
     */
    public function cartFor(User $user): Cart
    {
        $cart = Cart::where('user_id', $user->id)->orderBy('id')->first();

        if ($cart) {
            return $cart;
        }

        try {
            return Cart::create(['user_id' => $user->id, 'session_id' => null]);
        } catch (\Illuminate\Database\QueryException $e) {
            // สร้างพร้อมกัน 2 คำขอ → unique(user_id, deleted_at) ไม่ได้กันเพราะ NULL — อ่านอีกครั้ง
            $existing = Cart::where('user_id', $user->id)->orderBy('id')->first();
            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * เพิ่มสินค้าลงตะกร้า (สินค้า+ตัวเลือกเดียวกันรวมเป็นแถวเดียว)
     *
     * @param  array<string, mixed>|null  $attributes  ตัวเลือกสินค้า เช่น {"สี":"แดง"}
     *
     * @throws ShopException PRODUCT_NOT_FOUND|PRODUCT_UNAVAILABLE|AFFILIATE_PRODUCT|OUT_OF_STOCK
     */
    public function addItem(User $user, int $productId, int $quantity, ?array $attributes = null): CartItem
    {
        $quantity = max(1, min(self::MAX_LINE_QUANTITY, $quantity));
        $canonical = $this->canonicalAttributes($attributes);
        $cart = $this->cartFor($user);

        return DB::transaction(function () use ($cart, $productId, $quantity, $canonical) {
            Cart::whereKey($cart->id)->lockForUpdate()->first();

            $product = Product::whereKey($productId)->lockForUpdate()->first();
            if (! $product) {
                throw ShopException::make(ShopException::PRODUCT_NOT_FOUND, 'ไม่พบสินค้านี้', 404);
            }

            $this->assertPurchasable($product);

            $lines = CartItem::where('cart_id', $cart->id)->get();

            if ($lines->count() >= self::MAX_LINES && ! $lines->contains(fn ($l) => (int) $l->product_id === (int) $product->id)) {
                throw ShopException::make(ShopException::ACTION_NOT_ALLOWED, 'ตะกร้ามีสินค้าครบ '.self::MAX_LINES.' รายการแล้ว กรุณาสั่งซื้อหรือลบบางรายการก่อน', 422);
            }

            $existing = $lines->first(function (CartItem $line) use ($product, $canonical) {
                return (int) $line->product_id === (int) $product->id
                    && $this->canonicalAttributes($line->getAttribute('attributes')) === $canonical;
            });

            $newLineQty = min(self::MAX_LINE_QUANTITY, ($existing ? (int) $existing->quantity : 0) + $quantity);
            $otherLinesQty = (int) $lines
                ->filter(fn ($l) => (int) $l->product_id === (int) $product->id && (! $existing || $l->id !== $existing->id))
                ->sum('quantity');

            $this->assertStock($product, $otherLinesQty + $newLineQty);

            $price = round((float) $product->price, 2);
            $decoded = $canonical !== null ? json_decode($canonical, true) : null;

            if ($existing) {
                $existing->update(['quantity' => $newLineQty, 'price' => $price]);

                return $existing->fresh();
            }

            return CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $newLineQty,
                'price' => $price,
                'attributes' => $decoded,
            ]);
        });
    }

    /**
     * ตั้งจำนวนของรายการในตะกร้า (0 = ลบ)
     *
     * @throws ShopException CART_ITEM_NOT_FOUND|OUT_OF_STOCK|PRODUCT_UNAVAILABLE
     */
    public function updateItem(User $user, int $itemId, int $quantity): void
    {
        $quantity = max(0, min(self::MAX_LINE_QUANTITY, $quantity));

        DB::transaction(function () use ($user, $itemId, $quantity) {
            $item = $this->ownedItem($user, $itemId, true);

            if ($quantity === 0) {
                $item->forceDelete();

                return;
            }

            $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
            if (! $product) {
                $item->forceDelete();
                throw ShopException::make(ShopException::PRODUCT_NOT_FOUND, 'สินค้านี้ถูกลบแล้ว จึงนำออกจากตะกร้าให้', 404);
            }

            // ลดจำนวนได้เสมอ (ให้ผู้ใช้ปรับให้พอดีสต็อก) · เพิ่มจำนวนต้องผ่านการตรวจ
            if ($quantity > (int) $item->quantity) {
                $this->assertPurchasable($product);

                $otherQty = (int) CartItem::where('cart_id', $item->cart_id)
                    ->where('product_id', $item->product_id)
                    ->where('id', '!=', $item->id)
                    ->sum('quantity');

                $this->assertStock($product, $otherQty + $quantity);
            }

            $item->update(['quantity' => $quantity, 'price' => round((float) $product->price, 2)]);
        });
    }

    /**
     * ลบรายการออกจากตะกร้า
     *
     * @throws ShopException CART_ITEM_NOT_FOUND
     */
    public function removeItem(User $user, int $itemId): void
    {
        $this->ownedItem($user, $itemId)->forceDelete();
    }

    /**
     * ล้างตะกร้า (ลบจริง — soft delete หลายแถวพร้อมกันชน unique(cart_id, product_id, deleted_at))
     */
    public function clear(User $user): int
    {
        $cart = Cart::where('user_id', $user->id)->first();
        if (! $cart) {
            return 0;
        }

        return CartItem::withTrashed()->where('cart_id', $cart->id)->forceDelete();
    }

    // =====================================================
    // คำนวณตะกร้า
    // =====================================================

    /**
     * รายการในตะกร้าพร้อมสินค้า (รวมสินค้าที่ถูกลบ เพื่อแจ้งผู้ใช้ว่าไม่พร้อมขาย)
     *
     * @return Collection<int, CartItem>
     */
    public function lines(Cart $cart, bool $lock = false): Collection
    {
        $query = CartItem::where('cart_id', $cart->id)->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        $items = $query->get();
        if ($items->isEmpty()) {
            return $items;
        }

        $productQuery = Product::withTrashed()
            ->with(['store', 'category'])
            ->whereIn('id', $items->pluck('product_id')->unique()->all())
            ->orderBy('id');
        if ($lock) {
            $productQuery->lockForUpdate();
        }
        $products = $productQuery->get()->keyBy('id');

        return $items->each(fn (CartItem $item) => $item->setRelation('product', $products->get($item->product_id)));
    }

    /**
     * เหตุผลที่รายการนี้สั่งไม่ได้ (null = สั่งได้)
     *
     * @param  int  $productTotalQty  จำนวนรวมของสินค้านี้ทุกแถวในตะกร้า
     */
    public function lineBlockReason(CartItem $item, int $productTotalQty): ?string
    {
        $product = $item->product;
        if (! $product) {
            return 'สินค้านี้ถูกลบแล้ว';
        }

        $reason = $product->purchaseBlockReason();
        if ($reason !== null) {
            return $reason;
        }

        if (! $product->isInStock()) {
            return 'สินค้าหมด';
        }

        if ($product->track_inventory && $productTotalQty > (int) $product->stock_quantity) {
            return 'สินค้าเหลือ '.max(0, (int) $product->stock_quantity).' ชิ้น กรุณาลดจำนวน';
        }

        return null;
    }

    /**
     * แบ่งรายการที่สั่งได้เป็นกลุ่มตามร้าน พร้อมค่าส่งตามวิธีจัดส่ง
     *
     * @param  Collection<int, CartItem>  $lines  เฉพาะรายการที่สั่งได้
     * @return array<int, array<string, mixed>>
     */
    public function buildGroups(Collection $lines, ?ShippingAddress $address, string $deliveryMethod): array
    {
        $groups = [];

        foreach ($lines as $item) {
            $product = $item->product;
            $store = $product->resolveStore();
            $key = $store ? 'store:'.$store->id : 'seller:'.(int) $product->seller_id;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'store_id' => $store ? (int) $store->id : null,
                    'store' => $store,
                    'store_name' => $store ? (string) $store->store_name : 'ร้านค้าทางการ',
                    'seller_id' => (int) $product->seller_id,
                    'lines' => [],
                    'subtotal' => 0.0,
                    'has_physical' => false,
                ];
            }

            $unitPrice = round((float) $product->price, 2);
            $qty = (int) $item->quantity;

            $groups[$key]['lines'][] = [
                'line_key' => (int) $item->id,
                'cart_item' => $item,
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $qty, 2),
            ];
            $groups[$key]['subtotal'] = round($groups[$key]['subtotal'] + $unitPrice * $qty, 2);
            $groups[$key]['has_physical'] = $groups[$key]['has_physical'] || ! $product->is_virtual;
        }

        foreach ($groups as $key => $group) {
            $groups[$key] = $this->applyDelivery($group, $address, $deliveryMethod);
        }

        return array_values($groups);
    }

    /**
     * ค่าส่ง + ความพร้อมของไรเดอร์/COD ของกลุ่มร้าน
     *
     * @param  array<string, mixed>  $group
     * @return array<string, mixed>
     */
    private function applyDelivery(array $group, ?ShippingAddress $address, string $deliveryMethod): array
    {
        /** @var VendorStore|null $store */
        $store = $group['store'];

        // ความพร้อมของไรเดอร์ (คำนวณเสมอเพื่อให้แอปแสดงตัวเลือกได้)
        $rider = ['available' => false, 'reason' => null, 'fee' => null, 'distance_km' => null, 'estimated_minutes' => null];

        if (! $group['has_physical']) {
            $rider['reason'] = 'สินค้าดิจิทัลไม่ต้องจัดส่ง';
        } elseif (! $store || ! $store->canUseRiderDelivery()) {
            $rider['reason'] = 'ร้านนี้ยังไม่เปิดส่งด้วยไรเดอร์';
        } elseif (! $address) {
            $rider['reason'] = 'กรุณาเลือกที่อยู่จัดส่ง';
        } elseif (! $address->hasLocation()) {
            $rider['reason'] = 'ที่อยู่นี้ยังไม่ได้ปักหมุดตำแหน่ง';
        } else {
            try {
                $quote = $this->deliveryFees->quote(
                    (float) $store->pickup_latitude,
                    (float) $store->pickup_longitude,
                    (float) $address->latitude,
                    (float) $address->longitude
                );

                $rider['distance_km'] = (float) $quote['distance_km'];
                $rider['estimated_minutes'] = (int) $quote['estimated_duration_minutes'];

                if ($quote['within_service_area']) {
                    $rider['available'] = true;
                    $rider['fee'] = round((float) $quote['total_fee'], 2);
                } else {
                    $rider['reason'] = 'ที่อยู่อยู่นอกพื้นที่ส่งของไรเดอร์ (ไม่เกิน '.number_format((float) $quote['max_distance_km'], 0).' กม.)';
                }
            } catch (RiderJobException $e) {
                $rider['reason'] = $e->getMessage();
            } catch (\Throwable $e) {
                Log::warning('ShopCart: rider quote failed', ['store_id' => $store->id, 'error' => $e->getMessage()]);
                $rider['reason'] = 'คำนวณค่าส่งไรเดอร์ไม่ได้ในขณะนี้';
            }
        }

        // ค่าส่งพัสดุ (สูตรเดียวกับเว็บ)
        $parcelFee = 0.0;
        if ($group['has_physical']) {
            $shippingItems = collect($group['lines'])->map(fn ($line) => (object) [
                'product' => $line['product'],
                'quantity' => $line['quantity'],
            ]);
            $parcelFee = round((float) ($this->shipping->calculateForCart($shippingItems)['total_shipping'] ?? 0), 2);
        }

        $useRider = $deliveryMethod === 'rider' && $group['has_physical'];

        // COD: เฉพาะส่งด้วยไรเดอร์ (ไรเดอร์เก็บเงินแล้วส่งเข้าแพลตฟอร์มผ่านวอลเลต) และไม่เกินวงเงิน COD
        $codLimit = $this->deliveryFees->maxCodAmount();
        $riderTotal = $group['subtotal'] + (float) ($rider['fee'] ?? 0);
        $cod = ['available' => false, 'reason' => null, 'limit' => round($codLimit, 2)];
        if (! $rider['available']) {
            $cod['reason'] = 'เก็บเงินปลายทางใช้ได้เมื่อส่งด้วยไรเดอร์';
        } elseif ($riderTotal > $codLimit) {
            // เงื่อนไขเดียวกับ RiderDispatchService::createJobForSource (วงเงิน 0 = ปิดรับ COD)
            // ไม่งั้นสั่งได้แต่ร้านเรียกไรเดอร์ไม่ได้ ออเดอร์ค้าง
            $cod['reason'] = $codLimit > 0
                ? 'ยอดเกินวงเงินเก็บเงินปลายทาง '.number_format($codLimit, 2).' บาท'
                : 'ขณะนี้ปิดรับเก็บเงินปลายทาง';
        } else {
            $cod['available'] = true;
        }

        $group['delivery_method'] = $useRider ? 'rider' : 'parcel';
        $group['shipping_fee'] = $useRider ? (float) ($rider['fee'] ?? 0) : $parcelFee;
        $group['parcel_fee'] = $parcelFee;
        $group['rider'] = $rider;
        $group['cod'] = $cod;

        return $group;
    }

    /**
     * ตะกร้าพร้อมยอดรวมทั้งหมด (GET /cart, POST /cart/promo)
     *
     * @param  array{address_id?: int|null, delivery_method?: string|null, coupon_code?: string|null}  $opts
     * @return array<string, mixed>
     */
    public function quote(User $user, array $opts = []): array
    {
        $cart = $this->cartFor($user);
        $lines = $this->lines($cart);
        $deliveryMethod = ($opts['delivery_method'] ?? 'parcel') === 'rider' ? 'rider' : 'parcel';

        $address = null;
        if (! empty($opts['address_id'])) {
            $address = ShippingAddress::where('user_id', $user->id)->find((int) $opts['address_id']);
        }
        if (! $address) {
            $address = ShippingAddress::where('user_id', $user->id)
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->first();
        }

        $qtyByProduct = $lines->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));

        $itemsOut = [];
        $available = collect();
        foreach ($lines as $item) {
            $reason = $this->lineBlockReason($item, (int) ($qtyByProduct[$item->product_id] ?? 0));
            $product = $item->product;

            if ($reason === null) {
                $available->push($item);
            }

            $store = $product?->resolveStore();
            $unitPrice = $product ? round((float) $product->price, 2) : round((float) $item->price, 2);
            $compare = $product && $product->compare_at_price !== null ? round((float) $product->compare_at_price, 2) : null;

            $itemsOut[] = [
                'id' => (int) $item->id,
                'product_id' => (int) $item->product_id,
                'name' => $product?->name ?? 'สินค้าที่ถูกลบ',
                'image' => $product ? (ShopPresenter::productImages($product)[0] ?? null) : null,
                'unit_price' => $unitPrice,
                'original_price' => $compare !== null && $compare > $unitPrice ? $compare : null,
                'quantity' => (int) $item->quantity,
                'line_total' => round($unitPrice * (int) $item->quantity, 2),
                'attributes' => $item->getAttribute('attributes'),
                'stock' => $product && $product->track_inventory ? max(0, (int) $product->stock_quantity) : null,
                'max_quantity' => $product && $product->track_inventory
                    ? max(0, min(self::MAX_LINE_QUANTITY, (int) $product->stock_quantity))
                    : self::MAX_LINE_QUANTITY,
                'is_available' => $reason === null,
                'unavailable_reason' => $reason,
                'store' => $store ? ['id' => (int) $store->id, 'name' => (string) $store->store_name] : null,
            ];
        }

        $groups = $this->buildGroups($available, $address, $deliveryMethod);

        $coupon = null;
        $couponError = null;
        if (! empty($opts['coupon_code']) && $groups !== []) {
            try {
                $couponModel = $this->coupons->findUsable((string) $opts['coupon_code'], $user);
                $coupon = $this->coupons->evaluate($couponModel, $groups);
            } catch (ShopException $e) {
                $couponError = ['code' => $e->errorCode, 'message' => $e->getMessage()];
            }
        }

        $storesOut = [];
        $subtotal = 0.0;
        $shippingTotal = 0.0;
        $discountTotal = 0.0;
        foreach ($groups as $group) {
            $discount = ($coupon && $coupon['group_key'] === $group['key']) ? (float) $coupon['total_discount'] : 0.0;
            $total = round(max(0.0, $group['subtotal'] + $group['shipping_fee'] - $discount), 2);

            $subtotal += $group['subtotal'];
            $shippingTotal += $group['shipping_fee'];
            $discountTotal += $discount;

            $storesOut[] = [
                'key' => $group['key'],
                'store_id' => $group['store_id'],
                'store_name' => $group['store_name'],
                'items_count' => array_sum(array_column($group['lines'], 'quantity')),
                'subtotal' => round($group['subtotal'], 2),
                'delivery_method' => $group['delivery_method'],
                'shipping_fee' => round($group['shipping_fee'], 2),
                'parcel_fee' => round($group['parcel_fee'], 2),
                'discount' => round($discount, 2),
                'total' => $total,
                'rider' => $group['rider'],
                'cod' => $group['cod'],
            ];
        }

        $subtotal = round($subtotal, 2);
        $threshold = (float) ShippingService::DEFAULT_FREE_SHIPPING_THRESHOLD;

        return [
            'cart_id' => (int) $cart->id,
            'items' => $itemsOut,
            'stores' => $storesOut,
            'address' => $address ? [
                'id' => (int) $address->id,
                'recipient_name' => $address->recipient_name,
                'full_address' => $address->full_address,
                'has_location' => $address->hasLocation(),
            ] : null,
            'delivery_method' => $deliveryMethod,
            'coupon' => $coupon ? [
                'code' => $coupon['code'],
                'store_id' => $coupon['store_id'],
                'discount_type' => $coupon['discount_type'],
                'discount' => round((float) $coupon['total_discount'], 2),
            ] : null,
            'coupon_error' => $couponError,
            'summary' => [
                'items_count' => (int) $lines->sum('quantity'),
                'available_items_count' => (int) $available->sum('quantity'),
                'unavailable_count' => count($itemsOut) - $available->count(),
                'subtotal' => $subtotal,
                'shipping_fee' => round($shippingTotal, 2),
                'discount' => round($discountTotal, 2),
                'grand_total' => round(max(0.0, $subtotal + $shippingTotal - $discountTotal), 2),
                'free_shipping_threshold' => $threshold,
                'amount_to_free_shipping' => round(max(0.0, $threshold - $subtotal), 2),
                'rider_available' => $groups !== [] && collect($groups)->every(fn ($g) => ! $g['has_physical'] || $g['rider']['available']),
                'cod_available' => $groups !== [] && collect($groups)->every(fn ($g) => $g['cod']['available']),
            ],
        ];
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * ตรวจว่าสินค้าสั่งซื้อได้
     *
     * @throws ShopException
     */
    public function assertPurchasable(Product $product): void
    {
        if ($product->is_affiliate) {
            throw ShopException::make(
                ShopException::AFFILIATE_PRODUCT,
                'สินค้านี้ต้องสั่งซื้อที่ร้านต้นทาง',
                422,
                ['product_id' => (int) $product->id, 'affiliate_url' => $product->affiliate_url]
            );
        }

        $reason = $product->purchaseBlockReason();
        if ($reason !== null) {
            throw ShopException::make(ShopException::PRODUCT_UNAVAILABLE, $reason, 409, ['product_id' => (int) $product->id]);
        }
    }

    /**
     * ตรวจสต็อกกับจำนวนรวมที่ต้องการ
     *
     * @throws ShopException OUT_OF_STOCK
     */
    public function assertStock(Product $product, int $totalQty): void
    {
        if (! $product->isInStock()) {
            throw ShopException::make(ShopException::OUT_OF_STOCK, "สินค้า '{$product->name}' หมดแล้ว", 409, [
                'product_id' => (int) $product->id,
                'available' => 0,
            ]);
        }

        if ($product->track_inventory && $totalQty > (int) $product->stock_quantity) {
            $left = max(0, (int) $product->stock_quantity);

            throw ShopException::make(ShopException::OUT_OF_STOCK, "สินค้า '{$product->name}' เหลือ {$left} ชิ้น", 409, [
                'product_id' => (int) $product->id,
                'available' => $left,
            ]);
        }
    }

    /**
     * รายการในตะกร้าของผู้ใช้คนนี้เท่านั้น (กัน IDOR)
     *
     * @throws ShopException CART_ITEM_NOT_FOUND
     */
    private function ownedItem(User $user, int $itemId, bool $lock = false): CartItem
    {
        $query = CartItem::whereKey($itemId)
            ->whereHas('cart', fn ($q) => $q->where('user_id', $user->id));

        if ($lock) {
            $query->lockForUpdate();
        }

        $item = $query->first();
        if (! $item) {
            throw ShopException::make(ShopException::CART_ITEM_NOT_FOUND, 'ไม่พบรายการสินค้านี้ในตะกร้า', 404);
        }

        return $item;
    }

    /**
     * ตัวเลือกสินค้าในรูป JSON ที่เทียบกันได้ (เรียง key) — ว่าง = null
     *
     * @param  mixed  $attributes
     */
    public function canonicalAttributes($attributes): ?string
    {
        if (is_string($attributes)) {
            $decoded = json_decode($attributes, true);
            $attributes = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($attributes) || $attributes === []) {
            return null;
        }

        $sort = function (array $value) use (&$sort): array {
            ksort($value);
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    $value[$k] = $sort($v);
                }
            }

            return $value;
        };

        return json_encode($sort($attributes), JSON_UNESCAPED_UNICODE);
    }
}
