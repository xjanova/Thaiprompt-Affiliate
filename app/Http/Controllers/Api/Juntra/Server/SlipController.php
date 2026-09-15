<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Models\SlipVerification;
use App\Services\Fortune\SlipOkService;
use App\Services\Fortune\SlipUsageRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🧾 /api/v1/juntra/server/slips/* (2026-09-15) — เซิร์ฟเวอร์ของจันทรา.online เรียกตรง
 *
 * เป้าหมายเจ้าของ: "สลิปหนึ่งใบ ใช้ได้ครั้งเดียว ข้ามทั้งสองระบบ" (บอทแม่หมอ + วอลเลตจันทรา)
 *   - verify : ตรวจด้วย SlipOK ก้อนเดียวกับบอท (pool/โควตา/flood guard/hash cache/ถอด QR เอง)
 *              แล้วบอกว่าสลิปถูกใช้ในระบบเราแล้วหรือยัง (หลักฐานเชิงบวกเท่านั้น)
 *   - check  : เช็คเลขอ้างอิงหลายเลขฟรี (ไม่ยิง SlipOK)
 *   - claim  : จันทราเครดิตเงินแล้ว → จองเลขอ้างอิงลง slip_verifications
 *              ⇒ บอทด่าน 4 (evaluateForReading) + ด่านถอด QR จะมองสลิปนี้ว่า "ซ้ำ" ทันที
 *
 * middleware: juntra.server (client_credentials ของจันทราเท่านั้น) + throttle:120,1
 */
class SlipController extends Controller
{
    /** แพลตฟอร์มที่ใช้นับ flood guard + บันทึกผู้ใช้สลิป */
    public const PLATFORM = 'juntraweb';

