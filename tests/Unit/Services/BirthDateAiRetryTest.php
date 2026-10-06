<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * 🎂 AI แปลงวันเกิด (parseBirthDateWithAI) — กันข้อความว่าง + ลองคีย์อื่นเมื่อล่มชั่วคราว (2026-10-06)
 *
 * ต้นเรื่อง: log prod 2026-10-01 → 06 สำเร็จ 5 ล้ม 24 และ log อยู่ระดับ DEBUG ไม่มีใครเห็น
 *   - 12× Gemini "high demand" · 6× timeout 15 วิ · 1× service unavailable
 *   - 4× "Request has empty input" — ลูกค้าพิมพ์ "ค่ะ"/"คะ"/"ค่า" ถูกตัดคำลงท้ายเหลือ ""
 *
 * ล็อกไว้:
 *   1. ข้อความว่าง / ไม่มีตัวเลข = ไม่ยิง AI เลย
 *   2. 503 / high demand / timeout → ลองคีย์อื่นใน Pool อีก "ครั้งเดียว" (ห้ามคีย์เดิม)
 *   3. มีค่าย/รุ่นอื่นให้เลือก → หยิบก่อน (high demand เป็นเรื่องของรุ่น)
 *   4. error ถาวร (400) ไม่ลองซ้ำ · ล้มทุกแบบ log ระดับ WARNING
 *
 * ไม่แตะ DB — settings เป็น mock · Pool ถูกแทนด้วยรายการคงที่ · HTTP ผ่าน Http::fake
 */
class BirthDateAiRetryTest extends TestCase
{
    private const GEMINI = 'generativelanguage.googleapis.com/*';

    private const GROQ = 'api.groq.com/*';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * FortuneAIService ที่ข้าม constructor (อ่าน DB) — คีย์รอบแรก = key-first (gemini)
     *
     * @param  array<int, array>  $poolKeys  รายการที่ getAllAvailableKeys จะคืน
     */
    private function service(array $poolKeys): FortuneAIService
    {
        $settings = Mockery::mock(FortuneTellingSetting::class);
        $settings->shouldReceive('getChatAIApiKey')->andReturn('key-first');
        $settings->shouldReceive('getChatAIProvider')->andReturn('gemini');
        $settings->shouldReceive('getChatAIModel')->andReturn('gemini-3.1-flash-lite');

        return new class($settings, $poolKeys) extends FortuneAIService
        {
            public function __construct(FortuneTellingSetting $settings, private array $poolKeys)
            {
                $this->settings = $settings;
            }

            protected function getAllAvailableKeys(?string $userContext = null, string $purpose = 'prediction', ?string $preferredProvider = null): array
            {
                return $this->poolKeys;
            }
        };
    }

    private static function key(string $apiKey, string $provider = 'gemini', string $model = 'gemini-3.1-flash-lite'): array
    {
        return ['provider' => $provider, 'api_key' => $apiKey, 'model' => $model, 'name' => $apiKey, 'pool_key' => null];
    }

