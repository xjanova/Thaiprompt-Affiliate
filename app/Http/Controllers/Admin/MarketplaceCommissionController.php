<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCommission;
use App\Models\MarketplacePlatform;
use App\Models\User;
use App\Services\Marketplace\AffiliateSettlementGuard;
use App\Services\Marketplace\MarketplaceCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin Marketplace Commission Controller
 *
 * จัดการคอมมิชชั่นจาก Marketplace Affiliate
 *
 * 🚨 กฎเหล็ก: ทุกทางที่ "ทำให้เงินออก" ต้องผ่าน AffiliateSettlementGuard ก่อนเสมอ
 *    (approve / pay / bulkApprove / bulkPay)
 *    ห้ามเรียก MarketplaceCommissionService ตรง ๆ โดยไม่ตรวจด่าน
 *    เหตุผล: ค่าคอม Lazada จ่ายได้ต่อเมื่อลาซาด้าเคลียร์เงินให้เราจริงเท่านั้น
 */
class MarketplaceCommissionController extends Controller
{
    /**
     * แสดงรายการคอมมิชชั่นทั้งหมด
     *
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
     * 🚨 ต้องผ่าน AffiliateSettlementGuard ก่อนเสมอ — ลาซาด้ายังไม่เคลียร์เงิน = อนุมัติไม่ได้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, MarketplaceCommission $commission)
    {
        // ── ด่านตรวจ "เงินเข้าจริงหรือยัง" ──────────────────────────────────
        $gate = $this->settlementGate($request, $commission, 'approve');
        if ($gate['stop'] !== null) {
            return $gate['stop'];
        }

        try {
            $commissionService = new MarketplaceCommissionService;

            // service คืน false เงียบ ๆ เมื่อสถานะไม่ใช่ pending — ห้ามรายงานว่าสำเร็จ
            if (! $commissionService->approveCommission($commission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'อนุมัติคอมมิชชั่นไม่สำเร็จ (สถานะปัจจุบัน: '.$commission->status.')',
                ], 422);
            }

            $this->writeOverrideNote($commission, $gate, 'อนุมัติ');

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
                'message' => 'อนุมัติคอมมิชชั่นล้มเหลว — ระบบบันทึกรายละเอียดไว้ใน log แล้ว',
            ], 500);
        }
    }

    /**
     * จ่ายคอมมิชชั่น (โอนเข้า Wallet)
     *
     * 🚨 จุดนี้ "เงินออกจริง" — ด่านตรวจสำคัญที่สุดอยู่ตรงนี้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(Request $request, MarketplaceCommission $commission)
    {
        // ── ด่านตรวจ "เงินเข้าจริงหรือยัง" ──────────────────────────────────
        $gate = $this->settlementGate($request, $commission, 'pay');
        if ($gate['stop'] !== null) {
            return $gate['stop'];
        }

        try {
            $commissionService = new MarketplaceCommissionService;

            // service คืน false เมื่อสถานะไม่ใช่ approved หรือผู้ใช้ไม่มีวอลเลต
            // (เดิมตอบว่า "สำเร็จ" ทั้งที่เงินไม่ได้เข้า → แอดมินเข้าใจผิดว่าจ่ายไปแล้ว)
            if (! $commissionService->payCommission($commission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'จ่ายคอมมิชชั่นไม่สำเร็จ (สถานะปัจจุบัน: '.$commission->status.') — ตรวจสอบวอลเลตผู้รับและ log',
                ], 422);
            }

            $this->writeOverrideNote($commission, $gate, 'จ่ายเงิน');

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
                'message' => 'จ่ายคอมมิชชั่นล้มเหลว — ระบบบันทึกรายละเอียดไว้ใน log แล้ว',
            ], 500);
        }
    }

    /**
     * ด่านตรวจการเคลียร์เงินสำหรับ "การกดทีละรายการ"
     *
     * 🚫 ไม่มีทางข้ามด่าน (no override) — ตั้งใจไม่ทำประตูหลัง
     *    ลาซาด้ายังไม่ยืนยันว่าเคลียร์เงินให้เรา = อนุมัติ/จ่ายไม่ได้ ไม่มีข้อยกเว้น
     *    ถ้าเจอเคสที่ควรจ่ายแต่ด่านไม่ผ่าน ให้แก้ที่ต้นเหตุ (ยืนยันยอดกับลาซาด้า)
     *    ไม่ใช่หาทางลัดข้ามการตรวจสอบ
     *
     * @param  string  $action  ชื่อการกระทำ (ใช้ใน log เท่านั้น)
     * @return array{stop:?\Illuminate\Http\JsonResponse, override_reason:?string, verdict:array}
     */
    private function settlementGate(Request $request, MarketplaceCommission $commission, string $action): array
    {
        $verdict = (new AffiliateSettlementGuard)->check($commission);

        if ($verdict['allowed']) {
            return ['stop' => null, 'override_reason' => null, 'verdict' => $verdict];
        }

        // 🚫 ไม่มีทางข้ามด่าน — ตั้งใจไม่ทำ "ประตูหลัง"
        //
        //    เคยมีโค้ดรับ force_override + override_reason แล้วปล่อยให้จ่ายได้ทันที
        //    ตัดออกแล้วเพราะขัดกฎของเจ้าของระบบตรงๆ ("จ่ายได้ต่อเมื่อลาซาด้าจ่ายเราจริง"):
        //      - แอดมินคนไหนก็ใช้ได้ ไม่มีการเช็คสิทธิ์เพิ่ม
        //      - ไม่มีสวิตช์ปิด และไม่ต้องมีคนที่สองอนุมัติ
        //      - การ log หลังเงินออกไปแล้ว = "บันทึกความเสียหาย" ไม่ใช่ "การป้องกัน"
        //    ถ้าวันหนึ่งจำเป็นต้องมีจริง ต้องออกแบบใหม่: ธงใน MlmGlobalSetting (default ปิด)
        //    + จำกัดเฉพาะ super admin + ต้องมีผู้อนุมัติคนที่สอง
        //
        //    ระหว่างนี้ ถ้ามีเคสจำเป็นจริง ให้แก้ที่ต้นเหตุ (ยืนยันยอดกับลาซาด้าให้ผ่านด่าน)
        //    ไม่ใช่เปิดทางลัดข้ามการตรวจสอบ
        return [
            'stop' => response()->json([
                'success' => false,
                'message' => 'ยังจ่ายไม่ได้: '.$verdict['reason'],
            ], 422),
            'override_reason' => null,
            'verdict' => $verdict,
        ];
    }

