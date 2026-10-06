<?php

namespace Tests\Feature\AdminApp\Approvals;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🔒 แอปแอดมิน: ระงับ/ยกเลิกระงับบัญชี (UserPolicy::block + UserSuspensionService) · รีเซ็ต PIN (super admin เท่านั้น)
 */
#[Group('admin-app')]
class UserModerationApiTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Http::fake();
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_suspend_revokes_tokens_records_reason_and_unsuspend_restores(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeMember();
        $target->createToken('mobile-app');

        $this->actAs($admin);
        $this->postJson('/api/admin/users/'.$target->id.'/suspend', ['reason' => str_repeat('ก', 501)])
            ->assertStatus(422)->assertJsonPath('error_code', 'VALIDATION_ERROR');
        $this->assertFalse($target->fresh()->isSuspended());

        $this->postJson('/api/admin/users/'.$target->id.'/suspend', ['reason' => 'สแปมร้านค้า'])
            ->assertOk()
            ->assertJsonPath('data.suspended', true)
            ->assertJsonPath('data.already', false);

        $fresh = $target->fresh();
        $this->assertTrue($fresh->isSuspended());
        $this->assertSame((int) $admin->id, (int) $fresh->blocked_by);
        $this->assertSame('สแปมร้านค้า', $fresh->blocked_reason);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $target->id)->count());

        // ระงับซ้ำ = สำเร็จเฉย ๆ (ไม่เขียนทับเหตุผลเดิม)
        $this->postJson('/api/admin/users/'.$target->id.'/suspend', ['reason' => 'อื่น'])
            ->assertOk()->assertJsonPath('data.already', true);
        $this->assertSame('สแปมร้านค้า', $target->fresh()->blocked_reason);

        $this->postJson('/api/admin/users/'.$target->id.'/unsuspend')
            ->assertOk()->assertJsonPath('data.suspended', false);
        $this->assertFalse($target->fresh()->isSuspended());
        $this->postJson('/api/admin/users/'.$target->id.'/unsuspend')
            ->assertOk()->assertJsonPath('data.already', true);
    }

    public function test_cannot_suspend_self_or_super_admin(): void
    {
        $admin = $this->makeAdmin();
        $super = $this->makeSuperAdmin();

        $this->actAs($admin);
        $this->postJson('/api/admin/users/'.$admin->id.'/suspend')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'PERMISSION_DENIED')
            ->assertJsonPath('message', 'ไม่สามารถระงับบัญชีของตัวเองได้');
        $this->postJson('/api/admin/users/'.$super->id.'/suspend')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'CANNOT_SUSPEND_SUPER_ADMIN');

        // super admin ผ่าน policy ได้ทุกอย่าง แต่กติการะงับตัวเองยังกันไว้
        $this->actAs($super);
        $this->postJson('/api/admin/users/'.$super->id.'/suspend')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'CANNOT_SUSPEND_SELF');

        $this->assertFalse($admin->fresh()->isSuspended());
        $this->assertFalse($super->fresh()->isSuspended());
        $this->postJson('/api/admin/users/999999/suspend')->assertNotFound();
    }

    public function test_only_super_admin_can_suspend_or_unsuspend_staff_accounts(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        $moderator = User::factory()->create(['role' => 'moderator', 'is_super_admin' => false]);
        $otherAdmin->createToken('admin-app', ['admin']);

        // แอดมินธรรมดา → ระงับทีมงานไม่ได้ (403) ไม่มีอะไรเปลี่ยน · token ของอีกฝ่ายยังอยู่
        $this->actAs($admin);
        foreach ([$otherAdmin, $moderator] as $staff) {
            $this->postJson('/api/admin/users/'.$staff->id.'/suspend', ['reason' => 'ลองระงับ'])
                ->assertStatus(403)
                ->assertJsonPath('error_code', 'STAFF_REQUIRES_SUPER_ADMIN')
                ->assertJsonPath('data.suspended', false);
            $this->assertFalse($staff->fresh()->isSuspended());
        }
        $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $otherAdmin->id)->count());

        // super admin ระงับได้ → แอดมินธรรมดาปลดไม่ได้ (กันปลดบัญชีที่ super admin สั่งระงับไว้)
        $this->actAs($this->makeSuperAdmin());
        $this->postJson('/api/admin/users/'.$otherAdmin->id.'/suspend', ['reason' => 'token หลุด'])
            ->assertOk()->assertJsonPath('data.suspended', true);

        $this->actAs($admin);
        $this->postJson('/api/admin/users/'.$otherAdmin->id.'/unsuspend')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'STAFF_REQUIRES_SUPER_ADMIN');
        $this->assertTrue($otherAdmin->fresh()->isSuspended());

        // หน้าเว็บใช้กติกาเดียวกัน (UserSuspensionService) → flash error ไม่ระงับ
        $this->actingAs($admin, 'web')
            ->post(route('admin.users.suspend', $moderator), ['reason' => 'ลองจากเว็บ'])
            ->assertSessionHas('error');
        $this->assertFalse($moderator->fresh()->isSuspended());

        // สมาชิกทั่วไป → แอดมินธรรมดายังระงับได้ตามเดิม
        $member = $this->makeMember();
        $this->actAs($admin);
        $this->postJson('/api/admin/users/'.$member->id.'/suspend')->assertOk()->assertJsonPath('data.suspended', true);
    }

    public function test_reset_wallet_pin_is_super_admin_only_and_logged(): void
    {
        $target = $this->makeMember();
        $wallet = Wallet::create(['user_id' => $target->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);
        $wallet->forceFill(['pin_hash' => Hash::make('123456'), 'failed_attempts' => 5, 'locked_until' => now()->addHour()])->save();

        $this->actAs($this->makeAdmin());
        $this->postJson('/api/admin/users/'.$target->id.'/reset-wallet-pin')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'PERMISSION_DENIED');
        $this->assertNotNull($wallet->fresh()->pin_hash);

        $super = $this->actAs($this->makeSuperAdmin());
        $this->postJson('/api/admin/users/'.$target->id.'/reset-wallet-pin')
            ->assertOk()
            ->assertJsonPath('data.wallet_id', $wallet->id)
            ->assertJsonPath('data.has_pin', false);

        $fresh = $wallet->fresh();
        $this->assertNull($fresh->pin_hash);
        $this->assertSame(0, (int) $fresh->failed_attempts);
        $this->assertNull($fresh->locked_until);
        $log = WalletLog::where('wallet_id', $wallet->id)->where('action', 'pin_changed')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('warning', $log->severity);
        $this->assertStringContainsString($super->name, $log->description);

        // กดซ้ำได้ ผลเหมือนเดิม · ผู้ใช้ที่ไม่มีกระเป๋า → 404
        $this->postJson('/api/admin/users/'.$target->id.'/reset-wallet-pin')->assertOk();
        $this->postJson('/api/admin/users/'.$this->makeMember()->id.'/reset-wallet-pin')
            ->assertNotFound()->assertJsonPath('error_code', 'WALLET_NOT_FOUND');
    }
}
