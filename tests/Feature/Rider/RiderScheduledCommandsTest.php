<?php

namespace Tests\Feature\Rider;

use App\Models\Notification;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\RiderLocation;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RiderDispatchService;
use App\Services\RiderGpsTrackingService;
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
 * คำสั่งตั้งเวลาของระบบไรเดอร์ (ต้องใช้ MySQL — รันบน CI)
 *
 * - rider:sweep-pending   กระจายซ้ำด้วยรัศมีกว้างขึ้น / ส่งแอดมินเมื่อหาไรเดอร์ไม่ได้ / เคลียร์เงิน COD ค้าง (RIDER-17)
 * - rider:auto-offline    ไรเดอร์ผีถูกปิดรับงาน (CC-12)
 * - rider:gps-watch       GPS หายระหว่างงาน → หยุดชั่วคราว, ตำแหน่งใหม่เข้ามา → กลับมาเอง (CC-13)
 * - rider:purge-locations ลบตำแหน่งเก่ากว่า 30 วัน (CC-22)
 */
#[Group('rider')]
class RiderScheduledCommandsTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = 13.7291;

    private const PICKUP_LNG = 100.5210;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');
        Cache::flush();
        FakeDeliverableSource::reset();

        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');
    }

    public function test_auto_offline_turns_off_ghost_riders_only(): void
    {
        $ghost = $this->makeRider(['last_location_update' => now()->subMinutes(30)]);
        $fresh = $this->makeRider();

        $this->artisan('rider:auto-offline')->assertSuccessful();

        $this->assertSame('offline', $ghost->fresh()->availability);
        $this->assertSame('online', $fresh->fresh()->availability);
        $this->assertTrue(Notification::where('user_id', $ghost->user_id)->where('type', 'rider_account')->exists());
    }

    public function test_purge_locations_keeps_recent_history(): void
    {
        $rider = $this->makeRider();
        $old = RiderLocation::recordLocation($rider->id, ['latitude' => 13.7, 'longitude' => 100.5, 'recorded_at' => now()->subDays(40)]);
        $recent = RiderLocation::recordLocation($rider->id, ['latitude' => 13.7, 'longitude' => 100.5, 'recorded_at' => now()->subDays(2)]);

        $this->artisan('rider:purge-locations')->assertSuccessful();

        $this->assertNull(RiderLocation::find($old->id));
        $this->assertNotNull(RiderLocation::find($recent->id));
    }

    public function test_sweep_widens_radius_then_escalates_to_admin(): void
    {
        // ไรเดอร์อยู่ห่างจุดรับ ~6.7 กม. → รอบแรก (5 กม.) ไม่เจอ รอบถัดไป (7.5 กม.) เจอ
        $far = $this->makeRider([
            'last_latitude' => self::PICKUP_LAT + 0.06,
            'last_longitude' => self::PICKUP_LNG,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $job = app(RiderDispatchService::class)->createJobForSource($this->makeSource(), 'fresh_market')->fresh();
        $this->assertSame(1, (int) $job->dispatch_round);
        $this->assertNotContains((int) $far->id, array_map('intval', $job->candidate_riders ?? []));

        RiderJob::whereKey($job->id)->update(['last_dispatched_at' => now()->subMinutes(5)]);
        $this->artisan('rider:sweep-pending')->assertSuccessful();

        $job->refresh();
        $this->assertSame(2, (int) $job->dispatch_round);
        $this->assertEqualsWithDelta(7.5, (float) $job->dispatch_radius_km, 0.01);
        $this->assertContains((int) $far->id, array_map('intval', $job->candidate_riders ?? []));

        // รอนานเกิน rider.pending_timeout_minutes → ส่งต่อแอดมิน (ครั้งเดียว)
        RiderJob::whereKey($job->id)->update(['created_at' => now()->subMinutes(30)]);
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->artisan('rider:sweep-pending')->assertSuccessful();

        $job->refresh();
        $this->assertSame('manual_needed', $job->dispatch_type);
        $this->assertTrue($job->isOpen(), 'ยังเปิดให้ไรเดอร์/แอดมินรับได้');
        $this->assertSame(1, Notification::where('user_id', $admin->id)->where('type', 'rider_admin')->count());
    }

    public function test_gps_watch_pauses_and_location_update_resumes(): void
    {
        $rider = $this->makeRider();
        $job = app(RiderDispatchService::class)->createJobForSource($this->makeSource(), 'fresh_market');
        app(RiderJobService::class)->accept($job, $rider);

        Rider::whereKey($rider->id)->update(['last_location_update' => now()->subMinutes(10)]);

        $this->artisan('rider:gps-watch')->assertSuccessful();
        $this->artisan('rider:gps-watch')->assertSuccessful(); // รอบซ้ำไม่นับเตือนเพิ่ม

        $job->refresh();
        $this->assertFalse($job->gps_active);
        $this->assertSame(1, (int) $job->gps_warning_count);

        $result = (new RiderGpsTrackingService)->recordRiderLocation($rider->fresh(), [
            'latitude' => self::PICKUP_LAT,
            'longitude' => self::PICKUP_LNG,
            'heading' => -1,
            'speed' => 3.5,
        ]);

        $this->assertTrue($result['has_active_job']);
        $this->assertTrue($result['gps_resumed']);
        $this->assertTrue($job->fresh()->gps_active);

        $location = RiderLocation::where('job_id', $job->id)->latest('id')->first();
        $this->assertNotNull($location);
        $this->assertNull($location->heading, 'heading ติดลบจาก iOS ต้องเก็บเป็น null');
    }

    public function test_sweep_retries_cod_settlement_after_wallet_top_up(): void
    {
        $rider = $this->makeRider([], 1000);
        $job = app(RiderDispatchService::class)->createJobForSource($this->makeSource(['cod' => 300]), 'fresh_market');
        $service = app(RiderJobService::class);

        $service->accept($job, $rider);
        $service->markPickedUp($job->fresh(), $rider, null);

        // ไรเดอร์ถอนเงินออกระหว่างวิ่ง → ตอนส่งยอดไม่พอเคลียร์ COD
        Wallet::where('user_id', $rider->user_id)->update(['balance' => 5]);
        $service->deliver($job->fresh(), $rider, UploadedFile::fake()->create('p.jpg', 50, 'image/jpeg'), null, null, true);

        $job->refresh();
        $this->assertSame('completed', $job->status);
        $this->assertNull($job->cod_settled_at);
        $this->assertNotNull($job->earnings_settled_at);

        // เติมเงินแล้ว sweep → เคลียร์ได้ครั้งเดียว
        Wallet::where('user_id', $rider->user_id)->update(['balance' => 1000]);
        $this->artisan('rider:sweep-pending')->assertSuccessful();
        $this->artisan('rider:sweep-pending')->assertSuccessful();

        $job->refresh();
        $this->assertNotNull($job->cod_settled_at);
        $net = round(300 - (float) $job->rider_earnings, 2);
        $this->assertEqualsWithDelta(1000 - $net, (float) Wallet::where('user_id', $rider->user_id)->value('balance'), 0.001);
        $this->assertSame(1, (int) $rider->fresh()->completed_jobs);
    }

    // =====================================================
    // Helpers
    // =====================================================

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeSource(array $config = []): FakeDeliverableSource
    {
        $holder = User::factory()->create();
        $buyer = User::factory()->create();

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
            'party' => [(int) $buyer->id],
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

        $rider = Rider::create(array_merge([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'last_latitude' => self::PICKUP_LAT + 0.001,
            'last_longitude' => self::PICKUP_LNG + 0.001,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ], $overrides));

        $wallet = app(WalletService::class)->getOrCreateWallet($user);
        $wallet->forceFill(['balance' => $walletBalance])->save();

        return $rider->fresh();
    }
}
