<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\FortuneBubbleSplitter;
use App\Services\Fortune\FortuneMessengerFormatter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ✦ แต่งหัวข้อคำทำนายบน Messenger (2026-09-13) — แบบ ก "【 หัวข้อ 】 + เส้นคั่นสั้น"
 *
 * รูปทรงข้อความทดสอบเลียนคำตอบข้อแรกของ Celtic 99 บน prod (ตรวจ 12 ใบล่าสุด):
 *   หัวข้อขึ้นต้นด้วยอีโมจิประจำเซคชั่น แล้ว "เว้นบรรทัดว่าง" ก่อนเนื้อทุกครั้ง
 *   ⇒ ตัวผ่ากล่องเดิมมองหัวข้อเป็นย่อหน้าเดี่ยว = หัวข้อค้างท้ายกล่องได้
 * คำตอบข้อ 2 เป็นต้นไปไม่มีหัวข้อ ⇒ ต้องผ่านไปแบบเดิมทุกตัวอักษร
 *
 * ไม่ boot แอป — คลาสที่ทดสอบไม่แตะ config/DB
 */
class FortuneMessengerFormatterTest extends TestCase
{
    private function q1Answer(bool $withPreamble = false): string
    {
        $sections = [
            '🎯 เรื่องเด่นรอบนี้' => 'ไพ่ The Tower ขึ้นตำแหน่งอนาคตอันใกล้ แม่หมอฟันธงเลยนะลูก งานที่ทำอยู่จะเปลี่ยนกะทันหันก่อนสิ้นเดือนตุลาคม แต่ปลายทางได้เงินดีกว่าเดิม',
            '🌟 พื้นฐานดวง' => "ลูกเกิดวันพุธกลางวัน ดาวพุธเป็นเจ้าชะตา ใจเร็ว คิดไว พูดเก่ง\n\nจุดอ่อนคือใจอ่อนกับคนใกล้ตัว ยอมคนง่ายเกินไป",
            '🔮 ภาพรวมชีวิตช่วงนี้' => 'ช่วงนี้อยู่ปลายวงจรเก่า เรื่องที่ค้างคาจะจบลงภายในสองเดือน',
            '🕛 จันทร์ลัคน์ & เรือนชะตา' => 'จันทร์ลัคน์ราศีกรกฎ ใจข้างในอ่อนไหว ต้องการความมั่นคง',
            '💞 ความรัก' => 'คนที่ลูกคุยอยู่เป็นผู้ชายอายุมากกว่า 3-5 ปี ใจจริงเขาชอบ',
            '💼 การงาน' => 'หัวหน้าผู้หญิงวัย 40 กว่ากำลังจับตาผลงานลูกอยู่',
            '💰 การเงิน' => 'เงินเข้าเป็นก้อนจากงานพิเศษ ช่วงสัปดาห์ที่สองของเดือนหน้า',
            '🍀 โชคลาภ' => 'โชคมาจากผู้ใหญ่ฝ่ายหญิง ไม่ใช่จากการเสี่ยงดวง',
            '🌿 สุขภาพ' => 'ระวังกระเพาะกับการนอนดึก',
            '🕉️ องค์เทพ/สิ่งศักดิ์สิทธิ์คุ้มครอง' => 'องค์พระพิฆเนศคุ้มครองเรื่องงาน บูชาวันพุธด้วยดอกไม้สีเหลือง',
        ];

        $parts = $withPreamble ? ['ลูกเอ๋ย แม่หมอเปิดไพ่ครบ 10 ใบแล้ว ฟังให้ดีนะ'] : [];
        foreach ($sections as $header => $body) {
            $parts[] = $header;
            $parts[] = $body;
        }

        return implode("\n\n", $parts);
    }

    #[Test]
    public function headers_are_bracketed_glued_to_body_and_sections_are_divided(): void
    {
        $out = (new FortuneMessengerFormatter)->format($this->q1Answer());

        $this->assertStringStartsWith("【 🎯 เรื่องเด่นรอบนี้ 】\nไพ่ The Tower", $out, 'หัวข้อแรกไม่มีเส้นนำหน้า + ติดกับเนื้อ');
        $this->assertStringContainsString("\n\n".FortuneMessengerFormatter::DIVIDER."\n\n【 💞 ความรัก 】\nคนที่ลูกคุยอยู่", $out);
        $this->assertStringContainsString("【 🕉️ องค์เทพ/สิ่งศักดิ์สิทธิ์คุ้มครอง 】\n", $out, 'อีโมจิที่มี VS16 ต้องจับได้');
        $this->assertSame(10, substr_count($out, '【'));
        $this->assertSame(9, substr_count($out, FortuneMessengerFormatter::DIVIDER));

        // ย่อหน้าที่ 2 ในเซคชั่นเดียวกันยังเว้นบรรทัดเหมือนเดิม (ไม่ถูกบีบ)
        $this->assertStringContainsString("พูดเก่ง\n\nจุดอ่อนคือ", $out);
    }

