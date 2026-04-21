<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Models\TarotCard;
use App\Models\TarotReading;
use App\Models\TarotReadingCard;
use App\Models\TarotReadingCategory;
use App\Models\TarotSpreadType;
use App\Services\FortuneAIService;
use App\Services\TarotInterpretationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HoroscopeTarotController — ไพ่ทาโรต์บน Horoscope Public
 *
 * จัดการ:
 * - หน้าหลักไพ่ทาโรต์ (3 ตัวเลือก)
 * - Quick 1-Card Reading (ฟรี, ไม่ต้อง login)
 * - AI Interpretation สำหรับ quick reading
 */
class HoroscopeTarotController extends Controller
{
    /**
     * หน้าหลักไพ่ทาโรต์ — แสดง 3 ตัวเลือก
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงสถิติ
        $totalReadings = TarotReading::count();
        $todayReadings = TarotReading::today()->count();
        $totalCards = TarotCard::active()->count();

        // ดึง categories สำหรับแสดง
        $categories = TarotReadingCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // ดึง spread types
        $spreadTypes = TarotSpreadType::active()->ordered()->get();

        // ตัดคำว่า "ดูดวงฟรี" ออกจาก page title ถ้า admin ปิดการใช้งานฟรี
        $settings = FortuneTellingSetting::first();
        $freeEnabled = $settings ? $settings->isHoroscopePublicFreeEnabled() : true;
        $pageTitle = $freeEnabled
            ? 'ไพ่ทาโรต์ออนไลน์ — ดูดวงฟรี'
            : 'ไพ่ทาโรต์ออนไลน์';

        return view('frontend.horoscope.tarot.index', [
            'pageTitle' => $pageTitle,
            'totalReadings' => $totalReadings,
            'todayReadings' => $todayReadings,
            'totalCards' => $totalCards,
            'categories' => $categories,
            'spreadTypes' => $spreadTypes,
        ]);
    }

    /**
     * Quick 1-Card Reading — เปิดไพ่ 1 ใบ (ฟรี)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function quickReading(Request $request)
    {
        // ดึงหมวดหมู่สำหรับเลือก
        $categories = TarotReadingCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('frontend.horoscope.tarot.quick-reading', [
            'pageTitle' => 'เปิดไพ่ทาโรต์ 1 ใบ — Quick Reading',
            'categories' => $categories,
        ]);
    }

    /**
     * Quick Interpret — AI ตีความไพ่ที่เลือก
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function quickInterpret(Request $request): JsonResponse
    {
        $request->validate([
            'card_id' => 'nullable|integer',
            'is_reversed' => 'required|boolean',
            'category' => 'nullable|string',
            'question' => 'nullable|string|max:500',
        ]);

        try {
            // ถ้า card_id = 0 หรือไม่ได้ระบุ → สุ่มไพ่ 1 ใบ
            if (! $request->card_id || $request->card_id === 0) {
                $card = TarotCard::active()->inRandomOrder()->firstOrFail();
            } else {
                $card = TarotCard::findOrFail($request->card_id);
            }
            $isReversed = (bool) $request->is_reversed;
            $categorySlug = $request->category ?? 'general';
            $question = $request->question;

            // ดึง category
            $category = TarotReadingCategory::where('slug', $categorySlug)->first();

            // ดึง spread type สำหรับ 1 ใบ
            $spreadType = TarotSpreadType::where('card_count', 1)->first();

            // สร้าง reading record
            $reading = TarotReading::create([
                'user_id' => auth()->id(),
                'category_id' => $category?->id,
                'spread_type_id' => $spreadType?->id,
                'question' => $question,
                'is_free' => true,
                'session_id' => session()->getId(),
                'ip_address' => $request->ip(),
            ]);

            // สร้าง reading card
            $readingCard = TarotReadingCard::create([
                'reading_id' => $reading->id,
                'card_id' => $card->id,
                'position' => 1,
                'position_name' => 'คำตอบ',
                'is_reversed' => $isReversed,
            ]);

            // สร้างคำตีความ
            $interpretation = $this->generateQuickInterpretation($card, $isReversed, $categorySlug, $question);

            // อัปเดต reading
            $readingCard->update(['interpretation' => $interpretation['card_interpretation']]);
            $reading->update(['interpretation' => $interpretation['overall']]);

            return response()->json([
                'success' => true,
                'data' => [
                    'card' => [
                        'id' => $card->id,
                        'name_th' => $card->name_th,
                        'name_en' => $card->name_en,
                        'type' => $card->type,
                        'suit' => $card->suit,
                        'image_url' => $card->image_url,
                        'is_reversed' => $isReversed,
                        'orientation' => $isReversed ? 'กลับหัว' : 'หัวตั้ง',
                        'meaning' => $card->getMeaning($isReversed, 'th'),
                        'keywords' => $card->getKeywords('th'),
                    ],
                    'interpretation' => $interpretation['card_interpretation'],
                    'overall' => $interpretation['overall'],
                    'advice' => $interpretation['advice'],
                    'energy' => $interpretation['energy'],
                    'reading_id' => $reading->id,
                ],
                'message' => 'ตีความไพ่สำเร็จ',
            ]);
        } catch (\Exception $e) {
            Log::error('Quick Tarot Interpret Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการตีความ กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * สร้างคำตีความ Quick Reading
     *
     * ลำดับ:
     * 1. ลองใช้ AI (FortuneAIService)
     * 2. ถ้า AI ไม่ว่าง ใช้ TarotInterpretationService
     * 3. ถ้าไม่ได้ทั้งคู่ ใช้ meaning เดิมของไพ่
     *
     * @param TarotCard $card
     * @param bool $isReversed
     * @param string $categorySlug
     * @param string|null $question
     * @return array
     */
    protected function generateQuickInterpretation(
        TarotCard $card,
        bool $isReversed,
        string $categorySlug,
        ?string $question
    ): array {
        // ลอง AI ก่อน
        try {
            return $this->generateAIInterpretation($card, $isReversed, $categorySlug, $question);
        } catch (\Exception $e) {
            Log::info('AI Tarot interpretation unavailable, using fallback: '.$e->getMessage());
        }

        // Fallback: ใช้คำตีความจาก DB / card meaning
        return $this->generateFallbackInterpretation($card, $isReversed, $categorySlug);
    }

