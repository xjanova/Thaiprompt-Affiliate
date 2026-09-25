<?php

namespace App\Services\Shop;

use App\Exceptions\ShopException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Payment\PaymentService;
use App\Services\Pricing\PricingEngine;
use App\Services\WalletService;
use App\Support\Shop\PaymentMethod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * เส้นทางสร้างออเดอร์เส้นเดียวของแอปมือถือ (SHOP-01/02/03/06/08/09/21, G6)
 *
 * ลำดับ:
 *  1. ล็อกกันกดซ้ำ (ต่อผู้ใช้) + Idempotency-Key (ส่งซ้ำได้ผลเดิม 10 นาที)
 *  2. ใน transaction เดียว: ล็อกตะกร้า → ล็อกสินค้า → ตรวจสินค้า/สต็อก/ที่อยู่/ไรเดอร์/COD/คูปอง
 *     → สร้างออเดอร์แยกตามร้าน (order_items ใช้ PricingEngine::itemSnapshot = GP ณ เวลาซื้อ)
 *     → wallet: ตัดสต็อก + หักเงิน (WalletService::deductForService, ล็อกวอลเลต) + ตั้งจ่ายแล้ว
 *     → cod: ตัดสต็อก (จองของให้ร้านส่ง) สถานะรอร้านยืนยัน
 *     → promptpay: ยังไม่ตัดสต็อก (ตัดตอนระบบยืนยันเงินเข้า) สถานะรอชำระ
 *     → บันทึกการใช้คูปอง → ล้างตะกร้า
 *  3. หลัง commit: promptpay สร้าง QR ผ่าน PaymentService (ระบบ SMS Checker ยืนยันเงินเข้าเอง) · แจ้งร้าน/ผู้ซื้อ
 *
 * ❗ ออเดอร์ที่ยังไม่จ่ายเป็น pending เสมอ (ไม่ใช่ processing) — ยกเลิกแล้วไม่ถูกคืนเงิน
 */
class ShopCheckoutService
{
    /** อายุผลลัพธ์ที่เก็บไว้ตอบ Idempotency-Key ซ้ำ (วินาที) */
    private const IDEMPOTENCY_TTL = 600;

    public function __construct(
        private readonly ShopCartService $carts,
        private readonly CouponService $coupons,
        private readonly WalletService $wallets,
        private readonly PricingEngine $pricing,
        private readonly ShopOrderNotifier $notifier,
    ) {}

    /** ช่องทางที่สั่งซื้อ (บันทึกใน metadata ของรายการเงิน) */
    private string $source = 'mobile_app';

