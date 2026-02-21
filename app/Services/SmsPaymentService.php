<?php

namespace App\Services;

use App\Jobs\ProcessDeepFortuneReadingJob;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneChannelManager;
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

        foreach ($expiredFortuneAmounts as $uniqueAmount) {
            // ปิด FortuneReading ที่ยังรอชำระ
            $expiredReadings = FortuneReading::where('unique_payment_amount_id', $uniqueAmount->id)
                ->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)
                ->get();

            foreach ($expiredReadings as $reading) {
                $reading->update([
                    'conversation_status' => FortuneReading::STATUS_COMPLETED,
                ]);
                $stats['expired_fortune_readings']++;

                Log::info('SMS Payment: ปิดบิลดูดวงหมดเวลา (หลัง grace period)', [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'unique_amount' => $uniqueAmount->unique_amount,
                ]);
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
     * ตรวจจับและจัดการยอดเงินดูดวงจาก Conversational Flow
     *
     * ตรวจสอบว่ายอดเงินตรงกับ FortuneReading ที่รอชำระเงินหรือไม่
     * ถ้าตรง → ยืนยันการชำระ → ทำนายละเอียด → ส่งผ่าน Platform ที่ใช้งาน (Facebook/LINE)
     *
     * @return bool มีการจัดการหรือไม่
     */
    protected function handleFortuneReadingPayment(SmsPaymentNotification $notification): bool
    {
        $amount = (float) $notification->amount;

        // ขั้นที่ 1: ค้นหา FortuneReading ที่รอชำระเงินด้วย unique amount ที่ยังไม่หมดอายุ
        $reading = FortuneReading::findByUniqueAmount($amount);

        // ขั้นที่ 2: Grace period — ค้นหา unique amount ที่เพิ่งหมดอายุ (ภายใน 30 นาที)
        // กรณีลูกค้าโอนช้ากว่าเวลาที่กำหนด แต่ยังอยู่ใน grace period
        if (! $reading) {
            $reading = $this->findFortuneReadingByExpiredAmount($amount);

            if ($reading) {
                Log::info('SMS Payment: พบ Fortune Reading ผ่าน grace period (unique amount หมดอายุแล้ว)', [
                    'notification_id' => $notification->id,
                    'reading_id' => $reading->id,
                    'amount' => $amount,
                ]);
            }
        }

        // ขั้นที่ 3: Fallback — ค้นหาจาก amount_paid ตรงๆ
        // กรณี unique amount ถูกลบไปแล้ว แต่ FortuneReading ยังรอชำระ
        // หรือ cleanup ปิดไปแล้ว (completed) แต่ SMS มาช้า → recover กลับมา
        if (! $reading) {
            $reading = FortuneReading::whereIn('conversation_status', [
                FortuneReading::STATUS_PENDING_PAYMENT,
                FortuneReading::STATUS_COMPLETED, // cleanup อาจปิดไปแล้ว
            ])
                ->where('is_paid', false)
                ->where('amount_paid', $amount)
                ->where('updated_at', '>=', now()->subMinutes(FortuneReading::PAYMENT_TIMEOUT_MINUTES + 30))
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($reading) {
                Log::info('SMS Payment: พบ Fortune Reading ผ่าน amount_paid fallback', [
                    'notification_id' => $notification->id,
                    'reading_id' => $reading->id,
                    'amount' => $amount,
                    'was_status' => $reading->conversation_status,
                ]);
            }
        }

        if (! $reading) {
            return false;
        }

        // Recovery: ถ้า reading ถูก cleanup ปิดไปแล้ว (completed) แต่ยังไม่ได้จ่าย → กู้คืนกลับมา
        if ($reading->conversation_status !== FortuneReading::STATUS_PENDING_PAYMENT && ! $reading->is_paid) {
            Log::info('SMS Payment: กู้คืน Fortune Reading ที่หมดอายุ/ถูกปิด กลับเป็น pending_payment', [
                'reading_id' => $reading->id,
                'old_status' => $reading->conversation_status,
                'amount' => $amount,
            ]);
            $reading->update(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
        }

        // ระบุ platform และ user ID ที่จะส่งข้อความ
        $platform = $reading->platform ?? 'facebook';
        $userId = $reading->platform_user_id ?? $reading->facebook_user_id;

        Log::info('SMS Payment: พบ Fortune Reading ที่รอชำระ', [
            'notification_id' => $notification->id,
            'reading_id' => $reading->id,
            'amount' => $amount,
            'platform' => $platform,
            'user_id' => $userId,
        ]);

        // ✅ ยืนยันการชำระเงินทันที (ก่อน dispatch job)
        // เพื่อให้ response กลับไปแอพ SMS Checker แสดงสถานะ "auto_approved" ทันที
        // ไม่ใช่ค้างที่ "pending_review" จนกว่า job จะรัน confirmPayment()
        $reading->confirmPayment($notification);

        Log::info('SMS Payment: confirmPayment ทันที (ก่อน dispatch job)', [
            'reading_id' => $reading->id,
            'notification_id' => $notification->id,
            'is_paid' => $reading->is_paid,
            'conversation_status' => $reading->conversation_status,
        ]);

        // ✅ ส่งข้อความ "รอสักครู่" ให้ลูกค้าทราบทันทีหลังชำระเงินสำเร็จ
        // ⚠️ ป้องกัน SMS duplicate — เช็คว่าส่ง "รอสักครู่" ไปแล้วหรือยัง
        $alreadySentWait = $reading->getConversationState('wait_message_sent', false);

        if (! empty($userId) && ! $alreadySentWait) {
            try {
                $settings = FortuneTellingSetting::getSettings();
                $channelManager = new FortuneChannelManager($settings);

                $name = $reading->facebook_user_name ?? 'คุณ';
                $waitMessage = "✨ ขอบคุณค่ะ {$name}! ได้รับการชำระเงินเรียบร้อยแล้ว\n\n"
                    . "🔮 จันทราจะตรวจดวงชะตาให้นะคะ รอสักครู่ประมาณ 5 นาทีค่ะ ✨";

                $sent = $channelManager->sendResponse($platform, $userId, [
                    'action' => 'payment_confirmed_wait',
                    'message' => $waitMessage,
                    'reading' => $reading,
                    'facebook_user_id' => $userId,
                ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                // 🔄 Retry: ถ้า Flex pushMessage ล้มเหลว → รอ 3 วิ ลองส่ง text ธรรมดาแทน
                if (! $sent) {
                    Log::warning('SMS Payment: Flex "รอสักครู่" ล้มเหลว → retry ด้วย text ธรรมดาใน 3 วิ', [
                        'reading_id' => $reading->id,
                        'platform' => $platform,
                    ]);
                    sleep(3);

                    // ลอง Flex อีกครั้ง
                    $sent = $channelManager->sendResponse($platform, $userId, [
                        'action' => 'payment_confirmed_wait',
                        'message' => $waitMessage,
                        'reading' => $reading,
                        'facebook_user_id' => $userId,
                    ], ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE']);

                    // ถ้า Flex ยังไม่สำเร็จ → ลอง text ธรรมดา (fallback สุดท้าย)
                    if (! $sent && $platform === 'line') {
                        try {
                            $lineService = $channelManager->getPlatform('line');
                            if ($lineService) {
                                $sent = $lineService->sendMessage($userId, $waitMessage);
                                if ($sent) {
                                    Log::info('SMS Payment: text fallback "รอสักครู่" สำเร็จ', [
                                        'reading_id' => $reading->id,
                                    ]);
                                }
                            }
                        } catch (\Exception $textErr) {
                            Log::warning('SMS Payment: text fallback ล้มเหลวด้วย', [
                                'reading_id' => $reading->id,
                                'error' => $textErr->getMessage(),
                            ]);
                        }
                    }
                }

                // บันทึกสถานะว่าส่งข้อความรอแล้ว (ป้องกัน duplicate)
                // ⚠️ เซ็ต flag เสมอ ไม่ว่าจะส่งสำเร็จหรือไม่ — เพื่อป้องกัน duplicate จาก SMS ซ้ำ
                // check-pending Phase 2 จะ retry ส่งคำทำนายให้อัตโนมัติ
                $reading->setConversationState('wait_message_sent', true);
                $reading->setConversationState('wait_message_sent_at', now()->toIso8601String());
                $reading->setConversationState('wait_message_delivered', $sent);

                Log::info('SMS Payment: ส่งข้อความ "รอสักครู่"', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'user_id' => $userId,
                    'sent_result' => $sent,
                ]);
            } catch (\Exception $waitErr) {
                Log::error('SMS Payment: ส่งข้อความ "รอสักครู่" ล้มเหลว', [
                    'reading_id' => $reading->id,
                    'platform' => $platform,
                    'user_id' => $userId,
                    'error' => $waitErr->getMessage(),
                    'trace' => substr($waitErr->getTraceAsString(), 0, 300),
                ]);
            }
        } elseif ($alreadySentWait) {
            Log::info('SMS Payment: ข้าม "รอสักครู่" — ส่งไปแล้วก่อนหน้า (SMS duplicate)', [
                'reading_id' => $reading->id,
                'notification_id' => $notification->id,
            ]);
        } else {
            Log::error('SMS Payment: ไม่สามารถส่งข้อความ "รอสักครู่" — ไม่มี userId', [
                'reading_id' => $reading->id,
                'platform' => $platform,
                'platform_user_id' => $reading->platform_user_id,
                'facebook_user_id' => $reading->facebook_user_id,
            ]);
        }

        // ✅ เช็คว่ามีคำทำนายพร้อมแล้วหรือยัง (กรณี check-pending retry สำเร็จก่อน SMS duplicate เข้ามา)
        $reading->refresh();
        if (! empty($reading->deep_response) && ! $reading->getConversationState('reading_sent_directly', false)) {
            // มีคำทำนายแล้ว → ส่งให้ลูกค้าเลยทันที (ไม่ต้อง dispatch job ใหม่)
            try {
                $settings = $settings ?? FortuneTellingSetting::getSettings();
                $channelManager = $channelManager ?? new FortuneChannelManager($settings);
                $name = $reading->facebook_user_name ?? 'คุณ';
                $extra = ['from_admin' => true, 'message_tag' => 'POST_PURCHASE_UPDATE'];

                // 1. ส่ง chart + header
                $channelManager->sendResponse($platform, $userId, [
                    'action' => 'send_chart',
                    'message' => "🔮✨ คำทำนายของคุณ{$name}พร้อมแล้วค่ะ!",
                    'chart_image_url' => $reading->reading_image_url,
                ], $extra);

                sleep(2); // ⚡ 2s — เว้นระยะก่อนส่งคำทำนาย

                // 2. ส่งคำทำนาย — ✅ เช็ค return value
                $deepSent = $channelManager->sendResponse($platform, $userId, [
                    'action' => 'deep_reading_result',
                    'message' => "🌟 *คำทำนายเชิงลึก*\n📋 เลขที่บิล: " . ($reading->bill_reference ?? '-') . "\n═══════════════════════\n\n" . $reading->deep_response,
                    'reading' => $reading,
                ], $extra);

                // 🔄 Retry ถ้าส่งไม่สำเร็จ (เนื้อหาเสียเงิน สำคัญมาก)
                if (! $deepSent) {
                    Log::warning('SMS Payment: ส่งคำทำนายครั้ง 1 ไม่สำเร็จ → retry ใน 5 วิ', ['reading_id' => $reading->id]);
                    sleep(5);
                    $deepSent = $channelManager->sendResponse($platform, $userId, [
                        'action' => 'deep_reading_result',
                        'message' => "🌟 *คำทำนายเชิงลึก*\n📋 เลขที่บิล: " . ($reading->bill_reference ?? '-') . "\n═══════════════════════\n\n" . $reading->deep_response,
                        'reading' => $reading,
                    ], $extra);
                }

                if ($deepSent) {
                    sleep(2);

                    // 3. ข้อความปิดท้าย
                    $channelManager->sendResponse($platform, $userId, [
                        'action' => 'reading_complete',
                        'message' => "💫 หวังว่าคำทำนายจะเป็นประโยชน์นะคะ\n\n💡 พิมพ์ 'ดูคำทำนาย' เพื่อดูอีกครั้งได้ทุกเมื่อค่ะ 🔮",
                    ], $extra);

                    // ✅ ส่งสำเร็จจริง → บันทึกสถานะ
                    $reading->setConversationState('reading_sent_directly', true);
                    $reading->setConversationState('reading_ready_sent', true);
                    $reading->setConversationState('reading_ready_sent_at', now()->toIso8601String());

                    Log::info('SMS Payment: มีคำทำนายพร้อมแล้ว → ส่งให้ลูกค้าสำเร็จ', [
                        'reading_id' => $reading->id,
                        'has_chart' => ! empty($reading->reading_image_url),
                    ]);

                    $notification->update(['status' => 'matched', 'matched_transaction_id' => $reading->id]);

                    return true;
                } else {
                    // ❌ ส่งไม่สำเร็จ → ไม่เซ็ต flag → fallthrough dispatch job ด้านล่าง
                    Log::error('SMS Payment: ส่งคำทำนายไม่สำเร็จ 2 ครั้ง — fallback dispatch job', [
                        'reading_id' => $reading->id,
                    ]);
                }
            } catch (\Exception $sendErr) {
                Log::error('SMS Payment: ส่งคำทำนายที่มีอยู่แล้วล้มเหลว — fallback dispatch job', [
                    'reading_id' => $reading->id,
                    'error' => $sendErr->getMessage(),
                ]);
                // fallthrough ไป dispatch job ด้านล่าง
            }
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

                return true; // return true เพราะ matched แล้ว (เงินโอนมาจริง) แค่ dispatch ล้มเหลว
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
    protected function findFortuneReadingByExpiredAmount(float $amount): ?FortuneReading
    {
        // ค้นหา unique amount ที่หมดอายุแล้วแต่ยังอยู่ใน grace period (30 นาทีหลังหมดอายุ)
        $gracePeriodMinutes = 30;

        $uniquePayment = UniquePaymentAmount::where('unique_amount', $amount)
            ->where('transaction_type', 'fortune_reading')
            ->whereIn('status', ['reserved', 'expired'])
            ->where('expires_at', '<=', now())
            ->where('expires_at', '>', now()->subMinutes($gracePeriodMinutes))
            ->orderBy('expires_at', 'desc')
            ->lockForUpdate()
            ->first();

        if (! $uniquePayment) {
            return null;
        }

        // ค้นหา FortuneReading ที่ยังรอชำระ หรือ cleanup ปิดไปแล้ว (completed) แต่ยังไม่ได้จ่าย
        return FortuneReading::where('unique_payment_amount_id', $uniquePayment->id)
            ->where('is_paid', false)
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_PENDING_PAYMENT,
                FortuneReading::STATUS_COMPLETED, // cleanup อาจปิดไปแล้ว → recover
            ])
            ->first();
    }
}
