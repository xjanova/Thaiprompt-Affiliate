<?php

namespace Tests\Feature\V4;

use App\Models\AccountingActivityLog;
use App\Models\Order;
use App\Models\PosDevice;
use App\Models\PosSession;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\RiderJob;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * แก้ข้อที่รีวิวเจอในหน้า V4 ของผู้ขาย/แอดมินร้านค้า (fix2b) — ต้องใช้ MySQL (RefreshDatabase)
 *
 * 1. สร้างสินค้าแบบ "ใช้ค่าร้าน/ส่งฟรี" ได้ (ช่องค่าส่งถูก disable → ไม่ถูกส่งมา → ใช้ 0)
 * 2. /pos/checkout ตัดสต็อกเอง (เอา hook created ของ PosTransactionItem ออกแล้ว)
 * 3. ตั้งค่าร้าน: สวิตช์ COD/รีวิว/ไรเดอร์ ที่ปิดไว้ไม่เด้งกลับเป็นเปิดหลัง validation error
 * 4. หน้ารอจัดส่ง: ปุ่มเรียกไรเดอร์ตาม allowedActions (เรียกใหม่ได้หลังงานยกเลิก · ร้านไม่มีจุดรับของไม่เห็นปุ่ม)
 * 5. บันทึกของแอดมินในออเดอร์ต่อท้าย ไม่เขียนทับ
 * 6. สีร้าน (แอดมิน): ว่าง/ยาวเกิน → error ไม่ใช่ 500 · ไม่มี # → เติมให้
 * 7. เปิดร้านคืนได้เฉพาะร้านที่ถูกระงับ (ร้าน closed = ใบสมัครถูกปฏิเสธ/บัญชีถูกลบ เปิดไม่ได้)
 * 8. สวิตช์เปิดขายในหน้ารายการไม่เปิดร้านที่ถูกระงับ (409)
 * 9. แอดมินตั้ง VAT ต้องมีเลขผู้เสียภาษี 13 หลัก + บันทึกประวัติ VAT/แพ็กเกจ/GP
 * 10. แก้สินค้า (แอดมิน): สต็อกว่าง → error ชัดเจน · เกณฑ์ใกล้หมดว่าง → ใช้ค่าเดิม
 */
