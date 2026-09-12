<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use App\Support\ThaiProvinces;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🗺️ บิลแรกของดูดวง 39 ต้องผูกลัคนาด้วยจังหวัดเกิดที่ลูกค้าบอกไว้ (2026-09-11)
 *
 * ที่มา: เลน 39 ถาม "เกิดกี่โมง ที่จังหวัดไหน" ก่อนเปิดไพ่ (deep_birthtime_pending)
 *   แล้วเก็บลง fortune_readings.birth_province + ทวนกับลูกค้าว่า "จดไว้ใช้ผูกดวงแล้ว"
 *   แต่คำทำนายบิลแรก (processPaymentConfirmed) อ่านจังหวัดจาก *ข้อความคำถาม* อย่างเดียว
 *   ทั้งผังท้ายพรอมต์ (FortuneAIService::buildPrompt) และช่อง {transit_info}
 *   — คำถามของเลน 39 เป็นคำถามพื้นดวงที่ระบบใส่ให้เอง ไม่มีชื่อจังหวัด
 *   ⇒ ลัคนาคำนวณด้วยพิกัดกรุงเทพทุกบิล ส่วนคุยต่อ (ProSession) กับ Celtic ใช้จังหวัดจริง
 *
 * เทสต์ชุดนี้ล็อก:
 *   1. จังหวัดที่เก็บไว้ไปถึงผังทั้ง 2 ทางในพรอมต์ใบเดียว และทุกบล็อกดาวจรยังตรงกันทุกตัวอักษร
 *   2. จังหวัดที่เก็บไว้ชนะชื่อจังหวัดที่บังเอิญอยู่ในคำถาม (เช่น "ย้ายไปทำงานภูเก็ต")
 *   3. จังหวัดบน FortuneAIService เป็น one-shot — ไม่รั่วไปผูกดวงลูกค้าคนถัดไป แม้ call ล้ม
 *   4. processPaymentConfirmed ส่งค่าเดียวกันให้ทั้ง 2 ทาง ทั้ง call แรกและ retry
 *
 * ไม่แตะ DB — สร้าง service ด้วย newInstanceWithoutConstructor (constructor อ่าน settings จาก DB)
 */
class Deep39StoredBirthProvinceTest extends TestCase
{
    /**
     * คำถามพื้นดวงที่เลน 39 ใส่ให้เอง (ข้อความเดียวกับใน FortuneConversationService — ฝังไว้ 3 จุด)
     */
    private const GENERAL_QUESTION = 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา — ภาพรวมชีวิตช่วงนี้ ทั้งนิสัยพื้นฐาน ความรัก '
        .'การงาน การเงิน สุขภาพ โชคลาภ และสิ่งที่ควรระวัง พร้อมคำแนะนำจากแม่หมอ';

    /** ลองจิจูดห่างกรุงเทพ ~4.3° (~17 นาที LST) — มีช่วงเวลาที่ลัคนาข้ามราศีจริง */
    private const PROVINCE = 'อุบลราชธานี';

    /** ทรงเดียวกับ template ของแอดมินบน prod: {transit_info} 2 ที่ และไม่มี {birth_date_section} */
    private const PROD_SHAPED_TEMPLATE = "5. ผูกข้อมูลเฉพาะของลูกค้า: {planet_positions}, {transit_info}\n"
        ."{planet_positions}\n{transit_info}\nคำถาม: {question}";

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 9, 11, 10, 0, 0, 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // 1. จังหวัดที่เก็บไว้ถึงผังทั้ง 2 ทาง
    // ─────────────────────────────────────────────────────────────

