<?php

namespace App\Services;

use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortuneHoroscopeContent;
use App\Services\AiGen\AiGenProviderFactory;
use App\Services\Fortune\DailyAstroBrief;
use App\Services\Fortune\PlanetEphemeris;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * FortuneHoroscopeService
 *
 * ตัวหลักสำหรับสร้างเนื้อหาดวงรายวัน
 * 1. ดึงข้อมูลดาวจาก FortuneChartService
 * 2. สร้างคำทำนายจาก FortuneAIService
 * 3. สร้างรูปภาพจาก AiGen Provider (Pollinations ฟรี)
 */
class FortuneHoroscopeService
{
    /**
     * 🔢 วันที่ที่ "แจกเลขนำโชค" ได้เท่านั้น
     *
     * เจ้าของสั่ง (2026-08-02): "งดเลขนำโชค สีมงคล — เลขนำโชคจะให้ในวันที่ 29
     *   และวันที่ 15 เท่านั้น" · ตรงกับ HoroscopeDailyService::LUCKY_NUMBER_DAYS
     * (2026-09-03) เดิมกฎนี้ลงแค่เลนบทความเว็บ/DM เลนโพสเพจตกค้าง แจกทุกวัน
     */
    public const LUCKY_NUMBER_DAYS = [15, 29];

    protected FortuneChartService $chartService;

    protected FortuneAIService $aiService;

    public function __construct()
    {
        $this->chartService = new FortuneChartService;
        $this->aiService = new FortuneAIService;
    }

