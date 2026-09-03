<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\ThaiAstrologyService;
use Tests\TestCase;

/**
 * ThaiAstrologyService Unit Tests
 *
 * ทดสอบการตรวจจับวันเกิดจากข้อความ + คำนวณดวงดาว (ราศี/ดาวเจ้าชนะ)
 * อ้างอิงเคสจริง: bill FTU-260530-Z4397 (ลูกค้าใส่ 27/6/1978 + คู่ปรับ 6/2/2532)
 */
class ThaiAstrologyServiceTest extends TestCase
{
    protected ThaiAstrologyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ThaiAstrologyService;
    }

    /** @test */
    public function it_parses_ad_year_date(): void
    {
        $dates = $this->service->extractBirthDatesFromText('เกิด 27/6/1978');

        $this->assertCount(1, $dates);
        $this->assertSame('1978-06-27', $dates[0]['ymd']);
    }

    /** @test */
    public function it_converts_be_year_to_ad(): void
    {
        // 6/2/2532 (พ.ศ.) → 6 ก.พ. 1989 — แก้บั๊ก Deep 39฿ ที่เคยเก็บเป็น 1989-03-06
        $dates = $this->service->extractBirthDatesFromText('คู่ปรับเกิด 6/2/2532');

        $this->assertCount(1, $dates);
        $this->assertSame('1989-02-06', $dates[0]['ymd']);
    }

    /** @test */
    public function it_parses_real_bill_input_two_people_in_order(): void
    {
        // ข้อความจริงจากบิล FTU-260530-Z4397
        $text = 'เกิด27/6/1978.เวลา12.00 และคู่ปรับเกิด 6/2/2532.ลักษณะนิสัยไปกันได้ไหม';
        $dates = $this->service->extractBirthDatesFromText($text);

        $this->assertCount(2, $dates);
        $this->assertSame('1978-06-27', $dates[0]['ymd']);
        $this->assertSame('1989-02-06', $dates[1]['ymd']);
    }

    /** @test */
    public function it_does_not_treat_time_as_date(): void
    {
        // "เวลา12.00" มี 2 กลุ่มตัวเลข → ไม่ใช่วันที่ (ต้องมีปี 4 หลัก)
        $dates = $this->service->extractBirthDatesFromText('นัดเวลา 12.00 น.');

        $this->assertCount(0, $dates);
    }

    /** @test */
    public function it_normalizes_thai_digits(): void
    {
        // เลขไทย ๖/๒/๒๕๓๒ → 1989-02-06
        $dates = $this->service->extractBirthDatesFromText('เกิด ๖/๒/๒๕๓๒');

        $this->assertCount(1, $dates);
        $this->assertSame('1989-02-06', $dates[0]['ymd']);
    }

    /** @test */
    public function it_rejects_invalid_dates(): void
    {
        // เดือน 13 / วัน 32 = ไม่มีจริง
        $dates = $this->service->extractBirthDatesFromText('รหัส 32/13/2000');

        $this->assertCount(0, $dates);
    }

    /** @test */
    public function it_deduplicates_same_date(): void
    {
        $dates = $this->service->extractBirthDatesFromText('เกิด 27/6/1978 ... ย้ำอีกที 27/6/1978');

        $this->assertCount(1, $dates);
    }

    /** @test */
    public function it_caps_at_max_people(): void
    {
        $text = '1/1/1980 2/2/1981 3/3/1982 4/4/1983 5/5/1984';
        $dates = $this->service->extractBirthDatesFromText($text);

        $this->assertLessThanOrEqual(ThaiAstrologyService::MAX_PEOPLE, count($dates));
        $this->assertCount(3, $dates);
    }

    /**
     * ราศีต้องเป็น **นิรายนะ (แบบไทย)** ไม่ใช่สายนะ (สากล)
     *
     * ⚠️ เทสต์นี้เคยชื่อ `it_calculates_western_zodiac` และยืนยันช่วงวันแบบสากล
     *    พอ `624d56855` เปลี่ยนตารางเป็นราศีไทยตามเจตนาเจ้าของระบบ ค่าที่ถูกต้องก็เปลี่ยนตาม
     *    (ราศีไทยยกช้ากว่าสากลเกือบเดือน ⇒ 27 มิ.ย. ยังเป็น "เมถุน" ไม่ใช่ "กรกฎ")
     *    ของเดิมผิดทั้ง 4 บรรทัด แต่ PHPUnit รายงานแค่บรรทัดแรก
     *
     * @test
     */
    public function it_calculates_thai_sidereal_zodiac(): void
    {
        $this->assertStringContainsString('เมถุน', $this->service->getZodiacSign(6, 27));
        $this->assertStringContainsString('มังกร', $this->service->getZodiacSign(2, 6));
        $this->assertStringContainsString('ธนู', $this->service->getZodiacSign(1, 1));
        $this->assertStringContainsString('พิจิก', $this->service->getZodiacSign(12, 10));
    }

    /**
     * วันที่ดวงอาทิตย์ "ยกราศี" — ขอบเขตที่พลาดง่ายที่สุดเวลาแก้ตาราง
     *
     * @test
     */
    public function it_switches_sign_on_the_thai_transition_day(): void
    {
        // สงกรานต์ 14 เม.ย. = อาทิตย์ยกเข้าเมษ (ขึ้นปีใหม่ทางโหราศาสตร์ไทย)
        $this->assertStringContainsString('มีน', $this->service->getZodiacSign(4, 13));
        $this->assertStringContainsString('เมษ', $this->service->getZodiacSign(4, 14));

        // 17 ก.ย. = ยกเข้ากันย์
        $this->assertStringContainsString('สิงห์', $this->service->getZodiacSign(9, 16));
        $this->assertStringContainsString('กันย์', $this->service->getZodiacSign(9, 17));
    }

    /** @test */
    public function it_returns_planet_data_for_each_weekday(): void
    {
        for ($dow = 0; $dow <= 6; $dow++) {
            $p = $this->service->getPlanetByDayOfWeek($dow);
            $this->assertArrayHasKey('planet', $p);
            $this->assertArrayHasKey('element', $p);
            $this->assertArrayHasKey('personality', $p);
            $this->assertNotEmpty($p['planet']);
        }

        // อังคาร (index 2) → ดาวอังคาร
        $this->assertStringContainsString('อังคาร', $this->service->getPlanetByDayOfWeek(2)['planet']);
    }

    /** @test */
    public function it_builds_block_with_astrology_when_birthdate_present(): void
    {
        $block = $this->service->buildCelticBirthAstrologyBlock('เกิด 27/6/1978');

        $this->assertNotSame('', $block);
        $this->assertStringContainsString('ราศี', $block);
        $this->assertStringContainsString('ดาวเจ้าชนะ', $block);
        $this->assertStringContainsString('ห้ามถามวันเกิดซ้ำ', $block);
    }

    /** @test */
    public function it_returns_empty_block_when_no_birthdate(): void
    {
        $block = $this->service->buildCelticBirthAstrologyBlock('สวัสดีค่ะ อยากดูดวงความรักหน่อย');

        $this->assertSame('', $block);
    }

    /** @test */
    public function it_labels_multiple_people_and_adds_compatibility_directive(): void
    {
        $block = $this->service->buildCelticBirthAstrologyBlock('เกิด 27/6/1978 คู่ปรับเกิด 6/2/2532');

        $this->assertStringContainsString('คนที่ 1', $block);
        $this->assertStringContainsString('คนที่ 2', $block);
        // มีหลายคน → ต้องมี directive เทียบความเข้ากัน
        $this->assertStringContainsString('เข้ากันไหม', $block);
    }
}
