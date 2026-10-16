<?php

namespace App\Services\TpixPay;

use App\Exceptions\FreshMarketException;
use App\Models\FreshMarketOrder;
use App\Models\ServiceBooking;
use App\Models\TpixPayment;
use App\Models\User;
use App\Services\FreshMarketService;
use App\Services\ServiceBookingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🪙 จ่ายตลาดสด / การจองบริการด้วยเหรียญ TPIX — "เหรียญ → กระเป๋าเงิน → เส้นจ่ายด้วยกระเป๋าเดิม"
 *
 * ทำไมไม่สร้างออเดอร์ค้างจ่ายก่อน (แบบร้านค้า):
 *  - ตลาดสดไม่มีสถานะ "รอจ่าย" — ออเดอร์ที่สร้างแล้วตัดสต็อก + แจ้งร้านทันที และร้านกดรับได้เลย (escrow ผูกกับ wallet/cod)
 *    ถ้าสร้างก่อนจ่าย ร้านจะเตรียมของให้ออเดอร์ที่ยังไม่มีเงิน → จึงเก็บ "ข้อมูลสั่งซื้อที่ตรวจแล้ว" ไว้ในใบเสนอยอดแทน
 *  - การจองบริการมีสถานะรอจ่ายอยู่แล้ว (สร้างการจองก่อน ชำระทีหลัง) → ผูกใบกับการจองโดยตรง
 *
 * ลำดับ:
 *  1. quoteFreshMarket()/quoteServiceBooking() — คำนวณยอดจริงด้วยตรรกะเดียวกับตอนสั่ง (ด่านครบ: eKYC/ร้านเปิด/สต็อก/ค่าส่ง)
 *     แล้วออกใบเสนอยอด (ยอด wei ตรงตัว · เพดานต่อรายการ/ต่อวัน/สภาพคล่องพูล · ราคา TWAP)
 *  2. ได้เหรียญ → TpixPaymentService::settle() เติม amount_thb เข้ากระเป๋า (ในธุรกรรมเดียวกับการผูกเลขธุรกรรม) + fulfilment = pending
 *  3. fulfil() (หลัง commit) — จองสิทธิ์ทำครั้งเดียว (pending → processing) แล้วเรียกเส้นจ่ายด้วยกระเป๋าของแต่ละระบบ
 *     สำเร็จ → placed + เลขออเดอร์/การจอง · ไม่สำเร็จ (ของหมด/ร้านปิด/ราคาเปลี่ยน/การจองถูกยกเลิก) → failed + เหตุผล เงินอยู่ในกระเป๋า
 *
 * ❗ ทำแบบ "ไม่เกินหนึ่งครั้ง": ค้างที่ processing (เครื่องดับกลางคัน) = ไม่ลองซ้ำอัตโนมัติ (กันสั่งซ้ำ) — เงินยังอยู่ในกระเป๋า/ออเดอร์ที่สั่งสำเร็จ
 */
class TpixCheckoutService
{
    /** หลังได้เหรียญ ลองสั่งอัตโนมัติ (รอบแรกล้มด้วย DB สะดุด) ได้ภายในกี่นาที — เกินนี้ไม่สั่งให้แล้ว (ของสด/นัดเวลาอาจไม่ตรงความตั้งใจ) */
    public const FULFIL_WINDOW_MINUTES = 30;

    /** ลองซ้ำเมื่อ DB สะดุดได้สูงสุดกี่ครั้ง */
    private const MAX_ATTEMPTS = 3;

    /** ช่องข้อมูลสั่งซื้อตลาดสดที่เก็บในใบ (ส่งต่อให้ createOrderFromItems ตอนได้เงิน) */
    private const FRESH_MARKET_FIELDS = [
        'delivery_type', 'delivery_address', 'delivery_notes', 'buyer_latitude', 'buyer_longitude',
        'preferred_rider_id', 'channel', 'client_app_build', 'apply_default_options',
    ];

    public function __construct(
        private readonly TpixPaySettings $settings,
        private readonly TpixPriceService $prices,
    ) {}

    // =====================================================================
    // ออกใบเสนอยอด
    // =====================================================================

