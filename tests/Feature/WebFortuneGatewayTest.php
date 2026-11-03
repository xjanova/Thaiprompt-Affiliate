<?php

namespace Tests\Feature;

use App\Http\Controllers\WebFortuneGatewayController;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneWebHandoff;
use App\Models\User;
use App\Services\FortuneWebLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 🔐 (2026-11-03) T13 — WebFortuneGatewayController (FB web-only)
 *
 * ครอบคลุม: GET ไม่มีผลข้างเคียง · POST login แบบไม่ remember + ส่งต่อ SSO พร้อม to
 * · jti ใช้ครั้งเดียว (ยกเว้น session เดิม) · กันแอดมิน/ทีมงาน/บัญชีระงับ
 * · token หมดอายุ → หน้า expired + resend ไป PSID เดิมเท่านั้น (3 ครั้ง/ชม.)
 * · ธง facebook_web_only เปิด FB แต่ LINE ยังปิด · throttle นับต่อ token
 *
 * @group security
 */
class WebFortuneGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const PSID = '24680135792468013';

    private const OTHER_PSID = '13579246801357924';

    private const LINE_ID = 'U0123456789abcdef0123456789abcdef';

    private const SSO = 'https://xn--82c4af5bzdj.online/auth/thaiprompt/redirect';

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing ของเครื่อง dev มี APP_KEY ที่ความยาวไม่ถูก (encrypter ของ session/cookie บูตไม่ได้)
        // → ตั้ง key ชั่วคราวที่ถูกต้องเฉพาะเทสต์นี้
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        $this->app->forgetInstance('encrypter');

        $this->withoutVite();
        Cache::flush();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['recipient_id' => self::PSID, 'message_id' => 'm_1'], 200),
            '*' => Http::response([], 200),
        ]);

        $this->settings(['enable_web_fortune_button' => true, 'facebook_web_only' => false]);
    }

    /**
     * ตั้งค่าแถวกลาง (ล้าง static memo ก่อน/หลัง)
     */
    private function settings(array $override): void
    {
        FortuneTellingSetting::clearSettingsCache();
        $settings = FortuneTellingSetting::getSettings();
        $settings->forceFill(array_merge(['web_fortune_sso_url' => self::SSO], $override))->save();
        FortuneTellingSetting::clearSettingsCache();
    }

    private function link(): FortuneWebLinkService
    {
        FortuneTellingSetting::clearSettingsCache();

        return new FortuneWebLinkService;
    }

    private function token(string $platform = 'facebook', string $uid = self::PSID, ?string $to = null, ?string $h = null, ?int $pg = null): string
    {
        return $this->link()->generateToken($platform, $uid, $to, $h, $pg);
    }

    /**
     * token ที่เซ็นถูกแต่หมดอายุแล้ว (ปลอมเวลาให้ย้อนไป 2 วันตอนออก)
     */
    private function expiredToken(string $uid = self::PSID, ?string $h = null): string
    {
        $this->travel(-2)->days();
        $token = $this->token('facebook', $uid, $h ? '/chat/continue/'.$h : null, $h);
        $this->travelBack();

        return $token;
    }

    private function jti(string $token): string
    {
        return $this->link()->verifyToken($token, true)['j'];
    }

    /** แถว jti ที่ใช้แล้วใน DB (null = ยังไม่ถูกใช้) */
    private function jtiUse(string $token): ?object
    {
        return \DB::table('fortune_web_link_uses')->where('jti', $this->jti($token))->first();
    }

    /** จำลองการเปิดจากเบราว์เซอร์อีกเครื่อง (ไม่มี session/auth ติดมา) */
    private function freshBrowser(): void
    {
        Auth::logout();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    private function makeUser(array $attrs = []): User
    {
        $psid = $attrs['facebook_psid'] ?? null;
        unset($attrs['facebook_psid']);
        $user = User::factory()->create($attrs);
        if ($psid !== null) {
            $user->forceFill(['facebook_psid' => $psid])->save();
        }

        return $user->fresh();
    }

    // ============================================================
    // GET ไม่มีผลข้างเคียง
    // ============================================================

    public function test_get_renders_interstitial_without_creating_or_logging_in(): void
    {
        $token = $this->token();
        $before = User::count();

        $response = $this->withHeaders(['User-Agent' => 'facebookexternalhit/1.1'])
            ->get('/web-fortune/go?tk='.$token);

        $response->assertOk()
            ->assertViewIs('web-fortune.interstitial')
            ->assertSee(route('web-fortune.consume'), false)
            ->assertSee('กำลังพาไปหาแม่หมอ');
        $this->assertSame($before, User::count(), 'GET ต้องไม่สร้าง user');
        $this->assertGuest();
        $this->assertNull($this->jtiUse($token), 'GET ต้องไม่กิน token');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    // ============================================================
    // POST happy path
    // ============================================================

    public function test_post_logs_in_without_remember_and_redirects_to_sso_with_handoff(): void
    {
        $handoff = FortuneWebHandoff::issue([
            'platform_user_id' => self::PSID,
            'fortune_page_id' => 7,
            'intent_text' => 'อยากถามเรื่องงาน',
            'source' => 'web_only_gate',
        ]);
        $token = $this->token('facebook', self::PSID, null, $handoff->nonce, 7);

        $response = $this->post('/web-fortune/go', ['tk' => $token]);

        $response->assertRedirect(self::SSO.'?to='.urlencode('/chat/continue/'.$handoff->nonce));
        $this->assertAuthenticated();
        $user = Auth::user();

        // remember=false — ไม่มี remember cookie และไม่มี remember_token
        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertStringStartsNotWith('remember_web', $cookie->getName(), 'ห้าม remember login จาก Magic Link');
        }
        $this->assertEmpty($user->fresh()->remember_token);

        $response->assertSessionHas('wf_psid', self::PSID);
        $response->assertSessionHas('wf_page', 7);
        $this->assertSame((int) $user->id, (int) $handoff->fresh()->thaiprompt_user_id);
        $this->assertSame((int) $user->id, (int) $this->jtiUse($token)?->user_id);

        if (Schema::hasColumn('users', 'fortune_origin_page_id')) {
            $this->assertSame(7, (int) $user->fresh()->fortune_origin_page_id);
        }
    }

    public function test_post_keeps_explicit_to_path(): void
    {
        $token = $this->token('facebook', self::PSID, '/mlm');

        $this->post('/web-fortune/go', ['tk' => $token])
            ->assertRedirect(self::SSO.'?to='.urlencode('/mlm'));
        $this->assertAuthenticated();
    }

    public function test_handoff_of_other_psid_is_not_attached(): void
    {
        $handoff = FortuneWebHandoff::issue(['platform_user_id' => self::OTHER_PSID]);
        $token = $this->token('facebook', self::PSID, null, $handoff->nonce);

        $this->post('/web-fortune/go', ['tk' => $token])->assertRedirect();
        $this->assertAuthenticated();
        $this->assertNull($handoff->fresh()->thaiprompt_user_id);
    }

    public function test_two_posts_for_same_psid_resolve_to_one_user(): void
    {
        $before = User::count();

        $this->post('/web-fortune/go', ['tk' => $this->token()])->assertRedirect();
        $first = Auth::id();
        $this->freshBrowser();
        $this->post('/web-fortune/go', ['tk' => $this->token()])->assertRedirect();

        $this->assertSame($first, Auth::id());
        $this->assertSame($before + 1, User::count());
    }

    // ============================================================
    // single-use jti
    // ============================================================

    public function test_reused_token_from_other_browser_does_not_login_but_same_session_passes(): void
    {
        $token = $this->token();

        $this->post('/web-fortune/go', ['tk' => $token])->assertRedirect(self::SSO);
        $owner = Auth::id();
        $this->assertNotNull($owner);

        // รีเฟรชจาก session เดิม → ผ่าน
        $this->post('/web-fortune/go', ['tk' => $token])->assertRedirect(self::SSO);
        $this->assertSame($owner, Auth::id());

        // เบราว์เซอร์อื่น → ไม่ login และไปหน้า expired (used)
        $this->freshBrowser();
        $response = $this->post('/web-fortune/go', ['tk' => $token]);
        $response->assertRedirect(route('web-fortune.expired', ['reason' => 'used', 'tk' => $token]));
        $this->assertGuest();
    }

    // ============================================================
    // ตัวกรองสิทธิ์
    // ============================================================

    /**
     * แอดมิน / super admin / ทีมงาน (role_id) / hotel admin / บัญชีถูกระงับ ต้องไม่ถูก auto-login
     */
    #[DataProvider('deniedAccounts')]
    public function test_privileged_or_suspended_accounts_are_not_logged_in(string $kind): void
    {
        $user = $this->makeUser(['facebook_psid' => self::PSID]);
        match ($kind) {
            'admin' => $user->forceFill(['role' => 'admin'])->save(),
            'super_admin' => $user->forceFill(['is_super_admin' => true])->save(),
            'staff' => $user->forceFill(['role_id' => $this->anyRoleId()])->save(),
            'hotel_admin' => $user->forceFill(['is_hotel_admin' => true])->save(),
            'suspended' => $user->forceFill(['blocked_at' => now()])->save(),
        };

        $token = $this->token();
        $this->post('/web-fortune/go', ['tk' => $token])
            ->assertRedirect(route('web-fortune.expired', ['reason' => 'denied', 'tk' => $token]));
        $this->assertGuest();
    }

    public static function deniedAccounts(): array
    {
        return [
            'admin' => ['admin'],
            'super_admin' => ['super_admin'],
            'staff' => ['staff'],
            'hotel_admin' => ['hotel_admin'],
            'suspended' => ['suspended'],
        ];
    }

    public function test_staff_session_is_not_switched_and_token_is_not_consumed(): void
    {
        $admin = $this->makeUser();
        $admin->forceFill(['role' => 'admin'])->save();
        $token = $this->token();

        $this->actingAs($admin->fresh())
            ->post('/web-fortune/go', ['tk' => $token])
            ->assertRedirect(route('web-fortune.expired', ['reason' => 'staff_session', 'tk' => $token]));

        $this->assertSame((int) $admin->id, (int) Auth::id(), 'session ทีมงานต้องไม่ถูกสลับเป็นลูกค้า');
        $this->assertNull($this->jtiUse($token), 'token ลูกค้ายังต้องใช้ได้');
    }

    // ============================================================
    // expired + resend
    // ============================================================

    public function test_expired_token_goes_to_expired_page_and_resend_targets_same_psid_only(): void
    {
        $old = FortuneWebHandoff::issue(['platform_user_id' => self::PSID, 'intent_text' => 'เรื่องความรัก']);
        $old->consume('jw-1');
        $token = $this->expiredToken(self::PSID, $old->nonce);

        $this->get('/web-fortune/go?tk='.$token)
            ->assertRedirect(route('web-fortune.expired', ['reason' => 'expired', 'tk' => $token]));
        $this->post('/web-fortune/go', ['tk' => $token])
            ->assertRedirect(route('web-fortune.expired', ['reason' => 'expired', 'tk' => $token]));
        $this->assertGuest();

        $this->get(route('web-fortune.expired', ['reason' => 'expired', 'tk' => $token]))
            ->assertOk()
            ->assertSee('ส่งลิงก์ใหม่เข้าแชทให้หน่อย')
            ->assertDontSee('บาท')
            ->assertDontSee('โอน');

        $this->post('/web-fortune/resend', ['tk' => $token, 'reason' => 'expired'])
            ->assertRedirect()
            ->assertSessionHas('wf_resend_status', 'sent');

        $sent = Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/me/messages'));
        $this->assertCount(1, $sent);
        [$request] = $sent[0];
        $this->assertSame(self::PSID, $request['recipient']['id']);

        $button = $request['message']['attachment']['payload']['buttons'][0];
        $this->assertSame('web_url', $button['type']);
        parse_str((string) parse_url($button['url'], PHP_URL_QUERY), $q);
        $fresh = $this->link()->verifyToken($q['tk']);
        $this->assertNotNull($fresh, 'ลิงก์ใหม่ต้องยังไม่หมดอายุ');
        $this->assertSame(self::PSID, $fresh['u']);
        $this->assertNotSame($old->nonce, $fresh['h'], 'handoff เดิมถูกใช้แล้ว → ต้องออกแถวใหม่');
        $this->assertSame('/chat/continue/'.$fresh['h'], $fresh['to']);
        $this->assertSame('เรื่องความรัก', FortuneWebHandoff::where('nonce', $fresh['h'])->value('intent_text'));
        $this->assertStringNotContainsString('บาท', $request['message']['attachment']['payload']['text']);
    }

    public function test_resend_is_limited_to_three_per_hour_per_psid(): void
    {
        $token = $this->expiredToken();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/web-fortune/resend', ['tk' => $token])->assertSessionHas('wf_resend_status', 'sent');
        }
        $this->post('/web-fortune/resend', ['tk' => $this->expiredToken()])
            ->assertSessionHas('wf_resend_status', 'limited');

        $this->assertCount(3, Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/me/messages')));
    }

    public function test_resend_rejects_tampered_line_and_too_old_tokens(): void
    {
        $token = $this->expiredToken();
        [$body, $sig] = explode('.', $token);
        $this->post('/web-fortune/resend', ['tk' => $body.'.'.str_repeat('0', strlen($sig))])
            ->assertSessionHas('wf_resend_status', 'unavailable');

        // LINE — ห้าม push จากหน้าเว็บ
        $this->post('/web-fortune/resend', ['tk' => $this->token('line', self::LINE_ID)])
            ->assertSessionHas('wf_resend_status', 'unavailable');

        // หมดอายุเกิน 14 วัน
        $this->travel(-20)->days();
        $ancient = $this->token();
        $this->travelBack();
        $this->post('/web-fortune/resend', ['tk' => $ancient])
            ->assertSessionHas('wf_resend_status', 'unavailable');

        $this->assertCount(0, Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/me/messages')));
    }

    public function test_invalid_token_shows_expired_page_without_resend(): void
    {
        $this->get('/web-fortune/go?tk=garbage.token')
            ->assertRedirect(route('web-fortune.expired', ['reason' => 'invalid', 'tk' => 'garbage.token']));

        $this->get(route('web-fortune.expired', ['reason' => 'invalid', 'tk' => 'garbage.token']))
            ->assertOk()
            ->assertDontSee('ส่งลิงก์ใหม่เข้าแชทให้หน่อย')
            ->assertSee('เข้าด้วย Facebook');
    }

    // ============================================================
    // ธง FB web-only — FB เปิด LINE ปิด
    // ============================================================

    public function test_web_only_flag_enables_facebook_but_not_line(): void
    {
        $this->settings(['enable_web_fortune_button' => false, 'facebook_web_only' => true]);

        $this->post('/web-fortune/go', ['tk' => $this->token('facebook')])->assertRedirect(self::SSO);
        $this->assertAuthenticated();

        $this->freshBrowser();
        $before = User::count();
        $this->post('/web-fortune/go', ['tk' => $this->token('line', self::LINE_ID)])->assertRedirect(self::SSO);
        $this->assertGuest();
        $this->assertSame($before, User::count(), 'LINE ปิดอยู่ต้องไม่สร้าง user');

        // GET ของ LINE ก็ไม่แสดง interstitial
        $this->get('/web-fortune/go?tk='.$this->token('line', self::LINE_ID))->assertRedirect(self::SSO);
    }

    public function test_both_flags_off_keeps_old_behaviour(): void
    {
        $this->settings(['enable_web_fortune_button' => false, 'facebook_web_only' => false]);

        $this->post('/web-fortune/go', ['tk' => $this->token('facebook')])->assertRedirect(self::SSO);
        $this->assertGuest();
    }

    // ============================================================
    // throttle ต่อ token
    // ============================================================

    public function test_throttle_counts_per_token(): void
    {
        $a = $this->token();
        $b = $this->token();

        for ($i = 0; $i < WebFortuneGatewayController::GO_MAX_PER_TOKEN_PER_MINUTE; $i++) {
            $this->get('/web-fortune/go?tk='.$a)->assertOk();
        }
        $this->get('/web-fortune/go?tk='.$a)->assertStatus(429);
        $this->post('/web-fortune/go', ['tk' => $a])->assertStatus(429);
        $this->assertGuest();

        // token อื่นจาก IP เดียวกัน (CGNAT) ยังใช้ได้
        $this->get('/web-fortune/go?tk='.$b)->assertOk();

        RateLimiter::clear('wfgo:'.sha1($a));
    }

    // ============================================================
    // 🔐 รีวิว — jti ใน DB / login CSRF / 2FA / seller / LINE parity
    // ============================================================

    public function test_used_token_stays_used_after_cache_is_flushed_by_deploy(): void
    {
        $token = $this->token();
        $this->post('/web-fortune/go', ['tk' => $token])->assertRedirect(self::SSO);

        // deploy.sh → cache:clear (Redis flushdb)
        Cache::flush();
        $this->freshBrowser();

        $this->post('/web-fortune/go', ['tk' => $token])
            ->assertRedirect(route('web-fortune.expired', ['reason' => 'used', 'tk' => $token]));
        $this->assertGuest();
    }

    public function test_cross_site_get_does_not_auto_submit_but_chat_app_open_does(): void
    {
        $token = $this->token();

        $this->withHeaders(['Sec-Fetch-Site' => 'cross-site', 'Referer' => 'https://evil.example/x'])
            ->get('/web-fortune/go?tk='.$token)
            ->assertOk()
            ->assertSee('data-auto="0"', false);

        $this->withHeaders(['Sec-Fetch-Site' => 'cross-site', 'Referer' => 'https://l.facebook.com/'])
            ->get('/web-fortune/go?tk='.$token)
            ->assertOk()
            ->assertSee('data-auto="1"', false);

        $this->withHeaders(['Sec-Fetch-Site' => 'none'])
            ->get('/web-fortune/go?tk='.$token)
            ->assertOk()
            ->assertSee('data-auto="1"', false);
    }

    public function test_logged_in_other_customer_must_confirm_switch_and_token_is_not_burned(): void
    {
        $victim = $this->makeUser(['facebook_psid' => self::OTHER_PSID]);
        $token = $this->token();

        // GET → หน้ายืนยัน (ไม่ auto-submit) พร้อมชื่อบัญชีปัจจุบัน
        $this->actingAs($victim)
            ->get('/web-fortune/go?tk='.$token)
            ->assertOk()
            ->assertSee('data-auto="0"', false)
            ->assertSee('confirm_switch', false);

        // POST ไม่ยืนยัน → ไม่สลับบัญชี ไม่กิน token
        $this->post('/web-fortune/go', ['tk' => $token])->assertOk()->assertSee('confirm_switch', false);
        $this->assertSame((int) $victim->id, (int) Auth::id());
        $this->assertNull($this->jtiUse($token));

        // กดยืนยันเอง → สลับได้
        $this->post('/web-fortune/go', ['tk' => $token, 'confirm_switch' => '1'])->assertRedirect(self::SSO);
        $this->assertNotSame((int) $victim->id, (int) Auth::id());
    }

    public function test_account_requiring_two_factor_is_not_logged_in_by_magic_link(): void
    {
        $this->makeUser(['facebook_psid' => self::PSID]);
        $this->mock(\App\Services\Auth\SocialLoginGuard::class, function ($m) {
            $m->shouldReceive('assertCanSignIn')
                ->andThrow(new \App\Exceptions\SocialLoginRefusedException('ต้องใช้ 2FA', 'two_factor'));
        });

        $token = $this->token();
        $this->post('/web-fortune/go', ['tk' => $token])
            ->assertRedirect(route('web-fortune.expired', ['reason' => 'denied', 'tk' => $token]));
        $this->assertGuest();
    }

    public function test_seller_with_customer_role_id_can_still_use_magic_link_on_both_platforms(): void
    {
        $seller = $this->makeUser(['facebook_psid' => self::PSID]);
        $seller->forceFill(['role' => 'seller', 'role_id' => $this->anyRoleId('seller')])->save();

        $this->post('/web-fortune/go', ['tk' => $this->token()])->assertRedirect(self::SSO);
        $this->assertSame((int) $seller->id, (int) Auth::id());

        $this->freshBrowser();
        $lineSeller = $this->makeUser();
        $lineSeller->forceFill(['line_user_id' => self::LINE_ID, 'role' => 'seller', 'role_id' => $this->anyRoleId('seller')])->save();
        $this->post('/web-fortune/go', ['tk' => $this->token('line', self::LINE_ID)])->assertRedirect(self::SSO);
        $this->assertSame((int) $lineSeller->id, (int) Auth::id());
    }

    public function test_line_links_keep_72h_reuse_remember_and_sso_fallback(): void
    {
        $this->assertSame(72, FortuneWebLinkService::ttlHoursFor('line'));
        $this->assertSame(24, FortuneWebLinkService::ttlHoursFor('facebook'));

        $lineUser = $this->makeUser();
        $lineUser->forceFill(['line_user_id' => self::LINE_ID])->save();

        // อายุ 2 วันยังใช้ได้ (FB หมดแล้ว)
        $this->travel(-2)->days();
        $token = $this->token('line', self::LINE_ID, '/mlm');
        $this->travelBack();

        $first = $this->post('/web-fortune/go', ['tk' => $token]);
        $first->assertRedirect(self::SSO.'?to='.urlencode('/mlm'));
        $this->assertSame((int) $lineUser->id, (int) Auth::id());
        $remember = collect($first->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web'));
        $this->assertNotNull($remember, 'LINE ยัง remember เหมือนเดิม');

        // กดซ้ำจากประวัติแชทในเบราว์เซอร์อื่น → ยังเข้าได้ (ไม่ single-use)
        $this->freshBrowser();
        $this->post('/web-fortune/go', ['tk' => $token])->assertRedirect(self::SSO.'?to='.urlencode('/mlm'));
        $this->assertSame((int) $lineUser->id, (int) Auth::id());

        // หมดอายุ (เกิน 72 ชม.) / เสีย → SSO แบบเดิม ไม่ใช่หน้า expired
        $this->freshBrowser();
        $this->travel(-4)->days();
        $old = $this->token('line', self::LINE_ID);
        $this->travelBack();
        $this->get('/web-fortune/go?tk='.$old)->assertRedirect(self::SSO);
        $this->post('/web-fortune/go', ['tk' => $old])->assertRedirect(self::SSO);
        $this->assertGuest();
    }

    private function anyRoleId(string $name = 'staff'): int
    {
        $id = \DB::table('roles')->where('name', $name)->value('id');
        if ($id) {
            return (int) $id;
        }

        return (int) \DB::table('roles')->insertGetId([
            'name' => $name,
            'display_name' => 'บทบาท '.$name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
