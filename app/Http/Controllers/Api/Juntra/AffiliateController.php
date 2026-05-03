<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Models\FortuneCommission;
use App\Models\FortuneReferral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Affiliate dashboard for the Juntra mobile app.
 *
 * Wraps the existing fortune_referrals + fortune_commissions tables.
 * The MLM commission engine itself lives in FortuneAffiliateService /
 * FortuneCommissionService — this controller only reads aggregates.
 */
class AffiliateController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $totalEarn = (float) FortuneCommission::where('user_id', $user->id)->sum('amount');
        $thisMonth = (float) FortuneCommission::where('user_id', $user->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        $lastMonth = (float) FortuneCommission::where('user_id', $user->id)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        $downlineCount = FortuneReferral::where('upline_user_id', $user->id)->count();
        $activeCount = FortuneReferral::where('upline_user_id', $user->id)
            ->whereHas('downlineUser', function ($q) {
                $q->whereHas('fortuneReadings', fn ($r) => $r->where('created_at', '>=', now()->subDays(30)));
            })
            ->count();

        return response()->json(['data' => [
            'total_earnings' => $totalEarn,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'change_pct' => $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : null,
            'downline_count' => $downlineCount,
            'active_count' => $activeCount,
            'levels' => 5,
        ]]);
    }

    public function downline(Request $request): JsonResponse
    {
        $user = $request->user();
        $maxLevel = (int) $request->query('max_level', 3);
        $maxLevel = min(5, max(1, $maxLevel));

        $tree = $this->buildTree($user->id, 0, $maxLevel);
        return response()->json(['data' => $tree]);
    }

    public function commissions(Request $request): JsonResponse
    {
        $items = FortuneCommission::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'amount', 'level', 'source_user_id', 'created_at']);
        return response()->json(['data' => $items]);
    }

    public function link(Request $request): JsonResponse
    {
        $user = $request->user();
        $code = strtoupper(substr(md5('juntra-' . $user->id), 0, 8));
        return response()->json(['data' => [
            'code' => $code,
            'url' => "https://juntra.app/r/{$code}",
        ]]);
    }

    private function buildTree(int $userId, int $level, int $maxLevel): array
    {
        if ($level > $maxLevel) return [];
        $children = FortuneReferral::where('upline_user_id', $userId)
            ->with('downlineUser:id,name')
            ->limit(20)
            ->get();
        return $children->map(function ($r) use ($level, $maxLevel) {
            $u = $r->downlineUser;
            return [
                'user_id' => $u?->id,
                'name' => $u?->name ?? 'ผู้ใช้ที่ลบไปแล้ว',
                'level' => $level + 1,
                'children' => $level < $maxLevel ? $this->buildTree($u?->id ?? 0, $level + 1, $maxLevel) : [],
            ];
        })->toArray();
    }
}
