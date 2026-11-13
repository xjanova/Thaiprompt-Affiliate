<?php

namespace Tests\Feature\Rider;

use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RiderDispatchService;
use App\Services\RiderEarningService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Rider\Fixtures\FakeDeliverableSource;
use Tests\TestCase;

/**
 * ความแม่นยำของตัวเลขไรเดอร์/หน้าร้าน (2026-11-13) — ต้องใช้ MySQL
 *
 *   - หน้ารายได้ไรเดอร์ (GET /api/v1/rider/earnings) ต้องรวมโบนัสจากร้าน = ยอดเดียวกับเงินที่เข้ากระเป๋า
 *   - ค้นหาร้าน/ไรเดอร์ใกล้ตัว: พิกัดซ้อนกับจุดค้นหาพอดีต้องไม่หายจากผลลัพธ์ (acos เกิน 1 → NULL)
 */
#[Group('rider')]
class RiderAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = 13.7291;

    private const PICKUP_LNG = 100.5210;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('rider.handover_enabled', '0', 'boolean', 'rider');
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');
        $this->withHeader('X-App-Build', (string) config('rider.min_app_build'));

        Http::fake();
        Storage::fake('public');
        Storage::fake('local');
        Cache::flush();
        FakeDeliverableSource::reset();
    }

    // =====================================================
    // รายได้ไรเดอร์ = เงินที่เข้ากระเป๋า
    // =====================================================

    public function test_earnings_summary_includes_shop_bonus_and_matches_wallet_credit(): void
    {
        $rider = $this->makeRider();

        // งานที่ร้านเติมโบนัส 15 บาท + งานปกติไม่มีโบนัส
        $withBonus = $this->completeJob($rider, ['shop_bonus' => 15]);
        $plain = $this->completeJob($rider);

        $expected = round(
            (float) $withBonus->rider_earnings + 15 + (float) $plain->rider_earnings,
            2
        );

        // เงินเข้ากระเป๋า = ส่วนแบ่งค่าส่งทั้งสองงาน + โบนัสร้าน
        $balance = round((float) Wallet::where('user_id', $rider->user_id)->value('balance'), 2);
        $this->assertSame($expected, $balance);

        Sanctum::actingAs($rider->user);
        $data = $this->getJson('/api/v1/rider/earnings?period=today')->assertOk()->json('data');

        $this->assertSame(2, $data['completed_jobs']);
        // ยอดรวมของหน้ารายได้ต้องเท่ากับเงินที่เข้ากระเป๋าจริง (เดิมขาดโบนัส 15 บาท)
        $this->assertEqualsWithDelta($expected, $data['gross_earnings'], 0.001);
        $this->assertEqualsWithDelta($expected - 15, $data['delivery_earnings'], 0.001);
        $this->assertEqualsWithDelta(15.0, $data['shop_bonus_earnings'], 0.001);
        $this->assertEqualsWithDelta($expected, $data['total_earnings_all_time'], 0.001);

        // กราฟรายวันรวมกันต้องเท่ายอดรวม
        $this->assertCount(1, $data['daily']);
        $this->assertSame(2, $data['daily'][0]['jobs']);
        $this->assertEqualsWithDelta($expected, $data['daily'][0]['earnings'], 0.001);

        // รายการงานล่าสุด: แยกส่วนแบ่งค่าส่ง / โบนัส / ยอดที่ได้จริง
        $rows = collect($data['recent_jobs'])->keyBy('id');
        $this->assertEqualsWithDelta(15.0, $rows[$withBonus->id]['shop_bonus'], 0.001);
        $this->assertEqualsWithDelta((float) $withBonus->rider_earnings + 15, $rows[$withBonus->id]['rider_total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $rows[$plain->id]['shop_bonus'], 0.001);
        $this->assertEqualsWithDelta((float) $plain->rider_earnings, $rows[$plain->id]['rider_total'], 0.001);
        $this->assertEqualsWithDelta(
            $data['gross_earnings'],
            collect($data['recent_jobs'])->sum('rider_total'),
            0.001
        );
    }

    public function test_earnings_summary_adds_satang_without_float_drift(): void
    {
        $rider = $this->makeRider();

        // 0.10 + 0.20 + 0.30 … ยอดทศนิยมที่บวกแบบ float แล้วเพี้ยนได้
        foreach ([10.10, 20.20, 30.30, 0.07, 0.01] as $earn) {
            $this->completeJob($rider, ['rider_earnings' => $earn, 'shop_bonus' => 0.05]);
        }

        Sanctum::actingAs($rider->user);
        $data = $this->getJson('/api/v1/rider/earnings?period=all')->assertOk()->json('data');

        $this->assertSame(60.68, $data['delivery_earnings']);
        $this->assertSame(0.25, $data['shop_bonus_earnings']);
        $this->assertSame(60.93, $data['gross_earnings']);
        $this->assertSame(60.93, round((float) Wallet::where('user_id', $rider->user_id)->value('balance'), 2));
    }

    // =====================================================
    // ค่าตอบแทนขั้นต่ำที่ไรเดอร์ตั้ง — เทียบกับที่ได้จริง (รวมโบนัสร้าน)
    // =====================================================

    public function test_rider_minimum_pay_counts_the_shop_bonus(): void
    {
        $dispatch = app(RiderDispatchService::class);
        $job = $dispatch->createJobForSource($this->makeSource(), 'fresh_market');
        $share = (float) $job->rider_earnings;

        // ไรเดอร์ตั้งขั้นต่ำสูงกว่าส่วนแบ่งค่าส่ง 10 บาท
        $rider = $this->makeRider(['preferred_min_fee' => $share + 10]);

        // ไม่มีโบนัส → ไม่ถึงขั้นต่ำ ไม่ถูกเสนองาน
        $this->assertNotContains($rider->id, $dispatch->findEligibleRiders($job->fresh(), 5)->pluck('id')->all());

        // ร้านเติมโบนัส 9.99 → ยังขาด 1 สตางค์
        $job->forceFill(['shop_bonus' => 9.99])->save();
        $this->assertNotContains($rider->id, $dispatch->findEligibleRiders($job->fresh(), 5)->pluck('id')->all());

        // ร้านเติมโบนัส 10 → ถึงขั้นต่ำพอดี ต้องถูกเสนองาน
        $job->forceFill(['shop_bonus' => 10])->save();
        $this->assertContains($rider->id, $dispatch->findEligibleRiders($job->fresh(), 5)->pluck('id')->all());
    }

    // =====================================================
    // ร้านตลาดสด: "ร้านได้รับ" ต้องหักต้นทุนไรเดอร์ที่ร้านออกตั้งแต่ออเดอร์ยังไม่ปิด
    // =====================================================

    public function test_fresh_market_seller_net_deducts_rider_costs_until_the_order_is_closed(): void
    {
        $order = new FreshMarketOrder;

        // ออเดอร์เงินพักยังไม่ปิด: ค่าสินค้าหลัง GP 90 · ร้านเติมโบนัส 15 + ออกค่าส่งแทน 40 → ร้านจะได้ 35 (เดิมโชว์ 90)
        $order->forceFill([
            'order_status' => 'ready', 'settlement_deferred' => true,
            'seller_earning' => 90, 'rider_bonus_amount' => 15, 'delivery_subsidy_amount' => 40,
        ]);
        $this->assertSame(
            ['seller_net' => 35.0, 'seller_net_final' => false, 'rider_bonus_amount' => 15.0, 'delivery_subsidy_amount' => 40.0],
            $order->sellerNetPayload()
        );

        // ปิดออเดอร์แล้ว: seller_earning ถูกเขียนเป็นยอดสุทธิ 35 แล้ว → ห้ามหักซ้ำ
        $order->forceFill(['order_status' => FreshMarketOrder::STATUS_COMPLETED, 'seller_earning' => 35]);
        $this->assertSame(35.0, $order->sellerNetPayload()['seller_net']);
        $this->assertTrue($order->sellerNetPayload()['seller_net_final']);

        // ต้นทุนไรเดอร์เกินรายได้ → 0 ไม่ติดลบ
        $order->forceFill(['order_status' => 'ready', 'seller_earning' => 18]);
        $this->assertSame(0.0, $order->sellerNetPayload()['seller_net']);

        // ออเดอร์ที่ไม่ใช่เงินพัก (นัดรับเอง) → ไม่หักอะไร
        $order->forceFill(['settlement_deferred' => false, 'seller_earning' => 90]);
        $this->assertSame(90.0, $order->sellerNetPayload()['seller_net']);
    }

    // =====================================================
    // รัศมีรับงานที่ไรเดอร์ตั้ง = ระยะจากตัวไรเดอร์ถึงจุดรับของ (ตรงกับป้ายในแอป)
    // =====================================================

    public function test_preferred_radius_is_measured_from_the_rider_to_the_pickup(): void
    {
        $dispatch = app(RiderDispatchService::class);
        // งานนี้ส่งไกล ~2 กม. (ร้าน → ลูกค้า) แต่ร้านอยู่ห่างไรเดอร์ราว 150 ม.
        $job = $dispatch->createJobForSource($this->makeSource(), 'fresh_market');
        $this->assertGreaterThan(1.0, (float) $job->distance_km);

        // ตั้ง "รับงานไม่เกิน 1 กม. จากตำแหน่งคุณ" → ร้านอยู่ใกล้ ต้องได้รับข้อเสนอ (เดิมเทียบกับระยะส่ง 2 กม. แล้วถูกตัดออก)
        $near = $this->makeRider(['preferred_radius_km' => 1]);
        // ไรเดอร์อีกคนตั้ง 1 กม. เหมือนกัน แต่อยู่ห่างร้านราว 3 กม. → ไม่ได้รับข้อเสนอ
        $far = $this->makeRider([
            'preferred_radius_km' => 1,
            'last_latitude' => self::PICKUP_LAT + 0.027,
            'last_longitude' => self::PICKUP_LNG,
        ]);

        $ids = $dispatch->findEligibleRiders($job->fresh(), 5)->pluck('id')->all();

        $this->assertContains($near->id, $ids);
        $this->assertNotContains($far->id, $ids);
    }

    // =====================================================
    // ค้นหาใกล้ตัว — พิกัดซ้อนกันพอดี
    // =====================================================

    public function test_shop_at_the_exact_search_point_is_found_with_zero_distance(): void
    {
        // พิกัด 4 ชุดแรกทำให้ค่าใน acos() เกิน 1 เล็กน้อย → MySQL คืน NULL ถ้าไม่จำกัดช่วง
        // (สุ่ม 100,000 จุดในไทย เจอแบบนี้ราว 5%)
        $points = [[19.357889, 102.209183], [8.29475, 104.346495], [15.92482, 100.296803], [19.412455, 102.239579], [13.7291, 100.5210]];

        foreach ($points as $i => [$lat, $lng]) {
            $shop = FreshMarketSeller::create([
                'user_id' => User::factory()->create()->id,
                'shop_name' => 'แผงที่ '.$i,
                'phone' => '08900000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'address' => 'ตลาดทดสอบ',
                'latitude' => $lat,
                'longitude' => $lng,
                'is_active' => true,
                'is_suspended' => false,
                'is_verified' => true,
                'subscription_type' => 'free',
            ]);

            $found = FreshMarketSeller::query()->nearby($lat, $lng, 1)->get()->firstWhere('id', $shop->id);

            $this->assertNotNull($found, "ร้านที่พิกัด {$lat},{$lng} ต้องอยู่ในผลค้นหาจุดเดียวกัน");
            $this->assertNotNull($found->distance_km);
            $this->assertEqualsWithDelta(0.0, (float) $found->distance_km, 0.001);
        }
    }

    public function test_rider_at_the_exact_search_point_is_found_with_zero_distance(): void
    {
        foreach ([[19.357889, 102.209183], [8.29475, 104.346495], [15.92482, 100.296803]] as [$lat, $lng]) {
            $rider = $this->makeRider(['last_latitude' => $lat, 'last_longitude' => $lng]);

            $found = Rider::query()->nearby($lat, $lng, 1)->get()->firstWhere('id', $rider->id);

            $this->assertNotNull($found, "ไรเดอร์ที่พิกัด {$lat},{$lng} ต้องอยู่ในผลค้นหาจุดเดียวกัน");
            $this->assertEqualsWithDelta(0.0, (float) $found->distance_km, 0.001);
        }

        // ไรเดอร์ที่ยังไม่เคยส่งพิกัด ไม่ถูกนับว่าอยู่ใกล้
        $noFix = $this->makeRider(['last_latitude' => null, 'last_longitude' => null]);
        $this->assertNull(Rider::query()->nearby(13.7291, 100.5210, 50)->get()->firstWhere('id', $noFix->id));
    }

    // =====================================================
    // Helpers
    // =====================================================

    /**
     * สร้างงาน → ให้ไรเดอร์ถือ → ปิดงาน → เคลียร์เงิน
     *
     * @param  array<string, mixed>  $jobOverrides  ค่าที่เขียนทับบนงานก่อนเคลียร์เงิน (rider_earnings, shop_bonus)
     */
    private function completeJob(Rider $rider, array $jobOverrides = []): RiderJob
    {
        $job = app(RiderDispatchService::class)->createJobForSource($this->makeSource(), 'fresh_market');

        $job->forceFill(array_merge([
            'rider_id' => $rider->id,
            'status' => 'completed',
            'accepted_at' => now(),
            'completed_at' => now(),
        ], $jobOverrides))->save();

        $this->assertTrue(app(RiderEarningService::class)->settle($job, false));

        return $job->fresh();
    }

    private function makeSource(): FakeDeliverableSource
    {
        $holder = User::factory()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        FakeDeliverableSource::configure((int) $holder->id, [
            'pickup' => [
                'name' => 'ร้านป้าแดง',
                'address' => 'ตลาดบางรัก เขตบางรัก กรุงเทพมหานคร',
                'latitude' => self::PICKUP_LAT,
                'longitude' => self::PICKUP_LNG,
                'phone' => '0811111111',
                'notes' => null,
            ],
            'dropoff' => [
                'name' => 'คุณลูกค้า',
                'address' => '88/8 ซ.เจริญกรุง 30 แขวงบางรัก เขตบางรัก กรุงเทพมหานคร 10500',
                'latitude' => 13.7400,
                'longitude' => 100.5300,
                'phone' => '0822222222',
                'notes' => null,
            ],
            'cod' => 0,
            'party' => [(int) $buyer->id, (int) $seller->id],
            'customer_id' => (int) $buyer->id,
        ]);

        return FakeDeliverableSource::findOrFail($holder->id);
    }

    private function makeRider(array $overrides = []): Rider
    {
        $user = User::factory()->create();
        $user->forceFill(['kyc_status' => 'approved', 'kyc_verified_at' => now()])->save();

        $rider = new Rider;
        $rider->forceFill(array_merge([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'gps_permission_granted' => true,
            'last_latitude' => self::PICKUP_LAT + 0.001,
            'last_longitude' => self::PICKUP_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ], $overrides))->save();

        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }
}
