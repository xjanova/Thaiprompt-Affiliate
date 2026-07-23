<?php

namespace App\Services;

use App\Models\EarningsLedger;
use App\Models\MlmCommission;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletDebt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MlmCommissionClawbackService
 *
 * จัดการ Clawback (เรียกคืน) MLM Commission เมื่อมีการ Refund
 * - ค้นหา Commission ทั้งหมดที่เกี่ยวข้องกับ Order
 * - สร้างหนี้สำหรับทุกคนที่ได้รับ Commission
 * - ถ้ามีเงินใน Wallet → หักทันที
 * - ถ้าไม่พอ → สร้างเป็นหนี้รอหักจากรายได้ในอนาคต
 */
class MlmCommissionClawbackService
{
    protected DebtCollectionService $debtService;

    public function __construct()
    {
        $this->debtService = new DebtCollectionService;
    }

    /**
     * Clawback ทุก Commission ที่เกี่ยวข้องกับ Order
     *
     * @param  int|null  $adminId  Admin ที่ทำการ Refund
     */
    public function clawbackOrderCommissions(Order $order, ?int $adminId = null): array
    {
        return DB::transaction(function () use ($order, $adminId) {
            $results = [
                'order_id' => $order->id,
                'total_commissions' => 0,
                'total_clawback_amount' => 0,
                'affected_users' => [],
                'debts_created' => [],
                'deducted_from_wallets' => [],
                'cancelled_pending' => [],
            ];

            // ค้นหา Commission ทั้งหมดที่เกี่ยวข้องกับ Order นี้
            // ⚠️ ข้อมูลจริงบันทึก source_type เป็น FQCN (App\Models\Order) ผ่าน Order::class
            //    ต้อง match ทั้ง FQCN และ short name เดิม ไม่งั้น clawback หาไม่เจอเลย
            $commissions = MlmCommission::whereIn('source_type', [Order::class, 'Order'])
                ->where('source_id', $order->id)
                ->get();

            if ($commissions->isEmpty()) {
                // ลองค้นหาจาก OrderItem
                $orderItemIds = $order->items()->pluck('id');
                $commissions = MlmCommission::whereIn('source_type', [\App\Models\OrderItem::class, 'OrderItem'])
                    ->whereIn('source_id', $orderItemIds)
                    ->get();
            }

            $results['total_commissions'] = $commissions->count();

            // จัดกลุ่มตาม user_id
            $commissionsByUser = $commissions->groupBy('user_id');

            foreach ($commissionsByUser as $userId => $userCommissions) {
                $userResult = $this->clawbackUserCommissions(
                    $userId,
                    $userCommissions,
                    $order,
                    $adminId
                );

                $results['total_clawback_amount'] += $userResult['clawback_amount'];
                $results['affected_users'][] = $userResult;

                if (! empty($userResult['debt_id'])) {
                    $results['debts_created'][] = $userResult['debt_id'];
                }

                if ($userResult['deducted_from_wallet'] > 0) {
                    $results['deducted_from_wallets'][] = [
                        'user_id' => $userId,
                        'amount' => $userResult['deducted_from_wallet'],
                    ];
                }

                if (! empty($userResult['cancelled_commission_ids'])) {
                    $results['cancelled_pending'] = array_merge(
                        $results['cancelled_pending'],
                        $userResult['cancelled_commission_ids']
                    );
                }
            }

            Log::info('MLM Commission clawback completed', $results);

            return $results;
        });
    }

