<?php

namespace Tests\Feature\AdminApp;

use App\Http\Controllers\Api\Admin\Fortune\FortuneReadingsController;
use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsPaymentNotification;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 💸 (v3) ปุ่มเงินของบิลในแอปแอดมิน
 *
 *   - refund ใช้ FortuneReading::voidApproval() ตัวเดียวกับปุ่ม void บนเว็บ: คืน UPA · ปลด SMS · ดึงค่าแนะนำคืน · ปิดบิล
 *     (เดิมพลิก is_paid อย่างเดียว) — รูปคำตอบเดิม
 *   - mark-paid บันทึกยอดจริงของบิล (amount_paid → UPA → ราคาแพคเกจ) ไม่ใช่ 49 ตายตัว · amount ที่ส่งมายังชนะ
 */
#[Group('admin-app')]
class AdminAppBillMoneyActionsTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ห้ามสั่งงาน AI/ส่งข้อความจริงหลัง mark-paid — ทดสอบแค่ยอดที่บันทึก
        $this->app->bind(FortuneReadingsController::class, fn () => new class extends FortuneReadingsController
        {
            protected function triggerFortuneFlowAfterPayment(FortuneReading $reading): void {}
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();

        parent::tearDown();
    }

    public function test_refund_reverses_everything_like_the_web_void(): void
    {
        $buyer = $this->makeMember();
        $referrer = $this->makeMember();
        $wallet = Wallet::where('user_id', $referrer->id)->first() ?? Wallet::factory()->create(['user_id' => $referrer->id]);
        $wallet->forceFill(['balance' => 50, 'total_income' => 50])->save();

        $upaId = $this->upa(39, 39.42, 'used');
        $reading = $this->makeReading([
            'user_id' => $buyer->id, 'is_paid' => true, 'amount_paid' => 39.42, 'amount_received' => 39.42,
            'paid_at' => now()->subHour(), 'conversation_status' => FortuneReading::STATUS_COMPLETED,
            'deep_response' => 'คำทำนาย', 'unique_payment_amount_id' => $upaId,
        ]);
        $sms = SmsPaymentNotification::create([
            'bank' => 'KBANK', 'type' => 'credit', 'amount' => 39.42, 'sms_timestamp' => now()->subHour(),
            'device_id' => 'dev-1', 'nonce' => 'n-refund', 'status' => 'matched', 'sender_or_receiver' => 'นาย ก',
            'matched_transaction_id' => $reading->id,
        ]);
        $commission = FortuneCommission::create([
            'user_id' => $referrer->id, 'from_user_id' => $buyer->id, 'fortune_reading_id' => $reading->id,
            'level' => 1, 'commission_type' => 'fixed', 'commission_rate' => 5, 'amount' => 5, 'reading_price' => 39,
            'status' => FortuneCommission::STATUS_PAID,
        ]);

        $admin = $this->actAs($this->makeAdmin());
        $res = $this->postJson("/api/admin/fortune/readings/{$reading->id}/refund", ['reason' => 'โอนผิดบิล'])->assertOk();

        // รูปคำตอบเดิม: success + FortuneReadingResource + message
        $res->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $reading->id)
            ->assertJsonPath('data.is_paid', false);
        $this->assertStringStartsWith('ส่งเข้าคิวคืนเงินแล้ว', (string) $res->json('message'));

        $fresh = $reading->fresh();
        $this->assertFalse((bool) $fresh->is_paid);
        $this->assertNull($fresh->paid_at);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $fresh->conversation_status);
        $this->assertTrue((bool) $fresh->getConversationState('approval_voided'));
        $this->assertSame($admin->id, $fresh->getConversationState('approval_voided_by_admin_id'));
        $this->assertStringContainsString('โอนผิดบิล', (string) $fresh->getConversationState('approval_void_reason'));

        $this->assertSame('cancelled', DB::table('unique_payment_amounts')->where('id', $upaId)->value('status'), 'ปล่อยยอดทศนิยม');
        $this->assertNull($sms->fresh()->matched_transaction_id, 'ปลด SMS ให้ไปจับบิลที่ถูก');
        $this->assertSame('pending', $sms->fresh()->status);
        $this->assertSame(FortuneCommission::STATUS_REJECTED, $commission->fresh()->status, 'ดึงค่าแนะนำคืน');
        $this->assertEquals(45.0, (float) $wallet->fresh()->balance);

        // กองบิล: คืนเงิน/ยกเลิกการอนุมัติ
        $bill = collect($this->getJson('/api/admin/fortune/bills?status=refunded')->assertOk()->json('data.data'))->firstWhere('id', $reading->id);
        $this->assertSame('approval_voided', $bill['status_reason']);

        // กดซ้ำ = บิลยังไม่จ่ายแล้ว → 422 (ไม่ดึงคอมซ้ำ)
        $this->postJson("/api/admin/fortune/readings/{$reading->id}/refund")->assertStatus(422);
        $this->assertEquals(45.0, (float) $wallet->fresh()->balance);
    }

    public function test_refund_refuses_unpaid_and_juntra_bills(): void
    {
        $this->actAs($this->makeAdmin());

        $unpaid = $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.1]);
        $this->postJson("/api/admin/fortune/readings/{$unpaid->id}/refund")->assertStatus(422)->assertJsonPath('success', false);

        $juntra = $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_JUNTRA, 'is_paid' => true,
            'amount_paid' => 99, 'paid_at' => now()]);
        $this->postJson("/api/admin/fortune/readings/{$juntra->id}/refund")->assertStatus(422)
            ->assertJsonPath('message', FortuneReading::JUNTRA_VOID_ELSEWHERE);
        $this->assertTrue((bool) $juntra->fresh()->is_paid);
    }

    public function test_mark_paid_stores_the_real_bill_amount_not_49(): void
    {
        $settings = FortuneTellingSetting::getGlobalSettings();
        $settings->forceFill(['deep_reading_price' => 39, 'celtic_cross_price' => 99])->save();
        FortuneTellingSetting::clearSettingsCache();
        $this->actAs($this->makeAdmin());

        // Deep 39: ยังไม่มี amount_paid แต่ออก QR ทศนิยมแล้ว → ยอด UPA
        $deep = $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
            'unique_payment_amount_id' => $this->upa(39, 39.42, 'reserved')]);
        $this->postJson("/api/admin/fortune/readings/{$deep->id}/mark-paid")->assertOk();
        $this->assertSame('39.42', number_format((float) $deep->fresh()->amount_paid, 2, '.', ''));
        $this->assertTrue((bool) $deep->fresh()->is_paid);

        // Celtic 99: มี amount_paid อยู่แล้ว → ใช้ยอดนั้น
        $celticWithAmount = $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT, 'amount_paid' => 99.17]);
        $this->postJson("/api/admin/fortune/readings/{$celticWithAmount->id}/mark-paid")->assertOk();
        $this->assertSame('99.17', number_format((float) $celticWithAmount->fresh()->amount_paid, 2, '.', ''));

        // Celtic 99: ไม่มียอดเลย → ราคาแพคเกจในตั้งค่า (ไม่ใช่ 49)
        $celticBare = $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT]);
        $this->postJson("/api/admin/fortune/readings/{$celticBare->id}/mark-paid")->assertOk();
        $this->assertSame('99.00', number_format((float) $celticBare->fresh()->amount_paid, 2, '.', ''));

        // Deep 39 ไม่มียอดเลย → 39
        $deepBare = $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
        $this->postJson("/api/admin/fortune/readings/{$deepBare->id}/mark-paid")->assertOk();
        $this->assertSame('39.00', number_format((float) $deepBare->fresh()->amount_paid, 2, '.', ''));

        // amount ที่แอดมินส่งมาชนะเสมอ
        $explicit = $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
            'unique_payment_amount_id' => $this->upa(39, 39.55, 'reserved')]);
        $this->postJson("/api/admin/fortune/readings/{$explicit->id}/mark-paid", ['amount' => 40])->assertOk();
        $this->assertSame('40.00', number_format((float) $explicit->fresh()->amount_paid, 2, '.', ''));

        // ไม่มียอดให้ดึง (ไพ่ฟรี) → 422 ให้แอดมินระบุยอด — ไม่เดาเลข
        $free = $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_FREE_CARD,
            'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT]);
        $this->postJson("/api/admin/fortune/readings/{$free->id}/mark-paid")->assertStatus(422)
            ->assertJsonPath('error_code', 'AMOUNT_REQUIRED');
        $this->assertFalse((bool) $free->fresh()->is_paid);
    }

    private function upa(float $base, float $amount, string $status): int
    {
        return DB::table('unique_payment_amounts')->insertGetId([
            'base_amount' => $base, 'unique_amount' => $amount, 'decimal_suffix' => (int) round(($amount - floor($amount)) * 100),
            'status' => $status, 'expires_at' => now()->addHours(3), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
