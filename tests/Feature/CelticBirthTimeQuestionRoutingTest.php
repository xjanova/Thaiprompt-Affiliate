<?php

namespace Tests\Feature;

use App\Models\FortuneReading;
use App\Services\Fortune\CelticCrossConversationTrait;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🕛 ด่าน "ขอเวลาเกิด" ของ Celtic 99 — ข้อความลูกค้าต้องไม่หล่นทางไหนก็ตาม
 *
 * เคสจริงที่ทำให้ต้องมีเทสต์นี้ — FTU-260905-N3337 (reading 12386, ปราณี 2026-09-05):
 *   แม่หมอขอเวลาเกิด → ลูกค้าไม่เข้าใจขั้นตอน เลยพิมพ์คำถามที่ตั้งใจถามจริง ๆ มาแทน
 *   ("เอาเรื่องย้ายที่อยู่ 84 ม.4 อ.แม่ระมาด จ.ตาก จะเป็นของเราอยู่ตลอดไปไหม")
 *   → ด่านนี้ park ไว้เป็นแค่ "บริบท" แล้วทับด้วยคำถามพื้นดวงสังเคราะห์
 *   ⇒ คำตอบ 16,064 ตัวที่ส่งไป ไม่มีคำว่า ย้าย/ที่อยู่/ที่ดิน/โฉนด/ตลอดไป แม้แต่คำเดียว
 *   ⇒ ลูกค้าจ่าย 99 แล้วคำถามเดียวของทั้งบิลไม่ถูกตอบ
 *
 * บทเรียนที่ล็อกไว้: **park กัน "ข้อมูลหาย" ได้ แต่ไม่ได้ทำให้คำถาม "ถูกตอบ"**
 *   บล็อกบริบทค้างสั่ง "ถ้ามีคำถามค้าง → ตอบให้ด้วย" แต่พรอมต์พื้นดวงมี section mandate
 *   + must gate ของตัวเอง ⇒ 2 กฎขัดกันในพรอมต์เดียว โมเดลทิ้งอันที่ไม่มี gate บังคับ
 *   ⇒ คำถามลูกค้าต้องไปอยู่ใน *ตัวคำถาม* เท่านั้น
 *
 * ⚠️ เทสต์นี้เรียก handleCelticBirthTimeStep() ตัวจริง — ไม่ใช่แค่ตัวแยกแยะข้อความ
 *   (เวอร์ชันแรกของเทสต์นี้เทสต์แค่ looksLikeSubstantiveCelticInput ซึ่ง "ผ่านทั้งก่อนและหลังแก้"
 *    = ล็อกอะไรไม่ได้เลย — บั๊กอยู่ที่ *การเดินทาง* ไม่ใช่ที่ตัวแยกแยะ)
 *
 * ⚠️ ไม่ใช้ RefreshDatabase — ใช้ FortuneReading ที่ยังไม่ save (exists=false)
 *   ⇒ setConversationState() → update() → คืน false ทันที ไม่แตะ DB
 *   ส่วน 2 เมธอดที่แตะ DB/service จริง (celticBaseChartQuestion, parkCelticPendingContext)
 *   ถูก override ในคลาสทดสอบ เพื่อ "ดัก" ว่าข้อความไปลงทางไหน
 */
class CelticBirthTimeQuestionRoutingTest extends TestCase
{
    /**
     * สตริงหมายจำแทนคำถามพื้นดวงสังเคราะห์ — ทำให้เห็นชัดว่าอะไรถูกต่อท้าย
     *
     * public เพราะ anonymous class ด้านล่างอ่านคอนสแตนต์ private ของคลาสนี้ไม่ได้
     */
    public const BASE_CHART = '[[BASECHART]]';

