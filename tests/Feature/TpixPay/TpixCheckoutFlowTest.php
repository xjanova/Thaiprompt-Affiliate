<?php

namespace Tests\Feature\TpixPay;

use App\Models\FreshMarketCartItem;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\TpixPayment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FreshMarketCartService;
use App\Services\FreshMarketService;
use App\Services\ServiceBookingService;
use App\Services\TpixPay\TpixCheckoutService;
use App\Services\TpixPay\TpixPayException;
use App\Services\TpixPay\TpixPaymentPresenter;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\TpixPay\Concerns\SimulatesTpixChain;
use Tests\TestCase;

/**
 * 🪙 จ่ายตลาดสด / การจองบริการด้วยเหรียญ TPIX — "เหรียญ → กระเป๋าเงิน → เส้นจ่ายด้วยกระเป๋าเดิม" (ต้องใช้ MySQL)
 *
 * ครอบคลุม: ออกยอดจากยอดจริง (ไม่สร้างออเดอร์) · ได้เหรียญแล้วสั่งผ่านกระเป๋า (escrow held) · สั่งไม่สำเร็จเงินอยู่ในกระเป๋า
 *           ราคาขึ้นระหว่างโอนไม่หักเงินเพิ่ม · ส่งซ้ำ/เรียกซ้ำได้ออเดอร์เดียว · เพดาน · API คืนก้อน tpix · การจองบริการ
 */
class TpixCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;
    use SimulatesTpixChain;

    private User $buyer;

    private FreshMarketSeller $seller;

    private FreshMarketListing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Notification::fake();

        config([
            'ekyc.enforce' => false,
            'profile_photo.required' => false,
            'turnstile.enabled' => false,
        ]);

        $this->bootTpixChain();

        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => false,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();
        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('pricing.gp_free', '0', 'boolean', 'pricing');

        $this->buyer = User::factory()->create(['name' => 'ผู้ซื้อเหรียญ']);
        $sellerUser = User::factory()->create(['name' => 'ร้านผัก']);
        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $this->seller = FreshMarketSeller::create([
            'user_id' => $sellerUser->id,
            'shop_name' => 'ร้านผักทดสอบ',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        $this->listing = FreshMarketListing::create([
            'seller_id' => $this->seller->id,
            'title' => 'ผักบุ้งจีน',
            'price' => 50,
            'unit' => 'กำ',
            'quantity_available' => 5,
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }

    // =====================================================
    // ตลาดสด
    // =====================================================

    public function test_quote_uses_real_total_and_creates_no_order_until_paid(): void
    {
        $payment = $this->quoteCart(2);

        $this->assertSame(TpixPayment::PURPOSE_FRESH_MARKET, $payment->purpose);
        $this->assertSame('100.00', (string) $payment->amount_thb, '2 × 50 บาท (รับเองที่ร้าน ไม่มีค่าส่ง)');
        $this->assertSame(1, bccomp((string) $payment->amount_tpix, '30.7692', 18), '100 / 3.25 = 30.7692… + เลขท้าย');
        $this->assertSame(0, FreshMarketOrder::count(), 'ยังไม่จ่าย = ยังไม่มีออเดอร์ (ร้านไม่เห็น)');
        $this->assertSame(5, (int) $this->listing->fresh()->quantity_available, 'ยังไม่ตัดสต็อก');
        $this->assertSame(1, FreshMarketCartItem::where('user_id', $this->buyer->id)->count(), 'ตะกร้ายังอยู่');

        // กดซ้ำด้วยข้อมูลเดิม → ใบเดิม (ไม่ออกยอดซ้ำ)
        $again = $this->quoteCart(0);
        $this->assertSame($payment->id, $again->id);
    }

    public function test_paid_quote_places_the_order_through_the_wallet_path_with_escrow_held(): void
    {
        $payment = $this->quoteCart(2);
        $hash = $this->sendTpixTx(self::TPIX_RECEIVE, $payment->amount_wei);

        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame(TpixPayment::STATUS_PAID, $result['status'], $result['message']);
        $this->assertStringContainsString('สั่งซื้อสำเร็จ', $result['message']);

        $order = FreshMarketOrder::sole();
        $this->assertSame($this->buyer->id, (int) $order->buyer_id);
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('held', $order->escrow_status);
        $this->assertSame(FreshMarketOrder::STATUS_PENDING, $order->order_status);
        $this->assertSame(100.0, (float) $order->total_amount);
        $this->assertSame(10.0, (float) $order->platform_fee);
        $this->assertSame(3, (int) $this->listing->fresh()->quantity_available);
        $this->assertSame(0, FreshMarketCartItem::where('user_id', $this->buyer->id)->count(), 'ตะกร้าร้านนี้ถูกล้าง');

        // เงิน: เติม 100 จาก TPIX แล้วหัก 100 ค่าสินค้า → คงเหลือ 0
        $this->assertSame(0.0, $this->balance());
        $this->assertSame(1, WalletTransaction::where('reference_type', 'tpix_payment')->where('reference_id', $payment->id)->where('type', 'deposit')->count());
        $this->assertSame(1, WalletTransaction::where('reference_type', FreshMarketService::REF_PAYMENT)->where('reference_id', $order->id)->count());

        $payment->refresh();
        $this->assertSame(TpixPayment::FULFIL_PLACED, $payment->fulfilmentStatus());
        $data = TpixPaymentPresenter::present($payment);
        $this->assertSame($order->id, $data['fresh_market_order_id']);
        $this->assertNull($data['order_id'], 'order_id สงวนไว้ให้ออเดอร์ร้านค้า (แอปเปิดหน้าคำสั่งซื้อร้านค้า)');
        $this->assertSame('placed', $data['fulfilment_status']);
        $this->assertSame($order->order_number, $data['order_number']);
        $this->assertStringContainsString('/taladsod/orders/'.$order->id, (string) $data['checkout']['result_url']);
    }

    public function test_double_settle_and_repeated_fulfil_place_exactly_one_order(): void
    {
        $payment = $this->quoteCart(1);
        $hash = $this->sendTpixTx(self::TPIX_RECEIVE, $payment->amount_wei);
        $payments = app(TpixPaymentService::class);

        $payments->submitTx($payment, $hash);
        $payments->submitTx($payment->fresh(), $hash);
        $payments->verifyAndSettle($payment->fresh(), $hash);
        app(TpixCheckoutService::class)->fulfil($payment->fresh());
        app(TpixCheckoutService::class)->retryPending();
        $payments->refresh($payment->fresh());

        $this->assertSame(1, FreshMarketOrder::count());
        $this->assertSame(4, (int) $this->listing->fresh()->quantity_available);
        $this->assertSame(0.0, $this->balance());
        $this->assertSame(1, WalletTransaction::where('reference_type', 'tpix_payment')->count(), 'เติมเข้ากระเป๋าครั้งเดียว');
    }

    public function test_claimed_fulfilment_is_never_retried_automatically(): void
    {
        $payment = $this->quoteCart(1);
        // จำลองเครื่องดับระหว่างสั่ง: ได้เงินแล้ว + จองสิทธิ์ไปแล้ว (processing)
        $payment->forceFill([
            'status' => TpixPayment::STATUS_CREDITED_WALLET,
            'paid_at' => now(),
            'meta' => array_merge($payment->meta, ['fulfilment' => ['status' => 'processing', 'attempts' => 1]]),
        ])->save();

        app(TpixCheckoutService::class)->fulfil($payment->fresh());
        app(TpixCheckoutService::class)->retryPending();

        $this->assertSame(0, FreshMarketOrder::count(), 'ไม่สั่งซ้ำเมื่อไม่รู้ผลรอบก่อน');
    }

    public function test_failure_after_credit_keeps_the_money_in_the_wallet_with_a_clear_reason(): void
    {
        $payment = $this->quoteCart(2);
        // ของหมดระหว่างรอโอน
        $this->listing->forceFill(['quantity_available' => 1])->save();

        $hash = $this->sendTpixTx(self::TPIX_RECEIVE, $payment->amount_wei);
        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame(TpixPayment::STATUS_CREDITED_WALLET, $result['status']);
        $this->assertStringContainsString('ไม่เพียงพอ', $result['message']);
        $this->assertStringContainsString('กระเป๋าเงิน', $result['message']);
        $this->assertSame(0, FreshMarketOrder::count());
        $this->assertSame(100.0, $this->balance(), 'เงินอยู่ในกระเป๋าครบ');
        $this->assertSame(1, (int) $this->listing->fresh()->quantity_available);

        $payment->refresh();
        $this->assertSame(TpixPayment::FULFIL_FAILED, $payment->fulfilmentStatus());
        $data = TpixPaymentPresenter::present($payment);
        $this->assertSame('failed', $data['fulfilment_status']);
        $this->assertNull($data['fresh_market_order_id']);
        $this->assertTrue($data['is_settled']);
    }

    public function test_price_increase_while_paying_does_not_take_extra_money_from_the_wallet(): void
    {
        $this->buyerWallet()->forceFill(['balance' => 500])->save();
        $payment = $this->quoteCart(2);
        $this->listing->forceFill(['price' => 80])->save();

        $hash = $this->sendTpixTx(self::TPIX_RECEIVE, $payment->amount_wei);
        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame(TpixPayment::STATUS_CREDITED_WALLET, $result['status']);
        $this->assertStringContainsString('ยอดสั่งซื้อเปลี่ยน', $result['message']);
        $this->assertSame(0, FreshMarketOrder::count());
        $this->assertSame(600.0, $this->balance(), 'เงินเดิม 500 + เหรียญ 100 — ไม่ถูกหักส่วนต่างราคา');
    }

    public function test_quote_is_refused_when_disabled_or_over_the_caps_or_order_invalid(): void
    {
        $this->addToCart(2);
        $checkout = app(TpixCheckoutService::class);
        [$items, $cartIds] = $this->cartLines();

        app(TpixPaySettings::class)->save(['max_per_payment_thb' => 50]);
        try {
            $checkout->quoteFreshMarket($this->buyer, $this->seller->id, $items, ['delivery_type' => 'pickup'], $cartIds);
            $this->fail('ต้องติดเพดานต่อรายการ');
        } catch (TpixPayException $e) {
            $this->assertSame(TpixPayException::LIMIT_EXCEEDED, $e->errorCode);
            $this->assertStringContainsString('ไม่เกิน 50.00 บาทต่อรายการ', $e->getMessage());
        }

        app(TpixPaySettings::class)->save(['max_per_payment_thb' => 20000, 'enabled' => false]);
        try {
            $checkout->quoteFreshMarket($this->buyer, $this->seller->id, $items, ['delivery_type' => 'pickup'], $cartIds);
            $this->fail('ต้องปิดรับ');
        } catch (TpixPayException $e) {
            $this->assertSame(TpixPayException::DISABLED, $e->errorCode);
        }

        // ร้านตัวเอง → ด่านเดียวกับการสั่งจริง (ตรวจก่อนให้โอนเหรียญ)
        app(TpixPaySettings::class)->save(['enabled' => true]);
        $this->expectException(\App\Exceptions\FreshMarketException::class);
        $checkout->quoteFreshMarket($this->seller->user, $this->seller->id, $items, ['delivery_type' => 'pickup']);
    }

    public function test_api_returns_the_tpix_payload_and_status_shows_the_placed_order(): void
    {
        $this->addToCart(2);
        Sanctum::actingAs($this->buyer);

        $config = $this->getJson('/api/v1/fresh-market/config')->assertOk();
        $this->assertTrue($config->json('data.tpix.enabled'));
        $this->assertNotContains('tpix', $config->json('data.payment_methods'), 'แอปรุ่นเก่าต้องไม่เห็นค่าใหม่ในรายการเดิม');

        $res = $this->postJson('/api/v1/fresh-market/orders', [
            'seller_id' => $this->seller->id,
            'delivery_type' => 'pickup',
            'payment_method' => 'tpix',
        ])->assertOk();

        $res->assertJsonPath('data.payment_method', 'tpix')
            ->assertJsonPath('data.order', null)
            ->assertJsonPath('data.tpix.purpose', 'fresh_market')
            ->assertJsonPath('data.tpix.amount_thb', 100);
        $reference = (string) $res->json('data.tpix.reference');
        $this->assertMatchesRegularExpression('/^TPX[A-Z0-9]{21}$/', $reference);
        $this->assertSame(0, FreshMarketOrder::count());

        $payment = TpixPayment::where('reference', $reference)->firstOrFail();
        $hash = $this->sendTpixTx(self::TPIX_RECEIVE, $payment->amount_wei);
        $this->postJson("/api/v1/tpix/payments/{$reference}/submit", ['tx_hash' => $hash])->assertOk()
            ->assertJsonPath('result', 'paid');

        $order = FreshMarketOrder::sole();
        $this->getJson("/api/v1/tpix/payments/{$reference}")->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.fresh_market_order_id', $order->id)
            ->assertJsonPath('data.fulfilment_status', 'placed');
    }

    public function test_web_checkout_redirects_to_the_tpix_pay_page(): void
    {
        $this->addToCart(1);

        $response = $this->actingAs($this->buyer)->post(route('taladsod.checkout.store'), [
            'seller_id' => $this->seller->id,
            'delivery_type' => 'pickup',
            'payment_method' => 'tpix',
        ]);

        $payment = TpixPayment::sole();
        $response->assertRedirect(route('tpix-pay.show', $payment->reference));
        $this->assertSame('50.00', (string) $payment->amount_thb);
        $this->assertSame(0, FreshMarketOrder::count());

        // หน้าจ่ายแสดงชื่อรายการตลาดสด · หน้ายืนยันสั่งซื้อมีตัวเลือกเหรียญ TPIX
        $this->withoutVite();
        $this->actingAs($this->buyer)->get(route('tpix-pay.show', $payment->reference))
            ->assertOk()
            ->assertSee('ตลาดสด · ร้านผักทดสอบ');
        $this->actingAs($this->buyer)->get(route('taladsod.checkout', $this->seller->id))
            ->assertOk()
            ->assertSee('เหรียญ TPIX');
    }

    // =====================================================
    // การจองบริการ
    // =====================================================

    public function test_paid_booking_quote_pays_the_booking_from_the_wallet_and_refunds_on_cancel(): void
    {
        $booking = $this->pendingBooking(500);
        $payment = app(TpixCheckoutService::class)->quoteServiceBooking($booking);

        $this->assertSame(TpixPayment::PURPOSE_SERVICE_BOOKING, $payment->purpose);
        $this->assertSame('500.00', (string) $payment->amount_thb);
        $this->assertSame($payment->id, app(TpixCheckoutService::class)->quoteServiceBooking($booking)->id, 'ใบเดิม');

        $hash = $this->sendTpixTx(self::TPIX_RECEIVE, $payment->amount_wei);
        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame(TpixPayment::STATUS_PAID, $result['status'], $result['message']);
        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('paid', $booking->status);
        $this->assertSame('wallet', $booking->payment_method);
        $this->assertSame(0.0, $this->balance());

        $data = TpixPaymentPresenter::present($payment->fresh());
        $this->assertSame($booking->id, $data['service_booking_id']);
        $this->assertSame('placed', $data['fulfilment_status']);

        // ยกเลิกหลังจ่าย → คืนเงินเข้ากระเป๋าครั้งเดียว
        app(ServiceBookingService::class)->cancelBooking($booking, 'เปลี่ยนใจ', 'customer');
        $this->assertSame(500.0, $this->balance());
        $this->assertSame('refunded', $booking->fresh()->payment_status);
    }

    public function test_booking_api_with_tpix_returns_the_tpix_payload_and_leaves_the_booking_unpaid(): void
    {
        $service = $this->pendingBooking(400)->service;
        Sanctum::actingAs($this->buyer);

        $res = $this->postJson('/api/v1/bookings', [
            'service_id' => $service->id,
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            'customer_latitude' => 13.75,
            'customer_longitude' => 100.50,
            'customer_address' => 'บ้านทดสอบ',
            'payment_method' => 'tpix',
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.payment_method', 'tpix')
            ->assertJsonPath('data.tpix.purpose', 'service_booking');
        $booking = ServiceBooking::findOrFail($res->json('data.id'));
        $this->assertSame('pending', $booking->payment_status);
        $this->assertSame($booking->id, $res->json('data.tpix.service_booking_id'));
        $this->assertSame((float) $booking->total_amount, (float) $res->json('data.tpix.amount_thb'));
    }

    public function test_booking_cancelled_before_the_coins_arrive_keeps_the_money_in_the_wallet(): void
    {
        $booking = $this->pendingBooking(300);
        $payment = app(TpixCheckoutService::class)->quoteServiceBooking($booking);
        $booking->forceFill(['status' => 'cancelled'])->save();

        $hash = $this->sendTpixTx(self::TPIX_RECEIVE, $payment->amount_wei);
        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame(TpixPayment::STATUS_CREDITED_WALLET, $result['status']);
        $this->assertStringContainsString('ปิดไปแล้ว', $result['message']);
        $this->assertSame(300.0, $this->balance());
        $this->assertSame('pending', $booking->fresh()->payment_status);
    }

    public function test_wallet_payment_for_bookings_works_and_refuses_insufficient_balance(): void
    {
        $booking = $this->pendingBooking(200);

        try {
            app(ServiceBookingService::class)->processPayment($booking, 'wallet');
            $this->fail('ยอดไม่พอต้องไม่ผ่าน');
        } catch (\Exception $e) {
            $this->assertStringContainsString('ไม่เพียงพอ', $e->getMessage());
        }

        $this->buyerWallet()->forceFill(['balance' => 250])->save();
        app(ServiceBookingService::class)->processPayment($booking, 'wallet');
        $this->assertSame(50.0, $this->balance());
        $this->assertSame('paid', $booking->fresh()->payment_status);

        $this->expectExceptionMessage('ชำระเงินแล้ว');
        app(ServiceBookingService::class)->processPayment($booking->fresh(), 'wallet');
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    private function addToCart(int $qty): void
    {
        app(FreshMarketCartService::class)->addItem($this->buyer, $this->listing->id, $qty);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, int>}
     */
    private function cartLines(): array
    {
        $lines = app(FreshMarketCartService::class)->checkoutLines($this->buyer, $this->seller->id);

        return [$lines['items'], $lines['cart_item_ids']];
    }

    /** ใส่ตะกร้า (qty > 0) แล้วออกยอด TPIX ของตะกร้าร้านนี้ */
    private function quoteCart(int $qty): TpixPayment
    {
        if ($qty > 0) {
            $this->addToCart($qty);
        }
        [$items, $cartIds] = $this->cartLines();

        return app(TpixCheckoutService::class)->quoteFreshMarket($this->buyer, $this->seller->id, $items, [
            'delivery_type' => 'pickup',
            'channel' => 'api',
        ], $cartIds);
    }

    private function pendingBooking(float $total): ServiceBooking
    {
        $category = ServiceCategory::create(['name' => 'นวด', 'slug' => 'massage-'.uniqid()]);
        $service = Service::create([
            'category_id' => $category->id,
            'owner_id' => $this->seller->user_id,
            'name' => 'นวดไทย 1 ชั่วโมง',
            'slug' => 'thai-massage-'.uniqid(),
            'base_price' => $total,
            'requires_location' => false,
        ]);

        return ServiceBooking::create([
            'user_id' => $this->buyer->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay(),
            'base_price' => $total,
            'subtotal' => $total,
            'total_amount' => $total,
            'payment_method' => 'tpix',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    private function buyerWallet(): Wallet
    {
        return Wallet::where('user_id', $this->buyer->id)->firstOrFail();
    }

    private function balance(): float
    {
        return round((float) $this->buyerWallet()->fresh()->balance, 2);
    }
}
