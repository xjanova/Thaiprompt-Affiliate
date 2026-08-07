<?php

namespace App\Services;

use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentTransaction;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
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
     * @param  array  $payload  ข้อมูล payload ที่ถอดรหัสแล้ว
     * @param  SmsCheckerDevice  $device  อุปกรณ์ที่ authenticate แล้ว
     * @param  string  $ipAddress  IP ของ client
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
            $fortuneReadingHandled = false;
            $specialAmountHandled = false;
            $matchedModel = null; // เก็บ model ที่ match ได้เพื่อส่งกลับให้แอพแสดงบิล
            $matchedModelType = null;

            if ($notification->type === 'credit') {
                // ขั้นที่ 1: ตรวจสอบว่าเป็นยอดดูดวง (unique amount ที่สร้างจาก conversation)
                $fortuneReadingHandled = $this->handleFortuneReadingPayment($notification);

                // ดึง FortuneReading ที่ match ได้ เพื่อส่งกลับให้แอพแสดงบิล
                if ($fortuneReadingHandled && $notification->matched_transaction_id) {
                    $matchedModel = FortuneReading::find($notification->matched_transaction_id);
                    $matchedModelType = 'fortune_reading';
                }

                if (! $fortuneReadingHandled) {
                    // ขั้นที่ 2: จับคู่กับ UniquePaymentAmount / PaymentTransaction (อีคอมเมิร์ซ)
                    $autoConfirm = config('smschecker.auto_confirm_matched', true);
                    $matched = $notification->attemptMatch($autoConfirm);

                    // ดึง PaymentTransaction ที่ match ได้
                    if ($matched && $notification->matched_transaction_id) {
                        $matchedModel = PaymentTransaction::find($notification->matched_transaction_id);
                        $matchedModelType = 'payment_transaction';
                    }
                }

                if (! $fortuneReadingHandled && ! $matched) {
                    // ขั้นที่ 3: ตรวจจับยอดพิเศษ (เช่น 29.99 = ดูดวง)
                    // สร้าง FortuneReading อัตโนมัติเป็น "บิลลอย" ถ้า match ไม่ได้กับ unique amount
                    $specialAmountHandled = $this->handleSpecialAmount($notification);

                    if ($specialAmountHandled && $notification->matched_transaction_id) {
                        $matchedModel = FortuneReading::find($notification->matched_transaction_id);
                        $matchedModelType = 'fortune_reading';
                    }
                }

                // 🚨 Orphan fortune payment guard — ยอดอยู่ในช่วงดูดวงแต่ไม่มีบิลจับคู่
                //    เคสที่เกิด: บิลถูกยกเลิกไปแล้ว (auto/user) → ลูกค้ามาจ่ายข้ามวัน → SMS เข้า
                //    → ระบบไม่ match (เพราะ UPA cancelled/expired เกิน grace) → เงินค้าง
                //    Fix: flag notification + ส่ง FCM push ให้ admin ตรวจสอบ + จ่ายคืน/สร้าง reading manual
                if (! $fortuneReadingHandled && ! $matched && ! $specialAmountHandled) {
                    $this->flagOrphanFortunePayment($notification);
                }
            }

            Log::info('SMS Payment: ประมวลผล notification สำเร็จ', [
                'notification_id' => $notification->id,
                'bank' => $notification->bank,
                'type' => $notification->type,
                'amount' => $notification->amount,
                'matched' => $matched,
                'fortune_reading' => $fortuneReadingHandled,
                'special_amount' => $specialAmountHandled,
            ]);

            $message = $fortuneReadingHandled
                ? 'Fortune reading payment matched and processed'
                : ($matched ? 'Payment matched and confirmed'
                    : ($specialAmountHandled ? 'Special amount detected and processed'
                        : 'Notification recorded'));

            // ✅ matched ต้องเป็น true ทุกกรณีที่จับคู่ได้ (ทั้ง fortune + ecommerce + special)
            // เพื่อให้ Android app รู้ว่ามี order ใน response (app เช็ค data.matched)
            $anyMatched = $matched || $fortuneReadingHandled || $specialAmountHandled;

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'notification_id' => $notification->id,
                    'status' => $notification->fresh()->status,
                    'matched' => $anyMatched,
                    'fortune_reading' => $fortuneReadingHandled,
                    'special_amount' => $specialAmountHandled,
                    'matched_transaction_id' => $notification->matched_transaction_id,
                ],
                // ส่ง matched model กลับเพื่อให้ controller แปลงเป็น RemoteOrderApproval ให้แอพแสดง
                'matched_model' => $matchedModel,
                'matched_model_type' => $matchedModelType,
            ];
        });
    }

    /**
     * ถอดรหัส payload ที่เข้ารหัสด้วย AES-256-GCM จาก Android App
     *
     * รูปแบบข้อมูล: Base64(IV[12 bytes] + Ciphertext + AuthTag[16 bytes])
     *
     * @param  string  $encryptedData  ข้อมูลเข้ารหัส Base64
     * @param  string  $secretKey  secret key ของอุปกรณ์
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
     * @param  string  $data  ข้อมูลที่ต้องตรวจสอบ
     * @param  string  $signature  ลายเซ็นที่ได้รับจาก client (Base64)
     * @param  string  $secretKey  secret key ของอุปกรณ์
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
     * @param  string  $secret  Secret key string
     * @param  string  $context  Purpose context ('encryption' or 'hmac-signing')
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
     * @param  float  $baseAmount  ราคาสินค้าเดิม
     * @param  int|null  $transactionId  ID ของ transaction
     * @param  string  $transactionType  ประเภท transaction
     * @param  int  $expiryMinutes  เวลาหมดอายุ (นาที)
     */
    public function generateUniqueAmount(
        float $baseAmount,
        ?int $transactionId = null,
        string $transactionType = 'order',
        ?int $expiryMinutes = null
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
     * @param  int  $limit  จำนวนสูงสุด
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
            'expired_fortune_readings' => 0,
            'deleted_nonces' => 0,
            'expired_notifications' => 0,
        ];

        // ========================================
        // ขั้นที่ 1: ยกเลิก PaymentTransaction และ Order ที่หมดเวลาชำระ (30 นาที)
        // ========================================

        // ดึง unique amounts ที่หมดอายุและยังเป็น 'reserved' (ไม่รวม fortune_reading)
        // fortune_reading มี grace period แยก → จัดการใน ขั้นที่ 1.5
        $expiredUniqueAmounts = UniquePaymentAmount::where('status', 'reserved')
            ->where('expires_at', '<=', now())
            ->where('transaction_type', '!=', 'fortune_reading')
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
        // ขั้นที่ 1.5: ล้าง FortuneReading ที่หมดเวลา (หลัง grace period)
        // ========================================
        // fortune_reading unique amounts หมดอายุ 60 นาที + grace period 30 นาที = 90 นาที
        // หลัง grace period → expire unique amount + ปิด FortuneReading conversation

        $expiredFortuneAmounts = UniquePaymentAmount::where('status', 'reserved')
            ->where('transaction_type', 'fortune_reading')
            ->where('expires_at', '<=', now()->subMinutes(30)) // grace period 30 นาที
            ->get();

        // ⚡ FCM service สำหรับแจ้งแอพ smschecker (best-effort)
        $fcmService = null;
        try {
            $fcmService = app(\App\Services\FcmNotificationService::class);
        } catch (\Throwable $e) {
            Log::warning('SmsPaymentService::cleanup — FCM service unavailable', [
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($expiredFortuneAmounts as $uniqueAmount) {
            // ปิด FortuneReading ที่ยังรอชำระ
            $expiredReadings = FortuneReading::where('unique_payment_amount_id', $uniqueAmount->id)
                ->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)
                ->get();

            foreach ($expiredReadings as $reading) {
                // 🏷️ ระบุประเภทการยกเลิก = หมดอายุหลัง grace period (Phase J cron พลาดหรือยังไม่ทัน)
                $reading->setConversationState('cancelled_at', now()->toIso8601String());
                $reading->setConversationState('cancellation_reason', 'auto_expired_grace');

                $reading->update([
                    'conversation_status' => FortuneReading::STATUS_COMPLETED,
                ]);
                $stats['expired_fortune_readings']++;

                Log::info('SMS Payment: ปิดบิลดูดวงหมดเวลา (หลัง grace period)', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'unique_amount' => $uniqueAmount->unique_amount,
                    'cancellation_reason' => 'auto_expired_grace',
                ]);

                // 📡 แจ้ง smschecker app ทันที — กันบิลค้างใน UI รอ admin อนุมัติ
                if ($fcmService) {
                    try {
                        $fcmService->notifyFortuneReadingCancelled($reading);
                    } catch (\Throwable $fcmErr) {
                        Log::warning('SMS Payment: FCM cancelled push ล้ม (best-effort)', [
                            'reading_id' => $reading->id,
                            'error' => $fcmErr->getMessage(),
                        ]);
                    }
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

    /**
     * 🚨 Flag orphan fortune payment — ยอดอยู่ในช่วงดูดวงแต่ไม่มีบิลจับคู่
     *
     * เคสที่เกิดขึ้น:
     *   - บิลถูก cancel/expired ไปแล้ว (UPA cancelled / expired เกิน grace 60 นาที)
     *   - ลูกค้ามาโอนข้ามวัน → SMS เข้า → ระบบไม่ match (handleFortuneReadingPayment fail)
     *   - หา special_amount ก็ไม่ตรง (เพราะลูกค้าโอนตามยอดบิลเก่าที่ unique)
     *   - ผลลัพธ์เดิม: SMS notification status='pending' ค้างไว้ admin ไม่รู้
     *
     * Fix:
     *   1. เช็ค amount ในช่วง fortune (deep_reading_price ± 1 บาท — รองรับ unique decimal suffix .00-.99)
     *   2. Mark notification ใน metadata: requires_admin_review + reason
     *   3. ส่ง FCM push 'orphan_fortune_payment' ให้ admin device — เด้งแจ้งเตือนทันที
     *   4. Log critical สำหรับ ops monitoring
     *
     * smschecker app หน้าที่: รับ FCM push → แสดงเป็น "บิลค้างต้องตรวจสอบ" ใน admin queue
     */
    protected function flagOrphanFortunePayment(SmsPaymentNotification $notification): void
    {
        $amount = (float) $notification->amount;

        // ดึง fortune price จาก settings (รองรับ admin เปลี่ยนราคา)
        $fortunePrice = 0.0;
        try {
            $settings = FortuneTellingSetting::getSettings();
            $fortunePrice = (float) ($settings->deep_reading_price ?? 0);
            if ($fortunePrice <= 0) {
                $fortunePrice = (float) ($settings->reading_price ?? 0);
            }
        } catch (\Throwable $e) {
            // ignore — fallback ด้านล่าง
        }
        if ($fortunePrice <= 0) {
            $fortunePrice = 39.0; // default
        }

        // Range check: amount ต้องอยู่ในช่วง [price, price + 1]
        // เช่น price=39 → match 39.00 ถึง 39.99 (รองรับ unique decimal suffix)
        // กัน edge: ถ้า admin ตั้งราคา 40 → match 40.00 ถึง 40.99
        if ($amount < $fortunePrice || $amount >= ($fortunePrice + 1.0)) {
            return; // ยอดไม่อยู่ในช่วง fortune — ไม่ใช่ orphan ของระบบนี้
        }

        // Mark notification — admin จะเห็นใน UI พร้อม flag
        // 🩹 (2026-05-05) Defensive: enum 'requires_admin_review' อาจยังไม่ถูก migrate
        //                  → ลอง update เต็ม → ถ้า SQL truncation error → fallback แค่ raw_data
        //                  → กัน exception bubble up ทำให้ payment flow พัง
        $rawDataMerged = array_merge((array) ($notification->raw_data ?? []), [
            'orphan_fortune_payment' => true,
            'orphan_reason' => 'amount_in_fortune_range_but_no_matching_bill',
            'expected_price_range' => [$fortunePrice, $fortunePrice + 0.99],
            'flagged_at' => now()->toIso8601String(),
        ]);

        try {
            $notification->update([
                'status' => 'requires_admin_review',
                'raw_data' => $rawDataMerged,
            ]);
        } catch (\Illuminate\Database\QueryException $sqlErr) {
            // เคสจริง production (2026-05-05): enum status ยังไม่มี 'requires_admin_review'
            //   → "Data truncated for column 'status'" → notification ยังเป็น pending
            //   → fallback เก็บแค่ raw_data + log critical (admin queue ใช้ raw_data flag ก็ได้)
            Log::error('SMS Payment: status enum migration ขาด — fallback to raw_data only', [
                'notification_id' => $notification->id,
                'sql_error' => $sqlErr->getMessage(),
                'hint' => 'ต้องรัน migration 2026_05_05_000100_add_requires_admin_review_to_sms_notifications_status_enum',
            ]);
            try {
                $notification->update(['raw_data' => $rawDataMerged]);
            } catch (\Throwable $rawErr) {
                Log::critical('SMS Payment: fallback raw_data update ก็ล้ม — admin ต้อง check log', [
                    'notification_id' => $notification->id,
                    'error' => $rawErr->getMessage(),
                ]);
            }
        }

        Log::critical('🚨 SMS Payment: Orphan fortune payment — ลูกค้าโอนแต่ไม่มีบิล (admin ต้องตรวจ)', [
            'notification_id' => $notification->id,
            'amount' => $amount,
            'expected_range' => "{$fortunePrice}-".($fortunePrice + 0.99),
            'bank' => $notification->bank,
            'sender' => $notification->sender_or_receiver,
            'sms_timestamp' => $notification->sms_timestamp,
        ]);

        // 📡 ส่ง FCM push ให้ admin device — เด้งแจ้งเตือนทันที
        try {
            app(FcmNotificationService::class)->notifyOrphanFortunePayment($notification, $fortunePrice);
        } catch (\Throwable $fcmErr) {
            Log::warning('SMS Payment: FCM orphan payment alert ล้ม (best-effort)', [
                'notification_id' => $notification->id,
                'error' => $fcmErr->getMessage(),
            ]);
        }
    }

    /**
     * ตรวจจับและจัดการยอดเงินดูดวงจาก Conversational Flow
     *
     * ตรวจสอบว่ายอดเงินตรงกับ FortuneReading ที่รอชำระเงินหรือไม่
     * ถ้าตรง → ยืนยันการชำระ → ทำนายละเอียด → ส่งผ่าน Platform ที่ใช้งาน (Facebook/LINE)
     *
     * @return bool มีการจัดการหรือไม่
     */
    protected function handleFortuneReadingPayment(SmsPaymentNotification $notification): bool
    {
        // 🔒 GUARD #1 (2026-04-28): SMS notification นี้ถูก match ไปบิลอื่นแล้ว → ห้าม reuse
        // กฎทอง: 1 SMS ใช้ได้กับ 1 บิล เท่านั้น
        // เคสที่กัน: notification ถูก reprocess (เช่น admin คลิก retry, queue replay)
        if ($notification->matched_transaction_id !== null) {
            Log::warning('🚫 SMS Payment: notification ถูก match แล้ว — ปฏิเสธ reuse', [
                'notification_id' => $notification->id,
                'matched_transaction_id' => $notification->matched_transaction_id,
                'matched_status' => $notification->status,
            ]);

            return false;
        }

        $amount = (float) $notification->amount;
        // 🔒 SMS timestamp — ใช้กัน SMS ที่มาก่อนบิลถูกสร้าง
        $smsTimestamp = $notification->sms_timestamp ?? $notification->created_at;

        // ขั้นที่ 1: ค้นหา FortuneReading ที่รอชำระเงินด้วย unique amount ที่ยังไม่หมดอายุ
        // ⭐ ส่ง smsTimestamp เพื่อกัน bill ใหม่ที่ยอดตรงแต่สร้างหลัง SMS
        $reading = FortuneReading::findByUniqueAmount($amount, $smsTimestamp);

        // ขั้นที่ 2: Grace period — ค้นหา unique amount ที่เพิ่งหมดอายุ (ภายใน 30 นาที)
        // กรณีลูกค้าโอนช้ากว่าเวลาที่กำหนด แต่ยังอยู่ใน grace period
        if (! $reading) {
            $reading = $this->findFortuneReadingByExpiredAmount($amount, $smsTimestamp);

            if ($reading) {
                Log::info('SMS Payment: พบ Fortune Reading ผ่าน grace period (unique amount หมดอายุแล้ว)', [
                    'notification_id' => $notification->id,
                    'reading_id' => $reading->id,
                    'amount' => $amount,
                ]);
            }
        }

        // 🚫 REMOVED (2026-04-28): Step 3 amount_paid fallback
        // เดิม: where('amount_paid', $amount) → match ใครก็ได้ที่บิลตรง 90 นาที
        // อันตรายเกินไป — ถ้า UPA ของบิลเก่าถูก cleanup แต่บิลใหม่ยอดตรง → match ผิด
        // หลัง remove: ถ้า UPA หาย → ไป orphan flag ตามปกติ (admin แก้มือ ปลอดภัยกว่า)

        if (! $reading) {
            return false;
        }

        // Recovery: ถ้า reading ถูก cleanup ปิดไปแล้ว (completed) แต่ยังไม่ได้จ่าย → กู้คืนกลับมา
        // แยกเช็คตาม reading_type เพราะ Celtic Cross ใช้ status คนละชุด
        $expectedPendingStatus = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
            ? FortuneReading::STATUS_CELTIC_PENDING_PAYMENT
            : FortuneReading::STATUS_PENDING_PAYMENT;

        if ($reading->conversation_status !== $expectedPendingStatus && ! $reading->is_paid) {
            Log::info('SMS Payment: กู้คืน Fortune Reading ที่หมดอายุ/ถูกปิด กลับเป็น pending_payment', [
                'reading_id' => $reading->id,
                'old_status' => $reading->conversation_status,
                'expected_status' => $expectedPendingStatus,
                'amount' => $amount,
            ]);
            $reading->update(['conversation_status' => $expectedPendingStatus]);
        }

        // ระบุ platform และ user ID ที่จะส่งข้อความ
        // ✅ ตรวจจับ LINE user จาก ID format (U + 32 hex chars) เป็น fallback
        // ป้องกันกรณี reading เก่าที่ยังไม่มี platform field
        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
        $platform = $reading->platform;
        if (! $platform) {
            $platform = (preg_match('/^U[0-9a-f]{32}$/i', $userId)) ? 'line' : 'facebook';
        }

        Log::info('SMS Payment: พบ Fortune Reading ที่รอชำระ', [
            'notification_id' => $notification->id,
            'reading_id' => $reading->id,
            'amount' => $amount,
            'platform' => $platform,
            'user_id' => $userId,
        ]);

        // 🩹 (2026-05-09 audit fix P3) Cache::lock + idempotency flag
        //    เคสเดิม: 2 SMS notifications สำหรับ payment เดียว (เช่น K Bank ฝาก/รับ push 2 ครั้ง)
        //    → ทั้งคู่ผ่าน matched_transaction_id IS NULL check (คนละ notification row)
        //    → confirmPayment idempotent (paid_at คงที่) แต่ side effects (push msg + FCM +
        //      dispatch Job) ยิงซ้ำ → ลูกค้าเห็น "ระบบตัดบิลเรียบร้อย" 2 ครั้ง
        //    Fix: lock เฉพาะ reading.id + ตั้ง flag sms_match_processed → idempotent ทุก path
        // 🩹 (self-review H1) wrap Cache::lock ใน try/catch — กัน Redis outage block payment
        $lock = null;
        $lockAcquired = false;
        try {
            $lock = \Illuminate\Support\Facades\Cache::lock("sms-fortune-match:{$reading->id}", 30);
            $lockAcquired = $lock->get();
        } catch (\Throwable $cacheErr) {
            Log::warning('🔒 SMS Payment: Cache::lock failed — proceeding WITHOUT dedup (cache outage)', [
                'reading_id' => $reading->id,
                'notification_id' => $notification->id,
                'error' => $cacheErr->getMessage(),
            ]);
            // Continue without lock — better than blocking payment matching
        }

        // Lock created แต่ไม่ได้ → ถูกถือโดย notification อื่น (concurrent) → dedup skip
        if ($lock !== null && ! $lockAcquired) {
            Log::info('🔒 SMS Payment: lock held by another notification — dedup skip', [
                'notification_id' => $notification->id,
                'reading_id' => $reading->id,
            ]);

            // Mark this notification as matched (เพื่อ SMS app แสดง status ถูก) แต่ไม่ทำงานซ้ำ
            $notification->update([
                'status' => 'matched',
                'matched_transaction_id' => $reading->id,
            ]);

            return true;
        }

        try {
            $reading->refresh();
            if ($reading->getConversationState('sms_match_processed', false)) {
                Log::info('🔒 SMS Payment: side effects already fired by previous notification — dedup skip', [
                    'notification_id' => $notification->id,
                    'reading_id' => $reading->id,
                    'previous_processed_at' => $reading->getConversationState('sms_match_processed_at'),
                ]);

                $notification->update([
                    'status' => 'matched',
                    'matched_transaction_id' => $reading->id,
                ]);

                return true;
            }

            // ✨ Mark processed helper — เรียกก่อน return true ทุก path กัน duplicate ที่
            //    มาทีหลัง (lock acquired แล้วเห็น flag → skip)
            $markProcessed = function () use ($reading) {
                $reading->setConversationState('sms_match_processed', true);
                $reading->setConversationState('sms_match_processed_at', now()->toIso8601String());
            };

            // ✅ ยืนยันการชำระเงินทันที (ก่อน dispatch job)
            // เพื่อให้ response กลับไปแอพ SMS Checker แสดงสถานะ "auto_approved" ทันที
            // ไม่ใช่ค้างที่ "pending_review" จนกว่า job จะรัน confirmPayment()
            $reading->confirmPayment($notification);

            // 🛡️ (2026-05-05) Clear spam guards — ลูกค้าจ่ายเงินแล้ว ห้ามถูก silent_mode ดัก
            //   เคส: ลูกค้ารัวข้อความก่อนจ่าย → silent_mode triggered → จ่ายแล้ว push prompt
            //   → ลูกค้าตอบ "พร้อม" → silent_mode ยังอยู่ → บอทเงียบ
            //   Fix: clear silent + rapid + paid_active cache ทุกครั้งที่ payment matched
            \Illuminate\Support\Facades\Cache::forget("fortune:silent:{$userId}");
            \Illuminate\Support\Facades\Cache::forget("fortune:rapid:{$userId}");
            \Illuminate\Support\Facades\Cache::forget("fortune:has_paid_active:{$userId}");

            Log::info('SMS Payment: confirmPayment ทันที (ก่อน dispatch job)', [
                'reading_id' => $reading->id,
                'notification_id' => $notification->id,
                'is_paid' => $reading->is_paid,
                'conversation_status' => $reading->conversation_status,
                'reading_type' => $reading->reading_type,
            ]);

            // 🔮 Celtic Cross fork — push "ตัดบิลแล้ว + เปิดไพ่ใบ 1/10" ทันที (ไม่ต้องรอ AI generate)
            if ($reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS) {
                $celticResult = $this->handleCelticPaymentMatched($reading, $notification, $platform, $userId, $amount);
                $markProcessed();

                return $celticResult;
            }

            // 💰 (2026-05-10) Deep 39 Pay-First fork — จ่ายก่อนเก็บข้อมูล
            //   เดิม: ตรวจ flag pay_first_mode + ไม่มี birth_date → ขอวันเกิดต่อ
            //   ปัญหา (2026-05-13 user report): บิลที่สร้างก่อน $payFirst fix → flag ไม่ถูก set
            //     → fall through → dispatch AI Job → AI gen โดยไม่มีวันเกิด → fail → ส่ง error
            //     "😔 ขออภัยค่ะ ระบบ AI ขัดข้องชั่วคราว..." ให้ลูกค้า
            //   ใหม่: ตรวจ data presence แทน flag — ถ้า Deep + จ่ายแล้วแต่ไม่มี birth_date → ขอวันเกิด
            //         (ครอบคลุมทั้งบิลใหม่ที่มี flag + บิลเก่าที่ไม่มี flag)
            $isDeepReading = $reading->reading_type === FortuneReading::READING_TYPE_DEEP;
            $hasNoBirthdate = empty($reading->birth_date);
            if ($isDeepReading && $hasNoBirthdate) {
                // ตั้ง flag ให้บิลนี้รู้ว่า pay-first (สำหรับ flow ต่อเนื่อง — Job retry, scheduler ฯลฯ)
                $reading->setConversationState('pay_first_mode', true);

                $payFirstResult = $this->handleDeepPayFirstPaymentMatched($reading, $notification, $platform, $userId, $amount);
                $markProcessed();

                return $payFirstResult;
            }

            // ✅ Push แจ้งผู้ใช้ทันทีว่า "ชำระเงินเรียบร้อย กำลังวิเคราะห์ดวง"
            // ใช้ message_tag: POST_PURCHASE_UPDATE เพื่อ push นอก messaging window ได้
            $reading->setConversationState('wait_message_sent', true);
            $reading->setConversationState('wait_message_sent_at', now()->toIso8601String());

            try {
                $settings = FortuneTellingSetting::getSettings();
                $channelManager = new FortuneChannelManager($settings);

                $userName = $reading->facebook_user_name ?? 'คุณ';
                $billRef = $reading->bill_reference ?? '-';
                $paymentConfirmedMessage = "✅ ระบบตัดบิลเรียบร้อยแล้วค่ะ คุณ{$userName}\n\n"
                    ."🔖 เลขที่บิล: {$billRef}\n"
                    .'💰 ยอดที่ได้รับ: ฿'.number_format($amount, 2)."\n\n"
                    ."═══════════════════════\n"
                    ."🌙 *แม่หมอจันทรากำลังคำนวณดวงดาวให้*\n"
                    ."═══════════════════════\n\n"
                    ."✨ กำลังเปิดดาวเจ้าชนะของเจ้าชะตา\n"
                    ."🃏 เรียงไพ่ยิปซีตามพลังจิตที่เลือก\n"
                    ."🔮 รวบรวมพลังจักรวาลเข้าสู่คำทำนาย\n\n"
                    ."⏳ ใช้เวลา 1-3 นาที — ขอให้เจ้าชะตารอสักครู่\n"
                    .'จะส่งคำทำนายให้ทันทีเมื่อพร้อมนะคะ 🙏';

                $pushSent = $channelManager->sendResponse($platform, $userId, [
                    'action' => 'payment_confirmed_wait',
                    'message' => $paymentConfirmedMessage,
                    'reading' => $reading,
                ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                Log::info('SMS Payment: push แจ้ง "ชำระเงินเรียบร้อย" สำเร็จ', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'sent' => $pushSent,
                ]);

                // ⏳ แสดง loading animation บน LINE ให้ลูกค้ารู้ว่าบอทกำลังทำงาน (ไม่ได้เงียบ)
                // Animation นี้จะแสดง 60 วินาที — Job retry/check-pending จะเติมต่อถ้านานกว่านั้น
                if ($platform === 'line') {
                    try {
                        $lineService = new \App\Services\LineFortuneService($settings);
                        $lineService->showLoadingAnimation($userId, 60);
                    } catch (\Exception $loadingErr) {
                        // ไม่ critical — ถ้า loading animation ล้มเหลวก็ไม่ต้องหยุดโฟล
                        Log::debug('SMS Payment: LINE loading animation ล้มเหลว (ไม่ critical)', [
                            'error' => $loadingErr->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $pushErr) {
                // Push ล้มเหลวไม่ critical — ผู้ใช้จะได้รับแจ้งเมื่อส่งข้อความมา
                Log::warning('SMS Payment: push แจ้ง "ชำระเงินเรียบร้อย" ล้มเหลว (ไม่ critical)', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'error' => $pushErr->getMessage(),
                ]);
            }

            // ✅ เช็คว่ามีคำทำนายพร้อมแล้วหรือยัง → ตั้ง flag เพื่อให้ replyMessage ส่งได้
            $reading->refresh();
            if (! empty($reading->deep_response) && ! $reading->getConversationState('reading_sent_directly', false)) {
                $reading->setConversationState('reading_ready_for_reply', true);
                $reading->setConversationState('reading_ready_at', now()->toIso8601String());

                Log::info('SMS Payment: คำทำนายพร้อมแล้ว → ตั้ง flag reading_ready_for_reply', [
                    'reading_id' => $reading->id,
                ]);
            }

            // 🌙 (2026-05-08 v3) Quiet Period — กันลูกค้ารัวข้อความระหว่าง AI gen
            //   ลูกค้าโอนเงินแล้วใจร้อน รัวพิมพ์ "ทำนายให้แล้ว?" / "เร็วหน่อย" หลายข้อความ
            //   bot ตอบทุกข้อความ → คำทำนายที่กำลัง gen ถูก spam messages เลื่อนหายในแชท
            //   Fix: set flag → processMessage silent skip + announce 1 ครั้งต่อ 60 วิ
            //   Clear flag: ตอน processPaymentConfirmed return success (predictions ส่งหมดแล้ว)
            try {
                \Illuminate\Support\Facades\Cache::put(
                    "fortune:gen_processing:{$userId}",
                    ['reading_id' => $reading->id, 'started_at' => now()->toIso8601String()],
                    now()->addMinutes(5)
                );
            } catch (\Throwable $cacheErr) {
                // Cache fail = ไม่ block flow ของ payment matching
                Log::debug('SMS Payment: gen_processing flag set fail (non-blocking)', [
                    'error' => $cacheErr->getMessage(),
                ]);
            }

            // Dispatch background job → ไม่ติด web server timeout / SMS webhook timeout
            // Job จะ: สร้าง chart → สร้างคำทำนาย 2 ข้อ → ส่ง Messenger → save DB
            // (confirmPayment() ถูกเรียกแล้วด้านบน — job จะเรียกซ้ำก็ไม่เป็นไร เป็น idempotent)
            // ⚠️ อัพเดท notification เป็น matched หลัง dispatch สำเร็จ (ไม่ใช่ก่อน)
            try {
                ProcessDeepFortuneReadingJob::dispatchSmart(
                    $reading->id, $notification->id, $platform, $userId
                );

                // Dispatch สำเร็จ → อัพเดท notification เป็น matched
                $notification->update([
                    'status' => 'matched',
                    'matched_transaction_id' => $reading->id,
                ]);

                Log::info('SMS Payment: dispatch ProcessDeepFortuneReadingJob สำเร็จ', [
                    'reading_id' => $reading->id,
                    'notification_id' => $notification->id,
                    'platform' => $platform,
                    'user_id' => $userId,
                ]);

                // ส่ง FCM push ให้แอพอัพเดทสถานะทันที (จาก "รอจับคู่" → "ชำระแล้ว")
                try {
                    app(FcmNotificationService::class)->notifyFortuneReadingMatched($reading, $notification);
                } catch (\Exception $fcmErr) {
                    Log::warning('SMS Payment: FCM push fortune_reading_matched ล้มเหลว (ไม่ critical)', [
                        'error' => $fcmErr->getMessage(),
                    ]);
                }

                $markProcessed();

                return true;

            } catch (\Exception $e) {
                Log::error('SMS Payment: dispatch job ล้มเหลว — ลอง sync fallback', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'error' => $e->getMessage(),
                ]);

                // Fallback: ลอง process sync ทันที (ขยาย execution time)
                try {
                    \set_time_limit(300);

                    // Flush response กลับ user ก่อน (ถ้าเป็น FPM)
                    if (\function_exists('fastcgi_finish_request')) {
                        \fastcgi_finish_request();
                    }

                    $settings = FortuneTellingSetting::getSettings();
                    $conversationService = new FortuneConversationService($settings);
                    $channelManager = new FortuneChannelManager($settings);

                    $result = $conversationService->processPaymentConfirmed(
                        $reading, $notification, $channelManager, $platform, $userId
                    );

                    // ถ้าไม่ได้ streaming (fallback) → ส่งข้อความรวม
                    if (empty($result['streaming']) && ! empty($result['message'])) {
                        $channelManager->sendResponse($platform, $userId, $result);
                    }

                    // Sync สำเร็จ → mark matched
                    $notification->update([
                        'status' => 'matched',
                        'matched_transaction_id' => $reading->id,
                    ]);

                    Log::info('SMS Payment: sync fallback สำเร็จ', [
                        'reading_id' => $reading->id,
                        'action' => $result['action'] ?? 'unknown',
                    ]);

                    $markProcessed();

                    return true;

                } catch (\Exception $syncErr) {
                    Log::critical('SMS Payment: sync fallback ล้มเหลว!', [
                        'reading_id' => $reading->id,
                        'error' => $syncErr->getMessage(),
                        'trace' => substr($syncErr->getTraceAsString(), 0, 500),
                    ]);

                    // Mark เป็น matched เพราะเงินโอนมาแล้ว แต่ใส่ notes ว่า dispatch ล้มเหลว
                    $notification->update([
                        'status' => 'matched',
                        'matched_transaction_id' => $reading->id,
                        'notes' => 'dispatch + sync fallback failed: '.$syncErr->getMessage(),
                    ]);

                    // ❌ ไม่ส่ง error message ให้ลูกค้า — ลูกค้าได้รับ "รอสักครู่" ไปแล้ว
                    // fortune:check-pending จะ retry ให้อัตโนมัติทุก 1 นาที
                    Log::info('SMS Payment: dispatch ล้มเหลว → รอ check-pending retry', [
                        'reading_id' => $reading->id,
                    ]);

                    $markProcessed();

                    return true; // return true เพราะ matched แล้ว (เงินโอนมาจริง) แค่ dispatch ล้มเหลว
                }
            }
        } finally {
            // 🔓 (2026-05-09 audit fix P3) Release lock — auto release ผ่าน TTL 30s ก็ได้
            //    แต่ explicit release ดีกว่า กัน lock contention โดยไม่จำเป็น
            // 🩹 (self-review H1) เช็ค $lockAcquired ก่อน release — กัน lock null (cache outage)
            if ($lockAcquired && $lock !== null) {
                try {
                    $lock->release();
                } catch (\Throwable $releaseErr) {
                    // ignore — non-blocking
                }
            }
        }
    }

    /**
     * ส่งข้อความยาวไปยัง Messenger โดยแบ่งเป็นหลายข้อความ
     */
    protected function sendLongMessageToMessenger(FacebookWebhookService $facebookService, string $recipientId, string $message): void
    {
        $maxLength = 1800;

        if (mb_strlen($message) <= $maxLength) {
            $facebookService->sendMessage($recipientId, $message);

            return;
        }

        // แบ่งข้อความตาม paragraph หรือ ═══
        $parts = preg_split('/(?=═══════════════════════)/', $message);

        $currentMessage = '';
        foreach ($parts as $part) {
            if (mb_strlen($currentMessage.$part) > $maxLength && ! empty($currentMessage)) {
                $facebookService->sendMessage($recipientId, trim($currentMessage));
                usleep(300000); // รอ 300ms ระหว่างข้อความ
                $currentMessage = $part;
            } else {
                $currentMessage .= $part;
            }
        }

        if (! empty($currentMessage)) {
            $facebookService->sendMessage($recipientId, trim($currentMessage));
        }
    }

    /**
     * ค้นหา FortuneReading จาก unique amount ที่เพิ่งหมดอายุ (grace period)
     *
     * กรณีลูกค้าโอนเงินช้ากว่าเวลาที่กำหนด (60 นาที) แต่ unique amount
     * เพิ่งหมดอายุไม่นาน (ภายใน 30 นาทีหลังหมดอายุ)
     * → ยังสามารถจับคู่กับ FortuneReading ที่รอชำระเงินได้
     */
    protected function findFortuneReadingByExpiredAmount(float $amount, ?\Carbon\Carbon $smsTimestamp = null): ?FortuneReading
    {
        // ⏰ (2026-08-07) ขยาย grace ให้เท่ากับ "อายุบิลจริง" แทนค่าคงที่ 30 นาที
        //
        //   ปัญหาเดิม: บิลมีชีวิต 3 ชม. (bill_payment_timeout_minutes) แต่ยอดจองตายที่ 30 นาที
        //   + grace อีก 30 นาที → ลูกค้าที่โอนหลังชั่วโมงที่ 1 จับคู่ไม่ได้เลยทั้งที่บิลยังอยู่
        //   prod: เจอเคสลูกค้าโอนช้า 5 รายการ 497.65 บาท ตกค้างไม่มีใครจับคู่
        //
        //   ➕ รับสถานะ 'cancelled' ด้วย — บิลที่หมดเวลาถูกยกเลิกจะเซ็ต UPA เป็น cancelled
        //      (ไม่ใช่ expired) ของเดิมจึงไม่มีวันเจอเคสนี้เลย
        //      prod: UPA cancelled 1,012 ตัว vs expired เพียง 177
        $gracePeriodMinutes = max(30, FortuneReading::billTimeoutMinutes());
        $smsTimestamp = $smsTimestamp ?? now();

        $candidates = UniquePaymentAmount::where('unique_amount', $amount)
            ->where('transaction_type', 'fortune_reading')
            ->whereIn('status', ['reserved', 'expired', 'cancelled'])
            ->where('expires_at', '<=', now())
            ->where('expires_at', '>', now()->subMinutes($gracePeriodMinutes))
            // 🔒 (2026-04-28) SMS ต้องมาหลัง bill ถูกสร้าง — กัน SMS ก่อน bill
            ->where('created_at', '<=', $smsTimestamp)
            ->orderBy('expires_at', 'desc')
            ->lockForUpdate()
            ->get();

        // 🚨 ด่านกันจ่ายเงินผิดคน — ยอดทศนิยมถูก "เวียนใช้ซ้ำ" ได้
        //   UniquePaymentAmount::generate() กันเฉพาะ suffix ที่ status='reserved' และยังไม่หมดอายุ
        //   → ยอด 99.36 ของบิลที่ยกเลิกไปแล้ว ถูกจ่ายให้บิลใหม่ได้ทันที
        //   ยิ่งขยายหน้าต่างยิ่งมีโอกาสเจอหลายใบพร้อมกัน ถ้าเดาผิด = ตัดเงินเข้าบิลผิดคน
        //   เจอมากกว่า 1 ใบ → ไม่เดา ปล่อยไปทาง orphan/แอดมินตรวจเอง (ปลอดภัยกว่า)
        if ($candidates->count() > 1) {
            Log::warning('⚠️ SMS Payment: grace period เจอ UPA ยอดซ้ำหลายใบ — ไม่เดา ส่งให้แอดมินตรวจ', [
                'amount' => $amount,
                'candidate_ids' => $candidates->pluck('id')->all(),
                'grace_minutes' => $gracePeriodMinutes,
            ]);

            return null;
        }

        $uniquePayment = $candidates->first();

        if (! $uniquePayment) {
            return null;
        }

        // ค้นหา FortuneReading ที่ยังรอชำระ หรือ cleanup ปิดไปแล้ว (completed) แต่ยังไม่ได้จ่าย
        return FortuneReading::where('unique_payment_amount_id', $uniquePayment->id)
            ->where('is_paid', false)
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_PENDING_PAYMENT,
                FortuneReading::STATUS_CELTIC_PENDING_PAYMENT, // 🔮 Celtic Cross รองรับ recovery ด้วย
                FortuneReading::STATUS_COMPLETED, // cleanup อาจปิดไปแล้ว → recover
            ])
            ->first();
    }

    /**
     * 🔮 จัดการ Celtic Cross payment matched — push "เริ่มเปิดไพ่ใบที่ 1/10" ทันที
     *
     * ต่างจาก 39฿ flow:
     * - ไม่ dispatch ProcessDeepFortuneReadingJob (ยังไม่ต้องสร้างคำทำนาย)
     * - แต่ transition reading ไป STATUS_CELTIC_PICKING แล้ว push ข้อความเชิญตั้งจิตเปิดไพ่ใบที่ 1
     *
     * เรียกจาก:
     *   1. matchAndProcessFortuneReading() — auto SMS match (มี notification เสมอ)
     *   2. SmsPaymentController::approveOrder/bulkApprove/etc. — admin manual approve (notification อาจเป็น null)
     *
     * @param  SmsPaymentNotification|null  $notification  ถ้า null จะข้าม update notification status (admin force approve case)
     */
    public function handleCelticPaymentMatched(
        FortuneReading $reading,
        ?SmsPaymentNotification $notification,
        string $platform,
        string $userId,
        float $amount
    ): bool {
        try {
            $settings = FortuneTellingSetting::getSettings();

            // 🆕 (2026-06-03) บันทึกยอดที่รับจริงลงบิล (SMS amount / admin force actual)
            //   amount_paid = ยอดบิลตั้งไว้ (unique amount) — ไม่ทับ
            //   amount_received = ยอดจริงที่โอนเข้ามา → แสดงในหน้า admin ให้ตรงตามจริง
            if ($amount > 0) {
                try {
                    $reading->forceFill(['amount_received' => $amount])->save();
                } catch (\Throwable $e) {
                    // non-blocking — ไม่ให้กระทบ flow ตัดบิล
                }
            }

            // 1. ส่งข้อความ "ตัดบิลเรียบร้อย" + เริ่มเปิดไพ่ใบที่ 1/10
            //    เรียก trait method ผ่าน FortuneConversationService::onCelticPaymentConfirmed
            //    ซึ่งจะ:
            //      - update conversation_status → CELTIC_PICKING
            //      - return array ที่มี message + first card prompt
            $conversationService = new FortuneConversationService($settings);
            $celticResponse = $conversationService->onCelticPaymentConfirmed($reading);

            // 2. ปรับข้อความให้มี "ตัดบิลเรียบร้อย" prefix สำหรับ proactive push
            $userName = $reading->facebook_user_name ?? 'เจ้าชะตา';
            $billRef = $reading->bill_reference ?? '-';
            $payAmountStr = number_format($amount, 2);

            $billConfirmHeader = "✅ ระบบตัดบิลเรียบร้อยแล้วค่ะ คุณ{$userName}\n\n"
                ."🔖 เลขที่บิล: {$billRef}\n"
                ."💰 ค่าครูที่ได้รับ: ฿{$payAmountStr}\n\n"
                ."═══════════════════════\n"
                ."🔮 *Celtic Cross Tarot — เริ่มเลย!*\n"
                ."═══════════════════════\n\n";

            $celticResponse['message'] = $billConfirmHeader.($celticResponse['message'] ?? '');

            // 3. Push ผ่าน channel manager (ใช้ POST_PURCHASE_UPDATE tag)
            $channelManager = new FortuneChannelManager($settings);
            $pushSent = $channelManager->sendResponse($platform, $userId, $celticResponse, [
                'from_admin' => true,
                'message_tag' => 'POST_PURCHASE_UPDATE',
            ]);

            Log::info('SMS Payment (Celtic): push เริ่มเปิดไพ่ สำเร็จ', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'sent' => $pushSent,
                'next_position' => $reading->fresh()->getNextCelticPosition(),
            ]);

            // 4. Mark notification matched (skip ถ้า admin force approve โดยไม่มี SMS)
            if ($notification) {
                $notification->update([
                    'status' => 'matched',
                    'matched_transaction_id' => $reading->id,
                ]);

                // 5. FCM push ให้แอพ SMS Checker อัพเดทสถานะ (ต้องมี notification)
                try {
                    app(FcmNotificationService::class)->notifyFortuneReadingMatched($reading, $notification);
                } catch (\Exception $fcmErr) {
                    Log::warning('SMS Payment (Celtic): FCM push ล้มเหลว (ไม่ critical)', [
                        'error' => $fcmErr->getMessage(),
                    ]);
                }
            }

            // 6. 🆕 (2026-05-13) Auto-register + commission distribution
            //    เดิม: Celtic แตก fork ใน parent (line 769-775) → ข้าม processPaymentConfirmed
            //    → ไม่มี user_id assign → commission ไม่ทำงาน (ลูกค้า Celtic 99฿ ทุกบิล user_id=NULL)
            //    Fix: เรียก processAffiliateAndCommissions (logic เดียวกับ Deep 39฿) — ทุกอย่างใน try/catch
            try {
                $conversationService->processAffiliateAndCommissions(
                    $reading,
                    $platform,
                    $userId,
                    $channelManager
                );
            } catch (\Throwable $affErr) {
                // ห้าม error กระทบการ push first card (ที่สำเร็จไปแล้ว)
                Log::warning('SMS Payment (Celtic): auto-register + commission ล้มเหลว (ไม่ critical)', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'error' => $affErr->getMessage(),
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::critical('SMS Payment (Celtic): handleCelticPaymentMatched ล้มเหลว', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            // confirmPayment() ถูกเรียกไปแล้ว — เงินอยู่ใน DB เรียบร้อย
            // ลูกค้าจะได้ message ตอนทักกลับ (handleCelticPendingPayment refresh เจอ is_paid)
            return true;
        }
    }

    /**
     * 💰 (2026-05-10) Deep 39 Pay-First — handle payment ก่อนข้อมูลครบ
     *
     * Pay-First flow: ลูกค้ากด "39" → สร้างบิลทันที → จ่ายเงิน → flow นี้ทำงาน
     *   1. confirmPayment() ถูกเรียกไปแล้วใน parent (is_paid=true, status=PAID)
     *   2. เปลี่ยน status → COLLECTING_BIRTHDATE (กลับเข้า flow เก็บข้อมูล)
     *   3. Push "ขอบคุณค่ะ ขอวันเกิด" ผ่าน POST_PURCHASE_UPDATE
     *   4. ❌ ไม่ dispatch ProcessDeepFortuneReadingJob (ยังไม่มี data ให้ทำนาย)
     *   5. ลูกค้าใส่วันเกิด → continueConversation รู้ status → handleBirthdateInput
     */
    public function handleDeepPayFirstPaymentMatched(
        FortuneReading $reading,
        ?SmsPaymentNotification $notification,
        string $platform,
        string $userId,
        float $amount
    ): bool {
        try {
            $settings = FortuneTellingSetting::getSettings();

            // 1. เปลี่ยน status → COLLECTING_BIRTHDATE (จาก PAID → กลับเข้า flow เก็บข้อมูล)
            //    ✅ confirmPayment() เรียกไปแล้ว → is_paid=true เก็บไว้
            //    ⚠️ override status PAID เพราะระบบยังไม่ได้ทำนาย — ต้องเก็บข้อมูลก่อน
            $reading->update(['conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE]);

            // 2. ปรับข้อความขอบคุณ + ขอวันเกิด
            $userName = $reading->facebook_user_name ?? 'เจ้าชะตา';
            $billRef = $reading->bill_reference ?? '-';
            $payAmountStr = number_format($amount, 2);

            // 🔁 (2026-06-08) ดึง 2 ทาง — เคยให้วันเกิด (39/Celtic) → ไม่ถามซ้ำ ข้ามไปตั้งจิตเปิดไพ่เลย
            //   พื้นชะตาเก็บที่ column birth_date เดียวกันทั้ง 39 และ Celtic → reuse ข้ามระบบได้
            $reuseResult = null;
            try {
                $fcsForReuse = new \App\Services\FortuneConversationService($settings);
                $priorBirth = $fcsForReuse->findReusableBirthDateForUser($reading);
                if ($priorBirth !== null) {
                    $reuseResult = $fcsForReuse->beginDeepGeneralReading($reading, $priorBirth->format('Y-m-d'));
                    // 🆕 (2026-06-23, owner) ตั้ง flag ให้ตรงกับ path processPaymentConfirmed —
                    //   เปิดให้ลูกค้า "พิมพ์วันเกิดใหม่ทับ" ได้ที่ขั้นตั้งจิต (handleTarotCardDraw)
                    $reading->setConversationState('birthdate_auto_filled', true);
                    $reading->setConversationState('birthdate_reused_from_history', $priorBirth->format('Y-m-d'));
                    Log::info('SMS Payment (Pay-First Deep 39): reuse วันเกิดเดิม → ข้ามขั้นถามวันเกิด', [
                        'reading_id' => $reading->id,
                        'birth_date' => $priorBirth->format('Y-m-d'),
                    ]);
                }
            } catch (\Throwable $reuseErr) {
                Log::warning('SMS Payment (Pay-First Deep 39): reuse วันเกิดล้มเหลว → ถามตามปกติ', [
                    'reading_id' => $reading->id,
                    'error' => $reuseErr->getMessage(),
                ]);
                $reuseResult = null;
            }

            if ($reuseResult !== null) {
                // ✅ มีวันเกิดเดิม → ตัดบิล + ตั้งจิตเปิดไพ่ทันที
                //   (beginDeepGeneralReading ตั้ง status = COLLECTING_TAROT + ใส่คำถามพื้นดวงให้แล้ว)
                // 🎂 (2026-07-25, owner) "ต้องถามก่อนทำนายว่าจะใช้วันเกิดเก่าไหม"
                //   path นี้คือทางหลัก (SMS/สลิปตัดบิลอัตโนมัติ) — เดิม hardcode ข้อความ "จำได้แล้ว
                //   ไม่ต้องบอกใหม่" ทำให้ลูกค้าส่วนใหญ่ไม่เคยเห็นขั้นยืนยัน → ดวงผิดวันเกิดโดยไม่รู้ตัว
                //   ใช้ข้อความ + ปุ่มชุดเดียวกับ processPaymentConfirmed (helper กลาง)
                $thanksMessage = "✅ ระบบตัดบิลเรียบร้อยแล้วค่ะ คุณ{$userName}\n\n"
                    ."🔖 เลขที่บิล: {$billRef}\n"
                    ."💰 ค่าครู: ฿{$payAmountStr}\n\n"
                    .$fcsForReuse->buildReusedBirthdateNotePublic($reading)
                    .($reuseResult['message'] ?? '');
            } else {
                // ไม่เคยมีวันเกิด → ขอวันเกิด (ตามเดิม)
                $thanksMessage = "✅ ระบบตัดบิลเรียบร้อยแล้วค่ะ คุณ{$userName}\n\n"
                    ."🔖 เลขที่บิล: {$billRef}\n"
                    ."💰 ค่าครู: ฿{$payAmountStr}\n\n"
                    ."═══════════════════════\n"
                    ."🌙 *แม่หมอจันทรากำลังเปิดประตูดวงให้*\n"
                    ."═══════════════════════\n\n"
                    ."🪄 ตอนนี้ขั้นตอนแรกของการเปิดดวง —\n"
                    ."ขอ*วันเดือนปีเกิด*ของเจ้าชะตาก่อนนะคะ ✨\n\n"
                    ."📝 *ตัวอย่าง:* 15 มีนาคม 2538\n"
                    ."   หรือ 15/3/2538 / 15-3-2538\n\n"
                    ."💡 หากจำไม่ได้แม่นยำ — ใส่ปีก่อน เดือน ก็พอค่ะ\n"
                    .'(ค่าครูที่จ่ายแล้วจะรอเก็บไว้ตลอด ไม่หมดอายุนะคะ 🙏)';
            }

            // 3. Push ผ่าน channel manager (POST_PURCHASE_UPDATE — push ได้นอก 24hr window)
            // 🛡️ (2026-05-13 v3) Async retry — กัน webhook block
            //   เดิม (v2): sleep + retry 3 ครั้ง inline = block PHP-FPM 7 วินาที
            //              → SMS webhook timeout 10s ลูกค้ารอนาน
            //   ใหม่ (v3): ลอง inline 1 ครั้ง → fail → dispatch RetryPayFirstPushJob (async)
            //              webhook return ทันที, job retry backoff 10s/30s ใน background
            $channelManager = new FortuneChannelManager($settings);
            $pushSent = false;

            try {
                // 🔁 (2026-06-08) ถ้า reuse วันเกิด → ส่ง action ตั้งจิตเปิดไพ่ + ปุ่ม "พร้อมเปิดไพ่"
                //   ถ้าไม่ → action ขอวันเกิดตามเดิม
                $pushPayload = [
                    'action' => $reuseResult !== null
                        ? ($reuseResult['action'] ?? 'awaiting_tarot_intention')
                        : 'collecting_birthdate',
                    'message' => $thanksMessage,
                    'reading' => $reading,
                ];
                if ($reuseResult !== null) {
                    // 🎂 (2026-07-25) ใช้วันเกิดเดิม → ปุ่ม "✅ ใช่ ใช้วันเกิดนี้ / 📅 เปลี่ยนวันเกิด"
                    //   (ถ้าไม่ได้ reuse helper คืนปุ่ม "พร้อมเปิดไพ่" ตามเดิม)
                    $pushPayload['quick_replies'] = $fcsForReuse->buildReusedBirthdateQuickRepliesPublic($reading);
                    $pushPayload['show_quick_replies'] = true;
                }

                $pushSent = $channelManager->sendResponse($platform, $userId, $pushPayload, [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);
            } catch (\Throwable $pushErr) {
                Log::warning('SMS Payment (Pay-First): push inline fail — จะ dispatch async job', [
                    'reading_id' => $reading->id,
                    'error' => $pushErr->getMessage(),
                ]);
            }

            if ($pushSent) {
                // mark resent_at เพื่อ scheduler dedup
                $reading->setConversationState('birthdate_resent_at', now()->toIso8601String());
            } else {
                // ❌ Inline push fail → dispatch async retry job (ไม่ block webhook)
                //    Job retry 3 ครั้ง backoff 10s/30s (รวม 40s)
                //    ถ้าครบ retry ยัง fail → scheduler `deep-pay-first-auto-recovery` รับช่วง
                try {
                    \App\Jobs\RetryPayFirstPushJob::dispatch(
                        $reading->id,
                        $platform,
                        $userId,
                        $thanksMessage,
                        // 🔁 (2026-06-08) reuse วันเกิด → retry ด้วย action ตั้งจิตเปิดไพ่ (ไม่โดน birth_date guard skip)
                        $reuseResult !== null
                            ? ($reuseResult['action'] ?? 'awaiting_tarot_intention')
                            : 'collecting_birthdate',
                    )->delay(now()->addSeconds(5)); // 5s grace ก่อน first retry

                    Log::warning('SMS Payment (Pay-First): inline push fail — dispatched async retry job', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                    ]);
                } catch (\Throwable $dispatchErr) {
                    // ถ้า queue ใช้ไม่ได้ (no jobs table / sync driver) → scheduler รับช่วง
                    Log::error('SMS Payment (Pay-First): dispatch retry job fail — scheduler จะ retry', [
                        'reading_id' => $reading->id,
                        'error' => $dispatchErr->getMessage(),
                    ]);
                }
            }

            // 4. Clear gen_processing flag (ไม่ใช้ใน pay-first — ลูกค้ายังไม่ได้รอ AI)
            \Illuminate\Support\Facades\Cache::forget("fortune:gen_processing:{$userId}");

            // 5. Mark notification matched
            if ($notification) {
                $notification->update([
                    'status' => 'matched',
                    'matched_transaction_id' => $reading->id,
                ]);

                try {
                    app(FcmNotificationService::class)->notifyFortuneReadingMatched($reading, $notification);
                } catch (\Exception $fcmErr) {
                    Log::warning('SMS Payment (Pay-First): FCM push ล้มเหลว (ไม่ critical)', [
                        'error' => $fcmErr->getMessage(),
                    ]);
                }
            }

            Log::info('SMS Payment (Pay-First Deep 39): ตัดบิล + ขอวันเกิดต่อ สำเร็จ', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'sent' => $pushSent,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::critical('SMS Payment (Pay-First Deep 39): handleDeepPayFirstPaymentMatched ล้มเหลว', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            // confirmPayment() เรียกไปแล้ว → เงินอยู่ใน DB
            // ลูกค้าจะได้ message ตอนทักกลับมา (handlePendingPayment เห็น is_paid=true → fall through)
            return true;
        }
    }
}
