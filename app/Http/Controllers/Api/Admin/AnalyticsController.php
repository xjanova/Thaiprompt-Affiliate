<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmCommission;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin Mobile API: Analytics
 *
 * รวมตัวเลขสำหรับหน้า Analytics: revenue trend, user growth, top categories
 */
class AnalyticsController extends Controller
{
    /**
     * GET /api/admin/analytics/overview
     */
    public function overview(Request $request): JsonResponse
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
                'revenue_trend' => $this->getRevenueTrend($period),
                'user_growth' => $this->getUserGrowth($period),
                'top_metrics' => $this->getTopMetrics($start),
                'period' => $period,
            ],
        ]);
    }

    /**
     * รายได้รายวัน 30 วันล่าสุด (default)
     */
    private function getRevenueTrend(string $period): array
    {
        $days = match ($period) {
            'today' => 1,
            'week' => 7,
            default => 30,
        };

        try {
            return MlmCommission::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(commission_amount) as total')
            )
                ->where('status', 'paid')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($r) => [
                    'date' => (string) $r->date,
                    'value' => round((float) $r->total, 2),
                ])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * ยอดสมาชิกใหม่รายวัน
     */
    private function getUserGrowth(string $period): array
    {
        $days = match ($period) {
            'today' => 1,
            'week' => 7,
            default => 30,
        };

        try {
            return User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($r) => [
                    'date' => (string) $r->date,
                    'value' => (int) $r->total,
                ])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getTopMetrics(Carbon $start): array
    {
        $metrics = [
            'orders_count' => 0,
            'orders_revenue_thb' => 0.0,
            'avg_order_value_thb' => 0.0,
            'unique_buyers' => 0,
        ];

        try {
            $count = Order::where('created_at', '>=', $start)->count();
            $rev = (float) Order::where('created_at', '>=', $start)
                ->where('payment_status', 'paid')
                ->sum('total_amount');
            $unique = Order::where('created_at', '>=', $start)
                ->distinct('user_id')
                ->count('user_id');

            $metrics['orders_count'] = $count;
            $metrics['orders_revenue_thb'] = round($rev, 2);
            $metrics['avg_order_value_thb'] = $count > 0 ? round($rev / $count, 2) : 0;
            $metrics['unique_buyers'] = $unique;
        } catch (\Throwable $e) {
            //
        }

        return $metrics;
    }
}
