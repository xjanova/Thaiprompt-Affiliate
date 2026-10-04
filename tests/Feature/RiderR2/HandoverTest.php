<?php

namespace Tests\Feature\RiderR2;

use App\Models\DeliveryHandover;
use App\Models\EarningsLedger;
use App\Models\Notification;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Rider\HandoverService;
use App\Services\RiderEarningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * ไรเดอร์รอบ 2 (เลน money) — ส่งมอบของด้วยการสแกน QR ใส่กัน / รหัส 6 หลัก / รูป 2 รอบ / ร้องเรียน
 *
 * ครอบคลุม: สแกนครบสองฝ่าย · รหัส + ล็อกหลังผิด 5 ครั้ง · QR ซ้ำ/ผิดคน/ผิดฝั่ง/งานอื่น/หมดอายุ · รัศมีจุดส่ง
 * · กดซ้ำไม่จ่ายซ้ำ · ทางสำรองรูป 2 รอบ + เวลารอ · ปลดเงินอัตโนมัติ · ร้องเรียน → แอดมินปล่อย/คืนเงิน
 * · ปุ่มส่งของแบบเดิมโดนปฏิเสธ (HANDOVER_REQUIRED) · ข้อมูลรายการงานมี handover + allowed_actions ใหม่
 */
class HandoverTest extends HandoverTestCase
{
    // =====================================================
    // สแกนครบสองฝ่าย
    // =====================================================

