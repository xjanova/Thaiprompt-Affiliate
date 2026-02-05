<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * จัดการระบบ SMS Payment Checker ในหน้า Admin
 *
 * รองรับ:
 * - แดชบอร์ดสรุปภาพรวม
 * - จัดการอุปกรณ์ (CRUD + เปลี่ยนสถานะ)
 * - ดูประวัติ SMS Notifications
 * - ดูสถานะ Unique Amounts
 */
class SmsCheckerAdminController extends Controller
{
    /**
     * แดชบอร์ดสรุปภาพรวมระบบ SMS Checker
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // สถิติอุปกรณ์
        $deviceStats = [
            'total' => SmsCheckerDevice::count(),
            'active' => SmsCheckerDevice::where('status', 'active')->count(),
            'inactive' => SmsCheckerDevice::where('status', 'inactive')->count(),
            'blocked' => SmsCheckerDevice::where('status', 'blocked')->count(),
        ];

        // สถิติ notifications
        $notificationStats = [
            'total' => SmsPaymentNotification::count(),
            'pending' => SmsPaymentNotification::where('status', 'pending')->count(),
            'matched' => SmsPaymentNotification::where('status', 'matched')->count(),
            'confirmed' => SmsPaymentNotification::where('status', 'confirmed')->count(),
            'today' => SmsPaymentNotification::whereDate('created_at', today())->count(),
        ];

        // สถิติ unique amounts
        $amountStats = [
            'active' => UniquePaymentAmount::where('status', 'reserved')
                ->where('expires_at', '>', now())->count(),
            'used' => UniquePaymentAmount::where('status', 'used')->count(),
            'expired' => UniquePaymentAmount::where('status', 'expired')->count(),
        ];

        // อุปกรณ์ล่าสุดที่ active
        $recentDevices = SmsCheckerDevice::where('status', 'active')
            ->orderBy('last_active_at', 'desc')
            ->limit(5)
            ->get();

        // notifications ล่าสุด
        $recentNotifications = SmsPaymentNotification::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.smschecker.index', compact(
            'deviceStats',
            'notificationStats',
            'amountStats',
            'recentDevices',
            'recentNotifications'
        ));
    }

    /**
     * แสดงรายการอุปกรณ์ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function devices(Request $request)
    {
        $query = SmsCheckerDevice::query();

        // ค้นหา
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('device_name', 'like', "%{$search}%")
                  ->orWhere('device_id', 'like', "%{$search}%");
            });
        }

        // กรองสถานะ
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $devices = $query->withCount('notifications')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.smschecker.devices', compact('devices'));
    }

    /**
     * แสดงฟอร์มสร้างอุปกรณ์ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function createDevice()
    {
        return view('admin.smschecker.create-device');
    }

    /**
     * บันทึกอุปกรณ์ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'device_name' => 'required|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $device = SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-' . strtoupper(bin2hex(random_bytes(4))),
            'device_name' => $validated['device_name'],
            'api_key' => SmsCheckerDevice::generateApiKey(),
            'secret_key' => SmsCheckerDevice::generateSecretKey(),
            'platform' => 'android',
            'status' => 'active',
            'user_id' => $validated['user_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.smschecker.device-show', $device)
            ->with('success', 'สร้างอุปกรณ์ SMS Checker สำเร็จ!');
    }

    /**
     * แสดงรายละเอียดอุปกรณ์
     *
     * @param SmsCheckerDevice $device
     * @return \Illuminate\View\View
     */
    public function showDevice(SmsCheckerDevice $device)
    {
        $device->load('user');

        // notifications ล่าสุดของอุปกรณ์นี้
        $notifications = SmsPaymentNotification::where('device_id', $device->device_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // สถิติของอุปกรณ์นี้
        $stats = [
            'total_notifications' => SmsPaymentNotification::where('device_id', $device->device_id)->count(),
            'matched' => SmsPaymentNotification::where('device_id', $device->device_id)
                ->where('status', 'matched')->count(),
            'pending' => SmsPaymentNotification::where('device_id', $device->device_id)
                ->where('status', 'pending')->count(),
            'today' => SmsPaymentNotification::where('device_id', $device->device_id)
                ->whereDate('created_at', today())->count(),
        ];

        return view('admin.smschecker.show-device', compact('device', 'notifications', 'stats'));
    }

    /**
     * เปลี่ยนสถานะอุปกรณ์ (toggle active/inactive/blocked)
     *
     * @param Request $request
     * @param SmsCheckerDevice $device
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleDeviceStatus(Request $request, SmsCheckerDevice $device)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,blocked',
        ]);

        $device->update(['status' => $validated['status']]);

        $statusLabels = [
            'active' => 'เปิดใช้งาน',
            'inactive' => 'ปิดใช้งาน',
            'blocked' => 'บล็อก',
        ];

        return redirect()
            ->back()
            ->with('success', "เปลี่ยนสถานะอุปกรณ์เป็น \"{$statusLabels[$validated['status']]}\" สำเร็จ!");
    }

    /**
     * สร้าง API Key ใหม่สำหรับอุปกรณ์
     *
     * @param SmsCheckerDevice $device
     * @return \Illuminate\Http\RedirectResponse
     */
    public function regenerateKeys(SmsCheckerDevice $device)
    {
        $device->update([
            'api_key' => SmsCheckerDevice::generateApiKey(),
            'secret_key' => SmsCheckerDevice::generateSecretKey(),
        ]);

        return redirect()
            ->route('admin.smschecker.device-show', $device)
            ->with('success', 'สร้าง API Key และ Secret Key ใหม่สำเร็จ!');
    }

    /**
     * ลบอุปกรณ์
     *
     * @param SmsCheckerDevice $device
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyDevice(SmsCheckerDevice $device)
    {
        $deviceName = $device->device_name ?? $device->device_id;
        $device->delete();

        return redirect()
            ->route('admin.smschecker.devices')
            ->with('success', "ลบอุปกรณ์ \"{$deviceName}\" สำเร็จ!");
    }

    /**
     * แสดงประวัติ SMS Notifications ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function notifications(Request $request)
    {
        $query = SmsPaymentNotification::query();

        // กรองสถานะ
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // กรองธนาคาร
        if ($bank = $request->input('bank')) {
            $query->where('bank', $bank);
        }

        // กรองประเภท
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // ค้นหา
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('sender_or_receiver', 'like', "%{$search}%")
                  ->orWhere('device_id', 'like', "%{$search}%");
            });
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate(30)
            ->appends($request->query());

        $banks = config('smschecker.supported_banks', []);

        return view('admin.smschecker.notifications', compact('notifications', 'banks'));
    }

    /**
     * แสดงหน้า QR Code สำหรับ provisioning อุปกรณ์
     *
     * Android App จะสแกน QR Code นี้เพื่อรับค่า API Key, Secret Key
     * และ URL ของ server
     *
     * @param SmsCheckerDevice $device
     * @return \Illuminate\View\View
     */
    public function qrCode(SmsCheckerDevice $device)
    {
        $qrData = [
            'type' => 'smschecker_config',
            'version' => 1,
            'url' => config('app.url'),
            'apiKey' => $device->api_key,
            'secretKey' => $device->secret_key,
            'deviceId' => $device->device_id,
            'deviceName' => $device->device_name ?? $device->device_id,
        ];

        return view('admin.smschecker.qr-code', compact('device', 'qrData'));
    }

    /**
     * ส่งข้อมูล QR Code เป็น JSON สำหรับ API
     *
     * @param SmsCheckerDevice $device
     * @return \Illuminate\Http\JsonResponse
     */
    public function qrCodeJson(SmsCheckerDevice $device)
    {
        return response()->json([
            'type' => 'smschecker_config',
            'version' => 1,
            'url' => config('app.url'),
            'apiKey' => $device->api_key,
            'secretKey' => $device->secret_key,
            'deviceId' => $device->device_id,
            'deviceName' => $device->device_name ?? $device->device_id,
        ]);
    }

    /**
     * แสดงหน้าตั้งค่าระบบ SMS Checker
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $settings = [
            'enabled' => config('smschecker.enabled', true),
            'timestamp_tolerance' => config('smschecker.timestamp_tolerance', 300),
            'unique_amount_expiry' => config('smschecker.unique_amount_expiry', 60),
            'max_pending_per_amount' => config('smschecker.max_pending_per_amount', 99),
            'rate_limit_per_minute' => config('smschecker.rate_limit_per_minute', 30),
            'default_approval_mode' => config('smschecker.default_approval_mode', 'auto'),
            'nonce_expiry_hours' => config('smschecker.nonce_expiry_hours', 24),
            'auto_confirm_matched' => config('smschecker.auto_confirm_matched', true),
            'sync_interval' => config('smschecker.sync.interval', 30),
        ];

        $supportedBanks = config('smschecker.supported_banks', []);

        return view('admin.smschecker.settings', compact('settings', 'supportedBanks'));
    }

    /**
     * บันทึกการตั้งค่าระบบ
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        // ในเวอร์ชันนี้การตั้งค่าเก็บใน config file
        // สามารถเปลี่ยนเป็น database settings ได้ในอนาคต

        return redirect()
            ->back()
            ->with('info', 'การตั้งค่าถูกจัดเก็บใน config/smschecker.php กรุณาแก้ไขไฟล์โดยตรง');
    }

    /**
     * แสดงรายการคำสั่งซื้อที่รอการตรวจสอบชำระเงิน
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function pendingOrders(Request $request)
    {
        $query = Order::with(['user', 'paymentTransaction'])
            ->where('payment_status', 'pending')
            ->whereIn('payment_method', ['promptpay', 'bank_transfer']);

        // ค้นหา
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // รวม notifications ที่ยังไม่ได้จับคู่
        $unmatchedNotifications = SmsPaymentNotification::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.smschecker.pending-orders', compact('orders', 'unmatchedNotifications'));
    }

    /**
     * ยืนยันการชำระเงินด้วยตนเอง
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirmPayment(Request $request, Order $order)
    {
        try {
            DB::beginTransaction();

            // อัปเดตสถานะ order
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);

            // อัปเดต transaction ถ้ามี
            if ($order->paymentTransaction) {
                $order->paymentTransaction->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'metadata' => array_merge($order->paymentTransaction->metadata ?? [], [
                        'manual_confirmed_at' => now()->toIso8601String(),
                        'confirmed_by' => auth()->id(),
                    ]),
                ]);
            }

            // อัปเดต unique amount ถ้ามี
            $uniqueAmountId = $order->paymentTransaction?->metadata['unique_amount_id'] ?? null;
            if ($uniqueAmountId) {
                UniquePaymentAmount::where('id', $uniqueAmountId)->update(['status' => 'used']);
            }

            DB::commit();

            Log::info("[SMS Checker] Admin ยืนยันการชำระเงิน Order #{$order->id} โดย User #" . auth()->id());

            return redirect()
                ->back()
                ->with('success', "ยืนยันการชำระเงินคำสั่งซื้อ #{$order->id} สำเร็จ!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[SMS Checker] ยืนยันการชำระเงินล้มเหลว: " . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ปฏิเสธการชำระเงิน
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectPayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // อัปเดตสถานะ order
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);

            // อัปเดต transaction ถ้ามี
            if ($order->paymentTransaction) {
                $order->paymentTransaction->update([
                    'status' => 'failed',
                    'metadata' => array_merge($order->paymentTransaction->metadata ?? [], [
                        'rejected_at' => now()->toIso8601String(),
                        'rejected_by' => auth()->id(),
                        'rejection_reason' => $validated['reason'] ?? 'ยกเลิกโดยแอดมิน',
                    ]),
                ]);
            }

            // คืน unique amount ถ้ามี
            $uniqueAmountId = $order->paymentTransaction?->metadata['unique_amount_id'] ?? null;
            if ($uniqueAmountId) {
                UniquePaymentAmount::where('id', $uniqueAmountId)->update(['status' => 'cancelled']);
            }

            DB::commit();

            Log::info("[SMS Checker] Admin ปฏิเสธการชำระเงิน Order #{$order->id} โดย User #" . auth()->id());

            return redirect()
                ->back()
                ->with('success', "ปฏิเสธการชำระเงินคำสั่งซื้อ #{$order->id} สำเร็จ");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[SMS Checker] ปฏิเสธการชำระเงินล้มเหลว: " . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
