<?php

namespace Tests\Feature\Mlm;

use App\Helpers\MlmRetentionHelper;
use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\MlmPvTransaction;
use App\Models\Order;
use App\Models\PlatformWallet;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Services\MlmCalculationService;
use App\Services\MlmCommissionClawbackService;
use App\Services\MlmCommissionService;
use App\Services\MlmPvService;
use App\Services\RefundService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * คืนเงินออเดอร์แล้วย้อน PV + ดึงคู่ binary คืน (ขั้น 2e — audit F11, F13, เจ้าของตัดสิน M6)
 *
 * ค่าตั้งเหมือน prod (2026-11-03) ยกเว้นเปิด binary: คู่ละ 100 บาท, 1 คู่ = 100 PV, 1:1, ไม่ยกยอด, flush 100%,
 * budget ต่อออเดอร์ 50% ของ PV × 1 บาท, ปิด unilevel/ค่าแนะนำตรง (ให้เห็นเฉพาะเงินคู่)
 * ลง PV เหมือน OrderDistributionService (MlmPvService::recordPvTransaction + total_pv) แล้วเรียก engine จริง
 * คืนเงินผ่าน RefundService::processFullRefund (ตัวที่ปุ่มคืนเงินของแอดมินเรียก)
 *
 * ผัง:  S ─┬─ L (ซ้าย)
 *          └─ R (ขวา)
 */
class RefundReversalTest extends TestCase
{
    use RefreshDatabase;

    protected MlmPlan $plan;

    protected MlmMember $s;

    protected MlmMember $l;

    protected MlmMember $r;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Mail::fake();
        Queue::fake();

        foreach ([
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
            'max_commission_percentage' => ['50.00', 'decimal'],
            'commission_per_pv' => ['1', 'decimal'],
            'volume_retention_enabled' => ['1', 'boolean'],
            'volume_retention_monthly_pv' => ['100', 'decimal'],
            'volume_retention_grace_days' => ['7', 'integer'],
        ] as $key => [$value, $type]) {
            DB::table('mlm_global_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'type' => $type]);
        }
        DB::table('mlm_global_settings')->where('key', 'binary_pair_unit_pv')->delete();
        Cache::flush();

        $this->plan = MlmPlan::create([
            'name' => 'Hybrid', 'name_th' => 'ไฮบริด', 'slug' => 'hybrid-'.Str::random(5),
            'type' => 'hybrid', 'is_active' => true, 'is_default' => true,
        ]);

        $this->s = $this->member('S');
        $this->l = $this->member('L', ['binary_parent_id' => $this->s->id, 'binary_position' => 'left', 'unilevel_sponsor_id' => $this->s->id]);
        $this->r = $this->member('R', ['binary_parent_id' => $this->s->id, 'binary_position' => 'right', 'unilevel_sponsor_id' => $this->s->id]);
    }

