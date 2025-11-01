<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\MembershipRetentionService;
use App\Models\MembershipRetentionHistory;
use App\Models\MembershipRetentionRepair;
use App\Models\MembershipRetentionAdvanceRenewal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipRetentionController extends Controller
{
    protected $retentionService;

    public function __construct(MembershipRetentionService $retentionService)
    {
        $this->retentionService = $retentionService;
    }

    /**
     * Display retention dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $statistics = $this->retentionService->getUserStatistics($user);
        $status = $this->retentionService->getOrCreateStatus($user);

        // Get recent history
        $history = MembershipRetentionHistory::where('user_id', $user->id)
            ->orderBy('period_month', 'desc')
            ->limit(12)
            ->get();

        // Get repairs
        $repairs = MembershipRetentionRepair::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get advance renewals
        $advanceRenewals = MembershipRetentionAdvanceRenewal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('user.retention.index', compact(
            'statistics',
            'status',
            'history',
            'repairs',
            'advanceRenewals'
        ));
    }

    /**
     * Get retention status API
     */
    public function getStatus()
    {
        $user = Auth::user();
        $statistics = $this->retentionService->getUserStatistics($user);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get history
     */
    public function history()
    {
        $user = Auth::user();

        $history = MembershipRetentionHistory::where('user_id', $user->id)
            ->orderBy('period_month', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Show repair page
     */
    public function showRepair()
    {
        $user = Auth::user();

        // Get failed periods that can be repaired
        $repairablePeriods = MembershipRetentionHistory::where('user_id', $user->id)
            ->where('status', 'failed')
            ->orderBy('period_month', 'desc')
            ->get()
            ->map(function ($history) use ($user) {
                $cost = $this->retentionService->calculateRepairCost($user, $history->period_month);
                return [
                    'period_month' => $history->period_month,
                    'required_points' => $history->required_points,
                    'earned_points' => $history->earned_points,
                    'points_needed' => max(0, $history->required_points - $history->earned_points),
                    'repair_cost' => $cost,
                ];
            });

        return view('user.retention.repair', compact('repairablePeriods'));
    }

    /**
     * Process repair
     */
    public function processRepair(Request $request)
    {
        $request->validate([
            'period_month' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        try {
            $repair = $this->retentionService->processRepair(
                $user,
                $request->period_month,
                $request->amount
            );

            return response()->json([
                'success' => true,
                'message' => 'ซ่อมสิทธิ์สำเร็จ',
                'data' => $repair,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show advance renewal page
     */
    public function showAdvanceRenewal()
    {
        $user = Auth::user();

        // Calculate costs for different months
        $renewalOptions = [];
        for ($months = 1; $months <= 12; $months++) {
            $cost = $this->retentionService->calculateAdvanceRenewalCost($months);
            $renewalOptions[] = [
                'months' => $months,
                'cost' => $cost,
                'cost_per_month' => $cost / $months,
            ];
        }

        return view('user.retention.advance-renewal', compact('renewalOptions'));
    }

    /**
     * Process advance renewal
     */
    public function processAdvanceRenewal(Request $request)
    {
        $request->validate([
            'months' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        try {
            $renewal = $this->retentionService->processAdvanceRenewal(
                $user,
                $request->months,
                $request->amount
            );

            return response()->json([
                'success' => true,
                'message' => 'เติมวันล่วงหน้าสำเร็จ',
                'data' => $renewal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get widget data (for Life Power widget)
     */
    public function getWidgetData()
    {
        $user = Auth::user();
        $statistics = $this->retentionService->getUserStatistics($user);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $statistics['status'],
                'current_points' => $statistics['current_points'],
                'required_points' => $statistics['required_points'],
                'points_percentage' => $statistics['points_percentage'],
                'remaining_points' => $statistics['remaining_points'],
                'days_remaining' => $statistics['days_remaining'],
                'health_color' => $statistics['health_color'],
                'health_percentage' => $statistics['health_percentage'],
                'next_renewal_date' => $statistics['next_renewal_date'],
            ],
        ]);
    }
}
