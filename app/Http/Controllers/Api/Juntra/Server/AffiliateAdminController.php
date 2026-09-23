<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\JuntraAccount;
use App\Models\MlmMember;
use App\Models\User;
use App\Services\FortuneCommissionAdminService;
use App\Services\Juntra\JuntraMlmReadService;
use App\Services\MlmTeamTransferService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * 🌙 /api/v1/juntra/server/affiliate/admin/* (2026-09-21) — หลังบ้านจันทราจัดการผังแม่หมอผ่าน API
 *
 * เจ้าของสั่ง: หลังบ้านจันทราทำได้ครบเหมือนหลังบ้านแม่หมอ แต่ข้อมูลเก็บและคำนวณที่นี่
 *   ทุกคำสั่งเรียก service ตัวเดียวกับหลังบ้านแม่หมอ (FortuneCommissionAdminService,
 *   MlmTeamTransferService::adminDirectTransfer) — กติกาเรื่องเงินชุดเดียว ไม่มีทางลัด
 *
 * ขอบเขต (เจ้าของสั่ง 2026-09-21 "บิลแม่หมอ แยกกันทำรายการเว็บใครเว็บมัน แต่ค่าคอมคิดที่ thaiprompt"):
 *   - ค่าแนะนำ: จันทราดู/จัดการได้เฉพาะรายการที่มาจากบิลจันทรา — ของบิลบอทจัดการที่หลังบ้านแม่หมอ
 *   - ย้ายสาย: เฉพาะตำแหน่งที่จันทราสร้างให้ลูกค้าจันทรา (ตำแหน่งเดิมของบัญชี Thaiprompt จัดการที่หลังบ้านแม่หมอ)
 *   - ดูข้อมูลรายคน: เฉพาะลูกค้าจันทรา — ไม่เปิดรายชื่อ/อีเมลลูกค้าบอทให้หลังบ้านจันทรา
 *   - อัตราค่าแนะนำ: ดูได้อย่างเดียว ตั้งที่หน้าคอมแม่หมอของ Thaiprompt ที่เดียว
 *
 * สิทธิ์: ตัวตนของเซิร์ฟเวอร์จันทรา ('juntra.server') — จันทราเป็นผู้ตรวจว่าเป็นแอดมินจริง
 *   ทุกคำสั่งที่เปลี่ยนข้อมูลต้องบอกว่าแอดมินคนไหนของจันทราสั่ง (actor) ไว้ตรวจย้อนหลัง
 */
class AffiliateAdminController extends Controller
{
    public function __construct(
        private FortuneCommissionAdminService $admin,
        private JuntraMlmReadService $reads,
    ) {}

