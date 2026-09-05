<?php

namespace App\Services\Fortune;

use App\Models\FortuneHoroscopeContent;
use App\Models\HoroscopeDailyPrediction;
use App\Services\FortuneChartService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 🪞 DailyArticleMirror — ทำให้ "ดวงรายวันในแชท" กับ "โพสดวงรายวันบนเพจ" เป็นก้อนเดียวกัน
 * ─────────────────────────────────────────────────────────────────────────────
 * 🚨 (2026-09-05) ปัญหาที่แก้ — เจ้าของจับได้เอง:
 *   *"ดวงรายวันในแชทต้องดึงจากโพสรายวัน ไม่งั้นเสียโทเค็นสองรอบ และได้ไม่เหมือนกันด้วย"*
 *
 *   เดิมมี **2 รอบ AI ต่อวัน** สำหรับวันเกิดชุดเดียวกัน:
 *     เลน A (โพสเพจ) `FortuneHoroscopeService` → `fortune_horoscope_contents`  @ 00:00
 *     เลน B (แชท/เว็บ) `HoroscopeDailyService` → `horoscope_daily_predictions` @ 00:01
 *   ⇒ จ่ายค่า AI 2 เท่า และลูกค้าที่อ่านโพสแล้วทักมาได้ "คำทำนายคนละใบ" ของวันเดียวกัน
 *
 *   ตัวนี้ทำให้ **เลน A เป็นเจ้าของคำทำนายรอบเดียว** แล้วคัดลอกลงตารางของเลน B ให้
 *   ⇒ ทุกจุดปลายทางเดิม (กล่องแชท, DM, หน้าเว็บ, preflight, `dailyArticlesReadyToday()`)
 *     ยังอ่านตารางเดิมไม่ต้องแก้สักบรรทัด แต่ได้ข้อความเดียวกับที่โพสจริง
 *
 * ⏱️ ต่อสาย 2 จุด (ต้องมีทั้งคู่ — [[rule_feature_built_but_never_wired]]):
 *   1. `FortuneHoroscopeService::generateForBirthDay()` — คัดลอกทันทีที่โพสสร้างเสร็จ
 *      (ปิด race: เลน A เริ่ม 00:00:05 เลน B เริ่ม 00:01:02 ซึ่ง**คาบเกี่ยวกันจริง**
 *       บน prod — วันเกิดใบท้าย ๆ ของเลน A ยังไม่เสร็จตอนเลน B เริ่มวิ่ง)
 *   2. `HoroscopeDailyService::generateBirthDayPrediction()` — ก่อนยิง AI ให้มาหยิบก่อน
 *      (ตาข่ายสำหรับใบที่รอบ 00:00 ล้ม แล้ว `--heal` มาเก็บทีหลัง)
 *
 * 🛟 ถ้าไม่มีบทความของเลน A → คืน null แล้วเลน B ยิง AI เองเหมือนเดิม
 *    (เลน A พังทั้งวัน ต้องไม่ลากให้แชทไม่มีดวงส่ง)
 */
class DailyArticleMirror
{
    /**
     * 🕐 ตัวคั่นบล็อกช่วงเวลา — เลน A ถูกสั่ง (ฝั่งโค้ด) ให้ต่อท้ายบล็อกนี้เสมอ
     *
     * โพสบนเพจ **ตัดทิ้ง** ก่อนประกอบ (ดู FortuneHoroscopePublishService::stripPeriodBlock)
     * แชท**เก็บไว้** ลง `time_prediction_th` — เจ้าของสั่งไว้ 2026-08-02 ว่าต้องมีครบ
     * ⇒ ยิง AI ครั้งเดียว แต่ละปลายทางเลือกหยิบเอง
     */
    public const PERIOD_MARKER = '[ช่วงเวลา]';

