<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;

class CashOnDeliveryProvider implements PaymentProviderInterface
{
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        return true;
    }

    public function process(PaymentTransaction $transaction, array $data): array
    {
        // COD doesn't require immediate payment
        return [
            'status' => 'pending',
            'gateway' => 'cash_on_delivery',
            'response' => [
                'message' => 'Payment will be collected upon delivery',
            ],
        ];
    }

    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // Manual verification required
        return isset($data['delivered']) && $data['delivered'] === true;
    }

    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        return ['status' => 'completed', 'refund_amount' => $amount];
    }
}
