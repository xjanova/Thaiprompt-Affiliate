<?php

namespace App\Services;

use App\Models\HoroscopeDailyPrediction;
use App\Models\HoroscopeZodiacSign;
use App\Services\Fortune\DailyAstroBrief;
use App\Services\Fortune\PlanetEphemeris;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * HoroscopeDailyService — สร้างและจัดการดวงรายวัน
 *
 * รับผิดชอบ:
 * - สร้างดวงรายวัน 12 ราศี ด้วย AI
 * - สร้างดวงรายวัน 7 วันเกิด ด้วย AI
 * - ดึงข้อมูลดวงพร้อม cache
 * - สร้าง prompt สำหรับ AI
 */
class HoroscopeDailyService
{
    protected FortuneAIService $aiService;

    protected FortuneChartService $chartService;

    /**
     * Cache TTL สำหรับดวงรายวัน (24 ชั่วโมง)
     */
    protected const CACHE_TTL = 86400;

    /**
     * 🔢 วันที่ของเดือนที่ "แจกเลขนำโชค" — นอกจากนี้ไม่ให้
     *
     * เจ้าของสั่ง (2026-08-02): "เลขนำโชคจะให้ในวันที่ 29 และวันที่ 15 เท่านั้น"
     * (สีมงคลงดถาวรทุกวัน — ไม่มีข้อยกเว้น)
     */
    public const LUCKY_NUMBER_DAYS = [15, 29];

    /**
     * ทิศมงคลตามดาว
     */
    protected const LUCKY_DIRECTIONS = [
        'sun' => 'ตะวันออก',
        'moon' => 'ตะวันตกเฉียงเหนือ',
        'mars' => 'ใต้',
        'mercury' => 'เหนือ',
        'jupiter' => 'ตะวันออกเฉียงเหนือ',
        'venus' => 'ตะวันออกเฉียงใต้',
        'saturn' => 'ตะวันตก',
    ];

    // (ลบ ELEMENT_COLORS + generateLuckyColorForZodiac ออก 2026-08-02 —
    //  สีมงคลถูกงดถาวรตามคำสั่งเจ้าของ ตารางสีจึงกลายเป็น dead code)

    /**
     * Constructor
     */
    public function __construct(FortuneAIService $aiService)
    {
        $this->aiService = $aiService;
        $this->chartService = new FortuneChartService;
    }

    // ==========================================
    // สร้างดวงรายวัน
    // ==========================================

