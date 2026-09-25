<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ระงับบัญชีผู้ใช้ (users.blocked_at) — CC-01
 *
 * เดิม blocked_at ใช้แค่กับเจ้าของโรงแรม · API/เว็บล็อกอินไม่เช็คเลย → ระงับใครไม่ได้จริง
 * ล็อกไว้: API login, token เดิมของแอป, เว็บ login, session ที่ค้างอยู่ และปุ่มระงับของแอดมิน
 *
 * ใช้ DB (MySQL บน CI)
 */
class SuspendedUserTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'secret-pass-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_api_login_is_refused_for_suspended_account(): void
    {
        $user = $this->suspendedUser();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'ACCOUNT_SUSPENDED']);

        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count());
    }

    public function test_api_login_messages_are_thai_for_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
    }

    public function test_existing_app_token_is_revoked_once_account_is_suspended(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')->assertOk();

        $user->forceFill(['blocked_at' => now()])->save();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertStatus(403)
            ->assertJson(['code' => 'ACCOUNT_SUSPENDED']);

        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count());
    }

    public function test_web_login_is_refused_for_suspended_account(): void
    {
        $user = $this->suspendedUser();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_logged_in_web_session_is_ended_when_suspended(): void
    {
        $user = $this->suspendedUser();

        $this->actingAs($user, 'web')
            ->get('/account/delete')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest('web');
    }

    public function test_admin_can_suspend_and_unsuspend_a_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();
        $target->createToken('mobile-app');

        // หน้ารายชื่อผู้ใช้ (V4) มีปุ่มระงับ
        $this->actingAs($admin, 'web')->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('sel.suspendUrl', false);

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $target), ['reason' => 'สแปมร้านค้า'])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertTrue($target->isSuspended());
        $this->assertSame((int) $admin->id, (int) $target->blocked_by);
        $this->assertSame('สแปมร้านค้า', $target->blocked_reason);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $target->id)->count());

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.index'))
            ->post(route('admin.users.unsuspend', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($target->fresh()->isSuspended());
    }

    public function test_admin_cannot_suspend_self_or_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $super = User::factory()->create(['role' => 'super_admin', 'is_super_admin' => true]);

        // ระงับตัวเอง → UserPolicy::block ปฏิเสธ (403)
        $this->actingAs($admin, 'web')
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $admin))
            ->assertForbidden();
        $this->assertFalse($admin->fresh()->isSuspended());

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.index'))
            ->post(route('admin.users.suspend', $super))
            ->assertRedirect(route('admin.users.index'));
        $this->assertFalse($super->fresh()->isSuspended());
    }

    private function suspendedUser(): User
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $user->forceFill(['blocked_at' => now()])->save();

        return $user;
    }
}
