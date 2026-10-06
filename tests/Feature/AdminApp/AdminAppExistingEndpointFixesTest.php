<?php

namespace Tests\Feature\AdminApp;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🩹 แก้ endpoint เดิมแบบเพิ่มอย่างเดียว (Warroom ใช้รูปเดิมอยู่)
 *   - finance/wallets · finance/withdrawals(/pending): paging กลับมา (เดิม ->load() บน paginator ทำ meta หาย)
 *   - auth/me: permissions จริงของแอดมินที่ไม่ใช่ super admin · avatar_url จริง
 *   - บัญชีถูกระงับ: มี error_code คู่กับ code
 */
#[Group('admin-app')]
class AdminAppExistingEndpointFixesTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    private function walletFor(User $user): Wallet
    {
        return Wallet::where('user_id', $user->id)->first() ?? Wallet::factory()->create(['user_id' => $user->id]);
    }

    public function test_wallet_and_withdrawal_lists_keep_paging_meta(): void
    {
        $members = [$this->makeMember(), $this->makeMember(), $this->makeMember()];
        foreach ($members as $i => $m) {
            $wallet = $this->walletFor($m);
            DB::table('withdrawal_requests')->insert([
                'user_id' => $m->id, 'wallet_id' => $wallet->id, 'request_id' => 'WD-PAGE-'.$i,
                'amount' => 100 + $i, 'net_amount' => 100 + $i, 'status' => 'pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->actAs($this->makeSuperAdmin());
        $walletTotal = Wallet::count();

        $w = $this->getJson('/api/admin/finance/wallets?per_page=1')->assertOk();
        $this->assertCount(1, $w->json('data.data'));
        $this->assertSame($walletTotal, $w->json('data.meta.total'));
        $this->assertSame($walletTotal, $w->json('data.total'));
        $this->assertSame(1, $w->json('data.per_page'));
        $this->assertArrayHasKey('links', $w->json('data'));

        foreach (['/api/admin/finance/withdrawals?per_page=2', '/api/admin/finance/withdrawals/pending?per_page=2'] as $uri) {
            $r = $this->getJson($uri)->assertOk();
            $this->assertCount(2, $r->json('data.data'), $uri);
            $this->assertSame(3, $r->json('data.meta.total'), $uri);
            $this->assertSame(3, $r->json('data.total'), $uri);
            $this->assertSame(2, $r->json('data.last_page'), $uri);
        }
    }

    public function test_me_returns_real_permissions_and_avatar(): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'fortune_ops', 'display_name' => 'ทีมดูดวง', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $permId = DB::table('permissions')->insertGetId([
            'name' => 'approve_withdrawals', 'display_name' => 'อนุมัติถอนเงิน', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permId, 'created_at' => now(), 'updated_at' => now()]);

        $admin = $this->makeAdmin([
            'role_id' => $roleId,
            'permissions' => ['manage_api_keys'],
            'line_picture_url' => 'https://profile.line-scdn.net/abc123',
        ]);
        $this->actAs($admin);

        $me = $this->getJson('/api/admin/auth/me')->assertOk();
        $this->assertSame(['approve_withdrawals', 'manage_api_keys'], $me->json('data.admin.permissions'));
        $this->assertSame('https://profile.line-scdn.net/abc123', $me->json('data.admin.avatar_url'));

        $this->actAs($this->makeSuperAdmin());
        $this->getJson('/api/admin/auth/me')->assertOk()
            ->assertJsonPath('data.admin.permissions', ['*'])
            ->assertJsonPath('data.admin.avatar_url', null);
    }

    public function test_suspended_account_gets_error_code(): void
    {
        $admin = $this->makeAdmin();
        $token = $admin->createToken('admin-app', ['admin'])->plainTextToken;
        $admin->forceFill(['blocked_at' => now()])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/ops/summary')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ACCOUNT_SUSPENDED')
            ->assertJsonPath('error_code', 'ACCOUNT_SUSPENDED');
    }
}
