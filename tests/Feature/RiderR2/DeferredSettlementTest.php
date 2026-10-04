<?php

namespace Tests\Feature\RiderR2;

use App\Exceptions\FreshMarketException;
use App\Models\DeliveryHandover;
use App\Models\EarningsLedger;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CashbackService;
use App\Services\FreshMarketService;
use App\Services\OrderDistributionService;

/**
 * ไรเดอร์รอบ 2 (เลน money) — เงินพัก: ไม่แบ่งให้ใครจนส่งมอบสำเร็จ + หักโบนัส/ค่าส่งที่ร้านออกจากรายได้ร้าน
 *
 * ครอบคลุม: จ่ายแล้วแต่ยังไม่ส่งมอบ = ไม่แบ่งเงิน/ไม่จ่ายเงินคืน (observer + cron + เรียกตรง)
 * · ส่งมอบแล้ว = แบ่งเงิน + ปล่อยรายได้ร้านทันที · โบนัสไรเดอร์/ร้านออกค่าส่ง หักจากร้าน · ค่าส่งของผู้ซื้อไม่เข้าร้าน
 * · ยกเลิก/คืนเงินก่อนส่งมอบ (ไม่มี ledger/escrow) = คืนเต็มจำนวน ไม่มีเงินงอก · ตลาดสดเส้นทางเดียวกัน
 * · ตลาดสด COD + ไรเดอร์ ถูกปฏิเสธ (ร้านค้าอยู่ใน RiderPrepaidCheckoutTest)
 */
class DeferredSettlementTest extends HandoverTestCase
{
    // =====================================================
    // ร้านค้า: ไม่แบ่งเงินก่อนส่งมอบ
    // =====================================================

    public function test_paid_deferred_order_is_not_distributed_until_handover(): void
    {
        [$order] = $this->makeDeferredShopOrder();

        // ออเดอร์ที่เพิ่งจ่าย (ผ่าน observer) — สร้างเป็นรอจ่ายก่อนแล้วตั้งจ่าย
        Order::whereKey($order->id)->update(['status' => 'pending', 'payment_status' => 'pending', 'paid_at' => null]);
        $order = $order->fresh();
        $order->markAsPaid('TEST-REF');

        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());
        $this->assertSame(0, PlatformTransaction::where('source_type', 'Order')->where('source_id', $order->id)->count());
        $this->assertFalse((bool) $order->fresh()->cashback_processed);

