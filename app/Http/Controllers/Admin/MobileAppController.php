<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileBanner;
use App\Models\MobileDevice;
use App\Models\MobilePushNotification;
use App\Services\ExpoPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MobileAppController - จัดการแอพมือถือ
 *
 * Admin ควบคุมได้เฉพาะ 3 อย่างเท่านั้น:
 * 1. Push Notifications - ส่งข้อความถึงผู้ใช้
 * 2. Banner โฆษณา - จัดการแบนเนอร์ในหน้าช้อปปิ้ง
 * 3. Device Analytics - ดูสถิติเครื่องที่ลงทะเบียน
 *
 * หมายเหตุ: แอพเป็น Standalone - การตั้งค่าอื่นๆ ควบคุมในแอพโดยตรง
 */
class MobileAppController extends Controller
{
    /**
     * แสดงหน้า Dashboard รวมของ Mobile App
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // สถิติ Device
        $deviceStats = [
            'total' => MobileDevice::count(),
            'active' => MobileDevice::where('last_active_at', '>=', now()->subDays(7))->count(),
            'today' => MobileDevice::whereDate('created_at', today())->count(),
            'ios' => MobileDevice::where('platform', 'ios')->count(),
            'android' => MobileDevice::where('platform', 'android')->count(),
        ];

        // สถิติ Push Notification
        $pushStats = [
            'total_sent' => MobilePushNotification::count(),
            'this_month' => MobilePushNotification::whereMonth('created_at', now()->month)->count(),
            'success_rate' => $this->calculatePushSuccessRate(),
        ];

        // สถิติ Banner
        $bannerStats = [
            'active' => MobileBanner::where('is_active', true)->count(),
            'total_views' => MobileBanner::sum('view_count'),
            'total_clicks' => MobileBanner::sum('click_count'),
            'ctr' => $this->calculateBannerCTR(),
        ];

        return view('admin.mobile-app.index', compact(
            'deviceStats',
            'pushStats',
            'bannerStats'
        ));
    }

    /**
     * คำนวณ Push Success Rate
     */
    private function calculatePushSuccessRate(): float
    {
        $total = MobilePushNotification::count();
        if ($total === 0) {
            return 0;
        }

        $success = MobilePushNotification::where('status', 'sent')->count();

        return round(($success / $total) * 100, 1);
    }

    /**
     * คำนวณ Banner CTR (Click-Through Rate)
     */
    private function calculateBannerCTR(): float
    {
        $views = MobileBanner::sum('view_count');
        if ($views === 0) {
            return 0;
        }

        $clicks = MobileBanner::sum('click_count');

        return round(($clicks / $views) * 100, 2);
    }

    // =====================================================
    // Device Analytics
    // =====================================================

