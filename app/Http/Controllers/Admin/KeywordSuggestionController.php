<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineBotKeyword;
use App\Services\KeywordSuggestionService;
use Illuminate\Http\Request;

/**
 * Keyword Suggestion Controller
 *
 * จัดการ keyword suggestions จาก AI analysis
 */
class KeywordSuggestionController extends Controller
{
    private KeywordSuggestionService $suggestionService;

    public function __construct(KeywordSuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
    }

    /**
     * แสดง suggestions dashboard
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $days = $request->input('days', 30);
        $minFrequency = $request->input('min_frequency', 3);

        $suggestions = $this->suggestionService->getUniqueNewSuggestions($days, $minFrequency);
        $statistics = $this->suggestionService->getStatistics($days);
        $recommendations = $this->suggestionService->getRecommendations();

        return view('admin.line-bot.keywords.suggestions', [
            'suggestions' => $suggestions,
            'statistics' => $statistics,
            'recommendations' => $recommendations,
            'days' => $days,
            'minFrequency' => $minFrequency,
            'pageTitle' => 'Keyword Suggestions',
        ]);
    }

    /**
     * ดึง suggestions เป็น JSON
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuggestionsJson(Request $request)
    {
        $days = $request->input('days', 30);
        $minFrequency = $request->input('min_frequency', 3);

        $suggestions = $this->suggestionService->getUniqueNewSuggestions($days, $minFrequency);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'count' => count($suggestions),
            'message' => 'ดึง suggestions สำเร็จ',
        ]);
    }

    /**
     * ดึง statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        $days = $request->input('days', 30);
        $statistics = $this->suggestionService->getStatistics($days);

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'ดึง statistics สำเร็จ',
        ]);
    }

    /**
     * ดึง recommendations
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecommendations()
    {
        $recommendations = $this->suggestionService->getRecommendations();

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'message' => 'ดึง recommendations สำเร็จ',
        ]);
    }

    /**
     * Preview suggestion (ดูตัวอย่าง keyword ที่สร้างจะออกมาเป็นไง)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string',
            'trigger_words' => 'required|array',
            'response_text' => 'nullable|string',
        ]);

        $keyword = new LineBotKeyword();
        $keyword->keyword = $validated['keyword'];
        $keyword->trigger_words = json_encode($validated['trigger_words']);
        $keyword->response_type = 'text';
        $keyword->response_text = $validated['response_text'] ?? 'Default response';
        $keyword->category = 'custom';
        $keyword->priority = 50;

        return response()->json([
            'success' => true,
            'preview' => $keyword->toArray(),
            'message' => 'Preview สำเร็จ',
        ]);
    }

    /**
     * Approve and create keyword from suggestion
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|unique:line_bot_keywords',
            'trigger_words' => 'required|json',
            'response_text' => 'required|string',
            'category' => 'required|in:faq,support,product,custom',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ]);

        // Create keyword
        $keyword = new LineBotKeyword();
        $keyword->keyword = $validated['keyword'];
        $keyword->trigger_words = $validated['trigger_words'];
        $keyword->response_type = 'text';
        $keyword->response_text = $validated['response_text'];
        $keyword->category = $validated['category'];
        $keyword->priority = $validated['priority'];
        $keyword->is_active = $validated['is_active'] ?? true;
        $keyword->save();

        return redirect()
            ->route('admin.line-bot.keywords.index')
            ->with('success', "สร้าง Keyword '{$keyword->keyword}' จาก suggestion สำเร็จ");
    }

    /**
     * Approve multiple suggestions at once
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveBatch(Request $request)
    {
        $validated = $request->validate([
            'suggestions' => 'required|array',
            'suggestions.*.keyword' => 'required|string|unique:line_bot_keywords',
            'suggestions.*.trigger_words' => 'required|json',
            'suggestions.*.response_text' => 'required|string',
            'suggestions.*.category' => 'required|in:faq,support,product,custom',
            'suggestions.*.priority' => 'required|integer|min:1|max:100',
        ]);

        $created = [];
        $errors = [];

        foreach ($validated['suggestions'] as $data) {
            try {
                $keyword = new LineBotKeyword();
                $keyword->keyword = $data['keyword'];
                $keyword->trigger_words = $data['trigger_words'];
                $keyword->response_type = 'text';
                $keyword->response_text = $data['response_text'];
                $keyword->category = $data['category'];
                $keyword->priority = $data['priority'];
                $keyword->is_active = true;
                $keyword->save();

                $created[] = $keyword->keyword;
            } catch (\Exception $e) {
                $errors[] = [
                    'keyword' => $data['keyword'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'created_count' => count($created),
            'errors' => $errors,
            'message' => count($created) . ' keywords สร้างสำเร็จ' .
                        (count($errors) > 0 ? ', ' . count($errors) . ' ข้อผิดพลาด' : ''),
        ]);
    }

    /**
     * Reject/Dismiss suggestion
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request)
    {
        // Note: suggestions มาจาก activity logs ดังนั้นไม่มีการ delete
        // แต่ user สามารถ dismiss ได้โดยการไม่ approve มัน

        return response()->json([
            'success' => true,
            'message' => 'Suggestion dismissed',
        ]);
    }

    /**
     * Get suggestion detail with full context
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string',
        ]);

        $suggestions = $this->suggestionService->getUniqueNewSuggestions();

        $suggestion = collect($suggestions)->firstWhere('keyword', $validated['keyword']);

        if (!$suggestion) {
            return response()->json([
                'success' => false,
                'message' => 'Suggestion not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $suggestion,
            'message' => 'ดึงข้อมูล suggestion สำเร็จ',
        ]);
    }

    /**
     * Refresh suggestions (re-analyze)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $days = $request->input('days', 30);

        // This will re-run the analysis
        $suggestions = $this->suggestionService->getUniqueNewSuggestions($days);
        $statistics = $this->suggestionService->getStatistics($days);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'statistics' => $statistics,
            'message' => 'Refresh suggestions สำเร็จ',
        ]);
    }

    /**
     * Export suggestions as JSON
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $days = $request->input('days', 30);
        $suggestions = $this->suggestionService->getUniqueNewSuggestions($days);

        $data = json_encode($suggestions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $filename = 'keyword-suggestions-' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->streamDownload(
            function () use ($data) {
                echo $data;
            },
            $filename,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
