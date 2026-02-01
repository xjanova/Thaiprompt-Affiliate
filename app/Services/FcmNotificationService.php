<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\SmsCheckerDevice;
use App\Models\SmsGatewaySubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (FCM) Notification Service
 *
 * ส่ง push notification ไปยังอุปกรณ์ SMS Checker Android เมื่อมีคำสั่งซื้อใหม่
 * รองรับ Multi-Store: route notification ไปถูกร้านค้าเท่านั้น
 *
 * Logic การ route:
 * 1. ดึง seller_id + store_id จาก OrderItems
 * 2. ถ้า seller เป็น Official Shop → push ไป admin devices (store_id IS NULL)
 * 3. ถ้า seller ทั้งหมดเป็นร้านเดียวกัน + มี subscription → push ไปร้านนั้น
 * 4. ถ้า mixed sellers → push ไป admin devices
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
     * ส่ง push notification แบบ Smart Routing ตามร้านค้า
     * เมื่อมีคำสั่งซื้อใหม่ที่รอชำระเงิน
     */
    public function notifyNewOrder(Order $order): void
    {
        $serverKey = config('services.firebase.server_key');
        if (empty($serverKey)) {
            Log::warning('[FCM] Firebase server key not configured (FCM_SERVER_KEY)');
            return;
        }

        // หาอุปกรณ์ที่ควรได้รับ notification
        $devices = $this->resolveTargetDevices($order);

        if ($devices->isEmpty()) {
            Log::info('[FCM] No target devices found for order #' . $order->id);
            return;
        }

        $data = [
            'type' => 'new_order',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number ?? (string) $order->id,
            'amount' => number_format($order->total_amount, 2, '.', ''),
            'payment_method' => $order->payment_method,
            'timestamp' => now()->toIso8601String(),
        ];

        foreach ($devices as $device) {
            try {
                $this->sendToDevice($device, $data);
            } catch (\Exception $e) {
                Log::error("[FCM] Failed to send to device {$device->device_id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * หาอุปกรณ์เป้าหมายสำหรับ order นี้ (Smart Routing)
     *
     * Logic:
     * 1. ดึง store_id ทั้งหมดจาก order items (ผ่าน product.store_id)
     * 2. ถ้าทุก item เป็น Official Shop → ส่งไป admin devices
     * 3. ถ้าทุก item เป็นร้านเดียวกัน + มี SMS Gateway access → ส่งไปร้านนั้น
     * 4. ถ้า mixed stores → ส่งไป admin devices (fallback)
     */
    private function resolveTargetDevices(Order $order): \Illuminate\Support\Collection
    {
        $order->loadMissing('items.product');

        $officialSellerId = Product::getOfficialSellerId();

        // รวบรวม store_id ที่ไม่ซ้ำกัน + ตรวจสอบ Official Shop
        $storeIds = collect();
        $hasOfficialShopItems = false;
        $hasSellerItems = false;

        foreach ($order->items as $item) {
            if ($item->seller_id === $officialSellerId) {
                $hasOfficialShopItems = true;
            } else {
                $hasSellerItems = true;
                if ($item->product && $item->product->store_id) {
                    $storeIds->push($item->product->store_id);
                }
            }
        }

        $uniqueStoreIds = $storeIds->unique()->values();

        // Case 1: ทุก item เป็น Official Shop → ส่งไป admin devices
        if ($hasOfficialShopItems && !$hasSellerItems) {
            Log::info("[FCM] Order #{$order->id} is Official Shop → routing to admin devices");
            return $this->getAdminDevices();
        }

        // Case 2: ทุก item เป็นร้านเดียวกัน
        if ($uniqueStoreIds->count() === 1) {
            $storeId = $uniqueStoreIds->first();

            // เช็คว่าร้านนั้นมี SMS Gateway access หรือไม่
            if (SmsGatewaySubscription::storeHasAccess($storeId)) {
                $storeDevices = $this->getStoreDevices($storeId);

                if ($storeDevices->isNotEmpty()) {
                    Log::info("[FCM] Order #{$order->id} → routing to store #{$storeId} ({$storeDevices->count()} devices)");
                    return $storeDevices;
                }

                // ร้านมี access แต่ไม่มี device → fallback ไป admin
                Log::info("[FCM] Store #{$storeId} has access but no devices → fallback to admin");
            } else {
                Log::info("[FCM] Store #{$storeId} has no SMS Gateway access → routing to admin devices");
            }

            return $this->getAdminDevices();
        }

        // Case 3: Mixed stores (หลายร้าน หรือ official + seller) → ส่งไป admin
        Log::info("[FCM] Order #{$order->id} has mixed stores → routing to admin devices");
        return $this->getAdminDevices();
    }

    /**
     * ดึง admin devices (store_id IS NULL)
     */
    private function getAdminDevices(): \Illuminate\Support\Collection
    {
        return SmsCheckerDevice::adminDevices()
            ->active()
            ->withFcmToken()
            ->get();
    }

    /**
     * ดึง devices ของร้านค้าที่ระบุ
     */
    private function getStoreDevices(int $storeId): \Illuminate\Support\Collection
    {
        return SmsCheckerDevice::forStore($storeId)
            ->active()
            ->withFcmToken()
            ->get();
    }

    /**
     * ส่ง notification ไปยัง devices ของร้านค้าที่ระบุ (ไม่ผ่าน smart routing)
     * ใช้สำหรับกรณีที่รู้ store_id อยู่แล้ว
     */
    public function notifyStore(int $storeId, array $data): void
    {
        $devices = $this->getStoreDevices($storeId);

        if ($devices->isEmpty()) {
            return;
        }

        foreach ($devices as $device) {
            try {
                $this->sendToDevice($device, $data);
            } catch (\Exception $e) {
                Log::error("[FCM] Failed to send to device {$device->device_id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * ส่ง silent sync notification (ไม่แสดง notification แต่ทริกให้แอพ sync)
     * สามารถระบุ storeId เพื่อส่งเฉพาะร้าน หรือ null = ทุกเครื่อง
     */
    public function triggerSync(?int $storeId = null): void
    {
        $serverKey = config('services.firebase.server_key');
        if (empty($serverKey)) {
            return;
        }

        if ($storeId !== null) {
            $devices = $this->getStoreDevices($storeId);
        } else {
            $devices = SmsCheckerDevice::active()->withFcmToken()->get();
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
                Log::debug("[FCM] Notification sent to {$device->device_id}" .
                    ($device->store_id ? " (store #{$device->store_id})" : " (admin)"));
            }
        } else {
            Log::error("[FCM] HTTP error {$response->status()} for device {$device->device_id}");
        }
    }
}
