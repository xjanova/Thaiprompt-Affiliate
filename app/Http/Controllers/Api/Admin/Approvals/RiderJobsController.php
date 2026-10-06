<?php

namespace App\Http\Controllers\Api\Admin\Approvals;

use App\Exceptions\HandoverException;
use App\Http\Controllers\Admin\RiderJobController as WebRiderJobController;
use App\Http\Controllers\Api\Admin\Approvals\Concerns\ApprovalResponses;
use App\Http\Controllers\Controller;
use App\Models\DeliveryHandover;
use App\Models\RiderJob;
use App\Services\Rider\HandoverService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * 💸 แอปแอดมิน: งานไรเดอร์ที่รอแอดมินตัดสิน — ร้องเรียนการส่งมอบ / เงินพักรอปลด / ไม่มีไรเดอร์รับ (ต้องมอบหมายเอง)
 *
 * ⚠️ endpoint เขียนทุกตัวเคลื่อนเงิน → เรียกเมธอดของหลังบ้านเว็บ Admin\RiderJobController ตัวเดิมเท่านั้น
 *    (ไม่มีตรรกะเงินใหม่ในไฟล์นี้) — การตรวจทั้งหมดอยู่ใน service ที่เว็บใช้:
 *    - release → HandoverService::adminRelease: ล็อกงาน+การส่งมอบ · ปิดไปแล้ว = HANDOVER_FINAL · adminCanResolve
 *      · ออเดอร์ถูกยกเลิก/คืนเงินแล้ว = ปล่อยไม่ได้ (HANDOVER_NOT_READY) → ปิดงานเป็นส่งสำเร็จ + แบ่งเงินร้าน/ไรเดอร์
 *    - refund  → HandoverService::adminRefund: ล็อกคู่เดียวกัน · adminCanResolve · ไรเดอร์ไม่ได้ค่าส่ง + ยกเลิกออเดอร์คืนเงินเต็ม
 *      (คืนไม่ได้ เช่นกระเป๋าผู้ซื้อถูกระงับ = REFUND_FAILED ไม่มีอะไรเปลี่ยน)
 *    - reassign → RiderJobService::adminReassign: งานยังไม่จบ · ไรเดอร์ใหม่อนุมัติแล้ว/ไม่ถูกระงับ/ไม่มีงานค้าง/วงเงิน COD พอ/ไม่ใช่คู่กรณี
 *    - redispatch → เฉพาะงาน cancelled/failed + ออเดอร์ต้นทางยังเรียกไรเดอร์ได้ (FreshMarketService / RiderDispatchService)
 *    สิทธิ์: แอดมิน (เว็บใช้ role:admin,super_admin เหมือนกัน — ไม่มีสิทธิ์ย่อย)
 */
class RiderJobsController extends Controller
{
    use ApprovalResponses;

    public const FILTERS = ['needs_decision', 'handover_review', 'disputed', 'awaiting_release', 'manual_needed'];

    /** เพดานรายการต่อหน้าของคิวนี้ (ต่ำกว่าคิวอื่นที่ 100 — แต่ละแถวคำนวณปุ่ม/สถานะผ่าน service) */
    public const MAX_PER_PAGE = 50;

    public function __construct(private readonly HandoverService $handovers) {}

    /**
     * GET /api/admin/approvals/rider-jobs?filter=needs_decision&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->validateInput($request, [
            'filter' => 'nullable|in:'.implode(',', self::FILTERS),
        ] + $this->pagingRules(), [
            'filter.in' => 'ตัวกรองไม่ถูกต้อง',
        ]);

        $filter = (string) ($data['filter'] ?? 'needs_decision');

        $query = self::applyFilter(RiderJob::query(), $filter)
            ->with(['rider:id,user_id,full_name', 'customer:id,name,member_number', 'handover'])
            ->orderBy('id');

        // แต่ละงานเรียก service หลายคิวรี (ออเดอร์ต้นทาง · adminCanResolve · ปุ่มของเว็บ) → เพดาน 50 ต่อหน้า (ขอมากกว่า = ได้ 50)
        $page = $query->paginate(min(self::MAX_PER_PAGE, (int) ($data['per_page'] ?? 20)));
        $web = app(WebRiderJobController::class);

        return $this->paged($page, $page->getCollection()->map(fn (RiderJob $job) => $this->listItem($job, $web))->all());
    }

    /**
     * นิยามคิว — ใช้ชุดเดียวกับตัวกรอง/ตัวนับของหน้าเว็บ admin.rider-jobs.index
     *
     * @param  Builder<RiderJob>  $query
     * @return Builder<RiderJob>
     */
    public static function applyFilter(Builder $query, string $filter): Builder
    {
        $manual = fn ($q) => $q->open()->where('dispatch_type', 'manual_needed');

        return match ($filter) {
            'handover_review' => $query->where(fn ($q) => WebRiderJobController::scopeHandoverReview($q)),
            'disputed' => $query->whereHas('handover', fn ($h) => $h->where('status', DeliveryHandover::STATUS_DISPUTED))
                ->where(fn ($q) => HandoverService::scopeAdminResolvable($q)),
            'awaiting_release' => $query->where('status', RiderJob::STATUS_AWAITING_RELEASE)
                ->where(fn ($q) => HandoverService::scopeAdminResolvable($q)),
            'manual_needed' => $query->where($manual),
            default => $query->where(function ($q) use ($manual) {
                $q->where(fn ($r) => WebRiderJobController::scopeHandoverReview($r))
                    ->orWhere($manual);
            }),
        };
    }

