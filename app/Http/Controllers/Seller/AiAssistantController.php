<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AiAssistantController
 *
 * จัดการ AI Sales Assistant สำหรับร้านค้า
 */
class AiAssistantController extends Controller
{
    /**
     * หน้าหลัก AI Assistant Dashboard
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $store = $request->user()->vendorStore;

        // ดึง AI Bot ที่ร้านค้าใช้งาน (ถ้ามี)
        $aiBot = AiBotProfile::where('user_id', $request->user()->id)
            ->where('bot_type', 'sales')
            ->first();

        // สถิติการสนทนา
        $stats = [
            'total_conversations' => 0,
            'today_conversations' => 0,
            'avg_response_time' => 0,
            'conversion_rate' => 0,
        ];

        if ($aiBot) {
            $stats['total_conversations'] = AiConversation::where('bot_id', $aiBot->id)->count();
            $stats['today_conversations'] = AiConversation::where('bot_id', $aiBot->id)
                ->whereDate('created_at', today())
                ->count();
        }

        return view('seller.ai-assistant.index', [
            'store' => $store,
            'aiBot' => $aiBot,
            'stats' => $stats,
            'pageTitle' => 'AI ผู้ช่วยขาย',
        ]);
    }

    /**
     * ตั้งค่า AI Assistant
     *
     * @param Request $request
     * @return View
     */
    public function settings(Request $request): View
    {
        $store = $request->user()->vendorStore;

        $aiBot = AiBotProfile::where('user_id', $request->user()->id)
            ->where('bot_type', 'sales')
            ->first();

        return view('seller.ai-assistant.settings', [
            'store' => $store,
            'aiBot' => $aiBot,
            'pageTitle' => 'ตั้งค่า AI ผู้ช่วยขาย',
        ]);
    }

    /**
     * อัพเดทการตั้งค่า AI
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'bot_name' => 'required|string|max:100',
            'greeting_message' => 'nullable|string|max:500',
            'system_prompt' => 'nullable|string|max:2000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'is_active' => 'boolean',
        ]);

        $aiBot = AiBotProfile::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'bot_type' => 'sales',
            ],
            [
                'name' => $validated['bot_name'],
                'greeting_message' => $validated['greeting_message'] ?? 'สวัสดีค่ะ! มีอะไรให้ช่วยไหมคะ?',
                'system_prompt' => $validated['system_prompt'] ?? 'คุณเป็น AI ผู้ช่วยขายที่เป็นมิตรและช่วยเหลือลูกค้าในการเลือกซื้อสินค้า',
                'temperature' => $validated['temperature'] ?? 0.7,
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return redirect()
            ->route('seller.ai-assistant.settings')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ!');
    }

    /**
     * แสดงรายการการสนทนา
     *
     * @param Request $request
     * @return View
     */
    public function conversations(Request $request): View
    {
        $aiBot = AiBotProfile::where('user_id', $request->user()->id)
            ->where('bot_type', 'sales')
            ->first();

        $conversations = collect();

        if ($aiBot) {
            $conversations = AiConversation::where('bot_id', $aiBot->id)
                ->with('user')
                ->latest()
                ->paginate(20);
        }

        return view('seller.ai-assistant.conversations', [
            'conversations' => $conversations,
            'pageTitle' => 'ประวัติการสนทนา',
        ]);
    }

    /**
     * แสดงรายละเอียดการสนทนา
     *
     * @param Request $request
     * @param AiConversation $conversation
     * @return View
     */
    public function showConversation(Request $request, AiConversation $conversation): View
    {
        // ตรวจสอบสิทธิ์
        $aiBot = AiBotProfile::where('user_id', $request->user()->id)
            ->where('bot_type', 'sales')
            ->first();

        if (!$aiBot || $conversation->bot_id !== $aiBot->id) {
            abort(403, 'ไม่มีสิทธิ์ดูการสนทนานี้');
        }

        $conversation->load('messages');

        return view('seller.ai-assistant.conversation-detail', [
            'conversation' => $conversation,
            'pageTitle' => 'รายละเอียดการสนทนา',
        ]);
    }

    /**
     * Analytics ของ AI
     *
     * @param Request $request
     * @return View
     */
    public function analytics(Request $request): View
    {
        $aiBot = AiBotProfile::where('user_id', $request->user()->id)
            ->where('bot_type', 'sales')
            ->first();

        $analytics = [
            'daily_conversations' => [],
            'popular_topics' => [],
            'satisfaction_rate' => 0,
        ];

        if ($aiBot) {
            // ดึง conversations ของ 30 วันล่าสุด
            $analytics['daily_conversations'] = AiConversation::where('bot_id', $aiBot->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();
        }

        return view('seller.ai-assistant.analytics', [
            'aiBot' => $aiBot,
            'analytics' => $analytics,
            'pageTitle' => 'สถิติ AI ผู้ช่วยขาย',
        ]);
    }

    /**
     * ทดสอบ Bot
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testBot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        // จำลองการตอบกลับ (ในระบบจริงจะเรียก AI API)
        $response = 'สวัสดีค่ะ! ขอบคุณที่สนใจสินค้าของเรา ฉันยินดีช่วยเหลือคุณในการเลือกสินค้าค่ะ';

        return response()->json([
            'success' => true,
            'response' => $response,
        ]);
    }
}
