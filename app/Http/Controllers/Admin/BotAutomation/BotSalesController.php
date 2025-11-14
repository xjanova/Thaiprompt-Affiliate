<?php

namespace App\Http\Controllers\Admin\BotAutomation;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotSalesLead;
use App\Models\BotAutomation\BotSalesConversion;
use App\Models\BotAutomation\BotSalesPipeline;
use Illuminate\Http\Request;

class BotSalesController extends Controller
{
    /**
     * Display the sales dashboard
     */
    public function index()
    {
        $statistics = [
            'total_leads' => BotSalesLead::count(),
            'qualified_leads' => BotSalesLead::where('status', 'qualified')->count(),
            'total_conversions' => BotSalesConversion::count(),
            'conversion_rate' => $this->calculateConversionRate(),
            'total_revenue' => BotSalesConversion::sum('amount'),
            'avg_deal_size' => BotSalesConversion::avg('amount'),
        ];

        $recentLeads = BotSalesLead::with(['user', 'automation'])
            ->latest()
            ->take(10)
            ->get();

        $recentConversions = BotSalesConversion::with(['lead', 'automation'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.bot-automation.sales.index', compact('statistics', 'recentLeads', 'recentConversions'));
    }

    /**
     * Display all sales leads
     */
    public function leads(Request $request)
    {
        $leads = BotSalesLead::query()
            ->with(['user', 'automation'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->source, function ($query, $source) {
                $query->where('source', $source);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        return view('admin.bot-automation.sales.leads.index', compact('leads'));
    }

    /**
     * Display a single lead
     */
    public function showLead(BotSalesLead $lead)
    {
        $lead->load(['user', 'automation', 'conversions', 'activities']);

        return view('admin.bot-automation.sales.leads.show', compact('lead'));
    }

    /**
     * Update lead status
     */
    public function updateLeadStatus(Request $request, BotSalesLead $lead)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost',
        ]);

        $lead->update([
            'status' => $validated['status'],
            'updated_at' => now(),
        ]);

        // Log activity
        $lead->activities()->create([
            'user_id' => auth()->id(),
            'type' => 'status_change',
            'description' => "Status changed to {$validated['status']}",
        ]);

        return back()->with('success', 'Lead status updated successfully');
    }

    /**
     * Add note to lead
     */
    public function addNote(Request $request, BotSalesLead $lead)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        $lead->activities()->create([
            'user_id' => auth()->id(),
            'type' => 'note',
            'description' => $validated['note'],
        ]);

        return back()->with('success', 'Note added successfully');
    }

    /**
     * Display all conversions
     */
    public function conversions(Request $request)
    {
        $conversions = BotSalesConversion::query()
            ->with(['lead', 'automation'])
            ->when($request->date_from, function ($query, $date) {
                $query->whereDate('converted_at', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->whereDate('converted_at', '<=', $date);
            })
            ->latest('converted_at')
            ->paginate(20);

        return view('admin.bot-automation.sales.conversions.index', compact('conversions'));
    }

    /**
     * Display sales pipeline
     */
    public function pipeline()
    {
        $pipeline = [
            'new' => BotSalesLead::where('status', 'new')->count(),
            'contacted' => BotSalesLead::where('status', 'contacted')->count(),
            'qualified' => BotSalesLead::where('status', 'qualified')->count(),
            'proposal' => BotSalesLead::where('status', 'proposal')->count(),
            'negotiation' => BotSalesLead::where('status', 'negotiation')->count(),
            'won' => BotSalesLead::where('status', 'won')->count(),
            'lost' => BotSalesLead::where('status', 'lost')->count(),
        ];

        $pipelineValue = [
            'new' => BotSalesLead::where('status', 'new')->sum('estimated_value'),
            'contacted' => BotSalesLead::where('status', 'contacted')->sum('estimated_value'),
            'qualified' => BotSalesLead::where('status', 'qualified')->sum('estimated_value'),
            'proposal' => BotSalesLead::where('status', 'proposal')->sum('estimated_value'),
            'negotiation' => BotSalesLead::where('status', 'negotiation')->sum('estimated_value'),
        ];

        return view('admin.bot-automation.sales.pipeline', compact('pipeline', 'pipelineValue'));
    }

    /**
     * Display sales reports
     */
    public function reports(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, year

        $data = $this->generateReportData($period);

        return view('admin.bot-automation.sales.reports', compact('data', 'period'));
    }

    /**
     * Calculate conversion rate
     */
    protected function calculateConversionRate()
    {
        $totalLeads = BotSalesLead::count();
        $conversions = BotSalesConversion::count();

        if ($totalLeads === 0) {
            return 0;
        }

        return round(($conversions / $totalLeads) * 100, 2);
    }

    /**
     * สร้างข้อมูลรายงานตามช่วงเวลาที่กำหนด
     *
     * @param string $period day, week, month, year
     * @return array
     */
    protected function generateReportData(string $period): array
    {
        // กำหนดช่วงเวลาตาม period
        $dateRange = $this->getDateRange($period);

        return [
            // สถิติภาพรวม
            'overview' => $this->getOverviewStats($dateRange),

            // แนวโน้ม Leads
            'leads_trend' => $this->getLeadsTrend($dateRange, $period),

            // แนวโน้ม Conversions
            'conversions_trend' => $this->getConversionsTrend($dateRange, $period),

            // แนวโน้ม Revenue
            'revenue_trend' => $this->getRevenueTrend($dateRange, $period),

            // Conversion Rate Trend
            'conversion_rate_trend' => $this->getConversionRateTrend($dateRange, $period),

            // Top Performing Automations
            'top_automations' => $this->getTopAutomations($dateRange),

            // Lead Sources Breakdown
            'lead_sources' => $this->getLeadSourcesBreakdown($dateRange),

            // Sales Pipeline Distribution
            'pipeline_distribution' => $this->getPipelineDistribution(),

            // Period Information
            'period_info' => [
                'period' => $period,
                'start_date' => $dateRange['start']->format('Y-m-d'),
                'end_date' => $dateRange['end']->format('Y-m-d'),
            ],
        ];
    }

    /**
     * คำนวณช่วงเวลาตาม period
     *
     * @param string $period
     * @return array
     */
    protected function getDateRange(string $period): array
    {
        $end = now();

        $start = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        return ['start' => $start, 'end' => $end];
    }

    /**
     * ดึงสถิติภาพรวม
     *
     * @param array $dateRange
     * @return array
     */
    protected function getOverviewStats(array $dateRange): array
    {
        $leads = BotSalesLead::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        $conversions = BotSalesConversion::whereBetween('converted_at', [$dateRange['start'], $dateRange['end']]);

        return [
            'total_leads' => $leads->count(),
            'new_leads' => (clone $leads)->where('status', 'new')->count(),
            'qualified_leads' => (clone $leads)->where('status', 'qualified')->count(),
            'total_conversions' => $conversions->count(),
            'total_revenue' => $conversions->sum('amount'),
            'avg_deal_size' => $conversions->avg('amount'),
            'conversion_rate' => $this->calculateConversionRateForPeriod($dateRange),
        ];
    }

    /**
     * ดึงแนวโน้ม Leads
     *
     * @param array $dateRange
     * @param string $period
     * @return array
     */
    protected function getLeadsTrend(array $dateRange, string $period): array
    {
        $groupFormat = $this->getGroupFormat($period);

        return BotSalesLead::query()
            ->selectRaw("DATE_FORMAT(created_at, '{$groupFormat}') as date, COUNT(*) as count")
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * ดึงแนวโน้ม Conversions
     *
     * @param array $dateRange
     * @param string $period
     * @return array
     */
    protected function getConversionsTrend(array $dateRange, string $period): array
    {
        $groupFormat = $this->getGroupFormat($period);

        return BotSalesConversion::query()
            ->selectRaw("DATE_FORMAT(converted_at, '{$groupFormat}') as date, COUNT(*) as count")
            ->whereBetween('converted_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * ดึงแนวโน้ม Revenue
     *
     * @param array $dateRange
     * @param string $period
     * @return array
     */
    protected function getRevenueTrend(array $dateRange, string $period): array
    {
        $groupFormat = $this->getGroupFormat($period);

        return BotSalesConversion::query()
            ->selectRaw("DATE_FORMAT(converted_at, '{$groupFormat}') as date, SUM(amount) as revenue")
            ->whereBetween('converted_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * ดึงแนวโน้ม Conversion Rate
     *
     * @param array $dateRange
     * @param string $period
     * @return array
     */
    protected function getConversionRateTrend(array $dateRange, string $period): array
    {
        $groupFormat = $this->getGroupFormat($period);

        // ดึงข้อมูล leads ตามวัน
        $leads = BotSalesLead::query()
            ->selectRaw("DATE_FORMAT(created_at, '{$groupFormat}') as date, COUNT(*) as count")
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // ดึงข้อมูล conversions ตามวัน
        $conversions = BotSalesConversion::query()
            ->selectRaw("DATE_FORMAT(converted_at, '{$groupFormat}') as date, COUNT(*) as count")
            ->whereBetween('converted_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // คำนวณ conversion rate
        $result = [];
        foreach ($leads as $date => $lead) {
            $conversionCount = $conversions->get($date)?->count ?? 0;
            $leadCount = $lead->count;
            $rate = $leadCount > 0 ? round(($conversionCount / $leadCount) * 100, 2) : 0;

            $result[] = [
                'date' => $date,
                'rate' => $rate,
            ];
        }

        return $result;
    }

    /**
     * ดึง Top Performing Automations
     *
     * @param array $dateRange
     * @return array
     */
    protected function getTopAutomations(array $dateRange): array
    {
        return BotSalesConversion::query()
            ->selectRaw('automation_id, COUNT(*) as conversions, SUM(amount) as revenue')
            ->with(['automation'])
            ->whereBetween('converted_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('automation_id')
            ->orderByDesc('revenue')
            ->take(10)
            ->get()
            ->map(function ($item) {
                return [
                    'automation_name' => $item->automation->name ?? 'N/A',
                    'conversions' => $item->conversions,
                    'revenue' => $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * ดึง Lead Sources Breakdown
     *
     * @param array $dateRange
     * @return array
     */
    protected function getLeadSourcesBreakdown(array $dateRange): array
    {
        return BotSalesLead::query()
            ->selectRaw('source, COUNT(*) as count')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'source' => $item->source ?? 'Unknown',
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * ดึง Pipeline Distribution
     *
     * @return array
     */
    protected function getPipelineDistribution(): array
    {
        $statuses = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
        $result = [];

        foreach ($statuses as $status) {
            $result[] = [
                'status' => $status,
                'count' => BotSalesLead::where('status', $status)->count(),
                'value' => BotSalesLead::where('status', $status)->sum('estimated_value'),
            ];
        }

        return $result;
    }

    /**
     * คำนวณ Conversion Rate สำหรับช่วงเวลา
     *
     * @param array $dateRange
     * @return float
     */
    protected function calculateConversionRateForPeriod(array $dateRange): float
    {
        $totalLeads = BotSalesLead::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $conversions = BotSalesConversion::whereBetween('converted_at', [$dateRange['start'], $dateRange['end']])->count();

        if ($totalLeads === 0) {
            return 0;
        }

        return round(($conversions / $totalLeads) * 100, 2);
    }

    /**
     * รับ format สำหรับ GROUP BY ตาม period
     *
     * @param string $period
     * @return string
     */
    protected function getGroupFormat(string $period): string
    {
        return match ($period) {
            'day' => '%Y-%m-%d %H:00:00', // Group by hour
            'week' => '%Y-%m-%d',          // Group by day
            'month' => '%Y-%m-%d',         // Group by day
            'year' => '%Y-%m',             // Group by month
            default => '%Y-%m-%d',
        };
    }
}
