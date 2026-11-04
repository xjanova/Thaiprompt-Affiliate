<?php

namespace Tests\Feature;

use App\Services\Fortune\LifeGraphService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 📈 /api/v1/juntra/server/fortune/lifegraph — กราฟชีวิตให้โรงละครคำทำนายของเว็บ
 *
 * ตัวเลขต้องชุดเดียวกับรูปกราฟใน LINE (LifeGraphService::forInputs) — อ้างอิงค่าจาก LifeGraphTest
 */
class JuntraServerLifeGraphApiTest extends TestCase
{
    use BuildsJuntraServerSchema;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
        LifeGraphService::flush();

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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        LifeGraphService::flush();
        parent::tearDown();
    }

    public function test_returns_the_same_graph_as_the_line_image(): void
    {
        $res = $this->withToken('server-token')->postJson('/api/v1/juntra/server/fortune/lifegraph', [
            'birth_date' => '1990-05-16',
            'birth_time' => '21:30',
            'birth_place' => 'กรุงเทพมหานคร',
        ])->assertOk();

        $g = LifeGraphService::forInputs('1990-05-16', 21.5, 'กรุงเทพมหานคร');
        $this->assertNotNull($g);
        // JSON เขียน 50.0 เป็น 50 — เทียบค่า ไม่เทียบชนิด
        $this->assertEquals(array_column($g['years'], 'score'), array_column($res->json('data.years'), 1));
        // ช่วงดาวเสวยตรงกับ LifeGraphTest (8:0-12, 6:12-33, …)
        $this->assertSame([0, 12], [$res->json('data.periods.0.from_age'), $res->json('data.periods.0.to_age')]);
        $this->assertSame(36, $res->json('data.current.age'));
        $this->assertSame([12, 21, 27], array_column($res->json('data.peaks'), 'age'));
        $this->assertSame('กรุงเทพมหานคร', $res->json('data.profile.province'));
        // ฉบับย่อ — ไม่ส่งก้อนเหตุผลรายปี/น้ำหนักออกไป
        $this->assertArrayNotHasKey('weights', $res->json('data'));
    }

    public function test_unknown_place_falls_back_and_bad_dates_are_rejected(): void
    {
        $this->withToken('server-token')->postJson('/api/v1/juntra/server/fortune/lifegraph', [
            'birth_date' => '1985-01-06',
            'birth_place' => 'ดาวอังคาร',
        ])->assertOk()->assertJsonPath('data.profile.province', null)->assertJsonPath('data.profile.birth_time', null);

        $this->withToken('server-token')->postJson('/api/v1/juntra/server/fortune/lifegraph', [
            'birth_date' => '16/05/1990',
        ])->assertStatus(422);
    }
}
