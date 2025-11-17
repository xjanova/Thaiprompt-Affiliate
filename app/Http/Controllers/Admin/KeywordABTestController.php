<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KeywordABTest;
use App\Models\KeywordABTestResult;
use App\Models\KeywordABTestVariant;
use App\Models\LineBotKeyword;
use App\Services\KeywordABTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Keyword A/B Test Controller
 *
 * จัดการ A/B test สำหรับคีย์เวิร์ด
 */
class KeywordABTestController extends Controller
{
    public function __construct(
        private KeywordABTestService $abTestService
    ) {}

    /**
     * แสดง A/B tests dashboard
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $filter = $request->get('filter', 'all'); // all, active, completed
        $keyword_id = $request->get('keyword_id');

        $query = KeywordABTest::with(['keyword', 'variants']);

        if ($filter === 'active') {
            $query = $query->active();
        } elseif ($filter === 'completed') {
            $query = $query->completed();
        }

        if ($keyword_id) {
            $query = $query->where('keyword_id', $keyword_id);
        }

        $tests = $query->latest()->paginate(15);
        $stats = $this->abTestService->getDashboardStats();
        $recommendations = $this->abTestService->getRecommendations();
        $keywords = LineBotKeyword::where('is_active', true)
            ->orderBy('keyword')
            ->get();

        return view('admin.line-bot.keywords.ab-tests.index', [
            'tests' => $tests,
            'stats' => $stats,
            'recommendations' => $recommendations,
            'keywords' => $keywords,
            'filter' => $filter,
            'selected_keyword_id' => $keyword_id,
        ]);
    }

    /**
     * แสดงหน้าสร้าง A/B test ใหม่
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        $keyword_id = $request->get('keyword_id');
        $keyword = null;

        if ($keyword_id) {
            $keyword = LineBotKeyword::findOrFail($keyword_id);
        }

        $keywords = LineBotKeyword::where('is_active', true)
            ->orderBy('keyword')
            ->get();

        return view('admin.line-bot.keywords.ab-tests.create', [
            'keywords' => $keywords,
            'selected_keyword' => $keyword,
        ]);
    }

    /**
     * บันทึก A/B test ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'keyword_id' => 'required|exists:line_bot_keywords,id',
            'test_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'variant_a_percentage' => 'integer|between:1,99',
            'variant_b_percentage' => 'integer|between:1,99',
            'winning_criterion' => 'required|in:conversion_rate,response_time,satisfaction,interaction_rate',
            'minimum_samples' => 'integer|min:10|max:10000',
            'variant_a.response_text' => 'required|string|max:1000',
            'variant_a.response_type' => 'required|in:text,quick_reply,flex_message',
            'variant_b.response_text' => 'required|string|max:1000',
            'variant_b.response_type' => 'required|in:text,quick_reply,flex_message',
        ]);

        // ตรวจสอบ percentages รวมกันเท่า 100
        if (($validated['variant_a_percentage'] + $validated['variant_b_percentage']) !== 100) {
            return back()->withErrors([
                'variant_percentage' => 'A + B ต้องรวมกันเท่า 100%',
            ]);
        }

        $test = $this->abTestService->createTest($validated);

        return redirect()
            ->route('admin.line-bot.keywords.ab-tests.show', $test)
            ->with('success', 'สร้าง A/B test สำเร็จ');
    }

    /**
     * แสดงรายละเอียด A/B test
     *
     * @param KeywordABTest $test
     * @return View
     */
    public function show(KeywordABTest $test): View
    {
        $test->load(['keyword', 'variants', 'results_records']);

        $variantA = $test->variantA();
        $variantB = $test->variantB();

        $summary = $this->abTestService->getTestSummary($test);

        // Get comparison data
        $comparison = [
            'variant_a' => $variantA->getDetailedStats(),
            'variant_b' => $variantB->getDetailedStats(),
        ];

        // Get results timeline
        $resultsTimeline = KeywordABTestResult::where('ab_test_id', $test->id)
            ->selectRaw('DATE(created_at) as date, variant_served, COUNT(*) as count')
            ->groupBy('date', 'variant_served')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        return view('admin.line-bot.keywords.ab-tests.show', [
            'test' => $test,
            'summary' => $summary,
            'comparison' => $comparison,
            'resultsTimeline' => $resultsTimeline,
        ]);
    }

