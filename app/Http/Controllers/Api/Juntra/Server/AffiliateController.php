<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Models\FortuneCommission;
use App\Services\Fortune\WebFunnelTracker;
use App\Services\Juntra\JuntraAffiliateException;
use App\Services\Juntra\JuntraAffiliateService;
use App\Services\Juntra\JuntraMlmReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * 🌙 /api/v1/juntra/server/affiliate/* (2026-09-21) — ผังแม่หมอสำหรับลูกค้าเว็บ/แอพจันทราทุกคน
 *
 * ยิงด้วยตัวตนของเซิร์ฟเวอร์จันทรา ('juntra.server') ไม่ใช่ token ของลูกค้า
 *   → ลูกค้าที่สมัครด้วยเบอร์/อีเมล (ไม่เคยผูก Thaiprompt) ก็มีสายงานและได้ค่าแนะนำ
 *   user_ref = users.id ฝั่งจันทรา — จันทราเป็นผู้ยืนยันตัวลูกค้าเอง ที่นี่เชื่อเซิร์ฟเวอร์จันทรา
 *
 * ทุกเส้นเขียน idempotent: จันทราเก็บงานที่ไม่สำเร็จไว้ส่งซ้ำ
 *   503 = ลองใหม่ได้ · 4xx = ข้อมูลผิด (ส่งซ้ำไม่ช่วย)
 */
class AffiliateController extends Controller
{
    public function __construct(
        private JuntraAffiliateService $affiliate,
        private JuntraMlmReadService $reads,
    ) {}

