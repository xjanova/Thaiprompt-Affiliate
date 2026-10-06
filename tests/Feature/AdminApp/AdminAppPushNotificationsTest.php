<?php

namespace Tests\Feature\AdminApp;

use App\Console\Commands\AdminAppPushAlerts;
use App\Models\AdminPushToken;
use App\Models\FortuneReading;
use App\Services\Fcm\FcmHttpV1Client;
use App\Services\Fcm\FcmSendResult;
use App\Services\FcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\BuildsAdminAppWorld;
use Tests\TestCase;

/**
 * 🔔 (v3) แจ้งเตือนแอปแอดมิน
 *
 *   - devices/push-token ลงทะเบียน/ถอน · ไม่คืน token ใน JSON · 1 token = 1 แถว
 *   - ออกจากระบบ = ลบ token ของเครื่องนั้น · ออกทุกเครื่อง = ลบทั้งหมดของแอดมินคนนั้น
 *   - admin-app:push-alerts ส่งเมื่อคิว "เพิ่มขึ้น" เท่านั้น · token เสียถูกลบ · ไม่มี credentials = เงียบ
 *   - ตัวส่ง FCM ที่แยกออกมา: แอป SMS Checker ยังได้ข้อความรูปเดิม (channel sms_payment_channel)
 */
#[Group('admin-app')]
class AdminAppPushNotificationsTest extends TestCase
{
    use BuildsAdminAppWorld;
    use RefreshDatabase;

    private const TOKEN_A = 'fcmTokenA_0123456789abcdefghijklmnopqrstuvwxyz';

    private const TOKEN_B = 'fcmTokenB_0123456789abcdefghijklmnopqrstuvwxyz';

    protected function tearDown(): void
    {
        $this->tearDownAdminAppWorld();
        Cache::forget(AdminAppPushAlerts::SNAPSHOT_KEY);

        parent::tearDown();
    }

    public function test_register_reassign_and_remove_push_token(): void
    {
        $admin = $this->actAs($this->makeAdmin());

        $res = $this->postJson('/api/admin/devices/push-token', [
            'token' => self::TOKEN_A, 'platform' => 'android', 'device_id' => 'pixel-7', 'app_version' => '1.2.0',
        ])->assertOk()->assertJsonPath('data.registered', true)->assertJsonPath('data.platform', 'android');
        $this->assertStringNotContainsString(self::TOKEN_A, $res->getContent(), 'ไม่คืน token');
        $this->assertDatabaseHas('admin_push_tokens', ['user_id' => $admin->id, 'token' => self::TOKEN_A, 'device_id' => 'pixel-7']);

        // เครื่องเดิมได้ token ใหม่ → token เก่าของเครื่องนั้นถูกแทน
        $this->postJson('/api/admin/devices/push-token', ['token' => self::TOKEN_B, 'platform' => 'android', 'device_id' => 'pixel-7'])->assertOk();
        $this->assertSame([self::TOKEN_B], AdminPushToken::query()->pluck('token')->all());

        // เครื่องเดียวกันล็อกอินแอดมินอีกคน → แถวย้ายเจ้าของ (ไม่ซ้ำ)
        $other = $this->actAs($this->makeAdmin());
        $this->postJson('/api/admin/devices/push-token', ['token' => self::TOKEN_B, 'platform' => 'ios'])->assertOk();
        $this->assertSame(1, AdminPushToken::query()->count());
        $this->assertSame($other->id, AdminPushToken::query()->value('user_id'));

        // ถอนได้เฉพาะของตัวเอง
        $this->actAs($admin);
        $this->deleteJson('/api/admin/devices/push-token', ['token' => self::TOKEN_B])->assertOk()->assertJsonPath('data.removed', 0);
        $this->actAs($other);
        $this->deleteJson('/api/admin/devices/push-token', ['token' => self::TOKEN_B])->assertOk()->assertJsonPath('data.removed', 1);
        $this->assertSame(0, AdminPushToken::query()->count());

        $this->postJson('/api/admin/devices/push-token', ['token' => 'short', 'platform' => 'android'])->assertStatus(422);
        $this->postJson('/api/admin/devices/push-token', ['token' => self::TOKEN_A, 'platform' => 'symbian'])->assertStatus(422);
        $this->deleteJson('/api/admin/devices/push-token', [])->assertStatus(422);
    }

