<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;

class BankTransferProvider implements PaymentProviderInterface
{
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        return true;
    }

    public function process(PaymentTransaction $transaction, array $data): array
    {
        // Generate bank transfer instructions
        return [
            'status' => 'processing',
            'gateway' => 'bank_transfer',
            'response' => [
                'bank_name' => 'ธนาคารกสิกรไทย',
                'account_number' => 'XXX-X-XXXXX-X',
                'account_name' => config('app.name'),
                'amount' => $transaction->amount,
                'ref_no' => 'BT-' . strtoupper(substr($transaction->transaction_id, -8)),
            ],
        ];
    }

    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        return isset($data['status']) && $data['status'] === 'success';
    }

    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        return ['status' => 'completed', 'refund_amount' => $amount];
    }
}