    /**
     * สร้างเนื้อหาดวงรายวันสำหรับทุกวันเกิดในแคมเปญ
     *
     * @param  FortuneHoroscopeCampaign  $campaign  แคมเปญที่จะสร้าง
     * @param  Carbon  $targetDate  วันที่สร้างเนื้อหา
     * @return array สรุปผล ['success' => int, 'failed' => int, 'contents' => Collection]
     */
    public function generateDailyContent(FortuneHoroscopeCampaign $campaign, Carbon $targetDate): array
    {
        $birthDays = $campaign->getTargetBirthDays();
        $success = 0;
        $failed = 0;
        $contents = collect();

        Log::info('FortuneHoroscope: เริ่มสร้างเนื้อหาดวงรายวัน', [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'target_date' => $targetDate->toDateString(),
            'birth_days' => $birthDays,
        ]);

        foreach ($birthDays as $birthDay) {
            try {
                $content = $this->generateForBirthDay($campaign, $targetDate, $birthDay);
                $contents->push($content);
                $success++;
            } catch (Exception $e) {
                $failed++;
                Log::error('FortuneHoroscope: สร้างเนื้อหาล้มเหลวสำหรับวัน '.FortuneHoroscopeCampaign::THAI_DAYS[$birthDay], [
                    'campaign_id' => $campaign->id,
                    'birth_day' => $birthDay,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // อัพเดทสถานะแคมเปญ
        // 🐛 (2026-09-01) เดิม stamp last_generated_at เสมอแม้ล้มยกชุด → scopeReadyToGenerate
        //   ตัดแคมเปญทิ้งตลอดวันที่เหลือ = วันนั้นไม่มีโพสแบบเงียบสนิท (ไม่มี retry/ยามเหมือน
        //   เลนบทความที่มี fortune:daily-preflight คุ้มกัน)
        //   ใหม่: ล้มยกชุด "ก่อนถึงเวลาโพส" → ไม่ stamp ให้ tick 5 นาทีถัดไปลองใหม่
        //   (bounded ~6 ครั้งในหน้าต่าง −30 นาที) · เลยเวลาโพสแล้วยังล้ม → stamp ยอมแพ้
        //   (กัน AI ยิงวนทั้งวันกับแคมเปญที่พังถาวร)
        if ($success > 0) {
            $campaign->update(['last_generated_at' => now()]);
        } else {
            $pastPublishTime = true;
            try {
                $pastPublishTime = now($campaign->timezone)->gte($campaign->getScheduleTimeCarbon());
            } catch (\Throwable $e) {
                // อ่านเวลาไม่ได้ → คงพฤติกรรมเดิม (stamp) กันลูป
            }

            if ($pastPublishTime) {
                $campaign->update(['last_generated_at' => now()]);
            } else {
                Log::warning('FortuneHoroscope: ล้มยกชุด — ไม่ stamp last_generated_at เปิดทางให้ retry รอบถัดไป', [
                    'campaign_id' => $campaign->id,
                ]);
            }
        }

        if ($failed > 0 && $success === 0) {
            $campaign->markError('สร้างเนื้อหาล้มเหลวทั้งหมด');
        }

        Log::info('FortuneHoroscope: สร้างเนื้อหาเสร็จ', [
            'campaign_id' => $campaign->id,
            'success' => $success,
            'failed' => $failed,
        ]);

        return [
            'success' => $success,
            'failed' => $failed,
            'contents' => $contents,
        ];
    }

    /**
     * สร้างเนื้อหาสำหรับ 1 วันเกิด
     *
     * @param  int  $birthDay  0-6
     */
    public function generateForBirthDay(
        FortuneHoroscopeCampaign $campaign,
        Carbon $targetDate,
        int $birthDay
    ): FortuneHoroscopeContent {
        $dayName = FortuneHoroscopeCampaign::THAI_DAYS[$birthDay];

        // ดึงหรือสร้าง content record
        $content = FortuneHoroscopeContent::updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'target_date' => $targetDate->toDateString(),
                'birth_day' => $birthDay,
            ],
            [
                'birth_day_name' => $dayName,
                'status' => FortuneHoroscopeContent::STATUS_GENERATING,
                'error_message' => null,
            ]
        );

        try {
            // ขั้นที่ 1: ดึงข้อมูลดาวศาสตร์ ณ วันที่ทำนาย (ต้องส่ง $targetDate ไม่งั้นดาวคงที่ทุกวัน)
            $astrologyData = $this->getAstrologyData($birthDay, $targetDate);
            $content->update([
                'main_planet' => $astrologyData['main_planet'],
                'planet_positions' => $astrologyData['planet_positions'],
                'chaochana_data' => $astrologyData['chaochana'],
                // 🎨 (2026-09-03) สีมงคล = null ถาวร · เลขนำโชค = เฉพาะวันที่ 15/29
                //    ตามคำสั่งเจ้าของ 2026-08-02 ที่เดิมลงแค่เลนบทความเว็บ/DM
                'lucky_color' => null,
                'lucky_number' => $this->luckyNumberForDate($birthDay, $targetDate, $astrologyData['astro_brief']),
                'lucky_direction' => $this->generateLuckyDirection($birthDay),
            ]);

            // ขั้นที่ 2: สร้างคำทำนายด้วย AI
            $prediction = $this->generatePrediction($campaign, $targetDate, $birthDay, $astrologyData);
            $content->update([
                'ai_prediction' => $prediction['response'],
                'ai_prompt_used' => $prediction['prompt'],
                'ai_text_provider_used' => $prediction['provider'] ?? null,
                'ai_text_model_used' => $prediction['model'] ?? null,
            ]);

            // ขั้นที่ 3: สร้างรูปภาพ (ถ้าเปิดใช้)
            if ($campaign->include_image) {
                $imageResult = $this->generateImage($campaign, $birthDay, $astrologyData);
                if ($imageResult) {
                    $content->update([
                        'image_url' => $imageResult['url'] ?? null,
                        'image_path' => $imageResult['path'] ?? null,
                        'image_prompt_used' => $imageResult['prompt'] ?? null,
                        'ai_image_provider_used' => $campaign->ai_image_provider,
                    ]);
                }
            }

            $content->markGenerated();

            Log::info("FortuneHoroscope: สร้างเนื้อหาสำเร็จ วัน{$dayName}", [
                'content_id' => $content->id,
                'has_image' => ! empty($content->image_url),
            ]);

            return $content;

        } catch (Exception $e) {
            $content->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * ดึงข้อมูลโหราศาสตร์สำหรับวันเกิด ณ "วันที่ทำนาย" จริง ๆ
     *
     * 🪐 (2026-09-03) เดิมรับแค่ $birthDay แล้วเรียก `calculatePlanetPositions($birthDay)`
     *    ซึ่งเป็น **ผังภพคงที่ตามวันเกิด ไม่มีวันที่เข้ามาเกี่ยวเลย** → ค่าที่ยัดเข้า prompt
     *    ช่อง "ตำแหน่งดาว" เหมือนกันเป๊ะทุกวันตั้งแต่ ก.พ. 2569 (ตรวจจาก prod: 103 วัน
     *    แต่ distinct planet_positions = 1 ต่อวันเกิด) ทั้งที่ prompt สั่ง "อ้างอิงตำแหน่ง
     *    ดาวจริง" ⇒ AI ไม่มีข้อเท็จจริงรายวันให้ยึด จึงแต่งเอง = มโน
     *
     *    บทเรียนเดียวกับที่แก้ไปแล้วในเลนบทความเว็บ/DM เมื่อ 2026-08-02 —
     *    ระบบมี [[PlanetEphemeris]] คำนวณดาวจริงอยู่แล้ว แค่ไม่เคยถูกเรียกในเลนนี้
     *
     * @param  int  $birthDay  0=อาทิตย์ … 6=เสาร์ (ตรงกับ FortuneChartService::CHAOCHANA)
     * @param  Carbon|null  $targetDate  วันที่ทำนาย (null = วันนี้)
     */
    protected function getAstrologyData(int $birthDay, ?Carbon $targetDate = null): array
    {
        $targetDate = $targetDate ?? Carbon::now('Asia/Bangkok');

        $chaochana = FortuneChartService::CHAOCHANA[$birthDay];
        $mainPlanetKey = $chaochana['planet'];
        $mainPlanet = FortuneChartService::PLANETS[$mainPlanetKey];

        // ผังภพตามตำราเจ้าชนะ — คงที่ตามวันเกิด (ใช้โชว์ผังในแอดมิน + prompt รูปภาพ)
        $planetPositions = $this->chartService->calculatePlanetPositions($birthDay);

        // ข้อเท็จจริงของ "วันนั้นจริง ๆ" — ตำแหน่งดาวจริง 9 ดวง + ศักดิ์ + พักร + มุมสัมพันธ์
        // fail-open: คำนวณไม่ได้จะได้ ok=false แล้ว prompt จะสั่งห้าม AI อ้างตำแหน่งดาว
        $astroBrief = (new DailyAstroBrief)->build($birthDay, $targetDate);

        // แปลงชื่อดาวมิตร/ศัตรูเป็นภาษาไทย
        $friendNames = array_map(
            fn ($key) => FortuneChartService::PLANETS[$key]['name'] ?? $key,
            $chaochana['friends']
        );
        $enemyNames = array_map(
            fn ($key) => FortuneChartService::PLANETS[$key]['name'] ?? $key,
            $chaochana['enemies']
        );

        return [
            'main_planet' => $mainPlanet['name'],
            'main_planet_key' => $mainPlanetKey,
            'main_planet_color' => $mainPlanet['color'],
            'planet_positions' => $planetPositions,
            'chaochana' => $chaochana,
            'friend_planets' => implode(', ', $friendNames),
            'enemy_planets' => implode(', ', $enemyNames),
            'astro_brief' => $astroBrief,
        ];
    }

    /**
     * สร้างคำทำนายด้วย AI
     */
    protected function generatePrediction(
        FortuneHoroscopeCampaign $campaign,
        Carbon $targetDate,
        int $birthDay,
        array $astrologyData
    ): array {
        $dayName = FortuneHoroscopeCampaign::THAI_DAYS[$birthDay];
        $prompt = $this->buildTextPrompt($campaign, $targetDate, $birthDay, $astrologyData);

        // ใช้ FortuneAIService::generateWithRetryAndFallback
        $result = $this->aiService->generateWithRetryAndFallback(
            questions: ["ทำนายดวงวันนี้สำหรับคนเกิดวัน{$dayName}"],
            userProfile: ['name' => "คนเกิดวัน{$dayName}"],
            promptTemplate: $prompt,
            readingType: 'basic',
        );

        return [
            'response' => $result['response'] ?? '',
            'prompt' => $prompt,
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? null,
        ];
    }

    /**
     * สร้าง text prompt จาก template
     */
    protected function buildTextPrompt(
        FortuneHoroscopeCampaign $campaign,
        Carbon $targetDate,
        int $birthDay,
        array $astrologyData
    ): string {
        $dayName = FortuneHoroscopeCampaign::THAI_DAYS[$birthDay];
        $template = $campaign->text_prompt_template;

        // ถ้าไม่มี template ใช้ default
        if (empty($template)) {
            $template = $this->getDefaultTextPrompt();
        }

        // ผังภพตามวันเกิด (คงที่) — เก็บไว้เป็น placeholder แยกสำหรับ template ที่อยากใช้
        $natalHouses = $this->formatPlanetPositions($astrologyData['planet_positions']);

        // 🪐 ข้อเท็จจริงรายวันจริง ๆ — ตัวนี้คือสิ่งที่ทำให้ "วันนี้" ต่างจาก "เมื่อวาน"
        $brief = $astrologyData['astro_brief'] ?? ['ok' => false, 'text' => ''];
        $briefOk = (bool) ($brief['ok'] ?? false);

        // ⚠️ {planet_positions} ตัวเดิมชี้ไปที่ผังภพคงที่ = ค่าเดิมทุกวัน
        //    template ที่แอดมินบันทึกไว้ใน DB ใช้ชื่อนี้อยู่ จึงชี้มันมาที่ข้อเท็จจริงจริง
        //    (ไม่ต้องรอแอดมินแก้ template เอง — ดู [[rule_db_prompt_overrides_code]])
        $positionsText = $briefOk
            ? "\n".$brief['text']
            : "\n".'(วันนี้ระบบคำนวณตำแหน่งดาวจริงไม่สำเร็จ) ด้านล่างคือ "ผังภพตามวันเกิด" '
                .'ตามตำราเจ้าชนะ ซึ่งคงที่ทุกวัน ไม่ใช่ตำแหน่งดาวของวันนี้ '
                .'ห้ามนำไปอ้างเป็นราศีหรือมุมสัมพันธ์ประจำวันเด็ดขาด: '.$natalHouses;

        // วันที่แบบไทย
        $thaiDate = $targetDate->format('d/m/').($targetDate->year + 543);

        // แทนที่ placeholders
        $replacements = [
            '{target_date}' => $thaiDate,
            '{birth_day_name}' => $dayName,
            '{birth_day}' => (string) $birthDay,
            '{main_planet}' => $astrologyData['main_planet'],
            '{element}' => $astrologyData['chaochana']['element'] ?? '',
            '{lucky_color}' => $astrologyData['chaochana']['lucky_color'] ?? '',
            '{friend_planets}' => $astrologyData['friend_planets'],
            '{enemy_planets}' => $astrologyData['enemy_planets'],
            '{planet_positions}' => $positionsText,
            '{astro_brief}' => $positionsText,
            '{natal_houses}' => $natalHouses,
        ];

        $prompt = str_replace(array_keys($replacements), array_values($replacements), $template);

        return $prompt."\n\n".$this->antiHallucinationRules($dayName, $briefOk);
    }

    /**
     * 🚨 กฎเหล็กท้าย prompt — บังคับให้ทำนายจากข้อเท็จจริงชุดที่ให้ไปเท่านั้น
     *
     * ผนวกท้ายเสมอไม่ว่าแอดมินจะเขียน template ยังไง เพราะ template ใน DB แก้ได้อิสระ
     * ถ้าปล่อยให้กฎอยู่ใน template อย่างเดียว วันหนึ่งมีคนแก้ทับ = กลับไปมโนเงียบ ๆ
     *
     * @param  bool  $briefOk  false = คำนวณดาวไม่สำเร็จ ต้องห้ามอ้างดาวทั้งหมด
     */
    protected function antiHallucinationRules(string $dayName, bool $briefOk): string
    {
        // 📌 (2026-09-03) ย้ายเนื้อกฎไปไว้ที่ DailyAstroBrief::promptRules() แหล่งเดียว
        //    เพราะทั้ง 3 เลนของดวงรายวันต้องใช้กฎชุดเดียวกัน — เขียนแยกกันแล้วหลุด
        return DailyAstroBrief::promptRules($dayName, $briefOk);
    }

    /**
     * สร้างรูปภาพด้วย AI
     */
    protected function generateImage(
        FortuneHoroscopeCampaign $campaign,
        int $birthDay,
        array $astrologyData
    ): ?array {
        try {
            $dayName = FortuneHoroscopeCampaign::THAI_DAYS[$birthDay];
            $imagePrompt = $this->buildImagePrompt($campaign, $birthDay, $astrologyData);

            // สร้าง provider instance
            $provider = AiGenProviderFactory::create($campaign->ai_image_provider);

            $result = $provider->generateImage($imagePrompt, [
                'model' => $campaign->ai_image_model,
                'size' => $campaign->image_size,
                'style' => $campaign->image_style,
                'num_images' => 1,
            ]);

            if (! ($result['success'] ?? false) || empty($result['images'])) {
                Log::warning("FortuneHoroscope: สร้างรูปล้มเหลวสำหรับวัน{$dayName}", [
                    'error' => $result['error'] ?? 'ไม่ทราบสาเหตุ',
                ]);

                return null;
            }

            return [
                'url' => $result['images'][0]['url'] ?? null,
                'path' => null,
                'prompt' => $imagePrompt,
            ];

        } catch (Exception $e) {
            Log::warning('FortuneHoroscope: สร้างรูปภาพ exception', [
                'birth_day' => $birthDay,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * สร้าง image prompt จาก template
     */
    protected function buildImagePrompt(
        FortuneHoroscopeCampaign $campaign,
        int $birthDay,
        array $astrologyData
    ): string {
        $dayName = FortuneHoroscopeCampaign::THAI_DAYS[$birthDay];
        $template = $campaign->image_prompt_template;

        if (empty($template)) {
            $template = $this->getDefaultImagePrompt();
        }

        $replacements = [
            '{birth_day_name}' => $dayName,
            '{main_planet}' => $astrologyData['main_planet'],
            '{element}' => $astrologyData['chaochana']['element'] ?? 'fire',
            '{lucky_color}' => $astrologyData['chaochana']['lucky_color'] ?? 'gold',
            '{planet_color}' => $astrologyData['main_planet_color'] ?? '#FFD700',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * แปลง planet_positions เป็นข้อความอ่านง่าย
     */
    protected function formatPlanetPositions(array $positions): string
    {
        $lines = [];
        foreach ($positions as $house => $planets) {
            if (empty($planets)) {
                continue;
            }
            $houseName = FortuneChartService::HOUSES[$house]['name'] ?? "ภพ {$house}";
            $houseMeaning = FortuneChartService::HOUSES[$house]['meaning'] ?? '';
            $planetNames = array_map(
                fn ($key) => FortuneChartService::PLANETS[$key]['name'] ?? $key,
                $planets
            );
            $lines[] = "ภพ{$houseName}({$houseMeaning}): ".implode(', ', $planetNames);
        }

        return implode(' | ', $lines);
    }

    /**
     * 🔢 เลขนำโชค — ให้เฉพาะ "วันที่ 15 และ 29" เท่านั้น
     *
     * ⚠️ (2026-09-03) ของเดิม `generateLuckyNumber($birthDay)` แจกทุกวัน และเลขตัวสุดท้าย
     *    มาจาก `(now()->dayOfYear + $birthDay) % 36 + 1` ซึ่ง (ก) ไม่มีที่มาทางโหรเลย
     *    (ข) ใช้ `now()` ไม่ใช่วันที่ทำนาย ⇒ รีเจนคนละวันได้เลขคนละชุดของวันเดียวกัน
     *
     *    ตัวใหม่ลอกสูตรจาก `HoroscopeDailyService::luckyNumberForDate()` ให้สองระบบ
     *    พูดตรงกัน — derive จากของจริงล้วน จึงคงที่เสมอสำหรับวันเดียวกัน:
     *      - เลขดาวเจ้าเรือนวันเกิด (เลขศาสตร์ไทย: อาทิตย์ 1 … เสาร์ 7)
     *      - กำลังพระเคราะห์ของดาวเจ้าเรือน
     *      - ลำดับราศีที่ดาวเจ้าเรือนสถิตอยู่ **จริง** ในวันนั้น (จาก ephemeris)
     *
     * @param  array  $brief  ผลจาก DailyAstroBrief::build()
     * @return string|null null = วันนี้ไม่ใช่วันแจกเลข (คอลัมน์ว่าง = โพสไม่พิมพ์บรรทัดมงคล)
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

        // กันเลข 0 และเลขซ้ำ
        $numbers = array_map(fn ($n) => $n < 1 ? $n + 7 : $n, $numbers);
        $numbers = array_values(array_unique($numbers));

        return implode(', ', array_map(fn ($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT), $numbers));
    }

    /**
     * สร้างทิศมงคลจากวันเกิด
     */
    protected function generateLuckyDirection(int $birthDay): string
    {
        $directions = [
            0 => 'ตะวันออกเฉียงเหนือ',  // อาทิตย์
            1 => 'ตะวันตกเฉียงเหนือ',     // จันทร์
            2 => 'ใต้',                      // อังคาร
            3 => 'เหนือ',                   // พุธ
            4 => 'ตะวันออกเฉียงเหนือ',    // พฤหัสบดี
            5 => 'ตะวันออกเฉียงใต้',       // ศุกร์
            6 => 'ตะวันตก',                 // เสาร์
        ];

        return $directions[$birthDay] ?? 'เหนือ';
    }

    /**
     * Default text prompt template
     */
    protected function getDefaultTextPrompt(): string
    {
        return 'คุณเป็นหมอดูชื่อดังเชี่ยวชาญโหราศาสตร์ไทย (หลักเจ้าชนะ) ที่เก่งเรื่องการเขียนคอนเทนต์โซเชียลมีเดีย

วันนี้วันที่: {target_date}
คนเกิดวัน: {birth_day_name}
ดาวเจ้าชนะ: {main_planet}
ธาตุ: {element}
สีมงคล: {lucky_color}
ดาวมิตร: {friend_planets}
ดาวศัตรู: {enemy_planets}
ตำแหน่งดาว: {planet_positions}

ทำนายดวงวันนี้สำหรับคนเกิดวัน{birth_day_name} ในสไตล์ที่:
✅ ฟันธง ชัดเจน อ้างอิงตำแหน่งดาวจริง
✅ ใช้ภาษาโดนใจ ให้คนอ่านอยากแชร์/คอมเมนต์
✅ เน้นเรื่องที่คนสนใจ (เงิน, ความรัก, โชคลาภ)

รูปแบบ:
1. 🔥 ภาพรวมวันนี้ (2-3 ประโยค ขึ้นต้นด้วยสิ่งที่น่าสนใจที่สุด เช่น "วันนี้ดาว{main_planet}ส่งพลัง...")
2. 💰 การเงิน (1-2 ประโยค - ระบุช่วงเวลาที่โชคดี)
3. 💕 ความรัก (1-2 ประโยค - คนโสด/คนมีคู่)
4. 💼 การงาน (1-2 ประโยค)
5. 🏥 สุขภาพ (1 ประโยค)

กฎ:
- ภาษาไทย 100%
- ใส่ emoji ให้น่าอ่าน
- ฟันธงชัดเจน ไม่กั๊ก
- อ้างอิงดาวเจ้าชนะ/ดาวมิตร/ดาวศัตรู
- ไม่เกิน 200 คำ
- เขียนให้คนอ่านอยากแท็กเพื่อนที่เกิดวันเดียวกัน';
    }

    /**
     * Default image prompt template
     */
    protected function getDefaultImagePrompt(): string
    {
        return 'Thai astrology zodiac card, {birth_day_name} born, {element} element theme, mystical {lucky_color} color scheme, celestial background with stars, ornate Thai art style, golden decorations, high quality digital art';
    }
}
