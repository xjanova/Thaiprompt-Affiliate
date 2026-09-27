<?php

namespace Tests\Feature;

use App\Models\FacebookOAuthSetting;
use App\Models\GoogleOAuthSetting;
use App\Models\LineOaSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\DrivesMobileAppLogin;
use Tests\TestCase;

/**
 * 📱 (2026-09-27) สถานะปุ่มเข้าสู่ระบบในแอป + การกระโดดไปผู้ให้บริการจาก /mobile-login
 *
 * - GET /api/v1/auth/social/status บอกแอปว่าควรแสดงปุ่ม LINE / Facebook / Google ไหม
 *   (เปิดใช้ + ตั้งค่าครบเท่านั้น — Google ยังไม่ตั้งค่า = false → แอปซ่อนปุ่ม ไม่มีปุ่มพัง)
 * - provider ต้องอยู่ใน whitelist (line|facebook|google) ทั้งตอน init และบนหน้า /mobile-login
 * - LINE ผ่านตัวกลางออก code ตัวเดียวกับ Facebook/Google แล้วต้องยังทำงานเหมือนเดิม
 */
class SocialStatusTest extends TestCase
{
    use DrivesMobileAppLogin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response(['access_token' => 'line-access-token', 'expires_in' => 2592000]),
            'api.line.me/v2/profile' => Http::response([
                'userId' => 'U0000000000000000000000000000pkce',
                'displayName' => 'ไลน์ ทดสอบ',
            ]),
            '*' => Http::response([], 200),
        ]);
    }

    private function configureAll(): void
    {
        LineOaSetting::create([
            'login_channel_id' => '1234567890',
            'channel_secret' => 'line-secret',
            'redirect_uri' => 'https://main.thaiprompt.online/auth/line/callback',
            'is_active' => true,
        ]);
        FacebookOAuthSetting::create(['app_id' => '664172615253513', 'app_secret' => 'fb-secret', 'is_enabled' => true]);
        GoogleOAuthSetting::create(['client_id' => 'gid.apps.googleusercontent.com', 'client_secret' => 'g-secret', 'is_active' => true]);

        Cache::forget('line_oa_settings');
        FacebookOAuthSetting::clearCache();
        GoogleOAuthSetting::clearCache();
    }

    public function test_status_is_all_off_when_nothing_is_configured(): void
    {
        $this->getJson('/api/v1/auth/social/status')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => ['line' => false, 'facebook' => false, 'google' => false],
            ]);
    }

    public function test_status_turns_on_each_configured_provider(): void
    {
        $this->configureAll();

        $this->getJson('/api/v1/auth/social/status')
            ->assertOk()
            ->assertJsonPath('data.line', true)
            ->assertJsonPath('data.facebook', true)
            ->assertJsonPath('data.google', true);
    }

    public function test_disabled_google_is_reported_off_and_never_leaks_credentials(): void
    {
        $this->configureAll();
        GoogleOAuthSetting::first()->update(['is_active' => false]);

        $response = $this->getJson('/api/v1/auth/social/status')
            ->assertOk()
            ->assertJsonPath('data.google', false)
            ->assertJsonPath('data.facebook', true);

        $this->assertStringNotContainsString('secret', $response->getContent());
        $this->assertStringNotContainsString('gid.apps', $response->getContent());
    }

    public function test_init_accepts_only_whitelisted_providers(): void
    {
        foreach (['twitter', 'https://evil.example', '../auth/line', 'GOOGLE'] as $provider) {
            $this->postJson('/api/v1/auth/mobile/init', [
                'device_id' => 'device-1',
                'code_verifier' => $this->codeVerifier,
                'provider' => $provider,
            ])->assertStatus(422);
        }

        $init = $this->initMobileLogin('line');
        $this->assertSame('line', $this->queryOf($init['login_url'])['provider']);

        // ไม่ส่ง provider = หน้าเข้าสู่ระบบปกติ (แอปรุ่นเก่า)
        $plain = $this->initMobileLogin();
        $this->assertArrayNotHasKey('provider', $this->queryOf($plain['login_url']));
    }

    public function test_mobile_login_page_ignores_unknown_provider(): void
    {
        $this->configureAll();
        $init = $this->initMobileLogin();

        $this->get('/mobile-login?'.http_build_query([
            'token' => $init['login_token'],
            'state' => $init['state'],
            'provider' => 'https://evil.example',
        ]))->assertOk()->assertViewIs('auth.mobile-login');
    }

    public function test_mobile_login_page_explains_when_the_chosen_provider_is_off(): void
    {
        $init = $this->initMobileLogin();

        $this->get('/mobile-login?'.http_build_query([
            'token' => $init['login_token'],
            'state' => $init['state'],
            'provider' => 'google',
        ]))->assertOk()
            ->assertViewIs('auth.mobile-login')
            ->assertSee('ยังไม่เปิดให้เข้าสู่ระบบด้วยช่องทางนี้');
    }

    public function test_mobile_login_page_shows_facebook_and_google_buttons_only_when_configured(): void
    {
        $init = $this->initMobileLogin();
        $url = '/mobile-login?'.http_build_query(['token' => $init['login_token'], 'state' => $init['state']]);

        $this->get($url)->assertOk()
            ->assertDontSee('เข้าสู่ระบบด้วย Facebook')
            ->assertDontSee('เข้าสู่ระบบด้วย Google');

        $this->configureAll();

        $this->get($url)->assertOk()
            ->assertSee('เข้าสู่ระบบด้วย Facebook')
            ->assertSee('เข้าสู่ระบบด้วย Google')
            ->assertSee(e(route('google.login', ['mobile_token' => $init['login_token'], 'state' => $init['state']])), false)
            ->assertSee(e(route('facebook.login', ['mobile_token' => $init['login_token'], 'state' => $init['state']])), false);
    }

    public function test_line_in_the_app_still_ends_with_a_code_through_the_shared_authorizer(): void
    {
        $this->configureAll();
        $user = User::factory()->create(['line_user_id' => 'U0000000000000000000000000000pkce']);
        $init = $this->initMobileLogin('line');

        // /mobile-login?provider=line → /auth/line?mobile_token=..&state=.. → LINE
        $jump = $this->get($this->relative($init['login_url']))->assertRedirect()->headers->get('Location');
        $this->assertSame(route('line.login', ['mobile_token' => $init['login_token'], 'state' => $init['state']]), $jump);
        $lineUrl = $this->get($this->relative($jump))->assertRedirect()->headers->get('Location');
        $lineState = $this->queryOf($lineUrl)['state'];

        // LINE ส่งกลับ callback ของเว็บ → หน้าพากลับแอปพร้อม code
        $deepLink = $this->deepLinkFrom($this->get('/auth/line/callback?'.http_build_query(['code' => 'line-code', 'state' => $lineState])));

        $this->exchangeCode($deepLink['code'], $deepLink['state'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }
}
