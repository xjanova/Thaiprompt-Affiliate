<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\Fortune\CelticCrossConversationTrait;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneAIService;
use App\Services\FortuneChartService;
use App\Services\FortuneConversationService;
use App\Support\ThaiProvinces;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🖼️ รูปผังดวงกำเนิดที่ส่งให้ลูกค้าต้องเป็น "ผังเดียวกับที่คำทำนายอ้าง" (2026-09-12)
 *
 * ที่มา: บิลดูดวง 39 ส่งรูปผังจาก FortuneChartService::generateBirthChart() คู่กับคำทำนาย
 *   รูปวาดจาก calculatePlanetPositions($dayOfWeek) = ผังสาธิต 7 แบบทั้งระบบ (เจ้าชนะ→ภพ 1 · มิตร→9/11/5
 *   · ศัตรู→6/12/8) + วันปฏิทิน (ไม่ข้ามย่ำรุ่ง · ไม่มีพุธกลางคืน) ขณะที่ข้อความอ้างผังจริงจาก
 *   ThaiAstrologyService (ดาวนิรายนะจริง · ภพนับจากลัคนา → จันทร์ลัคน์ → ไม่มีภพ) ⇒ รูปกับข้อความขัดกัน
 *
 * เทสต์ชุดนี้ล็อก:
 *   1. ภพของดาวทุกดวงที่ส่งให้ตัววาด = ภพในบล็อก {planet_positions} ของพรอมต์ (อินพุตชุดเดียวกัน)
 *   2. ผังไม่มีภพ (ไม่รู้เวลาเกิด + จันทร์ย้ายราศี) = ไม่มีการวางดาวลงภพเลย วางตามราศีแทน
 *   3. ดาวเจ้าชนะ/วันเกิดในรูป = ชุดเดียวกับหัวผัง (ย่ำรุ่ง 06:00 + พุธกลางคืน = ราหู)
 *   4. ชื่อไฟล์ประจำผัง ไม่ใช่ประจำวันในสัปดาห์
 *   5. อินพุตรูปของบิล 39 = อินพุตผังในพรอมต์ข้อ 1 · จุดเรียกที่ลูกค้าจ่ายเงินส่งอินพุตครบ
 *
 * ไม่แตะ DB — service ที่ constructor อ่าน DB สร้างด้วย newInstanceWithoutConstructor
 */
