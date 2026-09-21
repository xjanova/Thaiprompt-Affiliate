<?php

namespace App\Services;

use App\Models\HoroscopeNumerologyReading;
use App\Services\Fortune\ThaiNumerology;
use Illuminate\Support\Facades\Log;

/**
 * HoroscopeNumerologyService — วิเคราะห์เลขศาสตร์
 *
 * รับผิดชอบ:
 * - วิเคราะห์ชื่อ (พยัญชนะไทย → ตัวเลข)
 * - วิเคราะห์เบอร์โทร
 * - วิเคราะห์ทะเบียนรถ
 * - วิเคราะห์เลขบัตรประชาชน
 * - วิเคราะห์วันเกิด
 * - เรียก AI สำหรับคำทำนายเชิงลึก
 */
class HoroscopeNumerologyService
{
    protected FortuneAIService $aiService;

    /**
     * ความหมายของเลขศาสตร์ 1-9 — เลขดาวแบบไทย
     *
     * 🇹🇭 (2026-09-21 เจ้าของสั่ง "ยึดตำราโหราจาก genlotto เป็นหลักทั้งหมด")
     *   เดิมผูกเลขกับดาวแบบฝรั่ง (3=พฤหัส 4=ราหู 5=พุธ 7=เกตุ 8=เสาร์ 9=อังคาร) ขัดกับเลขดาวไทยทั้งระบบ
     *   ⇒ ตอนนี้ใช้เลขดาวของ GenLotto (1 อาทิตย์ … 8 ราหู 9 เกตุ) · ธาตุ/สีหลักจาก GenLotto `ThaiTables::CHAOCHANA`
     *   · ความหมาย = จุดเด่นรายดาวใน config `thai_astrology_knowledge.planet_meta.*.trait`
     *   · เกตุไม่มีธาตุ/สีในตำรา GenLotto → เว้นว่าง (ห้ามเดาใส่)
     *   ตารางค่าอักษรย้ายไป App\Services\Fortune\ThaiNumerology (ตาราง GenLotto นับสระ/วรรณยุกต์ด้วย)
     */
    protected const NUMBER_MEANINGS = [
        1 => ['name' => 'อาทิตย์', 'meaning' => 'อำนาจ ผู้นำ ศักดิ์ศรี', 'color' => 'แดง', 'element' => 'ไฟ'],
        2 => ['name' => 'จันทร์', 'meaning' => 'อ่อนโยน เมตตา อารมณ์', 'color' => 'เหลือง', 'element' => 'น้ำ'],
        3 => ['name' => 'อังคาร', 'meaning' => 'กล้าหาญ ร้อนแรง ทะเยอทะยาน', 'color' => 'ชมพู', 'element' => 'ไฟ'],
        4 => ['name' => 'พุธ', 'meaning' => 'ฉลาด พูดเก่ง ค้าขาย', 'color' => 'เขียว', 'element' => 'ดิน'],
        5 => ['name' => 'พฤหัสบดี', 'meaning' => 'ปัญญา ใจกว้าง โชคดี คุณธรรม', 'color' => 'ส้ม', 'element' => 'ลม'],
        6 => ['name' => 'ศุกร์', 'meaning' => 'รักสวยงาม เสน่ห์ ศิลปะ โรแมนติก', 'color' => 'ฟ้า', 'element' => 'น้ำ'],
        7 => ['name' => 'เสาร์', 'meaning' => 'อดทน มีวินัย จริงจัง หนักแน่น', 'color' => 'ม่วง', 'element' => 'ดิน'],
        8 => ['name' => 'ราหู', 'meaning' => 'ลึกลับ พลิกผัน เสน่ห์มืด การต่างแดน', 'color' => 'เทา', 'element' => 'ลม'],
        9 => ['name' => 'เกตุ', 'meaning' => 'ปัญญา จิตวิญญาณ ลึกลับ', 'color' => '', 'element' => ''],
    ];

