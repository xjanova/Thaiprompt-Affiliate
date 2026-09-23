<?php

namespace Tests\Feature;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\JuntraAccount;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsJuntraAffiliateWorld;
use Tests\TestCase;

/**
 * 🌙 /api/v1/juntra/server/affiliate/* — บิลเว็บ/แอพจันทรา → ผังแม่หมอคำนวณปันผล
 *
 * เจ้าของสั่ง (2026-09-21): จันทราไม่คำนวณค่าแนะนำเอง ทุกบิลส่งมาที่ผังแม่หมอ
 *   อัตราบิลจันทรา = % ของยอดบิล (เจ้าของเลือก — ไพ่ 9฿ ห้ามจ่ายค่าแนะนำเกินราคา)
 *   ลูกค้าที่ไม่เคยผูก Thaiprompt ก็ต้องมีสายงาน (สร้างสมาชิกให้อัตโนมัติ)
 *
 * @group fortune-commission
 */
class JuntraServerAffiliateApiTest extends TestCase
{
    use BuildsJuntraAffiliateWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraAffiliateWorld();
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // สมาชิก / สายงาน
    // ─────────────────────────────────────────────────────────────────────

    public function test_customer_without_thaiprompt_joins_under_the_inviter(): void
    {
        [, $inviter] = $this->activeMember('INVITE01');

        $res = $this->ensure(501, 'ลูกค้าเบอร์โทร', referral: 'INVITE01');

        $res->assertStatus(201)
            ->assertJsonPath('data.enrolled_now', true)
            ->assertJsonPath('data.linked_via', 'auto')
            ->assertJsonPath('data.referral.applied', true)
            ->assertJsonPath('data.sponsor.member_code', 'INVITE01');

        $account = JuntraAccount::where('juntra_user_id', 501)->firstOrFail();
        // อีเมลเงาสุ่มท้าย — เดาไม่ได้ จองล่วงหน้าไม่ได้
        $this->assertMatchesRegularExpression('/^juntra_501_[a-z0-9]{16}@thaiprompt\.local$/', $account->user->email);
        $this->assertSame($inviter->id, MlmMember::where('user_id', $account->user_id)->value('unilevel_sponsor_id'));
    }

    /** ใครจองอีเมลที่เดาได้ไว้ก่อน ต้องยึดตัวตน (และค่าแนะนำทั้งสาย) ของลูกค้าจันทราไม่ได้ */
    public function test_a_pre_registered_lookalike_email_cannot_capture_the_customer(): void
    {
        $attacker = User::factory()->create(['email' => 'juntra_506@thaiprompt.local']);
        $this->member($attacker, $this->rootMember, 'ATTACK01');

        $this->ensure(506, 'ลูกค้าจริง')->assertStatus(201);

        $this->assertNotSame($attacker->id, JuntraAccount::where('juntra_user_id', 506)->value('user_id'));
        $this->assertNotSame('ATTACK01', $this->ensure(506, 'ลูกค้าจริง')->json('data.member_code'));
    }

    public function test_second_call_never_moves_the_customer_to_another_line(): void
    {
        $this->activeMember('INVITE01');
        $this->activeMember('INVITE02');

        $code = $this->ensure(502, 'ลูกค้า', referral: 'INVITE01')->json('data.member_code');

        $res = $this->ensure(502, 'ลูกค้า', referral: 'INVITE02');

        $res->assertOk()
            ->assertJsonPath('data.enrolled_now', false)
            ->assertJsonPath('data.member_code', $code)
            ->assertJsonPath('data.sponsor.member_code', 'INVITE01')
            ->assertJsonPath('data.referral.reason_code', 'already_enrolled');
        $this->assertSame(1, JuntraAccount::count());
    }

