<?php

namespace Tests\Feature\Shop;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * checkout ของแอปมือถือ (ต้องใช้ MySQL — รันบน CI)
 *
 * ครอบคลุม gap จาก audit 2026-09-25:
 *   - SHOP-01/G6  สร้างออเดอร์ได้จริง: order_items มีคอลัมน์ครบ + snapshot GP จาก PricingEngine
 *   - SHOP-02     จ่ายด้วยกระเป๋าเงิน (deductForService) ครั้งเดียว + ยอดไม่พอได้ข้อความไทย
 *   - SHOP-03/21  ตะกร้าใน DB เป็นแหล่งเดียว · ตัดสต็อกหลังล็อก · ล้างตะกร้าหลังสั่ง
 *   - SHOP-06/CC-05 ยกเลิกออเดอร์ที่ยังไม่จ่ายไม่คืนเงิน/ไม่คืนสต็อก · ยกเลิกที่จ่ายแล้วคืนเงินครั้งเดียว
 *   - SHOP-09     คูปองตรวจกับตาราง coupons จริง ใช้ได้คนละครั้ง
 *   - SHOP-19/SELLER-20 สินค้าที่ถูกบล็อกสั่งไม่ได้
 *   - COD ใช้ได้เฉพาะส่งด้วยไรเดอร์ → ออเดอร์ค้างจ่าย (pending) + จองสต็อก
 */
