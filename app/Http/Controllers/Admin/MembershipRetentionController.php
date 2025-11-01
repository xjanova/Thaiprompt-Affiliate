<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MembershipRetentionService;
use App\Models\User;
use App\Models\MembershipRetentionStatus;
use App\Models\MembershipRetentionHistory;
use App\Models\MembershipRetentionSetting;
use Illuminate\Http\Request;

class MembershipRetentionController extends Controller
{
    protected $retentionService;

    public function __construct(MembershipRetentionService $retentionService)
    {
        $this->retentionService = $retentionService;
    }

    /**
     * Display retention management dashboard
     */
    public function index()
    {
        // Get statistics
        $totalUsers = MembershipRetentionStatus::count();
        $activeUsers = MembershipRetentionStatus::where('status', 'active')->count();
        $expiredUsers = MembershipRetentionStatus::where('status', 'expired')->count();
        $expiringUsers = MembershipRetentionStatus::expiringSoon(7)->count();

        // Get recent statuses
        $recentStatuses = MembershipRetentionStatus::with('user')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        // Get settings
        $settings = MembershipRetentionSetting::all()->pluck('value', 'key');

        return view('admin.retention.index', compact(
            'totalUsers',
            'activeUsers',
            'expiredUsers',
            'expiringUsers',
            'recentStatuses',
            'settings'
        ));
    }

    /**
     * List all users with retention status
     */
    public function users(Request $request)
    {
        $query = MembershipRetentionStatus::with('user');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by user name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('next_renewal_date', 'asc')->paginate(50);

        return view('admin.retention.users', compact('users'));
    }

    /**
     * Show user details
     */
    public function showUser($userId)
    {
        $user = User::findOrFail($userId);
        $statistics = $this->retentionService->getUserStatistics($user);
        $status = $this->retentionService->getOrCreateStatus($user);

        $history = MembershipRetentionHistory::where('user_id', $userId)
            ->orderBy('period_month', 'desc')
            ->paginate(20);

        return view('admin.retention.user-details', compact('user', 'statistics', 'status', 'history'));
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'minimum_points_per_month' => 'required|numeric|min:0',
            'repair_cost_per_point' => 'required|numeric|min:0',
            'advance_renewal_discount' => 'required|numeric|min:0|max:1',
            'grace_period_days' => 'required|integer|min:0',
            'warning_days_before_expiry' => 'required|integer|min:1',
            'enable_retention_system' => 'required|boolean',
            'block_commission_if_expired' => 'required|boolean',
        ]);

        try {
            MembershipRetentionSetting::set('minimum_points_per_month', $request->minimum_points_per_month, 'number');
            MembershipRetentionSetting::set('repair_cost_per_point', $request->repair_cost_per_point, 'number');
            MembershipRetentionSetting::set('advance_renewal_discount', $request->advance_renewal_discount, 'number');
            MembershipRetentionSetting::set('grace_period_days', $request->grace_period_days, 'number');
            MembershipRetentionSetting::set('warning_days_before_expiry', $request->warning_days_before_expiry, 'number');
            MembershipRetentionSetting::set('enable_retention_system', $request->enable_retention_system, 'boolean');
            MembershipRetentionSetting::set('block_commission_if_expired', $request->block_commission_if_expired, 'boolean');

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manual initialize user
     */
    public function initializeUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $status = $this->retentionService->initializeUser($user);

            return response()->json([
                'success' => true,
                'message' => 'เริ่มต้นระบบสำหรับผู้ใช้สำเร็จ',
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manual process renewal
     */
    public function processRenewal()
    {
        try {
            $results = $this->retentionService->processMonthlyRenewal();

            return response()->json([
                'success' => true,
                'message' => 'ประมวลผลรายเดือนสำเร็จ',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manual expire users
     */
    public function expireUsers()
    {
        try {
            $results = $this->retentionService->expireInactiveUsers();

            return response()->json([
                'success' => true,
                'message' => 'ตรวจสอบและหมดอายุผู้ใช้สำเร็จ',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get expiring users
     */
    public function getExpiringUsers(Request $request)
    {
        $days = $request->input('days', 7);
        $users = $this->retentionService->getExpiringUsers($days);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Export report
     */
    public function exportReport(Request $request)
    {
        // This would generate a CSV or Excel report
        // Implementation depends on your export library
        return response()->json([
            'success' => true,
            'message' => 'Report generation not implemented yet',
        ]);
    }
}
