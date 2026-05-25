<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (FCM) Notification Service
 *
 * Sends push notifications to Android SmsChecker app via FCM HTTP v1 API.
 *
 * Setup Requirements:
 * 1. Create Firebase project at https://console.firebase.google.com
 * 2. Download service account JSON key
 * 3. Set FIREBASE_CREDENTIALS_PATH in .env
 * 4. Set FCM_PROJECT_ID in .env
 */
class FcmNotificationService
{
    private ?string $accessToken = null;

    private ?int $tokenExpiry = null;

    /**
     * Send push notification for new payment transaction (bill)
     */
    public function notifyNewTransaction(PaymentTransaction $transaction, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for new transaction notification');

            return false;
        }

        $data = [
            'type' => 'new_order',
            'order_id' => (string) $transaction->id,
            'order_number' => $transaction->transaction_id ?? ('TXN-'.$transaction->id),
            'amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'customer_name' => $transaction->user?->name ?? 'N/A',
        ];

        $notification = [
            'title' => 'คำสั่งซื้อใหม่ รอชำระเงิน',
            'body' => sprintf(
                'รายการ #%s ยอด ฿%s',
                $transaction->transaction_id ?? $transaction->id,
                number_format((float) $transaction->amount, 2)
            ),
        ];

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when payment is matched
     */
    public function notifyPaymentMatched(PaymentTransaction $transaction, SmsPaymentNotification $smsNotification, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for payment matched notification');

            return false;
        }

        $banks = config('smschecker.supported_banks', []);
        $bankName = $banks[$smsNotification->bank] ?? $smsNotification->bank;

        $data = [
            'type' => 'payment_matched',
            'order_id' => (string) $transaction->id,
            'order_number' => $transaction->transaction_id ?? ('TXN-'.$transaction->id),
            'amount' => number_format((float) $smsNotification->amount, 2, '.', ''),
            'bank' => $smsNotification->bank,
            'status' => $smsNotification->status,
        ];

