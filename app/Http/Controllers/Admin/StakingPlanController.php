<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use App\Models\Rank;
use App\Models\StakingCoinSetting;
use App\Models\StakingPosition;
use Illuminate\Http\Request;

/**
 * Admin Staking Plan Controller
 *
 * จัดการแผนการลงทุน (Staking Plans) สำหรับ Admin
 * รวมถึงการเปิด/ปิดแผน, ตั้งค่าอัตราแลกเปลี่ยน Coin, และดูสถิติ
 *
 * @version 3.296.0
 *
 * @since 2025-12-02
 */
class StakingPlanController extends Controller
{
    /**
     * แสดงหน้า Dashboard จัดการแผนการลงทุน
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงแผนการลงทุนทั้งหมด
        $plans = InvestmentPlan::withCount([
            'stakingPositions as active_positions' => function ($query) {
                $query->whereIn('status', ['active', 'locked']);
            },
            'stakingPositions as total_positions',
        ])
            ->withSum(['stakingPositions as total_invested' => function ($query) {
                $query->whereIn('status', ['active', 'locked', 'matured']);
            }], 'amount')
            ->orderBy('sort_order')
            ->get();

        // สถิติรวม
        $stats = [
            'total_plans' => InvestmentPlan::count(),
            'active_plans' => InvestmentPlan::where('is_active', true)
                ->where(function ($q) {
                    $q->where('is_paused', false)->orWhereNull('is_paused');
                })->count(),
            'paused_plans' => InvestmentPlan::where('is_paused', true)->count(),
            'total_invested' => StakingPosition::whereIn('status', ['active', 'locked', 'matured'])->sum('amount'),
            'total_positions' => StakingPosition::whereIn('status', ['active', 'locked'])->count(),
            'total_roi_paid' => StakingPosition::sum('earned_roi'),
            'coin_invested' => StakingPosition::whereNotNull('coin_amount')->sum('coin_amount'),
        ];

        // ตั้งค่า Coin
        $coinSettings = StakingCoinSetting::active();

        return view('admin.staking-plans.index', compact('plans', 'stats', 'coinSettings'));
    }

    /**
     * แสดงฟอร์มสร้างแผนใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $ranks = Rank::orderBy('level')->get();
        $coinSettings = StakingCoinSetting::active();

        return view('admin.staking-plans.create', compact('ranks', 'coinSettings'));
    }

    /**
     * บันทึกแผนใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'roi_rate' => 'required|numeric|min:0|max:100',
            'roi_frequency' => 'required|in:daily,weekly,monthly',
            'term_days' => 'required|integer|min:1',
            'lock_days' => 'required|integer|min:0',
            'allow_compound' => 'boolean',
            'allow_early_withdrawal' => 'boolean',
            'early_withdrawal_penalty' => 'nullable|numeric|min:0|max:100',
            'referral_bonus_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'allow_coin_investment' => 'boolean',
            'coin_to_money_rate' => 'nullable|numeric|min:0',
            'min_coin_amount' => 'nullable|numeric|min:0',
            'max_coin_amount' => 'nullable|numeric|min:0',
            'max_investors' => 'nullable|integer|min:1',
            'max_positions_per_user' => 'nullable|integer|min:1',
            'required_rank_id' => 'nullable|exists:ranks,id',
            'requires_kyc' => 'boolean',
            'risk_level' => 'required|in:low,medium,high,very_high',
            'risk_warning' => 'nullable|string',
            'open_at' => 'nullable|date',
            'close_at' => 'nullable|date|after:open_at',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            $plan = InvestmentPlan::create($validated);

            return redirect()
                ->route('admin.staking-plans.index')
                ->with('success', "สร้างแผนการลงทุน \"{$plan->display_name}\" สำเร็จ");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดแผน
     *
     * @return \Illuminate\View\View
     */
    public function show(InvestmentPlan $stakingPlan)
    {
        $stakingPlan->loadCount([
            'stakingPositions as active_positions' => function ($query) {
                $query->whereIn('status', ['active', 'locked']);
            },
            'stakingPositions as total_positions',
        ]);

        // ดึง positions ล่าสุด
        $recentPositions = $stakingPlan->stakingPositions()
            ->with('user:id,name,email')
            ->latest('invested_at')
            ->take(20)
            ->get();

        // สถิติของแผนนี้
        $stats = [
            'total_invested' => $stakingPlan->stakingPositions()
                ->whereIn('status', ['active', 'locked', 'matured'])
                ->sum('amount'),
            'total_roi_paid' => $stakingPlan->stakingPositions()->sum('earned_roi'),
            'coin_invested' => $stakingPlan->stakingPositions()
                ->whereNotNull('coin_amount')
                ->sum('coin_amount'),
            'unique_investors' => $stakingPlan->stakingPositions()
                ->distinct('user_id')
                ->count('user_id'),
        ];

        return view('admin.staking-plans.show', compact('stakingPlan', 'recentPositions', 'stats'));
    }

