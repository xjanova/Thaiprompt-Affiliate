<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiProvider;
use App\Models\AiModel;
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
            'total_revenue' => $user->ownedBotRentals()->sum('owner_earning'),
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
