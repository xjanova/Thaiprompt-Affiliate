<?php

namespace Tests\Feature\RiderR2;

use App\Exceptions\RiderJobException;
use App\Http\Middleware\EnsureEkycVerified;
use App\Models\DeliveryHandover;
use App\Models\FreshMarketSetting;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use App\Services\FreshMarketChannelManager;
use App\Services\RiderJobService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * 🛵 ไรเดอร์รับงานผ่านแอปเท่านั้น (owner ตัดสิน 2026-10-13) — ต้องใช้ MySQL
 *
 * "ไรเดอร์รับงานผ่านแอปเท่านั้น ในเว็บรับไม่ได้ แต่สมัครและใช้โปรไฟล์ได้"
 *   - เว็บ: รับงาน/ทำงานส่ง/เปิดรับงาน = APP_ONLY · ปิดรับงานได้
 *   - LINE: postback รับงาน = reply ให้ไปใช้แอป (ไม่ push · ไม่แตะงาน)
 *   - API: เส้นรับงาน/ทำงานส่ง/สแกนส่งมอบ/เปิดรับงาน ต้อง X-App-Build ≥ rider.min_app_build ไม่งั้น 426
 *   - eKYC ฝั่งเซิร์ฟเวอร์: ยังไม่ยืนยันตัวตน = 422 KYC_REQUIRED ทุกช่องทาง (ไม่ขึ้นกับ header)
 *   - งานที่มีออเดอร์ต้นทาง + เปิด rider.handover_enabled = ต้องสแกนส่งมอบเสมอ (ไม่ดู build แอปของผู้ซื้อ)
 *     ผู้ซื้อเว็บ/LINE (ไม่มีแอป) ปิดงานได้ด้วยรูป 2 รอบ + ปลดเงินอัตโนมัติ โดยผู้ซื้อไม่ต้องทำอะไร
 */
class RiderAppOnlyTest extends HandoverTestCase
{
    private const APP_ONLY_MESSAGE = 'รับงานและทำงานส่งได้ผ่านแอป Thai Prompt APP เท่านั้น';

    // =====================================================
    // เว็บ
    // =====================================================

    public function test_web_accept_is_refused_but_going_offline_still_works(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $job = $this->makePendingJob($order);
        $rider = $this->makeOnlineRider();

        // AJAX → JSON 403 APP_ONLY (แนบ X-App-Build มาก็ไม่ช่วย — เว็บไม่ใช่แอป)
        $this->actingAs($rider->user)
            ->postJson(route('user.rider.jobs.accept', $job), [], ['X-App-Build' => '44'])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'APP_ONLY')
            ->assertJsonPath('message', self::APP_ONLY_MESSAGE)
            ->assertJsonPath('data.download_url', url('/app/download'));

        // ฟอร์มปกติ → redirect กลับพร้อม flash error
        $this->actingAs($rider->user)
            ->from(route('user.rider.jobs.show', $job))
            ->post(route('user.rider.jobs.accept', $job))
            ->assertRedirect(route('user.rider.jobs.show', $job))
            ->assertSessionHas('error', self::APP_ONLY_MESSAGE);

        $fresh = $job->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertNull($fresh->rider_id);

