<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\DeliveryTrackingException;
use App\Http\Controllers\Controller;
use App\Services\DeliveryTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ผู้ซื้อติดตามไรเดอร์ของออเดอร์ตัวเอง + แชร์ตำแหน่งตัวเองให้ไรเดอร์ (auth:sanctum)
 *
 * GET  /api/v1/orders/{source}/{id}/rider-location    source = shop | fresh-market
 * POST /api/v1/orders/{source}/{id}/share-location    {share: bool, latitude?, longitude?}
 *
 * ออเดอร์ของคนอื่น = 404 ORDER_NOT_FOUND (ไม่บอกว่ามีออเดอร์นี้อยู่)
 * ตำแหน่งไรเดอร์มีเฉพาะตอนงานวิ่งอยู่ — ส่งถึง/ยกเลิก/ส่งไม่สำเร็จแล้ว rider_location = null เสมอ
 */
class DeliveryTrackingApiController extends Controller
{
    public function __construct(private readonly DeliveryTrackingService $tracking) {}

    /**
     * GET /orders/{source}/{id}/rider-location
     */
    public function riderLocation(Request $request, string $source, int $id): JsonResponse
    {
        return $this->handle(function () use ($request, $source, $id) {
            $order = $this->tracking->findBuyerOrder($request->user(), $source, $id);

            return $this->ok($this->tracking->riderLocation($order, $source), 'ดึงตำแหน่งไรเดอร์สำเร็จ');
        });
    }

    /**
     * POST /orders/{source}/{id}/share-location — เปิด/ปิดแชร์ตำแหน่งตัวเองให้ไรเดอร์
     *
     * แอปที่เปิดแชร์ไว้ส่งซ้ำทุก ~30 วินาที (ไรเดอร์เห็นเฉพาะตำแหน่งที่อายุไม่เกิน 2 นาที)
     */
    public function shareLocation(Request $request, string $source, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'share' => 'required|boolean',
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
        ], [
            'share.required' => 'กรุณาระบุว่าจะเปิดหรือปิดการแชร์ตำแหน่ง',
            'latitude.required_with' => 'กรุณาส่งพิกัดให้ครบทั้งละติจูดและลองจิจูด',
            'longitude.required_with' => 'กรุณาส่งพิกัดให้ครบทั้งละติจูดและลองจิจูด',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        return $this->handle(function () use ($request, $source, $id, $data) {
            $order = $this->tracking->findBuyerOrder($request->user(), $source, $id);
            $result = $this->tracking->setSharing(
                $order,
                filter_var($data['share'], FILTER_VALIDATE_BOOLEAN),
                $data['latitude'] ?? null,
                $data['longitude'] ?? null
            );

            return $this->ok($result, $result['sharing']
                ? 'กำลังแชร์ตำแหน่งให้ไรเดอร์ (หยุดอัตโนมัติเมื่อส่งของเสร็จ)'
                : 'หยุดแชร์ตำแหน่งแล้ว');
        });
    }

    protected function handle(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (DeliveryTrackingException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->errorCode(),
            ], $e->httpStatus());
        } catch (\Throwable $e) {
            Log::error('DeliveryTrackingAPI: failed', [
                'user_id' => auth()->id(),
                'path' => request()->path(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
                'code' => 'SERVER_ERROR',
            ], 500);
        }
    }

    protected function ok(mixed $data, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
