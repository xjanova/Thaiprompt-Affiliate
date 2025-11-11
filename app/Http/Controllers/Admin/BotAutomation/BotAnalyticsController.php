<?php

namespace App\Http\Controllers\Admin\BotAutomation;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotAutomation;
use App\Models\BotAutomation\BotAutomationExecution;
use App\Models\BotAutomation\BotEngagementMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BotAnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // days

        $statistics = [
            'total_executions' => BotAutomationExecution::count(),
            'successful_executions' => BotAutomationExecution::where('status', 'success')->count(),
            'failed_executions' => BotAutomationExecution::where('status', 'failed')->count(),
            'total_reach' => BotEngagementMetric::sum('reach'),
            'total_engagement' => BotEngagementMetric::sum('engagement'),
            'avg_engagement_rate' => $this->calculateAverageEngagementRate(),
        ];

        $executionTrend = $this->getExecutionTrend($period);
        $engagementTrend = $this->getEngagementTrend($period);
        $topPerformingAutomations = $this->getTopPerformingAutomations(10);

        return view('admin.bot-automation.analytics.index', compact(
            'statistics',
            'executionTrend',
            'engagementTrend',
            'topPerformingAutomations',
            'period'
        ));
    }

    /**
     * Display automation performance report
     */
    public function automationPerformance(Request $request)
    {
        $automations = BotAutomation::query()
            ->withCount(['executions as total_executions'])
            ->withCount(['executions as successful_executions' => function ($query) {
                $query->where('status', 'success');
            }])
            ->withCount(['executions as failed_executions' => function ($query) {
                $query->where('status', 'failed');
            }])
            ->with(['engagementMetrics' => function ($query) {
                $query->selectRaw('automation_id, SUM(reach) as total_reach, SUM(engagement) as total_engagement');
                $query->groupBy('automation_id');
            }])
            ->when($request->automation_type, function ($query, $type) {
                $query->where('automation_type', $type);
            })
            ->paginate(20);

        return view('admin.bot-automation.analytics.performance', compact('automations'));
    }

    /**
     * Display engagement analytics
     */
    public function engagement(Request $request)
    {
        $period = $request->get('period', '30');

        $metrics = BotEngagementMetric::query()
            ->with(['automation'])
            ->where('created_at', '>=', now()->subDays($period))
            ->latest()
            ->paginate(50);

        $summary = [
            'total_reach' => $metrics->sum('reach'),
            'total_impressions' => $metrics->sum('impressions'),
            'total_clicks' => $metrics->sum('clicks'),
            'total_shares' => $metrics->sum('shares'),
            'total_comments' => $metrics->sum('comments'),
            'total_likes' => $metrics->sum('likes'),
            'avg_ctr' => $this->calculateAverageCTR($metrics),
            'avg_engagement_rate' => $this->calculateAverageEngagementRate(),
        ];

        return view('admin.bot-automation.analytics.engagement', compact('metrics', 'summary', 'period'));
    }

    /**
     * Display execution logs
     */
    public function executions(Request $request)
    {
        $executions = BotAutomationExecution::query()
            ->with(['automation'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->automation_id, function ($query, $id) {
                $query->where('automation_id', $id);
            })
            ->latest()
            ->paginate(50);

        return view('admin.bot-automation.analytics.executions', compact('executions'));
    }

    /**
     * Display platform-specific analytics
     */
    public function platformAnalytics(Request $request)
    {
        $platform = $request->get('platform', 'all');
        $period = $request->get('period', '30');

        $metrics = BotEngagementMetric::query()
            ->when($platform !== 'all', function ($query) use ($platform) {
                $query->where('platform', $platform);
            })
            ->where('created_at', '>=', now()->subDays($period))
            ->get();

        $platformBreakdown = BotEngagementMetric::query()
            ->select('platform', DB::raw('COUNT(*) as count, SUM(reach) as total_reach, SUM(engagement) as total_engagement'))
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('platform')
            ->get();

        return view('admin.bot-automation.analytics.platforms', compact('metrics', 'platformBreakdown', 'platform', 'period'));
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'executions'); // executions, engagement, performance
        $period = $request->get('period', '30');

        // TODO: Implement CSV/Excel export logic
        return response()->download('analytics-export.csv');
    }

    /**
     * Get execution trend data
     */
    protected function getExecutionTrend(int $days)
    {
        return BotAutomationExecution::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get engagement trend data
     */
    protected function getEngagementTrend(int $days)
    {
        return BotEngagementMetric::query()
            ->selectRaw('DATE(created_at) as date, SUM(reach) as reach, SUM(engagement) as engagement')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top performing automations
     */
    protected function getTopPerformingAutomations(int $limit)
    {
        return BotAutomation::query()
            ->withCount(['executions as successful_executions' => function ($query) {
                $query->where('status', 'success');
            }])
            ->with(['engagementMetrics' => function ($query) {
                $query->selectRaw('automation_id, SUM(engagement) as total_engagement');
                $query->groupBy('automation_id');
            }])
            ->orderByDesc('successful_executions')
            ->take($limit)
            ->get();
    }

    /**
     * Calculate average engagement rate
     */
    protected function calculateAverageEngagementRate()
    {
        $metrics = BotEngagementMetric::all();

        if ($metrics->isEmpty()) {
            return 0;
        }

        $totalRate = $metrics->sum(function ($metric) {
            return $metric->reach > 0 ? ($metric->engagement / $metric->reach) * 100 : 0;
        });

        return round($totalRate / $metrics->count(), 2);
    }

    /**
     * Calculate average CTR
     */
    protected function calculateAverageCTR($metrics)
    {
        if ($metrics->isEmpty()) {
            return 0;
        }

        $totalCTR = $metrics->sum(function ($metric) {
            return $metric->impressions > 0 ? ($metric->clicks / $metric->impressions) * 100 : 0;
        });

        return round($totalCTR / $metrics->count(), 2);
    }
}
