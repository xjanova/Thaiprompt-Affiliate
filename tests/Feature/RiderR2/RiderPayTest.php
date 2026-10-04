<?php

namespace Tests\Feature\RiderR2;

use App\Models\AiApiKey;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\RiderDispatchService;
use App\Services\RiderPay\RiderPayAdviceWriter;
use App\Services\RiderPay\RiderPayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 🛵 ไรเดอร์รอบ 2 — GET/PUT /seller/rider-pay และ /fresh-market/seller/rider-pay
 *
 * ตรวจ: รูปแบบ RiderPay ตามสัญญา, ตัวเลขประมาณสมเหตุสมผล (prod ยังไม่มีงาน), ข้อมูลจริง ≥ 20 งาน,
 *       ทดลองค่าไม่บันทึก, PUT ตรวจ 0..100 ทศนิยม 2 ตำแหน่ง, ด่านผู้ขาย (คนอื่น/ไม่ใช่ร้าน/ระงับ),
 *       AI เรียบเรียงได้แต่ห้ามเปลี่ยนตัวเลข + หมดเวลา → ข้อความจากกฎ
 */
class RiderPayTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Notification::fake();
        Cache::flush();
        // ห้ามยิงเครือข่ายจริง (AI/Valhalla) — เทสต์ที่ต้องใช้ fake เอง
        Http::preventStrayRequests();
        FreshMarketSetting::clearCache();

        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00:00', 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * ผู้ขายที่ผ่านด่าน SellerPanelGate (role seller + KYC + ร้านเปิด + แพ็กเกจ)
     *
     * @return array{0: User, 1: VendorStore}
     */
    private function approvedSeller(array $storeOverrides = []): array
    {
        [$seller, $store] = $this->makeSellerWithStore($storeOverrides);
        $seller->forceFill(['kyc_status' => 'approved'])->save();

        return [$seller->fresh(), $store];
    }

    private function fmSeller(?User $user = null, array $overrides = []): FreshMarketSeller
    {
        $user ??= User::factory()->create();

        return FreshMarketSeller::create(array_merge([
            'user_id' => $user->id, 'shop_name' => 'ร้านตลาดสด '.$user->id, 'phone' => '0812345678', 'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7563, 'longitude' => 100.5018, 'is_active' => true, 'is_suspended' => false, 'is_verified' => true,
            'subscription_type' => 'free',
        ], $overrides));
    }

    // =====================================================
    // รูปแบบ + ค่าประมาณ
    // =====================================================

    public function test_shop_rider_pay_payload_matches_contract_with_sane_estimates(): void
    {
        [$seller] = $this->approvedSeller(['rider_bonus' => 10]);
        Sanctum::actingAs($seller);

        $data = $this->getJson('/api/v1/seller/rider-pay')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [
                'settings' => ['rider_bonus', 'rider_bonus_peak', 'rider_free_delivery'],
                'base' => ['base_fee', 'per_km_fee', 'free_km', 'min_fee', 'rider_share_percent', 'max_distance_km', 'night_surcharge', 'peak_surcharge', 'peak_hours'],
                'bands' => [['key', 'label', 'from_km', 'to_km', 'fee_min', 'fee_max', 'rider_earn_min', 'rider_earn_max',
                    'accept_rate_5min', 'accept_rate_with_bonus', 'sample_size', 'basis']],
                'advice' => ['headline', 'text', 'suggested_bonus', 'suggested_bonus_peak', 'suggest_free_delivery', 'source', 'generated_at'],
                'customer_distance',
            ]])
            ->json('data');

        $this->assertEquals(10, $data['settings']['rider_bonus']);
        $this->assertSame([[11, 13], [17, 19]], $data['base']['peak_hours']);
        $this->assertSame(['0-2', '2-5', '5-8', '8+'], array_column($data['bands'], 'key'));
        $this->assertNull($data['bands'][3]['to_km']);
        $this->assertNull($data['customer_distance'], 'ร้านใหม่ยังไม่มีออเดอร์ส่งไรเดอร์');

        $rates = array_column($data['bands'], 'accept_rate_5min');
        foreach ($data['bands'] as $band) {
            $this->assertSame('estimate', $band['basis']);
            $this->assertSame(0, $band['sample_size']);
            $this->assertGreaterThan($band['accept_rate_5min'], $band['accept_rate_with_bonus'], 'เติมโบนัสแล้วโอกาสต้องเพิ่ม');
            $this->assertLessThanOrEqual(0.98, $band['accept_rate_with_bonus']);
        }
        // งานไกลรับยากกว่าเสมอ และอยู่ในช่วงที่สมเหตุสมผล
        $this->assertGreaterThan($rates[1], $rates[0]);
        $this->assertGreaterThan($rates[2], $rates[1]);
        $this->assertGreaterThan($rates[3], $rates[2]);
        $this->assertGreaterThanOrEqual(0.85, $rates[0]);
        $this->assertLessThanOrEqual(0.40, $rates[3]);

        // ค่าส่งตามสูตร (30 + 10 บาท/กม. หลัง 2 กม.) · ไรเดอร์ได้ 80% + โบนัสร้าน
        $this->assertEquals(30, $data['bands'][1]['fee_min']);
        $this->assertEquals(60, $data['bands'][1]['fee_max']);
        $this->assertEquals(58, $data['bands'][1]['rider_earn_max'], '48 + โบนัส 10');
        $this->assertEquals(160, $data['bands'][3]['fee_max'], 'ถึงระยะส่งสูงสุด 15 กม.');

        $advice = $data['advice'];
        $this->assertSame('rules', $advice['source'], 'AI ปิดในเทสต์');
        $this->assertContains((int) $advice['suggested_bonus'], RiderPayService::BONUS_STEPS);
        $this->assertGreaterThanOrEqual($advice['suggested_bonus'], $advice['suggested_bonus_peak']);
        $this->assertFalse($advice['suggest_free_delivery']);
        $this->assertNotSame('', $advice['headline']);
        $this->assertMatchesRegularExpression('/\p{Thai}/u', $advice['text']);
    }

    public function test_rules_advice_is_deterministic(): void
    {
        [$seller] = $this->approvedSeller();
        Sanctum::actingAs($seller);

        $first = $this->getJson('/api/v1/seller/rider-pay')->json('data.advice');
        $second = $this->getJson('/api/v1/seller/rider-pay')->json('data.advice');

        unset($first['generated_at'], $second['generated_at']);
        $this->assertSame($first, $second);
    }

    // =====================================================
    // ทดลองค่า + บันทึก
    // =====================================================

    public function test_preview_recomputes_without_saving(): void
    {
        [$seller, $store] = $this->approvedSeller();
        Sanctum::actingAs($seller);

        $base = $this->getJson('/api/v1/seller/rider-pay')->json('data.bands.2');
        $preview = $this->getJson('/api/v1/seller/rider-pay?bonus=20&bonus_peak=25.5&free_delivery=true')
            ->assertOk()
            ->assertJsonPath('data.settings.rider_bonus', 20)
            ->assertJsonPath('data.settings.rider_bonus_peak', 25.5)
            ->assertJsonPath('data.settings.rider_free_delivery', true)
            ->json('data.bands.2');

        $this->assertGreaterThan($base['accept_rate_with_bonus'], $preview['accept_rate_with_bonus']);
        $this->assertEquals($base['rider_earn_min'] + 20, $preview['rider_earn_min']);

        $store->refresh();
        $this->assertEquals(0, (float) $store->rider_bonus, 'ทดลองค่าห้ามบันทึก');
        $this->assertEquals(0, (float) $store->rider_bonus_peak);
        $this->assertFalse((bool) $store->rider_free_delivery);

        $this->getJson('/api/v1/seller/rider-pay?bonus=150')->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_put_saves_and_validates(): void
    {
        [$seller, $store] = $this->approvedSeller();
        Sanctum::actingAs($seller);

        $this->putJson('/api/v1/seller/rider-pay', ['rider_bonus' => 12.5, 'rider_bonus_peak' => 20, 'rider_free_delivery' => true])
            ->assertOk()
            ->assertJsonPath('data.settings.rider_bonus', 12.5)
            ->assertJsonPath('data.settings.rider_bonus_peak', 20)
            ->assertJsonPath('data.settings.rider_free_delivery', true);

        $store->refresh();
        $this->assertEquals(12.5, (float) $store->rider_bonus);
        $this->assertEquals(20, (float) $store->rider_bonus_peak);
        $this->assertTrue((bool) $store->rider_free_delivery);

        // กดซ้ำค่าเดิม (double tap) → ผลเท่าเดิม
        $this->putJson('/api/v1/seller/rider-pay', ['rider_bonus' => 12.5])->assertOk()->assertJsonPath('data.settings.rider_bonus', 12.5);

        foreach ([['rider_bonus' => 100.01], ['rider_bonus' => -1], ['rider_bonus_peak' => 10.123], ['rider_bonus' => 'abc'], ['rider_free_delivery' => 'maybe'], []] as $bad) {
            $this->putJson('/api/v1/seller/rider-pay', $bad)
                ->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('code', 'VALIDATION_ERROR');
        }

        $this->assertEquals(12.5, (float) $store->fresh()->rider_bonus, 'ค่าไม่ถูกต้องห้ามแตะของเดิม');
    }

    // =====================================================
    // ด่าน
    // =====================================================

    public function test_shop_gate_blocks_non_sellers_and_only_touches_own_store(): void
    {
        $buyer = $this->makeBuyer();
        Sanctum::actingAs($buyer);
        $this->getJson('/api/v1/seller/rider-pay')->assertStatus(403)->assertJsonPath('code', 'NOT_A_SELLER');
        $this->putJson('/api/v1/seller/rider-pay', ['rider_bonus' => 10])->assertStatus(403);

        // ผู้ขายที่ยังไม่ผ่าน KYC
        [$noKyc] = $this->makeSellerWithStore();
        Sanctum::actingAs($noKyc);
        $this->getJson('/api/v1/seller/rider-pay')->assertStatus(403)->assertJsonPath('code', 'SELLER_KYC_REQUIRED');

        // ร้านถูกระงับ
        [$suspended] = $this->approvedSeller(['status' => 'suspended']);
        Sanctum::actingAs($suspended);
        $this->putJson('/api/v1/seller/rider-pay', ['rider_bonus' => 10])->assertStatus(403)->assertJsonPath('code', 'STORE_SUSPENDED');

        // ร้าน A แก้ได้เฉพาะร้าน A (ไม่มี id ใน URL — ร้าน B ไม่เปลี่ยน)
        [$sellerA, $storeA] = $this->approvedSeller();
        [, $storeB] = $this->approvedSeller(['rider_bonus' => 7]);
        Sanctum::actingAs($sellerA);
        $this->putJson('/api/v1/seller/rider-pay', ['rider_bonus' => 30])->assertOk();
        $this->assertEquals(30, (float) $storeA->fresh()->rider_bonus);
        $this->assertEquals(7, (float) $storeB->fresh()->rider_bonus);
    }

    public function test_unauthenticated_is_rejected(): void
    {
        $this->getJson('/api/v1/seller/rider-pay')->assertStatus(401);
        $this->getJson('/api/v1/fresh-market/seller/rider-pay')->assertStatus(401);
    }

    public function test_fresh_market_rider_pay_uses_callers_own_shop(): void
    {
        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);
        $this->getJson('/api/v1/fresh-market/seller/rider-pay')->assertStatus(403)->assertJsonPath('code', 'NOT_SELLER');
        $this->putJson('/api/v1/fresh-market/seller/rider-pay', ['rider_bonus' => 5])->assertStatus(403);

        $owner = User::factory()->create();
        $shop = $this->fmSeller($owner);
        $other = $this->fmSeller(null, ['rider_bonus' => 3]);
        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/fresh-market/seller/rider-pay')
            ->assertOk()
            ->assertJsonPath('data.settings.rider_bonus', 0)
            ->assertJsonCount(4, 'data.bands');

        $this->putJson('/api/v1/fresh-market/seller/rider-pay', ['rider_bonus' => 8, 'rider_free_delivery' => true])
            ->assertOk()
            ->assertJsonPath('data.settings.rider_bonus', 8)
            ->assertJsonPath('data.settings.rider_free_delivery', true);

        $this->assertEquals(8, (float) $shop->fresh()->rider_bonus);
        $this->assertEquals(3, (float) $other->fresh()->rider_bonus);

        // ร้านถูกระงับ: ดูได้ แก้ไม่ได้
        $shop->forceFill(['is_suspended' => true])->save();
        $this->getJson('/api/v1/fresh-market/seller/rider-pay')->assertOk();
        $this->putJson('/api/v1/fresh-market/seller/rider-pay', ['rider_bonus' => 9])->assertStatus(403)->assertJsonPath('code', 'SELLER_SUSPENDED');
        $this->assertEquals(8, (float) $shop->fresh()->rider_bonus);
    }

    // =====================================================
    // ข้อมูลจริง
    // =====================================================

    public function test_band_switches_to_data_basis_with_20_jobs_and_customer_distance_appears(): void
    {
        [$seller, $store] = $this->approvedSeller(['rider_delivery_enabled' => true]);
        $product = $this->makeProduct($seller, $store, ['price' => 400, 'stock_quantity' => 50]);
        $buyer = $this->makeBuyer();
        $address = $this->makeAddress($buyer, true);

        // งานต้นแบบ 1 งานจากออเดอร์จริงของร้าน แล้วคัดลอกเป็นตัวอย่าง 25 งานในช่วง 0–2 กม.
        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id, 'payment_method' => 'cod', 'delivery_method' => 'rider',
            'shipping_fee' => 30, 'total_amount' => 430,
            'shipping_address_id' => $address->id, 'shipping_address_snapshot' => $address->toSnapshot(),
        ]);
        $template = (array) DB::table('rider_jobs')->find(app(RiderDispatchService::class)->createJobForSource($order, 'shop_delivery')->id);
        DB::table('rider_jobs')->where('id', $template['id'])->delete();
        unset($template['id']);

        $created = now()->subHours(2);
        for ($i = 0; $i < 25; $i++) {
            $sourceOrder = $i < 6 ? $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
                'store_id' => $store->id, 'payment_method' => 'cod', 'delivery_method' => 'rider', 'shipping_fee' => 30, 'total_amount' => 430,
            ]) : null;

            DB::table('rider_jobs')->insert(array_merge($template, [
                'job_number' => 'RJT'.Str::upper(Str::random(10)),
                'tracking_token' => Str::random(48),
                'source_id' => $sourceOrder?->id ?? $order->id,
                'status' => 'completed',
                'distance_km' => 1.0 + ($i % 5) * 0.2,
                'rider_earnings' => 24,
                'shop_bonus' => 0,
                'created_at' => $created,
                'updated_at' => $created,
                // 20 งานมีคนรับใน 3 นาที · 5 งานรับช้า 9 นาที → 80%
                'accepted_at' => $created->copy()->addMinutes($i < 20 ? 3 : 9),
            ]));
        }

        Sanctum::actingAs($seller);
        $data = $this->getJson('/api/v1/seller/rider-pay')->assertOk()->json('data');

        $near = $data['bands'][0];
        $this->assertSame('data', $near['basis']);
        $this->assertSame(25, $near['sample_size']);
        $this->assertEquals(0.8, $near['accept_rate_5min']);
        $this->assertSame('estimate', $data['bands'][1]['basis'], 'ช่วงอื่นยังไม่พอ 20 งาน');

        // ระยะลูกค้า: 6 ออเดอร์ (≥ 5) ของร้านนี้
        $this->assertNotNull($data['customer_distance']);
        $this->assertGreaterThanOrEqual(5, $data['customer_distance']['sample_size']);
        $this->assertLessThanOrEqual($data['customer_distance']['p90_km'], $data['customer_distance']['p50_km']);
        $this->assertLessThan(2.0, $data['customer_distance']['p90_km']);
    }

    // =====================================================
    // AI เรียบเรียงข้อความ
    // =====================================================

    private function enableAi(): void
    {
        config(['services.rider_pay_ai.enabled' => true, 'services.rider_pay_ai.timeout' => 3]);
        FreshMarketSetting::create(['brand_name' => 'ตลาดสดทดสอบ', 'ai_provider' => 'groq', 'ai_model' => 'llama-3.3-70b-versatile']);
        FreshMarketSetting::clearCache();
        AiApiKey::create([
            'name' => 'groq-test',
            'provider' => 'groq',
            'api_key' => 'gsk_test_key_for_rider_pay',
            'is_active' => true,
            'priority' => 10,
            'last_test_passed_at' => now(),
        ]);
    }

    public function test_ai_phrases_advice_but_numbers_must_come_from_the_system(): void
    {
        $this->enableAi();
        [$seller] = $this->approvedSeller();
        Sanctum::actingAs($seller);

        // ตัวเลขที่ระบบแนะนำ (คำนวณตรงจาก service — preview ไม่เรียก AI)
        $b = (int) app(RiderPayService::class)->build(VendorStore::where('user_id', $seller->id)->firstOrFail(), ['rider_bonus' => 0])['advice']['suggested_bonus'];
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'headline' => "เติมโบนัส {$b} บาทดีไหมคะ",
            'text' => "ร้านเติมโบนัส {$b} บาทต่อออเดอร์ ไรเดอร์จะรับงานไวขึ้นค่ะ",
        ], JSON_UNESCAPED_UNICODE)]]]])]);

        $rules = $this->getJson('/api/v1/seller/rider-pay?bonus=0')->assertJsonPath('data.advice.source', 'rules')->json('data.advice');
        Http::assertNothingSent();

        $advice = $this->getJson('/api/v1/seller/rider-pay')->assertOk()->json('data.advice');
        $this->assertSame('ai', $advice['source']);
        $this->assertSame("เติมโบนัส {$b} บาทดีไหมคะ", $advice['headline']);
        $this->assertSame($rules['suggested_bonus'], $advice['suggested_bonus'], 'AI ห้ามเปลี่ยนตัวเลขที่แนะนำ');

        // cache 6 ชม. → ไม่ถามซ้ำ
        $this->getJson('/api/v1/seller/rider-pay')->assertJsonPath('data.advice.source', 'ai');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer gsk_test_key_for_rider_pay'));
    }

    public function test_ai_inventing_numbers_or_timing_out_falls_back_to_rules(): void
    {
        $this->enableAi();
        [$seller, $store] = $this->approvedSeller();
        Sanctum::actingAs($seller);

        // ครั้งแรก AI แต่งตัวเลขเอง · ครั้งที่สอง AI หมดเวลา
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            if ($calls === 1) {
                return Http::response(['choices' => [['message' => ['content' => '{"headline":"เติมโบนัส 77 บาท","text":"ไรเดอร์จะรับงาน 99% แน่นอนค่ะ"}']]]]);
            }

            throw new ConnectionException('cURL error 28: timed out');
        });

        $advice = $this->getJson('/api/v1/seller/rider-pay')->assertOk()->json('data.advice');
        $this->assertSame('rules', $advice['source'], 'AI แต่งตัวเลขเอง → ทิ้ง');
        $this->assertStringNotContainsString('77', $advice['headline']);

        // ร้านเปลี่ยนค่า (ข้อมูลชุดใหม่) + AI หมดเวลา → ข้อความจากกฎ ไม่ error
        $store->forceFill(['rider_bonus' => 5])->save();
        $this->getJson('/api/v1/seller/rider-pay')
            ->assertOk()
            ->assertJsonPath('data.advice.source', 'rules');
        $this->assertSame(2, $calls);

        // AI ล้มกับข้อมูลชุดนี้แล้ว → 15 นาทีไม่ถามซ้ำ
        $this->getJson('/api/v1/seller/rider-pay')->assertJsonPath('data.advice.source', 'rules');
        $this->assertSame(2, $calls);
    }

    public function test_ai_output_validator_rules(): void
    {
        $writer = new RiderPayAdviceWriter;
        $allowed = $writer->allowedNumbers(['bonus' => 15, 'rate' => 64, 'hours' => '11:00–13:00'], ['headline' => 'แนะนำ 15 บาท', 'text' => 'ระยะ 2–5 กม.']);

        $this->assertNotNull($writer->validate('```json {"headline":"แนะนำ 15 บาท","text":"ช่วง 11:00–13:00 โอกาส 64% ค่ะ"} ```', $allowed));
        $this->assertNull($writer->validate('{"headline":"แนะนำ 16 บาท","text":"ค่ะ"}', $allowed), 'ตัวเลขนอกชุด');
        $this->assertNull($writer->validate('{"headline":"แนะนำ 15 บาท","text":"ดูที่ https://example.com"}', $allowed), 'ห้ามลิงก์');
        $this->assertNull($writer->validate('{"headline":"Hello","text":"English only"}', $allowed), 'ต้องเป็นภาษาไทย');
        $this->assertNull($writer->validate('not json', $allowed));
        $this->assertNull($writer->validate('{"headline":"'.str_repeat('ก', 61).'","text":"ค่ะ"}', $allowed), 'หัวข้อยาวเกิน');

        $clean = $writer->validate('{"headline":"แนะนำ 15 บาท 🚀","text":"ประโยคหนึ่ง. ประโยคสอง. ประโยคสาม. ประโยคสี่."}', $allowed);
        $this->assertSame('แนะนำ 15 บาท', $clean['headline'], 'ตัดอีโมจิ');
        $this->assertSame('ประโยคหนึ่ง. ประโยคสอง. ประโยคสาม.', $clean['text'], 'ไม่เกิน 3 ประโยค');

        $this->assertSame('10.5', RiderPayAdviceWriter::normalizeNumber('10.50'));
        $this->assertSame('1000', RiderPayAdviceWriter::normalizeNumber('1,000'));
        $this->assertSame('0', RiderPayAdviceWriter::normalizeNumber('00'));
    }
}
