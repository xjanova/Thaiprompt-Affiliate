<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\MlmCommission;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin Mobile API: DashboardController
 *
 * รวมสถิติหลักสำหรับหน้า Dashboard ของ Admin Mobile App
 * Mirror ข้อมูลของ Admin\DashboardController@index แต่คืนเป็น JSON
 */
class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     *
     * คืนข้อมูลรวมสำหรับหน้า Dashboard:
     * - hero stats (รายได้รวม, สมาชิก, ออเดอร์ ฯลฯ)
     * - sparkline 30 วันล่าสุด
     * - quick action counts (รออนุมัติ, ถอนเงิน, KYC ฯลฯ)
     * - module shortcuts data
     */
    public function index(Request $request): JsonResponse
    {
        // ── Hero stats ──
        $totalUsers = $this->safeCount(User::class);
        $totalAffiliates = $this->safeCount(Affiliate::class);

        $monthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // รายได้แพลตฟอร์มเดือนนี้ (รวม commission ที่จ่ายแล้ว)
        $monthlyRevenue = $this->sumCommissionByPeriod($monthStart, Carbon::now());
        $lastMonthRevenue = $this->sumCommissionByPeriod($lastMonthStart, $lastMonthEnd);
        $revenueGrowth = $this->growthPct($lastMonthRevenue, $monthlyRevenue);

        // ── Stat tiles ──
        $newUsersToday = $this->safeWhere(User::class, fn ($q) => $q->whereDate('created_at', today()));

        $ordersTotal = $this->safeCount(Order::class);
        $ordersPending = $this->safeWhere(Order::class, fn ($q) => $q->where('status', 'pending'));

        $pendingWithdrawals = $this->safeWhere(WithdrawalRequest::class, fn ($q) => $q->where('status', 'pending'));
        $pendingCommissions = $this->safeWhere(MlmCommission::class, fn ($q) => $q->where('status', 'pending'));
        // ใช้ count อย่างเดียวพอ ไม่ต้อง sum ตรงนี้

        // ── Sparkline (30 วันล่าสุด) ──
        $sparkline = $this->dailyCommissions(30);

        // ── Quick actions ──
        $quickActions = [
            'approvals' => $pendingCommissions,
            'withdrawals' => $pendingWithdrawals,
            'kyc_pending' => $this->safeKycPendingCount(),
            'reports' => 0, // placeholder
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'hero' => [
                    'monthly_revenue' => round($monthlyRevenue, 2),
                    'last_month_revenue' => round($lastMonthRevenue, 2),
                    'revenue_growth_pct' => $revenueGrowth,
                    'currency' => 'THB',
                ],
                'stats' => [
                    'total_users' => $totalUsers,
                    'total_affiliates' => $totalAffiliates,
                    'new_users_today' => $newUsersToday,
                    'orders_total' => $ordersTotal,
                    'orders_pending' => $ordersPending,
                    'pending_withdrawals' => $pendingWithdrawals,
                ],
                'sparkline' => $sparkline,
                'quick_actions' => $quickActions,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/admin/dashboard/sparkline?days=30
     *
     * คืน sparkline สำหรับช่วงเวลาที่กำหนด (สำหรับ refresh แยก)
     */
    public function sparkline(Request $request): JsonResponse
    {
        $days = max(1, min(90, (int) $request->input('days', 30)));

        return response()->json([
            'success' => true,
            'data' => [
                'days' => $days,
                'series' => $this->dailyCommissions($days),
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Helpers (ทุกตัวต้องไม่ throw ถ้าตารางไม่มี เพื่อกัน fail dashboard ทั้งหน้า)
    // ────────────────────────────────────────────────────────────

    private function safeCount(string $modelClass): int
    {
        try {
            return $modelClass::count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeWhere(string $modelClass, callable $where): int
    {
        try {
            return $where($modelClass::query())->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function sumCommissionByPeriod(Carbon $start, Carbon $end): float
    {
        try {
            return (float) MlmCommission::where('status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->sum('commission_amount');
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private function dailyCommissions(int $days): array
    {
        try {
            $rows = MlmCommission::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(commission_amount) as total')
            )
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return $rows->map(fn ($r) => [
                'date' => (string) $r->date,
                'count' => (int) $r->count,
                'total' => round((float) $r->total, 2),
            ])->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function safeKycPendingCount(): int
    {
        try {
            // ลองค้นหา KYC table หลายชื่อที่อาจใช้
            if (\Schema::hasTable('kyc_verifications')) {
                return DB::table('kyc_verifications')->where('status', 'pending')->count();
            }
            if (\Schema::hasTable('user_kyc')) {
                return DB::table('user_kyc')->where('status', 'pending')->count();
            }
        } catch (\Throwable $e) {
            //
        }

        return 0;
    }

    private function growthPct(float $previous, float $current): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