    /**
     * Clawback Commission ของ User คนเดียว
     *
     * @param  \Illuminate\Support\Collection  $commissions
     */
    protected function clawbackUserCommissions(
        int $userId,
        $commissions,
        Order $order,
        ?int $adminId
    ): array {
        $result = [
            'user_id' => $userId,
            'commission_count' => $commissions->count(),
            'clawback_amount' => 0,
            'deducted_from_wallet' => 0,
            'debt_created' => 0,
            'debt_id' => null,
            'cancelled_commission_ids' => [],
            'status' => 'processed',
        ];

        $pendingAmount = 0;
        $paidAmount = 0;

        foreach ($commissions as $commission) {
            // ตรวจสอบสถานะ Commission
            if (in_array($commission->status, ['pending', 'approved'])) {
                // ยังไม่จ่าย → ยกเลิกได้เลย
                $commission->update([
                    'status' => 'cancelled',
                    'rejection_reason' => "Clawback due to refund - Order #{$order->id}",
                    'rejected_at' => now(),
                ]);

                $result['cancelled_commission_ids'][] = $commission->id;
                $pendingAmount += $commission->commission_amount;

            } elseif ($commission->status === 'paid') {
                // จ่ายไปแล้ว → ต้องเรียกคืน
                $paidAmount += $commission->commission_amount;

                // อัพเดทสถานะเป็น clawback
                $commission->update([
                    'status' => 'clawback',
                    'notes' => "Clawback due to refund - Order #{$order->id}",
                ]);
            }
        }

        $result['clawback_amount'] = $pendingAmount + $paidAmount;

        // ถ้ามีเงินที่ต้องเรียกคืน (เคยจ่ายไปแล้ว)
        if ($paidAmount > 0) {
            $clawbackResult = $this->processClawback(
                $userId,
                $paidAmount,
                $order,
                $adminId
            );

            $result['deducted_from_wallet'] = $clawbackResult['deducted_from_wallet'];
            $result['debt_created'] = $clawbackResult['debt_amount'];
            $result['debt_id'] = $clawbackResult['debt_id'];
        }

        // ยกเลิก Earnings Ledger ที่เกี่ยวข้อง (ถ้ามี)
        $this->cancelRelatedEarnings($userId, $order);

        return $result;
    }

    /**
     * ประมวลผล Clawback - หักเงินหรือสร้างหนี้
     */
    protected function processClawback(
        int $userId,
        float $amount,
        Order $order,
        ?int $adminId
    ): array {
        $result = [
            'deducted_from_wallet' => 0,
            'debt_amount' => 0,
            'debt_id' => null,
        ];

        $user = User::find($userId);
        if (! $user) {
            return $result;
        }

        // ตรวจสอบยอดเงินใน Wallet (null safety)
        $walletBalance = $user->wallet?->balance ?? 0;

        if ($walletBalance >= $amount) {
            // มีเงินพอ → หักทันที
            $this->deductFromWallet($user, $amount, $order);
            $result['deducted_from_wallet'] = $amount;

        } elseif ($walletBalance > 0) {
            // มีเงินบางส่วน → หักเท่าที่มี + สร้างหนี้ส่วนที่เหลือ
            $this->deductFromWallet($user, $walletBalance, $order);
            $result['deducted_from_wallet'] = $walletBalance;

            $debtAmount = $amount - $walletBalance;
            $debt = $this->createClawbackDebt($userId, $debtAmount, $order, $adminId);
            $result['debt_amount'] = $debtAmount;
            $result['debt_id'] = $debt->id;

        } else {
            // ไม่มีเงินเลย → สร้างหนี้ทั้งหมด
            $debt = $this->createClawbackDebt($userId, $amount, $order, $adminId);
            $result['debt_amount'] = $amount;
            $result['debt_id'] = $debt->id;
        }

        return $result;
    }

