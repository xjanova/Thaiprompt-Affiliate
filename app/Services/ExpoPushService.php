<?php

namespace App\Services;

use App\Models\MobileDevice;
use App\Models\MobilePushNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ExpoPushService - ส่ง Push Notification ผ่าน Expo Push API
 *
 * Expo Push Token format: ExponentPushToken[xxxxx]
 * ใช้ API endpoint: https://exp.host/--/api/v2/push/send
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
     * @param string $pushToken Expo Push Token
     * @param string $title หัวข้อ
     * @param string $body เนื้อหา
     * @param array $data ข้อมูลเพิ่มเติม
     * @return array
     */
    public function sendToDevice(string $pushToken, string $title, string $body, array $data = []): array
    {
        return $this->send([$pushToken], $title, $body, $data);
    }

    /**
     * ส่ง Push Notification ไปยังหลายเครื่อง
     *
     * @param array $pushTokens รายการ Expo Push Token
     * @param string $title หัวข้อ
     * @param string $body เนื้อหา
     * @param array $data ข้อมูลเพิ่มเติม
     * @return array
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

        // สร้าง messages สำหรับแต่ละ token
        $messages = [];
        foreach ($pushTokens as $token) {
            if (!$this->isValidExpoPushToken($token)) {
                $results['failed']++;
                $results['errors'][] = "Invalid token: {$token}";
                continue;
            }

            $messages[] = [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => $data['channel'] ?? 'default',
            ];
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
                    $results['errors'][] = 'HTTP Error: ' . $response->status();
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
     *
     * @param MobilePushNotification $notification
     * @return array
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
            'error_message' => !empty($results['errors']) ? implode(', ', $results['errors']) : null,
        ]);

        return $results;
    }

    /**
     * ส่งไปยัง User เฉพาะ
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
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

    /**
     * ส่งไปยังทุกเครื่อง (Broadcast)
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
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
     * @param string $platform 'ios' หรือ 'android'
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
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

    /**
     * ตรวจสอบว่าเป็น Expo Push Token ที่ถูกต้องหรือไม่
     *
     * รองรับรูปแบบ:
     * - ExponentPushToken[xxxx]
     * - ExpoPushToken[xxxx]
     *
     * @param string $token
     * @return bool
     */
    private function isValidExpoPushToken(string $token): bool
    {
        // รองรับทั้ง ExponentPushToken และ ExpoPushToken
        return preg_match('/^Expo(nent)?PushToken\[.+\]$/', $token) === 1;
    }

    /**
     * ประมวลผล response จาก Expo API
     *
     * @param array $responseData
     * @param array &$results
     */
    private function processResponse(array $responseData, array &$results): void
    {
        if (!isset($responseData['data'])) {
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
     *
     * @param MobilePushNotification $notification
     * @return array
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
