<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\MlmMember;
use App\Models\User;
use App\Services\FortuneAffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Juntra (จันทรา.online) MLM consumer API.
 *
 * Read-only endpoints exposing the **fortune-telling slice** of the MLM tree
 * and commissions. NEVER returns marketplace, NFC, vendor, or TPIX data —
 * only what's tied to fortune_readings + fortune_commissions.
 *
 * Auth: Sanctum personal-access tokens.
 * - Normal user: can read their OWN tree + commissions.
 * - Admin (role 'admin'): can read ANY user's tree + commissions, list active
 *   fortune customers, and view aggregate stats.
 *
 * All output uses arrays (no Eloquent leakage) and short-lived per-user cache.
 */
class JuntraMlmApiController extends Controller
{
    /** Default depth so a single call doesn't pull a 5000-node tree. */
    private const DEFAULT_TREE_DEPTH = 5;
    private const MAX_TREE_DEPTH     = 10;

    /** Cache TTL (seconds) — short so admin actions feel live but we still cushion API. */
    private const CACHE_TTL = 300;

    /* =========================================================================
       GET /api/v1/juntra/mlm/tree?user_id=&depth=
       ========================================================================= */
    public function tree(Request $request): JsonResponse
    {
        $auth     = $request->user();
        $targetId = $this->resolveTargetUserId($request, $auth);
        if ($targetId instanceof JsonResponse) {
            return $targetId;
        }

        $depth = (int) $request->query('depth', self::DEFAULT_TREE_DEPTH);
        $depth = max(1, min($depth, self::MAX_TREE_DEPTH));

        $payload = Cache::remember(
            "juntra.mlm.tree.{$targetId}.d{$depth}",
            self::CACHE_TTL,
            fn () => $this->buildTree($targetId, $depth)
        );

        return response()->json($payload);
    }

    /* =========================================================================
       GET /api/v1/juntra/mlm/commissions?user_id=&page=&status=&from=&to=
       ========================================================================= */
    public function commissions(Request $request): JsonResponse
    {
        $auth     = $request->user();
        $targetId = $this->resolveTargetUserId($request, $auth);
        if ($targetId instanceof JsonResponse) {
            return $targetId;
        }

        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(5, min($perPage, 100));

        $q = FortuneCommission::query()
            ->where('user_id', $targetId)
            ->with(['fromUser:id,name,email', 'reading:id,facebook_user_name,amount_paid,paid_at'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($from = $request->query('from')) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $q->whereDate('created_at', '<=', $to);
        }

        $page = $q->paginate($perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($c) => [
                'id'             => $c->id,
                'level'          => (int) $c->level,
                'amount'         => (float) $c->amount,
                'commission_type'=> $c->commission_type,
                'commission_rate'=> (float) $c->commission_rate,
                'reading_price'  => (float) $c->reading_price,
                'status'         => $c->status,
                'from_user'      => $c->fromUser ? [
                    'id'   => $c->fromUser->id,
                    'name' => $c->fromUser->name,
                ] : null,
                'reading' => $c->reading ? [
                    'id'         => $c->reading->id,
                    'customer'   => $c->reading->facebook_user_name,
                    'amount'     => (float) $c->reading->amount_paid,
                    'paid_at'    => optional($c->reading->paid_at)->toIso8601String(),
                ] : null,
                'created_at'  => $c->created_at?->toIso8601String(),
                'approved_at' => $c->approved_at?->toIso8601String(),
                'paid_at'     => $c->paid_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
            ],
        ]);
    }

    /* =========================================================================
       GET /api/v1/juntra/mlm/stats?user_id=
       ========================================================================= */
    public function stats(Request $request): JsonResponse
    {
        $auth     = $request->user();
        $targetId = $this->resolveTargetUserId($request, $auth);
        if ($targetId instanceof JsonResponse) {
            return $targetId;
        }

        $payload = Cache::remember(
            "juntra.mlm.stats.{$targetId}",
            self::CACHE_TTL,
            fn () => $this->buildStats($targetId)
        );

        return response()->json($payload);
    }

    /* =========================================================================
       POST /api/v1/juntra/mlm/claim-referral   {code: <member_code>}

       จันทรา.online referral attribution: a visitor lands on จันทรา.online/r/{code}
       (code = the inviter's MlmMember.member_code), juntraweb stores it in a
       30-day cookie, and after the user links Thaiprompt (OAuth) juntraweb
       calls this with the NEW user's token to enroll them under the inviter.

       Conservative by design: if the caller already has an MlmMember we NEVER
       re-parent (unilevel_path of the whole downline would break) — we return
       409 `already_enrolled` and juntraweb clears its cookie.
       ========================================================================= */
    public function claimReferral(Request $request): JsonResponse
    {
        $auth = $request->user();
        if (! $auth) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $request->validate(['code' => 'required|string|max:64']);
        $code = substr(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $request->input('code')), 0, 64);
        if ($code === '') {
            return response()->json(['claimed' => false, 'reason_code' => 'invalid_code'], 404);
        }

