<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 👂 (2026-09-15) ฟังลูกค้าระหว่างรอโอน — เลิกส่งกล่องทวงเงินซ้ำทุกข้อความ
 *
 * เจ้าของแจ้ง (บิล FTU-260915-D0350): *"ปัญหาเดิม ๆ ที่พอตอนจะชำระ ถ้าลูกค้าพูด หรือติดปัญหา
 *   บอทจะส่งแต่ให้โอนเงิน ไม่ฟังลูกค้า"*
 *
 * เคสจริง (FB Max Hawley, 23:14–23:16): ลูกค้าพิมพ์ 6 ข้อความหลังได้ QR
 *   "มีปัญหานิดหนึ่งค่ะ" / "คือไม่มีบัญชี" / "โอนเงินที่ไทยคะ" / "สักพัก" / "นะ" / "จะลองให้ญาติโอนให้นะคะ"
 *   → บอทตอบ "💸 รอเจ้าชะตาโอนค่าครู 99.54 บาทตาม QR ที่ส่งให้นะคะ" ซ้ำ **ทั้ง 6 ครั้ง** จนแอดมินต้องพิมพ์เอง
 *
 * สำรวจ log 3 วัน: ข้อความที่ลูกค้าพิมพ์ระหว่างรอโอนบิล 99 ได้กล่องสำเร็จรูปกลับไป 18/23 (14 ก.ย.) และ 9/13 (15 ก.ย.)
 * ต้นเหตุ 3 ทางที่ทำให้ "ฟังไม่ได้" แล้วตกไปกล่องเดิม:
 *   1. ข้อความสั้น < 10 ตัวที่ไม่อยู่ในลิสต์คำ ("ค่ะ", "สวัดีคะ", "ไม่มีแอบ", "สักพัก") ไม่เคยถึง AI เลย
 *   2. AI ล้ม (คืนนั้น Gemini "high demand" ทุก key) → ไม่มีชั้นไหนเข้าใจข้อความเลย
 *   3. AI ตอบครบ 3 รอบต่อบิลแล้วตัน → กลับไปกล่องเดิมตลอดอายุบิล 3 ชม.
 *      (ลูกค้าอีกคนโดนกล่องเดิม 12 ครั้งทั้งที่พิมพ์ "ก็บอกพรุ่งนี้ค่ะ")
 *
 * หลักใหม่ (ใช้ร่วมกันทั้ง handlePendingPayment บิล 39 + handleCelticPendingPayment บิล 99):
 *   ก. คำรับทราบล้วน ("ค่ะ/โอเค/ขอบคุณ") → ไม่ต้องใช้ AI · บอทเพิ่งพูดไปไม่เกิน 10 นาที = เงียบ
 *      (ยกเว้นบอทเพิ่งถามคำถาม — "ค่ะ" คือคำตอบ ต้องให้ AI ตีความ)
 *   ข. อย่างอื่นทั้งหมด → AI ฟังก่อนเสมอ (ไม่มีเกณฑ์ความยาว) พร้อมบอกว่าระบบจับเจตนาอะไรได้
 *   ค. AI ไม่ได้ผล → ตอบตามเจตนาที่จับได้แบบตายตัว (ไม่มีบัญชี/ให้ญาติโอน/ขอเวลา/พรุ่งนี้ ฯลฯ)
 *      จับไม่ได้ → บอกว่าอ่านแล้ว + ส่งต่อแอดมินจริง (saveQuestionForAdmin)
 *   ง. เรื่องเดิมภายใน 10 นาที = เงียบ · บรรทัดสรุปยอดบิลแนบได้ไม่เกิน 1 ครั้ง / 10 นาที
 *
 * สถานะกันพูดซ้ำเก็บใน Cache ต่อบิล (หายตอน deploy ได้ — แย่สุดคือตอบซ้ำ 1 ครั้ง ไม่กระทบเงิน)
 * และไม่เขียน conversation_state เพิ่ม — กันเขียนทับ key ที่เส้นยืนยันการจ่ายเงินเขียนพร้อมกัน
 *
 * ⚠️ ห้ามใช้ const ใน trait — composer ประกาศ php ^8.1 (ใช้เมธอดคืนค่าแทน)
 */
trait PendingPaymentListenerTrait
{
    /**
     * หน้าต่างกันพูดเรื่องเดิมซ้ำ (นาที)
     */
    protected function pendingListenWindowMinutes(): int
    {
        return 10;
    }

    /**
     * ลูกค้าพิมพ์ภายในกี่นาทีถึงนับว่า "กำลังคุยอยู่" — ตัวเตือนบิลอัตโนมัติต้องหลบ
     */
    public static function pendingCustomerActiveMinutes(): int
    {
        return 5;
    }

    /**
     * key Cache ที่บอกว่าลูกค้าเพิ่งพิมพ์ระหว่างรอโอน (อ่านโดย SendBillReminderJob)
     */
    public static function pendingCustomerActiveKey(int $readingId): string
    {
        return "fortune:pending_customer_active:{$readingId}";
    }

    /**
     * จดว่าลูกค้าเพิ่งพิมพ์ระหว่างรอโอน — เรียกบรรทัดแรกของตัวจัดการบิลรอโอนทั้ง 2 ตัว
     *
     * ที่มา: บิล FTU-260915-D0350 ตัวเตือนบิลอัตโนมัติยิง "แม่หมอเห็นว่าบิล... ยังไม่ได้โอนเลยนะคะ"
     *   ห่างจากที่ลูกค้าพิมพ์ "คือไม่มีบัญชี" แค่ 14 วินาที — ทวงคนที่กำลังเล่าปัญหาอยู่
     */
    protected function notePendingPaymentActivity(FortuneReading $reading): void
    {
        if (empty($reading->id)) {
            return;
        }

        try {
            Cache::put(
                self::pendingCustomerActiveKey((int) $reading->id),
                now()->toIso8601String(),
                now()->addMinutes(self::pendingCustomerActiveMinutes() + 1)
            );
        } catch (\Throwable $e) {
            // Cache ล่ม = แย่สุดคือตัวเตือนยิงแทรก 1 ครั้ง ไม่ต้องหยุดงานหลัก
        }
    }

