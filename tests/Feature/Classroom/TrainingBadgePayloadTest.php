<?php

namespace Tests\Feature\Classroom;

use App\Models\ClassroomCertification;
use App\Models\ClassroomCourse;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\Classroom\TrainingBadge;
use App\Services\FreshMarketShopPresenceService;
use App\Services\Rider\RiderSocialService;
use App\Services\Shop\ShopPresenter;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Classroom\Concerns\BuildsClassroomFixtures;
use Tests\Feature\RiderR2\HandoverTestCase;

/**
 * 🎓 ป้าย "ผ่านการอบรม" ใน payload ที่แอปอ่าน (ข้างป้าย eKYC) — ต้องใช้ MySQL
 *
 * - โปรไฟล์ตัวเอง: GET /me · GET/PUT /profile → training_badges {seller, rider, buyer}
 * - ไรเดอร์ที่ผู้ซื้อเห็น: handover (rider card) · ShopPresenter::riderSummary · RiderJob::toApiDetail · personCard · ตลาดสด rider_job
 * - ร้าน: GET /mobile/stores/{id} · ตลาดสด shopProfile · seller ในออเดอร์ตลาดสด
 * - ผู้ซื้อที่ร้าน/ไรเดอร์เห็น: handover (buyer card) · GET /seller/orders/{id} customer · ออเดอร์ตลาดสดฝั่งร้าน
 * - ยังไม่ผ่าน = null (คีย์มีเสมอ) · รายการไม่ยิง query ต่อแถว (prime)
 */
