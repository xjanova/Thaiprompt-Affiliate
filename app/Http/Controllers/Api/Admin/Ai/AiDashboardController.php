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

        // ai_usage_logs is the real usage tracker (created by FortuneAIService etc.).
        // Bucket per hour for hours<=24, per day otherwise so the chart stays readable.
        $series = [];
        try {
            if (Schema::hasTable('ai_usage_logs')) {
                $format = $hours <= 24 ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d 00:00:00';
                $series = DB::table('ai_usage_logs')
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

        $providers = [];
        try {
            $providerRows = AiProvider::query()
                ->orderBy('display_name')
                ->get();

            if (! Schema::hasTable('ai_usage_logs')) {
                $providers = $providerRows->map(fn ($p) => $this->emptyProviderUsage($p, $bucketCount))->toArray();
            } else {
                // Single aggregate query keyed by provider — avoid N+1.
                $agg = DB::table('ai_usage_logs')
                    ->selectRaw('provider_id, COUNT(*) as requests, SUM(total_tokens) as tokens, SUM(cost) as cost, AVG(response_time_ms) as avg_ms, SUM(IF(status="error",1,0)) as errors')
                    ->where('created_at', '>=', $since)
                    ->groupBy('provider_id')
                    ->get()
                    ->keyBy('provider_id');

                // p95 latency per provider — separate pass over latencies.
                $latenciesByProvider = DB::table('ai_usage_logs')
                    ->select('provider_id', 'response_time_ms')
                    ->where('created_at', '>=', $since)
                    ->whereNotNull('response_time_ms')
                    ->get()
                    ->groupBy('provider_id');

                // Series — single grouped query so we can slot into per-provider buckets.
                $seriesRows = DB::table('ai_usage_logs')
                    ->selectRaw("provider_id, DATE_FORMAT(created_at, '$bucketFormat') as time, COUNT(*) as requests, SUM(total_tokens) as tokens")
                    ->where('created_at', '>=', $since)
                    ->groupBy('provider_id', 'time')
                    ->orderBy('time')
                    ->get()
                    ->groupBy('provider_id');

                $providers = $providerRows->map(function ($p) use ($agg, $latenciesByProvider, $seriesRows, $bucketCount) {
                    $a = $agg->get($p->id);
                    $lats = ($latenciesByProvider->get($p->id) ?? collect())->pluck('response_time_ms')->sort()->values();
                    $p95 = $lats->count() > 0 ? (int) ($lats[(int) ceil($lats->count() * 0.95) - 1] ?? 0) : 0;
                    $reqs = (int) ($a->requests ?? 0);
                    $errors = (int) ($a->errors ?? 0);
                    $series = ($seriesRows->get($p->id) ?? collect())->map(fn ($r) => [
                        'time' => (string) $r->time,
                        'tokens' => (int) ($r->tokens ?? 0),
                        'requests' => (int) ($r->requests ?? 0),
                    ])->toArray();

                    return [
                        'provider_id' => $p->id,
                        'name' => $p->name,
                        'display_name' => $p->display_name ?? $p->name,
                        'type' => $p->provider_type ?? null,
                        'is_active' => (bool) $p->is_active,
                        'is_available' => (bool) ($p->is_available ?? false),
                        'color' => $this->providerColor($p->name),
                        'requests' => $reqs,
                        'tokens' => (int) ($a->tokens ?? 0),
                        'cost_usd' => round((float) ($a->cost ?? 0), 4),
                        'avg_latency_ms' => (int) round((float) ($a->avg_ms ?? 0)),
                        'p95_latency_ms' => $p95,
                        'error_rate_pct' => $reqs > 0 ? round(($errors / $reqs) * 100, 1) : 0.0,
                        'series' => $series,
                        'series_buckets' => $bucketCount,
                    ];
                })->toArray();
            }
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

    private function emptyProviderUsage(AiProvider $p, int $buckets): array
    {
        return [
            'provider_id' => $p->id,
            'name' => $p->name,
            'display_name' => $p->display_name ?? $p->name,
            'type' => $p->provider_type ?? null,
            'is_active' => (bool) $p->is_active,
            'is_available' => (bool) ($p->is_available ?? false),
            'color' => $this->providerColor($p->name),
            'requests' => 0,
            'tokens' => 0,
            'cost_usd' => 0.0,
            'avg_latency_ms' => 0,
            'p95_latency_ms' => 0,
            'error_rate_pct' => 0.0,
            'series' => [],
            'series_buckets' => $buckets,
        ];
    }

    private function providerColor(string $name): string
    {
        // Stable brand-ish colors so warroom can draw the chart without storing
        // a color per row. Falls through to a deterministic hash.
        $map = [
            'openai'        => '#10b981',
            'anthropic'     => '#d4a747',
            'groq'          => '#22d3ee',
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
        // Deterministic fallback hue from name hash.
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
            if (Schema::hasTable('ai_usage_logs')) {
                // ai_usage_logs.cost is USD (decimal 10,6). UI key kept as
                // total_cost_thb for backward compat with existing mobile app —
                // value is now in USD; convert at the consumer when needed.
                // No cache_hit column → always 0.
                $row = DB::table('ai_usage_logs')
                    ->selectRaw('SUM(total_tokens) as tokens, SUM(cost) as cost')
                    ->where('created_at', '>=', $start)
                    ->first();

                if ($row) {
                    $stats['total_tokens'] = (int) ($row->tokens ?? 0);
                    $stats['total_cost_thb'] = round((float) ($row->cost ?? 0), 2);
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
            if (Schema::hasTable('ai_usage_logs')) {
                $recent = DB::table('ai_usage_logs')
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->get(['response_time_ms', 'status']);

                if ($recent->count() > 0) {
                    $latencies = $recent->pluck('response_time_ms')->filter()->sort()->values();
                    $p95Idx = max(0, (int) ceil($latencies->count() * 0.95) - 1);
                    $summary['p95_latency_ms'] = (int) ($latencies[$p95Idx] ?? 0);
                    $summary['requests_per_min'] = (int) round($recent->count() / 15);
                    $summary['errors_pct'] = round(
                        $recent->where('status', 'error')->count() / max(1, $recent->count()) * 100,
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
        try {
            if (Schema::hasTable('ai_usage_logs')) {
                return (float) DB::table('ai_usage_logs')
                    ->where('provider_id', $provider->id)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->sum('cost');
            }
        } catch (\Throwable $e) {
            //
        }

        return 0.0;
    }
}
