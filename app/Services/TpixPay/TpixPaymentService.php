<?php

namespace App\Services\TpixPay;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\TpixPayment;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\WalletService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ระบบชำระเงินด้วยเหรียญ TPIX — ออกใบเสนอยอด · รับเลขธุรกรรม · ตรวจบนเชน · ส่งมอบ · สแกนหาการโอนเอง
 *
 * ลำดับการจ่าย 1 ครั้ง:
 *  1. PaymentService::createOrderPayment(order,'tpix') / createWalletTopup(user,thb,'tpix') → PaymentTransaction (บาท)
 *  2. processPayment → TpixPaymentProvider → quoteForTransaction() = ใบเสนอยอดเหรียญ (ยอด wei ตรงตัว + เลขท้ายเฉพาะใบ)
 *  3. ลูกค้าโอน (MetaMask / สแกน QR / กระเป๋าใดก็ได้) → ส่งเลขธุรกรรม หรือระบบสแกนเจอเอง (ทุกนาที)
 *  4. TpixTxVerifier ตรวจบนเชน → settle(): ออเดอร์จ่ายแล้ว (ผ่าน PaymentService::completePayment เส้นเดียวกับพร้อมเพย์)
 *
 * เงินไม่หาย:
 *  - ออเดอร์ถูกยกเลิก/จ่ายช่องทางอื่นไปแล้ว → เติมมูลค่าบาทเข้ากระเป๋าเงินแทน
 *  - โอนหลังหมดเวลา → เติมเข้ากระเป๋าตาม "ราคาปัจจุบัน" (ราคาตอนออกใบเก่าแล้ว — กันรอราคาตกแล้วค่อยจ่าย)
 *  - โอนซ้ำยอดเดิมภายในช่วงกันยอด → เติมเข้ากระเป๋าเช่นกัน
 */
class TpixPaymentService
{
    public function __construct(
        private readonly TpixRpcClient $rpc,
        private readonly TpixPriceService $prices,
        private readonly TpixPaySettings $settings,
        private readonly TpixTxVerifier $verifier,
    ) {}

    // =====================================================================
    // ออกใบเสนอยอด
    // =====================================================================

