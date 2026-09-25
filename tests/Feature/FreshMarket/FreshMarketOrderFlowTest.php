<?php

namespace Tests\Feature\FreshMarket;

use App\Exceptions\FreshMarketException;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\PlatformTransaction;
use App\Models\PlatformWallet;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Services\FreshMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ทดสอบวงจรเงินของออเดอร์ตลาดสด (ต้องใช้ MySQL — รันบน CI)
 *
 * ครอบคลุม:
 * - จ่ายผ่าน wallet หักเงินผู้ซื้อจริง + ถือ escrow
 * - ยกเลิกคืนเงินครั้งเดียว (กดซ้ำไม่คืนซ้ำ) + คืนสต็อก
 * - COD ยกเลิกไม่คืนเงิน (ไม่มีเงินในระบบ)
 * - ปิดออเดอร์ปล่อย escrow ครั้งเดียว + GP เข้ากระเป๋า fee แพลตฟอร์ม
 * - COD นัดรับ: หัก GP จาก wallet ร้าน / ไม่พอ = หนี้ แล้วหักจากยอดขายถัดไป
 * - ห้ามซื้อของร้านตัวเอง / กันขายเกินสต็อก / เงินไม่พอไม่ตัดสต็อก
 * - ตัวกวาดยกเลิกออเดอร์ที่ร้านไม่รับเกินเวลา
 */
class FreshMarketOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected FreshMarketService $service;

    protected User $buyer;

    protected User $sellerUser;

    protected FreshMarketSeller $seller;

    protected FreshMarketListing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        FreshMarketSetting::clearCache();

        // ตั้งค่า: GP 10%, เปิด wallet + COD, ปิดไรเดอร์/แคชแบ็ค (ทดสอบเฉพาะเงินออเดอร์)
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => false,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();

        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        // migration ตั้งโปรฯ GP ฟรีช่วงเปิดตัวไว้ → ปิดในเทสต์ที่ตรวจตัวเลข GP
        Setting::set('pricing.gp_free', '0', 'boolean', 'pricing');
        Setting::set('fresh_market.auto_approve_sellers', '1', 'boolean', 'fresh_market');
        Setting::set('fresh_market.pending_expiry_minutes', '30', 'integer', 'fresh_market');

        $this->buyer = User::factory()->create(['name' => 'ผู้ซื้อทดสอบ']);
        $this->sellerUser = User::factory()->create(['name' => 'ผู้ขายทดสอบ']);

        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $this->sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $this->seller = FreshMarketSeller::create([
            'user_id' => $this->sellerUser->id,
            'shop_name' => 'ร้านผักทดสอบ',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        $this->listing = FreshMarketListing::create([
            'seller_id' => $this->seller->id,
            'title' => 'ผักบุ้งจีน',
            'price' => 50,
            'unit' => 'กำ',
            'quantity_available' => 5,
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        $this->service = new FreshMarketService;
    }

    /**
     * สั่งซื้อผ่าน wallet → หักเงินผู้ซื้อทันที + escrow held + ตัดสต็อก
     */
    public function test_wallet_order_deducts_buyer_and_holds_escrow(): void
    {
        $order = $this->placeOrder($this->buyer, 2, 'wallet');

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('held', $order->escrow_status);
        $this->assertSame(100.0, (float) $order->total_amount);
        $this->assertSame(10.0, (float) $order->platform_fee);
        $this->assertSame(90.0, (float) $order->seller_earning);
        $this->assertSame(900.0, $this->balanceOf($this->buyer));
        $this->assertSame(3, (int) $this->listing->fresh()->quantity_available);

        $this->assertSame(1, WalletTransaction::where('reference_type', FreshMarketService::REF_PAYMENT)
            ->where('reference_id', $order->id)->count());
    }

    /**
     * ยกเลิกออเดอร์ที่จ่ายผ่าน wallet → คืนเงินครั้งเดียว แม้เรียกซ้ำ + คืนสต็อก
     */
    public function test_cancel_refunds_wallet_payment_exactly_once(): void
    {
        $order = $this->placeOrder($this->buyer, 2, 'wallet');

        $cancelled = $this->service->cancelOrder($order, 'เปลี่ยนใจ', 'buyer', $this->buyer);
        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $cancelled->order_status);
        $this->assertSame('refunded', $cancelled->payment_status);
        $this->assertSame(100.0, (float) $cancelled->refunded_amount);
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));

        // เรียกซ้ำ (กดสองครั้ง / แอดมินกดซ้ำ) → ไม่คืนเงินซ้ำ
        $this->service->cancelOrder($order->fresh(), 'กดซ้ำ', 'buyer', $this->buyer);
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));
        $this->assertSame(1, WalletTransaction::where('reference_type', FreshMarketService::REF_REFUND)
            ->where('reference_id', $order->id)->count());

        // คืนสต็อก
        $this->assertSame(5, (int) $this->listing->fresh()->quantity_available);
    }

    /**
     * COD ที่ยังไม่เก็บเงิน → ยกเลิกแล้วไม่มีการคืนเงินเข้า wallet (กันเสกเงิน)
     */
    public function test_cod_cancel_refunds_nothing(): void
    {
        $order = $this->placeOrder($this->buyer, 1, 'cod');

        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->escrow_status);
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));

        $cancelled = $this->service->cancelOrder($order, 'เปลี่ยนใจ', 'buyer', $this->buyer);

        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $cancelled->order_status);
        $this->assertSame(0.0, (float) $cancelled->refunded_amount);
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));
        $this->assertSame(0, WalletTransaction::where('reference_type', FreshMarketService::REF_REFUND)->count());
        $this->assertSame(5, (int) $this->listing->fresh()->quantity_available);
    }

    /**
     * ปิดออเดอร์ → ร้านได้ (ยอด − GP) ครั้งเดียว, GP เข้ากระเป๋า fee แพลตฟอร์มครั้งเดียว
     */
    public function test_complete_releases_escrow_once_and_credits_gp_to_platform_fee_wallet(): void
    {
        $order = $this->placeOrder($this->buyer, 2, 'wallet');

        $this->service->applyAction($order, 'accept', 'seller', $this->sellerUser);
        $this->service->applyAction($order, 'ready', 'seller', $this->sellerUser);
        $this->service->applyAction($order, 'handover', 'seller', $this->sellerUser);

        $feeBefore = (float) PlatformWallet::getFeeWallet()->fresh()->balance;

        $completed = $this->service->completeOrder($order->fresh(), 'buyer', $this->buyer);
        $this->assertSame(FreshMarketOrder::STATUS_COMPLETED, $completed->order_status);
        $this->assertSame('released', $completed->escrow_status);
        $this->assertSame(90.0, $this->balanceOf($this->sellerUser));

        // ยืนยันซ้ำ / ระบบปิดซ้ำ → ไม่จ่ายซ้ำ
        $this->service->completeOrder($order->fresh(), 'buyer', $this->buyer);
        $this->service->completeOrder($order->fresh(), 'system');

        $this->assertSame(90.0, $this->balanceOf($this->sellerUser));
        $this->assertSame(1, WalletTransaction::where('reference_type', FreshMarketService::REF_PAYOUT)
            ->where('reference_id', $order->id)->count());

        $gpRows = PlatformTransaction::where('source_type', FreshMarketOrder::class)
            ->where('source_id', $order->id)
            ->where('sub_type', FreshMarketService::PLATFORM_GP)
            ->get();
        $this->assertCount(1, $gpRows);
        $this->assertSame(10.0, (float) $gpRows->first()->amount);
        $this->assertSame(round($feeBefore + 10, 2), round((float) PlatformWallet::getFeeWallet()->fresh()->balance, 2));
    }

    /**
     * นัดรับ: ผู้ซื้อยืนยันรับได้ตั้งแต่สถานะ ready
     */
    public function test_pickup_order_can_be_confirmed_from_ready(): void
    {
        $order = $this->placeOrder($this->buyer, 1, 'wallet');
        $this->service->applyAction($order, 'accept', 'seller', $this->sellerUser);
        $this->service->applyAction($order, 'ready', 'seller', $this->sellerUser);

        $completed = $this->service->applyAction($order->fresh(), 'confirm', 'buyer', $this->buyer);

        $this->assertSame(FreshMarketOrder::STATUS_COMPLETED, $completed->order_status);
        $this->assertSame(45.0, $this->balanceOf($this->sellerUser));
    }

    /**
     * ผู้ขายซื้อสินค้าร้านตัวเองไม่ได้ (กันปั่น escrow/แคชแบ็ค)
     */
    public function test_self_purchase_is_rejected(): void
    {
        Wallet::where('user_id', $this->sellerUser->id)->update(['balance' => 1000]);

        try {
            $this->placeOrder($this->sellerUser, 1, 'wallet');
            $this->fail('ควรโยน SELF_PURCHASE');
        } catch (FreshMarketException $e) {
            $this->assertSame('SELF_PURCHASE', $e->errorCode());
        }

        $this->assertSame(0, FreshMarketOrder::count());
        $this->assertSame(5, (int) $this->listing->fresh()->quantity_available);
        $this->assertSame(1000.0, $this->balanceOf($this->sellerUser));
    }

    /**
     * กันขายเกินสต็อก: สั่งเกินไม่ได้ / ของหมดแล้วสั่งไม่ได้ / reserveStock ไม่ติดลบ
     */
    public function test_oversell_is_prevented(): void
    {
        try {
            $this->placeOrder($this->buyer, 6, 'cod');
            $this->fail('ควรโยน OUT_OF_STOCK');
        } catch (FreshMarketException $e) {
            $this->assertSame('OUT_OF_STOCK', $e->errorCode());
        }

        $this->placeOrder($this->buyer, 5, 'cod');

        $listing = $this->listing->fresh();
        $this->assertSame(0, (int) $listing->quantity_available);
        $this->assertSame('sold_out', $listing->status);
        $this->assertFalse((bool) $listing->is_available);

        $otherBuyer = User::factory()->create();
        Wallet::create(['user_id' => $otherBuyer->id, 'balance' => 500, 'currency' => 'THB', 'status' => 'active']);

        try {
            $this->placeOrder($otherBuyer, 1, 'wallet');
            $this->fail('ควรซื้อไม่ได้เพราะของหมด');
        } catch (FreshMarketException $e) {
            $this->assertContains($e->errorCode(), ['LISTING_UNAVAILABLE', 'OUT_OF_STOCK']);
        }

        $this->assertFalse(FreshMarketListing::reserveStock($this->listing->id, 1));
        $this->assertSame(0, (int) $this->listing->fresh()->quantity_available);
        $this->assertSame(500.0, $this->balanceOf($otherBuyer));
    }

    /**
     * เงินไม่พอ → ไม่สร้างออเดอร์ ไม่ตัดสต็อก (ทั้งก้อน rollback)
     */
    public function test_insufficient_balance_rolls_back_order_and_stock(): void
    {
        Wallet::where('user_id', $this->buyer->id)->update(['balance' => 10]);

        try {
            $this->placeOrder($this->buyer, 2, 'wallet');
            $this->fail('ควรโยน INSUFFICIENT_BALANCE');
        } catch (FreshMarketException $e) {
            $this->assertSame('INSUFFICIENT_BALANCE', $e->errorCode());
        }

        $this->assertSame(0, FreshMarketOrder::count());
        $this->assertSame(5, (int) $this->listing->fresh()->quantity_available);
        $this->assertSame(10.0, $this->balanceOf($this->buyer));
    }

    /**
     * ผู้ซื้อยกเลิกได้เฉพาะตอนร้านยังไม่รับ
     */
    public function test_buyer_cannot_cancel_after_seller_accepts(): void
    {
        $order = $this->placeOrder($this->buyer, 1, 'wallet');
        $this->service->applyAction($order, 'accept', 'seller', $this->sellerUser);

        try {
            $this->service->cancelOrder($order->fresh(), 'เปลี่ยนใจ', 'buyer', $this->buyer);
            $this->fail('ควรโยน INVALID_TRANSITION');
        } catch (FreshMarketException $e) {
            $this->assertSame('INVALID_TRANSITION', $e->errorCode());
        }

        $this->assertSame(950.0, $this->balanceOf($this->buyer));
    }

    /**
     * COD นัดรับ: ร้านไม่มีเงินใน wallet → บันทึกหนี้ GP แล้วหักจากยอดขายผ่าน wallet ออเดอร์ถัดไป
     */
    public function test_cod_gp_becomes_debt_and_is_collected_from_next_escrow_payout(): void
    {
        // ออเดอร์ COD 100 บาท → GP 10 บาท ร้านมีเงิน 0 → เป็นหนี้
        $cod = $this->placeOrder($this->buyer, 2, 'cod');
        $this->service->applyAction($cod, 'accept', 'seller', $this->sellerUser);
        $this->service->applyAction($cod, 'ready', 'seller', $this->sellerUser);

        // COD นัดรับ: ผู้ซื้อปิดออเดอร์เองตั้งแต่ ready ไม่ได้ (ยังไม่ได้จ่ายเงินสด/รับของ)
        try {
            $this->service->completeOrder($cod->fresh(), 'buyer', $this->buyer);
            $this->fail('COD นัดรับต้องให้ร้านกดส่งมอบ (รับเงินแล้ว) ก่อน');
        } catch (FreshMarketException $e) {
            $this->assertSame('INVALID_TRANSITION', $e->errorCode());
        }
        $this->assertSame(0.0, $this->seller->fresh()->outstandingGpDebt(), 'ยังไม่ส่งมอบ ต้องไม่ถูกคิด GP');

        // ร้านกดส่งมอบ (= ได้รับเงินสดแล้ว) → ผู้ซื้อยืนยันรับของได้
        $this->service->applyAction($cod->fresh(), 'handover', 'seller', $this->sellerUser);
        $this->service->completeOrder($cod->fresh(), 'buyer', $this->buyer);

        $this->assertSame(10.0, $this->seller->fresh()->outstandingGpDebt());
        $this->assertSame(1, WalletDebt::where('source_type', FreshMarketService::DEBT_SOURCE_GP)->count());

        // ออเดอร์ wallet 50 บาท → ร้านได้สุทธิ 45 − หนี้ 10 = 35
        $walletOrder = $this->placeOrder($this->buyer, 1, 'wallet');
        $this->service->applyAction($walletOrder, 'accept', 'seller', $this->sellerUser);
        $this->service->applyAction($walletOrder, 'ready', 'seller', $this->sellerUser);
        $this->service->completeOrder($walletOrder->fresh(), 'buyer', $this->buyer);

        $this->assertSame(35.0, $this->balanceOf($this->sellerUser));
        $this->assertSame(0.0, $this->seller->fresh()->outstandingGpDebt());
    }

    /**
     * ตัวกวาด: pending เกินเวลา → ยกเลิกโดยระบบ + คืนเงิน + คืนสต็อก
     */
    public function test_expire_pending_orders_cancels_and_refunds(): void
    {
        $order = $this->placeOrder($this->buyer, 2, 'wallet');

        $this->travel(31)->minutes();

        $count = $this->service->expirePendingOrders();

        $this->assertSame(1, $count);
        $fresh = $order->fresh();
        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $fresh->order_status);
        $this->assertSame('system', $fresh->cancelled_by);
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));
        $this->assertSame(5, (int) $this->listing->fresh()->quantity_available);
    }

    /**
     * เติมสต็อกให้สินค้าที่ของหมด → กลับมาขายได้อัตโนมัติ
     */
    public function test_restock_reactivates_sold_out_listing(): void
    {
        $this->placeOrder($this->buyer, 5, 'cod');
        $this->assertSame('sold_out', $this->listing->fresh()->status);

        $listing = $this->listing->fresh();
        $listing->update(['quantity_available' => 10]);

        $listing = $listing->fresh();
        $this->assertSame('active', $listing->status);
        $this->assertTrue((bool) $listing->is_available);
        $this->assertTrue($listing->isAvailableForPurchase());
    }

    // ===== Helpers =====

    /**
     * COD ส่งด้วยไรเดอร์ที่ยอดเกินวงเงิน COD ต่องาน → ปฏิเสธตั้งแต่ตอนสั่ง
     * (เดิมสั่งได้ ร้านเตรียมของเสร็จแล้วเรียกไรเดอร์ไม่ได้ ออเดอร์ค้าง READY ถาวร)
     */
    public function test_cod_rider_order_over_the_cod_limit_is_rejected_at_checkout(): void
    {
        FreshMarketSetting::query()->update(['rider_enabled' => true]);
        FreshMarketSetting::clearCache();
        Setting::set('rider.max_cod_amount', '100', 'float', 'rider');
        $service = new FreshMarketService;

        $riderOrder = fn (int $qty) => $service->createOrder($this->buyer, $this->listing->fresh(), [
            'quantity' => $qty,
            'delivery_type' => 'rider',
            'payment_method' => 'cod',
            'buyer_latitude' => 13.7600,
            'buyer_longitude' => 100.5050,
            'delivery_address' => '99 ถนนทดสอบ แขวงทดสอบ',
        ]);

        try {
            $riderOrder(2); // 100 + ค่าส่ง > 100
            $this->fail('ต้องปฏิเสธ COD ที่เกินวงเงิน');
        } catch (FreshMarketException $e) {
            $this->assertSame('COD_NOT_AVAILABLE', $e->errorCode());
        }

        $this->assertSame(5, (int) $this->listing->fresh()->quantity_available, 'ถูกปฏิเสธต้องไม่ตัดสต็อก');
        $this->assertSame(0, FreshMarketOrder::where('buyer_id', $this->buyer->id)->count());

        // ยอดไม่เกินวงเงิน → สั่งได้
        $ok = $riderOrder(1);
        $this->assertSame('cod', $ok->payment_method);
        $this->assertLessThanOrEqual(100.0, (float) $ok->total_amount + (float) $ok->delivery_fee);
    }

    protected function placeOrder(User $buyer, int $quantity, string $paymentMethod): FreshMarketOrder
    {
        return $this->service->createOrder($buyer, $this->listing->fresh(), [
            'quantity' => $quantity,
            'delivery_type' => 'pickup',
            'payment_method' => $paymentMethod,
        ]);
    }

    protected function balanceOf(User $user): float
    {
        return round((float) Wallet::where('user_id', $user->id)->value('balance'), 2);
    }
}
