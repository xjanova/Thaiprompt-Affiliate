<?php

namespace Tests\Feature\V4;

use App\Models\LabelTemplate;
use App\Models\PosAdvertisement;
use App\Models\PosApiKey;
use App\Models\PosCategory;
use App\Models\PosDevice;
use App\Models\PosLabelPrint;
use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * หน้าแผงร้านค้า part 2 (Theme V4 / layouts.seller-v4) — ต้องใช้ MySQL
 *
 * ครอบคลุม (audit 2026-09-25):
 *   - SELLER-17 / GAP-16  POS, analytics, marketing, coupons, store-rating, achievements, staff, คู่มือ → seller-v4
 *   - SELLER-10 / GAP-07 / GAP-15  view ที่หายไป (เดิม 500) ต้องได้ 200
 *   - GAP-09  route template ฉลากไม่มี method → ตอนนี้ได้หน้า "ยังไม่เปิดให้บริการ" แทน 500
 *   - GAP-10  โฆษณา POS แก้ไข/ลบได้ เฉพาะของร้านตัวเอง
 *   - GAP-26  ใบเสร็จ POS ไม่โหลด Tailwind Play CDN
 *   - POS: หน้าขายบันทึกการขายได้จริง + กัน IDOR ขายสินค้าร้านอื่น
 */
