<?php

namespace Tests\Feature;

use App\Models\FacebookOAuthSetting;
use App\Models\MobileAuthToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\DrivesMobileAppLogin;
use Tests\TestCase;

/**
 * 📱 (2026-09-27) แอปเข้าสู่ระบบด้วย Facebook — ใช้ค่าเดียวกับเว็บ
 *
 * owner: "ให้ใช้ค่าเดียวกับเว็บ" → App ID/Secret จาก FacebookOAuthSetting ตัวเดิม
 *   และ redirect URI = /auth/facebook/callback ที่ลงทะเบียนไว้แล้ว (ห้ามมี URI ใหม่)
 *
 * เส้นที่ต้องผ่าน: แอป init(provider=facebook) → /mobile-login → /auth/facebook?mobile_token
 *   → Facebook → /auth/facebook/callback (ของเว็บ) → thaiprompt://auth?code → แอปแลก code
 * ส่วนคุยกับ Facebook จริงแทนด้วย Socialite mock ที่เหลือเป็นของจริง
 *
 * @group security
 */
class FacebookMobileLoginTest extends TestCase
{
    use DrivesMobileAppLogin;
    use RefreshDatabase;

    private const APP_ID = '664172615253513';

    private const WEB_CALLBACK = 'https://main.thaiprompt.online/auth/facebook/callback';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        FacebookOAuthSetting::create([
            'app_id' => self::APP_ID,
            'app_secret' => 'test-app-secret',
            'redirect_uri' => self::WEB_CALLBACK,
            'is_enabled' => true,
        ]);
        FacebookOAuthSetting::clearCache();

