<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🌙 โหมด DM ดูดวงรายวัน — ด่านขาเข้า "ลูกค้าตอบวันเกิดกลับมา"
 *
 * (2026-07-31) flow: DM ชวนบอกวันเกิด → ลูกค้าตอบ → ส่งดวงของวันเกิดนั้นให้ฟรี
 *
 * 🚨 กฎเหล็กของด่านนี้ — "ห้ามแย่งข้อความของลูกค้าที่จ่ายเงินแล้ว"
 *
 *   ลูกค้า Deep-39 ที่จ่ายแล้วและอยู่สถานะ collecting_birthdate **พิมพ์วันเกิด
 *   มาเหมือนกันเป๊ะ** และที่อันตรายกว่านั้นคือ IN_PREDICTION_STATUSES จงใจ
 *   ไม่รวม DEEP_ACTIVE_STATUSES (ดู FortuneReading:269) แปลว่า IN-PREDICTION
 *   Hard Guard ที่หัว processMessage **มองไม่เห็นลูกค้ากลุ่มนี้** — ไหลมาถึงด่านนี้เต็ม ๆ
 *
 *   ด่านนี้จึงต้องเช็คเองครบทุกชั้น ห้ามพึ่ง guard ข้างบน และต้อง fail-open เสมอ
 *   (ทุก exception → คืน null = ปล่อยให้ flow เดิมทำงาน ไม่ใช่บล็อกลูกค้า)
 *
 * ⚠️ ผูกกับ "ธง pending" เท่านั้น ไม่ใช่ regex ลอย ๆ เพราะ:
 *   - LINE ใช้ processMessage ตัวเดียวกัน (ไม่มี DM ชวนฝั่ง LINE เลย)
 *   - "วันจันทร์" กำกวมสูงมาก ("ไปหาหมอวันจันทร์" / "นัดวันจันทร์")
 *   ตั้งธงเฉพาะตอนที่เรา "ถามไปก่อน" เท่านั้น
 */
trait DailyHoroscopeModeTrait
{
    /** อายุธง pending — ลูกค้าบางคนตอบข้ามวัน */
    protected const DAILY_PENDING_TTL_DAYS = 7;

    /** ชื่อวันในสัปดาห์ index 0=อาทิตย์ … 6=เสาร์ (ตรงกับคอลัมน์ birth_day) */
    protected const DAILY_DAY_NAMES = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

    /**
     * 🇹🇭 (2026-08-27) คำนำหน้า "วัน" ที่พิมพ์ตกหล่น — เคสจริง แม่ฝน คำแจ่ม
     * (PSID 28058628077130184, 27 ส.ค. 18:06) พิมพ์ **"วัพฤหัสบดีค่ะ"** ขาด "น" ตัวเดียว
     * → ตัวปอกเดิมจับ 'วัน' แบบตรงตัว ปอกไม่ออก → เหลือ "วัพฤหัสบดี" → เทียบ alias ไม่ติด
     *
     * ⚠️ บังคับให้มีสระ ั หรือ า เสมอ (ไม่ยอมรับ "ว" เปล่า ๆ) — กันคำที่ขึ้นต้นด้วย ว
     * ถูกแทะหัวมั่ว และเพราะไม่มีชื่อวันไหนขึ้นต้นด้วย ว การปอกเกินจะจบที่
     * "เทียบ alias ไม่ติด" อยู่แล้ว ไม่ใช่จับผิดวัน
     */
    protected const DAILY_DAY_PREFIX_TYPO_RE = 'ว[ัา][นณ]?|วน';

    /**
     * 🕐 (2026-08-27) ช่วงเวลาที่คนไทยพ่วงท้ายวันเกิดเป็นปกติ — "พุธกลางคืน" / "ศุกร์ตอนเช้า"
     *
     * เคสจริงวันเดียวกัน: `26646988441640086` พิมพ์ "วันพุธกลางคืน" แล้วตกไป AI chat
     * (ตระกูลเดียวกับ "พุธก่างคืนน่ะ" ที่บันทึกไว้ตั้งแต่ 2026-08-22 แต่ยังไม่เคยแก้)
     *
     * ✅ ปลอดภัยเพราะเป็น **closed set** จริง ๆ (ต่างจากคำลงท้ายสุภาพที่เป็น open set)
     * และปอกแล้วยังต้องเทียบ alias เต็มคำเหมือนเดิม — "เสาร์ไปงานแต่ง" ยังตกเหมือนเดิม
     */
    protected const DAILY_TIME_OF_DAY_RE =
        '(?:ตอน)?\s*(?:กลางวัน|กลางคืน|กลางดึก|เช้ามืด|เช้า|สาย|เที่ยง|บ่าย|เย็น|ค่ำ|ดึก)';

    /**
     * ชื่อวัน + คำสะกดที่ลูกค้าใช้จริง → index 0-6
     *
     * ⚠️ ลำดับสำคัญ — คำที่ยาวกว่าต้องมาก่อน ("พฤหัสบดี" ก่อน "พฤหัส") เพราะ
     *    detectThaiDayName() จับแบบ substring ถ้าเรียงผิดจะตัดคำผิดคำ
     *
     * ใช้ร่วมกัน 2 ที่: detectThaiDayName() (จับในประโยค)
     * และ looksLikeStandaloneDayName() (เทียบเต็มคำ) — อย่าแยกสำเนา
     */
    protected const DAILY_DAY_ALIASES = [
        'อาทิตย์' => 0, 'อาทิด' => 0, 'อาทิจ' => 0,
        'จันทร์' => 1, 'จันทร' => 1, 'จัน' => 1,
        'อังคาร' => 2,
        'พุธ' => 3,
        'พฤหัสบดี' => 4, 'พฤหัส' => 4, 'พฤหัสฯ' => 4, 'พหัส' => 4,
        'ศุกร์' => 5, 'ศุก' => 5,
        'เสาร์' => 6, 'เสา' => 6,
    ];

    /** วรรณยุกต์ไทย ่ ้ ๊ ๋ (optional) — คนพิมพ์ผิดตัวไหนก็ได้ ไม่เปลี่ยนความหมายของคำลงท้าย */
    protected const DAILY_TONE = '[\x{0E48}-\x{0E4B}]?';

    /**
     * ฅ (U+0E05 ฅ คน) เป็นอักษรเลิกใช้ — โผล่ในข้อความจริงเพราะคนพิมพ์พลาดจาก ค (U+0E04)
     * เคสจริง 2026-08-22: ลูกค้าพิมพ์ "วันศุกร์ฅรับ" แล้วไม่ได้ดวงรายวัน
     */
    protected const DAILY_KHO = '[คฅ]';

    /**
     * 🇹🇭 (2026-08-22) คำลงท้ายไทย — จับเป็น "ตระกูลเสียง" ไม่ใช่ลิสต์คำสะกดเป๊ะ
     *
     * ต้นเหตุ (Phensri Paopluk, PSID 27674940652154887, 17:22 น.):
     *   ลูกค้าพิมพ์ "วันศุกร์ค้ะ" → ปอก "วัน" ออกได้ เหลือ "ศุกร์ค้ะ"
     *   แต่ "ค้ะ" (ไม้โท) ไม่อยู่ในลิสต์ซึ่งมีแค่ "ค่ะ" (ไม้เอก) → ปอกไม่ออก
     *   → เทียบ DAILY_DAY_ALIASES ไม่ติด → ตกไปสาย AI chat = ลูกค้าขอดวงฟรีแล้วไม่ได้ของ
     *   วันเดียวกันเจอ 3 คนตกด้วยเหตุตระกูลนี้ ("วันศุกร์ฅรับ", "พุธก่างคืนน่ะ")
     *
     * ⚠️ ทำไมไม่เติมคำลงลิสต์: รูปสะกดผิดของคำลงท้ายเป็น **open set**
     *   (ค่ะ/ค้ะ/ค๊ะ/คะ/ค้าบ/คร้บ/ฅรับ...) เติมเท่าไหร่ก็ไม่จบ — บทเรียนเดิมของรีโปนี้
     *   ("keyword detector = แก้ไม่จบ ต้องแก้ที่โครงสร้าง")
     *   จึงเปิดกว้างที่ **มิติวรรณยุกต์ + อักษรที่คนพิมพ์สับ** ซึ่งเป็นมิติที่พลาดจริง
     *
     * 🛡️ ทำไมปลอดภัย: ตัวนี้ปอกแค่ "หาง" — เศษที่เหลือยังต้องเป็นชื่อวัน **เต็มคำ**
     *   ใน DAILY_DAY_ALIASES เหมือนเดิมทุกประการ ⇒ ไม่ได้ทำให้ด่านไหนอ่อนลง
     *   ("จันทร์ ขอดูดวงค่ะ" ยังเหลือ "จันทร์ ขอดูดวง" = ไม่ใช่ชื่อวัน → ตกเหมือนเดิม
     *    ด่าน escape ที่ผูกกับ $namedDayIndex === null จึงยังทำงาน = ไม่กินยอดขาย 39/99)
     *
     * ⛔ ห้ามเปลี่ยนไปเทียบแบบ substring หรือ fuzzy บนตัวชื่อวันเด็ดขาด —
     *   "จันทรา" คือชื่อแม่หมอเอง (59% ของแถวที่มีคำว่า "จันทร" ในคอมเมนต์จริง)
     *   ที่กันไว้ได้ทุกวันนี้ด้วย boundary (?![\p{L}\p{M}]) ซึ่ง \p{L} เป็นตัวที่ทำงาน
     *   (สระ า U+0E32 เป็น \p{L} ไม่ใช่ \p{M} — ยืนยันด้วย preg_match บน PHP 8.3 แล้ว)
     */
    protected const DAILY_PARTICLE_RE =
        '(?:เจ'.self::DAILY_TONE.'า'.self::DAILY_KHO.self::DAILY_TONE.'ะ'
        .'|น'.self::DAILY_TONE.'ะ(?:'.self::DAILY_KHO.self::DAILY_TONE.'ะ|'.self::DAILY_KHO.'ร'.self::DAILY_TONE.'ั?บ)?'
        .'|'.self::DAILY_KHO.'ร'.self::DAILY_TONE.'ั?บ(?:ผม)?'
        .'|'.self::DAILY_KHO.self::DAILY_TONE.'ั?บ'
        .'|'.self::DAILY_KHO.self::DAILY_TONE.'[ะา]บ?'
        .'|จ'.self::DAILY_TONE.'[าะ]'
        .'|จร'.self::DAILY_TONE.'า'
        .'|ฮ'.self::DAILY_TONE.'ะ)';

    /**
     * คำตอบรับที่ถือว่า "เท่ากับกดปุ่มดูดวงวันนี้" (เทียบตรงตัวหลังตัดคำลงท้าย)
     *
     * ⚠️ ห้ามใส่คำลงท้ายเปล่า ๆ ("ค่ะ" / "ครับ" / "จ้า") — กำกวมเกินไป
     *    และห้ามเทียบแบบ substring ไม่งั้น "ไม่เอา" จะ match "เอา"
     */
    protected const DAILY_SHORT_YES = [
        'เอา', 'เอาเลย', 'เอาสิ', 'ดู', 'ดูเลย', 'ดูสิ', 'อยากดู', 'ขอดู', 'ขอ',
        'ตกลง', 'ได้', 'ได้เลย', 'โอเค', 'โอเคเลย', 'ใช่', 'สนใจ', 'อยากรู้',
        'ส่งมา', 'ส่งเลย', 'ส่งมาเลย', 'เปิดเลย', 'เปิดให้',
        'ok', 'okay', 'yes', 'y',
        // ❗ ห้ามใส่คำที่มี "ดูดวง" — looksLikeDailyEscape (ด่านที่ 4) กินไปก่อนถึงตรงนี้
    ];

