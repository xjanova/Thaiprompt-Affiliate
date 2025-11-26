<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletDebt;
use App\Models\User;
use App\Services\DebtCollectionService;
use Illuminate\Http\Request;

/**
 * DebtController
 *
 * จัดการระบบหนี้/ติดลบ
 */
class DebtController extends Controller
{
    protected DebtCollectionService $debtService;

    public function __construct()
    {
        $this->debtService = new DebtCollectionService();
    }

    /**
     * รายการหนี้ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $debts = $this->debtService->getDebts(
            $request->only(['user_id', 'status', 'source_type', 'date_from', 'date_to']),
            20
        );

        $stats = $this->debtService->getDebtStats();

        return view('admin.platform-revenue.debts.index', [
            'debts' => $debts,
            'stats' => $stats,
            'filters' => $request->only(['user_id', 'status', 'source_type', 'date_from', 'date_to']),
            'pageTitle' => 'Wallet Debts',
        ]);
    }

    /**
     * รายละเอียดหนี้
     *
     * @param WalletDebt $debt
     * @return \Illuminate\View\View
     */
    public function show(WalletDebt $debt)
    {
        $debt->load(['user', 'createdByUser', 'waivedByUser']);

        // ดึงประวัติการหัก
        $deductionHistory = $debt->metadata['deduction_history'] ?? [];

        return view('admin.platform-revenue.debts.show', [
            'debt' => $debt,
            'deductionHistory' => $deductionHistory,
            'pageTitle' => "Debt #{$debt->id}",
        ]);
    }

    /**
     * ฟอร์มสร้างหนี้ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.platform-revenue.debts.create', [
            'pageTitle' => 'สร้างหนี้ใหม่',
        ]);
    }

    /**
     * สร้างหนี้ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
            'priority' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $debt = $this->debtService->createManualDebt(
                $validated['user_id'],
                $validated['amount'],
                $validated['reason'],
                auth()->id(),
                $validated['priority'] ?? 1
            );

            return redirect()
                ->route('admin.platform-revenue.debts.show', $debt)
                ->with('success', 'สร้างหนี้สำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * ยกเว้นหนี้
     *
     * @param Request $request
     * @param WalletDebt $debt
     * @return \Illuminate\Http\RedirectResponse
     */
    public function waive(Request $request, WalletDebt $debt)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->debtService->waiveDebt($debt, auth()->id(), $validated['reason']);

            return redirect()
                ->route('admin.platform-revenue.debts.show', $debt)
                ->with('success', 'ยกเว้นหนี้สำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * ยกเลิกหนี้
     *
     * @param WalletDebt $debt
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(WalletDebt $debt)
    {
        try {
            $this->debtService->cancelDebt($debt);

            return redirect()
                ->route('admin.platform-revenue.debts.index')
                ->with('success', 'ยกเลิกหนี้สำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * ดูหนี้ของ User
     *
     * @param User $user
     * @return \Illuminate\View\View
     */
    public function userDebts(User $user)
    {
        $summary = $this->debtService->getUserDebtSummary($user->id);

        return view('admin.platform-revenue.debts.user', [
            'user' => $user,
            'summary' => $summary,
            'pageTitle' => "หนี้ของ {$user->name}",
        ]);
    }

    /**
     * Batch collect debts
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function batchCollect()
    {
        try {
            $result = $this->debtService->batchCollectDebts();

            $message = "เก็บหนี้จาก {$result['processed']} users, รวม ฿" . number_format($result['collected'], 2);
            if (count($result['errors']) > 0) {
                $message .= " (ล้มเหลว " . count($result['errors']) . " รายการ)";
            }

            return redirect()
                ->route('admin.platform-revenue.debts.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export รายงานหนี้
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $debts = WalletDebt::with(['user'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->byPriority()
            ->get();

        $filename = 'debts-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($debts) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, [
                'ID',
                'User ID',
                'ชื่อ User',
                'ยอดหนี้เดิม',
                'หักแล้ว',
                'คงเหลือ',
                'สถานะ',
                'แหล่งที่มา',
                'เหตุผล',
                'วันที่สร้าง',
            ]);

            foreach ($debts as $debt) {
                fputcsv($file, [
                    $debt->id,
                    $debt->user_id,
                    $debt->user->name ?? '-',
                    $debt->original_amount,
                    $debt->deducted_amount,
                    $debt->remaining_amount,
                    $debt->status_label,
                    "{$debt->source_type}#{$debt->source_id}",
                    $debt->reason,
                    $debt->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * API: ดึงสถิติหนี้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiStats()
    {
        $stats = $this->debtService->getDebtStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * API: ค้นหา User สำหรับสร้างหนี้
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q');

        if (!$query || strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orWhere('id', $query)
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'data' => $users->map(fn($u) => [
                'id' => $u->id,
                'text' => "{$u->name} ({$u->email})",
            ]),
        ]);
    }
}
