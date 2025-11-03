<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmCommission;
use App\Models\MlmPlan;
use App\Models\MlmMember;
use App\Services\MlmCalculationService;
use Illuminate\Http\Request;

class MlmCommissionController extends Controller
{
    protected $calculationService;

    public function __construct()
    {
        $this->calculationService = new MlmCalculationService();
    }

    public function index(Request $request)
    {
        $query = MlmCommission::with(['member.user', 'user', 'plan', 'fromMember.user'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('plan_id')) {
            $query->where('mlm_plan_id', $request->plan_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('member_id')) {
            $query->where('mlm_member_id', $request->member_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $commissions = $query->paginate(50);
        $plans = MlmPlan::all();

        // Statistics
        $stats = [
            'pending_count' => MlmCommission::pending()->count(),
            'pending_amount' => MlmCommission::pending()->sum('commission_amount'),
            'approved_count' => MlmCommission::approved()->count(),
            'approved_amount' => MlmCommission::approved()->sum('commission_amount'),
            'paid_count' => MlmCommission::paid()->count(),
            'paid_amount' => MlmCommission::paid()->sum('commission_amount'),
        ];

        return view('admin.mlm.commissions.index', compact('commissions', 'plans', 'stats'));
    }

    public function show(MlmCommission $commission)
    {
        $commission->load([
            'member.user',
            'user',
            'plan',
            'fromMember.user',
            'walletTransaction',
        ]);

        return view('admin.mlm.commissions.show', compact('commission'));
    }

    public function approve(Request $request)
    {
        $validated = $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:mlm_commissions,id',
        ]);

        $count = $this->calculationService->approvePendingCommissions($validated['commission_ids']);

        return response()->json([
            'success' => true,
            'message' => "{$count} commissions approved",
            'count' => $count,
        ]);
    }

    public function approveAll(Request $request)
    {
        $count = $this->calculationService->approvePendingCommissions();

        return redirect()
            ->route('admin.mlm.commissions.index')
            ->with('success', "{$count} commissions approved");
    }

    public function reject(Request $request, MlmCommission $commission)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $commission->reject($validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Commission rejected',
        ]);
    }

    public function pay(Request $request)
    {
        $validated = $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:mlm_commissions,id',
        ]);

        try {
            $count = $this->calculationService->payApprovedCommissions($validated['commission_ids']);

            return response()->json([
                'success' => true,
                'message' => "{$count} commissions paid",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error paying commissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function payAll(Request $request)
    {
        try {
            $count = $this->calculationService->payApprovedCommissions();

            return redirect()
                ->route('admin.mlm.commissions.index')
                ->with('success', "{$count} commissions paid successfully");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.mlm.commissions.index')
                ->with('error', 'Error paying commissions: ' . $e->getMessage());
        }
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject,pay',
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:mlm_commissions,id',
            'reason' => 'nullable|string',
        ]);

        $commissions = MlmCommission::whereIn('id', $validated['commission_ids'])->get();

        $count = 0;

        foreach ($commissions as $commission) {
            switch ($validated['action']) {
                case 'approve':
                    if ($commission->status === 'pending') {
                        $commission->approve();
                        $count++;
                    }
                    break;

                case 'reject':
                    if ($commission->status === 'pending') {
                        $commission->reject($validated['reason'] ?? 'Bulk rejection');
                        $count++;
                    }
                    break;

                case 'pay':
                    if ($commission->status === 'approved') {
                        // Will be handled by payApprovedCommissions
                        $count++;
                    }
                    break;
            }
        }

        if ($validated['action'] === 'pay') {
            $count = $this->calculationService->payApprovedCommissions($validated['commission_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} commissions processed",
            'count' => $count,
        ]);
    }
}
