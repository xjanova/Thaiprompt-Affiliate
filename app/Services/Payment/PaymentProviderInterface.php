<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;

interface PaymentProviderInterface
{
    /**
     * Validate payment data
     */
    public function validate(PaymentTransaction $transaction, array $data): bool;

    /**
     * Process payment
     */
    public function process(PaymentTransaction $transaction, array $data): array;

    /**
     * Verify payment (for webhooks)
     */
    public function verify(PaymentTransaction $transaction, array $data): bool;

    /**
     * Refund payment
     */
    public function refund(PaymentTransaction $transaction, float $amount): array;
}
