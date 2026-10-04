<?php

namespace App\Services\Shop;

use App\Models\CartItem;
use App\Models\PosDeliveryRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * แปลงรายการในคำขอจาก POS (pos_delivery_requests.items) เป็นรายการแบบเดียวกับตะกร้าแอป (CartItem)
 *
 * แบบเดียวกับ WebCartLines: สร้าง CartItem ชั่วคราว (ไม่บันทึกลงฐานข้อมูล) ให้
 * ShopCartService::buildGroups (ใบเสนอราคา) และ ShopCheckoutService::checkout (สั่งซื้อจริง) ใช้กฎชุดเดียวกัน
 * — ราคา/สต็อก/สถานะสินค้ามาจาก products ปัจจุบันเสมอ (ราคาที่ POS ส่งมาไม่ถูกใช้)
 *
 * สินค้าที่ย้ายไปร้านอื่นแล้ว = ถือว่าไม่มีสินค้า (checkout ตอบ PRODUCT_UNAVAILABLE)
 */
class PosRequestLines
{
    /**
     * รายการของคำขอ (สินค้าที่ถูกลบ/ย้ายร้าน ติดมาเป็น product = null เพื่อให้แจ้งว่าไม่พร้อมขาย)
     *
     * @param  bool  $lock  ล็อกแถวสินค้า (ใช้ตอนสั่งซื้อจริง ภายใน transaction)
     * @return Collection<int, CartItem>
     */
    public function forRequest(PosDeliveryRequest $request, bool $lock = false): Collection
    {
        $rows = collect(is_array($request->items) ? $request->items : [])
            ->filter(fn ($row) => is_array($row) && (int) ($row['product_id'] ?? 0) > 0 && (int) ($row['qty'] ?? 0) > 0)
            ->values();

        if ($rows->isEmpty()) {
            return collect();
        }

        $productQuery = Product::withTrashed()
            ->with(['store', 'category'])
            ->whereIn('id', $rows->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->all())
            ->orderBy('id');
        if ($lock) {
            $productQuery->lockForUpdate();
        }
        $products = $productQuery->get()->keyBy('id');

        return $rows->map(function (array $row, int $index) use ($products, $request) {
            $product = $products->get((int) $row['product_id']);

            // สินค้าต้องยังเป็นของร้านเดียวกับคำขอ
            if ($product && (int) $product->store_id !== (int) $request->store_id) {
                $product = null;
            }

            $item = new CartItem;
            // id = ลำดับในคำขอ (ใช้เป็น line_key ของ checkout)
            $item->forceFill([
                'id' => $index + 1,
                'cart_id' => 0,
                'product_id' => (int) $row['product_id'],
                'quantity' => max(1, (int) $row['qty']),
                'price' => $product ? round((float) $product->price, 2) : round((float) ($row['price'] ?? 0), 2),
                'attributes' => null,
            ]);
            $item->setRelation('product', $product);

            return $item;
        })->values();
    }

    /**
     * แหล่งรายการสำหรับ ShopCheckoutService::checkout() — ล็อกแถวสินค้าแล้วคืน [รายการ, ฟังก์ชันล้างตะกร้า]
     *
     * ถูกเรียกภายใน transaction ของ checkout เท่านั้น · ไม่มีตะกร้าจริงให้ล้าง (คำขอถูกตั้ง paid โดยผู้เรียก)
     *
     * @return array{0: Collection<int, CartItem>, 1: callable(): void}
     */
    public function lockedSource(PosDeliveryRequest $request, User $user): array
    {
        $lines = $this->forRequest($request, true);

        return [
            $lines,
            function (): void {
                // ไม่มีตะกร้าให้ล้าง — PosDeliveryRequestService ตั้งคำขอเป็น paid ใน transaction เดียวกัน
            },
        ];
    }
}
