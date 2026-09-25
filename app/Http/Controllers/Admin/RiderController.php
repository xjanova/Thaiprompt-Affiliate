<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RiderJobException;
use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\RiderLocation;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Services\DeliveryFeeCalculator;
use App\Services\RiderAccountService;
use App\Services\RiderJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * หลังบ้านไรเดอร์ (admin.riders.*) — route อยู่ใต้ middleware auth + role:admin,super_admin
 *
 * - รายชื่อ / รอตรวจ / รายละเอียด (พร้อมลิงก์เอกสารผ่าน route แอดมินเท่านั้น — ไฟล์อยู่ private disk)
 * - อนุมัติ (ต้องมีเอกสารครบ) / ปฏิเสธ (บันทึก rejected_at/by) / ระงับ (บังคับออฟไลน์ + จัดการงานค้าง)
 * - แผนที่สด + GPS monitor (คอลัมน์ last_latitude/last_longitude/last_location_update)
 * - JSON สำหรับจอติดตามการกระจายงาน (งานรอไรเดอร์ + ไรเดอร์ออนไลน์)
 * - ตั้งค่าระบบไรเดอร์ (Setting rider.* — ตรวจช่วงค่าก่อนบันทึก)
 */
class RiderController extends Controller
{
    /**
     * ค่าตั้งค่าระบบไรเดอร์ที่แอดมินแก้ได้: ชื่อฟิลด์ฟอร์ม => [key, ชนิด, ต่ำสุด, สูงสุด, ชื่อไทย, หน่วย]
     *
     * ชื่อฟิลด์ในฟอร์ม = settings[<ชื่อฟิลด์>] (ไม่มีจุด — จุดในชื่อ input ใช้กับ Laravel ไม่ได้)
     *
     * @var array<string, array{key: string, type: string, min?: float, max?: float, options?: array<int, string>, label: string, unit?: string}>
     */
    public const SETTINGS_SPEC = [
        'dispatch_mode' => ['key' => 'rider.dispatch_mode', 'type' => 'string', 'options' => ['broadcast', 'cascade'], 'label' => 'โหมดกระจายงาน'],
        'require_deposit' => ['key' => 'rider.require_deposit', 'type' => 'boolean', 'label' => 'บังคับวางเงินประกันก่อนรับงาน'],
        'base_fee' => ['key' => 'rider.base_fee', 'type' => 'float', 'min' => 0, 'max' => 1000, 'label' => 'ค่าส่งเริ่มต้น', 'unit' => 'บาท'],
        'per_km_fee' => ['key' => 'rider.per_km_fee', 'type' => 'float', 'min' => 0, 'max' => 200, 'label' => 'ค่าส่งต่อกิโลเมตร', 'unit' => 'บาท/กม.'],
        'free_km' => ['key' => 'rider.free_km', 'type' => 'float', 'min' => 0, 'max' => 50, 'label' => 'ระยะที่รวมในค่าส่งเริ่มต้น', 'unit' => 'กม.'],
        'min_fee' => ['key' => 'rider.min_fee', 'type' => 'float', 'min' => 0, 'max' => 1000, 'label' => 'ค่าส่งขั้นต่ำ', 'unit' => 'บาท'],
        'max_distance_km' => ['key' => 'rider.max_distance_km', 'type' => 'float', 'min' => 1, 'max' => 100, 'label' => 'ระยะส่งสูงสุด', 'unit' => 'กม.'],
        'road_factor' => ['key' => 'rider.road_factor', 'type' => 'float', 'min' => 1, 'max' => 3, 'label' => 'ตัวคูณระยะถนนจริง', 'unit' => 'เท่า'],
        'rider_share_percent' => ['key' => 'rider.rider_share_percent', 'type' => 'float', 'min' => 0, 'max' => 100, 'label' => 'ส่วนแบ่งไรเดอร์จากค่าส่ง', 'unit' => '%'],
        'offer_radius_km' => ['key' => 'rider.offer_radius_km', 'type' => 'float', 'min' => 0.5, 'max' => 50, 'label' => 'รัศมีกระจายงานรอบแรก', 'unit' => 'กม.'],
        'max_offer_radius_km' => ['key' => 'rider.max_offer_radius_km', 'type' => 'float', 'min' => 0.5, 'max' => 100, 'label' => 'รัศมีกระจายงานสูงสุด', 'unit' => 'กม.'],
        'max_dispatch_rounds' => ['key' => 'rider.max_dispatch_rounds', 'type' => 'integer', 'min' => 1, 'max' => 20, 'label' => 'จำนวนรอบกระจายงานสูงสุด', 'unit' => 'รอบ'],
        'rebroadcast_interval_minutes' => ['key' => 'rider.rebroadcast_interval_minutes', 'type' => 'integer', 'min' => 1, 'max' => 60, 'label' => 'กระจายงานซ้ำทุก', 'unit' => 'นาที'],
        'offer_timeout_seconds' => ['key' => 'rider.offer_timeout_seconds', 'type' => 'integer', 'min' => 30, 'max' => 600, 'label' => 'เวลาให้ไรเดอร์ตัดสินใจ (โหมดทีละคน)', 'unit' => 'วินาที'],
        'max_cod_amount' => ['key' => 'rider.max_cod_amount', 'type' => 'float', 'min' => 0, 'max' => 100000, 'label' => 'ยอดเก็บเงินปลายทางสูงสุดต่องาน', 'unit' => 'บาท'],
        'pending_timeout_minutes' => ['key' => 'rider.pending_timeout_minutes', 'type' => 'integer', 'min' => 1, 'max' => 240, 'label' => 'ส่งต่อแอดมินเมื่อไม่มีคนรับเกิน', 'unit' => 'นาที'],
        'location_fresh_minutes' => ['key' => 'rider.location_fresh_minutes', 'type' => 'integer', 'min' => 1, 'max' => 120, 'label' => 'พิกัดไรเดอร์ถือว่าสดภายใน', 'unit' => 'นาที'],
        'location_retention_days' => ['key' => 'rider.location_retention_days', 'type' => 'integer', 'min' => 1, 'max' => 365, 'label' => 'เก็บประวัติตำแหน่ง', 'unit' => 'วัน'],
        'tracking_expiry_hours' => ['key' => 'rider.tracking_expiry_hours', 'type' => 'integer', 'min' => 1, 'max' => 72, 'label' => 'อายุลิงก์ติดตามระหว่างงาน', 'unit' => 'ชั่วโมง'],
        'tracking_grace_minutes' => ['key' => 'rider.tracking_grace_minutes', 'type' => 'integer', 'min' => 1, 'max' => 180, 'label' => 'ลิงก์ติดตามหลังงานจบ', 'unit' => 'นาที'],
        'avg_speed_kmh' => ['key' => 'rider.avg_speed_kmh', 'type' => 'float', 'min' => 5, 'max' => 120, 'label' => 'ความเร็วเฉลี่ยสำหรับประมาณเวลา', 'unit' => 'กม./ชม.'],
        'max_release_count' => ['key' => 'rider.max_release_count', 'type' => 'integer', 'min' => 1, 'max' => 20, 'label' => 'คืนงานได้กี่ครั้งก่อนส่งแอดมิน', 'unit' => 'ครั้ง'],
    ];

