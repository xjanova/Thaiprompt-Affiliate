<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\MlmRetentionHelper;
use App\Http\Controllers\Controller;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use App\Services\MlmCalculationService;
use App\Services\MlmGenealogyService;
use Illuminate\Http\Request;

class MlmMemberController extends Controller
{
    protected $genealogyService;

    protected $calculationService;

    public function __construct()
    {
        $this->genealogyService = new MlmGenealogyService;
        $this->calculationService = new MlmCalculationService;
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
        $retentionStatus = MlmRetentionHelper::getRetentionStatus($member);

        return view('admin.mlm.members.show', compact('member', 'statistics', 'retentionStatus'));
    }

    public function create()
    {
        $plans = MlmPlan::active()->get();
        $users = User::whereDoesntHave('mlmMembers')->get();
        $members = MlmMember::with('user')->where('status', 'active')->get();

        return view('admin.mlm.members.create', compact('plans', 'users', 'members'));
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
        $member->update(['is_qualified' => ! $member->is_qualified]);

        return response()->json([
            'success' => true,
            'is_qualified' => $member->is_qualified,
        ]);
    }

    public function genealogy(MlmMember $member, Request $request)
    {
        $treeType = $request->get('type', 'unilevel');
        // cap depth ที่ 10 กัน recursion หนักเกิน
        $maxDepth = min((int) $request->get('depth', 5), 10);

        try {
            $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);
        } catch (\Exception $e) {
            \Log::error('Admin genealogy error', ['member_id' => $member->id, 'error' => $e->getMessage()]);
            $treeData = null;
        }

        return view('admin.mlm.members.genealogy', compact('member', 'treeData', 'treeType'));
    }

    public function getTreeData(MlmMember $member, Request $request)
    {
        $treeType = $request->get('type', 'unilevel');
        // cap depth ที่ 10 กัน recursion หนักเกิน (เดิมรับค่า depth โดยไม่จำกัด)
        $maxDepth = min((int) $request->get('depth', 5), 10);

        try {
            $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);

            return response()->json([
                'success' => true,
                'data' => $treeData,
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin getTreeData error', ['member_id' => $member->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลผังสายงาน',
            ], 500);
        }
    }

    public function statistics(MlmMember $member)
    {
        $statistics = $this->calculationService->getMemberStatistics($member);

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }

    /**
     * ดึงข้อมูลสายเลือด (Bloodline) สำหรับ API
     * แสดงเส้นทางจาก root ลงมาถึงสมาชิกที่เลือก
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBloodlineData(MlmMember $member, Request $request)
    {
        $treeType = $request->get('type', 'unilevel');

        $bloodlineData = $this->genealogyService->getBloodlineData($member, $treeType);

        return response()->json([
            'success' => true,
            'data' => $bloodlineData,
        ]);
    }
}
