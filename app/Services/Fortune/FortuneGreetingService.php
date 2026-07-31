<?php

namespace App\Services\Fortune;

use App\Models\FortuneDailyHoroscopePost;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
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
 * ใช้ FortuneDailyHoroscopePost ที่ scheduler สร้างไว้ — ถ้าวันนี้ไม่มี post
 * → fallback generic by day name (ยังคงมี personalization)
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
            // 1️⃣ สวิตช์ในส่วน DM — ปิดอยู่ → ไม่แนบกล่องนี้ (พฤติกรรมเดิม 100%)
            if (! (bool) FortuneTellingSetting::query()->value('dm_daily_horoscope_enabled')) {
                return null;
            }

            $displayName = $this->normalizeName($name);
            $birthdate = FortuneReading::findLatestBirthdate($userId);

            // 2️⃣ มีวันเกิด → ดวงเฉพาะวันเกิดนั้น / ไม่มี → ส่งทั้ง 7 วันให้เลือกอ่านเอง
            $boxes = $birthdate instanceof Carbon
                ? $this->buildTodayBoxForBirthday($displayName, $birthdate)
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
    protected function buildTodayBoxForBirthday(string $name, Carbon $birthdate): ?array
    {
        // ⚠️ ต้องใช้ dayOfWeek (0=อาทิตย์ … 6=เสาร์) ให้ตรงกับคอลัมน์ birth_day
        //    ห้ามใช้ dayOfWeekIso (1–7) เพราะคนเกิดวันอาทิตย์จะได้ 7 = ไม่มีในตาราง
        $dayIndex = $birthdate->dayOfWeek;

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

        $header = [
            "🌙 ดวงประจำ{$this->thaiFullDate()}",
            "ทั้ง 7 วันเกิดค่ะคุณ {$name} — เลื่อนหาวันเกิดของเจ้าชะตาได้เลยนะคะ ✨",
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
            if ($dayIndex < 0 || $dayIndex > 6) {
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
                .$this->buildLuckyLine($prediction);

            if ($staleDays > 0) {
                $text .= "\n\n(ของวันนี้แม่หมอกำลังเปิดตำราอยู่ค่ะ เดี๋ยวพร้อมแล้วทักมาถามใหม่ได้นะคะ)";
                Log::info('🌙 Daily: ใช้บทความย้อนหลังแทนของวันนี้', [
                    'day_index' => $dayIndex,
                    'stale_days' => $staleDays,
                ]);
            }

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
        if ($dayIndex < 0 || $dayIndex > 6) {
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
     */
    protected const DAY_NAMES = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

    protected const DAY_EMOJIS = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣'];

    /**
     * คำทักทายสำหรับลูกค้าเก่า — มีวันเกิดใน DB
     */
    protected function buildHoroscopeForKnownUser(string $name, Carbon $birthdate): string
    {
        $dayOfBirth = $birthdate->dayOfWeekIso; // 1=จันทร์ ... 7=อาทิตย์
        $dayName = FortuneDailyHoroscopePost::DAY_NAMES[$dayOfBirth] ?? '?';
        $dayEmoji = FortuneDailyHoroscopePost::DAY_EMOJI[$dayOfBirth] ?? '✨';

        $post = FortuneDailyHoroscopePost::findTodayForDayOfBirth($dayOfBirth);

        if ($post !== null) {
            $teaser = $post->getShortCaption(140);
            if ($teaser !== '') {
                return "🌙 สวัสดีคุณ {$name}\n"
                    ."ดวงประจำวันสำหรับคนเกิดวัน{$dayName} {$dayEmoji}\n\n"
                    .$teaser."\n\n"
                    .'💫 อยากให้หมอเปิดเชิงลึก ทักมาบอกได้เลยค่ะ ✨';
            }
        }

        // วันนี้ยังไม่มี post → ใช้ teaser generic ตามวันเกิด
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
