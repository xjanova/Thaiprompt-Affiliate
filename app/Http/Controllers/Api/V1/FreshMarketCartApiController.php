<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\FreshMarketCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ตะกร้าตลาดสด (API แอป) — /api/v1/fresh-market/cart*
 *
 * ทุกคำสั่งที่แก้ตะกร้าตอบ "ตะกร้าทั้งใบ" (CartDTO) ให้แอปแทน state ทั้งก้อน
 * ราคาในตะกร้าคำนวณใหม่ฝั่งเซิร์ฟเวอร์ทุกครั้ง — แอปห้ามส่งราคามาเอง
 *
 * ชำระเงิน: POST /api/v1/fresh-market/orders {seller_id, delivery_type, payment_method, ...} (FreshMarketApiController::storeOrder)
 */
class FreshMarketCartApiController extends FreshMarketApiController
{
    protected function cart(): FreshMarketCartService
    {
        return app(FreshMarketCartService::class);
    }

    /**
     * GET /api/v1/fresh-market/cart?seller_id=
     */
    public function show(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'seller_id' => 'nullable|integer|min:1',
        ]);

        return $this->ok($this->cart()->cartFor($request->user(), isset($data['seller_id']) ? (int) $data['seller_id'] : null), 'ดึงตะกร้าสำเร็จ');
    }

    /**
     * POST /api/v1/fresh-market/cart/items
     * body: listing_id, quantity (1-999, ค่าเริ่มต้น 1), option_ids[] (id ตัวเลือก), note?
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'listing_id' => 'required|integer|min:1',
            'quantity' => 'nullable|integer|min:1|max:999',
            'option_ids' => 'nullable|array|max:300',
            'option_ids.*' => 'integer|min:1',
            'note' => 'nullable|string|max:255',
        ], [
            'listing_id.required' => 'กรุณาเลือกสินค้า',
            'quantity.min' => 'จำนวนต้องอย่างน้อย 1',
            'quantity.max' => 'จำนวนต่อรายการสูงสุด 999',
        ]);

        return $this->handle(function () use ($request, $data) {
            $item = $this->cart()->addItem(
                $request->user(),
                (int) $data['listing_id'],
                (int) ($data['quantity'] ?? 1),
                $data['option_ids'] ?? [],
                $data['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'เพิ่มลงตะกร้าแล้ว',
                'data' => $this->cart()->cartFor($request->user()),
                'item_id' => (int) $item->id,
            ], 201);
        });
    }

    /**
     * PUT /api/v1/fresh-market/cart/items/{id}
     * body: quantity? (0 = ลบ), option_ids[]?, note?
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'quantity' => 'sometimes|nullable|integer|min:0|max:999',
            'option_ids' => 'sometimes|nullable|array|max:300',
            'option_ids.*' => 'integer|min:1',
            'note' => 'sometimes|nullable|string|max:255',
        ]);

        return $this->handle(function () use ($request, $id, $data) {
            $this->cart()->updateItem($request->user(), $id, $data);

            return $this->ok($this->cart()->cartFor($request->user()), 'อัปเดตตะกร้าแล้ว');
        });
    }

    /**
     * DELETE /api/v1/fresh-market/cart/items/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->handle(function () use ($request, $id) {
            $this->cart()->removeItem($request->user(), $id);

            return $this->ok($this->cart()->cartFor($request->user()), 'ลบออกจากตะกร้าแล้ว');
        });
    }

    /**
     * DELETE /api/v1/fresh-market/cart?seller_id= (ไม่ระบุร้าน = ล้างทั้งตะกร้า)
     */
    public function clear(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'seller_id' => 'nullable|integer|min:1',
        ]);

        $this->cart()->clear($request->user(), isset($data['seller_id']) ? (int) $data['seller_id'] : null);

        return $this->ok($this->cart()->cartFor($request->user()), 'ล้างตะกร้าแล้ว');
    }

    /**
     * GET /api/v1/fresh-market/cart/quote?seller_id&latitude&longitude
     * ค่าส่งไรเดอร์ + ยอดรวมของตะกร้าร้านเดียว (แสดงก่อนกดยืนยัน)
     */
    public function quote(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'seller_id' => 'required|integer|min:1',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        return $this->handle(function () use ($request, $data) {
            $quote = $this->cart()->quote($request->user(), (int) $data['seller_id'], (float) $data['latitude'], (float) $data['longitude']);

            if (! $quote['available']) {
                return response()->json([
                    'success' => false,
                    'message' => $quote['message'],
                    'code' => $quote['code'],
                    'data' => $quote,
                ], 422);
            }

            return $this->ok($quote, 'คำนวณค่าส่งสำเร็จ');
        });
    }
}
