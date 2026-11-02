<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * กันช่องโหว่ "ผู้ซื้ออนุมัติบิลตัวเอง" (F10 — ออดิทเงิน MLM 2026-11-03)
 *
 * เดิม POST /payment/callback/{transactionId} ส่งข้อมูลจากผู้ใช้เข้า provider->verify() ตรงๆ
 * ผู้ซื้อที่ login อยู่ส่ง {status:success, approved_by:x} → บิลโอนเงินเป็น "จ่ายแล้ว" ทั้งที่ไม่มีเงินเข้า
 * (COD ใช้ {delivered:true}, พร้อมเพย์ใช้ ref_no + amount ที่เห็นบนหน้าจ่าย) → ระบบตั้ง escrow/MLM pool/โบนัสแนะนำ
 *
 * หลังแก้: route ถูกถอด · gateway ใช้ /api/webhook/* ที่ตรวจลายเซ็น · สลิปโอนอนุมัติผ่าน admin เท่านั้น
 */
class PaymentCallbackSelfApproveTest extends TestCase
{
    use BuildsShopFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Mail::fake();
    }

    public function test_generic_payment_callback_route_no_longer_exists(): void
    {
        $this->assertFalse(Route::has('payment.callback'));
    }

    /**
     * ผู้ซื้อเอง และผู้ใช้อื่น ยิงอนุมัติบิลโอนเงิน / COD / พร้อมเพย์ ต้องไม่ทำให้ออเดอร์เป็นจ่ายแล้ว
     */
    public function test_buyer_or_stranger_cannot_mark_order_paid_through_callback(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 1000, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer();
        $stranger = User::factory()->create();

        $cases = [
            'bank_transfer' => ['status' => 'success', 'approved_by' => 'me'],
            'cash_on_delivery' => ['delivered' => true],
            'promptpay' => ['status' => 'paid'],
        ];

        foreach ($cases as $method => $payload) {
            $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], ['payment_method' => $method]);
            $tx = app(PaymentService::class)->createOrderPayment($order, $method);
            if ($method === 'promptpay') {
                $payload += ['ref_no' => $tx->fresh()->promptpay_ref_no, 'amount' => (float) $tx->amount];
            }

            foreach ([$buyer, $stranger] as $actor) {
                $this->actingAs($actor)
                    ->post('/payment/callback/'.$tx->transaction_id, $payload)
                    ->assertNotFound();
            }

            $this->assertNotSame('paid', $order->fresh()->payment_status, "{$method}: ออเดอร์ต้องไม่เป็นจ่ายแล้ว");
            $this->assertNotSame('completed', $tx->fresh()->status, "{$method}: transaction ต้องไม่ completed");
        }
    }

    /**
     * webhook ของ gateway ที่ไม่มีลายเซ็น ต้องโดนปฏิเสธ (ยืนยันว่าทางเข้าที่เหลืออยู่ปลอดภัย)
     */
    public function test_unsigned_gateway_webhook_is_rejected(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 1000, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer();
        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], ['payment_method' => 'bank_transfer']);
        $tx = app(PaymentService::class)->createOrderPayment($order, 'bank_transfer');

        $this->postJson('/api/webhook/paysolutions', ['order_id' => $tx->transaction_id, 'status' => 'success', 'approved_by' => 'x'])
            ->assertForbidden();
        $this->postJson('/api/webhook/promptpay', ['ref_no' => 'x', 'status' => 'paid'])
            ->assertForbidden();

        $this->assertNotSame('paid', $order->fresh()->payment_status);
    }
}
