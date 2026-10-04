<?php

namespace Tests\Feature\RiderR2;

use App\Models\Rider;
use App\Models\Setting;
use App\Models\User;
use App\Services\DeliveryFeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\RiderR2\Concerns\BuildsRiderSocialFixtures;
use Tests\TestCase;

/**
 * ไรเดอร์ใกล้ฉัน GET /api/v1/riders/nearby (ไรเดอร์รอบ 2 — เลน social) — ต้องใช้ MySQL
 *
 * - เห็นเฉพาะไรเดอร์ approved + online/busy + show_on_nearby + ยินยอมแชร์ตำแหน่ง + พิกัดสด + ในรัศมี (ไม่รวมตัวเอง)
 * - ไม่มีพิกัดจริง/เบอร์โทร/ทะเบียนเต็มหลุดออกไป · ตำแหน่งเบลอคงที่ในช่วง 10 นาที (เรียกซ้ำได้จุดเดิม)
 * - ตรวจพิกัด + จำกัด 30 ครั้ง/นาที
 * - ไรเดอร์เปิด/ปิดการแสดงตัวได้ (PUT /rider/profile) และเห็นหัวใจรวมใน GET /rider/status
 */
#[Group('rider')]
class NearbyRidersTest extends TestCase
{
    use BuildsRiderSocialFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Cache::flush();
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Carbon::setTestNow(Carbon::parse('2026-10-04 12:03:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_only_visible_online_riders_within_radius_are_listed(): void
    {
        $viewer = User::factory()->create();

        $visible = $this->makeRider();
        $busy = $this->makeRider(['availability' => 'busy']);
        $this->makeJobFor($this->makeShopOrder(User::factory()->create()), $busy, User::factory()->create(), 'delivering');

        $hidden = [
            'offline' => $this->makeRider(['availability' => 'offline']),
            'opted_out' => $this->makeRider(['show_on_nearby' => false]),
            'stale' => $this->makeRider(['last_location_update' => now()->subMinutes(30)]),
            'no_consent' => $this->makeRider(['share_location_consent_at' => null]),
            'pending' => $this->makeRider(['status' => 'pending']),
            'suspended' => $this->makeRider(['status' => 'suspended', 'suspended_at' => now()]),
            'far' => $this->makeRider(['last_latitude' => self::BASE_LAT + 0.06, 'last_longitude' => self::BASE_LNG]),
            'service_only' => $this->makeRider(['rider_type' => 'service']),
            'self' => $this->makeRider([], $viewer),
        ];
        $blockedUser = User::factory()->create();
        $blockedUser->forceFill(['blocked_at' => now()])->save();
        $hidden['blocked'] = $this->makeRider([], $blockedUser);

        Sanctum::actingAs($viewer);

        $data = $this->getJson('/api/v1/riders/nearby?lat='.self::BASE_LAT.'&lng='.self::BASE_LNG)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fuzz_m', 200)
            ->assertJsonPath('data.lock_min_hearts', 11)
            ->json('data');

        $this->assertEquals(3.0, $data['radius_km']);
        $ids = array_column($data['riders'], 'id');
        sort($ids);
        $expected = [$visible->id, $busy->id];
        sort($expected);
        $this->assertSame($expected, $ids);

        $byId = array_column($data['riders'], null, 'id');
        $this->assertSame('available', $byId[$visible->id]['status']);
        $this->assertSame('busy', $byId[$busy->id]['status']);
        $this->assertFalse($byId[$visible->id]['is_favorite']);
        $this->assertSame(0, $byId[$visible->id]['hearts_from_me']);
        $this->assertFalse($byId[$visible->id]['can_lock']);

        foreach (array_keys($hidden) as $why) {
            $this->assertNotContains($hidden[$why]->id, $ids, "ไรเดอร์ {$why} ต้องไม่โผล่บนแผนที่");
        }
    }

    public function test_positions_are_fuzzed_stable_within_window_and_leak_no_private_data(): void
    {
        $viewer = User::factory()->create();
        $rider = $this->makeRider([
            'last_latitude' => 13.7303123,
            'last_longitude' => 100.5223456,
        ]);

        Sanctum::actingAs($viewer);
        $url = '/api/v1/riders/nearby?lat='.self::BASE_LAT.'&lng='.self::BASE_LNG;

        $first = $this->getJson($url)->assertOk()->json('data.riders.0');
        $raw = $this->getJson($url)->assertOk()->getContent();

        // ไม่มีพิกัดจริง / เบอร์โทร / ทะเบียนเต็ม / ชื่อเต็ม
        foreach (['13.7303123', '100.5223456', '13.73031', '100.52234', $rider->phone, '1กข 1234', 'สมชาย ใจดี', 'last_latitude', '"phone"'] as $secret) {
            $this->assertStringNotContainsString((string) $secret, $raw, "ห้ามหลุด: {$secret}");
        }
        $this->assertSame('สมชาย จ.', $first['display_name']);
        $this->assertSame('•กข ••34', $first['plate_masked']);
        $this->assertSame('มอเตอร์ไซค์ Honda แดง', $first['vehicle_label']);

        // เบลอจริง แต่ไม่ไกลเกิน ~1.5 เท่าของช่องตาราง
        $offsetKm = DeliveryFeeCalculator::haversineKm(13.7303123, 100.5223456, $first['approx_latitude'], $first['approx_longitude']);
        $this->assertGreaterThan(0.001, $offsetKm, 'ต้องไม่ใช่ตำแหน่งจริง');
        $this->assertLessThan(0.3, $offsetKm);

        // distance_km = ระยะจากจุดเบลอ ปัด 0.1
        $expected = round(DeliveryFeeCalculator::haversineKm(self::BASE_LAT, self::BASE_LNG, $first['approx_latitude'], $first['approx_longitude']), 1);
        $this->assertEqualsWithDelta($expected, $first['distance_km'], 0.0001);

        // ช่วง 10 นาทีเดียวกัน: เรียกซ้ำ + ไรเดอร์ขยับนิดเดียว (ยังอยู่ช่องเดิม) → จุดเดิมเป๊ะ
        Carbon::setTestNow(Carbon::parse('2026-10-04 12:09:30'));
        $rider->forceFill(['last_latitude' => 13.7303200, 'last_longitude' => 100.5223500, 'last_location_update' => now()])->save();
        $again = $this->getJson($url)->assertOk()->json('data.riders.0');
        $this->assertSame([$first['approx_latitude'], $first['approx_longitude']], [$again['approx_latitude'], $again['approx_longitude']]);

        // ช่วงถัดไป → ค่าเลื่อนใหม่ (ยังอยู่ในกรอบเบลอ)
        Carbon::setTestNow(Carbon::parse('2026-10-04 12:13:00'));
        $rider->forceFill(['last_location_update' => now()])->save();
        $next = $this->getJson($url)->assertOk()->json('data.riders.0');
        $this->assertNotSame([$first['approx_latitude'], $first['approx_longitude']], [$next['approx_latitude'], $next['approx_longitude']]);
        $this->assertLessThan(0.3, DeliveryFeeCalculator::haversineKm(13.73032, 100.52235, $next['approx_latitude'], $next['approx_longitude']));
    }

    public function test_favorite_flags_and_can_lock_show_on_nearby_card(): void
    {
        $viewer = User::factory()->create();
        $rider = $this->makeRider();
        $this->giveHearts($rider, $viewer, 11);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/riders/nearby?lat='.self::BASE_LAT.'&lng='.self::BASE_LNG)
            ->assertOk()
            ->assertJsonPath('data.riders.0.id', $rider->id)
            ->assertJsonPath('data.riders.0.is_favorite', true)
            ->assertJsonPath('data.riders.0.hearts_from_me', 11)
            ->assertJsonPath('data.riders.0.hearts_total', 11)
            ->assertJsonPath('data.riders.0.can_lock', true);
    }

    public function test_validates_coordinates_and_requires_auth(): void
    {
        $this->getJson('/api/v1/riders/nearby?lat=13.7&lng=100.5')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/riders/nearby')
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
        $this->getJson('/api/v1/riders/nearby?lat=95&lng=100.5')
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
        $this->getJson('/api/v1/riders/nearby?lat=0&lng=0')
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        // ชื่อช่องแบบเต็มก็รับ
        $this->getJson('/api/v1/riders/nearby?latitude=13.7291&longitude=100.5210')->assertOk();
    }

    public function test_nearby_is_rate_limited(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $url = '/api/v1/riders/nearby?lat='.self::BASE_LAT.'&lng='.self::BASE_LNG;

        for ($i = 0; $i < 30; $i++) {
            $this->getJson($url)->assertOk();
        }

        $this->getJson($url)->assertStatus(429);
    }

    public function test_rider_can_toggle_show_on_nearby_and_sees_hearts_count(): void
    {
        $rider = $this->makeRider();
        $buyer = User::factory()->create();
        $this->giveHearts($rider, $buyer, 3);

        Sanctum::actingAs($rider->user);

        $this->getJson('/api/v1/rider/status')
            ->assertOk()
            ->assertJsonPath('data.rider.show_on_nearby', true)
            ->assertJsonPath('data.rider.hearts_count', 3);

        // สลับอย่างเดียว ไม่ต้องส่งโปรไฟล์ทั้งชุด
        $this->putJson('/api/v1/rider/profile', ['show_on_nearby' => false])
            ->assertOk()
            ->assertJsonPath('data.rider.show_on_nearby', false);
        $this->assertFalse((bool) $rider->fresh()->show_on_nearby);

        $this->putJson('/api/v1/rider/profile', ['show_on_nearby' => 'abc'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        // ส่งพร้อมโปรไฟล์ทั้งชุดก็ได้ · ไม่ส่งช่องนี้ = ค่าเดิม
        $this->putJson('/api/v1/rider/profile', [
            'phone' => '0812345678',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'show_on_nearby' => true,
        ])->assertOk()->assertJsonPath('data.rider.show_on_nearby', true);

        $this->putJson('/api/v1/rider/profile', [
            'phone' => '0812345678',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
        ])->assertOk()->assertJsonPath('data.rider.show_on_nearby', true);

        // ปิดแล้วผู้ซื้อไม่เห็นบนแผนที่
        $rider->forceFill(['show_on_nearby' => false])->save();
        Sanctum::actingAs($buyer);
        $this->getJson('/api/v1/riders/nearby?lat='.self::BASE_LAT.'&lng='.self::BASE_LNG)
            ->assertOk()
            ->assertJsonCount(0, 'data.riders');
    }
}
