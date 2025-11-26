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
}
