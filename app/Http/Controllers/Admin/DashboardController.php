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
        ];

        // Get monthly revenue data for chart
        $monthlyRevenue = Commission::where('status', 'paid')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        // Get recent commissions
        $recentCommissions = Commission::with(['affiliate.user', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        // Get top affiliates
        $topAffiliates = Affiliate::with('user')
            ->orderBy('total_earnings', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'monthlyRevenue',
            'recentCommissions',
            'topAffiliates'
        ));
    }
}
