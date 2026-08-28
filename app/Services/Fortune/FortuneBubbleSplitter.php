<?php

namespace App\Services\Fortune;

/**
 * 💬 FortuneBubbleSplitter — ผ่าคำทำนายยาว ๆ ให้เป็น "หลายกล่องแชท" เหมือนคนพิมพ์ตอบ
 *
 * เจ้าของสั่ง (2026-08-28): "อยากให้แยกกล่องบับเบิ้ลตอบ อย่าตอบยาว ๆ ทำเป็นหลายกล่อง
 * เพื่อให้เหมือนคนตอบ ค่อย ๆ ส่งห่างกันอย่างน้อย 5-10 วินาที แต่ละกล่องบับเบิ้ล"
 *
 * ⚠️ คลาสนี้ทำหน้าที่เดียว — **ตัดข้อความ** ส่วนการหน่วงเวลาอยู่ที่ SendFortuneBubbleJob
 *    (แยกกันเพื่อให้เทสต์การตัดได้โดยไม่ต้องมี queue/แพลตฟอร์ม)
 *
 * 🇹🇭 กับดักภาษาไทยที่ต้องรู้:
 *   ไทยเขียนติดกันไม่มีช่องว่างระหว่างคำ ⇒ ตัดด้วยความยาวดิบ ๆ = คำขาดกลาง
 *   สระ/วรรณยุกต์เป็น Mark (\p{M}) ไม่ใช่ Letter ⇒ ถ้าตัดผิดตำแหน่งจะเห็น "พ ธ" แทน "พุธ"
 *   ที่นี่จึงตัดที่ **ขอบเขตที่ผู้เขียนตั้งใจไว้แล้ว** เท่านั้น (บรรทัดว่าง → ขึ้นบรรทัดใหม่
 *   → คำลงท้ายประโยค) และ **ไม่มีการตัดกลางอักขระเลย** — ยาวเกินก็ยอมให้กล่องยาว
 *   ดีกว่าได้ข้อความพิการ (rule_never_byte_trim_thai_charlist · rule_thai_regex_needs_mark_class)
 */
class FortuneBubbleSplitter
{
    /** ขนาดที่ "กำลังดี" ต่อ 1 กล่อง — สะสมย่อหน้าจนถึงเลขนี้แล้วขึ้นกล่องใหม่ */
    public const TARGET_CHARS = 420;

    /** กล่องสั้นกว่านี้ = เศษ ให้ยุบรวมกับกล่องก่อนหน้า (กันบับเบิ้ล "ค่ะ" ลอยเดี่ยว) */
    public const MIN_CHARS = 60;

    /** จำนวนกล่องมากสุด — เกินนี้รอนานเกินไป (4 กล่อง × 10 วิ = 30 วิกว่าจะครบ) */
    public const MAX_BUBBLES = 4;

    /**
     * คำลงท้ายประโยคไทยที่ใช้เป็นรอยตัดสำรอง (ตอนข้อความไม่มีบรรทัดว่างเลย)
     *
     * ⚠️ ต้องตัด **หลัง** คำเหล่านี้เสมอ ห้ามตัดหน้า — ไม่งั้นคำลงท้ายไปโผล่หัวกล่องถัดไป
     */
    private const SENTENCE_ENDERS = ['ค่ะ', 'คะ', 'นะคะ', 'นะ', 'ครับ', 'จ้ะ', 'เลยค่ะ', 'ด้วยค่ะ'];

    /**
     * ผ่าข้อความเป็นกล่อง ๆ
     *
     * @param  string  $text  คำทำนายเต็ม
     * @param  int|null  $maxBubbles  จำนวนกล่องมากสุด (null = ใช้ค่า default)
     * @return array<int, string> อย่างน้อย 1 กล่องเสมอ · ข้อความว่าง = คืน []
     */
    public function split(string $text, ?int $maxBubbles = null): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $max = max(1, $maxBubbles ?? self::MAX_BUBBLES);

        if ($max === 1) {
            return [$text];
        }

        // สั้นอยู่แล้ว → ไม่ต้องผ่า (กล่องเดียวจบ ไม่ต้องรอ)
        if (mb_strlen($text) <= self::TARGET_CHARS) {
            return [$text];
        }

        $blocks = $this->toBlocks($text);
        $bubbles = $this->packBlocks($blocks, $max);

