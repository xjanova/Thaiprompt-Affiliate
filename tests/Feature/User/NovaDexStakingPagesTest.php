<?php

namespace Tests\Feature\User;

use App\Models\TPIXLiquidityPool;
use App\Models\TPIXLiquidityPosition;
use App\Models\TPIXStake;
use App\Models\TPIXStakingPool;
use App\Models\TPIXSwap;
use App\Models\TPIXToken;
use App\Models\TPIXTokenBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * หน้าสมาชิก TPIX DEX + Staking ธีมโนวาเต็ม (/user/dex/*, /user/staking/*)
 */
class NovaDexStakingPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    private function token(User $creator, string $symbol, array $attributes = []): TPIXToken
    {
        return TPIXToken::forceCreate(array_merge([
            'creator_id' => $creator->id,
            'name' => $symbol.' Coin',
            'symbol' => $symbol,
            'total_supply' => '1000000',
            'status' => 'active',
            'current_price_tpix' => '1',
        ], $attributes));
    }

    private function liquidityPool(TPIXToken $a, TPIXToken $b, array $attributes = []): TPIXLiquidityPool
    {
        return TPIXLiquidityPool::forceCreate(array_merge([
            'token_a_id' => $a->id,
            'token_b_id' => $b->id,
            'reserve_a' => '1000',
            'reserve_b' => '2000',
            'total_liquidity' => '1414',
            'fee_percentage' => '0.3',
            'volume_24h' => '150',
            'is_active' => true,
        ], $attributes));
    }

    private function position(User $owner, TPIXLiquidityPool $pool, array $attributes = []): TPIXLiquidityPosition
    {
        return TPIXLiquidityPosition::forceCreate(array_merge([
            'user_id' => $owner->id,
            'pool_id' => $pool->id,
            'token_a_amount' => '100',
            'token_b_amount' => '200',
            'lp_tokens' => '141.4',
            'status' => 'active',
        ], $attributes));
    }

    private function stakingPool(User $creator, TPIXToken $token, array $attributes = []): TPIXStakingPool
    {
        return TPIXStakingPool::forceCreate(array_merge([
            'token_id' => $token->id,
            'creator_id' => $creator->id,
            'name' => 'พูลทดสอบ '.$token->symbol,
            'apy' => '12.50',
            'lock_period_days' => 30,
            'min_stake_amount' => '10',
            'total_staked' => '500',
            'current_stakers' => 3,
            'status' => 'active',
        ], $attributes));
    }

    private function stake(User $owner, TPIXStakingPool $pool, array $attributes = []): TPIXStake
    {
        return TPIXStake::forceCreate(array_merge([
            'pool_id' => $pool->id,
            'user_id' => $owner->id,
            'token_id' => $pool->token_id,
            'amount' => '250',
            'rewards_earned' => '3.5',
            'staked_at' => now()->subDays(3),
            'unlock_at' => now()->addDays(27),
            'status' => 'active',
        ], $attributes));
    }

    private function assertNovaShell(TestResponse $response): void
    {
        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('theme-nova/member/dex-staking.css', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html);
        $this->assertStringNotContainsString('tp-card', $html);
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bfa[sbr]? fa-/', $html);
    }

    /**
     * ข้อมูลตั้งต้นครบทุกหน้า: token 2 ตัว, พูล DEX, ตำแหน่ง, swap, พูล Stake และ Stake ของสมาชิก
     */
    private function scenario(User $member): array
    {
        $admin = $this->member(['role' => 'admin']);
        $tpix = $this->token($admin, 'TPIX');
        $gold = $this->token($admin, 'GOLD');
        $pool = $this->liquidityPool($tpix, $gold);
        $position = $this->position($member, $pool);

        TPIXTokenBalance::forceCreate([
            'token_id' => $tpix->id,
            'user_id' => $member->id,
            'wallet_address' => '0x'.str_repeat('a', 40),
            'balance' => '900',
            'available_balance' => '875.5',
        ]);

        TPIXSwap::forceCreate([
            'user_id' => $member->id,
            'pool_id' => $pool->id,
            'token_in_id' => $tpix->id,
            'token_out_id' => $gold->id,
            'amount_in' => '10',
            'amount_out' => '19.4321',
            'fee' => '0.06',
            'price_impact' => '0.5',
            'tx_hash' => '0x'.str_repeat('b', 64),
            'status' => 'completed',
        ]);

        $stakingPool = $this->stakingPool($admin, $tpix);
        $stake = $this->stake($member, $stakingPool);

        return compact('admin', 'tpix', 'gold', 'pool', 'position', 'stakingPool', 'stake');
    }

    // ── เปลือกโนวาเต็มทุกหน้า ─────────────────────────────────────

    public function test_every_dex_and_staking_page_renders_the_full_nova_shell_with_data(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);

        $pages = [
            route('user.dex.swap') => ['TPIX', 'GOLD', 'การแลกล่าสุดของฉัน'],
            route('user.dex.pools') => ['TPIX / GOLD', 'พูลที่เปิดอยู่'],
            route('user.dex.add-liquidity') => ['เพิ่มสภาพคล่อง', 'TPIX / GOLD'],
            route('user.dex.add-liquidity', ['pool' => $s['pool']->id]) => ['TPIX / GOLD'],
            route('user.dex.remove-liquidity', $s['position']->id) => ['ถอนสภาพคล่อง TPIX / GOLD', '141.4'],
            route('user.dex.my-positions') => ['TPIX / GOLD', '141.4'],
            route('user.dex.swap-history') => ['19.4321', 'สำเร็จ'],
            route('user.staking.index') => ['พูลทดสอบ TPIX', '12.5%', '250'],
            route('user.staking.show', $s['stakingPool']->id) => ['พูลทดสอบ TPIX', 'Stake ของฉันในพูลนี้', '250'],
            route('user.staking.stake-form', $s['stakingPool']->id) => ['Stake TPIX Coin', '875.5'],
            route('user.staking.history') => ['พูลทดสอบ TPIX', 'กำลัง Stake'],
        ];

        foreach ($pages as $url => $needles) {
            $response = $this->actingAs($member)->get($url);
            $this->assertNovaShell($response);
            foreach ($needles as $needle) {
                $response->assertSee($needle, false);
            }
        }
    }

    public function test_pages_render_empty_states_for_a_new_member(): void
    {
        $member = $this->member();

        $this->actingAs($member)->get(route('user.dex.swap'))->assertOk()->assertSee('ยังไม่มีพูลสภาพคล่องที่เปิดให้แลก');
        $this->actingAs($member)->get(route('user.dex.pools'))->assertOk()->assertSee('ยังไม่มีพูลสภาพคล่อง');
        $this->actingAs($member)->get(route('user.dex.add-liquidity'))->assertOk()->assertSee('ยังมีเหรียญไม่พอสำหรับเปิดคู่');
        $this->actingAs($member)->get(route('user.dex.my-positions'))->assertOk()->assertSee('ยังไม่มีตำแหน่งสภาพคล่อง');
        $this->actingAs($member)->get(route('user.dex.swap-history'))->assertOk()->assertSee('ยังไม่มีประวัติการแลกเปลี่ยน');
        $this->actingAs($member)->get(route('user.staking.index'))->assertOk()->assertSee('ยังไม่มีพูล Stake ที่เปิดอยู่');
        $this->actingAs($member)->get(route('user.staking.history'))->assertOk()->assertSee('ยังไม่มีประวัติการ Stake');
    }

    // ── บั๊กที่แก้ ───────────────────────────────────────────────

    public function test_staking_index_shows_real_pools_instead_of_hardcoded_demo_plans(): void
    {
        $member = $this->member();
        $this->scenario($member);

        $this->actingAs($member)->get(route('user.staking.index'))
            ->assertOk()
            ->assertSee('พูลทดสอบ TPIX')
            ->assertDontSee('Starter Plan')
            ->assertDontSee('(Demo)', false)
            ->assertDontSee('แผนพรีเมียม');
    }

    public function test_stake_form_reads_the_real_available_balance_column(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);

        // เดิมอ่าน $balance->amount (ไม่มีคอลัมน์) → แสดง 0.00 เสมอ
        $this->actingAs($member)->get(route('user.staking.stake-form', $s['stakingPool']->id))
            ->assertOk()
            ->assertSee('875.5');
    }

    public function test_add_liquidity_accepts_the_pool_id_query_used_by_older_links(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);

        $this->actingAs($member)->get(route('user.dex.add-liquidity', ['pool_id' => $s['pool']->id]))
            ->assertOk()
            ->assertSee('nvm-pill nvm-pill--glass', false)
            ->assertSee('TPIX / GOLD');
    }

    public function test_dex_actions_go_through_session_authenticated_web_routes(): void
    {
        $member = $this->member();
        $this->scenario($member);

        $html = $this->actingAs($member)->get(route('user.dex.swap'))->getContent();
        $this->assertStringContainsString(route('user.dex.quote'), str_replace('\/', '/', $html));
        $this->assertStringNotContainsString('/api/v1/tpix', $html);

        // route เว็บตอบ JSON ด้วย session ของสมาชิก (ไม่ใช่ 401 แบบที่หน้าเดิมโดนจาก /api)
        $this->actingAs($member)
            ->postJson(route('user.dex.quote'), [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['token_in_id', 'amount_in']]);
    }

    public function test_staking_actions_require_login_and_validate_input(): void
    {
        $this->postJson(route('user.staking.claim'), ['pool_id' => 1])->assertUnauthorized();

        $member = $this->member();
        $this->actingAs($member)
            ->postJson(route('user.staking.claim'), [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_swap_history_filters_by_status(): void
    {
        $member = $this->member();
        $this->scenario($member);

        $this->actingAs($member)->get(route('user.dex.swap-history', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('ไม่พบรายการตามตัวกรอง')
            ->assertDontSee('19.4321');
    }

    // ── สิทธิ์เจ้าของ ─────────────────────────────────────────────

    public function test_member_cannot_open_another_members_liquidity_position(): void
    {
        $owner = $this->member();
        $s = $this->scenario($owner);
        $intruder = $this->member();

        $this->actingAs($intruder)->get(route('user.dex.remove-liquidity', $s['position']->id))->assertNotFound();
    }

    public function test_removed_position_cannot_be_opened_again(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $s['position']->forceFill(['status' => 'removed'])->save();

        $this->actingAs($member)->get(route('user.dex.remove-liquidity', $s['position']->id))->assertNotFound();
    }

    public function test_other_members_data_does_not_leak_into_my_pages(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $other = $this->member(['name' => 'คนอื่นชื่อลับ', 'email' => 'secret-other@example.com']);
        $this->stake($other, $s['stakingPool'], ['amount' => '777.123']);

        // ความเคลื่อนไหวในพูลแสดงจำนวนได้ แต่ไม่มีชื่อ/อีเมลของสมาชิกคนอื่น
        $show = $this->actingAs($member)->get(route('user.staking.show', $s['stakingPool']->id))->assertOk();
        $show->assertSee('777.123');
        $show->assertDontSee('คนอื่นชื่อลับ');
        $show->assertDontSee('secret-other@example.com');

        // ประวัติ/ตำแหน่งของฉันไม่มี Stake ของคนอื่น
        $this->actingAs($member)->get(route('user.staking.history'))->assertOk()->assertDontSee('777.123');
        $this->actingAs($member)->get(route('user.staking.index'))->assertOk()->assertDontSee('777.123');
    }

    public function test_draft_staking_pool_is_hidden_from_members(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $draft = $this->stakingPool($s['admin'], $s['tpix'], ['status' => 'draft', 'name' => 'พูลร่าง']);

        $this->actingAs($member)->get(route('user.staking.show', $draft->id))->assertNotFound();
        $this->actingAs($member)->get(route('user.staking.stake-form', $draft->id))->assertNotFound();
        $this->actingAs($member)->get(route('user.staking.index'))->assertDontSee('พูลร่าง');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('user.dex.swap'))->assertRedirect();
        $this->get(route('user.staking.index'))->assertRedirect();
    }
}
