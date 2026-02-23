<?php

namespace App\Services;

use App\Models\HoroscopeNumerologyReading;
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
    /**
     * @var FortuneAIService
     */
    protected FortuneAIService $aiService;

    /**
     * แผนที่พยัญชนะไทย → ตัวเลข (หลักเลขศาสตร์ไทย)
     *
     * กลุ่ม 1: ก ข ฃ
     * กลุ่ม 2: ค ฅ ฆ
     * กลุ่ม 3: ง จ ฉ
     * กลุ่ม 4: ช ซ ญ
     * กลุ่ม 5: ด ต ถ ท ธ
     * กลุ่ม 6: น บ ป ผ ฝ พ ฟ ภ ม
     * กลุ่ม 7: ย ร ล ว
     * กลุ่ม 8: ศ ษ ส ห ฬ อ ฮ
     * กลุ่ม 9: ฎ ฏ ฐ
     */
    protected const THAI_CONSONANT_MAP = [
        'ก' => 1, 'ข' => 1, 'ฃ' => 1,
        'ค' => 2, 'ฅ' => 2, 'ฆ' => 2,
        'ง' => 3, 'จ' => 3, 'ฉ' => 3,
        'ช' => 4, 'ซ' => 4, 'ญ' => 4,
        'ด' => 5, 'ต' => 5, 'ถ' => 5, 'ท' => 5, 'ธ' => 5,
        'น' => 6, 'บ' => 6, 'ป' => 6, 'ผ' => 6, 'ฝ' => 6,
        'พ' => 6, 'ฟ' => 6, 'ภ' => 6, 'ม' => 6,
        'ย' => 7, 'ร' => 7, 'ล' => 7, 'ว' => 7,
        'ศ' => 8, 'ษ' => 8, 'ส' => 8, 'ห' => 8, 'ฬ' => 8,
        'อ' => 8, 'ฮ' => 8,
        'ฎ' => 9, 'ฏ' => 9, 'ฐ' => 9,
    ];

    /**
     * ความหมายของเลขศาสตร์ 1-9
     */
    protected const NUMBER_MEANINGS = [
        1 => ['name' => 'อาทิตย์', 'meaning' => 'ผู้นำ ความมั่นใจ อิสระ', 'color' => 'แดง', 'element' => 'ไฟ'],
        2 => ['name' => 'จันทร์', 'meaning' => 'อ่อนโยน ความร่วมมือ สมดุล', 'color' => 'ขาว', 'element' => 'น้ำ'],
        3 => ['name' => 'พฤหัสบดี', 'meaning' => 'สร้างสรรค์ สนุกสนาน มองโลกในแง่ดี', 'color' => 'เหลือง', 'element' => 'ลม'],
        4 => ['name' => 'ราหู', 'meaning' => 'มั่นคง ระเบียบ ขยัน', 'color' => 'เขียว', 'element' => 'ดิน'],
        5 => ['name' => 'พุธ', 'meaning' => 'อิสระ การเปลี่ยนแปลง ผจญภัย', 'color' => 'ฟ้า', 'element' => 'ลม'],
        6 => ['name' => 'ศุกร์', 'meaning' => 'ความรัก ครอบครัว ความรับผิดชอบ', 'color' => 'ชมพู', 'element' => 'น้ำ'],
        7 => ['name' => 'เกตุ', 'meaning' => 'ปัญญา จิตวิญญาณ ลึกลับ', 'color' => 'ม่วง', 'element' => 'ดิน'],
        8 => ['name' => 'เสาร์', 'meaning' => 'อำนาจ ความสำเร็จ มั่งคั่ง', 'color' => 'ดำ', 'element' => 'ดิน'],
        9 => ['name' => 'อังคาร', 'meaning' => 'กล้าหาญ เสียสละ จบสิ้น', 'color' => 'ส้ม', 'element' => 'ไฟ'],
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
     * @param string $name ชื่อภาษาไทย
     * @param string|null $question คำถามเพิ่มเติม
     * @return HoroscopeNumerologyReading
     */
    public function analyzeName(string $name, ?string $question = null): HoroscopeNumerologyReading
    {
        $digits = $this->thaiNameToDigits($name);
        $rootNumber = $this->reduceToSingleDigit(array_sum($digits));
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
                'sum' => array_sum($digits),
                'root' => $rootNumber,
            ]),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'status' => 'generating',
        ]);

        // เรียก AI วิเคราะห์
        $this->generateAIAnalysis($reading, $name, $rootNumber, $numberMeaning, 'name', $question);

        return $reading->fresh();
    }

    /**
     * วิเคราะห์เบอร์โทรศัพท์
     *
     * @param string $phone เบอร์โทร
     * @param string|null $question
     * @return HoroscopeNumerologyReading
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
     * @param string $licensePlate ทะเบียนรถ เช่น "กข 1234"
     * @param string|null $question
     * @return HoroscopeNumerologyReading
     */
    public function analyzeLicensePlate(string $licensePlate, ?string $question = null): HoroscopeNumerologyReading
    {
        // แยกตัวอักษรและตัวเลข
        $thaiLetters = [];
        $numberPart = '';

        foreach (mb_str_split($licensePlate) as $char) {
            if (isset(self::THAI_CONSONANT_MAP[$char])) {
                $thaiLetters[] = ['char' => $char, 'value' => self::THAI_CONSONANT_MAP[$char]];
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
     * @param string $birthday วันเกิด (Y-m-d)
     * @param string|null $question
     * @return HoroscopeNumerologyReading
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
     * @param string $name
     * @return array ตัวเลขแต่ละตัว
     */
    public function thaiNameToDigits(string $name): array
    {
        $digits = [];
        foreach (mb_str_split($name) as $char) {
            if (isset(self::THAI_CONSONANT_MAP[$char])) {
                $digits[] = self::THAI_CONSONANT_MAP[$char];
            }
        }

        return $digits;
    }

    /**
     * ลดตัวเลขให้เหลือ 1 หลัก (1-9)
     *
     * @param int $number
     * @return int
     */
    public function reduceToSingleDigit(int $number): int
    {
        while ($number > 9) {
            $number = array_sum(array_map('intval', str_split((string) $number)));
        }

        return max(1, $number);
    }

    /**
     * ดึง meanings ของเลข
     *
     * @param int $number
     * @return array
     */
    public function getNumberMeaning(int $number): array
    {
        return self::NUMBER_MEANINGS[$number] ?? self::NUMBER_MEANINGS[1];
    }

    /**
     * ดึง meanings ทั้งหมด (สำหรับ view)
     *
     * @return array
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
     *
     * @param HoroscopeNumerologyReading $reading
     * @param string $inputValue
     * @param int $rootNumber
     * @param array $numberMeaning
     * @param string $type
     * @param string|null $question
     * @return void
     */
    protected function generateAIAnalysis(
        HoroscopeNumerologyReading $reading,
        string $inputValue,
        int $rootNumber,
        array $numberMeaning,
        string $type,
        ?string $question
    ): void {
        try {
            $prompt = $this->buildPrompt($inputValue, $rootNumber, $numberMeaning, $type, $question);

            $result = $this->aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                promptTemplate: null,
                readingType: 'basic'
            );

            $aiResponse = $result['response'] ?? '';
            $parsed = $this->parseAIResponse($aiResponse);

            // สร้างเลขมงคลและสีมงคล
            $luckyNumbers = $this->generateLuckyNumbers($rootNumber);
            $luckyColors = [$numberMeaning['color']];
            $complementColor = self::NUMBER_MEANINGS[$this->getComplementNumber($rootNumber)]['color'] ?? 'ทอง';
            $luckyColors[] = $complementColor;

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
                'ai_advice_th' => "เลข {$rootNumber} แนะนำให้ใช้สี{$numberMeaning['color']}เพื่อเสริมดวง",
                'lucky_numbers' => $this->generateLuckyNumbers($rootNumber),
                'lucky_colors' => [$numberMeaning['color']],
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
        ?string $question
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

        return <<<PROMPT
คุณเป็นนักเลขศาสตร์ระดับสูง เชี่ยวชาญเลขศาสตร์ไทยและพุทธศาสนา

วิเคราะห์{$typeLabel}: **{$inputValue}**
เลขชะตา (Root Number): **{$rootNumber}**
ดาวประจำเลข: {$numberMeaning['name']}
ความหมายหลัก: {$numberMeaning['meaning']}
ธาตุ: {$numberMeaning['element']}
สีมงคล: {$numberMeaning['color']}
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