    /**
     * แสดงหน้าแก้ไข A/B test
     *
     * @param KeywordABTest $test
     * @return View
     */
    public function edit(KeywordABTest $test): View
    {
        $test->load('variants');

        return view('admin.line-bot.keywords.ab-tests.edit', [
            'test' => $test,
            'criteria_options' => [
                'conversion_rate' => 'Conversion Rate (อัตราการแปลง)',
                'response_time' => 'Response Time (เวลาตอบกลับ)',
                'satisfaction' => 'Satisfaction (ความพึงพอใจ)',
                'interaction_rate' => 'Interaction Rate (อัตราการโต้ตอบ)',
            ],
        ]);
    }

    /**
     * อัพเดท A/B test
     *
     * @param Request $request
     * @param KeywordABTest $test
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, KeywordABTest $test)
    {
        $validated = $request->validate([
            'test_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'minimum_samples' => 'integer|min:10',
        ]);

        $test->update($validated);

        return redirect()
            ->route('admin.line-bot.keywords.ab-tests.show', $test)
            ->with('success', 'อัพเดท A/B test สำเร็จ');
    }

    /**
     * เริ่มทดสอบ
     *
     * @param KeywordABTest $test
     * @return JsonResponse
     */
    public function start(KeywordABTest $test): JsonResponse
    {
        if ($test->status !== 'planning') {
            return response()->json([
                'success' => false,
                'message' => 'สามารถเริ่มทดสอบได้เฉพาะ tests ที่อยู่ในสถานะ planning',
            ], 422);
        }

        $test = $this->abTestService->startTest($test);

        return response()->json([
            'success' => true,
            'message' => 'เริ่มการทดสอบสำเร็จ',
            'test' => $this->abTestService->getTestSummary($test),
        ]);
    }

