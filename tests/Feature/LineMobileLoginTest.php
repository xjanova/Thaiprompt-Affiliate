<?php

namespace Tests\Feature;

use App\Models\LineOaSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 📱 (2026-09-27) LINE Login ในแอปต้องใช้ callback เดียวกับเว็บ
 *
 * owner: "แอพเรายัง login ด้วย Line ... ไม่ได้ ให้ใช้ค่าเดียวกับ เว็บได้เลย"
 *   prod: แอปส่ง redirect_uri = /auth/line/mobile-callback ซึ่งไม่ได้ลงทะเบียนในช่อง LINE Login
 *   → LINE ปฏิเสธตั้งแต่หน้าแรก (log มีแค่ "URL generated" ไม่เคยมี callback กลับมา)
 *
 * เส้นที่ต้องผ่าน: แอปขอ URL → LINE ส่งกลับ /auth/line/callback (ของเว็บ) → เว็บส่ง code เข้าแอป
 * → แอปแลก code ด้วย redirect_uri ตัวเดียวกัน · LINE API ถูกแทนด้วย Http::fake ที่เหลือเป็นของจริง
 */
class LineMobileLoginTest extends TestCase
{
    use RefreshDatabase;

    /** callback ที่ลงทะเบียนไว้ในช่อง LINE Login (ค่าเดียวกับที่เว็บใช้) */
    private const WEB_CALLBACK = 'https://main.thaiprompt.online/auth/line/callback';

    /** channel id สมมติ */
    private const CHANNEL_ID = '1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        LineOaSetting::create([
            'login_channel_id' => self::CHANNEL_ID,
            'channel_secret' => 'test-secret',
            'redirect_uri' => self::WEB_CALLBACK,
            'is_active' => true,
        ]);
        Cache::forget('line_oa_settings');

        Http::fake([
            'api.line.me/oauth2/v2.1/token' => Http::response([
                'access_token' => 'line-access-token',
                'expires_in' => 2592000,
            ]),
            'api.line.me/v2/profile' => Http::response([
                'userId' => 'U0000000000000000000000000000test',
                'displayName' => 'ทดสอบ ไลน์',
                'pictureUrl' => 'https://profile.line-scdn.net/test',
            ]),
            // อย่างอื่นที่ยิงออกนอก (observer ฯลฯ) ห้ามหลุดไปของจริง
            '*' => Http::response([], 200),
        ]);
    }

    /** จำนวนครั้งที่ยิงแลก code กับ LINE */
    private function tokenExchanges(): int
    {
        return Http::recorded(fn (HttpRequest $request) => str_contains($request->url(), 'api.line.me/oauth2/v2.1/token'))->count();
    }

    /**
     * ขอ URL จาก API แบบที่แอปทำ แล้วคืน [authUrl, state, query ของ authUrl]
     *
     * @return array{0: string, 1: string, 2: array<string, string>}
     */
    private function requestAuthUrl(): array
    {
        $response = $this->getJson('/api/v1/auth/line/mobile-url')->assertOk();
        $authUrl = $response->json('data.authUrl');
        $state = $response->json('data.state');
        parse_str((string) parse_url($authUrl, PHP_URL_QUERY), $query);

        return [$authUrl, $state, $query];
    }

    public function test_app_auth_url_uses_the_web_callback_registered_with_line(): void
    {
        [, $state, $query] = $this->requestAuthUrl();

        $this->assertSame(self::WEB_CALLBACK, $query['redirect_uri']);
        $this->assertSame(self::CHANNEL_ID, $query['client_id']);
        $this->assertSame($state, $query['state']);
    }

    public function test_web_callback_hands_the_app_code_back_to_the_app(): void
    {
        [, $state] = $this->requestAuthUrl();

        $this->get('/auth/line/callback?'.http_build_query(['code' => 'code-1', 'state' => $state]))
            ->assertOk()
            ->assertViewHas('deepLink', 'thaiprompt://login?'.http_build_query(['code' => 'code-1', 'state' => $state]));

        // เว็บไม่ได้แลก code เอง — ปล่อยให้แอปแลก (code ใช้ได้ครั้งเดียว)
        $this->assertSame(0, $this->tokenExchanges());
    }

    public function test_user_cancel_on_line_returns_to_app_with_error(): void
    {
        [, $state] = $this->requestAuthUrl();

        $response = $this->get('/auth/line/callback?'.http_build_query([
            'error' => 'access_denied',
            'state' => $state,
        ]))->assertOk();

        $this->assertStringStartsWith('thaiprompt://login?error=access_denied', $response->viewData('deepLink'));
    }

    public function test_web_login_callback_is_untouched_for_states_the_app_did_not_get(): void
    {
        // state ที่ไม่ได้ออกให้แอป → เส้นเว็บเดิม (เช็ค state กับ session → ไม่ตรง → กลับหน้า login)
        $this->get('/auth/line/callback?'.http_build_query(['code' => 'code-1', 'state' => 'web-state']))
            ->assertRedirect(route('login'));

        $this->assertSame(0, $this->tokenExchanges());
    }

    public function test_app_exchanges_code_with_the_same_redirect_uri_and_signs_in(): void
    {
        [, $state] = $this->requestAuthUrl();

        $this->postJson('/api/v1/auth/line/mobile-callback', ['code' => 'code-1', 'state' => $state])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.isNewUser', true);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), '/oauth2/v2.1/token')
            && $request['redirect_uri'] === self::WEB_CALLBACK
            && $request['client_id'] === self::CHANNEL_ID
            && $request['code'] === 'code-1');

        $this->assertSame(1, User::where('line_user_id', 'U0000000000000000000000000000test')->count());
    }

    public function test_state_is_single_use_so_a_code_is_never_exchanged_twice(): void
    {
        [, $state] = $this->requestAuthUrl();

        $this->postJson('/api/v1/auth/line/mobile-callback', ['code' => 'code-1', 'state' => $state])
            ->assertCreated();

        // deep link + auth session ยิงเข้ามาพร้อมกัน → ครั้งที่สองต้องไม่ไปถึง LINE
        $this->postJson('/api/v1/auth/line/mobile-callback', ['code' => 'code-1', 'state' => $state])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertSame(1, $this->tokenExchanges());
    }

    public function test_state_not_issued_by_us_is_rejected_before_calling_line(): void
    {
        $this->postJson('/api/v1/auth/line/mobile-callback', ['code' => 'code-1', 'state' => 'forged-state'])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertSame(0, $this->tokenExchanges());
        $this->assertSame(0, User::where('line_user_id', 'U0000000000000000000000000000test')->count());
    }

    public function test_existing_web_line_user_signs_in_to_the_same_account(): void
    {
        // บัญชีที่เคยล็อกอิน LINE บนเว็บ → ช่อง LINE เดียวกัน = userId เดียวกัน = บัญชีเดิม
        $user = User::factory()->create(['line_user_id' => 'U0000000000000000000000000000test']);
        [, $state] = $this->requestAuthUrl();

        $this->postJson('/api/v1/auth/line/mobile-callback', ['code' => 'code-1', 'state' => $state])
            ->assertOk()
            ->assertJsonPath('data.isNewUser', false)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertSame(1, User::where('line_user_id', 'U0000000000000000000000000000test')->count());
    }
}