    /**
     * 🌙 ด่านหลัก — เรียกจาก processMessage (หลัง tier-direct 39/99, ก่อน smart-skip)
     *
     * ตำแหน่งสำคัญมาก:
     *   - ต้องอยู่ **หลัง** tier-direct ไม่งั้นคนพิมพ์ "39"/"99" จะได้ดวงฟรีแทนบิล (ตัดยอดขาย)
     *   - ต้องอยู่ **ก่อน** shouldSkipReply ไม่งั้นวันเกิดที่เป็นตัวเลขล้วน ("12/05/2530")
     *     จะถูกตีเป็น sticker_or_emoji_only แล้วเงียบหายไป
     *
     * @return array|null null = ไม่ใช่เคสของโหมดนี้ ปล่อย flow เดิมทำงานต่อ
     */
    protected function maybeHandleDailyHoroscopeReply(
        string $userId,
        string $messageText,
        ?array $userProfile = null
    ): ?array {
        try {
            $platform = $this->currentPlatform ?? 'facebook';

            // 1️⃣ ช่องทาง + โหมด (เช็คถูกที่สุดก่อน — ไม่แตะ DB/Cache)
            //    ⚠️ (2026-08-01) จงใจใช้ dailyReplyAllowedFor ไม่ใช่ dailyAppliesTo:
            //    ปุ่มรับดวงประจำวันเกิดถูกยื่นในโหมด classic ด้วย (ลูกค้าขอดูฟรีแต่สิทธิ์หมด)
            //    ถ้าบังคับ isDaily() ที่นี่ ปุ่มในโหมด classic จะกดแล้วไม่มีอะไรเกิดขึ้น
            if (! (new FortuneBotMode($this->settings))->dailyReplyAllowedFor($platform, $userId)) {
                return null;
            }

            // 2️⃣ ปกติต้องมีธง "เราถามไปแล้ว" — ออกจาก hot path ก่อนแตะ DB
            //
            // 🆕 (2026-08-02, owner: "ไม่ส่งคำทำนายฟรี") ยกเว้นคนที่พิมพ์ *ชื่อวันเดี่ยว ๆ* มาเอง
            //   เคสจริง (user 26806555292314388, 20:18): ลูกค้าใหม่เอี่ยมทักมาครั้งแรกว่า
            //   "@Meta AI 🟢 พุธ" (ก๊อปมาจากคอมเมนต์) — เราไม่เคยทักไปถามเขา จึงไม่มีธง
            //   → ด่านนี้ตีตก → ตกไป AI chat ทั่วไป = ลูกค้าขอดวงฟรีแล้วไม่ได้ของ
            //
            //   ⚠️ แคบไว้โดยตั้งใจ: ต้องเป็น "ชื่อวัน" ล้วน ๆ เท่านั้น (ดู looksLikeStandaloneDayName)
            //      ประโยคที่มีคำว่าวันพุธปนอยู่ ("วันพุธนี้จะไปหาหมอ") ห้าม trigger
            //      และด่าน 3/4 (บิล/กำลังทำนาย/เปลี่ยนเรื่อง) ยังทำงานเหมือนเดิมทุกประการ
            //
            // 🎯 (2026-08-21) ขยายให้รับ "เจตนา" ไม่ใช่แค่ป้ายปุ่ม
            //   เจ้าของสั่ง: "อยากให้บอทฉลาดพอจะส่งเข้าส่วนดูดวงรายวัน เมื่อลูกค้าพิมพ์
            //   แต่วันเกิด หรืออยากดูดวงรายวันแบบดวงฟรี"
            //   วัดจากการลากประโยคจริง 30 ประโยคผ่านโค้ด: กลุ่ม "ควรเข้าโหมดรายวัน"
            //   เข้าได้แค่ 6 จาก 12 — ที่เหลือตกไปเมนูราคา 39/99 หรือ AI chat
            $hasPending = Cache::has($this->dailyPendingKey($platform, $userId));
            $namedDayIndex = $this->resolveBirthDayNameIndex($messageText);
            $dailyIntent = $this->looksLikeDailyIntent($messageText);
            $coldDayName = ! $hasPending
                && ($namedDayIndex !== null || $this->looksLikeStandaloneDayName($messageText));

            // 🎯 (2026-08-27) ประตูที่ 4 — "ขาประจำสายรายวัน" ที่เรารู้วันเกิดอยู่แล้ว
            //
            //   เคสจริง แม่ฝน คำแจ่ม (PSID 28058628077130184, 27 ส.ค. 18:04):
            //     birth_day=4 นอนอยู่ใน fortune_user_credits ตั้งแต่ 15 ส.ค. (เธอกดปุ่มตอบไปแล้ว)
            //     วันนี้ทักมาว่า **"ดูค่ะ"** → ด่านนี้ตีตกทั้งสามประตู (ไม่มีธง pending เพราะ
            //     deploy รัน cache:clear ทิ้ง · ไม่มีชื่อวันในข้อความ · ไม่มีแกน "ดวง")
            //     → ตกไป AI chat ที่เสนอขายทันที ทั้งที่ resolveDayIndexFromShortYes()
            //     ห่างจากบรรทัดนี้ไป 55 บรรทัด พร้อมส่งกล่องดวงพฤหัสบดีให้อยู่แล้ว
            //
            //   👉 บทเรียน: ด่านนี้ถามแต่ "ข้อความพิสูจน์เจตนาได้ไหม" ไม่เคยถาม DB ว่า
            //      "คนนี้เป็นขาประจำเลนรายวันหรือเปล่า" — ตัวแยกบริบทที่ดีที่สุดคือ
            //      *เขามาจากไหน* ไม่ใช่ *เขาสะกดถูกไหม*
            //
            //   ⚠️ แคบไว้ 3 ชั้นโดยตั้งใจ:
            //     1. อ่าน fortune_user_credits.birth_day ตรง ๆ ผ่าน dailyLaneBirthDayIndex()
            //        **ห้ามใช้ findBirthDayIndex()** — ตัวนั้นหยิบวันเกิดจาก fortune_readings
            //        ของสาย Deep-39 มาด้วย (นับคนที่ไม่เคยแตะเลนรายวันเลย)
            //        วัดบน prod 2026-08-27: 3,415 แถวที่มี birth_day มาจากเลนรายวัน 100%
            //        (daily_dm_button 2,604 + daily_dm_text 811 · ไม่มี source อื่นเลย)
            //     2. ต้องเป็น "คำตอบรับสั้น ๆ ล้วน" (looksLikeShortYes) เท่านั้น
            //        ห้ามเปิดด้วยชื่อวันแบบ substring เด็ดขาด ไม่งั้น "วันพุธนี้จะไปหาหมอ"
            //        โดนกลืน — เคสห้ามติดที่ FortuneDailyColdDayNameTest ล็อกไว้
            //     3. ด่าน 3 (บิล/จ่ายเงิน/กำลังทำนาย) และด่าน 4 (escape) ยังวิ่งครบเหมือนเดิม
            //        ⇒ คนที่กำลังจะซื้อไม่มีทางถูกดึงมาเข้าเลนฟรี
            $dailyRegularYes = ! $hasPending
                && ! $coldDayName
                && ! $dailyIntent
                && $this->looksLikeShortYes($messageText)
                && $this->dailyLaneBirthDayIndex($platform, $userId) !== null;

            if (! $hasPending && ! $coldDayName && ! $dailyIntent && ! $dailyRegularYes) {
                return null;
            }

            if ($coldDayName) {
                Log::info('🌙 Daily: รับชื่อวันเดี่ยว ๆ ทั้งที่ไม่มีธง pending (ลูกค้าพิมพ์มาเอง)', [
                    'user_id' => $userId,
                    'platform' => $platform,
                    'text' => mb_substr($messageText, 0, 40),
                ]);
            }

            if ($dailyRegularYes) {
                Log::info('🌙 Daily: ขาประจำเลนรายวันตอบรับสั้น ๆ — รู้วันเกิดอยู่แล้ว', [
                    'user_id' => $userId,
                    'platform' => $platform,
                    'text' => mb_substr($messageText, 0, 40),
                ]);
            }

            // 3️⃣ 🚨 ลูกค้าจ่ายเงินแล้ว / มีบิล / กำลังทำนาย → ห้ามแตะเด็ดขาด
            //    ต้องเช็คทั้ง 3 ตัวเพราะครอบคนละช่วง:
            //      hasPaidActiveReading       = จ่ายแล้ว แต่มีกรอบ updated_at 2 ชม. (มีรูรั่ว)
            //      dailyBlockingReadingExists = ไม่มีกรอบเวลา ← ครอบ Deep-39 collecting_birthdate
            //      hasPendingUnpaidBill       = กำลัง checkout อยู่
            //
            // 🐛 (2026-08-17) ตัวกลางเคยเป็น FortuneReading::hasActiveReading() ซึ่งกว้างเกินไป
            //    จนกลืน "ยืนอ่านเมนูอยู่" (tier_choice) มาด้วย → ดูเคสจริงที่
            //    dailyBlockingReadingExists()
            if ($this->hasPaidActiveReading($userId)
                || $this->dailyBlockingReadingExists($platform, $userId)
                || $this->hasPendingUnpaidBill($userId)) {
                Log::info('🌙 Daily: ข้าม — ลูกค้ามีบิล/กำลังทำนายอยู่', [
                    'user_id' => $userId,
                ]);

                return null;
            }

            // 4️⃣ Escape hatch — ลูกค้าเปลี่ยนเรื่องแล้ว (แจ้งโอน/ขอคุยกับคน/ยกเลิก/ขอดูดวง)
            //    ลบธงทิ้งแล้วปล่อยให้ flow เดิมจัดการ ไม่ใช่ดันวันเกิดต่อ
            //    ⚠️ (2026-08-21) ยกเว้นเมื่อรู้แน่ว่าเป็นเจตนาดวงรายวัน — คำว่า "ดูดวง"
            //    อยู่ในลิสต์ escape ⇒ "อยากดูดวงรายวัน" เคยถูกตีตกที่นี่ แถมลบธง pending ทิ้งด้วย
            //    (ห้ามแก้ตัวฟังก์ชัน looksLikeDailyEscape เอง — มีเทสต์ล็อกไว้ และมันคือด่าน
            //     ที่กันคนอยากซื้อไม่ให้ติดอยู่ในโหมดฟรี)
            if (! $dailyIntent
                && $namedDayIndex === null
                && $this->looksLikeDailyEscape($messageText)) {
                $this->clearDailyPending($platform, $userId);

                return null;
            }

            // 5️⃣ ตีความคำตอบ — วันในสัปดาห์ หรือ วันเดือนปีเกิดเต็ม
            //    🎯 (2026-08-21) ถ้าจับชื่อวันจากประโยคได้แล้ว ใช้เลย ไม่ต้องวนหาซ้ำ
            //       (resolveDayIndexFromReply เทียบแบบ substring ซึ่งกว้างกว่าและเสี่ยงกว่า)
            $resolved = $namedDayIndex !== null
                ? [$namedDayIndex, null]
                : $this->resolveDayIndexFromReply($messageText);

            // 👍 (2026-08-01) ตอบรับสั้น ๆ แทนการกดปุ่ม ("เอาค่ะ" / "ดูเลย")
            //    คนที่เรารู้วันเกิดแล้วเห็นข้อความ "กดปุ่มด้านล่างได้เลย" แล้วพิมพ์ตอบแทน
            //    ถ้าไม่รับตรงนี้ คำเชิญจะกลายเป็นทางตัน — ลูกค้าตอบรับแล้วบอทเงียบ
            if ($resolved === null) {
                $resolved = $this->resolveDayIndexFromShortYes($messageText, $userId);
            }

            // 🎁 (2026-08-21) ขอดวงรายวันมาชัด ๆ แต่ยังไม่บอกวันเกิด
            //    → ยื่นกล่องดวงรายวัน (maybeOfferDailyForFreeRequest จัดการครบแล้วทั้งเคส
            //      "รู้วันเกิดแล้ว" / "ยังไม่รู้" / "บทความยังไม่พร้อม" — ห้ามเขียนใหม่)
            //    ⚠️ ต้องมีล็อกของตัวเอง เพราะด่านนี้วิ่งก่อน dedup และ mutex ของ processMessage
            //       ไม่งั้น FB retry หรือกดรัวจะยิงกล่องชวนซ้ำ
            if ($resolved === null && $dailyIntent) {
                if (! Cache::add("fortune:daily_intent_lock:{$platform}:{$userId}", true, 30)) {
                    Log::info('🌙 Daily: ขอดวงรายวันซ้ำเร็วเกิน — เบรกเงียบ', [
                        'user_id' => $userId,
                        'platform' => $platform,
                    ]);

                    return [
                        'action' => 'silent_skip',
                        'message' => null,
                        'reading' => null,
                    ];
                }

                Log::info('🌙 Daily: จับเจตนา "ขอดวงรายวัน" จากข้อความที่ลูกค้าพิมพ์เอง', [
                    'user_id' => $userId,
                    'platform' => $platform,
                    'text' => mb_substr($messageText, 0, 60),
                ]);

                return $this->maybeOfferDailyForFreeRequest($userId, $userProfile);
            }

            if ($resolved === null) {
                return null;   // ไม่ใช่คำตอบวันเกิด → คุยปกติ (ไม่ตื๊อ)
            }

            [$dayIndex, $fullDate] = $resolved;

            // 6️⃣ กันกดรัว/พิมพ์ซ้อน
            //    🚦 (2026-08-21) เดิมฮาร์ดโค้ด 8 วินาที ซึ่งเคสจริงกดห่าง 8-9 วินาที
            //       = พ้นล็อกทุกครั้ง ไม่ใช่ฟลุ๊ก (PSID 26463023433375768 กด 10+ ครั้งใน 2 นาที)
            //       ตอนนี้อ่านจาก settings (default 25) ปรับได้โดยไม่ต้อง deploy
            $answerLockSec = max(1, (int) ($this->settings->nav_flood_same_payload_lock_sec ?? 25));

            if (! Cache::add("fortune:daily_answer_lock:{$platform}:{$userId}", true, $answerLockSec)) {
                // 🔎 เดิมเงียบสนิทไม่มี log เลย — เวลามันทำงานจึงไม่มีหลักฐานย้อนหลัง
                Log::info('🌙 Daily: เบรกเงียบ (ตอบซ้ำเร็วเกิน)', [
                    'user_id' => $userId,
                    'platform' => $platform,
                    'lock_sec' => $answerLockSec,
                ]);

                return [
                    'action' => 'silent_skip',
                    'message' => null,
                    'reading' => null,
                ];
            }

            return $this->buildDailyHoroscopeReply($platform, $userId, $dayIndex, $fullDate, $userProfile);
        } catch (\Throwable $e) {
            // fail-open — ด่านเสริมพังต้องไม่บล็อกลูกค้า
            Log::warning('🌙 Daily: ด่านขาเข้าล้ม (ปล่อย flow เดิม)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ประกอบคำตอบ: กล่องดวงรายวัน (+ เก็บวันเกิดถ้ารู้ครบ ทำในก้อนถัดไป)
     */
    protected function buildDailyHoroscopeReply(
        string $platform,
        string $userId,
        int $dayIndex,
        ?string $fullDate,
        ?array $userProfile
    ): array {
        $name = (string) ($userProfile['first_name'] ?? $userProfile['name'] ?? 'คุณ');

        $greeting = app(FortuneGreetingService::class);
        $box = $greeting->buildDailyBoxForDayIndex($dayIndex, $name);

        // ❗ ห้ามเงียบใส่คนที่เพิ่งตอบเรา — ไม่มีบทความก็ต้องมีคำตอบ
        $message = $box['text'] ?? $greeting->buildDailyUnavailableMessage($name, $dayIndex);

        // 💾 เก็บวันเกิดถาวร — ครั้งเดียวได้ใช้ยาวทั้ง Deep/Celtic ทีหลัง
        $this->rememberDailyBirthInfo($platform, $userId, $dayIndex, $fullDate);

        // 🙏 คำอวยพรปิดท้าย — seed ด้วย uid+วันที่ ให้คนเดิมวันเดิมได้คำเดิม
        //    (ไม่สลับไปมาถ้าลูกค้าขอดูซ้ำในวันเดียวกัน)
        if ($blessing = $greeting->pickBlessing($userId.':'.now()->toDateString())) {
            $message .= "\n\n".$blessing;
        }

        // 🌱 เนียนชวนดูเชิงลึกต่อ — ต่างกันตามว่าเรารู้วันเกิดครบหรือยัง
        //    ห้ามฮาร์ดเซล ห้ามบอกราคา (rule_listen_dont_pitch_when_declining)
        //
        // 🐛 (2026-08-01) $fullDate = null แปลว่า "ข้อความนี้ไม่ได้แนบวันเดือนปีมา"
        //    ไม่ได้แปลว่าเราไม่รู้ — ปุ่ม DAILY_SHOW_MINE ส่งมาแค่ชื่อวัน ทั้งที่วันเกิดเต็ม
        //    อยู่ในระบบแล้ว เดิมจึงไปขอวันเดือนปีจากคนที่เพิ่งให้ไว้ = บอทเหมือนไม่จำอะไรเลย
        $knowsFullBirthdate = $fullDate !== null || $this->dailyKnowsFullBirthdate($userId);

        // 🌱 (2026-08-10, owner: "หลังจากรับดวงรายวันฟรีแล้ว อยากให้ชวนดูดวงละเอียดเนียน ๆ ด้วย")
        //    ย้ายจากประโยคตายตัว 2 แบบ → ตัวประกอบกลางที่หมุนสำนวน + เงียบเองเมื่อไม่ควรชวน
        //
        // 🐛 $box === null = ช่วงก่อน 06:00 / job สร้างบทความล่ม → ข้อความที่ส่งคือ
        //    buildDailyUnavailableMessage ("เดี๋ยวกลับมาใหม่นะคะ") ไม่ใช่คำทำนาย
        //    ต่อคำชวนที่ขึ้นต้นว่า "ดวงที่แม่หมออ่านให้เมื่อกี้..." = โกหกลูกค้าที่ยังไม่ได้อะไรเลย
        //    (บทเรียนเดียวกับ maybeInviteDeepAfterDailySent — daily_dm_answered_at ≠ ได้บทความแล้ว)
        $tail = $this->buildDailyDeepInviteTail(
            $platform,
            $userId,
            $dayIndex,
            $knowsFullBirthdate,
            $box !== null
        );

        if ($tail['text'] !== null) {
            $message .= "\n\n———\n".$tail['text'];
        }

        // ตอบแล้วปิดธง — ไม่ให้ข้อความถัดไปถูกตีเป็นวันเกิดอีก
        $this->clearDailyPending($platform, $userId);

        Log::info('🌙 Daily: ส่งดวงรายวันตามคำขอ', [
            'user_id' => $userId,
            'day_index' => $dayIndex,
            'day' => self::DAILY_DAY_NAMES[$dayIndex] ?? '?',
            'has_full_date' => $fullDate !== null,
            'has_article' => $box !== null,
            'stale_days' => $box['stale_days'] ?? null,
            'invite_text' => $tail['text'] !== null,
            'invite_button' => $tail['quick_replies'] !== [],
        ]);

        // 💎 ได้ของฟรีไปแล้ว = จังหวะที่ลูกค้าสนใจที่สุด — ต้องมีปุ่มให้กดต่อทันที
        //    ไม่งั้นคำชวน "ทักมาบอกได้เลยค่ะ" บังคับให้ลูกค้าพิมพ์เอง = เสียคนที่พร้อมจ่าย
        //    ⚠️ ปุ่มว่างปลอดภัย: FacebookWebhookService::sendQuickReplies เห็น array ว่าง
        //       จะ fallback เป็น sendMessage + no_default_qr = ไม่มีปุ่มแพคเกจลอยมาเกาะ
        return [
            'action' => 'daily_horoscope_sent',
            'message' => $message,
            'reading' => null,
            'daily_day_index' => $dayIndex,
            'quick_replies' => $tail['quick_replies'],
        ];
    }

    /**
     * 🌱 (2026-08-10) หาง "ชวนดูเชิงลึกเนียน ๆ" ที่ต่อท้ายดวงรายวันฟรี
     *
     * owner: "หลังจากรับดวงรายวันฟรีแล้ว อยากให้ชวนดูดวงละเอียดเนียน ๆ ด้วย"
     *
     * เดิมเป็นประโยคตายตัว 2 แบบท้าย buildDailyHoroscopeReply ซึ่งมี 3 ปัญหา:
     *   1. ไม่หมุนสำนวน — คนที่รับดวงทุกวันเห็นบรรทัดเดิมเป๊ะทุกวัน = อ่านเป็นแบนเนอร์โฆษณา
     *      (ตัวชวนอีก 2 ตัวในไฟล์นี้ pickDailyFreeOffer / buildDailyToDeepInvite หมุนอยู่แล้ว)
     *   2. ไม่มีเหตุผลรองรับ — ไม่ได้บอกว่าดวงรายวันคือ "ดวงรวมของคนเกิดวันเดียวกัน"
     *      ลูกค้าจึงคิดว่า "ก็ได้ไปแล้วนี่" แล้วคำชวนกลายเป็นการขายของเดิมซ้ำ
     *      (นี่คือประโยคเดียวที่ทำให้การชวนต่อเป็น "การบอกตามตรง" ไม่ใช่ "การขาย")
     *   3. ยื่นปุ่ม 👑 VIP ให้ทุกคนเสมอ แม้ตอนที่ deep+celtic ปิดทั้งคู่ (กดแล้วไม่มีของ)
     *      และแม้กับขาประจำที่เพิ่งจ่ายไปเมื่อวาน
     *
     * 🚨 เงียบทั้งข้อความและปุ่มเมื่อ:
     *   - deep + celtic ปิดทั้งคู่ → ไม่มีอะไรให้ชวน (ด่านเดียวกับ maybeInviteDeepAfterDailySent)
     *   - เพิ่งจ่ายภายใน 7 วัน → ขาประจำ ห้ามตื๊อ (owner 2026-06-17: "อย่าตื้อให้ซื้ออีก
     *     ทักแบบขาประจำ แต่ถ้าเขาอยากดูเอง ก็เปิดบิลให้ตามปกติ")
     *     ใช้ระยะ 7 วันแบบ rolling ตัวเดียวกับ DM guard ไม่ใช่เดือนปฏิทิน
     *
     * 🔕 เงียบเฉพาะข้อความ (ปุ่มยังอยู่) เมื่อวันนี้ชวนไปแล้วรอบหนึ่ง —
     *    ดวงรายวันขอซ้ำได้ไม่จำกัดต่อวัน (maybeOfferDailyForFreeRequest) การแปะคำชวน
     *    ทุกรอบคือการตื๊อ แต่ปุ่มเงียบ ๆ ไว้ให้คนที่พร้อมจ่ายกดเองยังต้องมี
     *
     * 🐛 เงียบเฉพาะข้อความอีกกรณี: $articleDelivered = false (ยังไม่มีบทความให้ส่ง)
     *    ทุกสำนวนอ้างถึง "ดวงที่เพิ่งอ่านให้/ใบนี้" — ต่อท้ายข้อความ "ยังไม่พร้อม" = โกหก
     *    ปุ่มยังยื่นได้ เพราะคนที่อยากจ่ายตอนนี้ไม่ต้องรอบทความรายวัน
     *
     * @param  string|null  $userId  null = ไม่รู้ตัวตน (ข้ามด่านขาประจำ/กันชวนซ้ำ ชวนตามปกติ)
     * @param  bool  $articleDelivered  ข้อความที่กำลังจะส่ง มีคำทำนายจริงอยู่ในนั้นไหม
     * @return array{text: string|null, quick_replies: array<int, array>}
     */
    protected function buildDailyDeepInviteTail(
        string $platform,
        ?string $userId,
        int $dayIndex,
        bool $knowsFullBirthdate,
        bool $articleDelivered = true
    ): array {
        $silent = ['text' => null, 'quick_replies' => []];

        try {
            // 1️⃣+2️⃣ มีของให้ชวนจริง และไม่ใช่ขาประจำที่เพิ่งจ่าย
            if (! $this->dailyUpgradeInviteAllowed($platform, $userId)) {
                return $silent;
            }

            $buttons = [static::dailyUpgradeQuickReply()];

            // 3️⃣ ยังไม่ได้ส่งคำทำนายจริง → ห้ามอ้างถึง "ดวงที่เพิ่งอ่านให้"
            //    ทุกสำนวนขึ้นต้นด้วย "ดวงที่แม่หมออ่านให้เมื่อกี้/ใบนี้" — ต่อท้ายข้อความ
            //    "วันนี้ยังไม่มีบทความ" = โกหกคนที่ยังไม่ได้อะไรเลย · ปุ่มยังยื่นได้ตามเดิม
            if (! $articleDelivered) {
                return ['text' => null, 'quick_replies' => $buttons];
            }

            // 🚧 (2026-08-19) เจ้าของสั่ง "ทำให้มันออกมาในรูปแบบนี้เสมอ เมื่อรับดวงฟรีรายวัน"
            //    เดิมมีด่าน markDailyInviteShownToday() ตรงนี้: ชวนได้วันละครั้ง
            //    ครั้งที่ 2 ของวันจะเหลือแค่ปุ่ม ไม่มีหางคำชวน → กล่องหน้าตาไม่เหมือนกัน
            //    ซึ่งคือความไม่สม่ำเสมอที่เจ้าของเห็น (ลูกค้าขอดวงฟรีซ้ำได้ไม่จำกัดต่อวัน
            //    ตาม rule_free_request_never_hits_paywall จึงเจอเคสนี้บ่อย)
            //    ถอดออกแล้ว — สำนวนหมุนตาม seed uid+วันที่อยู่แล้ว คนเดิมวันเดิมได้คำเดิม
            //    ไม่ใช่การพูดซ้ำคนละแบบให้รำคาญ

            return [
                'text' => $this->pickDailySoftDeepInvite(
                    ($userId ?? 'anon').':'.now()->toDateString(),
                    $dayIndex,
                    $knowsFullBirthdate
                ),
                'quick_replies' => $buttons,
            ];
        } catch (\Throwable $e) {
            // เช็คไม่ได้ → เงียบไว้ก่อน ลูกค้ายังได้ดวงฟรีครบเหมือนเดิม
            // (พลาดชวน 1 ครั้ง เสียหายน้อยกว่าชวนใส่คนที่เพิ่งจ่าย/ชวนไปหาของที่ปิดอยู่)
            Log::warning('🌱 Daily: ประกอบหางคำชวนล้ม (ส่งดวงเปล่า ๆ)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $silent;
        }
    }

    /**
     * 🚦 ยื่นคำชวน/ปุ่ม 👑 VIP ให้คนนี้ตอนนี้ได้ไหม
     *
     * 2 ด่าน (แยกออกมาเพราะทั้งหางคำชวนและการ์ด teaser ต้องใช้เกณฑ์เดียวกัน —
     * ไม่งั้นปุ่มโผล่ในการ์ดหนึ่งแต่หายในอีกการ์ด ทั้งที่เป็นบทสนทนาเดียวกัน):
     *
     *   1. deep/celtic ต้องเปิดอย่างน้อย 1 อย่าง — ปุ่มพาไป tier menu ที่ไม่มีอะไรขาย
     *      = ลูกค้ากดแล้วเจอทางตัน (ด่านเดียวกับข้อ 4 ของ maybeInviteDeepAfterDailySent)
     *   2. ไม่ใช่ขาประจำที่เพิ่งจ่ายภายใน 7 วัน (owner 2026-06-17 "อย่าตื้อให้ซื้ออีก")
     *
     * เช็คไม่ได้ → false (เงียบ) — ลูกค้ายังได้ดวงฟรีครบเหมือนเดิม
     */
    protected function dailyUpgradeInviteAllowed(string $platform, ?string $userId): bool
    {
        try {
            $deepEnabled = (bool) $this->settings->isDeepReadingEnabled();
            $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);

            if (! $deepEnabled && ! $celticEnabled) {
                return false;
            }

            if ($userId !== null && $userId !== '' && $this->dailyRecentlyPaid($platform, $userId)) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 💤 เพิ่งจ่ายค่าดูดวงไปภายใน 7 วันหรือยัง — ใช้ปิดคำชวนใส่ขาประจำ
     *
     * ⚠️ ต้องเป็น hasPaidReadingWithinDays ไม่ใช่ hasPaidReadingThisCalendarMonth —
     *    ตัวเดือนปฏิทินรีเซ็ตตอนขึ้นเดือนใหม่ (จ่าย 31 ก.ค. → 1 ส.ค. โดนขายอีก)
     *    ดูเหตุผลเต็มใน FortuneReading::hasPaidReadingWithinDays
     *
     * เช็คไม่ได้ → ถือว่า "ยังไม่เคยจ่าย" (ชวนตามปกติ) เพราะการเงียบใส่ลูกค้าใหม่
     * เสียโอกาสมากกว่าการชวนขาประจำเกินไป 1 ครั้ง
     */
    protected function dailyRecentlyPaid(string $platform, string $userId, int $days = 7): bool
    {
        try {
            return FortuneReading::hasPaidReadingWithinDays(
                $platform === 'facebook' ? $userId : null,
                $platform === 'facebook' ? null : $userId,
                $days
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 🔕 ประทับว่า "วันนี้ชวนดูเชิงลึกไปแล้ว" — คืน true เฉพาะครั้งแรกของวัน
     *
     * Cache::add เป็น atomic check-and-set ในตัว (กันข้อความที่มาพร้อมกันชวนซ้ำ)
     * คีย์มีวันที่อยู่แล้ว TTL จึงแค่กันขยะสะสม
     *
     * แคชล้ม → คืน true (ชวนตามปกติ) — พฤติกรรมเดิมก่อนมีด่านนี้
     */
    protected function markDailyInviteShownToday(string $platform, string $userId): bool
    {
        try {
            return (bool) Cache::add(
                "fortune:daily_deep_invite_shown:{$platform}:{$userId}:".now()->toDateString(),
                true,
                now()->endOfDay()
            );
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * 🌱 คำชวนดูเชิงลึกท้ายดวงรายวัน — หมุนสำนวนตามคน+วัน
     *
     * สูตรของทุกสำนวน (ห้ามตัดข้อไหนออกตอนเพิ่มสำนวนใหม่):
     *   1. **บอกตามตรงว่านี่คือดวงรวม** ของคนเกิดวันเดียวกัน — เหตุผลที่ทำให้การชวนต่อ
     *      ไม่ใช่การขายของที่เพิ่งให้ไปฟรี ๆ ถ้าไม่มีข้อนี้ ลูกค้าอ่านแล้วคิดว่า "ก็ได้ไปแล้วนี่"
     *   2. ชวน 1 จังหวะแล้วหยุด ห้ามตื๊อ ห้ามรัวหลายประโยคขาย
     *   3. **ห้ามฝังตัวเลขราคา** — แอดมินแก้ราคาได้ ปุ่ม 👑 พาไป tier menu ซึ่งเป็นเจ้าของราคาตัวจริง
     *   4. แม่หมอเป็นหญิงเสมอ ห้าม ครับ/ผม/ดิฉัน · เรียกลูกค้าว่า "เจ้าชะตา"
     *
     * ⚠️ สาย "ยังไม่รู้วันเกิดเต็ม" ห้ามกลับไปสัญญาว่า "บอกวันเกิดมาแล้วจะดูให้ละเอียดกว่านี้"
     *    ประโยคนั้นคือต้นเหตุบั๊ก 2026-08-04 — บทความรายวันผูกกับ *วันในสัปดาห์* วันเกิดเต็ม
     *    ของคนเดิมให้ dayIndex เดิม = บทความใบเดิมเป๊ะ ๆ คำสัญญาจึงวนที่เดิมเสมอ
     *    ที่นี่จึงขอวันเกิดโดยบอกตามจริงว่า "เก็บไว้ให้ ใช้ตอนเปิดไพ่เฉพาะตัว" เท่านั้น
     *
     * @param  string  $seed  คนเดิม+วันเดิม ต้องได้สำนวนเดิม (ขอดูซ้ำในวันเดียวกันไม่สลับไปมา)
     */
    protected function pickDailySoftDeepInvite(string $seed, int $dayIndex, bool $knowsFullBirthdate): string
    {
        // ⚠️ dayIndex นอกช่วง 0-6 ต้องยังอ่านรู้เรื่อง — เตรียมสำนวนสำรองไว้ทั้ง 2 รูป
        //    ("ของคน..." ใช้ขยายคำนำหน้า / "คน..." ใช้เป็นประธานของประโยค)
        $day = self::DAILY_DAY_NAMES[$dayIndex] ?? '';
        $ofDay = $day !== '' ? 'ของคนเกิดวัน'.$day : 'ของคนเกิดวันเดียวกัน';
        $peopleOfDay = $day !== '' ? 'คนเกิดวัน'.$day : 'คนที่เกิดวันเดียวกัน';

        $lines = $knowsFullBirthdate
            ? [
                "💫 ดวงที่แม่หมออ่านให้เมื่อกี้ เป็นดวงรวม{$ofDay}ทั้งหมดนะคะ\n"
                    ."เกิดวันเดียวกันก็จริง แต่เวลาตกฟากไม่เหมือนกันสักคน\n"
                    .'อยากให้แม่หมอเปิดไพ่ดูเฉพาะเจ้าชะตาคนเดียวไหมคะ กดปุ่มด้านล่างได้เลย ✨',

                "💫 ดวงรวม{$ofDay}บอกได้แค่ลมฟ้าอากาศของวันนี้นะคะ\n"
                    ."ส่วนเรื่องที่ค้างอยู่ในใจเจ้าชะตา ต้องเปิดไพ่เฉพาะตัวถึงจะเห็นว่าติดตรงไหน\n"
                    .'พร้อมเมื่อไหร่ กดปุ่มด้านล่างบอกแม่หมอได้เลยค่ะ ✨',

                "💫 อ่านแล้วมีตรงไหนสะดุดใจไหมคะ\n"
                    ."ใบนี้เป็นดวงรวม{$ofDay}ทุกคน — ถ้าอยากรู้ว่าของเจ้าชะตาเองต่างออกไปตรงไหน\n"
                    .'แม่หมอเปิดไพ่เจาะเรื่องนั้นให้ได้ กดปุ่มด้านล่างได้เลยนะคะ ✨',

                "💫 วันนี้{$peopleOfDay}ได้ดวงรวมใบเดียวกันหมดค่ะ\n"
                    ."แต่ดวงของเจ้าชะตามีดาวเจ้าเรือนของตัวเอง ที่ใบรวมนี้บอกไม่ได้\n"
                    .'วันไหนอยากให้แม่หมอเปิดดูให้ลึกกว่านี้ กดปุ่มด้านล่างได้เลย ✨',
            ]
            : [
                "💫 ใบนี้เป็นดวงรวม{$ofDay}ทั้งหมดนะคะ\n"
                    ."ถ้าเจ้าชะตาบอกวัน/เดือน/ปีเกิดครบมา แม่หมอจะจดเก็บไว้ให้ —\n"
                    .'วันไหนอยากให้เปิดไพ่ดูเฉพาะตัว จะได้เริ่มได้ทันทีไม่ต้องถามใหม่ค่ะ ✨',

                "💫 ดวงที่ส่งไปเป็นดวงรวม{$ofDay}นะคะ ยังไม่ใช่ดวงของเจ้าชะตาคนเดียว\n"
                    ."อยากให้แม่หมอเปิดไพ่เฉพาะตัว ขอวัน/เดือน/ปีเกิดครบ ๆ ไว้ก่อนได้ไหมคะ\n"
                    .'หรือถ้าอยากเริ่มเลย กดปุ่มด้านล่างได้เลยค่ะ ✨',

                "💫 รู้แค่วันในสัปดาห์ แม่หมอเปิดได้แค่ดวงรวม{$ofDay}ทั้งหมดค่ะ\n"
                    ."จะดูให้เจาะจงถึงตัวเจ้าชะตา ต้องมีวัน/เดือน/ปีเกิดครบ\n"
                    .'ทิ้งไว้ให้แม่หมอสักบรรทัดนะคะ เก็บไว้ใช้ได้ยาว ๆ ✨',

                "💫 ใบนี้ใช้แค่วันในสัปดาห์ เลยเป็นดวงรวม{$ofDay}ทุกคนนะคะ\n"
                    ."ถ้าเจ้าชะตาฝากวัน/เดือน/ปีเกิดเต็ม ๆ ไว้ แม่หมอจะเก็บไว้ให้\n"
                    .'วันไหนพร้อมให้เปิดไพ่เฉพาะตัว บอกมาได้เลยค่ะ ✨',
            ];

        return $lines[crc32($seed) % count($lines)];
    }

    /**
     * เรารู้ "วันเดือนปีเกิดเต็ม" ของลูกค้าคนนี้อยู่แล้วไหม
     *
     * ใช้ตัดสินว่าจะขอวันเกิดเพิ่มหรือไม่ — ถามซ้ำกับคนที่ให้ไว้แล้วดูแย่กว่าไม่ถาม
     * ผิดพลาด → ถือว่าไม่รู้ (กลับไปขอ) ปลอดภัยกว่าอ้างว่าจดไว้แล้วทั้งที่ไม่มี
     *
     * 🚨 **ห้ามเปลี่ยนไปใช้ FortuneUserCredit::findBirthDayIndex()** (2026-08-04)
     *   ตัวนั้นนับ "รู้แค่วันในสัปดาห์" ว่ารู้ด้วย — พอมาถึงตรงนี้บอทจะพูดว่า
     *   "แม่หมอจดวันเกิดของเจ้าชะตาไว้แล้วนะคะ" ใส่คนที่กดปุ่มบอกแค่ "วันพุธ"
     *   แล้วพอเขาซื้อ Deep/Celtic จริง flow ต้องถาม ว/ด/ป ใหม่ = บอทโกหกลูกค้า
     *   ตรงนี้ต้องเป็น "รู้ ว/ด/ป ครบ" เท่านั้น จึงต้องคง findLatestBirthdate ไว้
     */
    protected function dailyKnowsFullBirthdate(string $userId): bool
    {
        try {
            return FortuneReading::findLatestBirthdate($userId) instanceof \Carbon\Carbon;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 💾 เก็บวันเกิดที่ได้จากโหมด daily ลง fortune_user_credits
     *
     * 🚨 กติกาที่ห้ามพลาด: **ห้ามทับข้อมูลที่มาจาก reading ที่จ่ายเงินแล้ว**
     *   ลูกค้าที่เคยกรอกวันเกิดตอนซื้อ Deep/Celtic = ข้อมูลที่เชื่อถือได้กว่า
     *   ถ้า parser ตีความคำตอบฟรีผิด แล้วไปทับ = ทำลายข้อมูลลูกค้าจ่ายเงิน
     *   (findLatestBirthdate อ่าน readings ก่อนอยู่แล้ว แต่กันไว้อีกชั้นที่ต้นทาง)
     */
    protected function rememberDailyBirthInfo(
        string $platform,
        string $userId,
        int $dayIndex,
        ?string $fullDate
    ): void {
        try {
            $row = \App\Models\FortuneUserCredit::getOrCreate($userId, $platform);

            $payload = [
                'birth_day' => $dayIndex,
                'birth_date_at' => now(),
                'daily_dm_answered_at' => now(),
                'birth_date_source' => $fullDate !== null ? 'daily_dm_text' : 'daily_dm_button',
            ];

            // เก็บวันเกิดเต็มเฉพาะเมื่อ (ก) ได้มาจริง และ (ข) ยังไม่เคยมีของเดิม
            if ($fullDate !== null && empty($row->birth_date)) {
                $payload['birth_date'] = $fullDate;
            }

            $row->forceFill($payload)->save();
        } catch (\Throwable $e) {
            // เก็บไม่ได้ก็ไม่เป็นไร — ลูกค้าต้องได้คำทำนายก่อนเสมอ
            Log::warning('🌙 Daily: เก็บวันเกิดไม่สำเร็จ', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ตีความคำตอบของลูกค้าเป็น index วันเกิด (0-6)
     *
     * รับ 2 แบบ:
     *   1. วันในสัปดาห์ — "จันทร์" / "วันจันทร์" / "เกิดวันพุธค่ะ"
     *   2. วันเดือนปีเกิดเต็ม — ใช้ parseBirthDate ตัวเดิม (ผ่านการซ่อมปี 2 หลักแล้ว)
     *
     * ⚠️ ลองแบบวันในสัปดาห์ก่อนเสมอ เพราะถูกและไม่เรียก AI
     *    (parseBirthDate มี AI fallback ที่กินเวลา 0.7-7.5 วิ ถ้า regex ไม่เจอ)
     */
    protected function resolveDayIndexFromReply(string $text): ?array
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return null;
        }

        // ── แบบที่ 2 ก่อน: วันเดือนปีเกิดเต็ม (มีค่ากว่า — เก็บถาวรได้)
        //    เรียก parser ตัวเต็มเฉพาะตอนที่ "หน้าตาเป็นวันที่" เท่านั้น
        //    กันข้อความทั่วไปไปโดน AI fallback (0.7-7.5 วิ บนเส้น webhook)
        if ($this->looksLikeDateInput($trimmed)) {
            $parsed = $this->parseBirthDate($trimmed);

            if ($parsed !== null) {
                try {
                    return [\Carbon\Carbon::parse($parsed)->dayOfWeek, $parsed];
                } catch (\Throwable $e) {
                    // parse วันที่ไม่ได้ → ตกไปลองชื่อวันด้านล่าง
                }
            }
        }

        // ── แบบที่ 1: ชื่อวันในสัปดาห์ (ไม่มีวันเดือนปี = เก็บถาวรไม่ได้)
        $dayIndex = $this->detectThaiDayName($trimmed);
        if ($dayIndex !== null) {
            return [$dayIndex, null];
        }

        return null;
    }

    /**
     * 👍 (2026-08-01) "ตอบรับสั้น ๆ" ของคนที่เรารู้วันเกิดแล้ว = เท่ากับกดปุ่มดูดวงวันนี้
     *
     * เคสจริง: เรายื่น "ดวงของคุณพร้อมแล้ว กดปุ่มด้านล่างได้เลย" → ลูกค้าพิมพ์ "เอาค่ะ"
     * แทนการกดปุ่ม → เดิมไหลไป AI chat = ลูกค้าตอบรับแล้วไม่ได้ของที่เราเพิ่งสัญญา
     *
     * 🚨 แคบไว้ 2 ชั้นโดยตั้งใจ:
     *   1. ต้อง **รู้วันเกิดอยู่แล้ว** — ไม่รู้ = ไม่รู้จะส่งดวงวันไหน ปล่อย flow เดิม
     *   2. เทียบแบบ **ตรงตัวเป๊ะ** หลังตัดคำลงท้าย ไม่ใช่ substring
     *      → "ไม่เอา" ไม่ match "เอา" · "ค่ะ" เปล่า ๆ ไม่นับเป็นการตอบรับ (กำกวมเกินไป)
     *
     * 🌙 (2026-08-04) ข้อ 1 ใช้ findBirthDayIndex ไม่ใช่ findLatestBirthdate —
     *   คนที่เห็นข้อความ "กดปุ่มด้านล่างได้เลย" แล้วพิมพ์ตอบแทน คือกลุ่มเดียวกับที่เคย
     *   ตอบเราด้วยปุ่ม (มีแต่ birth_day) ถ้าเช็คด้วยวันเกิดเต็ม คนกลุ่มนี้จะตอบรับแล้วบอทเงียบ
     *
     * @return array{0: int, 1: null}|null [index วันเกิด, ไม่มีวันเดือนปีใหม่]
     */
    protected function resolveDayIndexFromShortYes(string $text, string $userId): ?array
    {
        if (! $this->looksLikeShortYes($text)) {
            return null;
        }

        $platform = $this->currentPlatform ?? 'facebook';
        $dayIndex = \App\Models\FortuneUserCredit::findBirthDayIndex($userId, $platform);

        if ($dayIndex === null) {
            return null;
        }

        return [$dayIndex, null];
    }

    /**
     * 🌙 (2026-08-27) วันเกิดที่ได้มาจาก **เลนรายวันเท่านั้น** — ใช้เป็นตัวแยกบริบท
     *
     * ต่างจาก FortuneUserCredit::findBirthDayIndex() ตรงที่ตัวนั้นลอง
     * FortuneReading::findLatestBirthdate() ก่อน ⇒ กินวันเกิดของสาย Deep-39
     * (คนที่ไม่เคยแตะเลนรายวันเลย) มาด้วย ซึ่งกว้างเกินไปสำหรับด่านที่ 2
     *
     * คอลัมน์ fortune_user_credits.birth_day เขียนจาก 2 ที่เท่านั้น คือ
     * daily_dm_button / daily_dm_text — วัดบน prod 2026-08-27 ได้ 2,604 + 811 = 3,415 แถว
     * ไม่มี source อื่นปน ⇒ "มีค่า" = "เคยเดินผ่านเลนรายวันมาแล้ว" แบบไม่ต้องเดา
     *
     * fail-safe = null ("ไม่รู้") — ปล่อยให้ flow เดิมทำงาน ดีกว่าเปิดประตูมั่ว
     *
     * @return int|null 0=อาทิตย์ … 6=เสาร์
     */
    protected function dailyLaneBirthDayIndex(string $platform, string $userId): ?int
    {
        try {
            if (! Schema::hasColumn('fortune_user_credits', 'birth_day')) {
                return null;
            }

            $day = \App\Models\FortuneUserCredit::byUser($userId, $platform)
                ->whereNotNull('birth_day')
                ->value('birth_day');

            return \App\Models\FortuneUserCredit::normalizeBirthDayIndex($day);
        } catch (\Throwable $e) {
            // ⚠️ ห้ามกลืนเงียบ — บทเรียนจาก buildReturningCustomerContext ที่ query พังมา
            //   หลายเดือนโดยไม่มีใครเห็น เพราะ catch ไม่เคยพูดอะไรออกมาเลย
            Log::warning('🌙 Daily: อ่าน birth_day ของเลนรายวันไม่ได้ (ถือว่าไม่รู้)', [
                'user_id' => $userId,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ข้อความตอบรับสั้น ๆ ล้วน (ไม่มีเนื้อหาอื่น) — เทียบตรงตัวเท่านั้น
     */
    protected function looksLikeShortYes(string $text): bool
    {
        $clean = mb_strtolower(trim($text));

        if ($clean === '' || mb_strlen($clean) > 24) {
            return false;
        }

        // ตัดคำลงท้าย/ตัวขยายท้ายประโยคออกก่อนเทียบ (วนหลายรอบ: "เอาเลยค่ะ" → "เอา")
        for ($i = 0; $i < 3; $i++) {
            $clean = trim((string) preg_replace(
                '/\s*(?:'.self::DAILY_PARTICLE_RE.'|เลย|ด้วย|หน่อย|ที|สิ|ๆ|!|\.)\s*$/u',
                '',
                $clean
            ));
        }

        return in_array($clean, self::DAILY_SHORT_YES, true);
    }

    /**
     * จับชื่อวันในสัปดาห์ภาษาไทย → index 0-6
     *
     * ⚠️ "อาทิตย์" ต้องเช็คก่อน "อังคาร" ไม่ได้ แต่ต้องระวัง "พฤหัส" ที่เป็นคำนำของ
     *    "พฤหัสบดี" — ใช้ลำดับที่ยาวกว่ามาก่อนเพื่อไม่ให้จับผิดคำ
     */
    protected function detectThaiDayName(string $text): ?int
    {
        foreach (self::DAILY_DAY_ALIASES as $needle => $index) {
            if (mb_strpos($text, $needle) !== false) {
                return $index;
            }
        }

        return null;
    }

    /**
     * ข้อความนี้คือ "ชื่อวันเดี่ยว ๆ" ที่ลูกค้าพิมพ์มาเองใช่ไหม
     *
     * 🆕 (2026-08-02, owner) ใช้เปิดทางให้คนที่ทักมาเองว่า "พุธ" ได้ดวงฟรี
     *    ทั้งที่เราไม่เคยทักไปถาม (ไม่มีธง daily_pending)
     *
     * ⚠️ ต้องเทียบ *เต็มคำ* หลังปอกเปลือกแล้วเท่านั้น ห้าม substring —
     *    ไม่งั้น "วันพุธนี้จะไปหาหมอค่ะ" หรือ "ศุกร์นี้เงินเดือนออกไหม" จะถูกกลืน
     *    ไปเป็นดวงรายวัน ทั้งที่เป็นคำถามที่ควรเข้า flow ปกติ
     *
     * ปอกอะไรบ้าง: อีโมจิ/สัญลักษณ์ (เช่น "🟢 พุธ") · คำนำหน้า (เกิดวัน/วันเกิด/วัน)
     * · คำลงท้ายสุภาพ (ค่ะ/ครับ/จ้า)
     */
    protected function looksLikeStandaloneDayName(string $text): bool
    {
        $t = trim($text);

        // ยาวเกินนี้ = เป็นประโยค ไม่ใช่คำตอบชื่อวัน (ชื่อวันยาวสุด "พฤหัสบดี" 9 ตัว)
        if ($t === '' || mb_strlen($t) > 25) {
            return false;
        }

        // เหลือเฉพาะตัวอักษร/ตัวเลข — อีโมจิ เครื่องหมาย @ ฯลฯ กลายเป็นช่องว่าง
        // 🇹🇭 ต้องมี \p{M} ด้วย! สระ/วรรณยุกต์ไทย (ุ ิ ่ ้) เป็น Mark ไม่ใช่ Letter
        //    ถ้าตกไป "พุธ" จะถูกหั่นเป็น "พ ธ" แล้วเทียบไม่ติดสักคำ
        $t = preg_replace('/[^\p{L}\p{N}\p{M}\s]/u', ' ', $t) ?? $t;
        $t = trim(preg_replace('/\s+/u', ' ', $t) ?? $t);

        // เผื่อ mention ของแพลตฟอร์มหลุดด่าน stripPlatformAiMention มาได้ (กันเหนียว)
        $t = preg_replace('/^\s*meta\s*ai\s*/iu', '', $t) ?? $t;

        // ปอกคำนำหน้า/คำลงท้ายที่คนไทยพิมพ์ติดมาเป็นปกติ
        //   ⚠️ ลำดับใน alternation สำคัญ — คำเต็มต้องมาก่อนรูปสะกดตกหล่นเสมอ
        //      ไม่งั้น "วัน" จะถูก "ว[ัา][นณ]?" กินไปแบบไม่ครบคำ
        $t = preg_replace(
            '/^(เกิดวัน|วันเกิด|เกิด|วัน|'.self::DAILY_DAY_PREFIX_TYPO_RE.')\s*/u',
            '',
            $t
        ) ?? $t;
        $t = preg_replace('/\s*(?:'.self::DAILY_PARTICLE_RE.')+$/u', '', $t) ?? $t;
        $t = preg_replace('/\s*'.self::DAILY_TIME_OF_DAY_RE.'$/u', '', $t) ?? $t;
        $t = trim($t);

        return $t !== '' && array_key_exists($t, self::DAILY_DAY_ALIASES);
    }

    /**
     * 🎯 (2026-08-21) ข้อความนี้ "ขอดวงรายวัน" หรือเปล่า — ตัวจับ *เจตนา* ไม่ใช่ตัวจับ *ป้ายปุ่ม*
     *
     * ของเดิม looksLikeDailyFreeRequest() บังคับว่าต้องมีคำว่า "ฟรี" **และ** ("ประจำวัน" หรือ "รายวัน")
     * ⇒ รับได้แค่ป้ายปุ่มของเราเอง · ลูกค้าที่พิมพ์ "อยากดูดวงรายวัน" / "ดวงประจำวัน" ตกหมด
     * แล้วไปโดน isGenericFortuneRequest ลากเข้าเมนูราคา 39/99 แทน (ขอของฟรีได้เมนูราคา)
     *
     * โครงใหม่: **แกนดวง + รอบเวลา** — "ฟรี" ไม่ใช่เงื่อนไขบังคับอีกต่อไป
     *   กลุ่มแข็ง (รายวัน/ประจำวัน/daily) → รับทันที
     *   กลุ่มอ่อน (วันนี้) → ต้องมีคำชี้เจตนา + ข้อความสั้น (กัน "วันนี้ดวงตกมากเลย เล่าให้ฟังหน่อย")
     *
     * ⚠️ "ดวงฟรี" ที่ไม่ระบุรอบเวลา จงใจคืน false — ยกให้ไพ่ฟรี 1 ใบ (matchesFreeCardKeyword)
     *
     * ✅ (2026-08-21) เลน LINE เปิดแล้ว — `FortuneBotMode::DAILY_PLATFORMS` = [facebook, line]
     *    ตอนเปิดต้องแก้ครบ 6 จุดพร้อมกัน ไม่งั้นได้ปุ่มตาย/ลูกค้าโดนปิดปาก:
     *      1. `FortuneBotMode::dailyReplyAllowedFor()` + `buildDailyReadingForDetectedBirthdate()`
     *      2. `FortuneChannelManager` arm `'daily_horoscope_sent'` — เดิมทิ้ง quick_replies ทั้งชุด
     *      3. `stripFortuneStartQuickReplies()` — ลบปุ่มที่ป้ายมีคำว่า "ดูดวง" และไม่มีตัวเลข
     *         ⇒ กินปุ่ม `🔮 ดูดวงวันนี้เลย` (DAILY_SHOW_MINE) ทิ้งเงียบ ๆ
     *      4. postback router ของ LINE — ต้องมี case `DAILY_*`
     *      5. ป้ายปุ่มที่ไหลกลับมาเป็น text (LINE quick reply = `type=message` ไม่มี payload)
     *      6. whitelist สแปมฝั่ง LINE — ชื่อวันไทยยาว 5-7 ตัว ไม่รอดกฎ `mb_strlen <= 4`
     */
    protected function looksLikeDailyIntent(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        // ปอกอีโมจิ (ป้ายปุ่มมี 🎁 / 🔮 นำหน้า) — ใช้ range เดียวกับ looksLikeDailyFreeRequest
        $t = preg_replace(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}\x{2190}-\x{21FF}\x{FE00}-\x{FE0F}\x{1F1E6}-\x{1F1FF}\x{200D}\x{20E3}]/u',
            '',
            $t
        ) ?? $t;

        $t = trim(preg_replace('/\s+/u', ' ', $t) ?? $t);

        // ยาวกว่านี้ = เล่าเรื่อง ไม่ใช่คำขอ
        if ($t === '' || mb_strlen($t) > 60) {
            return false;
        }

        // ❌ ด่านตัดสิทธิ์ — เงิน / แพคเกจ / ปฏิเสธ / ราคา / แอดมิน
        //    ต้องเช็คก่อนทุกอย่าง ไม่งั้น "ดูดวงรายวันกับ 99 อันไหนดี" จะถูกชิงไปเป็นของฟรี
        foreach ([
            '39', '99', 'โอน', 'จ่าย', 'ชำระ', 'สลิป', 'บิล',
            'ราคา', 'เท่าไร', 'เท่าไหร่', 'กี่บาท', 'ค่าครู', 'ค่าบูชา',
            'เชิงลึก', 'ละเอียด', 'celtic', 'vip', 'คุณไสย',
            'ไม่อยาก', 'ไม่เอา', 'ไม่ดู', 'ไม่ต้อง', 'ยกเลิก', 'คืนเงิน',
            'แอดมิน', 'คุยกับคน',
        ] as $bad) {
            if (mb_strpos($t, $bad) !== false) {
                return false;
            }
        }

        // ❌ คำประสมที่มี "ดวง" แต่ไม่ใช่การขอดูดวง
        foreach ([
            'ดวงตา', 'ดวงไฟ', 'ดวงดาว', 'ดวงจันทร์', 'ดวงอาทิตย์',
            'ดวงวิญญาณ', 'ดวงแก้ว', 'ดวงเพชร', 'ดวงเดือน',
        ] as $compound) {
            if (mb_strpos($t, $compound) !== false) {
                return false;
            }
        }

        // ต้องมีแกนดวงเสมอ
        if (! preg_match('/(ดวง|ทำนาย|ชะตา|horoscope)/u', $t)) {
            return false;
        }

        // ✅ กลุ่มแข็ง — ระบุรอบเวลาชัดเจน
        if (preg_match('/(รายวัน|ประจำวัน|ประจําวัน|ราย ?วัน|ประจำ ?วัน|daily)/u', $t)) {
            return true;
        }

        // ✅ กลุ่มอ่อน — "วันนี้" ต้องคู่กับคำชี้เจตนา และข้อความต้องสั้น
        if (preg_match('/(วันนี้|ของวันนี้|today)/u', $t)
            && mb_strlen($t) <= 30
            && preg_match('/(ขอ|อยาก|ดู|เช็ค|ช่วย|รบกวน|เอา|มี|ไหม|มั้ย|หน่อย|เป็นไง|เป็นยังไง|เป็นอย่างไร|ยังไง)/u', $t)) {
            return true;
        }

        return false;
    }

    /**
     * 🎂 (2026-08-21) หา index วันเกิดจากประโยคที่ลูกค้าพิมพ์เอง — กว้างกว่า looksLikeStandaloneDayName
     *
     * ทำไมต้องมีตัวใหม่แทนที่จะแก้ตัวเดิม: ตัวเดิมมีเทสต์ล็อกไว้ 15 assertion
     * (tests/Unit/Services/FortuneDailyColdDayNameTest.php) และมันบังคับให้ทั้งข้อความ
     * เป็นชื่อวันล้วน ⇒ "ผมเกิดวันอังคารครับ" ตกเพราะมีสรรพนามนำหน้า
     * และ "วันเกิดวันจันทร์" ก็ตก เพราะ regex ปอกคำนำหน้าได้ชั้นเดียว (anchored ^)
     *
     * ตัวชี้ขาดของตัวใหม่ = คำว่า **"เกิด"** ต้องติดกับชื่อวัน
     *   ⇒ "วันพุธนี้จะไปหาหมอ" / "ศุกร์นี้เงินเดือนออกไหม" / "วันจันทร์ที่ผ่านมาแฟนทิ้ง" ยังตกเหมือนเดิม
     *
     * 🇹🇭 ต้องมี \p{M} ใน character class! สระ/วรรณยุกต์ไทยเป็น Mark ไม่ใช่ Letter
     *     ถ้าตกไป "พุธ" จะถูกหั่นเป็น "พ ธ" แล้วเทียบไม่ติด (บทเรียนเดิมของ looksLikeStandaloneDayName)
     *
     * @return int|null 0=อาทิตย์ … 6=เสาร์ · null = ไม่ใช่การบอกวันเกิด
     */
    protected function resolveBirthDayNameIndex(string $text): ?int
    {
        $t = trim($text);

        if ($t === '' || mb_strlen($t) > 60) {
            return null;
        }

        // 🚫 มีเลขปี = เป็นวันเดือนปีเกิดเต็ม → เป็นงานของ parseBirthDate ไม่ใช่ของดวงรายวัน
        //    (กันไม่ให้ดวงรายวันแย่งข้อความของ Deep 39 ที่ต้องการ ว/ด/ป ครบ)
        if (preg_match('/(?:^|\D)(19\d{2}|20\d{2}|25\d{2})(?:\D|$)/u', $t)) {
            return null;
        }

        // 🚫 ระบุหัวข้อเฉพาะ = ต้องการดวงเฉพาะเรื่อง → flow ขาย ไม่ใช่ดวงรวมรายวัน
        if (preg_match('/(ความรัก|เนื้อคู่|การงาน|การเงิน|สุขภาพ|ธุรกิจ|คดี|หวย|เลขเด็ด)/u', $t)) {
            return null;
        }

        // 🚫 บริบทเงิน (ชั้นที่ 2 — ชั้นแรกคือ guard บิล/จ่ายเงินด้านบน)
        if (preg_match('/(39|99|โอน|จ่าย|ชำระ|สลิป|บิล)/u', $t)) {
            return null;
        }

        // คงตัวอักษร/ตัวเลข/Mark — อีโมจิ @ ฯลฯ กลายเป็นช่องว่าง
        $t = preg_replace('/[^\p{L}\p{N}\p{M}\s]/u', ' ', $t) ?? $t;
        $t = trim(preg_replace('/\s+/u', ' ', $t) ?? $t);
        $t = preg_replace('/^\s*meta\s*ai\s*/iu', '', $t) ?? $t;

        // ── ทาง A: ชื่อวันเดี่ยว ๆ หลังปอกสรรพนาม/คำนำหน้า/คำลงท้าย (ปอกซ้ำได้หลายชั้น)
        //    ซ่อมเคส "วันเกิดวันจันทร์" ที่ regex ของตัวเดิมปอกได้ชั้นเดียว
        $peeled = $t;
        for ($i = 0; $i < 3; $i++) {
            $peeled = preg_replace('/^(ผม|ฉัน|ดิฉัน|หนู|เรา|กระผม|ข้าพเจ้า|อิฉัน|นู๋)\s*/u', '', $peeled) ?? $peeled;
            // ⚠️ คำเต็มต้องมาก่อนรูปสะกดตกหล่นเสมอ ไม่งั้น "วัน" โดนกินไม่ครบคำ
            $peeled = preg_replace(
                '/^(เกิดวันที่|เกิดวัน|วันเกิดคือ|วันเกิดเป็น|วันเกิด|เกิด|วัน|'.self::DAILY_DAY_PREFIX_TYPO_RE.')\s*/u',
                '',
                $peeled
            ) ?? $peeled;
        }
        for ($i = 0; $i < 3; $i++) {
            $peeled = trim(preg_replace(
                '/\s*(?:'.self::DAILY_PARTICLE_RE.')$/u',
                '',
                $peeled
            ) ?? $peeled);
            $peeled = trim(preg_replace(
                '/\s*'.self::DAILY_TIME_OF_DAY_RE.'$/u',
                '',
                $peeled
            ) ?? $peeled);
        }

        if ($peeled !== '' && array_key_exists($peeled, self::DAILY_DAY_ALIASES)) {
            return self::DAILY_DAY_ALIASES[$peeled];
        }

        // ── ทาง B: "เกิด" ติดกับชื่อวัน แม้อยู่กลางประโยค
        //    DAILY_DAY_ALIASES เรียงยาว→สั้นอยู่แล้ว alternation จึงจับคำยาวก่อน
        $days = implode('|', array_map(
            static fn ($d) => preg_quote($d, '/'),
            array_keys(self::DAILY_DAY_ALIASES)
        ));

        // (?![\p{L}\p{M}]) กัน alias สั้น 3 ตัว (จัน/เสา/ศุก) ไปติดใน "จันทบุรี" / "เสาไฟ" / "ศุกร์"
        //   ⚠️ ต้องมี \p{M} ด้วย ไม่งั้นสระ/วรรณยุกต์ที่ตามมาไม่ถูกนับเป็นตัวกั้น
        $pattern = '/(?:เกิด|วันเกิด)\s*(?:คือ|เป็น|ตรงกับ|ที่)?\s*(?:วัน)?\s*('
            .$days
            .')(?![\p{L}\p{M}])\s*(นี้|หน้า|ที่แล้ว|ที่ผ่านมา|ก่อน)?/u';

        if (preg_match($pattern, $t, $m) === 1) {
            // "เกิดวันจันทร์ที่ผ่านมา" = เล่าเรื่อง ไม่ใช่วันเกิด
            if (! empty($m[2])) {
                return null;
            }

            return self::DAILY_DAY_ALIASES[$m[1]] ?? null;
        }

        return null;
    }

    /**
     * "หน้าตาเป็นวันที่" ไหม — ด่านเช็คเร็วก่อนเรียก parseBirthDate ตัวเต็ม
     *
     * ต้องมีเลขอย่างน้อย 2 กลุ่ม (วัน+ปี) หรือ เลข + ชื่อเดือนไทย
     * กันข้อความทั่วไป ("สวัสดีค่ะ") และเบอร์โทรเดี่ยว ๆ ไปโดน AI fallback
     */
    protected function looksLikeDateInput(string $text): bool
    {
        // เลข 2 กลุ่มขึ้นไป คั่นด้วย / - . หรือช่องว่าง
        if (preg_match('/\d{1,4}\s*[\/\-.\s]\s*\d{1,4}/u', $text)) {
            return true;
        }

        // เลข + ชื่อเดือนไทย (เต็ม/ย่อ)
        if (preg_match('/\d{1,2}\s*(มกรา|กุมภา|มีนา|เมษา|พฤษภา|มิถุนา|กรกฎา|สิงหา|กันยา|ตุลา|พฤศจิกา|ธันวา|ม\.?ค|ก\.?พ|มี\.?ค|เม\.?ย|พ\.?ค|มิ\.?ย|ก\.?ค|ส\.?ค|ก\.?ย|ต\.?ค|พ\.?ย|ธ\.?ค)/u', $text)) {
            return true;
        }

        return false;
    }

    /**
     * ลูกค้าเปลี่ยนเรื่องแล้ว — ต้องปล่อยให้ flow เดิมจัดการ
     */
    protected function looksLikeDailyEscape(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        foreach ([
            'โอนแล้ว', 'จ่ายแล้ว', 'สลิป', 'โอนเงิน',
            'คุยกับคน', 'คุยกับแอดมิน', 'ติดต่อแอดมิน', 'เรียกแอดมิน',
            'ยกเลิก', 'ไม่เอา', 'คืนเงิน',
            'ดูดวง', 'เช็คสถานะ', 'เช็คบิล', 'บิล',
        ] as $kw) {
            if (mb_strpos($t, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตั้งธง "ถามวันเกิดไปแล้ว" — เรียกจากขาออก DM (ก้อนถัดไป) และตอนกดปุ่ม
     */
    public function markDailyPending(string $platform, string $userId): void
    {
        try {
            Cache::put(
                $this->dailyPendingKey($platform, $userId),
                true,
                now()->addDays(self::DAILY_PENDING_TTL_DAYS)
            );
        } catch (\Throwable $e) {
            Log::warning('🌙 Daily: ตั้งธง pending ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    public function clearDailyPending(string $platform, string $userId): void
    {
        try {
            Cache::forget($this->dailyPendingKey($platform, $userId));
        } catch (\Throwable $e) {
            // ไม่เป็นไร — ธงหมดอายุเองใน 7 วัน
        }
    }

    protected function dailyPendingKey(string $platform, string $userId): string
    {
        return "fortune:daily_dm_pending:{$platform}:{$userId}";
    }

    /**
     * 🙏 (2026-07-31) ลูกค้าขอบคุณมา → ตอบด้วยคำอวยพร
     *
     * owner: "คำอวยพร...ไว้ใช้ตอนลูกค้าขอบคุณด้วย"
     *
     * ทำงานทุกโหมด (ไม่ใช่แค่ daily) เพราะเป็นมารยาทพื้นฐานของแม่หมอ
     *
     * 🚨 ห้ามแทรกลูกค้าที่จ่ายเงินแล้ว/กำลังทำนาย — flow พวกนั้นมีคำตอบของตัวเอง
     *    (Celtic มี ack handler ของตัวเองอยู่แล้วที่ CelticCrossConversationTrait:3873)
     *
     * @return array|null null = ไม่ใช่คำขอบคุณล้วน หรือไม่ควรตอบตอนนี้
     */
    protected function maybeBlessOnThanks(string $userId, string $messageText): ?array
    {
        try {
            if (! $this->looksLikePureThanks($messageText)) {
                return null;
            }

            $platform = $this->currentPlatform ?? 'facebook';

            // มีบิล/กำลังทำนาย → ปล่อย flow เดิมตอบเอง
            if ($this->hasPaidActiveReading($userId)
                || FortuneReading::hasActiveReading($platform, $userId)
                || $this->hasPendingUnpaidBill($userId)) {
                return null;
            }

            // ตอบคำอวยพรครั้งเดียวต่อ 6 ชม. — ลูกค้าขอบคุณรัว ๆ ไม่ควรได้พรรัว ๆ
            if (! Cache::add("fortune:bless_thanks:{$platform}:{$userId}", true, 6 * 3600)) {
                return null;
            }

            $blessing = app(FortuneGreetingService::class)
                ->pickBlessing($userId.':'.now()->toDateString());

            if ($blessing === '') {
                return null;
            }

            // 🌱 owner: "การดูดวงฟรีถ้าลูกค้าขอบคุณ ต้องไม่หยุด เหมือนกรณีลูกค้า
            //   ขอบคุณหลังชำระเงิน ต้องเนียนคุยต่อเพื่อลองเสนอการดูดวงแบบเสียเงินบูชาครู"
            //   → คำอวยพรอย่างเดียว = ปิดบทสนทนา ต้องมีประโยคเปิดทางต่อเสมอ
            //   ⚠️ แต่ห้ามคุยเรื่อยเปื่อย (บทเรียน Free-Chat Wind-Down "บอทคุยไม่หยุด
            //      น่ารำคาญมาก") → เสนอทางเลือกชัด ๆ 1 ประโยค แล้วหยุด ให้ลูกค้าตัดสินใจ
            // 🚦 (2026-08-10) ประโยคชวน + ปุ่มขาย ใช้เกณฑ์เดียวกับหางดวงรายวัน
            //    deep/celtic ปิดทั้งคู่ = ชวนไปหาของที่ไม่มี · เพิ่งจ่ายใน 7 วัน = ตื๊อขาประจำ
            //    (owner 2026-06-17: "คนเก่าที่จ่ายแล้ว **ในแชท** อย่าตื้อให้ซื้ออีก ทักแบบขาประจำ")
            //
            // ⚠️ แต่ห้ามเงียบจนบทสนทนาตาย — owner สั่งไว้อีกข้อว่า "ลูกค้าขอบคุณ ต้องไม่หยุด"
            //    2 คำสั่งนี้ชนกันเฉพาะกับขาประจำ → ทางออกคือคุยต่อแบบไม่ขาย
            $canInvite = $this->dailyUpgradeInviteAllowed($platform, $userId);

            $message = "🙏 ด้วยความยินดีค่ะ\n\n".$blessing."\n\n"
                .($canInvite
                    ? $this->pickThanksFollowUp($userId)
                    : $this->pickRegularWarmFollowUp($userId));

            Log::info('🙏 Daily: ลูกค้าขอบคุณ → คำอวยพร'.($canInvite ? ' + ชวนดูเชิงลึก' : ' + คุยต่อแบบไม่ขาย'), [
                'user_id' => $userId,
                'can_invite' => $canInvite,
            ]);

            // 💎 มีปุ่มให้กดคู่กับประโยคชวน — ลูกค้าที่พร้อมจ่ายไม่ต้องพิมพ์เอง
            //    (LINE ไม่มีปุ่ม → ChannelManager ส่งเป็นข้อความล้วน ประโยคชวนยังอยู่ครบ)
            return [
                'action' => 'daily_horoscope_sent',
                'message' => $message,
                'reading' => null,
                'quick_replies' => $canInvite ? [static::dailyUpgradeQuickReply()] : [],
            ];
        } catch (\Throwable $e) {
            return null;   // fail-open
        }
    }

    /**
     * 🌱 ประโยคเปิดทางคุยต่อหลังคำอวยพร — เสนอดูดวงบูชาครูแบบเนียน
     *
     * กติกา:
     *   - เสนอทางเลือกชัด 1 ประโยค แล้วหยุด ห้ามรัวหลายประโยค
     *     (บทเรียน Free-Chat Wind-Down: "บอทคุยไม่หยุด น่ารำคาญมาก")
     *   - ไม่บอกตัวเลขราคา — ให้ระบบราคาจริงเป็นคนบอกตอนลูกค้าสนใจ
     *     (ราคาเปลี่ยนได้จากแอดมิน ฝังตัวเลขไว้จะเพี้ยนทันทีที่แก้)
     *   - หมุนหลายสำนวน ลูกค้าประจำจะได้ไม่เห็นประโยคเดิมซ้ำ
     *
     * 🚦 (2026-08-10) ใช้เฉพาะตอน dailyUpgradeInviteAllowed() = true
     *    ขาประจำที่เพิ่งจ่าย/ช่วงบริการปิด ใช้ pickRegularWarmFollowUp() แทน
     */
    protected function pickThanksFollowUp(string $userId): string
    {
        $lines = [
            '💫 ถ้าอยากให้แม่หมอเปิดไพ่ดูให้ลึกกว่านี้ แบบบูชาครู ทักมาบอกได้เลยนะคะ',
            '💫 วันไหนอยากรู้ลึกกว่านี้ แม่หมอเปิดไพ่ให้แบบเต็ม ๆ ได้ค่ะ บอกมาได้เลย',
            '💫 ถ้ามีเรื่องไหนค้างใจอยู่ ลองให้แม่หมอเปิดไพ่ดูให้ชัด ๆ ไหมคะ',
            '💫 อยากให้ดูเจาะเรื่องไหนเป็นพิเศษ บอกแม่หมอได้นะคะ เดี๋ยวเปิดไพ่ให้',
            '💫 ถ้าอยากได้คำตอบที่ชัดกว่านี้ แม่หมอเปิดไพ่ดูให้แบบละเอียดได้ค่ะ',
            '💫 มีเรื่องไหนอยากให้แม่หมอดูให้ลึก ๆ ไหมคะ ทักมาคุยกันได้เลย',
        ];

        return $lines[crc32($userId.':'.now()->toDateString()) % count($lines)];
    }

    /**
     * 🌿 (2026-08-10) ประโยคเปิดทางคุยต่อ **แบบไม่ขาย** — สำหรับขาประจำที่เพิ่งจ่ายไป
     *    หรือช่วงที่ deep/celtic ปิดทั้งคู่
     *
     * ทำไมต้องมีแยกจาก pickThanksFollowUp: คำสั่ง owner 2 ข้อชนกันตรงกลุ่มนี้พอดี
     *   - "ลูกค้าขอบคุณ ต้องไม่หยุด" (ห้ามจบด้วยคำอวยพรเปล่า ๆ = ปิดบทสนทนา)
     *   - "คนเก่าที่จ่ายแล้วในเดือนนี้ อย่าตื้อให้ซื้ออีก ทักแบบขาประจำ"
     * → คุยต่อ แต่ไม่มีคำขาย ไม่มีปุ่มขาย ไม่มีราคา
     */
    protected function pickRegularWarmFollowUp(string $userId): string
    {
        $lines = [
            '🌿 มีอะไรอยากคุยกับแม่หมออีกไหมคะ ถามได้เสมอนะ',
            '🌿 ถ้ามีเรื่องไหนอยากเล่าให้แม่หมอฟัง ทักมาได้ทุกเมื่อค่ะ',
            '🌿 แม่หมออยู่ตรงนี้เสมอนะคะ อยากคุยเรื่องไหนก็บอกได้',
            '🌿 วันนี้เป็นยังไงบ้างคะ อยากเล่าอะไรให้แม่หมอฟังก็ได้นะ',
        ];

        return $lines[crc32($userId.':'.now()->toDateString()) % count($lines)];
    }

    /**
     * "ขอบคุณ" ล้วน ๆ ไหม (ไม่มีคำถามตามหลัง)
     *
     * ⚠️ ตัดคำลงท้าย/intensifier ออกก่อนตัดสิน — "ขอบคุณค่ะ แล้วเรื่องงานล่ะ"
     *    ต้องไม่ถูกกินเป็นคำขอบคุณล้วน (บทเรียนเคส R4543 ใน Celtic ack handler)
     */
    protected function looksLikePureThanks(string $text): bool
    {
        $clean = mb_strtolower(trim($text));

        if ($clean === '' || mb_strlen($clean) > 40) {
            return false;
        }

        foreach (['ขอบพระคุณ', 'ขอบคุณ', 'ขอบคุน', 'ขอบใจ', 'thank you', 'thankyou', 'thanks', 'thx'] as $thx) {
            if (! str_starts_with($clean, mb_strtolower($thx))) {
                continue;
            }

            $rest = trim(mb_substr($clean, mb_strlen($thx)));
            $rest = trim((string) preg_replace('/^(?:มากมาย|มาก|จริง|หลาย|เด้อ|งับ|ฮะ|ค้าบ|ๆ)+/u', '', $rest));

            for ($i = 0; $i < 3; $i++) {
                $rest = trim((string) preg_replace(
                    '/\s*(ค่ะ|คะ|ค่า|ครับ|คับ|จ้า|จ้ะ|จ๊ะ|จ๋า|นะ|น่ะ|เลย|ละ|ล่ะ|ฮะ|แม่หมอ|แม่|หมอ|ๆ|!|\.)\s*$/u',
                    '',
                    $rest
                ));
            }

            return $rest === '' || mb_strlen($rest) <= 2;
        }

        return false;
    }

    /**
     * 🎂 (2026-07-31) ลูกค้าพิมพ์วันเกิดมาเองนอก flow จ่ายเงิน → ให้ดวงรายวันก่อน แล้วค่อยชวน
     *
     * owner: "ถ้าลูกค้าพิมพ์วันที่มา นอกการชำระเงินเพื่อทำนาย ให้ส่งดวงรายวันให้
     *         และชวนดูดวงเนียน ๆ ได้ไหม"
     *
     * เดิม: เจอวันเกิด → เด้งขาย "ดูเชิงลึกไหม 39 บาท" ทันที = ขอแล้วขายเลย
     * ใหม่: ให้ของฟรีที่เขาควรได้ก่อน (ดวงวันนี้ของวันเกิดนั้น) แล้วค่อยชวนแบบเบา ๆ
     *
     * ⚠️ ผู้เรียกต้องอยู่ในบริบท "นอก flow จ่ายเงิน" อยู่แล้ว (จุดเรียกทั้ง 2 จุด
     *    อยู่หลังกำแพง active reading/บิลค้างมาแล้ว) เมธอดนี้จึงไม่เช็คซ้ำ
     *
     * @param  string  $birthDate  Y-m-d ที่ parse ได้แล้ว
     * @param  string|null  $userId  ใส่มาเพื่อจดวันเกิด + ธง "วันนี้ส่งดวงให้แล้ว" (ดูหมายเหตุในตัวเมธอด)
     * @return string|null null = ไม่ใช่โหมด daily หรือวันนี้ยังไม่มีบทความ → ใช้ข้อความเดิม
     */
    protected function buildDailyReadingForDetectedBirthdate(
        string $birthDate,
        ?array $userProfile = null,
        ?string $userId = null
    ): ?string {
        try {
            $platform = $this->currentPlatform ?? 'facebook';

            if (! (new FortuneBotMode($this->settings))->isDaily()) {
                return null;
            }

            // 🌙 (2026-08-21) เปิด LINE ด้วย — ต้องเปิดพร้อม dailyReplyAllowedFor เสมอ
            //    ถ้าเปิดแค่ตัวนั้น: คนที่เคยได้ดวงรายวันแล้วพิมพ์วันเกิดเต็ม จะได้กล่องชวน
            //    ดูเชิงลึก (maybeInviteDeepAfterDailySent ผ่านแล้ว) แต่คนที่**ยังไม่เคยได้**
            //    จะตกมาที่นี่แล้วโดนตีตก → ไหลไปกล่อง "ค่าครู 39 บาท" = คนที่ควรได้ของฟรี
            //    ที่สุดกลับเป็นคนเดียวที่เจอใบเสนอราคา
            if (! in_array($platform, FortuneBotMode::DAILY_PLATFORMS, true)) {
                return null;
            }

            $dayIndex = \Carbon\Carbon::parse($birthDate)->dayOfWeek;
            $name = (string) ($userProfile['first_name'] ?? $userProfile['name'] ?? 'คุณ');

            $greeting = app(FortuneGreetingService::class);
            $box = $greeting->buildDailyBoxForDayIndex($dayIndex, $name);

            if ($box === null) {
                return null;   // วันนี้ยังไม่มีบทความ → ปล่อยข้อความเดิมทำงาน
            }

            // 🙏 คำอวยพรปิดท้าย — seed ด้วยวันเกิด+วันที่ ให้คงที่ในวันเดียวกัน
            $blessing = $greeting->pickBlessing($birthDate.':'.now()->toDateString());

            // 💾 (2026-08-04) จดวันเกิดเต็ม + ประทับเวลา "วันนี้ส่งดวงรายวันให้คนนี้แล้ว"
            //    ถ้าไม่จด ลูกค้าพิมพ์วันเกิดซ้ำอีกรอบจะได้บทความใบเดิมกลับไปอีก
            //    (บั๊กเดียวกับที่ maybeInviteDeepAfterDailySent แก้ — แค่คนละประตูเข้า)
            if ($userId !== null && $userId !== '') {
                $this->rememberDailyBirthInfo($platform, $userId, $dayIndex, $birthDate);
            }

            Log::info('🎂 Daily: ลูกค้าพิมพ์วันเกิดมาเอง → ส่งดวงรายวันให้ก่อน', [
                'day_index' => $dayIndex,
                'birth_date' => $birthDate,
            ]);

            // 🌱 (2026-08-10) เนียนชวนต่อ — ใช้ตัวประกอบกลางตัวเดียวกับดวงรายวันฟรี
            //    (หมุนสำนวน + เงียบเองเมื่อ deep/celtic ปิดทั้งคู่ หรือเป็นขาประจำที่เพิ่งจ่าย)
            //    เส้นนี้ปุ่มมาจากชุด default ของ action birthdate_detected (show_quick_replies)
            //    จึงหยิบมาแค่ 'text' ไม่แตะ quick_replies ของผู้เรียก
            //
            //    ⚠️ ตัดประโยคเดิม "นี่คือดวงประจำวันของเจ้าชะตานะคะ" ทิ้ง — มันขัดกับคำชวน
            //    ที่บอกตามตรงว่าใบนี้เป็น "ดวงรวม" ของคนเกิดวันเดียวกัน ไม่ใช่ดวงเฉพาะตัว
            $tail = $this->buildDailyDeepInviteTail($platform, $userId, $dayIndex, true);

            // ทวนว่ารับวันเกิดแล้ว — เฉพาะตอนที่จดลงระบบจริง (มี $userId) ไม่งั้นเป็นคำโกหก
            $ack = ($userId !== null && $userId !== '')
                ? '🎂 แม่หมอจดวันเกิดของเจ้าชะตาไว้แล้วนะคะ'
                : '🎂 แม่หมอรับวันเกิดของเจ้าชะตาแล้วค่ะ';

            return $box['text']
                .($blessing !== '' ? "\n\n".$blessing : '')
                ."\n\n———\n".$ack
                .($tail['text'] !== null ? "\n\n".$tail['text'] : '');
        } catch (\Throwable $e) {
            Log::warning('🎂 Daily: สร้างดวงจากวันเกิดที่ลูกค้าพิมพ์ล้ม', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 💎 (2026-08-04) เพิ่งได้ดวงรายวันไปวันนี้ แล้วส่ง "วันเดือนปีเกิดเต็ม" ตามที่แม่หมอขอ
     *    → ห้ามส่งดวงรายวันใบเดิมซ้ำ ให้ชวนดูเชิงลึกแทน
     *
     * owner: "หลังจากรับคำทำนายรายวันไปแล้ว บอทบอกให้ส่งวันเดือนปีเกิดละเอียด พอผู้ใช้ส่ง
     *         ก็กลับไปส่งดวงรายวันเหมือนเดิมอีก ให้เปลี่ยนเป็นการชักชวนเข้าการดูดวงแบบละเอียดแทน"
     *
     * ต้นเหตุ: ท้ายกล่องดวงรายวันมีประโยค "ถ้าบอกวัน/เดือน/ปีเกิดเต็ม ๆ มาด้วย แม่หมอจะดูให้
     *   ละเอียดกว่านี้ได้อีกเยอะ" (buildDailyHoroscopeReply) — ลูกค้าทำตาม แต่วันเกิดนั้นไหลไป
     *   parseStandaloneBirthdate → buildDailyReadingForDetectedBirthdate ซึ่งเปิดบทความ
     *   "ของคนเกิดวันเดียวกัน" = **ใบเดิมเป๊ะ ๆ ที่เพิ่งส่งไปเมื่อกี้** → คำสัญญากลายเป็นวนที่เดิม
     *
     * 🚨 แคบไว้โดยตั้งใจ — ยิงเฉพาะเมื่อครบทั้ง 4 ข้อ:
     *   1. อยู่ในเลนดวงรายวัน (Facebook + ไม่ใช่โหมด transfer)
     *   2. **วันนี้** ส่งดวงรายวันให้คนนี้ไปแล้วจริง (daily_dm_answered_at)
     *   3. วันเกิดที่เพิ่งส่งมา ตรงกับวันที่เราเปิดดวงให้ (birth_day) = ยืนยันว่าเป็นใบเดิม
     *      คนละวัน = เนื้อหาคนละใบ ปล่อยให้ได้ของใหม่ตามเดิม
     *   4. มีบริการเชิงลึกเปิดอยู่จริง — ชวนไปหาของที่ปิดอยู่ = เสียลูกค้าเปล่า ๆ
     *
     * ⚠️ ไม่ใส่ตัวเลขราคา — ปุ่ม 💎 พาไป tier menu ซึ่งเป็นเจ้าของราคาตัวจริง
     *    (แพทเทิร์นเดียวกับ dailyUpgradeQuickReply — ฝังเลขไว้ = เพี้ยนทันทีที่แอดมินแก้ราคา)
     *
     * @param  string  $birthDate  Y-m-d ที่ parse ได้แล้ว
     * @return array|null null = ไม่ใช่เคสนี้ ปล่อย flow เดิมทำงานต่อ
     */
    protected function maybeInviteDeepAfterDailySent(string $userId, string $birthDate): ?array
    {
        try {
            $platform = $this->currentPlatform ?? 'facebook';

            // 1️⃣ เลนดวงรายวัน (เช็คถูกที่สุดก่อน — ไม่แตะ DB)
            if (! (new FortuneBotMode($this->settings))->dailyReplyAllowedFor($platform, $userId)) {
                return null;
            }

            // 4️⃣ ไม่มีของให้ชวนเลย → ปล่อยข้อความเดิม (ทางเดิมมีสำนวน "ปิดชั่วคราว" อยู่แล้ว)
            $deepEnabled = (bool) $this->settings->isDeepReadingEnabled();
            $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);

            if (! $deepEnabled && ! $celticEnabled) {
                return null;
            }

            $row = \App\Models\FortuneUserCredit::findByUser($userId, $platform);

            // 2️⃣ ยังไม่เคยได้ดวงรายวันจากเรา → ให้ของฟรีก่อนตามเดิม (อย่าเพิ่งขาย)
            if (! $row || empty($row->daily_dm_answered_at) || $row->birth_day === null) {
                return null;
            }

            if (! $row->daily_dm_answered_at->isSameDay(now())) {
                return null;   // ส่งไปเมื่อวาน/ก่อนหน้า → วันนี้ยังมีสิทธิ์ได้ของใหม่
            }

            // 3️⃣ ใบเดิมจริงไหม
            $dayIndex = \Carbon\Carbon::parse($birthDate)->dayOfWeek;

            if ((int) $row->birth_day !== $dayIndex) {
                return null;   // คนละวันเกิด = คนละบทความ → ส่งให้ตามปกติ
            }

            // 🐛 กันบอทโกหก: daily_dm_answered_at แปลว่า "ลูกค้าตอบวันเกิดกลับมา" ไม่ใช่
            //    "ได้บทความไปแล้ว" — buildDailyHoroscopeReply ประทับเวลานี้แม้ตอนที่ไม่มีบทความ
            //    แล้วส่ง buildDailyUnavailableMessage ไปแทน (ช่วงก่อน 06:00 / job สร้างบทความล่ม)
            //    ถ้าไม่เช็ค เราจะพูดว่า "ดวงที่เพิ่งส่งไป..." ใส่คนที่ยังไม่เคยได้ดวงเลย
            //    ⚠️ ต้องเช็คของ "วันเกิดวันนี้" จริง ๆ ไม่ใช่ dailyArticlesReadyToday() —
            //       ตัวนั้นดูแค่ว่าวันนี้มีบทความไหม แต่กล่องดวงย้อนหลังได้ 2 วัน
            //       (ลูกค้าได้ของเก่าที่บอกตามตรง = ได้ของจริง ต้องนับว่าส่งแล้ว)
            if (app(FortuneGreetingService::class)->buildDailyBoxForDayIndex($dayIndex, 'คุณ') === null) {
                return null;
            }

            // 💾 เก็บวันเกิดเต็มที่ลูกค้าเพิ่งให้ — เขาให้เพราะเราสัญญาว่าจะดูให้ลึกขึ้น
            //    (rememberDailyBirthInfo กันทับข้อมูลของลูกค้าที่จ่ายเงินแล้วไว้ให้อยู่แล้ว)
            $this->rememberDailyBirthInfo($platform, $userId, $dayIndex, $birthDate);

            Log::info('💎 Daily: ได้วันเกิดเต็มหลังส่งดวงรายวันแล้ว → ชวนดูเชิงลึก (ไม่ส่งดวงซ้ำ)', [
                'user_id' => $userId,
                'day_index' => $dayIndex,
                'birth_date' => $birthDate,
            ]);

            // ใช้ action ของเลนดวงรายวัน — FortuneChannelManager ส่งปุ่มชุดนี้ตรง ๆ
            // โดยไม่มีปุ่มแพคเกจ default ลอยมาเกาะ (ดู sendFacebookResponse: daily_horoscope_sent)
            // ธง daily_upgrade_invite ไว้แยกในล็อก/วิเคราะห์ ว่าไม่ใช่การส่งบทความรายวัน
            return [
                'action' => 'daily_horoscope_sent',
                'daily_upgrade_invite' => true,
                'message' => $this->buildDailyToDeepInvite($userId, $birthDate, $dayIndex),
                'reading' => null,
                'quick_replies' => [static::dailyUpgradeQuickReply()],
                'pending_birthdate' => $birthDate,
            ];
        } catch (\Throwable $e) {
            // fail-open — ด่านเสริมพังต้องไม่ทำให้ลูกค้าไม่ได้คำตอบ
            Log::warning('💎 Daily: ชวนเชิงลึกหลังดวงรายวันล้ม (ปล่อย flow เดิม)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 💎 ข้อความชวนดูเชิงลึก หลังลูกค้าให้วันเกิดเต็มตามที่แม่หมอขอ
     *
     * กติกาของข้อความนี้:
     *   - "รับของ" ให้ชัดก่อน (ทวนวันเกิดที่จดไว้) — ลูกค้าเพิ่งทำตามที่เราขอ ต้องรู้สึกว่าถึงมือ
     *   - บอกตรง ๆ ว่าดวงรายวัน = ดวงรวมของคนเกิดวันเดียวกัน ไม่งั้นลูกค้าคิดว่า "ก็ได้ไปแล้วนี่"
     *     (นี่คือเหตุผลเดียวที่ทำให้การชวนต่อไม่ใช่การขายซ้ำของเดิม)
     *   - ชวน 1 ประโยคแล้วหยุด ห้ามตื๊อ ห้ามบอกราคา (rule_listen_dont_pitch_when_declining)
     *   - หมุนสำนวนตามคน+วัน ลูกค้าประจำจะได้ไม่เห็นประโยคเดิมซ้ำ
     */
    protected function buildDailyToDeepInvite(string $userId, string $birthDate, int $dayIndex): string
    {
        $dayName = self::DAILY_DAY_NAMES[$dayIndex] ?? '';
        $formatted = $this->formatThaiDate($birthDate);

        $closings = [
            'อยากให้แม่หมอเปิดให้ไหมคะ กดปุ่มด้านล่างได้เลย ✨',
            'ถ้าพร้อมแล้ว กดปุ่มด้านล่าง เดี๋ยวแม่หมอเปิดไพ่ให้เลยนะคะ ✨',
            'มีเรื่องไหนค้างใจอยู่ กดปุ่มด้านล่างแล้วบอกแม่หมอได้เลยค่ะ ✨',
            'อยากรู้ลึกกว่านี้ กดปุ่มด้านล่างได้เลยนะคะ แม่หมอรออยู่ ✨',
        ];

        $closing = $closings[crc32($userId.':'.now()->toDateString()) % count($closings)];

        return "🎂 แม่หมอจดวันเกิดของเจ้าชะตาไว้เรียบร้อยแล้วค่ะ\n"
            ."📅 {$formatted}".($dayName !== '' ? " — ตรงกับวัน{$dayName}" : '')
            ."\n\n———\n"
            .'💫 ดวงที่เพิ่งส่งไปเป็นดวงรวมของคนเกิดวัน'.$dayName."ทั้งหมดนะคะ\n"
            ."แต่พอมีวัน/เดือน/ปีเกิดครบแบบนี้ แม่หมอเปิดไพ่ดูเฉพาะเจ้าชะตาคนเดียวได้เลย —\n"
            ."เจาะเรื่องที่ค้างใจ จังหวะที่ควรลุย และทางที่ควรเลี่ยง\n\n"
            .$closing;
    }

    /**
     * 🎁 (2026-08-12) ข้อความนี้ขอ "ดวงฟรีประจำวัน" ตรง ๆ หรือเปล่า
     *
     * ตาข่ายชั้นสองของบั๊ก "ปุ่มหล่นมาเป็นข้อความ" (ชั้นแรก = resolveQuickReplyPayloadFromTitle
     * ที่ FacebookWebhookController ซึ่งเทียบป้ายปุ่มเป๊ะ ๆ และแก้เฉพาะขา FB)
     *
     * ที่ต้องมีชั้นนี้ด้วย: matchesFreeCardKeyword() ต้องเจอคำว่า "ทำนาย" หรือ "ดูดวง"
     * คู่กับ "ฟรี" — แต่ป้ายปุ่ม/คำพูดจริงของลูกค้าคือ "รับดวงฟรีประจำวัน" ซึ่งมีแค่ "ดวง"
     * → ไม่เข้าเงื่อนไขไหนเลย → ตกไปถึง AI chat → AI ตอบมั่วว่าปิดบริการไปแล้ว
     *
     * ⚠️ เกณฑ์แคบโดยตั้งใจ — ต้องมีทั้ง "ฟรี" **และ** คำที่ชี้ว่าเป็นดวงรายวัน
     *    ("ประจำวัน"/"รายวัน") ในข้อความสั้น ๆ เท่านั้น
     *    จงใจไม่รับ "วันนี้" ลอย ๆ เพราะ "ดูดวงฟรีวันนี้" เป็นของ free card flow มาแต่เดิม
     *    — กฎนี้มีไว้เติมช่องที่ขาด ไม่ใช่แย่งเคสที่ทางเดิมทำงานถูกอยู่แล้ว
     */
    protected function looksLikeDailyFreeRequest(string $text): bool
    {
        $clean = mb_strtolower(trim($text));

        // ตัด emoji (ป้ายปุ่มมี 🎁 นำหน้า) — range เดียวกับ normalizeUserInput ไม่ทับไทย/ลาว
        $clean = preg_replace(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}\x{2190}-\x{21FF}\x{FE00}-\x{FE0F}\x{1F1E6}-\x{1F1FF}\x{200D}\x{20E3}]/u',
            '',
            $clean
        ) ?? $clean;

        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean);

        // ยาว = ประโยคเล่าเรื่อง ไม่ใช่คำขอสั้น ๆ → ปล่อยให้ AI chat คุยตามปกติ
        if ($clean === '' || mb_strlen($clean) > 40) {
            return false;
        }

        if (mb_strpos($clean, 'ฟรี') === false) {
            return false;
        }

        foreach (['ประจำวัน', 'รายวัน'] as $keyword) {
            if (mb_strpos($clean, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🚧 (2026-08-17) ตอนนี้ลูกค้าติด "ของจริง" ที่ห้ามให้ดวงฟรีแทรกอยู่หรือเปล่า
     *
     * เดิมด่านดวงฟรีใช้ `FortuneReading::hasActiveReading()` ตรง ๆ ซึ่ง **กว้างเกินไป**:
     * `ACTIVE_READING_STATUSES` รวม tier_choice / awaiting_confirmation /
     * discovery_chat / discovery_confirm ด้วย ซึ่งแปลว่า "ยืนอ่านเมนูอยู่" เท่านั้น
     * ยังไม่มีบิล ไม่มียอดเงิน (ยืนยันกับ prod: 10 แถว tier_choice → bill_number/amount = null ทุกแถว)
     *
     * 🐛 เคสจริง 2026-08-16 22:47 (user 26895114853414011):
     *    กดปุ่ม [🎁 รับดวงฟรีประจำวัน] → ด่านตีตกเพราะมี reading #11161 status=tier_choice
     *    → ชื่อวันที่ปุ่มยิงเข้ามาไหลต่อไปถึง handleTierChoice → ตอบ `tier_choice_chitchat`
     *    ซึ่งแนบปุ่มแพคเกจ 39/99 = **ขอของฟรี ได้เมนูราคากลับไป** ตรงกับสิ่งที่กฎ
     *    2026-08-01 ห้ามไว้เป๊ะ ๆ
     *
     * ⚠️ ซ้ำร้าย tier_choice ค้างได้ยาวเป็นวัน (ตัวปิดอัตโนมัติคือ cron fortune:flow-nudge
     *    ซึ่งถ้าไม่ทำงานก็ไม่มีใครปิดให้) ⇒ ลูกค้าคนนั้นถูกตัดสิทธิ์ดวงฟรีแบบถาวร ไม่ใช่ชั่วคราว
     *
     * ที่ต้องกันจริง ๆ มีแค่ "มีเงินเข้ามาเกี่ยว หรือกำลังทำนายอยู่":
     *   - PENDING_PAYMENT_STATUSES  บิลออกแล้ว รอโอน
     *   - DEEP_ACTIVE_STATUSES      Deep-39 จ่ายแล้ว กำลังเก็บวันเกิด/คำถาม/ไพ่
     *   - CELTIC_ACTIVE_STATUSES    Celtic 99 จ่ายแล้ว กำลังเปิดไพ่/ถามตอบ
     *   - IN_PREDICTION_STATUSES    AI กำลังเขียนคำทำนายอยู่
     * (เส้นจ่ายเงิน→ทำนาย ห้ามแทรกเด็ดขาด — feedback_never_interrupt_payment_to_prediction_flow)
     *
     * @return bool true = ติดของจริง ห้ามแทรก · เช็คไม่ได้ก็คืน true (ปลอดภัยไว้ก่อน)
     */
    protected function dailyBlockingReadingExists(string $platform, string $userId): bool
    {
        try {
            $statuses = array_values(array_unique(array_merge(
                FortuneReading::PENDING_PAYMENT_STATUSES,
                FortuneReading::DEEP_ACTIVE_STATUSES,
                FortuneReading::CELTIC_ACTIVE_STATUSES,
                FortuneReading::IN_PREDICTION_STATUSES,
            )));

            // fortune_readings ไม่มีคอลัมน์ line_user_id — LINE ใช้ platform_user_id
            // (เหตุผลเดียวกับ FortuneReading::hasActiveReading)
            $column = $platform === 'facebook' ? 'facebook_user_id' : 'platform_user_id';

            return FortuneReading::where($column, $userId)
                ->whereIn('conversation_status', $statuses)
                ->exists();
        } catch (\Throwable $e) {
            // เช็คไม่ได้ → ถือว่าติด ปลอดภัยกว่าไปแทรกกลางเส้นจ่ายเงิน
            Log::warning('🌙 Daily: เช็ค reading ที่ห้ามแทรกไม่ได้ (ถือว่าติดไว้ก่อน)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }

    /**
     * 🎁 (2026-08-01) ลูกค้าขอดูดวงฟรี แต่สิทธิ์ฟรีหมด/ปิด → ยื่นดวงประจำวันเกิดแทนเมนูราคา
     *
     * owner: "ถ้าใครจะดูดวงฟรี ก็ส่งปุ่มรับดวงประจำวันเกิดได้เสมอ ทุกวัน ถ้ามีคำทำนายไว้แล้ว"
     *
     * เดิม: ขอของฟรี + สิทธิ์หมด → เด้งเมนูราคาทันที (ขอของฟรี ได้ใบเสนอราคากลับไป)
     * ใหม่: บทความรายวันถูกสร้างไว้ทุกเช้าอยู่แล้ว = ของฟรีที่แจกได้ไม่จำกัดต้นทุน
     *       และไม่กินยอดขาย เพราะเป็นดวงรวมตามวันเกิด ไม่ใช่ดวงเฉพาะตัวเหมือน 39/99
     *
     * ⚠️ ไม่จำกัดวันละครั้ง — เจ้าของสั่ง "ได้เสมอ ทุกวัน" และของมีอยู่แล้วจริง ๆ
     *
     * ⚠️ ตั้งธง pending ทุกครั้ง เพื่อให้คนที่ "พิมพ์" วันเกิดแทนการกดปุ่ม ได้ดวงเหมือนกัน
     *
     * @return array|null null = ไม่ใช่เคสของโหมดนี้เลย (ไม่ใช่ FB / โหมด transfer / ติดบิล)
     *                    → ผู้เรียกกลับไปใช้ทางเดิม
     */
    protected function maybeOfferDailyForFreeRequest(string $userId, ?array $userProfile = null): ?array
    {
        try {
            $platform = $this->currentPlatform ?? 'facebook';

            if (! (new FortuneBotMode($this->settings))->dailyReplyAllowedFor($platform, $userId)) {
                return null;
            }

            // 🚨 มีบิล/กำลังทำนายอยู่ → ห้ามแทรก flow พวกนั้นมีคำตอบของตัวเอง
            //    (เช็คก่อนเรื่องบทความ — คนกลางทางจ่ายเงินไม่ควรได้ยินเรื่องดวงฟรีเลย)
            if ($this->hasPaidActiveReading($userId)
                || $this->dailyBlockingReadingExists($platform, $userId)
                || $this->hasPendingUnpaidBill($userId)) {
                return null;
            }

            $greeting = app(FortuneGreetingService::class);
            $name = (string) ($userProfile['first_name'] ?? $userProfile['name'] ?? 'คุณ');

            // ⏰ วันนี้ยังไม่มีบทความ (ก่อนรอบ gen / job ล่ม / cron ตาย)
            //
            // 🐛 (2026-08-17) เดิมคืน null ตรงนี้ ซึ่งแปลว่า "ไปต่อทางเดิม" — และปลายทาง
            //    ของผู้เรียกทุกจุดคือ startDeepReadingFlow() = **เมนูราคา**
            //    ⇒ วันที่บทความไม่ถูกสร้าง ทุกคนที่ขอของฟรีได้ใบเสนอราคากลับไปทั้งวัน
            //    (เกิดจริง 2026-08-17: cron schedule:run หลุดจาก crontab → ไม่มีบทความเลย)
            //
            //    บอกตามตรงว่ายังไม่พร้อม ดีกว่าเงียบ และดีกว่าเด้งราคาใส่คนขอของฟรี
            //    (rule_free_request_never_hits_paywall · rule_listen_dont_pitch_when_declining)
            //    ปุ่ม 👑 VIP ยังยื่นให้คนที่พร้อมจ่ายกดเองได้ แต่ไม่พูดเรื่องราคา
            if (! $greeting->dailyArticlesReadyToday()) {
                Log::warning('🎁 Daily: ขอดูฟรีแต่วันนี้ยังไม่มีบทความ — บอกตามตรง (ไม่เด้งเมนูราคา)', [
                    'user_id' => $userId,
                    'date' => now()->toDateString(),
                ]);

                return [
                    'action' => 'daily_horoscope_sent',
                    'message' => $greeting->buildDailyUnavailableMessage($name),
                    'reading' => null,
                    'quick_replies' => $this->dailyUpgradeInviteAllowed($platform, $userId)
                        ? [static::dailyUpgradeQuickReply()]
                        : [],
                ];
            }

            // รู้วันเกิดแล้ว → ปุ่มเดียว กดแล้วส่งฉบับเต็ม (ไม่ถามวันเกิดซ้ำ)
            $teaser = $greeting->buildDailyReadyTeaser($userId, $name);

            $this->markDailyPending($platform, $userId);

            if ($teaser !== null) {
                Log::info('🎁 Daily: ขอดูฟรี → ยื่นดวงประจำวันเกิด (รู้วันเกิดแล้ว)', [
                    'user_id' => $userId,
                ]);

                return [
                    'action' => 'daily_horoscope_sent',
                    'message' => $teaser,
                    'reading' => null,
                    // 🚦 (2026-08-10) ปุ่ม 👑 VIP ใช้เกณฑ์เดียวกับหางคำชวน (dailyUpgradeInviteAllowed)
                    //    ไม่งั้นปุ่มโผล่ในการ์ด teaser แต่หายในการ์ดคำทำนายของบทสนทนาเดียวกัน
                    //    ปุ่มรับดวงฟรียังอยู่เสมอ — ที่ตัดคือปุ่มขายเท่านั้น
                    'quick_replies' => $this->dailyUpgradeInviteAllowed($platform, $userId)
                        ? static::withDailyUpgrade(static::dailyShowMineQuickReplies())
                        : static::dailyShowMineQuickReplies(),
                ];
            }

            Log::info('🎁 Daily: ขอดูฟรี → ชวนบอกวันเกิดรับดวงฟรี', ['user_id' => $userId]);

            // 🚦 (2026-08-10) ปุ่มคู่ = [รับดวงฟรี] + [👑 VIP] — ตัวหลังต้องผ่านเกณฑ์เดียวกัน
            //    อ่านครั้งเดียวแล้วใช้ซ้ำ (เมธอดนี้อ่านอย่างเดียว ไม่มี side effect)
            $upgradeAllowed = $this->dailyUpgradeInviteAllowed($platform, $userId);

            // 🎁 (2026-08-07) ยื่น "ปุ่มคู่" แทนปุ่ม 7 วันดิบ ๆ — คนที่ยังไม่รู้วันเกิด
            //    ต้องเห็นคำว่า "ฟรี" ก่อน ไม่งั้นเหมาว่าเป็นตัวเดียวกับ 39 บาท
            //    (ปุ่ม 7 วันย้ายไปโผล่หลังกด DAILY_FREE_START)
            return [
                'action' => 'daily_horoscope_sent',
                'message' => $this->pickDailyFreeOffer($userId),
                'reading' => null,
                'quick_replies' => $upgradeAllowed
                    ? static::dailyFreeEntryQuickReplies()
                    : [static::dailyFreeStartQuickReply()],

                // 🃏 (2026-08-28) ธงบอกช่องทางว่า "กล่องนี้ยกเป็นการ์ด 2 ใบได้"
                //
                //   เจ้าของสั่ง: คนที่ **พิมพ์** ว่าอยากดูดวงรายวัน / อยากดูฟรี
                //   ควรได้หน้าตาเดียวกับคนที่ **กดปุ่ม** — เดิมคนพิมพ์ได้ข้อความล้วน
                //   ส่วนคนกดปุ่มได้การ์ดมีรูป ทั้งที่เป็นฟีเจอร์เดียวกัน
                //   (แพทเทิร์นเดิมเป๊ะกับที่เจอตอนการ์ดแพคเกจ: ของมีอยู่แล้ว แต่ต่อไว้ทางเดียว)
                //
                //   ⚠️ ต้องผ่าน $upgradeAllowed ด้วย — ชุดการ์ดมี **ใบ VIP ที่มีราคา**
                //      คนที่เพิ่งจ่ายภายใน 7 วัน / ปิดขายทั้งสองแพคเกจ ต้องไม่เห็นใบนั้น
                //      และ facebookEntry() สร้างครบ 2 ใบเสมอ (แยกส่งใบเดียวไม่ได้)
                //      ⇒ กรณีนั้นตกกลับไปปุ่มฟรีปุ่มเดียวเหมือนเดิม
                //      (rule_listen_dont_pitch_when_declining · owner 2026-06-17 "อย่าตื้อให้ซื้ออีก")
                //
                //   ⚠️ ตัวส่งต้อง fallback กลับมาที่ message + quick_replies ชุดบนเสมอเมื่อการ์ดล้ม
                //      — ธงนี้เป็น "ถ้าได้ก็ดี" ไม่ใช่การแทนที่กล่องเดิม
                'entry_cards' => $upgradeAllowed,
            ];
        } catch (\Throwable $e) {
            // fail-open — ทางเสริมพังต้องไม่ทำให้ลูกค้าไม่ได้คำตอบ
            Log::warning('🎁 Daily: ยื่นดวงฟรีล้ม (กลับไปทางเดิม)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🎁 คำชวนบอกวันเกิดเพื่อรับดวงฟรี — หมุนสำนวนตามคน+วัน
     *
     * ไม่บอกราคา ไม่ตื๊อ (rule_listen_dont_pitch_when_declining) — ลูกค้าเพิ่งขอของฟรี
     * การเด้งราคาใส่ตรงนี้คือสิ่งที่เรากำลังแก้อยู่พอดี
     */
    protected function pickDailyFreeOffer(string $userId): string
    {
        // 🎁 (2026-08-07) ถ้อยคำต้องตรงกับ "ปุ่มคู่" ที่ยื่นไปพร้อมกัน (dailyFreeEntryQuickReplies)
        //   เดิมเขียนว่า "เกิดวันอะไรคะ กดปุ่มด้านล่าง" เพราะปุ่มคือ 7 วันเกิด
        //   ตอนนี้ปุ่มคือ [รับดวงฟรีประจำวัน] → ต้องชี้ไปที่ปุ่มฟรี ไม่ใช่ถามวันเกิดตรงนี้
        //   (วันเกิดจะถูกถามในสเต็ปถัดไปหลังลูกค้ากด — เจ้าของสั่งให้แยกสเต็ป)
        $lines = [
            "🌙 วันนี้แม่หมอมีดวงประจำวันเกิดแจกฟรีค่ะ\nกดปุ่ม \"รับดวงฟรีประจำวัน\" ด้านล่างได้เลย ไม่มีค่าใช้จ่าย ✨",
            "🌙 ดวงประจำวันเกิดของวันนี้ แม่หมอเปิดไว้ให้ฟรีค่ะ\nกดรับได้เลยที่ปุ่มด้านล่าง เดี๋ยวแม่หมอถามวันเกิดแล้วส่งให้ทันที ✨",
            "🌙 แม่หมอมีคำทำนายประจำวันเกิดของวันนี้ให้ฟรีค่ะ\nกดปุ่มรับดวงฟรีด้านล่างได้เลยนะคะ ไม่ต้องเกรงใจ ✨",
            "🌙 อยากรู้ว่าวันนี้ดวงพาไปทางไหน แม่หมอดูให้ฟรีค่ะ\nกดปุ่มรับดวงฟรีประจำวันได้เลย แล้วบอกวันเกิดตามมาทีหลัง ✨",
        ];

        return $lines[crc32($userId.':'.now()->toDateString()) % count($lines)];
    }

    /**
     * 🔔 (2026-07-31) ปุ่มเดียว "ดูดวงวันนี้" สำหรับคนที่เรารู้วันเกิดแล้ว
     *
     * ไม่ต้องถามวันเกิดซ้ำ — แค่รอให้กด แล้วส่งฉบับเต็มตอบกลับ
     * (การกดเปิดหน้าต่าง 24 ชม. ของ FB ให้เอง → ข้อความไม่ถูกตัด)
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public static function dailyShowMineQuickReplies(): array
    {
        return [
            ['content_type' => 'text', 'title' => '🔮 ดูดวงวันนี้เลย', 'payload' => 'DAILY_SHOW_MINE'],
        ];
    }

    /**
     * 💎 ต่อท้ายทางลัดจ่ายเงินให้ชุดปุ่ม — ใช้เฉพาะ**ในแชท**เท่านั้น
     *
     * 🚨 ห้ามยัดเข้าไปใน dailyBirthdayQuickReplies()/dailyShowMineQuickReplies() ตรง ๆ
     *    เพราะ 2 ตัวนั้นถูกใช้กับ **DM ขาออก** ด้วย ซึ่งเจ้าของสั่งไว้ชัดว่า
     *    "ให้ส่งแต่คำเชิญชวนดูดวงให้บอกวันเดือนปีเกิดเพื่อรับคำทำนายรายวันฟรี" —
     *    ติดปุ่มขายไปกับ DM เย็น ๆ = กลับไปเป็นสแปมที่เพิ่งแก้ไป
     *
     *    ในแชทต่างกัน: ลูกค้าทักมาเอง/เพิ่งได้ของฟรีจากเรา = จังหวะที่ยื่นทางเลือกได้
     *
     * @param  array<int, array>  $buttons
     * @return array<int, array>
     */
    protected static function withDailyUpgrade(array $buttons): array
    {
        $buttons[] = static::dailyUpgradeQuickReply();

        return $buttons;
    }

    /**
     * 💎 (2026-08-01) ทางลัดสำหรับคนที่อยากจ่ายเงินดูเลย ไม่รอของฟรี
     *
     * owner: "สำหรับผู้ต้องการดูดวงแบบจ่ายเงินทันที มีทางเลือกให้ไหม จะได้ไม่เสียลูกค้า"
     *
     * ทุกข้อความในเส้นดวงฟรีจบด้วยคำชวน "ทักมาบอกได้เลยค่ะ" ซึ่งบังคับให้ลูกค้าพิมพ์เอง
     * = แรงเสียดทานตรงจังหวะที่ลูกค้าอยากจ่ายที่สุด → ให้ปุ่มกดทันทีไปเลย
     *
     * ⚠️ **ปุ่มเดียว ไม่ใส่ตัวเลขราคา**:
     *   - ไม่ใช่แผงปุ่มขายท้ายทุกข้อความแบบเดิมที่เจ้าของสั่งปิด ("vending machine ฮาร์ดเซล")
     *   - ราคาแอดมินแก้ได้จากหน้าตั้งค่า ฝังเลขในปุ่มไว้ = เพี้ยนทันทีที่แก้
     *     ให้ tier menu (เจ้าของราคาตัวจริง) เป็นคนบอกตัวเลข
     *
     * 🏷️ (2026-08-07, owner) ป้ายปุ่มต้องบอกตรง ๆ ว่า "มีค่าครู"
     *   เดิมชื่อ "💎 ดูแบบละเอียดเลย" — ไม่มีคำไหนบอกว่าเสียเงินเลย เจ้าของสั่ง:
     *   "ทำปุ่มดูดวงฟรีประจำวันไว้คู่กับ VIP มีค่าครู ... กันการกล่าวหาว่าหลอกให้กด"
     *   ปุ่มที่พาไปหน้าจ่ายเงินต้องอ่านออกตั้งแต่ก่อนกด ไม่ใช่ไปเจอราคาเอาตอนกดแล้ว
     *
     * payload = DAILY_VIP_PACKAGES → มี case ใน FacebookWebhookController::handleQuickReply
     * → ส่ง 'ดูดวง' เข้า flow → เมนูเลือกแพคเกจที่บอก "ค่าครู 39/99 บาท"
     *
     * (เดิม payload เป็น 'ดูดวง' อาศัย default branch — เปลี่ยนเป็น payload เฉพาะ
     *  เพื่อไม่ต้องพึ่ง fall-through และกันกรณี FB ส่งกลับมาเป็นข้อความดิบ
     *  ดู [[rule_fb_quickreply_label_arrives_as_text]] — ถ้า title หลุดมาเป็นข้อความ
     *  คำว่า "ค่าครู" จะเข้า looksLikePricingQuestion → ได้กล่องราคา ไม่ใช่บิล)
     *
     * @return array{content_type: string, title: string, payload: string}
     */
    public static function dailyUpgradeQuickReply(): array
    {
        // 🏷️ (2026-08-12, owner) "กล่องหลังส่งดวงฟรี ควรใส่คำว่า ดู vip ส่วนตัว มีค่าครู"
        //   ถ้อยคำเต็มยาว 22 ตัว — เกินเพดานป้ายปุ่ม FB (20) จะโดนตัดกลางคำเป็น "…มีค่าค"
        //   → ยุบช่องว่างออก เหลือ 20 พอดี คำครบทุกคำ (ไทยเขียนติดกันได้ ไม่เสียความหมาย)
        //   มงกุฎ 👑 ต้องสละ — ใส่แล้วเกินเพดาน (คำสำคัญกว่าอีโมจิ ตามเจตนา "กันกล่าวหาว่าหลอกให้กด")
        return ['content_type' => 'text', 'title' => 'ดูvipส่วนตัวมีค่าครู', 'payload' => 'DAILY_VIP_PACKAGES'];
    }

    /**
     * 🎁 (2026-08-07) ปุ่มคู่ "ทางเข้า" สำหรับคนที่เรายังไม่รู้วันเกิด
     *
     * เจ้าของสั่ง: "ตอนนี้ลูกค้าเข้าใจว่าดูฟรีคือแบบ 39 บาท ... ปุ่มวันจันทร์-อาทิตย์
     * ให้เปลี่ยนเป็นปุ่ม รับดวงฟรีประจำวัน เพื่อให้กด แล้วแยกไปถามวันเกิดเพื่อรับ
     * คำทำนายฟรี แล้วค่อยเนียนปิดขายแบบดูดวงละเอียดมีค่าครู"
     *
     * 🐛 ปัญหาเดิม: ยิงปุ่ม 7 วันเกิด (จันทร์…อาทิตย์) ใส่คนที่ยังไม่รู้ว่ามันคืออะไร
     *    → ไม่มีคำว่า "ฟรี" อยู่บนปุ่มสักปุ่ม ลูกค้าจึงเหมาเอาว่าเป็นตัวเดียวกับ 39 บาท
     *    → ลังเลไม่กด หรือกดแล้วมาโวยว่าโดนหลอก
     *
     * ✅ ใหม่: 2 ปุ่มที่อ่านแล้วรู้ราคาทันทีทั้งคู่ ไม่มีทางเข้าใจสลับกัน
     *    [🎁 รับดวงฟรีประจำวัน] → ถามวันเกิด (ปุ่ม 7 วันโผล่ตรงนี้) → ได้ของฟรีจริง
     *    [👑 VIP มีค่าครู]      → ลัดเข้าเมนูแพคเกจ สำหรับคนที่พร้อมจ่ายอยู่แล้ว
     *
     * ⚠️ ปุ่ม 7 วันเกิดไม่ได้ถูกยกเลิก — แค่ย้ายไปอยู่หลังปุ่มฟรี 1 สเต็ป
     *    (ดู FacebookWebhookController::handleDailyFreeStart)
     *
     * 🚨 **ปุ่มคู่นี้ใช้ได้เฉพาะ "ในแชท" เท่านั้น** — ห้ามยัดลง DM ขาออกเย็น ๆ
     *    เจ้าของเคยสั่งไว้ (ดู withDailyUpgrade): "ติดปุ่มขายไปกับ DM เย็น ๆ =
     *    กลับไปเป็นสแปมที่เพิ่งแก้ไป" และรอบนี้ก็ระบุว่าปุ่ม VIP มีไว้
     *    "สำหรับกลุ่มคนที่ตอบดีเอ็มได้ถูก" = คนที่ตอบกลับมาแล้ว ไม่ใช่คนที่เพิ่งได้รับ DM
     *    → DM ขาออกใช้ dailyFreeStartQuickReply() (ปุ่มฟรีปุ่มเดียว) แทน
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public static function dailyFreeEntryQuickReplies(): array
    {
        return [
            static::dailyFreeStartQuickReply(),
            static::dailyUpgradeQuickReply(),
        ];
    }

    /**
     * 🎁 (2026-08-07) ปุ่ม "รับดวงฟรีประจำวัน" เดี่ยว ๆ — ใช้กับ **DM ขาออก**
     *
     * ตัวที่มาแทนปุ่ม 7 วันเกิดใน DM: บอกชัดว่าฟรี + ไม่มีปุ่มขายติดไปด้วย
     * (เคารพคำสั่งเดิม "ห้ามติดปุ่มขายไปกับ DM เย็น ๆ" — ดู withDailyUpgrade)
     *
     * @return array{content_type: string, title: string, payload: string}
     */
    public static function dailyFreeStartQuickReply(): array
    {
        return ['content_type' => 'text', 'title' => '🎁 รับดวงฟรีประจำวัน', 'payload' => 'DAILY_FREE_START'];
    }

    /**
     * 💚 (2026-08-21) แปลงปุ่มเลนดวงรายวัน FB (content_type/title/payload) → LINE (label/text)
     *
     * 🚨 **แหล่งเดียวของการแปลง** — ห้ามก็อป array_map ไปเขียนซ้ำที่ไหนอีก
     *    ตอนนี้มีผู้ใช้ 2 จุด (FortuneChannelManager arm 'daily_horoscope_sent' และ
     *    LineFortuneWebhookController::replyWithDailyQuickReplies) ถ้าแยกกันเขียนเมื่อไร
     *    มันจะดริฟต์ทันที — ซึ่งเป็นบั๊กตระกูลเดียวกับที่ทำให้เลนนี้ตายฝั่ง LINE มาตลอด
     *
     * 🐛 **กับดักตัวจริง: LINE quick reply เป็น `type=message` ⇒ ป้ายปุ่มคือข้อความที่ส่งกลับ**
     *    ป้ายปุ่มวันอาทิตย์คือ "☀️ อาทิตย์" ซึ่งมี **U+FE0F (Variation Selector-16)** ติดมาด้วย
     *    และ VS16 เป็น Unicode category **Mn (Mark)** ⇒ ตัวปอกของ resolveBirthDayNameIndex()
     *    (`[^\p{L}\p{N}\p{M}\s]` → space) **เก็บมันไว้** เพราะมันคือ \p{M}
     *    ผลคือได้ "️ อาทิตย์" ซึ่งเทียบกับ DAILY_DAY_ALIASES ไม่ติด → คืน null
     *    ⇒ คนเกิดวันอาทิตย์ (1 ใน 7 ของลูกค้า) กดปุ่มแล้วบอทไม่รู้จัก
     *    (วันอื่นรอดเพราะอีโมจิของมันไม่มี VS16 — บั๊กที่โผล่วันเดียวแบบนี้หายากมาก)
     *
     *    ⇒ ปุ่มวันเกิดจึงส่ง **"วัน{ชื่อวัน}" ที่ประกอบจาก payload** ไม่ใช่ป้ายปุ่ม
     *      (แพทเทิร์นเดียวกับ FacebookWebhookController::handleDailyBirthdayPick)
     *      ส่วนปุ่มอื่นส่งป้ายที่ปอกอีโมจิแล้ว — ตัวจับป้ายฝั่ง LINE normalize ให้อยู่แล้ว
     *
     * @param  array<int, array>  $buttons  ปุ่มรูปแบบ FB
     * @return array<int, array{label: string, text: string, payload: string}>
     */
    public static function dailyQuickRepliesForLine(array $buttons): array
    {
        $out = [];

        foreach ($buttons as $b) {
            if (! is_array($b)) {
                continue;
            }

            // LINE จำกัดป้ายปุ่ม 20 ตัวอักษร (เท่ากับ FB)
            $label = mb_substr(trim((string) ($b['title'] ?? $b['label'] ?? '')), 0, 20);

            if ($label === '') {
                continue;
            }

            $payload = trim((string) ($b['payload'] ?? ''));

            if (preg_match('/^DAILY_BDAY_([0-6])$/', $payload, $m) === 1) {
                $text = 'วัน'.self::DAILY_DAY_NAMES[(int) $m[1]];
            } else {
                $text = trim((string) ($b['text'] ?? ''));

                if ($text === '') {
                    // ปอกอีโมจิ + VS16 ออกจากป้าย — range ชุดเดียวกับ looksLikeDailyIntent
                    $text = trim(preg_replace(
                        '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}\x{2190}-\x{21FF}\x{FE00}-\x{FE0F}\x{1F1E6}-\x{1F1FF}\x{200D}\x{20E3}]/u',
                        '',
                        $label
                    ) ?? $label);
                }

                if ($text === '') {
                    $text = $label;
                }
            }

            $out[] = ['label' => $label, 'text' => $text, 'payload' => $payload];
        }

        // LINE API รับสูงสุด 13 ปุ่ม
        return array_slice($out, 0, 13);
    }

    /**
     * 🔘 ปุ่ม 7 วันเกิด — ทางหลักของโหมดนี้ (ไม่ต้องพึ่ง parser เลย)
     *
     * payload = DAILY_BDAY_0 … DAILY_BDAY_6 (ตรงกับ index วัน)
     * ต้องมี case ใน FacebookWebhookController::handleQuickReply ไม่งั้น payload
     * จะถูกส่งเป็น "ข้อความลูกค้า" ดิบ ๆ เข้า processMessage (default branch)
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public static function dailyBirthdayQuickReplies(): array
    {
        $emojis = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣'];

        $buttons = [];
        foreach (self::DAILY_DAY_NAMES as $index => $dayName) {
            $buttons[] = [
                'content_type' => 'text',
                'title' => $emojis[$index].' '.$dayName,
                'payload' => 'DAILY_BDAY_'.$index,
            ];
        }

        return $buttons;
    }
}