    /**
     * 🔒 ด่านตรวจนับอีโมจิหัวข้อด้วย str_contains — แต่งแล้วอีโมจิทุกตัวต้องยังอยู่
     */
    #[Test]
    public function every_gate_emoji_survives_formatting(): void
    {
        $out = (new FortuneMessengerFormatter)->format($this->q1Answer());

        foreach (['🎯', '🌟', '🔮', '🕛', '💞', '💼', '💰', '🍀', '🌿'] as $emoji) {
            $this->assertStringContainsString($emoji, $out);
        }
    }

    #[Test]
    public function preamble_gets_a_divider_before_the_first_header(): void
    {
        $out = (new FortuneMessengerFormatter)->format($this->q1Answer(true));

        $this->assertStringStartsWith('ลูกเอ๋ย แม่หมอเปิดไพ่ครบ 10 ใบแล้ว ฟังให้ดีนะ'."\n\n".FortuneMessengerFormatter::DIVIDER."\n\n【 🎯 เรื่องเด่นรอบนี้ 】", $out);
    }

    /**
     * คำตอบข้อ 2+ ไม่มีโครงหัวข้อ / มีบรรทัดอีโมจิแค่บรรทัดเดียว / ประโยคยาวขึ้นต้นด้วยอีโมจิ → ห้ามแตะ
     */
    #[Test]
    public function text_without_a_section_structure_is_untouched(): void
    {
        $f = new FortuneMessengerFormatter;

        $plain = "ลูกถามว่าเขาจะกลับมาไหม แม่หมอเห็นว่าเขาจะทักมาก่อนสิ้นเดือนนี้\n\nแต่ลูกต้องไม่ง้อก่อน";
        $this->assertSame($plain, $f->format($plain));

        $oneHeader = "💞 ความรัก\n\nเขาจะทักมาก่อนสิ้นเดือน";
        $this->assertSame($oneHeader, $f->format($oneHeader));

        $sentences = "💰 เงินก้อนนี้จะเข้ามาช่วงกลางเดือน แต่ห้ามให้ญาติยืมเด็ดขาดเพราะยืมแล้วไม่ได้คืนแน่นอนนะลูก จำไว้\n\n"
            .'💞 ส่วนเรื่องความรักนั้นเขาชอบลูกจริง แต่ยังไม่พร้อมเปิดตัวเพราะติดเรื่องทางบ้านของเขาอยู่ ใจเย็นก่อน';
        $this->assertSame($sentences, $f->format($sentences));

        $this->assertSame('', $f->format(''));

        // รายการ "หัวข้อ: ค่า" เรียงติดกัน (เช่น ของเสริมดวงในบทสรุป) — ห้ามยกเป็นหัวข้อ/แทรกเส้นกลางรายการ
        $list = "ของเสริมดวงของลูก\n\n🍀 เลขนำโชค: 3, 7\n🌿 สีมงคล: เขียว\n\n💰 การเงิน: ดีขึ้น\n\nขอให้โชคดีนะลูก";
        $this->assertSame($list, $f->format($list));

        // หัวข้อที่ติดกับเนื้อโดยไม่เว้นบรรทัด = ไม่ใช่รูปทรงที่เรารู้จัก → ไม่แตะ (เลือกปลอดภัยไว้ก่อน)
        $glued = "💞 ความรัก\nเขาชอบลูก\n\n💼 การงาน\nงานดี";
        $this->assertSame($glued, $f->format($glued));
    }

    #[Test]
    public function formatting_twice_changes_nothing(): void
    {
        $f = new FortuneMessengerFormatter;
        $once = $f->format($this->q1Answer(true));

        $this->assertSame($once, $f->format($once));
    }

    /**
     * 🇹🇭 หัวข้อลงท้ายด้วย "บ" + โคลอน — rtrim(":：") จะตัดไบต์ท้ายของ บ ทิ้ง (0x9A) ต้องไม่เกิด
     */
    #[Test]
    public function trailing_colon_is_removed_without_breaking_thai(): void
    {
        $text = "💼 งานและระบบ:\n\nงานเข้าเยอะ\n\n💰 เงินทอง：\n\nเงินเข้าเป็นก้อน";
        $out = (new FortuneMessengerFormatter)->format($text);

        $this->assertStringContainsString('【 💼 งานและระบบ 】', $out);
        $this->assertStringContainsString('【 💰 เงินทอง 】', $out);
        $this->assertTrue(mb_check_encoding($out, 'UTF-8'));
    }