    public function test_stored_province_drives_both_chart_blocks_of_the_first_paid_prompt(): void
    {
        $this->assertNull(
            ThaiProvinces::resolve(self::GENERAL_QUESTION),
            'เงื่อนไขของบั๊ก: คำถามพื้นดวงของเลน 39 ไม่มีชื่อจังหวัดให้อ่าน'
        );
        $birthDate = $this->birthTimeWhereLagnaDependsOn(self::PROVINCE, [null]);

        $final = $this->firstPaidPrompt($birthDate, self::GENERAL_QUESTION, self::PROVINCE);
        $sections = $this->todayTransitSections($final);

        $this->assertCount(3, $sections, '{transit_info} 2 ที่ + ผังที่ต่อท้าย 1');
        $this->assertCount(1, array_unique($sections), 'ดาวจรทุกบล็อกในพรอมต์ใบเดียวต้องตรงกันทุกตัวอักษร');

        // ผังท้ายพรอมต์บอกจังหวัดจริง — ไม่ใช่ค่ากลางกรุงเทพ
        $this->assertStringContainsString('เกิดที่ จ.'.self::PROVINCE, $final);
        $this->assertStringNotContainsString('ไม่ทราบจังหวัดเกิด ใช้พิกัดกรุงเทพฯ', $final);

        // ภพของดาวจร = นับจากลัคนาของจังหวัดนั้น และต่างจากที่ได้ถ้าใช้พิกัดกรุงเทพ
        $this->assertSame($this->expectedTodaySection($birthDate, self::PROVINCE), $sections[0]);
        $this->assertNotSame(
            $this->expectedTodaySection($birthDate, null),
            $sections[0],
            'ถ้ายังตรงกับผังกรุงเทพ = จังหวัดที่เก็บไว้ไม่ถูกใช้ (บั๊กเดิม)'
        );
    }

