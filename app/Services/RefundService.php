<?php

namespace App\Services;

use App\Models\EarningsLedger;
use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\PlatformWallet;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RefundService — คืนเงินเต็มจำนวนของออเดอร์ e-commerce
 *
 * ย้อนทุกเส้นทางเงินของออเดอร์ในครั้งเดียว (transaction เดียว, lock แถวออเดอร์, ทำซ้ำไม่คืนเงินซ้ำ):
 *  0. งานไรเดอร์: ยกเลิก/ปิดงานที่ยังวิ่ง · ส่งสำเร็จแล้ว = หักค่าส่ง (จ่ายไรเดอร์ไปแล้ว) ออกจากยอดคืน
 *  1. คืนเงินลูกค้าเข้า wallet (type = refund) — สร้าง wallet ให้ถ้ายังไม่มี
 *  2. เรียกเงินคืน (cashback) กลับจาก wallet ลูกค้า ไม่พอ = สร้างหนี้ และคืนรายจ่ายโปรให้แพลตฟอร์ม
 *  3. รายได้ผู้ขาย: ยังไม่จ่าย = ยกเลิก + ดึงเงินพักออกจาก seller_escrow / จ่ายแล้ว = หักคืนจาก wallet ผู้ขาย ไม่พอ = หนี้
 *  4. คอม MLM: ยังไม่จ่าย = ยกเลิก / จ่ายแล้ว = หักคืน (เงินที่หักคืนกลับเข้ากองทุน MLM)
 *  5. ย้อนรายการเงินเข้ากระเป๋าแพลตฟอร์มของออเดอร์ (GP, VAT, กองทุน MLM, ร้านทางการ, ส่วนลดคูปอง)
 *  6. ออเดอร์ → status = refunded, payment_status = refunded
 *
 * ถ้าขั้นใดล้ม ทั้งหมด rollback — ออเดอร์จะไม่ถูกเปลี่ยนเป็นคืนเงินโดยที่ลูกค้าไม่ได้เงิน (audit G4)
 * กระเป๋าแพลตฟอร์มที่เงินไม่พอให้ย้อน → บันทึกเป็น shortfall ในรายงาน (แพลตฟอร์มรับภาระ) ไม่ทำให้การคืนเงินล้ม
 */
class RefundService
{
    protected MlmCommissionClawbackService $mlmClawbackService;

    protected WalletService $wallets;

    protected SellerPayoutService $payouts;

    protected PlatformExpenseService $expenses;

    public function __construct(
        ?MlmCommissionClawbackService $mlmClawbackService = null,
        ?WalletService $wallets = null,
        ?SellerPayoutService $payouts = null,
        ?PlatformExpenseService $expenses = null
    ) {
        $this->mlmClawbackService = $mlmClawbackService ?? new MlmCommissionClawbackService;
        $this->wallets = $wallets ?? app(WalletService::class);
        $this->payouts = $payouts ?? new SellerPayoutService($this->wallets);
        $this->expenses = $expenses ?? new PlatformExpenseService;
    }

