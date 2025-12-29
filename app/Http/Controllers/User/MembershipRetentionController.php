<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MembershipRetentionAdvanceRenewal;
use App\Models\MembershipRetentionHistory;
use App\Models\MembershipRetentionRepair;
use App\Services\MembershipRetentionService;
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
     * Show how retention system works
     */
    public function howItWorks()
    {
        $settings = [
            'minimum_points_per_month' => $this->retentionService->getSetting('minimum_points_per_month', 1000),
            'grace_period_days' => $this->retentionService->getSetting('grace_period_days', 3),
            'warning_days_before_expiry' => $this->retentionService->getSetting('warning_days_before_expiry', 7),
            'repair_cost_per_point' => $this->retentionService->getSetting('repair_cost_per_point', 1.5),
            'advance_renewal_discount' => $this->retentionService->getSetting('advance_renewal_discount', 0.9),
        ];

        return view('user.retention.how-it-works', compact('settings'));
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
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
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
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
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

    /**
     * แสดงหน้าตั้งค่า Auto-Renewal
     *
     * @return \Illuminate\View\View
     */
    public function showAutoSettings()
    {
        $user = Auth::user();
        $status = $this->retentionService->getOrCreateStatus($user);

        return view('user.retention.auto-settings', compact('status'));
    }

    /**
     * บันทึกการตั้งค่า Auto-Renewal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAutoSettings(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'source' => 'required|in:wallet,commission,both',
            'priority' => 'required|in:wallet_first,commission_first',
            'months' => 'required|integer|min:1|max:12',
            'max_amount' => 'nullable|numeric|min:0',
            'notify_days' => 'required|integer|min:1|max:7',
        ]);

        $user = Auth::user();
        $status = $this->retentionService->getOrCreateStatus($user);

        try {
            $status->update([
                'auto_renewal_enabled' => $request->enabled,
                'auto_renewal_source' => $request->source,
                'auto_renewal_priority' => $request->priority,
                'auto_renewal_months' => $request->months,
                'auto_renewal_max_amount' => $request->max_amount,
                'auto_renewal_notify_before_days' => $request->notify_days,
                // รีเซ็ต failed count เมื่อตั้งค่าใหม่
                'auto_renewal_failed_count' => 0,
                'auto_renewal_paused_until' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * หยุด Auto-Renewal ชั่วคราว
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pauseAutoRenewal(Request $request)
    {
        $request->validate([
            'pause_until' => 'required|date|after:today',
        ]);

        $user = Auth::user();
        $status = $this->retentionService->getOrCreateStatus($user);

        try {
            $status->update([
                'auto_renewal_paused_until' => $request->pause_until,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'หยุด Auto-Renewal ชั่วคราวสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ยกเลิกการหยุด Auto-Renewal ชั่วคราว
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resumeAutoRenewal()
    {
        $user = Auth::user();
        $status = $this->retentionService->getOrCreateStatus($user);

        try {
            $status->update([
                'auto_renewal_paused_until' => null,
                'auto_renewal_failed_count' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'เปิดใช้งาน Auto-Renewal อีกครั้งสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงข้อมูลสำหรับ Status Bar ใน Dashboard
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatusBarData()
    {
        $user = Auth::user();
        $status = $this->retentionService->getOrCreateStatus($user);
        $statistics = $this->retentionService->getUserStatistics($user);

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'auto_renewal' => [
                    'enabled' => $status->isAutoRenewalEnabled(),
                    'paused' => $status->isAutoRenewalPaused(),
                    'can_auto_renew' => $status->canAutoRenew(),
                    'status_text' => $status->getAutoRenewalStatusText(),
                    'status_color' => $status->getAutoRenewalStatusColor(),
                    'source_label' => $status->getAutoRenewalSourceLabel(),
                    'priority_label' => $status->getAutoRenewalPriorityLabel(),
                ],
            ],
        ]);
    }
}
