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
     * @return string|null ข้อความกล่องดวงรายวัน หรือ null ถ้าไม่ต้องส่ง
     */
    public function buildDailyHoroscopeBox(string $userId, string $name, string $platform = 'facebook'): ?string
    {
        try {
            // 1️⃣ สวิตช์ในส่วน DM — ปิดอยู่ → ไม่แนบกล่องนี้ (พฤติกรรมเดิม 100%)
            if (! (bool) FortuneTellingSetting::query()->value('dm_daily_horoscope_enabled')) {
                return null;
            }

            $displayName = $this->normalizeName($name);
            $birthdate = FortuneReading::findLatestBirthdate($userId);

            // 2️⃣ มีวันเกิด → ดวงเฉพาะวันเกิดนั้น / ไม่มี → ส่งทั้ง 7 วันให้เลือกอ่านเอง
            $box = $birthdate instanceof Carbon
                ? $this->buildTodayBoxForBirthday($displayName, $birthdate)
                : $this->buildTodayBoxAllDays($displayName);

            // 3️⃣ วันนี้ยังไม่มีบทความ → ไม่ต้องมีกล่องนี้ (ไม่ใส่ generic แทน ตาม spec)
            if ($box === null) {
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

            return $box;
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
     */
    protected function buildTodayBoxForBirthday(string $name, Carbon $birthdate): ?string
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

        return "🌙 ดวงวันนี้ของคุณ {$name}\n"
            .'คนเกิดวัน'.self::DAY_NAMES[$dayIndex].' '.self::DAY_EMOJIS[$dayIndex]."\n\n"
            .$body
            .$this->buildLuckyLine($prediction);
    }

    /**
     * กล่องดวงรายวัน "ครบ 7 วันเกิด" — สำหรับลูกค้าที่ระบบยังไม่มีวันเกิด
     *
     * USER SPEC: "บางคนไม่มีวันเกิด ให้ส่งไปทั้งบทความเลยให้ลูกค้าเลือกอ่านเอง"
     */
    protected function buildTodayBoxAllDays(string $name): ?string
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

        $lines = ["🌙 ดวงวันนี้ทั้ง 7 วันเกิดค่ะคุณ {$name}", 'เลื่อนหาวันเกิดของเจ้าชะตาได้เลยนะคะ ✨', ''];

        foreach (self::DAY_NAMES as $index => $dayName) {
            $prediction = $predictions->get($index);
            if ($prediction === null) {
                continue;
            }

            $teaser = trim((string) $prediction->overall_prediction_th);
            if ($teaser === '') {
                continue;
            }

            // ตัดสั้นต่อวัน — 7 วันรวมกันต้องไม่บวมจนโดน FB ตัด
            $teaser = trim(preg_replace('/\s+/', ' ', $teaser));
            if (mb_strlen($teaser) > 110) {
                $teaser = mb_substr($teaser, 0, 109).'…';
            }

            $lines[] = self::DAY_EMOJIS[$index].' *วัน'.$dayName."*\n".$teaser;
            $lines[] = '';
        }

        // มีแต่หัวข้อ ไม่มีเนื้อสักวัน → ไม่ต้องส่งกล่องนี้
        if (count($lines) <= 3) {
            return null;
        }

        $lines[] = '💫 อยากให้แม่หมอเปิดเชิงลึกให้ ทักมาบอกวันเกิดได้เลยค่ะ';

        return implode("\n", $lines);
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
