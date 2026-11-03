<?php

namespace App\Services\Fortune;

use Carbon\CarbonInterface;

/**
 * 🌙 (2026-11-03) ชุดข้อความ "พาไปห้องแม่หมอ" สำหรับ FB web-only — ที่เดียวของถ้อยคำทั้งหมด (design หัวข้อ 5)
 *
 * เหตุผล: Facebook แบนเพจที่มีสลิปโอนเงินในแชท → ทุกการดูดวง/จ่ายเงินย้ายไปเว็บจันทรา
 * ข้อความในไฟล์นี้ต้อง "เนียน" — ชวนเหมือนแม่หมอเชิญเข้าห้อง **ห้ามพูดเรื่องเงิน**
 *
 * กติกาถ้อยคำ (มีเทสต์ lint บังคับ):
 *   - ห้ามมีคำว่า ฟรี บาท ฿ ค่าครู โอน สลิป QR บัญชี LINE/ไลน์ สายงาน รายได้ กระเป๋าเงิน
 *   - ห้ามขู่ว่า "FB จะแบน" และห้ามวาง URL ดิบในข้อความ (ลิงก์อยู่ในปุ่ม web_url เท่านั้น)
 *   - หัว button template ≤ 640 ตัวอักษร · ป้ายปุ่ม ≤ 20 ตัวอักษร
 *
 * ชื่อลูกค้า ({name}) ใส่เฉพาะเมื่อไม่ใช่ 'User'/'คุณ' และยาวไม่เกิน 20 ตัวอักษร
 * ถ้าไม่มีชื่อ บางประโยคใช้คำว่า "เจ้าชะตา" แทน บางประโยคตัดทิ้งเฉย ๆ (ดู NAME_FALLBACK)
 *
 * @example
 * $text = WebHandoffCopy::reply(WebHandoffCopy::KIND_PRICE, 'สมศรี');
 * $qr = WebHandoffCopy::inviteQuickReplies();
 */
final class WebHandoffCopy
{
    // ------------------------------------------------------------
    // payload ของปุ่ม (ฝั่ง webhook ต้องรู้จักทุกตัว — T6)
    // ------------------------------------------------------------

    /** ปุ่มหลัก "เข้าห้องแม่หมอ" (postback / quick reply) → ตอบด้วยกล่องลิงก์ใหม่ทุกครั้ง */
    public const PAYLOAD_WEB_ROOM = 'WEB_ROOM';

    /** ปุ่ม "เข้ายังไงคะ" → ส่งคำอธิบาย 2 บรรทัด + กล่องลิงก์ใหม่ */
    public const PAYLOAD_WEB_ROOM_HOWTO = 'WEB_ROOM_HOWTO';

    /** ปุ่มพักการชวน 7 วัน (มีอยู่แล้วในระบบ) */
    public const PAYLOAD_SNOOZE = 'INVITE_SNOOZE_7D';

    /** ปุ่มไม่ต้องส่งอีก (มีอยู่แล้วในระบบ) */
    public const PAYLOAD_OPTOUT = 'INVITE_OPTOUT';

    // ------------------------------------------------------------
    // ป้ายปุ่ม (≤ 20 ตัวอักษร)
    // ------------------------------------------------------------

    public const LABEL_WEB_ROOM = '🌙 เข้าห้องแม่หมอ';

    public const LABEL_WEB_ROOM_ALT = '🕯️ ไปนั่งคุยกัน';

    public const LABEL_HOWTO = '❓ เข้ายังไงคะ';

    public const LABEL_SNOOZE = '🔕 พัก 7 วัน';

    public const LABEL_OPTOUT = '🚫 ไม่ต้องส่งอีก';

    // ------------------------------------------------------------
    // ชนิดข้อความตอบในแชท (หัวกล่อง button template)
    // ------------------------------------------------------------

    /** ลูกค้าขอดูดวง/ขอแพ็กเกจ */
    public const KIND_READING = 'reading';

    /** ลูกค้าถามราคา/วิธีจ่าย */
    public const KIND_PRICE = 'price';

    /** ลูกค้าบอกว่าทำไม่เป็น/กดไม่ได้ (กล่องมีปุ่ม "เข้ายังไงคะ" เพิ่ม) */
    public const KIND_CANNOT = 'cannot';

    /** ลูกค้ากดปุ่ม WEB_ROOM เอง (ข้อความกลาง ๆ) */
    public const KIND_ROOM = 'room';

