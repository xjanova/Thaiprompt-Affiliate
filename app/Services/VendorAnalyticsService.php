<?php

namespace App\Services;

use App\Models\VendorStore;
use App\Models\VendorAnalytics;
use App\Models\VendorStoreVisit;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VendorAnalyticsService
{
    /**
     * Record daily analytics for a store
     */
    public function recordDailyAnalytics(VendorStore $store, $date = null): VendorAnalytics
    {
        $date = $date ?? now()->toDateString();
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        $analytics = VendorAnalytics::firstOrNew([
            'store_id' => $store->id,
            'date' => $date,
        ]);

        // Traffic metrics
        $visits = VendorStoreVisit::where('store_id', $store->id)
            ->whereBetween('visited_at', [$startOfDay, $endOfDay]);

        $analytics->page_views = (clone $visits)->count();
        $analytics->unique_visitors = (clone $visits)->distinct('visitor_id')->count('visitor_id');

        // Session metrics
        $sessions = (clone $visits)->distinct('session_id')->get();
        if ($sessions->count() > 0) {
            $totalDuration = 0;
            $bounces = 0;

            foreach ($sessions as $sessionId => $sessionVisits) {
                $sessionData = VendorStoreVisit::where('store_id', $store->id)
                    ->where('session_id', $sessionVisits->session_id)
                    ->whereBetween('visited_at', [$startOfDay, $endOfDay])
                    ->orderBy('visited_at')
                    ->get();

                if ($sessionData->count() > 1) {
                    $duration = $sessionData->last()->visited_at->diffInSeconds($sessionData->first()->visited_at);
                    $totalDuration += $duration;
                } else {
                    $bounces++;
                }
            }

            $analytics->avg_session_duration = $sessions->count() > 0
                ? round($totalDuration / $sessions->count())
                : 0;

            $analytics->bounce_rate = round(($bounces / $sessions->count()) * 100, 2);
        }

        // Sales metrics
        $orders = OrderItem::where('seller_id', $store->user_id)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->whereBetween('created_at', [$startOfDay, $endOfDay]);

        $analytics->orders_count = (clone $orders)->count();
        $analytics->total_sales = (clone $orders)->sum('seller_earning');
        $analytics->gross_revenue = (clone $orders)->sum('total_price');

        // Commission
        $analytics->commission_paid = (clone $orders)->sum('platform_commission');
        $analytics->net_revenue = $analytics->gross_revenue - $analytics->commission_paid;

        // Average order value
        if ($analytics->orders_count > 0) {
            $analytics->avg_order_value = $analytics->total_sales / $analytics->orders_count;
        }

        // Conversion rate
        if ($analytics->unique_visitors > 0) {
            $analytics->conversion_rate = round(($analytics->orders_count / $analytics->unique_visitors) * 100, 2);
        }

        $analytics->save();

        return $analytics;
    }

    /**
     * Get analytics summary for a date range
     */
    public function getAnalyticsSummary(VendorStore $store, $startDate, $endDate): array
    {
        $analytics = VendorAnalytics::where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return [
            'total_page_views' => $analytics->sum('page_views'),
            'total_unique_visitors' => $analytics->sum('unique_visitors'),
            'avg_bounce_rate' => round($analytics->avg('bounce_rate'), 2),
            'avg_session_duration' => round($analytics->avg('avg_session_duration')),
            'total_orders' => $analytics->sum('orders_count'),
            'total_revenue' => $analytics->sum('total_sales'),
            'avg_order_value' => round($analytics->avg('avg_order_value'), 2),
            'avg_conversion_rate' => round($analytics->avg('conversion_rate'), 2),
            'total_commission_paid' => $analytics->sum('commission_paid'),
            'total_net_revenue' => $analytics->sum('net_revenue'),
        ];
    }

    /**
     * Get daily analytics chart data
     */
    public function getDailyChartData(VendorStore $store, $startDate, $endDate): array
    {
        $analytics = VendorAnalytics::where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        return [
            'dates' => $analytics->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'))->toArray(),
            'page_views' => $analytics->pluck('page_views')->toArray(),
            'unique_visitors' => $analytics->pluck('unique_visitors')->toArray(),
            'orders' => $analytics->pluck('orders_count')->toArray(),
            'revenue' => $analytics->pluck('total_sales')->map(fn($v) => (float)$v)->toArray(),
        ];
    }

    /**
     * Get real-time stats (today)
     */
    public function getRealTimeStats(VendorStore $store): array
    {
        $today = now()->toDateString();

        // Try to get existing analytics or calculate on the fly
        $analytics = VendorAnalytics::where('store_id', $store->id)
            ->where('date', $today)
            ->first();

        if (!$analytics) {
            $analytics = $this->recordDailyAnalytics($store, $today);
        }

        $todayStart = now()->startOfDay();

        // Get live counts
        $currentVisitors = VendorStoreVisit::where('store_id', $store->id)
            ->where('visited_at', '>=', now()->subMinutes(5))
            ->distinct('visitor_id')
            ->count('visitor_id');

        return [
            'page_views_today' => $analytics->page_views,
            'unique_visitors_today' => $analytics->unique_visitors,
            'orders_today' => $analytics->orders_count,
            'revenue_today' => $analytics->total_sales,
            'current_active_visitors' => $currentVisitors,
        ];
    }
}
