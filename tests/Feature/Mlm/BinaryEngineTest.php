<?php

namespace Tests\Feature\Mlm;

use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\Order;
use App\Models\User;
use App\Services\MlmBinaryService;
use App\Services\MlmCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Engine binary ขั้น 2a (audit F1-F4, เจ้าของตัดสิน M2)
 *
 * ค่าตั้งเหมือน prod (2026-11-03) ยกเว้นเปิด binary: คู่ละ 100 บาท, 1:1, ไม่ยกยอด, flush 100%,
 * เพดานคู่/เงินต่อวันว่าง, budget ต่อออเดอร์ 50% ของ PV × 1 บาท, ปิดค่าแนะนำตรง
 * เรียกเส้นทางเงินจริง MlmCommissionService::processOrderCommissions (ตัวที่ OrderDistributionService ใช้)
 *
 * ผัง:  G ── S(ซ้ายของ G) ─┬─ L (ซ้ายของ S)
 *                          └─ R (ขวาของ S)
 */
class BinaryEngineTest extends TestCase
{
    use RefreshDatabase;

    protected MlmPlan $plan;

    protected MlmMember $g;

    protected MlmMember $s;

    protected MlmMember $l;

    protected MlmMember $r;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings([
            'mlm_enabled' => ['1', 'boolean'],
            'binary_enabled' => ['1', 'boolean'],
            'unilevel_enabled' => ['0', 'boolean'],
            'direct_referral_bonus_enabled' => ['0', 'boolean'],
            'binary_pair_commission' => ['100.00', 'decimal'],
            'binary_pairing_type' => ['1:1', 'string'],
            'binary_max_pairs_per_day' => [null, 'integer'],
            'binary_max_commission_per_day' => [null, 'decimal'],
            'binary_flush_mode' => ['percentage', 'string'],
            'binary_flush_percentage' => ['100.00', 'decimal'],
            'binary_carry_forward_enabled' => ['0', 'boolean'],
            'binary_carry_forward_days' => ['30', 'integer'],
            'max_commission_percentage' => ['50.00', 'decimal'],
            'commission_per_pv' => ['1', 'decimal'],
            'volume_retention_enabled' => ['1', 'boolean'],
            'volume_retention_monthly_pv' => ['100', 'decimal'],
            'volume_retention_grace_days' => ['7', 'integer'],
        ]);
        // ค่าที่ยังไม่มีแถวบน prod ต้องใช้ค่าเริ่มต้นของ engine (100 PV/คู่)
        DB::table('mlm_global_settings')->where('key', 'binary_pair_unit_pv')->delete();
        Cache::flush();

        $this->plan = MlmPlan::create([
            'name' => 'Hybrid', 'name_th' => 'ไฮบริด', 'slug' => 'hybrid-'.Str::random(5),
            'type' => 'hybrid', 'is_active' => true, 'is_default' => true,
        ]);