    /**
     * จำแนกข้อความลูกค้าระหว่างรอโอน — ตายตัว ไม่พึ่ง AI (ใช้ตอน AI ล้ม และเป็นคำใบ้ให้ AI)
     *
     * ลำดับความสำคัญ (ตัวแรกที่ติดชนะ):
     *   claim > keep_bill > later > no_bank > third_party > no_money > problem > wait > apology > greeting > thanks > ack
     *
     * ตัวอย่างทดสอบดึงจากข้อความจริงใน log prod (รวมคำพิมพ์ผิดที่เจอจริง "ปัณชี" / "แอบ" / "พรุ้งนี้")
     *
     * @return string|null ชื่อเจตนา หรือ null ถ้าจับไม่ได้ (ปล่อยให้ AI ตีความ)
     */
    public function classifyPendingPaymentListening(string $message): ?string
    {
        $text = $this->normalizeUserInput($message);

        // ข้อความยาวมาก = เล่าเรื่อง ไม่ใช่คำสั้น ๆ ที่ตีความแบบตายตัวได้ปลอดภัย
        if (mb_strlen($text) > 160) {
            return null;
        }

        $noSpace = str_replace(' ', '', $text);

        // 💸 แจ้งว่าโอนแล้ว — ปกติถูกเส้นตรวจยอด/สลิปดักไปก่อนถึงตัวฟัง
        //   แต่บิล 99 ตกมาถึงนี่ได้ (ตรวจสลิปแล้วไม่คืนคำตอบ) → ต้องขอสลิป ไม่ใช่ "ส่งต่อแอดมิน"
        if ($this->isPaymentClaimRequest($message, true)) {
            return 'claim';
        }

        // 🔒 ขอไม่ให้ยกเลิก ("ไม่ยกเลิกค่ะ" / พิมพ์ผิดจริง "ไม่ยกเลือกค่ะ")
        if ($this->pendingListenOnlyNegatedCancel($text)) {
            return 'keep_bill';
        }

        if ($this->pendingListenLooksLikeLater($noSpace)) {
            return 'later';
        }

        // 🏦 ไม่มีบัญชี / แอปธนาคาร / พร้อมเพย์ — ต้องมีคำปฏิเสธติดกันเท่านั้น
        //   ("มีบัญชีออมสิน" ต้องไม่ติด · "ไม่มีเงินในบัญชี" = เรื่องเงิน ไม่ใช่ไม่มีบัญชี)
        if (preg_match(
            '/(ไม่มี|ไม่ได้ใช้|ไม่ได้ทำ|ไม่ทำ|ไม่เล่น|ไม่ได้เปิด|ไม่เคยมี|ไม่ได้มี)'
            .'([บป]\x{0E31}?[ญณน]ชี|บช|แอ[ปพบ]|แอ๊ป|app|แบงค์|แบงก์|แบ้งค์|แบ้ง|ธนาคาร|พร้อมเ[พฟ]|โมบาย|mobilebanking|เคพลัส|kplus)/u',
            $noSpace
        )) {
            return 'no_bank';
        }

        // 👨‍👩‍👧 ให้คนอื่นโอนแทน (อนาคต — ประโยค "พ่อจ่ายให้แล้ว" ถูกตัวจับแจ้งโอนดักไปก่อนถึงตรงนี้แล้ว)
        //   ไม่ใส่คำพยางค์เดียวที่เป็นส่วนของคำอื่นได้ง่าย (ตา/อา) — [[rule_thai_text_matching_traps]]
        //   ⚠️ "แม่" ต้องไม่ใช่ "แม่หมอ" ("ให้แม่หมอดูก่อนจ่าย" = ขอดูก่อนจ่าย ไม่ใช่ให้แม่โอนแทน)
        //   ⚠️ ไม่ใส่ "ลูก" — ลูกค้าเรียกตัวเองว่า "ลูก" กับแม่หมอ ("จะให้ลูกโอนยังไง" = ฉันจะโอนยังไง)
        if (preg_match(
            '/ให้(ญาติ|ญาต|แฟน|หลาน|เพื่อน|พี่|น้อง|แม่(?!หมอ)|พ่อ|สามี|ภรรยา|เมีย|ผัว|เขา|เค้า|คนอื่น|คนรู้จัก|ป้า|ลุง|น้า|ยาย).{0,12}?(โอน|จ่าย|ชำระ)/u',
            $noSpace
        ) || preg_match('/(ฝากโอน|ฝากจ่าย|โอนแทน|จ่ายแทน)/u', $noSpace)) {
            return 'third_party';
        }

        // 💸 ติดขัดเรื่องเงิน — ⚠️ ห้ามใส่ "ช็อต" (ชนคำว่า "สกรีนช็อต" = คนกำลังจะส่งสลิป)
        foreach ([
            'ไม่มีเงิน', 'เงินไม่พอ', 'เงินไม่มี', 'เงินหมด', 'ไม่มีตัง', 'ตังไม่พอ', 'ตังหมด', 'ตังค์หมด',
            'เดือดร้อน', 'ขัดสน', 'ชักหน้าไม่ถึงหลัง', 'ยืมเงิน', 'ยืมเพื่อน',
            'รอเงินเข้า', 'รอเงินเดือน', 'เงินยังไม่เข้า', 'ยังไม่มีเงิน', 'ติดขัดเรื่องเงิน',
            'ปัญหาเรื่องเงิน', 'ปัญหาการเงิน',
        ] as $kw) {
            if (str_contains($noSpace, $kw)) {
                return 'no_money';
            }
        }

        if ($this->pendingListenLooksLikePaymentProblem($noSpace)) {
            return 'problem';
        }

        // ⏳ ขอเวลา — ⚠️ "เดี๋ยวนี้" แปลว่า "ตอนนี้" ไม่ใช่ขอเวลา ("เดี๋ยวนี้เดือดร้อน...")
        $waitText = str_replace('เดี๋ยวนี้', '', $noSpace);
        foreach ([
            'สักพัก', 'สักครู่', 'แป๊บ', 'แปบ', 'แปป', 'แป๊ป', 'รอก่อน', 'ขอเวลา', 'เดี๋ยว', 'เด๋ว',
            'กำลังโอน', 'กำลังจะโอน', 'กำลังหา', 'กำลังถาม', 'กำลังดู', 'กำลังไป', 'กำลังทำ',
        ] as $kw) {
            if (str_contains($waitText, $kw)) {
                return 'wait';
            }
        }

        $short = mb_strlen($noSpace) <= 20;

        if (preg_match('/(ขอโทษ|ขอโทด|ขอโทส|โทษที|sorry)/u', $noSpace)) {
            return 'apology';
        }

        if ($short && preg_match('/^(สวัสดี|สวัดดี|สวัดี|หวัดดี|ดีจ้า|hello|hi)/u', $noSpace)) {
            return 'greeting';
        }

        if ($short && preg_match('/(ขอบคุณ|ขอบคุน|ขอบใจ|ขอบพระคุณ|thank)/u', $noSpace)) {
            return 'thanks';
        }

        // มีเครื่องหมายคำถาม = งง/ถามอยู่ ("?" / "ค่ะ?") → ห้ามนับเป็นคำรับทราบ (จะโดนเงียบใส่)
        if (! preg_match('/[?？]/u', $message) && $this->pendingListenIsPureAck($text)) {
            return 'ack';
        }

        return null;
    }