    /**
     * สั่งซื้อจากตะกร้าในระบบ
     *
     * $lineSource = แหล่งรายการอื่นแทนตะกร้าแอป (เช่นตะกร้าเว็บผ่าน WebCartLines::lockedSource)
     * ถูกเรียกภายใน transaction และต้องคืน [รายการที่ล็อกแล้ว, ฟังก์ชันล้างตะกร้า]
     *
     * @param  array{address_id?: int|null, payment_method: string, delivery_method?: string|null, coupon_code?: string|null, note?: string|null, source?: string|null}  $input
     * @param  (callable(): array{0: \Illuminate\Support\Collection<int, CartItem>, 1: callable(): void})|null  $lineSource
     * @return array<string, mixed> ข้อมูลตอบกลับ (orders + payment)
     *
     * @throws ShopException
     */
    public function checkout(User $user, array $input, ?string $idempotencyKey = null, ?callable $lineSource = null): array
    {
        $this->source = ($input['source'] ?? null) === 'web' ? 'web' : 'mobile_app';

        $paymentMethod = PaymentMethod::normalize((string) ($input['payment_method'] ?? ''));
        if (! in_array($paymentMethod, PaymentMethod::APP_CHECKOUT_VALUES, true)) {
            throw ShopException::make(ShopException::PAYMENT_METHOD_UNAVAILABLE, 'วิธีชำระเงินนี้ยังไม่รองรับในแอป', 422);
        }

        $deliveryMethod = ($input['delivery_method'] ?? 'parcel') === 'rider' ? 'rider' : 'parcel';

        $idemKey = $this->idempotencyCacheKey($user, $idempotencyKey);
        if ($idemKey !== null && ($cached = Cache::get($idemKey)) !== null) {
            return $cached;
        }

        if ($paymentMethod === PaymentMethod::PROMPTPAY && ! $this->isPromptPayAvailable()) {
            throw ShopException::make(ShopException::PAYMENT_METHOD_UNAVAILABLE, 'ชำระด้วยพร้อมเพย์ยังไม่พร้อมใช้งาน กรุณาเลือกวิธีอื่น', 422);
        }

        $lock = Cache::lock('shop:checkout:user:'.$user->id, 30);
        if (! $lock->get()) {
            throw ShopException::make(ShopException::CHECKOUT_IN_PROGRESS, 'กำลังดำเนินการสั่งซื้ออยู่ กรุณารอสักครู่', 409);
        }

        try {
            $orders = DB::transaction(fn () => $this->createOrders($user, $input, $paymentMethod, $deliveryMethod, $lineSource));

            $payments = [];
            if ($paymentMethod === PaymentMethod::PROMPTPAY) {
                foreach ($orders as $order) {
                    $payments[] = $this->startPromptPay($order);
                }
            }

            foreach ($orders as $order) {
                $this->notifier->orderPlaced($order);
            }

            $result = $this->presentResult($user, $orders, $paymentMethod, $payments);

            if ($idemKey !== null) {
                Cache::put($idemKey, $result, self::IDEMPOTENCY_TTL);
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    /**
     * สร้างออเดอร์ทั้งหมด (อยู่ใน transaction ของผู้เรียก)
     *
     * @return array<int, Order>
     */
    private function createOrders(User $user, array $input, string $paymentMethod, string $deliveryMethod, ?callable $lineSource = null): array
    {
        // 1) ล็อกตะกร้า + รายการ + สินค้า (กดสั่งพร้อมกัน 2 ครั้ง → ครั้งที่สองเห็นตะกร้าว่าง)
        if ($lineSource !== null) {
            // ตะกร้าจากแหล่งอื่น (หน้าเว็บ) — แหล่งล็อกแถวให้เองและบอกวิธีล้างตะกร้า
            [$lines, $clearCart] = $lineSource();
        } else {
            $cart = Cart::where('user_id', $user->id)->orderBy('id')->lockForUpdate()->first();
            if (! $cart) {
                throw ShopException::make(ShopException::CART_EMPTY, 'ไม่มีสินค้าในตะกร้า', 409);
            }

            $lines = $this->carts->lines($cart, true);
            $clearCart = fn () => CartItem::withTrashed()->where('cart_id', $cart->id)->forceDelete();
        }

        if ($lines->isEmpty()) {
            throw ShopException::make(ShopException::CART_EMPTY, 'ไม่มีสินค้าในตะกร้า', 409);
        }

        // 2) สินค้าทุกชิ้นต้องสั่งได้และสต็อกพอ (ตรวจหลังล็อกแล้ว)
        $qtyByProduct = $lines->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
        foreach ($lines as $item) {
            $product = $item->product;
            if (! $product) {
                throw ShopException::make(ShopException::PRODUCT_UNAVAILABLE, 'มีสินค้าในตะกร้าที่ถูกลบแล้ว กรุณานำออกก่อนสั่งซื้อ', 409, [
                    'cart_item_id' => (int) $item->id,
                ]);
            }

            $this->carts->assertPurchasable($product);
            $this->carts->assertStock($product, (int) $qtyByProduct[$item->product_id]);
        }

        // 3) ที่อยู่จัดส่ง (บังคับเมื่อมีสินค้าที่ต้องส่ง)
        $hasPhysical = $lines->contains(fn (CartItem $item) => ! $item->product->is_virtual);
        $address = null;
        if (! empty($input['address_id'])) {
            $address = ShippingAddress::where('user_id', $user->id)->find((int) $input['address_id']);
            if (! $address) {
                throw ShopException::make(ShopException::ADDRESS_NOT_FOUND, 'ไม่พบที่อยู่จัดส่งนี้', 404);
            }
        } elseif ($hasPhysical) {
            // แอปรุ่นก่อนไม่ส่ง address_id (ใช้ที่อยู่ในโปรไฟล์) → ใช้ที่อยู่หลักของผู้ใช้ / สร้างจากโปรไฟล์
            $address = $this->fallbackAddress($user);
        }
        if ($hasPhysical && ! $address) {
            throw ShopException::make(ShopException::ADDRESS_REQUIRED, 'กรุณาเลือกที่อยู่จัดส่ง', 422);
        }
        if ($deliveryMethod === 'rider' && $hasPhysical && ! $address->hasLocation()) {
            throw ShopException::make(ShopException::ADDRESS_LOCATION_REQUIRED, 'ส่งด้วยไรเดอร์ต้องปักหมุดตำแหน่งที่อยู่ก่อน', 422, [
                'address_id' => (int) $address->id,
            ]);
        }

        // 4) แบ่งตามร้าน + ค่าส่ง
        $groups = $this->carts->buildGroups($lines, $address, $deliveryMethod);

        foreach ($groups as $group) {
            if ($deliveryMethod === 'rider' && $group['has_physical'] && ! $group['rider']['available']) {
                throw ShopException::make(
                    ShopException::RIDER_NOT_AVAILABLE,
                    "ร้าน {$group['store_name']}: ".($group['rider']['reason'] ?? 'ส่งด้วยไรเดอร์ไม่ได้'),
                    422,
                    ['store_id' => $group['store_id']]
                );
            }

            if ($paymentMethod === PaymentMethod::COD && ! ($deliveryMethod === 'rider' && $group['cod']['available'])) {
                throw ShopException::make(
                    ShopException::COD_NOT_AVAILABLE,
                    $deliveryMethod === 'rider'
                        ? "ร้าน {$group['store_name']}: ".($group['cod']['reason'] ?? 'เก็บเงินปลายทางไม่ได้')
                        : 'เก็บเงินปลายทางใช้ได้เมื่อเลือกส่งด้วยไรเดอร์เท่านั้น',
                    422,
                    ['store_id' => $group['store_id']]
                );
            }
        }

        // 5) คูปอง (ตรวจกับตาราง coupons จริง)
        $coupon = null;
        if (! empty($input['coupon_code'])) {
            $couponModel = $this->coupons->findUsable((string) $input['coupon_code'], $user, true);
            $coupon = $this->coupons->evaluate($couponModel, $groups);
        }

        // 6) ยอดรวมแต่ละร้าน
        $plans = [];
        $grandTotal = 0.0;
        foreach ($groups as $group) {
            $isCouponGroup = $coupon !== null && $coupon['group_key'] === $group['key'];
            $productDiscount = $isCouponGroup ? (float) $coupon['product_discount'] : 0.0;
            $shippingDiscount = $isCouponGroup ? min((float) $coupon['shipping_discount'], (float) $group['shipping_fee']) : 0.0;
            $total = round(max(0.0, $group['subtotal'] + $group['shipping_fee'] - $productDiscount - $shippingDiscount), 2);

            $plans[] = [
                'group' => $group,
                'product_discount' => round($productDiscount, 2),
                'shipping_discount' => round($shippingDiscount, 2),
                'total' => $total,
                'line_discounts' => $isCouponGroup ? $coupon['line_discounts'] : [],
            ];
            $grandTotal += $total;
        }
        $grandTotal = round($grandTotal, 2);

        // 7) วอลเลต: ล็อกแล้วตรวจยอดก่อนสร้างอะไร
        $wallet = null;
        if ($paymentMethod === PaymentMethod::WALLET) {
            $wallet = $this->wallets->getOrCreateWallet($user);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if (! $wallet || ! $wallet->isActive()) {
                throw ShopException::make(ShopException::WALLET_INACTIVE, 'กระเป๋าเงินของคุณใช้งานไม่ได้ในขณะนี้ กรุณาติดต่อเจ้าหน้าที่', 403);
            }

            $balance = round((float) $wallet->balance, 2);
            if ($balance < $grandTotal) {
                throw ShopException::make(ShopException::INSUFFICIENT_BALANCE, 'ยอดเงินในกระเป๋าไม่เพียงพอ', 422, [
                    'required' => $grandTotal,
                    'available' => $balance,
                    'shortfall' => round($grandTotal - $balance, 2),
                ]);
            }
        }

        // 8) สร้างออเดอร์ทีละร้าน
        $checkoutGroup = (string) Str::uuid();
        $orders = [];

        foreach ($plans as $plan) {
            $order = $this->createOrder($user, $plan, $address, $paymentMethod, $checkoutGroup, $input['note'] ?? null);

            if ($coupon !== null && $coupon['group_key'] === $plan['group']['key']) {
                $this->coupons->recordUsage(
                    (int) $coupon['coupon_id'],
                    $user,
                    $order,
                    round($plan['product_discount'] + $plan['shipping_discount'], 2)
                );
            }

            if ($paymentMethod === PaymentMethod::WALLET) {
                $order->deductStockOnce(true);
                $this->chargeWallet($wallet, $order);
                $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();
            } elseif ($paymentMethod === PaymentMethod::COD) {
                // จองสต็อกให้ร้านส่งของ (ยกเลิก → คืนสต็อก)
                $order->deductStockOnce(true);
            }

            $orders[] = $order;
        }

        // 9) ล้างตะกร้า (ตะกร้าแอป: ลบจริง กันชน unique ของ soft delete · ตะกร้าเว็บ: ลบแถวที่สั่ง)
        $clearCart();

        return $orders;
    }

    /**
     * ที่อยู่สำรองเมื่อแอปไม่ได้ส่ง address_id มา (แอปรุ่นก่อนใช้ที่อยู่ในโปรไฟล์)
     *
     * 1) ที่อยู่หลัก (is_default) หรือที่อยู่ล่าสุดของผู้ใช้
     * 2) ยังไม่มีสักที่ → สร้างที่อยู่หลักจากโปรไฟล์ (ต้องมีที่อยู่ จังหวัด รหัสไปรษณีย์ และเบอร์โทรครบ)
     * ไม่พอทั้งสองทาง → null (ผู้เรียกตอบ ADDRESS_REQUIRED)
     */
    private function fallbackAddress(User $user): ?ShippingAddress
    {
        $existing = ShippingAddress::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $line = trim((string) ($user->address ?? ''));
        $province = trim((string) ($user->state ?? ''));
        $postal = trim((string) ($user->postal_code ?? ''));
        $phone = (string) preg_replace('/[^0-9+]/', '', (string) ($user->phone ?? ''));

        if ($line === '' || $province === '' || $postal === '' || $phone === '') {
            return null;
        }

        $name = trim((string) $user->name);

        return ShippingAddress::create([
            'user_id' => $user->id,
            'recipient_name' => mb_substr($name !== '' ? $name : 'ผู้รับ', 0, 255),
            'phone_number' => mb_substr($phone, 0, 20),
            'address_line_1' => mb_substr($line, 0, 255),
            'district' => trim((string) ($user->city ?? '')) !== '' ? mb_substr(trim((string) $user->city), 0, 255) : null,
            'province' => mb_substr($province, 0, 255),
            'postal_code' => mb_substr($postal, 0, 10),
            'country' => 'Thailand',
            'is_default' => true,
            'notes' => 'สร้างจากที่อยู่ในโปรไฟล์ตอนสั่งซื้อ',
        ]);
    }

    /**
     * สร้างออเดอร์ 1 ร้าน + รายการสินค้า (snapshot GP/ราคา ณ เวลาซื้อ)
     *
     * @param  array<string, mixed>  $plan
     */
    private function createOrder(User $user, array $plan, ?ShippingAddress $address, string $paymentMethod, string $checkoutGroup, ?string $note): Order
    {
        $group = $plan['group'];
        $productDiscount = (float) $plan['product_discount'];
        $shippingDiscount = (float) $plan['shipping_discount'];

        $order = Order::create([
            'user_id' => $user->id,
            'checkout_group' => $checkoutGroup,
            'store_id' => $group['store_id'],
            'shipping_address_id' => $group['has_physical'] && $address ? $address->id : null,
            'shipping_address_snapshot' => $group['has_physical'] && $address ? $address->toSnapshot() : null,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'delivery_method' => $group['delivery_method'],
            'subtotal' => round($group['subtotal'], 2),
            'discount_amount' => round($productDiscount + $shippingDiscount, 2),
            // แยกชนิดส่วนลด + ผู้ออกเงิน → ระบบแบ่งเงินรู้ว่าร้านรับภาระ (คูปองร้าน) ไม่ลงเป็นรายจ่ายแพลตฟอร์ม
            'product_discount' => round($productDiscount, 2),
            'shipping_discount' => round($shippingDiscount, 2),
            'discount_funded_by' => ($productDiscount + $shippingDiscount) > 0 ? 'store' : null,
            'shipping_fee' => round((float) $group['shipping_fee'], 2),
            'total_amount' => round((float) $plan['total'], 2),
            'customer_notes' => $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 500) : null,
            'cashback_amount' => 0,
            'cashback_processed' => false,
        ]);

        $platformCommission = 0.0;
        $sellerEarning = 0.0;
        $officialSellerId = null;

        foreach ($group['lines'] as $line) {
            /** @var Product $product */
            $product = $line['product'];
            $qty = (int) $line['quantity'];
            $unitPrice = (float) $line['unit_price'];
            $lineDiscount = round((float) ($plan['line_discounts'][$line['line_key']] ?? 0), 2);

            $snapshot = $this->pricing->itemSnapshot($product, $qty, $unitPrice);

            if ($lineDiscount > 0) {
                // ส่วนลดของร้าน → คิด GP และรายได้ร้านจากยอดหลังลด (ร้านเป็นผู้ออกส่วนลด)
                $netLine = round(max(0.0, $snapshot['subtotal'] - $lineDiscount), 2);
                $pvPerUnit = (float) ($this->pricing->pvInfoForProduct($product)['pv'] ?? 0);
                $breakdown = $this->pricing->breakdown(
                    $netLine,
                    1,
                    $this->pricing->optionsForProduct($product, ['pv' => $pvPerUnit * $qty])
                );

                $snapshot['discount_amount'] = $lineDiscount;
                $snapshot['total'] = $netLine;
                $snapshot['commission_amount'] = $breakdown->gp_amount;
                $snapshot['seller_earning'] = max(0.0, $breakdown->seller_net);
            }

            if ($product->seller_id) {
                $sellerId = (int) $product->seller_id;
            } else {
                $officialSellerId ??= Product::getOfficialSellerId();
                $sellerId = $officialSellerId;
            }

            OrderItem::create(array_merge($snapshot, [
                'order_id' => $order->id,
                'product_id' => $product->id,
                'seller_id' => $sellerId,
                'product_name' => mb_substr((string) $product->name, 0, 255),
                'product_sku' => (string) ($product->sku ?: 'PRD-'.$product->id),
                'product_image' => $product->main_image_url,
                'product_attributes' => $line['cart_item']->getAttribute('attributes'),
                'status' => 'pending',
            ]));

            $platformCommission += (float) $snapshot['commission_amount'];
            $sellerEarning += (float) $snapshot['seller_earning'];
        }

        $order->forceFill([
            'platform_commission' => round($platformCommission, 2),
            'seller_earning' => round($sellerEarning, 2),
        ]);
        $order->suppressStatusNotification = true;
        $order->save();

        return $order;
    }

    /**
     * หักวอลเลตของผู้ซื้อ 1 ออเดอร์ แล้วตั้งออเดอร์เป็นจ่ายแล้ว (อยู่ใน transaction checkout)
     *
     * idempotent: มีรายการหักของออเดอร์นี้แล้ว → ไม่หักซ้ำ
     */
    private function chargeWallet(Wallet $wallet, Order $order): void
    {
        $existing = WalletTransaction::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('type', 'fee')
            ->where('status', 'completed')
            ->first();

        $amount = round((float) $order->total_amount, 2);

        if (! $existing && $amount > 0) {
            try {
                $existing = $this->wallets->deductForService(
                    $wallet,
                    $amount,
                    "ชำระคำสั่งซื้อ #{$order->order_number}",
                    'order',
                    $order->id,
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'checkout_group' => $order->checkout_group,
                        'source' => $this->source,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Shop checkout: wallet debit failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                $message = $e->getMessage();
                if (str_contains($message, 'Insufficient') || str_contains($message, 'ไม่เพียงพอ')) {
                    throw ShopException::make(ShopException::INSUFFICIENT_BALANCE, 'ยอดเงินในกระเป๋าไม่เพียงพอ', 422, [
                        'required' => $amount,
                        'available' => round((float) Wallet::whereKey($wallet->id)->value('balance'), 2),
                    ]);
                }

                if (str_contains($message, 'not active')) {
                    throw ShopException::make(ShopException::WALLET_INACTIVE, 'กระเป๋าเงินของคุณใช้งานไม่ได้ในขณะนี้ กรุณาติดต่อเจ้าหน้าที่', 403);
                }

                throw $e;
            }
        }

        // ตั้งจ่ายแล้วเป็นขั้นสุดท้าย → OrderObserver แบ่งเงินร้าน/เงินคืน (ภายใน transaction เดียวกัน)
        $order->forceFill([
            'payment_status' => 'paid',
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $existing ? 'WALLET-'.$existing->transaction_id : 'WALLET-FREE',
        ]);
        $order->suppressStatusNotification = true;
        $order->save();
    }

    /**
     * เริ่มการชำระพร้อมเพย์ของออเดอร์ (หลัง commit) — ล้มเหลวไม่ทำให้ checkout ล้ม แอปกดชำระใหม่ได้
     *
     * @return array<string, mixed>
     */
    private function startPromptPay(Order $order): array
    {
        try {
            $paymentService = app(PaymentService::class);
            $transaction = $paymentService->createOrderPayment($order, PaymentMethod::PROMPTPAY, [
                'metadata' => ['source' => $this->source, 'checkout_group' => $order->checkout_group],
            ]);
            $result = $paymentService->processPayment($transaction, []);

            if (! ($result['success'] ?? false)) {
                Log::warning('Shop checkout: promptpay init failed', [
                    'order_id' => $order->id,
                    'message' => $result['message'] ?? null,
                ]);

                return [
                    'order_id' => (int) $order->id,
                    'status' => 'error',
                    'message' => 'สร้าง QR พร้อมเพย์ไม่สำเร็จ กรุณากดชำระเงินอีกครั้งจากหน้าคำสั่งซื้อ',
                ];
            }

            return ShopPresenter::payment($result['transaction'], $result['data'] ?? []);
        } catch (\Throwable $e) {
            Log::error('Shop checkout: promptpay exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return [
                'order_id' => (int) $order->id,
                'status' => 'error',
                'message' => 'สร้าง QR พร้อมเพย์ไม่สำเร็จ กรุณากดชำระเงินอีกครั้งจากหน้าคำสั่งซื้อ',
            ];
        }
    }

    /**
     * @param  array<int, Order>  $orders
     * @param  array<int, array<string, mixed>>  $payments
     * @return array<string, mixed>
     */
    private function presentResult(User $user, array $orders, string $paymentMethod, array $payments): array
    {
        $ordersOut = [];
        $total = 0.0;
        foreach ($orders as $order) {
            $fresh = $order->fresh(['items', 'store']) ?? $order;
            $ordersOut[] = ShopPresenter::order($fresh);
            $total += (float) $fresh->total_amount;
        }

        $result = [
            'checkout_id' => $orders[0]->checkout_group ?? null,
            'orders' => $ordersOut,
            'order_ids' => array_map(fn (Order $o) => (int) $o->id, $orders),
            'total_amount' => round($total, 2),
            // คีย์แบบเดิมสำหรับแอปรุ่นก่อน (อ่าน data.orderId / data.orderNumber / data.total) — ออเดอร์แรกของการสั่ง
            'orderId' => isset($orders[0]) ? (int) $orders[0]->id : null,
            'orderNumber' => $orders[0]->order_number ?? null,
            'total' => round($total, 2),
            'payment_method' => $paymentMethod,
            'payment_status' => match ($paymentMethod) {
                PaymentMethod::WALLET => 'paid',
                PaymentMethod::COD => 'cod',
                default => 'pending',
            },
            'payments' => $payments,
        ];

        if ($paymentMethod === PaymentMethod::WALLET) {
            $result['wallet_balance'] = round((float) (Wallet::where('user_id', $user->id)->value('balance') ?? 0), 2);
        }

        return $result;
    }

    /**
     * พร้อมเพย์เปิดใช้งานอยู่หรือไม่ (ตามรายการวิธีชำระของระบบ)
     */
    private function isPromptPayAvailable(): bool
    {
        try {
            foreach (app(PaymentService::class)->getAvailablePaymentMethods() as $method) {
                if (($method['id'] ?? null) === PaymentMethod::PROMPTPAY) {
                    return (bool) ($method['enabled'] ?? false);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Shop checkout: read payment methods failed', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function idempotencyCacheKey(User $user, ?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        $key = trim($key);
        if ($key === '' || strlen($key) > 100) {
            return null;
        }

        return 'shop:checkout:idem:'.$user->id.':'.hash('sha256', $key);
    }
}
