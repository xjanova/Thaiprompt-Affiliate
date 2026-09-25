<?php

namespace Tests\Feature\FreshMarket;

use App\Models\FreshMarketOrder;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * ตารางเปลี่ยนสถานะออเดอร์ตลาดสด (ไม่ใช้ DB — รันบนเครื่อง dev ได้)
 *
 * ล็อกกฎที่เคยพังจนเงินรั่ว/ออเดอร์ค้าง:
 * - ผู้ซื้อยกเลิกได้เฉพาะ pending (หลังร้านรับ = ต้องให้ร้าน/แอดมินยกเลิก)
 * - ยืนยันรับของได้ตอน delivered และ ready เฉพาะนัดรับ (แบบไรเดอร์ต้องรอส่งถึง)
 * - ผู้ขายยืนยันรับแทนผู้ซื้อไม่ได้ / ปิดออเดอร์ได้เฉพาะแอดมิน-ระบบ
 * - ส่งมอบ (handover) เฉพาะนัดรับ
 * - route ที่หน้าเว็บ/แอปเรียกต้องมีอยู่จริง
 */
class FreshMarketOrderStateMachineTest extends TestCase
{
    protected function order(string $status, string $deliveryType = 'pickup'): FreshMarketOrder
    {
        return new FreshMarketOrder([
            'order_status' => $status,
            'delivery_type' => $deliveryType,
        ]);
    }

    public function test_buyer_can_cancel_only_pending_orders(): void
    {
        $this->assertTrue($this->order('pending')->canTransition('cancel', 'buyer'));

        foreach (['accepted', 'preparing', 'ready', 'delivering', 'delivered', 'completed', 'cancelled'] as $status) {
            $this->assertFalse($this->order($status)->canTransition('cancel', 'buyer'), "buyer cancel from {$status}");
        }
    }

    public function test_seller_cancel_stops_once_goods_leave_the_shop(): void
    {
        foreach (['pending', 'accepted', 'preparing', 'ready'] as $status) {
            $this->assertTrue($this->order($status, 'rider')->canTransition('cancel', 'seller'), "seller cancel from {$status}");
        }

        foreach (['delivering', 'delivered', 'completed', 'delivery_failed'] as $status) {
            $this->assertFalse($this->order($status, 'rider')->canTransition('cancel', 'seller'), "seller cancel from {$status}");
        }
    }

    public function test_confirm_receipt_rules(): void
    {
        $this->assertTrue($this->order('delivered', 'rider')->canTransition('confirm', 'buyer'));
        $this->assertTrue($this->order('delivered', 'pickup')->canTransition('confirm', 'buyer'));
        $this->assertTrue($this->order('ready', 'pickup')->canTransition('confirm', 'buyer'));

        // แบบไรเดอร์ยืนยันตอน ready ไม่ได้ (ของยังไม่ถึงมือ)
        $this->assertFalse($this->order('ready', 'rider')->canTransition('confirm', 'buyer'));
        $this->assertFalse($this->order('delivering', 'rider')->canTransition('confirm', 'buyer'));

        // ผู้ขายยืนยันแทนผู้ซื้อไม่ได้
        $this->assertFalse($this->order('delivered')->canTransition('confirm', 'seller'));
        $this->assertFalse($this->order('delivered')->canTransition('complete', 'seller'));

        // ระบบปิดได้เฉพาะ delivered
        $this->assertTrue($this->order('delivered')->canTransition('complete', 'system'));
        $this->assertFalse($this->order('ready')->canTransition('complete', 'system'));
    }

    /**
     * COD นัดรับ: ผู้ซื้อปิดออเดอร์เองตั้งแต่ ready ไม่ได้ — ร้านต้องกดส่งมอบ (= ได้รับเงินสดแล้ว) ก่อน
     * (เดิมผู้ซื้อกดยืนยันได้ทันที → ร้านโดนหัก GP + ผู้ซื้อได้ cashback โดยไม่ต้องมารับ/จ่าย)
     */
    public function test_cod_pickup_requires_seller_handover_before_buyer_confirm(): void
    {
        $codReady = new FreshMarketOrder(['order_status' => 'ready', 'delivery_type' => 'pickup', 'payment_method' => 'cod']);
        $this->assertFalse($codReady->canTransition('confirm', 'buyer'));
        $this->assertTrue($codReady->canTransition('handover', 'seller'));
        $this->assertSame([], $codReady->allowedActions('buyer'));

        $codDelivered = new FreshMarketOrder(['order_status' => 'delivered', 'delivery_type' => 'pickup', 'payment_method' => 'cod']);
        $this->assertTrue($codDelivered->canTransition('confirm', 'buyer'));

        // จ่ายผ่าน wallet แล้ว → ยืนยันรับของได้ตั้งแต่ ready เหมือนเดิม
        $walletReady = new FreshMarketOrder(['order_status' => 'ready', 'delivery_type' => 'pickup', 'payment_method' => 'wallet']);
        $this->assertTrue($walletReady->canTransition('confirm', 'buyer'));
    }

