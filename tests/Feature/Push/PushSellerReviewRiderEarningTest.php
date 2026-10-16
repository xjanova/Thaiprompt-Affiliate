<?php

namespace Tests\Feature\Push;

use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\MobileDevice;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceCategory;
use App\Models\ServiceProvider;
use App\Models\StoreRating;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\RiderEarningService;
use App\Services\ServiceBookingNotificationService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Money\MoneyTestCase;

/**
 * 🔔 (2026-10-16) ปิดช่องโหว่ push ข้อ 5 (ต้องใช้ MySQL)
 *
 * 1. ร้านช้อปได้รีวิวสินค้า / ดาวร้าน → push ผู้ขาย (ครั้งเดียว)
 * 2. ร้านตลาดสดได้ดาวจากผู้ซื้อ → push ร้าน (ครั้งเดียว)
 * 3. ค่าส่ง (+ โบนัสจากร้าน) เข้าวอลเลตไรเดอร์ → push ไรเดอร์ (ครั้งเดียว) · COD ที่หักเงินไรเดอร์ไม่แจ้ง "เงินเข้า"
 * 4. จองบริการ: LINE push ให้ผู้ให้บริการปิดเป็นค่าเริ่มต้น (โควต้า 300/เดือน) — push แอปยังไปครบ
 *
 * ยิงผ่าน Expo จริงในโค้ด (Http::fake) แล้วตรวจว่าใครได้ข้อความอะไร
 */
