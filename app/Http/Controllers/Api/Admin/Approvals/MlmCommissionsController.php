<?php

namespace App\Http\Controllers\Api\Admin\Approvals;

use App\Http\Controllers\Api\Admin\Approvals\Concerns\ApprovalResponses;
use App\Http\Controllers\Controller;
use App\Models\MlmCommission;
use App\Services\MlmCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 💰 แอปแอดมิน: คอมมิชชัน MLM ที่รออนุมัติ/รอจ่าย — ทางเดียวกับหลังบ้านเว็บ Admin\MlmCommissionController
 *
 * ⚠️ เคลื่อนเงิน → เรียก MlmCalculationService ตัวเดิมเท่านั้น (ไม่มีตรรกะเงินใหม่):
 *   - approve → approvePendingCommissions([id]): เฉพาะแถว status = pending → approved
 *   - pay     → payApprovedCommissions([id]): ทีละรายการใน transaction ของตัวเอง · ล็อกแถวคอมด้วย primary key แล้วตรวจสถานะซ้ำ
 *               · เคยมีรายการ wallet ของคอมนี้แล้ว = แค่ปิดสถานะ (ไม่จ่ายซ้ำ) · หักกองทุน MLM ก่อน (ไม่พอ = ข้าม)
 *               · ฝากเข้า wallet ผ่าน WalletService (type commission)
 *   🚫 ไม่เพิ่ม lockForUpdate แบบค้นหา (gap lock) บน mlm_commissions — กติกาโปรเจกต์ (deadlock กับ insert ของการแบ่งเงิน)
 * สิทธิ์: แอดมิน (เว็บใช้ role:admin,super_admin เหมือนกัน — ไม่มีสิทธิ์ย่อย)
 */
class MlmCommissionsController extends Controller
{
    use ApprovalResponses;

    public const STATUSES = ['pending', 'approved', 'paid', 'rejected', 'all'];

    public function __construct(private readonly MlmCalculationService $calculation) {}

    /**
     * GET /api/admin/approvals/mlm-commissions?status=pending&type=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->validateInput($request, [
            'status' => 'nullable|in:'.implode(',', self::STATUSES),
            'type' => 'nullable|string|max:50',
        ] + $this->pagingRules(), [
            'status.in' => 'สถานะไม่ถูกต้อง',
        ]);

        $status = (string) ($data['status'] ?? 'pending');

        $query = MlmCommission::query()->with(['user:id,name,member_number', 'plan:id,name', 'fromMember.user:id,name']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if (! empty($data['type'])) {
            $query->where('type', $data['type']);
        }

        // คิวรออนุมัติ/รอจ่าย = เก่าสุดก่อน · อื่น ๆ = ล่าสุดก่อน
        if (in_array($status, ['pending', 'approved'], true)) {
            $query->orderBy('created_at')->orderBy('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $page = $query->paginate((int) ($data['per_page'] ?? 20));

        $summary = MlmCommission::query()
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) AS pending_amount")
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END), 0) AS approved_amount")
            ->toBase()
            ->first();

        return $this->paged($page, $page->getCollection()->map(fn (MlmCommission $c) => $this->item($c))->all(), [
            'summary' => [
                'pending_count' => (int) ($summary->pending_count ?? 0),
                'pending_amount_thb' => round((float) ($summary->pending_amount ?? 0), 2),
                'approved_count' => (int) ($summary->approved_count ?? 0),
                'approved_amount_thb' => round((float) ($summary->approved_amount ?? 0), 2),
            ],
        ]);
    }

    /**
     * POST /api/admin/approvals/mlm-commissions/{commission}/approve
     */
    public function approve(MlmCommission $commission): JsonResponse
    {
        try {
            $count = $this->calculation->approvePendingCommissions([$commission->id]);
        } catch (\Throwable $e) {
            return $this->serverError('mlm_commission_approve', $e, ['commission_id' => $commission->id]);
        }

        $fresh = $commission->fresh();

        if ($count > 0) {
            return $this->ok($this->result($fresh, false), 'อนุมัติคอมมิชชั่นแล้ว');
        }

        // กดซ้ำ / แอดมินอีกคนทำไปก่อน → สถานะไปไกลกว่า pending แล้ว = สำเร็จเฉย ๆ
        if (in_array($fresh->status, ['approved', 'paid'], true)) {
            return $this->ok($this->result($fresh, true), 'คอมมิชชั่นนี้อนุมัติไปแล้ว');
        }

        return $this->fail('อนุมัติได้เฉพาะคอมมิชชั่นที่รออนุมัติ', 'INVALID_STATUS', 409, $this->result($fresh, false));
    }

