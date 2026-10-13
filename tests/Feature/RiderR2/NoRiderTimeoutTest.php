<?php

namespace Tests\Feature\RiderR2;

use App\Events\Marketplace\NoRiderOrderCancelled;
use App\Events\Marketplace\RiderAbandonedJob;
use App\Models\DeliveryHandover;
use App\Models\FreshMarketOrder;
use App\Models\RiderJob;
use App\Services\FreshMarketService;
use App\Services\Rider\HandoverService;
use App\Services\RiderJobService;
use App\Services\Shop\ShopPresenter;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\RiderR2\Concerns\BuildsTimeoutFixtures;

/**
 * ⏱️ กติกาตัดเวลา ข้อ 2 (2026-10-13): หาไรเดอร์ไม่ได้ (ต้องใช้ MySQL)
 *
 * - นับจากรอบหาไรเดอร์ปัจจุบัน (search_started_at) — ไรเดอร์คืนงาน = เริ่มนับใหม่ · ไม่ส่งแอดมินทันทีหลังคืนงาน
 * - 30 นาที → ผู้ซื้อยกเลิก + คืนเงินเต็ม (ตลาดสด: เดิมยกเลิกไม่ได้หลังร้านรับ)
 * - 60 นาที → ระบบยกเลิกออเดอร์ต้นทาง (ตลาดสด + ร้านค้า) คืนเงิน ร้านไม่ได้เงิน ปิดงาน + แถวส่งมอบ แจ้งแอดมินครั้งเดียว
 * - งาน manual_needed ยังเสนอให้ไรเดอร์ที่เพิ่งออนไลน์ (ไม่แจ้งแอดมินซ้ำ)
 */
