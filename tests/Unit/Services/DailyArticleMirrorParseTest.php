<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\DailyArticleMirror;
use PHPUnit\Framework\TestCase;

/**
 * 🪞 ล็อกตัวแยกคำทำนาย "โพสเพจ → กล่องแชท"
 *
 * 🚨 ทำไมต้องมี (2026-09-05): เจ้าของสั่งให้แชทดึงจากโพสรายวันแทนการยิง AI รอบสอง
 *   ⇒ กล่องแชททั้งใบแขวนอยู่กับตัว parse นี้ตัวเดียว ถ้ามันพัง ลูกค้าจะได้กล่องเปล่า
 *   หรือได้ย่อหน้าซ้ำ โดยไม่มี error สักบรรทัด (ตระกูล "ล้มเหลวแบบมองไม่เห็น")
 *
 * ⚠️ template ของแคมเปญอยู่ใน **DB** ที่แอดมินแก้เองได้ ([[rule_db_prompt_overrides_code]])
 *   ตัวแยกจึงต้องทนรูปแบบหัวข้อหลายทรง — เทสต์นี้ล็อกทรงที่เจอจริงบน prod
 *
 * ไม่ต้องใช้ DB/Laravel — parse() เป็น pure string function
 */
class DailyArticleMirrorParseTest extends TestCase
{
    protected DailyArticleMirror $mirror;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mirror = new DailyArticleMirror;
    }

    /** คำทำนายจริงจาก prod 2026-09-05 (คนเกิดวันพุธ) + บล็อกช่วงเวลาที่เพิ่มเข้ามา */
    protected function realPrediction(): string
    {
        return <<<'TXT'
🔥 **ภาพรวมวันนี้**
ดาวพุธเจ้าชนะของคนเกิดวันพุธสถิตราศีสิงห์ กุมอาทิตย์ ทำให้คำพูดและความคิดเด่นมาก

💰 **การเงิน/โชคลาภ**
เงินมาจากการพูด การติดต่อ และการค้าขายโดยตรง ช่วงบ่ายถึงเย็นคล่องตัวกว่าเช้า

💕 **ความรัก**
คนโสดมีคนคุยผ่านงานหรือการติดต่อเข้ามา แต่ต้องคุยให้ชัด อย่าใช้อารมณ์นำ

💼 **การงาน/การเรียน**
ทำงานที่ใช้การสื่อสารแล้วขึ้นมาก พุธสัมพันธ์อังคารช่วยให้ลุยงานเร็ว

🏥 **สุขภาพ**
ระวังเครียดจากการคิดมาก ปวดศีรษะและพักผ่อนไม่พอ

📌 **คำคมวันนี้:** "พูดให้ช้าลงหนึ่งจังหวะ แล้วโอกาสจะชัดขึ้นสองเท่า"
แท็กเพื่อนคนเกิดวันพุธให้มาเช็กดวงวันนี้ 🔮

[ช่วงเวลา]
เช้า (06:00-11:00): พุธจตุโกณจันทร์คลาด 2.4 องศาแรงที่สุด เลี่ยงการโต้เถียง
เที่ยง (11:00-13:00): จันทร์ย้ายเข้าราศีเมถุน จังหวะเจรจาเริ่มลื่น
บ่าย (13:00-17:00): มุมเริ่มคลาย เหมาะปิดดีล
เย็น (17:00-20:00): พุธกุมอาทิตย์ส่งผลเต็ม เหมาะนำเสนอ
กลางคืน (20:00-06:00): พักสมอง อย่าตัดสินใจเรื่องเงิน
TXT;
    }

    /**
     * 5 ด้าน + ช่วงเวลา ต้องถูกแยกครบ — นี่คือรูปทรงที่กล่องแชทคาดหวัง
     *
     * @test
     */
    public function แยกครบทั้งห้าด้านและบล็อกช่วงเวลา(): void
    {
        $out = $this->mirror->parse($this->realPrediction());

        $this->assertStringContainsString('ดาวพุธเจ้าชนะ', $out['overall']);
        $this->assertStringContainsString('เงินมาจากการพูด', (string) $out['finance']);
        $this->assertStringContainsString('คนโสดมีคนคุย', (string) $out['love']);
        $this->assertStringContainsString('ใช้การสื่อสาร', (string) $out['career']);
        $this->assertStringContainsString('ระวังเครียด', (string) $out['health']);
        $this->assertStringContainsString('เช้า (06:00-11:00)', (string) $out['time']);
        $this->assertStringContainsString('กลางคืน (20:00-06:00)', (string) $out['time']);
    }

    /**
     * 🚫 หางขอ "แท็กเพื่อน" ต้องไม่ติดเข้าไปในคำตอบบอท
     *
     * บนโพสมันคือ engagement hook ของเจ้าของ แต่ในแชทคือการขอไลก์/แชร์/แท็ก
     * ซึ่งทำให้เพจโดนลด reach ([[rule_never_ask_for_engagement_in_bot_replies]])
     *
     * @test
     */
    public function หางขอแท็กเพื่อนต้องถูกตัดทิ้ง(): void
    {
        $out = $this->mirror->parse($this->realPrediction());
        $all = implode("\n", array_filter([
            $out['overall'], $out['love'], $out['career'],
            $out['finance'], $out['health'], $out['time'],
        ]));

        $this->assertStringNotContainsString('แท็กเพื่อน', $all);
        $this->assertStringNotContainsString('กดแชร์', $all);
        $this->assertStringNotContainsString('กดไลค์', $all);

        // แต่ต้องไม่กินเนื้อจริงที่อยู่เหนือหางไปด้วย
        $this->assertStringContainsString('คำคมวันนี้', (string) $out['health']);
    }

    /**
     * บล็อกช่วงเวลาต้องถูก "ตัดออกจากเนื้อ" ไม่ใช่แค่ก็อป — โพสเพจใช้ครึ่งแรก
     *
     * @test
     */
    public function บล็อกช่วงเวลาต้องหลุดออกจากเนื้อที่โพสใช้(): void
    {
        [$head, $period] = $this->mirror->splitPeriodBlock($this->realPrediction());

        $this->assertStringNotContainsString('[ช่วงเวลา]', $head);
        $this->assertStringNotContainsString('เช้า (06:00-11:00)', $head);
        $this->assertStringContainsString('ภาพรวมวันนี้', $head);
        $this->assertNotNull($period);
        $this->assertStringContainsString('บ่าย (13:00-17:00)', (string) $period);
    }

    /** ไม่มีบล็อกช่วงเวลา (วันที่คำนวณดาวไม่ได้) ต้องไม่พัง */
    public function test_ไม่มีบล็อกช่วงเวลาก็ต้องไม่พัง(): void
    {
        [$head, $period] = $this->mirror->splitPeriodBlock("**ภาพรวมวันนี้**\nเนื้อหา");

        $this->assertNull($period);
        $this->assertStringContainsString('เนื้อหา', $head);
    }

    /**
     * 🛟 ตาข่าย: แอดมินแก้ template จนไม่มีหัวข้อเลย → ต้องยัดทั้งก้อนเป็นภาพรวม
     *
     * ห้ามคืนภาพรวมว่างเด็ดขาด — buildDailyBoxForDayIndex() คืน null ทันที
     * ถ้าภาพรวมว่าง = ลูกค้าที่เพิ่งตอบเราจะโดนบอทเงียบใส่
     *
     * @test
     */
    public function ไม่มีหัวข้อเลยต้องไม่คืนกล่องเปล่า(): void
    {
        $out = $this->mirror->parse('วันนี้ดาวพุธส่งพลังเต็มที่ เหมาะกับการเจรจาและค้าขาย');

        $this->assertNotSame('', trim($out['overall']));
        $this->assertStringContainsString('ดาวพุธส่งพลัง', $out['overall']);
        $this->assertNull($out['love']);
    }

    /**
     * ไม่มีหัวข้อ "ภาพรวม" แต่มีด้านอื่น → เลื่อนด้านแรกขึ้นเป็นภาพรวม
     * และต้อง **ไม่ซ้ำ** อยู่ในช่องเดิมด้วย (ลูกค้าจะเห็นย่อหน้าเดียวกัน 2 รอบ)
     *
     * @test
     */
    public function ไม่มีภาพรวมต้องเลื่อนด้านแรกขึ้นมาโดยไม่ซ้ำ(): void
    {
        $out = $this->mirror->parse("💕 **ความรัก**\nคนโสดจะเจอคนถูกใจ\n\n🏥 **สุขภาพ**\nนอนให้พอ");

        $this->assertStringContainsString('คนโสดจะเจอคนถูกใจ', $out['overall']);
        $this->assertNull($out['love'], 'ด้านที่ถูกเลื่อนขึ้นต้องถูกล้าง ไม่งั้นซ้ำ');
        $this->assertStringContainsString('นอนให้พอ', (string) $out['health']);
    }

    /**
     * 🚨 ทรง "หัวข้อติดเนื้อบรรทัดเดียวกัน" — คอนเทนต์จริงบน prod 4 ก.ย. 2569
     *
     * `💰 **การเงิน:** ช่วงบ่ายนี้…` (บรรทัดยาว 193 ตัว) — template เดียวกับวันอื่น
     * แต่โมเดลเลือกเขียนคนละทรง ⇒ ตัวแยกต้องรับทั้ง 2 ทรง ไม่งั้นวันไหนโมเดลเปลี่ยนใจ
     * กล่องแชทจะกลายเป็นก้อนเดียวไม่มีหัวข้อโดยไม่มีใครรู้ (3 ใน 7 ใบของวันนั้น)
     *
     * @test
     */
    public function หัวข้อที่ติดอยู่บรรทัดเดียวกับเนื้อต้องแยกออกได้(): void
    {
        $raw = "หมอจันทราสวัสดีค่ะลูก วันนี้ดาวอาทิตย์ของลูกสถิตราศีสิงห์เป็นเกษตร\n\n"
            ."💰 **การเงิน:** ช่วงบ่ายนี้หยิบจับอะไรเป็นเงินทอง เพราะดาวอาทิตย์ส่งพลัง\n\n"
            ."💕 **ความรัก:** คนมีคู่ระวังอารมณ์ที่พุ่งพล่านจากดาวจันทร์\n\n"
            ."💼 การงาน: งานที่ต้องใช้การเจรจาจะสำเร็จได้\n\n"
            ."🏥 **สุขภาพ:** ระวังอาการปวดหัวจากธาตุไฟ\n\n"
            .'ถ้าถูกใจ ฝากส่งต่อให้เพื่อนที่เกิดวันอาทิตย์มาลองดูดวงด้วย 🔮';

        $out = $this->mirror->parse($raw);

        $this->assertStringContainsString('ดาวอาทิตย์ของลูกสถิตราศีสิงห์', $out['overall']);
        $this->assertStringStartsWith('ช่วงบ่ายนี้', (string) $out['finance'], 'เศษ ":**" ต้องไม่ติดหัวเนื้อ');
        $this->assertStringStartsWith('คนมีคู่', (string) $out['love']);
        $this->assertStringStartsWith('งานที่ต้องใช้', (string) $out['career'], 'ทรงไม่มีตัวหนาก็ต้องแยกได้');
        $this->assertStringStartsWith('ระวังอาการปวดหัว', (string) $out['health']);

        // หางชวนส่งต่อให้เพื่อน = engagement bait ต้องไม่ติดเข้าแชท
        $this->assertStringNotContainsString('ส่งต่อให้เพื่อน', (string) $out['health']);
    }

    /**
     * ⚠️ ประโยคเนื้อที่ขึ้นต้นด้วยคำเดียวกับหัวข้อ ห้ามถูกตัดเป็นหัวข้อใหม่
     *
     * ตัวแยกใช้ "บรรทัดต้องสั้นไม่เกิน 40 ตัว" เป็นตัวชี้ขาด — ถ้าใครแก้ให้กว้างขึ้น
     * เทสต์นี้จะแดงก่อน (เนื้อคำทำนายจะถูกกลืนหายไปทีละย่อหน้าแบบเงียบ ๆ)
     *
     * @test
     */
    public function ประโยคเนื้อที่ขึ้นต้นเหมือนหัวข้อต้องไม่ถูกตัด(): void
    {
        $raw = "**ภาพรวมวันนี้**\n"
            ."ความรักของเจ้าชะตาวันนี้จะราบรื่นขึ้นมากเพราะดาวศุกร์ย้ายเข้าสู่ราศีที่ถูกโฉลก\n"
            ."สุขภาพจะดีขึ้นตามไปด้วยเมื่อได้พักผ่อนอย่างเต็มที่ในช่วงบ่ายของวันนี้\n";

        $out = $this->mirror->parse($raw);

        $this->assertStringContainsString('ดาวศุกร์ย้ายเข้าสู่ราศี', $out['overall']);
        $this->assertStringContainsString('ได้พักผ่อนอย่างเต็มที่', $out['overall']);
        $this->assertNull($out['love']);
        $this->assertNull($out['health']);
    }

    /** ดัชนีวันเกิดที่ระบบผลิต = 7 วัน + พุธกลางคืน */
    public function test_รายชื่อวันเกิดต้องมีแปดวันและมีพุธกลางคืน(): void
    {
        $days = DailyArticleMirror::allBirthDays();

        $this->assertCount(8, $days);
        $this->assertContains(7, $days);
        $this->assertSame('พุธกลางคืน', DailyArticleMirror::dayName(7));
        $this->assertSame('พุธ', DailyArticleMirror::dayName(3));
    }
}