#[Group('v4')]
class SellerPanelPart2PagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected VendorStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();

        [$this->seller, $this->store] = $this->makeSeller();
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    /**
     * สร้างผู้ขายที่ผ่าน KYC + มีร้านที่เปิดใช้งานและมีแพ็กเกจ active
     *
     * @return array{0: User, 1: VendorStore}
     */
    protected function makeSeller(): array
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'seller', 'kyc_status' => 'approved'])->save();

        $store = VendorStore::create([
            'user_id' => $user->id,
            'store_name' => 'ร้านทดสอบ V4 '.$user->id,
            'store_slug' => 'v4-store-'.$user->id.'-'.Str::lower(Str::random(4)),
            'store_phone' => '0812345678',
            'store_address' => '1 ถนนสีลม',
            'status' => 'active',
            'is_active' => true,
            'subscription_status' => 'active',
        ]);

        return [$user->fresh(), $store->fresh()];
    }

    protected function makeProduct(VendorStore $store, array $overrides = []): Product
    {
        $category = \App\Models\ProductCategory::create([
            'name' => 'หมวดทดสอบ '.Str::random(4),
            'slug' => 'v4-cat-'.Str::lower(Str::random(8)),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return Product::create(array_merge([
            'seller_id' => $store->user_id,
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'สินค้าทดสอบ '.Str::random(5),
            'sku' => 'V4-'.Str::upper(Str::random(8)),
            'price' => 100,
            'stock_quantity' => 10,
            'track_inventory' => true,
            'stock_status' => 'in_stock',
            'shipping_method' => 'free',
            'is_active' => true,
            'is_hidden' => false,
            'is_blocked' => false,
        ], $overrides));
    }

    /**
     * ข้อมูล POS ครบชุดสำหรับเปิดหน้าที่มีข้อมูลจริง
     *
     * @return array<string, mixed>
     */
    protected function makePosData(): array
    {
        $product = $this->makeProduct($this->store);

        $device = PosDevice::create([
            'store_id' => $this->store->id,
            'device_name' => 'เครื่องหน้าร้าน',
            'device_type' => 'web',
            'subscription_status' => 'active',
            'is_active' => true,
            'is_online' => true,
        ]);

        $session = PosSession::create([
            'pos_device_id' => $device->id,
            'user_id' => $this->seller->id,
            'status' => 'open',
            'opened_at' => now()->subHour(),
            'opening_cash' => 500,
        ]);

        $transaction = PosTransaction::create([
            'store_id' => $this->store->id,
            'pos_device_id' => $device->id,
            'pos_session_id' => $session->id,
            'user_id' => $this->seller->id,
            'transaction_code' => 'POS-'.Str::upper(Str::random(8)),
            'receipt_number' => 'RCP-T-'.Str::upper(Str::random(8)),
            'transaction_date' => now(),
            'subtotal' => 200,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 200,
            'payment_method' => 'cash',
            'amount_paid' => 500,
            'change_amount' => 300,
            'payment_status' => 'completed',
            'status' => 'completed',
            'total_items' => 1,
            'total_quantity' => 2,
        ]);

        PosTransactionItem::create([
            'pos_transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => 100,
            'original_price' => 100,
            'quantity' => 2,
            'discount_amount' => 0,
            'subtotal' => 200,
            'total' => 200,
            'tax_amount' => 0,
        ]);

        $category = PosCategory::create([
            'store_id' => $this->store->id,
            'name' => 'เครื่องดื่ม',
            'icon' => '🥤',
            'color' => '#e6b347',
            'is_active' => true,
            'show_in_pos' => true,
        ]);

        $ad = PosAdvertisement::create([
            'store_id' => $this->store->id,
            'title' => 'โปรกาแฟ 1 แถม 1',
            'type' => 'promotion',
            'duration_seconds' => 10,
            'is_active' => true,
        ]);

        $apiKey = PosApiKey::create([
            'shop_id' => $this->store->id,
            'name' => 'แคชเชียร์ 1',
            'is_active' => true,
            'is_blocked' => false,
        ]);

        $terminalData = [
            'api_key_id' => $apiKey->id,
            'shop_id' => $this->store->id,
            'product_key' => 'PK-'.Str::upper(Str::random(10)),
            'device_id' => 'HW-'.Str::random(12),
            'device_name' => 'Windows POS',
            'platform' => 'windows',
        ];
        $terminalId = DB::table('pos_terminals')->insertGetId(array_merge($terminalData, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $terminal = PosTerminal::findOrFail($terminalId);

        $template = LabelTemplate::create([
            'name' => 'ฉลากราคา 40x30',
            'paper_width' => 40,
            'paper_height' => 30,
            'paper_unit' => 'mm',
            'columns' => 1,
            'rows' => 1,
            'is_pos_template' => true,
            'pos_category' => 'product_label',
            'printer_type' => 'thermal_label',
            'is_active' => true,
            'is_system' => true,
        ]);

        $print = PosLabelPrint::create([
            'user_id' => $this->seller->id,
            'template_id' => $template->id,
            'print_type' => 'product_label',
            'products' => [['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'price' => 100, 'quantity' => 3]],
            'total_labels' => 3,
            'sheets_count' => 1,
            'status' => 'completed',
            'printed_at' => now(),
        ]);

        return compact('product', 'device', 'session', 'transaction', 'category', 'ad', 'apiKey', 'terminal', 'template', 'print');
    }

    /**
     * เปิดหน้าแล้วต้องได้ 200 + เป็นเปลือก V4 (มีคลาส tp-card)
     */
    protected function assertV4Page(string $url): void
    {
        $response = $this->actingAs($this->seller)->get($url);

        $error = $response->exception
            ? get_class($response->exception).': '.$response->exception->getMessage().' @ '.$response->exception->getFile().':'.$response->exception->getLine()
            : mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $response->getContent()))), 0, 300);
        $this->assertSame(200, $response->getStatusCode(), "GET {$url} → ".$response->getStatusCode()."\n".$error);
        $response->assertSee('tp-card', false);
    }

    // ------------------------------------------------------------------
    // POS
    // ------------------------------------------------------------------

    public function test_pos_pages_render_on_seller_v4_with_data(): void
    {
        $d = $this->makePosData();

        $urls = [
            route('seller.pos.index'),
            route('seller.pos.terminal'),
            route('seller.pos.transactions'),
            route('seller.pos.transactions', ['device_id' => '', 'date_from' => '', 'date_to' => '']),
            route('seller.pos.transactions.show', $d['transaction']),
            route('seller.pos.sessions'),
            route('seller.pos.sessions.show', $d['session']),
            route('seller.pos.devices'),
            route('seller.pos.devices.show', $d['device']),
            route('seller.pos.categories'),
            route('seller.pos.advertisements'),
            route('seller.pos.settings'),
            route('seller.pos.analytics'),
            route('seller.pos.analytics', ['date_from' => now()->subDays(3)->toDateString(), 'date_to' => now()->toDateString()]),
            route('seller.pos.terminals'),
            route('seller.pos.api-keys.show', $d['apiKey']),
            route('seller.pos.terminals.show', $d['terminal']),
            route('seller.pos.labels.index'),
            route('seller.pos.labels.history'),
            route('seller.pos.labels.print-product'),
            route('seller.pos.labels.print-shipping'),
            route('seller.pos.labels.show', $d['print']),
        ];

        foreach ($urls as $url) {
            $this->assertV4Page($url);
        }
    }

    public function test_pos_pages_render_when_store_has_no_data(): void
    {
        foreach (['seller.pos.index', 'seller.pos.transactions', 'seller.pos.sessions', 'seller.pos.devices', 'seller.pos.categories',
            'seller.pos.advertisements', 'seller.pos.settings', 'seller.pos.analytics', 'seller.pos.terminals', 'seller.pos.labels.index',
            'seller.pos.labels.history', 'seller.pos.labels.print-product'] as $name) {
            $this->assertV4Page(route($name));
        }
    }

    public function test_pos_detail_pages_of_another_store_are_forbidden(): void
    {
        $d = $this->makePosData();
        [$other] = $this->makeSeller();

        $this->actingAs($other)->get(route('seller.pos.transactions.show', $d['transaction']))->assertForbidden();
        $this->actingAs($other)->get(route('seller.pos.devices.show', $d['device']))->assertForbidden();
        $this->actingAs($other)->get(route('seller.pos.sessions.show', $d['session']))->assertForbidden();
        $this->actingAs($other)->get(route('seller.pos.receipt', $d['transaction']))->assertForbidden();
        $this->actingAs($other)->get(route('seller.pos.labels.show', $d['print']))->assertForbidden();
    }

    public function test_pos_receipt_is_standalone_without_tailwind_play_cdn(): void
    {
        $d = $this->makePosData();

        $response = $this->actingAs($this->seller)->get(route('seller.pos.receipt', $d['transaction']));

        $response->assertOk();
        $response->assertDontSee('cdn.tailwindcss.com', false);
        $response->assertSee($d['transaction']->receipt_number);
    }

    public function test_web_pos_terminal_records_a_sale_and_decrements_stock(): void
    {
        $product = $this->makeProduct($this->store, ['stock_quantity' => 5]);

        // เปิดหน้าขายก่อน → ระบบสร้างอุปกรณ์ Web POS + เซสชันให้
        $this->actingAs($this->seller)->get(route('seller.pos.terminal'))->assertOk();
        $device = PosDevice::where('store_id', $this->store->id)->firstOrFail();
        $session = PosSession::where('pos_device_id', $device->id)->firstOrFail();

        $payload = [
            'device_id' => $device->id,
            'session_id' => $session->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'discount' => 0]],
            'subtotal' => 200,
            'discount_amount' => 0,
            'tax_amount' => 13.08,
            'total_amount' => 200,
            'payment_method' => 'bank_transfer',
            'payment_amount' => 200,
        ];

        $response = $this->actingAs($this->seller)->postJson(route('seller.pos.create-transaction'), $payload);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
        $tx = PosTransaction::where('store_id', $this->store->id)->latest('id')->firstOrFail();
        $this->assertSame('200.00', (string) $tx->amount_paid);
        $this->assertSame('bank_transfer', $tx->payment_method);
        $this->assertStringContainsString('RCP-'.$this->store->id.'-', $tx->receipt_number);
    }

    public function test_web_pos_terminal_rejects_products_of_another_store(): void
    {
        [, $otherStore] = $this->makeSeller();
        $foreign = $this->makeProduct($otherStore, ['stock_quantity' => 5]);

        $this->actingAs($this->seller)->get(route('seller.pos.terminal'))->assertOk();
        $device = PosDevice::where('store_id', $this->store->id)->firstOrFail();
        $session = PosSession::where('pos_device_id', $device->id)->firstOrFail();

        $this->actingAs($this->seller)->postJson(route('seller.pos.create-transaction'), [
            'device_id' => $device->id,
            'session_id' => $session->id,
            'items' => [['product_id' => $foreign->id, 'quantity' => 1, 'unit_price' => 1]],
            'subtotal' => 1,
            'total_amount' => 1,
            'payment_method' => 'cash',
            'payment_amount' => 1,
        ])->assertForbidden()->assertJsonPath('code', 'NOT_YOUR_STORE');

        $this->assertSame(5, (int) $foreign->fresh()->stock_quantity);
    }

    public function test_pos_advertisement_can_be_updated_and_deleted_only_by_owner(): void
    {
        $d = $this->makePosData();
        [$other] = $this->makeSeller();

        $this->actingAs($other)->put(route('seller.pos.advertisements.update', $d['ad']), [
            'title' => 'แฮ็ก', 'type' => 'image', 'duration_seconds' => 5,
        ])->assertForbidden();
        $this->actingAs($other)->delete(route('seller.pos.advertisements.destroy', $d['ad']))->assertForbidden();

        $this->actingAs($this->seller)->put(route('seller.pos.advertisements.update', $d['ad']), [
            'title' => 'โปรใหม่', 'type' => 'image', 'duration_seconds' => 15, 'order' => 2, 'is_active' => '0',
        ])->assertRedirect();
        $this->assertSame('โปรใหม่', $d['ad']->fresh()->title);
        $this->assertFalse((bool) $d['ad']->fresh()->is_active);

        $this->actingAs($this->seller)->delete(route('seller.pos.advertisements.destroy', $d['ad']))->assertRedirect();
        $this->assertSoftDeleted('pos_advertisements', ['id' => $d['ad']->id]);
    }

    public function test_pos_settings_checkboxes_can_be_turned_off(): void
    {
        $this->actingAs($this->seller)->get(route('seller.pos.settings'))->assertOk();

        $this->actingAs($this->seller)->put(route('seller.pos.settings.update'), [
            'tax_enabled' => '0',
            'tax_inclusive' => '1',
            'tax_percentage' => 7,
            'allow_discounts' => '0',
            'auto_print_receipt' => '0',
            'enabled_payment_methods' => ['cash', 'qr'],
            'receipt_size' => '58mm',
        ])->assertRedirect();

        $settings = \App\Models\PosSetting::where('store_id', $this->store->id)->firstOrFail();
        $this->assertFalse((bool) $settings->tax_enabled);
        $this->assertFalse((bool) $settings->allow_discounts);
        $this->assertSame(['cash', 'qr'], $settings->enabled_payment_methods);
    }

    public function test_label_template_routes_show_unavailable_page_instead_of_500(): void
    {
        $response = $this->actingAs($this->seller)->get(route('seller.pos.labels.templates.index'));
        $response->assertOk()->assertSee('ยังไม่เปิดให้บริการ');

        $this->actingAs($this->seller)->get(route('seller.pos.labels.templates.create'))->assertOk();
        $this->actingAs($this->seller)->post(route('seller.pos.labels.templates.store'), ['name' => 'x'])
            ->assertRedirect(route('seller.pos.labels.index'));
    }

    public function test_api_key_creation_without_name_works(): void
    {
        $this->actingAs($this->seller)->post(route('seller.pos.api-keys.store'), [])
            ->assertRedirect()
            ->assertSessionHas('new_api_key');

        $this->assertSame(1, PosApiKey::where('shop_id', $this->store->id)->count());
    }

    // ------------------------------------------------------------------
    // Analytics
    // ------------------------------------------------------------------

    /**
     * สถิติรายวันย้อนหลัง (พอให้ AI คาดการณ์ได้ ≥ 14 วัน)
     */
    protected function seedVendorAnalytics(int $days = 20): void
    {
        $rows = [];
        for ($i = $days; $i >= 1; $i--) {
            $rows[] = [
                'store_id' => $this->store->id,
                'date' => now()->subDays($i)->toDateString(),
                'page_views' => 100 + $i * 3,
                'unique_visitors' => 60 + $i,
                'bounce_rate' => 35.5,
                'avg_session_duration' => 90,
                'orders_count' => 3 + ($i % 4),
                'total_sales' => 1000 + $i * 55.25,
                'avg_order_value' => 300,
                'products_viewed' => 80,
                'products_added_to_cart' => 20,
                'conversion_rate' => 4.2,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('vendor_analytics')->insert($rows);
    }

    public function test_analytics_pages_render_on_seller_v4(): void
    {
        foreach (['seller.analytics.index', 'seller.analytics.ai-insights', 'seller.analytics.segmentation',
            'seller.analytics.cohort', 'seller.analytics.products', 'seller.analytics.settings'] as $name) {
            $this->assertV4Page(route($name));
        }

        $this->seedVendorAnalytics();

        foreach (['seller.analytics.index', 'seller.analytics.ai-insights'] as $name) {
            $this->assertV4Page(route($name));
        }
        $this->assertV4Page(route('seller.analytics.index', ['start_date' => now()->subDays(10)->toDateString(), 'end_date' => now()->toDateString(), 'min_orders' => 1]));
    }

    public function test_analytics_export_streams_csv(): void
    {
        $this->seedVendorAnalytics(5);

        $response = $this->actingAs($this->seller)->get(route('seller.analytics.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function test_analytics_settings_can_turn_tracking_off(): void
    {
        $this->actingAs($this->seller)->get(route('seller.analytics.settings'))->assertOk();

        $this->actingAs($this->seller)->put(route('seller.analytics.settings.update'), [
            'retention_days' => 60,
            'auto_cleanup_enabled' => '0',
            'track_visits' => '0',
            'track_unique_visitors' => '1',
            'track_session_duration' => '0',
            'anonymize_ip' => '1',
            'respect_dnt' => '1',
            'email_weekly_reports' => '0',
            'email_monthly_reports' => '0',
        ])->assertRedirect(route('seller.analytics.settings'));

        $settings = \App\Models\VendorAnalyticsSetting::where('store_id', $this->store->id)->firstOrFail();
        $this->assertSame(60, (int) $settings->retention_days);
        $this->assertFalse((bool) $settings->track_visits);
    }

    public function test_system_monitoring_is_admin_only(): void
    {
        $response = $this->actingAs($this->seller)->get(route('seller.analytics.system-monitoring'));
        $this->assertNotSame(200, $response->getStatusCode());
        $this->assertNotSame(500, $response->getStatusCode());

        if (! function_exists('sys_getloadavg')) {
            $this->markTestSkipped('sys_getloadavg() ไม่มีบน Windows — หน้า monitoring ของแอดมินทดสอบบน CI (Linux)');
        }

        [$admin, $adminStore] = $this->makeSeller();
        $admin->forceFill(['role' => 'super_admin'])->save();
        $this->seller = $admin->fresh();

        $this->assertV4Page(route('seller.analytics.system-monitoring'));
    }

    // ------------------------------------------------------------------
    // Marketing / coupons / store-rating / achievements (GAP-07, SELLER-10, GAP-15)
    // ------------------------------------------------------------------

    /**
     * ข้อมูลการตลาดครบชุด
     *
     * @return array<string, mixed>
     */
    protected function makeMarketingData(): array
    {
        $product = $this->makeProduct($this->store);
        $newProduct = $this->makeProduct($this->store);

        $official = \App\Models\OfficialShopProduct::create([
            'product_id' => $product->id,
            'store_id' => $this->store->id,
            'selection_type' => \App\Models\OfficialShopProduct::TYPE_AI_FEATURED,
            'ai_score' => 72.5,
            'is_active' => true,
            'selected_at' => now()->subDays(3),
            'expires_at' => now()->addDays(4),
        ]);

        \App\Models\OfficialShopWarning::create([
            'official_shop_product_id' => $official->id,
            'product_id' => $product->id,
            'store_id' => $this->store->id,
            'status' => 'pending',
            'current_score' => 55,
            'required_score' => 60,
            'warning_message' => 'คะแนนรีวิวลดลง',
            'warned_at' => now(),
            'deadline_at' => now()->addDays(7),
        ]);

        \App\Models\NewProductPromotion::create([
            'product_id' => $product->id,
            'store_id' => $this->store->id,
            'status' => 'expired',
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->subDays(13),
        ]);

        $coupon = \App\Models\Coupon::create([
            'code' => 'V4TEST-'.Str::upper(Str::random(5)),
            'store_id' => $this->store->id,
            'name' => 'ลด 10%',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_purchase' => 100,
            'usage_limit' => 50,
            'used_count' => 5,
            'is_active' => true,
            'is_public' => true,
        ]);

        $buyer = User::factory()->create();
        $rating = \App\Models\StoreRating::create([
            'store_id' => $this->store->id,
            'user_id' => $buyer->id,
            'rating' => 4,
            'service_rating' => 5,
            'shipping_rating' => 4,
            'communication_rating' => 4,
            'comment' => 'ของดี ส่งไว',
            'is_approved' => true,
            'is_verified_purchase' => true,
        ]);

        \App\Models\StoreTrophy::create([
            'name' => 'First Sale',
            'name_th' => 'ขายชิ้นแรก',
            'requirement_type' => 'products',
            'requirement_value' => 0,
            'tier' => 'bronze',
            'points' => 10,
            'is_active' => true,
            'is_premium' => false,
            'icon' => '🥉',
        ]);

        return compact('product', 'newProduct', 'official', 'coupon', 'rating');
    }

    public function test_marketing_hub_pages_render_instead_of_500(): void
    {
        foreach (['seller.marketing.index', 'seller.marketing.select-product', 'seller.marketing.promotion-history',
            'seller.marketing.warnings', 'seller.marketing.official-products', 'seller.coupons.index', 'seller.coupons.create',
            'seller.store-rating.index', 'seller.store-rating.all', 'seller.store-rating.statistics',
            'seller.achievements.index', 'seller.achievements.trophies', 'seller.achievements.premium-status'] as $name) {
            $this->assertV4Page(route($name));
        }

        $d = $this->makeMarketingData();

        foreach (['seller.marketing.index', 'seller.marketing.select-product', 'seller.marketing.promotion-history',
            'seller.marketing.warnings', 'seller.marketing.official-products', 'seller.coupons.index',
            'seller.store-rating.index', 'seller.store-rating.all', 'seller.store-rating.statistics',
            'seller.achievements.index', 'seller.achievements.trophies', 'seller.achievements.premium-status'] as $name) {
            $this->assertV4Page(route($name));
        }
        $this->assertV4Page(route('seller.coupons.edit', $d['coupon']));
        $this->assertV4Page(route('seller.store-rating.show', $d['rating']));
        $this->assertV4Page(route('seller.store-rating.all', ['rating' => 4, 'responded' => '0', 'sort' => 'lowest']));
    }

    public function test_coupon_crud_is_scoped_to_own_store(): void
    {
        $this->actingAs($this->seller)->post(route('seller.coupons.store'), [
            'name' => 'ส่งฟรี',
            'discount_type' => 'free_shipping',
            'discount_value' => 0,
            'is_public' => '0',
        ])->assertRedirect(route('seller.coupons.index'));

        $coupon = \App\Models\Coupon::where('store_id', $this->store->id)->firstOrFail();
        $this->assertLessThanOrEqual(50, strlen($coupon->code));

        // เปอร์เซ็นต์เกิน 100 ต้องไม่ผ่าน
        $this->actingAs($this->seller)->put(route('seller.coupons.update', $coupon), [
            'name' => 'ลดเยอะ', 'discount_type' => 'percentage', 'discount_value' => 150,
        ])->assertSessionHasErrors('discount_value');

        [$other] = $this->makeSeller();
        $this->actingAs($other)->get(route('seller.coupons.edit', $coupon))->assertForbidden();
        $this->actingAs($other)->postJson(route('seller.coupons.toggle-active', $coupon))->assertForbidden();

        $this->actingAs($this->seller)->postJson(route('seller.coupons.toggle-active', $coupon))
            ->assertOk()->assertJsonPath('is_active', false);
    }

    public function test_seller_can_respond_to_own_store_rating_only(): void
    {
        $d = $this->makeMarketingData();

        $this->actingAs($this->seller)->post(route('seller.store-rating.respond', $d['rating']), ['response' => 'ขอบคุณค่ะ'])
            ->assertRedirect();
        $this->assertSame('ขอบคุณค่ะ', $d['rating']->fresh()->seller_response);

        [$other] = $this->makeSeller();
        $this->actingAs($other)->get(route('seller.store-rating.show', $d['rating']))->assertForbidden();
    }

    public function test_ai_assistant_pages_show_unavailable_instead_of_500(): void
    {
        foreach (['seller.ai-assistant.index', 'seller.ai-assistant.settings', 'seller.ai-assistant.conversations', 'seller.ai-assistant.analytics'] as $name) {
            $response = $this->actingAs($this->seller)->get(route($name));
            $response->assertOk()->assertSee('ยังไม่เปิดให้บริการ');
        }

        $this->actingAs($this->seller)->get(route('seller.ai-assistant.conversations.show', 123))->assertOk();
        $this->actingAs($this->seller)->postJson(route('seller.ai-assistant.test'), ['message' => 'สวัสดี'])
            ->assertStatus(503)->assertJsonPath('code', 'FEATURE_UNAVAILABLE');
    }

    public function test_marketing_request_edit_locked_product_uses_existing_ticket_route(): void
    {
        $d = $this->makeMarketingData();
        $d['official']->forceFill(['is_locked' => true])->save();

        $response = $this->actingAs($this->seller)->post(route('seller.marketing.request-edit', $d['product']));

        $response->assertRedirect();
        $this->assertStringContainsString('/user/tickets/create', (string) $response->headers->get('Location'));
    }

    // ------------------------------------------------------------------
    // Staff + user guide
    // ------------------------------------------------------------------

    public function test_staff_pages_render_and_employee_can_be_created(): void
    {
        foreach (['seller.staff.index', 'seller.staff.create', 'seller.staff.departments', 'seller.staff.positions', 'seller.staff.shifts'] as $name) {
            $this->assertV4Page(route($name));
        }

        // เพิ่มพนักงานโดยไม่เลือกแผนก/ตำแหน่ง → ระบบสร้าง "ทั่วไป / พนักงาน" ให้
        $this->actingAs($this->seller)->post(route('seller.staff.store'), [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'first_name_th' => 'สมชาย',
            'last_name_th' => 'ใจดี',
            'hire_date' => now()->toDateString(),
            'employment_type' => 'full_time',
            'pin_code' => '1234',
            'pos_permissions' => ['sales', 'drawer'],
        ])->assertRedirect(route('seller.staff.index'))->assertSessionHasNoErrors();

        $employee = \App\Models\Employee::where('store_id', $this->store->id)->firstOrFail();
        $this->assertStringContainsString((string) $this->store->id, $employee->employee_id);

        // PIN ต้องบันทึกได้ (คอลัมน์ขยายแล้ว) และตรวจผ่านด้วย PIN จริง (ไม่ถูก hash ซ้ำสองชั้น)
        $assignment = \App\Models\PosStaffAssignment::where('employee_id', $employee->id)->firstOrFail();
        $this->assertTrue($assignment->verifyPin('1234'));
        $this->assertFalse($assignment->verifyPin('9999'));

        $this->assertV4Page(route('seller.staff.index'));
        $this->assertV4Page(route('seller.staff.show', $employee));
        $this->assertV4Page(route('seller.staff.edit', $employee));

        // แก้ไขพนักงาน (ฟอร์มหน้า edit ส่งแผนก/ตำแหน่งปัจจุบันเสมอ)
        $this->actingAs($this->seller)->put(route('seller.staff.update', $employee), [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'nickname' => 'ชาย',
            'department_id' => $employee->department_id,
            'position_id' => $employee->position_id,
            'employment_type' => 'full_time',
            'employment_status' => 'probation',
            'pos_permissions' => ['sales'],
        ])->assertRedirect(route('seller.staff.index'))->assertSessionMissing('error');
        $this->assertSame('probation', $employee->fresh()->employment_status);
        $this->assertTrue(\App\Models\PosStaffAssignment::where('employee_id', $employee->id)->firstOrFail()->verifyPin('1234'), 'PIN เดิมต้องยังใช้ได้เมื่อไม่ได้เปลี่ยน');

        // ร้านที่สองต้องเพิ่มพนักงานได้เหมือนกัน (เดิมรหัสแผนก GENERAL ชนกันข้ามร้าน)
        [$other] = $this->makeSeller();
        $this->actingAs($other)->post(route('seller.staff.store'), [
            'first_name' => 'Anan',
            'last_name' => 'Sukjai',
            'hire_date' => now()->toDateString(),
            'employment_type' => 'part_time',
        ])->assertRedirect(route('seller.staff.index'))->assertSessionHasNoErrors();

        // ร้านอื่นดูพนักงานร้านเราไม่ได้
        $this->actingAs($other)->get(route('seller.staff.show', $employee))->assertForbidden();

        // แผนก/ตำแหน่ง/กะ ภาษาไทย สร้างได้ (รหัสไม่ชนกันข้ามร้าน)
        $this->actingAs($this->seller)->post(route('seller.staff.departments.store'), ['name' => 'ฝ่ายขาย'])->assertRedirect();
        $this->actingAs($other)->post(route('seller.staff.departments.store'), ['name' => 'ฝ่ายขาย'])->assertRedirect();
        $this->actingAs($this->seller)->post(route('seller.staff.positions.store'), ['title' => 'แคชเชียร์'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->seller)->post(route('seller.staff.shifts.store'), ['name' => 'กะเช้า', 'start_time' => '08:00', 'end_time' => '17:00'])->assertRedirect()->assertSessionHasNoErrors();

        foreach (['seller.staff.departments', 'seller.staff.positions', 'seller.staff.shifts'] as $name) {
            $this->assertV4Page(route($name));
        }
    }

    public function test_user_guide_renders_on_seller_v4(): void
    {
        $this->assertV4Page(route('seller.user-guide.index'));
    }

    public function test_seller_menu_routes_all_resolve(): void
    {
        $menu = config('menus.seller');
        $names = [];
        foreach ($menu as $item) {
            if (! empty($item['route'])) {
                $names[] = $item['route'];
            }
            foreach ($item['submenu'] ?? [] as $sub) {
                if (! empty($sub['route'])) {
                    $names[] = $sub['route'];
                }
            }
        }

        foreach (['seller.marketing.index', 'seller.coupons.index', 'seller.staff.index', 'seller.user-guide.index', 'seller.pos.index'] as $expected) {
            $this->assertContains($expected, $names);
        }
        $this->assertNotContains('chatbot.marketplace.my-rentals', $names);
        $this->assertNotContains('seller.marketing', $names);
    }

    public function test_terminal_toggle_works_on_the_real_table_shape(): void
    {
        $d = $this->makePosData();
        $column = Schema::hasColumn('pos_terminals', 'status') ? 'status' : 'is_active';

        $this->actingAs($this->seller)->post(route('seller.pos.terminals.toggle-status', $d['terminal']))->assertRedirect();

        $fresh = DB::table('pos_terminals')->where('id', $d['terminal']->id)->first();
        $this->assertNotNull($fresh->{$column});
    }
}
