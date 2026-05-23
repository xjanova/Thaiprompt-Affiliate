<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneUserBan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Admin Mobile API: ModerationController
 *
 * Powers the Warroom Juntra `/moderation` page: suspect detection on recent
 * Fortune readings + active ban list management + simple keyword auto-rules.
 *
 * Suspect detection is a heuristic-only first pass — it scans the user
 * questions + AI response of recent readings for patterns commonly
 * associated with disputes, refund threats, or abusive language. The list
 * is informational; operators decide whether to ban via POST /ban.
 */
class ModerationController extends Controller
{
    /**
     * Built-in heuristic keyword list used by suspects(). Operator can add
     * more via auto-rules; we merge those in at runtime.
     */
    private const DEFAULT_KEYWORDS = [
        // Refund / dispute pressure
        'คืนเงิน', 'คืนตังค์', 'ขอเงินคืน', 'ทวงเงิน', 'หลอกลวง', 'โกง', 'มิจฉาชีพ',
        // Abuse / threat
        'แจ้งความ', 'ดำเนินคดี', 'ฟ้องร้อง', 'ฟ้องตำรวจ', 'ผู้บริโภค', 'สคบ',
        // Profanity (mild — refine over time)
        'ไอ้สัด', 'เหี้ย', 'ควาย', 'shit', 'fuck',
    ];

    /**
     * GET /api/admin/moderation/suspects
     *
     * Query:
     *   ?since_hours=24 (default — scan window for recent readings)
     *   ?min_score=1 (default — heuristic hit count threshold)
     *   ?per_page=20 (1-100)
     */
    public function suspects(Request $request): JsonResponse
    {
        $sinceHours = (int) $request->input('since_hours', 24);
        $sinceHours = max(1, min(720, $sinceHours)); // cap at 30 days
        $minScore = max(1, (int) $request->input('min_score', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));

        $since = Carbon::now()->subHours($sinceHours);
        $keywords = $this->loadKeywords();

        // Pull a bounded recent window — never scan the full table.
        $candidates = FortuneReading::query()
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(2000)
            ->get([
                'id', 'user_id', 'facebook_user_id', 'facebook_user_name',
                'questions', 'ai_response', 'response_type', 'is_paid',
                'rating', 'created_at',
            ]);

        $hits = [];
        foreach ($candidates as $r) {
            $questions = is_array($r->questions) ? $r->questions : [];
            $haystack = mb_strtolower(
                implode(' ', $questions) . ' ' . (string) $r->ai_response
            );

            $matched = [];
            foreach ($keywords as $kw) {
                if ($kw !== '' && mb_strpos($haystack, mb_strtolower($kw)) !== false) {
                    $matched[] = $kw;
                }
            }

            // Low rating is another suspect signal even without keyword hits.
            $ratingFlag = $r->rating !== null && (int) $r->rating <= 2;

            $score = count($matched) + ($ratingFlag ? 1 : 0);
            if ($score < $minScore) {
                continue;
            }

            $hits[] = [
                'reading_id' => $r->id,
                'user_id' => $r->user_id,
                'platform_user_id' => $r->facebook_user_id,
                'display_name' => $r->facebook_user_name,
                'response_type' => $r->response_type,
                'is_paid' => (bool) $r->is_paid,
                'rating' => $r->rating !== null ? (int) $r->rating : null,
                'created_at' => $r->created_at?->toIso8601String(),
                'matched_keywords' => $matched,
                'score' => $score,
                'flags' => array_filter([
                    $ratingFlag ? 'low_rating' : null,
                    count($matched) ? 'keyword_hit' : null,
                ]),
                'preview' => $this->buildPreview($questions, $r->ai_response, $matched),
            ];
        }

        // Sort by score desc, then most recent.
        usort($hits, function ($a, $b) {
            return $b['score'] <=> $a['score'] ?: strcmp($b['created_at'], $a['created_at']);
        });

        $total = count($hits);
        $hits = array_slice($hits, 0, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $hits,
                'total' => $total,
                'window_hours' => $sinceHours,
                'keywords_used' => $keywords,
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/admin/moderation/banned
     *
     * Query:
     *   ?platform=facebook|line
     *   ?search= (display_name / platform_user_id)
     *   ?active_only=1 (default — exclude permanent bans? no, exclude expired)
     *   ?per_page=20
     */
    public function banned(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
        $activeOnly = (bool) $request->input('active_only', true);

        $query = FortuneUserBan::query()
            ->with(['bannedBy:id,name,email'])
            ->orderByDesc('created_at');

        if ($platform = $request->input('platform')) {
            $query->where('platform', $platform);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                  ->orWhere('platform_user_id', 'like', "%{$search}%");
            });
        }
        if ($activeOnly) {
            $query->where(function ($q) {
                $q->whereNull('banned_until')
                  ->orWhere('banned_until', '>', now());
            });
        }

        $page = $query->paginate($perPage);

        $data = $page->getCollection()->map(function (FortuneUserBan $ban) {
            return [
                'id' => $ban->id,
                'platform' => $ban->platform,
                'platform_user_id' => $ban->platform_user_id,
                'display_name' => $ban->display_name,
                'reason' => $ban->reason,
                'banned_until' => $ban->banned_until?->toIso8601String(),
                'is_permanent' => $ban->banned_until === null,
                'is_active' => $ban->banned_until === null || $ban->banned_until->isFuture(),
                'attempt_count' => (int) $ban->attempt_count,
                'notify_count' => (int) $ban->notify_count,
                'last_notified_at' => $ban->last_notified_at?->toIso8601String(),
                'banned_by' => $ban->bannedBy ? [
                    'id' => $ban->bannedBy->id,
                    'name' => $ban->bannedBy->name,
                    'email' => $ban->bannedBy->email,
                ] : null,
                'created_at' => $ban->created_at?->toIso8601String(),
            ];
        })->all();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * POST /api/admin/moderation/ban
     * Body: { platform, platform_user_id, display_name?, reason?, banned_until? (ISO8601 or null for permanent) }
     */
    public function ban(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => 'required|in:facebook,line',
            'platform_user_id' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:500',
            'banned_until' => 'nullable|date',
        ]);