        // Graph API (ids_for_pages ฯลฯ) ห้ามหลุดไปของจริง
        Http::fake(['*' => Http::response(['data' => []], 200)]);
    }

    /**
     * แอปกดปุ่ม Facebook → ได้ URL หน้าอนุญาตของ Facebook
     *
     * @return array{0: array{login_url: string, login_token: string, state: string}, 1: string}
     */
    private function startAppFacebookLogin(): array
    {
        $init = $this->initMobileLogin('facebook');

        $jump = $this->get($this->relative($init['login_url']))->assertRedirect()->headers->get('Location');
        $this->assertSame(route('facebook.login', ['mobile_token' => $init['login_token'], 'state' => $init['state']]), $jump);

        $facebook = $this->get($this->relative($jump))->assertRedirect()->headers->get('Location');

        return [$init, $facebook];
    }

    public function test_app_uses_the_same_app_id_and_the_web_callback_already_registered_with_facebook(): void
    {
        [, $facebookUrl] = $this->startAppFacebookLogin();
        $query = $this->queryOf($facebookUrl);

        $this->assertStringContainsString('facebook.com', $facebookUrl);
        $this->assertSame(self::APP_ID, $query['client_id']);
        $this->assertSame(self::WEB_CALLBACK, $query['redirect_uri']);
    }

    public function test_facebook_callback_hands_the_app_a_code_it_can_exchange_once(): void
    {
        [$init] = $this->startAppFacebookLogin();

        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-asid-1', 'fb.user@example.com', 'เอฟบี ทดสอบ'));
        $deepLink = $this->deepLinkFrom($this->get('/auth/facebook/callback?code=abc&state=x'));

        $this->assertSame($init['state'], $deepLink['state']);
        // เส้นแอปไม่ทิ้ง session เว็บ (remember) ไว้ในเบราว์เซอร์ของมือถือ
        $this->assertGuest();

        $user = User::where('facebook_user_id', 'fb-asid-1')->firstOrFail();
        $this->exchangeCode($deepLink['code'], $deepLink['state'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token']]);

        // deep link + auth session ส่ง code เดียวกันมาสองทาง → ครั้งที่สองต้องไม่ได้ token
        $this->exchangeCode($deepLink['code'], $deepLink['state'])->assertStatus(401);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_existing_web_facebook_user_gets_the_same_account_in_the_app(): void
    {
        $existing = User::factory()->create(['facebook_user_id' => 'fb-asid-2']);
        $this->startAppFacebookLogin();

        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-asid-2', null));
        $deepLink = $this->deepLinkFrom($this->get('/auth/facebook/callback?code=abc&state=x'));

        $this->exchangeCode($deepLink['code'], $deepLink['state'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $existing->id);

        $this->assertSame(1, User::where('facebook_user_id', 'fb-asid-2')->count());
    }

    public function test_user_cancel_at_facebook_goes_back_to_the_app_login_page_with_thai_message(): void
    {
        [$init] = $this->startAppFacebookLogin();

        $this->get('/auth/facebook/callback?error=access_denied&error_reason=user_denied&state=x')
            ->assertRedirect(route('mobile-login.show', ['token' => $init['login_token'], 'state' => $init['state']]))
            ->assertSessionHas('error', 'การเข้าสู่ระบบด้วย Facebook ถูกยกเลิก');

        $this->assertNull(MobileAuthToken::first()->auth_code);
        $this->assertGuest();
    }

    public function test_code_is_only_issued_for_the_token_this_browser_started_with(): void
    {
        // callback ไม่รับ mobile_token จาก query — ต่อให้แนบ token คนอื่นมาก็ไม่ออก code ให้
        $victimInit = $this->initMobileLogin('facebook');

        $this->get('/auth/facebook');
        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-asid-3', 'web@example.com'));

        $this->get('/auth/facebook/callback?'.http_build_query([
            'code' => 'abc',
            'state' => 'x',
            'mobile_token' => $victimInit['login_token'],
        ]))->assertRedirect(route('user.wallet.index'));

        $this->assertNull(MobileAuthToken::first()->auth_code);
    }

    public function test_app_link_with_expired_token_never_reaches_facebook(): void
    {
        $init = $this->initMobileLogin('facebook');
        MobileAuthToken::query()->update(['login_token_expires_at' => now()->subMinute()]);

        $this->get(route('facebook.login', ['mobile_token' => $init['login_token'], 'state' => $init['state']]))
            ->assertOk()
            ->assertViewIs('auth.mobile-login-error');
    }

    public function test_web_facebook_login_still_goes_to_the_wallet_and_ignores_foreign_redirects(): void
    {
        $this->get('/auth/facebook?redirect='.urlencode('https://evil.example/steal'));
        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-asid-4', 'web2@example.com'));

        $this->get('/auth/facebook/callback?code=abc&state=x')
            ->assertRedirect(route('user.wallet.index'));

        $this->assertAuthenticated();
    }

    public function test_web_facebook_login_keeps_a_redirect_inside_our_site(): void
    {
        $this->get('/auth/facebook?redirect=/user/fortune-referral/recruit');
        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-asid-5', 'web3@example.com'));

        $this->get('/auth/facebook/callback?code=abc&state=x')
            ->assertRedirect('/user/fortune-referral/recruit');
    }

    public function test_unverified_local_account_is_never_auto_linked_by_facebook_email(): void
    {
        // คนร้ายตั้งอีเมลเหยื่อไว้ในบัญชีตัวเอง (ยืนยันไม่ได้) → เหยื่อกด Facebook ในแอป
        $squatter = User::factory()->create(['email' => 'victim@example.com', 'email_verified_at' => null]);
        [$init] = $this->startAppFacebookLogin();

        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-victim', 'victim@example.com'));
        $this->get('/auth/facebook/callback?code=abc&state=x')
            ->assertRedirect(route('mobile-login.show', ['token' => $init['login_token'], 'state' => $init['state']]))
            ->assertSessionHas('error', 'มีบัญชีที่ใช้อีเมลนี้อยู่แล้ว กรุณาเข้าสู่ระบบด้วยอีเมลและรหัสผ่าน');

        $this->assertNull($squatter->fresh()->facebook_user_id);
        $this->assertSame(0, User::where('facebook_user_id', 'fb-victim')->count());
        $this->assertNull(MobileAuthToken::first()->auth_code);
    }

    public function test_admin_account_is_never_auto_linked_by_facebook_email(): void
    {
        $admin = User::factory()->create(['email' => 'boss@example.com', 'is_super_admin' => true]);

        $this->get('/auth/facebook');
        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-boss', 'boss@example.com'));
        $this->get('/auth/facebook/callback?code=abc&state=x')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'มีบัญชีที่ใช้อีเมลนี้อยู่แล้ว กรุณาเข้าสู่ระบบด้วยอีเมลและรหัสผ่าน');

        $this->assertNull($admin->fresh()->facebook_user_id);
        $this->assertGuest();
    }

    public function test_verified_normal_account_is_linked_by_facebook_email(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);

        $this->get('/auth/facebook');
        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-owner', 'owner@example.com'));
        $this->get('/auth/facebook/callback?code=abc&state=x')->assertRedirect(route('user.wallet.index'));

        $this->assertSame('fb-owner', $owner->fresh()->facebook_user_id);
        $this->assertAuthenticatedAs($owner);
    }

    public function test_account_with_two_factor_login_cannot_skip_it_with_facebook(): void
    {
        User::factory()->create(['facebook_user_id' => 'fb-2fa']);
        $this->mock(\App\Services\TwoFactorService::class, fn ($mock) => $mock->shouldReceive('isRequired')->with('login', \Mockery::any())->andReturn(true));
        [$init] = $this->startAppFacebookLogin();

        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-2fa', null));
        $this->get('/auth/facebook/callback?code=abc&state=x')
            ->assertRedirect(route('mobile-login.show', ['token' => $init['login_token'], 'state' => $init['state']]))
            ->assertSessionHas('error', 'บัญชีนี้เปิดยืนยันตัวตน 2 ขั้นตอน กรุณาเข้าสู่ระบบด้วยรหัสผ่าน');

        $this->assertNull(MobileAuthToken::first()->auth_code);
        $this->assertGuest();
    }

    public function test_suspended_facebook_user_is_refused_in_the_app(): void
    {
        $user = User::factory()->create(['facebook_user_id' => 'fb-asid-6']);
        $user->forceFill(['blocked_at' => now()])->save();
        [$init] = $this->startAppFacebookLogin();

        $this->fakeProviderUser('facebook', $this->socialiteUser('fb-asid-6', null));
        $this->get('/auth/facebook/callback?code=abc&state=x')
            ->assertRedirect(route('mobile-login.show', ['token' => $init['login_token'], 'state' => $init['state']]))
            ->assertSessionHas('error', \App\Http\Middleware\EnsureAccountActive::SUSPENDED_MESSAGE);

        $this->assertNull(MobileAuthToken::first()->auth_code);
    }
}
