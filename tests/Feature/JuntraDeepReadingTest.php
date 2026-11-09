<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * POST /api/v1/juntra/fortune/deep
 *
 * แพ็กดูดวงเชิงลึก 39฿ ที่เว็บ จันทรา.online เรียกใช้ — ต้องเป็นตัวเดียวกับที่บอท FB/LINE ขายอยู่
 *
 * 🛡️ (2026-11-07) เดิมเรียก generateFortuneTelling ครั้งเดียว (ไม่มี purpose → key แชท Haiku ตอบคำปฏิเสธ
 *   แทนคำทำนายที่ลูกค้าจ่าย) ตอนนี้:
 *   - ทีละข้อ (question_index) → FortuneConversationService::answerDeepQuestionForWeb (เลนเดียวกับบอท)
 *   - ทั้งบิล (ไม่ส่ง question_index) → คีย์พูลบิลจ่ายเงิน prediction_deep
 *   - คำปฏิเสธ/ไม่ครบ → 503 + reason_code ให้เว็บคืนเงิน
 */
class JuntraDeepReadingTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/juntra/fortune/deep';

    /** คำทำนายยาวพอผ่านด่านความครบ (≥ 250 ตัว · ไทย ≥ 150) */
    private const GOOD = 'ดวงการงานช่วงนี้ดาวพฤหัสหนุนภพกัมมะ งานที่ค้างจะคลี่คลายในอีกไม่กี่สัปดาห์ค่ะ ';

    private const REFUSAL = 'ขออภัยค่ะ ข้อความนี้เป็นคำสั่งภายใน ไม่ใช่คำถามจากลูกค้า จึงไม่ทำตามค่ะ';

    private function payload(array $override = []): array
    {
        return array_merge([
            'questions' => ['ปีนี้การงานจะเป็นอย่างไร', 'ความรักจะลงตัวเมื่อไหร่'],
            'birth_date' => '1990-08-12',
            'name' => 'คุณทดสอบ',
        ], $override);
    }

    private function good(): string
    {
        return str_repeat(self::GOOD, 5);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson(self::URL, $this->payload())->assertUnauthorized();
    }

    /** ทั้งบิล: ต้องเป็น deep บนเลนบิลจ่ายเงิน (prediction_deep) ไม่ใช่ยิงครั้งเดียวด้วย key ของ constructor */
    public function test_whole_bill_reads_deep_on_the_paid_lane(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $captured = [];
        $this->mock(FortuneAIService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('withCustomerContext')->once()->andReturnSelf();
            $mock->shouldReceive('withConfigOverride')->once()->andReturnSelf();
            $mock->shouldNotReceive('generateFortuneTelling');
            $mock->shouldReceive('generateWithRetryAndFallback')
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

        // args: questions, userProfile, userPosts, promptTemplate, readingType, birthDate, userContext, purpose
        $this->assertCount(2, $captured[0] ?? []);
        $this->assertSame('deep', $captured[4] ?? null, 'ต้องเป็น deep ไม่ใช่ basic');
        $this->assertSame('1990-08-12', $captured[5] ?? null, 'ต้องส่งวันเกิดไปให้ AI ใช้');
        $this->assertSame('prediction_deep', $captured[7] ?? null, 'บิลจ่ายเงินต้องอยู่บนเลน prediction_deep');
    }

    public function test_whole_bill_refusal_returns_503_so_the_web_refunds(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneAIService::class, function ($mock) {
            $mock->shouldReceive('withCustomerContext')->andReturnSelf();
            $mock->shouldReceive('withConfigOverride')->andReturnSelf();
            $mock->shouldReceive('generateWithRetryAndFallback')->once()
                ->andReturn(['response' => self::REFUSAL, 'model' => 'claude-haiku-5-5']);
        });

        $this->postJson(self::URL, $this->payload())
            ->assertStatus(503)
            ->assertJsonPath('reason_code', 'model_refused');
    }

    public function test_ai_failure_returns_503_so_the_caller_can_refund(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneAIService::class, function ($mock) {
            $mock->shouldReceive('withCustomerContext')->andReturnSelf();
            $mock->shouldReceive('withConfigOverride')->andReturnSelf();
            $mock->shouldReceive('generateWithRetryAndFallback')->once()
                ->andThrow(new \RuntimeException('pool down'));
        });

        $this->postJson(self::URL, $this->payload())->assertStatus(503);
    }

    public function test_empty_reading_returns_503(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneAIService::class, function ($mock) {
            $mock->shouldReceive('withCustomerContext')->andReturnSelf();
            $mock->shouldReceive('withConfigOverride')->andReturnSelf();
            $mock->shouldReceive('generateWithRetryAndFallback')->once()->andReturn(['response' => '  ']);
        });

        $this->postJson(self::URL, $this->payload())->assertStatus(503);
    }

    /** ทีละข้อ: ส่งต่อให้เลนเดียวกับบอท พร้อมคำตอบข้อก่อน (timeline สอดคล้อง) */
    public function test_one_question_uses_the_same_engine_as_the_chat_bill(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $captured = [];
        $this->mock(FortuneConversationService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('answerDeepQuestionForWeb')->once()
                ->andReturnUsing(function (...$args) use (&$captured) {
                    $captured = $args;

                    return ['ai' => ['provider' => 'openai', 'model' => 'gpt-6-luna'], 'text' => $this->good(),
                        'complete' => true, 'tokens' => 3100, 'purpose' => 'prediction_deep'];
                });
        });

        $this->postJson(self::URL, $this->payload([
            'question_index' => 1,
            'previous' => [['question' => 'ปีนี้การงานจะเป็นอย่างไร', 'answer' => 'งานรุ่งค่ะ']],
            'user_ref' => 'jw:42',
        ]))
            ->assertOk()
            ->assertJsonPath('data.question_index', 1)
            ->assertJsonPath('data.purpose', 'prediction_deep')
            ->assertJsonPath('data.tokens_used', 3100);

        // args: questions, index, previous, userProfile, birthDate, userRef, budgetSec
        $this->assertCount(2, $captured[0]);
        $this->assertSame(1, $captured[1]);
        $this->assertSame('งานรุ่งค่ะ', $captured[2][0]['answer']);
        $this->assertSame('1990-08-12', $captured[4]);
        $this->assertSame('jw:42', $captured[5]);
        $this->assertLessThan(60, $captured[6], 'คำขอหนึ่งต้องจบก่อนเว็บตัดที่ ~55-60 วิ');
    }

    public function test_one_question_refusal_returns_503_model_refused(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneConversationService::class, function ($mock) {
            $mock->shouldReceive('answerDeepQuestionForWeb')->once()->andReturn([
                'ai' => ['model' => 'claude-haiku-5-5'], 'text' => self::REFUSAL,
                'complete' => false, 'tokens' => 90, 'purpose' => 'prediction_deep',
            ]);
        });

        $this->postJson(self::URL, $this->payload(['question_index' => 0]))
            ->assertStatus(503)
            ->assertJsonPath('reason_code', 'model_refused');
    }

    public function test_one_question_incomplete_returns_503_incomplete(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneConversationService::class, function ($mock) {
            $mock->shouldReceive('answerDeepQuestionForWeb')->once()->andReturn([
                'ai' => ['model' => 'gemini-3.5-flash'], 'text' => 'Block 2: Mars (Winas/Patni)',
                'complete' => false, 'tokens' => 40, 'purpose' => 'prediction_deep',
            ]);
        });

        $this->postJson(self::URL, $this->payload(['question_index' => 0]))
            ->assertStatus(503)
            ->assertJsonPath('reason_code', 'incomplete');
    }

    public function test_one_question_engine_failure_returns_503(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneConversationService::class, function ($mock) {
            $mock->shouldReceive('answerDeepQuestionForWeb')->once()->andThrow(new \RuntimeException('pool down'));
        });

        $this->postJson(self::URL, $this->payload(['question_index' => 0]))->assertStatus(503);
    }

    public function test_one_question_index_out_of_range_is_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneConversationService::class, fn ($mock) => $mock->shouldNotReceive('answerDeepQuestionForWeb'));

        $this->postJson(self::URL, $this->payload(['question_index' => 3]))
            ->assertStatus(422)
            ->assertJsonPath('reason_code', 'bad_index');
    }

    /** 🤍 (2026-11-09 owner) เลิกตรวจจับ self-harm แล้ว — คำถามแบบนี้ไปให้ AI ทำนายตามปกติ (ไม่ปัด 422) */
    public function test_self_harm_question_goes_to_the_normal_reading(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->mock(FortuneConversationService::class, function ($mock) {
            $mock->shouldReceive('answerDeepQuestionForWeb')->once()->andThrow(new \RuntimeException('ถึง AI แล้ว'));
        });

        // ถึงเส้นทำนายจริง (AI ล้มในเทสต์ = 503) — ไม่ใช่ 422 question_rejected แบบเดิม
        $this->postJson(self::URL, $this->payload(['questions' => ['อยากตายมาก ไม่อยากอยู่แล้ว'], 'question_index' => 0]))
            ->assertStatus(503);
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
