<?php

namespace Tests\Feature\RiderR2;

use App\Models\EarningsLedger;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * ไรเดอร์รอบ 2 (เลน money) — ชำระเงินร้านค้าแบบส่งด้วยไรเดอร์: ต้องจ่ายก่อน + เงินพักจนส่งมอบ
 *
 * - COD + ไรเดอร์ ถูกปฏิเสธ COD_NOT_AVAILABLE (rider.allow_cod = false ค่าเริ่มต้น)
 * - จ่ายผ่านวอลเลต + ไรเดอร์ → settlement_deferred = true, ล็อกโบนัส/ค่าส่งที่ร้านออก, ยังไม่แบ่งเงิน/ไม่จ่ายเงินคืน
 * - ส่งพัสดุ (ไม่ใช่ไรเดอร์) ยังแบ่งเงินทันทีแบบเดิม
 */
class RiderPrepaidCheckoutTest extends TestCase
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
        Setting::set('money.distribution_backfill_from', now()->subYear()->toDateTimeString(), 'string', 'money');
    }

    public function test_cod_with_rider_is_rejected_with_prepaid_message(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 150, 'stock_quantity' => 5]);
        $buyer = $this->makeBuyer(0);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'cod',
            'delivery_method' => 'rider',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'COD_NOT_AVAILABLE')
            ->assertJsonPath('message', 'ส่งด้วยไรเดอร์ต้องชำระก่อน เงินพักไว้ปลอดภัยจนคุณได้รับของ');

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
        $this->assertSame(5, (int) $product->fresh()->stock_quantity);
    }

    public function test_wallet_rider_checkout_defers_settlement(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 150, 'stock_quantity' => 5]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
            'delivery_method' => 'rider',
        ])->assertCreated()->assertJsonPath('data.payment_status', 'paid');

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('rider', $order->delivery_method);
        $this->assertTrue((bool) $order->settlement_deferred);
        $this->assertEqualsWithDelta(0.0, (float) $order->rider_bonus_amount, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $order->delivery_subsidy_amount, 0.001);

        // จ่ายแล้วแต่ยังไม่ส่งมอบ → ไม่มีการแบ่งเงิน ไม่จ่ายเงินคืน
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());
        $this->assertFalse((bool) $order->cashback_processed);
    }

    public function test_parcel_checkout_still_distributes_immediately(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 5]);
        $buyer = $this->makeBuyer(1000);
        $address = $this->makeAddress($buyer, true);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->postJson('/api/v1/cart/checkout', [
            'address_id' => $address->id,
            'payment_method' => 'wallet',
            'delivery_method' => 'parcel',
        ])->assertCreated();

        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertFalse((bool) $order->settlement_deferred);
        $this->assertSame(1, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());
    }
}
