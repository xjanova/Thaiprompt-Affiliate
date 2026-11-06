<?php

namespace App\Services;

use App\Models\EarningsLedger;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletDebt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DebtCollectionService
 *
 * จัดการระบบหนี้/ติดลบ
 * - สร้างหนี้เมื่อ Refund หลังผู้ขายถอนเงินไปแล้ว
 * - หักหนี้อัตโนมัติจากรายได้ใหม่
 */
class DebtCollectionService
{
    /** สัดส่วนของรายได้ครั้งถัดไปที่ใช้หักหนี้ (เจ้าของตัดสิน M7 = 100%) */
    public const INCOME_COLLECTION_RATE = 1.0;

    /** reference_type ของรายการหักหนี้จากกระเป๋า (เงินเข้ากระเป๋าแล้วถูกหักทันที) */
    public const WALLET_REF_DEBT_COLLECTION = 'WalletDebtCollection';

    /**
     * หนี้ที่ระบบต้นทางเก็บเอง (ไม่บล็อกการถอน/โอน และไม่ถูกหักจากรายได้อื่นเข้ากองทุนคืนเงิน)
     *
     * ค่า GP / ต้นทุนไรเดอร์ตลาดสด: ออกแบบให้ร้านค้าขายต่อได้ระหว่างมีหนี้ (เพดาน fresh_market.max_seller_gp_debt)
     * และหักจากยอดโอนรอบถัดไปของร้านเอง (เงินต้องเข้ากระเป๋า GP ไม่ใช่ refund_pool)
     * ⚠️ ขอให้เจ้าของยืนยัน: M7 เขียนว่า "ทุกระบบ" — ถ้าต้องการให้หนี้ตลาดสดบล็อกการถอนด้วย ให้ลบออกจากรายการนี้
     */
    public const SELF_COLLECTED_DEBT_SOURCES = [
        FreshMarketService::DEBT_SOURCE_GP,
        FreshMarketService::DEBT_SOURCE_RIDER_COST,
    ];

    /**
     * query หนี้ที่ยังค้างซึ่งบล็อกการถอน/โอน และถูกหักจากรายได้ครั้งถัดไป (M7)
     */
    public static function blockingDebtQuery(int $userId)
    {
        return WalletDebt::forUser($userId)
            ->active()
            ->where('remaining_amount', '>', 0)
            ->where(fn ($q) => $q->whereNull('source_type')->orWhereNotIn('source_type', self::SELF_COLLECTED_DEBT_SOURCES));
    }

    /**
     * มีหนี้ค้างอยู่ไหม (M7: ระหว่างมีหนี้ห้ามถอน/โอนเงินออก)
     */
    public static function hasActiveDebt(int $userId): bool
    {
        return self::blockingDebtQuery($userId)->exists();
    }

    /**
     * ยอดหนี้ที่บล็อกการถอน/โอน
     */
    public static function blockingDebtTotal(int $userId): float
    {
        return round((float) self::blockingDebtQuery($userId)->sum('remaining_amount'), 2);
    }

    /**
     * ข้อความไทยเมื่อถอนไม่ได้เพราะมีหนี้ (null = ไม่มีหนี้)
     */
    public static function withdrawalBlockedMessage(int $userId): ?string
    {
        $total = self::blockingDebtTotal($userId);
        if ($total <= 0) {
            return null;
        }

        return 'ยังถอนหรือโอนเงินออกไม่ได้ เนื่องจากมียอดค้างชำระ '.number_format($total, 2)
            .' บาท (เช่น หนี้จากการคืนเงิน/ดึงคืนค่าคอม) ระบบจะหักจากรายได้ครั้งถัดไปโดยอัตโนมัติ เมื่อชำระครบจึงทำรายการได้';
    }

