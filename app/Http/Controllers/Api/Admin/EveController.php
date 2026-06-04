<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\FortuneAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:64|max:1024',
        ]);

        $admin = $request->user();
        $operatorName = $admin?->name ?? 'admin';

        $systemPrompt = $this->buildSystemPrompt($operatorName, $data['context'] ?? []);
        $userMessage = $this->buildUserMessage($data['history'] ?? [], $data['message']);

        $provider = $data['provider'] ?? 'groq';
        $model = $data['model'] ?? 'llama-3.3-70b-versatile';
        $config = [
            'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : 0.55,
            'max_tokens' => isset($data['max_tokens']) ? (int) $data['max_tokens'] : 320,
        ];

        $started = microtime(true);
        try {
            try {
                $result = $aiService->chatWithCustomSystemPrompt(
                    systemMessage: $systemPrompt,
                    userMessage: $userMessage,
                    config: $config,
                    providerOverride: $provider,
                    modelOverride: $model,
                );
            } catch (Throwable $inner) {
                // 🩹 (2026-06-04) The requested provider may have no Chat-AI key
                //   (e.g. groq isn't used for chat per the AI-pool routing). Rather
                //   than hard-fail Eve, fall back to the DEFAULT AI pool — the same
                //   keyless path ChatController::suggest + the fortune bot use — so
                //   Eve still answers using whatever provider has an active key.
                if (stripos($inner->getMessage(), 'API Key') === false) {
                    throw $inner;
                }
                $result = $aiService->chatWithCustomSystemPrompt(
                    systemMessage: $systemPrompt,
                    userMessage: $userMessage,
                    config: $config,
                );
                $provider = 'pool';
                $model = 'auto';
            }

            // FortuneAIService::chatWithCustomSystemPrompt returns
            // ['response' => string, ...] via sanitizeChatResult.
            $reply = is_array($result)
                ? ($result['response'] ?? $result['content'] ?? $result['text'] ?? '')
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

    /**
     * GET /api/admin/eve/signals
     *
     * Aggregator that feeds Eve. Each "signal" is a single number Eve can
     * reason over — she doesn't need raw SQL, just the headline counts
     * and a few hot pointers.
     *
     * This is also what the warroom polls so Eve panel can show live
     * mission-control numbers next to her face.
     */
    public function signals(Request $request): JsonResponse
    {
        $now = now();

        $payload = [
            'generated_at' => $now->toIso8601String(),
            'fortune' => $this->fortuneSignals($now),
            'ai_pool' => $this->aiPoolSignals($now),
            'finance' => $this->financeSignals($now),
            'moderation' => $this->moderationSignals($now),
        ];

        // Compute a top-line "alert level" for Eve's badge.
        $crit = ($payload['fortune']['stuck_paid'] ?? 0)
            + ($payload['fortune']['triage_crit'] ?? 0)
            + ($payload['ai_pool']['providers_offline'] ?? 0)
            + ($payload['finance']['withdrawals_pending'] ?? 0);
        $warn = ($payload['fortune']['triage_warn'] ?? 0)
            + ($payload['fortune']['lead_count'] ?? 0)
            + ($payload['moderation']['suspects'] ?? 0);

        $payload['alert'] = [
            'crit' => $crit,
            'warn' => $warn,
            'level' => $crit > 0 ? 'crit' : ($warn > 0 ? 'warn' : 'ok'),
            'headline' => $this->buildHeadline($payload),
        ];

        return response()->json(['success' => true, 'data' => $payload]);
    }

    private function fortuneSignals(\DateTimeInterface $now): array
    {
        $out = [
            'in_flight' => 0,           // paid + has responded_at < 60s
            'stuck_paid' => 0,          // paid + no responded_at, > 60s
            'unpaid_followups' => 0,    // !paid, created < 24h
            'lead_count' => 0,          // !paid, age 5-60 min — fresh leads
            'completed_15m' => 0,
            'failed_15m' => 0,
            'triage_crit' => 0,
            'triage_warn' => 0,
            'oldest_stuck_paid_min' => null,
        ];
        try {
            if (Schema::hasTable('fortune_readings')) {
                $cutoff10 = (clone $now)->modify('-10 minutes');
                $cutoff24h = (clone $now)->modify('-24 hours');
                $cutoff60s = (clone $now)->modify('-60 seconds');

                $out['unpaid_followups'] = DB::table('fortune_readings')
                    ->where('is_paid', false)
                    ->whereNull('paid_at')
                    ->where('created_at', '>=', $cutoff24h)
                    ->count();

                $out['lead_count'] = DB::table('fortune_readings')
                    ->where('is_paid', false)
                    ->whereNull('paid_at')
                    ->where('created_at', '<', (clone $now)->modify('-5 minutes'))
                    ->where('created_at', '>=', (clone $now)->modify('-60 minutes'))
                    ->count();

                $stuck = DB::table('fortune_readings')
                    ->where('is_paid', true)
                    ->whereNull('responded_at')
                    ->where('created_at', '>=', $cutoff10)
                    ->get(['id', 'paid_at', 'created_at']);
                $out['stuck_paid'] = $stuck->count();
                if ($stuck->count() > 0) {
                    $oldestSec = $stuck->reduce(function ($carry, $r) use ($now) {
                        $ts = $r->paid_at ?? $r->created_at;
                        if (! $ts) return $carry;
                        $diff = max(0, $now->getTimestamp() - strtotime((string) $ts));
                        return max($carry, $diff);
                    }, 0);
                    $out['oldest_stuck_paid_min'] = (int) round($oldestSec / 60);
                }
            }

            if (Schema::hasTable('ai_api_key_usage_logs')) {
                $out['completed_15m'] = DB::table('ai_api_key_usage_logs')
                    ->where('created_at', '>=', (clone $now)->modify('-15 minutes'))
                    ->where('is_success', 1)
                    ->count();
                $out['failed_15m'] = DB::table('ai_api_key_usage_logs')
                    ->where('created_at', '>=', (clone $now)->modify('-15 minutes'))
                    ->where('is_success', 0)
                    ->count();
            }

            // Re-use the behavior triage logic by counting our own sources.
            if (Schema::hasTable('fortune_sensitive_events')) {
                $window = (clone $now)->modify('-6 hours');
                $out['triage_crit'] = DB::table('fortune_sensitive_events')
                    ->where('created_at', '>=', $window)
                    ->where('mood_level', '>=', 5)
                    ->count();
                $out['triage_warn'] = DB::table('fortune_sensitive_events')
                    ->where('created_at', '>=', $window)
                    ->where(function ($q) {
                        $q->where('mood_level', '>=', 4)->orWhere('is_sensitive', 1);
                    })
                    ->count() - $out['triage_crit'];
                $out['triage_warn'] = max(0, $out['triage_warn']);
            }
        } catch (\Throwable $e) {
            //
        }
        return $out;
    }

    private function aiPoolSignals(\DateTimeInterface $now): array
    {
        $out = [
            'providers_offline' => 0,
            'keys_disabled' => 0,
            'keys_active' => 0,
            'error_rate_15m_pct' => 0.0,
        ];
        try {
            if (Schema::hasTable('ai_api_keys')) {
                $out['keys_active'] = DB::table('ai_api_keys')->where('is_active', 1)->count();
                $out['keys_disabled'] = DB::table('ai_api_keys')->where('is_active', 0)->count();
                $out['providers_offline'] = DB::table('ai_api_keys')
                    ->where('consecutive_errors', '>=', 5)
                    ->count();
            }
            if (Schema::hasTable('ai_api_key_usage_logs')) {
                $row = DB::table('ai_api_key_usage_logs')
                    ->where('created_at', '>=', (clone $now)->modify('-15 minutes'))
                    ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_success=0 THEN 1 ELSE 0 END) as fails')
                    ->first();
                $tot = (int) ($row->total ?? 0);
                $out['error_rate_15m_pct'] = $tot > 0
                    ? round((((int) ($row->fails ?? 0)) / $tot) * 100, 1)
                    : 0.0;
            }
        } catch (\Throwable $e) {
            //
        }
        return $out;
    }

    private function financeSignals(\DateTimeInterface $now): array
    {
        $out = ['withdrawals_pending' => 0, 'sms_unmatched' => 0];
        try {
            if (Schema::hasTable('withdrawal_requests')) {
                $out['withdrawals_pending'] = DB::table('withdrawal_requests')
                    ->where('status', 'pending')
                    ->count();
            }
            if (Schema::hasTable('sms_payment_notifications')) {
                $out['sms_unmatched'] = DB::table('sms_payment_notifications')
                    ->where('status', 'pending')
                    ->where('created_at', '>=', (clone $now)->modify('-24 hours'))
                    ->count();
            }
        } catch (\Throwable $e) {
            //
        }
        return $out;
    }

    private function moderationSignals(\DateTimeInterface $now): array
    {
        $out = ['suspects' => 0, 'banned_active' => 0];
        try {
            if (Schema::hasTable('fortune_user_bans')) {
                $out['banned_active'] = DB::table('fortune_user_bans')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('banned_until')->orWhere('banned_until', '>=', $now);
                    })
                    ->count();
            }
        } catch (\Throwable $e) {
            //
        }
        return $out;
    }

    /**
     * Short Thai sentence Eve can announce. Generated server-side so the
     * frontend doesn't need to re-derive it from raw numbers.
     */
    private function buildHeadline(array $payload): string
    {
        $f = $payload['fortune'] ?? [];
        $a = $payload['ai_pool'] ?? [];

        $bits = [];
        if (($f['stuck_paid'] ?? 0) > 0) {
            $msg = '💰 มี ' . $f['stuck_paid'] . ' รายจ่ายแล้วยังไม่ได้คำทำนาย';
            if (! empty($f['oldest_stuck_paid_min'])) {
                $msg .= ' (เก่าสุด ' . $f['oldest_stuck_paid_min'] . ' นาที)';
            }
            $bits[] = $msg;
        }
        if (($f['lead_count'] ?? 0) > 0) {
            $bits[] = '🎯 ' . $f['lead_count'] . ' ลีดสด รอตอบ';
        }
        if (($a['providers_offline'] ?? 0) > 0) {
            $bits[] = '🔌 ' . $a['providers_offline'] . ' provider offline';
        }
        if (($a['error_rate_15m_pct'] ?? 0) > 20) {
            $bits[] = '⚠ error rate ' . $a['error_rate_15m_pct'] . '%';
        }
        if (empty($bits)) return '✓ ทุกอย่างปกติ — Eve เฝ้าดูอยู่';
        return implode(' · ', $bits);
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
