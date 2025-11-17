<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LineBotKeyword;
use Illuminate\Http\Request;

/**
 * LINE Bot Keyword API Controller
 *
 * API endpoints สำหรับจัดการ keywords
 * Authentication: Bearer token (Sanctum)
 */
class LineBotKeywordController extends Controller
{
    /**
     * ดึงรายการ keywords ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = LineBotKeyword::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('keyword', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        // Sort
        $sort = $request->input('sort', '-priority');
        $sortBy = ltrim($sort, '-');
        $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDirection);

        // Paginate
        $keywords = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $keywords->items(),
            'pagination' => [
                'total' => $keywords->total(),
                'count' => $keywords->count(),
                'per_page' => $keywords->perPage(),
                'current_page' => $keywords->currentPage(),
                'last_page' => $keywords->lastPage(),
            ],
            'message' => 'Keywords ดึงข้อมูลสำเร็จ',
        ]);
    }

    /**
     * ดึงรายละเอียด keyword
     *
     * @param LineBotKeyword $keyword
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(LineBotKeyword $keyword)
    {
        return response()->json([
            'success' => true,
            'data' => $keyword,
            'message' => 'ดึงข้อมูล Keyword สำเร็จ',
        ]);
    }

    /**
     * สร้าง keyword ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:100|unique:line_bot_keywords',
            'description' => 'nullable|string|max:500',
            'trigger_words' => 'required|array|min:1',
            'trigger_words.*' => 'string',
            'response_type' => 'required|in:text,flex_message,quick_reply',
            'response_text' => 'required_if:response_type,text,quick_reply|string|nullable',
            'response_flex_json' => 'required_if:response_type,flex_message|json|nullable',
            'quick_reply_options' => 'required_if:response_type,quick_reply|json|nullable',
            'category' => 'required|in:faq,support,product,custom',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $keyword = LineBotKeyword::create($validated);

        return response()->json([
            'success' => true,
            'data' => $keyword,
            'message' => "สร้าง Keyword '{$keyword->keyword}' สำเร็จ",
        ], 201);
    }

    /**
     * อัปเดต keyword
     *
     * @param Request $request
     * @param LineBotKeyword $keyword
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, LineBotKeyword $keyword)
    {
        $validated = $request->validate([
            'keyword' => "required|string|max:100|unique:line_bot_keywords,keyword,{$keyword->id}",
            'description' => 'nullable|string|max:500',
            'trigger_words' => 'required|array|min:1',
            'trigger_words.*' => 'string',
            'response_type' => 'required|in:text,flex_message,quick_reply',
            'response_text' => 'required_if:response_type,text,quick_reply|string|nullable',
            'response_flex_json' => 'required_if:response_type,flex_message|json|nullable',
            'quick_reply_options' => 'required_if:response_type,quick_reply|json|nullable',
            'category' => 'required|in:faq,support,product,custom',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $keyword->update($validated);

        return response()->json([
            'success' => true,
            'data' => $keyword->fresh(),
            'message' => "อัปเดต Keyword '{$keyword->keyword}' สำเร็จ",
        ]);
    }

    /**
     * ลบ keyword
     *
     * @param LineBotKeyword $keyword
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(LineBotKeyword $keyword)
    {
        $keywordName = $keyword->keyword;
        $keyword->delete();

        return response()->json([
            'success' => true,
            'message' => "ลบ Keyword '{$keywordName}' สำเร็จ",
        ]);
    }

    /**
     * ทดสอบ keyword
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $keywords = LineBotKeyword::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        $matched = $keywords->first(function ($keyword) use ($validated) {
            return $keyword->matches($validated['message']);
        });

        if ($matched) {
            return response()->json([
                'success' => true,
                'matched' => true,
                'keyword' => $matched->keyword,
                'category' => $matched->category,
                'priority' => $matched->priority,
                'response_type' => $matched->response_type,
                'response' => $matched->getFormattedResponse(),
                'message' => 'พบ Keyword ที่ตรงกัน',
            ]);
        }

        return response()->json([
            'success' => true,
            'matched' => false,
            'message' => 'ไม่พบ Keyword - จะใช้ AI Fallback',
        ]);
    }

    /**
     * ดึง keywords สำหรับ Hybrid Bot
     *
     * สำหรับใช้ใน LINE Webhook
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activeKeywords()
    {
        $keywords = LineBotKeyword::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get()
            ->map(function ($keyword) {
                return [
                    'id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'trigger_words' => $keyword->trigger_words,
                    'response_type' => $keyword->response_type,
                    'response' => $keyword->getFormattedResponse(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $keywords,
            'count' => $keywords->count(),
            'message' => 'ดึง Active Keywords สำเร็จ',
        ]);
    }

    /**
     * Statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $total = LineBotKeyword::count();
        $active = LineBotKeyword::where('is_active', true)->count();
        $byCategory = LineBotKeyword::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');
        $byResponseType = LineBotKeyword::selectRaw('response_type, COUNT(*) as count')
            ->groupBy('response_type')
            ->pluck('count', 'response_type');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $total - $active,
                'by_category' => $byCategory,
                'by_response_type' => $byResponseType,
            ],
            'message' => 'ดึงสถิติสำเร็จ',
        ]);
    }
}
