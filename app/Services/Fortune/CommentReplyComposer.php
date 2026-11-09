<?php

namespace App\Services\Fortune;

use App\Jobs\ProcessCommentEngagement;
use App\Models\FortuneCommentReply;
use App\Models\FortuneInviteMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🗨️ (2026-11-09) แต่งคำตอบใต้คอมเมนต์ของโหมด FB web-only ให้ไม่ซ้ำกัน
 *
 * ต้นเหตุ: เปิดธง facebook_web_only เมื่อ 2026-11-09 01:03 แล้วทุกคอมเมนต์บนเพจหลัก
 * ได้ประโยคเดียวกัน "แม่หมอทักไปใน inbox แล้วนะคะ 🌙" (177 จาก 184 คอมเมนต์ในวันนั้น)
 * เพจที่ตอบคอมเมนต์ด้วยข้อความเดิมซ้ำ ๆ หลายร้อยครั้ง = สัญญาณสแปม/พฤติกรรมไม่จริงของ Facebook
 * (เสี่ยงโดนซ่อนคำตอบ ลดการมองเห็น หรือจำกัดเพจ แบบที่เคยโดน #2022 เมื่อ 2026-07-23)
 *
 * วิธีแต่ง:
 * - คำขึ้นต้นตาม "เรื่อง" ของคอมเมนต์ (สาธุ/ความรัก/การงาน/โชคลาภ/สุขภาพ/สติกเกอร์/ทั่วไป)
 *   ⇒ คำตอบสอดคล้องกับสิ่งที่ลูกค้าพิมพ์ ไม่ใช่ประโยคสำเร็จรูปประโยคเดียว
 * - DM ส่งถึงจริง ⇒ ต่อท้ายด้วยประโยคบอกว่าแม่หมอทักไปแล้ว (22 แบบ บางแบบใส่ชื่อ)
 *   DM ไม่ได้ส่ง ⇒ ห้ามพูดว่าทักไปแล้ว ใช้คลังขอบคุณ หรือคำขึ้นต้น + คำอวยพรท้าย
 * - คอมเมนต์ปล่อยวาง/ตำหนิ ⇒ ใช้คลังโทนสงบ/โทนกลาง และไม่พูดเรื่อง inbox
 * - กันซ้ำ "ต่อเพจ": จำคำขึ้นต้น / ประโยค inbox / ข้อความเต็มที่เพิ่งใช้บนเพจนี้
 *   ⇒ สองคอมเมนต์ติดกันบนเพจเดียวกันไม่มีวันได้ข้อความเดียวกัน
 *
 * ทุกบรรทัดในคลังต้องผ่าน FortuneInviteMessage::isWebSafeText (ไม่มีเรื่องเงิน/ลิงก์/ฟรี/LINE)
 * — เทสต์ CommentReplyVarietyTest ตรวจทุกบรรทัด
 */
class CommentReplyComposer
{
    /** อารมณ์/เรื่องของคอมเมนต์ */
    public const MOOD_BLESSING = 'blessing';

    public const MOOD_LOVE = 'love';

    public const MOOD_CAREER = 'career';

    public const MOOD_LUCK = 'luck';

    public const MOOD_HEALTH = 'health';

    public const MOOD_STICKER = 'sticker';

    public const MOOD_GENERAL = 'general';

    /** โทนสงบ (ใช้เมื่อคลัง letting_go ใน DB ยังไม่พร้อม) */
    public const MOOD_CALM = 'calm';

    /** โทนกลาง (ใช้เมื่อคลัง neutral ใน DB ยังไม่พร้อม) */
    public const MOOD_NEUTRAL = 'neutral';

    /**
     * คำขึ้นต้นตามเรื่องของคอมเมนต์
     *
     * @var array<string, array<int, string>>
     */
    public const OPENERS = [
        self::MOOD_BLESSING => [
            'สาธุค่ะ 🙏',
            'รับพรไปเต็ม ๆ เลยนะคะ ✨',
            'สาธุ ขอให้สมปรารถนานะคะ 🙏',
            'น้อมรับพรไปด้วยกันค่ะ 💜',
            'ขอให้สิ่งที่ตั้งใจไว้เป็นจริงนะคะ 🌙',
            'ขอให้พรนี้กลับไปหาเป็นร้อยเท่าค่ะ ✨',
            'สาธุ ๆ ขอให้มีแต่เรื่องดี ๆ เข้ามานะคะ 🌟',
            'รับไปเลยค่ะ ขอให้โชคดีตลอดปีนะคะ 🍀',
            'อนุโมทนาด้วยนะคะ 🙏',
            'ขอให้ใจที่ส่งมาได้รับสิ่งดีตอบแทนนะคะ 🤍',
        ],
        self::MOOD_LOVE => [
            'เรื่องหัวใจนี่ละเอียดอ่อนจริง ๆ ค่ะ 💜',
            'ขอให้ความรักลงตัวนะคะ 💞',
            'ขอให้เจอคนที่ใช่และอยู่ด้วยกันนาน ๆ นะคะ 💜',
            'ใจที่รักจริงย่อมมีทางของมันค่ะ 🌙',
            'ขอให้หัวใจได้พักในที่ที่อบอุ่นนะคะ 🤍',
            'เรื่องคนคุยคนรัก แม่หมอเข้าใจเลยค่ะ 💗',
            'ขอให้ความรักหวานขึ้นทุกวันนะคะ 🌸',
            'ขอให้คนที่คิดถึงคิดถึงตอบนะคะ 💌',
        ],
        self::MOOD_CAREER => [
            'ขอให้งานราบรื่น มีคนเห็นฝีมือนะคะ ✨',
            'สู้ ๆ นะคะ งานที่ตั้งใจจะออกดอกออกผลค่ะ 🌱',
            'ขอให้การงานก้าวหน้าเร็ว ๆ นะคะ 🌟',
            'ขอให้ผ่านทุกด่านไปได้สวย ๆ นะคะ 💪',
            'เรื่องงานช่วงนี้ ขอให้มีผู้ใหญ่ช่วยหนุนนะคะ 🙏',
            'ขอให้สอบผ่าน ยื่นที่ไหนได้ที่นั่นนะคะ 📜',
            'ความตั้งใจแบบนี้ ดวงช่วยแน่นอนค่ะ ✨',
            'ขอให้ค้าขายคล่องมือนะคะ 🌿',
        ],
        self::MOOD_LUCK => [
            'ขอให้โชคลาภไหลมาเทมานะคะ 🍀',
            'รับโชคไปเต็ม ๆ เลยค่ะ ✨',
            'ขอให้มั่งมีศรีสุขนะคะ 🌟',
            'ขอให้การเงินคล่องตัว มีเหลือเก็บนะคะ 🍀',
            'ขอให้ร่ำรวยสมใจนะคะ 💫',
            'ขอให้มีลาภลอยเข้ามาแบบไม่ทันตั้งตัวนะคะ 🌙',
            'ขอให้เรื่องหนี้สินคลี่คลายเร็ว ๆ นะคะ 🙏',
            'ขอให้มีกินมีใช้ ไม่ขัดสนนะคะ 🌿',
        ],
        self::MOOD_HEALTH => [
            'ขอให้แข็งแรง หายไว ๆ นะคะ 🤍',
            'ดูแลตัวเองดี ๆ นะคะ ขอให้สุขภาพแข็งแรงค่ะ 🌿',
            'ขอให้คนที่รักปลอดภัย แข็งแรงนะคะ 🙏',
            'ขอให้หายป่วยไว ๆ นะคะ 💚',
            'ส่งกำลังใจให้หายดีไว ๆ ค่ะ 🌸',
            'ขอให้ร่างกายแข็งแรง จิตใจสดใสนะคะ ✨',
        ],
        self::MOOD_STICKER => [
            'ขอบคุณที่แวะมาทักทายค่ะ 🌙',
            'ส่งยิ้มกลับไปให้นะคะ 😊',
            'แม่หมอเห็นแล้วนะคะ 💜',
            'ขอบคุณที่ส่งพลังมาให้กันค่ะ ✨',
            'ดีใจที่แวะมานะคะ 🤍',
            'รับรู้ถึงพลังดี ๆ เลยค่ะ 🌟',
        ],
        self::MOOD_GENERAL => [
            'ขอบคุณที่แวะมาคุยกันนะคะ 🌙',
            'ขอบคุณที่ร่วมแบ่งปันนะคะ 🙏',
            'ดีใจที่ได้เห็นกันอีกแล้วค่ะ 💜',
            'ขอให้วันนี้เป็นวันที่ดีนะคะ ✨',
            'แม่หมออ่านแล้วนะคะ 🤍',
            'ขอบคุณที่มาร่วมส่งพลังค่ะ 🌟',
            'ขอให้ใจสบาย ไร้กังวลนะคะ 🌿',
            'ขอให้เรื่องที่คิดอยู่คลี่คลายนะคะ 🌙',
            'ขอบคุณสำหรับทุกถ้อยคำนะคะ 💫',
            'ขอให้มีแต่คนดี ๆ อยู่รอบตัวนะคะ 🤍',
            'อ่านแล้วอบอุ่นใจค่ะ 🌸',
            'ขอให้ทุกอย่างค่อย ๆ ดีขึ้นนะคะ ✨',
        ],
        self::MOOD_CALM => [
            // ⚠️ ห้ามเขียน "เบาที่" — มีคำว่า "บาท" ซ้อนอยู่ ด่าน lint เงินจะตีตก
            'ใจที่ปล่อยวางได้ คือใจที่สงบที่สุดค่ะ 🤍',
            'ขอให้ใจได้พักและสงบนะคะ 🌙',
            'วางลงได้ ใจก็เบาลงค่ะ 🕊️',
            'ขอเป็นกำลังใจให้นะคะ 🤍',
            'ค่อย ๆ หายใจ ค่อย ๆ ผ่านไปนะคะ 🌿',
            'คนที่ให้อภัยได้ คือคนที่ใจแข็งแรงค่ะ 🕊️',
        ],
        self::MOOD_NEUTRAL => [
            'ขอบคุณที่บอกความรู้สึกนะคะ 🙏',
            'รับฟังทุกความเห็นค่ะ 🙏',
            'ขอบคุณที่สละเวลามาเขียนนะคะ',
            'แม่หมอรับไว้พิจารณานะคะ 🙏',
        ],
    ];

    /**
     * ประโยคบอกว่าแม่หมอทักไปใน inbox แล้ว — ใช้เฉพาะเมื่อ DM ส่งถึงจริง
     *
     * แบบที่มี {name} ใช้เฉพาะเมื่อรู้ชื่อลูกค้า (displayName ผ่าน)
     *
     * @var array<int, string>
     */
    public const INBOX_NOTICES = [
        'แม่หมอทักไปใน inbox แล้วนะคะ',
        'แม่หมอส่งข้อความไปหาในแชทแล้วค่ะ',
        'มีข้อความจากแม่หมอรออยู่ใน Messenger นะคะ',
        'แวะเปิด inbox ดูหน่อยนะคะ แม่หมอฝากข้อความไว้ค่ะ',
        'แม่หมอฝากข้อความไว้ในแชทแล้ว ว่าง ๆ เปิดอ่านนะคะ',
        'ลองเช็คกล่องข้อความดูนะคะ แม่หมอทักไปแล้วค่ะ',
        'ถ้ายังไม่เห็นข้อความ ลองดูที่คำขอข้อความนะคะ',
        'แม่หมอส่งข้อความส่วนตัวไปแล้วนะคะ',
        'เรื่องส่วนตัวแม่หมอขอคุยต่อในแชทนะคะ ทักไปแล้วค่ะ',
        'แม่หมอแอบส่งข้อความไปให้แล้วนะคะ',
        'ส่งข้อความไปให้ทาง Messenger แล้วนะคะ',
        'แม่หมอรออยู่ในแชทนะคะ ทักไปให้แล้วค่ะ',
        'มีข้อความเล็ก ๆ จากแม่หมอรออยู่ในกล่องข้อความค่ะ',
        'เปิดแชทดูได้เลยนะคะ แม่หมอทักไปแล้ว',
        'ข้อความจากแม่หมอส่งถึงแชทแล้วนะคะ',
        'แม่หมอทักไปทางข้อความแล้ว ไว้คุยกันต่อนะคะ',
        'คุณ{name} แม่หมอส่งข้อความไปให้แล้วนะคะ',
        'คุณ{name}ลองเปิด inbox ดูนะคะ แม่หมอทักไปแล้วค่ะ',
        'แม่หมอฝากข้อความถึงคุณ{name}ไว้ในแชทแล้วค่ะ',
        'คุณ{name}คะ มีข้อความจากแม่หมอรออยู่นะคะ',
        'แม่หมอทักคุณ{name}ไปในแชทแล้วนะคะ',
        'คุณ{name} เช็คข้อความในแชทได้เลยค่ะ',
    ];

    /**
     * คำอวยพรท้าย (ใช้เมื่อไม่ได้ DM) — '' = ไม่ต่อท้าย
     *
     * @var array<int, string>
     */
    public const CLOSERS = [
        '',
        'ขอให้มีความสุขมาก ๆ นะคะ',
        'ขอบคุณที่ติดตามแม่หมอนะคะ',
        'ขอให้เจอแต่สิ่งดี ๆ ค่ะ',
        'แม่หมอส่งพลังดี ๆ กลับไปให้นะคะ',
        'ดูแลตัวเองด้วยนะคะ',
        'ขอให้ราบรื่นทุกเรื่องนะคะ',
        'แล้วแวะมาคุยกันใหม่นะคะ',
    ];

    /** จำคำขึ้นต้นล่าสุดต่อเพจกี่อัน (ต่อหมวด — ห้ามเกินขนาดหมวด - 1) */
    protected const RECENT_OPENERS = 4;

    /** จำประโยค inbox ล่าสุดต่อเพจกี่อัน */
    protected const RECENT_NOTICES = 8;

    /** จำข้อความเต็มล่าสุดต่อเพจกี่อัน */
    protected const RECENT_TEXTS = 40;

    /** อายุความจำต่อเพจ (วินาที) — 1 วัน */
    protected const MEMORY_TTL = 86400;

    /** ความยาวสูงสุดของคำตอบใต้คอมเมนต์ */
    public const MAX_LENGTH = 180;

    /** คำที่บอกว่าเป็นคอมเมนต์รับพร/อวยพร */
    protected const BLESSING_REGEX = '/สาธุ|น้อมรับ|รับพร|รับโชค|รับทรัพย์|อามีน|อาเมน|อนุโมทนา|ขอให้|ขอบคุณ|ขอบใจ|^\s*รับ/u';

    /**
     * แต่งคำตอบใต้คอมเมนต์ 1 อัน (ผ่าน lint แล้ว ไม่ซ้ำกับคำตอบล่าสุดของเพจนี้)
     *
     * @param  string|null  $commentText  ข้อความคอมเมนต์ของลูกค้า
     * @param  string|null  $name  ชื่อ FB ของลูกค้า
     * @param  bool  $dmSent  true = DM (Private Reply) ส่งถึงลูกค้าแล้วจริง ⇒ พูดได้ว่าทักไปแล้ว
     * @param  string  $pageKey  ตัวระบุเพจ (สาขา) — ความจำกันซ้ำแยกตามเพจ
     * @param  string|null  $userKey  PSID ลูกค้า (ให้คลังใน DB กันซ้ำต่อคน)
     * @return string คำตอบ (ไม่ว่างเสมอ)
     *
     * @example
     * CommentReplyComposer::compose('สาธุ รับโชคค่ะ', 'สมศรี', true, '107173337600346');
     * // "สาธุค่ะ 🙏 คุณสมศรีลองเปิด inbox ดูนะคะ แม่หมอทักไปแล้วค่ะ"
     */
    public static function compose(?string $commentText, ?string $name, bool $dmSent, string $pageKey, ?string $userKey = null): string
    {
        $text = (string) $commentText;

        try {
            $lock = Cache::lock('fcr:compose:lock:'.md5($pageKey), 5);

            return $lock->block(2, fn () => self::composeUnlocked($text, $name, $dmSent, $pageKey, $userKey));
        } catch (\Throwable $e) {
            // ล็อกไม่ได้ (driver ไม่รองรับ / รอนาน) → แต่งแบบไม่ล็อก ดีกว่าไม่ตอบ
            Log::debug('🗨️ CommentReplyComposer: ล็อกไม่ได้ แต่งแบบไม่ล็อก', ['error' => $e->getMessage()]);

            return self::composeUnlocked($text, $name, $dmSent, $pageKey, $userKey);
        }
    }

    /**
     * อารมณ์/เรื่องของคอมเมนต์ (ฟรี ไม่ใช้ AI)
     *
     * ลำดับ: โทนตำหนิ/ปล่อยวางก่อน (ห้ามตอบร่าเริงใต้คอมเมนต์แบบนี้) → เรื่องเฉพาะ → รับพร → สติกเกอร์ → ทั่วไป
     */
    public static function detectMood(?string $commentText): string
    {
        $text = trim((string) $commentText);

        $tone = ProcessCommentEngagement::detectCommentTone($text);
        if ($tone === FortuneCommentReply::CATEGORY_NEUTRAL) {
            return self::MOOD_NEUTRAL;
        }
        if ($tone === FortuneCommentReply::CATEGORY_LETTING_GO) {
            return self::MOOD_CALM;
        }

        // ไม่มีตัวอักษรเลย (สติกเกอร์/รูป/อีโมจิล้วน)
        if ($text === '' || ! preg_match('/[\p{L}\p{N}]/u', $text)) {
            return self::MOOD_STICKER;
        }

        // ตัดคำเรียกแม่หมอออกก่อน — คำว่า "หมอ" อยู่ในคำค้นหมวดสุขภาพ
        // ไม่ตัด = "ขอบคุณแม่หมอ" ถูกตีเป็นเรื่องป่วย แล้วได้คำตอบ "ขอให้หายป่วยไว ๆ"
        $topicText = str_replace(['แม่หมอ', 'หมอดู', 'หมอจันทรา'], ' ', $text);
        $topic = WebHandoffCopy::detectTopic($topicText);
        $byTopic = [
            'love' => self::MOOD_LOVE,
            'career' => self::MOOD_CAREER,
            'money' => self::MOOD_LUCK,
            'health' => self::MOOD_HEALTH,
        ];
        if (isset($byTopic[$topic])) {
            return $byTopic[$topic];
        }

        if (preg_match(self::BLESSING_REGEX, $text)) {
            return self::MOOD_BLESSING;
        }

        return self::MOOD_GENERAL;
    }

    /**
     * ทุกบรรทัดในคลัง (ใช้ใน lint test)
     *
     * @return array<int, string>
     */
    public static function allLines(): array
    {
        $lines = [];
        foreach (self::OPENERS as $mood => $openers) {
            foreach ($openers as $line) {
                $lines[] = $line;
            }
        }
        foreach (self::INBOX_NOTICES as $line) {
            $lines[] = str_replace('{name}', 'สมศรี', $line);
        }
        foreach (self::CLOSERS as $line) {
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * ตัวแต่งจริง (เรียกภายใต้ล็อกต่อเพจ)
     */
    protected static function composeUnlocked(string $commentText, ?string $name, bool $dmSent, string $pageKey, ?string $userKey): string
    {
        $memoryKey = 'fcr:compose:'.md5($pageKey);
        $memory = Cache::get($memoryKey);
        $memory = is_array($memory) ? $memory : [];
        $recentTexts = (array) ($memory['texts'] ?? []);

        $mood = self::detectMood($commentText);
        $displayName = self::nameForReply($name);
        $reply = null;

        // 1) โทนสงบ/โทนกลาง → คลังใน DB (100/40 แบบ) ไม่พูดเรื่อง inbox ไม่ชวนอะไร
        if ($mood === self::MOOD_CALM || $mood === self::MOOD_NEUTRAL) {
            $category = $mood === self::MOOD_CALM
                ? FortuneCommentReply::CATEGORY_LETTING_GO
                : FortuneCommentReply::CATEGORY_NEUTRAL;
            $reply = self::fromPool($category, $displayName, $userKey, $pageKey, $recentTexts);
        }

        // 2) ไม่ได้ DM + โทนปกติ → คลังขอบคุณ 200 แบบ (ห้ามบอกว่าทักไปแล้ว)
        if ($reply === null && ! $dmSent && $mood !== self::MOOD_CALM && $mood !== self::MOOD_NEUTRAL) {
            $reply = self::fromPool(FortuneCommentReply::CATEGORY_APPRECIATION, $displayName, $userKey, $pageKey, $recentTexts);
        }

        // 3) แต่งจากคำขึ้นต้น + ประโยค inbox (DM ส่งแล้ว) หรือ + คำอวยพรท้าย (ไม่ได้ DM)
        if ($reply === null) {
            $withNotice = $dmSent && $mood !== self::MOOD_CALM && $mood !== self::MOOD_NEUTRAL;

            for ($attempt = 0; $attempt < 12; $attempt++) {
                $openerIdx = self::pickIndex(
                    count(self::OPENERS[$mood]),
                    (array) ($memory['openers'][$mood] ?? []),
                    self::RECENT_OPENERS
                );
                $opener = self::OPENERS[$mood][$openerIdx];

                if ($withNotice) {
                    $usable = self::usableNoticeIndexes($displayName);
                    $noticeIdx = self::pickFrom($usable, (array) ($memory['notices'] ?? []), self::RECENT_NOTICES);
                    $tail = self::fillName(self::INBOX_NOTICES[$noticeIdx], $displayName);
                } else {
                    $noticeIdx = null;
                    // โทนสงบ/โทนกลาง ไม่ต่อคำอวยพรร่าเริง
                    $tail = ($mood === self::MOOD_CALM || $mood === self::MOOD_NEUTRAL)
                        ? ''
                        : self::CLOSERS[random_int(0, count(self::CLOSERS) - 1)];
                }

                $candidate = self::tidy($opener.' '.$tail);

                if (! in_array(self::hash($candidate), $recentTexts, true) || $attempt === 11) {
                    $reply = $candidate;
                    $memory['openers'][$mood] = self::remember((array) ($memory['openers'][$mood] ?? []), $openerIdx, min(self::RECENT_OPENERS, count(self::OPENERS[$mood]) - 1));
                    if ($noticeIdx !== null) {
                        $memory['notices'] = self::remember((array) ($memory['notices'] ?? []), $noticeIdx, self::RECENT_NOTICES);
                    }
                    break;
                }
            }
        }

        $reply = self::tidy((string) $reply);

        // ตาข่ายสุดท้าย: ห้ามว่าง / ห้ามมีคำเรื่องเงิน-ลิงก์
        if (! FortuneInviteMessage::isWebSafeText($reply)) {
            $reply = self::OPENERS[self::MOOD_GENERAL][random_int(0, count(self::OPENERS[self::MOOD_GENERAL]) - 1)];
        }

        $memory['texts'] = self::remember($recentTexts, self::hash($reply), self::RECENT_TEXTS);
        Cache::put($memoryKey, $memory, self::MEMORY_TTL);

        return $reply;
    }

    /**
     * หยิบจากคลังใน DB (กันซ้ำต่อคน + ต่อเพจ) — คืน null ถ้าคลังไม่พร้อม / ไม่ผ่าน lint / ซ้ำกับล่าสุดของเพจ
     *
     * @param  array<int, string>  $recentTexts  hash ข้อความล่าสุดของเพจนี้
     */
    protected static function fromPool(string $category, ?string $displayName, ?string $userKey, string $pageKey, array $recentTexts): ?string
    {
        try {
            $text = FortuneCommentReply::pickRendered($category, $displayName ?? '', $userKey, 'th', $pageKey);
        } catch (\Throwable $e) {
            Log::debug('🗨️ CommentReplyComposer: หยิบคลังไม่ได้', ['category' => $category, 'error' => $e->getMessage()]);

            return null;
        }

        if ($text === null) {
            return null;
        }

        $text = self::tidy($text);

        // คลังกลางเขียนไว้สำหรับโหมดปกติ — บางบรรทัดอาจมีลิงก์/{page_link} ⇒ web-only ห้ามใช้
        if (! FortuneInviteMessage::isWebSafeText($text) || in_array(self::hash($text), $recentTexts, true)) {
            return null;
        }

        return $text;
    }

    /**
     * index ของประโยค inbox ที่ใช้ได้ (ไม่รู้ชื่อ ⇒ ตัดแบบที่มี {name})
     *
     * @return array<int, int>
     */
    protected static function usableNoticeIndexes(?string $displayName): array
    {
        $out = [];
        foreach (self::INBOX_NOTICES as $i => $line) {
            if ($displayName === null && str_contains($line, '{name}')) {
                continue;
            }
            $out[] = $i;
        }

        return $out;
    }

    /**
     * สุ่ม index 0..$count-1 โดยเลี่ยง index ที่เพิ่งใช้
     *
     * @param  array<int, int>  $recent
     */
    protected static function pickIndex(int $count, array $recent, int $memory): int
    {
        return self::pickFrom(range(0, $count - 1), $recent, $memory);
    }

    /**
     * สุ่มจากรายการ โดยเลี่ยงตัวที่เพิ่งใช้ (ถ้าเลี่ยงแล้วไม่เหลือ ⇒ เลี่ยงแค่ตัวล่าสุด)
     *
     * @param  array<int, int>  $options
     * @param  array<int, int>  $recent
     */
    protected static function pickFrom(array $options, array $recent, int $memory): int
    {
        $memory = max(0, min($memory, count($options) - 1));
        $avoid = $memory > 0 ? array_slice($recent, -$memory) : [];
        $pool = array_values(array_diff($options, $avoid));

        if ($pool === []) {
            $last = end($recent);
            $pool = array_values(array_diff($options, [$last]));
        }
        if ($pool === []) {
            $pool = array_values($options);
        }

        return $pool[random_int(0, count($pool) - 1)];
    }

    /**
     * ต่อท้ายความจำแบบจำกัดจำนวน
     *
     * @param  array<int, mixed>  $list
     * @return array<int, mixed>
     */
    protected static function remember(array $list, mixed $value, int $max): array
    {
        $list[] = $value;

        return $max > 0 ? array_values(array_slice($list, -$max)) : [];
    }

    /**
     * ชื่อที่ใส่ในคำตอบได้ (ตัด "คุณ" นำหน้าออก กัน "คุณคุณ...")
     */
    protected static function nameForReply(?string $name): ?string
    {
        $clean = WebHandoffCopy::displayName($name);
        if ($clean === null) {
            return null;
        }

        $clean = trim((string) preg_replace('/^คุณ\s*/u', '', $clean));

        return $clean === '' ? null : $clean;
    }

    protected static function fillName(string $line, ?string $displayName): string
    {
        return str_replace('{name}', (string) $displayName, $line);
    }

    /**
     * ยุบช่องว่าง + ตัดความยาว
     */
    protected static function tidy(string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text) > self::MAX_LENGTH ? mb_substr($text, 0, self::MAX_LENGTH) : $text;
    }

    protected static function hash(string $text): string
    {
        return substr(md5($text), 0, 12);
    }
}
