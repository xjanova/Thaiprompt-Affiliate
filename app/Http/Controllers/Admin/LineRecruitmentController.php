<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineBotAiSetting;
use App\Models\LineBotConversation;
use App\Models\LineBotKnowledgeBase;
use App\Models\LineBotMessage;
use App\Models\LineRecruitmentTopicBoundary;
use App\Services\LineBotAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * LINE Recruitment Controller
 *
 * จัดการระบบรับสมัครสมาชิกผ่าน LINE AI
 * - Dashboard แสดงสถิติ
 * - จัดการ AI Settings
 * - ดูประวัติการสนทนา
 * - จัดการ Topic Boundaries
 * - จัดการ Knowledge Base
 */
class LineRecruitmentController extends Controller
{
    /**
     * แสดง Dashboard หลักของระบบรับสมัคร
     */
    public function index(): View
    {
        // สถิติรวม
        $stats = $this->getRecruitmentStats();

        // AI Settings ที่ใช้สำหรับรับสมัคร
        $aiSettings = LineBotAiSetting::with(['knowledgeBases', 'topicBoundaries'])
            ->forRecruitment()
            ->get();

        // การสนทนาล่าสุด
        $recentConversations = LineBotConversation::with(['aiSetting'])
            ->whereHas('aiSetting', function ($q) {
                $q->where('use_for_recruitment', true);
            })
            ->latest()
            ->take(10)
            ->get();

        // กราฟการสนทนาต่อวัน (7 วันล่าสุด)
        $conversationsChart = $this->getConversationsChart();

        return view('admin.line-recruitment.index', compact(
            'stats',
            'aiSettings',
            'recentConversations',
            'conversationsChart'
        ));
    }

    /**
     * หน้าตั้งค่าระบบรับสมัคร
     */
    public function settings(): View
    {
        $aiSettings = LineBotAiSetting::with(['knowledgeBases', 'topicBoundaries'])
            ->get();

        $providers = [
            'openai' => 'OpenAI (ChatGPT)',
            'deepseek' => 'DeepSeek',
            'anthropic' => 'Anthropic (Claude)',
            'gemini' => 'Google Gemini',
            'custom' => 'Custom API',
        ];

        $responseStyles = [
            'friendly' => 'เป็นกันเอง',
            'professional' => 'มืออาชีพ',
            'casual' => 'สบายๆ',
        ];

        return view('admin.line-recruitment.settings', compact(
            'aiSettings',
            'providers',
            'responseStyles'
        ));
    }

