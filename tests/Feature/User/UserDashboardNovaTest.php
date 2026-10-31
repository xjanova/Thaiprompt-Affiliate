<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * แดชบอร์ดสมาชิก (ธีมโนวา) — /user/dashboard
 *
 * กันบั๊กที่เคยเกิดจริง:
 *  - ยอดเงินขึ้น ฿0 ทุกคน (อ่าน users.wallet_balance ที่ไม่มีคอลัมน์ แทน wallets.balance)
 *  - หน้า 500 ทันทีที่มีคอมมิชชั่นสักรายการ (eager load relation mlmMember ที่ไม่มีใน MlmCommission)
 */
class UserDashboardNovaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * สมาชิกใหม่ ยังไม่มีกระเป๋า/ทีม/รายได้ — หน้าต้องเปิดได้และแสดงสถานะว่าง
     */
    public function test_new_member_sees_empty_states(): void
    {
        $user = User::factory()->create(['role' => 'user', 'name' => 'มาลี ทดสอบ', 'kyc_status' => 'not_submitted']);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('มาลี ทดสอบ')
            ->assertSee('ยืนยันตัวตนเพื่อปลดล็อกการถอนเงิน')
            ->assertSee('ยังไม่มีรายได้ในช่วงนี้')
            ->assertSee('ยังไม่มีความเคลื่อนไหว')
            ->assertSee('เข้าร่วมแผนสมาชิก');
    }

    /**
     * สมาชิกที่มีกระเป๋า + คอมมิชชั่น — ยอดเงินมาจาก wallets.balance และหน้าไม่ล่ม
     */
    public function test_member_with_commissions_sees_wallet_and_activity(): void
    {
        $user = User::factory()->create(['role' => 'user', 'kyc_status' => 'approved']);
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => 12450.75]);

        $planId = DB::table('mlm_plans')->insertGetId(['name' => 'Unilevel', 'slug' => 'unilevel-test', 'created_at' => now(), 'updated_at' => now()]);
        $memberId = DB::table('mlm_members')->insertGetId([
            'user_id' => $user->id, 'mlm_plan_id' => $planId, 'member_code' => 'TPTEST01',
            'status' => 'active', 'is_qualified' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('mlm_commissions')->insert([
            ['mlm_member_id' => $memberId, 'mlm_plan_id' => $planId, 'user_id' => $user->id, 'type' => 'direct_referral', 'commission_amount' => 2450, 'status' => 'paid', 'created_at' => now(), 'updated_at' => now()],
            ['mlm_member_id' => $memberId, 'mlm_plan_id' => $planId, 'user_id' => $user->id, 'type' => 'binary_pair', 'commission_amount' => 640, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('12,450')
            ->assertSee('คอมมิชชั่นจ่ายเข้ากระเป๋าแล้ว')
            ->assertSee('คอมมิชชั่นรอดำเนินการ')
            ->assertSee('ref=TPTEST01', false)
            ->assertDontSee('ยืนยันตัวตนเพื่อปลดล็อกการถอนเงิน');
    }

    /**
     * วันที่ 31: กราฟต้องมีครบ 12 เดือนไม่ซ้ำ และ "เดือนก่อน" ต้องเป็น ก.ย. จริง (ไม่ล้นกลับมา ต.ค.)
     */
    public function test_month_math_does_not_overflow_on_the_31st(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31)->setTime(12, 0));

        $user = User::factory()->create(['role' => 'user']);
        $planId = DB::table('mlm_plans')->insertGetId(['name' => 'Unilevel', 'slug' => 'unilevel-test', 'created_at' => now(), 'updated_at' => now()]);
        $memberId = DB::table('mlm_members')->insertGetId([
            'user_id' => $user->id, 'mlm_plan_id' => $planId, 'member_code' => 'TPTEST02', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([['2026-09-15', 1000], ['2026-10-05', 1500]] as [$date, $amount]) {
            DB::table('mlm_commissions')->insert([
                'mlm_member_id' => $memberId, 'mlm_plan_id' => $planId, 'user_id' => $user->id, 'type' => 'direct_referral',
                'commission_amount' => $amount, 'status' => 'paid', 'created_at' => $date.' 10:00:00', 'updated_at' => $date.' 10:00:00',
            ]);
        }

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertViewHas('commissionGrowth', fn ($g) => abs($g - 50.0) < 0.001)
            ->assertViewHas('chartData', function ($d) {
                return count(array_unique($d['labels'])) === 12
                    && $d['values'][10] === 1000.0  // ก.ย.
                    && $d['values'][11] === 1500.0; // ต.ค.
            });
    }
}
