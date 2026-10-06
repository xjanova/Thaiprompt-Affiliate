<?php

namespace Tests\Feature;

use App\Models\AiApiKey;
use App\Models\FortuneTellingSetting;
use App\Services\AiApiKeyPoolService;
use App\Services\Fortune\FortuneSensitivityDetector;
use App\Services\FortuneAIService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * 🧭 classifier ของ FortuneSensitivityDetector ต้องไม่ตายตามค่ายที่ตั้งไว้ (2026-10-06)
 *
 * ต้นเรื่อง: prod ปิด groq key ครบทุกตัว แต่ sensitive_classifier_provider ยังเป็น 'groq'
 *   และคอลัมน์ chat_ai_provider ค้าง 'groq' ขณะที่ resolver แชทแจก key gemini
 *   → fallback เดิมส่ง key gemini ไป Groq → 401 ทุกครั้ง → circuit เปิดวน → เหลือ heuristic ล้วน
 *
 * ล็อกไว้:
 *   1. ไม่มี key ของค่ายที่ตั้ง → ยืมเส้นแชท และ key แชทค่ายอื่นห้ามหลุดไปยิง Groq
 *   2. มี key ของค่ายที่ตั้ง → ยังใช้ค่ายนั้นก่อน (ไม่แตะเส้นแชท)
 *   3. ค่ายที่ตั้งปฏิเสธ key (401) → ยืมเส้นแชท
 *   4. เส้นแชทล้ม → ใช้ heuristic ต่อ ไม่ throw · ล้มครบ 3 ครั้ง circuit เปิด หยุดเรียก AI
 *   5. คำตอบห่อ ```json ก็แกะได้
 *
 * ไม่แตะ DB — pool / AI / settings ถูกแทนในหน่วยความจำ · HTTP ถูก fake
 */
class FortuneSensitivityClassifierRouteTest extends TestCase
{
    /** ยาว ≥ 25 ตัว · มีคำดูดวง · ไม่ติด keyword/topic → heuristic confidence 0 → ต้องเรียก classifier */
    private const MSG = 'แฟนเก่าทิ้งไปแล้วรู้สึกชีวิตไม่มีความหมาย อยากรู้เรื่องความรักว่าจะผ่านไปได้ไหมคะ';

    private const DISTRESS_JSON = '{"mood_level": 2, "complexity": 4, "is_offtopic": false, "reason": "emotional_distress"}';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * settings แบบ prod: classifier = groq · คอลัมน์ chat_ai_provider ค้าง 'groq'
     * แต่ resolver แชทแจกค่าย $resolvedChatProvider
     */
    private function settings(string $resolvedChatProvider = 'gemini'): FortuneTellingSetting
    {
        $settings = new class extends FortuneTellingSetting
        {
            public string $resolvedChatProvider = 'gemini';

            public function getChatAIProvider(): string
            {
                return $this->resolvedChatProvider;
            }

            public function getChatAIApiKey(): ?string
            {
                return 'chat-key-'.$this->resolvedChatProvider;
            }
        };
        $settings->resolvedChatProvider = $resolvedChatProvider;
        $settings->forceFill([
            'sensitive_detection_mode' => 'hybrid',
            'sensitive_classifier_provider' => 'groq',
            'sensitive_classifier_model' => 'llama-3.3-70b-versatile',
            'chat_ai_provider' => 'groq',
            'sensitive_keywords' => ['มึง'],
            'sensitive_topics' => ['ป่วยหนัก'],
        ]);

        return $settings;
    }

    /** pool ที่แจก key ตัวเดียว (null = ค่ายนี้ไม่มี key active) */
    private function poolGiving(?AiApiKey $key): void
    {
        $pool = Mockery::mock(AiApiKeyPoolService::class);
        $pool->shouldReceive('acquireKey')->andReturn($key);
        $pool->shouldReceive('releaseKey');
        $pool->shouldReceive('requestExceedsTpm')->andReturn(false);
        $this->app->instance(AiApiKeyPoolService::class, $pool);
    }

    private function groqKey(): AiApiKey
    {
        $key = new class extends AiApiKey
        {
            public function getApiKeyAttribute($value): string
            {
                return 'gsk_test_key';
            }
        };
        $key->setRawAttributes(['id' => 7, 'provider' => 'groq']);

        return $key;
    }

    private function groqAnswer(string $json, int $status = 200): array
    {
        return ['api.groq.com/*' => Http::response(
            $status === 200 ? ['choices' => [['message' => ['content' => $json]]]] : ['error' => ['message' => 'Invalid API Key']],
            $status
        )];
    }

    /** AI แชทที่ตอบคำเดียว และเก็บสิ่งที่ได้รับไว้ตรวจ */
    private function chatAnswering(string $answer, ?array &$captured = null): FortuneAIService
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')
            ->once()
            ->andReturnUsing(function (string $system, string $input, array $config) use ($answer, &$captured) {
                $captured = ['system' => $system, 'input' => $input, 'config' => $config];

                return ['response' => $answer, 'provider' => 'gemini'];
            });

        return $ai;
    }

    private function chatNeverCalled(): FortuneAIService
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldNotReceive('chatWithCustomSystemPrompt');

        return $ai;
    }

    // ───────────────────────── 1. ไม่มี key ของค่ายที่ตั้ง ─────────────────────────

    public function test_no_active_groq_key_routes_through_chat_resolver_without_touching_groq(): void
    {
        Http::fake();
        $this->poolGiving(null);
        $ai = $this->chatAnswering(self::DISTRESS_JSON, $captured);

        $result = (new FortuneSensitivityDetector($this->settings('gemini'), $ai))->detect(self::MSG);

        // key gemini ของแชทห้ามหลุดไปยิง Groq (ต้นเหตุ 401 บน prod)
        Http::assertNothingSent();

        $this->assertSame('classifier', $result['detection_used']);
        $this->assertTrue($result['is_sensitive']);
        $this->assertFalse($result['is_offtopic']);
        $this->assertSame(4, $result['complexity']);
        $this->assertContains('classifier:emotional_distress', $result['reasons']);

        $this->assertSame(['temperature' => 0.0, 'max_tokens' => 150, 'timeout' => 5], $captured['config']);
        $this->assertStringContainsString(self::MSG, $captured['input']);
        $this->assertStringContainsString('Output strict JSON only', $captured['system']);
    }

    public function test_chat_key_is_still_used_on_groq_when_chat_resolver_really_is_groq(): void
    {
        Http::fake($this->groqAnswer(self::DISTRESS_JSON));
        $this->poolGiving(null);

        $result = (new FortuneSensitivityDetector($this->settings('groq'), $this->chatNeverCalled()))->detect(self::MSG);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.groq.com')
            && $request->hasHeader('Authorization', 'Bearer chat-key-groq'));
        $this->assertSame('classifier', $result['detection_used']);
        $this->assertSame(4, $result['complexity']);
    }

    // ───────────────────────── 2. มี key ของค่ายที่ตั้ง ─────────────────────────

    public function test_active_groq_key_keeps_using_groq_first(): void
    {
        Http::fake($this->groqAnswer('{"mood_level": 5, "complexity": 1, "is_offtopic": false, "reason": "abusive"}'));
        $this->poolGiving($this->groqKey());

        $result = (new FortuneSensitivityDetector($this->settings('gemini'), $this->chatNeverCalled()))->detect(self::MSG);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.groq.com')
            && $request->hasHeader('Authorization', 'Bearer gsk_test_key'));
        $this->assertSame(5, $result['mood_level']);
        $this->assertTrue($result['is_sensitive']);
        $this->assertContains('classifier:abusive', $result['reasons']);
    }

    // ───────────────────────── 3. ค่ายที่ตั้งปฏิเสธ key ─────────────────────────

    public function test_groq_rejecting_key_falls_back_to_chat_resolver(): void
    {
        Http::fake($this->groqAnswer('', 401));
        $this->poolGiving($this->groqKey());
        $ai = $this->chatAnswering(self::DISTRESS_JSON);

        $result = (new FortuneSensitivityDetector($this->settings('gemini'), $ai))->detect(self::MSG);

        Http::assertSentCount(1);
        $this->assertSame('classifier', $result['detection_used']);
        $this->assertSame(4, $result['complexity']);
        $this->assertNull(Cache::get('fortune:classifier:fails:count'), '401 ที่กู้ได้ด้วยเส้นแชท ห้ามนับเป็น fail');
    }

    // ───────────────────────── 4. เส้นแชทล้ม ─────────────────────────

    public function test_chat_resolver_failure_fails_open_and_trips_circuit_after_three(): void
    {
        Http::fake();
        $this->poolGiving(null);
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')
            ->times(3)
            ->andThrow(new Exception('Gemini Chat API Error: quota exceeded'));

        $detector = new FortuneSensitivityDetector($this->settings('gemini'), $ai);

        for ($i = 1; $i <= 4; $i++) {
            $result = $detector->detect(self::MSG);

            $this->assertSame('heuristic', $result['detection_used'], "รอบ {$i}");
            $this->assertFalse($result['is_sensitive'], "รอบ {$i}");
            $this->assertSame(1, $result['complexity'], "รอบ {$i}");
            $this->assertSame($i >= 3, (bool) Cache::get('fortune:classifier:fails:open'), "circuit รอบ {$i}");
        }

        Http::assertNothingSent();
    }

    // ───────────────────────── 5. คำตอบห่อ code fence ─────────────────────────

    public function test_fenced_json_from_chat_model_is_parsed(): void
    {
        Http::fake();
        $this->poolGiving(null);
        $ai = $this->chatAnswering("```json\n{\"mood_level\": 1, \"complexity\": 1, \"is_offtopic\": true, \"reason\": \"coding_request\"}\n```");

        $result = (new FortuneSensitivityDetector($this->settings('gemini'), $ai))->detect(self::MSG);

        $this->assertSame('classifier', $result['detection_used']);
        $this->assertTrue($result['is_offtopic']);
        $this->assertFalse($result['is_sensitive']);
        $this->assertContains('classifier:offtopic', $result['reasons']);
    }
}
