<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\AiBotProfile;
use App\Models\HotelBooking;
use App\Models\PosTransaction;
use App\Models\ServiceBooking;
use App\Models\CryptoTransaction;
use App\Models\Rank;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * UnifiedReportService - บริการดึงข้อมูลรายงานรวมจากทุกระบบ
 *
 * รวมข้อมูลจากหลายระบบมาแสดงผลในรายงานเดียว:
 * - MLM/Affiliate
 * - E-commerce
 * - Wallet/Finance
 * - AI Bot
 * - Hotel
 * - POS
 * - Crypto/TPIX
 * - และระบบอื่นๆ
 */
class UnifiedReportService
{
    /**
     * ระยะเวลา cache (นาที)
     */
    protected int $cacheMinutes = 5;

    /**
     * ดึงข้อมูลสรุปภาพรวมทั้งหมด (Executive Summary)
     *
     * @param string $period ช่วงเวลา (today, week, month, year, custom)
     * @param string|null $startDate วันที่เริ่มต้น
     * @param string|null $endDate วันที่สิ้นสุด
     * @return array
     */
    public function getExecutiveSummary(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        $dates = $this->getDateRange($period, $startDate, $endDate);

        return Cache::remember("unified_report.executive_summary.{$period}.{$startDate}.{$endDate}", $this->cacheMinutes * 60, function () use ($dates) {
            return [
                'period' => [
                    'start' => $dates['start']->toDateString(),
                    'end' => $dates['end']->toDateString(),
                ],
                'users' => $this->getUserStats($dates),
                'revenue' => $this->getRevenueStats($dates),
                'mlm' => $this->getMlmStats($dates),
                'ecommerce' => $this->getEcommerceStats($dates),
                'wallet' => $this->getWalletStats($dates),
                'ai_bot' => $this->getAiBotStats($dates),
                'hotel' => $this->getHotelStats($dates),
                'pos' => $this->getPosStats($dates),
                'crypto' => $this->getCryptoStats($dates),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * ดึงข้อมูลแนวโน้ม (Trends) สำหรับกราฟ
     *
     * @param string $period ช่วงเวลา
     * @param string $groupBy กลุ่มตาม (day, week, month)
     * @return array
     */
    public function getTrends(string $period = 'month', string $groupBy = 'day'): array
    {
        $dates = $this->getDateRange($period);

        return Cache::remember("unified_report.trends.{$period}.{$groupBy}", $this->cacheMinutes * 60, function () use ($dates, $groupBy) {
            $format = match ($groupBy) {
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                default => '%Y-%m-%d',
            };

            return [
                'users' => $this->getUserTrends($dates, $format),
                'revenue' => $this->getRevenueTrends($dates, $format),
                'orders' => $this->getOrderTrends($dates, $format),
                'commissions' => $this->getCommissionTrends($dates, $format),
            ];
        });
    }

    /**
     * ดึงข้อมูลรายงาน MLM/Affiliate
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getMlmReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getMlmStats($dates),
            'rank_distribution' => $this->getRankDistribution(),
            'top_earners' => $this->getTopEarners($dates, 10),
            'top_recruiters' => $this->getTopRecruiters($dates, 10),
            'commission_by_type' => $this->getCommissionByType($dates),
            'level_analysis' => $this->getLevelAnalysis(),
            'growth_rate' => $this->getMlmGrowthRate($dates),
        ];
    }

    /**
     * ดึงข้อมูลรายงาน E-commerce
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getEcommerceReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getEcommerceStats($dates),
            'top_products' => $this->getTopProducts($dates, 10),
            'sales_by_category' => $this->getSalesByCategory($dates),
            'order_status_distribution' => $this->getOrderStatusDistribution($dates),
            'average_order_value' => $this->getAverageOrderValue($dates),
            'customer_stats' => $this->getCustomerStats($dates),
        ];
    }

    /**
     * ดึงข้อมูลรายงานการเงิน (Wallet/Finance)
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getFinanceReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getWalletStats($dates),
            'transaction_types' => $this->getTransactionTypes($dates),
            'top_wallets' => $this->getTopWallets(10),
            'withdrawal_stats' => $this->getWithdrawalStats($dates),
            'deposit_stats' => $this->getDepositStats($dates),
            'daily_flow' => $this->getDailyFlow($dates),
        ];
    }

    /**
     * ดึงข้อมูลรายงาน AI Bot
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getAiBotReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getAiBotStats($dates),
            'popular_bots' => $this->getPopularBots($dates, 10),
            'usage_by_feature' => $this->getAiUsageByFeature($dates),
            'revenue_from_ai' => $this->getAiRevenue($dates),
        ];
    }

    /**
     * ดึงข้อมูลรายงานโรงแรม
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getHotelReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getHotelStats($dates),
            'occupancy_rate' => $this->getOccupancyRate($dates),
            'revenue_by_hotel' => $this->getRevenueByHotel($dates, 10),
            'booking_status' => $this->getBookingStatusDistribution($dates),
        ];
    }

    /**
     * ดึงข้อมูลรายงาน POS
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getPosReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getPosStats($dates),
            'sales_by_device' => $this->getSalesByDevice($dates),
            'peak_hours' => $this->getPeakHours($dates),
            'payment_methods' => $this->getPaymentMethodStats($dates),
        ];
    }

    /**
     * ดึงข้อมูลรายงาน Crypto/TPIX
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getCryptoReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getCryptoStats($dates),
            'transaction_types' => $this->getCryptoTransactionTypes($dates),
            'wallet_distribution' => $this->getCryptoWalletDistribution(),
        ];
    }

    /**
     * ดึงข้อมูลรายงาน HRM/บุคลากร
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getHrmReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getHrmStats($dates),
            'departments' => $this->getDepartmentStats($dates),
            'attendance' => $this->getAttendanceStats($dates),
            'payroll' => $this->getPayrollStats($dates),
        ];
    }

    /**
     * ดึงข้อมูลรายงานการเรียนรู้
     *
     * @param string $period ช่วงเวลา
     * @return array
     */
    public function getLearningReport(string $period = 'month'): array
    {
        $dates = $this->getDateRange($period);

        return [
            'summary' => $this->getLearningStats($dates),
            'courses' => $this->getCourseStats($dates),
            'enrollments' => $this->getEnrollmentStats($dates),
            'completion_rate' => $this->getCourseCompletionRate($dates),
        ];
    }

    /**
     * ดึงรายการระบบทั้งหมดที่สามารถสร้างรายงานได้
     *
     * @return array
     */
    public function getAvailableSystems(): array
    {
        return [
            [
                'id' => 'executive',
                'name' => 'ภาพรวมผู้บริหาร',
                'name_en' => 'Executive Summary',
                'icon' => 'chart-bar',
                'description' => 'รายงานสรุปภาพรวมทั้งหมดของระบบ',
                'available' => true,
            ],
            [
                'id' => 'mlm',
                'name' => 'MLM/Affiliate',
                'name_en' => 'MLM/Affiliate',
                'icon' => 'users',
                'description' => 'รายงานระบบเครือข่าย สมาชิก คอมมิชชั่น',
                'available' => true,
            ],
            [
                'id' => 'ecommerce',
                'name' => 'E-commerce',
                'name_en' => 'E-commerce',
                'icon' => 'shopping-cart',
                'description' => 'รายงานยอดขาย สินค้า คำสั่งซื้อ',
                'available' => true,
            ],
            [
                'id' => 'finance',
                'name' => 'การเงิน/กระเป๋าเงิน',
                'name_en' => 'Finance/Wallet',
                'icon' => 'wallet',
                'description' => 'รายงานการเงิน การถอน ยอดคงเหลือ',
                'available' => true,
            ],
            [
                'id' => 'ai_bot',
                'name' => 'AI Bot',
                'name_en' => 'AI Bot',
                'icon' => 'robot',
                'description' => 'รายงานการใช้งาน AI Bot',
                'available' => true,
            ],
            [
                'id' => 'hotel',
                'name' => 'โรงแรม',
                'name_en' => 'Hotel',
                'icon' => 'building',
                'description' => 'รายงานการจองห้องพัก รายได้โรงแรม',
                'available' => true,
            ],
            [
                'id' => 'pos',
                'name' => 'POS',
                'name_en' => 'Point of Sale',
                'icon' => 'cash-register',
                'description' => 'รายงานยอดขายหน้าร้าน',
                'available' => true,
            ],
            [
                'id' => 'crypto',
                'name' => 'Crypto/TPIX',
                'name_en' => 'Crypto/TPIX',
                'icon' => 'bitcoin',
                'description' => 'รายงาน Cryptocurrency และ TPIX Token',
                'available' => true,
            ],
            [
                'id' => 'hrm',
                'name' => 'บุคลากร',
                'name_en' => 'HRM',
                'icon' => 'user-tie',
                'description' => 'รายงานพนักงาน การลา เงินเดือน',
                'available' => true,
            ],
            [
                'id' => 'learning',
                'name' => 'การเรียนรู้',
                'name_en' => 'Learning',
                'icon' => 'graduation-cap',
                'description' => 'รายงานหลักสูตร การลงทะเบียน',
                'available' => true,
            ],
        ];
    }

    // =================================================================
    // PRIVATE METHODS - ดึงข้อมูลแต่ละส่วน
    // =================================================================

    /**
     * คำนวณช่วงวันที่ตาม period
     */
    protected function getDateRange(string $period, ?string $startDate = null, ?string $endDate = null): array
    {
        if ($period === 'custom' && $startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        $end = now()->endOfDay();
        $start = match ($period) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'week' => now()->subWeek()->startOfDay(),
            'month' => now()->subMonth()->startOfDay(),
            'quarter' => now()->subQuarter()->startOfDay(),
            'year' => now()->subYear()->startOfDay(),
            default => now()->subMonth()->startOfDay(),
        };

        return ['start' => $start, 'end' => $end];
    }

    /**
     * สถิติผู้ใช้
     */
    protected function getUserStats(array $dates): array
    {
        $query = User::query();

        // ตรวจสอบว่ามี column last_login_at หรือไม่
        $activeUsers = 0;
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'last_login_at')) {
            // ใช้ last_login_at ถ้ามี
            $activeUsers = User::where('last_login_at', '>=', $dates['start'])->count();
        } else {
            // ใช้ PageView แทน - นับ unique users ที่มีการเข้าชมในช่วงเวลาที่กำหนด
            if (class_exists(\App\Models\PageView::class)) {
                $activeUsers = \App\Models\PageView::whereBetween('viewed_at', [$dates['start'], $dates['end']])
                    ->whereNotNull('user_id')
                    ->distinct('user_id')
                    ->count('user_id');
            }
        }

        return [
            'total' => User::count(),
            'new' => User::whereBetween('created_at', [$dates['start'], $dates['end']])->count(),
            'active' => $activeUsers,
            'verified' => User::whereNotNull('email_verified_at')->count(),
        ];
    }

    /**
     * สถิติรายได้
     */
    protected function getRevenueStats(array $dates): array
    {
        // รายได้จาก E-commerce
        $ecommerceRevenue = 0;
        if (class_exists(\App\Models\Order::class)) {
            $ecommerceRevenue = Order::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
        }

        // รายได้จาก Hotel
        $hotelRevenue = 0;
        if (class_exists(\App\Models\HotelBooking::class)) {
            $hotelRevenue = HotelBooking::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
        }

        // รายได้จาก POS
        $posRevenue = 0;
        if (class_exists(\App\Models\PosTransaction::class)) {
            $posRevenue = PosTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
        }

        // รายได้จาก Service
        $serviceRevenue = 0;
        if (class_exists(\App\Models\ServiceBooking::class)) {
            $serviceRevenue = ServiceBooking::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
        }

        $total = $ecommerceRevenue + $hotelRevenue + $posRevenue + $serviceRevenue;

        return [
            'total' => $total,
            'ecommerce' => $ecommerceRevenue,
            'hotel' => $hotelRevenue,
            'pos' => $posRevenue,
            'service' => $serviceRevenue,
            'formatted_total' => number_format($total, 2),
        ];
    }

    /**
     * สถิติ MLM
     */
    protected function getMlmStats(array $dates): array
    {
        $totalAffiliates = 0;
        $newAffiliates = 0;
        $activeAffiliates = 0;
        $totalCommissions = 0;
        $paidCommissions = 0;
        $pendingCommissions = 0;

        if (class_exists(\App\Models\Affiliate::class)) {
            $totalAffiliates = Affiliate::count();
            $newAffiliates = Affiliate::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
            $activeAffiliates = Affiliate::where('status', 'active')->count();
        }

        if (class_exists(\App\Models\Commission::class)) {
            $totalCommissions = Commission::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->sum('amount') ?? 0;
            $paidCommissions = Commission::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'paid')
                ->sum('amount') ?? 0;
            $pendingCommissions = Commission::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'pending')
                ->sum('amount') ?? 0;
        }

        return [
            'total_affiliates' => $totalAffiliates,
            'new_affiliates' => $newAffiliates,
            'active_affiliates' => $activeAffiliates,
            'total_commissions' => $totalCommissions,
            'paid_commissions' => $paidCommissions,
            'pending_commissions' => $pendingCommissions,
        ];
    }