        $this->g = $this->member('G');
        $this->s = $this->member('S', ['binary_parent_id' => $this->g->id, 'binary_position' => 'left', 'unilevel_sponsor_id' => $this->g->id]);
        $this->l = $this->member('L', ['binary_parent_id' => $this->s->id, 'binary_position' => 'left', 'unilevel_sponsor_id' => $this->s->id]);
        $this->r = $this->member('R', ['binary_parent_id' => $this->s->id, 'binary_position' => 'right', 'unilevel_sponsor_id' => $this->s->id]);
    }

    /** ตั้งค่า MLM ตรงๆ ใน DB (รองรับค่า NULL แบบ prod) แล้วล้าง cache */
    protected function settings(array $map): void
    {
        foreach ($map as $key => [$value, $type]) {
            DB::table('mlm_global_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'type' => $type]);
        }
        Cache::flush();
    }

    protected function member(string $tag, array $attrs = [], bool $active = true): MlmMember
    {
        $user = User::factory()->create(['name' => 'Binary '.$tag]);

        return MlmMember::create(array_merge([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'BIN-'.$tag.'-'.Str::random(4),
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonth(),
            // grace period = active ตามเกณฑ์รักษายอด
            'last_purchase_at' => $active ? now() : null,
        ], $attrs));
    }

    /** ออเดอร์ที่จ่ายแล้วของสมาชิก แล้วเรียกคำนวณคอมแบบที่ OrderDistributionService เรียก */
    protected function buy(MlmMember $buyer, float $pv, ?float $total = null): Order
    {
        $total ??= $pv;
        $order = Order::create([
            'user_id' => $buyer->user_id,
            'status' => 'paid',
            'payment_method' => 'promptpay',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'subtotal' => $total,
            'shipping_fee' => 0,
            'total_amount' => $total,
        ]);

        app(MlmCommissionService::class)->processOrderCommissions($order, ['total_pv' => $pv]);

        return $order;
    }

    /** ออเดอร์ที่มีแถว PV แบบ OrderDistributionService (MlmPvService) แล้วเรียก engine */
    protected function buyWithPvRow(MlmMember $buyer, float $pv): Order
    {
        $order = Order::create([
            'user_id' => $buyer->user_id,
            'status' => 'paid',
            'payment_method' => 'promptpay',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'subtotal' => $pv,
            'shipping_fee' => 0,
            'total_amount' => $pv,
        ]);
        (new \App\Services\MlmPvService)->recordPvTransaction($buyer->fresh(), $order, ['total_pv' => $pv]);
        app(MlmCommissionService::class)->processOrderCommissions($order, ['total_pv' => $pv]);

        return $order;
    }

    protected function pairs(Order $order, ?MlmMember $to = null)
    {
        return MlmCommission::where('source_type', Order::class)
            ->where('source_id', $order->id)
            ->where('type', 'binary_pair')
            ->when($to, fn ($q) => $q->where('mlm_member_id', $to->id))
            ->get();
    }

    protected function legs(MlmMember $m): array
    {
        $m = $m->fresh();

        return [
            (float) $m->left_leg_pv, (float) $m->right_leg_pv,
            (float) $m->carried_left_pv, (float) $m->carried_right_pv,
        ];
    }

    // ------------------------------------------------------------------ F1

    public function test_pair_unit_is_100pv_and_pair_money_stays_inside_the_order_budget(): void
    {
        $this->buy($this->l, 1000);
        $this->assertSame([1000.0, 0.0, 0.0, 0.0], $this->legs($this->s));

        // 1,000 vs 1,000 PV = 10 คู่ (ไม่ใช่ 1,000 คู่) แต่ budget ออเดอร์ = 50% × 1,000 = 500 → จ่าย 5 คู่
        $o2 = $this->buy($this->r, 1000);
        $rows = $this->pairs($o2, $this->s);
        $this->assertCount(1, $rows);
        $this->assertSame(5, (int) $rows[0]->pairs_count);
        $this->assertEqualsWithDelta(500.0, (float) $rows[0]->commission_amount, 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $rows[0]->calculation_details['pair_unit_pv'], 0.001);
        // 5 คู่ที่ยังไม่มีเงินจ่าย เก็บ PV ไว้ (ไม่ flush) รอออเดอร์ถัดไป
        $this->assertSame([500.0, 500.0, 0.0, 0.0], $this->legs($this->s));

        $orderTotal = (float) MlmCommission::where('source_id', $o2->id)->sum('commission_amount');
        $this->assertLessThanOrEqual(500.0, $orderTotal, 'คอมทั้งออเดอร์ต้องไม่เกิน budget 50%');

        // ออเดอร์ขวาอีก 1,000: ขาขวา 1,500 ซ้าย 500 → 5 คู่ (ครบเพดานวันละ 10) เศษขวา 1,000 ถูก flush (ไม่ยกยอด)
        $o3 = $this->buy($this->r, 1000);
        $this->assertSame(5, (int) $this->pairs($o3, $this->s)->sum('pairs_count'));
        $this->assertEqualsWithDelta(500.0, (float) $this->pairs($o3)->sum('commission_amount'), 0.001);
        $this->assertSame([0.0, 0.0, 0.0, 0.0], $this->legs($this->s));
    }

    public function test_binary_gets_only_what_is_left_after_unilevel_on_the_same_order(): void
    {
        $this->settings([
            'unilevel_enabled' => ['1', 'boolean'],
            'unilevel_levels' => [json_encode([['level' => 1, 'percentage' => 5], ['level' => 2, 'percentage' => 3]]), 'json'],
            // ตั้งเพดานต่อชั้นชัดเจน เพื่อไม่ขึ้นกับ F6 (NULL ถูกอ่านเป็น 0) ซึ่งแก้ในขั้น 2d
            'unilevel_max_commission_per_level' => ['1000', 'decimal'],
            'rollup_enabled' => ['0', 'boolean'],
        ]);

        $this->buy($this->l, 1000);
        $o2 = $this->buy($this->r, 1000);

        $all = MlmCommission::where('source_type', Order::class)->where('source_id', $o2->id)->get();
        $unilevel = (float) $all->filter(fn ($c) => str_starts_with($c->type, 'unilevel'))->sum('commission_amount');
        $binary = (float) $all->where('type', 'binary_pair')->sum('commission_amount');

        $this->assertEqualsWithDelta(80.0, $unilevel, 0.001, 'S 5% (50) + G 3% (30)');
        // budget 500 − unilevel 80 = 420 → 4 คู่เต็ม = 400
        $this->assertEqualsWithDelta(400.0, $binary, 0.001);
        $this->assertLessThanOrEqual(500.0, $unilevel + $binary);
    }

    public function test_daily_pair_cap_defaults_to_10_when_setting_is_null_and_is_respected_across_orders(): void
    {
        // คู่ละ 10 บาท เพื่อให้ budget ไม่ใช่ตัวจำกัด — เหลือแค่เพดานรายวัน
        $this->settings(['binary_pair_commission' => ['10', 'decimal']]);

        $this->buy($this->l, 3000);
        $o2 = $this->buy($this->r, 3000);
        $this->assertSame(10, (int) $this->pairs($o2, $this->s)->sum('pairs_count'), '30 คู่ที่มี ถูกตัดเหลือ 10 ตามค่าเริ่มต้น');
        // เกินเพดาน + ไม่ยกยอด → flush ทั้งหมด
        $this->assertSame([0.0, 0.0, 0.0, 0.0], $this->legs($this->s));

        $this->buy($this->l, 500);
        $o4 = $this->buy($this->r, 500);
        $this->assertCount(0, $this->pairs($o4, $this->s), 'วันนี้ครบ 10 คู่แล้ว');
        $this->assertSame(10, (int) MlmCommission::where('mlm_member_id', $this->s->id)->where('type', 'binary_pair')->sum('pairs_count'));
    }

    public function test_explicit_daily_pair_cap_is_used(): void
    {
        $this->settings(['binary_pair_commission' => ['10', 'decimal'], 'binary_max_pairs_per_day' => ['3', 'integer']]);

        $this->buy($this->l, 1000);
        $o2 = $this->buy($this->r, 1000);

        $this->assertSame(3, (int) $this->pairs($o2, $this->s)->sum('pairs_count'));
    }

    public function test_daily_money_cap_limits_pairs(): void
    {
        $this->settings(['binary_max_commission_per_day' => ['250', 'decimal']]);

        $this->buy($this->l, 1000);
        $o2 = $this->buy($this->r, 1000);

        // budget 500 จ่ายได้ 5 คู่ แต่เพดานเงิน 250/วัน ที่คู่ละ 100 → 2 คู่ (200)
        $this->assertSame(2, (int) $this->pairs($o2, $this->s)->sum('pairs_count'));
        $this->assertEqualsWithDelta(200.0, (float) $this->pairs($o2, $this->s)->sum('commission_amount'), 0.001);
    }

    public function test_zero_daily_pair_cap_means_default_10_pairs(): void
    {
        $this->settings(['binary_pair_commission' => ['10', 'decimal'], 'binary_max_pairs_per_day' => ['0', 'integer']]);

        $this->buy($this->l, 2000);
        $o2 = $this->buy($this->r, 2000);

        // 20 คู่พร้อมจับ budget พอ แต่ค่า 0 = เพดานเริ่มต้น 10 คู่ (ไม่ใช่ไม่จำกัด)
        $this->assertSame(10, (int) $this->pairs($o2, $this->s)->sum('pairs_count'));
    }

    /**
     * ออเดอร์เดียวถูกคำนวณสองรอบ (ยังไม่เกิดคู่ → ไม่มีแถวคอมให้ตัวกันซ้ำเห็น) → PV ขาต้องลงครั้งเดียว
     */
    public function test_running_the_same_order_twice_attributes_leg_pv_once(): void
    {
        $order = $this->buyWithPvRow($this->l, 1000);
        app(MlmCommissionService::class)->processOrderCommissions($order->fresh(), ['total_pv' => 1000]);

        $this->assertSame([1000.0, 0.0, 0.0, 0.0], $this->legs($this->s), 'ขาซ้าย S ต้องเป็น 1,000 ไม่ใช่ 2,000');
        $this->assertSame([1000.0, 0.0, 0.0, 0.0], $this->legs($this->g));
        $credits = \App\Models\MlmPvTransaction::where('order_id', $order->id)->where('transaction_type', 'purchase')->sole()->binary_leg_credits;
        $this->assertCount(2, $credits, 'รายการลงขาต้องไม่ซ้ำ (S และ G คนละ 1 รายการ)');

        $o2 = $this->buyWithPvRow($this->r, 1000);
        $this->assertSame(5, (int) $this->pairs($o2, $this->s)->sum('pairs_count'), 'จับคู่จาก PV จริง 1,000 (budget 500 = 5 คู่)');
    }

    /**
     * ออเดอร์ที่ upline ไม่ active เลย → ยังต้องกันรอบสองไม่ให้ลง PV (เครื่องหมาย m = 0)
     */
    public function test_rerun_is_blocked_even_when_no_upline_was_active(): void
    {
        MlmMember::whereIn('id', [$this->s->id, $this->g->id])->update(['last_purchase_at' => null]);
        Cache::flush();
        $order = $this->buyWithPvRow($this->l, 1000);
        MlmMember::whereIn('id', [$this->s->id, $this->g->id])->update(['last_purchase_at' => now()]);
        Cache::flush();

        app(MlmCommissionService::class)->processOrderCommissions($order->fresh(), ['total_pv' => 1000]);

        $this->assertSame([0.0, 0.0, 0.0, 0.0], $this->legs($this->s), 'รอบสองต้องไม่ลง PV ย้อนหลังให้คนที่เพิ่งกลับมา active');
    }

    public function test_carried_pv_keeps_its_original_expiry(): void
    {
        $this->settings([
            'binary_pair_commission' => ['10', 'decimal'],
            'binary_carry_forward_enabled' => ['1', 'boolean'],
            'binary_max_pairs_per_day' => ['100', 'integer'],
        ]);

        $this->buy($this->l, 3000);
        $this->buy($this->r, 1000);
        $first = $this->s->fresh()->carried_left_pv_expires_at;
        $this->assertNotNull($first);

        $this->travel(5)->days();
        $this->buy($this->r, 500); // จับคู่ 5 คู่จากยอดยกมา เหลือยกต่อ 1,500 (ก้อนเดิม)
        $this->assertSame([0.0, 0.0, 1500.0, 0.0], $this->legs($this->s));

        $this->assertTrue(
            $this->s->fresh()->carried_left_pv_expires_at->equalTo($first),
            'วันหมดอายุของ PV ยกยอดต้องไม่ถูกต่ออายุทุกครั้งที่มีออเดอร์ผ่าน'
        );
    }

    // ------------------------------------------------------------------ F2

    public function test_leftover_is_not_double_counted_when_carry_forward_is_off(): void
    {
        $this->settings(['binary_pair_commission' => ['10', 'decimal']]);

        $this->buy($this->l, 3000);
        $o2 = $this->buy($this->r, 1000);
        $this->assertSame(10, (int) $this->pairs($o2, $this->s)->sum('pairs_count'));
        // เดิม: leg 2,000 + carried 2,000 (นับซ้ำ) แม้ปิดยกยอด → ตอนนี้ flush 100% ทั้งก้อน
        $this->assertSame([0.0, 0.0, 0.0, 0.0], $this->legs($this->s));

        $o3 = $this->buy($this->r, 1000);
        $this->assertCount(0, $this->pairs($o3, $this->s), 'ขาซ้ายว่างแล้ว ห้ามจับคู่จากเศษเดิม');
    }

    public function test_partial_flush_keeps_rest_in_leg_only(): void
    {
        $this->settings(['binary_pair_commission' => ['10', 'decimal'], 'binary_flush_percentage' => ['50', 'decimal']]);

        $this->buy($this->l, 3000);
        $this->buy($this->r, 1000);

        // เศษขาซ้าย 2,000 ล้าง 50% เหลือ 1,000 อยู่ใน leg ที่เดียว
        $this->assertSame([1000.0, 0.0, 0.0, 0.0], $this->legs($this->s));
    }

    public function test_leftover_moves_to_carried_only_when_carry_forward_is_on(): void
    {
        $this->settings([
            'binary_pair_commission' => ['10', 'decimal'],
            'binary_carry_forward_enabled' => ['1', 'boolean'],
            'binary_max_pairs_per_day' => ['100', 'integer'],
        ]);

        $this->buy($this->l, 3000);
        $this->buy($this->r, 1000);
        $this->assertSame([0.0, 0.0, 2000.0, 0.0], $this->legs($this->s), 'เศษ 2,000 อยู่ที่ carried ที่เดียว (ไม่ใช่ 4,000)');
        $this->assertNotNull($this->s->fresh()->carried_left_pv_expires_at);

        $o3 = $this->buy($this->r, 1000);
        $this->assertSame(10, (int) $this->pairs($o3, $this->s)->sum('pairs_count'));
        $this->assertSame([0.0, 0.0, 1000.0, 0.0], $this->legs($this->s));
    }

    // ------------------------------------------------------------------ F3

    public function test_upline_rows_are_locked_and_legs_never_go_negative(): void
    {
        $this->buy($this->l, 1000);
        // ขาซ้ายของ S ถูกแก้ตรงใน DB (เช่น ออเดอร์อื่นที่เพิ่ง commit) — engine ต้องอ่านค่าจากแถวที่ล็อก ไม่ใช่ค่าที่อ่านไว้ก่อน
        DB::table('mlm_members')->where('id', $this->s->id)->update(['left_leg_pv' => 300]);

        $locks = [];
        DB::listen(function ($q) use (&$locks) {
            if (str_contains($q->sql, 'mlm_members') && str_contains(strtolower($q->sql), 'for update')) {
                // ORDER BY id อยู่ใน SQL แล้ว (ล็อกเรียง id) — เทียบชุด id ที่ถูกล็อก
                $ids = array_map('intval', $q->bindings);
                sort($ids);
                $locks[] = $ids;
                $this->assertStringContainsString('order by', strtolower($q->sql));
            }
        });

        $o2 = $this->buy($this->r, 1000);

        // ผู้ซื้อ R → สาย upline คือ S และ G ต้องถูกล็อกครบในคำสั่งเดียว เรียง id
        $chainIds = [(int) $this->s->id, (int) $this->g->id];
        sort($chainIds);
        $this->assertContains($chainIds, $locks, 'ต้อง lockForUpdate แถว upline ทั้งสาย (เรียง id) ก่อนแก้ PV ขา');
        $this->assertSame(3, (int) $this->pairs($o2, $this->s)->sum('pairs_count'), 'จับคู่จากค่าในแถวที่ล็อก (300 PV = 3 คู่) ไม่ใช่ค่าเก่า 1,000');

        foreach (MlmMember::all() as $m) {
            foreach (['left_leg_pv', 'right_leg_pv', 'carried_left_pv', 'carried_right_pv'] as $col) {
                $this->assertGreaterThanOrEqual(0.0, (float) $m->{$col}, "{$m->member_code}.{$col}");
            }
        }
    }

    // ------------------------------------------------------------------ F4

    public function test_inactive_member_does_not_accumulate_or_pair_and_gets_nothing_when_reactivated(): void
    {
        // S ไม่รักษายอด (ไม่มีการเคลื่อนไหว) — G ยัง active
        MlmMember::whereKey($this->s->id)->update(['last_purchase_at' => null]);
        Cache::flush();

        $this->buy($this->l, 1000);
        $o2 = $this->buy($this->r, 1000);

        $this->assertCount(0, $this->pairs($o2));
        $this->assertSame([0.0, 0.0, 0.0, 0.0], $this->legs($this->s), 'คนไม่ active ไม่สะสม PV ขา');
        // PV ยังผ่าน S ขึ้นไปถึง G ตามปกติ (ทั้งคู่อยู่ขาซ้ายของ G)
        $this->assertSame([2000.0, 0.0, 0.0, 0.0], $this->legs($this->g));

        // S กลับมา active แล้วมีออเดอร์ขวาเล็กๆ → ไม่มี PV เก่าให้ขึ้นเงินก้อนใหญ่
        MlmMember::whereKey($this->s->id)->update(['last_purchase_at' => now()]);
        $o3 = $this->buy($this->r, 100);
        $this->assertCount(0, $this->pairs($o3, $this->s));
        $this->assertSame([0.0, 100.0, 0.0, 0.0], $this->legs($this->s));
    }

    public function test_flush_inactive_command_is_dry_run_unless_execute_flag_is_given(): void
    {
        MlmMember::whereKey($this->s->id)->update(['last_purchase_at' => null, 'left_leg_pv' => 500, 'carried_right_pv' => 300]);
        MlmMember::whereKey($this->g->id)->update(['left_leg_pv' => 700]);

        Artisan::call('mlm:binary-flush-inactive');
        $this->assertSame([500.0, 0.0, 0.0, 300.0], $this->legs($this->s), 'dry-run ห้ามเขียน DB');

        Artisan::call('mlm:binary-flush-inactive', ['--execute' => true]);
        $this->assertSame([0.0, 0.0, 0.0, 0.0], $this->legs($this->s));
        $this->assertSame([700.0, 0.0, 0.0, 0.0], $this->legs($this->g), 'คนที่ active ไม่ถูกล้าง');
    }

    // ------------------------------------------------------------------ ค่าตั้ง + หน้าจอ

    public function test_pair_unit_setting_is_configurable_and_validated(): void
    {
        MlmGlobalSetting::set('binary_pair_unit_pv', 50);
        $this->settings(['binary_pair_commission' => ['10', 'decimal']]);

        $this->buy($this->l, 300);
        $o2 = $this->buy($this->r, 300);
        $this->assertSame(6, (int) $this->pairs($o2, $this->s)->sum('pairs_count'), '300 / 50 = 6 คู่');

        $est = app(MlmBinaryService::class)->estimatePairs($this->s->fresh()->forceFill(['left_leg_pv' => 250, 'right_leg_pv' => 1000]));
        $this->assertSame(5, $est['pairs']);

        $this->expectException(\InvalidArgumentException::class);
        MlmGlobalSetting::set('binary_pair_unit_pv', 0);
    }
}
