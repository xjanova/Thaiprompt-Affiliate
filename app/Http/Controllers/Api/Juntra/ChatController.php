<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\Fortune\JuntraChatService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Mae Mor Chantra AI chat — Juntra mobile (per-user token).
 *
 * Logic lives in JuntraChatService, shared with the server-to-server chat the จันทรา.online website
 * uses for customers who never linked a Thaiprompt account. Behaviour here is unchanged: an empty AI
 * reply still answers with the filler line, and the reply carries no reading offers.
 */
class ChatController extends Controller
{
    public function __construct(private JuntraChatService $chat) {}

    public function start(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->chat->start((string) $request->user()->id)]);
    }

    public function send(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'session_id' => 'required|string|uuid',
            'text' => 'required|string|min:1|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first()], 422);
        }

        $user = $request->user();
        $sessionId = $request->input('session_id');

        try {
            $out = $this->chat->send(
                (string) $user->id,
                $sessionId,
                trim($request->input('text')),
                // 🪪 (2026-05-24) Tag the AI usage log with customer identity (warroom /workers cards)
                ['user_id' => $user->id, 'customer_name' => $user->name],
                webOffers: false,
                fillerOnEmpty: true,
            );

            return response()->json(['data' => [
                'session_id' => $sessionId,
                'reply' => $out['reply'],
                'ai_provider' => $out['provider'],
            ]]);
        } catch (Exception $e) {
            Log::warning('Juntra chat send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'แม่หมอกำลังพักสายตา · ลองใหม่อีกครู่นะคะลูก',
            ], 503);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $history = $this->chat->history((string) $request->user()->id, $id);
        if (empty($history)) {
            return response()->json(['message' => 'ไม่พบเซสชั่น'], 404);
        }

        return response()->json(['data' => [
            'session_id' => $id,
            'messages' => $history,
        ]]);
    }
}
