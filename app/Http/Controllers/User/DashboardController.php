<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;
use App\Services\MembershipRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // ตรวจสอบ KYC status จาก KycVerification record (ข้อมูลที่ถูกต้องที่สุด)
        $latestKyc = \App\Models\KycVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        // กำหนด kycStatus จาก KycVerification หรือ User
        if ($latestKyc) {
            $kycStatus = $latestKyc->status; // draft, pending, approved, rejected

            // ⚠️ ถ้า status เป็น 'draft' ให้แสดงเป็น 'not_submitted' (ยังไม่ได้ส่งเอกสารครบ)
            // เพราะ 'draft' หมายถึงกำลัง upload รูปอยู่ ยังไม่ submit
            $displayStatus = ($kycStatus === 'draft') ? 'not_submitted' : $kycStatus;

            // Sync kyc_status ถ้าไม่ตรงกัน (แก้ไขข้อมูลเก่าที่ไม่ได้ถูก sync)
            // ⚠️ ไม่ sync 'draft' เพราะ users.kyc_status ไม่รองรับค่านี้
            $validStatuses = ['pending', 'approved', 'rejected', 'not_submitted'];
            if (in_array($displayStatus, $validStatuses) && $user->kyc_status !== $displayStatus) {
                $user->forceFill(['kyc_status' => $displayStatus])->save();
            }

            // ใช้ displayStatus สำหรับแสดงผล
            $kycStatus = $displayStatus;
        } else {
            $kycStatus = $user->kyc_status ?? 'not_submitted';
        }

        // แสดง KYC Alert เฉพาะเมื่อยังไม่ได้ส่งเอกสาร หรือถูกปฏิเสธ
        $showKycAlert = in_array($kycStatus, ['not_submitted', 'rejected']);

        // ===============================================
        // 12. ข้อมูลระบบรักษายอด (Retention Status)
        // ===============================================

        $retentionStatus = null;
        $retentionStatistics = null;

        try {
            $retentionService = app(MembershipRetentionService::class);
            $retentionStatus = $retentionService->getOrCreateStatus($user);
            $retentionStatistics = $retentionService->getUserStatistics($user);
        } catch (\Exception $e) {
            // ถ้าเกิด error ก็ปล่อยเป็น null
            \Log::warning('Failed to load retention data for user dashboard: '.$e->getMessage());
        }

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
            'showKycAlert',

            // Retention (ระบบรักษายอด)
            'retentionStatus',
            'retentionStatistics'
        ));
    }

    /**
     * แสดงหน้า Home แบบ App-Like Interface
     *
     * หน้าหลักหลังล็อกอินที่มี Hub Navigation Cards
     * เหมือนกับ mobile app
     *
     * @return \Illuminate\View\View
     */
    public function home()
    {
        $user = Auth::user();

        // ===============================================
        // 1. ข้อมูล Wallet
        // ===============================================
        $walletBalance = $user->wallet_balance ?? 0;

        // คำนวณ wallet growth (เทียบกับเดือนที่แล้ว)
        $thisMonthEarnings = $user->mlmCommissions()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount') ?? 0;

        $lastMonthEarnings = $user->mlmCommissions()
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount') ?? 0;

        $walletGrowth = 0;
        if ($lastMonthEarnings > 0) {
            $walletGrowth = (($thisMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100;
        } elseif ($thisMonthEarnings > 0) {
            $walletGrowth = 100;
        }

        // ===============================================
        // 2. ข้อมูล Commission & Referrals
        // ===============================================
        $pendingCommission = $user->mlmCommissions()
            ->where('status', 'pending')
            ->sum('commission_amount') ?? 0;

        $mlmMember = $user->mlmMembers()->first();
        $totalReferrals = $mlmMember ? ($mlmMember->total_direct_referrals ?? 0) : 0;

        // ===============================================
        // 3. Notifications
        // ===============================================
        $unreadNotifications = $user->unreadNotifications()->count();

        // ===============================================
        // 4. Hub Items (8 รายการ)
        // ===============================================
        $hubs = $this->getHubItems();

        // ===============================================
        // 5. กิจกรรมล่าสุด (5 รายการ)
        // ===============================================
        $recentActivities = $user->mlmCommissions()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($commission) {
                return [
                    'type' => 'commission',
                    'status' => $commission->status,
                    'amount' => $commission->commission_amount,
                    'description' => $commission->description ?? 'คอมมิชชั่น',
                    'created_at' => $commission->created_at,
                ];
            });

        return view('user.home', compact(
            'user',
            'walletBalance',
            'walletGrowth',
            'pendingCommission',
            'totalReferrals',
            'unreadNotifications',
            'hubs',
            'recentActivities'
        ));
    }

    /**
     * สร้างรายการ Hub Items ทั้ง 8 รายการ
     */
    private function getHubItems(): array
    {
        return [
            // 1. ช้อปปิ้ง & บริการ
            [
                'id' => 'shopping',
                'icon' => '🛒',
                'title' => 'ช้อปปิ้ง & บริการ',
                'subtitle' => 'สั่งอาหาร, จองโรงแรม, E-commerce',
                'route' => route('user.services.index'),
                'gradient_start' => '#F59E0B',
                'gradient_end' => '#EF4444',
                'badge' => null,
            ],
            // 2. กระเป๋าเงิน
            [
                'id' => 'wallet',
                'icon' => '💰',
                'title' => 'กระเป๋าเงิน',
                'subtitle' => 'เติมเงิน, ถอนเงิน, โอนเงิน',
                'route' => route('user.wallet.index'),
                'gradient_start' => '#10B981',
                'gradient_end' => '#059669',
                'badge' => null,
            ],
            // 3. ลงทุน & Trade
            [
                'id' => 'invest',
                'icon' => '📈',
                'title' => 'ลงทุน & Trade',
                'subtitle' => 'Crypto, TPIX Token, Staking',
                'route' => route('user.crypto-wallet.index'),
                'gradient_start' => '#3B82F6',
                'gradient_end' => '#1D4ED8',
                'badge' => null,
            ],
            // 4. MLM & Affiliate
            [
                'id' => 'mlm',
                'icon' => '🤝',
                'title' => 'MLM & Affiliate',
                'subtitle' => 'Commission, Referral, Team',
                'route' => route('user.mlm.dashboard'),
                'gradient_start' => '#8B5CF6',
                'gradient_end' => '#6D28D9',
                'badge' => 'HOT',
            ],
            // 5. เป็นไรเดอร์
            [
                'id' => 'rider',
                'icon' => '🚴',
                'title' => 'เป็นไรเดอร์',
                'subtitle' => 'รับงานส่งของ, บริการ',
                'route' => '#',
                'gradient_start' => '#06B6D4',
                'gradient_end' => '#0891B2',
                'badge' => 'เร็วๆ นี้',
            ],
            // 6. AI Bot
            [
                'id' => 'aibot',
                'icon' => '🤖',
                'title' => 'AI Bot',
                'subtitle' => 'LINE Bot, Chatbot, Automation',
                'route' => route('user.ai-gen.index'),
                'gradient_start' => '#EC4899',
                'gradient_end' => '#BE185D',
                'badge' => 'NEW',
            ],
            // 7. Academy
            [
                'id' => 'academy',
                'icon' => '🎓',
                'title' => 'Academy',
                'subtitle' => 'เรียนรู้, หลักสูตร, Certificate',
                'route' => '#',
                'gradient_start' => '#14B8A6',
                'gradient_end' => '#0D9488',
                'badge' => 'เร็วๆ นี้',
            ],
            // 8. Gaming & Rewards
            [
                'id' => 'gaming',
                'icon' => '🎮',
                'title' => 'Gaming & Rewards',
                'subtitle' => 'เกม, Quest, Achievement',
                'route' => '#',
                'gradient_start' => '#F97316',
                'gradient_end' => '#EA580C',
                'badge' => 'เร็วๆ นี้',
            ],
        ];
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request, ImageUploadService $imageUploadService)
    {
        $user = Auth::user();

        $rules = [
            // ข้อมูลส่วนตัว
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
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
            if (! Hash::check($request->current_password, $user->password)) {
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

        // ⚠️ SECURITY: ป้องกันไม่ให้แก้ไข ID Card fields ผ่าน profile update
        // ID Card fields สามารถแก้ไขได้โดย Admin เท่านั้น (ผ่าน Support Ticket)
        $protectedIdCardFields = [
            'id_card_number',
            'thai_first_name',
            'thai_last_name',
            'english_first_name',
            'english_last_name',
            'id_card_birth_date',
            'id_card_religion',
            'id_card_address',
            'id_card_issue_date',
            'id_card_expiry_date',
        ];

        foreach ($protectedIdCardFields as $field) {
            unset($validated[$field]);
        }

        $user->update($validated);

        return redirect()->route('user.profile')
            ->with('success', 'อัปเดตโปรไฟล์เรียบร้อยแล้ว');
    }

    /**
     * แสดงรายการ Commissions ของ User
     *
     * รวมคอมมิชชั่นจากทุกระบบ:
     * - MLM Commissions
     * - Marketplace Commissions
     *
     * @return \Illuminate\View\View
     */
    public function commissions()
    {
        $user = Auth::user();

        // ดึงคอมมิชชั่นจาก MLM
        $mlmCommissions = $user->mlmCommissions()
            ->select([
                'id',
                'user_id',
                'source_id as order_id',
                'type',
                'commission_amount as amount',
                'percentage',
                'status',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->selectRaw("'mlm' as source");

        // ดึงคอมมิชชั่นจาก Marketplace
        $marketplaceCommissions = $user->marketplaceCommissions()
            ->select([
                'id',
                'user_id',
                'order_id',
                'commission_type as type',
                'net_commission as amount',
                'commission_rate as percentage',
                'status',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->selectRaw("'marketplace' as source");

        // รวมและ paginate
        $commissions = $mlmCommissions
            ->union($marketplaceCommissions)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // คำนวณสถิติ
        $mlmStats = $user->mlmCommissions();
        $marketplaceStats = $user->marketplaceCommissions();

        $stats = [
            'total' => $mlmStats->count() + $marketplaceStats->count(),
            'pending' => $mlmStats->clone()->where('status', 'pending')->count()
                + $marketplaceStats->clone()->where('status', 'pending')->count(),
            'approved' => $mlmStats->clone()->where('status', 'approved')->count()
                + $marketplaceStats->clone()->where('status', 'approved')->count(),
            'paid' => $mlmStats->clone()->where('status', 'paid')->count()
                + $marketplaceStats->clone()->where('status', 'paid')->count(),
        ];

        return view('user.commissions', compact('commissions', 'stats'));
    }

    /**
     * อัปเดตรหัสผ่านของ User
     *
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
        if (! Hash::check($request->current_password, $user->password)) {
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
