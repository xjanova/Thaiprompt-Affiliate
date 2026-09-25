<?php

namespace Tests\Feature\Rider;

use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Services\MenuService;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use App\Support\RiderWebUi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Rider\Fixtures\FakeDeliverableSource;
use Tests\TestCase;

/**
 * หน้าเว็บไรเดอร์ V4 (/user/rider/*) — ทุกหน้าเปิดได้จริงตามสถานะไรเดอร์ (ต้องใช้ MySQL รันบน CI)
 *
 * ครอบคลุม gap จาก audit 2026-09-25:
 *   - RIDER-07 / GAP-01 หน้าเว็บไรเดอร์ขาด view 6 หน้า (เปิดแล้ว 500) + ไม่มีเมนู
 *   - ยังไม่สมัคร / รอตรวจ / ถูกปฏิเสธ / อนุมัติแล้ว / ถูกระงับ → แต่ละหน้าได้ 200 หรือพาไปหน้าที่ถูก
 *   - สมัครจากเว็บต้องยินยอม PDPA และบันทึกเวลาไว้
 *   - เมนูผู้ใช้มี ไรเดอร์ / คำสั่งซื้อของฉัน / ที่อยู่จัดส่ง / ตลาดสด / สมัครเปิดร้าน
 */
#[Group('rider')]
class RiderWebPagesTest extends TestCase
{
    use RefreshDatabase;

    private const PICKUP_LAT = 13.7291;

    private const PICKUP_LNG = 100.5210;

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
    // ยังไม่สมัคร
    // =====================================================

    public function test_user_without_rider_sees_register_form_and_other_pages_redirect_there(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('user.rider.register'))
            ->assertOk()
            ->assertSee('tp-root', false)
            ->assertSee('tp-card', false)
            ->assertSee('name="pdpa_consent"', false)
            ->assertSee('name="id_card_number"', false)
            ->assertSee(route('user.rider.register.submit'), false);

