<?php

namespace Tests\Feature\AdminApp;

use App\Http\Controllers\Api\Admin\Fortune\FortuneReadingsController;
use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\SmsPaymentNotification;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        // ข้อความตามจริง: คืนทันที ไม่ได้เข้าคิว + บอกว่าดึงค่าแนะนำคืนกี่รายการ
        $this->assertStringStartsWith('คืนเงินแล้ว', (string) $res->json('message'));
        $this->assertStringContainsString('ดึงค่าแนะนำคืน 1 รายการ', (string) $res->json('message'));
        $this->assertStringNotContainsString('คิว', (string) $res->json('message'));

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

    /**
     * (v3 จับผี C1/L4) คืนเงินตอนงาน AI วิ่ง = 409 · ลูกค้ายังใช้บริการอยู่ต้องส่ง confirm · บิลจบแล้วคืนได้เลย (Warroom)
     */
    public function test_refund_guards_in_flight_and_in_progress_bills(): void
    {
        $this->actAs($this->makeAdmin());

        // งาน AI กำลังสร้างคำทำนาย → ห้ามคืน (job จะเขียนธงชุดเก่าทับ + ส่งคำทำนายให้คนที่คืนเงินแล้ว)
        $generating = $this->makeReading(['is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subMinutes(3),
            'conversation_status' => FortuneReading::STATUS_PAID, 'birth_date' => '1995-03-15']);
        Cache::put('fortune:deep_gen:'.$generating->id, 1, 300);
        $this->postJson("/api/admin/fortune/readings/{$generating->id}/refund", ['confirm' => true])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'GENERATION_IN_FLIGHT');
        $this->assertTrue((bool) $generating->fresh()->is_paid);
        Cache::forget('fortune:deep_gen:'.$generating->id);

        // Celtic กำลังเปิดไพ่ (ยังใช้บริการอยู่) → ต้องยืนยันก่อน
        $picking = $this->makeReading(['reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
            'facebook_user_id' => '61550000003101', 'platform_user_id' => '61550000003101',
            'is_paid' => true, 'amount_paid' => 99, 'paid_at' => now()->subMinutes(10),
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING]);
        $res = $this->postJson("/api/admin/fortune/readings/{$picking->id}/refund", ['reason' => 'ลูกค้าขอยกเลิก'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'REFUND_CONFIRM_REQUIRED');
        $this->assertStringContainsString('ดึงค่าแนะนำคืน', (string) $res->json('message'));
        $this->assertStringContainsString('ปิดเซสชัน', (string) $res->json('message'));
        $this->assertTrue((bool) $picking->fresh()->is_paid, 'ยังไม่แตะอะไร');
        $this->assertSame(FortuneReading::STATUS_CELTIC_PICKING, $picking->fresh()->conversation_status);

        // ยืนยันแล้ว → คืนได้
        $this->postJson("/api/admin/fortune/readings/{$picking->id}/refund", ['reason' => 'ลูกค้าขอยกเลิก', 'confirm' => true])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.is_paid', false);
        $this->assertSame(FortuneReading::STATUS_COMPLETED, $picking->fresh()->conversation_status);

        // บิลจบแล้ว → คืนได้เลยไม่ต้องยืนยัน (ปุ่ม Warroom เดิมยังใช้ได้)
        $done = $this->makeReading(['facebook_user_id' => '61550000003102', 'platform_user_id' => '61550000003102',
            'is_paid' => true, 'amount_paid' => 39, 'paid_at' => now()->subDay(),
            'conversation_status' => FortuneReading::STATUS_COMPLETED, 'deep_response' => 'คำทำนาย']);
        $this->postJson("/api/admin/fortune/readings/{$done->id}/refund")->assertOk()->assertJsonPath('data.is_paid', false);
    }

    /**
     * (v3 จับผี L6) mark-paid กดซ้ำ/สองเครื่อง — มีคำขอเดียวที่ได้ทำ · ได้ล็อกแล้วอ่านแถวใหม่ก่อนตัดสิน
     */
    public function test_mark_paid_is_single_flight(): void
    {
        $this->actAs($this->makeAdmin());
        $reading = $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.31]);

        // อีกคำขอถือล็อกอยู่ → 409 BUSY ไม่แตะบิล
        $held = Cache::lock('admin-app:mark-paid:'.$reading->id, 30);
        $this->assertTrue($held->get());
        $this->postJson("/api/admin/fortune/readings/{$reading->id}/mark-paid")
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'BUSY');
        $this->assertFalse((bool) $reading->fresh()->is_paid);
        $held->release();

        // ปล่อยล็อกแล้ว → ทำได้ · ล็อกถูกปล่อยหลังทำเสร็จ
        $this->postJson("/api/admin/fortune/readings/{$reading->id}/mark-paid")->assertOk();
        $this->assertTrue((bool) $reading->fresh()->is_paid);
        $probe = Cache::lock('admin-app:mark-paid:'.$reading->id, 1);
        $this->assertTrue($probe->get(), 'ล็อกถูกปล่อยแล้ว');
        $probe->release();

        // คำขอที่สองหลังจ่ายแล้ว → 422 (อ่านแถวใหม่หลังได้ล็อก)
        $this->postJson("/api/admin/fortune/readings/{$reading->id}/mark-paid")->assertStatus(422);
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
