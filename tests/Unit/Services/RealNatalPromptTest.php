<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\PlanetEphemeris;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use App\Support\ThaiProvinces;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🗺️ ช่อง {zodiac_info} + {planet_positions} ของพรอมต์ดูดวง 39 ต้องเป็น "ดวงกำเนิดจริง" (2026-09-11)
 *
 * ที่มา: 2 ช่องนี้เคยเป็นของปลอม แต่ถูกส่งให้โมเดลในฐานะดวงจริงของลูกค้าที่จ่ายเงิน
 *   - {planet_positions} = FortuneChartService::calculatePlanetPositions($dayOfWeek) ผังสาธิต 7 แบบทั้งระบบ
 *     (เจ้าชนะ→ภพ 1 · มิตร→9/11/5 · ศัตรู→6/12/8) ติดป้าย "ตำแหน่งดาวในภพจริง (ต้องอ้างอิงในคำทำนาย)"
 *   - {zodiac_info} = ตารางราศีสากล (สายนะ) + ดาวพลูโต/ยูเรนัส/เนปจูน + วันปฏิทิน
 *   ขณะที่ FortuneAIService::buildPrompt() ต่อผังจริงท้ายพรอมต์ใบเดียวกัน ⇒ ราศี/ภพ 2 ชุดขัดกัน
 *
 * เทสต์ชุดนี้ล็อก:
 *   1. ราศีเกิด = นิรายนะจากดวงอาทิตย์จริง · ไม่มีดาวสากล · ดาวเจ้าชนะตามวันทางโหร (ย่ำรุ่ง + พุธกลางคืน)
 *   2. ทุกค่าใน {zodiac_info} และทุกบรรทัดดาวใน {planet_positions} ตรงกับผังที่ต่อท้ายพรอมต์
 *   3. ภพมีเฉพาะเมื่อผังมีฐานนับภพจริง — ดวงไม่มีภพ = ไม่มีเลขภพเลย (รวม Section A)
 *   4. พรอมต์ใบจริง (template ทรง prod) มีผังเต็มก้อนเดียว ในประโยคคำสั่งเป็นป้ายชี้กลับ
 *
 * ไม่แตะ DB — สร้าง service ด้วย newInstanceWithoutConstructor (constructor อ่าน settings จาก DB)
 */
class RealNatalPromptTest extends TestCase
{
    /**
     * ทรงเดียวกับ deep_prompt_template ของแอดมินบน prod (ตรวจ 2026-09-11 · 9,001 ตัวอักษร · CRLF)
     * {planet_positions} ในประโยคคำสั่ง 3 ที่ (บรรทัด 13 · 18 · 135) + บรรทัดวางข้อมูล 1 ที่ (47)
     * {zodiac_info} ในประโยค 1 ที่ (18) + บรรทัดวาง 1 ที่ (46) · ไม่มี {birth_date_section}
     * ⚠️ บรรทัด 135 จงใจเก็บถ้อยคำเดิม ("2 ภพ + 2 ดาวจริง") — ผังต้องกำกับดวงไม่มีภพได้เองแม้ template ยังสั่งภพ
     *    (prod แก้เป็น "2 ดาวจริง + ภพที่ดาวนั้นสถิต …" แล้วเมื่อ 2026-09-12 · md5 fb268b9d…)
     */
    private const PROD_SHAPED_TEMPLATE = [
        '   ✅ ต้องอ้าง*ดาวจริง + ภพจริง*ของลูกค้าคนนี้จาก {planet_positions} ด้านล่าง',
        '5. *ผูกข้อมูลเฉพาะของลูกค้า*: ทุกคำทำนายต้องเชื่อมโยงกับ {birth_info}, {zodiac_info}, {planet_positions}, {transit_info}',
        'ข้อมูลผู้ขอดูดวง:',
        '- วันเกิด: {birth_info}',
        '- {zodiac_info}',
        '{planet_positions}',
        '{transit_info}',
        'คำถาม: {question}',
        '{section_a_block}',
        '🪐 **บังคับอ้างโหราศาสตร์**: 2 ภพ + 2 ดาวจริง (จาก {planet_positions} เท่านั้น)',
    ];

    /** เครื่องหมายของของปลอมชุดเดิม — ห้ามหลุดกลับเข้าพรอมต์ */
    private const FAKE_MARKERS = [
        'แผนที่ดวงชะตากำเนิด',     // หัวบล็อกผังสาธิตเดิม
        'ธาตุประจำวันเกิด:',        // บรรทัดท้ายผังสาธิตเดิม
        'ดาวประจำราศี:',            // ช่องราศีสากลเดิม
        'ดาวพลูโต', 'ดาวยูเรนัส', 'ดาวเนปจูน',
        '(Western', 'ธาตุจีน',
    ];

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
    // 1. ราศีนิรายนะ · ดาวตำราไทย · วันทางโหร
    // ─────────────────────────────────────────────────────────────

