<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\NFCCard;
use App\Services\NFC\NFCCardService;
use App\Services\NFC\NFCCardEncryptionService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * NFC Card Payment Provider
 *
 * Provider สำหรับการชำระเงินด้วยบัตร NFC
 */
class NFCCardProvider implements PaymentProviderInterface
{
    protected NFCCardService $nfcCardService;
    protected NFCCardEncryptionService $encryptionService;

    public function __construct(
        NFCCardService $nfcCardService,
        NFCCardEncryptionService $encryptionService
    ) {
        $this->nfcCardService = $nfcCardService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Validate NFC card payment
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (!isset($data['card_number'])) {
            throw new Exception('Card number is required');
        }

        if (!isset($data['encrypted_card_data'])) {
            throw new Exception('Encrypted card data is required');
        }

        // ค้นหาบัตร
        $card = NFCCard::where('card_number', $data['card_number'])->first();

        if (!$card) {
            throw new Exception('Card not found');
        }

        // ตรวจสอบว่าบัตรเป็นของ user นี้หรือไม่
        if ($card->user_id !== $transaction->user_id) {
            throw new Exception('Card does not belong to this user');
        }

        // ตรวจสอบสถานะบัตร
        if (!$card->isActive()) {
            throw new Exception('Card is not active. Status: ' . $card->status);
        }

        // ตรวจสอบว่าบัตรหมดอายุหรือไม่
        if ($card->isExpired()) {
            throw new Exception('Card has expired');
        }

        // ตรวจสอบว่าบัตรถูกบล็อกหรือไม่
        if ($card->isBlocked()) {
            throw new Exception('Card is blocked: ' . $card->blocked_reason);
        }

        // ตรวจสอบความถูกต้องของข้อมูลเข้ารหัส
        $verification = $this->nfcCardService->readAndVerifyCard(
            $data['card_number'],
            $data['encrypted_card_data']
        );

        if (!$verification['verified']) {
            throw new Exception('Card verification failed: ' . $verification['error']);
        }

        // ตรวจสอบยอดเงินในบัตร
        if (!$card->hasSufficientBalance($transaction->amount)) {
            throw new Exception(
                'Insufficient card balance. Available: ' . $card->balance . ' ' . $transaction->currency
            );
        }

        return true;
    }

    /**
     * Process NFC card payment
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        return DB::transaction(function () use ($transaction, $data) {
            try {
                // ค้นหาบัตร
                $card = NFCCard::where('card_number', $data['card_number'])
                    ->lockForUpdate()
                    ->first();

                if (!$card) {
                    throw new Exception('Card not found');
                }

                // ตรวจสอบอีกครั้งว่ามียอดเงินเพียงพอ
                if (!$card->hasSufficientBalance($transaction->amount)) {
                    throw new Exception('Insufficient card balance');
                }

                // ประมวลผลการชำระเงิน
                $nfcTransaction = $this->nfcCardService->processPayment(
                    $card,
                    $transaction->amount,
                    null, // NFCReader (สามารถส่งมาได้ถ้ามี)
                    [
                        'payment_transaction_id' => $transaction->id,
                        'order_id' => $transaction->order_id,
                        'description' => $this->getTransactionDescription($transaction),
                    ]
                );

                // อัพเดท payment transaction
                $transaction->update([
                    'gateway_transaction_id' => $nfcTransaction->transaction_id,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'nfc_transaction_id' => $nfcTransaction->id,
                        'card_id' => $card->id,
                        'card_number_masked' => $card->masked_card_number,
                    ]),
                ]);

                // ลิงก์ NFC transaction กับ payment transaction
                $nfcTransaction->update([
                    'payment_transaction_id' => $transaction->id,
                ]);

                Log::info('NFC Card payment processed', [
                    'payment_transaction_id' => $transaction->id,
                    'nfc_transaction_id' => $nfcTransaction->transaction_id,
                    'card_id' => $card->id,
                    'amount' => $transaction->amount,
                ]);

                return [
                    'status' => 'completed',
                    'gateway' => 'nfc_card',
                    'gateway_transaction_id' => $nfcTransaction->transaction_id,
                    'receipt_number' => $nfcTransaction->receipt_number,
                    'response' => [
                        'card_id' => $card->id,
                        'card_number_masked' => $card->masked_card_number,
                        'balance_before' => $nfcTransaction->balance_before,
                        'balance_after' => $nfcTransaction->balance_after,
                        'transaction_id' => $nfcTransaction->transaction_id,
                        'receipt_number' => $nfcTransaction->receipt_number,
                        'completed_at' => $nfcTransaction->completed_at,
                    ],
                ];
            } catch (Exception $e) {
                Log::error('NFC Card payment failed', [
                    'payment_transaction_id' => $transaction->id,
                    'card_number' => $data['card_number'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Verify NFC card payment
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // NFC card payments are instant, no additional verification needed
        // ตรวจสอบว่า transaction เสร็จสมบูรณ์แล้ว
        if ($transaction->isCompleted()) {
            return true;
        }

        // ตรวจสอบ NFC transaction ที่เชื่อมโยง
        if (isset($transaction->metadata['nfc_transaction_id'])) {
            $nfcTransactionId = $transaction->metadata['nfc_transaction_id'];
            $nfcTransaction = \App\Models\NFCTransaction::find($nfcTransactionId);

            if ($nfcTransaction && $nfcTransaction->isCompleted()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Refund NFC card payment
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        return DB::transaction(function () use ($transaction, $amount) {
            try {
                // ค้นหาบัตรจาก metadata
                if (!isset($transaction->metadata['card_id'])) {
                    throw new Exception('Card ID not found in transaction metadata');
                }

                $card = NFCCard::lockForUpdate()->find($transaction->metadata['card_id']);

                if (!$card) {
                    throw new Exception('Card not found');
                }

                // เติมเงินคืนให้บัตร
                $nfcTransaction = $this->nfcCardService->topUpCard(
                    $card,
                    $amount,
                    null,
                    [
                        'payment_transaction_id' => $transaction->id,
                        'type' => 'refund',
                        'description' => 'Refund for ' . $this->getTransactionDescription($transaction),
                    ]
                );

                Log::info('NFC Card payment refunded', [
                    'payment_transaction_id' => $transaction->id,
                    'nfc_transaction_id' => $nfcTransaction->transaction_id,
                    'card_id' => $card->id,
                    'amount' => $amount,
                ]);

                return [
                    'status' => 'completed',
                    'nfc_transaction_id' => $nfcTransaction->transaction_id,
                    'receipt_number' => $nfcTransaction->receipt_number,
                    'balance_after' => $card->balance,
                ];
            } catch (Exception $e) {
                Log::error('NFC Card refund failed', [
                    'payment_transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Get transaction description
     */
    protected function getTransactionDescription(PaymentTransaction $transaction): string
    {
        if ($transaction->type === 'order_payment' && $transaction->order) {
            return 'Payment for Order #' . $transaction->order->order_number;
        }

        return 'Payment via NFC Card';
    }

    /**
     * Get card information for display
     */
    public function getCardInfo(string $cardNumber): ?array
    {
        $card = NFCCard::where('card_number', $cardNumber)->first();

        if (!$card) {
            return null;
        }

        return [
            'card_id' => $card->id,
            'card_number_masked' => $card->masked_card_number,
            'card_type' => $card->card_type,
            'card_type_label' => $card->card_type_label,
            'balance' => $card->balance,
            'status' => $card->status,
            'status_label' => $card->status_label,
            'is_active' => $card->isActive(),
            'expires_at' => $card->expires_at,
        ];
    }
}
