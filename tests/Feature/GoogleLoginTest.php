<?php

namespace Tests\Feature;

use App\Models\GoogleOAuthSetting;
use App\Models\MobileAuthToken;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\DrivesMobileAppLogin;
use Tests\TestCase;

/**
 * 🔑 (2026-09-27) เข้าสู่ระบบด้วย Google — เว็บ + แอป
 *
 * owner: "แอปต้องล็อกอินด้วย Facebook และ Google ได้ ใช้ค่าเดียวกับเว็บ"
 *   เว็บยังไม่มี Google login เลย → สร้างทั้งชุด ตั้งค่าจากหลังบ้าน (client id/secret)
 *   ยังไม่ตั้งค่า = ปุ่มซ่อนทุกที่ และ /auth/google พากลับหน้าเข้าสู่ระบบพร้อมข้อความไทย
 *
 * กติกาความปลอดภัยที่ต้องไม่หลุด:
 *   - ผูกบัญชีเดิมด้วยอีเมลได้ "เฉพาะอีเมลที่ Google ยืนยันแล้ว" (กันยึดบัญชีคนอื่น)
 *   - state ของ OAuth ต้องตรง session · ผู้ใช้กดยกเลิก = ข้อความไทย ไม่ใช่ exception
 *   - code ของแอปออกให้ token ที่แอปขอไว้เท่านั้น และแลกได้ครั้งเดียว
 *
 * @group security
 */
class GoogleLoginTest extends TestCase
{
    use DrivesMobileAppLogin;
    use RefreshDatabase;

    private const CLIENT_ID = '123456789012-testclient.apps.googleusercontent.com';

