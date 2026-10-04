<?php

namespace Tests\Feature\RiderR2;

use App\Exceptions\FreshMarketException;
use App\Exceptions\ShopException;
use App\Models\DeliveryHandover;
use App\Models\EarningsLedger;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\DeliveryFeeCalculator;
use App\Services\FreshMarketService;
use App\Services\RefundService;
use App\Services\Rider\HandoverService;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\RiderNotificationService;
use App\Services\Shop\SellerOrderService;
use Laravel\Sanctum\Sanctum;
use Mockery;

/**
 * ไรเดอร์รอบ 2 — รอบแก้ 2 (ผู้ตรวจอิสระ) เลน backend
 *
 * B2  ร้องเรียนตอนไรเดอร์ยังรอผู้รับ (รูปรอบ 1) → ไรเดอร์ยังถือของ: บอกให้เก็บของไว้ · แอดมินคืนเงิน = คืนสต็อก + สั่งนำของคืนร้าน
 * B3  ทางตันหลังผู้ซื้อยืนยัน: ไรเดอร์แจ้งส่งไม่สำเร็จไม่ได้ · ผู้ซื้อสแกนระหว่างรอผู้รับ = ปิดทันที
 *     · งานใหม่ที่ยกเลิกก่อนรับของไม่ทำให้เรื่องเก่าตัดสินไม่ได้ · ผู้ซื้อยืนยันแล้วไรเดอร์เงียบ → ปลดอัตโนมัติ
 * B4  เงินพักรอตัดสิน → ยกเลิก/คืนเงินทางปกติถูกปฏิเสธ (ร้านค้า + ตลาดสด + แอดมิน) ต้องตัดสินที่แผงการส่งมอบ
 * B5  ยกเลิกชนกับปลดเงิน/สแกน → ไรเดอร์ไม่ได้เงินหลังคืนเงินผู้ซื้อ · ผู้ซื้อได้เงินคืนครั้งเดียว
 * B7  ตัวนับ/ตัวกรองหน้าแอดมินนับเฉพาะเรื่องที่ตัดสินได้จริง
 * B10 ใบเสนอราคาตะกร้าตลาดสด (GP ต่อบรรทัด) = ค่าส่งที่เก็บจริงตอนสั่ง
 */
