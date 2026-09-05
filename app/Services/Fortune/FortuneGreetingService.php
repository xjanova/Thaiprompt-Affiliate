<?php

namespace App\Services\Fortune;

use App\Models\FortuneHoroscopePost;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use App\Models\HoroscopeDailyPrediction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🌙 บริการสร้างคำทักทาย DM แบบ "ดวงประจำวันสั้นๆ"
 *
 * นโยบาย 2026-05-22 (อัพเดต tone — ไม่สัญญา ไม่ล่อ):
 * - ลูกค้าเก่า (มี birth_date ใน DB) → ส่งดวงประจำวันตาม day_of_birth + ชวน
 *   เปิดเชิงลึก ทักมาได้
 * - ลูกค้าใหม่ (ไม่มี birth_date) → ทักทายอบอุ่น เปิดประตูคุย ไม่กดดัน
 *   ไม่สัญญาว่าจะให้ฟรีหรือยื่นข้อเสนอใดๆ
 *
 * 📚 แหล่งบทความ = `horoscope_daily_predictions` (job `horoscope:generate-daily` 00:01)
 *   ถ้าวันนี้ยังไม่มีบทความ → fallback generic by day name (ยังคงมี personalization)
 *   ⛔ **ห้ามกลับไปอ่าน `FortuneDailyHoroscopePost`** — ระบบเก่ารายวันเกิดที่ปิดสวิตช์
 *      และหยุดเขียนตั้งแต่ 29 เม.ย. 2569 (อ่านแล้วได้ null เสมอ = ลูกค้าได้แต่ประโยคน้ำเปล่า)
 *
 * Used by:
 * - FacebookWebhookController::tryReactionDm (reaction → DM)
 * - FacebookWebhookController::sendTemplateEngagement (comment → DM)
 * - ProcessCommentEngagement (AI fallback)
 */
class FortuneGreetingService
{
    /**
     * สร้างคำทักทาย DM พร้อมดวงประจำวัน
     *
     * @param  string  $userId  facebook_user_id หรือ platform_user_id
     * @param  string  $name  ชื่อลูกค้า (placeholder substitution)
     * @return string ข้อความ DM พร้อมส่ง (ใส่ {name} แทนที่แล้ว)
     */
    public function buildDailyHoroscopeGreeting(string $userId, string $name): string
    {
        $birthdate = FortuneReading::findLatestBirthdate($userId);
        $displayName = $this->normalizeName($name);

        if ($birthdate instanceof Carbon) {
            return $this->buildHoroscopeForKnownUser($displayName, $birthdate);
        }

        return $this->buildInviteForNewUser($displayName);
    }

