<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmMember;
use App\Models\MlmTeamTransferRequest;
use App\Services\MlmTeamTransferService;
use Illuminate\Http\JsonResponse;
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
            'pageTitle' => 'รายละเอียดคำขอย้ายทีม #'.$teamTransfer->id,
        ]);
    }

    /**
     * แสดงฟอร์มดำเนินการย้ายทีม
     *
     * @return \Illuminate\View\View
     */
    public function edit(MlmTeamTransferRequest $teamTransfer)
    {
        // ตรวจสอบว่าสามารถดำเนินการได้หรือไม่
        if (! $teamTransfer->canBeProcessed()) {
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
            'pageTitle' => 'ดำเนินการย้ายทีม #'.$teamTransfer->id,
        ]);
    }

    /**
     * ดำเนินการย้ายทีม
     *
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
     * @param  int  $id
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
     * @param  int  $memberId
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

    // ============================================
    // Admin Direct Transfer Methods
    // ย้ายทีมโดยตรง - รองรับทั้ง Unilevel และ Binary
    // ============================================

    /**
     * แสดงฟอร์มย้ายทีมโดยตรง (Admin Direct Transfer)
     *
     * @return \Illuminate\View\View
     */
    public function directTransferForm(Request $request)
    {
        // ถ้ามี member_id ส่งมา ให้ดึงข้อมูลสมาชิก
        $selectedMember = null;
        if ($request->filled('member_id')) {
            $selectedMember = MlmMember::with([
                'user',
                'unilevelSponsor.user',
                'binaryParent.user',
            ])->find($request->member_id);
        }

        return view('admin.team-transfer.direct', [
            'selectedMember' => $selectedMember,
            'pageTitle' => 'ย้ายทีม MLM โดยตรง',
        ]);
    }

    /**
     * ดำเนินการย้ายทีมโดยตรง (Admin Direct Transfer)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function directTransferProcess(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'member_id' => 'required|exists:mlm_members,id',
            'transfer_unilevel' => 'nullable|boolean',
            'new_unilevel_sponsor_id' => 'nullable|required_if:transfer_unilevel,1|exists:mlm_members,id',
            'transfer_binary' => 'nullable|boolean',
            'new_binary_parent_id' => 'nullable|required_if:transfer_binary,1|exists:mlm_members,id',
            'new_binary_position' => 'nullable|required_if:transfer_binary,1|in:left,right',
            'admin_notes' => 'nullable|string|max:1000',
        ], [
            'member_id.required' => 'กรุณาเลือกสมาชิกที่ต้องการย้าย',
            'member_id.exists' => 'ไม่พบสมาชิกที่เลือก',
            'new_unilevel_sponsor_id.required_if' => 'กรุณาเลือก Sponsor ใหม่สำหรับ Unilevel',
            'new_unilevel_sponsor_id.exists' => 'ไม่พบ Sponsor ที่เลือก',
            'new_binary_parent_id.required_if' => 'กรุณาเลือก Parent ใหม่สำหรับ Binary',
            'new_binary_parent_id.exists' => 'ไม่พบ Binary Parent ที่เลือก',
            'new_binary_position.required_if' => 'กรุณาเลือกตำแหน่ง Binary',
            'new_binary_position.in' => 'ตำแหน่ง Binary ไม่ถูกต้อง',
        ]);

        // ตรวจสอบว่าเลือกย้ายอย่างน้อย 1 แผน
        $transferUnilevel = $request->boolean('transfer_unilevel');
        $transferBinary = $request->boolean('transfer_binary');

        if (! $transferUnilevel && ! $transferBinary) {
            return back()
                ->withInput()
                ->with('error', 'กรุณาเลือกอย่างน้อย 1 แผน (Unilevel หรือ Binary) ที่ต้องการย้าย');
        }

        try {
            $member = MlmMember::findOrFail($validated['member_id']);
            $admin = Auth::user();

            // เตรียมข้อมูลการย้าย
            $transferData = [
                'admin_notes' => $validated['admin_notes'] ?? null,
            ];

            if ($transferUnilevel && ! empty($validated['new_unilevel_sponsor_id'])) {
                $transferData['new_unilevel_sponsor_id'] = $validated['new_unilevel_sponsor_id'];
            }

            if ($transferBinary && ! empty($validated['new_binary_parent_id'])) {
                $transferData['new_binary_parent_id'] = $validated['new_binary_parent_id'];
                $transferData['new_binary_position'] = $validated['new_binary_position'];
            }

            // เรียกใช้ service
            $results = $this->transferService->adminDirectTransfer($member, $transferData, $admin);

            // สร้างข้อความสรุป
            $messages = [];
            if ($results['unilevel_transferred']) {
                $newSponsor = MlmMember::with('user')->find($results['new_data']['unilevel_sponsor_id']);
                $messages[] = "Unilevel: ย้ายไป {$newSponsor->user->name} สำเร็จ";
            }
            if ($results['binary_transferred']) {
                $newParent = MlmMember::with('user')->find($results['new_data']['binary_parent_id']);
                $positionLabel = $results['new_data']['binary_position'] === 'left' ? 'ซ้าย' : 'ขวา';
                $messages[] = "Binary: ย้ายไป {$newParent->user->name} (ตำแหน่ง{$positionLabel}) สำเร็จ";
            }

            return redirect()
                ->route('admin.team-transfer.direct')
                ->with('success', 'ย้ายทีมสำเร็จ! '.implode(' | ', $messages));

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * API: ค้นหาสมาชิกสำหรับ autocomplete
     */
    public function searchMembersApi(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $excludeId = $request->get('exclude_id');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $members = $this->transferService->searchMembersForTransfer(
            $search,
            $excludeId ? (int) $excludeId : null
        );

        return response()->json($members->map(function ($member) {
            return [
                'id' => $member->id,
                'member_code' => $member->member_code,
                'user_name' => $member->user?->name ?? 'N/A',
                'user_email' => $member->user?->email ?? 'N/A',
                'display' => "{$member->member_code} - {$member->user?->name}",
            ];
        }));
    }

    /**
     * API: ดึงตำแหน่ง Binary ที่ว่างของสมาชิก
     */
    public function getBinaryPositionsApi(MlmMember $member): JsonResponse
    {
        $positions = $this->transferService->getAvailableBinaryPositions($member);

        // ดึงข้อมูลลูกทีม Binary
        $leftChild = MlmMember::with('user')
            ->where('binary_parent_id', $member->id)
            ->where('binary_position', 'left')
            ->first();

        $rightChild = MlmMember::with('user')
            ->where('binary_parent_id', $member->id)
            ->where('binary_position', 'right')
            ->first();

        return response()->json([
            'left' => [
                'available' => $positions['left'],
                'occupied_by' => $leftChild ? [
                    'id' => $leftChild->id,
                    'member_code' => $leftChild->member_code,
                    'user_name' => $leftChild->user?->name,
                ] : null,
            ],
            'right' => [
                'available' => $positions['right'],
                'occupied_by' => $rightChild ? [
                    'id' => $rightChild->id,
                    'member_code' => $rightChild->member_code,
                    'user_name' => $rightChild->user?->name,
                ] : null,
            ],
        ]);
    }
}
