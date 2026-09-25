<?php

namespace Tests\Feature\FreshMarket;

use App\Exceptions\FreshMarketException;
use App\Models\FreshMarketCartItem;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketListingOption;
use App\Models\FreshMarketListingOptionGroup;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketOrderItem;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FreshMarketCartService;
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
 * ตัวเลือกสินค้า + ตะกร้าหลายรายการ + ออเดอร์หลายรายการ + เมนูเปิดตัว (ต้องใช้ MySQL)
 *
 * ครอบคลุม:
 * - ราคาคำนวณฝั่งเซิร์ฟเวอร์ = ราคาสินค้า + ตัวเลือก (ไม่เชื่อราคาจาก client)
 * - ตัวเลือกของสินค้าอื่น (ปลอม id) ถูกปฏิเสธ / ไม่เลือกกลุ่มบังคับถูกปฏิเสธ / เลือกเกินจำนวน / ตัวเลือกปิดขาย
 * - ออเดอร์หลายรายการ: ยอดรวม GP แคชแบ็ค สต็อก (เฉพาะสินค้าที่ตัดสต็อก) + ยกเลิกคืนสต็อกถูกรายการ
 * - ตะกร้า → ออเดอร์ (API) ล้างตะกร้า + กดซ้ำไม่ได้ออเดอร์ที่สอง
 * - ทางเดิม (สินค้าเดียว) ยังใช้ได้ · LINE ใช้ตัวเลือกเริ่มต้น
 * - ร้านจัดการตัวเลือกผ่าน API / ร้านอื่นแก้ไม่ได้
 * - data migration เมนูเปิดตัวรันซ้ำได้ (ได้สินค้าเดียว)
 */
class FreshMarketOptionsCartTest extends TestCase
{
    use RefreshDatabase;

    protected FreshMarketService $service;

    protected User $buyer;

    protected User $sellerUser;

    protected FreshMarketSeller $seller;

    protected FreshMarketListing $krapao;

    protected FreshMarketListing $vegetable;

    protected FreshMarketListingOptionGroup $meatGroup;

    protected FreshMarketListingOptionGroup $extraGroup;

    /** @var array<string, FreshMarketListingOption> */
    protected array $opt = [];

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

        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('pricing.gp_free', '0', 'boolean', 'pricing');
        Setting::set('fresh_market.auto_approve_sellers', '1', 'boolean', 'fresh_market');

