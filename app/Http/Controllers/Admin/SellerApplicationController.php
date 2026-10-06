<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorStore;
use App\Services\Seller\SellerApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🏪 คิวอนุมัติคำขอเปิดร้านค้า (audit SELLER-07)
 *
 * คำขอ = VendorStore status=pending (สร้างจาก /user/seller-apply)
 * อนุมัติ → ร้าน active + ผู้ใช้เป็น role seller + ใช้แพ็กเกจฟรี (ถ้ามี) เพื่อให้ผ่าน
 *          EnsureHasVendorStore ได้ทันที ผู้ขายเปลี่ยนแพ็กเกจเองภายหลังได้ที่ onboarding
 * ปฏิเสธ → ร้าน status=closed + เก็บเหตุผลไว้ใน suspension_reason (ผู้ใช้แก้แล้วยื่นใหม่ได้)
 *
 * ตรรกะอนุมัติ/ปฏิเสธอยู่ที่ SellerApplicationService (ใช้ร่วมกับแอปแอดมิน /api/admin/approvals/seller-applications)
 */
class SellerApplicationController extends Controller
{
    public function __construct(private readonly SellerApplicationService $applications) {}

    /**
     * รายการคำขอที่รออนุมัติ
     *
     * GET admin/seller-applications (route: admin.seller-applications.index)
     */
    public function index(Request $request)
    {
        $applications = VendorStore::with(['user' => fn ($q) => $q->withTrashed()])
            ->where('status', 'pending')
            ->orderBy('updated_at')
            ->paginate(20)
            ->withQueryString();

        $recent = VendorStore::with(['user' => fn ($q) => $q->withTrashed()])
            ->whereIn('status', ['active', 'closed'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->latest('updated_at')
            ->limit(10)
            ->get();

        $stats = [
            'pending' => VendorStore::where('status', 'pending')->count(),
            'active' => VendorStore::where('status', 'active')->count(),
        ];

        return view('admin.seller-applications.index', compact('applications', 'recent', 'stats'));
    }

    /**
     * อนุมัติคำขอเปิดร้าน
     *
     * POST admin/seller-applications/{store}/approve (route: admin.seller-applications.approve)
     */
    public function approve(VendorStore $store)
    {
        try {
            $result = $this->applications->approve($store, auth()->user());
        } catch (\Throwable $e) {
            Log::error('Approve seller application failed', ['store_id' => $store->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'อนุมัติไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * ปฏิเสธคำขอเปิดร้าน
     *
     * POST admin/seller-applications/{store}/reject  body: reason (บังคับ)
     */
    public function reject(Request $request, VendorStore $store)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่ปฏิเสธ (ผู้สมัครจะเห็นข้อความนี้)',
            'reason.max' => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ]);

        $result = $this->applications->reject($store, $validated['reason'], auth()->user());

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
