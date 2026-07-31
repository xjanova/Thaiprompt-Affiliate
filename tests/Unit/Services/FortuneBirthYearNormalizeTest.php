<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * ทดสอบการเดา "ปีเกิด" ของ FortuneConversationService::normalizeBirthYear()
 *
 * 🐛 (2026-07-31) กันบั๊กปี 2 หลักตีความผิดกลับมาอีก
 *
 * บั๊กเดิม: ใช้ logic แบบบัตรประชาชน (เทียบเลขท้ายปี ค.ศ. ปัจจุบัน)
 *   "17" → 2017 = อายุ 9 ขวบ   ❌  (ที่ถูกคือ พ.ศ.2517 = 1974 อายุ 52)
 *   "38" → 1938 = อายุ 88 ปี   ❌  (ที่ถูกคือ พ.ศ.2538 = 1995 อายุ 31)
 * ทั้งคู่ผ่าน isValidBirthYear() (อายุ 1-120) จึงถูกใช้ทำนายไปเงียบ ๆ
 *
 * ข้อมูลจริงบน prod ตอนแก้: ปีเกิดที่พบบ่อยสุด 12 อันดับแรกคือ พ.ศ. 2506-2522
 * (เลขท้าย "06"-"22" ซึ่ง ≤ 26 ทุกตัว) → ของเดิมแปลงเป็น 20xx ผิดทั้งกลุ่มลูกค้าหลัก
 *
 * ⚠️ ตรึงเวลาด้วย Carbon::setTestNow() เพราะตรรกะขึ้นกับ "ปีปัจจุบัน"
 *    ไม่ตรึง = เทสต์นี้จะพังเองเมื่อขึ้นปีใหม่
 *
 * ไม่ต้องใช้ฐานข้อมูล — เรียกเมธอดตรงผ่าน Reflection (เมธอดนี้ใช้แค่ now())
 */
