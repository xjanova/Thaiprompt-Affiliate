<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\FreshMarketException;
use App\Exceptions\HandoverException;
use App\Exceptions\RiderJobException;
use App\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use App\Models\DeliveryHandover;
use App\Models\FreshMarketOrder;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Services\DeliveryFeeCalculator;
use App\Services\FreshMarketService;
use App\Services\Rider\HandoverService;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * หลังบ้านงานไรเดอร์ (admin.rider-jobs.*) — route อยู่ใต้ auth + role:admin,super_admin
 *
 * ทุกการเปลี่ยนสถานะเรียก RiderJobService เท่านั้น (ไม่อัปเดตตารางตรงๆ):
 *   - ยกเลิก: ก่อนรับของ → cancel('admin') / รับของแล้ว → adminFail (ต้องประสานคืนของ)
 *     เลือก "ยกเลิกแล้วหาไรเดอร์ใหม่" ได้ (สร้างงานใหม่ให้ออเดอร์เดิม)
 *   - มอบหมายไรเดอร์ใหม่: adminReassign (ตรวจสิทธิ์/งานค้าง/วงเงิน COD/ออเดอร์ตัวเอง + แจ้งทุกฝ่าย)
 *   - สร้างงานใหม่ให้ออเดอร์ของงานที่ยกเลิก/ล้มเหลว: redispatch
 */
class RiderJobController extends Controller
{
    public function __construct(
        private readonly RiderJobService $jobs,
        private readonly RiderDispatchService $dispatch,
    ) {}