    protected function member(string $tag, array $attrs = [], bool $active = true): MlmMember
    {
        $user = User::factory()->create(['name' => 'Refund '.$tag]);
        Wallet::create(['user_id' => $user->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        return MlmMember::create(array_merge([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'RF-'.$tag.'-'.Str::random(4),
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonth(),
            'last_purchase_at' => $active ? now() : null,
        ], $attrs));
    }

    /** ออเดอร์ที่จ่ายแล้ว: ลง PV แบบ OrderDistributionService แล้วคำนวณคอมด้วย engine จริง */
    protected function buy(MlmMember $buyer, float $pv): Order
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

        $pvData = ['total_pv' => $pv];
        $fresh = $buyer->fresh();
        (new MlmPvService)->recordPvTransaction($fresh, $order, $pvData);
        $fresh->increment('total_pv', $pv);
        $fresh->update(['last_purchase_at' => now()]);
        app(MlmCommissionService::class)->processOrderCommissions($order, $pvData);

        return $order->fresh();
    }

    protected function refund(Order $order): array
    {
        return app(RefundService::class)->processFullRefund($order->fresh(), null, 'ทดสอบคืนเงิน');
    }

    /** อนุมัติ + จ่ายคอมของออเดอร์ (เติมกองทุนให้พอ และบันทึกยอดที่ออเดอร์เก็บเข้ากอง) */
    protected function approveAndPay(Order $order): void
    {
        $rows = MlmCommission::where('source_type', Order::class)->where('source_id', $order->id)->get();
        $total = (float) $rows->sum('commission_amount');
        $order->forceFill(['mlm_commission_total' => $total])->saveQuietly();
        PlatformWallet::getMlmPoolWallet()->addFunds($total, 'test_funding');

        $calc = app(MlmCalculationService::class);
        $calc->approvePendingCommissions($rows->pluck('id')->all());
        $calc->payApprovedCommissions($rows->pluck('id')->all());
    }

    protected function pairRows(Order $order)
    {
        return MlmCommission::where('source_id', $order->id)->where('type', 'binary_pair')->get();
    }

    protected function legs(MlmMember $m): array
    {
        $f = $m->fresh();

        return [
            'left' => round((float) $f->left_leg_pv + (float) $f->carried_left_pv, 2),
            'right' => round((float) $f->right_leg_pv + (float) $f->carried_right_pv, 2),
        ];
    }

    protected function wallet(MlmMember $m): float
    {
        return round((float) Wallet::where('user_id', $m->user_id)->value('balance'), 2);
    }

    /**
     * F11 ฉาก 1: ซ้ายซื้อก่อน ขวามาจับคู่ (คู่บันทึกเป็นของออเดอร์ขวา) แล้วคืนเงินฝั่งซ้าย
     * → คู่ที่จ่ายแล้วต้องถูกดึงคืน เงินกลับกอง และ PV ขวากลับมาเป็นยังไม่จับคู่
     */
    public function test_refunding_left_order_claws_back_paid_pair_recorded_on_right_order(): void
    {
        $oL = $this->buy($this->l, 200);
        $oR = $this->buy($this->r, 400); // budget 200 → 2 คู่ × 100 · ขวาเหลือ 200 ถูก flush

        $pair = $this->pairRows($oR)->sole();
        $this->assertEqualsWithDelta(200.0, (float) $pair->commission_amount, 0.001);
        $this->approveAndPay($oR);
        $this->assertSame('paid', $pair->fresh()->status);
        $this->assertEqualsWithDelta(200.0, $this->wallet($this->s), 0.001);
        $poolBefore = (float) PlatformWallet::getMlmPoolWallet()->fresh()->balance;

        $this->refund($oL);

        $this->assertSame('clawback', $pair->fresh()->status, 'คู่ที่ใช้ PV ของออเดอร์ที่คืนเงินต้องถูกดึงคืน');
        $this->assertEqualsWithDelta(0.0, $this->wallet($this->s), 0.001);
        $this->assertEqualsWithDelta($poolBefore + 200, (float) PlatformWallet::getMlmPoolWallet()->fresh()->balance, 0.001, 'เงินคู่ที่ดึงคืนต้องกลับกองทุน');
        $this->assertSame(['left' => 0.0, 'right' => 200.0], $this->legs($this->s), 'PV ขวาที่คู่ใช้ไปกลับมาเป็นยังไม่จับคู่ ขาซ้ายไม่เหลือ PV ของออเดอร์ที่คืนเงิน');
        $this->assertSame(0, WalletDebt::count());

        // ผู้ซื้อ: total_pv กลับเป็น 0 + มีแถว refund ติดลบ
        $this->assertEqualsWithDelta(0.0, (float) $this->l->fresh()->total_pv, 0.001);
        $refundRow = MlmPvTransaction::where('order_id', $oL->id)->where('transaction_type', 'refund')->sole();
        $this->assertEqualsWithDelta(-200.0, (float) $refundRow->pv_amount, 0.001);
    }

    /**
     * F11 / M6: คืนเงินออเดอร์ที่ให้ PV แค่ครึ่งของคู่ → ดึงเงินคู่คืนตามสัดส่วน (ยังไม่จ่าย = ลดยอด)
     */
    public function test_pair_is_clawed_back_proportionally_to_the_refunded_pv(): void
    {
        $oA = $this->buy($this->l, 100);
        $oB = $this->buy($this->l, 100);
        $oR = $this->buy($this->r, 400); // ซ้าย 200 ขวา 400 → 2 คู่ = 200 บาท (pending)
        $pair = $this->pairRows($oR)->sole();

        $this->refund($oA);
        $pair->refresh();
        $this->assertSame('pending', $pair->status);
        $this->assertEqualsWithDelta(100.0, (float) $pair->commission_amount, 0.001, 'PV 100 จาก 200 ที่คู่ใช้ = ดึงคืนครึ่งหนึ่ง');
        $this->assertSame(['left' => 0.0, 'right' => 100.0], $this->legs($this->s));

        $this->refund($oB);
        $this->assertSame('cancelled', $pair->fresh()->status, 'PV ฝั่งซ้ายทั้งหมดถูกคืนเงิน → คู่ถูกยกเลิกทั้งแถว');
        $this->assertSame(['left' => 0.0, 'right' => 200.0], $this->legs($this->s));
    }

    /**
     * F11 ฉาก 2 / RefundReverse S1d: คู่ของออเดอร์ที่คืนเงินเอง → PV อีกฝั่งกลับมา และ PV ที่คืนเงินแล้วจับคู่ไม่ได้อีก
     */
    public function test_refunded_order_pv_cannot_be_matched_by_a_later_sale(): void
    {
        $this->buy($this->r, 200);              // ขวา 200 รอคู่
        $oL = $this->buy($this->l, 200);        // budget 100 → จ่าย 1 คู่ เก็บอีก 1 คู่ไว้
        $own = $this->pairRows($oL)->sole();
        $this->assertSame(1, (int) $own->pairs_count);

        $this->refund($oL);
        $this->assertSame('cancelled', $own->fresh()->status);
        $this->assertSame(['left' => 0.0, 'right' => 200.0], $this->legs($this->s), 'PV ขวาที่คู่ของออเดอร์นี้ใช้ต้องกลับมาครบ');

        $later = $this->buy($this->r, 100);
        $this->assertCount(0, $this->pairRows($later), 'ขาซ้ายไม่มี PV จริงเหลือ — ต้องไม่มีคู่');
        $this->assertSame(['left' => 0.0, 'right' => 300.0], $this->legs($this->s));
    }

    /**
     * F11 ฉาก 3 / M6: ผู้ซื้อที่คืนเงินแล้วต้องไม่ถูกนับเป็น active จาก PV ของออเดอร์นั้น
     */
    public function test_refunded_purchase_no_longer_counts_for_retention(): void
    {
        $x = $this->member('X', [], active: false);
        $order = $this->buy($x, 200);
        $this->assertTrue(MlmRetentionHelper::isMemberActive($x->fresh()));

        $this->refund($order);

        $x->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $x->total_pv, 0.001);
        $this->assertNull($x->last_purchase_at, 'วันซื้อล่าสุดต้องย้อนกลับ (ไม่มีการซื้อจริงที่เหลือ)');
        $this->assertFalse(MlmRetentionHelper::isMemberActive($x), 'PV ของออเดอร์ที่คืนเงินต้องไม่นับรักษายอด');
    }

