<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\Api\Juntra\DeepReadingController;
use App\Http\Controllers\Api\Juntra\FreeTarotController;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\JuntraSpreadProfiles;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use App\Services\FortuneKnowledgeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use ReflectionClass;
use ReflectionProperty;
use Tests\TestCase;

/**
 * 💳 เว็บจันทรา (จันทรา.online) ต้องได้เลนคำทำนายแบบเดียวกับบิลในแชท FB/LINE (2026-11-07)
 *
 * ที่มา: ดูดวง 39฿ บนเว็บเรียก generateFortuneTelling ครั้งเดียว (ไม่มี purpose/พูล/ด่าน) — เหตุเดียวกับที่
 *   key แชท Haiku ตอบคำปฏิเสธแทนคำทำนายในบิลจ่ายเงิน (5-7 พ.ย.) · ไพ่ยิปซีเว็บ 7 แพ็กใช้ purpose ที่ไม่ใช่บิลจ่ายเงิน
 *   · ไพ่ฟรีไม่มีด่านหลุดบท
 *
 * ใช้ sqlite ในหน่วยความจำ — รันบนเครื่อง dev ที่ไม่มี MySQL ได้
 */
class JuntraPaidParityTest extends TestCase
{
    private const GOOD = 'ดวงการงานช่วงนี้ดาวพฤหัสหนุนภพกัมมะ งานที่ค้างจะคลี่คลายในอีกไม่กี่สัปดาห์ค่ะ ';