    /** กดปุ่ม "เข้ายังไงคะ" — คำอธิบาย 2 บรรทัด */
    public const KIND_HOWTO = 'howto';

    /** สลิประดับ C — เก็บหลักฐานแล้ว ส่งเรื่องให้ทีม */
    public const KIND_SLIP_FORWARDED = 'slip_forwarded';

    /** CTA ปิดท้ายงานที่จ่ายแล้ว (แทน 'พิมพ์ ดูดวง') */
    public const KIND_PAID_CTA = 'paid_cta';

    /** 🔐 (รีวิว) บอกว่าเข้าไม่ได้ซ้ำภายใน 24 ชม. — แจ้งทีมให้ช่วยแล้ว (ไม่ส่งกล่องเดิมวนไม่จบ) */
    public const KIND_CANNOT_ESCALATED = 'cannot_escalated';

    public const KINDS = [
        self::KIND_READING,
        self::KIND_PRICE,
        self::KIND_CANNOT,
        self::KIND_ROOM,
        self::KIND_HOWTO,
        self::KIND_SLIP_FORWARDED,
        self::KIND_PAID_CTA,
        self::KIND_CANNOT_ESCALATED,
    ];

    /** ข้อความตอบในแชทตามชนิด (หัวกล่อง) */
    public const REPLIES = [
        self::KIND_READING => 'เรื่องนี้แม่หมออยากเปิดไพ่ให้ละเอียดค่ะ 🌙 ห้องแม่หมอเปิดรอไว้แล้ว แตะปุ่มด้านล่างได้เลยนะคะ',
        self::KIND_PRICE => 'รายละเอียดแพ็กเกจทั้งหมดแม่หมอวางไว้ในห้องแล้วค่ะ เข้าไปดูได้สบาย ๆ เลยนะคะ 🌙',
        self::KIND_CANNOT => 'ไม่ยากเลยค่ะ{name} แตะปุ่มด้านล่าง ระบบพาเข้าห้องให้เองเลย ถ้าติดตรงไหน พิมพ์บอกแม่หมอตรงนี้ได้ เดี๋ยวช่วยค่ะ',
        self::KIND_ROOM => 'อยากคุยกับแม่หมอ หรือเปิดไพ่ฟรี แตะปุ่มด้านล่างเข้าห้องแม่หมอได้เลยค่ะ 🌙 ระบบพาเข้าให้เอง ไม่ต้องสมัครอะไรเลยนะคะ',
        self::KIND_HOWTO => "1) แตะปุ่ม 🌙 เข้าห้องแม่หมอ ด้านล่างนี้\n2) รอแป๊บเดียว ระบบพาเข้าห้องให้เอง ไม่ต้องตั้งรหัส ไม่ต้องกรอกอะไรเลยค่ะ",
        self::KIND_SLIP_FORWARDED => 'แม่หมอส่งเรื่องให้ทีมดูให้แล้วค่ะ 🙏 ระหว่างนี้ห้องแม่หมอเปิดรอไว้นะคะ',
        self::KIND_PAID_CTA => 'ถ้าอยากให้แม่หมอเปิดไพ่เรื่องอื่นอีก แม่หมอรออยู่ในห้องนะคะ 🌙',
        self::KIND_CANNOT_ESCALATED => 'แม่หมอแจ้งทีมให้ช่วยดูแล้วนะคะ 🙏 เดี๋ยวมีคนทักกลับมาช่วยตรงนี้ค่ะ ระหว่างนี้ลองแตะปุ่มด้านล่างอีกครั้งได้นะคะ',
    ];

    /** สร้างลิงก์ไม่ได้ (fail-closed) — ส่งพร้อม quick reply WEB_ROOM และแจ้งแอดมิน */
    public const FAIL_CLOSED = 'ห้องแม่หมอกำลังจัดเตรียมอยู่สักครู่นะคะ 🙏 อีกเดี๋ยวลองแตะ 🌙 เข้าห้องแม่หมอ อีกครั้งได้เลยค่ะ';

    /** รับสลิประดับ B (บิลค้างก่อน T0) — สั้น ไม่ขาย */
    public const SLIP_RECEIVED = 'แม่หมอได้รับแล้วนะคะ กำลังเช็คให้อยู่ค่ะ 🙏';

