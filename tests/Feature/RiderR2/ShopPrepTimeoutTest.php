<?php

namespace Tests\Feature\RiderR2;

use App\Events\Marketplace\ShopMissedPrepDeadline;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSetting;
use App\Models\WalletTransaction;
use App\Services\FreshMarketChannelManager;
use App\Services\FreshMarketService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\RiderR2\Concerns\BuildsTimeoutFixtures;

/**
 * ⏱️ กติกาตัดเวลา ข้อ 1 (2026-10-13): ร้านตลาดสดรับออเดอร์แล้วไม่กด "พร้อม" (ต้องใช้ MySQL)
 *
 * - 45 นาที (fresh_market.prep_buyer_cancel_minutes) → ผู้ซื้อยกเลิก + คืนเงินเต็มจำนวน (รวมค่าส่ง) ได้ — 44 นาทียังไม่ได้
 * - 90 นาที (fresh_market.prep_auto_cancel_minutes) → ระบบยกเลิก + คืนเงิน + แจ้งแอดมิน (ทำซ้ำไม่คืนซ้ำ/ไม่แจ้งซ้ำ)
 * - toApiArray ส่ง buyer_can_cancel + buyer_cancel_note ให้แอป
 * - LINE: ปุ่มยกเลิกในคำตอบเช็คสถานะ (reply เท่านั้น ห้าม push) · ยกเลิกได้เฉพาะเจ้าของออเดอร์
 */