    /**
     * สร้างคำตีความด้วย AI
     *
     * @param TarotCard $card
     * @param bool $isReversed
     * @param string $categorySlug
     * @param string|null $question
     * @return array
     */
    protected function generateAIInterpretation(
        TarotCard $card,
        bool $isReversed,
        string $categorySlug,
        ?string $question
    ): array {
        $aiService = app(FortuneAIService::class);

        $orientation = $isReversed ? 'กลับหัว (Reversed)' : 'หัวตั้ง (Upright)';
        $meaning = $card->getMeaning($isReversed, 'th');
        $keywords = implode(', ', $card->getKeywords('th') ?? []);

        $categoryLabels = [
            'love-relationships' => 'ความรักและความสัมพันธ์',
            'career-finance' => 'การงานและการเงิน',
            'personal-growth' => 'การพัฒนาตนเอง',
            'health-wellness' => 'สุขภาพและความเป็นอยู่',
            'general' => 'ภาพรวมชีวิต',
        ];
        $categoryLabel = $categoryLabels[$categorySlug] ?? 'ภาพรวมชีวิต';

        $questionPart = $question ? "\nคำถามของผู้ถาม: \"{$question}\"" : '';

        $prompt = <<<PROMPT
คุณเป็นนักอ่านไพ่ทาโรต์ระดับสูง เชี่ยวชาญการตีความไพ่ทาโรต์

ไพ่ที่จับได้: **{$card->name_th}** ({$card->name_en})
สถานะ: **{$orientation}**
ชนิด: {$card->type} {$card->suit}
ความหมายหลัก: {$meaning}
คำสำคัญ: {$keywords}
หมวดหมู่คำถาม: {$categoryLabel}
{$questionPart}

กรุณาตีความไพ่ใบนี้ ให้ครอบคลุม:

[คำตีความ]
อธิบายความหมายของไพ่ใบนี้อย่างละเอียด สำหรับหมวด{$categoryLabel} (3-4 ประโยค)

[คำแนะนำ]
ให้คำแนะนำเชิงบวกและปฏิบัติได้จริง (2-3 ประโยค)

[พลังงาน]
อธิบายพลังงานของไพ่ใบนี้สั้นๆ (1 ประโยค)

หมายเหตุ:
- ใช้ภาษาไทยทั้งหมด สุภาพ อ่านง่าย
- ให้คำแนะนำเชิงบวกเสมอ แม้ไพ่จะกลับหัว
- ใช้ภาษาที่สร้างแรงบันดาลใจ
PROMPT;

        $result = $aiService->generateWithRetryAndFallback(
            questions: [$prompt],
            promptTemplate: null,
            readingType: 'basic'
        );

        $parsed = $this->parseAITarotResponse($result['response'] ?? '');

        return [
            'card_interpretation' => $parsed['interpretation'],
            'overall' => $parsed['interpretation'],
            'advice' => $parsed['advice'],
            'energy' => $parsed['energy'],
        ];
    }

