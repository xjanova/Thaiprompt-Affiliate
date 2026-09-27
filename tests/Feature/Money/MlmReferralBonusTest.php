<?php

namespace Tests\Feature\Money;

use App\Models\MlmCommission;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\Order;
use App\Models\User;
use App\Services\MlmReferralBonusService;
use Illuminate\Support\Facades\Cache;

/**
 * ค่าแนะนำตรง (Direct Referral Bonus) ต้องตรงกับที่แอปบอกผู้ใช้
 *
 * แอป (thaiprompt/app/referral.tsx) บอกว่า "รับค่าแนะนำเมื่อเพื่อนสั่งซื้อครั้งแรก"
 * - เพดานต่อออเดอร์ที่เป็นค่าว่าง (NULL ชนิด decimal แบบบน prod) = ไม่จำกัด ไม่ใช่ตัดเหลือ 0
 * - first_order_only: เพื่อนหนึ่งคนให้ค่าแนะนำครั้งเดียว · ถ้าออเดอร์แรกถูกคืนเงิน (ค่าแนะนำถูกยกเลิก) ออเดอร์ถัดไปได้สิทธิ์แทน
 */
class MlmReferralBonusTest extends MoneyTestCase
{
    private MlmPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->setting('genealogy_enabled', '1', 'boolean');
        $this->setting('direct_referral_bonus_enabled', '1', 'boolean');
        $this->setting('direct_referral_bonus_type', 'fixed', 'string');
        $this->setting('direct_referral_bonus_amount', '100.00', 'decimal');
        $this->setting('direct_referral_bonus_min_order', '0', 'decimal');
        // สภาพเดียวกับ prod: แถวชนิด decimal ที่ value เป็น NULL
        $this->setting('direct_referral_bonus_max_per_order', null, 'decimal');
        $this->setting('direct_referral_bonus_first_order_only', '1', 'boolean');
    }

    private function setting(string $key, ?string $value, string $type): void
    {
        MlmGlobalSetting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type]);
        Cache::forget("mlm_setting_{$key}");
    }

    private function member(User $user, ?MlmMember $sponsor = null): MlmMember
    {
        return MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'M'.uniqid(),
            'original_sponsor_id' => $sponsor?->id,
            'unilevel_sponsor_id' => $sponsor?->id,
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now()->subMonth(),
        ]);
    }

    /**
     * @return array{0: MlmMember, 1: User} [ผู้แนะนำ, เพื่อนที่สมัครด้วยรหัส]
     */
    private function referralPair(): array
    {
        $sponsor = $this->member($this->makeUser('ผู้แนะนำ'));
        $friend = $this->makeUser('เพื่อน');
        $this->member($friend, $sponsor);

        return [$sponsor, $friend];
    }

    private function paidOrder(User $buyer, float $total): Order
    {
        $product = $this->makeProduct($this->makeSeller(), $total);

        return $this->makePaidOrder($buyer, [[$product, 1]]);
    }

    public function test_empty_max_per_order_does_not_cap_bonus_to_zero(): void
    {
        [$sponsor, $friend] = $this->referralPair();

        $commission = (new MlmReferralBonusService)->calculateReferralBonus($this->paidOrder($friend, 250));

        $this->assertNotNull($commission, 'เพดานว่างต้องแปลว่าไม่จำกัด ไม่ใช่ตัดค่าแนะนำเหลือ 0');
        $this->assertSame($sponsor->id, (int) $commission->mlm_member_id);
        $this->assertSame('direct_referral', $commission->type);
        $this->assertSame('pending', $commission->status);
        $this->assertEqualsWithDelta(100.00, (float) $commission->commission_amount, 0.001);
    }

    public function test_positive_max_per_order_still_caps_bonus(): void
    {
        $this->setting('direct_referral_bonus_max_per_order', '40', 'decimal');
        [, $friend] = $this->referralPair();

        $commission = (new MlmReferralBonusService)->calculateReferralBonus($this->paidOrder($friend, 250));

        $this->assertEqualsWithDelta(40.00, (float) $commission->commission_amount, 0.001);
    }

    public function test_first_order_only_pays_once_per_friend(): void
    {
        [$sponsor, $friend] = $this->referralPair();
        $service = new MlmReferralBonusService;

        $first = $service->calculateReferralBonus($this->paidOrder($friend, 250));
        $second = $service->calculateReferralBonus($this->paidOrder($friend, 300));

        $this->assertNotNull($first);
        $this->assertNull($second, 'ออเดอร์ที่สองของเพื่อนคนเดิมต้องไม่ได้ค่าแนะนำ');
        $this->assertSame(1, MlmCommission::where('mlm_member_id', $sponsor->id)->where('type', 'direct_referral')->count());
    }

    public function test_same_order_is_never_paid_twice(): void
    {
        $this->setting('direct_referral_bonus_first_order_only', '0', 'boolean');
        [, $friend] = $this->referralPair();
        $order = $this->paidOrder($friend, 250);
        $service = new MlmReferralBonusService;

        $this->assertNotNull($service->calculateReferralBonus($order));
        $this->assertNull($service->calculateReferralBonus($order));
        $this->assertSame(1, MlmCommission::where('source_id', $order->id)->where('type', 'direct_referral')->count());
    }

    public function test_next_order_qualifies_when_first_bonus_was_cancelled_by_refund(): void
    {
        [$sponsor, $friend] = $this->referralPair();
        $service = new MlmReferralBonusService;

        $first = $service->calculateReferralBonus($this->paidOrder($friend, 250));
        // คืนเงินออเดอร์แรก → MlmCommissionClawbackService ยกเลิกค่าแนะนำที่ยังไม่จ่าย
        $first->update(['status' => 'cancelled']);

        $next = $service->calculateReferralBonus($this->paidOrder($friend, 300));

        $this->assertNotNull($next);
        $this->assertSame(1, MlmCommission::where('mlm_member_id', $sponsor->id)
            ->where('type', 'direct_referral')
            ->where('status', '!=', 'cancelled')
            ->count());
    }

    public function test_buyer_without_sponsor_gets_no_bonus(): void
    {
        $buyer = $this->makeUser('ไม่มีผู้แนะนำ');
        $this->member($buyer);

        $this->assertNull((new MlmReferralBonusService)->calculateReferralBonus($this->paidOrder($buyer, 250)));
        $this->assertSame(0, MlmCommission::where('type', 'direct_referral')->count());
    }
}