    /**
     * 🔐 (รีวิว) ลูกค้าที่เงินถูกพักรอตรวจ (partial hold) ถามสถานะ — ห้ามมีคำเรื่องเงิน
     * (ข้อความเดิม "ยอดที่โอนมา…" โดนด่านเงินชั้นส่ง (T9) บล็อกทั้งข้อความ = ลูกค้าที่เพิ่งจ่ายเงินได้ความเงียบ)
     */
    public const HOLD = 'แม่หมอรับเรื่องไว้แล้วนะคะ ทีมกำลังตรวจให้อยู่ เดี๋ยวแจ้งกลับทางนี้ค่ะ 🙏';

    /** 🔐 (รีวิว) ลูกค้าตอบคำถามยืนยันยอด (fuzzy) ใต้ web-only — ไม่ตัดบิลเอง ส่งให้ทีมตรวจ */
    public const FUZZY_ANSWER_ACK = 'รับทราบค่ะ 🙏 แม่หมอให้ทีมช่วยตรวจให้อีกครั้งนะคะ เดี๋ยวแจ้งกลับทางนี้ค่ะ';

    /** ตอบใต้คอมเมนต์สาธารณะ — ห้ามชวน "ดูดวงในแชท" */
    public const COMMENT_REPLY = 'แม่หมอทักไปใน inbox แล้วนะคะ 🌙';

    // ------------------------------------------------------------
    // ข้อความชวน (DM ขาออก) — seed เป็น mode='web'
    // ------------------------------------------------------------

    /** ใช้ได้ทุกเวลา */
    public const WINDOW_ANY = 'any';

    /** 18:00-22:59 (เวลาไทย) */
    public const WINDOW_EVENING = 'evening';

    /** 05:00-09:59 (เวลาไทย) */
    public const WINDOW_MORNING = 'morning';

    /**
     * ข้อความชวน 9 แบบ
     *
     * - window: ช่วงเวลาที่ใช้ได้
     * - continuity: true = ใช้ได้หลัง deploy ความต่อเนื่องของแชท (T17/J2) แล้วเท่านั้น
     * - topic: null = ทุกเรื่อง / 'love' = เฉพาะเรื่องหัวใจ
     *
     * @var array<int, array{text:string, window:string, continuity:bool, topic:?string}>
     */
    public const INVITES = [
        1 => ['text' => '🌙 {name}คะ เรื่องที่อยากถาม แม่หมอขอคุยกันในห้องส่วนตัวนะคะ เงียบ ๆ สองคน คุยกันได้ยาว ๆ แม่หมอเปิดห้องรอไว้แล้วค่ะ', 'window' => self::WINDOW_ANY, 'continuity' => false, 'topic' => null],
        2 => ['text' => 'แม่หมอจัดห้องไว้ให้{name}แล้วนะคะ 🕯️ จุดเทียนรอไว้แล้ว เปิดไพ่ให้ได้เต็มที่ที่นั่นเลยค่ะ', 'window' => self::WINDOW_ANY, 'continuity' => false, 'topic' => null],
        3 => ['text' => 'เรื่องหัวใจแบบนี้ คุยกันสองคนดีกว่านะคะ{name} 💜 แม่หมอรออยู่ในห้องแล้วค่ะ', 'window' => self::WINDOW_ANY, 'continuity' => false, 'topic' => 'love'],
        4 => ['text' => 'ดวงช่วงนี้ของ{name}มีอะไรอยากบอกอยู่ค่ะ ✨ ขอเปิดไพ่ในห้องแม่หมอนะคะ คำทำนายเก็บไว้ให้ย้อนอ่านได้ทุกเมื่อค่ะ', 'window' => self::WINDOW_ANY, 'continuity' => false, 'topic' => null],
        5 => ['text' => 'คืนนี้แม่หมอเปิดห้องรับเจ้าชะตาอยู่ค่ะ 🕯️ ถ้าใจยังค้างเรื่องไหน เข้ามานั่งคุยกันได้นะคะ', 'window' => self::WINDOW_EVENING, 'continuity' => false, 'topic' => null],
        // 🔐 (รีวิว) เดิมสัญญา "ไพ่ประจำวันวางรอในห้อง" แต่ห้องแม่หมอไม่มีไพ่ประจำวัน — ดู RETIRED_INVITES
        6 => ['text' => 'อรุณสวัสดิ์ค่ะ{name} ☀️ เช้านี้ใจเป็นยังไงบ้างคะ แม่หมอเปิดห้องรอแล้ว แวะมาคุยกันได้นะคะ', 'window' => self::WINDOW_MORNING, 'continuity' => false, 'topic' => null],
        7 => ['text' => 'แตะทีเดียวก็เข้าห้องแม่หมอได้เลยค่ะ ไม่ต้องตั้งรหัส ไม่ต้องกรอกอะไรเลย 🌙', 'window' => self::WINDOW_ANY, 'continuity' => false, 'topic' => null],
        8 => ['text' => 'แม่หมอไม่ได้หายไปไหนนะคะ{name} แค่ย้ายมานั่งห้องที่สงบกว่า 🌙 เรื่องที่เราคุยกันไว้ แม่หมอเก็บไว้ให้ครบ เข้ามาคุยต่อได้เลยค่ะ', 'window' => self::WINDOW_ANY, 'continuity' => true, 'topic' => null],
        9 => ['text' => 'เมื่อกี้ที่{name}เล่ามา แม่หมอฟังอยู่นะคะ ขอไปนั่งคุยต่อในห้องแม่หมอได้ไหมคะ จะได้ดูให้ละเอียด ✨', 'window' => self::WINDOW_ANY, 'continuity' => true, 'topic' => null],
    ];