    /**
     * แสดงหน้า Device Analytics
     *
     * @return \Illuminate\View\View
     */
    public function analytics()
    {
        // สถิติรวม
        $totalDevices = MobileDevice::count();
        $activeDevices = MobileDevice::where('last_active_at', '>=', now()->subDays(7))->count();

        // แยกตาม Platform
        $byPlatform = MobileDevice::select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        // แยกตาม App Version
        $byAppVersion = MobileDevice::select('app_version', DB::raw('count(*) as count'))
            ->groupBy('app_version')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Daily Active Users (30 วันล่าสุด)
        $dailyActiveUsers = MobileDevice::select(
            DB::raw('DATE(last_active_at) as date'),
            DB::raw('count(*) as count')
        )
            ->where('last_active_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // เครื่องใหม่ต่อวัน (30 วันล่าสุด)
        $newDevicesPerDay = MobileDevice::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Retention Rate (7 วัน)
        $retentionRate = $this->calculateRetentionRate(7);

        // รายการเครื่องล่าสุด
        $recentDevices = MobileDevice::with('user')
            ->latest()
            ->take(20)
            ->get();

        return view('admin.mobile-app.analytics', compact(
            'totalDevices',
            'activeDevices',
            'byPlatform',
            'byAppVersion',
            'dailyActiveUsers',
            'newDevicesPerDay',
            'retentionRate',
            'recentDevices'
        ));
    }

    /**
     * คำนวณ Retention Rate
     *
     * @param  int  $days  จำนวนวัน
     */
    private function calculateRetentionRate(int $days): float
    {
        // เครื่องที่สมัครในช่วง $days วันที่แล้ว
        $startDate = now()->subDays($days * 2);
        $endDate = now()->subDays($days);

        $cohortDevices = MobileDevice::whereBetween('created_at', [$startDate, $endDate])->count();

        if ($cohortDevices === 0) {
            return 0;
        }

        // เครื่องที่ยังใช้งานอยู่
        $retainedDevices = MobileDevice::whereBetween('created_at', [$startDate, $endDate])
            ->where('last_active_at', '>=', now()->subDays($days))
            ->count();

        return round(($retainedDevices / $cohortDevices) * 100, 1);
    }

    /**
     * Export Device Analytics เป็น CSV
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportAnalytics()
    {
        $filename = 'mobile_devices_'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'Device ID',
                'Platform',
                'Device Model',
                'Device Brand',
                'OS Version',
                'App Version',
                'User ID',
                'User Name',
                'Locale',
                'Timezone',
                'Last Active',
                'Registered At',
            ]);

            // Data rows
            MobileDevice::with('user')->chunk(500, function ($devices) use ($file) {
                foreach ($devices as $device) {
                    fputcsv($file, [
                        $device->device_id,
                        $device->platform,
                        $device->device_model,
                        $device->device_brand,
                        $device->os_version,
                        $device->app_version,
                        $device->user_id,
                        $device->user?->name ?? '-',
                        $device->locale,
                        $device->timezone,
                        $device->last_active_at?->format('Y-m-d H:i:s'),
                        $device->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =====================================================
    // Push Notifications
    // =====================================================

    /**
     * แสดงรายการ Push Notifications
     *
     * @return \Illuminate\View\View
     */
    public function pushIndex()
    {
        $notifications = MobilePushNotification::latest()
            ->paginate(20);

        $stats = [
            'total' => MobilePushNotification::count(),
            'sent' => MobilePushNotification::where('status', 'sent')->count(),
            'failed' => MobilePushNotification::where('status', 'failed')->count(),
            'pending' => MobilePushNotification::where('status', 'pending')->count(),
        ];

        return view('admin.mobile-app.push.index', compact('notifications', 'stats'));
    }

    /**
     * แสดงฟอร์มสร้าง Push Notification
     *
     * @return \Illuminate\View\View
     */
    public function pushCreate()
    {
        // นับเฉพาะ devices ที่ active และมี push_token (ตรงกับเงื่อนไขที่ใช้ส่งจริง)
        $totalDevices = MobileDevice::where('is_active', true)
            ->whereNotNull('push_token')
            ->count();
        $iosDevices = MobileDevice::where('platform', 'ios')
            ->where('is_active', true)
            ->whereNotNull('push_token')
            ->count();
        $androidDevices = MobileDevice::where('platform', 'android')
            ->where('is_active', true)
            ->whereNotNull('push_token')
            ->count();

        return view('admin.mobile-app.push.create', compact(
            'totalDevices',
            'iosDevices',
            'androidDevices'
        ));
    }

    /**
     * บันทึกและส่ง Push Notification
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pushStore(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'url', 'max:500'],
            'action_url' => ['nullable', 'string', 'max:500'],
            'target' => ['required', 'in:all,ios,android'],
            'schedule_at' => ['nullable', 'date', 'after:now'],
        ]);

        $notification = MobilePushNotification::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'image' => $validated['image'] ?? null,
            'data' => [
                'action_url' => $validated['action_url'] ?? null,
            ],
            'target_platform' => $validated['target'],
            'scheduled_at' => $validated['schedule_at'] ?? null,
            'status' => $validated['schedule_at'] ? 'scheduled' : 'pending',
            'created_by' => auth()->id(),
        ]);

        // ถ้าไม่ได้ตั้งเวลา ให้ส่งทันที
        if (! $validated['schedule_at']) {
            // ส่ง Push Notification ผ่าน Expo API
            $pushService = new ExpoPushService;

            // กำหนด data สำหรับ notification
            $data = [
                'type' => 'general',
                'notification_id' => $notification->id,
                'url' => $validated['action_url'] ?? null,
            ];

            // ส่งตาม platform ที่เลือก
            if ($validated['target'] === 'all') {
                $results = $pushService->broadcast(
                    $validated['title'],
                    $validated['body'],
                    $data
                );
            } else {
                $results = $pushService->sendToPlatform(
                    $validated['target'],
                    $validated['title'],
                    $validated['body'],
                    $data
                );
            }

            // อัพเดทสถานะ
            $totalAttempted = $results['success'] + $results['failed'];
            $notification->update([
                'status' => $totalAttempted === 0 ? 'failed' : ($results['failed'] === 0 ? 'sent' : ($results['success'] > 0 ? 'sent' : 'failed')),
                'sent_at' => now(),
                'success_count' => $results['success'],
                'failure_count' => $results['failed'],
            ]);

            // สร้าง message ที่ชัดเจน
            if ($totalAttempted === 0) {
                // ไม่มี devices ที่สามารถส่งได้
                return redirect()
                    ->route('admin.mobile-app.push.index')
                    ->with('warning', 'ไม่พบอุปกรณ์ที่ลงทะเบียน Push Token - กรุณาตรวจสอบว่ามี devices ที่ active และมี push token');
            }

            $message = "ส่ง Push Notification สำเร็จ {$results['success']} เครื่อง";
            if ($results['failed'] > 0) {
                $message .= " (ล้มเหลว {$results['failed']} เครื่อง)";
            }
        } else {
            $message = 'ตั้งเวลาส่ง Push Notification สำเร็จ';
        }

        return redirect()
            ->route('admin.mobile-app.push.index')
            ->with('success', $message);
    }

    /**
     * แสดงรายละเอียด Push Notification
     *
     * @return \Illuminate\View\View
     */
    public function pushShow(MobilePushNotification $push)
    {
        return view('admin.mobile-app.push.show', compact('push'));
    }

    // =====================================================
    // Banners
    // =====================================================

    /**
     * แสดงรายการ Banners
     *
     * @return \Illuminate\View\View
     */
    public function bannersIndex()
    {
        $banners = MobileBanner::orderBy('sort_order')
            ->paginate(20);

        $stats = [
            'active' => MobileBanner::where('is_active', true)->count(),
            'inactive' => MobileBanner::where('is_active', false)->count(),
            'total_views' => MobileBanner::sum('view_count'),
            'total_clicks' => MobileBanner::sum('click_count'),
        ];

        return view('admin.mobile-app.banners.index', compact('banners', 'stats'));
    }

    /**
     * แสดงฟอร์มสร้าง Banner
     *
     * @return \Illuminate\View\View
     */
    public function bannersCreate()
    {
        return view('admin.mobile-app.banners.create');
    }

    /**
     * บันทึก Banner ใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bannersStore(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'image' => ['required_without:cropped_image', 'nullable', 'image', 'max:5120'],
            'cropped_image' => ['nullable', 'string'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'link_type' => ['nullable', 'in:internal,external,product,category'],
            'link_target' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'in:home,shop'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // ใช้รูปที่ครอปแล้ว (base64) หรือรูปต้นฉบับ
        if (! empty($request->cropped_image)) {
            // แปลง base64 เป็นไฟล์และบันทึก
            $validated['image'] = $this->saveCroppedImage($request->cropped_image, 'banners');
        } elseif ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image'] = '/storage/'.$path;
        }

        // ลบ cropped_image ออกจาก validated data
        unset($validated['cropped_image']);

        // แปลง link_url เป็น link สำหรับ model
        $validated['link'] = $validated['link_url'] ?? null;
        unset($validated['link_url']);

        // กำหนด link_type default เป็น external ถ้าไม่ได้ระบุ
        $validated['link_type'] = $validated['link_type'] ?? 'external';

        // Get next sort order
        $validated['sort_order'] = MobileBanner::max('sort_order') + 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        MobileBanner::create($validated);

        return redirect()
            ->route('admin.mobile-app.banners.index')
            ->with('success', 'สร้าง Banner สำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไข Banner
     *
     * @return \Illuminate\View\View
     */
    public function bannersEdit(MobileBanner $banner)
    {
        return view('admin.mobile-app.banners.edit', compact('banner'));
    }

    /**
     * อัปเดท Banner
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bannersUpdate(Request $request, MobileBanner $banner)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'max:5120'],
            'cropped_image' => ['nullable', 'string'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'link_type' => ['nullable', 'in:internal,external,product,category'],
            'link_target' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'in:home,shop'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // ใช้รูปที่ครอปแล้ว (base64) หรือรูปต้นฉบับ
        if (! empty($request->cropped_image)) {
            // แปลง base64 เป็นไฟล์และบันทึก
            $validated['image'] = $this->saveCroppedImage($request->cropped_image, 'banners');
        } elseif ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $validated['image'] = '/storage/'.$path;
        }

        // ลบ cropped_image ออกจาก validated data
        unset($validated['cropped_image']);

        // แปลง link_url เป็น link สำหรับ model
        $validated['link'] = $validated['link_url'] ?? null;
        unset($validated['link_url']);

        // กำหนด link_type default เป็น external ถ้าไม่ได้ระบุ
        $validated['link_type'] = $validated['link_type'] ?? ($banner->link_type ?? 'external');

        $validated['is_active'] = $validated['is_active'] ?? false;

        $banner->update($validated);

        return redirect()
            ->route('admin.mobile-app.banners.index')
            ->with('success', 'อัปเดท Banner สำเร็จ');
    }

    /**
     * ลบ Banner
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bannersDestroy(MobileBanner $banner)
    {
        $banner->delete();

        return redirect()
            ->route('admin.mobile-app.banners.index')
            ->with('success', 'ลบ Banner สำเร็จ');
    }

    /**
     * Toggle สถานะ Banner
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bannersToggle(MobileBanner $banner)
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $banner->is_active,
            'message' => $banner->is_active ? 'เปิดใช้งาน Banner แล้ว' : 'ปิดใช้งาน Banner แล้ว',
        ]);
    }

    /**
     * อัปเดทลำดับ Banners
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bannersReorder(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:mobile_banners,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $item) {
            MobileBanner::where('id', $item['id'])->update(['sort_order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดทลำดับสำเร็จ',
        ]);
    }

    /**
     * บันทึกรูปที่ครอปแล้ว (base64) เป็นไฟล์
     *
     * @param  string  $base64Image  รูปในรูปแบบ base64
     * @param  string  $folder  โฟลเดอร์สำหรับเก็บรูป
     * @return string path ของรูปที่บันทึก
     */
    private function saveCroppedImage(string $base64Image, string $folder = 'banners'): string
    {
        // แยก data:image/jpeg;base64, ออกจาก base64 string
        $imageData = $base64Image;
        if (str_contains($base64Image, ',')) {
            [, $imageData] = explode(',', $base64Image, 2);
        }

        // ถอดรหัส base64
        $decodedImage = base64_decode($imageData);

        // สร้างชื่อไฟล์
        $filename = $folder.'/'.uniqid().'_'.time().'.jpg';

        // บันทึกไฟล์
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decodedImage);

        return '/storage/'.$filename;
    }
}