        $this->buyer = User::factory()->create(['name' => 'ผู้ซื้อทดสอบ']);
        $this->sellerUser = User::factory()->create(['name' => 'ร้านข้าวทดสอบ']);

        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
        Wallet::create(['user_id' => $this->sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $this->seller = FreshMarketSeller::create([
            'user_id' => $this->sellerUser->id,
            'shop_name' => 'ร้านข้าวทดสอบ',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        // อาหารทำตามสั่ง: ไม่ตัดสต็อก
        $this->krapao = FreshMarketListing::create([
            'seller_id' => $this->seller->id,
            'title' => 'ผัดกะเพราราดข้าว',
            'price' => 50,
            'unit' => 'จาน',
            'quantity_available' => 0,
            'track_stock' => false,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        // ของสด: ตัดสต็อก
        $this->vegetable = FreshMarketListing::create([
            'seller_id' => $this->seller->id,
            'title' => 'ผักบุ้งจีน',
            'price' => 50,
            'unit' => 'กำ',
            'quantity_available' => 5,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        $this->meatGroup = FreshMarketListingOptionGroup::create([
            'listing_id' => $this->krapao->id,
            'name' => 'เลือกเนื้อสัตว์',
            'selection_type' => 'single',
            'is_required' => true,
            'sort_order' => 0,
        ]);

        foreach ([['pork', 'หมูสับ', 0], ['chicken', 'ไก่', 0], ['squid', 'หมึก', 10], ['shrimp', 'กุ้ง', 20]] as $i => [$key, $name, $delta]) {
            $this->opt[$key] = FreshMarketListingOption::create([
                'group_id' => $this->meatGroup->id,
                'listing_id' => $this->krapao->id,
                'name' => $name,
                'price_delta' => $delta,
                'is_available' => true,
                'sort_order' => $i,
            ]);
        }

        $this->extraGroup = FreshMarketListingOptionGroup::create([
            'listing_id' => $this->krapao->id,
            'name' => 'เพิ่มเติม',
            'selection_type' => 'multi',
            'is_required' => false,
            'max_select' => 1,
            'sort_order' => 1,
        ]);

        $this->opt['egg'] = FreshMarketListingOption::create([
            'group_id' => $this->extraGroup->id,
            'listing_id' => $this->krapao->id,
            'name' => 'ไข่ดาว',
            'price_delta' => 10,
            'is_available' => true,
        ]);

        $this->opt['cheese'] = FreshMarketListingOption::create([
            'group_id' => $this->extraGroup->id,
            'listing_id' => $this->krapao->id,
            'name' => 'ไข่เจียว',
            'price_delta' => 15,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $this->service = new FreshMarketService;
    }

    // =====================================================
    // ราคา + กติกาตัวเลือก
    // =====================================================

    public function test_unit_price_is_base_plus_selected_option_deltas(): void
    {
        $line = app(FreshMarketOptionService::class)->resolveLine(
            $this->krapao->fresh(),
            [$this->opt['egg']->id, $this->opt['shrimp']->id],
            2
        );

        $this->assertSame(50.0, $line['base_price']);
        $this->assertSame(30.0, $line['options_price']);
        $this->assertSame(80.0, $line['unit_price']);
        $this->assertSame(160.0, $line['line_total']);
        // snapshot เรียงตามลำดับกลุ่ม: เนื้อสัตว์ก่อน แล้วค่อยของเพิ่ม
        $this->assertSame(['กุ้ง', 'ไข่ดาว'], array_column($line['selected_options'], 'name'));
    }

    public function test_option_id_from_another_listing_is_rejected(): void
    {
        $otherListing = FreshMarketListing::create([
            'seller_id' => $this->seller->id,
            'title' => 'ข้าวผัด',
            'price' => 45,
            'unit' => 'จาน',
            'quantity_available' => 10,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
        $otherGroup = FreshMarketListingOptionGroup::create(['listing_id' => $otherListing->id, 'name' => 'ท็อปปิ้ง', 'selection_type' => 'multi']);
        $foreign = FreshMarketListingOption::create([
            'group_id' => $otherGroup->id,
            'listing_id' => $otherListing->id,
            'name' => 'ส่วนลดปลอม',
            'price_delta' => 0,
        ]);

        $this->assertFreshMarketError('INVALID_OPTION', fn () => $this->placeKrapao([$this->opt['pork']->id, $foreign->id]));
        $this->assertSame(0, FreshMarketOrder::count());
    }

    public function test_missing_required_group_is_rejected(): void
    {
        $this->assertFreshMarketError('OPTION_REQUIRED', fn () => $this->placeKrapao([$this->opt['egg']->id]));
        $this->assertFreshMarketError('OPTION_REQUIRED', fn () => $this->placeKrapao([]));
        $this->assertSame(0, FreshMarketOrder::count());
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));
    }

    public function test_group_selection_limits_are_enforced(): void
    {
        // เลือกเดียว แต่ส่งมาสองอย่าง
        $this->assertFreshMarketError('OPTION_LIMIT', fn () => $this->placeKrapao([$this->opt['pork']->id, $this->opt['shrimp']->id]));

        // หลายอย่างแต่สูงสุด 1
        $this->assertFreshMarketError('OPTION_LIMIT', fn () => $this->placeKrapao([
            $this->opt['pork']->id, $this->opt['egg']->id, $this->opt['cheese']->id,
        ]));
    }

    public function test_unavailable_option_is_rejected(): void
    {
        $this->opt['shrimp']->update(['is_available' => false]);

        $this->assertFreshMarketError('OPTION_UNAVAILABLE', fn () => $this->placeKrapao([$this->opt['shrimp']->id]));
    }

    public function test_line_channel_without_options_uses_cheapest_required_option(): void
    {
        $order = $this->service->createOrder($this->buyer, $this->krapao, [
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'payment_method' => 'cod',
            'channel' => 'line',
        ]);

        $item = $order->items->first();
        $this->assertSame(50.0, (float) $order->total_amount);
        $this->assertSame('หมูสับ', $item->selected_options[0]['name']);
    }

    // =====================================================
    // ออเดอร์หลายรายการ
    // =====================================================

    public function test_multi_item_order_totals_stock_and_snapshot(): void
    {
        $order = $this->service->createOrderFromItems($this->buyer, $this->seller->id, [
            ['listing_id' => $this->krapao->id, 'quantity' => 2, 'option_ids' => [$this->opt['squid']->id], 'note' => 'ไม่เผ็ด'],
            ['listing_id' => $this->vegetable->id, 'quantity' => 1],
            // ราคาที่ client ส่งมาต้องถูกเมิน
            ['listing_id' => $this->krapao->id, 'quantity' => 1, 'option_ids' => [$this->opt['shrimp']->id, $this->opt['egg']->id], 'unit_price' => 1],
        ], [
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
            'channel' => 'api',
        ]);

        // (50+10)×2 + 50×1 + (50+20+10)×1 = 120 + 50 + 80 = 250
        $this->assertSame(250.0, (float) $order->total_amount);
        $this->assertSame(25.0, (float) $order->platform_fee);
        $this->assertSame(225.0, (float) $order->seller_earning);
        $this->assertSame(10.0, (float) $order->gp_rate);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(750.0, $this->balanceOf($this->buyer));

        $items = $order->items;
        $this->assertCount(3, $items);
        $this->assertSame([120.0, 50.0, 80.0], $items->map(fn ($i) => (float) $i->line_total)->all());
        $this->assertSame('ไม่เผ็ด', $items[0]->note);
        $this->assertSame(['กุ้ง', 'ไข่ดาว'], array_column($items[2]->selected_options, 'name'));

        // คอลัมน์เดิม = รายการแรก
        $this->assertSame((int) $this->krapao->id, (int) $order->listing_id);
        $this->assertSame(2, (int) $order->quantity);
        $this->assertSame(60.0, (float) $order->unit_price);

        // สต็อก: ผักตัด 1 / กะเพราทำตามสั่งไม่แตะ
        $this->assertSame(4, (int) $this->vegetable->fresh()->quantity_available);
        $this->assertSame(0, (int) $this->krapao->fresh()->quantity_available);
        $this->assertSame('active', $this->krapao->fresh()->status);

        // สรุปให้ไรเดอร์/แจ้งเตือนมีทุกรายการ
        $summary = $order->riderItemsSummary();
        $this->assertStringContainsString('ผัดกะเพราราดข้าว (หมึก) x2', $summary);
        $this->assertStringContainsString('ผักบุ้งจีน x1', $summary);

        // API ของผู้ซื้อมี items + ตัวเลขเป็น number
        $api = $order->toApiArray('buyer');
        $this->assertCount(3, $api['items']);
        $this->assertSame(250.0, $api['subtotal']);
        $this->assertSame(4, $api['items_count']);
        $this->assertIsFloat($api['items'][0]['unit_price']);
        $this->assertArrayNotHasKey('platform_fee', $api['items'][0]);
    }

    public function test_multi_item_order_rejects_listing_from_another_shop(): void
    {
        $otherUser = User::factory()->create();
        $otherShop = FreshMarketSeller::create([
            'user_id' => $otherUser->id,
            'shop_name' => 'ร้านอื่น',
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        $otherListing = FreshMarketListing::create([
            'seller_id' => $otherShop->id,
            'title' => 'มะนาว',
            'price' => 5,
            'unit' => 'ลูก',
            'quantity_available' => 50,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);

        $this->assertFreshMarketError('MIXED_SELLERS', fn () => $this->service->createOrderFromItems($this->buyer, $this->seller->id, [
            ['listing_id' => $this->vegetable->id, 'quantity' => 1],
            ['listing_id' => $otherListing->id, 'quantity' => 1],
        ], ['delivery_type' => 'pickup', 'payment_method' => 'cod']));

        $this->assertSame(5, (int) $this->vegetable->fresh()->quantity_available);
    }

    public function test_cancel_multi_item_order_restores_only_tracked_stock_once(): void
    {
        $order = $this->service->createOrderFromItems($this->buyer, $this->seller->id, [
            ['listing_id' => $this->vegetable->id, 'quantity' => 2],
            ['listing_id' => $this->krapao->id, 'quantity' => 1, 'option_ids' => [$this->opt['pork']->id]],
            ['listing_id' => $this->vegetable->id, 'quantity' => 1],
        ], ['delivery_type' => 'pickup', 'payment_method' => 'wallet']);

        $this->assertSame(2, (int) $this->vegetable->fresh()->quantity_available);

        $this->service->cancelOrder($order, 'เปลี่ยนใจ', 'buyer', $this->buyer);
        $this->service->cancelOrder($order->fresh(), 'กดซ้ำ', 'buyer', $this->buyer);

        $this->assertSame(5, (int) $this->vegetable->fresh()->quantity_available);
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));
        $this->assertSame(0, FreshMarketOrderItem::where('order_id', $order->id)->where('stock_deducted', true)->count());
    }

    public function test_multi_item_order_over_stock_is_rejected_atomically(): void
    {
        $this->assertFreshMarketError('OUT_OF_STOCK', fn () => $this->service->createOrderFromItems($this->buyer, $this->seller->id, [
            ['listing_id' => $this->vegetable->id, 'quantity' => 3],
            ['listing_id' => $this->vegetable->id, 'quantity' => 3],
        ], ['delivery_type' => 'pickup', 'payment_method' => 'wallet']));

        $this->assertSame(5, (int) $this->vegetable->fresh()->quantity_available);
        $this->assertSame(0, FreshMarketOrder::count());
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));
    }

    // =====================================================
    // ตะกร้า → ออเดอร์ (API)
    // =====================================================

    public function test_cart_to_order_via_api(): void
    {
        Sanctum::actingAs($this->buyer);

        $this->postJson('/api/v1/fresh-market/cart/items', [
            'listing_id' => $this->krapao->id,
            'quantity' => 1,
            'option_ids' => [$this->opt['shrimp']->id, $this->opt['egg']->id],
            'price' => 1, // ราคาปลอมจาก client ต้องไม่มีผล
        ])->assertCreated()
            // json_encode ตัด .0 ของเลขทศนิยมที่ลงตัว → ได้ int (ยังเป็น JSON number)
            ->assertJsonPath('data.shops.0.items.0.unit_price', 80)
            ->assertJsonPath('data.shops.0.subtotal', 80);

        // หยิบของเดิม+ตัวเลือกเดิมซ้ำ = เพิ่มจำนวนในบรรทัดเดิม
        $this->postJson('/api/v1/fresh-market/cart/items', [
            'listing_id' => $this->krapao->id,
            'quantity' => 1,
            'option_ids' => [$this->opt['egg']->id, $this->opt['shrimp']->id],
        ])->assertCreated();

        $this->postJson('/api/v1/fresh-market/cart/items', [
            'listing_id' => $this->vegetable->id,
            'quantity' => 2,
        ])->assertCreated();

        $cart = $this->getJson('/api/v1/fresh-market/cart')
            ->assertOk()
            ->assertJsonPath('data.shops_count', 1)
            ->assertJsonPath('data.lines_count', 2)
            ->assertJsonPath('data.items_count', 4)
            ->assertJsonPath('data.subtotal', 260)
            ->assertJsonPath('data.shops.0.can_checkout', true)
            ->json('data');

        $this->assertSame(2, $cart['shops'][0]['items'][0]['quantity']);

        // ตัวเลือกบังคับไม่ครบ → เพิ่มลงตะกร้าไม่ได้
        $this->postJson('/api/v1/fresh-market/cart/items', [
            'listing_id' => $this->krapao->id,
            'quantity' => 1,
        ])->assertStatus(422)->assertJsonPath('code', 'OPTION_REQUIRED');

        $response = $this->postJson('/api/v1/fresh-market/orders', [
            'seller_id' => $this->seller->id,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ])->assertCreated()
            ->assertJsonPath('data.total_amount', 260)
            ->assertJsonPath('data.items_count', 4);

        $this->assertCount(2, $response->json('data.items'));
        $this->assertSame(0, FreshMarketCartItem::where('user_id', $this->buyer->id)->count());
        $this->assertSame(740.0, $this->balanceOf($this->buyer));
        $this->assertSame(3, (int) $this->vegetable->fresh()->quantity_available);

        // กดสั่งซ้ำ → ไม่มีของในตะกร้าแล้ว ไม่เกิดออเดอร์ที่สอง
        $this->postJson('/api/v1/fresh-market/orders', [
            'seller_id' => $this->seller->id,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ])->assertStatus(422)->assertJsonPath('code', 'CART_EMPTY');

        $this->assertSame(1, FreshMarketOrder::count());
        $this->assertSame(1, WalletTransaction::where('reference_type', FreshMarketService::REF_PAYMENT)->count());
    }

    public function test_cart_update_merge_and_clear(): void
    {
        $cart = app(FreshMarketCartService::class);
        $a = $cart->addItem($this->buyer, $this->krapao->id, 1, [$this->opt['pork']->id]);
        $b = $cart->addItem($this->buyer, $this->krapao->id, 2, [$this->opt['chicken']->id]);

        // เปลี่ยนตัวเลือกของ b ให้ตรงกับ a → รวมเป็นบรรทัดเดียว
        $merged = $cart->updateItem($this->buyer, $b->id, ['option_ids' => [$this->opt['pork']->id]]);
        $this->assertSame((int) $a->id, (int) $merged->id);
        $this->assertSame(3, (int) $merged->fresh()->quantity);
        $this->assertSame(1, FreshMarketCartItem::where('user_id', $this->buyer->id)->count());

        // เกินสต็อกของที่ตัดสต็อก
        $this->assertFreshMarketError('OUT_OF_STOCK', fn () => $cart->addItem($this->buyer, $this->vegetable->id, 6));

        // จำนวน 0 = ลบ
        $this->assertNull($cart->updateItem($this->buyer, $a->id, ['quantity' => 0]));
        $this->assertSame(0, $cart->cartFor($this->buyer)['lines_count']);

        // ผู้ใช้อื่นแก้บรรทัดของคนอื่นไม่ได้
        $c = $cart->addItem($this->buyer, $this->vegetable->id, 1);
        $stranger = User::factory()->create();
        $this->assertFreshMarketError('CART_ITEM_NOT_FOUND', fn () => $cart->updateItem($stranger, $c->id, ['quantity' => 3]));

        $this->assertSame(1, $cart->clear($this->buyer, $this->seller->id));
    }

    public function test_cart_line_shows_issue_when_option_becomes_unavailable(): void
    {
        $cart = app(FreshMarketCartService::class);
        $cart->addItem($this->buyer, $this->krapao->id, 1, [$this->opt['shrimp']->id]);
        $this->opt['shrimp']->update(['is_available' => false]);

        $shop = $cart->cartFor($this->buyer)['shops'][0];

        $this->assertFalse($shop['can_checkout']);
        $this->assertSame('OPTION_UNAVAILABLE', $shop['items'][0]['issue']['code']);
        $this->assertSame(0.0, $shop['subtotal']);
    }

    public function test_legacy_single_listing_api_order_still_works(): void
    {
        Sanctum::actingAs($this->buyer);

        $this->postJson('/api/v1/fresh-market/orders', [
            'listing_id' => $this->vegetable->id,
            'quantity' => 2,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ])->assertCreated()
            ->assertJsonPath('data.total_amount', 100)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.listing.id', $this->vegetable->id)
            ->assertJsonPath('data.items.0.line_total', 100);

        // สินค้าที่มีตัวเลือกบังคับ ส่งแบบเดิมโดยไม่เลือก → แจ้งให้เลือก
        $this->postJson('/api/v1/fresh-market/orders', [
            'listing_id' => $this->krapao->id,
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ])->assertStatus(422)->assertJsonPath('code', 'OPTION_REQUIRED');

        // แบบเดิม + option_ids
        $this->postJson('/api/v1/fresh-market/orders', [
            'listing_id' => $this->krapao->id,
            'quantity' => 1,
            'option_ids' => [$this->opt['squid']->id],
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ])->assertCreated()->assertJsonPath('data.total_amount', 60);
    }

    public function test_listing_api_exposes_option_groups(): void
    {
        $this->getJson('/api/v1/fresh-market/listings/'.$this->krapao->id)
            ->assertOk()
            ->assertJsonPath('data.track_stock', false)
            ->assertJsonPath('data.max_order_quantity', 999)
            ->assertJsonPath('data.has_options', true)
            ->assertJsonPath('data.option_groups.0.name', 'เลือกเนื้อสัตว์')
            ->assertJsonPath('data.option_groups.0.min_select', 1)
            ->assertJsonPath('data.option_groups.0.max_select', 1)
            ->assertJsonPath('data.option_groups.0.options.3.price_delta', 20)
            ->assertJsonPath('data.option_groups.1.selection_type', 'multi');

        // สินค้าทำตามสั่งที่จำนวนเป็น 0 ยังแสดงในรายการ
        $ids = collect($this->getJson('/api/v1/fresh-market/listings')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertContains($this->krapao->id, $ids);
    }

    // =====================================================
    // ร้านจัดการตัวเลือก
    // =====================================================

    public function test_seller_syncs_option_groups_and_other_seller_cannot(): void
    {
        Sanctum::actingAs($this->sellerUser);

        $response = $this->putJson('/api/v1/fresh-market/listings/'.$this->vegetable->id.'/option-groups', [
            'option_groups' => [
                [
                    'name' => 'ขนาด',
                    'selection_type' => 'single',
                    'is_required' => true,
                    'options' => [
                        ['name' => 'กำเล็ก', 'price_delta' => 0],
                        ['name' => 'กำใหญ่', 'price_delta' => 15],
                    ],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.option_groups.0.name', 'ขนาด')
            ->assertJsonPath('data.option_groups.0.is_required', true);

        $group = $response->json('data.option_groups.0');

        // แก้ชื่อตัวเลือกเดิม (มี id) + ลบอีกตัว (ไม่ได้ส่ง) + เพิ่มกลุ่มใหม่
        $this->putJson('/api/v1/fresh-market/listings/'.$this->vegetable->id.'/option-groups', [
            'option_groups' => [
                [
                    'id' => $group['id'],
                    'name' => 'ขนาดกำ',
                    'selection_type' => 'single',
                    'is_required' => true,
                    'options' => [
                        ['id' => $group['options'][1]['id'], 'name' => 'กำจัมโบ้', 'price_delta' => 20],
                    ],
                ],
                [
                    'name' => 'แถม',
                    'selection_type' => 'multi',
                    'options' => [['name' => 'พริก', 'price_delta' => 0]],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.option_groups.0.id', $group['id'])
            ->assertJsonPath('data.option_groups.0.options.0.id', $group['options'][1]['id'])
            ->assertJsonPath('data.option_groups.0.options.0.name', 'กำจัมโบ้')
            ->assertJsonPath('data.option_groups.1.is_required', false);

        $this->assertSame(1, FreshMarketListingOption::where('group_id', $group['id'])->count());

        // ร้านอื่นแก้ไม่ได้
        $otherUser = User::factory()->create();
        FreshMarketSeller::create([
            'user_id' => $otherUser->id,
            'shop_name' => 'ร้านคู่แข่ง',
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        Sanctum::actingAs($otherUser);

        $this->putJson('/api/v1/fresh-market/option-groups/'.$group['id'], ['name' => 'แฮก'])
            ->assertStatus(404)
            ->assertJsonPath('code', 'OPTION_GROUP_NOT_FOUND');
        $this->putJson('/api/v1/fresh-market/listings/'.$this->vegetable->id.'/option-groups', ['option_groups' => []])
            ->assertStatus(404);

        $this->assertSame('ขนาดกำ', FreshMarketListingOptionGroup::find($group['id'])->name);
    }

    public function test_seller_creates_listing_with_options_via_api(): void
    {
        $category = FreshMarketCategory::create(['name' => 'อาหารพร้อมทาน', 'slug' => 'ready-meals-test', 'is_active' => true]);
        Sanctum::actingAs($this->sellerUser);

        $this->postJson('/api/v1/fresh-market/listings', [
            'title' => 'ข้าวไข่เจียว',
            'category_id' => $category->id,
            'price' => 35,
            'unit' => 'จาน',
            'track_stock' => false,
            'option_groups' => [
                ['name' => 'เพิ่ม', 'selection_type' => 'multi', 'max_select' => 2, 'options' => [
                    ['name' => 'หมูสับ', 'price_delta' => 10],
                    ['name' => 'แหนม', 'price_delta' => 10],
                ]],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.track_stock', false)
            ->assertJsonPath('data.option_groups.0.max_select', 2)
            ->assertJsonPath('data.option_groups.0.options.1.name', 'แหนม');
    }

    public function test_web_listing_form_saves_groups_with_images_on_the_right_rows(): void
    {
        Storage::fake('public');
        $category = FreshMarketCategory::create(['name' => 'อาหารพร้อมทาน', 'slug' => 'ready-meals-web', 'is_active' => true]);

        // key ของแถวไม่เรียงต่อกัน (ผู้ใช้ลบแถวกลางในฟอร์มไปแล้ว) → รูปต้องไปอยู่กับตัวเลือกที่ถูกต้อง
        $this->actingAs($this->sellerUser)->post(route('taladsod.listing.store'), [
            'title' => 'ข้าวผัดกระเพรา',
            'category_id' => $category->id,
            'price' => 45,
            'unit' => 'จาน',
            'track_stock' => '0',
            'option_groups' => [
                3 => [
                    'name' => 'เนื้อ',
                    'selection_type' => 'single',
                    'is_required' => '1',
                    'options' => [
                        4 => ['name' => 'หมู', 'price_delta' => '0', 'is_available' => '1'],
                        7 => ['name' => 'กุ้ง', 'price_delta' => '20', 'is_available' => '1', 'image' => UploadedFile::fake()->image('shrimp.jpg', 40, 40)],
                    ],
                ],
            ],
        ])->assertRedirect(route('taladsod.seller.dashboard'));

        $listing = FreshMarketListing::where('title', 'ข้าวผัดกระเพรา')->firstOrFail();
        $this->assertFalse($listing->tracksStock());

        $options = $listing->optionGroups()->with('options')->first()->options;
        $this->assertSame(['หมู', 'กุ้ง'], $options->pluck('name')->all());
        $this->assertNull($options[0]->image_url);
        $this->assertStringStartsWith('/storage/fresh-market/options/', (string) $options[1]->image_url);
        Storage::disk('public')->assertExists(substr($options[1]->image_url, strlen('/storage/')));
    }

    // =====================================================
    // หน้าเว็บ (session)
    // =====================================================

    public function test_web_cart_and_checkout(): void
    {
        $this->actingAs($this->buyer)
            ->postJson(route('taladsod.cart.items.store'), [
                'listing_id' => $this->krapao->id,
                'quantity' => 2,
                'option_ids' => [$this->opt['chicken']->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal', 100);

        $this->actingAs($this->buyer)
            ->getJson(route('taladsod.cart.data'))
            ->assertOk()
            ->assertJsonPath('data.items_count', 2);

        // หน้าเว็บตะกร้า/ยืนยันสั่งซื้อเปิดได้ (มี view แล้วหรือยังตอบ JSON ชั่วคราว ก็ต้องไม่ error)
        $this->actingAs($this->buyer)->get(route('taladsod.cart'))->assertOk();
        $this->actingAs($this->buyer)->get(route('taladsod.checkout', ['seller' => $this->seller->id]))->assertOk();

        // ร้านที่ไม่มีของในตะกร้า → กลับหน้าตะกร้า
        $this->actingAs($this->buyer)
            ->get(route('taladsod.checkout', ['seller' => 999999]))
            ->assertRedirect(route('taladsod.cart'));

        $response = $this->actingAs($this->buyer)->post(route('taladsod.checkout.store'), [
            'seller_id' => $this->seller->id,
            'delivery_type' => 'pickup',
            'payment_method' => 'cod',
        ]);

        $order = FreshMarketOrder::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('taladsod.orders.show', $order));
        $this->assertSame(100.0, (float) $order->total_amount);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame(0, FreshMarketCartItem::count());
    }

    // =====================================================
    // เมนูเปิดตัว (data migration)
    // =====================================================

    public function test_launch_menu_migration_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_09_26_110100_seed_launch_menu_pad_kra_pao.php');

        // ไม่มีแอดมิน → ข้าม (ไม่ล้ม)
        $migration->up();
        $this->assertSame(0, FreshMarketListing::withTrashed()->where('slug', 'pad-kra-pao-rad-khao')->count());

        $admin = User::factory()->create(['name' => 'แอดมิน']);
        $admin->forceFill(['role' => 'admin'])->save();

        $migration->up();
        $migration->up();

        $listings = FreshMarketListing::withTrashed()->where('slug', 'pad-kra-pao-rad-khao')->get();
        $this->assertCount(1, $listings);

        $listing = $listings->first();
        $this->assertFalse($listing->tracksStock());
        $this->assertSame(50.0, (float) $listing->price);
        $this->assertSame('/images/taladsod/krapao-hero.webp', $listing->main_image_url);
        $this->assertCount(6, $listing->images);
        $this->assertSame('อาหารพร้อมทาน', $listing->category->name);

        $shop = $listing->seller;
        $this->assertSame('ครัวไทยพร้อม', $shop->shop_name);
        $this->assertSame((int) $admin->id, (int) $shop->user_id);
        $this->assertTrue($shop->is_verified);
        $this->assertFalse($shop->hasPickupLocation());
        $this->assertSame(1, FreshMarketSeller::where('shop_name', 'ครัวไทยพร้อม')->count());
        $this->assertSame(1, FreshMarketCategory::where('name', 'อาหารพร้อมทาน')->count());

        $groups = $listing->optionGroups()->with('options')->get();
        $this->assertSame(['เลือกเนื้อสัตว์', 'เพิ่มเติม'], $groups->pluck('name')->all());
        $this->assertSame(['หมูสับ', 'ไก่', 'หมึก', 'กุ้ง'], $groups[0]->options->pluck('name')->all());
        $this->assertSame([0.0, 0.0, 10.0, 20.0], $groups[0]->options->map(fn ($o) => (float) $o->price_delta)->all());
        $this->assertSame('/images/taladsod/krapao-shrimp.webp', $groups[0]->options[3]->image_url);
        $this->assertSame(1, $groups[1]->maxAllowed());
        $this->assertSame(0, $groups[1]->minRequired());

        // ราคาเมนูจริง: กุ้ง + ไข่ดาว = 80
        $line = app(FreshMarketOptionService::class)->resolveLine($listing, [
            $groups[0]->options[3]->id, $groups[1]->options[0]->id,
        ], 1);
        $this->assertSame(80.0, $line['unit_price']);
        $this->assertSame('/images/taladsod/krapao-shrimp.webp', $line['image_url']);
    }

    // =====================================================
    // Helpers
    // =====================================================

    protected function placeKrapao(array $optionIds): FreshMarketOrder
    {
        return $this->service->createOrder($this->buyer, $this->krapao, [
            'quantity' => 1,
            'option_ids' => $optionIds,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
            'channel' => 'api',
        ]);
    }

    protected function assertFreshMarketError(string $code, callable $callback): void
    {
        try {
            $callback();
            $this->fail("คาดว่าจะได้ FreshMarketException {$code}");
        } catch (FreshMarketException $e) {
            $this->assertSame($code, $e->errorCode(), $e->getMessage());
        }
    }

    protected function balanceOf(User $user): float
    {
        return round((float) Wallet::where('user_id', $user->id)->value('balance'), 2);
    }
}
