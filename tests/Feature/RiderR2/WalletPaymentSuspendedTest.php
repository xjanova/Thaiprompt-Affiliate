<?php

namespace Tests\Feature\RiderR2;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Payment\PaymentService;
use App\Services\Payment\WalletPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 🔒 G1 (audit 2026-10-04): จ่ายออเดอร์ด้วยกระเป๋าเงิน (WalletPaymentProvider + POST /api/v1/payment/order)
 *
 * ครอบคลุม:
 *   - กระเป๋าถูกระงับ/ล็อก → ปฏิเสธ (ข้อความไทย) ไม่หักเงิน — ทั้งผ่าน API และเรียก provider ตรง
 *   - กดจ่ายซ้ำ (แอป + เว็บ / double tap) ออเดอร์ที่จ่ายแล้ว → 409 ALREADY_PAID หักครั้งเดียว
 *   - คำขอแรกหักเงินแล้วแต่ยังไม่ตั้งออเดอร์จ่ายแล้ว (race) → คำขอที่สองไม่หักซ้ำ
 *   - จ่ายวอลเลตแล้ว → บิลพร้อมเพย์ที่ค้างของออเดอร์ถูกยกเลิก + ยอดจองทศนิยมถูกปล่อย (SMS มาทีหลังตัดซ้ำไม่ได้)
 *   - วอลเลตหักไปแล้วแต่ออเดอร์ถูกจ่ายด้วยช่องทางอื่นก่อน → คืนเงินเข้ากระเป๋าอัตโนมัติ
 */
