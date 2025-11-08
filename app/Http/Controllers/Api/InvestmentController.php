<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use App\Models\StakingPosition;
use App\Models\RoiDistribution;
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
     * Get available investment plans
     */
    public function plans()
    {
        $user = Auth::user();

        $plans = InvestmentPlan::active()
            ->available()
            ->orderBy('sort_order')
            ->orderBy('min_amount')
            ->get()
            ->filter(function ($plan) use ($user) {
                return $plan->canUserInvest($user);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * Get plan details
     */
    public function showPlan(InvestmentPlan $plan)
    {
        $user = Auth::user();

        if (!$plan->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'แผนการลงทุนนี้ไม่พร้อมใช้งาน',
            ], 404);
        }

        $canInvest = $plan->canUserInvest($user);

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'can_invest' => $canInvest,
                'expected_roi' => [
                    'min' => $plan->calculateExpectedROI($plan->min_amount),
                    'max' => $plan->max_amount ? $plan->calculateExpectedROI($plan->max_amount) : null,
                ],
            ],
        ]);
    }

    /**
     * Calculate expected ROI
     */
    public function calculateROI(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:investment_plans,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $plan = InvestmentPlan::findOrFail($request->plan_id);
        $user = Auth::user();

        $expectedRoi = $plan->calculateExpectedROI($request->amount);
        $dailyRoi = $plan->calculateDailyROI($request->amount);

        // Apply rank bonus
        $rankMultiplier = 1.0;
        if ($user->current_rank_id && $user->currentRank) {
            $rankMultiplier = $user->currentRank->bonus_multiplier ?? 1.0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'expected_roi' => $expectedRoi * $rankMultiplier,
                'daily_roi' => $dailyRoi * $rankMultiplier,
                'total_return' => $request->amount + ($expectedRoi * $rankMultiplier),
                'rank_multiplier' => $rankMultiplier,
                'roi_rate' => $plan->roi_rate,
                'term_days' => $plan->term_days,
                'lock_days' => $plan->lock_days,
            ],
        ]);
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
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'position' => $result['position'],
                    'transaction' => $result['transaction'],
                ],
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error' => $result['error'] ?? null,
        ], 400);
    }

    /**
     * Get user's investment summary
     */
    public function summary()
    {
        $user = Auth::user();
        $summary = $this->investmentService->getUserInvestmentSummary($user);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get user's staking positions
     */
    public function positions(Request $request)
    {
        $user = Auth::user();

        $query = StakingPosition::where('user_id', $user->id)
            ->with(['investmentPlan']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $positions = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data' => $positions,
        ]);
    }

    /**
     * Get position details
     */
    public function showPosition(StakingPosition $position)
    {
        if ($position->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้',
            ], 403);
        }

        $position->load(['investmentPlan', 'roiDistributions']);

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

        return response()->json([
            'success' => true,
            'data' => [
                'position' => $position,
                'withdrawal_info' => $withdrawalInfo,
            ],
        ]);
    }

    /**
     * Withdraw from position
     */
    public function withdraw(Request $request, StakingPosition $position)
    {
        if ($position->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลนี้',
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->investmentService->withdrawInvestment(
            $position,
            $request->reason
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Get ROI distributions
     */
    public function distributions(Request $request)
    {
        $user = Auth::user();

        $query = RoiDistribution::where('user_id', $user->id)
            ->with(['stakingPosition.investmentPlan']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by position
        if ($request->has('position_id')) {
            $query->where('staking_position_id', $request->position_id);
        }

        $distributions = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $distributions,
        ]);
    }
}
