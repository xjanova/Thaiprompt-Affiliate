<?php

namespace Tests\Unit\Services;

use App\Services\FortuneConversationService;
use App\Support\StatedBirthDayName;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🎂 (2026-08-30) "วันในสัปดาห์ที่ลูกค้าบอกเอง" ต้องไม่ถูกทิ้ง
 *
 * เคสจริง PSID 28646125271642250 (09:26 น.): "เกิดวันอาทิตย์เดือน 4 ปีขาล 05"
 *   → ตัวปอกใน parseBirthDate ลบ "วันอาทิตย์" ทิ้ง → AI ได้แค่ "4 ขาล 05"
 *   → เดาเป็น 1962-04-05 ซึ่งตรงกับ **วันพฤหัสบดี**
 *   → บอทส่ง "ดวงประจำวันพฤหัสบดี" ให้คนที่เพิ่งบอกว่าเกิดวันอาทิตย์ ในประโยคเดียวกัน
 *
 * ลูกค้าพูดถูกทุกชิ้น (เม.ย. ✓ · พ.ศ.2505 เป็นปีขาลจริง ✓) — เขาแค่ไม่ได้บอกวันที่
 * "05" คือปี ไม่ใช่วันที่ 5 · AI เติมวันที่ขึ้นมาเองแล้วเราจดลงฐานข้อมูล
 *
 * owner: "ถ้ามันคลุมเครือ ข้อมูลไม่พอ บอทต้องฉลาดในการถามเพิ่มสิ"
 *
 * เทสต์นี้ตรึง 4 อย่าง:
 *   1. จับชื่อวันได้จากข้อความจริง (รวมรูปที่เขียนติดกัน/มีคำลงท้าย)
 *   2. **ไม่จับ** ชื่อวันที่ไม่ได้หมายถึงวันเกิด (และไม่จับ "จันทรา" = ชื่อแม่หมอ)
 *   3. ขัดกัน → parseBirthDate ต้องคืน null + เปิดข้อมูลให้ผู้เรียกถามกลับได้
 *   4. ปีที่ AI เสกขึ้นมาเองโดยไม่มีในข้อความ → ไม่รับ
 */
class FortuneStatedBirthDayNameTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 8, 30, 12, 0, 0));

        // เมธอดที่เทสต์ใช้แตะแค่ now() + regex → ข้าม constructor ได้ (ไม่ต้องมี DB)
        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * ⚠️ ห้ามตั้งชื่อ `call()` — ชนกับ TestCase::call() ของ Laravel (public) = fatal error
     */
    protected function invokeHidden(string $method, ...$args)
    {
        $m = new ReflectionMethod($this->service, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service, ...$args);
    }

    /**
     * ชื่อวันที่ลูกค้าบอก ต้องอ่านออกทุกรูปที่คนพิมพ์จริง
     *
     * @test
     */
    public function จับชื่อวันเกิดที่ลูกค้าบอกได้(): void
    {
        // ⭐ เคสจริงที่พัง — คำเขียนติดกันหมด ไม่มีเว้นวรรค
        $this->assertSame(0, StatedBirthDayName::stated('เกิดวันอาทิตย์เดือน 4 ปีขาล 05'));
        $this->assertSame(0, StatedBirthDayName::stated('ผมเกิดวันอาทิตย์ครับแต่ตอนนี้ผมไม่มีตังค์เลย'));

        $this->assertSame(5, StatedBirthDayName::stated('เกิดวันศุกร์ค่ะ'));
        $this->assertSame(5, StatedBirthDayName::stated('เกิดวันศุกร์ฅรับ'), 'ฅ เลิกใช้ — คนพิมพ์พลาดจาก ค');
        $this->assertSame(6, StatedBirthDayName::stated('เกิดเสาร์ที่ 23 กันยายน 32 ครับ'));
        $this->assertSame(1, StatedBirthDayName::stated('วันเกิดวันจันทร์ 5/8/2530'));
        $this->assertSame(4, StatedBirthDayName::stated('เกิดวันพฤหัสบดี 12 พ.ค. 2510'));
        $this->assertSame(3, StatedBirthDayName::stated('หนูเกิดวันพุธนะคะ'));
        $this->assertSame(0, StatedBirthDayName::stated('เกิดวันอาทิดค่ะ'), 'รูปสะกดย่อ');

        // 🚨 "อังคาร" มี "คา" อยู่กลางคำ — ตัวปอกคำลงท้ายต้องไม่กินมันทิ้ง
        $this->assertSame(2, StatedBirthDayName::stated('เกิดวันอังคาร 1 มกราคม 2500'));
        $this->assertSame(2, StatedBirthDayName::stated('เกิดวันอังคารครับ'));
    }

    /**
     * ชื่อวันที่ "ไม่ได้หมายถึงวันเกิด" ห้ามถูกจับ — ไม่งั้นจะตีตกวันเกิดที่ถูกต้องทิ้ง
     *
     * @test
     */
    public function ไม่จับชื่อวันที่ไม่ใช่วันเกิด(): void
    {
        $this->assertNull(
            StatedBirthDayName::stated('วันจันทร์ที่ผ่านมาผมไปทำบุญ เกิด 15/3/2538'),
            'ชื่อวันมาก่อนคำว่าเกิด = คนละเรื่อง'
        );

        $this->assertNull(
            StatedBirthDayName::stated('เกิด 15/3/2538 วันจันทร์ที่แล้วไปทำบุญมา'),
            'มีตัวเลขคั่นระหว่าง "เกิด" กับชื่อวัน = คนละประโยค'
        );

        $this->assertNull(
            StatedBirthDayName::stated('เกิดที่โรงพยาบาลศิริราช วันจันทร์นี้ไปหาหมอ'),
            'ชื่อวันอยู่ไกลจากคำว่าเกิดเกินไป'
        );

        // 🚨 "จันทรา" คือชื่อแม่หมอเอง — ห้ามอ่านเป็นวันจันทร์เด็ดขาด
        $this->assertNull(StatedBirthDayName::stated('เกิดมาก็เจอแม่หมอจันทราคนเดียว'));
        $this->assertNull(StatedBirthDayName::stated('สวัสดีค่ะแม่หมอจันทรา'));

        $this->assertNull(StatedBirthDayName::stated('เกิด 15 มีนาคม 2538'), 'ไม่ได้บอกชื่อวัน');
        $this->assertNull(StatedBirthDayName::stated('ผมชื่อเสาร์ครับ'), 'ไม่มีคำว่าเกิด');
        $this->assertNull(StatedBirthDayName::stated(''));
    }

    /**
     * วันที่ในเดือนนั้นที่ตรงกับวันในสัปดาห์ — ใช้ยื่นตัวเลือกให้ลูกค้าตอบในทีเดียว
     *
     * @test
     */
    public function หาวันที่ที่ตรงกับวันในสัปดาห์ได้(): void
    {
        // เมษายน 1962 (พ.ศ.2505) — วันอาทิตย์ตรงกับวันที่ 1, 8, 15, 22, 29
        $this->assertSame([1, 8, 15, 22, 29], StatedBirthDayName::datesMatching(1962, 4, 0));

        // 5 เม.ย. 1962 เป็นวันพฤหัสบดี ⇒ ต้องไม่โผล่ในชุดวันอาทิตย์
        $this->assertNotContains(5, StatedBirthDayName::datesMatching(1962, 4, 0));

        $this->assertSame([], StatedBirthDayName::datesMatching(1962, 13, 0), 'เดือนนอกช่วง');
        $this->assertSame([], StatedBirthDayName::datesMatching(1962, 4, 9), 'index วันนอกช่วง');
    }

    /**
     * ⭐ เคสจริงทั้งก้อน — ขัดกันแล้วต้องไม่รับ และต้องบอกได้ว่าขัดตรงไหน
     *
     * @test
     */
    public function วันในสัปดาห์ขัดกับวันที่แล้วต้องไม่รับ(): void
    {
        // ลูกค้าบอกวันอาทิตย์ แต่ 5 เม.ย. 2505 เป็นวันพฤหัสบดี
        $this->assertNull(
            $this->invokeHidden('parseBirthDate', 'เกิดวันอาทิตย์ 5 เมษายน 2505'),
            'ขัดกัน = ไม่รับ ต้องถามกลับ'
        );

        $conflict = $this->invokeHidden('birthDateConflict');

        $this->assertIsArray($conflict);
        $this->assertSame(0, $conflict['stated_day'], 'ลูกค้าบอกวันอาทิตย์');
        $this->assertSame(4, $conflict['parsed_day'], 'วันที่ที่พิมพ์ตรงกับพฤหัสบดี');
        $this->assertSame('1962-04-05', $conflict['parsed_date']);
        $this->assertSame([1, 8, 15, 22, 29], $conflict['candidates'], 'ต้องยื่นวันอาทิตย์ที่เป็นไปได้ให้เลือก');
    }

    /**
     * ตรงกัน = ยืนยันซึ่งกันและกัน ห้ามขวาง
     *
     * @test
     */
    public function วันในสัปดาห์ตรงกับวันที่แล้วต้องผ่านปกติ(): void
    {
        // 8 เม.ย. 2505 เป็นวันอาทิตย์จริง
        $this->assertSame(
            '1962-04-08',
            $this->invokeHidden('parseBirthDate', 'เกิดวันอาทิตย์ 8 เมษายน 2505')
        );

        $this->assertNull($this->invokeHidden('birthDateConflict'), 'ตรงกัน = ไม่มี conflict ค้าง');
    }

    /**
     * ไม่บอกชื่อวัน = พฤติกรรมเดิมทุกประการ (ด่านนี้ต้องไม่กระทบทางหลัก)
     *
     * @test
     */
    public function ไม่บอกชื่อวันแล้วต้องทำงานเหมือนเดิม(): void
    {
        $this->assertSame('1962-04-05', $this->invokeHidden('parseBirthDate', '5 เมษายน 2505'));
        $this->assertSame('1990-08-15', $this->invokeHidden('parseBirthDate', '15/8/2533'));
        $this->assertNull($this->invokeHidden('birthDateConflict'));
    }

    /**
     * คำถามกลับต้องบอกให้ครบว่า "ขัดตรงไหน" + "เลือกวันไหนได้บ้าง"
     *
     * ⚠️ ถามลอย ๆ ว่า "พิมพ์ใหม่" = ลูกค้าพิมพ์ชุดเดิมกลับมา แล้ววนไม่จบ
     *
     * @test
     */
    public function คำถามกลับต้องมีวันที่ให้เลือกจริง(): void
    {
        $this->invokeHidden('parseBirthDate', 'เกิดวันอาทิตย์ 5 เมษายน 2505');
        $conflict = $this->invokeHidden('birthDateConflict');

        $msg = $this->invokeHidden('buildBirthDayConflictQuestion', $conflict);

        $this->assertStringContainsString('อาทิตย์', $msg, 'ต้องทวนวันที่ลูกค้าบอก');
        $this->assertStringContainsString('พฤหัสบดี', $msg, 'ต้องบอกว่าวันที่ที่พิมพ์ตรงกับวันอะไร');
        $this->assertStringContainsString('เมษายน', $msg);
        $this->assertStringContainsString('1, 8, 15, 22, 29', $msg, 'ต้องยื่นตัวเลือกให้ตอบได้ทันที');
        $this->assertStringContainsString('ยืนยันวันที่', $msg, 'ต้องมีทางออกให้คนที่มั่นใจในวันที่');
    }

    /**
     * ผลพลอยได้ที่ต้องได้: เส้นดวงรายวันตกกลับไปใช้ "ชื่อวันที่ลูกค้าบอก" เอง
     *
     * resolveDayIndexFromReply ลองวันที่ก่อน — พอวันที่ถูกตีตกเพราะขัดกัน
     * มันจะไหลลง detectThaiDayName ซึ่งอ่านชื่อวันออก ⇒ ลูกค้าได้ดวงของวันที่เขาบอก
     * (เดิมเส้นชื่อวันตายสนิท เพราะเส้นวันที่ return ก่อนเสมอ)
     *
     * @test
     */
    public function เส้นดวงรายวันตกไปใช้ชื่อวันที่ลูกค้าบอกเมื่อวันที่ขัดกัน(): void
    {
        $result = $this->invokeHidden('resolveDayIndexFromReply', 'เกิดวันอาทิตย์ 5 เมษายน 2505');

        $this->assertIsArray($result);
        $this->assertSame(0, $result[0], 'ต้องได้วันอาทิตย์ตามที่ลูกค้าบอก ไม่ใช่พฤหัสบดีจากวันที่');
        $this->assertNull($result[1], 'วันเกิดเต็มยังไม่ยืนยัน — ห้ามจดลงฐานข้อมูล');

        // ตรงกัน = ได้ทั้ง index และวันเกิดเต็ม (เก็บถาวรได้)
        $ok = $this->invokeHidden('resolveDayIndexFromReply', 'เกิดวันอาทิตย์ 8 เมษายน 2505');

        $this->assertSame([0, '1962-04-08'], $ok);
    }

    /**
     * ทางออกกันติดวน — ลูกค้ายืนยันว่าวันที่ถูกแล้ว
     *
     * @test
     */
    public function รับคำยืนยันวันที่จากลูกค้าได้(): void
    {
        $this->assertTrue($this->invokeHidden('looksLikeBirthDateOverrideConfirm', 'ยืนยันวันที่'));
        $this->assertTrue($this->invokeHidden('looksLikeBirthDateOverrideConfirm', 'วันที่ถูกแล้วค่ะ'));
        $this->assertFalse($this->invokeHidden('looksLikeBirthDateOverrideConfirm', 'เกิดวันอาทิตย์'));
    }

    /**
     * 🚫 ปีที่ AI เสกเองโดยไม่มีร่องรอยในข้อความ = ไม่รับ
     *
     * เคสจริง 2026-08-30: "เขา 4ส.ค. เสีย9ส.ค" → AI ตอบ 2009-08-04
     * ลูกค้าเล่าเรื่องคนเสียชีวิต ไม่ได้บอกวันเกิดตัวเอง และ 2009 ไม่มีในข้อความเลย
     *
     * @test
     */
    public function ปีที่เอไอเสกเองต้องไม่ผ่าน(): void
    {
        $this->assertNull(
            $this->invokeHidden('reconcileAiBirthDate', 'เขา 4ส.ค. เสีย9ส.ค', '2009-08-04'),
            'เคสจริง — 2009 ไม่มีที่มาในข้อความ'
        );

        // ปีที่มีร่องรอยจริง ต้องยังผ่านตามเดิม
        $this->assertSame(
            '1992-09-23',
            $this->invokeHidden('reconcileAiBirthDate', '23 กย 2535', '1992-09-23')
        );
        $this->assertSame(
            '1961-01-25',
            $this->invokeHidden('reconcileAiBirthDate', '25 มค. 04', '2004-01-25'),
            'ปีย่อที่ AI ตีเป็น ค.ศ. ต้องถูกแก้เป็น พ.ศ. แล้วยังผ่าน'
        );
    }
}
