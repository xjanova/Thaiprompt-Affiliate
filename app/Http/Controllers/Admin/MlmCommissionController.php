<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmCommission;
use App\Models\MlmPlan;
use App\Services\MlmCalculationService;
use Illuminate\Http\Request;

class MlmCommissionController extends Controller
{
    protected $calculationService;

    public function __construct()
    {
        $this->calculationService = new MlmCalculationService;
    }

    public function index(Request $request)
    {
        $query = MlmCommission::with(['member.user', 'user', 'plan', 'fromMember.user'])
            ->orderBy('created_at', 'desc');

        // ตัวกรอง — ใช้ filled() (เดิม has() ทำให้กดกรองด้วยช่องว่างแล้วได้ where = null → ไม่เจออะไรเลย)
        if ($request->filled('plan_id')) {
            $query->where('mlm_plan_id', $request->plan_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('member_id')) {
            $query->where('mlm_member_id', $request->member_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $commissions = $query->paginate(50)->withQueryString();
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
            'message' => "อนุมัติคอมมิชชั่นแล้ว {$count} รายการ",
            'count' => $count,
        ]);
    }

    public function approveAll(Request $request)
    {
        $count = $this->calculationService->approvePendingCommissions();

        return redirect()
            ->route('admin.mlm.commissions.index')
            ->with('success', "อนุมัติคอมมิชชั่นแล้ว {$count} รายการ");
    }

    public function reject(Request $request, MlmCommission $commission)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $commission->reject($validated['reason']);

        if (! $request->expectsJson()) {
            return back()->with('success', 'ปฏิเสธคอมมิชชั่นแล้ว');
        }

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคอมมิชชั่นแล้ว',
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
                'message' => "จ่ายคอมมิชชั่นแล้ว {$count} รายการ",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Admin MLM commission pay failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'code' => 'PAY_FAILED',
                'message' => 'จ่ายคอมมิชชั่นไม่สำเร็จ กรุณาลองใหม่หรือแจ้งผู้ดูแลระบบ',
            ], 500);
        }
    }

    public function payAll(Request $request)
    {
        try {
            $count = $this->calculationService->payApprovedCommissions();

            return redirect()
                ->route('admin.mlm.commissions.index')
                ->with('success', "จ่ายคอมมิชชั่นแล้ว {$count} รายการ");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Admin MLM commission pay-all failed', ['error' => $e->getMessage()]);

            return redirect()
                ->route('admin.mlm.commissions.index')
                ->with('error', 'จ่ายคอมมิชชั่นไม่สำเร็จ กรุณาลองใหม่หรือแจ้งผู้ดูแลระบบ');
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
            'message' => "ดำเนินการแล้ว {$count} รายการ",
            'count' => $count,
        ]);
    }
}
