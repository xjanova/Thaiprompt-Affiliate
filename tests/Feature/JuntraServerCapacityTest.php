<?php

namespace Tests\Feature;

use App\Console\Commands\FortuneFbWebOnlyPreflight;
use App\Models\FortuneTellingSetting;
use App\Support\JuntraServerRateLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🚦 T16 — ความจุของเส้น /api/v1/juntra/server/* หลังย้ายลูกค้า FB ทั้งหมดไปคุยบนเว็บ
 *
 * ทุกคำขอมาจาก IP เดียว (เซิร์ฟเวอร์เว็บจันทรา) เดิมแชททั้งเว็บใช้ถังเดียว 300/นาที
 *   → แชทนับต่อลูกค้า (user_ref) + เพดานรวมที่ตั้งได้ · ลูกค้าคนเดียวกดรัวไม่กินถังของคนอื่น
 *
 * เทสต์ยิงคำขอที่ไม่ผ่าน validation (422) — throttle อยู่ก่อน controller จึงนับเหมือนคำขอจริง
 * โดยไม่ต้องเรียก AI
 */
class JuntraServerCapacityTest extends TestCase
{
    use BuildsJuntraServerSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();

        $client = app(ClientRepository::class)->create(
            null,
            'Juntra Chantra SSO',
            'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback',
            'oauth_users'
        );
        Passport::actingAsClient($client, [], 'api-oauth');

