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
}