    /**
     * POST /api/admin/approvals/mlm-commissions/{commission}/pay
     */
    public function pay(MlmCommission $commission): JsonResponse
    {
        if ($commission->status === 'paid') {
            return $this->ok($this->result($commission, true), 'คอมมิชชั่นนี้จ่ายไปแล้ว');
        }
        if ($commission->status !== 'approved') {
            return $this->fail('ต้องอนุมัติคอมมิชชั่นก่อนจ่าย', 'NOT_APPROVED', 409, $this->result($commission, false));
        }

        try {
            $count = $this->calculation->payApprovedCommissions([$commission->id]);
        } catch (\Throwable $e) {
            return $this->serverError('mlm_commission_pay', $e, ['commission_id' => $commission->id]);
        }

        $fresh = $commission->fresh();

        if ($count > 0 || $fresh->status === 'paid') {
            return $this->ok($this->result($fresh, $count === 0), $count > 0 ? 'จ่ายคอมมิชชั่นแล้ว' : 'คอมมิชชั่นนี้จ่ายไปแล้ว');
        }

        // service ข้ามรายการนี้ (กองทุน MLM ไม่พอ / กระเป๋าเงินผู้รับถูกระงับ / ไม่มีผู้รับ) — รายละเอียดอยู่ใน log
        return $this->fail('จ่ายคอมมิชชั่นไม่สำเร็จ (กองทุน MLM ไม่พอ หรือกระเป๋าเงินผู้รับใช้งานไม่ได้) กรุณาตรวจสอบแล้วลองใหม่', 'PAY_FAILED', 409, $this->result($fresh, false));
    }

    /**
     * @return array<string, mixed>
     */
    private function result(MlmCommission $commission, bool $already): array
    {
        return [
            'id' => (int) $commission->id,
            'status' => (string) $commission->status,
            'amount_thb' => round((float) $commission->commission_amount, 2),
            'already' => $already,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(MlmCommission $c): array
    {
        $waitingFrom = $c->status === 'approved' ? ($c->approved_at ?? $c->created_at) : $c->created_at;

        return [
            'id' => (int) $c->id,
            'type' => $c->type,
            'level' => $c->level !== null ? (int) $c->level : null,
            'status' => (string) $c->status,
            'status_label' => match ($c->status) {
                'pending' => 'รออนุมัติ',
                'approved' => 'อนุมัติแล้ว รอจ่าย',
                'paid' => 'จ่ายแล้ว',
                'rejected' => 'ปฏิเสธแล้ว',
                default => (string) $c->status,
            },
            'amount_thb' => round((float) $c->commission_amount, 2),
            'sales_amount_thb' => $c->sales_amount !== null ? round((float) $c->sales_amount, 2) : null,
            'pv_amount' => $c->pv_amount !== null ? round((float) $c->pv_amount, 2) : null,
            'recipient' => $this->personRef($c->user),
            'from_member' => $c->fromMember ? ['id' => (int) $c->fromMember->id, 'name' => $c->fromMember->user?->name] : null,
            'plan' => $c->plan?->name,
            'source' => [
                'type' => $c->source_type ? class_basename($c->source_type) : null,
                'id' => $c->source_id ? (int) $c->source_id : null,
            ],
            'created_at' => $this->iso($c->created_at),
            'approved_at' => $this->iso($c->approved_at),
            'paid_at' => $this->iso($c->paid_at),
            'waiting_minutes' => in_array($c->status, ['pending', 'approved'], true) ? $this->minutesSince($waitingFrom) : null,
            'actions' => [
                'can_approve' => $c->status === 'pending',
                'can_pay' => $c->status === 'approved',
            ],
        ];
    }
}
