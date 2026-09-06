<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Services\Fortune\CelticCrossConversationTrait;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🃏 ด่าน "ขอวันเกิด" ของ Celtic 99 — ข้ามวันเกิดแล้วต้องยังได้ "พื้นดวงเปิดตัว" (Q1)
 *
 * เคสจริงที่ทำให้ต้องมีเทสต์นี้ — FTU-260906-K2263 (reading 12441, Tim Nualjun 2026-09-06):
 *   12:27:29 เปิดไพ่ครบ 10 ใบ → ระบบขอวันเกิด
 *   12:28:59 ลูกค้าพิมพ์ "ข้าม"
 *     → แม่หมอตอบ "ได้ค่ะ ไม่เป็นไร — แม่หมอจะอ่านจากพลังไพ่ทั้ง 10 ใบให้ก็แม่นได้เช่นกัน"
 *       แล้ว **ไม่อ่านอะไรเลย** ถามกลับว่าอยากดูเรื่องอะไร
 *   12:29:21 ลูกค้าพิมพ์ "การเงิน" → กลายเป็น seq 1 = คำถามของลูกค้าเอง
 *   ⇒ พื้นดวงเปิดตัว (1500-3000 ตัว ของแถมที่จ่าย 99 มาแล้ว) หายทั้งใบ
 *
 * วัดบน prod (25 ส.ค.–6 ก.ย. 2569): 70 บิล Celtic — 6 บิลไม่มีพื้นดวงเป็น seq 1
 *   4 ใน 6 มาจากเส้นนี้ (birth_date = NULL)
 *
 * บทเรียนที่ล็อกไว้: **ข้อความที่บอทสัญญาไว้ ต้องมีโค้ดทำจริง**
 *   ("จะอ่านจากพลังไพ่ทั้ง 10 ใบให้" แล้วไม่อ่าน = ลูกค้าเสียของที่จ่ายมาแล้วแบบเงียบ ๆ)
 *
 * ⚠️ เทสต์นี้เรียก handleCelticBirthdateStep() ตัวจริง — เพราะบั๊กอยู่ที่ *การเดินทาง*
 *   ของข้อความ (array = จบที่ด่าน / string = ไหลเข้า askQuestion) ไม่ใช่ที่ตัวแยกแยะคำว่า "ข้าม"
 *
 * ⚠️ ไม่ใช้ RefreshDatabase — FortuneReading ที่ยังไม่ save (exists=false)
 *   ⇒ setConversationState() → update() คืน false ทันที ไม่แตะ DB
 *   ส่วนเมธอดที่แตะ DB/service จริง (parseBirthDate, celticBaseChartQuestion,
 *   parkCelticPendingContext) ถูก override ในคลาสทดสอบเพื่อ "ดัก" ว่าข้อความไปลงทางไหน
 */
class CelticSkipBirthdateBaseChartTest extends TestCase
{
    /**
     * สตริงหมายจำแทนคำถามพื้นดวงสังเคราะห์ — ทำให้เห็นชัดว่าอะไรถูกต่อท้าย
     *
     * public เพราะ anonymous class ด้านล่างอ่านคอนสแตนต์ private ของคลาสนี้ไม่ได้
     */
    public const BASE_CHART = '[[BASECHART]]';

    private ReflectionMethod $birthdateStep;

    private object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class
        {
            use CelticCrossConversationTrait;

            /** @var array<int, string> ข้อความที่ถูก park ไว้ (แทน DB) */
            public array $parked = [];

            // เมธอดที่ประกาศในตัวคลาส ชนะเมธอดจาก trait — ตัดขา DB/service ออก
            protected function celticBaseChartQuestion(FortuneReading $reading): string
            {
                return CelticSkipBirthdateBaseChartTest::BASE_CHART;
            }

            // parseBirthDate ตัวจริงอยู่ที่ FortuneConversationService (+ AI fallback)
            //   เทสต์นี้สนใจเฉพาะ "เส้นที่อ่านวันเกิดไม่ออก" → คืน null เสมอ
            protected function parseBirthDate(string $text): ?string
            {
                return null;
            }

            protected function parkCelticPendingContext(FortuneReading $reading, string $text): void
            {
                $this->parked[] = $text;
            }
        };

