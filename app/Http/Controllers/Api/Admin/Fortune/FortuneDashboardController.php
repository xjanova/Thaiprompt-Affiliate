<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Controller;
use App\Models\FortuneCategory;
use App\Models\FortuneReading;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Mobile API: Fortune Dashboard
 *
 * รวมสถิติสำหรับหน้า "ดูดวง & ทาโรต์" บนมือถือ:
 * - Hero (crystal ball)
 * - 4-stat: รายได้/เดือน, sessions, avg rating, active now
 * - Services summary: 6 services with sessions/revenue
 * - Recent readings count
 */
class FortuneDashboardController extends Controller
{
    /**
     * GET /api/admin/fortune/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');
        $start = match ($period) {
            'today' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            default => Carbon::now()->startOfMonth(),
        };

        return response()->json([
            'success' => true,
            'data' => [
                'hero' => $this->getHeroStats($start),
                'services_summary' => $this->getServicesSummary($start),
                'period' => $period,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function getHeroStats(Carbon $start): array
    {
        $stats = [
            'monthly_revenue_thb' => 0.0,
            'sessions_count' => 0,
            'avg_rating' => 0.0,
            'active_now' => 0,
        ];

        try {
            $stats['monthly_revenue_thb'] = (float) FortuneReading::where('is_paid', true)
                ->where('created_at', '>=', $start)
                ->sum('amount_paid');

            $stats['sessions_count'] = FortuneReading::where('created_at', '>=', $start)->count();

            $stats['avg_rating'] = round((float) FortuneReading::whereNotNull('rating')
                ->where('created_at', '>=', $start)
                ->avg('rating'), 2);

            // active_now = readings ที่ user_id และยังไม่ตอบ (response_type=pending) ใน 1 ชม.
            $stats['active_now'] = FortuneReading::whereNotNull('user_id')
                ->where('responded_at', null)
                ->where('created_at', '>=', now()->subHour())
                ->count();
        } catch (\Throwable $e) {
            // table missing? ส่ง 0
        }

        return $stats;
    }

    private function getServicesSummary(Carbon $start): array
    {
        try {
            $categories = FortuneCategory::query()
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            return $categories->map(function ($cat) use ($start) {
                // หา reading ที่ category นี้ — categories เป็น JSON array ในแต่ละ reading
                // ใช้ JSON_CONTAINS (MySQL) หรือ whereJsonContains (Laravel)
                $sessions = 0;
                $revenue = 0.0;

                try {
                    $query = FortuneReading::where('created_at', '>=', $start);
                    // whereJsonContains ใช้ได้ถ้า categories field เป็น array
                    $query = $query->whereJsonContains('categories', $cat->slug);

                    $sessions = (clone $query)->count();
                    $revenue = (float) (clone $query)->where('is_paid', true)->sum('amount_paid');
                } catch (\Throwable $e) {
                    //
                }

                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'color' => $cat->color,
                    'icon' => $cat->icon,
                    'sessions' => $sessions,
                    'revenue_thb' => round($revenue, 2),
                    'is_active' => (bool) $cat->is_active,
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * GET /api/admin/fortune/workers/queue
     *
     * Realtime view of the Fortune AI bot workers — every AI call the bot makes
     * to reply to a customer (comment-to-DM, paid reading, chat). Powers warroom
     * /workers page.
     *
     * Data sources (in priority order):
     *   - ai_api_key_usage_logs — the canonical "every AI call" log. Each row is
     *     ONE bot reply to ONE customer. ~70 rows / hour on a normal day.
     *     This is what the operator actually wants to see.
     *   - ai_api_keys.provider — joined to get provider name (groq/gemini/...).
     *   - fortune_comment_engagements — recent comment→DM events for context
     *     (which FB user just got DM'd).
     *   - fortune_readings — paid reading queue (pending_paid + stuck counters).
     */
    public function workersQueue(Request $request): JsonResponse
    {
        if (! Schema::hasTable('ai_api_key_usage_logs') || ! Schema::hasTable('ai_api_keys')) {
            return response()->json([
                'success' => true,
                'data' => $this->emptyQueueShape(),
            ]);
        }

        $now = now();

        // ── AI-call counters (the real bot heartbeat) ──
        $completed15 = DB::table('ai_api_key_usage_logs')
            ->where('created_at', '>=', $now->copy()->subMinutes(15))
            ->where('is_success', 1)
            ->count();
        $failed15 = DB::table('ai_api_key_usage_logs')
            ->where('created_at', '>=', $now->copy()->subMinutes(15))
            ->where('is_success', 0)
            ->count();
        $completed60 = DB::table('ai_api_key_usage_logs')
            ->where('created_at', '>=', $now->copy()->subHour())
            ->where('is_success', 1)
            ->count();

        // ── Latency (only successful) ──
        $latencies = DB::table('ai_api_key_usage_logs')
            ->select('response_time_ms')
            ->where('created_at', '>=', $now->copy()->subMinutes(15))
            ->where('is_success', 1)
            ->whereNotNull('response_time_ms')
            ->pluck('response_time_ms')
            ->filter(fn ($v) => $v !== null && $v >= 0)
            ->sort()
            ->values();
        $avgLat = $latencies->count() > 0 ? (float) ($latencies->avg() ?? 0) : 0;
        $p95Lat = $latencies->count() > 0
            ? (int) ($latencies[max(0, (int) ceil($latencies->count() * 0.95) - 1)] ?? 0)
            : 0;

        // ── Paid-reading queue (separate concept — extra signal for the operator) ──
        $pendingPaid = 0;
        $pendingUnpaid = 0;
        $stuck = 0;
        if (Schema::hasTable('fortune_readings')) {
            $inFlightCutoff = $now->copy()->subMinutes(10);
            $pendingPaid = DB::table('fortune_readings')
                ->whereNull('responded_at')
                ->where('is_paid', true)
                ->where('created_at', '>=', $inFlightCutoff)
                ->count();
            $pendingUnpaid = DB::table('fortune_readings')
                ->whereNull('responded_at')
                ->where('is_paid', false)
                ->where('created_at', '>=', $inFlightCutoff)
                ->count();
            $stuck = DB::table('fortune_readings')
                ->whereNull('responded_at')
                ->where('is_paid', true)
                ->where('created_at', '<', $now->copy()->subSeconds(60))
                ->where('created_at', '>=', $inFlightCutoff)
                ->count();
        }

        // ── In-flight: most recent successful calls in the last 30s (worker
        // just finished, or is finishing right now). The bot is fully sync so
        // there's no formal "in-flight" row — we surface the freshest activity. ──
        $inFlight = DB::table('ai_api_key_usage_logs as l')
            ->join('ai_api_keys as k', 'k.id', '=', 'l.ai_api_key_id')
            ->select(
                'l.id', 'l.created_at', 'l.model', 'l.request_type',
                'l.total_tokens', 'l.response_time_ms', 'l.is_success',
                'k.provider', 'k.name as key_name'
            )
            ->where('l.created_at', '>=', $now->copy()->subSeconds(30))
            ->orderByDesc('l.created_at')
            ->limit(12)
            ->get()
            ->map(function ($r) use ($now) {
                $createdTs = strtotime((string) $r->created_at);
                $age = max(0, $now->getTimestamp() - $createdTs);
                return [
                    'log_id' => (int) $r->id,
                    'provider' => $r->provider,
                    'model' => $r->model,
                    'key_name' => $r->key_name,
                    'request_type' => $r->request_type ?: 'unknown',
                    'tokens' => (int) ($r->total_tokens ?? 0),
                    'latency_ms' => (int) ($r->response_time_ms ?? 0),
                    'success' => (bool) $r->is_success,
                    'created_at' => $r->created_at,
                    'age_seconds' => $age,
                ];
            });

        // ── Recent completed (full activity log, last 24h) ──
        $recent = DB::table('ai_api_key_usage_logs as l')
            ->join('ai_api_keys as k', 'k.id', '=', 'l.ai_api_key_id')
            ->select(
                'l.id', 'l.created_at', 'l.model', 'l.request_type',
                'l.total_tokens', 'l.response_time_ms', 'l.is_success',
                'l.error_message',
                'k.provider', 'k.name as key_name'
            )
            ->where('l.created_at', '>=', $now->copy()->subDay())
            ->orderByDesc('l.created_at')
            ->limit(40)
            ->get()
            ->map(fn ($r) => [
                'log_id' => (int) $r->id,
                'provider' => $r->provider,
                'model' => $r->model,
                'key_name' => $r->key_name,
                'request_type' => $r->request_type ?: 'unknown',
                'tokens' => (int) ($r->total_tokens ?? 0),
                'latency_ms' => (int) ($r->response_time_ms ?? 0),
                'success' => (bool) $r->is_success,
                'error_message' => $r->error_message,
                'created_at' => $r->created_at,
            ]);

        // ── Recent comment→DM engagements (who the bot actually messaged) ──
        $commentDms = [];
        if (Schema::hasTable('fortune_comment_engagements')) {
            $commentDms = DB::table('fortune_comment_engagements')
                ->select('id', 'facebook_user_id', 'facebook_post_id', 'comment_text', 'comment_reply', 'dm_message', 'engaged_at')
                ->where('engaged_at', '>=', $now->copy()->subDay())
                ->orderByDesc('engaged_at')
                ->limit(20)
                ->get()
                ->map(fn ($r) => [
                    'id' => (int) $r->id,
                    'fb_user_id' => $r->facebook_user_id,
                    'fb_post_id' => $r->facebook_post_id,
                    'comment_text' => mb_substr((string) ($r->comment_text ?? ''), 0, 140),
                    'comment_reply' => mb_substr((string) ($r->comment_reply ?? ''), 0, 140),
                    'dm_message' => mb_substr((string) ($r->dm_message ?? ''), 0, 160),
                    'engaged_at' => $r->engaged_at,
                ])
                ->toArray();
        }

        // ── Per-provider breakdown over the last 15min ──
        $providerSplit = DB::table('ai_api_key_usage_logs as l')
            ->join('ai_api_keys as k', 'k.id', '=', 'l.ai_api_key_id')
            ->selectRaw('k.provider as name, COUNT(*) as calls, SUM(CASE WHEN l.is_success=1 THEN 1 ELSE 0 END) as ok, SUM(l.total_tokens) as tokens')
            ->where('l.created_at', '>=', $now->copy()->subMinutes(15))
            ->groupBy(DB::raw('k.provider'))
            ->orderByDesc('calls')
            ->get()
            ->map(fn ($r) => [
                'provider' => $r->name,
                'calls' => (int) $r->calls,
                'ok' => (int) $r->ok,
                'tokens' => (int) ($r->tokens ?? 0),
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'queue' => [
                    'pending_paid' => $pendingPaid,
                    'pending_unpaid' => $pendingUnpaid,
                    'in_flight' => $inFlight->count(),
                    'stuck' => $stuck,
                    'completed_last_15m' => $completed15,
                    'completed_last_hour' => $completed60,
                    'failed_last_15m' => $failed15,
                ],
                'throughput' => [
                    'per_min' => round(($completed15 + $failed15) / 15, 1),
                    'per_hour' => $completed60,
                ],
                'latency' => [
                    'avg_ms' => (int) round($avgLat),
                    'p95_ms' => $p95Lat,
                ],
                'in_flight' => $inFlight,
                'recent_completed' => $recent,
                'comment_dms' => $commentDms,
                'provider_split' => $providerSplit,
                'generated_at' => $now->toIso8601String(),
            ],
        ]);
    }

    private function emptyQueueShape(): array
    {
        return [
            'queue' => [
                'pending_paid' => 0, 'pending_unpaid' => 0, 'in_flight' => 0, 'stuck' => 0,
                'completed_last_15m' => 0, 'completed_last_hour' => 0, 'failed_last_15m' => 0,
            ],
            'throughput' => ['per_min' => 0, 'per_hour' => 0],
            'latency' => ['avg_ms' => 0, 'p95_ms' => 0],
            'in_flight' => [], 'recent_completed' => [], 'comment_dms' => [], 'provider_split' => [],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function parseQuestions($raw): string
    {
        if (is_array($raw)) return implode(' · ', $raw);
        if (is_string($raw) && str_starts_with(trim($raw), '[')) {
            try { $j = json_decode($raw, true); if (is_array($j)) return implode(' · ', $j); } catch (\Throwable $e) {}
        }
        return (string) ($raw ?? '');
    }
}