    /**
     * คืนเงินซ้ำ / เรียก clawback ตรงซ้ำ → PV และคู่ถูกย้อนครั้งเดียว
     */
    public function test_repeated_refund_and_clawback_do_not_reverse_twice(): void
    {
        $oL = $this->buy($this->l, 200);
        $oR = $this->buy($this->r, 400);
        $this->approveAndPay($oR);
        $this->refund($oL);
        $snap = [$this->legs($this->s), $this->wallet($this->s), (float) PlatformWallet::getMlmPoolWallet()->fresh()->balance, (float) $this->l->fresh()->total_pv];

        $second = $this->refund($oL);
        app(MlmCommissionClawbackService::class)->clawbackOrderCommissions($oL->fresh());
        app(MlmCommissionClawbackService::class)->clawbackOrderCommissions($oL->fresh());

        $this->assertTrue($second['already_refunded'] ?? false);
        $this->assertSame(1, MlmPvTransaction::where('order_id', $oL->id)->where('transaction_type', 'refund')->count());
        $this->assertSame($snap, [$this->legs($this->s), $this->wallet($this->s), (float) PlatformWallet::getMlmPoolWallet()->fresh()->balance, (float) $this->l->fresh()->total_pv]);
    }

    /**
     * F13: clawback ล็อกออเดอร์ก่อนแล้วล็อกแถวคอม (ลำดับเดียวกับฝั่งจ่าย) — กันคอมถูกยกเลิกขณะที่เงินเข้ากระเป๋าไปแล้ว
     */
    public function test_clawback_locks_order_then_commission_rows(): void
    {
        $this->buy($this->l, 200);
        $oR = $this->buy($this->r, 400);

        $locks = [];
        DB::listen(function ($q) use (&$locks) {
            if (stripos($q->sql, 'for update') !== false) {
                if (preg_match('/from [`"]?(\w+)[`"]?/i', $q->sql, $m)) {
                    $locks[] = $m[1];
                }
            }
        });

        app(MlmCommissionClawbackService::class)->clawbackOrderCommissions($oR->fresh());

        $orderLock = array_search('orders', $locks, true);
        $commLock = array_search('mlm_commissions', $locks, true);
        $this->assertNotFalse($orderLock, 'ต้องล็อกแถวออเดอร์');
        $this->assertNotFalse($commLock, 'ต้องล็อกแถวคอมก่อนเปลี่ยนสถานะ');
        $this->assertLessThan($commLock, $orderLock, 'ล็อกออเดอร์ก่อนแถวคอม (กัน deadlock กับฝั่งจ่าย)');
    }

