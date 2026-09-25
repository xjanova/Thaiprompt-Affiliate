<?php

namespace Tests\Feature\Rider;

use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Services\DeliveryFeeCalculator;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Rider\Fixtures\FakeDeliverableSource;
use Tests\TestCase;

/**
 * หลังบ้านไรเดอร์ + หน้าเว็บไรเดอร์ (session) + ลิงก์ติดตามของลูกค้า — ต้องใช้ MySQL รันบน CI
 *
 * ครอบคลุม gap จาก audit 2026-09-25:
 *   - RIDER-09 ปฏิเสธใบสมัครได้จริง + บันทึก rejected_at/by + ไรเดอร์ส่งใหม่ได้
 *   - RIDER-12 อนุมัติต้องมีเอกสารครบ / เปิดเอกสารผ่าน route แอดมิน (private disk)
 *   - RIDER-10 แอดมินยกเลิกงาน (cancelled_by = admin) / รับของแล้ว → failed
 *   - CC-14 มอบหมายใหม่ผ่าน RiderJobService::adminReassign (ตรวจงานค้าง)
 *   - ระงับไรเดอร์ → บังคับออฟไลน์ + งานก่อนรับของกลับเข้าคิว
 *   - RIDER-11 จอติดตาม/แผนที่ใช้ last_latitude/last_longitude
 *   - ตั้งค่า rider.* ตรวจช่วงค่า
 *   - RIDER-23 / FM-23 หน้าเว็บไรเดอร์กดงานผ่าน route session (ไม่ใช่ Sanctum token)
 *   - ลิงก์ติดตามใช้ได้ถึง 1 ชม. หลังงานจบ และไม่มีตำแหน่งสดหลังส่งของแล้ว
 */