    /**
     * เขียนร่องรอยการข้ามด่านลง notes ของค่าคอม (ต่อท้ายของเดิม ห้ามทับ)
     *
     * @param  array{stop:?\Illuminate\Http\JsonResponse, override_reason:?string, verdict:array}  $gate
     */
    private function writeOverrideNote(MarketplaceCommission $commission, array $gate, string $actionLabel): void
    {
        if (empty($gate['override_reason'])) {
            return;
        }

        $stamp = '[ข้ามด่านตรวจ '.now()->format('Y-m-d H:i').'] '
            .$actionLabel.'โดยแอดมิน #'.(auth()->id() ?? '-')
            .' | ด่านตรวจแจ้งว่า: '.(string) ($gate['verdict']['reason'] ?? '-')
            .' | เหตุผลของแอดมิน: '.$gate['override_reason'];

        $old = trim((string) $commission->notes);

        $commission->update([
            'notes' => $old === '' ? $stamp : $old."\n".$stamp,
        ]);
    }

    /**
     * ปฏิเสธคอมมิชชั่น
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, MarketplaceCommission $commission)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            // เหตุผลปฏิเสธมีคอลัมน์ของตัวเองอยู่แล้ว (rejected_reason)
            // ⚠️ ห้ามยัดลง notes แล้วทับของเดิม — notes เก็บร่องรอยจากระบบ sync
            //    (เช่น "อนุมัติอัตโนมัติจากรายงาน conversion ของ Lazada | ออเดอร์ ...")
            //    ทับทิ้ง = หลักฐานว่าเงินก้อนนี้มาจากไหนหายไปตลอดกาล → ต่อท้ายแทน
            // ⚠️ ไม่มีคอลัมน์ rejected_by ในตารางนี้ (เดิมเขียนไปแล้ว Eloquent ตัดทิ้งเงียบๆ
            //    = ไม่รู้ว่าใครปฏิเสธ) → บันทึกคนปฏิเสธต่อท้ายใน notes แทน
            $stamp = '[ปฏิเสธ '.now()->format('Y-m-d H:i').' โดยแอดมิน #'.(auth()->id() ?? '-').'] '
                .$validated['reason'];
            $oldNotes = trim((string) $commission->notes);

            $commission->update([
                'status' => 'rejected',
                'rejected_reason' => $validated['reason'],
                'notes' => $oldNotes === '' ? $stamp : $oldNotes."\n".$stamp,
                'rejected_at' => now(),
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
                'message' => 'ปฏิเสธคอมมิชชั่นล้มเหลว — ระบบบันทึกรายละเอียดไว้ใน log แล้ว',
            ], 500);
        }
    }

    /**
     * อนุมัติคอมมิชชั่นหลายรายการ
     *
     * 🚨 รายการที่ลาซาด้ายังไม่ยืนยัน = "ข้าม" ไม่ใช่ "ทำให้ผ่าน"
     *    และต้องบอกจำนวนที่ข้ามเสมอ — ห้ามหายเงียบ (แอดมินจะเข้าใจผิดว่าอนุมัติครบแล้ว)
     * 🚫 bulk ไม่มีทางลัด override เด็ดขาด (กดครั้งเดียวเงินออกหลายรายการ = อันตรายเกินไป)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|max:200', // จำกัดต่อรอบ — แต่ละ id เขียนวอลเลต 1 ครั้ง ไม่มี queue ยิงพันรายการจะ timeout กลางคัน
            'ids.*' => 'exists:marketplace_commissions,id',
        ]);

        try {
            $commissionService = new MarketplaceCommissionService;
            $guard = new AffiliateSettlementGuard;

            $count = 0;      // อนุมัติสำเร็จ
            $blocked = 0;    // ด่านตรวจไม่ผ่าน (ลาซาด้ายังไม่ยืนยัน)
            $skipped = 0;    // สถานะไม่พร้อมอนุมัติ
            $failed = 0;     // ลงมือแล้วไม่สำเร็จ

            foreach ($validated['ids'] as $id) {
                $commission = MarketplaceCommission::with('order.platform')->find($id);

                if (! $commission || $commission->status !== 'pending') {
                    $skipped++;

                    continue;
                }

                if (! $guard->check($commission)['allowed']) {
                    $blocked++;

                    continue;
                }

                // 🛡️ แถวเดียวพัง ต้องไม่ทำให้ทั้งชุดล้ม
                try {
                    $commissionService->approveCommission($commission) ? $count++ : $failed++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Bulk approve row failed: {$e->getMessage()}", ['commission_id' => $id]);
                }
            }

            return response()->json([
                'success' => $count > 0,
                'message' => $this->bulkMessage('อนุมัติ', $count, $blocked, $skipped, $failed, 'อนุมัติ'),
                'count' => $count,
                'blocked' => $blocked,
                'skipped' => $skipped,
                'failed' => $failed,
            ]);

        } catch (\Exception $e) {
            Log::error("Bulk approve failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'อนุมัติคอมมิชชั่นล้มเหลว — ระบบบันทึกรายละเอียดไว้ใน log แล้ว',
            ], 500);
        }
    }

    /**
     * จ่ายคอมมิชชั่นหลายรายการ
     *
     * 🚨 จุดนี้ "เงินออกจริงหลายรายการพร้อมกัน" — ด่านตรวจต้องรันทีละรายการ ห้ามข้าม
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkPay(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|max:200', // จำกัดต่อรอบ — แต่ละ id เขียนวอลเลต 1 ครั้ง ไม่มี queue ยิงพันรายการจะ timeout กลางคัน
            'ids.*' => 'exists:marketplace_commissions,id',
        ]);

        try {
            $commissionService = new MarketplaceCommissionService;
            $guard = new AffiliateSettlementGuard;

            $count = 0;
            $blocked = 0;
            $skipped = 0;
            $failed = 0;

            foreach ($validated['ids'] as $id) {
                $commission = MarketplaceCommission::with('order.platform')->find($id);

                if (! $commission || $commission->status !== 'approved') {
                    $skipped++;

                    continue;
                }

                if (! $guard->check($commission)['allowed']) {
                    $blocked++;

                    continue;
                }

                try {
                    $commissionService->payCommission($commission) ? $count++ : $failed++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Bulk pay row failed: {$e->getMessage()}", ['commission_id' => $id]);
                }
            }

            return response()->json([
                'success' => $count > 0,
                'message' => $this->bulkMessage('จ่าย', $count, $blocked, $skipped, $failed, 'จ่าย'),
                'count' => $count,
                'blocked' => $blocked,
                'skipped' => $skipped,
                'failed' => $failed,
            ]);

        } catch (\Exception $e) {
            Log::error("Bulk pay failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'จ่ายคอมมิชชั่นล้มเหลว — ระบบบันทึกรายละเอียดไว้ใน log แล้ว',
            ], 500);
        }
    }

    /**
     * ประกอบข้อความสรุปผล bulk — ต้องบอก "ที่ข้าม" ทุกครั้ง ห้ามเงียบ
     *
     * @param  string  $verb  คำกริยาสำหรับผลสำเร็จ เช่น "อนุมัติ"
     * @param  string  $verbSkip  คำกริยาสำหรับรายการที่ข้ามเพราะสถานะไม่พร้อม
     */
    private function bulkMessage(string $verb, int $count, int $blocked, int $skipped, int $failed, string $verbSkip): string
    {
        $parts = [$verb.' '.$count.' รายการ'];

        if ($blocked > 0) {
            $parts[] = 'ข้าม '.$blocked.' รายการที่ลาซาด้ายังไม่ยืนยัน';
        }
        if ($skipped > 0) {
            $parts[] = 'ข้าม '.$skipped.' รายการที่สถานะไม่พร้อม'.$verbSkip;
        }
        if ($failed > 0) {
            $parts[] = 'ล้มเหลว '.$failed.' รายการ';
        }

        return implode(' · ', $parts);
    }

    /**
     * ลบคอมมิชชั่น (เฉพาะที่ยังไม่จ่าย)
     *
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
