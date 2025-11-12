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
     * Generate report data for the specified period
     */
    protected function generateReportData(string $period)
    {
        // TODO: Implement detailed report generation logic
        return [];
    }
}
