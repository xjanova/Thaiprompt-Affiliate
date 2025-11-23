<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MlmMember;
use App\Models\MlmTeamTransferRequest;
use App\Services\MlmTeamTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * TeamTransferController - User Dashboard
 *
 * จัดการคำขอย้ายทีมสำหรับสมาชิก
 */
class TeamTransferController extends Controller
{
    /**
     * MlmTeamTransferService instance
     *
     * @var MlmTeamTransferService
     */
    protected MlmTeamTransferService $transferService;

    /**
     * Constructor
     */
    public function __construct(MlmTeamTransferService $transferService)
    {
        $this->middleware('auth');
        $this->transferService = $transferService;
    }

    /**
     * แสดงรายการคำขอย้ายทีมของฉัน
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // ดึงคำขอทั้งหมดของ user นี้
        $requests = MlmTeamTransferRequest::where('user_id', $user->id)
            ->with(['oldSponsor.user', 'newSponsor.user', 'approver', 'rejecter'])
            ->latest()
            ->paginate(15);

        return view('user.team-transfer.index', [
            'requests' => $requests,
            'pageTitle' => 'คำขอย้ายทีมของฉัน',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างคำขอย้ายทีมใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $user = Auth::user();

        // ตรวจสอบว่ามี MLM member หรือไม่
        if (!$user->mlmMember) {
            return redirect()
                ->route('user.dashboard')
                ->with('error', 'คุณยังไม่ได้เป็นสมาชิก MLM');
        }

        // ตรวจสอบว่ามีคำขอที่รออนุมัติอยู่หรือไม่
        $pendingRequest = MlmTeamTransferRequest::where('user_id', $user->id)
            ->whereIn('status', [
                MlmTeamTransferRequest::STATUS_PENDING,
                MlmTeamTransferRequest::STATUS_APPROVED,
                MlmTeamTransferRequest::STATUS_PAID,
                MlmTeamTransferRequest::STATUS_PROCESSING,
            ])
            ->first();

        if ($pendingRequest) {
            return redirect()
                ->route('user.team-transfer.show', $pendingRequest)
                ->with('warning', 'คุณมีคำขอที่รออนุมัติอยู่แล้ว');
        }

        return view('user.team-transfer.create', [
            'pageTitle' => 'ขอย้ายทีม',
            'currentSponsor' => $user->mlmMember->unilevelSponsor,
            'transferFee' => 100.00,
        ]);
    }

    /**
     * บันทึกคำขอย้ายทีมใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate input
        $validated = $request->validate([
            'new_sponsor_code' => 'required|string|max:50',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // ค้นหาแม่ทีมใหม่จาก member_code
            $newSponsor = MlmMember::where('member_code', $validated['new_sponsor_code'])
                ->where('status', 'active')
                ->first();

            if (!$newSponsor) {
                return back()
                    ->withInput()
                    ->with('error', 'ไม่พบรหัสแม่ทีมที่ระบุ');
            }

            // สร้างคำขอย้ายทีม
            $transferRequest = $this->transferService->createTransferRequest(
                $user->mlmMember,
                $newSponsor,
                [
                    'reason' => $validated['reason'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            return redirect()
                ->route('user.team-transfer.show', $transferRequest)
                ->with('success', 'ส่งคำขอย้ายทีมเรียบร้อยแล้ว รอการอนุมัติจากแม่ทีมเก่า');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดคำขอย้ายทีม
     *
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\View\View
     */
    public function show(MlmTeamTransferRequest $teamTransfer)
    {
        // ตรวจสอบว่าเป็นเจ้าของคำขอหรือไม่
        if ($teamTransfer->user_id !== Auth::id()) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงคำขอนี้');
        }

        $teamTransfer->load([
            'oldSponsor.user',
            'newSponsor.user',
            'approver',
            'rejecter',
            'processor',
            'payment',
        ]);

