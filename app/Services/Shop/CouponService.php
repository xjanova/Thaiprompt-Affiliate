<?php

namespace App\Services\Shop;

use App\Exceptions\ShopException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Models\UserCoupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ตรวจ/ใช้/คืนสิทธิ์คูปองของร้านค้า (SHOP-09 / G6)
 *
 * แทนโค้ดส่วนลดที่ฝังในโค้ด (FIRST10/FREE50/FREESHIP ใช้ได้ไม่จำกัด) ด้วยตาราง coupons จริง:
 * - คูปองของร้าน (coupons.store_id) ใช้ได้กับสินค้าของร้านนั้นเท่านั้น ร้านเป็นผู้ออกส่วนลด
 *   (ส่วนลดถูกหักที่ order_items.total → ระบบแบ่งเงินหักจากยอดขายของร้านตรงตามจริง)
 * - คูปองที่ไม่มีร้าน (ของแพลตฟอร์ม) ยังไม่รองรับในการสั่งซื้อสินค้า — ยังไม่มีบัญชีรับภาระส่วนลดฝั่งแพลตฟอร์ม
 * - 1 คน ใช้ได้ 1 ครั้งต่อคูปอง · เคารพ usage_limit / starts_at / expires_at / min_purchase / สินค้าที่กำหนด
 * - บันทึกการใช้ใน transaction เดียวกับการสร้างออเดอร์ (ล็อกแถวคูปอง) · ยกเลิกออเดอร์ = คืนสิทธิ์
 */