    /**
     * 🌙 (2026-07-31) กล่อง "ดวงรายวัน" สำหรับแนบนำหน้าข้อความ DM ปกติ
     *
     * USER SPEC:
     *   1. ใช้บทความที่ AI สร้างทุกวัน 06:00 — **ต้องเป็นบทความของวันเดียวกันเท่านั้น**
     *   2. ส่งเป็นกล่องแยก แล้วค่อยต่อด้วยข้อความ DM ปกติอีกกล่อง
     *   3. ไม่มีบทความของวันนี้ → คืน null (เหลือแค่ข้อความ DM ปกติ)
     *   4. ส่งเพียงครั้งแรกของวัน (ต่อลูกค้า)
     *   5. ลูกค้าที่ระบบไม่มีวันเกิด → ส่งไปทั้ง 7 วันเกิด ให้เลือกอ่านเอง
     *   6. มีสวิตช์เปิด/ปิดในส่วน DM (`dm_daily_horoscope_enabled`, default ปิด)
     *   7. (2026-08-28) อยู่ใต้สวิตช์ใหญ่ `daily_free_horoscope_enabled` อีกชั้น —
     *      ปิดระบบดวงฟรี = ไม่แนบกล่องนี้ แม้สวิตช์ข้อ 6 จะเปิดค้างไว้
     *
     * ⛔ (2026-07-31) **ใช้กับโหมด classic เท่านั้น** — โหมด daily คืน null ทันที
     *   เพราะ DM ครั้งแรกของโหมดนั้นต้องมีแค่ "คำเชิญ + ปุ่ม" ไม่ใช่คำทำนาย
     *   (คนที่ยังไม่เคยทักเพจอยู่นอกหน้าต่าง 24 ชม. → คำทำนายถูกตัดจนอ่านไม่รู้เรื่อง)
     *
     * ⚠️ อ่านจาก `horoscope_daily_predictions` (ตารางที่ `horoscope:generate-daily` 06:00 เขียน)
     *   ไม่ใช่ `fortune_daily_horoscope_posts` ของระบบเก่าที่ถูกปิดสวิตช์ไว้ตั้งแต่ 2026-04-29
     *
     * @param  string  $userId  facebook_user_id / platform_user_id
     * @param  string  $name  ชื่อลูกค้า
     * @param  string  $platform  ใช้แยก key กันส่งซ้ำข้ามช่องทาง
     * @return array{text: string, merge_text: string}|null
     *                                                      text       = ฉบับเต็ม ใช้ตอนส่งแยกกล่อง (อยู่ใน 24 ชม. — FB ตัดท่อนให้เองได้)
     *                                                      merge_text = ฉบับย่อ ใช้ตอนรวมกับ DM ปกติ (ต้องอยู่ใน 1 Private Reply ไม่งั้น
     *                                                      ท่อนแรกๆ จะถูกยิงด้วย RESPONSE แล้วตกกฎ 24 ชม. = ไม่ถึงลูกค้า)
     */
    public function buildDailyHoroscopeBox(string $userId, string $name, string $platform = 'facebook'): ?array
    {
        try {
            // 0️⃣ (2026-07-31) โหมด daily — **ห้ามแนบคำทำนายใน DM ครั้งแรกเด็ดขาด**
            //
            //   owner: "การ DM ครั้งแรกยังไม่ต้องส่งคำทำนาย เพราะมันจะส่งได้ไม่สุด
            //           ข้อความขาด เอาแค่คำเชิญดูดวงและมีปุ่ม"
            //
            //   เหตุผลเชิงเทคนิคตรงกันเป๊ะ: ลูกค้าที่ยังไม่เคยทักเพจอยู่นอกหน้าต่าง
            //   24 ชม. ของ FB (error 10/2018278) → กล่องคำทำนายส่งแยกไม่ผ่าน ต้องยุบ
            //   ไปรวมกับข้อความ DM ใน Private Reply เดียว แล้วถูกตัดจนอ่านไม่รู้เรื่อง
            //
            //   โหมดนี้ออกแบบมาให้ลูกค้า "กดปุ่มวันเกิด" ซึ่ง**เปิดหน้าต่าง 24 ชม.
            //   ให้เอง** แล้วค่อยส่งคำทำนายฉบับเต็มตอบกลับ (buildDailyBoxForDayIndex)
            //   → DM ครั้งแรกจึงมีแค่คำเชิญ + ปุ่ม 7 วันเกิด เท่านั้น
            $botMode = new FortuneBotMode;

            if ($botMode->isDaily()) {
                return null;
            }

            // 🎁 (2026-08-28) แอดมินปิด "ระบบชวนรับดวงรายวันฟรี" → ไม่แนบดวงฟรีไปกับ DM เลย
            //    กล่องนี้คือการ**ยื่นของฟรีให้เอง**ในโหมด classic ซึ่งเข้าข่าย "ชวนรับดวงฟรี"
            //    เต็ม ๆ (owner: "จะใช้ DM ชุดแรกเท่านั้นเพื่อชวนมาดูดวงอย่างเดียว")
            //    ⚠️ อยู่ก่อนการจองสิทธิ์ครั้งแรกของวัน — ปิดสวิตช์ต้องไม่เผาสิทธิ์ใคร
            if (! $botMode->dailyFreeOutboundEnabled()) {
                return null;
            }

            // 1️⃣ สวิตช์ในส่วน DM — ปิดอยู่ → ไม่แนบกล่องนี้ (พฤติกรรมเดิม 100%)
            //    (สวิตช์นี้ใช้กับโหมด classic เท่านั้น)
            if (! (bool) FortuneTellingSetting::query()->value('dm_daily_horoscope_enabled')) {
                return null;
            }

            $displayName = $this->normalizeName($name);

            // 🌙 (2026-08-04) รวมคนที่ตอบด้วย**ปุ่มวันเกิด** ด้วย (มีแต่ birth_day ไม่มี birth_date)
            //   บทความกล่องนี้เลือกด้วยวันในสัปดาห์อยู่แล้ว จึงเสิร์ฟคนกลุ่มนั้นได้เต็ม ๆ
            $dayIndex = FortuneUserCredit::findBirthDayIndex($userId, $platform);

            // 2️⃣ มีวันเกิด → ดวงเฉพาะวันเกิดนั้น / ไม่มี → ส่งทั้ง 7 วันให้เลือกอ่านเอง
            $boxes = $dayIndex !== null
                ? $this->buildTodayBoxForBirthday($displayName, $dayIndex)
                : $this->buildTodayBoxAllDays($displayName);

            // 3️⃣ วันนี้ยังไม่มีบทความ → ไม่ต้องมีกล่องนี้ (ไม่ใส่ generic แทน ตาม spec)
            if ($boxes === null) {
                return null;
            }

            // 4️⃣ ครั้งแรกของวันเท่านั้น — Cache::add เป็น atomic (กัน DM ซ้อนจาก webhook พร้อมกัน)
            //    มาร์คหลังสร้างข้อความสำเร็จ เพื่อไม่ให้ "วันที่ยังไม่มีบทความ" มาเผาสิทธิ์ของวันนั้น
            //    ⚠️ ผู้เรียก **ต้อง** เรียก releaseDailyHoroscopeBoxSlot() ถ้าส่งไม่สำเร็จ
            //       ไม่งั้นลูกค้าที่ FB ปฏิเสธ (นอก 24 ชม. / 551) จะเสียสิทธิ์ของวันนั้นฟรีๆ
            $ttl = max(60, (int) now()->endOfDay()->diffInSeconds(now(), true));
            if (! Cache::add($this->dailyBoxSlotKey($userId, $platform), true, $ttl)) {
                return null;
            }

            return $boxes;
        } catch (\Throwable $e) {
            // กล่องเสริม — ล้มแล้วต้องไม่ทำให้ DM ปกติไม่ถูกส่ง
            Log::warning('FortuneGreetingService: buildDailyHoroscopeBox ล้ม (ข้ามกล่องดวง)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🔓 คืนสิทธิ์ "ครั้งแรกของวัน" เมื่อส่งกล่องดวงไม่สำเร็จ
     *
     * เคสจริง 2026-07-31: FB ปฏิเสธด้วย error 10/2018278 (นอก 24 ชม.) และ 551
     * → ถ้าไม่คืนสิทธิ์ ลูกค้าจะไม่มีวันได้กล่องดวงของวันนั้นอีกเลย แม้ภายหลัง
     *   จะทักเข้ามาเองจนเปิดหน้าต่าง 24 ชม.แล้วก็ตาม
     */
    public function releaseDailyHoroscopeBoxSlot(string $userId, string $platform = 'facebook'): void
    {
        try {
            Cache::forget($this->dailyBoxSlotKey($userId, $platform));
        } catch (\Throwable $e) {
            Log::warning('FortuneGreetingService: คืนสิทธิ์กล่องดวงรายวันล้ม', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * cache key ของสิทธิ์ "ส่งกล่องดวงรายวันแล้ววันนี้"
     */
    protected function dailyBoxSlotKey(string $userId, string $platform): string
    {
        return "fortune:dm_horoscope_sent:{$platform}:{$userId}:".now()->toDateString();
    }

    /**
     * กล่องดวงรายวันของ "วันเกิดที่รู้" — บทความวันนี้เท่านั้น
     *
     * บทความเดียว ~300 ตัวอักษร → ใช้ฉบับเต็มได้ทั้งตอนส่งแยกและตอนรวมกับ DM ปกติ
     *
     * @return array{text: string, merge_text: string}|null
     */
    protected function buildTodayBoxForBirthday(string $name, int $dayIndex): ?array
    {
        // ⚠️ $dayIndex ต้องเป็น dayOfWeek (0=อาทิตย์ … 6=เสาร์) ให้ตรงกับคอลัมน์ birth_day
        //    ห้ามส่ง dayOfWeekIso (1–7) มา เพราะคนเกิดวันอาทิตย์จะได้ 7 = ไม่มีในตาราง
        //    (ผู้เรียกใช้ FortuneUserCredit::findBirthDayIndex ซึ่งการันตีช่วง 0-6 ให้แล้ว)
        $prediction = $this->findTodayPrediction($dayIndex);
        if ($prediction === null) {
            return null;
        }

        $body = trim((string) $prediction->overall_prediction_th);
        if ($body === '') {
            return null;
        }

        $text = "🌙 ดวงประจำ{$this->thaiFullDate()}\n"
            ."สำหรับคุณ {$name} — คนเกิดวัน".self::DAY_NAMES[$dayIndex].' '.self::DAY_EMOJIS[$dayIndex]."\n\n"
            .$body
            .$this->buildLuckyLine($prediction);

        return ['text' => $text, 'merge_text' => $text];
    }

    /**
     * กล่องดวงรายวัน "ครบ 7 วันเกิด" — สำหรับลูกค้าที่ระบบยังไม่มีวันเกิด
     *
     * USER SPEC: "บางคนไม่มีวันเกิด ให้ส่งไปทั้งบทความเลยให้ลูกค้าเลือกอ่านเอง"
     *
     * @return array{text: string, merge_text: string}|null
     */
    protected function buildTodayBoxAllDays(string $name): ?array
    {
        $predictions = HoroscopeDailyPrediction::query()
            ->where('target_date', now()->toDateString())
            ->where('prediction_type', 'birth_day')
            ->where('status', 'generated')
            ->whereNotNull('overall_prediction_th')
            ->get()
            ->keyBy('birth_day');

        if ($predictions->isEmpty()) {
            return null;
        }

        // 🌙 (2026-09-05) นับจาก **บทความที่มีจริงวันนี้** ไม่ใช่ตัวเลขตายตัว —
        //    เดิมเขียน "ทั้ง 7 วันเกิด" ไว้ตรง ๆ พอเพิ่มวันเกิดที่ 8 หรือวันไหน generate
        //    ไม่ครบ ตัวเลขในกล่องจะโกหกลูกค้าทันทีโดยไม่มีใครเห็น
        $header = [
            "🌙 ดวงประจำ{$this->thaiFullDate()}",
            'ทั้ง '.$predictions->count()." วันเกิดค่ะคุณ {$name} — เลื่อนหาวันเกิดของเจ้าชะตาได้เลยนะคะ ✨",
            '(เกิดวันพุธหลังหกโมงเย็นถึงเช้ามืดวันพฤหัสฯ ตำราไทยถือเป็น "พุธกลางคืน" นะคะ)',
            '',
        ];

        $full = $header;      // ฉบับเต็ม (USER SPEC: "ส่งไปทั้งบทความเลยให้ลูกค้าเลือกอ่านเอง")
        $compact = $header;   // ฉบับย่อ ใช้เฉพาะตอนรวมกับ DM ปกติ
        $found = 0;

        foreach (self::DAY_NAMES as $index => $dayName) {
            $prediction = $predictions->get($index);
            if ($prediction === null) {
                continue;
            }

            $body = trim(preg_replace('/\s+/', ' ', (string) $prediction->overall_prediction_th));
            if ($body === '') {
                continue;
            }

            $found++;
            $title = self::DAY_EMOJIS[$index].' *วัน'.$dayName.'*';

            // เต็ม — ไม่ตัดคำทำนาย + แถมของนำโชคของวันนั้น
            $full[] = $title."\n".$body.ltrim($this->buildLuckyLine($prediction), "\n");
            $full[] = '';

            // ย่อ — 7 วันรวมกันต้องอยู่ใน Private Reply เดียว ไม่งั้นถูกหั่นแล้วท่อนแรกหาย
            $compact[] = $title."\n".(mb_strlen($body) > 110 ? mb_substr($body, 0, 109).'…' : $body);
            $compact[] = '';
        }

        // มีแต่หัวข้อ ไม่มีเนื้อสักวัน → ไม่ต้องส่งกล่องนี้
        if ($found === 0) {
            return null;
        }

        $footer = '💫 อยากให้แม่หมอเปิดเชิงลึกให้ ทักมาบอกวันเกิดได้เลยค่ะ';
        $full[] = $footer;
        $compact[] = $footer;

        return [
            'text' => implode("\n", $full),
            'merge_text' => implode("\n", $compact),
        ];
    }

    /**
     * วันที่ไทยเต็มของวันนี้ เช่น "วันศุกร์ที่ 31 กรกฎาคม 2569"
     *
     * USER SPEC 2026-07-31: "ควรบอกด้วยเป็นดวงประจำวันที่เท่าไหร่"
     * ใช้ พ.ศ. (ค.ศ. + 543) ตามที่ลูกค้าไทยคุ้นเคย
     */
    protected function thaiFullDate(): string
    {
        return $this->thaiDateOf(now());
    }

    /**
     * ชื่อเดือนไทย index 1-12
     */
    protected const THAI_MONTHS = [
        1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
    ];

    /**
     * 🌙 (2026-07-31) กล่องดวงรายวัน "ตามคำขอของลูกค้า" — ใช้ในโหมด daily
     *
     * ต่างจาก buildDailyHoroscopeBox() ตรงที่:
     *   1. **ไม่จอง Cache slot รายวัน** — ลูกค้าเป็นฝ่ายขอเอง ห้ามติดลิมิต "ครั้งแรกของวัน"
     *      ของ DM อัตโนมัติ ไม่งั้นคนที่ได้กล่อง 7 วันไปแล้วตอนเช้า พอตอบวันเกิดกลับมา
     *      จะได้ null = บอทเงียบใส่คนที่เพิ่งตอบเรา (แย่ที่สุด)
     *      ⚠️ ห้ามเรียก releaseDailyHoroscopeBoxSlot() ที่นี่ด้วย — จะไปล้างสิทธิ์ของ DM
     *         อัตโนมัติ ทำให้ลูกค้าได้กล่องซ้ำ 2 รอบในวันเดียว
     *   2. รู้ index วันเกิดมาแล้ว (จากปุ่มหรือจากที่ลูกค้าพิมพ์) ไม่ต้องเดาจาก DB
     *   3. ถ้าวันนี้ยังไม่มีบทความ → ย้อนหลังได้ถึง 2 วัน **พร้อมบอกลูกค้าตามตรง**
     *      (job สร้างบทความรัน 06:00 — คนทักตี 2-5 หรือ job fail จะไม่มีของวันนี้)
     *
     * @param  int  $dayIndex  0=อาทิตย์ … 6=เสาร์ (ตรงกับคอลัมน์ birth_day)
     * @return array{text: string, stale_days: int}|null null = ไม่มีบทความเลยแม้ย้อนหลัง
     */
    public function buildDailyBoxForDayIndex(int $dayIndex, string $name): ?array
    {
        try {
            if ($dayIndex < 0 || $dayIndex > self::MAX_DAY_INDEX) {
                return null;
            }

            $displayName = $this->normalizeName($name);

            [$prediction, $staleDays] = $this->findPredictionWithinDays($dayIndex, self::DAILY_FALLBACK_DAYS);

            if ($prediction === null) {
                return null;
            }

            $body = trim((string) $prediction->overall_prediction_th);
            if ($body === '') {
                return null;
            }

            // บอกตามตรงเมื่อใช้ของย้อนหลัง — ห้ามแอบส่งของเก่าเป็นของวันนี้
            $header = $staleDays === 0
                ? "🌙 ดวงประจำ{$this->thaiFullDate()}"
                : '🌙 ดวงของคนเกิดวัน'.self::DAY_NAMES[$dayIndex]." (ฉบับล่าสุดที่แม่หมอเปิดไว้)\n"
                    .'📅 '.$this->thaiDateOf($prediction->target_date);

            $text = $header."\n"
                .($staleDays === 0
                    ? 'สำหรับคุณ '.$displayName.' — คนเกิดวัน'.self::DAY_NAMES[$dayIndex].' '.self::DAY_EMOJIS[$dayIndex]
                    : 'สำหรับคุณ '.$displayName.' '.self::DAY_EMOJIS[$dayIndex])
                ."\n\n".$body
                .$this->buildSectionsBlock($prediction)
                .$this->buildTimeLine($prediction)
                .$this->buildLuckyLine($prediction);

            // 🔗 ลิงก์โพสของเพจ — แนบ **ทุกครั้ง** (เจ้าของสั่ง 2026-08-19)
            //
            //    เดิมกันไว้ด้วย `$staleDays === 0` เพราะกลัวพาไปเจอหน้าว่าง
            //    แต่ด่านจริงอยู่ที่ todayHoroscopePostUrl() อยู่แล้ว — ไม่มีโพสของวันนี้
            //    มันคืน null และ buildReadMoreLine() คืน '' เอง ⇒ ไม่มีทางแนบลิงก์เสีย
            //    เงื่อนไขเดิมจึงกันเฉพาะเคสที่ "มีโพสให้อ่านจริง แต่เราไม่ยอมให้ลิงก์"
            //    ซึ่งเป็นเคสที่ลูกค้าต้องการลิงก์มากที่สุด (ได้ของย้อนหลัง อยากดูของวันนี้)
            //    ⚠️ ลำดับสำคัญ — คำบอกว่า "ของวันนี้ยังไม่พร้อม" ต้องมา **ก่อน** ลิงก์
            //       ไม่งั้นจะกลายเป็นชวนอ่าน "โพสของเพจวันนี้" แล้วค่อยบอกว่าของวันนี้ยังไม่เสร็จ
            //       = ขัดกันเองในกล่องเดียว
            if ($staleDays > 0) {
                $text .= "\n\n(ของวันนี้แม่หมอกำลังเปิดตำราอยู่ค่ะ เดี๋ยวพร้อมแล้วทักมาถามใหม่ได้นะคะ)";
                Log::info('🌙 Daily: ใช้บทความย้อนหลังแทนของวันนี้', [
                    'day_index' => $dayIndex,
                    'stale_days' => $staleDays,
                ]);
            }

            $text .= $this->buildReadMoreLine();

            return ['text' => $text, 'stale_days' => $staleDays];
        } catch (\Throwable $e) {
            Log::warning('FortuneGreetingService: buildDailyBoxForDayIndex ล้ม', [
                'day_index' => $dayIndex,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🌙 ข้อความสำรองเมื่อ "ไม่มีบทความเลย" — ห้ามให้ลูกค้าที่ตอบเรามาแล้วเจอความเงียบ
     *
     * ผู้เรียกต้องใช้ข้อความนี้เสมอเมื่อ buildDailyBoxForDayIndex() คืน null
     */
    public function buildDailyUnavailableMessage(string $name, ?int $dayIndex = null): string
    {
        $displayName = $this->normalizeName($name);

        $dayPart = ($dayIndex !== null && isset(self::DAY_NAMES[$dayIndex]))
            ? 'คนเกิดวัน'.self::DAY_NAMES[$dayIndex].' '
            : '';

        return "🙏 ขออภัยค่ะคุณ {$displayName}\n"
            ."ดวงรายวันของ{$dayPart}วันนี้แม่หมอยังเปิดตำราไม่เสร็จค่ะ\n\n"
            .'เดี๋ยวพร้อมแล้วทักมาถามใหม่ได้เลยนะคะ 🌙';
    }

    /**
     * จำนวนวันย้อนหลังสูงสุดที่ยอมใช้บทความเก่าแทน (บอกลูกค้าตามตรงทุกครั้ง)
     */
    protected const DAILY_FALLBACK_DAYS = 2;

    /**
     * หาบทความของวันเกิดนี้ ย้อนหลังได้ไม่เกิน N วัน
     *
     * @return array{0: HoroscopeDailyPrediction|null, 1: int} [บทความ, เก่ากี่วัน]
     */
    protected function findPredictionWithinDays(int $dayIndex, int $maxBackDays): array
    {
        for ($back = 0; $back <= $maxBackDays; $back++) {
            $date = now()->subDays($back)->toDateString();

            $prediction = HoroscopeDailyPrediction::query()
                ->where('target_date', $date)
                ->where('prediction_type', 'birth_day')
                ->where('birth_day', $dayIndex)
                ->where('status', 'generated')
                ->whereNotNull('overall_prediction_th')
                ->latest('generated_at')
                ->first();

            if ($prediction !== null) {
                return [$prediction, $back];
            }
        }

        return [null, 0];
    }

    /**
     * แปลงวันที่เป็นข้อความไทย (ใช้กับบทความย้อนหลัง)
     */
    protected function thaiDateOf(mixed $date): string
    {
        try {
            $c = $date instanceof Carbon ? $date : Carbon::parse((string) $date);
        } catch (\Throwable $e) {
            return '-';
        }

        return 'วัน'.(self::DAY_NAMES[$c->dayOfWeek] ?? '')
            .'ที่ '.$c->day
            .' '.(self::THAI_MONTHS[(int) $c->month] ?? '')
            .' '.($c->year + 543);
    }

    /**
     * 🔔 (2026-07-31) ข้อความ "ดวงของคุณพร้อมแล้ว จะดูไหม" สำหรับคนที่เรารู้วันเกิดแล้ว
     *
     * owner: "คนที่มีข้อมูลอยู่แล้วให้ส่งเป็น คำทำนายประจำวันเกิด วันที่... ของคุณ
     *         พร้อมแล้วจะดูไหม แล้วก็รอให้กดปุ่มเพื่อส่งเต็ม ข้อความไม่ขาด"
     *
     * ทำไมต้องรอให้กดปุ่มก่อน:
     *   ลูกค้าที่ไม่ได้ทักเพจใน 24 ชม. อยู่นอกหน้าต่างของ FB → ส่งคำทำนายเต็มไปเลย
     *   จะถูกยุบรวมเป็น Private Reply เดียวแล้วตัดจนอ่านไม่รู้เรื่อง
     *   **การกดปุ่มเปิดหน้าต่าง 24 ชม. ให้เอง** → ตอบฉบับเต็มกลับได้ครบทุกตัวอักษร
     *   ข้อความเชิญนี้สั้น (~120 ตัวอักษร) จึงส่งผ่านได้สบาย
     *
     * @return string|null null = ยังไม่รู้วันเกิด หรือวันนี้ยังไม่มีบทความ
     *                     (ผู้เรียกจะกลับไปใช้คำเชิญบอกวันเกิด + ปุ่ม 7 วัน)
     */
    public function buildDailyReadyTeaser(string $userId, string $name): ?string
    {
        try {
            // 🌙 (2026-08-04) ต้องเป็น findBirthDayIndex ไม่ใช่ findLatestBirthdate —
            //   บทความรายวันต้องการแค่ "วันในสัปดาห์" และคนส่วนใหญ่ตอบเราด้วย**ปุ่ม**
            //   ซึ่งให้ birth_day มาแต่ไม่มี birth_date (prod: 416/493 = 84%)
            //   ใช้ตัวเก่า = ถามวันเกิดซ้ำใส่คนที่เพิ่งตอบเมื่อวาน เหมือนบอทไม่จำอะไรเลย
            $dayIndex = FortuneUserCredit::findBirthDayIndex($userId);

            if ($dayIndex === null) {
                return null;   // ยังไม่รู้วันเกิด → ไปทางคำเชิญ
            }

            // 🔕 (2026-09-01) รับดวงของวันนี้ไปแล้ว → ห้าม tease "พร้อมแล้ว อยากดูไหม" ซ้ำ
            //   (rule_daily_horoscope_no_repeat_upsell — daily_dm_answered_at stamp ตอนส่งกล่องดวง)
            //   คืน null ให้ผู้เรียกตกไปคำเชิญปกติ ซึ่งอย่างน้อยไม่ได้อ้างว่ามีของใหม่รออยู่
            $answeredAt = FortuneUserCredit::findByUser($userId, 'facebook')?->daily_dm_answered_at;
            if ($answeredAt && \Illuminate\Support\Carbon::parse($answeredAt)->isToday()) {
                return null;
            }

            // มีบทความของวันนี้จริงไหม — ห้ามชวนดูของที่ยังไม่มี
            // (ช่วงหลังเที่ยงคืนถึง 6 โมงจะยังไม่มี → คืน null ใช้ระบบเดิม)
            if ($this->findTodayPrediction($dayIndex) === null) {
                return null;
            }

            $displayName = $this->normalizeName($name);

            return "🌙 คุณ {$displayName} คำทำนายประจำวัน".self::DAY_NAMES[$dayIndex].' '.self::DAY_EMOJIS[$dayIndex]."\n"
                .'ประจำ'.$this->thaiFullDate()." พร้อมแล้วค่ะ\n\n"
                .'อยากดูไหมคะ กดปุ่มด้านล่างได้เลย ✨';
        } catch (\Throwable $e) {
            Log::warning('FortuneGreetingService: buildDailyReadyTeaser ล้ม', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🙏 (2026-07-31) สุ่มคำอวยพรปิดท้ายคำทำนาย
     *
     * owner: "เพิ่มรูปแบบคำอวยพรให้หลากหลาย ดูศักดิ์สิทธิ์ อบอุ่นมากขึ้นสัก 60 แบบ"
     *
     * เก็บใน config/fortune-blessings.php (แพทเทิร์นเดียวกับ fortune-philosophies)
     * แอดมินแก้/เพิ่มได้เลยโดยไม่ต้อง migrate
     *
     * @param  string|null  $seed  ใส่เพื่อให้คนเดิม+วันเดิมได้คำเดิม (ไม่สุ่มใหม่ทุกข้อความ)
     * @return string '' ถ้าไม่มีคำอวยพรเลย (ผู้เรียกต้องรับมือได้)
     */
    public function pickBlessing(?string $seed = null): string
    {
        try {
            $list = (array) config('fortune-blessings', []);
            $list = array_values(array_filter($list, fn ($b) => is_string($b) && trim($b) !== ''));

            if ($list === []) {
                return '';
            }

            // มี seed → เลือกแบบคงที่ (คนเดิม วันเดิม ได้คำเดิม ไม่สลับไปมาในบทสนทนาเดียว)
            $index = $seed !== null
                ? crc32($seed) % count($list)
                : array_rand($list);

            return trim($list[$index]);
        } catch (\Throwable $e) {
            Log::warning('FortuneGreetingService: สุ่มคำอวยพรล้ม', ['error' => $e->getMessage()]);

            return '';
        }
    }

    /**
     * ⏰ (2026-07-31) วันนี้มีบทความดวงรายวันพร้อมเสิร์ฟหรือยัง
     *
     * owner: "หลังเที่ยงคืน ต้องสลับกลับไปเป็น DM แบบเก่า จนกว่าจะ 6 โมง"
     *
     * ⚠️ ตัดสินจาก **ข้อมูลจริง ไม่ใช่นาฬิกา** — เช็คว่ามีบทความของวันนี้ไหม
     *   ดีกว่าเช็ค "เลย 6 โมงหรือยัง" เพราะครอบเคสที่ job สร้างบทความพัง/ช้าด้วย
     *   (ถ้าเช็คแต่เวลา พอ job ล่มตอน 6 โมง บอทจะชวนลูกค้าทั้งวันแล้วส่งของไม่ได้)
     *
     * cache 5 นาที — เมธอดนี้ถูกเรียกทุก DM ขาออก ยิง DB ทุกครั้งไม่ไหว
     * และ 5 นาทีคือความหน่วงที่รับได้สำหรับการสลับโหมดตอนเช้า
     */
    public function dailyArticlesReadyToday(): bool
    {
        try {
            return (bool) Cache::remember(
                'fortune:daily_articles_ready:'.now()->toDateString(),
                300,
                fn () => HoroscopeDailyPrediction::query()
                    ->where('target_date', now()->toDateString())
                    ->where('prediction_type', 'birth_day')
                    ->where('status', 'generated')
                    ->whereNotNull('overall_prediction_th')
                    ->exists()
            );
        } catch (\Throwable $e) {
            // เช็คไม่ได้ → ถือว่ายังไม่พร้อม (ปลอดภัยกว่าชวนแล้วส่งของไม่ได้)
            return false;
        }
    }

    /**
     * 🔄 (2026-08-08) ล้างแคช "บทความวันนี้พร้อมหรือยัง"
     *
     * `dailyArticlesReadyToday()` แคชคำตอบไว้ 300 วินาที — เร็วดีสำหรับด่านขาออก
     * ที่ถูกเรียกทุก DM แต่กลายเป็นกับดักเวลาเราเพิ่งสร้างบทความเสร็จ:
     * ค่าเดิม (false) จะยังถูกตอบต่ออีกได้ถึง 5 นาที = DM ช่วงนั้นยัง fallback
     * ไปชุดขายแบบเก่าทั้งที่ของพร้อมแล้ว
     *
     * ⇒ ทุกจุดที่ "สร้างบทความเสร็จแล้วอยากให้มีผลทันที" ต้องเรียกตัวนี้
     *   (fortune:daily-preflight --heal เรียกให้แล้ว)
     */
    public function forgetDailyArticlesReadyCache(?string $date = null): void
    {
        try {
            Cache::forget('fortune:daily_articles_ready:'.($date ?? now()->toDateString()));
        } catch (\Throwable $e) {
            // แคชล้มไม่ควรทำให้ flow กู้คืนพัง — เดี๋ยว TTL 300 วิ ก็หมดเอง
            Log::warning('FortuneGreetingService: ล้างแคช daily_articles_ready ไม่สำเร็จ', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🩺 ตรวจความพร้อมของโหมดดูดวงรายวัน (ใช้โดย fortune:daily-preflight)
     *
     * @return array{ready: bool, today: string, found: int, missing: array<int, string>}
     */
    public function dailyPreflight(): array
    {
        $today = now()->toDateString();

        $have = HoroscopeDailyPrediction::query()
            ->where('target_date', $today)
            ->where('prediction_type', 'birth_day')
            ->where('status', 'generated')
            ->whereNotNull('overall_prediction_th')
            ->pluck('birth_day')
            ->map(fn ($d) => (int) $d)
            ->all();

        $missing = [];
        foreach (self::DAY_NAMES as $index => $dayName) {
            if (! in_array($index, $have, true)) {
                $missing[$index] = $dayName;
            }
        }

        return [
            'ready' => $missing === [],
            'today' => $today,
            'found' => count($have),
            'missing' => $missing,
        ];
    }

    /**
     * หาบทความดวงรายวันของ "วันนี้" ตาม index วันเกิด (0–6)
     */
    protected function findTodayPrediction(int $dayIndex): ?HoroscopeDailyPrediction
    {
        if ($dayIndex < 0 || $dayIndex > self::MAX_DAY_INDEX) {
            return null;
        }

        return HoroscopeDailyPrediction::query()
            ->where('target_date', now()->toDateString())
            ->where('prediction_type', 'birth_day')
            ->where('birth_day', $dayIndex)
            ->where('status', 'generated')
            ->whereNotNull('overall_prediction_th')
            ->latest('generated_at')
            ->first();
    }

    /**
     * 📋 (2026-08-02) ทำนายครบทุกด้าน — ความรัก/การงาน/การเงิน/สุขภาพ
     *
     * เจ้าของทัก: "จริง ๆ แล้วในบทความมีการทำนายครบทุกด้าน แต่ทำไมบอทส่งคำทำนายฟรี
     *   ให้สั้น ๆ เอง"
     *
     * ถูกต้อง — ของเดิมส่งแต่ `overall_prediction_th` ด้านเดียว ทั้งที่ job 6 โมง
     * สร้างครบ 5 ด้านเก็บไว้ใน DB อยู่แล้ว = จ่ายค่า AI ครบแต่ให้ลูกค้าเห็น 1 ใน 5
     *
     * ⚠️ เดิมกลัวข้อความขาด แต่กล่องนี้ถูกส่ง**หลังลูกค้ากดปุ่ม**เสมอ ซึ่งเปิดหน้าต่าง
     *    24 ชม. ของ FB ให้แล้ว + FacebookWebhookService::sendQuickReplies แบ่งข้อความ
     *    เกิน 1800 ตัวอักษรให้เอง (ปุ่มเกาะกล่องสุดท้าย) จึงส่งเต็มได้ปลอดภัย
     *    — ตรงกับเจตนาเดิมของเจ้าของที่ว่า "รอให้กดปุ่มเพื่อส่งเต็ม ข้อความไม่ขาด"
     *
     * ใส่เฉพาะด้านที่มีข้อมูลจริง — ดวงเก่าที่ parse ได้ไม่ครบจะไม่โผล่หัวข้อเปล่า
     */
    protected function buildSectionsBlock(HoroscopeDailyPrediction $prediction): string
    {
        $sections = [
            ['❤️ ความรัก', $prediction->love_prediction_th],
            ['💼 การงาน', $prediction->career_prediction_th],
            ['💰 การเงิน', $prediction->finance_prediction_th],
            ['💚 สุขภาพ', $prediction->health_prediction_th],
        ];

        $out = '';
        foreach ($sections as [$title, $body]) {
            $body = trim((string) $body);
            if ($body === '') {
                continue;
            }
            $out .= "\n\n{$title}\n{$body}";
        }

        return $out;
    }

    /**
     * 🔗 (2026-08-02) ลิงก์ไปอ่านดวงประจำวันฉบับเต็มบนเว็บของเรา
     *
     * เจ้าของสั่ง: "คำทำนายให้แนบลิงก์ไปอ่านดวงเพิ่มเติมจากเพจด้วย
     *   (ลิงก์ที่คำทำนายรายวันของเรา) — เฉพาะคำทำนายฟรีประจำวันนะ"
     *
     * ⚠️ **เฉพาะกล่องดวงฟรีรายวันเท่านั้น** — เมธอดนี้ถูกเรียกจาก
     *    buildDailyBoxForDayIndex() ที่มีผู้เรียกแค่ 2 จุด ทั้งคู่อยู่ในเส้นดวงฟรี
     *    (DailyHoroscopeModeTrait) เส้น Deep 39 / Celtic 99 ใช้ตัวประกอบข้อความคนละชุด
     *    จึงไม่มีลิงก์นี้ติดไปตามที่เจ้าของกำหนด
     *
     * ⚠️ **ห้ามเอาไปใส่ในโพส Facebook** — FB ลดการมองเห็นโพสที่มีลิงก์ออกนอกแพลตฟอร์ม
     *    (กฎข้อ 7 ใน prompt ของคอนเทนต์อัตโนมัติสั่งห้ามลิงก์อยู่แล้ว) ตัวนี้ใช้ในแชท
     *    ซึ่งไม่ติดข้อจำกัดนั้น
     *
     * เจ้าของแก้ทิศทาง (2026-08-02): "ถ้าอยากดูของวันอื่น ให้อ่านบนเพจ แล้วแนบลิงก์
     *   คำทำนายรายวันของเพจที่โพสไป"
     *
     * → ใช้ **ลิงก์โพสบนเพจ Facebook** ไม่ใช่หน้าเว็บของเรา เพราะโพสดวงรายวัน
     *   เป็นแบบ combined = มีครบทั้ง 7 วันเกิดในโพสเดียว ตอบโจทย์ "อยากดูของวันอื่น"
     *   ได้ตรงกว่าหน้าเว็บที่แยกเป็นวันละหน้า และลูกค้าอยู่บน FB อยู่แล้ว ไม่ต้องออกแอป
     *
     * ⚠️ คืน '' เมื่อยังไม่มีโพสของวันนี้ — แนบลิงก์ที่ยังไม่มีของ = พาไปเจอ 404
     */
    protected function buildReadMoreLine(): string
    {
        $url = $this->todayHoroscopePostUrl();

        if ($url === null) {
            return '';
        }

        return "\n\n———\n📖 อยากดูดวงของวันเกิดอื่นด้วย อ่านได้ที่โพสของเพจวันนี้เลยค่ะ\n"
            .$url;
    }

    /**
     * ลิงก์โพส "ดวงรายวัน" ของวันนี้บนเพจ Facebook
     *
     * มาจากระบบแคมเปญ (FortuneHoroscopePost) ที่โพสแบบ combined ทุกเช้า —
     * ไม่ใช่ระบบเก่ารายวันเกิด (FortuneDailyHoroscopePost) ที่ปิดอยู่
     *
     * cache 10 นาที — เมธอดนี้ถูกเรียกทุกครั้งที่ส่งดวงฟรีให้ลูกค้า
     * และค่าเปลี่ยนแค่วันละครั้งตอนโพสเช้า
     *
     * @return string|null null = วันนี้ยังไม่ได้โพส / โพสไม่สำเร็จ / ตารางมีปัญหา
     */
    protected function todayHoroscopePostUrl(): ?string
    {
        try {
            return Cache::remember(
                'fortune:daily_fb_post_url:'.now()->toDateString(),
                600,
                function () {
                    $url = FortuneHoroscopePost::query()
                        ->whereDate('target_date', now()->toDateString())
                        ->where('platform', FortuneHoroscopePost::PLATFORM_FACEBOOK)
                        ->where('status', FortuneHoroscopePost::STATUS_POSTED)
                        ->whereNotNull('platform_post_url')
                        ->latest('id')
                        ->value('platform_post_url');

                    return $url ?: null;
                }
            );
        } catch (\Throwable $e) {
            Log::warning('FortuneGreetingService: หาลิงก์โพสดวงรายวันไม่สำเร็จ', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🕐 (2026-08-02) บล็อกคำทำนายรายช่วงเวลา — เช้า/เที่ยง/บ่าย/เย็น/กลางคืน
     *
     * เจ้าของสั่ง: "คำนวณช่วงเวลาของวันไปด้วย … ให้คำทำนายครบ จะได้ครบข้อมูล"
     *
     * ใส่เฉพาะที่มีจริง — ดวงที่ generate ไว้ก่อนหน้าคอลัมน์นี้ยังว่าง
     * ต้องไม่โผล่หัวข้อเปล่า ๆ ให้ลูกค้าเห็น
     */
    protected function buildTimeLine(HoroscopeDailyPrediction $prediction): string
    {
        $body = trim((string) ($prediction->time_prediction_th ?? ''));

        return $body === '' ? '' : "\n\n⏰ ช่วงเวลาของวัน\n".$body;
    }

    /**
     * บรรทัดของนำโชค — ใส่เฉพาะที่มีข้อมูลจริง
     */
    protected function buildLuckyLine(HoroscopeDailyPrediction $prediction): string
    {
        $parts = [];

        if (! empty($prediction->lucky_number)) {
            $parts[] = 'เลขนำโชค '.$prediction->lucky_number;
        }
        if (! empty($prediction->lucky_color_th)) {
            $parts[] = 'สีมงคล '.$prediction->lucky_color_th;
        }

        return $parts === [] ? '' : "\n\n🍀 ".implode(' · ', $parts);
    }

    /**
     * ชื่อวัน/อีโมจิ index 0=อาทิตย์ … 6=เสาร์ — ตรงกับคอลัมน์ birth_day
     *
     * 🌙 (2026-09-05) index 7 = "พุธกลางคืน" (ราหู) วันเกิดที่ 8 ตามตำราไทย
     *    ⚠️ index นี้มาจาก **คำที่ลูกค้าพิมพ์เอง** เท่านั้น ("วันพุธกลางคืน")
     *    ห้ามคำนวณจาก `$birthdate->dayOfWeek` เพราะวันที่ในปฏิทินไม่มีเวลาเกิดติดมาด้วย
     *    การเดาข้างผิดเปลี่ยนดาวเจ้าเรือนทั้งดวง ([[rule_birth_time_ask_and_parse]])
     */
    protected const DAY_NAMES = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'พุธกลางคืน'];

    protected const DAY_EMOJIS = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣', '🌘'];

    /** ดัชนีวันเกิดสูงสุดที่รับได้ (7 = พุธกลางคืน) */
    protected const MAX_DAY_INDEX = 7;

    /**
     * คำทักทายสำหรับลูกค้าเก่า — มีวันเกิดใน DB
     */
    protected function buildHoroscopeForKnownUser(string $name, Carbon $birthdate): string
    {
        // 🚨 (2026-09-03) เดิมอ่าน `FortuneDailyHoroscopePost` = ตารางของระบบเก่ารายวันเกิด
        //    ซึ่งถูกปิดสวิตช์ (`daily_horoscope_per_day_enabled=0`) และหยุดเขียนตั้งแต่
        //    29 เม.ย. 2569 ⇒ `findTodayForDayOfBirth()` คืน null ทุกครั้ง ⇒ ลูกค้าที่
        //    ระบบ**รู้วันเกิดแล้ว** ได้ประโยคน้ำเปล่า "พลังดวงดาวกำลังหมุนเวียน" มา 4 เดือน
        //    ทั้งที่บทความจริงของวันนั้นถูกสร้างครบ 7 วันเกิดทุกวันอยู่แล้ว
        //    (เป็นแผลเดียวกับที่เคยเจอใน DM greeting 2026-07-31 — ตอนนั้นแก้แค่เลนกล่องดวง)
        //
        // ⚠️ index: `horoscope_daily_predictions.birth_day` ใช้ 0=อาทิตย์…6=เสาร์
        //    = Carbon::dayOfWeek **ไม่ใช่** dayOfWeekIso (1=จันทร์…7=อาทิตย์) ที่เคยใช้
        $dayIndex = (int) $birthdate->dayOfWeek;
        $dayName = self::DAY_NAMES[$dayIndex] ?? '?';
        $dayEmoji = self::DAY_EMOJIS[$dayIndex] ?? '✨';

        $prediction = $this->findTodayPrediction($dayIndex);

        if ($prediction !== null) {
            $teaser = trim((string) $prediction->overall_prediction_th);
            if ($teaser !== '') {
                $teaser = mb_strlen($teaser) > 140 ? mb_substr($teaser, 0, 140).'…' : $teaser;

                return "🌙 สวัสดีคุณ {$name}\n"
                    ."ดวงประจำวันสำหรับคนเกิดวัน{$dayName} {$dayEmoji}\n\n"
                    .$teaser."\n\n"
                    .'💫 อยากให้หมอเปิดเชิงลึก ทักมาบอกได้เลยค่ะ ✨';
            }
        }

        // วันนี้ยังไม่มีบทความ (job 00:01 ยังไม่รัน / ล้ม) → teaser generic ตามวันเกิด
        return $this->buildGenericByDay($name, $dayName, $dayEmoji);
    }

    /**
     * Fallback ถ้าไม่มี post วันนี้ — generic ตามวัน
     */
    protected function buildGenericByDay(string $name, string $dayName, string $dayEmoji): string
    {
        return "🌙 สวัสดีคุณ {$name}\n"
            ."คนเกิดวัน{$dayName} {$dayEmoji} วันนี้พลังดวงดาวกำลังหมุนเวียนค่ะ\n\n"
            .'🔮 อยากรู้ดวงเชิงลึกวันนี้ ทักมาคุยกับหมอจันทราได้เลยนะคะ ✨';
    }

    /**
     * คำทักทายสำหรับลูกค้าใหม่ — ยังไม่มีข้อมูล
     *
     * Tone: อบอุ่น เปิดประตู ไม่กดดัน ไม่สัญญา ไม่ล่อด้วยของฟรี
     */
    protected function buildInviteForNewUser(string $name): string
    {
        return "🌙 สวัสดีค่ะคุณ {$name}\n\n"
            ."ยินดีที่ได้เจอกันค่ะ ✨\n"
            .'หมอจันทราเปิดดูดวงไพ่ยิปซีอยู่ ถ้าสนใจทักมาคุยกันได้นะคะ';
    }

    /**
     * ป้องกัน name เป็น "FACEBOOK-XXXXX" หรือเปล่า — fallback "คุณ"
     */
    protected function normalizeName(?string $name): string
    {
        if (empty($name)) {
            return 'คุณ';
        }

        $name = trim($name);

        // กรอง code-pattern (raw FB id, empty, "คุณ" placeholder ที่เป็น default)
        if ($name === '' || $name === 'คุณ' || preg_match('/^FACEBOOK-/i', $name)) {
            return 'คุณ';
        }

        return $name;
    }
}
