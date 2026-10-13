<?php

namespace Tests\Feature\Push;

use App\Models\MobileDevice;
use App\Models\User;
use App\Services\ExpoPushService;
use App\Services\Fcm\FcmHttpV1Client;
use App\Services\Fcm\FcmSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * 🔔 (2026-10-13) แอป Thai Prompt APP บน Android ลงทะเบียน FCM device token ตรง (ไม่ผ่าน Expo)
 *
 * กติกาที่ต้องเป็นจริง:
 * - token Expo → Expo Push API เหมือนเดิม · token FCM → FCM HTTP v1 ของเราเอง
 * - ข้อความ FCM เป็น data-only รูปแบบเดียวกับ Expo (title/message/body JSON/channelId) ให้ expo-notifications แสดงได้
 * - งานไรเดอร์เข้าช่อง orders (สำคัญสูง) · token ที่ FCM บอกว่าตายแล้วถูกล้าง
 * - ยังไม่ตั้งค่า FCM = นับว่าล้ม ไม่ระเบิด
 */
class AppFcmDirectPushTest extends TestCase
{
    use RefreshDatabase;

    private const FCM_TOKEN = 'dGVzdC1mY20tdG9rZW4:APA91bH_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_abcdefghij';

    private const DEAD_FCM_TOKEN = 'ZGVhZC1mY20tdG9rZW4:APA91bH_9876543210abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_zyxwvutsrq';

    private const EXPO_TOKEN = 'ExponentPushToken[abcdefghijklmnopqrstuv]';

