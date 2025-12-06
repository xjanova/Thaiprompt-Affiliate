<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\RiderLocation;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RiderController - จัดการไรเดอร์ในระบบ Admin
 *
 * รองรับการ:
 * - ดูรายการไรเดอร์ทั้งหมด พร้อมสถิติ
 * - ดูไรเดอร์ที่รอตรวจสอบ
 * - อนุมัติ/ปฏิเสธไรเดอร์
 * - ดูรายละเอียดไรเดอร์และประวัติงาน
 * - ดูตำแหน่ง GPS แบบ realtime
 * - ระงับ/เปิดใช้งานไรเดอร์
 */
class RiderController extends Controller
{
    /**
     * แสดงรายการไรเดอร์ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Rider::with(['user', 'jobs']);

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id_card_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามสถานะพร้อมรับงาน
        if ($request->filled('availability')) {
            $query->where('availability', $request->availability);
        }

        // กรองตามประเภทรถ
        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        // สถิติรวม
        $stats = [
            'total' => Rider::count(),
            'pending' => Rider::where('status', 'pending')->count(),
            'approved' => Rider::where('status', 'approved')->count(),
            'rejected' => Rider::where('status', 'rejected')->count(),
            'online' => Rider::where('availability', 'online')->count(),
            'busy' => Rider::where('availability', 'busy')->count(),
        ];

        // ดึงข้อมูลพร้อม pagination
        $riders = $query->latest()->paginate(15);

        return view('admin.riders.index', [
            'riders' => $riders,
            'stats' => $stats,
            'pageTitle' => 'จัดการไรเดอร์',
        ]);
    }

    /**
     * แสดงรายการไรเดอร์ที่รอตรวจสอบ
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function pending(Request $request)
    {
        $query = Rider::with(['user'])
            ->where('status', 'pending');

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $riders = $query->oldest()->paginate(15);

        return view('admin.riders.pending', [
            'riders' => $riders,
            'pageTitle' => 'ไรเดอร์รอตรวจสอบ',
            'pendingCount' => Rider::where('status', 'pending')->count(),
        ]);
    }

    /**
     * แสดงรายละเอียดไรเดอร์
     *
     * @param Rider $rider
     * @return \Illuminate\View\View
     */
    public function show(Rider $rider)
    {
        $rider->load(['user', 'jobs' => function ($q) {
            $q->latest()->limit(20);
        }]);

        // สถิติงาน
        $jobStats = [
            'total' => $rider->jobs()->count(),
            'completed' => $rider->jobs()->where('status', 'completed')->count(),
            'cancelled' => $rider->jobs()->where('status', 'cancelled')->count(),
            'earnings' => $rider->jobs()->where('status', 'completed')->sum('rider_earnings'),
        ];

        // งานล่าสุด
        $recentJobs = $rider->jobs()
            ->with(['customer'])
            ->latest()
            ->limit(10)
            ->get();

        // บริการที่ให้บริการ (ถ้ามี)
        $services = [];
        if ($rider->services) {
            $serviceIds = is_array($rider->services) ? $rider->services : json_decode($rider->services, true);
            if ($serviceIds) {
                $services = ServiceCategory::whereIn('id', $serviceIds)->get();
            }
        }

        return view('admin.riders.show', [
            'rider' => $rider,
            'jobStats' => $jobStats,
            'recentJobs' => $recentJobs,
            'services' => $services,
            'pageTitle' => 'รายละเอียดไรเดอร์: ' . $rider->full_name,
        ]);
    }

