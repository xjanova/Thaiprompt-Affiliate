<?php

namespace App\Services;

use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Services\FortunePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SMS Payment Service
 *
 * จัดการ business logic สำหรับระบบชำระเงินผ่าน SMS Checker
 * รวม: ประมวลผล notification, ถอดรหัส, ตรวจสอบลายเซ็น, สร้าง unique amount, ทำความสะอาดข้อมูล
 */
class SmsPaymentService
{
    /**
     * ประมวลผล SMS payment notification ที่ได้รับจาก Android App
     *
     * @param array $payload ข้อมูล payload ที่ถอดรหัสแล้ว
     * @param SmsCheckerDevice $device อุปกรณ์ที่ authenticate แล้ว
     * @param string $ipAddress IP ของ client
     * @return array ผลลัพธ์ success/failure
     */
    public function processNotification(array $payload, SmsCheckerDevice $device, string $ipAddress): array
    {
        return DB::transaction(function () use ($payload, $device, $ipAddress) {
            // ตรวจสอบ nonce ซ้ำ (ป้องกัน replay attack)
            $existingNonce = DB::table('sms_payment_nonces')
                ->where('nonce', $payload['nonce'])
                ->exists();

            if ($existingNonce) {
                Log::warning('SMS Payment: พบ nonce ซ้ำ (replay attack)', [
                    'nonce' => $payload['nonce'],
                    'device_id' => $device->device_id,
                ]);
                return [
                    'success' => false,
                    'message' => 'Duplicate request (nonce already used)',
                ];
            }

            // บันทึก nonce
            DB::table('sms_payment_nonces')->insert([
                'nonce' => $payload['nonce'],
                'device_id' => $device->device_id,
                'used_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // สร้าง notification record
            $notification = SmsPaymentNotification::create([
                'bank' => $payload['bank'],
                'type' => $payload['type'],
                'amount' => $payload['amount'],
                'account_number' => $payload['account_number'] ?? '',
                'sender_or_receiver' => $payload['sender_or_receiver'] ?? '',
                'reference_number' => $payload['reference_number'] ?? '',
                'sms_timestamp' => date('Y-m-d H:i:s', $payload['sms_timestamp'] / 1000),
                'device_id' => $device->device_id,
                'nonce' => $payload['nonce'],
                'status' => 'pending',
                'raw_payload' => json_encode($payload),
                'ip_address' => $ipAddress,
            ]);

            // อัพเดทกิจกรรมล่าสุดของอุปกรณ์
            $device->update([
                'last_active_at' => now(),
                'ip_address' => $ipAddress,
            ]);

            // พยายามจับคู่อัตโนมัติสำหรับ credit transactions
            $matched = false;
            $specialAmountHandled = false;

            if ($notification->type === 'credit') {
                // ขั้นที่ 1: จับคู่กับ UniquePaymentAmount / PaymentTransaction
                $autoConfirm = config('smschecker.auto_confirm_matched', true);
                $matched = $notification->attemptMatch($autoConfirm);

                // ขั้นที่ 2: ถ้าไม่ match → ตรวจว่าเป็นยอดพิเศษหรือไม่ (เช่น 29.99 = ดูดวง)
                if (!$matched) {
                    $specialAmountHandled = $this->handleSpecialAmount($notification);
                }
            }

            Log::info('SMS Payment: ประมวลผล notification สำเร็จ', [
                'notification_id' => $notification->id,
                'bank' => $notification->bank,
                'type' => $notification->type,
                'amount' => $notification->amount,
                'matched' => $matched,
                'special_amount' => $specialAmountHandled,
            ]);

            $message = $matched
                ? 'Payment matched and confirmed'
                : ($specialAmountHandled ? 'Special amount detected and processed' : 'Notification recorded');

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'notification_id' => $notification->id,
                    'status' => $notification->fresh()->status,
                    'matched' => $matched,
                    'special_amount' => $specialAmountHandled,
                    'matched_transaction_id' => $notification->matched_transaction_id,
                ],
            ];
        });
    }