    /**
     * 🔐 (รีวิว) ถ้อยคำชวนที่เลิกใช้แล้ว — seeder ปิด (is_active=false) แถวที่ยังเป็นข้อความเดิมเป๊ะ
     * (seeder จับคู่ด้วยข้อความ: แก้ INVITES แล้วแถวเก่ายังเปิดอยู่ถ้าไม่ปิดตรงนี้)
     *
     * @var array<int, string>
     */
    public const RETIRED_INVITES = [
        'อรุณสวัสดิ์ค่ะ{name} ☀️ ไพ่ประจำวันของเจ้าชะตาวางรอในห้องแม่หมอแล้ว แวะมาเปิดดูได้นะคะ',
    ];

    /**
     * คำที่ใช้แทน {name} เมื่อไม่มีชื่อ — คีย์ = 'invite_{n}' หรือชนิดข้อความตอบ
     * ไม่อยู่ในรายการ = ใช้ 'เจ้าชะตา'
     */
    public const NAME_FALLBACK = [
        'invite_3' => '',
        'invite_6' => '',
        'invite_8' => '',
        self::KIND_CANNOT => '',
        self::KIND_CANNOT_ESCALATED => '',
    ];

    public const DEFAULT_NAME_FALLBACK = 'เจ้าชะตา';

    /** ชื่อยาวเกินนี้ไม่ใส่ (design หัวข้อ 4) */
    public const NAME_MAX_LENGTH = 20;

    /** หัว button template สูงสุด */
    public const BOX_TEXT_MAX = 640;

    /**
     * คำต้องห้ามในทุกถ้อยคำของไฟล์นี้ (ใช้ใน lint test และให้ T11 ใช้ตรวจ seeder ได้)
     */
    public const FORBIDDEN_REGEX = '/(โอน|สลิป|บัญชี|บาท|฿|ค่าครู|LINE|ไลน์|ฟรี|สายงาน|รายได้|QR|คิวอาร์|กระเป๋าเงิน|แบน)/iu';

    /**
     * คำต้องห้ามของ "คำตอบ" ที่ลูกค้าทักมาก่อน — เหมือน FORBIDDEN_REGEX แต่อนุญาต "ฟรี"
     * (เจ้าของสั่ง 2026-11-03: กล่องพาไปห้องบอกได้ว่าคุย/เปิดไพ่ฟรีบนเว็บ — "ฟรี" ยังห้ามใน DM ชวนที่บอทส่งเอง)
     */
    public const REPLY_FORBIDDEN_REGEX = '/(โอน|สลิป|บัญชี|บาท|฿|ค่าครู|LINE|ไลน์|สายงาน|รายได้|QR|คิวอาร์|กระเป๋าเงิน|แบน)/iu';