    /**
     * แสดงฟอร์มแก้ไขแผน
     *
     * @return \Illuminate\View\View
     */
    public function edit(InvestmentPlan $stakingPlan)
    {
        $ranks = Rank::orderBy('level')->get();
        $coinSettings = StakingCoinSetting::active();

        return view('admin.staking-plans.edit', compact('stakingPlan', 'ranks', 'coinSettings'));
    }

    /**
     * อัพเดทแผน
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, InvestmentPlan $stakingPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'roi_rate' => 'required|numeric|min:0|max:100',
            'roi_frequency' => 'required|in:daily,weekly,monthly',
            'term_days' => 'required|integer|min:1',
            'lock_days' => 'required|integer|min:0',
            'allow_compound' => 'boolean',
            'allow_early_withdrawal' => 'boolean',
            'early_withdrawal_penalty' => 'nullable|numeric|min:0|max:100',
            'referral_bonus_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'allow_coin_investment' => 'boolean',
            'coin_to_money_rate' => 'nullable|numeric|min:0',
            'min_coin_amount' => 'nullable|numeric|min:0',
            'max_coin_amount' => 'nullable|numeric|min:0',
            'max_investors' => 'nullable|integer|min:1',
            'max_positions_per_user' => 'nullable|integer|min:1',
            'required_rank_id' => 'nullable|exists:ranks,id',
            'requires_kyc' => 'boolean',
            'risk_level' => 'required|in:low,medium,high,very_high',
            'risk_warning' => 'nullable|string',
            'open_at' => 'nullable|date',
            'close_at' => 'nullable|date|after:open_at',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            $stakingPlan->update($validated);

            return redirect()
                ->route('admin.staking-plans.index')
                ->with('success', "อัพเดทแผนการลงทุน \"{$stakingPlan->display_name}\" สำเร็จ");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * หยุดรับการลงทุนชั่วคราว (Pause)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pause(Request $request, InvestmentPlan $stakingPlan)
    {
        $reason = $request->input('reason', 'หยุดรับการลงทุนชั่วคราว');

        $stakingPlan->pause($reason);

        return back()->with('success', "หยุดรับการลงทุนแผน \"{$stakingPlan->display_name}\" ชั่วคราว");
    }

    /**
     * เปิดรับการลงทุนอีกครั้ง (Resume)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resume(InvestmentPlan $stakingPlan)
    {
        $stakingPlan->resume();

        return back()->with('success', "เปิดรับการลงทุนแผน \"{$stakingPlan->display_name}\" อีกครั้ง");
    }

    /**
     * สลับสถานะ Active
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(InvestmentPlan $stakingPlan)
    {
        $stakingPlan->update([
            'is_active' => ! $stakingPlan->is_active,
        ]);

        $status = $stakingPlan->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return back()->with('success', "{$status}แผน \"{$stakingPlan->display_name}\" แล้ว");
    }

    /**
     * หน้าตั้งค่า Coin Settings
     *
     * @return \Illuminate\View\View
     */
    public function coinSettings()
    {
        $settings = StakingCoinSetting::active();

        return view('admin.staking-plans.coin-settings', compact('settings'));
    }

    /**
     * อัพเดทตั้งค่า Coin
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCoinSettings(Request $request)
    {
        $validated = $request->validate([
            'coin_to_thb_rate' => 'required|numeric|min:0.0001',
            'allow_coin_staking' => 'boolean',
            'allow_mixed_payment' => 'boolean',
            'min_coin_percentage' => 'required|numeric|min:0|max:100',
            'max_coin_percentage' => 'required|numeric|min:0|max:100',
            'require_risk_acknowledgment' => 'boolean',
            'default_risk_warning' => 'nullable|string',
        ]);

        try {
            $settings = StakingCoinSetting::active();
            $settings->updateSettings($validated, auth()->id());

            return back()->with('success', 'อัพเดทตั้งค่า Coin สำเร็จ');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * หน้ารายงาน Positions
     *
     * @return \Illuminate\View\View
     */
    public function positions(Request $request)
    {
        $query = StakingPosition::with(['user:id,name,email', 'investmentPlan:id,name,name_th']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามแผน
        if ($request->filled('plan_id')) {
            $query->where('investment_plan_id', $request->plan_id);
        }

        // กรองตามวิธีการชำระ
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('position_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $positions = $query->latest('invested_at')->paginate(20);

        $plans = InvestmentPlan::orderBy('sort_order')->get(['id', 'name', 'name_th']);

        return view('admin.staking-plans.positions', compact('positions', 'plans'));
    }

    /**
     * ลบแผน (Soft Delete)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(InvestmentPlan $stakingPlan)
    {
        // ตรวจสอบว่ายังมี positions อยู่หรือไม่
        $activePositions = $stakingPlan->stakingPositions()
            ->whereIn('status', ['pending', 'active', 'locked'])
            ->count();

        if ($activePositions > 0) {
            return back()->with('error', "ไม่สามารถลบได้ เพราะยังมีการลงทุนที่ใช้งานอยู่ {$activePositions} รายการ");
        }

        $stakingPlan->delete();

        return redirect()
            ->route('admin.staking-plans.index')
            ->with('success', "ลบแผน \"{$stakingPlan->display_name}\" สำเร็จ");
    }
}