    /**
     * POST /affiliate/accounts — หา/สร้างสมาชิกแม่หมอของลูกค้าจันทรา + ต่อสายงานตามรหัสเชิญ
     */
    public function ensureAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_ref' => 'required|integer|min:1',
            'name' => 'required|string|max:190',
            'thaiprompt_user_id' => 'nullable|integer|min:1',
            'referral_code' => 'nullable|string|max:64',
            // 🏬 (2026-11-03) fortune_pages.code ของสาขาที่พาลูกค้ามา — ไม่รู้จัก = ข้าม (ห้ามทำให้คำขอล้ม)
            'page_code' => 'nullable|string|max:64',
        ]);

        try {
            $result = $this->affiliate->ensureAccount(
                (int) $data['user_ref'],
                $data['name'],
                $data['thaiprompt_user_id'] ?? null,
                $data['referral_code'] ?? null,
                $data['page_code'] ?? null,
            );
        } catch (JuntraAffiliateException $e) {
            return $this->failure($e);
        }

        $sponsor = $result['member']?->unilevel_sponsor_id
            ? \App\Models\MlmMember::with('user:id,name')->find($result['member']->unilevel_sponsor_id)
            : null;

        return response()->json([
            'data' => [
                'member_code' => $result['member']?->member_code,
                'enrolled_now' => $result['enrolled_now'],
                'linked_via' => $result['account']->linked_via,
                'sponsor' => $sponsor ? [
                    'name' => $sponsor->user?->name,
                    'member_code' => $sponsor->member_code,
                ] : null,
                'referral' => $result['referral'],
            ],
        ], $result['enrolled_now'] ? 201 : 200);
    }

    /**
     * POST /affiliate/bills — บิลที่ลูกค้าจ่ายแล้วบนจันทรา → แจกค่าแนะนำ (ส่งซ้ำได้)
     */
    public function recordBill(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bill_id' => 'required|integer|min:1',
            'user_ref' => 'required|integer|min:1',
            'name' => 'required|string|max:190',
            'amount' => 'required|numeric|min:0.01|max:100000',
            'product' => 'required|string|max:60',
            'paid_at' => 'nullable|date',
            'thaiprompt_user_id' => 'nullable|integer|min:1',
            'referral_code' => 'nullable|string|max:64',
            // บิลก่อนเปิดระบบค่าแนะนำ — นับสิทธิ์ "เคยมีบิลที่ชำระแล้ว" แต่ไม่แจกค่าแนะนำ
            'history_only' => 'sometimes|boolean',
            // 🏬 (2026-11-03) สาขาของบิล (ไม่บังคับ) — ไม่ส่ง = ใช้สาขาต้นทางของลูกค้า (users.fortune_origin_page_id)
            'page_code' => 'nullable|string|max:64',
        ]);

        try {
            $result = $this->affiliate->recordBill($data);
        } catch (JuntraAffiliateException $e) {
            return $this->failure($e);
        }

        // 📈 (2026-11-14) ตัวนับ FB → เว็บ ขั้น 4 — ลูกค้าที่มาจากลิงก์ในแชทเพจซื้อคำทำนาย
        //    บิลใหม่ที่จ่ายจริงเท่านั้น (ไม่นับบิลซ้ำ / ยกเลิก / บิลย้อนหลังก่อนเปิดระบบ)
        if (! $result['duplicate'] && $result['status'] === 'paid' && ! $result['reading']?->isJuntraHistoryBill()) {
            WebFunnelTracker::paid(
                (string) $data['user_ref'],
                isset($data['thaiprompt_user_id']) ? (int) $data['thaiprompt_user_id'] : null,
                (int) $data['bill_id'],
                (float) $data['amount'],
                ! empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : null,
                $result['reading']?->fortune_page_id !== null ? (int) $result['reading']->fortune_page_id : null,
            );
        }

        return response()->json([
            'data' => [
                'bill_reference' => $result['bill_reference'],
                'reading_id' => $result['reading']?->id,
                'fortune_page_id' => $result['reading']?->fortune_page_id,
                'status' => $result['status'],
                'history_only' => (bool) $result['reading']?->isJuntraHistoryBill(),
                'duplicate' => $result['duplicate'],
                'member_code' => $result['member']?->member_code,
                'commissions' => array_map(fn (FortuneCommission $c) => [
                    'id' => $c->id,
                    'level' => (int) $c->level,
                    'user_id' => $c->user_id,
                    'recipient' => $c->user?->name,
                    'amount' => (float) $c->amount,
                    'status' => $c->status,
                ], $result['commissions']),
            ],
        ], $result['duplicate'] ? 200 : 201);
    }

    /**
     * POST /affiliate/bills/{billId}/void — จันทราคืนเงินลูกค้าแล้ว → ดึงค่าแนะนำคืน (ส่งซ้ำได้)
     */
    public function voidBill(Request $request, int $billId): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:255']);

        try {
            $result = $this->affiliate->voidBill($billId, $data['reason'] ?? null);
        } catch (JuntraAffiliateException $e) {
            return $this->failure($e);
        }

        if (! $result['found']) {
            // ไม่เคยได้รับบิลนี้ = ไม่มีค่าแนะนำให้ดึงคืน — จันทราถือว่าจบได้
            //   reason_code ชั้นบนสุด: ฝั่งจันทราแยก "ไม่พบบิล" ออกจาก "เส้นนี้ยังไม่ deploy" (404 เปล่า)
            return response()->json([
                'reason_code' => 'unknown_bill',
                'message' => 'แม่หมอไม่เคยได้รับบิลนี้ — ไม่มีค่าแนะนำให้ดึงคืน',
                'data' => ['voided' => false, 'reason_code' => 'unknown_bill'],
            ], 404);
        }

        return response()->json([
            'data' => [
                'voided' => true,
                'already_voided' => $result['already_voided'],
                'reverted' => $result['reverted'],
                'warnings' => $result['warnings'],
            ],
        ]);
    }

    /**
     * POST /affiliate/accounts/{userRef}/release — 🌙 (2026-11-03) จันทราลบบัญชีลูกค้าแล้ว (ส่งซ้ำได้)
     *
     * ปลดการผูกกับผู้ใช้ Thaiprompt — บัญชีจันทราใหม่ที่ผูก Thaiprompt คนเดิมจะชี้มาที่แถวเดิม
     *   (สายงาน ตำแหน่ง ค่าแนะนำเดิมทั้งหมด) แทนที่จะได้ 409 thaiprompt_taken
     * ไม่เคยรู้จัก user_ref นี้ = 404 unknown_account (จันทราถือว่าจบได้ ไม่มีอะไรให้ปลด)
     */
    public function releaseAccount(Request $request, int $userRef): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:255']);

        try {
            $result = $this->affiliate->releaseAccount($userRef, $data['reason'] ?? null);
        } catch (JuntraAffiliateException $e) {
            return $this->failure($e);
        }

        if (! $result['found']) {
            return response()->json([
                'reason_code' => 'unknown_account',
                'message' => 'แม่หมอไม่รู้จักบัญชีจันทรานี้ — ไม่มีอะไรให้ปลด',
                'data' => ['released' => false, 'reason_code' => 'unknown_account'],
            ], 404);
        }

        return response()->json([
            'data' => [
                'released' => true,
                'already_released' => $result['already_released'],
                'linked_via' => $result['linked_via'],
            ],
        ]);
    }

    /** GET /affiliate/members/{userRef}/stats */
    public function stats(int $userRef): JsonResponse
    {
        $userId = $this->affiliate->userIdFor($userRef);
        if ($userId === null) {
            return $this->notEnrolled();
        }

        return response()->json($this->reads->stats($userId));
    }

    /** GET /affiliate/members/{userRef}/tree?depth= */
    public function tree(Request $request, int $userRef): JsonResponse
    {
        $data = $request->validate(['depth' => 'nullable|integer|min:1|max:'.JuntraMlmReadService::MAX_TREE_DEPTH]);

        $userId = $this->affiliate->userIdFor($userRef);
        if ($userId === null) {
            return $this->notEnrolled();
        }

        return response()->json($this->reads->tree($userId, (int) ($data['depth'] ?? JuntraMlmReadService::DEFAULT_TREE_DEPTH)));
    }

    /** GET /affiliate/members/{userRef}/commissions?page=&per_page=&status=&from=&to= */
    public function commissions(Request $request, int $userRef): JsonResponse
    {
        $data = $request->validate([
            'page' => 'nullable|integer|min:1',
            // แอพส่งค่าที่ผู้ใช้เลือกผ่านจันทรามาตรง ๆ — รับกว้าง แล้วให้ service บีบช่วงเอง
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|max:32',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $userId = $this->affiliate->userIdFor($userRef);
        if ($userId === null) {
            return $this->notEnrolled();
        }

        return response()->json($this->reads->commissions(
            $userId,
            ['status' => $data['status'] ?? null, 'from' => $data['from'] ?? null, 'to' => $data['to'] ?? null],
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 25),
        ));
    }

    private function notEnrolled(): JsonResponse
    {
        return response()->json([
            'reason_code' => 'not_enrolled',
            'message' => 'ลูกค้าคนนี้ยังไม่อยู่ในผังแม่หมอ — เรียก POST /affiliate/accounts ก่อน',
        ], 404);
    }

    private function failure(JuntraAffiliateException $e): JsonResponse
    {
        return response()->json([
            'reason_code' => $e->reasonCode,
            'message' => $e->getMessage(),
        ], $e->status);
    }
}
