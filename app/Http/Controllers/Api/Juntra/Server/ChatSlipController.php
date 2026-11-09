<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Models\JuntraChatSlip;
use App\Models\SlipVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * 🧾 /api/v1/juntra/server/chat-slips/* (2026-11-10) — สลิปที่ลูกค้าส่งในแชทเพจ รอเว็บจันทรามารับ
 *
 * บอทตรวจสลิปแล้วพักไว้ (JuntraChatSlipRelay) → เว็บจันทรามาดึงด้วย client_credentials ของมันเอง
 *   - fetch   : ข้อมูลสลิปหนึ่งใบ (หลังได้กริ่ง)
 *   - pending : สลิปที่ยังพักอยู่ (ตัวกวาดทุกนาทีของเว็บ — กรณีกริ่งไม่ถึง)
 *   - resolve : เว็บรายงานผล (เติมแล้ว / รอแอดมิน / สลิปซ้ำ / ไม่เจอบัญชี) — ครั้งเดียว ห้ามเปลี่ยนทีหลัง
 *
 * การเติมเงินจริง + การจองเลขอ้างอิงให้บิล ยังผ่าน /slips/claim (ส่ง chat_owner มาด้วย)
 */
class ChatSlipController extends Controller
{
    /** สลิปที่พักนานกว่านี้ ตัวกวาดไม่รับแล้ว (ให้แอดมินดูเอง) — เท่าเพดานอายุสลิปของบอท */
    private const PENDING_MAX_AGE_DAYS = 3;

    /**
     * POST /api/v1/juntra/server/chat-slips/fetch {trans_ref}
     */
    public function fetch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trans_ref' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        $row = JuntraChatSlip::where('trans_ref', $validated['trans_ref'])->first();
        if ($row === null) {
            return response()->json(['message' => 'ไม่พบสลิปนี้', 'reason_code' => 'not_found'], 404);
        }

        return response()->json(['data' => $row->toJuntraPayload()]);
    }

    /**
     * POST /api/v1/juntra/server/chat-slips/pending — สลิปที่ยังไม่มีผล (เก่ากว่า 1 นาที กันชนกับกริ่ง)
     */
    public function pending(): JsonResponse
    {
        $rows = JuntraChatSlip::where('status', JuntraChatSlip::PARKED)
            ->where('created_at', '<=', now()->subMinute())
            ->where('created_at', '>=', now()->subDays(self::PENDING_MAX_AGE_DAYS))
            ->orderBy('id')
            ->limit(20)
            ->get();

        return response()->json(['data' => $rows->map(fn (JuntraChatSlip $r) => $r->toJuntraPayload())->values()]);
    }

    /**
     * POST /api/v1/juntra/server/chat-slips/resolve
     *
     * 🔒 ล็อกแถวก่อนเปลี่ยน — รายงานซ้ำด้วยผลเดิม = สำเร็จ (idempotent) · ผลอื่น = 409
     */
    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trans_ref' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9]+$/'],
            'outcome' => ['required', Rule::in(JuntraChatSlip::OUTCOMES)],
            'user_ref' => 'nullable|string|max:64',
            'topup_ref' => 'nullable|string|max:64',
            'credited_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated) {
            $row = JuntraChatSlip::where('trans_ref', $validated['trans_ref'])->lockForUpdate()->first();
            if ($row === null) {
                return response()->json(['message' => 'ไม่พบสลิปนี้', 'reason_code' => 'not_found'], 404);
            }

            $topupRef = isset($validated['topup_ref']) ? trim((string) $validated['topup_ref']) : null;

            if ($row->status !== JuntraChatSlip::PARKED) {
                $same = $row->status === $validated['outcome'] && (string) $row->topup_ref === (string) $topupRef;

                return $same
                    ? response()->json(['data' => $row->toJuntraPayload() + ['idempotent' => true]])
                    : response()->json([
                        'message' => 'สลิปนี้มีผลแล้ว',
                        'reason_code' => 'already_resolved',
                        'data' => $row->toJuntraPayload(),
                    ], 409);
            }

            $row->fill([
                'status' => $validated['outcome'],
                'juntra_user_ref' => $validated['user_ref'] ?? null,
                'topup_ref' => $topupRef,
                'credited_amount' => $validated['outcome'] === JuntraChatSlip::CREDITED ? ($validated['credited_amount'] ?? null) : null,
                'outcome_note' => isset($validated['note']) ? mb_substr((string) $validated['note'], 0, 255) : null,
                'resolved_at' => now(),
            ])->save();

            // รอแอดมินที่บิลไหน = ผูกเลขอ้างอิงในทะเบียนไว้กับบิลนั้น (ยังเป็นของลูกค้าคนนี้ ใช้ที่อื่นไม่ได้)
            // เติมแล้ว = /slips/claim ผูกไปแล้ว · ซ้ำ/ไม่เจอบัญชี = ค้างไว้ใต้ตัวตนแชท ให้แอดมินดู
            if ($validated['outcome'] === JuntraChatSlip::REVIEW && $topupRef && ! empty($validated['user_ref'])) {
                $this->bindRegistry($row, (string) $validated['user_ref'], $topupRef);
            }

            Log::info('Juntra chat slip resolved', [
                'trans_ref' => $row->trans_ref,
                'outcome' => $row->status,
                'topup_ref' => $row->topup_ref,
                'credited' => $row->credited_amount,
            ]);

            return response()->json(['data' => $row->toJuntraPayload()]);
        });
    }

    /** ย้ายเจ้าของเลขอ้างอิงจากตัวตนแชท → บิลของเว็บ (เฉพาะตอนที่ยังเป็นของตัวตนแชทอยู่) */
    protected function bindRegistry(JuntraChatSlip $row, string $userRef, string $topupRef): void
    {
        $sv = SlipVerification::where('trans_ref', $row->trans_ref)
            ->where('consumed_by_platform', 'juntraweb')
            ->where('consumed_by_user_id', $row->owner())
            ->lockForUpdate()
            ->first();

        if ($sv === null) {
            return;
        }

        $raw = is_array($sv->raw) ? $sv->raw : [];
        $sv->forceFill([
            'consumed_by_user_id' => $userRef,
            'raw' => array_merge($raw, ['topup_ref' => $topupRef, 'bound_from' => $row->owner()]),
        ])->save();
    }
}