    /**
     * GET /api/admin/approvals/rider-jobs/{job}
     */
    public function show(RiderJob $job): JsonResponse
    {
        $job->load(['rider:id,user_id,full_name', 'customer:id,name,member_number', 'handover']);
        $web = app(WebRiderJobController::class);
        $handover = $job->loadedHandover();

        $photos = [];
        foreach (['arrival' => ['arrival_photo_path', 'arrival_photo_at', 'รูปถึงจุดส่ง (รอบ 1)'], 'waited' => ['waited_photo_path', 'waited_photo_at', 'รูปวางของ (รอบ 2)']] as $kind => [$col, $atCol, $label]) {
            if ($handover?->{$col}) {
                $photos[] = [
                    'kind' => $kind,
                    'label' => $label,
                    'taken_at' => $this->iso($handover->{$atCol}),
                    'url' => route('api.admin.approvals.rider-jobs.handover-photo', [$job->id, $kind]),
                    'requires_auth' => true,
                ];
            }
        }

        // รายชื่อไรเดอร์ที่มอบหมายได้ — ชุดเดียวกับหน้าเว็บ (ตัดเบอร์โทรออก ไม่จำเป็นต่อการเลือก)
        $eligible = array_map(function (array $r) {
            unset($r['phone']);

            return $r;
        }, $web->eligibleRiders($job));

        return $this->ok($this->listItem($job, $web) + [
            'title' => $job->title,
            'pickup' => ['address' => $job->pickup_address, 'contact_name' => $job->pickup_contact_name],
            'delivery' => ['address' => $job->delivery_address, 'contact_name' => $job->delivery_contact_name],
            'distance_km' => $job->distance_km !== null ? (float) $job->distance_km : null,
            'timeline' => [
                'created_at' => $this->iso($job->created_at),
                'accepted_at' => $this->iso($job->accepted_at),
                'picked_up_at' => $this->iso($job->picked_up_at),
                'delivered_at' => $this->iso($job->delivered_at),
                'completed_at' => $this->iso($job->completed_at),
                'cancelled_at' => $this->iso($job->cancelled_at),
                'failed_at' => $this->iso($job->failed_at),
            ],
            'cancellation_reason' => $job->cancellation_reason,
            'failure_reason' => $job->failure_reason,
            'handover_photos' => $photos,
            'eligible_riders' => $eligible,
            'dispatch_round' => $job->dispatch_round !== null ? (int) $job->dispatch_round : null,
        ]);
    }

    /**
     * GET /api/admin/approvals/rider-jobs/{job}/handover-photo/{kind}  (arrival | waited)
     *
     * รูปหลักฐานการส่งมอบจาก private disk ผ่าน Admin\RiderJobController::handoverPhoto (log การเปิดดูแบบเดียวกับเว็บ)
     */
    public function handoverPhoto(RiderJob $job, string $kind): Response
    {
        try {
            return app(WebRiderJobController::class)->handoverPhoto($job, $kind);
        } catch (HttpExceptionInterface $e) {
            return $this->fail('ไม่พบรูปนี้', 'PHOTO_NOT_FOUND', 404);
        } catch (\Throwable $e) {
            return $this->serverError('handover_photo', $e, ['job_id' => $job->id, 'kind' => $kind]);
        }
    }