        return $this->mergeStrays($bubbles);
    }

    /**
     * แตกข้อความเป็น "ก้อนที่ห้ามแยกกลาง"
     *
     * ลำดับความชอบ: บรรทัดว่าง (ย่อหน้า) → ขึ้นบรรทัดใหม่ → คำลงท้ายประโยค
     * ก้อนไหนยังยาวเกิน 2 เท่าของเป้า ค่อยลงไปหารอยตัดชั้นถัดไป
     *
     * @return array<int, string>
     */
    private function toBlocks(string $text): array
    {
        // ย่อหน้า = คั่นด้วยบรรทัดว่าง (รูปแบบที่ AI เขียนออกมาเป็นปกติ)
        $paragraphs = preg_split('/\R{2,}/u', $text) ?: [$text];

        $blocks = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph) <= self::TARGET_CHARS * 2) {
                $blocks[] = $paragraph;

                continue;
            }

            // ย่อหน้ายักษ์ → ลองหั่นทีละบรรทัด
            foreach ($this->splitByLines($paragraph) as $line) {
                if (mb_strlen($line) <= self::TARGET_CHARS * 2) {
                    $blocks[] = $line;

                    continue;
                }

                // บรรทัดเดียวยาวมาก (ไทยไม่มีเว้นวรรค) → หั่นที่คำลงท้ายประโยค
                foreach ($this->splitBySentence($line) as $sentence) {
                    $blocks[] = $sentence;
                }
            }
        }

        return $blocks === [] ? [$text] : $blocks;
    }

    /**
     * หั่นตามบรรทัด แต่เก็บบรรทัดที่เป็น "รายการ" ให้ติดกับหัวข้อของมัน
     *
     * @return array<int, string>
     */
    private function splitByLines(string $paragraph): array
    {
        $lines = preg_split('/\R/u', $paragraph) ?: [$paragraph];
        $out = [];
        $buffer = '';

        foreach ($lines as $line) {
            $candidate = $buffer === '' ? $line : $buffer."\n".$line;

            if (mb_strlen($candidate) > self::TARGET_CHARS && $buffer !== '') {
                $out[] = trim($buffer);
                $buffer = $line;

                continue;
            }

            $buffer = $candidate;
        }

        if (trim($buffer) !== '') {
            $out[] = trim($buffer);
        }

        return $out;
    }

    /**
     * หั่นประโยคยาวที่คำลงท้ายไทย — ตัด **หลัง** คำลงท้ายเสมอ
     *
     * หาไม่เจอเลย → คืนทั้งก้อน (ยอมให้กล่องยาว ดีกว่าตัดกลางคำ)
     *
     * @return array<int, string>
     */
    private function splitBySentence(string $line): array
    {
        $enders = implode('|', array_map(
            static fn (string $w) => preg_quote($w, '/'),
            self::SENTENCE_ENDERS
        ));

        // (?<=...) = ตัดหลังคำลงท้าย · อนุญาตให้มีอีโมจิ/วรรคตอนตามหลังได้
        $parts = preg_split('/(?<='.$enders.')(?=\s|$)/u', $line) ?: [$line];

        $out = [];
        $buffer = '';

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $candidate = $buffer === '' ? $part : $buffer.' '.$part;

            if (mb_strlen($candidate) > self::TARGET_CHARS && $buffer !== '') {
                $out[] = $buffer;
                $buffer = $part;

                continue;
            }

            $buffer = $candidate;
        }

        if ($buffer !== '') {
            $out[] = $buffer;
        }

        return $out === [] ? [$line] : $out;
    }

    /**
     * ยัดก้อนลงกล่อง — สะสมจนเกินเป้าแล้วขึ้นกล่องใหม่ · ถึงเพดานแล้วเทที่เหลือลงกล่องสุดท้าย
     *
     * @param  array<int, string>  $blocks
     * @return array<int, string>
     */
    private function packBlocks(array $blocks, int $max): array
    {
        $bubbles = [];
        $buffer = '';

        foreach ($blocks as $block) {
            // 🚧 ถึงกล่องสุดท้ายแล้ว — ที่เหลือทั้งหมดต้องอยู่ในกล่องนี้ ห้ามทิ้ง
            if (count($bubbles) === $max - 1) {
                $buffer = $buffer === '' ? $block : $buffer."\n\n".$block;

                continue;
            }

            $candidate = $buffer === '' ? $block : $buffer."\n\n".$block;

            if (mb_strlen($candidate) > self::TARGET_CHARS && $buffer !== '') {
                $bubbles[] = $buffer;
                $buffer = $block;

                continue;
            }

            $buffer = $candidate;
        }

        if (trim($buffer) !== '') {
            $bubbles[] = trim($buffer);
        }

        return $bubbles;
    }

    /**
     * ยุบกล่องเศษ (สั้นกว่า MIN_CHARS) เข้ากับกล่องก่อนหน้า
     *
     * กล่อง "ค่ะ ✨" ลอยเดี่ยวดูเหมือนบอทค้าง ไม่เหมือนคนพิมพ์
     *
     * @param  array<int, string>  $bubbles
     * @return array<int, string>
     */
    private function mergeStrays(array $bubbles): array
    {
        $out = [];

        foreach ($bubbles as $bubble) {
            $bubble = trim($bubble);

            if ($bubble === '') {
                continue;
            }

            if ($out !== [] && mb_strlen($bubble) < self::MIN_CHARS) {
                $out[count($out) - 1] .= "\n\n".$bubble;

                continue;
            }

            $out[] = $bubble;
        }

        return $out;
    }
}
