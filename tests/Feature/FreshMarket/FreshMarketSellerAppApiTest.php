<?php

namespace Tests\Feature\FreshMarket;

use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketListingOptionGroup;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FreshMarketSellerEarningsService;
use App\Services\FreshMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * ร้านตลาดสดในแอป (ของที่เดิมต้องเปิดเว็บ) — ต้องใช้ MySQL
 *
 * - สมัครร้านผ่าน API: สำเร็จ / สมัครซ้ำ 409 / ข้อความ validation ภาษาไทย
 * - ลงขายสินค้า (รูป + กลุ่มตัวเลือก) · ครบโควต้า → 403 และไม่มีไฟล์รูปค้าง
 * - แก้รายละเอียดสินค้า (ราคาก่อนลดต้องมากกว่าราคาขาย) · ลบสินค้า (มีออเดอร์ค้าง = 409)
 * - IDOR: ร้านอื่นแก้/ลบ/ดู/เพิ่มรูปสินค้าของเราไม่ได้ (404) และข้อมูลไม่เปลี่ยน
 * - รายได้ร้าน: ตัวเลข API = หน้าเว็บ = ค่าที่คำนวณมือ (เฉพาะออเดอร์ของร้านตัวเอง)
 */
class FreshMarketSellerAppApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
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
            'rider_enabled' => false,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();
        Setting::set('fresh_market.auto_approve_sellers', '1', 'boolean', 'fresh_market');
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    private function makeUser(float $balance = 0): User
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => $balance, 'currency' => 'THB', 'status' => 'active']);

        return $user;
    }

    /**
     * @return array{0: User, 1: FreshMarketSeller}
     */
    private function makeShop(string $name = 'ร้านทดสอบ'): array
    {
        $user = $this->makeUser();

        $shop = FreshMarketSeller::create([
            'user_id' => $user->id,
            'shop_name' => $name,
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.75,
            'longitude' => 100.5,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        return [$user, $shop];
    }

    private function makeListing(FreshMarketSeller $shop, array $overrides = []): FreshMarketListing
    {
        return FreshMarketListing::create(array_merge([
            'seller_id' => $shop->id,
            'title' => 'ข้าวผัดกะเพรา',
            'price' => 50,
            'unit' => 'จาน',
            'quantity_available' => 0,
            'track_stock' => false,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ], $overrides));
    }

    private function category(string $name = 'อาหารปรุงสด'): FreshMarketCategory
    {
        return FreshMarketCategory::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'icon' => '🍲',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function order(FreshMarketSeller $shop, User $buyer, array $overrides): FreshMarketOrder
    {
        return FreshMarketOrder::create(array_merge([
            'buyer_id' => $buyer->id,
            'seller_id' => $shop->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total_amount' => 100,
            'platform_fee' => 10,
            'gp_rate' => 10,
            'seller_earning' => 90,
            'delivery_type' => 'pickup',
            'delivery_fee' => 0,
            'payment_method' => 'wallet',
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ], $overrides));
    }

    // =====================================================
    // สมัครร้าน
    // =====================================================

    public function test_register_seller_via_api_then_duplicate_is_refused(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $body = [
            'shop_name' => 'กะเพราป้าแดง',
            'shop_description' => 'ผัดไฟแรง',
            'phone' => '081-234-5678',
            'address' => '12 ซอยตลาด',
            'province' => 'กรุงเทพมหานคร',
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'agree_terms' => true,
        ];

        $this->postJson('/api/v1/fresh-market/seller/register', $body)
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shop_name', 'กะเพราป้าแดง');

        $this->assertDatabaseHas('fresh_market_sellers', ['user_id' => $user->id, 'shop_name' => 'กะเพราป้าแดง']);

        // กดซ้ำ (เช่น เน็ตช้าแล้วกดอีกครั้ง) → ไม่สร้างร้านที่สอง
        $this->postJson('/api/v1/fresh-market/seller/register', $body)
            ->assertStatus(409)
            ->assertJsonPath('code', 'SELLER_EXISTS');

        $this->assertSame(1, FreshMarketSeller::where('user_id', $user->id)->count());
    }

    public function test_register_seller_validation_messages_are_thai(): void
    {
        Sanctum::actingAs($this->makeUser());

        // ไม่ปักหมุด + ไม่ยอมรับเงื่อนไข
        $response = $this->postJson('/api/v1/fresh-market/seller/register', [
            'shop_name' => 'ร้านใหม่',
            'phone' => '0812345678',
            'address' => 'ตลาด',
        ])->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->assertStringContainsString('ปักหมุด', (string) $response->json('message'));
        $this->assertArrayHasKey('agree_terms', $response->json('errors'));
        $this->assertStringContainsString('ยอมรับ', $response->json('errors.agree_terms.0'));

        // เบอร์ผิดรูปแบบ
        $this->postJson('/api/v1/fresh-market/seller/register', [
            'shop_name' => 'ร้านใหม่',
            'phone' => 'abc',
            'address' => 'ตลาด',
            'latitude' => 13.7,
            'longitude' => 100.5,
            'agree_terms' => true,
        ])->assertStatus(422)->assertJsonPath('message', 'รูปแบบเบอร์โทรไม่ถูกต้อง');

        $this->assertSame(0, FreshMarketSeller::count());
    }

    public function test_update_seller_profile_validates_and_saves(): void
    {
        [$user, $shop] = $this->makeShop();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/fresh-market/seller/profile', ['phone' => '12'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'รูปแบบเบอร์โทรไม่ถูกต้อง');

        $this->putJson('/api/v1/fresh-market/seller/profile', [
            'shop_name' => 'ร้านชื่อใหม่',
            'shop_description' => null,
            'phone' => '0899999999',
            'address' => 'ที่อยู่ใหม่',
            'district' => 'บางรัก',
            'latitude' => 13.72,
            'longitude' => 100.52,
        ])->assertOk()
            ->assertJsonPath('data.shop_name', 'ร้านชื่อใหม่')
            ->assertJsonPath('data.district', 'บางรัก');

        $fresh = $shop->fresh();
        $this->assertSame('0899999999', $fresh->phone);
        $this->assertEqualsWithDelta(13.72, (float) $fresh->latitude, 0.0001);
    }

    // =====================================================
    // ลงขาย / แก้ / ลบ
    // =====================================================

    public function test_create_listing_with_images_and_option_groups(): void
    {
        [$user, $shop] = $this->makeShop();
        $category = $this->category();
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/fresh-market/listings', [
            'title' => 'ข้าวผัดกะเพรา',
            'description' => 'ผัดไฟแรง',
            'category_id' => $category->id,
            'price' => '60',
            'compare_at_price' => '70',
            'unit' => 'จาน',
            'track_stock' => '0',
            'is_organic' => '0',
            'freshness_level' => 'ผลิตวันนี้',
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            'option_groups' => json_encode([
                [
                    'name' => 'เลือกเนื้อสัตว์',
                    'selection_type' => 'single',
                    'is_required' => true,
                    'options' => [['name' => 'หมู', 'price_delta' => 0], ['name' => 'กุ้ง', 'price_delta' => 20]],
                ],
            ]),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $listing = FreshMarketListing::where('seller_id', $shop->id)->firstOrFail();
        $this->assertSame('ข้าวผัดกะเพรา', $listing->title);
        $this->assertFalse($listing->tracksStock());
        $this->assertCount(2, (array) $listing->images);
        $this->assertSame($listing->images[0], $listing->main_image_url);
        foreach ($listing->images as $url) {
            Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));
        }

        $group = FreshMarketListingOptionGroup::where('listing_id', $listing->id)->with('options')->firstOrFail();
        $this->assertSame('เลือกเนื้อสัตว์', $group->name);
        $this->assertCount(2, $group->options);
        $this->assertSame(2, count($response->json('data.images')));
    }

    public function test_create_listing_validation_is_thai_and_compare_price_must_exceed_price(): void
    {
        [$user] = $this->makeShop();
        $category = $this->category();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/fresh-market/listings', [
            'title' => 'ผักบุ้ง',
            'category_id' => $category->id,
            'price' => 30,
            'compare_at_price' => 20,
            'unit' => 'กำ',
            'track_stock' => true,
            'quantity_available' => 5,
        ])->assertStatus(422)->assertJsonPath('message', 'ราคาก่อนลดต้องมากกว่าราคาขาย');

        // นับสต็อกแต่ไม่กรอกจำนวน
        $this->postJson('/api/v1/fresh-market/listings', [
            'title' => 'ผักบุ้ง',
            'category_id' => $category->id,
            'price' => 30,
            'unit' => 'กำ',
            'track_stock' => true,
        ])->assertStatus(422)->assertJsonPath('message', 'กรุณาระบุจำนวนสินค้าที่มีขาย');

        $this->assertSame(0, FreshMarketListing::count());
    }

    public function test_create_listing_over_quota_is_refused_before_storing_images(): void
    {
        FreshMarketSetting::getSettings()->update(['fee_mode' => 'subscription', 'max_listings_free' => 0]);
        FreshMarketSetting::clearCache();

        [$user] = $this->makeShop();
        $category = $this->category();
        Sanctum::actingAs($user);

        $this->post('/api/v1/fresh-market/listings', [
            'title' => 'ผักบุ้ง',
            'category_id' => $category->id,
            'price' => '30',
            'unit' => 'กำ',
            'images' => [UploadedFile::fake()->image('a.jpg')],
        ], ['Accept' => 'application/json'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'LISTING_LIMIT');

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, FreshMarketListing::count());

        // ฟอร์มในแอปรู้ล่วงหน้าว่าลงขายเพิ่มไม่ได้
        $this->getJson('/api/v1/fresh-market/seller/listing-form')
            ->assertOk()
            ->assertJsonPath('data.can_create_listing', false)
            ->assertJsonPath('data.limit_message', app(FreshMarketService::class)->listingLimitMessage());
    }

    public function test_update_listing_details_and_compare_price_rule(): void
    {
        [$user, $shop] = $this->makeShop();
        $category = $this->category();
        $listing = $this->makeListing($shop, ['price' => 50]);
        Sanctum::actingAs($user);

        // ราคาก่อนลดต่ำกว่าราคาเดิม → ไม่ผ่าน
        $this->putJson("/api/v1/fresh-market/listings/{$listing->id}", ['compare_at_price' => 40])
            ->assertStatus(422)
            ->assertJsonPath('message', 'ราคาก่อนลดต้องมากกว่าราคาขาย');

        // เปลี่ยนราคาพร้อมกัน → เทียบกับราคาใหม่
        $this->putJson("/api/v1/fresh-market/listings/{$listing->id}", [
            'title' => 'กะเพราหมูกรอบ',
            'description' => 'หมูกรอบทำเอง',
            'category_id' => $category->id,
            'unit' => 'กล่อง',
            'price' => 35,
            'compare_at_price' => 40,
            'freshness_level' => 'สดมาก',
            'is_organic' => true,
        ])->assertOk()
            ->assertJsonPath('data.title', 'กะเพราหมูกรอบ')
            ->assertJsonPath('data.compare_at_price', 40);

        $fresh = $listing->fresh();
        $this->assertSame('กล่อง', $fresh->unit);
        $this->assertSame((int) $category->id, (int) $fresh->category_id);
        $this->assertEqualsWithDelta(35.0, (float) $fresh->price, 0.001);

        // ล้างราคาก่อนลด (null) ได้
        $this->putJson("/api/v1/fresh-market/listings/{$listing->id}", ['compare_at_price' => null])->assertOk();
        $this->assertNull($listing->fresh()->compare_at_price);
    }

    public function test_seller_cannot_touch_another_sellers_listing(): void
    {
        [, $victimShop] = $this->makeShop('ร้านเหยื่อ');
        [$attacker] = $this->makeShop('ร้านอื่น');
        $listing = $this->makeListing($victimShop, ['title' => 'ของเหยื่อ', 'price' => 50]);
        Sanctum::actingAs($attacker);

        $this->getJson("/api/v1/fresh-market/seller/listings/{$listing->id}")->assertStatus(404)->assertJsonPath('code', 'LISTING_NOT_FOUND');
        $this->putJson("/api/v1/fresh-market/listings/{$listing->id}", ['title' => 'ถูกแก้', 'price' => 1])->assertStatus(404);
        $this->deleteJson("/api/v1/fresh-market/listings/{$listing->id}")->assertStatus(404);
        $this->post("/api/v1/fresh-market/listings/{$listing->id}/images", [
            'images' => [UploadedFile::fake()->image('x.jpg')],
        ], ['Accept' => 'application/json'])->assertStatus(404);
        $this->putJson("/api/v1/fresh-market/listings/{$listing->id}/option-groups", ['option_groups' => []])->assertStatus(404);

        $fresh = $listing->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame('ของเหยื่อ', $fresh->title);
        $this->assertEqualsWithDelta(50.0, (float) $fresh->price, 0.001);
        $this->assertSame([], Storage::disk('public')->allFiles());

        // ผู้ใช้ที่ไม่มีร้าน → NOT_SELLER
        Sanctum::actingAs($this->makeUser());
        $this->deleteJson("/api/v1/fresh-market/listings/{$listing->id}")->assertStatus(403)->assertJsonPath('code', 'NOT_SELLER');
    }

    public function test_delete_own_listing_and_refuse_when_order_is_active(): void
    {
        [$user, $shop] = $this->makeShop();
        $buyer = $this->makeUser();
        $busy = $this->makeListing($shop, ['title' => 'มีออเดอร์ค้าง']);
        $free = $this->makeListing($shop, ['title' => 'ลบได้']);
        $this->order($shop, $buyer, ['listing_id' => $busy->id, 'order_status' => 'preparing']);
        $shop->refreshStats();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/fresh-market/listings/{$busy->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'LISTING_HAS_ACTIVE_ORDERS');
        $this->assertNotNull($busy->fresh());

        $this->deleteJson("/api/v1/fresh-market/listings/{$free->id}")->assertOk()->assertJsonPath('data.id', $free->id);
        $this->assertNull(FreshMarketListing::find($free->id));
        $this->assertSame(1, (int) $shop->fresh()->total_listings);
    }

    public function test_listing_form_returns_categories_without_emoji_and_limits(): void
    {
        [$user] = $this->makeShop();
        $root = $this->category('ผักสด');
        $child = FreshMarketCategory::create(['name' => 'ผักใบ', 'slug' => 'leaf-'.Str::random(4), 'parent_id' => $root->id, 'sort_order' => 1, 'is_active' => true]);
        FreshMarketCategory::create(['name' => 'ปิดอยู่', 'slug' => 'off-'.Str::random(4), 'sort_order' => 2, 'is_active' => false]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/fresh-market/seller/listing-form')->assertOk();

        // หมวดหลักมาก่อนหมวดย่อย · ไม่มีหมวดที่ปิด · ไม่มีอีโมจิ (แอปใช้ไอคอนของตัวเอง)
        $categories = collect($response->json('data.categories'));
        $rootIndex = $categories->search(fn ($c) => $c['id'] === $root->id);
        $childIndex = $categories->search(fn ($c) => $c['id'] === $child->id);
        $this->assertNotFalse($rootIndex);
        $this->assertNotFalse($childIndex);
        $this->assertLessThan($childIndex, $rootIndex);
        $this->assertSame(['id' => $child->id, 'name' => 'ผักใบ', 'parent_id' => $root->id], $categories[$childIndex]);
        $this->assertFalse($categories->contains(fn ($c) => $c['name'] === 'ปิดอยู่'));
        $this->assertFalse($categories->contains(fn ($c) => array_key_exists('icon', $c)));
        $this->assertTrue($response->json('data.can_create_listing'));
        $this->assertNull($response->json('data.limit_message'));
        $this->assertSame(['สด', 'สดมาก', 'ผลิตวันนี้'], $response->json('data.freshness_levels'));
        $this->assertSame(5, $response->json('data.max_images'));
        $this->assertContains('จาน', $response->json('data.units'));

        Sanctum::actingAs($this->makeUser());
        $this->getJson('/api/v1/fresh-market/seller/listing-form')->assertStatus(403)->assertJsonPath('code', 'NOT_SELLER');
    }

    // =====================================================
    // รายได้ร้าน
    // =====================================================

    public function test_earnings_match_web_page_and_hand_computed_numbers(): void
    {
        $this->travelTo(Carbon::parse('2026-09-15 12:00:00'));

        [$user, $shop] = $this->makeShop();
        [$otherUser, $otherShop] = $this->makeShop('ร้านอื่น');
        $buyer = $this->makeUser();
        $listing = $this->makeListing($shop);

        // สำเร็จ: วันนี้ / 3 วันก่อน / 12 วันก่อน (ต้นเดือน) / เดือนก่อน
        $this->order($shop, $buyer, ['listing_id' => $listing->id, 'total_amount' => 100, 'platform_fee' => 10, 'seller_earning' => 90, 'completed_at' => '2026-09-15 10:00:00']);
        $this->order($shop, $buyer, ['listing_id' => $listing->id, 'total_amount' => 200, 'platform_fee' => 20, 'seller_earning' => 180, 'completed_at' => '2026-09-12 09:00:00']);
        $this->order($shop, $buyer, ['listing_id' => $listing->id, 'total_amount' => 50, 'platform_fee' => 5, 'seller_earning' => 45, 'payment_method' => 'cod', 'completed_at' => '2026-09-03 18:30:00']);
        $this->order($shop, $buyer, ['listing_id' => $listing->id, 'total_amount' => 80, 'platform_fee' => 8, 'seller_earning' => 72, 'completed_at' => '2026-08-20 08:00:00']);
        // ยังไม่จบ: จ่ายผ่านกระเป๋าแล้ว (ระบบถือไว้) + เก็บเงินปลายทาง + ยกเลิก (ไม่นับ)
        $this->order($shop, $buyer, ['listing_id' => $listing->id, 'total_amount' => 60, 'platform_fee' => 6, 'seller_earning' => 54, 'order_status' => 'preparing']);
        $this->order($shop, $buyer, ['listing_id' => $listing->id, 'total_amount' => 40, 'platform_fee' => 4, 'seller_earning' => 36, 'payment_method' => 'cod', 'payment_status' => 'pending', 'order_status' => 'accepted']);
        $this->order($shop, $buyer, ['listing_id' => $listing->id, 'total_amount' => 999, 'platform_fee' => 99, 'seller_earning' => 900, 'order_status' => 'cancelled']);
        // ร้านอื่น (ห้ามปน)
        $this->order($otherShop, $buyer, ['total_amount' => 500, 'platform_fee' => 50, 'seller_earning' => 450, 'completed_at' => '2026-09-15 11:00:00']);

        Wallet::where('user_id', $user->id)->update(['balance' => 321.5]);
        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'user_id' => $user->id, 'transaction_id' => 'TX-'.Str::random(10), 'type' => 'deposit',
            'amount' => 90, 'balance_before' => 231.5, 'balance_after' => 321.5, 'description' => 'รายได้ออเดอร์ตลาดสด',
            'reference_type' => FreshMarketService::REF_PAYOUT, 'reference_id' => 1, 'status' => 'completed',
        ]);
        $otherWallet = Wallet::where('user_id', $otherUser->id)->firstOrFail();
        WalletTransaction::create([
            'wallet_id' => $otherWallet->id, 'user_id' => $otherUser->id, 'transaction_id' => 'TX-'.Str::random(10), 'type' => 'deposit',
            'amount' => 450, 'balance_before' => 0, 'balance_after' => 450, 'description' => 'ร้านอื่น',
            'reference_type' => FreshMarketService::REF_PAYOUT, 'reference_id' => 2, 'status' => 'completed',
        ]);

        Sanctum::actingAs($user);
        $api = $this->getJson('/api/v1/fresh-market/seller/earnings')->assertOk()->json('data');

        // ค่าที่คำนวณมือ
        $this->assertEquals(['label' => 'วันนี้', 'orders' => 1, 'gross' => 100, 'gp' => 10, 'net' => 90], $api['periods']['today']);
        $this->assertEquals(['label' => '7 วันล่าสุด', 'orders' => 2, 'gross' => 300, 'gp' => 30, 'net' => 270], $api['periods']['week']);
        $this->assertEquals(['label' => 'เดือนนี้', 'orders' => 3, 'gross' => 350, 'gp' => 35, 'net' => 315], $api['periods']['month']);
        $this->assertEquals(['label' => 'ทั้งหมด', 'orders' => 4, 'gross' => 430, 'gp' => 43, 'net' => 387], $api['periods']['all']);
        $this->assertEquals(['orders' => 2, 'held_net' => 54, 'cod_to_collect' => 40], $api['pending']);
        $this->assertCount(14, $api['daily']);
        $this->assertSame('2026-09-02', $api['daily'][0]['date']);
        $this->assertSame('2026-09-15', $api['daily'][13]['date']);
        $this->assertEquals(315, array_sum(array_column($api['daily'], 'net')));
        $this->assertEquals(321.5, $api['wallet_balance']);
        $this->assertCount(1, $api['payouts']);
        $this->assertEquals(90, $api['payouts'][0]['amount']);
        $this->assertCount(4, $api['recent_completed']);
        $this->assertSame('ข้าวผัดกะเพรา', $api['recent_completed'][0]['title']);
        $this->assertSame('เก็บเงินปลายทาง', $api['recent_completed'][2]['payment_method_label']);
        $this->assertTrue(is_int($api['periods']['all']['net']) || is_float($api['periods']['all']['net']));

        // หน้าเว็บใช้ service เดียวกัน → ตัวเลขตรงกันทุกช่อง
        $service = app(FreshMarketSellerEarningsService::class)->summary($shop->fresh());
        $web = $this->actingAs($user)->get(route('taladsod.seller.earnings'))->assertOk();
        $web->assertViewHas('periods', $service['periods']);
        $web->assertViewHas('daily', $service['daily']);
        $web->assertViewHas('pending', $service['pending']);
        $web->assertViewHas('walletBalance', $service['wallet_balance']);
        foreach ($service['periods'] as $key => $period) {
            $this->assertEquals($period['data'], array_diff_key($api['periods'][$key], ['label' => true]), "ช่วง {$key}");
        }
        $this->assertEquals($service['daily'], $api['daily']);

        // ไม่ใช่เจ้าของร้าน → 403
        Sanctum::actingAs($this->makeUser());
        $this->getJson('/api/v1/fresh-market/seller/earnings')->assertStatus(403)->assertJsonPath('code', 'NOT_SELLER');
    }
}
