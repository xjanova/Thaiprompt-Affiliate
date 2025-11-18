<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MlmMember;
use App\Models\MlmCommission;
use App\Models\CryptoExchangeRate;
use App\Models\CryptoWithdrawalRequest;
use App\Models\CryptoTransaction;
use App\Models\TradingMarketData;
use App\Models\KycVerification;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_affiliates' => MlmMember::count(), // เปลี่ยนจาก Affiliate เป็น MLM Member
            'total_commissions' => MlmCommission::sum('commission_amount'), // เปลี่ยนจาก Commission เป็น MlmCommission
            'pending_commissions' => MlmCommission::where('status', 'pending')->count(),
            'approved_commissions' => MlmCommission::where('status', 'approved')->count(),
            'active_affiliates' => MlmMember::where('is_qualified', true)->count(), // MLM Member ที่มีคุณสมบัติ
            'paid_commissions' => MlmCommission::where('status', 'paid')->sum('commission_amount'),
            'rejected_commissions' => MlmCommission::where('status', 'rejected')->count(),
        ];

        // Get monthly revenue data for chart (last 12 months)
        $monthlyRevenue = MlmCommission::where('status', 'paid')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(commission_amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->reverse();

        // Commission types breakdown
        $commissionTypes = MlmCommission::selectRaw('type, SUM(commission_amount) as total')
            ->groupBy('type')
            ->get();

        // Commission status breakdown for pie chart
        $commissionStatus = [
            'pending' => MlmCommission::where('status', 'pending')->count(),
            'approved' => MlmCommission::where('status', 'approved')->count(),
            'paid' => MlmCommission::where('status', 'paid')->count(),
            'rejected' => MlmCommission::where('status', 'rejected')->count(),
        ];

        // Daily commissions for the last 30 days
        $dailyCommissions = MlmCommission::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(commission_amount) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Get recent commissions
        $recentCommissions = MlmCommission::with(['mlmMember.user'])
            ->latest()
            ->limit(10)
            ->get();

        // Get top MLM members (เปลี่ยนจาก affiliates)
        $topAffiliates = MlmMember::with('user')
            ->orderBy('total_pv', 'desc') // เรียงตาม PV แทน earnings
            ->limit(5)
            ->get();

        // Growth statistics
        $thisMonthUsers = User::whereMonth('created_at', now()->month)->count();
        $lastMonthUsers = User::whereMonth('created_at', now()->subMonth()->month)->count();
        $userGrowth = $lastMonthUsers > 0 ? (($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100 : 0;

        $thisMonthRevenue = MlmCommission::where('status', 'paid')
            ->whereMonth('created_at', now()->month)->sum('commission_amount');
        $lastMonthRevenue = MlmCommission::where('status', 'paid')
            ->whereMonth('created_at', now()->subMonth()->month)->sum('commission_amount');
        $revenueGrowth = $lastMonthRevenue > 0 ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        // Crypto data
        $cryptoRates = [];
        $cryptoSymbols = ['BTC', 'ETH', 'USDT', 'BNB'];
        foreach ($cryptoSymbols as $symbol) {
            $currency = \App\Models\CryptoCurrency::where('symbol', $symbol)->first();
            if ($currency) {
                $rate = CryptoExchangeRate::where('crypto_currency_id', $currency->id)
                    ->latest()
                    ->first();
                if ($rate) {
                    $cryptoRates[$symbol] = [
                        'price' => $rate->rate_thb,
                        'change_24h' => $rate->price_change_24h ?? 0,
                        'volume_24h' => $rate->volume_24h ?? 0,
                    ];
                }
            }
        }

        // Crypto withdrawal stats
        $cryptoWithdrawals = [
            'pending' => CryptoWithdrawalRequest::pending()->count(),
            'requires_approval' => CryptoWithdrawalRequest::requiresApproval()->count(),
            'total_pending_amount' => CryptoWithdrawalRequest::pending()->sum('amount'),
        ];

        // Crypto transactions (last 7 days)
        $cryptoTransactionsCount = CryptoTransaction::where('created_at', '>=', now()->subDays(7))->count();

        // KYC stats
        $kycStats = [
            'pending' => KycVerification::pending()->count(),
            'verified' => KycVerification::approved()->count(),
            'rejected' => KycVerification::rejected()->count(),
        ];

        // Trading stats (if table exists)
        $tradingStats = [];
        try {
            if (DB::getSchemaBuilder()->hasTable('trading_market_data')) {
                $tradingStats = [
                    'active_pairs' => TradingMarketData::distinct('symbol')->count('symbol'),
                    'total_volume_24h' => TradingMarketData::where('created_at', '>=', now()->subDay())
                        ->sum('volume'),
                ];
            }
        } catch (\Exception $e) {
            // Table doesn't exist or other error, skip
        }

        // Ticket stats
        $ticketStats = [
            'open' => Ticket::open()->count(),
            'new_today' => Ticket::whereDate('created_at', today())->count(),
            'high_priority' => Ticket::open()->whereIn('priority', ['high', 'critical'])->count(),
            'unassigned' => Ticket::open()->whereNull('assigned_to')->count(),
        ];

        // Recent tickets
        $recentTickets = Ticket::with(['user', 'category'])
            ->latest()
            ->limit(5)
            ->get();

        // ใช้ dashboard-v3 view สำหรับ V3 theme
        return view('admin.dashboard-v3', compact(
            'stats',
            'monthlyRevenue',
            'commissionTypes',
            'commissionStatus',
            'dailyCommissions',
            'recentCommissions',
            'topAffiliates',
            'userGrowth',
            'revenueGrowth',
            'cryptoRates',
            'cryptoWithdrawals',
            'cryptoTransactionsCount',
            'kycStats',
            'tradingStats',
            'ticketStats',
            'recentTickets'
        ));
    }
}
