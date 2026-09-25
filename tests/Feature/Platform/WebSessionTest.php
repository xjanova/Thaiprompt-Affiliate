<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Support\WebSessionRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * เปิดเว็บแบบล็อกอินจากแอป: POST /api/v1/web-session → /mobile-web-session (PLAY-16 / SHOP-15)
 *
 * ล็อก 4 เรื่อง:
 *   1. ออก token ด้วยปลายทางภายนอก/นอก prefix → 422 (ไม่มี open redirect)
 *   2. URL ที่ได้มีแค่ ?token= — query ของปลายทางเก็บฝั่ง server (ต่อ query จากลิงก์ไม่ได้)
 *   3. GET ไม่เผา token และไม่ล็อกอิน (กัน link preview + login CSRF) · POST ใช้ได้ครั้งเดียว
 *   4. มีบัญชีอื่นล็อกอินอยู่ → ต้องเห็นหน้ายืนยันก่อนสลับ · บัญชีถูกระงับ/ลิงก์หมดอายุ → ไม่ให้เข้า
 *
 * ใช้ DB (MySQL บน CI)
 */
class WebSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_external_or_unlisted_redirect_path_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (['https://evil.example', '//evil.example/user', '/admin/users', 'javascript:alert(1)'] as $path) {
            $this->postJson('/api/v1/web-session', ['redirect_path' => $path])
                ->assertStatus(422)
                ->assertJson(['success' => false, 'code' => 'INVALID_REDIRECT_PATH']);
        }
    }

    public function test_issued_url_carries_only_the_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/web-session', [
            'redirect_path' => '/user/wallet/topup',
            'query_params' => ['amount' => 100, 'next' => ['x' => 'https://evil.example']],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.redirect_path', '/user/wallet/topup')
            ->assertJsonPath('data.expires_in', WebSessionRedirect::TTL_SECONDS);

        parse_str((string) parse_url($response->json('data.url'), PHP_URL_QUERY), $query);
        $this->assertSame(['token'], array_keys($query), 'URL ต้องมีแค่ token — query ปลายทางเก็บฝั่ง server');

        $payload = Cache::get('web_session_token:'.hash('sha256', $query['token']));
        $this->assertSame(['amount' => '100'], $payload['query']);
        $this->assertSame((int) $user->id, (int) $payload['user_id']);
    }

    public function test_get_shows_confirmation_without_consuming_and_post_logs_in_once(): void
    {
        $user = User::factory()->create(['name' => 'สมชาย ทดสอบ']);
        $token = $this->seedToken($user, '/user/wallet/topup', ['amount' => '100']);

        // GET (เช่นตัว preview ลิงก์ของแชท) → แค่หน้ายืนยัน ไม่ล็อกอิน ไม่เผา token
        $this->get('/mobile-web-session?token='.$token.'&redirect=https://evil.example')
            ->assertOk()
            ->assertSee('สมชาย ทดสอบ')
            ->assertSee('mws-form', false);

        $this->assertGuest('web');
        $this->assertNotNull(Cache::get('web_session_token:'.hash('sha256', $token)), 'GET ต้องไม่เผา token');

        // POST (มี CSRF บนหน้าจริง) → ล็อกอินแล้วไปปลายทางที่เก็บไว้ (ไม่สน query ที่แนบมากับลิงก์)
        $this->post('/mobile-web-session', ['token' => $token, 'redirect' => 'https://evil.example'])
            ->assertRedirect('/user/wallet/topup?amount=100');
        $this->assertAuthenticatedAs($user, 'web');

        // ใช้ซ้ำไม่ได้
        auth('web')->logout();
        $this->post('/mobile-web-session', ['token' => $token])->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_confirmation_is_required_when_another_account_is_logged_in(): void
    {
        $target = User::factory()->create(['name' => 'บัญชีเป้าหมาย']);
        $other = User::factory()->create(['name' => 'บัญชีที่ค้างอยู่']);
        $token = $this->seedToken($target, '/user');

        $this->actingAs($other, 'web');

        $this->get('/mobile-web-session?token='.$token)
            ->assertOk()
            ->assertSee('บัญชีเป้าหมาย')
            ->assertSee('บัญชีที่ค้างอยู่')
            ->assertSee('สลับบัญชีและไปต่อ');

        // ยังไม่สลับจนกว่าจะกดยืนยัน
        $this->assertAuthenticatedAs($other, 'web');

        $this->post('/mobile-web-session', ['token' => $token])->assertRedirect('/user');
        $this->assertAuthenticatedAs($target, 'web');
    }

    public function test_same_account_already_logged_in_goes_straight_through(): void
    {
        $user = User::factory()->create();
        $token = $this->seedToken($user, '/seller/dashboard');

        $this->actingAs($user, 'web')
            ->get('/mobile-web-session?token='.$token)
            ->assertRedirect('/seller/dashboard');

        $this->assertNull(Cache::get('web_session_token:'.hash('sha256', $token)), 'ใช้ token แล้วต้องหายจาก cache');
    }

    public function test_dangerous_path_left_in_cache_is_rejected_at_consume_time(): void
    {
        $user = User::factory()->create();
        $token = $this->seedToken($user, 'https://evil.example');

        $this->post('/mobile-web-session', ['token' => $token])->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_suspended_or_expired_tokens_do_not_log_in(): void
    {
        $suspended = User::factory()->create();
        $suspended->suspend(null, 'ทดสอบ');
        $token = $this->seedToken($suspended, '/user');

        $this->post('/mobile-web-session', ['token' => $token])->assertRedirect('/login');
        $this->assertGuest('web');

        $user = User::factory()->create();
        $expired = $this->seedToken($user, '/user', [], now()->subMinute()->getTimestamp());

        $this->post('/mobile-web-session', ['token' => $expired])->assertRedirect('/login');
        $this->assertGuest('web');

        $this->post('/mobile-web-session', ['token' => 'not-a-valid-token'])->assertRedirect('/login');
    }

    /**
     * วาง token ลง cache ในรูปแบบเดียวกับ MobileApiController::generateWebSessionToken
     */
    private function seedToken(User $user, string $path, array $query = [], ?int $expiresAt = null): string
    {
        $token = Str::random(64);

        Cache::put('web_session_token:'.hash('sha256', $token), [
            'user_id' => $user->id,
            'redirect_path' => $path,
            'query' => $query,
            'created_at' => now()->toISOString(),
            'expires_at' => $expiresAt ?? now()->addMinutes(5)->getTimestamp(),
            'ip' => '10.9.9.9',
        ], now()->addMinutes(5));

        return $token;
    }
}
