<?php

namespace Tests\Feature\User;

use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * 🐛 (2026-11-11) หน้า "ชวนเพื่อน" ในแอป — QR โหลดไม่สำเร็จ
 *
 * เดิม GET /api/v1/profile/referral-code ส่ง users.referral_code ซึ่งว่าง 1,269 จาก 1,272 บัญชีบน prod
 * รหัสจริงที่หน้าสมัครอ่านคือ mlm_members.member_code
 */
#[Group('referral')]
class AppReferralShareTest extends TestCase
{
    use RefreshDatabase;

    private MlmPlan $plan;

    private MlmMember $rootMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->rootMember = $this->memberFor(User::factory()->create(['role' => 'user']));
        Setting::set('default_sponsor_member_code', $this->rootMember->member_code);
    }

    private function memberFor(User $user, ?MlmMember $sponsor = null): MlmMember
    {
        return MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'TM'.strtoupper(Str::random(7)),
            'unilevel_sponsor_id' => $sponsor?->id,
            'unilevel_level' => $sponsor ? $sponsor->unilevel_level + 1 : 0,
            'unilevel_path' => $sponsor ? $sponsor->unilevel_path.'/'.$sponsor->id : '',
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now(),
        ]);
    }

    public function test_referral_code_is_the_member_code_not_the_empty_users_column(): void
    {
        $user = User::factory()->create(['role' => 'user', 'referral_code' => null]);
        $member = $this->memberFor($user, $this->rootMember);
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/v1/profile/referral-code')->assertOk();

        $this->assertSame($member->member_code, $res->json('data.referralCode'));
        $this->assertStringContainsString('ref='.$member->member_code, (string) $res->json('data.referralLink'));
        $this->assertStringStartsWith('http', (string) $res->json('data.referralLink'));
    }

    public function test_user_without_a_member_row_is_enrolled_once_under_their_sponsor(): void
    {
        $sponsorUser = User::factory()->create(['role' => 'user']);
        $sponsor = $this->memberFor($sponsorUser, $this->rootMember);
        $user = User::factory()->create(['role' => 'user', 'referral_code' => null, 'sponsor_id' => $sponsorUser->id]);
        Sanctum::actingAs($user);

        $first = $this->getJson('/api/v1/profile/referral-code')->assertOk()->json('data.referralCode');
        $second = $this->getJson('/api/v1/profile/referral-code')->assertOk()->json('data.referralCode');

        $this->assertNotEmpty($first);
        $this->assertSame($first, $second, 'เปิดซ้ำต้องได้รหัสเดิม');
        $this->assertSame(1, MlmMember::where('user_id', $user->id)->count(), 'ห้ามสร้างสมาชิกซ้ำ');
        $this->assertSame($sponsor->id, (int) MlmMember::where('user_id', $user->id)->value('unilevel_sponsor_id'));
    }

    public function test_user_without_sponsor_goes_under_the_default_sponsor(): void
    {
        $user = User::factory()->create(['role' => 'user', 'referral_code' => null]);
        Sanctum::actingAs($user);

        $code = $this->getJson('/api/v1/profile/referral-code')->assertOk()->json('data.referralCode');

        $member = MlmMember::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($member->member_code, $code);
        $this->assertSame($this->rootMember->id, (int) $member->unilevel_sponsor_id);
    }

    public function test_app_signup_accepts_the_shared_member_code(): void
    {
        $sponsorUser = User::factory()->create(['role' => 'user']);
        $sponsor = $this->memberFor($sponsorUser, $this->rootMember);

        $this->postJson('/api/v1/register', [
            'name' => 'เพื่อนใหม่',
            'email' => 'friend-'.uniqid().'@gmail.com',
            'password' => 'Secret-pass-123',
            'password_confirmation' => 'Secret-pass-123',
            'referral_code' => $sponsor->member_code,
        ])->assertCreated();

        $friend = User::where('name', 'เพื่อนใหม่')->firstOrFail();
        $this->assertSame($sponsorUser->id, (int) $friend->sponsor_id);
        $this->assertSame($sponsor->id, (int) MlmMember::where('user_id', $friend->id)->value('unilevel_sponsor_id'));
    }

    public function test_app_signup_rejects_an_unknown_code_in_thai(): void
    {
        $res = $this->postJson('/api/v1/register', [
            'name' => 'เพื่อนใหม่',
            'email' => 'friend-'.uniqid().'@gmail.com',
            'password' => 'Secret-pass-123',
            'password_confirmation' => 'Secret-pass-123',
            'referral_code' => 'NOPE1234',
        ])->assertStatus(422);

        $this->assertStringContainsString('ไม่พบรหัสแนะนำ', (string) $res->json('errors.referral_code.0'));
    }
}
