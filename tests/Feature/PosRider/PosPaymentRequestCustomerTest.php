<?php

namespace Tests\Feature\PosRider;

use App\Models\Order;
use App\Models\OrderTrackingHistory;
use App\Models\PosDeliveryRequest;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\VendorStore;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\DeliveryFeeCalculator;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;

/**
 * POS → ไรเดอร์ Thai Prompt — ฝั่งลูกค้าในแอป (ดูใบเสนอราคา / จ่ายจากกระเป๋าเงิน)
 */
#[Group('pos-rider')]
class PosPaymentRequestCustomerTest extends PosRiderTestCase
{
    /**
     * ร้าน + เครื่อง POS + สินค้า 2 ชิ้น (60 × 2) + คำขอที่รอจ่าย
     *
     * @return array{store: VendorStore, headers: array<string, string>, id: int, token: string, product: \App\Models\Product}
     */
    private function pendingRequest(array $extra = []): array
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store, ['name' => 'ข้าวมันไก่', 'price' => 60, 'stock_quantity' => 10]);

        [$id, $token] = $this->createRequest($headers, [['product_id' => $product->id, 'qty' => 2]], array_merge([
            'order_local_id' => 'POS-000123',
            'note' => 'หน้าบ้านสีฟ้า',
        ], $extra));

        return ['store' => $store, 'headers' => $headers, 'id' => $id, 'token' => $token, 'product' => $product];
    }

    private function expectedFee(VendorStore $store, float $lat, float $lng): float
    {
        return round((float) app(DeliveryFeeCalculator::class)->quote(
            (float) $store->pickup_latitude,
            (float) $store->pickup_longitude,
            $lat,
            $lng
        )['total_fee'], 2);
    }

    // =====================================================
    // ใบเสนอราคา
    // =====================================================

    /**
     * ยังไม่มีที่อยู่ → address=null · ไรเดอร์ใช้ไม่ได้ + ข้อความให้เลือกที่อยู่ที่ปักหมุด · ไม่บอกยอดเงิน
     */
    public function test_quote_without_address_asks_for_pinned_address(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);

        $response = $this->customerShow($customer, $r['token'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.store.id', $r['store']->id)
            ->assertJsonPath('data.items.0.name', 'ข้าวมันไก่')
            ->assertJsonPath('data.items.0.qty', 2)
            ->assertJsonPath('data.note', 'หน้าบ้านสีฟ้า')
            ->assertJsonPath('data.address', null)
            ->assertJsonPath('data.rider.available', false)
            ->assertJsonPath('data.rider.fee', null)
            ->assertJsonPath('data.rider.message', 'กรุณาเลือกที่อยู่ที่ปักหมุดไว้')
            ->assertJsonPath('data.wallet.balance_ok', true);

        $this->assertEqualsWithDelta(120.0, $response->json('data.subtotal'), 0.001);
        $this->assertEqualsWithDelta(120.0, $response->json('data.total'), 0.001);
        $this->assertSame(['balance_ok'], array_keys($response->json('data.wallet')));
        $this->assertStringNotContainsString('"balance"', $response->getContent(), 'ห้ามบอกยอดเงินคงเหลือ');
    }

    /**
     * ที่อยู่ยังไม่ปักหมุด → ไรเดอร์ใช้ไม่ได้ + "ที่อยู่นี้ยังไม่ได้ปักหมุด"
     */
    public function test_quote_with_unpinned_address(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer, false);

        $this->customerShow($customer, $r['token'], $address->id)
            ->assertOk()
            ->assertJsonPath('data.address.id', $address->id)
            ->assertJsonPath('data.address.has_location', false)
            ->assertJsonPath('data.rider.available', false)
            ->assertJsonPath('data.rider.message', 'ที่อยู่นี้ยังไม่ได้ปักหมุด');
    }

    /**
     * ที่อยู่ปักหมุดแล้ว → ค่าส่งตามสูตรหลังบ้าน (DeliveryFeeCalculator) · token มี/ไม่มี TPPOS1. ก็ได้
     */
    public function test_quote_with_pinned_address_uses_backend_fee_formula(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer);
        $fee = $this->expectedFee($r['store'], (float) $address->latitude, (float) $address->longitude);
        $this->assertGreaterThan(0, $fee);

        foreach ([$r['token'], 'TPPOS1.'.$r['token']] as $token) {
            $response = $this->customerShow($customer, $token, $address->id)
                ->assertOk()
                ->assertJsonPath('data.token', $r['token'])
                ->assertJsonPath('data.address.id', $address->id)
                ->assertJsonPath('data.address.has_location', true)
                ->assertJsonPath('data.rider.available', true)
                ->assertJsonPath('data.rider.message', null)
                ->assertJsonPath('data.wallet.balance_ok', true);

            $this->assertEqualsWithDelta($fee, $response->json('data.rider.fee'), 0.001);
            $this->assertEqualsWithDelta(120.0 + $fee, $response->json('data.total'), 0.001);
            $this->assertGreaterThan(0, $response->json('data.rider.distance_km'));
        }

        // เงินไม่พอ → แค่ balance_ok=false
        $poor = $this->makeCustomer(10);
        $poorAddress = $this->makeAddress($poor);
        $this->customerShow($poor, $r['token'], $poorAddress->id)
            ->assertOk()
            ->assertJsonPath('data.wallet.balance_ok', false);
    }

    /**
     * ไม่รู้จัก token → 404 REQUEST_NOT_FOUND
     */
    public function test_unknown_token_returns_404(): void
    {
        $customer = $this->makeCustomer();

        $this->customerShow($customer, Str::random(40))
            ->assertNotFound()
            ->assertJsonPath('code', 'REQUEST_NOT_FOUND');

        $this->customerShow($customer, 'TPPOS1.short')
            ->assertNotFound()
            ->assertJsonPath('code', 'REQUEST_NOT_FOUND');
    }

    // =====================================================
    // จ่าย
    // =====================================================

    /**
     * จ่ายสำเร็จ: กระเป๋าถูกตัด (สินค้า + ค่าส่ง) ครั้งเดียว · ออเดอร์ร้านค้าผ่าน checkout เส้นเดิม (source pos)
     * · คำขอเป็น paid · สร้างงานไรเดอร์ + ประวัติ "ร้านเรียกไรเดอร์แล้ว" · POS เห็นสถานะใหม่
     */
    public function test_pay_happy_path_charges_wallet_creates_order_and_rider_job(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $customer->forceFill(['name' => 'สมหญิง ใจดี'])->save();
        $address = $this->makeAddress($customer, true, ['recipient_name' => 'สมหญิง ใจดี']);
        $fee = $this->expectedFee($r['store'], (float) $address->latitude, (float) $address->longitude);
        $total = round(120.0 + $fee, 2);

        $response = $this->customerPay($customer, $r['token'], $address->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $order = Order::findOrFail($response->json('data.order_id'));
        $this->assertSame($order->order_number, $response->json('data.order_number'));
        $this->assertSame('/order/'.$order->id, $response->json('data.track_path'));
        $this->assertEqualsWithDelta($total, $response->json('data.total'), 0.001);

        // ออเดอร์ร้านค้า: จ่ายด้วยกระเป๋า + ส่งไรเดอร์ + ผูกเครื่อง POS
        $this->assertSame((int) $customer->id, (int) $order->user_id);
        $this->assertSame((int) $r['store']->id, (int) $order->store_id);
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('rider', $order->delivery_method);
        $this->assertEqualsWithDelta(120.0, (float) $order->subtotal, 0.001);
        $this->assertEqualsWithDelta($fee, (float) $order->shipping_fee, 0.001);
        $this->assertEqualsWithDelta($total, (float) $order->total_amount, 0.001);
        $this->assertSame('หน้าบ้านสีฟ้า', $order->customer_notes);
        $this->assertSame((int) PosDeliveryRequest::findOrFail($r['id'])->pos_terminal_id, (int) $order->getAttribute('pos_terminal_id'));
        $this->assertSame('POS-000123', $order->getAttribute('pos_local_id'));
        $this->assertSame('processing', $order->status, 'เรียกไรเดอร์แล้ว = กำลังเตรียม');
        $this->assertTrue((bool) $order->settlement_deferred, 'ไรเดอร์รอบ 2: เงินพักไว้จนส่งมอบสำเร็จ');

        // กระเป๋าถูกตัดครั้งเดียว ตามยอด server · ช่องทาง pos
        $this->assertEqualsWithDelta(1000 - $total, $this->walletBalance($customer), 0.001);
        $debits = WalletTransaction::where('reference_type', 'order')->where('reference_id', $order->id)->get();
        $this->assertCount(1, $debits);
        $this->assertSame('pos', $debits->first()->metadata['source'] ?? null);

        // สต็อกถูกตัด
        $this->assertSame(8, (int) $r['product']->fresh()->stock_quantity);

        // คำขอเป็น paid
        $request = PosDeliveryRequest::findOrFail($r['id']);
        $this->assertSame('paid', $request->status);
        $this->assertSame((int) $order->id, (int) $request->order_id);
        $this->assertSame((int) $customer->id, (int) $request->paid_by_user_id);
        $this->assertNotNull($request->paid_at);

        // งานไรเดอร์ + ประวัติการติดตาม
        $job = RiderJob::forSource($order)->firstOrFail();
        $this->assertSame('shop_delivery', $job->job_type);
        $this->assertSame('pending', $job->status);
        $this->assertEqualsWithDelta($fee, (float) $job->total_fee, 0.001, 'ค่าส่งงาน = ที่ลูกค้าจ่ายจริง');
        // ไม่มี X-App-Build (หน้าเว็บ/แอปเก่า) → งานแบบเดิม ไม่ต้องสแกนส่งมอบ (กติกาไรเดอร์รอบ 2: ตัดสินตอนรับงานจาก build)
        $this->assertNull($order->getAttribute('client_app_build'));
        $this->assertFalse((bool) $job->handover_required);
        $this->assertFalse($job->handoverRequiredOnAccept(43));
        $this->assertTrue(OrderTrackingHistory::where('order_id', $order->id)->where('status', 'rider_requested')->exists());

        // POS เห็นสถานะ
        $this->getJson('/api/pos/delivery-requests/'.$r['id'], $r['headers'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.order.id', $order->id)
            ->assertJsonPath('data.order.order_number', $order->order_number)
            ->assertJsonPath('data.customer.display_name', 'สมหญิง ใ.')
            ->assertJsonPath('data.rider_job.id', $job->id)
            ->assertJsonPath('data.rider_job.status', 'searching')
            ->assertJsonPath('data.rider_job.rider', null)
            ->assertJsonPath('data.handover', null);

        // ลูกค้าเปิด QR เดิมอีกครั้ง → 200 status paid + order_id
        $this->customerShow($customer, $r['token'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.order_id', $order->id);
    }

    /**
     * จ่ายจากแอปรุ่นที่มีหน้าส่งมอบ (X-App-Build ≥ 43) → ออเดอร์เก็บ build · เงินพัก (settlement_deferred)
     * → ไรเดอร์รับงานจากแอป build ≥ 43 → งานต้องสแกนส่งมอบสองฝ่าย (handover_required) ก่อนปลดเงิน
     * ไรเดอร์รับจากหน้าเว็บ/แอปเก่า = งานแบบเดิม (กติกาเดียวกับ /cart/checkout)
     */
    public function test_new_app_payment_makes_the_rider_job_require_the_two_way_handover(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $customer->forceFill(['profile_photo_private_path' => 'profile-photos/'.$customer->id.'/live.jpg', 'profile_photo_taken_at' => now()])->save();
        $address = $this->makeAddress($customer);

        $orderId = $this->customerPay($customer, $r['token'], $address->id, self::PIN, null, ['X-App-Build' => '57'])
            ->assertOk()
            ->json('data.order_id');

        $order = Order::findOrFail($orderId);
        $this->assertSame(57, (int) $order->getAttribute('client_app_build'));
        $this->assertSame('paid', $order->payment_status);
        $this->assertTrue((bool) $order->settlement_deferred, 'เงินพักไว้จนส่งมอบสำเร็จ');

        $job = RiderJob::forSource($order)->firstOrFail();
        $this->assertFalse((bool) $job->handover_required, 'งานใหม่เริ่มแบบเดิม — ตัดสินตอนไรเดอร์รับงาน');
        $this->assertTrue($job->handoverRequiredOnAccept(43), 'ไรเดอร์แอปใหม่รับงาน → ต้องสแกนส่งมอบ');
        $this->assertFalse($job->handoverRequiredOnAccept(null), 'ไรเดอร์หน้าเว็บ/แอปเก่า → ปุ่มส่งของแบบเดิม');

        // ไรเดอร์รับงานจริงจากแอป build 43 → handover_required
        $riderUser = User::factory()->create();
        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $riderUser->id,
            'full_name' => 'สมชาย ขยันส่ง',
            'phone' => '0891235678',
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'gps_permission_granted' => true,
            'share_location_consent_at' => now(),
            'last_latitude' => (float) $r['store']->pickup_latitude,
            'last_longitude' => (float) $r['store']->pickup_longitude,
            'last_location_update' => now(),
            'approved_at' => now(),
        ])->save();

        $accepted = app(RiderJobService::class)->accept($job, $rider->fresh(), 43);
        $this->assertSame('accepted', $accepted->status);
        $this->assertTrue((bool) $accepted->fresh()->handover_required, 'ปลดเงินด้วยการสแกนส่งมอบสองฝ่ายเท่านั้น');

        // POS เห็นงานถูกรับแล้ว
        $this->getJson('/api/pos/delivery-requests/'.$r['id'], $r['headers'])
            ->assertOk()
            ->assertJsonPath('data.rider_job.status', 'assigned');
    }

    /**
     * ไรเดอร์รับงานแล้ว → POS เห็นชื่อย่อ / ทะเบียน / เบอร์ แบบปิดบางส่วนเท่านั้น
     */
    public function test_terminal_status_shows_masked_rider_after_assignment(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer);

        $orderId = $this->customerPay($customer, $r['token'], $address->id)->assertOk()->json('data.order_id');
        $job = RiderJob::forSource(Order::findOrFail($orderId))->firstOrFail();

        $riderUser = User::factory()->create();
        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $riderUser->id,
            'full_name' => 'สมชาย ขยันส่ง',
            'phone' => '0891235678',
            'status' => 'approved',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
        ])->save();
        RiderJob::whereKey($job->id)->update(['rider_id' => $rider->id, 'status' => 'accepted', 'accepted_at' => now()]);

        $response = $this->getJson('/api/pos/delivery-requests/'.$r['id'], $r['headers'])
            ->assertOk()
            ->assertJsonPath('data.rider_job.status', 'assigned')
            ->assertJsonPath('data.rider_job.rider.display_name', 'สมชาย ข.')
            ->assertJsonPath('data.rider_job.rider.plate_masked', '1กข **34')
            ->assertJsonPath('data.rider_job.rider.phone_masked', '08x-xxx-5678');

        $content = json_encode($response->json(), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('0891235678', $content);
        $this->assertStringNotContainsString('ขยันส่ง', $content);
    }

    /**
     * Idempotency-Key เดิม → ผลเดิม ไม่ตัดเงินซ้ำ
     */
    public function test_pay_with_same_idempotency_key_returns_same_result(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer);
        $key = (string) Str::uuid();

        $first = $this->customerPay($customer, $r['token'], $address->id, self::PIN, $key)->assertOk();
        $balanceAfter = $this->walletBalance($customer);
        $second = $this->customerPay($customer, $r['token'], $address->id, self::PIN, $key)->assertOk();

        $this->assertSame($first->json('data.order_id'), $second->json('data.order_id'));
        $this->assertSame($balanceAfter, $this->walletBalance($customer));
        $this->assertSame(1, Order::where('user_id', $customer->id)->count());
    }

    /**
     * จ่ายซ้ำ (คนอื่น หรือคนเดิมคนละ Idempotency-Key) → 409 REQUEST_ALREADY_PAID ไม่ตัดเงิน
     */
    public function test_second_payment_gets_409(): void
    {
        $r = $this->pendingRequest();
        $first = $this->makeCustomer(1000);
        $firstAddress = $this->makeAddress($first);
        $second = $this->makeCustomer(1000);
        $secondAddress = $this->makeAddress($second);

        $this->customerPay($first, $r['token'], $firstAddress->id)->assertOk();

        $this->customerPay($second, $r['token'], $secondAddress->id)
            ->assertStatus(409)
            ->assertJsonPath('code', 'REQUEST_ALREADY_PAID');
        $this->assertEqualsWithDelta(1000.0, $this->walletBalance($second), 0.001);

        $this->customerPay($first, $r['token'], $firstAddress->id)
            ->assertStatus(409)
            ->assertJsonPath('code', 'REQUEST_ALREADY_PAID');

        // คนอื่นเปิด QR ที่จ่ายแล้ว → 409 (ไม่เห็นรายละเอียด)
        $this->customerShow($second, $r['token'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'REQUEST_ALREADY_PAID');

        $this->assertSame(1, Order::count());
    }

    /**
     * QR หมดอายุ → 410 REQUEST_EXPIRED ไม่ตัดเงิน
     */
    public function test_expired_request_returns_410(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer);

        $this->travel(16)->minutes();

        $this->customerShow($customer, $r['token'], $address->id)
            ->assertStatus(410)
            ->assertJsonPath('code', 'REQUEST_EXPIRED');

        $this->customerPay($customer, $r['token'], $address->id)
            ->assertStatus(410)
            ->assertJsonPath('code', 'REQUEST_EXPIRED');

        $this->assertEqualsWithDelta(1000.0, $this->walletBalance($customer), 0.001);
        $this->assertSame(0, Order::count());
    }

    /**
     * ร้านยกเลิกแล้ว → 409 REQUEST_CANCELLED
     */
    public function test_cancelled_request_cannot_be_paid(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer);

        $this->postJson('/api/pos/delivery-requests/'.$r['id'].'/cancel', [], $r['headers'])->assertOk();

        $this->customerPay($customer, $r['token'], $address->id)
            ->assertStatus(409)
            ->assertJsonPath('code', 'REQUEST_CANCELLED');

        $this->assertSame(0, Order::count());
    }

    /**
     * PIN ผิด → 403 INVALID_PIN นับครั้ง (กลไกเดียวกับกระเป๋าเงิน) · คำขอยังรอจ่าย · ไม่มี Idempotency-Key → 422
     */
    public function test_wrong_pin_and_missing_idempotency_key(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer);

        $this->customerPay($customer, $r['token'], $address->id, '000000')
            ->assertStatus(403)
            ->assertJsonPath('code', 'INVALID_PIN')
            ->assertJsonPath('data.attempts_remaining', 4);

        $this->assertSame(1, (int) Wallet::where('user_id', $customer->id)->value('failed_attempts'));
        $this->assertSame('pending', PosDeliveryRequest::findOrFail($r['id'])->status);

        $this->customerPay($customer, $r['token'], $address->id, self::PIN, '')
            ->assertStatus(422)
            ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');

        // ยังไม่ตั้ง PIN
        $noPin = $this->makeCustomer(1000, null);
        $noPinAddress = $this->makeAddress($noPin);
        $this->customerPay($noPin, $r['token'], $noPinAddress->id, '123456')
            ->assertStatus(422)
            ->assertJsonPath('code', 'PIN_NOT_SET');

        $this->assertSame(0, Order::count());
    }

    /**
     * เงินไม่พอ → รหัส/ข้อความเดิมของ checkout แต่ไม่บอกยอดคงเหลือ · คำขอยังรอจ่าย
     */
    public function test_insufficient_balance_does_not_disclose_balance(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(50);
        $address = $this->makeAddress($customer);

        $response = $this->customerPay($customer, $r['token'], $address->id)
            ->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_BALANCE')
            ->assertJsonPath('message', 'ยอดเงินในกระเป๋าไม่เพียงพอ');

        $this->assertArrayNotHasKey('available', (array) $response->json('data'));
        $this->assertSame('pending', PosDeliveryRequest::findOrFail($r['id'])->status);
        $this->assertSame(0, Order::count());
        $this->assertEqualsWithDelta(50.0, $this->walletBalance($customer), 0.001);
    }

    /**
     * ที่อยู่ไม่ปักหมุดตอนจ่าย → ใช้กฎ checkout เดิม (ADDRESS_LOCATION_REQUIRED) ไม่ตัดเงิน
     */
    public function test_pay_with_unpinned_address_is_rejected_by_checkout_rules(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer, false);

        $this->customerPay($customer, $r['token'], $address->id)
            ->assertStatus(422)
            ->assertJsonPath('code', 'ADDRESS_LOCATION_REQUIRED');

        $this->assertSame('pending', PosDeliveryRequest::findOrFail($r['id'])->status);
        $this->assertEqualsWithDelta(1000.0, $this->walletBalance($customer), 0.001);
    }

    /**
     * เรียกไรเดอร์ล้ม → เงินที่จ่ายแล้วไม่หาย ลูกค้ายังได้ผลสำเร็จ (ร้าน/แอดมินกดเรียกไรเดอร์เองภายหลัง)
     */
    public function test_dispatch_failure_does_not_lose_the_payment(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer);

        $this->mock(RiderDispatchService::class, function ($mock) {
            $mock->shouldReceive('createJobForSource')->andThrow(new \RuntimeException('dispatch down'));
        });

        $response = $this->customerPay($customer, $r['token'], $address->id)->assertOk();

        $order = Order::findOrFail($response->json('data.order_id'));
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('paid', $order->status, 'ยังไม่เรียกไรเดอร์ = คงสถานะจ่ายแล้วให้ร้านกดเรียกเอง');
        $this->assertSame(0, RiderJob::count());
        $this->assertSame('paid', PosDeliveryRequest::findOrFail($r['id'])->status);
    }

    /**
     * ไรเดอร์รอบ 2: แอปรุ่นใหม่ (X-App-Build ≥ 43) ที่ยังไม่มีรูปโปรไฟล์ถ่ายสด → 422 PROFILE_PHOTO_REQUIRED
     * ตรวจก่อน PIN (PIN ผิดก็ไม่ถูกนับ) · ไม่ตัดเงิน · คำขอยังรอจ่าย · มีรูปแล้วจ่ายได้ตามปกติ
     */
    public function test_pay_requires_live_profile_photo_on_new_app_builds(): void
    {
        $r = $this->pendingRequest();
        $customer = $this->makeCustomer(1000);
        $address = $this->makeAddress($customer);
        $newApp = ['X-App-Build' => '43'];

        $this->customerPay($customer, $r['token'], $address->id, '000000', null, $newApp)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'PROFILE_PHOTO_REQUIRED')
            ->assertJsonPath('message', 'กรุณาถ่ายรูปโปรไฟล์ก่อนใช้งานส่วนนี้')
            ->assertJsonPath('data.has_photo', false);

        $this->assertSame(0, (int) Wallet::where('user_id', $customer->id)->value('failed_attempts'), 'ด่านรูปมาก่อน PIN');
        $this->assertSame('pending', PosDeliveryRequest::findOrFail($r['id'])->status);
        $this->assertSame(0, Order::count());
        $this->assertEqualsWithDelta(1000.0, $this->walletBalance($customer), 0.001);

        // ถ่ายรูปแล้ว → จ่ายได้
        $customer->forceFill(['profile_photo_private_path' => 'profile-photos/'.$customer->id.'/live.jpg', 'profile_photo_taken_at' => now()])->save();

        $this->customerPay($customer, $r['token'], $address->id, self::PIN, null, $newApp)
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSame('paid', PosDeliveryRequest::findOrFail($r['id'])->status);
    }

    /**
     * ผู้ใช้ที่ไม่ได้ล็อกอิน → 401
     */
    public function test_customer_endpoints_require_login(): void
    {
        $r = $this->pendingRequest();

        $this->getJson('/api/v1/pos-requests/'.$r['token'])->assertUnauthorized();
        $this->postJson('/api/v1/pos-requests/'.$r['token'].'/pay', ['address_id' => 1, 'pin' => '1'])->assertUnauthorized();
    }

    /**
     * ผู้ใช้คนละคนดูใบเสนอราคาเดียวกันได้ (ใครถือ QR ก็จ่ายได้) แต่ไม่เห็นข้อมูลกันและกัน
     */
    public function test_quote_does_not_expose_other_users_data(): void
    {
        $r = $this->pendingRequest(['customer_phone' => '0899999992']);
        $viewer = $this->makeCustomer(1000);

        $response = $this->customerShow($viewer, $r['token'])->assertOk();

        $this->assertStringNotContainsString('0899999992', $response->getContent());
        $this->assertArrayNotHasKey('customer_phone', (array) $response->json('data'));
        $this->assertArrayNotHasKey('target_user_id', (array) $response->json('data'));
    }
}
