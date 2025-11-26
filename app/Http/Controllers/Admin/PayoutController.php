<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\PayoutSetting;
use App\Models\EarningsLedger;
use App\Services\PayoutService;
use Illuminate\Http\Request;

/**
 * PayoutController
 *
 * จัดการ Payout Requests และการตั้งค่า
 */
class PayoutController extends Controller
{
    protected PayoutService $payoutService;

    public function __construct()
    {
        $this->payoutService = new PayoutService();
    }

    /**
     * รายการ Payout Requests ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $payouts = $this->payoutService->getPayoutRequests(
            $request->only(['user_id', 'status', 'earning_type', 'date_from', 'date_to']),
            20
        );

        $stats = $this->payoutService->getPayoutStats([
            'date_from' => now()->startOfMonth(),
            'date_to' => now(),
        ]);

        return view('admin.platform-revenue.payouts.index', [
            'payouts' => $payouts,
            'stats' => $stats,
            'filters' => $request->only(['user_id', 'status', 'earning_type', 'date_from', 'date_to']),
            'pageTitle' => 'Payout Requests',
        ]);
    }

    /**
     * รายละเอียด Payout Request
     *
     * @param PayoutRequest $payout
     * @return \Illuminate\View\View
     */
    public function show(PayoutRequest $payout)
    {
        $payout->load(['user', 'payoutSetting', 'approvedByUser', 'rejectedByUser', 'earnings']);

        return view('admin.platform-revenue.payouts.show', [
            'payout' => $payout,
            'pageTitle' => "Payout Request #{$payout->id}",
        ]);
    }

    /**
     * อนุมัติ Payout
     *
     * @param Request $request
     * @param PayoutRequest $payout
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request, PayoutRequest $payout)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $this->payoutService->approvePayout(
                $payout,
                auth()->id(),
                $validated['note'] ?? null
            );

            return redirect()
                ->route('admin.platform-revenue.payouts.show', $payout)
                ->with('success', 'อนุมัติ Payout สำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * ปฏิเสธ Payout
     *
     * @param Request $request
     * @param PayoutRequest $payout
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, PayoutRequest $payout)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->payoutService->rejectPayout(
                $payout,
                auth()->id(),
                $validated['reason']
            );

            return redirect()
                ->route('admin.platform-revenue.payouts.index')
                ->with('success', 'ปฏิเสธ Payout สำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * ประมวลผล Payout ที่อนุมัติแล้ว
     *
     * @param PayoutRequest $payout
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(PayoutRequest $payout)
    {
        try {
            $this->payoutService->processPayout($payout);

            return redirect()
                ->route('admin.platform-revenue.payouts.show', $payout)
                ->with('success', 'ประมวลผล Payout สำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk approve payouts
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'payout_ids' => 'required|array',
            'payout_ids.*' => 'exists:payout_requests,id',
        ]);

        $approved = 0;
        $failed = 0;

        foreach ($validated['payout_ids'] as $payoutId) {
            try {
                $payout = PayoutRequest::find($payoutId);
                if ($payout && $payout->status === PayoutRequest::STATUS_PENDING) {
                    $this->payoutService->approvePayout($payout, auth()->id(), 'Bulk approved');
                    $approved++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return redirect()
            ->route('admin.platform-revenue.payouts.index')
            ->with('success', "อนุมัติสำเร็จ {$approved} รายการ" . ($failed > 0 ? " (ล้มเหลว {$failed} รายการ)" : ''));
    }

    /**
     * การตั้งค่า Payout
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $settings = PayoutSetting::all();

        return view('admin.platform-revenue.payouts.settings', [
            'settings' => $settings,
            'pageTitle' => 'Payout Settings',
        ]);
    }

    /**
     * อัพเดทการตั้งค่า Payout
     *
     * @param Request $request
     * @param PayoutSetting $setting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSetting(Request $request, PayoutSetting $setting)
    {
        $validated = $request->validate([
            'payout_mode' => 'required|in:manual,auto_schedule,realtime,hybrid',
            'min_payout' => 'required|numeric|min:0',
            'max_payout' => 'nullable|numeric|min:0',
            'fee_fixed' => 'nullable|numeric|min:0',
            'fee_percentage' => 'nullable|numeric|min:0|max:100',
            'holding_days' => 'nullable|integer|min:0',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'schedule' => 'nullable|array',
        ]);

        $setting->update($validated);

        return redirect()
            ->route('admin.platform-revenue.payouts.settings')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * Earnings Ledger
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function earnings(Request $request)
    {
        $query = EarningsLedger::with(['user', 'payoutRequest']);

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->earning_type) {
            $query->where('earning_type', $request->earning_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $earnings = $query->latest()->paginate(30);

        // สถิติ
        $stats = [
            'total_gross' => EarningsLedger::sum('gross_amount'),
            'total_net' => EarningsLedger::sum('net_amount'),
            'total_fees' => EarningsLedger::sum('platform_fee'),
            'pending_count' => EarningsLedger::where('status', EarningsLedger::STATUS_PENDING)->count(),
            'available_count' => EarningsLedger::where('status', EarningsLedger::STATUS_AVAILABLE)->count(),
            'paid_count' => EarningsLedger::where('status', EarningsLedger::STATUS_PAID)->count(),
        ];

        return view('admin.platform-revenue.earnings.index', [
            'earnings' => $earnings,
            'stats' => $stats,
            'filters' => $request->only(['user_id', 'earning_type', 'status', 'date_from', 'date_to']),
            'pageTitle' => 'Earnings Ledger',
        ]);
    }

    /**
     * รายละเอียด Earning
     *
     * @param EarningsLedger $earning
     * @return \Illuminate\View\View
     */
    public function showEarning(EarningsLedger $earning)
    {
        $earning->load(['user', 'payoutRequest']);

        return view('admin.platform-revenue.earnings.show', [
            'earning' => $earning,
            'pageTitle' => "Earning #{$earning->id}",
        ]);
    }

    /**
     * API: ดึง pending payouts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiPendingPayouts()
    {
        $payouts = PayoutRequest::with(['user'])
            ->where('status', PayoutRequest::STATUS_PENDING)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payouts,
            'total_pending' => PayoutRequest::where('status', PayoutRequest::STATUS_PENDING)->count(),
            'total_amount' => PayoutRequest::where('status', PayoutRequest::STATUS_PENDING)->sum('net_amount'),
        ]);
    }
}
