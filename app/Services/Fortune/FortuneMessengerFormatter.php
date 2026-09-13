<?php

namespace App\Services\Fortune;

/**
 * ✦ FortuneMessengerFormatter — แต่งหัวข้อคำทำนายให้อ่านง่ายบน Facebook Messenger
 *
 * เจ้าของสั่ง (2026-09-13): "ทำไมไม่ทำให้การตอบแม่หมอเป็นสัดส่วน สวยงาม ... แชทเฟซบุ๊คมีข้อจำกัด"
 * → เลือก "แบบ ก · วงเล็บหัวข้อ" จากตัวอย่าง https://claude.ai/code/artifact/c15b4d48-06dd-43cd-b0ea-0ac30c9716e0
 *
 * สิ่งที่ทำ (ข้อความล้วน ไม่มีตาราง):
 *   1. หัวข้อที่ขึ้นต้นด้วยอีโมจิประจำเซคชั่น → "【 💞 ความรัก 】"
 *   2. ติดหัวข้อไว้กับเนื้อหา (ตัดบรรทัดว่างระหว่างหัวข้อกับเนื้อ)
 *      เดิมหัวข้อเป็นย่อหน้าเดี่ยว ⇒ FortuneBubbleSplitter เคยปล่อยหัวข้อค้างท้ายกล่อง แล้วเนื้อไปอยู่กล่องถัดไป
 *   3. คั่นเซคชั่นด้วยเส้นประสั้น "┈┈┈┈┈┈┈┈┈┈" (10 ตัว — ยาวกว่านี้จอมือถือแคบตกบรรทัด)
 *
 * ⚠️ ทำไม Messenger ทำตารางไม่ได้: ฟอนต์กว้างไม่เท่ากัน + สระบน-ล่าง/วรรณยุกต์ไทยไม่กินที่ ⇒ ขอบขวาไม่มีวันตรง
 *
 * 🔒 กติกาความปลอดภัย:
 *   - ใช้ **ตอนส่งเข้า FB เท่านั้น หลังด่านตรวจผ่านแล้ว** — must-gate `str_contains` อีโมจิหัวข้อ
 *     ⇒ ต้องเก็บอีโมจิเดิมไว้ในหัวข้อเสมอ (บันทึกแชทหลังบ้านเก็บข้อความต้นฉบับก่อนถึงจุดนี้)
 *   - แตะเฉพาะบรรทัดที่ขึ้นต้นด้วยอีโมจิในรายการ + สั้น + มีเนื้อตามหลัง และต้องมี ≥ 2 หัวข้อ
 *     คำตอบข้อ 2 เป็นต้นไป (ไม่มีหัวข้อ) จึงผ่านไปแบบเดิมทุกตัวอักษร
 *   - แต่งซ้ำไม่เปลี่ยนผล (idempotent) — ข้อความที่มี "【" แล้วไม่แตะอีก
 *   - คลาสนี้ไม่โยน exception ออกไปเอง แต่ผู้เรียกยังต้อง try/catch แล้วส่งต้นฉบับ (ห้ามทำคำทำนายหาย)
 */
class FortuneMessengerFormatter
{
    /** เส้นคั่นเซคชั่น — สั้นพอไม่ตกบรรทัดบนจอแคบ (บล็อกเดียวกับ ━ ที่เมนูราคาใช้อยู่ ขึ้นทุกเครื่อง) */
    public const DIVIDER = '┈┈┈┈┈┈┈┈┈┈';

    /**
     * อีโมจิหัวข้อที่พรอมต์ Celtic 99 / ดูดวง 39 สั่งให้โมเดลใช้ (ดู CelticCrossService groups + lagnaSectionDirective)
     *
     * 🎯 เรื่องเด่น · 🌟 พื้นฐาน · 🔮 ภาพรวม · 🕛 ลัคนา/จันทร์ลัคน์ · 💞 ความรัก · 💼 การงาน
     * 💰 การเงิน · 🍀 โชคลาภ · 🌿 สุขภาพ · 🕉 องค์เทพ (มักตามด้วย VS16)
     */
    public const HEADER_EMOJI = ['🎯', '🌟', '🔮', '🕛', '💞', '💼', '💰', '🍀', '🌿', '🕉'];

    /** หัวข้อยาวกว่านี้ = ประโยคเนื้อหาที่บังเอิญขึ้นต้นด้วยอีโมจิ ไม่ใช่หัวข้อ */
    private const MAX_HEADER_CHARS = 60;

    /** ต้องมีหัวข้ออย่างน้อยเท่านี้ถึงจะแต่ง — บรรทัดอีโมจิเดี่ยว ๆ ในคำตอบสั้นไม่ใช่โครงเซคชั่น */
    private const MIN_HEADERS = 2;

