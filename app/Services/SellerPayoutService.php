<?php

namespace App\Services;

use App\Models\EarningsLedger;
use App\Models\Order;
use App\Models\PayoutSetting;
use App\Models\PlatformTransaction;
use App\Models\PlatformWallet;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorStore;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * จ่ายรายได้ผู้ขายจริง (audit SELLER-01 / G18)
 *
 * วงจรเงินของผู้ขาย:
 *  1. ออเดอร์ชำระแล้ว → OrderDistributionService สร้าง EarningsLedger (pending) และพักเงินสุทธิของผู้ขาย
 *     ไว้ในกระเป๋าแพลตฟอร์ม "seller_escrow" (เงินพักรอจ่ายผู้ขาย)
 *  2. ออเดอร์ส่งถึงแล้ว (delivered/completed) + ครบ holding_days (หรือส่งพัสดุแล้วเกิน N วันโดยลูกค้าไม่กดยืนยัน,
 *     หรือสินค้าดิจิทัลล้วนที่จ่ายแล้วครบ holding_days) → releaseLedger()
 *  3. releaseLedger: หักเงินพักออกจาก seller_escrow → หักหนี้ค้าง (ไม่เกิน 50%) → โอนเข้า wallet ผู้ขาย
 *     → ledger = paid (ทำครั้งเดียว: lock ออเดอร์ + ledger และเช็ครายการ wallet ที่อ้างอิง ledger เดิม)
 *  4. ผู้ขายถอนเงินผ่าน WithdrawalService ปกติ (KYC + PIN + แอดมินอนุมัติ)
 *
 * ค่าส่งที่พักไว้กับผู้ขาย: ถ้าออเดอร์ส่งโดยไรเดอร์แพลตฟอร์ม (RiderJob เสร็จแล้ว) ค่าส่งเป็นของไรเดอร์
 * (RiderEarningService จ่ายไรเดอร์แล้ว) → ไม่โอนส่วนค่าส่งให้ผู้ขาย
 */
class SellerPayoutService
{
    public const ESCROW_WALLET_SLUG = 'seller_escrow';

    public const ESCROW_HOLD_SUB_TYPE = 'seller_sale_hold';

    public const ESCROW_RELEASE_SUB_TYPE = 'seller_payout';

    public const ESCROW_REVERSAL_SUB_TYPE = 'seller_sale_refund';

    /** reference_type ของรายการเงินเข้า wallet ผู้ขาย (กันจ่ายซ้ำ) */
    public const WALLET_REFERENCE_TYPE = 'EarningsLedger';

    public const SETTING_SHIPPED_AUTO_RELEASE_DAYS = 'money.shipped_auto_release_days';

    public const DEFAULT_SHIPPED_AUTO_RELEASE_DAYS = 14;

    /** สถานะออเดอร์ที่ถือว่าลูกค้าได้รับของแล้ว */
    public const DELIVERED_STATUSES = ['delivered', 'completed'];

    protected WalletService $wallets;

    protected DebtCollectionService $debts;

    public function __construct(?WalletService $wallets = null, ?DebtCollectionService $debts = null)
    {
        $this->wallets = $wallets ?? app(WalletService::class);
        $this->debts = $debts ?? new DebtCollectionService;
    }

    /**
     * กระเป๋าพักเงินผู้ขาย (สร้างอัตโนมัติครั้งแรก)
     */
    public static function escrowWallet(): PlatformWallet
    {
        return PlatformWallet::firstOrCreate(
            ['slug' => self::ESCROW_WALLET_SLUG],
            [
                'name' => 'เงินพักรอจ่ายผู้ขาย',
                'type' => PlatformWallet::TYPE_RESERVE,
                'description' => 'เงินสุทธิของผู้ขายที่ลูกค้าจ่ายแล้ว พักไว้จนกว่าจะส่งของถึงและครบระยะพักเงิน',
            ]
        );
    }

