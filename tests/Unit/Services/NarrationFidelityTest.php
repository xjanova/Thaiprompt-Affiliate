<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\NarrationFidelity;
use App\Services\Fortune\ReadingOutline;
use PHPUnit\Framework\TestCase;

/**
 * 🎬 (2026-11-04) บทเล่าคำทำนายต้องไม่แต่งเพิ่ม — ตัวตรวจต้องจับทุกกรณีที่ owner ถาม
 * (ไพ่ผิดใบ · เดือนผิด · ตัวเลขแต่ง · ฟันธงกลับข้าง · คุณไสยไม่ตรง) และปล่อยบทที่ตรงผ่าน
 */
class NarrationFidelityTest extends TestCase
{
    public const THREE = <<<'TXT'
## 🎯 ฟันธง
ผล: ใช่ ความรักครั้งนี้ไปต่อได้
ไพ่หนุนให้เดินหน้า

## 🃏 ใบที่ 1 · อดีต
ไพ่คู่รักตั้งตรง คุณเคยมีความสัมพันธ์ที่ดีมาก่อน

## 🃏 ใบที่ 2 · ปัจจุบัน
ไพ่หอคอยกลับหัว ตอนนี้กำลังผ่านแรงสั่นคลอน ช่วงวันที่ 15 ระวังคำพูด

## 🃏 ใบที่ 3 · อนาคต
ไพ่ดวงดาวตั้งตรง ความหวังกลับมา สีมงคลคือสีชมพู ทิศมงคลคือทิศตะวันออก

## 🌠 ดวงดาววันเกิด
คุณเกิดวันพุธ ราศีมีน ธาตุน้ำ ใจอ่อนโยน

## 🧭 คำแนะนำ
ใจเย็น สื่อสารตรง ๆ
TXT;

    public const YEAR = <<<'TXT'
## 🎯 ฟันธง
ผล: ปีนี้ดีกว่าปีก่อน
## 📅 มกราคม · ดี
ธีม: เริ่มต้นใหม่ งานเด่น
## 📅 กุมภาพันธ์ · ระวัง
ธีม: เงินรั่ว ใช้จ่ายระวัง
## 📅 มีนาคม · กลาง
ธีม: ทรงตัว
## ⚠ เดือนที่ต้องระวัง
กุมภาพันธ์ เรื่องเงิน
TXT;

    public const KUNSAI = <<<'TXT'
## 🪬 ผลวินิจฉัย
พบของ: ไม่ใช่
ชนิด: -
## 🃏 ใบที่ 1 · ต้นเหตุ
ไพ่ดวงจันทร์ตั้งตรง ความกังวลในใจทำให้คิดไปเอง
## 🙏 ทางแก้
สวดมนต์ก่อนนอน ทำจิตให้นิ่ง
TXT;

    private const CARDS = [
        1 => ['name' => 'คู่รัก', 'reversed' => false],
        2 => ['name' => 'หอคอย', 'reversed' => true],
        3 => ['name' => 'ดวงดาว', 'reversed' => false],
    ];

    private function ctx(string $reading, ?string $sectionType = null, ?int $pos = null, string $persona = 'maemor', array $cards = self::CARDS): array
    {
        $outline = ReadingOutline::parse($reading);
        $section = null;
        $months = 0;
        foreach ($outline['sections'] as $s) {
            if ($s['type'] === 'month') {
                $months++;
            }
            if ($s['type'] === $sectionType && ($pos === null || ($s['pos'] ?? $months) === $pos)) {
                $section = $s;
                break;
            }
        }

        return ['persona' => $persona, 'reading' => $reading, 'outline' => $outline, 'cards' => $cards, 'section' => $section];
    }

    private function check(string $anchor, string $say, array $ctx): array
    {
        return NarrationFidelity::check(['anchor' => $anchor, 'say' => $say], $ctx);
    }

    public function test_the_outline_reads_every_heading_the_web_writes(): void
    {
        $o = ReadingOutline::parse(self::THREE);
        $this->assertSame(['verdict', 'card', 'card', 'card', 'birth', 'advice'], array_column($o['sections'], 'type'));
        $this->assertSame([1, 2, 3], array_values(array_filter(array_column($o['sections'], 'pos'))));
        $this->assertSame('ใช่ ความรักครั้งนี้ไปต่อได้', $o['verdict']);
        $this->assertSame('yes', ReadingOutline::polarity($o['verdict']));

        $y = ReadingOutline::parse(self::YEAR);
        $months = array_values(array_filter($y['sections'], fn ($s) => $s['type'] === 'month'));
        $this->assertSame(['มกราคม', 'กุมภาพันธ์', 'มีนาคม'], array_column($months, 'month'));
        $this->assertSame(['good', 'caution', 'neutral'], array_column($months, 'tone'));
        // ⚠ ที่ไม่มี U+FE0F ก็ยังเป็นหัวข้อเดือนที่ต้องระวัง
        $this->assertSame('caution', end($y['sections'])['type']);

        $this->assertSame('ไม่ใช่', ReadingOutline::parse(self::KUNSAI)['kunsai']);
        $this->assertSame(2, ReadingOutline::parse("## 🎯 ฟันธง\nผล: เลือกทางเลือกที่ 2\n## ✅ ไพ่เลือกทาง\nเลือก: ทางเลือกที่ 2")['choice']);
    }

