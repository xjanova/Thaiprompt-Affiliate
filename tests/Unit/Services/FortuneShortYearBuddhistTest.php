<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use App\Support\ThaiBirthYear;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🇹🇭 (2026-08-02, owner) "บริบทเราใช้ในไทย ใช้ พ.ศ. เป็นหลัก
 *    ให้ตีเป็น พ.ศ. แทน ค.ศ. นอกจากลูกค้าจะแย้งเอง"
 *
 * เคสจริงที่รายงาน: ลูกค้าพิมพ์ "25 มค 04" หมายถึง 25 มกราคม พ.ศ.2504 (ค.ศ.1961)
 * แต่เส้น AI fallback ถูกสั่งด้วย "Thai ID logic" (ค.ศ. ก่อน) → ได้ ค.ศ.2004
 * และผลจาก AI เดิม **ไม่ผ่านด่านใดเลย** ก่อนถูกใช้ทำนาย
 *
 * เทสต์นี้ตรึง 3 อย่าง:
 *   1. ปีย่อ 2 หลักทุกเส้น = พ.ศ. ก่อนเสมอ
 *   2. ผลจาก AI ต้องผ่านด่านเดียวกับเส้น regex (checkdate + ช่วงอายุที่รับได้)
 *   3. ปีย่อ = ต้องทวนให้ลูกค้าฟังว่าอ่านเป็น พ.ศ. อะไร (ลูกค้าแย้งได้)
 *
 * ⚠️ ตรึงเวลาด้วย Carbon::setTestNow() เพราะตรรกะขึ้นกับ "ปีปัจจุบัน"
 *
 * @see \Tests\Unit\Services\FortuneBirthYearNormalizeTest ด่านกัน regression ของ normalizeBirthYear
 */
class FortuneShortYearBuddhistTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 8, 2, 12, 0, 0));

        // เมธอดที่เทสต์ใช้แค่ now() → สร้างแบบข้าม constructor ได้ (ไม่ต้องแตะ DB)
        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * เรียกเมธอด protected ของ service
     *
     * ⚠️ ห้ามตั้งชื่อ `call()` — ชนกับ TestCase::call() ของ Laravel (public) = fatal error
     */
    protected function invokeHidden(string $method, ...$args)
    {
        $m = new ReflectionMethod($this->service, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service, ...$args);
    }

    /**
     * เคสที่ owner รายงาน — "25 มค 04" ต้องได้ 25 ม.ค. พ.ศ.2504
     *
     * @test
     */
    public function ปีย่อสองหลักต้องอ่านเป็นพศทุกรูปแบบที่ลูกค้าพิมพ์(): void
    {
        $this->assertSame('1961-01-25', $this->invokeHidden('parseBirthDate', '25 มค 04'), 'เคส owner: 25 มค 04');
        $this->assertSame('1961-01-25', $this->invokeHidden('parseBirthDate', '25/1/04'));
        $this->assertSame('1961-01-25', $this->invokeHidden('parseBirthDate', '25 มกราคม 2504'), 'ปีเต็มต้องได้ผลเดียวกัน');
        $this->assertSame('1990-08-15', $this->invokeHidden('parseBirthDate', '15/8/33'), 'พ.ศ.2533');
    }

    /**
     * ตกไปเป็น ค.ศ. เฉพาะตอน พ.ศ. เป็นไปไม่ได้เท่านั้น
     *
     * @test
     */
    public function ตกไปเป็นคศเฉพาะตอนพศเป็นไปไม่ได้(): void
    {
        $this->assertSame(1995, ThaiBirthYear::normalize(95), 'พ.ศ.2595 = อนาคต');
        $this->assertSame(1968, ThaiBirthYear::normalize(68), 'พ.ศ.2568 = ทารก 1 ขวบ');
        $this->assertSame(1961, ThaiBirthYear::normalize(4), 'พ.ศ.2504 = อายุ 65 → รับ');
    }

    /**
     * ผลจาก AI parser ต้องถูกตรวจซ้ำ — ทั้งเรื่อง พ.ศ. และช่วงอายุ
     *
     * @test
     */
    public function ผลจากเอไอต้องผ่านด่านเดียวกับเส้นregex(): void
    {
        // AI ตีเป็น ค.ศ.2004 (Thai ID logic เดิม) → ต้องถูกแก้เป็น พ.ศ.2504
        $this->assertSame(
            '1961-01-25',
            $this->invokeHidden('reconcileAiBirthDate', '25 มค. 04', '2004-01-25'),
            'ปีย่อที่ AI ตีเป็น ค.ศ. ต้องถูกแก้กลับเป็น พ.ศ.'
        );

        // AI ตอบถูกอยู่แล้ว → ห้ามแตะ
        $this->assertSame('1961-01-25', $this->invokeHidden('reconcileAiBirthDate', '25 มค. 04', '1961-01-25'));

        // ข้อความอิสระ — เลข 2 หลักตัวท้ายไม่ใช่ปี ห้ามแก้ผิดตัว
        $this->assertSame(
            '1990-08-15',
            $this->invokeHidden('reconcileAiBirthDate', 'เกิดปี 90 เดือน 8 วันที่ 15', '1990-08-15'),
            'AI ไม่ได้ใช้เลขตัวนั้นเป็นปี → ต้องปล่อยผ่าน'
        );

        // อายุที่เป็นไปไม่ได้ (เคสจริงบน prod) → ต้องปฏิเสธ ไม่ใช่เอาไปทำนาย
        $this->assertNull($this->invokeHidden('reconcileAiBirthDate', 'เกิด 2568', '2025-02-17'), 'อายุ 1 ขวบ');
        $this->assertNull($this->invokeHidden('reconcileAiBirthDate', 'เกิด 2469', '1926-12-26'), 'อายุ 100 ปี');
        $this->assertNull($this->invokeHidden('reconcileAiBirthDate', 'มั่ว', '19x1-01-25'), 'รูปแบบเพี้ยน');
    }

    /**
     * ตำแหน่งปีย่อต้องชัดเจนเท่านั้น — ห้ามกวาดเลข 2 หลักมั่ว
     *
     * @test
     */
    public function ดึงปีย่อเฉพาะรูปแบบวันเกิดมาตรฐาน(): void
    {
        $this->assertSame(4, ThaiBirthYear::extractShortYear('25 มค 04'));
        $this->assertSame(4, ThaiBirthYear::extractShortYear('25 มค. 04'), 'มีจุดคั่น (เส้นที่ regex หลักพลาด)');
        $this->assertSame(90, ThaiBirthYear::extractShortYear('15/8/90'));
        $this->assertNull(ThaiBirthYear::extractShortYear('15/8/1990'), 'ปีเต็ม = ไม่กำกวม');
        $this->assertNull(ThaiBirthYear::extractShortYear('25 มกราคม 2504'), 'ปีเต็ม = ไม่กำกวม');
    }

    /**
     * ปีย่อ → ต้องทวนให้ลูกค้าฟังว่าอ่านเป็น พ.ศ. อะไร (owner: "นอกจากลูกค้าจะแย้งเอง")
     *
     * @test
     */
    public function ปีย่อต้องทวนการตีความให้ลูกค้าแย้งได้(): void
    {
        $note = $this->invokeHidden('buildShortYearInterpretationNote', '25 มค 04', '1961-01-25');

        $this->assertStringContainsString('พ.ศ. 2504', $note, 'ต้องบอกปี พ.ศ. ที่อ่านได้');
        $this->assertStringContainsString('4 หลัก', $note, 'ต้องบอกวิธีแก้ = พิมพ์ปีเต็ม');

        $this->assertSame(
            '',
            $this->invokeHidden('buildShortYearInterpretationNote', '25 มกราคม 2504', '1961-01-25'),
            'ลูกค้าพิมพ์ปีเต็มมาแล้ว = ไม่ต้องรบกวน'
        );
    }
}
