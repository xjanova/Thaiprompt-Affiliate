<?php

namespace Tests\Feature\Rider;

use App\Models\Rider;
use App\Models\Setting;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * หน้าตั้งค่าไรเดอร์ในแอป (ยานพาหนะ + งานที่อยากรับ) — ต้องใช้ MySQL
 *
 * PUT /rider/profile ล้างค่าความชอบงานที่ไม่ได้ส่งมา (เหมือนฟอร์มเว็บ)
 * → GET /rider/status ต้องส่งค่าเดิมกลับให้แอปเติมฟอร์ม แล้วบันทึกกลับได้โดยค่าไม่หาย
 */
#[Group('rider')]
class RiderSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Cache::flush();
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
    }

    private function makeRider(array $overrides = []): Rider
    {
        $user = User::factory()->create();

        $rider = new Rider;
        $rider->forceFill(array_merge([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '0812345678',
            'status' => 'approved',
            'availability' => 'offline',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'approved_at' => now(),
        ], $overrides))->save();

        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }

    public function test_status_returns_preferences_and_round_trip_keeps_them(): void
    {
        $rider = $this->makeRider([
            'preferred_job_types' => ['fresh_market', 'food'],
            'preferred_radius_km' => 8,
            'preferred_min_fee' => 35.5,
        ]);
        Sanctum::actingAs($rider->user);

        $status = $this->getJson('/api/v1/rider/status')->assertOk()->json('data.rider');
        $this->assertSame(['fresh_market', 'food'], $status['preferred_job_types']);
        $this->assertEquals(8, $status['preferred_radius_km']);
        $this->assertEquals(35.5, $status['preferred_min_fee']);
        $this->assertTrue(is_float($status['preferred_min_fee']) || is_int($status['preferred_min_fee']));

        // แอปส่งค่าที่เติมจากสถานะกลับไป + แก้เฉพาะสีรถ → ความชอบงานไม่หาย และยานพาหนะไม่ถือว่าเปลี่ยน
        $this->putJson('/api/v1/rider/profile', [
            'phone' => $status['phone'],
            'vehicle_type' => $status['vehicle_type'],
            'vehicle_plate' => $status['vehicle_plate'],
            'vehicle_brand' => 'Honda Wave',
            'vehicle_color' => 'แดง',
            'preferred_job_types' => $status['preferred_job_types'],
            'preferred_radius_km' => $status['preferred_radius_km'],
            'preferred_min_fee' => $status['preferred_min_fee'],
        ])->assertOk()
            ->assertJsonPath('data.vehicle_changed', false)
            ->assertJsonPath('data.rider.vehicle_color', 'แดง')
            ->assertJsonPath('data.rider.preferred_job_types', ['fresh_market', 'food']);

        $fresh = $rider->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertNull($fresh->documents_changed_at);
        $this->assertEqualsWithDelta(8.0, (float) $fresh->preferred_radius_km, 0.001);
        $this->assertEqualsWithDelta(35.5, (float) $fresh->preferred_min_fee, 0.001);

        // ล้างความชอบงาน (ไม่เลือก = รับทุกงาน)
        $this->putJson('/api/v1/rider/profile', [
            'phone' => '0812345678',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'preferred_job_types' => [],
            'preferred_radius_km' => null,
            'preferred_min_fee' => null,
        ])->assertOk()
            ->assertJsonPath('data.rider.preferred_job_types', [])
            ->assertJsonPath('data.rider.preferred_radius_km', null);
    }

    public function test_profile_validation_and_non_rider(): void
    {
        $rider = $this->makeRider();
        Sanctum::actingAs($rider->user);

        $this->putJson('/api/v1/rider/profile', [
            'phone' => '12345',
            'vehicle_type' => 'car',
            'vehicle_plate' => '',
            'preferred_radius_km' => 99,
            'preferred_job_types' => ['spaceship'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'vehicle_plate', 'preferred_radius_km', 'preferred_job_types.0']);

        $this->assertSame('motorcycle', $rider->fresh()->vehicle_type);

        Sanctum::actingAs(User::factory()->create());
        $this->putJson('/api/v1/rider/profile', ['phone' => '0812345678', 'vehicle_type' => 'walk'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'NOT_RIDER');
    }
}
