<?php

namespace App\Services;

use App\Models\MobileDevice;
use App\Models\MobilePushNotification;
use App\Services\Fcm\FcmHttpV1Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ExpoPushService - ส่ง Push Notification ไปแอป Thai Prompt APP
 *
 * token 2 แบบใน mobile_devices.push_token:
 *   1. Expo Push Token (ExponentPushToken[xxxxx]) → Expo Push API https://exp.host/--/api/v2/push/send
 *   2. 🔔 (2026-10-13) FCM device token ของ Android (แอป 3.390+ ส่ง token ตรงจาก Firebase)
 *      → ส่งตรงผ่าน FCM HTTP v1 ด้วย service account ของเราเอง (Firebase project plptdb)
 *      ไม่ต้องฝากคีย์ FCM ไว้กับ Expo · ข้อความเป็น data-only รูปแบบเดียวกับที่ Expo ส่ง
 *      (title / message / body=JSON ของ data / channelId) → expo-notifications ในแอปแสดงและพาไปหน้าเดิมได้ทุกอย่าง
 *
 * @see https://docs.expo.dev/push-notifications/sending-notifications/
 */
class ExpoPushService
{
    /**
     * Expo Push API endpoint
     */
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * ส่ง Push Notification ไปยังเครื่องเดียว
     *
     * @param  string  $pushToken  Expo Push Token
     * @param  string  $title  หัวข้อ
     * @param  string  $body  เนื้อหา
     * @param  array  $data  ข้อมูลเพิ่มเติม
     */
    public function sendToDevice(string $pushToken, string $title, string $body, array $data = []): array
    {
        return $this->send([$pushToken], $title, $body, $data);
    }

    /**
     * ส่ง Push Notification ไปยังหลายเครื่อง
     *
     * @param  array  $pushTokens  รายการ Expo Push Token
     * @param  string  $title  หัวข้อ
     * @param  string  $body  เนื้อหา
     * @param  array  $data  ข้อมูลเพิ่มเติม
     */
    public function send(array $pushTokens, string $title, string $body, array $data = []): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (empty($pushTokens)) {
            return $results;
        }

        $channelId = self::androidChannelFor($data);

        // สร้าง messages สำหรับแต่ละ token
        $messages = [];
        $fcmTokens = [];
        foreach (array_unique($pushTokens) as $token) {
            if (self::isFcmDeviceToken($token)) {
                $fcmTokens[] = $token;

                continue;
            }

            if (! $this->isValidExpoPushToken($token)) {
                $results['failed']++;
                $results['errors'][] = 'Invalid token: '.substr($token, 0, 12).'…';

                continue;
            }

            $messages[] = [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => $channelId,
            ];
        }

        if ($fcmTokens !== []) {
            $this->sendViaFcm($fcmTokens, $title, $body, $data, $channelId, $results);
        }

        if (empty($messages)) {
            return $results;
        }

