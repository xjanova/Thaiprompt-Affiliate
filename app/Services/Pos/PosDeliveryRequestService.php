<?php

namespace App\Services\Pos;

use App\Exceptions\PosDeliveryException;
use App\Exceptions\RiderJobException;
use App\Exceptions\ShopException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTrackingHistory;
use App\Models\PosDeliveryRequest;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\VendorStore;
use App\Models\Wallet;
use App\Services\NotificationService;
use App\Services\RiderDispatchService;
use App\Services\Shop\PosRequestLines;
use App\Services\Shop\ShopCartService;
use App\Services\Shop\ShopCheckoutService;
use App\Services\Shop\ShopPresenter;
use App\Services\WalletService;
use App\Support\Shop\PaymentMethod;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * คำขอชำระเงิน + ส่งด้วยไรเดอร์ จากเครื่อง POS (POS → ไรเดอร์ Thai Prompt)
 *
 * ฝั่ง POS:   create (resolve สินค้าในร้านของเครื่อง + ราคา server) · showForTerminal · cancel
 * ฝั่งลูกค้า: quote (ที่อยู่ → ค่าส่งไรเดอร์ด้วยสูตรเดียวกับ checkout) · pay (PIN → ShopCheckoutService เส้นเดิม)
 *
 * กฎเจ้าของ:
 *  - ลูกค้าจ่ายจากกระเป๋าเงิน Thai Prompt เท่านั้น — ไม่มี COD / จ่ายปลายทาง / ร้านจ่ายแทน
 *  - ค่าส่งคำนวณที่ server (ShopCartService::buildGroups → DeliveryFeeCalculator) ห้ามเชื่อค่าจาก client
 *  - เงินปลดด้วยการสแกนส่งมอบสองฝ่ายของไรเดอร์รอบ 2 (ที่นี่ไม่ยุ่ง)
 *
 * ❗ ห้าม log เบอร์โทร / token / PIN · ห้ามบอกยอดเงินคงเหลือ (บอกแค่ balance_ok)
 */
class PosDeliveryRequestService
{
    /** อายุผลลัพธ์ที่เก็บไว้ตอบ Idempotency-Key ซ้ำของการจ่าย (วินาที) */
    private const PAY_IDEMPOTENCY_TTL = 600;

    /** รหัสสถานะงานส่งไรเดอร์ที่ใช้เรียกงาน (เหมือน SellerOrderService::requestRider) */
    private const JOB_TYPE = 'shop_delivery';

    public function __construct(
        private readonly PosRequestLines $lines,
        private readonly ShopCartService $carts,
        private readonly WalletService $wallets,
        private readonly RiderDispatchService $dispatch,
        private readonly NotificationService $notifications,
    ) {}

    // =====================================================
    // ฝั่งเครื่อง POS
    // =====================================================