        // เปิดรับงานบนเว็บไม่ได้ · ปิดรับงานได้เสมอ
        $this->actingAs($rider->user)
            ->postJson(route('user.rider.availability'), ['availability' => 'online'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'APP_ONLY');
        $this->assertSame('online', $rider->fresh()->availability, 'คำขอเปิดรับงานที่ถูกปฏิเสธต้องไม่เปลี่ยนสถานะ');

        $this->actingAs($rider->user)
            ->postJson(route('user.rider.availability'), ['availability' => 'offline'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.availability', 'offline');
        $this->assertSame('offline', $rider->fresh()->availability);

        $this->actingAs($rider->user)
            ->postJson(route('user.rider.availability'), ['availability' => 'online'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'APP_ONLY');
        $this->assertSame('offline', $rider->fresh()->availability);

        // หน้าเว็บที่อ่านอย่างเดียวยังเปิดได้ + มีแบนเนอร์ดาวน์โหลดแอป
        $this->actingAs($rider->user)->get(route('user.rider.jobs.show', $job))
            ->assertOk()
            ->assertSee(self::APP_ONLY_MESSAGE)
            ->assertSee(url('/app/download'), false);
    }

    // =====================================================
    // LINE
    // =====================================================

    public function test_line_accept_postback_is_refused_with_a_reply_and_never_a_push(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $job = $this->makePendingJob($order);
        $rider = $this->makeOnlineRider();
        $rider->forceFill(['line_user_id' => 'U-app-only-'.$rider->id])->save();

        $settings = (new FreshMarketSetting)->forceFill(['line_channel_access_token' => 'test-line-token']);
        $manager = new FreshMarketChannelManager($settings);

        foreach (['rider_accept_job', 'rider_reject_job'] as $action) {
            $manager->handlePostback($rider->line_user_id, 'action='.$action.'&job_id='.$job->id, ['reply_token' => 'reply-'.$action]);
        }

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/reply')
            && $r['replyToken'] === 'reply-rider_accept_job'
            && str_contains((string) ($r['messages'][0]['text'] ?? ''), self::APP_ONLY_MESSAGE)
            && str_contains((string) ($r['messages'][0]['text'] ?? ''), '/app/download'));
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/reply') && $r['replyToken'] === 'reply-rider_reject_job');
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), '/message/push'));