class FortuneBirthYearNormalizeTest extends TestCase
{
    protected ReflectionMethod $normalize;

    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // ตรึงเป็น 31 ก.ค. 2026 (พ.ศ. 2569) — วันที่แก้บั๊กนี้
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 12, 0, 0));

        // เมธอดนี้ไม่แตะ property ใดของ service เลย → สร้างแบบข้าม constructor ได้
        // (constructor จริงต้องอ่าน settings จาก DB ซึ่งไม่จำเป็นกับเทสต์นี้)
        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();

        $this->normalize = new ReflectionMethod($this->service, 'normalizeBirthYear');
        $this->normalize->setAccessible(true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function normalize(int $year): ?int
    {
        return $this->normalize->invoke($this->service, $year);
    }

    /**
     * ปี 2 หลักต้องตีความเป็น พ.ศ. ก่อน — นี่คือกลุ่มลูกค้าหลักของระบบ
     *
     * @test
     */
    public function ปีสองหลักต้องตีความเป็นพศก่อน(): void
    {
        // เลขท้าย พ.ศ. ของปีเกิดที่พบบ่อยสุดบน prod (อายุ 47-63)
        $this->assertSame(1978, $this->normalize(21), 'พ.ศ.2521 อายุ 48 — ปีเกิดอันดับ 1');
        $this->assertSame(1977, $this->normalize(20), 'พ.ศ.2520 อายุ 49');
        $this->assertSame(1976, $this->normalize(19), 'พ.ศ.2519 อายุ 50');
        $this->assertSame(1974, $this->normalize(17), 'พ.ศ.2517 อายุ 52 — เดิมได้ 2017 (อายุ 9)');
        $this->assertSame(1969, $this->normalize(12), 'พ.ศ.2512 อายุ 57');
        $this->assertSame(1968, $this->normalize(11), 'พ.ศ.2511 อายุ 58');
        $this->assertSame(1963, $this->normalize(6), 'พ.ศ.2506 อายุ 63');
        $this->assertSame(1962, $this->normalize(5), 'พ.ศ.2505 อายุ 64');
        $this->assertSame(1957, $this->normalize(0), 'พ.ศ.2500 อายุ 69');

        // เคสที่ owner รายงาน
        $this->assertSame(1995, $this->normalize(38), 'พ.ศ.2538 อายุ 31 — เดิมได้ 1938 (อายุ 88)');
    }

    /**
     * แปลงเป็น พ.ศ. แล้วได้อนาคต/เด็กเกินไป → ต้องตกไปตีความเป็น ค.ศ. แบบเดิม
     *
     * @test
     */
    public function ปีสองหลักที่เป็นพศไม่ได้ต้องตกไปเป็นคศ(): void
    {
        $this->assertSame(1995, $this->normalize(95), 'พ.ศ.2595 = อนาคต → ค.ศ.1995');
        $this->assertSame(1999, $this->normalize(99), 'พ.ศ.2599 = อนาคต → ค.ศ.1999');
        $this->assertSame(1980, $this->normalize(80), 'พ.ศ.2580 = อนาคต → ค.ศ.1980');
        $this->assertSame(1968, $this->normalize(68), 'พ.ศ.2568 = ทารก 1 ขวบ → ค.ศ.1968');
        $this->assertSame(1969, $this->normalize(69), 'พ.ศ.2569 = ปีนี้ → ค.ศ.1969');
        $this->assertSame(1960, $this->normalize(60), 'พ.ศ.2560 = อายุ 9 → ค.ศ.1960');
    }

    /**
     * ขอบล่างของเกณฑ์อายุ — พ.ศ. ที่ให้อายุ 13 พอดีต้องยังรับ
     *
     * @test
     */
    public function ขอบอายุขั้นต่ำสิบสามปี(): void
    {
        $this->assertSame(2013, $this->normalize(56), 'พ.ศ.2556 = อายุ 13 พอดี → รับ');
        $this->assertSame(1957, $this->normalize(57), 'พ.ศ.2557 = อายุ 12 → ตกไป ค.ศ.1957');
    }

    /**
     * ปี 4 หลักห้ามเปลี่ยนพฤติกรรมเด็ดขาด (เป็น path ที่ลูกค้าจ่ายเงินใช้อยู่)
     *
     * @test
     */
    public function ปีสี่หลักต้องไม่เปลี่ยนพฤติกรรม(): void
    {
        $this->assertSame(1987, $this->normalize(2530), 'พ.ศ. เต็ม');
        $this->assertSame(1974, $this->normalize(2517), 'พ.ศ. เต็ม');
        $this->assertSame(1987, $this->normalize(1987), 'ค.ศ. เต็ม');
        $this->assertSame(1974, $this->normalize(1974), 'ค.ศ. เต็ม');
        $this->assertSame(2026, $this->normalize(2569), 'พ.ศ. ปีนี้ (isValidBirthYear ตีตกทีหลัง)');
    }

    /**
     * ปีที่เป็นไปไม่ได้ต้องคืน null
     *
     * @test
     */
    public function ปีที่เป็นไปไม่ได้ต้องคืนnull(): void
    {
        $this->assertNull($this->normalize(1899), 'เก่าเกิน 1900');
        $this->assertNull($this->normalize(2027), 'ค.ศ. อนาคต');
        $this->assertNull($this->normalize(2600), 'พ.ศ.2600 → ค.ศ.2057 = อนาคต');
    }

    /**
     * กวาดทุกปี 3-4 หลักเทียบกับตรรกะเดิม — ต้องไม่มีปีไหนเปลี่ยนเลย
     *
     * เป็นด่านกัน regression ที่แข็งแรงสุดของก้อนนี้: การแก้ต้องแตะเฉพาะปี 2 หลัก
     *
     * @test
     */
    public function ปีสามถึงสี่หลักทุกปีต้องให้ผลเท่าตรรกะเดิม(): void
    {
        $changed = [];

        for ($year = 100; $year <= 2600; $year++) {
            if ($this->normalize($year) !== $this->legacyNormalize($year)) {
                $changed[] = $year;
            }
        }

        $this->assertSame([], $changed, 'ปี 3-4 หลักต้องให้ผลเหมือนเดิมทุกปี');
    }

    /**
     * ตรรกะเดิมก่อนแก้ (2026-07-31) — ใช้เทียบเฉพาะในเทสต์เท่านั้น
     */
    protected function legacyNormalize(int $year): ?int
    {
        if ($year < 100) {
            $currentYY = (int) now()->format('y');
            $year = ($year <= $currentYY) ? (2000 + $year) : (1900 + $year);
        }

        if ($year > 2400) {
            $year -= 543;
        }

        if ($year < 1900 || $year > (int) now()->format('Y')) {
            return null;
        }

        return $year;
    }
}
