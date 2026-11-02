<?php

namespace Tests\Feature\User;

use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ชุดหน้า MLM ของสมาชิกธีมโนวาเต็ม (/user/mlm/*)
 * ครอบคลุม: เปลือกโนวา, ตัวกรองคอมมิชชั่น, ความเป็นส่วนตัวของลูกทีม, ยอดขา Binary จริง
 */
class NovaMlmPagesTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        'user.mlm.dashboard',
        'user.mlm.commissions',
        'user.mlm.genealogy',
        'user.mlm.referral',
        'user.mlm.team',
        'user.mlm.binary-position',
        'user.mlm.income-simulator',
        'user.mlm.scenario-simulator',
        'user.mlm.income-comparison',
        'user.mlm.dividend-simulator',
    ];

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    private function plan(string $type = 'binary'): MlmPlan
    {
        return MlmPlan::create([
            'name' => 'Test '.$type.' '.(++$this->seq),
            'slug' => 'test-'.$type.'-'.$this->seq,
            'type' => $type,
            'is_active' => true,
        ]);
    }

    private function member(User $user, MlmPlan $plan, array $attributes = []): MlmMember
    {
        return MlmMember::create(array_merge([
            'user_id' => $user->id,
            'mlm_plan_id' => $plan->id,
            'member_code' => 'MT'.str_pad((string) (++$this->seq), 5, '0', STR_PAD_LEFT),
            'status' => 'active',
            'joined_at' => now(),
        ], $attributes));
    }

    private function commission(MlmMember $member, array $attributes = []): MlmCommission
    {
        return MlmCommission::create(array_merge([
            'mlm_member_id' => $member->id,
            'mlm_plan_id' => $member->mlm_plan_id,
            'user_id' => $member->user_id,
            'type' => 'unilevel_direct',
            'level' => 1,
            'pv_amount' => 100,
            'sales_amount' => 1000,
            'commission_amount' => 50,
            'status' => 'paid',
            'paid_at' => now(),
        ], $attributes));
    }

    private function assertFullNova(string $html, string $route): void
    {
        $this->assertStringContainsString('nvm-body', $html, $route);
        $this->assertStringContainsString('nova-member.css', $html, $route);
        $this->assertStringContainsString('theme-nova/member/mlm.css', $html, $route);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, $route.' ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, $route.' ยังมีการ์ด V4 หลงอยู่');
    }

    public function test_every_mlm_page_renders_in_full_nova_shell(): void
    {
        $user = $this->user();
        $member = $this->member($user, $this->plan('binary'));
        $this->commission($member);

        foreach (self::PAGES as $route) {
            $response = $this->actingAs($user)->get(route($route));
            $response->assertOk();
            $this->assertFullNova($response->getContent(), $route);
        }
    }

    public function test_non_member_is_redirected_or_sees_empty_state(): void
    {
        $user = $this->user();

        foreach (['user.mlm.dashboard', 'user.mlm.genealogy', 'user.mlm.referral', 'user.mlm.binary-position'] as $route) {
            $this->actingAs($user)->get(route($route))->assertRedirect(route('user.dashboard'));
        }

        $team = $this->actingAs($user)->get(route('user.mlm.team'));
        $team->assertOk()->assertSee('คุณยังไม่ได้เป็นสมาชิก MLM');
        $this->assertFullNova($team->getContent(), 'user.mlm.team');

        // เครื่องมือการตลาดไม่ต้องเป็นสมาชิก
        foreach (['user.mlm.income-simulator', 'user.mlm.scenario-simulator', 'user.mlm.income-comparison', 'user.mlm.dividend-simulator'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_binary_position_redirects_for_unilevel_plan(): void
    {
        $user = $this->user();
        $this->member($user, $this->plan('unilevel'));

        $this->actingAs($user)->get(route('user.mlm.binary-position'))->assertRedirect(route('user.mlm.dashboard'));
    }

    public function test_commission_filter_all_returns_rows_and_enum_type_filters(): void
    {
        $user = $this->user();
        $member = $this->member($user, $this->plan('binary'));
        $this->commission($member, ['type' => 'unilevel_direct', 'notes' => 'NOTE-UNILEVEL']);
        $this->commission($member, ['type' => 'binary_pair', 'leg' => 'left', 'notes' => 'NOTE-BINARY']);

        // เลือก "ทั้งหมด" → ฟอร์มส่ง type= ว่าง (ของเดิมได้ผลว่าง)
        $all = $this->actingAs($user)->get(route('user.mlm.commissions', ['type' => '', 'status' => '']));
        $all->assertOk()->assertSee('NOTE-UNILEVEL')->assertSee('NOTE-BINARY');

        $binary = $this->actingAs($user)->get(route('user.mlm.commissions', ['type' => 'binary_pair']));
        $binary->assertOk()->assertSee('NOTE-BINARY')->assertDontSee('NOTE-UNILEVEL');

        // ค่าที่ไม่อยู่ใน enum ถูกเมิน ไม่ใช่ทำให้ผลว่าง
        $this->actingAs($user)->get(route('user.mlm.commissions', ['type' => 'direct_referral']))
            ->assertOk()->assertSee('NOTE-UNILEVEL');
    }

    public function test_commissions_only_show_own_rows(): void
    {
        $plan = $this->plan('binary');
        $me = $this->user();
        $other = $this->user();
        $this->member($me, $plan);
        $this->commission($this->member($other, $plan), ['notes' => 'SOMEONE-ELSES']);

        $this->actingAs($me)->get(route('user.mlm.commissions'))->assertOk()->assertDontSee('SOMEONE-ELSES');
    }

    public function test_this_month_stat_excludes_same_month_last_year(): void
    {
        $user = $this->user();
        $member = $this->member($user, $this->plan('binary'));
        $this->commission($member, ['commission_amount' => 123, 'paid_at' => now()]);
        $this->commission($member, ['commission_amount' => 9000, 'paid_at' => now()->subYear()]);

        $response = $this->actingAs($user)->get(route('user.mlm.commissions'));
        $response->assertOk();
        $this->assertSame(123.0, (float) $response->viewData('stats')['this_month']);
    }

    public function test_team_hides_downline_email_and_counts_whole_team(): void
    {
        $plan = $this->plan('unilevel');
        $me = $this->user();
        $sponsor = $this->member($me, $plan);

        $active = $this->user(['email' => 'downline-secret@example.test', 'name' => 'ลูกทีมคนแรก']);
        $this->member($active, $plan, ['unilevel_sponsor_id' => $sponsor->id, 'status' => 'active']);
        $this->member($this->user(), $plan, ['unilevel_sponsor_id' => $sponsor->id, 'status' => 'suspended']);
        $old = $this->member($this->user(), $plan, ['unilevel_sponsor_id' => $sponsor->id, 'status' => 'inactive']);
        $old->forceFill(['created_at' => now()->subYear()])->save();

        $response = $this->actingAs($me)->get(route('user.mlm.team'));
        $response->assertOk()->assertSee('ลูกทีมคนแรก')->assertDontSee('downline-secret@example.test');

        $stats = $response->viewData('teamStats');
        $this->assertSame(3, $stats['total']);
        $this->assertSame(1, $stats['active']);
        $this->assertSame(2, $stats['inactive']);
        $this->assertSame(2, $stats['this_month']);
    }

    public function test_genealogy_json_strips_downline_private_data(): void
    {
        $plan = $this->plan('unilevel');
        $me = $this->user();
        $root = $this->member($me, $plan, ['total_earnings' => 777]);
        $child = $this->user(['email' => 'child-private@example.test']);
        $this->member($child, $plan, ['unilevel_sponsor_id' => $root->id, 'unilevel_level' => 1, 'total_earnings' => 98765.43]);

        $response = $this->actingAs($me)->getJson(route('user.mlm.genealogy-data', ['type' => 'unilevel', 'depth' => 99]));
        $response->assertOk()->assertJson(['success' => true, 'max_depth' => 10]);

        $json = $response->getContent();
        $this->assertStringNotContainsString('child-private@example.test', $json);
        $this->assertStringNotContainsString('98765', $json);
        $this->assertStringNotContainsString('"email"', $json);
    }

    public function test_genealogy_json_rejects_unknown_tree_type(): void
    {
        $user = $this->user();
        $this->member($user, $this->plan('unilevel'));

        $this->actingAs($user)->getJson(route('user.mlm.genealogy-data', ['type' => 'evil']))
            ->assertOk()->assertJson(['tree_type' => 'unilevel']);
    }

    public function test_binary_position_shows_real_leg_numbers(): void
    {
        $user = $this->user();
        $member = $this->member($user, $this->plan('binary'), [
            'left_leg_pv' => 4321, 'right_leg_pv' => 1234,
            'left_leg_members' => 17, 'right_leg_members' => 9,
        ]);
        $this->commission($member, ['type' => 'binary_pair', 'leg' => 'left', 'commission_amount' => 250]);
        $this->commission($member, ['type' => 'binary_pair', 'leg' => 'right', 'commission_amount' => 999, 'status' => 'cancelled']);

        $response = $this->actingAs($user)->get(route('user.mlm.binary-position'));
        $response->assertOk()->assertSee('4,321')->assertSee('1,234')->assertSee('17 คน')->assertSee('9 คน');

        $legs = $response->viewData('legs');
        $this->assertSame(250.0, $legs['left']['commission']);
        $this->assertSame(0.0, $legs['right']['commission'], 'รายการที่ยกเลิกต้องไม่ถูกนับ');
        $this->assertSame(250.0, $response->viewData('binaryEarned'));
        // ลิงก์ ?position= ที่หน้าสมัครไม่อ่านถูกเอาออก
        $this->assertStringNotContainsString('position=left', $response->getContent());
    }

    public function test_dashboard_shows_thai_commission_type_without_fake_rank(): void
    {
        $user = $this->user();
        $member = $this->member($user, $this->plan('binary'));
        $this->commission($member, ['type' => 'binary_matching']);

        $response = $this->actingAs($user)->get(route('user.mlm.dashboard'));
        $response->assertOk()->assertSee('Binary Matching')->assertDontSee('Silver');
        $this->assertNull($response->viewData('rankInfo')['current']);
    }

    public function test_settings_api_does_not_leak_exception_text(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->getJson(route('user.mlm.settings'));
        $this->assertArrayNotHasKey('error', $response->json() ?? []);
    }

    public function test_preview_calculation_survives_zero_pv_rate(): void
    {
        // MlmGlobalSetting::set() กันค่า 0 อยู่แล้ว — จำลองค่าเสียที่หลุดมาในแคช/ข้อมูลเก่า
        \Illuminate\Support\Facades\Cache::put('mlm_setting_global_pv_rate', 0, 60);
        $user = $this->user();

        $response = $this->actingAs($user)->postJson(route('user.mlm.preview-calculation'), ['sales_amount' => 1000]);
        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_dividend_simulator_marks_example_rates_when_no_pool(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('user.mlm.dividend-simulator'))
            ->assertOk()->assertSee('ยังไม่มี pool staking ที่เปิดอยู่')->assertDontSee('ผลตอบแทนสูงสุด 120%');
    }

    public function test_income_comparison_has_no_fabricated_testimonials(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('user.mlm.income-comparison'))
            ->assertOk()->assertDontSee('คุณสมชาย')->assertSee('ไม่ใช่การรับประกันรายได้');
    }
}