    /**
     * รายการงานทั้งหมด (admin.rider-jobs.index)
     */
    public function index(Request $request)
    {
        $query = RiderJob::with(['rider', 'customer']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('job_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('rider', function ($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('manual_needed')) {
            $query->open()->where('dispatch_type', 'manual_needed');
        }

        // ไรเดอร์รอบ 2: งานส่งมอบที่รอแอดมิน (ผู้ซื้อร้องเรียน / วางของรอปลดเงิน)
        if ($request->boolean('handover_review')) {
            $query->where(fn ($q) => $this->scopeHandoverReview($q));
        }

        if ($request->boolean('disputed')) {
            $query->whereHas('handover', fn ($h) => $h->where('status', DeliveryHandover::STATUS_DISPUTED))
                ->where(fn ($q) => HandoverService::scopeAdminResolvable($q));
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $stats = [
            'total' => RiderJob::count(),
            'pending' => RiderJob::where('status', 'pending')->count(),
            'manual_needed' => RiderJob::open()->where('dispatch_type', 'manual_needed')->count(),
            'in_progress' => RiderJob::whereIn('status', RiderJob::ACTIVE_STATUSES)->count(),
            'completed' => RiderJob::where('status', 'completed')->count(),
            'cancelled' => RiderJob::where('status', 'cancelled')->count(),
            'failed' => RiderJob::where('status', 'failed')->count(),
            'total_earnings' => round((float) RiderJob::where('status', 'completed')->sum('total_fee'), 2),
            // ไรเดอร์รอบ 2: เงินพักรอแอดมิน/รอปลดอัตโนมัติ
            // รอบแก้ 2 (B7): นับเฉพาะเรื่องที่แอดมินกดตัดสินได้จริง (เงื่อนไขเดียวกับ adminCanResolve) — ไม่มีตัวนับค้างถาวร
            'awaiting_release' => RiderJob::where('status', RiderJob::STATUS_AWAITING_RELEASE)
                ->where(fn ($q) => HandoverService::scopeAdminResolvable($q))
                ->count(),
            'disputed' => RiderJob::whereHas('handover', fn ($h) => $h->where('status', DeliveryHandover::STATUS_DISPUTED))
                ->where(fn ($q) => HandoverService::scopeAdminResolvable($q))
                ->count(),
            'handover_review' => RiderJob::where(fn ($q) => $this->scopeHandoverReview($q))->count(),
        ];

        $jobs = $query->latest('id')->paginate(20)->withQueryString();

        $riders = Rider::where('status', 'approved')
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return view('admin.rider-jobs.index', [
            'jobs' => $jobs,
            'stats' => $stats,
            'riders' => $riders,
            'pageTitle' => 'จัดการงานไรเดอร์',
        ]);
    }

    /**
     * งานส่งมอบที่รอแอดมินดู: งานรอปลดเงิน (awaiting_release) หรือการส่งมอบถูกร้องเรียน (ยังไม่ตัดสิน)
     *
     * รอบแก้ 2 (B7): เฉพาะงานที่แอดมินตัดสินได้จริง (HandoverService::scopeAdminResolvable = adminCanResolve)
     * → งานเก่าที่มีงานใหม่แทนแล้ว/การส่งมอบที่จบไปแล้ว ไม่ค้างในตัวนับ
     *
     * @param  \Illuminate\Database\Eloquent\Builder<RiderJob>  $query
     */
    private function scopeHandoverReview($query): void
    {
        $query->where(function ($q) {
            $q->where('status', RiderJob::STATUS_AWAITING_RELEASE)
                ->orWhereHas('handover', fn ($h) => $h->where('status', DeliveryHandover::STATUS_DISPUTED));
        })->where(fn ($q) => HandoverService::scopeAdminResolvable($q));
    }

    /**
     * รายละเอียดงาน (admin.rider-jobs.show)
     */
    public function show(RiderJob $job)
    {
        $job->load(['rider', 'customer', 'handover']);

        $locationHistory = $job->locations()
            ->orderBy('recorded_at')
            ->get();

        return view('admin.rider-jobs.show', [
            'job' => $job,
            'jobData' => $job->toApiDetail(null),
            'locationHistory' => $locationHistory,
            'adminActions' => $this->adminActions($job),
            'eligibleRiders' => $this->eligibleRiders($job),
            'source' => $this->sourceInfo($job),
            'dispatchAttempts' => $job->dispatch_attempts ?? [],
            'handover' => $this->handoverPanel($job),
            'pageTitle' => 'รายละเอียดงาน: #'.$job->job_number,
        ]);
    }

    // =====================================================
    // ไรเดอร์รอบ 2: ตัดสินการส่งมอบ (ร้องเรียน / วางของรอปลดเงิน)
    // =====================================================

    /**
     * ปล่อยเงิน (admin.rider-jobs.handover.release) — ปิดงานเหมือนส่งสำเร็จ แบ่งเงินร้าน/จ่ายไรเดอร์ตามปกติ
     */
    public function handoverRelease(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'reason.max' => 'หมายเหตุยาวเกินไป (ไม่เกิน 1,000 ตัวอักษร)',
        ]);

        if ($validator->fails()) {
            return $this->respond($request, false, $validator->errors()->first(), 422, 'VALIDATION_ERROR');
        }

        try {
            app(HandoverService::class)->adminRelease($job, Auth::user(), $request->input('reason'));
        } catch (HandoverException $e) {
            return $this->respond($request, false, $e->getMessage(), $e->httpStatus, $e->errorCode);
        } catch (\Throwable $e) {
            return $this->failure($request, 'handover_release', $e, $job);
        }

        return $this->respond($request, true, 'ปล่อยเงินแล้ว งานปิดเป็นส่งสำเร็จ และแจ้งผู้ซื้อกับไรเดอร์แล้ว', 200, null, [
            'job' => ['id' => (int) $job->id, 'status' => (string) $job->fresh()->status],
        ]);
    }

