<?php

namespace Tests\Feature\Shop;

use App\Models\Coupon;
use App\Models\Notification as AppNotification;
use App\Models\Order;
use App\Models\OrderTrackingHistory;
use App\Models\PaymentTransaction;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\SmsPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 🧾 (2026-10-13) เงินพร้อมเพย์ของออเดอร์ร้านค้า ห้ามหายเงียบ
 *
 * บั๊กที่ล็อกไว้:
 *   1. ยกเลิกออเดอร์ที่ยังไม่จ่าย → บิลถูกปิดแต่ยอดจองทศนิยมค้าง reserved → SMS โอนตามหลังจับคู่ยอดนั้น
 *      ตั้ง SMS matched + ยอด used แต่ไม่ตัดบิล = เงินหาย ไม่มีออเดอร์ ไม่มีใครคืน SMS หลุดคิวแอดมิน
 *   2. "ชำระใหม่" ออก QR ใหม่โดยไม่ปิด QR เดิม → ยอดเดิมหมดเวลา cleanup ยกเลิกทั้งออเดอร์
 *      ทั้งที่ QR ใหม่ยังจ่ายได้ → ลูกค้าโอนตาม QR ใหม่ = เงินเข้าออเดอร์ที่ถูกยกเลิก
 *   3. cleanup ยกเลิกออเดอร์ด้วย update ดิบ (ไม่คืนคูปอง/สต็อก) และมองไม่เห็นยอดจองที่ generate() พลิกเป็น expired
 *
 * ยิง SMS ผ่าน SmsPaymentService::processNotification (เส้นเดียวกับ POST /notify หลังถอดรหัส)
 */
