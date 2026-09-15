<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Models\UniquePaymentAmount;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🌙 /api/v1/juntra/server/amounts/* (2026-09-15) — Thaiprompt เป็นผู้จองยอดทศนิยมให้ทั้งสองเว็บ
 *
 * ทำไม: บัญชีรับเงินเดียวกัน + มือถือ SMS เครื่องเดียวกัน → ถ้าต่างคนต่างสุ่มทศนิยม ยอด 100.37
 *   ของลูกค้าจันทรากับบิลของเราอาจชนกัน แล้วเงินก้อนเดียวถูกตัดบิลทั้งสองฝั่ง
 *   → จองผ่าน UniquePaymentAmount::generate() ตัวเดียวกับบิลของเรา (lock + กันซ้ำ 24 ชม.)
 *
 * แถวที่จองให้จันทรา: transaction_type = 'juntraweb_topup', transaction_id = null,
 *   ref/id ของจันทราอยู่ที่ external_ref/external_id — ทุกเส้นจับคู่ SMS ของเราข้ามแถวนี้
 */
class AmountController extends Controller
{
    /**
     * POST /api/v1/juntra/server/amounts/reserve (JSON)
     *
     * idempotent ตาม ref: ref เดิมที่ยังจองอยู่ → คืนแถวเดิม (200 + idempotent)
     */
    public function reserve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'base_amount' => 'required|numeric|min:1|max:999999',
            'ref' => 'required|string|max:64',
            'external_id' => 'required|integer|min:1',
            'ttl_minutes' => 'required|integer|min:5|max:2880',
        ]);

        $ref = trim((string) $validated['ref']);
        $base = (int) floor((float) $validated['base_amount']);

        // serialize ต่อ ref — กันจันทรายิงซ้ำพร้อมกันแล้วได้สองยอดสำหรับรายการเดียว
        $lock = Cache::lock('juntra:amount-reserve:'.sha1($ref), 20);

        try {
            $lock->block(10);
        } catch (LockTimeoutException $e) {
            return response()->json([
                'message' => 'มีคำขอจองยอดของรายการนี้ค้างอยู่ กรุณาลองใหม่',
                'reason_code' => 'busy',
            ], 503);
        }

        try {
            $existing = UniquePaymentAmount::query()
                ->where('transaction_type', UniquePaymentAmount::TYPE_JUNTRAWEB_TOPUP)
                ->where('external_ref', $ref)
                ->where('status', 'reserved')
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->first();

            if ($existing !== null) {
                if ((int) floor((float) $existing->base_amount) !== $base) {
                    // ref เดิมแต่ราคาใหม่ — ห้ามคืนยอดผิดราคา และห้ามจองซ้อน (ลูกค้าอาจโอนตามยอดเก่า)
                    return response()->json([
                        'message' => 'รายการนี้มียอดที่จองไว้แล้วด้วยราคาอื่น — ปล่อยยอดเดิมก่อน',
                        'reason_code' => 'ref_conflict',
                        'data' => $this->present($existing),
                    ], 409);
                }

                return response()->json(['data' => $this->present($existing) + ['idempotent' => true]]);
            }

            $upa = UniquePaymentAmount::generate(
                $base,
                null,
                UniquePaymentAmount::TYPE_JUNTRAWEB_TOPUP,
                (int) $validated['ttl_minutes'],
                null,
                [
                    'external_ref' => $ref,
                    'external_id' => (int) $validated['external_id'],
                ]
            );

            if ($upa === null) {
                Log::warning('Juntra amount reserve: ทศนิยมของราคานี้เต็ม', [
                    'base_amount' => $base,
                    'ref' => $ref,
                ]);

                return response()->json([
                    'message' => 'ยอดทศนิยมของราคานี้เต็มชั่วคราว',
                    'reason_code' => 'pool_exhausted',
                ], 409);
            }

            Log::info('Juntra amount reserve: จองยอดให้จันทรา.online', [
                'id' => $upa->id,
                'unique_amount' => $this->money($upa->unique_amount),
                'ref' => $ref,
                'external_id' => (int) $validated['external_id'],
                'ttl_minutes' => (int) $validated['ttl_minutes'],
            ]);

            return response()->json(['data' => $this->present($upa)], 201);
        } finally {
            $lock->release();
        }
    }

    /**
     * POST /api/v1/juntra/server/amounts/release (JSON) — idempotent
     *
     * used      : จันทรารับเงินยอดนี้แล้ว (สถานะสุดท้าย — ไม่มีวันถอยกลับ)
     * cancelled : จันทรายกเลิกรายการ (ยอดยังถูกกันไว้ 24 ชม. เผื่อลูกค้าโอนตามมาทีหลัง)
     */
    public function release(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|min:1|required_without:ref',
            'ref' => 'nullable|string|max:64|required_without:id',
            'status' => 'required|in:used,cancelled',
        ]);

        $id = isset($validated['id']) ? (int) $validated['id'] : null;
        $ref = isset($validated['ref']) ? trim((string) $validated['ref']) : null;
        $target = (string) $validated['status'];

        return DB::transaction(function () use ($id, $ref, $target) {
            $query = UniquePaymentAmount::query()
                ->where('transaction_type', UniquePaymentAmount::TYPE_JUNTRAWEB_TOPUP)
                ->lockForUpdate();

            if ($id !== null) {
                $query->where('id', $id);
            }
            if ($ref !== null && $ref !== '') {
                $query->where('external_ref', $ref);
            }

            $upa = $query->orderByDesc('id')->first();

            if ($upa === null) {
                return response()->json([
                    'message' => 'ไม่พบยอดที่จองไว้ของจันทรา.online',
                    'reason_code' => 'not_found',
                ], 404);
            }

            $before = $upa->status;

            if ($target === 'used') {
                if ($upa->status !== 'used') {
                    $upa->update(['status' => 'used', 'matched_at' => now()]);
                }
            } elseif (in_array($upa->status, ['reserved', 'expired'], true)) {
                $upa->update(['status' => 'cancelled']);
            }
            // cancelled บนแถวที่ used แล้ว → คงเป็น used (เงินเข้าจริงแล้ว ห้ามถอย)

            if ($before !== $upa->status) {
                Log::info('Juntra amount release', [
                    'id' => $upa->id,
                    'ref' => $upa->external_ref,
                    'from' => $before,
                    'to' => $upa->status,
                ]);
            }

            return response()->json(['data' => [
                'released' => true,
                'id' => $upa->id,
                'status' => $upa->status,
            ]]);
        });
    }

    /**
     * @return array{id: int, unique_amount: string, base_amount: string, expires_at: string|null, ref: string|null}
     */
    protected function present(UniquePaymentAmount $upa): array
    {
        return [
            'id' => (int) $upa->id,
            'unique_amount' => $this->money($upa->unique_amount),
            'base_amount' => $this->money($upa->base_amount),
            'expires_at' => $upa->expires_at?->toIso8601String(),
            'ref' => $upa->external_ref,
        ];
    }

    /** เงินเป็นสตริงทศนิยม 2 ตำแหน่งเสมอ — ห้ามพึ่ง float->string (เช่น 100.37 → "100.37000000000001") */
    protected function money($value): string
    {
        return number_format(round((float) $value, 2), 2, '.', '');
    }
}
