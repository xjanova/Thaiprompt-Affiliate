<?php

namespace Tests\Feature\Shop;

use App\Jobs\SendNotificationPush;
use App\Models\Notification as InAppNotification;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * แชทออเดอร์ฝั่งผู้ขายในแอป (ต้องใช้ MySQL)
 *
 * gap จาก audit 2026-09-26: แชทออเดอร์ใช้ได้แค่ฝั่งผู้ซื้อ — ร้านไม่มี API อ่าน/ตอบ และ push ของแชทพาร้านไปหน้าผู้ซื้อ
 *   - เจ้าของร้านอ่าน/ส่ง/อ่านแล้วได้ · ร้านอื่น = 404 · ผู้ซื้อ (ไม่ใช่ร้าน) = 403
 *   - ตรวจข้อมูล (ว่าง/ยาวเกิน/ไฟล์ไม่ใช่รูป) · ออเดอร์ยกเลิกแล้วปิดแชท · ส่งซ้ำด้วยรหัสเดิมไม่สร้างซ้ำ
 *   - แจ้งเตือนถูกคน (payload role ของผู้รับ) และไม่มี LINE push
 */
#[Group('shop')]
class SellerOrderChatApiTest extends TestCase
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
    }

    /**
     * @return array{0: User, 1: User, 2: Order}
     */
    private function paidOrder(string $status = 'paid'): array
    {
        [$seller, $store] = $this->makeSellerWithStore();
        $product = $this->makeProduct($seller, $store);
        $buyer = $this->makeBuyer();

        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id,
            'status' => $status,
            'payment_status' => 'paid',
            'payment_method' => 'wallet',
            'paid_at' => now(),
        ]);

        return [$seller, $buyer, $order];
    }

    private function chatNotificationsFor(User $user)
    {
        return InAppNotification::where('user_id', $user->id)
            ->whereIn('type', ['order_message', 'seller_order_message'])
            ->get();
    }

    public function test_store_owner_reads_replies_and_marks_read(): void
    {
        [$seller, $buyer, $order] = $this->paidOrder();

        // ลูกค้าทักร้าน (ภาษาไทย + อีโมจิ) → ร้านได้แจ้งเตือนที่พาไปหน้าแชทฝั่งร้าน
        OrderMessage::send($order, $buyer->id, 'customer', 'สวัสดีค่ะ ส่งวันไหนคะ 🙏');

        $sellerNotes = $this->chatNotificationsFor($seller);
        $this->assertCount(1, $sellerNotes);
        // type แยกจากฝั่งผู้ซื้อ — แอปรุ่นเก่าที่ไม่อ่าน role จะไม่พาร้านไปหน้าผู้ซื้อ (404)
        $this->assertSame('seller_order_message', $sellerNotes->first()->type);
        $this->assertSame('seller_order_message', $sellerNotes->first()->data['type']);
        $this->assertSame('seller', $sellerNotes->first()->data['role']);
        $this->assertSame($order->id, $sellerNotes->first()->data['order_id']);
        $this->assertSame('messages', $sellerNotes->first()->data['channel']);
        $this->assertCount(0, $this->chatNotificationsFor($buyer), 'ผู้ส่งต้องไม่ได้แจ้งเตือนของตัวเอง');

        Sanctum::actingAs($seller);

        // รายการ + ตัวกรองข้อความใหม่ + ป้ายยังไม่อ่าน
        $this->getJson('/api/v1/seller/orders?status=all')
            ->assertOk()
            ->assertJsonPath('data.orders.0.id', $order->id)
            ->assertJsonPath('data.orders.0.unread_messages', 1)
            ->assertJsonPath('data.counts.unread_chat', 1);
        $this->getJson('/api/v1/seller/orders?status=unread_chat')
            ->assertOk()
            ->assertJsonCount(1, 'data.orders');

        $this->getJson("/api/v1/seller/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.chat.unread', 1)
            ->assertJsonPath('data.chat.can_send', true);

        // เปิดแชท = อ่านแล้ว
        $this->getJson("/api/v1/seller/orders/{$order->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data.messages')
            ->assertJsonPath('data.messages.0.sender_type', 'customer')
            ->assertJsonPath('data.messages.0.message', 'สวัสดีค่ะ ส่งวันไหนคะ 🙏')
            ->assertJsonPath('data.messages.0.is_mine', false)
            ->assertJsonPath('data.chat.can_send', true);

        $this->assertTrue((bool) OrderMessage::where('order_id', $order->id)->value('is_read'));
        $this->getJson("/api/v1/seller/orders/{$order->id}")->assertJsonPath('data.chat.unread', 0);
        $this->getJson('/api/v1/seller/orders?status=unread_chat')->assertJsonCount(0, 'data.orders');

        // ร้านตอบ → ลูกค้าได้แจ้งเตือนที่พาไปหน้าแชทฝั่งผู้ซื้อ (เลื่อนเวลาให้ลำดับข้อความชัด)
        $this->travel(2)->seconds();
        $this->postJson("/api/v1/seller/orders/{$order->id}/messages", ['message' => '  ได้เลยครับ ส่งพรุ่งนี้ 😊  '])
            ->assertCreated()
            ->assertJsonPath('data.is_mine', true)
            ->assertJsonPath('data.sender_type', 'seller')
            ->assertJsonPath('data.message', 'ได้เลยครับ ส่งพรุ่งนี้ 😊')
            ->assertJsonPath('data.is_read', false);

        $this->assertDatabaseHas('order_messages', [
            'order_id' => $order->id,
            'sender_id' => $seller->id,
            'sender_type' => 'seller',
            'is_read' => false,
        ]);
        $this->assertTrue((bool) $order->fresh()->has_unread_messages, 'ข้อความร้านที่ลูกค้ายังไม่อ่าน = ยังมีข้อความค้าง (ความหมายเดิม)');

        $buyerNotes = $this->chatNotificationsFor($buyer);
        $this->assertCount(1, $buyerNotes);
        $this->assertSame('order_message', $buyerNotes->first()->type);
        $this->assertSame('buyer', $buyerNotes->first()->data['role']);
        $this->assertStringContainsString('ร้าน', $buyerNotes->first()->title);
        Queue::assertPushed(SendNotificationPush::class);

        // ลูกค้าเห็นข้อความร้านที่ API ฝั่งผู้ซื้อ (ไม่ถดถอย)
        Sanctum::actingAs($buyer);
        $this->getJson("/api/v1/orders/{$order->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.messages.0.sender_type', 'seller')
            ->assertJsonPath('data.messages.0.is_mine', false)
            ->assertJsonPath('data.messages.1.is_mine', true);

        // อ่านแล้วแบบกดเอง
        OrderMessage::send($order, $buyer->id, 'customer', 'ขอบคุณค่ะ');
        Sanctum::actingAs($seller);
        $this->postJson("/api/v1/seller/orders/{$order->id}/messages/read")
            ->assertOk()
            ->assertJsonPath('data.marked', 1);
        $this->assertFalse((bool) $order->fresh()->has_unread_messages);

        // ไม่มี LINE push ในทุกขั้น
        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'line.me'));
    }

    public function test_other_store_and_buyer_are_refused(): void
    {
        [, $buyer, $order] = $this->paidOrder();
        OrderMessage::send($order, $buyer->id, 'customer', 'ข้อความลับของออเดอร์นี้');
        [$otherSeller] = $this->makeSellerWithStore();

        // ร้านอื่น = 404 ทุกเส้น (ไม่รู้ว่ามีออเดอร์นี้)
        Sanctum::actingAs($otherSeller);
        $this->getJson("/api/v1/seller/orders/{$order->id}/messages")
            ->assertNotFound()
            ->assertJsonPath('code', 'ORDER_NOT_FOUND')
            ->assertJsonMissing(['message' => 'ข้อความลับของออเดอร์นี้']);
        $this->postJson("/api/v1/seller/orders/{$order->id}/messages", ['message' => 'แอบตอบ'])->assertNotFound();
        $this->postJson("/api/v1/seller/orders/{$order->id}/messages/read")->assertNotFound();

        // ผู้ซื้อ (ไม่มีร้าน) ปลอมเป็นร้าน = 403
        Sanctum::actingAs($buyer);
        $this->getJson("/api/v1/seller/orders/{$order->id}/messages")
            ->assertForbidden()
            ->assertJsonPath('code', 'NOT_A_SELLER');
        $this->postJson("/api/v1/seller/orders/{$order->id}/messages", ['message' => 'ปลอมเป็นร้าน'])->assertForbidden();

        $this->assertSame(0, OrderMessage::where('order_id', $order->id)->where('sender_type', 'seller')->count());
        $this->assertFalse((bool) OrderMessage::where('order_id', $order->id)->value('is_read'), 'คนอื่นต้องทำให้ข้อความเป็นอ่านแล้วไม่ได้');
    }

    public function test_validation_closed_chat_and_image_attachment(): void
    {
        [$seller, , $order] = $this->paidOrder();
        Sanctum::actingAs($seller);
        $url = "/api/v1/seller/orders/{$order->id}/messages";

        $this->postJson($url, [])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
        $this->postJson($url, ['message' => '     '])->assertStatus(422);
        $this->postJson($url, ['message' => str_repeat('ก', 2001)])
            ->assertStatus(422)
            ->assertJsonPath('message', 'ข้อความต้องไม่เกิน 2000 ตัวอักษร');
        $this->postJson($url, ['message' => 'x', 'client_message_id' => '../../etc'])->assertStatus(422);

        // ไฟล์แนบต้องเป็นรูปเท่านั้น
        Storage::fake('public');
        $this->post($url, ['attachment' => UploadedFile::fake()->create('slip.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->post($url, ['attachment' => UploadedFile::fake()->image('parcel.jpg', 400, 300)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.attachment_type', 'image');
        $path = OrderMessage::where('order_id', $order->id)->value('attachment');
        Storage::disk('public')->assertExists($path);

        $this->assertSame(1, OrderMessage::where('order_id', $order->id)->count());

        // ข้อความยาวพอดี 2000 ตัว (ไทย) ผ่าน
        $this->postJson($url, ['message' => str_repeat('ก', 2000)])->assertCreated();

        // ออเดอร์ยกเลิกแล้ว = ปิดแชท (อ่านได้ ส่งไม่ได้)
        Order::whereKey($order->id)->update(['status' => 'cancelled']);
        $this->postJson($url, ['message' => 'ยังส่งได้ไหม'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CHAT_CLOSED');
        $this->getJson($url)->assertOk()->assertJsonPath('data.chat.can_send', false);
    }

    public function test_retry_with_same_client_id_does_not_duplicate(): void
    {
        [$seller, $buyer, $order] = $this->paidOrder();
        Sanctum::actingAs($seller);
        $url = "/api/v1/seller/orders/{$order->id}/messages";

        $first = $this->postJson($url, ['message' => 'ส่งแล้วนะครับ', 'client_message_id' => 'c-123_abc'])->assertCreated();
        $again = $this->postJson($url, ['message' => 'ส่งแล้วนะครับ', 'client_message_id' => 'c-123_abc'])->assertOk();

        $this->assertSame($first->json('data.id'), $again->json('data.id'));
        $this->assertSame(1, OrderMessage::where('order_id', $order->id)->count());
        $this->assertCount(1, $this->chatNotificationsFor($buyer));
    }

    public function test_buyer_retry_with_same_client_id_does_not_duplicate(): void
    {
        [$seller, $buyer, $order] = $this->paidOrder();
        Sanctum::actingAs($buyer);
        $url = "/api/v1/orders/{$order->id}/messages";

        $first = $this->postJson($url, ['message' => 'ของถึงวันไหนคะ', 'client_message_id' => 'b-777'])->assertCreated();
        $again = $this->postJson($url, ['message' => 'ของถึงวันไหนคะ', 'client_message_id' => 'b-777'])
            ->assertOk()
            ->assertJsonPath('data.is_mine', true);

        $this->assertSame($first->json('data.id'), $again->json('data.id'));
        $this->assertSame(1, OrderMessage::where('order_id', $order->id)->count());
        $this->assertCount(1, $this->chatNotificationsFor($seller));

        // ไม่มีรหัส = พฤติกรรมเดิม (สร้างทุกครั้ง) · รหัสผิดรูปแบบ = 422
        $this->postJson($url, ['message' => 'ของถึงวันไหนคะ'])->assertCreated();
        $this->assertSame(2, OrderMessage::where('order_id', $order->id)->count());
        $this->postJson($url, ['message' => 'x', 'client_message_id' => 'bad id!'])->assertStatus(422);
    }

    public function test_chat_notifications_are_throttled_per_recipient(): void
    {
        [$seller, $buyer, $order] = $this->paidOrder();

        OrderMessage::send($order, $buyer->id, 'customer', 'ข้อความที่ 1');
        OrderMessage::send($order, $buyer->id, 'customer', 'ข้อความที่ 2');
        OrderMessage::send($order, $buyer->id, 'customer', 'ข้อความที่ 3');

        $this->assertCount(1, $this->chatNotificationsFor($seller), 'ลูกค้าพิมพ์รัวๆ ร้านได้แจ้งเตือนครั้งเดียวต่อช่วง');
        $this->assertCount(0, $this->chatNotificationsFor($buyer));

        // พ้นช่วงกันเด้งแล้ว → แจ้งได้อีก
        $this->travel(61)->seconds();
        OrderMessage::send($order, $buyer->id, 'customer', 'ข้อความที่ 4');
        $this->assertCount(2, $this->chatNotificationsFor($seller));
    }

    public function test_no_push_while_recipient_has_the_chat_open(): void
    {
        [$seller, $buyer, $order] = $this->paidOrder();

        // ร้านเปิดหน้าแชทอยู่ (แอปดึงข้อความ) → ข้อความลูกค้าไม่เด้ง push ทับหน้าแชท
        Sanctum::actingAs($seller);
        $this->getJson("/api/v1/seller/orders/{$order->id}/messages")->assertOk();
        OrderMessage::send($order, $buyer->id, 'customer', 'ร้านเปิดแชทอยู่');
        $this->assertCount(0, $this->chatNotificationsFor($seller));

        // ออกจากหน้าแชทแล้ว (ไม่ได้ดึงข้อความเกินช่วง) → แจ้งเตือนตามปกติ
        $this->travel(30)->seconds();
        OrderMessage::send($order, $buyer->id, 'customer', 'ออกจากแชทแล้ว');
        $this->assertCount(1, $this->chatNotificationsFor($seller));

        // ผู้ซื้อเปิดแชทอยู่ที่ API ฝั่งผู้ซื้อ → ร้านตอบไม่ต้อง push
        Sanctum::actingAs($buyer);
        $this->getJson("/api/v1/orders/{$order->id}/messages")->assertOk();
        Sanctum::actingAs($seller);
        $this->postJson("/api/v1/seller/orders/{$order->id}/messages", ['message' => 'ตอบแล้วครับ'])->assertCreated();
        $this->assertCount(0, $this->chatNotificationsFor($buyer));
    }

    public function test_chat_does_not_touch_order_updated_at(): void
    {
        // updated_at ใช้ซ่อนเบอร์ลูกค้าของออเดอร์ที่จบเกิน 7 วัน — ทักแชทต้องไม่ทำให้เบอร์กลับมาโชว์
        [$seller, $buyer, $order] = $this->paidOrder('completed');
        $old = now()->subDays(10)->startOfSecond();
        Order::whereKey($order->id)->toBase()->update(['updated_at' => $old]);

        Sanctum::actingAs($seller);
        $this->postJson("/api/v1/seller/orders/{$order->id}/messages", ['message' => 'สินค้าเป็นอย่างไรบ้าง'])->assertCreated();
        OrderMessage::send($order->fresh(), $buyer->id, 'customer', 'ดีมากค่ะ');
        $this->getJson("/api/v1/seller/orders/{$order->id}/messages")->assertOk();

        // ผู้ซื้อเปิดอ่านแชท (อ่านข้อความร้าน + คำนวณ has_unread_messages ใหม่)
        Sanctum::actingAs($buyer);
        $this->getJson("/api/v1/orders/{$order->id}/messages")->assertOk();
        $this->assertFalse((bool) $order->fresh()->has_unread_messages);

        $fresh = $order->fresh();
        $this->assertTrue($fresh->updated_at->equalTo($old));
        $this->assertNotNull($fresh->last_message_at);
    }

    public function test_send_route_has_its_own_rate_limit(): void
    {
        $route = Route::getRoutes()->match(\Illuminate\Http\Request::create('/api/v1/seller/orders/1/messages', 'POST'));

        $this->assertContains('throttle:20,1,api-seller-order-message-send', $route->gatherMiddleware());
    }
}