    private const REFUSAL = 'ขออภัยค่ะ ข้อความนี้เป็นคำสั่งภายใน ไม่ใช่คำถามจากลูกค้า จึงไม่ทำตามค่ะ';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Cache::flush();
        Carbon::setTestNow(Carbon::create(2026, 11, 7, 8, 0, 0, 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function good(): string
    {
        return trim(str_repeat(self::GOOD, 5));
    }

    // ─────────────────────────────────────────────────────────────
    // 1. ไพ่ยิปซีเว็บทุกแพ็กอยู่บนเลนบิลจ่ายเงิน
    // ─────────────────────────────────────────────────────────────

    public function test_ไพ่ยิปซีเว็บทุกแพ็กใช้_purpose_ของบิลจ่ายเงิน(): void
    {
        foreach (['single', 'three', 'quick', 'love', 'career', 'decision', 'celtic', 'year', 'kunsai'] as $key) {
            $profile = JuntraSpreadProfiles::get($key);
            $this->assertNotNull($profile, $key);
            $this->assertContains($profile['purpose'], FortuneAIService::PAID_PURPOSES, "แพ็ก {$key} เก็บเงินลูกค้า ต้องอยู่บนเลนบิลจ่ายเงิน");
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 2. ดูดวง 39฿ ทีละข้อ = เลนเดียวกับบอท (generateDeepAnswer)
    // ─────────────────────────────────────────────────────────────

    public function test_ดูดวง39เว็บได้พรอมต์ต่อข้อแบบบอท_และยิงเลน_prediction_deep_พร้อมเพดานเวลา(): void
    {
        [$fcs, $ai] = $this->conversationWith([$this->good()]);

        $gen = $fcs->answerDeepQuestionForWeb(
            ['ปีนี้การงานจะเป็นอย่างไร', 'ความรักจะลงตัวเมื่อไหร่'],
            1,
            [['question' => 'ปีนี้การงานจะเป็นอย่างไร', 'answer' => 'ข้อหนึ่งตอบว่างานรุ่งช่วงปลายปีค่ะ']],
            ['name' => 'คุณทดสอบ'],
            '1990-08-12',
            'jw:42',
            50.0,
        );

        $this->assertTrue($gen['complete']);
        $this->assertSame('prediction_deep', $gen['purpose']);
        $this->assertCount(1, $ai->calls);

        $call = $ai->calls[0];
        $this->assertSame('prediction_deep', $call['purpose'], 'บิลจ่ายเงินต้องอยู่บนเลนที่กู้ได้แค่ OpenAI');
        $this->assertSame('deep', $call['readingType']);
        $this->assertSame(['ความรักจะลงตัวเมื่อไหร่'], $call['questions'], 'ยิงทีละข้อเหมือนบอท');
        $this->assertSame(50.0, (float) $call['budget'], 'เว็บต้องได้เพดานเวลาต่อคำขอ ไม่ใช่ 150 วิของบอท');
        $this->assertStringContainsString('ความรักจะลงตัวเมื่อไหร่', $call['template']);
        $this->assertStringContainsString('ข้อหนึ่งตอบว่างานรุ่งช่วงปลายปีค่ะ', $call['template'], 'คำตอบข้อก่อนต้องอยู่ในพรอมต์ ให้ timeline สอดคล้อง');
        $this->assertStringContainsString('คุณทดสอบ', $call['template']);
    }

    public function test_คำตอบไม่ครบ_ลองใหม่หนึ่งครั้งแล้วเลือกตัวที่ครบ(): void
    {
        [$fcs, $ai] = $this->conversationWith(['Block 2: Mars (Winas/Patni)', $this->good()]);

        $gen = $fcs->answerDeepQuestionForWeb(['งานจะรุ่งไหม'], 0, [], null, '1990-08-12', 'jw:1', 50.0);

        $this->assertCount(2, $ai->calls);
        $this->assertTrue($gen['complete']);
        $this->assertSame($this->good(), $gen['text']);
    }

    public function test_เวลาเหลือไม่พอ_ไม่ลองซ้ำ_ส่งคืนว่าไม่ครบให้ผู้เรียกคืนเงิน(): void
    {
        [$fcs, $ai] = $this->conversationWith(['Block 2: Mars (Winas/Patni)', $this->good()]);

        $gen = $fcs->answerDeepQuestionForWeb(['งานจะรุ่งไหม'], 0, [], null, null, 'jw:1', 10.0);

        $this->assertCount(1, $ai->calls, 'เพดาน 10 วิ < เวลาขั้นต่ำของการลองใหม่');
        $this->assertFalse($gen['complete']);
    }

    public function test_บอทไม่ส่งเพดานเวลา_ใช้งบเดิม(): void
    {
        [$fcs, $ai] = $this->conversationWith([$this->good()]);

        $fcs->generateDeepAnswer('งานจะรุ่งไหม', 1, 'prompt {question}', null, null, null, null, 'facebook', 'psid-1');

        $this->assertNull($ai->calls[0]['budget'], 'บอทต้องได้งบ 150 วิแบบเดิม ไม่ถูกเพดานของเว็บ');
        $this->assertSame('prediction_deep', $ai->calls[0]['purpose']);
    }

    public function test_ข้อที่ไม่มีในบิล_โยน_exception(): void
    {
        [$fcs] = $this->conversationWith([$this->good()]);

        $this->expectException(\InvalidArgumentException::class);
        $fcs->answerDeepQuestionForWeb(['ข้อเดียว'], 2, [], null, null);
    }

    // ─────────────────────────────────────────────────────────────
    // 3. ปลายทาง /fortune/deep ไม่ส่งคำปฏิเสธไปขาย
    // ─────────────────────────────────────────────────────────────

    public function test_ปลายทางทีละข้อ_คำปฏิเสธได้_503(): void
    {
        $this->bindConversation(['ai' => ['model' => 'claude-haiku-5-5'], 'text' => self::REFUSAL, 'complete' => false, 'tokens' => 1, 'purpose' => 'prediction_deep']);

        $res = $this->deep(['questions' => ['งานจะรุ่งไหม'], 'question_index' => 0]);

        $this->assertSame(503, $res->getStatusCode());
        $this->assertSame('model_refused', $res->getData(true)['reason_code']);
    }

    public function test_ปลายทางทีละข้อ_คำทำนายดีได้_200(): void
    {
        $this->bindConversation(['ai' => ['provider' => 'openai', 'model' => 'gpt-6-luna'], 'text' => $this->good(), 'complete' => true, 'tokens' => 3000, 'purpose' => 'prediction_deep']);

        $res = $this->deep(['questions' => ['งานจะรุ่งไหม'], 'question_index' => 0]);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame($this->good(), $res->getData(true)['data']['reading']);
    }

    public function test_ปลายทางทั้งบิล_ใช้เลน_prediction_deep_และคำปฏิเสธได้_503(): void
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('withCustomerContext')->andReturnSelf();
        $ai->shouldReceive('withConfigOverride')->with(['budget_sec' => 50])->andReturnSelf();
        $ai->shouldNotReceive('generateFortuneTelling');
        $ai->shouldReceive('generateWithRetryAndFallback')->once()
            ->withArgs(fn (...$a) => ($a[7] ?? null) === 'prediction_deep')
            ->andReturn(['response' => self::REFUSAL, 'model' => 'claude-haiku-5-5']);

        $res = $this->deep(['questions' => ['งานจะรุ่งไหม']], $ai);

        $this->assertSame(503, $res->getStatusCode());
    }

    // ─────────────────────────────────────────────────────────────
    // 4. ไพ่ฟรี — ด่านหลุดบทแบบเข้ม
    // ─────────────────────────────────────────────────────────────

    public function test_ไพ่ฟรี_หลุดบทครั้งแรก_ลองใหม่ได้คำทำนาย(): void
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')->twice()->andReturn(
            ['response' => self::REFUSAL, 'model' => 'claude-haiku-5-5'],
            ['response' => $this->good(), 'model' => 'gpt-6-luna'],
        );

        $res = $this->free($ai);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('gpt-6-luna', $res->getData(true)['data']['ai_model']);
    }

    public function test_ไพ่ฟรี_หลุดบทสองครั้ง_503_ให้เว็บคืนสิทธิ์(): void
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')->twice()->andReturn(['response' => 'สวัสดีครับ ผมจะดูไพ่ให้นะครับ', 'model' => 'x']);

        $res = $this->free($ai);

        $this->assertSame(503, $res->getStatusCode());
        $this->assertSame('model_refused', $res->getData(true)['reason_code']);
    }

