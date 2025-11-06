<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use Exception;

/**
 * IMPORTANT: This provider should NOT handle raw card data directly.
 * In production, use tokenized payment methods via payment gateways like:
 * - Stripe Elements
 * - Omise.js
 * - PaySolutions Card Token API
 *
 * This ensures PCI DSS compliance by never touching raw card data on your server.
 */
class CreditCardProvider implements PaymentProviderInterface
{
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // SECURITY: Validate that we're receiving a payment token, NOT raw card data
        if (isset($data['card_number'])) {
            throw new Exception('PCI DSS Violation: Raw card data not allowed. Use payment token instead.');
        }

        if (empty($data['payment_token']) && empty($data['card_token'])) {
            throw new Exception('Payment token required');
        }

        // Validate gateway is specified
        if (empty($data['gateway'])) {
            throw new Exception('Payment gateway not specified');
        }

        return true;
    }

    public function process(PaymentTransaction $transaction, array $data): array
    {
        // SECURITY: This method should only work with tokenized payments
        // The actual card processing happens on the gateway side (Stripe, Omise, PaySolutions)

        $gateway = $data['gateway'] ?? 'stripe';
        $token = $data['payment_token'] ?? $data['card_token'];

        // In production, integrate with actual payment gateway APIs
        // Example: Stripe, Omise, PaySolutions

        return [
            'status' => 'processing',
            'gateway' => $gateway,
            'gateway_transaction_id' => 'CC-' . strtoupper(substr($transaction->transaction_id, -10)),
            'response' => [
                'card_last4' => $data['card_last4'] ?? '****', // Provided by gateway
                'card_brand' => $data['card_brand'] ?? 'Unknown', // Provided by gateway
                'token_used' => substr($token, 0, 10) . '...',
            ],
            'message' => 'Card payment is processing via ' . $gateway,
        ];
    }

    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // Verify payment with the actual gateway
        // In production, query the gateway API for payment status
        return isset($data['status']) && $data['status'] === 'success';
    }

    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        // In production, call the gateway refund API
        return [
            'status' => 'completed',
            'refund_amount' => $amount,
        ];
    }
}
