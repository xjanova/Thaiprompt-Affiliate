<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Seller\SellerApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 🏪 สมัครเปิดร้านค้า สำหรับสมาชิกทั่วไป (audit SELLER-07)
 *
 * เดิมทุกหน้า /seller/* (รวม onboarding) ต้องมี role seller อยู่ก่อน แต่ไม่มีทางไหนให้ได้ role นี้
 * นอกจากแอดมินแก้มือ → ตอนนี้:
 *   1. สมาชิกกรอกคำขอที่ /user/seller-apply → สร้าง VendorStore status=pending, is_active=false
 *   2. แอดมินอนุมัติที่ /admin/seller-applications → ร้าน active + ผู้ใช้กลายเป็น role seller
 *   3. ผู้ขายเข้า /seller/onboarding ต่อ (KYC → เลือกแพ็กเกจ/ใช้แพ็กเกจฟรี)
 *
 * คำขอที่ถูกปฏิเสธ = ร้าน status=closed + suspension_reason เก็บเหตุผล → แก้ไขแล้วยื่นใหม่ได้
 *
 * (2026-09-26) ตรรกะย้ายไป SellerApplicationService — แอป (/api/v1/seller/application) ใช้ชุดเดียวกัน
 */
class SellerApplicationController extends Controller
{
    /**
     * role ที่สมัครเปิดร้านเองได้ (คงชื่อเดิมไว้ให้โค้ดที่อ้างถึง — ค่าจริงอยู่ที่ service)
     */
    public const APPLICABLE_ROLES = SellerApplicationService::APPLICABLE_ROLES;

    public function __construct(private readonly SellerApplicationService $applications) {}

    /**
     * หน้าแบบฟอร์ม/สถานะคำขอเปิดร้าน
     *
     * GET /user/seller-apply  (route: user.seller-apply.index)
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $store = $this->applications->latestStore($user);

        return view('user.seller-apply.index', [
            'user' => $user,
            'store' => $store,
            'state' => $this->applications->stateFor($user, $store),
            'kycApproved' => $user->kyc_status === 'approved',
        ]);
    }

    /**
     * ยื่นคำขอเปิดร้าน (หรือยื่นใหม่หลังถูกปฏิเสธ)
     *
     * POST /user/seller-apply  (route: user.seller-apply.store)
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate($this->applications->rules(), $this->applications->messages());

        try {
            $result = $this->applications->submit($user, $validated);
        } catch (\Throwable $e) {
            Log::error('Seller application submit failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'ส่งคำขอไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        if (! $result['ok']) {
            return redirect()->route('user.seller-apply.index')
                ->with('info', $this->applications->stateMessage($result['state']));
        }

        return redirect()->route('user.seller-apply.index')
            ->with('success', $result['resubmitted']
                ? 'ส่งคำขอเปิดร้านใหม่เรียบร้อย ทีมงานจะตรวจสอบโดยเร็ว'
                : 'ส่งคำขอเปิดร้านเรียบร้อย ทีมงานจะตรวจสอบและแจ้งผลทางการแจ้งเตือน');
    }
}
