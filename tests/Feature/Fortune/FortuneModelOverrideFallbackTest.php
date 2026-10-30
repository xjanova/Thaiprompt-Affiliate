<?php

namespace Tests\Feature\Fortune;

use App\Models\AiApiKey;
use App\Services\FortuneAIService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 🛟 (2026-10-30, owner) key เรียกโมเดลที่ router เลือกไม่ได้ ลูกค้าที่จ่ายแล้วต้องยังได้คำตอบ
 *
 * router อ่านโมเดลจาก key หนึ่ง แล้วส่งผ่าน key อีกเลนหนึ่ง ถ้าสอง key อยู่คนละบัญชีจะเจอ model_not_found
 * ต้องถอยไปใช้โมเดลของ key เอง และห้ามนับเป็น error ของ key เพราะครบ 3 ครั้ง key ดีๆ จะถูกปิด
 */
class FortuneModelOverrideFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function key(string $purpose, string $model): AiApiKey
    {
        return AiApiKey::create([
            'name' => "เทส {$purpose}", 'provider' => 'openai', 'model' => $model,
            'api_key' => 'sk-'.str_repeat('t1', 20), 'is_active' => true, 'priority' => 50,
            'purpose' => $purpose, 'last_test_passed_at' => now(),
        ]);
    }

    /**
     * จำลองบัญชีที่เรียก $refused ไม่ได้ แต่เรียกโมเดลอื่นได้
     */
    private function fakeOpenAi(string $refused, int $status = 404, string $error = ''): void
    {
        $error = $error ?: "The model `{$refused}` does not exist or you do not have access to it.";

        Http::fake(['api.openai.com/*' => function (Request $request) use ($refused, $status, $error) {
            return $request['model'] === $refused
                ? Http::response(['error' => ['message' => $error]], $status)
                : Http::response([
                    'output_text' => 'ไพ่ใบนี้บอกว่าทางข้างหน้าเปิดแล้วนะลูก',
                    'usage' => ['input_tokens' => 10, 'output_tokens' => 20],
                ]);
        }]);
    }

    private function predict(string $override): array
    {
        return app(FortuneAIService::class)->generateWithRetryAndFallback(
            questions: ['งานจะดีขึ้นไหม'],
            promptTemplate: '{questions}',
            readingType: 'deep',
            userContext: 'override-test',
            purpose: 'prediction_celtic',
            modelOverrides: ['openai' => $override],
        );
    }

    /** @return array<int, string> โมเดลที่ถูกยิงจริงตามลำดับ */
    private function modelsCalled(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0]['model'])->values()->all();
    }

    public function test_prediction_falls_back_to_the_key_model(): void
    {
        $key = $this->key('prediction', 'gpt-6-luna');
        $this->fakeOpenAi('gpt-6.1-sol');

        $result = $this->predict('gpt-6.1-sol');

        $this->assertStringContainsString('ไพ่ใบนี้', $result['response']);
        $this->assertSame('gpt-6-luna', $result['model']);
        $this->assertSame(['gpt-6.1-sol', 'gpt-6-luna'], $this->modelsCalled());
        $this->assertSame(0, (int) $key->fresh()->consecutive_errors, 'key ไม่ได้เสีย ห้ามนับ error');
    }

    public function test_project_without_model_access_also_falls_back(): void
    {
        $this->key('prediction', 'gpt-6-luna');
        $this->fakeOpenAi('gpt-6.1-sol', 403, 'Project `proj_x` does not have access to model `gpt-6.1-sol`');

        $this->assertSame('gpt-6-luna', $this->predict('gpt-6.1-sol')['model']);
    }

    public function test_other_failures_do_not_silently_downgrade_the_model(): void
    {
        $key = $this->key('prediction', 'gpt-6-luna');
        $this->fakeOpenAi('gpt-6.1-sol', 500, 'server exploded');

        try {
            $this->predict('gpt-6.1-sol');
            $this->fail('ควรล้มต่อตามทางเดิม');
        } catch (Exception) {
            $this->assertNotContains('gpt-6-luna', $this->modelsCalled());
            $this->assertGreaterThan(0, (int) $key->fresh()->consecutive_errors + (int) ($key->fresh()->disabled_until !== null));
        }
    }

    public function test_pro_session_falls_back_to_the_key_model(): void
    {
        $key = $this->key('sensitive', 'gpt-6.1-sol');
        $this->fakeOpenAi('gpt-6-luna');

        $result = app(FortuneAIService::class)->generatePostReadingDeepResponse(
            'แล้วเรื่องงานล่ะคะ', null, [], 'คุณคือแม่หมอจันทรา', 'openai', 'gpt-6-luna'
        );

        $this->assertStringContainsString('ไพ่ใบนี้', $result['response']);
        $this->assertSame(['gpt-6-luna', 'gpt-6.1-sol'], $this->modelsCalled());
        $this->assertSame(0, (int) $key->fresh()->consecutive_errors);
    }

    public function test_model_error_without_an_override_stays_an_error(): void
    {
        $this->key('sensitive', 'gpt-6.1-sol');
        $this->fakeOpenAi('gpt-6.1-sol');

        $this->expectException(Exception::class);

        app(FortuneAIService::class)->generatePostReadingDeepResponse(
            'แล้วเรื่องงานล่ะคะ', null, [], 'คุณคือแม่หมอจันทรา', 'openai'
        );
    }
}
