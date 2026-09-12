<?php

namespace Tests\Unit\Services;

use App\Models\FortuneReading;
use App\Services\Fortune\ThaiAstrologyService;
use App\Services\FortuneAIService;
use App\Services\FortuneConversationService;
use App\Support\ThaiProvinces;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🕛🗺️ อินพุตผังดวงของบิลดูดวง 39 ต้องเป็น "ชุดเดียวทั้งบิล" (2026-09-12 — จับผีรอบรูปผังจริง)
 *
 * ที่มา 2 บั๊ก (ต้นเหตุเก่า เพิ่งมองเห็นเมื่อรูปผังวาดจากผังจริง):
 *   1. แต่ละข้ออ่านเวลาเกิดจากข้อความข้อตัวเอง ⇒ ข้อ 2 "แฟนเกิดตี 2 จะไปรอดไหม" ถูกนับเป็นเวลาเกิด
 *      ของเจ้าชะตา ⇒ คำตอบข้อ 2 ใช้ลัคนา/วันทางโหรคนละชุดกับข้อ 1 และรูปผังในบิลเดียวกัน
 *   2. ThaiProvinces::resolve() ไม่ต้องมีคำว่าเกิด ⇒ "ย้ายไปทำงานภูเก็ต" = เกิดภูเก็ต
 *      (ทั้งพรอมต์ 39 และบล็อกดวงของ Celtic 99 — ส่วนรูป Celtic ใช้ค่าจาก DB ⇒ ขัดกัน)
 *
 * ไม่แตะ DB — สร้าง service ด้วย newInstanceWithoutConstructor
 */
class Deep39PerBillChartInputsTest extends TestCase
{
    /** 15 พ.ค. 2533 = วันอังคาร · ตี 2 ของวันนั้นทางโหรคือ "จันทร์กลางคืน" */
    private const BIRTH = '1990-05-15 08:30';

    private const Q1 = 'ขอดูพื้นดวงโดยรวมของเจ้าชะตา';

    private const Q2 = 'แฟนเกิดตี 2 จะไปรอดไหมคะ';

    private const TEMPLATE = "- {zodiac_info}\n{planet_positions}\n{transit_info}\nคำถาม: {question}";

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
    // 1. จังหวัดจากคำถามอิสระต้องมีคำบ่งชี้การเกิด
    // ─────────────────────────────────────────────────────────────

    public function test_birthplace_in_free_text_needs_a_birth_cue(): void
    {
        $born = [
            'หนูเกิดเวลา 08:30 น. ที่เชียงใหม่ อยากรู้เรื่องงานค่ะ' => 'เชียงใหม่',
            'เกิด 15 พ.ค. 2533 ที่จังหวัดขอนแก่นค่ะ' => 'ขอนแก่น',
            'หนูเกิดที่ขอนแก่นค่ะ' => 'ขอนแก่น',
            'บ้านเกิดอยู่อุดร' => 'อุดรธานี',
            'เกิดในเชียงราย ตอนเช้า' => 'เชียงราย',
            'เกิดวันพุธ ตอนเช้า ที่ลำปาง' => 'ลำปาง',
            'เกิดที่เชียงใหม่ แต่ตอนนี้ย้ายไปภูเก็ต' => 'เชียงใหม่',
        ];
        foreach ($born as $text => $expected) {
            $this->assertSame($expected, ThaiProvinces::resolveBirthplace($text), $text);
            $this->assertSame($expected, ThaiProvinces::forChart(null, $text), "forChart: {$text}");
        }

        $notBirthplace = [
            'อยากย้ายไปทำงานภูเก็ต จะได้ไปไหมคะ',
            'เกิด 15/5/2533 อยากรู้ว่าจะได้ย้ายไปภูเก็ตไหม',
            'จะเกิดอะไรขึ้นถ้าย้ายไปตาก',
            'แฟนอยู่เชียงใหม่ จะไปรอดไหม',
            'ไปเลยค่ะ',
            '',
        ];
        foreach ($notBirthplace as $text) {
            $this->assertNull(ThaiProvinces::resolveBirthplace($text), "ห้ามนับเป็นที่เกิด: {$text}");
            $this->assertNull(ThaiProvinces::forChart(null, $text), "forChart: {$text}");
        }

        // คำตอบกล่อง "เกิดจังหวัดไหน" ยังใช้ตัวอ่านแบบเดิม (ทั้งข้อความคือที่เกิด ไม่ต้องมีคำว่าเกิด)
        $this->assertSame('ภูเก็ต', ThaiProvinces::resolve('ภูเก็ตค่ะ'));
        // จังหวัดที่รู้แล้วยังชนะข้อความเสมอ
        $this->assertSame('เชียงใหม่', ThaiProvinces::forChart('เชียงใหม่', 'หนูเกิดที่ภูเก็ต'));
    }