    /**
     * สร้างงานไรเดอร์ได้เฉพาะออเดอร์ส่งด้วยไรเดอร์ที่ร้านรับแล้วและของยังไม่ออกจากร้าน (หรือส่งไม่สำเร็จ)
     */
    public function test_rider_can_dispatch_only_for_active_rider_orders(): void
    {
        foreach (['accepted', 'preparing', 'ready', 'delivery_failed'] as $status) {
            $this->assertTrue($this->order($status, 'rider')->riderCanDispatch(), "dispatch from {$status}");
        }

        foreach (['pending', 'delivering', 'delivered', 'completed', 'cancelled'] as $status) {
            $this->assertFalse($this->order($status, 'rider')->riderCanDispatch(), "dispatch from {$status}");
        }

        $this->assertFalse($this->order('ready', 'pickup')->riderCanDispatch(), 'นัดรับไม่ใช้ไรเดอร์');
    }

    public function test_seller_step_sequence_and_handover_only_for_pickup(): void
    {
        $this->assertTrue($this->order('pending')->canTransition('accept', 'seller'));
        $this->assertTrue($this->order('accepted')->canTransition('prepare', 'seller'));
        $this->assertTrue($this->order('accepted')->canTransition('ready', 'seller'));
        $this->assertTrue($this->order('preparing')->canTransition('ready', 'seller'));
        $this->assertFalse($this->order('pending')->canTransition('ready', 'seller'));

        $this->assertTrue($this->order('ready', 'pickup')->canTransition('handover', 'seller'));
        $this->assertFalse($this->order('ready', 'rider')->canTransition('handover', 'seller'));

        // ผู้ซื้อทำขั้นตอนฝั่งร้านไม่ได้
        $this->assertFalse($this->order('pending')->canTransition('accept', 'buyer'));
    }

    public function test_allowed_actions_lists_buttons_per_role(): void
    {
        $this->assertSame(['accept', 'cancel'], $this->order('pending')->allowedActions('seller'));
        $this->assertSame(['cancel'], $this->order('pending')->allowedActions('buyer'));
        $this->assertSame(['confirm'], $this->order('ready', 'pickup')->allowedActions('buyer'));
        $this->assertSame([], $this->order('completed')->allowedActions('buyer'));
    }

    public function test_legacy_status_names_are_normalized(): void
    {
        $this->assertSame('accepted', FreshMarketOrder::normalizeStatus('confirmed'));
        $this->assertSame('ready', FreshMarketOrder::normalizeStatus('ready_for_pickup'));
        $this->assertNull(FreshMarketOrder::normalizeStatus('all'));
        $this->assertNull(FreshMarketOrder::normalizeStatus('hacked'));
    }

    public function test_cod_amount_only_for_unpaid_cod(): void
    {
        $cod = new FreshMarketOrder([
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'total_amount' => 100,
            'delivery_fee' => 30,
        ]);
        $this->assertSame(130.0, $cod->riderCodAmount());

        $wallet = new FreshMarketOrder([
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'total_amount' => 100,
            'delivery_fee' => 30,
        ]);
        $this->assertSame(0.0, $wallet->riderCodAmount());
    }

    public function test_fresh_market_routes_are_registered(): void
    {
        foreach ([
            'taladsod.orders.cancel',
            'taladsod.seller.orders',
            'taladsod.seller.orders.show',
            'taladsod.seller.orders.status',
            'taladsod.seller.profile',
            'taladsod.seller.profile.update',
            'taladsod.seller.subscribe',
            'taladsod.api.delivery-quote',
            'admin.fresh-market.categories.toggle',
            'admin.fresh-market.orders.cancel',
            'admin.fresh-market.orders.complete',
            'admin.fresh-market.orders.redispatch',
        ] as $name) {
            $this->assertTrue(Route::has($name), "route {$name} ต้องมีอยู่");
        }

        // /seller/orders ต้องไม่ถูกจับเป็นโปรไฟล์ร้าน /seller/{id}
        $route = Route::getRoutes()->match(\Illuminate\Http\Request::create('/taladsod/seller/orders', 'GET'));
        $this->assertSame('taladsod.seller.orders', $route->getName());
    }
}
