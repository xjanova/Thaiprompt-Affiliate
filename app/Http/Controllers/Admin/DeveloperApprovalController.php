<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeveloperProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DeveloperApprovalController
 *
 * จัดการการอนุมัตินักพัฒนาโดยแอดมิน
 */
class DeveloperApprovalController extends Controller
{
    /**
     * แสดงรายการนักพัฒนาทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = DeveloperProfile::with(['user', 'approver'])
            ->latest();

        // กรองตามสถานะ
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $developers = $query->paginate(20);

        // สถิติ
        $stats = [
            'total' => DeveloperProfile::count(),
            'pending' => DeveloperProfile::where('status', DeveloperProfile::STATUS_PENDING)->count(),
            'approved' => DeveloperProfile::where('status', DeveloperProfile::STATUS_APPROVED)->count(),
            'rejected' => DeveloperProfile::where('status', DeveloperProfile::STATUS_REJECTED)->count(),
            'suspended' => DeveloperProfile::where('status', DeveloperProfile::STATUS_SUSPENDED)->count(),
        ];

        return view('admin.developers.index', [
            'pageTitle' => 'จัดการนักพัฒนา',
            'developers' => $developers,
            'stats' => $stats,
            'currentStatus' => $status,
        ]);
    }

    /**
     * แสดงรายละเอียดนักพัฒนา
     *
     * @param DeveloperProfile $developer
     * @return \Illuminate\View\View
     */
    public function show(DeveloperProfile $developer)
    {
        $developer->load(['user', 'approver', 'products']);

        return view('admin.developers.show', [
            'pageTitle' => 'รายละเอียดนักพัฒนา: ' . $developer->display_name,
            'developer' => $developer,
        ]);
    }

    /**
     * อนุมัตินักพัฒนา
     *
     * @param DeveloperProfile $developer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(DeveloperProfile $developer)
    {
        if ($developer->isApproved()) {
            return back()->with('error', 'นักพัฒนารายนี้ได้รับการอนุมัติแล้ว');
        }

        try {
            DB::beginTransaction();

            $developer->update([
                'status' => DeveloperProfile::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'is_verified' => true,
                'rejection_reason' => null,
            ]);

            DB::commit();

            // Log activity
            activity()
                ->performedOn($developer)
                ->causedBy(Auth::user())
                ->log('อนุมัตินักพัฒนา');

            // TODO: ส่งอีเมลแจ้งการอนุมัติ

            return back()->with('success', 'อนุมัตินักพัฒนาสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ปฏิเสธนักพัฒนา
     *
     * @param Request $request
     * @param DeveloperProfile $developer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, DeveloperProfile $developer)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ], [
            'rejection_reason.required' => 'กรุณาระบุเหตุผลที่ปฏิเสธ',
        ]);

        try {
            DB::beginTransaction();

            $developer->update([
                'status' => DeveloperProfile::STATUS_REJECTED,
                'rejection_reason' => $validated['rejection_reason'],
                'approved_at' => null,
                'approved_by' => null,
                'is_verified' => false,
            ]);

            DB::commit();

            // Log activity
            activity()
                ->performedOn($developer)
                ->causedBy(Auth::user())
                ->withProperties(['reason' => $validated['rejection_reason']])
                ->log('ปฏิเสธนักพัฒนา');

            // TODO: ส่งอีเมลแจ้งการปฏิเสธ

            return back()->with('success', 'ปฏิเสธนักพัฒนาสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ระงับการใช้งานนักพัฒนา
     *
     * @param Request $request
     * @param DeveloperProfile $developer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function suspend(Request $request, DeveloperProfile $developer)
    {
        $validated = $request->validate([
            'suspension_reason' => 'required|string|max:1000',
        ], [
            'suspension_reason.required' => 'กรุณาระบุเหตุผลที่ระงับ',
        ]);

        try {
            DB::beginTransaction();

            $developer->update([
                'status' => DeveloperProfile::STATUS_SUSPENDED,
                'is_active' => false,
                'rejection_reason' => $validated['suspension_reason'],
            ]);

            DB::commit();

            // Log activity
            activity()
                ->performedOn($developer)
                ->causedBy(Auth::user())
                ->withProperties(['reason' => $validated['suspension_reason']])
                ->log('ระงับนักพัฒนา');

            // TODO: ส่งอีเมลแจ้งการระงับ

            return back()->with('success', 'ระงับนักพัฒนาสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ยกเลิกการระงับ
     *
     * @param DeveloperProfile $developer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unsuspend(DeveloperProfile $developer)
    {
        if (!$developer->isSuspended()) {
            return back()->with('error', 'นักพัฒนารายนี้ไม่ได้ถูกระงับ');
        }

        try {
            DB::beginTransaction();

            $developer->update([
                'status' => DeveloperProfile::STATUS_APPROVED,
                'is_active' => true,
                'rejection_reason' => null,
            ]);

            DB::commit();

            // Log activity
            activity()
                ->performedOn($developer)
                ->causedBy(Auth::user())
                ->log('ยกเลิกการระงับนักพัฒนา');

            return back()->with('success', 'ยกเลิกการระงับสำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * อัพเดทค่าคอมมิชชั่น
     *
     * @param Request $request
     * @param DeveloperProfile $developer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCommission(Request $request, DeveloperProfile $developer)
    {
        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ], [
            'commission_rate.required' => 'กรุณาระบุเปอร์เซ็นต์ค่าคอมมิชชั่น',
            'commission_rate.min' => 'ค่าคอมมิชชั่นต้องไม่ต่ำกว่า 0%',
            'commission_rate.max' => 'ค่าคอมมิชชั่นต้องไม่เกิน 100%',
        ]);

        try {
            $oldRate = $developer->commission_rate;

            $developer->update([
                'commission_rate' => $validated['commission_rate'],
            ]);

            // Log activity
            activity()
                ->performedOn($developer)
                ->causedBy(Auth::user())
                ->withProperties([
                    'old_rate' => $oldRate,
                    'new_rate' => $validated['commission_rate'],
                ])
                ->log('อัพเดทค่าคอมมิชชั่น');

            return back()->with('success', 'อัพเดทค่าคอมมิชชั่นสำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบนักพัฒนา (soft delete)
     *
     * @param DeveloperProfile $developer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DeveloperProfile $developer)
    {
        try {
            // ตรวจสอบว่ามีสินค้าที่กำลังขายอยู่หรือไม่
            $activeProducts = $developer->products()->where('status', 'active')->count();
            if ($activeProducts > 0) {
                return back()->with('error', 'ไม่สามารถลบนักพัฒนาที่มีสินค้ากำลังขายอยู่ได้');
            }

            $developer->delete();

            // Log activity
            activity()
                ->performedOn($developer)
                ->causedBy(Auth::user())
                ->log('ลบนักพัฒนา');

            return redirect()
                ->route('admin.developers.index')
                ->with('success', 'ลบนักพัฒนาสำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