    public function test_birthplace_reader_skips_third_parties_and_ambiguous_words(): void
    {
        $cases = [
            // บุคคลที่สาม / อนาคต — ไม่ใช่ที่เกิดของเจ้าชะตา
            'แฟนเกิดที่ภูเก็ต จะไปรอดไหม' => null,
            'ลูกจะคลอดที่ขอนแก่นเดือนหน้า' => null,
            'แม่เกิดที่อุดร อยากรู้ดวงแม่' => null,
            // ชื่อจังหวัดที่เป็นคำธรรมดา ต้องมีคำบอกสถานที่
            'ไม่รู้เวลาเกิดเลยค่ะ' => null,
            'ถ้าเกิดตากฝนจะไม่สบายไหม' => null,
            'หนูเกิดที่เลยค่ะ' => 'เลย',
            // สองจังหวัด = ที่พูดถึงก่อน (ไม่ใช่ชื่อที่ยาวกว่า) · ของแฟนถูกตัดอยู่แล้ว
            'หนูเกิดที่เชียงใหม่ แฟนเกิดที่นครศรีธรรมราช' => 'เชียงใหม่',
            // ประโยคบอกเกิดแบบเต็มยศ ยาว ~90 ตัวอักษรในช่วงคั่น
            'เกิดวันพฤหัสบดีที่ 15 เดือนพฤษภาคม พ.ศ. 2533 เวลาประมาณ 08:30 น. ตอนเช้า ที่อำเภอเมือง จังหวัดเชียงใหม่' => 'เชียงใหม่',
            // วิธีพูดที่พบบ่อยซึ่งรอบแรกพลาด
            'เกิดเมื่อ 5 มค 2533 ที่ขอนแก่น' => 'ขอนแก่น',
            'เกิดตีห้าครึ่ง ที่โรงพยาบาล ขอนแก่นค่ะ' => 'ขอนแก่น',
        ];

        foreach ($cases as $text => $expected) {
            $this->assertSame($expected, ThaiProvinces::resolveBirthplace($text), $text);
        }

        // บุคคลที่สามแบบมีสรรพนาม/คำขยายคั่น — lookbehind ตัวเดียวมองไม่เห็น (เคยได้ภูเก็ตเป็นที่เกิดของเจ้าชะตา)
        $thirdParty = [
            'แฟนผมเกิดที่ภูเก็ต จะไปกันรอดไหม' => null,
            'แม่ของหนูเกิดที่อุดร อยากรู้ว่าแม่จะหายป่วยไหม' => null,
            'แฟนเก่าเกิดที่ภูเก็ต จะกลับมาไหมคะ' => null,
            'ลูกพี่ลูกน้องเกิดที่ลำปาง' => null,
            'น้องหมาหนูเกิดที่เชียงราย' => null,
            // ของคนอื่นถูกตัด แต่ของเจ้าชะตาในประโยคเดียวกันยังอ่านได้
            'สามีหนูเกิดที่ภูเก็ต ส่วนหนูเกิดที่เชียงใหม่' => 'เชียงใหม่',
            // เจ้าชะตาพูดถึงตัวเอง — ต้องไม่โดนตัด
            'แม่หมอคะ หนูเกิดที่ขอนแก่นค่ะ' => 'ขอนแก่น',
            'แม่ หนูเกิดที่ขอนแก่นค่ะ' => 'ขอนแก่น',
            'น้องมิวเกิดที่เชียงใหม่ค่ะ' => 'เชียงใหม่',
            'ผมเกิดที่ภูเก็ต แฟนผมอยู่เชียงใหม่' => 'ภูเก็ต',
            'แฟนถามว่าผมเกิดที่ไหน ผมเกิดที่ลำปาง' => 'ลำปาง',
        ];
        foreach ($thirdParty as $text => $expected) {
            $this->assertSame($expected, ThaiProvinces::resolveBirthplace($text), $text);
        }

        // วิธีพูดที่เคยพลาด (recall 82% → 96% ในชุด 84 ประโยค) + ตัวกันไม่ให้ช่องคั่นใหม่พาไปจับผิด
        $phrasings = [
            'เกิดที่ รพ.มหาราช เชียงใหม่' => 'เชียงใหม่',
            'เกิดที่โรงพยาบาลศิริราช กรุงเทพ' => 'กรุงเทพมหานคร',
            'เกิดที่ อ.แม่สาย จ.เชียงราย' => 'เชียงราย',
            'เกิดและโตที่ขอนแก่น' => 'ขอนแก่น',
            'เกิดปี 2533 เป็นคนสกลนคร' => 'สกลนคร',
            'บ้านเกิดหนูอยู่ขอนแก่น' => 'ขอนแก่น',
            'เกิด 15 May 1990 ที่ภูเก็ต' => 'ภูเก็ต',
            'เรื่องที่เกิดในกรุงเทพจะจบไหม' => null,
            'บ้านเกิดแฟนอยู่ขอนแก่น' => null,
            'เกิด 15/5/33 เป็นคนทำงานที่ภูเก็ต' => null,
            'เกิด 5 พ.ค. 33 อยู่ภูเก็ต อยากย้ายงาน' => null,
        ];
        foreach ($phrasings as $text => $expected) {
            $this->assertSame($expected, ThaiProvinces::resolveBirthplace($text), $text);
        }

        // จับผีก่อนพุช (reviewer อิสระ 2026-09-12) — ช่องคั่นใหม่เคยกลืนกริยาที่พิมพ์ติดกัน / เลือกชื่อยาวก่อนชื่อที่พูดก่อน
        $reviewed = [
            // ชื่ออำเภอ/รพ. ต้องจบด้วยช่องว่าง — ไม่งั้น "อยู่/ย้าย/ทำงาน" ที่พิมพ์ติดถูกกลืนไปด้วย
            'เกิดที่รพ.ศิริราชตอนนี้อยู่ภูเก็ต' => null,
            'เกิดอำเภอเมืองย้ายมาอยู่กรุงเทพ' => null,
            'เกิดที่อ.เมืองอยู่ภูเก็ต' => null,
            'เกิดเขตบางรักแต่ทำงานภูเก็ต' => null,
            // สองจังหวัดตามคำว่าเกิดตัวเดียวกัน = ที่พูดก่อน · บอกที่เกิดตรงๆ ชนะ "เป็นคน…"
            'เกิดที่อำเภอเมืองลำปาง เติบโตที่ภูเก็ต' => 'ลำปาง',
            'เกิดที่ อ.เมืองลำปาง ปี 33 ที่ภูเก็ต' => 'ลำปาง',
            'เกิดปี 33 เป็นคนภูเก็ตแต่เกิดที่ตรัง' => 'ตรัง',
            // เจ้าชะตาพูดถึงตัวเอง — "เป็น…" + คำขยาย · คำเรียกคนฟังแล้วเว้นวรรค
            'หนูเป็นลูกคนเล็ก เกิดที่เชียงใหม่ค่ะ' => 'เชียงใหม่',
            'ผมเป็นลูกชาย เกิดที่ภูเก็ตครับ' => 'ภูเก็ต',
            'สวัสดีครับพี่ เกิด 15 พ.ค. 2533 ที่ภูเก็ตครับ' => 'ภูเก็ต',
            'ขอบคุณค่ะน้อง เกิดที่เชียงใหม่ค่ะ' => 'เชียงใหม่',
            // บุคคลที่สามแบบมีคำตามหลังสรรพนาม
            'แฟนหนูคนนี้เกิดที่ภูเก็ต' => null,
            'แฟนหนูเค้าเกิดที่ภูเก็ต' => null,
            'ลูกสาวคนเล็กหนูเกิดที่ภูเก็ต' => null,
            'คนที่เป็นแฟนผมเกิดที่ภูเก็ต' => null,
            // "โตที่" ไม่ใช่ที่เกิด · คำอังกฤษที่ขึ้นต้นเหมือนชื่อเดือนไม่ใช่เดือน
            'เกิด 15/5/33 เติบโตที่ภูเก็ต' => null,
            'เกิด 1990 married ภูเก็ต' => null,
            'เกิด 1990 maybe ภูเก็ต' => null,
            'เกิด 15 MARCH 1990 ที่ภูเก็ต' => 'ภูเก็ต',
            // เครือ รพ.กรุงเทพ + ชื่อเมือง = อยู่เมืองนั้น ไม่ใช่กรุงเทพ
            'เกิดโรงพยาบาลกรุงเทพภูเก็ต' => 'ภูเก็ต',
            'เกิดที่ รพ.กรุงเทพเชียงใหม่ค่ะ' => 'เชียงใหม่',
            'เกิดที่รพ.กรุงเทพค่ะ' => 'กรุงเทพมหานคร',
            // อำเภอเมือง + ชื่อกำกวม
            'เกิดที่อ.เมืองตาก' => 'ตาก',
            'สถานที่เกิดคือภูเก็ต' => 'ภูเก็ต',
        ];
        foreach ($reviewed as $text => $expected) {
            $this->assertSame($expected, ThaiProvinces::resolveBirthplace($text), $text);
        }

        // ข้อความยาวที่คำนำหน้าอำเภอซ้ำๆ — เดิมชน backtrack limit แล้ว preg_match_all คืน false = ที่เกิดจริงหายเงียบ
        $this->assertSame('ภูเก็ต', ThaiProvinces::resolveBirthplace(
            'หนูเกิดที่ภูเก็ตค่ะ แล้วก็เกิด'.str_repeat('อ.เมืองต.ในเมือง ', 30)
        ));

        // คำตอบกล่อง "เกิดกี่โมง ที่จังหวัดไหน" — "ไม่รู้เวลาเกิดเลย" ห้ามกลายเป็น จ.เลย (ตัวอ่านแบบคำตอบ)
        $this->assertNull(ThaiProvinces::resolve('ไม่รู้เวลาเกิดเลยค่ะ'));
        $this->assertSame('เลย', ThaiProvinces::resolve('เกิดที่ จ.เลย'));
    }

