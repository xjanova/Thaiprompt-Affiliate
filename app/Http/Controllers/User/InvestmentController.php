<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use App\Models\StakingPosition;
use App\Services\InvestmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    protected InvestmentService $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;
    }

    /**
     * Display investment dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's investment summary
        $summary = $this->investmentService->getUserInvestmentSummary($user);

        // Get user's positions
        $positions = StakingPosition::where('user_id', $user->id)
            ->with(['investmentPlan', 'roiDistributions'])
            ->latest()
            ->paginate(10);

        // Get recent ROI distributions
        $recentDistributions = \App\Models\RoiDistribution::where('user_id', $user->id)
            ->with('stakingPosition.investmentPlan')
            ->latest()
            ->limit(10)
            ->get();

        return view('user.investments.index', compact('summary', 'positions', 'recentDistributions'));
    }

    /**
     * Display investment plans
     */
    public function plans()
    {
        $user = Auth::user();

        // Get available investment plans
        $plans = InvestmentPlan::active()
            ->available()
            ->orderBy('sort_order')
            ->orderBy('min_amount')
            ->get();

        // Filter plans user can invest in
        $plans = $plans->filter(function ($plan) use ($user) {
            return $plan->canUserInvest($user);
        });

        return view('user.investments.plans', compact('plans'));
    }

    /**
     * Show specific plan details
     */
    public function showPlan(InvestmentPlan $plan)
    {
        $user = Auth::user();

        if (!$plan->is_active) {
            abort(404);
        }

        $canInvest = $plan->canUserInvest($user);
        $expectedRoi = $plan->calculateExpectedROI($plan->min_amount);

        return view('user.investments.plan-details', compact('plan', 'canInvest', 'expectedRoi'));
    }

    /**
     * Create new investment
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:investment_plans,id',
            'amount' => 'required|numeric|min:0',
            'auto_compound' => 'boolean',
        ]);

        $user = Auth::user();
        $plan = InvestmentPlan::findOrFail($request->plan_id);

        $result = $this->investmentService->createInvestment(
            $user,
            $plan,
            $request->amount,
            $request->boolean('auto_compound', false)
        );

        if ($result['success']) {
            return redirect()
                ->route('user.investments.index')
                ->with('success', $result['message']);
        }

        return back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * Show position details
     */
    public function show(StakingPosition $position)
    {
        $this->authorize('view', $position);

        $position->load(['investmentPlan', 'roiDistributions' => function ($query) {
            $query->latest()->limit(20);
        }]);

        // Calculate withdrawal info
        $withdrawalInfo = null;
        if ($position->canWithdraw()) {
            $withdrawalInfo = $position->isLocked()
                ? $position->calculateEarlyWithdrawalAmount()
                : [
                    'principal' => $position->amount,
                    'earned_roi' => $position->earned_roi,
                    'total' => $position->amount + $position->earned_roi,
                    'penalty' => 0,
                    'net_amount' => $position->amount + $position->earned_roi,
                ];
        }

        return view('user.investments.show', compact('position', 'withdrawalInfo'));
    }

    /**
     * Withdraw from position
     */
    public function withdraw(Request $request, StakingPosition $position)
    {
        $this->authorize('withdraw', $position);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->investmentService->withdrawInvestment(
            $position,
            $request->reason
        );

        if ($result['success']) {
            return redirect()
                ->route('user.investments.index')
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Get ROI distributions for a position
     */
    public function distributions(StakingPosition $position)
    {
        $this->authorize('view', $position);

        $distributions = $position->roiDistributions()
            ->latest()
            ->paginate(20);

        return view('user.investments.distributions', compact('position', 'distributions'));
    }

    /**
     * Calculate expected ROI (AJAX)
     */
    public function calculateROI(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:investment_plans,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $plan = InvestmentPlan::findOrFail($request->plan_id);
        $user = Auth::user();

        // Calculate expected ROI
        $expectedRoi = $plan->calculateExpectedROI($request->amount);
        $dailyRoi = $plan->calculateDailyROI($request->amount);

        // Apply rank bonus
        $rankMultiplier = 1.0;
        if ($user->current_rank_id && $user->currentRank) {
            $rankMultiplier = $user->currentRank->bonus_multiplier ?? 1.0;
        }

        return response()->json([
            'success' => true,
            'expected_roi' => $expectedRoi * $rankMultiplier,
            'daily_roi' => $dailyRoi * $rankMultiplier,
            'total_return' => $request->amount + ($expectedRoi * $rankMultiplier),
            'rank_multiplier' => $rankMultiplier,
            'roi_rate' => $plan->roi_rate,
            'term_days' => $plan->term_days,
            'lock_days' => $plan->lock_days,
        ]);
    }
}
