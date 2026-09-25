<?php

namespace Tests\Unit\Money;

use App\Services\OrderDistributionService;
use PHPUnit\Framework\TestCase;

/**
 * แยกส่วนลดออเดอร์ว่าใครออกเงิน (ฟังก์ชันบริสุทธิ์ — ไม่ใช้ DB รันบนเครื่อง dev ได้)
 *
 * บั๊กที่ล็อกไว้ (รีวิว Wave-1):
 *  - คูปองส่งฟรีของร้าน: ร้านเคยได้ค่าส่งเต็มทั้งที่ผู้ซื้อไม่ได้จ่าย → ใช้ค่าส่งสูงๆ + คูปองส่งฟรี ดึงเงินออกจากระบบได้
 *  - คูปองลดราคาของร้าน: ร้านรับภาระใน order_items.total แล้ว แต่แพลตฟอร์มยังลงรายจ่ายซ้ำอีกรอบ
 * และทุกกรณี: เงินของทุกฝ่ายรวมกันต้องเท่ายอดที่ผู้ซื้อจ่ายจริงเสมอ
 */
class OrderDiscountSplitTest extends TestCase
{
    public function test_store_free_shipping_coupon_pays_seller_only_the_shipping_the_buyer_paid(): void
    {
        // สินค้า 1 บาท ค่าส่งร้านตั้ง 10,000 + คูปองส่งฟรีของร้าน → ผู้ซื้อจ่าย 1 บาท
        $split = OrderDistributionService::splitDiscount(10000, 10000, 0, 0, 10000, 'store');

        $this->assertSame('store', $split['funded_by']);
        $this->assertSame(0.0, $split['seller_shipping_base'], 'ร้านต้องไม่ได้ค่าส่งที่ผู้ซื้อไม่ได้จ่าย');
        $this->assertSame(0.0, $split['platform_expense'], 'คูปองร้าน แพลตฟอร์มไม่ออกเงิน');
        $this->assertSame(0.0, $split['seller_deduction']);
    }

    public function test_store_product_coupon_already_absorbed_is_not_a_platform_expense(): void
    {
        // คูปองร้านลด 100 บาท ถูกหักใน order_items.total แล้ว
        $split = OrderDistributionService::splitDiscount(100, 40, 100, 100, 0, 'store');

        $this->assertSame(0.0, $split['platform_expense'], 'ห้ามลงส่วนลดของร้านเป็นรายจ่ายแพลตฟอร์มซ้ำ');
        $this->assertSame(40.0, $split['seller_shipping_base'], 'ผู้ซื้อจ่ายค่าส่งเต็ม → ร้านได้ค่าส่งเต็ม');
        $this->assertSame(0.0, $split['seller_deduction']);
    }

    public function test_store_discount_not_absorbed_in_items_is_deducted_from_the_seller(): void
    {
        // ข้อมูลเก่า: ส่วนลดของร้าน 30 บาทยังไม่ถูกหักในยอดสินค้า → หักจากรายได้ร้าน (ไม่ใช่แพลตฟอร์ม)
        $split = OrderDistributionService::splitDiscount(30, 0, 0, null, null, 'store');

        $this->assertSame(30.0, $split['product_discount']);
        $this->assertSame(30.0, $split['seller_deduction']);
        $this->assertSame(0.0, $split['platform_expense']);
    }

    public function test_platform_coupon_is_a_platform_expense_and_seller_keeps_full_price_and_shipping(): void
    {
        $split = OrderDistributionService::splitDiscount(50, 0, 0, null, null, 'platform');

        $this->assertSame('platform', $split['funded_by']);
        $this->assertSame(50.0, $split['platform_expense']);
        $this->assertSame(0.0, $split['seller_deduction']);

        $shipping = OrderDistributionService::splitDiscount(40, 40, 0, 0, 40, 'platform');
        $this->assertSame(40.0, $shipping['seller_shipping_base'], 'แพลตฟอร์มออกค่าส่งแทน ร้านยังได้ค่าส่งเต็ม');
        $this->assertSame(40.0, $shipping['platform_expense']);
    }

    public function test_legacy_order_without_split_columns_is_derived_from_item_discounts(): void
    {
        // ออเดอร์เก่า: ส่วนลดรวม 60 = สินค้า 20 (หักในรายการแล้ว) + ค่าส่ง 40
        $split = OrderDistributionService::splitDiscount(60, 40, 20, null, null, 'store');

        $this->assertSame(20.0, $split['product_discount']);
        $this->assertSame(40.0, $split['shipping_discount']);
        $this->assertSame(0.0, $split['seller_shipping_base']);
        $this->assertSame(0.0, $split['seller_deduction']);
    }

    /**
     * ผลรวมเงิน (ร้านก่อนหัก GP + ค่าส่งส่วนร้าน − หักเพิ่ม − รายจ่ายแพลตฟอร์ม) = ยอดที่ผู้ซื้อจ่าย ทุกกรณี
     */
    public function test_money_is_conserved_for_every_funding_combination(): void
    {
        $cases = [
            // [subtotal, shippingFee, productDiscount, shippingDiscount, absorbedInItems, fundedBy]
            [500, 40, 50, 0, 50, 'store'],
            [500, 40, 0, 40, 0, 'store'],
            [500, 40, 50, 40, 50, 'store'],
            [500, 40, 50, 0, 0, 'store'],
            [500, 40, 50, 0, 0, 'platform'],
            [500, 40, 0, 40, 0, 'platform'],
            [1, 10000, 0, 10000, 0, 'store'],
        ];

        foreach ($cases as [$subtotal, $shippingFee, $product, $shipping, $absorbed, $fundedBy]) {
            $split = OrderDistributionService::splitDiscount($product + $shipping, $shippingFee, $absorbed, $product, $shipping, $fundedBy);

            $buyerPaid = round($subtotal + $shippingFee - $product - $shipping, 2);
            $itemsTotal = $subtotal - $absorbed;
            $allocated = round($itemsTotal + $split['seller_shipping_base'] - $split['seller_deduction'] - $split['platform_expense'], 2);

            $this->assertSame($buyerPaid, $allocated, "เงินต้องสมดุล: {$fundedBy} สินค้า {$product} ค่าส่ง {$shipping}");
        }
    }
}