    /**
     * ถอดรหัส payload ที่เข้ารหัสด้วย AES-256-GCM จาก Android App
     *
     * รูปแบบข้อมูล: Base64(IV[12 bytes] + Ciphertext + AuthTag[16 bytes])
     *
     * @param string $encryptedData ข้อมูลเข้ารหัส Base64
     * @param string $secretKey secret key ของอุปกรณ์
     * @return array|null payload ที่ถอดรหัสแล้ว หรือ null ถ้าล้มเหลว
     */
    public function decryptPayload(string $encryptedData, string $secretKey): ?array
    {
        try {
            $combined = base64_decode($encryptedData);
            if ($combined === false || strlen($combined) < 12) {
                return null;
            }

            $ivLength = 12;  // GCM IV = 12 bytes
            $tagLength = 16; // GCM auth tag = 16 bytes

            $iv = substr($combined, 0, $ivLength);
            $cipherTextWithTag = substr($combined, $ivLength);

            // แยก ciphertext และ auth tag
            $tag = substr($cipherTextWithTag, -$tagLength);
            $cipherText = substr($cipherTextWithTag, 0, -$tagLength);

            // SECURITY: ใช้ PBKDF2 สร้าง encryption key (ตรงกับ Android CryptoManager)
            $key = $this->deriveKey($secretKey, 'encryption');

            $decrypted = openssl_decrypt(
                $cipherText,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                Log::warning('SMS Payment: ถอดรหัสล้มเหลว (auth tag mismatch หรือ key ไม่ตรง)');
                return null;
            }

            $payload = json_decode($decrypted, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('SMS Payment: JSON ใน payload ไม่ถูกต้อง');
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            Log::error('SMS Payment: เกิดข้อผิดพลาดขณะถอดรหัส', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ตรวจสอบลายเซ็น HMAC-SHA256
     *
     * ลายเซ็น = HMAC-SHA256(encrypted_data + nonce + timestamp, hmacKey)
     * hmacKey ถูก derive แยกจาก encryption key ผ่าน PBKDF2
     *
     * @param string $data ข้อมูลที่ต้องตรวจสอบ
     * @param string $signature ลายเซ็นที่ได้รับจาก client (Base64)
     * @param string $secretKey secret key ของอุปกรณ์
     * @return bool ลายเซ็นถูกต้องหรือไม่
     */
    public function verifySignature(string $data, string $signature, string $secretKey): bool
    {
        // SECURITY: ใช้ dedicated HMAC key (แยกจาก encryption key)
        $hmacKey = $this->deriveKey($secretKey, 'hmac-signing');
        $expected = base64_encode(hash_hmac('sha256', $data, $hmacKey, true));
        return hash_equals($expected, $signature);
    }

    /**
     * Derive a strong key from secret using PBKDF2-SHA256
     *
     * ต้องตรงกับ Android CryptoManager.deriveKey() ทุกประการ:
     * - Algorithm: PBKDF2WithHmacSHA256
     * - Iterations: 100,000
     * - Key length: 256 bits (32 bytes)
     * - Salt: "thaiprompt-smschecker-v1:{context}"
     *
     * @param string $secret Secret key string
     * @param string $context Purpose context ('encryption' or 'hmac-signing')
     * @return string 32-byte derived key (raw binary)
     */
    private function deriveKey(string $secret, string $context = 'encryption'): string
    {
        $salt = "thaiprompt-smschecker-v1:{$context}";
        return hash_pbkdf2('sha256', $secret, $salt, 100000, 32, true);
    }

    /**
     * สร้าง unique payment amount สำหรับ transaction
     *
     * @param float $baseAmount ราคาสินค้าเดิม
     * @param int|null $transactionId ID ของ transaction
     * @param string $transactionType ประเภท transaction
     * @param int $expiryMinutes เวลาหมดอายุ (นาที)
     * @return UniquePaymentAmount|null
     */
    public function generateUniqueAmount(
        float $baseAmount,
        ?int $transactionId = null,
        string $transactionType = 'order',
        int $expiryMinutes = null
    ): ?UniquePaymentAmount {
        $expiryMinutes = $expiryMinutes ?? config('smschecker.unique_amount_expiry', 30);

        return UniquePaymentAmount::generate(
            $baseAmount,
            $transactionId,
            $transactionType,
            $expiryMinutes
        );
    }

    /**
     * ดึง notifications ที่ยังไม่จับคู่
     *
     * @param int $limit จำนวนสูงสุด
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingNotifications(int $limit = 50)
    {
        return SmsPaymentNotification::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * ทำความสะอาดข้อมูลที่หมดอายุ
     *
     * - ยกเลิก PaymentTransaction และ Order ที่ unique amount หมดอายุ (30 นาที)
     * - ล้าง unique amounts ที่หมดอายุ → สถานะ 'expired'
     * - ลบ nonces เก่า (ตามค่า config nonce_expiry_hours)
     * - ล้าง pending notifications เก่า (> 7 วัน) → สถานะ 'expired'
     *
     * @return array สถิติการทำความสะอาด
     */
    public function cleanup(): array
    {
        $stats = [
            'cancelled_transactions' => 0,
            'cancelled_orders' => 0,
            'expired_amounts' => 0,
            'deleted_nonces' => 0,
            'expired_notifications' => 0,
        ];

        // ========================================
        // ขั้นที่ 1: ยกเลิก PaymentTransaction และ Order ที่หมดเวลาชำระ (30 นาที)
        // ========================================

        // ดึง unique amounts ที่หมดอายุและยังเป็น 'reserved'
        $expiredUniqueAmounts = UniquePaymentAmount::where('status', 'reserved')
            ->where('expires_at', '<=', now())
            ->with('transaction.order')
            ->get();

        foreach ($expiredUniqueAmounts as $uniqueAmount) {
            // ยกเลิก PaymentTransaction ถ้ายังเป็น pending/processing
            if ($uniqueAmount->transaction && in_array($uniqueAmount->transaction->status, ['pending', 'processing'])) {
                $uniqueAmount->transaction->update([
                    'status' => 'expired',
                    'notes' => 'หมดเวลาชำระเงิน (30 นาที) - ยกเลิกโดยระบบอัตโนมัติ',
                ]);
                $stats['cancelled_transactions']++;

                Log::info('SMS Payment: ยกเลิก PaymentTransaction หมดเวลา', [
                    'transaction_id' => $uniqueAmount->transaction->id,
                    'amount' => $uniqueAmount->unique_amount,
                ]);

                // ยกเลิก Order ถ้ายังเป็น pending และยังไม่ได้ชำระ
                if ($uniqueAmount->transaction->order &&
                    $uniqueAmount->transaction->order->status === 'pending' &&
                    $uniqueAmount->transaction->order->payment_status !== 'paid') {

                    $uniqueAmount->transaction->order->update([
                        'status' => 'cancelled',
                        'payment_status' => 'expired',
                        'cancellation_reason' => 'หมดเวลาชำระเงิน (30 นาที) - ระบบยกเลิกอัตโนมัติ',
                    ]);
                    $stats['cancelled_orders']++;

                    Log::info('SMS Payment: ยกเลิก Order หมดเวลา', [
                        'order_id' => $uniqueAmount->transaction->order->id,
                        'order_number' => $uniqueAmount->transaction->order->order_number,
                    ]);
                }
            }

            // อัปเดต unique amount เป็น expired
            $uniqueAmount->update(['status' => 'expired']);
            $stats['expired_amounts']++;
        }

        // ========================================
        // ขั้นที่ 2: ลบ nonces เก่า
        // ========================================

        $nonceExpiryHours = config('smschecker.nonce_expiry_hours', 24);
        $stats['deleted_nonces'] = DB::table('sms_payment_nonces')
            ->where('used_at', '<', now()->subHours($nonceExpiryHours))
            ->delete();

        // ========================================
        // ขั้นที่ 3: ล้าง pending notifications เก่า (> 7 วัน)
        // ========================================

        $stats['expired_notifications'] = SmsPaymentNotification::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->update(['status' => 'expired']);

        Log::info('SMS Payment: ทำความสะอาดข้อมูลสำเร็จ', $stats);

        return $stats;
    }

    /**
     * ตรวจจับและจัดการยอดเงินพิเศษ (เช่น 29.99 = ดูดวง)
     *
     * ถ้ายอดตรงกับ config 'smschecker.special_amounts':
     * - สร้าง FortuneReading อัตโนมัติ (พร้อมจ่ายแล้ว)
     * - ถ้าจับคู่ User ได้ → เชื่อมกับ user_id
     * - ถ้าจับคู่ไม่ได้ → สร้างเป็น "บิลลอย" รอ admin assign
     *
     * @param SmsPaymentNotification $notification
     * @return bool มีการจัดการยอดพิเศษหรือไม่
     */
    protected function handleSpecialAmount(SmsPaymentNotification $notification): bool
    {
        $config = FortunePaymentService::findSpecialAmount((float) $notification->amount);

        if ($config === null) {
            return false;
        }

        Log::info('SMS Payment: ตรวจจับยอดพิเศษ', [
            'amount' => $notification->amount,
            'type' => $config['type'],
            'name' => $config['name'],
        ]);

        // จัดการตามประเภท
        if ($config['type'] === 'fortune_reading') {
            $fortunePaymentService = app(FortunePaymentService::class);
            $fortunePaymentService->createFromSmsNotification($notification, $config);
            return true;
        }

        // รองรับประเภทอื่นในอนาคต
        Log::warning('SMS Payment: ยอดพิเศษประเภทไม่รู้จัก', [
            'type' => $config['type'],
        ]);

        return false;
    }
}
