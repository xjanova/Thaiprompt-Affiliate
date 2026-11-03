<?php

namespace Tests\Feature\Fortune;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\User;
use App\Services\FortuneCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * F23 (M13): ค่าแนะนำดูดวงต้องผูกกับยอดที่รับจริง
 *
 * - จ่ายเฉพาะเมื่อ amount_paid ≥ ราคาแพคเกจของบิล (เผื่อโอนขาดได้ไม่เกิน fuzzy_underpay_max_baht ที่ระบบจับคู่ยอมรับอยู่แล้ว)
 * - ค่าแนะนำ L1 + L2 รวมของบิลหนึ่งใบห้ามเกินยอดที่รับ
 * - แอดมินมาร์คจ่ายด้วยยอดต่ำกว่าราคา → ใช้กฎเดียวกัน
 */
class FortuneCommissionPaidAmountTest extends TestCase
{
    use BuildsFortuneCommissionChain;
    use RefreshDatabase;

    protected User $sponsorUser;

    protected User $grandUser;

    protected User $buyerUser;

    protected \App\Models\MlmMember $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::fake();
        $this->setUpFortuneChain(['fuzzy_underpay_max_baht' => 1.00]);

        [$this->grandUser, $grand] = $this->makeChainMember();
        [$this->sponsorUser, $sponsor] = $this->makeChainMember($grand);
        [$this->buyerUser, $this->buyer] = $this->makeChainMember($sponsor);
    }

    private function distribute(FortuneReading $bill): void
    {
        app(FortuneCommissionService::class)->distributeCommissions($bill, $this->buyer, $this->settings->fresh());
    }

    private function paidTotal(FortuneReading $bill): float
    {
        return (float) FortuneCommission::where('fortune_reading_id', $bill->id)
            ->where('status', FortuneCommission::STATUS_PAID)->sum('amount');
    }

    public function test_celtic_bill_paid_below_package_price_pays_no_commission(): void
    {
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 0.50);
        $this->distribute($bill);

        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $bill->id)->count());
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
    }

    public function test_underpay_within_matcher_tolerance_still_pays(): void
    {
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 98.00);
        $this->distribute($bill);

        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(2.0, $this->balanceOf($this->grandUser->id));
    }

    public function test_full_price_with_random_satang_pays_normally(): void
    {
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 99.37);
        $this->distribute($bill);

        $this->assertSame(12.0, $this->paidTotal($bill));
    }

    public function test_total_commission_never_exceeds_amount_paid(): void
    {
        // บิลพื้นฐาน reading_price = 1.00 (ค่าจริงบน prod) — อัตราเดิม L1 10 / L2 1
        $this->settings->forceFill(['reading_price' => 1.00])->save();
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_BASIC, 1.00);
        $this->distribute($bill);

        $this->assertLessThanOrEqual(1.00, $this->paidTotal($bill), 'ค่าแนะนำรวมต้องไม่เกินยอดที่รับ');
        // L1 ถูกตัดเหลือเท่ายอดรับพอดี และไม่เหลือที่ให้ L2
        $this->assertSame(1.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(0.0, $this->balanceOf($this->grandUser->id));
        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $bill->id)->where('level', 2)->count());
    }

    /** ขอบเขตเผื่อโอนขาด 1.00: Celtic 99 → 97.99 ไม่จ่าย / 98.00 และ 99.00 จ่าย */
    public function test_underpay_tolerance_boundary(): void
    {
        foreach ([[97.99, 0.0], [98.00, 10.0], [99.00, 10.0]] as [$paid, $expected]) {
            $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, (float) $paid);
            $this->distribute($bill);

            $this->assertSame($expected, (float) FortuneCommission::where('fortune_reading_id', $bill->id)
                ->where('level', 1)->where('status', FortuneCommission::STATUS_PAID)->sum('amount'), "ยอดรับ {$paid}");
        }
    }

    /** บิลโอนขาด 39 แล้วโอนเติม 60.37 ผ่านบิลโอนเติม (UPA ตรงเป๊ะ) — amount_paid = แค่ยอดก้อนหลัง แต่ลูกค้าจ่ายครบ */
    public function test_topup_bill_paid_in_two_transfers_still_pays_commission(): void
    {
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 60.37, [
            'partial_paid_total' => 39.00, 'partial_target_total' => 99.00, 'partial_rounds' => 1,
        ]);
        $this->distribute($bill);

        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(2.0, $this->balanceOf($this->grandUser->id));
    }

    /** เส้นสลิป: ยอดสะสมครบแล้ว partial_paid_total รวมก้อนสุดท้ายแล้ว + ก้อนเติมเล็ก — ห้ามโดนเพดานตัดเหลือยอดก้อนหลัง */
    public function test_small_topup_is_not_capped_to_last_transfer(): void
    {
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 5.37, [
            'partial_paid_total' => 99.00, 'partial_target_total' => 99.00, 'partial_rounds' => 2,
        ]);
        $this->distribute($bill);

        $this->assertSame(12.0, $this->paidTotal($bill));
    }

    /** แอดมินสลับ Deep (จ่าย 39 แล้ว) → Celtic แล้วเก็บส่วนต่าง: partial_paid_total = 39, amount_paid = ส่วนต่าง */
    public function test_admin_switch_charge_bill_pays_commission(): void
    {
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 60.41, [
            'partial_paid_total' => 39.00,
        ]);
        $this->distribute($bill);

        $this->assertSame(10.0, $this->balanceOf($this->sponsorUser->id));
        $this->assertSame(2.0, $this->balanceOf($this->grandUser->id));
    }

    /** บิลโอนขาดที่ยอดรวมยังไม่ถึงราคา (แอดมินมาร์คจ่ายเศษ) → ไม่จ่าย */
    public function test_partial_bill_still_short_pays_no_commission(): void
    {
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 0.50, [
            'partial_paid_total' => 39.00, 'partial_target_total' => 99.00, 'partial_rounds' => 1,
        ]);
        $this->distribute($bill);

        $this->assertSame(0, FortuneCommission::where('fortune_reading_id', $bill->id)->count());
    }

    public function test_admin_mark_paid_below_price_pays_no_commission(): void
    {
        $line = 'U'.md5(uniqid('', true));
        $this->buyerUser->forceFill(['line_user_id' => $line])->save();
        $bill = $this->makeBill($this->buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 99.72, [
            'is_paid' => false, 'paid_at' => null,
            'facebook_user_id' => $line, 'platform_user_id' => $line, 'platform' => 'line',
            'conversation_status' => FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']), ['admin']);
        $this->postJson("/api/admin/fortune/readings/{$bill->id}/mark-paid", ['amount' => 0.5])->assertOk();

        $this->assertTrue((bool) $bill->fresh()->is_paid);
        $this->assertSame(0.0, $this->paidTotal($bill));
        $this->assertSame(0.0, $this->balanceOf($this->sponsorUser->id));
    }
}
