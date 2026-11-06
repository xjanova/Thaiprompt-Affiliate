<?php

namespace Tests\Feature\Mlm;

use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\Order;
use App\Models\PlatformWallet;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * คืนเงินตอนกองเหลือน้อย (F32) + กันสถานะ cancelled/SMS-checker (F31) — ขั้น 2e
 */
class RefundPoolShortfallAndStatusGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Mail::fake();
        Queue::fake();
        Cache::flush();
    }

    protected function user(string $tag, float $balance = 0): User
    {
        $user = User::factory()->create(['name' => $tag, 'email' => $tag.'-'.Str::lower(Str::random(6)).'@test.local']);
        Wallet::create(['user_id' => $user->id, 'balance' => $balance, 'currency' => 'THB', 'status' => 'active']);

        return $user;
    }

    protected function admin(): User
    {
        $admin = $this->user('admin');
        $admin->forceFill(['role' => 'super_admin', 'is_super_admin' => true])->save();

        return $admin->fresh();
    }

    protected function order(User $buyer, array $attrs = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $buyer->id,
            'status' => 'paid',
            'payment_method' => 'promptpay',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'total_amount' => 1000,
        ], $attrs));
    }

    protected function balance(string $slug): float
    {
        return round((float) PlatformWallet::where('slug', $slug)->value('balance'), 2);
    }

    /** คอมของออเดอร์ที่จ่ายเข้ากระเป๋าไปแล้ว (ตัดเงินออกจากกองแบบที่ payApprovedCommissions ทำ) */
    protected function paidCommission(Order $order, User $earner, float $amount, MlmPlan $plan): MlmCommission
    {
        $member = MlmMember::create([
            'user_id' => $earner->id, 'mlm_plan_id' => $plan->id, 'member_code' => 'SF'.Str::random(6),
            'status' => 'active', 'is_qualified' => true, 'joined_at' => now()->subMonth(),
        ]);
        PlatformWallet::getMlmPoolWallet()->deductFunds($amount, 'mlm_commission_payout', 'MlmCommission', null);

        return MlmCommission::create([
            'mlm_member_id' => $member->id, 'mlm_plan_id' => $plan->id, 'user_id' => $earner->id,
            'source_type' => Order::class, 'source_id' => $order->id,
            'type' => 'unilevel_direct', 'level' => 1, 'pv_amount' => 1000, 'sales_amount' => 1000,
            'commission_amount' => $amount, 'status' => 'paid', 'paid_at' => now(),
        ]);
    }

    /**
     * F32: กองเก็บ 500 จ่ายคอม C 150 (ถอนหมด) + B 30 (เหลือ 10) แล้วคืนเงิน
     * → ย้อนกองเท่าที่เป็นเงินของออเดอร์นี้ (500 − หนี้ 170 = 330) shortfall 170 เท่ากับหนี้
     * และไม่หยิบเงินกองของออเดอร์อื่น (1,000) มาปิด · กระเป๋าอื่นที่เหลือน้อยย้อนเท่าที่มี
     */
    public function test_pool_reversal_takes_only_this_orders_money_and_records_real_shortfall(): void
    {
        $plan = MlmPlan::create(['name' => 'P', 'name_th' => 'แผน', 'slug' => 'p-'.Str::random(5), 'type' => 'unilevel', 'is_active' => true, 'is_default' => true]);
        $buyer = $this->user('buyer');
        $order = $this->order($buyer);
        $other = $this->order($this->user('other-buyer'));

        $pool = PlatformWallet::getMlmPoolWallet();
        $pool->addFunds(500, 'mlm_commission_pool', 'Order', $order->id);
        $pool->addFunds(1000, 'mlm_commission_pool', 'Order', $other->id);
        $vat = PlatformWallet::getVatWallet();
        $vat->addFunds(65, 'vat_collection', 'Order', $order->id);
        $vat->deductFunds(15, 'vat_remitted'); // VAT บางส่วนถูกนำส่งไปแล้ว

        $c = $this->user('upline-c', 0);     // ถอนไปหมดแล้ว
        $b = $this->user('upline-b', 10);    // ถอนไป 20 จาก 30
        $this->paidCommission($order, $c, 150, $plan);
        $this->paidCommission($order, $b, 30, $plan);
        $this->assertEqualsWithDelta(1320.0, $this->balance('mlm_pool'), 0.001);

        $report = app(RefundService::class)->processFullRefund($order, null, 'ทดสอบกองเหลือน้อย');

        $this->assertEqualsWithDelta(170.0, (float) WalletDebt::where('source_type', 'MlmClawback')->sum('remaining_amount'), 0.001);
        $this->assertEqualsWithDelta(1000.0, $this->balance('mlm_pool'), 0.001, 'กองต้องเหลือเงินของออเดอร์อื่นครบ 1,000');

        $poolRow = collect($report['platform_adjustments'])->firstWhere('original_sub_type', 'mlm_commission_pool');
        $this->assertEqualsWithDelta(330.0, $poolRow['reversed_amount'], 0.001);
        $this->assertEqualsWithDelta(170.0, $poolRow['shortfall'], 0.001, 'shortfall ต้องเท่าส่วนที่เป็นหนี้ ไม่ใช่ 500 ทั้งก้อน');

        $vatRow = collect($report['platform_adjustments'])->firstWhere('original_sub_type', 'vat_collection');
        $this->assertEqualsWithDelta(50.0, $vatRow['reversed_amount'], 0.001, 'ย้อนเท่าที่กระเป๋ามี');
        $this->assertEqualsWithDelta(15.0, $vatRow['shortfall'], 0.001);
        $this->assertEqualsWithDelta(0.0, $this->balance('vat'), 0.001);
        $this->assertEqualsWithDelta(1000.0, round((float) Wallet::where('user_id', $buyer->id)->value('balance'), 2), 0.001);
    }

    /**
     * F31: ออเดอร์ที่ยกเลิกแล้ว — ปุ่ม payment-status=paid ต้องถูกกัน (เดิมได้ cancelled+paid แบบออเดอร์ #29)
     */
    public function test_admin_cannot_mark_a_cancelled_order_paid(): void
    {
        $admin = $this->admin();
        $order = $this->order($this->user('buyer'), ['status' => 'cancelled', 'payment_status' => 'pending', 'paid_at' => null]);

        $this->actingAs($admin)->post(route('admin.ecommerce.orders.payment-status.update', $order), [
            'payment_status' => 'paid', 'reason' => 'ลูกค้าโอนมาหลังยกเลิก', 'payment_reference' => 'SLIP-LATE',
        ])->assertSessionHas('error');

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(0, \App\Models\PlatformTransaction::where('source_type', 'Order')->where('source_id', $order->id)->count());
    }

    /**
     * F31: ข้อมูลเก่า cancelled + paid ต้องคืนเงินผ่านปุ่ม "สถานะ: คืนเงิน" ปกติได้ (เดิม canBeRefunded = false)
     */
    public function test_legacy_cancelled_but_paid_order_can_be_refunded_with_the_normal_button(): void
    {
        $admin = $this->admin();
        $buyer = $this->user('buyer');
        $order = $this->order($buyer, ['status' => 'cancelled']);

        $this->actingAs($admin)->post(route('admin.ecommerce.orders.status.update', $order), [
            'status' => 'refunded', 'admin_notes' => 'คืนเงินออเดอร์ยกเลิกที่ค้างจ่าย',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertEqualsWithDelta(1000.0, round((float) Wallet::where('user_id', $buyer->id)->value('balance'), 2), 0.001);
    }

    /**
     * F31: SMS-checker ยืนยัน/ปฏิเสธได้เฉพาะออเดอร์ที่ยังรอชำระ
     */
    public function test_sms_checker_confirm_and_reject_only_touch_pending_orders(): void
    {
        $admin = $this->admin();
        $cancelled = $this->order($this->user('b1'), ['status' => 'cancelled', 'payment_status' => 'pending', 'paid_at' => null]);
        $paid = $this->order($this->user('b2'));

        $this->actingAs($admin)->post(route('admin.smschecker.order-confirm', $cancelled))->assertSessionHas('error');
        $this->assertSame(['cancelled', 'pending'], [$cancelled->fresh()->status, $cancelled->fresh()->payment_status]);

        $this->actingAs($admin)->post(route('admin.smschecker.order-reject', $paid))->assertSessionHas('error');
        $this->assertSame(['paid', 'paid'], [$paid->fresh()->status, $paid->fresh()->payment_status], 'ออเดอร์ที่จ่ายแล้วห้ามถูกตั้งเป็นยกเลิกโดยไม่คืนเงิน');

        $pending = $this->order($this->user('b3'), ['status' => 'pending', 'payment_status' => 'pending', 'paid_at' => null]);
        $this->actingAs($admin)->post(route('admin.smschecker.order-confirm', $pending))->assertSessionHas('success');
        $this->assertSame('paid', $pending->fresh()->payment_status);
    }
}