    public function test_without_a_stored_province_the_prompt_is_unchanged(): void
    {
        $birthDate = $this->birthTimeWhereLagnaDependsOn(self::PROVINCE, [null]);

        // ไม่รู้จังหวัด = พฤติกรรมเดิมทุกตัวอักษร (ผู้เรียกเก่าที่ไม่ส่งจังหวัด)
        $this->assertSame(
            $this->firstPaidPrompt($birthDate, self::GENERAL_QUESTION, null, legacyCall: true),
            $this->firstPaidPrompt($birthDate, self::GENERAL_QUESTION, null)
        );

        $final = $this->firstPaidPrompt($birthDate, self::GENERAL_QUESTION, null);
        $this->assertStringContainsString('ไม่ทราบจังหวัดเกิด ใช้พิกัดกรุงเทพฯ', $final, 'ไม่รู้จังหวัด = บอกตามตรง');
        $this->assertSame($this->expectedTodaySection($birthDate, null), $this->todayTransitSections($final)[0]);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. จังหวัดที่เก็บไว้ชนะข้อความ
    // ─────────────────────────────────────────────────────────────

    public function test_stored_province_outranks_a_province_mentioned_in_the_question(): void
    {
        // คำถามพูดถึงภูเก็ตในฐานะ "ที่ทำงาน" — resolve() ไม่ต้องมีคำว่าเกิด จึงอ่านเป็นจังหวัดได้
        $question = 'อยากย้ายไปทำงานภูเก็ต จะได้ไปไหมคะ';
        $this->assertSame('ภูเก็ต', ThaiProvinces::resolve($question));

        $birthDate = $this->birthTimeWhereLagnaDependsOn(self::PROVINCE, [null, 'ภูเก็ต']);
        $final = $this->firstPaidPrompt($birthDate, $question, self::PROVINCE);
        $sections = $this->todayTransitSections($final);

        $this->assertCount(1, array_unique($sections), 'ทั้ง 2 ทางต้องใช้กฎลำดับเดียวกัน');
        $this->assertStringContainsString('เกิดที่ จ.'.self::PROVINCE, $final);
        $this->assertStringNotContainsString('เกิดที่ จ.ภูเก็ต', $final);
        $this->assertSame($this->expectedTodaySection($birthDate, self::PROVINCE), $sections[0]);
    }

    public function test_for_chart_precedence_rule(): void
    {
        $this->assertSame('เชียงใหม่', ThaiProvinces::forChart('เชียงใหม่', 'อยากย้ายไปทำงานภูเก็ต'), 'รู้แล้ว = ชนะข้อความ');
        $this->assertSame('ขอนแก่น', ThaiProvinces::forChart(null, 'หนูเกิดที่ขอนแก่นค่ะ'), 'ยังไม่รู้ = อ่านจากข้อความแบบเดิม');
        $this->assertSame('ขอนแก่น', ThaiProvinces::forChart('ดาวอังคาร', 'หนูเกิดที่ขอนแก่นค่ะ'), 'ชื่อที่ไม่รู้จัก = ถือว่ายังไม่รู้');
        $this->assertSame('โคราช', ThaiProvinces::forChart('โคราช'), 'ชื่อเรียกอื่นใช้ได้ (formatPersonBlock แปลงเป็นชื่อทางการเอง)');
        $this->assertNull(ThaiProvinces::forChart(null, ''));
        $this->assertNull(ThaiProvinces::forChart('', 'ไปเลยค่ะ'));
    }

    // ─────────────────────────────────────────────────────────────
    // 3. one-shot — instance เดียวใช้ร่วมกับลูกค้าทุกคน
    // ─────────────────────────────────────────────────────────────

    public function test_birth_province_is_one_shot_and_never_leaks_to_the_next_customer(): void
    {
        $birthDate = $this->birthTimeWhereLagnaDependsOn(self::PROVINCE, [null]);
        $ai = $this->recordingAiService();

        $ai->withBirthProvince(self::PROVINCE)
            ->generateWithRetryAndFallback(['ถามเรื่องงาน'], null, null, '{questions}', 'deep', $birthDate);
        // ลูกค้าคนถัดไปบน instance เดิม — ไม่ได้ตั้งจังหวัด
        $ai->generateWithRetryAndFallback(['ถามเรื่องงาน'], null, null, '{questions}', 'deep', $birthDate);

        $this->assertStringContainsString('เกิดที่ จ.'.self::PROVINCE, $ai->prompts[0]);
        $this->assertStringContainsString('ไม่ทราบจังหวัดเกิด ใช้พิกัดกรุงเทพฯ', $ai->prompts[1], 'จังหวัดของคนก่อนห้ามค้าง');
    }

    public function test_a_failed_call_does_not_leave_the_province_behind(): void
    {
        $birthDate = $this->birthTimeWhereLagnaDependsOn(self::PROVINCE, [null]);
        $ai = $this->recordingAiService();
        $ai->failNext = true;

        try {
            $ai->withBirthProvince(self::PROVINCE)
                ->generateWithRetryAndFallback(['ถามเรื่องงาน'], null, null, '{questions}', 'deep', $birthDate);
            $this->fail('call แรกต้องล้ม');
        } catch (\RuntimeException $e) {
            // ตั้งใจให้ล้ม — key pool หมด / provider ล่ม
        }

        $ai->generateWithRetryAndFallback(['ถามเรื่องงาน'], null, null, '{questions}', 'deep', $birthDate);

        $this->assertCount(1, $ai->prompts);
        $this->assertStringContainsString('ไม่ทราบจังหวัดเกิด ใช้พิกัดกรุงเทพฯ', $ai->prompts[0]);
    }

    public function test_unknown_province_name_is_ignored(): void
    {
        $birthDate = $this->birthTimeWhereLagnaDependsOn(self::PROVINCE, [null]);
        $ai = $this->recordingAiService();

        $ai->withBirthProvince('ดาวอังคาร')
            ->generateWithRetryAndFallback(['ถามเรื่องงาน'], null, null, '{questions}', 'deep', $birthDate);

        $this->assertStringContainsString('ไม่ทราบจังหวัดเกิด ใช้พิกัดกรุงเทพฯ', $ai->prompts[0]);
    }

    // ─────────────────────────────────────────────────────────────
    // 4. ต่อสายใน processPaymentConfirmed (เมธอดแตะ DB ทั้งก้อน — ตรวจที่ตัวโค้ด)
    // ─────────────────────────────────────────────────────────────

    public function test_process_payment_confirmed_feeds_the_same_province_to_both_blocks(): void
    {
        $body = $this->methodSource(FortuneConversationService::class, 'processPaymentConfirmed');

        $this->assertMatchesRegularExpression(
            '/\$birthProvince\s*=\s*\$reading->birthProvinceIfKnown\(\)/',
            $body,
            'ต้องอ่านจังหวัดที่เก็บไว้ครั้งเดียว แล้วใช้ค่านั้นทั้ง 2 ทาง'
        );
        // 🕛 (2026-09-12) + ค่า "ต่อบิล" ($chartInputs) ตามหลังจังหวัด — ทุกข้อใช้เวลา/จังหวัดชุดเดียวกัน
        $this->assertMatchesRegularExpression(
            '/buildPerQuestionDeepPrompt\([^;]*\$birthProvince\s*,\s*\$chartInputs\s*\)/s',
            $body,
            'ช่อง {transit_info}/{planet_positions} ต้องได้จังหวัด + ค่า "ต่อบิล" เดียวกัน'
        );
        $this->assertMatchesRegularExpression(
            '/\$chartInputs\s*=\s*\$birthDate\s*\?\s*\$this->deepChartImageInputs\(/',
            $body,
            'ค่า "ต่อบิล" ต้อง resolve ครั้งเดียว (ตัวเดียวกับรูปผัง)'
        );

        // ทุก AI call ที่ใช้พรอมต์ต่อคำถาม ต้องตั้งจังหวัดใหม่หลัง call ก่อนหน้า (one-shot ล้างทุก call)
        $chunks = explode('generateWithRetryAndFallback(', $body);
        $calls = 0;
        for ($i = 1; $i < count($chunks); $i++) {
            if (! str_contains((string) strstr($chunks[$i], ');', true), '$perQuestionPrompt')) {
                continue;
            }
            $calls++;
            $this->assertStringContainsString(
                '->withBirthProvince($birthProvince)',
                $chunks[$i - 1],
                "AI call ที่ {$calls} ของพรอมต์ต่อคำถามไม่ได้ตั้งจังหวัด = ผังท้ายพรอมต์กลับไปใช้พิกัดกรุงเทพ"
            );
            $this->assertStringContainsString(
                '->withChartInputs($chartInputs)',
                $chunks[$i - 1],
                "AI call ที่ {$calls} ไม่ได้ล็อกค่า \"ต่อบิล\" = ผังท้ายพรอมต์กลับไปอ่านเวลาจากข้อความข้อนี้"
            );
        }

        $this->assertGreaterThanOrEqual(2, $calls, 'call แรก + retry ตอนคำทำนายไม่ครบ');
    }

    // ─────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * พรอมต์ใบจริงของบิลแรก — เดินเส้นเดียวกับ processPaymentConfirmed:
     * buildPerQuestionDeepPrompt() สร้างจาก template → FortuneAIService::buildPrompt() ต่อผังดวงท้าย
     *
     * @param  bool  $legacyCall  เรียกแบบก่อนแก้ (ไม่ส่งจังหวัดทั้ง 2 ทาง) — ใช้เทียบว่าไม่มีอะไรเปลี่ยน
     */
    private function firstPaidPrompt(string $birthDate, string $question, ?string $province, bool $legacyCall = false): string
    {
        $args = [['name' => 'ทดสอบ', 'gender' => 'female'], $question, 1, 1, $birthDate, [], null];
        if (! $legacyCall) {
            $args[] = $province;
        }
        $perQuestion = $this->invoke($this->conversation(self::PROD_SHAPED_TEMPLATE), 'buildPerQuestionDeepPrompt', $args);

        $ai = (new ReflectionClass(FortuneAIService::class))->newInstanceWithoutConstructor();
        if (! $legacyCall) {
            $ai->withBirthProvince($province);
        }

        return $this->invoke($ai, 'buildPrompt', [[$question], ['name' => 'ทดสอบ'], null, $perQuestion, $birthDate]);
    }

    /** บล็อก "ดาวจรวันนี้" ที่ถูกต้องสำหรับวันเกิด+จังหวัดนี้ — คำนวณตรงจาก ThaiAstrologyService */
    private function expectedTodaySection(string $birthDate, ?string $province): string
    {
        $block = (new ThaiAstrologyService)->formatTransitOutlookBlock($birthDate, null, $province, Carbon::now('Asia/Bangkok'));

        return $this->todayTransitSections($block)[0];
    }

    /**
     * ไล่หาเวลาเกิด (วันที่ 15 พ.ค. 2533) ที่ลัคนาของ $province ต่างราศีกับของทุกที่ใน $others
     * — ไม่ฮาร์ดโค้ดเวลา เพราะขึ้นกับสูตรลัคนา/ตำแหน่งดาวจริง ถ้าเครื่องคำนวณปรับความแม่น เทสต์ต้องไม่พังตาม
     * ถ้าลัคนาเท่ากันทุกที่ เทสต์จะผ่านได้แม้จังหวัดไม่ถูกใช้เลย ⇒ ต้องหาเวลาที่ "จังหวัดมีผลจริง"
     *
     * @param  array<int, string|null>  $others  ชื่อจังหวัด · null = ไม่ทราบจังหวัด (พิกัดกลางที่ผังใช้จริง)
     */
    private function birthTimeWhereLagnaDependsOn(string $province, array $others): string
    {
        $astro = new ThaiAstrologyService;
        $lagnaAt = function (Carbon $dt, ?string $p) use ($astro): ?string {
            $c = $p === null
                ? ['lat' => ThaiAstrologyService::DEFAULT_LAT, 'lon' => ThaiAstrologyService::DEFAULT_LON]
                : ThaiProvinces::coords($p);

            return $astro->siderealLagna($dt, $c['lat'], $c['lon']);
        };

        for ($minutes = 0; $minutes < 24 * 60; $minutes += 5) {
            $ymd = sprintf('1990-05-15 %02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $dt = Carbon::parse($ymd);
            $target = $lagnaAt($dt, $province);
            if ($target === null) {
                continue;
            }
            foreach ($others as $other) {
                if ($lagnaAt($dt, $other) === $target) {
                    continue 2;
                }
            }

            return $ymd;
        }

        $this->fail("ไม่พบเวลาเกิดที่ลัคนาของ {$province} ต่างจาก ".implode('/', $others));
    }

    /**
     * FortuneAIService ที่ไม่ยิง AI จริง — บันทึกพรอมต์ที่ buildPrompt() สร้างแทน
     * แต่ยังวิ่งผ่าน generateWithRetryAndFallback() ตัวจริง (ตัวที่มี finally ล้าง one-shot)
     */
    private function recordingAiService(): FortuneAIService
    {
        return new class extends FortuneAIService
        {
            /** @var string[] */
            public array $prompts = [];

            public bool $failNext = false;

            public function __construct()
            {
                // ข้าม constructor จริง — อ่าน settings/key pool จาก DB
            }

            protected function generateWithRetryAndFallbackInner(
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
                if ($this->failNext) {
                    $this->failNext = false;
                    throw new \RuntimeException('provider ล่มทุกตัว');
                }

                $this->prompts[] = $this->buildPrompt($questions, $userProfile, $userPosts, $promptTemplate, $birthDate);

                return ['response' => 'ok', 'tokens_used' => 0, 'provider' => 'fake', 'model' => 'fake'];
            }
        };
    }

    /** บล็อก "🔭 ดาวจรวันนี้" ทุกก้อนในข้อความ — หัวบล็อก + บรรทัดย่อหน้าที่ตามมา */
    private function todayTransitSections(string $text): array
    {
        $sections = [];
        $lines = explode("\n", $text);

        foreach ($lines as $i => $line) {
            if (! str_starts_with($line, '🔭 ดาวจรวันนี้')) {
                continue;
            }
            $block = [$line];
            for ($j = $i + 1; $j < count($lines) && preg_match('/^\s+\S/u', $lines[$j]); $j++) {
                $block[] = $lines[$j];
            }
            $sections[] = implode("\n", $block);
        }

        return $sections;
    }

    /** FortuneConversationService แบบไม่ผ่าน constructor (constructor อ่าน settings จาก DB) */
    private function conversation(string $deepTemplate): FortuneConversationService
    {
        $ref = new ReflectionClass(FortuneConversationService::class);
        $svc = $ref->newInstanceWithoutConstructor();

        $settings = new FortuneTellingSetting;
        $settings->deep_prompt_template = $deepTemplate;
        $ref->getProperty('settings')->setValue($svc, $settings);

        return $svc;
    }

    /** ซอร์สของเมธอดเดียว (ตั้งแต่บรรทัดประกาศถึงปีกกาปิด) */
    private function methodSource(string $class, string $method): string
    {
        $m = new ReflectionMethod($class, $method);
        $lines = file((string) $m->getFileName());

        return implode('', array_slice($lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));
    }

    private function invoke(object $target, string $method, array $args): mixed
    {
        return (new ReflectionMethod($target, $method))->invokeArgs($target, $args);
    }
}