    private ReflectionMethod $birthTimeStep;

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
                return CelticBirthTimeQuestionRoutingTest::BASE_CHART;
            }

            protected function parkCelticPendingContext(FortuneReading $reading, string $text): void
            {
                $this->parked[] = $text;
            }
        };

        $this->birthTimeStep = new ReflectionMethod($this->subject, 'handleCelticBirthTimeStep');
        $this->birthTimeStep->setAccessible(true);
    }

    /** เดินด่านเวลาเกิดด้วยข้อความหนึ่งข้อความ → คืนคำถามที่จะไหลเข้า askQuestion */
    private function step(string $text): string
    {
        $this->subject->parked = [];

        $reading = new FortuneReading;   // ยังไม่ save → exists=false → update() ไม่แตะ DB
        $reading->id = 12386;

        return (string) $this->birthTimeStep->invoke($this->subject, $reading, $text);
    }

    /**
     * 🎯 เคสต้นเหตุ — ข้อความจริงจาก reading 12386
     *
     * ถ้าเทสต์นี้แดง = บั๊กเดิมกลับมา ลูกค้าจ่าย 99 แล้วคำถามหาย
     */
    public function test_คำถามจริงที่ลูกค้าพิมพ์แทนเวลาเกิด_ต้องอยู่ในตัวคำถาม(): void
    {
        $จากบิลจริง = 'เอาเรื่องย้ายที่อยู่84ม.4 อ.แม่ระมาดจ.ตาก จะเป็นของเราอยู่ตลอดไปไหม';

        $ผลลัพธ์ = $this->step($จากบิลจริง);

        $this->assertStringContainsString(
            $จากบิลจริง,
            $ผลลัพธ์,
            'คำถามของ FTU-260905-N3337 ต้องอยู่ใน "ตัวคำถาม" — บริบทค้างอย่างเดียวแพ้ section mandate'
        );
        $this->assertStringContainsString(self::BASE_CHART, $ผลลัพธ์, 'พื้นดวงต้องยังอยู่ครบ');
        $this->assertSame([], $this->subject->parked, 'เลื่อนเป็นคำถามแล้ว ห้าม park ซ้ำ (กัน re-inject ค้างทุกเทิร์น)');
    }

    /**
     * 🐶 เคส FTU-260904-S9843 — "น้องหมา" คำเดียวในทั้งบิลอยู่ตรงด่านนี้
     */
    public function test_คำถามเรื่องน้องหมา_ต้องอยู่ในตัวคำถาม(): void
    {
        $ผลลัพธ์ = $this->step('น้องหมาตัวนี้ยังมีชีวิตอยู่ไหม');

        $this->assertStringContainsString('น้องหมาตัวนี้ยังมีชีวิตอยู่ไหม', $ผลลัพธ์);
    }

    /**
     * 🚨 กับดักลำดับด่าน — "ไม่ทราบ" + คำถาม อยู่ในประโยคเดียวกัน
     *
     * ถ้าเช็ค /ไม่ทราบ|จำไม่ได้/ ก่อน คำถามจะหายแบบเดียวกับบั๊กเดิมเป๊ะ
     * (ของเดิมแย่กว่า: เข้า branch declined แล้วไม่ park ด้วยซ้ำ = หายสนิท)
     */
    public function test_ไม่ทราบเวลาเกิดแต่มีคำถามพ่วง_คำถามต้องไม่หาย(): void
    {
        $ผลลัพธ์ = $this->step('ไม่ทราบค่ะ แล้วเรื่องงานจะดีไหม');

        $this->assertStringContainsString('เรื่องงานจะดีไหม', $ผลลัพธ์);
    }

    /**
     * ⏰ คำตอบเรื่องเวลาจริง ๆ ต้องไม่ปนเข้าไปในคำถาม
     *
     * ถ้าหลุด → พรอมต์จะกลายเป็น "ต้องตอบคำถามที่เจ้าชะตาถามมา: 06:30"
     */
    public function test_คำตอบเวลาเกิดปกติ_ต้องได้พื้นดวงเปล่า(): void
    {
        foreach (['ตี 5', '06:30', '6 โมงเช้า', 'ประมาณ 6 โมงเช้า', 'เที่ยงวัน'] as $เวลา) {
            $this->assertSame(
                self::BASE_CHART,
                $this->step($เวลา),
                "\"{$เวลา}\" เป็นคำตอบเรื่องเวลา ห้ามต่อท้ายเป็นคำถาม"
            );
        }
    }

    /** ลูกค้าบอกว่าจำเวลาไม่ได้ (ไม่มีคำถามพ่วง) → พื้นดวงเปล่า ไม่ park */
    public function test_บอกว่าไม่ทราบเวลา_ได้พื้นดวงเปล่าและไม่เก็บบริบท(): void
    {
        $this->assertSame(self::BASE_CHART, $this->step('ไม่ทราบค่ะ จำไม่ได้'));
        $this->assertSame([], $this->subject->parked);
    }

    /**
     * 📌 เนื้อความที่ไม่ใช่รูปคำถาม → ต้อง park เป็นบริบท ไม่ใช่ต่อท้ายเป็นคำถาม
     *
     * ถ้าเลื่อนเป็นคำถามจะได้พรอมต์ประหลาด
     * ("ต้องตอบคำถามที่เจ้าชะตาถามมา: เกิดที่โรงพยาบาลแม่สอด")
     */
    public function test_บริบทที่ไม่ใช่คำถาม_ต้อง_park_ไม่ใช่ต่อท้ายคำถาม(): void
    {
        $บริบท = 'เกิดที่โรงพยาบาลแม่สอด จังหวัดตาก แม่บอกว่าตอนเช้า';

        $this->assertSame(self::BASE_CHART, $this->step($บริบท), 'ไม่ใช่รูปคำถาม → ห้ามต่อท้าย');
        $this->assertSame([$บริบท], $this->subject->parked, 'แต่ต้อง park ไว้ ห้ามทิ้ง');
    }

    /**
     * ✂️ คำถามยาวผิดปกติ (วางข้อความมาทั้งก้อน) ต้องถูกตัดที่ 300 ตัว
     *
     * กันพรอมต์บวมจนเบียดสเปกพื้นดวงจนเซคชั่นหาย
     */
    public function test_คำถามยาวเกิน_ต้องถูกตัดที่300ตัว(): void
    {
        $ยาว = 'เรื่องที่ดินจะเป็นของเราไหม '.str_repeat('ก', 400);

        $ผลลัพธ์ = $this->step($ยาว);

        $this->assertStringContainsString('เรื่องที่ดินจะเป็นของเราไหม', $ผลลัพธ์);
        $this->assertStringNotContainsString($ยาว, $ผลลัพธ์, 'ต้องไม่ยัดทั้งก้อน');
        $this->assertStringContainsString(mb_substr($ยาว, 0, 300), $ผลลัพธ์);
    }

    /**
     * 🚫 ข้อความที่โค้ดต่อท้ายเอง ต้องไม่มีอีโมจิ
     *
     * must gate ของพื้นดวงใช้ str_contains หาอีโมจิหัวข้อบนคำตอบทั้งก้อน
     * ⇒ อีโมจิแปลกปลอมที่ไหลจากคำถามไปโผล่ในคำตอบ ทำให้ด่านรายงาน "ผ่าน" ทั้งที่เซคชั่นหาย
     * (กับดักเดียวกับ rule_own_appended_text_can_spoof_downstream_gate)
     */
    public function test_ข้อความที่ต่อท้ายเอง_ต้องไม่มีอีโมจิ(): void
    {
        $ผลลัพธ์ = $this->step('ปีนี้การเงินจะดีขึ้นไหมคะ');

        // ตัดส่วนที่มาจากลูกค้าและ base chart ออก เหลือเฉพาะที่โค้ดเราเขียนเอง
        $ของเราเอง = str_replace([self::BASE_CHART, 'ปีนี้การเงินจะดีขึ้นไหมคะ'], '', $ผลลัพธ์);

        $this->assertSame(
            0,
            preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]/u', $ของเราเอง),
            'ข้อความที่โค้ดต่อท้ายเอง ห้ามมีอีโมจิ — จะไปหลอก must gate ของพื้นดวง'
        );
    }

    /**
     * 🔤 รูปประโยคคำถามไทยหลายแบบ — ลูกค้าเขียนไม่เหมือนกันสักคน
     */
    public function test_รูปประโยคคำถามหลายแบบ_ต้องเข้าคำถามหมด(): void
    {
        $คำถาม = [
            'ปีนี้การเงินจะดีขึ้นไหมคะ',
            'เขาจะกลับมาหรือเปล่า',
            'ต้องแก้ยังไงดีคะ',
            'จะได้งานเมื่อไหร่',
            'อยากรู้เรื่องคดีความที่ค้างอยู่',
            'ช่วยดูเรื่องลูกชายให้หน่อย',
        ];

        foreach ($คำถาม as $q) {
            $this->assertStringContainsString($q, $this->step($q), "\"{$q}\" ต้องเข้าไปอยู่ในคำถาม");
        }
    }
}
