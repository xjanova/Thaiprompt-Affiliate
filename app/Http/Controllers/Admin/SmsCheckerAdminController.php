<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}
