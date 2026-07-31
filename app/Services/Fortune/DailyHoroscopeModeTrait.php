<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

            // 1️⃣ โหมด + ช่องทาง (เช็คถูกที่สุดก่อน — ไม่แตะ DB/Cache)
            if (! (new FortuneBotMode($this->settings))->dailyAppliesTo($platform, $userId)) {
                return null;
            }

            // 2️⃣ ต้องมีธง "เราถามไปแล้ว" เท่านั้น — ออกจาก hot path ก่อนแตะ DB
            if (! Cache::has($this->dailyPendingKey($platform, $userId))) {
                return null;
            }

            // 3️⃣ 🚨 ลูกค้าจ่ายเงินแล้ว / มีบิล / กำลังทำนาย → ห้ามแตะเด็ดขาด
            //    ต้องเช็คทั้ง 3 ตัวเพราะครอบคนละช่วง:
            //      hasPaidActiveReading  = จ่ายแล้ว แต่มีกรอบ updated_at 2 ชม. (มีรูรั่ว)
            //      hasActiveReading      = ไม่มีกรอบเวลา ← ตัวที่ครอบ Deep-39 collecting_birthdate
            //      hasPendingUnpaidBill  = กำลัง checkout อยู่
            if ($this->hasPaidActiveReading($userId)
                || FortuneReading::hasActiveReading($platform, $userId)
                || $this->hasPendingUnpaidBill($userId)) {
                Log::info('🌙 Daily: ข้าม — ลูกค้ามีบิล/กำลังทำนายอยู่', [
                    'user_id' => $userId,
                ]);

                return null;
            }

            // 4️⃣ Escape hatch — ลูกค้าเปลี่ยนเรื่องแล้ว (แจ้งโอน/ขอคุยกับคน/ยกเลิก/ขอดูดวง)
            //    ลบธงทิ้งแล้วปล่อยให้ flow เดิมจัดการ ไม่ใช่ดันวันเกิดต่อ
            if ($this->looksLikeDailyEscape($messageText)) {
                $this->clearDailyPending($platform, $userId);

                return null;
            }

            // 5️⃣ ตีความคำตอบ — วันในสัปดาห์ หรือ วันเดือนปีเกิดเต็ม
            $resolved = $this->resolveDayIndexFromReply($messageText);

            if ($resolved === null) {
                return null;   // ไม่ใช่คำตอบวันเกิด → คุยปกติ (ไม่ตื๊อ)
            }

            [$dayIndex, $fullDate] = $resolved;

            // 6️⃣ กันกดรัว/พิมพ์ซ้อน — 1 คำตอบต่อ 8 วินาที
            if (! Cache::add("fortune:daily_answer_lock:{$platform}:{$userId}", true, 8)) {
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
        $message .= $fullDate !== null
            ? "\n\n———\n💫 แม่หมอจดวันเกิดของเจ้าชะตาไว้แล้วนะคะ\nถ้าอยากให้เปิดดูเชิงลึกว่าช่วงนี้ดวงพาไปทางไหน ทักมาบอกได้เลยค่ะ"
            : "\n\n———\n💫 ถ้าบอกวัน/เดือน/ปีเกิดเต็ม ๆ มาด้วย แม่หมอจะดูให้ละเอียดกว่านี้ได้อีกเยอะเลยค่ะ";

        // ตอบแล้วปิดธง — ไม่ให้ข้อความถัดไปถูกตีเป็นวันเกิดอีก
        $this->clearDailyPending($platform, $userId);

        Log::info('🌙 Daily: ส่งดวงรายวันตามคำขอ', [
            'user_id' => $userId,
            'day_index' => $dayIndex,
            'day' => self::DAILY_DAY_NAMES[$dayIndex] ?? '?',
            'has_full_date' => $fullDate !== null,
            'has_article' => $box !== null,
            'stale_days' => $box['stale_days'] ?? null,
        ]);

        return [
            'action' => 'daily_horoscope_sent',
            'message' => $message,
            'reading' => null,
            'daily_day_index' => $dayIndex,
        ];
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
     * จับชื่อวันในสัปดาห์ภาษาไทย → index 0-6
     *
     * ⚠️ "อาทิตย์" ต้องเช็คก่อน "อังคาร" ไม่ได้ แต่ต้องระวัง "พฤหัส" ที่เป็นคำนำของ
     *    "พฤหัสบดี" — ใช้ลำดับที่ยาวกว่ามาก่อนเพื่อไม่ให้จับผิดคำ
     */
    protected function detectThaiDayName(string $text): ?int
    {
        $map = [
            'อาทิตย์' => 0, 'อาทิด' => 0, 'อาทิจ' => 0,
            'จันทร์' => 1, 'จันทร' => 1, 'จัน' => 1,
            'อังคาร' => 2,
            'พุธ' => 3,
            'พฤหัสบดี' => 4, 'พฤหัส' => 4, 'พฤหัสฯ' => 4, 'พหัส' => 4,
            'ศุกร์' => 5, 'ศุก' => 5,
            'เสาร์' => 6, 'เสา' => 6,
        ];

        foreach ($map as $needle => $index) {
            if (mb_strpos($text, $needle) !== false) {
                return $index;
            }
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
     * 🔘 ปุ่ม 7 วันเกิด — ทางหลักของโหมดนี้ (ไม่ต้องพึ่ง parser เลย)
     *
     * payload = DAILY_BDAY_0 … DAILY_BDAY_6 (ตรงกับ index วัน)
     * ต้องมี case ใน FacebookWebhookController::handleQuickReply ไม่งั้น payload
     * จะถูกส่งเป็น "ข้อความลูกค้า" ดิบ ๆ เข้า processMessage (default branch)
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
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
     * @return string|null null = ไม่ใช่โหมด daily หรือวันนี้ยังไม่มีบทความ → ใช้ข้อความเดิม
     */
    protected function buildDailyReadingForDetectedBirthdate(string $birthDate, ?array $userProfile = null): ?string
    {
        try {
            $platform = $this->currentPlatform ?? 'facebook';

            if (! (new FortuneBotMode($this->settings))->isDaily()) {
                return null;
            }

            if ($platform !== FortuneBotMode::INTERCEPT_PLATFORM) {
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

            Log::info('🎂 Daily: ลูกค้าพิมพ์วันเกิดมาเอง → ส่งดวงรายวันให้ก่อน', [
                'day_index' => $dayIndex,
                'birth_date' => $birthDate,
            ]);

            // เนียนชวนต่อ — ไม่บอกราคา ไม่กดดัน ปุ่มเลือกยังอยู่ให้กดเอง
            return $box['text']
                .($blessing !== '' ? "\n\n".$blessing : '')
                ."\n\n———\n"
                .'💫 นี่คือดวงประจำวันของเจ้าชะตานะคะ'."\n"
                .'ถ้าอยากให้แม่หมอเปิดเชิงลึกจากวันเกิดนี้ กดดูด้านล่างได้เลยค่ะ';
        } catch (\Throwable $e) {
            Log::warning('🎂 Daily: สร้างดวงจากวันเกิดที่ลูกค้าพิมพ์ล้ม', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
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
