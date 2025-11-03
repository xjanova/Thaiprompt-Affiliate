<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Exception;

class WithdrawalController extends Controller
{
    protected $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Display all withdrawal requests
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->input('status'),
            'user_id' => $request->input('user_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'payment_type' => $request->input('payment_type'),
        ];

        $withdrawals = $this->withdrawalService->getAllWithdrawals($filters, 20);

        $stats = [
            'pending_count' => WithdrawalRequest::pending()->count(),
            'pending_amount' => WithdrawalRequest::pending()->sum('amount'),
            'approved_today' => WithdrawalRequest::approved()->whereDate('approved_at', today())->count(),
            'completed_today' => WithdrawalRequest::completed()->whereDate('transfer_completed_at', today())->count(),
        ];

        return view('admin.withdrawals.index', compact('withdrawals', 'filters', 'stats'));
    }

    /**
     * Display pending withdrawal requests
     */
    public function pending()
    {
        $withdrawals = $this->withdrawalService->getPendingWithdrawals(20);

        return view('admin.withdrawals.pending', compact('withdrawals'));
    }

    /**
     * Show withdrawal request details
     */
    public function show($id)
    {
        $withdrawal = WithdrawalRequest::with(['user', 'wallet', 'paymentMethod', 'approvedBy', 'rejectedBy'])
            ->findOrFail($id);

        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Approve withdrawal request
     */
    public function approve(Request $request, $id)
    {
        if (!auth()->user()->hasPermission('approve_withdrawals')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์อนุมัติคำขอถอนเงิน');
        }

        try {
            $withdrawal = WithdrawalRequest::findOrFail($id);
            $this->withdrawalService->approveWithdrawal($withdrawal, auth()->user());

            return redirect()->route('admin.withdrawals.show', $id)
                ->with('success', 'อนุมัติคำขอถอนเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Reject withdrawal request
     */
    public function reject(Request $request, $id)
    {
        if (!auth()->user()->hasPermission('approve_withdrawals')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ปฏิเสธคำขอถอนเงิน');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $withdrawal = WithdrawalRequest::findOrFail($id);
            $this->withdrawalService->rejectWithdrawal(
                $withdrawal,
                auth()->user(),
                $request->rejection_reason
            );

            return redirect()->route('admin.withdrawals.show', $id)
                ->with('success', 'ปฏิเสธคำขอถอนเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Complete withdrawal with transfer slip
     */
    public function complete(Request $request, $id)
    {
        if (!auth()->user()->hasPermission('approve_withdrawals')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ดำเนินการนี้');
        }

        $request->validate([
            'transfer_slip' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'transfer_note' => 'nullable|string|max:500',
        ]);

        try {
            $withdrawal = WithdrawalRequest::findOrFail($id);
            $this->withdrawalService->completeWithdrawal(
                $withdrawal,
                auth()->user(),
                $request->file('transfer_slip'),
                $request->transfer_note
            );

            return redirect()->route('admin.withdrawals.show', $id)
                ->with('success', 'ดำเนินการโอนเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Batch approve withdrawals
     */
    public function batchApprove(Request $request)
    {
        if (!auth()->user()->hasPermission('approve_withdrawals')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์อนุมัติคำขอถอนเงิน');
        }

        $request->validate([
            'withdrawal_ids' => 'required|array',
            'withdrawal_ids.*' => 'exists:withdrawal_requests,id',
        ]);

        $successCount = 0;
        $errorCount = 0;

        foreach ($request->withdrawal_ids as $id) {
            try {
                $withdrawal = WithdrawalRequest::find($id);
                if ($withdrawal && $withdrawal->isPending()) {
                    $this->withdrawalService->approveWithdrawal($withdrawal, auth()->user());
                    $successCount++;
                }
            } catch (Exception $e) {
                $errorCount++;
            }
        }

        $message = "อนุมัติสำเร็จ {$successCount} รายการ";
        if ($errorCount > 0) {
            $message .= ", ล้มเหลว {$errorCount} รายการ";
        }

        return redirect()->back()->with('success', $message);
    }
}
