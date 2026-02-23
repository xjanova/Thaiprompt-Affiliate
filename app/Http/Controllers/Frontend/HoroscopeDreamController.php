<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\HoroscopeDreamCategory;
use App\Models\HoroscopeDreamDictionary;
use App\Models\HoroscopeDreamReading;
use App\Services\HoroscopeDreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HoroscopeDreamController — ทำนายฝัน + เลขเด็ด
 *
 * จัดการ:
 * - หน้าหลักทำนายฝัน (ค้นหา + หมวดหมู่ + เล่าฝันให้ AI ฟัง)
 * - ค้นหาพจนานุกรมฝัน (AJAX autocomplete)
 * - ทำนายฝันด้วย AI
 * - แสดงสัญลักษณ์ตามหมวดหมู่
 * - แสดงผลทำนายฝัน (shareable)
 */
class HoroscopeDreamController extends Controller
{
    /**
     * @var HoroscopeDreamService
     */
    protected HoroscopeDreamService $dreamService;

    /**
     * Constructor
     */
    public function __construct(HoroscopeDreamService $dreamService)
    {
        $this->dreamService = $dreamService;
    }

    /**
     * หน้าหลักทำนายฝัน — ค้นหา + หมวดหมู่ + เล่าฝันให้ AI ฟัง
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงหมวดหมู่ทั้งหมด (active + เรียงลำดับ)
        $categories = HoroscopeDreamCategory::active()
            ->ordered()
            ->withCount(['dreamSymbols' => fn ($q) => $q->where('is_active', true)])
            ->get();

        // สัญลักษณ์ยอดนิยม 12 อันดับ
        $popularSymbols = HoroscopeDreamDictionary::active()
            ->with('category')
            ->popular()
            ->limit(12)
            ->get();

        // สถิติ
        $totalSymbols = HoroscopeDreamDictionary::active()->count();
        $totalReadings = HoroscopeDreamReading::count();
        $todayReadings = HoroscopeDreamReading::whereDate('created_at', today())->count();

        return view('frontend.horoscope.dream.index', [
            'pageTitle' => 'ทำนายฝัน — ค้นหาความหมายของฝัน + เลขเด็ด',
            'categories' => $categories,
            'popularSymbols' => $popularSymbols,
            'totalSymbols' => $totalSymbols,
            'totalReadings' => $totalReadings,
            'todayReadings' => $todayReadings,
        ]);
    }

    /**
     * ค้นหาพจนานุกรมฝัน (AJAX)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1|max:100',
        ]);

        try {
            $results = $this->dreamService->searchDictionary(
                query: $request->input('query'),
                limit: 20
            );

            return response()->json([
                'success' => true,
                'data' => $results->map(fn ($item) => [
                    'id' => $item->id,
                    'keyword' => $item->keyword_th,
                    'meaning' => $item->meaning_th,
                    'lucky_numbers' => $item->lucky_numbers ?? [],
                    'is_good_omen' => $item->is_good_omen,
                    'intensity' => $item->intensity,
                    'category' => $item->category?->name_th,
                    'category_icon' => $item->category?->icon,
                    'omen_icon' => $item->is_good_omen ? '✅' : '⚠️',
                ]),
                'count' => $results->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('ค้นหาพจนานุกรมฝันผิดพลาด: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการค้นหา',
            ], 500);
        }
    }

    /**
     * ทำนายฝันด้วย AI (AJAX)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function interpret(Request $request): JsonResponse
    {
        $request->validate([
            'dream_description' => 'required|string|min:10|max:2000',
            'question' => 'nullable|string|max:500',
        ]);

        try {
            $reading = $this->dreamService->interpretDream(
                dreamDescription: $request->input('dream_description'),
                question: $request->input('question')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $reading->id,
                    'dream_description' => $reading->dream_description,
                    'matched_keywords' => $reading->matched_keywords ?? [],
                    'ai_interpretation' => $reading->ai_interpretation,
                    'lucky_numbers' => $reading->lucky_numbers ?? [],
                    'result_url' => route('horoscope.dream.result', $reading->id),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ทำนายฝัน AI ผิดพลาด: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการทำนายฝัน กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }

    /**
     * แสดงสัญลักษณ์ตามหมวดหมู่
     *
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function showCategory(string $slug)
    {
        $category = HoroscopeDreamCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // ดึงสัญลักษณ์ในหมวด (paginate)
        $symbols = HoroscopeDreamDictionary::active()
            ->where('category_id', $category->id)
            ->orderBy('keyword_th')
            ->paginate(30);

        // หมวดหมู่ทั้งหมด (สำหรับ sidebar)
        $allCategories = HoroscopeDreamCategory::active()
            ->ordered()
            ->withCount(['dreamSymbols' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return view('frontend.horoscope.dream.category', [
            'pageTitle' => "ทำนายฝัน — หมวด{$category->name_th}",
            'category' => $category,
            'symbols' => $symbols,
            'allCategories' => $allCategories,
        ]);
    }

    /**
     * แสดงผลทำนายฝัน (shareable)
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showResult(int $id)
    {
        $reading = HoroscopeDreamReading::findOrFail($id);

        // อัปเดต view count
        $reading->incrementView();

        // ดึงข้อมูลสัญลักษณ์ที่ตรงกัน
        $matchedSymbols = collect();
        if (! empty($reading->matched_keywords)) {
            $matchedSymbols = HoroscopeDreamDictionary::active()
                ->with('category')
                ->whereIn('keyword_th', $reading->matched_keywords)
                ->get();
        }

        return view('frontend.horoscope.dream.result', [
            'pageTitle' => 'ผลทำนายฝัน — ' . \Illuminate\Support\Str::limit($reading->dream_description, 30),
            'reading' => $reading,
            'matchedSymbols' => $matchedSymbols,
        ]);
    }
}
