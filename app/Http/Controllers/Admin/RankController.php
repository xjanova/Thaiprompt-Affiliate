<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\RankBonus;
use App\Models\RankPromotion;
use App\Models\RankRequirement;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'level' => 'required|integer|unique:ranks,level,'.$rank->id,
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

    /**
     * แสดงหน้าจัดการกรอบอวาต้าร์
     *
     * จัดการกรอบ Avatar สำหรับแต่ละ Rank
     */
    public function avatarFrames()
    {
        $ranks = Rank::byLevel()->get();

        return view('admin.ranks.avatar-frames', compact('ranks'));
    }

    /**
     * อัพโหลดกรอบอวาต้าร์สำหรับ Rank
     *
     * รองรับ PNG, SVG (transparent background)
     */
    public function uploadAvatarFrame(Request $request, Rank $rank)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนอัพเดท
        $this->authorize('update', $rank);

        $validated = $request->validate([
            'avatar_frame' => 'required|file|mimes:png,svg,webp|max:2048',
            'frame_animation' => 'nullable|in:none,pulse,glow,sparkle,rotate',
        ]);

        // ลบไฟล์เก่าถ้ามี
        if ($rank->avatar_frame) {
            Storage::disk('public')->delete($rank->avatar_frame);
        }

        // อัพโหลดไฟล์ใหม่
        $path = $request->file('avatar_frame')->store('rank-frames', 'public');

        // อัพเดท rank
        $rank->update([
            'avatar_frame' => $path,
            'frame_animation' => $request->input('frame_animation', 'none'),
        ]);

        return back()->with('success', 'อัพโหลดกรอบอวาต้าร์สำเร็จ!');
    }

    /**
     * ลบกรอบอวาต้าร์
     */
    public function deleteAvatarFrame(Rank $rank)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนลบ
        $this->authorize('update', $rank);

        // ลบไฟล์
        if ($rank->avatar_frame) {
            Storage::disk('public')->delete($rank->avatar_frame);
        }

        // อัพเดท rank
        $rank->update([
            'avatar_frame' => null,
            'frame_animation' => null,
        ]);

        return back()->with('success', 'ลบกรอบอวาต้าร์สำเร็จ!');
    }

    /**
     * อัพเดท animation ของกรอบ
     */
    public function updateFrameAnimation(Request $request, Rank $rank)
    {
        // ✅ ตรวจสอบสิทธิ์ก่อนอัพเดท
        $this->authorize('update', $rank);

        $validated = $request->validate([
            'frame_animation' => 'required|in:none,pulse,glow,sparkle,rotate',
        ]);

        $rank->update([
            'frame_animation' => $validated['frame_animation'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดท animation สำเร็จ!',
        ]);
    }

    // ============================================
    // จัดการเงื่อนไขและรางวัล Rank
    // ============================================

    /**
     * แสดงหน้าตั้งค่าเงื่อนไขและรางวัลของ Rank
     *
     * แสดงรายการ Requirements และ Bonuses สำหรับ Rank ที่เลือก
     */
    public function settings(Rank $rank)
    {
        $this->authorize('update', $rank);

        $rank->load(['requirements', 'bonuses']);

        // ดึงรายการสิทธิพิเศษที่มีในระบบ
        $availablePrivileges = Rank::getAvailablePrivileges();

        // ดึง requirement types ที่รองรับ
        $requirementTypes = [
            'points' => ['name' => 'คะแนนสะสม', 'icon' => '🎯', 'unit' => 'คะแนน'],
            'referrals' => ['name' => 'จำนวนคนแนะนำโดยตรง', 'icon' => '👥', 'unit' => 'คน'],
            'sales' => ['name' => 'ยอดขายส่วนตัว', 'icon' => '💰', 'unit' => 'บาท'],
            'active_referrals' => ['name' => 'คนแนะนำที่ Active', 'icon' => '✅', 'unit' => 'คน'],
            'team_sales' => ['name' => 'ยอดขายทีม', 'icon' => '📊', 'unit' => 'บาท'],
            'consecutive_months' => ['name' => 'จำนวนเดือนต่อเนื่อง', 'icon' => '📅', 'unit' => 'เดือน'],
            'diamond_legs' => ['name' => 'ลูกทีมระดับ Diamond', 'icon' => '💎', 'unit' => 'คน'],
            'crown_legs' => ['name' => 'ลูกทีมระดับ Crown', 'icon' => '👑', 'unit' => 'คน'],
            'royal_legs' => ['name' => 'ลูกทีมระดับ Royal', 'icon' => '🏆', 'unit' => 'คน'],
        ];

        // ดึง bonus types ที่รองรับ
        $bonusTypes = [
            'one_time' => ['name' => 'โบนัสครั้งเดียว', 'icon' => '💰', 'description' => 'รับเมื่อเลื่อนระดับ'],
            'monthly' => ['name' => 'โบนัสรายเดือน', 'icon' => '📆', 'description' => 'รับทุกเดือน'],
            'commission' => ['name' => 'เพิ่มค่าคอมมิชชั่น', 'icon' => '📈', 'description' => 'เพิ่มเปอร์เซ็นต์คอมฯ'],
            'multiplier' => ['name' => 'ตัวคูณรายได้', 'icon' => '✖️', 'description' => 'คูณรายได้ทั้งหมด'],
        ];

        return view('admin.ranks.settings', compact(
            'rank',
            'availablePrivileges',
            'requirementTypes',
            'bonusTypes'
        ));
    }

    /**
     * สร้างเงื่อนไขใหม่สำหรับ Rank
     */
    public function storeRequirement(Request $request, Rank $rank)
    {
        $this->authorize('update', $rank);

        $validated = $request->validate([
            'requirement_type' => 'required|string|in:points,referrals,sales,active_referrals,team_sales,consecutive_months,diamond_legs,crown_legs,royal_legs',
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'target_value' => 'required|numeric|min:0',
            'operator' => 'required|in:>=,>,=,<=,<',
            'unit' => 'nullable|string|max:50',
            'is_required' => 'boolean',
            'weight' => 'integer|min:0',
        ]);

        $validated['rank_id'] = $rank->id;
        $validated['is_active'] = true;

        RankRequirement::create($validated);

        return back()->with('success', 'เพิ่มเงื่อนไขสำเร็จ!');
    }

    /**
     * อัพเดทเงื่อนไข
     */
    public function updateRequirement(Request $request, RankRequirement $requirement)
    {
        $this->authorize('update', $requirement->rank);

        $validated = $request->validate([
            'requirement_type' => 'required|string|in:points,referrals,sales,active_referrals,team_sales,consecutive_months,diamond_legs,crown_legs,royal_legs',
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'target_value' => 'required|numeric|min:0',
            'operator' => 'required|in:>=,>,=,<=,<',
            'unit' => 'nullable|string|max:50',
            'is_required' => 'boolean',
            'weight' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $requirement->update($validated);

        return back()->with('success', 'อัพเดทเงื่อนไขสำเร็จ!');
    }

    /**
     * ลบเงื่อนไข
     */
    public function deleteRequirement(RankRequirement $requirement)
    {
        $this->authorize('update', $requirement->rank);

        $requirement->delete();

        return back()->with('success', 'ลบเงื่อนไขสำเร็จ!');
    }

    /**
     * สร้างโบนัส/รางวัลใหม่สำหรับ Rank
     */
    public function storeBonus(Request $request, Rank $rank)
    {
        $this->authorize('update', $rank);

        $validated = $request->validate([
            'bonus_type' => 'required|string|in:one_time,monthly,commission,multiplier',
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'auto_apply' => 'boolean',
            'priority' => 'integer|min:0',
        ]);

        $validated['rank_id'] = $rank->id;
        $validated['is_active'] = true;

        RankBonus::create($validated);

        return back()->with('success', 'เพิ่มโบนัส/รางวัลสำเร็จ!');
    }

    /**
     * อัพเดทโบนัส/รางวัล
     */
    public function updateBonus(Request $request, RankBonus $bonus)
    {
        $this->authorize('update', $bonus->rank);

        $validated = $request->validate([
            'bonus_type' => 'required|string|in:one_time,monthly,commission,multiplier',
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'auto_apply' => 'boolean',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $bonus->update($validated);

        return back()->with('success', 'อัพเดทโบนัส/รางวัลสำเร็จ!');
    }

    /**
     * ลบโบนัส/รางวัล
     */
    public function deleteBonus(RankBonus $bonus)
    {
        $this->authorize('update', $bonus->rank);

        $bonus->delete();

        return back()->with('success', 'ลบโบนัส/รางวัลสำเร็จ!');
    }

    /**
     * อัพเดทสิทธิพิเศษของ Rank
     */
    public function updatePrivileges(Request $request, Rank $rank)
    {
        $this->authorize('update', $rank);

        $validated = $request->validate([
            'privileges' => 'nullable|array',
            'privileges.*' => 'string|in:'.implode(',', array_keys(Rank::getAvailablePrivileges())),
        ]);

        $rank->update([
            'privileges' => $validated['privileges'] ?? [],
        ]);

        return back()->with('success', 'อัพเดทสิทธิพิเศษสำเร็จ!');
    }
}
