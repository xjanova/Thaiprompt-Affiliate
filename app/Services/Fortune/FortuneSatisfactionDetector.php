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

        $msgLower = mb_strtolower(trim($message));

        // ข้อความสั้นมากเกินไปก็อาจจะ trigger ผิด — ต้องมีอย่างน้อย 2 ตัว
        if (mb_strlen($msgLower) < 2) {
            return $defaults;
        }

        $signals = [];
        $score = 0;

        foreach (self::PATTERNS as $category => $words) {
            foreach ($words as $word) {
                if (mb_stripos($msgLower, mb_strtolower($word)) !== false) {
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

        // ข้อความสั้น (≤ 20 ตัว) + มี signal → confidence สูงขึ้น
        if (! empty($signals) && mb_strlen($message) <= 20) {
            $score += 20;
        }

        $confidence = min(100, $score);
        $isSatisfied = $confidence >= 35;
        $wantsToEnd = $confidence >= 50 || in_array(true, array_map(
            fn ($s) => str_starts_with($s, 'goodbye:'),
            $signals
        ), true);

        return [
            'is_satisfied' => $isSatisfied,
            'wants_to_end' => $wantsToEnd,
            'confidence' => $confidence,
            'signals' => $signals,
        ];
    }

    /**
     * ดึงข้อความปิด session (default หรือ admin custom)
     */
    public function getCloseMessage(?string $customerName = null): string
    {
        $custom = trim($this->settings->satisfaction_close_message ?? '');
        if (! empty($custom)) {
            return str_replace('{name}', $customerName ?? 'เจ้าชะตา', $custom);
        }

        $name = $customerName ?: 'เจ้าชะตา';

        return "🌙 ขอบคุณ{$name}ที่ไว้วางใจแม่หมอจันทรานะคะ\n\n"
            ."ขอให้ดวงดาวคุ้มครอง พบเจอแต่สิ่งดี ๆ ✨\n"
            .'หากต้องการปรึกษาเพิ่มเติมเมื่อใด แม่หมอพร้อมเสมอค่ะ 🙏';
    }
}