    /**
     * POST /api/admin/approvals/rider-jobs/{job}/release  body: { reason? }
     *
     * 💸 ปล่อยเงิน: ปิดงานเป็นส่งสำเร็จ แบ่งเงินร้าน/จ่ายไรเดอร์ตามปกติ
     */
    public function release(Request $request, RiderJob $job): JsonResponse
    {
        return $this->decideHandover(
            $request,
            $job,
            DeliveryHandover::STATUS_RELEASED,
            'งานนี้ปล่อยเงินไปแล้ว',
            fn () => app(WebRiderJobController::class)->handoverRelease($request, $job),
            'rider_job_release'
        );
    }

    /**
     * POST /api/admin/approvals/rider-jobs/{job}/refund  body: { reason }
     *
     * 💸 คืนเงินผู้ซื้อเต็มจำนวน: งานส่งไม่สำเร็จ (ไรเดอร์ไม่ได้ค่าส่ง) + ยกเลิกออเดอร์
     */
    public function refund(Request $request, RiderJob $job): JsonResponse
    {
        return $this->decideHandover(
            $request,
            $job,
            DeliveryHandover::STATUS_REFUNDED,
            'งานนี้คืนเงินผู้ซื้อไปแล้ว',
            fn () => app(WebRiderJobController::class)->handoverRefund($request, $job),
            'rider_job_refund'
        );
    }

    /**
     * ตัดสินการส่งมอบ (ปล่อยเงิน / คืนเงิน) แบบกดซ้ำปลอดภัย
     *
     * - ผลเดียวกันอยู่แล้วก่อนเรียก → 200 already_decided (ไม่เรียก service)
     * - สองคำขอพร้อมกัน: ตัวที่แพ้ล็อกได้ HANDOVER_FINAL จาก service → อ่านสถานะใหม่
     *   ถ้าเป็นผลเดียวกับที่สั่ง = 200 already_decided (เงินขยับครั้งเดียวจากคำขอแรก) · ผลอื่น = คง 409 เดิม
     *
     * @param  callable(): Response  $call
     */
    private function decideHandover(Request $request, RiderJob $job, string $target, string $alreadyMessage, callable $call, string $action): JsonResponse
    {
        if ($job->handover()->value('status') === $target) {
            return $this->ok($this->actionResult($job, true), $alreadyMessage);
        }

        $response = $this->delegateToWeb($request, $call, $action);

        if (! $response->isSuccessful()
            && ($response->getData(true)['error_code'] ?? null) === HandoverException::FINAL
            && $job->handover()->value('status') === $target) {
            return $this->ok($this->actionResult($job, true), $alreadyMessage);
        }

        return $response;
    }

    /**
     * POST /api/admin/approvals/rider-jobs/{job}/reassign  body: { rider_id }
     */
    public function reassign(Request $request, RiderJob $job): JsonResponse
    {
        // ตรวจ rider_id ก่อนใช้ (กฎ/ข้อความชุดเดียวกับหน้าเว็บ — เว็บตรวจซ้ำอีกชั้นตอนส่งต่อ)
        $data = $this->validateInput($request, [
            'rider_id' => ['required', 'integer', 'exists:riders,id'],
        ], [
            'rider_id.required' => 'กรุณาเลือกไรเดอร์',
            'rider_id.integer' => 'ไรเดอร์ไม่ถูกต้อง',
            'rider_id.exists' => 'ไม่พบไรเดอร์ที่เลือก',
        ]);

        if ($job->status !== 'pending' && (int) $job->rider_id === (int) $data['rider_id'] && ! $job->isTerminal()) {
            return $this->ok(['job' => ['id' => (int) $job->id, 'status' => (string) $job->status, 'rider_id' => (int) $job->rider_id], 'already_decided' => true], 'งานนี้มอบหมายให้ไรเดอร์คนนี้อยู่แล้ว');
        }

        return $this->delegateToWeb($request, fn () => app(WebRiderJobController::class)->reassign($request, $job), 'rider_job_reassign');
    }

    /**
     * POST /api/admin/approvals/rider-jobs/{job}/redispatch
     *
     * สร้างงานใหม่ให้ออเดอร์ของงานที่ยกเลิก/ส่งไม่สำเร็จ (มีงานใหม่วิ่งอยู่แล้ว = ไม่สร้างซ้ำ)
     */
    public function redispatch(Request $request, RiderJob $job): JsonResponse
    {
        return $this->delegateToWeb($request, fn () => app(WebRiderJobController::class)->redispatch($request, $job), 'rider_job_redispatch');
    }