#[Group('shop')]
class MobileCheckoutTest extends TestCase
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

        $this->setGpRate(10);
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.max_cod_amount', '2000', 'float', 'rider');
    }

    // =====================================================
    // wallet
    // =====================================================

    public function test_wallet_checkout_creates_paid_order_with_gp_snapshot(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer);

        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.subtotal', 200);

        $response = $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.orders.0.payment_status', 'paid')
            ->assertJsonPath('data.orders.0.status', 'paid');

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame((int) $store->id, (int) $order->store_id);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->stock_deducted_at);
        $this->assertEquals(200.0, (float) $order->subtotal);
        $this->assertEquals(0.0, (float) $order->shipping_fee);
        $this->assertEquals(200.0, (float) $order->total_amount);
        $this->assertSame('ผู้รับ ทดสอบ', $order->shipping_address_snapshot['recipient_name']);

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame((int) $seller->id, (int) $item->seller_id);
        $this->assertSame($product->name, $item->product_name);
        $this->assertSame($product->sku, $item->product_sku);
        $this->assertEquals(100.0, (float) $item->unit_price);
        $this->assertSame(2, (int) $item->quantity);
        $this->assertEquals(200.0, (float) $item->subtotal);
        $this->assertEquals(200.0, (float) $item->total);
        $this->assertEquals(10.0, (float) $item->commission_rate, 'GP rate ต้องมาจาก PricingEngine');
        $this->assertEquals(20.0, (float) $item->commission_amount);
        $this->assertEquals(180.0, (float) $item->seller_earning);

        // หักเงินครั้งเดียวตามยอดจริง
        $this->assertEquals(800.0, $this->walletBalance($buyer));
        $this->assertSame(1, WalletTransaction::where('reference_type', 'order')->where('reference_id', $order->id)->count());

        // ตัดสต็อก + ล้างตะกร้า
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);
        $this->assertSame(0, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $buyer->id))->count());

        // ตอบ payload ไม่มี PV/คอมมิชชั่น (SHOP-16)
        $this->assertArrayNotHasKey('pvEarned', $response->json('data'));
    }

    public function test_wallet_checkout_with_insufficient_balance_returns_thai_message_and_changes_nothing(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(50);
        $address = $this->makeAddress($buyer);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'INSUFFICIENT_BALANCE')
            ->assertJsonPath('message', 'ยอดเงินในกระเป๋าไม่เพียงพอ')
            ->assertJsonPath('data.required', 200)
            ->assertJsonPath('data.available', 50)
            ->assertJsonPath('data.shortfall', 150);

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
        $this->assertEquals(50.0, $this->walletBalance($buyer));
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $buyer->id))->count(), 'ตะกร้ายังอยู่ให้ลองใหม่');
    }

    public function test_checkout_requires_address_for_physical_products(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store);
        $buyer = $this->makeBuyer(1000);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->assertOk();

        $this->postJson('/api/v1/cart/checkout', ['payment_method' => 'wallet'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'ADDRESS_REQUIRED');

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
    }

    /**
     * แอปรุ่นก่อน (ใน store) ส่งแค่ {payment_method, promo_code} และอ่าน data.orderId / orderNumber
     * → ใช้ที่อยู่หลักของผู้ใช้ และตอบคีย์แบบเดิมด้วย (deploy backend ก่อนแอปใหม่ได้โดยไม่พัง)
     */
    public function test_old_app_checkout_without_address_id_uses_default_address_and_gets_legacy_keys(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->assertOk();

        $response = $this->postJson('/api/v1/cart/checkout', ['payment_method' => 'wallet'])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame((int) $address->id, (int) $order->shipping_address_id);
        $this->assertSame((int) $order->id, $response->json('data.orderId'));
        $this->assertSame($order->order_number, $response->json('data.orderNumber'));
        $this->assertEqualsWithDelta(100.0, $response->json('data.total'), 0.001);
    }

    public function test_old_app_checkout_builds_address_from_profile_when_user_has_none(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(1000);
        $buyer->forceFill([
            'phone' => '0812345678',
            'address' => '12 ถนนสีลม',
            'city' => 'บางรัก',
            'state' => 'กรุงเทพมหานคร',
            'postal_code' => '10500',
        ])->save();

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->assertOk();

        $this->postJson('/api/v1/cart/checkout', ['payment_method' => 'wallet'])->assertCreated();

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertNotNull($order->shipping_address_id);
        $this->assertSame('12 ถนนสีลม', $order->shipping_address_snapshot['address_line_1']);
        $this->assertSame('กรุงเทพมหานคร', $order->shipping_address_snapshot['province']);
    }

    // =====================================================
    // COD
    // =====================================================

    public function test_cod_with_rider_delivery_creates_pending_order_and_reserves_stock(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 150, 'stock_quantity' => 5]);
        $buyer = $this->makeBuyer(0);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $this->getJson('/api/v1/cart?delivery_method=rider&address_id='.$address->id)
            ->assertOk()
            ->assertJsonPath('data.stores.0.rider.available', true)
            ->assertJsonPath('data.stores.0.cod.available', true);

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'cod',
            'delivery_method' => 'rider',
        ])
            ->assertCreated()
            ->assertJsonPath('data.payment_status', 'cod')
            ->assertJsonPath('data.orders.0.status', 'pending')
            ->assertJsonPath('data.orders.0.payment_status', 'pending')
            ->assertJsonPath('data.orders.0.delivery_method', 'rider');

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertTrue((float) $order->shipping_fee > 0, 'ค่าส่งไรเดอร์ต้องมาจากระยะทางจริง');
        $this->assertEquals(round(150 + (float) $order->shipping_fee, 2), (float) $order->total_amount);
        $this->assertNotNull($order->stock_deducted_at);
        $this->assertSame(4, (int) $product->fresh()->stock_quantity);
        $this->assertEquals((float) $order->total_amount, $order->riderCodAmount());
        $this->assertEquals(0.0, $this->walletBalance($buyer));
    }

    public function test_cod_with_parcel_delivery_is_rejected(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store);
        $buyer = $this->makeBuyer(0);
        $address = $this->makeAddress($buyer);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->assertOk();

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'cod',
            'delivery_method' => 'parcel',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'COD_NOT_AVAILABLE');

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
    }

    // =====================================================
    // ยกเลิก
    // =====================================================

    public function test_cancel_unpaid_order_does_not_refund_or_restock(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(500);

        // สถานะ processing แต่ยังไม่จ่าย (ข้อมูลแบบที่ checkout เดิมเคยสร้าง) → ต้องไม่คืนเงิน
        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 3]], [
            'store_id' => $store->id,
            'status' => 'processing',
            'payment_status' => 'pending',
        ]);

        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/{$order->id}/cancel", ['reason' => 'เปลี่ยนใจ'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('refunded', false);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertEquals(500.0, $this->walletBalance($buyer), 'ออเดอร์ที่ยังไม่จ่ายห้ามคืนเงิน');
        $this->assertSame(10, (int) $product->fresh()->stock_quantity, 'ไม่เคยตัดสต็อก ห้ามคืนสต็อก');
        $this->assertSame(0, WalletTransaction::where('user_id', $buyer->id)->count());
    }

    public function test_cancel_paid_order_refunds_once_and_restores_stock(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->postJson('/api/v1/cart/checkout', ['address_id' => $address->id, 'payment_method' => 'wallet'])->assertCreated();

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertEquals(800.0, $this->walletBalance($buyer));
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);

        $this->postJson("/api/v1/orders/{$order->id}/cancel", ['reason' => 'สั่งผิด'])
            ->assertOk()
            ->assertJsonPath('refunded', true);

        $order->refresh();
        $this->assertSame('refunded', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertEquals(1000.0, $this->walletBalance($buyer), 'คืนเงินเต็มจำนวน');
        $this->assertSame(10, (int) $product->fresh()->stock_quantity, 'คืนสต็อกที่ตัดไป');
        $this->assertNull($order->stock_deducted_at);

        // กดซ้ำทาง API → ยกเลิกไม่ได้แล้ว
        $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertStatus(409);

        // เรียกตรงซ้ำ → ไม่คืนเงินซ้ำ
        $again = $order->cancel('ซ้ำ', null, 'buyer');
        $this->assertTrue($again['already']);
        $this->assertEquals(1000.0, $this->walletBalance($buyer), 'ห้ามคืนเงินซ้ำ');
        $this->assertSame(10, (int) $product->fresh()->stock_quantity, 'ห้ามคืนสต็อกซ้ำ');
    }

    // =====================================================
    // คูปอง / สินค้าที่สั่งไม่ได้
    // =====================================================

    public function test_store_coupon_is_validated_applied_to_items_and_usable_once(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer);

        Coupon::create([
            'code' => 'SHOP10',
            'store_id' => $store->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_purchase' => 100,
            'usage_limit' => 5,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        // โค้ดปลอมที่เคยฝังในโค้ดใช้ไม่ได้แล้ว
        $this->postJson('/api/v1/cart/promo', ['code' => 'FIRST10'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'COUPON_INVALID');

        $this->postJson('/api/v1/cart/promo', ['code' => 'shop10'])
            ->assertOk()
            ->assertJsonPath('data.coupon.discount', 20)
            ->assertJsonPath('data.summary.grand_total', 180);

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
            'coupon_code' => 'SHOP10',
        ])->assertCreated();

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertEquals(20.0, (float) $order->discount_amount);
        $this->assertEquals(20.0, (float) $order->product_discount);
        $this->assertEquals(0.0, (float) $order->shipping_discount);
        $this->assertSame('store', $order->discount_funded_by, 'คูปองของร้าน = ร้านออกเงินส่วนลด');
        $this->assertEquals(180.0, (float) $order->total_amount);
        $this->assertEquals(820.0, $this->walletBalance($buyer));

        // คูปองร้านไม่ใช่รายจ่ายของแพลตฟอร์ม (ร้านรับภาระใน order_items.total แล้ว — ห้ามนับซ้ำ)
        $this->assertSame(0, \App\Models\PlatformTransaction::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('sub_type', 'like', 'order_discount%')
            ->count());

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals(20.0, (float) $item->discount_amount);
        $this->assertEquals(180.0, (float) $item->total, 'ร้านออกส่วนลด → ยอดขายสุทธิของรายการลดลง');
        $this->assertEquals(18.0, (float) $item->commission_amount, 'GP คิดจากยอดหลังลด');

        $this->assertSame(1, (int) Coupon::where('code', 'SHOP10')->value('used_count'));
        $this->assertSame(1, DB::table('coupon_usages')->where('order_id', $order->id)->count());

        // ใช้ซ้ำไม่ได้
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
            'coupon_code' => 'SHOP10',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'COUPON_INVALID');
    }

    public function test_blocked_product_cannot_be_added_or_checked_out(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])->assertOk();

        // ถูกบล็อกหลังใส่ตะกร้า
        Product::whereKey($product->id)->update(['is_blocked' => true]);

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.items.0.is_available', false)
            ->assertJsonPath('data.summary.subtotal', 0);

        $this->postJson('/api/v1/cart/checkout', ['address_id' => $address->id, 'payment_method' => 'wallet'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PRODUCT_UNAVAILABLE');

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PRODUCT_UNAVAILABLE');

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
    }

    public function test_adding_same_product_twice_merges_into_one_line_and_respects_stock(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['stock_quantity' => 3]);
        $buyer = $this->makeBuyer(0);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->postJson('/api/v1/cart/add', ['product_id' => $product->id, 'quantity' => 1])
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.quantity', 3);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertStatus(409)
            ->assertJsonPath('code', 'OUT_OF_STOCK');
    }
}
