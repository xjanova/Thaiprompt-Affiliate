<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\MlmPlan;
use App\Models\MlmPvTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MlmReportController extends Controller
{
    public function index()
    {
        return view('admin.mlm.reports.index');
    }

    public function dashboard(Request $request)
    {
        $planId = $request->get('plan_id');

        $query = MlmMember::query();
        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        $stats = [
            'total_members' => $query->count(),
            'active_members' => (clone $query)->where('status', 'active')->count(),
            'total_pv' => (clone $query)->sum('total_pv'),
            'total_earnings' => (clone $query)->sum('total_earnings'),
            'new_members_today' => (clone $query)->whereDate('created_at', today())->count(),
            'new_members_this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
        ];

        $commissionQuery = MlmCommission::query();
        if ($planId) {
            $commissionQuery->where('mlm_plan_id', $planId);
        }

        $commissionStats = [
            'pending_count' => (clone $commissionQuery)->where('status', 'pending')->count(),
            'pending_amount' => (clone $commissionQuery)->where('status', 'pending')->sum('commission_amount'),
            'approved_count' => (clone $commissionQuery)->where('status', 'approved')->count(),
            'approved_amount' => (clone $commissionQuery)->where('status', 'approved')->sum('commission_amount'),
            'paid_count' => (clone $commissionQuery)->where('status', 'paid')->count(),
            'paid_amount' => (clone $commissionQuery)->where('status', 'paid')->sum('commission_amount'),
            'paid_this_month' => (clone $commissionQuery)->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('commission_amount'),
        ];

        $plans = MlmPlan::all();

        return view('admin.mlm.reports.dashboard', compact('stats', 'commissionStats', 'plans'));
    }

    public function memberGrowth(Request $request)
    {
        $planId = $request->get('plan_id');
        $period = $request->get('period', '30'); // days

        $startDate = now()->subDays($period);

        $query = MlmMember::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date');

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function commissionTrends(Request $request)
    {
        $planId = $request->get('plan_id');
        $period = $request->get('period', '30'); // days

        $startDate = now()->subDays($period);

        $query = MlmCommission::selectRaw('
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(commission_amount) as total_amount
            ')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date');

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function pvAnalytics(Request $request)
    {
        $planId = $request->get('plan_id');

        $query = MlmPvTransaction::selectRaw('
                transaction_type,
                COUNT(*) as count,
                SUM(pv_amount) as total_pv,
                SUM(sales_amount) as total_sales
            ')
            ->groupBy('transaction_type');

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function topPerformers(Request $request)
    {
        $planId = $request->get('plan_id');
        $limit = $request->get('limit', 10);
        $metric = $request->get('metric', 'earnings'); // earnings, pv, referrals

        $query = MlmMember::with('user');

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        switch ($metric) {
            case 'pv':
                $query->orderBy('total_pv', 'desc');
                break;
            case 'referrals':
                $query->orderBy('total_direct_referrals', 'desc');
                break;
            default:
                $query->orderBy('total_earnings', 'desc');
        }

        $members = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    public function commissionByType(Request $request)
    {
        $planId = $request->get('plan_id');

        $query = MlmCommission::selectRaw('
                type,
                COUNT(*) as count,
                SUM(commission_amount) as total_amount
            ')
            ->groupBy('type');

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function levelAnalysis(Request $request)
    {
        $planId = $request->get('plan_id');

        $query = MlmMember::selectRaw('
                unilevel_level as level,
                COUNT(*) as member_count,
                SUM(total_pv) as total_pv,
                AVG(total_earnings) as avg_earnings
            ')
            ->groupBy('unilevel_level')
            ->orderBy('unilevel_level');

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        $data = $query->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function binaryAnalysis(Request $request)
    {
        $planId = $request->get('plan_id');

        $query = MlmMember::selectRaw('
                COUNT(*) as total_members,
                AVG(left_leg_pv) as avg_left_pv,
                AVG(right_leg_pv) as avg_right_pv,
                SUM(left_leg_members) as total_left_members,
                SUM(right_leg_members) as total_right_members
            ');

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        $data = $query->first();

        // Get balance statistics
        $balanceQuery = MlmMember::selectRaw('
                CASE
                    WHEN left_leg_pv > right_leg_pv THEN "left_heavy"
                    WHEN right_leg_pv > left_leg_pv THEN "right_heavy"
                    ELSE "balanced"
                END as balance,
                COUNT(*) as count
            ')
            ->groupBy('balance');

        if ($planId) {
            $balanceQuery->where('mlm_plan_id', $planId);
        }

        $balanceData = $balanceQuery->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'balance' => $balanceData,
        ]);
    }

    public function exportMembers(Request $request)
    {
        $planId = $request->get('plan_id');

        $query = MlmMember::with(['user', 'plan']);

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        $members = $query->get();

        $csv = "Member Code,Name,Email,Plan,Status,Total PV,Total Earnings,Joined At\n";

        foreach ($members as $member) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $member->member_code,
                $member->user->name,
                $member->user->email,
                $member->plan->name,
                $member->status,
                $member->total_pv,
                $member->total_earnings,
                $member->joined_at
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="mlm_members_' . now()->format('Y-m-d') . '.csv"');
    }

    public function exportCommissions(Request $request)
    {
        $planId = $request->get('plan_id');
        $status = $request->get('status');

        $query = MlmCommission::with(['member.user', 'plan']);

        if ($planId) {
            $query->where('mlm_plan_id', $planId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $commissions = $query->get();

        $csv = "Date,Member,Plan,Type,Amount,Status,Paid At\n";

        foreach ($commissions as $commission) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $commission->created_at,
                $commission->member->user->name,
                $commission->plan->name,
                $commission->type,
                $commission->commission_amount,
                $commission->status,
                $commission->paid_at ?? 'N/A'
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="mlm_commissions_' . now()->format('Y-m-d') . '.csv"');
    }
}
