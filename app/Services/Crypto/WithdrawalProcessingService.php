<?php

namespace App\Services\Crypto;

use App\Models\CryptoWithdrawalRequest;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\CryptoCurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalProcessingService
{
    protected BlockchainTransactionService $blockchainService;
    protected CryptoWalletService $walletService;

    public function __construct(
        BlockchainTransactionService $blockchainService,
        CryptoWalletService $walletService
    ) {
        $this->blockchainService = $blockchainService;
        $this->walletService = $walletService;
    }

    /**
     * Process pending withdrawal requests
     */
    public function processPendingWithdrawals(): array
    {
        $results = [
            'processed' => 0,
            'approved' => 0,
            'rejected' => 0,
            'sent' => 0,
            'errors' => 0,
        ];

        // Get pending withdrawal requests
        $withdrawals = CryptoWithdrawalRequest::where('status', 'pending')
            ->with(['user', 'cryptoWallet', 'currency'])
            ->orderBy('created_at', 'asc')
            ->limit(50) // Process in batches
            ->get();

        foreach ($withdrawals as $withdrawal) {
            $results['processed']++;

            try {
                // Auto-approve or send for manual review based on risk
                if ($this->shouldAutoApprove($withdrawal)) {
                    if ($this->approveWithdrawal($withdrawal)) {
                        $results['approved']++;

                        // Execute withdrawal
                        if ($this->executeWithdrawal($withdrawal)) {
                            $results['sent']++;
                        }
                    }
                } else {
                    // Mark for manual review
                    $withdrawal->update([
                        'status' => 'reviewing',
                        'reviewed_at' => now(),
                        'admin_note' => 'Flagged for manual review due to risk score: ' . $withdrawal->risk_score,
                    ]);
                }

            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Withdrawal processing failed', [
                    'withdrawal_id' => $withdrawal->id,
                    'error' => $e->getMessage(),
                ]);

                $withdrawal->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Determine if withdrawal should be auto-approved
     */
    protected function shouldAutoApprove(CryptoWithdrawalRequest $withdrawal): bool
    {
        // Check risk score
        if ($withdrawal->risk_score > 70) {
            return false; // High risk - needs manual review
        }

        // Check amount limits
        $maxAutoApprove = config('crypto.max_auto_approve_amount', 100000); // 100k THB
        if ($withdrawal->amount_thb > $maxAutoApprove) {
            return false; // Large amount - needs manual review
        }

        // Check user verification status
        if (!$withdrawal->user->isKycVerified()) {
            return false; // Unverified user - needs manual review
        }

        // Check for recent suspicious activity
        if ($this->hasRecentSuspiciousActivity($withdrawal->user)) {
            return false;
        }

        return true;
    }

    /**
     * Check for suspicious activity
     */
    protected function hasRecentSuspiciousActivity($user): bool
    {
        // Check for multiple failed withdrawal attempts
        $recentFailures = CryptoWithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentFailures >= 3) {
            return true;
        }

        // Check for rapid withdrawal requests
        $recentRequests = CryptoWithdrawalRequest::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        if ($recentRequests >= 5) {
            return true;
        }

        return false;
    }

    /**
     * Approve withdrawal request
     */
    public function approveWithdrawal(CryptoWithdrawalRequest $withdrawal): bool
    {
        return DB::transaction(function () use ($withdrawal) {
            $wallet = $withdrawal->cryptoWallet;
            $currency = $withdrawal->currency;

            // Check balance
            $balance = $this->walletService->getBalance($wallet, $currency);

            $totalRequired = $withdrawal->amount + $withdrawal->total_fee;

            if ($balance < $totalRequired) {
                $withdrawal->update([
                    'status' => 'failed',
                    'error_message' => 'Insufficient balance',
                ]);
                return false;
            }

            // Lock the withdrawal amount
            $balanceRecord = $wallet->balances()
                ->where('crypto_currency_id', $currency->id)
                ->first();

            if (!$balanceRecord) {
                throw new \Exception('Balance record not found');
            }

            $balanceRecord->decrement('balance', $totalRequired);
            $balanceRecord->increment('locked_balance', $totalRequired);

            // Update withdrawal status
            $withdrawal->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => 'system', // or admin user ID
            ]);

            Log::info('Withdrawal approved', [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'currency' => $currency->code,
            ]);

            return true;
        });
    }

    /**
     * Execute approved withdrawal
     */
    public function executeWithdrawal(CryptoWithdrawalRequest $withdrawal): bool
    {
        try {
            $wallet = $withdrawal->cryptoWallet;
            $currency = $withdrawal->currency;

            // Send blockchain transaction
            $result = $this->blockchainService->sendTransaction(
                $wallet,
                $withdrawal->to_address,
                $withdrawal->amount,
                $currency,
                $withdrawal->network
            );

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Transaction failed');
            }

            // Update withdrawal with transaction hash
            $withdrawal->update([
                'status' => 'processing',
                'tx_hash' => $result['tx_hash'],
                'processed_at' => now(),
            ]);

            // Unlock and remove from locked balance
            $balanceRecord = $wallet->balances()
                ->where('crypto_currency_id', $currency->id)
                ->first();

            if ($balanceRecord) {
                $totalAmount = $withdrawal->amount + $withdrawal->total_fee;
                $balanceRecord->decrement('locked_balance', $totalAmount);
            }

            Log::info('Withdrawal executed', [
                'withdrawal_id' => $withdrawal->id,
                'tx_hash' => $result['tx_hash'],
            ]);

            // Notify user
            $this->notifyUserOfWithdrawal($withdrawal);

            return true;

        } catch (\Exception $e) {
            Log::error('Withdrawal execution failed', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);

            // Unlock balance on failure
            $this->unlockWithdrawalBalance($withdrawal);

            $withdrawal->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reject withdrawal request
     */
    public function rejectWithdrawal(CryptoWithdrawalRequest $withdrawal, string $reason): bool
    {
        return DB::transaction(function () use ($withdrawal, $reason) {
            // Unlock balance if it was locked
            if ($withdrawal->status === 'approved') {
                $this->unlockWithdrawalBalance($withdrawal);
            }

            $withdrawal->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_reason' => $reason,
            ]);

            // Notify user
            $this->notifyUserOfRejection($withdrawal, $reason);

            Log::info('Withdrawal rejected', [
                'withdrawal_id' => $withdrawal->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Unlock withdrawal balance
     */
    protected function unlockWithdrawalBalance(CryptoWithdrawalRequest $withdrawal): void
    {
        $wallet = $withdrawal->cryptoWallet;
        $currency = $withdrawal->currency;

        $balanceRecord = $wallet->balances()
            ->where('crypto_currency_id', $currency->id)
            ->first();

        if ($balanceRecord) {
            $totalAmount = $withdrawal->amount + $withdrawal->total_fee;

            // Return locked balance to available balance
            $balanceRecord->increment('balance', $totalAmount);
            $balanceRecord->decrement('locked_balance', $totalAmount);
        }
    }

    /**
     * Update withdrawal confirmations
     */
    public function updateWithdrawalConfirmations(): array
    {
        $results = [
            'checked' => 0,
            'confirmed' => 0,
            'failed' => 0,
        ];

        $processingWithdrawals = CryptoWithdrawalRequest::where('status', 'processing')
            ->whereNotNull('tx_hash')
            ->with(['currency'])
            ->get();

        foreach ($processingWithdrawals as $withdrawal) {
            $results['checked']++;

            try {
                $status = $this->blockchainService->checkTransactionStatus(
                    $withdrawal->tx_hash,
                    $withdrawal->currency->network
                );

                if ($status['status'] === 'confirmed') {
                    $withdrawal->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'confirmations' => $status['confirmations'] ?? 0,
                    ]);

                    $results['confirmed']++;

                    Log::info('Withdrawal confirmed', [
                        'withdrawal_id' => $withdrawal->id,
                        'tx_hash' => $withdrawal->tx_hash,
                    ]);

                } elseif ($status['status'] === 'failed') {
                    // Transaction failed on blockchain
                    $withdrawal->update([
                        'status' => 'failed',
                        'error_message' => 'Transaction failed on blockchain',
                    ]);

                    // Return balance to user
                    $this->unlockWithdrawalBalance($withdrawal);

                    $results['failed']++;
                }

            } catch (\Exception $e) {
                Log::error('Failed to update withdrawal confirmation', [
                    'withdrawal_id' => $withdrawal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Notify user of withdrawal
     */
    protected function notifyUserOfWithdrawal(CryptoWithdrawalRequest $withdrawal): void
    {
        try {
            $withdrawal->user->notify(
                new \App\Notifications\CryptoWithdrawalProcessed($withdrawal)
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send withdrawal notification', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify user of rejection
     */
    protected function notifyUserOfRejection(CryptoWithdrawalRequest $withdrawal, string $reason): void
    {
        try {
            $withdrawal->user->notify(
                new \App\Notifications\CryptoWithdrawalRejected($withdrawal, $reason)
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send rejection notification', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel withdrawal request
     */
    public function cancelWithdrawal(CryptoWithdrawalRequest $withdrawal): bool
    {
        if (!in_array($withdrawal->status, ['pending', 'approved'])) {
            throw new \Exception('Cannot cancel withdrawal in ' . $withdrawal->status . ' status');
        }

        return DB::transaction(function () use ($withdrawal) {
            // Unlock balance if it was locked
            if ($withdrawal->status === 'approved') {
                $this->unlockWithdrawalBalance($withdrawal);
            }

            $withdrawal->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            Log::info('Withdrawal cancelled', [
                'withdrawal_id' => $withdrawal->id,
            ]);

            return true;
        });
    }

    /**
     * Get withdrawal statistics
     */
    public function getWithdrawalStats(): array
    {
        return [
            'pending' => CryptoWithdrawalRequest::where('status', 'pending')->count(),
            'reviewing' => CryptoWithdrawalRequest::where('status', 'reviewing')->count(),
            'approved' => CryptoWithdrawalRequest::where('status', 'approved')->count(),
            'processing' => CryptoWithdrawalRequest::where('status', 'processing')->count(),
            'completed_24h' => CryptoWithdrawalRequest::where('status', 'completed')
                ->where('completed_at', '>=', now()->subHours(24))
                ->count(),
            'failed_24h' => CryptoWithdrawalRequest::where('status', 'failed')
                ->where('updated_at', '>=', now()->subHours(24))
                ->count(),
        ];
    }
}