    private const CLIENT_SECRET = 'GOCSPX-test-secret-value';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // อะไรที่ยิงออกนอก (observer ฯลฯ) ห้ามหลุดไปของจริง
        Http::fake(['*' => Http::response([], 200)]);
    }

    private function configureGoogle(bool $active = true): GoogleOAuthSetting
    {
        $setting = GoogleOAuthSetting::create([
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'is_active' => $active,
        ]);
        GoogleOAuthSetting::clearCache();

        return $setting;
    }

    /**
     * โปรไฟล์ Google (email_verified เป็นค่าที่ Google ส่งมาเอง)
     */
    private function googleUser(string $sub, ?string $email, bool $verified = true)
    {
        return $this->socialiteUser($sub, $email, 'สมหญิง กูเกิล', ['email_verified' => $verified]);
    }

    // ============================================================
    // ยังไม่ตั้งค่า
    // ============================================================

    public function test_not_configured_sends_back_to_login_with_thai_error_and_hides_buttons(): void
    {
        $this->assertFalse(GoogleOAuthSetting::isConfigured());
        $this->get('/login')->assertOk()->assertDontSee('เข้าสู่ระบบด้วย Google');
        $this->get('/register')->assertOk()->assertDontSee('สมัครด้วย Google');

        $this->get('/auth/google')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Google'));
    }

    public function test_inactive_setting_counts_as_not_configured(): void
    {
        $this->configureGoogle(active: false);

        $this->assertFalse(GoogleOAuthSetting::isConfigured());
        $this->get('/auth/google')->assertRedirect(route('login'));
    }

    // ============================================================
    // หลังบ้าน
    // ============================================================

    public function test_admin_page_shows_the_redirect_uri_to_register_and_never_the_secret(): void
    {
        $this->configureGoogle();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.auth.google-oauth.index'))
            ->assertOk()
            ->assertSee(route('google.callback'))
            ->assertSee(self::CLIENT_ID)
            ->assertSee('บันทึกแล้ว (ปล่อยว่างเพื่อคงค่าเดิม)')
            ->assertDontSee(self::CLIENT_SECRET);
    }

    public function test_admin_saves_credentials_and_blank_secret_keeps_the_old_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // เปิดใช้โดยยังไม่มี secret → ไม่ยอม (ปุ่มจะพัง)
        $this->actingAs($admin)
            ->put(route('admin.auth.google-oauth.update'), ['client_id' => self::CLIENT_ID, 'is_active' => '1'])
            ->assertSessionHasErrors('is_active');
        $this->assertFalse(GoogleOAuthSetting::isConfigured());

        $this->actingAs($admin)
            ->put(route('admin.auth.google-oauth.update'), [
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'is_active' => '1',
            ])->assertRedirect(route('admin.auth.google-oauth.index'));
        $this->assertTrue(GoogleOAuthSetting::isConfigured());

        // แก้อย่างอื่นโดยเว้นช่อง secret → secret เดิมยังอยู่
        $this->actingAs($admin)
            ->put(route('admin.auth.google-oauth.update'), ['client_id' => self::CLIENT_ID, 'client_secret' => '', 'is_active' => '1'])
            ->assertRedirect(route('admin.auth.google-oauth.index'));

        $this->assertSame(self::CLIENT_SECRET, GoogleOAuthSetting::first()->client_secret);
        $this->assertTrue(GoogleOAuthSetting::isConfigured());
    }

    public function test_non_admin_cannot_open_google_settings(): void
    {
        $this->configureGoogle();

        // middleware role:admin เด้งออก (ไม่เห็นหน้าตั้งค่า)
        $this->actingAs(User::factory()->create())
            ->get(route('admin.auth.google-oauth.index'))
            ->assertRedirect()
            ->assertDontSee(self::CLIENT_ID);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.auth.google-oauth.update'), ['client_id' => 'hijack.apps.googleusercontent.com', 'is_active' => '0'])
            ->assertRedirect();
        $this->assertSame(self::CLIENT_ID, GoogleOAuthSetting::first()->client_id);
    }

    // ============================================================
    // ตั้งค่าแล้ว — เว็บ
    // ============================================================

    public function test_configured_shows_button_and_redirects_to_google_with_the_registered_callback(): void
    {
        $this->configureGoogle();

        $this->get('/login')->assertOk()->assertSee('เข้าสู่ระบบด้วย Google');
        $this->get('/register?ref=M123')->assertOk()->assertSee('สมัครด้วย Google')->assertSee('auth/google?ref=M123', false);

        $location = $this->get('/auth/google')->assertRedirect()->headers->get('Location');
        $query = $this->queryOf($location);

        $this->assertStringStartsWith('https://accounts.google.com/', $location);
        $this->assertSame(self::CLIENT_ID, $query['client_id']);
        $this->assertSame(route('google.callback'), $query['redirect_uri']);
        $this->assertNotEmpty($query['state']);
        // Translate API key เดิมใน services.google ต้องไม่ถูกแตะ
        $this->assertArrayHasKey('api_key', config('services.google'));
    }

    public function test_client_secret_is_encrypted_at_rest(): void
    {
        $this->configureGoogle();

        $raw = DB::table('google_oauth_settings')->value('client_secret');

        $this->assertNotSame(self::CLIENT_SECRET, $raw);
        $this->assertStringNotContainsString(self::CLIENT_SECRET, (string) $raw);
        $this->assertSame(self::CLIENT_SECRET, GoogleOAuthSetting::first()->client_secret);
        $this->assertArrayNotHasKey('client_secret', GoogleOAuthSetting::first()->toArray());
    }

    public function test_new_google_user_gets_an_account_and_wallet(): void
    {
        $this->configureGoogle();
        $this->get('/auth/google');
        $this->fakeProviderUser('google', $this->googleUser('g-100', 'new.person@gmail.com'));

        $this->get('/auth/google/callback?code=abc&state=x')
            ->assertRedirect(route('user.home'));

        $user = User::where('google_id', 'g-100')->firstOrFail();
        $this->assertSame('new.person@gmail.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('https://example.test/avatar/g-100.jpg', $user->google_avatar);
        $this->assertTrue(Wallet::where('user_id', $user->id)->exists());
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, GoogleOAuthSetting::first()->total_logins);
    }

    public function test_returning_google_user_logs_into_the_same_account(): void
    {
        $this->configureGoogle();
        $existing = User::factory()->create(['google_id' => 'g-200', 'email' => 'old@example.com']);

        $this->get('/auth/google');
        $this->fakeProviderUser('google', $this->googleUser('g-200', 'changed@gmail.com'));

        $this->get('/auth/google/callback?code=abc&state=x')->assertRedirect(route('user.home'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::where('google_id', 'g-200')->count());
        // อีเมลบัญชีเดิมไม่ถูกเปลี่ยนตาม Google
        $this->assertSame('old@example.com', $existing->fresh()->email);
    }

    public function test_verified_email_links_google_to_the_existing_account(): void
    {
        $this->configureGoogle();
        $existing = User::factory()->create(['email' => 'owner@example.com']);

        $this->get('/auth/google');
        $this->fakeProviderUser('google', $this->googleUser('g-300', 'Owner@Example.com', verified: true));

        $this->get('/auth/google/callback?code=abc&state=x')->assertRedirect(route('user.home'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('g-300', $existing->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'owner@example.com')->count());
    }

    public function test_unverified_email_never_links_to_the_existing_account(): void
    {
        $this->configureGoogle();
        $victim = User::factory()->create(['email' => 'victim@example.com']);

        $this->get('/auth/google');
        $this->fakeProviderUser('google', $this->googleUser('g-attacker', 'victim@example.com', verified: false));

        $this->get('/auth/google/callback?code=abc&state=x')->assertRedirect(route('user.home'));

        // บัญชีเหยื่อไม่ถูกผูก และคนที่ล็อกอินไม่ใช่เหยื่อ
        $this->assertNull($victim->fresh()->google_id);
        $this->assertNotSame($victim->id, auth()->id());

        // ได้บัญชีแยกของตัวเอง อีเมลสังเคราะห์ (ไม่เอาอีเมลที่ยังไม่ยืนยันมาเป็นของตัว)
        $created = User::where('google_id', 'g-attacker')->firstOrFail();
        $this->assertSame('googleoauth_g-attacker@thaiprompt.local', $created->email);
        $this->assertNull($created->email_verified_at);
        $this->assertAuthenticatedAs($created);
    }

    public function test_email_already_linked_to_another_google_account_is_refused(): void
    {
        $this->configureGoogle();
        User::factory()->create(['email' => 'taken@example.com', 'google_id' => 'g-original']);

        $this->get('/auth/google');
        $this->fakeProviderUser('google', $this->googleUser('g-other', 'taken@example.com'));

        $this->get('/auth/google/callback?code=abc&state=x')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ผูกกับบัญชี Google อื่น'));

        $this->assertGuest();
        $this->assertSame(0, User::where('google_id', 'g-other')->count());
    }

    public function test_user_cancel_at_google_returns_thai_message(): void
    {
        $this->configureGoogle();
        $this->get('/auth/google');

        $this->get('/auth/google/callback?error=access_denied&state=x')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'คุณยกเลิกการเข้าสู่ระบบด้วย Google');

        $this->assertGuest();
    }

    public function test_state_mismatch_is_rejected_without_logging_in(): void
    {
        // Socialite ตัวจริง (ไม่ mock) — state ใน query ไม่ตรงกับ session ต้องโดนตีกลับก่อนคุยกับ Google
        $this->configureGoogle();
        $this->get('/auth/google');
        // เทสต์ใช้แอปเดียวข้ามคำขอ — ทิ้ง driver ของคำขอก่อน ให้ callback สร้างใหม่จากคำขอของตัวเอง (เหมือน prod)
        app(\Laravel\Socialite\Contracts\Factory::class)->forgetDrivers();

        $this->get('/auth/google/callback?code=abc&state=forged-state')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'การยืนยันตัวตน'));

        $this->assertGuest();
        $this->assertSame(0, User::whereNotNull('google_id')->count());
    }

    public function test_suspended_account_cannot_sign_in_with_google(): void
    {
        $this->configureGoogle();
        $user = User::factory()->create(['google_id' => 'g-400']);
        $user->forceFill(['blocked_at' => now()])->save();

        $this->get('/auth/google');
        $this->fakeProviderUser('google', $this->googleUser('g-400', 'blocked@gmail.com'));

        $this->get('/auth/google/callback?code=abc&state=x')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', \App\Http\Middleware\EnsureAccountActive::SUSPENDED_MESSAGE);

        $this->assertGuest();
    }

    public function test_redirect_to_another_site_after_login_is_ignored(): void
    {
        $this->configureGoogle();
        $this->fakeProviderUser('google', $this->googleUser('g-500', 'safe@gmail.com'));

        $this->get('/auth/google?redirect='.urlencode('https://evil.example/phish'));
        $this->get('/auth/google/callback?code=abc&state=x')->assertRedirect(route('user.home'));
    }

    public function test_redirect_to_a_page_on_our_site_after_login_is_kept(): void
    {
        $this->configureGoogle();
        $this->fakeProviderUser('google', $this->googleUser('g-501', 'safe2@gmail.com'));

        $this->get('/auth/google?redirect=/user/wallet');
        $this->get('/auth/google/callback?code=abc&state=x')->assertRedirect('/user/wallet');
    }

    // ============================================================
    // แอป (PKCE ผ่านเว็บ)
    // ============================================================

    public function test_app_google_button_goes_straight_to_google_and_ends_with_a_code_the_app_can_exchange(): void
    {
        $this->configureGoogle();
        $init = $this->initMobileLogin('google');
        $this->assertSame('google', $this->queryOf($init['login_url'])['provider']);

        // /mobile-login?provider=google → /auth/google?mobile_token=..&state=..
        $jump = $this->get($this->relative($init['login_url']))->assertRedirect()->headers->get('Location');
        $this->assertSame(route('google.login', ['mobile_token' => $init['login_token'], 'state' => $init['state']]), $jump);

        // → หน้าอนุญาตของ Google (redirect_uri = callback ตัวเดียวกับเว็บ)
        $google = $this->get($this->relative($jump))->assertRedirect()->headers->get('Location');
        $this->assertSame(route('google.callback'), $this->queryOf($google)['redirect_uri']);

        // Google ส่งกลับ callback ของเว็บ → หน้าพากลับแอปพร้อม code
        $this->fakeProviderUser('google', $this->googleUser('g-app', 'app.user@gmail.com'));
        $deepLink = $this->deepLinkFrom($this->get('/auth/google/callback?code=abc&state=x'));
        $this->assertSame($init['state'], $deepLink['state']);

        // แอปแลก code + verifier → ได้ token ของบัญชี Google นี้
        $this->exchangeCode($deepLink['code'], $deepLink['state'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'app.user@gmail.com');

        // แลกซ้ำไม่ได้ (deep link + auth session ส่ง code เดียวกันมาสองทาง)
        $this->exchangeCode($deepLink['code'], $deepLink['state'])->assertStatus(401);
    }

    public function test_app_code_is_useless_without_the_apps_verifier(): void
    {
        $this->configureGoogle();
        $init = $this->initMobileLogin('google');
        $this->get('/auth/google?'.http_build_query(['mobile_token' => $init['login_token'], 'state' => $init['state']]));

        $this->fakeProviderUser('google', $this->googleUser('g-app2', 'app2@gmail.com'));
        $deepLink = $this->deepLinkFrom($this->get('/auth/google/callback?code=abc&state=x'));

        $this->exchangeCode($deepLink['code'], $deepLink['state'], str_repeat('x', 50))->assertStatus(401);
        $this->assertNull(MobileAuthToken::first()->used_at);
    }

    public function test_app_user_cancel_at_google_goes_back_to_the_app_login_page_with_thai_message(): void
    {
        $this->configureGoogle();
        $init = $this->initMobileLogin('google');
        $this->get('/auth/google?'.http_build_query(['mobile_token' => $init['login_token'], 'state' => $init['state']]));

        $this->get('/auth/google/callback?error=access_denied&state=x')
            ->assertRedirect(route('mobile-login.show', ['token' => $init['login_token'], 'state' => $init['state']]))
            ->assertSessionHas('error', 'คุณยกเลิกการเข้าสู่ระบบด้วย Google');

        $this->assertNull(MobileAuthToken::first()->auth_code);
    }

    public function test_app_link_with_unknown_token_never_reaches_google(): void
    {
        $this->configureGoogle();

        $this->get('/auth/google?mobile_token=forged&state=forged')
            ->assertOk()
            ->assertViewIs('auth.mobile-login-error');

        $this->assertNull(session('google_mobile_token'));
    }

    public function test_web_login_after_an_abandoned_app_attempt_is_not_hijacked(): void
    {
        $this->configureGoogle();
        $init = $this->initMobileLogin('google');

        // เปิดจากแอปแล้วทิ้งไว้
        $this->get('/auth/google?'.http_build_query(['mobile_token' => $init['login_token'], 'state' => $init['state']]));
        // วันหลังล็อกอินเว็บปกติในเบราว์เซอร์เดียวกัน
        $this->get('/auth/google');

        $this->fakeProviderUser('google', $this->googleUser('g-web', 'web@gmail.com'));
        $this->get('/auth/google/callback?code=abc&state=x')->assertRedirect(route('user.home'));

        $this->assertNull(MobileAuthToken::first()->auth_code, 'ล็อกอินเว็บต้องไม่ออก code ให้แอปที่ทิ้งไว้');
    }
}