    /**
     * พักเงินสุทธิของผู้ขายไว้ใน escrow ตอนแบ่งเงินออเดอร์
     */
    public function holdInEscrow(float $amount, Order $order, int $sellerId, array $metadata = []): ?PlatformTransaction
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $tx = self::escrowWallet()->addFunds(
            $amount,
            self::ESCROW_HOLD_SUB_TYPE,
            'Order',
            (int) $order->id,
            array_merge(['order_number' => $order->order_number, 'seller_id' => $sellerId], $metadata)
        );

        $tx->update([
            'related_user_id' => $sellerId,
            'description' => "พักเงินผู้ขายจากออเดอร์ #{$order->order_number}",
        ]);

        return $tx;
    }

    /**
     * จำนวนวันพักเงินหลังส่งของถึง (payout_settings ของผู้ขาย)
     */
    public function holdingDays(): int
    {
        try {
            return max(0, (int) PayoutSetting::getSellerSetting()->holding_days);
        } catch (\Throwable $e) {
            Log::warning('SellerPayout: read holding_days failed, using 7', ['error' => $e->getMessage()]);

            return 7;
        }
    }

    /**
     * ส่งพัสดุแล้วกี่วันจึงถือว่าลูกค้าได้รับของ (กรณีลูกค้าไม่กดยืนยัน)
     */
    public function shippedAutoReleaseDays(): int
    {
        $value = Setting::get(self::SETTING_SHIPPED_AUTO_RELEASE_DAYS, self::DEFAULT_SHIPPED_AUTO_RELEASE_DAYS);

        return is_numeric($value) ? max(1, (int) $value) : self::DEFAULT_SHIPPED_AUTO_RELEASE_DAYS;
    }

    /**
     * เวลาที่รายได้ของออเดอร์นี้จะพร้อมจ่าย (null = ยังไม่ส่งถึง ยังนับเวลาไม่ได้)
     */
    public function availableAtFor(Order $order): ?Carbon
    {
        $holding = $this->holdingDays();

        if (in_array($order->status, self::DELIVERED_STATUSES, true)) {
            $base = $order->delivered_at ? Carbon::parse($order->delivered_at) : now();

            return $base->copy()->addDays($holding);
        }

        if ($order->status === 'shipped' && $order->shipped_at) {
            return Carbon::parse($order->shipped_at)->addDays($this->shippedAutoReleaseDays() + $holding);
        }

        return null;
    }

    /**
     * เริ่มนับเวลาพักเงินเมื่อออเดอร์ส่งถึงแล้ว (เรียกจาก OrderObserver)
     *
     * @return int จำนวน ledger ที่ตั้งเวลา
     */
    public function startHoldingClock(Order $order): int
    {
        $availableAt = $this->availableAtFor($order);
        if ($availableAt === null) {
            return 0;
        }

        return EarningsLedger::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('earning_type', EarningsLedger::TYPE_SELLER_SALE)
            ->where('status', EarningsLedger::STATUS_PENDING)
            ->update(['available_at' => $availableAt]);
    }

    /**
     * ledger ที่ถึงเวลาจ่ายแล้ว (ตัดสินจากสถานะออเดอร์จริง ไม่ใช่เวลาอย่างเดียว)
     */
    public function eligibleQuery(?Carbon $now = null): Builder
    {
        $now = $now ?? now();
        $holding = $this->holdingDays();
        $deliveredBefore = $now->copy()->subDays($holding);
        $shippedBefore = $now->copy()->subDays($holding + $this->shippedAutoReleaseDays());

        $orderMorph = (new Order)->getMorphClass();

        return EarningsLedger::query()
            ->where('earning_type', EarningsLedger::TYPE_SELLER_SALE)
            ->where('source_type', 'Order')
            ->whereIn('status', [EarningsLedger::STATUS_PENDING, EarningsLedger::STATUS_AVAILABLE])
            ->whereExists(function ($q) use ($deliveredBefore, $shippedBefore, $orderMorph) {
                $q->selectRaw('1')
                    ->from('orders')
                    ->whereColumn('orders.id', 'earnings_ledger.source_id')
                    ->whereNull('orders.deleted_at')
                    ->where('orders.payment_status', 'paid')
                    // COD ส่งด้วยไรเดอร์: ไรเดอร์ต้องนำส่งเงินเข้าระบบแล้ว (cod_settled_at) จึงปล่อยเงินร้าน
                    ->whereNotExists(function ($r) use ($orderMorph) {
                        $r->selectRaw('1')
                            ->from('rider_jobs')
                            ->where('rider_jobs.source_type', $orderMorph)
                            ->whereColumn('rider_jobs.source_id', 'orders.id')
                            ->where('rider_jobs.cod_amount', '>', 0)
                            ->whereIn('rider_jobs.status', ['delivered', 'completed'])
                            ->whereNull('rider_jobs.cod_settled_at')
                            ->whereNull('rider_jobs.deleted_at');
                    })
                    ->where(function ($w) use ($deliveredBefore, $shippedBefore) {
                        // 1) ส่งถึงแล้ว + ครบระยะพักเงิน
                        $w->where(function ($d) use ($deliveredBefore) {
                            $d->whereIn('orders.status', self::DELIVERED_STATUSES)
                                ->whereRaw('COALESCE(orders.delivered_at, orders.updated_at) <= ?', [$deliveredBefore]);
                        })
                        // 2) ส่งพัสดุแล้วเกินกำหนด ลูกค้าไม่กดยืนยัน
                            ->orWhere(function ($s) use ($shippedBefore) {
                                $s->where('orders.status', 'shipped')
                                    ->whereNotNull('orders.shipped_at')
                                    ->where('orders.shipped_at', '<=', $shippedBefore);
                            })
                        // 3) สินค้าดิจิทัลล้วน (ไม่มีของต้องส่ง) จ่ายแล้วครบระยะพักเงิน
                            ->orWhere(function ($v) use ($deliveredBefore) {
                                $v->whereNotIn('orders.status', ['pending', 'cancelled', 'refunded'])
                                    ->whereNotNull('orders.paid_at')
                                    ->where('orders.paid_at', '<=', $deliveredBefore)
                                    ->whereExists(function ($i) {
                                        $i->selectRaw('1')->from('order_items')->whereColumn('order_items.order_id', 'orders.id');
                                    })
                                    ->whereNotExists(function ($p) {
                                        $p->selectRaw('1')
                                            ->from('order_items')
                                            ->join('products', 'products.id', '=', 'order_items.product_id')
                                            ->whereColumn('order_items.order_id', 'orders.id')
                                            ->where('products.is_virtual', false);
                                    });
                            });
                    });
            })
            ->orderBy('id');
    }

    /**
     * จ่ายรายได้ทุกรายการที่ถึงเวลา (ใช้โดย earnings:release-pending)
     *
     * @return array{checked: int, credited: int, skipped: int, failed: int, total_credited: float, errors: array<int, array{id: int, reason: string}>}
     */
    public function releaseEligible(int $limit = 100, ?Carbon $now = null): array
    {
        $ids = $this->eligibleQuery($now)->limit(max(1, $limit))->pluck('id');

        $result = ['checked' => $ids->count(), 'credited' => 0, 'skipped' => 0, 'failed' => 0, 'total_credited' => 0.0, 'errors' => []];

        foreach ($ids as $id) {
            $outcome = $this->releaseLedger((int) $id, $now);

            if ($outcome['status'] === 'credited') {
                $result['credited']++;
                $result['total_credited'] = round($result['total_credited'] + $outcome['credited'], 2);
            } elseif ($outcome['status'] === 'failed') {
                $result['failed']++;
                $result['errors'][] = ['id' => (int) $id, 'reason' => $outcome['reason']];
            } else {
                $result['skipped']++;
            }
        }

        return $result;
    }

    /**
     * จ่ายรายได้ 1 ledger เข้า wallet ผู้ขาย — เรียกซ้ำได้ ไม่จ่ายซ้ำ
     *
     * @return array{status: string, reason: string, credited: float, debt_collected: float, rider_shipping: float, wallet_transaction_id: ?int}
     */
    public function releaseLedger(int $ledgerId, ?Carbon $now = null): array
    {
        $now = $now ?? now();
        $notify = null;

        try {
            $outcome = DB::transaction(function () use ($ledgerId, $now, &$notify) {
                $peek = EarningsLedger::find($ledgerId);
                if (! $peek) {
                    return $this->outcome('skipped', 'not_found');
                }
                if ($peek->earning_type !== EarningsLedger::TYPE_SELLER_SALE || $peek->source_type !== 'Order') {
                    return $this->outcome('skipped', 'not_seller_sale');
                }

                // ลำดับ lock เดียวกับการแบ่งเงิน/คืนเงิน: ออเดอร์ก่อน แล้วค่อย ledger (กัน deadlock)
                $order = Order::whereKey($peek->source_id)->lockForUpdate()->first();
                $ledger = EarningsLedger::whereKey($ledgerId)->lockForUpdate()->first();

                if (! $ledger) {
                    return $this->outcome('skipped', 'not_found');
                }
                if ($ledger->status === EarningsLedger::STATUS_PAID) {
                    return $this->outcome('skipped', 'already_paid', 0.0, 0.0, 0.0, $ledger->wallet_transaction_id);
                }
                if (! in_array($ledger->status, [EarningsLedger::STATUS_PENDING, EarningsLedger::STATUS_AVAILABLE], true)) {
                    return $this->outcome('skipped', 'status_'.$ledger->status);
                }
                if (! $order || ! $this->isOrderReleasable($order, $now)) {
                    return $this->outcome('skipped', 'order_not_ready');
                }

                // เคยโอนแล้ว (เช่น process ล้มหลังโอน) → ปิด ledger ให้ตรงกับรายการเดิม ไม่โอนซ้ำ
                $existingTx = WalletTransaction::where('reference_type', self::WALLET_REFERENCE_TYPE)
                    ->where('reference_id', $ledger->id)
                    ->where('type', 'deposit')
                    ->first();
                if ($existingTx) {
                    $ledger->update([
                        'status' => EarningsLedger::STATUS_PAID,
                        'paid_at' => $ledger->paid_at ?? $now,
                        'wallet_transaction_id' => $existingTx->id,
                    ]);

                    return $this->outcome('skipped', 'already_credited', 0.0, 0.0, 0.0, $existingTx->id);
                }

                $seller = User::withTrashed()->find($ledger->user_id);
                if (! $seller) {
                    return $this->outcome('failed', 'seller_not_found');
                }

                // ร้านถูกแอดมินระงับ → พักการจ่ายไว้จนกว่าจะปลดระงับ (ไม่ยกเลิก ledger)
                if ($this->sellerStoreSuspended((int) $seller->id)) {
                    Log::info('SellerPayout: store suspended, payout on hold', ['ledger_id' => $ledger->id, 'user_id' => $seller->id]);

                    return $this->outcome('skipped', 'store_suspended');
                }

                $wallet = $this->wallets->getOrCreateWallet($seller);
                if (! $wallet->isActive()) {
                    Log::warning('SellerPayout: seller wallet inactive, payout postponed', [
                        'ledger_id' => $ledger->id,
                        'user_id' => $seller->id,
                    ]);

                    return $this->outcome('skipped', 'wallet_inactive');
                }

                $original = round((float) $ledger->net_amount, 2);
                $riderShipping = min($original, $this->riderShippingShare($ledger, $order));
                $payable = round(max(0.0, $original - $riderShipping), 2);

                // หักหนี้ค้าง (ไม่เกิน 50% ของรายได้ครั้งนี้ ตามนโยบาย DebtCollectionService)
                $debtCollected = 0.0;
                if ($payable > 0) {
                    $debt = $this->debts->collectDebtFromEarning((int) $ledger->user_id, $payable, (int) $ledger->id);
                    $debtCollected = round(min($payable, (float) ($debt['collected'] ?? 0)), 2);
                }
                $credit = round($payable - $debtCollected, 2);

                // ปล่อยเงินพักทั้งก้อนออกจาก escrow (ก้อนนี้ถูกพักไว้ตอนแบ่งเงิน)
                if ($original > 0) {
                    $release = self::escrowWallet()->deductFunds(
                        $original,
                        self::ESCROW_RELEASE_SUB_TYPE,
                        self::WALLET_REFERENCE_TYPE,
                        (int) $ledger->id,
                        [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'seller_id' => $seller->id,
                            'credited_to_seller' => $credit,
                            'debt_collected' => $debtCollected,
                            'rider_shipping' => $riderShipping,
                        ]
                    );
                    $release->update([
                        'related_user_id' => $seller->id,
                        'description' => "จ่ายรายได้ผู้ขาย ออเดอร์ #{$order->order_number}",
                    ]);
                }

                // เงินที่หักหนี้ → กองทุนคืนเงิน (หนี้เกิดจากการคืนเงินลูกค้าที่แพลตฟอร์มออกไปก่อน)
                if ($debtCollected > 0) {
                    $debtTx = PlatformWallet::getRefundPoolWallet()->addFunds(
                        $debtCollected,
                        'debt_collected_from_earnings',
                        self::WALLET_REFERENCE_TYPE,
                        (int) $ledger->id,
                        ['order_id' => $order->id, 'seller_id' => $seller->id]
                    );
                    $debtTx->update([
                        'related_user_id' => $seller->id,
                        'description' => "หักหนี้จากรายได้ผู้ขาย ออเดอร์ #{$order->order_number}",
                    ]);
                }

                $walletTx = null;
                if ($credit > 0) {
                    $walletTx = $this->wallets->deposit(
                        $wallet,
                        $credit,
                        "รายได้จากการขาย ออเดอร์ #{$order->order_number}",
                        self::WALLET_REFERENCE_TYPE,
                        (int) $ledger->id,
                        [
                            'type' => 'seller_sale_payout',
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'ledger_id' => $ledger->id,
                        ]
                    );
                }

                $breakdown = is_array($ledger->breakdown) ? $ledger->breakdown : [];
                $breakdown['payout'] = [
                    'released_at' => $now->toIso8601String(),
                    'ledger_net' => $original,
                    'rider_shipping_withheld' => $riderShipping,
                    'debt_collected' => $debtCollected,
                    'credited' => $credit,
                    'wallet_transaction_id' => $walletTx?->id,
                ];

                $ledger->update([
                    'status' => EarningsLedger::STATUS_PAID,
                    'paid_at' => $now,
                    'available_at' => $ledger->available_at ?? $now,
                    'wallet_transaction_id' => $walletTx?->id,
                    'debt_deduction' => round((float) $ledger->debt_deduction + $debtCollected, 4),
                    'other_deductions' => round((float) $ledger->other_deductions + $riderShipping, 4),
                    'net_amount' => $credit,
                    'breakdown' => $breakdown,
                ]);

                if ($credit > 0) {
                    $notify = [$seller, $credit, $order->order_number, $ledger->id];
                }

                return $this->outcome('credited', 'ok', $credit, $debtCollected, $riderShipping, $walletTx?->id);
            });
        } catch (\Throwable $e) {
            Log::error('SellerPayout: release ledger failed', [
                'ledger_id' => $ledgerId,
                'error' => $e->getMessage(),
            ]);

            return $this->outcome('failed', 'exception');
        }

        if ($notify !== null) {
            $this->notifySeller(...$notify);
        }

        return $outcome;
    }

    /**
     * ยกเลิก ledger ที่ยังไม่จ่าย (ตอนคืนเงินออเดอร์) + ดึงเงินพักออกจาก escrow — ครั้งเดียว
     *
     * ผู้เรียกต้อง lock แถวออเดอร์ไว้แล้ว
     *
     * @return array{action: string, escrow_reversed: float, shortfall: float}
     */
    public function reverseUnpaidLedger(EarningsLedger $ledger, Order $order, string $reason): array
    {
        $locked = EarningsLedger::whereKey($ledger->id)->lockForUpdate()->first();
        if (! $locked || ! in_array($locked->status, [EarningsLedger::STATUS_PENDING, EarningsLedger::STATUS_AVAILABLE, EarningsLedger::STATUS_HELD], true)) {
            return ['action' => 'none', 'escrow_reversed' => 0.0, 'shortfall' => 0.0];
        }

        $amount = round((float) $locked->net_amount, 2);
        $reversed = 0.0;
        $shortfall = 0.0;

        if ($amount > 0) {
            $escrow = self::escrowWallet();
            $already = PlatformTransaction::where('platform_wallet_id', $escrow->id)
                ->where('sub_type', self::ESCROW_REVERSAL_SUB_TYPE)
                ->where('source_type', self::WALLET_REFERENCE_TYPE)
                ->where('source_id', $locked->id)
                ->exists();

            if (! $already) {
                try {
                    $tx = $escrow->deductFunds($amount, self::ESCROW_REVERSAL_SUB_TYPE, self::WALLET_REFERENCE_TYPE, (int) $locked->id, [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'reason' => $reason,
                    ]);
                    $tx->update([
                        'related_user_id' => $locked->user_id,
                        'description' => "คืนเงินพักผู้ขาย (ยกเลิก/คืนเงิน) ออเดอร์ #{$order->order_number}",
                    ]);
                    $reversed = $amount;
                } catch (\Throwable $e) {
                    // escrow ไม่มีเงินก้อนนี้ (ledger เก่าก่อนมีระบบพักเงิน) → แพลตฟอร์มออกส่วนต่างเอง
                    $shortfall = $amount;
                    Log::warning('SellerPayout: escrow reversal shortfall', [
                        'ledger_id' => $locked->id,
                        'amount' => $amount,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $locked->update([
            'status' => EarningsLedger::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => mb_substr("คืนเงินออเดอร์ #{$order->order_number}: {$reason}", 0, 255),
        ]);

        return ['action' => 'cancelled', 'escrow_reversed' => $reversed, 'shortfall' => $shortfall];
    }

    /**
     * สรุปรายได้ผู้ขาย (หน้า wallet ร้านค้า / API)
     *
     * @return array{pending_amount: float, pending_count: int, waiting_delivery_count: int, next_release_at: ?string, paid_amount: float, paid_count: int, holding_days: int}
     */
    public function summaryForSeller(int $userId): array
    {
        $base = EarningsLedger::where('user_id', $userId)->where('earning_type', EarningsLedger::TYPE_SELLER_SALE);

        $pending = (clone $base)->whereIn('status', [EarningsLedger::STATUS_PENDING, EarningsLedger::STATUS_AVAILABLE]);
        $nextRelease = (clone $pending)->whereNotNull('available_at')->min('available_at');

        return [
            'pending_amount' => round((float) (clone $pending)->sum('net_amount'), 2),
            'pending_count' => (int) (clone $pending)->count(),
            'waiting_delivery_count' => (int) (clone $pending)->whereNull('available_at')->count(),
            'next_release_at' => $nextRelease ? Carbon::parse($nextRelease)->toIso8601String() : null,
            'paid_amount' => round((float) (clone $base)->where('status', EarningsLedger::STATUS_PAID)->sum('net_amount'), 2),
            'paid_count' => (int) (clone $base)->where('status', EarningsLedger::STATUS_PAID)->count(),
            'holding_days' => $this->holdingDays(),
        ];
    }

    /**
     * ออเดอร์นี้ปล่อยเงินผู้ขายได้หรือยัง (เงื่อนไขเดียวกับ eligibleQuery)
     */
    public function isOrderReleasable(Order $order, ?Carbon $now = null): bool
    {
        $now = $now ?? now();

        if ($order->payment_status !== 'paid' || $order->trashed()) {
            return false;
        }

        // COD ส่งด้วยไรเดอร์: เงินสดยังอยู่กับไรเดอร์ (ยังหักวอลเลตไม่ได้) → ยังไม่ปล่อยเงินร้าน
        if ($this->hasUnsettledRiderCod($order)) {
            return false;
        }

        $holding = $this->holdingDays();

        if (in_array($order->status, self::DELIVERED_STATUSES, true)) {
            $base = $order->delivered_at ?? $order->updated_at;

            return $base !== null && Carbon::parse($base)->addDays($holding)->lte($now);
        }

        if ($order->status === 'shipped') {
            return $order->shipped_at !== null
                && Carbon::parse($order->shipped_at)->addDays($holding + $this->shippedAutoReleaseDays())->lte($now);
        }

        if (in_array($order->status, ['pending', 'cancelled', 'refunded'], true) || $order->paid_at === null) {
            return false;
        }

        // สินค้าดิจิทัลล้วน
        $items = $order->items()->with(['product' => fn ($q) => $q->withTrashed()])->get();
        if ($items->isEmpty() || $items->contains(fn ($item) => ! $item->product || ! $item->product->is_virtual)) {
            return false;
        }

        return Carbon::parse($order->paid_at)->addDays($holding)->lte($now);
    }

    /**
     * ออเดอร์นี้มีงานไรเดอร์ที่เก็บเงินสด (COD) แล้วแต่ยังนำส่งเข้าระบบไม่ได้หรือไม่
     */
    protected function hasUnsettledRiderCod(Order $order): bool
    {
        try {
            return RiderJob::forSource($order)
                ->where('cod_amount', '>', 0)
                ->whereIn('status', ['delivered', 'completed'])
                ->whereNull('cod_settled_at')
                ->exists();
        } catch (\Throwable $e) {
            // ตรวจไม่ได้ = ไม่ปล่อยเงิน (ปลอดภัยไว้ก่อน) รอบถัดไปลองใหม่
            Log::warning('SellerPayout: rider COD lookup failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return true;
        }
    }

    /**
     * ร้านของผู้ขายถูกแอดมินระงับอยู่หรือไม่ (ระงับ = พักการจ่ายรายได้ไว้ก่อน)
     */
    protected function sellerStoreSuspended(int $sellerId): bool
    {
        return VendorStore::where('user_id', $sellerId)->where('status', 'suspended')->exists();
    }

    /**
     * ส่วนค่าส่งใน ledger ที่ต้องเก็บไว้ให้ไรเดอร์ (ออเดอร์ที่ไรเดอร์แพลตฟอร์มส่งสำเร็จ)
     */
    protected function riderShippingShare(EarningsLedger $ledger, Order $order): float
    {
        $share = round((float) data_get($ledger->breakdown, 'shipping_share', 0), 2);
        if ($share <= 0) {
            return 0.0;
        }

        try {
            $riderDelivered = RiderJob::query()
                ->where('source_type', $order->getMorphClass())
                ->where('source_id', $order->id)
                ->whereIn('status', ['delivered', 'completed'])
                ->exists();
        } catch (\Throwable $e) {
            Log::warning('SellerPayout: rider job lookup failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            $riderDelivered = false;
        }

        return $riderDelivered ? $share : 0.0;
    }

    /**
     * แจ้งผู้ขายว่ารายได้เข้ากระเป๋าแล้ว (in-app — ไม่ใช้ LINE push)
     */
    protected function notifySeller(User $seller, float $amount, ?string $orderNumber, int $ledgerId): void
    {
        try {
            app(NotificationService::class)->create(
                $seller,
                'seller_earning_paid',
                'รายได้จากการขายเข้ากระเป๋าแล้ว',
                'รายได้จากออเดอร์ #'.($orderNumber ?? '-').' จำนวน '.number_format($amount, 2).' บาท เข้ากระเป๋าเงินของคุณแล้ว ถอนเงินได้ที่หน้ากระเป๋าเงินร้านค้า',
                ['ledger_id' => $ledgerId, 'amount' => $amount, 'order_number' => $orderNumber],
                '/seller/wallet'
            );
        } catch (\Throwable $e) {
            Log::warning('SellerPayout: notify seller failed', ['ledger_id' => $ledgerId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @return array{status: string, reason: string, credited: float, debt_collected: float, rider_shipping: float, wallet_transaction_id: ?int}
     */
    private function outcome(string $status, string $reason, float $credited = 0.0, float $debt = 0.0, float $rider = 0.0, ?int $txId = null): array
    {
        return [
            'status' => $status,
            'reason' => $reason,
            'credited' => round($credited, 2),
            'debt_collected' => round($debt, 2),
            'rider_shipping' => round($rider, 2),
            'wallet_transaction_id' => $txId,
        ];
    }
}