    /**
     * บันทึกการตั้งค่าระบบรับสมัคร
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request, int $id)
    {
        $setting = LineBotAiSetting::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:openai,deepseek,anthropic,gemini,custom',
            'api_key' => 'nullable|string',
            'api_endpoint' => 'nullable|string|url',
            'model' => 'nullable|string',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:1',
            'system_prompt' => 'nullable|string',
            'recruitment_system_prompt' => 'nullable|string',
            'response_style' => 'required|in:friendly,professional,casual',
            'greeting_message' => 'nullable|string',
            'max_conversation_turns' => 'integer|min:1|max:100',
            'max_turns_message' => 'nullable|string',
            'off_topic_message' => 'nullable|string',
            'use_for_recruitment' => 'boolean',
            'enable_topic_filtering' => 'boolean',
            'collect_user_info' => 'boolean',
            'required_fields' => 'nullable|array',
            'is_active' => 'boolean',
            'enable_fallback' => 'boolean',
            'fallback_message' => 'nullable|string',
            'enable_conversation_history' => 'boolean',
            'conversation_memory_limit' => 'integer|min:1',
        ]);

        // ถ้าไม่ได้ส่ง api_key ใหม่มา ให้ใช้อันเดิม
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        // ถ้าเปิดใช้งานเป็น active ให้ปิด active ตัวอื่นก่อน
        if ($request->is_active && ! $setting->is_active) {
            LineBotAiSetting::where('is_active', true)->update(['is_active' => false]);
        }

        $setting->update($validated);
        LineBotAiSetting::clearCache();
        LineBotAiSetting::clearRecruitmentCache();

        return redirect()->route('admin.line-recruitment.settings')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * แสดงรายการการสนทนาทั้งหมด
     */
    public function conversations(Request $request): View
    {
        $query = LineBotConversation::with(['aiSetting', 'messages' => function ($q) {
            $q->latest()->take(1);
        }]);

        // Filter by AI setting
        if ($request->filled('ai_setting_id')) {
            $query->where('ai_setting_id', $request->ai_setting_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by LINE user ID
        if ($request->filled('search')) {
            $query->where('line_user_id', 'like', '%'.$request->search.'%');
        }

        $conversations = $query->latest()->paginate(20);

        $aiSettings = LineBotAiSetting::forRecruitment()->get();

        // สถิติ
        $stats = [
            'total' => LineBotConversation::count(),
            'today' => LineBotConversation::whereDate('created_at', today())->count(),
            'active' => LineBotConversation::where('status', 'active')->count(),
            'total_messages' => LineBotMessage::count(),
        ];

        return view('admin.line-recruitment.conversations.index', compact(
            'conversations',
            'aiSettings',
            'stats'
        ));
    }

    /**
     * แสดงรายละเอียดการสนทนา
     */
    public function conversationDetail(int $id): View
    {
        $conversation = LineBotConversation::with(['aiSetting', 'messages' => function ($q) {
            $q->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        return view('admin.line-recruitment.conversations.show', compact('conversation'));
    }

    /**
     * ลบการสนทนา
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteConversation(int $id)
    {
        $conversation = LineBotConversation::findOrFail($id);
        $conversation->messages()->delete();
        $conversation->delete();

        return redirect()->route('admin.line-recruitment.conversations')
            ->with('success', 'ลบการสนทนาสำเร็จ');
    }

    /**
     * แสดงหน้า Topic Boundaries
     */
    public function topicBoundaries(int $aiSettingId): View
    {
        $aiSetting = LineBotAiSetting::with('topicBoundaries')->findOrFail($aiSettingId);

        $allowedTopics = $aiSetting->topicBoundaries()
            ->where('type', 'allowed')
            ->orderBy('priority', 'desc')
            ->get();

        $blockedTopics = $aiSetting->topicBoundaries()
            ->where('type', 'blocked')
            ->orderBy('priority', 'desc')
            ->get();

        return view('admin.line-recruitment.topic-boundaries.index', compact(
            'aiSetting',
            'allowedTopics',
            'blockedTopics'
        ));
    }

    /**
     * เพิ่ม Topic Boundary ใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeTopicBoundary(Request $request, int $aiSettingId)
    {
        $aiSetting = LineBotAiSetting::findOrFail($aiSettingId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:allowed,blocked',
            'keywords' => 'nullable|string',
            'response_message' => 'nullable|string',
            'example_phrases' => 'nullable|string',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // แปลง keywords และ example_phrases จาก string เป็น array
        $validated['keywords'] = ! empty($validated['keywords'])
            ? array_map('trim', explode(',', $validated['keywords']))
            : null;

        $validated['example_phrases'] = ! empty($validated['example_phrases'])
            ? array_map('trim', explode("\n", $validated['example_phrases']))
            : null;

        $aiSetting->topicBoundaries()->create($validated);

        return redirect()->route('admin.line-recruitment.topic-boundaries', $aiSettingId)
            ->with('success', 'เพิ่มขอบเขตหัวข้อสำเร็จ');
    }

    /**
     * อัปเดต Topic Boundary
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTopicBoundary(Request $request, int $aiSettingId, int $topicId)
    {
        $topic = LineRecruitmentTopicBoundary::where('ai_setting_id', $aiSettingId)
            ->findOrFail($topicId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:allowed,blocked',
            'keywords' => 'nullable|string',
            'response_message' => 'nullable|string',
            'example_phrases' => 'nullable|string',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // แปลง keywords และ example_phrases
        $validated['keywords'] = ! empty($validated['keywords'])
            ? array_map('trim', explode(',', $validated['keywords']))
            : null;

        $validated['example_phrases'] = ! empty($validated['example_phrases'])
            ? array_map('trim', explode("\n", $validated['example_phrases']))
            : null;

        $topic->update($validated);

        return redirect()->route('admin.line-recruitment.topic-boundaries', $aiSettingId)
            ->with('success', 'อัปเดตขอบเขตหัวข้อสำเร็จ');
    }

    /**
     * ลบ Topic Boundary
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteTopicBoundary(int $aiSettingId, int $topicId)
    {
        $topic = LineRecruitmentTopicBoundary::where('ai_setting_id', $aiSettingId)
            ->findOrFail($topicId);

        $topic->delete();

        return redirect()->route('admin.line-recruitment.topic-boundaries', $aiSettingId)
            ->with('success', 'ลบขอบเขตหัวข้อสำเร็จ');
    }

    /**
     * แสดงหน้า Knowledge Base
     */
    public function knowledgeBase(int $aiSettingId): View
    {
        $aiSetting = LineBotAiSetting::with('knowledgeBases')->findOrFail($aiSettingId);
        $knowledgeBases = $aiSetting->knowledgeBases()->orderBy('priority', 'desc')->get();

        return view('admin.line-recruitment.knowledge-base.index', compact(
            'aiSetting',
            'knowledgeBases'
        ));
    }

    /**
     * เพิ่ม Knowledge Base ใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeKnowledgeBase(Request $request, int $aiSettingId)
    {
        $aiSetting = LineBotAiSetting::findOrFail($aiSettingId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:url,internal,file,text',
            'source' => 'nullable|string|url',
            'content' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,txt,doc,docx,csv,json|max:10240',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // จัดการไฟล์อัปโหลด
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('knowledge-bases', 'public');
            $validated['file_path'] = $path;
            $validated['file_type'] = $request->file('file')->getClientOriginalExtension();
        }

        $aiSetting->knowledgeBases()->create($validated);

        return redirect()->route('admin.line-recruitment.knowledge-base', $aiSettingId)
            ->with('success', 'เพิ่มแหล่งข้อมูลสำเร็จ');
    }

    /**
     * อัปเดต Knowledge Base
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateKnowledgeBase(Request $request, int $aiSettingId, int $knowledgeId)
    {
        $knowledge = LineBotKnowledgeBase::where('ai_setting_id', $aiSettingId)
            ->findOrFail($knowledgeId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:url,internal,file,text',
            'source' => 'nullable|string|url',
            'content' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,txt,doc,docx,csv,json|max:10240',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // จัดการไฟล์ใหม่ถ้ามี
        if ($request->hasFile('file')) {
            // ลบไฟล์เก่า
            if ($knowledge->file_path) {
                Storage::disk('public')->delete($knowledge->file_path);
            }

            $path = $request->file('file')->store('knowledge-bases', 'public');
            $validated['file_path'] = $path;
            $validated['file_type'] = $request->file('file')->getClientOriginalExtension();
        }

        $knowledge->update($validated);

        return redirect()->route('admin.line-recruitment.knowledge-base', $aiSettingId)
            ->with('success', 'อัปเดตแหล่งข้อมูลสำเร็จ');
    }

    /**
     * ลบ Knowledge Base
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteKnowledgeBase(int $aiSettingId, int $knowledgeId)
    {
        $knowledge = LineBotKnowledgeBase::where('ai_setting_id', $aiSettingId)
            ->findOrFail($knowledgeId);

        // ลบไฟล์ถ้ามี
        if ($knowledge->file_path) {
            Storage::disk('public')->delete($knowledge->file_path);
        }

        $knowledge->delete();

        return redirect()->route('admin.line-recruitment.knowledge-base', $aiSettingId)
            ->with('success', 'ลบแหล่งข้อมูลสำเร็จ');
    }

    /**
     * ทดสอบ AI Connection
     */
    public function testAi(Request $request, int $id): JsonResponse
    {
        $setting = LineBotAiSetting::findOrFail($id);
        $aiService = new LineBotAiService($setting, true);

        $testMessage = $request->input('message', 'สวัสดีค่ะ อยากสมัครสมาชิก');

        try {
            $response = $aiService->generateResponse($testMessage);

            return response()->json([
                'success' => true,
                'message' => $response['message'],
                'provider' => $response['provider'],
                'tokens_used' => $response['tokens_used'],
                'response_time' => round($response['response_time'], 2),
                'filtered' => $response['filtered'] ?? false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ทดสอบ Topic Filtering
     */
    public function testTopicFilter(Request $request, int $id): JsonResponse
    {
        $setting = LineBotAiSetting::findOrFail($id);
        $aiService = new LineBotAiService($setting, true);

        $message = $request->input('message', '');

        $result = $aiService->checkTopicBoundaries($message);

        return response()->json([
            'success' => true,
            'allowed' => $result['allowed'],
            'topic_name' => $result['topic_name'],
            'response' => $result['response'],
        ]);
    }

    /**
     * Export การสนทนาเป็น CSV
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportConversations(Request $request)
    {
        $conversations = LineBotConversation::with(['messages', 'aiSetting'])
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="conversations_'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($conversations) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'ID',
                'LINE User ID',
                'AI Setting',
                'Status',
                'จำนวนข้อความ',
                'วันที่เริ่ม',
                'ข้อความล่าสุด',
            ]);

            foreach ($conversations as $conv) {
                fputcsv($file, [
                    $conv->id,
                    $conv->line_user_id,
                    $conv->aiSetting->name ?? 'N/A',
                    $conv->status,
                    $conv->message_count,
                    $conv->created_at->format('Y-m-d H:i:s'),
                    $conv->last_message_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * รับสถิติการรับสมัคร
     */
    private function getRecruitmentStats(): array
    {
        return [
            'total_conversations' => LineBotConversation::whereHas('aiSetting', function ($q) {
                $q->where('use_for_recruitment', true);
            })->count(),

            'today_conversations' => LineBotConversation::whereHas('aiSetting', function ($q) {
                $q->where('use_for_recruitment', true);
            })->whereDate('created_at', today())->count(),

            'active_conversations' => LineBotConversation::whereHas('aiSetting', function ($q) {
                $q->where('use_for_recruitment', true);
            })->where('status', 'active')->count(),

            'total_messages' => LineBotMessage::whereHas('conversation.aiSetting', function ($q) {
                $q->where('use_for_recruitment', true);
            })->count(),

            'today_messages' => LineBotMessage::whereHas('conversation.aiSetting', function ($q) {
                $q->where('use_for_recruitment', true);
            })->whereDate('created_at', today())->count(),

            'ai_settings_count' => LineBotAiSetting::where('use_for_recruitment', true)->count(),

            'active_ai_settings' => LineBotAiSetting::where('use_for_recruitment', true)
                ->where('is_active', true)->count(),

            'total_knowledge_bases' => LineBotKnowledgeBase::whereHas('aiSetting', function ($q) {
                $q->where('use_for_recruitment', true);
            })->count(),

            'blocked_topics_triggered' => LineRecruitmentTopicBoundary::where('type', 'blocked')
                ->sum('hit_count'),
        ];
    }

    /**
     * รับข้อมูลกราฟการสนทนา
     */
    private function getConversationsChart(): array
    {
        $data = LineBotConversation::whereHas('aiSetting', function ($q) {
            $q->where('use_for_recruitment', true);
        })
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        // สร้างข้อมูล 7 วันย้อนหลัง
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');

            $found = $data->firstWhere('date', $date);
            $values[] = $found ? $found->count : 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