    /**
     * หยุดทดสอบชั่วคราว
     *
     * @param Request $request
     * @param KeywordABTest $test
     * @return JsonResponse
     */
    public function pause(Request $request, KeywordABTest $test): JsonResponse
    {
        if ($test->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'สามารถหยุดได้เฉพาะ tests ที่ active',
            ], 422);
        }

        $reason = $request->get('reason', '');
        $test = $this->abTestService->pauseTest($test, $reason);

        return response()->json([
            'success' => true,
            'message' => 'หยุดการทดสอบสำเร็จ',
            'test' => $this->abTestService->getTestSummary($test),
        ]);
    }

    /**
     * จบการทดสอบ
     *
     * @param KeywordABTest $test
     * @return JsonResponse
     */
    public function complete(KeywordABTest $test): JsonResponse
    {
        if ($test->status !== 'active' && $test->status !== 'paused') {
            return response()->json([
                'success' => false,
                'message' => 'สามารถจบได้เฉพาะ tests ที่กำลังดำเนินการ',
            ], 422);
        }

        $test = $this->abTestService->completeTest($test);

        return response()->json([
            'success' => true,
            'message' => 'จบการทดสอบสำเร็จ',
            'test' => $this->abTestService->getTestSummary($test),
        ]);
    }

    /**
     * ยกเลิกการทดสอบ
     *
     * @param KeywordABTest $test
     * @return JsonResponse
     */
    public function cancel(KeywordABTest $test): JsonResponse
    {
        if (in_array($test->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยกเลิก test ที่จบแล้วหรือยกเลิกแล้ว',
            ], 422);
        }

        $test->update(['status' => 'cancelled']);

        activity()
            ->performedOn($test)
            ->log('ยกเลิกการทดสอบ');

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการทดสอบสำเร็จ',
        ]);
    }

    /**
     * ได้ JSON list tests
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listJson(Request $request): JsonResponse
    {
        $filter = $request->get('filter', 'all');

        $tests = match ($filter) {
            'active' => $this->abTestService->getActiveTests(),
            'completed' => $this->abTestService->getCompletedTests(),
            default => $this->abTestService->getAllTests(),
        };

        return response()->json([
            'success' => true,
            'count' => $tests->count(),
            'data' => $tests,
        ]);
    }

    /**
     * ได้ A/B test statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->abTestService->getDashboardStats();

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * ได้ recommendations
     *
     * @return JsonResponse
     */
    public function recommendations(): JsonResponse
    {
        $recommendations = $this->abTestService->getRecommendations();

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * ได้ chart data สำหรับ variant comparison
     *
     * @param KeywordABTest $test
     * @return JsonResponse
     */
    public function chartData(KeywordABTest $test): JsonResponse
    {
        $variantA = $test->variantA();
        $variantB = $test->variantB();

        return response()->json([
            'success' => true,
            'data' => [
                'variant_a' => [
                    'label' => 'Variant A',
                    'impressions' => $variantA->impressions,
                    'interactions' => $variantA->interactions,
                    'conversion_rate' => round($variantA->conversion_rate, 2),
                    'avg_response_time' => round($variantA->avg_response_time, 2),
                    'satisfaction_score' => round($variantA->satisfaction_score, 2),
                ],
                'variant_b' => [
                    'label' => 'Variant B',
                    'impressions' => $variantB->impressions,
                    'interactions' => $variantB->interactions,
                    'conversion_rate' => round($variantB->conversion_rate, 2),
                    'avg_response_time' => round($variantB->avg_response_time, 2),
                    'satisfaction_score' => round($variantB->satisfaction_score, 2),
                ],
            ],
        ]);
    }

    /**
     * ได้ timeline chart data
     *
     * @param KeywordABTest $test
     * @return JsonResponse
     */
    public function timelineData(KeywordABTest $test): JsonResponse
    {
        $timeline = KeywordABTestResult::where('ab_test_id', $test->id)
            ->selectRaw('DATE(created_at) as date, variant_served, COUNT(*) as count')
            ->groupBy('date', 'variant_served')
            ->orderBy('date')
            ->get();

        $variantAData = [];
        $variantBData = [];
        $dates = [];

        foreach ($timeline as $item) {
            if (!in_array($item->date, $dates)) {
                $dates[] = $item->date;
            }

            if ($item->variant_served === 'variant_a') {
                $variantAData[$item->date] = $item->count;
            } else {
                $variantBData[$item->date] = $item->count;
            }
        }

        return response()->json([
            'success' => true,
            'labels' => $dates,
            'variant_a' => array_map(fn ($date) => $variantAData[$date] ?? 0, $dates),
            'variant_b' => array_map(fn ($date) => $variantBData[$date] ?? 0, $dates),
        ]);
    }

    /**
     * Apply winner variant ให้เป็น keyword หลัก
     *
     * @param KeywordABTest $test
     * @return JsonResponse
     */
    public function applyWinner(KeywordABTest $test): JsonResponse
    {
        if (!$test->winner || $test->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'สามารถนำไปใช้ได้เฉพาะ tests ที่จบแล้วและมี winner',
            ], 422);
        }

        try {
            $keyword = $this->abTestService->applyWinner($test);

            return response()->json([
                'success' => true,
                'message' => 'นำไปใช้ variant ผู้ชนะสำเร็จ',
                'keyword' => $keyword,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ลบ A/B test
     *
     * @param KeywordABTest $test
     * @return JsonResponse
     */
    public function destroy(KeywordABTest $test): JsonResponse
    {
        $test->delete();

        activity()
            ->performedOn($test)
            ->log('ลบ A/B test');

        return response()->json([
            'success' => true,
            'message' => 'ลบการทดสอบสำเร็จ',
        ]);
    }
}
