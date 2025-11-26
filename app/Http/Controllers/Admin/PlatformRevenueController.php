<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformWallet;
use App\Models\PlatformTransaction;
use App\Models\EarningsLedger;
use App\Models\PayoutRequest;
use App\Models\WalletDebt;
use App\Models\PayoutSetting;
use App\Services\PlatformRevenueService;
use App\Services\PayoutService;
use App\Services\DebtCollectionService;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * PlatformRevenueController
 *
 * แดชบอร์ดรายได้ Platform - ภาพรวมและการวิเคราะห์
 */
class PlatformRevenueController extends Controller
{
    protected PlatformRevenueService $revenueService;
    protected PayoutService $payoutService;
    protected DebtCollectionService $debtService;

    public function __construct()
    {
        $this->revenueService = new PlatformRevenueService();
        $this->payoutService = new PayoutService();
        $this->debtService = new DebtCollectionService();
    }

    /**
     * แดชบอร์ดหลัก - ภาพรวมรายได้ Platform
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงข้อมูล Wallets
        $wallets = PlatformWallet::all();
        $totalBalance = $wallets->sum('balance');

        // สถิติรายได้วันนี้
        $todayStats = $this->revenueService->getDailyStats(now());

        // สถิติรายได้เดือนนี้
        $monthlyStats = $this->revenueService->getMonthlyStats(now()->year, now()->month);

        // สถิติ Payout
        $payoutStats = $this->payoutService->getPayoutStats([
            'date_from' => now()->startOfMonth(),
            'date_to' => now(),
        ]);

        // สถิติหนี้
        $debtStats = $this->debtService->getDebtStats();

        // รายการล่าสุด
        $recentTransactions = PlatformTransaction::with(['wallet'])
            ->latest()
            ->limit(10)
            ->get();

        $pendingPayouts = PayoutRequest::with(['user'])
            ->where('status', PayoutRequest::STATUS_PENDING)
            ->latest()
            ->limit(5)
            ->get();

        // กราฟรายได้ 30 วัน
        $revenueChart = $this->getRevenueChartData(30);

        return view('admin.platform-revenue.index', [
            'wallets' => $wallets,
            'totalBalance' => $totalBalance,
            'todayStats' => $todayStats,
            'monthlyStats' => $monthlyStats,
            'payoutStats' => $payoutStats,
            'debtStats' => $debtStats,
            'recentTransactions' => $recentTransactions,
            'pendingPayouts' => $pendingPayouts,
            'revenueChart' => $revenueChart,
            'pageTitle' => 'Platform Revenue Dashboard',
        ]);
    }

    /**
     * รายละเอียด Wallet
     *
     * @param PlatformWallet $wallet
     * @return \Illuminate\View\View
     */
    public function showWallet(PlatformWallet $wallet)
    {
        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20);

        $stats = [
            'balance' => $wallet->balance,
            'total_income' => $wallet->total_income,
            'total_expense' => $wallet->total_expense,
            'total_transferred' => $wallet->total_transferred,
            'today_income' => $wallet->transactions()
                ->where('type', 'income')
                ->whereDate('created_at', now())
                ->sum('amount'),
            'today_expense' => $wallet->transactions()
                ->where('type', 'expense')
                ->whereDate('created_at', now())
                ->sum('amount'),
        ];

        return view('admin.platform-revenue.wallet-detail', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'stats' => $stats,
            'pageTitle' => "Wallet: {$wallet->name}",
        ]);
    }

    /**
     * รายการ Transactions ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function transactions(Request $request)
    {
        $query = PlatformTransaction::with(['wallet']);

        // Filters
        if ($request->wallet_id) {
            $query->where('wallet_id', $request->wallet_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(30);

        $wallets = PlatformWallet::all();

        return view('admin.platform-revenue.transactions', [
            'transactions' => $transactions,
            'wallets' => $wallets,
            'filters' => $request->only(['wallet_id', 'type', 'date_from', 'date_to']),
            'pageTitle' => 'Platform Transactions',
        ]);
    }

    /**
     * รายงานรายได้/รายจ่าย
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function reports(Request $request)
    {
        $period = $request->period ?? 'monthly';
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;

        $reports = [];

        if ($period === 'daily') {
            // รายวันของเดือนที่เลือก
            $daysInMonth = Carbon::create($year, $month)->daysInMonth;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::create($year, $month, $day);
                $reports[] = array_merge(
                    ['date' => $date->format('Y-m-d')],
                    $this->revenueService->getDailyStats($date)
                );
            }
        } elseif ($period === 'monthly') {
            // รายเดือนของปีที่เลือก
            for ($m = 1; $m <= 12; $m++) {
                $reports[] = array_merge(
                    ['month' => $m, 'month_name' => Carbon::create($year, $m)->format('F')],
                    $this->revenueService->getMonthlyStats($year, $m)
                );
            }
        }

        // สรุปรวม
        $summary = $this->revenueService->getYearlyStats($year);

        return view('admin.platform-revenue.reports', [
            'reports' => $reports,
            'summary' => $summary,
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'pageTitle' => 'Revenue Reports',
        ]);
    }

    /**
     * ข้อมูลกราฟรายได้
     *
     * @param int $days
     * @return array
     */
    protected function getRevenueChartData(int $days): array
    {
        $data = [
            'labels' => [],
            'fee' => [],
            'vat' => [],
            'mlm_pool' => [],
            'payout' => [],
        ];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data['labels'][] = $date->format('M d');

            $dailyStats = $this->revenueService->getDailyStats($date);
            $data['fee'][] = $dailyStats['by_wallet']['fee'] ?? 0;
            $data['vat'][] = $dailyStats['by_wallet']['vat'] ?? 0;
            $data['mlm_pool'][] = $dailyStats['by_wallet']['mlm_pool'] ?? 0;

            // Payouts
            $payout = PayoutRequest::where('status', PayoutRequest::STATUS_COMPLETED)
                ->whereDate('processed_at', $date)
                ->sum('net_amount');
            $data['payout'][] = $payout;
        }

        return $data;
    }

    /**
     * Export รายงาน
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportReport(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $transactions = PlatformTransaction::with(['wallet'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->orderBy('created_at')
            ->get();

        $filename = "platform-revenue-{$dateFrom}-to-{$dateTo}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, [
                'ID',
                'วันที่',
                'Wallet',
                'ประเภท',
                'จำนวนเงิน',
                'ยอดคงเหลือ',
                'รายละเอียด',
                'อ้างอิง',
            ]);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->id,
                    $tx->created_at->format('Y-m-d H:i:s'),
                    $tx->wallet->name ?? '-',
                    $tx->type,
                    $tx->amount,
                    $tx->balance_after,
                    $tx->description,
                    $tx->reference_type ? "{$tx->reference_type}#{$tx->reference_id}" : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * API: ดึงสถิติแบบ real-time
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiStats()
    {
        $wallets = PlatformWallet::all();
        $todayStats = $this->revenueService->getDailyStats(now());

        $pendingPayoutsCount = PayoutRequest::where('status', PayoutRequest::STATUS_PENDING)->count();
        $activeDebtsCount = WalletDebt::where('status', WalletDebt::STATUS_ACTIVE)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'wallets' => $wallets->map(fn($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'slug' => $w->slug,
                    'balance' => $w->balance,
                ]),
                'total_balance' => $wallets->sum('balance'),
                'today' => $todayStats,
                'pending_payouts' => $pendingPayoutsCount,
                'active_debts' => $activeDebtsCount,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
