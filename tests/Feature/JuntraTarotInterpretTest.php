<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FortuneAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * POST /api/v1/juntra/fortune/tarot/interpret
 *
 * endpoint คำทำนายไพ่ที่เว็บ จันทรา.online (juntraweb) เรียกใช้
 * juntraweb เขียนฝั่ง client รอไว้นานแล้ว (FortuneBotClient::interpretTarot)
 * แต่ฝั่งนี้ยังไม่มี route → 404 → ตกไป chat pipeline ที่ไม่มีคลังความรู้ไพ่
 *
 * สิ่งที่ล็อกไว้:
 *   - ต้อง auth (คนนอกยิงไม่ได้ เพราะกิน token AI ของพูล)
 *   - คลังความรู้ตามไพ่ที่เปิดจริงถูกแนบเข้า prompt
 *   - AI ล่ม/ตอบว่าง → 503 เพื่อให้ juntraweb fall back ได้ ไม่ใช่ 500
 */
class JuntraTarotInterpretTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/juntra/fortune/tarot/interpret';

    private function payload(array $override = []): array
    {
        return array_merge([
            'spread'      => 'tarot_celtic',
            'spread_key'  => 'celtic',
            'spread_name' => 'เซลติกครอส',
            'question'    => 'ความรักของหนูจะเป็นอย่างไรคะ',
            'prompt'      => 'อ่านไพ่ชุดนี้ให้เจ้าชะตาแบบฟันธง',
            'cards'       => [
                ['position' => 1, 'position_label' => 'สถานการณ์ปัจจุบัน', 'name_en' => 'The Star',
                 'name_th' => 'ดวงดาว', 'reversed' => false, 'meaning' => 'ความหวัง'],
                ['position' => 2, 'position_label' => 'อุปสรรค', 'name_en' => 'The Tower',
                 'name_th' => 'หอคอย', 'reversed' => true, 'meaning' => 'การพังทลาย'],
            ],
        ], $override);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson(self::URL, $this->payload())->assertUnauthorized();
    }

    public function test_returns_interpretation_from_the_ai_pool(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneAIService::class, function ($mock) {
            $mock->shouldReceive('chatWithCustomSystemPrompt')
                ->once()
                ->andReturn([
                    'response'    => '**ภาพรวม** ดวงดาวเปิดทางให้ค่ะ',
                    'provider'    => 'openai',
                    'model'       => 'gpt-5.4-mini',
                    'tokens_used' => 1234,
                ]);
        });

        $this->postJson(self::URL, $this->payload())
            ->assertOk()
            ->assertJsonPath('data.interpretation', '**ภาพรวม** ดวงดาวเปิดทางให้ค่ะ')
            ->assertJsonPath('data.ai_provider', 'openai')
            ->assertJsonPath('data.tokens_used', 1234);
    }

    /** คลังความรู้ต้องถูกแนบเข้าไปใน prompt ไม่ใช่ส่งแต่ข้อความของ juntraweb */
    public function test_injects_card_knowledge_into_the_prompt(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $captured = null;
        $this->mock(FortuneAIService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('chatWithCustomSystemPrompt')
                ->once()
                ->andReturnUsing(function ($system, $user) use (&$captured) {
                    $captured = $user;
                    return ['response' => 'ok'];
                });
        });

        $this->postJson(self::URL, $this->payload())->assertOk();

        $this->assertNotNull($captured);
        $this->assertStringContainsString('อ่านไพ่ชุดนี้ให้เจ้าชะตาแบบฟันธง', $captured);
        $this->assertStringContainsString('ตำราประกอบ', $captured,
            'ต้องแนบคลังความรู้ไพ่เข้า prompt — ไม่งั้นเว็บได้คำทำนายคุณภาพเดียวกับ chat ธรรมดา');
    }

    /** AI ตอบว่าง → 503 เพื่อให้ juntraweb ตกไปใช้ทางสำรองของตัวเอง */
    public function test_empty_ai_reply_returns_503_so_the_caller_can_fall_back(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneAIService::class, function ($mock) {
            $mock->shouldReceive('chatWithCustomSystemPrompt')->once()->andReturn(['response' => '   ']);
        });

        $this->postJson(self::URL, $this->payload())->assertStatus(503);
    }

    public function test_ai_exception_returns_503_not_500(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneAIService::class, function ($mock) {
            $mock->shouldReceive('chatWithCustomSystemPrompt')
                ->once()->andThrow(new \RuntimeException('pool down'));
        });

        $this->postJson(self::URL, $this->payload())->assertStatus(503);
    }

    public function test_rejects_payload_without_cards(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(self::URL, $this->payload(['cards' => []]))
            ->assertStatus(422);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
