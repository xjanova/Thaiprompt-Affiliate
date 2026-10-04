<?php

namespace Tests\Feature\RiderR2;

use App\Models\EarningsLedger;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Order;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Services\FreshMarketService;
use App\Services\OrderDistributionService;
use App\Services\RefundService;
use App\Services\RiderEarningService;
use App\Services\SellerPayoutService;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;

/**
 * 💸 ไรเดอร์รอบ 2 (fix lane money — L1) — คืนเงินหลังไรเดอร์ส่งสำเร็จ (ต้องใช้ MySQL)
 *
 * ไรเดอร์ได้ค่าส่งเต็ม + โบนัสร้านไปแล้ว (ไม่เรียกคืนจากไรเดอร์) → นอกจากรายได้สุทธิของร้าน
 * ต้องเรียกคืน "ต้นทุนไรเดอร์ที่ร้านเลือกจ่าย" (โบนัส + ค่าส่งที่ออกให้) จากร้านด้วย ไม่พอ = หนี้ (WalletDebt)
 * แพลตฟอร์มต้องเหลือเท่าส่วนแบ่งค่าส่งของตัวเองพอดี — ไม่รับภาระแทนร้าน
 *
 * ครอบคลุม: ร้านค้า (รายได้ยังพักอยู่ / จ่ายร้านไปแล้ว + wallet ร้านไม่พอ → หนี้) ทั้งผ่าน Order::cancel และ RefundService
 *         · งานไรเดอร์ยังไม่สำเร็จ = ไม่เรียกคืน · ตลาดสด: แอดมินยกเลิกจาก "ส่งถึงแล้ว" (พอ / ไม่พอ → หนี้) + กดซ้ำไม่หักซ้ำ
 */
#[Group('rider-r2')]
class RiderRefundClawbackTest extends HandoverTestCase
{
    // =====================================================
    // ร้านค้า
    // =====================================================

    public function test_refund_after_completed_delivery_claws_back_rider_costs_from_seller_while_rider_keeps_pay(): void
    {
        // สินค้า 100 (GP 10) · ร้านส่งฟรี (ออกค่าส่ง 40) + โบนัส 15 · ผู้ซื้อจ่าย 100
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 15, subsidy: 40, shipping: 0);
        Wallet::where('user_id', $seller->id)->update(['balance' => 100]);
        $job = $this->completeDelivery($order, shopBonus: 15);

        // ร้าน: 100 − GP 10 − ค่าส่ง 40 − โบนัส 15 = 35 (ยังพักอยู่ — ส่งแบบปุ่มเดิม ยังไม่ครบเวลาพักเงิน)
        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->firstOrFail();
        $this->assertSame(EarningsLedger::STATUS_PENDING, $ledger->status);
        $this->assertEqualsWithDelta(35.0, (float) $ledger->net_amount, 0.001);
        $this->assertEqualsWithDelta(47.0, $this->riderEarningCredits($job), 0.001, 'ไรเดอร์ได้ 32 + โบนัส 15');

        $before = $this->systemWallets();

        $result = $order->fresh()->cancel('สินค้าชำรุด คืนเงินลูกค้า', (int) User::factory()->create(['role' => 'admin'])->id, 'admin');
        $this->assertTrue($result['refunded']);

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertEqualsWithDelta(100.0, $this->walletOf($buyer), 0.001, 'คืนค่าสินค้าเต็ม (ผู้ซื้อไม่ได้จ่ายค่าส่ง)');
        $this->assertSame(EarningsLedger::STATUS_CANCELLED, $ledger->fresh()->status, 'รายได้ที่ยังพักอยู่ถูกยกเลิก');
        $this->assertEqualsWithDelta(45.0, $this->walletOf($seller), 0.001, 'เรียกคืนต้นทุนไรเดอร์ 55 จากร้าน');
        $this->assertEqualsWithDelta(47.0, $this->riderEarningCredits($job), 0.001, 'ไรเดอร์ไม่ถูกเรียกเงินคืน');
        $this->assertSame(0, WalletTransaction::where('reference_type', 'rider_job')->where('reference_id', $job->id)->where('type', '!=', 'deposit')->count());

        $clawback = WalletTransaction::where('reference_type', RefundService::WALLET_REF_RIDER_COST)->where('reference_id', $order->id)->firstOrFail();
        $this->assertEqualsWithDelta(55.0, (float) $clawback->amount, 0.001);
        $this->assertSame((int) $seller->id, (int) $clawback->user_id);
        $this->assertSame(0, WalletDebt::where('source_type', RefundService::DEBT_SOURCE_RIDER_COST)->count());

