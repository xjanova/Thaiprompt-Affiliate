<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\RankRequirement;
use App\Models\RankBonus;
use App\Models\RankPromotion;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Http\Request;

class RankController extends Controller
{
    protected $rankingService;

    public function __construct(RankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }

    /**
     * แสดงรายการ ranks ทั้งหมด
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนดูรายการ
     */
    public function index()
    {
        // ✅ ตรวจสอบสิทธิ์ในการดูรายการ
        $this->authorize('viewAny', Rank::class);

        $ranks = Rank::withCount(['users', 'requirements', 'bonuses'])
            ->byLevel()
            ->get();

        return view('admin.ranks.index', compact('ranks'));
    }

    /**
     * แสดงฟอร์มสร้าง rank ใหม่
     *
     * ⚠️ CRITICAL: การสร้าง rank มีผลต่อระบบ commission
     */
    public function create()
    {
        // ✅ ตรวจสอบสิทธิ์ในการสร้าง
        $this->authorize('create', Rank::class);

        return view('admin.ranks.create');
    }

    /**
     * บันทึก rank ใหม่
     *
     * ⚠️ CRITICAL: ป้องกันการสร้าง rank โดยไม่ได้รับอนุญาต
     */
    public function store(Request $request)
    {
        // ✅ ตรวจสอบสิทธิ์ในการสร้าง
        $this->authorize('create', Rank::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'level' => 'required|integer|unique:ranks',
            'icon' => 'nullable|string|max:255',
            'color' => 'required|string',
            'badge_icon' => 'nullable|string|max:10',
            'stars' => 'required|integer|min:1|max:10',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'bonus_multiplier' => 'required|numeric|min:1',
            'min_points' => 'required|integer|min:0',
            'min_referrals' => 'required|integer|min:0',
            'min_sales' => 'required|numeric|min:0',
        ]);

        $rank = Rank::create($validated);

        return redirect()->route('admin.ranks.edit', $rank)
            ->with('success', 'Rank created successfully!');
    }

    /**
     * แสดงฟอร์มแก้ไข rank
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนแก้ไข
     */
    public function edit(Rank $rank)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนแก้ไข
        $this->authorize('update', $rank);

        $rank->load(['requirements', 'bonuses']);
        return view('admin.ranks.edit', compact('rank'));
    }

    /**
     * อัพเดท rank
     *
     * ⚠️ CRITICAL: การแก้ไข commission_rate และ bonus_multiplier
     * มีผลกระทบโดยตรงต่อรายได้ของ user ทุกคน
     */
    public function update(Request $request, Rank $rank)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนอัพเดท
        $this->authorize('update', $rank);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'level' => 'required|integer|unique:ranks,level,' . $rank->id,
            'icon' => 'nullable|string|max:255',
            'color' => 'required|string',
            'badge_icon' => 'nullable|string|max:10',
            'stars' => 'required|integer|min:1|max:10',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'bonus_multiplier' => 'required|numeric|min:1',
            'min_points' => 'required|integer|min:0',
            'min_referrals' => 'required|integer|min:0',
            'min_sales' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $rank->update($validated);

        return redirect()->route('admin.ranks.index')
            ->with('success', 'Rank updated successfully!');
    }

    /**
     * ลบ rank
     *
     * ⚠️ CRITICAL: ลบ rank ได้เฉพาะเมื่อไม่มี user ใช้งาน
     */
    public function destroy(Rank $rank)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนลบ
        $this->authorize('delete', $rank);

        if ($rank->users()->count() > 0) {
            return back()->with('error', 'Cannot delete rank with assigned users');
        }

        $rank->delete();

        return redirect()->route('admin.ranks.index')
            ->with('success', 'Rank deleted successfully!');
    }

    /**
     * แสดงรายการ rank promotions
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนดูรายการ
     */
    public function promotions()
    {
        // ✅ ตรวจสอบสิทธิ์ในการดูรายการ
        $this->authorize('viewAny', RankPromotion::class);

        $promotions = RankPromotion::with(['user', 'fromRank', 'toRank'])
            ->latest()
            ->paginate(20);

        return view('admin.ranks.promotions', compact('promotions'));
    }

    /**
     * อนุมัติการเลื่อนยศ
     *
     * ⚠️ CRITICAL: การอนุมัติการเลื่อนยศมีผลกระทบสูงมาก
     * - เปลี่ยน commission rate
     * - เปลี่ยน bonus multiplier
     * - มีผลต่อ downline ทั้งหมด
     */
    public function approvePromotion(RankPromotion $promotion)
    {
        // ✅ ตรวจสอบสิทธิ์ในการอนุมัติ
        $this->authorize('approve', $promotion);

        $promotion->approve(auth()->user());

        return back()->with('success', 'Promotion approved successfully!');
    }

    /**
     * ปฏิเสธการเลื่อนยศ
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ก่อนปฏิเสธ
     */
    public function rejectPromotion(Request $request, RankPromotion $promotion)
    {
        // ✅ ตรวจสอบสิทธิ์ในการปฏิเสธ
        $this->authorize('reject', $promotion);

        $promotion->reject($request->input('reason'));

        return back()->with('success', 'Promotion rejected');
    }
}
