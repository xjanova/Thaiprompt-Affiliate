<?php

namespace App\Http\Controllers\Api\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Mobile API: AI Dashboard
 *
 * รวมสถิติ AI สำหรับหน้า "AI Management" บนมือถือ:
 * - Hero: Central AI Brain (orb)
 * - 3-stat: tokens used, cost, cache hit rate
 * - Live inference timeseries (สำหรับ chart)
 */
class AiDashboardController extends Controller
{
    /**
     * GET /api/admin/ai/dashboard
     *
     * คืน:
     * - hero: { total_tokens, total_cost_thb, cache_hit_pct }
     * - providers: [{ name, status, quota_pct, cost_thb }]
     * - bots: { total, active }
     * - inference: { p95_ms, requests_per_min }
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month'); // 'today' | 'week' | 'month'

        return response()->json([
            'success' => true,
            'data' => [
                'hero' => $this->getHeroStats($period),
                'providers_summary' => $this->getProvidersSummary(),
                'bots_summary' => $this->getBotsSummary(),
                'inference' => $this->getInferenceSummary(),
                'generated_at' => now()->toIso8601String(),
                'period' => $period,
            ],
        ]);
    }

    /**
     * GET /api/admin/ai/dashboard/timeseries?hours=24
     *
     * คืน timeseries data สำหรับ live inference chart
     */
    public function timeseries(Request $request): JsonResponse
    {
        $hours = max(1, min(168, (int) $request->input('hours', 24)));

        // ai_api_key_usage_logs is the live usage tracker (FortuneAIService writes
        // via recordUsageForKey()). The empty ai_usage_logs table is legacy and
        // never written to in this codepath.
        $series = [];
        try {
            if (Schema::hasTable('ai_api_key_usage_logs')) {
                $format = $hours <= 24 ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d 00:00:00';
                $series = DB::table('ai_api_key_usage_logs')
                    ->selectRaw("DATE_FORMAT(created_at, '$format') as time, COUNT(*) as requests, AVG(response_time_ms) as avg_latency_ms")
                    ->where('created_at', '>=', now()->subHours($hours))
                    ->groupBy('time')
                    ->orderBy('time')
                    ->get()
                    ->map(fn ($r) => [
                        'time' => (string) $r->time,
                        'requests' => (int) $r->requests,
                        'avg_latency_ms' => round((float) ($r->avg_latency_ms ?? 0), 0),
                    ])
                    ->toArray();
            }
        } catch (\Throwable $e) {
            //
        }

        return response()->json([
            'success' => true,
            'data' => [
                'hours' => $hours,
                'series' => $series,
            ],
        ]);
    }

