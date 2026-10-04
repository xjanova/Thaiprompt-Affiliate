<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PosDeliveryException;
use App\Http\Controllers\Controller;
use App\Services\Pos\PosDeliveryRequestService;
use App\Support\Rider\ClientAppBuild;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 🛵 POS → ไรเดอร์ Thai Prompt — ฝั่งลูกค้าในแอป (auth:sanctum)
 *
 * GET  /api/v1/pos-requests/{token}?address_id=7  รายการ + ค่าส่งไรเดอร์ตามที่อยู่ + จ่ายไหวไหม (boolean)
 * POST /api/v1/pos-requests/{token}/pay           Idempotency-Key + {address_id, pin} → จ่ายจากกระเป๋าเงิน
 *
 * token ส่งมาพร้อม "TPPOS1." หรือไม่ก็ได้ · ตอบ {success, code, message} ภาษาไทย ไม่ส่งข้อความ exception ดิบ
 */
class PosPaymentRequestController extends Controller
{
    public function __construct(private readonly PosDeliveryRequestService $service) {}

    /**
     * ใบเสนอราคาของคำขอ
     */
    public function show(Request $request, string $token): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'address_id' => 'nullable|integer|min:1',
        ], [
            'address_id.integer' => 'ที่อยู่จัดส่งไม่ถูกต้อง',
            'address_id.min' => 'ที่อยู่จัดส่งไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray(), $validator->errors()->first());
        }

        $user = $request->user();
        $addressId = $request->query('address_id');

        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->quote($user, $token, $addressId !== null && $addressId !== '' ? (int) $addressId : null),
            ]);
        } catch (PosDeliveryException $e) {
            return $e->toJsonResponse();
        } catch (\Throwable $e) {
            Log::error('PosPaymentRequest: quote failed', ['user_id' => $user->id] + PosDeliveryRequestService::safeError($e));

            return response()->json([
                'success' => false,
                'code' => 'SERVER_ERROR',
                'message' => 'ดึงข้อมูลคำขอไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }

    /**
     * จ่ายคำขอด้วยกระเป๋าเงิน (ยืนยัน PIN)
     */
    public function pay(Request $request, string $token): JsonResponse
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 100) {
            return response()->json([
                'success' => false,
                'code' => PosDeliveryException::IDEMPOTENCY_KEY_REQUIRED,
                'message' => 'คำขอไม่สมบูรณ์ กรุณาอัปเดตแอปแล้วลองใหม่',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer|min:1',
            'pin' => 'required|string|max:20',
        ], [
            'address_id.required' => 'กรุณาเลือกที่อยู่จัดส่ง',
            'address_id.integer' => 'ที่อยู่จัดส่งไม่ถูกต้อง',
            'address_id.min' => 'ที่อยู่จัดส่งไม่ถูกต้อง',
            'pin.required' => 'กรุณากรอก PIN กระเป๋าเงิน',
            'pin.string' => 'PIN ไม่ถูกต้อง',
            'pin.max' => 'PIN ไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray(), $validator->errors()->first());
        }

        $user = $request->user();

        try {
            $result = $this->service->pay(
                $user,
                $token,
                (int) $request->input('address_id'),
                (string) $request->input('pin'),
                $idempotencyKey,
                ClientAppBuild::fromRequest($request) // ไรเดอร์รอบ 2: build แอปที่จ่าย (ตัดสินการสแกนส่งมอบ)
            );

            return response()->json([
                'success' => true,
                'message' => 'ชำระเงินสำเร็จ กำลังหาไรเดอร์มารับสินค้าที่ร้าน',
                'data' => $result,
            ]);
        } catch (PosDeliveryException $e) {
            return $e->toJsonResponse();
        } catch (\Throwable $e) {
            Log::error('PosPaymentRequest: pay failed', ['user_id' => $user->id] + PosDeliveryRequestService::safeError($e));

            return response()->json([
                'success' => false,
                'code' => 'PAY_FAILED',
                'message' => 'ชำระเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function validationError(array $errors, ?string $first): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => PosDeliveryException::VALIDATION,
            'message' => $first ?: 'ข้อมูลไม่ถูกต้อง',
            'errors' => $errors,
        ], 422);
    }
}
