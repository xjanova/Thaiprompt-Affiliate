<?php

namespace Tests\Feature\Push;

use App\Models\MobileDevice;
use App\Models\MobilePushNotification;
use App\Models\Notification;
use App\Models\PaymentTransaction;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceCategory;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AntiAbuseService;
use App\Services\NotificationService;
use App\Services\Payment\PaymentService;
use App\Services\ServiceBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 🔔 (2026-10-16) ปิดช่องโหว่ "เหตุการณ์สำคัญไม่เคยเด้งเข้ามือถือ" (ต้องใช้ MySQL)
 *
 * 1. จองบริการ: งานใหม่ → ผู้ให้บริการ · รับงาน/กำลังไป/เสร็จ/ยกเลิก → ลูกค้า
 * 2. เติมเงิน PromptPay+SMS (completePayment wallet_topup) → แจ้งผู้ใช้ครั้งเดียว
 * 3. push โปรตั้งเวลา (mobile_push_notifications) → คำสั่ง mobile-push:send-scheduled ส่งครั้งเดียว
 * 4. หลังบ้าน › แจ้งเตือน: เลือกผู้ใช้ = push รายคน · ทุกคน = push campaign ก้อนเดียว
 *
 * ยิงผ่าน Expo จริงในโค้ด (Http::fake) แล้วตรวจว่าใครได้ข้อความอะไร
 */
class PushCoverageGapsTest extends TestCase
{
    use RefreshDatabase;

