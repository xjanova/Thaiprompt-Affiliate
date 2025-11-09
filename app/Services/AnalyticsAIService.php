<?php

namespace App\Services;

use App\Models\VendorStore;
use App\Models\VendorAnalytics;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsAIService
{
    /**
     * Predict revenue for next N days using linear regression
     */
    public function predictRevenue(VendorStore $store, int $days = 30): array
    {
        // Get historical data (last 90 days)
        $historicalData = VendorAnalytics::where('store_id', $store->id)
            ->where('date', '>=', now()->subDays(90))
            ->orderBy('date')
            ->get();

        if ($historicalData->count() < 14) {
            return [
                'predictions' => [],
                'confidence' => 0,
                'trend' => 'insufficient_data',
            ];
        }

        // Prepare data for linear regression
        $x = [];
        $y = [];
        foreach ($historicalData as $index => $data) {
            $x[] = $index;
            $y[] = (float) $data->total_sales;
        }

        // Calculate linear regression
        $regression = $this->linearRegression($x, $y);

        // Generate predictions
        $predictions = [];
        $lastIndex = count($x) - 1;

        for ($i = 1; $i <= $days; $i++) {
            $predictedValue = $regression['slope'] * ($lastIndex + $i) + $regression['intercept'];
            $predictions[] = [
                'date' => now()->addDays($i)->format('Y-m-d'),
                'predicted_revenue' => max(0, $predictedValue), // Don't predict negative
                'confidence' => $regression['r_squared'],
            ];
        }

        return [
            'predictions' => $predictions,
            'confidence' => round($regression['r_squared'] * 100, 2),
            'trend' => $regression['slope'] > 0 ? 'up' : 'down',
            'daily_average' => round(array_sum($y) / count($y), 2),
        ];
    }

    /**
     * Detect anomalies in analytics data
     */
    public function detectAnomalies(VendorStore $store, int $days = 30): array
    {
        $data = VendorAnalytics::where('store_id', $store->id)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get();

        if ($data->count() < 7) {
            return [];
        }

        $anomalies = [];
        $metrics = ['page_views', 'unique_visitors', 'orders_count', 'total_sales', 'conversion_rate'];

        foreach ($metrics as $metric) {
            $values = $data->pluck($metric)->toArray();
            $mean = array_sum($values) / count($values);
            $stdDev = $this->standardDeviation($values, $mean);

            foreach ($data as $day) {
                $value = $day->{$metric};
                $zScore = $stdDev > 0 ? abs(($value - $mean) / $stdDev) : 0;

                // If z-score > 2.5, it's an anomaly
                if ($zScore > 2.5) {
                    $anomalies[] = [
                        'date' => $day->date,
                        'metric' => $metric,
                        'value' => $value,
                        'expected' => round($mean, 2),
                        'severity' => $zScore > 3 ? 'high' : 'medium',
                        'type' => $value > $mean ? 'spike' : 'drop',
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * RFM Analysis - Recency, Frequency, Monetary
     */
    public function rfmAnalysis(VendorStore $store): array
    {
        $customers = OrderItem::where('seller_id', $store->user_id)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->with('order.user')
            ->get()
            ->groupBy(function ($item) {
                return $item->order->user_id ?? 'guest';
            });

        $rfmData = [];

        foreach ($customers as $userId => $orders) {
            if ($userId === 'guest') continue;

            $lastOrderDate = $orders->max(fn($o) => $o->order->created_at);
            $recency = now()->diffInDays($lastOrderDate);
            $frequency = $orders->count();
            $monetary = $orders->sum('total_price');

            $rfmData[] = [
                'user_id' => $userId,
                'user_name' => $orders->first()->order->user->name ?? 'Unknown',
                'recency' => $recency,
                'frequency' => $frequency,
                'monetary' => $monetary,
            ];
        }

        // Score RFM (1-5 scale)
        $scored = $this->scoreRFM($rfmData);

        // Segment customers
        return $this->segmentCustomers($scored);
    }

    /**
     * Cohort Analysis - Track customer behavior over time
     */
    public function cohortAnalysis(VendorStore $store): array
    {
        $cohorts = [];
        $startDate = now()->subMonths(6)->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $cohortMonth = $startDate->copy()->addMonths($i);

            // Get customers who made their first purchase in this month
            $newCustomers = OrderItem::where('seller_id', $store->user_id)
                ->whereHas('order', function ($q) use ($cohortMonth) {
                    $q->where('payment_status', 'paid')
                        ->whereYear('created_at', $cohortMonth->year)
                        ->whereMonth('created_at', $cohortMonth->month);
                })
                ->distinct('order_id')
                ->pluck('order_id')
                ->toArray();

            $cohortSize = count($newCustomers);

            if ($cohortSize === 0) continue;

            $retention = [];
            for ($month = 0; $month <= now()->diffInMonths($cohortMonth); $month++) {
                $periodStart = $cohortMonth->copy()->addMonths($month);
                $periodEnd = $periodStart->copy()->endOfMonth();

                $returningCustomers = OrderItem::where('seller_id', $store->user_id)
                    ->whereIn('order_id', $newCustomers)
                    ->whereHas('order', function ($q) use ($periodStart, $periodEnd) {
                        $q->whereBetween('created_at', [$periodStart, $periodEnd]);
                    })
                    ->distinct('order_id')
                    ->count();

                $retention[$month] = [
                    'month' => $month,
                    'customers' => $returningCustomers,
                    'rate' => round(($returningCustomers / $cohortSize) * 100, 2),
                ];
            }

            $cohorts[] = [
                'cohort' => $cohortMonth->format('Y-m'),
                'size' => $cohortSize,
                'retention' => $retention,
            ];
        }

        return $cohorts;
    }

    /**
     * Funnel Analysis
     */
    public function funnelAnalysis(VendorStore $store, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Define funnel stages
        $visitors = VendorAnalytics::where('store_id', $store->id)
            ->where('date', '>=', $startDate)
            ->sum('unique_visitors');

        $productViews = VendorAnalytics::where('store_id', $store->id)
            ->where('date', '>=', $startDate)
            ->sum('products_viewed');

        $addedToCart = VendorAnalytics::where('store_id', $store->id)
            ->where('date', '>=', $startDate)
            ->sum('products_added_to_cart');

        $orders = VendorAnalytics::where('store_id', $store->id)
            ->where('date', '>=', $startDate)
            ->sum('orders_count');

        $completed = OrderItem::where('seller_id', $store->user_id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->count();

        return [
            [
                'stage' => 'Visitors',
                'count' => $visitors,
                'percentage' => 100,
                'drop_off' => 0,
            ],
            [
                'stage' => 'Product Views',
                'count' => $productViews,
                'percentage' => $visitors > 0 ? round(($productViews / $visitors) * 100, 2) : 0,
                'drop_off' => $visitors > 0 ? round((($visitors - $productViews) / $visitors) * 100, 2) : 0,
            ],
            [
                'stage' => 'Add to Cart',
                'count' => $addedToCart,
                'percentage' => $visitors > 0 ? round(($addedToCart / $visitors) * 100, 2) : 0,
                'drop_off' => $productViews > 0 ? round((($productViews - $addedToCart) / $productViews) * 100, 2) : 0,
            ],
            [
                'stage' => 'Orders',
                'count' => $orders,
                'percentage' => $visitors > 0 ? round(($orders / $visitors) * 100, 2) : 0,
                'drop_off' => $addedToCart > 0 ? round((($addedToCart - $orders) / $addedToCart) * 100, 2) : 0,
            ],
            [
                'stage' => 'Completed',
                'count' => $completed,
                'percentage' => $visitors > 0 ? round(($completed / $visitors) * 100, 2) : 0,
                'drop_off' => $orders > 0 ? round((($orders - $completed) / $orders) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Generate AI-powered insights
     */
    public function generateInsights(VendorStore $store): array
    {
        $insights = [];
        $recentData = VendorAnalytics::where('store_id', $store->id)
            ->where('date', '>=', now()->subDays(30))
            ->get();

        if ($recentData->isEmpty()) {
            return $insights;
        }

        // Insight 1: Traffic trends
        $firstWeek = $recentData->take(7)->sum('unique_visitors');
        $lastWeek = $recentData->sortByDesc('date')->take(7)->sum('unique_visitors');

        if ($lastWeek > $firstWeek * 1.2) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Traffic Growth',
                'message' => 'Your unique visitors increased by ' . round((($lastWeek - $firstWeek) / $firstWeek) * 100) . '% in the last week!',
                'action' => 'Keep up the great marketing efforts!',
                'priority' => 'high',
            ];
        } elseif ($lastWeek < $firstWeek * 0.8) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Traffic Decline',
                'message' => 'Your traffic dropped by ' . round((($firstWeek - $lastWeek) / $firstWeek) * 100) . '% compared to last week.',
                'action' => 'Consider running a promotion or improving SEO.',
                'priority' => 'high',
            ];
        }

        // Insight 2: Conversion rate
        $avgConversion = $recentData->avg('conversion_rate');
        if ($avgConversion < 1) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low Conversion Rate',
                'message' => "Your average conversion rate is {$avgConversion}%, which is below industry average (2-3%).",
                'action' => 'Try improving product descriptions, adding reviews, or offering discounts.',
                'priority' => 'medium',
            ];
        } elseif ($avgConversion > 5) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Conversion!',
                'message' => "Your conversion rate of {$avgConversion}% is exceptional!",
                'action' => 'Consider increasing your traffic to maximize revenue.',
                'priority' => 'low',
            ];
        }

        // Insight 3: Bounce rate
        $avgBounce = $recentData->avg('bounce_rate');
        if ($avgBounce > 60) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'High Bounce Rate',
                'message' => "{$avgBounce}% of visitors leave without interacting.",
                'action' => 'Improve page load speed, add engaging content, or check mobile responsiveness.',
                'priority' => 'high',
            ];
        }

        // Insight 4: Best selling day
        $bestDay = $recentData->sortByDesc('total_sales')->first();
        if ($bestDay) {
            $dayOfWeek = Carbon::parse($bestDay->date)->format('l');
            $insights[] = [
                'type' => 'info',
                'title' => 'Peak Sales Day',
                'message' => "Your best sales day is {$dayOfWeek} with ฿" . number_format($bestDay->total_sales, 2),
                'action' => "Schedule promotions on {$dayOfWeek} for maximum impact.",
                'priority' => 'medium',
            ];
        }

        // Insight 5: Revenue trend
        $revenuePredict = $this->predictRevenue($store, 7);
        if ($revenuePredict['trend'] === 'up') {
            $insights[] = [
                'type' => 'success',
                'title' => 'Revenue Trending Up',
                'message' => 'AI predicts continued revenue growth over the next week.',
                'action' => 'Consider increasing inventory to meet demand.',
                'priority' => 'medium',
            ];
        }

        return $insights;
    }

    /**
     * Product performance analysis
     */
    public function productPerformance(VendorStore $store, int $days = 30): array
    {
        $products = OrderItem::where('seller_id', $store->user_id)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                'product_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_price) as total_revenue'),
                DB::raw('AVG(total_price) as avg_price')
            )
            ->groupBy('product_id')
            ->with('product')
            ->get();

        return $products->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'orders' => $item->order_count,
                'quantity_sold' => $item->total_quantity,
                'revenue' => $item->total_revenue,
                'avg_price' => $item->avg_price,
                'performance' => $this->calculatePerformanceScore($item),
            ];
        })->sortByDesc('performance')->values()->toArray();
    }

    // Helper methods

    private function linearRegression(array $x, array $y): array
    {
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Calculate R-squared
        $yMean = $sumY / $n;
        $ssTotal = 0;
        $ssResidual = 0;

        for ($i = 0; $i < $n; $i++) {
            $predicted = $slope * $x[$i] + $intercept;
            $ssTotal += pow($y[$i] - $yMean, 2);
            $ssResidual += pow($y[$i] - $predicted, 2);
        }

        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => max(0, min(1, $rSquared)),
        ];
    }

    private function standardDeviation(array $values, float $mean): float
    {
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        return sqrt($variance / count($values));
    }

    private function scoreRFM(array $data): array
    {
        $recencyValues = array_column($data, 'recency');
        $frequencyValues = array_column($data, 'frequency');
        $monetaryValues = array_column($data, 'monetary');

        foreach ($data as &$customer) {
            $customer['r_score'] = $this->getQuintile($customer['recency'], $recencyValues, true); // Lower is better
            $customer['f_score'] = $this->getQuintile($customer['frequency'], $frequencyValues);
            $customer['m_score'] = $this->getQuintile($customer['monetary'], $monetaryValues);
            $customer['rfm_score'] = ($customer['r_score'] + $customer['f_score'] + $customer['m_score']) / 3;
        }

        return $data;
    }

    private function getQuintile($value, array $values, bool $reverse = false): int
    {
        sort($values);
        $n = count($values);
        $quintiles = [
            $values[intval($n * 0.2)],
            $values[intval($n * 0.4)],
            $values[intval($n * 0.6)],
            $values[intval($n * 0.8)],
        ];

        for ($i = 0; $i < 4; $i++) {
            if ($value <= $quintiles[$i]) {
                return $reverse ? (5 - $i) : ($i + 1);
            }
        }

        return $reverse ? 1 : 5;
    }

    private function segmentCustomers(array $scoredData): array
    {
        $segments = [
            'champions' => [],
            'loyal' => [],
            'potential' => [],
            'at_risk' => [],
            'lost' => [],
        ];

        foreach ($scoredData as $customer) {
            $score = $customer['rfm_score'];

            if ($score >= 4.5) {
                $segments['champions'][] = $customer;
            } elseif ($score >= 3.5) {
                $segments['loyal'][] = $customer;
            } elseif ($score >= 2.5) {
                $segments['potential'][] = $customer;
            } elseif ($score >= 1.5) {
                $segments['at_risk'][] = $customer;
            } else {
                $segments['lost'][] = $customer;
            }
        }

        return $segments;
    }

    private function calculatePerformanceScore($item): float
    {
        // Weighted score: revenue (50%), quantity (30%), order count (20%)
        $maxRevenue = 10000; // Normalize
        $maxQuantity = 100;
        $maxOrders = 50;

        $revenueScore = min(($item->total_revenue / $maxRevenue) * 50, 50);
        $quantityScore = min(($item->total_quantity / $maxQuantity) * 30, 30);
        $orderScore = min(($item->order_count / $maxOrders) * 20, 20);

        return round($revenueScore + $quantityScore + $orderScore, 2);
    }
}
