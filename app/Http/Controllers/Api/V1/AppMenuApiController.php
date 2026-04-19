<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Reads dynamic content (menus, sliders, promotions) for the mobile client.
 */
class AppMenuApiController extends Controller
{
    public function menus(Request $request): JsonResponse
    {
        $slot = $request->query('slot');
        $appVersion = $this->appVersionNumeric($request);
        $user = $request->user();

        $query = DB::table('app_menus')
            ->where('enabled', true)
            ->where(function ($q) {
                $now = now();
                $q->whereNull('visible_from')->orWhere('visible_from', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('visible_until')->orWhere('visible_until', '>=', $now);
            });

        if ($slot) $query->where('slot', $slot);

        $rows = $query->orderBy('slot')->orderBy('order')->get();

        $filtered = $rows->filter(function ($r) use ($user, $appVersion) {
            if ($r->role && (! $user || $user->role !== $r->role)) return false;
            if ($r->min_app_version && $appVersion !== null
                && version_compare($appVersion, $r->min_app_version, '<')) return false;
            return true;
        })->values();

        return response()->json(['menus' => $filtered]);
    }

    public function sliders(Request $request): JsonResponse
    {
        $appVersion = $this->appVersionNumeric($request);
        $geo = $request->query('geohash');
        $now = now();

        $rows = DB::table('app_sliders')
            ->where('enabled', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('order')
            ->get();

        $filtered = $rows->filter(function ($r) use ($appVersion, $geo) {
            if ($r->min_app_version && $appVersion !== null
                && version_compare($appVersion, $r->min_app_version, '<')) return false;
            if ($r->region_geohash && $geo && ! str_starts_with($geo, $r->region_geohash)) {
                return false;
            }
            return true;
        })->values();

        return response()->json(['sliders' => $filtered]);
    }

    public function promotions(Request $request): JsonResponse
    {
        $area = $request->query('area', 'home');
        $geo = $request->query('geohash');
        $now = now();

        $rows = DB::table('promotions')
            ->where('enabled', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('priority')
            ->limit(20)
            ->get();

        $filtered = $rows->filter(function ($r) use ($geo) {
            if ($r->region_geohash && $geo && ! str_starts_with($geo, $r->region_geohash)) {
                return false;
            }
            return true;
        })->values();

        return response()->json([
            'promotions' => $filtered,
            'area'       => $area,
        ]);
    }

    private function appVersionNumeric(Request $request): ?string
    {
        $v = $request->header('X-App-Version');
        if (! $v) return null;
        return explode('+', $v)[0] ?: null;
    }
}
