<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use App\Services\MlmGenealogyService;
use App\Services\MlmCalculationService;
use Illuminate\Http\Request;

class MlmMemberController extends Controller
{
    protected $genealogyService;
    protected $calculationService;

    public function __construct()
    {
        $this->genealogyService = new MlmGenealogyService();
        $this->calculationService = new MlmCalculationService();
    }

    public function index(Request $request)
    {
        $query = MlmMember::with(['user', 'plan', 'unilevelSponsor.user'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('plan_id')) {
            $query->where('mlm_plan_id', $request->plan_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('member_code', 'like', "%{$search}%");
        }

        $members = $query->paginate(50);
        $plans = MlmPlan::all();

        return view('admin.mlm.members.index', compact('members', 'plans'));
    }

    public function show(MlmMember $member)
    {
        $member->load([
            'user',
            'plan',
            'unilevelSponsor.user',
            'binarySponsor.user',
            'binaryParent.user',
            'unilevelChildren.user',
            'commissions' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            },
            'pvTransactions' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            },
        ]);

        $statistics = $this->calculationService->getMemberStatistics($member);

        return view('admin.mlm.members.show', compact('member', 'statistics'));
    }

    public function create()
    {
        $plans = MlmPlan::active()->get();
        $users = User::whereDoesntHave('mlmMembers')->get();

        return view('admin.mlm.members.create', compact('plans', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mlm_plan_id' => 'required|exists:mlm_plans,id',
            'unilevel_sponsor_id' => 'required|exists:mlm_members,id',
            'binary_sponsor_id' => 'nullable|exists:mlm_members,id',
            'binary_position' => 'nullable|in:left,right',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $member = $this->genealogyService->registerMember(
            $user,
            $validated['mlm_plan_id'],
            $validated['unilevel_sponsor_id'],
            $validated['binary_sponsor_id'] ?? $validated['unilevel_sponsor_id'],
            $validated['binary_position'] ?? null
        );

        return redirect()
            ->route('admin.mlm.members.show', $member)
            ->with('success', 'MLM Member registered successfully');
    }

    public function updateStatus(Request $request, MlmMember $member)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $member->update($validated);

        return response()->json([
            'success' => true,
            'status' => $member->status,
        ]);
    }

    public function toggleQualification(MlmMember $member)
    {
        $member->update(['is_qualified' => !$member->is_qualified]);

        return response()->json([
            'success' => true,
            'is_qualified' => $member->is_qualified,
        ]);
    }

    public function genealogy(MlmMember $member, Request $request)
    {
        $treeType = $request->get('type', 'unilevel');
        $maxDepth = $request->get('depth', 5);

        $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);

        return view('admin.mlm.members.genealogy', compact('member', 'treeData', 'treeType'));
    }

    public function getTreeData(MlmMember $member, Request $request)
    {
        $treeType = $request->get('type', 'unilevel');
        $maxDepth = $request->get('depth', 5);

        $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);

        return response()->json([
            'success' => true,
            'data' => $treeData,
        ]);
    }

    public function statistics(MlmMember $member)
    {
        $statistics = $this->calculationService->getMemberStatistics($member);

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }
}