    /**
     * อนุมัติไรเดอร์
     *
     * @param Request $request
     * @param Rider $rider
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, Rider $rider)
    {
        DB::beginTransaction();
        try {
            $rider->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejection_reason' => null,
            ]);

            // TODO: ส่ง notification ให้ไรเดอร์

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'อนุมัติไรเดอร์เรียบร้อย',
                ]);
            }

            return redirect()->back()->with('success', 'อนุมัติไรเดอร์เรียบร้อย');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ปฏิเสธไรเดอร์
     *
     * @param Request $request
     * @param Rider $rider
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, Rider $rider)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $rider->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
            ]);

            // TODO: ส่ง notification ให้ไรเดอร์

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ปฏิเสธไรเดอร์เรียบร้อย',
                ]);
            }

            return redirect()->back()->with('success', 'ปฏิเสธไรเดอร์เรียบร้อย');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ระงับไรเดอร์
     *
     * @param Request $request
     * @param Rider $rider
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function suspend(Request $request, Rider $rider)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $rider->update([
                'status' => 'suspended',
                'suspension_reason' => $request->reason,
                'suspended_at' => now(),
                'suspended_by' => auth()->id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ระงับไรเดอร์เรียบร้อย',
                ]);
            }

            return redirect()->back()->with('success', 'ระงับไรเดอร์เรียบร้อย');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * สลับสถานะ Active
     *
     * @param Request $request
     * @param Rider $rider
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive(Request $request, Rider $rider)
    {
        try {
            if ($rider->status === 'suspended') {
                $rider->update([
                    'status' => 'approved',
                    'suspension_reason' => null,
                ]);
                $message = 'เปิดใช้งานไรเดอร์เรียบร้อย';
            } else {
                $rider->update([
                    'status' => 'suspended',
                    'suspension_reason' => 'ถูกระงับโดยแอดมิน',
                ]);
                $message = 'ระงับไรเดอร์เรียบร้อย';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $rider->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * แสดงตำแหน่ง GPS ของไรเดอร์
     *
     * @param Rider $rider
     * @return \Illuminate\View\View
     */
    public function locations(Rider $rider)
    {
        // ตำแหน่งล่าสุด
        $latestLocation = RiderLocation::where('rider_id', $rider->id)
            ->latest()
            ->first();

        // ประวัติตำแหน่ง 24 ชม.
        $locationHistory = RiderLocation::where('rider_id', $rider->id)
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('admin.riders.locations', [
            'rider' => $rider,
            'latestLocation' => $latestLocation,
            'locationHistory' => $locationHistory,
            'pageTitle' => 'ตำแหน่ง GPS: ' . $rider->full_name,
        ]);
    }

    /**
     * API: ดึงตำแหน่ง GPS ล่าสุด
     *
     * @param Rider $rider
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestLocation(Rider $rider)
    {
        $location = RiderLocation::where('rider_id', $rider->id)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => $location ? [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'accuracy' => $location->accuracy,
                'speed' => $location->speed,
                'heading' => $location->heading,
                'activity' => $location->activity_type,
                'battery' => $location->battery_level,
                'updated_at' => $location->created_at->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * แสดงไรเดอร์ทั้งหมดบนแผนที่
     *
     * @return \Illuminate\View\View
     */
    public function map()
    {
        // ไรเดอร์ที่ออนไลน์
        $onlineRiders = Rider::where('availability', 'online')
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get(['id', 'full_name', 'phone', 'vehicle_type', 'current_latitude', 'current_longitude']);

        // ไรเดอร์ที่กำลังส่งงาน
        $busyRiders = Rider::where('availability', 'busy')
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get(['id', 'full_name', 'phone', 'vehicle_type', 'current_latitude', 'current_longitude']);

        return view('admin.riders.map', [
            'onlineRiders' => $onlineRiders,
            'busyRiders' => $busyRiders,
            'pageTitle' => 'แผนที่ไรเดอร์',
        ]);
    }

