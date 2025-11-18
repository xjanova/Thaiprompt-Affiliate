<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MlmCommission;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    /**
     * แสดงหน้า Dashboard ของ User
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // ===============================================
        // 1. ข้อมูลพื้นฐาน
        // ===============================================

        // ดึงข้อมูล MLM member ของ user
        $mlmMember = $user->mlmMembers()->first();

        // ===============================================
        // 2. สถิติ Wallet
        // ===============================================

        $walletBalance = $user->wallet_balance ?? 0;

        // ===============================================
        // 3. สถิติ Commission (MLM)
        // ===============================================

        $pendingCommission = $user->mlmCommissions()
            ->where('status', 'pending')
            ->sum('commission_amount') ?? 0;

        $approvedCommission = $user->mlmCommissions()
            ->where('status', 'approved')
            ->sum('commission_amount') ?? 0;

        $paidCommission = $user->mlmCommissions()
            ->where('status', 'paid')
            ->sum('commission_amount') ?? 0;

        $totalEarnings = $approvedCommission + $paidCommission;

        // คอมมิชชั่นเดือนนี้
        $thisMonthCommission = $user->mlmCommissions()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount') ?? 0;

        // คอมมิชชั่นเดือนที่แล้ว
        $lastMonthCommission = $user->mlmCommissions()
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount') ?? 0;

        // คำนวณอัตราการเติบโต
        $commissionGrowth = 0;
        if ($lastMonthCommission > 0) {
            $commissionGrowth = (($thisMonthCommission - $lastMonthCommission) / $lastMonthCommission) * 100;
        } elseif ($thisMonthCommission > 0) {
            $commissionGrowth = 100;
        }

        // ===============================================
        // 4. สถิติ Referrals / Team
        // ===============================================

        $totalReferrals = 0;
        $activeReferrals = 0;

        if ($mlmMember) {
            $totalReferrals = $mlmMember->total_direct_referrals ?? 0;
            $activeReferrals = $mlmMember->directReferrals()
                ->where('is_qualified', true)
                ->count();
        }

        // Referrals เดือนนี้
        $thisMonthReferrals = 0;
        $lastMonthReferrals = 0;

        if ($mlmMember) {
            $thisMonthReferrals = $mlmMember->directReferrals()
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();

            $lastMonthReferrals = $mlmMember->directReferrals()
                ->whereYear('created_at', now()->subMonth()->year)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->count();
        }

        // อัตราการเติบโตของ referrals
        $referralsGrowth = 0;
        if ($lastMonthReferrals > 0) {
            $referralsGrowth = (($thisMonthReferrals - $lastMonthReferrals) / $lastMonthReferrals) * 100;
        } elseif ($thisMonthReferrals > 0) {
            $referralsGrowth = 100;
        }

        // ===============================================
        // 5. Rank System
        // ===============================================

        $currentRank = $user->currentRank;
        $nextRank = null;
        $rankProgress = 0;
        $pointsNeeded = 0;

        if ($currentRank) {
            // หา rank ถัดไป
            $nextRank = \App\Models\Rank::where('level', '>', $currentRank->level)
                ->where('is_active', true)
                ->orderBy('level', 'asc')
                ->first();

            if ($nextRank) {
                $currentPoints = $user->rank_points ?? 0;
                $currentRankPoints = $currentRank->min_points ?? 0;
                $nextRankPoints = $nextRank->min_points ?? 0;

                $totalPointsNeeded = $nextRankPoints - $currentRankPoints;
                $pointsProgress = max(0, $currentPoints - $currentRankPoints);

                $rankProgress = $totalPointsNeeded > 0
                    ? min(100, ($pointsProgress / $totalPointsNeeded) * 100)
                    : 0;

                $pointsNeeded = max(0, $nextRankPoints - $currentPoints);
            }
        } else {
            // ยังไม่มี rank - หา rank แรก
            $nextRank = \App\Models\Rank::where('is_active', true)
                ->orderBy('level', 'asc')
                ->first();

            if ($nextRank) {
                $currentPoints = $user->rank_points ?? 0;
                $nextRankPoints = $nextRank->min_points ?? 0;

                $rankProgress = $nextRankPoints > 0
                    ? min(100, ($currentPoints / $nextRankPoints) * 100)
                    : 0;

                $pointsNeeded = max(0, $nextRankPoints - $currentPoints);
            }
        }

        // ===============================================
        // 6. กิจกรรมล่าสุด (Recent Activity)
        // ===============================================

        $recentActivities = $user->mlmCommissions()
            ->with('mlmMember.user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($commission) {
                return [
                    'type' => 'commission',
                    'status' => $commission->status,
                    'amount' => $commission->commission_amount,
                    'commission_type' => $commission->type ?? 'general',
                    'created_at' => $commission->created_at,
                ];
            });

        // ===============================================
        // 7. ข้อมูลกราฟ - รายได้ 12 เดือนย้อนหลัง
        // ===============================================

        $chartLabels = [];
        $chartValues = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->locale('th')->translatedFormat('M Y');

            $monthTotal = $user->mlmCommissions()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->whereIn('status', ['approved', 'paid'])
                ->sum('commission_amount') ?? 0;

            $chartLabels[] = $monthName;
            $chartValues[] = (float) $monthTotal;
        }

        // ===============================================
        // 8. สถิติสำหรับการ์ด (Stats Cards)
        // ===============================================

        $stats = [
            'wallet_balance' => (float) $walletBalance,
            'wallet_change' => round($commissionGrowth, 1),

            'pending_commission' => (float) $pendingCommission,
            'commission_change' => round($commissionGrowth, 1),

            'total_referrals' => (int) $totalReferrals,
            'referrals_change' => round($referralsGrowth, 1),

            'rank_points' => (int) ($user->rank_points ?? 0),
            'points_change' => 0, // TODO: คำนวณจากประวัติ

            'total_earnings' => (float) $totalEarnings,
            'active_referrals' => (int) $activeReferrals,
        ];

        // ===============================================
        // 9. ข้อมูลกราฟสำหรับ frontend
        // ===============================================

        $chartData = [
            'labels' => $chartLabels,
            'values' => $chartValues,
        ];

        // ===============================================
        // 10. Commission ล่าสุดสำหรับแสดงในตาราง
        // ===============================================

        $recentCommissions = $user->mlmCommissions()
            ->with('mlmMember.user')
            ->latest()
            ->limit(5)
            ->get();

        // ===============================================
        // 11. ข้อมูล KYC สำหรับแจ้งเตือน
        // ===============================================

        $kycStatus = $user->kyc_status ?? 'not_submitted';
        $showKycAlert = in_array($kycStatus, ['not_submitted', 'rejected']);

        // ===============================================
        // Return View พร้อมข้อมูลทั้งหมด
        // ===============================================

        return view('user.dashboard', compact(
            // User & MLM Member
            'user',
            'mlmMember',

            // Wallet
            'walletBalance',

            // Commission
            'pendingCommission',
            'approvedCommission',
            'paidCommission',
            'totalEarnings',
            'thisMonthCommission',
            'commissionGrowth',

            // Referrals
            'totalReferrals',
            'activeReferrals',
            'referralsGrowth',

            // Rank
            'currentRank',
            'nextRank',
            'rankProgress',
            'pointsNeeded',

            // Activities
            'recentActivities',
            'recentCommissions',

            // Charts & Stats
            'stats',
            'chartData',

            // KYC
            'kycStatus',
            'showKycAlert'
        ));
    }

    /**
     * แสดงหน้าโปรไฟล์ของ User
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    /**
     * อัปเดตโปรไฟล์ของ User
     *
     * @param Request $request
     * @param ImageUploadService $imageUploadService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request, ImageUploadService $imageUploadService)
    {
        $user = Auth::user();

        $rules = [
            // ข้อมูลส่วนตัว
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],

            // ที่อยู่หลัก (Billing Address)
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],

            // ที่อยู่จัดส่ง (Shipping Address) - NEW
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_state' => ['nullable', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country' => ['nullable', 'string', 'max:2'],
            'shipping_phone' => ['nullable', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],

            // รูปโปรไฟล์ (จะแปลงเป็น WebP อัตโนมัติ)
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'], // 5MB max

            // รหัสผ่าน (ถ้ามีการเปลี่ยน)
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ];

        $validated = $request->validate($rules);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                $imageUploadService->deleteImage($user->profile_picture);
            }

            // Upload new profile picture with WebP conversion
            // Use 'avatars' directory, max 800x800 for avatar, quality 90
            $validated['profile_picture'] = $imageUploadService->uploadImage(
                $request->file('profile_picture'),
                'avatars',
                800,
                800,
                90
            );
        }

        // Don't update phone if it hasn't changed, or if it changed, require verification
        if (isset($validated['phone']) && $validated['phone'] !== $user->phone) {
            // Phone changed - reset verification status
            $validated['phone_verified'] = false;
            $validated['phone_verified_at'] = null;
        }

        // Handle password change
        if ($request->filled('current_password') && $request->filled('new_password')) {
            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withInput()
                    ->with('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
            }

            // Check if new password is same as current password
            if (Hash::check($request->new_password, $user->password)) {
                return back()
                    ->withInput()
                    ->with('error', 'รหัสผ่านใหม่ต้องไม่เหมือนกับรหัสผ่านเดิม');
            }

            // Update password
            $validated['password'] = Hash::make($request->new_password);
        }

        // Remove password fields from validated array if not changing password
        unset($validated['current_password'], $validated['new_password']);

        $user->update($validated);

        return redirect()->route('user.profile')
            ->with('success', 'อัปเดตโปรไฟล์เรียบร้อยแล้ว');
    }

    /**
     * แสดงรายการ Commissions ของ User
     *
     * @return \Illuminate\View\View
     */
    public function commissions()
    {
        $user = Auth::user();
        $commissions = $user->mlmCommissions()->paginate(20);

        return view('user.commissions', compact('commissions'));
    }

    /**
     * อัปเดตรหัสผ่านของ User
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ], [
            'current_password.required' => 'กรุณากรอกรหัสผ่านปัจจุบัน',
            'new_password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'new_password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร',
            'new_password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
        }

        // Check if new password is same as current password
        if (Hash::check($request->new_password, $user->password)) {
            return back()->with('error', 'รหัสผ่านใหม่ต้องไม่เหมือนกับรหัสผ่านเดิม');
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
    }
}