        $fresh = $job->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertNull($fresh->rider_id);
        $this->assertSame('online', $rider->fresh()->availability);
    }

    // =====================================================
    // API: ต้องเป็นแอปรุ่นปัจจุบัน
    // =====================================================

    public function test_api_job_routes_require_current_app_build(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $pending = $this->makePendingJob($order);
        $rider = $this->makeOnlineRider();
        Sanctum::actingAs($rider->user);

        $this->assertSame(44, (int) config('rider.min_app_build'), 'แอปปัจจุบันส่ง X-App-Build: 44 คงที่ ต้องผ่าน');

        // ไม่ส่ง header / แอปเก่า / ค่าแปลก → 426
        $this->flushHeaders();
        foreach ([[], ['X-App-Build' => '43'], ['X-App-Build' => '44abc'], ['X-App-Build' => '0']] as $headers) {
            $this->postJson('/api/v1/rider/jobs/'.$pending->id.'/accept', [], $headers)
                ->assertStatus(426)
                ->assertJsonPath('success', false)
                ->assertJsonPath('code', 'APP_UPDATE_REQUIRED')
                ->assertJsonPath('message', 'กรุณาอัปเดตแอป Thai Prompt APP เป็นรุ่นล่าสุดก่อนรับงาน')
                ->assertJsonPath('data.min_build', 44);
        }
        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertNull($pending->fresh()->rider_id);

        // งานที่กำลังส่ง: ทุกปุ่มงาน + ปุ่มส่งมอบ ต้องมาจากแอปรุ่นปัจจุบัน
        $active = $this->makeHandoverJob($this->makeDeferredShopOrder()[0], $this->makeRider(), 'delivering');
        Sanctum::actingAs($active->rider->user);
        foreach ([
            "/api/v1/rider/jobs/{$active->id}/reject" => [],
            "/api/v1/rider/jobs/{$active->id}/release" => ['reason' => 'x'],
            "/api/v1/rider/jobs/{$active->id}/status" => ['status' => 'delivered'],
            "/api/v1/rider/jobs/{$active->id}/deliver" => [],
            "/api/v1/rider/jobs/{$active->id}/fail" => ['reason_code' => 'customer_unreachable'],
            "/api/v1/rider/jobs/{$active->id}/gps-lost" => [],
            "/api/v1/rider/jobs/{$active->id}/gps-off" => [],
            "/api/v1/rider/jobs/{$active->id}/handover/scan" => ['code' => '123456'] + self::NEAR,
            "/api/v1/rider/jobs/{$active->id}/handover/arrival-photo" => self::NEAR,
            "/api/v1/rider/jobs/{$active->id}/handover/waited-photo" => self::NEAR,
        ] as $uri => $payload) {
            $this->postJson($uri, $payload)->assertStatus(426)->assertJsonPath('code', 'APP_UPDATE_REQUIRED');
        }
        $this->assertSame('delivering', $active->fresh()->status);

        // อ่านอย่างเดียวไม่โดนด่าน
        $this->getJson("/api/v1/rider/jobs/{$active->id}")->assertOk();
        $this->getJson("/api/v1/rider/jobs/{$active->id}/handover")->assertOk();

        // เปิดรับงานต้องเป็นแอปรุ่นปัจจุบัน · ปิดรับงานได้เสมอแม้ไม่ส่ง header
        Sanctum::actingAs($rider->user);
        $this->postJson('/api/v1/rider/availability', ['availability' => 'online', 'latitude' => self::NEAR['latitude'], 'longitude' => self::NEAR['longitude']])
            ->assertStatus(426)
            ->assertJsonPath('code', 'APP_UPDATE_REQUIRED');
        $this->postJson('/api/v1/rider/availability', ['availability' => 'offline'])
            ->assertOk()
            ->assertJsonPath('data.availability', 'offline');
        $this->assertSame('offline', $rider->fresh()->availability);

        // แอปรุ่นปัจจุบัน (44) ผ่าน
        $this->postJson('/api/v1/rider/availability', ['availability' => 'online', 'latitude' => self::NEAR['latitude'], 'longitude' => self::NEAR['longitude']], ['X-App-Build' => '44'])
            ->assertOk()
            ->assertJsonPath('data.availability', 'online');
        $this->postJson('/api/v1/rider/jobs/'.$pending->id.'/accept', [], ['X-App-Build' => '44'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'accepted');
    }

    // =====================================================
    // eKYC ฝั่งเซิร์ฟเวอร์
    // =====================================================

    public function test_api_accept_by_rider_without_ekyc_returns_kyc_required(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $job = $this->makePendingJob($order);
        $rider = $this->makeOnlineRider();
        $rider->user->forceFill(['kyc_status' => 'pending', 'kyc_verified_at' => null])->save();
        Sanctum::actingAs($rider->user);

        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/accept', [], ['X-App-Build' => '44'])
            ->assertStatus(422)
            ->assertExactJson([
                'success' => false,
                'code' => 'KYC_REQUIRED',
                'message' => EnsureEkycVerified::MESSAGE,
                'data' => ['kyc_status' => 'pending'],
            ]);
        $this->assertSame('pending', $job->fresh()->status);
        $this->assertNull($job->fresh()->rider_id);

        // ด่านอยู่ในชั้นโดเมน → ทุกช่องทาง (ไม่ขึ้นกับ header) เปิดรับงาน/รับงานไม่ได้
        try {
            app(RiderJobService::class)->accept($job->fresh(), $rider->fresh());
            $this->fail('ต้องโยน KYC_REQUIRED');
        } catch (RiderJobException $e) {
            $this->assertSame('KYC_REQUIRED', $e->errorCode);
            $this->assertSame(422, $e->httpStatus);
        }

        $rider->forceFill(['availability' => 'offline'])->save();
        try {
            $rider->fresh()->setAvailability('online');
            $this->fail('ต้องโยน KYC_REQUIRED');
        } catch (RiderJobException $e) {
            $this->assertSame('KYC_REQUIRED', $e->errorCode);
        }
        $this->assertSame('KYC_REQUIRED', $rider->fresh()->onlineBlockReason()['code'] ?? null);

        // ปิดการบังคับ eKYC → ด่านนี้ไม่ทำงาน
        config(['ekyc.enforce' => false]);
        $this->assertNull($rider->fresh()->onlineBlockReason());
        config(['ekyc.enforce' => true]);

        // ยืนยันตัวตนแล้ว → รับงานได้
        $rider->user->forceFill(['kyc_status' => 'approved', 'kyc_verified_at' => now()])->save();
        $rider->forceFill(['availability' => 'online'])->save();
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/accept', [], ['X-App-Build' => '44'])->assertOk();
    }

    public function test_web_rider_registration_does_not_need_ekyc(): void
    {
        $user = User::factory()->create(['role' => 'user', 'kyc_status' => 'not_submitted']);

        $this->actingAs($user)
            ->post(route('user.rider.register.submit'), [
                'full_name' => 'สมหญิง ขยันส่ง',
                'phone' => '0898765432',
                'id_card_number' => '3100200345676',
                'birth_date' => now()->subYears(30)->toDateString(),
                'address' => '12/3 ถ.เจริญกรุง เขตบางรัก กรุงเทพมหานคร 10500',
                'vehicle_type' => 'motorcycle',
                'vehicle_plate' => '2กค 5678',
                'pdpa_consent' => '1',
            ])
            ->assertRedirect(route('user.rider.documents'));

        $this->assertSame('pending', Rider::where('user_id', $user->id)->value('status'));
    }

    // =====================================================
    // ต้องสแกนส่งมอบเสมอ + ทางสำรองของผู้ซื้อที่ไม่มีแอป
    // =====================================================

    public function test_ekyc_rider_accept_requires_handover_even_when_order_has_no_app_build(): void
    {
        [$order] = $this->makeDeferredShopOrder();
        $this->assertNull($order->fresh()->client_app_build, 'ออเดอร์จากเว็บ/LINE ไม่มี build แอป');
        $job = $this->makePendingJob($order);
        $rider = $this->makeOnlineRider();
        Sanctum::actingAs($rider->user);

        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/accept', [], ['X-App-Build' => '44'])
            ->assertOk()
            ->assertJsonPath('data.job.handover.required', true)
            ->assertJsonPath('data.job.handover.status', 'waiting');

        $this->assertTrue((bool) $job->fresh()->handover_required);
        $this->assertTrue($job->fresh()->handoverRequiredOnAccept());

        // ปิดระบบสแกนส่งมอบ (แอดมิน) → งานที่รับใหม่เป็นแบบเดิม
        \App\Models\Setting::set('rider.handover_enabled', '0', 'boolean', 'rider');
        $legacy = $this->makePendingJob($this->makeDeferredShopOrder()[0]);
        $other = $this->makeOnlineRider();
        Sanctum::actingAs($other->user);
        $this->postJson('/api/v1/rider/jobs/'.$legacy->id.'/accept', [], ['X-App-Build' => '44'])->assertOk();
        $this->assertFalse((bool) $legacy->fresh()->handover_required);
    }

    public function test_legacy_deliver_is_refused_and_web_buyer_order_releases_by_fallback_without_buyer_action(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $this->assertNull($order->fresh()->client_app_build);
        $job = $this->makePendingJob($order);
        $rider = $this->makeOnlineRider();
        Sanctum::actingAs($rider->user);

        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/accept', [], ['X-App-Build' => '44'])->assertOk();
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/status', ['status' => 'picked_up'])->assertOk();
        $this->postJson('/api/v1/rider/jobs/'.$job->id.'/status', ['status' => 'delivering'])->assertOk();

        // ปุ่มส่งของแบบเดิม (รูปอะไรก็ได้ จ่ายทันที) ใช้ไม่ได้ทั้ง API และ service ตรง
        $this->post('/api/v1/rider/jobs/'.$job->id.'/deliver', ['photo' => UploadedFile::fake()->image('p.jpg')] + self::NEAR, ['Accept' => 'application/json'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'HANDOVER_REQUIRED');
        try {
            app(RiderJobService::class)->deliver($job->fresh(), $rider->fresh(), UploadedFile::fake()->image('p.jpg'), self::NEAR['latitude'], self::NEAR['longitude'], false);
            $this->fail('ต้องโยน HANDOVER_REQUIRED');
        } catch (RiderJobException $e) {
            $this->assertSame(RiderJobException::HANDOVER_REQUIRED, $e->errorCode);
        }
        $this->assertSame('delivering', $job->fresh()->status);
        $this->assertNull($job->fresh()->earnings_settled_at);

        // ผู้ซื้อไม่มีแอป: ไรเดอร์ถ่ายรูปถึงจุดส่ง → รอครบเวลา → รูปรอบ 2 → ระบบปลดเงินเองหลัง 24 ชม.
        $this->riderPhoto($rider, $job, 'arrival-photo')->assertOk()->assertJsonPath('data.handover.status', 'fallback_waiting');
        $this->travel(181)->seconds();
        $this->riderPhoto($rider, $job, 'waited-photo')->assertOk()->assertJsonPath('data.handover.status', 'fallback_pending_release');
        $this->assertSame(RiderJob::STATUS_AWAITING_RELEASE, $job->fresh()->status);
        $this->assertSame(0.0, $this->riderEarningCredits($job));

        $this->travel(24)->hours();
        $this->travel(1)->minutes();
        $this->artisan('rider:handover-release')->assertSuccessful();

        $this->assertSame(DeliveryHandover::STATUS_RELEASED, $this->handoverOf($job)->status);
        $this->assertSame('completed', $job->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertEqualsWithDelta((float) $job->fresh()->rider_earnings, $this->riderEarningCredits($job), 0.001);
        $this->assertNull($this->handoverOf($job)->buyer_confirmed_at, 'ผู้ซื้อไม่ได้ทำอะไรเลย');
    }

    // =====================================================
    // แอดมินมอบหมายงาน
    // =====================================================

    public function test_admin_reassign_keeps_handover_required(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $service = app(RiderJobService::class);

        // รับไปแล้ว (ต้องสแกน) → ย้ายไรเดอร์ก่อนรับของ → ยังต้องสแกน (เดิมถูกตั้งเป็น false = ช่องส่งของแบบเดิม)
        foreach (['accepted', 'picking_up', 'delivering'] as $status) {
            $job = $this->makeHandoverJob($this->makeDeferredShopOrder()[0], $this->makeRider(), $status);
            $this->assertTrue((bool) $job->handover_required);

            $moved = $service->adminReassign($job, $this->makeRider(), $admin);
            $this->assertTrue((bool) $moved->fresh()->handover_required, $status);
        }

        // งานที่ยังรอไรเดอร์ → แอดมินมอบหมาย = ตัดสินแบบเดียวกับไรเดอร์กดรับเอง
        $pending = $this->makePendingJob($this->makeDeferredShopOrder()[0]);
        $this->assertFalse((bool) $pending->handover_required);
        $assigned = $service->adminReassign($pending, $this->makeRider(), $admin);
        $this->assertSame('accepted', $assigned->fresh()->status);
        $this->assertTrue((bool) $assigned->fresh()->handover_required);

        // งานแบบเดิมที่รับไว้ก่อนเปลี่ยนกติกา → คงค่าเดิม (ไม่เปลี่ยนกลางทาง)
        $legacy = $this->makeHandoverJob($this->makeDeferredShopOrder()[0], $this->makeRider(), 'accepted', ['handover_required' => false]);
        $this->assertFalse((bool) $service->adminReassign($legacy, $this->makeRider(), $admin)->fresh()->handover_required);

        // ไรเดอร์ที่ยังไม่ยืนยันตัวตนรับมอบหมายไม่ได้
        $unverified = $this->makeRider();
        $unverified->user->forceFill(['kyc_status' => 'pending'])->save();
        $another = $this->makePendingJob($this->makeDeferredShopOrder()[0]);
        try {
            $service->adminReassign($another, $unverified->fresh(), $admin);
            $this->fail('ต้องโยน NOT_ELIGIBLE');
        } catch (RiderJobException $e) {
            $this->assertSame('KYC_REQUIRED', $e->context['block_code'] ?? null);
        }
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * ไรเดอร์พร้อมรับงาน (อนุมัติ + ยืนยันตัวตน + online + พิกัดสด + ยินยอมแชร์ตำแหน่ง)
     */
    private function makeOnlineRider(): Rider
    {
        $rider = $this->makeRider();
        $rider->forceFill(['availability' => 'online', 'last_location_update' => now()])->save();

        return $rider->fresh();
    }

    /**
     * งานรอไรเดอร์ของออเดอร์ (ยังไม่ระบุ handover_required = ค่าเริ่มต้นตอนสร้าง)
     */
    private function makePendingJob(Order $order): RiderJob
    {
        Order::whereKey($order->id)->update(['status' => 'processing']);

        $job = new RiderJob;
        $job->forceFill([
            'job_type' => 'shop_delivery',
            'source_type' => $order->getMorphClass(),
            'source_id' => $order->id,
            'customer_id' => $order->user_id,
            'title' => 'ส่งสินค้า '.$order->order_number,
            'pickup_address' => 'ร้านทดสอบ สีลม',
            'pickup_latitude' => 13.7291,
            'pickup_longitude' => 100.5210,
            'delivery_address' => 'บ้านลูกค้า บางรัก',
            'delivery_latitude' => self::DROP_LAT,
            'delivery_longitude' => self::DROP_LNG,
            'base_fee' => 30,
            'distance_fee' => 10,
            'total_fee' => 40,
            'rider_earnings' => 32,
            'platform_fee' => 8,
            'cod_amount' => 0,
            'status' => 'pending',
            'dispatch_type' => 'broadcast',
            'tracking_token' => Str::random(48),
        ])->save();

        return $job->fresh();
    }
}
