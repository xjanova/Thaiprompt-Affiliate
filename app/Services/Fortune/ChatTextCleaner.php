<?php

namespace App\Services\Fortune;

/**
 * 🧹 ChatTextCleaner — ล้างของที่โมเดลเขียนติดมา ก่อนข้อความถึงลูกค้าในแชท
 *
 * เจ้าของรายงาน (2026-09-23): "เมื่อวาน แม่หมอทำนายใน LINE มีสัญลักษณ์พิเศษปรากฏในคำทำนาย"
 * ตรวจ prod ย้อนหลัง 30 วัน เจอ 2 เรื่อง:
 *
 *   1. markdown ที่แชทไม่ render — LINE/Messenger/Telegram โชว์ดิบทุกตัว
 *      - หัวข้อ "## 🔮 ภาพรวมชีวิตช่วงนี้" 600 บรรทัด · "**ตัวหนา**" 1,188 บรรทัด
 *      - ป้ายโครงพรอมต์ "## Section A" / "🌙 **Section A — ทายเจ้าชะตา**" 84 บรรทัด (18 ทรง)
 *        พรอมต์ดูดวง 39 แบ่งงานเป็น Section A/B ให้โมเดล — โมเดลเอาชื่อป้ายมาตั้งเป็นหัวข้อให้ลูกค้าเห็น
 *      - ตัวล้างเดิม (stripMessengerMarkdown) ลบได้แค่ *ดอกจันเดี่ยว* ⇒ "**x**" เหลือ "*x*" ยังเห็นดอกจัน
 *      - คำทำนาย 39 บน LINE ไม่ผ่านตัวล้างเลย — ประกอบ Flex จาก deep_response ในฐานข้อมูลตรง ๆ
 *
 *   2. คำตอบคุยต่อเป็น JSON ดิบ `{ "response": "…\n\n…" }` (FTU-260922-G4552 · 2 ครั้ง)
 *      AI ตัวหลักค้างจนหมดเวลา 120 วิ → ทางสำรองใช้ตัวช่วยที่ต่อท้ายว่า "ตอบ JSON" (ของด่านคัดเจตนา)
 *      → โมเดลตอบเป็น JSON แล้วส่งให้ลูกค้าทั้งก้อน · ดู unwrapJsonReply()
 *
 * 🔒 กติกา:
 *   - ไม่มีเครื่องหมายที่ต้องล้าง = คืนต้นฉบับทุกตัวอักษร · ล้างซ้ำได้ผลเดิม (idempotent)
 *   - แตะเฉพาะ markdown ที่แชทไม่ render — ไม่แตะอีโมจิ (ด่านนับอีโมจิหัวข้อใช้ str_contains)
 *     ไม่แตะเส้นคั่น ═══ · แฮชแท็ก "#ดูดวง" (ไม่มีช่องว่างตาม #) · snake_case
 *   - ใช้ "ตอนส่งเข้าแชท" — ข้อความในฐานข้อมูลคงต้นฉบับไว้ (คำทำนายเก่าที่เก็บไว้แล้วก็ได้รับการล้างตอนส่งซ้ำ)
 */
class ChatTextCleaner
{
    /**
     * ป้ายโครงพรอมต์ "Section A/B" ที่ขึ้นต้นบรรทัด (หลัง #, อีโมจิ, ** ได้)
     *   กลุ่ม 1 = ของนำหน้า (เก็บไว้ ขั้นถัดไปล้าง # / ** เอง) · กลุ่ม 2 = ตัวคั่น · กลุ่ม 3 = ชื่อหัวข้อที่ตามหลัง
     *   ทรงที่เจอจริงบน prod: "## Section A" · "## Section B — ภาพรวมชีวิตช่วงนี้" · "🌙 **Section A — ทายเจ้าชะตา**"
     *   "**Section B — …**" · "## 🌙 Section A — ทายลูก" · "### 🌙 Section A — …" · "Section A — …" · "Section B"
     *   + "**Section A**: …" (ตัวหนาปิดก่อนตัวคั่น — กินทิ้งด้วย ไม่งั้นเหลือ ": …")
     */
    private const SECTION_LABEL = '/^([ \t]*(?:#{1,6}[ \t]*)?(?:[\p{So}\p{Sk}\x{FE0F}\x{200D}]+[ \t]*)?(?:\*{1,2}[ \t]*)?)Section[ \t]+[A-Z](?![\p{L}\p{N}])\*{0,2}[ \t]*([—–\-:：]?)[ \t]*(.*)$/mu';

