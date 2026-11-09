<?php

namespace App\Services\Fortune;

use App\Services\FortuneAIService;

/**
 * 🧼 (2026-11-03 FB web-only) ตัวกรองข้อความของ "ความต่อเนื่องแชท FB → เว็บจันทรา"
 *
 * ข้อความจากแชท FB (ของลูกค้าและของบอท) ถูกยกไปวางใน system message ของห้องแม่หมอบนเว็บ
 * จึงต้องกรอง 2 ทาง:
 *
 *   ขาเข้า (sanitizeInbound) — ก่อนวางลงบล็อกความจำ
 *     - ถอดแท็กภายในทุกตัว (รวมแท็ก action ที่ stripInternalContextTags ยกเว้นไว้ เช่น [OFFER_FORTUNE])
 *     - ทิ้ง [IMAGE_ATTACHED] / __PREDICT_ALL__
 *     - ลบ URL, m.me, line.me, lin.ee, เบอร์โทร, ตัวเลขยาว ≥ 8 หลัก (เลขบัญชี) และจำนวนเงิน บาท/฿
 *     - ข้อความของบอทที่พูดเรื่องจ่ายเงิน → แทนทั้งข้อความด้วย "(แม่หมอแจ้งรายละเอียดค่าบริการ)"
 *       (เว็บห้ามพูดเรื่องโอน/สลิป/บัญชี/ราคาในเพจ — ถ้าส่งเนื้อไป AI จะเอาไปพูดต่อ)
 *     - บรรทัดที่ detectAdversarialInput จับได้ → "(ข้อความถูกตัด)"
 *     - ทำให้รั้ว ==== และป้าย [[ ... ]] ไร้ผล (กันลูกค้าฝังคำสั่งให้หลุดรั้วข้อมูล/ปลอมป้าย OFFER)
 *
 *   ขาออก (sanitizeOutbound) — ทุกคำตอบของห้องแชทเว็บก่อนถึงลูกค้า
 *     - stripInternalContextTags + ลบ [CUSTOMER_*] / [PAST_CASE_*] + แท็ก action ที่เหลือ
 *     - ตัด prefix "แม่หมอ:" / "บุษบา:" ที่ AI ชอบเลียนแบบจากประวัติแชท
 */
final class WebChatContinuitySanitizer
{
    /** ข้อความแทนคำตอบของบอทที่พูดเรื่องเงิน */
    public const PAYMENT_PLACEHOLDER = '(แม่หมอแจ้งรายละเอียดค่าบริการ)';

    /** ข้อความแทนบรรทัดที่เป็นการโจมตี prompt */
    public const ADVERSARIAL_PLACEHOLDER = '(ข้อความถูกตัด)';

    /** แท็ก action ที่ stripInternalContextTags ยกเว้นไว้ — บนเว็บไม่มีความหมาย ต้องถอดทิ้งเสมอ */
    private const ACTION_TAGS = 'OFFER_FORTUNE|DEEP_READING|ASK_SAVE|USE_STRIPE|SKIP|END_SESSION|OFF_TOPIC_REPICK';

    /** คำที่บอกว่าข้อความของบอทพูดเรื่องจ่ายเงิน */
    private const PAYMENT_WORDS = '/(โอน|สลิป|บัญชี|พร้อมเพย์|promptpay|qr|คิวอาร์|ค่าครู|ชำระ|ราคา|บาท|฿|จ่ายเงิน|ยอดเงิน|ยอดโอน|stripe|บัตรเครดิต|ค่าบริการ|เปิดบิล)/iu';

    /**
     * กรองข้อความหนึ่งข้อความก่อนวางลงบล็อกความจำ
     *
     * @param  string  $text  ข้อความดิบจากแชท FB / intent / คำทำนาย
     * @param  string  $role  'user' | 'assistant' | 'system'
     * @return string ข้อความที่ปลอดภัย ('' = ไม่เหลืออะไรให้ใช้ ผู้เรียกควรข้าม)
     *
     * @example
     * WebChatContinuitySanitizer::sanitizeInbound('โอนมาที่ 123-4-56789-0 นะคะ', 'assistant');
     * // ผลลัพธ์: '(แม่หมอแจ้งรายละเอียดค่าบริการ)'
     */
    public static function sanitizeInbound(string $text, string $role = 'user'): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // บอทพูดเรื่องเงิน → ไม่ส่งเนื้อไปเลย (ตรวจก่อนลบตัวเลข ไม่งั้น "99 บาท" หายไปก่อนจะเห็น)
        if ($role !== 'user' && preg_match(self::PAYMENT_WORDS, $text)) {
            return self::PAYMENT_PLACEHOLDER;
        }

        // 1) ทำให้ป้าย [[ ]] และรั้ว ==== ไร้ผลก่อน — [[OFFER:love]] จะกลายเป็น [OFFER:love] แล้วถูกถอดในขั้นถัดไป
        $text = self::neutralizeFences($text);