        $sponsor = MlmMember::where('member_code', $code)->first();
        if (! $sponsor) {
            return response()->json(['claimed' => false, 'reason_code' => 'invalid_code'], 404);
        }
        if ((int) $sponsor->user_id === (int) $auth->id) {
            return response()->json(['claimed' => false, 'reason_code' => 'self_referral'], 422);
        }

        $existing = MlmMember::where('user_id', $auth->id)->first();
        if ($existing) {
            return response()->json([
                'claimed'     => false,
                'reason_code' => 'already_enrolled',
                'member_code' => $existing->member_code,
            ], 409);
        }

        $member = app(FortuneAffiliateService::class)->enrollUserUnderSponsor($auth, $sponsor);
        if (! $member) {
            return response()->json(['claimed' => false, 'reason_code' => 'enrollment_failed'], 503);
        }

        // Bust cached stats/trees for everyone whose dashboard just changed:
        // the new member, the sponsor, and every ancestor up the unilevel path.
        $this->forgetJuntraCachesFor($auth->id);
        $this->forgetJuntraCachesFor((int) $sponsor->user_id);
        $ancestorIds = array_filter(explode('/', (string) $sponsor->unilevel_path));
        if ($ancestorIds !== []) {
            MlmMember::whereIn('id', $ancestorIds)->pluck('user_id')
                ->each(fn ($uid) => $this->forgetJuntraCachesFor((int) $uid));
        }

