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

    // ── ทำรายการจริงผ่าน route เว็บ (happy path + กรณีที่ต้องปฏิเสธ) ─────

    private function giveBalance(User $member, TPIXToken $token, string $amount): TPIXTokenBalance
    {
        return TPIXTokenBalance::updateOrCreate(
            ['user_id' => $member->id, 'token_id' => $token->id],
            ['wallet_address' => '0x'.str_repeat('a', 40), 'balance' => $amount, 'available_balance' => $amount]
        );
    }

    private function balanceOf(User $member, TPIXToken $token): ?TPIXTokenBalance
    {
        return TPIXTokenBalance::where('user_id', $member->id)->where('token_id', $token->id)->first();
    }

    public function test_quote_and_swap_succeed_and_update_balances_and_pool_reserves(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);

        // เดิม getQuote อ่าน reserve_in/reserve_out ที่ไม่มีอยู่ → 500 ทุกครั้ง
        $quote = $this->actingAs($member)->postJson(route('user.dex.quote'), [
            'token_in_id' => $s['tpix']->id, 'token_out_id' => $s['gold']->id, 'amount_in' => '10',
        ])->assertOk()->json('data');

        $this->assertGreaterThan(19, (float) $quote['amount_out']);
        $this->assertLessThan(20, (float) $quote['amount_out']);

        // แลกไปยังเหรียญที่ยังไม่เคยถือ (GOLD) — เดิมสร้างแถวยอดไม่ได้เพราะไม่มี wallet_address
        $this->assertNull($this->balanceOf($member, $s['gold']));
        $this->actingAs($member)->postJson(route('user.dex.swap.execute'), [
            'token_in_id' => $s['tpix']->id,
            'token_out_id' => $s['gold']->id,
            'amount_in' => '10',
            'min_amount_out' => bcmul($quote['amount_out'], '0.99', 8),
        ])->assertOk()->assertJsonPath('data.amount_out', $quote['amount_out']);

        $tpix = $this->balanceOf($member, $s['tpix']);
        $this->assertSame(0, bccomp($tpix->available_balance, '865.5', 8));
        $this->assertSame(0, bccomp($tpix->balance, '890', 8));

        $gold = $this->balanceOf($member, $s['gold']);
        $this->assertSame(0, bccomp($gold->available_balance, $quote['amount_out'], 8));
        $this->assertNotEmpty($gold->wallet_address);

        $pool = $s['pool']->fresh();
        $this->assertSame(0, bccomp($pool->reserve_a, '1010', 8));
        $this->assertSame(-1, bccomp($pool->reserve_b, '2000', 8));
        $this->assertSame('completed', TPIXSwap::where('user_id', $member->id)->latest('id')->first()->status);
    }

    public function test_swap_in_the_reverse_direction_moves_the_right_reserves(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $this->giveBalance($member, $s['gold'], '50');

        $this->actingAs($member)->postJson(route('user.dex.swap.execute'), [
            'token_in_id' => $s['gold']->id, 'token_out_id' => $s['tpix']->id, 'amount_in' => '20', 'min_amount_out' => '0',
        ])->assertOk();

        $pool = $s['pool']->fresh();
        $this->assertSame(0, bccomp($pool->reserve_b, '2020', 8));
        $this->assertSame(-1, bccomp($pool->reserve_a, '1000', 8));
        $this->assertSame(0, bccomp($this->balanceOf($member, $s['gold'])->available_balance, '30', 8));
    }

    public function test_quote_for_a_pair_without_pool_does_not_create_a_pool(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $silver = $this->token($s['admin'], 'SILV');
        $before = TPIXLiquidityPool::count();

        $this->actingAs($member)->postJson(route('user.dex.quote'), [
            'token_in_id' => $s['tpix']->id, 'token_out_id' => $silver->id, 'amount_in' => '5',
        ])->assertOk()->assertJsonPath('data.amount_out', '0');

        $this->actingAs($member)->postJson(route('user.dex.swap.execute'), [
            'token_in_id' => $s['tpix']->id, 'token_out_id' => $silver->id, 'amount_in' => '5', 'min_amount_out' => '0',
        ])->assertStatus(400)->assertJsonPath('error', 'Pool not found');

        $this->assertSame($before, TPIXLiquidityPool::count());
    }

    public function test_swap_beyond_balance_is_rejected_without_changes(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);

        $this->actingAs($member)->postJson(route('user.dex.swap.execute'), [
            'token_in_id' => $s['tpix']->id, 'token_out_id' => $s['gold']->id, 'amount_in' => '5000', 'min_amount_out' => '0',
        ])->assertStatus(400)->assertJsonPath('error', 'Insufficient balance');

        $this->assertSame(0, bccomp($s['pool']->fresh()->reserve_a, '1000', 8));
        $this->assertSame(0, bccomp($this->balanceOf($member, $s['tpix'])->available_balance, '875.5', 8));
    }

    public function test_adding_liquidity_beyond_balance_is_rejected(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $this->giveBalance($member, $s['gold'], '10');
        $positions = TPIXLiquidityPosition::count();

        $this->actingAs($member)->postJson(route('user.dex.liquidity.add'), [
            'token_a_id' => $s['tpix']->id, 'token_b_id' => $s['gold']->id, 'amount_a' => '100', 'amount_b' => '200',
        ])->assertStatus(400)->assertJsonPath('error', 'Insufficient balance');

        $this->assertSame(0, bccomp($this->balanceOf($member, $s['gold'])->available_balance, '10', 8));
        $this->assertSame(0, bccomp($this->balanceOf($member, $s['tpix'])->available_balance, '875.5', 8));
        $this->assertSame($positions, TPIXLiquidityPosition::count());
        $this->assertSame(0, bccomp($s['pool']->fresh()->reserve_a, '1000', 8));
    }

    public function test_adding_liquidity_in_reverse_order_keeps_pool_reserves_on_the_right_side(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $this->giveBalance($member, $s['gold'], '100');

        // เลือก GOLD เป็นฝั่ง A (กลับทิศกับพูล ที่ TPIX เป็น token_a)
        $res = $this->actingAs($member)->postJson(route('user.dex.liquidity.add'), [
            'token_a_id' => $s['gold']->id, 'token_b_id' => $s['tpix']->id, 'amount_a' => '20', 'amount_b' => '10',
        ])->assertOk();

        $pool = $s['pool']->fresh();
        $this->assertSame(0, bccomp($pool->reserve_a, '1010', 8));
        $this->assertSame(0, bccomp($pool->reserve_b, '2020', 8));
        $this->assertSame(0, bccomp($pool->total_liquidity, '1428.14', 8));

        $position = TPIXLiquidityPosition::find($res->json('data.position_id'));
        $this->assertSame(0, bccomp($position->token_a_amount, '10', 8));
        $this->assertSame(0, bccomp($position->token_b_amount, '20', 8));
        $this->assertSame(0, bccomp($position->lp_tokens, '14.14', 8));
        $this->assertSame(0, bccomp($this->balanceOf($member, $s['gold'])->available_balance, '80', 8));
    }

    public function test_opening_a_new_pair_creates_the_pool_with_total_liquidity(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $silver = $this->token($s['admin'], 'SILV');
        $this->giveBalance($member, $silver, '100');

        $this->actingAs($member)->postJson(route('user.dex.liquidity.add'), [
            'token_a_id' => $s['tpix']->id, 'token_b_id' => $silver->id, 'amount_a' => '25', 'amount_b' => '100',
        ])->assertOk();

        $pool = TPIXLiquidityPool::where('token_a_id', $s['tpix']->id)->where('token_b_id', $silver->id)->firstOrFail();
        $this->assertSame(0, bccomp($pool->total_liquidity, '50', 8));
        $this->assertSame(0, bccomp($pool->reserve_a, '25', 8));
        $this->assertSame(0, bccomp($pool->reserve_b, '100', 8));
    }

    public function test_removing_liquidity_returns_tokens_and_updates_pool_totals(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);

        $this->actingAs($member)->postJson(route('user.dex.liquidity.remove'), [
            'position_id' => $s['position']->id, 'lp_tokens' => '141.4',
        ])->assertOk();

        $pool = $s['pool']->fresh();
        $this->assertSame(0, bccomp($pool->reserve_a, '900', 8));
        $this->assertSame(0, bccomp($pool->reserve_b, '1800', 8));
        $this->assertSame(0, bccomp($pool->total_liquidity, '1272.6', 8));

        $this->assertSame(0, bccomp($this->balanceOf($member, $s['tpix'])->available_balance, '975.5', 8));
        $this->assertSame(0, bccomp($this->balanceOf($member, $s['gold'])->available_balance, '200', 8));

        $position = $s['position']->fresh();
        $this->assertSame('removed', $position->status);
        $this->assertNotNull($position->removed_at);
    }

    public function test_removing_liquidity_from_a_legacy_pool_with_zero_total_uses_position_sum(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);
        $s['pool']->forceFill(['total_liquidity' => '0'])->save();

        // หน้าถอนแสดงส่วนแบ่งจริง (เดิมขึ้น 0)
        $this->actingAs($member)->get(route('user.dex.remove-liquidity', $s['position']->id))
            ->assertOk()->assertSee('0022a'.chr(92).'u0022:1000,', false);

        $this->actingAs($member)->postJson(route('user.dex.liquidity.remove'), [
            'position_id' => $s['position']->id, 'lp_tokens' => '70.7',
        ])->assertOk();

        $this->assertSame(0, bccomp($this->balanceOf($member, $s['tpix'])->available_balance, '1375.5', 8));
        $this->assertSame('active', $s['position']->fresh()->status);
    }

    public function test_stake_claim_and_unstake_succeed_through_web_routes(): void
    {
        $admin = $this->member(['role' => 'admin']);
        $tpix = $this->token($admin, 'TPIX');
        $pool = $this->stakingPool($admin, $tpix);
        $member = $this->member();
        $this->giveBalance($member, $tpix, '1000');

        $this->actingAs($member)->postJson(route('user.staking.stake'), [
            'pool_id' => $pool->id, 'amount' => '100', 'lock_period_days' => 30,
        ])->assertOk()->assertJsonPath('success', true);

        $stake = TPIXStake::where('user_id', $member->id)->firstOrFail();
        $this->assertSame(0, bccomp($stake->amount, '100', 8));
        $this->assertSame($tpix->id, (int) $stake->token_id);
        $this->assertTrue($stake->unlock_at->isFuture());
        $balance = $this->balanceOf($member, $tpix);
        $this->assertSame(0, bccomp($balance->available_balance, '900', 8));
        $this->assertSame(0, bccomp($balance->staked_balance, '100', 8));
        $this->assertSame(0, bccomp($pool->fresh()->total_staked, '600', 8));
        $this->assertSame(4, (int) $pool->fresh()->current_stakers);

        // รับรางวัลหลัง 10 วัน: 100 × 12.5% × 10/365 ≈ 0.3425
        $this->travel(10)->days();
        $claimed = $this->actingAs($member)->postJson(route('user.staking.claim'), ['pool_id' => $pool->id])
            ->assertOk()->json('data.claimed_amount');
        $this->assertEqualsWithDelta(0.3425, (float) $claimed, 0.001);
        $this->assertSame(0, bccomp($this->balanceOf($member, $tpix)->available_balance, bcadd('900', $claimed, 8), 8));

        // ยังอยู่ในช่วงล็อก → ถอนไม่ได้
        $this->actingAs($member)->postJson(route('user.staking.unstake'), ['pool_id' => $pool->id])
            ->assertStatus(400)->assertJsonPath('success', false);
        $this->assertSame('active', $stake->fresh()->status);

        // พ้นช่วงล็อก → ถอนได้พร้อมรางวัลที่เหลือ
        $this->travel(21)->days();
        $res = $this->actingAs($member)->postJson(route('user.staking.unstake'), ['pool_id' => $pool->id])->assertOk();

        $stake->refresh();
        $this->assertSame('unstaked', $stake->status);
        $this->assertNotNull($stake->unstaked_at);
        $balance = $this->balanceOf($member, $tpix);
        $this->assertSame(0, bccomp($balance->staked_balance, '0', 8));
        $this->assertSame(1, bccomp($balance->available_balance, bcadd('1000', $claimed, 8), 8));
        $this->assertSame(0, bccomp($pool->fresh()->total_staked, '500', 8));
        $this->assertSame(3, (int) $pool->fresh()->current_stakers);
        $this->assertGreaterThan(0, (float) $res->json('data.rewards'));
    }

    public function test_staking_into_a_pool_that_is_not_open_is_rejected(): void
    {
        $admin = $this->member(['role' => 'admin']);
        $tpix = $this->token($admin, 'TPIX');
        $member = $this->member();
        $this->giveBalance($member, $tpix, '1000');

        foreach (['draft', 'paused', 'ended'] as $status) {
            $pool = $this->stakingPool($admin, $tpix, ['status' => $status]);
            $this->actingAs($member)->postJson(route('user.staking.stake'), ['pool_id' => $pool->id, 'amount' => '100'])
                ->assertStatus(400)->assertJsonPath('success', false);
        }

        $expired = $this->stakingPool($admin, $tpix, ['ends_at' => now()->subDay()]);
        $this->actingAs($member)->postJson(route('user.staking.stake'), ['pool_id' => $expired->id, 'amount' => '100'])
            ->assertStatus(400);

        $full = $this->stakingPool($admin, $tpix, ['max_stakers' => 3]);
        $this->actingAs($member)->postJson(route('user.staking.stake'), ['pool_id' => $full->id, 'amount' => '100'])
            ->assertStatus(400);

        $capped = $this->stakingPool($admin, $tpix, ['pool_cap' => '550']);
        $this->actingAs($member)->postJson(route('user.staking.stake'), ['pool_id' => $capped->id, 'amount' => '100'])
            ->assertStatus(400);

        $this->assertSame(0, TPIXStake::where('user_id', $member->id)->count());
        $this->assertSame(0, bccomp($this->balanceOf($member, $tpix)->available_balance, '1000', 8));
    }

    public function test_stake_lock_period_follows_the_pool(): void
    {
        $admin = $this->member(['role' => 'admin']);
        $tpix = $this->token($admin, 'TPIX');
        $pool = $this->stakingPool($admin, $tpix);
        $member = $this->member();
        $this->giveBalance($member, $tpix, '1000');

        // พูลล็อก 30 วัน → ขอแบบไม่ล็อก (0 วัน) ไม่ได้
        $this->actingAs($member)->postJson(route('user.staking.stake'), [
            'pool_id' => $pool->id, 'amount' => '100', 'lock_period_days' => 0,
        ])->assertStatus(400)->assertJsonPath('success', false);
        $this->assertSame(0, TPIXStake::where('user_id', $member->id)->count());

        // ฟอร์มแสดงระยะล็อกของพูลเท่านั้น ไม่มีตัวเลือกระยะอื่น
        $this->actingAs($member)->get(route('user.staking.stake-form', $pool->id))
            ->assertOk()
            ->assertSee('ล็อก 30 วัน')
            ->assertDontSee('ยืดหยุ่น')
            ->assertDontSee('name="lock_period_days"', false);
    }

    public function test_stake_beyond_balance_is_rejected(): void
    {
        $admin = $this->member(['role' => 'admin']);
        $tpix = $this->token($admin, 'TPIX');
        $pool = $this->stakingPool($admin, $tpix);
        $member = $this->member();
        $this->giveBalance($member, $tpix, '50');

        $this->actingAs($member)->postJson(route('user.staking.stake'), ['pool_id' => $pool->id, 'amount' => '100'])
            ->assertStatus(400)->assertJsonPath('message', 'ยอดคงเหลือไม่พอ');
    }

    public function test_unexpected_errors_do_not_leak_raw_exception_text(): void
    {
        $member = $this->member();
        $s = $this->scenario($member);

        $this->mock(\App\Services\TPIX\DEXService::class, function ($mock) {
            $mock->shouldReceive('getQuote')->andThrow(new \RuntimeException('SQLSTATE[42S22] secret column'));
        });

        $res = $this->actingAs($member)->postJson(route('user.dex.quote'), [
            'token_in_id' => $s['tpix']->id, 'token_out_id' => $s['gold']->id, 'amount_in' => '1',
        ])->assertStatus(500)->assertJsonPath('error', 'Request failed');

        $this->assertStringNotContainsString('SQLSTATE', $res->getContent());
    }

    public function test_history_csv_button_says_it_exports_only_the_current_page(): void
    {
        $member = $this->member();
        $this->scenario($member);

        $this->actingAs($member)->get(route('user.dex.swap-history'))->assertOk()->assertSee('ส่งออกหน้านี้');
        $this->actingAs($member)->get(route('user.staking.history'))->assertOk()->assertSee('ส่งออกหน้านี้');
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
