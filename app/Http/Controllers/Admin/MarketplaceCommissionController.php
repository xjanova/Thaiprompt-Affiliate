<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCommission;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceAccount;
use App\Models\User;
use App\Services\Marketplace\MarketplaceCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin Marketplace Commission Controller
 *
 * จัดการคอมมิชชั่นจาก Marketplace Affiliate
 */
class MarketplaceCommissionController extends Controller
{
    /**
     * แสดงรายการคอมมิชชั่นทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = MarketplaceCommission::with(['user', 'order.platform', 'order.account']);

        // กรองตาม platform
        if ($request->filled('platform')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('platform_id', $request->platform);
            });
        }

        // กรองตามผู้ใช้
        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->where('commission_type', $request->type);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // กรองตามวันที่
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $commissions = $query->paginate(20)->withQueryString();
        $platforms = MarketplacePlatform::where('is_active', true)->get();
        $users = User::whereHas('marketplaceCommissions')->get();

        // สถิติภาพรวม
        $stats = [
            'total_commissions' => MarketplaceCommission::count(),
            'pending_commissions' => MarketplaceCommission::where('status', 'pending')->count(),
            'approved_commissions' => MarketplaceCommission::where('status', 'approved')->count(),
            'paid_commissions' => MarketplaceCommission::where('status', 'paid')->count(),
            'total_pending_amount' => MarketplaceCommission::where('status', 'pending')->sum('commission_amount'),
            'total_paid_amount' => MarketplaceCommission::where('status', 'paid')->sum('commission_amount'),
        ];

        return view('admin.marketplace.commissions.index', compact(
            'commissions',
            'platforms',
            'users',
            'stats'
        ));
    }

    /**
     * แสดงรายละเอียดคอมมิชชั่น
     *
     * @param MarketplaceCommission $commission
     * @return \Illuminate\View\View
     */
    public function show(MarketplaceCommission $commission)
    {
        $commission->load(['user', 'order.platform', 'order.account', 'order.items']);

        return view('admin.marketplace.commissions.show', compact('commission'));
    }

    /**
     * อนุมัติคอมมิชชั่น
     *
     * @param MarketplaceCommission $commission
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(MarketplaceCommission $commission)
    {
        try {
            $commissionService = new MarketplaceCommissionService();
            $commissionService->approveCommission($commission);

            return response()->json([
                'success' => true,
                'message' => 'อนุมัติคอมมิชชั่นสำเร็จ',
            ]);

        } catch (\Exception $e) {
            Log::error("Approve commission failed: {$e->getMessage()}", [
                'commission_id' => $commission->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'อนุมัติคอมมิชชั่นล้มเหลว: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * จ่ายคอมมิชชั่น (โอนเข้า Wallet)
     *
     * @param MarketplaceCommission $commission
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(MarketplaceCommission $commission)
    {
        try {
            $commissionService = new MarketplaceCommissionService();
            $commissionService->payCommission($commission);

            return response()->json([
                'success' => true,
                'message' => 'จ่ายคอมมิชชั่นสำเร็จ',
            ]);

        } catch (\Exception $e) {
            Log::error("Pay commission failed: {$e->getMessage()}", [
                'commission_id' => $commission->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'จ่ายคอมมิชชั่นล้มเหลว: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ปฏิเสธคอมมิชชั่น
     *
     * @param Request $request
     * @param MarketplaceCommission $commission
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, MarketplaceCommission $commission)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $commission->update([
                'status' => 'rejected',
                'notes' => $validated['reason'],
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ปฏิเสธคอมมิชชั่นสำเร็จ',
            ]);

        } catch (\Exception $e) {
            Log::error("Reject commission failed: {$e->getMessage()}", [
                'commission_id' => $commission->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ปฏิเสธคอมมิชชั่นล้มเหลว: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อนุมัติคอมมิชชั่นหลายรายการ
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:marketplace_commissions,id',
        ]);

        try {
            $commissionService = new MarketplaceCommissionService();
            $count = 0;

            foreach ($validated['ids'] as $id) {
                $commission = MarketplaceCommission::find($id);
                if ($commission && $commission->status === 'pending') {
                    $commissionService->approveCommission($commission);
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "อนุมัติคอมมิชชั่น {$count} รายการสำเร็จ",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            Log::error("Bulk approve failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'อนุมัติคอมมิชชั่นล้มเหลว: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * จ่ายคอมมิชชั่นหลายรายการ
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkPay(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:marketplace_commissions,id',
        ]);

        try {
            $commissionService = new MarketplaceCommissionService();
            $count = 0;

            foreach ($validated['ids'] as $id) {
                $commission = MarketplaceCommission::find($id);
                if ($commission && $commission->status === 'approved') {
                    $commissionService->payCommission($commission);
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "จ่ายคอมมิชชั่น {$count} รายการสำเร็จ",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            Log::error("Bulk pay failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'จ่ายคอมมิชชั่นล้มเหลว: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบคอมมิชชั่น (เฉพาะที่ยังไม่จ่าย)
     *
     * @param MarketplaceCommission $commission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MarketplaceCommission $commission)
    {
        if ($commission->status === 'paid') {
            return redirect()
                ->route('admin.marketplace.commissions.index')
                ->with('error', 'ไม่สามารถลบคอมมิชชั่นที่จ่ายแล้วได้');
        }

        $commission->delete();

        return redirect()
            ->route('admin.marketplace.commissions.index')
            ->with('success', 'ลบคอมมิชชั่นสำเร็จ');
    }
}
