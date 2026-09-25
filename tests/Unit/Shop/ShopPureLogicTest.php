<?php

namespace Tests\Unit\Shop;

use App\Services\DeliveryFeeCalculator;
use App\Services\ShippingService;
use App\Services\Shop\CouponService;
use App\Services\Shop\ShopCartService;
use App\Support\Shop\PaymentMethod;
use PHPUnit\Framework\TestCase;

/**
 * ตรรกะล้วนของระบบร้านค้า (ไม่แตะฐานข้อมูล — รันในเครื่องได้)
 *
 * - G7/SELLER-16: normalize วิธีชำระเงินให้ตรง enum ของ orders.payment_method
 * - SHOP-09: กระจายส่วนลดคูปองลงรายการสินค้าให้ผลรวมตรงเป๊ะ
 * - SHOP-22: ตัวเลือกสินค้าเดียวกันแต่ลำดับ key ต่างกัน → ถือเป็นแถวเดียวในตะกร้า
 */
class ShopPureLogicTest extends TestCase
{
    public function test_payment_method_aliases_are_normalized(): void
    {
        $this->assertSame('cod', PaymentMethod::normalize('cash_on_delivery'));
        $this->assertSame('cod', PaymentMethod::normalize(' COD '));
        $this->assertSame('bank_transfer', PaymentMethod::normalize('bank'));
        $this->assertSame('credit_card', PaymentMethod::normalize('card'));
        $this->assertSame('wallet', PaymentMethod::normalize('wallet'));
        $this->assertNull(PaymentMethod::normalize(''));
        $this->assertNull(PaymentMethod::normalize(null));
        $this->assertSame('bitcoin', PaymentMethod::normalize('Bitcoin'), 'ค่าที่ไม่รู้จักคืนตามเดิมให้ validator ปฏิเสธ');
    }

    public function test_only_enum_values_are_accepted_for_orders(): void
    {
        foreach (['wallet', 'promptpay', 'bank_transfer', 'credit_card', 'cod', 'paysolutions', 'coins', 'cash_on_delivery', 'bank', 'card'] as $method) {
            $this->assertTrue(PaymentMethod::isOrderValue($method), $method);
        }

        $this->assertFalse(PaymentMethod::isOrderValue('bitcoin'));
        $this->assertFalse(PaymentMethod::isOrderValue(null));
    }

    public function test_provider_key_maps_cod_to_payment_service_provider(): void
    {
        $this->assertSame('cash_on_delivery', PaymentMethod::providerKey('cod'));
        $this->assertSame('cash_on_delivery', PaymentMethod::providerKey('cash_on_delivery'));
        $this->assertSame('promptpay', PaymentMethod::providerKey('promptpay'));
        $this->assertSame('bank_transfer', PaymentMethod::providerKey('bank'));
    }

    public function test_payment_method_labels_are_thai(): void
    {
        $this->assertSame('เก็บเงินปลายทาง', PaymentMethod::labelTh('cash_on_delivery'));
        $this->assertSame('กระเป๋าเงิน', PaymentMethod::labelTh('wallet'));
        $this->assertSame('ไม่ระบุ', PaymentMethod::labelTh(null));
    }

    public function test_coupon_discount_allocation_sums_exactly(): void
    {
        $service = new CouponService;

        $result = $service->allocate(10.0, [
            ['line_key' => 1, 'line_total' => 33.33],
            ['line_key' => 2, 'line_total' => 33.33],
            ['line_key' => 3, 'line_total' => 33.34],
        ]);

        $this->assertEqualsWithDelta(10.0, array_sum($result), 0.0001);
        $this->assertSame([1, 2, 3], array_keys($result));
        foreach ($result as $share) {
            $this->assertGreaterThanOrEqual(0, $share);
        }
    }

    public function test_coupon_allocation_never_exceeds_line_total(): void
    {
        $service = new CouponService;

        $result = $service->allocate(50.0, [
            ['line_key' => 'a', 'line_total' => 20.0],
            ['line_key' => 'b', 'line_total' => 30.0],
        ]);

        $this->assertSame(20.0, $result['a']);
        $this->assertSame(30.0, $result['b']);
    }

    public function test_zero_discount_allocates_nothing(): void
    {
        $result = (new CouponService)->allocate(0, [['line_key' => 7, 'line_total' => 99.0]]);

        $this->assertSame([7 => 0.0], $result);
    }

    public function test_cart_attributes_are_canonical_regardless_of_key_order(): void
    {
        $service = new ShopCartService(new ShippingService, new DeliveryFeeCalculator([]), new CouponService);

        $a = $service->canonicalAttributes(['สี' => 'แดง', 'ไซซ์' => 'L']);
        $b = $service->canonicalAttributes(['ไซซ์' => 'L', 'สี' => 'แดง']);
        $c = $service->canonicalAttributes('{"ไซซ์":"L","สี":"แดง"}');

        $this->assertSame($a, $b);
        $this->assertSame($a, $c);
        $this->assertNull($service->canonicalAttributes([]));
        $this->assertNull($service->canonicalAttributes(null));
        $this->assertNotSame($a, $service->canonicalAttributes(['สี' => 'ดำ', 'ไซซ์' => 'L']));
    }
}
