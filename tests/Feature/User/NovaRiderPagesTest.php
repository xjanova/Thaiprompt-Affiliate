<?php

namespace Tests\Feature\User;

use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Rider\Fixtures\FakeDeliverableSource;
use Tests\TestCase;

/**
 * หน้าไรเดอร์ของสมาชิกธีมโนวาเต็ม (/user/rider/*)
 *
 * ทุกหน้าต้องอยู่ในเปลือก layouts.user-nova (ไม่ใช่โหมดสะพาน V4) · ดูงานของไรเดอร์คนอื่นไม่ได้ ·
 * งานที่ยังเปิดรับไม่เผยเบอร์ลูกค้า · หน้าตั้งค่าแสดงเลขบัตรแบบปิดบัง
 */
class NovaRiderPagesTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = 13.7291;

    private const PICKUP_LNG = 100.5210;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
        Storage::fake('public');
        Storage::fake('local');
        Cache::flush();
        FakeDeliverableSource::reset();

        Setting::set('rider.handover_enabled', '0', 'boolean', 'rider');
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');
    }

    /**
     * หน้าต้องอยู่ในเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4 และโหลด CSS ของชุดไรเดอร์
     */
    private function assertFullNova(TestResponse $response): string
    {
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('theme-nova/member/rider.css', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, 'ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, 'ยังมีการ์ด V4 หลงอยู่');
        $this->assertStringNotContainsString('rd-scope', $html, 'ยังมีสไตล์ไรเดอร์ V4 หลงอยู่');

        return $html;
    }

    public function test_member_without_rider_sees_nova_register_form(): void
    {
        $user = User::factory()->create(['role' => 'user', 'kyc_status' => 'approved']);

        $html = $this->assertFullNova($this->actingAs($user)->get(route('user.rider.register')));

        $this->assertStringContainsString('name="pdpa_consent"', $html);
        $this->assertStringContainsString('name="id_card_number"', $html);
        $this->assertStringContainsString('name="vehicle_type"', $html);
        $this->assertStringContainsString(route('user.rider.register.submit'), $html);
    }

    public function test_pending_rider_pages_use_full_nova(): void
    {
        $rider = $this->makeRider(['status' => 'pending', 'availability' => 'offline', 'approved_at' => null, 'share_location_consent_at' => null]);
        $user = $rider->user;

        $this->assertStringContainsString('รอตรวจสอบ', $this->assertFullNova($this->actingAs($user)->get(route('user.rider.status'))));
        $this->assertStringContainsString('แก้ไขใบสมัคร', $this->assertFullNova($this->actingAs($user)->get(route('user.rider.register'))));
        $this->assertStringContainsString(route('user.rider.settings.update'), $this->assertFullNova($this->actingAs($user)->get(route('user.rider.settings'))));

        $docs = $this->assertFullNova($this->actingAs($user)->get(route('user.rider.documents')));
        $this->assertStringContainsString('name="document_type"', $docs);
        $this->assertStringContainsString(route('user.rider.documents.upload'), $docs);
        // ยืนยันก่อนอัปโหลดทับด้วยหน้าต่างโนวา ไม่ใช้ window.confirm
        $this->assertStringContainsString('nvm-modal', $docs);
        $this->assertStringContainsString('confirmUpload()', $docs);
        $this->assertStringNotContainsString('window.confirm', $docs);
    }

    public function test_approved_rider_pages_use_full_nova(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        $user = $rider->user;

        $dash = $this->assertFullNova($this->actingAs($user)->get(route('user.rider.dashboard')));
        $this->assertStringContainsString('รายได้วันนี้', $dash);
        $this->assertStringContainsString((string) $job->job_number, $dash);
        $this->assertStringContainsString('แตะเพื่อปิดรับงาน', $dash);

        foreach (['available', 'current', 'history'] as $tab) {
            $this->assertFullNova($this->actingAs($user)->get(route('user.rider.jobs', ['tab' => $tab])));
        }
        foreach (['completed', 'cancelled', 'failed'] as $status) {
            $this->assertFullNova($this->actingAs($user)->get(route('user.rider.jobs', ['tab' => 'history', 'status' => $status])));
        }
        foreach (['today', 'week', 'month', 'all'] as $period) {
            $this->assertStringContainsString('รายได้ของคุณ', $this->assertFullNova($this->actingAs($user)->get(route('user.rider.earnings', ['period' => $period]))));
        }

        $this->assertFullNova($this->actingAs($user)->get(route('user.rider.documents')));

        $settings = $this->assertFullNova($this->actingAs($user)->get(route('user.rider.settings')));
        $this->assertStringContainsString('name="preferred_job_types[]"', $settings);
        $this->assertStringContainsString('ยินยอมแล้ว', $settings);
        // ถอนความยินยอมผ่านหน้าต่างยืนยันของโนวา
        $this->assertStringContainsString('withdrawForm', $settings);

        $detail = $this->assertFullNova($this->actingAs($user)->get(route('user.rider.jobs.show', $job)));
        $this->assertStringContainsString('ดาวน์โหลดแอป', $detail);
        $this->assertStringNotContainsString(route('user.rider.jobs.accept', $job), $detail);
    }

    public function test_job_detail_regression_through_delivery(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        $service = app(RiderJobService::class);

        $service->accept($job, $rider->fresh());

        // งานของตัวเองที่กำลังส่ง: เห็นเบอร์ลูกค้าได้
        $active = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.jobs.show', $job)));
        $this->assertStringContainsString('0822222222', $active);

        $dash = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.dashboard')));
        $this->assertStringContainsString('งานที่กำลังทำ', $dash);
        $this->assertStringContainsString('อัปเดตสถานะงานในแอป', $dash);
    }

    public function test_completed_cod_job_shows_celebration_and_cod_summary(): void
    {
        $rider = $this->makeRider();
        app(WalletService::class)->getOrCreateWallet($rider->user)->forceFill(['balance' => 1000])->save();
        $job = $this->createJob(200);
        $service = app(RiderJobService::class);

        $service->accept($job, $rider->fresh());
        $service->markPickedUp($job->fresh(), $rider->fresh(), null);
        $done = $service->deliver($job->fresh(), $rider->fresh(), UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg'), 13.74, 100.53, true);
        $this->assertSame('completed', $done->status);

        $html = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.jobs.show', $job)));
        $this->assertStringContainsString('ส่งสำเร็จ!', $html);
        $this->assertStringContainsString('เก็บเงินปลายทาง', $html);
        $this->assertStringContainsString('nvm-pill nvm-pill--ok', $html);
        // ปุ่มในการ์ดฉลอง: รายได้วันนี้ + ประวัติงาน
        $this->assertStringContainsString(e(route('user.rider.earnings', ['period' => 'today'])), $html);
        $this->assertStringContainsString(e(route('user.rider.jobs', ['tab' => 'history'])), $html);
        $this->assertStringContainsString('ประวัติงาน', $html);
        $this->assertStringContainsString('รูปยืนยัน', $html);
    }

    public function test_cancelled_job_shows_cancel_alert_with_neutral_pill(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        $service = app(RiderJobService::class);

        $service->accept($job, $rider->fresh());
        $service->cancel($job->fresh(), 'admin', 'ร้านปิดกะทันหัน');

        $html = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.jobs.show', $job)));
        $this->assertStringContainsString('งานถูกยกเลิก', $html);
        $this->assertStringContainsString('ร้านปิดกะทันหัน', $html);
        // งานยกเลิกต้องไม่ใช้ป้ายสีทองเหมือนงานที่กำลังส่ง
        $this->assertStringContainsString('nvm-pill nvm-pill--glass', $html);
        $this->assertStringNotContainsString('nvm-pill nvm-pill--gold', $html);
    }

    public function test_failed_job_shows_failure_alert(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        $service = app(RiderJobService::class);

        $service->accept($job, $rider->fresh());
        $service->markPickedUp($job->fresh(), $rider->fresh(), null);
        $service->fail($job->fresh(), $rider->fresh(), 'customer_refused', 'ลูกค้าไม่รับของ');

        $html = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.jobs.show', $job)));
        $this->assertStringContainsString('ส่งไม่สำเร็จ:', $html);
        $this->assertStringContainsString('ลูกค้าไม่รับของ', $html);
        $this->assertStringContainsString('nvm-pill nvm-pill--bad', $html);
    }

    public function test_history_and_earnings_paginate_with_nova_pager(): void
    {
        $rider = $this->makeRider();

        for ($i = 0; $i < 16; $i++) {
            $job = $this->createJob();
            RiderJob::whereKey($job->id)->update([
                'rider_id' => $rider->id,
                'status' => 'completed',
                'accepted_at' => now()->subHour(),
                'completed_at' => now()->subMinutes(60 - $i),
            ]);
        }

        $history = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.jobs', ['tab' => 'history'])));
        $this->assertStringContainsString('nvm-pager', $history);
        $this->assertStringContainsString('ทั้งหมด 16 งาน', $history);

        $earnings = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.earnings', ['period' => 'all'])));
        $this->assertStringContainsString('nvm-pager', $earnings);
    }

    public function test_approved_rider_without_consent_sees_consent_card(): void
    {
        $rider = $this->makeRider(['share_location_consent_at' => null]);

        $html = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.dashboard')));
        $this->assertStringContainsString('ยินยอมให้ลูกค้าเห็นตำแหน่งของคุณระหว่างส่งงาน', $html);
        $this->assertStringContainsString('giveConsent()', $html);
    }

    public function test_rejected_and_suspended_riders_render_full_nova(): void
    {
        $rejected = $this->makeRider(['status' => 'rejected', 'availability' => 'offline', 'approved_at' => null, 'rejection_reason' => 'รูปบัตรไม่ชัด']);
        $this->assertStringContainsString('รูปบัตรไม่ชัด', $this->assertFullNova($this->actingAs($rejected->user)->get(route('user.rider.status'))));

        $suspended = $this->makeRider(['status' => 'suspended', 'availability' => 'offline', 'suspension_reason' => 'ส่งของช้าหลายครั้ง', 'suspended_at' => now()]);
        $this->assertStringContainsString('ส่งของช้าหลายครั้ง', $this->assertFullNova($this->actingAs($suspended->user)->get(route('user.rider.dashboard'))));
        $this->assertStringContainsString('บัญชียังรับงานไม่ได้', $this->assertFullNova($this->actingAs($suspended->user)->get(route('user.rider.jobs'))));
    }

    // =====================================================
    // ความเป็นเจ้าของ
    // =====================================================

    public function test_rider_cannot_view_job_assigned_to_another_rider(): void
    {
        $owner = $this->makeRider();
        $other = $this->makeRider();
        $job = $this->createJob();

        app(RiderJobService::class)->accept($job, $owner->fresh());

        $this->actingAs($other->user)->get(route('user.rider.jobs.show', $job))
            ->assertForbidden()
            ->assertDontSee('0822222222');
    }

    public function test_document_file_is_not_found_for_member_without_rider(): void
    {
        $user = User::factory()->create(['role' => 'user', 'kyc_status' => 'approved']);

        $this->actingAs($user)->get(route('user.rider.documents.file', ['type' => 'id_card']))->assertNotFound();
    }

    // =====================================================
    // ความเป็นส่วนตัว
    // =====================================================

    public function test_open_job_hides_customer_phone_and_name(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();

        foreach ([route('user.rider.jobs.show', $job), route('user.rider.dashboard'), route('user.rider.jobs', ['tab' => 'available'])] as $url) {
            $html = $this->assertFullNova($this->actingAs($rider->user)->get($url));
            $this->assertStringNotContainsString('0822222222', $html, $url);
            $this->assertStringNotContainsString('คุณลูกค้า', $html, $url);
        }
    }

    public function test_settings_masks_id_card_number(): void
    {
        $rider = $this->makeRider(['id_card_number' => '3100200345676']);

        $html = $this->assertFullNova($this->actingAs($rider->user)->get(route('user.rider.settings')));
        $this->assertStringNotContainsString('3100200345676', $html);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    private function createJob(float $cod = 0): RiderJob
    {
        $holder = User::factory()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        FakeDeliverableSource::configure((int) $holder->id, [
            'pickup' => [
                'name' => 'ร้านป้าแดง',
                'address' => 'ตลาดบางรัก เขตบางรัก กรุงเทพมหานคร',
                'latitude' => self::PICKUP_LAT,
                'longitude' => self::PICKUP_LNG,
                'phone' => '0811111111',
                'notes' => null,
            ],
            'dropoff' => [
                'name' => 'คุณลูกค้า',
                'address' => '88/8 ซ.เจริญกรุง 30 เขตบางรัก กรุงเทพมหานคร 10500',
                'latitude' => 13.7400,
                'longitude' => 100.5300,
                'phone' => '0822222222',
                'notes' => null,
            ],
            'cod' => $cod,
            'party' => [(int) $buyer->id, (int) $seller->id],
            'customer_id' => (int) $buyer->id,
        ]);

        $source = FakeDeliverableSource::findOrFail($holder->id);

        return app(RiderDispatchService::class)->createJobForSource($source, 'fresh_market')->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRider(array $overrides = []): Rider
    {
        $user = User::factory()->create(['role' => 'user', 'kyc_status' => 'approved']);
        $user->forceFill(['kyc_verified_at' => now()])->save();

        $rider = new Rider;
        $rider->forceFill(array_merge([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'gps_permission_granted' => true,
            'last_latitude' => self::PICKUP_LAT + 0.001,
            'last_longitude' => self::PICKUP_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ], $overrides))->save();

        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }
}