    /**
     * สร้างคำขอ (หรือคืนคำขอเดิมเมื่อ local_id ซ้ำและยัง pending)
     *
     * @param  array{local_id: string, order_local_id?: string|null, items: array<int, array<string, mixed>>, customer_phone?: string|null, note?: string|null}  $input  ผ่าน validation แล้ว
     * @return array{0: PosDeliveryRequest, 1: bool, 2: bool} [คำขอ, สร้างใหม่หรือไม่, ส่ง push แล้วหรือไม่]
     *
     * @throws PosDeliveryException RIDER_UNAVAILABLE|ITEMS_NOT_IN_STORE|REQUEST_ALREADY_PAID
     */
    public function create(PosTerminal $terminal, array $input): array
    {
        $store = $terminal->shop;
        if (! $store instanceof VendorStore || ! $store->canUseRiderDelivery()) {
            throw PosDeliveryException::make(
                PosDeliveryException::RIDER_UNAVAILABLE,
                'ร้านนี้ยังไม่เปิดส่งด้วยไรเดอร์ Thai Prompt (ต้องเปิดใช้งานและปักหมุดจุดรับของในหน้าร้านก่อน)',
                409
            );
        }

        $localId = trim((string) $input['local_id']);

        // ส่งซ้ำ: คำขอเดิมยังรอจ่าย → คืนคำขอเดิม (token เดิม)
        $existing = PosDeliveryRequest::where('pos_terminal_id', $terminal->id)->where('local_id', $localId)->first();
        if ($existing) {
            $this->expireIfDue($existing);

            if ($existing->status === PosDeliveryRequest::STATUS_PENDING) {
                return [$existing, false, $existing->target_user_id !== null];
            }

            if ($existing->status === PosDeliveryRequest::STATUS_PAID) {
                throw PosDeliveryException::make(PosDeliveryException::REQUEST_ALREADY_PAID, 'คำขอเลขนี้ลูกค้าชำระเงินแล้ว', 409, [
                    'id' => (int) $existing->id,
                ]);
            }
            // หมดอายุ/ยกเลิกแล้ว → ใช้แถวเดิมออก QR ใหม่ (token ใหม่ — QR เก่าใช้ไม่ได้)
        }

        [$items, $missing, $subtotal] = $this->resolveItems($store, $input['items']);
        if ($missing !== []) {
            throw PosDeliveryException::make(
                PosDeliveryException::ITEMS_NOT_IN_STORE,
                'มีสินค้าที่ยังไม่มีขายในร้านออนไลน์ (ไม่พบ/ปิดการขาย/สินค้าหมด) กรุณาตรวจรายการ',
                422,
                ['missing' => $missing]
            );
        }

        $phone = self::normalizePhone($input['customer_phone'] ?? null);
        $note = trim((string) ($input['note'] ?? ''));
        $orderLocalId = trim((string) ($input['order_local_id'] ?? ''));

        $attributes = [
            'token' => $this->newToken(),
            'order_local_id' => $orderLocalId !== '' ? mb_substr($orderLocalId, 0, 64) : null,
            'items' => $items,
            'subtotal' => $subtotal,
            'customer_phone' => $phone,
            'target_user_id' => null,
            'note' => $note !== '' ? mb_substr($note, 0, 500) : null,
            'status' => PosDeliveryRequest::STATUS_PENDING,
            'order_id' => null,
            'paid_by_user_id' => null,
            'paid_at' => null,
            'cancelled_at' => null,
            'expires_at' => now()->addMinutes(PosDeliveryRequest::TTL_MINUTES),
        ];

        try {
            [$request, $created] = DB::transaction(function () use ($existing, $attributes, $terminal, $store, $localId) {
                if ($existing) {
                    $locked = PosDeliveryRequest::whereKey($existing->id)->lockForUpdate()->first();
                    if ($locked && $locked->isPayable()) {
                        return [$locked, false];
                    }
                    if ($locked && $locked->status === PosDeliveryRequest::STATUS_PAID) {
                        throw PosDeliveryException::make(PosDeliveryException::REQUEST_ALREADY_PAID, 'คำขอเลขนี้ลูกค้าชำระเงินแล้ว', 409, [
                            'id' => (int) $locked->id,
                        ]);
                    }
                    if ($locked) {
                        $locked->forceFill($attributes + ['store_id' => $store->id])->save();

                        return [$locked, true];
                    }
                }

                return [PosDeliveryRequest::create($attributes + [
                    'pos_terminal_id' => $terminal->id,
                    'store_id' => $store->id,
                    'local_id' => $localId,
                ]), true];
            });
        } catch (UniqueConstraintViolationException $e) {
            // POS ยิงซ้ำพร้อมกัน → อีกคำขอสร้างแถวไปแล้ว คืนแถวนั้น
            $request = PosDeliveryRequest::where('pos_terminal_id', $terminal->id)->where('local_id', $localId)->first();
            if (! $request) {
                throw $e;
            }

            return [$request, false, $request->target_user_id !== null];
        }

        $pushSent = false;
        if ($created && $phone !== null) {
            $pushSent = $this->notifyCustomer($request, $store, $phone);
        }

        return [$request, $created, $pushSent];
    }

    /**
     * คำขอของร้านเดียวกับเครื่อง (ร้านอื่น = ไม่พบ) — เปลี่ยนเป็น expired ถ้าหมดเวลา
     *
     * @throws PosDeliveryException REQUEST_NOT_FOUND
     */
    public function showForTerminal(PosTerminal $terminal, int $id): PosDeliveryRequest
    {
        $request = PosDeliveryRequest::where('store_id', $terminal->shop_id)->find($id);
        if (! $request) {
            throw PosDeliveryException::make(PosDeliveryException::REQUEST_NOT_FOUND, 'ไม่พบคำขอส่งไรเดอร์นี้', 404);
        }

        $this->expireIfDue($request);

        return $request;
    }

    /**
     * ยกเลิกคำขอ (เฉพาะที่ยังรอจ่าย)
     *
     * @throws PosDeliveryException REQUEST_NOT_FOUND|NOT_CANCELLABLE
     */
    public function cancel(PosTerminal $terminal, int $id): PosDeliveryRequest
    {
        $request = $this->showForTerminal($terminal, $id);

        return DB::transaction(function () use ($request) {
            $locked = PosDeliveryRequest::whereKey($request->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isPayable()) {
                throw PosDeliveryException::make(PosDeliveryException::NOT_CANCELLABLE, 'ยกเลิกไม่ได้ คำขอนี้ไม่ได้อยู่ในสถานะรอชำระแล้ว', 409, [
                    'status' => $locked ? ($locked->isExpired() ? PosDeliveryRequest::STATUS_EXPIRED : (string) $locked->status) : null,
                ]);
            }

            $locked->forceFill([
                'status' => PosDeliveryRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ])->save();

            return $locked;
        });
    }