    /**
     * F14 / M7: ดึงคู่คืนตอนผู้รับถอนเงินไปแล้ว → หนี้ · กระเป๋าไม่ติดลบ · ถอนไม่ได้ระหว่างมีหนี้ · หักจากคอมครั้งถัดไป 100%
     */
    public function test_clawback_debt_blocks_withdrawal_and_is_collected_from_next_commission(): void
    {
        $oL = $this->buy($this->l, 200);
        $oR = $this->buy($this->r, 400);
        $this->approveAndPay($oR);
        $sUser = User::find($this->s->user_id);
        $sUser->forceFill(['kyc_status' => 'approved'])->save();
        $wallets = app(WalletService::class);
        $wallets->deductForService($wallets->getOrCreateWallet($sUser), 200, 'ทดสอบ: ถอนออกไปแล้ว', 'TestWithdrawal', 1);

        $this->refund($oL);

        $this->assertEqualsWithDelta(0.0, $this->wallet($this->s), 0.001, 'กระเป๋าต้องไม่ติดลบ');
        $debt = WalletDebt::where('user_id', $sUser->id)->where('source_type', 'MlmClawback')->sole();
        $this->assertEqualsWithDelta(200.0, (float) $debt->remaining_amount, 0.001);

        // มีหนี้ → ถอนไม่ได้ (แม้มีเงินจากทางอื่น)
        $wallets->deposit($wallets->getOrCreateWallet($sUser), 50, 'ทดสอบ: เติมเงิน', 'TestTopup', 1, [], 'deposit');
        $method = $sUser->paymentMethods()->create([
            'type' => 'bank_transfer', 'name' => 'บัญชีหลัก', 'account_name' => 'S',
            'account_number' => '1234567890', 'bank_name' => 'กสิกรไทย', 'is_active' => true,
        ]);
        try {
            app(\App\Services\WithdrawalService::class)->createWithdrawalRequest($sUser->fresh(), 50, $method->id);
            $this->fail('มีหนี้ค้างต้องถอนไม่ได้');
        } catch (\Exception $e) {
            $this->assertStringContainsString('ค้างชำระ', $e->getMessage());
        }

        // คอมครั้งถัดไป 150 → หักหนี้ทั้งก้อน 150 (100%) เงินเข้ากองทุนคืนเงิน
        $refundPoolBefore = (float) PlatformWallet::getRefundPoolWallet()->fresh()->balance;
        $this->payStandaloneCommission(150);
        $this->assertEqualsWithDelta(50.0, (float) $debt->fresh()->remaining_amount, 0.001);
        $this->assertEqualsWithDelta(50.0, $this->wallet($this->s), 0.001, 'เงินเติมเดิม 50 + คอม 150 − หนี้ 150');
        $this->assertEqualsWithDelta($refundPoolBefore + 150, (float) PlatformWallet::getRefundPoolWallet()->fresh()->balance, 0.001);

        // คอมถัดไป 80 → หักหนี้ที่เหลือ 50 หนี้หมด ถอนได้อีกครั้ง
        $this->payStandaloneCommission(80);
        $this->assertSame(WalletDebt::STATUS_PAID, $debt->fresh()->status);
        $this->assertEqualsWithDelta(80.0, $this->wallet($this->s), 0.001);
        $this->assertNotNull(app(\App\Services\WithdrawalService::class)->createWithdrawalRequest($sUser->fresh(), 50, $method->id));
    }

