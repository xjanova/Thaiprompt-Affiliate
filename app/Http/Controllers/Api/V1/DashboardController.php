<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        $mlmMember = $user->mlmMembers()->first();

        $totalEarnings = $user->mlmCommissions()
            ->where('status', 'approved')
            ->sum('commission_amount');

        $pendingEarnings = $user->mlmCommissions()
            ->where('status', 'pending')
            ->sum('commission_amount');

        $totalReferrals = $mlmMember ? $mlmMember->total_direct_referrals : 0;

        $recentCommissions = $user->mlmCommissions()
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_earnings' => $totalEarnings,
                'pending_earnings' => $pendingEarnings,
                'total_referrals' => $totalReferrals,
                'recent_commissions' => $recentCommissions,
            ],
        ]);
    }

    /**
     * Get commissions
     */
    public function commissions(Request $request)
    {
        $user = $request->user();
        $commissions = $user->mlmCommissions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $commissions,
        ]);
    }

    /**
     * Get referrals
     */
    public function referrals(Request $request)
    {
        $user = $request->user();
        $mlmMember = $user->mlmMembers()->first();

        if (!$mlmMember) {
            return response()->json([
                'success' => true,
                'data' => [
                    'referrals' => [],
                    'referral_link' => null,
                ],
            ]);
        }

        $referrals = $mlmMember->directReferrals()->with('user')->get();
        $referralLink = url('/register?ref=' . $mlmMember->member_code);

        return response()->json([
            'success' => true,
            'data' => [
                'referrals' => $referrals,
                'referral_link' => $referralLink,
            ],
        ]);
    }

    /**
     * Get app settings
     */
    public function settings()
    {
        $logo = \App\Models\Setting::get('logo');
        $favicon = \App\Models\Setting::get('favicon');
        $primaryStart = \App\Models\Setting::get('theme_primary_start', '#3B82F6');
        $primaryEnd = \App\Models\Setting::get('theme_primary_end', '#1D4ED8');
        $secondaryStart = \App\Models\Setting::get('theme_secondary_start', '#10B981');
        $secondaryEnd = \App\Models\Setting::get('theme_secondary_end', '#059669');
        $accentStart = \App\Models\Setting::get('theme_accent_start', '#8B5CF6');
        $accentEnd = \App\Models\Setting::get('theme_accent_end', '#6D28D9');

        return response()->json([
            'success' => true,
            'data' => [
                'branding' => [
                    'logo' => $logo ? url($logo) : null,
                    'favicon' => $favicon ? url($favicon) : null,
                ],
                'theme' => [
                    'primary' => [
                        'start' => $primaryStart,
                        'end' => $primaryEnd,
                    ],
                    'secondary' => [
                        'start' => $secondaryStart,
                        'end' => $secondaryEnd,
                    ],
                    'accent' => [
                        'start' => $accentStart,
                        'end' => $accentEnd,
                    ],
                ],
            ],
        ]);
    }
}
