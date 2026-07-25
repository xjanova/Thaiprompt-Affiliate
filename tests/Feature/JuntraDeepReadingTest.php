<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FortuneAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * POST /api/v1/juntra/fortune/deep
 *
 * แพ็กดูดวงเชิงลึก 39฿ ที่เว็บ จันทรา.online เรียกใช้ — ต้องเป็นตัวเดียวกับ
 * ที่บอท FB/LINE ขายอยู่ (readingType 'deep' → prompt template หลังบ้าน +
 * READING_CONFIG['deep'] ที่จูนมาแล้ว)
 */
class JuntraDeepReadingTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/juntra/fortune/deep';

    private function payload(array $override = []): array
    {
        return array_merge([
            'questions'  => ['ปีนี้การงานจะเป็นอย่างไร', 'ความรักจะลงตัวเมื่อไหร่'],
            'birth_date' => '1990-08-12',
            'name'       => 'คุณทดสอบ',
        ], $override);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson(self::URL, $this->payload())->assertUnauthorized();
    }

    /** ต้องเรียกด้วย readingType 'deep' ไม่ใช่ basic — ไม่งั้นลูกค้าจ่าย 39 ได้ของถูกกว่า */
    public function test_generates_a_deep_reading_not_a_basic_one(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $captured = [];
        $this->mock(FortuneAIService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('withCustomerContext')->once()->andReturnSelf();
            $mock->shouldReceive('generateFortuneTelling')
                ->once()
                ->andReturnUsing(function (...$args) use (&$captured) {
                    $captured = $args;
                    return ['response' => 'คำทำนายเชิงลึก', 'provider' => 'openai',
                            'model' => 'gpt-5.4-mini', 'tokens_used' => 4200];
                });
        });

        $this->postJson(self::URL, $this->payload())
            ->assertOk()
            ->assertJsonPath('data.reading', 'คำทำนายเชิงลึก')
            ->assertJsonPath('data.tokens_used', 4200);

        // args: questions, userProfile, userPosts, promptTemplate, readingType, birthDate
        $this->assertSame('deep', $captured[4] ?? null, 'ต้องเป็น deep ไม่ใช่ basic');
        $this->assertSame('1990-08-12', $captured[5] ?? null, 'ต้องส่งวันเกิดไปให้ AI ใช้');
        $this->assertCount(2, $captured[0] ?? []);
    }

    public function test_ai_failure_returns_503_so_the_caller_can_refund(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneAIService::class, function ($mock) {
            $mock->shouldReceive('withCustomerContext')->once()->andReturnSelf();
            $mock->shouldReceive('generateFortuneTelling')->once()
                ->andThrow(new \RuntimeException('pool down'));
        });

        $this->postJson(self::URL, $this->payload())->assertStatus(503);
    }

    public function test_empty_reading_returns_503(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneAIService::class, function ($mock) {
            $mock->shouldReceive('withCustomerContext')->once()->andReturnSelf();
            $mock->shouldReceive('generateFortuneTelling')->once()->andReturn(['response' => '  ']);
        });

        $this->postJson(self::URL, $this->payload())->assertStatus(503);
    }

    public function test_rejects_empty_or_oversized_question_list(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(self::URL, $this->payload(['questions' => []]))->assertStatus(422);
        $this->postJson(self::URL, $this->payload(['questions' => array_fill(0, 9, 'ถามเยอะ')]))
            ->assertStatus(422);
    }

    public function test_rejects_future_birth_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(self::URL, $this->payload(['birth_date' => now()->addDay()->format('Y-m-d')]))
            ->assertStatus(422);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