    /** GET /affiliate/admin/overview?date_from=&date_to= */
    public function overview(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $juntraBills = FortuneReading::where('reading_type', FortuneReading::READING_TYPE_JUNTRA);
        if (! empty($filters['date_from'])) {
            $juntraBills->whereDate('paid_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $juntraBills->whereDate('paid_at', '<=', $filters['date_to']);
        }

        return response()->json([
            'data' => [
                'commissions' => $this->admin->statsFor($this->admin->query($filters + ['source' => 'juntra'])),
                'juntra_bills' => [
                    'paid_count' => (clone $juntraBills)->where('is_paid', true)->count(),
                    'paid_amount' => (float) (clone $juntraBills)->where('is_paid', true)->sum('amount_paid'),
                    'voided_count' => (clone $juntraBills)->where('is_paid', false)->count(),
                ],
                'juntra_customers' => JuntraAccount::count(),
            ],
        ]);
    }

    /** GET /affiliate/admin/commissions?status=&level=&source=&search=&date_from=&date_to=&page=&per_page= */
    public function commissions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => 'nullable|in:all,pending,approved,paid,rejected',
            'level' => 'nullable|in:all,1,2',
            'search' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $paginator = $this->admin->query(['source' => 'juntra'] + $data)
            ->with(['user:id,name,email', 'fromUser:id,name,email', 'reading:id,facebook_user_name,amount_paid,paid_at,reading_type,bill_reference'])
            ->orderByDesc('created_at')
            ->paginate((int) ($data['per_page'] ?? 25), ['*'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (FortuneCommission $c) => $this->reads->commissionRow($c))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /** POST /affiliate/admin/commissions/approve {ids[], actor} */
    public function approve(Request $request): JsonResponse
    {
        $data = $request->validate($this->actorRules() + [
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:fortune_commissions,id',
        ]);

        if ($refused = $this->refuseNonJuntra($data['ids'])) {
            return $refused;
        }

        $count = $this->admin->approve($data['ids']);
        $this->audit('approve', $data, ['count' => $count]);
        $this->forgetRecipients($data['ids']);

        return response()->json(['data' => ['count' => $count]]);
    }

    /** POST /affiliate/admin/commissions/{commission}/reject {reason, actor} */
    public function reject(Request $request, FortuneCommission $commission): JsonResponse
    {
        $data = $request->validate($this->actorRules() + ['reason' => 'nullable|string|max:500']);

        if ($refused = $this->refuseNonJuntra([$commission->id])) {
            return $refused;
        }

        if (! $this->admin->reject($commission, $data['reason'] ?? null)) {
            return response()->json([
                'reason_code' => 'not_pending',
                'message' => 'ปฏิเสธได้เฉพาะรายการที่รอดำเนินการ — รายการนี้สถานะ '.$commission->status_name,
            ], 422);
        }

        $this->audit('reject', $data, ['commission_id' => $commission->id]);
        $this->reads->forgetCachesFor((int) $commission->user_id);

        return response()->json(['data' => ['status' => $commission->fresh()->status]]);
    }

    /** POST /affiliate/admin/commissions/{commission}/adjust {amount, reason, actor} */
    public function adjust(Request $request, FortuneCommission $commission): JsonResponse
    {
        $data = $request->validate($this->actorRules() + [
            'amount' => 'required|numeric|min:0|max:100000',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($refused = $this->refuseNonJuntra([$commission->id])) {
            return $refused;
        }
        if ((float) $data['amount'] > (float) ($commission->reading?->amount_paid ?? 0)) {
            return $this->refuse('amount_over_bill', 'ค่าแนะนำต้องไม่เกินยอดบิล (฿'.number_format((float) ($commission->reading?->amount_paid ?? 0), 2).')');
        }

        $error = $this->admin->adjust($commission, (float) $data['amount'], $data['reason'] ?? null);
        if ($error !== null) {
            return response()->json(['reason_code' => 'not_adjustable', 'message' => $error], 422);
        }

        $this->audit('adjust', $data, ['commission_id' => $commission->id]);
        $this->reads->forgetCachesFor((int) $commission->user_id);

        return response()->json(['data' => ['amount' => (float) $commission->amount]]);
    }

    /** POST /affiliate/admin/commissions/pay {ids[], actor} */
    public function pay(Request $request): JsonResponse
    {
        $data = $request->validate($this->actorRules() + [
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:fortune_commissions,id',
        ]);

        if ($refused = $this->refuseNonJuntra($data['ids'])) {
            return $refused;
        }

        $result = $this->admin->payOut($data['ids']);
        $this->audit('pay', $data, ['count' => $result['count'], 'errors' => count($result['errors'])]);
        $this->forgetRecipients($data['ids']);

        return response()->json(['data' => $result]);
    }

    /**
     * POST /affiliate/admin/commissions/manual {bill_id | fortune_reading_id, user_id, level, amount, notes, actor}
     *
     * ใช้ซ่อมค่าแนะนำของบิลจันทราที่ตกหล่น — ไม่ใช่ช่องสร้างเงินให้ใครก็ได้:
     *   บิลต้องยังจ่ายอยู่ · ผู้รับต้องเป็นผู้แนะนำชั้นนั้นของเจ้าของบิลในผังตอนนี้ · ยอดไม่เกินยอดบิล
     *   ลูกค้าเจ้าของบิล = เจ้าของบิลเสมอ (ไม่รับจากคำขอ)
     */
    public function createManual(Request $request): JsonResponse
    {
        // ระบุบิลได้สองแบบ: เลขบิลจันทรา (bill_id — แอดมินจันทรารู้เลขนี้) หรือ id บิลดูดวงฝั่งแม่หมอ
        $data = $request->validate($this->actorRules() + [
            'user_id' => 'required|integer|exists:users,id',
            'bill_id' => 'required_without:fortune_reading_id|integer|min:1',
            'fortune_reading_id' => 'required_without:bill_id|integer|min:1',
            'level' => 'required|in:1,2',
            'amount' => 'required|numeric|min:0.01|max:100000',
            'notes' => 'nullable|string|max:500',
        ]);

        $reading = FortuneReading::where('reading_type', FortuneReading::READING_TYPE_JUNTRA)
            ->when(
                isset($data['bill_id']),
                fn ($q) => $q->where('bill_reference', FortuneReading::JUNTRA_BILL_PREFIX.$data['bill_id']),
                fn ($q) => $q->whereKey($data['fortune_reading_id']),
            )
            ->first();
        if (! $reading) {
            return $this->refuse('not_juntra', 'ไม่พบบิลนี้ในบิลของเว็บจันทรา — สร้างค่าแนะนำจากหลังบ้านจันทราได้เฉพาะบิลจันทรา (บิลบอทจัดการที่หลังบ้านแม่หมอ)');
        }
        if (! $reading->is_paid) {
            return $this->refuse('bill_voided', "บิล {$reading->bill_reference} คืนเงินลูกค้าไปแล้ว — สร้างค่าแนะนำไม่ได้");
        }
        // เจ้าของสั่ง (2026-09-21): ไม่จ่ายค่าแนะนำย้อนหลังให้บิลก่อนเปิดระบบ — บิลกลุ่มนี้มีไว้นับสิทธิ์เท่านั้น
        if ($reading->isJuntraHistoryBill()) {
            return $this->refuse('history_bill', "บิล {$reading->bill_reference} จ่ายก่อนเปิดระบบค่าแนะนำ — ไม่มีค่าแนะนำย้อนหลัง");
        }
        if ((float) $data['amount'] > (float) $reading->amount_paid) {
            return $this->refuse('amount_over_bill', 'ค่าแนะนำต้องไม่เกินยอดบิล (฿'.number_format((float) $reading->amount_paid, 2).')');
        }

        $upline = $this->uplineOf((int) $reading->user_id, (int) $data['level']);
        if (! $upline || (int) $upline->user_id !== (int) $data['user_id']) {
            return $this->refuse('not_upline', $upline
                ? "ผู้รับชั้นนี้ของบิล {$reading->bill_reference} คือ {$upline->member_code} ({$upline->user?->name}) · ผู้ใช้ #{$upline->user_id}"
                : "เจ้าของบิล {$reading->bill_reference} ไม่มีผู้แนะนำชั้นที่ {$data['level']} ในผังตอนนี้");
        }

        $data['fortune_reading_id'] = $reading->id;
        $data['from_user_id'] = $reading->user_id;
        unset($data['bill_id']);
        $data['notes'] = trim(($data['notes'] ?? '').' · สั่งจากหลังบ้านจันทราโดย '.$data['actor']['name']);

        try {
            $commission = $this->admin->createManual($data);
        } catch (UniqueConstraintViolationException $e) {
            return $this->refuse('duplicate', 'ผู้รับคนนี้มีค่าแนะนำชั้นนี้ของบิลนี้อยู่แล้ว — ใช้ปรับยอดแทน');
        }
        $this->audit('manual', $data, ['commission_id' => $commission->id]);
        $this->reads->forgetCachesFor((int) $commission->user_id);

        return response()->json(['data' => ['id' => $commission->id]], 201);
    }

    /**
     * GET /affiliate/admin/settings — ดูได้อย่างเดียว (ตั้งที่หน้าคอมแม่หมอของ Thaiprompt)
     *   ส่งเฉพาะค่าที่ใช้กับบิลจันทรา — อัตราของบอทเป็นเรื่องของหลังบ้านแม่หมอ (เว็บใครเว็บมัน)
     */
    public function settings(): JsonResponse
    {
        return response()->json(['data' => Arr::only($this->admin->rates(FortuneTellingSetting::getSettings()), [
            'fortune_affiliate_enabled',
            'fortune_juntra_l1_percent',
            'fortune_juntra_l2_enabled',
            'fortune_juntra_l2_percent',
            'fortune_central_fallback_enabled',
        ])]);
    }

    /** GET /affiliate/admin/users?q=&page= — ลูกค้าจันทรา (ไม่เปิดรายชื่อลูกค้าบอทให้หลังบ้านจันทรา) */
    public function users(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:200',
        ]);

        return response()->json($this->reads->users(
            trim((string) ($data['q'] ?? '')),
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 50),
            juntraOnly: true,
        ));
    }

    /** GET /affiliate/admin/users/{userId}/stats */
    public function userStats(int $userId): JsonResponse
    {
        if ($refused = $this->refuseNonJuntraCustomer($userId)) {
            return $refused;
        }

        return response()->json($this->reads->stats($userId));
    }

    /** GET /affiliate/admin/users/{userId}/tree?depth= */
    public function userTree(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate(['depth' => 'nullable|integer|min:1|max:'.JuntraMlmReadService::MAX_TREE_DEPTH]);
        if ($refused = $this->refuseNonJuntraCustomer($userId)) {
            return $refused;
        }

        return response()->json($this->reads->tree($userId, (int) ($data['depth'] ?? JuntraMlmReadService::DEFAULT_TREE_DEPTH)));
    }

    /** GET /affiliate/admin/users/{userId}/commissions?page=&status= */
    public function userCommissions(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
            'status' => 'nullable|in:pending,approved,paid,rejected',
        ]);
        if ($refused = $this->refuseNonJuntraCustomer($userId)) {
            return $refused;
        }

