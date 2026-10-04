<?php

namespace Tests\Feature\RiderR2;

use App\Models\DeliveryHandover;
use App\Models\EarningsLedger;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

/**
 * ไรเดอร์รอบ 2 — รอบแก้หลังรีวิว เลน handover/state (FIXES.md §A2–§A7, §B)
 *
 * ครอบคลุม: server_now · รหัส 6 หลักของไรเดอร์ (ผู้ซื้อกรอก) + ล็อกแยกตัวนับ · ผู้ซื้อกด "ได้รับของแล้ว"
 * · ร้องเรียนแล้วปล่อยไรเดอร์ (awaiting_release) · ไรเดอร์แจ้งส่งไม่สำเร็จไม่ได้เมื่อวางของ/ถูกร้องเรียน
 * · แอดมินตัดสินได้ทุกสถานะที่ยังไม่จบ (รวมงาน failed) · ผู้ซื้อยืนยันแล้วไม่ขังไรเดอร์ด้วยรัศมี
 * · ตำแหน่งบนเซิร์ฟเวอร์ (สด/เก่า) · role ใน push · ไม่เปิดรับงานให้ไรเดอร์ที่ปิดอยู่ · งาน COD ไม่มีปุ่มวางของ
 * · แอดมินคืนเงินไม่คืนสต็อก + แจ้งผู้ซื้อข้อความเดียว · ประวัติงานรวมงานรอปลดเงิน · งานแบบเดิมคงระยะพักเงิน
 */
class HandoverFixesTest extends HandoverTestCase
{
    // =====================================================
    // §A2 server_now + §A3 รหัสของไรเดอร์
    // =====================================================

    public function test_payloads_include_server_now_and_rider_code(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $buyerView = $this->buyerGet($buyer, $order)->assertOk()->json('data');
        $riderView = $this->riderGet($rider, $job)->assertOk()->json('data');

        foreach ([$buyerView, $riderView] as $payload) {
            $this->assertArrayHasKey('server_now', $payload);
            $this->assertLessThanOrEqual(5, abs(now()->diffInSeconds(\Illuminate\Support\Carbon::parse($payload['server_now']))));
        }

        // ไรเดอร์เห็นรหัส 6 หลักของตัวเอง (คนละค่ากับของผู้ซื้อ) · ผู้ซื้อยังกดได้รับของไม่ได้
        $this->assertMatchesRegularExpression('/^\d{6}$/', $riderView['handover']['code']);
        $this->assertSame($this->handovers()->riderCodeFor($this->handoverOf($job)), $riderView['handover']['code']);
        $this->assertFalse($buyerView['handover']['can_confirm_received']);

        // ไรเดอร์ยังไม่รับของ → ไม่แสดงรหัส
        [$order2] = $this->makeDeferredShopOrder();
        $job2 = $this->makeHandoverJob($order2, $this->makeRider(), 'accepted');
        $this->riderGet($job2->rider, $job2)->assertOk()->assertJsonPath('data.handover.code', null);
    }

    public function test_buyer_can_enter_rider_code_with_its_own_lockout(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $riderCode = $this->riderGet($rider, $job)->json('data.handover.code');
        $wrong = $riderCode === '000000' ? '111111' : '000000';

        Sanctum::actingAs($buyer);
        $url = '/api/v1/orders/shop/'.$order->id.'/handover/scan';

        // ไม่ส่งอะไรมาเลย = ตรวจข้อมูลไม่ผ่าน
        $this->postJson($url, [])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        for ($i = 1; $i <= 4; $i++) {
            $this->postJson($url, ['code' => $wrong])
                ->assertStatus(422)
                ->assertJsonPath('code', 'HANDOVER_CODE_INVALID')
                ->assertJsonPath('data.attempts_left', 5 - $i);
        }

        $this->postJson($url, ['code' => $wrong])->assertStatus(429)->assertJsonPath('code', 'HANDOVER_CODE_LOCKED');
        // ระหว่างล็อก รหัสถูกก็ใช้ไม่ได้
        $this->postJson($url, ['code' => $riderCode])->assertStatus(429)->assertJsonPath('code', 'HANDOVER_CODE_LOCKED');

        // ตัวนับแยกฝั่ง: ฝั่งไรเดอร์ (กรอกรหัสผู้ซื้อ) ยังไม่ถูกแตะ
        $handover = $this->handoverOf($job);
        $this->assertSame(5, (int) $handover->rider_code_attempts);
        $this->assertSame(0, (int) $handover->code_attempts);
        $this->assertNull($handover->code_locked_until);
        $buyerCode = $this->buyerGet($buyer, $order)->json('data.handover.code');
        $this->riderScan($rider, $job, ['code' => $buyerCode] + self::NEAR)
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'rider_confirmed');

