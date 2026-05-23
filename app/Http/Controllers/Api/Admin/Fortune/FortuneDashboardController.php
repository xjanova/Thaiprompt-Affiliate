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
     * Returns the realtime state of the Fortune AI worker pool. Powers warroom
     * /workers page. Workers themselves are Laravel queue processes (no per-row
     * registration), so we infer "in-flight" from rows that are paid but not
     * responded — the queue is processing them right now.
     *
     * Shape:
     *   {
     *     queue: { pending_paid, pending_unpaid, in_flight, stuck, completed_last_15m, failed_last_15m },
     *     throughput: { per_min, per_hour },
     *     latency: { avg_seconds, p95_seconds },
     *     in_flight: [ { reading_id, name, platform, comment_preview, created_at, age_seconds, paid } ],
     *     recent_completed: [ { reading_id, name, platform, ai_response_preview, latency_seconds, responded_at, provider } ],
     *     generated_at,
     *   }
     */
    public function workersQueue(Request $request): JsonResponse
    {
        if (! Schema::hasTable('fortune_readings')) {
            return response()->json([
                'success' => true,
                'data' => $this->emptyQueueShape(),
            ]);
        }

        $now = now();
        $stuckThresholdSec = 60;          // > 60s without a response = stuck
        $inFlightCutoff = $now->copy()->subMinutes(10); // ignore "pending" older than 10min (likely abandoned)

        // ── Queue counts ──
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
            ->where('created_at', '<', $now->copy()->subSeconds($stuckThresholdSec))
            ->where('created_at', '>=', $inFlightCutoff)
            ->count();
        $completed15 = DB::table('fortune_readings')
            ->whereNotNull('responded_at')
            ->where('responded_at', '>=', $now->copy()->subMinutes(15))
            ->count();
        // "failed" proxy: paid but no responded_at AND older than 10min — bot gave up.
        $failed15 = DB::table('fortune_readings')
            ->whereNull('responded_at')
            ->where('is_paid', true)
            ->where('created_at', '<', $inFlightCutoff)
            ->where('created_at', '>=', $now->copy()->subHour())
            ->count();

        // ── Throughput ──
        $throughputPerMin = (int) round($completed15 / 15);
        $completed60 = DB::table('fortune_readings')
            ->whereNotNull('responded_at')
            ->where('responded_at', '>=', $now->copy()->subHour())
            ->count();

        // ── Latency (responded_at - created_at) for completed-last-15m ──
        $latencies = DB::table('fortune_readings')
            ->selectRaw('TIMESTAMPDIFF(SECOND, created_at, responded_at) as lat')
            ->whereNotNull('responded_at')
            ->where('responded_at', '>=', $now->copy()->subMinutes(15))
            ->pluck('lat')
            ->filter(fn ($v) => $v !== null && $v >= 0)
            ->sort()
            ->values();
        $avgLat = $latencies->count() > 0 ? (float) ($latencies->avg() ?? 0) : 0;
        $p95Lat = $latencies->count() > 0
            ? (int) ($latencies[max(0, (int) ceil($latencies->count() * 0.95) - 1)] ?? 0)
            : 0;

        // ── In-flight rows (workers currently processing these) ──
        $inFlight = DB::table('fortune_readings as fr')
            ->select('fr.id', 'fr.facebook_user_name', 'fr.platform', 'fr.questions', 'fr.created_at', 'fr.is_paid')
            ->whereNull('fr.responded_at')
            ->where('fr.created_at', '>=', $inFlightCutoff)
            ->orderByDesc('fr.created_at')
            ->limit(12)
            ->get()
            ->map(function ($r) use ($now) {
                $age = (int) max(0, $now->diffInSeconds($r->created_at, false));
                $age = abs($age);
                $q = $this->parseQuestions($r->questions);
                return [
                    'reading_id' => (int) $r->id,
                    'name' => $r->facebook_user_name ?? '(ลูกค้า)',
                    'platform' => $r->platform ?? 'facebook',
                    'comment_preview' => mb_substr($q, 0, 80),
                    'created_at' => $r->created_at,
                    'age_seconds' => $age,
                    'paid' => (bool) $r->is_paid,
                ];
            });

        // ── Recent completed (worker → DM result log) ──
        // Activity log shows up to 30 most-recent across the last 24h so the
        // operator can still see history on slow days. The "completed_last_15m"
        // counter above stays at the 15-minute window for realtime metrics.
        $recent = DB::table('fortune_readings as fr')
            ->select('fr.id', 'fr.facebook_user_name', 'fr.platform', 'fr.questions', 'fr.ai_response', 'fr.responded_at', 'fr.created_at', 'fr.ai_provider')
            ->whereNotNull('fr.responded_at')
            ->where('fr.responded_at', '>=', $now->copy()->subDay())
            ->orderByDesc('fr.responded_at')
            ->limit(30)
            ->get()
            ->map(function ($r) {
                $lat = null;
                if ($r->created_at && $r->responded_at) {
                    $lat = max(0, strtotime($r->responded_at) - strtotime($r->created_at));
                }
                $q = $this->parseQuestions($r->questions);
                return [
                    'reading_id' => (int) $r->id,
                    'name' => $r->facebook_user_name ?? '(ลูกค้า)',
                    'platform' => $r->platform ?? 'facebook',
                    'comment_preview' => mb_substr($q, 0, 80),
                    'reply_preview' => mb_substr((string) ($r->ai_response ?? ''), 0, 120),
                    'latency_seconds' => $lat,
                    'responded_at' => $r->responded_at,
                    'provider' => $r->ai_provider,
                ];
            });

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
                    'per_min' => $throughputPerMin,
                    'per_hour' => $completed60,
                ],
                'latency' => [
                    'avg_seconds' => round($avgLat, 1),
                    'p95_seconds' => $p95Lat,
                ],
                'in_flight' => $inFlight,
                'recent_completed' => $recent,
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
            'latency' => ['avg_seconds' => 0, 'p95_seconds' => 0],
            'in_flight' => [], 'recent_completed' => [],
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