    public function test_no_or_bad_code_lands_under_the_default_sponsor(): void
    {
        $this->ensure(503, 'ไม่มีคนชวน')->assertStatus(201)
            ->assertJsonPath('data.sponsor.member_code', 'ROOT0001')
            ->assertJsonPath('data.referral.reason_code', null);

        $this->ensure(504, 'รหัสผิด', referral: 'NOPE9999')->assertStatus(201)
            ->assertJsonPath('data.sponsor.member_code', 'ROOT0001')
            ->assertJsonPath('data.referral.applied', false)
            ->assertJsonPath('data.referral.reason_code', 'invalid_code');
    }

    public function test_linked_customer_uses_their_own_thaiprompt_account(): void
    {
        [$tpUser, $tpMember] = $this->activeMember('TPUSER01');

        $this->ensure(505, 'ผูกแล้ว', thaipromptUserId: $tpUser->id, referral: 'ROOT0001')
            ->assertOk()
            ->assertJsonPath('data.linked_via', 'sso')
            ->assertJsonPath('data.member_code', 'TPUSER01')
            ->assertJsonPath('data.referral.reason_code', 'already_enrolled');

        $this->assertSame($tpUser->id, JuntraAccount::where('juntra_user_id', 505)->value('user_id'));
        $this->assertSame(1, MlmMember::where('user_id', $tpUser->id)->count());
        $this->assertSame($tpMember->id, MlmMember::where('user_id', $tpUser->id)->value('id'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // รวมบัญชี — ลูกค้าซื้อก่อนผูก Thaiprompt แล้วมาผูกทีหลัง (เจ้าของสั่ง: Thaiprompt เป็นตัวหลัก)
    // ─────────────────────────────────────────────────────────────────────

    public function test_linking_thaiprompt_merges_the_shadow_into_an_account_without_a_position(): void
    {
        $this->activeMember('INVITE01', $this->rootMember);
        $shadowCode = $this->bill(9020, 620, 99, referral: 'INVITE01')->json('data.member_code');
        $shadowUserId = JuntraAccount::where('juntra_user_id', 620)->value('user_id');
        $shadowMemberId = MlmMember::where('user_id', $shadowUserId)->value('id');
        // เงาชวนเพื่อนมาซื้อ → เงาได้ค่าแนะนำเข้ากระเป๋า
        $this->bill(9021, 621, 99, referral: $shadowCode)->assertStatus(201);
        $this->assertEquals(9.90, (float) Wallet::where('user_id', $shadowUserId)->value('balance'));

        $real = User::factory()->create();

        $this->ensure(620, 'ลูกค้าผูกแล้ว', thaipromptUserId: $real->id)
            ->assertOk()
            ->assertJsonPath('data.linked_via', 'sso')
            ->assertJsonPath('data.member_code', $shadowCode);

        $account = JuntraAccount::where('juntra_user_id', 620)->firstOrFail();
        $this->assertSame($real->id, $account->user_id);
        $this->assertSame($shadowUserId, $account->merged_from_user_id);
        $this->assertSame($shadowMemberId, MlmMember::where('user_id', $real->id)->value('id'), 'รับตำแหน่งเดิมของเงาไปทั้งตำแหน่ง');
        $this->assertSame($real->id, FortuneReading::where('bill_reference', 'JW-9020')->value('user_id'));
        $this->assertSame(0, FortuneCommission::where('user_id', $shadowUserId)->count());
        $this->assertEquals(9.90, (float) Wallet::where('user_id', $real->id)->value('balance'));
        $this->assertEquals(0.0, (float) Wallet::where('user_id', $shadowUserId)->value('balance'));
    }

    public function test_merge_keeps_the_thaiprompt_position_and_moves_the_shadow_team(): void
    {
        [$real, $realMember] = $this->activeMember('REAL0001', $this->rootMember);
        $this->activeMember('INVITE01', $this->rootMember);
        $shadowCode = $this->bill(9022, 622, 99, referral: 'INVITE01')->json('data.member_code');
        $shadowUserId = JuntraAccount::where('juntra_user_id', 622)->value('user_id');
        $shadowMember = MlmMember::where('user_id', $shadowUserId)->firstOrFail();
        $this->bill(9023, 623, 99, referral: $shadowCode)->assertStatus(201);
        $friendMember = MlmMember::where('user_id', JuntraAccount::where('juntra_user_id', 623)->value('user_id'))->firstOrFail();

        $this->ensure(622, 'ลูกค้าผูกแล้ว', thaipromptUserId: $real->id)
            ->assertOk()
            ->assertJsonPath('data.member_code', 'REAL0001');

        $this->assertSame($realMember->id, $friendMember->fresh()->unilevel_sponsor_id, 'ลูกทีมของเงาย้ายมาอยู่ใต้บัญชีจริง');
        $this->assertSame('inactive', $shadowMember->fresh()->status);
        $this->assertSame(1, FortuneCommission::where('user_id', $real->id)->where('mlm_member_id', $realMember->id)->count());
        $this->assertEquals(9.90, (float) Wallet::where('user_id', $real->id)->value('balance'));

        // บิลถัดไปของเพื่อนจ่ายให้บัญชีจริง
        $this->bill(9024, 623, 39)->assertStatus(201);
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $this->readingId(9024),
            'user_id' => $real->id,
            'level' => 1,
        ]);
    }

    /** คงตำแหน่งบัญชีจริง → ตำแหน่งนั้นไม่ใช่ของที่จันทราสร้าง · ลิงก์เชิญเดิมของเงายังพามาหาบัญชีจริง */
    public function test_after_merge_the_old_invite_link_leads_to_the_thaiprompt_position(): void
    {
        [$real, $realMember] = $this->activeMember('REAL0001', $this->rootMember);
        $shadowCode = $this->bill(9026, 626, 99)->json('data.member_code');
        $this->assertNotNull(JuntraAccount::where('juntra_user_id', 626)->value('enrolled_member_id'));

        $this->ensure(626, 'ลูกค้าผูกแล้ว', thaipromptUserId: $real->id)->assertOk();
        $this->assertNull(JuntraAccount::where('juntra_user_id', 626)->value('enrolled_member_id'));

        // เพื่อนกดลิงก์เดิมที่ลูกค้าแจกไว้ก่อนผูก
        $this->bill(9027, 627, 39, referral: $shadowCode)->assertStatus(201);
        $friend = MlmMember::where('user_id', JuntraAccount::where('juntra_user_id', 627)->value('user_id'))->firstOrFail();
        $this->assertSame($realMember->id, $friend->unilevel_sponsor_id);
        $this->assertCommission(9027, $real->id, 1, '3.90', '10.00');
    }

    /**
     * บิลของลูกทีมที่แจกค่าแนะนำชนจังหวะรวมบัญชี อาจลงผู้ใช้เงาหลังรวมเสร็จ (ต่างล็อกกัน)
     * รอบเก็บตกต้องย้ายตามไปบัญชีจริง — รวมยอดติดลบ (ค่าแนะนำที่ถูกดึงคืนหลังรวม หนี้ต้องตามคน)
     */
    public function test_sweep_moves_what_landed_on_the_shadow_after_the_merge(): void
    {
        $this->bill(9028, 628, 99)->assertStatus(201);
        $shadowId = JuntraAccount::where('juntra_user_id', 628)->value('user_id');
        $real = User::factory()->create();
        $this->ensure(628, 'ลูกค้าผูกแล้ว', thaipromptUserId: $real->id)->assertOk();

        // จำลองบิลที่ชนจังหวะ: ค่าแนะนำ + เงินเข้ากระเป๋าเงาหลังรวม
        $late = FortuneCommission::create([
            'user_id' => $shadowId, 'from_user_id' => $shadowId, 'fortune_reading_id' => $this->readingId(9028),
            'level' => 2, 'commission_type' => 'percent', 'commission_rate' => 5, 'amount' => 4.95,
            'reading_price' => 99, 'status' => FortuneCommission::STATUS_PAID,
        ]);
        Wallet::updateOrCreate(['user_id' => $shadowId], ['balance' => 4.95, 'total_income' => 4.95, 'currency' => 'THB', 'status' => 'active']);

        $this->artisan('juntra:sweep-merged-accounts')->assertSuccessful();

        $this->assertSame($real->id, $late->fresh()->user_id);
        $this->assertSame($real->id, $late->fresh()->from_user_id);
        $this->assertEquals(4.95, (float) Wallet::where('user_id', $real->id)->value('balance'));
        $this->assertEquals(0.0, (float) Wallet::where('user_id', $shadowId)->value('balance'));

        // ค่าแนะนำถูกดึงคืนจากกระเป๋าเงาหลังรวม → ยอดติดลบย้ายตามคน
        Wallet::where('user_id', $shadowId)->update(['balance' => -2]);
        $this->artisan('juntra:sweep-merged-accounts')->assertSuccessful();
        $this->assertEquals(2.95, (float) Wallet::where('user_id', $real->id)->value('balance'));
        $this->assertEquals(0.0, (float) Wallet::where('user_id', $shadowId)->value('balance'));

        // ไม่มีอะไรค้าง = ไม่ทำอะไร
        $this->artisan('juntra:sweep-merged-accounts')->expectsOutput('เก็บตก 0 บัญชี')->assertSuccessful();
    }

    /** บัญชีจริงอยู่ใต้ผังของเงา = รวมไม่ได้ ห้ามลองซ้ำทุกบิล (ล็อกผังแล้ว rollback) — เว้น 1 วัน */
    public function test_a_merge_that_cannot_happen_backs_off_for_a_day(): void
    {
        $this->bill(9029, 629, 99)->assertStatus(201);
        $shadowMember = MlmMember::where('user_id', JuntraAccount::where('juntra_user_id', 629)->value('user_id'))->firstOrFail();
        [$real] = $this->activeMember('REAL0001', $shadowMember);

        $this->ensure(629, 'ลูกค้า', thaipromptUserId: $real->id)->assertOk()->assertJsonPath('data.linked_via', 'auto');
        $failedAt = JuntraAccount::where('juntra_user_id', 629)->value('merge_failed_at');
        $this->assertNotNull($failedAt);

        $this->travel(2)->hours();
        $this->ensure(629, 'ลูกค้า', thaipromptUserId: $real->id)->assertOk();
        $this->assertEquals($failedAt, JuntraAccount::where('juntra_user_id', 629)->value('merge_failed_at'), 'ยังไม่ถึงรอบ ห้ามลองใหม่');

        $this->travel(23)->hours();
        $this->ensure(629, 'ลูกค้า', thaipromptUserId: $real->id)->assertOk();
        $this->assertNotEquals($failedAt, JuntraAccount::where('juntra_user_id', 629)->value('merge_failed_at'), 'ครบวันแล้วลองใหม่');
    }

    public function test_no_merge_when_the_thaiprompt_account_belongs_to_another_juntra_customer(): void
    {
        [$real] = $this->activeMember('REAL0001', $this->rootMember);
        JuntraAccount::create(['juntra_user_id' => 700, 'user_id' => $real->id, 'linked_via' => 'sso']);
        $this->bill(9025, 624, 99)->assertStatus(201);
        $shadowUserId = JuntraAccount::where('juntra_user_id', 624)->value('user_id');

        $this->ensure(624, 'ลูกค้า', thaipromptUserId: $real->id)->assertOk()->assertJsonPath('data.linked_via', 'auto');

        $this->assertSame($shadowUserId, JuntraAccount::where('juntra_user_id', 624)->value('user_id'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // บิล → ค่าแนะนำ
    // ─────────────────────────────────────────────────────────────────────

    public function test_bill_pays_percent_of_the_bill_to_both_levels(): void
    {
        [$grandUser, $grand] = $this->activeMember('GRAND001');
        [$inviterUser] = $this->activeMember('INVITE01', $grand);

        $res = $this->bill(9001, 601, 99, referral: 'INVITE01');

        $res->assertStatus(201)
            ->assertJsonPath('data.bill_reference', 'JW-9001')
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.duplicate', false)
            ->assertJsonCount(2, 'data.commissions');

        $this->assertCommission(9001, $inviterUser->id, 1, '9.90', '10.00');
        $this->assertCommission(9001, $grandUser->id, 2, '4.95', '5.00');
        $this->assertEquals(9.90, (float) Wallet::where('user_id', $inviterUser->id)->value('balance'));
        $this->assertEquals(4.95, (float) Wallet::where('user_id', $grandUser->id)->value('balance'));

        $reading = FortuneReading::where('bill_reference', 'JW-9001')->firstOrFail();
        $this->assertSame(FortuneReading::READING_TYPE_JUNTRA, $reading->reading_type);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $reading->conversation_status);
        $this->assertTrue((bool) $reading->is_paid);
        $this->assertNull($reading->platform_user_id);
    }

    public function test_cheap_bill_never_pays_more_than_its_price(): void
    {
        [$inviterUser] = $this->activeMember('INVITE01', $this->rootMember);

        $this->bill(9002, 602, 9, referral: 'INVITE01')->assertStatus(201);

        $this->assertCommission(9002, $inviterUser->id, 1, '0.90', '10.00');
        $total = FortuneCommission::where('fortune_reading_id', $this->readingId(9002))->sum('amount');
        $this->assertLessThan(9, (float) $total);
    }

    public function test_bot_bills_keep_their_fixed_rate(): void
    {
        [, $inviter] = $this->activeMember('INVITE01');
        [$buyer, $buyerMember] = $this->activeMember('BUYER001', $inviter);

        $reading = FortuneReading::create([
            'user_id' => $buyer->id,
            'facebook_user_id' => 'test_fb_'.uniqid(),
            'reading_type' => 'deep',
            'questions' => ['ทดสอบ'],
            'is_paid' => true,
            'amount_paid' => 39,
            'paid_at' => now(),
            'conversation_status' => 'paid',
            'platform' => 'facebook',
        ]);

        app(\App\Services\FortuneCommissionService::class)
            ->distributeCommissions($reading, $buyerMember, FortuneTellingSetting::getSettings());

        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $reading->id,
            'level' => 1,
            'commission_type' => 'fixed',
            'commission_rate' => '10.00',
            'amount' => '10.00',
        ]);
    }

    public function test_resending_a_bill_pays_nothing_twice(): void
    {
        [$inviterUser] = $this->activeMember('INVITE01', $this->rootMember);

        $this->bill(9003, 603, 99, referral: 'INVITE01')->assertStatus(201);
        $this->bill(9003, 603, 99, referral: 'INVITE01')
            ->assertOk()
            ->assertJsonPath('data.duplicate', true);

        $this->assertSame(1, FortuneReading::where('bill_reference', 'JW-9003')->count());
        $this->assertSame(1, FortuneCommission::where('user_id', $inviterUser->id)->count());
        $this->assertEquals(9.90, (float) Wallet::where('user_id', $inviterUser->id)->value('balance'));
    }

    public function test_bill_number_cannot_be_reused_for_another_customer(): void
    {
        $this->bill(9004, 604, 99)->assertStatus(201);

        $this->bill(9004, 605, 99)
            ->assertStatus(409)
            ->assertJsonPath('reason_code', 'bill_conflict');
    }

    // ─────────────────────────────────────────────────────────────────────
    // คืนเงินที่จันทรา → ดึงค่าแนะนำคืน
    // ─────────────────────────────────────────────────────────────────────

    public function test_refund_at_juntra_claws_back_every_level(): void
    {
        [$grandUser, $grand] = $this->activeMember('GRAND001');
        [$inviterUser] = $this->activeMember('INVITE01', $grand);
        $this->bill(9005, 606, 99, referral: 'INVITE01')->assertStatus(201);

        $this->postJson('/api/v1/juntra/server/affiliate/bills/9005/void', ['reason' => 'แม่หมออ่านไม่สำเร็จ'])
            ->assertOk()
            ->assertJsonPath('data.voided', true)
            ->assertJsonPath('data.already_voided', false);

        $this->assertSame(2, FortuneCommission::where('fortune_reading_id', $this->readingId(9005))
            ->where('status', FortuneCommission::STATUS_REJECTED)->count());
        $this->assertEquals(0.0, (float) Wallet::where('user_id', $inviterUser->id)->value('balance'));
        $this->assertEquals(0.0, (float) Wallet::where('user_id', $grandUser->id)->value('balance'));
        $this->assertFalse((bool) FortuneReading::find($this->readingId(9005))->is_paid);

        // ส่งซ้ำ: ไม่ดึงคืนรอบสอง
        $this->postJson('/api/v1/juntra/server/affiliate/bills/9005/void')
            ->assertOk()
            ->assertJsonPath('data.already_voided', true);
        $this->assertEquals(0.0, (float) Wallet::where('user_id', $inviterUser->id)->value('balance'));

        // บิลที่ถูกยกเลิกแล้ว ส่งบิลเดิมมาอีกก็ไม่จ่ายใหม่
        $this->bill(9005, 606, 99, referral: 'INVITE01')
            ->assertOk()
            ->assertJsonPath('data.status', 'voided');
        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $this->readingId(9005))
            ->where('status', '!=', FortuneCommission::STATUS_REJECTED)->count());
    }

    public function test_unknown_bill_void_is_404(): void
    {
        $this->postJson('/api/v1/juntra/server/affiliate/bills/424242/void')
            ->assertNotFound()
            ->assertJsonPath('reason_code', 'unknown_bill')
            ->assertJsonPath('data.reason_code', 'unknown_bill');
    }

    /**
     * คำสั่งยกเลิกมาถึงก่อนตัวบิล (จันทราเลิกรอคำตอบบิล แล้วคืนเงินลูกค้า) —
     * บิลที่ตามมาทีหลังต้องไม่แจกค่าแนะนำ
     */
    public function test_void_arriving_before_the_bill_blocks_its_commission(): void
    {
        [$inviterUser] = $this->activeMember('INVITE01', $this->rootMember);

        $this->postJson('/api/v1/juntra/server/affiliate/bills/9010/void', ['reason' => 'คืนเงิน'])->assertNotFound();

        $this->bill(9010, 610, 99, referral: 'INVITE01')
            ->assertOk()
            ->assertJsonPath('data.status', 'voided')
            ->assertJsonCount(0, 'data.commissions');

        $this->assertSame(0, FortuneCommission::where('user_id', $inviterUser->id)->count());
        $this->assertEquals(0.0, (float) (Wallet::where('user_id', $inviterUser->id)->value('balance') ?? 0));
    }

    /** แอพ SMS Checker ต้องไม่เห็น/อนุมัติบิลจันทรา (จะผูก SMS ของลูกค้าจริงเข้าไป แล้วสั่งบอทรัน AI) */
    public function test_sms_checker_scope_leaves_juntra_bills_out(): void
    {
        $this->bill(9011, 611, 99)->assertStatus(201);

        $this->assertSame(0, FortuneReading::withoutJuntra()->where('bill_reference', 'JW-9011')->count());
        $this->assertSame(1, FortuneReading::where('bill_reference', 'JW-9011')->count());
    }

    public function test_back_office_buttons_cannot_void_a_juntra_bill(): void
    {
        [$inviterUser] = $this->activeMember('INVITE01', $this->rootMember);
        $this->bill(9006, 607, 99, referral: 'INVITE01')->assertStatus(201);

        $result = FortuneReading::find($this->readingId(9006))->voidApproval('ปุ่มหลังบ้าน', 1);

        $this->assertFalse($result['ok']);
        $this->assertSame(FortuneReading::JUNTRA_VOID_ELSEWHERE, $result['message']);
        $this->assertTrue((bool) FortuneReading::find($this->readingId(9006))->is_paid);
        $this->assertEquals(9.90, (float) Wallet::where('user_id', $inviterUser->id)->value('balance'));
    }

    public function test_back_office_cannot_mark_a_voided_juntra_bill_paid(): void
    {
        $this->activeMember('INVITE01', $this->rootMember);
        $this->bill(9009, 609, 99, referral: 'INVITE01')->assertStatus(201);
        $this->postJson('/api/v1/juntra/server/affiliate/bills/9009/void')->assertOk();

        $reading = FortuneReading::find($this->readingId(9009));
        $reading->confirmPayment();

        $this->assertFalse((bool) $reading->fresh()->is_paid, 'สถานะจ่ายของบิลจันทรามาจากจันทราเท่านั้น');
    }

    // ─────────────────────────────────────────────────────────────────────
    // สิ่งที่จันทราแสดง
    // ─────────────────────────────────────────────────────────────────────

    public function test_stats_leave_out_clawed_back_commissions(): void
    {
        [$inviterUser] = $this->activeMember('INVITE01', $this->rootMember);
        $inviterRef = 700;
        JuntraAccount::create(['juntra_user_id' => $inviterRef, 'user_id' => $inviterUser->id, 'linked_via' => 'sso']);

        $this->bill(9007, 608, 99, referral: 'INVITE01')->assertStatus(201);
        $this->bill(9008, 608, 39, referral: 'INVITE01')->assertStatus(201);
        $this->postJson('/api/v1/juntra/server/affiliate/bills/9007/void')->assertOk();

        $this->getJson("/api/v1/juntra/server/affiliate/members/{$inviterRef}/stats")
            ->assertOk()
            ->assertJsonPath('totals.all_time', 3.9)
            ->assertJsonPath('totals.reversed', 9.9)
            ->assertJsonPath('counts.commissions_total', 1)
            ->assertJsonPath('mlm.direct_referrals', 1)
            ->assertJsonPath('mlm.total_team_members', 1)
            ->assertJsonPath('user.referral_code', 'INVITE01');
    }

    public function test_tree_shows_only_the_members_own_downline(): void
    {
        // ราก A กับพี่น้อง S อยู่ใต้ผู้แนะนำเดียวกัน — ผังของ A ต้องไม่มีทีมของ S
        [$aUser, $a] = $this->activeMember('AAAA0001', $this->rootMember);
        [, $s] = $this->activeMember('SSSS0001', $this->rootMember);
        [, $child] = $this->activeMember('CHLD0001', $a);
        $this->activeMember('GCHD0001', $child);
        $this->activeMember('SCHD0001', $s);
        JuntraAccount::create(['juntra_user_id' => 800, 'user_id' => $aUser->id, 'linked_via' => 'sso']);

        $res = $this->getJson('/api/v1/juntra/server/affiliate/members/800/tree?depth=5')->assertOk();

        $res->assertJsonPath('tree.id', $a->id)
            ->assertJsonPath('total_descendants', 2)
            ->assertJsonPath('tree.direct_referrals', 1)
            ->assertJsonPath('tree.total_team_members', 2)
            ->assertJsonCount(1, 'tree.children')
            ->assertJsonPath('tree.children.0.id', $child->id)
            ->assertJsonCount(1, 'tree.children.0.children');
    }

    public function test_member_reads_before_enrolment_say_not_enrolled(): void
    {
        foreach (['stats', 'tree', 'commissions'] as $endpoint) {
            $this->getJson("/api/v1/juntra/server/affiliate/members/99999/{$endpoint}")
                ->assertNotFound()
                ->assertJsonPath('reason_code', 'not_enrolled');
        }
    }

    /**
     * 🌙 (2026-09-23) บิลที่จ่ายก่อนเปิดระบบค่าแนะนำ — นับว่าลูกค้า "เคยมีบิลที่ชำระแล้ว" แต่ไม่แจกค่าแนะนำ
     *   (เจ้าของสั่ง: ไม่จ่ายย้อนหลัง) ส่งซ้ำก็ไม่แจก · ลูกค้าคนนั้นได้สิทธิ์รับค่าแนะนำจากทีมตัวเอง
     */
    public function test_history_bill_makes_the_customer_eligible_but_pays_nothing(): void
    {
        $this->activeMember('INVITE01', $this->rootMember);
        $history = [
            'bill_id' => 9101, 'user_ref' => 901, 'name' => 'ลูกค้าเก่า', 'amount' => 129, 'product' => 'tarot_year',
            'paid_at' => now()->subMonth()->toIso8601String(), 'referral_code' => 'INVITE01', 'history_only' => true,
        ];

        $this->postJson('/api/v1/juntra/server/affiliate/bills', $history)->assertStatus(201)
            ->assertJsonPath('data.history_only', true)
            ->assertJsonCount(0, 'data.commissions');
        $this->postJson('/api/v1/juntra/server/affiliate/bills', $history)->assertOk()
            ->assertJsonCount(0, 'data.commissions');
        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $this->readingId(9101))->count());

        // ลูกค้าเก่าคนนี้ชวนเพื่อน → บิลปกติของเพื่อนจ่ายสายตรงให้เขา 10%
        $oldCustomer = JuntraAccount::where('juntra_user_id', 901)->value('user_id');
        $code = MlmMember::where('user_id', $oldCustomer)->value('member_code');
        $this->bill(9102, 902, 99, referral: $code)->assertStatus(201);
        $this->assertCommission(9102, $oldCustomer, 1, '9.90', '10.00');

        $this->getJson('/api/v1/juntra/server/affiliate/members/901/stats')->assertOk()
            ->assertJsonPath('mlm.commission_eligible', true)
            ->assertJsonPath('wallet.balance', 9.9);
    }

    /** ลูกค้าที่ยังไม่เคยมีบิลที่ชำระแล้ว — สถิติบอกว่ายังไม่มีสิทธิ์ และค่าแนะนำจากทีมไม่ถึงเขา */
    public function test_customer_without_a_paid_bill_is_not_eligible_yet(): void
    {
        $this->ensure(903, 'ยังไม่เคยซื้อ')->assertStatus(201);
        $code = MlmMember::where('user_id', JuntraAccount::where('juntra_user_id', 903)->value('user_id'))->value('member_code');

        $this->getJson('/api/v1/juntra/server/affiliate/members/903/stats')->assertOk()
            ->assertJsonPath('mlm.commission_eligible', false)
            ->assertJsonPath('wallet.balance', 0);

        $this->bill(9103, 904, 99, referral: $code)->assertStatus(201);
        $this->assertDatabaseMissing('fortune_commissions', [
            'fortune_reading_id' => $this->readingId(9103),
            'user_id' => JuntraAccount::where('juntra_user_id', 903)->value('user_id'),
        ]);
    }

    public function test_server_routes_need_the_juntra_server_token(): void
    {
        $this->withoutToken()->postJson('/api/v1/juntra/server/affiliate/accounts', ['user_ref' => 1, 'name' => 'x'])
            ->assertUnauthorized();
    }

    // ─────────────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────────────

    private function ensure(int $userRef, string $name, ?int $thaipromptUserId = null, ?string $referral = null)
    {
        return $this->postJson('/api/v1/juntra/server/affiliate/accounts', array_filter([
            'user_ref' => $userRef,
            'name' => $name,
            'thaiprompt_user_id' => $thaipromptUserId,
            'referral_code' => $referral,
        ], fn ($v) => $v !== null));
    }

    private function assertCommission(int $billId, int $recipientId, int $level, string $amount, string $rate): void
    {
        $this->assertDatabaseHas('fortune_commissions', [
            'fortune_reading_id' => $this->readingId($billId),
            'user_id' => $recipientId,
            'level' => $level,
            'commission_type' => 'percent',
            'commission_rate' => $rate,
            'amount' => $amount,
            'status' => FortuneCommission::STATUS_PAID,
        ]);
    }
}
