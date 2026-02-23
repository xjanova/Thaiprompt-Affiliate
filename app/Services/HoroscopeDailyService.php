<?php

namespace App\Services;

use App\Models\HoroscopeDailyPrediction;
use App\Models\HoroscopeZodiacSign;
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
    /**
     * @var FortuneAIService
     */
    protected FortuneAIService $aiService;

    /**
     * @var FortuneChartService
     */
    protected FortuneChartService $chartService;

    /**
     * Cache TTL สำหรับดวงรายวัน (24 ชั่วโมง)
     */
    protected const CACHE_TTL = 86400;

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

    /**
     * สีมงคลตามธาตุ
     */
    protected const ELEMENT_COLORS = [
        'ไฟ' => ['แดง', 'ส้ม', 'ชมพู', 'ทอง'],
        'ดิน' => ['เหลือง', 'น้ำตาล', 'ครีม', 'ทอง'],
        'ลม' => ['เขียว', 'ฟ้า', 'เขียวอ่อน', 'ขาว'],
        'น้ำ' => ['ฟ้า', 'น้ำเงิน', 'ม่วง', 'เงิน'],
    ];

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
     * @param Carbon $date วันที่เป้าหมาย
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
     * @param Carbon $date วันที่เป้าหมาย
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
     *
     * @param HoroscopeZodiacSign $zodiac
     * @param Carbon $date
     * @return HoroscopeDailyPrediction
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

            // สร้างเลขมงคลและข้อมูลเสริม
            $luckyNumber = $this->generateLuckyNumberForZodiac($zodiac);
            $luckyColor = $this->generateLuckyColorForZodiac($zodiac);
            $luckyDirection = $this->generateLuckyDirection($zodiac->ruling_planet);

            // อัปเดต prediction
            $prediction->update([
                'overall_prediction_th' => $parsed['overall'] ?? $result['response'],
                'love_prediction_th' => $parsed['love'] ?? null,
                'career_prediction_th' => $parsed['career'] ?? null,
                'finance_prediction_th' => $parsed['finance'] ?? null,
                'health_prediction_th' => $parsed['health'] ?? null,
                'overall_score' => $parsed['scores']['overall'] ?? rand(2, 5),
                'love_score' => $parsed['scores']['love'] ?? rand(2, 5),
                'career_score' => $parsed['scores']['career'] ?? rand(2, 5),
                'finance_score' => $parsed['scores']['finance'] ?? rand(2, 5),
                'health_score' => $parsed['scores']['health'] ?? rand(2, 5),
                'lucky_number' => $luckyNumber,
                'lucky_color_th' => $luckyColor,
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
     * @param int $birthDay 0-6 (อาทิตย์-เสาร์)
     * @param Carbon $date
     * @return HoroscopeDailyPrediction
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
            $prompt = $this->buildBirthDayPrompt($birthDay, $date);

            $result = $this->aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                promptTemplate: null,
                readingType: 'basic'
            );

            $parsed = $this->parseAIPrediction($result['response'] ?? '');

            // ดึง astrology data จาก FortuneChartService
            $chaochana = FortuneChartService::CHAOCHANA[$birthDay] ?? [];
            $planetKey = $chaochana['planet'] ?? 'sun';
            $planet = FortuneChartService::PLANETS[$planetKey] ?? [];

            $luckyNumber = $this->generateLuckyNumberForBirthDay($birthDay);
            $luckyColor = $chaochana['lucky_color'] ?? 'ขาว';
            $luckyDirection = self::LUCKY_DIRECTIONS[$planetKey] ?? 'เหนือ';

            $prediction->update([
                'overall_prediction_th' => $parsed['overall'] ?? $result['response'],
                'love_prediction_th' => $parsed['love'] ?? null,
                'career_prediction_th' => $parsed['career'] ?? null,
                'finance_prediction_th' => $parsed['finance'] ?? null,
                'health_prediction_th' => $parsed['health'] ?? null,
                'overall_score' => $parsed['scores']['overall'] ?? rand(2, 5),
                'love_score' => $parsed['scores']['love'] ?? rand(2, 5),
                'career_score' => $parsed['scores']['career'] ?? rand(2, 5),
                'finance_score' => $parsed['scores']['finance'] ?? rand(2, 5),
                'health_score' => $parsed['scores']['health'] ?? rand(2, 5),
                'lucky_number' => $luckyNumber,
                'lucky_color_th' => $luckyColor,
                'lucky_direction_th' => $luckyDirection,
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
     *
     * @param string $zodiacSlug
     * @param Carbon|null $date
     * @return HoroscopeDailyPrediction|null
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
     * @param int $birthDay 0-6
     * @param Carbon|null $date
     * @return HoroscopeDailyPrediction|null
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
     * @param int $zodiacSignId
     * @param int $days จำนวนวันย้อนหลัง
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
     * @param int $birthDay
     * @param int $days
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
     *
     * @param HoroscopeZodiacSign $zodiac
     * @param Carbon $date
     * @return string
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
     * @param int $birthDay 0-6
     * @param Carbon $date
     * @return string
     */
    protected function buildBirthDayPrompt(int $birthDay, Carbon $date): string
    {
        $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        $dayName = $dayNames[$birthDay] ?? 'อาทิตย์';

        $chaochana = FortuneChartService::CHAOCHANA[$birthDay] ?? [];
        $planetKey = $chaochana['planet'] ?? 'sun';
        $planet = FortuneChartService::PLANETS[$planetKey] ?? [];
        $planetName = $planet['name'] ?? 'อาทิตย์';
        $element = $chaochana['element'] ?? 'ไฟ';
        $luckyColor = $chaochana['lucky_color'] ?? 'แดง';

        // ดาวมิตรและศัตรู
        $friendNames = collect($chaochana['friends'] ?? [])
            ->map(fn ($p) => FortuneChartService::PLANETS[$p]['name'] ?? $p)
            ->implode(', ');
        $enemyNames = collect($chaochana['enemies'] ?? [])
            ->map(fn ($p) => FortuneChartService::PLANETS[$p]['name'] ?? $p)
            ->implode(', ');

        $thaiDate = $date->locale('th')->translatedFormat('l j F').' '.($date->year + 543);

        return <<<PROMPT
คุณเป็นนักโหราศาสตร์ไทยระดับสูง เชี่ยวชาญระบบเชาจันทร์

จงทำนายดวง **คนเกิดวัน{$dayName}** ประจำวันที่ **{$thaiDate}**

ข้อมูลโหราศาสตร์ไทย:
- ดาวประจำวัน: {$planetName}
- ธาตุ: {$element}
- สีมงคล: {$luckyColor}
- ดาวมิตร: {$friendNames}
- ดาวศัตรู: {$enemyNames}

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
- อ้างอิงอิทธิพลของดาว{$planetName}ที่ส่งผลในวันนี้
- คำนึงถึงดาวมิตร/ศัตรูที่ส่งอิทธิพล
- ให้คำแนะนำเชิงบวกเสมอ
PROMPT;
    }

    // ==========================================
    // Parse AI Response
    // ==========================================

    /**
     * แยกคำทำนายจาก AI response
     *
     * @param string $response
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
                $result[$key] = trim($matches[2]);
            }
        }

        // ถ้า parse ไม่ได้เลย ใช้ response ทั้งหมดเป็น overall
        if (! $result['overall']) {
            $result['overall'] = $response;
        }

        return $result;
    }

    // ==========================================
    // สร้างข้อมูลมงคล
    // ==========================================

    /**
     * สร้างเลขมงคลสำหรับราศี
     *
     * @param HoroscopeZodiacSign $zodiac
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
     * สร้างเลขมงคลสำหรับวันเกิด
     *
     * @param int $birthDay
     * @return string
     */
    protected function generateLuckyNumberForBirthDay(int $birthDay): string
    {
        $dayFactor = now()->dayOfYear;

        $numbers = [
            (($birthDay + 1) * ($dayFactor % 13 + 1)) % 100,
            (($birthDay + 3) * ($dayFactor % 7 + 1)) % 100,
            rand(1, 99),
        ];

        return implode(', ', array_map(fn ($n) => str_pad($n, 2, '0', STR_PAD_LEFT), $numbers));
    }

    /**
     * สร้างสีมงคลสำหรับราศี
     *
     * @param HoroscopeZodiacSign $zodiac
     * @return string
     */
    protected function generateLuckyColorForZodiac(HoroscopeZodiacSign $zodiac): string
    {
        $element = $zodiac->element ?? 'ไฟ';
        $colors = self::ELEMENT_COLORS[$element] ?? self::ELEMENT_COLORS['ไฟ'];

        return $colors[array_rand($colors)];
    }

    /**
     * สร้างทิศมงคลจากดาวประจำ
     *
     * @param string|null $rulingPlanet
     * @return string
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