class NatalChartImageTest extends TestCase
{
    /** template ขั้นต่ำที่มีบรรทัดวาง {planet_positions} แบบของแอดมินบน prod */
    private const DEEP_TEMPLATE = [
        'ข้อมูลผู้ขอดูดวง:',
        '- วันเกิด: {birth_info}',
        '- {zodiac_info}',
        '{planet_positions}',
        '{transit_info}',
        'คำถาม: {question}',
        '{section_a_block}',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 9, 12, 10, 0, 0, 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // 1-2. ภพในรูป = ภพในพรอมต์ · ไม่มีฐาน = ไม่มีภพ
    // ─────────────────────────────────────────────────────────────

    public function test_house_assignment_passed_to_the_renderer_equals_the_prompt_chart(): void
    {
        foreach ($this->anchoredCases() as $label => [$ymd, $hour, $province, $basis]) {
            $data = (new FortuneChartService)->natalChartData($ymd, 'ทดสอบ', $hour, $province);
            $blocks = (new ThaiAstrologyService)->natalPromptBlocks($ymd, $hour, $province);

            $this->assertNotNull($data, $label);
            $this->assertSame($basis, $blocks['basis'], "{$label}: เคสนี้ต้องเป็นฐาน {$basis}");
            $this->assertSame($blocks['basis'], $data['basis'], "{$label}: ฐานนับภพของรูปต้องเท่าของพรอมต์");

            $expected = $this->promptHouses($blocks['planet_positions']);
            $this->assertCount(10, $expected, $label);

            $this->assertSame(
                $this->normalise($this->housesByKey($expected, $data)),
                $this->normalise($data['planetPositions']),
                "{$label}: ภพที่ส่งให้ตัววาดต้องตรงกับบรรทัดดาวในพรอมต์ทุกดวง"
            );

            // ราศีภพที่ 1 ในรูป = ฐานที่พรอมต์บอก
            $this->assertSame($data['anchor'], $data['houseSigns'][1], $label);
            $ref = $basis === 'lagna' ? "ภพนับจากลัคนาราศี{$data['anchor']}" : "ภพนับจากจันทร์ลัคน์ราศี{$data['anchor']}";
            $this->assertStringContainsString($ref, $blocks['planet_positions_ref'], $label);
        }
    }

    public function test_no_house_assignment_when_the_chart_has_no_anchor(): void
    {
        $ymd = $this->findBirthDayWithBasis('none');
        $data = (new FortuneChartService)->natalChartData($ymd, 'ทดสอบ');
        $blocks = (new ThaiAstrologyService)->natalPromptBlocks($ymd);

        $this->assertSame('none', $blocks['basis']);
        $this->assertSame('none', $data['basis']);
        $this->assertNull($data['anchor']);
        $this->assertSame([], $data['planetPositions'], 'ไม่มีฐานนับภพ = ห้ามวางดาวลงภพแม้แต่ดวงเดียว');
        $this->assertSame([], $data['houseSigns']);
        foreach ($data['planets'] as $key => $p) {
            $this->assertNull($p['house'], "{$key} ต้องไม่มีภพ");
        }

        // ดาวยังอยู่ครบ — วางตามราศีตามบรรทัดดาวในพรอมต์
        $placed = array_merge(...array_values($data['signPositions']));
        $this->assertCount(10, $placed);
        $this->assertCount(10, array_unique($placed));
        foreach ($this->promptHouses($blocks['planet_positions']) as $name => [$sign, $house]) {
            $this->assertNull($house, "พรอมต์ต้องไม่มีเลขภพ: {$name}");
            $key = $this->keyByName($data, $name);
            $this->assertContains($key, $data['signPositions'][$sign], "{$name} ต้องอยู่ช่องราศี{$sign}");
        }

        // จันทร์วันนั้นย้ายราศี — รูปต้องติดป้ายว่าไม่แน่ (ผังข้อความสั่งห้ามฟันธงจากราศีจันทร์)
        $this->assertTrue($data['planets']['moon']['uncertain']);
    }

    public function test_unreadable_birth_date_gives_no_chart_instead_of_the_demo_layout(): void
    {
        $chart = $this->capturingChartService();

        $this->assertNull($chart->natalChartData('ไม่ใช่วันที่', 'ทดสอบ'));
        $this->assertNull($chart->generateBirthChart('ไม่ใช่วันที่', 'ทดสอบ'));
        $this->assertNull($chart->rendered, 'ผูกดวงไม่ได้ = ไม่มีรูป ห้ามถอยไปวาดผังสาธิต');
    }

    // ─────────────────────────────────────────────────────────────
    // 3. ดาวเจ้าชนะ/วันเกิด = ชุดเดียวกับหัวผัง
    // ─────────────────────────────────────────────────────────────

    public function test_ruler_and_day_follow_the_chart_header_not_the_calendar(): void
    {
        $wednesday = $this->firstWeekday(Carbon::WEDNESDAY);
        $cases = [
            // [วันเกิด, ดาวเจ้าชนะ, วันทางโหร, วันปฏิทิน (null = ไม่ข้ามย่ำรุ่ง)]
            'ไม่รู้เวลา = พุธกลางวัน' => [$wednesday, 'พุธ', 'พุธ', null],
            'พุธกลางคืน = ราหู' => [$wednesday.' 20:00', 'ราหู', 'พุธ กลางคืน', null],
            'ก่อนย่ำรุ่ง = คืนของเมื่อวาน' => [$wednesday.' 02:00', 'อังคาร', 'อังคาร กลางคืน', 'พุธ'],
            'พฤหัส ตี 3 = พุธกลางคืน' => [Carbon::parse($wednesday)->addDay()->format('Y-m-d').' 03:00', 'ราหู', 'พุธ กลางคืน', 'พฤหัสบดี'],
        ];

        foreach ($cases as $label => [$ymd, $ruler, $day, $calendar]) {
            $data = (new FortuneChartService)->natalChartData($ymd, 'ทดสอบ');
            $info = (new ThaiAstrologyService)->natalPromptBlocks($ymd)['zodiac_info'];

            $this->assertSame($ruler, $data['mainPlanet'], $label);
            $this->assertSame($day, $data['dayOfWeek'], $label);
            $this->assertSame($calendar, $data['calendarDayOfWeek'], $label);
            $this->assertNotNull($data['mainPlanetKey'], "{$label}: ต้องหาดาวเจ้าชนะในตารางดาวของตัววาดเจอ");
            $this->assertNotSame('', $data['mainPlanetNumeral'], $label);

            // ชุดเดียวกับที่พรอมต์พิมพ์ในช่อง {zodiac_info}
            $this->assertMatchesRegularExpression('/ดาวเจ้าชนะ: (ดาว)?'.preg_quote($ruler, '/').'/u', $info, $label);
            $this->assertStringContainsString("เกิดวัน{$day}", $info, $label);
            $this->assertSame(
                $this->relationNames($this->relationField($info, 'ดาวมิตร')),
                $data['friends'],
                "{$label}: ดาวมิตรในรูปต้องเป็นชุดเดียวกับพรอมต์"
            );
            $this->assertSame(
                $this->relationNames($this->relationField($info, 'ดาวศัตรู')),
                $data['enemies'],
                "{$label}: ดาวศัตรูในรูปต้องเป็นชุดเดียวกับพรอมต์"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 4. เส้นทางวาดจริง + ชื่อไฟล์ประจำผัง
    // ─────────────────────────────────────────────────────────────

    public function test_generate_birth_chart_hands_the_real_chart_to_the_renderer(): void
    {
        [$ymd, $hour, $province] = $this->anchoredCases()['เวลา+จังหวัดจากคำถาม'];
        $chart = $this->capturingChartService();

        $url = $chart->generateBirthChart($ymd, 'ทดสอบ', 'female', $hour, $province);

        $this->assertNotNull($url);
        $this->assertNotNull($chart->rendered, 'ต้องวาดด้วยตัววาดผังดวงกำเนิดจริง');
        $expected = (new FortuneChartService)->natalChartData($ymd, 'ทดสอบ', $hour, $province);
        $this->assertSame($expected['planetPositions'], $chart->rendered['planetPositions']);
        $this->assertSame($expected['basis'], $chart->rendered['basis']);

        // ของจริงมีดาว 10 ดวง (รวมมฤตยู) — ผังสาธิตเดิมมี 9 และวางดาวเจ้าชนะไว้ภพ 1 เสมอ
        $this->assertCount(10, array_merge(...array_values($chart->rendered['planetPositions'])));
        $this->assertArrayHasKey('uranus', $chart->rendered['planets']);

        $this->assertSame('birth-chart-'.$expected['chartKey'], $chart->savedPrefix);
        $this->assertDoesNotMatchRegularExpression('/^birth-chart-\d$/', (string) $chart->savedPrefix, 'ห้ามกลับไปตั้งชื่อตามวันในสัปดาห์');
    }

    public function test_chart_file_key_is_per_chart_not_per_weekday(): void
    {
        $svc = new FortuneChartService;
        $key = fn (string $ymd, ?float $h = null, ?string $p = null, string $name = 'ทดสอบ') => $svc->natalChartData($ymd, $name, $h, $p)['chartKey'];

        $base = $key('1990-05-15 08:30', null, 'เชียงใหม่');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{12}$/', $base);
        $this->assertSame($base, $key('1990-05-15 08:30', null, 'เชียงใหม่'), 'ผังเดียวกัน = คีย์เดียวกัน');
        $this->assertSame($base, $key('1990-05-15', 8.5, 'เชียงใหม่'), 'เวลาจากคำถามกับเวลาในสตริงวันเกิด = ผังเดียวกัน');
        $this->assertSame($base, $key('1990-05-15 08:30', null, 'เชียงใหม่', 'ชื่ออื่น'), 'ชื่อไม่ใช่อินพุตผูกดวง');

        $this->assertNotSame($base, $key('1990-05-15 09:30', null, 'เชียงใหม่'), 'เวลาเกิดต่าง = ผังต่าง');
        $this->assertNotSame($base, $key('1990-05-15 08:30', null, 'ภูเก็ต'), 'จังหวัดต่าง = ผังต่าง');
        $this->assertNotSame($base, $key('1990-05-15 08:30'), 'ไม่รู้จังหวัด = พิกัดกรุงเทพ = ผังต่าง');
        $this->assertSame($key('1990-05-15 08:30'), $key('1990-05-15 08:30', null, 'ดาวอังคาร'), 'ชื่อจังหวัดที่ไม่รู้จัก = ไม่บอกจังหวัด');

        // วันในสัปดาห์เดียวกัน (ห่าง 7 วัน) ต้องได้คนละไฟล์ — ป้ายเดิมให้ชื่อเดียวกัน
        $this->assertNotSame($key('1990-05-15'), $key('1990-05-22'));
    }

    // ─────────────────────────────────────────────────────────────
    // 5. อินพุตรูปบิล 39 = อินพุตพรอมต์ข้อ 1 · จุดเรียกที่จ่ายเงินแล้ว
    // ─────────────────────────────────────────────────────────────

    public function test_image_inputs_are_the_question_one_prompt_inputs(): void
    {
        $svc = $this->conversation();
        $inputs = fn (array $q, ?string $known = null) => $this->invoke($svc, 'deepChartImageInputs', [$q, $known]);

        $this->assertSame(
            ['hour' => 8.5, 'province' => 'เชียงใหม่'],
            $inputs(['หนูเกิดเวลา 08:30 น. ที่เชียงใหม่ อยากรู้เรื่องงานค่ะ', 'แฟนเกิดตี 2 ที่ภูเก็ต'])
        );
        $this->assertSame(
            ['hour' => 8.5, 'province' => 'ขอนแก่น'],
            $inputs(['หนูเกิดเวลา 08:30 น. ที่เชียงใหม่ อยากรู้เรื่องงานค่ะ'], 'ขอนแก่น'),
            'จังหวัดที่รู้แล้ว (DB) ชนะข้อความ — กฎเดียวกับพรอมต์'
        );
        $this->assertSame(['hour' => null, 'province' => 'ขอนแก่น'], $inputs([], 'ขอนแก่น'));
        $this->assertSame(['hour' => null, 'province' => null], $inputs([]));
    }

    public function test_image_matches_the_chart_printed_in_the_question_one_prompt(): void
    {
        $birthDate = $this->birthTimeWhereLagnaDependsOn('เชียงใหม่', [null, 'ขอนแก่น']);
        $cases = [
            // [คำถามทั้งบิล, จังหวัดที่รู้แล้ว]
            'เวลาในสตริง + จังหวัดจากคำถามข้อ 1' => [['ถามงาน หนูเกิดที่เชียงใหม่ค่ะ', 'ถามเงิน'], null],
            'จังหวัดที่รู้แล้วชนะคำถาม' => [['ถามงาน หนูเกิดที่เชียงใหม่ค่ะ'], 'ขอนแก่น'],
            'ไม่รู้จังหวัด' => [['ถามงาน', 'ถามเงิน'], null],
        ];

        foreach ($cases as $label => [$questions, $known]) {
            $prompt = $this->firstPaidPrompt($birthDate, $questions[0], $known);
            $expected = $this->promptHouses($prompt);
            $this->assertCount(10, $expected, "{$label}: พรอมต์ข้อ 1 ต้องมีผังดวงกำเนิดครบ 10 ดวง");

            $inputs = $this->invoke($this->conversation(), 'deepChartImageInputs', [$questions, $known]);
            $data = (new FortuneChartService)->natalChartData($birthDate, 'ทดสอบ', $inputs['hour'], $inputs['province']);

            $this->assertSame('lagna', $data['basis'], $label);
            $this->assertStringContainsString("ภพนับจากลัคนาราศี{$data['anchor']}", $prompt, "{$label}: ลัคนาในรูปต้องเป็นลัคนาเดียวกับพรอมต์");
            $this->assertSame(
                $this->normalise($this->housesByKey($expected, $data)),
                $this->normalise($data['planetPositions']),
                "{$label}: ภพในรูปต้องตรงกับพรอมต์ข้อ 1 ทุกดวง"
            );
        }

        // เคสแรกกับเคสที่ 2 ต้องได้ลัคนาคนละราศีจริง ไม่งั้นเทสต์ผ่านได้แม้จังหวัดไม่ถูกใช้
        $anchor = fn (?string $p) => (new FortuneChartService)->natalChartData($birthDate, 'ทดสอบ', null, $p)['anchor'];
        $this->assertNotSame($anchor('เชียงใหม่'), $anchor('ขอนแก่น'));
        $this->assertNotSame($anchor('เชียงใหม่'), $anchor(null));
    }

    public function test_paid_call_sites_draw_with_the_prompt_inputs(): void
    {
        $paid = $this->methodSource(FortuneConversationService::class, 'processPaymentConfirmed');
        $this->assertMatchesRegularExpression(
            // (2026-09-12) resolve ครั้งเดียวต่อบิลก่อนวาดรูป แล้วใช้ค่าเดียวกันทุกข้อ ⇒ มีเงื่อนไข $birthDate นำหน้าได้
            '/\$chartInputs\s*=\s*(?:\$birthDate\s*\?\s*)?\$this->deepChartImageInputs\(\(array\) \$questions, \$birthProvince(?:, \$reading->birthTimeIsKnown\(\))?\)/',
            $paid,
            'รูปบิล 39 ต้องใช้คำถาม + จังหวัดชุดเดียวกับที่ส่งให้พรอมต์'
        );
        $this->assertMatchesRegularExpression(
            '/generateBirthChart\(\s*\$birthDate,[^;]*\$chartInputs\[\'hour\'\], \$chartInputs\[\'province\'\]\s*\)/s',
            $paid,
            'ต้องส่งเวลา/จังหวัดเข้า generateBirthChart — ส่งแค่วันเกิด = ลัคนาคนละราศีกับคำทำนาย'
        );

        foreach (['createPaymentBill', 'afterTarotCardDrawn'] as $method) {
            $body = $this->methodSource(FortuneConversationService::class, $method);
            $this->assertStringContainsString('$this->deepChartImageInputs(', $body, "{$method}: รูปของบิลเดียวกันต้องผูกดวงชุดเดียวกัน");
            $this->assertStringContainsString('birthDateTimeForChart()', $body, $method);
        }

        $celtic = $this->methodSource(CelticCrossConversationTrait::class, 'buildCelticBirthChartUrl');
        $this->assertStringContainsString('birthDateTimeForChart()', $celtic, 'Celtic 99: เวลาเกิดที่ยืนยันแล้วต้องมากับรูป');
        $this->assertStringContainsString('$reading->birthProvinceIfKnown()', $celtic, 'Celtic 99: จังหวัดเดียวกับบล็อกดวงในพรอมต์');
    }

    // ─────────────────────────────────────────────────────────────
    // วาดจริงได้ทุกฐาน (รวมดาวกองราศีเดียว 7 ดวง)
    // ─────────────────────────────────────────────────────────────

    public function test_real_render_produces_a_png_for_every_basis(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ไม่มี GD');
        }

        $cases = [
            'lagna' => ['1990-05-15 08:30', null, 'เชียงใหม่'],
            'moon' => [$this->findBirthDayWithBasis('moon'), null, null],
            'none' => [$this->findBirthDayWithBasis('none'), null, null],
            // 4 ก.พ. 2505 — ดาว 7 ดวงกองราศีเดียว (อาทิตย์ จันทร์ อังคาร พุธ พฤหัส ศุกร์ เสาร์)
            'stellium' => ['1962-02-04 12:00', null, null],
        ];

        $render = new ReflectionMethod(FortuneChartService::class, 'buildNatalPngChart');
        foreach ($cases as $label => [$ymd, $hour, $province]) {
            $svc = new FortuneChartService;
            $png = $render->invoke($svc, $svc->natalChartData($ymd, 'ทดสอบ ภาษาไทย', $hour, $province));

            $size = getimagesizefromstring($png);
            $this->assertNotFalse($size, "{$label}: ต้องได้ PNG ที่เปิดได้");
            $this->assertSame([1000, 1000], [$size[0], $size[1]], $label);
            $this->assertSame('image/png', $size['mime'], $label);
        }

        $stellium = (new FortuneChartService)->natalChartData('1962-02-04 12:00', 'ทดสอบ');
        $this->assertGreaterThanOrEqual(6, max(array_map('count', $stellium['planetPositions'])), 'เคสนี้ต้องมีดาวกองช่องเดียวจริง');
    }

    // ─────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────

    /** @return array<string, array{0:string, 1:float|null, 2:string|null, 3:string}> [วันเกิด, เวลาจากคำถาม, จังหวัด, ฐานที่คาด] */
    private function anchoredCases(): array
    {
        $wednesday = $this->firstWeekday(Carbon::WEDNESDAY);

        return [
            'เวลาจาก DB' => ['1990-05-15 08:30', null, null, 'lagna'],
            'เวลา+จังหวัดจากคำถาม' => ['1990-05-15', 8.5, 'เชียงใหม่', 'lagna'],
            'เวลาจากคำถามชนะเวลาในสตริง' => ['1990-05-15 23:10', 8.5, 'ภูเก็ต', 'lagna'],
            'พุธกลางคืน' => [$wednesday.' 20:00', null, null, 'lagna'],
            'ก่อนย่ำรุ่ง' => [$wednesday.' 02:00', null, 'สงขลา', 'lagna'],
            'จันทร์ลัคน์' => [$this->findBirthDayWithBasis('moon'), null, null, 'moon'],
        ];
    }

    /**
     * บรรทัดดาวกำเนิดในพรอมต์ ("- ☉ อาทิตย์ ราศีเมษ 12° · ภพ3 …") → [ชื่อดาว => [ราศี, ภพ|null]]
     *
     * @return array<string, array{0:string, 1:int|null}>
     */
    private function promptHouses(string $text): array
    {
        preg_match_all('/^- \S+ (\S+) ราศี(\S+) +\d+°(?: · ภพ(\d+))?/mu', $text, $m, PREG_SET_ORDER);

        $out = [];
        foreach ($m as $row) {
            $out[$row[1]] = [$row[2], isset($row[3]) && $row[3] !== '' ? (int) $row[3] : null];
        }

        return $out;
    }

    /** [ชื่อดาว => [ราศี, ภพ]] → [ภพ => [คีย์ดาวของตัววาด]] */
    private function housesByKey(array $promptHouses, array $data): array
    {
        $out = [];
        foreach ($promptHouses as $name => [, $house]) {
            $this->assertNotNull($house, "พรอมต์ต้องมีภพของ{$name}");
            $out[$house][] = $this->keyByName($data, $name);
        }

        return $out;
    }

    private function keyByName(array $data, string $name): string
    {
        foreach ($data['planets'] as $key => $p) {
            if ($p['name'] === $name) {
                return $key;
            }
        }

        $this->fail("ตัววาดไม่มีดาว {$name}");
    }

    /** เทียบแบบไม่สนลำดับในช่อง + ตัดช่องว่างทิ้ง */
    private function normalise(array $houses): array
    {
        $out = [];
        foreach ($houses as $h => $keys) {
            if ($keys !== []) {
                sort($keys);
                $out[(int) $h] = $keys;
            }
        }
        ksort($out);

        return $out;
    }

    /** ค่าช่อง "ดาวมิตร: … |" ใน {zodiac_info} */
    private function relationField(string $info, string $label): string
    {
        $this->assertSame(1, preg_match('/'.$label.': ([^|]+) \|/u', $info, $m), "ไม่มีช่อง {$label}: {$info}");

        return trim($m[1]);
    }

    /** "ดาวพฤหัสบดี, ดาวอังคาร" / "เสาร์" → ['พฤหัสบดี', 'อังคาร'] */
    private function relationNames(string $raw): array
    {
        return array_values(array_filter(array_map(
            fn (string $x) => (string) preg_replace('/^ดาว/u', '', trim($x)),
            preg_split('/[,+]/u', $raw) ?: []
        ), fn (string $x) => $x !== '' && $x !== '-'));
    }

    /** พรอมต์ใบจริงของข้อ 1 — เส้นเดียวกับ processPaymentConfirmed (จังหวัดที่รู้แล้วส่งทั้ง 2 ทาง) */
    private function firstPaidPrompt(string $birthDate, string $question, ?string $knownProvince): string
    {
        $perQuestion = $this->invoke($this->conversation(implode("\n", self::DEEP_TEMPLATE)), 'buildPerQuestionDeepPrompt', [
            ['name' => 'ทดสอบ', 'gender' => 'female'], $question, 1, 1, $birthDate, [], null, $knownProvince,
        ]);

        $ai = (new ReflectionClass(FortuneAIService::class))->newInstanceWithoutConstructor();
        $ai->withBirthProvince($knownProvince);

        return $this->invoke($ai, 'buildPrompt', [[$question], ['name' => 'ทดสอบ'], null, $perQuestion, $birthDate]);
    }

    /**
     * เวลาเกิด (15 พ.ค. 2533) ที่ลัคนาของ $province ต่างราศีกับทุกที่ใน $others
     * — ไม่ฮาร์ดโค้ดเวลา เพราะขึ้นกับสูตรลัคนา ถ้าลัคนาเท่ากันทุกที่ เทสต์จะผ่านแม้จังหวัดไม่ถูกใช้
     *
     * @param  array<int, string|null>  $others  null = ไม่ทราบจังหวัด (พิกัดกลาง)
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

        $this->fail("ไม่พบเวลาเกิดที่ลัคนาของ {$province} ต่างจาก ".implode('/', array_map('strval', $others)));
    }

    /** ไล่หาวันเกิด (ไม่รู้เวลา) ที่ฐานนับภพเป็นแบบที่ต้องการ — ขึ้นกับจันทร์จริง ไม่ฮาร์ดโค้ด */
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

    /** ตัววาดที่ไม่วาด/ไม่เขียนไฟล์จริง — เก็บสิ่งที่ส่งให้ตัววาดและชื่อไฟล์ไว้ตรวจ */
    private function capturingChartService(): FortuneChartService
    {
        return new class extends FortuneChartService
        {
            public ?array $rendered = null;

            public ?string $savedPrefix = null;

            protected function gdAvailable(): bool
            {
                return true;
            }

            protected function buildNatalPngChart(array $d): string
            {
                $this->rendered = $d;

                return 'PNG';
            }

            protected function saveChartAsImage(string $pngData, string $prefix): ?string
            {
                $this->savedPrefix = $prefix;

                return "https://example.test/fortune/charts/{$prefix}.png";
            }
        };
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