    public function __construct(
        private readonly RiderAccountService $accounts,
        private readonly RiderJobService $jobs,
    ) {}

    // =====================================================
    // รายการ / รายละเอียด
    // =====================================================

    /**
     * รายการไรเดอร์ทั้งหมด (admin.riders.index)
     */
    public function index(Request $request)
    {
        $query = Rider::with(['user']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id_card_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_plate', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('availability')) {
            $query->where('availability', $request->availability);
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        if ($request->boolean('needs_review')) {
            $query->whereNotNull('documents_changed_at');
        }

        $stats = [
            'total' => Rider::count(),
            'pending' => Rider::where('status', 'pending')->count(),
            'approved' => Rider::where('status', 'approved')->count(),
            'rejected' => Rider::where('status', 'rejected')->count(),
            'suspended' => Rider::where('status', 'suspended')->count(),
            'online' => Rider::where('availability', 'online')->count(),
            'busy' => Rider::where('availability', 'busy')->count(),
            'needs_review' => Rider::whereNotNull('documents_changed_at')->count(),
        ];

        $riders = $query->latest()->paginate(15)->withQueryString();

        return view('admin.riders.index', [
            'riders' => $riders,
            'stats' => $stats,
            'pageTitle' => 'จัดการไรเดอร์',
        ]);
    }

    /**
     * ไรเดอร์ที่รอตรวจ (admin.riders.pending)
     */
    public function pending(Request $request)
    {
        $query = Rider::with(['user'])->where('status', 'pending');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $riders = $query->oldest()->paginate(15)->withQueryString();

        // เอกสารของแต่ละคน (ลิงก์ผ่าน route แอดมินเท่านั้น) + ที่ยังขาด
        $documentsByRider = [];
        foreach ($riders as $rider) {
            $documentsByRider[$rider->id] = [
                'documents' => $this->accounts->documentList($rider, fn (Rider $r, string $t) => route('admin.riders.document', [$r, $t])),
                'missing' => $this->accounts->missingDocuments($rider),
                'complete' => $this->accounts->documentsComplete($rider),
            ];
        }

        return view('admin.riders.pending', [
            'riders' => $riders,
            'documentsByRider' => $documentsByRider,
            'pageTitle' => 'ไรเดอร์รอตรวจสอบ',
            'pendingCount' => Rider::where('status', 'pending')->count(),
        ]);
    }

    /**
     * รายละเอียดไรเดอร์ (admin.riders.show)
     */
    public function show(Rider $rider)
    {
        $rider->load(['user']);

        $jobStats = [
            'total' => $rider->jobs()->count(),
            'completed' => $rider->jobs()->where('status', 'completed')->count(),
            'cancelled' => $rider->jobs()->where('status', 'cancelled')->count(),
            'failed' => $rider->jobs()->where('status', 'failed')->count(),
            'earnings' => round((float) $rider->jobs()->where('status', 'completed')->sum('rider_earnings'), 2),
        ];

        $recentJobs = $rider->jobs()
            ->with(['customer'])
            ->latest('id')
            ->limit(10)
            ->get();

        $services = collect();
        $serviceIds = $rider->service_categories;
        if (is_array($serviceIds) && $serviceIds !== []) {
            $services = ServiceCategory::whereIn('id', $serviceIds)->get();
        }

        $missing = $this->accounts->missingDocuments($rider);

        return view('admin.riders.show', [
            'rider' => $rider,
            'jobStats' => $jobStats,
            'recentJobs' => $recentJobs,
            'services' => $services,
            'documents' => $this->accounts->documentList($rider, fn (Rider $r, string $t) => route('admin.riders.document', [$r, $t])),
            'missingDocuments' => $missing,
            'missingDocumentLabels' => $this->accounts->documentLabels($missing),
            'canApprove' => in_array($rider->status, ['pending', 'rejected', 'inactive'], true) && $missing === [],
            'documentsChangedAt' => $this->accounts->documentsChangedAt($rider),
            'activeJob' => $rider->activeJob(),
            'walletBalance' => $rider->walletBalance(),
            'lastLocation' => $this->locationPayload($rider),
            'riderData' => $this->accounts->statusPayload($rider, fn (Rider $r, string $t) => route('admin.riders.document', [$r, $t])),
            'pageTitle' => 'รายละเอียดไรเดอร์: '.$rider->full_name,
        ]);
    }

    /**
     * เปิดไฟล์เอกสารไรเดอร์จาก private disk (admin.riders.document) — แอดมินเท่านั้น
     */
    public function document(Rider $rider, string $type): StreamedResponse
    {
        if (! array_key_exists($type, RiderAccountService::DOCUMENT_TYPES)) {
            abort(404);
        }

        Log::info('Admin: view rider document', ['admin_id' => Auth::id(), 'rider_id' => $rider->id, 'type' => $type]);

        return $this->accounts->documentResponse($rider, $type);
    }

    // =====================================================
    // อนุมัติ / ปฏิเสธ / ระงับ
    // =====================================================

    /**
     * อนุมัติไรเดอร์ (admin.riders.approve) — ต้องมีเอกสารที่บังคับครบ
     */
    public function approve(Request $request, Rider $rider): JsonResponse|RedirectResponse
    {
        if ($rider->status === 'approved') {
            // อนุมัติซ้ำ = ถือว่าตรวจเอกสารที่เปลี่ยนแล้ว
            $rider->forceFill(['documents_changed_at' => null])->save();

            return $this->respond($request, true, 'ไรเดอร์คนนี้อนุมัติอยู่แล้ว');
        }

        if ($rider->status === 'suspended') {
            return $this->respond($request, false, 'ไรเดอร์ถูกระงับอยู่ กรุณาใช้ปุ่ม "ยกเลิกการระงับ"', 409, 'RIDER_SUSPENDED');
        }

        $missing = $this->accounts->missingDocuments($rider);
        if ($missing !== []) {
            return $this->respond(
                $request,
                false,
                'อนุมัติไม่ได้ ยังขาดเอกสาร: '.$this->accounts->documentLabels($missing),
                422,
                'DOCUMENTS_INCOMPLETE',
                ['missing' => $missing]
            );
        }

        try {
            DB::transaction(function () use ($rider) {
                $rider->approve(Auth::user());
                $rider->forceFill(['documents_changed_at' => null])->save();
            });
        } catch (\Throwable $e) {
            return $this->failure($request, 'approve', $e, $rider);
        }

        Log::info('Admin: rider approved', ['rider_id' => $rider->id, 'admin_id' => Auth::id()]);

        return $this->respond($request, true, 'อนุมัติไรเดอร์เรียบร้อย');
    }

    /**
     * ปฏิเสธใบสมัคร (admin.riders.reject) — reason บังคับ, บันทึก rejected_at/rejected_by, ไรเดอร์ส่งใหม่ได้
     */
    public function reject(Request $request, Rider $rider): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่ไม่อนุมัติ',
            'reason.min' => 'กรุณาระบุเหตุผลให้ชัดเจน',
            'reason.max' => 'เหตุผลยาวเกินไป (ไม่เกิน 500 ตัวอักษร)',
        ]);

        if ($validator->fails()) {
            return $this->respond($request, false, $validator->errors()->first(), 422, 'VALIDATION_ERROR');
        }

        if (! in_array($rider->status, ['pending', 'inactive'], true)) {
            return $this->respond(
                $request,
                false,
                $rider->status === 'approved'
                    ? 'ไรเดอร์คนนี้อนุมัติแล้ว หากต้องการหยุดใช้งานให้ใช้ปุ่ม "ระงับ"'
                    : 'ปฏิเสธได้เฉพาะใบสมัครที่รอตรวจ',
                409,
                'INVALID_STATUS'
            );
        }

        try {
            $rider->reject(Auth::user(), trim((string) $request->input('reason')));
        } catch (\Throwable $e) {
            return $this->failure($request, 'reject', $e, $rider);
        }

        Log::info('Admin: rider rejected', ['rider_id' => $rider->id, 'admin_id' => Auth::id()]);

        return $this->respond($request, true, 'ปฏิเสธใบสมัครเรียบร้อย ไรเดอร์แก้ไขแล้วส่งใหม่ได้');
    }

    /**
     * ระงับไรเดอร์ (admin.riders.suspend) — บังคับออฟไลน์ + จัดการงานที่ค้างอย่างปลอดภัย
     *
     * งานยังไม่รับของ → คืนเข้าคิวให้ไรเดอร์คนอื่น · รับของแล้ว → แจ้งแอดมินมอบหมายใหม่ (ของอยู่กับไรเดอร์)
     */
    public function suspend(Request $request, Rider $rider): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่ระงับ',
            'reason.min' => 'กรุณาระบุเหตุผลให้ชัดเจน',
            'reason.max' => 'เหตุผลยาวเกินไป (ไม่เกิน 500 ตัวอักษร)',
        ]);

        if ($validator->fails()) {
            return $this->respond($request, false, $validator->errors()->first(), 422, 'VALIDATION_ERROR');
        }

        return $this->suspendRider($request, $rider, trim((string) $request->input('reason')));
    }

    /**
     * สลับ ระงับ ↔ ใช้งาน (admin.riders.toggle-active)
     */
    public function toggleActive(Request $request, Rider $rider): JsonResponse|RedirectResponse
    {
        if ($rider->status === 'suspended' || $rider->suspended_at !== null) {
            try {
                $rider->unsuspend(Auth::user());
            } catch (\Throwable $e) {
                return $this->failure($request, 'unsuspend', $e, $rider);
            }

            Log::info('Admin: rider unsuspended', ['rider_id' => $rider->id, 'admin_id' => Auth::id()]);

            return $this->respond($request, true, 'ยกเลิกการระงับแล้ว ไรเดอร์กดเปิดรับงานเองได้', 200, null, ['status' => 'approved']);
        }

        if ($rider->status !== 'approved') {
            return $this->respond($request, false, 'ระงับได้เฉพาะไรเดอร์ที่อนุมัติแล้ว', 409, 'INVALID_STATUS');
        }

        $reason = trim((string) $request->input('reason', '')) ?: 'ระงับโดยแอดมิน';

        return $this->suspendRider($request, $rider, $reason);
    }

    /**
     * แอดมินตรวจเอกสารที่ไรเดอร์เปลี่ยนหลังอนุมัติแล้ว (admin.riders.documents-reviewed)
     */
    public function markDocumentsReviewed(Request $request, Rider $rider): JsonResponse|RedirectResponse
    {
        $missing = $this->accounts->missingDocuments($rider);
        if ($missing !== []) {
            return $this->respond(
                $request,
                false,
                'ยังขาดเอกสาร: '.$this->accounts->documentLabels($missing),
                422,
                'DOCUMENTS_INCOMPLETE',
                ['missing' => $missing]
            );
        }

        $rider->forceFill(['documents_changed_at' => null])->save();

        Log::info('Admin: rider documents reviewed', ['rider_id' => $rider->id, 'admin_id' => Auth::id()]);

        return $this->respond($request, true, 'บันทึกว่าตรวจเอกสารแล้ว');
    }

    // =====================================================
    // ตำแหน่ง / แผนที่ / จอติดตามการกระจายงาน
    // =====================================================

    /**
     * ตำแหน่ง GPS ของไรเดอร์ + ประวัติ 24 ชม. (admin.riders.locations)
     */
    public function locations(Rider $rider)
    {
        $latestLocation = RiderLocation::where('rider_id', $rider->id)
            ->orderByDesc('recorded_at')
            ->first();

        $locationHistory = RiderLocation::where('rider_id', $rider->id)
            ->where('recorded_at', '>=', now()->subDay())
            ->orderByDesc('recorded_at')
            ->limit(100)
            ->get();

        return view('admin.riders.locations', [
            'rider' => $rider,
            'latestLocation' => $latestLocation,
            'locationHistory' => $locationHistory,
            'lastLocation' => $this->locationPayload($rider),
            'pageTitle' => 'ตำแหน่ง GPS: '.$rider->full_name,
        ]);
    }

    /**
     * API: ตำแหน่งล่าสุดของไรเดอร์ (admin.riders.latest-location)
     *
     * ใช้พิกัดล่าสุดจากตาราง riders (อัปเดตทุกครั้งที่แอปส่ง) — rider_locations เก็บเฉพาะช่วงมีงาน
     */
    public function getLatestLocation(Rider $rider): JsonResponse
    {
        $location = $this->locationPayload($rider);

        $trail = RiderLocation::where('rider_id', $rider->id)
            ->orderByDesc('recorded_at')
            ->first(['accuracy', 'speed', 'heading', 'activity_type', 'battery_level']);

        return response()->json([
            'success' => true,
            'data' => $location ? array_merge($location, [
                'accuracy' => $trail?->accuracy !== null ? (float) $trail->accuracy : null,
                'speed' => $trail?->speed !== null ? (float) $trail->speed : null,
                'heading' => $trail?->heading !== null ? (float) $trail->heading : null,
                'activity' => $trail?->activity_type,
                'battery' => $trail?->battery_level !== null ? (int) $trail->battery_level : null,
            ]) : null,
        ]);
    }

    /**
     * แผนที่ไรเดอร์ออนไลน์/กำลังส่งงาน (admin.riders.map)
     */
    public function map()
    {
        $columns = ['id', 'full_name', 'phone', 'vehicle_type', 'vehicle_plate', 'availability', 'last_latitude', 'last_longitude', 'last_location_update'];

        $onlineRiders = Rider::where('availability', 'online')
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->get($columns);

        $busyRiders = Rider::where('availability', 'busy')
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->get($columns);

        return view('admin.riders.map', [
            'onlineRiders' => $onlineRiders,
            'busyRiders' => $busyRiders,
            'onlineRidersData' => $onlineRiders->map(fn (Rider $r) => $this->mapRiderPayload($r))->values()->all(),
            'busyRidersData' => $busyRiders->map(fn (Rider $r) => $this->mapRiderPayload($r))->values()->all(),
            'staleAfterSeconds' => $this->staleAfterSeconds(),
            'gpsDataUrl' => route('admin.riders.gps-data'),
            'dispatchMonitorUrl' => route('admin.riders.dispatch-monitor'),
            'pageTitle' => 'แผนที่ไรเดอร์',
        ]);
    }

    /**
     * API: ตำแหน่งไรเดอร์ทั้งหมดสำหรับแผนที่สด (admin.riders.gps-data)
     *
     * ?show_online=1&show_busy=1&show_offline=0&vehicle_type=&limit=100|all
     */
    public function getGpsData(Request $request): JsonResponse
    {
        $availabilities = array_keys(array_filter([
            'online' => $request->boolean('show_online', true),
            'busy' => $request->boolean('show_busy', true),
            'offline' => $request->boolean('show_offline', false),
        ]));

        $limit = $request->input('limit', 100);

        $query = Rider::query()
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->when($availabilities !== [], fn ($q) => $q->whereIn('availability', $availabilities))
            ->when($request->filled('vehicle_type'), fn ($q) => $q->where('vehicle_type', $request->input('vehicle_type')))
            ->orderByDesc('last_location_update');

        if ($limit !== 'all') {
            $query->limit(max(1, min(500, (int) $limit)));
        }

        $riders = $query->get();

        $activeJobs = RiderJob::query()
            ->whereIn('rider_id', $riders->pluck('id')->all())
            ->whereIn('status', RiderJob::ACTIVE_STATUSES)
            ->get(['id', 'rider_id', 'job_number', 'status', 'pickup_address', 'delivery_address', 'gps_active'])
            ->keyBy('rider_id');

        $riderData = $riders->map(function (Rider $rider) use ($activeJobs) {
            $job = $activeJobs->get($rider->id);

            return array_merge($this->mapRiderPayload($rider), [
                'current_job' => $job ? [
                    'id' => (int) $job->id,
                    'job_number' => (string) $job->job_number,
                    'status' => (string) $job->status,
                    'status_text' => $job->status_text,
                    'pickup_address' => $job->pickup_address,
                    'delivery_address' => $job->delivery_address,
                    // คีย์เดิมที่หน้าแผนที่เก่าอ่าน
                    'dropoff_address' => $job->delivery_address,
                    'gps_active' => (bool) $job->gps_active,
                    'url' => route('admin.rider-jobs.show', $job->id),
                ] : null,
            ]);
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'riders' => $riderData,
                'stats' => [
                    'total' => $riders->count(),
                    'online' => $riders->where('availability', 'online')->count(),
                    'busy' => $riders->where('availability', 'busy')->count(),
                    'offline' => $riders->where('availability', 'offline')->count(),
                    'stale' => $riderData->where('is_stale', true)->count(),
                ],
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * JSON จอติดตามการกระจายงานสด (admin.riders.dispatch-monitor)
     *
     * งานที่รอไรเดอร์ (รวมที่ต้องจัดเอง manual_needed) + งานที่กำลังวิ่ง + ไรเดอร์ออนไลน์/กำลังส่ง
     */
    public function dispatchMonitor(): JsonResponse
    {
        $pending = RiderJob::query()
            ->open()
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $active = RiderJob::query()
            ->whereIn('status', RiderJob::ACTIVE_STATUSES)
            ->with('rider:id,full_name,phone,last_latitude,last_longitude,last_location_update')
            ->orderBy('accepted_at')
            ->limit(200)
            ->get();

        $riders = Rider::query()
            ->approved()
            ->whereIn('availability', ['online', 'busy'])
            ->orderByDesc('last_location_update')
            ->limit(500)
            ->get();

        $activeByRider = $active->keyBy('rider_id');

        $pendingData = $pending->map(fn (RiderJob $job) => [
            'id' => (int) $job->id,
            'job_number' => (string) $job->job_number,
            'job_type' => (string) $job->job_type,
            'job_type_text' => $job->job_type_text,
            'dispatch_type' => $job->dispatch_type,
            'manual_needed' => $job->dispatch_type === 'manual_needed',
            'dispatch_round' => (int) $job->dispatch_round,
            'dispatch_radius_km' => $job->dispatch_radius_km !== null ? (float) $job->dispatch_radius_km : null,
            'candidates_count' => count($job->candidate_riders ?? []),
            'release_count' => (int) $job->release_count,
            'age_minutes' => $job->created_at ? (int) $job->created_at->diffInMinutes(now()) : 0,
            'created_at' => $job->created_at?->toIso8601String(),
            'last_dispatched_at' => $job->last_dispatched_at?->toIso8601String(),
            'pickup' => [
                'name' => $job->pickup_contact_name,
                'address' => $job->pickup_address,
                'latitude' => $job->pickup_latitude !== null ? (float) $job->pickup_latitude : null,
                'longitude' => $job->pickup_longitude !== null ? (float) $job->pickup_longitude : null,
            ],
            'dropoff' => [
                'area' => $job->delivery_area ?: RiderJob::deriveArea($job->delivery_address),
                'address' => $job->delivery_address,
                'latitude' => $job->delivery_latitude !== null ? (float) $job->delivery_latitude : null,
                'longitude' => $job->delivery_longitude !== null ? (float) $job->delivery_longitude : null,
            ],
            'distance_km' => (float) $job->distance_km,
            'total_fee' => (float) $job->total_fee,
            'rider_earnings' => (float) $job->rider_earnings,
            'cod_amount' => (float) $job->cod_amount,
            'source' => $job->source_type ? ['type' => class_basename($job->source_type), 'id' => (int) $job->source_id] : null,
            'url' => route('admin.rider-jobs.show', $job->id),
        ])->values();

        $activeData = $active->map(fn (RiderJob $job) => [
            'id' => (int) $job->id,
            'job_number' => (string) $job->job_number,
            'status' => (string) $job->status,
            'status_text' => $job->status_text,
            'gps_active' => (bool) $job->gps_active,
            'gps_warning_count' => (int) $job->gps_warning_count,
            'minutes_since_accept' => $job->accepted_at ? (int) $job->accepted_at->diffInMinutes(now()) : null,
            'rider' => $job->rider ? [
                'id' => (int) $job->rider->id,
                'full_name' => $job->rider->full_name,
                'phone' => $job->rider->phone,
            ] : null,
            'pickup' => [
                'latitude' => $job->pickup_latitude !== null ? (float) $job->pickup_latitude : null,
                'longitude' => $job->pickup_longitude !== null ? (float) $job->pickup_longitude : null,
            ],
            'dropoff' => [
                'latitude' => $job->delivery_latitude !== null ? (float) $job->delivery_latitude : null,
                'longitude' => $job->delivery_longitude !== null ? (float) $job->delivery_longitude : null,
            ],
            'cod_amount' => (float) $job->cod_amount,
            'url' => route('admin.rider-jobs.show', $job->id),
        ])->values();

        $ridersData = $riders->map(function (Rider $rider) use ($activeByRider) {
            $job = $activeByRider->get($rider->id);

            return array_merge($this->mapRiderPayload($rider), [
                'active_job_id' => $job ? (int) $job->id : null,
                'has_location_consent' => $rider->hasLocationConsent(),
            ]);
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'generated_at' => now()->toIso8601String(),
                'pending_jobs' => $pendingData,
                'active_jobs' => $activeData,
                'riders' => $ridersData,
                'stats' => [
                    'pending' => $pendingData->count(),
                    'manual_needed' => $pendingData->where('manual_needed', true)->count(),
                    'active' => $activeData->count(),
                    'online' => $ridersData->where('availability', 'online')->count(),
                    'busy' => $ridersData->where('availability', 'busy')->count(),
                    'stale' => $ridersData->where('is_stale', true)->count(),
                ],
            ],
        ]);
    }

    /**
     * หน้าจอมอนิเตอร์การกระจายงานสด (admin.riders.monitor) — หน้าเว็บดึง JSON จาก dispatchMonitor() ทุก 15 วินาที
     */
    public function monitor()
    {
        return view('admin.riders.monitor', [
            'dataUrl' => route('admin.riders.dispatch-monitor'),
            'refreshSeconds' => 15,
            'staleAfterSeconds' => $this->staleAfterSeconds(),
            'manualAfterMinutes' => (new DeliveryFeeCalculator)->intSetting('rider.pending_timeout_minutes'),
            'pageTitle' => 'มอนิเตอร์การกระจายงานสด',
        ]);
    }

    /**
     * เล่นย้อนหลัง GPS 24 ชม. (admin.riders.playback)
     */
    public function locationPlayback(Rider $rider)
    {
        $logs = RiderLocation::where('rider_id', $rider->id)
            ->where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at')
            ->get();

        return view('admin.riders.playback', [
            'rider' => $rider,
            'logs' => $logs,
            'pageTitle' => 'เล่นย้อนหลัง GPS: '.$rider->full_name,
        ]);
    }

    /**
     * API: ประวัติตำแหน่ง (admin.riders.location-history) — ?hours=24&limit=500
     */
    public function getLocationHistory(Request $request, Rider $rider): JsonResponse
    {
        $hours = max(1, min(720, (int) $request->input('hours', 24)));
        $limit = max(1, min(5000, (int) $request->input('limit', 500)));

        $logs = RiderLocation::where('rider_id', $rider->id)
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at')
            ->limit($limit)
            ->get();

        $totalDistance = 0.0;
        $prev = null;
        foreach ($logs as $log) {
            if ($prev) {
                $totalDistance += DeliveryFeeCalculator::haversineKm(
                    (float) $prev->latitude,
                    (float) $prev->longitude,
                    (float) $log->latitude,
                    (float) $log->longitude
                );
            }
            $prev = $log;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rider' => [
                    'id' => (int) $rider->id,
                    'name' => $rider->full_name,
                ],
                'path' => $logs->map(fn ($log) => [
                    'lat' => (float) $log->latitude,
                    'lng' => (float) $log->longitude,
                    'timestamp' => ($log->recorded_at ?? $log->created_at)?->toIso8601String(),
                    'job_id' => $log->job_id !== null ? (int) $log->job_id : null,
                    'speed' => $log->speed !== null ? (float) $log->speed : null,
                    'heading' => $log->heading !== null ? (float) $log->heading : null,
                    'accuracy' => $log->accuracy !== null ? (float) $log->accuracy : null,
                    'battery' => $log->battery_level !== null ? (int) $log->battery_level : null,
                ])->values(),
                'total_points' => $logs->count(),
                'total_distance' => round($totalDistance, 2),
            ],
        ]);
    }

    // =====================================================
    // ตั้งค่าระบบไรเดอร์
    // =====================================================

    /**
     * หน้าตั้งค่าระบบไรเดอร์ (admin.riders.settings)
     */
    public function settings()
    {
        return view('admin.riders.settings', [
            'settings' => $this->currentSettings(),
            'spec' => self::SETTINGS_SPEC,
            'feeExamples' => $this->feeExamples(),
            'updateUrl' => route('admin.riders.settings.update'),
            'pageTitle' => 'ตั้งค่าระบบไรเดอร์',
        ]);
    }

    /**
     * บันทึกตั้งค่าระบบไรเดอร์ (admin.riders.settings.update) — ฟิลด์ settings[<ชื่อ>]
     */
    public function updateSettings(Request $request): JsonResponse|RedirectResponse
    {
        $rules = [];
        $messages = [];
        foreach (self::SETTINGS_SPEC as $field => $spec) {
            $name = "settings.{$field}";
            $rules[$name] = match ($spec['type']) {
                'boolean' => ['nullable', 'boolean'],
                'string' => ['nullable', Rule::in($spec['options'] ?? [])],
                'integer' => ['nullable', 'integer', 'between:'.$spec['min'].','.$spec['max']],
                default => ['nullable', 'numeric', 'between:'.$spec['min'].','.$spec['max']],
            };
            $unit = isset($spec['unit']) ? ' '.$spec['unit'] : '';
            $messages["{$name}.between"] = $spec['label'].' ต้องอยู่ระหว่าง '.($spec['min'] ?? 0).' ถึง '.($spec['max'] ?? 0).$unit;
            $messages["{$name}.integer"] = $spec['label'].' ต้องเป็นจำนวนเต็ม';
            $messages["{$name}.numeric"] = $spec['label'].' ต้องเป็นตัวเลข';
            $messages["{$name}.boolean"] = $spec['label'].' ไม่ถูกต้อง';
            $messages["{$name}.in"] = $spec['label'].' ไม่ถูกต้อง';
        }
        $rules['settings'] = ['required', 'array'];
        $messages['settings.required'] = 'ไม่พบค่าที่ต้องการบันทึก';

        $validator = Validator::make($request->all(), $rules, $messages);

        // เงื่อนไขข้ามฟิลด์
        $validator->after(function ($v) use ($request) {
            $current = $this->currentSettings();
            $input = (array) $request->input('settings', []);
            $value = fn (string $field) => array_key_exists($field, $input) && $input[$field] !== null && $input[$field] !== ''
                ? $input[$field]
                : $current[$field];

            if ((float) $value('max_offer_radius_km') < (float) $value('offer_radius_km')) {
                $v->errors()->add('settings.max_offer_radius_km', 'รัศมีกระจายงานสูงสุดต้องไม่น้อยกว่ารัศมีรอบแรก');
            }

            if ((float) $value('min_fee') > 0 && (float) $value('base_fee') > (float) $value('min_fee') * 10) {
                $v->errors()->add('settings.base_fee', 'ค่าส่งเริ่มต้นสูงผิดปกติเมื่อเทียบกับค่าส่งขั้นต่ำ กรุณาตรวจสอบ');
            }
        });

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'VALIDATION_ERROR',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()->toArray(),
                    'data' => null,
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $input = (array) $request->input('settings', []);
        $changed = [];

        try {
            DB::transaction(function () use ($input, &$changed) {
                $current = $this->currentSettings();

                foreach (self::SETTINGS_SPEC as $field => $spec) {
                    if (! array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
                        // checkbox ไม่ติ๊ก = ไม่ส่งค่ามา → ถือว่า false เฉพาะชนิด boolean ที่ฟอร์มส่งมาทั้งชุด
                        if ($spec['type'] === 'boolean' && array_key_exists($field, $input)) {
                            $input[$field] = false;
                        } else {
                            continue;
                        }
                    }

                    $value = match ($spec['type']) {
                        'boolean' => filter_var($input[$field], FILTER_VALIDATE_BOOLEAN),
                        'integer' => (int) $input[$field],
                        'float' => round((float) $input[$field], 2),
                        default => (string) $input[$field],
                    };

                    if ($current[$field] === $value) {
                        continue;
                    }

                    Setting::set(
                        $spec['key'],
                        match ($spec['type']) {
                            'boolean' => $value ? '1' : '0',
                            default => (string) $value,
                        },
                        $spec['type'],
                        'rider'
                    );

                    $changed[$field] = ['from' => $current[$field], 'to' => $value];
                }
            });
        } catch (\Throwable $e) {
            return $this->failure($request, 'update_settings', $e);
        }

        Log::info('Admin: rider settings updated', ['admin_id' => Auth::id(), 'changed' => $changed]);

        return $this->respond(
            $request,
            true,
            $changed === [] ? 'ไม่มีค่าที่เปลี่ยนแปลง' : 'บันทึกตั้งค่าระบบไรเดอร์แล้ว ('.count($changed).' รายการ)',
            200,
            null,
            ['settings' => $this->currentSettings(), 'changed' => array_keys($changed), 'fee_examples' => $this->feeExamples()]
        );
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * ระงับ + จัดการงานค้าง
     */
    private function suspendRider(Request $request, Rider $rider, string $reason): JsonResponse|RedirectResponse
    {
        // ระงับได้เฉพาะไรเดอร์ที่อนุมัติแล้ว (หรือแก้เหตุผลของคนที่ถูกระงับอยู่)
        // — ใบสมัครที่ยังไม่อนุมัติให้ใช้ "ปฏิเสธ" ไม่งั้นยกเลิกระงับแล้วจะกลายเป็นอนุมัติโดยไม่ได้ตรวจเอกสาร
        if (! in_array($rider->status, ['approved', 'suspended'], true)) {
            return $this->respond($request, false, 'ระงับได้เฉพาะไรเดอร์ที่อนุมัติแล้ว ใบสมัครที่รอตรวจให้ใช้ "ปฏิเสธ"', 409, 'INVALID_STATUS');
        }

        try {
            $rider->suspend(Auth::user(), $reason);
            $job = $this->jobs->handleRiderSuspended($rider->fresh());
        } catch (\Throwable $e) {
            return $this->failure($request, 'suspend', $e, $rider);
        }

        Log::info('Admin: rider suspended', ['rider_id' => $rider->id, 'admin_id' => Auth::id(), 'job_id' => $job?->id]);

        $message = 'ระงับไรเดอร์เรียบร้อย';
        if ($job) {
            $message .= $job->status === 'pending'
                ? ' — งาน #'.$job->job_number.' ถูกคืนเข้าคิวให้ไรเดอร์คนอื่นแล้ว'
                : ' — ⚠️ ไรเดอร์ถือของงาน #'.$job->job_number.' อยู่ กรุณามอบหมายไรเดอร์ใหม่หรือปิดงาน';
        }

        return $this->respond($request, true, $message, 200, null, [
            'status' => 'suspended',
            'job' => $job ? ['id' => (int) $job->id, 'job_number' => $job->job_number, 'status' => $job->status] : null,
        ]);
    }

    /**
     * ค่าตั้งค่าปัจจุบัน (ชื่อฟิลด์ฟอร์ม => ค่า ชนิดถูกต้อง)
     *
     * @return array<string, mixed>
     */
    private function currentSettings(): array
    {
        $config = new DeliveryFeeCalculator;
        $values = [];

        foreach (self::SETTINGS_SPEC as $field => $spec) {
            $values[$field] = match ($spec['type']) {
                'boolean' => $config->boolSetting($spec['key']),
                'integer' => $config->intSetting($spec['key']),
                'float' => round($config->floatSetting($spec['key']), 2),
                default => (string) $config->setting($spec['key']),
            };
        }

        return $values;
    }

    /**
     * ตัวอย่างค่าส่งตามระยะ (ให้แอดมินเห็นผลของค่าที่ตั้ง)
     *
     * @return array<int, array<string, mixed>>
     */
    private function feeExamples(): array
    {
        $config = new DeliveryFeeCalculator;

        return array_map(fn (float $km) => $config->quoteForDistance($km), [1.0, 3.0, 5.0, 8.0, 12.0]);
    }

    /**
     * ข้อมูลตำแหน่งล่าสุด + ธงว่าเงียบนานเกินไป
     *
     * @return array{latitude: float, longitude: float, updated_at: ?string, is_stale: bool}|null
     */
    private function locationPayload(Rider $rider): ?array
    {
        if ($rider->last_latitude === null || $rider->last_longitude === null) {
            return null;
        }

        return [
            'latitude' => (float) $rider->last_latitude,
            'longitude' => (float) $rider->last_longitude,
            'updated_at' => $rider->last_location_update?->toIso8601String(),
            'updated_ago' => $rider->last_location_update?->diffForHumans(),
            'is_stale' => $this->isStale($rider),
        ];
    }

    /**
     * ข้อมูลไรเดอร์ 1 คนบนแผนที่
     *
     * @return array<string, mixed>
     */
    private function mapRiderPayload(Rider $rider): array
    {
        return [
            'id' => (int) $rider->id,
            'name' => $rider->full_name,
            'full_name' => $rider->full_name,
            'phone' => $rider->phone,
            'vehicle_type' => $rider->vehicle_type,
            'vehicle_plate' => $rider->vehicle_plate,
            'availability' => (string) $rider->availability,
            'latitude' => $rider->last_latitude !== null ? (float) $rider->last_latitude : null,
            'longitude' => $rider->last_longitude !== null ? (float) $rider->last_longitude : null,
            'last_location_update' => $rider->last_location_update?->toIso8601String(),
            'is_stale' => $this->isStale($rider),
            'url' => route('admin.riders.show', $rider->id),
        ];
    }

    /**
     * พิกัดเงียบเกิน gps_lost_timeout_seconds ของตลาดสด (ค่าเริ่มต้น 120 วินาที)
     */
    private function isStale(Rider $rider): bool
    {
        return $rider->last_location_update === null
            || $rider->last_location_update->lt(now()->subSeconds($this->staleAfterSeconds()));
    }

    private function staleAfterSeconds(): int
    {
        static $seconds = null;

        if ($seconds === null) {
            try {
                $seconds = max(30, (int) (\App\Models\FreshMarketSetting::getSettings()->gps_lost_timeout_seconds ?? 120));
            } catch (\Throwable) {
                $seconds = 120;
            }
        }

        return $seconds;
    }

    /**
     * ตอบกลับตามชนิดคำขอ (AJAX → JSON / ฟอร์ม → redirect พร้อม flash)
     *
     * @param  array<string, mixed>|null  $data
     */
    private function respond(Request $request, bool $success, string $message, int $status = 200, ?string $code = null, ?array $data = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            $body = ['success' => $success, 'message' => $message, 'data' => $data];
            if (! $success) {
                $body['code'] = $code ?? 'ERROR';
            }

            return response()->json($body, $success ? $status : ($status >= 400 ? $status : 422));
        }

        return back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * ข้อผิดพลาดที่ไม่คาดคิด → log + ข้อความไทย (ไม่หลุดข้อความ exception ดิบ)
     */
    private function failure(Request $request, string $action, \Throwable $e, ?Rider $rider = null): JsonResponse|RedirectResponse
    {
        if ($e instanceof RiderJobException) {
            return $this->respond($request, false, $e->getMessage(), $e->httpStatus, $e->errorCode, $e->context ?: null);
        }

        Log::error('Admin rider: '.$action.' failed', [
            'rider_id' => $rider?->id,
            'admin_id' => Auth::id(),
            'error' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);

        return $this->respond($request, false, 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500, 'SERVER_ERROR');
    }
}