    /**
     * ผ่ากล่องจริงด้วย FortuneBubbleSplitter แล้วตัดขอบ — กล่องต้องไม่ขึ้นต้น/ลงท้ายด้วยเส้นคั่น
     * และหัวข้อต้องไม่ค้างเป็นบรรทัดสุดท้ายของกล่อง (อาการเดิมที่หัวข้อเป็นย่อหน้าเดี่ยว)
     */
    #[Test]
    public function split_bubbles_never_start_or_end_with_a_divider_or_a_lonely_header(): void
    {
        $f = new FortuneMessengerFormatter;
        $bubbles = $f->trimBubbleEdges((new FortuneBubbleSplitter)->split($f->format($this->q1Answer(true)), 4));

        $this->assertGreaterThanOrEqual(2, count($bubbles));
        foreach ($bubbles as $bubble) {
            $lines = preg_split('/\R/u', $bubble);
            $this->assertNotSame(FortuneMessengerFormatter::DIVIDER, trim($lines[0]));
            $this->assertNotSame(FortuneMessengerFormatter::DIVIDER, trim(end($lines)));
            $this->assertStringStartsNotWith('【', trim(end($lines)), 'หัวข้อค้างท้ายกล่อง: '.end($lines));
        }

        // ไม่มีเนื้อหาหาย — ตัดเส้นคั่นออกทั้งสองฝั่งแล้วต้องเท่ากัน
        $strip = fn (string $s) => preg_replace('/\s+/u', '', str_replace(FortuneMessengerFormatter::DIVIDER, '', $s));
        $this->assertSame($strip($f->format($this->q1Answer(true))), $strip(implode("\n\n", $bubbles)));
    }

    /**
     * ⭐ แบบ ข — บล็อกคะแนน "ความรัก: 4/5" ท้ายพื้นดวง → ดาวต้นบรรทัด + หัวใหม่ + เส้นคั่นก่อนบล็อก
     *    บรรทัดชวนถามที่ปิดท้ายต้องยังอยู่หลังบล็อก
     */
    #[Test]
    public function score_block_becomes_stars_with_its_own_header(): void
    {
        $text = $this->q1Answer()
            ."\n\n⭐ สรุปคะแนนดวง\nความรัก: 4/5\nการงาน: 3/5\nการเงิน: 5/5\nโชคลาภ: 2/5\nสุขภาพ: 1/5"
            ."\n\nอยากรู้เรื่องไหนลึก ๆ พิมพ์ถามแม่หมอได้เลยนะลูก";
        $out = (new FortuneMessengerFormatter)->format($text);

        $this->assertStringContainsString(
            FortuneMessengerFormatter::DIVIDER."\n\n".FortuneMessengerFormatter::SCORE_TITLE
            ."\n★★★★☆  ความรัก\n★★★☆☆  การงาน\n★★★★★  การเงิน\n★★☆☆☆  โชคลาภ\n★☆☆☆☆  สุขภาพ"
            ."\n\nอยากรู้เรื่องไหนลึก ๆ พิมพ์ถามแม่หมอได้เลยนะลูก",
            $out
        );
        $this->assertStringNotContainsString('⭐ สรุปคะแนนดวง', $out, 'หัวเดิมต้องถูกแทน ไม่ค้างสองหัว');
        $this->assertStringNotContainsString('/5', $out);
        $this->assertSame($out, (new FortuneMessengerFormatter)->format($out), 'แต่งซ้ำไม่เปลี่ยน');

        // ผ่ากล่องแล้วหัวบล็อกคะแนนต้องติดกับดาว ไม่ค้างท้ายกล่อง
        $f = new FortuneMessengerFormatter;
        foreach ($f->trimBubbleEdges((new FortuneBubbleSplitter)->split($out, 4)) as $bubble) {
            $lines = preg_split('/\R/u', $bubble);
            $this->assertNotSame(FortuneMessengerFormatter::SCORE_TITLE, trim(end($lines)));
        }
    }

