<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Affiliate;
use App\Models\Commission;
use App\Models\CryptoExchangeRate;
use App\Models\CryptoWithdrawalRequest;
use App\Models\CryptoTransaction;
use App\Models\TradingMarketData;
use App\Models\KycVerification;
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
            'total_affiliates' => Affiliate::count(),
            'total_commissions' => Commission::sum('amount'),
            'pending_commissions' => Commission::where('status', 'pending')->count(),
            'approved_commissions' => Commission::where('status', 'approved')->count(),
            'active_affiliates' => Affiliate::where('status', 'active')->count(),
            'paid_commissions' => Commission::where('status', 'paid')->sum('amount'),
            'rejected_commissions' => Commission::where('status', 'rejected')->count(),
        ];

        // Get monthly revenue data for chart (last 12 months)
        $monthlyRevenue = Commission::where('status', 'paid')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->reverse();

        // Commission types breakdown
        $commissionTypes = Commission::selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->get();

        // Commission status breakdown for pie chart
        $commissionStatus = [
            'pending' => Commission::where('status', 'pending')->count(),
            'approved' => Commission::where('status', 'approved')->count(),
            'paid' => Commission::where('status', 'paid')->count(),
            'rejected' => Commission::where('status', 'rejected')->count(),
        ];

        // Daily commissions for the last 30 days
        $dailyCommissions = Commission::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Get recent commissions
        $recentCommissions = Commission::with(['affiliate.user', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        // Get top affiliates
        $topAffiliates = Affiliate::with('user')
            ->orderBy('total_earnings', 'desc')
            ->limit(5)
            ->get();

        // Growth statistics
        $thisMonthUsers = User::whereMonth('created_at', now()->month)->count();
        $lastMonthUsers = User::whereMonth('created_at', now()->subMonth()->month)->count();
        $userGrowth = $lastMonthUsers > 0 ? (($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100 : 0;

        $thisMonthRevenue = Commission::where('status', 'paid')
            ->whereMonth('created_at', now()->month)->sum('amount');
        $lastMonthRevenue = Commission::where('status', 'paid')
            ->whereMonth('created_at', now()->subMonth()->month)->sum('amount');
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
            'pending' => KycVerification::where('verification_status', 'pending')->count(),
            'verified' => KycVerification::where('verification_status', 'verified')->count(),
            'rejected' => KycVerification::where('verification_status', 'rejected')->count(),
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

        return view('admin.dashboard', compact(
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
            'tradingStats'
        ));
    }
}
