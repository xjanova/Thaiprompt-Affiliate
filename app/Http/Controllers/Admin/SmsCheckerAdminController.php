<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\SiteSetting;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Models\VendorStore;
use App\Services\FcmNotificationService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'device_name' => 'required|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'store_id' => 'nullable|integer|exists:vendor_stores,id',
        ]);

        // ถ้าไม่ได้ระบุ store_id → ใช้ platformStoreId (ร้าน admin/official)
        // เพื่อให้ device ผูกกับร้านเสมอ (ป้องกัน null store_id)
        $storeId = $validated['store_id'] ?? VendorStore::getPlatformStoreId();

        $device = SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-'.strtoupper(bin2hex(random_bytes(4))),
            'device_name' => $validated['device_name'],
            'api_key' => SmsCheckerDevice::generateApiKey(),
            'secret_key' => SmsCheckerDevice::generateSecretKey(),
            'platform' => 'android',
            'status' => 'active',
            'user_id' => $validated['user_id'] ?? null,
            'store_id' => $storeId,
        ]);

        return redirect()
            ->route('admin.smschecker.device-show', $device)
            ->with('success', 'สร้างอุปกรณ์ SMS Checker สำเร็จ!');
    }

    /**
     * แสดงรายละเอียดอุปกรณ์
     *
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
     * ลบ FCM Token ของอุปกรณ์ (สำหรับ reset token ที่ไม่ถูกต้อง)
     *
     * @param SmsCheckerDevice $device
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearFcmToken(SmsCheckerDevice $device)
    {
        $deviceName = $device->device_name ?? $device->device_id;

        $device->update([
            'fcm_token' => null,
            'fcm_token_updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\Log::info('Admin: ลบ FCM token ของอุปกรณ์', [
            'device_id' => $device->device_id,
            'device_name' => $deviceName,
        ]);

        return redirect()
            ->route('admin.smschecker.device-show', $device)
            ->with('success', "ลบ FCM Token ของ \"{$deviceName}\" สำเร็จ! แอพจะลงทะเบียน token ใหม่เมื่อเปิดใช้งาน");
    }

    /**
     * แสดงประวัติ SMS Notifications ทั้งหมด
     *
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
     * @return \Illuminate\View\View
     */
    public function qrCode(SmsCheckerDevice $device)
    {
        // Config format ตรงกับ xman studio version 2 (camelCase สำหรับ Android app)
        $qrData = [
            'type' => 'smschecker_config',
            'version' => 2,
            'url' => config('app.url'),
            'apiKey' => $device->api_key,
            'secretKey' => $device->secret_key,
            'deviceId' => $device->device_id,
            'deviceName' => $device->device_name ?? $device->device_id,
            'sync_interval' => (int) config('smschecker.sync.interval', 5),
        ];

        // สร้าง SVG QR Code server-side ด้วย BaconQrCode (สแกนได้เร็วกว่า JS)
        // ใช้ Error Correction Level H (High) = recover 30% data loss → กล้องจับได้ไวมาก
        $qrCodeSvg = '';
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(300, 1, null, null,
                    \BaconQrCode\Renderer\RendererStyle\Fill::uniformColor(
                        new \BaconQrCode\Renderer\Color\Rgb(255, 255, 255),
                        new \BaconQrCode\Renderer\Color\Rgb(0, 0, 0)
                    )
                ),
                new SvgImageBackEnd
            );
            $writer = new Writer($renderer);
            $qrCodeSvg = $writer->writeString(
                json_encode($qrData),
                \BaconQrCode\Encoder\Encoder::DEFAULT_BYTE_MODE_ECODING,
                \BaconQrCode\Common\ErrorCorrectionLevel::H()
            );
        } catch (\Throwable $e) {
            Log::warning('QR Code SVG generation failed, will use JS fallback: '.$e->getMessage());
        }

        return view('admin.smschecker.qr-code', compact('device', 'qrData', 'qrCodeSvg'));
    }

    /**
     * ส่งข้อมูล QR Code เป็น JSON สำหรับ API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function qrCodeJson(SmsCheckerDevice $device)
    {
        return response()->json([
            'type' => 'smschecker_config',
            'version' => 2,
            'url' => config('app.url'),
            'apiKey' => $device->api_key,
            'secretKey' => $device->secret_key,
            'deviceId' => $device->device_id,
            'deviceName' => $device->device_name ?? $device->device_id,
            'sync_interval' => (int) config('smschecker.sync.interval', 5),
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
            'sync_interval' => config('smschecker.sync.interval', 5),
        ];

        $supportedBanks = config('smschecker.supported_banks', []);

        // FCM settings
        $fcmEnabled = Setting::get('fcm_enabled', false);
        $fcmProjectId = Setting::get('fcm_project_id', '');
        $fcmCredentialsPath = Setting::get('fcm_credentials_path', '');
        $fcmServiceAccount = null;

        if ($fcmCredentialsPath && file_exists($fcmCredentialsPath)) {
            try {
                $creds = json_decode(file_get_contents($fcmCredentialsPath), true);
                $fcmServiceAccount = $creds['client_email'] ?? null;
            } catch (\Exception $e) {
                // ignore
            }
        }

        // URL ดาวน์โหลดแอป SmsChecker (ตั้งค่าได้จาก admin)
        $siteSettings = SiteSetting::getSetting();
        $appDownloadUrl = $siteSettings->smschecker_app_download_url
            ?: 'https://github.com/xjanova/SmsChecker/releases/latest';

        return view('admin.smschecker.settings', compact(
            'settings', 'supportedBanks',
            'fcmEnabled', 'fcmProjectId', 'fcmCredentialsPath', 'fcmServiceAccount',
            'appDownloadUrl'
        ));
    }

    /**
     * บันทึกการตั้งค่าระบบ
     *
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
     * บันทึกการตั้งค่า FCM
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFcmSettings(Request $request)
    {
        $request->validate([
            'fcm_project_id' => 'required|string|max:100',
            'fcm_credentials' => 'nullable|file|mimes:json|max:512',
            'fcm_enabled' => 'nullable|boolean',
        ]);

        // Save Project ID
        Setting::set('fcm_project_id', $request->fcm_project_id, 'string', 'fcm');

        // Save enabled status
        Setting::set('fcm_enabled', $request->boolean('fcm_enabled') ? '1' : '0', 'boolean', 'fcm');

        // Handle JSON file upload
        if ($request->hasFile('fcm_credentials')) {
            $file = $request->file('fcm_credentials');

            // Validate JSON content
            $content = file_get_contents($file->getRealPath());
            $json = json_decode($content, true);

            if (! $json || empty($json['project_id']) || empty($json['private_key']) || empty($json['client_email'])) {
                $errorMsg = 'ไฟล์ JSON ไม่ถูกต้อง ต้องเป็น Firebase Service Account JSON';

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }

                return redirect()->back()->with('error', $errorMsg);
            }

            // Save to storage
            $storagePath = storage_path('app/firebase-credentials.json');
            file_put_contents($storagePath, $content);
            chmod($storagePath, 0600);

            Setting::set('fcm_credentials_path', $storagePath, 'string', 'fcm');

            Log::info('FCM: Credentials file uploaded', [
                'admin_id' => auth()->id(),
                'service_account' => $json['client_email'],
            ]);
        } else {
            // ✅ ถ้าไม่ได้อัพโหลดไฟล์ใหม่ แต่ไฟล์มีอยู่แล้ว → บันทึก path ลง Setting DB
            $existingPath = Setting::get('fcm_credentials_path');
            if (empty($existingPath) || ! file_exists($existingPath)) {
                $defaultPath = storage_path('app/firebase-credentials.json');
                if (file_exists($defaultPath)) {
                    Setting::set('fcm_credentials_path', $defaultPath, 'string', 'fcm');
                    Log::info('FCM: Auto-detected existing credentials file', [
                        'path' => $defaultPath,
                    ]);
                }
            }
        }

        $successMsg = 'บันทึกการตั้งค่า FCM เรียบร้อยแล้ว';

        // ถ้าเป็น AJAX request → return JSON (ป้องกัน session หมดอายุจาก form submit)
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
            ]);
        }

        return redirect()
            ->route('admin.smschecker.settings')
            ->with('success', $successMsg);
    }

    /**
     * บันทึก URL ดาวน์โหลดแอป SmsChecker
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateDownloadUrl(Request $request)
    {
        $request->validate([
            'smschecker_app_download_url' => 'nullable|url|max:500',
        ]);

        $siteSettings = SiteSetting::getSetting();
        $siteSettings->smschecker_app_download_url = $request->input('smschecker_app_download_url') ?: null;
        $siteSettings->save();

        // ล้าง cache ของ SiteSetting
        cache()->forget('site_settings');

        $successMsg = 'บันทึก URL ดาวน์โหลดแอป SmsChecker เรียบร้อยแล้ว';

        Log::info('SmsChecker: Download URL updated', [
            'admin_id' => auth()->id(),
            'url' => $request->input('smschecker_app_download_url'),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
            ]);
        }

        return redirect()
            ->route('admin.smschecker.settings')
            ->with('success', $successMsg);
    }

    /**
     * ทดสอบส่ง FCM push notification
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testFcm(Request $request)
    {
        try {
            $fcmService = app(FcmNotificationService::class);

            // ใช้ testPush() แทน triggerSync() เพื่อให้ได้ข้อมูล debug ละเอียด
            $result = $fcmService->testPush();

            // ถ้าเป็น AJAX request → return JSON พร้อม details
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($result);
            }

            $flashType = $result['success'] ? 'success' : 'error';

            return redirect()
                ->route('admin.smschecker.settings')
                ->with($flashType, $result['message']);

        } catch (\Exception $e) {
            Log::error('FCM Test: Exception', ['error' => $e->getMessage()]);

            $errorMsg = 'เกิดข้อผิดพลาด: '.$e->getMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 500);
            }

            return redirect()
                ->route('admin.smschecker.settings')
                ->with('error', $errorMsg);
        }
    }

    /**
     * แสดงรายการคำสั่งซื้อที่รอการตรวจสอบชำระเงิน
     *
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

            Log::info("[SMS Checker] Admin ยืนยันการชำระเงิน Order #{$order->id} โดย User #".auth()->id());

            return redirect()
                ->back()
                ->with('success', "ยืนยันการชำระเงินคำสั่งซื้อ #{$order->id} สำเร็จ!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[SMS Checker] ยืนยันการชำระเงินล้มเหลว: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ปฏิเสธการชำระเงิน
     *
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

            Log::info("[SMS Checker] Admin ปฏิเสธการชำระเงิน Order #{$order->id} โดย User #".auth()->id());

            return redirect()
                ->back()
                ->with('success', "ปฏิเสธการชำระเงินคำสั่งซื้อ #{$order->id} สำเร็จ");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[SMS Checker] ปฏิเสธการชำระเงินล้มเหลว: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ยืนยันการชำระเงิน Wallet Topup ด้วยมือ (กรณี auto-complete ล้มเหลว)
     *
     * ใช้สำหรับ recovery เมื่อ SMS match สำเร็จ แต่ completePayment() ล้มเหลว
     * จะเรียก PaymentService::completePayment() ใหม่อีกครั้ง
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function retryCompleteTransaction(Request $request, int $transactionId)
    {
        try {
            $transaction = \App\Models\PaymentTransaction::findOrFail($transactionId);

            // ตรวจสอบว่าเป็น wallet_topup เท่านั้น
            if ($transaction->type !== 'wallet_topup') {
                return response()->json([
                    'success' => false,
                    'message' => 'ใช้ได้เฉพาะ wallet_topup transactions เท่านั้น',
                ], 400);
            }

            // ตรวจสอบว่ายังไม่ completed
            if ($transaction->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction นี้ completed แล้ว',
                ]);
            }

            // เรียก completePayment ใหม่
            $paymentService = app(\App\Services\Payment\PaymentService::class);
            $result = $paymentService->completePayment($transaction);

            Log::info('[SMS Checker] Admin retry completePayment สำเร็จ', [
                'transaction_id' => $transaction->id,
                'admin_id' => auth()->id(),
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'เติมเงินสำเร็จ! Transaction #'.$transaction->id.' completed',
                'transaction_status' => $transaction->fresh()->status,
            ]);

        } catch (\Exception $e) {
            Log::error('[SMS Checker] Admin retry completePayment ล้มเหลว', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Debug page for fortune reading → SmsChecker order transformation.
     * Shows what the API would return and identifies potential issues.
     */
    public function debugFortuneSmsChecker()
    {
        // 1. Fortune Reading status counts
        $statusCounts = \App\Models\FortuneReading::query()
            ->selectRaw('conversation_status, COUNT(*) as count')
            ->groupBy('conversation_status')
            ->pluck('count', 'conversation_status')
            ->toArray();

        // 2. Fortune readings eligible for SmsChecker (same query as orders() endpoint)
        $eligibleStatuses = ['pending_payment', 'paid', 'completed'];
        $fortuneReadings = \App\Models\FortuneReading::query()
            ->whereIn('conversation_status', $eligibleStatuses)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // 3. Transform each using same logic as SmsPaymentController
        $transformed = [];
        $validationIssues = [];
        $smsPaymentController = app(\App\Http\Controllers\Api\V1\SmsPaymentController::class);
        $idOffset = 10000000;

        foreach ($fortuneReadings as $reading) {
            // Use reflection to call the private transform method, or replicate logic
            $order = $this->transformFortuneForDebug($reading, $idOffset);
            $transformed[] = $order;

            // Validate
            $issues = [];
            if (empty($order['order_details_json']['order_number'])) {
                $issues[] = 'Missing order_number (bill_reference is null)';
            }
            if (empty($order['order_details_json']['amount']) || $order['order_details_json']['amount'] == 0) {
                $issues[] = 'Amount is 0 or missing';
            }
            if (empty($order['approval_status'])) {
                $issues[] = 'Missing approval_status';
            }
            if (!empty($issues)) {
                $validationIssues[] = [
                    'fortune_reading_id' => $reading->id,
                    'offset_id' => $reading->id + $idOffset,
                    'bill_reference' => $reading->bill_reference,
                    'issues' => $issues,
                ];
            }
        }

        // 4. Active devices
        $devices = \App\Models\SmsCheckerDevice::query()
            ->where('status', 'active')
            ->get()
            ->map(function ($d) {
                return [
                    'device_id' => $d->device_id,
                    'device_name' => $d->device_name,
                    'last_active_at' => $d->last_active_at?->toIso8601String(),
                    'fcm_token_length' => strlen($d->fcm_token ?? ''),
                    'has_fcm_token' => !empty($d->fcm_token),
                ];
            });

        // 5. syncOrders specific check: fortune readings with bill_reference
        $withBillRef = \App\Models\FortuneReading::query()
            ->whereIn('conversation_status', $eligibleStatuses)
            ->whereNotNull('bill_reference')
            ->count();
        $withoutBillRef = \App\Models\FortuneReading::query()
            ->whereIn('conversation_status', $eligibleStatuses)
            ->whereNull('bill_reference')
            ->count();

        return view('admin.smschecker.debug-fortune', [
            'statusCounts' => $statusCounts,
            'fortuneReadings' => $fortuneReadings,
            'transformed' => $transformed,
            'validationIssues' => $validationIssues,
            'devices' => $devices,
            'withBillRef' => $withBillRef,
            'withoutBillRef' => $withoutBillRef,
            'idOffset' => $idOffset,
        ]);
    }

    /**
     * Replicate SmsPaymentController::transformFortuneReadingToOrderApproval()
     * with extra debug fields.
     */
    private function transformFortuneForDebug($reading, int $idOffset): array
    {
        $statusMap = [
            'pending_payment' => 'pending_review',
            'paid' => 'auto_approved',
            'completed' => 'auto_approved',
            'cancelled' => 'cancelled',
            'expired' => 'expired',
        ];

        $approvalStatus = $statusMap[$reading->conversation_status] ?? 'pending_review';
        $amount = 0;

        // Try to get amount from bill_amount or fortune_type pricing
        if ($reading->bill_amount) {
            $amount = (float) $reading->bill_amount;
        } elseif ($reading->fortune_type) {
            // Look up pricing
            $pricing = \App\Models\FortuneType::where('slug', $reading->fortune_type)->first();
            if ($pricing) {
                $amount = (float) $pricing->price;
            }
        }

        return [
            'id' => $reading->id + $idOffset,
            'notification_id' => null,
            'matched_transaction_id' => null,
            'device_id' => null,
            'approval_status' => $approvalStatus,
            'confidence' => ($approvalStatus === 'pending_review') ? 'medium' : 'high',
            'approved_by' => ($approvalStatus !== 'pending_review') ? 'system' : null,
            'approved_at' => ($reading->paid_at) ? $reading->paid_at->toIso8601String() : null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'order_details_json' => [
                'order_number' => $reading->bill_reference,
                'product_name' => 'ดูดวง: ' . ($reading->fortune_type ?? 'ทั่วไป'),
                'product_details' => $reading->question ?? null,
                'amount' => $amount,
                'customer_name' => $reading->customer_name ?? ($reading->line_user_id ? 'LINE:' . substr($reading->line_user_id, 0, 8) : null),
                'website_name' => 'thaiprompt.com',
            ],
            'server_name' => config('app.name', 'Thaiprompt'),
            'synced_version' => $reading->updated_at ? intval($reading->updated_at->timestamp * 1000) : 0,
            'created_at' => $reading->created_at?->toIso8601String(),
            'updated_at' => $reading->updated_at?->toIso8601String(),
            'notification' => null,
            // Debug-only fields
            '_debug' => [
                'real_id' => $reading->id,
                'conversation_status' => $reading->conversation_status,
                'bill_reference' => $reading->bill_reference,
                'bill_amount' => $reading->bill_amount,
                'fortune_type' => $reading->fortune_type,
                'paid_at' => $reading->paid_at?->toIso8601String(),
                'line_user_id' => $reading->line_user_id,
            ],
        ];
    }
}
