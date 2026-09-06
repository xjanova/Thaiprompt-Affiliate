<?php

namespace App\Services\Fortune;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🛒 FortunePackageOffer (2026-09-06)
 *
 * "ความจำว่าเรากำลังขายอยู่" — บอทต้องรู้ตัวว่าเพิ่งยื่นเมนูแพคเกจไปให้ใคร
 * แล้วคำตอบที่กลับมาต้องถูกอ่านเป็น **การเลือกแพคเกจ** ก่อนอย่างอื่นเสมอ
 *
 * ต้นเรื่อง (owner 2026-09-06 · Bunphon Saenchawee r12479 FTU-260906-H1143):
 *   ลูกค้ากดปุ่ม "🪬 ดูคุณไสย 99฿" จากกล่องกระตุ้น แต่กดช้าไป 3 ชม.
 *   ตอนนั้น flow หมดอายุ (flow_exit 30 นาที) → ไม่มี reading สถานะ tier_choice
 *   ⇒ ด่านเดียวที่รู้จักคำว่า "คุณไสย" คือ handleTierChoice ซึ่งต้องมี reading ถึงจะรัน
 *   ⇒ ข้อความหล่นเข้าเลนแชทฟรี → กฎ G ("ห้ามเป็นผู้ช่วยความรู้ทั่วไป") ตีเป็นเรื่องนอกขอบเขต
 *   ⇒ บอท **ปฏิเสธลูกค้าที่กำลังจะจ่าย 99฿** ทั้งที่สวิตช์เลนคุณไสยเปิดอยู่
 *
 * 🔑 หลักคิด: อายุของ "เรากำลังขาย" ต้องยาวกว่าอายุของ reading
 *   reading ปิดที่ 30 นาที แต่คนกดปุ่มทีหลังยังเป็นลูกค้าคนเดิมที่เห็นราคาไปแล้ว
 *   ⇒ เก็บ "ชุดแพคเกจที่ยื่นไป" ไว้ที่ *ตัวลูกค้า* (Cache) ไม่ใช่ที่ reading
 *
 * ⚠️ ทำไมไม่แค่เติม keyword ใน handleTierChoice:
 *   ปุ่มที่เราส่งเองมี 3 ที่ (FortuneChannelManager / LineFortuneService / FortuneFlowNudge)
 *   และส่งเป็น "ข้อความดิบ" ทั้งหมด — เติม keyword ที่ด่านเดียวแก้ได้แค่เคสที่ยังมี reading
 *   ([[rule_button_path_needs_own_guards]] · [[rule_feature_built_but_never_wired]])
 */
class FortunePackageOffer
{
    /** Cache key prefix — ผูกกับ "ตัวลูกค้า" ไม่ใช่ reading */
    protected const CACHE_PREFIX = 'fortune:tier_offer_pending:';

    /**
     * อายุความจำ 48 ชม.
     *
     * ⚠️ ต้องยาวกว่า EXIT_AFTER_SEC ของ FortuneFlowNudge (30 นาที) หลายเท่า —
     *    นั่นคือทั้งหมดของบั๊กนี้. เคสจริงกดช้า 3 ชม. 12 นาที
     */
    protected const TTL_HOURS = 48;

    /**
     * ความยาวสูงสุดที่ยอมอ่านว่าเป็น "การกดปุ่มเลือกแพคเกจ" (ตัวกรองหยาบชั้นแรก)
     *
     * ⚠️ ยาวกว่านี้ = ประโยคเล่าเรื่องของลูกค้า ห้ามกลืนไปสร้างบิล
     *    ([[rule_gate_must_not_swallow_customer_text]])
     *    ป้ายยาวสุดที่เราส่งจริงคือ "ดู vip ส่วนตัว 99บาท" (20 ตัว) + อีโมจิ/ราคา
     */
    protected const MAX_CHOICE_LEN = 40;

    // ============================================================
    // คลังคำ — แหล่งเดียวของทั้งระบบ
    // ============================================================