    private static function geminiAnswer(string $json): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $json]]]]], 'usageMetadata' => ['totalTokenCount' => 42]];
    }

    private static function geminiError(string $message, int $status): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response(['error' => ['message' => $message]], $status);
    }

    /** คีย์ที่ถูกส่งไปกับแต่ละ request ตามลำดับ (gemini = header, groq = Bearer) */
    private static function sentKeys(): array
    {
        return Http::recorded()->map(function ($pair) {
            /** @var Request $request */
            $request = $pair[0];

            return $request->header('x-goog-api-key')[0]
                ?? str_replace('Bearer ', '', $request->header('Authorization')[0] ?? '');
        })->values()->all();
    }

    public function test_empty_input_never_calls_ai(): void
    {
        Http::fake();

        $ai = $this->service([self::key('key-second')]);

        $this->assertNull($ai->parseBirthDateWithAI(''));
        $this->assertNull($ai->parseBirthDateWithAI("   \n "));
        Http::assertNothingSent();
    }

    public function test_high_demand_retries_once_on_a_different_key(): void
    {
        Http::fake([
            self::GEMINI => Http::sequence()
                ->pushResponse(self::geminiError('This model is currently experiencing high demand. Spikes in demand are usually temporary.', 503))
                ->push(self::geminiAnswer('{"date": "1990-08-15"}')),
        ]);

        // คีย์ที่เพิ่งล่ม (key-first) อยู่ใน Pool ด้วย — ต้องถูกข้าม
        $ai = $this->service([self::key('key-first'), self::key('key-second')]);

        $this->assertSame('1990-08-15', $ai->parseBirthDateWithAI('เกิด 15 สิงหา ปี 33'));
        $this->assertSame(['key-first', 'key-second'], self::sentKeys());
    }

    public function test_retry_prefers_another_provider_or_model(): void
    {
        Http::fake([
            self::GEMINI => self::geminiError('The service is currently unavailable.', 503),
            self::GROQ => Http::response([
                'choices' => [['message' => ['content' => '{"date": "1992-09-23"}']]],
                'usage' => ['total_tokens' => 30],
            ]),
        ]);

        $ai = $this->service([
            self::key('key-first'),
            self::key('key-same-model'),
            self::key('key-groq', 'groq', 'llama-3.3-70b-versatile'),
        ]);

        $this->assertSame('1992-09-23', $ai->parseBirthDateWithAI('23 กย 2535'));
        $this->assertSame(['key-first', 'key-groq'], self::sentKeys());
    }

    public function test_timeout_on_both_keys_gives_up_after_one_retry_and_warns(): void
    {
        Log::spy();

        // ต่อไม่ติดไม่ถูกบันทึกใน Http::recorded() — นับคีย์เอง
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = $request->header('x-goog-api-key')[0] ?? null;

            return HttpFactory::failedConnection('cURL error 28: Operation timed out after 8001 milliseconds with 0 bytes received')($request);
        });

        $ai = $this->service([self::key('key-second'), self::key('key-third')]);

        $this->assertNull($ai->parseBirthDateWithAI('เกิด 15 สิงหา ปี 33'));

        // รอบแรก + ลองใหม่ 1 ครั้ง — ห้ามวนทั้ง Pool (คนรอคำตอบอยู่)
        $this->assertSame(['key-first', 'key-second'], $sent);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context = []) => str_contains($message, 'parseBirthDateWithAI ล้มเหลว')
                && ($context['retried'] ?? null) === true
                && str_contains($context['error'] ?? '', 'cURL error 28'))
            ->once();
    }

    public function test_permanent_error_does_not_retry_but_still_warns(): void
    {
        Log::spy();
        Http::fake([self::GEMINI => self::geminiError('API key not valid. Please pass a valid API key.', 400)]);

        $ai = $this->service([self::key('key-second')]);

        $this->assertNull($ai->parseBirthDateWithAI('เกิด 15 สิงหา ปี 33'));
        $this->assertSame(['key-first'], self::sentKeys());

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context = []) => str_contains($message, 'parseBirthDateWithAI ล้มเหลว')
                && ($context['retried'] ?? null) === false)
            ->once();
    }

    public function test_no_spare_key_in_pool_gives_up_without_retry(): void
    {
        Http::fake([self::GEMINI => self::geminiError('This model is currently experiencing high demand.', 503)]);

        // Pool มีแต่คีย์ที่เพิ่งล่ม
        $ai = $this->service([self::key('key-first')]);

        $this->assertNull($ai->parseBirthDateWithAI('เกิด 15 สิงหา ปี 33'));
        $this->assertSame(['key-first'], self::sentKeys());
    }

    /**
     * ฝั่งผู้เรียก: ข้อความไม่มีตัวเลข = reconcileAiBirthDate ตีตกแน่นอน ⇒ ไม่ต้องถาม AI
     * ข้อความจริงจาก log prod: "ค่ะ" "คะ" "ค่า"
     */
    public function test_caller_skips_ai_when_text_has_no_digits(): void
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldNotReceive('parseBirthDateWithAI');

        $conv = $this->conversation($ai);

        foreach (['ค่ะ', 'คะ', 'ค่า', 'สวัสดีค่ะ แม่หมอ'] as $text) {
            $this->assertNull($conv->parse($text), $text);
        }
    }

    public function test_caller_still_asks_ai_when_regex_misses_but_digits_exist(): void
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('parseBirthDateWithAI')->once()->andReturn(null);

        $this->assertNull($this->conversation($ai)->parse('ผมเกิด สิงหา ปี35 ครับ'));
    }

    /** FortuneConversationService ที่ข้าม constructor และใช้ AI ที่ส่งเข้ามา */
    private function conversation(FortuneAIService $ai): object
    {
        return new class($ai) extends FortuneConversationService
        {
            public function __construct(private FortuneAIService $birthAi) {}

            protected function birthDateAiService(): FortuneAIService
            {
                return $this->birthAi;
            }

            public function parse(string $text): ?string
            {
                return $this->parseBirthDateCore($text);
            }
        };
    }
}
