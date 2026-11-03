<?php

namespace Tests\Feature\Fortune;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\Notification;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Fortune\FortuneStripeService;
use App\Services\FortuneCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * F12: คืนเงินบัตร Stripe ของบิลดูดวง
 *
 * - คืนเต็มจำนวน (webhook charge.refunded / ปุ่มคืนเงินหลังบ้าน) → ยกเลิกการอนุมัติบิลผ่าน voidApproval()
 *   ตัวเดียวกับปุ่ม void ของแอดมิน → ค่าแนะนำ L1/L2 ถูกดึงคืนแบบเดียวกับ void ด้วยมือ
 * - event ซ้ำ (Stripe ส่งซ้ำ / ปุ่ม + webhook) ต้องไม่ดึงคืนสองรอบ
 * - คืนบางส่วน หรือ charge.dispute.* → ไม่ยกเลิกอัตโนมัติ บันทึกไว้ในบิล + แจ้งแอดมินในระบบ (ไม่ส่ง LINE)
 */
class FortuneStripeRefundVoidTest extends TestCase
{
    use BuildsFortuneCommissionChain;
    use RefreshDatabase;

    protected User $sponsorUser;

    protected User $grandUser;

    protected FortuneReading $bill;

    protected \App\Models\MlmMember $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::fake();

        $this->setUpFortuneChain([
            'stripe_secret_key' => 'sk_test_x',
            'stripe_webhook_secret' => 'whsec_x',
            'enable_stripe_foreign_fallback' => true,
        ]);

        [$this->grandUser, $grand] = $this->makeChainMember();
        [$this->sponsorUser, $sponsor] = $this->makeChainMember($grand);
        [$buyerUser, $this->buyer] = $this->makeChainMember($sponsor);
        $buyer = $this->buyer;

        // บิล Celtic จ่ายด้วยบัตรต่างประเทศ 99 + 15 = 114
        $this->bill = $this->makeBill($buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 114, [
            'service_fee' => 15,
            'payment_method' => FortuneReading::PAYMENT_METHOD_STRIPE,
            'stripe_session_id' => 'cs_test_'.uniqid(),
            'stripe_payment_intent_id' => 'pi_test_'.uniqid(),
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
        ]);