#[Group('rider-r2')]
class ShopPrepTimeoutTest extends HandoverTestCase
{
    use BuildsTimeoutFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freshMarketWorld();
    }

    public function test_buyer_cancel_is_refused_at_44_minutes_and_allowed_at_45_with_full_refund(): void
    {
        Event::fake([ShopMissedPrepDeadline::class]);

        [, $sellerUser, $listing] = $this->makeFreshShop();
        $buyer = $this->makeUser('ผู้ซื้อรอนาน', 1000);
        $order = $this->placeFreshRiderOrder($buyer, $listing);
        $grandTotal = $order->grand_total;
        $this->assertEqualsWithDelta(1000 - $grandTotal, $this->walletOf($buyer), 0.001);

        (new FreshMarketService)->applyAction($order, 'accept', 'seller', $sellerUser);

        // 44 นาที: ยังยกเลิกไม่ได้ (ปุ่มไม่ขึ้น + API ปฏิเสธ)
        $this->travel(44)->minutes();
        Sanctum::actingAs($buyer);

        $this->getJson("/api/v1/fresh-market/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.buyer_can_cancel', false)
            ->assertJsonPath('data.buyer_cancel_note', null);

        $this->postJson("/api/v1/fresh-market/orders/{$order->id}/cancel")
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_TRANSITION');
        $this->assertSame(FreshMarketOrder::STATUS_ACCEPTED, $order->fresh()->order_status);

        // 45 นาที: ยกเลิกได้ พร้อมเหตุผลที่แอปแสดง
        $this->travel(1)->minutes();

        $this->getJson("/api/v1/fresh-market/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.buyer_can_cancel', true)
            ->assertJsonPath('data.buyer_cancel_note', 'ร้านเตรียมอาหารเกิน 45 นาที ยกเลิกและรับเงินคืนเต็มจำนวนได้');

        $this->postJson("/api/v1/fresh-market/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.order_status', FreshMarketOrder::STATUS_CANCELLED)
            ->assertJsonPath('data.buyer_can_cancel', false);

        $cancelled = $order->fresh();
        $this->assertSame('buyer', $cancelled->cancelled_by);
        $this->assertSame('refunded', $cancelled->payment_status);
        $this->assertStringContainsString('ร้านเตรียมสินค้าเกิน 45 นาที', (string) $cancelled->cancel_reason);
        $this->assertEqualsWithDelta($grandTotal, (float) $cancelled->refunded_amount, 0.001, 'คืนเต็มจำนวนรวมค่าส่ง');
        $this->assertEqualsWithDelta(1000.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(10, (int) $listing->fresh()->quantity_available, 'คืนสต็อก');

        Event::assertDispatchedTimes(ShopMissedPrepDeadline::class, 1);
        Event::assertDispatched(ShopMissedPrepDeadline::class, fn (ShopMissedPrepDeadline $e) => $e->orderId === (int) $order->id
            && $e->sellerId === (int) $order->seller_id
            && $e->by === ShopMissedPrepDeadline::BY_BUYER
            && $e->minutes === 45);
    }

    public function test_preparing_order_is_auto_cancelled_at_90_minutes_with_refund_and_admin_alert_once(): void
    {
        Event::fake([ShopMissedPrepDeadline::class]);

        $admin = $this->makeAdmin();
        [, $sellerUser, $listing] = $this->makeFreshShop();
        $buyer = $this->makeUser('ผู้ซื้อรอนาน', 1000);
        $order = $this->placeFreshRiderOrder($buyer, $listing);
        $service = new FreshMarketService;

        $service->applyAction($order, 'accept', 'seller', $sellerUser);
        $service->applyAction($order->fresh(), 'prepare', 'seller', $sellerUser);

        // 89 นาที: ยังไม่ถึงเวลา
        $this->travel(89)->minutes();
        $this->assertSame(0, $service->expireOverduePreparation());
        $this->assertSame(FreshMarketOrder::STATUS_PREPARING, $order->fresh()->order_status);

        // 90 นาที: ตัวกวาด (fresh-market:sweep-orders) ยกเลิก + คืนเงินเต็ม
        $this->travel(1)->minutes();
        $this->artisan('fresh-market:sweep-orders --only=prep')->assertSuccessful();

        $cancelled = $order->fresh();
        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $cancelled->order_status);
        $this->assertSame('system', $cancelled->cancelled_by);
        $this->assertEqualsWithDelta($order->grand_total, (float) $cancelled->refunded_amount, 0.001);
        $this->assertEqualsWithDelta(1000.0, $this->walletOf($buyer), 0.001);
        $this->assertEqualsWithDelta(0.0, $this->walletOf($sellerUser), 0.001, 'ร้านไม่ได้เงิน');
        $this->assertSame(1, $this->notificationCount($admin, 'fresh_market_order', '%ร้านเตรียมช้า%'));
        $this->assertGreaterThanOrEqual(1, $this->notificationCount($buyer, 'fresh_market_order', '%ถูกยกเลิกอัตโนมัติ%'));
        $this->assertGreaterThanOrEqual(1, $this->notificationCount($sellerUser, 'fresh_market_order', '%ถูกยกเลิก%'));

        // รันซ้ำ: ไม่คืนเงินซ้ำ ไม่แจ้งซ้ำ ไม่ยิง event ซ้ำ
        $this->travel(10)->minutes();
        $this->artisan('fresh-market:sweep-orders --only=prep')->assertSuccessful();
        $this->assertSame(0, $service->expireOverduePreparation());

        $this->assertEqualsWithDelta(1000.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(1, WalletTransaction::where('reference_type', FreshMarketService::REF_REFUND)->where('reference_id', $order->id)->count());
        $this->assertSame(1, $this->notificationCount($admin, 'fresh_market_order', '%ร้านเตรียมช้า%'));
        Event::assertDispatchedTimes(ShopMissedPrepDeadline::class, 1);
        Event::assertDispatched(ShopMissedPrepDeadline::class, fn (ShopMissedPrepDeadline $e) => $e->by === ShopMissedPrepDeadline::BY_AUTO && $e->minutes === 90);
    }

    public function test_ready_order_is_not_touched_by_the_prep_sweep(): void
    {
        [, $sellerUser, $listing] = $this->makeFreshShop();
        $buyer = $this->makeUser('ผู้ซื้อ', 1000);
        $order = $this->placeFreshRiderOrder($buyer, $listing);
        $service = new FreshMarketService;

        $service->applyAction($order, 'accept', 'seller', $sellerUser);
        $this->travel(30)->minutes();
        $service->applyAction($order->fresh(), 'ready', 'seller', $sellerUser);

        $this->travel(70)->minutes();
        $this->assertSame(0, $service->expireOverduePreparation());
        $this->assertSame(FreshMarketOrder::STATUS_READY, $order->fresh()->order_status);
    }

    public function test_line_buyer_can_cancel_a_late_order_by_reply_only(): void
    {
        [, $sellerUser, $listing] = $this->makeFreshShop();
        $buyer = $this->makeUser('ผู้ซื้อ LINE', 1000);
        $buyer->forceFill(['line_user_id' => 'U-late-prep-'.$buyer->id])->save();
        $order = $this->placeFreshRiderOrder($buyer, $listing);
        (new FreshMarketService)->applyAction($order, 'accept', 'seller', $sellerUser);

        $settings = (new FreshMarketSetting)->forceFill(['line_channel_access_token' => 'test-line-token']);
        $manager = new FreshMarketChannelManager($settings);

        // 10 นาที: เช็คสถานะ → ยังไม่มีปุ่มยกเลิก · กดปุ่มเก่าก็ยกเลิกไม่ได้
        $this->travel(10)->minutes();
        $manager->handlePostback($buyer->line_user_id, 'action=track_order&order_id='.$order->id, ['reply_token' => 'reply-track-early']);
        $manager->handlePostback($buyer->line_user_id, 'action=buyer_cancel_order&order_id='.$order->id.'&confirm=1', ['reply_token' => 'reply-cancel-early']);
        $this->assertSame(FreshMarketOrder::STATUS_ACCEPTED, $order->fresh()->order_status);

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/reply') && $r['replyToken'] === 'reply-track-early'
            && ! str_contains(json_encode($r['messages'], JSON_UNESCAPED_UNICODE), 'buyer_cancel_order'));

        // 45 นาที: มีปุ่มยกเลิก + เหตุผล → กดแล้วยกเลิก + คืนเงินเต็ม
        $this->travel(35)->minutes();
        $manager->handlePostback($buyer->line_user_id, 'action=track_order&order_id='.$order->id, ['reply_token' => 'reply-track-late']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/reply') && $r['replyToken'] === 'reply-track-late'
            && str_contains(json_encode($r['messages'], JSON_UNESCAPED_UNICODE), 'buyer_cancel_order')
            && str_contains((string) ($r['messages'][0]['text'] ?? ''), 'ร้านเตรียมอาหารเกิน 45 นาที'));

        // กดครั้งแรก = ถามยืนยัน (ยังไม่ยกเลิก)
        $manager->handlePostback($buyer->line_user_id, 'action=buyer_cancel_order&order_id='.$order->id, ['reply_token' => 'reply-cancel-ask']);
        $this->assertSame(FreshMarketOrder::STATUS_ACCEPTED, $order->fresh()->order_status);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/reply') && $r['replyToken'] === 'reply-cancel-ask'
            && str_contains(json_encode($r['messages'], JSON_UNESCAPED_UNICODE), 'confirm=1'));

        $manager->handlePostback($buyer->line_user_id, 'action=buyer_cancel_order&order_id='.$order->id.'&confirm=1', ['reply_token' => 'reply-cancel-late']);

        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $order->fresh()->order_status);
        $this->assertEqualsWithDelta(1000.0, $this->walletOf($buyer), 0.001);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/reply') && $r['replyToken'] === 'reply-cancel-late'
            && str_contains((string) ($r['messages'][0]['text'] ?? ''), 'เรียบร้อย'));
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/push'));

        // คนอื่นยกเลิกออเดอร์ของผู้ซื้อผ่าน LINE ไม่ได้
        $other = $this->makeUser('คนแปลกหน้า', 0);
        $other->forceFill(['line_user_id' => 'U-stranger-'.$other->id])->save();
        $order2 = $this->placeFreshRiderOrder($buyer, $listing);
        $manager->handlePostback($other->line_user_id, 'action=buyer_cancel_order&order_id='.$order2->id.'&confirm=1', ['reply_token' => 'reply-stranger']);
        $this->assertSame(FreshMarketOrder::STATUS_PENDING, $order2->fresh()->order_status);

        // 🔒 คนอื่นดูสถานะออเดอร์ของผู้ซื้อด้วยเลขออเดอร์ไม่ได้ (ตอบเหมือนไม่พบ ไม่บอกเลขออเดอร์)
        $manager->handlePostback($other->line_user_id, 'action=track_order&order_id='.$order2->id, ['reply_token' => 'reply-stranger-track']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/reply') && $r['replyToken'] === 'reply-stranger-track'
            && str_contains((string) ($r['messages'][0]['text'] ?? ''), 'ไม่พบข้อมูลออเดอร์')
            && ! str_contains(json_encode($r['messages'], JSON_UNESCAPED_UNICODE), (string) $order2->order_number));

        // ร้านของออเดอร์ (ผ่านบัญชีผู้ใช้ของร้าน) ยังดูสถานะได้
        $sellerUser->forceFill(['line_user_id' => 'U-shop-'.$sellerUser->id])->save();
        $manager->handlePostback($sellerUser->line_user_id, 'action=track_order&order_id='.$order2->id, ['reply_token' => 'reply-seller-track']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/reply') && $r['replyToken'] === 'reply-seller-track'
            && str_contains((string) ($r['messages'][0]['text'] ?? ''), (string) $order2->order_number));
    }

    public function test_to_api_array_exposes_buyer_cancel_fields(): void
    {
        [, $sellerUser, $listing] = $this->makeFreshShop();
        $buyer = $this->makeUser('ผู้ซื้อ', 1000);
        $order = $this->placeFreshRiderOrder($buyer, $listing);

        // รอร้านรับ: ยกเลิกได้ตามปกติ ไม่มีข้อความเกินเวลา
        $pending = $order->fresh()->toApiArray('buyer');
        $this->assertTrue($pending['buyer_can_cancel']);
        $this->assertNull($pending['buyer_cancel_note']);
        $this->assertContains('cancel', $pending['allowed_actions']);

        (new FreshMarketService)->applyAction($order, 'accept', 'seller', $sellerUser);

        $this->travel(10)->minutes();
        $accepted = $order->fresh()->toApiArray('buyer');
        $this->assertFalse($accepted['buyer_can_cancel']);
        $this->assertNull($accepted['buyer_cancel_note']);
        $this->assertNotContains('cancel', $accepted['allowed_actions']);

        $this->travel(35)->minutes();
        $late = $order->fresh()->toApiArray('buyer');
        $this->assertTrue($late['buyer_can_cancel']);
        $this->assertSame('ร้านเตรียมอาหารเกิน 45 นาที ยกเลิกและรับเงินคืนเต็มจำนวนได้', $late['buyer_cancel_note']);
        $this->assertContains('cancel', $late['allowed_actions']);

        // ฝั่งร้านเห็นค่าเดียวกัน (ร้านรู้ว่าผู้ซื้อยกเลิกได้แล้ว)
        $this->assertTrue($order->fresh()->toApiArray('seller')['buyer_can_cancel']);
    }
}
