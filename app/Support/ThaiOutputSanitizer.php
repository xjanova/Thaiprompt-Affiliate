<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * 🧹 ตัวกรองข้อความขาออกก่อนส่งถึงลูกค้า (เลนดูดวงไทย)
 *
 * ⚠️ ทำไมต้องมี (2026-09-04 — owner เจอเอง "หลุดหลายบิลเลย"):
 *   โมเดลหลุดคำภาษาอื่นมาต่อท้ายประโยคไทยเป็นครั้งคราว แล้ววิ่งถึงลูกค้าที่จ่ายเงินตรงๆ
 *   เพราะ **เลน Celtic 99฿ ไม่เคยผ่าน sanitizer ใดเลย** (`FortuneAIService::sanitizeChatResult()`
 *   คุมเฉพาะเลนแชท/39฿ — คอมเมนต์ในไฟล์นั้นเขียนไว้เองว่า "Celtic flow แยก strip เอง")
 *
 *   เคสจริงที่วัดได้จาก prod (21 วัน ย้อนหลัง):
 *     • `fortune_celtic_questions.response` — หลุด 3 / 676 (0.44%) กระจาย 3 บิล 2 โมเดล
 *       - rd11214 seq3 `окончательно` (gpt-5.6-luna) · rd11557 seq2 `排除` (gpt-5.6-sol)
 *       - rd12214 seq2 `...หรือสุขภาพ在线a` (gpt-5.6-luna) ← owner เห็นใบนี้
 *     • `deep_response` 0 / 66 · `ai_response` 0 / 1 = เลนที่ผ่าน sanitizer อยู่แล้วสะอาด
 *   ⇒ ไม่ใช่ปัญหาของโมเดลตัวใดตัวหนึ่ง แต่คือ "เลนนี้ไม่มีตะแกรง"
 */
class ThaiOutputSanitizer
{
    /**
     * อักษรที่ไม่ควรโผล่ในคำทำนายภาษาไทย — จีน/ญี่ปุ่น/เกาหลี/รัสเซีย
     *
     * 🚫 จงใจ **ไม่** รวม: อีโมจิ · อักษรไทย · อังกฤษ · ตัวเลข · เครื่องหมายวรรคตอน
     *    (แม่หมอใช้อังกฤษปนได้ตามปกติ เช่น ชื่อแบรนด์/หน่วยเงิน — ห้ามตัด)
     */
    public const FOREIGN_SCRIPT_PATTERN = '/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Cyrillic}]+/u';

    /** หลุดเกินสัดส่วนนี้ = โมเดลตอบผิดภาษาทั้งก้อน ไม่ใช่เศษหลุด → ยกระดับ log */
    protected const ANOMALY_RATIO = 0.15;

    /**
     * ตัดอักษรต่างภาษาออกจากข้อความขาออก
     *
     * @param  string  $text  ข้อความที่กำลังจะส่งลูกค้า
     * @param  array<string, mixed>  $logContext  บริบทสำหรับ log (reading_id / sequence / model)
     * @return string ข้อความที่กรองแล้ว — ไม่มีอะไรให้ตัด = คืนของเดิมทั้งดุ้น (ไม่แตะ)
     */
    public static function stripForeignScript(string $text, array $logContext = []): string
    {
        if ($text === '') {
            return $text;
        }

        $runs = preg_match_all(self::FOREIGN_SCRIPT_PATTERN, $text, $matches);
        if (! $runs) {
            return $text;
        }

        $cleaned = (string) preg_replace(self::FOREIGN_SCRIPT_PATTERN, '', $text);

        // เก็บกวาดช่องว่างที่เหลือจากการตัด — ⚠️ ห้ามยุบ \n (ย่อหน้าคำทำนายจะพังติดกันหมด)
        $cleaned = (string) preg_replace('/[ \t]{2,}/u', ' ', $cleaned);
        $cleaned = (string) preg_replace('/[ \t]+(\R)/u', '$1', $cleaned);
        $cleaned = trim($cleaned);

        $originalLen = mb_strlen($text);
        $removed = $originalLen - mb_strlen($cleaned);

        Log::warning('Fortune: ตัดอักษรต่างภาษาออกจากคำตอบก่อนส่งลูกค้า', $logContext + [
            'runs' => $runs,
            'samples' => array_slice($matches[0], 0, 5),
            'removed_chars' => $removed,
            'original_len' => $originalLen,
        ]);

        // 🚨 หายไปเยอะผิดปกติ = ไม่ใช่เศษหลุดแล้ว — ต้องรู้ทันที ไม่ใช่ให้เงียบอยู่ใน warning
        if ($originalLen > 0 && ($removed / $originalLen) > self::ANOMALY_RATIO) {
            Log::error('Fortune: คำตอบมีอักษรต่างภาษาเกินปกติ — สงสัยโมเดลตอบผิดภาษาทั้งก้อน', $logContext + [
                'removed_ratio' => round($removed / $originalLen, 3),
                'preview' => mb_substr($text, 0, 200),
            ]);
        }

        // 🛡️ กันเคสสุดขั้ว: ตัดแล้วไม่เหลืออะไรเลย → ส่งของเดิมดีกว่าส่งข้อความว่าง
        //    (ลูกค้าจ่ายเงินแล้ว ได้ข้อความแปลกยังดีกว่าไม่ได้อะไร — และ log error ด้านบนจับไว้แล้ว)
        return $cleaned === '' ? $text : $cleaned;
    }
}