    private int $deviceSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['exp.host/*' => fn (HttpRequest $r) => Http::response([
            'data' => array_map(fn () => ['status' => 'ok', 'id' => 'x'], $r->data()),
        ], 200)]);
    }

    // ───────────────────────── 1. จองบริการ ─────────────────────────

    public function test_new_booking_pushes_provider_and_lifecycle_pushes_customer(): void
    {
        [$booking, $provider, $customer] = $this->booking();
        $customerToken = $this->device($customer);
        $providerToken = $this->device($provider->user);

        $service = app(ServiceBookingService::class);

        $service->assignProvider($booking, $provider);
        $this->assertPushedTo($providerToken, 'มีงานจองใหม่');

        $service->acceptBooking($booking->fresh());
        $this->assertSame('provider_accepted', $booking->fresh()->status, 'รับงานต้องสำเร็จ (เดิม Error ทุกครั้ง)');
        $this->assertPushedTo($customerToken, 'ผู้ให้บริการรับงานแล้ว');

        $service->startJourney($booking->fresh());
        $this->assertPushedTo($customerToken, 'ผู้ให้บริการกำลังเดินทาง');

        $this->assertDatabaseHas('notifications', ['user_id' => $customer->id, 'type' => 'service_booking_booking_accepted']);
        $this->assertDatabaseHas('notifications', ['user_id' => $provider->user_id, 'type' => 'service_booking_provider_new_booking']);
    }

    public function test_customer_cancel_pushes_provider_and_provider_cancel_pushes_customer(): void
    {
        [$booking, $provider, $customer] = $this->booking(['status' => 'notifying_provider']);
        $customerToken = $this->device($customer);
        $providerToken = $this->device($provider->user);

        app(ServiceBookingService::class)->cancelBooking($booking, 'เปลี่ยนใจ', 'customer');
        $this->assertPushedTo($providerToken, 'ลูกค้ายกเลิกการจอง');
        $this->assertNotPushedTo($customerToken, 'การจองถูกยกเลิก');

        [$booking2] = $this->booking(['status' => 'provider_accepted'], $customer, $provider);
        $result = app(AntiAbuseService::class)->cancelBooking($booking2, 'provider', 'ติดธุระ');
        $this->assertTrue($result['success']);
        $this->assertPushedTo($customerToken, 'การจองถูกยกเลิก');
    }

    // ───────────────────────── 2. เติมเงิน PromptPay ─────────────────────────

    public function test_promptpay_topup_notifies_user_once(): void
    {
        $user = User::factory()->create();
        $token = $this->device($user);
        Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        $tx = PaymentTransaction::create([
            'transaction_id' => 'TOPUP-'.uniqid(),
            'user_id' => $user->id,
            'type' => 'wallet_topup',
            'payment_method' => 'promptpay',
            'status' => 'pending',
            'amount' => 300,
        ]);

        $payments = app(PaymentService::class);
        $payments->completePayment($tx);
        $payments->completePayment($tx->fresh()); // SMS ซ้ำ/เรียกซ้ำ ต้องไม่แจ้งซ้ำ

        $this->assertSame(1, Notification::where('user_id', $user->id)->where('type', 'deposit')->count());
        $this->assertPushedTo($token, 'เติมเงินสำเร็จ');
        $this->assertEquals(300.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    // ───────────────────────── 3. push โปรตั้งเวลา ─────────────────────────

    public function test_scheduled_promo_is_sent_once_and_stale_ones_are_closed(): void
    {
        $android = $this->device(User::factory()->create(), 'android');
        $ios = $this->device(User::factory()->create(), 'ios');

        $due = MobilePushNotification::create([
            'title' => 'โปรลด 50%', 'body' => 'วันนี้เท่านั้น', 'data' => ['action_url' => '/promo'],
            'target_platform' => 'android', 'status' => 'scheduled', 'scheduled_at' => now()->subMinute(),
        ]);
        $future = MobilePushNotification::create([
            'title' => 'โปรพรุ่งนี้', 'body' => 'x', 'target_platform' => 'all',
            'status' => 'scheduled', 'scheduled_at' => now()->addHour(),
        ]);
        $stale = MobilePushNotification::create([
            'title' => 'โปรเก่าค้าง', 'body' => 'x', 'target_platform' => 'all',
            'status' => 'scheduled', 'scheduled_at' => now()->subDays(2),
        ]);

        $this->artisan('mobile-push:send-scheduled')->assertSuccessful();
        $this->artisan('mobile-push:send-scheduled')->assertSuccessful();

        $this->assertSame(1, $this->pushCount($android, 'โปรลด 50%'), 'ถึงเวลาแล้วต้องส่งครั้งเดียว');
        $this->assertSame(0, $this->pushCount($ios, 'โปรลด 50%'), 'เลือก android ต้องไม่ส่ง iOS');
        $this->assertSame('sent', $due->fresh()->status);
        $this->assertSame(1, $due->fresh()->success_count);

        $this->assertSame('scheduled', $future->fresh()->status);
        $this->assertSame('failed', $stale->fresh()->status);
        $this->assertSame(0, $this->pushCount($android, 'โปรเก่าค้าง'));
    }

    // ───────────────────────── 4. หลังบ้าน › แจ้งเตือน ─────────────────────────

    public function test_admin_broadcast_to_chosen_users_pushes_each_user(): void
    {
        $chosen = User::factory()->create();
        $other = User::factory()->create();
        $chosenToken = $this->device($chosen);
        $otherToken = $this->device($other);

        $count = app(NotificationService::class)->broadcast(
            'promotion', 'คูปองพิเศษของคุณ', 'ใช้ได้ถึงสิ้นเดือน', [], null, null, 'normal',
            false, false, null, null, [$chosen->id]
        );

        $this->assertSame(1, $count);
        $this->assertPushedTo($chosenToken, 'คูปองพิเศษของคุณ');
        $this->assertNotPushedTo($otherToken, 'คูปองพิเศษของคุณ');
        $this->assertDatabaseHas('user_notifications', ['user_id' => $chosen->id, 'title' => 'คูปองพิเศษของคุณ']);
    }

    public function test_admin_broadcast_to_everyone_sends_one_campaign_push(): void
    {
        $a = $this->device(User::factory()->create());
        $b = $this->device(User::factory()->create());

        app(NotificationService::class)->broadcast('promotion', 'ประกาศถึงทุกคน', 'ตลาดเปิดแล้ว');

        $this->assertSame(1, $this->pushCount($a, 'ประกาศถึงทุกคน'));
        $this->assertSame(1, $this->pushCount($b, 'ประกาศถึงทุกคน'));
        $this->assertSame(1, MobilePushNotification::where('title', 'ประกาศถึงทุกคน')->where('status', 'sent')->count());
    }

    public function test_admin_broadcast_to_everyone_scheduled_waits_for_the_command(): void
    {
        $a = $this->device(User::factory()->create());

        app(NotificationService::class)->broadcast(
            'promotion', 'ประกาศตั้งเวลา', 'เร็วๆนี้', [], null, null, 'normal',
            false, false, null, null, null, now()->addMinutes(30)
        );

        $this->assertSame(0, $this->pushCount($a, 'ประกาศตั้งเวลา'));
        $campaign = MobilePushNotification::where('title', 'ประกาศตั้งเวลา')->firstOrFail();
        $this->assertSame('scheduled', $campaign->status);

        $this->travel(31)->minutes();
        $this->artisan('mobile-push:send-scheduled')->assertSuccessful();
        $this->assertSame(1, $this->pushCount($a, 'ประกาศตั้งเวลา'));
    }

    public function test_low_priority_and_web_only_broadcasts_do_not_push(): void
    {
        $a = $this->device(User::factory()->create());

        app(NotificationService::class)->broadcast('promotion', 'เงียบๆ', 'x', [], null, null, 'low');
        app(NotificationService::class)->broadcast('commission_earned', 'คอมมิชชั่น', 'x');

        $this->assertSame(0, MobilePushNotification::count());
        $this->assertSame(0, $this->pushCount($a, 'เงียบๆ') + $this->pushCount($a, 'คอมมิชชั่น'));
    }

    // ───────────────────────── helpers ─────────────────────────

    /**
     * @return array{0: ServiceBooking, 1: ServiceProvider, 2: User}
     */
    private function booking(array $overrides = [], ?User $customer = null, ?ServiceProvider $provider = null): array
    {
        $customer ??= User::factory()->create();
        $owner = User::factory()->create();

        if (! $provider) {
            $provider = ServiceProvider::create([
                'user_id' => User::factory()->create()->id,
                'owner_id' => $owner->id,
                'name' => 'ช่างสมชาย',
                'phone' => '0800000000',
                'status' => 'available',
                'is_active' => true,
            ]);
        }

        $category = ServiceCategory::create(['name' => 'ซ่อมแอร์', 'slug' => 'aircon-'.uniqid()]);
        $service = Service::create([
            'category_id' => $category->id,
            'owner_id' => $owner->id,
            'name' => 'ล้างแอร์',
            'slug' => 'aircon-clean-'.uniqid(),
            'base_price' => 500,
            'requires_location' => false,
        ]);

        $booking = ServiceBooking::create(array_merge([
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'provider_id' => isset($overrides['status']) ? $provider->id : null,
            'scheduled_at' => now()->addDay(),
            'base_price' => 500,
            'subtotal' => 500,
            'total_amount' => 500,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'status' => 'pending',
        ], $overrides));

        return [$booking, $provider->fresh(), $customer];
    }

    private function device(User $user, string $platform = 'android'): string
    {
        $this->deviceSeq++;
        $token = 'ExponentPushToken[test'.str_pad((string) $this->deviceSeq, 18, 'x').']';

        MobileDevice::create([
            'device_id' => $platform.'_'.$this->deviceSeq,
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

    private function assertNotPushedTo(string $token, string $titleContains): void
    {
        $this->assertSame(0, $this->pushCount($token, $titleContains), "ต้องไม่ได้ push \"{$titleContains}\"");
    }
}
