<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * PromptPay Payment Provider
 *
 * รองรับการชำระเงินผ่าน QR Code พร้อมเพย์
 */
class PromptPayProvider implements PaymentProviderInterface
{
    protected $gateway;

    public function __construct()
    {
        try {
            $this->gateway = PaymentGateway::findByCode('promptpay');
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ข้ามการโหลด config
            Log::debug('PromptPayProvider: Cannot load gateway config - '.$e->getMessage());
            $this->gateway = null;
        }
    }

    /**
     * Validate PromptPay payment
     *
     * @throws Exception
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // ตรวจสอบจำนวนเงิน
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // ตรวจสอบ limits ถ้ามี gateway config
        if ($this->gateway) {
            $limits = $this->gateway->limits ?? [];
            $minDeposit = $limits['min_deposit'] ?? 1;
            $maxDeposit = $limits['max_deposit'] ?? 50000;

            if ($transaction->amount < $minDeposit) {
                throw new Exception("จำนวนเงินขั้นต่ำคือ {$minDeposit} บาท");
            }

            if ($transaction->amount > $maxDeposit) {
                throw new Exception("จำนวนเงินสูงสุดคือ {$maxDeposit} บาท");
            }
        }

        return true;
    }

    /**
     * Process PromptPay payment
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        // ดึง PromptPay ID และชื่อจาก credentials
        $promptPayId = $this->gateway?->getCredential('promptpay_id') ?? config('payment.promptpay.id', '');
        $promptPayName = $this->gateway?->getCredential('promptpay_name') ?? config('app.name');
        $promptPayType = $this->gateway?->getCredential('promptpay_type') ?? 'phone'; // phone, citizen_id, ewallet

        // สร้าง reference number
        $refNo = 'PP-'.strtoupper(substr($transaction->transaction_id, -8));

        // สร้าง QR Code
        $qrData = $this->generatePromptPayQRCode($transaction, $refNo, $promptPayId, $promptPayType);

        Log::info('PromptPay payment initiated', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $transaction->amount,
            'ref_no' => $refNo,
        ]);

        return [
            'status' => 'processing',
            'gateway' => 'promptpay',
            'qr_code' => $qrData,
            'ref_no' => $refNo,
            'response' => [
                'promptpay_id' => $this->maskPromptPayId($promptPayId),
                'promptpay_name' => $promptPayName,
                'amount' => $transaction->amount,
                'currency' => 'THB',
                'expires_at' => $transaction->expired_at?->toIso8601String() ?? now()->addMinutes(30)->toIso8601String(),
            ],
        ];
    }

    /**
     * Verify PromptPay payment (webhook callback)
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // ตรวจสอบ reference number
        if (isset($data['ref_no']) && $data['ref_no'] === $transaction->promptpay_ref_no) {
            // ตรวจสอบสถานะ
            if (isset($data['status']) && in_array($data['status'], ['success', 'completed', 'paid'])) {
                // ตรวจสอบจำนวนเงิน (anti-tampering)
                if (isset($data['amount'])) {
                    $paidAmount = (float) $data['amount'];
                    if (abs($paidAmount - $transaction->amount) > 0.01) {
                        Log::warning('PromptPay payment amount mismatch', [
                            'transaction_id' => $transaction->transaction_id,
                            'expected' => $transaction->amount,
                            'received' => $paidAmount,
                        ]);

                        return false;
                    }
                }

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
        // PromptPay ไม่รองรับ refund อัตโนมัติ ต้องทำ manual
        Log::info('PromptPay refund requested', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $amount,
        ]);

        return [
            'status' => 'pending',
            'refund_amount' => $amount,
            'message' => 'Refund will be processed manually via bank transfer.',
            'refunded_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate PromptPay QR Code
     */
    protected function generatePromptPayQRCode(PaymentTransaction $transaction, string $refNo, string $promptPayId, string $type): string
    {
        // ตรวจสอบว่ามี PromptPay ID หรือไม่
        if (empty($promptPayId)) {
            Log::warning('PromptPay ID not configured');

            return $this->generateMockQRCode($transaction, $refNo);
        }

        try {
            // ใช้ PromptPay QR Standard (EMVCo standard)
            // Format: 00020101021129XXXX จะต้องใช้ library สำหรับ generate จริง
            // ตัวอย่างนี้ใช้ basic format

            // Clean PromptPay ID (remove hyphens and spaces)
            $cleanId = preg_replace('/[\s\-]/', '', $promptPayId);

            // Determine payload format ID based on type
            $payloadFormatId = match ($type) {
                'citizen_id' => '02', // National ID
                'ewallet' => '03',    // E-Wallet
                default => '01',      // Phone number
            };

            // Generate QR payload (simplified version)
            // Production จะใช้ EMVCo standard library
            $qrPayload = [
                'format' => 'promptpay',
                'version' => '01',
                'id_type' => $payloadFormatId,
                'id' => $cleanId,
                'amount' => number_format($transaction->amount, 2, '.', ''),
                'currency' => '764', // THB ISO 4217
                'ref' => $refNo,
                'transaction_id' => $transaction->transaction_id,
            ];

            // TODO: ใช้ library PromptPay QR จริง เช่น:
            // return PromptPay::generateQRCode($cleanId, $transaction->amount);

            return base64_encode(json_encode($qrPayload));
        } catch (\Exception $e) {
            Log::error('Failed to generate PromptPay QR', [
                'error' => $e->getMessage(),
            ]);

            return $this->generateMockQRCode($transaction, $refNo);
        }
    }

    /**
     * Generate mock QR code (fallback)
     */
    protected function generateMockQRCode(PaymentTransaction $transaction, string $refNo): string
    {
        $qrContent = json_encode([
            'version' => '1.0',
            'type' => 'promptpay_mock',
            'amount' => number_format($transaction->amount, 2, '.', ''),
            'currency' => 'THB',
            'ref_no' => $refNo,
            'transaction_id' => $transaction->transaction_id,
            'warning' => 'This is a mock QR code. Configure PromptPay credentials for real QR.',
        ]);

        return base64_encode($qrContent);
    }

    /**
     * Mask PromptPay ID for display
     */
    protected function maskPromptPayId(string $id): string
    {
        if (strlen($id) < 4) {
            return str_repeat('*', strlen($id));
        }

        return substr($id, 0, 3).str_repeat('*', strlen($id) - 6).substr($id, -3);
    }

    /**
     * Check payment status
     */
    public function checkStatus(PaymentTransaction $transaction): array
    {
        // ในการใช้งานจริง ต้อง query จาก PromptPay API หรือ bank API
        return [
            'status' => $transaction->status,
            'ref_no' => $transaction->promptpay_ref_no,
            'amount' => $transaction->amount,
        ];
    }

    /**
     * Get PromptPay account info for display
     */
    public function getAccountInfo(): array
    {
        if (! $this->gateway) {
            return [];
        }

        $id = $this->gateway->getCredential('promptpay_id') ?? '';

        return [
            'promptpay_id' => $this->maskPromptPayId($id),
            'promptpay_name' => $this->gateway->getCredential('promptpay_name'),
            'promptpay_type' => $this->gateway->getCredential('promptpay_type') ?? 'phone',
        ];
    }
}
