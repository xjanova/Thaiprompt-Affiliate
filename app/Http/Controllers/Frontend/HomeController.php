<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the homepage
     */
    public function index()
    {
        // Check if system needs setup
        if (!User::where('is_super_admin', true)->exists()) {
            return redirect()->route('setup.index');
        }

        // Premium Statistics for Landing Page
        $totalUsers = User::count();
        $totalAffiliates = \App\Models\Affiliate::count();

        $totalCommissions = \App\Models\Commission::whereIn('status', ['approved', 'paid'])->count();

        $totalPaid = \App\Models\Commission::where('status', 'paid')->sum('amount');
        $totalApproved = \App\Models\Commission::where('status', 'approved')->sum('amount');
        $totalEarnings = $totalPaid + $totalApproved;

        // Average earnings per affiliate
        $avgEarnings = $totalAffiliates > 0
            ? \App\Models\Commission::whereIn('status', ['approved', 'paid'])->sum('amount') / $totalAffiliates
            : 0;

        // Success rate (affiliates who earned at least 1 commission)
        $affiliatesWithEarnings = \App\Models\Affiliate::whereHas('commissions', function($query) {
            $query->whereIn('status', ['approved', 'paid']);
        })->count();

        $successRate = $totalAffiliates > 0
            ? ($affiliatesWithEarnings / $totalAffiliates) * 100
            : 0;

        // Get this month's stats
        $thisMonthEarnings = \App\Models\Commission::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $thisMonthCommissions = \App\Models\Commission::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // Top performing affiliates (public leaderboard)
        $topAffiliates = \App\Models\Affiliate::with('user')
            ->withCount('children as total_referrals')
            ->get()
            ->map(function($affiliate) {
                $affiliate->total_earnings = \App\Models\Commission::where('affiliate_id', $affiliate->id)
                    ->whereIn('status', ['approved', 'paid'])
                    ->sum('amount');
                return $affiliate;
            })
            ->sortByDesc('total_earnings')
            ->take(5);

        // Recent success stories (recent big commissions)
        $recentSuccesses = \App\Models\Commission::with('affiliate.user')
            ->whereIn('status', ['approved', 'paid'])
            ->where('amount', '>', 1000) // Only show significant commissions
            ->latest()
            ->take(10)
            ->get();

        // Monthly growth
        $lastMonthUsers = User::whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        $thisMonthUsers = User::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $userGrowth = $lastMonthUsers > 0
            ? (($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100
            : 0;

        // Total referral network size
        $totalReferrals = \App\Models\Affiliate::sum('total_referrals') ?? 0;

        $stats = [
            'total_users' => $totalUsers,
            'total_affiliates' => $totalAffiliates,
            'total_commissions' => $totalCommissions,
            'total_earnings' => $totalEarnings,
            'total_paid' => $totalPaid,
            'avg_earnings' => $avgEarnings,
            'success_rate' => $successRate,
            'this_month_earnings' => $thisMonthEarnings,
            'this_month_commissions' => $thisMonthCommissions,
            'user_growth' => $userGrowth,
            'total_referrals' => $totalReferrals,
        ];

        // Get premium page sections configuration
        $premiumSections = [
            'hero' => Setting::get('premium_page_hero_enabled', true),
            'statistics' => Setting::get('premium_page_statistics_enabled', true),
            'features' => Setting::get('premium_page_features_enabled', true),
            'leaderboard' => Setting::get('premium_page_leaderboard_enabled', true),
            'how_it_works' => Setting::get('premium_page_how_it_works_enabled', true),
            'faq' => Setting::get('premium_page_faq_enabled', true),
            'cta' => Setting::get('premium_page_cta_enabled', true),
        ];

        return view('frontend.home', compact(
            'stats',
            'premiumSections',
            'topAffiliates',
            'recentSuccesses'
        ));
    }

    /**
     * Show the about page
     */
    public function about()
    {
        return view('frontend.about');
    }

    /**
     * Show the contact page
     */
    public function contact()
    {
        return view('frontend.contact');
    }
}