        app(FortuneCommissionService::class)->distributeCommissions($this->bill, $buyer, $this->settings);

        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(2.0, $this->balanceOf($this->grandUser->id));
    }

    private function stripe(): FortuneStripeService
    {
        return new FortuneStripeService($this->settings->fresh());
    }

    private function chargeRefunded(int $amountRefunded, bool $fully): array
    {
        return $this->stripe()->handleWebhookEvent([
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'id' => 'ch_test_1',
                'payment_intent' => $this->bill->stripe_payment_intent_id,
                'amount' => 11400,
                'amount_refunded' => $amountRefunded,
                'refunded' => $fully,
            ]],
        ]);
    }

    public function test_full_refund_webhook_voids_the_bill_and_claws_back_commissions(): void
    {
        $this->chargeRefunded(11400, true);

        $bill = $this->bill->fresh();
        $this->assertFalse((bool) $bill->is_paid, 'คืนเงินเต็มจำนวนแล้ว บิลต้องไม่นับเป็นจ่ายแล้ว');
        $this->assertTrue((bool) $bill->getConversationState('approval_voided'), 'ต้องใช้ทาง voidApproval เดียวกับแอดมิน');
        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $bill->id)
            ->where('status', FortuneCommission::STATUS_PAID)->count());
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(0.0, $this->balanceOf($this->grandUser->id));
    }

    public function test_repeated_full_refund_events_claw_back_only_once(): void
    {
        $this->chargeRefunded(11400, true);
        $txAfterFirst = WalletTransaction::count();

        // Stripe ส่ง event ซ้ำ
        $second = $this->chargeRefunded(11400, true);

        $this->assertTrue($second['handled'] ?? false);
        $this->assertSame($txAfterFirst, WalletTransaction::count(), 'event ซ้ำต้องไม่ดึงคืนซ้ำ');
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(0.0, $this->balanceOf($this->grandUser->id));
    }

    public function test_partial_refund_is_recorded_for_review_and_not_voided(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->chargeRefunded(5000, false);
        // event เดิมซ้ำ → บันทึกซ้ำไม่ได้
        $this->chargeRefunded(5000, false);

        $bill = $this->bill->fresh();
        $this->assertTrue((bool) $bill->is_paid, 'คืนบางส่วนห้ามยกเลิกบิลอัตโนมัติ');
        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));

        $reviews = $bill->getConversationState('stripe_refund_reviews');
        $this->assertIsArray($reviews);
        $this->assertCount(1, $reviews);
        $this->assertSame('partial_refund', $reviews[0]['kind']);
        $this->assertEquals(50.0, $reviews[0]['amount']);

        $this->assertSame(1, Notification::where('user_id', $admin->id)->count(), 'แอดมินต้องเห็นเคสในกล่องแจ้งเตือน');
    }

    public function test_dispute_is_recorded_for_review_and_not_voided(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $result = $this->stripe()->handleWebhookEvent([
            'type' => 'charge.dispute.created',
            'data' => ['object' => [
                'id' => 'dp_test_1',
                'charge' => 'ch_test_1',
                'payment_intent' => $this->bill->stripe_payment_intent_id,
                'amount' => 11400,
                'status' => 'needs_response',
                'reason' => 'fraudulent',
            ]],
        ]);

        $this->assertTrue($result['handled'] ?? false);
        $bill = $this->bill->fresh();
        $this->assertTrue((bool) $bill->is_paid);
        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $reviews = $bill->getConversationState('stripe_refund_reviews');
        $this->assertSame('dispute', $reviews[0]['kind'] ?? null);
        $this->assertSame(1, Notification::where('user_id', $admin->id)->count());
    }

    public function test_admin_full_refund_button_voids_the_bill(): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response(['id' => 're_test_1', 'amount' => 11400, 'status' => 'succeeded']),
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.fortune.billing.stripe-refund', $this->bill), ['reason' => 'ลูกค้าขอคืน'])
            ->assertRedirect();

        $bill = $this->bill->fresh();
        $this->assertFalse((bool) $bill->is_paid);
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(0.0, $this->balanceOf($this->grandUser->id));

        // webhook charge.refunded ที่ตามมาทีหลัง ต้องไม่ดึงซ้ำ
        $txCount = WalletTransaction::count();
        $this->chargeRefunded(11400, true);
        $this->assertSame($txCount, WalletTransaction::count());
    }

    public function test_admin_partial_refund_button_does_not_void(): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response(['id' => 're_test_2', 'amount' => 3000, 'status' => 'succeeded']),
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.fortune.billing.stripe-refund', $this->bill), ['amount' => 30, 'reason' => 'คืนบางส่วน'])
            ->assertRedirect();

        $bill = $this->bill->fresh();
        $this->assertTrue((bool) $bill->is_paid);
        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame('partial_refund', $bill->getConversationState('stripe_refund_reviews')[0]['kind'] ?? null);
    }

    /**
     * คืนเต็ม → void → ลูกค้าจ่ายใหม่ (QR) แอดมินอนุมัติ → Stripe ส่ง event คืนเงินเดิมซ้ำ
     * ต้องไม่ void/ดึงค่าแนะนำของการจ่ายรอบใหม่ (payment_intent นี้คืนไปแล้วครั้งเดียว)
     */
    public function test_retried_refund_event_after_reapproval_does_not_claw_again(): void
    {
        $this->chargeRefunded(11400, true);
        $this->assertFalse((bool) $this->bill->fresh()->is_paid);

        $this->bill->fresh()->forceFill(['is_paid' => true, 'paid_at' => now(), 'amount_paid' => 99.42, 'service_fee' => 0])->save();
        app(FortuneCommissionService::class)->distributeCommissions($this->bill->fresh(), $this->buyer, $this->settings->fresh());
        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));

        $this->chargeRefunded(11400, true);   // Stripe retry

        $this->assertTrue((bool) $this->bill->fresh()->is_paid, 'การจ่ายรอบใหม่ต้องไม่ถูกยกเลิก');
        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(2.0, $this->balanceOf($this->grandUser->id));
    }

    /** คืนเต็มขณะงาน AI กำลังสร้างคำทำนาย → ยัง void (เงินคืนแล้ว) + บันทึกให้แอดมินตรวจ */
    public function test_full_refund_during_generation_voids_and_flags_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Cache::put(\App\Services\CelticCrossService::generationInFlightKey($this->bill->id), 'tok', 300);

        $this->chargeRefunded(11400, true);

        $bill = $this->bill->fresh();
        $this->assertFalse((bool) $bill->is_paid);
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
        $reviews = $bill->getConversationState('stripe_refund_reviews');
        $this->assertSame('full_refund_during_generation', $reviews[0]['kind'] ?? null);
        $this->assertSame(1, Notification::where('user_id', $admin->id)->count());
    }
}