    /**
     * แต่งข้อความทั้งก้อน — ไม่มีโครงเซคชั่นคืนต้นฉบับทุกตัวอักษร
     */
    public function format(string $text): string
    {
        if (trim($text) === '' || str_contains($text, '【')) {
            return $text;
        }

        $lines = preg_split('/\R/u', $text);
        if ($lines === false) {
            return $text;
        }

        $headerIdx = [];
        foreach ($lines as $i => $line) {
            if ($this->isHeader($line) && $this->hasBodyAfter($lines, $i)) {
                $headerIdx[$i] = true;
            }
        }

        if (count($headerIdx) < self::MIN_HEADERS) {
            return $text;
        }

        $out = [];
        $seenContent = false;
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            if (! isset($headerIdx[$i])) {
                $out[] = $lines[$i];
                if (trim($lines[$i]) !== '') {
                    $seenContent = true;
                }

                continue;
            }

            // คั่นจากเนื้อก่อนหน้า (ไม่ใส่เส้นหน้าหัวข้อแรกสุดที่ไม่มีอะไรนำ)
            if ($seenContent) {
                $this->trimTrailingBlank($out);
                $out[] = '';
                $out[] = self::DIVIDER;
                $out[] = '';
            }

            $out[] = '【 '.$this->headerText($lines[$i]).' 】';
            $seenContent = true;

            // ติดหัวข้อกับเนื้อ — ข้ามบรรทัดว่างที่ตามหลังหัวข้อ
            while ($i + 1 < $count && trim($lines[$i + 1]) === '') {
                $i++;
            }
        }

        return rtrim(implode("\n", $out));
    }

    /**
     * ตัดเส้นคั่นที่ไปค้างหัว/ท้ายกล่อง หลังผ่ากล่องแล้ว
     *
     * ตัวผ่ากล่องตัดที่ย่อหน้า — เส้นคั่นเป็นย่อหน้าเล็ก ๆ ของมันเอง จึงอาจไปอยู่ขอบกล่องได้
     * ขอบกล่องแชทเป็นตัวคั่นอยู่แล้ว ⇒ เส้นตรงนั้นซ้ำซ้อน ดูเหมือนบอทพิมพ์ผิด
     *
     * @param  array<int, string>  $bubbles
     * @return array<int, string>
     */
    public function trimBubbleEdges(array $bubbles): array
    {
        $out = [];
        foreach ($bubbles as $bubble) {
            $lines = preg_split('/\R/u', (string) $bubble) ?: [(string) $bubble];

            while ($lines !== [] && in_array(trim($lines[0]), ['', self::DIVIDER], true)) {
                array_shift($lines);
            }
            while ($lines !== [] && in_array(trim($lines[count($lines) - 1]), ['', self::DIVIDER], true)) {
                array_pop($lines);
            }

            $clean = implode("\n", $lines);
            if (trim($clean) !== '') {
                $out[] = $clean;
            }
        }

        return $out;
    }

    /**
     * บรรทัดนี้เป็นหัวข้อเซคชั่นไหม — ขึ้นต้นด้วยอีโมจิในรายการ + สั้น
     */
    private function isHeader(string $line): bool
    {
        $t = trim($line);
        if ($t === '' || mb_strlen($t) > self::MAX_HEADER_CHARS) {
            return false;
        }

        foreach (self::HEADER_EMOJI as $emoji) {
            if (str_starts_with($t, $emoji)) {
                return true;
            }
        }

        return false;
    }

    /**
     * หัวข้อต้องมีเนื้อตามหลัง — บรรทัดถัดไปที่ไม่ว่างต้องมีอยู่และไม่ใช่หัวข้ออีกตัว
     *
     * @param  array<int, string>  $lines
     */
    private function hasBodyAfter(array $lines, int $i): bool
    {
        $count = count($lines);
        for ($j = $i + 1; $j < $count; $j++) {
            if (trim($lines[$j]) !== '') {
                return ! $this->isHeader($lines[$j]);
            }
        }

        return false;
    }

    /**
     * ข้อความหัวข้อ — ตัดช่องว่าง/โคลอนท้าย ("💞 ความรัก:" → "💞 ความรัก")
     *
     * ⚠️ ห้ามใช้ rtrim($s, ":：") — rtrim ตัดทีละไบต์ ไบต์ท้ายของ "：" (0x9A) ตรงกับไบต์ท้ายของ "บ"
     *    ⇒ หัวข้อที่ลงท้ายด้วย บ จะโดนตัดครึ่งตัวอักษร (rule_thai_text_matching_traps)
     */
    private function headerText(string $line): string
    {
        return (string) preg_replace('/[\s:：]+$/u', '', trim($line));
    }

    /**
     * ตัดบรรทัดว่างท้ายผลลัพธ์ ก่อนต่อเส้นคั่น (กันบรรทัดว่างซ้อน)
     *
     * @param  array<int, string>  $out
     */
    private function trimTrailingBlank(array &$out): void
    {
        while ($out !== [] && trim($out[count($out) - 1]) === '') {
            array_pop($out);
        }
    }
}