        return response()->json([
            'claimed'     => true,
            'member_code' => $member->member_code,
            'sponsor'     => [
                'name'        => $sponsor->user?->name,
                'member_code' => $sponsor->member_code,
            ],
        ], 201);
    }

    /* =========================================================================
       GET /api/v1/juntra/mlm/users  (admin only)
       List of users who have fortune activity — drives the admin "view any user"
       picker on the juntra dashboard.
       ========================================================================= */
    public function users(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request->user())) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $search  = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(10, min($perPage, 200));

        $q = User::query()
            ->whereIn('id', function ($sub) {
                // Anyone who has earned commission OR paid for a reading.
                $sub->select('user_id')->from('fortune_commissions')
                    ->union(
                        DB::table('fortune_readings')->select('user_id')->whereNotNull('user_id')
                    );
            })
            ->select(['id', 'name', 'email']);

        if ($search !== '') {
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        $page = $q->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
            ])->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'total'        => $page->total(),
            ],
        ]);
    }

    /* ============================================================
       INTERNAL
       ============================================================ */

    /**
     * Resolve who we're querying for.
     *  - If `user_id` query param is given AND caller is admin → use it.
     *  - If `user_id` is given but caller is NOT admin → 403.
     *  - Otherwise → caller's own id.
     */
    private function resolveTargetUserId(Request $request, ?User $auth): int|JsonResponse
    {
        if (!$auth) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $explicit = $request->query('user_id');
        if ($explicit !== null && (string) $explicit !== (string) $auth->id) {
            if (!$this->isAdmin($auth)) {
                return response()->json(['error' => 'forbidden — admin only'], 403);
            }
            return (int) $explicit;
        }
        return (int) $auth->id;
    }

    private function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return method_exists($user, 'hasRole') ? $user->hasRole('admin') : false;
    }

    /**
     * Build a recursive downline tree using the `unilevel_path` materialized
     * column from `mlm_members` (already maintained by the MLM core).
     *
     * Returns ['user' => ..., 'tree' => ..., 'depth_returned' => N, 'total_descendants' => N].
     */
    private function buildTree(int $rootUserId, int $depth): array
    {
        $rootMember = MlmMember::where('user_id', $rootUserId)->first();
        if (!$rootMember) {
            return [
                'user'              => $this->slimUser($rootUserId),
                'tree'              => null,
                'depth_returned'    => 0,
                'total_descendants' => 0,
                'note'              => 'user is not enrolled in MLM',
            ];
        }

        // Pull all descendants up to $depth via unilevel_path LIKE.
        $rootPath = $rootMember->unilevel_path ?: (string) $rootMember->id;

        $allMembers = MlmMember::query()
            ->where('unilevel_path', 'LIKE', $rootPath . '%')
            ->where('unilevel_level', '<=', ($rootMember->unilevel_level ?? 0) + $depth)
            ->with('user:id,name,email')
            ->get(['id', 'user_id', 'unilevel_sponsor_id', 'unilevel_level', 'unilevel_path',
                   'total_pv', 'total_team_pv', 'total_earnings', 'total_direct_referrals',
                   'total_team_members', 'status']);

        // Restrict commission lookup to FORTUNE only — sum per member.
        $memberIds = $allMembers->pluck('id')->all();
        $fortuneSums = FortuneCommission::query()
            ->whereIn('mlm_member_id', $memberIds)
            ->select('mlm_member_id', DB::raw('SUM(amount) as total'))
            ->groupBy('mlm_member_id')
            ->pluck('total', 'mlm_member_id');

        // Index by sponsor for O(N) tree assembly.
        $bySponsor = $allMembers->groupBy('unilevel_sponsor_id');

        $build = function (MlmMember $node, int $level) use (&$build, $bySponsor, $fortuneSums) {
            $children = $bySponsor->get($node->id, collect());
            return [
                'id'     => $node->id,
                'user_id'=> $node->user_id,
                'name'   => $node->user?->name ?? "Member #{$node->id}",
                'level'  => $level,
                'status' => $node->status,
                'personal_volume'    => (float) $node->total_pv,
                'team_volume'        => (float) $node->total_team_pv,
                'fortune_commission' => (float) ($fortuneSums[$node->id] ?? 0),
                'direct_referrals'   => (int) $node->total_direct_referrals,
                'total_team_members' => (int) $node->total_team_members,
                'children'           => $children->map(fn ($c) => $build($c, $level + 1))->values()->all(),
            ];
        };

        return [
            'user'              => $this->slimUser($rootUserId),
            'tree'              => $build($rootMember, 0),
            'depth_returned'    => $depth,
            'total_descendants' => max(0, $allMembers->count() - 1),
        ];
    }

    /** KPI strip data — what the dashboard cards show. */
    private function buildStats(int $userId): array
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();
        $year  = now()->startOfYear();

        $base = FortuneCommission::query()->where('user_id', $userId);

        return [
            'user'    => $this->slimUser($userId),
            'totals'  => [
                'today'       => (float) (clone $base)->where('created_at', '>=', $today)->sum('amount'),
                'this_month'  => (float) (clone $base)->where('created_at', '>=', $month)->sum('amount'),
                'this_year'   => (float) (clone $base)->where('created_at', '>=', $year)->sum('amount'),
                'all_time'    => (float) (clone $base)->sum('amount'),
                'pending'     => (float) (clone $base)->where('status', FortuneCommission::STATUS_PENDING)->sum('amount'),
                'paid'        => (float) (clone $base)->where('status', FortuneCommission::STATUS_PAID)->sum('amount'),
            ],
            'counts' => [
                'commissions_total' => (int) (clone $base)->count(),
                'unique_customers'  => (int) (clone $base)->distinct('from_user_id')->count('from_user_id'),
            ],
            'monthly_series' => $this->monthlyEarnings($userId, 12),
            'mlm'           => $this->memberSummary($userId),
        ];
    }

    /** [{label: 'Jan 26', amount: 1234.5}, ...] for the dashboard line chart. */
    private function monthlyEarnings(int $userId, int $months): array
    {
        $from = now()->subMonths($months - 1)->startOfMonth();
        $rows = FortuneCommission::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $from)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $cursor = now()->subMonths($months - 1 - $i)->startOfMonth();
            $key    = $cursor->format('Y-m');
            $out[]  = [
                'label'  => $cursor->format('M y'),
                'amount' => (float) ($rows->get($key)->total ?? 0),
            ];
        }
        return $out;
    }

    private function memberSummary(int $userId): ?array
    {
        $m = MlmMember::where('user_id', $userId)->first();
        if (!$m) {
            return null;
        }
        return [
            'member_code'        => $m->member_code,
            'unilevel_level'     => (int) ($m->unilevel_level ?? 0),
            'direct_referrals'   => (int) $m->total_direct_referrals,
            'total_team_members' => (int) $m->total_team_members,
            'total_pv'           => (float) $m->total_pv,
            'total_team_pv'      => (float) $m->total_team_pv,
            'status'             => $m->status,
        ];
    }

    private function slimUser(int $userId): ?array
    {
        $u = User::find($userId);
        if (! $u) {
            return null;
        }

        // referral_code = MlmMember.member_code — จันทรา.online builds its
        // invite link (จันทรา.online/r/{code}) from this; claim-referral
        // resolves the same code back to the sponsor. Null until enrolled.
        return [
            'id'            => $u->id,
            'name'          => $u->name,
            'email'         => $u->email,
            'referral_code' => MlmMember::where('user_id', $u->id)->value('member_code'),
        ];
    }

    /** Drop every juntra MLM cache entry for a user (stats + all tree depths). */
    private function forgetJuntraCachesFor(int $userId): void
    {
        Cache::forget("juntra.mlm.stats.{$userId}");
        for ($d = 1; $d <= self::MAX_TREE_DEPTH; $d++) {
            Cache::forget("juntra.mlm.tree.{$userId}.d{$d}");
        }
    }
}
