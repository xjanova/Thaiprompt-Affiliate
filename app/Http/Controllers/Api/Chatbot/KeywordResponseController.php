<?php

namespace App\Http\Controllers\Api\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\ChatbotKeywordResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Keyword Response Controller
 *
 * จัดการคำตอบแบบ keyword-based
 */
class KeywordResponseController extends Controller
{
    /**
     * Get all keyword responses for a bot
     */
    public function index($botId)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $responses = ChatbotKeywordResponse::where('bot_profile_id', $botId)
            ->orderBy('priority', 'desc')
            ->orderBy('order', 'asc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $responses,
        ]);
    }

    /**
     * Create new keyword response
     */
    public function store(Request $request, $botId)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $validator = Validator::make($request->all(), [
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'string',
            'match_type' => 'required|in:exact,partial,regex',
            'response_text' => 'required|string',
            'response_media' => 'nullable|array',
            'quick_replies' => 'nullable|array',
            'response_variations' => 'nullable|array',
            'priority' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $response = ChatbotKeywordResponse::create([
            'bot_profile_id' => $botId,
            'keywords' => $request->keywords,
            'match_type' => $request->match_type,
            'case_sensitive' => $request->case_sensitive ?? false,
            'response_text' => $request->response_text,
            'response_media' => $request->response_media,
            'quick_replies' => $request->quick_replies,
            'response_variations' => $request->response_variations,
            'priority' => $request->priority ?? 0,
            'conditions' => $request->conditions,
            'is_active' => $request->is_active ?? true,
            'order' => $request->order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มคำตอบสำเร็จ',
            'data' => $response,
        ], 201);
    }

    /**
     * Update keyword response
     */
    public function update(Request $request, $botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $response = ChatbotKeywordResponse::where('bot_profile_id', $botId)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'keywords' => 'sometimes|array|min:1',
            'keywords.*' => 'string',
            'match_type' => 'sometimes|in:exact,partial,regex',
            'response_text' => 'sometimes|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $response->update($request->only([
            'keywords',
            'match_type',
            'case_sensitive',
            'response_text',
            'response_media',
            'quick_replies',
            'response_variations',
            'priority',
            'conditions',
            'is_active',
            'order',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตคำตอบสำเร็จ',
            'data' => $response,
        ]);
    }

    /**
     * Delete keyword response
     */
    public function destroy($botId, $id)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $response = ChatbotKeywordResponse::where('bot_profile_id', $botId)
            ->findOrFail($id);

        $response->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบคำตอบสำเร็จ',
        ]);
    }

    /**
     * Bulk update order
     */
    public function updateOrder(Request $request, $botId)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $validator = Validator::make($request->all(), [
            'responses' => 'required|array',
            'responses.*.id' => 'required|exists:chatbot_keyword_responses,id',
            'responses.*.order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->responses as $item) {
            ChatbotKeywordResponse::where('id', $item['id'])
                ->where('bot_profile_id', $botId)
                ->update(['order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตลำดับสำเร็จ',
        ]);
    }

    /**
     * Test keyword matching
     */
    public function testMatch(Request $request, $botId)
    {
        $user = Auth::user();

        $bot = AiBotProfile::where('owner_id', $user->id)->findOrFail($botId);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $responses = ChatbotKeywordResponse::where('bot_profile_id', $botId)
            ->active()
            ->orderedByPriority()
            ->get();

        $matched = null;

        foreach ($responses as $response) {
            if ($response->matches($request->message)) {
                $matched = $response;
                break;
            }
        }

        if ($matched) {
            return response()->json([
                'success' => true,
                'matched' => true,
                'data' => $matched->getFullResponse(),
            ]);
        }

        return response()->json([
            'success' => true,
            'matched' => false,
            'message' => 'ไม่พบคำตอบที่ตรงกับข้อความ',
        ]);
    }
}