    /**
     * บอกว่า "มีปัญหา/ติดขัด" เรื่องการโอน
     *
     * รวมคำพิมพ์ผิดจริง "มีป๊ณหา" (ป + วรรณยุกต์/ไม้หันอากาศ + ญ/ณ/น + หา)
     * ⚠️ "ไม่มีปัญหา" / "ไม่ได้มีปัญหาอะไร" = ไม่มีปัญหา
     * ⚠️ "มีปัญหาเรื่องแฟน" = ปัญหาชีวิต (คำถามดูดวง) ไม่ใช่ปัญหาการโอน —
     *    ถ้าระบุ "เรื่อง…" แต่ไม่มีคำเกี่ยวกับการจ่ายเงิน ปล่อยให้ AI ตีความ
     */
    protected function pendingListenLooksLikePaymentProblem(string $noSpace): bool
    {
        $problemWord = '(มี|ติด|เจอ)ป[\x{0E31}\x{0E48}-\x{0E4B}]?[ญณน]หา';
        $hasProblem = (bool) preg_match('/'.$problemWord.'/u', $noSpace)
            && ! preg_match('/ไม่.{0,4}'.$problemWord.'/u', $noSpace);

        if (! $hasProblem) {
            foreach (['ติดขัด', 'ติดขะด', 'ขัดข้อง', 'มีอุปสรรค'] as $kw) {
                if (str_contains($noSpace, $kw)) {
                    $hasProblem = true;
                    break;
                }
            }
        }

        $paymentContext = (bool) preg_match(
            '/(โอน|จ่าย|ชำระ|บัญชี|แอป|แอพ|qr|คิวอาร์|สแกน|แสกน|ธนาคาร|แบงค์|แบงก์|พร้อมเ[พฟ]|สลิป|บิล|ค่าครู|เงิน)/u',
            $noSpace
        );

        if ($hasProblem) {
            return $paymentContext || ! str_contains($noSpace, 'เรื่อง');
        }

        // อาการที่เป็นเรื่องการโอนในตัวอยู่แล้ว
        foreach (['โอนไม่ผ่าน', 'โอนไม่เข้า', 'สแกนไม่ได้', 'แสกนไม่ได้', 'สแกนไม่ติด', 'แสกนไม่ติด', 'สแกนไม่ขึ้น'] as $kw) {
            if (str_contains($noSpace, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * "ยกเลิก" ทุกคำในข้อความติดคำปฏิเสธ = ลูกค้าขอให้ *ไม่* ยกเลิก (ใช้ร่วมกับ isCancelRequest)
     *
     * ⚠️ "ทำไม(ยัง)ไม่ยกเลิกให้" = ต่อว่าที่ยังไม่ยกเลิก → เป็นคำสั่งยกเลิก
     * ⚠️ "กดยกเลิกแล้วแต่ยังไม่ยกเลิก" ยังมี "ยกเลิก" ที่ไม่ติดคำปฏิเสธ → ยกเลิก
     * ⚠️ "ไม่ ยกเลิก" (เว้นวรรค) = "ไม่(เอา) ยกเลิก" → ยกเลิก — นับเฉพาะที่เขียนติดกัน
     *
     * @param  string  $normalized  ข้อความที่ผ่าน normalizeUserInput แล้ว (ยังมีช่องว่าง)
     */
    public function pendingListenOnlyNegatedCancel(string $normalized): bool
    {
        if (preg_match('/(ทำไม|ทำมัย|ไหนว่า|บอกให้)/u', $normalized)) {
            return false;
        }

        $cancel = 'ยกเล[\x{0E34}\x{0E37}]อ?ก';
        $total = (int) preg_match_all('/'.$cancel.'/u', $normalized);
        if ($total === 0) {
            return false;
        }

        return (int) preg_match_all('/(?:ไม่ต้อง|ไม่ได้|ไม่|อย่า)'.$cancel.'/u', $normalized) === $total;
    }

    /**
     * "จะโอน/ดูทีหลัง" — พรุ่งนี้/วันหลัง ต้องมาคู่กับคำนัดหรือคำจ่ายเงิน
     *
     * กันคำถามดูดวงที่มีคำว่าพรุ่งนี้ ("พรุ่งนี้จะได้งานไหม" ต้องไม่ติด)
     * แต่ "พรุ่งนี้โอนได้ไหม" ติด — ถามเรื่องจ่ายเงินจริง คำตอบที่ถูกคือบอกเวลาบิลหมดอายุ
     */
    protected function pendingListenLooksLikeLater(string $noSpace): bool
    {
        foreach (['ยังไม่ต้องเปิด', 'ไม่ต้องเปิดวันนี้', 'ไว้วันหลัง', 'วันหลังค่อย', 'โอนทีหลัง', 'จ่ายทีหลัง', 'ค่อยโอนทีหลัง'] as $kw) {
            if (str_contains($noSpace, $kw)) {
                return true;
            }
        }

        // รวมคำพิมพ์ผิดของ "พรุ่งนี้" ที่เจอจริง (พรุ้งนี้ / พรุ่งนี / พุ่งนี้) ให้เป็นคำเดียวก่อนเทียบ
        $t = preg_replace('/(พร[ุู][่้]?งน[ี้ิ]้?|พุ่งนี้)/u', 'พรุ่งนี้', $noSpace) ?? $noSpace;
        if (! preg_match('/(พรุ่งนี้|มะรืน|วันหลัง)/u', $t)) {
            return false;
        }

        // คำถามดูดวงเรื่องพรุ่งนี้ ("พรุ่งนี้ต้องระวังอะไร" / "พรุ่งนี้สัมภาษณ์งาน" / "พรุ่งนี้วันเกิด")
        //   → ปล่อยให้ AI ตีความ (เดาผิด = บอกลูกค้าให้ปล่อยบิลหมดอายุทั้งที่เขากำลังถามดวง)
        if (preg_match('/(ดวง|งาน|แฟน|ความรัก|เนื้อคู่|สอบ|สัมภาษณ์|โชค|หวย|ระวัง|วันเกิด|เงินเข้า|ได้เงิน|สุขภาพ|ผ่าตัด|เดินทาง)/u', $t)) {
            return false;
        }

        // พูดถึงการจ่ายเงินตรงๆ ("พรุ่งนี้โอน" / "พรุ่งนี้โอนได้ไหม")
        if (preg_match('/(โอน|จ่าย|ชำระ)/u', $t)) {
            return true;
        }

        // วลีนัด/เลื่อนแบบเจาะจง — ⚠️ ห้ามใช้พยางค์เดี่ยว "ขอ/รอ/ต้อง" (ชน "ของ" / "หรอ" / "ต้องระวัง")
        return (bool) preg_match(
            '/(ค่อย|รอพรุ่งนี้|ไว้พรุ่งนี้|บอกพรุ่งนี้|เป็นพรุ่งนี้|เอาพรุ่งนี้|ดูพรุ่งนี้|เปิดพรุ่งนี้|ทักพรุ่งนี้|พรุ่งนี้ทัก|พรุ่งนี้มา|ขอนอน|นอนก่อน|ไม่ต้องรอ)/u',
            $t
        );
    }

    /**
     * คำรับทราบล้วน — "ค่ะ" / "ค่ะแม่" / "นะะ" / "ไช่ค่ะ" / "รู้และเข้าใจค่ะ" / สติกเกอร์อีโมจิล้วน
     *
     * @param  string  $normalized  ข้อความที่ผ่าน normalizeUserInput แล้ว
     */
    protected function pendingListenIsPureAck(string $normalized): bool
    {
        $core = preg_replace('/(แม่หมอ|แม่)$/u', '', $normalized) ?? $normalized;
        $core = $this->normalizeUserInput($core);
        // ยุบตัวอักษรซ้ำ ("นะะ" → "นะ" / "ค่ะะะ" → "ค่ะ") แล้วปอกคำลงท้ายอีกรอบ
        $core = preg_replace('/(.)\1+/u', '$1', $core) ?? $core;
        $core = $this->normalizeUserInput($core);
        $core = preg_replace('/[\sๆ.!?~]+/u', '', $core) ?? $core;

        if ($core === '') {
            return true;
        }

        return in_array($core, [
            'ได้', 'ได้เลย', 'โอเค', 'ok', 'okay', 'เค', 'k', 'ใช่', 'ไช่', 'ตกลง',
            'เข้าใจ', 'เข้าใจแล้ว', 'รู้แล้ว', 'รู้และเข้าใจ', 'รับทราบ', 'ทราบ', 'ทราบแล้ว',
            'อืม', 'อือ', 'อ่อ', 'อ๋อ', 'อ้อ', 'จ้า', 'จ้ะ', 'ค่า', 'คร้า', 'ครับผม', 'ค่ะ', 'คะ',
        ], true);
    }

    /**
     * ตอบลูกค้าระหว่างรอโอน — จุดเดียวที่บิล 39 + บิล 99 ใช้ร่วมกัน
     *
     * @param  array{action: string, pay_amount: string, expires_at: ?Carbon, remaining_minutes: int, footer: string, partial_line?: string}  $ctx
     * @return array response ของ handler (action เดิม หรือ silent_skip)
     */
    protected function respondWhilePendingPayment(FortuneReading $reading, string $messageText, array $ctx): array
    {
        $intent = $this->classifyPendingPaymentListening($messageText);
        $userId = (string) ($reading->facebook_user_id ?: $reading->platform_user_id);

        // ⏰ บิลเลยเวลาแล้วแต่ตัวเก็บกวาดยังไม่ปิด (บิล 99 — บิล 39 ถูกปิดก่อนถึงตรงนี้)
        //   ห้ามบอก "ไม่ต้องรีบ รอได้" กับบิลที่หมดอายุ → บอกตรงๆ + ทางไปต่อ
        $expiresAt = $ctx['expires_at'] ?? null;
        if ($expiresAt instanceof \DateTimeInterface && Carbon::instance($expiresAt)->lte(now())) {
            return $this->pendingListenDeterministicReply($reading, $messageText, 'expired', $ctx);
        }

        // ก. คำรับทราบล้วน — ไม่เผา AI (ยกเว้นบอทเพิ่งถามคำถาม "ค่ะ" คือคำตอบ)
        if (in_array($intent, ['ack', 'thanks'], true)
            && ! ($userId !== '' && $this->botAskedQuestionRecently($userId))) {
            return $this->pendingListenDeterministicReply($reading, $messageText, $intent, $ctx);
        }

        // ข. AI ฟังก่อนเสมอ
        $ai = $this->pendingListenAiReply($reading, $messageText, $intent, (int) ($ctx['remaining_minutes'] ?? 0));
        $aiText = trim((string) ($ai['text'] ?? ''));

        // 🩹 (2026-09-15 จับผี) AI ใช้เวลาหลายวินาที — เงินอาจเข้าระหว่างนั้น
        //   ห้ามส่ง "บิลยังรออยู่" ตามหลังข้อความยืนยันการจ่าย ([[feedback_never_interrupt_payment_to_prediction_flow]])
        if (! $this->pendingListenStillPending($reading)) {
            Log::info('Fortune: รอโอน — เงินเข้าระหว่าง AI คิด ไม่ตอบทับเส้นจ่ายเงิน', [
                'reading_id' => $reading->id,
                'text_preview' => mb_substr($messageText, 0, 60),
            ]);

            return [
                'action' => 'silent_skip',
                'message' => null,
                'reading' => $reading,
            ];
        }

        if ($aiText !== '') {
            if (empty($ai['history_saved']) && $userId !== '') {
                $this->pendingListenRecordTurn($userId, 'user', $messageText);
                $this->pendingListenRecordTurn($userId, 'assistant', $aiText);
            }

            $state = $this->pendingListenState($reading);
            $footer = $this->pendingListenFooterIfDue($reading, $state, $ctx, true);
            $message = $aiText.$footer['text'];
            $this->pendingListenMarkSpoke($reading, $state, $intent ?? 'ai', $footer['footer_shown']);

            Log::info('Fortune: รอโอน — AI ฟังแล้วตอบ', [
                'reading_id' => $reading->id,
                'intent' => $intent,
                'text_preview' => mb_substr($messageText, 0, 60),
            ]);

            return [
                'action' => $ctx['action'],
                'message' => $message,
                'reading' => $reading,
            ];
        }

        // ค. AI ไม่ได้ผล → ตอบตามเจตนาที่จับได้
        return $this->pendingListenDeterministicReply($reading, $messageText, $intent ?? 'unknown', $ctx);
    }

    /**
     * ตอบแบบตายตัวตามเจตนา — กันพูดเรื่องเดิมซ้ำใน 10 นาที (ซ้ำ = เงียบ)
     */
    protected function pendingListenDeterministicReply(FortuneReading $reading, string $messageText, string $intent, array $ctx): array
    {
        $state = $this->pendingListenState($reading);
        $userId = (string) ($reading->facebook_user_id ?: $reading->platform_user_id);
        $window = $this->pendingListenWindowMinutes();

        // ข้อความที่ AI ตอบไม่ได้และระบบจับเจตนาไม่ได้ → ส่งต่อแอดมิน (รวมรอบที่เงียบ)
        //   เพดาน 3 ข้อความ / 10 นาที / บิล — กันหน้า "คำถามที่ AI ตอบไม่ได้" ท่วมตอน AI ล่มแล้วโดนพิมพ์รัว
        if ($intent === 'unknown' && $this->pendingListenAdminQuotaLeft($reading)) {
            $this->pendingListenNotifyAdmin($reading, $userId, $messageText);
        }

        $silent = in_array($intent, ['ack', 'thanks'], true)
            ? $this->pendingListenWithin($this->pendingListenLastBotSpokeAt($reading, $state), $window)
            : $this->pendingListenWithin($state['intents'][$intent] ?? null, $window);

        if ($silent) {
            Log::info('Fortune: รอโอน — เรื่องเดิมเพิ่งตอบไป เงียบไว้', [
                'reading_id' => $reading->id,
                'intent' => $intent,
                'text_preview' => mb_substr($messageText, 0, 60),
            ]);

            return [
                'action' => 'silent_skip',
                'message' => null,
                'reading' => $reading,
            ];
        }

        $reply = $this->pendingListenReplyText($intent, $ctx);
        if ($intent === 'no_bank' && $userId !== '') {
            $reply .= $this->pendingListenCardHint($userId);
        }
        // เจตนาที่ลูกค้าจ่ายตอนนี้ไม่ได้ / แค่ตอบรับ / บิลหมดอายุ → ไม่แนบยอดบิลต่อท้าย (ไม่ทวง)
        $withFooter = ! in_array($intent, ['ack', 'thanks', 'apology', 'later', 'no_money', 'wait', 'keep_bill', 'expired'], true);
        $footer = $this->pendingListenFooterIfDue($reading, $state, $ctx, $withFooter);
        $message = $reply.$footer['text'];

        if ($userId !== '' && ! in_array($intent, ['ack', 'thanks'], true)) {
            $this->pendingListenRecordTurn($userId, 'user', $messageText);
            $this->pendingListenRecordTurn($userId, 'assistant', $reply);
        }

        $this->pendingListenMarkSpoke($reading, $state, $intent, $footer['footer_shown']);

        Log::info('Fortune: รอโอน — ตอบตามเจตนา (ไม่ใช้กล่องทวงเงิน)', [
            'reading_id' => $reading->id,
            'intent' => $intent,
            'text_preview' => mb_substr($messageText, 0, 60),
        ]);

        return [
            'action' => $ctx['action'],
            'message' => $message,
            'reading' => $reading,
        ];
    }

    /**
     * ข้อความตายตัวของแต่ละเจตนา — เรียกลูกค้าว่า "ลูก" แม่หมอเป็นผู้หญิง (ค่ะ/นะคะ)
     *
     * ❌ ห้ามเร่ง/ทวงในเจตนาที่ลูกค้าจ่ายตอนนี้ไม่ได้ ([[rule_hardship_is_not_a_buy_signal]])
     * ❌ ห้ามแต่งวิธีจ่ายที่ไม่มีจริง — ญาติโอนแทนยังเป็นพร้อมเพย์/บัญชีเดิม ระบบจับคู่ด้วยทศนิยม
     */
    public function pendingListenReplyText(string $intent, array $ctx): string
    {
        $amount = (string) ($ctx['pay_amount'] ?? '');
        $until = $this->pendingListenExpiryLabel($ctx['expires_at'] ?? null);
        $untilPart = $until !== '' ? "ถึง {$until}" : 'อยู่';

        return match ($intent) {
            'expired' => '⏰ บิลนี้หมดเวลาชำระแล้วนะคะ 🙏 ถ้าเพิ่งโอนไป ส่งรูปสลิปมาในแชทนี้ได้เลย แม่หมอเช็คให้ค่ะ '
                ."— ถ้ายังไม่ได้โอน พร้อมเมื่อไหร่พิมพ์ 'ดูดวง' แม่หมอออกบิลใหม่ให้นะคะ",
            'claim' => 'ขอบคุณค่ะ 🙏 ถ้าโอนแล้ว ส่งรูปสลิปมาในแชทนี้ได้เลยนะคะ แม่หมอเช็คให้ทันที '
                .'พอยอดเข้าระบบแม่หมอเปิดไพ่ให้เลยค่ะ',
            'keep_bill' => "ได้ค่ะ แม่หมอยังเปิดบิลไว้ให้นะคะ 🙏 รอได้{$untilPart} ค่ะ",
            'later' => 'ได้ค่ะ ไม่เป็นไรเลยนะคะ 🙏 '
                .($until !== '' ? "แต่บิลนี้จะหมดอายุ {$until} นะคะ " : '')
                ."ถ้าพร้อมหลังจากนั้น ทักมาพิมพ์ 'ดูดวง' แม่หมอออกบิลใหม่ให้ได้เลยค่ะ",
            'no_bank' => 'ไม่มีบัญชีหรือแอปธนาคารก็ไม่เป็นไรนะคะ 🙏 ให้ญาติหรือคนใกล้ตัวโอนแทนได้เลย '
                ."ขอแค่ยอด ฿{$amount} ตรงทศนิยม ระบบจับคู่บิลให้เอง — โอนแล้วส่งรูปสลิปมาในแชทนี้ได้เลยค่ะ",
            'third_party' => 'ได้เลยค่ะ ใครโอนแทนก็ได้นะคะ 🙏 '
                ."ขอแค่ยอด ฿{$amount} ตรงทศนิยม ระบบจะจับคู่บิลให้เอง ถ้าโอนแล้วส่งรูปสลิปมาในแชทนี้ แม่หมอเช็คให้ไวขึ้นค่ะ",
            'no_money' => 'ไม่เป็นไรเลยนะคะ 🙏 เรื่องเงินไม่ต้องฝืนเลยค่ะ '
                .($until !== '' ? "บิลนี้เปิดไว้ถึง {$until} ถ้าไม่ทันก็ไม่เป็นไร " : '')
                .'พร้อมเมื่อไหร่ค่อยทักแม่หมอมาใหม่ได้เลยนะคะ',
            'problem' => 'ติดตรงไหนคะลูก เล่าให้แม่หมอฟังได้เลยนะคะ 🙏 '
                .'เช่น ไม่มีบัญชี/แอปธนาคาร · สแกน QR ไม่ได้ · อยู่ต่างประเทศ — แม่หมอช่วยหาทางให้ค่ะ',
            'wait' => "ได้เลยค่ะ ไม่ต้องรีบนะคะ 🙏 บิลนี้เปิดรอไว้{$untilPart} โอนเสร็จแม่หมอรู้เองแล้วเปิดไพ่ให้ทันทีค่ะ",
            'apology' => 'ไม่เป็นไรเลยค่ะลูก 🙏 ค่อยเป็นค่อยไปนะคะ มีอะไรติดขัดบอกแม่หมอได้เลยค่ะ',
            'greeting' => 'สวัสดีค่ะลูก 🙏 แม่หมอรออยู่นะคะ มีอะไรติดขัดเรื่องการโอนบอกแม่หมอได้เลยค่ะ',
            'thanks' => 'ยินดีค่ะลูก 🙏 โอนเสร็จแม่หมอรู้เองอัตโนมัติ แล้วเปิดไพ่ให้ทันทีนะคะ',
            'ack' => 'ค่ะ 🙏 แม่หมอรออยู่นะคะ โอนเสร็จแม่หมอรู้เองอัตโนมัติ แล้วเปิดไพ่ให้ทันทีค่ะ',
            default => 'แม่หมออ่านที่ลูกพิมพ์มาแล้วนะคะ 🙏 เรื่องนี้แม่หมอส่งต่อให้แอดมินช่วยดูอีกทางด้วยค่ะ '
                .'ระหว่างนี้ติดเรื่องการโอนตรงไหน เล่าเพิ่มได้เลยนะคะ',
        };
    }

    /**
     * คำใบ้ให้ AI ว่าระบบจับเจตนาอะไรได้ — ใส่ในพรอมต์ของ buildPendingPaymentNudge
     */
    protected function pendingListenIntentHint(?string $intent): string
    {
        return match ($intent) {
            'claim' => 'ลูกค้าแจ้งว่าโอนแล้ว แต่ระบบยังไม่เห็นยอด → ขอบคุณ ขอให้ส่งรูปสลิปมาในแชท ห้ามพูดว่าได้รับเงินแล้ว',
            'keep_bill' => 'ลูกค้าบอกว่าไม่ยกเลิกบิล → รับทราบสั้นๆ ว่าบิลยังเปิดอยู่',
            'later' => 'ลูกค้าบอกว่าจะโอน/ดูทีหลัง (เช่นพรุ่งนี้) → รับทราบ ไม่เร่ง บอกตรงๆ ว่าบิลนี้หมดอายุเวลาไหน ถ้าเลยเวลาให้ทักมาพิมพ์ "ดูดวง" ออกบิลใหม่ได้',
            'no_bank' => 'ลูกค้าบอกว่าไม่มีบัญชี/แอปธนาคาร/พร้อมเพย์ → บอกว่าไม่เป็นไร ให้ญาติหรือคนใกล้ตัวโอนแทนได้ ขอแค่ยอดตรงทศนิยม โอนแล้วส่งรูปสลิปมาในแชทได้',
            'third_party' => 'ลูกค้าจะให้คนอื่นโอนแทน → ตอบว่าได้เลย ใครโอนก็ได้ ขอแค่ยอดตรงทศนิยม โอนแล้วส่งรูปสลิปมาในแชทได้',
            'no_money' => 'ลูกค้าติดขัดเรื่องเงิน → เห็นใจ ห้ามชวน/เร่งให้โอนเด็ดขาด บอกว่าไม่ต้องฝืน พร้อมเมื่อไหร่ค่อยกลับมา',
            'problem' => 'ลูกค้าบอกว่ามีปัญหาแต่ยังไม่ได้บอกว่าอะไร → ถามสั้นๆ ว่าติดตรงไหน',
            'wait' => 'ลูกค้าขอเวลาสักครู่ → รับทราบสั้นๆ ไม่ต้องรีบ',
            'apology' => 'ลูกค้าขอโทษ → บอกว่าไม่เป็นไร',
            'greeting' => 'ลูกค้าทักทาย → ทักกลับสั้นๆ',
            'thanks', 'ack' => 'ลูกค้าตอบรับสั้นๆ (บอทเพิ่งถามคำถามไป) → ตีความตามบทสนทนาล่าสุด',
            default => '',
        };
    }

    /**
     * ทางเลือกจ่ายบัตร — บอกเฉพาะเมื่อเลนบัตรเปิดอยู่ และลูกค้าถูกจดว่าอยู่ต่างประเทศแล้วเท่านั้น
     * (คนไทยที่ไม่มีบัญชีมักไม่มีบัตรด้วย และเลนบัตรมีค่าบริการเพิ่ม — [[rule_foreign_customer_needs_card_lane_routing]])
     */
    protected function pendingListenCardHint(string $userId): string
    {
        try {
            if ($this->isKnownForeignCustomer($userId) && $this->isStripeForeignFallbackAvailable()) {
                return "\nถ้าอยู่ต่างประเทศ จ่ายด้วยบัตรได้นะคะ พิมพ์ 'จ่ายบัตร' ได้เลยค่ะ";
            }
        } catch (\Throwable $e) {
            // เช็คเลนบัตรไม่ได้ → ไม่เสนอ (ไม่แต่งทางจ่ายที่ไม่แน่ใจว่าใช้ได้)
        }

        return '';
    }

    /**
     * ป้ายเวลาบิลหมดอายุ เช่น "02:12 น." (ว่างถ้าไม่รู้)
     */
    protected function pendingListenExpiryLabel(mixed $expiresAt): string
    {
        if ($expiresAt instanceof \DateTimeInterface) {
            $at = Carbon::instance($expiresAt);
            // เลยเวลาแล้ว (บิลรอตัวเก็บกวาดปิด) → ห้ามบอก "เปิดรอไว้ถึง HH:MM" เป็นเวลาที่ผ่านไปแล้ว
            if ($at->lte(now())) {
                return '';
            }

            return $at->timezone(config('app.timezone'))->format('H:i').' น.';
        }

        return '';
    }

    /**
     * บรรทัดสรุปยอดบิล — แนบได้ไม่เกิน 1 ครั้ง / 10 นาที (นับกล่องบิลตอนออก QR ด้วย)
     * บิลที่โอนมาแล้วบางส่วนแนบบรรทัด "รับแล้ว/ขาดอีก" เสมอ (owner 2026-08-29 — ทุกกล่องต้องบอก)
     */
    /**
     * @return array{text: string, footer_shown: bool}
     */
    protected function pendingListenFooterIfDue(FortuneReading $reading, array $state, array $ctx, bool $allowed): array
    {
        $partial = trim((string) ($ctx['partial_line'] ?? ''));
        $footer = trim((string) ($ctx['footer'] ?? ''));

        $lastFooterAt = $this->pendingListenLatest([
            $state['footer_at'] ?? null,
            $this->pendingListenBillIssuedAt($reading),
        ]);
        $due = $allowed && $footer !== '' && ! $this->pendingListenWithin($lastFooterAt, $this->pendingListenWindowMinutes());

        $lines = array_values(array_filter([
            $partial,
            $due ? $footer : '',
        ], fn ($l) => $l !== ''));

        return [
            'text' => $lines === [] ? '' : "\n\n".implode("\n", $lines),
            'footer_shown' => $due,
        ];
    }

    /**
     * ครั้งล่าสุดที่บอทพูดกับลูกค้าในบิลนี้ (กล่องบิลตอนออก QR / ตัวฟัง / ตัวเตือนบิล / กล่องบัญชีเต็ม)
     */
    protected function pendingListenLastBotSpokeAt(FortuneReading $reading, array $state): ?string
    {
        return $this->pendingListenLatest([
            $state['spoke_at'] ?? null,
            $this->pendingListenBillIssuedAt($reading),
            $reading->getConversationState('bill_reminder_sent_at'),
            $reading->getConversationState('last_payment_box_at'),
        ]);
    }

    /**
     * เวลาออกบิล (= เวลาที่ลูกค้าเห็นกล่องบิล + QR ครั้งแรก)
     */
    protected function pendingListenBillIssuedAt(FortuneReading $reading): ?string
    {
        try {
            $upa = $reading->relationLoaded('uniquePaymentAmount')
                ? $reading->getRelation('uniquePaymentAmount')
                : ($reading->unique_payment_amount_id ? $reading->uniquePaymentAmount : null);

            return $upa?->created_at?->toIso8601String();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * สถานะกันพูดซ้ำของบิลนี้ ['intents' => [intent => iso], 'spoke_at' => iso, 'footer_at' => iso]
     */
    protected function pendingListenState(FortuneReading $reading): array
    {
        try {
            $state = Cache::get($this->pendingListenStateKey($reading));

            return is_array($state) ? $state : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function pendingListenMarkSpoke(FortuneReading $reading, array $state, string $intent, bool $footerShown): void
    {
        $now = now()->toIso8601String();
        $state['intents'] = is_array($state['intents'] ?? null) ? $state['intents'] : [];
        $state['intents'][$intent] = $now;
        $state['spoke_at'] = $now;
        if ($footerShown) {
            $state['footer_at'] = $now;
        }

        try {
            // บิลอายุ 3 ชม. (ตั้งได้) — เก็บเผื่อ 1 ชม.
            Cache::put(
                $this->pendingListenStateKey($reading),
                $state,
                now()->addMinutes(FortuneReading::billTimeoutMinutes() + 60)
            );
        } catch (\Throwable $e) {
            // Cache ล่ม = แย่สุดคือตอบซ้ำ 1 ครั้ง
        }
    }

    protected function pendingListenStateKey(FortuneReading $reading): string
    {
        return 'fortune:pending_listen:'.(int) $reading->id;
    }

    /**
     * ยังส่งต่อแอดมินได้อีกไหม (≤ 3 ข้อความ / 10 นาที / บิล) — เรียกแล้วนับทันที
     */
    protected function pendingListenAdminQuotaLeft(FortuneReading $reading): bool
    {
        $key = 'fortune:pending_listen_admin:'.(int) $reading->id;

        try {
            $window = $this->pendingListenWindowMinutes();
            $recent = array_values(array_filter(
                (array) Cache::get($key, []),
                fn ($iso) => is_string($iso) && $this->pendingListenWithin($iso, $window)
            ));
            if (count($recent) >= 3) {
                return false;
            }

            $recent[] = now()->toIso8601String();
            Cache::put($key, $recent, now()->addMinutes($window + 1));
        } catch (\Throwable $e) {
            // Cache ล่ม → ส่งต่อตามปกติ
        }

        return true;
    }

    /**
     * เวลา (iso) อยู่ในหน้าต่าง N นาทีล่าสุดหรือไม่
     */
    protected function pendingListenWithin(?string $iso, int $minutes): bool
    {
        if (empty($iso)) {
            return false;
        }

        try {
            return Carbon::parse($iso)->gt(now()->subMinutes($minutes));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * เวลาล่าสุดจากหลายแหล่ง (ข้ามค่าว่าง/อ่านไม่ออก)
     *
     * @param  array<int, mixed>  $values
     */
    protected function pendingListenLatest(array $values): ?string
    {
        $latest = null;
        foreach ($values as $v) {
            if (empty($v) || ! is_string($v)) {
                continue;
            }
            try {
                $t = Carbon::parse($v);
            } catch (\Throwable $e) {
                continue;
            }
            if ($latest === null || $t->gt($latest)) {
                $latest = $t;
            }
        }

        return $latest?->toIso8601String();
    }

    /**
     * ให้ AI ฟังแล้วตอบ — Bill Psychology (Pro) ก่อน แล้วค่อย chat AI
     *
     * @return array{text: string, history_saved: bool}
     */
    protected function pendingListenAiReply(FortuneReading $reading, string $messageText, ?string $intent, int $remainingMinutes): array
    {
        $userId = (string) ($reading->facebook_user_id ?: $reading->platform_user_id);
        if ($userId === '') {
            return ['text' => '', 'history_saved' => false];
        }

        // 1) Bill Psychology — พรอมต์ฟังก่อนอยู่แล้ว + บันทึกประวัติเอง (prod ปิดอยู่: sensitive_ai_mode=off)
        try {
            $platform = $reading->platform ?: FortuneRecipient::platformFromUserId($userId);
            $pro = $this->tryBillPsychologyResponse($platform, $userId, $messageText, $reading, $remainingMinutes);
            if (! empty($pro)) {
                return ['text' => (string) $pro, 'history_saved' => true];
            }
        } catch (\Throwable $e) {
            Log::warning('Fortune: Bill Psychology (รอโอน) ล้มเหลว', [
                'error' => $e->getMessage(),
                'reading_id' => $reading->id,
            ]);
        }

        // 2) chat AI — ส่งประวัติสั้นๆ ไปด้วย ให้รู้ว่าก่อนหน้านี้ลูกค้าเล่าอะไรไว้
        try {
            $history = $this->getConversationHistoryForAI($userId);
            $nudge = $this->buildPendingPaymentNudge($reading, $messageText, $remainingMinutes, $intent, $history);

            return ['text' => trim($nudge), 'history_saved' => false];
        } catch (\Throwable $e) {
            Log::warning('Fortune: AI ฟังลูกค้าระหว่างรอโอนล้มเหลว', [
                'error' => $e->getMessage(),
                'reading_id' => $reading->id,
            ]);

            return ['text' => '', 'history_saved' => false];
        }
    }

    /**
     * บิลยังรอโอนอยู่จริงไหม — อ่านสดจาก DB (อย่าเชื่อ $reading ที่โหลดไว้ก่อนเรียก AI)
     */
    protected function pendingListenStillPending(FortuneReading $reading): bool
    {
        try {
            $fresh = FortuneReading::query()
                ->select(['id', 'is_paid', 'conversation_status'])
                ->find($reading->id);
            if (! $fresh) {
                return false; // บิลถูกลบ → ไม่ตอบ
            }

            return ! $fresh->is_paid && in_array($fresh->conversation_status, [
                FortuneReading::STATUS_PENDING_PAYMENT,
                FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            ], true);
        } catch (\Throwable $e) {
            return true; // อ่านไม่ได้ → ตอบตามปกติ (พฤติกรรมเดิม)
        }
    }

    /**
     * บันทึกบทสนทนาลงประวัติ (ให้ AI รอบหน้าเห็น + ตั้งธง "บอทเพิ่งถาม" เมื่อลงท้ายด้วยคำถาม)
     */
    protected function pendingListenRecordTurn(string $userId, string $role, string $text): void
    {
        $this->saveConversationMessage($userId, $role, $text);
    }

    /**
     * ส่งต่อข้อความที่ตอบไม่ได้ให้แอดมิน (หน้า "คำถามที่ AI ตอบไม่ได้")
     */
    protected function pendingListenNotifyAdmin(FortuneReading $reading, string $userId, string $messageText): void
    {
        if ($userId === '') {
            return;
        }

        $this->saveQuestionForAdmin(
            $userId,
            '[รอโอน '.($reading->bill_reference ?? '#'.$reading->id).'] '.$messageText,
            'ai_failed',
            null,
            $reading->facebook_user_name
        );
    }
}
