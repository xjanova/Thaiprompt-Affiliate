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
                ->select('id', 'facebook_user_id', 'facebook_post_id', 'facebook_comment_id', 'comment_text', 'comment_reply', 'dm_message', 'engaged_at')
                ->where('engaged_at', '>=', $now->copy()->subDay())
                ->orderByDesc('engaged_at')
                ->limit(20)
                ->get()
                ->map(fn ($r) => [
                    'id' => (int) $r->id,
                    'fb_user_id' => $r->facebook_user_id,
                    'fb_post_id' => $r->facebook_post_id,
                    'fb_comment_id' => $r->facebook_comment_id,
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

    /**
     * GET /api/admin/fortune/triage/behavior?since_minutes=60
     *
     * Detects urgent customer cases from behavior signals — meant to flow into
     * the warroom triage queue alongside the row-level cases that come from
     * /fortune/readings.
     *
     * Sources combined:
     *   1. fortune_sensitive_events (mood_level >= 4 OR is_sensitive + complexity >= 4):
     *      customer is upset / emotional / multi-question dump
     *   2. fortune_admin_qa.q_text matched against an urgency keyword list:
     *      "โอนแล้ว", "ไม่เห็นเปิดไพ่", "ทำไมยังไม่", "เงินหาย", "เร็วๆ", etc.
     *   3. ai_api_key_usage_logs request_type='image_vision' burst (≥2 calls
     *      from same user-ish window) — customer is spamming transfer slips
     *      Note: usage logs don't carry user_id, so we approximate via
     *      timestamp proximity to a fortune_admin_qa row from the same user.
     *
     * Returned cases are de-duped per platform_user_id with the highest
     * severity reason winning.
     */
    public function triageBehavior(Request $request): JsonResponse
    {
        $since = (int) $request->input('since_minutes', 60);
        $since = max(5, min(720, $since));
        $now = now();
        $cutoff = $now->copy()->subMinutes($since);

        // Frustration / urgency keywords — customer is upset they paid but
        // haven't been served, or chasing admin.
        $urgencyKeywords = [
            'โอนแล้ว', 'จ่ายแล้ว', 'จ่ายเงินแล้ว', 'โอนเงินแล้ว',
            'ไม่เห็นเปิดไพ่', 'ไม่เปิดไพ่', 'เปิดไพ่ที', 'เปิดไพ่ยัง',
            'ทำไมยัง', 'ทำไมไม่', 'นานมาก', 'รอนาน',
            'เงินหาย', 'เงินไม่เข้า', 'หายไปไหน',
            'แอดมินอยู่ไหน', 'admin หาย', 'ไม่ตอบ',
            'รีบ', 'เร็วๆ', 'ด่วน',
        ];

        // Lead-intent keywords — customer is asking about price / how to
        // start. These are HOT leads, not complaints. Need a different action
        // (send price + QR), not retry.
        $leadKeywords = [
            'ค่าครู', 'ค่าดู', 'ค่าทำนาย', 'ค่าบริการ',
            'ราคา', 'ราคาเท่าไหร่', 'ราคาเท่าไร',
            'กี่บาท', 'กี่ตังค์', 'เท่าไหร่', 'เท่าไร', 'เท่าไหร่ครับ', 'เท่าไหร่ค่ะ',
            'ดูดวงเท่าไหร่', 'ดูฟรี',
            'จะดูดวง', 'อยากดูดวง', 'ขอดูดวง',
            'เริ่มยังไง', 'ทำยังไง', 'เริ่มดู',
        ];

        $cases = [];

        // ── 1. fortune_sensitive_events: high mood or complex sensitive cases ──
        if (Schema::hasTable('fortune_sensitive_events')) {
            $events = DB::table('fortune_sensitive_events')
                ->where('created_at', '>=', $cutoff)
                ->where(function ($q) {
                    $q->where('mood_level', '>=', 4)
                      ->orWhere(function ($qq) {
                          $qq->where('is_sensitive', 1)->where('complexity', '>=', 4);
                      });
                })
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            foreach ($events as $e) {
                $psid = $e->platform_user_id;
                if (! $psid) continue;
                $key = $e->platform . ':' . $psid;
                $reasons = $this->decodeReasons($e->reasons);
                if ($e->mood_level >= 4) $reasons[] = 'mood-' . $e->mood_level;
                if ((int) $e->is_offtopic === 1) $reasons[] = 'off-topic';
                if ((int) $e->complexity >= 4) $reasons[] = 'multi-question';

                $cases[$key] = $this->mergeCase($cases[$key] ?? null, [
                    'case_id' => 'beh-se-' . $e->id,
                    'platform' => $e->platform,
                    'fb_user_id' => $psid,
                    'kind' => 'emotional',
                    'severity' => ((int) $e->mood_level >= 5) ? 'crit' : 'warn',
                    'mood_level' => (int) $e->mood_level,
                    'reasons' => $reasons,
                    'preview' => $e->message_preview ?? '',
                    'context' => $e->context,
                    'last_at' => (string) $e->created_at,
                    'count' => 1,
                ]);
            }
        }

        // ── 2. fortune_admin_qa: keyword match in q_text ──
        if (Schema::hasTable('fortune_admin_qa')) {
            $qaRows = DB::table('fortune_admin_qa')
                ->where('created_at', '>=', $cutoff)
                ->whereNotNull('source_user_id')
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(['id', 'source_user_id', 'source_platform', 'reading_id', 'q_text', 'created_at']);

            foreach ($qaRows as $r) {
                $text = (string) ($r->q_text ?? '');
                if ($text === '') continue;
                $matchedUrg = [];
                foreach ($urgencyKeywords as $kw) {
                    if (mb_stripos($text, $kw) !== false) $matchedUrg[] = $kw;
                }
                $matchedLead = [];
                foreach ($leadKeywords as $kw) {
                    if (mb_stripos($text, $kw) !== false) $matchedLead[] = $kw;
                }
                if (empty($matchedUrg) && empty($matchedLead)) continue;

                $psid = $r->source_user_id;
                $platform = $r->source_platform ?? 'facebook';
                $key = $platform . ':' . $psid;
                $reasons = array_merge(
                    array_map(fn ($k) => 'keyword:' . $k, array_slice($matchedUrg, 0, 3)),
                    array_map(fn ($k) => 'lead:' . $k, array_slice($matchedLead, 0, 3)),
                );

                // Urgency takes priority: if there are frustration keywords
                // this is "frustration"; otherwise it's a fresh "lead".
                $kind = !empty($matchedUrg) ? 'frustration' : 'lead';
                $severity = !empty($matchedUrg)
                    ? (count($matchedUrg) >= 2 ? 'crit' : 'warn')
                    : 'warn'; // lead is always warn — not crit unless paid+stuck

                $cases[$key] = $this->mergeCase($cases[$key] ?? null, [
                    'case_id' => 'beh-kw-' . $r->id,
                    'platform' => $platform,
                    'fb_user_id' => $psid,
                    'reading_id' => $r->reading_id,
                    'kind' => $kind,
                    'severity' => $severity,
                    'reasons' => $reasons,
                    'preview' => mb_substr($text, 0, 80),
                    'last_at' => (string) $r->created_at,
                    'count' => 1,
                ]);
            }
        }

        // ── 3. Pure leads: customer created a fortune_reading in the last
        // window but never paid and has no other signal yet. They tapped
        // "ดูดวง" but stalled — perfect time for admin to nudge with price.
        if (Schema::hasTable('fortune_readings')) {
            $leadRows = DB::table('fortune_readings')
                ->where('created_at', '>=', $cutoff)
                ->where('is_paid', false)
                ->whereNull('paid_at')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(['id', 'facebook_user_id', 'facebook_user_name', 'reading_type', 'amount_paid', 'created_at']);

            foreach ($leadRows as $r) {
                $psid = $r->facebook_user_id;
                if (! $psid) continue;
                $key = 'facebook:' . $psid;
                // Don't downgrade an existing crit/frustration to a fresh lead.
                $existing = $cases[$key] ?? null;
                if ($existing && $existing['kind'] === 'frustration') continue;

                $ageSec = max(0, time() - strtotime((string) $r->created_at));
                // 5min < age < 60min is the sweet spot to ping. Newer = let the
                // bot do its thing first. Older = customer cooled off.
                if ($ageSec < 300) continue;

                $svc = $r->reading_type === 'celtic_cross' ? 'Celtic Cross' : 'ดูดวง 3 ใบ';
                $cases[$key] = $this->mergeCase($existing, [
                    'case_id' => 'beh-lead-' . $r->id,
                    'platform' => 'facebook',
                    'fb_user_id' => $psid,
                    'reading_id' => $r->id,
                    'kind' => 'lead',
                    'severity' => 'warn',
                    'reasons' => ['started-not-paid', 'service:' . $svc],
                    'preview' => 'เริ่มดูดวง ' . $svc . ' แต่ยังไม่จ่าย',
                    'last_at' => (string) $r->created_at,
                    'count' => 1,
                ]);
            }
        }

        // ── 4. Resolve customer name + reading from users / readings tables ──
        $keys = array_keys($cases);
        if (! empty($keys)) {
            // Pull all unique psids
            $psids = array_values(array_unique(array_map(fn ($k) => explode(':', $k, 2)[1] ?? '', $keys)));

            // Lookup users via email pattern (fb_<psid>@thaiprompt.local).
            $userMap = [];
            if (! empty($psids)) {
                $emails = array_map(fn ($p) => 'fb_' . $p . '@thaiprompt.local', $psids);
                foreach (DB::table('users')->whereIn('email', $emails)->get(['id', 'name', 'email']) as $u) {
                    if (preg_match('/^fb_(.+)@thaiprompt\.local$/', (string) $u->email, $m)) {
                        $userMap[$m[1]] = ['id' => $u->id, 'name' => $u->name];
                    }
                }
            }

            // Lookup the most recent reading per psid (for reading_id badge + name fallback).
            $readingMap = [];
            if (! empty($psids)) {
                foreach (
                    DB::table('fortune_readings')
                        ->whereIn('facebook_user_id', $psids)
                        ->orderByDesc('created_at')
                        ->limit(500)
                        ->get(['id', 'facebook_user_id', 'facebook_user_name', 'is_paid', 'paid_at', 'responded_at', 'created_at']) as $r
                ) {
                    if (! isset($readingMap[$r->facebook_user_id])) {
                        $readingMap[$r->facebook_user_id] = $r;
                    }
                }
            }

            foreach ($cases as $key => &$c) {
                $psid = $c['fb_user_id'];
                $u = $userMap[$psid] ?? null;
                $rd = $readingMap[$psid] ?? null;
                $c['customer'] = $u['name']
                    ?? ($rd->facebook_user_name ?? null)
                    ?? ('FB ' . substr($psid, -6));
                $c['user_id'] = $u['id'] ?? null;
                if (empty($c['reading_id']) && $rd) $c['reading_id'] = $rd->id;
                // Cross-reference: if customer paid but no reading yet, mark stuck-paid signal.
                if ($rd && (int) $rd->is_paid === 1 && empty($rd->responded_at)) {
                    $c['reasons'][] = 'stuck-paid';
                    $c['severity'] = 'crit';
                }
            }
            unset($c);
        }

        // Sort: crit first, then by recency.
        $list = array_values($cases);
        usort($list, function ($a, $b) {
            $sa = $a['severity'] === 'crit' ? 0 : 1;
            $sb = $b['severity'] === 'crit' ? 0 : 1;
            if ($sa !== $sb) return $sa - $sb;
            return strcmp($b['last_at'] ?? '', $a['last_at'] ?? '');
        });

        return response()->json([
            'success' => true,
            'data' => [
                'cases' => array_slice($list, 0, 30),
                'window_minutes' => $since,
                'crit_count' => count(array_filter($list, fn ($c) => $c['severity'] === 'crit')),
                'warn_count' => count(array_filter($list, fn ($c) => $c['severity'] === 'warn')),
                'generated_at' => $now->toIso8601String(),
            ],
        ]);
    }

    private function decodeReasons($raw): array
    {
        if (is_array($raw)) return array_values(array_filter($raw, fn ($x) => $x !== null));
        if (is_string($raw) && str_starts_with(trim($raw), '[')) {
            try {
                $j = json_decode($raw, true);
                if (is_array($j)) return array_values(array_filter($j, fn ($x) => $x !== null));
            } catch (\Throwable $e) {}
        }
        return [];
    }

    private function mergeCase(?array $existing, array $next): array
    {
        if (! $existing) return $next;
        // Keep the more recent last_at and the worse severity.
        $newer = strcmp($next['last_at'] ?? '', $existing['last_at'] ?? '') > 0;
        $worseSev = $next['severity'] === 'crit' || $existing['severity'] !== 'crit';
        return [
            'case_id' => $existing['case_id'],
            'platform' => $existing['platform'],
            'fb_user_id' => $existing['fb_user_id'],
            'reading_id' => $existing['reading_id'] ?? ($next['reading_id'] ?? null),
            'kind' => $existing['kind'] === 'emotional' ? 'emotional' : $next['kind'],
            'severity' => $worseSev ? $next['severity'] : $existing['severity'],
            'mood_level' => max((int) ($existing['mood_level'] ?? 0), (int) ($next['mood_level'] ?? 0)),
            'reasons' => array_values(array_unique(array_merge(
                $existing['reasons'] ?? [],
                $next['reasons'] ?? []
            ))),
            'preview' => $newer ? ($next['preview'] ?? '') : ($existing['preview'] ?? ''),
            'context' => $next['context'] ?? ($existing['context'] ?? null),
            'last_at' => $newer ? ($next['last_at'] ?? '') : ($existing['last_at'] ?? ''),
            'count' => (int) ($existing['count'] ?? 1) + (int) ($next['count'] ?? 1),
        ];
    }
}
