<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

/**
 * ตัวช่วยเทสต์ "แอปล็อกอินผ่านเว็บ" (PKCE) ด้วย LINE / Facebook / Google
 *
 * เดินตามแอปจริง: init → เปิด login_url → หน้าอนุญาตของผู้ให้บริการ → callback ของเว็บ
 * → หน้าพากลับแอป (thaiprompt://auth?code=..&state=..) → แลก code ที่ /api/v1/auth/mobile/exchange
 * ส่วนที่คุยกับ Google/Facebook จริง (แลก code → โปรไฟล์) แทนด้วย Socialite mock
 */
trait DrivesMobileAppLogin
{
    /**
     * code_verifier ของแอปทดสอบ (PKCE ต้องยาว 43-128 ตัว)
     */
    protected string $codeVerifier = 'test-verifier-0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * ล้าง static cache ของตั้งค่าดูดวงก่อน/หลังทุกเทสต์ (Laravel เรียกให้เองตามชื่อ trait)
     *
     * callback ของ Facebook เรียก FortuneTellingSetting::getSettings() ซึ่งจำค่าไว้ใน static
     * ข้าม RefreshDatabase — ไม่ล้าง = เทสต์คลาสถัดไปในโปรเซสเดียวกันได้ค่าเก่า (เพจว่าง) แล้วล้มแบบสุ่มตามลำดับ
     */
    protected function setUpDrivesMobileAppLogin(): void
    {
        \App\Models\FortuneTellingSetting::clearSettingsCache();
    }

    protected function tearDownDrivesMobileAppLogin(): void
    {
        \App\Models\FortuneTellingSetting::clearSettingsCache();
    }

    /**
     * แอปเริ่มล็อกอิน — คืน data จาก API (login_url, login_token, state)
     *
     * @return array{login_url: string, login_token: string, state: string}
     */
    protected function initMobileLogin(?string $provider = null): array
    {
        $payload = [
            'device_id' => 'test-device-1',
            'device_name' => 'Pixel ทดสอบ',
            'code_verifier' => $this->codeVerifier,
        ];

        if ($provider !== null) {
            $payload['provider'] = $provider;
        }

        return $this->postJson('/api/v1/auth/mobile/init', $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data');
    }

    /**
     * path + query ของ URL (ตัด host ออก ใช้ยิงผ่าน test client)
     */
    protected function relative(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        return $path.($query ? '?'.$query : '');
    }

    /**
     * query ของ URL เป็น array
     *
     * @return array<string, mixed>
     */
    protected function queryOf(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * โปรไฟล์ที่ผู้ให้บริการส่งกลับ (จำลอง)
     *
     * @param  array<string, mixed>  $raw
     */
    protected function socialiteUser(string $id, ?string $email, string $name = 'ผู้ใช้ ทดสอบ', array $raw = []): SocialiteUser
    {
        return (new SocialiteUser)
            ->setRaw($raw + ['sub' => $id, 'email' => $email])
            ->map([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'avatar' => 'https://example.test/avatar/'.$id.'.jpg',
            ]);
    }

    /**
     * ให้ Socialite::driver($driver)->user() คืนโปรไฟล์นี้ (แทนการคุยกับ Google/Facebook จริง)
     */
    protected function fakeProviderUser(string $driver, SocialiteUser $user): void
    {
        $provider = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $provider->shouldReceive('user')->andReturn($user);
        // ขาไป (หน้าอนุญาตของผู้ให้บริการ) ยังเรียกได้ระหว่าง mock
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(new \Illuminate\Http\RedirectResponse('https://provider.test/authorize'));

        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }

    /**
     * หน้าพากลับแอป → code / state ที่จะส่งเข้า thaiprompt://auth
     *
     * @return array{code: string, state: string}
     */
    protected function deepLinkFrom(TestResponse $response): array
    {
        $response->assertOk()->assertViewIs('auth.mobile-login-redirect');

        $url = (string) $response->viewData('redirectUrl');
        $this->assertStringStartsWith('thaiprompt://auth?', $url);

        // สคริปต์เด้งอัตโนมัติต้องเปิด URL เดียวกับปุ่มเป๊ะ (เดิมได้ "&amp;state" → แอปหา state ไม่เจอ)
        $this->assertSame($url, $this->scriptRedirectTarget($response));

        $query = $this->queryOf($url);

        return ['code' => (string) ($query['code'] ?? ''), 'state' => (string) ($query['state'] ?? '')];
    }

    /**
     * URL ที่สคริปต์ในหน้าพากลับแอปจะเปิด (ถอดจาก JSON ในหน้า)
     */
    protected function scriptRedirectTarget(TestResponse $response): string
    {
        preg_match('/window\.location\.href\s*=\s*("(?:[^"\\\\]|\\\\.)*")/', (string) $response->getContent(), $m);
        $this->assertNotEmpty($m, 'หน้าพากลับแอปต้องมีสคริปต์เด้งเข้าแอป');

        return (string) json_decode($m[1]);
    }

    /**
     * แอปแลก code เป็น token
     */
    protected function exchangeCode(string $code, string $state, ?string $verifier = null): TestResponse
    {
        return $this->postJson('/api/v1/auth/mobile/exchange', [
            'auth_code' => $code,
            'code_verifier' => $verifier ?? $this->codeVerifier,
            'state' => $state,
        ]);
    }
}
