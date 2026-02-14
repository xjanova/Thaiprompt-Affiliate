<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Bank Transfer Payment Provider
 *
 * รองรับการโอนเงินผ่านธนาคารและอัพโหลดสลิปยืนยัน
 */
class BankTransferProvider implements PaymentProviderInterface
{
    protected $gateway;

    public function __construct()
    {
        try {
            $this->gateway = PaymentGateway::findByCode('bank_transfer');
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ข้ามการโหลด config
            Log::debug('BankTransferProvider: Cannot load gateway config - '.$e->getMessage());
            $this->gateway = null;
        }
    }

    /**
     * Validate bank transfer payment
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
            $minDeposit = $limits['min_deposit'] ?? 100;
            $maxDeposit = $limits['max_deposit'] ?? 500000;

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
     * Process bank transfer payment
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        // ดึงข้อมูลบัญชีธนาคารจาก credentials
        $bankName = $this->gateway?->getCredential('bank_name') ?? config('payment.bank_transfer.bank_name', 'ไม่ได้ระบุธนาคาร');
        $bankCode = $this->gateway?->getCredential('bank_code') ?? '';
        $accountNumber = $this->gateway?->getCredential('account_number') ?? 'ไม่ได้ระบุเลขบัญชี';
        $accountName = $this->gateway?->getCredential('account_name') ?? config('app.name');
        $branch = $this->gateway?->getCredential('branch') ?? '';

        // สร้าง reference number
        $refNo = 'BT-'.strtoupper(substr($transaction->transaction_id, -8));

        Log::info('Bank transfer payment initiated', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $transaction->amount,
            'ref_no' => $refNo,
        ]);

        return [
            'status' => 'processing',
            'gateway' => 'bank_transfer',
            'ref_no' => $refNo,
            'response' => [
                'bank_name' => $bankName,
                'bank_code' => $bankCode,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'branch' => $branch,
                'amount' => $transaction->amount,
                'ref_no' => $refNo,
                'instructions' => $this->gateway?->instructions ?? 'โอนเงินไปยังบัญชีด้านบนและอัพโหลดสลิปเพื่อยืนยัน',
            ],
        ];
    }

    /**
     * Verify bank transfer payment (admin approval)
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // Bank transfer ต้องผ่านการอนุมัติจาก admin
        if (isset($data['status']) && in_array($data['status'], ['success', 'approved'])) {
            // ตรวจสอบว่ามี slip หรือหลักฐานการโอน
            if (isset($data['slip_verified']) && $data['slip_verified'] === true) {
                return true;
            }

            // ถ้า admin อนุมัติโดยตรง
            if (isset($data['approved_by'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Refund bank transfer payment
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        // Bank transfer refund ต้องดำเนินการ manual โดย admin
        Log::info('Bank transfer refund requested', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $amount,
        ]);

        return [
            'status' => 'pending',
            'refund_amount' => $amount,
            'message' => 'Refund request submitted. Admin will process manually.',
            'refunded_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get bank account information for display
     */
    public function getBankAccountInfo(): array
    {
        if (! $this->gateway) {
            return [];
        }

        return [
            'bank_name' => $this->gateway->getCredential('bank_name'),
            'bank_code' => $this->gateway->getCredential('bank_code'),
            'account_number' => $this->gateway->getCredential('account_number'),
            'account_name' => $this->gateway->getCredential('account_name'),
            'branch' => $this->gateway->getCredential('branch'),
        ];
    }
}
