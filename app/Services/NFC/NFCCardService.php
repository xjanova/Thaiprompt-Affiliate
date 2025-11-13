<?php

namespace App\Services\NFC;

use App\Models\NFCCard;
use App\Models\NFCTransaction;
use App\Models\NFCReader;
use App\Models\User;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * NFC Card Service
 *
 * บริการจัดการบัตร NFC ทั้งหมด รวมถึงการจับคู่, การชำระเงิน, และการจัดการยอดเงิน
 */
class NFCCardService
{
    protected NFCCardEncryptionService $encryptionService;

    public function __construct(NFCCardEncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Issue a new NFC card
     *
     * ออกบัตร NFC ใหม่
     */
    public function issueCard(array $data, int $issuedBy): NFCCard
    {
        DB::beginTransaction();

        try {
            // เตรียมข้อมูลสำหรับเข้ารหัส
            $cardData = [
                'card_number' => $data['card_number'],
                'card_type' => $data['card_type'] ?? NFCCard::TYPE_STANDARD,
                'issued_at' => now()->toIso8601String(),
            ];

            // สร้างชุดข้อมูลเข้ารหัส
            $encryptionPackage = $this->encryptionService->createCardEncryptionPackage($cardData);

            // สร้าง signature
            $signature = $this->encryptionService->generateCardSignature(
                $data['card_number'],
                null,
                ['issued_by' => $issuedBy]
            );

            // สร้างบัตรในฐานข้อมูล
            $card = NFCCard::create([
                'card_number' => $data['card_number'],
                'card_name' => $data['card_name'] ?? null,
                'card_type' => $data['card_type'] ?? NFCCard::TYPE_STANDARD,
                'encrypted_data' => $encryptionPackage['encrypted_data'],
                'encryption_key_hash' => $encryptionPackage['encryption_key_hash'],
                'card_signature' => $signature,
                'encryption_version' => $encryptionPackage['encryption_version'],
                'balance' => $data['initial_balance'] ?? 0,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'status' => NFCCard::STATUS_PENDING,
                'expires_at' => $data['expires_at'] ?? now()->addYears(5),
                'issued_by' => $issuedBy,
                'metadata' => $data['metadata'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // เก็บ encryption key ไว้ในที่ปลอดภัย (ในระบบจริงควรเก็บในระบบแยก)
            // สำหรับ demo เก็บไว้ใน metadata (ไม่แนะนำในระบบจริง)
            $card->update([
                'metadata' => array_merge(
                    $card->metadata ?? [],
                    ['_encryption_key' => encrypt($encryptionPackage['encryption_key'])]
                ),
            ]);

            DB::commit();

            Log::info('NFC Card issued', [
                'card_id' => $card->id,
                'card_number' => $card->card_number,
                'issued_by' => $issuedBy,
            ]);

            return $card;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to issue NFC card', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new Exception('Failed to issue card: ' . $e->getMessage());
        }
    }

    /**
     * Pair card with user
     *
     * จับคู่บัตรกับผู้ใช้
     */
    public function pairCardWithUser(NFCCard $card, User $user, int $pairedBy): bool
    {
        DB::beginTransaction();

        try {
            if ($card->isPaired()) {
                throw new Exception('Card is already paired with another user');
            }

            if ($card->status === NFCCard::STATUS_BLOCKED) {
                throw new Exception('Card is blocked and cannot be paired');
            }

            // อัพเดทข้อมูลเข้ารหัสใหม่พร้อม user_id
            $encryptionKey = decrypt($card->metadata['_encryption_key'] ?? '');

            $newCardData = [
                'card_number' => $card->card_number,
                'user_id' => $user->id,
                'card_type' => $card->card_type,
                'paired_at' => now()->toIso8601String(),
            ];

            $encryptionPackage = $this->encryptionService->createCardEncryptionPackage($newCardData);

            $signature = $this->encryptionService->generateCardSignature(
                $card->card_number,
                (string) $user->id,
                ['paired_by' => $pairedBy]
            );

            // อัพเดทบัตร
            $card->update([
                'user_id' => $user->id,
                'is_paired' => true,
                'paired_at' => now(),
                'paired_by' => $pairedBy,
                'status' => NFCCard::STATUS_ACTIVE,
                'encrypted_data' => $encryptionPackage['encrypted_data'],
                'encryption_key_hash' => $encryptionPackage['encryption_key_hash'],
                'card_signature' => $signature,
                'metadata' => array_merge(
                    $card->metadata ?? [],
                    ['_encryption_key' => encrypt($encryptionPackage['encryption_key'])]
                ),
            ]);

            DB::commit();

            Log::info('NFC Card paired with user', [
                'card_id' => $card->id,
                'user_id' => $user->id,
                'paired_by' => $pairedBy,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to pair NFC card', [
                'card_id' => $card->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to pair card: ' . $e->getMessage());
        }
    }

    /**
     * Unpair card from user
     */
    public function unpairCard(NFCCard $card): bool
    {
        DB::beginTransaction();

        try {
            if (!$card->isPaired()) {
                throw new Exception('Card is not paired');
            }

            $card->unpair();

            DB::commit();

            Log::info('NFC Card unpaired', ['card_id' => $card->id]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to unpair NFC card', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to unpair card: ' . $e->getMessage());
        }
    }

    /**
     * Read and verify card data
     *
     * อ่านและตรวจสอบข้อมูลบัตร NFC
     */
    public function readAndVerifyCard(string $cardNumber, string $encryptedDataFromCard): array
    {
        try {
            // ค้นหาบัตรในระบบ
            $card = NFCCard::where('card_number', $cardNumber)->first();

            if (!$card) {
                return [
                    'success' => false,
                    'verified' => false,
                    'error' => 'Card not found in system',
                ];
            }

            // ดึง encryption key
            $encryptionKey = decrypt($card->metadata['_encryption_key'] ?? '');

            if (!$encryptionKey) {
                return [
                    'success' => false,
                    'verified' => false,
                    'error' => 'Encryption key not found',
                ];
            }

            // ตรวจสอบความถูกต้องของบัตร
            $verification = $this->encryptionService->verifyCardAuthenticity(
                $encryptedDataFromCard,
                $encryptionKey,
                $card->encryption_key_hash,
                $card->card_signature
            );

            if (!$verification['valid']) {
                // บันทึกความพยายามที่ล้มเหลว
                $card->incrementFailedAttempts();

                Log::warning('Invalid card verification attempt', [
                    'card_id' => $card->id,
                    'card_number' => $cardNumber,
                    'error' => $verification['error'],
                ]);

                return [
                    'success' => false,
                    'verified' => false,
                    'error' => $verification['error'],
                    'card' => null,
                ];
            }

            // รีเซ็ต failed attempts
            $card->resetFailedAttempts();

            return [
                'success' => true,
                'verified' => true,
                'card' => $card,
                'decrypted_data' => $verification['data'],
                'error' => null,
            ];
        } catch (Exception $e) {
            Log::error('Failed to read and verify card', [
                'card_number' => $cardNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'verified' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process payment with NFC card
     *
     * ประมวลผลการชำระเงินด้วยบัตร NFC
     */
    public function processPayment(
        NFCCard $card,
        float $amount,
        NFCReader $reader = null,
        array $metadata = []
    ): NFCTransaction {
        DB::beginTransaction();

        try {
            // ตรวจสอบสถานะบัตร
            if (!$card->isActive()) {
                throw new Exception('Card is not active');
            }

            // ตรวจสอบยอดเงิน
            if (!$card->hasSufficientBalance($amount)) {
                throw new Exception('Insufficient balance');
            }

            // สร้าง transaction
            $transaction = NFCTransaction::create([
                'nfc_card_id' => $card->id,
                'user_id' => $card->user_id,
                'nfc_reader_id' => $reader?->id,
                'type' => NFCTransaction::TYPE_PAYMENT,
                'amount' => $amount,
                'balance_before' => $card->balance,
                'balance_after' => $card->balance - $amount,
                'status' => NFCTransaction::STATUS_PROCESSING,
                'location' => $reader?->location,
                'metadata' => $metadata,
            ]);

            // หักยอดเงินจากบัตร
            $card->deductBalance($amount);

            // อัพเดทการใช้งานล่าสุด
            $card->updateLastUsed(request()->ip());

            // Mark transaction as completed
            $transaction->markAsCompleted('Payment successful');

            DB::commit();

            Log::info('NFC Card payment processed', [
                'transaction_id' => $transaction->transaction_id,
                'card_id' => $card->id,
                'amount' => $amount,
            ]);

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();

            // สร้าง failed transaction
            $transaction = NFCTransaction::create([
                'nfc_card_id' => $card->id,
                'user_id' => $card->user_id,
                'nfc_reader_id' => $reader?->id,
                'type' => NFCTransaction::TYPE_PAYMENT,
                'amount' => $amount,
                'balance_before' => $card->balance,
                'balance_after' => $card->balance,
                'status' => NFCTransaction::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'location' => $reader?->location,
                'metadata' => $metadata,
            ]);

            Log::error('NFC Card payment failed', [
                'transaction_id' => $transaction->transaction_id,
                'card_id' => $card->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Top up card balance
     *
     * เติมเงินเข้าบัตร
     */
    public function topUpCard(
        NFCCard $card,
        float $amount,
        NFCReader $reader = null,
        array $metadata = []
    ): NFCTransaction {
        DB::beginTransaction();

        try {
            if (!$card->isActive() && $card->status !== NFCCard::STATUS_PENDING) {
                throw new Exception('Card is not active');
            }

            // สร้าง transaction
            $transaction = NFCTransaction::create([
                'nfc_card_id' => $card->id,
                'user_id' => $card->user_id,
                'nfc_reader_id' => $reader?->id,
                'type' => NFCTransaction::TYPE_TOPUP,
                'amount' => $amount,
                'balance_before' => $card->balance,
                'balance_after' => $card->balance + $amount,
                'status' => NFCTransaction::STATUS_PROCESSING,
                'location' => $reader?->location,
                'metadata' => $metadata,
            ]);

            // เพิ่มยอดเงินในบัตร
            $card->addBalance($amount);

            // อัพเดทสถานะบัตรถ้ายังเป็น pending
            if ($card->status === NFCCard::STATUS_PENDING) {
                $card->activate();
            }

            // Mark transaction as completed
            $transaction->markAsCompleted('Top up successful');

            DB::commit();

            Log::info('NFC Card topped up', [
                'transaction_id' => $transaction->transaction_id,
                'card_id' => $card->id,
                'amount' => $amount,
            ]);

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('NFC Card top up failed', [
                'card_id' => $card->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Top up failed: ' . $e->getMessage());
        }
    }

    /**
     * Block card
     */
    public function blockCard(NFCCard $card, string $reason, int $minutes = null): bool
    {
        try {
            $card->block($reason, $minutes);

            Log::info('NFC Card blocked', [
                'card_id' => $card->id,
                'reason' => $reason,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to block NFC card', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Unblock card
     */
    public function unblockCard(NFCCard $card): bool
    {
        try {
            $card->unblock();

            Log::info('NFC Card unblocked', ['card_id' => $card->id]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to unblock NFC card', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get card statistics
     */
    public function getCardStatistics(NFCCard $card): array
    {
        return [
            'total_transactions' => $card->transactions()->count(),
            'completed_transactions' => $card->completedTransactions()->count(),
            'total_spent' => $card->transactions()
                ->where('type', NFCTransaction::TYPE_PAYMENT)
                ->where('status', NFCTransaction::STATUS_COMPLETED)
                ->sum('amount'),
            'total_topped_up' => $card->transactions()
                ->where('type', NFCTransaction::TYPE_TOPUP)
                ->where('status', NFCTransaction::STATUS_COMPLETED)
                ->sum('amount'),
            'current_balance' => $card->balance,
            'last_transaction' => $card->transactions()
                ->latest()
                ->first(),
        ];
    }

    /**
     * Get card by card number
     */
    public function getCardByNumber(string $cardNumber): ?NFCCard
    {
        return NFCCard::where('card_number', $cardNumber)->first();
    }

    /**
     * Get cards by user
     */
    public function getCardsByUser(int $userId)
    {
        return NFCCard::where('user_id', $userId)->get();
    }
}
