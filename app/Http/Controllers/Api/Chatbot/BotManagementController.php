<?php

namespace App\Http\Controllers\Api\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\ChatbotKeywordResponse;
use App\Services\Chatbot\HybridChatbotEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Bot Management Controller
 *
 * จัดการบอทโปรไฟล์ (CRUD)
 */
class BotManagementController extends Controller
{
    protected HybridChatbotEngine $chatbotEngine;

    public function __construct(HybridChatbotEngine $chatbotEngine)
    {
        $this->chatbotEngine = $chatbotEngine;
    }

    /**
     * Get all bots owned by user
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = AiBotProfile::where('owner_id', $user->id)
            ->with(['provider', 'model']);

        // Filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('is_rentable')) {
            $query->where('is_rentable', $request->is_rentable);
        }

        $bots = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bots,
        ]);
    }

    /**
     * Create new bot
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider_id' => 'required|exists:ai_providers,id',
            'model_id' => 'required|exists:ai_models,id',
            'system_prompt' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:100000',
            'is_rentable' => 'boolean',
            'rental_price_per_month' => 'nullable|numeric|min:0',
            'rental_price_per_message' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        $bot = AiBotProfile::create([
            'owner_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'provider_id' => $request->provider_id,
            'model_id' => $request->model_id,
            'system_prompt' => $request->system_prompt ?? 'You are a helpful assistant.',
            'temperature' => $request->temperature ?? 0.7,
            'max_tokens' => $request->max_tokens ?? 2000,
            'top_p' => $request->top_p ?? 1.0,
            'presence_penalty' => $request->presence_penalty ?? 0,
            'frequency_penalty' => $request->frequency_penalty ?? 0,
            'is_rentable' => $request->is_rentable ?? false,
            'rental_price_per_month' => $request->rental_price_per_month,
            'rental_price_per_message' => $request->rental_price_per_message,
            'commission_rate' => $request->commission_rate ?? 20,
            'is_active' => true,
            'is_public' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สร้างบอทสำเร็จ',
            'data' => $bot->load(['provider', 'model']),
        ], 201);
    }

    /**
     * Get bot details
     */
    public function show($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)
            ->with(['provider', 'model', 'keywordResponses', 'platformIntegrations'])
            ->findOrFail($id);

        // Validate configuration
        $validation = $this->chatbotEngine->validateBotConfiguration($bot);

        return response()->json([
            'success' => true,
            'data' => $bot,
            'validation' => $validation,
        ]);
    }

    /**
     * Update bot
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:100000',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_rentable' => 'boolean',
            'rental_price_per_month' => 'nullable|numeric|min:0',
            'rental_price_per_message' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $bot->update($request->only([
            'name',
            'description',
            'system_prompt',
            'temperature',
            'max_tokens',
            'top_p',
            'presence_penalty',
            'frequency_penalty',
            'is_active',
            'is_public',
            'is_rentable',
            'rental_price_per_month',
            'rental_price_per_message',
            'commission_rate',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตบอทสำเร็จ',
            'data' => $bot->fresh()->load(['provider', 'model']),
        ]);
    }

    /**
     * Delete bot
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        // Check if bot has active rentals
        if ($bot->activeRentals()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบบอทที่มีการเช่าอยู่ได้',
            ], 422);
        }

        $bot->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบบอทสำเร็จ',
        ]);
    }

    /**
     * Test bot
     */
    public function test(Request $request, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->chatbotEngine->processMessage($bot, $request->message);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Clone bot
     */
    public function clone($id)
    {
        $user = Auth::user();

        $originalBot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        $newBot = $originalBot->replicate();
        $newBot->name = $originalBot->name . ' (Copy)';
        $newBot->is_public = false;
        $newBot->save();

        // Clone keyword responses
        foreach ($originalBot->keywordResponses as $keywordResponse) {
            $newKeywordResponse = $keywordResponse->replicate();
            $newKeywordResponse->bot_profile_id = $newBot->id;
            $newKeywordResponse->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'คัดลอกบอทสำเร็จ',
            'data' => $newBot->load(['provider', 'model']),
        ]);
    }

    /**
     * Get bot statistics
     */
    public function stats($id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($id);

        $stats = [
            'total_conversations' => $bot->conversations()->count(),
            'total_messages' => $bot->conversations()->sum('total_messages'),
            'total_keywords' => $bot->keywordResponses()->count(),
            'active_keywords' => $bot->keywordResponses()->active()->count(),
            'total_rentals' => $bot->rentals()->count(),
            'active_rentals' => $bot->activeRentals()->count(),
            'total_revenue' => $bot->getTotalRentalIncome(),
            'platform_integrations' => $bot->platformIntegrations()->count(),
            'active_integrations' => $bot->platformIntegrations()->active()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