        foreach (['dashboard', 'status', 'documents', 'jobs', 'earnings', 'settings'] as $page) {
            $this->actingAs($user)->get(route('user.rider.'.$page))
                ->assertRedirect(route('user.rider.register'));
        }
    }

    public function test_web_registration_requires_pdpa_consent_and_records_it(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->from(route('user.rider.register'))
            ->post(route('user.rider.register.submit'), $this->registration(['pdpa_consent' => null]))
            ->assertRedirect(route('user.rider.register'))
            ->assertSessionHasErrors('pdpa_consent');
        $this->assertFalse(Rider::where('user_id', $user->id)->exists());

        $this->actingAs($user)
            ->post(route('user.rider.register.submit'), $this->registration())
            ->assertRedirect(route('user.rider.documents'));

        $rider = Rider::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('pending', $rider->status);
        $this->assertNotNull($rider->getAttribute('pdpa_consent_at'));
    }

    // =====================================================
    // รอตรวจ / ถูกปฏิเสธ
    // =====================================================

    public function test_pending_rider_pages(): void
    {
        $rider = $this->makeRider(['status' => 'pending', 'availability' => 'offline', 'approved_at' => null, 'share_location_consent_at' => null]);
        $user = $rider->user;

        $this->actingAs($user)->get(route('user.rider.dashboard'))->assertRedirect(route('user.rider.status'));
        $this->actingAs($user)->get(route('user.rider.jobs'))->assertRedirect(route('user.rider.status'));
        $this->actingAs($user)->get(route('user.rider.earnings'))->assertRedirect(route('user.rider.status'));

        $this->actingAs($user)->get(route('user.rider.status'))
            ->assertOk()->assertSee('tp-card', false)->assertSee('รอตรวจสอบ')->assertSee(route('user.rider.documents'), false);

        $this->actingAs($user)->get(route('user.rider.documents'))
            ->assertOk()->assertSee('tp-card', false)->assertSee('เอกสารไรเดอร์')
            ->assertSee('name="document_type"', false)->assertSee(route('user.rider.documents.upload'), false);

        $this->actingAs($user)->get(route('user.rider.register'))
            ->assertOk()->assertSee('tp-card', false)->assertSee('แก้ไขใบสมัคร');

        $this->actingAs($user)->get(route('user.rider.settings'))
            ->assertOk()->assertSee('tp-card', false)->assertSee(route('user.rider.settings.update'), false);
    }

    public function test_rejected_rider_sees_reason_and_can_reapply(): void
    {
        $rider = $this->makeRider([
            'status' => 'rejected',
            'availability' => 'offline',
            'approved_at' => null,
            'rejection_reason' => 'รูปบัตรไม่ชัด',
        ]);

        $this->actingAs($rider->user)->get(route('user.rider.status'))
            ->assertOk()->assertSee('รูปบัตรไม่ชัด')->assertSee(route('user.rider.register'), false);

        $this->actingAs($rider->user)->get(route('user.rider.register'))
            ->assertOk()->assertSee('ส่งใบสมัครใหม่')->assertSee('รูปบัตรไม่ชัด');
    }

    // =====================================================
    // อนุมัติแล้ว
    // =====================================================

    public function test_approved_rider_pages_render_with_available_jobs(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        $user = $rider->user;

        $this->actingAs($user)->get(route('user.rider.register'))->assertRedirect(route('user.rider.dashboard'));
        $this->actingAs($user)->get(route('user.rider.status'))->assertRedirect(route('user.rider.dashboard'));

        $this->actingAs($user)->get(route('user.rider.dashboard'))
            ->assertOk()
            ->assertSee('tp-root', false)
            ->assertSee('rd-scope', false)
            ->assertSee('รายได้วันนี้')
            ->assertSee((string) $job->job_number)
            ->assertSee('รับงานนี้')
            // URL ปุ่มรับงานอยู่ใน config ของ Alpine (Js::from → เครื่องหมาย / ถูก escape) จึงตรวจลิงก์รายละเอียดแทน
            ->assertSee(route('user.rider.jobs.show', $job), false)
            ->assertDontSee('ยินยอมให้ลูกค้าเห็นตำแหน่งของคุณระหว่างส่งงาน');

        foreach (['available', 'current', 'history'] as $tab) {
            $this->actingAs($user)->get(route('user.rider.jobs', ['tab' => $tab]))->assertOk()->assertSee('tp-card', false);
        }
        $this->actingAs($user)->get(route('user.rider.jobs', ['tab' => 'available']))->assertSee((string) $job->job_number);

        foreach (['today', 'week', 'month', 'all'] as $period) {
            $this->actingAs($user)->get(route('user.rider.earnings', ['period' => $period]))->assertOk()->assertSee('รายได้ของคุณ');
        }

        $this->actingAs($user)->get(route('user.rider.documents'))->assertOk()->assertSee('tp-card', false);
        $this->actingAs($user)->get(route('user.rider.settings'))
            ->assertOk()->assertSee('name="preferred_job_types[]"', false)->assertSee('ยินยอมแล้ว');

        // งานที่ยังเปิดรับ → เห็นปุ่มรับงาน แต่ไม่เห็นชื่อ/เบอร์ลูกค้า
        $this->actingAs($user)->get(route('user.rider.jobs.show', $job))
            ->assertOk()
            ->assertSee('รับงานนี้')
            ->assertSee(route('user.rider.jobs.accept', $job), false)
            ->assertDontSee('0822222222');
    }

    public function test_approved_rider_without_consent_sees_consent_card(): void
    {
        $rider = $this->makeRider(['share_location_consent_at' => null]);

        $this->actingAs($rider->user)->get(route('user.rider.dashboard'))
            ->assertOk()
            ->assertSee('ยินยอมให้ลูกค้าเห็นตำแหน่งของคุณระหว่างส่งงาน')
            ->assertSee('giveConsent()', false);
    }

    public function test_job_detail_follows_the_job_through_delivery(): void
    {
        $rider = $this->makeRider();
        $job = $this->createJob();
        $user = $rider->user;
        $service = app(RiderJobService::class);

        $service->accept($job, $rider->fresh());

        $this->actingAs($user)->get(route('user.rider.jobs.show', $job))
            ->assertOk()
            ->assertSee('เริ่มเดินทางไปรับของ')
            ->assertSee('ยืนยันรับของแล้ว')
            ->assertSee(route('taladsod.rider.active-job', $job), false)
            ->assertSee('0822222222')
            ->assertDontSee('ยืนยันส่งสำเร็จ');

        $this->actingAs($user)->get(route('user.rider.dashboard'))
            ->assertOk()->assertSee('งานที่กำลังทำ')->assertSee(route('taladsod.rider.active-job', $job), false);
        $this->actingAs($user)->get(route('user.rider.jobs', ['tab' => 'current']))
            ->assertOk()->assertSee((string) $job->job_number);

        $service->markPickedUp($job->fresh(), $rider->fresh());

        $this->actingAs($user)->get(route('user.rider.jobs.show', $job))
            ->assertOk()
            ->assertSee('ยืนยันส่งสำเร็จ')
            ->assertSee('name="reason_code"', false)
            ->assertDontSee('เริ่มเดินทางไปรับของ');

        $service->deliver($job->fresh(), $rider->fresh(), UploadedFile::fake()->create('d.jpg', 80, 'image/jpeg'), null, null, false);

        $done = $job->fresh();
        $this->assertSame('completed', $done->status);

        $this->actingAs($user)->get(route('user.rider.jobs.show', $job))
            ->assertOk()
            ->assertSee('ส่งสำเร็จ!')
            ->assertSee('+฿'.RiderWebUi::money($done->rider_earnings))
            ->assertDontSee('ยืนยันส่งสำเร็จ');

        $this->actingAs($user)->get(route('user.rider.earnings', ['period' => 'today']))
            ->assertOk()->assertSee((string) $job->job_number);
        $this->actingAs($user)->get(route('user.rider.jobs', ['tab' => 'history']))
            ->assertOk()->assertSee((string) $job->job_number);
    }

    public function test_suspended_rider_dashboard_shows_banner(): void
    {
        $rider = $this->makeRider(['status' => 'suspended', 'availability' => 'offline', 'suspension_reason' => 'ส่งของช้าหลายครั้ง', 'suspended_at' => now()]);

        $this->actingAs($rider->user)->get(route('user.rider.dashboard'))
            ->assertOk()->assertSee('ถูกระงับ')->assertSee('ส่งของช้าหลายครั้ง');

        $this->actingAs($rider->user)->get(route('user.rider.jobs'))->assertOk()->assertSee('บัญชียังรับงานไม่ได้');
    }

    // =====================================================
    // เมนู
    // =====================================================

    public function test_user_menu_has_rider_orders_address_market_and_shop_entries(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $menus = collect(app(MenuService::class)->getMenuForRole('user', $user))->keyBy('id');

        foreach (['rider' => '/user/rider', 'shipping-addresses' => '/shipping-addresses', 'taladsod' => '/taladsod', 'seller-apply' => '/user/seller-apply'] as $id => $path) {
            $this->assertTrue($menus->has($id), "missing user menu {$id}");
            $this->assertStringEndsWith($path, (string) $menus[$id]['url']);
        }

        $orders = collect($menus['my-orders']['submenu'] ?? [])->pluck('url')->all();
        $this->assertContains(route('orders.index'), $orders);
        $this->assertContains(route('taladsod.orders'), $orders);
    }

    // =====================================================
    // ตัวช่วยแสดงผล (ไม่แตะ DB)
    // =====================================================

    public function test_thai_date_helper_does_not_corrupt_day_matching_year_digits(): void
    {
        // วันที่ 26 ปี 2026 เวลา 14:26 — macro thaidate() เดิมแทน "26" ทั้งหมดเป็น "69"
        $this->assertSame('26 ก.ย. 2569 14:26', RiderWebUi::date('2026-09-26T14:26:00+07:00'));
        $this->assertSame('—', RiderWebUi::date(null));
        $this->assertSame('1,250', RiderWebUi::money(1250));
        $this->assertSame('42.50', RiderWebUi::money(42.5));
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registration(array $overrides = []): array
    {
        return array_filter(array_merge([
            'full_name' => 'สมหญิง ขยันส่ง',
            'phone' => '0898765432',
            'id_card_number' => '3100200345676',
            'birth_date' => now()->subYears(30)->toDateString(),
            'address' => '12/3 ถ.เจริญกรุง เขตบางรัก กรุงเทพมหานคร 10500',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '2กค 5678',
            'pdpa_consent' => '1',
        ], $overrides), fn ($v) => $v !== null);
    }

    private function createJob(): RiderJob
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
            'cod' => 0,
            'party' => [(int) $buyer->id, (int) $seller->id],
            'customer_id' => (int) $buyer->id,
        ]);

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
            'vehicle_plate' => '1กข 1234',
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