    // ─────────────────────────────────────────────────────────────
    // 1.5 ขั้น "เก็บลง DB" ก่อนล็อก — ทางจริงของ processPaymentConfirmed
    // ─────────────────────────────────────────────────────────────

    public function test_question_two_never_overwrites_the_time_box_answer(): void
    {
        $questions = [self::Q1, self::Q2];

        // ❌ ตัวควบคุม: วิธีเดิม (สแกนทุกข้อ + ทับทุกค่า) ทับคำตอบกล่องเวลาเกิดด้วยเวลาของแฟน
        $legacy = $this->readingDouble(['birth_time' => '08:30:00', 'birth_time_source' => 'time_answer']);
        $legacy->captureStatedBirthTime(implode(' ', $questions), 'question');
        $this->assertSame('02:00', substr((string) $legacy->birth_time, 0, 5), 'ตัวควบคุม: พฤติกรรมเดิมต้องพังแบบนี้จริง');

        // ✅ ใหม่: รู้เวลาอยู่แล้ว = ไม่แตะ · ค่า "ต่อบิล" ใช้เวลาใน DB
        $reading = $this->readingDouble(['birth_time' => '08:30:00', 'birth_time_source' => 'time_answer']);
        $conv = $this->conversation(self::TEMPLATE);
        $this->invoke($conv, 'captureOwnerBirthDetailsFromFirstQuestion', [$reading, $questions]);

        $this->assertSame('08:30', substr((string) $reading->birth_time, 0, 5));
        $this->assertSame('time_answer', $reading->birth_time_source);
        $this->assertSame('1990-05-15 08:30', $reading->birthDateTimeForChart());

        $chartInputs = $this->invoke($conv, 'deepChartImageInputs', [$questions, null, $reading->birthTimeIsKnown()]);
        $this->assertNull($chartInputs['hour'], 'รู้เวลาใน DB = ให้ผังอ่านจากสตริงวันเกิด ไม่ใช่ข้อความคำถาม');

        $q2 = $this->finalPrompt(self::Q2, 2, $chartInputs, $reading->birthDateTimeForChart());
        $this->assertStringContainsString('เกิดวันอังคาร |', $q2);
        $this->assertStringNotContainsString('จันทร์ กลางคืน', $q2);
    }

