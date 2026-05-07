<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;

/**
 * 🙏 FortuneSatisfactionDetector (2026-05-07)
 *
 * ตรวจจับว่าลูกค้าพอใจ / อยากจบการสนทนาแล้วหรือยัง
 *
 * จุดประสงค์: ปิด session อย่างอบอุ่น เพื่อให้ลูกค้าประทับใจ
 *   - ห้ามเสนอบริการเพิ่มในจังหวะนี้ (ดูเหมือน sales)
 *   - ขอบคุณ + อวยพร + บอกว่ากลับมาได้เสมอ
 *
 * Detection: heuristic only (เร็ว ฟรี deterministic)
 *   - Thanks: ขอบคุณ / ขอบใจ / thx / thank
 *   - Enough: พอแล้ว / พอแค่นี้ / เพียงพอ / โอเคแล้ว
 *   - Understood: เข้าใจแล้ว / รู้แล้ว / ชัดเจนแล้ว
 *   - Praise: ดีมาก / ตรงมาก / แม่นมาก / เก่งจัง
 *   - Goodbye: ลาก่อน / บาย / จบ
 *   - Lao: ຂອບໃຈ / ພໍແລ້ວ / ເຂົ້າໃຈແລ້ວ / ດີຫຼາຍ
 */
class FortuneSatisfactionDetector
{
    /**
     * Patterns สำหรับ heuristic detection
     */
    protected const PATTERNS = [
        'thanks' => [
            'ขอบคุณ', 'ขอบใจ', 'ขอบพระคุณ', 'ขอบคุณค่ะ', 'ขอบคุณครับ',
            'thank', 'thx',
            'ຂອບໃຈ',
        ],
        'enough' => [
            'พอแล้ว', 'พอแค่นี้', 'แค่นี้พอ', 'เพียงพอแล้ว', 'พอ',
            'โอเคแล้ว', 'โอเค', 'ok แล้ว', 'oke แล้ว',
            'ພໍແລ້ວ', 'ພໍ',
        ],
        'understood' => [
            'เข้าใจแล้ว', 'รู้แล้ว', 'ชัดเจนแล้ว', 'เคลียร์แล้ว',
            'เก็ทแล้ว', 'get แล้ว',
            'ເຂົ້າໃຈແລ້ວ',
        ],
        'praise' => [
            'ดีมาก', 'ตรงมาก', 'แม่นมาก', 'เก่งจัง', 'แม่นจริง',
            'ตรงเป๊ะ', 'แม่นเว่อ', 'ใช่เลย',
            'ດີຫຼາຍ', 'ແມ່ນຫຼາຍ',
        ],
        'goodbye' => [
            'ลาก่อน', 'บาย', 'bye', 'จบ', 'ปิด',
            'ขอตัวก่อน', 'ขอลาแล้ว', 'จบการสนทนา',
            'ລາກ່ອນ',
        ],
    ];

