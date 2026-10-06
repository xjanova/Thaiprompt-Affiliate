<?php

namespace Tests\Feature\AdminApp;

use App\Models\MobileAuthToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * หน้า "จับคู่แอปแอดมิน" — เห็นเครื่องที่จับคู่แล้วของทุกแอดมิน + ถอดเครื่องตามสิทธิ์
 */
class MobilePairDevicesTest extends TestCase
{
    use RefreshDatabase;

    private function pairDevice(User $admin, string $deviceId, string $deviceName, bool $newNameFormat = true): int
    {
        // แถวจับคู่ (ชื่อเครื่องที่แอปส่งมา)
        MobileAuthToken::create([
            'login_token' => hash('sha256', Str::random(64)),
            'login_token_expires_at' => now()->addMinutes(5),
            'code_challenge' => 'admin-pair-flow',
            'state' => Str::random(32),
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'is_admin' => true,
            'pair_code' => hash('sha256', Str::random(8)),
            'pair_code_expires_at' => now()->addMinutes(5),
            'generator_user_id' => $admin->id,
            'user_id' => $admin->id,
            'claimed_at' => now(),
            'used_at' => now(),
            'requires_2fa' => false,
            'two_factor_passed' => false,
        ]);

        $name = 'admin-pair-'.substr($deviceId, 0, 12).($newNameFormat ? ' ('.$deviceName.')' : '');

        return $admin->createToken($name, ['admin'])->accessToken->id;
    }

    public function test_page_renders_with_current_admin_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.mobile-pair.index'))
            ->assertOk()
            ->assertSee('เครื่องที่จับคู่แล้ว');
    }

    public function test_web_qr_then_app_claim_pairs_and_device_shows_in_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // เดิม pair_code กว้าง 16 แต่เก็บ sha256 (64) → สร้าง QR พังทุกครั้ง
        $code = $this->actingAs($admin)->postJson(route('admin.mobile-pair.init'))
            ->assertOk()->json('data.pair_code');
        $this->assertSame(8, strlen($code));

        $this->postJson('/api/admin/auth/pair/claim', [
            'pair_code' => $code,
            'device_id' => 'device-abcdef123456',
            'device_name' => 'samsung SM-S918B',
        ])->assertOk()->assertJsonStructure(['data' => ['token']]);

        $this->actingAs($admin)->getJson(route('admin.mobile-pair.status', ['pair_code' => $code]))
            ->assertJsonPath('data.status', 'claimed');

        $this->actingAs($admin)->getJson(route('admin.mobile-pair.devices'))
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.devices.0.device_name', 'samsung SM-S918B')
            ->assertJsonPath('data.devices.0.is_me', true);
    }

    public function test_lists_devices_of_every_admin_without_leaking_tokens(): void
    {
        $a = User::factory()->create(['role' => 'admin', 'name' => 'แอดมินเอ']);
        $b = User::factory()->create(['role' => 'admin', 'name' => 'แอดมินบี']);
        $this->pairDevice($a, 'aaaa1111bbbb2222', 'samsung SM-S918B');
        $this->pairDevice($a, 'cccc3333dddd4444', 'Xiaomi 13');
        // token รุ่นเก่า (ไม่มีชื่อเครื่องในชื่อ token) → หาชื่อจากแถวจับคู่
        $this->pairDevice($b, 'eeee5555ffff6666', 'OPPO Reno', false);
        // token ของระบบอื่น (เช่น Warroom) ต้องไม่ปน
        $b->createToken('warroom', ['admin']);

        $res = $this->actingAs($a)->getJson(route('admin.mobile-pair.devices'))->assertOk();

        $res->assertJsonPath('data.total', 3)->assertJsonPath('data.admins', 2);
        $names = collect($res->json('data.devices'))->pluck('device_name')->sort()->values()->all();
        $this->assertSame(['OPPO Reno', 'Xiaomi 13', 'samsung SM-S918B'], $names);
        $this->assertStringNotContainsString('token"', json_encode($res->json('data.devices')));

        $mine = collect($res->json('data.devices'))->where('is_me', true);
        $this->assertCount(2, $mine);
        $this->assertTrue($mine->every(fn ($d) => $d['can_revoke'] === true));
        $this->assertFalse(collect($res->json('data.devices'))->firstWhere('device_name', 'OPPO Reno')['can_revoke']);
    }

    public function test_admin_revokes_only_own_device_and_push_token_goes_with_it(): void
    {
        $a = User::factory()->create(['role' => 'admin']);
        $b = User::factory()->create(['role' => 'admin']);
        $own = $this->pairDevice($a, 'aaaa1111bbbb2222', 'samsung');
        $other = $this->pairDevice($b, 'eeee5555ffff6666', 'OPPO');
        DB::table('admin_push_tokens')->insert([
            'user_id' => $a->id, 'access_token_id' => $own, 'token' => 'fcm-'.Str::random(20),
            'platform' => 'android', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($a)->postJson(route('admin.mobile-pair.devices.revoke', $other))->assertForbidden();
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other]);

        $this->actingAs($a)->postJson(route('admin.mobile-pair.devices.revoke', $own))->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $own]);
        $this->assertDatabaseMissing('admin_push_tokens', ['access_token_id' => $own]);
    }

    public function test_super_admin_can_revoke_any_admin_device(): void
    {
        $super = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $b = User::factory()->create(['role' => 'admin']);
        $other = $this->pairDevice($b, 'eeee5555ffff6666', 'OPPO');

        $this->actingAs($super)->postJson(route('admin.mobile-pair.devices.revoke', $other))->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $other]);
        // ถอดซ้ำ = ไม่พบ
        $this->actingAs($super)->postJson(route('admin.mobile-pair.devices.revoke', $other))->assertNotFound();
    }
}