class HandoverRound2FixesTest extends HandoverTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    // =====================================================
    // B2 ร้องเรียนตอนไรเดอร์ยังถือของ
    // =====================================================

    public function test_dispute_while_rider_still_waits_keeps_item_with_rider_and_admin_refund_restocks(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        $productId = (int) $order->items()->value('product_id');
        Product::whereKey($productId)->update(['stock_quantity' => 99]);
        Order::whereKey($order->id)->update(['stock_deducted_at' => now()]);

        // ไรเดอร์ถ่ายรูปรอบ 1 แล้ว (ยังรอผู้รับ ยังไม่วางของ) → ผู้ซื้อร้องเรียน
        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk()->assertJsonPath('data.handover.status', 'fallback_waiting');
        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'not_received'])
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'disputed')
            ->assertJsonPath('data.job.status', RiderJob::STATUS_AWAITING_RELEASE);

        $riderNote = Notification::where('user_id', $rider->user_id)->where('type', 'handover_disputed')->firstOrFail();
        $this->assertStringContainsString('เก็บสินค้าไว้', $riderNote->title);
        $this->assertStringContainsString('นำคืนร้าน', $riderNote->message);
        $this->assertStringContainsString(
            'ไรเดอร์ยังถือสินค้าอยู่',
            (string) Notification::where('user_id', $admin->id)->where('title', 'ผู้ซื้อร้องเรียนการส่งมอบ ต้องตัดสิน')->value('message')
        );

        // แอดมินคืนเงิน → ของยังอยู่กับไรเดอร์ = คืนสต็อก + บอกไรเดอร์นำของคืนร้าน
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job), ['reason' => 'ผู้ซื้อไม่ได้รับของ ไรเดอร์ยังถือของ'])
            ->assertOk();

        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(100, (int) Product::whereKey($productId)->value('stock_quantity'), 'ของกลับร้าน → คืนสต็อก');
        $this->assertSame(0.0, $this->riderEarningCredits($job));
        $this->assertStringContainsString(
            'นำสินค้าคืนร้าน',
            (string) Notification::where('user_id', $rider->user_id)->where('type', 'handover_resolved')->value('message')
        );

        // เทียบ: ร้องเรียนหลังวางของ (รูปรอบ 2) → ของอยู่กับผู้ซื้อ ข้อความไรเดอร์แบบปกติ
        [$order2, $buyer2] = $this->makeDeferredShopOrder();
        $rider2 = $this->makeRider();
        $job2 = $this->makeHandoverJob($order2, $rider2);
        $this->dropOff($rider2, $job2);
        Sanctum::actingAs($buyer2);
        $this->postJson('/api/v1/orders/shop/'.$order2->id.'/handover/dispute', ['reason' => 'damaged'])->assertOk();
        $note2 = Notification::where('user_id', $rider2->user_id)->where('type', 'handover_disputed')->firstOrFail();
        $this->assertSame('ผู้รับแจ้งร้องเรียนการส่งมอบ', $note2->title);
        $this->assertStringNotContainsString('นำคืนร้าน', $note2->message);
    }

    // =====================================================
    // B3 ทางตันหลังผู้ซื้อยืนยัน
    // =====================================================

    public function test_rider_cannot_fail_after_buyer_confirmed_receipt(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $riderToken = $this->riderGet($rider, $job)->json('data.handover.qr_token');
        $this->buyerScan($buyer, $order, $riderToken)->assertOk()->assertJsonPath('data.handover.status', 'buyer_confirmed');

        Sanctum::actingAs($rider->user);
        $actions = $this->getJson('/api/v1/rider/jobs/'.$job->id)->assertOk()->json('data.job.allowed_actions');
        $this->assertNotContains('fail', $actions);
        $this->assertContains('handover_scan', $actions);

        $response = $this->postJson('/api/v1/rider/jobs/'.$job->id.'/fail', ['reason_code' => 'customer_unreachable'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_TRANSITION');
        $this->assertStringContainsString('ผู้รับยืนยันรับของแล้ว', (string) $response->json('message'));
        $this->assertSame('delivering', $job->fresh()->status);
        $this->assertSame('shipped', $order->fresh()->status, 'ออเดอร์ไม่ย้อนไปรอไรเดอร์ใหม่');

        // ไรเดอร์ยังปิดงานเองได้ด้วยรหัสของผู้ซื้อ
        $code = $this->buyerGet($buyer, $order)->json('data.handover.code');
        $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)->assertOk()->assertJsonPath('data.handover.status', 'completed');
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
    }

    public function test_buyer_scan_while_rider_waits_at_door_completes_immediately(): void
    {
        // QR ของไรเดอร์
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk()->assertJsonPath('data.handover.status', 'fallback_waiting');
        $riderToken = $this->riderGet($rider, $job)->json('data.handover.qr_token');
        $this->assertNotNull($riderToken);

        $this->buyerScan($buyer, $order, $riderToken)->assertOk()
            ->assertJsonPath('data.handover.status', 'completed')
            ->assertJsonPath('data.handover.method', 'qr')
            ->assertJsonPath('data.handover.buyer_confirmed', true);

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001, 'ส่งมอบสำเร็จ → ปล่อยเงินร้านทันที');
        $this->assertNotNull($job->fresh()->delivered_latitude, 'ใช้พิกัดรูปรอบ 1 เป็นจุดส่ง');

        // รหัส 6 หลักของไรเดอร์ (กล้องใช้ไม่ได้)
        [$order2, $buyer2] = $this->makeDeferredShopOrder();
        $rider2 = $this->makeRider();
        $job2 = $this->makeHandoverJob($order2, $rider2);
        $this->riderPhoto($rider2, $job2, 'arrival-photo')->assertOk();
        $riderCode = $this->riderGet($rider2, $job2)->json('data.handover.code');

        Sanctum::actingAs($buyer2);
        $this->postJson('/api/v1/orders/shop/'.$order2->id.'/handover/scan', ['code' => $riderCode])->assertOk()
            ->assertJsonPath('data.handover.status', 'completed')
            ->assertJsonPath('data.handover.method', 'code');
        $this->assertSame('completed', $job2->fresh()->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job2), 0.001);
    }

    public function test_superseded_check_ignores_newer_job_cancelled_before_pickup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // ข้อมูลเก่า (ก่อนแก้): ผู้ซื้อยืนยันรับของ แล้วงานถูกปิดเป็น failed → ร้านเรียกไรเดอร์ใหม่ → งานใหม่ถูกยกเลิกก่อนรับของ
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $old = $this->makeHandoverJob($order, $rider);
        $riderToken = $this->riderGet($rider, $old)->json('data.handover.qr_token');
        $this->buyerScan($buyer, $order, $riderToken)->assertOk()->assertJsonPath('data.handover.status', 'buyer_confirmed');
        RiderJob::whereKey($old->id)->update(['status' => 'failed', 'failed_at' => now(), 'failure_reason' => 'customer_unreachable']);

        $newer = $this->makeHandoverJob($order, $this->makeRider(), 'cancelled', [
            'rider_id' => null, 'picked_up_at' => null, 'cancelled_at' => now(), 'cancelled_by' => 'seller',
        ]);
        $this->assertTrue($this->handovers()->adminCanResolve($old->fresh(), $this->handoverOf($old)), 'งานใหม่ยกเลิกก่อนรับของ = ไม่ได้แทนงานเก่า');
        $this->actingAs($admin)->get(route('admin.rider-jobs.show', $old))->assertOk()->assertSee('คืนเงินผู้ซื้อ');

        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.release', $old), ['reason' => 'ผู้ซื้อยืนยันรับของแล้ว'])->assertOk();
        $this->assertSame('completed', $old->fresh()->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($old), 0.001);
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001);
        $this->assertSame('cancelled', $newer->fresh()->status);

        // งานใหม่ที่รับของไปแล้ว = แทนงานเก่าจริง → ตัดสินที่งานเก่าไม่ได้
        [$order2, $buyer2] = $this->makeDeferredShopOrder();
        $rider2 = $this->makeRider();
        $old2 = $this->makeHandoverJob($order2, $rider2);
        $this->buyerScan($buyer2, $order2, $this->riderGet($rider2, $old2)->json('data.handover.qr_token'))->assertOk();
        RiderJob::whereKey($old2->id)->update(['status' => 'failed', 'failed_at' => now()]);
        $this->makeHandoverJob($order2, $this->makeRider(), 'failed', ['failed_at' => now()]);
        $this->assertFalse($this->handovers()->adminCanResolve($old2->fresh(), $this->handoverOf($old2)));
    }

    public function test_buyer_confirmed_without_rider_confirm_auto_completes_after_window(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $riderToken = $this->riderGet($rider, $job)->json('data.handover.qr_token');
        $autoAt = $this->buyerScan($buyer, $order, $riderToken)->assertOk()
            ->assertJsonPath('data.handover.status', 'buyer_confirmed')
            ->json('data.handover.auto_release_at');
        $this->assertNotNull($autoAt);
        $this->assertEqualsWithDelta(30 * 60, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($autoAt)), 5, 'ค่าเริ่มต้น 30 นาที');

        // ยังไม่ครบเวลา → ไม่ปลด
        $this->travel(29)->minutes();
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->assertSame('delivering', $job->fresh()->status);
        $this->assertSame(0.0, $this->riderEarningCredits($job));

        // ครบเวลา (มือถือไรเดอร์ดับ ไม่ได้สแกน) → ปิดงาน จ่ายไรเดอร์ + ปล่อยเงินร้าน
        $this->travel(2)->minutes();
        $this->artisan('rider:handover-release')->assertSuccessful();

        $handover = $this->handoverOf($job);
        $this->assertSame(DeliveryHandover::STATUS_COMPLETED, $handover->status);
        $this->assertSame('qr', $handover->method);
        $this->assertNull($handover->rider_confirmed_at);
        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001, 'ส่งมอบสำเร็จ → ปล่อยเงินร้านทันที');
        $this->assertSame('online', $rider->fresh()->availability, 'ไรเดอร์ถือของอยู่ → ว่างรับงานใหม่');
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'handover_completed')->count());

        // รันซ้ำ → ไม่จ่ายซ้ำ
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);

        // แอดมินตั้งเวลาเองได้
        Setting::set('rider.handover_buyer_confirmed_release_minutes', '10', 'integer', 'rider');
        $this->assertSame(10, app(HandoverService::class)->buyerConfirmedReleaseMinutes());
    }

    // =====================================================
    // B4 เงินพักรอตัดสิน → ห้ามยกเลิก/คืนเงินทางปกติ
    // =====================================================

    public function test_shop_cancel_and_refund_are_refused_while_handover_money_is_held(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);

        $productId = (int) $order->items()->value('product_id');
        Product::whereKey($productId)->update(['stock_quantity' => 99]);
        Order::whereKey($order->id)->update(['stock_deducted_at' => now()]);

        $this->dropOff($rider, $job);
        $order = $order->fresh();

        // Order::cancel (ร้าน/แอดมิน/ระบบ) → ปฏิเสธพร้อมข้อความชี้ไปหน้างานไรเดอร์
        foreach (['admin', 'seller', 'system'] as $by) {
            try {
                $order->fresh()->cancel('ลองยกเลิก', $by === 'admin' ? (int) $admin->id : null, $by);
                $this->fail("ยกเลิกโดย {$by} ต้องถูกปฏิเสธ");
            } catch (ShopException $e) {
                $this->assertSame(ShopException::HANDOVER_PENDING, $e->errorCode, $by);
                $this->assertSame(409, $e->httpStatus, $by);
                $this->assertStringContainsString('หน้างานไรเดอร์ #'.$job->job_number, $e->getMessage(), $by);
            }
        }

        // ระบบคืนเงินโดยตรง → ปฏิเสธ
        try {
            app(RefundService::class)->processFullRefund($order->fresh(), (int) $admin->id, 'ลองคืนเงิน');
            $this->fail('คืนเงินทางปกติต้องถูกปฏิเสธ');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('ปล่อยเงิน หรือคืนเงินผู้ซื้อ', $e->getMessage());
        }

        // ธงบนหน้าจอ
        $this->assertFalse($order->fresh()->canBeCancelled());
        $this->assertFalse($order->fresh()->canBeRefunded());
        $this->assertFalse($order->fresh()->riderCanDispatch(), 'เรียกไรเดอร์ใหม่ไม่ได้');
        $this->assertNotContains('cancel', app(SellerOrderService::class)->allowedActions($order->fresh(), (int) $seller->id));

        // หน้าแอดมินคำสั่งซื้อ: เปลี่ยนเป็นยกเลิก/คืนเงิน → ข้อความเดียวกัน ไม่มีอะไรเปลี่ยน
        foreach (['cancelled', 'refunded'] as $status) {
            $this->actingAs($admin)
                ->from(route('admin.ecommerce.orders.show', $order))
                ->post(route('admin.ecommerce.orders.status.update', $order), ['status' => $status, 'admin_notes' => 'ลองจากหลังบ้าน'])
                ->assertRedirect()
                ->assertSessionHas('error', fn ($msg) => str_contains((string) $msg, 'หน้างานไรเดอร์'));
        }
        $this->actingAs($admin)->get(route('admin.ecommerce.orders.show', $order))->assertOk()->assertSee('หน้างานไรเดอร์ #'.$job->job_number);

        // หน้างานไรเดอร์: ยกเลิก/ปิดงานแบบปกติ → ปฏิเสธ
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.cancel', $job), ['reason' => 'ลองปิดงาน'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_PENDING');

        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertEqualsWithDelta(0.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(0, Notification::where('user_id', $rider->user_id)->where('title', 'ออเดอร์ถูกยกเลิก กรุณานำของคืนร้าน')->count());

        // ตัดสินที่แผงการส่งมอบ → คืนเงินเต็มจำนวน · ของอยู่กับผู้ซื้อ → ไม่คืนสต็อก
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job), ['reason' => 'ผู้ซื้อไม่ได้รับของ'])->assertOk();
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(99, (int) Product::whereKey($productId)->value('stock_quantity'));

        // ผู้ซื้อยืนยันรับของแล้ว (ไรเดอร์ยังไม่สแกน) ก็ถือว่าเงินพักรอตัดสิน
        [$order2, $buyer2] = $this->makeDeferredShopOrder();
        $rider2 = $this->makeRider();
        $job2 = $this->makeHandoverJob($order2, $rider2);
        $this->buyerScan($buyer2, $order2, $this->riderGet($rider2, $job2)->json('data.handover.qr_token'))->assertOk();
        $this->assertNotNull($order2->fresh()->riderHandoverHoldMessage());
        $this->expectException(ShopException::class);
        $order2->fresh()->cancel('ลองยกเลิก', (int) $admin->id, 'admin');
    }

    public function test_fresh_market_cancel_is_refused_while_handover_money_is_held(): void
    {
        [$service, $listing, $buyer] = $this->freshMarketFixture();
        $admin = User::factory()->create(['role' => 'admin']);

        // วางของแล้ว (รอปลดเงิน) → แอดมินยกเลิกทางปกติไม่ได้
        $order = $this->placeFreshMarketRiderOrder($service, $listing, $buyer);
        $paid = $order->walletPaidAmount();
        $rider = $this->makeRider();
        $job = $this->makeFreshMarketJob($order, $rider);
        $this->dropOff($rider, $job);
        $balanceBefore = $this->walletOf($buyer);

        try {
            $service->cancelOrder($order->fresh(), 'ลองยกเลิก', 'admin', $admin);
            $this->fail('ต้องถูกปฏิเสธ');
        } catch (FreshMarketException $e) {
            $this->assertSame('HANDOVER_PENDING', $e->errorCode());
            $this->assertStringContainsString('หน้างานไรเดอร์ #'.$job->job_number, $e->getMessage());
        }
        $this->assertSame(FreshMarketOrder::STATUS_DELIVERING, $order->fresh()->order_status);
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);
        $this->assertEqualsWithDelta($balanceBefore, $this->walletOf($buyer), 0.001);

        // ข้อมูลเก่า: ร้องเรียนแล้วงานถูกปิดเป็น failed (ออเดอร์ส่งไม่สำเร็จ) → ปุ่มยกเลิก/เรียกไรเดอร์ใหม่หาย + service ปฏิเสธ
        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/fresh-market/'.$order->id.'/handover/dispute', ['reason' => 'not_received'])->assertOk();
        RiderJob::whereKey($job->id)->update(['status' => 'failed', 'failed_at' => now()]);
        FreshMarketOrder::whereKey($order->id)->update(['order_status' => FreshMarketOrder::STATUS_DELIVERY_FAILED]);

        $fresh = $order->fresh();
        $this->assertNotContains('cancel', $fresh->allowedActions('admin'));
        $this->assertFalse($fresh->riderCanDispatch());
        foreach ([
            fn () => $service->cancelOrder($order->fresh(), 'ลองยกเลิก', 'admin', $admin),
            fn () => $service->redispatchRider($order->fresh(), $admin),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('ต้องถูกปฏิเสธ');
            } catch (FreshMarketException $e) {
                $this->assertSame('HANDOVER_PENDING', $e->errorCode());
            }
        }

        // แผงการส่งมอบ → คืนเงินเต็มจำนวนครั้งเดียว
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job), ['reason' => 'ตรวจแล้วไม่พบของ'])->assertOk();
        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $order->fresh()->order_status);
        $this->assertEqualsWithDelta($balanceBefore + $paid, $this->walletOf($buyer), 0.001);
        $this->assertSame(0.0, $this->riderEarningCredits($job));
    }

    // =====================================================
    // B5 ยกเลิกชนกับปลดเงิน/สแกน
    // =====================================================

    public function test_cancel_racing_auto_release_never_pays_rider_and_refunds_buyer_once(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->dropOff($rider, $job);

        // จำลองช่วงชน: คำสั่งยกเลิกผ่านด่านตรวจเงินพักไปก่อนสถานะเปลี่ยน และยังไม่ได้ปิดงานไรเดอร์ (เช่น ทำหลัง commit)
        $this->bypassHoldCheck();
        $this->deferJobCancellation();
        $order->fresh()->cancel('ลูกค้าขอยกเลิก', (int) $admin->id, 'admin');
        $this->restoreRealServices();

        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status, 'งานยังค้างรอปลดเงิน');
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);

        // ครบเวลาปลดเงิน → ตรวจซ้ำหลังล็อก: ออเดอร์คืนเงินแล้ว → ไม่จ่ายไรเดอร์ ปิดงาน + ปิดเรื่อง
        $this->travel(25)->hours();
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->artisan('rider:handover-release')->assertSuccessful();

        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame('order_cancelled', $job->fresh()->failure_reason);
        $this->assertSame(DeliveryHandover::STATUS_REFUNDED, $this->handoverOf($job)->status);
        $this->assertSame(0.0, $this->riderEarningCredits($job));
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001, 'คืนเงินครั้งเดียว');
        $this->assertSame(1, WalletTransaction::where('user_id', $buyer->id)->where('reference_type', 'Order')
            ->where('reference_id', $order->id)->where('type', 'refund')->count());
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count(), 'ไม่แบ่งเงินร้าน');
        $this->assertStringContainsString(
            'ไม่ต้องนำสินค้าคืนร้าน',
            (string) Notification::where('user_id', $rider->user_id)->where('type', 'handover_resolved')->value('message')
        );
        $this->assertSame(1, Notification::where('user_id', $admin->id)->where('title', 'ปิดงานส่งมอบ: ออเดอร์ถูกยกเลิกก่อนปลดเงิน')->count());

        // ไม่ค้างในตัวนับแอดมิน
        $this->actingAs($admin)->get(route('admin.rider-jobs.index'))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['awaiting_release'] === 0 && $s['handover_review'] === 0);
    }

    public function test_scan_after_order_was_refunded_cannot_complete_or_pay_rider(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        $buyerCode = $this->buyerGet($buyer, $order)->json('data.handover.code');
        $riderToken = $this->riderGet($rider, $job)->json('data.handover.qr_token');

        // ออเดอร์ถูกยกเลิก/คืนเงินแล้ว แต่ยังไม่ทันปิดงานไรเดอร์ (ช่วงชน)
        $this->deferJobCancellation();
        $order->fresh()->cancel('ลูกค้าขอยกเลิก', (int) $admin->id, 'admin');
        $this->restoreRealServices();
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('delivering', $job->fresh()->status);

        // ไรเดอร์ยืนยันฝั่งตัวเองได้ (ยังไม่จ่ายเงิน) แต่ผู้ซื้อสแกนปิดงานไม่ได้ → ไม่จ่ายไรเดอร์
        $this->riderScan($rider, $job, ['code' => $buyerCode] + self::NEAR)->assertOk()->assertJsonPath('data.handover.status', 'rider_confirmed');
        $this->buyerScan($buyer, $order, $riderToken)
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_NOT_READY');

        $this->assertSame('delivering', $job->fresh()->status);
        $this->assertNull($this->handoverOf($job)->buyer_confirmed_at, 'rollback ทั้งก้อน');
        $this->assertSame(0.0, $this->riderEarningCredits($job));
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
    }

    public function test_held_job_reaching_cancel_jobs_is_closed_without_return_to_shop_message(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->dropOff($rider, $job);

        // ทางกันพลาด: ถ้าการยกเลิกหลุดด่านมาถึง cancelJobsForSource ระหว่างรอปลดเงิน
        $this->bypassHoldCheck();
        $order->fresh()->cancel('ลูกค้าขอยกเลิก', (int) $admin->id, 'admin');
        $this->restoreRealServices();

        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame(DeliveryHandover::STATUS_REFUNDED, $this->handoverOf($job)->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(0.0, $this->riderEarningCredits($job));
        $this->assertSame(0, Notification::where('user_id', $rider->user_id)->where('title', 'ออเดอร์ถูกยกเลิก กรุณานำของคืนร้าน')->count());
        $this->assertStringContainsString(
            'ไม่ต้องนำสินค้าคืนร้าน',
            (string) Notification::where('user_id', $rider->user_id)->where('type', 'handover_resolved')->value('message')
        );

        $this->travel(25)->hours();
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->assertSame(0.0, $this->riderEarningCredits($job));
    }

    // =====================================================
    // B7 ตัวนับหน้าแอดมิน
    // =====================================================

    public function test_admin_counters_only_count_resolvable_handovers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // a) วางของแล้ว รอปลดเงิน → ตัดสินได้
        [$orderA] = $this->makeDeferredShopOrder();
        $riderA = $this->makeRider();
        $jobA = $this->makeHandoverJob($orderA, $riderA);
        $this->dropOff($riderA, $jobA);

        // b) ข้อมูลเก่า: ร้องเรียนค้างบนงาน failed ที่มีงานใหม่ (รับของแล้ว) แทน → ตัดสินไม่ได้ ไม่นับ
        [$orderB] = $this->makeDeferredShopOrder();
        $jobB = $this->makeHandoverJob($orderB, $this->makeRider());
        $this->markLegacyDisputedFailed($jobB);
        $this->makeHandoverJob($orderB, $this->makeRider(), 'delivering');

        // c) ข้อมูลเก่า: ร้องเรียนค้างบนงาน failed ที่งานใหม่ถูกยกเลิกก่อนรับของ → ยังตัดสินได้ นับ
        [$orderC] = $this->makeDeferredShopOrder();
        $jobC = $this->makeHandoverJob($orderC, $this->makeRider());
        $this->markLegacyDisputedFailed($jobC);
        $this->makeHandoverJob($orderC, $this->makeRider(), 'cancelled', ['rider_id' => null, 'picked_up_at' => null, 'cancelled_at' => now()]);

        $this->assertTrue($this->handovers()->adminCanResolve($jobA->fresh(), $this->handoverOf($jobA)));
        $this->assertFalse($this->handovers()->adminCanResolve($jobB->fresh(), $this->handoverOf($jobB)));
        $this->assertTrue($this->handovers()->adminCanResolve($jobC->fresh(), $this->handoverOf($jobC)));

        $this->actingAs($admin)->get(route('admin.rider-jobs.index'))->assertOk()
            ->assertViewHas('stats', function (array $s) {
                return $s['awaiting_release'] === 1 && $s['disputed'] === 1 && $s['handover_review'] === 2;
            });

        $review = $this->actingAs($admin)->get(route('admin.rider-jobs.index', ['handover_review' => 1]))->assertOk()->viewData('jobs');
        $this->assertEqualsCanonicalizing([$jobA->id, $jobC->id], collect($review->items())->pluck('id')->all());

        $disputed = $this->actingAs($admin)->get(route('admin.rider-jobs.index', ['disputed' => 1]))->assertOk()->viewData('jobs');
        $this->assertSame([$jobC->id], collect($disputed->items())->pluck('id')->all());

        // ตัดสินแล้ว → ตัวนับลดลง
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $jobC), ['reason' => 'ปิดเรื่องเก่า'])->assertOk();
        $this->actingAs($admin)->get(route('admin.rider-jobs.index'))->assertOk()
            ->assertViewHas('stats', fn (array $s) => $s['disputed'] === 0 && $s['handover_review'] === 1);
    }

    // =====================================================
    // B10 ใบเสนอราคาตะกร้าตลาดสด = ที่เก็บจริง
    // =====================================================

    public function test_fresh_market_cart_quote_fee_equals_charged_fee_with_per_line_gp(): void
    {
        [$service, $listing, $buyer, , $seller] = $this->freshMarketFixture(price: 10.05, sellerOverrides: ['rider_free_delivery' => true]);
        $second = FreshMarketListing::create([
            'seller_id' => $seller->id, 'title' => 'ผักกาดขาว', 'price' => 10.05, 'unit' => 'หัว', 'quantity_available' => 10,
            'latitude' => 13.7291, 'longitude' => 100.5210, 'status' => 'active', 'is_available' => true, 'created_via' => 'web',
        ]);

        // สองบรรทัด ฿10.05 (GP 10%): GP ต่อบรรทัด 1.01 × 2 = 2.02 ≠ GP ทั้งตะกร้า 2.01 → รายได้ร้าน 18.08 (ไม่ใช่ 18.09)
        $listings = FreshMarketListing::with('seller')->whereIn('id', [$listing->id, $second->id])->get();
        $perLine = $service->expectedSellerNetForLines($listings->map(fn ($l) => ['listing' => $l, 'line_total' => 10.05])->all());
        $this->assertEqualsWithDelta(18.08, $perLine, 0.001);
        $this->assertEqualsWithDelta(18.09, $service->expectedSellerNet($listings->first(), 20.10), 0.001, 'สูตรเดิม (GP ทั้งตะกร้า) ให้ค่าต่างจากตอนสั่ง');

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/fresh-market/cart/items', ['listing_id' => $listing->id, 'quantity' => 1])->assertCreated();
        $this->postJson('/api/v1/fresh-market/cart/items', ['listing_id' => $second->id, 'quantity' => 1])->assertCreated();
        $quote = $this->getJson('/api/v1/fresh-market/cart/quote?seller_id='.$seller->id.'&latitude='.self::DROP_LAT.'&longitude='.self::DROP_LNG)
            ->assertOk()->json('data');

        $this->assertTrue($quote['available']);
        $this->assertTrue($quote['subsidy_capped'], 'ค่าส่งเต็มเกินรายได้ร้าน → ร้านออกได้ไม่เกินรายได้');
        $this->assertEqualsWithDelta(18.08, $quote['shop_subsidy'], 0.001);

        $order = $service->createOrderFromItems($buyer, $seller->id, [
            ['listing_id' => $listing->id, 'quantity' => 1],
            ['listing_id' => $second->id, 'quantity' => 1],
        ], [
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => self::DROP_LAT,
            'buyer_longitude' => self::DROP_LNG,
            'delivery_address' => '99 ถนนทดสอบ',
            'channel' => 'api',
        ]);
        $order = FreshMarketOrder::findOrFail($order->id);

        $this->assertEqualsWithDelta((float) $quote['fee'], (float) $order->delivery_fee, 0.001, 'ค่าส่งที่แสดง = ค่าส่งที่เก็บจริง');
        $this->assertEqualsWithDelta((float) $quote['shop_subsidy'], (float) $order->delivery_subsidy_amount, 0.001);
        $this->assertEqualsWithDelta((float) $quote['grand_total'], $order->walletPaidAmount(), 0.001, 'ยอดที่หัก = grand_total ในใบเสนอราคา');
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * ไรเดอร์วางของ: รูปรอบ 1 → รอครบ → รูปรอบ 2 (งาน awaiting_release)
     */
    private function dropOff(Rider $rider, RiderJob $job): void
    {
        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk();
        $this->travel(181)->seconds();
        $this->riderPhoto($rider, $job, 'waited-photo')->assertOk()
            ->assertJsonPath('data.handover.status', DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE);
        $this->travelBack();
    }

    /**
     * ข้อมูลเก่า: การส่งมอบถูกร้องเรียนค้าง + งานถูกปิดเป็น failed
     */
    private function markLegacyDisputedFailed(RiderJob $job): void
    {
        $this->handovers()->ensureFor($job)->forceFill([
            'status' => DeliveryHandover::STATUS_DISPUTED,
            'disputed_at' => now(),
            'dispute_reason' => 'not_received',
        ])->save();
        RiderJob::whereKey($job->id)->update(['status' => 'failed', 'failed_at' => now(), 'failure_reason' => 'admin_intervention']);
    }

    /**
     * จำลองคำสั่งยกเลิกที่ผ่านด่านตรวจเงินพักไปก่อนสถานะเปลี่ยน (ช่วงชน)
     */
    private function bypassHoldCheck(): void
    {
        $mock = Mockery::mock(HandoverService::class, [
            app(DeliveryFeeCalculator::class),
            app(RiderJobService::class),
            app(RiderNotificationService::class),
        ])->makePartial();
        $mock->shouldReceive('cancelHoldMessage')->andReturnNull();

        $this->app->instance(HandoverService::class, $mock);
    }

    /**
     * จำลองการยกเลิกที่ยังไม่ทันปิดงานไรเดอร์ (เช่น เดิมตลาดสดปิดงานหลัง commit)
     */
    private function deferJobCancellation(): void
    {
        $mock = Mockery::mock(RiderDispatchService::class)->makePartial();
        $mock->shouldReceive('cancelJobsForSource')->andReturnNull();

        $this->app->instance(RiderDispatchService::class, $mock);
    }

    private function restoreRealServices(): void
    {
        $this->app->forgetInstance(HandoverService::class);
        $this->app->forgetInstance(RiderDispatchService::class);
    }

    /**
     * ร้านตลาดสด (GP 10%) + สินค้า + ผู้ซื้อมีเงิน 1,000 · เปิดไรเดอร์
     *
     * @param  array<string, mixed>  $sellerOverrides
     * @return array{0: FreshMarketService, 1: FreshMarketListing, 2: User, 3: User, 4: FreshMarketSeller}
     */
    private function freshMarketFixture(float $price = 100, array $sellerOverrides = []): array
    {
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => true,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();
        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');

        $buyer = $this->makeUser('สมหญิง ตลาดสด', 1000);
        $sellerUser = $this->makeUser('ร้านผัก');

        $seller = FreshMarketSeller::create(array_merge([
            'user_id' => $sellerUser->id,
            'shop_name' => 'ร้านผักทดสอบ',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7291,
            'longitude' => 100.5210,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ], $sellerOverrides));

        $listing = FreshMarketListing::create([
            'seller_id' => $seller->id,
            'title' => 'ผักบุ้งจีน',
            'price' => $price,
            'unit' => 'กำ',
            'quantity_available' => 10,
            'latitude' => 13.7291,
            'longitude' => 100.5210,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        return [new FreshMarketService, $listing->fresh(), $buyer, $sellerUser, $seller->fresh()];
    }

    private function placeFreshMarketRiderOrder(FreshMarketService $service, FreshMarketListing $listing, User $buyer): FreshMarketOrder
    {
        return $service->createOrder($buyer, $listing->fresh(), [
            'quantity' => 1,
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => self::DROP_LAT,
            'buyer_longitude' => self::DROP_LNG,
            'delivery_address' => '99 ถนนทดสอบ แขวงทดสอบ',
        ]);
    }

    /**
     * งานไรเดอร์ของออเดอร์ตลาดสด (ไรเดอร์ถือของ กำลังจัดส่ง)
     */
    private function makeFreshMarketJob(FreshMarketOrder $order, Rider $rider): RiderJob
    {
        $fee = round((float) $order->delivery_fee, 2);
        $earn = round($fee * 0.8, 2);

        $job = new RiderJob;
        $job->forceFill([
            'rider_id' => $rider->id,
            'job_type' => 'fresh_market',
            'source_type' => $order->getMorphClass(),
            'source_id' => $order->id,
            'customer_id' => $order->buyer_id,
            'title' => 'ส่งของตลาดสด '.$order->order_number,
            'pickup_address' => 'ร้านผักทดสอบ',
            'pickup_latitude' => 13.7291,
            'pickup_longitude' => 100.5210,
            'delivery_address' => '99 ถนนทดสอบ',
            'delivery_latitude' => self::DROP_LAT,
            'delivery_longitude' => self::DROP_LNG,
            'total_fee' => $fee,
            'rider_earnings' => $earn,
            'platform_fee' => round($fee - $earn, 2),
            'shop_bonus' => (float) $order->rider_bonus_amount,
            'cod_amount' => 0,
            'status' => 'delivering',
            'handover_required' => true,
            'accepted_at' => now()->subMinutes(30),
            'picked_up_at' => now()->subMinutes(20),
            'tracking_token' => \Illuminate\Support\Str::random(48),
            'tracking_expires_at' => now()->addDay(),
        ])->save();

        FreshMarketOrder::whereKey($order->id)->update([
            'order_status' => FreshMarketOrder::STATUS_DELIVERING,
            'rider_job_id' => $job->id,
            'rider_id' => $rider->id,
            'accepted_at' => now()->subHour(),
            'ready_at' => now()->subMinutes(40),
        ]);

        return $job->fresh();
    }
}