    /**
     * สร้างดวงรายวันสำหรับทุกราศี
     *
     * @param  Carbon  $date  วันที่เป้าหมาย
     * @return array ผลการสร้าง ['success' => int, 'failed' => int, 'skipped' => int]
     */
    public function generateDailyForAllZodiacs(Carbon $date): array
    {
        $zodiacSigns = HoroscopeZodiacSign::active()->ordered()->get();
        $results = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($zodiacSigns as $zodiac) {
            try {
                // ตรวจสอบว่ามีดวงวันนี้แล้วหรือยัง
                $existing = HoroscopeDailyPrediction::where('target_date', $date->toDateString())
                    ->where('zodiac_sign_id', $zodiac->id)
                    ->where('prediction_type', 'zodiac')
                    ->where('status', 'generated')
                    ->first();

                if ($existing) {
                    $results['skipped']++;

                    continue;
                }

                $this->generateZodiacPrediction($zodiac, $date);
                $results['success']++;

                // หน่วง 1 วินาทีเพื่อไม่ให้ rate limit
                sleep(1);
            } catch (\Exception $e) {
                Log::error("ดวงราศี {$zodiac->name_th} สร้างไม่สำเร็จ: ".$e->getMessage());
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * สร้างดวงรายวันสำหรับทุกวันเกิด (7 วัน)
     *
     * @param  Carbon  $date  วันที่เป้าหมาย
     * @return array ผลการสร้าง
     */
    public function generateDailyForAllBirthDays(Carbon $date): array
    {
        $results = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        for ($day = 0; $day <= 6; $day++) {
            try {
                // ตรวจสอบว่ามีดวงวันนี้แล้วหรือยัง
                $existing = HoroscopeDailyPrediction::where('target_date', $date->toDateString())
                    ->where('birth_day', $day)
                    ->where('prediction_type', 'birth_day')
                    ->where('status', 'generated')
                    ->first();

                if ($existing) {
                    $results['skipped']++;

                    continue;
                }

                $this->generateBirthDayPrediction($day, $date);
                $results['success']++;

                sleep(1);
            } catch (\Exception $e) {
                Log::error("ดวงวันเกิด {$day} สร้างไม่สำเร็จ: ".$e->getMessage());
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * สร้างดวงราศีเดี่ยว
     */
    public function generateZodiacPrediction(HoroscopeZodiacSign $zodiac, Carbon $date): HoroscopeDailyPrediction
    {
        // สร้าง/อัปเดต record
        $prediction = HoroscopeDailyPrediction::updateOrCreate(
            [
                'target_date' => $date->toDateString(),
                'zodiac_sign_id' => $zodiac->id,
                'prediction_type' => 'zodiac',
            ],
            ['status' => 'generating']
        );

        try {
            // สร้าง prompt
            $prompt = $this->buildZodiacPrompt($zodiac, $date);

            // เรียก AI
            $result = $this->aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                promptTemplate: null,
                readingType: 'basic'
            );

            // แยกคำทำนายจาก AI response
            $parsed = $this->parseAIPrediction($result['response'] ?? '');

            // 🔢 (2026-08-02) เลขนำโชคเฉพาะวันที่ 15/29 · สีมงคลงดถาวร
            //    ใช้กฎเดียวกับดวงวันเกิด — ไม่งั้นสองระบบดวงรายวันขัดกันเองบนเว็บ
            $luckyNumber = in_array((int) $date->day, self::LUCKY_NUMBER_DAYS, true)
                ? $this->generateLuckyNumberForZodiac($zodiac)
                : null;
            $luckyDirection = $this->generateLuckyDirection($zodiac->ruling_planet);

            // อัปเดต prediction
            $prediction->update([
                'overall_prediction_th' => $parsed['overall'] ?? $result['response'],
                'love_prediction_th' => $parsed['love'] ?? null,
                'career_prediction_th' => $parsed['career'] ?? null,
                'finance_prediction_th' => $parsed['finance'] ?? null,
                'health_prediction_th' => $parsed['health'] ?? null,
                // 🎯 (2026-08-02) เดิม rand(2,5) — สุ่มดาวคะแนนให้ลูกค้าคือการมโนตรง ๆ
                //    ดวงราศียังไม่มีตัวคำนวณ ephemeris เฉพาะทาง (ต่างจากดวงวันเกิด)
                //    จึงใช้ 3 = "กลาง ๆ" อย่างซื่อสัตย์ แทนการสุ่มตัวเลขให้ดูมีอะไร
                'overall_score' => $parsed['scores']['overall'] ?? 3,
                'love_score' => $parsed['scores']['love'] ?? 3,
                'career_score' => $parsed['scores']['career'] ?? 3,
                'finance_score' => $parsed['scores']['finance'] ?? 3,
                'health_score' => $parsed['scores']['health'] ?? 3,
                'lucky_number' => $luckyNumber,
                'lucky_color_th' => null,
                'lucky_direction_th' => $luckyDirection,
                'ai_provider_used' => $result['provider'] ?? null,
                'ai_model_used' => $result['model'] ?? null,
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            // ล้าง cache
            $this->clearZodiacCache($zodiac->slug, $date);

            return $prediction->fresh();
        } catch (\Exception $e) {
            $prediction->update([
                'status' => 'failed',
                'overall_prediction_th' => 'เกิดข้อผิดพลาดในการสร้างดวง: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * สร้างดวงวันเกิดเดี่ยว
     *
     * @param  int  $birthDay  0-6 (อาทิตย์-เสาร์)
     */
    public function generateBirthDayPrediction(int $birthDay, Carbon $date): HoroscopeDailyPrediction
    {
        $prediction = HoroscopeDailyPrediction::updateOrCreate(
            [
                'target_date' => $date->toDateString(),
                'birth_day' => $birthDay,
                'prediction_type' => 'birth_day',
            ],
            ['status' => 'generating']
        );

        try {
            $brief = (new DailyAstroBrief)->build($birthDay, $date);
            $prompt = $this->buildBirthDayPromptFromBrief($brief);

            $result = $this->aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                promptTemplate: null,
                readingType: 'basic'
            );

            $parsed = $this->parseAIPrediction($result['response'] ?? '');

            $chaochana = FortuneChartService::CHAOCHANA[$birthDay] ?? [];
            $planetKey = $chaochana['planet'] ?? 'sun';

            // 🎯 (2026-08-02) คะแนน fallback มาจากศักดิ์ดาว+มุมสัมพันธ์จริง ไม่ใช่ rand()
            //    เดิม rand(2,5) = มโนตรง ๆ · วันเดิมรีเจนใหม่ได้คะแนนคนละอย่าง
            $fallbackScore = $brief['score_hint'];

            $prediction->update([
                'overall_prediction_th' => $parsed['overall'] ?? $result['response'],
                'love_prediction_th' => $parsed['love'] ?? null,
                'career_prediction_th' => $parsed['career'] ?? null,
                'finance_prediction_th' => $parsed['finance'] ?? null,
                'health_prediction_th' => $parsed['health'] ?? null,
                'time_prediction_th' => $parsed['time'] ?? null,
                'overall_score' => $parsed['scores']['overall'] ?? $fallbackScore,
                'love_score' => $parsed['scores']['love'] ?? $fallbackScore,
                'career_score' => $parsed['scores']['career'] ?? $fallbackScore,
                'finance_score' => $parsed['scores']['finance'] ?? $fallbackScore,
                'health_score' => $parsed['scores']['health'] ?? $fallbackScore,
                // 🔢 เลขนำโชคเฉพาะวันที่ 15 และ 29 · สีมงคลงดถาวร (เจ้าของสั่ง 2026-08-02)
                'lucky_number' => $this->luckyNumberForDate($birthDay, $date, $brief),
                'lucky_color_th' => null,
                'lucky_direction_th' => self::LUCKY_DIRECTIONS[$planetKey] ?? 'เหนือ',
                'ai_provider_used' => $result['provider'] ?? null,
                'ai_model_used' => $result['model'] ?? null,
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            $this->clearBirthDayCache($birthDay, $date);

            return $prediction->fresh();
        } catch (\Exception $e) {
            $prediction->update([
                'status' => 'failed',
                'overall_prediction_th' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ==========================================
    // ดึงข้อมูลดวง (พร้อม Cache)
    // ==========================================

    /**
     * ดึงดวงราศีวันนี้ (พร้อม cache 24 ชม.)
     */
    public function getZodiacPrediction(string $zodiacSlug, ?Carbon $date = null): ?HoroscopeDailyPrediction
    {
        $date = $date ?? today();
        $cacheKey = "horoscope:zodiac:{$zodiacSlug}:{$date->toDateString()}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($zodiacSlug, $date) {
            $zodiac = HoroscopeZodiacSign::where('slug', $zodiacSlug)->first();
            if (! $zodiac) {
                return null;
            }

            return HoroscopeDailyPrediction::where('target_date', $date->toDateString())
                ->where('zodiac_sign_id', $zodiac->id)
                ->where('prediction_type', 'zodiac')
                ->generated()
                ->first();
        });
    }

    /**
     * ดึงดวงวันเกิดวันนี้ (พร้อม cache)
     *
     * @param  int  $birthDay  0-6
     */
    public function getBirthDayPrediction(int $birthDay, ?Carbon $date = null): ?HoroscopeDailyPrediction
    {
        $date = $date ?? today();
        $cacheKey = "horoscope:birthday:{$birthDay}:{$date->toDateString()}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($birthDay, $date) {
            return HoroscopeDailyPrediction::where('target_date', $date->toDateString())
                ->where('birth_day', $birthDay)
                ->where('prediction_type', 'birth_day')
                ->generated()
                ->first();
        });
    }

    /**
     * ดึงดวงย้อนหลัง 7 วันของราศี
     *
     * @param  int  $days  จำนวนวันย้อนหลัง
     * @return \Illuminate\Support\Collection
     */
    public function getZodiacHistory(int $zodiacSignId, int $days = 7)
    {
        return HoroscopeDailyPrediction::where('zodiac_sign_id', $zodiacSignId)
            ->where('prediction_type', 'zodiac')
            ->generated()
            ->where('target_date', '>=', today()->subDays($days))
            ->orderByDesc('target_date')
            ->get();
    }

    /**
     * ดึงดวงย้อนหลัง 7 วันของวันเกิด
     *
     * @return \Illuminate\Support\Collection
     */
    public function getBirthDayHistory(int $birthDay, int $days = 7)
    {
        return HoroscopeDailyPrediction::where('birth_day', $birthDay)
            ->where('prediction_type', 'birth_day')
            ->generated()
            ->where('target_date', '>=', today()->subDays($days))
            ->orderByDesc('target_date')
            ->get();
    }

    // ==========================================
    // สร้าง AI Prompt
    // ==========================================

    /**
     * สร้าง prompt สำหรับดวงราศี
     */
    protected function buildZodiacPrompt(HoroscopeZodiacSign $zodiac, Carbon $date): string
    {
        $thaiDate = $date->locale('th')->translatedFormat('l j F').' '.($date->year + 543);

        return <<<PROMPT
คุณเป็นนักโหราศาสตร์ระดับสูง เชี่ยวชาญโหราศาสตร์ไทยและสากล

จงทำนายดวง **ราศี{$zodiac->name_th}** ({$zodiac->name_en}) ประจำวันที่ **{$thaiDate}**

ข้อมูลราศี:
- ธาตุ: {$zodiac->element}
- ดาวประจำราศี: {$zodiac->ruling_planet}
- ช่วงวันเกิด: {$zodiac->date_range_start} - {$zodiac->date_range_end}

ให้ทำนาย 5 ด้าน พร้อมให้คะแนน 1-5 (1=แย่มาก, 5=ดีมาก) ในรูปแบบนี้:

[ภาพรวม] คะแนน: X/5
คำทำนายภาพรวมประจำวัน (2-3 ประโยค)

[ความรัก] คะแนน: X/5
คำทำนายด้านความรัก (2-3 ประโยค)

[การงาน] คะแนน: X/5
คำทำนายด้านการงาน (2-3 ประโยค)

[การเงิน] คะแนน: X/5
คำทำนายด้านการเงิน (2-3 ประโยค)

[สุขภาพ] คะแนน: X/5
คำทำนายด้านสุขภาพ (2-3 ประโยค)

หมายเหตุ:
- ใช้ภาษาไทยทั้งหมด สุภาพ อ่านง่าย
- ให้คำแนะนำเชิงบวกเสมอ
- อ้างอิงดาวเคราะห์ที่ส่งอิทธิพลในวันนี้
- แต่ละด้านต้องมีเนื้อหาแตกต่างกัน
PROMPT;
    }

    /**
     * สร้าง prompt สำหรับดวงวันเกิด
     *
     * @param  int  $birthDay  0-6
     * @param  Carbon  $date
     */
    protected function buildBirthDayPromptFromBrief(array $brief): string
    {
        $dayName = $brief['day_name'];
        $thaiDate = $brief['thai_date'];

        // 🪐 (2026-08-02) ถ้าคำนวณดาวไม่ได้ → ถอยไปใช้ข้อมูลคงที่แบบเดิม
        //    ห้ามปล่อยให้ job 6 โมงล้มทั้งวันเพราะ ephemeris มีปัญหา
        $factBlock = $brief['ok']
            ? $brief['text']
            : '(วันนี้ระบบดึงตำแหน่งดาวไม่ได้ — ให้ทำนายจากธรรมชาติของดาวเจ้าเรือนวันเกิดเท่านั้น '
                .'และ**ห้ามอ้างตำแหน่งดาวหรือมุมสัมพันธ์ใด ๆ**)';

        return <<<PROMPT
คุณเป็นโหรไทยที่อ่านดวงจากตำแหน่งดาวจริง ไม่ใช่คนเขียนคำคมให้กำลังใจ

ทำนายดวง **คนเกิดวัน{$dayName}** ประจำวันที่ **{$thaiDate}**

════════ ข้อเท็จจริงทางโหราศาสตร์ของวันนี้ (คำนวณจากตำแหน่งดาวจริง) ════════
{$factBlock}
═══════════════════════════════════════════════════════════════════

🚨 กฎเหล็ก 4 ข้อ (ผิดข้อใดข้อหนึ่ง = คำทำนายใช้ไม่ได้):

1. **ทำนายจากข้อเท็จจริงด้านบนเท่านั้น** — ทุกคำทำนายต้องสาวกลับไปหาดาว/ราศี/มุม
   ที่ระบุไว้ได้ ห้ามอ้างดาว ราศี มุมสัมพันธ์ หรือปรากฏการณ์ที่ไม่มีในรายการนั้น
   ถ้าข้อมูลไม่พอสำหรับด้านไหน ให้ทำนายด้านนั้นสั้นลง ห้ามแต่งเพิ่ม
   ⛔ **ห้ามอ้างไพ่ทาโรต์/ไพ่ยิปซี/การเปิดไพ่โดยเด็ดขาด** — งานนี้ไม่มีการจับไพ่
   ("ไพ่ที่จับได้คือ …" = แต่งขึ้นทั้งหมด) ห้ามอ้างเลขศาสตร์ ฤกษ์ยาม หรือของมงคลใด ๆ ด้วย

2. **ตัดน้ำออก เอาแต่เนื้อ** — ห้ามประโยคกลวงแบบ "วันนี้เป็นวันที่ดี ขอให้มีสติ"
   "พลังดวงดาวกำลังหมุนเวียน" "ทำดีย่อมได้ดี" ทุกประโยคต้องมีข้อมูลใหม่
   ห้ามขึ้นต้นด้วยการทักทายหรือเกริ่นนำ เข้าเนื้อทันที

3. **ฟันธง ไม่ hedge** — ห้าม "อาจจะ" "น่าจะ" "อาจมีโอกาส" บอกไปเลยว่าเกิดอะไร
   ควรทำอะไร ไม่ควรทำอะไร ให้เจาะจงจนลูกค้าเอาไปใช้ได้จริง
   ⏰ **การอ้างช่วงเวลาให้ทำในส่วน [ช่วงเวลา] เท่านั้น** และต้องตรงกับข้อเท็จจริง
   ห้ามเดาเวลาเองในส่วนอื่น (เช่น "หลังบ่ายสามจะดีขึ้น" ทั้งที่ข้อมูลไม่ได้บอก)

4. **ดึงจุดเด่นของคนเกิดวัน{$dayName}มาผูกกับดวงวันนี้** — ธรรมชาติของเจ้าชะตา
   (ตามที่ระบุในข้อเท็จจริง) เจอกับดาววันนี้แล้วออกมาเป็นอะไร นั่นคือแก่นของคำทำนาย
   ไม่ใช่ทำนายกลาง ๆ ที่ใครอ่านก็ได้

รูปแบบผลลัพธ์ (ห้ามผิดรูปแบบ ระบบ parse อัตโนมัติ):

[ภาพรวม] คะแนน: X/5
3-4 ประโยค — ระบุดาวที่เป็นเหตุ + ผลที่เจ้าชะตาจะเจอ + สิ่งที่ควรทำวันนี้

[ความรัก] คะแนน: X/5
2-3 ประโยค เจาะจง มีเหตุจากดาว

[การงาน] คะแนน: X/5
2-3 ประโยค เจาะจง มีเหตุจากดาว

[การเงิน] คะแนน: X/5
2-3 ประโยค เจาะจง มีเหตุจากดาว

[สุขภาพ] คะแนน: X/5
2-3 ประโยค เจาะจง มีเหตุจากดาว

[ช่วงเวลา]
เช้า (06:00-11:00): 1 ประโยค
เที่ยง (11:00-13:00): 1 ประโยค
บ่าย (13:00-17:00): 1 ประโยค
เย็น (17:00-20:00): 1 ประโยค
กลางคืน (20:00-06:00): 1 ประโยค

⚠️ ส่วน [ช่วงเวลา] ต้องอิง "ช่วงเวลาของวัน" ในข้อเท็จจริงเท่านั้น — ดูว่าช่วงนั้น
มุมไหนแน่น (คลาดน้อย = แรง) กำลังเข้าหรือกำลังคลาย แล้วบอกว่าควรทำ/เลี่ยงอะไร
ห้ามแต่งเวลาขึ้นเอง ห้ามบอกว่าช่วงไหนดีถ้าข้อมูลไม่ได้ชี้แบบนั้น
ครบทั้ง 5 ช่วง ห้ามข้าม · ช่วงละ 1 ประโยค ห้ามยาว

[จบ]

⛔ หลังบรรทัด [จบ] ห้ามเขียนอะไรต่ออีกแม้แต่บรรทัดเดียว — ห้ามสรุปปิดท้าย
ห้ามข้อคิด ("ดวงเป็นเครื่องชี้ทาง…") ห้ามชวนดูดวงต่อ ห้ามถามลูกค้า

เกณฑ์คะแนน: ดาวเจ้าเรือนเป็นอุจ/เกษตร + มุมส่งเสริม = 4-5 · เดินเรียบ = 3 ·
ดาวเจ้าเรือนพักร/นิจ หรือโดนจตุโกณ-เล็งจากดาวศัตรู = 1-2

เขียนภาษาไทยทั้งหมด · ห้ามใส่อีโมจิ · ห้ามใส่แฮชแท็ก · ห้ามใส่เลขนำโชค/สีมงคล
PROMPT;
    }

    // ==========================================
    // Parse AI Response
    // ==========================================

    /**
     * แยกคำทำนายจาก AI response
     *
     * @return array ['overall', 'love', 'career', 'finance', 'health', 'scores' => [...]]
     */
    protected function parseAIPrediction(string $response): array
    {
        $result = [
            'overall' => null,
            'love' => null,
            'career' => null,
            'finance' => null,
            'health' => null,
            'time' => null,
            'scores' => [
                'overall' => 3,
                'love' => 3,
                'career' => 3,
                'finance' => 3,
                'health' => 3,
            ],
        ];

        // แยกตาม section headers
        $sections = [
            'overall' => '/\[ภาพรวม\]\s*คะแนน:\s*(\d)\/5\s*\n(.+?)(?=\[|$)/s',
            'love' => '/\[ความรัก\]\s*คะแนน:\s*(\d)\/5\s*\n(.+?)(?=\[|$)/s',
            'career' => '/\[การงาน\]\s*คะแนน:\s*(\d)\/5\s*\n(.+?)(?=\[|$)/s',
            'finance' => '/\[การเงิน\]\s*คะแนน:\s*(\d)\/5\s*\n(.+?)(?=\[|$)/s',
            'health' => '/\[สุขภาพ\]\s*คะแนน:\s*(\d)\/5\s*\n(.+?)(?=\[|$)/s',
        ];

        foreach ($sections as $key => $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                $result['scores'][$key] = min(5, max(1, (int) $matches[1]));
                $result[$key] = $this->stripTrailingFiller(trim($matches[2]));
            }
        }

        // 🕐 (2026-08-02) ส่วนช่วงเวลา — ไม่มีคะแนน จึงใช้ pattern คนละแบบกับ 5 ด้านบน
        if (preg_match('/\[ช่วงเวลา\]\s*\n(.+?)(?=\[|$)/su', $response, $m)) {
            $result['time'] = $this->stripTrailingFiller(trim($m[1]));
        }

        // ถ้า parse ไม่ได้เลย ใช้ response ทั้งหมดเป็น overall
        if (! $result['overall']) {
            $result['overall'] = $this->stripTrailingFiller($response);
        }

        return $result;
    }

    /**
     * 🧹 (2026-08-02) ตัด "หางน้ำ" ที่โมเดลชอบต่อท้ายคำทำนาย
     *
     * เจ้าของสั่งให้ "ตัดน้ำออก เอาแต่เนื้อ" แต่ prompt อย่างเดียวกันไม่อยู่ —
     * เคสจริงรอบแรก โมเดลต่อท้ายด้านสุขภาพด้วย 2 ย่อหน้า:
     *   "ดวงเป็นเครื่องชี้ทาง แต่การคุมอารมณ์…กรรมดีเกิดจากสติ"  ← ข้อคิดกลวง
     *   "ถ้าต้องการ หมอจันทราทำนายต่อแบบเจาะลึก…"                ← ขายของในคำทำนาย
     *
     * ⚠️ หลุดมาที่ "ด้านสุดท้าย" เสมอ เพราะ regex ของแต่ละด้านจับถึง `[` ตัวถัดไป
     *    หรือจบสตริง — ด้านสุขภาพไม่มี `[` ตามหลังจึงกวาดทุกอย่างที่เหลือเข้ามา
     *    (prompt เพิ่ม `[จบ]` เป็นตัวปิดแล้ว แต่ถ้าโมเดลลืมใส่ ตัวนี้เป็นตาข่ายชั้นสอง)
     *
     * ตัดเฉพาะ "ย่อหน้าท้าย" ที่เข้าข่าย — ไม่แตะเนื้อคำทำนายกลางข้อความ
     */
    protected function stripTrailingFiller(string $text): string
    {
        // ตัดทุกอย่างหลังตัวปิด [จบ] (เผื่อโมเดลใส่มาแต่ regex ด้านบนไม่ได้กิน)
        $text = preg_replace('/\[\s*จบ\s*\].*$/su', '', $text) ?? $text;

        $fillerPatterns = [
            'ดวงเป็นเครื่องชี้ทาง', 'ดวงเป็นเพียง', 'เป็นเพียงแนวทาง',
            'ทำนายต่อ', 'ทักแชท', 'ทักมา', 'สนใจดูดวง', 'ดูดวงเชิงลึก', 'ปรึกษาแม่หมอ',
            'หากต้องการ', 'ถ้าต้องการ', 'หากสนใจ', 'ถ้าสนใจ',
            'ขอให้โชคดี', 'ขอให้มีความสุข', 'ด้วยความปรารถนาดี',
        ];

        $paras = preg_split('/\n\s*\n/u', trim($text)) ?: [];

        // ไล่ตัดจากท้ายมาหน้า — หยุดทันทีที่เจอย่อหน้าที่เป็นเนื้อจริง
        while (count($paras) > 1) {
            $last = trim((string) end($paras));
            $isFiller = false;

            foreach ($fillerPatterns as $needle) {
                if (mb_strpos($last, $needle) !== false) {
                    $isFiller = true;
                    break;
                }
            }

            if (! $isFiller) {
                break;
            }

            array_pop($paras);
        }

        return trim(implode("\n\n", $paras));
    }

    // ==========================================
    // สร้างข้อมูลมงคล
    // ==========================================

    /**
     * สร้างเลขมงคลสำหรับราศี
     *
     * @return string เลขมงคล เช่น "7, 14, 21"
     */
    protected function generateLuckyNumberForZodiac(HoroscopeZodiacSign $zodiac): string
    {
        // ใช้ sort_order เป็น base + day of year สำหรับ variety
        $base = $zodiac->sort_order;
        $dayFactor = now()->dayOfYear % 9 + 1;

        $numbers = [
            ($base * $dayFactor) % 100,
            ($base + $dayFactor * 7) % 100,
            rand(1, 99),
        ];

        return implode(', ', array_map(fn ($n) => str_pad($n, 2, '0', STR_PAD_LEFT), $numbers));
    }

    /**
     * 🔢 เลขนำโชค — ให้เฉพาะ "วันที่ 15 และ 29" เท่านั้น
     *
     * เจ้าของสั่ง (2026-08-02): "งดเลขนำโชค สีมงคล — เลขนำโชคจะให้ในวันที่ 29
     *   และวันที่ 15 เท่านั้น"
     *
     * ⚠️ ของเดิมใช้ `rand(1,99)` ปน = รีเจนวันเดิมได้เลขคนละชุด และไม่มีที่มาทางโหรเลย
     *    ตัวใหม่ derive จากของจริงล้วน จึงคงที่เสมอสำหรับวันเดียวกัน:
     *      - เลขดาวเจ้าเรือนวันเกิด (เลขศาสตร์ไทย: อาทิตย์ 1 … เสาร์ 7)
     *      - กำลังพระเคราะห์ของดาวเจ้าเรือน
     *      - ลำดับราศีที่ดาวเจ้าเรือนสถิตอยู่จริงวันนั้น
     *
     * @return string|null null = วันนี้ไม่ใช่วันแจกเลข
     */
    protected function luckyNumberForDate(int $birthDay, Carbon $date, array $brief): ?string
    {
        if (! in_array((int) $date->day, self::LUCKY_NUMBER_DAYS, true)) {
            return null;
        }

        $planetNum = $birthDay + 1;                       // อาทิตย์=1 … เสาร์=7
        $power = (int) ($brief['lord']['power'] ?? 9);    // กำลังพระเคราะห์
        $signIndex = array_search(
            $brief['lord']['sign'] ?? null,
            PlanetEphemeris::SIGNS,
            true
        );
        $signIndex = $signIndex === false ? 0 : (int) $signIndex + 1;

        $numbers = [
            $planetNum,
            ($power + $signIndex) % 100,
            ($planetNum * $signIndex + $power) % 100,
        ];

        // กันเลขซ้ำ/เลข 0 — เลื่อนขึ้นทีละหนึ่งจนได้ชุดที่อ่านแล้วไม่แปลก
        $numbers = array_map(fn ($n) => $n < 1 ? $n + 7 : $n, $numbers);
        $numbers = array_values(array_unique($numbers));

        return implode(', ', array_map(fn ($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT), $numbers));
    }

    /**
     * สร้างทิศมงคลจากดาวประจำ
     */
    protected function generateLuckyDirection(?string $rulingPlanet): string
    {
        // แปลงชื่อดาวไทยเป็น key
        $planetMap = [];
        foreach (FortuneChartService::PLANETS as $key => $data) {
            $planetMap[$data['name']] = $key;
        }

        $key = $planetMap[$rulingPlanet] ?? $rulingPlanet ?? 'sun';

        return self::LUCKY_DIRECTIONS[$key] ?? 'เหนือ';
    }

    // ==========================================
    // Cache Management
    // ==========================================

    /**
     * ล้าง cache ดวงราศี
     */
    protected function clearZodiacCache(string $slug, Carbon $date): void
    {
        Cache::forget("horoscope:zodiac:{$slug}:{$date->toDateString()}");
    }

    /**
     * ล้าง cache ดวงวันเกิด
     */
    protected function clearBirthDayCache(int $birthDay, Carbon $date): void
    {
        Cache::forget("horoscope:birthday:{$birthDay}:{$date->toDateString()}");
    }
}
