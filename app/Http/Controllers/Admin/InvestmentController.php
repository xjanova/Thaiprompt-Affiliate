<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use App\Models\StakingPosition;
use App\Models\RoiDistribution;
use App\Models\Rank;
use App\Services\InvestmentService;
use App\Services\StakingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    protected InvestmentService $investmentService;
    protected StakingService $stakingService;

    public function __construct(InvestmentService $investmentService, StakingService $stakingService)
    {
        $this->investmentService = $investmentService;
        $this->stakingService = $stakingService;
    }

    /**
     * Display investment dashboard
     */
    public function index()
    {
        $stats = $this->stakingService->getStakingStatistics();

        $pendingPositions = StakingPosition::pending()
            ->with(['user', 'investmentPlan'])
            ->latest()
            ->limit(10)
            ->get();

        $recentDistributions = RoiDistribution::with(['stakingPosition', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.investments.index', compact('stats', 'pendingPositions', 'recentDistributions'));
    }

    /**
     * Display investment plans
     */
    public function plans()
    {
        $plans = InvestmentPlan::withTrashed()
            ->with('requiredRank')
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.investments.plans.index', compact('plans'));
    }

    /**
     * Show create plan form
     */
    public function createPlan()
    {
        $ranks = Rank::orderBy('level')->get();
        return view('admin.investments.plans.create', compact('ranks'));
    }

    /**
     * Store new investment plan
     */
    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gt:min_amount',
            'roi_rate' => 'required|numeric|min:0|max:100',
            'roi_frequency' => 'required|in:daily,weekly,monthly',
            'term_days' => 'required|integer|min:1',
            'lock_days' => 'required|integer|min:0',
            'allow_compound' => 'boolean',
            'allow_early_withdrawal' => 'boolean',
            'early_withdrawal_penalty' => 'nullable|numeric|min:0|max:100',
            'referral_bonus_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'max_investors' => 'nullable|integer|min:1',
            'required_rank_id' => 'nullable|exists:ranks,id',
            'requires_kyc' => 'boolean',
            'sort_order' => 'nullable|integer',
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:100',
        ]);

        $plan = InvestmentPlan::create($validated);

        return redirect()
            ->route('admin.investments.plans.index')
            ->with('success', 'สร้างแผนการลงทุนสำเร็จ');
    }

    /**
     * Show edit plan form
     */
    public function editPlan(InvestmentPlan $plan)
    {
        $ranks = Rank::orderBy('level')->get();
        return view('admin.investments.plans.edit', compact('plan', 'ranks'));
    }

    /**
     * Update investment plan
     */
    public function updatePlan(Request $request, InvestmentPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gt:min_amount',
            'roi_rate' => 'required|numeric|min:0|max:100',
            'roi_frequency' => 'required|in:daily,weekly,monthly',
            'term_days' => 'required|integer|min:1',
            'lock_days' => 'required|integer|min:0',
            'allow_compound' => 'boolean',
            'allow_early_withdrawal' => 'boolean',
            'early_withdrawal_penalty' => 'nullable|numeric|min:0|max:100',
            'referral_bonus_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'max_investors' => 'nullable|integer|min:1',
            'required_rank_id' => 'nullable|exists:ranks,id',
            'requires_kyc' => 'boolean',
            'sort_order' => 'nullable|integer',
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:100',
        ]);

        $plan->update($validated);

        return redirect()
            ->route('admin.investments.plans.index')
            ->with('success', 'อัปเดตแผนการลงทุนสำเร็จ');
    }

    /**
     * Delete investment plan
     */
    public function destroyPlan(InvestmentPlan $plan)
    {
        // Check if plan has active positions
        if ($plan->activePositions()->exists()) {
            return back()->with('error', 'ไม่สามารถลบแผนที่มีการลงทุนอยู่');
        }

        $plan->delete();

        return redirect()
            ->route('admin.investments.plans.index')
            ->with('success', 'ลบแผนการลงทุนสำเร็จ');
    }

    /**
     * Display all staking positions
     */
    public function positions(Request $request)
    {
        $query = StakingPosition::with(['user', 'investmentPlan', 'wallet']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->has('plan_id') && $request->plan_id) {
            $query->where('investment_plan_id', $request->plan_id);
        }

        // Search by user
        if ($request->has('search') && $request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $positions = $query->latest()->paginate(20);
        $plans = InvestmentPlan::all();

        return view('admin.investments.positions.index', compact('positions', 'plans'));
    }

    /**
     * Show position details
     */
    public function showPosition(StakingPosition $position)
    {
        $position->load([
            'user',
            'investmentPlan',
            'wallet',
            'referrer',
            'approvedBy',
            'roiDistributions' => function ($query) {
                $query->latest();
            }
        ]);

        return view('admin.investments.positions.show', compact('position'));
    }

    /**
     * Approve pending position
     */
    public function approvePosition(StakingPosition $position)
    {
        $result = $this->investmentService->approveInvestment($position, Auth::user());

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Reject pending position
     */
    public function rejectPosition(Request $request, StakingPosition $position)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($position->status !== 'pending') {
            return back()->with('error', 'ไม่สามารถปฏิเสธการลงทุนนี้ได้');
        }

        // Refund to wallet
        $this->investmentService->withdrawInvestment($position, $request->reason);

        $position->update([
            'status' => 'cancelled',
            'admin_notes' => $request->reason,
        ]);

        return back()->with('success', 'ปฏิเสธการลงทุนและคืนเงินให้ผู้ใช้แล้ว');
    }

    /**
     * Display ROI distributions
     */
    public function distributions(Request $request)
    {
        $query = RoiDistribution::with(['stakingPosition', 'user', 'wallet']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date') && $request->date) {
            $query->whereDate('distribution_date', $request->date);
        }

        $distributions = $query->latest()->paginate(20);

        return view('admin.investments.distributions.index', compact('distributions'));
    }

    /**
     * Manually trigger ROI distribution
     */
    public function triggerDistribution()
    {
        $result = $this->stakingService->distributeROI();

        if (isset($result['success']) && is_numeric($result['success'])) {
            return back()->with('success', "จ่าย ROI สำเร็จ {$result['success']} รายการ, ล้มเหลว {$result['failed']} รายการ");
        }

        return back()->with('error', 'เกิดข้อผิดพลาดในการจ่าย ROI');
    }

    /**
     * Retry failed distributions
     */
    public function retryFailedDistributions()
    {
        $result = $this->stakingService->retryFailedDistributions();

        if (isset($result['success']) && is_numeric($result['success'])) {
            return back()->with('success', "ลองใหม่สำเร็จ {$result['success']} รายการ");
        }

        return back()->with('error', 'เกิดข้อผิดพลาดในการลองใหม่');
    }

    /**
     * Process mature positions
     */
    public function processMaturePositions()
    {
        $result = $this->stakingService->processMaturePositions();

        if (isset($result['success']) && is_numeric($result['success'])) {
            return back()->with('success', "ประมวลผลสำเร็จ {$result['success']} รายการ");
        }

        return back()->with('error', 'เกิดข้อผิดพลาดในการประมวลผล');
    }
}
