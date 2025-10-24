<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Create wallet for user
     */
    public function createWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'is_active' => true,
            ]
        );
    }

    /**
     * Request withdrawal
     */
    public function requestWithdrawal(User $user, float $amount, array $bankDetails): Withdrawal
    {
        $wallet = $user->wallet;

        if (!$wallet || !$wallet->hasSufficientBalance($amount)) {
            throw new \Exception('Insufficient balance');
        }

        // Minimum withdrawal amount
        if ($amount < 100) {
            throw new \Exception('Minimum withdrawal amount is 100 THB');
        }

        return DB::transaction(function () use ($user, $wallet, $amount, $bankDetails) {
            // Calculate fee (example: 2% or 10 THB, whichever is higher)
            $fee = max(10, $amount * 0.02);
            $netAmount = $amount - $fee;

            // Deduct from wallet
            $wallet->debit(
                $amount,
                'withdrawal',
                "Withdrawal request #{$this->generateWithdrawalId()}",
                null
            );

            // Create withdrawal request
            return Withdrawal::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'withdrawal_id' => $this->generateWithdrawalId(),
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'method' => $bankDetails['method'] ?? 'bank_transfer',
                'bank_name' => $bankDetails['bank_name'] ?? null,
                'account_number' => $bankDetails['account_number'] ?? null,
                'account_name' => $bankDetails['account_name'] ?? null,
                'promptpay_number' => $bankDetails['promptpay_number'] ?? null,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Approve withdrawal
     */
    public function approveWithdrawal(Withdrawal $withdrawal, User $admin): bool
    {
        if ($withdrawal->status !== 'pending') {
            throw new \Exception('Withdrawal is not in pending status');
        }

        return DB::transaction(function () use ($withdrawal, $admin) {
            $withdrawal->update([
                'status' => 'completed',
                'processed_at' => now(),
                'processed_by' => $admin->id,
            ]);

            return true;
        });
    }

    /**
     * Reject withdrawal
     */
    public function rejectWithdrawal(Withdrawal $withdrawal, User $admin, string $reason): bool
    {
        if ($withdrawal->status !== 'pending') {
            throw new \Exception('Withdrawal is not in pending status');
        }

        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            // Refund to wallet
            $wallet = $withdrawal->wallet;
            $wallet->credit(
                $withdrawal->amount,
                'refund',
                "Withdrawal rejected: {$reason}",
                $withdrawal
            );

            $withdrawal->update([
                'status' => 'rejected',
                'processed_at' => now(),
                'processed_by' => $admin->id,
                'admin_notes' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Get wallet balance
     */
    public function getBalance(User $user): float
    {
        $wallet = $user->wallet;
        return $wallet ? $wallet->balance : 0;
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(User $user, int $limit = 50)
    {
        $wallet = $user->wallet;

        if (!$wallet) {
            return collect([]);
        }

        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate withdrawal ID
     */
    protected function generateWithdrawalId(): string
    {
        return 'WD-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
}