    /**
     * 🧾 M7: หักหนี้จากเงินที่เพิ่งเข้ากระเป๋า (รายได้ครั้งถัดไป) — เรียกใน transaction เดียวกับการจ่ายเงินเข้ากระเป๋า
     *
     * - หักได้ไม่เกิน min(หนี้คงค้าง, เงินที่เพิ่งเข้า × 100%, ยอดในกระเป๋า) → กระเป๋าไม่มีวันติดลบ
     * - เงินที่หักได้เข้ากองทุนคืนเงิน (refund_pool) แบบเดียวกับ SellerPayoutService — หนี้เกิดจากเงินที่แพลตฟอร์มออกไปก่อน
     * - หักหนี้ตาม priority (WalletDebt::deductAllDebts ล็อกแถวหนี้)
     *
     * @return float ยอดที่หักได้จริง
     */
    public function collectFromWalletIncome(int $userId, float $incomeAmount, string $referenceType, int $referenceId, string $label): float
    {
        $incomeAmount = round($incomeAmount, 2);
        if ($incomeAmount <= 0 || ! self::hasActiveDebt($userId)) {
            return 0.0;
        }

        return DB::transaction(function () use ($userId, $incomeAmount, $referenceType, $referenceId, $label) {
            $debts = self::blockingDebtQuery($userId)->byPriority()->lockForUpdate()->get();
            $totalDebt = round((float) $debts->sum('remaining_amount'), 2);

            $wallet = \App\Models\Wallet::where('user_id', $userId)->lockForUpdate()->first();
            if (! $wallet || $totalDebt <= 0 || ! $wallet->isActive()) {
                return 0.0;
            }

            $take = round(min($totalDebt, $incomeAmount * self::INCOME_COLLECTION_RATE, max(0.0, (float) $wallet->balance)), 2);
            if ($take <= 0) {
                return 0.0;
            }

            app(WalletService::class)->deductForService(
                $wallet,
                $take,
                mb_substr("หักหนี้ค้างชำระจากรายได้ ({$label})", 0, 500),
                self::WALLET_REF_DEBT_COLLECTION,
                (int) $debts->first()->id,
                ['income_reference_type' => $referenceType, 'income_reference_id' => $referenceId, 'debt_ids' => $debts->pluck('id')->all()]
            );

            // หักเฉพาะหนี้ชุดเดียวกับที่ล็อกไว้ (ไม่แตะหนี้ที่ระบบต้นทางเก็บเอง)
            $collected = 0.0;
            $left = $take;
            foreach ($debts as $debt) {
                if ($left <= 0) {
                    break;
                }
                $got = round((float) $debt->deduct($left), 2);
                $collected = round($collected + $got, 2);
                $left = round($left - $got, 2);
            }

            if ($collected > 0) {
                $tx = \App\Models\PlatformWallet::getRefundPoolWallet()->addFunds(
                    $collected,
                    'debt_collected_from_income',
                    $referenceType,
                    $referenceId,
                    ['user_id' => $userId, 'label' => $label]
                );
                $tx->update([
                    'related_user_id' => $userId,
                    'description' => mb_substr("หักหนี้จากรายได้ ({$label})", 0, 255),
                ]);

                Log::info('Debt collected from wallet income', [
                    'user_id' => $userId,
                    'income' => $incomeAmount,
                    'collected' => $collected,
                    'reference' => [$referenceType, $referenceId],
                ]);
            }

            return $collected;
        });
    }

