<?php

namespace Tests\Feature\Platform;

use App\Jobs\SendNotificationPush;
use App\Models\MobileDevice;
use App\Models\Notification;
use App\Models\User;
use App\Services\ExpoPushService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * แจ้งเตือนในระบบ → มือถือ (CC-07) + ถอด push token ตอน logout (CC-21)
 *
 * เทสต์รันด้วย QUEUE_CONNECTION=sync (phpunit.xml) → SendNotificationPush รันทันทีใน create()
 * ใช้ DB (MySQL บน CI) · ยิง Expo จริงไม่ได้ → Http::fake
 */
class PushBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_mirrored_to_app_inbox_and_pushed(): void
    {
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok']]], 200)]);

        $user = User::factory()->create();
        $this->device($user, 'ExponentPushToken[bridge-1]');

        $notification = app(NotificationService::class)->create(
            $user, 'rider_job', 'งานใหม่ใกล้คุณ', 'ค่าส่ง 45 บาท ระยะ 3.2 กม.', ['job_id' => 7], '/rider/jobs/7'
        );

        $this->assertTrue((bool) $notification->fresh()->push_sent);
        $this->assertNotNull($notification->fresh()->push_sent_at);

        $inbox = DB::table('user_notifications')->where('user_id', $user->id)->first();
        $this->assertNotNull($inbox, 'ต้องคัดลอกลงกล่องแจ้งเตือนของแอป (user_notifications)');
        $this->assertSame('rider', $inbox->type);
        $this->assertSame('งานใหม่ใกล้คุณ', $inbox->title);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $message = $request->data()[0] ?? [];

            return ($message['to'] ?? null) === 'ExponentPushToken[bridge-1]'
                && ($message['data']['job_id'] ?? null) === 7
                && in_array($message['channelId'] ?? null, SendNotificationPush::ANDROID_CHANNELS, true);
        });

        // รันซ้ำ (retry/ส่งซ้ำ) ต้องไม่เด้งซ้ำ และไม่คัดลอกซ้ำ
        (new SendNotificationPush($notification->id))->handle(app(ExpoPushService::class));
        Http::assertSentCount(1);
        $this->assertSame(1, DB::table('user_notifications')->where('user_id', $user->id)->count());
    }

    public function test_low_priority_goes_to_inbox_without_push(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $this->device($user, 'ExponentPushToken[bridge-2]');

        $notification = app(NotificationService::class)->create(
            $user, 'system', 'ข่าวสารระบบ', 'ปรับปรุงระบบคืนนี้', [], null, null, 'low'
        );

        Http::assertNothingSent();
        $this->assertFalse((bool) $notification->fresh()->push_sent);
        $this->assertSame(1, DB::table('user_notifications')->where('user_id', $user->id)->count());
    }

    public function test_push_already_sent_directly_is_not_repeated(): void
    {
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok']]], 200)]);

        $user = User::factory()->create();
        $this->device($user, 'ExponentPushToken[bridge-3]');

        // โค้ดต้นทางยิง push เองก่อน แล้วค่อยสร้างแจ้งเตือนในระบบหัวข้อเดียวกัน
        app(ExpoPushService::class)->sendToUser($user->id, 'ออเดอร์ถูกจัดส่งแล้ว', 'เลขพัสดุ TH123', ['order_id' => 1]);
        app(NotificationService::class)->create($user, 'order', 'ออเดอร์ถูกจัดส่งแล้ว', 'เลขพัสดุ TH123', ['order_id' => 1]);

        Http::assertSentCount(1);
        $this->assertSame(1, DB::table('user_notifications')->where('user_id', $user->id)->count());
    }

    public function test_suspended_user_and_broadcast_are_not_bridged(): void
    {
        Http::fake();

        $suspended = User::factory()->create();
        $suspended->forceFill(['blocked_at' => now()])->save();
        $this->device($suspended, 'ExponentPushToken[bridge-4]');

        app(NotificationService::class)->create($suspended, 'wallet', 'เติมเงินสำเร็จ', '100 บาท');
        $this->assertSame(0, DB::table('user_notifications')->where('user_id', $suspended->id)->count());

        $broadcast = Notification::create([
            'user_id' => $suspended->id, 'type' => 'promotion', 'title' => 'โปร', 'message' => 'ลด 50%',
            'is_broadcast' => true,
        ]);
        $this->assertFalse(SendNotificationPush::shouldDispatch($broadcast));

        Http::assertNothingSent();
    }

    public function test_logout_detaches_this_device_push_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;
        $this->device($user, 'ExponentPushToken[logout-1]', 'dev-logout-1');

        $this->withToken($token)->postJson('/api/v1/logout', ['push_token' => 'ExponentPushToken[logout-1]'])
            ->assertOk()
            ->assertJsonPath('data.push_tokens_removed', 1);

        $this->assertNull(DB::table('mobile_devices')->where('device_id', 'dev-logout-1')->value('user_id'));
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count());
    }

    public function test_remove_push_token_endpoints_only_touch_own_devices(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->device($me, 'ExponentPushToken[mine]', 'dev-mine');
        $this->device($other, 'ExponentPushToken[theirs]', 'dev-theirs');
        $token = $me->createToken('mobile-app')->plainTextToken;

        // path ที่แอปเรียกอยู่แล้ว (เดิม 404)
        $this->withToken($token)->deleteJson('/api/v1/mobile/push-token', ['token' => 'ExponentPushToken[mine]'])
            ->assertOk()
            ->assertJsonPath('data.removed', 1);
        $this->assertNull(DB::table('mobile_devices')->where('device_id', 'dev-mine')->value('user_id'));

        // พยายามถอด token ของคนอื่น → ไม่มีผล
        $this->withToken($token)->postJson('/api/v1/push/token/remove', ['token' => 'ExponentPushToken[theirs]'])
            ->assertOk()
            ->assertJsonPath('data.removed', 0);
        $this->assertSame((int) $other->id, (int) DB::table('mobile_devices')->where('device_id', 'dev-theirs')->value('user_id'));

        $this->withToken($token)->deleteJson('/api/v1/push/token', [])->assertStatus(422);
    }

    private function device(User $user, string $pushToken, ?string $deviceId = null): MobileDevice
    {
        return MobileDevice::create([
            'device_id' => $deviceId ?? 'dev-'.$user->id.'-'.substr(md5($pushToken), 0, 6),
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => $pushToken,
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }
}
