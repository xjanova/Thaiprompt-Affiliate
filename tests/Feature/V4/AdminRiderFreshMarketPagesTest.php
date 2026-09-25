<?php

namespace Tests\Feature\V4;

use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\RiderLocation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * หน้าแอดมิน V4 ของไรเดอร์ / งานไรเดอร์ / ตลาดสด — เปิดได้จริง (200) และใช้ธีม V4 (มี tp-card)
 *
 * ครอบคลุม audit RIDER-25/26, FM-16/17, GAP-06/11/14/17: ทุกหน้าย้ายเป็น layouts.admin-v4
 * ทั้งแบบไม่มีข้อมูล (empty state) และมีข้อมูลจริงครบทุกความสัมพันธ์ + action สำคัญบางตัว
 * ต้องใช้ MySQL (RefreshDatabase)
 */
#[Group('v4')]
class AdminRiderFreshMarketPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();
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
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * หน้าแบบไม่มีข้อมูลเลยต้องเปิดได้ (ไม่มี 500 จาก null / หารศูนย์)
     */
    public function test_list_pages_render_empty_state_on_v4(): void
    {
        foreach ([
            'admin.riders.index', 'admin.riders.pending', 'admin.riders.map', 'admin.riders.monitor', 'admin.riders.settings',
            'admin.rider-jobs.index', 'admin.rider-jobs.statistics',
            'admin.fresh-market.dashboard', 'admin.fresh-market.settings', 'admin.fresh-market.categories', 'admin.fresh-market.sellers',
            'admin.fresh-market.listings', 'admin.fresh-market.orders', 'admin.fresh-market.commissions', 'admin.fresh-market.test-line',
        ] as $routeName) {
            $this->actingAs($this->admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('tp-card', false);
        }
    }

    /**
     * ทุกหน้า (รวมหน้ารายละเอียด) เปิดได้เมื่อมีข้อมูลครบ
     */
    public function test_every_page_renders_with_data_on_v4(): void
    {
        [$pendingRider, $activeRider] = $this->makeRiders();
        [$pendingJob, $activeJob] = $this->makeJobs($activeRider);
        [$seller, $listing, $order, $category] = $this->makeFreshMarket($activeJob);

        $pages = [
            route('admin.riders.index'),
            route('admin.riders.index', ['status' => 'pending', 'needs_review' => 1]),
            route('admin.riders.pending'),
            route('admin.riders.show', $pendingRider),
            route('admin.riders.show', $activeRider),
            route('admin.riders.map'),
            route('admin.riders.monitor'),
            route('admin.riders.settings'),
            route('admin.riders.locations', $activeRider),
            route('admin.riders.playback', $activeRider),
            route('admin.rider-jobs.index'),
            route('admin.rider-jobs.index', ['status' => 'picking_up', 'manual_needed' => 1]),
            route('admin.rider-jobs.show', $pendingJob),
            route('admin.rider-jobs.show', $activeJob),
            route('admin.rider-jobs.statistics'),
            route('admin.rider-jobs.statistics', ['period' => 'monthly']),
            route('admin.fresh-market.dashboard'),
            route('admin.fresh-market.settings'),
            route('admin.fresh-market.categories'),
            route('admin.fresh-market.sellers'),
            route('admin.fresh-market.sellers', ['status' => 'unverified']),
            route('admin.fresh-market.sellers.show', $seller),
            route('admin.fresh-market.listings'),
            route('admin.fresh-market.listings', ['seller_id' => $seller->id, 'status' => 'active']),
            route('admin.fresh-market.listings.show', $listing),
            route('admin.fresh-market.orders'),
            route('admin.fresh-market.orders', ['status' => 'ready_for_pickup', 'payment_status' => 'paid', 'delivery_type' => 'rider']),
            route('admin.fresh-market.orders.show', $order),
            route('admin.fresh-market.commissions'),
            route('admin.fresh-market.test-line'),
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $this->assertSame(200, $response->getStatusCode(), 'หน้า '.$url.' ตอบ '.$response->getStatusCode());
            $response->assertSee('tp-card', false);
        }

        // หน้ารายละเอียดแสดงข้อมูลจริง (ไม่ใช่คอลัมน์ผิดชื่อ)
        $this->actingAs($this->admin)->get(route('admin.fresh-market.sellers.show', $seller))->assertSee('ร้านผักทดสอบ');
        $this->actingAs($this->admin)->get(route('admin.fresh-market.orders.show', $order))->assertSee($order->order_number);
        $this->actingAs($this->admin)->get(route('admin.rider-jobs.show', $activeJob))->assertSee($activeJob->job_number);

        // ตัวเลือกสถานะงานใช้ enum จริง (ไม่มี in_transit แล้ว)
        $this->actingAs($this->admin)->get(route('admin.rider-jobs.index'))
            ->assertSee('value="picking_up"', false)
            ->assertSee('value="failed"', false)
            ->assertDontSee('in_transit', false);

        // secret ของ LINE ไม่ถูกพิมพ์ลงหน้าเว็บ
        $this->actingAs($this->admin)->get(route('admin.fresh-market.settings'))
            ->assertDontSee('super-secret-line-token-value', false);

        // ออเดอร์หลายรายการ: หน้ารายละเอียดแสดงทุกรายการพร้อมตัวเลือก/หมายเหตุ
        if (class_exists(\App\Models\FreshMarketOrderItem::class) && Schema::hasTable('fresh_market_order_items')) {
            \App\Models\FreshMarketOrderItem::create([
                'order_id' => $order->id,
                'listing_id' => $listing->id,
                'title' => 'ผัดกะเพราราดข้าว',
                'unit' => 'จาน',
                'quantity' => 2,
                'base_price' => 50,
                'options_price' => 20,
                'unit_price' => 70,
                'line_total' => 140,
                'selected_options' => [['name' => 'กุ้ง', 'price_delta' => 20]],
                'note' => 'ไม่เผ็ด',
            ]);

            $this->actingAs($this->admin)->get(route('admin.fresh-market.orders.show', $order))
                ->assertOk()
                ->assertSee('ผัดกะเพราราดข้าว')
                ->assertSee('กุ้ง')
                ->assertSee('ไม่เผ็ด');
            $this->actingAs($this->admin)->get(route('admin.fresh-market.orders'))->assertOk()->assertSee('ผัดกะเพราราดข้าว');
        }

        // สินค้าที่มีกลุ่มตัวเลือก (เมนูเปิดตัวจาก migration) แสดงตัวเลือกในหน้ารายละเอียด
        if (method_exists(FreshMarketListing::class, 'optionGroups') && Schema::hasTable('fresh_market_listing_option_groups')) {
            $group = \App\Models\FreshMarketListingOptionGroup::create([
                'listing_id' => $listing->id,
                'name' => 'เลือกเนื้อสัตว์',
                'selection_type' => 'single',
                'is_required' => true,
                'min_select' => 1,
                'max_select' => 1,
            ]);
            \App\Models\FreshMarketListingOption::create(['group_id' => $group->id, 'listing_id' => $listing->id, 'name' => 'หมึก', 'price_delta' => 10, 'is_available' => true]);

            $this->actingAs($this->admin)->get(route('admin.fresh-market.listings.show', $listing))
                ->assertOk()
                ->assertSee('ตัวเลือกสินค้า')
                ->assertSee('เลือกเนื้อสัตว์')
                ->assertSee('+฿10.00');
        }

        // มอนิเตอร์สดดึง JSON ได้
        $this->actingAs($this->admin)->getJson(route('admin.riders.dispatch-monitor'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.stats.pending', 1);
    }

    /**
     * ลากเรียงหมวดหมู่: หน้าใหม่ส่ง order เป็นอาร์เรย์ของ id (ของเดิมส่ง object จน validate ไม่ผ่าน)
     */
    public function test_category_reorder_payload_from_v4_page_is_accepted(): void
    {
        $a = FreshMarketCategory::create(['name' => 'ผักสด', 'slug' => 'veg-'.uniqid(), 'sort_order' => 1, 'is_active' => true]);
        $b = FreshMarketCategory::create(['name' => 'ผลไม้', 'slug' => 'fruit-'.uniqid(), 'sort_order' => 2, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.fresh-market.categories.reorder'), ['order' => [$b->id, $a->id]])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, (int) $b->fresh()->sort_order);
        $this->assertSame(2, (int) $a->fresh()->sort_order);

        // ปุ่มสลับเปิด/ปิด (GAP-06) ทำงาน
        $this->actingAs($this->admin)
            ->patch(route('admin.fresh-market.categories.toggle', $a))
            ->assertRedirect(route('admin.fresh-market.categories'));
        $this->assertFalse((bool) $a->fresh()->is_active);
    }

    /**
     * ฟอร์มปฏิเสธไรเดอร์ (ไม่ใช่ AJAX) บันทึกได้จริง — GAP-11
     */
    public function test_reject_form_post_from_v4_modal_records_rejection(): void
    {
        [$pendingRider] = $this->makeRiders();

        $this->actingAs($this->admin)
            ->from(route('admin.riders.pending'))
            ->post(route('admin.riders.reject', $pendingRider), ['reason' => 'รูปบัตรประชาชนไม่ชัด'])
            ->assertRedirect(route('admin.riders.pending'))
            ->assertSessionHas('success');

        $fresh = $pendingRider->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertNotNull($fresh->rejected_at);
        $this->assertSame((int) $this->admin->id, (int) $fresh->rejected_by);
    }

    /**
     * ฟอร์มตั้งค่าค่าส่ง (hidden 0 + checkbox) บันทึกผ่าน Setting rider.*
     */
    public function test_rider_settings_form_saves_fee_fields(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.riders.settings'))
            ->post(route('admin.riders.settings.update'), ['settings' => [
                'base_fee' => '35',
                'per_km_fee' => '12',
                'rider_share_percent' => '85',
                'require_deposit' => '0',
                'dispatch_mode' => 'broadcast',
            ]])
            ->assertRedirect(route('admin.riders.settings'))
            ->assertSessionHas('success');

        $this->assertSame(35.0, (float) Setting::get('rider.base_fee'));
        $this->assertSame(85.0, (float) Setting::get('rider.rider_share_percent'));
    }

    // =====================================================
    // ข้อมูลทดสอบ
    // =====================================================

    /**
     * @return array{0: Rider, 1: Rider}
     */
    private function makeRiders(): array
    {
        $pending = $this->makeRider([
            'status' => 'pending',
            'availability' => 'offline',
            'id_card_image' => 'rider-documents/1/id_card.jpg',
            'last_latitude' => null,
            'last_longitude' => null,
            'last_location_update' => null,
        ]);

        $active = $this->makeRider([
            'status' => 'approved',
            'availability' => 'busy',
            'profile_image' => 'rider-documents/2/profile.jpg',
            'documents_changed_at' => now()->subHour(),
        ]);

        foreach (range(1, 4) as $i) {
            $location = new RiderLocation;
            $location->forceFill([
                'rider_id' => $active->id,
                'latitude' => 13.7291 + $i * 0.001,
                'longitude' => 100.5210 + $i * 0.001,
                'speed' => 20 + $i,
                'battery_level' => 80 - $i,
                'recorded_at' => now()->subMinutes(30 - $i * 5),
            ])->save();
        }

        return [$pending, $active];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRider(array $overrides): Rider
    {
        $user = User::factory()->create(['role' => 'user']);

        $rider = new Rider;
        $rider->forceFill(array_merge([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ทดสอบ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'id_card_number' => '3100200345676',
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => '1กข 1234',
            'rider_type' => 'delivery',
            'last_latitude' => 13.7300,
            'last_longitude' => 100.5220,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now()->subDay(),
        ], $overrides))->save();

        return $rider->fresh();
    }

    /**
     * @return array{0: RiderJob, 1: RiderJob}
     */
    private function makeJobs(Rider $rider): array
    {
        $customer = User::factory()->create();

        $base = [
            'customer_id' => $customer->id,
            'job_type' => 'fresh_market',
            'title' => 'ส่งผักตลาดสด',
            'description' => 'ผักบุ้ง 2 กำ',
            'pickup_address' => 'ตลาดบางรัก กรุงเทพฯ',
            'pickup_contact_name' => 'ร้านป้าแดง',
            'pickup_contact_phone' => '0811111111',
            'pickup_latitude' => 13.7291,
            'pickup_longitude' => 100.5210,
            'delivery_address' => '88/8 ซ.เจริญกรุง 30 เขตบางรัก กรุงเทพมหานคร 10500',
            'delivery_contact_name' => 'คุณลูกค้า',
            'delivery_contact_phone' => '0822222222',
            'delivery_latitude' => 13.7400,
            'delivery_longitude' => 100.5300,
            'distance_km' => 2.5,
            'base_fee' => 30,
            'distance_fee' => 5,
            'total_fee' => 35,
            'rider_earnings' => 28,
            'platform_fee' => 7,
            'dispatch_round' => 2,
            'dispatch_radius_km' => 7.5,
            'candidate_riders' => [$rider->id],
            'dispatch_attempts' => [['rider_id' => $rider->id, 'status' => 'rejected', 'responded_at' => now()->toIso8601String()]],
        ];

        $pending = new RiderJob;
        $pending->forceFill(array_merge($base, [
            'rider_id' => null,
            'status' => 'pending',
            'dispatch_type' => 'manual_needed',
            'cod_amount' => 120,
        ]))->save();

        $active = new RiderJob;
        $active->forceFill(array_merge($base, [
            'rider_id' => $rider->id,
            'status' => 'picking_up',
            'dispatch_type' => 'broadcast',
            'accepted_at' => now()->subMinutes(10),
            'gps_active' => true,
        ]))->save();

        return [$pending->fresh(), $active->fresh()];
    }

    /**
     * @return array{0: FreshMarketSeller, 1: FreshMarketListing, 2: FreshMarketOrder, 3: FreshMarketCategory}
     */
    private function makeFreshMarket(RiderJob $job): array
    {
        $sellerUser = User::factory()->create(['name' => 'เจ้าของร้าน']);
        $buyer = User::factory()->create(['name' => 'ผู้ซื้อทดสอบ']);

        $category = FreshMarketCategory::create(['name' => 'ผักสด', 'slug' => 'veg-'.uniqid(), 'icon' => '🥬', 'sort_order' => 1, 'is_active' => true]);

        $seller = FreshMarketSeller::create([
            'user_id' => $sellerUser->id,
            'shop_name' => 'ร้านผักทดสอบ',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => false,
            'subscription_type' => 'free',
        ]);

        $listing = FreshMarketListing::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'ผักบุ้งจีน',
            'description' => 'ผักบุ้งสดจากสวน',
            'price' => 25,
            'compare_at_price' => 30,
            'unit' => 'กำ',
            'quantity_available' => 10,
            'images' => ['https://example.test/a.jpg', 'https://example.test/b.jpg'],
            'tags' => ['ผัก', 'ออร์แกนิก'],
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        $order = FreshMarketOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'listing_id' => $listing->id,
            'quantity' => 2,
            'unit_price' => 25,
            'total_amount' => 50,
            'platform_fee' => 5,
            'gp_rate' => 10,
            'seller_earning' => 45,
            'delivery_type' => 'rider',
            'delivery_fee' => 35,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'order_status' => 'ready',
            'escrow_status' => 'held',
            'buyer_latitude' => 13.7400,
            'buyer_longitude' => 100.5300,
            'delivery_address' => '88/8 ซ.เจริญกรุง 30',
            'rider_job_id' => $job->id,
            'status_history' => [
                ['at' => now()->subMinutes(20)->toIso8601String(), 'action' => 'create', 'to' => 'pending', 'by' => 'buyer'],
                ['at' => now()->subMinutes(10)->toIso8601String(), 'action' => 'accept', 'from' => 'pending', 'to' => 'accepted', 'by' => 'seller'],
            ],
        ]);

        // ค่าลับของ LINE ต้องไม่หลุดไปอยู่ใน HTML
        FreshMarketSetting::getSettings()->setLineCredentials('super-secret-line-token-value', 'super-secret-line-token-value');

        return [$seller, $listing, $order, $category];
    }
}
