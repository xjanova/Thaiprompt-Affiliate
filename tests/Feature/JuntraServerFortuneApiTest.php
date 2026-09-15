<?php

namespace Tests\Feature;

use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🔮 /api/v1/juntra/server/fortune/* — คำทำนายที่ลูกค้าเว็บ จันทรา.online จ่ายแล้ว
 *
 * ทำไม: เดิมมีแต่ทาง token ของลูกค้า (/api/v1/juntra/fortune/*) — ลูกค้าที่สมัครด้วยเบอร์/อีเมล
 * (ส่วนใหญ่ของเว็บ) ไม่มี token → จ่ายเปิดไพ่แล้วได้ข้อความประกอบจากความหมายไพ่แทนคำทำนาย
 * ส่วนดูดวงเชิงลึกถูกคืนเงินทุกครั้ง
 */
class JuntraServerFortuneApiTest extends TestCase
{
    use BuildsJuntraServerSchema;

    /** @var array<int,array<int,mixed>> */
    private array $aiCalls = [];

    /** @var array<int,array<string,mixed>> */
    private array $contexts = [];

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

        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('withCustomerContext')->andReturnUsing(function (array $ctx) use (&$ai) {
            $this->contexts[] = $ctx;

            return $ai;
        });
        $ai->shouldReceive('chatWithCustomSystemPrompt')->andReturnUsing(function (...$args) {
            $this->aiCalls[] = $args;

            return ['response' => 'ไพ่ถ้วยสองบอกว่าความรักของลูกกำลังเบ่งบานค่ะ', 'provider' => 'gemini', 'model' => 'flash'];
        });
        $ai->shouldReceive('generateFortuneTelling')->andReturnUsing(function (...$args) {
            $this->aiCalls[] = $args;

            return ['response' => 'ดวงเชิงลึกของลูกช่วงนี้เด่นเรื่องการงานค่ะ', 'provider' => 'gemini', 'model' => 'pro'];
        });
        $this->app->instance(FortuneAIService::class, $ai);
    }

    public function test_a_web_customer_without_a_thaiprompt_account_gets_a_real_tarot_interpretation(): void
    {
        $this->withToken('server-token')->postJson('/api/v1/juntra/server/fortune/tarot/interpret', [
            'spread' => 'tarot_love',
            'spread_name' => 'ไพ่ความรัก',
            'question' => 'ความรักปีนี้เป็นอย่างไร',
            'prompt' => 'อ่านไพ่ชุดนี้ให้หน่อย',
            'cards' => [
                ['position' => 1, 'position_label' => 'ใจของเรา', 'name_en' => 'Two of Cups', 'name_th' => 'ถ้วยสอง', 'reversed' => false],
            ],
        ])->assertOk()
            ->assertJsonPath('data.interpretation', 'ไพ่ถ้วยสองบอกว่าความรักของลูกกำลังเบ่งบานค่ะ')
            ->assertJsonPath('data.ai_provider', 'gemini');

        $this->assertCount(1, $this->aiCalls);
    }

    public function test_deep_reading_uses_the_name_juntraweb_sends_and_never_a_thaiprompt_user_id(): void
    {
        $this->withToken('server-token')->postJson('/api/v1/juntra/server/fortune/deep', [
            'questions' => ['งานปีนี้จะดีไหม'],
            'name' => 'สมใจ',
        ])->assertOk()->assertJsonPath('data.reading', 'ดวงเชิงลึกของลูกช่วงนี้เด่นเรื่องการงานค่ะ');

        // There is no Thaiprompt user behind a server call — only the web customer's name.
        $this->assertSame([['customer_name' => 'สมใจ']], $this->contexts);
    }

    public function test_every_throttle_on_the_server_lane_has_its_own_bucket(): void
    {
        // All server-lane calls come from one IP (the web server). An unprefixed throttle shares
        // one key with every other unprefixed throttle — two layers count each request twice, and
        // every customer's chat competes with slip checks for the same bucket.
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with((string) $r->getName(), 'api.juntra.server.'));

        $this->assertNotEmpty($routes);
        foreach ($routes as $route) {
            $throttles = array_filter($route->gatherMiddleware(), fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'));
            $this->assertNotEmpty($throttles, $route->getName().' has no throttle');
            foreach ($throttles as $t) {
                $this->assertCount(3, explode(',', substr($t, 9)), $route->getName()." uses unprefixed {$t}");
            }
            $prefixes = array_map(fn ($t) => explode(',', $t)[2], $throttles);
            $this->assertSame(array_unique($prefixes), $prefixes, $route->getName().' reuses a throttle bucket');
        }
    }

    public function test_the_fortune_lane_refuses_a_request_without_the_server_token(): void
    {
        $this->postJson('/api/v1/juntra/server/fortune/deep', ['questions' => ['x']])->assertStatus(401);
    }
}