        return view('user.team-transfer.show', [
            'request' => $teamTransfer,
            'pageTitle' => 'รายละเอียดคำขอย้ายทีม',
        ]);
    }

    /**
     * ชำระค่าธรรมเนียมการย้ายทีม
     *
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pay(MlmTeamTransferRequest $teamTransfer)
    {
        $user = Auth::user();

        // ตรวจสอบว่าเป็นเจ้าของคำขอหรือไม่
        if ($teamTransfer->user_id !== $user->id) {
            abort(403, 'คุณไม่มีสิทธิ์ดำเนินการกับคำขอนี้');
        }

        try {
            $this->transferService->payTransferFee($teamTransfer, $user);

            return redirect()
                ->route('user.team-transfer.show', $teamTransfer)
                ->with('success', 'ชำระค่าธรรมเนียมเรียบร้อยแล้ว รอ Admin ดำเนินการ');
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * ยกเลิกคำขอย้ายทีม
     *
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(MlmTeamTransferRequest $teamTransfer)
    {
        $user = Auth::user();

        // ตรวจสอบว่าเป็นเจ้าของคำขอหรือไม่
        if ($teamTransfer->user_id !== $user->id) {
            abort(403, 'คุณไม่มีสิทธิ์ดำเนินการกับคำขอนี้');
        }

        try {
            $this->transferService->cancelTransfer($teamTransfer, $user);

            return redirect()
                ->route('user.team-transfer.index')
                ->with('success', 'ยกเลิกคำขอเรียบร้อยแล้ว' .
                    ($teamTransfer->paid_at ? ' (คืนเงินเรียบร้อย)' : ''));
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * อนุมัติคำขอย้ายทีม (สำหรับแม่ทีมเก่า)
     *
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(MlmTeamTransferRequest $teamTransfer)
    {
        $user = Auth::user();

        try {
            $this->transferService->approveTransfer($teamTransfer, $user);

            return redirect()
                ->route('user.team-transfer.approvals')
                ->with('success', 'อนุมัติคำขอเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * ปฏิเสธคำขอย้ายทีม (สำหรับแม่ทีมเก่า)
     *
     * @param Request $request
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, MlmTeamTransferRequest $teamTransfer)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $this->transferService->rejectTransfer(
                $teamTransfer,
                $user,
                $validated['rejection_reason']
            );

            return redirect()
                ->route('user.team-transfer.approvals')
                ->with('success', 'ปฏิเสธคำขอเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * แสดงรายการคำขอที่รออนุมัติ (สำหรับแม่ทีม)
     *
     * @return \Illuminate\View\View
     */
    public function approvals()
    {
        $user = Auth::user();

        // ดึงคำขอที่รอการอนุมัติ (ลูกทีมขอย้าย)
        $pendingApprovals = MlmTeamTransferRequest::where('old_unilevel_sponsor_id', $user->mlmMember->id)
            ->where('status', MlmTeamTransferRequest::STATUS_PENDING)
            ->with(['member.user', 'newSponsor.user'])
            ->latest()
            ->paginate(15);

        // คำขอที่ดำเนินการแล้ว (อนุมัติ/ปฏิเสธ)
        $processedApprovals = MlmTeamTransferRequest::where('old_unilevel_sponsor_id', $user->mlmMember->id)
            ->whereIn('status', [
                MlmTeamTransferRequest::STATUS_APPROVED,
                MlmTeamTransferRequest::STATUS_REJECTED,
                MlmTeamTransferRequest::STATUS_PAID,
                MlmTeamTransferRequest::STATUS_COMPLETED,
            ])
            ->with(['member.user', 'newSponsor.user'])
            ->latest()
            ->paginate(15, ['*'], 'processed_page');

        return view('user.team-transfer.approvals', [
            'pendingApprovals' => $pendingApprovals,
            'processedApprovals' => $processedApprovals,
            'pageTitle' => 'คำขอย้ายทีมที่ต้องอนุมัติ',
        ]);
    }
}
