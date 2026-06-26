<?php

namespace App\Http\Controllers\User;

use App\Helpers\MlmRetentionHelper;
use App\Http\Controllers\Controller;
use App\Services\MlmCalculationService;
use App\Services\MlmGenealogyService;
use App\Services\MlmPvService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MlmDashboardController extends Controller
{
    protected $calculationService;

    protected $genealogyService;

    protected $pvService;

    public function __construct()
    {
        $this->calculationService = new MlmCalculationService;
        $this->genealogyService = new MlmGenealogyService;
        $this->pvService = new MlmPvService;
    }

    public function index()
    {
        $user = Auth::user();
        // หมายเหตุ: MlmMember ไม่มี relation 'rank' (eager-load 'rank' เดิมทำให้ dashboard 500 — RelationNotFoundException)
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return redirect()->route('user.dashboard')
                ->with('info', 'You are not enrolled in any MLM plan yet.');
        }

        // Get recent commissions
        $recentCommissions = $member->commissions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // ดึงสถานะรักษายอด (Volume Retention)
        $retentionStatus = MlmRetentionHelper::getRetentionStatus($member);

        return view('user.mlm.dashboard', compact('member', 'recentCommissions', 'retentionStatus'));
    }

    public function plan($memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $statistics = $this->calculationService->getMemberStatistics($member);
        $pvStats = $this->pvService->getMemberPvStatistics($member);

        return view('user.mlm.plan-dashboard', compact('member', 'statistics', 'pvStats'));
    }

    public function commissions(Request $request)
    {
        $user = Auth::user();

        $query = $user->mlmCommissions()
            ->with(['plan', 'member', 'fromMember.user'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('plan_id')) {
            $query->where('mlm_plan_id', $request->plan_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $commissions = $query->paginate(20);

        // Statistics
        $stats = [
            'pending' => $user->mlmCommissions()->where('status', 'pending')->sum('commission_amount'),
            'approved' => $user->mlmCommissions()->where('status', 'approved')->sum('commission_amount'),
            'paid' => $user->mlmCommissions()->where('status', 'paid')->sum('commission_amount'),
            'this_month' => $user->mlmCommissions()
                ->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('commission_amount'),
        ];

        return view('user.mlm.commissions', compact('commissions', 'stats'));
    }

    public function genealogy(Request $request)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return redirect()->route('user.mlm.dashboard')
                ->with('error', 'You are not enrolled in any MLM plan yet.');
        }

        $treeType = $request->get('type', $member->plan->type === 'binary' || $member->plan->type === 'hybrid' ? 'binary' : 'unilevel');

        return view('user.mlm.genealogy', compact('member', 'treeType'));
    }

    public function referral()
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return redirect()->route('user.mlm.dashboard')
                ->with('error', 'You are not enrolled in any MLM plan yet.');
        }

        // ใช้หน้าสมัครแบบบังคับเพิ่มเพื่อน LINE OA ก่อน (แทนระบบ LINE Membership Signup เดิมที่ถูกลบไปแล้ว)
        // Register/LineRegistration อ่าน ?ref= เป็น member_code เพื่อผูกผู้แนะนำ
        $referralUrl = route('line.registration.register', ['ref' => $member->member_code]);

        return view('user.mlm.referral', compact('member', 'referralUrl'));
    }

    public function team()
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        // ถ้าไม่ได้เป็น MLM member ให้แสดงหน้าแนะนำแทนที่จะ redirect
        if (! $member) {
            return view('user.mlm.team', [
                'member' => null,
                'directReferrals' => collect([]),
                'notMlmMember' => true,
            ]);
        }

        $directReferrals = $member->unilevelChildren()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('user.mlm.team', compact('member', 'directReferrals'));
    }

    public function getTreeData(Request $request, $memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $treeType = $request->get('type', 'unilevel');
        $maxDepth = $request->get('depth', 5);

        $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);

        return response()->json([
            'success' => true,
            'data' => $treeData,
        ]);
    }

    public function referrals($memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $directReferrals = $member->unilevelChildren()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('user.mlm.referrals', compact('member', 'directReferrals'));
    }

    public function referralLink($memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        // ใช้หน้าสมัครแบบบังคับเพิ่มเพื่อน LINE OA ก่อน (แทนระบบ LINE Membership Signup เดิมที่ถูกลบไปแล้ว)
        // Register/LineRegistration อ่าน ?ref= เป็น member_code เพื่อผูกผู้แนะนำ
        $referralUrl = route('line.registration.register', ['ref' => $member->member_code]);

        return view('user.mlm.referral-link', compact('member', 'referralUrl'));
    }

    public function pvHistory(Request $request, $memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $query = $member->pvTransactions()
            ->with(['order', 'product'])
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->paginate(20);

        return view('user.mlm.pv-history', compact('member', 'transactions'));
    }

    public function binaryPosition($memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        if ($member->plan->type !== 'binary' && $member->plan->type !== 'hybrid') {
            return redirect()->route('user.mlm.dashboard')
                ->with('error', 'This plan does not support binary structure');
        }

        $position = $this->genealogyService->getMemberPosition($member, 'binary');

        return view('user.mlm.binary-position', compact('member', 'position'));
    }

    /**
     * API สำหรับดึงข้อมูลผังสายงาน MLM (Binary/Unilevel Tree Data)
     * ใช้สำหรับ mlm-genealogy-premium.js
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function genealogyData(Request $request)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'คุณยังไม่ได้เป็นสมาชิก MLM',
                'data' => null,
            ], 404);
        }

        $treeType = $request->get('type', $member->plan->type === 'binary' || $member->plan->type === 'hybrid' ? 'binary' : 'unilevel');
        $maxDepth = min((int) $request->get('depth', 5), 10); // จำกัดสูงสุด 10 ชั้น

        try {
            $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);

            return response()->json([
                'success' => true,
                'data' => $treeData,
                'member' => [
                    'id' => $member->id,
                    'member_code' => $member->member_code,
                    'name' => $user->name,
                    'plan_type' => $member->plan->type ?? 'unilevel',
                ],
                'tree_type' => $treeType,
                'max_depth' => $maxDepth,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching genealogy data', [
                'user_id' => $user->id,
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลผังสายงาน',
                'data' => null,
            ], 500);
        }
    }

    /**
     * แสดงหน้าตำแหน่งใน Binary Tree (ไม่ต้องระบุ memberCode)
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function binaryPositionPage()
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return redirect()->route('user.mlm.dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        if ($member->plan->type !== 'binary' && $member->plan->type !== 'hybrid') {
            return redirect()->route('user.mlm.dashboard')
                ->with('error', 'แผนของคุณไม่รองรับโครงสร้าง Binary');
        }

        $position = $this->genealogyService->getMemberPosition($member, 'binary');

        return view('user.mlm.binary-position', compact('member', 'position'));
    }

    /**
     * ดึงการตั้งค่า MLM สำหรับ income simulator
     * Read-only endpoint สำหรับ user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        try {
            // ใช้ MlmGlobalSetting model เพื่อดึงข้อมูล
            $settings = \App\Models\MlmGlobalSetting::getAll();

            return response()->json([
                'success' => true,
                'settings' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถโหลดการตั้งค่าได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * คำนวณ Commission Preview สำหรับ Income Simulator
     * ใช้ logic เดียวกับ Admin Calculator เพื่อความถูกต้อง
     *
     * ⚠️ IMPORTANT: ฟังก์ชันนี้ใช้ค่าจาก MlmGlobalSetting เท่านั้น
     * ไม่ใช้ค่าจาก MlmPlan (per-plan settings) เพื่อความเป็นเอกภาพ
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewCalculation(Request $request)
    {
        try {
            $validated = $request->validate([
                'sales_amount' => 'required|numeric|min:0',
                'team_size' => 'nullable|integer|min:0',
                'member_depth' => 'nullable|integer|min:1|max:10',
                'binary_pairs' => 'nullable|integer|min:0',
                'left_ratio' => 'nullable|numeric|min:0|max:100',
                'rank_multiplier' => 'nullable|numeric|min:1',
            ]);

            $salesAmount = $validated['sales_amount'];
            $teamSize = $validated['team_size'] ?? 10;
            $memberDepth = $validated['member_depth'] ?? 10;
            $binaryPairs = $validated['binary_pairs'] ?? 0;
            $leftRatio = $validated['left_ratio'] ?? \App\Models\MlmGlobalSetting::get('binary_default_left_ratio', 50);
            $rankMultiplier = $validated['rank_multiplier'] ?? 1;

            // ดึงค่า settings จาก Global Settings (แหล่งเดียว)
            $pvRate = \App\Models\MlmGlobalSetting::get('global_pv_rate', 1);
            $commissionPerPv = \App\Models\MlmGlobalSetting::get('commission_per_pv', 1);
            $unilevelLevels = \App\Models\MlmGlobalSetting::get('unilevel_levels', []);
            $unilevelMaxDepth = \App\Models\MlmGlobalSetting::get('unilevel_max_depth', 10);
            $binaryPairCommission = \App\Models\MlmGlobalSetting::get('binary_pair_commission', 100);
            $binaryMatchPercentage = \App\Models\MlmGlobalSetting::get('binary_match_percentage', 50);
            $binaryPairingType = \App\Models\MlmGlobalSetting::get('binary_pairing_type', '1:1');

            // Constraints
            $maxPerLevel = \App\Models\MlmGlobalSetting::get('unilevel_max_commission_per_level', null);
            $maxPerDay = \App\Models\MlmGlobalSetting::get('binary_max_commission_per_day', null);
            $maxPairsPerDay = \App\Models\MlmGlobalSetting::get('binary_max_pairs_per_day', null);
            $overpayEnabled = \App\Models\MlmGlobalSetting::get('overpay_protection_enabled', true);
            $maxCommissionPercentage = \App\Models\MlmGlobalSetting::get('max_commission_percentage', 50);

            // System enabled flags
            $unilevelEnabled = \App\Models\MlmGlobalSetting::get('unilevel_enabled', true);
            $binaryEnabled = \App\Models\MlmGlobalSetting::get('binary_enabled', true);

            // คำนวณ PV
            $totalPv = $salesAmount / $pvRate;

            // ==== UNILEVEL CALCULATION ====
            $unilevelTotal = 0;
            $unilevelBreakdown = [];

            if ($unilevelEnabled && is_array($unilevelLevels)) {
                foreach ($unilevelLevels as $level) {
                    $levelNum = $level['level'] ?? 0;

                    if ($levelNum > $memberDepth || $levelNum > $unilevelMaxDepth) {
                        continue;
                    }

                    $percentage = $level['percentage'] ?? 0;
                    if ($percentage <= 0) {
                        continue;
                    }

                    // คำนวณ commission ตาม percentage จริงจาก settings
                    $commission = ($totalPv * $percentage) / 100;

                    // Apply rank multiplier
                    $commission *= $rankMultiplier;

                    // Apply level constraint
                    if ($maxPerLevel && $commission > $maxPerLevel) {
                        $commission = $maxPerLevel;
                    }

                    $unilevelBreakdown[] = [
                        'level' => $levelNum,
                        'percentage' => $percentage,
                        'commission' => round($commission, 2),
                    ];

                    $unilevelTotal += $commission;
                }
            }

            // ==== BINARY CALCULATION ====
            $binaryTotal = 0;
            $binaryBreakdown = [];

            if ($binaryEnabled && $binaryPairs > 0) {
                // คำนวณ left/right ratio จาก settings
                $leftLegPv = $totalPv * ($leftRatio / 100);
                $rightLegPv = $totalPv * ((100 - $leftRatio) / 100);
                $weakerLegPv = min($leftLegPv, $rightLegPv);

                // Pairing type
                $pairRatio = $binaryPairingType === '2:1' ? 2 : 1;

                // คำนวณ pairs ที่เป็นไปได้
                $maxPossiblePairs = floor($weakerLegPv / $pairRatio);
                $actualPairs = min($binaryPairs, $maxPossiblePairs);

                // Apply daily limits
                if ($maxPairsPerDay && $actualPairs > $maxPairsPerDay) {
                    $actualPairs = $maxPairsPerDay;
                }

                // คำนวณ commission
                $binaryCommission = $actualPairs * $binaryPairCommission;

                // Apply rank multiplier
                $binaryCommission *= $rankMultiplier;

                // Apply daily commission limit
                if ($maxPerDay && $binaryCommission > $maxPerDay) {
                    $binaryCommission = $maxPerDay;
                }

                $binaryTotal = $binaryCommission;

                $binaryBreakdown = [
                    'left_leg_pv' => round($leftLegPv, 2),
                    'right_leg_pv' => round($rightLegPv, 2),
                    'weaker_leg_pv' => round($weakerLegPv, 2),
                    'pairs_requested' => $binaryPairs,
                    'pairs_actual' => $actualPairs,
                    'commission_per_pair' => $binaryPairCommission,
                    'total_commission' => round($binaryTotal, 2),
                ];
            }

            // ==== TOTAL CALCULATION ====
            $totalCommission = $unilevelTotal + $binaryTotal;

            // Overpay protection
            $isOverpay = false;
            $totalPercentage = $salesAmount > 0 ? ($totalCommission / $salesAmount) * 100 : 0;

            if ($overpayEnabled && $totalPercentage > $maxCommissionPercentage) {
                $isOverpay = true;
                // ปรับลด commission ตามสัดส่วน
                $adjustmentRatio = $maxCommissionPercentage / $totalPercentage;
                $unilevelTotal *= $adjustmentRatio;
                $binaryTotal *= $adjustmentRatio;
                $totalCommission = $unilevelTotal + $binaryTotal;
                $totalPercentage = $maxCommissionPercentage;
            }

            return response()->json([
                'success' => true,
                'input' => [
                    'sales_amount' => $salesAmount,
                    'team_size' => $teamSize,
                    'member_depth' => $memberDepth,
                    'binary_pairs' => $binaryPairs,
                    'left_ratio' => $leftRatio,
                    'rank_multiplier' => $rankMultiplier,
                ],
                'calculation' => [
                    'total_pv' => round($totalPv, 2),
                    'unilevel_commission' => round($unilevelTotal, 2),
                    'binary_commission' => round($binaryTotal, 2),
                    'total_commission' => round($totalCommission, 2),
                    'total_percentage' => round($totalPercentage, 2),
                ],
                'breakdown' => [
                    'unilevel' => $unilevelBreakdown,
                    'binary' => $binaryBreakdown,
                ],
                'constraints' => [
                    'overpay_protection_enabled' => $overpayEnabled,
                    'max_commission_percentage' => $maxCommissionPercentage,
                    'is_overpay' => $isOverpay,
                    'max_per_level' => $maxPerLevel,
                    'max_pairs_per_day' => $maxPairsPerDay,
                    'max_per_day' => $maxPerDay,
                ],
                'settings_used' => [
                    'pv_rate' => $pvRate,
                    'unilevel_enabled' => $unilevelEnabled,
                    'binary_enabled' => $binaryEnabled,
                    'binary_pairing_type' => $binaryPairingType,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการคำนวณ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