    /**
     * หัวข้อที่ต้องแยกออกจาก `ai_prediction` ของเลน A → คอลัมน์ของเลน B
     *
     * ⚠️ ตั้งใจจับแบบ "คำสำคัญในบรรทัดหัวข้อ" ไม่ใช่ match เป๊ะ เพราะ template อยู่ใน DB
     *    ที่แอดมินแก้เองได้ + โมเดลใส่อีโมจิ/ตัวหนา/เลขข้อนำหน้าไม่คงที่
     *    ([[rule_db_prompt_overrides_code]]) — parse ไม่ได้ยังมีตาข่าย: ยัดทั้งก้อนเป็นภาพรวม
     *
     * ลำดับสำคัญ: หัวข้อที่ยาว/เจาะจงกว่าต้องมาก่อน ("การเงิน" ต้องมาก่อน "เงิน" ถ้ามี)
     *
     * @var array<string, string> field => regex ของคำในบรรทัดหัวข้อ
     */
    protected const SECTION_PATTERNS = [
        'overall' => 'ภาพรวม(?:วันนี้)?',
        'finance' => 'การเงิน(?:\s*[\/·|]\s*โชคลาภ)?|โชคลาภ|ดวงการเงิน|ดวงทรัพย์',
        'love' => 'ความรัก|เรื่องรัก|ดวงความรัก',
        'career' => 'การงาน(?:\s*[\/·|]\s*การเรียน)?|การเรียน|หน้าที่การงาน',
        'health' => 'สุขภาพ',
    ];