        // controller ของแชท/คำทำนายถูกสร้างตอนรวบรวม middleware — ห้ามแตะ AI จริง (และตารางตั้งค่า AI)
        $ai = \Mockery::mock(\App\Services\FortuneAIService::class);
        $ai->shouldReceive('withCustomerContext')->andReturnSelf();
        $ai->shouldNotReceive('chatWithCustomSystemPrompt');
        $this->app->instance(\App\Services\FortuneAIService::class, $ai);
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    /** คำขอแชทที่ขาด session_id/text → 422 หลังผ่าน throttle */
    private function chat(mixed $ref)
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/chat/send', ['user_ref' => $ref]);
    }

    private function limits(int $perUser, int $total): void
    {
        config([
            'ratelimit.juntra_server.chat_per_user' => $perUser,
            'ratelimit.juntra_server.chat_total' => $total,
        ]);
    }

    public function test_two_customers_have_separate_buckets(): void
    {
        $this->limits(perUser: 2, total: 100);

        $this->chat('A')->assertStatus(422);
        $this->chat('A')->assertStatus(422);

        // ลูกค้า B ไม่โดนผลของ A
        $this->chat('B')->assertStatus(422);
        $this->chat('B')->assertStatus(422);
    }

    public function test_one_customer_over_the_limit_gets_429_with_retry_after(): void
    {
        $this->limits(perUser: 2, total: 100);

        $this->chat('A')->assertStatus(422);
        $this->chat('A')->assertStatus(422);

        $res = $this->chat('A')
            ->assertStatus(429)
            ->assertJsonPath('reason_code', 'rate_limited')
            ->assertJsonPath('scope', 'customer')
            ->assertJsonPath('code', 'TOO_MANY_REQUESTS');

        $this->assertNotNull($res->headers->get('Retry-After'));
        $this->assertStringContainsString('รอสักครู่', (string) $res->json('message'));

        // คนอื่นยังคุยได้
        $this->chat('B')->assertStatus(422);
    }

    public function test_a_throttled_customer_does_not_eat_the_shared_ceiling(): void
    {
        $this->limits(perUser: 1, total: 3);

        $this->chat('A')->assertStatus(422);
        // A โดนเพดานตัวเอง — คำขอที่ถูกปัดต้องไม่ไปนับในถังรวม
        $this->chat('A')->assertStatus(429)->assertJsonPath('scope', 'customer');
        $this->chat('A')->assertStatus(429)->assertJsonPath('scope', 'customer');
        $this->chat('A')->assertStatus(429)->assertJsonPath('scope', 'customer');

        $this->chat('B')->assertStatus(422);
        $this->chat('C')->assertStatus(422);

        // ถังรวมครบ 3 แล้ว (A,B,C) → คนที่ 4 ได้ 429 แบบความจุเต็ม
        $this->chat('D')->assertStatus(429)
            ->assertJsonPath('reason_code', 'capacity')
            ->assertJsonPath('scope', 'chat');
    }

    public function test_shared_ceiling_saturation_is_logged_once_per_window(): void
    {
        $this->limits(perUser: 100, total: 2);

        Log::spy();

        $this->chat('r1')->assertStatus(422);
        $this->chat('r2')->assertStatus(422);
        $this->chat('r3')->assertStatus(429)->assertJsonPath('reason_code', 'capacity');
        $this->chat('r4')->assertStatus(429)->assertJsonPath('reason_code', 'capacity');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg, $ctx = []) => str_contains((string) $msg, 'เพดานรวมเต็ม') && ($ctx['scope'] ?? null) === 'chat')
            ->once();
    }

    public function test_chat_start_and_send_share_the_customer_bucket(): void
    {
        $this->limits(perUser: 2, total: 100);

        // start ที่ไม่ผ่าน validation (persona ไม่รู้จัก) → 422 แต่ผ่าน throttle แล้ว
        $this->withToken('server-token')
            ->postJson('/api/v1/juntra/server/chat/start', ['user_ref' => 'A', 'persona' => 'nobody'])
            ->assertStatus(422);
        $this->chat('A')->assertStatus(422);
        $this->chat('A')->assertStatus(429)->assertJsonPath('scope', 'customer');
    }

    public function test_malformed_user_ref_never_breaks_the_limiter(): void
    {
        $this->limits(perUser: 2, total: 100);

        // array / ยาวเกิน / อักขระแปลก → ไม่ 500 · ไปถังรวมของ "ไม่ถูกรูปแบบ" (คำขอโดน 422 อยู่แล้ว)
        $this->chat(['x' => 'y'])->assertStatus(422);
        $this->chat(str_repeat('a', 500))->assertStatus(422);
        $this->chat('../../etc')->assertStatus(429)->assertJsonPath('scope', 'customer');

        // ลูกค้าจริงไม่โดนผล
        $this->chat('42')->assertStatus(422);
        // user_ref ที่เป็นตัวเลข JSON = ถังเดียวกับข้อความ "42"
        $this->chat(42)->assertStatus(422);
        $this->chat('42')->assertStatus(429)->assertJsonPath('scope', 'customer');
    }

    public function test_chat_saturation_does_not_block_other_lanes(): void
    {
        $this->limits(perUser: 100, total: 1);

        $this->chat('A')->assertStatus(422);
        $this->chat('B')->assertStatus(429)->assertJsonPath('reason_code', 'capacity');

        // ถังแชทเต็ม แต่ทางค่าแนะนำ / ทางคำทำนายยังเข้าได้ (ไม่ใช่ 429)
        $aff = $this->withToken('server-token')->postJson('/api/v1/juntra/server/affiliate/accounts', []);
        $this->assertNotSame(429, $aff->status());

        $read = $this->withToken('server-token')->postJson('/api/v1/juntra/server/fortune/deep', []);
        $this->assertNotSame(429, $read->status());
    }

    public function test_unauthenticated_calls_do_not_consume_the_chat_ceiling(): void
    {
        $this->limits(perUser: 100, total: 1);

        // ไม่มี token → 401 ที่ juntra.server (อยู่ก่อน throttle) → ไม่กินถังรวมของแชท
        $this->postJson('/api/v1/juntra/server/chat/send', ['user_ref' => 'X'])->assertStatus(401);
        $this->postJson('/api/v1/juntra/server/chat/send', ['user_ref' => 'Y'])->assertStatus(401);

        $this->assertSame(0, RateLimiter::attempts(md5(JuntraServerRateLimits::CHAT.'all')));
        $this->chat('A')->assertStatus(422);
    }

    public function test_default_shared_ceiling_is_well_above_the_old_300_and_the_outer_gate_never_cuts_it(): void
    {
        // ค่าเริ่มต้น (ไม่ตั้ง env)
        config(['ratelimit.juntra_server' => [
            'total' => 2400, 'chat_per_user' => 60, 'chat_total' => 1200,
            'pay' => 120, 'read' => 60, 'affiliate' => 300,
        ]]);

        $this->assertSame(1200, JuntraServerRateLimits::chatTotal());
        $this->assertGreaterThan(300, JuntraServerRateLimits::chatTotal());
        $this->assertGreaterThanOrEqual(
            JuntraServerRateLimits::chatTotal() + 120 + 60 + 300,
            JuntraServerRateLimits::serverTotal(),
        );

        // ตั้งเพดานแชทสูงกว่าด่านนอก → ด่านนอกขยายตามให้เอง (ไม่ตัดแชทก่อนถึงเพดานที่ตั้ง)
        config(['ratelimit.juntra_server.chat_total' => 5000]);
        $this->assertSame(5000 + 120 + 60 + 300, JuntraServerRateLimits::serverTotal());

        // ค่าเพี้ยน → ค่าเริ่มต้น (ไม่ 0 = ไม่บล็อกทุกคำขอ)
        config(['ratelimit.juntra_server.chat_total' => 0, 'ratelimit.juntra_server.chat_per_user' => 'abc']);
        $this->assertSame(1200, JuntraServerRateLimits::chatTotal());
        $this->assertSame(60, JuntraServerRateLimits::chatPerUser());
    }

    public function test_preflight_reads_the_configured_shared_ceiling_from_the_route(): void
    {
        // preflight เทียบ "เพดานรวม" ของ chat/send กับ peak ข้อความ FB × 1.5
        config(['ratelimit.juntra_server.chat_total' => 3000, 'ratelimit.juntra_server.chat_per_user' => 60]);

        $cmd = app(FortuneFbWebOnlyPreflight::class);
        $method = new \ReflectionMethod($cmd, 'detectChatCeiling');
        $method->setAccessible(true);

        $this->assertSame(3000.0, $method->invoke($cmd));
    }

    public function test_chat_limiter_returns_per_customer_then_shared_layer(): void
    {
        $this->limits(perUser: 7, total: 900);

        $request = Request::create('/api/v1/juntra/server/chat/send', 'POST', ['user_ref' => 'abc']);
        $limits = RateLimiter::limiter(JuntraServerRateLimits::CHAT)($request);

        $this->assertCount(2, $limits);
        $this->assertSame('u:abc', $limits[0]->key);
        $this->assertSame(7, $limits[0]->maxAttempts);
        $this->assertSame('all', $limits[1]->key);
        $this->assertSame(900, $limits[1]->maxAttempts);
        $this->assertSame(60, $limits[1]->decaySeconds);
    }

    public function test_every_server_route_uses_a_registered_named_limiter(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with((string) $r->getName(), 'api.juntra.server.'));
        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $throttles = array_values(array_filter(
                $route->gatherMiddleware(),
                fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'),
            ));
            $this->assertContains('throttle:'.JuntraServerRateLimits::SERVER, $throttles, $route->getName());

            foreach ($throttles as $t) {
                $this->assertNotNull(RateLimiter::limiter(substr($t, 9)), $route->getName()." uses {$t}");
            }
        }

        $send = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->getName() === 'api.juntra.server.chat.send');
        $this->assertContains('throttle:'.JuntraServerRateLimits::CHAT, $send->gatherMiddleware());
    }
}