    public function test_zodiac_sign_is_sidereal_from_the_real_sun_not_the_western_table(): void
    {
        // วันที่ 1 ของทุกเดือนอยู่ห่างวันยกราศีไทย (13–18) ⇒ ราศีสากลกับนิรายนะต่างกันเสมอ ไม่ใช่เหรียญหัวก้อย
        $tropicalOnTheFirst = [1 => 'มังกร', 'กุมภ์', 'มีน', 'เมษ', 'พฤษภ', 'เมถุน', 'กรกฎ', 'สิงห์', 'กันย์', 'ตุลย์', 'พิจิก', 'ธนู'];
        $eph = new PlanetEphemeris;

        foreach ($tropicalOnTheFirst as $month => $tropical) {
            $ymd = sprintf('1990-%02d-01', $month);
            $info = (new ThaiAstrologyService)->natalPromptBlocks($ymd)['zodiac_info'];

            $sidereal = $eph->zodiacSignLabel(Carbon::parse($ymd));
            $this->assertStringContainsString("ราศีเกิด: {$sidereal} (นิรายนะ", $info, "{$ymd} ต้องได้ราศีจากดวงอาทิตย์จริง");
            $this->assertStringNotContainsString("ราศีเกิด: {$tropical} (", $info, "{$ymd} ห้ามกลับไปใช้ราศีสากล");
        }
    }

    public function test_sign_lord_comes_from_the_thai_dignity_table_never_western_outer_planets(): void
    {
        $dignity = (array) config('thai_astrology_knowledge.planet_dignity');

        for ($month = 1; $month <= 12; $month++) {
            $info = (new ThaiAstrologyService)->natalPromptBlocks(sprintf('1990-%02d-01', $month))['zodiac_info'];

            foreach (self::FAKE_MARKERS as $fake) {
                $this->assertStringNotContainsString($fake, $info);
            }

            $this->assertMatchesRegularExpression('/ราศีเกิด: (\S+) \(/u', $info);
            preg_match('/ราศีเกิด: (\S+) \(/u', $info, $m);
            $lords = array_keys(array_filter($dignity, fn (array $d) => in_array($m[1], $d['rules'] ?? [], true)));
            $this->assertNotEmpty($lords, "ราศี{$m[1]} ต้องมีดาวเกษตรใน config");
            $this->assertStringContainsString('ดาวเกษตรของราศี: '.implode(', ', $lords).' |', $info);
        }
    }

