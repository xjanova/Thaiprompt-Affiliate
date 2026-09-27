<?php

namespace Tests\Unit\Support;

use App\Support\OwnBirthDate;
use Tests\TestCase;

/**
 * 🎂 (2026-09-27, owner) ตัวอ่าน "วันเกิดของเจ้าชะตาเอง" จากแชทอิสระ
 *
 * เคสตั้งต้น FTU-260927-A4514: ลูกค้าพิมพ์ "27/6/2521" ระหว่างรอโอน → AI รับปากว่าได้วันเกิดแล้ว
 * แต่ไม่มีโค้ดเก็บ (birth_date = NULL) — ตัวอ่านนี้ต้องจับได้ และต้อง "ไม่" จับวันเกิดของคนอื่น
 *
 * ใช้ Tests\TestCase เพราะ ThaiBirthYear::normalize() เรียก now() (ต้องมีแอป) — ไม่แตะ DB
 */
class OwnBirthDateTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function ownBirthdates(): array
    {
        return [
            // ข้อความจริงจากเคส A4514
            'bare numeric (A4514)' => ['27/6/2521', '1978-06-27', OwnBirthDate::BASIS_BARE],
            'bare with born + polite' => ['เกิด 27/6/2521 ค่ะ', '1978-06-27', OwnBirthDate::BASIS_BARE],
            'bare thai month + time + province' => ['27 มิ.ย. 2521 ตี 5 เชียงใหม่ค่ะ', '1978-06-27', OwnBirthDate::BASIS_BARE],
            'bare 2-digit year = พ.ศ.' => ['27 มิถุนายน 21', '1978-06-27', OwnBirthDate::BASIS_BARE],
            'bare weekday + date' => ['เกิดวันอังคารที่ 27 มิถุนายน 2521', '1978-06-27', OwnBirthDate::BASIS_BARE],
            'bare thai digits' => ['๒๗/๖/๒๕๒๑', '1978-06-27', OwnBirthDate::BASIS_BARE],
            'bare correction phrase' => ['แก้วันเกิดเป็น 5 มีนาคม 2530 ค่ะ', '1987-03-05', OwnBirthDate::BASIS_BARE],
            'self pronoun + question' => ['หนูเกิด 5/3/2530 อยากรู้เรื่องงานค่ะ', '1987-03-05', OwnBirthDate::BASIS_SELF],
            'self correct-date phrase' => ['วันเกิดที่ถูกคือ 12/05/2515 นะคะ ช่วยดูใหม่ได้ไหม', '1972-05-12', OwnBirthDate::BASIS_SELF],
            'self possessive' => ['วันเกิดของฉันคือ 1 ม.ค. 2530 แล้วงานจะดีไหม', '1987-01-01', OwnBirthDate::BASIS_SELF],
            'self first, partner second' => ['หนูเกิด 27/6/2521 แฟนเกิด 3/6/2497 เข้ากันไหม', '1978-06-27', OwnBirthDate::BASIS_SELF],
            'vocative mom-doctor then self' => ['แม่ หนูเกิด 27/6/2521 ค่ะ ช่วยดูเรื่องเงินหน่อย', '1978-06-27', OwnBirthDate::BASIS_SELF],
            // (จับผี) วันที่ที่ตามคำปฏิเสธคือ "ตัวที่ผิด" — ต้องได้ตัวที่ถูกเสมอ
            'wrong one first, correct marker' => ['วันเกิดไม่ใช่ 27/7/2521 นะคะ ที่ถูกคือ 27/6/2521', '1978-06-27', OwnBirthDate::BASIS_SELF],
            'wrong then must-be' => ['วันเกิดผิดค่ะ 27/7/2521 ต้องเป็น 27/6/2521', '1978-06-27', OwnBirthDate::BASIS_SELF],
        ];
    }

    /**
     * @dataProvider ownBirthdates
     */
    public function test_reads_the_customers_own_birthdate(string $text, string $ymd, string $basis): void
    {
        $found = OwnBirthDate::find($text);

        $this->assertNotNull($found, "ต้องอ่านวันเกิดของลูกค้าได้: {$text}");
        $this->assertSame($ymd, $found['ymd'], $text);
        $this->assertSame($basis, $found['basis'], $text);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function notOwnBirthdates(): array
    {
        return [
            'partner' => ['แฟนเกิด 3/6/2497 จะกลับมาไหม'],
            'partner with pronoun' => ['แฟนหนูเกิด 3/6/2497 ค่ะ'],
            'mother' => ['วันเกิดแม่ 1/1/2500 ค่ะ'],
            'owner stated after the date' => ['วันเกิด 3/6/2497 ของแฟนค่ะ'],
            'mother of mine' => ['แม่ของหนูเกิด 1/1/2500'],
            'event date (not a birth)' => ['ขาดการติดต่อตั้งแต่ 11/11/2567 เขาจะมาง้อไหม'],
            'time range answer' => ['19.00-20.30'],
            'money' => ['หนี้ 2.50 แสน จะหมดเมื่อไหร่'],
            'warning only (A4514)' => ['อย่าทำนายผิดวันเกิดนะคะ'],
            'question with an old date' => ['เรื่องเมื่อ 5/3/2530 จะกลับมาอีกไหม'],
            // กำกวม: วันที่เดียวหลังคำว่า "ผิด" อาจเป็นตัวที่ผิด — ตัวอ่านนี้ไม่เดา (โฟลแก้วันเกิดมีกล่องยืนยันกั้น)
            'single date right after a negation' => ['วันเกิดผิดค่ะ 27/6/2521 ช่วยดูใหม่'],
            'partner with a correct marker' => ['วันเกิดแฟน ที่ถูกคือ 3/6/2497'],
        ];
    }

    /**
     * @dataProvider notOwnBirthdates
     */
    public function test_never_takes_someone_elses_date_as_the_customers(string $text): void
    {
        $this->assertNull(OwnBirthDate::find($text), "ห้ามนับเป็นวันเกิดของเจ้าชะตา: {$text}");
    }

    public function test_stated_weekday_that_disagrees_is_flagged_not_trusted(): void
    {
        // 27/6/2521 (1978) เป็นวันอังคาร — ลูกค้าบอกวันอาทิตย์ ⇒ ห้ามเลือกเชื่อตัวเลขเงียบ ๆ
        $found = OwnBirthDate::find('เกิดวันอาทิตย์ 27/6/2521');
        $this->assertNotNull($found);
        $this->assertNotNull($found['conflict']);
        $this->assertSame(0, $found['conflict']['stated_day']);
        $this->assertSame(2, $found['conflict']['parsed_day']);

        $this->assertNull(OwnBirthDate::find('เกิดวันอังคาร 27/6/2521')['conflict']);

        // 🌙 เกิดก่อนรุ่งสาง โหรไทยนับเป็นวันก่อนหน้า — วันจันทร์ตี 2 ของวันที่ 27 (อังคาร) ถูกต้อง
        $this->assertNull(OwnBirthDate::find('เกิดวันจันทร์ 27/6/2521 ตี 2')['conflict']);
        $this->assertNull(OwnBirthDate::find('เกิดวันจันทร์ 27/6/2521 เวลา 02:30 น.')['conflict']);
        $this->assertNotNull(OwnBirthDate::find('เกิดวันจันทร์ 27/6/2521')['conflict'], 'ไม่บอกเวลา = ยังขัดกัน ต้องถาม');
        $this->assertNotNull(OwnBirthDate::find('เกิดวันจันทร์ 27/6/2521 บ่าย 2')['conflict'], 'บ่าย 2 ไม่ใช่ก่อนรุ่งสาง');
    }

    public function test_bare_dates_can_be_switched_off(): void
    {
        $this->assertNull(OwnBirthDate::find('3/6/2497', allowBare: false), 'กลางวงคุย วันที่เปล่า ๆ อาจเป็นของคนอื่น');
        $this->assertSame('1987-03-05', OwnBirthDate::find('หนูเกิด 5/3/2530 ค่ะ', allowBare: false)['ymd']);
    }

    public function test_birth_info_only_detection(): void
    {
        $this->assertTrue(OwnBirthDate::isBirthInfoOnly('27/6/2521'));
        $this->assertTrue(OwnBirthDate::isBirthInfoOnly('27 มิ.ย. 2521 ตี 5 เชียงใหม่ค่ะ'));
        $this->assertTrue(OwnBirthDate::isBirthInfoOnly('เกิด 27/6/2521 เวลา 06:30 น. ที่ขอนแก่นค่ะ'));
        $this->assertFalse(OwnBirthDate::isBirthInfoOnly('27/6/2521 อยากรู้เรื่องงาน'));
        $this->assertFalse(OwnBirthDate::isBirthInfoOnly('15/3/2538 อยู่ภูเก็ต'), '"อยู่" = ที่อยู่ ไม่ใช่ที่เกิด');
        $this->assertFalse(OwnBirthDate::isBirthInfoOnly('ค่ะ'));
        $this->assertFalse(OwnBirthDate::isBirthInfoOnly('โอนแล้วค่ะ'));
        // เวลา/จังหวัดลอย ๆ ไม่มีวันที่หรือคำว่าเกิด = ไม่ใช่ข้อมูลเกิด ("เลยค่ะ" เคยถูกอ่านเป็น จ.เลย)
        $this->assertFalse(OwnBirthDate::isBirthInfoOnly('2 ทุ่มค่ะ'));
        $this->assertFalse(OwnBirthDate::isBirthInfoOnly('18.30'));
        $this->assertFalse(OwnBirthDate::isBirthInfoOnly('เลยค่ะ'));
        $this->assertTrue(OwnBirthDate::isBirthInfoOnly('เกิดตี 5 ที่เชียงใหม่ค่ะ'));

        $this->assertTrue(OwnBirthDate::mentionsOtherPerson('ไม่ใช่ค่ะ เป็นวันเกิดแฟน 3/6/2497'));
        $this->assertFalse(OwnBirthDate::mentionsOtherPerson('แม่หมอคะ หนูเกิด 27/6/2521'));
    }

    public function test_mentions_birth_info(): void
    {
        $this->assertTrue(OwnBirthDate::mentionsBirthInfo('อย่าทำนายผิดวันเกิดนะคะ'));
        $this->assertTrue(OwnBirthDate::mentionsBirthInfo('หนูเกิดตี 5 ค่ะ'));
        $this->assertFalse(OwnBirthDate::mentionsBirthInfo('โอนแล้วค่ะ'));
        $this->assertFalse(OwnBirthDate::mentionsBirthInfo('ถ้าไปทำงานต่างประเทศจะเกิดอะไรขึ้น'));
    }

    public function test_birth_cue_owner(): void
    {
        $this->assertTrue(OwnBirthDate::birthCueBelongsToOther('แฟนเกิดตี 2 ค่ะ'));
        $this->assertTrue(OwnBirthDate::birthCueBelongsToOther('แม่ของหนูเกิดตอนเช้า'));
        $this->assertFalse(OwnBirthDate::birthCueBelongsToOther('หนูเกิดตี 5 ค่ะ'));
        $this->assertFalse(OwnBirthDate::birthCueBelongsToOther('แม่ หนูเกิดตี 5 ค่ะ'), 'เว้นวรรค = เรียกแม่หมอ');
        $this->assertFalse(OwnBirthDate::birthCueBelongsToOther('เกิดตอนตี 5'));
        $this->assertFalse(OwnBirthDate::birthCueBelongsToOther('ไม่มีคำนั้นเลย'));

        $this->assertTrue(OwnBirthDate::isOwnBirthTalk('หนูเกิดตี 5 ที่เชียงใหม่'));
        $this->assertFalse(OwnBirthDate::isOwnBirthTalk('แฟนเกิดตี 5 ที่เชียงใหม่'));
    }
}
