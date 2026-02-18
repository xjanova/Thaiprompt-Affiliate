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

        $data = [
            'type' => 'new_order',
            'order_id' => (string) $offsetId,
            'order_number' => $reading->bill_reference ?? ('FTU-' . $reading->id),
            'amount' => number_format((float) ($reading->amount_paid ?? 0), 2, '.', ''),
            'customer_name' => $reading->facebook_user_name ?? 'ลูกค้าดูดวง',
            'is_fortune_reading' => 'true',
            'server_url' => config('app.url'),  // ให้ Android app รู้ว่า FCM นี้มาจากเซิร์ฟไหน
        ];

        $notification = [
            'title' => '🔮 บิลดูดวงใหม่ รอชำระเงิน',
            'body' => sprintf(
                'บิล %s ยอด ฿%s (%s)',
                $reading->bill_reference ?? "#{$reading->id}",
                number_format((float) ($reading->amount_paid ?? 0), 2),
                $reading->facebook_user_name ?? 'ลูกค้าดูดวง'
            ),
        ];

        Log::info('FCM: Sending new_fortune_reading push', [
            'reading_id' => $reading->id,
            'bill_reference' => $reading->bill_reference,
            'amount' => $reading->amount_paid,
        ]);

        return $this->sendToMultipleTokens($tokens, $data, $notification);
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
