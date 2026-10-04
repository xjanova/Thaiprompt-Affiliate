<?php

namespace Tests\Feature\PosRider;

use App\Models\Notification as AppNotification;
use App\Models\PosApiKey;
use App\Models\PosDeliveryRequest;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;

/**
 * POS → ไรเดอร์ Thai Prompt — ฝั่งเครื่อง POS (สร้างคำขอ / ดูสถานะ / ยกเลิก / ยืนยันเครื่อง)
 */
#[Group('pos-rider')]
class PosDeliveryRequestTerminalTest extends PosRiderTestCase
{
    /**
     * สร้างคำขอ: หาสินค้าด้วย product_id และ sku ภายในร้านของเครื่อง · ราคาใช้ของ server (ไม่สนราคา/ชื่อที่ POS ส่งมา)
     */
    public function test_create_resolves_items_by_id_and_sku_with_server_prices(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $rice = $this->makeStoreProduct($seller, $store, ['name' => 'ข้าวมันไก่', 'price' => 60]);
        $tea = $this->makeStoreProduct($seller, $store, ['name' => 'ชาเย็น', 'price' => 25, 'sku' => 'TP-TEA-01']);

        $response = $this->posCreate($headers, [
            'local_id' => 'DL-0007',
            'order_local_id' => 'POS-000123',
            'items' => [
                ['product_id' => $rice->id, 'qty' => 2, 'name' => 'ชื่อจาก POS', 'price' => 1],
                ['sku' => 'TP-TEA-01', 'qty' => 1],
            ],
            'customer_phone' => '0899999991',
            'note' => 'หน้าบ้านสีฟ้า',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.store.id', $store->id)
            ->assertJsonPath('data.store.name', $store->store_name)
            ->assertJsonPath('data.items.0.product_id', $rice->id)
            ->assertJsonPath('data.items.0.name', 'ข้าวมันไก่')
            ->assertJsonPath('data.items.0.qty', 2)
            ->assertJsonPath('data.items.1.product_id', $tea->id)
            ->assertJsonPath('data.items.1.sku', 'TP-TEA-01')
            ->assertJsonPath('data.push_sent', false);

        $this->assertEqualsWithDelta(60.0, $response->json('data.items.0.price'), 0.001);
        $this->assertEqualsWithDelta(120.0, $response->json('data.items.0.line_total'), 0.001);
        $this->assertEqualsWithDelta(145.0, $response->json('data.subtotal'), 0.001);
        $this->assertMatchesRegularExpression('/^TPPOS1\.[A-Za-z0-9]{40}$/', (string) $response->json('data.qr_payload'));
        $this->assertStringContainsString('+07:00', (string) $response->json('data.expires_at'));

        // ไม่หลุดเบอร์ลูกค้า
        $this->assertStringNotContainsString('0899999991', $response->getContent());

        $row = PosDeliveryRequest::findOrFail($response->json('data.id'));
        $this->assertSame((int) $store->id, (int) $row->store_id);
        $this->assertSame('POS-000123', $row->order_local_id);
        $this->assertSame('0899999991', $row->customer_phone);
        $this->assertSame('หน้าบ้านสีฟ้า', $row->note);
        $this->assertTrue($row->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
        $this->assertSame([$rice->id, $tea->id], array_column($row->items, 'product_id'));
    }

    /**
     * สินค้าที่ไม่มีในร้าน (ร้านอื่น / ไม่รู้จัก sku / หมด) → 422 ITEMS_NOT_IN_STORE พร้อมรายการ · ไม่สร้างคำขอ
     */
    public function test_create_with_items_not_in_store_returns_422_with_missing_list(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [$otherSeller, $otherStore] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $ok = $this->makeStoreProduct($seller, $store);
        $soldOut = $this->makeStoreProduct($seller, $store, ['name' => 'ของหมด', 'stock_quantity' => 0, 'stock_status' => 'out_of_stock']);
        $foreign = $this->makeStoreProduct($otherSeller, $otherStore);

        $this->posCreate($headers, [
            'local_id' => 'DL-0008',
            'items' => [
                ['product_id' => $ok->id, 'qty' => 1],
                ['product_id' => $foreign->id, 'qty' => 1, 'name' => 'ของร้านอื่น'],
                ['sku' => 'TP-009', 'qty' => 1, 'name' => 'ไม่มีในระบบ'],
                ['product_id' => $soldOut->id, 'qty' => 1],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ITEMS_NOT_IN_STORE')
            ->assertJsonCount(3, 'data.missing')
            ->assertJsonPath('data.missing.0.name', 'ของร้านอื่น')
            ->assertJsonPath('data.missing.1.sku', 'TP-009')
            ->assertJsonPath('data.missing.2.name', 'ของหมด');

        $this->assertSame(0, PosDeliveryRequest::count());
    }

    /**
     * ส่ง local_id เดิมซ้ำระหว่างที่ยังรอจ่าย → ได้คำขอเดิม (token เดิม) ไม่สร้างแถวใหม่
     */
    public function test_create_is_idempotent_on_local_id_while_pending(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);

        $payload = ['local_id' => 'DL-0009', 'items' => [['product_id' => $product->id, 'qty' => 1]]];
        $first = $this->posCreate($headers, $payload)->assertCreated();
        $second = $this->posCreate($headers, $payload)->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('data.qr_payload'), $second->json('data.qr_payload'));
        $this->assertSame(1, PosDeliveryRequest::count());
    }

    /**
     * คำขอเดิมหมดอายุแล้ว → local_id เดิมออก QR ใหม่ได้ (token ใหม่ QR เก่าใช้ไม่ได้)
     */
    public function test_create_after_expiry_reissues_a_new_token(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);

        $payload = ['local_id' => 'DL-0010', 'items' => [['product_id' => $product->id, 'qty' => 1]]];
        $first = $this->posCreate($headers, $payload)->assertCreated();

        $this->travel(16)->minutes();

        $second = $this->posCreate($headers, $payload)->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertNotSame($first->json('data.qr_payload'), $second->json('data.qr_payload'));
    }

    /**
     * ร้านยังไม่เปิดส่งไรเดอร์ (หรือไม่มีพิกัดจุดรับของ) → 409 RIDER_UNAVAILABLE
     */
    public function test_create_when_store_cannot_use_rider_returns_409(): void
    {
        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => false]);
        [, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);

        $this->posCreate($headers, ['local_id' => 'DL-1', 'items' => [['product_id' => $product->id, 'qty' => 1]]])
            ->assertStatus(409)
            ->assertJsonPath('code', 'RIDER_UNAVAILABLE');
    }

    /**
     * ข้อมูลไม่ครบ → 422 VALIDATION ข้อความไทย
     */
    public function test_validation_errors_return_thai_message(): void
    {
        [, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);

        $this->posCreate($headers, ['items' => []])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION')
            ->assertJsonPath('message', 'กรุณาส่งเลขอ้างอิงคำขอ (local_id)');

        $this->posCreate($headers, ['local_id' => 'X', 'items' => [['qty' => 1]]])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION');

        $this->posCreate($headers, ['local_id' => 'X', 'items' => [['product_id' => 1, 'qty' => 1]], 'customer_phone' => '12345'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'เบอร์มือถือลูกค้าไม่ถูกต้อง (ตัวอย่าง 0812345678)');
    }

    /**
     * API Key ผิด / ไม่มี header / key ถูกบล็อก → 401 (ไม่บอกว่าผิดที่ไหน)
     */
    public function test_wrong_or_blocked_terminal_key_returns_401(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [$terminal, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);
        $payload = ['local_id' => 'DL-2', 'items' => [['product_id' => $product->id, 'qty' => 1]]];

        $this->posCreate(array_merge($headers, ['X-API-Key' => 'pk_wrong']), $payload)
            ->assertStatus(401)
            ->assertJsonPath('code', 'TERMINAL_UNAUTHORIZED')
            ->assertJsonPath('message', 'ไม่ได้รับอนุญาต');

        $this->postJson('/api/pos/delivery-requests', $payload)->assertStatus(401);
        $this->getJson('/api/pos/delivery-requests/1')->assertStatus(401);

        PosApiKey::whereKey($terminal->api_key_id)->update(['is_blocked' => true]);
        $this->posCreate($headers, $payload)->assertStatus(401);

        $this->assertSame(0, PosDeliveryRequest::count());
    }

    /**
     * ดูสถานะได้เฉพาะคำขอของร้านตัวเอง · ยังไม่จ่าย = order/customer/rider_job/handover เป็น null
     */
    public function test_status_is_scoped_to_own_store(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $otherStore] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        [, $otherHeaders] = $this->makeTerminal($otherStore);
        $product = $this->makeStoreProduct($seller, $store);

        [$id] = $this->createRequest($headers, [['product_id' => $product->id, 'qty' => 2]]);

        $this->getJson('/api/pos/delivery-requests/'.$id, $headers)
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.delivery_fee', null)
            ->assertJsonPath('data.total', null)
            ->assertJsonPath('data.order', null)
            ->assertJsonPath('data.customer', null)
            ->assertJsonPath('data.rider_job', null)
            ->assertJsonPath('data.handover', null);

        $this->getJson('/api/pos/delivery-requests/'.$id, $otherHeaders)
            ->assertNotFound()
            ->assertJsonPath('code', 'REQUEST_NOT_FOUND');

        $this->postJson('/api/pos/delivery-requests/'.$id.'/cancel', [], $otherHeaders)->assertNotFound();
    }

    /**
     * เกิน 15 นาที → สถานะเปลี่ยนเป็น expired ตอนอ่าน
     */
    public function test_status_auto_expires_after_fifteen_minutes(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);
        [$id] = $this->createRequest($headers, [['product_id' => $product->id, 'qty' => 1]]);

        $this->travel(16)->minutes();

        $this->getJson('/api/pos/delivery-requests/'.$id, $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'expired');

        $this->assertSame('expired', PosDeliveryRequest::findOrFail($id)->status);
    }

