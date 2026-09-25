<?php

namespace Tests\Feature\Shop;

use App\Models\Notification as InAppNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * API ฝั่งร้านค้า (ต้องใช้ MySQL — รันบน CI)
 *
 * ครอบคลุม gap จาก audit 2026-09-25:
 *   - SHOP-13  API ร้านใช้งานได้จริง (ไม่ select คอลัมน์ที่ไม่มี)
 *   - SELLER-14 IDOR: ร้านอื่นเห็น/แก้ออเดอร์ที่ไม่มีสินค้าของตัวเองไม่ได้ · ส่งของได้เฉพาะออเดอร์ที่จ่ายแล้ว
 *               · ออเดอร์หลายร้านไม่เขียนทับสถานะ/เลขพัสดุของกันและกัน
 *   - CC-08    ผู้ซื้อได้แจ้งเตือนเมื่อร้านยืนยัน/จัดส่ง
 */
#[Group('shop')]
class SellerOrderApiTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();
    }

    public function test_seller_cannot_see_or_act_on_another_stores_order(): void
    {
        [$sellerA, $storeA] = $this->makeSellerWithStore();
        [$sellerB] = $this->makeSellerWithStore();
        $productA = $this->makeProduct($sellerA, $storeA);
        $buyer = $this->makeBuyer();

        $order = $this->makeOrder($buyer, [['product' => $productA, 'qty' => 1]], [
            'store_id' => $storeA->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($sellerB);

        $this->getJson('/api/v1/seller/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data.orders');

        $this->getJson("/api/v1/seller/orders/{$order->id}")
            ->assertNotFound()
            ->assertJsonPath('code', 'ORDER_NOT_FOUND');

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", ['action' => 'confirm'])
            ->assertNotFound();

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", ['action' => 'cancel', 'reason' => 'ลองยกเลิกของคนอื่น'])
            ->assertNotFound();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('pending', OrderItem::where('order_id', $order->id)->value('status'));
    }

    public function test_non_seller_gets_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/seller/orders')
            ->assertForbidden()
            ->assertJsonPath('code', 'NOT_A_SELLER');

        $this->getJson('/api/v1/seller/summary')->assertForbidden();
    }

    public function test_seller_confirms_and_ships_paid_order_and_buyer_is_notified(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store);
        $buyer = $this->makeBuyer();
        $provider = ShippingProvider::create([
            'code' => 'kerry-test',
            'name' => 'Kerry Express',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 2]], [
            'store_id' => $store->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($seller);

        $this->getJson('/api/v1/seller/orders?status=to_confirm')
            ->assertOk()
            ->assertJsonCount(1, 'data.orders')
            ->assertJsonPath('data.orders.0.id', $order->id);

        $this->getJson("/api/v1/seller/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.order.id', $order->id)
            ->assertJsonPath('data.allowed_actions', ['confirm', 'ship', 'cancel']);

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", ['action' => 'confirm'])
            ->assertOk()
            ->assertJsonPath('data.order.status', 'processing');

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", [
            'action' => 'ship',
            'tracking_number' => 'KER123456789',
            'shipping_provider_id' => $provider->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.order.status', 'shipped')
            ->assertJsonPath('data.tracking.tracking_number', 'KER123456789');

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('KER123456789', $order->tracking_number);
        $this->assertSame('shipped', OrderItem::where('order_id', $order->id)->value('status'));

        $buyerTitles = InAppNotification::where('user_id', $buyer->id)->pluck('title')->all();
        $this->assertContains('ร้านยืนยันคำสั่งซื้อแล้ว', $buyerTitles);
        $this->assertContains('จัดส่งสินค้าแล้ว', $buyerTitles);
    }

    public function test_seller_cannot_ship_unpaid_order(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store);
        $buyer = $this->makeBuyer();

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'promptpay',
        ]);

        Sanctum::actingAs($seller);

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", [
            'action' => 'ship',
            'tracking_number' => 'TH0001',
            'provider' => 'ไปรษณีย์ไทย',
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ACTION_NOT_ALLOWED')
            ->assertJsonPath('message', 'คำสั่งซื้อนี้ยังไม่ได้ชำระเงิน รอลูกค้าชำระก่อน');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertNull($order->fresh()->tracking_number);
    }

    public function test_multi_seller_order_is_not_overwritten_by_one_seller(): void
    {
        [$sellerA, $storeA] = $this->makeSellerWithStore();
        [$sellerB, $storeB] = $this->makeSellerWithStore();
        $productA = $this->makeProduct($sellerA, $storeA);
        $productB = $this->makeProduct($sellerB, $storeB);
        $buyer = $this->makeBuyer();

        // ออเดอร์เก่าจากเว็บที่มีสินค้า 2 ร้านในออเดอร์เดียว
        $order = $this->makeOrder($buyer, [
            ['product' => $productA, 'qty' => 1],
            ['product' => $productB, 'qty' => 1],
        ], [
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($sellerA);

        // ร้าน A เห็นเฉพาะสินค้าของตัวเอง + ยกเลิกทั้งออเดอร์ไม่ได้
        $this->getJson("/api/v1/seller/orders/{$order->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_id', $productA->id)
            ->assertJsonPath('data.order.is_multi_seller', true);

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", ['action' => 'cancel', 'reason' => 'ของหมดแล้วค่ะ'])
            ->assertStatus(409);

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", [
            'action' => 'ship',
            'tracking_number' => 'A-111',
            'provider' => 'Flash',
        ])->assertOk();

        $order->refresh();
        $this->assertSame('processing', $order->status, 'ร้าน B ยังไม่ส่ง → ออเดอร์ยังไม่เป็น shipped');
        $this->assertNull($order->tracking_number, 'เลขพัสดุร้าน A ต้องไม่ทับทั้งออเดอร์');
        $this->assertSame('shipped', OrderItem::where('order_id', $order->id)->where('seller_id', $sellerA->id)->value('status'));
        $this->assertSame('pending', OrderItem::where('order_id', $order->id)->where('seller_id', $sellerB->id)->value('status'), 'สินค้าร้าน B ไม่ถูกแตะ');

        // ผู้ซื้อยกเลิกทั้งออเดอร์ไม่ได้แล้ว (ร้าน A ส่งของไปแล้ว — เดิมยกเลิกได้และได้เงินคืนเต็ม)
        $this->assertFalse($order->fresh()->canBeCancelled());
        Sanctum::actingAs($buyer);
        $this->postJson("/api/v1/orders/{$order->id}/cancel", ['reason' => 'เปลี่ยนใจ'])->assertStatus(409);
        $this->assertSame('paid', $order->fresh()->payment_status, 'ต้องไม่มีการคืนเงิน');

        Sanctum::actingAs($sellerB);
        $this->postJson("/api/v1/seller/orders/{$order->id}/action", [
            'action' => 'ship',
            'tracking_number' => 'B-222',
            'provider' => 'Kerry',
        ])->assertOk();

        $this->assertSame('shipped', $order->fresh()->status);
    }

    /**
     * ร้านที่ถูกแอดมินระงับ จัดการออเดอร์ผ่าน API แอปไม่ได้ (เดิมหน้าเว็บบล็อก แต่ API เช็คแค่ว่ามีร้าน
     * → ร้านโกงกด ship + deliver เองได้ เริ่มนับวันปล่อยเงินทั้งที่ถูกระงับ)
     */
    public function test_suspended_store_cannot_act_on_orders_through_the_app(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store);
        $buyer = $this->makeBuyer();
        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'paid_at' => now(),
        ]);

        $store->forceFill(['status' => 'suspended', 'is_active' => false])->save();
        Sanctum::actingAs($seller);

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", ['action' => 'confirm'])
            ->assertForbidden()
            ->assertJsonPath('code', 'STORE_SUSPENDED');

        $this->getJson('/api/v1/seller/orders')
            ->assertForbidden()
            ->assertJsonPath('code', 'STORE_SUSPENDED');

        $this->assertSame('pending', OrderItem::where('order_id', $order->id)->value('status'));

        // ด่านใน service ด้วย (เว็บ/ช่องทางอื่นที่เรียก service ตรง)
        try {
            app(\App\Services\Shop\SellerOrderService::class)->perform($order->fresh(), $seller, 'confirm');
            $this->fail('ร้านที่ถูกระงับต้องทำรายการไม่ได้');
        } catch (\App\Exceptions\ShopException $e) {
            $this->assertSame('STORE_SUSPENDED', $e->errorCode);
        }
    }

    public function test_seller_summary_counts_only_own_orders(): void
    {
        [$sellerA, $storeA] = $this->makeSellerWithStore();
        [$sellerB, $storeB] = $this->makeSellerWithStore();
        $productA = $this->makeProduct($sellerA, $storeA);
        $productB = $this->makeProduct($sellerB, $storeB);
        $buyer = $this->makeBuyer();

        $paid = ['status' => 'paid', 'payment_status' => 'paid', 'payment_method' => 'wallet', 'paid_at' => now()];
        $this->makeOrder($buyer, [['product' => $productA, 'qty' => 1]], $paid + ['store_id' => $storeA->id]);
        $this->makeOrder($buyer, [['product' => $productB, 'qty' => 1]], $paid + ['store_id' => $storeB->id]);
        $this->makeOrder($buyer, [['product' => $productB, 'qty' => 1]], $paid + ['store_id' => $storeB->id]);

        Sanctum::actingAs($sellerA);

        $this->getJson('/api/v1/seller/summary')
            ->assertOk()
            ->assertJsonPath('data.counts.to_confirm', 1)
            ->assertJsonPath('data.store.id', $storeA->id);
    }

    public function test_seller_requests_rider_for_cod_order_and_rider_delivery_marks_order_paid(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 120]);
        $buyer = $this->makeBuyer();
        $address = $this->makeAddress($buyer, true);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'delivery_method' => 'rider',
            'shipping_fee' => 45,
            'total_amount' => 165,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshot(),
        ]);

        Sanctum::actingAs($seller);

        $this->getJson("/api/v1/seller/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.allowed_actions', ['confirm', 'request_rider', 'cancel']);

        $this->postJson("/api/v1/seller/orders/{$order->id}/action", ['action' => 'request_rider'])
            ->assertOk()
            ->assertJsonPath('data.order.status', 'processing')
            ->assertJsonPath('data.rider.status', 'pending');

        $job = \App\Models\RiderJob::forSource($order)->firstOrFail();
        $this->assertSame('shop_delivery', $job->job_type);
        $this->assertEquals(165.0, (float) $job->cod_amount, 'ไรเดอร์เก็บเงินสดเต็มยอด');
        $this->assertEquals(45.0, (float) $job->total_fee, 'ค่างานไรเดอร์ = ค่าส่งที่ลูกค้าจ่าย');

        // เรียกไรเดอร์ซ้ำ → ไม่สร้างงานซ้ำ
        $this->postJson("/api/v1/seller/orders/{$order->id}/action", ['action' => 'request_rider'])->assertStatus(409);
        $this->assertSame(1, \App\Models\RiderJob::forSource($order)->count());

        // ไรเดอร์รับของ → ออเดอร์ "จัดส่งแล้ว"
        $job->forceFill(['status' => 'picked_up'])->save();
        $order->fresh()->onRiderJobStatusChanged($job, 'accepted');
        $this->assertSame('shipped', $order->fresh()->status);

        // ส่งถึง → ออเดอร์ส่งถึง แต่ยังไม่ "จ่ายแล้ว" (ไรเดอร์ยังถือเงินสด ยังไม่ได้นำส่งเข้าระบบ)
        $job->forceFill(['status' => 'delivered'])->save();
        $order->fresh()->onRiderJobStatusChanged($job, 'picked_up');

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertSame('pending', $order->payment_status, 'ส่งถึง ≠ ได้เงิน — ห้ามแบ่งเงินร้าน/จ่าย cashback ก่อนไรเดอร์นำส่ง');
        $this->assertNull($order->paid_at);
        $this->assertSame('delivered', OrderItem::where('order_id', $order->id)->value('status'));

        // เรียกซ้ำ (completed) → idempotent
        $job->forceFill(['status' => 'completed'])->save();
        $order->fresh()->onRiderJobStatusChanged($job, 'delivered');
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);

        // หักวอลเลตไรเดอร์สำเร็จ (RiderEarningService::settle) → ออเดอร์จ่ายแล้ว
        $job->forceFill(['cod_settled_at' => now()])->save();
        $order->fresh()->onRiderCodSettled($job);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('RIDER-COD-'.$job->id, $order->payment_reference);

        // เรียกซ้ำ → ไม่เปลี่ยนอะไร
        $paidAt = $order->paid_at;
        $order->fresh()->onRiderCodSettled($job);
        $this->assertEquals($paidAt, $order->fresh()->paid_at);
    }

    public function test_order_model_filters_by_seller(): void
    {
        [$sellerA, $storeA] = $this->makeSellerWithStore();
        [$sellerB] = $this->makeSellerWithStore();
        $productA = $this->makeProduct($sellerA, $storeA);
        $buyer = $this->makeBuyer();

        $order = $this->makeOrder($buyer, [['product' => $productA, 'qty' => 1]]);

        $this->assertTrue(Order::forSeller($sellerA->id)->whereKey($order->id)->exists());
        $this->assertFalse(Order::forSeller($sellerB->id)->whereKey($order->id)->exists());
    }
}
