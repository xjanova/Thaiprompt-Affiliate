<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class WalletService
{
    /**
     * Create a new wallet for user
     */
    public function createWallet(User $user, string $currency = 'THB'): Wallet
    {
        try {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'currency' => $currency,
                'balance' => 0.00,
                'status' => 'active',
            ]);

            $this->logAction($wallet, 'settings_changed', 'Wallet created', 'info');

            return $wallet;
        } catch (Exception $e) {
            Log::error('Failed to create wallet: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get or create wallet for user
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        $wallet = $user->wallet ?? Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            $wallet = $this->createWallet($user);
        }

        return $wallet;
    }

    /**
     * Deposit money to wallet
     */
    public function deposit(
        Wallet $wallet,
        float $amount,
        string $description = 'Deposit',
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = []
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        if (!$wallet->isActive()) {
            throw new Exception('Wallet is not active');
        }

        return DB::transaction(function () use ($wallet, $amount, $description, $referenceType, $referenceId, $metadata) {
            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            // Create transaction
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => 'completed',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            // Update wallet balance
            $wallet->update([
                'balance' => $balanceAfter,
                'total_income' => $wallet->total_income + $amount,
                'last_transaction_at' => now(),
            ]);

            // Log action
            $this->logAction(
                $wallet,
                'transaction_success',
                "Deposit of {$amount} {$wallet->currency}",
                'info',
                ['transaction_id' => $transaction->id]
            );

            return $transaction;
        });
    }

    /**
     * Withdraw money from wallet
     */
    public function withdraw(
        Wallet $wallet,
        float $amount,
        string $pin,
        string $description = 'Withdrawal',
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = []
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        if (!$wallet->isActive()) {
            throw new Exception('Wallet is not active');
        }

        if ($wallet->balance < $amount) {
            $this->logAction($wallet, 'transaction_failed', 'Insufficient balance', 'warning');
            throw new Exception('Insufficient balance');
        }

        // Verify PIN
        if ($wallet->hasPIN() && !$wallet->verifyPIN($pin)) {
            $wallet->incrementFailedAttempts();
            $this->logAction($wallet, 'pin_failed', 'Invalid PIN attempt', 'warning');
            throw new Exception('Invalid PIN');
        }

        $wallet->resetFailedAttempts();

        return DB::transaction(function () use ($wallet, $amount, $description, $referenceType, $referenceId, $metadata) {
            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            // Create transaction
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => 'completed',
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            // Update wallet balance
            $wallet->update([
                'balance' => $balanceAfter,
                'total_expense' => $wallet->total_expense + $amount,
                'last_transaction_at' => now(),
            ]);

            // Log action
            $this->logAction(
                $wallet,
                'transaction_success',
                "Withdrawal of {$amount} {$wallet->currency}",
                'info',
                ['transaction_id' => $transaction->id]
            );

            return $transaction;
        });
    }

    /**
     * Transfer money between wallets
     */
    public function transfer(
        Wallet $fromWallet,
        Wallet $toWallet,
        float $amount,
        string $pin,
        string $description = 'Transfer'
    ): array {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }

        if (!$fromWallet->isActive() || !$toWallet->isActive()) {
            throw new Exception('One or both wallets are not active');
        }

        if ($fromWallet->id === $toWallet->id) {
            throw new Exception('Cannot transfer to the same wallet');
        }

        if ($fromWallet->balance < $amount) {
            $this->logAction($fromWallet, 'transaction_failed', 'Insufficient balance for transfer', 'warning');
            throw new Exception('Insufficient balance');
        }

        // Verify PIN
        if ($fromWallet->hasPIN() && !$fromWallet->verifyPIN($pin)) {
            $fromWallet->incrementFailedAttempts();
            $this->logAction($fromWallet, 'pin_failed', 'Invalid PIN attempt for transfer', 'warning');
            throw new Exception('Invalid PIN');
        }

        $fromWallet->resetFailedAttempts();

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description) {
            // Deduct from sender
            $fromBalanceBefore = $fromWallet->balance;
            $fromBalanceAfter = $fromBalanceBefore - $amount;

            $outTransaction = WalletTransaction::create([
                'wallet_id' => $fromWallet->id,
                'user_id' => $fromWallet->user_id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'balance_before' => $fromBalanceBefore,
                'balance_after' => $fromBalanceAfter,
                'currency' => $fromWallet->currency,
                'description' => $description,
                'related_wallet_id' => $toWallet->id,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $fromWallet->update([
                'balance' => $fromBalanceAfter,
                'total_expense' => $fromWallet->total_expense + $amount,
                'last_transaction_at' => now(),
            ]);

            // Add to receiver
            $toBalanceBefore = $toWallet->balance;
            $toBalanceAfter = $toBalanceBefore + $amount;

            $inTransaction = WalletTransaction::create([
                'wallet_id' => $toWallet->id,
                'user_id' => $toWallet->user_id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $toBalanceBefore,
                'balance_after' => $toBalanceAfter,
                'currency' => $toWallet->currency,
                'description' => $description,
                'related_wallet_id' => $fromWallet->id,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $toWallet->update([
                'balance' => $toBalanceAfter,
                'total_income' => $toWallet->total_income + $amount,
                'last_transaction_at' => now(),
            ]);

            // Log actions
            $this->logAction($fromWallet, 'transaction_success', "Transfer out of {$amount} {$fromWallet->currency}", 'info');
            $this->logAction($toWallet, 'transaction_success', "Transfer in of {$amount} {$toWallet->currency}", 'info');

            return [
                'out_transaction' => $outTransaction,
                'in_transaction' => $inTransaction,
            ];
        });
    }

    /**
     * Add commission to wallet
     */
    public function addCommission(
        Wallet $wallet,
        float $amount,
        int $commissionId,
        string $description = 'Commission'
    ): WalletTransaction {
        return $this->deposit(
            $wallet,
            $amount,
            $description,
            'commission',
            $commissionId,
            ['type' => 'affiliate_commission']
        );
    }

    /**
     * Log wallet action
     */
    public function logAction(
        Wallet $wallet,
        string $action,
        string $description,
        string $severity = 'info',
        array $metadata = []
    ): WalletLog {
        return WalletLog::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'action' => $action,
            'description' => $description,
            'severity' => $severity,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get wallet statistics
     */
    public function getWalletStatistics(Wallet $wallet): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        return [
            'balance' => $wallet->balance,
            'total_income' => $wallet->total_income,
            'total_expense' => $wallet->total_expense,
            'transactions_count' => $wallet->transactions()->count(),
            'last_30_days_income' => $wallet->transactions()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->whereIn('type', ['deposit', 'transfer_in', 'commission', 'bonus'])
                ->sum('amount'),
            'last_30_days_expense' => $wallet->transactions()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->whereIn('type', ['withdrawal', 'transfer_out', 'fee'])
                ->sum('amount'),
            'last_transaction' => $wallet->transactions()->latest()->first(),
        ];
    }

    /**
     * Get all wallets for admin
     */
    public function getAllWallets(array $filters = [], int $perPage = 20)
    {
        $query = Wallet::with(['user', 'transactions' => function($q) {
            $q->latest()->limit(5);
        }]);

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by balance
        if (!empty($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        if (!empty($filters['max_balance'])) {
            $query->where('balance', '<=', $filters['max_balance']);
        }

        // Search by wallet address or user name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('wallet_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get system-wide wallet statistics
     */
    public function getSystemStatistics(): array
    {
        $totalWallets = Wallet::count();
        $activeWallets = Wallet::where('status', 'active')->count();
        $totalBalance = Wallet::sum('balance');
        $totalIncome = Wallet::sum('total_income');
        $totalExpense = Wallet::sum('total_expense');

        $today = today();
        $todayTransactions = WalletTransaction::whereDate('created_at', $today)->count();
        $todayVolume = WalletTransaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('amount');

        $last30Days = now()->subDays(30);
        $monthlyTransactions = WalletTransaction::where('created_at', '>=', $last30Days)->count();
        $monthlyVolume = WalletTransaction::where('created_at', '>=', $last30Days)
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'total_wallets' => $totalWallets,
            'active_wallets' => $activeWallets,
            'suspended_wallets' => Wallet::where('status', 'suspended')->count(),
            'locked_wallets' => Wallet::where('status', 'locked')->count(),
            'total_balance' => $totalBalance,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'today_transactions' => $todayTransactions,
            'today_volume' => $todayVolume,
            'monthly_transactions' => $monthlyTransactions,
            'monthly_volume' => $monthlyVolume,
            'average_balance' => $totalWallets > 0 ? $totalBalance / $totalWallets : 0,
        ];
    }

    /**
     * Transfer with fee calculation
     */
    public function transferWithFee(
        Wallet $fromWallet,
        Wallet $toWallet,
        float $amount,
        string $pin,
        string $description = 'Transfer'
    ): array {
        // Calculate transfer fee
        $fee = \App\Models\WalletSetting::calculateTransferFee($amount);
        $totalAmount = $amount + $fee;

        // Validate amount
        $errors = \App\Models\WalletSetting::validateTransferAmount($amount);
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        if ($fromWallet->balance < $totalAmount) {
            throw new Exception('ยอดเงินไม่เพียงพอ (รวมค่าธรรมเนียม ' . number_format($fee, 2) . ' บาท)');
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $fee, $totalAmount, $pin, $description) {
            // Transfer main amount
            $result = $this->transfer($fromWallet, $toWallet, $amount, $pin, $description);

            // Deduct fee if applicable
            if ($fee > 0) {
                $feeTransaction = $this->withdraw(
                    $fromWallet,
                    $fee,
                    $pin,
                    'ค่าธรรมเนียมการโอน',
                    'transfer_fee',
                    $result['out_transaction']->id
                );

                $result['fee_transaction'] = $feeTransaction;
                $result['total_amount'] = $totalAmount;
                $result['fee'] = $fee;
            }

            return $result;
        });
    }

    /**
     * Refund to wallet (admin action)
     */
    public function refund(
        Wallet $wallet,
        float $amount,
        string $reason,
        User $admin,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new Exception('Refund amount must be greater than 0');
        }

        return $this->deposit(
            $wallet,
            $amount,
            "คืนเงิน: {$reason}",
            $referenceType ?? 'admin_refund',
            $referenceId ?? $admin->id,
            [
                'refunded_by' => $admin->id,
                'reason' => $reason,
                'type' => 'refund',
            ]
        );
    }

    /**
     * Rollback transaction (admin action)
     */
    public function rollbackTransaction(
        WalletTransaction $transaction,
        string $reason,
        User $admin
    ): WalletTransaction {
        if ($transaction->status !== 'completed') {
            throw new Exception('Can only rollback completed transactions');
        }

        $wallet = $transaction->wallet;
        $amount = $transaction->amount;

        // For deposit/income transactions, deduct the amount
        if (in_array($transaction->type, ['deposit', 'transfer_in', 'commission', 'refund', 'bonus', 'cashback'])) {
            if ($wallet->balance < $amount) {
                throw new Exception('Insufficient balance to rollback this transaction');
            }

            return DB::transaction(function () use ($wallet, $amount, $reason, $admin, $transaction) {
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $amount;

                $rollbackTx = WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'type' => 'fee',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'currency' => $wallet->currency,
                    'description' => "Rollback: {$reason} (ธุรกรรมเดิม: {$transaction->transaction_id})",
                    'reference_type' => 'rollback',
                    'reference_id' => $transaction->id,
                    'status' => 'completed',
                    'metadata' => [
                        'rollback_by' => $admin->id,
                        'reason' => $reason,
                        'original_transaction_id' => $transaction->id,
                    ],
                    'completed_at' => now(),
                ]);

                $wallet->update([
                    'balance' => $balanceAfter,
                    'total_expense' => $wallet->total_expense + $amount,
                    'last_transaction_at' => now(),
                ]);

                $this->logAction(
                    $wallet,
                    'transaction_rollback',
                    "Transaction rolled back by admin: {$reason}",
                    'warning',
                    ['transaction_id' => $rollbackTx->id, 'admin_id' => $admin->id]
                );

                return $rollbackTx;
            });
        } else {
            // For withdrawal/expense transactions, add the amount back
            return $this->deposit(
                $wallet,
                $amount,
                "Rollback: {$reason} (ธุรกรรมเดิม: {$transaction->transaction_id})",
                'rollback',
                $transaction->id,
                [
                    'rollback_by' => $admin->id,
                    'reason' => $reason,
                    'original_transaction_id' => $transaction->id,
                ]
            );
        }
    }

    /**
     * Adjust wallet balance (admin only)
     */
    public function adjustBalance(
        Wallet $wallet,
        float $amount,
        string $reason,
        User $admin
    ): WalletTransaction {
        $type = $amount > 0 ? 'deposit' : 'withdrawal';
        $absAmount = abs($amount);

        if ($amount > 0) {
            return $this->deposit(
                $wallet,
                $absAmount,
                "การปรับยอดโดยแอดมิน: {$reason}",
                'admin_adjustment',
                $admin->id,
                ['adjusted_by' => $admin->id, 'reason' => $reason]
            );
        } else {
            return DB::transaction(function () use ($wallet, $absAmount, $reason, $admin) {
                $balanceBefore = $wallet->balance;
                $balanceAfter = $balanceBefore - $absAmount;

                if ($balanceAfter < 0) {
                    throw new Exception('Cannot adjust balance below zero');
                }

                $transaction = WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'type' => 'fee',
                    'amount' => $absAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'currency' => $wallet->currency,
                    'description' => "การปรับยอดโดยแอดมิน: {$reason}",
                    'reference_type' => 'admin_adjustment',
                    'reference_id' => $admin->id,
                    'status' => 'completed',
                    'metadata' => ['adjusted_by' => $admin->id, 'reason' => $reason],
                    'completed_at' => now(),
                ]);

                $wallet->update([
                    'balance' => $balanceAfter,
                    'total_expense' => $wallet->total_expense + $absAmount,
                    'last_transaction_at' => now(),
                ]);

                $this->logAction(
                    $wallet,
                    'transaction_success',
                    "Balance adjusted by admin: {$reason}",
                    'warning',
                    ['transaction_id' => $transaction->id, 'admin_id' => $admin->id]
                );

                return $transaction;
            });
        }
    }

    /**
     * โอนเงินระหว่างกระเป๋าโดย Admin (ไม่ต้องใช้ PIN)
     *
     * @param Wallet $fromWallet กระเป๋าต้นทาง
     * @param Wallet $toWallet กระเป๋าปลายทาง
     * @param float $amount จำนวนเงิน
     * @param string $reason เหตุผลในการโอน
     * @param User $admin แอดมินที่ทำรายการ
     * @return array
     * @throws Exception
     */
    public function adminTransfer(
        Wallet $fromWallet,
        Wallet $toWallet,
        float $amount,
        string $reason,
        User $admin
    ): array {
        if ($amount <= 0) {
            throw new Exception('จำนวนเงินต้องมากกว่า 0');
        }

        if ($fromWallet->id === $toWallet->id) {
            throw new Exception('ไม่สามารถโอนเงินให้ตัวเองได้');
        }

        if ($fromWallet->balance < $amount) {
            throw new Exception('ยอดเงินในกระเป๋าต้นทางไม่เพียงพอ');
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $reason, $admin) {
            // หักเงินจากกระเป๋าต้นทาง
            $fromBalanceBefore = $fromWallet->balance;
            $fromBalanceAfter = $fromBalanceBefore - $amount;

            $outTransaction = WalletTransaction::create([
                'wallet_id' => $fromWallet->id,
                'user_id' => $fromWallet->user_id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'balance_before' => $fromBalanceBefore,
                'balance_after' => $fromBalanceAfter,
                'currency' => $fromWallet->currency,
                'description' => "โอนเงินโดยแอดมิน: {$reason}",
                'related_wallet_id' => $toWallet->id,
                'reference_type' => 'admin_transfer',
                'reference_id' => $admin->id,
                'status' => 'completed',
                'metadata' => [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'reason' => $reason,
                ],
                'completed_at' => now(),
            ]);

            $fromWallet->update([
                'balance' => $fromBalanceAfter,
                'total_expense' => $fromWallet->total_expense + $amount,
                'last_transaction_at' => now(),
            ]);

            // เพิ่มเงินให้กระเป๋าปลายทาง
            $toBalanceBefore = $toWallet->balance;
            $toBalanceAfter = $toBalanceBefore + $amount;

            $inTransaction = WalletTransaction::create([
                'wallet_id' => $toWallet->id,
                'user_id' => $toWallet->user_id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $toBalanceBefore,
                'balance_after' => $toBalanceAfter,
                'currency' => $toWallet->currency,
                'description' => "รับโอนเงินโดยแอดมิน: {$reason}",
                'related_wallet_id' => $fromWallet->id,
                'reference_type' => 'admin_transfer',
                'reference_id' => $admin->id,
                'status' => 'completed',
                'metadata' => [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'reason' => $reason,
                ],
                'completed_at' => now(),
            ]);

            $toWallet->update([
                'balance' => $toBalanceAfter,
                'total_income' => $toWallet->total_income + $amount,
                'last_transaction_at' => now(),
            ]);

            // บันทึก log
            $this->logAction(
                $fromWallet,
                'transaction_success',
                "Admin transfer out: {$amount} {$fromWallet->currency} - {$reason}",
                'info',
                ['transaction_id' => $outTransaction->id, 'admin_id' => $admin->id]
            );

            $this->logAction(
                $toWallet,
                'transaction_success',
                "Admin transfer in: {$amount} {$toWallet->currency} - {$reason}",
                'info',
                ['transaction_id' => $inTransaction->id, 'admin_id' => $admin->id]
            );

            return [
                'out_transaction' => $outTransaction,
                'in_transaction' => $inTransaction,
                'from_wallet' => $fromWallet->fresh(),
                'to_wallet' => $toWallet->fresh(),
            ];
        });
    }

    /**
     * ระงับการใช้งานกระเป๋า (Suspend)
     *
     * @param Wallet $wallet กระเป๋าที่ต้องการระงับ
     * @param string $reason เหตุผล
     * @param User $admin แอดมินที่ทำรายการ
     * @return Wallet
     */
    public function suspendWallet(Wallet $wallet, string $reason, User $admin): Wallet
    {
        $wallet->update([
            'status' => 'suspended',
            'locked_until' => null,
        ]);

        $this->logAction(
            $wallet,
            'wallet_locked',
            "กระเป๋าถูกระงับโดยแอดมิน: {$reason}",
            'critical',
            ['admin_id' => $admin->id, 'reason' => $reason]
        );

        return $wallet->fresh();
    }

    /**
     * ยกเลิกการระงับกระเป๋า (Unsuspend)
     *
     * @param Wallet $wallet กระเป๋าที่ต้องการยกเลิกการระงับ
     * @param string $reason เหตุผล
     * @param User $admin แอดมินที่ทำรายการ
     * @return Wallet
     */
    public function unsuspendWallet(Wallet $wallet, string $reason, User $admin): Wallet
    {
        $wallet->update([
            'status' => 'active',
            'locked_until' => null,
            'failed_attempts' => 0,
        ]);

        $this->logAction(
            $wallet,
            'wallet_unlocked',
            "ยกเลิกการระงับกระเป๋าโดยแอดมิน: {$reason}",
            'info',
            ['admin_id' => $admin->id, 'reason' => $reason]
        );

        return $wallet->fresh();
    }

    /**
     * ดึงธุรกรรมทั้งหมดในระบบ
     *
     * @param array $filters ตัวกรอง
     * @param int $perPage จำนวนต่อหน้า
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllTransactions(array $filters = [], int $perPage = 20)
    {
        $query = WalletTransaction::with(['wallet.user', 'relatedWallet.user']);

        // กรองตาม wallet_id
        if (!empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        // กรองตาม user_id
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // กรองตามประเภท
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // กรองตามสถานะ
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // กรองตามวันที่
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // กรองตามจำนวนเงิน
        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (!empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // ค้นหา
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('wallet.user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ดึง logs ทั้งหมดในระบบ
     *
     * @param array $filters ตัวกรอง
     * @param int $perPage จำนวนต่อหน้า
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllLogs(array $filters = [], int $perPage = 20)
    {
        $query = WalletLog::with(['wallet.user']);

        // กรองตาม wallet_id
        if (!empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        // กรองตาม action
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // กรองตาม severity
        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        // กรองตามวันที่
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // ค้นหา
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('wallet.user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ดึงรายการกระเป๋าสำหรับ dropdown
     *
     * @param string|null $exclude Wallet ID ที่ต้องการยกเว้น
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWalletsForDropdown(?int $excludeId = null)
    {
        $query = Wallet::with('user:id,name,email')
            ->where('status', 'active')
            ->select(['id', 'user_id', 'wallet_address', 'balance', 'currency']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