    public function test_a_faithful_chapter_passes(): void
    {
        $this->assertSame([], $this->check('card:2', '[softly] ใบที่สองหอคอยกลับหัว ช่วงวันที่ 15 คุณระวังคำพูดไว้นะ', $this->ctx(self::THREE, 'card', 2)));
        $this->assertSame([], $this->check('card:3', 'ดวงดาวมาแล้ว ความหวังกลับมา ลองใส่สีชมพู หันไปทางทิศตะวันออก', $this->ctx(self::THREE, 'card', 3)));
        $this->assertSame([], $this->check('summary', '[warmly] คำตอบคือใช่จ้ะ ความรักครั้งนี้ไปต่อได้ แค่ใจเย็นไว้', $this->ctx(self::THREE)));
        $this->assertSame([], $this->check('birth', 'คุณเกิดวันพุธ ราศีมีน ธาตุน้ำ ใจอ่อนโยนนะ', $this->ctx(self::THREE, 'birth')));
    }

    public function test_another_cards_name_in_a_card_chapter_is_caught(): void
    {
        $reasons = $this->check('card:1', 'ใบแรกคือหอคอย คุณเคยผ่านเรื่องดี ๆ มา', $this->ctx(self::THREE, 'card', 1));
        $this->assertNotEmpty($reasons);
        $this->assertStringContainsString('หอคอย', $reasons[0]);
    }

    public function test_upright_and_reversed_swapped_is_caught(): void
    {
        $this->assertNotEmpty($this->check('card:1', 'ไพ่คู่รักกลับหัว ความสัมพันธ์เก่ามีปัญหา', $this->ctx(self::THREE, 'card', 1)));
        $this->assertNotEmpty($this->check('card:2', 'หอคอยตั้งตรง ทุกอย่างพังลง', $this->ctx(self::THREE, 'card', 2)));
    }

    public function test_a_wrong_month_is_caught(): void
    {
        $ctx = $this->ctx(self::YEAR, 'month', 2);
        $this->assertSame([], $this->check('month:2', 'กุมภาพันธ์ เงินรั่ว ใช้จ่ายระวังนะคะ', $ctx));
        $this->assertNotEmpty($this->check('month:2', 'เดือนมีนาเงินรั่ว ระวังไว้', $ctx));
        // บทอื่นพูดเดือนที่ไม่มีในคำทำนาย
        $this->assertNotEmpty($this->check('summary', 'ปีนี้ดี โดยเฉพาะเดือนสิงหา', $this->ctx(self::YEAR)));
    }

    public function test_invented_numbers_directions_colours_and_birth_facts_are_caught(): void
    {
        $ctx = $this->ctx(self::THREE, 'card', 3);
        $this->assertNotEmpty($this->check('card:3', 'เลขมงคลของคุณคือ 9 นะ', $ctx));
        $this->assertNotEmpty($this->check('card:3', 'เลขมงคลของคุณคือ ๙ นะ', $ctx));
        $this->assertNotEmpty($this->check('card:3', 'หันไปทางทิศเหนือจะดี', $ctx));
        $this->assertNotEmpty($this->check('card:3', 'ใส่สีแดงจะเสริมดวง', $ctx));
        $this->assertNotEmpty($this->check('birth', 'คุณเกิดวันศุกร์ ราศีมีน', $this->ctx(self::THREE, 'birth')));
        $this->assertNotEmpty($this->check('birth', 'คุณราศีเมษ ธาตุไฟ', $this->ctx(self::THREE, 'birth')));
        // ราคาไม่เคยผ่าน
        $this->assertNotEmpty($this->check('summary', 'เปิดไพ่เพิ่มแค่ 99 บาท', $this->ctx(self::THREE)));
    }

    public function test_a_flipped_verdict_is_caught(): void
    {
        $ctx = $this->ctx(self::THREE);
        $this->assertNotEmpty($this->check('summary', 'คำตอบคือไม่ใช่นะคะ ความรักนี้ยังไม่ไปไหน', $ctx));
        $this->assertNotEmpty($this->check('summary', 'ไม่ใช่ค่ะ ยังไม่ถึงเวลา', $ctx));

        $decision = "## 🎯 ฟันธง\nผล: เลือกทางเลือกที่ 2\n## 🔀 ทางเลือกที่ 1 · งานเดิม\nมั่นคง\n## 🔀 ทางเลือกที่ 2 · งานใหม่\nเติบโต\n## ✅ ไพ่เลือกทาง\nเลือก: ทางเลือกที่ 2";
        $dctx = $this->ctx($decision);
        $this->assertSame([], $this->check('summary', 'ไพ่เลือกทางเลือกที่ 2 งานใหม่ให้คุณเติบโต', $dctx));
        $this->assertNotEmpty($this->check('summary', 'ไพ่เลือกทางเลือกที่ 1 งานเดิมมั่นคงกว่า', $dctx));
    }

    public function test_a_kunsai_result_that_disagrees_with_the_diagnosis_is_caught(): void
    {
        $cards = [1 => ['name' => 'ดวงจันทร์', 'reversed' => false]];
        $ctx = $this->ctx(self::KUNSAI, null, null, 'kunsai', $cards);
        $this->assertSame([], $this->check('summary', 'แม่หมอส่องแล้ว ไม่พบของ ใจคุณกังวลไปเอง', $ctx));
        $this->assertNotEmpty($this->check('summary', 'คุณโดนของจริง ต้องรีบแก้', $ctx));

        $unclear = str_replace('พบของ: ไม่ใช่', 'พบของ: ไม่ชัด', self::KUNSAI);
        $this->assertNotEmpty($this->check('summary', 'ไม่พบของแน่นอน', $this->ctx($unclear, null, null, 'kunsai', $cards)));
    }

    public function test_busaba_never_calls_the_customer_luuk(): void
    {
        $ctx = $this->ctx(self::THREE, null, null, 'busaba');
        $this->assertNotEmpty($this->check('summary', 'ลูกจ๋า ความรักไปต่อได้', $ctx));
        $this->assertSame([], $this->check('summary', 'คุณคะ ความรักไปต่อได้ ลูกของคุณก็สบายดี', $ctx));
    }
}