    /**
     * หักเงินจาก Wallet
     */
    protected function deductFromWallet(User $user, float $amount, Order $order): void
    {
        if (! $user->wallet) {
            return;
        }

        $user->wallet->decrement('balance', $amount);

        // บันทึก Transaction
        if (method_exists($user->wallet, 'transactions')) {
            $user->wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'mlm_clawback',
                'amount' => -$amount,
                'balance_after' => $user->wallet->fresh()->balance,
                'description' => "Clawback MLM Commission - Order #{$order->order_number}",
                'reference_type' => 'Order',
                'reference_id' => $order->id,
            ]);
        }

        Log::info('MLM Commission deducted from wallet', [
            'user_id' => $user->id,
            'amount' => $amount,
            'order_id' => $order->id,
        ]);
    }

    /**
     * สร้างหนี้ Clawback
     */
    protected function createClawbackDebt(
        int $userId,
        float $amount,
        Order $order,
        ?int $adminId
    ): WalletDebt {
        $debt = WalletDebt::createDebt(
            $userId,
            $amount,
            'MlmClawback',
            $order->id,
            "Clawback MLM Commission จาก Order #{$order->order_number} เนื่องจากการ Refund",
            $adminId,
            1, // Priority สูงสุด
            [
                'order_number' => $order->order_number,
                'clawback_type' => 'mlm_commission',
                'created_at' => now()->toIso8601String(),
            ]
        );

        // ส่ง Notification แจ้งหนี้ใหม่
        try {
            $user = User::find($userId);
            if ($user) {
                app(NotificationService::class)->notifyDebtCreated($user, $debt);
            }
        } catch (\Exception $e) {
            Log::warning('ส่ง Notification หนี้ MLM ล้มเหลว', ['debt_id' => $debt->id]);
        }

        return $debt;
    }

    /**
     * ยกเลิก Earnings ที่เกี่ยวข้อง
     */
    protected function cancelRelatedEarnings(int $userId, Order $order): void
    {
        // ยกเลิก earnings ที่ยัง pending หรือ available
        EarningsLedger::where('user_id', $userId)
            ->where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('earning_type', 'mlm_commission')
            ->whereIn('status', [
                EarningsLedger::STATUS_PENDING,
                EarningsLedger::STATUS_AVAILABLE,
            ])
            ->update([
                'status' => EarningsLedger::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => "Order #{$order->order_number} refunded",
            ]);
    }

    /**
     * ดึงสถิติ Clawback
     */
    public function getClawbackStats(array $filters = []): array
    {
        $query = WalletDebt::where('source_type', 'MlmClawback');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_clawbacks' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('original_amount'),
            'total_collected' => (clone $query)->sum('deducted_amount'),
            'total_pending' => (clone $query)->where('status', WalletDebt::STATUS_ACTIVE)->sum('remaining_amount'),
            'collection_rate' => $this->calculateCollectionRate($query),
            'by_status' => (clone $query)
                ->selectRaw('status, COUNT(*) as count, SUM(original_amount) as total')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->toArray(),
        ];
    }

    /**
     * คำนวณอัตราการเก็บเงิน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function calculateCollectionRate($query): float
    {
        $totalOriginal = (clone $query)->sum('original_amount');
        $totalCollected = (clone $query)->sum('deducted_amount');

        if ($totalOriginal <= 0) {
            return 0;
        }

        return round(($totalCollected / $totalOriginal) * 100, 2);
    }

    /**
     * Clawback Commission เฉพาะ User (Manual)
     */
    public function clawbackSingleCommission(
        MlmCommission $commission,
        ?int $adminId = null,
        string $reason = 'Manual clawback'
    ): array {
        return DB::transaction(function () use ($commission, $adminId, $reason) {
            $result = [
                'commission_id' => $commission->id,
                'user_id' => $commission->user_id,
                'amount' => $commission->commission_amount,
                'action' => 'cancelled',
                'debt_id' => null,
            ];

            if (in_array($commission->status, ['pending', 'approved'])) {
                // ยังไม่จ่าย → ยกเลิกได้เลย
                $commission->update([
                    'status' => 'cancelled',
                    'rejection_reason' => $reason,
                    'rejected_at' => now(),
                ]);

            } elseif ($commission->status === 'paid') {
                // จ่ายแล้ว → ต้องเรียกคืน
                $commission->update([
                    'status' => 'clawback',
                    'notes' => $reason,
                ]);

                // สร้างหนี้
                $debt = WalletDebt::createDebt(
                    $commission->user_id,
                    $commission->commission_amount,
                    'MlmClawback',
                    $commission->id,
                    $reason,
                    $adminId,
                    1,
                    ['commission_id' => $commission->id]
                );

                $result['action'] = 'debt_created';
                $result['debt_id'] = $debt->id;

                // ส่ง Notification แจ้งหนี้ใหม่
                try {
                    $debtUser = User::find($commission->user_id);
                    if ($debtUser) {
                        app(NotificationService::class)->notifyDebtCreated($debtUser, $debt);
                    }
                } catch (\Exception $e) {
                    Log::warning('ส่ง Notification หนี้ MLM ล้มเหลว', ['debt_id' => $debt->id]);
                }
            }

            Log::info('Single MLM commission clawback', $result);

            return $result;
        });
    }

    /**
     * ดึงรายการ Clawback ทั้งหมด
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getClawbacks(array $filters = [], int $perPage = 20)
    {
        $query = WalletDebt::with(['user'])
            ->where('source_type', 'MlmClawback');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }
}