        $notification = [
            'title' => 'ยืนยันการชำระเงินแล้ว!',
            'body' => sprintf(
                'รายการ #%s ยอด ฿%s (%s)',
                $transaction->transaction_id ?? $transaction->id,
                number_format((float) $smsNotification->amount, 2),
                $bankName
            ),
        ];

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification for transaction status update
     */
    public function notifyTransactionUpdate(PaymentTransaction $transaction, string $status, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $statusLabels = [
            'pending' => 'รอชำระเงิน',
            'matched' => 'พบการโอนเงิน',
            'confirmed' => 'ยืนยันแล้ว',
            'rejected' => 'ถูกปฏิเสธ',
            'expired' => 'หมดอายุ',
        ];

        $data = [
            'type' => 'order_update',
            'order_id' => (string) $transaction->id,
            'order_number' => $transaction->transaction_id ?? ('TXN-'.$transaction->id),
            'status' => $status,
        ];

        $notification = [
            'title' => 'อัพเดทรายการชำระเงิน',
            'body' => sprintf(
                'รายการ #%s สถานะ: %s',
                $transaction->transaction_id ?? $transaction->id,
                $statusLabels[$status] ?? $status
            ),
        ];

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when transaction is approved (by admin or Android app)
     * Android app receives this and updates local DB immediately
     */
    public function notifyTransactionApproved(PaymentTransaction $transaction, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $data = [
            'type' => 'order_approved',
            'order_id' => (string) $transaction->id,
            'order_number' => $transaction->transaction_id ?? ('TXN-'.$transaction->id),
            'amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'payment_status' => $transaction->status ?? 'completed',
        ];

        $notification = [
            'title' => '✅ อนุมัติรายการแล้ว',
            'body' => sprintf(
                'รายการ #%s ยอด ฿%s อนุมัติแล้ว',
                $transaction->transaction_id ?? $transaction->id,
                number_format((float) $transaction->amount, 2)
            ),
        ];

        Log::info('FCM: Sending order_approved push', [
            'transaction_id' => $transaction->id,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when transaction is rejected
     */
    public function notifyTransactionRejected(PaymentTransaction $transaction, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $data = [
            'type' => 'order_rejected',
            'order_id' => (string) $transaction->id,
            'order_number' => $transaction->transaction_id ?? ('TXN-'.$transaction->id),
            'payment_status' => $transaction->status ?? 'failed',
        ];

        $notification = [
            'title' => '❌ ปฏิเสธรายการ',
            'body' => sprintf(
                'รายการ #%s ถูกปฏิเสธ',
                $transaction->transaction_id ?? $transaction->id
            ),
        ];

        Log::info('FCM: Sending order_rejected push', [
            'transaction_id' => $transaction->id,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * Send push notification when transaction is cancelled
     */
    public function notifyTransactionCancelled(PaymentTransaction $transaction, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $data = [
            'type' => 'order_cancelled',
            'order_id' => (string) $transaction->id,
            'order_number' => $transaction->transaction_id ?? ('TXN-'.$transaction->id),
            'payment_status' => $transaction->status ?? 'cancelled',
        ];

        $notification = [
            'title' => '🚫 ยกเลิกรายการ',
            'body' => sprintf(
                'รายการ #%s ถูกยกเลิก',
                $transaction->transaction_id ?? $transaction->id
            ),
        ];

        Log::info('FCM: Sending order_cancelled push', [
            'transaction_id' => $transaction->id,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * ส่ง push notification เมื่อบิลดูดวง match กับ SMS แล้ว (auto-approve)
     *
     * แจ้งแอพ SMS Checker ว่าบิลดูดวงจับคู่สำเร็จ + ชำระแล้ว
     * เพื่อให้แอพอัพเดทสถานะจาก "รอจับคู่" เป็น "ชำระแล้ว" ทันที
     */
    public function notifyFortuneReadingMatched(FortuneReading $reading, SmsPaymentNotification $smsNotification, ?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for fortune reading matched notification');

            return false;
        }

        $banks = config('smschecker.supported_banks', []);
        $bankName = $banks[$smsNotification->bank] ?? $smsNotification->bank;

        // ใช้ offset ID เดียวกับ transformFortuneReadingToOrderApproval()
        // เพื่อให้แอพ map ได้ถูกต้อง
        $offsetId = $reading->id + 10000000;

        $data = [
            'type' => 'order_approved',
            'order_id' => (string) $offsetId,
            'order_number' => $reading->bill_reference ?? ('FTU-' . $reading->id),
            'amount' => number_format((float) $smsNotification->amount, 2, '.', ''),
            'bank' => $smsNotification->bank,
            'payment_status' => 'completed',
            'is_fortune_reading' => 'true',
        ];

        $notification = [
            'title' => '🔮 บิลดูดวงชำระแล้ว!',
            'body' => sprintf(
                'บิล %s ยอด ฿%s จับคู่สำเร็จ (%s)',
                $reading->bill_reference ?? "#{$reading->id}",
                number_format((float) $smsNotification->amount, 2),
                $bankName
            ),
        ];

        Log::info('FCM: Sending fortune_reading_matched push', [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'notification_id' => $smsNotification->id,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notification);
    }

    /**
     * ส่ง push notification เมื่อสร้างบิลดูดวงใหม่ (รอชำระเงิน)
     *
     * แจ้งแอพ SMS Checker ว่ามีบิลดูดวงใหม่รอจับคู่
     * เพื่อให้แอพแสดงบิลทันทีโดยไม่ต้องรอ polling cycle
     */
    public function notifyNewFortuneReading(FortuneReading $reading): bool
    {
        $tokens = $this->getTargetTokens(null);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for new fortune reading notification');

            return false;
        }

        // ใช้ offset ID เดียวกับ transformFortuneReadingToOrderApproval()
        $offsetId = $reading->id + 10000000;

        // Flag `is_floating` แยกเคสบิลลอย (ยังจับคู่ user ไม่ได้ — admin ต้อง review manual)
        $isFloating = (bool) ($reading->is_floating ?? false);

        $data = [
            'type' => $isFloating ? 'new_floating_fortune_bill' : 'new_order',
            'order_id' => (string) $offsetId,
            'order_number' => $reading->bill_reference ?? ('FTU-' . $reading->id),
            'amount' => number_format((float) ($reading->amount_paid ?? 0), 2, '.', ''),
            'customer_name' => $reading->facebook_user_name ?? 'ลูกค้าดูดวง',
            'is_fortune_reading' => 'true',
            'is_floating' => $isFloating ? 'true' : 'false',
            'server_url' => config('app.url'),  // ให้ Android app รู้ว่า FCM นี้มาจากเซิร์ฟไหน
        ];

        // ส่งเป็น data-only (ไม่มี notification field) เพื่อให้ onMessageReceived() ถูกเรียกเสมอ
        // แม้แอพอยู่ใน background — Android จะแสดง notification เองใน FcmService.handleNewOrder()
        // ถ้าส่ง notification field ด้วย → Android OS จะ intercept และ onMessageReceived() จะไม่ถูกเรียก
        Log::info($isFloating ? '⚠️ FCM: Floating fortune bill (ต้อง manual review)' : 'FCM: Sending new_fortune_reading push (data-only)', [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'amount' => $reading->amount_paid,
            'is_floating' => $isFloating,
            'sender_info' => $reading->sender_info,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, null);
    }

    /**
     * ส่ง push notification เมื่อบิลดูดวงถูกยกเลิก (ลูกค้ากดยกเลิกใน LINE)
     *
     * แจ้งแอพ SMS Checker ให้อัพเดทสถานะบิลเป็น cancelled ทันที
     * เพื่อไม่ให้แอพยังแสดงบิลเป็น pending อยู่
     */
    public function notifyFortuneReadingCancelled(FortuneReading $reading): bool
    {
        $tokens = $this->getTargetTokens(null);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for fortune reading cancelled notification');

            return false;
        }

        $offsetId = $reading->id + 10000000;

        // 🏷️ ดึง cancellation_reason จาก conversation_state (auto_expired / user_cancelled / auto_expired_grace)
        //    ส่งให้ smschecker app เพื่อแยกสถิติ + แสดง label ที่ถูกต้องใน UI
        $cancellationReason = 'unknown';
        try {
            $state = $reading->conversation_state ?? [];
            if (is_array($state) && ! empty($state['cancellation_reason'])) {
                $cancellationReason = (string) $state['cancellation_reason'];
            }
        } catch (\Throwable $e) {
            // ปล่อย default 'unknown'
        }

        // 🏷️ (2026-05-25) Human-readable label สำหรับ smschecker app
        //    User spec: "บิลที่ถูกยกเลิกโดยระบบ ให้ขึ้นในแอพและในระบบต่างๆ ว่า ยกเลิกโดยระบบ"
        //    auto_expired / auto_expired_grace → "ยกเลิกโดยระบบ"
        //    user_cancelled                     → "ยกเลิกโดยลูกค้า"
        //    unknown                            → "ยกเลิก (ไม่ทราบสาเหตุ)"
        $cancellationReasonLabel = FortuneReading::getCancellationReasonLabel($cancellationReason);

        $data = [
            'type' => 'order_cancelled',
            'order_id' => (string) $offsetId,
            'order_number' => $reading->bill_reference ?? ('FTU-'.$reading->id),
            'payment_status' => 'cancelled',
            'cancellation_reason' => $cancellationReason,
            'cancellation_reason_label' => $cancellationReasonLabel,
            'is_fortune_reading' => 'true',
            'server_url' => config('app.url'),
        ];

        // data-only push เพื่อให้ onMessageReceived() ถูกเรียกเสมอ
        Log::info('FCM: Sending fortune_reading_cancelled push (data-only)', [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'cancellation_reason' => $cancellationReason,
            'cancellation_reason_label' => $cancellationReasonLabel,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, null);
    }

    /**
     * 📡 (2026-05-21) ส่ง FCM "trigger_sms_rescan" — ขอให้ smschecker app
     *    รื้อ SMS inbox ในโทรศัพท์ที่อาจพลาดไป
     *
     * เคสที่ใช้:
     *   - ลูกค้ากด "เช็คสถานะ" / ส่งสลิป → trigger handlePaymentClaim ที่ยังไม่ paid
     *   - ระบบสงสัยว่าธนาคารส่ง SMS แล้วแต่ broadcast receiver พลาด
     *   - App รับ FCM → query Telephony.Sms.Inbox + parse SMS ใหม่
     *
     * Rate limit: cache 30 วินาที/บิล (กัน spam หาก customer กด check status รัวๆ)
     *
     * @param  FortuneReading  $reading  บิลที่ trigger
     * @param  string  $reason  'check_status' | 'slip_uploaded' | 'admin_manual'
     */
    public function notifyTriggerSmsRescan(FortuneReading $reading, string $reason = 'check_status'): bool
    {
        // Rate limit — กัน spam: 1 trigger / 30s ต่อบิล
        $cacheKey = "fcm:rescan_trigger:{$reading->id}";
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            Log::debug('FCM: rescan trigger throttled (cooldown 30s)', [
                'reading_id' => $reading->id,
                'reason' => $reason,
            ]);

            return false;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addSeconds(30));

        $tokens = $this->getTargetTokens(null);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens for rescan trigger', [
                'reading_id' => $reading->id,
            ]);

            return false;
        }

        $upa = $reading->uniquePaymentAmount;
        $expectedAmount = (float) ($upa?->unique_amount ?? $reading->amount_paid ?? 0);
        $basePrice = (float) ($upa?->base_amount ?? 0);

        // Lookback: SMS อาจมาก่อนหน้า bill creation ไม่กี่นาที (กรณีโอนล่วงหน้า)
        //          แต่ที่สำคัญ — ครอบ SMS ที่มาหลัง bill creation จนถึงตอนนี้
        $lookbackHours = max(1, (int) ceil(now()->diffInHours($reading->created_at) + 1));
        $lookbackHours = min($lookbackHours, 48); // hard cap 48 ชม.

        $data = [
            'type' => 'trigger_sms_rescan',
            'reading_id' => (string) $reading->id,
            'bill_reference' => $reading->bill_reference ?? '',
            'expected_amount' => number_format($expectedAmount, 2, '.', ''),
            'base_price' => number_format($basePrice, 2, '.', ''),
            'customer_name' => $reading->facebook_user_name ?? '',
            'lookback_hours' => (string) $lookbackHours,
            'reason' => $reason,
            'server_url' => config('app.url'),
        ];

        Log::info('📡 FCM: Sending trigger_sms_rescan (data-only)', [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'reason' => $reason,
            'lookback_hours' => $lookbackHours,
            'tokens_count' => count($tokens),
        ]);

        return $this->sendToMultipleTokens($tokens, $data, null);
    }

    /**
     * 🚨 ส่ง push แจ้ง admin เมื่อมียอดดูดวงเข้าแต่ไม่มีบิลจับคู่
     *
     * เคสที่ trigger: ลูกค้าโอนยอดในช่วงดูดวง (เช่น 39.34) แต่บิลถูกยกเลิก/หมดอายุ
     * ไปแล้วเกิน grace period — server ไม่ match → admin ต้อง review manual
     *
     * Push เป็น **visible notification** (ไม่ใช่ data-only) เพราะต้องการให้
     * admin เห็นเด้งทันทีไม่ต้องเปิดแอพ
     */
    public function notifyOrphanFortunePayment(SmsPaymentNotification $notification, float $expectedPrice): bool
    {
        $tokens = $this->getTargetTokens(null);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for orphan payment alert');

            return false;
        }

        $amount = number_format((float) $notification->amount, 2);
        $sender = $notification->sender_or_receiver ?: 'ไม่ทราบ';

        $data = [
            'type' => 'orphan_fortune_payment',
            'notification_id' => (string) $notification->id,
            'amount' => $amount,
            'bank' => $notification->bank ?? '',
            'sender' => $sender,
            'expected_price' => number_format($expectedPrice, 2),
            'requires_admin_review' => 'true',
            'server_url' => config('app.url'),
        ];

        $notificationPayload = [
            'title' => '🚨 ยอดดูดวงเข้าแต่ไม่มีบิล — ตรวจสอบด่วน',
            'body' => sprintf(
                '฿%s จาก %s — ยอดอยู่ในช่วงดูดวง (~฿%s) แต่ไม่มีบิลจับคู่ (อาจเป็นบิลที่ยกเลิกไปแล้ว)',
                $amount,
                $sender,
                number_format($expectedPrice, 2)
            ),
        ];

        Log::info('FCM: Sending orphan_fortune_payment alert', [
            'notification_id' => $notification->id,
            'amount' => $amount,
            'expected_price' => $expectedPrice,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notificationPayload);
    }

    /**
     * 🚨 (2026-05-14) ส่ง push แจ้ง admin เมื่อ AI ทำนายค้างเกิน 1 นาที
     *
     * เคสที่ trigger: AI Pool ใช้เวลานานผิดปกติ (timeout/network/provider down)
     *   - ลูกค้าจ่ายเงินแล้ว → AI ค้าง → admin ไม่รู้
     *   - ก่อนหน้านี้: alert เฉพาะ Job::failed() (รอ retry หมด ~5 นาที = ช้าเกินไป)
     *   - Now: alert ที่ 60s real-time → admin reaction time เร็วขึ้น
     *
     * Push เป็น **visible notification** (ไม่ใช่ data-only) เพราะต้องการให้
     * admin เห็นเด้งทันทีไม่ต้องเปิดแอพ (เหมือน notifyOrphanFortunePayment)
     *
     * ใช้แทน LineAlertService::alertSystemError() เพื่อประหยัด LINE push quota
     *   - LINE push: 1 push × จำนวน admin = กิน quota เร็ว
     *   - FCM push: free unlimited → คุ้มกว่ามาก
     *   - Caller ควร fallback LINE alert ถ้า FCM returns false (no tokens)
     */
    public function notifyAiStuck(FortuneReading $reading, string $aiPurpose): bool
    {
        $tokens = $this->getTargetTokens(null);
        if (empty($tokens)) {
            Log::debug('FCM: No tokens available for AI stuck alert');

            return false;
        }

        $offsetId = $reading->id + 10000000;
        $billRef = $reading->bill_reference ?? ('FTU-'.$reading->id);
        $customerName = $reading->facebook_user_name
            ?? $reading->line_user_name
            ?? 'ลูกค้าดูดวง';

        // 🌙 (2026-05-23) เลิกผูกราคา 39฿ ใน label — ใช้ชื่อบริการกลางๆ
        //   ลูกค้าสับสนเรื่อง 39฿ + ราคาอาจเปลี่ยน → ไม่ฮาร์ดโค้ดในข้อความ admin
        $purposeLabel = match ($aiPurpose) {
            'deep' => 'ดูดวงเชิงลึก (Deep)',
            'celtic-opening' => 'Celtic เปิดบทสนทนา',
            'celtic-question' => 'Celtic ตอบคำถาม',
            'chat' => 'แชท AI',
            default => $aiPurpose,
        };

        $data = [
            'type' => 'ai_stuck_alert',
            'order_id' => (string) $offsetId,
            'reading_id' => (string) $reading->id,
            'order_number' => $billRef,
            'ai_purpose' => $aiPurpose,
            'platform' => $reading->facebook_user_id ? 'facebook' : 'line',
            'customer_name' => $customerName,
            'requires_admin_review' => 'true',
            'server_url' => config('app.url'),
        ];

        $notificationPayload = [
            'title' => '⏰ AI ค้างเกิน 1 นาที — ตรวจสอบด่วน',
            'body' => sprintf(
                '%s | บิล %s | ลูกค้า %s',
                $purposeLabel,
                $billRef,
                $customerName
            ),
        ];

        Log::warning('FCM: Sending ai_stuck_alert', [
            'reading_id' => $reading->id,
            'bill_reference' => $billRef,
            'ai_purpose' => $aiPurpose,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notificationPayload);
    }

    /**
     * Notify device that settings changed (e.g. approval_mode from admin panel)
     * Device will trigger sync to pull updated settings
     */
    public function notifySettingsChanged(SmsCheckerDevice $device, string $setting, string $value): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $data = [
            'type' => 'settings_changed',
            'setting' => $setting,
            'value' => $value,
            'device_id' => $device->device_id,
        ];

        Log::info('FCM: Sending settings_changed push', [
            'device_id' => $device->device_id,
            'setting' => $setting,
            'value' => $value,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, null);
    }

    /**
     * Send silent push to trigger sync
     */
    public function triggerSync(?SmsCheckerDevice $device = null): bool
    {
        $tokens = $this->getTargetTokens($device);
        if (empty($tokens)) {
            return false;
        }

        $data = [
            'type' => 'sync',
            'timestamp' => (string) (time() * 1000),
        ];

        // Silent push - no notification shown
        return $this->sendToMultipleTokens($tokens, $data, null);
    }

    /**
     * ทดสอบ FCM push แบบละเอียด — return ข้อมูล debug สำหรับ admin panel
     *
     * @return array{success: bool, message: string, details: array}
     */
    public function testPush(): array
    {
        // ตรวจสอบ FCM enabled
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'FCM ถูกปิดอยู่ — เปิดใช้งานที่ตั้งค่า FCM ก่อน',
                'details' => ['fcm_enabled' => false],
            ];
        }

        // ตรวจสอบ credentials
        $credentialsPath = Setting::get('fcm_credentials_path') ?: config('services.firebase.credentials');
        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            return [
                'success' => false,
                'message' => 'ไม่พบไฟล์ Firebase credentials — อัพโหลดใหม่อีกครั้ง',
                'details' => ['credentials_path' => $credentialsPath],
            ];
        }

        // ตรวจสอบ project ID
        $projectId = Setting::get('fcm_project_id') ?: config('services.firebase.project_id');
        if (! $projectId) {
            $projectId = $this->getProjectIdFromCredentials();
        }
        if (! $projectId) {
            return [
                'success' => false,
                'message' => 'ไม่พบ Firebase Project ID — กรอกใน settings',
                'details' => [],
            ];
        }

        // ตรวจสอบ access token (OAuth2)
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถขอ Access Token จาก Google ได้ — ตรวจสอบไฟล์ credentials',
                'details' => ['project_id' => $projectId],
            ];
        }

        // ตรวจสอบอุปกรณ์
        $activeDevices = SmsCheckerDevice::where('status', 'active')->count();
        $tokens = $this->getTargetTokens(null);

        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => "มี {$activeDevices} อุปกรณ์ active แต่ไม่มีอุปกรณ์ที่ลงทะเบียน FCM token — ต้องเปิดแอพ Android เพื่อให้ส่ง token มาลงทะเบียน",
                'details' => [
                    'active_devices' => $activeDevices,
                    'devices_with_token' => 0,
                    'project_id' => $projectId,
                    'access_token_ok' => true,
                ],
            ];
        }

        // ลองส่ง sync push
        $data = [
            'type' => 'sync',
            'timestamp' => (string) (time() * 1000),
        ];

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $data, null)) {
                $successCount++;
            } else {
                $failedCount++;
                $errors[] = 'token: '.substr($token, 0, 20).'...';
            }
        }

        if ($successCount > 0) {
            return [
                'success' => true,
                'message' => "ส่ง FCM push สำเร็จ {$successCount}/" . count($tokens) . ' อุปกรณ์ — ตรวจสอบที่แอพ Android',
                'details' => [
                    'total_tokens' => count($tokens),
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'project_id' => $projectId,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => "ส่งล้มเหลวทั้ง " . count($tokens) . " อุปกรณ์ — token อาจไม่ถูกต้อง (แอพต้องอัพเดทและเปิดใหม่)",
            'details' => [
                'total_tokens' => count($tokens),
                'failed' => $failedCount,
                'errors' => $errors,
                'project_id' => $projectId,
            ],
        ];
    }

    /**
     * Check if FCM is enabled via admin settings
     */
    public function isEnabled(): bool
    {
        return (bool) Setting::get('fcm_enabled', true);
    }

    /**
     * Send FCM message to multiple tokens
     */
    private function sendToMultipleTokens(array $tokens, array $data, ?array $notification): bool
    {
        // Check if FCM is disabled via admin settings
        if (! $this->isEnabled()) {
            Log::debug('FCM: Disabled via admin settings, skipping send');

            return false;
        }

        if (empty($tokens)) {
            return false;
        }

        $successCount = 0;
        $failedTokens = [];

        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $data, $notification)) {
                $successCount++;
            } else {
                $failedTokens[] = $token;
            }
        }

        // Mark failed tokens as invalid
        if (! empty($failedTokens)) {
            $this->markTokensInvalid($failedTokens);
        }

        Log::debug('FCM: Sent to '.$successCount.'/'.count($tokens).' tokens');

        return $successCount > 0;
    }

    /**
     * Send FCM message to a single token
     */
    private function sendToToken(string $token, array $data, ?array $notification): bool
    {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            Log::error('FCM: Failed to get access token');

            return false;
        }

        // Read project ID from database first, fallback to config, then from credentials file
        $projectId = Setting::get('fcm_project_id') ?: config('services.firebase.project_id');
        if (! $projectId) {
            // ✅ Fallback: อ่าน project_id จากไฟล์ credentials JSON
            $projectId = $this->getProjectIdFromCredentials();
        }
        if (! $projectId) {
            Log::error('FCM: Firebase project ID not configured');

            return false;
        }

        $message = [
            'token' => $token,
            'data' => array_map('strval', $data), // FCM data must be strings
            'android' => [
                'priority' => 'high',
                'ttl' => '86400s', // 24 hours
            ],
        ];

        // Add notification if provided (visible push)
        if ($notification) {
            $message['notification'] = $notification;
            $message['android']['notification'] = [
                'channel_id' => 'sms_payment_channel',
                'click_action' => 'OPEN_ORDERS',
            ];
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    ['message' => $message]
                );

            if ($response->successful()) {
                return true;
            }

            $error = $response->json('error.details.0.errorCode') ?? $response->json('error.message');
            Log::warning('FCM: Send failed', [
                'error' => $error,
                'status' => $response->status(),
            ]);

            // Token-specific errors that indicate token is invalid
            if (in_array($error, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                return false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('FCM: Exception during send', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Get OAuth2 access token for FCM API
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry - 60) {
            return $this->accessToken;
        }

        // Read credentials path from database first, fallback to config
        $credentialsPath = Setting::get('fcm_credentials_path') ?: config('services.firebase.credentials');
        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            Log::error('FCM: Firebase credentials file not found', ['path' => $credentialsPath]);

            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);

            // Create JWT
            $now = time();
            $jwt = $this->createJwt([
                'iss' => $credentials['client_email'],
                'sub' => $credentials['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ], $credentials['private_key']);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600);

                return $this->accessToken;
            }

            Log::error('FCM: Failed to get access token', ['response' => $response->json()]);

            return null;
        } catch (\Exception $e) {
            Log::error('FCM: Exception getting access token', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * อ่าน project_id จากไฟล์ credentials JSON (fallback เมื่อไม่ได้ตั้ง env/DB)
     */
    private function getProjectIdFromCredentials(): ?string
    {
        $credentialsPath = Setting::get('fcm_credentials_path') ?: config('services.firebase.credentials');
        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);

            return $credentials['project_id'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create JWT for Google OAuth2
     */
    private function createJwt(array $payload, string $privateKey): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'RS256',
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload)),
        ];

        $signingInput = implode('.', $segments);

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get FCM tokens to send to
     */
    private function getTargetTokens(?SmsCheckerDevice $device): array
    {
        if ($device && $device->fcm_token) {
            return [$device->fcm_token];
        }

        // Get all active devices with FCM tokens
        return SmsCheckerDevice::where('status', 'active')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->toArray();
    }

    /**
     * ทำเครื่องหมาย tokens ที่ไม่ถูกต้อง (Firebase ปฏิเสธ)
     *
     * เรียกเมื่อ FCM push ล้มเหลวด้วย error UNREGISTERED หรือ INVALID_ARGUMENT
     * จะลบ token ออกจากอุปกรณ์เพื่อไม่ให้ส่งซ้ำ
     */
    private function markTokensInvalid(array $tokens): void
    {
        if (empty($tokens)) {
            return;
        }

        // Log device IDs ที่จะถูกลบ token ก่อน — เพื่อ debug
        $affectedDevices = SmsCheckerDevice::whereIn('fcm_token', $tokens)
            ->pluck('device_id')
            ->toArray();

        SmsCheckerDevice::whereIn('fcm_token', $tokens)
            ->update(['fcm_token' => null]);

        Log::warning('FCM: ลบ tokens ที่ไม่ถูกต้อง', [
            'count' => count($tokens),
            'affected_devices' => $affectedDevices,
            'token_prefixes' => array_map(fn ($t) => substr($t, 0, 20) . '...', $tokens),
        ]);
    }

    /**
     * ลงทะเบียน/อัพเดท FCM token สำหรับอุปกรณ์
     *
     * - ลบ token เดิมจากอุปกรณ์อื่น (1 token = 1 อุปกรณ์)
     * - บันทึก token ใหม่พร้อม timestamp
     * - Log ทุกขั้นตอนเพื่อ debug
     */
    public function registerToken(SmsCheckerDevice $device, string $fcmToken): bool
    {
        $tokenPrefix = substr($fcmToken, 0, 20) . '...';

        // ลบ token จากอุปกรณ์อื่น (ป้องกัน token ซ้ำ)
        $duplicateCount = SmsCheckerDevice::where('fcm_token', $fcmToken)
            ->where('id', '!=', $device->id)
            ->count();

        if ($duplicateCount > 0) {
            SmsCheckerDevice::where('fcm_token', $fcmToken)
                ->where('id', '!=', $device->id)
                ->update(['fcm_token' => null]);

            Log::info('FCM: ลบ token ซ้ำจากอุปกรณ์อื่น', [
                'device_id' => $device->device_id,
                'duplicates_removed' => $duplicateCount,
            ]);
        }

        // บันทึก token ใหม่พร้อม timestamp
        $device->update([
            'fcm_token' => $fcmToken,
            'fcm_token_updated_at' => now(),
        ]);

        Log::info('FCM: Token registered ✅', [
            'device_id' => $device->device_id,
            'token_prefix' => $tokenPrefix,
            'token_length' => strlen($fcmToken),
        ]);

        return true;
    }
}
