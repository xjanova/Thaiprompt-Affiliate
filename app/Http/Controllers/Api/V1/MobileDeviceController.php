<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileDevice;
use App\Models\MobileBanner;
use App\Models\PushNotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * MobileDeviceController - API สำหรับ Mobile App
 *
 * จัดการ:
 * 1. Device Registration - ลงทะเบียนเครื่อง
 * 2. Device Heartbeat - บันทึกการใช้งาน
 * 3. Banners - ดึง Banner โฆษณา
 * 4. Push Notification Delivery - ยืนยันการส่ง/ดึง pending notifications
 */
class MobileDeviceController extends Controller
{
    /**
     * ลงทะเบียนเครื่อง
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deviceId' => ['required', 'string', 'max:100'],
            'platform' => ['required', 'in:ios,android'],
            'deviceModel' => ['nullable', 'string', 'max:100'],
            'deviceBrand' => ['nullable', 'string', 'max:100'],
            'osVersion' => ['nullable', 'string', 'max:50'],
            'appVersion' => ['required', 'string', 'max:20'],
            'pushToken' => ['nullable', 'string', 'max:500'],
            'locale' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        // ค้นหาหรือสร้างเครื่องใหม่
        $device = MobileDevice::updateOrCreate(
            ['device_id' => $validated['deviceId']],
            [
                'platform' => $validated['platform'],
                'device_model' => $validated['deviceModel'] ?? null,
                'device_brand' => $validated['deviceBrand'] ?? null,
                'os_version' => $validated['osVersion'] ?? null,
                'app_version' => $validated['appVersion'],
                'push_token' => $validated['pushToken'] ?? null,
                'locale' => $validated['locale'] ?? 'th-TH',
                'timezone' => $validated['timezone'] ?? 'Asia/Bangkok',
                'is_active' => true,
                'last_active_at' => now(),
                'user_id' => auth('sanctum')->id(),
            ]
        );

        $isNewDevice = $device->wasRecentlyCreated;

        return response()->json([
            'success' => true,
            'data' => [
                'deviceId' => $device->device_id,
                'isNewDevice' => $isNewDevice,
            ],
            'message' => $isNewDevice ? 'ลงทะเบียนเครื่องใหม่สำเร็จ' : 'อัปเดทข้อมูลเครื่องสำเร็จ',
        ]);
    }

    /**
     * บันทึก Heartbeat (ใช้คำนวณ DAU/MAU)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deviceId' => ['required', 'string', 'max:100'],
        ]);

        $device = MobileDevice::where('device_id', $validated['deviceId'])->first();

        if ($device) {
            $device->update([
                'last_active_at' => now(),
                'is_active' => true,
                'user_id' => auth('sanctum')->id() ?? $device->user_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Heartbeat recorded',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Device not found',
        ], 404);
    }

    /**
     * ดึง Banners ตามตำแหน่ง
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function banners(Request $request): JsonResponse
    {
        $position = $request->input('position');

        $query = MobileBanner::active()->ordered();

        if ($position) {
            $query->where('position', $position);
        }

        $banners = $query->get()->map(function ($banner) {
            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'image' => $banner->image,
                'link' => $banner->link,
                'linkType' => $banner->link_type,
                'linkTarget' => $banner->link_target,
                'position' => $banner->position,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    /**
     * บันทึกการคลิก Banner
     *
     * @param int $bannerId
     * @return JsonResponse
     */
    public function bannerClick(int $bannerId): JsonResponse
    {
        $banner = MobileBanner::find($bannerId);

        if ($banner) {
            $banner->incrementClicks();

            return response()->json([
                'success' => true,
                'message' => 'Click tracked',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Banner not found',
        ], 404);
    }

    /**
     * ลงทะเบียน Push Token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'in:ios,android'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);

        // ถ้ามี device_id ให้อัปเดท push token ของเครื่องนั้น
        if ($validated['device_id'] ?? null) {
            $device = MobileDevice::where('device_id', $validated['device_id'])->first();
            if ($device) {
                $device->update(['push_token' => $validated['token']]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Push token registered',
        ]);
    }

    // =====================================================
    // Push Notification Delivery Tracking
    // =====================================================

    /**
     * ยืนยันการรับ Push Notification
     * เรียกเมื่อ device ได้รับ notification แล้ว
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmPushDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deviceId' => ['required', 'string', 'max:100'],
            'notificationId' => ['required', 'integer'],
        ]);

        $device = MobileDevice::where('device_id', $validated['deviceId'])->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        $delivery = PushNotificationDelivery::where('device_id', $device->id)
            ->where('notification_id', $validated['notificationId'])
            ->first();

        if ($delivery) {
            $delivery->markAsDelivered();

            return response()->json([
                'success' => true,
                'message' => 'Delivery confirmed',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Delivery record not found',
        ], 404);
    }

    /**
     * ดึง Pending Notifications สำหรับเครื่องที่กลับมา online
     * เรียกเมื่อ device reconnect to internet
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPendingNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deviceId' => ['required', 'string', 'max:100'],
        ]);

        $device = MobileDevice::where('device_id', $validated['deviceId'])->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        // อัปเดท last_active เพื่อบอกว่า device กลับมา online แล้ว
        $device->update(['last_active_at' => now()]);

        // ดึง notifications ที่ยังไม่ได้ส่ง หรือรอ retry
        $pendingDeliveries = PushNotificationDelivery::with('notification')
            ->where('device_id', $device->id)
            ->whereIn('status', [
                PushNotificationDelivery::STATUS_PENDING,
                PushNotificationDelivery::STATUS_RETRY_PENDING,
                PushNotificationDelivery::STATUS_SENT, // ส่งแล้วแต่ยังไม่ได้ยืนยัน
            ])
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $notifications = $pendingDeliveries->map(function ($delivery) {
            $notification = $delivery->notification;
            return [
                'id' => $notification->id,
                'deliveryId' => $delivery->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'image' => $notification->image,
                'data' => $notification->data,
                'createdAt' => $notification->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'count' => $notifications->count(),
            ],
        ]);
    }

    /**
     * Bulk confirm delivery สำหรับหลาย notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkConfirmDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deviceId' => ['required', 'string', 'max:100'],
            'deliveryIds' => ['required', 'array'],
            'deliveryIds.*' => ['integer'],
        ]);

        $device = MobileDevice::where('device_id', $validated['deviceId'])->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);
        }

        $updated = PushNotificationDelivery::whereIn('id', $validated['deliveryIds'])
            ->where('device_id', $device->id)
            ->update([
                'status' => PushNotificationDelivery::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'confirmed' => $updated,
            ],
            'message' => "Confirmed {$updated} deliveries",
        ]);
    }

    /**
     * ดึงสถิติ Push Notification สำหรับ Analytics Dashboard
     *
     * @return JsonResponse
     */
    public function pushAnalytics(): JsonResponse
    {
        // จำนวนการส่งทั้งหมด
        $totalDeliveries = PushNotificationDelivery::count();

        // แยกตามสถานะ
        $byStatus = PushNotificationDelivery::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Success rate
        $delivered = $byStatus[PushNotificationDelivery::STATUS_DELIVERED] ?? 0;
        $failed = $byStatus[PushNotificationDelivery::STATUS_FAILED] ?? 0;
        $totalFinished = $delivered + $failed;
        $successRate = $totalFinished > 0 ? round(($delivered / $totalFinished) * 100, 1) : 0;

        // การส่งใน 7 วันที่ผ่านมา (สำหรับกราฟ)
        $deliveriesLast7Days = PushNotificationDelivery::selectRaw(
            'DATE(created_at) as date, status, COUNT(*) as count'
        )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date', 'status')
            ->get()
            ->groupBy('date')
            ->map(function ($items) {
                $result = [
                    'delivered' => 0,
                    'failed' => 0,
                    'pending' => 0,
                ];
                foreach ($items as $item) {
                    if ($item->status === PushNotificationDelivery::STATUS_DELIVERED) {
                        $result['delivered'] = $item->count;
                    } elseif ($item->status === PushNotificationDelivery::STATUS_FAILED) {
                        $result['failed'] = $item->count;
                    } else {
                        $result['pending'] += $item->count;
                    }
                }
                return $result;
            });

        // จำนวน retry pending
        $retryPending = $byStatus[PushNotificationDelivery::STATUS_RETRY_PENDING] ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalDeliveries,
                'byStatus' => $byStatus,
                'successRate' => $successRate,
                'retryPending' => $retryPending,
                'last7Days' => $deliveriesLast7Days,
            ],
        ]);
    }
}