    /**
     * สร้างหนี้จาก Refund
     *
     * @param  string  $sourceType  เช่น 'Refund', 'Order'
     * @param  int|null  $createdBy  Admin ID
     */
    public function createDebtFromRefund(
        int $userId,
        float $amount,
        string $sourceType,
        int $sourceId,
        string $reason,
        ?int $createdBy = null
    ): ?WalletDebt {
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($userId, $amount, $sourceType, $sourceId, $reason, $createdBy) {
            // ตรวจสอบว่ามีเงินในกระเป๋าพอไหม
            $user = User::find($userId);
            $availableBalance = $this->getAvailableBalance($userId);

            // ถ้ามีเงินพอ → หักจาก wallet โดยตรง
            if ($availableBalance >= $amount) {
                $this->deductFromWallet($user, $amount, $sourceType, $sourceId, $reason);

                return null; // ไม่ต้องสร้างหนี้
            }

            // ถ้าเงินไม่พอ → หักเท่าที่มี แล้วสร้างหนี้ส่วนที่เหลือ
            $deductedFromWallet = 0;
            if ($availableBalance > 0) {
                $this->deductFromWallet($user, $availableBalance, $sourceType, $sourceId, $reason);
                $deductedFromWallet = $availableBalance;
            }

            // ส่วนที่เหลือ → สร้างเป็นหนี้
            $debtAmount = $amount - $deductedFromWallet;

            $debt = WalletDebt::createDebt(
                $userId,
                $debtAmount,
                $sourceType,
                $sourceId,
                $reason,
                $createdBy,
                1, // priority
                [
                    'original_refund_amount' => $amount,
                    'deducted_from_wallet' => $deductedFromWallet,
                ]
            );

            Log::info('Debt created from refund', [
                'debt_id' => $debt->id,
                'user_id' => $userId,
                'amount' => $debtAmount,
                'source' => "{$sourceType}#{$sourceId}",
            ]);

            // ส่ง Notification แจ้งหนี้ใหม่
            try {
                if ($user) {
                    app(NotificationService::class)->notifyDebtCreated($user, $debt);
                }
            } catch (\Exception $e) {
                Log::warning('ส่ง Notification หนี้ใหม่ล้มเหลว', ['debt_id' => $debt->id, 'error' => $e->getMessage()]);
            }

            return $debt;
        });
    }

    /**
     * ดึงยอดเงินที่ถอนได้
     */
    protected function getAvailableBalance(int $userId): float
    {
        $user = User::find($userId);
        if (! $user || ! $user->wallet) {
            return 0;
        }

        return max(0, $user->wallet->balance ?? 0);
    }

    /**
     * หักเงินจาก Wallet
     */
    protected function deductFromWallet(User $user, float $amount, string $sourceType, int $sourceId, string $reason): void
    {
        if (! $user->wallet) {
            return;
        }

        $user->wallet->decrement('balance', $amount);

        // บันทึก transaction
        if (method_exists($user->wallet, 'transactions')) {
            $user->wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => 'refund_deduction',
                'amount' => -$amount,
                'balance_after' => $user->wallet->fresh()->balance,
                'description' => $reason,
                'reference_type' => $sourceType,
                'reference_id' => $sourceId,
            ]);
        }
    }

    /**
     * หักหนี้จากรายได้ใหม่
     */
    public function collectDebtFromEarning(int $userId, float $earningAmount, ?int $earningId = null): array
    {
        // ตรวจสอบว่ามีหนี้ไหม
        $totalDebt = WalletDebt::getTotalDebtForUser($userId);

        if ($totalDebt <= 0) {
            return [
                'has_debt' => false,
                'total_debt' => 0,
                'collected' => 0,
                'remaining_earning' => $earningAmount,
            ];
        }

        // 🧾 M7: หักหนี้จากรายได้ครั้งถัดไป 100% (เดิม 50% — หนี้ค้างนานและผู้ใช้ถอนส่วนที่เหลือออกไปได้)
        $maxDeduction = $earningAmount * self::INCOME_COLLECTION_RATE;
        $toDeduct = min($totalDebt, $maxDeduction);

        $result = WalletDebt::deductAllDebts($userId, $toDeduct, $earningId);

        // ส่ง Notification แจ้งว่าถูกหักหนี้จากรายได้
        if ($result['total_deducted'] > 0) {
            try {
                $user = User::find($userId);
                if ($user) {
                    $remainingDebt = WalletDebt::getTotalDebtForUser($userId);
                    app(NotificationService::class)->notifyDebtCollected($user, $result['total_deducted'], $remainingDebt);
                }
            } catch (\Exception $e) {
                Log::warning('ส่ง Notification หักหนี้ล้มเหลว', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        }

        return [
            'has_debt' => true,
            'total_debt' => $totalDebt,
            'collected' => $result['total_deducted'],
            'remaining_earning' => $earningAmount - $result['total_deducted'],
        ];
    }

    /**
     * Process Refund และสร้างหนี้ (ถ้าจำเป็น)
     */
    public function processRefund(Order $order, float $refundAmount, ?int $adminId = null): array
    {
        return DB::transaction(function () use ($order, $refundAmount, $adminId) {
            $results = [
                'order_id' => $order->id,
                'refund_amount' => $refundAmount,
                'sellers_affected' => [],
                'debts_created' => [],
            ];

            // ดึง earnings ที่เกี่ยวกับ order นี้
            $earnings = EarningsLedger::where('source_type', 'Order')
                ->where('source_id', $order->id)
                ->get();

            foreach ($earnings as $earning) {
                $sellerId = $earning->user_id;
                $sellerShare = $earning->net_amount;

                // ถ้า earning ยังไม่ได้จ่าย → ยกเลิกได้เลย
                if ($earning->status === EarningsLedger::STATUS_PENDING) {
                    $earning->update([
                        'status' => EarningsLedger::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                        'cancel_reason' => 'Order refunded',
                    ]);

                    $results['sellers_affected'][] = [
                        'seller_id' => $sellerId,
                        'action' => 'earning_cancelled',
                        'amount' => $sellerShare,
                    ];

                    continue;
                }

                // ถ้า earning จ่ายแล้ว → ต้องสร้างหนี้
                if (in_array($earning->status, [
                    EarningsLedger::STATUS_AVAILABLE,
                    EarningsLedger::STATUS_PAID,
                ])) {
                    $debt = $this->createDebtFromRefund(
                        $sellerId,
                        $sellerShare,
                        'Refund',
                        $order->id,
                        "Refund จาก Order #{$order->order_number}",
                        $adminId
                    );

                    $results['sellers_affected'][] = [
                        'seller_id' => $sellerId,
                        'action' => $debt ? 'debt_created' : 'deducted_from_wallet',
                        'amount' => $sellerShare,
                    ];

                    if ($debt) {
                        $results['debts_created'][] = $debt->id;
                    }
                }
            }

            Log::info('Refund processed', $results);

            return $results;
        });
    }

    /**
     * ยกเว้นหนี้ (โดย Admin)
     */
    public function waiveDebt(WalletDebt $debt, int $waivedBy, string $reason): WalletDebt
    {
        if ($debt->status !== WalletDebt::STATUS_ACTIVE) {
            throw new \Exception('สามารถยกเว้นได้เฉพาะหนี้ที่ยังค้างชำระ');
        }

        DB::transaction(function () use ($debt, $waivedBy, $reason) {
            $debt->waive($waivedBy, $reason);

            Log::info('Debt waived', [
                'debt_id' => $debt->id,
                'user_id' => $debt->user_id,
                'amount' => $debt->remaining_amount,
                'waived_by' => $waivedBy,
                'reason' => $reason,
            ]);
        });

        // ส่ง Notification แจ้งว่าหนี้ถูกยกเว้น
        try {
            $user = User::find($debt->user_id);
            if ($user) {
                app(NotificationService::class)->notifyDebtWaived($user, $debt->fresh());
            }
        } catch (\Exception $e) {
            Log::warning('ส่ง Notification ยกเว้นหนี้ล้มเหลว', ['debt_id' => $debt->id]);
        }

        return $debt->fresh();
    }

    /**
     * ยกเลิกหนี้
     */
    public function cancelDebt(WalletDebt $debt): WalletDebt
    {
        if ($debt->status !== WalletDebt::STATUS_ACTIVE) {
            throw new \Exception('สามารถยกเลิกได้เฉพาะหนี้ที่ยังค้างชำระ');
        }

        $debt->cancel();

        Log::info('Debt cancelled', [
            'debt_id' => $debt->id,
            'user_id' => $debt->user_id,
        ]);

        return $debt->fresh();
    }

    /**
     * สร้างหนี้ด้วยตนเอง (Admin)
     */
    public function createManualDebt(
        int $userId,
        float $amount,
        string $reason,
        int $createdBy,
        int $priority = 1
    ): WalletDebt {
        if ($amount <= 0) {
            throw new \Exception('จำนวนหนี้ต้องมากกว่า 0');
        }

        $debt = WalletDebt::createDebt(
            $userId,
            $amount,
            'Manual',
            0,
            $reason,
            $createdBy,
            $priority,
            ['created_manually' => true]
        );

        Log::info('Manual debt created', [
            'debt_id' => $debt->id,
            'user_id' => $userId,
            'amount' => $amount,
            'created_by' => $createdBy,
        ]);

        return $debt;
    }

    /**
     * ดึงสถิติหนี้
     */
    public function getDebtStats(array $filters = []): array
    {
        $query = WalletDebt::query();

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

        $activeDebts = (clone $query)->where('status', WalletDebt::STATUS_ACTIVE);
        $paidDebts = (clone $query)->where('status', WalletDebt::STATUS_PAID);
        $waivedDebts = (clone $query)->where('status', WalletDebt::STATUS_WAIVED);

        return [
            'total_debts' => (clone $query)->count(),
            'total_original_amount' => (clone $query)->sum('original_amount'),
            'total_deducted' => (clone $query)->sum('deducted_amount'),
            'total_remaining' => (clone $query)->sum('remaining_amount'),
            'active' => [
                'count' => $activeDebts->count(),
                'amount' => $activeDebts->sum('remaining_amount'),
            ],
            'paid' => [
                'count' => $paidDebts->count(),
                'amount' => $paidDebts->sum('original_amount'),
            ],
            'waived' => [
                'count' => $waivedDebts->count(),
                'amount' => $waivedDebts->sum('remaining_amount'),
            ],
            'collection_rate' => $this->calculateCollectionRate($query),
        ];
    }

    /**
     * คำนวณอัตราการเก็บหนี้
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function calculateCollectionRate($query): float
    {
        $totalOriginal = (clone $query)->sum('original_amount');
        $totalDeducted = (clone $query)->sum('deducted_amount');

        if ($totalOriginal <= 0) {
            return 0;
        }

        return round(($totalDeducted / $totalOriginal) * 100, 2);
    }

    /**
     * ดึงรายการหนี้
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getDebts(array $filters = [], int $perPage = 15)
    {
        $query = WalletDebt::with(['user', 'createdByUser', 'waivedByUser']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->byPriority()->latest()->paginate($perPage);
    }

    /**
     * ดึงหนี้ของ User
     */
    public function getUserDebtSummary(int $userId): array
    {
        $debts = WalletDebt::forUser($userId)->get();
        $activeDebts = $debts->where('status', WalletDebt::STATUS_ACTIVE);

        return [
            'total_debt' => $activeDebts->sum('remaining_amount'),
            'debt_count' => $activeDebts->count(),
            'all_debts' => $debts,
            'has_active_debt' => $activeDebts->count() > 0,
            'oldest_debt_date' => $activeDebts->min('created_at'),
            // 🐛 (2026-10-29) เดิมนับเฉพาะหนี้ที่ชำระครบ — ยอดที่ถูกหักไปบางส่วนแล้วไม่ถูกนับ อัตราชำระบนหน้าเว็บต่ำกว่าจริง
            'total_paid' => $debts->sum('deducted_amount'),
        ];
    }

    /**
     * Batch process - เก็บหนี้จาก pending earnings
     */
    public function batchCollectDebts(): array
    {
        $results = [
            'processed' => 0,
            'collected' => 0,
            'errors' => [],
        ];

        // ดึง users ที่มีหนี้และมี earnings ใหม่
        $usersWithDebt = WalletDebt::active()
            ->select('user_id')
            ->groupBy('user_id')
            ->pluck('user_id');

        foreach ($usersWithDebt as $userId) {
            try {
                // ดึง earnings ที่ available
                $availableEarnings = EarningsLedger::where('user_id', $userId)
                    ->where('status', EarningsLedger::STATUS_AVAILABLE)
                    ->get();

                foreach ($availableEarnings as $earning) {
                    $collectionResult = $this->collectDebtFromEarning(
                        $userId,
                        $earning->net_amount,
                        $earning->id
                    );

                    if ($collectionResult['collected'] > 0) {
                        $results['collected'] += $collectionResult['collected'];

                        // อัพเดท earning
                        $earning->debt_deduction += $collectionResult['collected'];
                        $earning->net_amount = $collectionResult['remaining_earning'];
                        $earning->save();
                    }
                }

                $results['processed']++;

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ];

                Log::error('Batch debt collection failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