    public function test_first_question_fills_only_what_is_still_unknown(): void
    {
        $this->withBirthProvinceColumn(function (): void {
            $reading = $this->readingDouble([]);
            $conv = $this->conversation(self::TEMPLATE);

            $this->invoke($conv, 'captureOwnerBirthDetailsFromFirstQuestion', [$reading, [
                'หนูเกิดเวลา 08:30 น. ที่เชียงใหม่ อยากรู้เรื่องงานค่ะ',
                'แฟนเกิดตี 2 ที่ภูเก็ต จะไปรอดไหม',
            ]]);

            $this->assertSame('08:30', substr((string) $reading->birth_time, 0, 5), 'เวลาจากข้อ 1 เท่านั้น');
            $this->assertSame('เชียงใหม่', $reading->birth_province, 'จังหวัดจากข้อ 1 ที่มีคำว่าเกิด');
            $this->assertSame('question', $reading->birth_province_source);

            // ข้อ 1 พูดถึงจังหวัดแบบไม่ใช่ที่เกิด → ไม่บันทึก
            $moved = $this->readingDouble([]);
            $this->invoke($conv, 'captureOwnerBirthDetailsFromFirstQuestion', [$moved, ['อยากย้ายไปทำงานภูเก็ต จะได้ไปไหมคะ']]);
            $this->assertNull($moved->birth_province);
        });
    }