    /**
     * F14 / M7: หนี้เกิดระหว่างคำขอถอนรออนุมัติ → แอดมินอนุมัติไม่ได้
     */
    public function test_pending_withdrawal_cannot_be_approved_after_debt_appears(): void
    {
        $user = User::find($this->s->user_id);
        $user->forceFill(['kyc_status' => 'approved'])->save();
        Wallet::where('user_id', $user->id)->update(['balance' => 500]);
        $method = $user->paymentMethods()->create([
            'type' => 'bank_transfer', 'name' => 'บัญชีหลัก', 'account_name' => 'S',
            'account_number' => '1234567890', 'bank_name' => 'กสิกรไทย', 'is_active' => true,
        ]);
        $service = app(\App\Services\WithdrawalService::class);
        $request = $service->createWithdrawalRequest($user->fresh(), 200, $method->id);
        WalletDebt::createDebt($user->id, 120, 'MlmClawback', 1, 'ทดสอบหนี้ดึงคืนคอม');

        try {
            $service->approveWithdrawal($request->fresh(), null);
            $this->fail('มีหนี้ต้องอนุมัติไม่ได้');
        } catch (\Exception $e) {
            $this->assertStringContainsString('ค้างชำระ', $e->getMessage());
        }
        $this->assertSame('pending', $request->fresh()->status);
    }

    /**
     * BLOCKER (review money #1): คู่ที่ถูกดึงคืนบางส่วนตอนยังไม่จ่าย (ยอดถูกลด) แล้วจ่าย แล้วออเดอร์ของคู่เองถูกคืนเงิน
     * → ต้องดึงคืนยอดที่จ่ายจริง (100) ไม่ใช่ 100 − 100 = 0
     */
    public function test_partially_clawed_pending_pair_that_is_paid_is_fully_recovered_when_its_own_order_is_refunded(): void
    {
        $oL1 = $this->buy($this->l, 100);
        $this->buy($this->l, 100);
        $oR = $this->buy($this->r, 400); // ซ้าย 200 ขวา 400 → คู่ 200 (pending)
        $pair = $this->pairRows($oR)->sole();

        $this->refund($oL1);
        $this->assertEqualsWithDelta(100.0, (float) $pair->fresh()->commission_amount, 0.001);

        $this->approveAndPay($oR);
        $this->assertSame('paid', $pair->fresh()->status);
        $this->assertEqualsWithDelta(100.0, $this->wallet($this->s), 0.001);

        $this->refund($oR);

        $this->assertSame('clawback', $pair->fresh()->status);
        $this->assertEqualsWithDelta(0.0, $this->wallet($this->s), 0.001, 'ต้องดึงคืน 100 ที่จ่ายไปจริง');
        $this->assertSame(0, WalletDebt::count(), 'กระเป๋ามีพอ ไม่ต้องเป็นหนี้');
    }