    public function __construct(
        protected ?FortuneTellingSetting $settings = null
    ) {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตรวจ message
     *
     * 🩹 (2026-05-07 review L2) — strengthened detection:
     *   - skip ถ้ามี '?' หรือ '?' (ลูกค้ายังถามอยู่)
     *   - skip ถ้ามี follow-up verb ("ทำนาย/ต่อ/อีก/ขอ")
     *   - 'พอ' standalone keyword: ต้องเป็นคำเดี่ยว/ต้นประโยค ไม่ใช่ substring ของ "พอใจ/เพียงพอ"
     *
     * @return array{
     *     is_satisfied: bool,
     *     wants_to_end: bool,
     *     confidence: int,
     *     signals: array<string>,
     * }
     */
    public function detect(string $message): array
    {
        $defaults = [
            'is_satisfied' => false,
            'wants_to_end' => false,
            'confidence' => 0,
            'signals' => [],
        ];

        if (! ($this->settings->satisfaction_detection_enabled ?? true)) {
            return $defaults;
        }

        $trimmed = trim($message);
        $msgLower = mb_strtolower($trimmed);

        // ข้อความสั้นมากเกินไปก็อาจจะ trigger ผิด — ต้องมีอย่างน้อย 2 ตัว
        if (mb_strlen($msgLower) < 2) {
            return $defaults;
        }

        // 🛡️ Skip ถ้ามี question mark — ลูกค้ายังถามอยู่ ไม่ใช่ปิดบทสนทนา
        if (str_contains($trimmed, '?') || str_contains($trimmed, '?')) {
            return $defaults;
        }

        // 🛡️ Skip ถ้ามี follow-up verb — บ่งว่ายังคุยต่อ
        $followUpPatterns = ['ทำนายต่อ', 'ทำนายอีก', 'ดูต่อ', 'ดูอีก', 'ถามต่อ', 'ถามอีก',
            'ขอเพิ่ม', 'ขอต่อ', 'อีกครั้ง', 'อีกที', 'ต่อหน่อย', 'ต่อเลย',
            'ດູຕໍ່', 'ຖາມຕໍ່', 'ອີກ'];
        foreach ($followUpPatterns as $fp) {
            if (mb_stripos($msgLower, $fp) !== false) {
                return $defaults;
            }
        }

        $signals = [];
        $score = 0;

        foreach (self::PATTERNS as $category => $words) {
            foreach ($words as $word) {
                $wordLower = mb_strtolower($word);

                // 🛡️ "พอ" standalone — ต้องเป็นคำเดี่ยวหรือต้นประโยค
                //   เพราะ "พอใจ", "เพียงพอ", "ไม่พอใจ" ไม่ใช่ closure signal
                if ($wordLower === 'พอ' || $wordLower === 'ພໍ') {
                    // ต้องเป็นคำเดี่ยวเท่านั้น (เช่น "พอแล้ว", "พอ", "พอครับ")
                    if (! preg_match('/(^|\s)(พอ|ພໍ)($|\s)/u', $msgLower)) {
                        continue;
                    }
                }

                if (mb_stripos($msgLower, $wordLower) !== false) {
                    $signals[] = $category.':'.$word;
                    $score += match ($category) {
                        'goodbye' => 50,    // strongest
                        'thanks', 'enough' => 35,
                        'understood', 'praise' => 25,
                        default => 10,
                    };
                    break; // 1 word per category พอ
                }
            }
        }

        // ข้อความสั้น (≤ 15 ตัว) + มี signal → confidence สูงขึ้น
        // (ลด threshold จาก 20 เพื่อ stricter — "ขอบคุณค่ะ" 9 chars OK, "ดีมาก ทำนายต่อ" 14 chars no)
        if (! empty($signals) && mb_strlen($trimmed) <= 15) {
            $score += 20;
        }

        $confidence = min(100, $score);
        $isSatisfied = $confidence >= 35;

        // 🩹 wants_to_end ต้องชัดเจนกว่าเดิม:
        //   - มี goodbye signal (ลาก่อน/บาย/จบ) — ชัดสุด
        //   - หรือ confidence >= 55 + ข้อความสั้น (≤ 20 ตัว)
        $hasGoodbye = ! empty(array_filter($signals, fn ($s) => str_starts_with($s, 'goodbye:')));
        $wantsToEnd = $hasGoodbye || ($confidence >= 55 && mb_strlen($trimmed) <= 20);

        return [
            'is_satisfied' => $isSatisfied,
            'wants_to_end' => $wantsToEnd,
            'confidence' => $confidence,
            'signals' => $signals,
        ];
    }

    /**
     * ดึงข้อความปิด session (default หรือ admin custom)
     *
     * 🩹 (2026-05-07 review U4) — locale-aware (Thai/Lao)
     */
    public function getCloseMessage(?string $customerName = null): string
    {
        $custom = trim($this->settings->satisfaction_close_message ?? '');
        if (! empty($custom)) {
            return str_replace('{name}', $customerName ?? 'เจ้าชะตา', $custom);
        }

        // ตรวจ locale ปัจจุบัน
        $locale = 'th';
        try {
            if (class_exists(\App\Services\FortuneLocaleService::class)) {
                $locale = \App\Services\FortuneLocaleService::current() ?: 'th';
            }
        } catch (\Throwable $e) {
            // fallback Thai
        }

        if ($locale === 'lo') {
            $name = $customerName ?: 'ເຈົ້າຊາຕາ';

            return "🌙 ຂອບໃຈ{$name}ທີ່ໄວ້ວາງໃຈແມ່ໝໍຈັນທຣານະ\n\n"
                ."ຂໍໃຫ້ດວງດາວຄຸ້ມຄອງ ພົບເຈິແຕ່ສິ່ງດີໆ ✨\n"
                .'ຫາກຕ້ອງການປຶກສາເພີ່ມເຕີມເມື່ອໃດ ແມ່ໝໍພ້ອມສະເໝີ 🙏';
        }

        $name = $customerName ?: 'เจ้าชะตา';

        return "🌙 ขอบคุณ{$name}ที่ไว้วางใจแม่หมอจันทรานะคะ\n\n"
            ."ขอให้ดวงดาวคุ้มครอง พบเจอแต่สิ่งดี ๆ ✨\n"
            .'หากต้องการปรึกษาเพิ่มเติมเมื่อใด แม่หมอพร้อมเสมอค่ะ 🙏';
    }
}
