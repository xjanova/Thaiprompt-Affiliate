<?php

namespace Tests\Unit\Services;

use App\Models\FortuneReading;
use App\Services\FortuneConversationService;
use App\Support\StatedBirthDayName;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * 🎂 (2026-10-04) โหมดถามวันเกิดทีละส่วน (ปี → เดือน → วัน) ต้องไม่หยิบ "วันที่" ไปเป็น "ปี"
 *
 * เคสจริง FTU-261004-R4508 (FB, ดูดวง 39) — ลูกค้าเกิด 27/9/2510 (วันพุธ) แต่บิลได้ พ.ศ. 2527:
 *   บอท: "📅 ปีที่เกิดคือปีอะไรคะ?"
 *   ลูกค้า: "หนูเกิดวันที่27วันพุธ"   → บอท "✅ ปี 1984 รับแล้ว"  (หยิบ 27 เป็นปีย่อ = พ.ศ. 2527)
 *   ลูกค้า: "เดือนกันยา"             → บอท "❓ ไม่เข้าใจเดือนที่บอกมาค่ะ"
 *   ลูกค้า: "เดือน9" → "27" → กล่องยืนยัน 27/09/2527 → ลูกค้ากด "ใช่"
 *
 * เทสต์ไม่แตะ DB — บิลเป็นคลาสลูกที่ save() ไม่ลงฐานข้อมูล
 */