        return response()->json($this->reads->commissions(
            $userId,
            ['status' => $data['status'] ?? null, 'juntra_only' => true],
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 25),
        ));
    }

    /** GET /affiliate/admin/members/search?q=&exclude= — หาผู้แนะนำใหม่ตอนย้ายสายงาน */
    public function searchMembers(Request $request, MlmTeamTransferService $transfers): JsonResponse
    {
        $data = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'exclude' => 'nullable|integer',
        ]);

        $members = $transfers->searchMembersForTransfer($data['q'], $data['exclude'] ?? null);

        return response()->json([
            'data' => $members->map(fn (MlmMember $m) => [
                'id' => $m->id,
                'member_code' => $m->member_code,
                'user_id' => $m->user_id,
                'name' => $m->user?->name, // ไม่ส่งอีเมล — ผู้แนะนำปลายทางอาจเป็นลูกค้าบอท
            ])->values()->all(),
        ]);
    }

    /**
     * POST /affiliate/admin/members/{member}/move {new_sponsor_member_id, notes, actor}
     *
     * ย้ายผู้แนะนำ (unilevel) ของสมาชิก — ทั้งทีมใต้เขาย้ายตามไปด้วย
     * ใช้ adminDirectTransfer ตัวเดียวกับหลังบ้านแม่หมอ (กันย้ายเข้าลูกทีมตัวเอง + โยกตัวนับทีม)
     */
    public function moveMember(Request $request, MlmMember $member, MlmTeamTransferService $transfers): JsonResponse
    {
        $data = $request->validate($this->actorRules() + [
            'new_sponsor_member_id' => 'required|integer|exists:mlm_members,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if (! JuntraAccount::managesMember($member->id)) {
            return $this->refuse('not_juntra', 'ย้ายสายจากหลังบ้านจันทราได้เฉพาะตำแหน่งที่จันทราสร้างให้ลูกค้าจันทรา — สมาชิกคนอื่นจัดการที่หลังบ้านแม่หมอ');
        }

        $oldSponsorId = $member->unilevel_sponsor_id;
        $actorUser = (! empty($data['actor']['thaiprompt_user_id']) ? User::find($data['actor']['thaiprompt_user_id']) : null)
            ?? User::find(1);

        try {
            $result = $transfers->adminDirectTransfer($member, [
                'new_unilevel_sponsor_id' => (int) $data['new_sponsor_member_id'],
                'admin_notes' => trim(($data['notes'] ?? '').' · ย้ายจากหลังบ้านจันทราโดย '.$data['actor']['name']),
            ], $actorUser);
        } catch (\Throwable $e) {
            return response()->json(['reason_code' => 'transfer_refused', 'message' => $e->getMessage()], 422);
        }

        // ผังของทั้งสายเก่าและสายใหม่เปลี่ยน
        $this->reads->forgetCachesForUpline($member->fresh());
        if ($oldSponsorId && ($oldSponsor = MlmMember::find($oldSponsorId))) {
            $this->reads->forgetCachesForUpline($oldSponsor);
        }
        $this->audit('move', $data, ['member_id' => $member->id, 'from' => $oldSponsorId]);

        return response()->json(['data' => [
            'member_id' => $member->id,
            'old_sponsor_id' => $oldSponsorId,
            'new_sponsor_id' => $result['new_data']['unilevel_sponsor_id'] ?? null,
        ]]);
    }

    /* ============================================================ */

    /** รายการเหล่านี้ต้องมาจากบิลจันทราทั้งหมด — ไม่งั้นปฏิเสธทั้งคำสั่ง (เว็บใครเว็บมัน) */
    private function refuseNonJuntra(array $ids): ?JsonResponse
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $juntra = FortuneCommission::whereIn('id', $ids)
            ->whereHas('reading', fn ($q) => $q->where('reading_type', FortuneReading::READING_TYPE_JUNTRA))
            ->count();

        if ($juntra === count($ids)) {
            return null;
        }

        return response()->json([
            'reason_code' => 'not_juntra',
            'message' => 'จัดการได้เฉพาะค่าแนะนำจากบิลเว็บจันทรา — ของบิลบอทจัดการที่หลังบ้านแม่หมอ',
        ], 422);
    }

    /** ดูข้อมูลรายคนได้เฉพาะลูกค้าจันทรา */
    private function refuseNonJuntraCustomer(int $userId): ?JsonResponse
    {
        return JuntraAccount::where('user_id', $userId)->exists()
            ? null
            : response()->json(['reason_code' => 'not_juntra', 'message' => 'ผู้ใช้นี้ไม่ใช่ลูกค้าเว็บจันทรา — ดูที่หลังบ้านแม่หมอ'], 404);
    }

    /** ผู้แนะนำชั้นที่ $level ของผู้ใช้นี้ในผังตอนนี้ (1 = ผู้แนะนำตรง · 2 = ผู้แนะนำของผู้แนะนำ) */
    private function uplineOf(int $userId, int $level): ?MlmMember
    {
        $member = MlmMember::where('user_id', $userId)->first();
        for ($i = 0; $member && $i < $level; $i++) {
            $member = $member->unilevel_sponsor_id ? MlmMember::with('user:id,name')->find($member->unilevel_sponsor_id) : null;
        }

        return $member;
    }

    private function refuse(string $reasonCode, string $message): JsonResponse
    {
        return response()->json(['reason_code' => $reasonCode, 'message' => $message], 422);
    }

    private function actorRules(): array
    {
        return [
            'actor' => 'required|array',
            'actor.juntra_user_id' => 'required|integer|min:1',
            'actor.name' => 'required|string|max:190',
            'actor.thaiprompt_user_id' => 'nullable|integer|min:1',
        ];
    }

    private function audit(string $action, array $data, array $context = []): void
    {
        Log::info("Juntra admin affiliate: {$action}", $context + [
            'actor_juntra_user_id' => $data['actor']['juntra_user_id'] ?? null,
            'actor_name' => $data['actor']['name'] ?? null,
        ]);
    }

    private function forgetRecipients(array $ids): void
    {
        FortuneCommission::whereIn('id', $ids)->pluck('user_id')->unique()
            ->each(fn ($uid) => $this->reads->forgetCachesFor((int) $uid));
    }
}