    /**
     * คืนเงินเต็มจำนวนของออเดอร์
     *
     * @throws \DomainException ข้อความภาษาไทยที่แสดงให้แอดมินเห็นได้ (ยังไม่จ่ายเงิน / ไม่พบลูกค้า / กระเป๋าถูกระงับ)
     */
    public function processFullRefund(Order $order, ?int $adminId = null, string $reason = ''): array
    {
        $reason = trim($reason) !== '' ? trim($reason) : 'คืนเงินคำสั่งซื้อ';
        $notifyCustomer = null;

        $report = DB::transaction(function () use ($order, $adminId, $reason, &$notifyCustomer) {
            /** @var Order|null $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! $locked) {
                throw new \DomainException('ไม่พบคำสั่งซื้อนี้');
            }

            // คืนเงินไปแล้ว → คืนรายงานเดิม ไม่คืนซ้ำ
            if ($locked->payment_status === 'refunded') {
                return $this->emptyReport($locked, $reason, $adminId) + ['already_refunded' => true];
            }

            if ($locked->payment_status !== 'paid') {
                throw new \DomainException('คำสั่งซื้อนี้ยังไม่ได้ชำระเงิน จึงไม่มีเงินให้คืน');
            }

            $report = $this->emptyReport($locked, $reason, $adminId);

            // 0. งานไรเดอร์ของออเดอร์ (ภายใต้ lock ออเดอร์เดียวกัน — กันไรเดอร์ส่งของต่อหลังคืนเงิน)
            //    ยังไม่รับของ → ยกเลิก · รับของแล้ว → ส่งไม่สำเร็จ + ให้นำของคืนร้าน (ไม่ได้ค่าส่ง)
            //    ส่งสำเร็จไปแล้ว → ค่าส่งถูกจ่ายให้ไรเดอร์/แพลตฟอร์มแล้ว ไม่คืนส่วนนั้น
            $report['rider_fee_withheld'] = $this->settleRiderJobsBeforeRefund($locked, $reason);

            // 1. คืนเงินลูกค้า
            $report['customer_refund'] = $this->refundToCustomer($locked, $reason, (float) $report['rider_fee_withheld']);
            $report['summary']['total_customer_refund'] = $report['customer_refund']['amount'];

            // 2. เรียกเงินคืน (cashback) กลับ
            $report['cashback_clawback'] = $this->clawbackCashback($locked, $adminId, $reason);
            $report['summary']['total_cashback_clawback'] = $report['cashback_clawback']['clawback_amount'];

            // 3. รายได้ผู้ขาย
            $report['seller_clawback'] = $this->clawbackFromSellers($locked, $adminId, $reason);
            $report['summary']['total_seller_clawback'] = round(collect($report['seller_clawback'])->sum('clawback_amount'), 2);

            // 4. คอม MLM (ก่อนย้อนกองทุน — เงินที่หักคืนจะเติมกองทุนกลับก่อน)
            $report['mlm_clawback'] = $this->mlmClawbackService->clawbackOrderCommissions($locked, $adminId);
            $report['summary']['total_mlm_clawback'] = (float) ($report['mlm_clawback']['total_clawback_amount'] ?? 0);
            $this->returnMlmClawbackToPool($locked, $report['mlm_clawback']);

            // 5. ย้อนรายการเงินเข้ากระเป๋าแพลตฟอร์ม + คืนรายจ่ายส่วนลดคูปอง
            //    คืนรายจ่ายเท่าที่เคยลงไว้จริงเท่านั้น (คูปองของร้านไม่เคยเป็นรายจ่ายแพลตฟอร์ม → ไม่มีอะไรให้คืน)
            $report['platform_adjustments'] = $this->adjustPlatformWallets($locked, $reason);
            $discountExpense = $this->expenses->findExpense('fee', 'order_discount', 'Order', (int) $locked->id);
            if ($discountExpense && (float) $discountExpense->amount > 0) {
                $this->expenses->reverseExpense('fee', (float) $discountExpense->amount, 'order_discount', 'Order', (int) $locked->id,
                    "คืนรายจ่ายส่วนลดคูปอง (ออเดอร์ถูกคืนเงิน) #{$locked->order_number}");
            }

            // 6. รวมหนี้ที่เกิดขึ้น
            $debts = [];
            if (! empty($report['cashback_clawback']['debt_id'])) {
                $debts[] = $report['cashback_clawback']['debt_id'];
            }
            foreach ($report['seller_clawback'] as $row) {
                if (! empty($row['debt_id'])) {
                    $debts[] = $row['debt_id'];
                }
            }
            $debts = array_merge($debts, $report['mlm_clawback']['debts_created'] ?? []);
            $report['debts_created'] = array_values(array_unique($debts));
            $report['summary']['total_debts_created'] = count($report['debts_created']);

            // 7. สถานะออเดอร์
            $locked->forceFill([
                'status' => 'refunded',
                'payment_status' => 'refunded',
                'refund_reason' => $reason,
                'refunded_at' => now(),
                'refunded_by' => $adminId,
            ])->save();
            $order->setRawAttributes($locked->getAttributes(), true);

            Log::info('Full refund processed', [
                'order_id' => $locked->id,
                'admin_id' => $adminId,
                'summary' => $report['summary'],
            ]);

            if (($report['customer_refund']['amount'] ?? 0) > 0) {
                $notifyCustomer = [(int) $locked->user_id, (float) $report['customer_refund']['amount'], (string) $locked->order_number];
            }

            return $report;
        });

        if ($notifyCustomer !== null) {
            $this->notifyCustomerRefunded(...$notifyCustomer);
        }

        return $report;
    }

    /**
     * จัดการงานไรเดอร์ของออเดอร์ก่อนคืนเงิน (เรียกภายใต้ lock ออเดอร์)
     *
     * - งานที่ยังวิ่งอยู่: ยกเลิก (ยังไม่รับของ) / ส่งไม่สำเร็จ + นำของคืนร้าน (รับของแล้ว) — ไม่เรียก hook กลับออเดอร์
     * - งานที่ส่งสำเร็จแล้ว: ค่าส่ง (rider_earnings + platform_fee = total_fee) ถูกจ่ายออกไปแล้ว → คืนผู้ซื้อไม่ได้
     *
     * @return float ค่าส่งที่หักออกจากยอดคืน (0 = ไม่มีงานที่ส่งสำเร็จ)
     */
    protected function settleRiderJobsBeforeRefund(Order $order, string $reason): float
    {
        try {
            app(RiderDispatchService::class)->cancelJobsForSource($order, 'admin', mb_substr('คืนเงินคำสั่งซื้อ: '.$reason, 0, 500));
        } catch (\Throwable $e) {
            Log::warning('Refund: cancel rider jobs failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        $completedFee = (float) RiderJob::forSource($order)
            ->where('status', 'completed')
            ->latest('id')
            ->value('total_fee');

        return round(min(max(0.0, $completedFee), max(0.0, (float) $order->total_amount)), 2);
    }

    /**
     * คืนเงินลูกค้าเข้า wallet (ครั้งเดียวต่อออเดอร์)
     *
     * @param  float  $withheld  ส่วนที่คืนไม่ได้ (ค่าส่งที่จ่ายไรเดอร์ไปแล้ว)
     */
    protected function refundToCustomer(Order $order, string $reason, float $withheld = 0.0): array
    {
        $amount = round(max(0.0, (float) $order->total_amount - max(0.0, $withheld)), 2);
        $result = [
            'customer_id' => (int) $order->user_id,
            'amount' => $amount,
            'withheld_rider_fee' => round(max(0.0, $withheld), 2),
            'method' => 'wallet',
            'status' => 'completed',
            'wallet_transaction_id' => null,
        ];

        if ($amount <= 0) {
            $result['status'] = 'nothing_to_refund';

            return $result;
        }

        $customer = User::withTrashed()->find($order->user_id);
        if (! $customer) {
            throw new \DomainException('ไม่พบบัญชีลูกค้าของคำสั่งซื้อนี้ จึงคืนเงินเข้ากระเป๋าไม่ได้');
        }

        $existing = WalletTransaction::where('reference_type', 'Order')
            ->where('reference_id', $order->id)
            ->where('user_id', $customer->id)
            ->where('type', 'refund')
            ->first();
        if ($existing) {
            $result['status'] = 'already_refunded';
            $result['wallet_transaction_id'] = $existing->id;

            return $result;
        }

        $wallet = $this->wallets->getOrCreateWallet($customer);
        if (! $wallet->isActive()) {
            throw new \DomainException('กระเป๋าเงินของลูกค้าถูกระงับอยู่ กรุณาปลดระงับก่อนคืนเงิน');
        }

        $tx = $this->wallets->deposit(
            $wallet,
            $amount,
            mb_substr("คืนเงินคำสั่งซื้อ #{$order->order_number}: {$reason}", 0, 500),
            'Order',
            (int) $order->id,
            ['order_number' => $order->order_number, 'reason' => $reason],
            'refund'
        );

        $result['wallet_transaction_id'] = $tx->id;

        return $result;
    }

    /**
     * เรียกเงินคืน (cashback) ที่จ่ายไปแล้วกลับจาก wallet ลูกค้า — ไม่พอ = สร้างหนี้
     */
    protected function clawbackCashback(Order $order, ?int $adminId, string $reason): array
    {
        $cashback = round((float) ($order->cashback_amount ?? 0), 2);
        $result = [
            'customer_id' => (int) $order->user_id,
            'cashback_amount' => $cashback,
            'clawback_amount' => 0.0,
            'deducted_from_wallet' => 0.0,
            'debt_id' => null,
            'status' => 'skipped',
        ];

        if (! $order->cashback_processed || $cashback <= 0) {
            return $result;
        }

        $result['clawback_amount'] = $cashback;
        $clawed = $this->deductOrDebt(
            (int) $order->user_id,
            $cashback,
            'OrderCashbackClawback',
            (int) $order->id,
            "เรียกคืนเงินคืน (Cash Back) — คืนเงินออเดอร์ #{$order->order_number}",
            'CashbackClawback',
            (int) $order->id,
            "เรียกคืน Cash Back จากออเดอร์ #{$order->order_number}: {$reason}",
            $adminId,
            2,
            ['order_number' => $order->order_number, 'original_cashback' => $cashback]
        );

        $result['deducted_from_wallet'] = $clawed['deducted'];
        $result['debt_id'] = $clawed['debt_id'];
        $result['status'] = $clawed['debt_id'] ? ($clawed['deducted'] > 0 ? 'partial_debt' : 'full_debt') : 'deducted';

        // เงินที่เรียกคืนได้จริง → คืนรายจ่ายโปรให้กระเป๋า fee
        if ($clawed['deducted'] > 0) {
            $this->expenses->reverseExpense('fee', $clawed['deducted'], 'cashback_expense', 'Order', (int) $order->id,
                "เรียกคืน Cash Back ได้จากลูกค้า ออเดอร์ #{$order->order_number}");
        }

        return $result;
    }

    /**
     * รายได้ผู้ขายของออเดอร์: ยังไม่จ่าย = ยกเลิก + ดึงเงินพักคืน / จ่ายแล้ว = หักคืนจากผู้ขาย
     */
    protected function clawbackFromSellers(Order $order, ?int $adminId, string $reason): array
    {
        $results = [];

        $ledgers = EarningsLedger::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('earning_type', EarningsLedger::TYPE_SELLER_SALE)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($ledgers as $ledger) {
            $row = [
                'earning_id' => $ledger->id,
                'user_id' => (int) $ledger->user_id,
                'status_before' => $ledger->status,
                'net_amount' => round((float) $ledger->net_amount, 2),
                'clawback_amount' => 0.0,
                'deducted_from_wallet' => 0.0,
                'escrow_reversed' => 0.0,
                'debt_id' => null,
                'action' => 'none',
            ];

            if (in_array($ledger->status, [EarningsLedger::STATUS_PENDING, EarningsLedger::STATUS_AVAILABLE, EarningsLedger::STATUS_HELD], true)) {
                $reverse = $this->payouts->reverseUnpaidLedger($ledger, $order, $reason);
                $row['action'] = $reverse['action'];
                $row['escrow_reversed'] = $reverse['escrow_reversed'];
            } elseif (in_array($ledger->status, [EarningsLedger::STATUS_PAID, EarningsLedger::STATUS_PROCESSING], true)) {
                $amount = round((float) $ledger->net_amount, 2);
                $row['clawback_amount'] = $amount;

                if ($amount > 0) {
                    $clawed = $this->deductOrDebt(
                        (int) $ledger->user_id,
                        $amount,
                        'EarningsLedgerClawback',
                        (int) $ledger->id,
                        "หักคืนรายได้ผู้ขาย — คืนเงินออเดอร์ #{$order->order_number}",
                        'SellerClawback',
                        (int) $order->id,
                        "หักคืนรายได้จากออเดอร์ #{$order->order_number} (ลูกค้าได้รับเงินคืน): {$reason}",
                        $adminId,
                        1,
                        ['order_number' => $order->order_number, 'ledger_id' => $ledger->id]
                    );
                    $row['deducted_from_wallet'] = $clawed['deducted'];
                    $row['debt_id'] = $clawed['debt_id'];
                }

                $breakdown = is_array($ledger->breakdown) ? $ledger->breakdown : [];
                $breakdown['clawback'] = [
                    'at' => now()->toIso8601String(),
                    'amount' => $amount,
                    'deducted_from_wallet' => $row['deducted_from_wallet'],
                    'debt_id' => $row['debt_id'],
                ];
                $ledger->update([
                    'status' => EarningsLedger::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_reason' => mb_substr("หักคืนหลังจ่ายแล้ว — คืนเงินออเดอร์ #{$order->order_number}", 0, 255),
                    'breakdown' => $breakdown,
                ]);
                $row['action'] = $row['debt_id'] ? 'debt_created' : 'deducted';
            }

            $results[] = $row;
        }

        return $results;
    }

    /**
     * ย้อนรายการเงินเข้ากระเป๋าแพลตฟอร์มของออเดอร์ (ยกเว้นเงินพักผู้ขาย ซึ่งย้อนราย ledger แล้ว)
     */
    protected function adjustPlatformWallets(Order $order, string $reason): array
    {
        $adjustments = [];

        // เฉพาะรายการที่เกิดจากการแบ่งเงิน (GP, VAT, กองทุน MLM, ร้านทางการ) — ไม่รวมเงินพักผู้ขาย
        // (ย้อนราย ledger แล้ว) และไม่รวมรายการคืนรายจ่ายโปรที่เพิ่งสร้างในการคืนเงินครั้งนี้
        $subTypes = array_values(array_diff(
            OrderDistributionService::DISTRIBUTION_SUB_TYPES,
            [SellerPayoutService::ESCROW_HOLD_SUB_TYPE]
        ));

        $incomes = PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('type', PlatformTransaction::TYPE_INCOME)
            ->where('status', 'completed')
            ->whereIn('sub_type', $subTypes)
            ->orderBy('id')
            ->get();

        foreach ($incomes as $tx) {
            $amount = round((float) $tx->amount, 2);
            if ($amount <= 0) {
                continue;
            }

            $wallet = PlatformWallet::find($tx->platform_wallet_id);
            if (! $wallet) {
                continue;
            }

            $alreadyReversed = PlatformTransaction::where('platform_wallet_id', $wallet->id)
                ->where('sub_type', 'refund_reversal')
                ->where('source_type', 'Order')
                ->where('source_id', $order->id)
                ->where('metadata->original_tx_id', $tx->id)
                ->exists();
            if ($alreadyReversed) {
                continue;
            }

            $row = [
                'wallet_id' => $wallet->id,
                'wallet_slug' => $wallet->slug,
                'original_tx_id' => $tx->id,
                'original_sub_type' => $tx->sub_type,
                'reversed_amount' => 0.0,
                'shortfall' => 0.0,
            ];

            try {
                $reversal = $wallet->deductFunds($amount, 'refund_reversal', 'Order', (int) $order->id, [
                    'original_tx_id' => $tx->id,
                    'original_sub_type' => $tx->sub_type,
                    'order_number' => $order->order_number,
                    'reason' => $reason,
                ]);
                $reversal->update([
                    'related_user_id' => $tx->related_user_id,
                    'description' => mb_substr("ย้อนรายการ ({$tx->sub_type}) — คืนเงินออเดอร์ #{$order->order_number}", 0, 255),
                ]);
                $row['reversed_amount'] = $amount;
            } catch (\Throwable $e) {
                // เงินในกระเป๋าถูกใช้ไปแล้ว → แพลตฟอร์มรับภาระส่วนต่าง (ลูกค้ายังได้เงินคืนครบ)
                $row['shortfall'] = $amount;
                Log::warning('Refund: platform wallet reversal shortfall', [
                    'order_id' => $order->id,
                    'wallet' => $wallet->slug,
                    'amount' => $amount,
                    'error' => $e->getMessage(),
                ]);
            }

            $adjustments[] = $row;
        }

        return $adjustments;
    }

    /**
     * เงินคอม MLM ที่หักคืนจากผู้รับได้จริง → เติมกลับกองทุน MLM (กองทุนเคยจ่ายก้อนนี้ออกไป)
     */
    protected function returnMlmClawbackToPool(Order $order, array $mlmReport): void
    {
        $returned = round(collect($mlmReport['deducted_from_wallets'] ?? [])->sum('amount'), 2);
        if ($returned <= 0) {
            return;
        }

        $tx = PlatformWallet::getMlmPoolWallet()->addFunds($returned, 'mlm_clawback_return', 'OrderRefund', (int) $order->id, [
            'order_number' => $order->order_number,
        ]);
        $tx->update(['description' => "คอม MLM ที่หักคืน (คืนเงินออเดอร์ #{$order->order_number})"]);
    }

    /**
     * หักเงินจาก wallet เท่าที่มี ส่วนที่เหลือสร้างเป็นหนี้
     *
     * @return array{deducted: float, debt_id: ?int}
     */
    protected function deductOrDebt(
        int $userId,
        float $amount,
        string $walletRefType,
        int $walletRefId,
        string $walletDescription,
        string $debtSourceType,
        int $debtSourceId,
        string $debtReason,
        ?int $adminId,
        int $priority,
        array $metadata
    ): array {
        $amount = round($amount, 2);
        $deducted = 0.0;
        $debtId = null;

        $user = User::withTrashed()->find($userId);
        $wallet = $user ? $this->wallets->getOrCreateWallet($user) : null;

        if ($wallet && $wallet->isActive()) {
            $balance = round((float) $wallet->fresh()->balance, 2);
            $take = round(min($balance, $amount), 2);

            if ($take > 0) {
                try {
                    $this->wallets->deductForService($wallet, $take, $walletDescription, $walletRefType, $walletRefId, $metadata);
                    $deducted = $take;
                } catch (\Throwable $e) {
                    Log::warning('Refund clawback: wallet deduction failed, converting to debt', [
                        'user_id' => $userId,
                        'amount' => $take,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $remaining = round($amount - $deducted, 2);
        if ($remaining > 0) {
            $debt = WalletDebt::createDebt(
                $userId,
                $remaining,
                $debtSourceType,
                $debtSourceId,
                mb_substr($debtReason, 0, 1000),
                $adminId,
                $priority,
                array_merge($metadata, ['partial_deducted' => $deducted])
            );
            $debtId = $debt->id;
            $this->notifyDebtCreated($userId, $debt);
        }

        return ['deducted' => $deducted, 'debt_id' => $debtId];
    }

    /**
     * รายงานการคืนเงินของออเดอร์
     */
    public function getRefundReport(Order $order): array
    {
        $debts = WalletDebt::whereIn('source_type', ['SellerClawback', 'MlmClawback', 'CashbackClawback'])
            ->where('source_id', $order->id)
            ->get();

        $reversals = PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('sub_type', 'refund_reversal')
            ->get();

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_status' => $order->status,
            'payment_status' => $order->payment_status,
            'refund_status' => $order->payment_status === 'refunded' ? 'completed' : 'not_refunded',
            'debts' => [
                'total' => $debts->count(),
                'total_amount' => round((float) $debts->sum('original_amount'), 2),
                'collected' => round((float) $debts->sum('deducted_amount'), 2),
                'pending' => round((float) $debts->where('status', 'active')->sum('remaining_amount'), 2),
                'items' => $debts->map(fn ($d) => [
                    'id' => $d->id,
                    'user_id' => $d->user_id,
                    'type' => $d->source_type,
                    'amount' => (float) $d->original_amount,
                    'remaining' => (float) $d->remaining_amount,
                    'status' => $d->status,
                ])->values(),
            ],
            'platform_adjustments' => $reversals->map(fn ($r) => [
                'wallet_id' => $r->platform_wallet_id,
                'amount' => (float) $r->amount,
                'description' => $r->description,
            ])->values(),
        ];
    }

    /**
     * สถิติการคืนเงิน
     */
    public function getRefundStats(array $filters = []): array
    {
        $query = Order::where('payment_status', 'refunded');

        if (isset($filters['date_from'])) {
            $query->whereDate('refunded_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('refunded_at', '<=', $filters['date_to']);
        }

        $refundedOrders = $query->get();
        $debtTypes = ['SellerClawback', 'MlmClawback', 'CashbackClawback'];

        return [
            'total_refunds' => $refundedOrders->count(),
            'total_amount' => round((float) $refundedOrders->sum('total_amount'), 2),
            'debts_created' => WalletDebt::whereIn('source_type', $debtTypes)->count(),
            'debts_pending' => round((float) WalletDebt::whereIn('source_type', $debtTypes)->where('status', 'active')->sum('remaining_amount'), 2),
            'debts_collected' => round((float) WalletDebt::whereIn('source_type', $debtTypes)->sum('deducted_amount'), 2),
        ];
    }

    private function emptyReport(Order $order, string $reason, ?int $adminId): array
    {
        return [
            'order_id' => (int) $order->id,
            'order_number' => $order->order_number,
            'order_total' => round((float) $order->total_amount, 2),
            'refund_reason' => $reason,
            'refunded_by' => $adminId,
            'refunded_at' => now()->toIso8601String(),
            'rider_fee_withheld' => 0.0,
            'customer_refund' => null,
            'cashback_clawback' => null,
            'seller_clawback' => [],
            'mlm_clawback' => null,
            'platform_adjustments' => [],
            'debts_created' => [],
            'summary' => [
                'total_customer_refund' => 0.0,
                'total_cashback_clawback' => 0.0,
                'total_seller_clawback' => 0.0,
                'total_mlm_clawback' => 0.0,
                'total_debts_created' => 0,
            ],
        ];
    }

    protected function notifyCustomerRefunded(int $userId, float $amount, string $orderNumber): void
    {
        try {
            $user = User::find($userId);
            if ($user) {
                app(NotificationService::class)->create(
                    $user,
                    'order_refunded',
                    'คืนเงินคำสั่งซื้อแล้ว',
                    'คืนเงิน '.number_format($amount, 2)." บาท ของคำสั่งซื้อ #{$orderNumber} เข้ากระเป๋าเงินของคุณแล้ว",
                    ['order_number' => $orderNumber, 'amount' => $amount],
                    '/user/wallet'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Refund: notify customer failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    protected function notifyDebtCreated(int $userId, WalletDebt $debt): void
    {
        try {
            $user = User::find($userId);
            if ($user) {
                app(NotificationService::class)->notifyDebtCreated($user, $debt);
            }
        } catch (\Throwable $e) {
            Log::warning('ส่ง Notification หนี้ใหม่ล้มเหลว', ['debt_id' => $debt->id, 'error' => $e->getMessage()]);
        }
    }
}
