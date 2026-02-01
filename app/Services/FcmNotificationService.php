<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SmsCheckerDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (FCM) Notification Service
 *
 * ส่ง push notification ไปยังอุปกรณ์ SMS Checker Android เมื่อมีคำสั่งซื้อใหม่
 * ใช้ FCM HTTP v1 API ผ่าน Service Account Key
 *
 * วิธีตั้งค่า:
 * 1. สร้าง Firebase project ที่ https://console.firebase.google.com
 * 2. ดาวน์โหลด Service Account JSON key
 * 3. ตั้งค่า .env:
 *    FCM_SERVER_KEY=your_legacy_server_key
 *    FCM_PROJECT_ID=your-project-id
 */
class FcmNotificationService
{
    /**
     * ส่ง push notification ไปยังอุปกรณ์ SMS Checker ทุกตัวที่ active
     * เมื่อมีคำสั่งซื้อใหม่ที่รอชำระเงิน
     */
    public function notifyNewOrder(Order $order): void
    {
        $devices = SmsCheckerDevice::where('status', 'active')
            ->whereNotNull('fcm_token')
            ->get();

        if ($devices->isEmpty()) {
            Log::info('[FCM] No active devices with FCM token to notify');
            return;
        }

        $serverKey = config('services.firebase.server_key');
        if (empty($serverKey)) {
            Log::warning('[FCM] Firebase server key not configured (FCM_SERVER_KEY)');
            return;
        }

        foreach ($devices as $device) {
            try {
                $this->sendToDevice($device, [
                    'type' => 'new_order',
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number ?? (string) $order->id,
                    'amount' => number_format($order->total_amount, 2, '.', ''),
                    'payment_method' => $order->payment_method,
                    'timestamp' => now()->toIso8601String(),
                ]);
            } catch (\Exception $e) {
                Log::error("[FCM] Failed to send to device {$device->device_id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * ส่ง silent sync notification (ไม่แสดง notification แต่ทริกให้แอพ sync)
     */
    public function triggerSync(): void
    {
        $devices = SmsCheckerDevice::where('status', 'active')
            ->whereNotNull('fcm_token')
            ->get();

        $serverKey = config('services.firebase.server_key');
        if (empty($serverKey)) {
            return;
        }

        foreach ($devices as $device) {
            try {
                $this->sendToDevice($device, [
                    'type' => 'sync',
                    'timestamp' => now()->toIso8601String(),
                ]);
            } catch (\Exception $e) {
                Log::error("[FCM] Sync trigger failed for {$device->device_id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * ส่ง FCM data message ไปยังอุปกรณ์ (ใช้ Legacy HTTP API สำหรับความง่าย)
     */
    private function sendToDevice(SmsCheckerDevice $device, array $data): void
    {
        $serverKey = config('services.firebase.server_key');

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $device->fcm_token,
            'data' => $data,
            'priority' => 'high',
            'time_to_live' => 300, // 5 minutes TTL
        ]);

        if ($response->successful()) {
            $body = $response->json();
            if (($body['failure'] ?? 0) > 0) {
                // Token อาจหมดอายุ — ลบ token เก่าออก
                $results = $body['results'] ?? [];
                foreach ($results as $result) {
                    $error = $result['error'] ?? null;
                    if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
                        Log::info("[FCM] Removing invalid FCM token for device {$device->device_id}");
                        $device->update(['fcm_token' => null, 'fcm_token_updated_at' => null]);
                    }
                }
            } else {
                Log::debug("[FCM] Notification sent to {$device->device_id}");
            }
        } else {
            Log::error("[FCM] HTTP error {$response->status()} for device {$device->device_id}");
        }
    }
}
