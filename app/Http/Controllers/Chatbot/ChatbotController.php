<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Chatbot Controller
 *
 * Frontend controller สำหรับหน้าจัดการบอท
 */
class ChatbotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard - รายการบอททั้งหมด
     */
    public function index()
    {
        $user = Auth::user();

        $bots = AiBotProfile::where('owner_id', $user->id)
            ->with(['provider', 'model'])
            ->withCount(['activeRentals', 'conversations'])
            ->latest()
            ->paginate(12);

        $stats = [
            'total_bots' => AiBotProfile::where('owner_id', $user->id)->count(),
            'active_bots' => AiBotProfile::where('owner_id', $user->id)->where('is_active', true)->count(),
            'total_rentals' => $user->ownedBotRentals()->count(),
            'total_revenue' => $user->botOwnerEarnings()->sum('net_amount'),
        ];

        return view('chatbot.index', compact('bots', 'stats'));
    }

    /**
     * แสดงฟอร์มสร้างบอทใหม่
     */
    public function create()
    {
        $providers = AiProvider::where('is_active', true)->get();
        $models = AiModel::where('is_active', true)->get();

        return view('chatbot.create', compact('providers', 'models'));
    }

    /**
     * บันทึกบอทใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-z0-9\-_]+$/i',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'provider_id' => 'required|exists:ai_providers,id',
            'model_id' => 'required|exists:ai_models,id',
            'system_prompt' => 'nullable|string|max:10000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:100|max:8000',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'frequency_penalty' => 'nullable|numeric|min:-2|max:2',
            'presence_penalty' => 'nullable|numeric|min:-2|max:2',
            'is_public' => 'nullable|boolean',
            'enable_knowledge_base' => 'nullable|boolean',
            'line_oa_channel_id' => 'nullable|string|max:255',
            'line_oa_channel_secret' => 'nullable|string|max:255',
            'line_oa_access_token' => 'nullable|string|max:500',
        ]);

        // สร้างบอทใหม่
        $bot = AiBotProfile::create([
            'owner_id' => Auth::id(),
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'provider_id' => $validated['provider_id'],
            'model_id' => $validated['model_id'],
            'system_prompt' => $validated['system_prompt'] ?? 'You are a helpful AI assistant.',
            'temperature' => $validated['temperature'] ?? 0.7,
            'max_tokens' => $validated['max_tokens'] ?? 2000,
            'top_p' => $validated['top_p'] ?? 1,
            'frequency_penalty' => $validated['frequency_penalty'] ?? 0,
            'presence_penalty' => $validated['presence_penalty'] ?? 0,
            'is_public' => $request->has('is_public'),
            'enable_knowledge_base' => $request->has('enable_knowledge_base'),
            'line_oa_channel_id' => $validated['line_oa_channel_id'] ?? null,
            'line_oa_channel_secret' => $validated['line_oa_channel_secret'] ?? null,
            'line_oa_access_token' => $validated['line_oa_access_token'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('chatbot.show', $bot->id)
            ->with('success', 'สร้าง Bot สำเร็จ!');
    }

    /**
     * อัปเดตข้อมูลบอท
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-z0-9\-_]+$/i',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'provider_id' => 'required|exists:ai_providers,id',
            'model_id' => 'required|exists:ai_models,id',
            'system_prompt' => 'nullable|string|max:10000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:100|max:8000',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'frequency_penalty' => 'nullable|numeric|min:-2|max:2',
            'presence_penalty' => 'nullable|numeric|min:-2|max:2',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'enable_knowledge_base' => 'nullable|boolean',
            'line_oa_channel_id' => 'nullable|string|max:255',
            'line_oa_channel_secret' => 'nullable|string|max:255',
            'line_oa_access_token' => 'nullable|string|max:500',
        ]);

        // อัปเดตข้อมูลบอท
        $bot->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'provider_id' => $validated['provider_id'],
            'model_id' => $validated['model_id'],
            'system_prompt' => $validated['system_prompt'] ?? 'You are a helpful AI assistant.',
            'temperature' => $validated['temperature'] ?? 0.7,
            'max_tokens' => $validated['max_tokens'] ?? 2000,
            'top_p' => $validated['top_p'] ?? 1,
            'frequency_penalty' => $validated['frequency_penalty'] ?? 0,
            'presence_penalty' => $validated['presence_penalty'] ?? 0,
            'is_public' => $request->has('is_public'),
            'is_active' => $request->has('is_active'),
            'enable_knowledge_base' => $request->has('enable_knowledge_base'),
            'line_oa_channel_id' => $validated['line_oa_channel_id'] ?? null,
            'line_oa_channel_secret' => $validated['line_oa_channel_secret'] ?? null,
            'line_oa_access_token' => $validated['line_oa_access_token'] ?? null,
        ]);

        return redirect()
            ->route('chatbot.show', $bot->id)
            ->with('success', 'อัปเดต Bot สำเร็จ!');
    }

    /**
     * ลบบอท
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        // ลบบอท (soft delete)
        $bot->delete();

        return redirect()
            ->route('chatbot.index')
            ->with('success', 'ลบ Bot สำเร็จ!');
    }

    /**
     * แสดงรายละเอียดบอท
     */
    public function show($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)
            ->with(['provider', 'model', 'keywordResponses', 'platformIntegrations'])
            ->withCount(['activeRentals', 'conversations'])
            ->findOrFail($id);

        return view('chatbot.show', compact('bot'));
    }

    /**
     * แสดงฟอร์มแก้ไขบอท
     */
    public function edit($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);
        $providers = AiProvider::where('is_active', true)->get();
        $models = AiModel::where('is_active', true)->get();

        return view('chatbot.edit', compact('bot', 'providers', 'models'));
    }

    /**
     * หน้าจัดการ Keywords
     */
    public function keywords($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        return view('chatbot.keywords', compact('bot'));
    }

    /**
     * หน้าจัดการ Platform Integrations
     */
    public function integrations($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        $platforms = [
            'line' => ['name' => 'LINE Official Account', 'icon' => 'fab fa-line'],
            'facebook' => ['name' => 'Facebook Messenger', 'icon' => 'fab fa-facebook'],
            'instagram' => ['name' => 'Instagram DM', 'icon' => 'fab fa-instagram'],
            'telegram' => ['name' => 'Telegram', 'icon' => 'fab fa-telegram'],
            'discord' => ['name' => 'Discord', 'icon' => 'fab fa-discord'],
            'whatsapp' => ['name' => 'WhatsApp', 'icon' => 'fab fa-whatsapp'],
            'twitter' => ['name' => 'Twitter DM', 'icon' => 'fab fa-twitter'],
            'slack' => ['name' => 'Slack', 'icon' => 'fab fa-slack'],
            'web_widget' => ['name' => 'Web Widget', 'icon' => 'fas fa-globe'],
        ];

        return view('chatbot.integrations', compact('bot', 'platforms'));
    }

    /**
     * หน้าจัดการ Auto Content
     */
    public function autoContent($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        return view('chatbot.auto-content', compact('bot'));
    }

    /**
     * หน้า Analytics
     */
    public function analytics($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)
            ->with(['conversations', 'usageLogs'])
            ->findOrFail($id);

        $stats = [
            'total_conversations' => $bot->conversations()->count(),
            'total_messages' => $bot->conversations()->sum('total_messages'),
            'total_tokens' => $bot->usageLogs()->sum('tokens_used'),
            'total_cost' => $bot->usageLogs()->sum('cost'),
            'avg_response_time' => $bot->conversations()->avg('avg_response_time'),
        ];

        return view('chatbot.analytics', compact('bot', 'stats'));
    }

    /**
     * หน้าทดสอบบอท
     */
    public function playground($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)
            ->with(['provider', 'model'])
            ->findOrFail($id);

        return view('chatbot.playground', compact('bot'));
    }
}