    /**
     * 🪬 คุณไสย / มนต์ดำ
     *
     * ⚠️ ห้ามใส่ 'โดนของ' — นั่นคือ *อาการที่ลูกค้าเล่า* ไม่ใช่ชื่อแพคเกจ
     *    ("หนูว่าหนูโดนของ") ⇒ ใส่แล้วกลายเป็นยัดบิลใส่คนที่แค่มาระบายทุกข์
     *    ([[rule_hardship_is_not_a_buy_signal]])
     *
     * @var array<int, string>
     */
    public const BLACK_MAGIC_KEYWORDS = [
        'ดูคุณไสย', 'คุณไสย', 'คุนไสย', 'มนต์ดำ', 'มนตร์ดำ',
        'tier_celtic_blackmagic', 'blackmagic', 'black_magic',
    ];

    // ============================================================
    // 1) จำว่ายื่นเมนูไปแล้ว
    // ============================================================

    /**
     * ติดธง "เรากำลังขายอยู่กับคนนี้"
     *
     * เรียกทุกครั้งที่ยื่นเมนูแพคเกจ / กล่องกระตุ้นที่มีปุ่มแพคเกจ
     *
     * @param  string  $userId  PSID (FB) หรือ LINE userId
     * @param  array<int, string>  $tiers  แพคเกจที่ยื่นไปจริง ('deep'|'celtic'|'celtic_blackmagic')
     */
    public static function arm(string $userId, array $tiers): void
    {
        $tiers = array_values(array_unique(array_filter($tiers)));
        if ($userId === '' || $tiers === []) {
            return;
        }

        Cache::put(self::CACHE_PREFIX.$userId, [
            'tiers' => $tiers,
            'at' => now()->toIso8601String(),
        ], now()->addHours(self::TTL_HOURS));
    }

    /**
     * ยังมีเมนูค้างอยู่ไหม → คืนรายชื่อแพคเกจที่ยื่นไป
     *
     * @return array<int, string>
     */
    public static function pendingTiers(string $userId): array
    {
        if ($userId === '') {
            return [];
        }

        $data = Cache::get(self::CACHE_PREFIX.$userId);

        return is_array($data) ? (array) ($data['tiers'] ?? []) : [];
    }

    /**
     * ล้างธง — เรียกเมื่อ "ปิดการขาย" แล้ว (เลือกแพคเกจ/สร้างบิลสำเร็จ)
     */
    public static function clear(string $userId): void
    {
        if ($userId !== '') {
            Cache::forget(self::CACHE_PREFIX.$userId);
        }
    }

    // ============================================================
    // 2) อ่านคำตอบว่าเป็นการเลือกแพคเกจไหม
    // ============================================================

    /**
     * คำตอบที่ยอมรับว่าเป็น "การกดปุ่ม" — เทียบแบบ **ทั้งสตริง** ไม่ใช่สับสตริง
     *
     * 🚨 นี่คือหัวใจความปลอดภัยของด่านนี้ — ธงมีอายุ 48 ชม. ถ้าเทียบแบบสับสตริง
     *    ประโยคอย่าง *"คุณไสยมีจริงไหมคะ"* (17 ตัว ผ่านด่านความยาวสบาย ๆ)
     *    จะกลายเป็นการสั่งเปิดบิล 99฿ ให้คนที่แค่มาถาม
     *    ⇒ เทียบเท่ากันทั้งสตริงเท่านั้น ([[rule_hardship_is_not_a_buy_signal]])
     *
     * ป้ายที่ลูกค้าส่งกลับมามี 2 ทรง — ทั้ง `text/payload` และ *ตัวหนังสือบนปุ่ม*
     * ([[rule_fb_quickreply_label_arrives_as_text]]) จึงต้องเก็บทั้งคู่
     *
     * @var array<string, array<int, string>>
     */
    protected const CHOICE_ANSWERS = [
        // ⚠️ ห้ามใส่ 'โดนของ' — อาการที่ลูกค้าเล่า ไม่ใช่ชื่อแพคเกจ
        'celtic_blackmagic' => [
            'ดูคุณไสย', 'ดูคุณไสย์', 'คุณไสย', 'คุณไสย์', 'คุนไสย',
            'มนต์ดำ', 'มนตร์ดำ', 'ดูมนต์ดำ',
            'tier_celtic_blackmagic', 'blackmagic', 'black_magic',
        ],
        'celtic' => [
            'celtic', 'เซลติก', 'ดูเซลติก', 'tier_celtic_99',
            'ดู vip ส่วนตัว', 'vip ส่วนตัว', 'ดู vip', 'vip',
        ],
        // ⚠️ ห้ามใส่ 'ดูดวง' — นั่นแปลว่า "ขอดูเมนู" ไม่ใช่ "ฉันเลือก 39"
        //    ใส่แล้วคนพิมพ์ดูดวงจะโดนเปิดบิลข้ามเมนูโดยไม่เคยเห็นราคา
        //    (ปุ่ม 39 ส่ง text '39' ซึ่งบล็อก tier-direct เดิมรับอยู่แล้ว)
        'deep' => [
            'tier_deep_39',
        ],
    ];

