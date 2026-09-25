<?php

namespace Tests\Feature\Rider;

use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\RiderLocation;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Rider\Fixtures\FakeDeliverableSource;
use Tests\TestCase;

/**
 * API ไรเดอร์ของแอป /api/v1/rider/* (RiderApiController) — ต้องใช้ MySQL รันบน CI
 *
 * ครอบคลุม gap จาก audit 2026-09-25:
 *   - RIDER-03 / RIDER-APP-06 รับงานพร้อมกัน → คนที่สองได้ 409 JOB_TAKEN
 *   - IDOR: ไรเดอร์ดู/กดงานของไรเดอร์คนอื่นไม่ได้ (403 NOT_YOUR_JOB)
 *   - RIDER-APP-04/09 ส่งสำเร็จต้องมีรูป → delivered → completed ในคำขอเดียว + รายได้เข้ากระเป๋าครั้งเดียว
 *   - RIDER-APP-02 / RIDER-29 ตัวเลขเป็น JSON number (ไม่ใช่ string ทศนิยม)
 *   - RIDER-06 รายการงานรอรับ + ปิดที่อยู่ปลายทางก่อนรับงาน
 *   - RIDER-APP-15 ยังไม่เปิดรับงาน → 200 + reason (ไม่ใช่ error)
 *   - RIDER-APP-14 heading/speed ติดลบจาก iOS ไม่ทำให้ 422
 *   - PLAY-14 อนุญาตตำแหน่งแค่ "ขณะใช้แอป" ก็เปิดรับงานได้
 *   - RIDER-27 สมัครต้องมีเลขบัตร 13 หลัก checksum ถูก + อายุ 18+ และกดซ้ำไม่ 500
 *   - RIDER-APP-19 / RIDER-13 เอกสารเก็บบน private disk + เปิดได้เฉพาะ signed URL
 */
