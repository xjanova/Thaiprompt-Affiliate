<?php

namespace Tests\Feature\Fortune;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Services\FortuneCommissionAdminService;
use App\Services\FortuneCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * F24: บิลที่ถูก void (แถวค่าแนะนำเป็น rejected) แล้วจ่าย/อนุมัติใหม่
 *
 * - ต้องจ่ายค่าแนะนำอีกครั้ง "ครั้งเดียว" — ใช้แถว rejected เดิม (unique fc_reading_user_level_unique)
 * - API mark-paid ห้ามตอบว่า "ส่งคำทำนายแล้ว" ทั้งที่ข้ามการส่ง
 * - บิลที่ยังไม่เคยส่งคำทำนาย → เริ่ม flow แบบเดียวกับการอนุมัติครั้งแรก
 */
class FortuneRevoidRepayCommissionTest extends TestCase
{
    use BuildsFortuneCommissionChain;
    use RefreshDatabase;

    protected User $sponsorUser;

    protected User $grandUser;

    protected User $buyerUser;

    protected MlmMember $buyer;

    protected string $line;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::fake();
        $this->setUpFortuneChain();

        [$this->grandUser, $grand] = $this->makeChainMember();
        [$this->sponsorUser, $sponsor] = $this->makeChainMember($grand);
        $this->line = 'U'.md5(uniqid('', true));
        [$this->buyerUser, $this->buyer] = $this->makeChainMember($sponsor, ['line_user_id' => $this->line]);
    }

    private function distribute(FortuneReading $bill): void
    {
        app(FortuneCommissionService::class)->distributeCommissions($bill->fresh(), $this->buyer, $this->settings->fresh());
    }

    private function lineBill(string $type, float $amount, array $extra = []): FortuneReading
    {
        return $this->makeBill($this->buyerUser->id, $type, $amount, array_merge([
            'facebook_user_id' => $this->line, 'platform_user_id' => $this->line, 'platform' => 'line',
        ], $extra));
    }

    public function test_repaid_bill_after_void_pays_commission_again_exactly_once(): void
    {
        $bill = $this->lineBill(FortuneReading::READING_TYPE_CELTIC_CROSS, 99.82);
        $this->distribute($bill);
        $this->assertTrue($bill->fresh()->voidApproval('ทดสอบ', null)['ok']);
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));

        // ลูกค้าจ่ายจริงรอบใหม่
        $bill->fresh()->forceFill(['is_paid' => true, 'paid_at' => now()])->save();
        $this->distribute($bill);
        $this->distribute($bill);   // เรียกซ้ำต้องไม่จ่ายเพิ่ม

        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(2.0, $this->balanceOf($this->grandUser->id));
        $rows = FortuneCommission::where('fortune_reading_id', $bill->id)->get();
        $this->assertCount(2, $rows, 'ใช้แถวเดิม ไม่สร้างแถวใหม่ (unique index)');
        $this->assertTrue($rows->every(fn ($r) => $r->status === FortuneCommission::STATUS_PAID));
    }

    public function test_void_after_repay_claws_back_again(): void
    {
        $bill = $this->lineBill(FortuneReading::READING_TYPE_CELTIC_CROSS, 99.82);
        $this->distribute($bill);
        $bill->fresh()->voidApproval('ครั้งที่ 1', null);
        $bill->fresh()->forceFill(['is_paid' => true, 'paid_at' => now()])->save();
        $this->distribute($bill);

        $this->assertTrue($bill->fresh()->voidApproval('ครั้งที่ 2', null)['ok']);

        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(0.0, $this->balanceOf($this->grandUser->id));
        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $bill->id)
            ->where('status', FortuneCommission::STATUS_PAID)->count());
    }

    /** void ก่อนลูกค้าเปิดไพ่ แล้วแอดมินมาร์คจ่ายใหม่ → เริ่ม Celtic เหมือนอนุมัติครั้งแรก + ค่าแนะนำกลับมา */
    public function test_admin_mark_paid_on_voided_undelivered_celtic_restarts_flow_and_pays(): void
    {
        $bill = $this->lineBill(FortuneReading::READING_TYPE_CELTIC_CROSS, 99.82, [
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
        ]);
        $this->distribute($bill);
        $bill->fresh()->voidApproval('อนุมัติผิด', null);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']), ['admin']);
        $res = $this->postJson("/api/admin/fortune/readings/{$bill->id}/mark-paid", ['amount' => 99.82])->assertOk();

        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $bill->fresh()->conversation_status, 'ต้องเริ่ม flow Celtic ใหม่');
        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(2.0, $this->balanceOf($this->grandUser->id));
        $this->assertSame('มาร์คจ่ายเรียบร้อย — ส่งคำทำนายให้ลูกค้าแล้ว', $res->json('message'));
    }

    /** บิล Deep ที่ส่งคำทำนายไปแล้วถูก void แล้วมาร์คจ่ายใหม่ → ไม่ส่งซ้ำ แต่ค่าแนะนำกลับมา และข้อความตอบตามจริง */
    public function test_admin_mark_paid_on_voided_delivered_bill_pays_commission_and_says_not_resent(): void
    {
        $bill = $this->lineBill(FortuneReading::READING_TYPE_DEEP, 39.42, [
            'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'deep_response' => 'คำทำนายที่ส่งไปแล้ว',
        ]);
        $this->distribute($bill);
        $this->assertSame(5.0, $this->balanceOf($this->sponsorUser->id));
        $bill->fresh()->voidApproval('อนุมัติผิด', null);
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']), ['admin']);
        $res = $this->postJson("/api/admin/fortune/readings/{$bill->id}/mark-paid", ['amount' => 39.42])->assertOk();

        // dispatchSmart ไม่ผ่าน Queue — ตรวจว่า flow ไม่ถูกเริ่มจากสถานะ (triggerFortuneFlowAfterPayment ตั้ง paid ก่อน dispatch เสมอ)
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $bill->fresh()->conversation_status, 'ห้ามเริ่มส่งคำทำนายซ้ำ');
        $this->assertSame('คำทำนายที่ส่งไปแล้ว', $bill->fresh()->deep_response);
        $this->assertSame(
            'มาร์คจ่ายเรียบร้อย — บิลนี้ลูกค้าได้ใช้บริการไปแล้วก่อนถูกยกเลิก ระบบไม่ได้ส่งคำทำนายซ้ำ (คำนวณค่าแนะนำของบิลนี้ใหม่ตามกติกาแล้ว)',
            $res->json('message')
        );
        $this->assertSame(5.0, $this->balanceOf($this->sponsorUser->id), 'ค่าแนะนำต้องกลับมา');
        $this->assertStringNotContainsString('ส่งคำทำนายให้ลูกค้าแล้ว', (string) $res->json('message'));
    }

    /** แถวที่แอดมินกดปฏิเสธเอง (ไม่ใช่ void บิล) ต้องไม่ถูกปลุกกลับมาจ่ายเมื่อคำนวณค่าแนะนำซ้ำ */
    public function test_admin_rejected_commission_is_not_repaid_on_rerun(): void
    {
        $bill = $this->lineBill(FortuneReading::READING_TYPE_CELTIC_CROSS, 99.5);
        $manual = app(FortuneCommissionAdminService::class)->createManual([
            'user_id' => $this->sponsorUser->id, 'from_user_id' => $this->buyerUser->id,
            'fortune_reading_id' => $bill->id, 'level' => 1, 'amount' => 10, 'notes' => 'manual',
        ]);
        $this->assertTrue(app(FortuneCommissionAdminService::class)->reject($manual, 'แอดมินไม่อนุมัติ'));

        $this->distribute($bill);

        $this->assertSame(FortuneCommission::STATUS_REJECTED, $manual->fresh()->status);
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
    }

    /** ผู้แนะนำเปลี่ยนระหว่าง void กับจ่ายใหม่ → สร้างแถวใหม่ให้คนใหม่ แถวที่ดึงคืน (และหนี้) ของคนเก่ายังเป็นของคนเก่า */
    public function test_repay_after_sponsor_change_creates_new_row_and_keeps_old_history(): void
    {
        $bill = $this->lineBill(FortuneReading::READING_TYPE_CELTIC_CROSS, 99.5);
        $this->distribute($bill);
        $oldRow = FortuneCommission::where('fortune_reading_id', $bill->id)->where('level', 1)->first();
        Wallet::where('user_id', $this->sponsorUser->id)->update(['balance' => 0]);
        $this->assertTrue($bill->fresh()->voidApproval('ทดสอบ', null)['ok']);

        [$newSponsorUser, $newSponsor] = $this->makeChainMember();
        $this->buyer->update(['unilevel_sponsor_id' => $newSponsor->id]);
        $this->buyer = $this->buyer->fresh();
        $bill->fresh()->forceFill(['is_paid' => true, 'paid_at' => now()])->save();
        $this->distribute($bill);

        $oldRow = $oldRow->fresh();
        $this->assertSame($this->sponsorUser->id, (int) $oldRow->user_id);
        $this->assertSame(FortuneCommission::STATUS_REJECTED, $oldRow->status);
        $this->assertSame(10.0, $this->balanceOf($newSponsorUser->id));
        $this->assertEquals(10.0, (float) WalletDebt::where('source_type', 'FortuneClawback')
            ->where('source_id', $oldRow->id)->where('user_id', $this->sponsorUser->id)->value('remaining_amount'));
    }

    /** void ตอนผู้แนะนำถอนเงินไปแล้ว (เป็นหนี้) แล้วบิลจ่ายใหม่ → หักหนี้ของแถวนั้นจากค่าแนะนำที่จ่ายใหม่ ไม่ได้เงินซ้ำ */
    public function test_repay_settles_the_clawback_debt_of_the_restored_row(): void
    {
        $bill = $this->lineBill(FortuneReading::READING_TYPE_CELTIC_CROSS, 99.5);
        $this->distribute($bill);
        Wallet::where('user_id', $this->sponsorUser->id)->update(['balance' => 0]);
        $this->assertTrue($bill->fresh()->voidApproval('ทดสอบ', null)['ok']);
        $debt = WalletDebt::where('user_id', $this->sponsorUser->id)->where('source_type', 'FortuneClawback')->first();
        $this->assertEquals(10.0, (float) $debt->remaining_amount);

        $bill->fresh()->forceFill(['is_paid' => true, 'paid_at' => now()])->save();
        $this->distribute($bill);

        $debt = $debt->fresh();
        $this->assertSame(WalletDebt::STATUS_PAID, $debt->status);
        $this->assertEquals(0.0, (float) $debt->remaining_amount);
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id), 'เงินที่จ่ายใหม่ถูกหักชำระหนี้ ไม่ได้ซ้ำ');
        $this->assertSame(1, WalletTransaction::where('user_id', $this->sponsorUser->id)
            ->where('reference_type', WalletDebt::class)->where('type', 'fee')->count());
        $this->assertSame(FortuneCommission::STATUS_PAID, FortuneCommission::where('fortune_reading_id', $bill->id)
            ->where('level', 1)->value('status'));
    }
}