    // =====================================================
    // ฝั่งลูกค้า (แอป Thai Prompt)
    // =====================================================

    /**
     * ใบเสนอราคาสำหรับลูกค้า: รายการ + ค่าส่งไรเดอร์ตามที่อยู่ + จ่ายไหวไหม (บอกแค่ boolean)
     *
     * @return array<string, mixed>
     *
     * @throws PosDeliveryException REQUEST_NOT_FOUND|REQUEST_EXPIRED|REQUEST_ALREADY_PAID|REQUEST_CANCELLED
     */
    public function quote(User $user, string $rawToken, ?int $addressId): array
    {
        $request = $this->findByToken($rawToken);
        $this->expireIfDue($request);

        // จ่ายแล้วโดยผู้ใช้คนนี้ → ให้แอปพาไปหน้าออเดอร์
        if ($request->status === PosDeliveryRequest::STATUS_PAID && (int) $request->paid_by_user_id === (int) $user->id) {
            return $this->paidView($request);
        }

        $this->assertPayable($request);

        $store = $request->store;
        $lines = $this->lines->forRequest($request);

        // สินค้าทุกชิ้นยังสั่งได้ไหม (สูตรเดียวกับตะกร้า/checkout)
        $qtyByProduct = $lines->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
        $blockReason = null;
        foreach ($lines as $line) {
            $reason = $this->carts->lineBlockReason($line, (int) ($qtyByProduct[$line->product_id] ?? 0));
            if ($reason !== null) {
                $blockReason = $reason;
                break;
            }
        }

        // ที่อยู่: ระบุมา → ต้องเป็นของผู้ใช้ · ไม่ระบุ → ที่อยู่หลัก (หรือล่าสุด) แบบเดียวกับ checkout
        $addressNotFound = false;
        if ($addressId !== null) {
            $address = ShippingAddress::where('user_id', $user->id)->find($addressId);
            $addressNotFound = $address === null;
        } else {
            $address = ShippingAddress::where('user_id', $user->id)
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->first();
        }

        $group = null;
        if ($blockReason === null && $lines->isNotEmpty()) {
            $group = $this->carts->buildGroups($lines, $address, 'rider')[0] ?? null;
        }

        $riderAvailable = $group !== null && ($group['rider']['available'] ?? false) === true;
        $message = match (true) {
            $blockReason !== null => 'สินค้าบางรายการไม่พร้อมขายแล้ว ('.$blockReason.') กรุณาติดต่อร้าน',
            $lines->isEmpty() => 'คำขอนี้ไม่มีรายการสินค้า กรุณาติดต่อร้าน',
            $addressNotFound => 'ไม่พบที่อยู่จัดส่งนี้',
            $address === null => 'กรุณาเลือกที่อยู่ที่ปักหมุดไว้',
            ! $address->hasLocation() => 'ที่อยู่นี้ยังไม่ได้ปักหมุด',
            ! $riderAvailable => (string) ($group['rider']['reason'] ?? 'ส่งด้วยไรเดอร์ไม่ได้ในขณะนี้'),
            default => null,
        };
        $riderAvailable = $riderAvailable && $message === null;

        $items = [];
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $product = $line->product;
            $price = $product ? round((float) $product->price, 2) : round((float) $line->price, 2);
            $qty = (int) $line->quantity;
            $subtotal += $price * $qty;

            $items[] = [
                'product_id' => (int) $line->product_id,
                'name' => (string) ($product?->name ?? $this->storedName($request, (int) $line->product_id)),
                'qty' => $qty,
                'price' => $price,
                'line_total' => round($price * $qty, 2),
                'image_url' => $product ? (ShopPresenter::productImages($product)[0] ?? null) : null,
            ];
        }
        $subtotal = round($subtotal, 2);

        $fee = $riderAvailable ? round((float) $group['rider']['fee'], 2) : null;
        $total = round($subtotal + (float) ($fee ?? 0), 2);
        $balance = round((float) (Wallet::where('user_id', $user->id)->value('balance') ?? 0), 2);