    /**
     * หัวข้อของข้อความลูกค้า → ใช้ติดป้าย intent_topic ของ handoff
     *
     * ลำดับสำคัญ: คุณไสยก่อน (เฉพาะทาง) แล้วความรัก (กัน "แต่งงาน" ไปตก "งาน")
     *
     * @var array<string, array<int,string>>
     */
    public const TOPIC_KEYWORDS = [
        'kunsai' => ['คุณไสย', 'ไสยศาสตร์', 'ไสย', 'ของใส่', 'โดนของ', 'ทำของ', 'มนต์ดำ', 'คุณผี', 'เสน่ห์ยาแฝด'],
        'love' => ['ความรัก', 'แต่งงาน', 'เนื้อคู่', 'แฟน', 'สามี', 'ภรรยา', 'คนคุย', 'หัวใจ', 'คู่ครอง', 'รัก', 'อกหัก', 'นอกใจ'],
        'career' => ['การงาน', 'อาชีพ', 'ธุรกิจ', 'เลื่อนตำแหน่ง', 'สมัครงาน', 'ย้ายงาน', 'เปลี่ยนงาน', 'สอบ', 'งาน', 'ค้าขาย'],
        'money' => ['การเงิน', 'หนี้', 'โชคลาภ', 'หวย', 'ลาภ', 'เงินทอง', 'รวย', 'ลงทุน'],
        'health' => ['สุขภาพ', 'ป่วย', 'โรค', 'ผ่าตัด', 'หมอ', 'อาการ'],
    ];

    /** ถ้าข้อความเป็นเรื่องจ่ายเงิน → หัวข้อ general (ห้ามตีเป็น money) */
    private const PAYMENT_HINT_REGEX = '/(โอน|จ่าย|ชำระ|ราคา|บัญชี|สลิป|บาท|฿|ค่าครู|พร้อมเพย์|promptpay|qr|คิวอาร์)/iu';

    /**
     * ข้อความตอบในแชทตามชนิด (ชนิดที่ไม่รู้จัก → KIND_READING)
     */
    public static function reply(string $kind, ?string $name = null): string
    {
        $kind = in_array($kind, self::KINDS, true) ? $kind : self::KIND_READING;

        return self::fill(self::REPLIES[$kind], $name, self::NAME_FALLBACK[$kind] ?? self::DEFAULT_NAME_FALLBACK);
    }

    /**
     * ข้อความชวนตามเลข (1-9) — เลขที่ไม่มี = null
     */
    public static function invite(int $number, ?string $name = null): ?string
    {
        if (! isset(self::INVITES[$number])) {
            return null;
        }

        return self::fill(
            self::INVITES[$number]['text'],
            $name,
            self::NAME_FALLBACK['invite_'.$number] ?? self::DEFAULT_NAME_FALLBACK
        );
    }

    /**
     * เลขข้อความชวนที่ใช้ได้ "ตอนนี้"
     *
     * @param  CarbonInterface|null  $now  เวลา (null = ตอนนี้) — แปลงเป็นเวลาไทยก่อนเทียบช่วง
     * @param  bool  $continuityLive  ความต่อเนื่องของแชท deploy แล้วหรือยัง (ข้อ 8/9)
     * @param  string|null  $topic  หัวข้อของลูกค้า (ข้อ 3 ใช้เฉพาะ love)
     * @return array<int,int>
     */
    public static function eligibleInviteNumbers(?CarbonInterface $now = null, bool $continuityLive = false, ?string $topic = null): array
    {
        $hour = (int) ($now ?? now())->copy()->setTimezone('Asia/Bangkok')->format('G');

        $out = [];
        foreach (self::INVITES as $n => $row) {
            if ($row['continuity'] && ! $continuityLive) {
                continue;
            }
            if ($row['topic'] !== null && $row['topic'] !== $topic) {
                continue;
            }
            if ($row['window'] === self::WINDOW_EVENING && ! ($hour >= 18 && $hour < 23)) {
                continue;
            }
            if ($row['window'] === self::WINDOW_MORNING && ! ($hour >= 5 && $hour < 10)) {
                continue;
            }
            $out[] = $n;
        }

        return $out;
    }

    /**
     * สุ่มข้อความชวนที่ใช้ได้ตอนนี้ 1 ข้อ
     *
     * @param  int|null  $seed  ใส่เลขเดิม = ได้ข้อเดิม (เช่น crc32 ของ PSID ให้แต่ละคนได้ข้อคงที่)
     */
    public static function pickInvite(?string $name = null, ?CarbonInterface $now = null, bool $continuityLive = false, ?string $topic = null, ?int $seed = null): string
    {
        $numbers = self::eligibleInviteNumbers($now, $continuityLive, $topic);
        if ($numbers === []) {
            $numbers = [1];
        }

        $index = $seed === null ? random_int(0, count($numbers) - 1) : abs($seed) % count($numbers);

        return (string) self::invite($numbers[$index], $name);
    }

