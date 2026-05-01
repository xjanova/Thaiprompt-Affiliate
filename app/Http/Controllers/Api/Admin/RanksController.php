<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Mobile API: Ranks
 */
class RanksController extends Controller
{
    /**
     * GET /api/admin/ranks
     */
    public function index(Request $request): JsonResponse
    {
        $ranks = Rank::query()
            ->orderBy('sort_order')
            ->orderBy('level')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'name_th' => $r->name_th,
                'level' => (int) $r->level,
                'icon' => $r->icon,
                'color' => $r->color,
                'stars' => (int) $r->stars,
                'commission_rate' => (float) $r->commission_rate,
                'bonus_multiplier' => (float) $r->bonus_multiplier,
                'is_active' => (bool) $r->is_active,
                'is_top_tier' => (bool) $r->is_top_tier,
                'min_points' => (int) $r->min_points,
                'min_referrals' => (int) $r->min_referrals,
            ]);

        return response()->json([
            'success' => true,
            'data' => $ranks,
        ]);
    }
}
