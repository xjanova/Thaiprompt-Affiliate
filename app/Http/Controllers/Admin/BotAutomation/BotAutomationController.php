<?php

namespace App\Http\Controllers\Admin\BotAutomation;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotAutomation;
use App\Services\BotAutomation\BotAutomationService;
use Illuminate\Http\Request;

class BotAutomationController extends Controller
{
    public function __construct(
        protected BotAutomationService $automationService
    ) {}

    /**
     * แสดง Dashboard หลักของ Bot Automation
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        // สถิติรวมของระบบ
        $stats = [
            'total_automations' => BotAutomation::count(),
            'active_automations' => BotAutomation::where('is_active', true)->count(),
            'total_executions' => \App\Models\BotAutomation\BotExecutionLog::count(),
            'success_rate' => $this->calculateSuccessRate(),
        ];

        // Automations ล่าสุด
        $recentAutomations = BotAutomation::with(['user', 'aiBotProfile'])
            ->latest()
            ->limit(5)
            ->get();

        // Execution logs ล่าสุด
        $recentExecutions = \App\Models\BotAutomation\BotExecutionLog::with('automation')
            ->latest()
            ->limit(10)
            ->get();

        // สถิติตามประเภท automation
        $automationsByType = BotAutomation::selectRaw('automation_type, COUNT(*) as count')
            ->groupBy('automation_type')
            ->get()
            ->pluck('count', 'automation_type')
            ->toArray();

        // Platform connections
        $platformConnections = \App\Models\BotAutomation\BotPlatformConnection::where('is_active', true)
            ->count();

        return view('admin.bot-automation.dashboard', compact(
            'stats',
            'recentAutomations',
            'recentExecutions',
            'automationsByType',
            'platformConnections'
        ));
    }

    /**
     * คำนวณอัตราความสำเร็จของ automations
     *
     * @return float
     */
    protected function calculateSuccessRate(): float
    {
        $totalExecutions = \App\Models\BotAutomation\BotExecutionLog::count();

        if ($totalExecutions === 0) {
            return 0;
        }

        $successfulExecutions = \App\Models\BotAutomation\BotExecutionLog::where('status', 'success')->count();

        return round(($successfulExecutions / $totalExecutions) * 100, 2);
    }

    /**
     * Display a listing of automations
     */
    public function index(Request $request)
    {
        $automations = BotAutomation::query()
            ->with(['user', 'aiBotProfile', 'template'])
            ->when($request->automation_type, function ($query, $type) {
                $query->where('automation_type', $type);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return view('admin.bot-automation.index', compact('automations'));
    }

    /**
     * Show the form for creating a new automation
     */
    public function create()
    {
        return view('admin.bot-automation.create');
    }

    /**
     * Store a newly created automation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'automation_type' => 'required|in:scheduled_post,customer_support,sales_assistant,engagement,analytics',
            'trigger_type' => 'required|in:schedule,event,webhook,manual',
            'schedule_type' => 'nullable|in:once,hourly,daily,weekly,monthly,cron',
            'content_source' => 'required|in:custom,template,ai_generated',
            'custom_content' => 'nullable|string',
            'ai_generation_prompt' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = auth()->id();

        $automation = $this->automationService->createAutomation($validated);

        return redirect()
            ->route('admin.bot-automation.show', $automation)
            ->with('success', 'Bot automation created successfully');
    }

    /**
     * Display the specified automation
     */
    public function show(BotAutomation $automation)
    {
        $automation->load(['user', 'aiBotProfile', 'template', 'scheduledPosts', 'executions']);
        $statistics = $this->automationService->getStatistics($automation);

        return view('admin.bot-automation.show', compact('automation', 'statistics'));
    }

    /**
     * Show the form for editing the specified automation
     */
    public function edit(BotAutomation $automation)
    {
        return view('admin.bot-automation.edit', compact('automation'));
    }

    /**
     * Update the specified automation
     */
    public function update(Request $request, BotAutomation $automation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'automation_type' => 'required|in:scheduled_post,customer_support,sales_assistant,engagement,analytics',
            'content_source' => 'required|in:custom,template,ai_generated',
            'custom_content' => 'nullable|string',
            'ai_generation_prompt' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $automation->update($validated);

        return redirect()
            ->route('admin.bot-automation.show', $automation)
            ->with('success', 'Bot automation updated successfully');
    }

    /**
     * Remove the specified automation
     */
    public function destroy(BotAutomation $automation)
    {
        $automation->delete();

        return redirect()
            ->route('admin.bot-automation.index')
            ->with('success', 'Bot automation deleted successfully');
    }

    /**
     * Execute automation manually
     */
    public function execute(BotAutomation $automation)
    {
        try {
            $execution = $this->automationService->executeAutomation($automation, 'manual');

            return redirect()
                ->route('admin.bot-automation.show', $automation)
                ->with('success', 'Automation executed successfully');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.bot-automation.show', $automation)
                ->with('error', 'Automation execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle automation status
     */
    public function toggle(BotAutomation $automation)
    {
        $automation->update(['is_active' => !$automation->is_active]);

        $status = $automation->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('admin.bot-automation.show', $automation)
            ->with('success', "Automation {$status} successfully");
    }
}
