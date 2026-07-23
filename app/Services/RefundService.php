<?php

namespace App\Services;

use App\Models\EarningsLedger;
use App\Models\MlmCommission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlatformTransaction;
use App\Models\PlatformWallet;
use App\Models\User;
use App\Models\WalletDebt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RefundService
 *
 * ระบบ Refund รวมศูนย์ - ใช้ Order/Bill เป็นตัวอ้างอิงหลัก
 *
 * ครอบคลุม:
 * - คืนเงินลูกค้า
 * - เรียกคืน Cashback ที่จ่ายไปแล้ว (ใหม่!)
 * - เรียกคืนเงินจากผู้ขาย (Seller/Admin Shop)
 * - เรียกคืน MLM Commission ทั้งสายงาน
 * - เรียกคืน Affiliate Commission
 * - เรียกคืนค่า Fee, VAT จาก Platform Wallets
 *
 * หลักการ: ติดตามทุก Transaction ที่เกี่ยวข้องกับ Order
 * แล้วย้อนกลับทั้งหมด (Reverse all transactions)
 */
class RefundService
{
    protected MlmCommissionClawbackService $mlmClawbackService;

    protected DebtCollectionService $debtService;

    public function __construct()
    {
        $this->mlmClawbackService = new MlmCommissionClawbackService;
        $this->debtService = new DebtCollectionService;
    }