    /**
     * คัดลอกบทความของเลน A ลงตารางของเลน B
     *
     * idempotent — เรียกซ้ำได้ ทับของเดิมที่ target_date+birth_day เดียวกัน
     *
     * @return HoroscopeDailyPrediction|null null = บทความยังไม่พร้อม/คัดลอกไม่สำเร็จ
     */
    public function mirror(FortuneHoroscopeContent $content): ?HoroscopeDailyPrediction
    {
        try {
            $body = trim((string) $content->ai_prediction);
            if ($body === '' || $content->birth_day === null) {
                return null;
            }

            $birthDay = (int) $content->birth_day;
            $date = $content->target_date instanceof Carbon
                ? $content->target_date->copy()
                : Carbon::parse((string) $content->target_date);

            $parsed = $this->parse($body);

            // 🪐 คะแนนไม่ได้มาจาก AI — คำนวณจากศักดิ์ดาว+มุมสัมพันธ์จริง (ไม่ใช้ rand)
            //    ตัวนี้เป็นการ "คำนวณ" ล้วน ไม่กินโควตา AI (ดู DailyAstroBrief::scoreHint)
            $scoreHint = (int) ((new DailyAstroBrief)->build($birthDay, $date)['score_hint'] ?? 3);

            $prediction = HoroscopeDailyPrediction::updateOrCreate(
                [
                    'target_date' => $date->toDateString(),
                    'birth_day' => $birthDay,
                    'prediction_type' => 'birth_day',
                ],
                [
                    'overall_prediction_th' => $parsed['overall'],
                    'love_prediction_th' => $parsed['love'],
                    'career_prediction_th' => $parsed['career'],
                    'finance_prediction_th' => $parsed['finance'],
                    'health_prediction_th' => $parsed['health'],
                    'time_prediction_th' => $parsed['time'],
                    'overall_score' => $scoreHint,
                    'love_score' => $scoreHint,
                    'career_score' => $scoreHint,
                    'finance_score' => $scoreHint,
                    'health_score' => $scoreHint,
                    // 🔢 ของนำโชคยึดตามที่โพสไปแล้ว — ตัวเลขในแชทต้องไม่ขัดกับบนเพจ
                    //    สีมงคลงดถาวรตามคำสั่งเจ้าของ 2026-08-02 (เลน A เก็บ null อยู่แล้ว)
                    'lucky_number' => $content->lucky_number ?: null,
                    'lucky_color_th' => null,
                    'lucky_direction_th' => $content->lucky_direction ?: null,
                    'ai_provider_used' => $content->ai_text_provider_used,
                    'ai_model_used' => $content->ai_text_model_used,
                    'status' => 'generated',
                    'generated_at' => now(),
                ]
            );

            // แคชของเลน B ถือคำตอบเก่าไว้ 24 ชม. — ไม่ล้าง = คัดลอกเสร็จแล้วแชทยังไม่เห็น
            $this->forgetCaches($birthDay, $date);

            return $prediction->fresh();
        } catch (\Throwable $e) {
            // best-effort เสมอ — คัดลอกพังต้องไม่ทำให้โพสของวันนั้นล้มตาม
            Log::warning('DailyArticleMirror: คัดลอกบทความลงตารางแชทไม่สำเร็จ', [
                'content_id' => $content->id ?? null,
                'birth_day' => $content->birth_day ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * หาบทความของเลน A สำหรับวันเกิด/วันที่ที่ระบุ แล้วคัดลอกให้
     *
     * ใช้จากฝั่งเลน B (ก่อนยิง AI) — ไม่เจอ = คืน null ให้ผู้เรียกยิง AI เองตามเดิม
     *
     * @param  int  $birthDay  0=อาทิตย์ … 6=เสาร์ · 7=พุธกลางคืน
     */
    public function adoptForDay(int $birthDay, Carbon $date): ?HoroscopeDailyPrediction
    {
        try {
            $content = FortuneHoroscopeContent::query()
                ->whereDate('target_date', $date->toDateString())
                ->where('birth_day', $birthDay)
                ->where('status', FortuneHoroscopeContent::STATUS_GENERATED)
                ->whereNotNull('ai_prediction')
                ->latest('id')
                ->first();

            return $content === null ? null : $this->mirror($content);
        } catch (\Throwable $e) {
            Log::warning('DailyArticleMirror: หาบทความของเลนโพสไม่สำเร็จ', [
                'birth_day' => $birthDay,
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * แยกคำทำนายก้อนเดียวของเลน A ออกเป็นช่องของเลน B
     *
     * @return array{overall:string, love:?string, career:?string, finance:?string, health:?string, time:?string}
     */
    public function parse(string $raw): array
    {
        $text = str_replace("\r\n", "\n", trim($raw));

        // 1) ตัดบล็อกช่วงเวลาออกก่อน — มันอยู่ท้ายสุดและมีหัวข้อย่อยของตัวเอง
        //    ถ้าไม่ตัดก่อน หัวข้อ "สุขภาพ" จะกวาดบล็อกนี้เข้ามาด้วย (regex จับถึงหัวข้อถัดไป
        //    หรือจบสตริง — ด้านสุดท้ายไม่มีหัวข้อตามหลัง จึงกินทุกอย่างที่เหลือ)
        [$text, $time] = $this->splitPeriodBlock($text);

        $out = [
            'overall' => '', 'love' => null, 'career' => null,
            'finance' => null, 'health' => null, 'time' => $time,
        ];

        // 2) หาตำแหน่งเริ่มของแต่ละหัวข้อ แล้วตัดเป็นช่วง ๆ ตามลำดับที่โผล่จริงในข้อความ
        //    (ไม่ยึดลำดับตายตัว เพราะ template ใน DB จัดลำดับเองได้)
        $marks = [];
        foreach (self::SECTION_PATTERNS as $field => $words) {
            if (preg_match($this->headingRegex($words), $text, $m, PREG_OFFSET_CAPTURE)) {
                $marks[] = [
                    'field' => $field,
                    'start' => $m[0][1],
                    'bodyAt' => $m[0][1] + strlen($m[0][0]),
                ];
            }
        }

        if ($marks === []) {
            // parse ไม่ได้เลย → ยัดทั้งก้อนเป็นภาพรวม (ยังใช้ได้ ดีกว่าคืนกล่องเปล่า)
            $out['overall'] = $this->clean($text);

            return $out;
        }

        usort($marks, fn ($a, $b) => $a['start'] <=> $b['start']);

        foreach ($marks as $i => $mark) {
            $end = $marks[$i + 1]['start'] ?? strlen($text);
            $out[$mark['field']] = $this->clean(substr($text, $mark['bodyAt'], $end - $mark['bodyAt']));
        }

        // เนื้อก่อนหัวข้อแรก (ถ้ามี) = คำนำที่ไม่มีหัวข้อ — เก็บไว้หน้าภาพรวม ห้ามทิ้ง
        $preamble = $this->clean(substr($text, 0, $marks[0]['start']));
        if ($preamble !== '') {
            $out['overall'] = trim($preamble."\n\n".(string) $out['overall']);
        }

        // ไม่มีหัวข้อ "ภาพรวม" เลย → เลื่อนด้านแรกที่เจอขึ้นมาเป็นภาพรวม แล้ว**ล้างช่องเดิม**
        // (ถ้าไม่ล้าง ลูกค้าจะเห็นย่อหน้าเดียวกัน 2 รอบในกล่องเดียว)
        // ต้องมีเนื้อในภาพรวมเสมอ — buildDailyBoxForDayIndex() คืน null ทันทีถ้าภาพรวมว่าง
        if (trim((string) $out['overall']) === '') {
            $first = $marks[0]['field'];
            $out['overall'] = (string) ($out[$first] ?? '');
            if ($first !== 'overall') {
                $out[$first] = null;
            }
        }

        foreach (['love', 'career', 'finance', 'health'] as $f) {
            $out[$f] = ($out[$f] === null || trim($out[$f]) === '') ? null : $out[$f];
        }

        return $out;
    }

    /**
     * แยกบล็อก [ช่วงเวลา] ออกจากเนื้อหลัก
     *
     * @return array{0:string, 1:?string} [เนื้อที่เหลือ, เนื้อในบล็อกช่วงเวลา]
     */
    public function splitPeriodBlock(string $text): array
    {
        // จับทั้ง "[ช่วงเวลา]" และรูปที่โมเดลชอบแต่งเอง (**ช่วงเวลาของวัน** / 🕐 ช่วงเวลา)
        $re = '/^[^\p{L}\p{N}]{0,6}(?:\[\s*)?ช่วงเวลา(?:ของวัน)?\s*(?:\])?[^\p{L}\p{N}\n]{0,6}\n/mu';

        if (! preg_match_all($re, $text, $all, PREG_OFFSET_CAPTURE)) {
            return [$text, null];
        }

        // ⚠️ ใช้ตัวที่ **อยู่ท้ายสุด** — prompt สั่งให้เขียนบล็อกนี้ปิดท้ายครั้งเดียว
        //    แต่โมเดลเผลอเขียนซ้ำได้ ถ้าตัดที่ตัวแรกจะกินเนื้อของด้านที่เหลือไปด้วย
        $m = [end($all[0])];

        $head = substr($text, 0, $m[0][1]);
        $body = substr($text, $m[0][1] + strlen($m[0][0]));

        $body = $this->clean($body);

        return [rtrim($head), $body === '' ? null : $body];
    }

    /**
     * regex ของ "บรรทัดหัวข้อ" — ทนอีโมจิ/ตัวหนา/เลขข้อ/วงเล็บเหลี่ยมนำหน้า
     *
     * ต้องเป็นบรรทัดที่ **ขึ้นต้น**ด้วยหัวข้อเท่านั้น (ใช้ `^` + /m) ไม่งั้นคำว่า
     * "ความรัก" ที่โผล่กลางประโยคของด้านอื่นจะถูกตัดเป็นหัวข้อใหม่
     */
    protected function headingRegex(string $words): string
    {
        // 🚨 (2026-09-05) วัดกับคอนเทนต์จริงบน prod แล้วพบ **2 ทรงที่ต่างกันคนละวัน**
        //    (template เดียวกัน — โมเดลเลือกเอง จึงต้องรับทั้งคู่):
        //      ทรง A "หัวข้อบรรทัดเดียว"  →  `💕 **ความรัก**\nคนโสด…`
        //      ทรง B "หัวข้อติดเนื้อ"      →  `💰 **การเงิน:** ช่วงบ่ายนี้…`  (บรรทัดยาว 193 ตัว)
        //    เกณฑ์เดิมที่ใช้ "บรรทัดต้องสั้นไม่เกิน 40 ตัว" รับได้แค่ทรง A ⇒ 4 ก.ย.
        //    มี 3 ใน 7 ใบที่แยกไม่ออกแล้วตกไปกองรวมในภาพรวม
        //
        // เกณฑ์ใหม่ = **ตัวปิดหัวข้อ** ไม่ใช่ความยาวบรรทัด:
        //   - ทรง B: หัวข้อต้องจบด้วยเครื่องหมาย `:` (กิน `:**` และช่องว่างตามหลังไปด้วย)
        //   - ทรง A: หัวข้อต้องจบที่ท้ายบรรทัด (กิน `**` ปิดตัวหนาไปด้วย)
        //   ⇒ ประโยคเนื้อที่บังเอิญขึ้นต้นด้วยคำเดียวกัน ("ความรักของเจ้าชะตาวันนี้จะ…")
        //     ไม่มีทั้งโคลอนและไม่จบบรรทัดตรงนั้น จึงไม่ถูกตัดเป็นหัวข้อ
        //
        // นำหน้าได้: อีโมจิ/ช่องว่าง/เลขข้อ/จุด/วงเล็บเหลี่ยม/ดอกจัน (**ตัวหนา**)
        return '/^[^\p{L}\n]{0,8}(?:'.$words.')'
            .'(?:'
            .'[^\p{L}\n]{0,4}[:：][^\p{L}\n]{0,4}[ \t]*'   // ทรง B — จบด้วยโคลอน
            .'|[^\p{L}\n]{0,8}\n'                          // ทรง A — จบท้ายบรรทัด
            .')/mu';
    }

    /**
     * ล้างเนื้อแต่ละด้าน — ตัดคำขอ engagement + ตัวคั่นตกแต่ง + ช่องว่างเกิน
     *
     * 🚫 คำขอไลก์/แชร์/แท็ก ใช้ลิสต์กลางที่ [[FacebookContentPolicy]] ไม่เก็บสำเนาของตัวเอง —
     *    ของแบบนี้ต้องมีสวิตช์เดียว ไม่งั้นอีกหกเดือนจะเหลือลิสต์ที่ตกรุ่นอยู่ที่ใดที่หนึ่ง
     *    (บนโพสมันคือ engagement hook แต่ใน**คำตอบบอท**ห้ามเด็ดขาด
     *     [[rule_never_ask_for_engagement_in_bot_replies]])
     */
    protected function clean(string $text): string
    {
        $text = trim(str_replace("\r", '', $text));

        // ตัดเส้นคั่นตกแต่งที่ composePostContent ใช้ (━━━ / ─── / ===)
        $text = preg_replace('/^[\x{2500}-\x{257F}=\-_*]{3,}\s*$/mu', '', $text) ?? $text;

        $text = FacebookContentPolicy::stripEngagementBait($text);

        // ยุบบรรทัดว่างซ้อน 3+ ให้เหลือ 2
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * ล้างแคชของเลน B ให้ตรงกับที่ HoroscopeDailyService ใช้
     *
     * ⚠️ 2 คีย์คนละหน้าที่ ต้องล้างทั้งคู่:
     *   - `horoscope:birthday:{d}:{date}` = ตัวบทความ (TTL 24 ชม.)
     *   - `fortune:daily_articles_ready:{date}` = ธง "วันนี้พร้อมเสิร์ฟหรือยัง" (TTL 5 นาที)
     *     ไม่ล้าง = ด่านขาออกยังตอบ false ต่ออีก 5 นาทีทั้งที่ของพร้อมแล้ว
     *     ([[incident_deploy_breaks_queue_worker]] ตระกูลเดียวกัน — ของมาแล้วแต่ธงยังเก่า)
     */
    protected function forgetCaches(int $birthDay, Carbon $date): void
    {
        try {
            \Illuminate\Support\Facades\Cache::forget("horoscope:birthday:{$birthDay}:{$date->toDateString()}");
            app(FortuneGreetingService::class)->forgetDailyArticlesReadyCache($date->toDateString());
        } catch (\Throwable $e) {
            Log::warning('DailyArticleMirror: ล้างแคชไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ชื่อวันเกิดของดัชนี — เผื่อผู้เรียกอยาก log ให้อ่านออก
     */
    public static function dayName(int $birthDay): string
    {
        return \App\Models\FortuneHoroscopeCampaign::THAI_DAYS[$birthDay]
            ?? (string) $birthDay;
    }

    /** ดัชนีวันเกิดทั้งหมดที่ระบบผลิตต่อวัน (7 วัน + พุธกลางคืน) */
    public static function allBirthDays(): array
    {
        return [0, 1, 2, 3, 4, 5, 6, FortuneChartService::WEDNESDAY_NIGHT];
    }
}