    /**
     * ยกเลิกได้เฉพาะที่ยังรอจ่าย → ครั้งที่สอง 409 NOT_CANCELLABLE
     */
    public function test_cancel_only_while_pending(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);
        [$id] = $this->createRequest($headers, [['product_id' => $product->id, 'qty' => 1]]);

        $this->postJson('/api/pos/delivery-requests/'.$id.'/cancel', [], $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled');

        $this->postJson('/api/pos/delivery-requests/'.$id.'/cancel', [], $headers)
            ->assertStatus(409)
            ->assertJsonPath('code', 'NOT_CANCELLABLE');

        $this->assertNotNull(PosDeliveryRequest::findOrFail($id)->cancelled_at);
    }

    /**
     * เบอร์ตรงกับผู้ใช้ที่ยืนยันเบอร์แล้วคนเดียว → push pos_payment_request (token + screen) · ไม่บอกข้อมูลผู้ใช้กลับไป
     * เบอร์ไม่มีบัญชี / ยังไม่ยืนยัน → push_sent=false
     */
    public function test_phone_match_sends_push_without_leaking_account_details(): void
    {
        [$seller, $store] = $this->makeRiderStore();
        [, $headers] = $this->makeTerminal($store);
        $product = $this->makeStoreProduct($seller, $store);

        $customer = User::factory()->create(['name' => 'สมชาย กล้าหาญ']);
        $customer->forceFill(['phone' => '081-234-5678', 'phone_verified' => true])->save();
        $unverified = User::factory()->create();
        $unverified->forceFill(['phone' => '0823334444', 'phone_verified' => false])->save();

        $matched = $this->posCreate($headers, [
            'local_id' => 'DL-PUSH-1',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'customer_phone' => '+66 81 234 5678',
        ])->assertCreated()->assertJsonPath('data.push_sent', true);

        $content = json_encode($matched->json(), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('สมชาย', $content);
        $this->assertStringNotContainsString((string) $customer->email, $content);
        $this->assertStringNotContainsString('2345678', $content);

        $row = PosDeliveryRequest::findOrFail($matched->json('data.id'));
        $this->assertSame((int) $customer->id, (int) $row->target_user_id);
        $this->assertSame('0812345678', $row->customer_phone);

        $notification = AppNotification::where('user_id', $customer->id)->where('type', 'pos_payment_request')->firstOrFail();
        $this->assertSame($row->token, $notification->data['token']);
        $this->assertSame('pos-pay', $notification->data['screen']);
        $this->assertSame('pos_payment_request', $notification->data['type']);
        $this->assertSame('มีคำขอชำระเงินจากร้าน '.$store->store_name, $notification->title);

        $this->posCreate($headers, [
            'local_id' => 'DL-PUSH-2',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'customer_phone' => '0823334444',
        ])->assertCreated()->assertJsonPath('data.push_sent', false);

        $this->posCreate($headers, [
            'local_id' => 'DL-PUSH-3',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'customer_phone' => '0899990000',
        ])->assertCreated()->assertJsonPath('data.push_sent', false);

        $this->assertSame(1, AppNotification::where('type', 'pos_payment_request')->count());
    }

    /**
     * route ใหม่ใช้ throttle + pos.terminal / auth:sanctum ตามที่ตกลง
     */
    public function test_routes_have_expected_middleware(): void
    {
        $store = Route::getRoutes()->getByName('api.pos.delivery-requests.store');
        $show = Route::getRoutes()->getByName('api.pos.delivery-requests.show');
        $cancel = Route::getRoutes()->getByName('api.pos.delivery-requests.cancel');
        $customerShow = Route::getRoutes()->getByName('api.v1.pos-requests.show');
        $customerPay = Route::getRoutes()->getByName('api.v1.pos-requests.pay');

        $this->assertSame('api/pos/delivery-requests', $store->uri());
        $this->assertContains('pos.terminal', $store->gatherMiddleware());
        $this->assertContains('throttle:30,1,pos-delivery-create', $store->gatherMiddleware());
        $this->assertContains('throttle:120,1,pos-delivery-poll', $show->gatherMiddleware());
        $this->assertContains('pos.terminal', $cancel->gatherMiddleware());

        $this->assertSame('api/v1/pos-requests/{token}', $customerShow->uri());
        $this->assertContains('auth:sanctum', $customerShow->gatherMiddleware());
        $this->assertContains('throttle:60,1,api-pos-request-show', $customerShow->gatherMiddleware());
        $this->assertSame('api/v1/pos-requests/{token}/pay', $customerPay->uri());
        $this->assertContains('auth:sanctum', $customerPay->gatherMiddleware());
        $this->assertContains('throttle:10,1,api-pos-request-pay', $customerPay->gatherMiddleware());
    }
}