    public function test_logout_removes_that_devices_token_and_logout_all_removes_every_device(): void
    {
        $admin = $this->makeAdmin();
        $phone = $admin->createToken('phone', ['admin'])->plainTextToken;
        $tablet = $admin->createToken('tablet', ['admin'])->plainTextToken;

        $this->withToken($phone)->postJson('/api/admin/devices/push-token', ['token' => self::TOKEN_A, 'platform' => 'android'])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($tablet)->postJson('/api/admin/devices/push-token', ['token' => self::TOKEN_B, 'platform' => 'android'])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->assertNotNull(AdminPushToken::query()->where('token', self::TOKEN_A)->value('access_token_id'));

        // ออกจากระบบบนมือถือ → ลบแค่ของมือถือ
        $this->withToken($phone)->postJson('/api/admin/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->assertSame([self::TOKEN_B], AdminPushToken::query()->pluck('token')->all());

        // ออกทุกเครื่องจากแท็บเล็ต → ลบหมด
        AdminPushToken::query()->create(['user_id' => $admin->id, 'token' => self::TOKEN_A, 'platform' => 'android']);
        $this->withToken($tablet)->postJson('/api/admin/auth/logout-all')->assertOk();
        $this->assertSame(0, AdminPushToken::query()->where('user_id', $admin->id)->count());
    }

    public function test_push_alerts_fire_only_when_a_queue_grows_and_drop_dead_tokens(): void
    {
        $admin = $this->makeAdmin();
        $good = AdminPushToken::query()->create(['user_id' => $admin->id, 'token' => self::TOKEN_A, 'platform' => 'android']);
        $dead = AdminPushToken::query()->create(['user_id' => $admin->id, 'token' => self::TOKEN_B, 'platform' => 'android']);

        $sends = [];
        $fcm = Mockery::mock(FcmHttpV1Client::class);
        $fcm->shouldReceive('isConfigured')->andReturn(true);
        $fcm->shouldReceive('send')->andReturnUsing(function ($token, $data, $notification) use (&$sends) {
            $sends[] = compact('token', 'data', 'notification');

            return $token === self::TOKEN_B
                ? new FcmSendResult(false, true, 'UNREGISTERED', 404)
                : new FcmSendResult(true, false, null, 200);
        });
        $this->app->instance(FcmHttpV1Client::class, $fcm);

        // รอบแรก = เก็บฐาน ไม่ส่ง (มีบิลรอตรวจอยู่แล้ว 1)
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.21, 'slip_received_at' => now()->subMinutes(3)]);
        $this->artisan('admin-app:push-alerts')->assertSuccessful();
        $this->assertSame([], $sends);
        $this->assertSame(1, Cache::get(AdminAppPushAlerts::SNAPSHOT_KEY)['bills_awaiting']);

        // ไม่มีอะไรเปลี่ยน → ไม่ส่ง
        $this->artisan('admin-app:push-alerts')->assertSuccessful();
        $this->assertSame([], $sends);

        // บิลรอตรวจเพิ่ม → ส่งหาทุกเครื่อง · token ที่ FCM บอกว่าตายถูกลบ
        $this->makeReading(['conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT, 'amount_paid' => 39.22, 'transfer_reported' => true, 'transfer_reported_at' => now()]);
        $this->artisan('admin-app:push-alerts')->assertSuccessful();

        $this->assertCount(2, $sends);
        $this->assertSame(['type' => 'bills_awaiting', 'route' => '/work?tab=bills', 'count' => '2'], $sends[0]['data']);
        $this->assertSame('🧾 บิลรอตรวจการโอน', $sends[0]['notification']['title']);
        $this->assertStringContainsString('2 บิล', $sends[0]['notification']['body']);
        $this->assertNotNull(AdminPushToken::query()->find($good->id));
        $this->assertNull(AdminPushToken::query()->find($dead->id), 'UNREGISTERED → ลบ token');

        // ลดลง → ไม่ส่ง
        FortuneReading::query()->update(['slip_received_at' => null, 'transfer_reported' => false]);
        $sends = [];
        $this->artisan('admin-app:push-alerts')->assertSuccessful();
        $this->assertSame([], $sends);
        $this->assertSame(0, Cache::get(AdminAppPushAlerts::SNAPSHOT_KEY)['bills_awaiting']);
    }

    public function test_push_alerts_are_silent_without_firebase_credentials_or_devices(): void
    {
        $fcm = Mockery::mock(FcmHttpV1Client::class);
        $fcm->shouldReceive('isConfigured')->andReturn(false);
        $fcm->shouldNotReceive('send');
        $this->app->instance(FcmHttpV1Client::class, $fcm);

        AdminPushToken::query()->create(['user_id' => $this->makeAdmin()->id, 'token' => self::TOKEN_A, 'platform' => 'android']);
        $this->artisan('admin-app:push-alerts')->assertSuccessful();
        $this->assertNull(Cache::get(AdminAppPushAlerts::SNAPSHOT_KEY));

        // มี credentials แต่ไม่มีเครื่องลงทะเบียน → ไม่คำนวณ ล้างฐานทิ้ง
        $fcm2 = Mockery::mock(FcmHttpV1Client::class);
        $fcm2->shouldReceive('isConfigured')->andReturn(true);
        $fcm2->shouldNotReceive('send');
        $this->app->instance(FcmHttpV1Client::class, $fcm2);
        AdminPushToken::query()->delete();
        Cache::put(AdminAppPushAlerts::SNAPSHOT_KEY, ['bills_awaiting' => 5], 60);
        $this->artisan('admin-app:push-alerts')->assertSuccessful();
        $this->assertNull(Cache::get(AdminAppPushAlerts::SNAPSHOT_KEY));
    }

    public function test_command_is_scheduled_every_minute_without_overlap(): void
    {
        \Illuminate\Support\Facades\Artisan::call('schedule:list');
        $this->assertStringContainsString('admin-app:push-alerts', \Illuminate\Support\Facades\Artisan::output());

        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'admin-app:push-alerts'));

