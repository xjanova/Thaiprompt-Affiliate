<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EveController extends Controller
{
    public function chat(Request $request, FortuneAIService $aiService): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|min:1|max:2000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
            'context' => 'nullable|array',
            'provider' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:120',
        ]);

        $admin = $request->user();
        $operatorName = $admin?->name ?? 'admin';

        $systemPrompt = $this->buildSystemPrompt($operatorName, $data['context'] ?? []);
        $userMessage = $this->buildUserMessage($data['history'] ?? [], $data['message']);

        $provider = $data['provider'] ?? 'groq';
        $model = $data['model'] ?? 'llama-3.3-70b-versatile';

        $started = microtime(true);
        try {
            $result = $aiService->chatWithCustomSystemPrompt(
                systemMessage: $systemPrompt,
                userMessage: $userMessage,
                config: ['temperature' => 0.55, 'max_tokens' => 320],
                providerOverride: $provider,
                modelOverride: $model,
            );

            $reply = is_array($result)
                ? ($result['content'] ?? $result['text'] ?? '')
                : (string) $result;
            $tokens = is_array($result) ? ($result['tokens_used'] ?? $result['tokens'] ?? null) : null;
            $latencyMs = (int) round((microtime(true) - $started) * 1000);

            Log::info('Eve: chat reply', [
                'admin_id' => $admin?->id,
                'provider' => $provider,
                'model' => $model,
                'latency_ms' => $latencyMs,
                'tokens' => $tokens,
                'message_preview' => mb_substr($data['message'], 0, 80),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'reply' => $reply,
                    'provider' => $provider,
                    'model' => $model,
                    'latency_ms' => $latencyMs,
                    'tokens' => $tokens,
                    'mood' => $this->guessMood($reply),
                ],
            ]);
        } catch (Throwable $e) {
            Log::warning('Eve: chat failed', [
                'admin_id' => $admin?->id,
                'provider' => $provider,
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Eve ตอบไม่ได้: ' . $e->getMessage(),
                'data' => [
                    'provider' => $provider,
                    'model' => $model,
                ],
            ], 500);
        }
    }

    private function buildSystemPrompt(string $operatorName, array $context): string
    {
        $base = "You are 'Eve', a Thai-speaking AI assistant inside the Warroom (mission control for an online fortune-telling business). "
            . "You talk to ADMIN OPERATORS only (not customers). The operator's name is " . $operatorName . ". "
            . "Reply in Thai, 1-3 short sentences, polite female tone, end with kha/nakha naturally. "
            . "Use sparing emoji (max one per reply). Be action-oriented — when the admin asks about a case, suggest the next step. "
            . "Domains you know: triage queue, payments reconciliation, withdrawals approvals, moderation/bans, AI bots, fortune readings, customer 360. "
            . "You do NOT serve customers directly — that is the fortune bot's job.";

        if (! empty($context)) {
            $compact = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $base .= "\n\nLive warroom context (JSON):\n" . mb_substr((string) $compact, 0, 1500);
        }
        return $base;
    }

    private function buildUserMessage(array $history, string $latest): string
    {
        if (empty($history)) {
            return $latest;
        }
        $lines = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? 'user') === 'assistant' ? 'Eve' : 'Admin';
            $lines[] = $role . ': ' . ($turn['content'] ?? '');
        }
        $lines[] = 'Admin: ' . $latest;
        $lines[] = 'Eve:';
        return implode("\n", $lines);
    }

    private function guessMood(string $reply): string
    {
        $lower = mb_strtolower($reply);
        if (preg_match('/(ขออภัย|เสียใจ|กังวล|ระวัง|แย่|วิกฤต)/u', $lower)) return 'concerned';
        if (preg_match('/(เยี่ยม|ดี|สำเร็จ|เรียบร้อย|ขอบคุณ)/u', $lower)) return 'happy';
        if (preg_match('/(\?|ลอง|คิดว่า|น่าจะ|อาจจะ)/u', $lower)) return 'thinking';
        if (preg_match('/(โอ้|ว้าว|ตกใจ|จริงหรือ)/u', $lower)) return 'surprise';
        return 'talking';
    }
}