        $this->birthdateStep = new ReflectionMethod($this->subject, 'handleCelticBirthdateStep');
        $this->birthdateStep->setAccessible(true);
    }

    /**
     * เดินด่านวันเกิดด้วยข้อความหนึ่งข้อความ
     *
     * @return array|string array = ด่านจบเอง (ตอบกลับลูกค้าเลย) / string = ไหลเข้า askQuestion
     */
    private function step(string $text, int $attemptsSoFar = 0)
    {
        $this->subject->parked = [];

        $reading = $this->newInMemoryReading();
        $reading->id = 12441;
        $reading->platform = 'facebook';
        $reading->setConversationState('celtic_birthdate_pending', true);
        $reading->setConversationState('celtic_birthdate_attempts', $attemptsSoFar);

        $result = $this->birthdateStep->invoke($this->subject, $reading, $text);

        // แนบ reading กลับมาให้ assertion ตรวจธงได้
        $this->lastReading = $reading;

        return $result;
    }

    /**
     * FortuneReading ที่เก็บ state ได้จริงแต่ไม่แตะ DB
     *
     * ⚠️ ใช้ `new FortuneReading` เปล่า ๆ ไม่ได้: setConversationState() เรียก update()
     *   ซึ่ง Eloquent คืน false ทันทีเมื่อ exists=false **โดยไม่ fill attribute**
     *   ⇒ ธงทุกตัวอ่านกลับมาเป็น null → เทสต์ "ผ่าน/ตก" ด้วยเหตุผลผิด
     *   (เทสต์รุ่นแรกของไฟล์นี้เจอกับดักนี้จริง — attempts ไม่เคยขึ้น ด่านเลยไม่เคยครบ 2)
     */
    private function newInMemoryReading(): FortuneReading
    {
        return new class extends FortuneReading
        {
            /** เขียนลงหน่วยความจำแทน DB — คืน true เหมือนบันทึกสำเร็จ */
            public function update(array $attributes = [], array $options = [])
            {
                foreach ($attributes as $key => $value) {
                    $this->setAttribute($key, $value);
                }

                return true;
            }
        };
    }

    private ?FortuneReading $lastReading = null;

    /**
     * 🎯 หัวใจของบั๊ก: "ข้าม" ต้องได้พื้นดวง ไม่ใช่คำถามกลับ
     *
     * ก่อนแก้: คืน array (action=celtic_ask_birthdate) → จบที่ด่าน → พื้นดวงหาย
     * หลังแก้: คืน string = คำถามพื้นดวง → ไหลเข้า askQuestion → ลูกค้าได้ Q1
     */
    public function test_skip_birthdate_still_generates_base_chart(): void
    {
        foreach (['ข้าม', 'ไม่ทราบ', 'ไม่ทราบวันเกิดค่ะ', 'จำไม่ได้', 'ดูจากไพ่เลยค่ะ'] as $text) {
            $result = $this->step($text);

            $this->assertIsString(
                $result,
                "ข้อความ \"{$text}\" ต้องไหลเข้า askQuestion เป็นคำถามพื้นดวง ไม่ใช่จบที่ด่านแล้วถามกลับ"
            );
            $this->assertStringContainsString(
                self::BASE_CHART,
                $result,
                "ข้อความ \"{$text}\" ต้องได้คำถามพื้นดวงเปิดตัว"
            );
        }
    }

    /** ธงที่ทำให้ prompt สลับเป็นโหมดพื้นดวงเปิดตัว ต้องถูกตั้งจริง (ไม่งั้นได้ Q&A ธรรมดา 800-1500) */
    public function test_skip_birthdate_sets_base_chart_flag(): void
    {
        $this->step('ข้าม');

        $this->assertTrue(
            (bool) $this->lastReading->getConversationState('celtic_base_chart'),
            'ธง celtic_base_chart ต้องติด — ไม่งั้น buildFollowupPrompt ไม่ override เป็นพื้นดวงเปิดตัว'
        );
        $this->assertTrue(
            (bool) $this->lastReading->getConversationState('celtic_birthdate_skipped'),
            'ธง celtic_birthdate_skipped ต้องติด — กันด่านวันเกิดกลับมาถามซ้ำ'
        );
        $this->assertFalse(
            (bool) $this->lastReading->getConversationState('celtic_birthdate_pending'),
            'ธง celtic_birthdate_pending ต้องถูกปิด — ข้อความถัดไปคือคำถามจริง'
        );
    }

    /**
     * 🔕 nudge 1 นาที ("พร้อมพิมพ์คำถามหรือยัง") ต้องไม่ถูกตั้ง
     *
     * ตอนนี้พื้นดวงยิงทันที (gen 30-90 วิ) → nudge จะไปทับคำทำนายที่กำลังจะออก
     * (parity กับเส้นที่ได้วันเกิด ซึ่งไม่เคยตั้งธงนี้)
     */
    public function test_skip_birthdate_does_not_arm_ready_nudge(): void
    {
        $this->step('ข้าม');

        $this->assertEmpty(
            $this->lastReading->getConversationState('pro_session_ready_at'),
            'ห้ามตั้ง pro_session_ready_at — พื้นดวงกำลังจะยิง nudge จะไปทับ'
        );
    }

    /**
     * 🆘 "ไม่ทราบวันเกิด" + คำถามจริงในข้อความเดียวกัน → คำถามต้องถูกยัดลง *ตัวคำถาม*
     *
     * บทเรียนเดียวกับเคส 12386 ที่ด่านเวลาเกิด: park อย่างเดียวไม่พอ เพราะพรอมต์พื้นดวง
     * มี section mandate + must gate ของตัวเอง ⇒ โมเดลทิ้งกฎที่ไม่มี gate บังคับ
     */
    public function test_skip_with_real_question_carries_question_into_prompt(): void
    {
        $q = 'ไม่ทราบวันเกิดค่ะ อยากรู้ว่าเรื่องงานปีนี้จะดีขึ้นไหมคะ';
        $result = $this->step($q);

        $this->assertIsString($result);
        $this->assertStringContainsString(self::BASE_CHART, $result, 'ต้องยังได้พื้นดวงเปิดตัว');
        $this->assertStringContainsString(
            'เรื่องงานปีนี้จะดีขึ้นไหม',
            $result,
            'คำถามจริงต้องอยู่ใน *ตัวคำถาม* ไม่ใช่แค่ park เป็นบริบท'
        );
    }

    /** อีโมจิห้ามหลุดเข้าไปในคำถามที่ต่อท้าย — must gate เช็ค str_contains บนคำตอบทั้งก้อน */
    public function test_carried_question_adds_no_emoji_to_prompt(): void
    {
        $result = $this->step('ไม่ทราบวันเกิดค่ะ อยากรู้ว่าเรื่องงานจะดีขึ้นไหมคะ');

        $appended = str_replace(self::BASE_CHART, '', (string) $result);
        $this->assertSame(
            0,
            preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $appended),
            'ส่วนที่ต่อท้ายห้ามมีอีโมจิ — จะไปหลอก must gate ของพื้นดวงว่า "ผ่าน" ทั้งที่เซคชั่นหาย'
        );
    }

    /**
     * 🃏 ครบ 2 ครั้งแล้วยังอ่านวันเกิดไม่ออก → ต้องได้ทั้ง **พื้นดวง + คำถามของลูกค้า**
     *
     * คนกลุ่มนี้ *ไม่ได้ขอข้าม* — พยายามบอกวันเกิดแล้วระบบอ่านไม่ออกเอง
     * (prod 8 มิ.ย.–6 ก.ย. 2569: 27 ใบจาก 85 ใบที่ไม่ได้พื้นดวง มาจากเส้นนี้)
     *
     * ⚠️ ห้ามลดของเดิม: ก่อนหน้านี้ข้อความนั้นถูกเอาไปตอบเป็นคำถามจริง — ต้องยังได้ตอบอยู่
     */
    public function test_two_failed_attempts_with_text_gets_base_chart_and_keeps_question(): void
    {
        $result = $this->step('เจ้าหนี้เขาไม่ยอมค่ะ เขาจะเอาให้ครบ 63,000 บาทค่ะ', 1);

        $this->assertIsString($result, 'ครบ 2 ครั้งต้องไหลเข้า askQuestion ไม่ทิ้งข้อความลูกค้า');
        $this->assertStringContainsString(self::BASE_CHART, $result, 'ต้องได้พื้นดวงเปิดตัวด้วย');
        $this->assertStringContainsString(
            'เจ้าหนี้เขาไม่ยอม',
            $result,
            'ข้อความเดิมที่เคยถูกตอบเป็นคำถาม ต้องยังอยู่ในคำถาม (ห้ามลดของที่ลูกค้าเคยได้)'
        );
        $this->assertTrue((bool) $this->lastReading->getConversationState('celtic_base_chart'));
    }

    /**
     * 🎂 ข้อความที่หน้าตาเป็น "ความพยายามบอกวันเกิด" → ห้ามยัดเป็นคำถาม
     *
     * ยัดเข้าไปจะได้คำถามประหลาด `คำถามคือ "เกิดธันวาคม2512"` — ตัวนี้ถูก park เป็นบริบทอยู่แล้ว
     * เคสจริง: r12359 (FTU-260905-Y4174) "เกิดธันวาคม2512" · r9467 "19.10.2503" · r7145 "วันจันทร"
     */
    public function test_failed_birthdate_text_is_not_carried_as_question(): void
    {
        // ทุกสตริงมาจาก fortune_celtic_questions บน prod จริง (r12359 · r9467 · r8641 · r7145 · r6391)
        //   + '[IMAGE_ATTACHED]' รูปเปล่าไม่มีคำบรรยาย (r7804 · r11055)
        foreach (['เกิดธันวาคม2512', '19.10.2503', '10 25', 'วันจันทร', 'ของลูก8สิงหาคมปีกุน2514ค่ะ', '[IMAGE_ATTACHED]'] as $text) {
            $result = $this->step($text, 1);

            $this->assertIsString($result);
            $this->assertStringContainsString(self::BASE_CHART, $result, "\"{$text}\" ต้องยังได้พื้นดวง");
            $this->assertSame(
                self::BASE_CHART,
                trim($result),
                "\"{$text}\" หน้าตาเป็นวันเกิด → ห้ามต่อท้ายเป็นคำถาม (park เป็นบริบทแทน)"
            );
        }
    }

    /** ตัวแยกแยะ "วันเกิดที่อ่านไม่ออก" ห้ามกินคำถามจริงที่มีตัวเลขปน */
    public function test_birthdate_detector_does_not_eat_real_questions(): void
    {
        $method = new ReflectionMethod($this->subject, 'looksLikeFailedBirthdateAttempt');
        $method->setAccessible(true);

        $questions = [
            'เจ้าหนี้เขาไม่ยอมค่ะ เขาจะเอาให้ครบ 63,000 บาทค่ะ',
            'การเงิน',
            'ปี 2570 จะได้ทำงานต่อไหมคะ',      // มีปี พ.ศ. แต่เป็นคำถาม
            'เกิดวันจันทร์ ดูเรื่องงานให้หน่อย',  // มีวันในสัปดาห์ แต่ขอให้ดู
        ];
        foreach ($questions as $q) {
            $this->assertFalse($method->invoke($this->subject, $q), "\"{$q}\" ต้องไม่ถูกมองเป็นวันเกิด");
        }

        foreach (['เกิดธันวาคม2512', '19.10.2503', '2518', 'วันจันทร', '03/04/2532'] as $bd) {
            $this->assertTrue($method->invoke($this->subject, $bd), "\"{$bd}\" ต้องถูกมองเป็นวันเกิด");
        }
    }

    /** ปุ่มค้าง/คำตอบรับ ("พร้อม") ยังต้องถูกย้ำขอวันเกิด ไม่ใช่กลายเป็นข้าม (กันบั๊ก reading 11055 กลับมา) */
    public function test_nav_ack_still_asks_birthdate_again(): void
    {
        $result = $this->step('พร้อม');

        $this->assertIsArray($result, 'ปุ่มค้างต้องได้ข้อความย้ำขอวันเกิด ไม่ใช่ยิงพื้นดวง');
        $this->assertSame('celtic_ask_birthdate', $result['action']);
    }
}