        $admin = $request->user();

        $ban = FortuneUserBan::updateOrCreate(
            [
                'platform' => $data['platform'],
                'platform_user_id' => $data['platform_user_id'],
            ],
            [
                'display_name' => $data['display_name'] ?? null,
                'reason' => $data['reason'] ?? 'banned_by_admin',
                'banned_until' => $data['banned_until'] ?? null,
                'banned_by' => $admin?->id,
            ],
        );

        Log::info('Moderation: user banned', [
            'ban_id' => $ban->id,
            'platform' => $ban->platform,
            'platform_user_id' => $ban->platform_user_id,
            'admin_id' => $admin?->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['ban' => $ban->fresh()],
            'message' => 'แบนผู้ใช้สำเร็จ',
        ]);
    }

    /**
     * POST /api/admin/moderation/unban/{ban}
     */
    public function unban(FortuneUserBan $ban, Request $request): JsonResponse
    {
        $admin = $request->user();
        $platform = $ban->platform;
        $platformUserId = $ban->platform_user_id;

        // We don't physically delete — set banned_until to a past time so
        // the row stays for audit but the user is no longer banned.
        $ban->banned_until = now()->subSecond();
        $ban->save();

        Log::info('Moderation: user unbanned', [
            'ban_id' => $ban->id,
            'platform' => $platform,
            'platform_user_id' => $platformUserId,
            'admin_id' => $admin?->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['ban' => $ban->fresh()],
            'message' => 'ปลดแบนสำเร็จ',
        ]);
    }

    /**
     * GET /api/admin/moderation/rules
     *
     * Auto-moderation rules — currently a hybrid: built-in keyword list +
     * any extra keywords stashed in the `app_settings` cache key
     * `moderation.keywords` (one keyword per line).
     */
    public function rules(): JsonResponse
    {
        $extra = $this->loadExtraKeywords();

        return response()->json([
            'success' => true,
            'data' => [
                'default_keywords' => self::DEFAULT_KEYWORDS,
                'extra_keywords' => $extra,
                'all_keywords' => array_values(array_unique(array_merge(self::DEFAULT_KEYWORDS, $extra))),
                'description' => 'Default keywords are baked into the controller. Extra keywords are operator-editable via PUT /rules.',
            ],
        ]);
    }

    /**
     * PUT /api/admin/moderation/rules
     * Body: { extra_keywords: string[] }
     */
    public function updateRules(Request $request): JsonResponse
    {
        $data = $request->validate([
            'extra_keywords' => 'nullable|array',
            'extra_keywords.*' => 'string|max:120',
        ]);

        $extra = array_values(array_unique(array_filter(array_map('trim', $data['extra_keywords'] ?? []))));

        cache()->forever('moderation.extra_keywords', $extra);

        Log::info('Moderation: extra keywords updated', [
            'count' => count($extra),
            'admin_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['extra_keywords' => $extra],
            'message' => 'อัพเดทคำสำคัญสำเร็จ',
        ]);
    }

    private function loadKeywords(): array
    {
        return array_values(array_unique(array_merge(
            self::DEFAULT_KEYWORDS,
            $this->loadExtraKeywords()
        )));
    }

    private function loadExtraKeywords(): array
    {
        $cached = cache()->get('moderation.extra_keywords');
        if (! is_array($cached)) {
            return [];
        }
        return array_values(array_filter($cached, fn ($s) => is_string($s) && $s !== ''));
    }

    private function buildPreview(array $questions, ?string $aiResponse, array $matched): string
    {
        $first = $questions[0] ?? '';
        $response = (string) $aiResponse;

        // Try to surface a snippet that contains a matched keyword. Fall back
        // to first question, then first 120 chars of the AI reply.
        foreach ($matched as $kw) {
            $pos = mb_stripos($first, $kw);
            if ($pos !== false) {
                return $this->snippet($first, $pos, 120);
            }
            $pos = mb_stripos($response, $kw);
            if ($pos !== false) {
                return $this->snippet($response, $pos, 120);
            }
        }
        return mb_substr($first ?: $response, 0, 160);
    }

    private function snippet(string $haystack, int $pos, int $window): string
    {
        $start = max(0, $pos - 40);
        $sub = mb_substr($haystack, $start, $window);
        return ($start > 0 ? '…' : '') . trim($sub) . (mb_strlen($haystack) > $start + $window ? '…' : '');
    }
}