class PushSellerReviewRiderEarningTest extends MoneyTestCase
{
    private int $deviceSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'exp.host/*' => fn (HttpRequest $r) => Http::response([
                'data' => array_map(fn () => ['status' => 'ok', 'id' => 'x'], $r->data()),
            ], 200),
            'api.line.me/*' => Http::response([], 200),
        ]);
    }

    // ───────────────────────── 1. ร้านช้อป ─────────────────────────

    public function test_shop_product_review_via_app_pushes_seller_once(): void
    {
        $seller = $this->makeSeller();
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 100, ['name' => 'กาแฟดริป']);
        $order = $this->makePaidOrder($buyer, [[$product, 1]], 0, ['status' => 'delivered']);
        $item = $order->items()->firstOrFail();
        $sellerToken = $this->device($seller);
        $buyerToken = $this->device($buyer);

        Sanctum::actingAs($buyer);
        $url = "/api/v1/orders/{$order->id}/items/{$item->id}/review";
        $this->postJson($url, ['rating' => 5, 'comment' => 'หอมมาก ส่งไว'])->assertCreated();
        $this->postJson($url, ['rating' => 1, 'comment' => 'ลองซ้ำ'])->assertStatus(409);

        $this->assertPushedTo($sellerToken, 'ลูกค้ารีวิวสินค้า 5 ดาว');
        $this->assertSame(0, $this->pushCount($buyerToken, 'รีวิว'), 'ผู้รีวิวต้องไม่ได้ push ของตัวเอง');

        $n = Notification::where('user_id', $seller->id)->where('type', 'seller_review')->sole();
        $this->assertStringContainsString('กาแฟดริป', $n->message);
        $this->assertSame('merchant-order', $n->data['screen']);
        $this->assertSame($order->id, $n->data['order_id']);
    }

    public function test_store_rating_pushes_owner_on_first_rating_only(): void
    {
        $seller = $this->makeSeller();
        $buyer = $this->makeUser('ผู้ซื้อ');
        $store = VendorStore::where('user_id', $seller->id)->firstOrFail();
        $ownerToken = $this->device($seller);

        StoreRating::rateStore($store, $buyer, ['rating' => 4, 'comment' => 'บริการดี']);
        StoreRating::rateStore($store, $buyer, ['rating' => 2, 'comment' => 'แก้คะแนน']);

        $this->assertPushedTo($ownerToken, 'ลูกค้ารีวิวร้าน 4 ดาว');
        $this->assertSame(0, $this->pushCount($ownerToken, '2 ดาว'), 'แก้คะแนนเดิมต้องไม่ push ซ้ำ');
        $this->assertSame(1, Notification::where('user_id', $seller->id)->where('type', 'seller_review')->count());
    }

    // ───────────────────────── 2. ตลาดสด ─────────────────────────

    public function test_fresh_market_review_pushes_seller_once(): void
    {
        FreshMarketSetting::clearCache();
        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'platform_fee_percentage' => 10,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => false,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();

        $sellerUser = $this->makeUser('แม่ค้าผัก');
        $buyer = $this->makeUser('ผู้ซื้อ');
        $shop = FreshMarketSeller::create([
            'user_id' => $sellerUser->id,
            'shop_name' => 'ผักสดป้าแดง',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.75,
            'longitude' => 100.5,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);
        $order = FreshMarketOrder::create([
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
        ]);
        $sellerToken = $this->device($sellerUser);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/v1/fresh-market/orders/{$order->id}/review", ['rating' => 5, 'review' => 'ผักสดมาก'])->assertOk();
        $this->postJson("/api/v1/fresh-market/orders/{$order->id}/review", ['rating' => 3])->assertStatus(409);

        $this->assertPushedTo($sellerToken, 'ลูกค้ารีวิวร้าน 5 ดาว');
        $n = Notification::where('user_id', $sellerUser->id)->where('type', 'seller_review')->sole();
        $this->assertStringContainsString('ผักสดมาก', $n->message);
        $this->assertSame('fresh-market', $n->data['source']);
        $this->assertSame($order->id, $n->data['order_id']);
    }

    // ───────────────────────── 3. เงินเข้าไรเดอร์ ─────────────────────────

    public function test_prepaid_delivery_fee_and_shop_bonus_push_rider_once(): void
    {
        [$rider, $job] = $this->riderJob(['cod_amount' => 0, 'shop_bonus' => 15]);
        $riderToken = $this->device($rider->user);

        $earnings = app(RiderEarningService::class);
        $this->assertTrue($earnings->settle($job));
        $this->assertFalse($earnings->settle($job->fresh()), 'เคลียร์ซ้ำต้องไม่เครดิต/แจ้งซ้ำ');

        // ค่าส่ง 32 + โบนัสจากร้าน 15 = 47
        $this->assertEqualsWithDelta(47.0, $this->walletBalance($rider->user), 0.001);
        $this->assertPushedTo($riderToken, 'ค่าส่ง ฿47.00 เข้ากระเป๋าแล้ว');

        $n = Notification::where('user_id', $rider->user_id)->where('type', 'rider_earning')->sole();
        $this->assertStringContainsString('โบนัสจากร้าน ฿15.00', $n->message);
        $this->assertSame($job->id, $n->data['job_id']);
    }

    public function test_cod_job_that_debits_rider_does_not_claim_money_in(): void
    {
        [$rider, $job] = $this->riderJob(['cod_amount' => 140], 200);
        $riderToken = $this->device($rider->user);

        $this->assertFalse(app(RiderEarningService::class)->settle($job));

        $this->assertNotNull($job->fresh()->cod_settled_at);
        $this->assertSame(0, $this->pushCount($riderToken, 'เข้ากระเป๋าแล้ว'));
        $this->assertSame(0, Notification::where('user_id', $rider->user_id)->where('type', 'rider_earning')->count());
    }

    public function test_settle_without_rider_notice_credits_silently(): void
    {
        // ทางสแกนส่งมอบ: HandoverService แจ้ง "ส่งมอบสำเร็จ รายได้ ฿X เข้ากระเป๋าแล้ว" เอง → ห้าม push ซ้ำ
        [$rider, $job] = $this->riderJob(['cod_amount' => 0]);
        $riderToken = $this->device($rider->user);

        $this->assertTrue(app(RiderEarningService::class)->settle($job, false));
        $this->assertEqualsWithDelta(32.0, $this->walletBalance($rider->user), 0.001);
        $this->assertSame(0, $this->pushCount($riderToken, 'เข้ากระเป๋าแล้ว'));
    }

    // ───────────────────────── 4. จองบริการ: LINE push ─────────────────────────

    public function test_new_booking_never_calls_line_even_when_provider_has_line_enabled(): void
    {
        // owner (2026-10-16): ไม่แจ้งผ่าน LINE แล้ว แจ้งผ่านแอปอย่างเดียว — ต่อให้มี token + ผู้ให้บริการผูก LINE และเปิดแจ้ง LINE ไว้
        config(['services.line.channel_access_token' => 'test-line-token']);
        [$booking, $provider] = $this->serviceBooking();
        $this->assertTrue($provider->isLineNotificationEnabled(), 'fixture ต้องเป็นผู้ให้บริการที่เปิด LINE ไว้จริง');
        $providerToken = $this->device($provider->user);

        $results = app(ServiceBookingNotificationService::class)->notifyNewBooking($booking, $provider);

        $this->assertArrayNotHasKey('line', $results);
        $this->assertSame(0, $this->lineCalls(), 'ห้ามยิง api.line.me ทุกกรณี');
        $this->assertSame(0, \App\Models\ServiceBookingNotification::where('booking_id', $booking->id)->where('channel', 'line')->count());
        $this->assertPushedTo($providerToken, 'มีงานจองใหม่');
        $this->assertDatabaseHas('notifications', ['user_id' => $provider->user_id, 'type' => 'service_booking_provider_new_booking']);
    }

    // ───────────────────────── helpers ─────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: Rider, 1: RiderJob}
     */
    private function riderJob(array $overrides, float $walletBalance = 0): array
    {
        $seller = $this->makeSeller();
        $buyer = $this->makeUser('ผู้ซื้อ');
        $product = $this->makeProduct($seller, 100);
        $order = $this->makePaidOrder($buyer, [[$product, 1]], 40, [
            'status' => 'processing',
            'payment_method' => ($overrides['cod_amount'] ?? 0) > 0 ? 'cod' : 'wallet',
            'payment_status' => ($overrides['cod_amount'] ?? 0) > 0 ? 'pending' : 'paid',
            'delivery_method' => 'rider',
        ]);

        $user = $this->makeUser('ไรเดอร์', $walletBalance);
        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ทดสอบ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'offline',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'approved_at' => now(),
        ])->save();

        $job = new RiderJob;
        $job->forceFill(array_merge([
            'rider_id' => $rider->id,
            'job_type' => 'shop_delivery',
            'source_type' => (new Order)->getMorphClass(),
            'source_id' => $order->id,
            'title' => 'ส่งสินค้า '.$order->order_number,
            'pickup_address' => 'ร้านทดสอบ สีลม',
            'pickup_latitude' => 13.7291,
            'pickup_longitude' => 100.5210,
            'delivery_address' => 'บ้านลูกค้า บางรัก',
            'delivery_latitude' => 13.7300,
            'delivery_longitude' => 100.5300,
            'base_fee' => 30,
            'distance_fee' => 10,
            'total_fee' => 40,
            'rider_earnings' => 32,
            'platform_fee' => 8,
            'status' => 'completed',
            'accepted_at' => now()->subHour(),
            'completed_at' => now(),
        ], $overrides))->save();

        return [$rider->fresh(), $job->fresh()];
    }

    /**
     * @return array{0: ServiceBooking, 1: ServiceProvider}
     */
    private function serviceBooking(): array
    {
        $owner = $this->makeUser('เจ้าของบริการ');
        $provider = ServiceProvider::create([
            'user_id' => $this->makeUser('ช่าง')->id,
            'owner_id' => $owner->id,
            'name' => 'ช่างสมชาย',
            'phone' => '0800000000',
            'status' => 'available',
            'is_active' => true,
            'line_user_id' => 'U'.str_repeat('a', 32),
            'notification_settings' => ['line' => true],
        ]);

        $category = ServiceCategory::create(['name' => 'ซ่อมแอร์', 'slug' => 'aircon-'.uniqid()]);
        $service = Service::create([
            'category_id' => $category->id,
            'owner_id' => $owner->id,
            'name' => 'ล้างแอร์',
            'slug' => 'aircon-clean-'.uniqid(),
            'base_price' => 500,
            'requires_location' => false,
        ]);

        $booking = ServiceBooking::create([
            'user_id' => $this->makeUser('ลูกค้า')->id,
            'service_id' => $service->id,
            'provider_id' => $provider->id,
            'scheduled_at' => now()->addDay(),
            'base_price' => 500,
            'subtotal' => 500,
            'total_amount' => 500,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'status' => 'notifying_provider',
        ]);

        return [$booking, $provider->fresh()];
    }

    private function lineCalls(): int
    {
        return Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), 'api.line.me'))->count();
    }

    private function device(User $user, string $platform = 'android'): string
    {
        $this->deviceSeq++;
        $token = 'ExponentPushToken[rv'.str_pad((string) $this->deviceSeq, 18, 'x').']';

        MobileDevice::create([
            'device_id' => $platform.'_rv_'.$this->deviceSeq,
            'user_id' => $user->id,
            'platform' => $platform,
            'push_token' => $token,
            'app_version' => '3.392.0',
            'is_active' => true,
            'last_active_at' => now(),
        ]);

        return $token;
    }

    private function pushCount(string $token, string $titleContains): int
    {
        return Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), 'exp.host'))
            ->flatMap(fn ($pair) => $pair[0]->data())
            ->filter(fn ($m) => ($m['to'] ?? null) === $token && str_contains((string) ($m['title'] ?? ''), $titleContains))
            ->count();
    }

    private function assertPushedTo(string $token, string $titleContains): void
    {
        $this->assertSame(1, $this->pushCount($token, $titleContains), "ต้องได้ push \"{$titleContains}\" ครั้งเดียว");
    }
}