    /**
     * สถิติ E-commerce
     */
    protected function getEcommerceStats(array $dates): array
    {
        $totalOrders = 0;
        $totalRevenue = 0;
        $completedOrders = 0;
        $averageOrderValue = 0;
        $totalProducts = 0;

        if (class_exists(\App\Models\Order::class)) {
            $totalOrders = Order::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
            $completedOrders = Order::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->count();
            $totalRevenue = Order::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
            $averageOrderValue = $completedOrders > 0 ? $totalRevenue / $completedOrders : 0;
        }

        if (class_exists(\App\Models\Product::class)) {
            $totalProducts = Product::where('is_active', true)->count();
        }

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'total_revenue' => $totalRevenue,
            'average_order_value' => round($averageOrderValue, 2),
            'total_products' => $totalProducts,
        ];
    }

    /**
     * สถิติ Wallet
     */
    protected function getWalletStats(array $dates): array
    {
        $totalBalance = 0;
        $totalDeposits = 0;
        $totalWithdrawals = 0;
        $totalTransactions = 0;

        if (class_exists(\App\Models\Wallet::class)) {
            $totalBalance = Wallet::sum('balance') ?? 0;
        }

        if (class_exists(\App\Models\WalletTransaction::class)) {
            $totalTransactions = WalletTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
            $totalDeposits = WalletTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('type', 'deposit')
                ->sum('amount') ?? 0;
            $totalWithdrawals = WalletTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('type', 'withdrawal')
                ->sum('amount') ?? 0;
        }

        return [
            'total_balance' => $totalBalance,
            'total_deposits' => $totalDeposits,
            'total_withdrawals' => $totalWithdrawals,
            'total_transactions' => $totalTransactions,
            'net_flow' => $totalDeposits - $totalWithdrawals,
        ];
    }

    /**
     * สถิติ AI Bot
     */
    protected function getAiBotStats(array $dates): array
    {
        $totalBots = 0;
        $activeBots = 0;
        $totalMessages = 0;

        if (class_exists(\App\Models\AiBotProfile::class)) {
            $totalBots = AiBotProfile::count();
            $activeBots = AiBotProfile::where('status', 'active')->count();
        }

        return [
            'total_bots' => $totalBots,
            'active_bots' => $activeBots,
            'total_messages' => $totalMessages,
        ];
    }

    /**
     * สถิติโรงแรม
     */
    protected function getHotelStats(array $dates): array
    {
        $totalBookings = 0;
        $completedBookings = 0;
        $totalRevenue = 0;

        if (class_exists(\App\Models\HotelBooking::class)) {
            $totalBookings = HotelBooking::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
            $completedBookings = HotelBooking::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->count();
            $totalRevenue = HotelBooking::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
        }

        return [
            'total_bookings' => $totalBookings,
            'completed_bookings' => $completedBookings,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * สถิติ POS
     */
    protected function getPosStats(array $dates): array
    {
        $totalTransactions = 0;
        $totalRevenue = 0;
        $averageTransaction = 0;

        if (class_exists(\App\Models\PosTransaction::class)) {
            $totalTransactions = PosTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
            $totalRevenue = PosTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->where('status', 'completed')
                ->sum('total_amount') ?? 0;
            $averageTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;
        }

        return [
            'total_transactions' => $totalTransactions,
            'total_revenue' => $totalRevenue,
            'average_transaction' => round($averageTransaction, 2),
        ];
    }

    /**
     * สถิติ Crypto
     */
    protected function getCryptoStats(array $dates): array
    {
        $totalTransactions = 0;
        $totalVolume = 0;

        if (class_exists(\App\Models\CryptoTransaction::class)) {
            $totalTransactions = CryptoTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
            $totalVolume = CryptoTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
                ->sum('amount') ?? 0;
        }

        return [
            'total_transactions' => $totalTransactions,
            'total_volume' => $totalVolume,
        ];
    }

    /**
     * ข้อมูลแนวโน้มผู้ใช้
     */
    protected function getUserTrends(array $dates, string $format): array
    {
        return User::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * ข้อมูลแนวโน้มรายได้
     */
    protected function getRevenueTrends(array $dates, string $format): array
    {
        if (!class_exists(\App\Models\Order::class)) {
            return [];
        }

        return Order::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('status', 'completed')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as date, SUM(total_amount) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * ข้อมูลแนวโน้มคำสั่งซื้อ
     */
    protected function getOrderTrends(array $dates, string $format): array
    {
        if (!class_exists(\App\Models\Order::class)) {
            return [];
        }

        return Order::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * ข้อมูลแนวโน้มคอมมิชชั่น
     */
    protected function getCommissionTrends(array $dates, string $format): array
    {
        if (!class_exists(\App\Models\Commission::class)) {
            return [];
        }

        return Commission::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as date, SUM(amount) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * การกระจายตัวของ Rank
     */
    protected function getRankDistribution(): array
    {
        if (!class_exists(\App\Models\Rank::class)) {
            return [];
        }

        return DB::table('users')
            ->join('ranks', 'users.rank_id', '=', 'ranks.id')
            ->select('ranks.name', DB::raw('COUNT(*) as count'))
            ->groupBy('ranks.id', 'ranks.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Top Earners (ผู้มีรายได้สูงสุด)
     */
    protected function getTopEarners(array $dates, int $limit = 10): array
    {
        if (!class_exists(\App\Models\Commission::class)) {
            return [];
        }

        return Commission::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('status', 'paid')
            ->select('user_id', DB::raw('SUM(amount) as total_earned'))
            ->groupBy('user_id')
            ->orderByDesc('total_earned')
            ->limit($limit)
            ->with('user:id,name,email')
            ->get()
            ->toArray();
    }

    /**
     * Top Recruiters (ผู้แนะนำสมาชิกสูงสุด)
     */
    protected function getTopRecruiters(array $dates, int $limit = 10): array
    {
        return User::withCount(['referrals' => function ($query) use ($dates) {
            $query->whereBetween('created_at', [$dates['start'], $dates['end']]);
        }])
            ->orderByDesc('referrals_count')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'referrals_count'])
            ->toArray();
    }

    /**
     * Commission แยกตามประเภท
     */
    protected function getCommissionByType(array $dates): array
    {
        if (!class_exists(\App\Models\Commission::class)) {
            return [];
        }

        return Commission::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select('type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->toArray();
    }

    /**
     * วิเคราะห์ระดับ MLM
     */
    protected function getLevelAnalysis(): array
    {
        return DB::table('affiliates')
            ->select('level', DB::raw('COUNT(*) as count'))
            ->groupBy('level')
            ->orderBy('level')
            ->get()
            ->toArray();
    }

    /**
     * อัตราการเติบโต MLM
     */
    protected function getMlmGrowthRate(array $dates): array
    {
        if (!class_exists(\App\Models\Affiliate::class)) {
            return ['growth_rate' => 0, 'previous_period' => 0, 'current_period' => 0];
        }

        $periodLength = $dates['start']->diffInDays($dates['end']);
        $previousStart = $dates['start']->copy()->subDays($periodLength);

        $previousCount = Affiliate::whereBetween('created_at', [$previousStart, $dates['start']])->count();
        $currentCount = Affiliate::whereBetween('created_at', [$dates['start'], $dates['end']])->count();

        $growthRate = $previousCount > 0 ? (($currentCount - $previousCount) / $previousCount) * 100 : 0;

        return [
            'growth_rate' => round($growthRate, 2),
            'previous_period' => $previousCount,
            'current_period' => $currentCount,
        ];
    }

    /**
     * สินค้าขายดี
     */
    protected function getTopProducts(array $dates, int $limit = 10): array
    {
        if (!class_exists(\App\Models\Order::class)) {
            return [];
        }

        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$dates['start'], $dates['end']])
            ->where('orders.status', 'completed')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * ยอดขายตามหมวดหมู่
     */
    protected function getSalesByCategory(array $dates): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->whereBetween('orders.created_at', [$dates['start'], $dates['end']])
            ->where('orders.status', 'completed')
            ->select(
                'product_categories.name',
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as total_revenue')
            )
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->toArray();
    }

    /**
     * สถานะคำสั่งซื้อ
     */
    protected function getOrderStatusDistribution(array $dates): array
    {
        if (!class_exists(\App\Models\Order::class)) {
            return [];
        }

        return Order::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    /**
     * มูลค่าคำสั่งซื้อเฉลี่ย
     */
    protected function getAverageOrderValue(array $dates): float
    {
        if (!class_exists(\App\Models\Order::class)) {
            return 0;
        }

        $result = Order::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('status', 'completed')
            ->avg('total_amount');

        return round($result ?? 0, 2);
    }

    /**
     * สถิติลูกค้า
     */
    protected function getCustomerStats(array $dates): array
    {
        if (!class_exists(\App\Models\Order::class)) {
            return [
                'new_customers' => 0,
                'returning_customers' => 0,
                'repeat_rate' => 0,
            ];
        }

        $newCustomers = Order::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->whereRaw('user_id NOT IN (SELECT DISTINCT user_id FROM orders WHERE created_at < ?)', [$dates['start']])
            ->distinct('user_id')
            ->count('user_id');

        $totalCustomers = Order::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->distinct('user_id')
            ->count('user_id');

        $returningCustomers = $totalCustomers - $newCustomers;
        $repeatRate = $totalCustomers > 0 ? ($returningCustomers / $totalCustomers) * 100 : 0;

        return [
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'repeat_rate' => round($repeatRate, 2),
        ];
    }

    /**
     * ประเภท Transaction
     */
    protected function getTransactionTypes(array $dates): array
    {
        if (!class_exists(\App\Models\WalletTransaction::class)) {
            return [];
        }

        return WalletTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select('type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->toArray();
    }

    /**
     * กระเป๋าเงินยอดสูงสุด
     */
    protected function getTopWallets(int $limit = 10): array
    {
        if (!class_exists(\App\Models\Wallet::class)) {
            return [];
        }

        return Wallet::with('user:id,name,email')
            ->orderByDesc('balance')
            ->limit($limit)
            ->get(['id', 'user_id', 'balance'])
            ->toArray();
    }

    /**
     * สถิติการถอน
     */
    protected function getWithdrawalStats(array $dates): array
    {
        if (!class_exists(\App\Models\WalletTransaction::class)) {
            return [
                'total_amount' => 0,
                'count' => 0,
                'average' => 0,
            ];
        }

        $stats = WalletTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('type', 'withdrawal')
            ->selectRaw('SUM(amount) as total, COUNT(*) as count, AVG(amount) as average')
            ->first();

        return [
            'total_amount' => $stats->total ?? 0,
            'count' => $stats->count ?? 0,
            'average' => round($stats->average ?? 0, 2),
        ];
    }

    /**
     * สถิติการฝาก
     */
    protected function getDepositStats(array $dates): array
    {
        if (!class_exists(\App\Models\WalletTransaction::class)) {
            return [
                'total_amount' => 0,
                'count' => 0,
                'average' => 0,
            ];
        }

        $stats = WalletTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('type', 'deposit')
            ->selectRaw('SUM(amount) as total, COUNT(*) as count, AVG(amount) as average')
            ->first();

        return [
            'total_amount' => $stats->total ?? 0,
            'count' => $stats->count ?? 0,
            'average' => round($stats->average ?? 0, 2),
        ];
    }

    /**
     * ข้อมูล Flow รายวัน
     */
    protected function getDailyFlow(array $dates): array
    {
        if (!class_exists(\App\Models\WalletTransaction::class)) {
            return [];
        }

        return WalletTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->selectRaw("
                DATE(created_at) as date,
                SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as deposits,
                SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END) as withdrawals
            ")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Bot ยอดนิยม
     */
    protected function getPopularBots(array $dates, int $limit = 10): array
    {
        if (!class_exists(\App\Models\AiBotProfile::class)) {
            return [];
        }

        return AiBotProfile::withCount('messages')
            ->orderByDesc('messages_count')
            ->limit($limit)
            ->get(['id', 'name', 'messages_count'])
            ->toArray();
    }

    /**
     * การใช้งาน AI ตาม Feature
     */
    protected function getAiUsageByFeature(array $dates): array
    {
        return DB::table('ai_core_usage_logs')
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select('feature_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('feature_id')
            ->get()
            ->toArray();
    }

    /**
     * รายได้จาก AI
     */
    protected function getAiRevenue(array $dates): array
    {
        return [
            'total' => 0,
            'subscriptions' => 0,
            'pay_per_use' => 0,
        ];
    }

    /**
     * อัตราการเข้าพัก
     */
    protected function getOccupancyRate(array $dates): float
    {
        return 0;
    }

    /**
     * รายได้ตามโรงแรม
     */
    protected function getRevenueByHotel(array $dates, int $limit = 10): array
    {
        if (!class_exists(\App\Models\HotelBooking::class)) {
            return [];
        }

        return HotelBooking::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->where('status', 'completed')
            ->select('hotel_id', DB::raw('SUM(total_amount) as total_revenue'))
            ->groupBy('hotel_id')
            ->with('hotel:id,name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * สถานะการจอง
     */
    protected function getBookingStatusDistribution(array $dates): array
    {
        if (!class_exists(\App\Models\HotelBooking::class)) {
            return [];
        }

        return HotelBooking::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    /**
     * ยอดขายตามอุปกรณ์ POS
     */
    protected function getSalesByDevice(array $dates): array
    {
        if (!class_exists(\App\Models\PosTransaction::class)) {
            return [];
        }

        return PosTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select('device_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('device_id')
            ->get()
            ->toArray();
    }

    /**
     * ชั่วโมงที่ขายดี
     */
    protected function getPeakHours(array $dates): array
    {
        if (!class_exists(\App\Models\PosTransaction::class)) {
            return [];
        }

        return PosTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as transactions, SUM(total_amount) as revenue')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    /**
     * วิธีชำระเงิน
     */
    protected function getPaymentMethodStats(array $dates): array
    {
        if (!class_exists(\App\Models\PosTransaction::class)) {
            return [];
        }

        return PosTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }

    /**
     * ประเภท Transaction Crypto
     */
    protected function getCryptoTransactionTypes(array $dates): array
    {
        if (!class_exists(\App\Models\CryptoTransaction::class)) {
            return [];
        }

        return CryptoTransaction::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get()
            ->toArray();
    }

    /**
     * การกระจายกระเป๋า Crypto
     */
    protected function getCryptoWalletDistribution(): array
    {
        return [];
    }

    // =================================================================
    // HRM (บุคลากร) METHODS
    // =================================================================

    /**
     * สถิติ HRM ภาพรวม
     */
    protected function getHrmStats(array $dates): array
    {
        $totalEmployees = 0;
        $newEmployees = 0;
        $leftEmployees = 0;

        if (class_exists(\App\Models\Employee::class)) {
            $totalEmployees = \App\Models\Employee::where('status', 'active')->count();
            $newEmployees = \App\Models\Employee::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
            $leftEmployees = \App\Models\Employee::whereBetween('terminated_at', [$dates['start'], $dates['end']])->count();
        }

        return [
            'total_employees' => $totalEmployees,
            'new_employees' => $newEmployees,
            'left_employees' => $leftEmployees,
            'net_change' => $newEmployees - $leftEmployees,
        ];
    }

    /**
     * สถิติตามแผนก
     */
    protected function getDepartmentStats(array $dates): array
    {
        if (!class_exists(\App\Models\Employee::class)) {
            return [];
        }

        return DB::table('employees')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->where('employees.status', 'active')
            ->select('departments.name', DB::raw('COUNT(*) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->toArray();
    }

    /**
     * สถิติการเข้างาน
     */
    protected function getAttendanceStats(array $dates): array
    {
        $present = 0;
        $absent = 0;
        $late = 0;
        $leave = 0;

        if (class_exists(\App\Models\Attendance::class)) {
            $present = \App\Models\Attendance::whereBetween('date', [$dates['start'], $dates['end']])
                ->where('status', 'present')->count();
            $absent = \App\Models\Attendance::whereBetween('date', [$dates['start'], $dates['end']])
                ->where('status', 'absent')->count();
            $late = \App\Models\Attendance::whereBetween('date', [$dates['start'], $dates['end']])
                ->where('is_late', true)->count();
            $leave = \App\Models\Attendance::whereBetween('date', [$dates['start'], $dates['end']])
                ->where('status', 'leave')->count();
        }

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'leave' => $leave,
            'attendance_rate' => ($present + $absent + $leave) > 0
                ? round(($present / ($present + $absent + $leave)) * 100, 2)
                : 0,
        ];
    }

    /**
     * สถิติเงินเดือน
     */
    protected function getPayrollStats(array $dates): array
    {
        $totalPayroll = 0;
        $employeeCount = 0;

        if (class_exists(\App\Models\Payroll::class)) {
            $stats = \App\Models\Payroll::whereBetween('pay_date', [$dates['start'], $dates['end']])
                ->selectRaw('SUM(net_salary) as total, COUNT(DISTINCT employee_id) as employees')
                ->first();

            $totalPayroll = $stats->total ?? 0;
            $employeeCount = $stats->employees ?? 0;
        }

        return [
            'total_payroll' => $totalPayroll,
            'employee_count' => $employeeCount,
            'average_salary' => $employeeCount > 0 ? round($totalPayroll / $employeeCount, 2) : 0,
        ];
    }

    // =================================================================
    // LEARNING (การเรียนรู้) METHODS
    // =================================================================

    /**
     * สถิติการเรียนรู้ภาพรวม
     */
    protected function getLearningStats(array $dates): array
    {
        $totalCourses = 0;
        $totalEnrollments = 0;
        $completions = 0;

        if (class_exists(\App\Models\Course::class)) {
            $totalCourses = \App\Models\Course::where('status', 'published')->count();
        }

        if (class_exists(\App\Models\Enrollment::class)) {
            $totalEnrollments = \App\Models\Enrollment::whereBetween('created_at', [$dates['start'], $dates['end']])->count();
            $completions = \App\Models\Enrollment::whereBetween('completed_at', [$dates['start'], $dates['end']])->count();
        }

        return [
            'total_courses' => $totalCourses,
            'total_enrollments' => $totalEnrollments,
            'completions' => $completions,
            'completion_rate' => $totalEnrollments > 0 ? round(($completions / $totalEnrollments) * 100, 2) : 0,
        ];
    }

    /**
     * สถิติหลักสูตร
     */
    protected function getCourseStats(array $dates): array
    {
        if (!class_exists(\App\Models\Course::class)) {
            return [];
        }

        return \App\Models\Course::where('status', 'published')
            ->withCount(['enrollments' => function ($query) use ($dates) {
                $query->whereBetween('created_at', [$dates['start'], $dates['end']]);
            }])
            ->orderByDesc('enrollments_count')
            ->limit(10)
            ->get(['id', 'title', 'enrollments_count'])
            ->toArray();
    }

    /**
     * สถิติการลงทะเบียน
     */
    protected function getEnrollmentStats(array $dates): array
    {
        if (!class_exists(\App\Models\Enrollment::class)) {
            return [];
        }

        return \App\Models\Enrollment::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * อัตราการสำเร็จหลักสูตร
     */
    protected function getCourseCompletionRate(array $dates): array
    {
        if (!class_exists(\App\Models\Course::class)) {
            return [];
        }

        return DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->whereBetween('enrollments.created_at', [$dates['start'], $dates['end']])
            ->select(
                'courses.title',
                DB::raw('COUNT(*) as total_enrollments'),
                DB::raw('SUM(CASE WHEN enrollments.completed_at IS NOT NULL THEN 1 ELSE 0 END) as completions')
            )
            ->groupBy('courses.id', 'courses.title')
            ->get()
            ->map(function ($item) {
                $item->completion_rate = $item->total_enrollments > 0
                    ? round(($item->completions / $item->total_enrollments) * 100, 2)
                    : 0;
                return $item;
            })
            ->toArray();
    }
}