    /**
     * quick reply ของ DM ชวน: [🌙 เข้าห้องแม่หมอ] [🔕 พัก 7 วัน] [🚫 ไม่ต้องส่งอีก]
     *
     * @return array<int, array{content_type:string,title:string,payload:string}>
     */
    public static function inviteQuickReplies(): array
    {
        return [
            ['content_type' => 'text', 'title' => self::LABEL_WEB_ROOM, 'payload' => self::PAYLOAD_WEB_ROOM],
            ['content_type' => 'text', 'title' => self::LABEL_SNOOZE, 'payload' => self::PAYLOAD_SNOOZE],
            ['content_type' => 'text', 'title' => self::LABEL_OPTOUT, 'payload' => self::PAYLOAD_OPTOUT],
        ];
    }

    /**
     * quick reply ปุ่มเดียว "🌙 เข้าห้องแม่หมอ" (ใช้กับข้อความ fail-closed)
     *
     * @return array<int, array{content_type:string,title:string,payload:string}>
     */
    public static function webRoomQuickReply(): array
    {
        return [
            ['content_type' => 'text', 'title' => self::LABEL_WEB_ROOM, 'payload' => self::PAYLOAD_WEB_ROOM],
        ];
    }

    /**
     * ชื่อที่ใส่ในข้อความได้ — null เมื่อว่าง / 'User' / 'คุณ' / ยาวเกิน 20 ตัว
     */
    public static function displayName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        // ตัดอักขระควบคุม/ขึ้นบรรทัด แล้วยุบช่องว่าง (กันชื่อแปลก ๆ ทำข้อความพัง)
        $clean = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $name) ?? '';
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');

        if ($clean === '' || in_array(mb_strtolower($clean), ['user', 'คุณ', 'ลูกค้า'], true)) {
            return null;
        }

        // ชื่อที่ดูเหมือนลิงก์ → ไม่ใส่ (ห้ามมี URL ในข้อความ)
        if (preg_match('#(https?://|www\.|\.com|\.online|m\.me)#i', $clean)) {
            return null;
        }

        return mb_strlen($clean) > self::NAME_MAX_LENGTH ? null : $clean;
    }

    /**
     * หัวข้อของข้อความลูกค้า: love|career|money|health|kunsai|general
     */
    public static function detectTopic(?string $text): string
    {
        $text = mb_strtolower(trim((string) $text));
        if ($text === '') {
            return 'general';
        }

        $hasPaymentHint = (bool) preg_match(self::PAYMENT_HINT_REGEX, $text);

        foreach (self::TOPIC_KEYWORDS as $topic => $keywords) {
            // ข้อความเรื่องจ่ายเงิน ("โอนเงินยังไง") ต้องไม่ถูกตีเป็นหัวข้อ "การเงิน"
            if ($topic === 'money' && $hasPaymentHint) {
                continue;
            }
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    return $topic;
                }
            }
        }

        return 'general';
    }

    /**
     * ข้อความทุกบรรทัดในไฟล์นี้ (ใช้ใน lint test)
     *
     * @return array<string,string>
     */
    public static function allLines(): array
    {
        $lines = [];
        foreach (self::REPLIES as $kind => $text) {
            $lines['reply_'.$kind] = $text;
        }
        foreach (self::INVITES as $n => $row) {
            $lines['invite_'.$n] = $row['text'];
        }
        $lines['fail_closed'] = self::FAIL_CLOSED;
        $lines['slip_received'] = self::SLIP_RECEIVED;
        $lines['comment_reply'] = self::COMMENT_REPLY;
        foreach ([self::LABEL_WEB_ROOM, self::LABEL_WEB_ROOM_ALT, self::LABEL_HOWTO, self::LABEL_SNOOZE, self::LABEL_OPTOUT] as $i => $label) {
            $lines['label_'.$i] = $label;
        }

        return $lines;
    }

    /**
     * แทน {name} ด้วยชื่อ (หรือคำแทนเมื่อไม่มีชื่อ)
     */
    private static function fill(string $template, ?string $name, string $fallback): string
    {
        $display = self::displayName($name);

        return str_replace('{name}', $display ?? $fallback, $template);
    }
}
