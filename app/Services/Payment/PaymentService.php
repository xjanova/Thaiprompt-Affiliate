<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\User;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\PaymentIntent;

class PaymentService
{
    protected $stripeGateway;
    protected $promptPayGateway;

    public function __construct()
    {
        $this->stripeGateway = new StripeGateway();
        $this->promptPayGateway = new PromptPayGateway();
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Order $order, array $paymentData): array
    {
        return match ($order->payment_method) {
            'stripe' => $this->stripeGateway->charge($order, $paymentData),
            'promptpay' => $this->promptPayGateway->generateQR($order),
            'wallet' => $this->processWalletPayment($order),
            'cash' => $this->processCashPayment($order),
            default => ['success' => false, 'message' => 'Invalid payment method'],
        };
    }

    /**
     * Process wallet payment
     */
    protected function processWalletPayment(Order $order): array
    {
        $user = $order->user;
        $wallet = $user->wallet;

        if (!$wallet || !$wallet->hasSufficientBalance($order->total)) {
            return ['success' => false, 'message' => 'Insufficient wallet balance'];
        }

        try {
            $wallet->debit(
                $order->total,
                'purchase',
                "Payment for order {$order->order_number}",
                $order
            );

            $order->update([
                'payment_status' => 'completed',
                'paid_at' => now(),
                'transaction_id' => 'WALLET-' . uniqid(),
            ]);

            return ['success' => true, 'message' => 'Payment successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process cash payment (for POS)
     */
    protected function processCashPayment(Order $order): array
    {
        $order->update([
            'payment_status' => 'completed',
            'paid_at' => now(),
            'transaction_id' => 'CASH-' . uniqid(),
        ]);

        return ['success' => true, 'message' => 'Cash payment recorded'];
    }

    /**
     * Process refund
     */
    public function processRefund(Order $order, float $amount = null): array
    {
        $refundAmount = $amount ?? $order->total;

        return match ($order->payment_method) {
            'stripe' => $this->stripeGateway->refund($order, $refundAmount),
            'wallet', 'cash' => $this->processWalletRefund($order, $refundAmount),
            default => ['success' => false, 'message' => 'Refund not supported for this payment method'],
        };
    }

    /**
     * Process wallet refund
     */
    protected function processWalletRefund(Order $order, float $amount): array
    {
        $user = $order->user;
        $wallet = $user->wallet;

        if (!$wallet) {
            return ['success' => false, 'message' => 'User wallet not found'];
        }

        try {
            $wallet->credit(
                $amount,
                'refund',
                "Refund for order {$order->order_number}",
                $order
            );

            return ['success' => true, 'message' => 'Refund processed successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

class StripeGateway
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function charge(Order $order, array $paymentData): array
    {
        try {
            $charge = Charge::create([
                'amount' => $order->total * 100, // Convert to cents
                'currency' => 'thb',
                'source' => $paymentData['token'],
                'description' => "Order {$order->order_number}",
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

            $order->update([
                'payment_status' => 'completed',
                'paid_at' => now(),
                'transaction_id' => $charge->id,
            ]);

            return ['success' => true, 'charge' => $charge];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function refund(Order $order, float $amount): array
    {
        try {
            $refund = \Stripe\Refund::create([
                'charge' => $order->transaction_id,
                'amount' => $amount * 100,
            ]);

            return ['success' => true, 'refund' => $refund];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

class PromptPayGateway
{
    public function generateQR(Order $order): array
    {
        // PromptPay QR Code generation
        $merchantId = config('services.promptpay.merchant_id');
        $amount = $order->total;

        // Generate PromptPay QR payload
        $qrData = $this->generatePromptPayQR($merchantId, $amount, $order->order_number);

        return [
            'success' => true,
            'qr_code' => $qrData,
            'amount' => $amount,
            'order_number' => $order->order_number,
        ];
    }

    protected function generatePromptPayQR(string $merchantId, float $amount, string $reference): string
    {
        // Simplified PromptPay QR generation
        // In production, use proper PromptPay library

        $payload = '';

        // Payload Format Indicator
        $payload .= '000201';

        // Point of Initiation Method
        $payload .= '010212';

        // Merchant Account Information
        $merchantData = '0016A000000677010111' . // AID
                       '01' . strlen($merchantId) . $merchantId . // Merchant ID
                       '02' . strlen($reference) . $reference; // Reference
        $payload .= '29' . strlen($merchantData) . $merchantData;

        // Transaction Currency (THB = 764)
        $payload .= '5303764';

        // Transaction Amount
        $amountStr = number_format($amount, 2, '.', '');
        $payload .= '54' . str_pad(strlen($amountStr), 2, '0', STR_PAD_LEFT) . $amountStr;

        // Country Code (TH)
        $payload .= '5802TH';

        // CRC (placeholder - should be calculated)
        $payload .= '6304';

        return $payload;
    }

    public function verifyPayment(string $transactionId): array
    {
        // Verify PromptPay payment
        // In production, implement actual verification with bank API

        return [
            'success' => true,
            'verified' => true,
            'transaction_id' => $transactionId,
        ];
    }
}
