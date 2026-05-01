<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserListResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