        try {
            // ส่งเป็น batch (max 100 ต่อ request)
            $chunks = array_chunk($messages, 100);

            foreach ($chunks as $chunk) {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post(self::EXPO_PUSH_URL, $chunk);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $this->processResponse($responseData, $results);
                } else {
                    Log::error('Expo Push API error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $results['failed'] += count($chunk);
                    $results['errors'][] = 'HTTP Error: '.$response->status();
                }
            }
        } catch (\Exception $e) {
            Log::error('Expo Push Service error', [
                'message' => $e->getMessage(),
            ]);
            $results['failed'] += count($messages);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * ส่ง Notification จาก MobilePushNotification model
     */
    public function sendNotification(MobilePushNotification $notification): array
    {
        // ดึง push tokens ตาม target
        $tokens = $this->getTokensForNotification($notification);

        if (empty($tokens)) {
            $notification->update([
                'status' => 'failed',
                'error_message' => 'No valid push tokens found',
            ]);

            return ['success' => 0, 'failed' => 0, 'errors' => ['No valid push tokens found']];
        }

        // ส่ง notification
        $results = $this->send(
            $tokens,
            $notification->title,
            $notification->body,
            [
                'type' => $notification->type ?? 'general',
                'notification_id' => $notification->id,
                'url' => $notification->url ?? null,
            ]
        );

        // อัพเดท status
        $notification->update([
            'status' => $results['failed'] === 0 ? 'sent' : 'partial',
            'sent_at' => now(),
            'sent_count' => $results['success'],
            'failed_count' => $results['failed'],
            'error_message' => ! empty($results['errors']) ? implode(', ', $results['errors']) : null,
        ]);

        return $results;
    }

    /**
     * ส่งไปยัง User เฉพาะ
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $tokens = MobileDevice::where('user_id', $userId)
            ->whereNotNull('push_token')
            ->where('is_active', true)
            ->pluck('push_token')
            ->toArray();

        // Log สำหรับ debug
        Log::info('ExpoPushService: Sending push to user', [
            'user_id' => $userId,
            'title' => $title,
            'tokens_count' => count($tokens),
            'data_type' => $data['type'] ?? null,
        ]);

        if (empty($tokens)) {
            Log::warning('ExpoPushService: No push tokens found for user', [
                'user_id' => $userId,
            ]);
        }

        $results = $this->send($tokens, $title, $body, $data);

        // 🔔 (2026-09-25) จดไว้ว่าเพิ่งส่งหัวข้อ+ข้อความนี้ให้ผู้ใช้นี้แล้ว — ให้สะพาน
        //    NotificationService → push (App\Jobs\SendNotificationPush) ไม่ยิงซ้ำ
        //    เมื่อโค้ดต้นทางทั้งสร้างแจ้งเตือนในระบบและยิง push เองในเหตุการณ์เดียวกัน
        //    (จดอย่างเดียว ไม่เปลี่ยนพฤติกรรมการส่งของผู้เรียกเดิม)
        if ($results['success'] > 0) {
            self::rememberPushed($userId, $title, $body);
        }

        // Log ผลลัพธ์
        if ($results['success'] > 0) {
            Log::info('ExpoPushService: Push sent successfully', [
                'user_id' => $userId,
                'success' => $results['success'],
                'failed' => $results['failed'],
            ]);
        } elseif ($results['failed'] > 0) {
            Log::warning('ExpoPushService: Push failed', [
                'user_id' => $userId,
                'errors' => $results['errors'],
            ]);
        }

        return $results;
    }

    /** อายุของบันทึก "เพิ่งส่งไป" (วินาที) */
    private const RECENT_PUSH_TTL = 120;

    /**
     * ผู้ใช้นี้เพิ่งได้รับ push หัวข้อ+ข้อความนี้ (ผ่าน sendToUser) ภายใน 2 นาทีหรือไม่
     */
    public static function wasRecentlyPushed(int $userId, string $title, string $body): bool
    {
        try {
            return \Illuminate\Support\Facades\Cache::has(self::recentPushKey($userId, $title, $body));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function rememberPushed(int $userId, string $title, string $body): void
    {
        try {
            \Illuminate\Support\Facades\Cache::put(self::recentPushKey($userId, $title, $body), true, self::RECENT_PUSH_TTL);
        } catch (\Throwable $e) {
            // cache ใช้ไม่ได้ → อย่างมากเด้งซ้ำ 1 ครั้ง ห้ามทำให้การส่ง push ล้ม
        }
    }

    private static function recentPushKey(int $userId, string $title, string $body): string
    {
        return 'expo_push_recent:'.$userId.':'.sha1(trim($title)."\n".trim($body));
    }

    /**
     * ส่งไปยังทุกเครื่อง (Broadcast)
     */
    public function broadcast(string $title, string $body, array $data = []): array
    {
        $tokens = MobileDevice::whereNotNull('push_token')
            ->where('is_active', true)
            ->pluck('push_token')
            ->toArray();

        return $this->send($tokens, $title, $body, $data);
    }

    /**
     * ส่งไปยัง Platform เฉพาะ
     *
     * @param  string  $platform  'ios' หรือ 'android'
     */
    public function sendToPlatform(string $platform, string $title, string $body, array $data = []): array
    {
        $tokens = MobileDevice::where('platform', $platform)
            ->whereNotNull('push_token')
            ->where('is_active', true)
            ->pluck('push_token')
            ->toArray();

        return $this->send($tokens, $title, $body, $data);
    }

    /** ช่องแจ้งเตือน Android ที่แอปสร้างไว้ (services/notifications.ts → setupAndroidChannels) */
    private const ANDROID_CHANNELS = ['default', 'important', 'orders', 'messages', 'promotions'];

    /**
     * ช่องแจ้งเตือน Android ของข้อความนี้
     *
     * ระบุ data.channel มาเอง (ต้องเป็นช่องที่แอปมี) → ใช้ตามนั้น
     * งานไรเดอร์ (push_channel = rider) → orders (สำคัญสูง มีเสียง เด้งบนจอ) — ไรเดอร์ต้องรู้ทันทีว่ามีงานเข้า
     * อื่นๆ → default
     */
    public static function androidChannelFor(array $data): string
    {
        $channel = $data['channel'] ?? null;
        if (is_string($channel) && in_array($channel, self::ANDROID_CHANNELS, true)) {
            return $channel;
        }

        return ($data['push_channel'] ?? null) === 'rider' ? 'orders' : 'default';
    }

    /**
     * 🔔 (2026-10-13) เป็น FCM device token (Android) ไหม — ยาว ไม่มีวงเล็บแบบ Expo มีแค่ตัวอักษร/ตัวเลข/:-_
     */
    public static function isFcmDeviceToken(string $token): bool
    {
        return strlen($token) >= 100
            && strlen($token) <= 500
            && preg_match('/^[A-Za-z0-9_:\-]+$/', $token) === 1;
    }

    /**
     * 🔔 (2026-10-13) ส่งตรงผ่าน FCM HTTP v1 (Firebase project plptdb · service account ฝั่งเซิร์ฟเวอร์)
     *
     * เป็นข้อความ data-only รูปแบบเดียวกับ Expo Push (title / message / body / channelId / sound)
     * → expo-notifications ในแอปสร้างแจ้งเตือนเอง แม้แอปปิดอยู่ (priority high) และกดแล้วได้ data เดิมครบ
     * token ที่ FCM บอกว่าใช้ไม่ได้แล้ว (ถอนแอป/ล้างข้อมูล) → ล้างออกจาก mobile_devices ไม่ยิงซ้ำ
     *
     * @param  list<string>  $tokens
     * @param  array{success: int, failed: int, errors: array<int, string>}  $results
     */
    private function sendViaFcm(array $tokens, string $title, string $body, array $data, string $channelId, array &$results): void
    {
        $client = app(FcmHttpV1Client::class);

        if (! $client->isConfigured()) {
            $results['failed'] += count($tokens);
            $results['errors'][] = 'FCM not configured';
            Log::error('ExpoPushService: FCM ยังไม่ได้ตั้งค่า (ไฟล์ service account) — ส่งแจ้งเตือนเครื่อง Android ไม่ได้', [
                'tokens' => count($tokens),
            ]);

            return;
        }

        $payload = [
            'title' => $title,
            'message' => $body,
            'body' => json_encode($data === [] ? new \stdClass : $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'channelId' => $channelId,
            'sound' => 'default',
        ];

        foreach ($tokens as $token) {
            $result = $client->send($token, $payload);

            if ($result->ok) {
                $results['success']++;

                continue;
            }

            $results['failed']++;
            $results['errors'][] = 'FCM: '.($result->error ?? 'HTTP '.$result->status);

            if ($result->invalidToken) {
                MobileDevice::where('push_token', $token)->update(['push_token' => null]);
                Log::info('ExpoPushService: ล้าง FCM token ที่ใช้ไม่ได้แล้ว', ['error' => $result->error]);
            }
        }
    }

    /**
     * ตรวจสอบว่าเป็น Expo Push Token ที่ถูกต้องหรือไม่
     *
     * รองรับรูปแบบ:
     * - ExponentPushToken[xxxx]
     * - ExpoPushToken[xxxx]
     */
    private function isValidExpoPushToken(string $token): bool
    {
        // รองรับทั้ง ExponentPushToken และ ExpoPushToken
        return preg_match('/^Expo(nent)?PushToken\[.+\]$/', $token) === 1;
    }

    /**
     * ประมวลผล response จาก Expo API
     */
    private function processResponse(array $responseData, array &$results): void
    {
        if (! isset($responseData['data'])) {
            return;
        }

        foreach ($responseData['data'] as $item) {
            if (isset($item['status']) && $item['status'] === 'ok') {
                $results['success']++;
            } else {
                $results['failed']++;
                if (isset($item['message'])) {
                    $results['errors'][] = $item['message'];
                }
            }
        }
    }

    /**
     * ดึง push tokens สำหรับ notification
     */
    private function getTokensForNotification(MobilePushNotification $notification): array
    {
        $query = MobileDevice::whereNotNull('push_token')
            ->where('is_active', true);

        // กรองตาม target
        switch ($notification->target_type ?? 'all') {
            case 'user':
                if ($notification->target_user_id) {
                    $query->where('user_id', $notification->target_user_id);
                }
                break;

            case 'platform':
                if ($notification->target_platform) {
                    $query->where('platform', $notification->target_platform);
                }
                break;

            case 'all':
            default:
                // ไม่ต้องกรอง
                break;
        }

        return $query->pluck('push_token')->toArray();
    }
}
