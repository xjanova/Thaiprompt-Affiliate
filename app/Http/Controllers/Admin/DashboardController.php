<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Affiliate;
use App\Models\Commission;
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

        return view('admin.dashboard', compact(
            'stats',
            'monthlyRevenue',
            'commissionTypes',
            'commissionStatus',
            'dailyCommissions',
            'recentCommissions',
            'topAffiliates',
            'userGrowth',
            'revenueGrowth'
        ));
    }
}