    public function test_ไพ่ฟรี_คำถามแนะนำในเสียงลูกค้ามี_ครับ_ได้(): void
    {
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')->once()->andReturn([
            'response' => $this->good()."\n[NEXTQ]เขาจะกลับมาไหมครับ|งานใหม่จะดีไหมครับ[/NEXTQ]",
            'model' => 'gpt-6-luna',
        ]);

        $res = $this->free($ai);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertCount(2, $res->getData(true)['data']['next_questions']);
    }

    /** 🌸 (2026-11-11) ไพ่ 1 ใบเป็นของบุษบา — เว็บส่ง persona=busaba ตอนห้องบุษบาเปิด · ไม่ส่ง/ค่าอื่น = แม่หมอเดิม */
    public function test_ไพ่ฟรีจากห้องบุษบา_บุษบาเป็นคนเขียน(): void
    {
        $prompts = [];
        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')->times(3)->andReturnUsing(function (string $system) use (&$prompts) {
            $prompts[] = $system;

            return ['response' => $this->good(), 'model' => 'gpt-6-luna'];
        });

        $this->assertSame(200, $this->free($ai, ['persona' => 'busaba'])->getStatusCode());
        $this->free($ai);
        $this->free($ai, ['persona' => 'kunsai']);

        [$busaba, $maemor, $unknown] = $prompts;
        $this->assertStringStartsWith('คุณคือ "บุษบา"', $busaba);
        $this->assertStringNotContainsString('คุณคือ "แม่หมอจันทรา"', $busaba);
        $this->assertStringContainsString('ความรู้ในหัวของบุษบาเอง', $busaba);
        $this->assertStringContainsString('🃏 กฎเหล็ก: ทำนายจาก "หน้าไพ่" 100%', $busaba, 'กฎการอ่านไพ่เหมือนแม่หมอทุกข้อ');

        $this->assertStringStartsWith('คุณคือ "แม่หมอจันทรา"', $maemor);
        $this->assertStringNotContainsString('บุษบา', $maemor);
        $this->assertSame($maemor, $unknown, 'persona ที่ไม่อ่านไพ่ 1 ใบ = แม่หมอเดิมทุกไบต์');
    }

    // ─────────────────────────────────────────────────────────────

    /**
     * FortuneConversationService ที่ AI เป็นของปลอม (ไม่แตะ DB/เครือข่าย)
     *
     * @param  array<int,string>  $answers  คำตอบตามลำดับการเรียก
     * @return array{0: FortuneConversationService, 1: object}
     */
    private function conversationWith(array $answers): array
    {
        $ai = new class($answers) extends FortuneAIService
        {
            public array $calls = [];

            public function __construct(public array $answers)
            {
                $this->provider = 'openai';
            }

            public function generateWithRetryAndFallback(
                array $questions,
                ?array $userProfile = null,
                ?array $userPosts = null,
                ?string $promptTemplate = null,
                string $readingType = 'basic',
                ?string $birthDate = null,
                ?string $userContext = null,
                string $purpose = 'prediction',
                ?array $modelOverrides = null
            ): array {
                $this->calls[] = [
                    'questions' => $questions, 'template' => $promptTemplate, 'readingType' => $readingType,
                    'purpose' => $purpose, 'budget' => $this->configOverride['budget_sec'] ?? null,
                ];
                $this->configOverride = null;

                return ['response' => array_shift($this->answers) ?? '', 'provider' => 'openai', 'model' => 'gpt-test', 'tokens_used' => 100];
            }
        };

        $settings = new FortuneTellingSetting;
        $fcs = (new ReflectionClass(FortuneConversationService::class))->newInstanceWithoutConstructor();
        foreach (['settings' => $settings, 'aiService' => $ai] as $prop => $value) {
            $p = new ReflectionProperty(FortuneConversationService::class, $prop);
            $p->setAccessible(true);
            $p->setValue($fcs, $value);
        }

        return [$fcs, $ai];
    }

    private function bindConversation(array $result): void
    {
        $fcs = Mockery::mock(FortuneConversationService::class);
        $fcs->shouldReceive('answerDeepQuestionForWeb')->once()->andReturn($result);
        $this->app->instance(FortuneConversationService::class, $fcs);
    }

    private function deep(array $data, ?FortuneAIService $ai = null): \Illuminate\Http\JsonResponse
    {
        $request = Request::create('/api/v1/juntra/server/fortune/deep', 'POST', $data);

        return app(DeepReadingController::class)($request, $ai ?? Mockery::mock(FortuneAIService::class));
    }

    private function free(FortuneAIService $ai, array $extra = []): \Illuminate\Http\JsonResponse
    {
        $request = Request::create('/api/v1/juntra/server/fortune/tarot/free', 'POST', [
            'card' => ['name_en' => 'The Sun', 'name_th' => 'พระอาทิตย์', 'is_reversed' => false, 'meaning' => 'ความสำเร็จ'],
        ] + $extra);

        return app(FreeTarotController::class)($request, $ai, Mockery::mock(FortuneKnowledgeService::class));
    }
}