#[Group('rider')]
class RiderAdminWebTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = 13.7291;

    private const PICKUP_LNG = 100.5210;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');
        Storage::fake('local');
        Cache::flush();
        FakeDeliverableSource::reset();

        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // =====================================================
    // แอดมิน: ใบสมัคร / เอกสาร
    // =====================================================

    public function test_reject_records_who_and_when_and_rider_can_reapply(): void
    {
        $rider = $this->makeRider(['status' => 'pending', 'availability' => 'offline']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.reject', $rider), [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.reject', $rider), ['reason' => 'รูปบัตรประชาชนไม่ชัด'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $fresh = $rider->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('รูปบัตรประชาชนไม่ชัด', $fresh->rejection_reason);
        $this->assertNotNull($fresh->rejected_at);
        $this->assertSame((int) $this->admin->id, (int) $fresh->rejected_by);

        // ไรเดอร์ส่งใบสมัครใหม่จากเว็บ → กลับเป็นรอตรวจ
        $this->actingAs($rider->user)
            ->post(route('user.rider.register.submit'), $this->registration())
            ->assertRedirect(route('user.rider.documents'));

        $again = $rider->fresh();
        $this->assertSame('pending', $again->status);
        $this->assertNull($again->rejection_reason);
        $this->assertNull($again->rejected_at);
    }

    public function test_web_registration_enforces_thai_id_and_age(): void
    {
        // ต้องตั้ง role ในโมเดลที่ใช้ actingAs (ค่า default ของคอลัมน์ไม่อยู่ในโมเดลที่เพิ่ง create)
        // ไม่งั้น CheckRole ของกลุ่ม /user redirect ออกก่อนถึง FormRequest
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->post(route('user.rider.register.submit'), array_merge($this->registration(), ['id_card_number' => '1234567890123']))
            ->assertSessionHasErrors('id_card_number');

        $this->actingAs($user)
            ->post(route('user.rider.register.submit'), array_merge($this->registration(), ['birth_date' => now()->subYears(16)->toDateString()]))
            ->assertSessionHasErrors('birth_date');

        $this->assertFalse(Rider::where('user_id', $user->id)->exists());
    }

    public function test_approve_requires_documents_and_admin_views_them_through_private_route(): void
    {
        $rider = $this->makeRider(['status' => 'pending', 'availability' => 'offline']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.approve', $rider))
            ->assertStatus(422)
            ->assertJsonPath('code', 'DOCUMENTS_INCOMPLETE');
        $this->assertSame('pending', $rider->fresh()->status);

        // ไรเดอร์อัปโหลดเอกสารครบจากหน้าเว็บ (private disk)
        foreach (['id_card', 'driver_license', 'vehicle_registration', 'profile'] as $type) {
            $this->actingAs($rider->user)
                ->post(route('user.rider.documents.upload'), [
                    'document_type' => $type,
                    'document' => UploadedFile::fake()->create($type.'.jpg', 100, 'image/jpeg'),
                ], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonPath('data.uploaded', true);
        }

        $fresh = $rider->fresh();
        Storage::disk('local')->assertExists($fresh->id_card_image);
        Storage::disk('public')->assertMissing($fresh->id_card_image);

        // เจ้าของเปิดเอกสารตัวเองได้ / แอดมินเปิดได้ / ผู้ใช้อื่นเปิดไม่ได้
        $this->actingAs($rider->user)->get(route('user.rider.documents.file', 'id_card'))->assertOk();

        $response = $this->actingAs($this->admin)->get(route('admin.riders.document', [$rider, 'driver_license']));
        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $stranger = User::factory()->create();
        $this->assertNotSame(200, $this->actingAs($stranger)->get(route('admin.riders.document', [$rider, 'id_card']))->status());

        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.approve', $rider))
            ->assertOk()
            ->assertJsonPath('success', true);

        $approved = $rider->fresh();
        $this->assertSame('approved', $approved->status);
        $this->assertSame((int) $this->admin->id, (int) $approved->approved_by);
    }

    // =====================================================
    // แอดมิน: งาน
    // =====================================================

    public function test_admin_cancel_before_pickup_uses_admin_enum_and_frees_rider(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        app(RiderJobService::class)->accept($job, $rider);

        $this->actingAs($this->admin)
            ->postJson(route('admin.rider-jobs.cancel', $job), ['reason' => 'ร้านปิดกะทันหัน'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'cancelled');

        $fresh = $job->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertSame('admin', $fresh->cancelled_by);
        $this->assertSame('online', $rider->fresh()->availability);

        // งานจบแล้ว ยกเลิกซ้ำไม่ได้
        $this->actingAs($this->admin)
            ->postJson(route('admin.rider-jobs.cancel', $job), ['reason' => 'ซ้ำ'])
            ->assertStatus(409);
    }

    public function test_admin_cancel_after_pickup_marks_failed(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        $service = app(RiderJobService::class);
        $service->accept($job, $rider);
        $service->markPickedUp($job->fresh(), $rider->fresh());

        $this->actingAs($this->admin)
            ->postJson(route('admin.rider-jobs.cancel', $job), ['reason' => 'ลูกค้ายกเลิก'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'failed');

        $this->assertSame('admin_intervention', $job->fresh()->failure_reason);
    }

    public function test_admin_reassign_goes_through_service_checks(): void
    {
        $first = $this->makeRider();
        $second = $this->makeRider();
        $busy = $this->makeRider();

        $job = $this->createJob();
        app(RiderJobService::class)->accept($job, $first);

        $otherJob = $this->createJob();
        app(RiderJobService::class)->accept($otherJob, $busy);

        $this->actingAs($this->admin)
            ->postJson(route('admin.rider-jobs.reassign', $job), ['rider_id' => $busy->id])
            ->assertStatus(409)
            ->assertJsonPath('code', 'HAS_ACTIVE_JOB');

        $this->actingAs($this->admin)
            ->postJson(route('admin.rider-jobs.reassign', $job), ['rider_id' => $second->id])
            ->assertOk()
            ->assertJsonPath('data.job.rider_id', $second->id);

        $this->assertSame((int) $second->id, (int) $job->fresh()->rider_id);
        $this->assertSame('online', $first->fresh()->availability);
        $this->assertSame('busy', $second->fresh()->availability);
    }

    public function test_suspend_forces_offline_and_returns_pre_pickup_job_to_queue(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        app(RiderJobService::class)->accept($job, $rider);

        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.suspend', $rider), [])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.suspend', $rider), ['reason' => 'ร้องเรียนพฤติกรรม'])
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $fresh = $rider->fresh();
        $this->assertSame('suspended', $fresh->status);
        $this->assertSame('offline', $fresh->availability);
        $this->assertSame((int) $this->admin->id, (int) $fresh->suspended_by);

        $jobFresh = $job->fresh();
        $this->assertSame('pending', $jobFresh->status);
        $this->assertNull($jobFresh->rider_id);

        // ยกเลิกการระงับ
        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.toggle-active', $rider))
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
        $this->assertSame('approved', $rider->fresh()->status);
    }

    public function test_dispatch_monitor_and_gps_data_use_last_location_columns(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();

        $data = $this->actingAs($this->admin)
            ->getJson(route('admin.riders.dispatch-monitor'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data');

        $this->assertSame([$job->id], array_column($data['pending_jobs'], 'id'));
        $this->assertContains($rider->id, array_column($data['riders'], 'id'));
        $this->assertTrue(is_float($data['pending_jobs'][0]['total_fee']) || is_int($data['pending_jobs'][0]['total_fee']));
        $this->assertSame(1, $data['stats']['pending']);

        $gps = $this->actingAs($this->admin)
            ->getJson(route('admin.riders.gps-data'))
            ->assertOk()
            ->json('data.riders');

        $row = collect($gps)->firstWhere('id', $rider->id);
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(self::PICKUP_LAT + 0.001, $row['latitude'], 0.000001);
        $this->assertFalse($row['is_stale']);
    }

    public function test_settings_update_validates_ranges_and_saves(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.settings.update'), ['settings' => ['rider_share_percent' => 150]])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.settings.update'), ['settings' => ['offer_radius_km' => 8, 'max_offer_radius_km' => 6]])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson(route('admin.riders.settings.update'), ['settings' => ['rider_share_percent' => 75, 'base_fee' => 35, 'dispatch_mode' => 'cascade']])
            ->assertOk()
            ->assertJsonPath('success', true);

        $config = new DeliveryFeeCalculator;
        $this->assertEqualsWithDelta(75.0, $config->floatSetting('rider.rider_share_percent'), 0.001);
        $this->assertEqualsWithDelta(35.0, $config->floatSetting('rider.base_fee'), 0.001);
        $this->assertSame('cascade', $config->dispatchMode());
    }

    // =====================================================
    // เว็บไรเดอร์ (session) + หน้างานที่กำลังทำ
    // =====================================================

    public function test_web_session_routes_drive_the_job_flow(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();

        $this->actingAs($rider->user)
            ->postJson(route('user.rider.jobs.accept', $job))
            ->assertOk()
            ->assertJsonPath('data.job.status', 'accepted');

        // ไรเดอร์คนอื่นเปิดหน้างาน/กดงานนี้ไม่ได้
        $other = $this->makeRider();
        $this->actingAs($other->user)->get(route('taladsod.rider.active-job', $job))->assertForbidden();
        $this->actingAs($other->user)->get(route('user.rider.jobs.show', $job))->assertForbidden();
        $this->actingAs($other->user)
            ->postJson(route('user.rider.jobs.status', $job), ['status' => 'picked_up'])
            ->assertForbidden()
            ->assertJsonPath('code', 'NOT_YOUR_JOB');

        $this->actingAs($rider->user)
            ->postJson(route('user.rider.location'), ['latitude' => self::PICKUP_LAT, 'longitude' => self::PICKUP_LNG, 'heading' => -1])
            ->assertOk()
            ->assertJsonPath('data.has_active_job', true);

        $this->actingAs($rider->user)
            ->postJson(route('user.rider.jobs.status', $job), ['status' => 'picked_up'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'picked_up');

        $this->actingAs($rider->user)
            ->post(route('user.rider.jobs.deliver', $job), [], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PHOTO_REQUIRED');

        $this->actingAs($rider->user)
            ->post(route('user.rider.jobs.deliver', $job), ['photo' => UploadedFile::fake()->create('p.jpg', 80, 'image/jpeg')], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'completed');

        // งานจบแล้ว → หน้างานที่กำลังทำพาไปหน้ารายละเอียดแทน
        $this->actingAs($rider->user)
            ->get(route('taladsod.rider.active-job', $job))
            ->assertRedirect(route('user.rider.jobs.show', $job));
    }

    // =====================================================
    // ลิงก์ติดตามของลูกค้า
    // =====================================================

    public function test_tracking_link_lives_one_hour_after_completion_and_stops_live_location(): void
    {
        $rider = $this->makeRider();
        Storage::disk('local')->put('riders/'.$rider->id.'/profile/me.jpg', 'fake-image');
        $rider->forceFill(['profile_image' => 'riders/'.$rider->id.'/profile/me.jpg'])->save();

        $job = $this->createJob();
        $service = app(RiderJobService::class);
        $service->accept($job, $rider->fresh());
        $token = (string) $job->fresh()->tracking_token;

        $this->getJson("/taladsod/track/{$token}/location")
            ->assertOk()
            ->assertJsonPath('is_active', true)
            ->assertJsonPath('location.available', true);

        $this->get("/taladsod/track/{$token}/rider-photo")->assertOk();

        $service->markPickedUp($job->fresh(), $rider->fresh());
        $service->deliver($job->fresh(), $rider->fresh(), UploadedFile::fake()->create('d.jpg', 80, 'image/jpeg'), null, null, false);

        // ส่งของแล้ว → ลิงก์ยังเปิดได้ แต่ไม่มีตำแหน่งสด
        $this->getJson("/taladsod/track/{$token}/location")
            ->assertOk()
            ->assertJsonPath('is_active', false)
            ->assertJsonPath('location.available', false);
        $this->getJson("/taladsod/track/{$token}/route")->assertOk()->assertJsonPath('route', []);

        // เกิน 1 ชั่วโมงหลังจบงาน → ลิงก์หมดอายุ
        $this->travel(61)->minutes();

        $this->getJson("/taladsod/track/{$token}/location")
            ->assertNotFound()
            ->assertJsonPath('code', 'TRACKING_EXPIRED');
        $this->get("/taladsod/track/{$token}/rider-photo")->assertNotFound();
        $this->get('/taladsod/track/short-token/location')->assertNotFound();
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * @return array<string, mixed>
     */
    private function registration(): array
    {
        return [
            'full_name' => 'สมหญิง ขยันส่ง',
            'phone' => '0898765432',
            'id_card_number' => '3100200345676',
            'birth_date' => now()->subYears(30)->toDateString(),
            'address' => '12/3 ถ.เจริญกรุง เขตบางรัก กรุงเทพมหานคร 10500',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '2กค 5678',
            // หน้าเว็บบังคับติ๊กยินยอม PDPA ทุกครั้งที่ส่งใบสมัคร (User\RiderController::submitRegistration)
            'pdpa_consent' => '1',
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createJob(array $config = []): RiderJob
    {
        $holder = User::factory()->create();
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        FakeDeliverableSource::configure((int) $holder->id, array_merge([
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
            'cod' => 0,
            'party' => [(int) $buyer->id, (int) $seller->id],
            'customer_id' => (int) $buyer->id,
        ], $config));

        $source = FakeDeliverableSource::findOrFail($holder->id);

        return app(RiderDispatchService::class)->createJobForSource($source, 'fresh_market')->fresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRider(array $overrides = [], float $walletBalance = 0): Rider
    {
        $user = User::factory()->create(['role' => 'user']);

        $rider = new Rider;
        $rider->forceFill(array_merge([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'gps_permission_granted' => true,
            'last_latitude' => self::PICKUP_LAT + 0.001,
            'last_longitude' => self::PICKUP_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ], $overrides))->save();

        $wallet = app(WalletService::class)->getOrCreateWallet($user);
        $wallet->forceFill(['balance' => $walletBalance])->save();

        return $rider->fresh();
    }
}