    /**
     * รูปแบบไม่ตรงเป๊ะ = ไม่แตะคะแนน (ปล่อยตัวเลขไว้ ดีกว่าวาดดาวผิด)
     */
    #[Test]
    public function malformed_score_lines_are_left_alone(): void
    {
        $f = new FortuneMessengerFormatter;

        $outOfRange = "⭐ สรุปคะแนนดวง\nความรัก: 7/5\nการงาน: 3/5\nการเงิน: 0/5";
        $this->assertSame($outOfRange, $f->format($outOfRange));

        $tooFew = "⭐ สรุปคะแนนดวง\nความรัก: 4/5\nการงาน: 3/5";
        $this->assertSame($tooFew, $f->format($tooFew));

        $withNotes = "ความรัก: 4/5 เพราะเขาชอบลูก\nการงาน: 3/5 งานหนัก\nการเงิน: 4/5 มีเงินเข้า";
        $this->assertSame($withNotes, $f->format($withNotes));

        // ไม่มีโคลอน / เว้นวรรครอบ "/" ก็ยังเป็นบรรทัดคะแนน
        $loose = "ความรัก 4/5\nการงาน : 3 / 5\nการเงิน：5/5";
        $this->assertSame(FortuneMessengerFormatter::SCORE_TITLE."\n★★★★☆  ความรัก\n★★★☆☆  การงาน\n★★★★★  การเงิน", $f->format($loose));

        // หัว ⭐ เว้นบรรทัดก่อนคะแนน → ยังถูกแทน ไม่ค้างสองหัว
        $spaced = "⭐ สรุปคะแนนดวง\n\nความรัก: 4/5\nการงาน: 3/5\nการเงิน: 4/5";
        $this->assertSame(FortuneMessengerFormatter::SCORE_TITLE."\n★★★★☆  ความรัก\n★★★☆☆  การงาน\n★★★★☆  การเงิน", $f->format($spaced));
    }

    /**
     * 🔒 สเปกบล็อกคะแนนในพรอมต์ Celtic ต้องใช้ชื่อด้านชุดเดียวกับตัวแปลง และห้ามมีอีโมจิหัวข้ออื่น
     *    (ด่าน must เช็ค str_contains ทั้งก้อน — อีโมจิที่โมเดลลอกจากสเปกทำให้ด่านผ่านทั้งที่เซคชั่นหาย)
     */
    #[Test]
    public function celtic_score_spec_matches_formatter_and_carries_no_section_emoji(): void
    {
        $spec = \App\Services\CelticCrossService::scoreSummarySpec();

        foreach (FortuneMessengerFormatter::SCORE_AREAS as $area) {
            $this->assertStringContainsString("{$area}: N/5", $spec);
        }
        foreach (FortuneMessengerFormatter::HEADER_EMOJI as $emoji) {
            $this->assertStringNotContainsString($emoji, $spec, "สเปกคะแนนห้ามมี {$emoji}");
        }
    }

    /**
     * 📏 เส้นล้วนยาวเกิน 10 ตัว → 10 ตัว (ตัวอักษรเดิม) · บรรทัดที่มีข้อความปน / เส้นสั้นอยู่แล้ว ไม่แตะ
     */
    #[Test]
    public function long_divider_lines_are_shortened_to_ten(): void
    {
        $menu = "💎 อัตราค่าดูดวงกับแม่หมอจันทรา 💎\n\n━━━━━━━━━━━━━━━━━\n🔹 แพ็คเกจ 39 บาท\n═══════════════════════\nบิล\n  ──────────────────────  \n── หัวข้อ ──────────────\n┈┈┈┈┈┈┈┈┈┈";
        $out = FortuneMessengerFormatter::shortenDividers($menu);

        $this->assertSame(
            "💎 อัตราค่าดูดวงกับแม่หมอจันทรา 💎\n\n━━━━━━━━━━\n🔹 แพ็คเกจ 39 บาท\n══════════\nบิล\n──────────\n── หัวข้อ ──────────────\n┈┈┈┈┈┈┈┈┈┈",
            $out
        );
        $this->assertSame('', FortuneMessengerFormatter::shortenDividers(''));
        $this->assertSame('ไม่มีเส้น', FortuneMessengerFormatter::shortenDividers('ไม่มีเส้น'));
    }

    #[Test]
    public function trim_bubble_edges_drops_edge_dividers_and_empty_bubbles(): void
    {
        $d = FortuneMessengerFormatter::DIVIDER;
        $out = (new FortuneMessengerFormatter)->trimBubbleEdges([
            "เนื้อหาแรก\n\n{$d}",
            "{$d}\n\n【 💞 ความรัก 】\nเขาชอบ\n\n{$d}\n\n【 💼 การงาน 】\nงานดี",
            "\n{$d}\n",
        ]);

        $this->assertSame(['เนื้อหาแรก', "【 💞 ความรัก 】\nเขาชอบ\n\n{$d}\n\n【 💼 การงาน 】\nงานดี"], $out);
    }
}
