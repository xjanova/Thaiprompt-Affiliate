<?php

namespace Tests\Feature;

use App\Services\FortuneAIService;
use App\Services\FortuneKnowledgeService;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🌸 (2026-11-06 เจ้าของสั่ง) "การดูไพ่ฟรี ควรให้บุษบาดูฟรี เพราะไพ่ 1 ใบ บุษบาเป็นคนดูแลไม่ใช่แม่หมอ"
 *
 * เว็บส่ง persona ของห้องไพ่ฟรีมากับคำขอ — busaba = คำทำนายพูดเป็นบุษบา เรียกลูกค้าว่า "คุณ"
 * ไม่ส่ง / maemor = prompt เดิมของแม่หมอทุกตัวอักษร (เว็บรุ่นก่อนยังได้ผลเหมือนเดิม)
 */
class JuntraFreeTarotPersonaTest extends TestCase
{
    use BuildsJuntraServerSchema;

    /** @var array<int,string> */
    private array $systems = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();

        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');

        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')->andReturnUsing(function (...$args) {
            $this->systems[] = (string) $args[0];

            return ['response' => 'ไพ่ **ดวงอาทิตย์** บอกว่าช่วงนี้ชีวิตสว่างขึ้นแล้วค่ะ ทำสิ่งที่ตั้งใจต่อไปได้เลย'."\n[NEXTQ]งานจะดีขึ้นไหม|ความรักเป็นไง[/NEXTQ]", 'provider' => 'gemini'];
        });
        $this->app->instance(FortuneAIService::class, $ai);

        $kb = Mockery::mock(FortuneKnowledgeService::class);
        $kb->shouldReceive('personRoleLinesForCards')->andReturn('');
        $kb->shouldReceive('muLinesForCards')->andReturn('');
        $this->app->instance(FortuneKnowledgeService::class, $kb);
    }

    private function free(array $extra = [])
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/fortune/tarot/free', $extra + [
            'card' => ['name_en' => 'The Sun', 'name_th' => 'ดวงอาทิตย์', 'is_reversed' => false],
        ]);
    }

    public function test_the_free_card_is_read_by_busaba_when_the_web_asks_for_her(): void
    {
        $this->free(['persona' => 'busaba'])->assertOk()->assertJsonPath('data.next_questions', ['งานจะดีขึ้นไหม', 'ความรักเป็นไง']);

        $system = end($this->systems);
        $this->assertStringStartsWith('คุณคือ "บุษบา"', $system);
        $this->assertStringContainsString('เรียกเจ้าชะตาว่า "คุณ"', $system);
        $this->assertStringContainsString('ห้ามอ้างว่าเป็นแม่หมอ', $system);
        $this->assertStringNotContainsString('คุณคือ "แม่หมอจันทรา"', $system);
    }

    public function test_without_a_persona_the_free_card_stays_mae_mor(): void
    {
        $this->free()->assertOk();
        $this->assertStringStartsWith('คุณคือ "แม่หมอจันทรา"', end($this->systems));

        $this->free(['persona' => 'kunsai'])->assertOk();
        $this->assertStringStartsWith('คุณคือ "แม่หมอจันทรา"', end($this->systems));

        $this->free(['persona' => 'someone'])->assertStatus(422);
    }
}