    /**
     * Parse AI tarot response
     *
     * @param string $response
     * @return array
     */
    protected function parseAITarotResponse(string $response): array
    {
        $result = [
            'interpretation' => $response,
            'advice' => null,
            'energy' => null,
        ];

        // แยกส่วน [คำตีความ]
        if (preg_match('/\[คำตีความ\]\s*\n(.+?)(?=\[|$)/s', $response, $m)) {
            $result['interpretation'] = trim($m[1]);
        }

        // แยกส่วน [คำแนะนำ]
        if (preg_match('/\[คำแนะนำ\]\s*\n(.+?)(?=\[|$)/s', $response, $m)) {
            $result['advice'] = trim($m[1]);
        }

        // แยกส่วน [พลังงาน]
        if (preg_match('/\[พลังงาน\]\s*\n(.+?)(?=\[|$)/s', $response, $m)) {
            $result['energy'] = trim($m[1]);
        }

        return $result;
    }

    /**
     * Fallback: ใช้ข้อมูลจาก DB / ความหมายเดิม
     *
     * @param TarotCard $card
     * @param bool $isReversed
     * @param string $categorySlug
     * @return array
     */
    protected function generateFallbackInterpretation(
        TarotCard $card,
        bool $isReversed,
        string $categorySlug
    ): array {
        $meaning = $card->getMeaning($isReversed, 'th');
        $orientation = $isReversed ? 'กลับหัว' : 'หัวตั้ง';

        // ลองดึง custom interpretation จาก DB
        $category = TarotReadingCategory::where('slug', $categorySlug)->first();
        $customInterp = null;
        if ($category) {
            $customInterp = $card->getInterpretationForCategory($category->id);
        }

        if ($customInterp) {
            $interpText = $isReversed
                ? ($customInterp->reversed_th ?? $meaning)
                : ($customInterp->upright_th ?? $meaning);
            $advice = $customInterp->advice_th ?? "จงมั่นใจในเส้นทางของคุณ ไพ่ {$card->name_th} ส่งพลังแห่งการเปลี่ยนแปลงมาให้";
        } else {
            $interpText = "ไพ่ **{$card->name_th}** ({$orientation}) — {$meaning}";
            $advice = "จงเปิดใจรับพลังจากไพ่ {$card->name_th} และนำคำแนะนำไปปรับใช้ในชีวิตประจำวัน";
        }

        $energy = $isReversed
            ? "พลังงานของไพ่ {$card->name_th} กำลังเชิญชวนให้คุณมองสถานการณ์จากมุมใหม่"
            : "พลังงานของไพ่ {$card->name_th} กำลังหมุนเวียนอย่างเข้มแข็งรอบตัวคุณ";

        return [
            'card_interpretation' => $interpText,
            'overall' => $interpText,
            'advice' => $advice,
            'energy' => $energy,
        ];
    }
}