    /**
     * BLOCKER (review money #2): คู่ที่จ่ายแล้วถูกดึงคืนบางส่วนจากการคืนเงิน แล้วแอดมินดึงคืนแถวนั้นแบบแมนนวล
     * → ดึงคืนเฉพาะที่เหลือ (รวมทั้งหมดไม่เกินที่จ่ายจริง 200) หักกระเป๋าก่อน ไม่สร้างหนี้เต็มก้อน
     */
    public function test_manual_clawback_of_partially_clawed_paid_pair_takes_only_the_remainder(): void
    {
        $oL1 = $this->buy($this->l, 100);
        $this->buy($this->l, 100);
        $oR = $this->buy($this->r, 400);
        $pair = $this->pairRows($oR)->sole();
        $this->approveAndPay($oR);
        $this->assertEqualsWithDelta(200.0, $this->wallet($this->s), 0.001);
        $poolBefore = (float) PlatformWallet::getMlmPoolWallet()->fresh()->balance;

        $this->refund($oL1); // ดึงคืน 100 จากกระเป๋า
        $this->assertEqualsWithDelta(100.0, $this->wallet($this->s), 0.001);

        $result = app(MlmCommissionClawbackService::class)->clawbackSingleCommission($pair->fresh(), null, 'ทดสอบดึงคืนแมนนวล');

        $this->assertEqualsWithDelta(100.0, (float) $result['amount'], 0.001);
        $this->assertEqualsWithDelta(0.0, $this->wallet($this->s), 0.001);
        $this->assertSame(0, WalletDebt::count(), 'รวมดึงคืน 200 = ที่จ่ายจริง ห้ามเกิดหนี้เพิ่ม');
        $this->assertSame('clawback', $pair->fresh()->status);
        $this->assertEqualsWithDelta($poolBefore + 200, (float) PlatformWallet::getMlmPoolWallet()->fresh()->balance, 0.001, 'เงินที่ดึงคืนทั้งสองครั้งกลับกองทุน');

        // ดึงซ้ำ → แถวเป็น clawback แล้ว ไม่มีอะไรให้ดึง
        app(MlmCommissionClawbackService::class)->clawbackSingleCommission($pair->fresh(), null, 'ซ้ำ');
        $this->assertSame(0, WalletDebt::count());
    }

    /**
     * (review tests a) แถว PV เก่าที่ไม่มี binary_leg_credits (สร้างก่อน deploy) → คืนเงินต้องไม่หัก PV ขาของใคร
     */
    public function test_refunding_legacy_order_without_leg_credits_does_not_touch_anyones_legs(): void
    {
        // ออเดอร์เก่าสมัย binary ปิด: มีแถว PV แต่ไม่เคยลงขา
        $old = Order::create([
            'user_id' => $this->l->user_id, 'status' => 'paid', 'payment_method' => 'promptpay',
            'payment_status' => 'paid', 'paid_at' => now()->subMonth(), 'subtotal' => 300, 'shipping_fee' => 0, 'total_amount' => 300,
        ]);
        (new MlmPvService)->recordPvTransaction($this->l->fresh(), $old, ['total_pv' => 300]);
        MlmPvTransaction::where('order_id', $old->id)->update(['binary_leg_credits' => null]);
        $this->l->increment('total_pv', 300);

        $this->buy($this->l, 200); // ออเดอร์ใหม่ลงขาซ้าย S 200
        $this->assertSame(['left' => 200.0, 'right' => 0.0], $this->legs($this->s));

        $this->refund($old);

        $this->assertSame(['left' => 200.0, 'right' => 0.0], $this->legs($this->s), 'PV ขาจากออเดอร์อื่นต้องไม่ถูกหัก');
        $this->assertEqualsWithDelta(200.0, (float) $this->l->fresh()->total_pv, 0.001, 'total_pv ผู้ซื้อยังถูกหักตามปกติ');
    }

    /**
     * (review tests b) upline ที่ไม่ active ตอนซื้อไม่ได้รับ PV → คืนเงินต้องไม่หัก PV ของเขา
     */
    public function test_refund_does_not_take_pv_from_upline_who_was_inactive_at_purchase(): void
    {
        $g = $this->member('G', [], active: false);
        MlmMember::whereKey($this->s->id)->update(['binary_parent_id' => $g->id, 'binary_position' => 'left']);
        Cache::flush();

        $o1 = $this->buy($this->l, 200); // G ไม่ active → ไม่ได้ PV
        $this->assertSame(['left' => 0.0, 'right' => 0.0], $this->legs($g));

        MlmMember::whereKey($g->id)->update(['last_purchase_at' => now()]);
        Cache::flush();
        $this->buy($this->l, 100); // G active แล้ว ได้ 100
        $this->assertSame(['left' => 100.0, 'right' => 0.0], $this->legs($g));

        $this->refund($o1);

        $this->assertSame(['left' => 100.0, 'right' => 0.0], $this->legs($g), 'G ไม่เคยได้ PV ของออเดอร์ที่คืนเงิน — ต้องไม่ถูกหัก');
        $this->assertSame(['left' => 100.0, 'right' => 0.0], $this->legs($this->s));
    }