    /**
     * API: ดึงข้อมูล GPS ของไรเดอร์ทั้งหมดสำหรับ GPS Monitoring
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGpsData(Request $request)
    {
        $showOnline = $request->boolean('show_online', true);
        $showBusy = $request->boolean('show_busy', true);
        $showOffline = $request->boolean('show_offline', false);
        $limit = $request->input('limit', 100);
        $vehicleType = $request->input('vehicle_type');

        $query = Rider::with(['jobs' => function ($q) {
            $q->whereIn('status', ['accepted', 'picked_up', 'in_transit'])
                ->latest()
                ->limit(1);
        }])
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude');

        // กรองตามสถานะ
        $availabilities = [];
        if ($showOnline) $availabilities[] = 'online';
        if ($showBusy) $availabilities[] = 'busy';
        if ($showOffline) $availabilities[] = 'offline';

        if (!empty($availabilities)) {
            $query->whereIn('availability', $availabilities);
        }

        // กรองตามประเภทรถ
        if ($vehicleType) {
            $query->where('vehicle_type', $vehicleType);
        }

        if ($limit !== 'all') {
            $query->limit((int) $limit);
        }

        $riders = $query->get();

        // แปลงข้อมูลสำหรับแผนที่
        $riderData = $riders->map(function ($rider) {
            $currentJob = $rider->jobs->first();
            return [
                'id' => $rider->id,
                'name' => $rider->full_name,
                'phone' => $rider->phone,
                'vehicle_type' => $rider->vehicle_type,
                'vehicle_plate' => $rider->vehicle_plate ?? null,
                'latitude' => (float) $rider->current_latitude,
                'longitude' => (float) $rider->current_longitude,
                'availability' => $rider->availability,
                'last_location_update' => $rider->location_updated_at,
                'current_job' => $currentJob ? [
                    'id' => $currentJob->id,
                    'status' => $currentJob->status,
                    'pickup_address' => $currentJob->pickup_address,
                    'dropoff_address' => $currentJob->dropoff_address,
                ] : null,
            ];
        });

        // สถิติ
        $stats = [
            'total' => $riders->count(),
            'online' => $riders->where('availability', 'online')->count(),
            'busy' => $riders->where('availability', 'busy')->count(),
            'offline' => $riders->where('availability', 'offline')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'riders' => $riderData,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * แสดงประวัติตำแหน่ง GPS ของไรเดอร์ (Playback)
     *
     * @param Rider $rider
     * @return \Illuminate\View\View
     */
    public function locationPlayback(Rider $rider)
    {
        // ประวัติตำแหน่ง 24 ชม.
        $logs = RiderLocation::where('rider_id', $rider->id)
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.riders.playback', [
            'rider' => $rider,
            'logs' => $logs,
            'pageTitle' => 'เล่นย้อนหลัง GPS: ' . $rider->full_name,
        ]);
    }

    /**
     * API: ดึงประวัติตำแหน่ง GPS ของไรเดอร์
     *
     * @param Request $request
     * @param Rider $rider
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationHistory(Request $request, Rider $rider)
    {
        $hours = $request->input('hours', 24);
        $limit = $request->input('limit', 500);

        $logs = RiderLocation::where('rider_id', $rider->id)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        // คำนวณระยะทาง
        $totalDistance = 0;
        $prevLog = null;
        foreach ($logs as $log) {
            if ($prevLog) {
                $totalDistance += $this->calculateDistance(
                    $prevLog->latitude,
                    $prevLog->longitude,
                    $log->latitude,
                    $log->longitude
                );
            }
            $prevLog = $log;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rider' => [
                    'id' => $rider->id,
                    'name' => $rider->full_name,
                ],
                'path' => $logs->map(function ($log) {
                    return [
                        'lat' => (float) $log->latitude,
                        'lng' => (float) $log->longitude,
                        'timestamp' => $log->created_at->toIso8601String(),
                        'speed' => $log->speed,
                        'heading' => $log->heading,
                        'accuracy' => $log->accuracy,
                        'battery' => $log->battery_level,
                    ];
                }),
                'total_points' => $logs->count(),
                'total_distance' => round($totalDistance, 2),
            ],
        ]);
    }

    /**
     * คำนวณระยะทาง (Haversine formula)
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