    public function test_birth_day_ruler_follows_thai_day_rules(): void
    {
        $wednesday = $this->firstWeekday(Carbon::WEDNESDAY);

        // ไม่รู้เวลาเกิด = ห้ามเดา → พุธกลางวัน
        $plain = (new ThaiAstrologyService)->natalPromptBlocks($wednesday)['zodiac_info'];
        $this->assertStringContainsString('เกิดวันพุธ |', $plain);
        $this->assertStringContainsString('ดาวเจ้าชนะ: ดาวพุธ', $plain);

        // พุธกลางคืน = ราหู
        $night = (new ThaiAstrologyService)->natalPromptBlocks($wednesday.' 20:00')['zodiac_info'];
        $this->assertStringContainsString('เกิดวันพุธ กลางคืน |', $night);
        $this->assertStringContainsString('ดาวเจ้าชนะ: ราหู', $night);
        $this->assertStringContainsString('ดาวมิตร: เสาร์ |', $night);

        // ก่อนย่ำรุ่ง = ยังเป็นคืนของเมื่อวาน (ปฏิทินพุธ ตี 2 → ทางโหรอังคารกลางคืน) และต้องบอกตามตรง
        $beforeDawn = (new ThaiAstrologyService)->natalPromptBlocks($wednesday.' 02:00')['zodiac_info'];
        $this->assertStringContainsString('เกิดวันอังคาร กลางคืน (ทางโหร — ปฏิทินคือวันพุธ', $beforeDawn);
        $this->assertStringContainsString('ดาวเจ้าชนะ: ดาวอังคาร', $beforeDawn);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. ตรงกับผังที่ต่อท้ายพรอมต์ทุกค่า
    // ─────────────────────────────────────────────────────────────

    public function test_zodiac_info_matches_the_chart_header(): void
    {
        foreach ($this->chartCases() as $label => [$ymd, $hour, $province]) {
            $chart = (new ThaiAstrologyService)->formatPersonBlock($ymd, $hour, true, $province);
            $info = (new ThaiAstrologyService)->natalPromptBlocks($ymd, $hour, $province)['zodiac_info'];
            $h = $this->chartHeader($chart);

            $this->assertStringContainsString("ราศีเกิด: {$h['zodiac']} (", $info, "[{$label}] ราศี");
            $this->assertStringContainsString("เกิดวัน{$h['day']}", $info, "[{$label}] วันทางโหร");
            $this->assertStringContainsString("ดาวเจ้าชนะ: {$h['ruler']} |", $info, "[{$label}] ดาวเจ้าชนะ");
            $this->assertStringContainsString("ดาวมิตร: {$h['friends']} |", $info, "[{$label}] ดาวมิตร");
            $this->assertStringContainsString("ดาวศัตรู: {$h['enemies']} |", $info, "[{$label}] ดาวศัตรู");
            $this->assertStringContainsString("สีมงคล: {$h['color']} |", $info, "[{$label}] สีมงคล");
            $this->assertStringContainsString("เลขมงคล: {$h['number']} |", $info, "[{$label}] เลขมงคล");
            $this->assertStringContainsString("อายุ: {$h['age']} ปี", $info, "[{$label}] อายุ");
        }
    }

    public function test_every_planet_line_is_the_chart_line_verbatim(): void
    {
        foreach ($this->chartCases() as $label => [$ymd, $hour, $province]) {
            $chart = (new ThaiAstrologyService)->formatPersonBlock($ymd, $hour, true, $province);
            $lines = $this->planetLines((new ThaiAstrologyService)->natalPromptBlocks($ymd, $hour, $province)['planet_positions']);

            $this->assertCount(10, $lines, "[{$label}] ดาว 10 ดวงของผัง");
            foreach ($lines as $line) {
                $this->assertStringContainsString("      {$line}\n", $chart, "[{$label}] บรรทัดดาวต้องตรงกับผังทุกตัวอักษร");
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 3. ภพ — มีฐานจริงเท่านั้น
    // ─────────────────────────────────────────────────────────────

    public function test_houses_follow_the_real_lagna_when_birth_time_is_known(): void
    {
        $blocks = (new ThaiAstrologyService)->natalPromptBlocks('1990-05-15 08:30', null, 'เชียงใหม่');

        $coords = ThaiProvinces::coords('เชียงใหม่');
        $lagna = (new ThaiAstrologyService)->siderealLagna(Carbon::parse('1990-05-15 08:30'), $coords['lat'], $coords['lon']);
        $this->assertNotNull($lagna);

        $this->assertSame('lagna', $blocks['basis']);
        $this->assertStringContainsString("ภพนับจากลัคนาราศี{$lagna}", $blocks['planet_positions']);
        $this->assertSame("ผังดวงกำเนิด 🗺️ (ภพนับจากลัคนาราศี{$lagna})", $blocks['planet_positions_ref']);
        $this->assertHousesCountedFrom($lagna, $blocks['planet_positions']);
    }

    public function test_houses_count_from_moon_lagna_when_birth_time_is_unknown(): void
    {
        $ymd = $this->findBirthDayWithBasis('moon');
        $blocks = (new ThaiAstrologyService)->natalPromptBlocks($ymd);
        $moonSign = (new PlanetEphemeris)->positions(Carbon::parse($ymd)->setTime(0, 0, 0))['Moon']['sign'];

        $this->assertSame('moon', $blocks['basis']);
        $this->assertStringContainsString("*จันทร์ลัคน์* ราศี{$moonSign}", $blocks['planet_positions']);
        $this->assertStringNotContainsString('ภพนับจากลัคนา', $blocks['planet_positions'], 'ห้ามเรียกจันทร์ลัคน์ว่าลัคนา');
        $this->assertSame("ผังดวงกำเนิด 🗺️ (ภพนับจากจันทร์ลัคน์ราศี{$moonSign})", $blocks['planet_positions_ref']);
        $this->assertHousesCountedFrom($moonSign, $blocks['planet_positions']);
    }

    public function test_no_house_numbers_at_all_when_the_chart_has_no_anchor(): void
    {
        $blocks = (new ThaiAstrologyService)->natalPromptBlocks($this->findBirthDayWithBasis('none'));

        $this->assertSame('none', $blocks['basis']);
        $this->assertCount(10, $this->planetLines($blocks['planet_positions']), 'ไม่มีภพ ≠ ไม่มีดาว — ราศีของดาวยังต้องส่งให้โมเดล');
        $this->assertStringContainsString('ดวงนี้ไม่มีภพ', $blocks['planet_positions']);
        $this->assertStringContainsString('ดวงนี้ไม่มีภพ', $blocks['planet_positions_ref']);

        foreach (['planet_positions', 'planet_positions_ref', 'zodiac_info'] as $slot) {
            $this->assertDoesNotMatchRegularExpression('/ภพ\s*\d/u', $blocks[$slot], "{$slot}: ไม่มีฐานนับภพ = ห้ามมีเลขภพ");
        }
    }

    public function test_unreadable_birth_date_returns_empty_blocks(): void
    {
        $blocks = (new ThaiAstrologyService)->natalPromptBlocks('ไม่ใช่วันที่');

        $this->assertSame(['zodiac_info' => '', 'planet_positions' => '', 'planet_positions_ref' => '', 'basis' => ''], $blocks);
    }

    public function test_stated_inputs_are_read_from_the_build_prompt_text(): void
    {
        $this->assertSame("1. ถามงาน\n2. ถามเงิน", ThaiAstrologyService::numberedQuestionsText(['ถามงาน', 'ถามเงิน']));

        $astro = new ThaiAstrologyService;
        $stated = $astro->statedBirthInputs(ThaiAstrologyService::numberedQuestionsText(['หนูเกิดเวลา 08:30 น. ที่เชียงใหม่ อยากรู้เรื่องงานค่ะ']));
        $this->assertSame(8.5, $stated['hour']);
        $this->assertSame('เชียงใหม่', $stated['province']);

        $this->assertSame(['hour' => null, 'province' => null], $astro->statedBirthInputs(''));
    }

    // ─────────────────────────────────────────────────────────────
    // 4. ต่อสายเข้าเลน 39 จริง — buildPerQuestionDeepPrompt → FortuneAIService::buildPrompt
    // ─────────────────────────────────────────────────────────────

    public function test_prompt_carries_one_real_natal_chart_that_agrees_with_the_appended_chart(): void
    {
        // ไม่ฮาร์ดโค้ดว่าวันไหน "มีภพ" — ขึ้นกับจันทร์จริง (15 พ.ค. 2533 ไม่รู้เวลา = จันทร์ย้ายราศี = ไม่มีภพ)
        $cases = [
            'ไม่รู้เวลา · จันทร์ลัคน์' => [$this->findBirthDayWithBasis('moon'), 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา'],
            'ไม่รู้เวลา · ดวงไม่มีภพ' => [$this->findBirthDayWithBasis('none'), 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา'],
            'รู้เวลาจาก DB' => ['1990-05-15 08:30', 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา'],
            'พิมพ์เวลา+จังหวัดเอง' => ['1990-05-15', 'หนูเกิดเวลา 08:30 น. ที่เชียงใหม่ อยากรู้เรื่องงานค่ะ'],
            'พุธกลางคืน' => [$this->firstWeekday(Carbon::WEDNESDAY).' 20:00', 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา'],
        ];
        $expectedBasis = [
            'ไม่รู้เวลา · จันทร์ลัคน์' => 'moon',
            'ไม่รู้เวลา · ดวงไม่มีภพ' => 'none',
            'รู้เวลาจาก DB' => 'lagna',
            'พิมพ์เวลา+จังหวัดเอง' => 'lagna',
            'พุธกลางคืน' => 'lagna',
        ];

        foreach ($cases as $label => [$birthDate, $question]) {
            $final = $this->finalPrompt(implode("\r\n", self::PROD_SHAPED_TEMPLATE), $birthDate, $question);

            // อินพุตชุดเดียวกับที่ buildPrompt ใช้ผูกผังที่ต่อท้าย
            $astro = new ThaiAstrologyService;
            $stated = $astro->statedBirthInputs(ThaiAstrologyService::numberedQuestionsText([$question]));
            $expected = $astro->natalPromptBlocks($birthDate, $stated['hour'], $stated['province']);

            // ผังเต็มก้อนเดียว (บรรทัดวาง) · ประโยคคำสั่ง 3 ที่ + Section A 1 ที่ = ป้ายชี้กลับ
            $this->assertSame(1, substr_count($final, '[🗺️ ผังดวงกำเนิด'), "[{$label}] ผังดวงกำเนิดต้องมีก้อนเดียว");
            $this->assertStringContainsString($expected['planet_positions'], $final, "[{$label}] ผังเต็มต้องอยู่ที่บรรทัดวาง");
            $this->assertSame(4, substr_count($final, $expected['planet_positions_ref']), "[{$label}] ป้ายชี้ผัง 3 ที่ในคำสั่ง + Section A");
            $this->assertStringContainsString('(จาก '.$expected['planet_positions_ref'].' เท่านั้น)', $final);
            $this->assertStringNotContainsString('{planet_positions}', $final, "[{$label}] ห้ามเหลือ placeholder ดิบ");
            $this->assertStringNotContainsString('{zodiac_info}', $final);
            $this->assertSame(2, substr_count($final, $expected['zodiac_info']), "[{$label}] {zodiac_info} 2 ที่ตาม template");
            // 🔭 ตารางดาวจรก็ก้อนเดียว — ประโยคกฎข้อ 5 ได้ป้ายชี้แทนตาราง ~40 บรรทัด
            $this->assertSame(1, substr_count($final, '[🔭 ดาวจรจริง'), "[{$label}] ตารางดาวจรต้องมีก้อนเดียว");
            $this->assertStringContainsString(', ตารางดาวจรจริง 🔭', $final, "[{$label}] ประโยคคำสั่งต้องได้ป้ายชี้ตารางดาวจร");
            $this->assertStringNotContainsString('{transit_info}', $final);

            foreach (self::FAKE_MARKERS as $fake) {
                $this->assertStringNotContainsString($fake, $final, "[{$label}] ของปลอมหลุดกลับมา: {$fake}");
            }

            // ผังที่ต่อท้าย (buildPrompt) ต้องพูดเหมือนกันทุกค่า
            $chart = $this->appendedChart($final);
            $h = $this->chartHeader($chart);
            $this->assertStringContainsString("ราศีเกิด: {$h['zodiac']} (", $expected['zodiac_info'], "[{$label}] ราศีเกิด 2 บล็อกต้องตรงกัน");
            $this->assertStringContainsString("ดาวเจ้าชนะ: {$h['ruler']} |", $expected['zodiac_info'], "[{$label}] ดาวเจ้าชนะ 2 บล็อกต้องตรงกัน");
            foreach ($this->planetLines($expected['planet_positions']) as $line) {
                $this->assertStringContainsString("      {$line}\n", $chart, "[{$label}] บรรทัดดาวต้องตรงกับผังที่ต่อท้าย");
            }

            $this->assertSame($expectedBasis[$label], $expected['basis'], "[{$label}] ฐานนับภพ");

            if ($expected['basis'] === 'lagna') {
                preg_match('/⬆️ ลัคนา: ราศี(\S+)/u', $chart, $m);
                $this->assertNotEmpty($m, "[{$label}] ผังที่ต่อท้ายต้องผูกลัคนาได้");
                $this->assertStringContainsString("ภพนับจากลัคนาราศี{$m[1]}", $expected['planet_positions'], "[{$label}] ลัคนาต้องเป็นราศีเดียวกันทั้งสองบล็อก");
            }

            if ($expected['basis'] === 'moon') {
                preg_match('/จันทร์สถิตราศี(\S+) →/u', $chart, $m);
                $this->assertNotEmpty($m, "[{$label}] ผังที่ต่อท้ายต้องยกจันทร์ลัคน์");
                $this->assertStringContainsString("*จันทร์ลัคน์* ราศี{$m[1]}", $expected['planet_positions'], "[{$label}] จันทร์ลัคน์ต้องเป็นราศีเดียวกันทั้งสองบล็อก");
            }

            if ($expected['basis'] === 'none') {
                $this->assertDoesNotMatchRegularExpression('/ภพ\s*\d/u', $expected['planet_positions']);
                $this->assertDoesNotMatchRegularExpression('/ภพ\s*\d/u', $this->sectionA($final), "[{$label}] Section A ห้ามสั่งให้อ้างภพ");
                $this->assertStringContainsString('(จาก ผังดวงกำเนิด 🗺️ (ดวงนี้ไม่มีภพ', $final, "[{$label}] ประโยค \"2 ภพ + 2 ดาวจริง\" ต้องมีคำกำกับติดกัน");
            } else {
                $this->assertStringContainsString('ภพ 6/8/12', $this->sectionA($final), "[{$label}] มีภพ = คำใบ้ภพเดิมยังอยู่");
            }
        }
    }

    public function test_stored_birth_province_drives_the_natal_block_like_the_appended_chart(): void
    {
        // เวลาเกิดที่ลัคนาของอุบลฯ ต่างราศีกับพิกัดกลาง (กรุงเทพ) — ไม่งั้นเทสต์ผ่านได้แม้จังหวัดไม่ถูกใช้
        $province = 'อุบลราชธานี';
        $birthDate = $this->birthTimeWhereLagnaDependsOn($province);
        $question = 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา';

        $final = $this->finalPrompt(implode("\r\n", self::PROD_SHAPED_TEMPLATE), $birthDate, $question, $province);
        $chart = $this->appendedChart($final);

        $coords = ThaiProvinces::coords($province);
        $lagna = (new ThaiAstrologyService)->siderealLagna(Carbon::parse($birthDate), $coords['lat'], $coords['lon']);
        $this->assertStringContainsString("⬆️ ลัคนา: ราศี{$lagna}", $chart, 'ผังท้ายพรอมต์ใช้จังหวัดที่เก็บไว้');
        $this->assertStringContainsString("ภพนับจากลัคนาราศี{$lagna}", $final, 'ช่องผังดวงกำเนิดต้องใช้จังหวัดเดียวกับผังท้ายพรอมต์');
        $this->assertStringContainsString("(ภพนับจากลัคนาราศี{$lagna})", $final, 'ป้ายชี้ผังในประโยคคำสั่งก็ต้องเป็นลัคนาเดียวกัน');

        $expected = (new ThaiAstrologyService)->natalPromptBlocks($birthDate, null, $province);
        $this->assertStringContainsString($expected['planet_positions'], $final);
        foreach ($this->planetLines($expected['planet_positions']) as $line) {
            $this->assertStringContainsString("      {$line}\n", $chart);
        }

        // ไม่ส่งจังหวัด (บิลที่ยังไม่รู้จังหวัด) = พิกัดกลางทั้งสองบล็อก — ลัคนาต่างจากข้างบน
        $bangkok = $this->finalPrompt(implode("\r\n", self::PROD_SHAPED_TEMPLATE), $birthDate, $question, null);
        $this->assertStringNotContainsString("ภพนับจากลัคนาราศี{$lagna}", $bangkok);
    }

    public function test_every_block_reads_birth_time_from_the_same_numbered_text_as_build_prompt(): void
    {
        // เคสตั้งใจสร้าง: เวลาอยู่ท้ายหน้าต่างอ่าน 90 ตัวอักษรพอดี — เลขลำดับ "1. " ที่ buildPrompt เติมหน้าคำถาม
        //   ดันเวลาหลุดหน้าต่าง ⇒ ข้อความดิบอ่านได้ 08:30 แต่ข้อความที่ buildPrompt ใช้อ่านไม่ได้
        //   ถ้าช่องไหนกลับไปอ่านข้อความดิบ ช่องนั้นจะผูกลัคนา 08:30 ขณะที่ผังท้ายพรอมต์ไม่มีลัคนา = ขัดกัน
        $question = 'เกิด'.str_repeat('ก', 79).'08:30 น. อยากรู้เรื่องงาน';
        $astro = new ThaiAstrologyService;
        $this->assertSame(8.5, $astro->extractStatedBirthHour($question), 'ข้อความดิบต้องอ่านเวลาได้ (ไม่งั้นเคสนี้ไม่ได้ทดสอบอะไร)');
        $this->assertNull($astro->extractStatedBirthHour(ThaiAstrologyService::numberedQuestionsText([$question])));

        $final = $this->finalPrompt(implode("\r\n", self::PROD_SHAPED_TEMPLATE), '1990-05-15', $question);

        $this->assertStringNotContainsString('⬆️ ลัคนา: ราศี', $this->appendedChart($final), 'ผังท้ายพรอมต์ไม่ได้เวลาเกิด');
        $this->assertStringNotContainsString('ภพนับจากลัคนา', $final, 'ช่องผังดวงกำเนิด/ดาวจรต้องไม่ได้เวลาเกิดเช่นกัน');
    }

    public function test_zodiac_follows_the_sun_at_the_birth_moment_on_a_sign_change_day(): void
    {
        // หาวันอาทิตย์ยกราศีที่ "ตี 1" กับ "เที่ยงวัน" ได้คนละราศี (ไม่ฮาร์ดโค้ด — ขึ้นกับ ephemeris)
        $eph = new PlanetEphemeris;
        $cases = [];
        for ($m = 1; $m <= 12 && count($cases) < 3; $m++) {
            for ($d = 12; $d <= 18; $d++) {
                $date = Carbon::create(1985, $m, $d, 0, 0, 0, 'Asia/Bangkok');
                $atOne = $eph->positions($date->copy()->setTime(1, 0, 0))['Sun']['sign'];
                if (! str_starts_with($eph->zodiacSignLabel($date), $atOne)) {
                    $cases[] = [$date->format('Y-m-d').' 01:00', $atOne];
                    break;
                }
            }
        }
        $this->assertNotEmpty($cases, 'ต้องมีวันยกราศีให้ทดสอบ');

        foreach ($cases as [$ymd, $sunSign]) {
            $blocks = (new ThaiAstrologyService)->natalPromptBlocks($ymd);
            $chart = (new ThaiAstrologyService)->formatPersonBlock($ymd);

            $this->assertStringContainsString("ราศีเกิด: {$sunSign} (", $blocks['zodiac_info'], "{$ymd} ราศีเกิดต้องเป็นราศีของอาทิตย์ขณะเกิด");
            $this->assertStringContainsString("☉ อาทิตย์ ราศี{$sunSign} ", $blocks['planet_positions'], "{$ymd} บรรทัดอาทิตย์");
            $this->assertMatchesRegularExpression('/^♈ ราศี: '.preg_quote($sunSign, '/').' \(/mu', $chart, "{$ymd} หัวผังต้องตรงกับบรรทัดอาทิตย์");
        }
    }

    public function test_no_house_names_either_when_the_chart_has_no_anchor(): void
    {
        $names = array_merge(
            array_column((array) config('thai_astrology_knowledge.twelve_houses'), 'name'),
            ['กดุมภ', 'วินาศ']   // ชื่อสะกดแบบใน template ของแอดมิน
        );
        $pattern = '/ภพ\s*\d|'.implode('|', array_map(fn ($n) => preg_quote((string) $n, '/'), $names)).'/u';

        $birthDate = $this->findBirthDayWithBasis('none');
        $blocks = (new ThaiAstrologyService)->natalPromptBlocks($birthDate);
        foreach (['planet_positions', 'planet_positions_ref', 'zodiac_info'] as $slot) {
            $this->assertDoesNotMatchRegularExpression($pattern, $blocks[$slot], "{$slot}: ดวงไม่มีภพ = ห้ามมีชื่อภพ/เลขภพ");
        }

        $final = $this->finalPrompt(implode("\r\n", self::PROD_SHAPED_TEMPLATE), $birthDate, 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา');
        $this->assertDoesNotMatchRegularExpression($pattern, $this->sectionA($final), 'Section A ห้ามชี้ไปที่ภพ');
    }

    public function test_placement_line_tolerates_admin_ui_padding_and_is_used_once(): void
    {
        $nbsp = "\u{00A0}";
        $templates = [
            'NBSP นำหน้า' => "จาก {planet_positions} เท่านั้น\r\n{$nbsp}{planet_positions}{$nbsp}\r\nคำถาม: {question}",
            'หัวข้อ "- "' => "จาก {planet_positions} เท่านั้น\n- {planet_positions}\nคำถาม: {question}",
            'บรรทัดวาง 2 ที่' => "{planet_positions}\nจาก {planet_positions} เท่านั้น\n{planet_positions}\nคำถาม: {question}",
        ];

        foreach ($templates as $label => $template) {
            $final = $this->finalPrompt($template, '1990-05-15 08:30', 'ถามเรื่องงาน');

            $this->assertSame(1, substr_count($final, '[🗺️ ผังดวงกำเนิด'), "[{$label}] ผังเต็มต้องมีก้อนเดียว");
            $this->assertStringContainsString('จาก ผังดวงกำเนิด 🗺️ (ภพนับจากลัคนาราศี', $final, "[{$label}] ในประโยคต้องเป็นป้ายชี้กลับ");
            $this->assertStringNotContainsString('{planet_positions}', $final);
        }
    }

    public function test_template_without_a_placement_line_still_gets_the_full_chart(): void
    {
        // แอดมินแก้ template จนเหลือแต่ในประโยค — ผังต้องไม่หายจากพรอมต์ (ขยายเต็มเหมือนเดิม)
        $final = $this->finalPrompt("อ้างดาวจริงจาก {planet_positions} เท่านั้น\nคำถาม: {question}", '1990-05-15', 'ถามเรื่องงาน');

        $this->assertSame(1, substr_count($final, '[🗺️ ผังดวงกำเนิด'));
        $this->assertStringNotContainsString('ผังดวงกำเนิด 🗺️ (', $final, 'ไม่มีบรรทัดวาง = ไม่มีป้ายชี้ไปหาก้อนที่ไม่มี');
    }

    public function test_no_birth_date_leaves_both_slots_empty(): void
    {
        $prompt = $this->invoke($this->conversation(implode("\n", self::PROD_SHAPED_TEMPLATE)), 'buildPerQuestionDeepPrompt', [
            ['name' => 'ทดสอบ'], 'ถามเรื่องงาน', 1, 1, null, [], null,
        ]);

        $this->assertStringNotContainsString('{planet_positions}', $prompt);
        $this->assertStringNotContainsString('{zodiac_info}', $prompt);
        $this->assertStringNotContainsString('[🗺️ ผังดวงกำเนิด', $prompt);
        $this->assertStringContainsString('ข้อมูลดวงด้านบน', $this->sectionA($prompt));
    }

    public function test_section_a_never_shows_a_raw_placeholder(): void
    {
        $conv = $this->conversation();

        foreach (['lagna', 'moon', 'none', ''] as $basis) {
            $block = $this->invoke($conv, 'buildSectionABlock', [1, '', 'เจ้าชะตา', $basis === '' ? '' : 'ผังดวงกำเนิด 🗺️ (ป้าย)', $basis]);
            $this->assertStringNotContainsString('{planet_positions}', $block, "basis={$basis}");
        }

        $this->assertSame('', $this->invoke($conv, 'buildSectionABlock', [2, '', 'เจ้าชะตา', 'ป้าย', 'none']), 'Section A เฉพาะข้อ 1');
    }

    public function test_the_demo_chart_and_western_zodiac_can_no_longer_reach_a_prompt(): void
    {
        // กันถอยหลัง: ผังสาธิต/ตารางราศีสากลเคยถูก "เลื่อนขั้น" จากของตกแต่งเป็นแหล่งความจริงมาแล้ว
        $source = (string) file_get_contents(app_path('Services/FortuneConversationService.php'));

        $this->assertStringNotContainsString('->calculatePlanetPositions(', $source);
        $this->assertStringNotContainsString('function getZodiacDescription', $source);
        $this->assertStringNotContainsString('(Western Zodiac)', $source);
    }

    // ─────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────

    /** @return array<string, array{0:string, 1:float|null, 2:string|null}> */
    private function chartCases(): array
    {
        $wednesday = $this->firstWeekday(Carbon::WEDNESDAY);

        return [
            'ไม่รู้เวลา' => ['1990-05-15', null, null],
            'เวลาจาก DB' => ['1990-05-15 08:30', null, null],
            'เวลา+จังหวัดจากคำถาม' => ['1990-05-15', 8.5, 'เชียงใหม่'],
            'พุธกลางคืน' => [$wednesday.' 20:00', null, null],
            'ก่อนย่ำรุ่ง' => [$wednesday.' 02:00', null, null],
            'ดวงไม่มีภพ' => [$this->findBirthDayWithBasis('none'), null, null],
        ];
    }

    /**
     * พรอมต์สุดท้ายที่ส่งโมเดล — เส้นเดียวกับ processPaymentConfirmed
     * (จังหวัดที่เก็บไว้ส่งค่าเดียวกันให้ทั้ง buildPerQuestionDeepPrompt และ withBirthProvince)
     */
    private function finalPrompt(string $template, string $birthDate, string $question, ?string $storedProvince = null): string
    {
        $perQuestion = $this->invoke($this->conversation($template), 'buildPerQuestionDeepPrompt', [
            ['name' => 'ทดสอบ', 'gender' => 'female'], $question, 1, 1, $birthDate, [], null, $storedProvince,
        ]);

        $ai = (new ReflectionClass(FortuneAIService::class))->newInstanceWithoutConstructor();
        $ai->withBirthProvince($storedProvince);

        return $this->invoke($ai, 'buildPrompt', [[$question], ['name' => 'ทดสอบ'], null, $perQuestion, $birthDate]);
    }

    /** เวลาเกิด (15 พ.ค. 2533) ที่ลัคนาของ $province ต่างราศีกับพิกัดกลาง — ไม่ฮาร์ดโค้ด ขึ้นกับสูตรลัคนาจริง */
    private function birthTimeWhereLagnaDependsOn(string $province): string
    {
        $astro = new ThaiAstrologyService;
        $c = ThaiProvinces::coords($province);

        for ($minutes = 0; $minutes < 24 * 60; $minutes += 5) {
            $ymd = sprintf('1990-05-15 %02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $dt = Carbon::parse($ymd);
            $there = $astro->siderealLagna($dt, $c['lat'], $c['lon']);
            if ($there !== null && $there !== $astro->siderealLagna($dt)) {
                return $ymd;
            }
        }

        $this->fail("ไม่พบเวลาเกิดที่ลัคนาของ {$province} ต่างจากพิกัดกลาง");
    }

    /** ผังดวงที่ FortuneAIService::buildPrompt() ต่อท้าย (template ไม่มี {birth_date_section}) */
    private function appendedChart(string $final): string
    {
        $pos = mb_strpos($final, '=== 🪐 ดวงพื้นของเจ้าชะตา');
        $this->assertNotFalse($pos, 'buildPrompt ต้องต่อผังดวงจริงท้ายพรอมต์');

        return mb_substr($final, $pos);
    }

    /** ค่าหัวผังของ formatPersonBlock() */
    private function chartHeader(string $chart): array
    {
        $patterns = [
            'day' => '/^📅 .+ \(วัน(.+), อายุ (\d+) ปี\)$/mu',
            'zodiac' => '/^♈ ราศี: (.+)$/mu',
            'ruler' => '/^⭐ ดาวเจ้าชนะ: (.+) \| 🔥 ธาตุ: .+$/mu',
            'friends' => '/^🤝 ดาวมิตร: (.+) \| ⚔️ ดาวศัตรู: (.+)$/mu',
            'color' => '/^🎨 สีมงคล: (.+) \| 🔢 เลขมงคล: (.+)$/mu',
        ];
        $m = [];
        foreach ($patterns as $key => $re) {
            $this->assertSame(1, preg_match($re, $chart, $m[$key]), "หัวผังไม่มีบรรทัด {$key}:\n{$chart}");
        }

        return [
            'day' => $m['day'][1],
            'age' => $m['day'][2],
            'zodiac' => $m['zodiac'][1],
            'ruler' => $m['ruler'][1],
            'friends' => $m['friends'][1],
            'enemies' => $m['friends'][2],
            'color' => $m['color'][1],
            'number' => $m['color'][2],
        ];
    }

    /** บรรทัดดาวในบล็อก {planet_positions} (ตัด "- " นำหน้า) */
    private function planetLines(string $block): array
    {
        $out = [];
        foreach (explode("\n", $block) as $line) {
            if (str_starts_with($line, '- ')) {
                $out[] = substr($line, 2);
            }
        }

        return $out;
    }

    /** ทุกบรรทัดดาวต้องมีเลขภพที่นับจาก $anchor ถูกต้อง */
    private function assertHousesCountedFrom(string $anchor, string $block): void
    {
        $order = (array) config('thai_astrology_knowledge.zodiac_order');
        $lines = $this->planetLines($block);
        $this->assertCount(10, $lines);

        foreach ($lines as $line) {
            $this->assertSame(1, preg_match('/ราศี(\S+) +\d+° · ภพ(\d+)/u', $line, $m), "บรรทัดดาวต้องมีภพ: {$line}");
            $house = ((array_search($m[1], $order, true) - array_search($anchor, $order, true) + 12) % 12) + 1;
            $this->assertSame((string) $house, $m[2], "ภพของ {$line} ต้องนับจากราศี{$anchor}");
        }
    }

    /** Section A ในพรอมต์ (ถึงเส้นคั่นท้ายบล็อก) */
    private function sectionA(string $prompt): string
    {
        $start = mb_strpos($prompt, '🌙 **Section A');
        $this->assertNotFalse($start, 'ข้อ 1 ต้องมี Section A');
        $end = mb_strpos($prompt, '═══', $start);

        return mb_substr($prompt, $start, $end === false ? null : $end - $start);
    }

    /** ไล่หาวันเกิด (ไม่รู้เวลา) ที่ฐานนับภพเป็นแบบที่ต้องการ — ไม่ฮาร์ดโค้ดวัน เพราะขึ้นกับจันทร์จริง */
    private function findBirthDayWithBasis(string $basis): string
    {
        $astro = new ThaiAstrologyService;
        for ($d = 1; $d <= 28; $d++) {
            $ymd = sprintf('1990-05-%02d', $d);
            if ($astro->resolveLagnaBasis($ymd, null) === $basis) {
                return $ymd;
            }
        }

        $this->fail("ไม่พบวันเกิดเดือน พ.ค. 2533 ที่ฐานนับภพเป็น {$basis}");
    }

    private function firstWeekday(int $dayOfWeek): string
    {
        $d = Carbon::create(1990, 5, 13, 12, 0, 0, 'Asia/Bangkok');
        while ($d->dayOfWeek !== $dayOfWeek) {
            $d->addDay();
        }

        return $d->format('Y-m-d');
    }

    /** FortuneConversationService แบบไม่ผ่าน constructor (constructor อ่าน settings จาก DB) */
    private function conversation(?string $deepTemplate = null): FortuneConversationService
    {
        $ref = new ReflectionClass(FortuneConversationService::class);
        $svc = $ref->newInstanceWithoutConstructor();

        if ($deepTemplate !== null) {
            $settings = new FortuneTellingSetting;
            $settings->deep_prompt_template = $deepTemplate;
            $ref->getProperty('settings')->setValue($svc, $settings);
        }

        return $svc;
    }

    private function invoke(object $target, string $method, array $args): mixed
    {
        return (new ReflectionMethod($target, $method))->invokeArgs($target, $args);
    }
}