    /**
     * (review tests c) คู่ที่จ่ายแล้วถูกดึงคืนบางส่วนจากการคืนเงินออเดอร์อื่น แล้วออเดอร์ของคู่เองถูกคืนเงิน → รวมดึงคืนเท่าที่จ่าย
     */
    public function test_paid_pair_partially_clawed_then_own_order_refunded_recovers_exactly_what_was_paid(): void
    {
        $oL1 = $this->buy($this->l, 100);
        $this->buy($this->l, 100);
        $oR = $this->buy($this->r, 400);
        $pair = $this->pairRows($oR)->sole();
        $this->approveAndPay($oR);
        $poolBefore = (float) PlatformWallet::getMlmPoolWallet()->fresh()->balance;

        $this->refund($oL1);
        $this->refund($oR);

        $this->assertSame('clawback', $pair->fresh()->status);
        $this->assertEqualsWithDelta(0.0, $this->wallet($this->s), 0.001);
        $this->assertSame(0, WalletDebt::count(), 'รวมดึงคืน 200 ไม่ใช่ 300');
        $this->assertEqualsWithDelta($poolBefore + 200, (float) PlatformWallet::getMlmPoolWallet()->fresh()->balance, 0.001);
    }

    /**
     * (review tests d) เปิดยกยอด: คืนเงินหัก PV จาก carried ก่อน แล้วดึงคู่ที่ใช้ส่วนที่เหลือ · carried หมด = ล้างวันหมดอายุ
     */
    public function test_refund_takes_pv_from_carried_when_carry_forward_is_on(): void
    {
        foreach ([
            'binary_carry_forward_enabled' => ['1', 'boolean'],
            'binary_carry_forward_days' => ['30', 'integer'],
            'binary_pair_commission' => ['10.00', 'decimal'],
        ] as $key => [$value, $type]) {
            DB::table('mlm_global_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'type' => $type]);
        }
        Cache::flush();

        $oL = $this->buy($this->l, 300);
        $oR = $this->buy($this->r, 100); // 1 คู่ (10) เศษซ้าย 200 ยกยอด
        $pair = $this->pairRows($oR)->sole();
        $this->assertEqualsWithDelta(200.0, (float) $this->s->fresh()->carried_left_pv, 0.001);

        $this->refund($oL);

        $s = $this->s->fresh();
        $this->assertEqualsWithDelta(0.0, (float) $s->carried_left_pv, 0.001);
        $this->assertNull($s->carried_left_pv_expires_at, 'carried หมดแล้ว ต้องล้างวันหมดอายุ');
        $this->assertSame('cancelled', $pair->fresh()->status, 'อีก 100 PV ถูกจับคู่ไปแล้ว → ยกเลิกคู่');
        $this->assertSame(['left' => 0.0, 'right' => 100.0], $this->legs($this->s), 'PV ขวาของคู่ที่ยกเลิกกลับมา');
    }

    /**
     * (review tests deadlock) คืนเงินที่ดึงคู่ของออเดอร์อื่น → ล็อกออเดอร์ต้นทางของคู่ + แถวคู่ ก่อนล็อกสมาชิก
     * (ฝั่งจ่ายล็อก ออเดอร์ → แถวคู่ → ... → สมาชิก) กันวนรอกันจน deadlock
     */
    public function test_cross_order_pair_clawback_locks_pair_rows_before_members(): void
    {
        $oL = $this->buy($this->l, 200);
        $oR = $this->buy($this->r, 400);
        $pair = $this->pairRows($oR)->sole();

        $locks = [];
        DB::listen(function ($q) use (&$locks) {
            if (stripos($q->sql, 'for update') !== false && preg_match('/from [`"]?(\w+)[`"]?/i', $q->sql, $m)) {
                $locks[] = [$m[1], array_map('intval', array_values(array_filter($q->bindings, 'is_numeric')))];
            }
        });

        app(MlmCommissionClawbackService::class)->clawbackOrderCommissions($oL->fresh());

        $firstMember = null;
        $otherOrder = null;
        $pairLock = null;
        foreach ($locks as $i => [$table, $bindings]) {
            if ($table === 'mlm_members' && $firstMember === null) {
                $firstMember = $i;
            }
            if ($table === 'orders' && $otherOrder === null && in_array((int) $oR->id, $bindings, true)) {
                $otherOrder = $i;
            }
            if ($table === 'mlm_commissions' && $pairLock === null && $otherOrder !== null) {
                $pairLock = $i;
            }
        }
        $this->assertNotNull($otherOrder, 'ต้องล็อกออเดอร์ต้นทางของคู่ (ออเดอร์ขวา)');
        $this->assertNotNull($pairLock, 'ต้องล็อกแถวคู่หลังออเดอร์ต้นทาง');
        $this->assertNotNull($firstMember);
        $this->assertLessThan($firstMember, $otherOrder);
        $this->assertLessThan($firstMember, $pairLock);
        $this->assertSame('cancelled', $pair->fresh()->status);
    }

    /**
     * F14 / M7: ระหว่างมีหนี้ห้ามโอน P2P ออก (กันเลี่ยงด่านห้ามถอนด้วยการโอนให้บัญชีอื่นไปถอน)
     */
    public function test_transfer_is_blocked_while_debt_is_active(): void
    {
        $from = User::find($this->s->user_id);
        $to = User::find($this->r->user_id);
        $wallets = app(WalletService::class);
        Wallet::where('user_id', $from->id)->update(['balance' => 500]);
        WalletDebt::createDebt($from->id, 200, 'MlmClawback', 1, 'ทดสอบหนี้ดึงคืนคอม');

        foreach (['transfer', 'transferWithFee'] as $method) {
            try {
                $wallets->{$method}($wallets->getOrCreateWallet($from), $wallets->getOrCreateWallet($to), 100, '000000', 'ทดสอบโอน');
                $this->fail("{$method}: มีหนี้ต้องโอนออกไม่ได้");
            } catch (\Exception $e) {
                $this->assertStringContainsString('ค้างชำระ', $e->getMessage(), $method);
            }
        }
        $this->assertEqualsWithDelta(500.0, $this->wallet($this->s), 0.001);
        $this->assertEqualsWithDelta(0.0, $this->wallet($this->r), 0.001);
    }

    /**
     * หนี้ตลาดสด (ค่า GP/ต้นทุนไรเดอร์) ระบบต้นทางเก็บเอง → ไม่บล็อกการถอน และไม่ถูกหักจากคอม MLM เข้ากองทุนคืนเงิน
     */
    public function test_fresh_market_seller_debt_does_not_block_withdrawal_or_get_collected_from_mlm_income(): void
    {
        $user = User::find($this->s->user_id);
        $user->forceFill(['kyc_status' => 'approved'])->save();
        Wallet::where('user_id', $user->id)->update(['balance' => 500]);
        $debt = WalletDebt::createDebt($user->id, 120, \App\Services\FreshMarketService::DEBT_SOURCE_GP, 1, 'ทดสอบค่า GP ตลาดสด');

        $this->payStandaloneCommission(100);
        $this->assertEqualsWithDelta(120.0, (float) $debt->fresh()->remaining_amount, 0.001);
        $this->assertEqualsWithDelta(600.0, $this->wallet($this->s), 0.001);

        $method = $user->paymentMethods()->create([
            'type' => 'bank_transfer', 'name' => 'บัญชีหลัก', 'account_name' => 'S',
            'account_number' => '1234567890', 'bank_name' => 'กสิกรไทย', 'is_active' => true,
        ]);
        $this->assertNotNull(app(\App\Services\WithdrawalService::class)->createWithdrawalRequest($user->fresh(), 100, $method->id));
    }

    /** คอม MLM ที่อนุมัติแล้ว (ไม่ผูกออเดอร์) ให้ S แล้วจ่ายผ่านเส้นทางจริง */
    protected function payStandaloneCommission(float $amount): void
    {
        PlatformWallet::getMlmPoolWallet()->addFunds($amount, 'test_funding');
        $row = MlmCommission::create([
            'mlm_member_id' => $this->s->id,
            'mlm_plan_id' => $this->plan->id,
            'user_id' => $this->s->user_id,
            'type' => 'rank_bonus',
            'level' => 1,
            'pv_amount' => 0,
            'sales_amount' => 0,
            'commission_amount' => $amount,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->assertSame(1, app(MlmCalculationService::class)->payApprovedCommissions([$row->id]));
    }
}
