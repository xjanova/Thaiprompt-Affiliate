<?php

namespace Tests\Feature\User;

use App\Models\Rank;
use App\Models\RankBonus;
use App\Models\RankRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * หน้าระดับสมาชิกธีมโนวาเต็ม (/user/ranks/dashboard, /progress, /leaderboard, /widget-data)
 */
class RanksNovaPagesTest extends TestCase
{
    use RefreshDatabase;

    private Rank $bronze;

    private Rank $silver;

    private Rank $gold;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);

        $this->bronze = Rank::create(['name' => 'Bronze', 'name_th' => 'ทองแดง', 'level' => 1, 'min_points' => 0, 'color' => '#c08a4a', 'badge_icon' => '🥉', 'commission_rate' => 5, 'is_active' => true, 'is_default' => true]);
        $this->silver = Rank::create(['name' => 'Silver', 'name_th' => 'เงิน', 'level' => 2, 'min_points' => 1000, 'color' => '#9aa4ad', 'badge_icon' => '🥈', 'commission_rate' => 7.5, 'is_active' => true]);
        $this->gold = Rank::create(['name' => 'Gold', 'name_th' => 'ทองคำ', 'level' => 3, 'min_points' => 5000, 'color' => 'red;background:url(x)', 'badge_icon' => '🥇', 'is_active' => true]);

        RankRequirement::create(['rank_id' => $this->silver->id, 'requirement_type' => 'referrals', 'name' => 'Direct referrals', 'name_th' => 'แนะนำตรง', 'target_value' => 5, 'unit' => 'คน', 'is_required' => true, 'is_active' => true]);
        RankBonus::create(['rank_id' => $this->silver->id, 'bonus_type' => 'one_time', 'name' => 'Promotion bonus', 'name_th' => 'โบนัสเลื่อนขั้นเงิน', 'amount' => 500, 'is_active' => true]);
    }

    private function member(array $attributes = []): User
    {
        // ไม่ refresh — โมเดลจาก factory ยังไม่มีค่า default ของ DB (rank_points = null) ตรงกับเคสหน้า 500 เดิม
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    /**
     * หน้าต้องอยู่ในเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4
     */
    private function assertFullNova(string $html): void
    {
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, 'ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, 'ยังมีการ์ด V4 หลงอยู่');
        $this->assertStringContainsString('nova-member.css', $html);
    }

    public function test_dashboard_no_longer_crashes_for_a_member_without_points(): void
    {
        // เดิม: rank_points เป็น null → where('rank_points', '>', null) → 500
        $html = $this->actingAs($this->member())->get(route('user.ranks.dashboard'))
            ->assertOk()
            ->assertViewIs('user.ranks.dashboard')
            ->getContent();

        $this->assertFullNova($html);
    }

    public function test_member_without_a_rank_is_aimed_at_the_first_rank_not_told_they_are_at_the_top(): void
    {
        $this->actingAs($this->member())->get(route('user.ranks.dashboard'))
            ->assertOk()
            ->assertSee('สมาชิกใหม่')
            ->assertSee('เป้าหมายถัดไป: ทองแดง')
            ->assertDontSee('ระดับสูงสุดแล้ว');
    }

    public function test_dashboard_shows_points_needed_requirements_and_next_rewards(): void
    {
        $user = $this->member(['current_rank_id' => $this->bronze->id, 'rank_points' => 400]);

        $this->actingAs($user)->get(route('user.ranks.dashboard'))
            ->assertOk()
            ->assertSee('เป้าหมายถัดไป: เงิน')
            ->assertSee('600') // 1,000 - 400
            ->assertSee('แนะนำตรง')
            ->assertSee('โบนัสเลื่อนขั้นเงิน')
            ->assertSee('฿500.00')
            ->assertSee('คุณอยู่ที่นี่')
            ->assertSee(route('user.ranks.progress'), false)
            ->assertSee(route('user.ranks.leaderboard'), false);
    }

    public function test_top_rank_member_sees_congratulations(): void
    {
        $user = $this->member(['current_rank_id' => $this->gold->id, 'rank_points' => 9000]);

        $this->actingAs($user)->get(route('user.ranks.dashboard'))
            ->assertOk()
            ->assertSee('ยินดีด้วย! คุณอยู่ระดับสูงสุดแล้ว')
            ->assertDontSee('เป้าหมายถัดไป');
    }

    public function test_rank_colour_that_is_not_a_hex_code_never_reaches_the_style_attribute(): void
    {
        $user = $this->member(['current_rank_id' => $this->gold->id, 'rank_points' => 9000]);

        $this->actingAs($user)->get(route('user.ranks.dashboard'))
            ->assertOk()
            ->assertDontSee('url(x)', false)
            ->assertSee('--rk-c: #c08a4a', false);
    }

    public function test_widget_data_returns_points_based_progress(): void
    {
        $user = $this->member(['current_rank_id' => $this->bronze->id, 'rank_points' => 400]);

        $this->actingAs($user)->getJson(route('user.ranks.widget-data'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_points', 400)
            ->assertJsonPath('data.next_rank.name_th', 'เงิน')
            ->assertJsonPath('data.next_rank_points', 1000)
            ->assertJsonPath('data.progress_percentage', 40); // (400-0)/(1000-0)
    }

    public function test_widget_data_works_for_a_member_without_points(): void
    {
        $this->actingAs($this->member())->getJson(route('user.ranks.widget-data'))
            ->assertOk()
            ->assertJsonPath('data.current_points', 0)
            ->assertJsonPath('data.next_rank.name_th', 'ทองแดง');
    }

    public function test_progress_lists_requirement_names_instead_of_raw_json(): void
    {
        $user = $this->member(['current_rank_id' => $this->bronze->id, 'rank_points' => 400]);

        $html = $this->actingAs($user)->get(route('user.ranks.progress'))
            ->assertOk()
            ->assertViewIs('user.ranks.progress')
            ->assertSee('ยศทั้งหมด 3 ระดับ')
            ->assertSee('แนะนำตรง')
            ->assertSee('เป้าหมายถัดไป')
            ->assertSee('ยศปัจจุบัน')
            ->assertDontSee('"requirement_type"', false)
            ->getContent();

        $this->assertFullNova($html);
    }

    public function test_leaderboard_shows_names_but_never_other_members_emails(): void
    {
        $leader = $this->member(['name' => 'คุณผู้นำ', 'email' => 'leader-secret@example.com', 'current_rank_id' => $this->gold->id, 'rank_points' => 9000]);
        $this->member(['name' => 'คุณรอง', 'current_rank_id' => $this->silver->id, 'rank_points' => 3000]);
        $this->member(['name' => 'คุณสาม', 'current_rank_id' => $this->bronze->id, 'rank_points' => 800]);
        $me = $this->member(['name' => 'ฉันเอง', 'current_rank_id' => $this->bronze->id, 'rank_points' => 100]);

        $html = $this->actingAs($me)->get(route('user.ranks.leaderboard'))
            ->assertOk()
            ->assertSee('คุณผู้นำ')
            ->assertSee('ฉันเอง')
            ->assertSee('อันดับของคุณ')
            ->assertSee('#4')
            ->assertDontSee($leader->email)
            ->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('nvm-rk-podium', $html);
    }

    public function test_member_without_a_rank_is_told_how_to_join_the_leaderboard(): void
    {
        $this->actingAs($this->member())->get(route('user.ranks.leaderboard'))
            ->assertOk()
            ->assertSee('รับยศแรกเพื่อขึ้นกระดาน')
            ->assertSee('ยังไม่มีสมาชิกบนกระดานผู้นำ');
    }
}
