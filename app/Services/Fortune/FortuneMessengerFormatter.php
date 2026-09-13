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
 *   4. ⭐ (2026-09-13 · แบบ ข) บล็อกคะแนน "ความรัก: 4/5" ท้ายพื้นดวง Celtic → "★★★★☆  ความรัก" (renderScores)
 *
 * ⚠️ ทำไม Messenger ทำตารางไม่ได้: ฟอนต์กว้างไม่เท่ากัน + สระบน-ล่าง/วรรณยุกต์ไทยไม่กินที่ ⇒ ขอบขวาไม่มีวันตรง
 *
 * 🔒 กติกาความปลอดภัย:
 *   - ใช้ **ตอนส่งเข้า FB เท่านั้น หลังด่านตรวจผ่านแล้ว** — must-gate `str_contains` อีโมจิหัวข้อ
 *     ⇒ ต้องเก็บอีโมจิเดิมไว้ในหัวข้อเสมอ (บันทึกแชทหลังบ้านเก็บข้อความต้นฉบับก่อนถึงจุดนี้)
 *   - แตะเฉพาะบรรทัดที่ขึ้นต้นด้วยอีโมจิในรายการ + สั้น + ยืนเป็นย่อหน้าเดี่ยว + ไม่ใช่ "หัวข้อ: ค่า"
 *     + มีเนื้อตามหลัง และต้องมี ≥ 2 หัวข้อ
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
     * ⭐ (2026-09-13) ชื่อด้านในบล็อกคะแนนดวง — ต้องตรงกับ CelticCrossService::scoreSummarySpec() ทุกคำ
     */
    public const SCORE_AREAS = ['ความรัก', 'การงาน', 'การเงิน', 'โชคลาภ', 'สุขภาพ'];

    /** หัวบล็อกคะแนนหลังแปลงเป็นดาว */
    public const SCORE_TITLE = '【 ⭐ สรุปดวงรอบนี้ 】';

    /** บรรทัดคะแนนติดกันอย่างน้อยเท่านี้ถึงจะนับเป็นบล็อกคะแนน (กันประโยคเดี่ยว "การเงิน: 3/5" กลางเนื้อ) */
    private const MIN_SCORE_LINES = 3;

    /**
     * แต่งข้อความทั้งก้อน — หัวข้อเซคชั่น + บล็อกคะแนนดาว · ไม่มีทั้งสองอย่าง = คืนต้นฉบับทุกตัวอักษร
     */
    public function format(string $text): string
    {
        if (trim($text) === '' || str_contains($text, '【')) {
            return $text;
        }

        return $this->renderScores($this->formatSections($text));
    }

    /**
     * 📏 (2026-09-13) ย่อ "บรรทัดที่เป็นเส้นล้วน" ที่ยาวเกิน 10 ตัว ให้เหลือ 10 ตัว (ตัวอักษรเดิม)
     *
     * ข้อความแม่หมอหลายสิบจุดใช้เส้น ━ 13-17 ตัว / ═ 23 ตัว / ─ 21-22 ตัว (เมนูราคา บิล ข้อความขาย ฯลฯ)
     * บนจอมือถือแคบ ตัวอักษรชุดเส้นกว้างราว 1 ช่องเต็ม ⇒ เกิน ~14 ตัวตกบรรทัด เส้นขาดเป็นสองท่อน
     * ย่อที่ทางออก FB จุดเดียวแทนไล่แก้ทุก literal (บางตัวอยู่ในพรอมต์ AI ที่ลูกค้าไม่เห็น — ห้ามแตะ)
     *
     * แตะเฉพาะบรรทัดที่มีแต่ตัวเส้นซ้ำตัวเดียวกัน — บรรทัดที่มีข้อความปน ("── หัวข้อ ────") ไม่แตะ
     */
    public static function shortenDividers(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $out = preg_replace_callback(
            '/^[ \t]*([━─═┈])\1{10,}[ \t]*$/mu',
            fn (array $m) => str_repeat($m[1], 10),
            $text
        );

        return is_string($out) ? $out : $text;
    }

    /**
     * ⭐ แปลงบล็อก "⭐ สรุปคะแนนดวง / ความรัก: 4/5 / …" เป็น "【 ⭐ สรุปดวงรอบนี้ 】 / ★★★★☆  ความรัก / …"
     *
     * ดาวอยู่ต้นบรรทัดเสมอ — Messenger ตัวอักษรกว้างไม่เท่ากัน ต้นบรรทัดตรงกันได้ ท้ายบรรทัดไม่มีวันตรง
     * รูปแบบไม่ตรงเป๊ะ (คะแนนนอก 1-5 · ชื่อด้านแปลก · ไม่ถึง 3 บรรทัด) = ไม่แตะ ปล่อยตัวเลขไว้ตามเดิม
     */
    public function renderScores(string $text): string
    {
        $lines = preg_split('/\R/u', $text);
        if ($lines === false) {
            return $text;
        }

        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            if ($this->scoreOf($lines[$i]) === null) {
                continue;
            }

            $j = $i;
            while ($j < $count && $this->scoreOf($lines[$j]) !== null) {
                $j++;
            }
            if ($j - $i < self::MIN_SCORE_LINES) {
                $i = $j;

                continue;
            }

            // หัวบล็อก "⭐ …" ที่อยู่เหนือบรรทัดคะแนน (ข้ามบรรทัดว่างได้) → แทนด้วยหัวใหม่ ไม่ให้ค้างซ้ำสองหัว
            $h = $i - 1;
            while ($h >= 0 && trim($lines[$h]) === '') {
                $h--;
            }
            $start = ($h >= 0 && str_starts_with(trim($lines[$h]), '⭐')) ? $h : $i;

            $block = [self::SCORE_TITLE];
            for ($k = $i; $k < $j; $k++) {
                [$area, $score] = $this->scoreOf($lines[$k]);
                $block[] = str_repeat('★', $score).str_repeat('☆', 5 - $score).'  '.$area;
            }

            $before = array_slice($lines, 0, $start);
            $this->trimTrailingBlank($before);
            $after = array_slice($lines, $j);
            while ($after !== [] && trim($after[0]) === '') {
                array_shift($after);
            }

            $out = $before;
            if ($before !== []) {
                array_push($out, '', self::DIVIDER, '');
            }
            array_push($out, ...$block);
            if ($after !== []) {
                array_push($out, '', ...$after);
            }

            return rtrim(implode("\n", $out)); // บล็อกเดียวต่อข้อความ
        }

        return $text;
    }

    /**
     * บรรทัดนี้เป็นบรรทัดคะแนนไหม — "ความรัก: 4/5" → ['ความรัก', 4] · ไม่ใช่ = null
     *
     * @return array{0: string, 1: int}|null
     */
    private function scoreOf(string $line): ?array
    {
        $areas = implode('|', array_map(fn ($a) => preg_quote($a, '/'), self::SCORE_AREAS));

        // โคลอนเป็นทางเลือก ("ความรัก 4/5" ก็รับ) · ต้องทั้งบรรทัด — ประโยคที่มีคำอื่นปนไม่นับ
        if (preg_match('/^\s*('.$areas.')\s*[:：]?\s*([1-5])\s*\/\s*5\s*$/u', $line, $m) === 1) {
            return [$m[1], (int) $m[2]];
        }

        return null;
    }

    /**
     * แต่งหัวข้อเซคชั่น — ไม่มีโครงเซคชั่น (หัวข้อไม่ถึง 2) คืนต้นฉบับทุกตัวอักษร
     */
    private function formatSections(string $text): string
    {
        $lines = preg_split('/\R/u', $text);
        if ($lines === false) {
            return $text;
        }

        $headerIdx = [];
        foreach ($lines as $i => $line) {
            if ($this->isHeader($line) && $this->standsAlone($lines, $i) && $this->hasBodyAfter($lines, $i)) {
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

        // "🍀 เลขนำโชค: 3, 7" = รายการ "หัวข้อ: ค่า" ไม่ใช่หัวเซคชั่น (โคลอนท้ายเปล่า ๆ ยังนับ เช่น "💞 ความรัก:")
        if (preg_match('/[:：]\s*\S/u', $t) === 1) {
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
     * หัวข้อต้องยืนเป็นย่อหน้าเดี่ยว — บรรทัดก่อนหน้าว่าง (หรือเป็นบรรทัดแรก) และบรรทัดถัดไปว่าง
     *
     * ตรงกับคำตอบข้อแรก Celtic 99 บน prod ทุกใบที่ตรวจ (หัวข้อ + บรรทัดว่าง + เนื้อ)
     * กันรายการสั้น ๆ ที่เรียงติดกัน ("🍀 เลขนำโชค …\n🌿 สีมงคล …") ถูกยกเป็นหัวข้อ + มีเส้นคั่นแทรกกลางรายการ
     *
     * @param  array<int, string>  $lines
     */
    private function standsAlone(array $lines, int $i): bool
    {
        $prevBlank = $i === 0 || trim($lines[$i - 1]) === '';
        $nextBlank = isset($lines[$i + 1]) && trim($lines[$i + 1]) === '';

        return $prevBlank && $nextBlank;
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
