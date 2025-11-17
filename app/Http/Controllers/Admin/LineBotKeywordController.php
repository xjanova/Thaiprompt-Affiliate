<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineBotKeyword;
use App\Services\LineHybridBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * จัดการ Hybrid Bot Keywords ในระบบ Admin
 *
 * ควบคุมการสร้าง แก้ไข ลบ และทดสอบ keywords สำหรับระบบ Hybrid Bot
 */
class LineBotKeywordController extends Controller
{
    /**
     * แสดงรายการ keywords ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $keywords = LineBotKeyword::with([])
            ->when(request('category'), function ($query) {
                return $query->where('category', request('category'));
            })
            ->when(request('search'), function ($query) {
                return $query->where('keyword', 'like', '%' . request('search') . '%');
            })
            ->orderBy('priority', 'desc')
            ->paginate(15);

        $stats = [
            'total_keywords' => LineBotKeyword::count(),
            'active_keywords' => LineBotKeyword::where('is_active', true)->count(),
            'inactive_keywords' => LineBotKeyword::where('is_active', false)->count(),
            'by_category' => LineBotKeyword::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
        ];

        return view('admin.line-bot.keywords.index', [
            'keywords' => $keywords,
            'stats' => $stats,
            'pageTitle' => 'จัดการ Keywords Hybrid Bot',
            'pageDescription' => 'สร้าง แก้ไข และทดสอบ keywords สำหรับ Hybrid Bot',
        ]);
    }

    /**
     * แสดงฟอร์มสร้าง keyword ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.line-bot.keywords.create', [
            'pageTitle' => 'สร้าง Keyword ใหม่',
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * บันทึก keyword ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:100|unique:line_bot_keywords',
            'description' => 'nullable|string|max:500',
            'trigger_words' => 'required|string',
            'response_type' => 'required|in:text,flex_message,quick_reply',
            'response_text' => 'required_if:response_type,text,quick_reply|string|nullable',
            'response_flex_json' => 'required_if:response_type,flex_message|json|nullable',
            'quick_reply_options' => 'required_if:response_type,quick_reply|json|nullable',
            'category' => 'required|in:faq,support,product,custom',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // แปลง trigger_words จากข้อความเป็น array
        $triggerWords = array_map(
            'trim',
            explode(',', $request->input('trigger_words'))
        );
        $validated['trigger_words'] = $triggerWords;

        // Decode JSON fields
        if ($request->input('response_type') === 'flex_message' && $request->input('response_flex_json')) {
            $validated['response_flex_json'] = json_decode($request->input('response_flex_json'), true);
        }

        if ($request->input('response_type') === 'quick_reply' && $request->input('quick_reply_options')) {
            $validated['quick_reply_options'] = json_decode($request->input('quick_reply_options'), true);
        }

        $keyword = LineBotKeyword::create($validated);

        Log::info('สร้าง Keyword ใหม่', [
            'keyword_id' => $keyword->id,
            'keyword' => $keyword->keyword,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.line-bot.keywords.index')
            ->with('success', "สร้าง Keyword '{$keyword->keyword}' สำเร็จ");
    }

    /**
     * แสดงฟอร์มแก้ไข keyword
     *
     * @param LineBotKeyword $keyword
     * @return \Illuminate\View\View
     */
    public function edit(LineBotKeyword $keyword)
    {
        return view('admin.line-bot.keywords.edit', [
            'keyword' => $keyword,
            'pageTitle' => "แก้ไข Keyword: {$keyword->keyword}",
            'categories' => $this->getCategories(),
            'triggerWordsText' => implode(', ', $keyword->trigger_words ?? []),
        ]);
    }

    /**
     * อัปเดต keyword
     *
     * @param Request $request
     * @param LineBotKeyword $keyword
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, LineBotKeyword $keyword)
    {
        $validated = $request->validate([
            'keyword' => "required|string|max:100|unique:line_bot_keywords,keyword,{$keyword->id}",
            'description' => 'nullable|string|max:500',
            'trigger_words' => 'required|string',
            'response_type' => 'required|in:text,flex_message,quick_reply',
            'response_text' => 'required_if:response_type,text,quick_reply|string|nullable',
            'response_flex_json' => 'required_if:response_type,flex_message|json|nullable',
            'quick_reply_options' => 'required_if:response_type,quick_reply|json|nullable',
            'category' => 'required|in:faq,support,product,custom',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // แปลง trigger_words
        $triggerWords = array_map(
            'trim',
            explode(',', $request->input('trigger_words'))
        );
        $validated['trigger_words'] = $triggerWords;

        // Decode JSON fields
        if ($request->input('response_type') === 'flex_message' && $request->input('response_flex_json')) {
            $validated['response_flex_json'] = json_decode($request->input('response_flex_json'), true);
        } else {
            $validated['response_flex_json'] = null;
        }

        if ($request->input('response_type') === 'quick_reply' && $request->input('quick_reply_options')) {
            $validated['quick_reply_options'] = json_decode($request->input('quick_reply_options'), true);
        } else {
            $validated['quick_reply_options'] = null;
        }

        $keyword->update($validated);

        Log::info('แก้ไข Keyword', [
            'keyword_id' => $keyword->id,
            'keyword' => $keyword->keyword,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.line-bot.keywords.index')
            ->with('success', "อัปเดต Keyword '{$keyword->keyword}' สำเร็จ");
    }

    /**
     * ลบ keyword
     *
     * @param LineBotKeyword $keyword
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(LineBotKeyword $keyword)
    {
        $keywordName = $keyword->keyword;
        $keyword->delete();

        Log::info('ลบ Keyword', [
            'keyword' => $keywordName,
            'deleted_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.line-bot.keywords.index')
            ->with('success', "ลบ Keyword '{$keywordName}' สำเร็จ");
    }

    /**
     * ทดสอบ keyword ด้วยข้อความ
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|max:500',
            ]);

            $hybridBot = app(LineHybridBotService::class);

            // ตรวจสอบ keyword
            $keywords = LineBotKeyword::where('is_active', true)
                ->orderBy('priority', 'desc')
                ->get();

            $matchedKeyword = null;
            $messageText = $validated['message'];

            foreach ($keywords as $keyword) {
                if ($keyword->matches($messageText)) {
                    $matchedKeyword = $keyword;
                    break;
                }
            }

            if ($matchedKeyword) {
                return response()->json([
                    'success' => true,
                    'matched' => true,
                    'keyword' => $matchedKeyword->keyword,
                    'category' => $matchedKeyword->category,
                    'priority' => $matchedKeyword->priority,
                    'response_type' => $matchedKeyword->response_type,
                    'response_text' => $matchedKeyword->response_text,
                    'trigger_words' => $matchedKeyword->trigger_words,
                    'message' => "พบ Keyword '{$matchedKeyword->keyword}' ✓",
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'matched' => false,
                    'message' => 'ไม่พบ Keyword - จะส่งไปให้ AI',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ข้อผิดพลาดในการทดสอบ keyword', [
                'error' => $e->getMessage(),
                'message' => $request->input('message'),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงข้อมูลสำหรับการแสดงผล
     *
     * @return array
     */
    private function getCategories(): array
    {
        return [
            'faq' => 'FAQ (คำถามทั่วไป)',
            'support' => 'Support (ช่วยเหลือ)',
            'product' => 'Product (สินค้า)',
            'custom' => 'Custom (อื่นๆ)',
        ];
    }
}