class FortuneBirthdateStepModeTest extends TestCase
{
    protected FortuneConversationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 4, 21, 0, 0));

        // เมธอดที่เทสต์ใช้แตะแค่ now() + regex (ตัวอ่าน AI สำรองล้มเงียบเพราะไม่มี settings) → ข้าม constructor ได้
        $this->service = (new ReflectionClass(FortuneConversationService::class))
            ->newInstanceWithoutConstructor();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * ⚠️ ห้ามตั้งชื่อ `call()` — ชนกับ TestCase::call() ของ Laravel
     */
    protected function invokeHidden(string $method, ...$args)
    {
        $m = new ReflectionMethod($this->service, $method);
        $m->setAccessible(true);

        return $m->invoke($this->service, ...$args);
    }

    /** บิลในโหมดถามทีละส่วน ที่ setConversationState() ไม่ลง DB */
    protected function stepModeReading(array $partial = []): FortuneReading
    {
        $reading = new class extends FortuneReading
        {
            public function save(array $options = [])
            {
                return true;
            }
        };
        $reading->conversation_state = ['birthdate_step_mode' => true, 'birthdate_partial' => $partial];
        // Model::update() ไม่ทำอะไรเลยถ้า exists = false — ต้องทำเหมือนเป็นแถวที่มีอยู่แล้ว
        $reading->exists = true;

        return $reading;
    }

    protected function reply(FortuneReading $reading, string $text): array
    {
        return $this->invokeHidden('handleBirthdateStepMode', $reading, $text);
    }

    /** 1️⃣ เล่นซ้ำเคสจริง — ตอบวันที่ตอนบอทถามปี ต้องจำวันที่ไว้ แล้วถามปีต่อ */
    public function test_เคสจริง_r4508_ตอบวันที่ตอนถามปี_ต้องไม่กลายเป็นปี(): void
    {
        $reading = $this->stepModeReading();

        $r1 = $this->reply($reading, 'หนูเกิดวันที่27วันพุธ');
        $this->assertSame('collecting_birthdate', $r1['action']);
        $this->assertStringNotContainsString('2527', $r1['message']);
        $this->assertStringNotContainsString('1984', $r1['message']);
        $this->assertStringContainsString('วันที่ 27', $r1['message']);
        $this->assertStringContainsString('เกิดวันพุธ', $r1['message']);
        $this->assertStringContainsString('ปีที่เกิด', $r1['message'], 'ยังไม่มีปี ต้องถามปีต่อ');
        $partial = $reading->getConversationState('birthdate_partial');
        $this->assertArrayNotHasKey('year', $partial);
        $this->assertSame(27, $partial['day']);
        $this->assertSame(3, $partial['weekday']);

        $r2 = $this->reply($reading, '2510');
        $this->assertStringContainsString('ปี พ.ศ. 2510', $r2['message'], 'ทวนปีเป็น พ.ศ. ไม่ใช่ ค.ศ.');
        $this->assertStringContainsString('เดือนไหน', $r2['message']);

        // "กันยา" (ชื่อแบบพูด) ต้องอ่านได้ · วันที่ 27 มีแล้ว → ครบชุด → กล่องยืนยันเลย
        $r3 = $this->reply($reading, 'เดือนกันยา');
        $this->assertSame('awaiting_birthdate_confirmation', $r3['action']);
        $this->assertSame('1967-09-27', $reading->getConversationState('pending_birthdate'));
        $this->assertStringNotContainsString('⚠️', $r3['message'], '27/9/2510 เป็นวันพุธจริง ไม่ต้องเตือน');
        $this->assertFalse((bool) $reading->getConversationState('birthdate_step_mode'));
    }

    /** 2️⃣ ปีที่ได้ทำให้วันในสัปดาห์ไม่ตรงกับที่ลูกค้าบอก → เตือนหัวกล่องยืนยัน (ไม่บล็อก) */
    public function test_วันในสัปดาห์ไม่ตรง_เตือนในกล่องยืนยัน(): void
    {
        $reading = $this->stepModeReading(['day' => 27, 'weekday' => 3]);

        $this->reply($reading, '2527');
        $r = $this->reply($reading, 'เดือน9');

        $this->assertSame('awaiting_birthdate_confirmation', $r['action']);
        $this->assertSame('1984-09-27', $reading->getConversationState('pending_birthdate'));
        $this->assertStringContainsString('⚠️', $r['message']);
        $this->assertStringContainsString('วันพุธ', $r['message']);
        $this->assertStringContainsString('วันพฤหัสบดี', $r['message']);
        $this->assertStringContainsString('พ.ศ. 2527', $r['message']);
        $this->assertStringContainsString('ก่อนรุ่งสาง', $r['message'], 'พุธ = วันก่อนพฤหัส อาจเกิดก่อนรุ่งสาง');
    }

    /** 3️⃣ ลำดับปกติ (ปี → เดือน → วัน) ยังเดินได้เหมือนเดิม */
    public function test_ลำดับปกติ_ปี_เดือน_วัน(): void
    {
        $reading = $this->stepModeReading();

        $this->assertStringContainsString('ปี พ.ศ. 2533', $this->reply($reading, '2533')['message']);
        $this->assertStringContainsString('เดือนสิงหาคม', $this->reply($reading, 'ส.ค.')['message']);
        $r = $this->reply($reading, '15');

        $this->assertSame('awaiting_birthdate_confirmation', $r['action']);
        $this->assertSame('1990-08-15', $reading->getConversationState('pending_birthdate'));
    }

    /** 3️⃣.1 ตอบ "27/9" (วัน/เดือน ไม่มีปี) ตอนถามปี → ห้ามกลายเป็น พ.ศ. 2527 · เก็บวัน+เดือน แล้วถามปี */
    public function test_ตอบวันเดือนไม่มีปี_ตอนถามปี(): void
    {
        $reading = $this->stepModeReading();

        $r1 = $this->reply($reading, '27/9');
        $this->assertStringNotContainsString('2527', $r1['message']);
        $this->assertStringContainsString('ปีที่เกิด', $r1['message']);
        $this->assertSame(['day' => 27, 'month' => 9], $reading->getConversationState('birthdate_partial'));

        $r2 = $this->reply($reading, '2510');
        $this->assertSame('awaiting_birthdate_confirmation', $r2['action']);
        $this->assertSame('1967-09-27', $reading->getConversationState('pending_birthdate'));
    }

    /** 4️⃣ อ่านไม่ออกเลย → ถามชิ้นเดิมซ้ำ ไม่เดา */
    public function test_อ่านไม่ออก_ถามชิ้นเดิมซ้ำ(): void
    {
        $reading = $this->stepModeReading(['year' => 1967]);

        $r = $this->reply($reading, 'ไม่รู้ค่ะ');

        $this->assertStringContainsString('ไม่เข้าใจเดือน', $r['message']);
        $this->assertSame(['year' => 1967], $reading->getConversationState('birthdate_partial'));
    }

    /** 5️⃣ วันที่ไม่มีจริงในเดือนนั้น → ทิ้งวันที่ ขอใหม่ (ปี/เดือนเดิมยังอยู่) */
    public function test_วันที่ไม่มีในเดือน_ขอวันใหม่(): void
    {
        $reading = $this->stepModeReading(['year' => 1967, 'month' => 2]);

        $r = $this->reply($reading, '30');

        $this->assertSame('collecting_birthdate', $r['action']);
        $this->assertStringContainsString('ไม่มีวันที่ 30', $r['message']);
        $this->assertSame(['year' => 1967, 'month' => 2], $reading->getConversationState('birthdate_partial'));
    }

    public function test_parse_loose_year_ข้ามเลขที่ระบุว่าเป็นวันที่หรือเดือน(): void
    {
        $this->assertNull($this->invokeHidden('parseLooseYear', 'หนูเกิดวันที่27วันพุธ'));
        $this->assertNull($this->invokeHidden('parseLooseYear', 'เดือน 9'));
        $this->assertSame(1967, $this->invokeHidden('parseLooseYear', '2510'));
        $this->assertSame(1967, $this->invokeHidden('parseLooseYear', 'ปี 10'));
        // ปีย่อเปล่า ๆ ยังตีเป็น พ.ศ. ตามเดิม ([[rule_birthyear_buddhist_first]])
        $this->assertSame(1984, $this->invokeHidden('parseLooseYear', '27'));
    }

    public function test_parse_loose_month_ชื่อแบบพูด_และข้ามเลขของชิ้นอื่น(): void
    {
        $this->assertSame(9, $this->invokeHidden('parseLooseMonth', 'เดือนกันยา'));
        $this->assertSame(9, $this->invokeHidden('parseLooseMonth', 'กันยายน'));
        $this->assertSame(3, $this->invokeHidden('parseLooseMonth', 'มีนา'));
        $this->assertSame(9, $this->invokeHidden('parseLooseMonth', 'ก.ย.'));
        $this->assertSame(9, $this->invokeHidden('parseLooseMonth', '๙'));
        $this->assertNull($this->invokeHidden('parseLooseMonth', 'วันที่ 5'));
        $this->assertNull($this->invokeHidden('parseLooseMonth', 'ปี 10'));
    }

    public function test_parse_loose_day_ข้ามเลขที่ระบุว่าเป็นเดือนหรือปี(): void
    {
        $this->assertSame(27, $this->invokeHidden('parseLooseDay', '27'));
        $this->assertSame(27, $this->invokeHidden('parseLooseDay', 'วันที่ 27'));
        $this->assertNull($this->invokeHidden('parseLooseDay', 'ปี 10'));
        $this->assertNull($this->invokeHidden('parseLooseDay', 'เดือน 9'));
    }

    public function test_stated_birth_day_name_mentioned_อ่านวันที่มีเลขคั่น(): void
    {
        // stated() ใช้กับแชททั่วไป — มีเลขคั่นระหว่าง "เกิด" กับชื่อวัน = ไม่จับ (ตั้งใจ)
        $this->assertNull(StatedBirthDayName::stated('หนูเกิดวันที่27วันพุธ'));
        $this->assertSame(3, StatedBirthDayName::mentioned('หนูเกิดวันที่27วันพุธ'));
        $this->assertSame(0, StatedBirthDayName::mentioned('วันอาทิตย์ค่ะ'));
        $this->assertNull(StatedBirthDayName::mentioned('แม่หมอจันทรา วันจันทรา'));
        $this->assertNull(StatedBirthDayName::mentioned('2510'));
    }
}
