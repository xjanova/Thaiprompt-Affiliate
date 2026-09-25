<?php

namespace Tests\Feature\Money;

use App\Models\EarningsLedger;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Services\OrderDistributionService;
use App\Services\RefundService;
use App\Services\SellerPayoutService;

/**
 * คืนเงินออเดอร์เต็มจำนวน (audit G4)
 *
 * - ลูกค้าได้เงินคืนเข้า wallet จริง (type refund, มี balance_before)
 * - ลูกค้าที่ยังไม่มี wallet → สร้างให้
 * - รายได้ผู้ขายที่ยังไม่จ่าย → ยกเลิก + ดึงเงินพักออกจาก escrow / จ่ายแล้ว → หักคืนจาก wallet ผู้ขาย ไม่พอ = หนี้
 * - คืนซ้ำ = คืนครั้งเดียว / ออเดอร์ที่ยังไม่จ่าย = คืนไม่ได้
 */
class RefundFlowTest extends MoneyTestCase
{
    public function test_refund_before_payout_returns_money_and_reverses_all_allocations(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 400);
        $order = $this->makePaidOrder($buyer, [[$product, 1]], 40);

        app(OrderDistributionService::class)->processOrderDistribution($order);
        $this->assertEqualsWithDelta(40.00, $this->platformBalance('fee'), 0.001);
        $this->assertEqualsWithDelta(400.00, $this->platformBalance(SellerPayoutService::ESCROW_WALLET_SLUG), 0.001);

        $report = app(RefundService::class)->processFullRefund($order->fresh(), null, 'สินค้าหมด');

        $this->assertEqualsWithDelta(440.00, $this->walletBalance($buyer), 0.001);
        $tx = WalletTransaction::where('reference_type', 'Order')->where('reference_id', $order->id)->where('type', 'refund')->sole();
        $this->assertEqualsWithDelta(0.00, (float) $tx->balance_before, 0.001);
        $this->assertEqualsWithDelta(440.00, (float) $tx->balance_after, 0.001);

        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->sole();
        $this->assertSame(EarningsLedger::STATUS_CANCELLED, $ledger->status);

        $this->assertEqualsWithDelta(0.00, $this->platformBalance('fee'), 0.001);
        $this->assertEqualsWithDelta(0.00, $this->platformBalance(SellerPayoutService::ESCROW_WALLET_SLUG), 0.001);

        $order->refresh();
        $this->assertSame('refunded', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertEqualsWithDelta(440.00, $report['summary']['total_customer_refund'], 0.001);
    }

    public function test_refund_is_idempotent(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $order = $this->makePaidOrder($buyer, [[$this->makeProduct($seller, 100), 1]]);
        app(OrderDistributionService::class)->processOrderDistribution($order);

        $service = app(RefundService::class);
        $service->processFullRefund($order->fresh(), null, 'ครั้งแรก');
        $second = $service->processFullRefund($order->fresh(), null, 'กดซ้ำ');

        $this->assertTrue($second['already_refunded'] ?? false);
        $this->assertEqualsWithDelta(100.00, $this->walletBalance($buyer), 0.001);
        $this->assertSame(1, WalletTransaction::where('reference_type', 'Order')->where('reference_id', $order->id)->where('type', 'refund')->count());
    }

    public function test_customer_without_wallet_gets_one_created(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = \App\Models\User::factory()->create(['name' => 'ลูกค้าไม่มีกระเป๋า']);
        $order = $this->makePaidOrder($buyer, [[$this->makeProduct($seller, 150), 1]]);

        app(RefundService::class)->processFullRefund($order, null, 'ลูกค้ายกเลิก');

        $this->assertEqualsWithDelta(150.00, round((float) Wallet::where('user_id', $buyer->id)->value('balance'), 2), 0.001);
    }

    public function test_unpaid_order_cannot_be_refunded_and_stays_unchanged(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $order = $this->makePaidOrder($buyer, [[$this->makeProduct($seller, 150), 1]], 0, [
            'status' => 'pending',
            'payment_status' => 'pending',
            'paid_at' => null,
        ]);

        try {
            app(RefundService::class)->processFullRefund($order, null, 'ทดสอบ');
            $this->fail('ต้องคืนเงินไม่ได้');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('ยังไม่ได้ชำระเงิน', $e->getMessage());
        }

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertEqualsWithDelta(0.00, $this->walletBalance($buyer), 0.001);
    }

    public function test_refund_after_seller_was_paid_claws_back_and_creates_debt_for_shortfall(): void
    {
        $seller = $this->makeSeller(10.0);
        $buyer = $this->makeUser('ผู้ซื้อ');
        $order = $this->makePaidOrder($buyer, [[$this->makeProduct($seller, 1000), 1]]);
        app(OrderDistributionService::class)->processOrderDistribution($order);

        // ส่งถึงเมื่อ 5 วันก่อน (พักเงิน 3 วัน) → จ่ายเข้ากระเป๋าผู้ขาย 900
        $order->forceFill(['status' => 'delivered', 'delivered_at' => now()->subDays(5)])->saveQuietly();
        $release = app(SellerPayoutService::class)->releaseEligible();
        $this->assertSame(1, $release['credited']);
        $this->assertEqualsWithDelta(900.00, $this->walletBalance($seller), 0.001);

        // ผู้ขายถอนไปแล้วบางส่วน เหลือ 600
        Wallet::where('user_id', $seller->id)->update(['balance' => 600]);

        $report = app(RefundService::class)->processFullRefund($order->fresh(), null, 'ลูกค้าได้ของเสีย');

        $this->assertEqualsWithDelta(0.00, $this->walletBalance($seller), 0.001);
        $debt = WalletDebt::where('user_id', $seller->id)->where('source_type', 'SellerClawback')->sole();
        $this->assertEqualsWithDelta(300.00, (float) $debt->remaining_amount, 0.001);
        $this->assertEqualsWithDelta(1000.00, $this->walletBalance($buyer), 0.001);
        $this->assertSame(EarningsLedger::STATUS_CANCELLED, EarningsLedger::where('source_id', $order->id)->where('source_type', 'Order')->sole()->status);
        $this->assertEqualsWithDelta(900.00, $report['summary']['total_seller_clawback'], 0.001);
    }
}