    public function test_celtic_block_no_longer_takes_a_workplace_as_the_birthplace(): void
    {
        $astro = new ThaiAstrologyService;

        $moved = $astro->buildCelticBirthAstrologyBlock('เกิด 15/5/2533 เวลา 08:30 น. อยากย้ายไปทำงานภูเก็ต');
        $this->assertStringNotContainsString('จ.ภูเก็ต', $moved, 'ที่ทำงานไม่ใช่ที่เกิด');
        $this->assertStringContainsString('ไม่ทราบจังหวัดเกิด ใช้พิกัดกรุงเทพฯ', $moved);

        $born = (new ThaiAstrologyService)->buildCelticBirthAstrologyBlock('เกิด 15/5/2533 เวลา 08:30 น. ที่ภูเก็ต');
        $this->assertStringContainsString('เกิดที่ จ.ภูเก็ต', $born);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. ทุกข้อในบิลใช้เวลา/จังหวัดชุดเดียวกัน
    // ─────────────────────────────────────────────────────────────

    public function test_question_two_cannot_hijack_the_owner_birth_time(): void
    {
        $astro = new ThaiAstrologyService;
        $this->assertSame(2.0, $astro->extractStatedBirthHour(ThaiAstrologyService::numberedQuestionsText([self::Q2])),
            'ข้อความข้อ 2 อ่านได้เป็นเวลาตี 2 (ไม่งั้นเทสต์นี้ไม่ได้ทดสอบอะไร)');

        $conv = $this->conversation();
        $chartInputs = $this->invoke($conv, 'deepChartImageInputs', [[self::Q1, self::Q2], null]);
        $this->assertNull($chartInputs['hour'], 'ข้อ 1 ไม่ได้บอกเวลา ⇒ ใช้เวลาจาก DB ในสตริงวันเกิด');

        // ✅ ใหม่: ข้อ 2 ใช้ค่า "ต่อบิล" — เวลาเกิดยังเป็น 08:30 จาก DB (วันอังคาร)
        $locked = $this->finalPrompt(self::Q2, 2, $chartInputs);
        $this->assertStringContainsString('เกิดวันอังคาร |', $locked);
        $this->assertStringNotContainsString('จันทร์ กลางคืน', $locked, 'ข้อ 2 ห้ามได้ผังของเวลาเกิดแฟน');

        $q1 = $this->finalPrompt(self::Q1, 1, $chartInputs);
        $this->assertSame($this->lagnaLines($q1), $this->lagnaLines($locked), 'ลัคนาทุกบล็อกของข้อ 2 = ข้อ 1');

        // ❌ เดิม (ไม่ล็อก): ข้อ 2 อ่านตี 2 จากข้อความตัวเอง ⇒ วันทางโหรเลื่อนเป็นจันทร์กลางคืน
        $legacy = $this->finalPrompt(self::Q2, 2, null);
        $this->assertStringContainsString('จันทร์ กลางคืน', $legacy, 'ตัวควบคุม: พฤติกรรมเดิมต้องยังพังแบบนี้ ไม่งั้นเทสต์ไม่ได้จับอะไร');
    }

    public function test_locked_inputs_are_one_shot(): void
    {
        $ai = $this->recordingAiService();

        $ai->withChartInputs(['hour' => 8.5, 'province' => 'เชียงใหม่'])
            ->generateWithRetryAndFallback([self::Q2], null, null, '{questions}', 'deep', '1990-05-15');
        // call ถัดไปบน instance เดิม — ไม่ได้ล็อก ⇒ กลับไปอ่านข้อความแบบเดิม
        $ai->generateWithRetryAndFallback([self::Q1], null, null, '{questions}', 'deep', '1990-05-15');

        $this->assertStringContainsString('เวลาเกิด 08:30 น. · เกิดที่ จ.เชียงใหม่', $ai->prompts[0], 'ค่าที่ล็อกชนะข้อความ (ตี 2 ของแฟน)');
        $this->assertStringNotContainsString('จ.เชียงใหม่', $ai->prompts[1], 'ค่าที่ล็อกของบิลก่อนห้ามค้าง');
        $this->assertStringNotContainsString('⬆️ ลัคนา: ราศี', $ai->prompts[1], 'ไม่มีเวลาเกิด = ไม่มีลัคนา');
    }

    public function test_locked_inputs_are_normalised_the_same_way_on_both_sides(): void
    {
        $this->assertSame(
            ['hour' => null, 'province' => null],
            ThaiAstrologyService::normalizeChartInputs(['hour' => 25, 'province' => 'ดาวอังคาร'])
        );
        $this->assertSame(
            ['hour' => 8.5, 'province' => 'เชียงใหม่'],
            ThaiAstrologyService::normalizeChartInputs(['hour' => 8.5, 'province' => 'เชียงใหม่'])
        );
        $this->assertSame(['hour' => null, 'province' => null], ThaiAstrologyService::normalizeChartInputs([]));
    }

    // ─────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────

    /** พรอมต์สุดท้ายของข้อ $n — เส้นเดียวกับ processPaymentConfirmed (ล็อกทั้ง 2 ฝั่งเมื่อมี $chartInputs) */
    private function finalPrompt(string $question, int $n, ?array $chartInputs, string $birthDate = self::BIRTH): string
    {
        $perQuestion = $this->invoke($this->conversation(self::TEMPLATE), 'buildPerQuestionDeepPrompt', [
            ['name' => 'ทดสอบ'], $question, $n, 2, $birthDate, [], null, null, $chartInputs,
        ]);

        $ai = (new ReflectionClass(FortuneAIService::class))->newInstanceWithoutConstructor();
        if ($chartInputs !== null) {
            $ai->withChartInputs($chartInputs);
        }

        return $this->invoke($ai, 'buildPrompt', [[$question], ['name' => 'ทดสอบ'], null, $perQuestion, $birthDate]);
    }

    /**
     * FortuneReading ในหน่วยความจำ — update() เติมค่าในตัวแทนเขียน DB
     * (captureStatedBirthTime/Province + setConversationState เขียนผ่าน update() ทั้งหมด)
     */
    private function readingDouble(array $attributes): FortuneReading
    {
        $reading = new class extends FortuneReading
        {
            public function update(array $attributes = [], array $options = [])
            {
                $this->forceFill($attributes);

                return true;
            }
        };
        $reading->forceFill(array_merge(['birth_date' => '1990-05-15'], $attributes));

        return $reading;
    }

    /** เปิดคอลัมน์ birth_province ชั่วคราว (ตัวเช็คคอลัมน์ถาม DB — เทสต์นี้ไม่มี DB) */
    private function withBirthProvinceColumn(callable $fn): void
    {
        $prop = new \ReflectionProperty(FortuneReading::class, 'birthProvinceColumn');
        $before = $prop->getValue();
        $prop->setValue(null, true);
        try {
            $fn();
        } finally {
            $prop->setValue(null, $before);
        }
    }

    /** ทุกบรรทัดที่บอกลัคนา/ฐานนับภพ ในพรอมต์ (ผังดวงกำเนิด + ดาวจร + ผังท้ายพรอมต์) */
    private function lagnaLines(string $prompt): array
    {
        preg_match_all('/(?:ลัคนา: ราศี\S+|ภพนับจากลัคนาราศี\S+|เกิดวัน\S+(?: กลางคืน)?)/u', $prompt, $m);

        return $m[0];
    }

    private function recordingAiService(): FortuneAIService
    {
        return new class extends FortuneAIService
        {
            /** @var string[] */
            public array $prompts = [];

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
                $this->prompts[] = $this->buildPrompt($questions, $userProfile, $userPosts, $promptTemplate, $birthDate);

                return ['response' => 'ok', 'tokens_used' => 0, 'provider' => 'fake', 'model' => 'fake'];
            }
        };
    }

    private function conversation(?string $deepTemplate = null): FortuneConversationService
    {
        $ref = new ReflectionClass(FortuneConversationService::class);
        $svc = $ref->newInstanceWithoutConstructor();

        if ($deepTemplate !== null) {
            $settings = new \App\Models\FortuneTellingSetting;
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
