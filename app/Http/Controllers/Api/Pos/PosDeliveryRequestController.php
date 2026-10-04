<?php

namespace App\Http\Controllers\Api\Pos;

use App\Exceptions\PosDeliveryException;
use App\Http\Controllers\Controller;
use App\Models\PosTerminal;
use App\Services\Pos\PosDeliveryPresenter;
use App\Services\Pos\PosDeliveryRequestService;
use App\Services\Pos\PosTerminalAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 🛵 POS → ไรเดอร์ Thai Prompt — ฝั่งเครื่อง POS (middleware pos.terminal)
 *
 * POST /api/pos/delivery-requests              สร้างคำขอ + QR (TPPOS1.{token})
 * GET  /api/pos/delivery-requests/{id}         สถานะ (POS ถามทุก ~5 วินาที)
 * POST /api/pos/delivery-requests/{id}/cancel  ยกเลิก (เฉพาะที่รอจ่าย)
 *
 * เห็นเฉพาะคำขอของร้านตัวเอง · ตอบ {success, code, message} ภาษาไทย ไม่ส่งข้อความ exception ดิบ
 */
class PosDeliveryRequestController extends Controller
{
    public function __construct(
        private readonly PosDeliveryRequestService $service,
        private readonly PosDeliveryPresenter $presenter,
    ) {}

    /**
     * สร้างคำขอส่งไรเดอร์ (ส่ง local_id เดิมซ้ำ = ได้คำขอเดิม)
     */
    public function store(Request $request): JsonResponse
    {
        $terminal = $this->terminal($request);

        $validator = Validator::make($request->all(), [
            'local_id' => 'required|string|max:64',
            'order_local_id' => 'nullable|string|max:64',
            'items' => 'required|array|min:1|max:100',
            'items.*' => 'required|array',
            'items.*.product_id' => 'nullable|integer|min:1|required_without:items.*.sku',
            'items.*.sku' => 'nullable|string|max:100|required_without:items.*.product_id',
            'items.*.qty' => 'required|integer|min:1|max:999',
            'items.*.name' => 'nullable|string|max:255',
            'customer_phone' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value !== null && trim((string) $value) !== '' && PosDeliveryRequestService::normalizePhone((string) $value) === null) {
                        $fail('เบอร์มือถือลูกค้าไม่ถูกต้อง (ตัวอย่าง 0812345678)');
                    }
                },
            ],
            'note' => 'nullable|string|max:500',
        ], [
            'local_id.required' => 'กรุณาส่งเลขอ้างอิงคำขอ (local_id)',
            'local_id.max' => 'เลขอ้างอิงคำขอยาวเกิน 64 ตัวอักษร',
            'order_local_id.max' => 'เลขบิลยาวเกิน 64 ตัวอักษร',
            'items.required' => 'กรุณาเลือกสินค้าอย่างน้อย 1 รายการ',
            'items.array' => 'รูปแบบรายการสินค้าไม่ถูกต้อง',
            'items.min' => 'กรุณาเลือกสินค้าอย่างน้อย 1 รายการ',
            'items.max' => 'สินค้าได้ไม่เกิน 100 รายการต่อคำขอ',
            'items.*.array' => 'รูปแบบรายการสินค้าไม่ถูกต้อง',
            'items.*.product_id.integer' => 'รหัสสินค้าไม่ถูกต้อง',
            'items.*.product_id.required_without' => 'ทุกรายการต้องมีรหัสสินค้า (product_id) หรือ SKU',
            'items.*.sku.required_without' => 'ทุกรายการต้องมีรหัสสินค้า (product_id) หรือ SKU',
            'items.*.sku.max' => 'SKU ยาวเกิน 100 ตัวอักษร',
            'items.*.qty.required' => 'กรุณาระบุจำนวนสินค้า',
            'items.*.qty.integer' => 'จำนวนสินค้าต้องเป็นจำนวนเต็ม',
            'items.*.qty.min' => 'จำนวนสินค้าต้องอย่างน้อย 1',
            'items.*.qty.max' => 'จำนวนสินค้าได้ไม่เกิน 999 ต่อรายการ',
            'items.*.name.max' => 'ชื่อสินค้ายาวเกิน 255 ตัวอักษร',
            'customer_phone.max' => 'เบอร์มือถือลูกค้าไม่ถูกต้อง (ตัวอย่าง 0812345678)',
            'note.max' => 'หมายเหตุยาวเกิน 500 ตัวอักษร',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray(), $validator->errors()->first());
        }

        try {
            [$deliveryRequest, , $pushSent] = $this->service->create($terminal, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => $this->presenter->created($deliveryRequest, $terminal->shop, $pushSent),
            ], 201);
        } catch (PosDeliveryException $e) {
            return $e->toJsonResponse();
        } catch (\Throwable $e) {
            return $this->serverError('PosDelivery: create failed', $terminal, $e, 'สร้างคำขอส่งไรเดอร์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * สถานะคำขอ (pending → paid → สถานะไรเดอร์ → ส่งมอบ)
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $terminal = $this->terminal($request);

        try {
            $deliveryRequest = $this->service->showForTerminal($terminal, $id);

            return response()->json([
                'success' => true,
                'data' => $this->presenter->forTerminal($deliveryRequest),
            ]);
        } catch (PosDeliveryException $e) {
            return $e->toJsonResponse();
        } catch (\Throwable $e) {
            return $this->serverError('PosDelivery: show failed', $terminal, $e, 'ดึงสถานะคำขอไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * ยกเลิกคำขอที่ยังรอจ่าย
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $terminal = $this->terminal($request);

        try {
            $deliveryRequest = $this->service->cancel($terminal, $id);

            return response()->json([
                'success' => true,
                'message' => 'ยกเลิกคำขอแล้ว',
                'data' => $this->presenter->forTerminal($deliveryRequest),
            ]);
        } catch (PosDeliveryException $e) {
            return $e->toJsonResponse();
        } catch (\Throwable $e) {
            return $this->serverError('PosDelivery: cancel failed', $terminal, $e, 'ยกเลิกคำขอไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * เครื่อง POS ที่ middleware pos.terminal ยืนยันแล้ว
     */
    private function terminal(Request $request): PosTerminal
    {
        /** @var PosTerminal $terminal */
        $terminal = $request->attributes->get(PosTerminalAuthenticator::REQUEST_ATTRIBUTE);

        return $terminal;
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

    private function serverError(string $logMessage, PosTerminal $terminal, \Throwable $e, string $message): JsonResponse
    {
        Log::error($logMessage, ['terminal_id' => $terminal->id] + PosDeliveryRequestService::safeError($e));

        return response()->json([
            'success' => false,
            'code' => 'SERVER_ERROR',
            'message' => $message,
        ], 500);
    }
}