    /**
     * ดำเนินการ Full Refund สำหรับ Order
     */
    public function processFullRefund(Order $order, ?int $adminId = null, string $reason = ''): array
    {
        return DB::transaction(function () use ($order, $adminId, $reason) {
            $refundReport = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_total' => $order->total_amount,
                'refund_reason' => $reason,
                'refunded_by' => $adminId,
                'refunded_at' => now()->toIso8601String(),

                // 1. Customer Refund
                'customer_refund' => null,

                // 2. Cashback Clawback (ใหม่!)
                'cashback_clawback' => null,

                // 3. Seller/Admin Shop Clawback
                'seller_clawback' => [],

                // 4. MLM Commission Clawback
                'mlm_clawback' => null,

                // 5. Platform Wallet Adjustments
                'platform_adjustments' => [],

                // 6. Debts Created
                'debts_created' => [],

                // Summary
                'summary' => [
                    'total_customer_refund' => 0,
                    'total_cashback_clawback' => 0,
                    'total_seller_clawback' => 0,
                    'total_mlm_clawback' => 0,
                    'total_debts_created' => 0,
                ],
            ];

            // ===== 1. คืนเงินให้ลูกค้า =====
            $refundReport['customer_refund'] = $this->refundToCustomer($order, $adminId, $reason);
            $refundReport['summary']['total_customer_refund'] = $refundReport['customer_refund']['amount'] ?? 0;

            // ===== 2. เรียกคืน Cashback ที่จ่ายไปแล้ว =====
            $refundReport['cashback_clawback'] = $this->clawbackCashback($order, $adminId, $reason);
            $refundReport['summary']['total_cashback_clawback'] = $refundReport['cashback_clawback']['clawback_amount'] ?? 0;

            // ===== 3. เรียกคืนเงินจากผู้ขาย/ร้านแอดมิน =====
            $refundReport['seller_clawback'] = $this->clawbackFromSellers($order, $adminId, $reason);
            $refundReport['summary']['total_seller_clawback'] = collect($refundReport['seller_clawback'])
                ->sum('clawback_amount');

            // ===== 4. เรียกคืน MLM Commission ทั้งสายงาน =====
            $refundReport['mlm_clawback'] = $this->mlmClawbackService->clawbackOrderCommissions($order, $adminId);
            $refundReport['summary']['total_mlm_clawback'] = $refundReport['mlm_clawback']['total_clawback_amount'] ?? 0;

            // ===== 5. ปรับยอด Platform Wallets =====
            $refundReport['platform_adjustments'] = $this->adjustPlatformWallets($order, $reason);

            // ===== 6. รวบรวม Debts ที่สร้าง =====
            $allDebts = [];

            // จาก Cashback clawback
            if (! empty($refundReport['cashback_clawback']['debt_id'])) {
                $allDebts[] = $refundReport['cashback_clawback']['debt_id'];
            }

            // จาก Seller clawback
            foreach ($refundReport['seller_clawback'] as $seller) {
                if (! empty($seller['debt_id'])) {
                    $allDebts[] = $seller['debt_id'];
                }
            }

            // จาก MLM clawback
            if (! empty($refundReport['mlm_clawback']['debts_created'])) {
                $allDebts = array_merge($allDebts, $refundReport['mlm_clawback']['debts_created']);
            }

            $refundReport['debts_created'] = $allDebts;
            $refundReport['summary']['total_debts_created'] = count($allDebts);

            // อัพเดทสถานะ Order
            $order->update([
                'status' => 'refunded',
                'refund_reason' => $reason,
                'refunded_at' => now(),
                'refunded_by' => $adminId,
            ]);

            // บันทึก Log
            Log::info('Full refund processed', [
                'order_id' => $order->id,
                'summary' => $refundReport['summary'],
            ]);

            return $refundReport;
        });
    }

    /**
     * Partial Refund - คืนเงินบางส่วน (เฉพาะบาง Item)
     *
     * @param  array  $itemIds  รายการ OrderItem IDs ที่จะ refund
     */
    public function processPartialRefund(
        Order $order,
        array $itemIds,
        ?int $adminId = null,
        string $reason = ''
    ): array {
        return DB::transaction(function () use ($order, $itemIds, $adminId, $reason) {
            $refundReport = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refund_type' => 'partial',
                'refunded_items' => [],
                'total_refund' => 0,
                'debts_created' => [],
            ];

            $items = OrderItem::whereIn('id', $itemIds)
                ->where('order_id', $order->id)
                ->get();

            foreach ($items as $item) {
                $itemRefund = $this->refundOrderItem($item, $order, $adminId, $reason);
                $refundReport['refunded_items'][] = $itemRefund;
                $refundReport['total_refund'] += $itemRefund['customer_refund'];

                if (! empty($itemRefund['debts'])) {
                    $refundReport['debts_created'] = array_merge(
                        $refundReport['debts_created'],
                        $itemRefund['debts']
                    );
                }
            }

            // อัพเดทสถานะ Order เป็น partially_refunded
            $order->update([
                'status' => 'partially_refunded',
                'notes' => ($order->notes ?? '')."\nPartial refund: ".$reason,
            ]);

            Log::info('Partial refund processed', $refundReport);

            return $refundReport;
        });
    }

    /**
     * คืนเงินให้ลูกค้า
     */
    protected function refundToCustomer(Order $order, ?int $adminId, string $reason): array
    {
        $result = [
            'customer_id' => $order->user_id,
            'amount' => $order->total_amount,
            'method' => 'wallet', // หรือ original_payment_method
            'status' => 'completed',
        ];

        $customer = User::find($order->user_id);
        if (! $customer) {
            $result['status'] = 'failed';
            $result['error'] = 'Customer not found';

            return $result;
        }

        // ดึงเงินจาก Refund Pool
        $refundPool = PlatformWallet::where('slug', 'refund_pool')->first();
        if ($refundPool && $refundPool->balance >= $order->total_amount) {
            $refundPool->deductFunds(
                $order->total_amount,
                'refund',
                "Refund to customer - Order #{$order->order_number}",
                'Order',
                $order->id
            );
        }

        // เพิ่มเงินเข้า Wallet ลูกค้า
        if ($customer->wallet) {
            $customer->wallet->increment('balance', $order->total_amount);

            if (method_exists($customer->wallet, 'transactions')) {
                $customer->wallet->transactions()->create([
                    'user_id' => $customer->id,
                    'type' => 'refund',
                    'amount' => $order->total_amount,
                    'balance_after' => $customer->wallet->fresh()->balance,
                    'description' => "คืนเงิน Order #{$order->order_number}: {$reason}",
                    'reference_type' => 'Order',
                    'reference_id' => $order->id,
                ]);
            }
        }

        return $result;
    }

    /**
     * เรียกคืน Cashback ที่จ่ายไปแล้วให้ลูกค้า
     *
     * หัก cashback ออกจาก wallet ลูกค้า
     * ถ้าเงินไม่พอ → สร้างเป็นหนี้
     */
    protected function clawbackCashback(Order $order, ?int $adminId, string $reason): array
    {
        $result = [
            'customer_id' => $order->user_id,
            'cashback_amount' => $order->cashback_amount ?? 0,
            'clawback_amount' => 0,
            'deducted_from_wallet' => 0,
            'debt_id' => null,
            'status' => 'skipped',
            'reason' => null,
        ];

        // ตรวจสอบว่ามี cashback ที่ต้องเรียกคืนหรือไม่
        if (! $order->cashback_processed || ($order->cashback_amount ?? 0) <= 0) {
            $result['reason'] = 'No cashback to clawback';

            return $result;
        }

        $cashbackAmount = $order->cashback_amount;
        $result['clawback_amount'] = $cashbackAmount;

        $customer = User::find($order->user_id);
        if (! $customer) {
            $result['status'] = 'failed';
            $result['reason'] = 'Customer not found';

            return $result;
        }

        // ตรวจสอบยอดเงินใน Wallet
        $walletBalance = $customer->wallet?->balance ?? 0;

        if ($walletBalance >= $cashbackAmount) {
            // มีเงินพอ → หักทันที
            $customer->wallet->decrement('balance', $cashbackAmount);
            $this->createWalletTransaction(
                $customer,
                -$cashbackAmount,
                'cashback_clawback',
                $order,
                "เรียกคืน Cashback - Refund Order #{$order->order_number}"
            );

            $result['deducted_from_wallet'] = $cashbackAmount;
            $result['status'] = 'deducted';

        } elseif ($walletBalance > 0) {
            // มีบางส่วน → หักเท่าที่มี + สร้างหนี้
            $customer->wallet->decrement('balance', $walletBalance);
            $this->createWalletTransaction(
                $customer,
                -$walletBalance,
                'cashback_clawback',
                $order,
                "เรียกคืน Cashback (บางส่วน) - Refund Order #{$order->order_number}"
            );

            $result['deducted_from_wallet'] = $walletBalance;

            // สร้างหนี้ส่วนที่เหลือ
            $debtAmount = $cashbackAmount - $walletBalance;
            $debt = WalletDebt::createDebt(
                $order->user_id,
                $debtAmount,
                'CashbackClawback',
                $order->id,
                "เรียกคืน Cashback ส่วนที่เหลือจาก Order #{$order->order_number}: {$reason}",
                $adminId,
                2, // Priority 2 (ต่ำกว่า Seller/MLM)
                [
                    'order_number' => $order->order_number,
                    'original_cashback' => $cashbackAmount,
                    'partial_deducted' => $walletBalance,
                ]
            );

            $result['debt_id'] = $debt->id;
            $result['status'] = 'partial_debt';
            $this->notifyDebtCreated($order->user_id, $debt);

        } else {
            // ไม่มีเงินเลย → สร้างหนี้ทั้งหมด
            $debt = WalletDebt::createDebt(
                $order->user_id,
                $cashbackAmount,
                'CashbackClawback',
                $order->id,
                "เรียกคืน Cashback จาก Order #{$order->order_number}: {$reason}",
                $adminId,
                2,
                [
                    'order_number' => $order->order_number,
                    'original_cashback' => $cashbackAmount,
                ]
            );

            $result['debt_id'] = $debt->id;
            $result['status'] = 'full_debt';
            $this->notifyDebtCreated($order->user_id, $debt);
        }

        // อัพเดทสถานะ cashback ใน Order
        $order->update([
            'cashback_clawback_at' => now(),
            'cashback_clawback_reason' => $reason,
        ]);

        Log::info('Cashback clawback processed', [
            'order_id' => $order->id,
            'customer_id' => $order->user_id,
            'cashback_amount' => $cashbackAmount,
            'deducted' => $result['deducted_from_wallet'],
            'debt_id' => $result['debt_id'],
        ]);

        return $result;
    }

    /**
     * เรียกคืนเงินจากผู้ขาย/ร้านแอดมิน
     */
    protected function clawbackFromSellers(Order $order, ?int $adminId, string $reason): array
    {
        $results = [];

        // ดึง Earnings ทั้งหมดที่เกี่ยวกับ Order นี้
        $earnings = EarningsLedger::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->whereIn('earning_type', ['seller_sale', 'admin_shop', 'admin_services'])
            ->get();

        foreach ($earnings as $earning) {
            $result = [
                'earning_id' => $earning->id,
                'user_id' => $earning->user_id,
                'earning_type' => $earning->earning_type,
                'gross_amount' => $earning->gross_amount,
                'net_amount' => $earning->net_amount,
                'clawback_amount' => 0,
                'deducted_from_wallet' => 0,
                'debt_id' => null,
                'action' => 'none',
            ];

            // ตรวจสอบสถานะ Earning
            if ($earning->status === EarningsLedger::STATUS_PENDING) {
                // ยังไม่ available → ยกเลิกได้เลย
                $earning->update([
                    'status' => EarningsLedger::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_reason' => "Refund Order #{$order->order_number}: {$reason}",
                ]);
                $result['action'] = 'cancelled';

            } elseif ($earning->status === EarningsLedger::STATUS_AVAILABLE) {
                // Available แต่ยังไม่ถอน → ยกเลิก + คืนเงินเข้า Platform
                $earning->update([
                    'status' => EarningsLedger::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_reason' => "Refund Order #{$order->order_number}: {$reason}",
                ]);
                $result['action'] = 'cancelled_available';

            } elseif (in_array($earning->status, [EarningsLedger::STATUS_PROCESSING, EarningsLedger::STATUS_PAID])) {
                // จ่ายไปแล้ว → ต้องเรียกคืน
                $result['clawback_amount'] = $earning->net_amount;

                // สำหรับ Admin Shop/Services → คืนเข้า Platform Wallet โดยตรง
                if (in_array($earning->earning_type, ['admin_shop', 'admin_services'])) {
                    $walletSlug = $earning->earning_type === 'admin_shop' ? 'admin_shop' : 'admin_services';
                    $wallet = PlatformWallet::where('slug', $walletSlug)->first();

                    if ($wallet) {
                        // หักจาก Admin Wallet
                        $wallet->deductFunds(
                            $earning->net_amount,
                            'refund_clawback',
                            "Clawback - Order #{$order->order_number}",
                            'Order',
                            $order->id
                        );
                        $result['action'] = 'clawback_from_admin_wallet';
                        $result['deducted_from_wallet'] = $earning->net_amount;
                    }

                } else {
                    // สำหรับ Seller ทั่วไป → ตรวจสอบ Wallet และสร้างหนี้ถ้าจำเป็น
                    $clawbackResult = $this->clawbackFromUser(
                        $earning->user_id,
                        $earning->net_amount,
                        $order,
                        $adminId,
                        "Seller clawback - {$reason}"
                    );

                    $result['deducted_from_wallet'] = $clawbackResult['deducted'];
                    $result['debt_id'] = $clawbackResult['debt_id'];
                    $result['action'] = $clawbackResult['debt_id'] ? 'debt_created' : 'deducted';
                }

                $earning->update([
                    'status' => 'clawback',
                    'cancel_reason' => "Clawback - Order #{$order->order_number}",
                ]);
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * เรียกคืนเงินจาก User
     */
    protected function clawbackFromUser(
        int $userId,
        float $amount,
        Order $order,
        ?int $adminId,
        string $reason
    ): array {
        $result = [
            'deducted' => 0,
            'debt_id' => null,
        ];

        $user = User::find($userId);
        if (! $user || ! $user->wallet) {
            // ไม่มี wallet → สร้างหนี้ทั้งหมด
            $debt = WalletDebt::createDebt(
                $userId,
                $amount,
                'SellerClawback',
                $order->id,
                $reason,
                $adminId,
                1,
                ['order_number' => $order->order_number]
            );
            $result['debt_id'] = $debt->id;
            $this->notifyDebtCreated($userId, $debt);

            return $result;
        }

        $walletBalance = $user->wallet?->balance ?? 0;

        if ($walletBalance >= $amount) {
            // มีเงินพอ → หักทันที
            $user->wallet->decrement('balance', $amount);
            $this->createWalletTransaction($user, -$amount, 'seller_clawback', $order, $reason);
            $result['deducted'] = $amount;

        } elseif ($walletBalance > 0) {
            // มีบางส่วน → หักเท่าที่มี + สร้างหนี้
            $user->wallet->decrement('balance', $walletBalance);
            $this->createWalletTransaction($user, -$walletBalance, 'seller_clawback', $order, $reason);
            $result['deducted'] = $walletBalance;

            $debtAmount = $amount - $walletBalance;
            $debt = WalletDebt::createDebt(
                $userId,
                $debtAmount,
                'SellerClawback',
                $order->id,
                $reason,
                $adminId,
                1,
                ['order_number' => $order->order_number, 'partial_deducted' => $walletBalance]
            );
            $result['debt_id'] = $debt->id;
            $this->notifyDebtCreated($userId, $debt);

        } else {
            // ไม่มีเงินเลย → สร้างหนี้ทั้งหมด
            $debt = WalletDebt::createDebt(
                $userId,
                $amount,
                'SellerClawback',
                $order->id,
                $reason,
                $adminId,
                1,
                ['order_number' => $order->order_number]
            );
            $result['debt_id'] = $debt->id;
            $this->notifyDebtCreated($userId, $debt);
        }

        return $result;
    }

    /**
     * ปรับยอด Platform Wallets
     */
    protected function adjustPlatformWallets(Order $order, string $reason): array
    {
        $adjustments = [];

        // หา transactions ที่เกี่ยวกับ Order นี้
        $transactions = PlatformTransaction::where('reference_type', 'Order')
            ->where('reference_id', $order->id)
            ->where('type', 'income')
            ->get();

        foreach ($transactions as $tx) {
            $wallet = PlatformWallet::find($tx->wallet_id);
            if (! $wallet) {
                continue;
            }

            // สร้าง reverse transaction
            $wallet->deductFunds(
                $tx->amount,
                'refund_reversal',
                "Reverse: {$tx->description} - Refund Order #{$order->order_number}",
                'Order',
                $order->id
            );

            $adjustments[] = [
                'wallet_id' => $wallet->id,
                'wallet_name' => $wallet->name,
                'original_tx_id' => $tx->id,
                'reversed_amount' => $tx->amount,
            ];
        }

        return $adjustments;
    }

    /**
     * Refund สำหรับ OrderItem เดียว
     */
    protected function refundOrderItem(
        OrderItem $item,
        Order $order,
        ?int $adminId,
        string $reason
    ): array {
        $result = [
            'item_id' => $item->id,
            'product_name' => $item->product_name ?? 'Item #'.$item->id,
            'item_total' => $item->total_price,
            'customer_refund' => $item->total_price,
            'seller_clawback' => 0,
            'mlm_clawback' => 0,
            'debts' => [],
        ];

        // คืนเงินลูกค้า
        $customer = User::find($order->user_id);
        if ($customer && $customer->wallet) {
            $customer->wallet->increment('balance', $item->total_price);
            $this->createWalletTransaction(
                $customer,
                $item->total_price,
                'partial_refund',
                $order,
                "คืนเงิน: {$item->product_name}"
            );
        }

        // เรียกคืนจาก Seller
        $sellerEarning = EarningsLedger::where('source_type', 'OrderItem')
            ->where('source_id', $item->id)
            ->where('earning_type', 'seller_sale')
            ->first();

        if ($sellerEarning && in_array($sellerEarning->status, ['available', 'paid'])) {
            $clawback = $this->clawbackFromUser(
                $sellerEarning->user_id,
                $sellerEarning->net_amount,
                $order,
                $adminId,
                $reason
            );

            $result['seller_clawback'] = $sellerEarning->net_amount;
            if ($clawback['debt_id']) {
                $result['debts'][] = $clawback['debt_id'];
            }
        }

        // เรียกคืน MLM Commission ที่เกี่ยวกับ Item นี้
        // ⚠️ ปัจจุบันระบบบันทึก MLM commission เป็นระดับ Order (FQCN) เท่านั้น ไม่มีระดับ OrderItem
        //    query นี้จึงเจอเฉพาะกรณีที่มีการบันทึก per-item ในอนาคต — การเรียกคืนระดับ Order
        //    ทำที่ MlmCommissionClawbackService::clawbackOrderCommissions (full refund)
        $mlmCommissions = MlmCommission::whereIn('source_type', [\App\Models\OrderItem::class, 'OrderItem'])
            ->where('source_id', $item->id)
            ->where('status', 'paid')
            ->get();

        foreach ($mlmCommissions as $commission) {
            $clawback = $this->clawbackFromUser(
                $commission->user_id,
                $commission->commission_amount,
                $order,
                $adminId,
                "MLM Clawback - {$reason}"
            );

            $result['mlm_clawback'] += $commission->commission_amount;
            if ($clawback['debt_id']) {
                $result['debts'][] = $clawback['debt_id'];
            }

            $commission->update(['status' => 'clawback']);
        }

        // อัพเดทสถานะ Item
        $item->update([
            'status' => 'refunded',
            'refund_reason' => $reason,
        ]);

        return $result;
    }

    /**
     * สร้าง Wallet Transaction
     */
    protected function createWalletTransaction(
        User $user,
        float $amount,
        string $type,
        Order $order,
        string $description
    ): void {
        if (! $user->wallet || ! method_exists($user->wallet, 'transactions')) {
            return;
        }

        $user->wallet->transactions()->create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $user->wallet->fresh()->balance,
            'description' => $description,
            'reference_type' => 'Order',
            'reference_id' => $order->id,
        ]);
    }

    /**
     * ดึงรายงาน Refund ของ Order
     */
    public function getRefundReport(Order $order): array
    {
        // ดึงหนี้ทั้งหมดที่เกี่ยวกับ Order
        $debts = WalletDebt::where(function ($q) use ($order) {
            $q->where('source_type', 'SellerClawback')
                ->where('source_id', $order->id);
        })->orWhere(function ($q) use ($order) {
            $q->where('source_type', 'MlmClawback')
                ->where('source_id', $order->id);
        })->get();

        // ดึง Platform Transaction reversals
        $reversals = PlatformTransaction::where('reference_type', 'Order')
            ->where('reference_id', $order->id)
            ->where('type', 'refund_reversal')
            ->get();

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_status' => $order->status,
            'refund_status' => $order->status === 'refunded' ? 'completed' : 'partial',
            'debts' => [
                'total' => $debts->count(),
                'total_amount' => $debts->sum('original_amount'),
                'collected' => $debts->sum('deducted_amount'),
                'pending' => $debts->where('status', 'active')->sum('remaining_amount'),
                'items' => $debts->map(fn ($d) => [
                    'id' => $d->id,
                    'user_id' => $d->user_id,
                    'type' => $d->source_type,
                    'amount' => $d->original_amount,
                    'remaining' => $d->remaining_amount,
                    'status' => $d->status,
                ]),
            ],
            'platform_adjustments' => $reversals->map(fn ($r) => [
                'wallet_id' => $r->wallet_id,
                'amount' => $r->amount,
                'description' => $r->description,
            ]),
        ];
    }

    /**
     * ดึงสถิติ Refund
     */
    public function getRefundStats(array $filters = []): array
    {
        $query = Order::where('status', 'refunded');

        if (isset($filters['date_from'])) {
            $query->whereDate('refunded_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('refunded_at', '<=', $filters['date_to']);
        }

        $refundedOrders = $query->get();

        return [
            'total_refunds' => $refundedOrders->count(),
            'total_amount' => $refundedOrders->sum('total_amount'),
            'debts_created' => WalletDebt::whereIn('source_type', ['SellerClawback', 'MlmClawback'])->count(),
            'debts_pending' => WalletDebt::whereIn('source_type', ['SellerClawback', 'MlmClawback'])
                ->where('status', 'active')
                ->sum('remaining_amount'),
            'debts_collected' => WalletDebt::whereIn('source_type', ['SellerClawback', 'MlmClawback'])
                ->sum('deducted_amount'),
        ];
    }

    /**
     * ส่ง Notification แจ้งหนี้ใหม่ (helper method)
     */
    protected function notifyDebtCreated(int $userId, WalletDebt $debt): void
    {
        try {
            $user = User::find($userId);
            if ($user) {
                app(NotificationService::class)->notifyDebtCreated($user, $debt);
            }
        } catch (\Exception $e) {
            Log::warning('ส่ง Notification หนี้ใหม่ล้มเหลว', ['debt_id' => $debt->id, 'error' => $e->getMessage()]);
        }
    }
}
