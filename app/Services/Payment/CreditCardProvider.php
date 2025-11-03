<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use Exception;

class CreditCardProvider implements PaymentProviderInterface
{
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // Validate card data
        if (empty($data['card_number']) || empty($data['exp_month']) || empty($data['exp_year']) || empty($data['cvv'])) {
            throw new Exception('Invalid card data');
        }

        return true;
    }

    public function process(PaymentTransaction $transaction, array $data): array
    {
        // Mock credit card processing
        // In production, integrate with payment gateway like Stripe, Omise, etc.

        return [
            'status' => 'processing',
            'gateway' => 'credit_card',
            'gateway_transaction_id' => 'CC-' . strtoupper(substr($transaction->transaction_id, -10)),
            'response' => [
                'card_last4' => substr($data['card_number'], -4),
                'card_brand' => $this->detectCardBrand($data['card_number']),
            ],
        ];
    }

    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // Mock verification
        return isset($data['status']) && $data['status'] === 'success';
    }

    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        return [
            'status' => 'completed',
            'refund_amount' => $amount,
        ];
    }

    protected function detectCardBrand(string $cardNumber): string
    {
        $first = substr($cardNumber, 0, 1);
        if ($first == '4') return 'Visa';
        if ($first == '5') return 'Mastercard';
        if ($first == '3') return 'Amex';
        return 'Unknown';
    }
}