    /**
     * GET /api/admin/ai/usage/per-provider?hours=1
     *
     * Returns per-provider usage stats for the lookback window:
     *   { providers: [{provider_id, name, color, requests, tokens, cost_usd, error_rate_pct,
     *                 avg_latency_ms, p95_latency_ms, series: [{time, tokens, requests}]}],
     *     window_hours, generated_at }
     *
     * This powers warroom /usage page. Series is bucketed per minute when hours<=1,
     * per hour otherwise.
     */
    public function perProviderUsage(Request $request): JsonResponse
    {
        $hours = max(1, min(168, (int) $request->input('hours', 1)));
        $since = now()->subHours($hours);

        // Series bucket — minute when looking at <=1h, else hour.
        $bucketFormat = $hours <= 1 ? '%Y-%m-%d %H:%i:00' : '%Y-%m-%d %H:00:00';
        $bucketCount = $hours <= 1 ? 60 : $hours;

        if (! Schema::hasTable('ai_api_key_usage_logs') || ! Schema::hasTable('ai_api_keys')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'providers' => [],
                    'window_hours' => $hours,
                    'bucket_count' => $bucketCount,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        }

        try {
            // The log table stores ai_api_key_id; provider name lives on
            // ai_api_keys.provider as a varchar slug ('groq', 'gemini', 'openai',
            // 'grok', etc.). The ai_providers table is a different concept used by
            // mobile-admin features — don't conflate the two.

            // Aggregate per provider over the window.
            $agg = DB::table('ai_api_key_usage_logs as l')
                ->join('ai_api_keys as k', 'k.id', '=', 'l.ai_api_key_id')
                ->selectRaw('k.provider as name, COUNT(*) as requests, SUM(l.total_tokens) as tokens, AVG(l.response_time_ms) as avg_ms, SUM(CASE WHEN l.is_success=0 THEN 1 ELSE 0 END) as errors')
                ->where('l.created_at', '>=', $since)
                ->groupBy('k.provider')
                ->get()
                ->keyBy('name');

            // Latencies for p95 — separate pass so we can sort in PHP without
            // depending on MySQL's PERCENTILE_CONT (8.0+).
            $latByProvider = DB::table('ai_api_key_usage_logs as l')
                ->join('ai_api_keys as k', 'k.id', '=', 'l.ai_api_key_id')
                ->select('k.provider as name', 'l.response_time_ms')
                ->where('l.created_at', '>=', $since)
                ->whereNotNull('l.response_time_ms')
                ->get()
                ->groupBy('name');

            // Time-bucketed series for the chart.
            $seriesByProvider = DB::table('ai_api_key_usage_logs as l')
                ->join('ai_api_keys as k', 'k.id', '=', 'l.ai_api_key_id')
                ->selectRaw("k.provider as name, DATE_FORMAT(l.created_at, '$bucketFormat') as time, COUNT(*) as requests, SUM(l.total_tokens) as tokens")
                ->where('l.created_at', '>=', $since)
                ->groupBy('name', 'time')
                ->orderBy('time')
                ->get()
                ->groupBy('name');

            // Provider directory: include every provider that has at least one
            // key (so idle providers still render a card), union with any
            // provider seen in the log window.
            $providerNames = DB::table('ai_api_keys')
                ->select('provider')
                ->distinct()
                ->pluck('provider')
                ->merge($agg->keys())
                ->unique()
                ->filter()
                ->values();

            $keyCounts = DB::table('ai_api_keys')
                ->selectRaw('provider, COUNT(*) as total_keys, SUM(is_active) as active_keys, SUM(tokens_used_today) as tokens_today, SUM(tokens_used_month) as tokens_month')
                ->groupBy('provider')
                ->get()
                ->keyBy('provider');

            $providers = $providerNames->map(function ($name) use ($agg, $latByProvider, $seriesByProvider, $keyCounts, $bucketCount) {
                $a = $agg->get($name);
                $lats = ($latByProvider->get($name) ?? collect())->pluck('response_time_ms')->sort()->values();
                $p95 = $lats->count() > 0
                    ? (int) ($lats[max(0, (int) ceil($lats->count() * 0.95) - 1)] ?? 0)
                    : 0;
                $reqs = (int) ($a->requests ?? 0);
                $errors = (int) ($a->errors ?? 0);
                $series = ($seriesByProvider->get($name) ?? collect())->map(fn ($r) => [
                    'time' => (string) $r->time,
                    'tokens' => (int) ($r->tokens ?? 0),
                    'requests' => (int) ($r->requests ?? 0),
                ])->toArray();
                $kc = $keyCounts->get($name);

                return [
                    'name' => $name,
                    'display_name' => $this->providerDisplayName($name),
                    'color' => $this->providerColor($name),
                    'is_active' => $kc ? ((int) $kc->active_keys > 0) : false,
                    'total_keys' => (int) ($kc->total_keys ?? 0),
                    'active_keys' => (int) ($kc->active_keys ?? 0),
                    'requests' => $reqs,
                    'tokens' => (int) ($a->tokens ?? 0),
                    'tokens_today' => (int) ($kc->tokens_today ?? 0),
                    'tokens_month' => (int) ($kc->tokens_month ?? 0),
                    'cost_usd' => 0.0, // TODO: derive from model pricing
                    'avg_latency_ms' => (int) round((float) ($a->avg_ms ?? 0)),
                    'p95_latency_ms' => $p95,
                    'error_rate_pct' => $reqs > 0 ? round(($errors / $reqs) * 100, 1) : 0.0,
                    'series' => $series,
                    'series_buckets' => $bucketCount,
                ];
            })->values()->toArray();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'failed_to_aggregate',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'providers' => $providers,
                'window_hours' => $hours,
                'bucket_count' => $bucketCount,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function providerDisplayName(string $name): string
    {
        $map = [
            'openai'        => 'OpenAI',
            'anthropic'     => 'Anthropic',
            'groq'          => 'Groq',
            'grok'          => 'xAI Grok',
            'google'        => 'Google Gemini',
            'gemini'        => 'Google Gemini',
            'deepseek'      => 'DeepSeek',
            'deepseek-local' => 'DeepSeek (Local)',
            'qwen'          => 'Qwen',
            'meta'          => 'Meta Llama',
            'meta-local'    => 'Meta Llama (Local)',
            'postxagent'    => 'PostXAgent',
        ];
        return $map[strtolower($name)] ?? ucfirst($name);
    }

    private function providerColor(string $name): string
    {
        // Stable brand-ish colors so warroom can draw the chart without storing
        // a color per row. Falls through to a deterministic hash.
        $map = [
            'openai'        => '#10b981',
            'anthropic'     => '#d4a747',
            'groq'          => '#22d3ee',
            'grok'          => '#e879f9',
            'google'        => '#8b5cf6',
            'gemini'        => '#8b5cf6',
            'deepseek'      => '#f59e0b',
            'deepseek-local' => '#f59e0b',
            'qwen'          => '#f43f5e',
            'meta'          => '#1877f2',
            'meta-local'    => '#0ea5e9',
            'postxagent'    => '#a855f7',
        ];
        $k = strtolower($name);
        if (isset($map[$k])) return $map[$k];
        $hue = abs(crc32($k)) % 360;
        return "hsl($hue, 70%, 55%)";
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function getHeroStats(string $period): array
    {
        $start = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            default => now()->startOfMonth(),
        };

        $stats = [
            'total_tokens' => 0,
            'total_cost_thb' => 0.0,
            'cache_hit_pct' => 0.0,
        ];

        try {
            if (Schema::hasTable('ai_api_key_usage_logs')) {
                // No cost column in ai_api_key_usage_logs — only tokens.
                // total_cost_thb stays at 0 until we wire a per-model pricing
                // lookup (ai_models.input_cost_per_1m etc.).
                $row = DB::table('ai_api_key_usage_logs')
                    ->selectRaw('SUM(total_tokens) as tokens')
                    ->where('created_at', '>=', $start)
                    ->first();

                if ($row) {
                    $stats['total_tokens'] = (int) ($row->tokens ?? 0);
                }
            }
        } catch (\Throwable $e) {
            //
        }

        return $stats;
    }

    private function getProvidersSummary(): array
    {
        try {
            $providers = AiProvider::query()
                ->where('is_active', true)
                ->orderBy('display_name')
                ->get();

            return $providers->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->display_name ?? $p->name,
                'type' => $p->provider_type,
                'is_available' => (bool) $p->is_available,
                'quota_pct' => $this->safeQuotaPct($p),
                'cost_thb' => $this->safeProviderCost($p),
            ])->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getBotsSummary(): array
    {
        try {
            return [
                'total' => AiBotProfile::count(),
                'active' => AiBotProfile::where('is_active', true)->count(),
                'rentable' => AiBotProfile::where('is_rentable', true)->count(),
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'active' => 0, 'rentable' => 0];
        }
    }

    private function getInferenceSummary(): array
    {
        $summary = [
            'p95_latency_ms' => 0,
            'requests_per_min' => 0,
            'errors_pct' => 0.0,
        ];

        try {
            if (Schema::hasTable('ai_api_key_usage_logs')) {
                $recent = DB::table('ai_api_key_usage_logs')
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->get(['response_time_ms', 'is_success']);

                if ($recent->count() > 0) {
                    $latencies = $recent->pluck('response_time_ms')->filter()->sort()->values();
                    $p95Idx = max(0, (int) ceil($latencies->count() * 0.95) - 1);
                    $summary['p95_latency_ms'] = (int) ($latencies[$p95Idx] ?? 0);
                    $summary['requests_per_min'] = (int) round($recent->count() / 15);
                    $summary['errors_pct'] = round(
                        $recent->where('is_success', 0)->count() / max(1, $recent->count()) * 100,
                        2
                    );
                }
            }
        } catch (\Throwable $e) {
            //
        }

        return $summary;
    }

    private function safeQuotaPct(AiProvider $provider): float
    {
        // หา quota จาก ai_provider_quotas table (ถ้ามี)
        try {
            if (Schema::hasTable('ai_provider_quotas')) {
                $q = DB::table('ai_provider_quotas')
                    ->where('provider_id', $provider->id)
                    ->where('period_start', '<=', now())
                    ->where('period_end', '>=', now())
                    ->first();

                if ($q && $q->limit_amount > 0) {
                    return round(($q->used_amount / $q->limit_amount) * 100, 1);
                }
            }
        } catch (\Throwable $e) {
            //
        }

        return 0.0;
    }

    private function safeProviderCost(AiProvider $provider): float
    {
        // No cost field on ai_api_key_usage_logs. Return 0 until pricing wired.
        return 0.0;
    }
}