        // รหัสของผู้ซื้อใช้แทนรหัสไรเดอร์ไม่ได้ (คนละฝั่ง) · พ้นเวลาล็อกแล้วรหัสไรเดอร์ใช้ได้ → ปิดงานด้วยวิธี code
        $this->travel(11)->minutes();
        Sanctum::actingAs($buyer);
        if ($buyerCode !== $riderCode) {
            $this->postJson($url, ['code' => $buyerCode])->assertStatus(422)->assertJsonPath('code', 'HANDOVER_CODE_INVALID');
        }
        $this->postJson($url, ['code' => $riderCode])
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'completed')
            ->assertJsonPath('data.handover.method', 'code');

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
    }

    // =====================================================
    // §A4 ผู้ซื้อกด "ได้รับของแล้ว"
    // =====================================================

    public function test_buyer_confirm_received_while_rider_waits_completes_immediately(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        Sanctum::actingAs($buyer);
        $url = '/api/v1/orders/shop/'.$order->id.'/handover/confirm-received';

        // ยังไม่เข้าทางสำรอง → ยืนยันไม่ได้
        $this->postJson($url)->assertStatus(409)->assertJsonPath('code', 'HANDOVER_NOT_READY');

        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk();
        $this->buyerGet($buyer, $order)->assertJsonPath('data.handover.can_confirm_received', true);

        Sanctum::actingAs($buyer);
        $this->postJson($url)->assertOk()
            ->assertJsonPath('data.handover.status', 'completed')
            ->assertJsonPath('data.handover.method', 'buyer_confirm')
            ->assertJsonPath('data.handover.buyer_confirmed', true)
            ->assertJsonPath('data.handover.can_confirm_received', false);

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('online', $rider->fresh()->availability, 'ไรเดอร์ถือของอยู่ → ว่างรับงานใหม่');
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001, 'ปล่อยเงินร้านทันที (ส่งมอบสำเร็จ)');

        // กดซ้ำ → ได้ผลเดิม ไม่จ่ายซ้ำ
        Sanctum::actingAs($buyer);
        $this->postJson($url)->assertOk()->assertJsonPath('data.handover.method', 'buyer_confirm');
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'handover_completed')->count());
    }

    public function test_buyer_confirm_received_after_drop_off_keeps_offline_rider_offline(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $this->dropOff($rider, $job);
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);

        // ไรเดอร์ปิดรับงานไปแล้ว
        Rider::whereKey($rider->id)->update(['availability' => 'offline']);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/confirm-received')->assertOk()
            ->assertJsonPath('data.handover.status', 'completed')
            ->assertJsonPath('data.handover.method', 'buyer_confirm');

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame('offline', $rider->fresh()->availability, 'งานรอปลดเงินจบ → ห้ามเปิดรับงานให้ไรเดอร์เอง');
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertSame(EarningsLedger::STATUS_PAID, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->value('status'));

        // ร้องเรียนแล้ว → กดได้รับของไม่ได้ (ทีมงานตัดสิน)
        [$order2, $buyer2] = $this->makeDeferredShopOrder();
        $job2 = $this->makeHandoverJob($order2, $this->makeRider());
        $this->dropOff($job2->rider, $job2);
        Sanctum::actingAs($buyer2);
        $this->postJson('/api/v1/orders/shop/'.$order2->id.'/handover/dispute', ['reason' => 'not_received'])->assertOk();
        $this->postJson('/api/v1/orders/shop/'.$order2->id.'/handover/confirm-received')
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_NOT_READY');
    }

    // =====================================================
    // §A6 ร้องเรียนปล่อยไรเดอร์ · ไรเดอร์แจ้งส่งไม่สำเร็จไม่ได้
    // =====================================================

    public function test_dispute_while_delivering_frees_rider_and_rider_cannot_fail(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        $code = $this->buyerGet($buyer, $order)->json('data.handover.code');
        $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)->assertOk();

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'wrong_item'])
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'disputed')
            ->assertJsonPath('data.job.status', RiderJob::STATUS_AWAITING_RELEASE);

        $job->refresh();
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->status);
        $this->assertSame('online', $rider->fresh()->availability, 'ร้องเรียนแล้วไรเดอร์รับงานใหม่ได้');
        $this->assertFalse($rider->fresh()->hasActiveJob());
        $this->assertSame('shipped', $order->fresh()->status, 'เงินยังพัก ออเดอร์ยังไม่ส่งถึง');
        $this->assertSame(1, Notification::where('user_id', $rider->user_id)->where('type', 'handover_disputed')->count());

        // ไรเดอร์ทำอะไรต่อไม่ได้ + แจ้งส่งไม่สำเร็จไม่ได้
        Sanctum::actingAs($rider->user);
        $this->assertSame([], $this->getJson('/api/v1/rider/jobs/'.$job->id)->assertOk()->json('data.job.allowed_actions'));
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/fail', ['reason_code' => 'customer_unreachable'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_TRANSITION');
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);

        // ร้องเรียนอยู่ → ไม่ปลดอัตโนมัติแม้ครบเวลา
        $this->travel(25)->hours();
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);
        $this->assertSame(0.0, $this->riderEarningCredits($job));

        // แอดมินปล่อยเงินได้
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.release', $job), [])->assertOk();
        $this->assertSame('completed', $job->fresh()->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
    }

    public function test_rider_cannot_fail_after_drop_off_or_on_disputed_handover(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $this->dropOff($rider, $job);

        Sanctum::actingAs($rider->user);
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/fail', ['reason_code' => 'customer_unreachable'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_TRANSITION');
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);

        // ข้อมูลเก่า: ร้องเรียนค้างอยู่ทั้งที่งานยังกำลังส่ง → ไรเดอร์ก็แจ้งส่งไม่สำเร็จไม่ได้ และไม่มีปุ่ม
        [$order2] = $this->makeDeferredShopOrder();
        $rider2 = $this->makeRider();
        $job2 = $this->makeHandoverJob($order2, $rider2);
        $this->handovers()->ensureFor($job2)->forceFill([
            'status' => DeliveryHandover::STATUS_DISPUTED,
            'disputed_at' => now(),
            'dispute_reason' => 'damaged',
        ])->save();

        Sanctum::actingAs($rider2->user);
        $this->assertSame([], $this->getJson('/api/v1/rider/jobs/'.$job2->id)->assertOk()->json('data.job.allowed_actions'));
        $this->postJson('/api/v1/rider/jobs/'.$job2->id.'/fail', ['reason_code' => 'customer_unreachable'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_TRANSITION');
        $this->assertSame('delivering', $job2->fresh()->status);
    }

    // =====================================================
    // §A6/F4 แอดมินตัดสินได้ทุกสถานะที่ยังไม่จบ
    // =====================================================

    public function test_admin_can_release_from_waiting_rider_confirmed_and_buyer_confirmed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['waiting', 'rider_confirmed', 'buyer_confirmed'] as $state) {
            [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
            $rider = $this->makeRider();
            $job = $this->makeHandoverJob($order, $rider);

            if ($state === 'rider_confirmed') {
                $code = $this->buyerGet($buyer, $order)->json('data.handover.code');
                $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)->assertOk();
            } elseif ($state === 'buyer_confirmed') {
                $token = $this->riderGet($rider, $job)->json('data.handover.qr_token');
                $this->buyerScan($buyer, $order, $token)->assertOk();
            } else {
                $this->buyerGet($buyer, $order)->assertOk();
            }

            $this->assertSame($state, $this->handoverOf($job)->status);

            $this->actingAs($admin)->get(route('admin.rider-jobs.show', $job))->assertOk()->assertSee('คืนเงินผู้ซื้อ');
            $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.release', $job), ['reason' => 'ตรวจกล้องแล้ว'])
                ->assertOk();

            $this->assertSame('completed', $job->fresh()->status, $state);
            $this->assertSame(DeliveryHandover::STATUS_RELEASED, $this->handoverOf($job)->status, $state);
            $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001, $state);
            $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001, $state);
            $this->assertSame('online', $rider->fresh()->availability, $state);
        }
    }

    public function test_admin_release_of_a_failed_job_pays_out_but_not_when_order_was_refunded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // ไรเดอร์แจ้งส่งไม่สำเร็จ (ผู้ซื้อยืนยันรับของไปแล้วจริง) → แอดมินตรวจแล้วปล่อยเงิน
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $token = $this->riderGet($rider, $job)->json('data.handover.qr_token');
        $this->buyerScan($buyer, $order, $token)->assertOk()->assertJsonPath('data.handover.status', 'buyer_confirmed');

        Sanctum::actingAs($rider->user);
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/fail', ['reason_code' => 'customer_unreachable'])->assertOk();
        $this->assertSame('failed', $job->fresh()->status);
        Rider::whereKey($rider->id)->update(['availability' => 'offline']);

        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.release', $job), [])->assertOk();

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame(DeliveryHandover::STATUS_RELEASED, $this->handoverOf($job)->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001);
        $this->assertSame('offline', $rider->fresh()->availability, 'งานที่ปิดไปแล้ว ห้ามเปิดรับงานให้ไรเดอร์');

        // ออเดอร์ถูกยกเลิก/คืนเงินไปแล้ว → ปล่อยเงินไม่ได้ · คืนเงิน = ปิดเรื่องอย่างเดียว (ไม่คืนซ้ำ)
        [$order2, $buyer2] = $this->makeDeferredShopOrder();
        $rider2 = $this->makeRider();
        $job2 = $this->makeHandoverJob($order2, $rider2);
        $this->buyerGet($buyer2, $order2)->assertOk();
        Sanctum::actingAs($rider2->user);
        $this->postJson('/api/v1/rider/jobs/'.$job2->id.'/fail', ['reason_code' => 'customer_unreachable'])->assertOk();
        $order2->fresh()->cancel('ลูกค้าขอยกเลิก', (int) $admin->id, 'admin');
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer2), 0.001);

        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.release', $job2), [])
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_NOT_READY');
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job2), ['reason' => 'ปิดเรื่อง ออเดอร์ยกเลิกแล้ว'])
            ->assertOk();

        $this->assertSame(DeliveryHandover::STATUS_REFUNDED, $this->handoverOf($job2)->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer2), 0.001, 'ไม่คืนเงินซ้ำ');
        $this->assertSame(0.0, $this->riderEarningCredits($job2));

        // งานเก่าที่มีงานส่งใหม่แทนแล้ว → แอดมินตัดสินที่งานเก่าไม่ได้
        [$order3, $buyer3] = $this->makeDeferredShopOrder();
        $rider3 = $this->makeRider();
        $old = $this->makeHandoverJob($order3, $rider3);
        $this->buyerGet($buyer3, $order3)->assertOk();
        Sanctum::actingAs($rider3->user);
        $this->postJson('/api/v1/rider/jobs/'.$old->id.'/fail', ['reason_code' => 'wrong_address'])->assertOk();
        $this->makeHandoverJob($order3, $this->makeRider(), 'pending', ['rider_id' => null]);
        $this->assertFalse($this->handovers()->adminCanResolve($old->fresh(), $this->handoverOf($old)));
    }

    public function test_admin_refund_keeps_stock_out_and_buyer_gets_one_message(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        // สต็อกถูกตัดตอนสั่งแล้ว (99 จาก 100)
        $productId = (int) $order->items()->value('product_id');
        Product::whereKey($productId)->update(['stock_quantity' => 99]);
        Order::whereKey($order->id)->update(['stock_deducted_at' => now()]);

        $this->dropOff($rider, $job);
        Rider::whereKey($rider->id)->update(['availability' => 'offline']);

        $before = [
            'buyer' => Notification::where('user_id', $buyer->id)->count(),
            'seller' => Notification::where('user_id', $seller->id)->count(),
            'rider' => Notification::where('user_id', $rider->user_id)->count(),
        ];

        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job), ['reason' => 'ตรวจกล้องแล้วไม่พบของ'])
            ->assertOk();

        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(99, (int) Product::whereKey($productId)->value('stock_quantity'), 'ของออกจากร้านไปแล้ว ไม่คืนสต็อก');
        $this->assertSame('offline', $rider->fresh()->availability, 'งานรอปลดเงิน → ห้ามเปิดรับงานให้ไรเดอร์');

        // ผู้ซื้อ/ร้าน/ไรเดอร์ ได้ข้อความเดียว (handover_resolved) ที่ตรงเหตุการณ์
        foreach (['buyer' => $buyer->id, 'seller' => $seller->id, 'rider' => (int) $rider->user_id] as $role => $userId) {
            $new = Notification::where('user_id', $userId)->orderBy('id')->get()->slice($before[$role]);
            $this->assertCount(1, $new, $role);
            $this->assertSame('handover_resolved', $new->first()->type, $role);
            $this->assertSame($role, $new->first()->data['role'] ?? null, $role);
            $this->assertSame('refund', $new->first()->data['resolution'] ?? null, $role);
        }
        $this->assertStringContainsString('คืนเงินเต็มจำนวน', (string) Notification::where('user_id', $buyer->id)->where('type', 'handover_resolved')->value('message'));

        // แอดมิน: ข้อความตรงเหตุการณ์ ไม่ใช่ "งานส่งไม่สำเร็จ ต้องประสานคืนของ"
        $this->assertSame(1, Notification::where('user_id', $admin->id)->where('title', 'คืนเงินผู้ซื้อแล้ว (ตัดสินการส่งมอบ)')->count());
        $this->assertSame(0, Notification::where('user_id', $admin->id)->where('title', 'งานส่งไม่สำเร็จ ต้องประสานคืนของ')->count());
    }

    public function test_admin_refund_while_rider_still_holds_item_restocks_and_asks_rider_to_return_it(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        $productId = (int) $order->items()->value('product_id');
        Product::whereKey($productId)->update(['stock_quantity' => 99]);
        Order::whereKey($order->id)->update(['stock_deducted_at' => now()]);

        // ยังไม่มีใครสแกน (ไรเดอร์ถือของอยู่) → แอดมินคืนเงินได้
        $this->buyerGet($buyer, $order)->assertOk()->assertJsonPath('data.handover.status', 'waiting');
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job), ['reason' => 'ผู้ซื้อขอยกเลิก ไรเดอร์ยังไม่ถึง'])
            ->assertOk();

        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
        $this->assertSame(100, (int) Product::whereKey($productId)->value('stock_quantity'), 'ของยังอยู่กับไรเดอร์ → กลับร้าน คืนสต็อก');
        $this->assertSame('online', $rider->fresh()->availability, 'ไรเดอร์ถือของอยู่ → ว่างรับงานใหม่');
        $this->assertStringContainsString(
            'นำสินค้าคืนร้าน',
            (string) Notification::where('user_id', $rider->user_id)->where('type', 'handover_resolved')->value('message')
        );
    }

    // =====================================================
    // รัศมีจุดส่ง (L3 / M6)
    // =====================================================

    public function test_rider_confirm_after_buyer_confirmed_is_not_blocked_by_geofence(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $buyerToken = $this->buyerGet($buyer, $order)->json('data.handover.qr_token');
        $riderToken = $this->riderGet($rider, $job)->json('data.handover.qr_token');
        $this->buyerScan($buyer, $order, $riderToken)->assertOk()->assertJsonPath('data.handover.status', 'buyer_confirmed');

        // ผู้ซื้อยืนยันรับของแล้ว → ไรเดอร์ยืนยันจากที่ไกลได้ (บันทึกระยะไว้)
        Rider::whereKey($rider->id)->update(['last_latitude' => self::FAR['latitude'], 'last_longitude' => self::FAR['longitude'], 'last_location_update' => now()]);
        $this->riderScan($rider->fresh(), $job, ['token' => $buyerToken] + self::FAR)
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'completed');

        $this->assertGreaterThan(1000, (int) $this->handoverOf($job)->rider_confirm_distance_m);
        $this->assertSame('completed', $job->fresh()->status);
    }

    public function test_fresh_server_location_far_from_dropoff_is_rejected_but_stale_is_ignored(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $code = $this->buyerGet($buyer, $order)->json('data.handover.code');

        // คำขอบอกว่าอยู่หน้าบ้าน แต่ตำแหน่งล่าสุดบนเซิร์ฟเวอร์ (1 นาทีก่อน) ห่าง ~1.1 กม.
        Rider::whereKey($rider->id)->update([
            'last_latitude' => self::FAR['latitude'],
            'last_longitude' => self::FAR['longitude'],
            'last_location_update' => now()->subMinute(),
        ]);
        $far = $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)
            ->assertStatus(422)
            ->assertJsonPath('code', 'TOO_FAR_FROM_DROPOFF')
            ->json('data');
        $this->assertGreaterThan(1000, $far['distance_m']);
        $this->riderPhoto($rider, $job, 'arrival-photo')->assertStatus(422)->assertJsonPath('code', 'TOO_FAR_FROM_DROPOFF');
        $this->assertNull($this->handoverOf($job)->rider_confirmed_at);

        // ตำแหน่งบนเซิร์ฟเวอร์ห่าง ~250 ม. (ในระยะผ่อนผัน รัศมี 150 + 200) → ผ่าน
        Rider::whereKey($rider->id)->update(['last_latitude' => self::DROP_LAT + 0.00225, 'last_longitude' => self::DROP_LNG, 'last_location_update' => now()]);
        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk()->assertJsonPath('data.handover.status', 'fallback_waiting');

        // ตำแหน่งบนเซิร์ฟเวอร์เก่าเกิน 2 นาที → เชื่อพิกัดในคำขอ
        Rider::whereKey($rider->id)->update([
            'last_latitude' => self::FAR['latitude'],
            'last_longitude' => self::FAR['longitude'],
            'last_location_update' => now()->subMinutes(3),
        ]);
        $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'rider_confirmed');
    }

    // =====================================================
    // §A5 role ใน push
    // =====================================================

    public function test_push_payloads_carry_role_per_recipient(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'picked_up');

        // delivery_update (เปลี่ยนสถานะงาน) — ผู้ซื้อกับร้านได้ payload ของตัวเอง
        Sanctum::actingAs($rider->user);
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/status', ['status' => 'delivering'])->assertOk();

        $buyerUpdate = Notification::where('user_id', $buyer->id)->where('type', 'delivery_update')->latest('id')->firstOrFail();
        $sellerUpdate = Notification::where('user_id', $seller->id)->where('type', 'delivery_update')->latest('id')->firstOrFail();
        $this->assertSame('buyer', $buyerUpdate->data['role']);
        $this->assertSame('order', $buyerUpdate->data['screen']);
        $this->assertSame('seller', $sellerUpdate->data['role']);
        $this->assertSame('merchant-order', $sellerUpdate->data['screen']);
        foreach ([$buyerUpdate, $sellerUpdate] as $n) {
            $this->assertSame('shop', $n->data['source']);
            $this->assertSame($order->id, $n->data['order_id']);
            $this->assertSame($job->id, $n->data['job_id']);
            $this->assertSame('delivering', $n->data['event']);
        }
        $this->assertStringContainsString('ถึงลูกค้า', $sellerUpdate->message);

        // handover_* — role ตามผู้รับ
        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk();
        $arrived = Notification::where('user_id', $buyer->id)->where('type', 'handover_arrived')->firstOrFail();
        $this->assertSame('buyer', $arrived->data['role']);
        $this->assertSame('order-handover', $arrived->data['screen']);

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/confirm-received')->assertOk();

        $expect = [
            [$buyer->id, 'buyer', 'order'],
            [(int) $rider->user_id, 'rider', 'rider-job-detail'],
            [$seller->id, 'seller', 'merchant-order'],
        ];
        foreach ($expect as [$userId, $role, $screen]) {
            $n = Notification::where('user_id', $userId)->where('type', 'handover_completed')->firstOrFail();
            $this->assertSame($role, $n->data['role'], $role);
            $this->assertSame($screen, $n->data['screen'], $role);
            $this->assertSame('shop', $n->data['source'], $role);
            $this->assertSame($order->id, $n->data['order_id'], $role);
            $this->assertSame($job->id, $n->data['job_id'], $role);
        }
    }

    // =====================================================
    // F8 ไม่เปิดรับงานให้ไรเดอร์ที่ปิดอยู่
    // =====================================================

    public function test_auto_release_does_not_flip_offline_rider_online(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $this->dropOff($rider, $job);
        $this->assertSame('online', $rider->fresh()->availability, 'วางของแล้วไรเดอร์ว่าง');
        Rider::whereKey($rider->id)->update(['availability' => 'offline']);

        $this->travel(25)->hours();
        $this->artisan('rider:handover-release')->assertSuccessful();

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertSame('offline', $rider->fresh()->availability);
    }

    // =====================================================
    // F10 งาน COD
    // =====================================================

    public function test_cod_job_never_offers_drop_off_actions(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'delivering', ['cod_amount' => 140]);

        Sanctum::actingAs($rider->user);
        $this->assertSame(['handover_scan', 'fail'], $this->getJson('/api/v1/rider/jobs/'.$job->id)->assertOk()->json('data.job.allowed_actions'));

        // แม้ข้อมูลจะอยู่ในขั้นรอผู้รับ (เช่นเปลี่ยนเป็น COD ภายหลัง) ก็ไม่มีปุ่มรูปรอบ 2
        $this->handovers()->ensureFor($job)->forceFill([
            'status' => DeliveryHandover::STATUS_FALLBACK_WAITING,
            'arrival_photo_at' => now()->subMinutes(5),
            'wait_until' => now()->subMinute(),
        ])->save();

        $this->assertNotContains('waited_photo', $this->getJson('/api/v1/rider/jobs/'.$job->id)->json('data.job.allowed_actions'));
        $this->riderGet($rider, $job)->assertOk()
            ->assertJsonPath('data.handover.can_waited_photo', false)
            ->assertJsonPath('data.handover.can_arrival_photo', false);
    }

    // =====================================================
    // §A7 ประวัติงาน + หน้าแอดมิน
    // =====================================================

    public function test_history_and_admin_list_show_awaiting_release_jobs(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $this->dropOff($rider, $job);

        Sanctum::actingAs($rider->user);
        $jobs = $this->getJson('/api/v1/rider/jobs/history')->assertOk()->json('data.jobs');
        $this->assertSame([$job->id], array_column($jobs, 'id'));
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $jobs[0]['status']);
        $this->assertSame('fallback_pending_release', $jobs[0]['handover']['status']);

        $this->getJson('/api/v1/rider/jobs/history?status=awaiting_release')->assertOk()->assertJsonCount(1, 'data.jobs');
        $this->getJson('/api/v1/rider/jobs/history?status=completed')->assertOk()->assertJsonCount(0, 'data.jobs');
        $this->getJson('/api/v1/rider/jobs/history?status=delivering')->assertStatus(422);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.rider-jobs.index', ['handover_review' => 1]))
            ->assertOk()
            ->assertSee($job->job_number)
            ->assertSee('ส่งมอบรอแอดมิน');
    }

    // =====================================================
    // §A1 งานแบบเดิมคงระยะพักเงิน
    // =====================================================

    public function test_legacy_delivered_deferred_order_keeps_normal_holding_period(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'delivering', ['handover_required' => false]);

        Sanctum::actingAs($rider->user);
        $this->post('/api/v1/rider/jobs/'.$job->id.'/deliver', [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ] + self::NEAR, ['Accept' => 'application/json'])->assertOk();

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);

        // แบ่งเงินแล้ว แต่ไม่ปล่อยเข้ากระเป๋าร้านทันที (พักตามปกติ 3 วัน)
        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->firstOrFail();
        $this->assertNotSame(EarningsLedger::STATUS_PAID, $ledger->status);
        $this->assertEqualsWithDelta(0.0, $this->walletOf($seller), 0.001);
        $this->assertFalse(app(\App\Services\SellerPayoutService::class)->deferredHandoverDone($order->fresh()));

        // ผู้ซื้อเห็นว่าไม่ต้องสแกน
        $this->buyerGet($buyer, $order)->assertOk()->assertJsonPath('data.handover.required', false);
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
}