#[Group('rider')]
class RiderApiTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = 13.7291;

    private const PICKUP_LNG = 100.5210;

    private const VALID_ID_CARD = '1101700230708';

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
    }

    // =====================================================
    // สถานะ / สมัคร
    // =====================================================

    public function test_status_for_non_rider_returns_null_rider(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/rider/status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_rider', false)
            ->assertJsonPath('data.rider', null);
    }

    public function test_status_numbers_are_json_numbers(): void
    {
        $rider = $this->makeRider(['rating' => 4.5, 'total_earnings' => 1234.5, 'total_jobs' => 7], 150.5);
        Sanctum::actingAs($rider->user);

        $data = $this->getJson('/api/v1/rider/status')
            ->assertOk()
            ->assertJsonPath('data.is_rider', true)
            ->json('data.rider');

        foreach (['rating', 'total_earnings', 'wallet_balance', 'cod_credit_available', 'completion_rate'] as $key) {
            $this->assertJsonNumber($data[$key], $key);
        }
        $this->assertIsInt($data['total_jobs']);
        $this->assertEqualsWithDelta(150.5, $data['wallet_balance'], 0.001);
        $this->assertTrue($data['can_accept_jobs']);
        $this->assertNull($data['block_reason']);
        $this->assertSame(['id_card', 'driver_license', 'vehicle_registration', 'profile'], array_keys($data['documents']));
        $this->assertArrayHasKey('location_consent', $data['permissions']);
        $this->assertArrayHasKey('verified', $data['kyc']);
    }

    public function test_register_validates_thai_id_and_age_and_is_double_tap_safe(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // checksum ผิด
        $this->postJson('/api/v1/rider/register', array_merge($this->registration(), ['id_card_number' => '1101700230707']))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('id_card_number');

        // อายุไม่ถึง 18
        $this->postJson('/api/v1/rider/register', array_merge($this->registration(), ['birth_date' => now()->subYears(17)->toDateString()]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('birth_date');

        // มอเตอร์ไซค์ต้องมีทะเบียน
        $this->postJson('/api/v1/rider/register', array_merge($this->registration(), ['vehicle_plate' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('vehicle_plate');

        $this->postJson('/api/v1/rider/register', array_merge($this->registration(), ['id_card_number' => '1-1017-00230-70-8']))
            ->assertCreated()
            ->assertJsonPath('data.outcome', 'created')
            ->assertJsonPath('data.rider.status', 'pending')
            ->assertJsonPath('data.rider.documents_complete', false);

        $rider = Rider::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(self::VALID_ID_CARD, $rider->id_card_number, 'เลขบัตรต้องถูกบันทึกเป็นตัวเลขล้วน');

        // กดซ้ำ → ไม่ 500 และไม่สร้างแถวใหม่
        $this->postJson('/api/v1/rider/register', $this->registration())
            ->assertOk()
            ->assertJsonPath('data.outcome', 'updated');
        $this->assertSame(1, Rider::where('user_id', $user->id)->count());

        // เลขบัตรซ้ำกับไรเดอร์คนอื่น
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/rider/register', $this->registration())
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_card_number');
    }

    public function test_approved_rider_cannot_register_again(): void
    {
        $rider = $this->makeRider();
        Sanctum::actingAs($rider->user);

        $this->postJson('/api/v1/rider/register', $this->registration())
            ->assertStatus(409)
            ->assertJsonPath('code', 'ALREADY_REGISTERED');
    }

    public function test_profile_update_flags_review_when_vehicle_changes_after_approval(): void
    {
        $rider = $this->makeRider(['vehicle_type' => 'bicycle']);
        Sanctum::actingAs($rider->user);

        $this->putJson('/api/v1/rider/profile', ['phone' => '0812345678', 'vehicle_type' => 'motorcycle'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('vehicle_plate');

        $this->putJson('/api/v1/rider/profile', [
            'phone' => '081-234-5678',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 999',
            'preferred_radius_km' => 7,
            'preferred_job_types' => ['fresh_market'],
        ])->assertOk()
            ->assertJsonPath('data.vehicle_changed', true)
            ->assertJsonPath('data.rider.vehicle_type', 'motorcycle')
            ->assertJsonPath('data.rider.documents_pending_review', true);

        $fresh = $rider->fresh();
        $this->assertSame('0812345678', $fresh->phone);
        $this->assertSame(['fresh_market'], $fresh->preferred_job_types);
        $this->assertEqualsWithDelta(7.0, (float) $fresh->preferred_radius_km, 0.001);

        // เปลี่ยนยานพาหนะหลังอนุมัติ → ปิดรับงานทันที และเปิดใหม่ไม่ได้จนกว่าแอดมินตรวจเอกสาร
        // (เดิมเปลี่ยนเป็นมอเตอร์ไซค์โดยไม่มีใบขับขี่แล้ววิ่งงานต่อได้เลย)
        $this->assertSame('offline', $fresh->availability);
        $this->assertSame('DOCUMENTS_REVIEW_PENDING', $fresh->onlineBlockReason()['code'] ?? null);

        $this->postJson('/api/v1/rider/availability', ['availability' => 'online', 'latitude' => self::PICKUP_LAT, 'longitude' => self::PICKUP_LNG])
            ->assertStatus(403)
            ->assertJsonPath('data.block_code', 'DOCUMENTS_REVIEW_PENDING');

        // แอดมินตรวจแล้ว → เปิดรับงานได้อีกครั้ง
        $fresh->forceFill([
            'documents_changed_at' => null,
            'id_card_image' => 'riders/'.$fresh->id.'/id_card/a.jpg',
            'profile_image' => 'riders/'.$fresh->id.'/profile/a.jpg',
            'driver_license_image' => 'riders/'.$fresh->id.'/driver_license/a.jpg',
            'vehicle_registration_image' => 'riders/'.$fresh->id.'/vehicle_registration/a.jpg',
        ])->save();
        $this->assertNull($fresh->fresh()->onlineBlockReason());
    }

    public function test_non_rider_gets_403_on_rider_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/rider/jobs/available')->assertStatus(403)->assertJsonPath('code', 'NOT_RIDER');
        $this->postJson('/api/v1/rider/location', ['latitude' => 13.7, 'longitude' => 100.5])->assertStatus(403)->assertJsonPath('code', 'NOT_RIDER');
    }

    // =====================================================
    // เอกสาร (private disk)
    // =====================================================

    public function test_document_upload_is_private_and_served_only_by_signed_url(): void
    {
        $rider = $this->makeRider(['status' => 'pending', 'availability' => 'offline']);
        Sanctum::actingAs($rider->user);

        $response = $this->post('/api/v1/rider/document', [
            'type' => 'id_card',
            'image' => UploadedFile::fake()->create('id.jpg', 200, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.type', 'id_card')
            ->assertJsonPath('data.documents.id_card', true);

        $path = $rider->fresh()->id_card_image;
        $this->assertNotEmpty($path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        $this->assertStringNotContainsString($path, $response->getContent(), 'ห้ามคืน path ไฟล์ให้แอป');

        $signedUrl = $response->json('data.url');
        $this->assertNotEmpty($signedUrl);
        $this->get($signedUrl)->assertOk();

        // ไม่มีลายเซ็น → เปิดไม่ได้
        $this->get('/api/v1/rider/documents/file/'.$rider->id.'/id_card')->assertForbidden();

        // ประเภทเอกสารผิด
        $this->post('/api/v1/rider/document', [
            'type' => 'passport',
            'image' => UploadedFile::fake()->create('x.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    // =====================================================
    // เปิดรับงาน / ตำแหน่ง
    // =====================================================

    public function test_foreground_only_location_permission_can_go_online(): void
    {
        $rider = $this->makeRider([
            'availability' => 'offline',
            'gps_permission_granted' => false,
            'share_location_consent_at' => null,
            'last_latitude' => null,
            'last_longitude' => null,
            'last_location_update' => null,
        ]);
        Sanctum::actingAs($rider->user);

        // ยังไม่อนุญาตตำแหน่งเลย และไม่มีพิกัด → เปิดไม่ได้
        $this->postJson('/api/v1/rider/availability', ['availability' => 'online'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'GPS_REQUIRED');

        // อนุญาตแค่ "ขณะใช้แอป" (gps=true, background=false) + ยินยอมแชร์ตำแหน่ง
        $this->postJson('/api/v1/rider/permissions', ['gps' => true, 'gps_background' => false, 'location_consent' => true])
            ->assertOk()
            ->assertJsonPath('data.permissions.gps', true)
            ->assertJsonPath('data.permissions.location_consent', true);

        $this->postJson('/api/v1/rider/availability', ['availability' => 'online', 'latitude' => self::PICKUP_LAT, 'longitude' => self::PICKUP_LNG])
            ->assertOk()
            ->assertJsonPath('data.availability', 'online')
            ->assertJsonPath('data.can_accept_jobs', true);
    }

    public function test_pending_rider_cannot_go_online_and_gets_account_reason_first(): void
    {
        $rider = $this->makeRider(['status' => 'pending', 'availability' => 'offline', 'gps_permission_granted' => false, 'last_location_update' => null]);
        Sanctum::actingAs($rider->user);

        $this->postJson('/api/v1/rider/availability', ['availability' => 'online'])
            ->assertForbidden()
            ->assertJsonPath('code', 'NOT_ELIGIBLE')
            ->assertJsonPath('data.block_code', 'PENDING_REVIEW');

        $this->assertSame('offline', $rider->fresh()->availability);
    }

    public function test_old_app_with_background_denied_but_fresh_location_can_go_online(): void
    {
        $rider = $this->makeRider(['availability' => 'offline', 'gps_permission_granted' => false]);
        Sanctum::actingAs($rider->user);

        $this->postJson('/api/v1/rider/availability', ['availability' => 'online'])
            ->assertOk()
            ->assertJsonPath('data.availability', 'online');
    }

    public function test_location_accepts_ios_negative_heading_and_rejects_null_island(): void
    {
        $rider = $this->makeRider();
        Sanctum::actingAs($rider->user);

        $this->postJson('/api/v1/rider/location', [
            'latitude' => self::PICKUP_LAT,
            'longitude' => self::PICKUP_LNG,
            'heading' => -1,
            'speed' => -1,
            'accuracy' => 5.5,
            'battery_level' => -1,
        ])->assertOk()
            ->assertJsonPath('data.has_active_job', false)
            ->assertJsonPath('data.is_tracking', false);

        $this->assertNotNull($rider->fresh()->last_location_update);

        $this->postJson('/api/v1/rider/location', ['latitude' => 0, 'longitude' => 0])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_LOCATION');

        // ระหว่างมีงาน → เก็บเส้นทาง (heading ติดลบเก็บเป็น null)
        $job = $this->createJob();
        app(RiderJobService::class)->accept($job, $rider->fresh());

        $this->postJson('/api/v1/rider/location', [
            'latitude' => self::PICKUP_LAT + 0.002,
            'longitude' => self::PICKUP_LNG,
            'heading' => -1,
            'job_id' => $job->id,
        ])->assertOk()
            ->assertJsonPath('data.has_active_job', true)
            ->assertJsonPath('data.job_id', $job->id);

        $point = RiderLocation::where('job_id', $job->id)->latest('id')->firstOrFail();
        $this->assertNull($point->heading);
    }

    // =====================================================
    // งาน
    // =====================================================

    public function test_available_jobs_are_numbers_and_masked_before_accept(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        Sanctum::actingAs($rider->user);

        $jobs = $this->getJson('/api/v1/rider/jobs/available')
            ->assertOk()
            ->assertJsonPath('data.reason', null)
            ->assertJsonPath('data.count', 1)
            ->json('data.jobs');

        $this->assertCount(1, $jobs);
        $item = $jobs[0];
        $this->assertSame($job->id, $item['id']);

        foreach (['distance_km', 'distance_to_pickup_km', 'total_fee', 'rider_earnings', 'platform_fee', 'cod_amount', 'base_fee'] as $key) {
            $this->assertJsonNumber($item[$key], $key);
        }
        $this->assertJsonNumber($item['pickup']['latitude'], 'pickup.latitude');
        $this->assertIsInt($item['estimated_duration_minutes']);

        // ก่อนรับงาน: ไม่เห็นชื่อ/ที่อยู่/เบอร์ผู้ซื้อ
        $this->assertNull($item['dropoff']['address']);
        $this->assertNull($item['dropoff']['phone']);
        $this->assertNull($item['dropoff']['name']);
        $this->assertTrue($item['dropoff']['is_approximate']);
        $this->assertSame(['accept'], $item['allowed_actions']);

        // ไม่สนใจงาน → หายจากรายการ
        $this->postJson("/api/v1/rider/jobs/{$job->id}/reject")->assertOk();
        $this->getJson('/api/v1/rider/jobs/available')->assertOk()->assertJsonCount(0, 'data.jobs');
    }

    public function test_offline_or_busy_rider_gets_empty_list_with_reason(): void
    {
        $rider = $this->makeRider(['availability' => 'offline']);
        $this->createJob();
        Sanctum::actingAs($rider->user);

        $this->getJson('/api/v1/rider/jobs/available')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reason', 'offline')
            ->assertJsonCount(0, 'data.jobs');

        $busy = $this->makeRider();
        $job = $this->createJob();
        app(RiderJobService::class)->accept($job, $busy);
        Sanctum::actingAs($busy->user);

        $this->getJson('/api/v1/rider/jobs/available')
            ->assertOk()
            ->assertJsonPath('data.reason', 'busy')
            ->assertJsonPath('data.active_job_id', $job->id);
    }

    public function test_accept_returns_409_when_job_already_taken(): void
    {
        $first = $this->makeRider();
        $second = $this->makeRider();
        $job = $this->createJob();

        Sanctum::actingAs($first->user);
        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.job.status', 'accepted')
            ->assertJsonPath('data.job.is_mine', true)
            ->assertJsonPath('data.job.dropoff.is_approximate', false);

        // กดซ้ำโดยคนเดิม → idempotent
        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")->assertOk();

        Sanctum::actingAs($second->user);
        $this->postJson("/api/v1/rider/jobs/{$job->id}/accept")
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'JOB_TAKEN');

        $this->assertSame((int) $first->id, (int) $job->fresh()->rider_id);
        $this->postJson('/api/v1/rider/jobs/999999/accept')->assertStatus(404)->assertJsonPath('code', 'JOB_NOT_FOUND');
    }

    public function test_rider_cannot_view_or_act_on_another_riders_job(): void
    {
        $owner = $this->makeRider();
        $other = $this->makeRider();
        $job = $this->createJob();
        app(RiderJobService::class)->accept($job, $owner);

        Sanctum::actingAs($other->user);

        $this->getJson("/api/v1/rider/jobs/{$job->id}")->assertForbidden()->assertJsonPath('code', 'NOT_YOUR_JOB');
        $this->postJson("/api/v1/rider/jobs/{$job->id}/status", ['status' => 'picking_up'])->assertForbidden()->assertJsonPath('code', 'NOT_YOUR_JOB');
        $this->postJson("/api/v1/rider/jobs/{$job->id}/release", ['reason' => 'x'])->assertForbidden();
        $this->postJson("/api/v1/rider/jobs/{$job->id}/fail", ['reason_code' => 'wrong_address'])->assertForbidden();
        $this->postJson("/api/v1/rider/jobs/{$job->id}/gps-lost")->assertForbidden();
        $this->post("/api/v1/rider/jobs/{$job->id}/deliver", ['photo' => $this->photo()], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('code', 'NOT_YOUR_JOB');

        $this->assertSame('accepted', $job->fresh()->status);
        $this->getJson('/api/v1/rider/jobs/current')->assertOk()->assertJsonPath('data.has_job', false);
        $this->getJson('/api/v1/rider/jobs/history')->assertOk()->assertJsonCount(0, 'data.jobs');

        // เจ้าของงานเห็นรายละเอียดเต็ม
        Sanctum::actingAs($owner->user);
        $this->getJson("/api/v1/rider/jobs/{$job->id}")
            ->assertOk()
            ->assertJsonPath('data.job.is_mine', true)
            ->assertJsonPath('data.job.dropoff.phone', '0822222222');
    }

    public function test_deliver_requires_photo_then_completes_once_and_credits_wallet(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        $service = app(RiderJobService::class);
        $service->accept($job, $rider);

        Sanctum::actingAs($rider->user);

        $this->postJson("/api/v1/rider/jobs/{$job->id}/status", ['status' => 'picked_up'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'picked_up');

        $this->getJson('/api/v1/rider/jobs/current')
            ->assertOk()
            ->assertJsonPath('data.has_job', true)
            ->assertJsonPath('data.job.id', $job->id);

        // ไม่มีรูป → 422 PHOTO_REQUIRED และสถานะไม่เปลี่ยน
        $this->postJson("/api/v1/rider/jobs/{$job->id}/deliver", ['latitude' => 13.74, 'longitude' => 100.53])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PHOTO_REQUIRED');
        $this->assertSame('picked_up', $job->fresh()->status);

        $response = $this->post("/api/v1/rider/jobs/{$job->id}/deliver", [
            'photo' => $this->photo(),
            'latitude' => 13.74,
            'longitude' => 100.53,
            'note' => 'ฝากไว้กับ รปภ.',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'completed');

        $this->assertJsonNumber($response->json('data.earnings.rider_earnings'), 'earnings.rider_earnings');
        $this->assertJsonNumber($response->json('data.earnings.wallet_balance'), 'earnings.wallet_balance');

        $fresh = $job->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->delivery_proof_image);
        $this->assertSame('ฝากไว้กับ รปภ.', $fresh->delivery_note);

        $balance = (float) Wallet::where('user_id', $rider->user_id)->value('balance');
        $this->assertEqualsWithDelta((float) $fresh->rider_earnings, $balance, 0.001);

        // กดซ้ำ → ไม่จ่ายซ้ำ
        $this->post("/api/v1/rider/jobs/{$job->id}/deliver", ['photo' => $this->photo()], ['Accept' => 'application/json'])->assertOk();
        $this->assertEqualsWithDelta($balance, (float) Wallet::where('user_id', $rider->user_id)->value('balance'), 0.001);

        // งานเสร็จแล้ว → ไม่ใช่งานปัจจุบัน แต่อยู่ในประวัติ + รายได้
        $this->getJson('/api/v1/rider/jobs/current')->assertOk()->assertJsonPath('data.has_job', false);
        $this->getJson('/api/v1/rider/jobs/history')->assertOk()->assertJsonCount(1, 'data.jobs')->assertJsonPath('data.pagination.total', 1);

        $earnings = $this->getJson('/api/v1/rider/earnings?period=today')->assertOk()->json('data');
        $this->assertSame(1, $earnings['completed_jobs']);
        $this->assertJsonNumber($earnings['gross_earnings'], 'gross_earnings');
        $this->assertCount(1, $earnings['recent_jobs']);
    }

    public function test_cod_job_requires_cash_confirmation(): void
    {
        $rider = $this->makeRider([], 500);
        $job = $this->createJob(['cod' => 200]);
        $service = app(RiderJobService::class);
        $service->accept($job, $rider);
        $service->markPickedUp($job->fresh(), $rider->fresh());

        Sanctum::actingAs($rider->user);

        $this->post("/api/v1/rider/jobs/{$job->id}/deliver", ['photo' => $this->photo()], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'COD_CONFIRM_REQUIRED');

        $this->post("/api/v1/rider/jobs/{$job->id}/deliver", ['photo' => $this->photo(), 'cod_collected' => '1'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'completed')
            ->assertJsonPath('data.earnings.cod_amount', 200);
    }

    public function test_fail_requires_valid_reason_after_pickup_only(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        app(RiderJobService::class)->accept($job, $rider);
        Sanctum::actingAs($rider->user);

        // ยังไม่รับของ → ส่งไม่สำเร็จไม่ได้
        $this->postJson("/api/v1/rider/jobs/{$job->id}/fail", ['reason_code' => 'wrong_address'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVALID_TRANSITION');

        $this->postJson("/api/v1/rider/jobs/{$job->id}/status", ['status' => 'picked_up'])->assertOk();

        $this->postJson("/api/v1/rider/jobs/{$job->id}/fail", ['reason_code' => 'lost_it'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->postJson("/api/v1/rider/jobs/{$job->id}/fail", ['reason_code' => 'other'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('note');

        $this->postJson("/api/v1/rider/jobs/{$job->id}/fail", ['reason_code' => 'customer_unreachable', 'note' => 'โทร 3 ครั้งไม่รับ'])
            ->assertOk()
            ->assertJsonPath('data.job.status', 'failed')
            ->assertJsonPath('data.job.failure.reason_code', 'customer_unreachable');
    }

    public function test_release_before_pickup_returns_job_to_queue(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        app(RiderJobService::class)->accept($job, $rider);
        Sanctum::actingAs($rider->user);

        $this->postJson("/api/v1/rider/jobs/{$job->id}/release", ['reason' => 'รถเสีย'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->assertNull($job->fresh()->rider_id);
        $this->assertSame('online', $rider->fresh()->availability);
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
            'full_name' => 'สมชาย ใจดี',
            'phone' => '081-234-5678',
            'id_card_number' => self::VALID_ID_CARD,
            'birth_date' => now()->subYears(25)->toDateString(),
            'address' => '99/1 ซ.สุขุมวิท 21 เขตวัฒนา กรุงเทพมหานคร 10110',
            'province' => 'กรุงเทพมหานคร',
            'district' => 'วัฒนา',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createJob(array $config = []): RiderJob
    {
        return app(RiderDispatchService::class)->createJobForSource($this->makeSource($config), 'fresh_market')->fresh();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeSource(array $config = []): FakeDeliverableSource
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
                'address' => '88/8 ซ.เจริญกรุง 30 แขวงบางรัก เขตบางรัก กรุงเทพมหานคร 10500',
                'latitude' => 13.7400,
                'longitude' => 100.5300,
                'phone' => '0822222222',
                'notes' => 'ฝากไว้ที่ป้อม',
            ],
            'cod' => 0,
            'party' => [(int) $buyer->id, (int) $seller->id],
            'customer_id' => (int) $buyer->id,
        ], $config));

        return FakeDeliverableSource::findOrFail($holder->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRider(array $overrides = [], float $walletBalance = 0): Rider
    {
        $user = User::factory()->create();

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

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg');
    }

    /**
     * ต้องเป็น JSON number (int/float) ไม่ใช่ string ทศนิยม
     */
    private function assertJsonNumber(mixed $value, string $key): void
    {
        $this->assertTrue(is_int($value) || is_float($value), "{$key} ต้องเป็นตัวเลข (ได้ ".get_debug_type($value).')');
    }
}
