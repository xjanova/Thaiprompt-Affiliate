<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\AuspiciousTimingDirective;
use App\Services\Fortune\ThaiRuekYam;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 📅 บล็อกปฏิทินโหรไทยในพรอมต์แม่หมอ (ไม่แตะ DB ไม่บูต Laravel)
 *
 * สิ่งที่ล็อกไว้:
 *   1. ยิงเฉพาะเมื่อลูกค้า "ถามฤกษ์" — คำถามทำนาย ("จะได้แต่งงานช่วงไหน") และสำนวน ("วันดีคืนดี") ต้องไม่ยิง
 *      (ยิงผิด = พรอมต์บวม + AI ตอบวันฤกษ์ทั้งที่ลูกถามว่าจะเกิดเมื่อไหร่)
 *   2. ทุกวันที่อยู่ในบล็อกต้องเป็นวันที่ตัวคำนวณจัดว่า "ดี" จริง — ไม่มีวันลอย ๆ
 *   3. ตัดวันกาลกิณีของผู้ถามออกเมื่อรู้วันเกิด
 */
class AuspiciousTimingDirectiveTest extends TestCase
{
    private static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-09-21 10:00', new \DateTimeZone('Asia/Bangkok'));
    }

    public static function asksCases(): array
    {
        return [
            'ออกรถวันไหนดี' => ['จะออกรถใหม่ วันไหนดีคะ', 'car'],
            'ขอฤกษ์แต่งงาน' => ['ขอฤกษ์แต่งงานหน่อยค่ะ', 'wedding'],
            'ขึ้นบ้านใหม่' => ['ขอฤกษ์ขึ้นบ้านใหม่ค่ะ', 'house'],
            'เปิดร้านช่วงไหนดี' => ['ควรเปิดร้านช่วงไหนดี', 'shop'],
            'ตั้งศาลบ้านใหม่ = ตั้งศาล ไม่ใช่ขึ้นบ้าน' => ['ฤกษ์ตั้งศาลพระภูมิบ้านใหม่', 'shrine'],
            'สร้างบ้านใหม่ = เสาเอก' => ['จะสร้างบ้านใหม่ วันไหนดี', 'pillar'],
            'หมั้นก่อนแต่ง' => ['ฤกษ์หมั้นก่อนแต่งงาน', 'engage'],
            'ฤกษ์ทั่วไป' => ['เดือนหน้ามีฤกษ์ดีวันไหนบ้าง', null],
        ];
    }

    #[Test]
    #[DataProvider('asksCases')]
    public function detects_questions_asking_for_auspicious_timing(string $text, ?string $activity): void
    {
        $this->assertSame($activity, AuspiciousTimingDirective::detectActivity($text));
        $this->assertTrue(AuspiciousTimingDirective::asksForTiming($text, $activity !== null));
        $block = (new AuspiciousTimingDirective)->forCustomerText($text, '', null, self::now());
        $this->assertStringContainsString('📅 ปฏิทินโหรไทย', $block);
    }

    public static function notAskingCases(): array
    {
        return [
            'ถามว่าจะได้แต่งเมื่อไหร่ = ทำนาย' => ['จะได้แต่งงานช่วงไหนคะ'],
            'สำนวน วันดีคืนดี' => ['วันดีคืนดีเขาก็หายไปเลยค่ะ'],
            'รู้สึก ไม่ใช่ สึก' => ['รู้สึกเหนื่อยมาก วันไหนจะดีขึ้น'],
            'ถามงานเฉยๆ' => ['งานใหม่จะรุ่งไหมคะ'],
            'สีมงคล ไม่ใช่ฤกษ์' => ['ใส่สีมงคลอะไรดีคะ'],
            'เล่าเรื่องแต่งงาน ไม่ได้ถามวัน' => ['แต่งงานมา 5 ปีแล้วค่ะ สามีไม่ค่อยกลับบ้าน'],
            // ↓ เคสจากรอบจับผี 2026-09-21 — ทุกอันเคยได้ปฏิทินฤกษ์ทั้งที่ลูกค้าไม่ได้ถามฤกษ์
            'จะมีวันที่ดีขึ้นไหม = ทำนาย' => ['ชีวิตหนูจะมีวันที่ดีขึ้นไหมคะ'],
            'อวยพรวันดี' => ['ขอให้มีวันดี ๆ นะคะ'],
            'ขอบคุณ วันนี้วันดี' => ['ขอบคุณค่ะ วันนี้เป็นวันดีมาก'],
            'คู่สมรส ไม่ใช่งานแต่ง' => ['คู่สมรสนอกใจ ควรทำยังไงดีคะ'],
            'ควรย้ายงาน ไม่ใช่ถามฤกษ์' => ['ทำงานก่อสร้าง ควรย้ายงานไหม'],
            'ควรทำบุญอะไร ไม่ใช่ถามวัน' => ['ควรทำบุญอะไรดี'],
            'ควรทำสัญญาไหม ไม่ใช่ถามวัน' => ['ควรทำสัญญากับเขาไหม'],
            'งานจะดีขึ้นช่วงไหน = ทำนาย' => ['งานจะดีขึ้นช่วงไหนคะ'],
        ];
    }

    #[Test]
    public function an_old_activity_does_not_turn_a_prediction_question_into_a_timing_request(): void
    {
        $d = new AuspiciousTimingDirective;
        $this->assertSame('', $d->forCustomerText('เงินจะเข้าเดือนไหนคะ', 'อยากทำบุญบ้านค่ะ', null, self::now()));
        $this->assertSame('', $d->forCustomerText('งานจะดีขึ้นช่วงไหน', 'คู่สมรสนอกใจค่ะ', null, self::now()));
    }

    #[Test]
    public function a_birth_month_is_not_the_month_the_customer_wants(): void
    {
        $this->assertNull(AuspiciousTimingDirective::detectTargetMonth('เกิด 12 มีนาคม 2530 ค่ะ อยากออกรถ วันไหนดี'));
        $this->assertNull(AuspiciousTimingDirective::detectTargetMonth('เกิดเดือนมีนาค่ะ ขอฤกษ์ออกรถ'));
        $this->assertSame(12, AuspiciousTimingDirective::detectTargetMonth('ขอฤกษ์แต่งงานเดือนธันวาค่ะ'));

        $block = (new AuspiciousTimingDirective)->forCustomerText('เกิด 12 มีนาคม 2530 ค่ะ อยากออกรถ วันไหนดี', '', null, self::now());
        $this->assertStringContainsString('ช่วง 22 ก.ย. 2569', $block, 'ต้องหาฤกษ์จากพรุ่งนี้ ไม่ใช่กระโดดไปเดือนมีนาคมปีหน้า');
    }

    #[Test]
    public function a_short_month_follow_up_opens_that_month(): void
    {
        // บล็อกชวนลูกค้า "บอกเดือนที่ต้องการ" → ข้อความตามต่อต้องได้ปฏิทินเดือนนั้นจริง
        $block = (new AuspiciousTimingDirective)->forCustomerText('ขอเดือนธันวาค่ะ', 'ขอฤกษ์ออกรถใหม่ค่ะ', null, self::now());
        $this->assertStringContainsString('ฤกษ์ออกรถใหม่ ช่วง 1 ธ.ค. 2569', $block);

        // ไม่เคยถามฤกษ์มาก่อน → ชื่อเดือนเฉย ๆ ไม่ใช่คำขอฤกษ์
        $this->assertSame('', (new AuspiciousTimingDirective)->forCustomerText('ขอเดือนธันวาค่ะ', 'งานจะดีไหมคะ', null, self::now()));
    }

    #[Test]
    #[DataProvider('notAskingCases')]
    public function stays_silent_when_the_customer_is_not_asking_for_timing(string $text): void
    {
        $this->assertSame('', (new AuspiciousTimingDirective)->forCustomerText($text, '', null, self::now()));
    }

    #[Test]
    public function the_activity_can_come_from_the_previous_customer_turn(): void
    {
        $block = (new AuspiciousTimingDirective)->forCustomerText('แล้ววันไหนดีคะ', 'จะออกรถใหม่เดือนหน้า', null, self::now());
        $this->assertStringContainsString('ฤกษ์ออกรถใหม่', $block);

        // เทิร์นนี้ไม่ได้ถามเวลา → ไม่ยิง แม้เทิร์นก่อนจะพูดเรื่องออกรถ
        $this->assertSame('', (new AuspiciousTimingDirective)->forCustomerText('ขอบคุณค่ะ', 'ขอฤกษ์ออกรถ', null, self::now()));
    }

    #[Test]
    public function every_listed_day_is_a_day_the_engine_classifies_as_good(): void
    {
        $block = (new AuspiciousTimingDirective)->forCustomerText('ขอฤกษ์ออกรถค่ะ', '', null, self::now());
        preg_match_all('/^  - \S+ (\d{1,2}) (\S+) (\d{4}) \(/mu', $block, $m, PREG_SET_ORDER);
        $this->assertNotEmpty($m, $block);

        $months = ['ม.ค.' => 1, 'ก.พ.' => 2, 'มี.ค.' => 3, 'เม.ย.' => 4, 'พ.ค.' => 5, 'มิ.ย.' => 6, 'ก.ค.' => 7, 'ส.ค.' => 8, 'ก.ย.' => 9, 'ต.ค.' => 10, 'พ.ย.' => 11, 'ธ.ค.' => 12];
        $engine = new ThaiRuekYam;
        foreach ($m as [, $d, $mon, $be]) {
            $ymd = sprintf('%04d-%02d-%02d', (int) $be - 543, $months[$mon], (int) $d);
            $D = $engine->day($ymd);
            $c = ThaiRuekYam::classify(ThaiRuekYam::evaluate('car', $D), $D);
            $this->assertSame('good', $c['cls'], "{$ymd} อยู่ในรายการแต่ตัวคำนวณไม่ได้จัดว่าดี");
            $this->assertNotContains($D['wd'], [2, 6], 'ตำราห้ามออกรถวันอังคาร/เสาร์');
        }
    }

    #[Test]
    public function drops_the_askers_kalakini_weekday_when_the_birthday_is_known(): void
    {
        $d = new AuspiciousTimingDirective;
        $withoutPerson = $d->forCustomerText('ขอฤกษ์ออกรถค่ะ', '', null, self::now());
        $this->assertStringContainsString('ยังไม่รู้วันเกิดเจ้าของงาน', $withoutPerson);

        // ผู้ถามมีกาลกิณีวันพฤหัสบดี → ต้องไม่มี "พฤ." ในรายการ และบอกว่าตัดแล้ว
        $person = ['kala_planet' => 'พฤหัสบดี', 'kala_wd' => 4, 'good_wd' => [5 => 'ศรี']];
        $block = $d->forCustomerText('ขอฤกษ์ออกรถค่ะ', '', $person, self::now());
        $this->assertStringContainsString('ตัดวันพฤหัสบดี ซึ่งเป็นวันกาลกิณีของผู้ถามออกแล้ว', $block);
        $this->assertDoesNotMatchRegularExpression('/^  - พฤ\. /mu', $block);
        $this->assertStringNotContainsString('ยังไม่รู้วันเกิด', $block);
    }

    #[Test]
    public function kalayok_line_warns_when_a_good_day_is_also_a_bad_day(): void
    {
        // จ.ศ. 1388 (2569): วันจันทร์เป็นทั้งธงชัยและโลกาวินาศ ⇒ ต้องบอกว่าไม่นับเป็นวันมงคล
        $block = (new AuspiciousTimingDirective)->generalBlock('', null, self::now());
        $this->assertStringContainsString('จ.ศ. 1388', $block);
        $this->assertStringContainsString('ธงชัย = วันจันทร์', $block);
        $this->assertStringContainsString('วันจันทร์เป็นทั้งธงชัยและโลกาวินาศ', $block);
        $this->assertDoesNotMatchRegularExpression('/^  - จ\. /mu', $block, 'วันจันทร์ปีนี้ห้ามอยู่ในรายการวันมงคล');
    }

    #[Test]
    public function a_named_month_without_good_days_is_said_out_loud(): void
    {
        // ธันวาคม 2569 ไม่มีวันแต่งงานที่ผ่านเกณฑ์ตำรา (วันพระ/เดือนอ้าย/วารห้าม) → ต้องเตือน ไม่ใช่ปล่อยให้เข้าใจว่าวัน ม.ค. คือ ธ.ค.
        $block = (new AuspiciousTimingDirective)->forCustomerText('ขอฤกษ์แต่งงานเดือนธันวาค่ะ', '', null, self::now());
        $this->assertStringContainsString('ช่วง 1 ธ.ค. 2569', $block);
        $this->assertStringContainsString('เดือนธ.ค. ที่ลูกถาม *ไม่มีวันผ่านเกณฑ์ตำราเลย*', $block);
    }

    #[Test]
    public function today_line_uses_the_real_lunar_calendar(): void
    {
        // 21 ก.ย. 2569 = ขึ้น 10 ค่ำ · วันพระถัดไป 26 ก.ย. ห่าง 5 วัน → ยังไม่บอก (กัน AI แปะ "ทำบุญวันพระ" ทุกวัน)
        $line = AuspiciousTimingDirective::todayLine(self::now());
        $this->assertStringContainsString('ขึ้น 10 ค่ำ เดือนสิบ', $line);
        $this->assertStringNotContainsString('วันพระ', $line);

        // 24 ก.ย. → วันพระอีก 2 วัน → ยังไม่พูด · 25 ก.ย. → พรุ่งนี้วันพระ → บอก
        $twoDays = AuspiciousTimingDirective::todayLine(new \DateTimeImmutable('2026-09-24 09:00', new \DateTimeZone('Asia/Bangkok')));
        $this->assertStringNotContainsString('วันพระ', $twoDays);
        $near = AuspiciousTimingDirective::todayLine(new \DateTimeImmutable('2026-09-25 09:00', new \DateTimeZone('Asia/Bangkok')));
        $this->assertStringContainsString('พรุ่งนี้ (26 ก.ย. 2569) เป็นวันพระ ขึ้น 15 ค่ำ เดือนสิบ', $near);

        $holy = AuspiciousTimingDirective::todayLine(new \DateTimeImmutable('2026-09-19 09:00', new \DateTimeZone('Asia/Bangkok')));
        $this->assertStringContainsString('วันนี้วันพระ', $holy);
    }
}
