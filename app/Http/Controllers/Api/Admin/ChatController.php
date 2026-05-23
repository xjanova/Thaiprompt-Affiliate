<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Services\FacebookWebhookService;
use App\Services\FortuneAIService;
use App\Services\LineFortuneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatController extends Controller
{
    public function __construct(
        private readonly FacebookWebhookService $fbService,
        private readonly LineFortuneService $lineService,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reading_id' => 'nullable|integer|exists:fortune_readings,id',
            'platform' => 'nullable|in:facebook,line',
            'platform_user_id' => 'nullable|string|max:255',
            'text' => 'required|string|min:1|max:2000',
        ]);

        $platform = $data['platform'] ?? null;
        $userId = $data['platform_user_id'] ?? null;

        if (! empty($data['reading_id'])) {
            $reading = FortuneReading::find($data['reading_id']);
            if ($reading) {
                $platform = 'facebook';
                $userId = $reading->facebook_user_id;
            }
        }

        if (! $platform || ! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'need reading_id or (platform + platform_user_id)',
            ], 422);
        }

        try {
            $ok = $platform === 'line'
                ? $this->lineService->sendMessage($userId, $data['text'])
                : $this->fbService->sendMessage($userId, $data['text']);

            Log::info('AdminChat: operator message sent', [
                'admin_id' => $request->user()?->id,
                'platform' => $platform,
                'platform_user_id' => $userId,
                'reading_id' => $data['reading_id'] ?? null,
                'text_preview' => mb_substr($data['text'], 0, 80),
                'delivered' => $ok,
            ]);

            return response()->json([
                'success' => (bool) $ok,
                'data' => [
                    'platform' => $platform,
                    'platform_user_id' => $userId,
                    'delivered' => (bool) $ok,
                    'at' => now()->toIso8601String(),
                ],
                'message' => $ok ? 'sent' : 'platform service rejected',
            ], $ok ? 200 : 502);
        } catch (Throwable $e) {
            Log::error('AdminChat: send failed', [
                'error' => $e->getMessage(),
                'platform' => $platform,
                'platform_user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'send failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function suggest(Request $request, FortuneAIService $aiService): JsonResponse
    {
        $data = $request->validate([
            'reading_id' => 'nullable|integer|exists:fortune_readings,id',
            'context_text' => 'required|string|max:4000',
            'customer_name' => 'nullable|string|max:120',
        ]);

        $customerName = $data['customer_name'] ?? 'customer';
        if (! empty($data['reading_id'])) {
            $reading = FortuneReading::find($data['reading_id']);
            if ($reading?->facebook_user_name) {
                $customerName = $reading->facebook_user_name;
            }
        }

        $systemPrompt = "You are an admin assistant for a Thai fortune-telling business. "
            . "Customer name: " . $customerName . ". The admin is drafting a reply in Thai. "
            . "Output ONE short Thai reply (1-3 sentences) that the admin can send as-is. "
            . "Be polite, empathetic, end with kha. No emojis unless natural. No greetings repeated.";

        try {
            $result = $aiService->chatWithCustomSystemPrompt(
                systemMessage: $systemPrompt,
                userMessage: $data['context_text'],
                config: ['temperature' => 0.6, 'max_tokens' => 220],
            );
            $reply = is_array($result)
                ? ($result['content'] ?? $result['text'] ?? '')
                : (string) $result;

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestion' => $reply,
                    'customer_name' => $customerName,
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('AdminChat: suggest failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'suggest failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
