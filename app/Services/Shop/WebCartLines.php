<?php

namespace App\Services\Shop;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * แปลงตะกร้าของหน้าเว็บ (ตาราง shopping_cart) เป็นรายการแบบเดียวกับตะกร้าแอป (CartItem)
 *
 * เว็บกับแอปเก็บตะกร้าคนละตาราง แต่ต้องคิดยอด/ค่าส่งไรเดอร์/COD/คูปอง และสร้างออเดอร์ด้วยกฎชุดเดียวกัน
 * (ShopCartService::quoteLines + ShopCheckoutService::checkout) — คลาสนี้สร้าง CartItem ชั่วคราว
 * (ไม่บันทึกลงฐานข้อมูล) จากแถวใน shopping_cart ให้บริการทั้งสองตัวใช้ได้เลย
 * โดยไม่ต้องย้ายของไปมาระหว่างตะกร้าเว็บกับตะกร้าแอป
 */
class WebCartLines
{
    /**
     * รายการในตะกร้าเว็บของผู้ใช้ (สินค้าที่ถูกลบแล้วก็ติดมาด้วย เพื่อให้แจ้งว่าไม่พร้อมขาย)
     *
     * @param  bool  $lock  ล็อกแถวตะกร้า+สินค้า (ใช้ตอนสั่งซื้อจริง ภายใน transaction)
     * @return Collection<int, CartItem>
     */
    public function forUser(User $user, bool $lock = false): Collection
    {
        $query = ShoppingCart::where('user_id', $user->id)->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return collect();
        }

        $productQuery = Product::withTrashed()
            ->with(['store', 'category'])
            ->whereIn('id', $rows->pluck('product_id')->unique()->all())
            ->orderBy('id');
        if ($lock) {
            $productQuery->lockForUpdate();
        }
        $products = $productQuery->get()->keyBy('id');

        return $rows->map(function (ShoppingCart $row) use ($products) {
            $product = $products->get($row->product_id);

            $item = new CartItem;
            // id = id ของแถว shopping_cart (ใช้เป็น line_key ของคูปอง และตอนล้างตะกร้า)
            $item->forceFill([
                'id' => (int) $row->id,
                'cart_id' => 0,
                'product_id' => (int) $row->product_id,
                'quantity' => max(1, (int) $row->quantity),
                'price' => $product ? round((float) $product->price, 2) : 0,
                'attributes' => $this->attributes($row),
            ]);
            $item->setRelation('product', $product);

            return $item;
        })->values();
    }

    /**
     * แหล่งรายการสำหรับ ShopCheckoutService::checkout() — ล็อกแถวแล้วคืน [รายการ, ฟังก์ชันล้างตะกร้า]
     *
     * ถูกเรียกภายใน transaction ของ checkout เท่านั้น
     *
     * @return array{0: Collection<int, CartItem>, 1: callable(): void}
     */
    public function lockedSource(User $user): array
    {
        $lines = $this->forUser($user, true);
        $ids = $lines->pluck('id')->map(fn ($id) => (int) $id)->all();

        return [
            $lines,
            function () use ($user, $ids): void {
                // ลบเฉพาะแถวที่สั่งไปจริง (ของที่เพิ่มเข้ามาระหว่างนั้นยังอยู่ในตะกร้า)
                if ($ids !== []) {
                    ShoppingCart::where('user_id', $user->id)->whereIn('id', $ids)->delete();
                }
            },
        ];
    }

    /**
     * ตัวเลือกสินค้าของแถว (ว่าง = null)
     *
     * @return array<string, mixed>|null
     */
    private function attributes(ShoppingCart $row): ?array
    {
        $value = $row->selected_attributes;

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : null;
        }

        return is_array($value) && $value !== [] ? $value : null;
    }
}