    /**
     * Constructor
     */
    public function __construct(FortuneAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    // ==========================================
    // วิเคราะห์เลขศาสตร์
    // ==========================================

    /**
     * วิเคราะห์ชื่อ (พยัญชนะไทย → เลข → ผลรวม)
     *
     * @param  string  $name  ชื่อภาษาไทย
     * @param  string|null  $question  คำถามเพิ่มเติม
     */
    public function analyzeName(string $name, ?string $question = null): HoroscopeNumerologyReading
    {
        // 🇹🇭 (2026-09-21) ตารางเลขศาสตร์ GenLotto — นับพยัญชนะ + สระ + วรรณยุกต์ + ผลรวมดี/ไม่ดีตามตาราง
        $calc = ThaiNumerology::name($name);
        $digits = $calc['digits'];
        $rootNumber = $calc['root'];
        $numberMeaning = self::NUMBER_MEANINGS[$rootNumber] ?? self::NUMBER_MEANINGS[1];

        // สร้าง reading
        $reading = HoroscopeNumerologyReading::create([
            'user_id' => auth()->id(),
            'reading_type' => HoroscopeNumerologyReading::TYPE_NAME,
            'input_value' => $name,
            'numerology_number' => $rootNumber,
            'digit_breakdown' => json_encode([
                'name' => $name,
                'digits' => $digits,
                'letters' => $calc['letters'],
                'sum' => $calc['sum'],
                'sum_tone' => $calc['tone'],
                'root' => $rootNumber,
            ]),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'status' => 'generating',
        ]);

        // เรียก AI วิเคราะห์ — ส่งผลรวมกับเกณฑ์ตามตารางไปด้วย (ห้ามให้ AI ตัดสินดี/ร้ายเอง)
        $this->generateAIAnalysis(
            $reading, $name, $rootNumber, $numberMeaning, 'name', $question,
            "ผลรวมชื่อ: {$calc['sum']} = {$calc['tone']} (ตามตารางผลรวมเลขศาสตร์ — ใช้ผลนี้ ห้ามตัดสินใหม่เอง)"
        );

        return $reading->fresh();
    }

    /**
     * วิเคราะห์เบอร์โทรศัพท์
     *
     * @param  string  $phone  เบอร์โทร
     */
    public function analyzePhone(string $phone, ?string $question = null): HoroscopeNumerologyReading
    {
        // ลบอักขระที่ไม่ใช่ตัวเลข
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $digits = array_map('intval', str_split($cleanPhone));
        $rootNumber = $this->reduceToSingleDigit(array_sum($digits));
        $numberMeaning = self::NUMBER_MEANINGS[$rootNumber] ?? self::NUMBER_MEANINGS[1];

        // วิเคราะห์ตัวเลขเด่น
        $digitFrequency = array_count_values($digits);
        arsort($digitFrequency);

        $reading = HoroscopeNumerologyReading::create([
            'user_id' => auth()->id(),
            'reading_type' => HoroscopeNumerologyReading::TYPE_PHONE,
            'input_value' => $phone,
            'numerology_number' => $rootNumber,
            'digit_breakdown' => json_encode([
                'phone' => $cleanPhone,
                'digits' => $digits,
                'sum' => array_sum($digits),
                'root' => $rootNumber,
                'frequency' => $digitFrequency,
            ]),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'status' => 'generating',
        ]);

        $this->generateAIAnalysis($reading, $phone, $rootNumber, $numberMeaning, 'phone', $question);

        return $reading->fresh();
    }

    /**
     * วิเคราะห์ทะเบียนรถ
     *
     * @param  string  $licensePlate  ทะเบียนรถ เช่น "กข 1234"
     */
    public function analyzeLicensePlate(string $licensePlate, ?string $question = null): HoroscopeNumerologyReading
    {
        // แยกตัวอักษรและตัวเลข
        $thaiLetters = [];
        $numberPart = '';

        foreach (mb_str_split($licensePlate) as $char) {
            if (isset(ThaiNumerology::LETTER[$char])) {
                $thaiLetters[] = ['char' => $char, 'value' => ThaiNumerology::LETTER[$char]];
            } elseif (is_numeric($char)) {
                $numberPart .= $char;
            }
        }

        // รวมค่าตัวอักษร + ตัวเลข
        $letterSum = array_sum(array_column($thaiLetters, 'value'));
        $numberDigits = array_map('intval', str_split($numberPart ?: '0'));
        $numberSum = array_sum($numberDigits);
        $totalSum = $letterSum + $numberSum;
        $rootNumber = $this->reduceToSingleDigit($totalSum);
        $numberMeaning = self::NUMBER_MEANINGS[$rootNumber] ?? self::NUMBER_MEANINGS[1];

        $reading = HoroscopeNumerologyReading::create([
            'user_id' => auth()->id(),
            'reading_type' => HoroscopeNumerologyReading::TYPE_LICENSE_PLATE,
            'input_value' => $licensePlate,
            'numerology_number' => $rootNumber,
            'digit_breakdown' => json_encode([
                'plate' => $licensePlate,
                'letters' => $thaiLetters,
                'letter_sum' => $letterSum,
                'number_part' => $numberPart,
                'number_sum' => $numberSum,
                'total_sum' => $totalSum,
                'root' => $rootNumber,
            ]),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'status' => 'generating',
        ]);

        $this->generateAIAnalysis($reading, $licensePlate, $rootNumber, $numberMeaning, 'license_plate', $question);

        return $reading->fresh();
    }

    /**
     * วิเคราะห์วันเกิด
     *
     * @param  string  $birthday  วันเกิด (Y-m-d)
     */
    public function analyzeBirthday(string $birthday, ?string $question = null): HoroscopeNumerologyReading
    {
        $date = \Carbon\Carbon::parse($birthday);

        // คำนวณเลขชะตา (Life Path Number)
        $digits = array_map('intval', str_split($date->format('dmY')));
        $rootNumber = $this->reduceToSingleDigit(array_sum($digits));
        $numberMeaning = self::NUMBER_MEANINGS[$rootNumber] ?? self::NUMBER_MEANINGS[1];

        // วันเกิดตามวัน
        $dayOfWeek = $date->dayOfWeek; // 0=อาทิตย์
        $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        $thaiYear = $date->year + 543;

        $reading = HoroscopeNumerologyReading::create([
            'user_id' => auth()->id(),
            'reading_type' => HoroscopeNumerologyReading::TYPE_BIRTHDAY,
            'input_value' => $birthday,
            'numerology_number' => $rootNumber,
            'digit_breakdown' => json_encode([
                'date' => $birthday,
                'thai_year' => $thaiYear,
                'day_of_week' => $dayOfWeek,
                'day_name' => $dayNames[$dayOfWeek] ?? 'อาทิตย์',
                'digits' => $digits,
                'sum' => array_sum($digits),
                'root' => $rootNumber,
            ]),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'status' => 'generating',
        ]);

        $this->generateAIAnalysis($reading, $birthday, $rootNumber, $numberMeaning, 'birthday', $question);

        return $reading->fresh();
    }

    // ==========================================
    // ฟังก์ชันช่วย
    // ==========================================

    /**
     * แปลงชื่อไทยเป็นตัวเลข
     *
     * @return array ตัวเลขแต่ละตัว
     */
    public function thaiNameToDigits(string $name): array
    {
        return ThaiNumerology::name($name)['digits'];
    }

    /**
     * ลดตัวเลขให้เหลือ 1 หลัก (1-9) — ตามตำรา GenLotto: 0 → 9
     */
    public function reduceToSingleDigit(int $number): int
    {
        return ThaiNumerology::reduce($number);
    }

    /**
     * ดึง meanings ของเลข
     */
    public function getNumberMeaning(int $number): array
    {
        return self::NUMBER_MEANINGS[$number] ?? self::NUMBER_MEANINGS[1];
    }

    /**
     * ดึง meanings ทั้งหมด (สำหรับ view)
     */
    public static function getAllNumberMeanings(): array
    {
        return self::NUMBER_MEANINGS;
    }

    // ==========================================
    // AI Analysis
    // ==========================================

    /**
     * สร้างคำวิเคราะห์ด้วย AI
     */
    protected function generateAIAnalysis(
        HoroscopeNumerologyReading $reading,
        string $inputValue,
        int $rootNumber,
        array $numberMeaning,
        string $type,
        ?string $question,
        string $extraFacts = ''
    ): void {
        try {
            $prompt = $this->buildPrompt($inputValue, $rootNumber, $numberMeaning, $type, $question, $extraFacts);

            $result = $this->aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                promptTemplate: null,
                readingType: 'basic'
            );

            $aiResponse = $result['response'] ?? '';
            $parsed = $this->parseAIResponse($aiResponse);

            // สร้างเลขมงคลและสีมงคล — เกตุ (9) ไม่มีสีประจำตามตำรา → ข้ามช่องว่าง ไม่เติมสีเอง
            $luckyNumbers = $this->generateLuckyNumbers($rootNumber);
            $complementColor = self::NUMBER_MEANINGS[$this->getComplementNumber($rootNumber)]['color'] ?? '';
            $luckyColors = array_values(array_filter([$numberMeaning['color'], $complementColor], static fn ($c) => $c !== ''));

            $reading->update([
                'ai_analysis_th' => $parsed['analysis'],
                'ai_advice_th' => $parsed['advice'],
                'lucky_numbers' => $luckyNumbers,
                'lucky_colors' => $luckyColors,
                'ai_provider_used' => $result['provider'] ?? null,
                'ai_model_used' => $result['model'] ?? null,
                'status' => 'generated',
                'generated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("เลขศาสตร์ AI วิเคราะห์ไม่สำเร็จ ({$type}): ".$e->getMessage());

            // Fallback: ใช้ข้อมูลจาก meanings
            $reading->update([
                'ai_analysis_th' => "เลขชะตาของคุณคือ {$rootNumber} ({$numberMeaning['name']}) — {$numberMeaning['meaning']}",
                'ai_advice_th' => $numberMeaning['color'] !== ''
                    ? "เลข {$rootNumber} แนะนำให้ใช้สี{$numberMeaning['color']}เพื่อเสริมดวง"
                    : "เลข {$rootNumber} เป็นเลขดาว{$numberMeaning['name']} — ตำราไม่ได้กำหนดสีประจำเลขนี้",
                'lucky_numbers' => $this->generateLuckyNumbers($rootNumber),
                'lucky_colors' => array_values(array_filter([$numberMeaning['color']])),
                'status' => 'generated',
                'generated_at' => now(),
            ]);
        }
    }

    /**
     * สร้าง prompt สำหรับ AI
     */
    protected function buildPrompt(
        string $inputValue,
        int $rootNumber,
        array $numberMeaning,
        string $type,
        ?string $question,
        string $extraFacts = ''
    ): string {
        $typeLabels = [
            'name' => 'ชื่อ',
            'phone' => 'เบอร์โทรศัพท์',
            'license_plate' => 'ทะเบียนรถ',
            'id_card' => 'เลขบัตรประชาชน',
            'birthday' => 'วันเกิด',
        ];
        $typeLabel = $typeLabels[$type] ?? 'ข้อมูล';
        $questionPart = $question ? "\nคำถาม: \"{$question}\"" : '';
        // เกตุ (9) ไม่มีธาตุ/สีประจำตามตำรา → ไม่พิมพ์บรรทัดนั้น (กัน AI เติมเอง)
        $elementLine = $numberMeaning['element'] !== '' ? "ธาตุ: {$numberMeaning['element']}" : 'ธาตุ: (ตำราไม่ได้กำหนด — ห้ามระบุเอง)';
        $colorLine = $numberMeaning['color'] !== '' ? "สีมงคล: {$numberMeaning['color']}" : 'สีมงคล: (ตำราไม่ได้กำหนด — ห้ามระบุเอง)';
        $extraLine = $extraFacts !== '' ? "\n{$extraFacts}" : '';

        return <<<PROMPT
คุณเป็นนักเลขศาสตร์ระดับสูง เชี่ยวชาญเลขศาสตร์ไทยและพุทธศาสนา

วิเคราะห์{$typeLabel}: **{$inputValue}**
เลขชะตา (Root Number): **{$rootNumber}**
ดาวประจำเลข (เลขดาวไทย 1 อาทิตย์ … 8 ราหู 9 เกตุ): {$numberMeaning['name']}
ความหมายหลัก: {$numberMeaning['meaning']}
{$elementLine}
{$colorLine}{$extraLine}
{$questionPart}

กรุณาวิเคราะห์ในรูปแบบนี้:

[วิเคราะห์]
คำวิเคราะห์เชิงลึกเกี่ยวกับ{$typeLabel}นี้ (4-5 ประโยค) ครอบคลุมด้านชีวิต ความรัก การงาน การเงิน

[คำแนะนำ]
คำแนะนำเชิงปฏิบัติสำหรับเจ้าของ{$typeLabel}นี้ (2-3 ประโยค)

หมายเหตุ:
- ใช้ภาษาไทยทั้งหมด สุภาพ อ่านง่าย
- ให้คำแนะนำเชิงบวกเสมอ
- อ้างอิงหลักเลขศาสตร์ไทย
PROMPT;
    }

    /**
     * Parse AI response
     */
    protected function parseAIResponse(string $response): array
    {
        $result = ['analysis' => $response, 'advice' => null];

        if (preg_match('/\[วิเคราะห์\]\s*\n(.+?)(?=\[|$)/s', $response, $m)) {
            $result['analysis'] = trim($m[1]);
        }
        if (preg_match('/\[คำแนะนำ\]\s*\n(.+?)(?=\[|$)/s', $response, $m)) {
            $result['advice'] = trim($m[1]);
        }

        return $result;
    }

    /**
     * สร้างเลขมงคลจาก root number
     */
    protected function generateLuckyNumbers(int $rootNumber): array
    {
        $dayFactor = now()->dayOfYear;

        return [
            str_pad(($rootNumber * ($dayFactor % 11 + 1)) % 100, 2, '0', STR_PAD_LEFT),
            str_pad(($rootNumber * 7 + $dayFactor % 9) % 100, 2, '0', STR_PAD_LEFT),
            str_pad(($rootNumber * 3 + rand(1, 9)) % 100, 2, '0', STR_PAD_LEFT),
        ];
    }

    /**
     * ดึงเลขเสริม (complement)
     */
    protected function getComplementNumber(int $number): int
    {
        $complements = [
            1 => 4, 2 => 7, 3 => 5, 4 => 1, 5 => 3,
            6 => 9, 7 => 2, 8 => 6, 9 => 8,
        ];

        return $complements[$number] ?? 1;
    }
}
