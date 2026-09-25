<?php

namespace Tests\Feature\FreshMarket;

use App\Models\FreshMarketConversation;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketListingOption;
use App\Models\FreshMarketListingOptionGroup;
use App\Models\FreshMarketOrderItem;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FreshMarketChannelManager;
use App\Services\FreshMarketOptionService;
use App\Services\FreshMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * แก้ปัญหาจากรีวิวก่อนเปิดตัว (ตลาดสด) — ต้องใช้ MySQL
 *
 * - รูปตัวเลือก: ร้านหนึ่งชี้ image_url ไปไฟล์ของร้านอื่นแล้วลบทิ้งไม่ได้ · ลบตัวเลือกไม่ทำให้รูปในออเดอร์เก่าหาย
 *   · ส่งตัวเลือกทั้งชุดใหม่แล้วไฟล์ที่ไม่มีใครใช้ถูกลบ (ไม่ค้างบน disk)
 * - ร้านเคลื่อนที่: ออเดอร์ที่จบ/ยกเลิกแล้วไม่เปิดเผยตำแหน่งล่าสุดของร้าน (อาจเป็นบ้าน) ทั้ง API และหน้าเว็บ
 * - LINE: ยอดในสรุปก่อนกด "ยืนยัน" ตรงกับยอดที่ถูกเก็บจริง (รวมตัวเลือกบังคับที่ระบบเลือกให้)
 */
class FreshMarketLaunchReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
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

    /**
     * @return array{0: User, 1: FreshMarketSeller, 2: FreshMarketListing}
     */
    private function makeShop(string $name, float $price = 50): array
    {
        $user = User::factory()->create(['name' => $name]);
        Wallet::create(['user_id' => $user->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

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

        $listing = FreshMarketListing::create([
            'seller_id' => $shop->id,
            'title' => 'เมนู '.$name,
            'price' => $price,
            'unit' => 'จาน',
            'quantity_available' => 0,
            'track_stock' => false,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        return [$user, $shop, $listing];
    }

    private function makeBuyer(float $balance = 1000): User
    {
        $buyer = User::factory()->create();
        Wallet::create(['user_id' => $buyer->id, 'balance' => $balance, 'currency' => 'THB', 'status' => 'active']);

        return $buyer;
    }

    /**
     * สร้างกลุ่มตัวเลือก 1 กลุ่มผ่าน API แล้วอัปโหลดรูปให้ตัวเลือกแรก → [ตัวเลือก, url รูป]
     *
     * @return array{0: FreshMarketListingOption, 1: string}
     */
    private function optionWithUploadedImage(User $owner, FreshMarketListing $listing): array
    {
        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/fresh-market/listings/{$listing->id}/option-groups", [
            'name' => 'เนื้อสัตว์',
            'selection_type' => 'single',
            'is_required' => true,
            'options' => [['name' => 'หมู', 'price_delta' => 0], ['name' => 'ไก่', 'price_delta' => 0]],
        ])->assertStatus(201);

        $option = FreshMarketListingOption::where('listing_id', $listing->id)->where('name', 'หมู')->firstOrFail();
        $this->post("/api/v1/fresh-market/options/{$option->id}/image", [
            'image' => UploadedFile::fake()->image('pork.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $url = (string) $option->fresh()->image_url;
        $this->assertStringStartsWith('/storage/fresh-market/options/'.$listing->id.'/', $url);

        return [$option->fresh(), $url];
    }

    // ===== รูปตัวเลือก =====

    public function test_seller_cannot_point_option_image_at_another_shops_file(): void
    {
        Storage::fake('public');

        [$victimUser, , $victimListing] = $this->makeShop('ร้านเหยื่อ');
        [$attackerUser, , $attackerListing] = $this->makeShop('ร้านโจมตี');
        [, $victimUrl] = $this->optionWithUploadedImage($victimUser, $victimListing);
        $victimPath = substr($victimUrl, strlen('/storage/'));

        // ส่งทั้งชุด (sync) ชี้ไปรูปของร้านอื่น → ถูกปฏิเสธ ไม่มีอะไรถูกบันทึก
        Sanctum::actingAs($attackerUser);
        $this->putJson("/api/v1/fresh-market/listings/{$attackerListing->id}/option-groups", [
            'option_groups' => [[
                'name' => 'x',
                'selection_type' => 'single',
                'options' => [
                    ['name' => 'a', 'price_delta' => 0, 'image_url' => $victimUrl],
                    ['name' => 'b', 'price_delta' => 0],
                ],
            ]],
        ])->assertStatus(422)->assertJsonPath('code', 'INVALID_OPTION_IMAGE');
        $this->assertSame(0, FreshMarketListingOption::where('listing_id', $attackerListing->id)->count());

        // แก้ตัวเลือกทีละตัวก็ไม่ได้เช่นกัน
        $this->postJson("/api/v1/fresh-market/listings/{$attackerListing->id}/option-groups", [
            'name' => 'y', 'selection_type' => 'single', 'options' => [['name' => 'c'], ['name' => 'd']],
        ])->assertStatus(201);
        $own = FreshMarketListingOption::where('listing_id', $attackerListing->id)->where('name', 'c')->firstOrFail();
        $this->putJson("/api/v1/fresh-market/options/{$own->id}", ['image_url' => $victimUrl])
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_OPTION_IMAGE');
        $this->assertNull($own->fresh()->image_url);

        // ลบตัวเลือก/กลุ่มของตัวเอง → ไฟล์ของร้านอื่นยังอยู่ครบ
        $this->deleteJson("/api/v1/fresh-market/options/{$own->id}")->assertOk();
        Storage::disk('public')->assertExists($victimPath);

        // รูปคลังของระบบใช้ได้
        $other = FreshMarketListingOption::where('listing_id', $attackerListing->id)->where('name', 'd')->firstOrFail();
        $this->putJson("/api/v1/fresh-market/options/{$other->id}", ['image_url' => '/images/taladsod/krapao-pork.webp'])->assertOk();
        $this->assertSame('/images/taladsod/krapao-pork.webp', $other->fresh()->image_url);
    }

    public function test_file_shared_by_legacy_rows_is_not_deleted_while_still_referenced(): void
    {
        Storage::fake('public');

        [$victimUser, , $victimListing] = $this->makeShop('ร้านเหยื่อ');
        [$attackerUser, , $attackerListing] = $this->makeShop('ร้านโจมตี');
        [, $victimUrl] = $this->optionWithUploadedImage($victimUser, $victimListing);
        $victimPath = substr($victimUrl, strlen('/storage/'));

        // ข้อมูลเก่า (ก่อนมีการตรวจ) ที่ชี้ไฟล์ของร้านอื่นอยู่แล้ว
        Sanctum::actingAs($attackerUser);
        $this->postJson("/api/v1/fresh-market/listings/{$attackerListing->id}/option-groups", [
            'name' => 'x', 'selection_type' => 'single', 'options' => [['name' => 'a'], ['name' => 'b']],
        ])->assertStatus(201);
        $legacy = FreshMarketListingOption::where('listing_id', $attackerListing->id)->where('name', 'a')->firstOrFail();
        $legacy->forceFill(['image_url' => $victimUrl])->save();

        $this->deleteJson("/api/v1/fresh-market/options/{$legacy->id}")->assertOk();
        Storage::disk('public')->assertExists($victimPath);

        $group = FreshMarketListingOptionGroup::where('listing_id', $attackerListing->id)->firstOrFail();
        $this->deleteJson("/api/v1/fresh-market/option-groups/{$group->id}")->assertOk();
        Storage::disk('public')->assertExists($victimPath);
    }

    public function test_deleting_option_keeps_image_used_by_past_order_items(): void
    {
        Storage::fake('public');

        [$sellerUser, , $listing] = $this->makeShop('ร้านกะเพรา');
        [$option, $url] = $this->optionWithUploadedImage($sellerUser, $listing);
        $path = substr($url, strlen('/storage/'));

        // ผู้ซื้อสั่งตัวเลือกที่มีรูป → รูปถูก snapshot ลงรายการในออเดอร์
        $buyer = $this->makeBuyer();
        $order = app(FreshMarketService::class)->createOrder($buyer, $listing->fresh(), [
            'quantity' => 1, 'delivery_type' => 'pickup', 'payment_method' => 'cod', 'option_ids' => [$option->id],
        ]);
        $this->assertSame($url, FreshMarketOrderItem::where('order_id', $order->id)->value('image_url'));

        // ร้านลบตัวเลือก → ไฟล์ยังอยู่ (ใบเสร็จ/ออเดอร์เก่ายังแสดงรูปได้)
        Sanctum::actingAs($sellerUser);
        $this->deleteJson("/api/v1/fresh-market/options/{$option->id}")->assertOk();
        Storage::disk('public')->assertExists($path);
    }

    public function test_sync_and_replace_delete_unreferenced_option_files(): void
    {
        Storage::fake('public');

        [$sellerUser, , $listing] = $this->makeShop('ร้านข้าว');
        [$option, $url] = $this->optionWithUploadedImage($sellerUser, $listing);
        $path = substr($url, strlen('/storage/'));

        // อัปโหลดรูปใหม่แทนรูปเดิม → ไฟล์เดิม (ไม่มีใครใช้) ถูกลบ
        Sanctum::actingAs($sellerUser);
        $this->post("/api/v1/fresh-market/options/{$option->id}/image", [
            'image' => UploadedFile::fake()->image('pork2.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing($path);

        $newUrl = (string) $option->fresh()->image_url;
        $newPath = substr($newUrl, strlen('/storage/'));
        Storage::disk('public')->assertExists($newPath);

        // ส่งตัวเลือกทั้งชุดใหม่ (ไม่มีตัวเลือกเดิม) → ไฟล์ของตัวเลือกที่หายไปถูกลบ ไม่ค้างบน disk
        $this->putJson("/api/v1/fresh-market/listings/{$listing->id}/option-groups", [
            'option_groups' => [[
                'name' => 'ขนาด',
                'selection_type' => 'single',
                'options' => [['name' => 'ธรรมดา'], ['name' => 'พิเศษ', 'price_delta' => 10]],
            ]],
        ])->assertOk();
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_option_can_reuse_image_already_used_by_same_listing(): void
    {
        Storage::fake('public');

        [$sellerUser, , $listing] = $this->makeShop('ร้านข้าวผัด');
        [$option, $url] = $this->optionWithUploadedImage($sellerUser, $listing);

        $group = FreshMarketListingOptionGroup::findOrFail($option->group_id);

        // ส่งกลุ่มเดิมพร้อมตัวเลือกใหม่ที่ใช้รูปเดียวกับตัวเลือกในสินค้าเดียวกัน → ได้
        Sanctum::actingAs($sellerUser);
        $this->putJson("/api/v1/fresh-market/option-groups/{$group->id}", [
            'options' => [
                ['id' => $option->id, 'name' => 'หมู'],
                ['name' => 'หมูกรอบ', 'price_delta' => 10, 'image_url' => $url],
            ],
        ])->assertOk();

        $this->assertSame($url, FreshMarketListingOption::where('listing_id', $listing->id)->where('name', 'หมูกรอบ')->value('image_url'));
        Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));
    }

    public function test_removed_listing_image_is_kept_while_past_orders_use_it(): void
    {
        Storage::fake('public');

        [$sellerUser, , $listing] = $this->makeShop('ร้านผลไม้');

        Sanctum::actingAs($sellerUser);
        $this->post("/api/v1/fresh-market/listings/{$listing->id}/images", [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $images = $listing->fresh()->images;
        [$first, $second] = $images;

        // ออเดอร์เก่า snapshot รูปหลัก (รูปแรก)
        $buyer = $this->makeBuyer();
        $order = app(FreshMarketService::class)->createOrder($buyer, $listing->fresh(), [
            'quantity' => 1, 'delivery_type' => 'pickup', 'payment_method' => 'cod',
        ]);
        $this->assertSame($first, FreshMarketOrderItem::where('order_id', $order->id)->value('image_url'));

        // แทนรูปทั้งชุด → รูปแรก (ออเดอร์ใช้อยู่) ยังอยู่ · รูปที่สอง (ไม่มีใครใช้) ถูกลบ
        Sanctum::actingAs($sellerUser);
        $this->post("/api/v1/fresh-market/listings/{$listing->id}/images", [
            'images' => [UploadedFile::fake()->image('c.jpg')],
            'replace' => 1,
        ], ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertExists(substr($first, strlen('/storage/')));
        Storage::disk('public')->assertMissing(substr($second, strlen('/storage/')));

        // ลบรูปที่ไม่ใช่ของสินค้านี้ → ไม่แตะไฟล์
        $current = $listing->fresh()->images[0];
        [$otherUser, , $otherListing] = $this->makeShop('ร้านอื่น');
        Sanctum::actingAs($otherUser);
        $this->deleteJson("/api/v1/fresh-market/listings/{$otherListing->id}/images", ['url' => $current])->assertOk();
        Storage::disk('public')->assertExists(substr($current, strlen('/storage/')));
    }

    // ===== ร้านเคลื่อนที่: ไม่เปิดเผยตำแหน่งล่าสุดหลังออเดอร์จบ =====

    public function test_finished_order_does_not_reveal_closed_mobile_vendor_location(): void
    {
        [$sellerUser, $shop, $listing] = $this->makeShop('รถเข็นลุง');
        $shop->forceFill(['latitude' => null, 'longitude' => null, 'address' => null])->save();

        Sanctum::actingAs($sellerUser);
        $this->postJson('/api/v1/fresh-market/seller/open', [
            'latitude' => 13.70, 'longitude' => 100.50, 'location_label' => 'ตลาดนัดหน้าวัด', 'live_location_sharing' => true,
        ])->assertOk();

        $buyer = $this->makeBuyer();
        $service = app(FreshMarketService::class);
        $active = $service->createOrder($buyer, $listing->fresh(), [
            'quantity' => 1, 'delivery_type' => 'pickup', 'payment_method' => 'cod', 'channel' => 'api',
        ]);
        $cancelled = $service->createOrder($buyer, $listing->fresh(), [
            'quantity' => 1, 'delivery_type' => 'pickup', 'payment_method' => 'cod', 'channel' => 'api',
        ]);
        $service->applyAction($cancelled, 'cancel', 'buyer', $buyer, ['reason' => 'เปลี่ยนใจ']);

        // ร้านเปิดอยู่ + ออเดอร์ยังดำเนินอยู่ → เห็นจุดขายตอนนี้
        Sanctum::actingAs($buyer);
        $open = $this->getJson("/api/v1/fresh-market/orders/{$active->id}")->assertOk();
        $this->assertEqualsWithDelta(13.70, (float) $open->json('data.seller.latitude'), 0.0001);
        $this->getJson("/api/v1/fresh-market/orders/{$cancelled->id}")->assertOk()
            ->assertJsonPath('data.seller.latitude', null)
            ->assertJsonPath('data.seller.longitude', null);

        // ร้านขับกลับบ้านแล้วกดปิดร้าน
        Sanctum::actingAs($sellerUser);
        $this->postJson('/api/v1/fresh-market/seller/location', ['latitude' => 13.81, 'longitude' => 100.61])->assertOk();
        $this->postJson('/api/v1/fresh-market/seller/close')->assertOk();

        Sanctum::actingAs($buyer);
        foreach ([$active, $cancelled] as $order) {
            $res = $this->getJson("/api/v1/fresh-market/orders/{$order->id}")->assertOk();
            $this->assertNull($res->json('data.seller.latitude'));
            $this->assertNull($res->json('data.seller.longitude'));
        }

        $list = $this->getJson('/api/v1/fresh-market/orders')->assertOk();
        foreach ((array) $list->json('data') as $row) {
            $this->assertNull($row['seller']['latitude'] ?? null);
        }

        // หน้าเว็บผู้ซื้อ: ไม่มีลิงก์นำทางไปพิกัดล่าสุด (บ้าน)
        $this->actingAs($buyer);
        $html = $this->get(route('taladsod.orders.show', $cancelled->id))->assertOk()->getContent();
        $this->assertStringNotContainsString('13.81', $html);
        $this->assertStringNotContainsString('100.61', $html);
        $html = $this->get(route('taladsod.orders.show', $active->id))->assertOk()->getContent();
        $this->assertStringNotContainsString('13.81', $html);
        $this->assertStringContainsString('ตลาดนัดหน้าวัด', $html);

        // ฝั่งร้านเจ้าของยังเห็นจุดรับของของตัวเอง
        Sanctum::actingAs($sellerUser);
        $seller = $this->getJson("/api/v1/fresh-market/seller/orders/{$active->id}")->assertOk();
        $this->assertEqualsWithDelta(13.81, (float) $seller->json('data.seller.latitude'), 0.0001);
    }

    // ===== LINE: ยอดก่อนยืนยัน = ยอดที่ถูกเก็บจริง =====

    public function test_line_review_total_matches_charged_total_with_default_options(): void
    {
        [, , $listing] = $this->makeShop('ร้านข้าวมันไก่');
        $group = FreshMarketListingOptionGroup::create([
            'listing_id' => $listing->id, 'name' => 'ขนาด', 'selection_type' => 'single', 'is_required' => true,
        ]);
        FreshMarketListingOption::create(['group_id' => $group->id, 'listing_id' => $listing->id, 'name' => 'ธรรมดา', 'price_delta' => 10, 'is_available' => true]);
        FreshMarketListingOption::create(['group_id' => $group->id, 'listing_id' => $listing->id, 'name' => 'พิเศษ', 'price_delta' => 20, 'is_available' => true]);

        $buyer = $this->makeBuyer();
        $conversation = FreshMarketConversation::create([
            'line_user_id' => 'U'.str_repeat('a', 32),
            'user_id' => $buyer->id,
            'role' => 'buyer',
            'conversation_state' => FreshMarketConversation::STATE_ORDER_QUANTITY,
            'context' => ['order' => [
                'listing_id' => $listing->id,
                'listing_title' => $listing->title,
                'listing_price' => $listing->price,
                'listing_unit' => $listing->unit,
                'seller_shop_name' => 'ร้านข้าวมันไก่',
            ]],
        ]);

        $manager = new FreshMarketChannelManager;
        $quantity = new \ReflectionMethod($manager, 'handleOrderQuantity_Text');
        $reply = $quantity->invoke($manager, $conversation, '2 นัดรับ', []);

        $ctx = $conversation->fresh()->getFlowContext('order');
        $this->assertEquals(60, $ctx['unit_price']);
        $this->assertEquals(120, $ctx['total_amount']);
        $this->assertStringContainsString('฿60 x 2 = ฿120', $reply['text']);
        $this->assertStringContainsString('ธรรมดา', $reply['text']);

        $confirm = new \ReflectionMethod($manager, 'createOrderFromContext');
        $confirm->invoke($manager, $conversation->fresh());

        $order = \App\Models\FreshMarketOrder::where('buyer_id', $buyer->id)->latest('id')->firstOrFail();
        $this->assertSame(120.0, (float) $order->total_amount);
        $this->assertSame((float) $ctx['total_amount'], (float) $order->total_amount);
    }

    public function test_option_service_rejects_foreign_image_directly(): void
    {
        [, , $listingA] = $this->makeShop('ร้าน A');
        [, , $listingB] = $this->makeShop('ร้าน B');
        $service = app(FreshMarketOptionService::class);

        $this->assertTrue($service->imageUsableByListing('/images/taladsod/fried-egg.webp', (int) $listingA->id));
        $this->assertFalse($service->imageUsableByListing('/storage/fresh-market/options/'.$listingB->id.'/x.jpg', (int) $listingA->id));
        $this->assertFalse($service->imageUsableByListing('/storage/../.env', (int) $listingA->id));
        $this->assertFalse($service->deleteStoredImage('/storage/fresh-market/options/'.$listingB->id.'/x.jpg', (int) $listingA->id));
    }
}