    /**
     * ใบเสนอยอดของตะกร้าตลาดสด (ร้านเดียว) — กดซ้ำด้วยข้อมูลเดิม = ได้ใบเดิม (ยังไม่หมดเวลา)
     *
     * @param  array<int, array{listing_id: int, quantity: int, option_ids?: array, note?: ?string}>  $items
     * @param  array<string, mixed>  $data  delivery_type, delivery_address, buyer_latitude, buyer_longitude, delivery_notes, preferred_rider_id, channel, client_app_build
     * @param  array<int, int>  $cartItemIds  รายการตะกร้าที่ต้องลบเมื่อสั่งสำเร็จ (ว่าง = สั่งตรงจากหน้าสินค้า)
     *
     * @throws TpixPayException ปิดรับ / ราคาไม่พร้อม / เกินเพดาน
     * @throws FreshMarketException สั่งไม่ได้ (ร้านปิด / ของหมด / ยังไม่ยืนยันตัวตน ฯลฯ) — ตรวจก่อนให้โอนเหรียญ
     */
    public function quoteFreshMarket(User $buyer, int $sellerId, array $items, array $data, array $cartItemIds = []): TpixPayment
    {
        $this->assertEnabled();

        $orderData = array_intersect_key($data, array_flip(self::FRESH_MARKET_FIELDS));
        // เงินจาก TPIX เข้ากระเป๋าก่อน แล้วจ่ายด้วยเส้นกระเป๋าเงิน (escrow พักเงินจนส่งมอบ เหมือนจ่ายด้วยกระเป๋า)
        $orderData['payment_method'] = 'wallet';
        $cartItemIds = array_values(array_unique(array_map('intval', $cartItemIds)));
        if ($cartItemIds !== []) {
            $orderData['cart_item_ids'] = $cartItemIds;
        }

        $preview = app(FreshMarketService::class)->previewOrderFromItems($buyer, $sellerId, $items, $orderData);
        $grandTotal = round((float) $preview['grand_total'], 2);
        if ($grandTotal <= 0) {
            throw new TpixPayException(TpixPayException::INVALID_AMOUNT, 'ยอดชำระต้องมากกว่า 0 บาท');
        }

        $checkout = [
            'seller_id' => $sellerId,
            'items' => array_values($items),
            'data' => $orderData,
            'total_amount' => (float) $preview['total_amount'],
            'delivery_fee' => (float) $preview['delivery_fee'],
            'grand_total' => $grandTotal,
            'shop_name' => $preview['shop_name'],
        ];
        $fingerprint = sha1(json_encode([$buyer->id, $sellerId, $checkout['items'], $orderData, $grandTotal]) ?: '');

        $open = $this->openQuote(TpixPayment::PURPOSE_FRESH_MARKET, $buyer->id, fn (TpixPayment $p) => ($p->meta['fingerprint'] ?? null) === $fingerprint);
        if ($open) {
            return $open;
        }

        return $this->createQuote($buyer->id, TpixPayment::PURPOSE_FRESH_MARKET, 'fresh_market_checkout', $sellerId, $grandTotal, [
            'title' => 'ตลาดสด · '.$preview['shop_name'],
            'checkout' => $checkout,
            'fingerprint' => $fingerprint,
        ]);
    }

    /**
     * ใบเสนอยอดของการจองบริการที่ยังไม่ชำระ (มีใบเปิดอยู่ = ใช้ใบเดิม)
     *
     * @throws TpixPayException
     */
    public function quoteServiceBooking(ServiceBooking $booking): TpixPayment
    {
        $this->assertEnabled();

        if ($booking->payment_status === 'paid') {
            throw new TpixPayException(TpixPayException::INVALID_AMOUNT, 'การจองนี้ชำระเงินแล้ว');
        }
        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            throw new TpixPayException(TpixPayException::INVALID_AMOUNT, 'การจองนี้ปิดไปแล้ว ชำระเงินไม่ได้');
        }

        $amount = round((float) $booking->total_amount, 2);
        if ($amount <= 0) {
            throw new TpixPayException(TpixPayException::INVALID_AMOUNT, 'ยอดชำระต้องมากกว่า 0 บาท');
        }

