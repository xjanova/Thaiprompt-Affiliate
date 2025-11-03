<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\MlmCalculationService;
use App\Services\MlmGenealogyService;
use App\Services\MlmPvService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MlmDashboardController extends Controller
{
    protected $calculationService;
    protected $genealogyService;
    protected $pvService;

    public function __construct()
    {
        $this->calculationService = new MlmCalculationService();
        $this->genealogyService = new MlmGenealogyService();
        $this->pvService = new MlmPvService();
    }

    public function index()
    {
        $user = Auth::user();
        $members = $user->mlmMembers()->with('plan')->get();

        $dashboardData = [];

        foreach ($members as $member) {
            $statistics = $this->calculationService->getMemberStatistics($member);
            $pvStats = $this->pvService->getMemberPvStatistics($member);

            $dashboardData[] = [
                'member' => $member,
                'plan' => $member->plan,
                'statistics' => $statistics,
                'pv_stats' => $pvStats,
            ];
        }

        return view('user.mlm.dashboard', compact('dashboardData'));
    }

    public function plan($memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $statistics = $this->calculationService->getMemberStatistics($member);
        $pvStats = $this->pvService->getMemberPvStatistics($member);

        return view('user.mlm.plan-dashboard', compact('member', 'statistics', 'pvStats'));
    }

    public function commissions(Request $request)
    {
        $user = Auth::user();

        $query = $user->mlmCommissions()
            ->with(['plan', 'member', 'fromMember.user'])
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

        $commissions = $query->paginate(20);

        // Statistics
        $stats = [
            'pending' => $user->mlmCommissions()->where('status', 'pending')->sum('commission_amount'),
            'approved' => $user->mlmCommissions()->where('status', 'approved')->sum('commission_amount'),
            'paid' => $user->mlmCommissions()->where('status', 'paid')->sum('commission_amount'),
            'this_month' => $user->mlmCommissions()
                ->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('commission_amount'),
        ];

        return view('user.mlm.commissions', compact('commissions', 'stats'));
    }

    public function genealogy(Request $request, $memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $treeType = $request->get('type', 'unilevel');

        return view('user.mlm.genealogy', compact('member', 'treeType'));
    }

    public function getTreeData(Request $request, $memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $treeType = $request->get('type', 'unilevel');
        $maxDepth = $request->get('depth', 5);

        $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);

        return response()->json([
            'success' => true,
            'data' => $treeData,
        ]);
    }

    public function referrals($memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $directReferrals = $member->unilevelChildren()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('user.mlm.referrals', compact('member', 'directReferrals'));
    }

    public function referralLink($memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $referralUrl = route('register', ['ref' => $member->member_code]);

        return view('user.mlm.referral-link', compact('member', 'referralUrl'));
    }

    public function pvHistory(Request $request, $memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $query = $member->pvTransactions()
            ->with(['order', 'product'])
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->paginate(20);

        return view('user.mlm.pv-history', compact('member', 'transactions'));
    }

    public function binaryPosition($memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        if ($member->plan->type !== 'binary' && $member->plan->type !== 'hybrid') {
            return redirect()->route('user.mlm.dashboard')
                ->with('error', 'This plan does not support binary structure');
        }

        $position = $this->genealogyService->getMemberPosition($member, 'binary');

        return view('user.mlm.binary-position', compact('member', 'position'));
    }
}
