<?php

namespace Tests\Feature\V4;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreshMarketService;
use App\Services\RiderDispatchService;
use App\Services\RiderJobService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * หน้าเว็บที่แก้ในรอบแก้ข้อรีวิว (fix2a) — ต้องใช้ MySQL
 *
 * - หน้าติดตามไรเดอร์: งานรอไรเดอร์ (pending) ยัง poll ต่อ (watch) + มีปุ่มตรวจสอบสถานะ
 * - หน้าส่งงานไรเดอร์: ซ่อนปุ่มลอย Theme Studio / Eve ไม่ให้ทับแถบปุ่มหลัก
 * - สวิตช์แชร์ตำแหน่ง/ส่งตำแหน่งสด: ส่ง element เข้าไปเพื่อตั้งสวิตช์กลับเมื่อทำไม่สำเร็จ
 * - แบนเนอร์หน้าแรกตลาดสด + กรอบมือถือในแอดมิน: สไลด์ซ้อนช่องเดียวกัน (ไม่กระตุก)
 * ทุกหน้าต้องตอบ 200 และใช้ธีม V4 (tp-card)
 */
class Fix2aReviewPagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $sellerUser;

    protected FreshMarketSeller $shop;

    protected FreshMarketListing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('public');
        Storage::fake('local');
        Cache::flush();
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 0,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => true,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();

        Setting::set('pricing.fresh_market_gp_rate', '0', 'float', 'pricing');
        Setting::set('fresh_market.auto_approve_sellers', '1', 'boolean', 'fresh_market');
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
        Setting::set('rider.dispatch_mode', 'broadcast', 'string', 'rider');

        $this->buyer = User::factory()->create(['name' => 'ผู้ซื้อทดสอบ']);
        $this->sellerUser = User::factory()->create(['name' => 'ลุงรถเข็น']);
        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $this->sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $this->shop = FreshMarketSeller::create([
            'user_id' => $this->sellerUser->id,
            'shop_name' => 'รถเข็นลุงหนวด',
            'phone' => '0812345678',
            'address' => '99 ถนนทดสอบ',
            'latitude' => 13.7291,
            'longitude' => 100.5210,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        $this->shop->forceFill([
            'is_mobile' => true,
            'is_open' => true,
            'opened_at' => now()->subHour(),
            'closes_at' => now()->addHours(4),
            'current_latitude' => 13.7400,
            'current_longitude' => 100.5300,
            'location_label' => 'ตลาดนัดหน้าโรงเรียน',
            'location_updated_at' => now(),
            'live_location_sharing' => true,
        ])->save();

        $this->listing = FreshMarketListing::create([
            'seller_id' => $this->shop->id,
            'title' => 'ข้าวเหนียวหมูปิ้ง',
            'price' => 30,
            'unit' => 'ชุด',
            'quantity_available' => 0,
            'track_stock' => false,
            'latitude' => 13.7400,
            'longitude' => 100.5300,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }

    public function test_tracking_page_keeps_polling_while_waiting_for_a_rider(): void
    {
        [, $job] = $this->riderOrder();
        $this->assertSame('pending', $job->status);

        $html = $this->assertV4($this->get(route('taladsod.track.show', $job->tracking_token)));

        // Js::from เข้ารหัสเครื่องหมายคำพูดเป็น escape แบบ unicode (ดู jsPair)
        $this->assertStringContainsString($this->jsPair('active', 'false'), $html);
        $this->assertStringContainsString($this->jsPair('watch', 'true'), $html);
        $this->assertStringContainsString('ตรวจสอบสถานะอีกครั้ง', $html);
        $this->assertStringContainsString('if (this.watch) { this.schedule(); }', $html);

        // ปลายทาง poll ยังตอบงาน pending ได้ (ไม่ใช่ 404)
        $this->getJson(route('taladsod.track.location', $job->tracking_token))->assertOk()
            ->assertJsonPath('job_status', 'pending')
            ->assertJsonPath('is_active', false);
    }

    public function test_tracking_page_for_finished_job_stops_watching(): void
    {
        [, $job] = $this->riderOrder();
        app(RiderDispatchService::class)->cancelJobsForSource(FreshMarketOrder::findOrFail($job->source_id), 'admin', 'ทดสอบ');

        $response = $this->get(route('taladsod.track.show', $job->tracking_token));

        if ($response->getStatusCode() === 200) {
            $this->assertStringContainsString($this->jsPair('watch', 'false'), (string) $response->getContent());
        } else {
            $response->assertNotFound();
        }
    }

    public function test_rider_active_job_hides_floating_buttons_over_action_bar(): void
    {
        [, $job] = $this->riderOrder();
        $rider = $this->makeRider();
        app(RiderJobService::class)->accept($job, $rider);

        $html = $this->assertV4($this->actingAs($rider->user)->get(route('taladsod.rider.active-job', $job)));

        $this->assertStringContainsString('.tp-studio-fab, .eve-w { display: none !important; }', $html);
        $this->assertStringContainsString('ts-bottom-bar', $html);
    }

    public function test_share_switches_pass_element_to_revert_on_failure(): void
    {
        [$order, $job] = $this->riderOrder();
        app(RiderJobService::class)->accept($job, $this->makeRider());

        $html = $this->assertV4($this->actingAs($this->buyer)->get(route('taladsod.orders.show', $order)));
        $this->assertStringContainsString('toggleShare($event.target)', $html);
        $this->assertStringContainsString('x-ref="shareBox"', $html);

        $html = $this->assertV4($this->actingAs($this->buyer)->get(route('taladsod.track.show', $job->fresh()->tracking_token)));
        $this->assertStringContainsString('toggleShare($event.target)', $html);

        $html = $this->assertV4($this->actingAs($this->sellerUser)->get(route('taladsod.seller.dashboard')));
        $this->assertStringContainsString('setLive($event.target)', $html);
    }

    public function test_home_banner_slides_stack_in_one_cell(): void
    {
        $html = $this->assertV4($this->get(route('taladsod.home')));

        $this->assertStringContainsString('ts-hero-stack', $html);
        $this->assertStringContainsString('.ts-hero-stack > .ts-hero { grid-area: 1 / 1;', $html);
        $this->assertStringContainsString("pointerType === 'mouse'", $html);
        $this->assertStringNotContainsString('x-on:mouseenter="stop()"', $html);
    }

    public function test_admin_banner_phone_preview_stacks_slides(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $html = $this->assertV4($this->actingAs($admin)->get(route('admin.app-banners.index')), false);
        $this->assertStringContainsString('class="ab-slides"', $html);
        $this->assertStringContainsString('.ab-slides > .ab-slide { grid-area:1 / 1;', $html);
    }

    // =====================================================

    /**
     * ออเดอร์ส่งด้วยไรเดอร์ที่ร้านรับแล้ว + งานไรเดอร์รอคนรับ (pending)
     *
     * @return array{0: FreshMarketOrder, 1: RiderJob}
     */
    private function riderOrder(): array
    {
        $service = app(FreshMarketService::class);
        $order = $service->createOrder($this->buyer, $this->listing->fresh(), [
            'quantity' => 2,
            'delivery_type' => 'rider',
            'payment_method' => 'wallet',
            'buyer_latitude' => 13.7350,
            'buyer_longitude' => 100.5250,
            'delivery_address' => '88 ถนนเจริญกรุง แขวงบางรัก',
        ]);
        $service->applyAction($order, 'accept', 'seller', $this->sellerUser);

        $job = app(RiderDispatchService::class)->createJobForSource($order->fresh(), 'fresh_market')->fresh();

        return [$order->fresh(), $job];
    }

    private function makeRider(): Rider
    {
        $user = User::factory()->create(['name' => 'ไรเดอร์ทดสอบ']);
        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id,
            'full_name' => 'สมชาย ส่งไว',
            'phone' => '0899999999',
            'status' => 'approved',
            'availability' => 'online',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => 'กข 1234',
            'gps_permission_granted' => true,
            'last_latitude' => 13.7401,
            'last_longitude' => 100.5301,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ])->save();
        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }

    /**
     * คู่ key:value ใน config ที่ส่งผ่าน Js::from (เครื่องหมายคำพูดถูกเข้ารหัสเป็น backslash-u0022)
     */
    private function jsPair(string $key, string $value): string
    {
        $quote = chr(92).'u0022';

        return $quote.$key.$quote.':'.$value;
    }

    private function assertV4($response, bool $taladsodScope = true): string
    {
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringContainsString('tp-card', $html, 'หน้าไม่ได้ใช้ธีม V4');
        if ($taladsodScope) {
            $this->assertStringContainsString('ts-scope', $html, 'หน้าไม่มีชุด UI ตลาดสด');
        }

        return $html;
    }
}