    /**
     * ใบเสนอยอดของ PaymentTransaction (เรียกจาก TpixPaymentProvider::process) — เรียกซ้ำได้ใบเดิมถ้ายังไม่หมดเวลา
     *
     * @throws TpixPayException
     */
    public function quoteForTransaction(PaymentTransaction $transaction): TpixPayment
    {
        $existing = TpixPayment::where('payment_transaction_id', $transaction->id)
            ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_CONFIRMING])
            ->where('expires_at', '>', now()->addMinutes(2))
            ->latest('id')
            ->first();
        if ($existing) {
            return $existing;
        }

        if (! $this->settings->paymentsEnabled()) {
            throw new TpixPayException(TpixPayException::DISABLED, 'ยังไม่เปิดรับชำระด้วยเหรียญ TPIX');
        }

        $isOrder = $transaction->type === 'order_payment';
        $order = $isOrder ? Order::with('items.product')->find($transaction->order_id) : null;
        if ($isOrder && ! $order) {
            throw new TpixPayException(TpixPayException::INVALID_AMOUNT, 'ไม่พบคำสั่งซื้อของรายการนี้');
        }

        if ($isOrder && $order->items->contains(fn ($item) => TpixPriceService::isExternalProduct($item->product))) {
            throw new TpixPayException(TpixPayException::DISABLED, 'สินค้าจาก Lazada ชำระด้วยเหรียญ TPIX ไม่ได้');
        }

        $metadata = $transaction->metadata ?? [];
        $amountThb = $isOrder
            ? round((float) $order->total_amount, 2)
            : round((float) ($metadata['original_amount'] ?? $transaction->amount), 2);

        if ($amountThb <= 0) {
            throw new TpixPayException(TpixPayException::INVALID_AMOUNT, 'ยอดชำระต้องมากกว่า 0 บาท');
        }

        $userId = (int) ($isOrder ? $order->user_id : $transaction->user_id);
        $this->assertWithinLimits($userId, $amountThb);

        $rate = $this->prices->quoteRate();
        if ($rate === null) {
            throw new TpixPayException(TpixPayException::PRICE_UNAVAILABLE, 'ยังดึงราคา TPIX จริงไม่ได้ในขณะนี้ กรุณาลองใหม่อีกครั้งหรือเลือกวิธีชำระอื่น');
        }

        $this->assertPoolDepth($amountThb, $rate['reserve_usdt'], $rate['usd_thb']);

        $amountThbStr = number_format($amountThb, 2, '.', '');
        $baseTpix = $isOrder
            ? $this->orderTpixTotal($order, $rate['rate_thb'])
            : TpixPriceService::thbToTpix($amountThbStr, $rate['rate_thb']);

        return $this->createQuote([
            'user_id' => $userId ?: null,
            'payment_transaction_id' => $transaction->id,
            'purpose' => $isOrder ? TpixPayment::PURPOSE_ORDER : TpixPayment::PURPOSE_WALLET_TOPUP,
            'payable_type' => $isOrder ? 'order' : 'wallet_topup',
            'payable_id' => $isOrder ? $order->id : $transaction->id,
            'amount_thb' => $amountThbStr,
            'rate_thb' => $rate['rate_thb'],
            'price_usd' => $rate['price_usd'],
            'usd_thb' => $rate['usd_thb'],
            'meta' => [
                'rate_basis' => $rate['basis'],
                'spot_thb' => $rate['spot_thb'],
                'twap_thb' => $rate['twap_thb'],
                'order_number' => $order?->order_number,
                'fixed_price_lines' => $isOrder ? $this->fixedLineCount($order) : 0,
            ],
        ], $baseTpix);
    }

    /**
     * ใบเสนอยอดของออเดอร์ที่ยังจ่ายได้ (มีใบเปิดอยู่ใช้ใบเดิม ไม่งั้นออกใบใหม่ผ่าน PaymentService)
     *
     * @return array{reference: string|null, error: string|null}
     */
    public function openQuoteForOrder(Order $order, string $source = 'web'): array
    {
        if ($order->payment_status === 'paid') {
            return ['reference' => null, 'error' => 'คำสั่งซื้อนี้ชำระเงินแล้ว'];
        }
        if ($order->isCod() || ! $order->canRetryPayment()) {
            return ['reference' => null, 'error' => 'คำสั่งซื้อนี้ชำระเงินไม่ได้แล้ว'];
        }
        if (! $this->settings->paymentsEnabled()) {
            return ['reference' => null, 'error' => 'ยังไม่เปิดรับชำระด้วยเหรียญ TPIX'];
        }

        $open = TpixPayment::where('purpose', TpixPayment::PURPOSE_ORDER)
            ->where('payable_id', $order->id)
            ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_CONFIRMING])
            ->where('expires_at', '>', now()->addMinutes(2))
            ->latest('id')
            ->first();
        if ($open) {
            return ['reference' => $open->reference, 'error' => null];
        }

        try {
            if (\App\Support\Shop\PaymentMethod::normalize($order->payment_method) !== 'tpix') {
                $order->suppressStatusNotification = true;
                $order->update(['payment_method' => 'tpix']);
            }

            $paymentService = app(PaymentService::class);
            $transaction = $paymentService->createOrderPayment($order, 'tpix', [
                'metadata' => ['source' => $source, 'retry' => true],
            ]);
            $result = $paymentService->processPayment($transaction, []);
        } catch (\Throwable $e) {
            Log::error('TpixPay: open quote for order failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            $result = ['success' => false, 'message' => ''];
        }

        $reference = $result['data']['tpix']['reference'] ?? null;
        if (! ($result['success'] ?? false) || ! $reference) {
            $message = (string) ($result['message'] ?? '');

            return ['reference' => null, 'error' => str_contains($message, 'TPIX')
                ? $message
                : 'ออกยอดชำระด้วย TPIX ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'];
        }

        return ['reference' => $reference, 'error' => null];
    }

    /**
     * ยอดเหรียญของออเดอร์: รายการที่มีราคา TPIX คงที่ (Official Shop) ใช้ราคาคงที่ ที่เหลือแปลงจากบาท
     *
     * ส่วนลดของรายการคิดตามสัดส่วน (ยอดหลังลด ÷ ยอดก่อนลด) · ค่าส่งและส่วนที่เหลือแปลงจากบาทด้วยราคาใบนี้
     */
    public function orderTpixTotal(Order $order, string $rateThb): string
    {
        $order->loadMissing('items.product');

        $fixedSum = '0';
        $convertThb = TpixAmount::fromNumber((string) $order->total_amount, 2);

        foreach ($order->items as $item) {
            $fixed = TpixPriceService::fixedPriceFor($item->product);
            if ($fixed === null) {
                continue;
            }

            $qty = (string) max(1, (int) $item->quantity);
            $gross = TpixAmount::fromNumber((string) ($item->subtotal ?? 0), 2);
            $net = TpixAmount::fromNumber((string) ($item->total ?? $item->subtotal ?? 0), 2);

            $line = bcmul($fixed, $qty, 18);
            if (bccomp($gross, '0', 2) > 0 && bccomp($net, $gross, 2) < 0) {
                $line = bcdiv(bcmul($line, $net, 18), $gross, 18);
            }

            // ❗ ราคาคงที่ต้องไม่ต่ำกว่ามูลค่าตลาดของยอดบาทบรรทัดนี้ — ไม่งั้นซื้อด้วยเหรียญถูก แล้วยกเลิก/โอนช้า
            //    ได้คืน/เติมกระเป๋าเป็นบาทเต็ม (amount_thb) = กำไรจากส่วนต่าง · ราคาคงที่จึงมีผลเฉพาะเมื่อแพงกว่าตลาด
            $market = bcdiv($net, $rateThb, 18);
            if (bccomp($market, $line, 18) > 0) {
                $line = $market;
            }

            $fixedSum = bcadd($fixedSum, $line, 18);
            $convertThb = bcsub($convertThb, $net, 2);
        }

        if (bccomp($convertThb, '0', 2) < 0) {
            $convertThb = '0';
        }

        $converted = bccomp($convertThb, '0', 2) > 0 ? bcdiv($convertThb, $rateThb, 18) : '0';

        return TpixAmount::ceil(bcadd($fixedSum, $converted, 18), 4);
    }

    /**
     * สร้างใบเสนอยอดพร้อมเลขท้ายเฉพาะใบ (ยอดซ้ำกับใบที่ยังกันยอดอยู่ไม่ได้ — unique index ตัดสิน)
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws TpixPayException
     */
    public function createQuote(array $attributes, string $baseTpix): TpixPayment
    {
        $address = $this->settings->receiveAddress();
        if (! TpixPaySettings::isValidAddress($address)) {
            throw new TpixPayException(TpixPayException::DISABLED, 'ยังไม่ได้ตั้งกระเป๋ารับเงิน TPIX');
        }

        $baseTpix = TpixAmount::ceil($baseTpix, 4);
        if (bccomp($baseTpix, '0', 4) <= 0) {
            throw new TpixPayException(TpixPayException::INVALID_AMOUNT, 'ยอดเหรียญต้องมากกว่า 0');
        }

        $baseWei = TpixAmount::tpixToWei($baseTpix);
        $ttl = max(5, (int) config('tpix_pay.quote_ttl_minutes', 30));

        for ($attempt = 0; $attempt < 15; $attempt++) {
            // เลขท้าย 1–9999 ที่ตำแหน่งทศนิยม 5–8 (1 หน่วย = 10^-8 TPIX = 10^10 wei)
            $tag = random_int(1, 9999);
            $amountWei = bcadd($baseWei, bcmul((string) $tag, '10000000000', 0), 0);
            $openKey = TpixPayment::openKeyFor($address, $amountWei);

            $this->releaseStaleOpenKey($openKey);

            try {
                return TpixPayment::create($attributes + [
                    'reference' => 'TPX'.Str::upper(Str::random(21)),
                    'amount_wei' => $amountWei,
                    'amount_tpix' => TpixAmount::weiToTpix($amountWei),
                    'receive_address' => $address,
                    'status' => TpixPayment::STATUS_PENDING,
                    'open_key' => $openKey,
                    'expires_at' => now()->addMinutes($ttl),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                continue; // ยอดนี้มีใบอื่นจองอยู่ (หรือ reference ชน) → สุ่มเลขท้ายใหม่
            }
        }

        throw new TpixPayException(TpixPayException::QUOTE_FAILED, 'ออกรายการชำระด้วย TPIX ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    }

    /**
     * เพดานต่อรายการ + ต่อผู้ใช้ 24 ชม. (กันความเสี่ยงรับเหรียญเกินสภาพคล่องของพูล)
     *
     * @throws TpixPayException
     */
    public function assertWithinLimits(int $userId, float $amountThb): void
    {
        $perPayment = $this->settings->maxPerPaymentThb();
        if ($perPayment > 0 && $amountThb > $perPayment) {
            throw new TpixPayException(TpixPayException::LIMIT_EXCEEDED, sprintf(
                'ชำระด้วย TPIX ได้ไม่เกิน %s บาทต่อรายการ',
                number_format($perPayment, 2)
            ));
        }

        $perDay = $this->settings->maxPerUserDayThb();
        if ($perDay > 0 && $userId > 0) {
            $used = (float) TpixPayment::where('user_id', $userId)
                ->where('created_at', '>=', now()->subDay())
                ->where(function ($q) {
                    // นับใบที่ได้เงินแล้ว + ใบที่ยังเปิดอยู่ (ไม่นับใบที่หมดเวลา/ยกเลิกโดยไม่ได้จ่าย)
                    $q->whereIn('status', TpixPayment::SETTLED_STATUSES)
                        ->orWhere(fn ($open) => $open->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_CONFIRMING])
                            ->where('expires_at', '>', now()));
                })
                ->sum('amount_thb');

            if ($used + $amountThb > $perDay) {
                throw new TpixPayException(TpixPayException::LIMIT_EXCEEDED, sprintf(
                    'ชำระด้วย TPIX ได้ไม่เกิน %s บาทต่อ 24 ชั่วโมง (ใช้ไปแล้ว %s บาท)',
                    number_format($perDay, 2),
                    number_format($used, 2)
                ));
            }
        }

        $spot = $this->prices->spot();
        if ($spot !== null) {
            $this->assertPoolDepth($amountThb, $spot['reserve_usdt'], $spot['usd_thb']);
        }
    }

    /**
     * เพดานตามสภาพคล่องของตลาด: ยอดต่อรายการต้องไม่เกิน max_pool_ratio × มูลค่าฝั่ง USDT ในพูล
     * พูลบาง = ปั่นราคาได้ถูก (ดันราคา 2 เท่าแล้วค้างไว้ทั้งช่วงค่าเฉลี่ย) และเหรียญที่รับมาขายคืนตามราคานี้ไม่ได้
     *
     * @throws TpixPayException
     */
    public function assertPoolDepth(float $amountThb, string $reserveUsdt, string $usdThb): void
    {
        $ratio = $this->settings->maxPoolRatio();
        if ($ratio <= 0) {
            return;
        }

        $maxThb = (float) bcmul(bcmul($reserveUsdt, $usdThb, 6), TpixAmount::fromNumber($ratio, 4), 2);
        if ($amountThb > $maxThb) {
            throw new TpixPayException(TpixPayException::LIMIT_EXCEEDED, sprintf(
                'ยอดนี้เกินสภาพคล่องของตลาด TPIX ตอนนี้ — ชำระด้วย TPIX ได้สูงสุด %s บาทต่อรายการ',
                number_format(max(0, $maxThb), 2)
            ));
        }
    }

    /**
     * ยังอยู่ในเพดานต่อผู้ใช้ 24 ชม. หรือไม่ (ใช้กับเงินที่เข้ามาแล้ว — โอนช้า/โอนซ้ำ — ซึ่งปฏิเสธไม่ได้ แต่ห้ามเติมเกินเพดานอัตโนมัติ)
     */
    private function withinDailyCap(?int $userId, float $amountThb): bool
    {
        $perDay = $this->settings->maxPerUserDayThb();
        if ($perDay <= 0 || ! $userId) {
            return true;
        }

        $used = (float) TpixPayment::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->whereIn('status', TpixPayment::SETTLED_STATUSES)
            ->sum('amount_thb');

        return $used + $amountThb <= $perDay;
    }

    // =====================================================================
    // รับเลขธุรกรรม + ตรวจ + ส่งมอบ
    // =====================================================================

    /**
     * ลูกค้าส่งเลขธุรกรรม (หลังโอนผ่าน MetaMask หรือวางเอง)
     *
     * @return array{status: string, message: string}
     */
    public function submitTx(TpixPayment $payment, string $hash): array
    {
        $hash = strtolower(trim($hash));

        if (! TpixTxVerifier::isValidHash($hash)) {
            return ['status' => 'rejected', 'message' => 'รูปแบบเลขธุรกรรมไม่ถูกต้อง (ต้องขึ้นต้น 0x ตามด้วยตัวอักษร 64 ตัว)'];
        }

        if ($payment->isSettled()) {
            return ['status' => $payment->status, 'message' => 'รายการนี้ชำระเรียบร้อยแล้ว'];
        }

        $usedBy = TpixPayment::where('tx_hash', $hash)->first();
        if ($usedBy && $usedBy->id !== $payment->id) {
            return ['status' => 'rejected', 'message' => 'ธุรกรรมนี้ถูกใช้ชำระรายการอื่นไปแล้ว'];
        }

        return $this->verifyAndSettle($payment, $hash);
    }

    /**
     * ตรวจธุรกรรมกับเชนแล้วดำเนินการตามผล
     *
     * @return array{status: string, message: string}
     */
    public function verifyAndSettle(TpixPayment $payment, string $hash): array
    {
        $hash = strtolower($hash);

        try {
            $check = $this->verifier->check($payment, $hash);
        } catch (\Throwable $e) {
            // RPC ล่ม = "ยังไม่รู้" — จำเลขธุรกรรมไว้ให้ตัวสแกนตรวจซ้ำ ห้ามตัดสินว่าไม่ผ่าน
            $this->rememberSubmittedHash($payment, $hash);
            Log::warning('TpixPay: verify deferred (chain unreachable)', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);

            return ['status' => 'pending', 'message' => 'ตรวจสอบกับเชนไม่ได้ชั่วคราว ระบบจะตรวจให้อัตโนมัติภายในไม่กี่นาที'];
        }

        switch ($check['status']) {
            case TpixTxVerifier::REJECTED:
                $payment->forceFill(['failure_reason' => mb_substr((string) $check['reason'], 0, 255)])->save();

                return ['status' => 'rejected', 'message' => (string) $check['reason']];

            case TpixTxVerifier::NOT_FOUND:
                $this->rememberSubmittedHash($payment, $hash);

                return ['status' => 'pending', 'message' => (string) $check['reason']];

            case TpixTxVerifier::CONFIRMING:
                // ❗ ผูกเลขธุรกรรมเฉพาะเมื่อเข้าบล็อกแล้ว + receipt สำเร็จ — ธุรกรรมที่ยังค้างใน mempool อาจถูกเร่ง/ยกเลิก/หลุด
                //    ถ้าผูกไว้ก่อน ใบจะติดกับ hash ที่ไม่มีวันผ่าน และ hash จริงที่โอนทีหลังจะถูกปฏิเสธ (คนอื่นแกล้งจองใบได้ด้วย)
                if ($check['block_number'] === null) {
                    $this->rememberSubmittedHash($payment, $hash);

                    return ['status' => 'pending', 'message' => 'ธุรกรรมยังรอเข้าบล็อก ระบบจะตรวจให้อัตโนมัติ'];
                }

                if (! $this->claimHash($payment, $hash, $check, TpixPayment::STATUS_CONFIRMING)) {
                    return ['status' => 'rejected', 'message' => 'ธุรกรรมนี้ถูกใช้ชำระรายการอื่นไปแล้ว'];
                }

                return ['status' => 'confirming', 'message' => sprintf(
                    'พบธุรกรรมแล้ว กำลังรอยืนยันบนเชน (%d/%d)',
                    $check['confirmations'],
                    $this->settings->confirmations()
                )];

            case TpixTxVerifier::LATE:
            case TpixTxVerifier::OK:
                try {
                    return $check['status'] === TpixTxVerifier::OK
                        ? $this->settle($payment, $hash, $check)
                        : $this->settleLate($payment, $hash, $check);
                } catch (\Throwable $e) {
                    // บันทึกการรับเงินไม่สำเร็จ (ทั้งก้อน rollback) → จำเลขธุรกรรมไว้ ตัวสแกนลองใหม่ทุกนาที
                    $this->rememberSubmittedHash($payment->fresh() ?? $payment, $hash);
                    Log::error('TpixPay: settle failed', ['payment_id' => $payment->id, 'tx_hash' => $hash, 'error' => $e->getMessage()]);

                    return ['status' => 'pending', 'message' => 'ได้รับธุรกรรมแล้ว ระบบกำลังบันทึกการชำระ กรุณารอสักครู่'];
                }
        }

        return ['status' => 'pending', 'message' => 'กำลังตรวจสอบ'];
    }

    /**
     * ได้รับเงินครบ → ส่งมอบ (ออเดอร์จ่ายแล้ว / เติมวอลเลต) หรือเติมเข้ากระเป๋าถ้าออเดอร์ปิดไปแล้ว
     *
     * @param  array<string, mixed>  $check
     * @return array{status: string, message: string}
     */
    public function settle(TpixPayment $payment, string $hash, array $check): array
    {
        try {
            $outcome = DB::transaction(function () use ($payment, $hash, $check) {
                /** @var TpixPayment|null $locked */
                $locked = TpixPayment::whereKey($payment->id)->lockForUpdate()->first();
                if (! $locked) {
                    return ['status' => 'rejected', 'message' => 'ไม่พบรายการชำระเงิน'];
                }

                if ($locked->isSettled()) {
                    return ['status' => $locked->status, 'message' => 'รายการนี้ชำระเรียบร้อยแล้ว'];
                }

                if ($locked->tx_hash !== null && $locked->tx_hash !== $hash) {
                    return ['status' => 'rejected', 'message' => 'รายการนี้ผูกกับธุรกรรมอื่นอยู่แล้ว'];
                }

                // ผูกเลขธุรกรรม (unique — ธุรกรรมเดียวจ่ายได้ใบเดียว ชนกัน = ทั้งก้อน rollback)
                $locked->forceFill($this->txFields($hash, $check))->save();

                if ($locked->purpose === TpixPayment::PURPOSE_WALLET_TOPUP) {
                    $transaction = $locked->payment_transaction_id
                        ? PaymentTransaction::whereKey($locked->payment_transaction_id)->lockForUpdate()->first()
                        : null;

                    // บิลเติมเงินเดียวกันถูกปิดไปแล้วด้วยใบ TPIX อีกใบ (ออกใบซ้ำช่วงใกล้หมดเวลา) → เติมใบนี้แยก ไม่กลืนเงิน
                    if ($transaction && $transaction->isCompleted()) {
                        $this->creditWallet($locked, (float) $locked->amount_thb, 'เติมเงินด้วย TPIX ('.$locked->reference.')');
                        $this->markSettled($locked, TpixPayment::STATUS_CREDITED_WALLET);

                        return ['status' => TpixPayment::STATUS_CREDITED_WALLET, 'message' => 'เติมเงินเข้ากระเป๋าเรียบร้อยแล้ว'];
                    }

                    $this->completeTransaction($locked);
                    $this->markSettled($locked, TpixPayment::STATUS_PAID);

                    return ['status' => TpixPayment::STATUS_PAID, 'message' => 'เติมเงินเข้ากระเป๋าเรียบร้อยแล้ว'];
                }

                $order = Order::whereKey($locked->payable_id)->lockForUpdate()->first();
                $payable = $order
                    && $order->payment_status !== 'paid'
                    && ! in_array($order->status, ['cancelled', 'refunded'], true);

                if ($payable) {
                    $this->completeTransaction($locked);
                    $this->cancelSiblingQuotes($locked);
                    $this->markSettled($locked, TpixPayment::STATUS_PAID);

                    return ['status' => TpixPayment::STATUS_PAID, 'message' => 'ชำระเงินด้วย TPIX สำเร็จ'];
                }

                // ออเดอร์จ่ายด้วยช่องทางอื่น/ถูกยกเลิกไปแล้ว → เงินไม่หาย: เติมมูลค่าบาทเข้ากระเป๋า
                $this->creditWallet(
                    $locked,
                    (float) $locked->amount_thb,
                    'ชำระ TPIX ของคำสั่งซื้อ #'.($order->order_number ?? $locked->payable_id).' หลังคำสั่งซื้อปิดแล้ว — เติมเข้ากระเป๋าแทน'
                );
                $this->closeTransaction($locked, 'ได้รับ TPIX หลังคำสั่งซื้อปิด — เติมมูลค่าเข้ากระเป๋าเงินแทน');
                $this->markSettled($locked, TpixPayment::STATUS_CREDITED_WALLET);

                return [
                    'status' => TpixPayment::STATUS_CREDITED_WALLET,
                    'message' => 'ได้รับเหรียญแล้ว แต่คำสั่งซื้อนี้ปิดไปก่อน จึงเติมมูลค่า '.number_format((float) $locked->amount_thb, 2).' บาทเข้ากระเป๋าเงินของคุณแทน',
                ];
            });
        } catch (UniqueConstraintViolationException $e) {
            return ['status' => 'rejected', 'message' => 'ธุรกรรมนี้ถูกใช้ชำระรายการอื่นไปแล้ว'];
        }

        if ($outcome['status'] === TpixPayment::STATUS_PAID || $outcome['status'] === TpixPayment::STATUS_CREDITED_WALLET) {
            Log::info('TpixPay: payment settled', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'status' => $outcome['status'],
                'tx_hash' => $hash,
            ]);
        }

        return $outcome;
    }

    /**
     * โอนหลังหมดเวลา → เติมเข้ากระเป๋าตามมูลค่าปัจจุบันของเหรียญที่ได้รับ (ไม่ใช้ราคาในใบเก่า)
     * แล้วลองจ่ายออเดอร์ที่ยังค้างจากกระเป๋าให้เลย (ถ้ายอดพอ)
     *
     * @param  array<string, mixed>  $check
     * @return array{status: string, message: string}
     */
    public function settleLate(TpixPayment $payment, string $hash, array $check): array
    {
        if ($check['confirmations'] < $this->settings->confirmations()) {
            if (! $this->claimHash($payment, $hash, $check, TpixPayment::STATUS_CONFIRMING)) {
                return ['status' => 'rejected', 'message' => 'ธุรกรรมนี้ถูกใช้ชำระรายการอื่นไปแล้ว'];
            }

            return ['status' => 'confirming', 'message' => 'พบธุรกรรมแล้ว กำลังรอยืนยันบนเชน'];
        }

        $rate = $this->prices->quoteRate();
        if ($rate === null) {
            // ยังไม่รู้ราคา → ผูกธุรกรรมไว้สถานะ late ให้ตัวสแกนลองใหม่
            $this->claimHash($payment, $hash, $check, TpixPayment::STATUS_LATE);

            return ['status' => TpixPayment::STATUS_LATE, 'message' => 'ได้รับเหรียญหลังหมดเวลา ระบบจะคิดมูลค่าตามราคาปัจจุบันและเติมเข้ากระเป๋าให้'];
        }

        $valueThb = floor((float) bcmul((string) $payment->amount_tpix, $rate['rate_thb'], 4) * 100) / 100;

        // เงินเข้าแล้วแต่เกินเพดานต่อวัน → ไม่เติมอัตโนมัติ (กันใช้โอนช้าเป็นทางแลกเหรียญเป็นบาทเกินเพดาน) ให้แอดมินอนุมัติ
        if (! $this->withinDailyCap($payment->user_id, $valueThb)) {
            return $this->holdForReview($payment, $hash, $check, 'โอนหลังหมดเวลาและเกินเพดานต่อวัน — รอแอดมินอนุมัติ');
        }

        try {
            $outcome = DB::transaction(function () use ($payment, $hash, $check, $valueThb, $rate) {
                $locked = TpixPayment::whereKey($payment->id)->lockForUpdate()->first();
                if (! $locked || $locked->isSettled()) {
                    return ['status' => $locked?->status ?? 'rejected', 'message' => 'รายการนี้ดำเนินการแล้ว'];
                }
                if ($locked->tx_hash !== null && $locked->tx_hash !== $hash) {
                    return ['status' => 'rejected', 'message' => 'รายการนี้ผูกกับธุรกรรมอื่นอยู่แล้ว'];
                }

                $locked->forceFill($this->txFields($hash, $check))->save();

                if ($valueThb > 0) {
                    $this->creditWallet($locked, $valueThb, 'ได้รับ TPIX หลังรายการ '.$locked->reference.' หมดเวลา — คิดมูลค่าตามราคาปัจจุบัน');
                }
                $this->closeTransaction($locked, 'โอน TPIX หลังหมดเวลา — เติมมูลค่าปัจจุบันเข้ากระเป๋าเงิน');

                $locked->meta = array_merge($locked->meta ?? [], [
                    'late' => true,
                    'late_rate_thb' => $rate['rate_thb'],
                    'late_value_thb' => $valueThb,
                ]);
                $this->markSettled($locked, TpixPayment::STATUS_CREDITED_WALLET);

                return ['status' => TpixPayment::STATUS_CREDITED_WALLET, 'message' => ''];
            });
        } catch (UniqueConstraintViolationException $e) {
            return ['status' => 'rejected', 'message' => 'ธุรกรรมนี้ถูกใช้ชำระรายการอื่นไปแล้ว'];
        }

        if ($outcome['status'] !== TpixPayment::STATUS_CREDITED_WALLET) {
            return $outcome;
        }

        Log::warning('TpixPay: late payment credited to wallet at current rate', [
            'payment_id' => $payment->id,
            'value_thb' => $valueThb,
            'tx_hash' => $hash,
        ]);

        $paidOrder = $this->tryPayOrderFromWallet($payment->fresh());

        return [
            'status' => TpixPayment::STATUS_CREDITED_WALLET,
            'message' => $paidOrder
                ? 'ได้รับเหรียญหลังหมดเวลา — เติม '.number_format($valueThb, 2).' บาทตามราคาปัจจุบันเข้ากระเป๋า และชำระคำสั่งซื้อจากกระเป๋าให้แล้ว'
                : 'ได้รับเหรียญหลังหมดเวลา — เติม '.number_format($valueThb, 2).' บาทตามราคาปัจจุบันเข้ากระเป๋าเงินของคุณแล้ว',
        ];
    }

    /**
     * แอดมินอนุมัติเงินที่เข้ามาหลังหมดเวลา/โอนซ้ำที่ติดเพดาน → ตรวจบนเชนซ้ำ แล้วเติมเข้ากระเป๋าตามราคาปัจจุบัน
     *
     * @return array{status: string, message: string}
     */
    public function approveHeld(TpixPayment $payment): array
    {
        if ($payment->status !== TpixPayment::STATUS_LATE || ! $payment->tx_hash || empty($payment->meta['needs_review'])) {
            return ['status' => 'rejected', 'message' => 'รายการนี้ไม่ได้รอการอนุมัติ'];
        }

        try {
            $check = $this->verifier->check($payment, (string) $payment->tx_hash);
        } catch (\Throwable $e) {
            return ['status' => 'pending', 'message' => 'ตรวจกับเชนไม่ได้ชั่วคราว ลองใหม่อีกครั้ง'];
        }

        // ใบโอนซ้ำถูกสร้างหลังธุรกรรม → ช่วงเวลา/ยอดตรวจกับใบต้นทางไปแล้วตอนบันทึก ที่นี่ยืนยันแค่ว่าธุรกรรมสำเร็จ + ยอดตรง
        $mined = $check['block_number'] !== null && $check['value_wei'] === (string) $payment->amount_wei;
        if (! $mined || $check['confirmations'] < $this->settings->confirmations()) {
            return ['status' => 'rejected', 'message' => 'ยืนยันธุรกรรมบนเชนไม่ผ่าน: '.($check['reason'] ?? 'ยอดหรือ confirmation ไม่ครบ')];
        }

        $rate = $this->prices->quoteRate();
        if ($rate === null) {
            return ['status' => 'pending', 'message' => 'ยังไม่รู้ราคา TPIX ปัจจุบัน ลองใหม่อีกครั้ง'];
        }
        $valueThb = floor((float) bcmul((string) $payment->amount_tpix, $rate['rate_thb'], 4) * 100) / 100;

        DB::transaction(function () use ($payment, $valueThb, $rate) {
            $locked = TpixPayment::whereKey($payment->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== TpixPayment::STATUS_LATE) {
                return;
            }
            if ($valueThb > 0) {
                $this->creditWallet($locked, $valueThb, 'แอดมินอนุมัติ: ได้รับ TPIX '.$locked->reference.' — คิดมูลค่าตามราคาปัจจุบัน');
            }
            $this->closeTransaction($locked, 'แอดมินอนุมัติเงิน TPIX ที่ติดเพดาน — เติมเข้ากระเป๋าเงิน');
            $locked->meta = array_merge($locked->meta ?? [], [
                'needs_review' => false,
                'approved_at' => now()->toIso8601String(),
                'late_rate_thb' => $rate['rate_thb'],
                'late_value_thb' => $valueThb,
            ]);
            $locked->failure_reason = null;
            $this->markSettled($locked, TpixPayment::STATUS_CREDITED_WALLET);
        });

        return ['status' => TpixPayment::STATUS_CREDITED_WALLET, 'message' => 'เติม '.number_format($valueThb, 2).' บาทเข้ากระเป๋าผู้ใช้แล้ว'];
    }

    /**
     * ผูกธุรกรรมไว้สถานะ late + ธงรอแอดมิน (ตัวสแกนไม่วนตรวจซ้ำ)
     *
     * @param  array<string, mixed>  $check
     * @return array{status: string, message: string}
     */
    private function holdForReview(TpixPayment $payment, string $hash, array $check, string $reason): array
    {
        if (! $this->claimHash($payment, $hash, $check, TpixPayment::STATUS_LATE)) {
            return ['status' => 'rejected', 'message' => 'ธุรกรรมนี้ถูกใช้ชำระรายการอื่นไปแล้ว'];
        }

        $fresh = $payment->fresh();
        if ($fresh) {
            $fresh->forceFill([
                'failure_reason' => $reason,
                'meta' => array_merge($fresh->meta ?? [], ['needs_review' => true]),
            ])->save();
        }

        Log::critical('TpixPay: payment held for admin review', ['payment_id' => $payment->id, 'reason' => $reason]);

        return ['status' => TpixPayment::STATUS_LATE, 'message' => 'ได้รับเหรียญแล้ว — รายการนี้รอแอดมินตรวจสอบก่อนเติมเข้ากระเป๋า'];
    }

    /**
     * ตรวจสถานะใบเดียวทันที (หน้าจ่าย/แอปโพล) — ไม่ต้องรอตัวสแกนรอบนาที · กันยิงถี่ด้วยล็อก 8 วิต่อใบ
     */
    public function refresh(TpixPayment $payment): TpixPayment
    {
        if ($payment->isSettled()) {
            return $payment;
        }

        $grace = (int) config('tpix_pay.late_grace_minutes', 120);
        if ($payment->expires_at !== null && $payment->expires_at->lt(now()->subMinutes($grace)) && $payment->tx_hash === null) {
            return $payment;
        }

        if (! \Illuminate\Support\Facades\Cache::add('tpix_pay:refresh:'.$payment->id, 1, 8)) {
            return $payment;
        }

        try {
            if ($payment->tx_hash !== null) {
                $this->verifyAndSettle($payment, (string) $payment->tx_hash);
            } elseif (! empty($payment->meta['submitted_hashes'])) {
                foreach ((array) $payment->meta['submitted_hashes'] as $hash) {
                    $result = $this->verifyAndSettle($payment->fresh(), (string) $hash);
                    if ($result['status'] !== 'rejected') {
                        break;
                    }
                }
            } else {
                // ยังไม่มีเลขธุรกรรม → ดูการโอนเข้ากระเป๋าที่ยอดตรงกับใบนี้
                foreach ($this->incomingTransfers(strtolower($payment->receive_address), $payment->created_at) as $item) {
                    $hash = strtolower((string) ($item['hash'] ?? ''));
                    if (($item['value'] ?? null) === $payment->amount_wei && TpixTxVerifier::isValidHash($hash)
                        && ! TpixPayment::where('tx_hash', $hash)->exists()) {
                        $this->verifyAndSettle($payment, $hash);
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('TpixPay: refresh failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
        }

        return $payment->fresh() ?? $payment;
    }

    // =====================================================================
    // ตัวสแกน (ทุกนาที) — หาการโอนที่ลูกค้าไม่ได้ส่งเลขธุรกรรม + ตรวจรายการที่รอ confirmation
    // =====================================================================

    /**
     * @return array{snapshot: bool, confirmed: int, matched: int, expired: int, released: int, stray: int, cancelled_orders: int}
     */
    public function scan(): array
    {
        $stats = ['snapshot' => false, 'confirmed' => 0, 'matched' => 0, 'expired' => 0, 'released' => 0, 'stray' => 0, 'cancelled_orders' => 0];

        // 1) ราคาย้อนหลัง (ค่าเฉลี่ยกันปั่นราคา) — บันทึกเองไม่เกินนาทีละครั้ง
        $stats['snapshot'] = $this->prices->computeSpot() !== null;

        $grace = (int) config('tpix_pay.late_grace_minutes', 120);

        // 2) ใบที่หมดเวลา: แสดงสถานะหมดเวลา (ยังสแกนหาเงินที่โอนก่อนหมดเวลาต่อจนพ้นช่วงกันยอด)
        $stats['expired'] = TpixPayment::where('status', TpixPayment::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => TpixPayment::STATUS_EXPIRED, 'updated_at' => now()]);

        // 3) ปล่อยยอดที่พ้นช่วงกันยอดแล้ว (ใบใหม่ใช้ยอดเดิมได้)
        $stats['released'] = TpixPayment::whereNotNull('open_key')
            ->where('expires_at', '<', now()->subMinutes($grace))
            ->update(['open_key' => null, 'updated_at' => now()]);

        // 4) รายการที่ผูกธุรกรรมแล้ว รอ confirmation / รอคิดราคา (late)
        $waiting = TpixPayment::whereIn('status', [TpixPayment::STATUS_CONFIRMING, TpixPayment::STATUS_LATE])
            ->whereNotNull('tx_hash')
            ->where('updated_at', '>=', now()->subDays(2))
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->reject(fn (TpixPayment $p) => ! empty($p->meta['needs_review']));
        foreach ($waiting as $payment) {
            try {
                $result = $this->verifyAndSettle($payment, (string) $payment->tx_hash);
                if (in_array($result['status'], TpixPayment::SETTLED_STATUSES, true)) {
                    $stats['confirmed']++;
                }
            } catch (\Throwable $e) {
                Log::error('TpixPay scan: confirm failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            }
        }

        // 5) เลขธุรกรรมที่ลูกค้าส่งมาตอนเชนล่ม/ยังไม่เจอ
        // submitted_at ถูกตั้งตอนจำเลขธุรกรรม (rememberSubmittedHash) — ใช้กรองใน SQL ก่อน limit (meta มีทุกใบ กรองไม่ได้)
        $submitted = TpixPayment::whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED, TpixPayment::STATUS_FAILED, TpixPayment::STATUS_CANCELLED])
            ->whereNull('tx_hash')
            ->whereNotNull('submitted_at')
            ->where('expires_at', '>=', now()->subMinutes($grace))
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->filter(fn (TpixPayment $p) => ! empty($p->meta['submitted_hashes']));
        foreach ($submitted as $payment) {
            foreach ((array) $payment->meta['submitted_hashes'] as $hash) {
                try {
                    if (TpixPayment::where('tx_hash', $hash)->exists()) {
                        continue;
                    }
                    $result = $this->verifyAndSettle($payment->fresh(), (string) $hash);
                    if (in_array($result['status'], ['confirming', ...TpixPayment::SETTLED_STATUSES], true)) {
                        $stats['matched']++;
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::error('TpixPay scan: submitted hash failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                }
            }
        }

        // 6) ไล่ทุกบล็อกใหม่ทาง RPC (เก็บตำแหน่งที่สแกนถึงใน DB) แล้วจับคู่การโอนเข้ากระเป๋ารับเงินด้วยยอด wei ตรงตัว
        //    (เดิมใช้ explorer 3 หน้า — ธุรกรรม 0 TPIX ฟรีจำนวนมากดันเงินจริงหลุดหน้าต่างได้)
        $open = TpixPayment::whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED, TpixPayment::STATUS_FAILED, TpixPayment::STATUS_CANCELLED])
            ->whereNull('tx_hash')
            ->where('expires_at', '>=', now()->subMinutes($grace))
            ->get();
        $settledRecently = TpixPayment::whereIn('status', TpixPayment::SETTLED_STATUSES)
            ->whereNotNull('open_key')
            ->get();

        $addresses = $open->pluck('receive_address')->merge($settledRecently->pluck('receive_address'))
            ->map(fn ($a) => strtolower((string) $a))->unique()->values()->all();

        try {
            $incoming = $this->incomingFromBlocks($addresses);
        } catch (\Throwable $e) {
            Log::warning('TpixPay scan: block scan failed', ['error' => $e->getMessage()]);
            $incoming = [];
        }

        foreach ($incoming as $item) {
            try {
                $hash = $item['hash'];
                $value = $item['value'];
                $address = $item['to'];
                if (TpixPayment::where('tx_hash', $hash)->exists()) {
                    continue;
                }

                $match = $open->first(fn (TpixPayment $p) => strtolower($p->receive_address) === $address
                    && $p->amount_wei === $value && $p->tx_hash === null);
                if ($match) {
                    $result = $this->verifyAndSettle($match->fresh(), $hash);
                    if (in_array($result['status'], ['confirming', ...TpixPayment::SETTLED_STATUSES], true)) {
                        $stats['matched']++;
                        $match->tx_hash = $hash; // กันจับคู่ซ้ำในรอบเดียวกัน
                    }

                    continue;
                }

                // ยอดตรงกับใบที่จ่ายไปแล้ว (โอนซ้ำ) → เติมเข้ากระเป๋า (เกินเพดาน = รอแอดมิน)
                $origin = $settledRecently->first(fn (TpixPayment $p) => strtolower($p->receive_address) === $address
                    && $p->amount_wei === $value);
                if ($origin && $this->creditDuplicateTransfer($origin, $hash)) {
                    $stats['stray']++;
                }
            } catch (\Throwable $e) {
                Log::error('TpixPay scan: incoming transfer failed', ['tx_hash' => $item['hash'] ?? null, 'error' => $e->getMessage()]);
            }
        }

        // 7) ออเดอร์ TPIX ที่ไม่มีการโอนจนพ้นช่วงกันยอด → ยกเลิกผ่าน Order::cancel (คืนคูปอง/สต็อก)
        //    (งานล้างบิลของ SMS Checker ดูแลเฉพาะพร้อมเพย์/โอน)
        $stats['cancelled_orders'] = $this->expireAbandonedOrders();

        return $stats;
    }

    /**
     * ยกเลิกออเดอร์ที่เลือกจ่าย TPIX แต่ไม่มีการโอนเลยจนพ้นเวลา + ช่วงกันยอด
     */
    public function expireAbandonedOrders(): int
    {
        $grace = (int) config('tpix_pay.late_grace_minutes', 120);
        $cancelled = 0;

        $orderIds = Order::where('payment_method', 'tpix')
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where('created_at', '<', now()->subMinutes((int) config('tpix_pay.quote_ttl_minutes', 30) + $grace))
            ->whereNotExists(function ($q) use ($grace) {
                // ยังมีใบ TPIX ที่จ่ายได้/รอยืนยัน/ยังอยู่ในช่วงกันยอด → ยังไม่ยกเลิก
                $q->selectRaw('1')->from('tpix_payments')
                    ->whereColumn('tpix_payments.payable_id', 'orders.id')
                    ->where('tpix_payments.purpose', TpixPayment::PURPOSE_ORDER)
                    ->where(function ($open) use ($grace) {
                        // ผูกธุรกรรมแล้วแต่ยังไม่จบ (รอ confirmation / โอนช้ารอคิดราคา) หรือยังอยู่ในช่วงกันยอด
                        $open->where(fn ($q) => $q->whereNotNull('tpix_payments.tx_hash')
                            ->whereIn('tpix_payments.status', [TpixPayment::STATUS_CONFIRMING, TpixPayment::STATUS_LATE]))
                            ->orWhere(fn ($q) => $q->whereNull('tpix_payments.tx_hash')
                                ->where('tpix_payments.expires_at', '>=', now()->subMinutes($grace)));
                    });
            })
            ->whereNotExists(function ($q) {
                // มีบิลช่องทางอื่นที่ยังจ่ายได้ (เช่น ลูกค้าเปลี่ยนไปจ่ายพร้อมเพย์/วอลเลต)
                $q->selectRaw('1')->from('payment_transactions')
                    ->whereColumn('payment_transactions.order_id', 'orders.id')
                    ->where('payment_transactions.payment_method', '!=', 'tpix')
                    ->whereIn('payment_transactions.status', ['pending', 'processing'])
                    ->where(function ($live) {
                        $live->whereNull('payment_transactions.expired_at')
                            ->orWhere('payment_transactions.expired_at', '>', now())
                            // QR พร้อมเพย์/โอนที่ยอดจองยังใช้ได้ (อยู่ได้ ~24 ชม. เกินอายุบิล) — SMS ยังตัดบิลได้
                            ->orWhereExists(function ($ua) {
                                $ua->selectRaw('1')->from('unique_payment_amounts')
                                    ->whereColumn('unique_payment_amounts.transaction_id', 'payment_transactions.id')
                                    ->where('unique_payment_amounts.status', 'reserved')
                                    ->where('unique_payment_amounts.expires_at', '>', now());
                            });
                    });
            })
            ->orderBy('id')
            ->limit(100)
            ->pluck('id');

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::find($orderId);
                if (! $order) {
                    continue;
                }

                $order->cancel('หมดเวลาชำระด้วยเหรียญ TPIX - ระบบยกเลิกอัตโนมัติ', null, 'system');

                Order::whereKey($order->id)
                    ->where('status', 'cancelled')
                    ->where('payment_status', 'pending')
                    ->update(['payment_status' => 'failed']);

                TpixPayment::where('purpose', TpixPayment::PURPOSE_ORDER)
                    ->where('payable_id', $order->id)
                    ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED])
                    ->update(['status' => TpixPayment::STATUS_EXPIRED, 'updated_at' => now()]);

                $cancelled++;
            } catch (\Throwable $e) {
                Log::warning('TpixPay: cancel abandoned order failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            }
        }

        return $cancelled;
    }

    /**
     * การโอนเหรียญ TPIX (native) เข้ากระเป๋าที่ระบุ จากบล็อกใหม่ตั้งแต่ตำแหน่งที่สแกนล่าสุด (เก็บใน settings — ไม่หายตอน deploy)
     *
     * ไม่มีใบเปิด → เลื่อนตำแหน่งไปบล็อกล่าสุดเฉย ๆ (ธุรกรรมที่จ่ายใบต้องเกิดหลังออกใบอยู่แล้ว)
     * สแกนสูงสุด scan_max_blocks ต่อรอบ (ค้างนานก็ไล่ตามทัน) · ขอทีละ scan_batch บล็อกในคำขอเดียว (JSON-RPC batch)
     *
     * @param  array<int, string>  $addresses  ที่อยู่ตัวพิมพ์เล็ก
     * @return array<int, array{hash: string, value: string, to: string}>
     */
    public function incomingFromBlocks(array $addresses): array
    {
        $latest = $this->rpc->blockNumber();
        $cursor = (int) \App\Models\Setting::get('tpix_pay.scan_block', 0);
        $lookback = (int) ceil(((int) config('tpix_pay.quote_ttl_minutes', 30) + (int) config('tpix_pay.late_grace_minutes', 120)) * 60 / 2);

        if ($addresses === [] || $cursor <= 0) {
            // เริ่มครั้งแรกย้อน ~1 นาที · ไม่มีใบเปิด = ไม่ต้องอ่านบล็อก
            $this->saveScanCursor($addresses === [] ? $latest : max(0, $latest - 30));
            if ($addresses === []) {
                return [];
            }
            $cursor = max(0, $latest - 30);
        }

        // ระบบหยุดไปนาน → ย้อนไม่เกินช่วงที่ใบยังรับเงินได้
        $from = max($cursor + 1, $latest - $lookback);
        $to = min($latest, $from + max(30, (int) config('tpix_pay.scan_max_blocks', 900)) - 1);
        if ($from > $to) {
            return [];
        }

        $wanted = array_flip($addresses);
        $found = [];
        $batch = max(5, (int) config('tpix_pay.scan_batch', 40));

        for ($start = $from; $start <= $to; $start += $batch) {
            $end = min($to, $start + $batch - 1);
            $calls = [];
            for ($n = $start; $n <= $end; $n++) {
                $calls[] = ['eth_getBlockByNumber', ['0x'.dechex($n), true]];
            }

            $blocks = $this->rpc->batch($calls);
            foreach ($blocks as $i => $block) {
                if (! is_array($block)) {
                    // บล็อกหายจากคำตอบ → หยุดก่อนบล็อกนี้ รอบหน้าอ่านต่อ (ไม่ข้ามเงิน)
                    $this->saveScanCursor($start + $i - 1);

                    return $found;
                }
                foreach ((array) ($block['transactions'] ?? []) as $tx) {
                    if (! is_array($tx)) {
                        continue;
                    }
                    $toAddr = strtolower((string) ($tx['to'] ?? ''));
                    if (! isset($wanted[$toAddr])) {
                        continue;
                    }
                    $value = TpixAmount::hexToDec((string) ($tx['value'] ?? '0x0'));
                    if ($value === '0' || ! TpixTxVerifier::isValidHash($tx['hash'] ?? null)) {
                        continue;
                    }
                    $found[] = ['hash' => strtolower((string) $tx['hash']), 'value' => $value, 'to' => $toAddr];
                }
            }
            $this->saveScanCursor($end);
        }

        return $found;
    }

    private function saveScanCursor(int $block): void
    {
        \App\Models\Setting::set('tpix_pay.scan_block', (string) max(0, $block), 'integer', 'tpix_pay');
    }

    /**
     * การโอนเข้ากระเป๋า (Blockscout API v2) ตั้งแต่ $since — ใช้หา "ผู้สมัคร" เท่านั้น ตัดสินด้วย RPC
     *
     * @return array<int, array<string, mixed>>
     */
    public function incomingTransfers(string $address, mixed $since = null): array
    {
        $base = rtrim((string) config('tpix_pay.explorer_url'), '/').'/api/v2/addresses/'.$address.'/transactions';
        $sinceTs = $since ? \Illuminate\Support\Carbon::parse($since)->getTimestamp() - 120 : 0;
        $items = [];
        $params = ['filter' => 'to'];

        for ($page = 0; $page < 3; $page++) {
            try {
                $response = Http::withHeaders(['User-Agent' => (string) config('tpix_pay.user_agent'), 'Accept' => 'application/json'])
                    ->timeout((int) config('tpix_pay.http_timeout', 8))
                    ->get($base, $params);
            } catch (\Throwable $e) {
                Log::warning('TpixPay: explorer unreachable', ['error' => $e->getMessage()]);
                break;
            }

            if (! $response->successful()) {
                break;
            }

            $reachedOld = false;
            foreach ((array) $response->json('items', []) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $ts = isset($item['timestamp']) ? strtotime((string) $item['timestamp']) : false;
                if ($ts !== false && $ts < $sinceTs) {
                    $reachedOld = true;

                    continue;
                }
                if (($item['status'] ?? 'ok') !== 'ok' && ($item['result'] ?? 'success') !== 'success') {
                    continue;
                }
                $items[] = [
                    'hash' => $item['hash'] ?? null,
                    'value' => $item['value'] ?? null,
                ];
            }

            $next = $response->json('next_page_params');
            if ($reachedOld || ! is_array($next) || $next === []) {
                break;
            }
            $params = ['filter' => 'to'] + $next;
        }

        return $items;
    }

    // =====================================================================
    // ภายใน
    // =====================================================================

    /**
     * @param  array<string, mixed>  $check
     * @return array<string, mixed>
     */
    private function txFields(string $hash, array $check): array
    {
        return [
            'tx_hash' => $hash,
            'from_address' => $check['from'] ?? null,
            'block_number' => $check['block_number'] ?? null,
            'confirmations' => (int) ($check['confirmations'] ?? 0),
            'failure_reason' => null,
        ];
    }

    /**
     * ผูกเลขธุรกรรมกับใบ (ยังไม่ส่งมอบ) — false = ธุรกรรมนี้ถูกใบอื่นใช้แล้ว
     *
     * @param  array<string, mixed>  $check
     */
    private function claimHash(TpixPayment $payment, string $hash, array $check, string $status): bool
    {
        try {
            return (bool) DB::transaction(function () use ($payment, $hash, $check, $status) {
                $locked = TpixPayment::whereKey($payment->id)->lockForUpdate()->first();
                if (! $locked || $locked->isSettled()) {
                    return true;
                }
                if ($locked->tx_hash !== null && $locked->tx_hash !== $hash) {
                    return false;
                }

                $locked->forceFill($this->txFields($hash, $check) + [
                    'status' => $status,
                    'submitted_at' => $locked->submitted_at ?? now(),
                ])->save();

                return true;
            });
        } catch (UniqueConstraintViolationException $e) {
            return false;
        }
    }

    private function markSettled(TpixPayment $payment, string $status): void
    {
        $payment->forceFill([
            'status' => $status,
            'paid_at' => now(),
            'submitted_at' => $payment->submitted_at ?? now(),
            // open_key คงไว้จนพ้นช่วงกันยอด — ลูกค้าโอนซ้ำยอดเดิมจะไม่ไปจ่ายใบของคนอื่น
        ])->save();
    }

    /**
     * ปิด PaymentTransaction ของใบนี้ผ่าน PaymentService (เส้นเดียวกับพร้อมเพย์: ตั้งจ่าย + ตัดสต็อก + ปิดบิลอื่น / เติมวอลเลต)
     */
    private function completeTransaction(TpixPayment $payment): void
    {
        $transaction = $payment->payment_transaction_id
            ? PaymentTransaction::whereKey($payment->payment_transaction_id)->lockForUpdate()->first()
            : null;

        if (! $transaction) {
            throw new \RuntimeException('ไม่พบรายการชำระเงินของใบ TPIX #'.$payment->id);
        }

        if ($transaction->isCompleted()) {
            return;
        }

        // บิลอาจถูกตั้งหมดเวลา/ยกเลิกระหว่างรอ confirmation — เงินเข้าจริงแล้ว เปิดกลับให้ปิดได้
        // (markAsCompleted รับเฉพาะ pending/processing)
        $transaction->forceFill([
            'status' => 'processing',
            'gateway' => 'tpix',
            'gateway_transaction_id' => $payment->tx_hash,
        ])->save();

        app(PaymentService::class)->completePayment($transaction);
    }

    private function closeTransaction(TpixPayment $payment, string $note): void
    {
        if (! $payment->payment_transaction_id) {
            return;
        }

        PaymentTransaction::whereKey($payment->payment_transaction_id)
            ->whereIn('status', ['pending', 'processing', 'failed'])
            ->update([
                'status' => 'cancelled',
                'notes' => $note,
                'gateway' => 'tpix',
                'gateway_transaction_id' => $payment->tx_hash,
                'updated_at' => now(),
            ]);
    }

    /**
     * ใบ TPIX อื่นของออเดอร์เดียวกันที่ยังเปิด → ยกเลิก (ยอดยังถูกกันไว้ ถ้าโอนเข้ามาภายหลังจะเติมเข้ากระเป๋า)
     */
    private function cancelSiblingQuotes(TpixPayment $paid): void
    {
        TpixPayment::where('purpose', TpixPayment::PURPOSE_ORDER)
            ->where('payable_id', $paid->payable_id)
            ->where('id', '!=', $paid->id)
            ->whereIn('status', [TpixPayment::STATUS_PENDING, TpixPayment::STATUS_EXPIRED])
            ->whereNull('tx_hash')
            ->update([
                'status' => TpixPayment::STATUS_CANCELLED,
                'failure_reason' => 'คำสั่งซื้อชำระด้วยรายการอื่นแล้ว',
                'updated_at' => now(),
            ]);
    }

    private function creditWallet(TpixPayment $payment, float $amountThb, string $description): void
    {
        $user = $payment->user_id ? User::find($payment->user_id) : null;
        if (! $user) {
            throw new \RuntimeException('ไม่พบผู้ใช้ของใบ TPIX #'.$payment->id);
        }

        $wallets = app(WalletService::class);
        $wallet = $wallets->getOrCreateWallet($user);

        $wallets->deposit(
            $wallet,
            round($amountThb, 2),
            $description,
            'tpix_payment',
            $payment->id,
            [
                'tpix_reference' => $payment->reference,
                'tx_hash' => $payment->tx_hash,
                'amount_tpix' => (string) $payment->amount_tpix,
            ],
            'refund'
        );
    }

    /**
     * ลูกค้าโอนยอดเดิมซ้ำ (ภายในช่วงกันยอด) → บันทึกเป็นใบใหม่สถานะเติมกระเป๋า (tx_hash unique กันเติมซ้ำ)
     */
    private function creditDuplicateTransfer(TpixPayment $origin, string $hash): bool
    {
        try {
            $check = $this->verifier->check($origin, $hash);
        } catch (\Throwable $e) {
            return false;
        }

        // เป็นธุรกรรมของยอดเดียวกันที่เกิดหลังออกใบเดิม — ยืนยันครบแล้วเท่านั้น (ยังไม่ครบ = รอบถัดไป)
        if (! in_array($check['status'], [TpixTxVerifier::OK, TpixTxVerifier::LATE], true)
            || $check['confirmations'] < $this->settings->confirmations()) {
            return false;
        }

        $rate = $this->prices->quoteRate();
        if ($rate === null) {
            return false;
        }
        $valueThb = floor((float) bcmul((string) $origin->amount_tpix, $rate['rate_thb'], 4) * 100) / 100;
        // เกินเพดานต่อวัน → บันทึกไว้ให้แอดมินอนุมัติ ไม่เติมอัตโนมัติ
        $review = ! $this->withinDailyCap($origin->user_id, $valueThb);

        try {
            return (bool) DB::transaction(function () use ($origin, $hash, $check, $rate, $valueThb, $review) {
                $stray = TpixPayment::create([
                    'reference' => 'TPX'.Str::upper(Str::random(21)),
                    'user_id' => $origin->user_id,
                    'purpose' => TpixPayment::PURPOSE_WALLET_TOPUP,
                    'payable_type' => 'tpix_duplicate',
                    'payable_id' => $origin->id,
                    'amount_thb' => number_format($valueThb, 2, '.', ''),
                    'rate_thb' => $rate['rate_thb'],
                    'price_usd' => $rate['price_usd'],
                    'usd_thb' => $rate['usd_thb'],
                    'amount_wei' => $origin->amount_wei,
                    'amount_tpix' => $origin->amount_tpix,
                    'receive_address' => $origin->receive_address,
                    'status' => $review ? TpixPayment::STATUS_LATE : TpixPayment::STATUS_CREDITED_WALLET,
                    'tx_hash' => $hash,
                    'from_address' => $check['from'],
                    'block_number' => $check['block_number'],
                    'confirmations' => $check['confirmations'],
                    'submitted_at' => now(),
                    'expires_at' => now(),
                    'paid_at' => $review ? null : now(),
                    'failure_reason' => $review ? 'โอนซ้ำยอดเดิมและเกินเพดานต่อวัน — รอแอดมินอนุมัติ' : null,
                    'meta' => ['duplicate_of' => $origin->reference, 'needs_review' => $review],
                ]);

                if ($review) {
                    Log::critical('TpixPay: duplicate transfer over the daily cap — waiting for admin review', ['stray_id' => $stray->id]);

                    return true;
                }

                if ($valueThb > 0) {
                    $this->creditWallet($stray, $valueThb, 'ได้รับ TPIX ซ้ำยอดเดิมของรายการ '.$origin->reference.' — เติมมูลค่าปัจจุบันเข้ากระเป๋า');
                }

                Log::warning('TpixPay: duplicate transfer credited to wallet', [
                    'origin_id' => $origin->id,
                    'stray_id' => $stray->id,
                    'value_thb' => $valueThb,
                ]);

                return true;
            });
        } catch (UniqueConstraintViolationException $e) {
            return false;
        }
    }

    /**
     * หลังเติมเงินจากการโอนช้า: ออเดอร์ยังรอจ่ายและยอดในกระเป๋าพอ → จ่ายจากกระเป๋าให้เลย (ไม่สำเร็จก็ไม่เป็นไร ลูกค้ากดจ่ายเองได้)
     */
    private function tryPayOrderFromWallet(?TpixPayment $payment): bool
    {
        if (! $payment || $payment->purpose !== TpixPayment::PURPOSE_ORDER) {
            return false;
        }

        try {
            $order = Order::find($payment->payable_id);
            if (! $order || $order->payment_status === 'paid' || in_array($order->status, ['cancelled', 'refunded'], true)) {
                return false;
            }

            $balance = (float) (\App\Models\Wallet::where('user_id', $order->user_id)->value('balance') ?? 0);
            if ($balance < (float) $order->total_amount) {
                return false;
            }

            $paymentService = app(PaymentService::class);
            $transaction = $paymentService->createOrderPayment($order, 'wallet', [
                'metadata' => ['source' => 'tpix_late_autopay', 'tpix_reference' => $payment->reference],
            ]);
            $result = $paymentService->processPayment($transaction, []);

            return (bool) ($result['success'] ?? false);
        } catch (\Throwable $e) {
            Log::warning('TpixPay: auto-pay order from wallet after late payment failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function rememberSubmittedHash(TpixPayment $payment, string $hash): void
    {
        $meta = $payment->meta ?? [];
        $hashes = array_values(array_unique(array_merge((array) ($meta['submitted_hashes'] ?? []), [$hash])));
        $meta['submitted_hashes'] = array_slice($hashes, -5);

        $payment->forceFill(['meta' => $meta, 'submitted_at' => $payment->submitted_at ?? now()])->save();
    }

    private function fixedLineCount(Order $order): int
    {
        return $order->items->filter(fn ($item) => TpixPriceService::fixedPriceFor($item->product) !== null)->count();
    }

    /**
     * ใบเก่าที่ถือ open_key นี้และพ้นช่วงกันยอดแล้ว → ปล่อยยอดให้ใบใหม่
     */
    private function releaseStaleOpenKey(string $openKey): void
    {
        $grace = (int) config('tpix_pay.late_grace_minutes', 120);

        TpixPayment::where('open_key', $openKey)
            ->where('expires_at', '<', now()->subMinutes($grace))
            ->update(['open_key' => null, 'updated_at' => now()]);
    }
}