        $open = $this->openQuote(TpixPayment::PURPOSE_SERVICE_BOOKING, (int) $booking->user_id,
            fn (TpixPayment $p) => (int) $p->payable_id === (int) $booking->id && bccomp((string) $p->amount_thb, number_format($amount, 2, '.', ''), 2) === 0);
        if ($open) {
            return $open;
        }

        $booking->loadMissing('service');

        return $this->createQuote((int) $booking->user_id, TpixPayment::PURPOSE_SERVICE_BOOKING, 'service_booking', (int) $booking->id, $amount, [
            'title' => 'จองบริการ · '.($booking->service?->name ?? ('#'.$booking->booking_number)),
            'order_number' => $booking->booking_number,
        ]);
    }

    // =====================================================================
    // ส่งมอบหลังได้เงิน
    // =====================================================================

    /**
     * สั่งซื้อ/ชำระการจองจากกระเป๋าด้วยข้อมูลที่เก็บไว้ (เรียกหลังเงินเข้ากระเป๋าแล้ว — ไม่ throw)
     *
     * @return array{status: string, message: string}
     */
    public function fulfil(TpixPayment $payment): array
    {
        if (! $payment->isCheckoutPurpose()) {
            return ['status' => (string) $payment->status, 'message' => ''];
        }

        try {
            // 1) จองสิทธิ์ทำ (pending → processing) — ผู้เรียกพร้อมกัน (หน้าโพล/ตัวสแกน/ส่งเลขธุรกรรม) ทำได้คนเดียว
            $claimed = DB::transaction(function () use ($payment) {
                $locked = TpixPayment::whereKey($payment->id)->lockForUpdate()->first();
                if (! $locked || ! $locked->isSettled() || $locked->fulfilmentStatus() !== TpixPayment::FULFIL_PENDING) {
                    return null;
                }

                $fulfilment = (array) ($locked->meta['fulfilment'] ?? []);
                $paidAt = $locked->paid_at ?? now();
                if ($paidAt->lt(now()->subMinutes(self::FULFIL_WINDOW_MINUTES))) {
                    $this->writeFulfilment($locked, $fulfilment, TpixPayment::FULFIL_FAILED,
                        'เกินเวลาสั่งอัตโนมัติ — ยอดเงินอยู่ในกระเป๋าเงินของคุณ สั่งใหม่ด้วยกระเป๋าเงินได้เลย');

                    return null;
                }

                $fulfilment['status'] = 'processing';
                $fulfilment['attempts'] = (int) ($fulfilment['attempts'] ?? 0) + 1;
                $fulfilment['started_at'] = now()->toIso8601String();
                $locked->meta = array_merge($locked->meta ?? [], ['fulfilment' => $fulfilment]);
                $locked->save();

                return $locked;
            });
        } catch (\Throwable $e) {
            Log::error('TpixPay checkout: claim fulfilment failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            return $this->currentOutcome($payment);
        }

        if (! $claimed) {
            return $this->currentOutcome($payment);
        }

        // 2) เรียกเส้นจ่ายด้วยกระเป๋าของระบบนั้น (ธุรกรรมของมันเอง — แจ้งร้าน/ผู้ให้บริการหลัง commit ตามปกติ)
        $result = null;
        $failure = null;
        $retry = false;
        try {
            $result = $claimed->purpose === TpixPayment::PURPOSE_FRESH_MARKET
                ? $this->placeFreshMarketOrder($claimed)
                : $this->payServiceBooking($claimed);
        } catch (FreshMarketException $e) {
            $failure = $e->getMessage();
        } catch (QueryException $e) {
            // ธุรกรรมของออเดอร์ rollback ทั้งก้อนแล้ว (ไม่มีออเดอร์) → ลองใหม่ได้
            Log::warning('TpixPay checkout: database error while placing — will retry', ['payment_id' => $claimed->id, 'error' => $e->getMessage()]);
            $retry = (int) ($claimed->meta['fulfilment']['attempts'] ?? 1) < self::MAX_ATTEMPTS;
            $failure = 'ระบบสั่งซื้อขัดข้องชั่วคราว';
        } catch (\Throwable $e) {
            Log::warning('TpixPay checkout: wallet checkout refused', ['payment_id' => $claimed->id, 'error' => $e->getMessage()]);
            $message = $e->getMessage();
            $failure = preg_match('/[\x{0E00}-\x{0E7F}]/u', $message) ? $message : 'สั่งซื้อไม่สำเร็จ';
        }

        // 3) บันทึกผล
        try {
            DB::transaction(function () use ($claimed, $result, $failure, $retry) {
                $locked = TpixPayment::whereKey($claimed->id)->lockForUpdate()->first();
                if (! $locked) {
                    return;
                }
                $fulfilment = (array) ($locked->meta['fulfilment'] ?? []);

                if ($result !== null) {
                    $fulfilment = array_merge($fulfilment, $result, ['placed_at' => now()->toIso8601String()]);
                    $meta = array_merge($locked->meta ?? [], ['order_number' => $result['result_number'] ?? null]);
                    $locked->meta = $meta;
                    // เงินถูกใช้จ่ายรายการนี้แล้ว → สถานะใบ = ชำระแล้ว
                    $locked->status = TpixPayment::STATUS_PAID;
                    $this->writeFulfilment($locked, $fulfilment, TpixPayment::FULFIL_PLACED, $result['message']);

                    return;
                }

                if ($retry) {
                    $fulfilment['status'] = TpixPayment::FULFIL_PENDING;
                    $locked->meta = array_merge($locked->meta ?? [], ['fulfilment' => $fulfilment]);
                    $locked->save();

                    return;
                }

                $this->writeFulfilment($locked, $fulfilment, TpixPayment::FULFIL_FAILED,
                    ($failure ?: 'สั่งซื้อไม่สำเร็จ').' — ยอดเงินอยู่ในกระเป๋าเงินของคุณแล้ว สั่งใหม่หรือใช้จ่ายรายการอื่นได้ทันที');
            });
        } catch (\Throwable $e) {
            Log::critical('TpixPay checkout: could not record fulfilment result', [
                'payment_id' => $claimed->id,
                'result' => $result,
                'failure' => $failure,
                'error' => $e->getMessage(),
            ]);
        }

        // การจองที่จ่ายแล้ว → หาผู้ให้บริการ (ทำแบบเดียวกับจ่ายด้วยกระเป๋า · ไม่สำเร็จไม่กระทบการจ่าย)
        if ($result !== null && $claimed->purpose === TpixPayment::PURPOSE_SERVICE_BOOKING) {
            try {
                $booking = ServiceBooking::find($claimed->payable_id);
                if ($booking) {
                    app(ServiceBookingService::class)->assignProvider($booking);
                }
            } catch (\Throwable $e) {
                Log::warning('TpixPay checkout: assign provider after payment failed', ['booking_id' => $claimed->payable_id, 'error' => $e->getMessage()]);
            }
        }

        Log::info('TpixPay checkout: fulfilment finished', [
            'payment_id' => $claimed->id,
            'purpose' => $claimed->purpose,
            'placed' => $result !== null,
            'retry' => $retry,
        ]);

        return $this->currentOutcome($payment);
    }

    /**
     * ใบที่ได้เงินแล้วแต่ยังไม่ได้สั่ง (รอบแรกล้มเพราะ DB สะดุด / เครื่องดับก่อนเริ่ม) → ลองอีกครั้ง (ตัวสแกนทุกนาที)
     */
    public function retryPending(): int
    {
        $done = 0;

        TpixPayment::whereIn('purpose', TpixPayment::CHECKOUT_PURPOSES)
            ->whereIn('status', TpixPayment::SETTLED_STATUSES)
            ->where('paid_at', '>=', now()->subMinutes(self::FULFIL_WINDOW_MINUTES + 10))
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->filter(fn (TpixPayment $p) => $p->fulfilmentStatus() === TpixPayment::FULFIL_PENDING)
            ->each(function (TpixPayment $p) use (&$done) {
                $this->fulfil($p);
                $done++;
            });

        return $done;
    }

    /**
     * ข้อมูลผลการสั่ง/จองสำหรับหน้าจ่าย (เว็บ/แอป) — null = ไม่ใช่ใบแบบนี้
     *
     * @return array<string, mixed>|null
     */
    public static function present(TpixPayment $payment): ?array
    {
        if (! $payment->isCheckoutPurpose()) {
            return null;
        }

        $f = (array) ($payment->meta['fulfilment'] ?? []);
        $status = isset($f['status']) && is_string($f['status']) ? $f['status'] : null;
        $isFresh = $payment->purpose === TpixPayment::PURPOSE_FRESH_MARKET;
        $resultId = isset($f['result_id']) ? (int) $f['result_id'] : null;
        $bookingId = $isFresh ? null : (int) $payment->payable_id;

        $resultUrl = null;
        try {
            if ($isFresh && $resultId) {
                $resultUrl = route('taladsod.orders.show', $resultId);
            } elseif (! $isFresh && $bookingId) {
                $resultUrl = route('user.bookings.show', $bookingId);
            }
        } catch (\Throwable $e) {
            $resultUrl = null;
        }

        return [
            'kind' => $payment->purpose,
            'title' => (string) ($payment->meta['title'] ?? ''),
            'status' => $status === 'processing' ? TpixPayment::FULFIL_PENDING : $status,
            'message' => isset($f['message']) ? (string) $f['message'] : null,
            'fresh_market_order_id' => $isFresh ? $resultId : null,
            'service_booking_id' => $bookingId,
            'result_number' => $f['result_number'] ?? ($isFresh ? null : ($payment->meta['order_number'] ?? null)),
            'result_url' => $resultUrl,
            'shop_name' => $isFresh ? ($payment->meta['checkout']['shop_name'] ?? null) : null,
            // แอปพากลับไปหน้าสั่งซื้อของร้านนี้เมื่อใบหมดเวลา/สั่งไม่สำเร็จ
            'seller_id' => $isFresh ? ((int) ($payment->meta['checkout']['seller_id'] ?? 0) ?: null) : null,
        ];
    }

    // =====================================================================
    // ภายใน
    // =====================================================================

    /**
     * @return array{result_id: int, result_number: string|null, message: string}
     *
     * @throws FreshMarketException
     */
    private function placeFreshMarketOrder(TpixPayment $payment): array
    {
        $checkout = (array) ($payment->meta['checkout'] ?? []);
        $buyer = User::find($payment->user_id);
        if (! $buyer || empty($checkout['seller_id']) || empty($checkout['items'])) {
            throw FreshMarketException::make('INVALID_CHECKOUT', 'ข้อมูลสั่งซื้อไม่ครบ', 422);
        }

        $data = (array) ($checkout['data'] ?? []);
        $data['payment_method'] = 'wallet';
        $data['channel'] = $data['channel'] ?? 'tpix';

        // ยอดจริงต้องไม่เกินยอดที่จ่ายเหรียญมา (ราคาขึ้นระหว่างโอน = ไม่สั่ง เงินอยู่ในกระเป๋า)
        /** @var FreshMarketOrder $order */
        $order = app(FreshMarketService::class)->createOrderFromItems(
            $buyer,
            (int) $checkout['seller_id'],
            (array) $checkout['items'],
            $data,
            (float) $payment->amount_thb
        );

        $change = round((float) $payment->amount_thb - (float) $order->grand_total, 2);

        return [
            'result_id' => (int) $order->id,
            'result_number' => $order->order_number,
            'message' => 'สั่งซื้อสำเร็จ หมายเลข '.$order->order_number
                .($change > 0 ? ' · ส่วนต่าง ฿'.number_format($change, 2).' อยู่ในกระเป๋าเงินของคุณ' : ''),
        ];
    }

    /**
     * @return array{result_id: int, result_number: string|null, message: string}
     *
     * @throws \Exception
     */
    private function payServiceBooking(TpixPayment $payment): array
    {
        $booking = ServiceBooking::find($payment->payable_id);
        if (! $booking || (int) $booking->user_id !== (int) $payment->user_id) {
            throw new \Exception('ไม่พบการจองของรายการนี้');
        }

        // ยอดการจองเปลี่ยนไปมากกว่ายอดที่จ่ายเหรียญ → ไม่หักเงินเดิมในกระเป๋าเพิ่ม
        if (round((float) $booking->total_amount, 2) > round((float) $payment->amount_thb, 2)) {
            throw new \Exception('ยอดการจองเปลี่ยนไประหว่างรอชำระ');
        }

        app(ServiceBookingService::class)->processPayment($booking, 'wallet');

        return [
            'result_id' => (int) $booking->id,
            'result_number' => $booking->booking_number,
            'message' => 'ชำระการจอง #'.$booking->booking_number.' สำเร็จ',
        ];
    }

    /**
     * @param  array<string, mixed>  $fulfilment
     */
    private function writeFulfilment(TpixPayment $locked, array $fulfilment, string $status, string $message): void
    {
        $fulfilment['status'] = $status;
        $fulfilment['message'] = mb_substr($message, 0, 500);
        $fulfilment['finished_at'] = now()->toIso8601String();
        $locked->meta = array_merge($locked->meta ?? [], ['fulfilment' => $fulfilment]);
        $locked->save();
    }

    /**
     * @return array{status: string, message: string}
     */
    private function currentOutcome(TpixPayment $payment): array
    {
        $fresh = $payment->fresh() ?? $payment;

        return [
            'status' => (string) $fresh->status,
            'message' => (string) ($fresh->meta['fulfilment']['message'] ?? 'ได้รับเหรียญแล้ว เติมมูลค่าเข้ากระเป๋าเงินของคุณ กำลังสั่งซื้อให้'),
        ];
    }

    /**
     * @throws TpixPayException
     */
    private function assertEnabled(): void
    {
        if (! $this->settings->paymentsEnabled()) {
            throw new TpixPayException(TpixPayException::DISABLED, 'ยังไม่เปิดรับชำระด้วยเหรียญ TPIX');
        }
    }

    /**
     * ใบที่ยังเปิด (ไม่ใกล้หมดเวลา) ของผู้ใช้คนนี้ที่ตรงเงื่อนไข
     */
    private function openQuote(string $purpose, int $userId, callable $matches): ?TpixPayment
    {
        return TpixPayment::where('purpose', $purpose)
            ->where('user_id', $userId)
            ->where('status', TpixPayment::STATUS_PENDING)
            ->where('expires_at', '>', now()->addMinutes(2))
            ->latest('id')
            ->limit(20)
            ->get()
            ->first(fn (TpixPayment $p) => (bool) $matches($p));
    }

    /**
     * @param  array<string, mixed>  $meta
     *
     * @throws TpixPayException
     */
    private function createQuote(int $userId, string $purpose, string $payableType, int $payableId, float $amountThb, array $meta): TpixPayment
    {
        $payments = app(TpixPaymentService::class);
        $payments->assertWithinLimits($userId, $amountThb);

        $rate = $this->prices->quoteRate();
        if ($rate === null) {
            throw new TpixPayException(TpixPayException::PRICE_UNAVAILABLE, 'ยังดึงราคา TPIX จริงไม่ได้ในขณะนี้ กรุณาลองใหม่อีกครั้งหรือเลือกวิธีชำระอื่น');
        }
        $payments->assertPoolDepth($amountThb, $rate['reserve_usdt'], $rate['usd_thb']);

        $amountThbStr = number_format($amountThb, 2, '.', '');

        return $payments->createQuote([
            'user_id' => $userId,
            'payment_transaction_id' => null,
            'purpose' => $purpose,
            'payable_type' => $payableType,
            'payable_id' => $payableId,
            'amount_thb' => $amountThbStr,
            'rate_thb' => $rate['rate_thb'],
            'price_usd' => $rate['price_usd'],
            'usd_thb' => $rate['usd_thb'],
            'meta' => array_merge([
                'rate_basis' => $rate['basis'],
                'spot_thb' => $rate['spot_thb'],
                'twap_thb' => $rate['twap_thb'],
                'order_number' => null,
                'fixed_price_lines' => 0,
            ], $meta),
        ], TpixPriceService::thbToTpix($amountThbStr, $rate['rate_thb']));
    }
}