    /**
     * แปลงข้อความ → ชื่อแพคเกจ (ไม่สนว่ามีเมนูค้างหรือไม่)
     *
     * ลำดับสำคัญ: คุณไสย → celtic → deep
     *   ("ดูคุณไสย 99฿" มีทั้งคำว่าคุณไสยและเลข 99 — ต้องได้คุณไสย)
     *
     * @return string|null 'celtic_blackmagic' | 'celtic' | 'deep' | null
     */
    public static function matchTier(string $text): ?string
    {
        $normalized = self::normalize($text);
        if ($normalized === '' || mb_strlen($normalized) > self::MAX_CHOICE_LEN) {
            return null;
        }

        // ตัดราคาท้ายป้ายออก — "ดูคุณไสย 99฿" → "ดูคุณไสย" / "ดู vip ส่วนตัว 99บาท" → "ดู vip ส่วนตัว"
        $stripped = trim((string) preg_replace('/\s*\d+\s*(บาท|บ)?$/u', '', $normalized));

        foreach (self::CHOICE_ANSWERS as $tier => $answers) {
            foreach ($answers as $answer) {
                $answer = mb_strtolower($answer);
                if ($normalized === $answer || $stripped === $answer) {
                    return $tier;
                }
            }
        }

        return null;
    }

    /**
     * คำตอบนี้ = การกดปุ่มเลือกแพคเกจ "ที่เรายื่นไปจริง" หรือเปล่า
     *
     * เข้มกว่า matchTier() ตรงที่ต้องมีเมนูค้าง **และ** แพคเกจนั้นอยู่ในชุดที่ยื่นไป
     * ⇒ ปลอดภัยพอที่จะข้ามไปสร้างบิลตรง ๆ เพราะลูกค้าเห็นราคาบนปุ่มไปแล้ว
     *
     * @return string|null ชื่อแพคเกจที่เลือก หรือ null ถ้าไม่ใช่
     */
    public static function resolveChoice(string $userId, string $text): ?string
    {
        $pending = self::pendingTiers($userId);
        if ($pending === []) {
            return null;
        }

        $tier = self::matchTier($text);
        if ($tier === null || ! in_array($tier, $pending, true)) {
            return null;
        }

        Log::info('🛒 FortunePackageOffer: คำตอบตรงกับปุ่มแพคเกจที่เรายื่นไป — อ่านเป็นการเลือกแพคเกจ', [
            'user_id' => $userId,
            'tier' => $tier,
            'offered' => $pending,
            'text_preview' => mb_substr($text, 0, 40),
        ]);

        return $tier;
    }

    /**
     * ทำความสะอาดป้ายปุ่ม → เหลือแต่เนื้อคำ
     *
     * ป้ายจริงที่ลูกค้าส่งกลับมาหน้าตาแบบ "🪬 ดูคุณไสย 99฿" / "🔹 ดูดวง 39 บาท"
     *
     * ⚠️ ห้ามใช้ `[^\p{L}\p{N}\s]` ล้วน — จะกินสระ/วรรณยุกต์ไทยทิ้ง
     *    ต้องมี \p{M} เสมอ ([[rule_thai_regex_needs_mark_class]])
     * ⚠️ ห้าม trim() ด้วย charlist หลายไบต์ ([[rule_never_byte_trim_thai_charlist]])
     */
    protected static function normalize(string $text): string
    {
        $clean = preg_replace('/[^\p{L}\p{M}\p{N}\s_]+/u', ' ', $text) ?? $text;
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return mb_strtolower(trim($clean));
    }
}