#[Group('classroom')]
class TrainingBadgePayloadTest extends HandoverTestCase
{
    use BuildsClassroomFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetClassroom();
        foreach (ClassroomCourse::ROLES as $role) {
            $this->makeCourse($role, ['rules_version' => '2026-10']);
        }
    }

    private function certify(User $user, string $role, string $version = '2026-10'): void
    {
        ClassroomCertification::create([
            'user_id' => $user->id,
            'role' => $role,
            'course_id' => ClassroomCourse::activeForRole($role)?->id,
            'rules_version' => $version,
            'points_awarded' => 10,
            'certified_at' => now(),
        ]);
        TrainingBadge::forget($user->id);
    }

    private static function badge(string $role, bool $refresh = false): array
    {
        return ['certified' => true, 'role' => $role, 'label' => TrainingBadge::label($role), 'needs_refresh' => $refresh];
    }

    public function test_own_profile_payloads_carry_training_badges_for_every_role(): void
    {
        $user = $this->makeUser('ผู้ซื้อทดสอบ');
        $this->certify($user, 'buyer');
        $this->certify($user, 'seller', '2026-01');

        Sanctum::actingAs($user);
        $expected = ['seller' => self::badge('seller', true), 'rider' => null, 'buyer' => self::badge('buyer')];

        $this->assertSame($expected, $this->getJson('/api/v1/me')->assertOk()->json('data.training_badges'));
        $this->assertSame($expected, $this->getJson('/api/v1/profile')->assertOk()->json('data.training_badges'));
        $this->assertSame($expected, $this->putJson('/api/v1/profile', ['name' => 'ชื่อใหม่'])->assertOk()->json('data.training_badges'));

        $stranger = $this->makeUser('ยังไม่ผ่าน');
        Sanctum::actingAs($stranger);
        $this->assertSame(['seller' => null, 'rider' => null, 'buyer' => null], $this->getJson('/api/v1/me')->json('data.training_badges'));
    }

    public function test_rider_and_buyer_cards_in_shop_order_payloads(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $job = $this->makeHandoverJob($order, $rider);
        $this->certify($rider->user, 'rider');
        $this->certify($buyer, 'buyer');

        // ผู้ซื้อเห็นการ์ดไรเดอร์ / ไรเดอร์เห็นการ์ดผู้ซื้อ (ส่งมอบ)
        $this->buyerGet($buyer, $order)->assertOk()
            ->assertJsonPath('data.rider.verified', true)
            ->assertJsonPath('data.rider.training_badge', self::badge('rider'));
        $this->riderGet($rider, $job)->assertOk()
            ->assertJsonPath('data.buyer.training_badge', self::badge('buyer'));

        // การ์ด "ไรเดอร์กำลังมา" ในออเดอร์ร้านค้า + รายละเอียดงาน
        $summary = ShopPresenter::riderSummary($order->fresh());
        $this->assertSame(self::badge('rider'), $summary['rider']['training_badge']);
        $this->assertSame(self::badge('rider'), $job->fresh()->toApiDetail()['rider']['training_badge']);
        $this->assertSame(self::badge('rider'), app(RiderSocialService::class)->personCard($rider->fresh('user'), $buyer)['training_badge']);

        // ร้านเห็นผู้ซื้อในออเดอร์ (แอปร้านค้า)
        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/seller/orders/'.$order->id)->assertOk()
            ->assertJsonPath('data.customer.training_badge', self::badge('buyer'))
            ->assertJsonPath('data.rider.rider.training_badge', self::badge('rider'));
    }

    public function test_seller_order_list_and_chat_carry_customer_training_badge_without_n_plus_one(): void
    {
        [$order, $buyer, $seller] = $this->makeDeferredShopOrder();
        $otherBuyer = $this->makeUser('ผู้ซื้อคนที่สอง');
        $product = $this->makeProduct($seller, 80);
        $second = $this->makePaidOrder($otherBuyer, [[$product, 1]], 40, ['status' => 'paid', 'payment_method' => 'wallet', 'delivery_method' => 'rider']);
        $this->certify($buyer, 'buyer');
        TrainingBadge::forget();

        Sanctum::actingAs($seller);
        $certQueries = 0;
        DB::listen(function ($query) use (&$certQueries) {
            if (str_contains($query->sql, 'classroom_certifications')) {
                $certQueries++;
            }
        });

        $rows = collect($this->getJson('/api/v1/seller/orders')->assertOk()->json('data.orders'))->keyBy('id');
        $this->assertSame(self::badge('buyer'), $rows[$order->id]['customer_training_badge']);
        $this->assertArrayHasKey('customer_training_badge', $rows[$second->id]);
        $this->assertNull($rows[$second->id]['customer_training_badge']);
        $this->assertSame(1, $certQueries, 'ป้ายของทุกแถวต้องโหลดด้วย query เดียว');

        $this->getJson('/api/v1/seller/orders/'.$order->id.'/messages')->assertOk()
            ->assertJsonPath('data.chat.customer_training_badge', self::badge('buyer'));
    }

    public function test_uncertified_people_get_null_badges_without_breaking_existing_keys(): void
    {
        [$order, $buyer] = $this->makeDeferredShopOrder();
        $rider = $this->makeRider();
        $this->makeHandoverJob($order, $rider);

        $this->buyerGet($buyer, $order)->assertOk()
            ->assertJsonPath('data.rider.verified', true)
            ->assertJsonPath('data.rider.training_badge', null);

        $summary = ShopPresenter::riderSummary($order->fresh());
        $this->assertArrayHasKey('training_badge', $summary['rider']);
        $this->assertNull($summary['rider']['training_badge']);
        $this->assertTrue($summary['rider']['verified']);
    }

    public function test_store_detail_carries_owner_training_badge(): void
    {
        $seller = $this->makeSeller();
        $store = VendorStore::query()->where('user_id', $seller->id)->firstOrFail();

        Sanctum::actingAs($this->makeUser('ผู้ชมร้าน')); // GET /mobile/stores/{id} อยู่ใต้ auth:sanctum
        $this->getJson('/api/v1/mobile/stores/'.$store->id)->assertOk()
            ->assertJsonPath('data.owner_verified', false)
            ->assertJsonPath('data.training_badge', null);

        $this->certify($seller, 'seller');
        $this->assertSame(self::badge('seller'), ShopPresenter::store($store->fresh())['training_badge']);
    }

    public function test_fresh_market_shop_and_order_payloads(): void
    {
        FreshMarketSetting::clearCache();
        $owner = $this->makeUser('เจ้าของร้านตลาดสด');
        $shop = FreshMarketSeller::create([
            'user_id' => $owner->id,
            'shop_name' => 'ร้านป้าทดสอบ',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.75,
            'longitude' => 100.5,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        $buyer = $this->makeUser('ผู้ซื้อตลาดสด');
        $rider = $this->makeRider();

        $order = FreshMarketOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $shop->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total_amount' => 100,
            'platform_fee' => 10,
            'gp_rate' => 10,
            'seller_earning' => 90,
            'delivery_type' => 'rider',
            'delivery_fee' => 40,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'order_status' => 'delivering',
        ]);
        $job = new RiderJob;
        $job->forceFill([
            'rider_id' => $rider->id,
            'job_type' => 'fresh_market',
            'source_type' => $order->getMorphClass(),
            'source_id' => $order->id,
            'title' => 'ส่งออเดอร์ตลาดสด',
            'pickup_address' => 'ตลาดทดสอบ',
            'pickup_latitude' => 13.75,
            'pickup_longitude' => 100.5,
            'delivery_address' => 'บ้านลูกค้า',
            'delivery_latitude' => 13.76,
            'delivery_longitude' => 100.51,
            'base_fee' => 30,
            'distance_fee' => 10,
            'total_fee' => 40,
            'rider_earnings' => 32,
            'platform_fee' => 8,
            'cod_amount' => 0,
            'status' => 'delivering',
        ])->save();
        $order->forceFill(['rider_job_id' => $job->id])->saveQuietly();

        $this->certify($owner, 'seller');
        $this->certify($buyer, 'buyer');
        $this->certify($rider->user, 'rider');

        $sellerView = $order->fresh()->toApiArray('seller');
        $this->assertSame(self::badge('seller'), $sellerView['seller']['training_badge']);
        $this->assertSame(self::badge('buyer'), $sellerView['buyer']['training_badge']);
        $this->assertSame(self::badge('rider'), $sellerView['rider_job']['training_badge']);

        $buyerView = $order->fresh()->toApiArray('buyer');
        $this->assertSame(self::badge('rider'), $buyerView['rider_job']['training_badge']);
        $this->assertArrayNotHasKey('buyer', $buyerView);

        $profile = app(FreshMarketShopPresenceService::class)->shopProfile($shop->fresh());
        $this->assertSame(self::badge('seller'), $profile['training_badge']);

        // รายการออเดอร์ของผู้ซื้อผ่าน API — ป้ายอยู่ครบ
        Sanctum::actingAs($buyer);
        $this->getJson('/api/v1/fresh-market/orders')->assertOk()
            ->assertJsonPath('data.0.rider_job.training_badge', self::badge('rider'))
            ->assertJsonPath('data.0.seller.training_badge', self::badge('seller'));
    }

    public function test_prime_loads_many_users_with_one_query(): void
    {
        $users = collect(range(1, 6))->map(fn ($i) => $this->makeUser('คนที่ '.$i));
        $this->certify($users[0], 'rider');
        $this->certify($users[3], 'seller');
        TrainingBadge::forget();

        TrainingBadge::prime($users->pluck('id'));
        TrainingBadge::for($users[0], 'rider'); // โหลดรุ่นกติกาปัจจุบันครั้งเดียว

        DB::enableQueryLog();
        foreach ($users as $user) {
            TrainingBadge::allFor((int) $user->id);
        }
        $this->assertCount(0, DB::getQueryLog(), 'หลัง prime แล้วต้องไม่ยิง query ต่อคน');
        DB::disableQueryLog();

        $this->assertSame(self::badge('rider'), TrainingBadge::for((int) $users[0]->id, 'rider'));
        $this->assertSame(self::badge('seller'), TrainingBadge::for((int) $users[3]->id, 'seller'));
        $this->assertNull(TrainingBadge::for((int) $users[1]->id, 'rider'));
        $this->assertNull(TrainingBadge::for(null, 'rider'));
        $this->assertNull(TrainingBadge::for($users[0], 'admin'));
    }

    public function test_blade_component_renders_navy_gold_seal_only_when_certified(): void
    {
        $user = $this->makeUser('ผู้ขายเว็บ');

        $this->assertSame('', trim(Blade::render('<x-training-badge :user="$u" role="seller" />', ['u' => $user])));

        $this->certify($user, 'seller');
        $html = Blade::render('<x-training-badge :user="$u" role="seller" label />', ['u' => $user]);
        $this->assertStringContainsString('data-training-badge="seller"', $html);
        $this->assertStringContainsString('#042C53', $html);
        $this->assertStringContainsString('#EF9F27', $html);
        $this->assertStringContainsString('#FAC775', $html);
        $this->assertStringContainsString('ผ่านการอบรมผู้ขาย', $html);
    }
}