#[Group('rider-r2')]
class NoRiderTimeoutTest extends HandoverTestCase
{
    use BuildsTimeoutFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freshMarketWorld();
    }

    // =====================================================
    // ตลาดสด
    // =====================================================

    public function test_fresh_market_buyer_can_cancel_after_30_minutes_without_a_rider_with_full_refund(): void
    {
        Event::fake([NoRiderOrderCancelled::class]);

        [$order, $buyer, $job] = $this->readyFreshOrderWaitingForRider();
        $grandTotal = $order->grand_total;

        $this->travel(29)->minutes();
        Sanctum::actingAs($buyer);

        $this->getJson("/api/v1/fresh-market/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.buyer_can_cancel', false)
            ->assertJsonPath('data.buyer_cancel_note', null);
        $this->postJson("/api/v1/fresh-market/orders/{$order->id}/cancel")->assertStatus(409);

        $this->travel(1)->minutes();

        $this->getJson("/api/v1/fresh-market/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.buyer_can_cancel', true)
            ->assertJsonPath('data.buyer_cancel_note', 'ยังหาไรเดอร์ไม่ได้เกิน 30 นาที ยกเลิกและรับเงินคืนเต็มจำนวนได้');

        // ช่องทางเดิมของแอปรุ่นเก่า (PUT /status action=cancel) ก็ยกเลิกได้เหมือนกัน
        $this->putJson("/api/v1/fresh-market/orders/{$order->id}/status", ['action' => 'cancel'])
            ->assertOk()
            ->assertJsonPath('data.order_status', FreshMarketOrder::STATUS_CANCELLED);

        $cancelled = $order->fresh();
        $this->assertSame('buyer', $cancelled->cancelled_by);
        $this->assertEqualsWithDelta($grandTotal, (float) $cancelled->refunded_amount, 0.001, 'คืนเต็มรวมค่าส่ง');
        $this->assertEqualsWithDelta(1000.0, $this->walletOf($buyer), 0.001);
        $this->assertSame('cancelled', $job->fresh()->status);

        Event::assertDispatched(NoRiderOrderCancelled::class, fn (NoRiderOrderCancelled $e) => $e->riderJobId === (int) $job->id
            && $e->sourceId === (int) $order->id
            && $e->by === NoRiderOrderCancelled::BY_BUYER
            && $e->minutes === 30);
    }

    public function test_fresh_market_order_is_auto_cancelled_after_60_minutes_without_a_rider(): void
    {
        Event::fake([NoRiderOrderCancelled::class]);

        $admin = $this->makeAdmin();
        [$order, $buyer, $job, $sellerUser] = $this->readyFreshOrderWaitingForRider();

        // 59 นาที: ส่งแอดมินแล้ว (manual_needed) แต่ยังไม่ยกเลิก
        $this->travel(59)->minutes();
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertSame(FreshMarketOrder::STATUS_READY, $order->fresh()->order_status);
        $this->assertSame('manual_needed', $job->fresh()->dispatch_type);

        // 60 นาที: ระบบยกเลิก + คืนเงินเต็ม + ปิดงาน
        $this->travel(1)->minutes();
        $this->artisan('rider:sweep-pending')->assertSuccessful();

        $cancelled = $order->fresh();
        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $cancelled->order_status);
        $this->assertSame('system', $cancelled->cancelled_by);
        $this->assertStringContainsString('ไม่มีไรเดอร์รับงาน', (string) $cancelled->cancel_reason);
        $this->assertEqualsWithDelta($order->grand_total, (float) $cancelled->refunded_amount, 0.001);
        $this->assertEqualsWithDelta(1000.0, $this->walletOf($buyer), 0.001);
        $this->assertEqualsWithDelta(0.0, $this->walletOf($sellerUser), 0.001, 'ร้านไม่ได้เงิน');
        $this->assertSame('cancelled', $job->fresh()->status);

        $this->assertSame(1, $this->notificationCount($admin, 'rider_admin', 'ยกเลิกออเดอร์อัตโนมัติ%'));
        $this->assertGreaterThanOrEqual(1, $this->notificationCount($buyer, 'fresh_market_order', '%ถูกยกเลิกอัตโนมัติ%'));
        $this->assertGreaterThanOrEqual(1, $this->notificationCount($sellerUser, 'fresh_market_order', '%ถูกยกเลิก%'));

        // รอบถัดไป: ไม่ทำซ้ำ
        $this->travel(5)->minutes();
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertEqualsWithDelta(1000.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(1, $this->notificationCount($admin, 'rider_admin', 'ยกเลิกออเดอร์อัตโนมัติ%'));
        Event::assertDispatchedTimes(NoRiderOrderCancelled::class, 1);
        Event::assertDispatched(NoRiderOrderCancelled::class, fn (NoRiderOrderCancelled $e) => $e->by === NoRiderOrderCancelled::BY_AUTO
            && $e->sourceType === $order->getMorphClass() && $e->sourceId === (int) $order->id && $e->minutes === 60);
    }

    // =====================================================
    // ร้านค้า
    // =====================================================

    public function test_shop_order_is_auto_cancelled_after_60_minutes_refunds_buyer_and_closes_handover(): void
    {
        $admin = $this->makeAdmin();
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $job = $this->makeOpenShopJob($order, ['handover_required' => true]);
        $handover = app(HandoverService::class)->ensureFor($job); // ผู้ซื้อเคยเปิดหน้าส่งมอบไว้
        $this->assertSame(DeliveryHandover::STATUS_WAITING, $handover->status);

        // 30 นาที: แอปเห็นปุ่ม + เหตุผล
        $this->travel(30)->minutes();
        $payload = ShopPresenter::order($order->fresh());
        $this->assertTrue($payload['buyer_can_cancel']);
        $this->assertSame('ยังหาไรเดอร์ไม่ได้เกิน 30 นาที ยกเลิกและรับเงินคืนเต็มจำนวนได้', $payload['buyer_cancel_note']);

        Sanctum::actingAs($buyer);
        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.buyer_can_cancel', true)
            ->assertJsonPath('data.buyer_cancel_note', 'ยังหาไรเดอร์ไม่ได้เกิน 30 นาที ยกเลิกและรับเงินคืนเต็มจำนวนได้');

        // 60 นาที
        $this->travel(30)->minutes();
        $this->artisan('rider:sweep-pending')->assertSuccessful();

        $order->refresh();
        $this->assertSame('refunded', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertStringContainsString('ไม่มีไรเดอร์รับงาน', (string) $order->cancellation_reason);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001, 'คืนเต็ม 100 + ค่าส่ง 40');
        $this->assertEqualsWithDelta(0.0, $this->walletOf($seller), 0.001, 'ร้านไม่ได้เงิน');
        $this->assertSame('cancelled', $job->fresh()->status);
        $this->assertTrue($this->handoverOf($job)->isFinal(), 'ปิดแถวส่งมอบของงานที่ยกเลิก');

        $this->assertSame(1, $this->notificationCount($admin, 'rider_admin', 'ยกเลิกออเดอร์อัตโนมัติ%'));
        $this->assertSame(1, $this->notificationCount($seller, 'shop_order', '%ไม่มีไรเดอร์รับงาน%'));
        $this->assertGreaterThanOrEqual(1, $this->notificationCount($buyer, 'shop_order', 'คืนเงินแล้ว'));

        // ทำซ้ำไม่คืนซ้ำ
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(1, $this->notificationCount($admin, 'rider_admin', 'ยกเลิกออเดอร์อัตโนมัติ%'));
    }

    public function test_shop_buyer_cancel_after_30_minutes_fires_no_rider_event(): void
    {
        Event::fake([NoRiderOrderCancelled::class]);

        [$order, $buyer] = $this->makeDeferredShopOrder();
        $job = $this->makeOpenShopJob($order);

        $this->travel(31)->minutes();
        Sanctum::actingAs($buyer);
        $this->postJson("/api/v1/orders/{$order->id}/cancel")->assertOk()->assertJsonPath('refunded', true);

        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame('cancelled', $job->fresh()->status);
        Event::assertDispatched(NoRiderOrderCancelled::class, fn (NoRiderOrderCancelled $e) => $e->riderJobId === (int) $job->id
            && $e->by === NoRiderOrderCancelled::BY_BUYER && $e->minutes === 31);
    }

    // =====================================================
    // รอบหาไรเดอร์ปัจจุบัน
    // =====================================================

    public function test_search_timer_restarts_when_a_rider_releases_the_job(): void
    {
        Event::fake([NoRiderOrderCancelled::class, RiderAbandonedJob::class]);

        $admin = $this->makeAdmin();
        [$order, $buyer, $job] = $this->readyFreshOrderWaitingForRider();

        // นาทีที่ 25 ไรเดอร์รับงาน → นาทีที่ 35 คืนงาน
        $this->travel(25)->minutes();
        $rider = $this->onlineRider();
        app(RiderJobService::class)->accept($job->fresh(), $rider);

        $this->travel(10)->minutes();
        $released = app(RiderJobService::class)->release($job->fresh(), $rider->fresh(), 'รถเสีย');

        $this->assertTrue($released->isOpen());
        $this->assertEqualsWithDelta(now()->getTimestamp(), $released->searchStartedAt()->getTimestamp(), 2);
        $this->assertSame(1, (int) $rider->fresh()->cancelled_jobs);
        Event::assertDispatched(RiderAbandonedJob::class, fn (RiderAbandonedJob $e) => $e->riderJobId === (int) $job->id
            && $e->riderId === (int) $rider->id && $e->reason === RiderAbandonedJob::REASON_RELEASED);

        // เพิ่งกลับเข้าคิว: ไม่ส่งแอดมินทันที (เดิมนับจาก created_at = 35 นาที → manual_needed ทันที) และผู้ซื้อยังยกเลิกไม่ได้
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertNotSame('manual_needed', $job->fresh()->dispatch_type);
        $this->assertSame(0, $this->notificationCount($admin, 'rider_admin', 'ไม่มีไรเดอร์รับงาน'));
        $this->assertFalse($order->fresh()->toApiArray('buyer')['buyer_can_cancel']);

        // นาทีที่ 61 นับจากสร้างงาน แต่เพิ่ง 26 นาทีของรอบใหม่ → ยังไม่ยกเลิก
        $this->travel(26)->minutes();
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertSame(FreshMarketOrder::STATUS_READY, $order->fresh()->order_status);
        $this->assertFalse($order->fresh()->toApiArray('buyer')['buyer_can_cancel']);

        // 30 นาทีของรอบใหม่ → ผู้ซื้อยกเลิกได้
        $this->travel(4)->minutes();
        $this->assertTrue($order->fresh()->toApiArray('buyer')['buyer_can_cancel']);

        // 60 นาทีของรอบใหม่ → ระบบยกเลิก
        $this->travel(30)->minutes();
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $order->fresh()->order_status);
        Event::assertDispatched(NoRiderOrderCancelled::class, fn (NoRiderOrderCancelled $e) => $e->by === NoRiderOrderCancelled::BY_AUTO && $e->minutes === 60);
    }

    public function test_manual_needed_job_is_still_offered_to_a_rider_who_comes_online(): void
    {
        $admin = $this->makeAdmin();
        [$order] = $this->makeDeferredShopOrder();
        $job = $this->makeOpenShopJob($order);

        // ไม่มีไรเดอร์เกิน rider.pending_timeout_minutes → ส่งแอดมิน (ครั้งเดียว)
        $this->travel(11)->minutes();
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertSame('manual_needed', $job->fresh()->dispatch_type);
        $this->assertSame(1, $this->notificationCount($admin, 'rider_admin', 'ไม่มีไรเดอร์รับงาน'));

        // ไรเดอร์เพิ่งออนไลน์ → รอบกวาดถัดไปเสนองานให้ (แจ้งในแอป) โดยไม่แจ้งแอดมินซ้ำ
        $this->travel(3)->minutes();
        $rider = $this->onlineRider();
        $this->artisan('rider:sweep-pending')->assertSuccessful();

        $fresh = $job->fresh();
        $this->assertSame('manual_needed', $fresh->dispatch_type);
        $this->assertContains((int) $rider->id, array_map('intval', $fresh->candidate_riders ?? []));
        $this->assertSame(1, $this->notificationCount($rider->user_id, 'rider_job_offer'));
        $this->assertSame(1, $this->notificationCount($admin, 'rider_admin', 'ไม่มีไรเดอร์รับงาน'));

        // ไม่แจ้งไรเดอร์คนเดิมซ้ำทุกรอบ
        $this->travel(3)->minutes();
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->assertSame(1, $this->notificationCount($rider->user_id, 'rider_job_offer'));

        // ไรเดอร์รับงาน manual_needed ได้ตามปกติ
        $accepted = app(RiderJobService::class)->accept($job->fresh(), $rider->fresh());
        $this->assertSame('accepted', $accepted->status);
        $this->assertSame((int) $rider->id, (int) $accepted->rider_id);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * ออเดอร์ตลาดสดส่งด้วยไรเดอร์ ร้านกดพร้อมแล้ว → มีงานไรเดอร์รอคนรับ (ไม่มีไรเดอร์ออนไลน์)
     *
     * @return array{0: FreshMarketOrder, 1: \App\Models\User, 2: RiderJob, 3: \App\Models\User}
     */
    private function readyFreshOrderWaitingForRider(): array
    {
        [, $sellerUser, $listing] = $this->makeFreshShop();
        $buyer = $this->makeUser('ผู้ซื้อรอไรเดอร์', 1000);
        $order = $this->placeFreshRiderOrder($buyer, $listing);
        $service = new FreshMarketService;

        $service->applyAction($order, 'accept', 'seller', $sellerUser);
        $service->applyAction($order->fresh(), 'ready', 'seller', $sellerUser);

        $order = $order->fresh();
        $job = RiderJob::forSource($order)->latest('id')->firstOrFail();
        $this->assertTrue($job->isOpen());
        $this->assertSame((int) $job->id, (int) $order->rider_job_id);
        $this->assertNotNull($job->search_started_at);

        return [$order, $buyer, $job, $sellerUser];
    }
}
