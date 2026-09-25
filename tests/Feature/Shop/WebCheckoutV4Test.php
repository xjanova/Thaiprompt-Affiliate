<?php

namespace Tests\Feature\Shop;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShoppingCart;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * ชำระเงินหน้าเว็บ (ธีม V4) ใช้กฎเดียวกับแอป — ต้องใช้ MySQL
 *
 * - GET checkout.quote คิดค่าส่งพัสดุ/ไรเดอร์ + ความพร้อม COD + คูปอง จากตะกร้าเว็บ (shopping_cart)
 * - POST checkout.address-location ปักหมุดที่อยู่ของตัวเองเท่านั้น
 * - POST checkout.process: wallet/promptpay/cod → ShopCheckoutService (แยกออเดอร์ตามร้าน, COD เฉพาะไรเดอร์)
 *   ไม่แตะตะกร้าแอป (carts/cart_items) และกดซ้ำด้วย idempotency_key เดิมไม่สร้างออเดอร์ซ้ำ
 */
#[Group('shop')]
class WebCheckoutV4Test extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();

        $this->setGpRate(10);
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.max_cod_amount', '2000', 'float', 'rider');
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\Product, 2: \App\Models\ShippingAddress}
     */
    private function buyerWithWebCart(float $wallet = 1000, bool $riderStore = true, bool $pinned = true): array
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => $riderStore]);
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer($wallet);
        $address = $this->makeAddress($buyer, $pinned);

        ShoppingCart::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'quantity' => 2]);

        return [$buyer, $product, $address];
    }

    public function test_quote_reports_rider_and_cod_availability_for_web_cart(): void
    {
        [$buyer, , $address] = $this->buyerWithWebCart();

        $this->actingAs($buyer)
            ->getJson(route('checkout.quote', ['address_id' => $address->id, 'delivery_method' => 'rider']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.subtotal', 200)
            ->assertJsonPath('data.summary.rider_available', true)
            ->assertJsonPath('data.summary.cod_available', true)
            ->assertJsonPath('data.delivery_method', 'rider')
            ->assertJsonStructure(['data' => ['items', 'stores' => [['rider' => ['available', 'fee', 'distance_km'], 'cod' => ['available']]], 'summary' => ['grand_total']]]);
    }

    public function test_quote_explains_missing_pin_and_pin_endpoint_fixes_it(): void
    {
        [$buyer, , $address] = $this->buyerWithWebCart(1000, true, false);
        $this->actingAs($buyer);

        $this->getJson(route('checkout.quote', ['address_id' => $address->id, 'delivery_method' => 'rider']))
            ->assertOk()
            ->assertJsonPath('data.summary.rider_available', false)
            ->assertJsonPath('data.stores.0.rider.reason', 'ที่อยู่นี้ยังไม่ได้ปักหมุดตำแหน่ง');

        $this->postJson(route('checkout.address-location'), [
            'address_id' => $address->id,
            'latitude' => self::STORE_LAT + 0.01,
            'longitude' => self::STORE_LNG + 0.01,
        ])->assertOk()->assertJsonPath('data.has_location', true);

        $this->getJson(route('checkout.quote', ['address_id' => $address->id, 'delivery_method' => 'rider']))
            ->assertOk()
            ->assertJsonPath('data.summary.rider_available', true);
    }

    public function test_pin_endpoint_rejects_other_users_address_and_null_island(): void
    {
        [$buyer] = $this->buyerWithWebCart();
        $other = $this->makeBuyer(0);
        $otherAddress = $this->makeAddress($other, false);
        $ownAddress = \App\Models\ShippingAddress::where('user_id', $buyer->id)->firstOrFail();

        $this->actingAs($buyer)
            ->postJson(route('checkout.address-location'), ['address_id' => $otherAddress->id, 'latitude' => 13.7, 'longitude' => 100.5])
            ->assertNotFound()
            ->assertJsonPath('code', 'ADDRESS_NOT_FOUND');

        $this->assertNull($otherAddress->fresh()->latitude);

        $this->actingAs($buyer)
            ->postJson(route('checkout.address-location'), ['address_id' => $ownAddress->id, 'latitude' => 0, 'longitude' => 0])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_LOCATION');
    }

    public function test_wallet_checkout_pays_clears_web_cart_and_keeps_app_cart(): void
    {
        [$buyer, $product, $address] = $this->buyerWithWebCart(1000);

        // ตะกร้าในแอปมีของอื่นอยู่ — ต้องไม่ถูกแตะ
        $appCart = Cart::create(['user_id' => $buyer->id, 'session_id' => null]);
        $appItem = CartItem::create(['cart_id' => $appCart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 100]);

        $response = $this->actingAs($buyer)->post(route('checkout.process'), [
            'shipping_address_id' => $address->id,
            'delivery_method' => 'parcel',
            'payment_method' => 'wallet',
            'idempotency_key' => 'web-test-1',
        ]);

        $order = Order::where('user_id', $buyer->id)->latest('id')->firstOrFail();
        $response->assertRedirect(route('checkout.success', $order->id));

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame(0, ShoppingCart::where('user_id', $buyer->id)->count());
        $this->assertNotNull(CartItem::find($appItem->id), 'ตะกร้าแอปต้องไม่ถูกล้าง');
        $this->assertEqualsWithDelta(1000 - (float) $order->total_amount, (float) Wallet::where('user_id', $buyer->id)->value('balance'), 0.01);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);

        // กดซ้ำด้วย key เดิม → ไม่สร้างออเดอร์ใหม่
        $this->actingAs($buyer)->post(route('checkout.process'), [
            'shipping_address_id' => $address->id,
            'payment_method' => 'wallet',
            'idempotency_key' => 'web-test-1',
        ]);
        $this->assertSame(1, Order::where('user_id', $buyer->id)->count());

        // หน้าสำเร็จแสดงได้
        $this->actingAs($buyer)->get(route('checkout.success', $order->id))->assertOk()->assertSee($order->order_number);
    }

    public function test_cod_requires_rider_delivery(): void
    {
        [$buyer, , $address] = $this->buyerWithWebCart(0);

        $this->actingAs($buyer)->post(route('checkout.process'), [
            'shipping_address_id' => $address->id,
            'delivery_method' => 'parcel',
            'payment_method' => 'cod',
        ])->assertRedirect(route('checkout.index'))
            ->assertSessionHas('checkout_error_code', 'COD_NOT_AVAILABLE');

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
        $this->assertSame(1, ShoppingCart::where('user_id', $buyer->id)->count(), 'สั่งไม่สำเร็จ ตะกร้าต้องยังอยู่');
    }

    public function test_cod_with_rider_creates_rider_order(): void
    {
        [$buyer, , $address] = $this->buyerWithWebCart(0);

        $response = $this->actingAs($buyer)->post(route('checkout.process'), [
            'shipping_address_id' => $address->id,
            'delivery_method' => 'rider',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::where('user_id', $buyer->id)->latest('id')->firstOrFail();
        $response->assertRedirect(route('checkout.success', $order->id));
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('rider', $order->delivery_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertGreaterThan(0, (float) $order->shipping_fee);
    }

    public function test_insufficient_wallet_returns_to_checkout_with_thai_error(): void
    {
        [$buyer, , $address] = $this->buyerWithWebCart(10);

        $this->actingAs($buyer)->post(route('checkout.process'), [
            'shipping_address_id' => $address->id,
            'payment_method' => 'wallet',
        ])->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error', 'ยอดเงินในกระเป๋าไม่เพียงพอ')
            ->assertSessionHas('checkout_error_code', 'INSUFFICIENT_BALANCE');

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
    }

    public function test_legacy_card_payment_cannot_use_rider_or_coupon(): void
    {
        [$buyer, , $address] = $this->buyerWithWebCart();

        $this->actingAs($buyer)->post(route('checkout.process'), [
            'shipping_address_id' => $address->id,
            'delivery_method' => 'rider',
            'payment_method' => 'credit_card',
        ])->assertRedirect(route('checkout.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
    }

    public function test_checkout_page_lists_addresses_with_pin_state(): void
    {
        [$buyer, , $address] = $this->buyerWithWebCart(500, true, false);

        $this->actingAs($buyer)->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('tp-root', false)
            ->assertSee($address->recipient_name)
            ->assertSee('ปักหมุดตำแหน่ง', false)
            ->assertSee('x-data="tpCheckout(', false)
            ->assertSee('name="idempotency_key"', false);
    }
}
