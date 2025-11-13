<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService
{
    protected $providers = [];

    public function __construct()
    {
        $this->registerProviders();
    }

    /**
     * Register payment providers
     */
    protected function registerProviders()
    {
        $this->providers['wallet'] = new WalletPaymentProvider();
        $this->providers['promptpay'] = new PromptPayProvider();
        $this->providers['credit_card'] = new CreditCardProvider();
        $this->providers['bank_transfer'] = new BankTransferProvider();
        $this->providers['cash_on_delivery'] = new CashOnDeliveryProvider();
        $this->providers['paysolutions'] = new PaySolutionsProvider();
        $this->providers['nfc_card'] = app(NFCCardProvider::class);
    }

    /**
     * Get payment provider by method
     */
    public function getProvider(string $method)
    {
        if (!isset($this->providers[$method])) {
            throw new Exception("Payment provider '{$method}' not found");
        }

        return $this->providers[$method];
    }

    /**
     * Create payment transaction for order
     */
    public function createOrderPayment(Order $order, string $paymentMethod, array $options = []): PaymentTransaction
    {
        return DB::transaction(function () use ($order, $paymentMethod, $options) {
            $transaction = PaymentTransaction::create([
                'user_id' => $order->user_id,
                'type' => 'order_payment',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'amount' => $order->total_amount,
                'currency' => 'THB',
                'order_id' => $order->id,
                'metadata' => $options['metadata'] ?? null,
                'expired_at' => now()->addMinutes(30), // 30 minutes expiry
            ]);

            return $transaction;
        });
    }

    /**
     * Create payment transaction for wallet top-up
     */
    public function createWalletTopup(User $user, float $amount, string $paymentMethod, array $options = []): PaymentTransaction
    {
        return DB::transaction(function () use ($user, $amount, $paymentMethod, $options) {
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'type' => 'wallet_topup',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'THB',
                'metadata' => $options['metadata'] ?? null,
                'expired_at' => now()->addMinutes(30), // 30 minutes expiry
            ]);

            return $transaction;
        });
    }

    /**
     * Process payment
     */
    public function processPayment(PaymentTransaction $transaction, array $paymentData = [])
    {
        try {
            // SECURITY: Validate transaction amount hasn't been tampered with
            if (isset($paymentData['amount']) && $paymentData['amount'] != $transaction->amount) {
                throw new Exception('Payment amount mismatch. Possible tampering detected.');
            }

            // SECURITY: Check transaction hasn't expired
            if ($transaction->isExpired()) {
                throw new Exception('Payment transaction has expired');
            }

            // SECURITY: Check transaction is in valid state
            if (!in_array($transaction->status, ['pending', 'processing'])) {
                throw new Exception('Invalid transaction status for processing: ' . $transaction->status);
            }

            $provider = $this->getProvider($transaction->payment_method);

            // Validate payment
            if (!$provider->validate($transaction, $paymentData)) {
                throw new Exception('Payment validation failed');
            }

            // Process payment
            $result = $provider->process($transaction, $paymentData);

            // Update transaction
            $transaction->update([
                'gateway' => $result['gateway'] ?? null,
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                'gateway_response' => $result['response'] ?? null,
                'promptpay_qr_code' => $result['qr_code'] ?? null,
                'promptpay_ref_no' => $result['ref_no'] ?? null,
            ]);

            // If payment is completed immediately (e.g., wallet payment)
            if ($result['status'] === 'completed') {
                $this->completePayment($transaction);
            } else {
                $transaction->update(['status' => 'processing']);
            }

            return [
                'success' => true,
                'transaction' => $transaction->fresh(),
                'data' => $result,
            ];
        } catch (Exception $e) {
            $transaction->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction' => $transaction->fresh(),
            ];
        }
    }

    /**
     * Complete payment transaction
     */
    public function completePayment(PaymentTransaction $transaction)
    {
        return DB::transaction(function () use ($transaction) {
            if ($transaction->isCompleted()) {
                return true;
            }

            $transaction->markAsCompleted();

            // Handle different transaction types
            switch ($transaction->type) {
                case 'order_payment':
                    $this->completeOrderPayment($transaction);
                    break;

                case 'wallet_topup':
                    $this->completeWalletTopup($transaction);
                    break;

                case 'withdrawal':
                    $this->completeWithdrawal($transaction);
                    break;
            }

            return true;
        });
    }

    /**
     * Complete order payment
     */
    protected function completeOrderPayment(PaymentTransaction $transaction)
    {
        $order = $transaction->order;

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => 'processing', // Move to processing after payment
            ]);

            // Reduce product stock
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product->track_inventory) {
                    $product->decrement('stock_quantity', $item->quantity);
                    $product->increment('sales_count', $item->quantity);
                }
            }
        }
    }

    /**
     * Complete wallet top-up
     */
    protected function completeWalletTopup(PaymentTransaction $transaction)
    {
        $user = $transaction->user;

        // Get or create wallet
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // Create wallet transaction
        $walletTransaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => $transaction->amount,
            'balance_after' => $wallet->balance + $transaction->amount,
            'description' => 'Wallet top-up via ' . $transaction->payment_method,
            'status' => 'approved',
            'metadata' => [
                'payment_transaction_id' => $transaction->id,
            ],
        ]);

        // Update wallet balance
        $wallet->increment('balance', $transaction->amount);

        // Link wallet transaction
        $transaction->update(['wallet_transaction_id' => $walletTransaction->id]);
    }

    /**
     * Complete withdrawal
     */
    protected function completeWithdrawal(PaymentTransaction $transaction)
    {
        // Implementation for withdrawal completion
        // This would handle the actual money transfer to user's bank account
    }

    /**
     * Refund payment
     */
    public function refundPayment(PaymentTransaction $transaction, float $amount = null)
    {
        return DB::transaction(function () use ($transaction, $amount) {
            $refundAmount = $amount ?? $transaction->amount;

            // Create refund transaction
            $refund = PaymentTransaction::create([
                'user_id' => $transaction->user_id,
                'type' => 'refund',
                'payment_method' => $transaction->payment_method,
                'status' => 'completed',
                'amount' => $refundAmount,
                'currency' => $transaction->currency,
                'order_id' => $transaction->order_id,
                'metadata' => [
                    'original_transaction_id' => $transaction->id,
                ],
            ]);

            // Process refund based on payment method
            if ($transaction->payment_method === 'wallet') {
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $transaction->user_id],
                    ['balance' => 0]
                );

                $wallet->increment('balance', $refundAmount);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'refund',
                    'amount' => $refundAmount,
                    'balance_after' => $wallet->balance,
                    'description' => 'Refund for order #' . $transaction->order->order_number,
                    'status' => 'approved',
                ]);
            }

            // Update original transaction
            $transaction->update(['status' => 'refunded']);

            // Update order if exists
            if ($transaction->order) {
                $transaction->order->update([
                    'payment_status' => 'refunded',
                    'status' => 'refunded',
                ]);
            }

            return $refund;
        });
    }

    /**
     * Verify payment (for webhook callbacks)
     */
    public function verifyPayment(string $transactionId, array $data)
    {
        $transaction = PaymentTransaction::where('transaction_id', $transactionId)->firstOrFail();

        $provider = $this->getProvider($transaction->payment_method);

        if ($provider->verify($transaction, $data)) {
            $this->completePayment($transaction);
            return true;
        }

        return false;
    }

    /**
     * Get available payment methods
     */
    public function getAvailablePaymentMethods(): array
    {
        $methods = [
            [
                'id' => 'wallet',
                'name' => 'Wallet',
                'description' => 'จ่ายด้วยยอดเงินในกระเป๋า',
                'icon' => '👛',
                'enabled' => true,
            ],
            [
                'id' => 'promptpay',
                'name' => 'PromptPay',
                'description' => 'สแกน QR Code เพื่อชำระเงิน',
                'icon' => '📱',
                'enabled' => true,
            ],
            [
                'id' => 'credit_card',
                'name' => 'Credit/Debit Card',
                'description' => 'บัตรเครดิต/เดบิต',
                'icon' => '💳',
                'enabled' => true,
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'description' => 'โอนเงินผ่านธนาคาร',
                'icon' => '🏦',
                'enabled' => true,
            ],
            [
                'id' => 'cash_on_delivery',
                'name' => 'Cash on Delivery',
                'description' => 'เก็บเงินปลายทาง',
                'icon' => '💵',
                'enabled' => true,
            ],
        ];

        // Add PaySolutions if configured and active
        try {
            if (class_exists(\App\Models\PaymentGateway::class)) {
                $paymentGateway = \App\Models\PaymentGateway::findByCode('paysolutions');
                if ($paymentGateway && $paymentGateway->is_active && $paymentGateway->isConfigured()) {
                    $methods[] = [
                        'id' => 'paysolutions',
                        'name' => 'PaySolutions',
                        'description' => 'ชำระเงินผ่าน PaySolutions (QR, Card, E-Wallet)',
                        'icon' => '💳',
                        'enabled' => true,
                        'submethods' => [
                            'qr' => 'QR Code Payment',
                            'card' => 'Credit/Debit Card',
                            'bank_transfer' => 'Bank Transfer',
                            'ewallet' => 'E-Wallet',
                            'installment' => 'Installment',
                        ],
                    ];
                }
            }
        } catch (\Exception $e) {
            // PaymentGateway model not available or database not ready
            \Log::warning('Could not load PaySolutions gateway: ' . $e->getMessage());
        }

        return $methods;
    }
}