        // เงินในวอลเลตผู้ใช้ทั้งหมด (ผู้ซื้อ + ร้าน + ไรเดอร์) เปลี่ยน = ผู้ซื้อได้คืน 100 − ร้านจ่ายคืน 55
        // ⇒ แพลตฟอร์มเหลือแค่ส่วนแบ่งค่าส่ง 8 จากเงิน 100 ที่ผู้ซื้อจ่าย (100 − 47 − 100 + 55 = 8) ไม่ขาดทุน
        $this->assertEqualsWithDelta($before + 100.0 - 55.0, $this->systemWallets(), 0.001);
        $this->assertPlatformKeepsOnlyItsDeliveryShare(paid: 100.0, refunded: 100.0, riderPaid: 47.0, sellerNet: 0.0, clawedFromSeller: 55.0, platformShare: 8.0);
    }

    public function test_refund_after_seller_was_paid_and_wallet_is_short_records_rider_cost_debt(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 15, subsidy: 40, shipping: 0);
        Wallet::where('user_id', $seller->id)->update(['balance' => 20]);
        $job = $this->completeDelivery($order, shopBonus: 15);

        // ครบเวลาพักเงิน → โอนรายได้ 35 ให้ร้าน (wallet ร้าน 55)
        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->firstOrFail();
        $released = app(SellerPayoutService::class)->releaseLedger((int) $ledger->id, now()->addDays(4));
        $this->assertSame('credited', $released['status']);
        $this->assertEqualsWithDelta(55.0, $this->walletOf($seller), 0.001);

        $report = app(RefundService::class)->processFullRefund($order->fresh(), null, 'ลูกค้าไม่ได้รับของตามรูป');

        // ร้าน: หักคืนรายได้ 35 ก่อน (เหลือ 20) แล้วต้นทุนไรเดอร์ 55 → หักได้ 20 + หนี้ 35
        $this->assertEqualsWithDelta(0.0, $this->walletOf($seller), 0.001);
        $this->assertEqualsWithDelta(55.0, $report['summary']['total_rider_cost_clawback'], 0.001);
        $row = $report['rider_cost_clawback'][0];
        $this->assertSame((int) $seller->id, $row['user_id']);
        $this->assertEqualsWithDelta(20.0, $row['deducted_from_wallet'], 0.001);
        $this->assertNotNull($row['debt_id']);
        $this->assertContains($row['debt_id'], $report['debts_created']);

        $debt = WalletDebt::findOrFail($row['debt_id']);
        $this->assertSame(RefundService::DEBT_SOURCE_RIDER_COST, $debt->source_type);
        $this->assertSame((int) $order->id, (int) $debt->source_id);
        $this->assertSame((int) $seller->id, (int) $debt->user_id);
        $this->assertEqualsWithDelta(35.0, (float) $debt->remaining_amount, 0.001);
        $this->assertSame(WalletDebt::STATUS_ACTIVE, $debt->status);

        // ไรเดอร์ยังได้ค่าส่ง + โบนัสครบ · ผู้ซื้อได้คืนค่าสินค้า
        $this->assertEqualsWithDelta(47.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(100.0, $this->walletOf($buyer), 0.001);

        // รายงานคืนเงิน + สถิติเห็นหนี้ชนิดใหม่
        $refundReport = app(RefundService::class)->getRefundReport($order->fresh());
        $this->assertTrue(collect($refundReport['debts']['items'])
            ->contains(fn ($d) => $d['type'] === RefundService::DEBT_SOURCE_RIDER_COST && (float) $d['remaining'] === 35.0));
        $this->assertGreaterThanOrEqual(35.0, app(RefundService::class)->getRefundStats()['debts_pending']);

        // คืนเงินซ้ำ → ไม่หักซ้ำ ไม่สร้างหนี้ซ้ำ
        $again = app(RefundService::class)->processFullRefund($order->fresh(), null, 'กดซ้ำ');
        $this->assertTrue($again['already_refunded'] ?? false);
        $this->assertSame(1, WalletDebt::where('source_type', RefundService::DEBT_SOURCE_RIDER_COST)->count());
    }

    public function test_refund_without_a_completed_rider_job_does_not_charge_the_seller_rider_costs(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 15, subsidy: 40, shipping: 0);
        Wallet::where('user_id', $seller->id)->update(['balance' => 100]);
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'delivering', ['shop_bonus' => 15, 'handover_required' => false]);

        $report = app(RefundService::class)->processFullRefund($order->fresh(), null, 'ยกเลิกก่อนส่งถึง');

        $this->assertSame('failed', $job->fresh()->status, 'ไรเดอร์ยังไม่ส่งถึง → ไม่ได้ค่าส่ง');
        $this->assertSame([], $report['rider_cost_clawback']);
        $this->assertEqualsWithDelta(100.0, $this->walletOf($seller), 0.001);
        $this->assertEqualsWithDelta(100.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(0.0, $this->riderEarningCredits($job));
    }

    // =====================================================
    // ตลาดสด: แอดมินยกเลิกจาก "ส่งถึงแล้ว"
    // =====================================================

    public function test_fresh_market_admin_cancel_from_delivered_claws_back_rider_costs(): void
    {
        [$order, $buyer, $sellerUser] = $this->deliveredFreshMarketOrder(sellerBalance: 50);
        $job = RiderJob::findOrFail($order->rider_job_id);
        $riderPaid = $this->riderEarningCredits($job);
        $this->assertEqualsWithDelta((float) $job->rider_earnings + 12, $riderPaid, 0.001);
        $paid = $order->walletPaidAmount();

        $admin = User::factory()->create(['role' => 'admin']);
        $cancelled = (new FreshMarketService)->cancelOrder($order->fresh(), 'ของเสียหาย คืนเงินลูกค้า', 'admin', $admin);

        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $cancelled->order_status);
        $this->assertSame('refunded', $cancelled->escrow_status);
        $this->assertEqualsWithDelta(1000.0 - $paid + 100.0, $this->walletOf($buyer), 0.001, 'คืนค่าสินค้า (ผู้ซื้อไม่ได้จ่ายค่าส่ง)');

        // ร้านออกค่าส่ง 30 + โบนัส 12 = 42 → หักจาก wallet ร้าน 50 เหลือ 8
        $costs = round((float) $order->delivery_subsidy_amount + (float) $order->rider_bonus_amount, 2);
        $this->assertEqualsWithDelta(42.0, $costs, 0.001);
        $this->assertEqualsWithDelta(8.0, $this->walletOf($sellerUser), 0.001);
        $this->assertEqualsWithDelta($riderPaid, $this->riderEarningCredits($job), 0.001, 'ไรเดอร์ไม่ถูกเรียกเงินคืน');
        $this->assertSame(0, WalletDebt::where('source_type', FreshMarketService::DEBT_SOURCE_RIDER_COST)->count());

        $history = collect($cancelled->status_history ?? [])->last();
        $this->assertEqualsWithDelta(42.0, (float) data_get($history, 'meta.rider_cost_clawback.amount'), 0.001);

        // กดยกเลิกซ้ำ → ไม่หักซ้ำ
        (new FreshMarketService)->cancelOrder($cancelled->fresh(), 'กดซ้ำ', 'admin', $admin);
        $this->assertEqualsWithDelta(8.0, $this->walletOf($sellerUser), 0.001);
        $this->assertSame(1, WalletTransaction::where('reference_type', FreshMarketService::REF_RIDER_COST_CLAWBACK)->where('reference_id', $order->id)->count());
    }

    public function test_fresh_market_admin_cancel_records_debt_when_seller_wallet_is_short(): void
    {
        [$order, $buyer, $sellerUser] = $this->deliveredFreshMarketOrder(sellerBalance: 10);

        (new FreshMarketService)->cancelOrder($order->fresh(), 'คืนเงินลูกค้า', 'admin', User::factory()->create(['role' => 'admin']));

        $this->assertEqualsWithDelta(0.0, $this->walletOf($sellerUser), 0.001);
        $debt = WalletDebt::where('source_type', FreshMarketService::DEBT_SOURCE_RIDER_COST)->where('source_id', $order->id)->firstOrFail();
        $this->assertSame((int) $sellerUser->id, (int) $debt->user_id);
        $this->assertEqualsWithDelta(32.0, (float) $debt->remaining_amount, 0.001, 'ต้นทุน 42 − หักได้ 10 = หนี้ 32');
        $this->assertSame(WalletDebt::STATUS_ACTIVE, $debt->status);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * ไรเดอร์ส่งสำเร็จแบบปุ่มเดิม (งาน completed + เคลียร์เงินไรเดอร์) แล้วแบ่งเงินออเดอร์
     */
    private function completeDelivery(Order $order, float $shopBonus): RiderJob
    {
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'completed', [
            'shop_bonus' => $shopBonus,
            'handover_required' => false,
            'delivered_at' => now(),
            'completed_at' => now(),
        ]);

        app(RiderEarningService::class)->settle($job);

        Order::whereKey($order->id)->update(['status' => 'delivered', 'delivered_at' => now()]);
        app(OrderDistributionService::class)->processOrderDistribution($order->fresh());

        return $job->fresh();
    }

    private function systemWallets(): float
    {
        return round((float) Wallet::sum('balance'), 2);
    }

    /**
     * สมการเงินของแพลตฟอร์มต่อออเดอร์: รับจากผู้ซื้อ − คืนผู้ซื้อ − จ่ายไรเดอร์ − จ่ายร้าน + เรียกคืนจากร้าน = ส่วนแบ่งค่าส่งของแพลตฟอร์ม (≥ 0)
     */
    private function assertPlatformKeepsOnlyItsDeliveryShare(float $paid, float $refunded, float $riderPaid, float $sellerNet, float $clawedFromSeller, float $platformShare): void
    {
        $platform = round($paid - $refunded - $riderPaid - $sellerNet + $clawedFromSeller, 2);

        $this->assertGreaterThanOrEqual(0.0, $platform);
        $this->assertEqualsWithDelta($platformShare, $platform, 0.001);
    }

    /**
     * ออเดอร์ตลาดสดส่งด้วยไรเดอร์ จ่าย wallet แล้ว ไรเดอร์ส่งสำเร็จ (ได้ค่าส่ง + โบนัส) สถานะ "ส่งถึงแล้ว" ยังไม่ปิดออเดอร์
     * สินค้า 100 · ร้านส่งฟรี (ค่าส่งเต็ม 30) + โบนัส 12
     *
     * @return array{0: FreshMarketOrder, 1: User, 2: User}
     */
    private function deliveredFreshMarketOrder(float $sellerBalance): array
    {
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ', 'ai_provider' => 'groq', 'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10, 'fee_mode' => 'percentage', 'escrow_enabled' => true, 'cod_enabled' => true,
            'rider_enabled' => true, 'cashback_enabled' => false, 'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();
        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');

        $buyer = $this->makeUser('ผู้ซื้อตลาดสด', 1000);
        $sellerUser = $this->makeUser('ร้านผักส่งฟรี', $sellerBalance);
        $seller = FreshMarketSeller::create([
            'user_id' => $sellerUser->id, 'shop_name' => 'ร้านผักส่งฟรี', 'phone' => '0812345678', 'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7291, 'longitude' => 100.5210, 'is_active' => true, 'is_suspended' => false, 'is_verified' => true,
            'subscription_type' => 'free', 'rider_free_delivery' => true, 'rider_bonus' => 12,
        ]);
        $listing = FreshMarketListing::create([
            'seller_id' => $seller->id, 'title' => 'ผักบุ้ง', 'price' => 100, 'unit' => 'กำ', 'quantity_available' => 10,
            'status' => 'active', 'is_available' => true, 'created_via' => 'web',
        ]);

        $order = (new FreshMarketService)->createOrder($buyer, $listing->fresh(), [
            'quantity' => 1,
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => self::DROP_LAT,
            'buyer_longitude' => self::DROP_LNG,
            'delivery_address' => '99 ถนนทดสอบ',
        ]);
        $order = FreshMarketOrder::findOrFail($order->id);
        $this->assertEquals(0, (float) $order->delivery_fee);
        $this->assertEquals(30, (float) $order->delivery_subsidy_amount, 'ระยะใกล้ ค่าส่งขั้นต่ำ 30 — ร้านออกเต็ม (ไม่ถูกจำกัด)');
        $this->assertEquals(12, (float) $order->rider_bonus_amount);

        $rider = $this->makeRider();
        $job = new RiderJob;
        $job->forceFill([
            'rider_id' => $rider->id, 'job_type' => 'fresh_market', 'source_type' => $order->getMorphClass(), 'source_id' => $order->id,
            'customer_id' => $order->buyer_id, 'title' => 'ส่งของตลาดสด '.$order->order_number,
            'pickup_address' => 'ร้านผัก', 'pickup_latitude' => 13.7291, 'pickup_longitude' => 100.5210,
            'delivery_address' => '99 ถนนทดสอบ', 'delivery_latitude' => self::DROP_LAT, 'delivery_longitude' => self::DROP_LNG,
            'total_fee' => 30, 'rider_earnings' => 24, 'platform_fee' => 6, 'shop_bonus' => 12, 'cod_amount' => 0,
            'status' => 'completed', 'handover_required' => false,
            'accepted_at' => now()->subHour(), 'picked_up_at' => now()->subMinutes(40), 'completed_at' => now(),
            'tracking_token' => Str::random(48), 'tracking_expires_at' => now()->addDay(),
        ])->save();
        app(RiderEarningService::class)->settle($job->fresh());

        FreshMarketOrder::whereKey($order->id)->update([
            'order_status' => FreshMarketOrder::STATUS_DELIVERED,
            'delivered_at' => now(),
            'rider_job_id' => $job->id,
            'rider_id' => $rider->id,
        ]);

        return [$order->fresh(), $buyer, $sellerUser];
    }
}