#[Group('v4')]
class ShopReviewFixesTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    private User $seller;

    private VendorStore $store;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake();
        Queue::fake();
        Notification::fake();
        Storage::fake('public');
        Cache::flush();

        [$seller, $store] = $this->makeSellerWithStore(['rider_delivery_enabled' => true]);
        $seller->forceFill(['kyc_status' => 'approved'])->save();
        $this->seller = $seller->fresh();
        $this->store = $store;
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // =====================================================
    // 1. สร้างสินค้า — ค่าส่งไม่ถูกส่งมา
    // =====================================================

    public function test_seller_can_create_product_when_shipping_fee_field_is_disabled(): void
    {
        $category = $this->makeCategory();

        foreach (['store_default', 'free', 'weight_based'] as $method) {
            $name = 'สินค้าวิธีส่ง '.$method;
            $this->actingAs($this->seller)->post(route('seller.products.store'), [
                'name' => $name,
                'category_id' => $category->id,
                'price' => 199,
                'stock_quantity' => 5,
                'track_inventory' => '1',
                'shipping_method' => $method,
            ])->assertRedirect(route('seller.products.index'))->assertSessionHas('success');

            $product = Product::where('name', $name)->first();
            $this->assertNotNull($product, "สร้างสินค้าแบบ {$method} ไม่สำเร็จ");
            $this->assertSame($method, $product->shipping_method);
            $this->assertEquals(0.0, (float) $product->shipping_fee);
        }

        // ค่าส่งคงที่ยังบันทึกค่าที่กรอก
        $this->actingAs($this->seller)->post(route('seller.products.store'), [
            'name' => 'สินค้าค่าส่งคงที่',
            'category_id' => $category->id,
            'price' => 199,
            'stock_quantity' => 5,
            'shipping_method' => 'flat_rate',
            'shipping_fee' => '45.5',
        ])->assertSessionHas('success');
        $this->assertEquals(45.5, (float) Product::where('name', 'สินค้าค่าส่งคงที่')->value('shipping_fee'));
    }

    // =====================================================
    // 2. /pos/checkout ตัดสต็อก
    // =====================================================

    public function test_pos_cashier_checkout_decrements_stock_once(): void
    {
        $product = $this->makeProduct($this->seller, $this->store, ['price' => 100, 'stock_quantity' => 5, 'track_inventory' => true]);
        [$device, $session] = $this->posDeviceAndSession();

        $this->actingAs($this->seller)->postJson(route('pos.checkout'), [
            'device_id' => $device->id,
            'session_id' => $session->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 100]],
            'payment_method' => 'cash',
            'amount_paid' => 200,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(1, PosTransactionItem::where('product_id', $product->id)->count());
        $this->assertSame(3, (int) $product->fresh()->stock_quantity, 'ขายหน้าร้าน 2 ชิ้นต้องตัดสต็อก 2 ครั้งเดียว');

        // ขายหมด → out_of_stock
        $this->actingAs($this->seller)->postJson(route('pos.checkout'), [
            'device_id' => $device->id,
            'session_id' => $session->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'price' => 100]],
            'payment_method' => 'cash',
            'amount_paid' => 300,
        ])->assertOk();
        $product->refresh();
        $this->assertSame(0, (int) $product->stock_quantity);
        $this->assertSame('out_of_stock', $product->stock_status);

        // สต็อกไม่พอ → 422 ไม่สร้างรายการ ไม่ตัดสต็อก
        $this->actingAs($this->seller)->postJson(route('pos.checkout'), [
            'device_id' => $device->id,
            'session_id' => $session->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 100]],
            'payment_method' => 'cash',
            'amount_paid' => 100,
        ])->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame(0, (int) $product->fresh()->stock_quantity);
        $this->assertSame(2, PosTransactionItem::where('product_id', $product->id)->count());
    }

    public function test_pos_cashier_checkout_sums_duplicate_lines_before_stock_check(): void
    {
        $product = $this->makeProduct($this->seller, $this->store, ['price' => 50, 'stock_quantity' => 3, 'track_inventory' => true]);
        [$device, $session] = $this->posDeviceAndSession();

        // 2 บรรทัดสินค้าเดียวกัน รวม 4 ชิ้น > สต็อก 3 → ต้องไม่ผ่าน (เดิมเช็คทีละบรรทัด)
        $this->actingAs($this->seller)->postJson(route('pos.checkout'), [
            'device_id' => $device->id,
            'session_id' => $session->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 50],
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 50],
            ],
            'payment_method' => 'cash',
            'amount_paid' => 200,
        ])->assertStatus(422);
        $this->assertSame(3, (int) $product->fresh()->stock_quantity);
    }

    // =====================================================
    // 3. ตั้งค่าร้าน — สวิตช์ที่ปิดไม่เด้งกลับ
    // =====================================================

    public function test_store_settings_toggles_turned_off_stay_off_after_validation_error(): void
    {
        $this->store->forceFill(['enable_cod' => true, 'enable_reviews' => true, 'rider_delivery_enabled' => true])->save();

        // ก่อนแก้: เปิดอยู่ทั้งหมด → checkbox ต้องมี checked (ยืนยันว่า regex ด้านล่างจับได้จริง)
        $initial = $this->actingAs($this->seller)->get(route('seller.store.settings'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/name="enable_cod" value="1"\s+checked/', $initial);
        $this->assertMatchesRegularExpression('/name="enable_reviews" value="1"\s+checked/', $initial);
        $this->assertMatchesRegularExpression('/name="rider_delivery_enabled" value="1" x-model="enabled"\s+checked/', $initial);

        // ปิด COD/รีวิว/ไรเดอร์ แต่เลขผู้เสียภาษีผิด → กลับมาหน้าฟอร์มพร้อม old input
        $response = $this->actingAs($this->seller)
            ->from(route('seller.store.settings'))
            ->followingRedirects()
            ->put(route('seller.store.update'), [
                'store_name' => $this->store->store_name,
                'business_type' => 'individual',
                'enable_cod' => '0',
                'enable_reviews' => '0',
                'rider_delivery_enabled' => '0',
                'vat_registered' => '1',
                'tax_id' => '123',
            ]);

        $response->assertOk()->assertSee('tp-card', false);
        $html = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/name="enable_cod" value="1"\s+checked/', $html, 'COD ที่ปิดไว้เด้งกลับเป็นเปิด');
        $this->assertDoesNotMatchRegularExpression('/name="enable_reviews" value="1"\s+checked/', $html, 'รีวิวที่ปิดไว้เด้งกลับเป็นเปิด');
        $this->assertDoesNotMatchRegularExpression('/name="rider_delivery_enabled" value="1" x-model="enabled"\s+checked/', $html, 'ไรเดอร์ที่ปิดไว้เด้งกลับเป็นเปิด');

        // ค่าใน DB ยังไม่เปลี่ยน (บันทึกไม่ผ่าน)
        $this->assertTrue((bool) $this->store->fresh()->enable_cod);

        // บันทึกจริง: hidden 0 → ปิดได้ · ติ๊ก (checkbox ส่ง 1 ทับ hidden) → เปิด
        $this->actingAs($this->seller)->put(route('seller.store.update'), [
            'store_name' => $this->store->store_name,
            'business_type' => 'individual',
            'enable_cod' => '0',
            'enable_reviews' => '1',
            'rider_delivery_enabled' => '0',
        ])->assertRedirect(route('seller.store.settings'));
        $store = $this->store->fresh();
        $this->assertFalse((bool) $store->enable_cod);
        $this->assertTrue((bool) $store->enable_reviews);
        $this->assertFalse((bool) $store->rider_delivery_enabled);
    }

    // =====================================================
    // 4. หน้ารอจัดส่ง — ปุ่มเรียกไรเดอร์ตาม allowedActions
    // =====================================================

    public function test_pending_shipping_rider_button_follows_allowed_actions(): void
    {
        $order = $this->riderOrder($this->store, $this->seller);

        // ยังไม่เรียก → มีปุ่ม
        $this->actingAs($this->seller)->get(route('seller.orders.pending-shipping'))
            ->assertOk()->assertSee('tp-card', false)
            ->assertSee('value="request_rider"', false);

        // เรียกไรเดอร์ → งานกำลังหาไรเดอร์ → ไม่มีปุ่มซ้ำ
        $this->actingAs($this->seller)->post(route('seller.orders.action', $order->id), ['action' => 'request_rider'])
            ->assertSessionHas('success');
        $this->assertSame(1, RiderJob::forSource($order)->count());
        $this->actingAs($this->seller)->get(route('seller.orders.pending-shipping'))
            ->assertOk()
            ->assertDontSee('value="request_rider"', false);

        // งานถูกยกเลิก → ออเดอร์ยังรอส่ง → เรียกไรเดอร์อีกครั้งได้จากหน้ารายการ
        RiderJob::forSource($order)->update(['status' => 'cancelled']);
        $this->actingAs($this->seller)->get(route('seller.orders.pending-shipping'))
            ->assertOk()
            ->assertSee('value="request_rider"', false)
            ->assertSee('เรียกไรเดอร์อีกครั้ง');

        // ร้านที่ยังไม่ปักหมุดจุดรับของ → ไม่เห็นปุ่ม (กดแล้วจะได้ error) แต่เห็นคำแนะนำให้ไปตั้งค่า
        [$seller2, $store2] = $this->makeSellerWithStore(['rider_delivery_enabled' => false, 'pickup_latitude' => null, 'pickup_longitude' => null]);
        $seller2->forceFill(['kyc_status' => 'approved'])->save();
        $this->riderOrder($store2, $seller2);
        $this->actingAs($seller2->fresh())->get(route('seller.orders.pending-shipping'))
            ->assertOk()
            ->assertDontSee('value="request_rider"', false)
            ->assertSee('ไปตั้งค่าไรเดอร์');
    }

    // =====================================================
    // 5. บันทึกของแอดมินในออเดอร์ต่อท้าย
    // =====================================================

    public function test_admin_order_notes_are_appended_not_overwritten(): void
    {
        $product = $this->makeProduct($this->seller, $this->store, ['price' => 250]);
        $buyer = $this->makeBuyer();
        $order = $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $this->store->id, 'status' => 'paid', 'payment_status' => 'paid',
            'payment_method' => 'wallet', 'paid_at' => now(),
            'admin_notes' => '[2026-09-25 10:00] แอดมิน #1 เปลี่ยนสถานะชำระเงิน pending → paid: ตรวจสลิปแล้ว',
        ]);
        $provider = ShippingProvider::create(['code' => 'KERRY', 'name' => 'Kerry Express', 'is_active' => true, 'sort_order' => 1]);

        $this->actingAs($this->admin)->from(route('admin.ecommerce.orders.show', $order))
            ->post(route('admin.ecommerce.orders.status.update', $order), ['status' => 'processing', 'admin_notes' => 'แพ็กของแล้ว'])
            ->assertSessionHas('success');

        $this->actingAs($this->admin)->from(route('admin.ecommerce.orders.tracking', $order))
            ->post(route('admin.ecommerce.orders.tracking.update', $order), [
                'shipping_provider_id' => $provider->id,
                'tracking_number' => 'TH0000000001',
                'admin_notes' => 'ส่งไปรษณีย์',
            ])->assertSessionHas('success');

        $notes = (string) $order->fresh()->admin_notes;
        $this->assertStringContainsString('ตรวจสลิปแล้ว', $notes, 'บรรทัด audit เดิมหาย');
        $this->assertStringContainsString('แพ็กของแล้ว', $notes);
        $this->assertStringContainsString('ส่งไปรษณีย์', $notes);
        $this->assertStringContainsString('แอดมิน #'.$this->admin->id, $notes);
        $this->assertSame('TH0000000001', $order->fresh()->tracking_number);

        // ฟอร์มว่าง → ไม่เพิ่มบรรทัดเปล่า
        $before = (string) $order->fresh()->admin_notes;
        $this->actingAs($this->admin)->post(route('admin.ecommerce.orders.status.update', $order), ['status' => 'shipped', 'admin_notes' => ''])
            ->assertSessionHas('success');
        $this->assertSame($before, (string) $order->fresh()->admin_notes);

        // ยกเลิกไม่ได้ (ส่งของแล้ว) → เหตุผลต้องไม่ถูกบันทึก
        $this->actingAs($this->admin)->post(route('admin.ecommerce.orders.status.update', $order), ['status' => 'cancelled', 'admin_notes' => 'ลูกค้าขอยกเลิก'])
            ->assertSessionHas('error');
        $this->assertStringNotContainsString('ลูกค้าขอยกเลิก', (string) $order->fresh()->admin_notes);

        foreach ([route('admin.ecommerce.orders.show', $order), route('admin.ecommerce.orders.tracking', $order)] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()->assertSee('tp-card', false)->assertSee('ตรวจสลิปแล้ว');
        }
    }

    // =====================================================
    // 6. สีร้าน (แอดมิน)
    // =====================================================

    public function test_admin_store_colors_are_validated_and_normalized(): void
    {
        $store = $this->adminStore();
        $base = ['store_name' => $store->store_name, 'secondary_color' => '#ec4899'];

        $this->actingAs($this->admin)->from(route('admin.storefront.vendor-stores.edit', $store))
            ->put(route('admin.storefront.vendor-stores.update', $store), $base + ['primary_color' => ''])
            ->assertRedirect(route('admin.storefront.vendor-stores.edit', $store))
            ->assertSessionHasErrors('primary_color');

        $this->actingAs($this->admin)->from(route('admin.storefront.vendor-stores.edit', $store))
            ->put(route('admin.storefront.vendor-stores.update', $store), $base + ['primary_color' => '#f97316ff'])
            ->assertSessionHasErrors('primary_color');

        $this->actingAs($this->admin)->from(route('admin.storefront.vendor-stores.edit', $store))
            ->put(route('admin.storefront.vendor-stores.update', $store), $base + ['primary_color' => 'red'])
            ->assertSessionHasErrors('primary_color');

        $this->actingAs($this->admin)
            ->put(route('admin.storefront.vendor-stores.update', $store), $base + ['primary_color' => 'ABC'])
            ->assertRedirect(route('admin.storefront.vendor-stores.show', $store));
        $this->assertSame('#aabbcc', $store->fresh()->primary_color);

        $this->actingAs($this->admin)
            ->put(route('admin.storefront.vendor-stores.update', $store), $base + ['primary_color' => 'F97316'])
            ->assertSessionHasNoErrors();
        $this->assertSame('#f97316', $store->fresh()->primary_color);

        // ไม่ส่งช่องสีมาเลย (ฟอร์มอื่น) → ไม่เปลี่ยน · GP ว่าง → ไม่เปลี่ยน (คอลัมน์ NOT NULL)
        $this->actingAs($this->admin)
            ->put(route('admin.storefront.vendor-stores.update', $store), ['store_name' => $store->store_name, 'commission_rate' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame('#f97316', $store->fresh()->primary_color);
        $this->assertEquals(10.0, (float) $store->fresh()->commission_rate);

        $this->actingAs($this->admin)->get(route('admin.storefront.vendor-stores.edit', $store))
            ->assertOk()->assertSee('tp-card', false)->assertSee('maxlength="7"', false)->assertSee('name="tax_id"', false);
    }

    // =====================================================
    // 7 + 8. ระงับ/เปิดร้าน · สวิตช์เปิดขาย
    // =====================================================

    public function test_closed_stores_cannot_be_reopened_and_switch_does_not_bypass_suspension(): void
    {
        // ใบสมัครถูกปฏิเสธ (เจ้าของยังอยู่)
        $rejected = $this->adminStore(['status' => 'closed', 'is_active' => false, 'suspension_reason' => 'เอกสารไม่ครบ'], 'user');
        $this->actingAs($this->admin)->from(route('admin.storefront.vendor-stores.show', $rejected))
            ->post(route('admin.storefront.vendor-stores.unsuspend', $rejected))
            ->assertSessionHas('error');
        $rejected->refresh();
        $this->assertSame('closed', $rejected->status);
        $this->assertFalse((bool) $rejected->is_active);

        // ร้านของบัญชีที่ลบตาม PDPA
        $deleted = $this->adminStore(['status' => 'closed', 'is_active' => false]);
        User::whereKey($deleted->user_id)->first()->delete();
        $this->actingAs($this->admin)->post(route('admin.storefront.vendor-stores.unsuspend', $deleted))
            ->assertSessionHas('error');
        $this->assertSame('closed', $deleted->fresh()->status);

        // หน้า show: ร้าน closed ไม่มีปุ่มเปิดร้าน แต่บอกที่มา
        $this->actingAs($this->admin)->get(route('admin.storefront.vendor-stores.show', $rejected))
            ->assertOk()->assertSee('tp-card', false)
            ->assertSee('ใบสมัครเปิดร้านถูกปฏิเสธ')
            ->assertDontSee(route('admin.storefront.vendor-stores.unsuspend', $rejected), false);
        $this->actingAs($this->admin)->get(route('admin.storefront.vendor-stores.show', $deleted))
            ->assertOk()->assertSee('เจ้าของร้านลบบัญชีแล้ว');

        // ร้านที่ถูกระงับ: สวิตช์เปิดขาย → 409 ร้านยังปิด
        $suspended = $this->adminStore(['status' => 'suspended', 'is_active' => false, 'suspension_reason' => 'ขายของผิดกฎหมาย']);
        $this->actingAs($this->admin)->postJson(route('admin.storefront.vendor-stores.toggle-status', $suspended))
            ->assertStatus(409)
            ->assertJsonPath('success', false);
        $suspended->refresh();
        $this->assertFalse((bool) $suspended->is_active);
        $this->assertSame('ขายของผิดกฎหมาย', $suspended->suspension_reason);
        $this->assertTrue($suspended->isBlockedFromSelling());

        // ร้าน closed ก็เปิดจากสวิตช์ไม่ได้
        $this->actingAs($this->admin)->postJson(route('admin.storefront.vendor-stores.toggle-status', $rejected))->assertStatus(409);

        // ฟอร์มแก้ไข: ส่ง is_active=1 ให้ร้านที่ถูกระงับ → ไม่ยอม
        $this->actingAs($this->admin)->from(route('admin.storefront.vendor-stores.edit', $suspended))
            ->put(route('admin.storefront.vendor-stores.update', $suspended), ['store_name' => $suspended->store_name, 'is_active' => '1'])
            ->assertSessionHasErrors('is_active');
        $this->assertFalse((bool) $suspended->fresh()->is_active);
        $this->actingAs($this->admin)->get(route('admin.storefront.vendor-stores.edit', $suspended))
            ->assertOk()->assertDontSee('name="is_active"', false);

        // ร้านปกติ: ปิด/เปิดจากสวิตช์ได้
        $active = $this->adminStore();
        $this->actingAs($this->admin)->postJson(route('admin.storefront.vendor-stores.toggle-status', $active))->assertOk()->assertJsonPath('is_active', false);
        $this->actingAs($this->admin)->postJson(route('admin.storefront.vendor-stores.toggle-status', $active))->assertOk()->assertJsonPath('is_active', true);

        // ยังเปิดร้านที่ถูกระงับคืนได้ตามปกติ
        $this->actingAs($this->admin)->post(route('admin.storefront.vendor-stores.unsuspend', $suspended))->assertSessionHas('success');
        $this->assertSame('active', $suspended->fresh()->status);

        // ตัวกรอง: ถูกระงับ ไม่รวมร้านปิดถาวร
        $newlySuspended = $this->adminStore(['status' => 'suspended', 'is_active' => false, 'store_name' => 'ร้านโดนระงับ']);
        $this->actingAs($this->admin)->get(route('admin.storefront.vendor-stores.index', ['status' => 'suspended']))
            ->assertOk()->assertSee('tp-card', false)
            ->assertSee($newlySuspended->store_name)
            ->assertDontSee($rejected->store_name);
        $this->actingAs($this->admin)->get(route('admin.storefront.vendor-stores.index', ['status' => 'closed']))
            ->assertOk()
            ->assertSee($rejected->store_name)
            ->assertSee($deleted->store_name)
            ->assertSee('บัญชีเจ้าของถูกลบ')
            ->assertDontSee($newlySuspended->store_name);
        $this->actingAs($this->admin)->get(route('admin.storefront.vendor-stores.index'))->assertOk();
    }

    // =====================================================
    // 9. VAT / แพ็กเกจ / GP โดยแอดมิน
    // =====================================================

    public function test_admin_vat_requires_tax_id_and_money_settings_are_audited(): void
    {
        $store = $this->adminStore();
        $package = VendorPackage::create([
            'package_name' => 'Pro', 'package_slug' => 'pro-'.Str::lower(Str::random(4)), 'display_name' => 'แพ็กเกจโปร',
            'price' => 990, 'setup_fee' => 0, 'currency' => 'THB', 'commission_rate' => 7,
            'trial_days' => 0, 'sort_order' => 1, 'is_active' => true, 'features' => ['สินค้าไม่จำกัด'],
        ]);

        $this->actingAs($this->admin)->from(route('admin.storefront.vendor-stores.edit', $store))
            ->put(route('admin.storefront.vendor-stores.update', $store), ['store_name' => $store->store_name, 'vat_registered' => '1'])
            ->assertSessionHasErrors('tax_id');
        $this->assertFalse((bool) $store->fresh()->vat_registered);

        $this->actingAs($this->admin)->from(route('admin.storefront.vendor-stores.edit', $store))
            ->put(route('admin.storefront.vendor-stores.update', $store), ['store_name' => $store->store_name, 'vat_registered' => '1', 'tax_id' => '12345'])
            ->assertSessionHasErrors('tax_id');

        $this->actingAs($this->admin)
            ->put(route('admin.storefront.vendor-stores.update', $store), [
                'store_name' => $store->store_name,
                'vat_registered' => '1',
                'tax_id' => '0105561234567',
                'package_id' => $package->id,
                'commission_rate' => '12',
            ])->assertSessionHasNoErrors();

        $store->refresh();
        $this->assertTrue((bool) $store->vat_registered);
        $this->assertSame('0105561234567', $store->tax_id);
        $logs = AccountingActivityLog::where('loggable_type', VendorStore::class)->where('loggable_id', $store->id);
        $this->assertSame(1, (clone $logs)->where('action', 'store.vat_registered_changed')->count());
        $this->assertSame(1, (clone $logs)->where('action', 'store.gp_settings_changed')->count());
        $this->assertSame($this->admin->id, (int) (clone $logs)->where('action', 'store.vat_registered_changed')->value('user_id'));

        // บันทึกซ้ำค่าเดิม → ไม่เพิ่มประวัติ
        $this->actingAs($this->admin)
            ->put(route('admin.storefront.vendor-stores.update', $store), [
                'store_name' => $store->store_name, 'vat_registered' => '1', 'tax_id' => '0105561234567',
                'package_id' => $package->id, 'commission_rate' => '12',
            ])->assertSessionHasNoErrors();
        $this->assertSame(2, (clone $logs)->count());

        // ล้างเลขผู้เสียภาษีของร้านที่จด VAT → ไม่ยอม
        $this->actingAs($this->admin)->from(route('admin.storefront.vendor-stores.edit', $store))
            ->put(route('admin.storefront.vendor-stores.update', $store), ['store_name' => $store->store_name, 'vat_registered' => '1', 'tax_id' => ''])
            ->assertSessionHasErrors('tax_id');
        $this->assertSame('0105561234567', $store->fresh()->tax_id);

        // ยกเลิก VAT → บันทึกประวัติ
        $this->actingAs($this->admin)
            ->put(route('admin.storefront.vendor-stores.update', $store), ['store_name' => $store->store_name, 'vat_registered' => '0'])
            ->assertSessionHasNoErrors();
        $this->assertFalse((bool) $store->fresh()->vat_registered);
        $this->assertSame(2, (clone $logs)->where('action', 'store.vat_registered_changed')->count());
    }

    // =====================================================
    // 10. แก้สินค้า (แอดมิน) — ช่องสต็อกว่าง
    // =====================================================

    public function test_admin_product_edit_blank_stock_fields(): void
    {
        $product = $this->makeProduct($this->seller, $this->store, ['stock_quantity' => 8, 'low_stock_threshold' => 4]);
        $payload = [
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price' => '150',
            'stock_quantity' => '6',
            'low_stock_threshold' => '3',
            'is_active' => '1',
            'track_inventory' => '1',
        ];

        $this->actingAs($this->admin)->from(route('admin.ecommerce.products.edit', $product))
            ->put(route('admin.ecommerce.products.update', $product), ['stock_quantity' => ''] + $payload)
            ->assertRedirect(route('admin.ecommerce.products.edit', $product))
            ->assertSessionHasErrors(['stock_quantity' => 'กรุณากรอกจำนวนสต็อก (ใส่ 0 ได้ถ้าหมด)']);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);

        // เกณฑ์ใกล้หมดว่าง → ใช้ค่าเดิม และบันทึกช่องอื่นได้
        $this->actingAs($this->admin)
            ->put(route('admin.ecommerce.products.update', $product), ['low_stock_threshold' => ''] + $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $product->refresh();
        $this->assertSame(6, (int) $product->stock_quantity);
        $this->assertSame(4, (int) $product->low_stock_threshold);

        $this->actingAs($this->admin)->get(route('admin.ecommerce.products.edit', $product))
            ->assertOk()->assertSee('tp-card', false);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * @return array{0: PosDevice, 1: PosSession}
     */
    private function posDeviceAndSession(): array
    {
        $device = PosDevice::create([
            'store_id' => $this->store->id, 'device_name' => 'เครื่องหน้าร้าน', 'device_type' => 'web',
            'subscription_status' => 'active', 'is_active' => true, 'is_online' => true,
        ]);
        $session = PosSession::create([
            'pos_device_id' => $device->id, 'user_id' => $this->seller->id, 'status' => 'open',
            'opened_at' => now()->subHour(), 'opening_cash' => 500,
        ]);

        return [$device, $session];
    }

    private function riderOrder(VendorStore $store, User $seller): Order
    {
        $product = $this->makeProduct($seller, $store, ['price' => 120]);
        $buyer = $this->makeBuyer();
        $address = $this->makeAddress($buyer, true);

        return $this->makeOrder($buyer, [['product' => $product, 'qty' => 1]], [
            'store_id' => $store->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'delivery_method' => 'rider',
            'shipping_fee' => 45,
            'total_amount' => 165,
            'shipping_address_id' => $address->id,
            'shipping_address_snapshot' => $address->toSnapshot(),
        ]);
    }

    private function adminStore(array $overrides = [], string $ownerRole = 'seller'): VendorStore
    {
        $owner = User::factory()->create(['role' => $ownerRole]);

        return VendorStore::create(array_merge([
            'user_id' => $owner->id,
            'store_name' => 'ร้านแอดมินทดสอบ '.Str::random(5),
            'store_slug' => 'admin-store-'.Str::lower(Str::random(8)),
            'store_description' => 'ร้านทดสอบ',
            'store_phone' => '0811111111',
            'business_type' => 'individual',
            'commission_rate' => 10,
            'status' => 'active',
            'is_active' => true,
            'is_verified' => true,
        ], $overrides));
    }
}
