<?php

namespace Tests\Feature\User;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceCategory;
use App\Models\ServiceProvider;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * หน้าที่อยู่จัดส่ง / คำสั่งซื้อ / การจองบริการของสมาชิก ธีมโนวาเต็ม
 * (/shipping-addresses*, /orders*, /user/bookings*)
 */
class NovaOrdersShippingPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
    }

    private function member(): User
    {
        return User::factory()->create(['role' => 'user', 'kyc_status' => 'approved']);
    }

    private function assertFullNova(string $html): void
    {
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html);
        $this->assertStringNotContainsString('tp-card', $html);
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('orders-shipping.css', $html);
    }

    private function address(User $owner): ShippingAddress
    {
        return ShippingAddress::create([
            'user_id' => $owner->id,
            'recipient_name' => 'สมชาย ใจดี',
            'phone_number' => '0811111111',
            'address_line_1' => '99/1 ถนนสุขุมวิท',
            'sub_district' => 'คลองเตย',
            'district' => 'คลองเตย',
            'province' => 'กรุงเทพมหานคร',
            'postal_code' => '10110',
            'country' => 'ไทย',
            'is_default' => true,
        ]);
    }

    /**
     * @return array{0: Order, 1: OrderItem}
     */
    private function deliveredOrder(User $owner): array
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $category = ProductCategory::create(['name' => 'หมวด', 'slug' => 'c-'.Str::random(6), 'is_active' => true]);
        $product = Product::create([
            'seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'สบู่สมุนไพร',
            'price' => 100, 'stock_quantity' => 5, 'is_active' => true, 'is_virtual' => false,
        ]);
        $order = Order::create([
            'user_id' => $owner->id, 'status' => 'delivered', 'payment_method' => 'promptpay',
            'payment_status' => 'paid', 'paid_at' => now(), 'delivered_at' => now(),
            'subtotal' => 100, 'shipping_fee' => 0, 'total_amount' => 100, 'tracking_number' => 'TH123456',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'seller_id' => $seller->id,
            'product_name' => 'สบู่สมุนไพร', 'product_sku' => 'SKU-'.Str::random(5), 'unit_price' => 100,
            'quantity' => 1, 'subtotal' => 100, 'discount_amount' => 0, 'total' => 100,
            'commission_rate' => 0, 'commission_amount' => 0, 'seller_earning' => 100, 'status' => 'pending',
        ]);

        return [$order, $item];
    }

    private function booking(User $owner, array $attributes = [], bool $withProvider = true): ServiceBooking
    {
        $svcOwner = User::factory()->create();
        $provider = $withProvider ? ServiceProvider::create([
            'user_id' => User::factory()->create()->id, 'owner_id' => $svcOwner->id, 'name' => 'ช่างเอก',
            'phone' => '0800000000', 'status' => 'available', 'is_active' => true,
        ]) : null;
        $category = ServiceCategory::create(['name' => 'ซ่อมบำรุง', 'slug' => 'sc-'.Str::random(6)]);
        $service = Service::create([
            'category_id' => $category->id, 'owner_id' => $svcOwner->id, 'name' => 'ล้างแอร์',
            'slug' => 's-'.Str::random(6), 'base_price' => 500, 'requires_location' => false,
        ]);

        return ServiceBooking::create(array_merge([
            'user_id' => $owner->id, 'service_id' => $service->id, 'provider_id' => $provider?->id,
            'scheduled_at' => now()->addDay(), 'base_price' => 500, 'subtotal' => 500, 'total_amount' => 1234.5,
            'payment_method' => 'cash', 'payment_status' => 'pending', 'status' => 'in_progress',
        ], $attributes));
    }

    public function test_shipping_address_pages_render_full_nova(): void
    {
        $user = $this->member();
        $address = $this->address($user);

        foreach (['/shipping-addresses', '/shipping-addresses/create', "/shipping-addresses/{$address->id}/edit"] as $uri) {
            $res = $this->actingAs($user)->get($uri);
            $res->assertOk();
            $this->assertFullNova($res->getContent());
        }

        $this->actingAs($user)->get('/shipping-addresses')->assertSee('สมชาย ใจดี');
        $this->actingAs($user)->get("/shipping-addresses/{$address->id}/edit")->assertSee('99/1 ถนนสุขุมวิท');
    }

    public function test_cannot_edit_another_members_address(): void
    {
        $address = $this->address($this->member());

        $this->actingAs($this->member())->get("/shipping-addresses/{$address->id}/edit")->assertNotFound();
    }

    public function test_order_pages_render_full_nova(): void
    {
        $user = $this->member();
        [$order, $item] = $this->deliveredOrder($user);

        foreach (['/orders', "/orders/{$order->id}", "/orders/{$order->id}/items/{$item->id}/review"] as $uri) {
            $res = $this->actingAs($user)->get($uri);
            $res->assertOk();
            $this->assertFullNova($res->getContent());
        }

        $this->actingAs($user)->get("/orders/{$order->id}")->assertSee($order->order_number)->assertSee('สบู่สมุนไพร');
    }

    public function test_orders_index_ignores_array_status_filter(): void
    {
        $user = $this->member();
        $this->deliveredOrder($user);

        $this->actingAs($user)->get('/orders?status[]=delivered')->assertOk();
    }

    public function test_cannot_open_another_members_order_or_review_form(): void
    {
        [$order, $item] = $this->deliveredOrder($this->member());
        $other = $this->member();

        $this->actingAs($other)->get("/orders/{$order->id}")->assertNotFound();
        $this->actingAs($other)->get("/orders/{$order->id}/items/{$item->id}/review")->assertNotFound();
    }

    public function test_booking_pages_render_full_nova(): void
    {
        $user = $this->member();
        $booking = $this->booking($user);

        foreach (['/user/bookings', "/user/bookings/{$booking->id}", "/user/bookings/{$booking->id}/track"] as $uri) {
            $res = $this->actingAs($user)->get($uri);
            $res->assertOk();
            $this->assertFullNova($res->getContent());
        }
    }

    public function test_booking_index_shows_real_total_and_stats_and_survives_array_filter(): void
    {
        $user = $this->member();
        $this->booking($user);
        $this->booking($user, ['status' => 'completed']);

        // 🐛 เดิมอ่าน total_price (ไม่มีคอลัมน์) ราคาเป็น 0 ทุกแถว และไม่ส่ง $stats
        $this->actingAs($user)->get('/user/bookings')->assertOk()->assertSee('1,234.50');
        $this->actingAs($user)->get('/user/bookings?status=completed')->assertOk();
        $this->actingAs($user)->get('/user/bookings?status[]=x')->assertOk();
    }

    public function test_booking_index_stats_and_tab_grouping_are_correct(): void
    {
        $user = $this->member();
        $waiting = $this->booking($user, ['status' => 'waiting_provider']);
        $accepted = $this->booking($user, ['status' => 'provider_accepted']);
        $onWay = $this->booking($user, ['status' => 'provider_on_way']);
        $done = $this->booking($user, ['status' => 'completed']);
        $rejected = $this->booking($user, ['status' => 'provider_rejected']);
        $this->booking($this->member(), ['status' => 'completed']); // ของคนอื่น ต้องไม่ถูกนับ

        $res = $this->actingAs($user)->get('/user/bookings')->assertOk();
        $this->assertSame(['total' => 5, 'waiting' => 1, 'active' => 2, 'completed' => 1, 'cancelled' => 1], $res->viewData('stats'));

        // แท็บ "กำลังทำ" ต้องเห็นทั้งงานที่รับแล้วและกำลังเดินทาง (เดิมเห็นแค่ in_progress)
        $active = $this->actingAs($user)->get('/user/bookings?status=active')->viewData('bookings');
        $this->assertEqualsCanonicalizing([$accepted->id, $onWay->id], $active->pluck('id')->all());

        $cancelled = $this->actingAs($user)->get('/user/bookings?status=cancelled')->viewData('bookings');
        $this->assertSame([$rejected->id], $cancelled->pluck('id')->all());

        $completed = $this->actingAs($user)->get('/user/bookings?status=completed')->viewData('bookings');
        $this->assertSame([$done->id], $completed->pluck('id')->all());

        $waitingTab = $this->actingAs($user)->get('/user/bookings?status=waiting')->viewData('bookings');
        $this->assertSame([$waiting->id], $waitingTab->pluck('id')->all());
    }

    public function test_booking_show_renders_without_missing_method_error(): void
    {
        $user = $this->member();
        $booking = $this->booking($user, ['status' => 'completed']);

        // 🐛 เดิม 500 ทุกครั้ง: ServiceBooking::getStatusIcon() ไม่มีอยู่จริง
        $this->actingAs($user)->get("/user/bookings/{$booking->id}")
            ->assertOk()
            ->assertSee($booking->booking_number)
            ->assertSee('ให้คะแนนบริการ')
            ->assertSee(route('user.service-reviews.store', $booking), false);
    }

    public function test_booking_track_html_and_json_variants(): void
    {
        $user = $this->member();
        $booking = $this->booking($user);

        // 🐛 เดิมลิงก์ "ติดตาม" ได้ JSON ดิบแทนหน้าเว็บ
        $html = $this->actingAs($user)->get("/user/bookings/{$booking->id}/track");
        $html->assertOk()->assertHeader('content-type', 'text/html; charset=UTF-8');

        $json = $this->actingAs($user)->getJson("/user/bookings/{$booking->id}/track");
        $json->assertOk()
            ->assertJsonPath('booking.status', 'in_progress')
            ->assertJsonPath('booking.trackable', true);
        $this->assertStringNotContainsString('battery', $json->getContent());
        $json->assertJsonPath('provider.phone', '0800000000');
    }

    public function test_booking_track_json_hides_provider_location_and_phone_after_job_ends(): void
    {
        $user = $this->member();
        $booking = $this->booking($user, ['status' => 'completed']);
        $booking->provider->forceFill([
            'current_latitude' => 13.75, 'current_longitude' => 100.5, 'last_location_update' => now(),
        ])->save();

        $this->actingAs($user)->getJson("/user/bookings/{$booking->id}/track")
            ->assertOk()
            ->assertJsonPath('booking.trackable', false)
            ->assertJsonPath('provider_location', null)
            ->assertJsonPath('provider.phone', null)
            ->assertJsonPath('provider.name', 'ช่างเอก');
    }

    public function test_review_form_on_booking_show_submits(): void
    {
        $user = $this->member();
        $booking = $this->booking($user, ['status' => 'completed']);

        $this->actingAs($user)
            ->from(route('user.bookings.show', $booking))
            ->post(route('user.service-reviews.store', $booking), ['rating' => 5, 'review' => 'บริการดีมาก'])
            ->assertRedirect(route('user.bookings.show', $booking))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(5, (int) $booking->rating);
        $this->assertSame('บริการดีมาก', $booking->review);

        $this->actingAs($this->member())
            ->post(route('user.service-reviews.store', $booking), ['rating' => 1])
            ->assertForbidden();
    }

    public function test_shipping_address_failure_shows_generic_message(): void
    {
        $user = $this->member();
        ShippingAddress::creating(function () {
            throw new \RuntimeException('SQLSTATE[42S22]: Column not found: shipping_addresses.secret');
        });

        $res = $this->actingAs($user)->from('/shipping-addresses/create')->post('/shipping-addresses', [
            'recipient_name' => 'สมชาย ใจดี', 'phone_number' => '0811111111', 'address_line_1' => '99/1',
            'sub_district' => 'คลองเตย', 'district' => 'คลองเตย', 'province' => 'กรุงเทพมหานคร', 'postal_code' => '10110',
        ]);

        $res->assertRedirect('/shipping-addresses/create');
        $this->assertSame('ทำรายการไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', session('error'));
    }

    public function test_cannot_open_another_members_booking(): void
    {
        $booking = $this->booking($this->member());
        $other = $this->member();

        $this->actingAs($other)->get("/user/bookings/{$booking->id}")->assertForbidden();
        $this->actingAs($other)->get("/user/bookings/{$booking->id}/track")->assertForbidden();
        $this->actingAs($other)->postJson("/user/bookings/{$booking->id}/location", ['latitude' => 13.7, 'longitude' => 100.5])->assertForbidden();
    }

    public function test_share_location_updates_booking_only_while_trackable(): void
    {
        $user = $this->member();
        $booking = $this->booking($user);

        $this->actingAs($user)
            ->postJson("/user/bookings/{$booking->id}/location", ['latitude' => 13.7563, 'longitude' => 100.5018])
            ->assertOk()
            ->assertJsonPath('success', true);

        $booking->refresh();
        $this->assertEqualsWithDelta(13.7563, (float) $booking->user_live_latitude, 0.0001);
        $this->assertNotNull($booking->user_location_updated_at);

        // 🛡️ ต้องบันทึกลง location logs เหมือนเส้นทาง API (ใช้ตรวจ GPS spoofing)
        $this->assertDatabaseHas('service_booking_location_logs', [
            'service_booking_id' => $booking->id, 'actor_type' => 'user', 'user_id' => $user->id,
        ]);

        $done = $this->booking($user, ['status' => 'completed']);
        $this->actingAs($user)
            ->postJson("/user/bookings/{$done->id}/location", ['latitude' => 13.7, 'longitude' => 100.5])
            ->assertStatus(422);
    }

    public function test_cancel_requires_reason_and_cancels(): void
    {
        $user = $this->member();
        $booking = $this->booking($user, ['status' => 'pending'], false);

        $this->actingAs($user)->post("/user/bookings/{$booking->id}/cancel", [])->assertSessionHasErrors('reason');

        $this->actingAs($user)
            ->post("/user/bookings/{$booking->id}/cancel", ['reason' => 'เปลี่ยนวันนัด'])
            ->assertRedirect(route('user.bookings.show', $booking));

        $this->assertSame('cancelled', $booking->fresh()->status);
    }
}