    public function test_fcm_token_is_sent_directly_as_expo_compatible_data_message(): void
    {
        $sends = $this->fakeFcm();
        Http::fake(['exp.host/*' => Http::response(['data' => [['status' => 'ok']]], 200)]);

        $user = User::factory()->create();
        $this->device($user, self::FCM_TOKEN, 'android_a');
        $this->device($user, self::EXPO_TOKEN, 'ios_b');

        $result = app(ExpoPushService::class)->sendToUser($user->id, 'มีงานใหม่ใกล้คุณ', 'ค่าส่ง 45 บาท', [
            'type' => 'rider_job_offer',
            'job_id' => 77,
            'push_channel' => 'rider',
        ]);

        $this->assertSame(2, $result['success']);
        $this->assertSame(0, $result['failed']);

        $this->assertCount(1, $sends);
        $this->assertSame(self::FCM_TOKEN, $sends[0]['token']);
        $this->assertNull($sends[0]['notification'], 'ต้องเป็น data-only ให้ expo-notifications สร้างแจ้งเตือนเอง');
        $this->assertSame('มีงานใหม่ใกล้คุณ', $sends[0]['data']['title']);
        $this->assertSame('ค่าส่ง 45 บาท', $sends[0]['data']['message']);
        $this->assertSame('orders', $sends[0]['data']['channelId'], 'งานไรเดอร์ต้องเข้าช่องสำคัญสูง');
        $this->assertSame(
            ['type' => 'rider_job_offer', 'job_id' => 77, 'push_channel' => 'rider'],
            json_decode($sends[0]['data']['body'], true)
        );

        // token Expo ยังไปทาง Expo เหมือนเดิม (ช่องเดียวกัน)
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'exp.host')
            && $r->data()[0]['to'] === self::EXPO_TOKEN
            && $r->data()[0]['channelId'] === 'orders');
    }

    public function test_dead_fcm_token_is_cleared_and_counted_as_failed(): void
    {
        $client = Mockery::mock(FcmHttpV1Client::class);
        $client->shouldReceive('isConfigured')->andReturn(true);
        $client->shouldReceive('send')->andReturnUsing(fn ($token) => $token === self::DEAD_FCM_TOKEN
            ? new FcmSendResult(false, true, 'UNREGISTERED', 404)
            : new FcmSendResult(true, false, null, 200));
        $this->app->instance(FcmHttpV1Client::class, $client);

        $user = User::factory()->create();
        $alive = $this->device($user, self::FCM_TOKEN, 'android_a');
        $dead = $this->device($user, self::DEAD_FCM_TOKEN, 'android_b');

        $result = app(ExpoPushService::class)->sendToUser($user->id, 'ออเดอร์ใหม่', 'มีออเดอร์เข้าร้าน', ['channel' => 'orders']);

        $this->assertSame(1, $result['success']);
        $this->assertSame(1, $result['failed']);
        $this->assertNull($dead->fresh()->push_token, 'token ที่ถอนแอปแล้วต้องถูกล้าง');
        $this->assertSame(self::FCM_TOKEN, $alive->fresh()->push_token);
    }

    public function test_without_fcm_credentials_android_tokens_fail_quietly(): void
    {
        $client = Mockery::mock(FcmHttpV1Client::class);
        $client->shouldReceive('isConfigured')->andReturn(false);
        $client->shouldNotReceive('send');
        $this->app->instance(FcmHttpV1Client::class, $client);

        $user = User::factory()->create();
        $this->device($user, self::FCM_TOKEN, 'android_a');

        $result = app(ExpoPushService::class)->sendToUser($user->id, 'ทดสอบ', 'ข้อความ');

        $this->assertSame(0, $result['success']);
        $this->assertSame(1, $result['failed']);
        $this->assertContains('FCM not configured', $result['errors']);
    }

    public function test_token_classification_and_channel_mapping(): void
    {
        $this->assertTrue(ExpoPushService::isFcmDeviceToken(self::FCM_TOKEN));
        $this->assertFalse(ExpoPushService::isFcmDeviceToken(self::EXPO_TOKEN));
        $this->assertFalse(ExpoPushService::isFcmDeviceToken('short:token'));
        $this->assertFalse(ExpoPushService::isFcmDeviceToken(str_repeat('a', 120).' '.str_repeat('b', 10)));

        $this->assertSame('orders', ExpoPushService::androidChannelFor(['channel' => 'orders']));
        $this->assertSame('orders', ExpoPushService::androidChannelFor(['push_channel' => 'rider']));
        $this->assertSame('default', ExpoPushService::androidChannelFor(['channel' => 'no-such-channel']));
        $this->assertSame('default', ExpoPushService::androidChannelFor([]));
    }

    public function test_push_test_endpoint_sends_a_real_push_to_my_devices_only(): void
    {
        $sends = $this->fakeFcm();
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->device($me, self::FCM_TOKEN, 'android_me');
        $this->device($other, self::DEAD_FCM_TOKEN, 'android_other');

        \Laravel\Sanctum\Sanctum::actingAs($me);
        $this->postJson('/api/v1/mobile/push-test')
            ->assertOk()
            ->assertJsonPath('data.sent', 1);

        $this->assertCount(1, $sends);
        $this->assertSame(self::FCM_TOKEN, $sends[0]['token']);
        $this->assertSame('ทดสอบการแจ้งเตือน', $sends[0]['data']['title']);
    }

    public function test_push_test_endpoint_reports_missing_token_and_failures_in_thai(): void
    {
        $me = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($me);

        $this->postJson('/api/v1/mobile/push-test')
            ->assertStatus(422)
            ->assertJsonPath('code', 'NO_PUSH_TOKEN');

        $client = Mockery::mock(FcmHttpV1Client::class);
        $client->shouldReceive('isConfigured')->andReturn(true);
        $client->shouldReceive('send')->andReturn(new FcmSendResult(false, false, 'INTERNAL', 500));
        $this->app->instance(FcmHttpV1Client::class, $client);
        $this->device($me, self::FCM_TOKEN, 'android_me');

        $this->postJson('/api/v1/mobile/push-test')
            ->assertStatus(502)
            ->assertJsonPath('code', 'PUSH_FAILED')
            ->assertJsonMissingPath('errors');
    }

    /**
     * FCM ปลอม — คืน ArrayObject ของสิ่งที่ "ส่ง" (token, data, notification)
     */
    private function fakeFcm(): \ArrayObject
    {
        $sends = new \ArrayObject;
        $fcm = Mockery::mock(FcmHttpV1Client::class);
        $fcm->shouldReceive('isConfigured')->andReturn(true);
        $fcm->shouldReceive('send')->andReturnUsing(function ($token, $data, $notification = null) use ($sends) {
            $sends[] = compact('token', 'data', 'notification');

            return new FcmSendResult(true, false, null, 200);
        });
        $this->app->instance(FcmHttpV1Client::class, $fcm);

        return $sends;
    }

    private function device(User $user, string $token, string $deviceId): MobileDevice
    {
        return MobileDevice::create([
            'device_id' => $deviceId,
            'user_id' => $user->id,
            'platform' => str_starts_with($deviceId, 'ios') ? 'ios' : 'android',
            'push_token' => $token,
            'app_version' => '3.390.0',
            'is_active' => true,
            'last_active_at' => now(),
        ]);
    }
}
