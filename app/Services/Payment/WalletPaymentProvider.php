<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletPaymentProvider implements PaymentProviderInterface
{
    /**
     * Validate wallet payment
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // Get user's wallet
        $wallet = Wallet::where('user_id', $transaction->user_id)->first();

        if (!$wallet) {
            throw new Exception('Wallet not found');
        }

        // Check if wallet has enough balance
        if ($wallet->balance < $transaction->amount) {
            throw new Exception('Insufficient wallet balance');
        }

        return true;
    }

    /**
     * Process wallet payment
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        return DB::transaction(function () use ($transaction) {
            $wallet = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();

            if (!$wallet || $wallet->balance < $transaction->amount) {
                throw new Exception('Insufficient wallet balance');
            }

            // Deduct from wallet
            $wallet->decrement('balance', $transaction->amount);

            // Create wallet transaction
            $walletTransaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'payment',
                'amount' => -$transaction->amount, // Negative for deduction
                'balance_after' => $wallet->balance,
                'description' => $this->getTransactionDescription($transaction),
                'status' => 'approved',
                'metadata' => [
                    'payment_transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                ],
            ]);

            return [
                'status' => 'completed',
                'gateway' => 'wallet',
                'gateway_transaction_id' => $walletTransaction->id,
                'response' => [
                    'wallet_id' => $wallet->id,
                    'balance_before' => $wallet->balance + $transaction->amount,
                    'balance_after' => $wallet->balance,
                ],
            ];
        });
    }

    /**
     * Verify wallet payment
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // Wallet payments are instant, no verification needed
        return $transaction->isCompleted();
    }

    /**
     * Refund wallet payment
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        return DB::transaction(function () use ($transaction, $amount) {
            $wallet = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();

            if (!$wallet) {
                throw new Exception('Wallet not found');
            }

            // Add refund amount to wallet
            $wallet->increment('balance', $amount);

            // Create refund wallet transaction
            $walletTransaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'refund',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => 'Refund for ' . $this->getTransactionDescription($transaction),
                'status' => 'approved',
                'metadata' => [
                    'payment_transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                ],
            ]);

            return [
                'status' => 'completed',
                'wallet_transaction_id' => $walletTransaction->id,
                'balance_after' => $wallet->balance,
            ];
        });
    }

    /**
     * Get transaction description
     */
    protected function getTransactionDescription(PaymentTransaction $transaction): string
    {
        if ($transaction->type === 'order_payment' && $transaction->order) {
            return 'Payment for Order #' . $transaction->order->order_number;
        }

        return 'Payment via Wallet';
    }
}
