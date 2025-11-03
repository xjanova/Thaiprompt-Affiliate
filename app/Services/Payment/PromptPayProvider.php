<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use Exception;

class PromptPayProvider implements PaymentProviderInterface
{
    /**
     * Validate PromptPay payment
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // Validate amount
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        return true;
    }

    /**
     * Process PromptPay payment
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        // Generate QR Code for PromptPay
        // In production, you would integrate with actual PromptPay API
        // For now, we'll create a mock QR code

        $refNo = 'PP-' . strtoupper(substr($transaction->transaction_id, -8));

        // Mock QR Code data (in production, use actual PromptPay QR generation)
        $qrData = $this->generateMockQRCode($transaction, $refNo);

        return [
            'status' => 'processing', // Waiting for payment
            'gateway' => 'promptpay',
            'qr_code' => $qrData,
            'ref_no' => $refNo,
            'response' => [
                'amount' => $transaction->amount,
                'currency' => 'THB',
                'expires_at' => $transaction->expired_at->toIso8601String(),
            ],
        ];
    }

    /**
     * Verify PromptPay payment (webhook callback)
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // In production, verify the webhook signature and data
        // Check if payment is confirmed from PromptPay

        // Mock verification
        if (isset($data['ref_no']) && $data['ref_no'] === $transaction->promptpay_ref_no) {
            if (isset($data['status']) && $data['status'] === 'success') {
                return true;
            }
        }

        return false;
    }

    /**
     * Refund PromptPay payment
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        // In production, initiate refund through PromptPay API
        // For mock, we'll just return success

        return [
            'status' => 'completed',
            'refund_amount' => $amount,
            'refunded_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate mock QR code
     * In production, use actual PromptPay QR generation library
     */
    protected function generateMockQRCode(PaymentTransaction $transaction, string $refNo): string
    {
        // This is a mock QR code data
        // In production, you would:
        // 1. Use PromptPay QR code generation library
        // 2. Include merchant ID, amount, reference number
        // 3. Generate actual QR code image

        $qrContent = json_encode([
            'version' => '1.0',
            'type' => 'promptpay',
            'merchant_id' => config('payment.promptpay.merchant_id', '0000000000000'),
            'amount' => number_format($transaction->amount, 2, '.', ''),
            'currency' => 'THB',
            'ref_no' => $refNo,
            'transaction_id' => $transaction->transaction_id,
        ]);

        // Return base64 encoded mock QR
        // In production, return actual QR code image URL or base64 image
        return base64_encode($qrContent);
    }

    /**
     * Check payment status
     */
    public function checkStatus(PaymentTransaction $transaction): array
    {
        // In production, query PromptPay API for payment status
        // For mock, return pending

        return [
            'status' => 'pending',
            'ref_no' => $transaction->promptpay_ref_no,
            'amount' => $transaction->amount,
        ];
    }
}
