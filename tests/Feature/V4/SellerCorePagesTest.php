<?php

namespace Tests\Feature\V4;

use App\Models\AccountingActivityLog;
use App\Models\EarningsLedger;
use App\Models\OrderMessage;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Services\NotificationService;
use App\Services\VendorSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * หน้าแผงผู้ขายธีม V4 (ส่วนที่ 1) — ต้องใช้ MySQL
 *
 * - ทุกหน้าเปิดได้ (200) ในเปลือก seller-v4 (มี tp-card) ทั้งตอนมีข้อมูลและตอนว่าง
 * - หน้าแจ้งเตือนของผู้ขายอยู่ในเปลือกผู้ขาย และฟอร์มยิงไป route ของผู้ขาย (GAP-23)
 * - วางแผนราคา: quote/plan ตอบ JSON จาก PricingEngine · "ใช้ราคานี้" แก้ได้เฉพาะสินค้าตัวเอง + บันทึกประวัติ + กดซ้ำไม่บันทึกซ้ำ
 * - ตั้งค่าร้าน: จด VAT ต้องมีเลขผู้เสียภาษี 13 หลัก + บันทึกประวัติการเปลี่ยน
 */
#[Group('v4')]
class SellerCorePagesTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private User $seller;

    private VendorStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();

        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $seller->forceFill(['kyc_status' => 'approved'])->save();
        $this->seller = $seller->fresh();
        $this->store = $store;
    }

    private function package(string $slug, float $price, float $gp, array $extra = []): VendorPackage
    {
        return VendorPackage::create(array_merge([
            'package_name' => ucfirst($slug),
            'package_slug' => $slug.'-'.Str::lower(Str::random(4)),
            'display_name' => 'แพ็กเกจ '.$slug,
            'price' => $price,
            'yearly_price' => $price > 0 ? $price * 10 : null,
            'setup_fee' => 0,
            'currency' => 'THB',
            'commission_rate' => $gp,
            'trial_days' => 0,
            'sort_order' => 1,
            'is_active' => true,
            'features' => ['สินค้าไม่จำกัด'],
        ], $extra));
    }

    /**
     * ข้อมูลตัวอย่าง: สินค้า 1 ชิ้น + ออเดอร์พัสดุ (จ่ายแล้ว) + ออเดอร์ไรเดอร์ (จ่ายแล้ว) + ออเดอร์ส่งแล้ว + แชท + รายได้
     *
     * @return array<string, mixed>
     */
    private function seedSellerData(): array
    {
        $product = $this->makeProduct($this->seller, $this->store, ['price' => 250, 'cost_price' => 120]);
        $buyer = $this->makeBuyer();
        $address = $this->makeAddress($buyer);
        $snapshot = [
            'recipient_name' => 'ผู้รับ ทดสอบ', 'phone_number' => '0899999999', 'address_line_1' => '99 ถนนพระราม 4',
            'sub_district' => 'สุริยวงศ์', 'district' => 'บางรัก', 'province' => 'กรุงเทพมหานคร', 'postal_code' => '10500',
            'latitude' => self::STORE_LAT + 0.01, 'longitude' => self::STORE_LNG + 0.01,
        ];

        $parcel = $this->makeOrder($buyer, [['product' => $product, 'qty' => 2]], [
            'store_id' => $this->store->id, 'status' => 'paid', 'payment_status' => 'paid', 'payment_method' => 'wallet',
            'paid_at' => now(), 'shipping_address_id' => $address->id, 'shipping_address_snapshot' => $snapshot,
        ]);
        $rider = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $this->store->id, 'status' => 'paid', 'payment_status' => 'paid', 'payment_method' => 'wallet',
            'paid_at' => now(), 'delivery_method' => 'rider', 'shipping_address_snapshot' => $snapshot,
        ]);
        $shipped = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $this->store->id, 'status' => 'shipped', 'payment_status' => 'paid', 'payment_method' => 'wallet',
            'paid_at' => now()->subDay(), 'shipped_at' => now(), 'tracking_number' => 'TH1234567890', 'shipping_provider' => 'kerry',
            'shipping_address_snapshot' => $snapshot,
        ]);
        $shipped->items()->update(['status' => 'shipped']);

        OrderMessage::send($parcel, $buyer->id, 'customer', 'สวัสดีค่ะ ส่งวันไหนคะ');

        ShippingProvider::create(['code' => 'KERRY', 'name' => 'Kerry Express', 'is_active' => true, 'sort_order' => 1]);

        EarningsLedger::create([
            'user_id' => $this->seller->id,
            'earning_type' => EarningsLedger::TYPE_SELLER_SALE,
            'source_type' => 'order',
            'source_id' => $parcel->id,
            'gross_amount' => 500,
            'platform_fee' => 0,
            'vat_amount' => 0,
            'mlm_commission' => 0,
            'net_amount' => 500,
            'status' => EarningsLedger::STATUS_PENDING,
            'description' => 'รายได้จากออเดอร์ '.$parcel->order_number,
        ]);

        return compact('product', 'buyer', 'parcel', 'rider', 'shipped');
    }

    public function test_every_seller_core_page_renders_in_the_v4_shell(): void
    {
        $data = $this->seedSellerData();
        $this->package('free', 0, 10, ['is_default' => true]);
        $premium = $this->package('premium', 990, 7, ['is_featured' => true]);
        $subscription = app(VendorSubscriptionService::class)->createPendingSubscription($this->store->fresh(), $premium, 'monthly');

        app(NotificationService::class)->create($this->seller, 'order', 'มีคำสั่งซื้อใหม่', 'ออเดอร์ใหม่เข้ามา');

        $pages = [
            route('seller.dashboard'),
            route('seller.products.index'),
            route('seller.products.index', ['search' => 'ไม่มีสินค้านี้']),
            route('seller.products.create'),
            route('seller.products.edit', $data['product']->id),
            route('seller.pricing.planner'),
            route('seller.pricing.planner', ['product_id' => $data['product']->id]),
            route('seller.orders.index'),
            route('seller.orders.index', ['status' => 'shipped']),
            route('seller.orders.show', $data['parcel']->id),
            route('seller.orders.show', $data['rider']->id),
            route('seller.orders.show', $data['shipped']->id),
            route('seller.orders.pending-shipping'),
            route('seller.orders.shipped'),
            route('seller.orders.delivered'),
            route('seller.orders.tracking', $data['parcel']->id),
            route('seller.orders.tracking', ['orderId' => $data['shipped']->id, 'tab' => 'chat']),
            route('seller.orders.tracking', $data['rider']->id),
            route('seller.store.settings'),
            route('seller.store.layout.index'),
            route('seller.packages'),
            route('seller.packages.payment', $subscription->id),
            route('seller.commissions'),
            route('seller.commissions', ['type' => 'referral']),
            route('seller.messages.index'),
            route('seller.messages.unread'),
            route('seller.profile'),
            route('seller.settings'),
            route('seller.wallet.index'),
            route('seller.wallet.withdraw'),
            route('seller.wallet.withdrawals'),
            route('seller.notifications.index'),
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs($this->seller)->get($url);
            $this->assertSame(200, $response->status(), "หน้า {$url} ต้องเปิดได้ (ได้ {$response->status()})");
            $response->assertSee('tp-card', false);
        }
    }

    public function test_pages_render_when_the_store_is_empty(): void
    {
        foreach ([
            'seller.dashboard', 'seller.products.index', 'seller.orders.index', 'seller.orders.pending-shipping',
            'seller.orders.shipped', 'seller.orders.delivered', 'seller.commissions', 'seller.messages.index',
            'seller.messages.unread', 'seller.pricing.planner', 'seller.wallet.index', 'seller.notifications.index',
        ] as $name) {
            $response = $this->actingAs($this->seller)->get(route($name));
            $this->assertSame(200, $response->status(), "หน้า {$name} (ร้านว่าง) ต้องเปิดได้");
            $response->assertSee('tp-card', false);
        }
    }

    public function test_onboarding_renders_for_each_step(): void
    {
        $this->package('free', 0, 10, ['is_default' => true]);
        $this->package('enterprise', 0, 5, ['package_slug' => 'enterprise']);

        // ขั้น 1: ยังไม่ผ่าน KYC
        $newbie = User::factory()->create();
        $newbie->forceFill(['role' => 'seller', 'kyc_status' => 'pending'])->save();
        $this->actingAs($newbie)->get(route('seller.onboarding.index'))
            ->assertOk()->assertSee('tp-card', false)->assertSee('กำลังรอตรวจสอบเอกสาร');

        // ขั้น 2: ผ่าน KYC แต่ยังไม่มีร้าน → เห็นแพ็กเกจ + แพ็กเกจราคาพิเศษเป็นปุ่มติดต่อทีมงาน
        $newbie->forceFill(['kyc_status' => 'approved'])->save();
        $this->actingAs($newbie->fresh())->get(route('seller.onboarding.index'))
            ->assertOk()->assertSee('tp-card', false)->assertSee('ติดต่อทีมงาน');
    }

    public function test_seller_notifications_render_in_the_seller_shell_with_seller_routes(): void
    {
        app(NotificationService::class)->create($this->seller, 'order', 'มีคำสั่งซื้อใหม่', 'ออเดอร์ใหม่เข้ามา');

        $this->actingAs($this->seller)->get(route('seller.notifications.index'))
            ->assertOk()
            ->assertSee(route('seller.notifications.read-all'), false)
            ->assertDontSee(route('user.notifications.read-all'), false);

        $this->actingAs($this->seller)->get(route('user.notifications.index'))
            ->assertOk()
            ->assertSee(route('user.notifications.read-all'), false);
    }

    public function test_pricing_quote_and_plan_return_engine_numbers(): void
    {
        $this->setGpRate(10);
        $product = $this->makeProduct($this->seller, $this->store, ['price' => 200, 'cost_price' => 100]);

        $quote = $this->actingAs($this->seller)->postJson(route('seller.pricing.quote'), [
            'price' => 200, 'cost' => 100, 'product_id' => $product->id,
        ]);
        $quote->assertOk()->assertJsonPath('success', true);
        $this->assertEqualsWithDelta(20.0, $quote->json('data.gp_amount'), 0.001);
        $this->assertEqualsWithDelta(180.0, $quote->json('data.seller_net'), 0.001);
        $this->assertEqualsWithDelta(80.0, $quote->json('data.profit'), 0.001);
        $this->assertIsArray($quote->json('data.lines'));

        $this->actingAs($this->seller)->postJson(route('seller.pricing.quote'), ['price' => -5])
            ->assertStatus(422);

        $plan = $this->actingAs($this->seller)->postJson(route('seller.pricing.plan'), [
            'product_id' => $product->id, 'cost' => 100, 'target_margin_percent' => 20,
            'competitor_price' => 180, 'monthly_volume' => 50, 'delivery' => 'rider',
        ]);
        $plan->assertOk()->assertJsonPath('success', true);
        $this->assertCount(3, $plan->json('data.strategies'));
        $this->assertContains($plan->json('data.recommended'), ['penetration', 'balanced', 'premium']);
        $this->assertNotEmpty($plan->json('data.recommendations'));

        $this->actingAs($this->seller)->postJson(route('seller.pricing.plan'), ['target_margin_percent' => 20])
            ->assertStatus(422);
    }

    public function test_apply_price_updates_only_own_product_and_logs_once(): void
    {
        $product = $this->makeProduct($this->seller, $this->store, ['price' => 200]);
        [$other, $otherStore] = $this->makeSellerWithStore();
        $foreign = $this->makeProduct($other, $otherStore, ['price' => 300]);

        $this->actingAs($this->seller)->postJson(route('seller.pricing.apply'), [
            'product_id' => $product->id, 'price' => 249, 'strategy' => 'balanced',
        ])->assertOk()->assertJsonPath('data.changed', true);

        $this->assertEqualsWithDelta(249.0, (float) $product->fresh()->price, 0.001);
        $this->assertSame(1, AccountingActivityLog::where('loggable_type', Product::class)
            ->where('loggable_id', $product->id)->where('action', 'product.price_updated_by_planner')->count());

        // กดซ้ำราคาเดิม → ไม่เปลี่ยน ไม่บันทึกซ้ำ
        $this->actingAs($this->seller)->postJson(route('seller.pricing.apply'), [
            'product_id' => $product->id, 'price' => 249,
        ])->assertOk()->assertJsonPath('data.changed', false);
        $this->assertSame(1, AccountingActivityLog::where('loggable_id', $product->id)->where('action', 'product.price_updated_by_planner')->count());

        // สินค้าร้านอื่น → 404 และราคาไม่เปลี่ยน
        $this->actingAs($this->seller)->postJson(route('seller.pricing.apply'), [
            'product_id' => $foreign->id, 'price' => 1,
        ])->assertStatus(404)->assertJsonPath('code', 'PRODUCT_NOT_FOUND');
        $this->assertEqualsWithDelta(300.0, (float) $foreign->fresh()->price, 0.001);

        // ราคาไม่ถูกต้อง → 422
        $this->actingAs($this->seller)->postJson(route('seller.pricing.apply'), [
            'product_id' => $product->id, 'price' => 0,
        ])->assertStatus(422);
    }

    public function test_store_vat_flag_requires_tax_id_and_is_audited(): void
    {
        $base = [
            'store_name' => $this->store->store_name,
            'business_type' => 'individual',
            'vat_registered' => '1',
        ];

        $this->actingAs($this->seller)->from(route('seller.store.settings'))
            ->put(route('seller.store.update'), $base + ['tax_id' => '123'])
            ->assertRedirect(route('seller.store.settings'))
            ->assertSessionHasErrors('tax_id');
        $this->assertFalse((bool) $this->store->fresh()->vat_registered);

        $this->actingAs($this->seller)
            ->put(route('seller.store.update'), $base + ['tax_id' => '1234567890123'])
            ->assertRedirect(route('seller.store.settings'));
        $this->assertTrue((bool) $this->store->fresh()->vat_registered);
        $this->assertSame(1, AccountingActivityLog::where('loggable_type', VendorStore::class)
            ->where('loggable_id', $this->store->id)->where('action', 'store.vat_registered_changed')->count());

        // ฟอร์มที่ไม่ส่งช่อง VAT (ไคลเอนต์อื่น) ต้องไม่รีเซ็ตค่า
        $this->actingAs($this->seller)
            ->put(route('seller.store.update'), ['store_name' => $this->store->store_name, 'business_type' => 'individual'])
            ->assertRedirect(route('seller.store.settings'));
        $this->assertTrue((bool) $this->store->fresh()->vat_registered);
    }

    public function test_seller_cannot_open_another_sellers_order_pages(): void
    {
        $data = $this->seedSellerData();
        [$other] = $this->makeSellerWithStore();
        $other->forceFill(['kyc_status' => 'approved'])->save();

        $this->actingAs($other->fresh())->get(route('seller.orders.show', $data['parcel']->id))->assertNotFound();
        $this->actingAs($other->fresh())->get(route('seller.orders.tracking', $data['parcel']->id))->assertNotFound();
    }
}
