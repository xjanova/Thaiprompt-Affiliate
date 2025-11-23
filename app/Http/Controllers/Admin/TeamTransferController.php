<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmTeamTransferRequest;
use App\Services\MlmTeamTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * TeamTransferController - Admin Panel
 *
 * จัดการคำขอย้ายทีมสำหรับ Admin
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
        $this->middleware(['auth', 'role:admin']);
        $this->transferService = $transferService;
    }

    /**
     * แสดงรายการคำขอย้ายทีมทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = MlmTeamTransferRequest::with([
            'user',
            'member',
            'oldSponsor.user',
            'newSponsor.user',
        ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by user name or member code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('member', function ($q) use ($search) {
                $q->where('member_code', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $requests = $query->paginate(20);

        // สถิติ
        $stats = [
            'total' => MlmTeamTransferRequest::count(),
            'pending' => MlmTeamTransferRequest::where('status', MlmTeamTransferRequest::STATUS_PENDING)->count(),
            'approved' => MlmTeamTransferRequest::where('status', MlmTeamTransferRequest::STATUS_APPROVED)->count(),
            'paid' => MlmTeamTransferRequest::where('status', MlmTeamTransferRequest::STATUS_PAID)->count(),
            'processing' => MlmTeamTransferRequest::where('status', MlmTeamTransferRequest::STATUS_PROCESSING)->count(),
            'completed' => MlmTeamTransferRequest::where('status', MlmTeamTransferRequest::STATUS_COMPLETED)->count(),
            'rejected' => MlmTeamTransferRequest::where('status', MlmTeamTransferRequest::STATUS_REJECTED)->count(),
            'cancelled' => MlmTeamTransferRequest::where('status', MlmTeamTransferRequest::STATUS_CANCELLED)->count(),
        ];

        return view('admin.team-transfer.index', [
            'requests' => $requests,
            'stats' => $stats,
            'pageTitle' => 'จัดการคำขอย้ายทีม',
        ]);
    }

    /**
     * แสดงรายละเอียดคำขอย้ายทีม
     *
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\View\View
     */
    public function show(MlmTeamTransferRequest $teamTransfer)
    {
        $teamTransfer->load([
            'user',
            'member',
            'oldSponsor.user',
            'newSponsor.user',
            'oldBinaryParent.user',
            'newBinaryParent.user',
            'approver',
            'rejecter',
            'processor',
            'payment',
        ]);

        return view('admin.team-transfer.show', [
            'request' => $teamTransfer,
            'pageTitle' => 'รายละเอียดคำขอย้ายทีม #' . $teamTransfer->id,
        ]);
    }

    /**
     * แสดงฟอร์มดำเนินการย้ายทีม
     *
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\View\View
     */
    public function edit(MlmTeamTransferRequest $teamTransfer)
    {
        // ตรวจสอบว่าสามารถดำเนินการได้หรือไม่
        if (!$teamTransfer->canBeProcessed()) {
            return redirect()
                ->route('admin.team-transfer.show', $teamTransfer)
                ->with('error', 'ไม่สามารถดำเนินการคำขอนี้ได้');
        }

        $teamTransfer->load([
            'user',
            'member',
            'oldSponsor.user',
            'newSponsor.user',
        ]);

        return view('admin.team-transfer.edit', [
            'request' => $teamTransfer,
            'pageTitle' => 'ดำเนินการย้ายทีม #' . $teamTransfer->id,
        ]);
    }

    /**
     * ดำเนินการย้ายทีม
     *
     * @param Request $request
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(Request $request, MlmTeamTransferRequest $teamTransfer)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $admin = Auth::user();

            $this->transferService->processTransfer(
                $teamTransfer,
                $admin,
                $validated['admin_notes'] ?? null
            );

            return redirect()
                ->route('admin.team-transfer.show', $teamTransfer)
                ->with('success', 'ดำเนินการย้ายทีมเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * ลบคำขอย้ายทีม (Soft Delete)
     *
     * @param MlmTeamTransferRequest $teamTransfer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MlmTeamTransferRequest $teamTransfer)
    {
        // ตรวจสอบว่าสามารถลบได้หรือไม่
        if (in_array($teamTransfer->status, [
            MlmTeamTransferRequest::STATUS_PROCESSING,
            MlmTeamTransferRequest::STATUS_COMPLETED,
        ])) {
            return back()
                ->with('error', 'ไม่สามารถลบคำขอที่กำลังดำเนินการหรือเสร็จสิ้นแล้ว');
        }

        $teamTransfer->delete();

        return redirect()
            ->route('admin.team-transfer.index')
            ->with('success', 'ลบคำขอเรียบร้อยแล้ว');
    }

    /**
     * Restore คำขอย้ายทีมที่ถูกลบ
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $teamTransfer = MlmTeamTransferRequest::withTrashed()->findOrFail($id);
        $teamTransfer->restore();

        return redirect()
            ->route('admin.team-transfer.show', $teamTransfer)
            ->with('success', 'กู้คืนคำขอเรียบร้อยแล้ว');
    }

    /**
     * ดูประวัติการย้ายทีมของสมาชิก
     *
     * @param int $memberId
     * @return \Illuminate\View\View
     */
    public function history($memberId)
    {
        $requests = MlmTeamTransferRequest::where('mlm_member_id', $memberId)
            ->with([
                'oldSponsor.user',
                'newSponsor.user',
                'approver',
                'processor',
            ])
            ->latest()
            ->paginate(20);

        return view('admin.team-transfer.history', [
            'requests' => $requests,
            'pageTitle' => 'ประวัติการย้ายทีม',
        ]);
    }

    /**
     * Export รายงานคำขอย้ายทีม
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        // TODO: Implement export functionality (CSV/Excel)
        return back()->with('info', 'ฟีเจอร์ Export กำลังพัฒนา');
    }

    /**
     * สถิติการย้ายทีม
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function statistics(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // สถิติตามสถานะ
        $byStatus = MlmTeamTransferRequest::selectRaw('status, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        // สถิติรายวัน
        $byDate = MlmTeamTransferRequest::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top sponsors (แม่ทีมที่มีลูกทีมย้ายออกมากที่สุด)
        $topLosing = MlmTeamTransferRequest::selectRaw('old_unilevel_sponsor_id, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('old_unilevel_sponsor_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('oldSponsor.user')
            ->get();

        // Top sponsors (แม่ทีมที่มีลูกทีมย้ายเข้ามากที่สุด)
        $topGaining = MlmTeamTransferRequest::selectRaw('new_unilevel_sponsor_id, COUNT(*) as count')
            ->where('status', MlmTeamTransferRequest::STATUS_COMPLETED)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('new_unilevel_sponsor_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('newSponsor.user')
            ->get();

        return view('admin.team-transfer.statistics', [
            'byStatus' => $byStatus,
            'byDate' => $byDate,
            'topLosing' => $topLosing,
            'topGaining' => $topGaining,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pageTitle' => 'สถิติการย้ายทีม',
        ]);
    }
}