    public function test_both_scans_complete_job_and_settle_everything_once(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 10);
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        // ผู้ซื้อเห็น QR + รหัส 6 หลัก · ยังไม่มีการแบ่งเงิน
        $buyerView = $this->buyerGet($buyer, $order)->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.handover.required', true)
            ->assertJsonPath('data.handover.status', 'waiting')
            ->assertJsonPath('data.handover.can_dispute', false)
            ->assertJsonPath('data.job.id', $job->id)
            ->assertJsonPath('data.rider.display_name', 'สมชาย ข.')
            ->assertJsonPath('data.rider.plate_masked', '1กข **34')
            ->assertJsonPath('data.settlement', null)
            ->json('data');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $buyerView['handover']['code']);
        $this->assertStringStartsWith('TPH1.', $buyerView['handover']['qr_token']);
        $this->assertNotNull($buyerView['handover']['qr_expires_at']);
        $this->assertIsInt($buyerView['job']['distance_to_dropoff_m']);
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());

        // ไรเดอร์สแกน QR ของผู้ซื้อ (ในรัศมี)
        $this->riderScan($rider, $job, ['token' => $buyerView['handover']['qr_token']] + self::NEAR)
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'rider_confirmed')
            ->assertJsonPath('data.handover.rider_confirmed', true)
            ->assertJsonPath('data.buyer.display_name', 'สมหญิง ใ.');

        $this->assertSame('delivering', $job->fresh()->status, 'ฝั่งเดียวยังไม่ปิดงาน');

        // ผู้ซื้อสแกน QR ของไรเดอร์ → ปิดงาน
        $riderToken = $this->riderGet($rider, $job)->assertOk()->json('data.handover.qr_token');
        $done = $this->buyerScan($buyer, $order, $riderToken)->assertOk()
            ->assertJsonPath('data.handover.status', 'completed')
            ->assertJsonPath('data.handover.method', 'qr')
            ->assertJsonPath('data.handover.buyer_confirmed', true)
            ->json('data');

        $job->refresh();
        $this->assertSame('completed', $job->status);
        $this->assertNotNull($job->delivered_at);
        $this->assertSame('delivered', $order->fresh()->status);

        // ไรเดอร์ได้ 32 + โบนัสร้าน 10 = 42 ครั้งเดียว · ไรเดอร์ว่างรับงานใหม่
        $this->assertEqualsWithDelta(42.0, $this->riderEarningCredits($job), 0.001);
        $this->assertSame('online', $rider->fresh()->availability);

        // ร้าน: 100 − GP 10 − โบนัส 10 = 80 ปล่อยเข้ากระเป๋าทันที (ไม่พัก)
        $ledger = EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->firstOrFail();
        $this->assertSame(EarningsLedger::STATUS_PAID, $ledger->status);
        $this->assertEqualsWithDelta(80.0, $this->walletOf($seller), 0.001);

        // สรุปการแบ่งเงินจากรายการจริง
        $settlement = $done['settlement'];
        $this->assertNotNull($settlement);
        $this->assertEqualsWithDelta(140.0, $settlement['total_paid'], 0.001);
        $this->assertEqualsWithDelta(80.0, $settlement['seller_amount'], 0.001);
        $this->assertEqualsWithDelta(42.0, $settlement['rider_amount'], 0.001);
        $this->assertEqualsWithDelta(18.0, $settlement['platform_amount'], 0.001);
        $this->assertContains('rider', array_column($settlement['lines'], 'key'));

        // กดซ้ำ (ทั้งสองฝ่าย) → ได้ผลเดิม ไม่จ่ายซ้ำ
        $this->buyerScan($buyer, $order, $riderToken)->assertOk()->assertJsonPath('data.handover.status', 'completed');
        $this->riderScan($rider, $job, ['token' => $buyerView['handover']['qr_token']] + self::NEAR)->assertOk();
        app(RiderEarningService::class)->settle($job->fresh());
        $this->assertEqualsWithDelta(42.0, $this->riderEarningCredits($job), 0.001);
        $this->assertSame(1, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());
        $this->assertEqualsWithDelta(80.0, $this->walletOf($seller), 0.001);

        // แจ้งเตือน handover_completed ถึงผู้ซื้อ ไรเดอร์ ร้าน (ครั้งเดียว)
        foreach ([$buyer->id, $rider->user_id, $seller->id] as $userId) {
            $this->assertSame(1, Notification::where('user_id', $userId)->where('type', 'handover_completed')->count());
        }
    }

    public function test_buyer_can_scan_first_then_rider_completes(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'picked_up');

        $riderToken = $this->riderGet($rider, $job)->assertOk()->json('data.handover.qr_token');
        $this->buyerScan($buyer, $order, $riderToken)->assertOk()->assertJsonPath('data.handover.status', 'buyer_confirmed');

        $buyerToken = $this->buyerGet($buyer, $order)->json('data.handover.qr_token');
        $this->riderScan($rider, $job, ['token' => $buyerToken] + self::NEAR)
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'completed');

        $this->assertSame('completed', $job->fresh()->status, 'picked_up → delivered → completed ในคำขอเดียว');
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
    }

    // =====================================================
    // รหัส 6 หลัก + ล็อก
    // =====================================================

    public function test_code_fallback_and_lockout_after_five_wrong_attempts(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $code = $this->buyerGet($buyer, $order)->json('data.handover.code');
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 1; $i <= 4; $i++) {
            $this->riderScan($rider, $job, ['code' => $wrong] + self::NEAR)
                ->assertStatus(422)
                ->assertJsonPath('code', 'HANDOVER_CODE_INVALID')
                ->assertJsonPath('data.attempts_left', 5 - $i);
        }

        // ครั้งที่ 5 → ล็อก 10 นาที
        $this->riderScan($rider, $job, ['code' => $wrong] + self::NEAR)
            ->assertStatus(429)
            ->assertJsonPath('code', 'HANDOVER_CODE_LOCKED');

        // ระหว่างล็อก รหัสถูกก็ใช้ไม่ได้
        $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)
            ->assertStatus(429)
            ->assertJsonPath('code', 'HANDOVER_CODE_LOCKED');
        $this->assertNull($this->handoverOf($job)->rider_confirmed_at);

        // พ้นเวลาล็อก → รหัสถูกใช้ได้ (method = code)
        $this->travel(11)->minutes();
        $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'rider_confirmed')
            ->assertJsonPath('data.handover.method', 'code');

        $handover = $this->handoverOf($job);
        $this->assertSame(0, (int) $handover->code_attempts);
        $this->assertNull($handover->code_locked_until);
        $this->assertNotSame($code, $handover->code_hash, 'เก็บรหัสเป็น hash เท่านั้น');
        $this->assertArrayNotHasKey('code_hash', $handover->toArray());
        $this->assertArrayNotHasKey('secret', $handover->toArray());

        // ผู้ซื้อสแกนต่อ → ปิดงานด้วย method code
        $riderToken = $this->riderGet($rider, $job)->json('data.handover.qr_token');
        $this->buyerScan($buyer, $order, $riderToken)->assertOk()
            ->assertJsonPath('data.handover.status', 'completed')
            ->assertJsonPath('data.handover.method', 'code');
    }

    public function test_rider_scan_requires_token_or_code(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $this->riderScan($rider, $job, self::NEAR)
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    // =====================================================
    // QR: ผิดคน / ผิดฝั่ง / งานอื่น / แก้ไข / หมดอายุ
    // =====================================================

    public function test_tokens_are_bound_to_side_handover_and_user(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $buyerToken = $this->buyerGet($buyer, $order)->json('data.handover.qr_token');
        $riderToken = $this->riderGet($rider, $job)->json('data.handover.qr_token');

        // ผิดฝั่ง: ไรเดอร์ใช้ QR ของตัวเอง / ผู้ซื้อใช้ QR ของตัวเอง
        $this->riderScan($rider, $job, ['token' => $riderToken] + self::NEAR)
            ->assertStatus(422)->assertJsonPath('code', 'HANDOVER_TOKEN_INVALID');
        $this->buyerScan($buyer, $order, $buyerToken)
            ->assertStatus(422)->assertJsonPath('code', 'HANDOVER_TOKEN_INVALID');

        // แก้ลายเซ็น / แก้ฝั่ง
        $this->riderScan($rider, $job, ['token' => substr($buyerToken, 0, -2).'xx'] + self::NEAR)
            ->assertStatus(422)->assertJsonPath('code', 'HANDOVER_TOKEN_INVALID');
        $this->riderScan($rider, $job, ['token' => str_replace('.b.', '.r.', $buyerToken)] + self::NEAR)
            ->assertStatus(422)->assertJsonPath('code', 'HANDOVER_TOKEN_INVALID');

        // QR ของงานอื่น (ไรเดอร์คนเดียวกัน ผู้ซื้ออีกคน) ใช้กับงานนี้ไม่ได้
        [$order2, $buyer2] = $this->makeDeferredShopOrder();
        $rider2 = $this->makeRider();
        $job2 = $this->makeHandoverJob($order2, $rider2);
        $otherBuyerToken = $this->buyerGet($buyer2, $order2)->json('data.handover.qr_token');
        $this->riderScan($rider, $job, ['token' => $otherBuyerToken] + self::NEAR)
            ->assertStatus(422)->assertJsonPath('code', 'HANDOVER_TOKEN_INVALID');

        // ไรเดอร์คนอื่นเอา QR ผู้ซื้อของงานนี้ไปสแกน → ไม่ใช่งานของตัวเอง
        $this->riderScan($rider2, $job, ['token' => $buyerToken] + self::NEAR)
            ->assertStatus(403)->assertJsonPath('code', 'NOT_YOUR_JOB');

        // ผู้ใช้อื่นดู/สแกนการส่งมอบของออเดอร์นี้ไม่ได้
        $this->buyerGet($buyer2, $order)->assertStatus(404)->assertJsonPath('code', 'ORDER_NOT_FOUND');
        $this->buyerScan($buyer2, $order, $riderToken)->assertStatus(404)->assertJsonPath('code', 'ORDER_NOT_FOUND');
        Sanctum::actingAs($buyer2);
        $this->getJson('/api/v1/rider/jobs/'.$job->id.'/handover')->assertStatus(403)->assertJsonPath('code', 'NOT_RIDER');

        $this->assertNull($this->handoverOf($job)->rider_confirmed_at);
        $this->assertNull($this->handoverOf($job)->buyer_confirmed_at);
        $this->assertSame('delivering', $job->fresh()->status);
        $this->assertSame('waiting', $this->handoverOf($job2)->status);
    }

    public function test_previous_window_is_accepted_but_older_tokens_expire(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $token = $this->buyerGet($buyer, $order)->json('data.handover.qr_token');

        // เกิน 2 ช่วงเวลา (TTL 60 วิ) → หมดอายุ
        $this->travel(130)->seconds();
        $this->riderScan($rider, $job, ['token' => $token] + self::NEAR)
            ->assertStatus(422)
            ->assertJsonPath('code', 'HANDOVER_TOKEN_EXPIRED');

        // QR ใหม่ แล้วสแกนช้าไป 1 ช่วง → ยังรับ
        $fresh = $this->buyerGet($buyer, $order)->json('data.handover.qr_token');
        $this->assertNotSame($token, $fresh, 'QR ต้องเปลี่ยนตามช่วงเวลา');
        $this->travel(61)->seconds();
        $this->riderScan($rider, $job, ['token' => $fresh] + self::NEAR)
            ->assertOk()
            ->assertJsonPath('data.handover.rider_confirmed', true);
    }

    // =====================================================
    // รัศมีจุดส่ง / ตำแหน่ง
    // =====================================================

    public function test_rider_must_be_inside_geofence_and_send_location(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $token = $this->buyerGet($buyer, $order)->json('data.handover.qr_token');

        $far = $this->riderScan($rider, $job, ['token' => $token] + self::FAR)
            ->assertStatus(422)
            ->assertJsonPath('code', 'TOO_FAR_FROM_DROPOFF')
            ->json('data');
        $this->assertGreaterThan(1000, $far['distance_m']);
        $this->assertSame(150, $far['geofence_m']);

        $this->riderScan($rider, $job, ['token' => $token])
            ->assertStatus(422)
            ->assertJsonPath('code', 'LOCATION_REQUIRED');

        $this->riderPhoto($rider, $job, 'arrival-photo', self::FAR)
            ->assertStatus(422)
            ->assertJsonPath('code', 'TOO_FAR_FROM_DROPOFF');

        $this->assertNull($this->handoverOf($job)->rider_confirmed_at);
        $this->assertNull($this->handoverOf($job)->arrival_photo_at);
    }

    public function test_scan_before_pickup_is_not_ready(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'accepted');

        $this->buyerGet($buyer, $order)->assertOk()
            ->assertJsonPath('data.handover.qr_token', null)
            ->assertJsonPath('data.handover.code', null);

        // ทำ QR ปลอมจากแถวจริงไม่ได้ผ่านแอป → ต่อให้ได้ token ก็ยังสแกนไม่ได้เพราะไรเดอร์ยังไม่รับของ
        $token = $this->handovers()->tokenFor($this->handoverOf($job), HandoverService::SIDE_BUYER);
        $this->riderScan($rider, $job, ['token' => $token] + self::NEAR)
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_NOT_READY');
    }

    // =====================================================
    // ปุ่มส่งของแบบเดิม + ข้อมูลรายการงาน
    // =====================================================

    public function test_legacy_deliver_is_rejected_and_summary_exposes_handover(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        Sanctum::actingAs($rider->user);
        $this->post('/api/v1/rider/jobs/'.$job->id.'/deliver', [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ] + self::NEAR, ['Accept' => 'application/json'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_REQUIRED');

        // แอปรุ่นเก่าที่ส่ง status=delivered มาที่ /status ก็ต้องโดนปฏิเสธเหมือนกัน
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/status', ['status' => 'delivered'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_REQUIRED');

        $this->assertSame('delivering', $job->fresh()->status);

        $data = $this->getJson('/api/v1/rider/jobs/'.$job->id)->assertOk()->json('data.job');
        $this->assertSame(['handover_scan', 'arrival_photo', 'fail'], $data['allowed_actions']);
        $this->assertNotContains('deliver', $data['allowed_actions']);
        $this->assertSame(true, $data['handover']['required']);
        $this->assertSame('waiting', $data['handover']['status']);
        $this->assertFalse($data['handover']['rider_confirmed']);

        // งานแบบเดิม (ไม่ต้องสแกน) ยังมีปุ่มส่งของ และ handover = null
        $legacy = $this->makeHandoverJob($this->makeDeferredShopOrder()[0], $this->makeRider(), 'delivering', ['handover_required' => false]);
        $summary = $legacy->toApiSummary($legacy->rider);
        $this->assertNull($summary['handover']);
        $this->assertContains('deliver', $summary['allowed_actions']);
    }

    public function test_new_jobs_follow_handover_enabled_setting(): void
    {
        $fields = [
            'job_type' => 'delivery', 'title' => 'ทดสอบ', 'status' => 'pending',
            'pickup_address' => 'ร้าน', 'pickup_latitude' => 13.7291, 'pickup_longitude' => 100.5210,
            'delivery_address' => 'บ้าน', 'delivery_latitude' => self::DROP_LAT, 'delivery_longitude' => self::DROP_LNG,
        ];

        \App\Models\Setting::set('rider.handover_enabled', '1', 'boolean', 'rider');
        $on = new RiderJob;
        $on->forceFill($fields)->save();
        $this->assertTrue((bool) $on->fresh()->handover_required);

        \App\Models\Setting::set('rider.handover_enabled', '0', 'boolean', 'rider');
        $off = new RiderJob;
        $off->forceFill($fields)->save();
        $this->assertFalse((bool) $off->fresh()->handover_required);
    }

    // =====================================================
    // ทางสำรอง: รูป 2 รอบ + เวลารอ + ปลดเงินอัตโนมัติ
    // =====================================================

    public function test_fallback_photos_wait_then_auto_release(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 5);
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        // รูปรอบ 2 ก่อนรอบ 1 ไม่ได้
        $this->riderPhoto($rider, $job, 'waited-photo')->assertStatus(409)->assertJsonPath('code', 'HANDOVER_NOT_READY');

        // รูปรอบ 1 → เริ่มนับรอ + แจ้งผู้ซื้อ
        $arrival = $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk()
            ->assertJsonPath('data.handover.status', 'fallback_waiting')
            ->assertJsonPath('data.handover.can_waited_photo', false)
            ->json('data.handover');
        $this->assertNotNull($arrival['wait_until']);
        $handover = $this->handoverOf($job);
        Storage::disk('local')->assertExists($handover->arrival_photo_path);
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'handover_arrived')->count());

        // กดซ้ำ → ไม่เก็บรูปใหม่ ไม่เลื่อนเวลา ไม่แจ้งซ้ำ
        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk()->assertJsonPath('data.handover.wait_until', $arrival['wait_until']);
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'handover_arrived')->count());

        // ยังไม่ครบเวลารอ
        $this->riderPhoto($rider, $job, 'waited-photo')
            ->assertStatus(409)
            ->assertJsonPath('code', 'WAIT_NOT_OVER')
            ->assertJsonPath('data.wait_until', $arrival['wait_until']);

        // ผู้ซื้อร้องเรียนได้ระหว่างรอ (แต่เทสต์นี้ไม่ร้องเรียน)
        $this->buyerGet($buyer, $order)->assertJsonPath('data.handover.can_dispute', true);

        // ครบเวลารอ → รูปรอบ 2 → งานรอปลดเงิน ไรเดอร์ว่าง
        $this->travel(181)->seconds();
        $this->riderPhoto($rider, $job, 'waited-photo')->assertOk()
            ->assertJsonPath('data.handover.status', 'fallback_pending_release');

        $job->refresh();
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->status);
        $this->assertFalse(RiderJob::isActiveStatus($job->status));
        $this->assertSame('online', $rider->fresh()->availability, 'วางของแล้วไรเดอร์ต้องรับงานใหม่ได้');
        $this->assertFalse($rider->fresh()->hasActiveJob());
        $this->assertSame('shipped', $order->fresh()->status, 'ออเดอร์ยังเป็นจัดส่งแล้วจนปลดเงิน');
        $this->assertTrue($job->isTrackingValid(), 'ลิงก์ติดตามของผู้ซื้อยังใช้ได้');
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());
        $this->assertSame(0.0, $this->riderEarningCredits($job));
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'handover_auto_release_scheduled')->count());

        // รายได้รอปลด
        Sanctum::actingAs($rider->user);
        $this->getJson('/api/v1/rider/earnings')->assertOk()
            ->assertJsonPath('data.pending_release_jobs', 1)
            ->assertJsonPath('data.pending_release_amount', 37);

        // ยังไม่ถึงเวลาปลด
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);

        // ครบ 24 ชม. → ปลดเงิน
        $this->travel(24)->hours();
        $this->travel(1)->minutes();
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->artisan('rider:handover-release')->assertSuccessful(); // รอบซ้ำไม่ทำอะไร

        $handover = $this->handoverOf($job);
        $this->assertSame(DeliveryHandover::STATUS_RELEASED, $handover->status);
        $this->assertSame('fallback', $handover->method);
        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertEqualsWithDelta(37.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(85.0, $this->walletOf($seller), 0.001, '100 − GP 10 − โบนัส 5');

        $this->buyerGet($buyer, $order)->assertOk()
            ->assertJsonPath('data.handover.status', 'released')
            ->assertJsonPath('data.settlement.rider_amount', 37);
    }

    // =====================================================
    // ร้องเรียน → แอดมินตัดสิน
    // =====================================================

    public function test_dispute_blocks_auto_release_and_admin_refund_returns_everything(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        // ยังไม่มีเหตุให้ร้องเรียน
        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'not_received'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'DISPUTE_NOT_ALLOWED');

        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk();
        $this->travel(181)->seconds();
        $this->riderPhoto($rider, $job, 'waited-photo')->assertOk();

        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'other'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'not_received', 'note' => 'ไม่มีของหน้าบ้าน'])
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'disputed')
            ->assertJsonPath('data.handover.can_dispute', false)
            ->assertJsonPath('data.handover.dispute_reason', 'not_received');
        $this->assertSame(1, Notification::where('user_id', $admin->id)->where('type', 'handover_disputed')->count());

        // ร้องเรียนแล้ว → ครบเวลาก็ไม่ปลดเงิน
        $this->travel(25)->hours();
        $this->artisan('rider:handover-release')->assertSuccessful();
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);

        // หน้าแอดมินแสดงแผงส่งมอบ + ปุ่มตัดสิน + รูปทั้ง 2 รอบ (เปิดได้เฉพาะแอดมิน)
        $this->actingAs($admin)->get(route('admin.rider-jobs.show', $job))
            ->assertOk()
            ->assertSee('การส่งมอบของ')
            ->assertSee('คืนเงินผู้ซื้อ')
            ->assertSee(route('admin.rider-jobs.handover.photo', [$job, 'waited']), false);
        $this->actingAs($admin)->get(route('admin.rider-jobs.handover.photo', [$job, 'arrival']))->assertOk();
        $this->assertNotSame(200, $this->actingAs($buyer)->get(route('admin.rider-jobs.handover.photo', [$job, 'arrival']))->status());

        // แอดมินคืนเงิน: ต้องมีเหตุผล
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job), [])
            ->assertStatus(422);
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.refund', $job), ['reason' => 'ตรวจกล้องแล้วไม่พบของ'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $job->refresh();
        $this->assertSame('failed', $job->status);
        $this->assertSame('admin_intervention', $job->failure_reason);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001, 'คืนเต็มจำนวนรวมค่าส่ง');
        $this->assertSame(0.0, $this->riderEarningCredits($job), 'ไรเดอร์ไม่ได้ค่าส่ง');
        $this->assertEqualsWithDelta(0.0, $this->walletOf($seller), 0.001);
        $this->assertSame(0, EarningsLedger::where('source_type', 'Order')->where('source_id', $order->id)->count());

        $handover = $this->handoverOf($job);
        $this->assertSame(DeliveryHandover::STATUS_REFUNDED, $handover->status);
        $this->assertSame('refund', $handover->resolution);
        $this->assertSame((int) $admin->id, (int) $handover->resolved_by);
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'handover_resolved')->count());
        $this->assertSame(1, Notification::where('user_id', $rider->user_id)->where('type', 'handover_resolved')->count());

        // ตัดสินซ้ำไม่ได้
        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.release', $job), [])
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_FINAL');
        $this->assertEqualsWithDelta(140.0, $this->walletOf($buyer), 0.001);
    }

    public function test_dispute_after_rider_only_confirmed_then_admin_release_pays_normally(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $admin = User::factory()->create(['role' => 'admin']);

        $code = $this->buyerGet($buyer, $order)->json('data.handover.code');
        $this->riderScan($rider, $job, ['code' => $code] + self::NEAR)->assertOk();

        // ไรเดอร์ยืนยันแล้วแต่ผู้ซื้อยังไม่ยืนยัน → ผู้ซื้อร้องเรียนได้ (กดซ้ำได้ผลเดิม)
        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'wrong_item'])->assertOk();
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'wrong_item'])
            ->assertOk()
            ->assertJsonPath('data.handover.status', 'disputed');
        $this->assertSame(1, Notification::where('user_id', $admin->id)->where('type', 'handover_disputed')->count());

        // ร้องเรียนแล้วสแกนต่อไม่ได้
        $riderToken = $this->handovers()->tokenFor($this->handoverOf($job), HandoverService::SIDE_RIDER);
        $this->buyerScan($buyer, $order, $riderToken)->assertStatus(409)->assertJsonPath('code', 'HANDOVER_NOT_READY');

        $this->actingAs($admin)->postJson(route('admin.rider-jobs.handover.release', $job), ['reason' => 'ร้านยืนยันของถูกต้อง'])
            ->assertOk();

        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame(DeliveryHandover::STATUS_RELEASED, $this->handoverOf($job)->status);
        $this->assertSame('admin', $this->handoverOf($job)->method);
        $this->assertEqualsWithDelta(32.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(90.0, $this->walletOf($seller), 0.001);
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'handover_resolved')->count());
    }

    // =====================================================
    // กันซ้ำ / แข่งกัน (เรียก service ตรงซ้อนกัน)
    // =====================================================

    public function test_repeated_completion_paths_never_pay_twice(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder(bonus: 10);
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);

        $this->completeByScans($buyer, $order, $rider, $job);

        // หลังปิดแล้ว: รูปทางสำรอง / ร้องเรียน / ปลดเงิน / แอดมิน → ไม่เปลี่ยนอะไร
        $this->riderPhoto($rider, $job, 'arrival-photo')->assertStatus(409)->assertJsonPath('code', 'HANDOVER_FINAL');
        Sanctum::actingAs($buyer);
        $this->postJson('/api/v1/orders/shop/'.$order->id.'/handover/dispute', ['reason' => 'damaged'])
            ->assertStatus(409)->assertJsonPath('code', 'DISPUTE_NOT_ALLOWED');
        $this->assertSame(0, $this->handovers()->releaseDue());

        app(RiderEarningService::class)->settle($job->fresh());
        app(\App\Services\SellerPayoutService::class)->releaseEligible();
        app(\App\Services\OrderDistributionService::class)->processPendingOrders();

        $this->assertEqualsWithDelta(42.0, $this->riderEarningCredits($job), 0.001);
        $this->assertEqualsWithDelta(80.0, $this->walletOf($seller), 0.001);
        $this->assertSame(1, WalletTransaction::where('reference_type', 'EarningsLedger')->where('user_id', $seller->id)->count());
        $this->assertSame(1, Notification::where('user_id', $buyer->id)->where('type', 'handover_completed')->count());
    }

    public function test_cod_job_cannot_use_drop_off_fallback(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider, 'delivering', ['cod_amount' => 140]);

        $this->riderGet($rider, $job)->assertOk()->assertJsonPath('data.handover.can_arrival_photo', false);
        $this->riderPhoto($rider, $job, 'arrival-photo')
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_NOT_READY');
    }

    public function test_non_handover_order_returns_not_required(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $this->makeHandoverJob($order, $rider, 'delivering', ['handover_required' => false]);

        $this->buyerGet($buyer, $order)->assertOk()
            ->assertJsonPath('data.handover.required', false)
            ->assertJsonPath('data.handover.status', 'not_required');

        [$noJobOrder, $buyer2] = $this->makeDeferredShopOrder();
        $this->buyerGet($buyer2, $noJobOrder)->assertStatus(409)->assertJsonPath('code', 'HANDOVER_NOT_READY');
        $this->buyerGet($buyer2, $noJobOrder, 'nope')->assertStatus(404);
    }
}