#[Group('shop')]
class PromptPayLatePaymentSafetyTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private SmsCheckerDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();

        config([
            'smschecker.enabled' => true,
            'smschecker.unique_amount_expiry' => 1440,
            'smschecker.auto_confirm_matched' => true,
        ]);
        $this->setGpRate(10);

        // มือถือรับ SMS ของแพลตฟอร์ม (store_id ว่าง = เครื่องแอดมิน จับคู่ได้ทุกร้าน)
        $this->device = SmsCheckerDevice::create([
            'device_id' => 'SMSCHK-LATEPAY1',
            'device_name' => 'มือถือรับเงินร้านค้า',
            'api_key' => SmsCheckerDevice::generateApiKey(),
            'secret_key' => SmsCheckerDevice::generateSecretKey(),
            'platform' => 'android',
            'status' => 'active',
        ]);
    }

    // =====================================================
    // 1) ยกเลิกออเดอร์ → ปล่อยยอดจอง · SMS ตามหลังไม่ถูกกลืน เข้าคิวแอดมินพร้อมโน้ต
    // =====================================================

    public function test_cancel_releases_reserved_amount_and_late_sms_goes_to_admin_queue_with_note(): void
    {
        [$buyer, $order] = $this->pendingOrder();
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => false]);
        $qr = $this->openQr($order);
        $upa = $this->reservedAmountOf($qr);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/v1/orders/{$order->id}/cancel", ['reason' => 'เปลี่ยนใจ'])
            ->assertOk()
            ->assertJsonPath('refunded', false);

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $qr->fresh()->status);
        $this->assertSame('cancelled', $upa->fresh()->status, 'ยกเลิกออเดอร์แล้วต้องปล่อยยอดจองทศนิยม');

        // ลูกค้าโอนตาม QR เดิมทีหลัง
        $this->travel(10)->minutes();
        $result = $this->notify((float) $qr->amount, 'late-after-cancel');

        $sms = SmsPaymentNotification::findOrFail($result['data']['notification_id']);
        $this->assertFalse($result['data']['matched'], 'เงินเข้าออเดอร์ที่ยกเลิกแล้ว ห้ามตอบว่าจับคู่สำเร็จ');
        $this->assertSame('requires_admin_review', $sms->status);
        $this->assertNull($sms->matched_transaction_id, 'ห้ามผูกกับบิลที่ปิดแล้ว');
        $this->assertNotSame('used', $upa->fresh()->status, 'ยอดจองห้ามถูกตั้งเป็น used');
        $this->assertStringContainsString('ยอดโอนตรงกับออเดอร์ที่ยกเลิกแล้ว', (string) $sms->closedBillReviewNote());
        $this->assertStringContainsString($order->order_number, (string) $sms->closedBillReviewNote());
        $this->assertStringContainsString('คืนเงินลูกค้า', (string) $sms->closedBillReviewNote());

        // ออเดอร์ไม่ถูกเปิดกลับ ไม่ถูกตั้งจ่ายแล้ว
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNotSame('paid', $order->payment_status);
        $this->assertSame('cancelled', $qr->fresh()->status);

        // แจ้งแอดมินในระบบ (ไม่ใช่ LINE push)
        $this->assertSame(1, AppNotification::where('user_id', $admin->id)->where('type', 'sms_payment_review')->count());

        // อยู่ในคิว "SMS เงินเข้าที่ยังไม่ผูกบิล" ของแอปแอดมิน
        Sanctum::actingAs($admin, ['admin']);
        $res = $this->getJson('/api/admin/ops/summary')->assertOk();
        $this->assertSame(1, $res->json('data.queue.sms_unmatched.count'));
        $this->assertSame($sms->id, $res->json('data.queue.sms_unmatched.preview.0.id'));

        // Warroom inbox เห็นโน้ตบอกว่าเงินของออเดอร์ไหน
        $inbox = $this->getJson('/api/admin/payment/sms/inbox?status=requires_admin_review')->assertOk();
        $this->assertSame($sms->id, $inbox->json('data.data.0.id'));
        $this->assertStringContainsString($order->order_number, (string) $inbox->json('data.data.0.review_note'));

        // แอดมินกดจับคู่ซ้ำ → ยังไม่ผูก ไม่แจ้งเตือนซ้ำ
        $this->assertFalse($sms->fresh()->attemptMatch(true));
        $this->assertSame(1, AppNotification::where('user_id', $admin->id)->where('type', 'sms_payment_review')->count());
    }

    public function test_legacy_cancelled_order_with_still_reserved_amount_is_flagged_not_swallowed(): void
    {
        // ข้อมูลที่ยกเลิกก่อนแก้: บิล+ออเดอร์ถูกปิดแต่ยอดจองยัง reserved (ยังไม่หมดเวลา)
        [, $order] = $this->pendingOrder();
        $admin = User::factory()->create(['role' => 'admin']);
        $qr = $this->openQr($order);
        $upa = $this->reservedAmountOf($qr);

        DB::table('payment_transactions')->where('id', $qr->id)->update(['status' => 'cancelled']);
        DB::table('orders')->where('id', $order->id)->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $this->travel(5)->minutes();
        $result = $this->notify((float) $qr->amount, 'legacy-reserved');

        $sms = SmsPaymentNotification::findOrFail($result['data']['notification_id']);
        $this->assertFalse($result['data']['matched']);
        $this->assertSame('requires_admin_review', $sms->status);
        $this->assertNull($sms->matched_transaction_id);
        $this->assertSame('reserved', $upa->fresh()->status, 'เดิมถูกตั้ง used ทั้งที่ไม่ได้ตัดบิล — ตอนนี้ต้องไม่แตะ');
        $this->assertStringContainsString('ยอดโอนตรงกับออเดอร์ที่ยกเลิกแล้ว', (string) $sms->closedBillReviewNote());
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(1, AppNotification::where('user_id', $admin->id)->where('type', 'sms_payment_review')->count());
    }

    public function test_step2_fallback_does_not_complete_pending_bill_of_cancelled_order(): void
    {
        // บิลยังรอจ่าย (ยอดจองถูกพลิกเป็น expired) แต่ออเดอร์ถูกยกเลิกด้วยเส้นเก่า
        [, $order] = $this->pendingOrder();
        $qr = $this->openQr($order);
        $upa = $this->reservedAmountOf($qr);

        DB::table('unique_payment_amounts')->where('id', $upa->id)->update(['status' => 'expired', 'expires_at' => now()->subMinute()]);
        DB::table('orders')->where('id', $order->id)->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $this->travel(5)->minutes();
        $sms = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => (float) $qr->amount,
            'device_id' => $this->device->device_id,
            'nonce' => 'step2-'.Str::random(6),
            'sms_timestamp' => now(),
            'status' => 'pending',
        ]);

        $this->assertFalse($sms->attemptMatch(true));
        $sms->refresh();
        $this->assertSame('requires_admin_review', $sms->status);
        $this->assertNull($sms->matched_transaction_id);
        $this->assertSame('expired', $upa->fresh()->status);
        $this->assertContains($qr->fresh()->status, ['pending', 'processing'], 'ห้ามตัดบิลของออเดอร์ที่ยกเลิก');
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    // =====================================================
    // 2) ชำระใหม่ → ปิด QR เดิม · cleanup ไม่ยกเลิกออเดอร์ขณะ QR ใหม่ยังจ่ายได้ · จ่าย QR ใหม่ = ออเดอร์จ่ายแล้ว
    // =====================================================

    public function test_pay_again_closes_old_qr_and_order_survives_cleanup_then_new_qr_payment_completes_it(): void
    {
        [$buyer, $order] = $this->pendingOrder();
        Sanctum::actingAs($buyer);

        $first = $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'promptpay'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $oldQr = PaymentTransaction::where('order_id', $order->id)->latest('id')->firstOrFail();
        $oldUpa = $this->reservedAmountOf($oldQr);
        $this->assertSame('processing', $oldQr->status);
        $this->assertNotNull($first->json('data'));

        // QR เดิมหมดอายุ (30 นาที) → ลูกค้ากดชำระใหม่
        $this->travel(31)->minutes();
        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'promptpay'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $newQr = PaymentTransaction::where('order_id', $order->id)->latest('id')->firstOrFail();
        $this->assertNotSame($oldQr->id, $newQr->id);
        $this->assertSame('processing', $newQr->status);
        $newUpa = $this->reservedAmountOf($newQr);

        $this->assertSame('cancelled', $oldQr->fresh()->status, 'QR เดิมต้องถูกปิดเมื่อออก QR ใหม่');
        $this->assertSame('cancelled', $oldUpa->fresh()->status, 'ยอดจองของ QR เดิมต้องถูกปล่อย');
        $this->assertNotEquals((float) $oldQr->amount, (float) $newQr->amount, 'QR ใหม่ต้องได้ยอดคนละตัวกับ QR เดิม');
        $this->assertSame(1, PaymentTransaction::where('order_id', $order->id)->whereIn('status', ['pending', 'processing'])->count());

        // เลยเวลาที่ยอดจองเดิมจะหมด (24 ชม.) แต่ QR ใหม่ยังเหลือเวลา → cleanup ห้ามยกเลิกออเดอร์
        $this->travelTo($oldUpa->created_at->copy()->addMinutes(1441));
        $stats = app(SmsPaymentService::class)->cleanup();
        $this->assertSame(0, $stats['cancelled_orders']);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('processing', $newQr->fresh()->status);
        $this->assertSame('reserved', $newUpa->fresh()->status);

        // ลูกค้าโอนตาม QR ใหม่ → ออเดอร์จ่ายแล้ว
        $result = $this->notify((float) $newQr->amount, 'pay-new-qr');
        $this->assertTrue($result['data']['matched']);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame('completed', $newQr->fresh()->status);
        $this->assertSame('used', $newUpa->fresh()->status);
    }

    public function test_cleanup_keeps_order_while_another_qr_is_live_even_for_pre_fix_double_qr(): void
    {
        // ข้อมูลก่อนแก้: ออเดอร์มี QR ค้างสองใบ (ใบเก่าไม่ถูกปิดตอนออกใบใหม่)
        [, $order] = $this->pendingOrder();
        $service = app(PaymentService::class);

        $oldQr = $this->openQr($order);
        $oldUpa = $this->reservedAmountOf($oldQr);

        $this->travel(31)->minutes();
        $newQr = PaymentTransaction::create([
            'user_id' => $order->user_id,
            'store_id' => $oldQr->store_id,
            'type' => 'order_payment',
            'payment_method' => 'promptpay',
            'status' => 'pending',
            'amount' => $order->total_amount,
            'currency' => 'THB',
            'order_id' => $order->id,
            'expired_at' => now()->addMinutes(30),
        ]);
        $service->processPayment($newQr->fresh(), []);
        $newQr->refresh();
        $newUpa = $this->reservedAmountOf($newQr);
        $this->assertSame('processing', $oldQr->fresh()->status, 'จำลองข้อมูลเก่า: QR เดิมยังเปิดอยู่');

        $this->travelTo($oldUpa->created_at->copy()->addMinutes(1441));
        $stats = app(SmsPaymentService::class)->cleanup();

        $this->assertSame(1, $stats['cancelled_transactions']);
        $this->assertSame(0, $stats['cancelled_orders'], 'QR ใหม่ยังจ่ายได้ ห้ามยกเลิกออเดอร์');
        $this->assertSame('cancelled', $oldQr->fresh()->status);
        $this->assertSame('expired', $oldUpa->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('processing', $newQr->fresh()->status);

        $result = $this->notify((float) $newQr->amount, 'pay-new-qr-legacy');
        $this->assertTrue($result['data']['matched']);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('used', $newUpa->fresh()->status);
    }

    // =====================================================
    // 3) cleanup ยกเลิกผ่าน Order::cancel (คืนคูปอง/สต็อก) · ทำซ้ำได้ · เห็นยอดที่ถูกพลิกเป็น expired
    // =====================================================

    public function test_cleanup_cancels_expired_unpaid_order_through_cancel_path_and_is_idempotent(): void
    {
        [$buyer, $order, $product] = $this->pendingOrder(withProduct: true);
        $coupon = $this->useCoupon($buyer, $order);

        // ออเดอร์ที่จองสต็อกไว้แล้ว → ยกเลิกต้องคืน
        $order->deductStockOnce(true);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);

        $qr = $this->openQr($order);
        $upa = $this->reservedAmountOf($qr);

        // ยังไม่หมดเวลา → ไม่แตะ
        $this->travel(2)->hours();
        $stats = app(SmsPaymentService::class)->cleanup();
        $this->assertSame(0, $stats['cancelled_orders']);
        $this->assertSame('pending', $order->fresh()->status);

        $this->travelTo($upa->created_at->copy()->addMinutes(1441));
        $stats = app(SmsPaymentService::class)->cleanup();

        $this->assertSame(1, $stats['cancelled_orders']);
        $this->assertSame(1, $stats['cancelled_transactions']);
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', $order->payment_status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('cancelled', $qr->fresh()->status);
        $this->assertSame('expired', $upa->fresh()->status);

        // ผ่านทางยกเลิกปกติ: คืนสิทธิ์คูปอง + คืนสต็อก + บันทึกประวัติ
        $this->assertSame(0, DB::table('coupon_usages')->where('order_id', $order->id)->count());
        $this->assertSame(0, (int) $coupon->fresh()->used_count);
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->assertNull($order->stock_deducted_at);
        $this->assertTrue(OrderTrackingHistory::where('order_id', $order->id)->where('status', 'cancelled')->exists());
        $this->assertSame(1, $this->buyerCancelledNotices($buyer), 'หมดเวลาตามปกติ → แจ้งผู้ซื้อเหมือนเดิม');

        // รันซ้ำ (ทุก 5 นาที) → ไม่มีอะไรเปลี่ยน
        $again = app(SmsPaymentService::class)->cleanup();
        $this->assertSame(0, $again['cancelled_orders']);
        $this->assertSame(0, $again['cancelled_transactions']);
        $this->assertSame(0, (int) $coupon->fresh()->used_count);
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, OrderTrackingHistory::where('order_id', $order->id)->where('status', 'cancelled')->count());
    }

    public function test_cleanup_picks_up_order_whose_amount_was_flipped_to_expired_by_a_new_bill(): void
    {
        [$buyer, $order] = $this->pendingOrder();
        $coupon = $this->useCoupon($buyer, $order);
        $qr = $this->openQr($order);
        $upa = $this->reservedAmountOf($qr);

        $this->travelTo($upa->created_at->copy()->addMinutes(1441));

        // มีบิลใหม่เกิดขึ้นก่อน cleanup → generate() พลิกยอดจองที่หมดเวลาทุกแถวเป็น expired
        UniquePaymentAmount::generate(77, null, 'order_payment');
        $this->assertSame('expired', $upa->fresh()->status);
        $this->assertSame('processing', $qr->fresh()->status);

        $stats = app(SmsPaymentService::class)->cleanup();

        $this->assertSame(1, $stats['cancelled_orders']);
        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $qr->fresh()->status);
        $this->assertSame(0, (int) $coupon->fresh()->used_count, 'คูปองต้องถูกคืน');

        $again = app(SmsPaymentService::class)->cleanup();
        $this->assertSame(0, $again['cancelled_orders']);
        $this->assertSame(0, $again['cancelled_transactions']);
    }

    public function test_stale_stranded_backlog_is_cancelled_without_buyer_notification_burst(): void
    {
        // ออเดอร์ค้างจากก่อนแก้ (บิลเก่าหลายวัน ยอดจองถูกพลิกเป็น expired ไปนานแล้ว)
        [$buyer, $order] = $this->pendingOrder();
        $coupon = $this->useCoupon($buyer, $order);
        $qr = $this->openQr($order);
        $upa = $this->reservedAmountOf($qr);

        $this->travel(5)->days();
        UniquePaymentAmount::generate(77, null, 'order_payment');
        $this->assertSame('expired', $upa->fresh()->status);

        $stats = app(SmsPaymentService::class)->cleanup();

        $this->assertSame(1, $stats['cancelled_orders']);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, (int) $coupon->fresh()->used_count, 'ยกเลิกเงียบก็ต้องคืนคูปอง');
        $this->assertSame(0, $this->buyerCancelledNotices($buyer), 'ออเดอร์ค้างเก่าหลายวัน ไม่ต้องยิงแจ้งเตือนยกเลิก');
    }

    public function test_cleanup_never_cancels_a_paid_order(): void
    {
        [, $order] = $this->pendingOrder();
        $qr = $this->openQr($order);
        $upa = $this->reservedAmountOf($qr);

        // ออเดอร์จ่ายด้วยช่องทางอื่นไปแล้ว แต่บิลพร้อมเพย์ยังค้าง (ข้อมูลเก่า)
        DB::table('orders')->where('id', $order->id)->update(['status' => 'processing', 'payment_status' => 'paid', 'paid_at' => now()]);

        $this->travelTo($upa->created_at->copy()->addMinutes(1441));
        $stats = app(SmsPaymentService::class)->cleanup();

        $this->assertSame(0, $stats['cancelled_orders']);
        $order->refresh();
        $this->assertSame('processing', $order->status);
        $this->assertSame('paid', $order->payment_status);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * ผู้ซื้อ + ออเดอร์ 200 บาท (พร้อมเพย์) ที่ยังไม่จ่าย
     *
     * @return array{0: User, 1: Order, 2?: \App\Models\Product}
     */
    private function pendingOrder(bool $withProduct = false): array
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer(0);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 2]], [
            'store_id' => $store->id,
            'payment_method' => 'promptpay',
        ]);

        return $withProduct ? [$buyer, $order, $product] : [$buyer, $order];
    }

    /** ออก QR พร้อมเพย์ให้ออเดอร์ (บิล processing + ยอดจองทศนิยม) */
    private function openQr(Order $order): PaymentTransaction
    {
        $service = app(PaymentService::class);
        $transaction = $service->createOrderPayment($order, 'promptpay');
        $result = $service->processPayment($transaction->fresh(), []);
        $this->assertTrue($result['success'], 'สร้าง QR ไม่สำเร็จ: '.($result['message'] ?? ''));

        return $transaction->fresh();
    }

    private function reservedAmountOf(PaymentTransaction $transaction): UniquePaymentAmount
    {
        $upa = UniquePaymentAmount::where('transaction_id', $transaction->id)
            ->where('transaction_type', 'order_payment')
            ->firstOrFail();
        $this->assertSame('reserved', $upa->status, 'บิลพร้อมเพย์ต้องจองยอดทศนิยมไว้');
        $this->assertEquals((float) $upa->unique_amount, (float) $transaction->amount);

        return $upa;
    }

    /** คูปองที่ถูกใช้กับออเดอร์นี้ (นับสิทธิ์แล้ว) */
    private function useCoupon(User $buyer, Order $order): Coupon
    {
        $coupon = Coupon::create([
            'code' => 'LATE'.Str::upper(Str::random(6)),
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'min_purchase' => 0,
            'usage_limit' => 10,
            'used_count' => 1,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        DB::table('coupon_usages')->insert([
            'coupon_id' => $coupon->id,
            'user_id' => $buyer->id,
            'order_id' => $order->id,
            'discount_amount' => 10,
            'order_total' => (float) $order->total_amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $coupon;
    }

    /** จำนวนแจ้งเตือน "คำสั่งซื้อถูกยกเลิก" ที่ผู้ซื้อได้ */
    private function buyerCancelledNotices(User $buyer): int
    {
        return AppNotification::where('user_id', $buyer->id)
            ->where('type', 'shop_order')
            ->where('title', 'คำสั่งซื้อถูกยกเลิก')
            ->count();
    }

    /**
     * SMS เงินเข้าจากมือถือรับเงิน (เส้นเดียวกับ /notify หลังตรวจลายเซ็น/ถอดรหัส)
     *
     * @return array<string, mixed>
     */
    private function notify(float $amount, string $tag): array
    {
        return app(SmsPaymentService::class)->processNotification([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => $amount,
            'account_number' => 'xxx-x-x1234-x',
            'sender_or_receiver' => 'นาย ผู้ซื้อ ทดสอบ',
            'reference_number' => '',
            'sms_timestamp' => now()->getTimestampMs(),
            'nonce' => $tag.'-'.Str::random(8),
        ], $this->device, '127.0.0.1');
    }
}