        // cron แบ่งเงินย้อนหลัง + เรียก service ตรง + เงินคืน → ข้ามทั้งหมด
        $distribution = app(OrderDistributionService::class);
        $this->assertFalse($distribution->pendingOrdersQuery()->whereKey($order->id)->exists());
        $result = $distribution->processOrderDistribution($order->fresh());
        $this->assertTrue($result['skipped'] ?? false);
        $this->assertSame('settlement_deferred', $result['reason']);
        $this->assertNull(app(CashbackService::class)->processOrderCashback($order->fresh()));
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());

        // ส่งจัดส่งแล้ว (shipped) ก็ยังไม่แบ่ง
        $order->fresh()->markAsShipped('RIDER');
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());
    }

    public function test_rider_bonus_and_free_delivery_are_paid_by_the_shop_not_the_buyer_fee(): void
    {
        // ร้านออกค่าส่งให้ทั้งหมด (ผู้ซื้อจ่ายค่าส่ง 0) + เติมโบนัสไรเดอร์ 15
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 15, subsidy: 40, shipping: 0);
        // ผู้ซื้อจ่ายค่าส่ง 0 แต่ค่างานไรเดอร์ = ค่าส่งเต็ม (ร้านออกให้) — ไม่ใช่งาน 0 บาท
        $this->assertEqualsWithDelta(40.0, (float) $order->riderDeliveryFeeCharged(), 0.001);
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'delivering', ['shop_bonus' => 15]);

        $this->completeByScans($buyer, $order, $rider, $job);

        // ร้าน: 100 − GP 10 − ค่าส่งที่ออกให้ 40 − โบนัส 15 = 35
        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->firstOrFail();
        $this->assertSame(EarningsLedger::STATUS_PAID, $ledger->status);
        $this->assertEqualsWithDelta(55.0, (float) data_get($ledger->breakdown, 'calculations.rider_cost_deduction'), 0.001);
        $this->assertEqualsWithDelta(0.0, (float) data_get($ledger->breakdown, 'calculations.shipping_share'), 0.001);
        $this->assertEqualsWithDelta(35.0, $this->walletOf($seller), 0.001);

        // ไรเดอร์: ค่าส่ง 32 + โบนัส 15 = 47
        $this->assertEqualsWithDelta(47.0, $this->riderEarningCredits($job), 0.001);

        // ผู้ซื้อจ่าย 100 = ร้าน 35 + ไรเดอร์ 47 + แพลตฟอร์ม 18 (GP 10 + ส่วนแบ่งค่าส่ง 8) — ไม่มีเงินงอก
        $settlement = $this->buyerGet($buyer, $order)->assertOk()->json('data.settlement');
        $this->assertEqualsWithDelta(100.0, $settlement['total_paid'], 0.001);
        $this->assertEqualsWithDelta(35.0, $settlement['seller_amount'], 0.001);
        $this->assertEqualsWithDelta(47.0, $settlement['rider_amount'], 0.001);
        $this->assertEqualsWithDelta(18.0, $settlement['platform_amount'], 0.001);
        $this->assertEqualsWithDelta(
            $settlement['total_paid'],
            $settlement['seller_amount'] + $settlement['rider_amount'] + $settlement['referrer_amount'] + $settlement['platform_amount'],
            0.001
        );
    }

    public function test_buyer_delivery_fee_never_reaches_the_seller(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(shipping: 40);
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $this->completeByScans($buyer, $order, $rider, $job);

        // ร้านได้เฉพาะค่าสินค้าหลังหัก GP (90) — ค่าส่ง 40 เป็นของไรเดอร์ 32 + แพลตฟอร์ม 8
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(8.0, (float) PlatformTransaction::where('source_type', 'rider_job')
            ->where('source_id', $job->id)
            ->where('sub_type', 'rider_delivery_fee')
            ->sum('amount'), 0.001);
    }

    // =====================================================
    // ร้านค้า: ยกเลิก/คืนเงินก่อนส่งมอบ (ยังไม่มี ledger/escrow)
    // =====================================================

    public function test_cancel_before_handover_refunds_buyer_in_full_without_ledger(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 10);
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        $walletsBefore = round((float) Wallet::sum('balance'), 2);

        // ผู้ซื้อยกเลิกเองไม่ได้ (ไรเดอร์รับของแล้ว)
        $this->assertFalse($order->fresh()->canBeCancelled());

        $result = $order->fresh()->cancel('ทดสอบยกเลิกโดยแอดมิน', (int) $admin->id, 'admin');
        $this->assertTrue($result['refunded']);

        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001, 'คืนเต็มจำนวนรวมค่าส่ง');
        $this->assertSame(0.0, $this->riderEarningCredits($job));
        $this->assertEqualsWithDelta(0.0, $this->walletOf($seller), 0.001);
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());

        // เงินในระบบเพิ่มเท่ายอดที่ผู้ซื้อเคยจ่ายเท่านั้น (ไม่มีเงินงอก)
        $this->assertEqualsWithDelta($walletsBefore + 140.0, round((float) Wallet::sum('balance'), 2), 0.001);

        // ไรเดอร์ส่งมอบต่อไม่ได้แล้ว
        $this->assertNull($this->handoverOf($job)?->completed_at);
    }

    public function test_refund_while_awaiting_release_also_refunds_in_full(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk();
        $this->travel(181)->seconds();
        $this->riderPhoto($rider, $job, 'waited-photo')->assertOk();
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);

        $order->fresh()->cancel('คืนเงินระหว่างรอปลดเงิน', null, 'admin');

        $this->assertSame('failed', $job->fresh()->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(0.0, $this->riderEarningCredits($job));

        // ปลดเงินอัตโนมัติไม่ทำอะไรกับงานที่ปิดไปแล้ว
        $this->travel(25)->hours();
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame(0.0, $this->riderEarningCredits($job));
    }

    // =====================================================
    // ตลาดสด
    // =====================================================

    public function test_fresh_market_cod_with_rider_is_rejected_unless_allowed(): void
    {
        [$service, $listing, $buyer] = $this->freshMarketFixture();

        try {
            $this->placeFreshMarketRiderOrder($service, $listing, $buyer, 'cod');
            $this->fail('COD + ไรเดอร์ ต้องถูกปฏิเสธ');
        } catch (FreshMarketException $e) {
            $this->assertSame('COD_NOT_AVAILABLE', $e->errorCode());
            $this->assertSame('ส่งด้วยไรเดอร์ต้องชำระก่อน เงินพักไว้ปลอดภัยจนคุณได้รับของ', $e->getMessage());
        }
        $this->assertSame(0, FreshMarketOrder::where('buyer_id', $buyer->id)->count());

        // แอดมินเปิด rider.allow_cod → สั่งได้แบบเดิม
        Setting::set('rider.allow_cod', '1', 'boolean', 'rider');
        $order = $this->placeFreshMarketRiderOrder($service, $listing, $buyer, 'cod');
        $this->assertSame('cod', $order->payment_method);
    }

    public function test_fresh_market_rider_order_is_deferred_and_seller_pays_rider_costs(): void
    {
        [$service, $listing, $buyer, $sellerUser] = $this->freshMarketFixture();

        $order = $this->placeFreshMarketRiderOrder($service, $listing, $buyer, 'wallet');
        $this->assertTrue((bool) $order->settlement_deferred);
        $this->assertEqualsWithDelta(90.0, (float) $order->seller_earning, 0.001, 'สินค้า 100 − GP 10 (ค่าส่งของผู้ซื้อไม่อยู่ในรายได้ร้าน)');

        // เลน pricing ล็อกโบนัสไรเดอร์ที่ร้านเติมไว้บนออเดอร์ตอนสั่ง (จำลองค่าที่ล็อกไว้)
        FreshMarketOrder::whereKey($order->id)->update(['rider_bonus_amount' => 5]);
        $order = $order->fresh();
        $this->assertSame('held', $order->escrow_status);
        $paid = $order->walletPaidAmount();
        $this->assertEqualsWithDelta(100.0 + (float) $order->delivery_fee, $paid, 0.001);

        $rider = $this->makeRider();
        $job = $this->makeFreshMarketJob($order, $rider);

        $buyerToken = $this->buyerGet($buyer, $order->id, 'fresh-market')->assertOk()->json('data.handover.qr_token');
        $this->riderScan($rider, $job, ['token' => $buyerToken] + self::NEAR)->assertOk();
        $riderToken = $this->riderGet($rider, $job)->json('data.handover.qr_token');
        $data = $this->buyerScan($buyer, $order->id, $riderToken, 'fresh-market')->assertOk()
            ->assertJsonPath('data.handover.status', 'completed')
            ->json('data');

        // ส่งมอบสำเร็จ → ปิดออเดอร์ตลาดสดทันที โอนเงินร้าน + จ่ายไรเดอร์ (ค่าส่ง + โบนัส)
        $fresh = $order->fresh();
        $this->assertSame(FreshMarketOrder::STATUS_COMPLETED, $fresh->order_status);
        $this->assertSame('released', $fresh->escrow_status);
        // ร้าน: 100 − GP 10 − โบนัส 5 = 85 และ seller_earning ถูกเขียนเป็นยอดที่ได้จริง
        $this->assertEqualsWithDelta(85.0, $this->walletOf($sellerUser), 0.001);
        $this->assertEqualsWithDelta(85.0, (float) $fresh->seller_earning, 0.001);
        $this->assertEqualsWithDelta(round((float) $job->rider_earnings + 5, 2), $this->riderEarningCredits($job), 0.001);
        $this->assertNotNull($data['settlement']);
        $this->assertEqualsWithDelta(85.0, $data['settlement']['seller_amount'], 0.001);
        $this->assertEqualsWithDelta($paid, $data['settlement']['total_paid'], 0.001);
    }

    public function test_fresh_market_dispute_refund_returns_everything_before_completion(): void
    {
        [$service, $listing, $buyer, $sellerUser] = $this->freshMarketFixture();
        $order = $this->placeFreshMarketRiderOrder($service, $listing, $buyer, 'wallet');
        $paid = $order->walletPaidAmount();
        $balanceAfterPay = $this->walletOf($buyer);

        $rider = $this->makeRider();
        $job = $this->makeFreshMarketJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk();
        $this->travel(181)->seconds();
        $this->riderPhoto($rider, $job, 'waited-photo')->assertOk();
        $this->assertSame(FreshMarketOrder::STATUS_DELIVERING, $order->fresh()->order_status, 'วางของแล้วแต่ยังไม่ปิดออเดอร์');

        \Laravel\Sanctum\Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/fresh-market/'.$order->id.'/handover/dispute', ['reason' => 'damaged'])->assertOk();

        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job), ['reason' => 'ของเสียหายตามรูป'])
            ->assertOk();

        $fresh = $order->fresh();
        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $fresh->order_status);
        $this->assertSame('refunded', $fresh->escrow_status);
        $this->assertEqualsWithDelta($balanceAfterPay + $paid, $this->walletOf($buyer), 0.001, 'คืนเต็มจำนวนรวมค่าส่ง');
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame(0.0, $this->riderEarningCredits($job));
        $this->assertEqualsWithDelta(0.0, $this->walletOf($sellerUser), 0.001);
        $this->assertSame(DeliveryHandover::STATUS_REFUNDED, $this->handoverOf($job)->status);
    }

    // =====================================================
    // fixtures ตลาดสด
    // =====================================================

    /**
     * ร้านตลาดสด (GP 10%) + สินค้า 100 บาท + ผู้ซื้อมีเงิน 1,000 · เปิดไรเดอร์
     *
     * @return array{0: FreshMarketService, 1: FreshMarketListing, 2: User, 3: User}
     */
    private function freshMarketFixture(): array
    {
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => true,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();
        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');

        $buyer = $this->makeUser('สมหญิง ตลาดสด', 1000);
        $sellerUser = $this->makeUser('ร้านผัก');

        $seller = FreshMarketSeller::create([
            'user_id' => $sellerUser->id,
            'shop_name' => 'ร้านผักทดสอบ',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7291,
            'longitude' => 100.5210,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        $listing = FreshMarketListing::create([
            'seller_id' => $seller->id,
            'title' => 'ผักบุ้งจีน',
            'price' => 100,
            'unit' => 'กำ',
            'quantity_available' => 10,
            'latitude' => 13.7291,
            'longitude' => 100.5210,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        $service = new FreshMarketService;

        return [$service, $listing->fresh(), $buyer, $sellerUser];
    }

    private function placeFreshMarketRiderOrder(FreshMarketService $service, FreshMarketListing $listing, User $buyer, string $payment): FreshMarketOrder
    {
        return $service->createOrder($buyer, $listing->fresh(), [
            'quantity' => 1,
            'delivery_type' => 'rider',
            'payment_method' => $payment,
            'buyer_latitude' => self::DROP_LAT,
            'buyer_longitude' => self::DROP_LNG,
            'delivery_address' => '99 ถนนทดสอบ แขวงทดสอบ',
        ]);
    }

    /**
     * งานไรเดอร์ของออเดอร์ตลาดสด (ไรเดอร์ถือของ กำลังจัดส่ง)
     */
    private function makeFreshMarketJob(FreshMarketOrder $order, Rider $rider): RiderJob
    {
        $fee = round((float) $order->delivery_fee, 2);
        $earn = round($fee * 0.8, 2);

        $job = new RiderJob;
        $job->forceFill([
            'rider_id' => $rider->id,
            'job_type' => 'fresh_market',
            'source_type' => $order->getMorphClass(),
            'source_id' => $order->id,
            'customer_id' => $order->buyer_id,
            'title' => 'ส่งของตลาดสด '.$order->order_number,
            'pickup_address' => 'ร้านผักทดสอบ',
            'pickup_latitude' => 13.7291,
            'pickup_longitude' => 100.5210,
            'delivery_address' => '99 ถนนทดสอบ',
            'delivery_latitude' => self::DROP_LAT,
            'delivery_longitude' => self::DROP_LNG,
            'total_fee' => $fee,
            'rider_earnings' => $earn,
            'platform_fee' => round($fee - $earn, 2),
            'shop_bonus' => (float) $order->rider_bonus_amount,
            'cod_amount' => 0,
            'status' => 'delivering',
            'handover_required' => true,
            'accepted_at' => now()->subMinutes(30),
            'picked_up_at' => now()->subMinutes(20),
            'tracking_token' => \Illuminate\Support\Str::random(48),
            'tracking_expires_at' => now()->addDay(),
        ])->save();

        FreshMarketOrder::whereKey($order->id)->update([
            'order_status' => FreshMarketOrder::STATUS_DELIVERING,
            'rider_job_id' => $job->id,
            'rider_id' => $rider->id,
            'accepted_at' => now()->subHour(),
            'ready_at' => now()->subMinutes(40),
        ]);

        return $job->fresh();
    }
}
