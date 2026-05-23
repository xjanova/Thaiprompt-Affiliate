<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserListResource;
use App\Models\FortuneReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin Mobile API: Users management
 *
 * รายชื่อสมาชิก + filter + detail
 */
class UsersController extends Controller
{
    /**
     * GET /api/admin/users
     *
     * Query: ?search=&role=&rank_id=&blocked=&page=
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['wallet', 'currentRank']);

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('referral_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('rank_id')) {
            $query->where('current_rank_id', (int) $request->input('rank_id'));
        }

        if ($request->has('blocked')) {
            if ($request->boolean('blocked')) {
                $query->whereNotNull('blocked_at');
            } else {
                $query->whereNull('blocked_at');
            }
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $users = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AdminUserListResource::collection($users)->response()->getData(true),
        ]);
    }

    /**
     * GET /api/admin/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['wallet', 'currentRank']);

        return response()->json([
            'success' => true,
            'data' => new AdminUserListResource($user),
        ]);
    }

    /**
     * GET /api/admin/users/stats
     */
    public function stats(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => User::count(),
                    'active' => User::whereNull('blocked_at')->count(),
                    'blocked' => User::whereNotNull('blocked_at')->count(),
                    'super_admins' => User::where('is_super_admin', true)->count(),
                    'admins' => User::where('role', 'admin')->count(),
                    'new_today' => User::whereDate('created_at', today())->count(),
                    'new_this_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => true, 'data' => [
                'total' => 0, 'active' => 0, 'blocked' => 0,
                'super_admins' => 0, 'admins' => 0,
                'new_today' => 0, 'new_this_week' => 0,
            ]]);
        }
    }

    /**
     * GET /api/admin/users/{user}/readings
     *
     * Customer 360 drawer — recent fortune readings for one user. Matches
     * either by user_id (logged-in users) or by facebook_user_id (FB-only
     * users who haven't linked an account yet).
     *
     * Query: ?per_page=10 (1-50)
     */
    public function readings(User $user, Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->input('per_page', 10)));

        $readings = FortuneReading::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                // Also pick up any FB-only readings tied to this user's FB OAuth id
                // — common after late account creation. Cheap because we
                // already have the user row loaded.
                if (! empty($user->facebook_user_id)) {
                    $q->orWhere('facebook_user_id', $user->facebook_user_id);
                }
            })
            ->orderByDesc('created_at')
            ->limit($perPage)
            ->get([
                'id', 'user_id', 'facebook_user_id', 'facebook_user_name',
                'questions', 'ai_provider', 'ai_model', 'tokens_used',
                'is_paid', 'price_paid', 'rating', 'response_type',
                'paid_at', 'responded_at', 'created_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $readings->map(fn (FortuneReading $r) => [
                    'id' => $r->id,
                    'questions' => is_array($r->questions) ? $r->questions : [],
                    'ai_provider' => $r->ai_provider,
                    'ai_model' => $r->ai_model,
                    'tokens_used' => $r->tokens_used,
                    'is_paid' => (bool) $r->is_paid,
                    'price_paid_thb' => $r->price_paid !== null ? (float) $r->price_paid : null,
                    'rating' => $r->rating !== null ? (int) $r->rating : null,
                    'response_type' => $r->response_type,
                    'paid_at' => $r->paid_at?->toIso8601String(),
                    'responded_at' => $r->responded_at?->toIso8601String(),
                    'created_at' => $r->created_at?->toIso8601String(),
                ])->all(),
                'total' => $readings->count(),
            ],
        ]);
    }

    /**
     * GET /api/admin/users/admins/online
     *
     * Lightweight admin presence — operators who have logged in recently
     * via the admin API. "Online" is approximate: anyone whose token was
     * used in the last 15 minutes. Used by the warroom TopBar to render
     * the right-side presence avatar strip.
     */
    public function adminsOnline(): JsonResponse
    {
        $since = now()->subMinutes(15);

        // personal_access_tokens.last_used_at is updated by Sanctum on every
        // authenticated request. Cheap enough to join on for a presence
        // strip (max ~10-20 admin rows in practice).
        $admins = User::query()
            ->where(function ($q) {
                $q->where('role', 'admin')->orWhere('is_super_admin', true);
            })
            ->whereNull('blocked_at')
            ->select([
                'id', 'name', 'email', 'avatar', 'role', 'is_super_admin',
            ])
            ->limit(40)
            ->get();

        // Resolve last_used_at per admin from their newest token.
        $adminIds = $admins->pluck('id')->all();
        $lastSeenMap = collect();
        if ($adminIds) {
            $lastSeenMap = DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $adminIds)
                ->whereNotNull('last_used_at')
                ->selectRaw('tokenable_id, MAX(last_used_at) as last_used_at')
                ->groupBy('tokenable_id')
                ->pluck('last_used_at', 'tokenable_id');
        }

        $data = $admins->map(function (User $u) use ($lastSeenMap, $since) {
            $lastSeen = $lastSeenMap->get($u->id);
            $lastSeenAt = $lastSeen ? Carbon::parse($lastSeen) : null;
            $isOnline = $lastSeenAt && $lastSeenAt->gte($since);
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatar,
                'role' => $u->is_super_admin ? 'super_admin' : ($u->role ?? 'admin'),
                'last_seen_at' => $lastSeenAt?->toIso8601String(),
                'is_online' => $isOnline,
                'initials' => $this->initials($u->name ?? $u->email ?? '?'),
            ];
        })->sortByDesc('is_online')->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'online_count' => collect($data)->where('is_online', true)->count(),
                'window_minutes' => 15,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '?';
        }
        $parts = preg_split('/\s+/', $name);
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
        }
        return mb_strtoupper(mb_substr($name, 0, 2));
    }
}
