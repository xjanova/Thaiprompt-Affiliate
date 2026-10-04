<?php

namespace Tests\Feature\RiderR2;

use App\Models\Rider;
use App\Models\RiderHeart;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\RiderR2\Concerns\BuildsRiderSocialFixtures;
use Tests\TestCase;

/**
 * หัวใจไรเดอร์ POST /api/v1/orders/{source}/{id}/heart (ไรเดอร์รอบ 2 — เลน social) — ต้องใช้ MySQL
 *
 * - ให้ได้เฉพาะผู้ซื้อของออเดอร์ + งานไรเดอร์ส่งสำเร็จแล้ว (ไม่งั้น 409 HEART_NOT_ALLOWED)
 * - 1 ดวงต่องาน: กดซ้ำ → already = true ไม่นับเพิ่ม · กดพร้อมกัน (ชน unique) → already = true ไม่นับซ้ำ
 * - hearts_count ของไรเดอร์เพิ่มใน transaction เดียวกับแถวหัวใจ
 * - can_lock เมื่อหัวใจของคู่นี้ ≥ rider.lock_min_hearts (11)
 */
#[Group('rider')]
class RiderHeartsTest extends TestCase
{
    use BuildsRiderSocialFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Cache::flush();
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
    }

    public function test_buyer_hearts_completed_shop_job_once_and_repeat_is_idempotent(): void
    {
        $buyer = User::factory()->create();
        $rider = $this->makeRider();
        $order = $this->makeShopOrder($buyer);
        $job = $this->makeJobFor($order, $rider, $buyer);

        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/shop/{$order->id}/heart")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.already', false)
            ->assertJsonPath('data.hearts_from_me', 1)
            ->assertJsonPath('data.hearts_total', 1)
            ->assertJsonPath('data.can_lock', false);

        // กดซ้ำ (เน็ตหลุดแล้วส่งใหม่) → ผลเดิม ไม่นับเพิ่ม
        $this->postJson("/api/v1/orders/shop/{$order->id}/heart")
            ->assertOk()
            ->assertJsonPath('data.already', true)
            ->assertJsonPath('data.hearts_from_me', 1)
            ->assertJsonPath('data.hearts_total', 1);

        $this->assertSame(1, RiderHeart::where('rider_job_id', $job->id)->count());
        $this->assertSame(1, (int) $rider->fresh()->hearts_count);
    }

    public function test_concurrent_heart_hitting_unique_key_is_reported_as_already_and_not_double_counted(): void
    {
        $buyer = User::factory()->create();
        $rider = $this->makeRider();
        $order = $this->makeShopOrder($buyer);
        $job = $this->makeJobFor($order, $rider, $buyer);

        // จำลองคำขอที่สองที่วิ่งพร้อมกัน: ทันทีหลังคำขอนี้เช็คแล้วว่า "ยังไม่มีหัวใจ" อีกคำขอบันทึกสำเร็จไปก่อน
        // (แทรกหลัง query exists บน rider_hearts — ก่อน transaction ของคำขอนี้เริ่ม) → insert ของคำขอนี้ชน unique
        $raced = false;
        DB::listen(function ($query) use (&$raced, $rider, $buyer, $job) {
            if ($raced || ! str_contains($query->sql, 'rider_hearts') || ! str_contains($query->sql, 'exists')) {
                return;
            }
            $raced = true;
            DB::table('rider_hearts')->insert([
                'rider_id' => $rider->id,
                'user_id' => $buyer->id,
                'rider_job_id' => $job->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('riders')->where('id', $rider->id)->increment('hearts_count');
        });

        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/shop/{$order->id}/heart")
            ->assertOk()
            ->assertJsonPath('data.already', true)
            ->assertJsonPath('data.hearts_from_me', 1)
            ->assertJsonPath('data.hearts_total', 1);

        $this->assertTrue($raced);
        $this->assertSame(1, RiderHeart::where('rider_job_id', $job->id)->count());
        $this->assertSame(1, (int) $rider->fresh()->hearts_count, 'ชน unique ต้องไม่บวกตัวนับซ้ำ');
    }

    public function test_fresh_market_order_can_be_hearted(): void
    {
        $buyer = User::factory()->create();
        $rider = $this->makeRider();
        $order = $this->makeFreshMarketOrder($buyer);
        $this->makeJobFor($order, $rider, $buyer);

        Sanctum::actingAs($buyer);

        $this->postJson("/api/v1/orders/fresh-market/{$order->id}/heart")
            ->assertOk()
            ->assertJsonPath('data.already', false)
            ->assertJsonPath('data.hearts_total', 1);
    }

    public function test_other_user_cannot_heart_someone_elses_order(): void
    {
        $buyer = User::factory()->create();
        $rider = $this->makeRider();
        $order = $this->makeShopOrder($buyer);
        $this->makeJobFor($order, $rider, $buyer);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/orders/shop/{$order->id}/heart")
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'HEART_NOT_ALLOWED');

        // ออเดอร์ที่ไม่มีอยู่จริง → คำตอบเดียวกัน (ไม่บอกว่ามีออเดอร์นี้หรือไม่)
        $this->postJson('/api/v1/orders/shop/999999/heart')
            ->assertStatus(409)
            ->assertJsonPath('code', 'HEART_NOT_ALLOWED');

        $this->assertSame(0, RiderHeart::count());
        $this->assertSame(0, (int) $rider->fresh()->hearts_count);
    }

    public function test_cannot_heart_before_job_is_completed(): void
    {
        $buyer = User::factory()->create();
        $rider = $this->makeRider();

        foreach (['delivering', 'delivered', 'failed', 'cancelled'] as $status) {
            $order = $this->makeShopOrder($buyer);
            $this->makeJobFor($order, $rider, $buyer, $status);

            Sanctum::actingAs($buyer);
            $this->postJson("/api/v1/orders/shop/{$order->id}/heart")
                ->assertStatus(409)
                ->assertJsonPath('code', 'HEART_NOT_ALLOWED');
        }

        // ออเดอร์ส่งพัสดุ (ไม่มีงานไรเดอร์เลย)
        $parcel = $this->makeShopOrder($buyer, ['delivery_method' => 'parcel']);
        $this->postJson("/api/v1/orders/shop/{$parcel->id}/heart")
            ->assertStatus(409)
            ->assertJsonPath('code', 'HEART_NOT_ALLOWED');

        $this->assertSame(0, RiderHeart::count());
    }

    public function test_invalid_source_is_not_routed(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/orders/hotel/1/heart')->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/orders/shop/1/heart')->assertUnauthorized();
    }

    public function test_can_lock_turns_true_at_eleven_hearts_not_ten(): void
    {
        $buyer = User::factory()->create();
        $rider = $this->makeRider();

        // 9 ดวงเดิม + ดวงที่ 10 จาก API → ยังล็อกไม่ได้
        $this->giveHearts($rider, $buyer, 9);
        $order10 = $this->makeShopOrder($buyer);
        $this->makeJobFor($order10, $rider, $buyer);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/v1/orders/shop/{$order10->id}/heart")
            ->assertOk()
            ->assertJsonPath('data.hearts_from_me', 10)
            ->assertJsonPath('data.can_lock', false)
            ->assertJsonPath('data.lock_min_hearts', 11);

        // ดวงที่ 11 → ล็อกได้
        $order11 = $this->makeShopOrder($buyer);
        $this->makeJobFor($order11, $rider, $buyer);
        $this->postJson("/api/v1/orders/shop/{$order11->id}/heart")
            ->assertOk()
            ->assertJsonPath('data.hearts_from_me', 11)
            ->assertJsonPath('data.hearts_total', 11)
            ->assertJsonPath('data.can_lock', true);

        // ไรเดอร์ถูกระงับ → หัวใจครบก็ล็อกไม่ได้
        Rider::whereKey($rider->id)->update(['status' => 'suspended', 'suspended_at' => now()]);
        $this->getJson('/api/v1/riders/favorites')
            ->assertOk()
            ->assertJsonPath('data.riders.0.hearts_from_me', 11)
            ->assertJsonPath('data.riders.0.can_lock', false);
    }

    public function test_favorites_lists_hearted_riders_most_hearts_first_without_private_data(): void
    {
        $buyer = User::factory()->create();
        $few = $this->makeRider(['full_name' => 'นาย วิชัย มั่นคง']);
        $many = $this->makeRider(['full_name' => 'Somsak Prasert', 'show_on_nearby' => false]);
        $stranger = $this->makeRider();

        $this->giveHearts($few, $buyer, 2);
        $this->giveHearts($many, $buyer, 5);
        $this->giveHearts($stranger, User::factory()->create(), 3); // คนอื่นให้ ไม่ใช่ฉัน

        Sanctum::actingAs($buyer);

        $data = $this->getJson('/api/v1/riders/favorites')
            ->assertOk()
            ->assertJsonPath('data.lock_min_hearts', 11)
            ->json('data');

        $this->assertCount(2, $data['riders']);
        $this->assertSame($many->id, $data['riders'][0]['id']);
        $this->assertSame('Somsak P.', $data['riders'][0]['display_name']);
        $this->assertSame(5, $data['riders'][0]['hearts_from_me']);
        $this->assertFalse($data['riders'][0]['online'], 'ไรเดอร์ที่ปิดการแสดงตัวต้องไม่โชว์ว่าออนไลน์');
        $this->assertNotNull($data['riders'][0]['last_order_at']);

        $this->assertSame($few->id, $data['riders'][1]['id']);
        $this->assertSame('วิชัย ม.', $data['riders'][1]['display_name'], 'ตัดคำนำหน้า + ใช้อักษรแรกนามสกุล');
        $this->assertTrue($data['riders'][1]['online']);
        $this->assertSame('•กข ••34', $data['riders'][1]['plate_masked']);
        $this->assertArrayHasKey('photo_url', $data['riders'][1]);

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        foreach ([$few->phone, $many->phone, '1กข 1234', 'สมชาย ใจดี', 'last_latitude'] as $secret) {
            $this->assertStringNotContainsString((string) $secret, $json);
        }
    }

    public function test_rider_card_visible_to_related_buyer_but_not_to_stranger_when_hidden(): void
    {
        $buyer = User::factory()->create();
        $rider = $this->makeRider(['show_on_nearby' => false, 'completed_jobs' => 42]);
        $order = $this->makeShopOrder($buyer);
        $this->makeJobFor($order, $rider, $buyer);

        Sanctum::actingAs($buyer);
        $this->getJson("/api/v1/riders/{$rider->id}")
            ->assertOk()
            ->assertJsonPath('data.rider.id', $rider->id)
            ->assertJsonPath('data.rider.display_name', 'สมชาย จ.')
            ->assertJsonPath('data.rider.completed_jobs', 42)
            ->assertJsonPath('data.rider.hearts_from_me', 0)
            ->assertJsonMissingPath('data.rider.phone');

        // คนแปลกหน้า + ไรเดอร์ปิดการแสดงตัว → 404
        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/v1/riders/{$rider->id}")
            ->assertNotFound()
            ->assertJsonPath('code', 'RIDER_NOT_FOUND');

        // เปิดการแสดงตัว + ออนไลน์ → คนแปลกหน้าเห็นการ์ดได้ (แตะหมุดบนแผนที่)
        $rider->forceFill(['show_on_nearby' => true])->save();
        $this->getJson("/api/v1/riders/{$rider->id}")->assertOk();
    }
}
