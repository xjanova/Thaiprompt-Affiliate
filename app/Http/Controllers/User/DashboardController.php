<?php

namespace App\Http\Controllers\User;

use App\Helpers\MlmRetentionHelper;
use App\Http\Controllers\Controller;
use App\Rules\NotReservedEmailDomain;
use App\Services\AccountEmailChangeService;
use App\Services\ImageUploadService;
use App\Support\AccountEmail;
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

        // ⚠️ ยอดจริงอยู่ที่ตาราง wallets — users ไม่มีคอลัมน์ wallet_balance
        //    (เดิมอ่าน $user->wallet_balance ได้ null ตลอด → ยอดเงินขึ้น ฿0 ให้ทุกคน)
        //    ไม่สร้างกระเป๋าให้ตอนเปิดหน้า — ยังไม่มีก็แสดง 0 ไปก่อน
        $walletBalance = (float) ($user->wallet?->balance ?? 0);

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
        // ⚠️ ต้อง subMonthNoOverflow — วันที่ 31 (หรือ 29-31 มี.ค.) subMonth ธรรมดาจะล้นกลับมาเป็นเดือนนี้
        //    แล้ว % เติบโตจะเทียบเดือนนี้กับตัวเอง
        $lastMonthCommission = $user->mlmCommissions()
            ->whereYear('created_at', now()->subMonthNoOverflow()->year)
            ->whereMonth('created_at', now()->subMonthNoOverflow()->month)
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
                ->whereYear('created_at', now()->subMonthNoOverflow()->year)
                ->whereMonth('created_at', now()->subMonthNoOverflow()->month)
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

        // ขั้นระดับทั้งหมด — ใช้วาดแถบขั้นบนการ์ดระดับสมาชิก
        $rankLadder = \App\Models\Rank::where('is_active', true)
            ->orderBy('level', 'asc')
            ->get(['id', 'level', 'name', 'name_th']);

        // ===============================================
        // 6. กิจกรรมล่าสุด (Recent Activity)
        // ===============================================

        // ⚠️ MlmCommission ไม่มี relation ชื่อ mlmMember (มีแค่ member / fromMember)
        //    เดิม ->with('mlmMember.user') ทำให้หน้านี้ 500 ทันทีที่ผู้ใช้มีคอมมิชชั่นสักรายการ
        //    รายการนี้ไม่ได้ใช้ข้อมูล relation เลย จึงไม่ต้อง eager load
        $recentActivities = $user->mlmCommissions()
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

        // ดึงข้อมูลคอมมิชชัน 12 เดือนในคำสั่งเดียว (แทน 12 queries)
        $startDate = now()->startOfMonth()->subMonths(11);
        $monthlyCommissions = $user->mlmCommissions()
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['approved', 'paid'])
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(commission_amount) as total')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.$item->month);

        for ($i = 11; $i >= 0; $i--) {
            // ⚠️ ต้องถอยจากวันที่ 1 — วันที่ 29-31 ถ้า subMonths ตรงๆ เดือนสั้นจะล้นไปเดือนถัดไป (ก.พ. หาย มี.ค. ซ้ำ)
            $date = now()->startOfMonth()->subMonths($i);
            $key = $date->year.'-'.$date->month;
            $monthName = $date->locale('th')->translatedFormat('M Y');

            $chartLabels[] = $monthName;
            $chartValues[] = (float) ($monthlyCommissions[$key]->total ?? 0);
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
            ->with('fromMember.user')
            ->latest()
            ->limit(5)
            ->get();

        // ===============================================
        // 11. ข้อมูล KYC สำหรับแจ้งเตือน
        // ===============================================

        // ตรวจสอบ KYC status จาก KycVerification record (ข้อมูลที่ถูกต้องที่สุด)
        // 🪪 (2026-10-04) ไม่นับรอบ AI eKYC ที่ทำค้าง (draft) / ให้ถ่ายใหม่ (retake) / คำขอที่ถูกแทนแล้ว (superseded)
        //    ไม่งั้นการ sync ด้านล่างจะทับสถานะจริง (เช่น pending ของคำขอแบบเดิม) เป็น not_submitted
        $latestKyc = \App\Models\KycVerification::where('user_id', $user->id)
            ->whereNotIn('status', [\App\Models\KycVerification::STATUS_RETAKE, \App\Models\KycVerification::STATUS_SUPERSEDED])
            ->where(function ($q) {
                $q->where('method', '!=', \App\Models\KycVerification::METHOD_EKYC)
                    ->orWhereNull('method')
                    ->orWhere('status', '!=', 'draft');
            })
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
        // 12. สถานะรักษายอด (Volume Retention)
        // ===============================================

        $retentionStatus = $mlmMember
            ? MlmRetentionHelper::getRetentionStatus($mlmMember)
            : null;

        // ===============================================
        // 13. ลิงก์ชวนเพื่อน (ชุดเดียวกับ MlmDashboardController::referral)
        // ===============================================

        // หน้าสมัครแบบบังคับเพิ่มเพื่อน LINE OA — อ่าน ?ref= เป็น member_code เพื่อผูกผู้แนะนำ
        $referralUrl = ($mlmMember && $mlmMember->member_code && \Illuminate\Support\Facades\Route::has('line.registration.register'))
            ? route('line.registration.register', ['ref' => $mlmMember->member_code])
            : null;

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
            'thisMonthReferrals',
            'referralUrl',

            // Rank
            'currentRank',
            'nextRank',
            'rankProgress',
            'pointsNeeded',
            'rankLadder',

            // Activities
            'recentActivities',
            'recentCommissions',

            // Charts & Stats
            'stats',
            'chartData',

            // KYC
            'kycStatus',
            'showKycAlert',

            // Retention
            'retentionStatus'
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
        // ⚠️ ยอดจริงอยู่ที่ตาราง wallets — users ไม่มีคอลัมน์ wallet_balance
        //    (เดิมอ่าน $user->wallet_balance ได้ null ตลอด → ยอดเงินขึ้น ฿0 ให้ทุกคน)
        //    ไม่สร้างกระเป๋าให้ตอนเปิดหน้า — ยังไม่มีก็แสดง 0 ไปก่อน
        $walletBalance = (float) ($user->wallet?->balance ?? 0);

        // คำนวณ wallet growth (เทียบกับเดือนที่แล้ว)
        $thisMonthEarnings = $user->mlmCommissions()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('commission_amount') ?? 0;

        $lastMonthEarnings = $user->mlmCommissions()
            ->whereYear('created_at', now()->subMonthNoOverflow()->year)
            ->whereMonth('created_at', now()->subMonthNoOverflow()->month)
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
                'route' => route('chatbot.index'),
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
        ];
    }

    /**
     * แสดงหน้าโปรไฟล์ของ User
     *
     * @return \Illuminate\View\View
     */
    public function profile(AccountEmailChangeService $emailChange)
    {
        $user = Auth::user();

        // วิธียืนยันเมื่อเปลี่ยนอีเมล (รหัสผ่าน/รหัสทางอีเมลเดิม หรือ รหัสทางอีเมลใหม่) — ฟอร์มแสดงช่องตามนี้
        $emailChangeMode = $emailChange->mode($user);
        $emailIsReal = AccountEmail::isDeliverable($user->email);
        $currentEmailMasked = $emailIsReal ? AccountEmail::mask((string) $user->email) : null;

        return view('user.profile', compact('user', 'emailChangeMode', 'emailIsReal', 'currentEmailMasked'));
    }

    /**
     * อัปเดตโปรไฟล์ของ User
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request, ImageUploadService $imageUploadService, AccountEmailChangeService $emailChange)
    {
        $user = Auth::user();

        // 🔒 (2026-10-29) เปลี่ยนอีเมล = ต้องยืนยันตัวตน (ดู AccountEmailChangeService)
        $oldEmail = (string) $user->email;
        $emailMode = $emailChange->mode($user);
        $emailChanging = $emailChange->isChange($user, $request->input('email'));

        $emailRules = ['bail', 'required', 'email', 'max:255', 'unique:users,email,'.$user->id];
        // กันตั้งอีเมลโดเมนสงวน/อีเมลระบบเฉพาะตอนเปลี่ยน — บัญชี LINE/FB ที่ระบบตั้งอีเมลให้ ต้องแก้ชื่อ/เบอร์ได้โดยไม่ถูกบังคับเปลี่ยนอีเมล
        if ($emailChanging) {
            $emailRules[] = new NotReservedEmailDomain;
            $emailRules[] = AccountEmail::deliverableRule();
        }

        $rules = [
            // ข้อมูลส่วนตัว
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],

            // ที่อยู่หลัก (Billing Address)
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],

            // รูปโปรไฟล์ (จะแปลงเป็น WebP อัตโนมัติ)
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'], // 5MB max
        ];

        // 🔒 (2026-10-29) เดิมรับ current_password/new_password ในฟอร์มนี้ด้วย → เปลี่ยนรหัสผ่านผ่าน PUT /profile
        //    ได้โดยไม่ผ่าน turnstile:password_change + two-factor:password_change ที่ route update-password ตั้งไว้
        //    และถ้ากรอกรหัสใหม่แต่ไม่กรอกรหัสเดิม ระบบเงียบ ลูกค้านึกว่าเปลี่ยนแล้ว → ตอนนี้เปลี่ยนรหัสผ่านได้ทางเดียว
        if ($request->filled('new_password')) {
            return back()->withInput($request->except(['current_password', 'new_password', 'new_password_confirmation']))
                ->with('error', 'เปลี่ยนรหัสผ่านที่การ์ด "เปลี่ยนรหัสผ่าน" แยกต่างหาก ข้อมูลโปรไฟล์ยังไม่ถูกบันทึก');
        }

        $validated = $request->validate($rules);

        // ตรวจก่อนอัปโหลดรูป — ไม่ผ่านแล้วต้องไม่มีอะไรเปลี่ยน (รวมถึงรูปเดิมที่จะถูกลบหลังอัปโหลดใหม่)
        if ($emailChanging) {
            $error = $emailChange->confirmationError($user, $validated['email'], $request->input('email_password'), $request->input('email_code'));

            if ($error !== null) {
                return back()->withInput($request->except(['email_password', 'email_code']))
                    ->withErrors([$error['field'] => $error['message']]);
            }
        } else {
            // ต่างแค่ตัวพิมพ์เล็ก/ใหญ่ = กล่องเดิม → เก็บค่าเดิมไว้ตรงตัว
            $validated['email'] = $oldEmail;
        }

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // อัปโหลดรูปใหม่ก่อน (WebP, สูงสุด 800x800, quality 90) แล้วค่อยลบรูปเดิม
            // 🐛 (2026-10-29) เดิมลบรูปเดิมก่อน → ถ้าอัปโหลดใหม่พัง ลูกค้าเสียรูปโปรไฟล์ไปเลย
            $oldPicture = $user->profile_picture;
            try {
                $validated['profile_picture'] = $imageUploadService->uploadImage(
                    $request->file('profile_picture'),
                    'avatars',
                    800,
                    800,
                    90
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Profile picture upload failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

                return back()->withInput()->with('error', 'อัปโหลดรูปโปรไฟล์ไม่สำเร็จ ลองใช้รูปอื่นหรือลดขนาดไฟล์ แล้วบันทึกอีกครั้ง');
            }

            if ($oldPicture && $oldPicture !== $validated['profile_picture']) {
                $imageUploadService->deleteImage($oldPicture);
            }
        }

        // เบอร์เปลี่ยน (รวมถึงลบเบอร์ทิ้ง) → ต้องยืนยันเบอร์ใหม่
        // 🐛 (2026-10-29) เดิมใช้ isset() → ลบเบอร์ (null) แล้วสถานะ "ยืนยันแล้ว" ยังค้างอยู่
        if (array_key_exists('phone', $validated) && $validated['phone'] !== $user->phone) {
            // Phone changed - reset verification status
            $validated['phone_verified'] = false;
            $validated['phone_verified_at'] = null;
        }

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

        if ($emailChanging) {
            $emailChange->completeChange($user, $oldEmail, $emailMode);

            return redirect()->route('user.profile')->with('success', $emailMode === AccountEmailChangeService::MODE_PASSWORD
                ? 'อัปเดตโปรไฟล์เรียบร้อยแล้ว เราส่งลิงก์ยืนยันไปที่อีเมลใหม่ (กรุณากดยืนยันภายใน 24 ชั่วโมง) และแจ้งเตือนไปที่อีเมลเดิมแล้ว'
                : 'อัปเดตโปรไฟล์และยืนยันอีเมลใหม่เรียบร้อยแล้ว');
        }

        return redirect()->route('user.profile')
            ->with('success', 'อัปเดตโปรไฟล์เรียบร้อยแล้ว');
    }

    /**
     * ส่งรหัส 6 หลักสำหรับเปลี่ยนอีเมล (route: user.profile.email.send-code — เรียกด้วย fetch จากหน้าโปรไฟล์)
     *
     * บัญชีที่มีอีเมลจริง → ส่งไปอีเมลเดิม ; บัญชีอีเมลระบบ (LINE/FB) → ส่งไปอีเมลใหม่
     */
    public function sendEmailChangeCode(Request $request, AccountEmailChangeService $emailChange)
    {
        $user = Auth::user();

        $request->validate([
            'new_email' => ['bail', 'required', 'email', 'max:255', 'unique:users,email,'.$user->id, new NotReservedEmailDomain, AccountEmail::deliverableRule()],
        ], [], ['new_email' => 'อีเมลใหม่']);

        if (! $emailChange->isChange($user, $request->input('new_email'))) {
            return response()->json(['success' => false, 'message' => 'อีเมลใหม่ต้องไม่ซ้ำกับอีเมลปัจจุบัน'], 422);
        }

        $result = $emailChange->sendCode($user, (string) $request->input('new_email'));
        $sent = (bool) ($result['success'] ?? false);

        return response()->json([
            'success' => $sent,
            'message' => $result['message'] ?? 'ส่งรหัสไม่สำเร็จ กรุณาลองใหม่',
            'retry_after' => $result['retry_after'] ?? null,
        ], $sent ? 200 : (isset($result['retry_after']) ? 429 : 503));
    }

    /**
     * ส่งลิงก์ยืนยันอีเมลอีกครั้ง (route: user.profile.email.resend-verification)
     */
    public function resendEmailVerification(AccountEmailChangeService $emailChange)
    {
        $user = Auth::user();

        if ($user->email_verified_at) {
            return back()->with('success', 'อีเมลนี้ยืนยันแล้ว');
        }

        if (! AccountEmail::isDeliverable($user->email)) {
            return back()->with('error', 'บัญชีนี้ยังไม่มีอีเมลจริง กรุณาเปลี่ยนเป็นอีเมลของคุณก่อน');
        }

        return $emailChange->sendVerificationLink($user)
            ? back()->with('success', 'ส่งลิงก์ยืนยันไปที่ '.AccountEmail::mask((string) $user->email).' แล้ว')
            : back()->with('error', 'ส่งอีเมลไม่สำเร็จ กรุณาลองใหม่อีกครั้งในภายหลัง');
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
            // ยอดเงิน (เดิมมีแค่จำนวนรายการ ลูกค้าไม่รู้ว่าได้เท่าไร)
            'amount_earned' => (float) $mlmStats->clone()->whereIn('status', ['approved', 'paid'])->sum('commission_amount')
                + (float) $marketplaceStats->clone()->whereIn('status', ['approved', 'paid'])->sum('net_commission'),
            'amount_pending' => (float) $mlmStats->clone()->where('status', 'pending')->sum('commission_amount')
                + (float) $marketplaceStats->clone()->where('status', 'pending')->sum('net_commission'),
            'amount_paid' => (float) $mlmStats->clone()->where('status', 'paid')->sum('commission_amount')
                + (float) $marketplaceStats->clone()->where('status', 'paid')->sum('net_commission'),
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
