<?php

namespace App\Services;

use App\Models\HoroscopeDreamCategory;
use App\Models\HoroscopeDreamDictionary;
use App\Models\HoroscopeDreamReading;
use Illuminate\Support\Facades\Log;

/**
 * HoroscopeDreamService — ทำนายฝัน + ค้นหาพจนานุกรมความฝัน
 *
 * รับผิดชอบ:
 * - ค้นหาพจนานุกรมความฝัน (FULLTEXT + LIKE)
 * - แยก keywords จากคำบรรยายฝัน
 * - ตีความฝันด้วย AI
 * - สร้างเลขเด็ดจากสัญลักษณ์ที่พบ
 */
class HoroscopeDreamService
{
    /**
     * @var FortuneAIService
     */
    protected FortuneAIService $aiService;

    /**
     * Constructor
     */
    public function __construct(FortuneAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    // ==========================================
    // ค้นหาพจนานุกรม
    // ==========================================

    /**
     * ค้นหาพจนานุกรมความฝัน
     *
     * @param string $query คำค้นหา
     * @param int $limit จำนวนผลลัพธ์สูงสุด
     * @return \Illuminate\Support\Collection
     */
    public function searchDictionary(string $query, int $limit = 20)
    {
        $query = trim($query);
        if (empty($query)) {
            return collect();
        }

        // ค้นหาด้วย LIKE (สำหรับ prefix match)
        $results = HoroscopeDreamDictionary::active()
            ->with('category')
            ->searchKeyword($query)
            ->limit($limit)
            ->get();

        // อัปเดต search count สำหรับผลลัพธ์ที่ตรงเป๊ะ
        $exactMatch = $results->firstWhere('keyword_th', $query);
        if ($exactMatch) {
            $exactMatch->incrementSearch();
        }

        return $results;
    }

    /**
     * ดึงพจนานุกรมตาม category
     *
     * @param string $categorySlug
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByCategory(string $categorySlug, int $perPage = 30)
    {
        $category = HoroscopeDreamCategory::where('slug', $categorySlug)->firstOrFail();

        return HoroscopeDreamDictionary::active()
            ->where('category_id', $category->id)
            ->orderBy('keyword_th')
            ->paginate($perPage);
    }

    // ==========================================
    // ตีความฝัน (AI)
    // ==========================================

    /**
     * ตีความฝันด้วย AI + จับคู่สัญลักษณ์จากพจนานุกรม
     *
     * @param string $dreamDescription คำบรรยายฝัน
     * @param string|null $question คำถามเพิ่มเติม
     * @return HoroscopeDreamReading
     */
    public function interpretDream(string $dreamDescription, ?string $question = null): HoroscopeDreamReading
    {
        // 1. แยก keywords จากคำบรรยาย
        $matchedSymbols = $this->extractKeywords($dreamDescription);

        // 2. สร้าง reading record
        $reading = HoroscopeDreamReading::create([
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'dream_description' => $dreamDescription,
            'matched_keywords' => $matchedSymbols->pluck('keyword_th')->toArray(),
            'is_free' => true,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // 3. เรียก AI ตีความ
        try {
            $prompt = $this->buildDreamPrompt($dreamDescription, $matchedSymbols, $question);

            $result = $this->aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                promptTemplate: null,
                readingType: 'basic'
            );

            $aiResponse = $result['response'] ?? '';

            // 4. สร้างเลขเด็ดจากสัญลักษณ์
            $luckyNumbers = $this->generateLuckyNumbers($matchedSymbols);

            $reading->update([
                'ai_interpretation' => $aiResponse,
                'lucky_numbers' => $luckyNumbers,
                'ai_provider_used' => $result['provider'] ?? null,
                'ai_model_used' => $result['model'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('ทำนายฝัน AI ผิดพลาด: '.$e->getMessage());

            // Fallback: ใช้ข้อมูลจากพจนานุกรม
            $fallback = $this->buildFallbackInterpretation($matchedSymbols, $dreamDescription);
            $luckyNumbers = $this->generateLuckyNumbers($matchedSymbols);

            $reading->update([
                'ai_interpretation' => $fallback,
                'lucky_numbers' => $luckyNumbers,
            ]);
        }

        // อัปเดต search count ของ symbols ที่พบ
        foreach ($matchedSymbols as $symbol) {
            $symbol->incrementSearch();
        }

        return $reading->fresh();
    }

    // ==========================================
    // Extract Keywords
    // ==========================================

    /**
     * แยก keywords จากคำบรรยายฝัน → จับคู่กับพจนานุกรม
     *
     * @param string $description
     * @return \Illuminate\Support\Collection HoroscopeDreamDictionary models
     */
    public function extractKeywords(string $description)
    {
        // ดึงคำจากพจนานุกรมทั้งหมด (active)
        $allKeywords = HoroscopeDreamDictionary::active()
            ->select('id', 'keyword_th', 'category_id', 'meaning_th', 'lucky_numbers', 'is_good_omen', 'intensity')
            ->get();

        $matched = collect();

        // จับคู่คำที่ปรากฏในคำบรรยาย
        foreach ($allKeywords as $keyword) {
            if (mb_strpos($description, $keyword->keyword_th) !== false) {
                $matched->push($keyword);
            }
        }

        // เรียงตาม intensity (สูง → ต่ำ)
        return $matched->sortByDesc('intensity')->values();
    }

    // ==========================================
    // สร้างเลขเด็ด
    // ==========================================

    /**
     * สร้างเลขเด็ดจากสัญลักษณ์ที่จับคู่ได้
     *
     * อัลกอริทึม:
     * 1. ดึง lucky_numbers จากแต่ละ symbol
     * 2. รวมเลขเด็ดจากทุก symbol
     * 3. เพิ่มเลขจากวันที่ + จำนวน symbol
     *
     * @param \Illuminate\Support\Collection $matchedSymbols
     * @return array เลขเด็ด 3-5 ตัว
     */
    public function generateLuckyNumbers($matchedSymbols): array
    {
        $numbers = [];

        // ดึงจากพจนานุกรม
        foreach ($matchedSymbols as $symbol) {
            $symbolNumbers = $symbol->lucky_numbers ?? [];
            foreach ($symbolNumbers as $num) {
                $numbers[] = (string) $num;
            }
        }

        // เพิ่มเลขจากคำนวณ
        $dayFactor = now()->dayOfYear;
        $symbolCount = $matchedSymbols->count() ?: 1;

        $numbers[] = str_pad(($symbolCount * ($dayFactor % 17 + 1)) % 100, 2, '0', STR_PAD_LEFT);
        $numbers[] = str_pad(($symbolCount * 7 + $dayFactor % 11) % 100, 2, '0', STR_PAD_LEFT);

        // ลบซ้ำ + จำกัด 5 ตัว
        $unique = array_unique($numbers);
        $unique = array_filter($unique, fn ($n) => $n !== '00');

        return array_values(array_slice($unique, 0, 5));
    }

    // ==========================================
    // AI Prompt
    // ==========================================

    /**
     * สร้าง prompt ทำนายฝัน
     */
    protected function buildDreamPrompt(string $description, $matchedSymbols, ?string $question): string
    {
        $symbolList = '';
        if ($matchedSymbols->isNotEmpty()) {
            $symbolList = "\n\nสัญลักษณ์ที่พบในฝัน:\n";
            foreach ($matchedSymbols->take(10) as $symbol) {
                $omen = $symbol->is_good_omen ? '(ดี)' : '(ระวัง)';
                $symbolList .= "- {$symbol->keyword_th}: {$symbol->meaning_th} {$omen}\n";
            }
        }

        $questionPart = $question ? "\nคำถามเพิ่มเติม: \"{$question}\"" : '';

        return <<<PROMPT
คุณเป็นผู้เชี่ยวชาญด้านการทำนายฝันตามตำราไทยและสากล

ผู้ใช้เล่าฝันว่า:
"{$description}"
{$symbolList}
{$questionPart}

กรุณาทำนายฝันนี้โดยครอบคลุม:

1. **ความหมายโดยรวม** — สรุปความหมายของฝันนี้ (3-4 ประโยค)
2. **สัญลักษณ์สำคัญ** — อธิบายความหมายของสัญลักษณ์ที่โดดเด่น (2-3 สัญลักษณ์)
3. **ด้านความรัก** — ฝันนี้บ่งบอกอะไรเกี่ยวกับความรัก (1-2 ประโยค)
4. **ด้านการงาน/การเงิน** — ฝันนี้บ่งบอกอะไรเกี่ยวกับการงานหรือการเงิน (1-2 ประโยค)
5. **คำแนะนำ** — สิ่งที่ควรทำหลังฝันนี้ (2-3 ประโยค)

หมายเหตุ:
- ใช้ภาษาไทยทั้งหมด สุภาพ อ่านง่าย
- ให้คำแนะนำเชิงบวกเสมอ
- อ้างอิงตำราทำนายฝันไทย
- ห้ามทำให้ผู้อ่านกลัวหรือวิตกกังวล
PROMPT;
    }

    /**
     * Fallback interpretation จากพจนานุกรม
     */
    protected function buildFallbackInterpretation($matchedSymbols, string $description): string
    {
        if ($matchedSymbols->isEmpty()) {
            return "ฝันของคุณมีความหมายเกี่ยวกับจิตใต้สำนึกที่กำลังสื่อสารข้อความสำคัญ จงตั้งใจสังเกตสัญญาณรอบตัวในวันนี้ เพราะจักรวาลอาจกำลังส่งโอกาสดีๆ มาให้คุณ";
        }

        $parts = [];
        foreach ($matchedSymbols->take(3) as $symbol) {
            $omen = $symbol->is_good_omen ? 'เป็นลางดี' : 'ควรระวัง';
            $parts[] = "การฝันเห็น \"{$symbol->keyword_th}\" {$omen} หมายถึง {$symbol->meaning_th}";
        }

        return implode("\n\n", $parts)."\n\nโดยรวมแล้ว ฝันนี้กำลังส่งข้อความสำคัญมาให้คุณ จงตั้งใจรับรู้และนำไปปรับใช้ในชีวิตประจำวัน";
    }
}
