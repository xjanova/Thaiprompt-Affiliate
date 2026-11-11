<?php

namespace Tests\Feature;

use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 📱 (2026-11-11) GET /api/v1/mobile/affiliate (หน้า "ชวนเพื่อน/ทีมของฉัน" ในแอป) ต้องไม่ 500
 *
 * prod v4.0.676: getMyAffiliate() query ตาราง affiliates (แล้วไม่ได้ใช้ค่า) แต่ prod ไม่มีตารางนี้แล้ว
 *   → SQLSTATE 1146 → 500 ทุกครั้ง (schema ที่ใช้ทดสอบก็ไม่มีตารางนี้เหมือน prod)
 * และบน prod users.sponsor_id เป็น NULL ทุกคน — สายงานจริงอยู่ที่ mlm_members.unilevel_sponsor_id
 *   → ตัวเลขลูกทีมตรง/สมาชิกใหม่ต้องนับจากผัง MLM ไม่งั้นได้ 0 เสมอ
 *
 * shape ที่แอปใช้ (services/api.ts AffiliateData): referralCode, referralLink,
 *   statistics{directReferrals,totalTeamMembers,activeMembers}, earnings{total,thisMonth,pending}, rank
 */
class MobileAffiliateSummaryTest extends TestCase
{
    use RefreshDatabase;

    private MlmPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->plan = MlmPlan::create([
            'name' => 'Hybrid', 'name_th' => 'ไฮบริด', 'slug' => 'hybrid-'.Str::random(5),
            'type' => 'hybrid', 'is_active' => true, 'is_default' => true,
        ]);
    }

    /** สร้างสมาชิก MLM พร้อม user (sponsor ผ่าน mlm_members เท่านั้น — users.sponsor_id ว่างแบบ prod) */
    private function makeMember(string $code, ?MlmMember $sponsor = null, array $attrs = []): MlmMember
    {
        $user = User::factory()->create();

        return MlmMember::create(array_merge([
            'user_id' => $user->id, 'mlm_plan_id' => $this->plan->id, 'member_code' => $code,
            'unilevel_sponsor_id' => $sponsor?->id, 'status' => 'active', 'is_qualified' => true,
            'joined_at' => now()->subMonths(3),
        ], $attrs));
    }

    public function test_summary_returns_ok_without_affiliates_table_and_counts_mlm_tree(): void
    {
        $this->assertFalse(Schema::hasTable('affiliates'), 'ต้องจำลองแบบ prod ที่ไม่มีตาราง affiliates');

        $me = $this->makeMember('ME-0001');
        // ลูกตรง 2 คน: คนหนึ่งเพิ่งเข้าร่วม (นับเป็น activeMembers) อีกคนเข้าร่วมนานแล้ว
        $childNew = $this->makeMember('CH-0001', $me, ['joined_at' => now()->subDays(5)]);
        $this->makeMember('CH-0002', $me);
        // หลาน 1 คน (นับในทีมทั้งหมด แต่ไม่ใช่ลูกตรง)
        $this->makeMember('GC-0001', $childNew);
        // คนนอกสาย ห้ามนับ
        $this->makeMember('OT-0001');

        $base = ['mlm_member_id' => $me->id, 'mlm_plan_id' => $this->plan->id, 'user_id' => $me->user_id, 'created_at' => now(), 'updated_at' => now()];
        MlmCommission::query()->insert([
            $base + ['status' => 'paid', 'commission_amount' => 100, 'paid_at' => now()],
            $base + ['status' => 'pending', 'commission_amount' => 30, 'paid_at' => null],
        ]);

        Sanctum::actingAs($me->user);

        $response = $this->getJson('/api/v1/mobile/affiliate');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.referralCode', 'ME-0001')
            ->assertJsonPath('data.statistics.directReferrals', 2)
            ->assertJsonPath('data.statistics.totalTeamMembers', 3)
            ->assertJsonPath('data.statistics.activeMembers', 1)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'referralCode', 'referralLink',
                    'statistics' => ['directReferrals', 'totalTeamMembers', 'activeMembers'],
                    'earnings' => ['total', 'thisMonth', 'pending'],
                    'rank',
                ],
            ]);

        $this->assertStringContainsString('ME-0001', (string) $response->json('data.referralLink'));
        $this->assertEquals(100, $response->json('data.earnings.total'));
        $this->assertEquals(100, $response->json('data.earnings.thisMonth'));
        $this->assertEquals(30, $response->json('data.earnings.pending'));
    }

    public function test_user_without_mlm_membership_still_gets_ok(): void
    {
        // สมัครด้วยอีเมลในแอป ยังไม่มี mlm_member — endpoint ต้องไม่พัง และได้สถิติ 0
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/mobile/affiliate')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statistics.directReferrals', 0)
            ->assertJsonPath('data.statistics.totalTeamMembers', 0)
            ->assertJsonPath('data.statistics.activeMembers', 0);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/mobile/affiliate')->assertUnauthorized();
    }
}