class CouponService
{
    /**
     * หาคูปองจากรหัสและตรวจว่าผู้ใช้คนนี้ใช้ได้ (ยังไม่ดูตะกร้า)
     *
     * @throws ShopException COUPON_INVALID
     */
    public function findUsable(string $code, User $user, bool $lock = false): Coupon
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen($code) > 50) {
            throw $this->invalid('โค้ดส่วนลดไม่ถูกต้อง');
        }

        $query = Coupon::where('code', $code);
        if ($lock) {
            $query->lockForUpdate();
        }
        $coupon = $query->first();

        if (! $coupon) {
            throw $this->invalid('ไม่พบโค้ดส่วนลดนี้');
        }

        if (! $coupon->isValid()) {
            throw $this->invalid('โค้ดส่วนลดนี้หมดอายุหรือถูกใช้ครบแล้ว');
        }

        if ($coupon->user_id && (int) $coupon->user_id !== (int) $user->id) {
            throw $this->invalid('โค้ดส่วนลดนี้ไม่ใช่ของคุณ');
        }

        if (! $coupon->store_id) {
            throw $this->invalid('โค้ดส่วนลดนี้ยังใช้กับการสั่งซื้อสินค้าไม่ได้');
        }

        if ($this->userHasUsed($coupon, $user)) {
            throw $this->invalid('คุณใช้โค้ดส่วนลดนี้ไปแล้ว');
        }

        return $coupon;
    }

    /**
     * คำนวณส่วนลดของคูปองกับตะกร้าที่แบ่งตามร้านแล้ว
     *
     * @param  array<int, array{key: string, store_id: ?int, store_name: string, delivery_method: string, shipping_fee: float, lines: array<int, array{line_key: string|int, product: \App\Models\Product, quantity: int, line_total: float}>}>  $groups
     * @return array{coupon_id: int, code: string, group_key: string, store_id: int, discount_type: string, product_discount: float, shipping_discount: float, total_discount: float, line_discounts: array<string|int, float>}
     *
     * @throws ShopException COUPON_INVALID
     */
    public function evaluate(Coupon $coupon, array $groups): array
    {
        $group = null;
        foreach ($groups as $candidate) {
            if ($candidate['store_id'] !== null && (int) $candidate['store_id'] === (int) $coupon->store_id) {
                $group = $candidate;
                break;
            }
        }

        if ($group === null) {
            $storeName = $coupon->store?->store_name;

            throw $this->invalid($storeName
                ? "โค้ดส่วนลดนี้ใช้ได้เฉพาะสินค้าของร้าน {$storeName}"
                : 'ไม่มีสินค้าในตะกร้าที่ใช้โค้ดส่วนลดนี้ได้');
        }

        $applicableProducts = $this->intList($coupon->applicable_products);
        $applicableCategories = $this->intList($coupon->applicable_categories);
        $excludedProducts = $this->intList($coupon->excluded_products);

        $eligible = [];
        foreach ($group['lines'] as $line) {
            $product = $line['product'];
            $productId = (int) $product->id;

            if (in_array($productId, $excludedProducts, true)) {
                continue;
            }
            if ($applicableProducts !== [] && ! in_array($productId, $applicableProducts, true)) {
                continue;
            }
            if ($applicableCategories !== [] && ! in_array((int) $product->category_id, $applicableCategories, true)) {
                continue;
            }

            $eligible[] = $line;
        }

        if ($eligible === []) {
            throw $this->invalid('ไม่มีสินค้าในตะกร้าที่ใช้โค้ดส่วนลดนี้ได้');
        }

        $eligibleSubtotal = round(array_sum(array_map(fn ($l) => (float) $l['line_total'], $eligible)), 2);
        $minPurchase = round((float) $coupon->min_purchase, 2);

        if ($eligibleSubtotal < $minPurchase) {
            throw $this->invalid('ยอดซื้อยังไม่ถึงขั้นต่ำของโค้ดส่วนลด ('.number_format($minPurchase, 2).' บาท)', [
                'min_purchase' => $minPurchase,
                'eligible_subtotal' => $eligibleSubtotal,
            ]);
        }

        $productDiscount = 0.0;
        $shippingDiscount = 0.0;

        switch ($coupon->discount_type) {
            case 'percentage':
                $productDiscount = $eligibleSubtotal * max(0.0, min(100.0, (float) $coupon->discount_value)) / 100;
                if ($coupon->max_discount !== null && (float) $coupon->max_discount > 0) {
                    $productDiscount = min($productDiscount, (float) $coupon->max_discount);
                }
                break;

            case 'fixed':
                $productDiscount = min(max(0.0, (float) $coupon->discount_value), $eligibleSubtotal);
                break;

            case 'free_shipping':
                if ($group['delivery_method'] !== 'parcel') {
                    throw $this->invalid('โค้ดส่งฟรีใช้ได้กับการส่งพัสดุเท่านั้น');
                }
                $shippingDiscount = max(0.0, (float) $group['shipping_fee']);
                if ($coupon->max_discount !== null && (float) $coupon->max_discount > 0) {
                    $shippingDiscount = min($shippingDiscount, (float) $coupon->max_discount);
                }
                break;

            default:
                throw $this->invalid('โค้ดส่วนลดนี้ใช้ไม่ได้');
        }

        $productDiscount = round(max(0.0, $productDiscount), 2);
        $shippingDiscount = round(max(0.0, $shippingDiscount), 2);

        if ($productDiscount <= 0 && $shippingDiscount <= 0) {
            throw $this->invalid('โค้ดส่วนลดนี้ไม่ได้ลดราคาสินค้าในตะกร้า');
        }

        return [
            'coupon_id' => (int) $coupon->id,
            'code' => (string) $coupon->code,
            'group_key' => (string) $group['key'],
            'store_id' => (int) $coupon->store_id,
            'discount_type' => (string) $coupon->discount_type,
            'product_discount' => $productDiscount,
            'shipping_discount' => $shippingDiscount,
            'total_discount' => round($productDiscount + $shippingDiscount, 2),
            'line_discounts' => $this->allocate($productDiscount, $eligible),
        ];
    }

    /**
     * บันทึกการใช้คูปองกับออเดอร์ (เรียกภายใน transaction ของ checkout — ล็อกแถวคูปองก่อน)
     *
     * @throws ShopException COUPON_INVALID เมื่อสิทธิ์หมดระหว่างรอ
     */
    public function recordUsage(int $couponId, User $user, Order $order, float $discount): void
    {
        $coupon = Coupon::whereKey($couponId)->lockForUpdate()->first();

        if (! $coupon || ! $coupon->isValid()) {
            throw $this->invalid('โค้ดส่วนลดนี้หมดอายุหรือถูกใช้ครบแล้ว');
        }

        if ($this->userHasUsed($coupon, $user)) {
            throw $this->invalid('คุณใช้โค้ดส่วนลดนี้ไปแล้ว');
        }

        DB::table('coupon_usages')->insert([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'discount_amount' => round($discount, 2),
            'order_total' => round((float) $order->total_amount, 2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Coupon::whereKey($coupon->id)->update(['used_count' => DB::raw('used_count + 1')]);

        UserCoupon::where('user_id', $user->id)
            ->where('coupon_id', $coupon->id)
            ->update(['is_used' => true, 'used_at' => now()]);
    }

    /**
     * คืนสิทธิ์คูปองของออเดอร์ที่ถูกยกเลิก (idempotent — ลบแถวการใช้แล้วจึงคืนจำนวน)
     */
    public function releaseForOrder(Order $order): void
    {
        try {
            $usages = DB::table('coupon_usages')->where('order_id', $order->id)->get();

            foreach ($usages as $usage) {
                $deleted = DB::table('coupon_usages')->where('id', $usage->id)->delete();
                if ($deleted === 0) {
                    continue;
                }

                Coupon::withTrashed()
                    ->whereKey($usage->coupon_id)
                    ->where('used_count', '>', 0)
                    ->update(['used_count' => DB::raw('used_count - 1')]);

                $stillUsed = DB::table('coupon_usages')
                    ->where('coupon_id', $usage->coupon_id)
                    ->where('user_id', $usage->user_id)
                    ->exists();

                if (! $stillUsed) {
                    UserCoupon::where('user_id', $usage->user_id)
                        ->where('coupon_id', $usage->coupon_id)
                        ->update(['is_used' => false, 'used_at' => null]);
                }
            }
        } catch (\Throwable $e) {
            // คืนสิทธิ์คูปองไม่ได้ต้องไม่ทำให้การยกเลิก/คืนเงินล้ม
            Log::warning('CouponService: release usage failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * ผู้ใช้เคยใช้คูปองนี้กับออเดอร์ที่ยังไม่ถูกยกเลิกแล้วหรือยัง
     */
    public function userHasUsed(Coupon $coupon, User $user): bool
    {
        return DB::table('coupon_usages')
            ->where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * กระจายส่วนลดตามสัดส่วนยอดของแต่ละบรรทัด (บรรทัดสุดท้ายรับเศษ ให้ผลรวมตรงเป๊ะ)
     *
     * @param  array<int, array{line_key: string|int, line_total: float}>  $lines
     * @return array<string|int, float>
     */
    public function allocate(float $discount, array $lines): array
    {
        $discount = round(max(0.0, $discount), 2);
        $total = array_sum(array_map(fn ($l) => (float) $l['line_total'], $lines));
        $result = [];

        if ($discount <= 0 || $total <= 0) {
            foreach ($lines as $line) {
                $result[$line['line_key']] = 0.0;
            }

            return $result;
        }

        $remaining = $discount;
        $count = count($lines);
        foreach (array_values($lines) as $i => $line) {
            if ($i === $count - 1) {
                $share = $remaining;
            } else {
                $share = round($discount * ((float) $line['line_total'] / $total), 2);
                $share = min($share, $remaining);
            }

            $share = round(min($share, (float) $line['line_total']), 2);
            $result[$line['line_key']] = $share;
            $remaining = round($remaining - $share, 2);
        }

        return $result;
    }

    /**
     * @return array<int>
     */
    private function intList($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_filter($value, 'is_numeric'))));
    }

    private function invalid(string $message, array $context = []): ShopException
    {
        return ShopException::make(ShopException::COUPON_INVALID, $message, 422, $context);
    }
}
