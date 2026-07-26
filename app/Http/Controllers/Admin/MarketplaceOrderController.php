<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceOrder;
use App\Models\MarketplacePlatform;
use App\Services\Marketplace\MarketplaceCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin Marketplace Order Controller
 *
 * จัดการออเดอร์ที่ Sync มาจาก Marketplace (Lazada, Shopee, TikTok Shop)
 */
class MarketplaceOrderController extends Controller
{
    /**
     * แสดงรายการออเดอร์ Marketplace ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = MarketplaceOrder::with(['platform', 'account', 'items', 'affiliateUser', 'affiliateLink']);

        // กรองตาม platform
        if ($request->filled('platform')) {
            $query->where('platform_id', $request->platform);
        }

        // กรองตาม account
        if ($request->filled('account')) {
            $query->where('account_id', $request->account);
        }

        // กรองตามสถานะออเดอร์
        if ($request->filled('order_status')) {
            $query->where('order_status', $request->order_status);
        }

        // กรองตามสถานะคอมมิชชั่น
        if ($request->filled('commission_status')) {
            $query->where('commission_status', $request->commission_status);
        }

        // ค้นหาตามเลขออเดอร์
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('external_order_id', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        // กรองตามวันที่
        if ($request->filled('date_from')) {
            $query->whereDate('ordered_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('ordered_at', '<=', $request->date_to);
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort_by', 'ordered_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $orders = $query->paginate(20)->withQueryString();
        $platforms = MarketplacePlatform::where('is_active', true)->get();
        $accounts = MarketplaceAccount::where('status', 'active')->get();

        // สถิติภาพรวม
        $stats = [
            'total_orders' => MarketplaceOrder::count(),
            'pending_orders' => MarketplaceOrder::where('order_status', 'pending')->count(),
            'completed_orders' => MarketplaceOrder::where('order_status', 'completed')->count(),
            'total_sales' => MarketplaceOrder::sum('total_amount'),
            'pending_commissions' => MarketplaceOrder::where('commission_status', 'pending')->count(),
            'paid_commissions' => MarketplaceOrder::where('commission_status', 'paid')->count(),
        ];

        return view('admin.marketplace.orders.index', compact(
            'orders',
            'platforms',
            'accounts',
            'stats'
        ));
    }

    /**
     * แสดงรายละเอียดออเดอร์
     *
     * @return \Illuminate\View\View
     */
    public function show(MarketplaceOrder $order)
    {
        $order->load([
            'platform',
            'account',
            'items.product',
            'affiliateUser',
            'affiliateLink',
            'commissions.user',
        ]);

        return view('admin.marketplace.orders.show', compact('order'));
    }

    /**
     * คำนวณคอมมิชชั่นสำหรับออเดอร์
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateCommission(MarketplaceOrder $order)
    {
        // 🚨 ด่านตรวจต้องอยู่ที่ "จุดผลิตแถวค่าคอม" ด้วย ไม่ใช่แค่ปุ่มอนุมัติ/จ่าย
        //
        //    ช่องโหว่ที่เจอตอนรีวิว: ออเดอร์ลาซาด้าที่ยังไม่ผ่านด่าน (commission_status='pending')
        //    หน้ารายละเอียดยังโชว์ปุ่ม "คำนวณคอมมิชชั่น" ซึ่งสร้างแถว marketplace_commissions ได้เลย
        //    พอมีแถวแล้วก็ไปโผล่ในคิวอนุมัติ = เงินไหลออกได้โดยไม่ต้องผ่านด่านสักข้อ
        //    → ต้องกันตั้งแต่ยังไม่ให้ "สร้างแถว" สำหรับออเดอร์ affiliate ที่ลาซาด้ายังไม่เคลียร์
        $guard = new \App\Services\Marketplace\AffiliateSettlementGuard;
        $verdict = $guard->checkOrder($order);
        if (! ($verdict['allowed'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'ยังคำนวณค่าคอมไม่ได้: '.($verdict['reason'] ?? 'ลาซาด้ายังไม่ยืนยันการเคลียร์เงิน'),
            ], 422);
        }

        try {
            $commissionService = new MarketplaceCommissionService;
            $commissions = $commissionService->calculateCommissions($order);

            return response()->json([
                'success' => true,
                'message' => 'คำนวณคอมมิชชั่นสำเร็จ',
                'data' => [
                    'total_commissions' => count($commissions),
                    'total_amount' => collect($commissions)->sum('commission_amount'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Calculate commission failed: {$e->getMessage()}", [
                'order_id' => $order->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'คำนวณคอมมิชชั่นล้มเหลว: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดทสถานะออเดอร์
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, MarketplaceOrder $order)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending,processing,shipped,delivered,completed,cancelled,refunded',
        ]);

        $order->update($validated);

        // ถ้าออเดอร์ completed และยังไม่มีคอมมิชชั่น ให้คำนวณคอมมิชชั่น
        if ($validated['order_status'] === 'completed' && $order->commission_status === 'pending') {
            try {
                $commissionService = new MarketplaceCommissionService;
                $commissionService->calculateCommissions($order);
                $order->update(['commission_status' => 'calculated']);
            } catch (\Exception $e) {
                Log::error("Auto calculate commission failed: {$e->getMessage()}", [
                    'order_id' => $order->id,
                ]);
            }
        }

        return redirect()
            ->route('admin.marketplace.orders.show', $order)
            ->with('success', 'อัพเดทสถานะออเดอร์สำเร็จ');
    }

    /**
     * ลบออเดอร์
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MarketplaceOrder $order)
    {
        // ลบ commissions และ items ก่อน
        $order->commissions()->delete();
        $order->items()->delete();
        $order->delete();

        return redirect()
            ->route('admin.marketplace.orders.index')
            ->with('success', 'ลบออเดอร์สำเร็จ');
    }
}