        $this->assertNotNull($event, 'ลงทะเบียนใน routes/console.php');
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_fcm_client_classifies_dead_tokens_and_keeps_the_sms_checker_payload(): void
    {
        config(['services.firebase.project_id' => 'plptdb']);
        $client = Mockery::mock(FcmHttpV1Client::class)->makePartial();
        $client->shouldReceive('getAccessToken')->andReturn('ya29.test-access-token');
        $this->app->instance(FcmHttpV1Client::class, $client);

        Http::fake([
            'fcm.googleapis.com/*' => Http::sequence()
                ->push(['name' => 'projects/plptdb/messages/1'], 200)
                ->push(['error' => ['code' => 404, 'status' => 'NOT_FOUND', 'message' => 'Requested entity was not found.',
                    'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']]]], 404)
                ->push(['error' => ['code' => 400, 'status' => 'INVALID_ARGUMENT', 'message' => 'Invalid JSON payload received.',
                    'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'INVALID_ARGUMENT']]]], 400)
                ->push(['error' => ['code' => 400, 'status' => 'INVALID_ARGUMENT', 'message' => 'The registration token is not a valid FCM registration token',
                    'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'INVALID_ARGUMENT']]]], 400),
        ]);

        // แอป SMS Checker — ข้อความรูปเดิมผ่านตัวส่งใหม่
        $sms = new FcmNotificationService;
        $send = new \ReflectionMethod($sms, 'sendToToken');
        $this->assertTrue($send->invoke($sms, self::TOKEN_A, ['type' => 'sync', 'n' => 5], ['title' => 'ทดสอบ', 'body' => 'x']));

        Http::assertSent(function (HttpRequest $r) {
            $m = $r->data()['message'] ?? [];

            return str_contains($r->url(), '/v1/projects/plptdb/messages:send')
                && $r->hasHeader('Authorization', 'Bearer ya29.test-access-token')
                && $m['token'] === self::TOKEN_A
                && $m['data'] === ['type' => 'sync', 'n' => '5']
                && $m['android'] === ['priority' => 'high', 'ttl' => '86400s',
                    'notification' => ['channel_id' => 'sms_payment_channel', 'click_action' => 'OPEN_ORDERS']]
                && $m['notification'] === ['title' => 'ทดสอบ', 'body' => 'x'];
        });

        $unregistered = $client->send(self::TOKEN_A, ['type' => 'x']);
        $this->assertFalse($unregistered->ok);
        $this->assertTrue($unregistered->invalidToken);

        $badPayload = $client->send(self::TOKEN_A, ['type' => 'x']);
        $this->assertFalse($badPayload->invalidToken, 'INVALID_ARGUMENT ทั่วไปอาจเป็น payload ของเราเอง — ห้ามลบ token');

        $badToken = $client->send(self::TOKEN_A, ['type' => 'x']);
        $this->assertTrue($badToken->invalidToken);
    }
}
