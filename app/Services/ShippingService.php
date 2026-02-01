<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\VendorStore;
use Illuminate\Support\Collection;

/**
 * ShippingService - คำนวณค่าจัดส่งสินค้า
 *
 * รองรับ 4 รูปแบบ:
 * 1. free - จัดส่งฟรี
 * 2. flat_rate - อัตราเหมา (ค่าคงที่ต่อสินค้า)
 * 3. weight_based - คำนวณตามน้ำหนัก (กก.)
 * 4. store_default - ใช้ค่าเริ่มต้นของร้าน/ระบบ
 */
class ShippingService
{
    /**
     * ค่าจัดส่งเริ่มต้นของระบบ (บาท)
     */
    const DEFAULT_SHIPPING_FEE = 50;

    /**
     * ยอดสั่งซื้อขั้นต่ำสำหรับจัดส่งฟรี (บาท)
     */
    const DEFAULT_FREE_SHIPPING_THRESHOLD = 500;

    /**
     * คำนวณค่าจัดส่งสำหรับสินค้าชิ้นเดียว
     *
     * @param Product $product สินค้า
     * @param int $quantity จำนวน
     * @param string $zone โซนจัดส่ง (domestic/international)
     * @return float ค่าจัดส่ง
     */
    public function calculateForProduct(Product $product, int $quantity = 1, string $zone = 'domestic'): float
    {
        // สินค้าดิจิทัล ไม่มีค่าส่ง
        if ($product->is_virtual) {
            return 0;
        }

        $method = $product->shipping_method ?? 'store_default';

        return match ($method) {
            'free' => 0,
            'flat_rate' => $this->calculateFlatRate($product, $quantity),
            'weight_based' => $this->calculateWeightBased($product, $quantity, $zone),
            'store_default' => $this->calculateStoreDefault($product, $quantity),
            default => self::DEFAULT_SHIPPING_FEE,
        };
    }

    /**
     * คำนวณค่าจัดส่งสำหรับตะกร้าสินค้า (หลายรายการ)
     *
     * @param Collection $cartItems รายการสินค้าในตะกร้า (ต้องมี product relationship)
     * @param string $zone โซนจัดส่ง
     * @return array ['total' => ค่าส่งรวม, 'breakdown' => รายละเอียดแต่ละรายการ]
     */
    public function calculateForCart(Collection $cartItems, string $zone = 'domestic'): array
    {
        $totalShipping = 0;
        $breakdown = [];
        $hasPhysicalProducts = false;
        $subtotal = 0;

        foreach ($cartItems as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $quantity = $item->quantity ?? 1;
            $itemSubtotal = ($product->price ?? 0) * $quantity;
            $subtotal += $itemSubtotal;

            if ($product->is_virtual) {
                $breakdown[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'shipping_fee' => 0,
                    'method' => 'virtual',
                ];
                continue;
            }

            $hasPhysicalProducts = true;
            $fee = $this->calculateForProduct($product, $quantity, $zone);

            $breakdown[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'shipping_fee' => $fee,
                'method' => $product->shipping_method ?? 'store_default',
                'quantity' => $quantity,
            ];

            $totalShipping += $fee;
        }

        // ถ้ามีสินค้า physical แต่ยอดรวมเกิน threshold → ฟรีค่าส่ง
        if ($hasPhysicalProducts && $subtotal >= self::DEFAULT_FREE_SHIPPING_THRESHOLD) {
            // เช็คว่าทุกสินค้าใช้ store_default หรือไม่
            $allStoreDefault = collect($breakdown)->every(fn($b) =>
                in_array($b['method'], ['store_default', 'virtual'])
            );
            if ($allStoreDefault) {
                $totalShipping = 0;
            }
        }

        return [
            'total_shipping' => round($totalShipping, 2),
            'subtotal' => round($subtotal, 2),
            'has_physical' => $hasPhysicalProducts,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * คำนวณค่าจัดส่งแบบเหมาจ่าย (flat rate)
     *
     * @param Product $product
     * @param int $quantity
     * @return float
     */
    protected function calculateFlatRate(Product $product, int $quantity): float
    {
        $fee = $product->shipping_fee ?? 0;

        // ถ้ามีเงื่อนไขจัดส่งฟรีขั้นต่ำ
        if ($product->free_shipping_min_amount > 0) {
            $itemTotal = $product->price * $quantity;
            if ($itemTotal >= $product->free_shipping_min_amount) {
                return 0;
            }
        }

        return round($fee, 2);
    }

    /**
     * คำนวณค่าจัดส่งตามน้ำหนัก
     *
     * @param Product $product
     * @param int $quantity
     * @param string $zone
     * @return float
     */
    protected function calculateWeightBased(Product $product, int $quantity, string $zone): float
    {
        $weightPerUnit = $product->shipping_weight_kg ?? $product->weight ?? 0.5;
        $totalWeight = $weightPerUnit * $quantity;

        // ดึง rate จากตาราง shipping_rates
        $rate = ShippingRate::where('zone', $zone)
            ->where('is_active', true)
            ->where('min_weight_kg', '<=', $totalWeight)
            ->where(function ($q) use ($totalWeight) {
                $q->where('max_weight_kg', '>=', $totalWeight)
                  ->orWhere('max_weight_kg', 0);
            })
            ->orderBy('sort_order')
            ->first();

        if (!$rate) {
            // ใช้ rate เริ่มต้น: base 40 + 15 บาท/กก.
            $fee = 40 + ($totalWeight * 15);
        } else {
            $fee = $rate->base_fee + ($totalWeight * $rate->per_kg_fee);

            // จำกัดค่าส่งขั้นต่ำ/สูงสุด
            if ($rate->min_fee > 0 && $fee < $rate->min_fee) {
                $fee = $rate->min_fee;
            }
            if ($rate->max_fee > 0 && $fee > $rate->max_fee) {
                $fee = $rate->max_fee;
            }
        }

        return round($fee, 2);
    }

    /**
     * คำนวณค่าจัดส่งตามค่าเริ่มต้นของร้าน หรือค่า default ของระบบ
     *
     * @param Product $product
     * @param int $quantity
     * @return float
     */
    protected function calculateStoreDefault(Product $product, int $quantity): float
    {
        // ดึง store ของ seller
        $store = null;
        if ($product->store_id) {
            $store = VendorStore::find($product->store_id);
        } elseif ($product->seller_id) {
            $store = VendorStore::where('user_id', $product->seller_id)->first();
        }

        if ($store) {
            // ใช้ค่า threshold ของร้าน
            $itemTotal = $product->price * $quantity;
            if ($store->free_shipping_threshold > 0 && $itemTotal >= $store->free_shipping_threshold) {
                return 0;
            }

            // ใช้ค่าส่งของร้าน
            if ($store->shipping_fee > 0) {
                return round($store->shipping_fee, 2);
            }
        }

        // fallback: ใช้ค่าเริ่มต้นของระบบ
        return self::DEFAULT_SHIPPING_FEE;
    }

    /**
     * ตรวจสอบว่าสินค้าจัดส่งฟรีหรือไม่ (สำหรับแสดงบน storefront)
     *
     * @param Product $product
     * @return bool
     */
    public function isProductFreeShipping(Product $product): bool
    {
        if ($product->is_virtual) {
            return true;
        }

        $method = $product->shipping_method ?? 'store_default';

        if ($method === 'free') {
            return true;
        }

        if ($method === 'store_default') {
            return $product->price >= self::DEFAULT_FREE_SHIPPING_THRESHOLD;
        }

        return false;
    }
}
