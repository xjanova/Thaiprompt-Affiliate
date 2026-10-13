<?php

namespace Tests\Feature\TpixPay;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\TpixPayment;
use App\Models\Wallet;
use App\Services\Payment\PaymentService;
use App\Services\TpixPay\TpixAmount;
use App\Services\TpixPay\TpixPaymentService;
use App\Services\TpixPay\TpixPaySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 🪙 ชำระด้วยเหรียญ TPIX — จำลองเชน 4289 (RPC + explorer + อัตราแลกเปลี่ยน) ด้วย Http::fake (ต้องใช้ MySQL — รันบน CI)
 *
 * ราคาจำลอง: พูล 199.9 TPIX / 19.99 USDT = 0.10 USD × 32.5 บาท = 3.25 บาทต่อ TPIX
 * ออเดอร์ 1,000 บาท → 1000 / 3.25 = 307.6923… → ปัดขึ้น 307.6924 + เลขท้ายเฉพาะใบ
 */
class TpixPaymentFlowTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private const RECEIVE = '0x1111111111111111111111111111111111111111';

    private const OTHER = '0x2222222222222222222222222222222222222222';

    private const PAIR = '0x3684E0eBD1bd7eF7B7783e695639BADCD04BC4f0';

    /** @var array{latest: int, txs: array<string, array<string, mixed>>, explorer: array<int, array<string, mixed>>, fx_ok: bool, blocks: array<int, int>} */
    private array $chain;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Notification::fake();
        Cache::flush();
        $this->setGpRate(10);

        // จำลองเชนด้วย Http::fake → อนุญาตให้บริการราคาเรียกเครือข่าย (ค่าเริ่มต้นในเทสต์ปิดไว้)
        config(['tpix_pay.allow_network_in_tests' => true]);
        $this->chain = ['latest' => 1000, 'txs' => [], 'explorer' => [], 'fx_ok' => true, 'blocks' => []];
        $this->fakeNetwork();

        app(TpixPaySettings::class)->save([
            'enabled' => true,
            'show_prices' => true,
            'receive_address' => self::RECEIVE,
            'confirmations' => 3,
            'max_per_payment_thb' => 20000,
            'max_per_user_day_thb' => 50000,
        ]);
    }

    // =====================================================
    // ใบเสนอยอด
    // =====================================================

    public function test_quote_uses_real_pool_price_and_unique_exact_amount(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);

        $this->assertSame('1000.00', (string) $payment->amount_thb);
        $this->assertSame(0, bccomp('3.25', (string) $payment->rate_thb, 8), 'ราคา = 0.1 USD × 32.5');
        // 307.6924 ≤ ยอด ≤ 307.69249999 (เลขท้าย 1–9999 ที่ทศนิยม 5–8)
        $this->assertSame(1, bccomp((string) $payment->amount_tpix, '307.6924', 18));
        $this->assertSame(-1, bccomp((string) $payment->amount_tpix, '307.6925', 18));
        $this->assertSame(TpixAmount::tpixToWei((string) $payment->amount_tpix), $payment->amount_wei);
        $this->assertSame(TpixPayment::openKeyFor(self::RECEIVE, $payment->amount_wei), $payment->open_key);
        $this->assertSame(strtolower(self::RECEIVE), strtolower($payment->receive_address));
        $this->assertSame('tpix', $order->fresh()->payment_method);

        // ออเดอร์ยอดเท่ากันอีกใบ → ยอดเหรียญไม่ซ้ำ
        [, $second] = $this->pendingTpixOrder(1000);
        $this->assertNotSame($payment->amount_wei, $second->amount_wei);
    }

    public function test_quote_is_refused_without_fx_rate_and_without_hardcoded_fallback(): void
    {
        $this->chain['fx_ok'] = false;
        Cache::flush();

        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 500, 'stock_quantity' => 5]);
        $order = $this->makeOrder($this->makeBuyer(), [['product' => $product, 'qty' => 1]], ['payment_method' => 'tpix']);

        $service = app(PaymentService::class);
        $result = $service->processPayment($service->createOrderPayment($order, 'tpix'), []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ราคา TPIX', $result['message']);
        $this->assertSame(0, TpixPayment::count());
    }

    public function test_quote_respects_per_payment_limit(): void
    {
        app(TpixPaySettings::class)->save(['max_per_payment_thb' => 500]);

        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 1000, 'stock_quantity' => 5]);
        $order = $this->makeOrder($this->makeBuyer(), [['product' => $product, 'qty' => 1]], ['payment_method' => 'tpix']);

        $service = app(PaymentService::class);
        $result = $service->processPayment($service->createOrderPayment($order, 'tpix'), []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ไม่เกิน 500.00 บาทต่อรายการ', $result['message']);
    }

    // =====================================================
    // ตรวจธุรกรรม + ส่งมอบ
    // =====================================================

    public function test_valid_transfer_pays_the_order_through_payment_service_and_deducts_stock_once(): void
    {
        [$order, $payment, $product] = $this->pendingTpixOrder(1000, 5);
        $hash = $this->sendTx(self::RECEIVE, $payment->amount_wei, confirmations: 5);

        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame('paid', $result['status'], $result['message']);
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertNotNull($order->stock_deducted_at);
        $this->assertSame(4, (int) $product->fresh()->stock_quantity, 'สต็อก 5 − 1');

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame($hash, $payment->tx_hash);
        $this->assertSame('completed', PaymentTransaction::find($payment->payment_transaction_id)->status);

        // ส่งซ้ำ = ไม่ทำอะไรเพิ่ม (idempotent)
        $again = app(TpixPaymentService::class)->submitTx($payment->fresh(), $hash);
        $this->assertSame('paid', $again['status']);
        $this->assertSame(4, (int) $product->fresh()->stock_quantity);
    }

    public function test_amount_off_by_one_wei_is_rejected(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);
        $hash = $this->sendTx(self::RECEIVE, bcadd($payment->amount_wei, '1', 0), confirmations: 5);

        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame('rejected', $result['status']);
        $this->assertStringContainsString('ไม่ตรงกับยอดที่ต้องชำระ', $result['message']);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertNull($payment->fresh()->tx_hash);
    }

    public function test_transfer_to_another_address_is_rejected(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);
        $hash = $this->sendTx(self::OTHER, $payment->amount_wei, confirmations: 5);

        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame('rejected', $result['status']);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_failed_receipt_is_rejected(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);
        $hash = $this->sendTx(self::RECEIVE, $payment->amount_wei, confirmations: 5, status: '0x0');

        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame('rejected', $result['status']);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_one_transaction_can_pay_only_one_payment(): void
    {
        [, $first] = $this->pendingTpixOrder(1000);
        [$secondOrder, $second] = $this->pendingTpixOrder(1000);
        $hash = $this->sendTx(self::RECEIVE, $first->amount_wei, confirmations: 5);

        $this->assertSame('paid', app(TpixPaymentService::class)->submitTx($first, $hash)['status']);

        $result = app(TpixPaymentService::class)->submitTx($second, $hash);
        $this->assertSame('rejected', $result['status']);
        $this->assertStringContainsString('ถูกใช้ชำระรายการอื่น', $result['message']);
        $this->assertSame('pending', $secondOrder->fresh()->payment_status);
    }

    public function test_transaction_older_than_the_quote_is_rejected(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);
        $hash = $this->sendTx(self::RECEIVE, $payment->amount_wei, confirmations: 5, timestamp: now()->subHour()->getTimestamp());

        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame('rejected', $result['status']);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_waits_for_confirmations_then_the_scanner_settles(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);
        $hash = $this->sendTx(self::RECEIVE, $payment->amount_wei, confirmations: 1);

        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);
        $this->assertSame('confirming', $result['status']);
        $this->assertSame('confirming', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);

        $this->chain['latest'] += 5;
        app(TpixPaymentService::class)->scan();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_scanner_finds_a_transfer_the_customer_never_reported(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);
        $hash = $this->sendTx(self::RECEIVE, $payment->amount_wei, confirmations: 5);
        $this->chain['explorer'][] = ['hash' => $hash, 'value' => $payment->amount_wei, 'status' => 'ok', 'result' => 'success', 'timestamp' => now()->toIso8601String()];

        $stats = app(TpixPaymentService::class)->scan();

        $this->assertSame(1, $stats['matched']);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame($hash, $payment->fresh()->tx_hash);
    }

    public function test_coins_arriving_after_the_order_was_cancelled_are_credited_to_the_wallet(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);
        $order->forceFill(['status' => 'cancelled'])->saveQuietly();
        $hash = $this->sendTx(self::RECEIVE, $payment->amount_wei, confirmations: 5);

        $result = app(TpixPaymentService::class)->submitTx($payment, $hash);

        $this->assertSame('credited_wallet', $result['status']);
        $this->assertSame('credited_wallet', $payment->fresh()->status);
        $this->assertEquals(1000.0, round((float) Wallet::where('user_id', $order->user_id)->value('balance'), 2));
        $this->assertNotSame('paid', $order->fresh()->payment_status);
    }

    public function test_late_transfer_is_credited_at_the_current_price_and_pays_the_order_from_the_wallet(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);
        $this->travel(40)->minutes();
        $hash = $this->sendTx(self::RECEIVE, $payment->amount_wei, confirmations: 5);

        $result = app(TpixPaymentService::class)->submitTx($payment->fresh(), $hash);

        $this->assertSame('credited_wallet', $result['status'], $result['message']);
        $payment->refresh();
        $this->assertTrue((bool) ($payment->meta['late'] ?? false));
        // มูลค่าปัจจุบัน = 307.6924xxxx × 3.25 ≥ 1000.00 → จ่ายออเดอร์จากกระเป๋าให้อัตโนมัติ
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertLessThan(0.01, (float) Wallet::where('user_id', $order->user_id)->value('balance'));
    }

    public function test_api_status_and_submit_endpoints_work_with_the_reference(): void
    {
        [$order, $payment] = $this->pendingTpixOrder(1000);

        $this->getJson('/api/v1/tpix/payments/'.$payment->reference)
            ->assertOk()
            ->assertJsonPath('data.reference', $payment->reference)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount_wei', $payment->amount_wei)
            ->assertJsonPath('data.chain_id', 4289);

        $hash = $this->sendTx(self::RECEIVE, $payment->amount_wei, confirmations: 5);
        $this->postJson('/api/v1/tpix/payments/'.$payment->reference.'/submit', ['tx_hash' => $hash])
            ->assertOk()
            ->assertJsonPath('result', 'paid');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->getJson('/api/v1/tpix/payments/TPXNOTAREALREFERENCE00000')->assertNotFound();
    }

    public function test_price_endpoint_reports_the_dex_price(): void
    {
        $this->getJson('/api/v1/tpix/price')
            ->assertOk()
            ->assertJsonPath('data.chain_id', 4289)
            ->assertJsonPath('data.payments_enabled', true)
            ->assertJsonPath('data.price_thb_display', '฿3.25');
    }

    public function test_app_checkout_with_tpix_creates_a_pending_order_and_a_quote_without_touching_stock(): void
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => 250, 'stock_quantity' => 10]);
        $buyer = $this->makeBuyer();
        $address = $this->makeAddress($buyer);
        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $response = $this->postJson('/api/v1/cart/checkout', ['address_id' => $address->id, 'payment_method' => 'tpix']);

        $response->assertCreated()
            ->assertJsonPath('data.payment_method', 'tpix')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.payments.0.payment_method', 'tpix');

        $reference = $response->json('data.payments.0.tpix.reference');
        $this->assertNotEmpty($reference);
        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame('tpix', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->stock_deducted_at);
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, TpixPayment::where('reference', $reference)->where('payable_id', $order->id)->count());
    }

    public function test_pay_page_renders_without_login_and_shows_the_exact_amount_and_calculation(): void
    {
        [, $payment] = $this->pendingTpixOrder(1000);

        $exact = rtrim(rtrim(bcadd((string) $payment->amount_tpix, '0', 8), '0'), '.');

        $this->get('/tpix-pay/'.$payment->reference)
            ->assertOk()
            ->assertSee($exact)
            ->assertSee(self::RECEIVE)
            ->assertSee('1 TPIX = ฿3.25', false)
            ->assertSee('ชำระด้วยเหรียญ TPIX');

        $this->get('/tpix-pay/'.$payment->reference.'/qr.svg')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->get('/tpix-pay/TPXNOTAREALREFERENCE00000')->assertNotFound();
    }

    public function test_admin_page_saves_settings_and_lists_payments(): void
    {
        [, $payment] = $this->pendingTpixOrder(1000);
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/tpix-pay')
            ->assertOk()
            ->assertSee($payment->reference)
            ->assertSee('฿3.25', false);

        // เปิดรับโดยไม่มีกระเป๋า → ไม่ยอม
        $this->actingAs($admin)->post('/admin/tpix-pay/settings', [
            'enabled' => '1',
            'receive_address' => '',
            'confirmations' => 3,
            'max_per_payment_thb' => 1000,
            'max_per_user_day_thb' => 5000,
        ])->assertSessionHas('error');

        $this->actingAs($admin)->post('/admin/tpix-pay/settings', [
            'enabled' => '1',
            'show_prices' => '1',
            'receive_address' => self::OTHER,
            'confirmations' => 5,
            'max_per_payment_thb' => 1000,
            'max_per_user_day_thb' => 5000,
        ])->assertSessionHas('success');

        $settings = app(TpixPaySettings::class);
        $settings->flush();
        $this->assertSame(self::OTHER, $settings->receiveAddress());
        $this->assertSame(5, $settings->confirmations());
        $this->assertEquals(1000.0, $settings->maxPerPaymentThb());
    }

    public function test_abandoned_tpix_order_is_cancelled_after_the_grace_window(): void
    {
        [$order] = $this->pendingTpixOrder(1000);

        $this->travel((int) config('tpix_pay.quote_ttl_minutes') + (int) config('tpix_pay.late_grace_minutes') + 5)->minutes();
        $stats = app(TpixPaymentService::class)->scan();

        $this->assertSame(1, $stats['cancelled_orders']);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * @return array{0: Order, 1: TpixPayment, 2: \App\Models\Product}
     */
    private function pendingTpixOrder(float $price, int $stock = 5): array
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store, ['price' => $price, 'stock_quantity' => $stock]);
        $order = $this->makeOrder($this->makeBuyer(), [['product' => $product, 'qty' => 1]], ['payment_method' => 'tpix']);

        $service = app(PaymentService::class);
        $result = $service->processPayment($service->createOrderPayment($order, 'tpix'), []);
        $this->assertTrue($result['success'], (string) ($result['message'] ?? ''));

        $payment = TpixPayment::where('payable_id', $order->id)->latest('id')->firstOrFail();

        return [$order, $payment, $product];
    }

    private function sendTx(string $to, string $valueWei, int $confirmations, string $status = '0x1', ?int $timestamp = null): string
    {
        $hash = '0x'.bin2hex(random_bytes(32));
        $block = $this->chain['latest'] - $confirmations + 1;
        $this->chain['blocks'][$block] = $timestamp ?? now()->getTimestamp();
        $this->chain['txs'][$hash] = [
            'hash' => $hash,
            'from' => '0x9999999999999999999999999999999999999999',
            'to' => $to,
            'value' => TpixAmount::decToHex($valueWei),
            'blockNumber' => '0x'.dechex($block),
            'chainId' => '0x10c1',
            'status' => $status,
        ];

        return $hash;
    }

    private function fakeNetwork(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, 'er-api.com') || str_contains($url, 'exchangerate-api.com')) {
                return $this->chain['fx_ok']
                    ? Http::response(['rates' => ['THB' => 32.5]])
                    : Http::response('down', 503);
            }

            if (str_contains($url, 'explorer.tpix.online')) {
                return Http::response(['items' => $this->chain['explorer'], 'next_page_params' => null]);
            }

            if (str_contains($url, 'rpc')) {
                $body = $request->data();
                $method = $body['method'] ?? '';
                $params = $body['params'] ?? [];

                $result = match ($method) {
                    'eth_chainId' => '0x10c1',
                    'eth_blockNumber' => '0x'.dechex($this->chain['latest']),
                    'eth_call' => $this->ethCall($params[0]['data'] ?? ''),
                    'eth_getTransactionByHash' => isset($this->chain['txs'][$params[0]]) ? array_diff_key($this->chain['txs'][$params[0]], ['status' => 1]) : null,
                    'eth_getTransactionReceipt' => isset($this->chain['txs'][$params[0]])
                        ? ['status' => $this->chain['txs'][$params[0]]['status'], 'blockNumber' => $this->chain['txs'][$params[0]]['blockNumber']]
                        : null,
                    'eth_getBlockByNumber' => ['timestamp' => '0x'.dechex($this->chain['blocks'][hexdec($params[0])] ?? now()->getTimestamp())],
                    default => null,
                };

                return Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => $result]);
            }

            return Http::response([], 404);
        });
    }

    private function ethCall(string $data): string
    {
        if ($data === '0x0dfe1681') { // token0() = WTPIX
            return '0x'.str_pad(substr(strtolower((string) config('tpix_pay.dex.wtpix')), 2), 64, '0', STR_PAD_LEFT);
        }

        // getReserves(): 199.9 TPIX (18) / 19.99 USDT (6)
        $r0 = substr(TpixAmount::decToHex('199900000000000000000'), 2);
        $r1 = substr(TpixAmount::decToHex('19990000'), 2);

        return '0x'.str_pad($r0, 64, '0', STR_PAD_LEFT).str_pad($r1, 64, '0', STR_PAD_LEFT).str_pad(dechex(time()), 64, '0', STR_PAD_LEFT);
    }
}
