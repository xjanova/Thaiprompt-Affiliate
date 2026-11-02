<?php

namespace App\Http\Controllers\User;

use App\Helpers\MlmRetentionHelper;
use App\Http\Controllers\Controller;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\Rank;
use App\Services\MlmGenealogyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * หน้า MLM ฝั่งสมาชิก (ธีมโนวาเต็ม) + API อ่านอย่างเดียวของผังสายงาน/เครื่องจำลองรายได้
 *
 * ⚠️ หน้าที่ต้องรู้ว่า "ใครเป็นเจ้าของ" ดึงสมาชิกจาก Auth::user()->mlmMembers() เสมอ
 *    ไม่รับรหัสสมาชิกจาก URL (หน้าแบบ {memberCode} เดิมไม่มี route และถูกลบไปแล้ว)
 */
class MlmDashboardController extends Controller
{
    /**
     * ประเภทคอมมิชชั่นที่มีจริงใน mlm_commissions.type => ป้ายภาษาไทย
     * (ตัวกรองเดิมส่ง direct_referral/unilevel/generation/bonus ซึ่งไม่มีใน enum → กรองแล้วว่างทุกครั้ง)
     */
    public const COMMISSION_TYPES = [
        'unilevel_direct' => 'แนะนำตรง (ชั้น 1)',
        'unilevel_indirect' => 'Unilevel ชั้นลึก',
        'unilevel_rollup' => 'Unilevel โรลอัพ',
        'binary_pair' => 'Binary จับคู่',
        'binary_matching' => 'Binary Matching',
        'sponsor_bonus' => 'โบนัสผู้แนะนำ',
        'rank_bonus' => 'โบนัสยศ',
        'leadership_bonus' => 'โบนัสผู้นำ',
        'matching_bonus' => 'Matching Bonus',
        'pool_bonus' => 'โบนัสกองกลาง',
    ];

    /**
     * สถานะคอมมิชชั่นที่มีจริงใน mlm_commissions.status => ป้ายภาษาไทย
     */
    public const COMMISSION_STATUSES = [
        'pending' => 'รอดำเนินการ',
        'approved' => 'อนุมัติแล้ว',
        'paid' => 'จ่ายแล้ว',
        'rejected' => 'ไม่อนุมัติ',
        'cancelled' => 'ยกเลิก',
        'clawback' => 'เรียกคืน',
    ];

    /**
     * สถานะที่ไม่นับเป็นรายได้ (ใช้ตอนรวมยอดรายขา)
     */
    private const VOID_STATUSES = ['rejected', 'cancelled', 'clawback'];

    protected $genealogyService;

    public function __construct()
    {
        $this->genealogyService = new MlmGenealogyService;
    }

    /**
     * แดชบอร์ด MLM ของสมาชิก
     */
    public function index()
    {
        $user = Auth::user();
        // หมายเหตุ: MlmMember ไม่มี relation 'rank' (eager-load 'rank' เดิมทำให้ dashboard 500 — RelationNotFoundException)
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return redirect()->route('user.dashboard')
                ->with('info', 'คุณยังไม่ได้เข้าร่วมแผน MLM');
        }

        // คอมมิชชั่นล่าสุด 5 รายการ
        $recentCommissions = $member->commissions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // ดึงสถานะรักษายอด (Volume Retention)
        $retentionStatus = MlmRetentionHelper::getRetentionStatus($member);

        // ยศจริงของสมาชิก (เดิมหน้านี้โชว์ 65% / Silver / 5,000 PV แบบตายตัว ไม่ใช่ข้อมูลจริง)
        $rankInfo = $this->rankInfo($user);

        $referralUrl = $this->referralUrlFor($member);
        $commissionTypes = self::COMMISSION_TYPES;
        $commissionStatuses = self::COMMISSION_STATUSES;