        // 2) ถอดแท็กภายใน
        if ($role === 'user') {
            // ข้อความลูกค้า: ไม่เรียก stripInternalContextTags เพราะมันแปลงสรรพนามด้วย
            // (ไม่อยากเปลี่ยน "ครับ" ของลูกค้าเป็น "ค่ะ") — ถอดแท็กด้วย pattern ด้านล่างแทน
            $text = (string) preg_replace('/[\(\[]\s*(?:👤\s*)?ลูกค้าคนนี้\s*[:：][^\)\]]{0,500}[\)\]]/u', '', $text);
        } else {
            $text = FortuneAIService::stripInternalContextTags($text);
        }
        // แท็กอังกฤษในวงเล็บเหลี่ยมทุกตัว (รวมแบบมีช่องว่างหลัง "[" ที่ pattern หลักไม่จับ เช่น "[ OFFER : money ]")
        $text = (string) preg_replace('/\[\s*(?:👤|[A-Za-z_])[^\]\n]{0,300}\]/u', '', $text);
        $text = self::stripActionTags($text);
        $text = str_replace(['[IMAGE_ATTACHED]', '__PREDICT_ALL__'], '', $text);

        // 3) ลบช่องทางติดต่อ/ลิงก์
        $text = (string) preg_replace('~(?:https?://|www\.)\S+~iu', '', $text);
        $text = (string) preg_replace('~\b(?:m\.me|line\.me|lin\.ee|fb\.me|wa\.me)\b\S*~iu', '', $text);

        // 4) จำนวนเงินก่อน (1,000 บาท / ฿99) แล้วค่อยตัวเลขยาว ≥ 8 หลัก (เบอร์โทร / เลขบัญชีที่มีขีดคั่น)
        $text = (string) preg_replace('/฿\s*\d[\d,\.]*|\d[\d,\.]*\s*(?:บาท|฿|บ\.)/u', '', $text);
        $text = (string) preg_replace('/\d(?:[\s\-\.]?\d){7,}/u', '', $text);

        // 5) บรรทัดที่ด่านกันเจลเบรคจับได้ → แทนทั้งบรรทัด
        $lines = preg_split('/\R/u', $text) ?: [];
        foreach ($lines as $i => $line) {
            $attack = trim($line) !== '' ? FortuneAIService::detectAdversarialInput(trim($line)) : null;
            if ($attack !== null) {
                $lines[$i] = self::ADVERSARIAL_PLACEHOLDER;
            }
        }
        $text = implode("\n", $lines);

        return self::tidy($text);
    }

    /**
     * กรองคำตอบของห้องแชทเว็บก่อนส่งให้ลูกค้า (ทำหลังแยกป้าย [[OFFER:หมวด]] ออกแล้ว)
     *
     * @example
     * WebChatContinuitySanitizer::sanitizeOutbound("แม่หมอ: [CUSTOMER_TIMELINE] ไพ่บอกว่าดีค่ะ");
     * // ผลลัพธ์: 'ไพ่บอกว่าดีค่ะ'
     */
    public static function sanitizeOutbound(string $reply): string
    {
        $reply = trim($reply);
        if ($reply === '') {
            return '';
        }

        $reply = FortuneAIService::stripInternalContextTags($reply);
        // แท็กบล็อกความจำ/เคสเก่า — เผื่อเกินเพดาน 300 ตัวของ pattern หลัก หรือข้ามบรรทัด
        $reply = (string) preg_replace('/\[\s*(?:CUSTOMER|PAST_CASE)_[^\]]*\]/iu', '', $reply);
        $reply = self::stripActionTags($reply);
        $reply = (string) preg_replace('/\[\[[^\]]*\]\]/u', '', $reply);
        $reply = (string) preg_replace('/={3,}/u', '', $reply);
        // prefix ผู้พูดที่ AI เลียนแบบจากรูปแบบประวัติ "แม่หมอ: ..."
        $reply = (string) preg_replace('/^[ \t]*(?:แม่หมอจันทรา|แม่หมอ|น้องบุษบา|บุษบา)[ \t]*[:：][ \t]*/um', '', $reply);

        return self::tidy($reply);
    }

    /** ทำให้รั้ว ==== และป้าย [[ ]] ไร้ผล */
    public static function neutralizeFences(string $text): string
    {
        $text = (string) preg_replace('/[=＝]{3,}/u', '=', $text);
        $text = (string) preg_replace('/\[\s*\[/u', '[', $text);

        return (string) preg_replace('/\]\s*\]/u', ']', $text);
    }

    private static function stripActionTags(string $text): string
    {
        return (string) preg_replace('/\[\s*(?:'.self::ACTION_TAGS.')\b[^\]\n]{0,100}\]/iu', '', $text);
    }

    private static function tidy(string $text): string
    {
        $text = (string) preg_replace('/[ \t]{2,}/u', ' ', $text);
        $text = (string) preg_replace('/[ \t]+\n/u', "\n", $text);
        $text = (string) preg_replace('/\n{3,}/u', "\n\n", $text);

        return trim($text);
    }
}