    /** คีย์ที่โมเดลใช้ห่อคำตอบเมื่อเผลอตอบเป็น JSON — เรียงตามที่เจอบ่อย */
    private const REPLY_KEYS = ['response', 'reply', 'message', 'answer', 'text'];

    /**
     * ล้าง markdown ที่แชทไม่ render — หัวข้อ #, ตัวหนา **x**, *x*, _x_ และป้าย Section A/B
     *
     * @param  string  $text  ข้อความที่จะส่งเข้าแชท (คำทำนาย/คำตอบ/ข้อความระบบ)
     * @return string ข้อความที่ไม่มีเครื่องหมาย markdown · ไม่มีอะไรต้องล้าง = ต้นฉบับทุกตัวอักษร
     */
    public static function stripMarkdown(string $text): string
    {
        if ($text === '' || ! preg_match('/[#*_]|Section/u', $text)) {
            return $text;
        }

        // CRLF → LF ก่อน — "$" ของโหมดหลายบรรทัดไม่ตรงหน้า "\r" ⇒ "##\r" ไม่ถูกจับ / "\r" ค้างท้ายบรรทัดที่แก้
        $base = str_replace("\r\n", "\n", $text);
        $out = $base;

        // 1) ป้าย Section A/B — เก็บชื่อหัวข้อที่ตามหลัง · ป้ายล้วน ("## Section A") = ลบทั้งบรรทัด
        $out = preg_replace_callback(
            self::SECTION_LABEL,
            static function (array $m): string {
                // "Section A of the plan…" = ประโยคอังกฤษ ไม่ใช่ป้าย — ป้ายจริงตามด้วยตัวคั่น / หัวข้อไทย / ท้ายบรรทัดเสมอ
                if ($m[2] === '' && preg_match('/^[A-Za-z]/', $m[3]) === 1) {
                    return $m[0];
                }

                return preg_match('/[\p{L}\p{N}]/u', $m[3]) === 1 ? $m[1].$m[3] : '';
            },
            $out
        ) ?? $out;

        // 2) หัวข้อ markdown "## " ต้นบรรทัด — "#ดูดวง" (แฮชแท็ก ไม่มีช่องว่างตาม) ไม่ใช่หัวข้อ ไม่แตะ
        $out = preg_replace('/^[ \t]*#{1,6}(?:[ \t]+|$)/mu', '', $out) ?? $out;

        // 3) ตัวหนา **x** ก่อน — ตัวล้างเดิมจับดอกจันเดี่ยวคู่ในสุด ทำให้ "**x**" เหลือ "*x*"
        $out = preg_replace('/\*\*([^\n]+?)\*\*/u', '$1', $out) ?? $out;
        //    ** ที่ค้างเดี่ยว (ตัวหนาคร่อมบรรทัด / โมเดลโดนตัดจบกลางคำ) — เจอจริง 24 บรรทัดใน 30 วัน
        $out = str_replace('**', '', $out);

        // 4) *x* และ _x_ — กติกาเดิมของ stripMessengerMarkdown (2026-05-09) ทุกตัวอักษร
        //    [^*\n] กันข้ามบรรทัด · lookaround ของ _ กัน snake_case (user_id)
        $out = preg_replace('/\*([^*\n]+?)\*/u', '$1', $out) ?? $out;
        $out = preg_replace('/(?<![\p{L}\p{N}_])_([^_\n]+?)_(?![\p{L}\p{N}_])/u', '$1', $out) ?? $out;

        if ($out === $base) {
            return $text;
        }

        // บรรทัดหัวข้อที่ลบทิ้งทิ้งช่องว่างซ้อนไว้ — เหลือเว้นได้ไม่เกิน 1 บรรทัด
        $out = preg_replace('/\n(?:[ \t]*\n){2,}/u', "\n\n", $out) ?? $out;

        return trim($out, "\n");
    }