    /**
     * คืนเงินผู้ซื้อ (admin.rider-jobs.handover.refund) — งานส่งไม่สำเร็จ (ไรเดอร์ไม่ได้ค่าส่ง) + ยกเลิกออเดอร์ คืนเงินเต็มจำนวน
     */
    public function handoverRefund(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่คืนเงิน',
            'reason.min' => 'กรุณาระบุเหตุผลให้ชัดเจน',
            'reason.max' => 'เหตุผลยาวเกินไป (ไม่เกิน 1,000 ตัวอักษร)',
        ]);

        if ($validator->fails()) {
            return $this->respond($request, false, $validator->errors()->first(), 422, 'VALIDATION_ERROR');
        }

        try {
            app(HandoverService::class)->adminRefund($job, Auth::user(), (string) $request->input('reason'));
        } catch (HandoverException $e) {
            return $this->respond($request, false, $e->getMessage(), $e->httpStatus, $e->errorCode);
        } catch (ShopException|FreshMarketException $e) {
            // คืนเงินไม่สำเร็จ (เช่น กระเป๋าผู้ซื้อถูกระงับ) → ไม่มีอะไรเปลี่ยน
            return $this->respond($request, false, $e->getMessage(), 409, 'REFUND_FAILED');
        } catch (\Throwable $e) {
            return $this->failure($request, 'handover_refund', $e, $job);
        }

        return $this->respond($request, true, 'คืนเงินผู้ซื้อเต็มจำนวนแล้ว งานปิดเป็นส่งไม่สำเร็จ และแจ้งผู้ซื้อกับไรเดอร์แล้ว', 200, null, [
            'job' => ['id' => (int) $job->id, 'status' => (string) $job->fresh()->status],
        ]);
    }

    /**
     * รูปทางสำรองของการส่งมอบ (private disk) — เปิดได้เฉพาะแอดมิน (route อยู่ใต้ role:admin)
     */
    public function handoverPhoto(RiderJob $job, string $kind): StreamedResponse
    {
        $handover = $job->handover()->first();
        $path = match ($kind) {
            'arrival' => $handover?->arrival_photo_path,
            'waited' => $handover?->waited_photo_path,
            default => null,
        };

        if (! $path || str_contains($path, '..')) {
            abort(404, 'ไม่พบรูป');
        }

        $storage = Storage::disk(HandoverService::PHOTO_DISK);
        if (! $storage->exists($path)) {
            abort(404, 'ไม่พบไฟล์รูป');
        }

        Log::info('Admin: view handover photo', ['admin_id' => Auth::id(), 'job_id' => $job->id, 'kind' => $kind]);

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';

        return $storage->response($path, "handover-{$job->id}-{$kind}.{$extension}", [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ], 'inline');
    }

    /**
     * ข้อมูลแผง "การส่งมอบ" บนหน้ารายละเอียดงาน (null = งานแบบเดิมที่ไม่ต้องสแกน)
     *
     * @return array<string, mixed>|null
     */
    private function handoverPanel(RiderJob $job): ?array
    {
        if (! $job->handover_required) {
            return null;
        }

        $handover = $job->loadedHandover();
        $service = app(HandoverService::class);

        $photos = [];
        if ($handover?->arrival_photo_path) {
            $photos[] = ['label' => 'รูปถึงจุดส่ง (รอบ 1)', 'url' => route('admin.rider-jobs.handover.photo', [$job, 'arrival'])];
        }
        if ($handover?->waited_photo_path) {
            $photos[] = ['label' => 'รูปวางของ (รอบ 2)', 'url' => route('admin.rider-jobs.handover.photo', [$job, 'waited'])];
        }

        return [
            'row' => $handover,
            'status' => $handover?->status ?? 'waiting',
            'status_text' => self::handoverStatusText($handover?->status),
            'method_text' => match ($handover?->method) {
                'qr' => 'สแกน QR ทั้งสองฝ่าย',
                'code' => 'ไรเดอร์กรอกรหัส 6 หลัก + ผู้ซื้อสแกน QR',
                'fallback' => 'วางของ (รูป 2 รอบ) แล้วปลดเงินอัตโนมัติ',
                'buyer_confirm' => 'ผู้ซื้อกด "ได้รับของแล้ว" เอง',
                'admin' => 'แอดมินตัดสิน',
                default => null,
            },
            'dispute_reason_text' => $handover?->dispute_reason ? HandoverService::disputeReasonText($handover->dispute_reason) : null,
            'photos' => $photos,
            'can_resolve' => $service->adminCanResolve($job, $handover),
            'resolved_by_name' => $handover?->resolved_by ? \App\Models\User::whereKey($handover->resolved_by)->value('name') : null,
        ];
    }

    public static function handoverStatusText(?string $status): string
    {
        return match ($status) {
            null, 'waiting' => 'รอส่งมอบ',
            'rider_confirmed' => 'ไรเดอร์ยืนยันแล้ว รอผู้ซื้อสแกน',
            'buyer_confirmed' => 'ผู้ซื้อยืนยันแล้ว รอไรเดอร์สแกน',
            'completed' => 'ส่งมอบสำเร็จ',
            'fallback_waiting' => 'ไรเดอร์ถึงจุดส่ง กำลังรอผู้รับ',
            'fallback_pending_release' => 'วางของแล้ว รอปลดเงินอัตโนมัติ',
            'disputed' => 'ผู้ซื้อร้องเรียน รอแอดมินตัดสิน',
            'released' => 'ปลดเงินแล้ว',
            'refunded' => 'คืนเงินผู้ซื้อแล้ว',
            default => $status,
        };
    }

    /**
     * ยกเลิกงาน (admin.rider-jobs.cancel) — reason บังคับ, redispatch=1 เพื่อหาไรเดอร์ใหม่ทันที
     *
     * ยังไม่รับของ → cancelled (ออเดอร์กลับไปรอไรเดอร์) · รับของแล้ว → failed (ต้องประสานคืนของ)
     */
    public function cancel(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'redispatch' => ['nullable', 'boolean'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่ยกเลิก',
            'reason.min' => 'กรุณาระบุเหตุผลให้ชัดเจน',
            'reason.max' => 'เหตุผลยาวเกินไป (ไม่เกิน 500 ตัวอักษร)',
            'redispatch.boolean' => 'ตัวเลือกหาไรเดอร์ใหม่ไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return $this->respond($request, false, $validator->errors()->first(), 422, 'VALIDATION_ERROR');
        }

        $reason = trim((string) $request->input('reason'));
        $admin = Auth::user();

        if ($job->isTerminal()) {
            return $this->respond($request, false, 'งานนี้'.$job->status_text.'ไปแล้ว ยกเลิกไม่ได้', 409, 'INVALID_TRANSITION');
        }

        // รอบแก้ 2 (B4): เงินพักรอตัดสินการส่งมอบ (วางของแล้ว/ร้องเรียน/ผู้ซื้อยืนยันรับของแล้ว) → ปิดงานทางนี้ไม่ได้
        // (ไม่งั้นออเดอร์ย้อนไปรอไรเดอร์ใหม่ ทั้งที่ของอยู่กับผู้ซื้อ) ให้ใช้ปุ่ม "ปล่อยเงิน" / "คืนเงินผู้ซื้อ" ในแผงการส่งมอบ
        if ($hold = app(HandoverService::class)->holdMessageForJob($job, $job->loadedHandover())) {
            return $this->respond($request, false, $hold, 409, 'HANDOVER_PENDING');
        }

        try {
            if (in_array($job->status, ['picked_up', 'delivering', RiderJob::STATUS_AWAITING_RELEASE, 'delivered'], true)) {
                // ไรเดอร์ถือของอยู่ → ปิดเป็นส่งไม่สำเร็จ (ยกเลิกเฉยๆ ไม่ได้ ของต้องกลับร้าน)
                $done = $this->jobs->adminFail($job, $admin, $reason);
                $message = 'ปิดงานเป็น "ส่งไม่สำเร็จ" แล้ว (ไรเดอร์รับของไปแล้ว) กรุณาประสานคืนสินค้า';
            } else {
                $done = $this->jobs->cancel($job, 'admin', $reason.' (แอดมิน #'.$admin->id.')');
                $message = 'ยกเลิกงานเรียบร้อย';
            }
        } catch (\Throwable $e) {
            return $this->failure($request, 'cancel', $e, $job);
        }

        Log::info('Admin: rider job cancelled', ['job_id' => $job->id, 'admin_id' => $admin->id, 'status' => $done->status]);

        $newJob = null;
        if ($request->boolean('redispatch')) {
            [$newJob, $redispatchMessage] = $this->createReplacementJob($done, $admin);
            $message .= ' — '.$redispatchMessage;
        }

        return $this->respond($request, true, $message, 200, null, [
            'job' => ['id' => (int) $done->id, 'status' => (string) $done->status],
            'new_job' => $newJob ? ['id' => (int) $newJob->id, 'job_number' => $newJob->job_number, 'url' => route('admin.rider-jobs.show', $newJob)] : null,
        ]);
    }

    /**
     * มอบหมายไรเดอร์ใหม่ (admin.rider-jobs.reassign) — rider_id
     *
     * ได้เฉพาะงานที่ยังไม่จบ; ไรเดอร์ใหม่ต้องอนุมัติแล้ว ไม่ถูกระงับ ไม่มีงานค้าง วงเงิน COD พอ ไม่ใช่ผู้ซื้อ/ผู้ขาย
     */
    public function reassign(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'rider_id' => ['required', 'integer', 'exists:riders,id'],
        ], [
            'rider_id.required' => 'กรุณาเลือกไรเดอร์',
            'rider_id.integer' => 'ไรเดอร์ไม่ถูกต้อง',
            'rider_id.exists' => 'ไม่พบไรเดอร์ที่เลือก',
        ]);

        if ($validator->fails()) {
            return $this->respond($request, false, $validator->errors()->first(), 422, 'VALIDATION_ERROR');
        }

        if ($job->isTerminal()) {
            return $this->respond(
                $request,
                false,
                'งานนี้'.$job->status_text.'แล้ว มอบหมายใหม่ไม่ได้ — ใช้ "สร้างงานใหม่" แทน',
                409,
                'INVALID_TRANSITION'
            );
        }

        /** @var Rider $newRider */
        $newRider = Rider::findOrFail((int) $request->input('rider_id'));

        try {
            $updated = $this->jobs->adminReassign($job, $newRider, Auth::user());
        } catch (\Throwable $e) {
            return $this->failure($request, 'reassign', $e, $job);
        }

        Log::info('Admin: rider job reassigned', ['job_id' => $job->id, 'rider_id' => $newRider->id, 'admin_id' => Auth::id()]);

        return $this->respond($request, true, 'มอบหมายงานให้ '.$newRider->full_name.' เรียบร้อย', 200, null, [
            'job' => ['id' => (int) $updated->id, 'status' => (string) $updated->status, 'rider_id' => (int) $updated->rider_id],
        ]);
    }

    /**
     * สร้างงานใหม่ให้ออเดอร์ของงานที่ยกเลิก/ส่งไม่สำเร็จ (admin.rider-jobs.redispatch)
     */
    public function redispatch(Request $request, RiderJob $job): JsonResponse|RedirectResponse
    {
        if (! in_array($job->status, ['cancelled', 'failed'], true)) {
            return $this->respond($request, false, 'สร้างงานใหม่ได้เฉพาะงานที่ยกเลิกหรือส่งไม่สำเร็จ', 409, 'INVALID_TRANSITION');
        }

        [$newJob, $message] = $this->createReplacementJob($job, Auth::user());

        if (! $newJob) {
            return $this->respond($request, false, $message, 422, 'REDISPATCH_FAILED');
        }

        return $this->respond($request, true, $message, 200, null, [
            'new_job' => ['id' => (int) $newJob->id, 'job_number' => $newJob->job_number, 'url' => route('admin.rider-jobs.show', $newJob)],
        ]);
    }

    /**
     * สถิติรายวัน/รายเดือน (admin.rider-jobs.statistics)
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', 'daily') === 'monthly' ? 'monthly' : 'daily';
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $groupExpr = $period === 'daily' ? 'DATE(created_at)' : "DATE_FORMAT(created_at, '%Y-%m')";
        $groupAlias = $period === 'daily' ? 'date' : 'month';

        $stats = RiderJob::query()
            ->selectRaw("{$groupExpr} as {$groupAlias}")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN total_fee ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN rider_earnings ELSE 0 END) as rider_earnings")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN platform_fee ELSE 0 END) as platform_fee")
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->groupBy($groupAlias)
            ->orderBy($groupAlias)
            ->get();

        $topRiders = Rider::query()
            ->select('riders.*')
            ->withCount(['jobs as completed_jobs_count' => function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            }])
            ->having('completed_jobs_count', '>', 0)
            ->orderByDesc('completed_jobs_count')
            ->limit(10)
            ->get();

        return view('admin.rider-jobs.statistics', [
            'stats' => $stats,
            'topRiders' => $topRiders,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pageTitle' => 'สถิติงานไรเดอร์',
        ]);
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * สร้างงานใหม่ให้ออเดอร์ต้นทางของงานเดิม
     *
     * - ออเดอร์ตลาดสด → FreshMarketService::redispatchRider (อัปเดต rider_job_id + ตรวจสถานะออเดอร์)
     * - ออเดอร์อื่นที่ส่งด้วยไรเดอร์ได้ → RiderDispatchService::createJobForSource
     *
     * @return array{0: ?RiderJob, 1: string}
     */
    private function createReplacementJob(RiderJob $job, $admin): array
    {
        $source = $job->deliverableSource();

        if (! $source) {
            return [null, 'งานนี้ไม่มีออเดอร์ต้นทาง สร้างงานใหม่อัตโนมัติไม่ได้'];
        }

        try {
            if ($source instanceof FreshMarketOrder) {
                $order = app(FreshMarketService::class)->redispatchRider($source, $admin);
                $newJob = $order->rider_job_id ? RiderJob::find($order->rider_job_id) : null;
            } else {
                $newJob = $this->dispatch->createJobForSource($source, (string) $job->job_type);
            }
        } catch (RiderJobException|FreshMarketException $e) {
            return [null, 'สร้างงานใหม่ไม่ได้: '.$e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('Admin: redispatch failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);

            return [null, 'สร้างงานใหม่ไม่สำเร็จ กรุณาลองใหม่'];
        }

        if (! $newJob || (int) $newJob->id === (int) $job->id) {
            return [null, 'สร้างงานใหม่ไม่สำเร็จ (ออเดอร์อาจไม่อยู่ในสถานะที่เรียกไรเดอร์ได้)'];
        }

        Log::info('Admin: rider job redispatched', ['old_job_id' => $job->id, 'new_job_id' => $newJob->id, 'admin_id' => $admin?->id]);

        return [$newJob, 'สร้างงานใหม่ #'.$newJob->job_number.' และแจ้งไรเดอร์ใกล้ร้านแล้ว'];
    }

    /**
     * ปุ่มที่แอดมินกดได้กับงานนี้ตอนนี้
     *
     * @return array<int, string> cancel|fail|reassign|redispatch
     */
    private function adminActions(RiderJob $job): array
    {
        // รอบแก้ 2 (B4): เงินพักรอตัดสินการส่งมอบ → ไม่มีปุ่มปิดงาน (ใช้แผงการส่งมอบ: ปล่อยเงิน/คืนเงินผู้ซื้อ)
        $held = $job->handover_required && app(HandoverService::class)->holdMessageForJob($job, $job->loadedHandover()) !== null;

        return match (true) {
            in_array($job->status, ['pending', 'accepted', 'picking_up'], true) => ['cancel', 'reassign'],
            in_array($job->status, ['picked_up', 'delivering'], true) => $held ? ['reassign'] : ['fail', 'reassign'],
            in_array($job->status, ['cancelled', 'failed'], true) && $this->canRedispatch($job) => ['redispatch'],
            default => [],
        };
    }

    /**
     * สร้างงานใหม่ได้เฉพาะเมื่อออเดอร์ต้นทางยังอยู่ในสถานะที่เรียกไรเดอร์ได้
     * (ออเดอร์ที่ยกเลิก/คืนเงิน/ส่งถึงแล้ว → ไม่แสดงปุ่ม · service ตรวจซ้ำหลังล็อกอีกชั้น)
     */
    private function canRedispatch(RiderJob $job): bool
    {
        $source = $job->deliverableSource();

        if (! $source instanceof Model) {
            return false;
        }

        // มีงานที่ยังวิ่งอยู่แล้ว → ไม่ต้องสร้างใหม่
        if (RiderJob::forSource($source)->nonTerminal()->exists()) {
            return false;
        }

        return $this->dispatch->sourceCanDispatch($source);
    }

    /**
     * ไรเดอร์ที่มอบหมายงานนี้ได้ (อนุมัติแล้ว ไม่ถูกระงับ ไม่มีงานค้าง ไม่ใช่ผู้ซื้อ/ผู้ขาย) เรียงจากใกล้จุดรับของ
     *
     * @return array<int, array<string, mixed>>
     */
    private function eligibleRiders(RiderJob $job): array
    {
        if ($job->isTerminal()) {
            return [];
        }

        $partyUserIds = $job->partyUserIds();

        $riders = Rider::query()
            ->approved()
            ->whereNull('suspended_at')
            ->when($job->rider_id, fn ($q) => $q->where('id', '!=', $job->rider_id))
            ->when($partyUserIds !== [], fn ($q) => $q->whereNotIn('user_id', $partyUserIds))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('rider_jobs as active_jobs')
                    ->whereColumn('active_jobs.rider_id', 'riders.id')
                    ->whereIn('active_jobs.status', RiderJob::ACTIVE_STATUSES)
                    ->whereNull('active_jobs.deleted_at');
            })
            ->orderByRaw("CASE availability WHEN 'online' THEN 0 WHEN 'offline' THEN 1 ELSE 2 END")
            ->limit(100)
            ->get(['id', 'full_name', 'phone', 'vehicle_type', 'availability', 'last_latitude', 'last_longitude', 'last_location_update']);

        return $riders->map(function (Rider $rider) use ($job) {
            $distance = null;
            if ($rider->last_latitude !== null && $rider->last_longitude !== null
                && $job->pickup_latitude !== null && $job->pickup_longitude !== null) {
                $distance = round(DeliveryFeeCalculator::haversineKm(
                    (float) $rider->last_latitude,
                    (float) $rider->last_longitude,
                    (float) $job->pickup_latitude,
                    (float) $job->pickup_longitude
                ), 2);
            }

            return [
                'id' => (int) $rider->id,
                'full_name' => $rider->full_name,
                'phone' => $rider->phone,
                'vehicle_type_text' => $rider->vehicle_type_text,
                'availability' => (string) $rider->availability,
                'availability_text' => $rider->availability_text,
                'distance_to_pickup_km' => $distance,
                'last_location_update' => $rider->last_location_update?->toIso8601String(),
            ];
        })
            ->sortBy(fn ($r) => [$r['availability'] === 'online' ? 0 : 1, $r['distance_to_pickup_km'] ?? PHP_FLOAT_MAX])
            ->values()
            ->all();
    }

    /**
     * ข้อมูลออเดอร์ต้นทาง + ลิงก์หน้าออเดอร์ในหลังบ้าน
     *
     * @return array{type: ?string, id: ?int, order_number: ?string, url: ?string}
     */
    private function sourceInfo(RiderJob $job): array
    {
        $source = $job->deliverableSource();
        $url = null;

        if ($source instanceof FreshMarketOrder) {
            $url = $this->safeRoute('admin.fresh-market.orders.show', $source->getKey());
        } elseif ($source instanceof Order) {
            $url = $this->safeRoute('admin.ecommerce.orders.show', $source->getKey());
        }

        return [
            'type' => $job->source_type ? class_basename($job->source_type) : null,
            'id' => $job->source_id ? (int) $job->source_id : null,
            'order_number' => $source ? (data_get($source, 'order_number') ?: null) : null,
            'url' => $url,
        ];
    }

    private function safeRoute(string $name, mixed $param): ?string
    {
        try {
            return route($name, $param);
        } catch (\Throwable) {
            return null;
        }
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
     * ข้อผิดพลาด → ข้อความไทย (ธุรกิจ = ข้อความจาก RiderJobException / อื่นๆ = log + ข้อความกลาง)
     */
    private function failure(Request $request, string $action, \Throwable $e, RiderJob $job): JsonResponse|RedirectResponse
    {
        if ($e instanceof RiderJobException) {
            return $this->respond($request, false, $e->getMessage(), $e->httpStatus, $e->errorCode, $e->context ?: null);
        }

        Log::error('Admin rider job: '.$action.' failed', [
            'job_id' => $job->id,
            'admin_id' => Auth::id(),
            'error' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);

        return $this->respond($request, false, 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500, 'SERVER_ERROR');
    }
}
