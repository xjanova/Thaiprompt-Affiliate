<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorStore;
use App\Models\VendorAnalytics;
use App\Models\VendorStoreVisit;
use App\Models\VendorAnalyticsSetting;
use App\Services\VendorAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected VendorAnalyticsService $analyticsService;

    public function __construct(VendorAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return redirect()->route('seller.dashboard')
                ->with('error', 'Store not found. Please set up your store first.');
        }

        // Get date range and filters
        $endDate = $request->input('end_date', now()->toDateString());
        $startDate = $request->input('start_date', now()->subDays(29)->toDateString());

        // Filters
        $minOrders = $request->input('min_orders', 0);
        $minConversion = $request->input('min_conversion', 0);
        $maxBounce = $request->input('max_bounce', 100);

        // Get summary data
        $summary = $this->analyticsService->getAnalyticsSummary($store, $startDate, $endDate);

        // Get chart data
        $chartData = $this->analyticsService->getDailyChartData($store, $startDate, $endDate);

        // Get real-time stats (today)
        $realTimeStats = $this->analyticsService->getRealTimeStats($store);

        // Get recent analytics by day with filters
        $dailyAnalyticsQuery = VendorAnalytics::where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($minOrders > 0) {
            $dailyAnalyticsQuery->where('orders_count', '>=', $minOrders);
        }

        if ($minConversion > 0) {
            $dailyAnalyticsQuery->where('conversion_rate', '>=', $minConversion);
        }

        if ($maxBounce < 100) {
            $dailyAnalyticsQuery->where('bounce_rate', '<=', $maxBounce);
        }

        $dailyAnalytics = $dailyAnalyticsQuery->orderBy('date', 'desc')->get();

        return view('seller.analytics.index', compact(
            'user',
            'store',
            'summary',
            'chartData',
            'realTimeStats',
            'dailyAnalytics',
            'startDate',
            'endDate',
            'minOrders',
            'minConversion',
            'maxBounce'
        ));
    }

    /**
     * Display analytics settings
     */
    public function settings()
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return redirect()->route('seller.dashboard')
                ->with('error', 'Store not found. Please set up your store first.');
        }

        $settings = VendorAnalyticsSetting::getForStore($store);

        return view('seller.analytics.settings', compact('user', 'store', 'settings'));
    }

    /**
     * Update analytics settings
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return redirect()->route('seller.dashboard')
                ->with('error', 'Store not found.');
        }

        $validated = $request->validate([
            'retention_days' => 'required|integer|min:7|max:730',
            'auto_cleanup_enabled' => 'boolean',
            'track_visits' => 'boolean',
            'track_unique_visitors' => 'boolean',
            'track_session_duration' => 'boolean',
            'anonymize_ip' => 'boolean',
            'respect_dnt' => 'boolean',
            'email_weekly_reports' => 'boolean',
            'email_monthly_reports' => 'boolean',
        ]);

        $settings = VendorAnalyticsSetting::getForStore($store);
        $settings->update($validated);

        return redirect()->route('seller.analytics.settings')
            ->with('success', 'Analytics settings updated successfully!');
    }

    /**
     * Manual cleanup of analytics data
     */
    public function cleanup(Request $request)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return back()->with('error', 'Store not found.');
        }

        $validated = $request->validate([
            'older_than_days' => 'required|integer|min:7|max:730',
            'confirm' => 'required|accepted',
        ]);

        $cutoffDate = Carbon::now()->subDays($validated['older_than_days']);

        // Delete old analytics
        $analyticsDeleted = VendorAnalytics::where('store_id', $store->id)
            ->where('date', '<', $cutoffDate)
            ->delete();

        // Delete old visits
        $visitsDeleted = VendorStoreVisit::where('store_id', $store->id)
            ->where('visited_at', '<', $cutoffDate)
            ->delete();

        return back()->with('success', "Cleanup complete! Deleted {$analyticsDeleted} analytics records and {$visitsDeleted} visit records.");
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return back()->with('error', 'Store not found.');
        }

        $endDate = $request->input('end_date', now()->toDateString());
        $startDate = $request->input('start_date', now()->subDays(29)->toDateString());

        $analytics = VendorAnalytics::where('store_id', $store->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $filename = "analytics-{$store->store_slug}-{$startDate}-to-{$endDate}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($analytics) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Date',
                'Page Views',
                'Unique Visitors',
                'Orders',
                'Revenue',
                'Conversion Rate (%)',
                'Bounce Rate (%)',
                'Avg Session Duration (seconds)',
                'Avg Order Value',
            ]);

            // Data rows
            foreach ($analytics as $day) {
                fputcsv($file, [
                    $day->date,
                    $day->page_views,
                    $day->unique_visitors,
                    $day->orders_count,
                    $day->total_sales,
                    $day->conversion_rate,
                    $day->bounce_rate,
                    $day->avg_session_duration,
                    $day->avg_order_value,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