    /**
     * 📦 แกะคำตอบที่โมเดลห่อมาเป็น JSON — `{"response": "…"}` / ```json … ``` / JSON ที่โดนตัดจบกลางสตริง
     *
     * ใช้กับเส้นที่ "ต้องการข้อความธรรมดา" เท่านั้น — ด่านที่ตั้งใจขอ JSON (discoverIntent) ห้ามเรียก
     * ไม่ใช่ JSON / ไม่มีช่องคำตอบ = คืนต้นฉบับทุกตัวอักษร (ไม่เดา)
     */
    public static function unwrapJsonReply(string $text): string
    {
        $raw = trim($text);
        if ($raw === '') {
            return $text;
        }

        if (preg_match('/^```(?:json)?\s*(.*?)\s*(?:```)?$/su', $raw, $fence) === 1) {
            $raw = trim($fence[1]);
        }

        if (! str_starts_with($raw, '{')) {
            return $text;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach (self::REPLY_KEYS as $key) {
                if (isset($decoded[$key]) && is_string($decoded[$key]) && trim($decoded[$key]) !== '') {
                    return trim($decoded[$key]);
                }
            }

            return $text;
        }

        $keys = implode('|', self::REPLY_KEYS);

        // ก) JSON โดนตัดจบกลางสตริง (max_tokens) — ค่าต้องวิ่งถึงท้ายข้อความโดยไม่มี " ปิดเลย
        //    ⚠️ ห้ามรับทรงที่มี " ปิดกลางทาง — นั่นคือ JSON เสียจาก " ที่ไม่ได้ escape ตัดตรงนั้น = คำตอบหายครึ่งท่อนเงียบ ๆ
        if (preg_match('/^\{\s*"(?:'.$keys.')"\s*:\s*"((?:[^"\\\\]|\\\\.)*)\\\\?\z/su', $raw, $m) === 1) {
            // escape ที่โดนตัดครึ่งท้ายสตริง — "\" เดี่ยวตัดทิ้งไปแล้วนอกกลุ่มจับ · "\u0E" ครึ่งตัวตัดตรงนี้
            //   (ไม่ตัดทิ้ง json_decode ล้มทั้งก้อน)
            return self::decodeJsonString(preg_replace('/\\\\u[0-9a-fA-F]{0,3}$/u', '', $m[1]) ?? $m[1], $text);
        }

        // ข) ห่อครบคีย์เดียว แต่ข้างในมี " ที่ไม่ได้ escape — {"response": "แม่หมอบอกว่า "สู้ๆ" นะคะ"}
        //    เอาถึง " ตัวสุดท้ายก่อน } · มีคีย์อื่นต่อท้าย (", "offer": …) = ไม่ใช่ทรงนี้ ไม่เดา
        if (preg_match('/^\{\s*"(?:'.$keys.')"\s*:\s*"(.*)"\s*\}$/su', $raw, $m) === 1
            && preg_match('/"\s*,\s*"[A-Za-z_]+"\s*:/', $m[1]) !== 1) {
            $inner = preg_replace_callback('/(?<!\\\\)"/u', static fn (): string => '\\"', $m[1]) ?? $m[1];

            return self::decodeJsonString($inner, $text);
        }

        // ค) ทรงอื่น — คืนต้นฉบับ (ลูกค้าเห็น JSON ดีกว่าได้คำตอบครึ่งท่อนโดยไม่มีใครรู้)
        return $text;
    }

    /** ถอด escape ของสตริง JSON ("…\n…") — ถอดไม่ได้ใช้แทนที่ตรง ๆ · ได้ค่าว่าง = คืนต้นฉบับ */
    private static function decodeJsonString(string $inner, string $original): string
    {
        $value = json_decode('"'.$inner.'"');
        if (! is_string($value)) {
            $value = strtr($inner, ['\\n' => "\n", '\\t' => "\t", '\\"' => '"', '\\/' => '/', '\\\\' => '\\']);
        }

        return trim($value) !== '' ? trim($value) : $original;
    }

    /**
     * 🗣️ คำตอบข้อความธรรมดาจากทางสำรอง — แกะ JSON + ตัดป้ายชื่อผู้พูดที่โมเดลเลียนจากประวัติ
     *
     * ทางสำรองส่งประวัติแบบ "[ลูกค้า]: … / [หมอจันทรา]: …" ⇒ โมเดลบางครั้งขึ้นต้นคำตอบด้วย "[หมอจันทรา]: "
     */
    public static function plainReply(string $text): string
    {
        // ตัดป้ายก่อนแกะด้วย — "[หมอจันทรา]: {"response": …}" ขึ้นต้นด้วย [ ตัวแกะจะไม่มองเป็น JSON
        $label = '/^\s*\[?(?:แม่หมอจันทรา|หมอจันทรา|แม่หมอ)\]?\s*[:：]\s*/u';
        $out = self::unwrapJsonReply(preg_replace($label, '', $text) ?? $text);

        return preg_replace($label, '', $out) ?? $out;
    }
}