        return [
            'token' => (string) $request->token,
            'status' => (string) $request->status,
            'expires_at' => $request->expires_at?->toIso8601String(),
            'store' => $store ? [
                'id' => (int) $store->id,
                'name' => (string) $store->store_name,
                'logo_url' => $store->logo_url,
            ] : null,
            'items' => $items,
            'note' => $request->note,
            'subtotal' => $subtotal,
            'address' => $address ? [
                'id' => (int) $address->id,
                'label' => (string) ($address->recipient_name ?? ''),
                'line' => (string) $address->full_address,
                'has_location' => $address->hasLocation(),
            ] : null,
            'rider' => [
                'available' => $riderAvailable,
                'fee' => $fee,
                'distance_km' => $group !== null && $group['rider']['distance_km'] !== null ? round((float) $group['rider']['distance_km'], 2) : null,
                'message' => $message,
            ],
            'total' => $total,
            'wallet' => ['balance_ok' => $balance >= $total],
        ];
    }

    /**
     * จ่ายคำขอ: PIN → ล็อกแถวคำขอ → ShopCheckoutService (wallet + rider) → ตั้งคำขอ paid
     * หลัง commit: เรียกไรเดอร์ (ล้มเหลว = ไม่กระทบเงิน ร้าน/แอดมินกดเรียกไรเดอร์เองได้)
     *
     * @return array{order_id: int, order_number: string, total: float, track_path: string}
     *
     * @throws PosDeliveryException
     */
    public function pay(User $user, string $rawToken, int $addressId, string $pin, string $idempotencyKey): array
    {
        $token = PosDeliveryRequest::normalizeToken($rawToken);
        if ($token === null) {
            throw PosDeliveryException::notFound();
        }

        // Idempotency-Key เดิมของผู้ใช้เดิม → ผลเดิม (กดซ้ำ/เน็ตหลุดแล้วยิงใหม่)
        $idemCacheKey = 'pos:pay:idem:'.$user->id.':'.hash('sha256', $token.'|'.$idempotencyKey);
        if (($cached = Cache::get($idemCacheKey)) !== null) {
            return $cached;
        }

        $request = PosDeliveryRequest::where('token', $token)->first();
        if (! $request) {
            throw PosDeliveryException::notFound();
        }
        $this->expireIfDue($request);
        $this->assertPayable($request);

        // PIN ตรวจนอก transaction — นับครั้งที่ผิด/ล็อกกระเป๋าต้องคงอยู่แม้การจ่ายล้ม
        $this->verifyPin($user, $pin);

        try {
            /** @var Order $order */
            [$order, $request] = DB::transaction(function () use ($request, $user, $addressId) {
                // ล็อกแถวคำขอ → จ่ายพร้อมกัน 2 คน/กดซ้ำ จะมีแค่คนแรกที่ผ่าน
                $locked = PosDeliveryRequest::whereKey($request->id)->lockForUpdate()->first();
                if (! $locked) {
                    throw PosDeliveryException::notFound();
                }
                $this->assertPayable($locked);

                $result = app(ShopCheckoutService::class)->checkout($user, [
                    'address_id' => $addressId,
                    'payment_method' => PaymentMethod::WALLET,
                    'delivery_method' => Order::DELIVERY_RIDER,
                    'note' => $locked->note,
                    'source' => 'pos',
                ], null, fn () => $this->lines->lockedSource($locked, $user));

                $orderIds = array_values(array_filter(array_map('intval', (array) ($result['order_ids'] ?? []))));
                if (count($orderIds) !== 1) {
                    // สินค้าทุกชิ้นมาจากร้านเดียว → ต้องได้ 1 ออเดอร์เสมอ
                    throw new \RuntimeException('POS request checkout produced '.count($orderIds).' orders');
                }

                $order = Order::whereKey($orderIds[0])->lockForUpdate()->firstOrFail();

                // ผูกออเดอร์กับเครื่อง POS (เฉพาะข้อมูลอ้างอิง — ไม่เรียก observer/แจ้งเตือน)
                if (self::ordersHavePosColumns()) {
                    $order->forceFill([
                        'pos_terminal_id' => $locked->pos_terminal_id,
                        'pos_local_id' => mb_substr((string) ($locked->order_local_id ?: $locked->local_id), 0, 50),
                    ])->saveQuietly();
                }

                $locked->forceFill([
                    'status' => PosDeliveryRequest::STATUS_PAID,
                    'order_id' => $order->id,
                    'paid_by_user_id' => $user->id,
                    'paid_at' => now(),
                ])->save();

                return [$order, $locked];
            });
        } catch (ShopException $e) {
            throw $this->fromShopException($e);
        }

        // หลัง commit: เรียกไรเดอร์ + บันทึกประวัติการติดตาม (เหมือนร้านกด "เรียกไรเดอร์")
        $this->requestRider($order, $request);

        $result = [
            'order_id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'total' => round((float) $order->total_amount, 2),
            'track_path' => '/order/'.$order->id,
        ];

        // เงินตัดแล้ว — cache ล่มต้องไม่ทำให้ตอบว่าล้มเหลว (ส่งซ้ำจะได้ REQUEST_ALREADY_PAID แทน)
        try {
            Cache::put($idemCacheKey, $result, self::PAY_IDEMPOTENCY_TTL);
        } catch (\Throwable $e) {
            Log::warning('PosDelivery: idempotency cache put failed', ['order_id' => $order->id] + self::safeError($e));
        }

        return $result;
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * pending ที่เลยเวลา → expired (อัปเดตแบบมีเงื่อนไข กันทับสถานะที่เพิ่งเปลี่ยน)
     */
    public function expireIfDue(PosDeliveryRequest $request): void
    {
        if ($request->status !== PosDeliveryRequest::STATUS_PENDING || ! $request->isExpired()) {
            return;
        }

        PosDeliveryRequest::whereKey($request->id)
            ->where('status', PosDeliveryRequest::STATUS_PENDING)
            ->update(['status' => PosDeliveryRequest::STATUS_EXPIRED, 'updated_at' => now()]);

        $request->refresh();
    }

    /**
     * เบอร์มือถือไทยแบบตัวเลขล้วน 0XXXXXXXXX (+66 / 66 แปลงให้) — รูปแบบไม่ถูก = null
     */
    public static function normalizePhone(?string $raw): ?string
    {
        $digits = (string) preg_replace('/\D/', '', (string) $raw);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '66') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        return preg_match('/^0[689]\d{8}$/', $digits) === 1 ? $digits : null;
    }

    /**
     * หาสินค้าในร้านของเครื่อง: product_id ก่อน ไม่เจอค่อยใช้ sku · รวมรายการซ้ำ · ตรวจว่าสั่งได้ + สต็อกพอ
     *
     * @param  array<int, array<string, mixed>>  $rawItems
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: float} [รายการ, รายการที่ใช้ไม่ได้, ยอดรวม]
     */
    private function resolveItems(VendorStore $store, array $rawItems): array
    {
        $ids = [];
        $skus = [];
        foreach ($rawItems as $raw) {
            if (! empty($raw['product_id'])) {
                $ids[] = (int) $raw['product_id'];
            }
            if (isset($raw['sku']) && trim((string) $raw['sku']) !== '') {
                $skus[] = trim((string) $raw['sku']);
            }
        }

        $products = Product::query()
            ->with('store')
            ->where('store_id', $store->id)
            ->where(function ($q) use ($ids, $skus) {
                $q->whereIn('id', $ids !== [] ? array_values(array_unique($ids)) : [0]);
                if ($skus !== []) {
                    $q->orWhereIn('sku', array_values(array_unique($skus)));
                }
            })
            ->get();
        $byId = $products->keyBy('id');
        $bySku = $products->filter(fn (Product $p) => (string) $p->sku !== '')->keyBy(fn (Product $p) => (string) $p->sku);

        /** @var array<int, array{product: Product, qty: int, given: array<string, mixed>}> $resolved */
        $resolved = [];
        $missing = [];

        foreach ($rawItems as $raw) {
            $givenId = ! empty($raw['product_id']) ? (int) $raw['product_id'] : null;
            $givenSku = isset($raw['sku']) && trim((string) $raw['sku']) !== '' ? trim((string) $raw['sku']) : null;
            $givenName = trim((string) ($raw['name'] ?? ''));
            $qty = max(1, (int) ($raw['qty'] ?? 1));

            $product = ($givenId !== null ? $byId->get($givenId) : null)
                ?? ($givenSku !== null ? $bySku->get($givenSku) : null);

            if (! $product) {
                $missing[] = $this->missingRow($givenId, $givenSku, $givenName, 'ไม่พบสินค้านี้ในร้านออนไลน์');

                continue;
            }

            $key = (int) $product->id;
            if (isset($resolved[$key])) {
                $resolved[$key]['qty'] += $qty;
            } else {
                $resolved[$key] = ['product' => $product, 'qty' => $qty, 'given' => ['id' => $givenId, 'sku' => $givenSku, 'name' => $givenName]];
            }
        }

        $items = [];
        $subtotal = 0.0;
        foreach ($resolved as $row) {
            /** @var Product $product */
            $product = $row['product'];
            $qty = (int) $row['qty'];

            $reason = $this->unavailableReason($product, $qty);
            if ($reason !== null) {
                $missing[] = $this->missingRow(
                    $row['given']['id'] ?? (int) $product->id,
                    (string) ($product->sku ?: ($row['given']['sku'] ?? '')),
                    $row['given']['name'] !== '' ? $row['given']['name'] : (string) $product->name,
                    $reason
                );

                continue;
            }

            $price = round((float) $product->price, 2);
            $subtotal += $price * $qty;
            $items[] = [
                'product_id' => (int) $product->id,
                'sku' => (string) $product->sku,
                'name' => mb_substr((string) $product->name, 0, 255),
                'qty' => $qty,
                'price' => $price,
            ];
        }

        return [$items, $missing, round($subtotal, 2)];
    }

    /**
     * เหตุผลที่สินค้านี้ส่งด้วยไรเดอร์จาก POS ไม่ได้ (null = ได้)
     */
    private function unavailableReason(Product $product, int $qty): ?string
    {
        $reason = $product->purchaseBlockReason();
        if ($reason !== null) {
            return $reason;
        }

        if ($product->is_virtual) {
            return 'สินค้าดิจิทัลส่งด้วยไรเดอร์ไม่ได้';
        }

        if (! $product->isInStock()) {
            return 'สินค้าหมด';
        }

        if ($product->track_inventory && $qty > (int) $product->stock_quantity) {
            return 'สินค้าเหลือ '.max(0, (int) $product->stock_quantity).' ชิ้น';
        }

        return null;
    }

    /**
     * @return array{product_id: int|null, sku: string|null, name: string, reason: string}
     */
    private function missingRow(?int $productId, ?string $sku, string $name, string $reason): array
    {
        return [
            'product_id' => $productId,
            'sku' => $sku,
            'name' => mb_substr($name, 0, 255),
            'reason' => $reason,
        ];
    }

    /**
     * ส่ง push "มีคำขอชำระเงินจากร้าน" ถึงผู้ใช้ที่ยืนยันเบอร์นี้แล้ว (ตรงคนเดียวเท่านั้น)
     *
     * ไม่บอก POS ว่าเบอร์มีบัญชีหรือไม่ (นอกจาก push_sent) · ไม่ log เบอร์
     */
    private function notifyCustomer(PosDeliveryRequest $request, VendorStore $store, string $phone): bool
    {
        try {
            $user = $this->findVerifiedUserByPhone($phone);
            if (! $user) {
                return false;
            }

            $this->notifications->create(
                $user,
                'pos_payment_request',
                'มีคำขอชำระเงินจากร้าน '.mb_substr((string) $store->store_name, 0, 100),
                'แตะเพื่อดูรายการและยืนยันการส่งด้วยไรเดอร์',
                [
                    'type' => 'pos_payment_request',
                    'token' => (string) $request->token,
                    'screen' => 'pos-pay',
                ],
                null,
                'ดูรายการ',
                'high'
            );

            $request->forceFill(['target_user_id' => $user->id])->save();

            return true;
        } catch (\Throwable $e) {
            Log::warning('PosDelivery: customer push failed', ['request_id' => $request->id] + self::safeError($e));

            return false;
        }
    }

    /**
     * ผู้ใช้ที่ยืนยันเบอร์แล้วและเบอร์ตรง (เก็บได้หลายรูปแบบ) — ต้องตรงคนเดียว ไม่งั้นไม่ส่ง
     */
    private function findVerifiedUserByPhone(string $phone): ?User
    {
        $rest = substr($phone, 1); // ตัด 0 หน้า
        $candidates = array_values(array_unique([
            $phone,
            '+66'.$rest,
            '66'.$rest,
            substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6),
            substr($phone, 0, 3).' '.substr($phone, 3, 3).' '.substr($phone, 6),
        ]));

        $users = User::query()
            ->where('phone_verified', true)
            ->whereIn('phone', $candidates)
            ->limit(2)
            ->get();

        if ($users->count() !== 1) {
            return null;
        }

        $user = $users->first();

        return method_exists($user, 'isSuspended') && $user->isSuspended() ? null : $user;
    }

    /**
     * หาโดย token (ไม่ผ่านรูปแบบ = ไม่พบ ไม่ต้องค้นฐานข้อมูล)
     *
     * @throws PosDeliveryException REQUEST_NOT_FOUND
     */
    private function findByToken(string $rawToken): PosDeliveryRequest
    {
        $token = PosDeliveryRequest::normalizeToken($rawToken);
        $request = $token !== null ? PosDeliveryRequest::with('store')->where('token', $token)->first() : null;

        if (! $request) {
            throw PosDeliveryException::notFound();
        }

        return $request;
    }

    /**
     * ต้องยังรอจ่ายและไม่หมดเวลา
     *
     * @throws PosDeliveryException REQUEST_ALREADY_PAID|REQUEST_CANCELLED|REQUEST_EXPIRED
     */
    private function assertPayable(PosDeliveryRequest $request): void
    {
        if ($request->status === PosDeliveryRequest::STATUS_PAID) {
            throw PosDeliveryException::alreadyPaid();
        }

        if ($request->status === PosDeliveryRequest::STATUS_CANCELLED) {
            throw PosDeliveryException::cancelled();
        }

        if ($request->isExpired()) {
            throw PosDeliveryException::expired();
        }

        if ($request->status !== PosDeliveryRequest::STATUS_PENDING) {
            throw PosDeliveryException::notFound();
        }
    }

    /**
     * ผลสำหรับผู้ใช้ที่จ่ายคำขอนี้ไปแล้ว (แอปพาไปหน้าออเดอร์)
     *
     * @return array<string, mixed>
     */
    private function paidView(PosDeliveryRequest $request): array
    {
        $order = $request->order;

        return [
            'token' => (string) $request->token,
            'status' => PosDeliveryRequest::STATUS_PAID,
            'order_id' => $order ? (int) $order->id : (int) $request->order_id,
            'order_number' => $order?->order_number,
            'total' => $order ? round((float) $order->total_amount, 2) : null,
            'track_path' => '/order/'.(int) $request->order_id,
            'store' => $request->store ? [
                'id' => (int) $request->store->id,
                'name' => (string) $request->store->store_name,
                'logo_url' => $request->store->logo_url,
            ] : null,
        ];
    }

    /**
     * ชื่อสินค้าที่เก็บไว้ตอนสร้าง (สินค้าถูกลบไปแล้ว)
     */
    private function storedName(PosDeliveryRequest $request, int $productId): string
    {
        foreach ((array) $request->items as $row) {
            if ((int) ($row['product_id'] ?? 0) === $productId) {
                return (string) ($row['name'] ?? 'สินค้า');
            }
        }

        return 'สินค้า';
    }

    /**
     * ตรวจ PIN กระเป๋าเงิน — กลไกเดียวกับ WalletWithdrawalApiController::walletGuard + checkPin
     * (Wallet::verifyPIN · ผิด 5 ครั้งล็อก 30 นาที ผ่าน Wallet::incrementFailedAttempts)
     *
     * @throws PosDeliveryException WALLET_LOCKED|PIN_NOT_SET|INVALID_PIN
     */
    private function verifyPin(User $user, string $pin): void
    {
        $wallet = $this->wallets->getOrCreateWallet($user);

        if (! $wallet->isActive()) {
            throw PosDeliveryException::make(
                PosDeliveryException::WALLET_LOCKED,
                'กระเป๋าเงินถูกล็อกชั่วคราว (กรอก PIN ผิดหลายครั้งหรือถูกระงับ) กรุณาลองใหม่ภายหลัง',
                403,
                ['locked_until' => $wallet->locked_until?->toIso8601String()]
            );
        }

        if (! $wallet->hasPIN()) {
            throw PosDeliveryException::make(PosDeliveryException::PIN_NOT_SET, 'กรุณาตั้ง PIN กระเป๋าเงินก่อน', 422);
        }

        if ($wallet->verifyPIN($pin)) {
            $wallet->resetFailedAttempts();

            return;
        }

        $wallet->incrementFailedAttempts();
        $wallet->refresh();
        $remaining = max(0, 5 - (int) $wallet->failed_attempts);

        throw PosDeliveryException::make(
            $remaining > 0 ? PosDeliveryException::INVALID_PIN : PosDeliveryException::WALLET_LOCKED,
            $remaining > 0 ? "PIN ไม่ถูกต้อง (เหลือ {$remaining} ครั้ง)" : 'PIN ไม่ถูกต้องเกินกำหนด กระเป๋าถูกล็อก 30 นาที',
            403,
            ['attempts_remaining' => $remaining]
        );
    }

    /**
     * เรียกไรเดอร์หลังจ่ายสำเร็จ — แบบเดียวกับ SellerOrderService::requestRider
     *
     * ล้มเหลว (นอกพื้นที่ / พิกัดไม่ครบ / ระบบล่ม) → log แล้วปล่อยออเดอร์ไว้ให้ร้าน/แอดมินกด "เรียกไรเดอร์" เอง
     * เงินลูกค้าไม่หาย (ออเดอร์จ่ายแล้ว เงินพักไว้ตามระบบเดิม)
     */
    private function requestRider(Order $order, PosDeliveryRequest $request): void
    {
        try {
            $job = $this->dispatch->createJobForSource($order, self::JOB_TYPE);
        } catch (RiderJobException $e) {
            Log::warning('PosDelivery: rider dispatch refused — left for seller/admin request-rider', [
                'order_id' => $order->id,
                'request_id' => $request->id,
                'rider_code' => $e->errorCode,
            ]);

            return;
        } catch (\Throwable $e) {
            Log::error('PosDelivery: rider dispatch failed — left for seller/admin request-rider', [
                'order_id' => $order->id,
                'request_id' => $request->id,
            ] + self::safeError($e));

            return;
        }

        try {
            $sellerId = (int) (VendorStore::whereKey($request->store_id)->value('user_id') ?? 0);

            DB::transaction(function () use ($order, $job, $request, $sellerId) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                OrderItem::where('order_id', $locked->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'processing']);

                if (in_array($locked->status, ['pending', 'paid'], true)) {
                    $locked->status = 'processing';
                    $locked->save();
                }

                OrderTrackingHistory::createEntry($locked, 'rider_requested', 'ร้านเรียกไรเดอร์แล้ว', [
                    'description' => 'กำลังหาไรเดอร์ใกล้ร้าน (สั่งจากเครื่อง POS)',
                    'created_by' => $sellerId > 0 ? $sellerId : null,
                    'created_by_type' => 'seller',
                    'meta_data' => [
                        'rider_job_id' => $job->id,
                        'seller_id' => $sellerId > 0 ? $sellerId : null,
                        'pos_terminal_id' => (int) $request->pos_terminal_id,
                        'pos_delivery_request_id' => (int) $request->id,
                    ],
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('PosDelivery: post-dispatch tracking update failed', [
                'order_id' => $order->id,
                'request_id' => $request->id,
            ] + self::safeError($e));
        }
    }

    /**
     * แปลงข้อผิดพลาดของ checkout เป็นรหัส/ข้อความเดิม แต่คัดข้อมูลประกอบเฉพาะที่ปลอดภัย
     * (ไม่บอกยอดเงินคงเหลือ · ไม่มีคำว่า "ตะกร้า" ที่ลูกค้า POS ไม่มี)
     */
    private function fromShopException(ShopException $e): PosDeliveryException
    {
        $message = $e->getMessage();
        $context = $e->context;

        if ($e->errorCode === ShopException::INSUFFICIENT_BALANCE) {
            $context = array_intersect_key($context, array_flip(['required', 'shortfall']));
        } else {
            $context = array_intersect_key($context, array_flip(['product_id', 'available', 'store_id', 'address_id']));
        }

        if ($e->errorCode === ShopException::CART_EMPTY) {
            $message = 'คำขอนี้ไม่มีรายการสินค้า กรุณาติดต่อร้าน';
        } elseif ($e->errorCode === ShopException::PRODUCT_UNAVAILABLE && array_key_exists('cart_item_id', $e->context)) {
            $message = 'สินค้าบางรายการไม่มีขายแล้ว กรุณาติดต่อร้าน';
        }

        return PosDeliveryException::make($e->errorCode, $message, $e->httpStatus, $context);
    }

    /**
     * โทเคนสุ่ม 40 ตัวที่ยังไม่ซ้ำ
     */
    private function newToken(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $token = Str::random(PosDeliveryRequest::TOKEN_LENGTH);
            if (! PosDeliveryRequest::where('token', $token)->exists()) {
                return $token;
            }
        }

        return Str::random(PosDeliveryRequest::TOKEN_LENGTH);
    }

    /**
     * ตาราง orders มีคอลัมน์อ้างอิง POS หรือไม่ (migration 2025_12_11_000001) — จำผลไว้ต่อ process
     */
    private static function ordersHavePosColumns(): bool
    {
        static $has = null;

        return $has ??= Schema::hasColumn('orders', 'pos_terminal_id') && Schema::hasColumn('orders', 'pos_local_id');
    }

    /**
     * ข้อมูลข้อผิดพลาดสำหรับ log ที่ไม่มีข้อมูลส่วนบุคคล
     * (QueryException มี SQL + ค่าที่ bind เช่นเบอร์โทร → เก็บแค่ SQLSTATE)
     *
     * @return array{exception: string, error: string}
     */
    public static function safeError(\Throwable $e): array
    {
        if ($e instanceof QueryException) {
            return [
                'exception' => $e::class,
                'error' => 'query failed (SQLSTATE '.($e->errorInfo[0] ?? $e->getCode()).')',
            ];
        }

        return [
            'exception' => $e::class,
            'error' => Str::limit($e->getMessage(), 300),
        ];
    }
}