#[Group('rider-r2')]
class WalletPaymentSuspendedTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();

        $this->setGpRate(10);
    }

    public function test_suspended_wallet_is_rejected_without_charging(): void
    {
        [$buyer, $order] = $this->pendingOrder(1000);
        Wallet::where('user_id', $buyer->id)->update(['status' => 'suspended']);
        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'wallet'])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'WALLET_INACTIVE')
            ->assertJsonPath('message', WalletPaymentProvider::ERROR_WALLET_INACTIVE);

        $this->assertSame(1000.0, $this->walletBalance($buyer));
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(0, $this->debitCount($order));
    }

    public function test_temporarily_locked_wallet_is_rejected(): void
    {
        [$buyer, $order] = $this->pendingOrder(1000);
        Wallet::where('user_id', $buyer->id)->update(['status' => 'active', 'locked_until' => now()->addHour()]);
        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'wallet'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'WALLET_INACTIVE');

        $this->assertSame(1000.0, $this->walletBalance($buyer));
    }

    public function test_provider_refuses_inactive_wallet_in_validate_and_process(): void
    {
        [$buyer, $order] = $this->pendingOrder(1000);
        $transaction = app(PaymentService::class)->createOrderPayment($order, 'wallet');
        $provider = new WalletPaymentProvider;

        Wallet::where('user_id', $buyer->id)->update(['status' => 'locked']);

        try {
            $provider->validate($transaction, []);
            $this->fail('validate() ต้องปฏิเสธกระเป๋าที่ล็อก');
        } catch (\Exception $e) {
            $this->assertSame(WalletPaymentProvider::ERROR_WALLET_INACTIVE, $e->getMessage());
        }

        // แอดมินระงับกระเป๋าระหว่าง validate() กับ process() → process ต้องตรวจซ้ำหลังล็อก
        try {
            $provider->process($transaction, []);
            $this->fail('process() ต้องปฏิเสธกระเป๋าที่ล็อก');
        } catch (\Exception $e) {
            $this->assertSame(WalletPaymentProvider::ERROR_WALLET_INACTIVE, $e->getMessage());
        }

        $this->assertSame(1000.0, $this->walletBalance($buyer));
    }

    public function test_double_initialize_on_paid_order_is_refused_and_charged_once(): void
    {
        [$buyer, $order] = $this->pendingOrder(1000);
        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'wallet'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.payment_status', 'paid');

        // กดซ้ำจากแอป/เว็บ
        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'wallet'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ALREADY_PAID');
        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'promptpay'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ALREADY_PAID');

        $this->assertSame(800.0, $this->walletBalance($buyer));
        $this->assertSame(1, $this->debitCount($order));
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_second_wallet_attempt_after_uncommitted_order_update_does_not_charge_twice(): void
    {
        [$buyer, $order] = $this->pendingOrder(1000);

        // จำลอง: คำขอแรก (อีกช่องทาง) หักเงินของออเดอร์นี้ไปแล้ว แต่ completePayment ยังไม่ตั้งออเดอร์เป็นจ่ายแล้ว
        $wallet = Wallet::where('user_id', $buyer->id)->first();
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $buyer->id,
            'type' => 'withdrawal',
            'amount' => 200,
            'balance_before' => 1000,
            'balance_after' => 800,
            'description' => 'Payment for Order',
            'status' => 'completed',
            'completed_at' => now(),
            'metadata' => ['order_id' => $order->id],
        ]);
        $wallet->forceFill(['balance' => 800])->save();

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'wallet'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ALREADY_PAID');

        $this->assertSame(800.0, $this->walletBalance($buyer));
        $this->assertSame(1, $this->debitCount($order));
    }

    public function test_wallet_payment_cancels_pending_promptpay_bill_of_same_order(): void
    {
        config(['smschecker.enabled' => true]);
        [$buyer, $order] = $this->pendingOrder(1000);
        $service = app(PaymentService::class);

        // บิลพร้อมเพย์ค้าง (QR ที่สร้างไว้ก่อนเปลี่ยนใจไปจ่ายวอลเลต)
        $promptpay = $service->createOrderPayment($order, 'promptpay');
        $service->processPayment($promptpay->fresh(), []);
        $promptpay->refresh();
        $this->assertContains($promptpay->status, ['pending', 'processing']);
        $this->assertSame(1, UniquePaymentAmount::where('transaction_id', $promptpay->id)
            ->where('transaction_type', 'order_payment')
            ->where('status', 'reserved')
            ->count(), 'บิลพร้อมเพย์ต้องจองยอดทศนิยมไว้ก่อน (ไม่งั้นเทสต์ไม่ได้พิสูจน์การปล่อยยอด)');

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'wallet'])
            ->assertOk()
            ->assertJsonPath('data.order.payment_status', 'paid');

        $promptpay->refresh();
        $this->assertSame('cancelled', $promptpay->status);
        $this->assertSame(0, UniquePaymentAmount::where('transaction_id', $promptpay->id)
            ->where('transaction_type', 'order_payment')
            ->where('status', 'reserved')
            ->count(), 'ยอดจองทศนิยมของบิลพร้อมเพย์ต้องถูกปล่อย');

        // SMS พร้อมเพย์ (ยอดทศนิยมของบิลที่ยกเลิก) มาทีหลัง → ต้องจับคู่ไม่ได้ ไม่ตัดบิลซ้ำ
        $sms = SmsPaymentNotification::create([
            'bank' => 'KBANK',
            'type' => 'credit',
            'amount' => (float) $promptpay->amount,
            'device_id' => 'SMSCHK-R2PHOTO1',
            'nonce' => 'r2-photo-late-sms',
            'sms_timestamp' => now()->addMinute(),
            'status' => 'pending',
        ]);
        $this->assertFalse($sms->attemptMatch(true));
        $this->assertSame('cancelled', $promptpay->fresh()->status);

        $this->assertSame(800.0, $this->walletBalance($buyer));
        $this->assertSame(1, PaymentTransaction::where('order_id', $order->id)->where('status', 'completed')->count());
    }

    public function test_wallet_debit_is_refunded_when_order_was_paid_by_another_channel_first(): void
    {
        [$buyer, $order] = $this->pendingOrder(1000);
        $service = app(PaymentService::class);

        // วอลเลตหักเงินแล้ว (process commit) แต่ยังไม่ completePayment
        $walletTxn = $service->createOrderPayment($order, 'wallet');
        (new WalletPaymentProvider)->process($walletTxn, []);
        $this->assertSame(800.0, $this->walletBalance($buyer));

        // ระหว่างนั้น SMS พร้อมเพย์จับคู่บิลอีกใบของออเดอร์เดียวกันได้ก่อน
        $promptpay = $service->createOrderPayment($order->fresh(), 'promptpay');
        $service->completePayment($promptpay);
        $this->assertSame('paid', $order->fresh()->payment_status);

        // completePayment ของวอลเลตตามมา → ออเดอร์จ่ายแล้ว → คืนเงินเข้ากระเป๋า ไม่แตะออเดอร์
        $service->completePayment($walletTxn->fresh());

        $this->assertSame(1000.0, $this->walletBalance($buyer));
        $this->assertSame('refunded', $walletTxn->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_other_users_order_is_not_found(): void
    {
        [, $order] = $this->pendingOrder(1000);
        $stranger = $this->makeBuyer(1000);
        Sanctum::actingAs($stranger);

        $this->postJson('/api/v1/payment/order', ['order_id' => $order->id, 'payment_method' => 'wallet'])
            ->assertNotFound()
            ->assertJsonPath('code', 'ORDER_NOT_FOUND');

        $this->assertSame(1000.0, $this->walletBalance($stranger));
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * ผู้ซื้อ (มีเงินในกระเป๋า) + ออเดอร์ 200 บาทที่ยังไม่จ่าย (พร้อมเพย์)
     *
     * @return array{0: User, 1: Order}
     */
    private function pendingOrder(float $balance): array
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 100, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer($balance);

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 2]], [
            'payment_method' => 'promptpay',
        ]);

        return [$buyer, $order];
    }

    private function debitCount(Order $order): int
    {
        return WalletTransaction::where('type', 'withdrawal')
            ->where('status', 'completed')
            ->where(function ($q) use ($order) {
                $q->where(fn ($r) => $r->where('reference_type', 'order')->where('reference_id', $order->id))
                    ->orWhere('metadata->order_id', $order->id);
            })
            ->count();
    }
}
