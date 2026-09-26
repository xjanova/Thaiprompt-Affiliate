<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * รูปแบบคำตอบของ API หลังร้านในแอป: {success, data, message} / {success:false, code, message, errors?, data?}
 * ข้อความทั้งหมดเป็นภาษาไทย — ห้ามส่งข้อความ exception ดิบกลับไป
 */
trait SellerAppResponses
{
    /**
     * @param  array<string, mixed>  $extra  คีย์ระดับบนเพิ่มเติม (เช่น pagination / counts)
     */
    protected function ok(mixed $data, string $message = 'สำเร็จ', int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $extra), $status);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function fail(string $code, string $message, int $status, array $data = [], ?array $errors = null): JsonResponse
    {
        $body = ['success' => false, 'code' => $code, 'message' => $message];
        if ($data !== []) {
            $body['data'] = $data;
        }
        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    /**
     * ผลจาก SellerPanelGate::check() ที่ไม่ผ่าน → JSON
     *
     * @param  array{status: int, code: string, message: string, data: array<string, mixed>}  $gate
     */
    protected function denied(array $gate): JsonResponse
    {
        return $this->fail($gate['code'], $gate['message'], $gate['status'], $gate['data']);
    }

    /**
     * ตรวจข้อมูล — ผ่าน = array ค่าที่ตรวจแล้ว · ไม่ผ่าน = JsonResponse 422 (ข้อความแรกเป็นข้อความหลัก)
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return array<string, mixed>|JsonResponse
     */
    protected function validateOrFail(Request $request, array $rules, array $messages = []): array|JsonResponse
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', (string) $validator->errors()->first(), 422, [], $validator->errors()->toArray());
        }

        return $validator->validated();
    }
}
