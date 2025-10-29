<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display user dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's affiliate if exists
        $affiliate = $user->affiliate;

        // Get user's commissions
        $commissions = $user->commissions()
            ->latest()
            ->limit(10)
            ->get();

        // Calculate statistics
        $totalEarnings = $user->commissions()
            ->where('status', 'approved')
            ->sum('amount');

        $pendingEarnings = $user->commissions()
            ->where('status', 'pending')
            ->sum('amount');

        $totalReferrals = $affiliate ? $affiliate->children()->count() : 0;

        return view('user.dashboard', compact(
            'user',
            'affiliate',
            'commissions',
            'totalEarnings',
            'pendingEarnings',
            'totalReferrals'
        ));
    }

    /**
     * Display user profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    /**
     * Display user commissions
     */
    public function commissions()
    {
        $user = Auth::user();
        $commissions = $user->commissions()->paginate(20);

        return view('user.commissions', compact('commissions'));
    }

    /**
     * Display user referrals
     */
    public function referrals()
    {
        $user = Auth::user();
        $affiliate = $user->affiliate;
        $referrals = $affiliate ? $affiliate->children : collect();

        return view('user.referrals', compact('referrals', 'affiliate'));
    }
}