    /**
     * POST /api/v1/juntra/server/slips/verify (multipart)
     */
    public function verify(Request $request, SlipOkService $slipok, SlipUsageRegistry $registry): JsonResponse
    {
        $validated = $request->validate([
            'slip' => 'required|image|max:6144',
            'user_ref' => 'required|string|max:64',
            'expected_amount' => 'nullable|numeric',
        ]);

        if (! $slipok->isEnabled()) {
            return response()->json([
                'message' => 'ระบบตรวจสลิปปิดใช้งานอยู่',
                'reason_code' => 'slipok_disabled',
            ], 503);
        }

        $userRef = trim((string) $validated['user_ref']);

        // flood guard ตัวเดียวกับบอท — กันยิงสลิปปลอมรัวจนโควตา SlipOK ที่ใช้ร่วมกันหมด
        if (! $slipok->canSpendForUser(self::PLATFORM, $userRef)) {
            $strike = $slipok->registerOverflowStrike(self::PLATFORM, $userRef);

            Log::warning('Juntra server slip verify: เกินเพดานตรวจสลิปต่อคน (flood guard)', [
                'user_ref' => $userRef,
                'strikes' => $strike['strikes'] ?? null,
                'client_id' => $request->attributes->get('juntra_client_id'),
            ]);

            return response()->json([
                'message' => 'ตรวจสลิปบ่อยเกินไป กรุณารอสักครู่',
                'reason_code' => 'flood_guard',
            ], 429);
        }

        try {
            $verify = $slipok->verifyByFile(
                $request->file('slip')->getRealPath(),
                self::PLATFORM,
                $userRef,
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra server slip verify threw', ['err' => $e->getMessage()]);

            return response()->json([
                'message' => 'ตรวจสลิปไม่สำเร็จชั่วคราว',
                'reason_code' => 'slipok_error',
            ], 503);
        }

        // SlipOK ไม่ได้ตัดสินอะไรเลย (เครือข่ายล้ม / 5xx / key ใช้ไม่ได้) → ชั่วคราว ให้จันทราเก็บไว้รอแอดมิน
        //   ผลที่มี error_code (ซ้ำ/บัญชีผิด/ไม่มี QR/โควตา/ธนาคารช้า) = SlipOK ตอบแล้ว → 200 ตามปกติ
        if (empty($verify['ok']) && ($verify['error_code'] ?? null) === null) {
            Log::warning('Juntra server slip verify: SlipOK ไม่ตอบผลที่ใช้ได้', [
                'http' => $verify['http'] ?? null,
                'message' => $verify['message'] ?? null,
                'user_ref' => $userRef,
            ]);

            return response()->json([
                'message' => 'ตรวจสลิปไม่สำเร็จชั่วคราว',
                'reason_code' => 'slipok_error',
            ], 503);
        }

        $data = $registry->describe($verify);

        Log::info('Juntra server slip verify', [
            'user_ref' => $userRef,
            'expected_amount' => $validated['expected_amount'] ?? null,
            'ok' => $data['ok'],
            'error_code' => $data['error_code'],
            'trans_ref' => $data['trans_ref'],
            'amount' => $data['amount'],
            'receiver_matches' => $data['receiver_matches'],
            'used' => $data['used'],
            'used_source' => $data['used_source'],
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * POST /api/v1/juntra/server/slips/check (JSON) — ฟรี ไม่ยิง SlipOK
     */
    public function check(Request $request, SlipUsageRegistry $registry): JsonResponse
    {
        $validated = $request->validate([
            'refs' => 'required|array|min:1|max:25',
            'refs.*' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        return response()->json(['data' => [
            'used' => $registry->lookupMany($validated['refs']),
        ]]);
    }

    /**
     * POST /api/v1/juntra/server/slips/claim (JSON)
     *
     * 🔒 insert ตรงๆ แล้วให้ unique index ของ trans_ref ตัดสิน (ไม่ check-then-insert)
     *    → สองคำขอพร้อมกันไม่มีทางชนะทั้งคู่
     */
    public function claim(Request $request, SlipOkService $slipok, SlipUsageRegistry $registry): JsonResponse
    {
        $validated = $request->validate([
            'trans_ref' => 'required|string|max:64',
            'amount' => 'required|numeric|min:0',
            'user_ref' => 'required|string|max:64',
            'topup_ref' => 'required|string|max:64',
            'sender_name' => 'nullable|string|max:120',
            'receiver_account' => 'nullable|string|max:64',
            'sending_bank' => 'nullable|string|max:8',
            'receiving_bank' => 'nullable|string|max:8',
            'trans_timestamp' => 'nullable|string|max:64',
        ]);

        $transRef = trim((string) $validated['trans_ref']);
        $userRef = trim((string) $validated['user_ref']);
        $topupRef = trim((string) $validated['topup_ref']);

        try {
            SlipVerification::create([
                'trans_ref' => $transRef,
                'fortune_reading_id' => null,
                'consumed_by_platform' => self::PLATFORM,
                'consumed_by_user_id' => $userRef,
                'amount' => $validated['amount'],
                'sender_name' => $validated['sender_name'] ?? null,
                'receiver_account' => $validated['receiver_account'] ?? null,
                'sending_bank' => $validated['sending_bank'] ?? null,
                'receiving_bank' => $validated['receiving_bank'] ?? null,
                'status' => 'verified',
                'flagged_review' => false,
                'raw' => [
                    'source' => self::PLATFORM,
                    'topup_ref' => $topupRef,
                    'trans_timestamp' => $validated['trans_timestamp'] ?? null,
                    'client_id' => $request->attributes->get('juntra_client_id'),
                ],
                'verified_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return $this->respondToExistingClaim($registry, $transRef, $userRef, $topupRef);
        } catch (QueryException $e) {
            // driver เก่าบางตัวไม่แปลงเป็น UniqueConstraintViolationException — SQLSTATE 23000 = ซ้ำ
            if ((string) $e->getCode() === '23000' && $registry->findByRef($transRef) !== null) {
                return $this->respondToExistingClaim($registry, $transRef, $userRef, $topupRef);
            }

            throw $e;
        }

        // เครดิตสำเร็จ = จ่ายจริง → เคลียร์ตัวนับ flood เหมือนบอทตอนสลิปผ่าน (ลูกค้าประจำไม่ติดเพดาน)
        $slipok->clearFloodCounters(self::PLATFORM, $userRef);

        Log::info('Juntra server slip claim: จองเลขอ้างอิงสลิปให้จันทรา.online แล้ว', [
            'trans_ref' => $transRef,
            'user_ref' => $userRef,
            'topup_ref' => $topupRef,
            'amount' => $validated['amount'],
        ]);

        return response()->json(['data' => ['claimed' => true]], 201);
    }

    /**
     * เลขอ้างอิงนี้มีเจ้าของแล้ว — ถ้าเป็น "การเคลมเดิมของจันทรา" ตอบสำเร็จซ้ำ (idempotent) ไม่งั้น 409
     */
    protected function respondToExistingClaim(SlipUsageRegistry $registry, string $transRef, string $userRef, string $topupRef): JsonResponse
    {
        $existing = $registry->findByRef($transRef);

        if ($existing === null) {
            // ชนแต่หาแถวไม่เจอ (ถูกลบกลางทาง) — ไม่เดา ให้จันทราลองใหม่
            return response()->json([
                'message' => 'บันทึกสลิปไม่สำเร็จชั่วคราว กรุณาลองใหม่',
                'reason_code' => 'retry',
            ], 503);
        }

        $raw = is_array($existing->raw) ? $existing->raw : [];
        $sameClaim = $existing->consumed_by_platform === self::PLATFORM
            && (string) $existing->consumed_by_user_id === $userRef
            && (string) ($raw['topup_ref'] ?? '') === $topupRef;

        if ($sameClaim) {
            return response()->json(['data' => ['claimed' => true, 'idempotent' => true]]);
        }

        Log::warning('Juntra server slip claim: สลิปนี้ถูกใช้ไปแล้ว (ปฏิเสธการเคลม)', [
            'trans_ref' => $transRef,
            'claim_user_ref' => $userRef,
            'claim_topup_ref' => $topupRef,
            'used_by_platform' => $existing->consumed_by_platform,
            'used_by_user' => $existing->consumed_by_user_id,
            'used_by_reading_id' => $existing->fortune_reading_id,
        ]);

        return response()->json([
            'message' => 'สลิปนี้ถูกใช้ไปแล้ว',
            'reason_code' => 'already_used',
            'data' => $registry->usedBy($existing),
        ], 409);
    }
}
