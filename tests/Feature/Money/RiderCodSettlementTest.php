<?php

namespace Tests\Feature\Money;

use App\Models\EarningsLedger;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AccountDeletionService;
use App\Services\RefundService;
use App\Services\RiderEarningService;
use App\Services\SellerPayoutService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/**
 * ออเดอร์ร้านค้าที่ไรเดอร์เก็บเงินปลายทาง (COD) — เงินต้องเข้าระบบจริงก่อนจ่ายร้าน (รีวิว Wave-1)
 *
 * บั๊กเดิม: ส่งถึงแล้วตั้ง payment_status = paid ทันที → แบ่งเงินร้าน + cashback ทั้งที่ไรเดอร์ยังไม่นำส่ง
 * ไรเดอร์ถอน/โอนวอลเลตออกก่อนส่งของ → หักไม่ได้ แต่ร้านยังได้เงินหลังครบวันพัก = แพลตฟอร์มจ่ายเงินที่ไม่เคยได้
 *
 * ต้องใช้ MySQL — รันบน CI
 */
class RiderCodSettlementTest extends MoneyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
    }

    public function test_cod_order_is_paid_and_distributed_only_after_rider_remits(): void
    {
        [$order, $seller] = $this->makeCodRiderOrder();
        $rider = $this->makeRider(200);
        $job = $this->makeJob($order, $rider, 'completed');

        // ส่งถึงแล้ว → ออเดอร์ delivered แต่ยังไม่ได้เงิน
        $order->fresh()->onRiderJobStatusChanged($job, 'delivered');
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());

        // เคลียร์เงินไรเดอร์: หัก 140 − 32 = 108 → ออเดอร์จ่ายแล้ว + แบ่งเงินร้าน
        app(RiderEarningService::class)->settle($job);

        $this->assertNotNull($job->fresh()->cod_settled_at);
        $this->assertEqualsWithDelta(92.0, $this->walletBalance($rider->user), 0.001);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->where('user_id', $seller->id)->count());
    }

    public function test_rider_short_of_balance_keeps_order_unpaid_and_payout_blocked(): void
    {
        [$order] = $this->makeCodRiderOrder();
        $rider = $this->makeRider(10);
        $job = $this->makeJob($order, $rider, 'completed');
        $order->fresh()->onRiderJobStatusChanged($job, 'delivered');

        app(RiderEarningService::class)->settle($job);

        $this->assertNull($job->fresh()->cod_settled_at);
        $this->assertSame('pending', $order->fresh()->payment_status, 'ไรเดอร์ยังไม่นำส่ง → ห้ามตั้งจ่ายแล้ว');
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());

        // ต่อให้มีคนตั้ง paid เอง (ข้อมูลเก่า/แก้มือ) ก็ต้องไม่ปล่อยเงินร้านจนกว่าไรเดอร์นำส่ง
        Order::whereKey($order->id)->update([
            'payment_status' => 'paid',
            'paid_at' => now()->subDays(10),
            'delivered_at' => now()->subDays(10),
        ]);
        $payouts = app(SellerPayoutService::class);
        $this->assertFalse($payouts->isOrderReleasable($order->fresh()));

        $job->forceFill(['cod_settled_at' => now()])->save();
        $this->assertTrue($payouts->isOrderReleasable($order->fresh()));
    }

    public function test_rider_cannot_withdraw_or_transfer_money_reserved_for_cod(): void
    {
        [$order] = $this->makeCodRiderOrder();
        $rider = $this->makeRider(200);
        $this->makeJob($order, $rider, 'delivering');

        $this->assertEqualsWithDelta(108.0, RiderJob::codReserveForUser((int) $rider->user_id), 0.001);

        $wallets = app(WalletService::class);
        $from = Wallet::where('user_id', $rider->user_id)->firstOrFail();
        $to = $wallets->getOrCreateWallet($this->makeUser('ผู้รับโอน'));

        try {
            $wallets->transfer($from, $to, 150, '');
            $this->fail('ต้องโอนยอดที่ติดภาระ COD ไม่ได้');
        } catch (\Exception $e) {
            $this->assertStringContainsString('เก็บปลายทาง', $e->getMessage());
        }
        $this->assertEqualsWithDelta(200.0, $this->walletBalance($rider->user), 0.001);

        // ส่วนที่ไม่ติดภาระ (200 − 108 = 92) ยังโอนได้
        $wallets->transfer($from->fresh(), $to->fresh(), 50, '');
        $this->assertEqualsWithDelta(150.0, $this->walletBalance($rider->user), 0.001);

        // ผู้ใช้ทั่วไป (ไม่ใช่ไรเดอร์) ไม่มีภาระ
        $this->assertSame(0.0, RiderJob::codReserveForUser((int) $to->user_id));
    }

    public function test_account_deletion_is_blocked_while_cod_is_unsettled(): void
    {
        [$order] = $this->makeCodRiderOrder();
        $rider = $this->makeRider(0);
        $this->makeJob($order, $rider, 'completed');

        $codes = array_column(app(AccountDeletionService::class)->blockers($rider->user), 'code');

        $this->assertContains('UNSETTLED_COD', $codes);
    }

    public function test_refund_after_rider_delivery_withholds_the_delivery_fee_already_paid_out(): void
    {
        [$order] = $this->makePrepaidRiderOrder();
        $rider = $this->makeRider(0);
        $this->makeJob($order, $rider, 'completed', ['cod_amount' => 0]);

        $report = app(RefundService::class)->processFullRefund($order->fresh(), null, 'ลูกค้าคืนสินค้า');

        $this->assertEqualsWithDelta(40.0, (float) $report['rider_fee_withheld'], 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $report['customer_refund']['amount'], 0.001, '140 − ค่าส่งที่จ่ายไรเดอร์แล้ว 40');
        $this->assertEqualsWithDelta(100.0, (float) WalletTransaction::where('reference_type', 'Order')
            ->where('reference_id', $order->id)
            ->where('type', 'refund')
            ->sum('amount'), 0.001);
    }

    public function test_refund_cancels_rider_job_that_has_not_picked_up_yet(): void
    {
        [$order] = $this->makePrepaidRiderOrder();
        $rider = $this->makeRider(0);
        $job = $this->makeJob($order, $rider, 'accepted', ['cod_amount' => 0, 'completed_at' => null]);

        $report = app(RefundService::class)->processFullRefund($order->fresh(), null, 'ร้านของหมด');

        $this->assertSame('cancelled', $job->fresh()->status, 'ไรเดอร์ต้องไม่ไปส่งของที่คืนเงินแล้ว');
        $this->assertEqualsWithDelta(0.0, (float) $report['rider_fee_withheld'], 0.001);
        $this->assertEqualsWithDelta(140.0, (float) $report['customer_refund']['amount'], 0.001);
    }

    // =====================================================
    // fixtures
    // =====================================================

    /**
     * ออเดอร์ COD ส่งด้วยไรเดอร์: สินค้า 100 + ค่าส่ง 40 = 140 (ยังไม่จ่าย)
     *
     * @return array{0: Order, 1: User}
     */
    private function makeCodRiderOrder(): array
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ COD');
        $product = $this->makeProduct($seller, 100);

        $order = $this->makePaidOrder($buyer, [[$product, 1]], 40, [
            'status' => 'processing',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'paid_at' => null,
            'delivery_method' => 'rider',
        ]);

        return [$order, $seller];
    }

    /**
     * ออเดอร์จ่ายล่วงหน้าแล้ว ส่งด้วยไรเดอร์: 100 + ค่าส่ง 40
     *
     * @return array{0: Order, 1: User}
     */
    private function makePrepaidRiderOrder(): array
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 100);

        $order = $this->makePaidOrder($buyer, [[$product, 1]], 40, [
            'status' => 'processing',
            'payment_method' => 'wallet',
            'delivery_method' => 'rider',
        ]);

        return [$order, $seller];
    }

    private function makeRider(float $walletBalance): Rider
    {
        $user = $this->makeUser('ไรเดอร์');
        Wallet::where('user_id', $user->id)->update(['balance' => $walletBalance]);

        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ทดสอบ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'offline',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'approved_at' => now(),
        ])->save();

        return $rider->fresh();
    }

    /**
     * งานไรเดอร์ของออเดอร์ (ค่าส่ง 40 = ไรเดอร์ 32 + แพลตฟอร์ม 8, COD 140 ตามค่าเริ่มต้น)
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeJob(Order $order, Rider $rider, string $status, array $overrides = []): RiderJob
    {
        $job = new RiderJob;
        $job->forceFill(array_merge([
            'rider_id' => $rider->id,
            'job_type' => 'shop_delivery',
            'source_type' => $order->getMorphClass(),
            'source_id' => $order->id,
            'title' => 'ส่งสินค้า '.$order->order_number,
            'pickup_address' => 'ร้านทดสอบ สีลม',
            'pickup_latitude' => 13.7291,
            'pickup_longitude' => 100.5210,
            'delivery_address' => 'บ้านลูกค้า บางรัก',
            'delivery_latitude' => 13.7300,
            'delivery_longitude' => 100.5300,
            'base_fee' => 30,
            'distance_fee' => 10,
            'total_fee' => 40,
            'rider_earnings' => 32,
            'platform_fee' => 8,
            'cod_amount' => 140,
            'status' => $status,
            'accepted_at' => now()->subHour(),
            'completed_at' => $status === 'completed' ? now() : null,
        ], $overrides))->save();

        return $job->fresh();
    }
}
