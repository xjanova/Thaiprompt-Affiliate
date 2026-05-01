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

        // ลอง query ai_request_logs (ถ้ามี) — fallback เป็น empty
        $series = [];

        try {
            if (Schema::hasTable('ai_request_logs')) {
                $series = DB::table('ai_request_logs')
                    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour, COUNT(*) as requests, AVG(latency_ms) as avg_latency_ms")
                    ->where('created_at', '>=', now()->subHours($hours))
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->get()
                    ->map(fn ($r) => [
                        'time' => (string) $r->hour,
                        'requests' => (int) $r->requests,
                        'avg_latency_ms' => round((float) ($r->avg_latency_ms ?? 0), 0),
                    ])
                    ->toArray();
            }
        } catch (\Throwable $e) {
            // เงียบ — fallback empty
        }

        return response()->json([
            'success' => true,
            'data' => [
                'hours' => $hours,
                'series' => $series,
            ],
        ]);
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
            if (Schema::hasTable('ai_request_logs')) {
                $row = DB::table('ai_request_logs')
                    ->selectRaw('SUM(total_tokens) as tokens, SUM(cost_thb) as cost, AVG(IF(cache_hit, 1, 0)) as cache_rate')
                    ->where('created_at', '>=', $start)
                    ->first();

                if ($row) {
                    $stats['total_tokens'] = (int) ($row->tokens ?? 0);
                    $stats['total_cost_thb'] = round((float) ($row->cost ?? 0), 2);
                    $stats['cache_hit_pct'] = round(((float) ($row->cache_rate ?? 0)) * 100, 1);
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
            if (Schema::hasTable('ai_request_logs')) {
                $recent = DB::table('ai_request_logs')
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->get(['latency_ms', 'is_error']);

                if ($recent->count() > 0) {
                    $latencies = $recent->pluck('latency_ms')->filter()->sort()->values();
                    $p95Idx = (int) ceil($latencies->count() * 0.95) - 1;
                    $summary['p95_latency_ms'] = (int) ($latencies[$p95Idx] ?? 0);
                    $summary['requests_per_min'] = (int) round($recent->count() / 15);
                    $summary['errors_pct'] = round(
                        $recent->where('is_error', 1)->count() / max(1, $recent->count()) * 100,
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
            if (Schema::hasTable('ai_request_logs')) {
                return (float) DB::table('ai_request_logs')
                    ->where('provider_id', $provider->id)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->sum('cost_thb');
            }
        } catch (\Throwable $e) {
            //
        }

        return 0.0;
    }
}