        return view('user.mlm.dashboard', compact(
            'member', 'recentCommissions', 'retentionStatus', 'rankInfo', 'referralUrl', 'commissionTypes', 'commissionStatuses'
        ));
    }

    /**
     * ประวัติคอมมิชชั่น MLM (ของผู้ใช้ที่ล็อกอินเท่านั้น)
     */
    public function commissions(Request $request)
    {
        $user = Auth::user();

        $query = $user->mlmCommissions()
            ->with(['plan', 'member', 'fromMember.user'])
            ->orderBy('created_at', 'desc');

        // ตัวกรอง — ใช้ filled() เพราะฟอร์มส่ง type= / status= ว่างเมื่อเลือก "ทั้งหมด"
        // (has() เดิมทำให้ where type = '' → ไม่พบรายการเลย)
        if ($request->filled('plan_id')) {
            $query->where('mlm_plan_id', (int) $request->input('plan_id'));
        }

        $status = $request->input('status');
        if ($request->filled('status') && array_key_exists($status, self::COMMISSION_STATUSES)) {
            $query->where('status', $status);
        }

        $type = $request->input('type');
        if ($request->filled('type') && array_key_exists($type, self::COMMISSION_TYPES)) {
            $query->where('type', $type);
        }

        $commissions = $query->paginate(20)->withQueryString();

        // สถิติ — "เดือนนี้" ต้องเช็คปีด้วย (whereMonth อย่างเดียวรวมเดือนเดียวกันของปีก่อน ๆ)
        $stats = [
            'pending' => $user->mlmCommissions()->where('status', 'pending')->sum('commission_amount'),
            'approved' => $user->mlmCommissions()->where('status', 'approved')->sum('commission_amount'),
            'paid' => $user->mlmCommissions()->where('status', 'paid')->sum('commission_amount'),
            'this_month' => $user->mlmCommissions()
                ->where('status', 'paid')
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('commission_amount'),
        ];

        $commissionTypes = self::COMMISSION_TYPES;
        $commissionStatuses = self::COMMISSION_STATUSES;

        return view('user.mlm.commissions', compact('commissions', 'stats', 'commissionTypes', 'commissionStatuses'));
    }

    /**
     * ผังสายงาน (ตัวแสดงผลดึงข้อมูลจาก genealogyData)
     */
    public function genealogy(Request $request)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return redirect()->route('user.dashboard')
                ->with('info', 'คุณยังไม่ได้เข้าร่วมแผน MLM');
        }

        $treeType = in_array($member->plan?->type, ['binary', 'hybrid'], true) ? 'binary' : 'unilevel';
        $referralUrl = $this->referralUrlFor($member);

        return view('user.mlm.genealogy', compact('member', 'treeType', 'referralUrl'));
    }

    /**
     * ลิงก์แนะนำ + QR
     */
    public function referral()
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return redirect()->route('user.dashboard')
                ->with('info', 'คุณยังไม่ได้เข้าร่วมแผน MLM');
        }

        $referralUrl = $this->referralUrlFor($member);

        return view('user.mlm.referral', compact('member', 'referralUrl'));
    }

    /**
     * ทีมแนะนำตรงของฉัน
     */
    public function team()
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        // ถ้าไม่ได้เป็น MLM member ให้แสดงหน้าแนะนำแทนที่จะ redirect
        if (! $member) {
            return view('user.mlm.team', [
                'member' => null,
                'directReferrals' => collect([]),
                'teamStats' => null,
                'notMlmMember' => true,
            ]);
        }

        $directReferrals = $member->unilevelChildren()
            ->with('user.currentRank')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // สถิตินับจากทั้งทีม (เดิมนับจากหน้าปัจจุบัน 20 แถว และนับสถานะ pending ที่ไม่มีใน enum)
        $base = fn () => $member->unilevelChildren();
        $teamStats = [
            'total' => $directReferrals->total(),
            'active' => $base()->where('status', 'active')->count(),
            'inactive' => $base()->whereIn('status', ['inactive', 'suspended'])->count(),
            'this_month' => $base()->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
        ];

        return view('user.mlm.team', compact('member', 'directReferrals', 'teamStats'));
    }

    /**
     * API ผังสายงานตามรหัสสมาชิก (ยังไม่มี route — เก็บไว้ให้แอป/ของเดิม)
     */
    public function getTreeData(Request $request, $memberCode)
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->where('member_code', $memberCode)->firstOrFail();

        $treeType = $request->get('type', 'unilevel');
        // cap depth ที่ 10 กัน recursion หนักเกิน (เดิมรับค่า depth โดยไม่จำกัด)
        $maxDepth = min((int) $request->get('depth', 5), 10);

        $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);

        return response()->json([
            'success' => true,
            'data' => $this->stripPrivateNodeData($treeData),
        ]);
    }

    /**
     * API สำหรับดึงข้อมูลผังสายงาน MLM (Binary/Unilevel Tree Data)
     * ใช้สำหรับ resources/js/mlm-genealogy-v3.js
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
        $treeType = in_array($treeType, ['binary', 'unilevel'], true) ? $treeType : 'unilevel';
        $maxDepth = max(1, min((int) $request->get('depth', 5), 10)); // จำกัดสูงสุด 10 ชั้น

        try {
            $treeData = $this->genealogyService->getTreeData($member, $treeType, $maxDepth);

            return response()->json([
                'success' => true,
                'data' => $this->stripPrivateNodeData($treeData),
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
     * แสดงหน้าตำแหน่งใน Binary Tree ของสมาชิกที่ล็อกอิน
     *
     * 🐛 หน้าเดิมอ่าน $position['left_count'/'left_pv'/...] ซึ่ง getMemberPosition() ไม่เคยส่งมา
     *    → ทุกช่องเป็น 0 เสมอ ตอนนี้อ่านยอดจริงจาก mlm_members + รวมคอมมิชชั่นรายขาจาก mlm_commissions
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function binaryPositionPage()
    {
        $user = Auth::user();
        $member = $user->mlmMembers()->with('plan')->first();

        if (! $member) {
            return redirect()->route('user.dashboard')
                ->with('info', 'คุณยังไม่ได้เข้าร่วมแผน MLM');
        }

        if (! in_array($member->plan?->type, ['binary', 'hybrid'], true)) {
            return redirect()->route('user.mlm.dashboard')
                ->with('info', 'แผนของคุณไม่รองรับโครงสร้าง Binary');
        }

        $position = $this->genealogyService->getMemberPosition($member, 'binary');

        // คอมมิชชั่นรายขา (ไม่นับรายการที่ถูกปฏิเสธ/ยกเลิก/เรียกคืน)
        $legCommission = $member->commissions()
            ->whereNotIn('status', self::VOID_STATUSES)
            ->whereIn('leg', ['left', 'right'])
            ->selectRaw('leg, SUM(commission_amount) as total')
            ->groupBy('leg')
            ->pluck('total', 'leg');

        $binaryEarned = (float) $member->commissions()
            ->whereNotIn('status', self::VOID_STATUSES)
            ->whereIn('type', ['binary_pair', 'binary_matching'])
            ->sum('commission_amount');

        $legs = [
            'left' => [
                'members' => (int) $member->left_leg_members,
                'pv' => (float) $member->left_leg_pv,
                'carried_pv' => (float) $member->carried_left_pv,
                'sales' => (float) $member->left_leg_sales,
                'commission' => (float) ($legCommission['left'] ?? 0),
                'child' => $position['left_child'] ?? null,
            ],
            'right' => [
                'members' => (int) $member->right_leg_members,
                'pv' => (float) $member->right_leg_pv,
                'carried_pv' => (float) $member->carried_right_pv,
                'sales' => (float) $member->right_leg_sales,
                'commission' => (float) ($legCommission['right'] ?? 0),
                'child' => $position['right_child'] ?? null,
            ],
        ];

        $binarySettings = [
            'pair_commission' => (float) MlmGlobalSetting::get('binary_pair_commission', 100),
            'pairing_type' => (string) MlmGlobalSetting::get('binary_pairing_type', '1:1'),
        ];

        $referralUrl = $this->referralUrlFor($member);

        return view('user.mlm.binary-position', compact('member', 'position', 'legs', 'binaryEarned', 'binarySettings', 'referralUrl'));
    }

    /**
     * ลิงก์สมัครที่ผูกผู้แนะนำ — ใช้หน้าสมัครแบบบังคับเพิ่มเพื่อน LINE OA ก่อน
     * (Register/LineRegistration อ่าน ?ref= เป็น member_code เพื่อผูกผู้แนะนำ)
     * ทุกหน้าใช้ฟังก์ชันนี้ชุดเดียว — เดิมแดชบอร์ด/ผังสายงานใช้ /register แต่หน้าลิงก์แนะนำใช้ LINE
     */
    private function referralUrlFor(MlmMember $member): string
    {
        foreach (['line.registration.register', 'register'] as $name) {
            if (Route::has($name)) {
                return route($name, ['ref' => $member->member_code]);
            }
        }

        return url('/register?ref='.urlencode($member->member_code));
    }

    /**
     * ยศปัจจุบัน/ถัดไป + ความคืบหน้าคะแนน (สูตรเดียวกับการ์ดยศในแดชบอร์ดสมาชิก)
     *
     * @return array{current: ?Rank, next: ?Rank, percent: float, points: int, needed: int}
     */
    private function rankInfo($user): array
    {
        $current = $user->currentRank;
        $points = (int) ($user->rank_points ?? 0);

        $next = Rank::where('is_active', true)
            ->when($current, fn ($q) => $q->where('level', '>', $current->level))
            ->orderBy('level')
            ->first();

        $percent = 0.0;
        $needed = 0;
        if ($next) {
            $from = $current ? (int) $current->min_points : 0;
            $span = (int) $next->min_points - $from;
            $percent = $span > 0 ? min(100, max(0, ($points - $from) / $span * 100)) : 0;
            $needed = max(0, (int) $next->min_points - $points);
        }

        return ['current' => $current, 'next' => $next, 'percent' => round($percent, 1), 'points' => $points, 'needed' => $needed];
    }

    /**
     * ตัดข้อมูลส่วนตัวของสมาชิกคนอื่นออกจากผังสายงานก่อนส่งให้เบราว์เซอร์
     * - อีเมล: ไม่ส่งของใครเลย (ผังเป็นของสมาชิกทั่วไป ไม่ใช่แอดมิน)
     * - รายได้สะสม (total_earnings): ส่งเฉพาะของตัวเอง (โหนดราก)
     *
     * @param  mixed  $node
     * @return mixed
     */
    private function stripPrivateNodeData($node, bool $isRoot = true)
    {
        if (! is_array($node)) {
            return $node;
        }

        unset($node['email'], $node['phone']);
        // รูปสำรองจาก ui-avatars.com (User::profile_picture_url เมื่อไม่มีรูปจริง) ส่งอักษรแรกของชื่อไปบริการภายนอก
        // ตัดทิ้งให้หน้าแสดงอักษรย่อเอง
        if (isset($node['avatar_url']) && is_string($node['avatar_url'])
            && str_starts_with($node['avatar_url'], 'https://ui-avatars.com/')) {
            $node['avatar_url'] = null;
        }
        if (! $isRoot) {
            unset($node['total_earnings']);
        }

        foreach (['children', 'left', 'right'] as $key) {
            if (! array_key_exists($key, $node) || ! is_array($node[$key])) {
                continue;
            }
            if ($key === 'children') {
                $node[$key] = array_map(fn ($child) => $this->stripPrivateNodeData($child, false), $node[$key]);
            } else {
                $node[$key] = $this->stripPrivateNodeData($node[$key], false);
            }
        }

        return $node;
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
            // ไม่ส่งข้อความ exception ให้เบราว์เซอร์ (อาจมีชื่อตาราง/คิวรี) — เก็บไว้ใน log แทน
            \Log::error('MLM settings load failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถโหลดการตั้งค่าได้',
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

            // คำนวณ PV = ยอดขาย × global_pv_rate (สูตรเดียวกับ MlmPvService/MlmCalculationService ที่คิด PV จริง
            // และหน้าจำลองฝั่งแอดมิน) — เดิมหารด้วยอัตรา ทำให้ผลจำลองกลับทิศเมื่ออัตราไม่ใช่ 1
            $totalPv = $salesAmount * max(0.0, (float) $pvRate);

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
            \Log::error('MLM preview calculation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการคำนวณ',
            ], 500);
        }
    }
}