    /**
     * @return array<string, mixed>
     */
    private function actionResult(RiderJob $job, bool $already): array
    {
        return ['job' => ['id' => (int) $job->id, 'status' => (string) $job->fresh()->status], 'already_decided' => $already];
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(RiderJob $job, WebRiderJobController $web): array
    {
        $handover = $job->loadedHandover();
        $source = null;
        try {
            $source = $job->deliverableSource();
        } catch (\Throwable) {
            $source = null;
        }

        $canResolve = $job->handover_required && $this->handovers->adminCanResolve($job, $handover);
        $webActions = $web->adminActions($job);

        [$decision, $reason, $since] = $this->decisionOf($job, $handover, $canResolve);

        return [
            'id' => (int) $job->id,
            'job_number' => (string) $job->job_number,
            'job_type' => $job->job_type,
            'status' => (string) $job->status,
            'status_text' => $job->status_text,
            'decision_type' => $decision,
            'reason' => $reason,
            'amounts' => [
                'order_total_thb' => $source ? round((float) data_get($source, 'total_amount', 0), 2) : null,
                'delivery_fee_thb' => round((float) $job->total_fee, 2),
                'rider_earnings_thb' => round((float) $job->rider_earnings, 2),
                'platform_fee_thb' => round((float) $job->platform_fee, 2),
                'shop_bonus_thb' => round((float) ($job->shop_bonus ?? 0), 2),
                'cod_thb' => round((float) $job->cod_amount, 2),
            ],
            'order' => [
                'type' => $job->source_type ? class_basename($job->source_type) : null,
                'id' => $job->source_id ? (int) $job->source_id : null,
                'order_number' => $source ? (data_get($source, 'order_number') ?: null) : null,
            ],
            'rider' => $job->rider ? ['id' => (int) $job->rider->id, 'name' => (string) $job->rider->full_name] : null,
            'customer' => $this->personRef($job->customer),
            'handover' => $handover ? [
                'status' => (string) $handover->status,
                'status_text' => WebRiderJobController::handoverStatusText($handover->status),
                'method' => $handover->method,
                'disputed_at' => $this->iso($handover->disputed_at),
                'waited_photo_at' => $this->iso($handover->waited_photo_at),
                'buyer_confirmed_at' => $this->iso($handover->buyer_confirmed_at),
                'auto_release_at' => $this->iso($handover->auto_release_at),
            ] : null,
            'created_at' => $this->iso($job->created_at),
            'waiting_minutes' => $this->minutesSince($since),
            'actions' => [
                'can_release' => $canResolve,
                'can_refund' => $canResolve,
                'can_reassign' => in_array('reassign', $webActions, true),
                'can_redispatch' => in_array('redispatch', $webActions, true),
            ],
        ];
    }

    /**
     * ประเภทเรื่องที่รอตัดสิน + เหตุผล + เวลาที่เริ่มรอ
     *
     * @return array{0: string, 1: array{code: string, text: string, note: ?string}|null, 2: ?\Carbon\CarbonInterface}
     */
    private function decisionOf(RiderJob $job, ?DeliveryHandover $handover, bool $canResolve): array
    {
        if ($handover && $handover->status === DeliveryHandover::STATUS_DISPUTED && $canResolve) {
            return ['dispute', [
                'code' => (string) ($handover->dispute_reason ?? 'other'),
                'text' => 'ผู้ซื้อร้องเรียน: '.HandoverService::disputeReasonText($handover->dispute_reason),
                'note' => $handover->dispute_note,
            ], $handover->disputed_at ?? $job->updated_at];
        }

        if ($canResolve && $job->status === RiderJob::STATUS_AWAITING_RELEASE) {
            return ['awaiting_release', [
                'code' => 'awaiting_release',
                'text' => 'ไรเดอร์วางของแล้ว เงินพักรอปลด'.($handover?->auto_release_at ? ' (ปลดอัตโนมัติเมื่อครบเวลา ถ้าไม่มีร้องเรียน)' : ''),
                'note' => null,
            ], $handover?->waited_photo_at ?? $job->updated_at];
        }

        if ($job->status === 'pending' && $job->rider_id === null && $job->dispatch_type === 'manual_needed') {
            return ['manual_dispatch', [
                'code' => 'manual_needed',
                'text' => 'ไม่มีไรเดอร์รับงาน ต้องมอบหมายเอง',
                'note' => null,
            ], $job->last_dispatched_at ?? $job->created_at];
        }

        if ($canResolve) {
            return ['handover_review', [
                'code' => (string) ($handover?->status ?? 'waiting'),
                'text' => WebRiderJobController::handoverStatusText($handover?->status),
                'note' => null,
            ], $job->updated_at];
        }

        return ['none', null, null];
    }
}
