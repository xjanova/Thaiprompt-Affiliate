<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneCommission;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FortuneCommissionController
 *
 * จัดการคอมมิชชั่นดูดวง (แยกจาก MLM commissions)
 * - ภาพรวม: ดูสถิติ + ประวัติคอมมิชชั่นทั้งหมด
 * - จัดการ: อนุมัติ/ปฏิเสธ/จ่ายเงิน/ปรับจำนวน/Export CSV/สร้างมือ
 */
class FortuneCommissionController extends Controller
{
    /**
     * ภาพรวมคอมมิชชั่นดูดวง (Dashboard)
     *
     * แสดงสถิติ + ตารางประวัติคอมมิชชั่นทั้งหมด (filter ได้)
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึง filters
        $filters = $this->getFilters($request);

        // คำนวณสถิติ
        $stats = FortuneCommission::getStats($filters);

        // ดึงรายการคอมมิชชั่น พร้อม filter
        $commissions = $this->buildQuery($request)
            ->with(['user', 'fromUser', 'reading'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.fortune.commissions.index', [
            'commissions' => $commissions,
            'stats' => $stats,
            'filters' => $filters,
            'pageTitle' => 'ภาพรวมคอมมิชชั่นดูดวง',
        ]);
    }

    /**
     * จัดการคอมมิชชั่นดูดวง (Management)
     *
     * แสดง bulk actions + settings + ตารางจัดการ
     *
     * @return \Illuminate\View\View
     */
    public function manage(Request $request)
    {
        // ดึง filters
        $filters = $this->getFilters($request);

        // คำนวณสถิติ
        $stats = FortuneCommission::getStats($filters);

        // ดึงรายการคอมมิชชั่น พร้อม filter
        $commissions = $this->buildQuery($request)
            ->with(['user', 'fromUser', 'reading'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // ดึงการตั้งค่าคอมมิชชั่น
        $settings = FortuneTellingSetting::first();

        return view('admin.fortune.commissions.manage', [
            'commissions' => $commissions,
            'stats' => $stats,
            'filters' => $filters,
            'settings' => $settings,
            'pageTitle' => 'จัดการคอมมิชชั่นดูดวง',
        ]);
    }

    /**
     * แสดงรายละเอียดคอมมิชชั่น (JSON สำหรับ modal)
     */
    public function show(FortuneCommission $commission): JsonResponse
    {
        $commission->load(['user', 'fromUser', 'reading', 'mlmMember', 'fromMlmMember']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $commission->id,
                'user' => $commission->user ? [
                    'id' => $commission->user->id,
                    'name' => $commission->user->name,
                    'email' => $commission->user->email,
                ] : null,
                'from_user' => $commission->fromUser ? [
                    'id' => $commission->fromUser->id,
                    'name' => $commission->fromUser->name,
                    'email' => $commission->fromUser->email,
                ] : null,
                'reading' => $commission->reading ? [
                    'id' => $commission->reading->id,
                    'reading_type' => $commission->reading->reading_type ?? '-',
                    'amount_paid' => $commission->reading->amount_paid,
                    'created_at' => $commission->reading->created_at?->format('d/m/Y H:i'),
                ] : null,
                'level' => $commission->level,
                'level_name' => $commission->level_name,
                'commission_type' => $commission->commission_type,
                'commission_rate' => $commission->commission_rate,
                'amount' => $commission->amount,
                'reading_price' => $commission->reading_price,
                'status' => $commission->status,
                'status_name' => $commission->status_name,
                'notes' => $commission->notes,
                'approved_at' => $commission->approved_at?->format('d/m/Y H:i'),
                'paid_at' => $commission->paid_at?->format('d/m/Y H:i'),
                'rejected_at' => $commission->rejected_at?->format('d/m/Y H:i'),
                'created_at' => $commission->created_at?->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * อนุมัติคอมมิชชั่น (bulk)
     */
    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:fortune_commissions,id',
        ]);

        $count = 0;
        $commissions = FortuneCommission::whereIn('id', $validated['commission_ids'])->get();

        foreach ($commissions as $commission) {
            if ($commission->approve()) {
                $count++;
            }
        }

        Log::info('FortuneCommission Admin: อนุมัติ bulk', [
            'count' => $count,
            'ids' => $validated['commission_ids'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "อนุมัติ {$count} รายการสำเร็จ",
            'count' => $count,
        ]);
    }

    /**
     * ปฏิเสธคอมมิชชั่น
     */
    public function reject(Request $request, FortuneCommission $commission): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $commission->reject($validated['reason'] ?? null);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถปฏิเสธได้ (สถานะปัจจุบัน: '.$commission->status_name.')',
            ], 422);
        }

        Log::info('FortuneCommission Admin: ปฏิเสธ', [
            'commission_id' => $commission->id,
            'reason' => $validated['reason'] ?? '-',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคอมมิชชั่นสำเร็จ',
        ]);
    }

    /**
     * ปรับจำนวนเงินคอมมิชชั่น
     */
    public function adjustAmount(Request $request, FortuneCommission $commission): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldAmount = $commission->amount;
        $newAmount = (float) $validated['amount'];

        $commission->update([
            'amount' => $newAmount,
            'notes' => ($commission->notes ? $commission->notes."\n" : '')
                ."[ปรับจำนวน] {$oldAmount} → {$newAmount} บาท"
                .($validated['reason'] ? " เหตุผล: {$validated['reason']}" : ''),
        ]);

        Log::info('FortuneCommission Admin: ปรับจำนวนเงิน', [
            'commission_id' => $commission->id,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'reason' => $validated['reason'] ?? '-',
        ]);

        return response()->json([
            'success' => true,
            'message' => "ปรับจำนวนเงินจาก {$oldAmount} เป็น {$newAmount} บาท สำเร็จ",
        ]);
    }

    /**
     * จ่ายคอมมิชชั่นเข้า wallet (bulk)
     *
     * ใช้ pattern เดียวกับ FortuneCommissionService::depositToWallet()
     * (bypass WalletService, สร้าง Wallet + WalletTransaction ตรง)
     */
    public function payOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:fortune_commissions,id',
        ]);

        $count = 0;
        $errors = [];

        $commissions = FortuneCommission::with('user')
            ->whereIn('id', $validated['commission_ids'])
            ->whereIn('status', [FortuneCommission::STATUS_PENDING, FortuneCommission::STATUS_APPROVED])
            ->get();

        foreach ($commissions as $commission) {
            try {
                DB::transaction(function () use ($commission, &$count) {
                    $userId = $commission->user_id;
                    $amount = (float) $commission->amount;

                    if ($amount <= 0) {
                        return;
                    }

                    // หา wallet หรือสร้างใหม่ (bypass WalletService)
                    $wallet = Wallet::where('user_id', $userId)->first();
                    if (! $wallet) {
                        $wallet = Wallet::create([
                            'user_id' => $userId,
                            'balance' => 0,
                            'currency' => 'THB',
                            'status' => 'active',
                        ]);
                        $wallet->refresh();
                    }

                    if ($wallet->status !== 'active') {
                        throw new \Exception("Wallet ไม่ active สำหรับ user {$userId}");
                    }

                    $balanceBefore = (float) $wallet->balance;
                    $balanceAfter = $balanceBefore + $amount;

                    // สร้าง WalletTransaction
                    $transaction = WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'user_id' => $wallet->user_id,
                        'type' => 'deposit',
                        'amount' => $amount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'currency' => $wallet->currency,
                        'description' => "จ่ายคอมมิชชั่นดูดวง L{$commission->level} #{$commission->id} (Admin)",
                        'reference_type' => FortuneCommission::class,
                        'reference_id' => $commission->id,
                        'status' => 'completed',
                        'metadata' => [
                            'commission_id' => $commission->id,
                            'level' => $commission->level,
                            'mode' => 'fortune_commission_admin_payout',
                        ],
                        'completed_at' => now(),
                    ]);

                    // อัพเดท wallet balance
                    $wallet->update([
                        'balance' => $balanceAfter,
                        'total_income' => (float) ($wallet->total_income ?? 0) + $amount,
                        'last_transaction_at' => now(),
                    ]);

                    // อัพเดท commission status
                    $commission->update([
                        'status' => FortuneCommission::STATUS_PAID,
                        'paid_at' => now(),
                        'wallet_transaction_id' => $transaction->id,
                    ]);

                    $count++;
                });
            } catch (\Exception $e) {
                $errors[] = "Commission #{$commission->id}: {$e->getMessage()}";
                Log::error('FortuneCommission Admin: จ่ายล้มเหลว', [
                    'commission_id' => $commission->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = "จ่ายเงิน {$count} รายการสำเร็จ";
        if (count($errors) > 0) {
            $message .= ' (ล้มเหลว '.count($errors).' รายการ)';
        }

        return response()->json([
            'success' => $count > 0,
            'message' => $message,
            'count' => $count,
            'errors' => $errors,
        ]);
    }

    /**
     * Export CSV คอมมิชชั่นดูดวง
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $commissions = $this->buildQuery($request)
            ->with(['user', 'fromUser', 'reading'])
            ->orderBy('created_at', 'desc')
            ->get();

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $filename = "fortune_commissions_{$dateFrom}_to_{$dateTo}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($commissions) {
            $file = fopen('php://output', 'w');

            // BOM สำหรับ UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // หัวตาราง
            fputcsv($file, [
                'ID',
                'วันที่',
                'ผู้รับคอมมิชชั่น',
                'Email ผู้รับ',
                'จากผู้ใช้',
                'บิลดูดวง #',
                'ชั้น',
                'ประเภท',
                'อัตรา',
                'จำนวน (บาท)',
                'ราคาดูดวง',
                'สถานะ',
                'วันที่จ่าย',
                'หมายเหตุ',
            ]);

            // ข้อมูล
            foreach ($commissions as $c) {
                fputcsv($file, [
                    $c->id,
                    $c->created_at?->format('Y-m-d H:i:s'),
                    $c->user?->name ?? '-',
                    $c->user?->email ?? '-',
                    $c->fromUser?->name ?? '-',
                    $c->fortune_reading_id,
                    $c->level === 1 ? 'L1 (สายตรง)' : 'L2 (ชั้นหลาน)',
                    $c->commission_type === 'percent' ? 'เปอร์เซ็นต์' : 'คงที่',
                    $c->commission_rate,
                    $c->amount,
                    $c->reading_price,
                    $c->status_name,
                    $c->paid_at?->format('Y-m-d H:i:s') ?? '-',
                    $c->notes ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * อัพเดทการตั้งค่าคอมมิชชั่น L1/L2
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fortune_level1_commission_type' => 'required|in:fixed,percent',
            'fortune_level1_commission_amount' => 'required|numeric|min:0',
            'fortune_level2_enabled' => 'required|boolean',
            'fortune_level2_commission_type' => 'required|in:fixed,percent',
            'fortune_level2_commission_amount' => 'required|numeric|min:0',
        ]);

        $settings = FortuneTellingSetting::first();
        if (! $settings) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบการตั้งค่าดูดวง',
            ], 404);
        }

        $settings->update($validated);

        Log::info('FortuneCommission Admin: อัพเดทการตั้งค่า', $validated);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าสำเร็จ',
        ]);
    }

    /**
     * สร้างคอมมิชชั่นด้วยตนเอง (Manual)
     */
    public function createManual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'from_user_id' => 'required|exists:users,id',
            'fortune_reading_id' => 'nullable|exists:fortune_readings,id',
            'level' => 'required|in:1,2',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        // หา MLM member IDs (ถ้ามี)
        $mlmMemberId = MlmMember::where('user_id', $validated['user_id'])->value('id');
        $fromMlmMemberId = MlmMember::where('user_id', $validated['from_user_id'])->value('id');

        $commission = FortuneCommission::create([
            'user_id' => $validated['user_id'],
            'from_user_id' => $validated['from_user_id'],
            'fortune_reading_id' => $validated['fortune_reading_id'] ?? null,
            'mlm_member_id' => $mlmMemberId,
            'from_mlm_member_id' => $fromMlmMemberId,
            'level' => (int) $validated['level'],
            'commission_type' => 'fixed',
            'commission_rate' => (float) $validated['amount'],
            'amount' => (float) $validated['amount'],
            'reading_price' => 0,
            'status' => FortuneCommission::STATUS_PENDING,
            'notes' => '[สร้างด้วยมือ] '.($validated['notes'] ?? 'สร้างโดย Admin'),
        ]);

        Log::info('FortuneCommission Admin: สร้างด้วยมือ', [
            'commission_id' => $commission->id,
            'user_id' => $validated['user_id'],
            'amount' => $validated['amount'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "สร้างคอมมิชชั่น #{$commission->id} สำเร็จ",
            'data' => $commission,
        ]);
    }

    // ===== ผังสายงานดูดวง =====

    /**
     * แสดงผังสายงานดูดวง (Referral Tree)
     *
     * แสดงโครงสร้าง sponsor → ผู้ถูกแนะนำ เฉพาะระบบดูดวง
     * พร้อมข้อมูลคอมมิชชั่น L1/L2 ในแต่ละ node
     *
     * @return \Illuminate\View\View
     */
    public function referralTree(Request $request)
    {
        // ดึงรายชื่อสมาชิกที่มีคอมมิชชั่นดูดวง (เป็น sponsor หรือ receiver)
        $memberIds = FortuneCommission::select('mlm_member_id')
            ->whereNotNull('mlm_member_id')
            ->distinct()
            ->pluck('mlm_member_id')
            ->merge(
                FortuneCommission::select('from_mlm_member_id')
                    ->whereNotNull('from_mlm_member_id')
                    ->distinct()
                    ->pluck('from_mlm_member_id')
            )
            ->unique()
            ->values();

        $members = MlmMember::with('user')
            ->whereIn('id', $memberIds)
            ->orWhereHas('directReferrals', function ($q) use ($memberIds) {
                $q->whereIn('id', $memberIds);
            })
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        // ถ้าไม่มีสมาชิกที่เกี่ยวข้อง ดึงทั้งหมด
        if ($members->isEmpty()) {
            $members = MlmMember::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();
        }

        return view('admin.fortune.referral-tree.index', [
            'members' => $members,
            'pageTitle' => 'ผังสายงานดูดวง',
        ]);
    }

    /**
     * ดึงข้อมูล tree สำหรับแสดงผัง (JSON API)
     *
     * สร้าง tree data จาก MlmMember + fortune commission info
     */
    public function getReferralTreeData(MlmMember $member, Request $request): JsonResponse
    {
        $maxDepth = min((int) $request->get('depth', 5), 10);

        try {
            $treeData = $this->buildFortuneTree($member, $maxDepth);

            return response()->json([
                'success' => true,
                'data' => $treeData,
            ]);
        } catch (\Exception $e) {
            Log::error('Fortune referral tree error', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดผังสายงาน',
            ], 500);
        }
    }

    /**
     * สร้าง tree data สำหรับผังสายงานดูดวง
     *
     * @param  MlmMember  $member  root member
     * @param  int  $maxDepth  ความลึกสูงสุด
     * @param  int  $currentDepth  ความลึกปัจจุบัน
     */
    private function buildFortuneTree(MlmMember $member, int $maxDepth, int $currentDepth = 0): array
    {
        // 🐛 Fix 2026-07-24: เดิมอ่าน field ที่ไม่มีจริงบน MlmMember
        // (personal_pv → PV โชว์ 0 เสมอ, rank_name → โชว์ '-' เสมอ)
        // คอลัมน์จริงคือ total_pv และ rank ต้องอ่านผ่าน user->currentRank
        $member->loadMissing('user.currentRank');

        // ดึงสรุปคอมมิชชั่นดูดวงของสมาชิกคนนี้
        $commissionStats = FortuneCommission::where('user_id', $member->user_id)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount')
            ->first();

        $node = [
            'id' => $member->id,
            'name' => $member->user?->name ?? 'Unknown',
            'code' => $member->member_code ?? '-',
            'avatar' => $member->user?->profile_picture_url,
            'status' => $member->status ?? 'active',
            'rank' => $member->user?->currentRank?->name ?? '-',
            'pv' => (float) ($member->total_pv ?? 0),
            'total_earnings' => (float) ($commissionStats->total_amount ?? 0),
            'commission_count' => (int) ($commissionStats->total_count ?? 0),
            'children' => [],
        ];

        // ดึง children ถ้ายังไม่เกิน depth
        if ($currentDepth < $maxDepth) {
            $children = MlmMember::with('user.currentRank')
                ->where('original_sponsor_id', $member->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($children as $child) {
                $node['children'][] = $this->buildFortuneTree($child, $maxDepth, $currentDepth + 1);
            }
        }

        return $node;
    }

    // ===== Private Methods =====

    /**
     * ดึง filters จาก request
     */
    private function getFilters(Request $request): array
    {
        return [
            'status' => $request->input('status', 'all'),
            'level' => $request->input('level', 'all'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
        ];
    }

    /**
     * สร้าง query จาก filters
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildQuery(Request $request)
    {
        $query = FortuneCommission::query();

        // กรองตามสถานะ
        $status = $request->input('status');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // กรองตามชั้น
        $level = $request->input('level');
        if ($level && $level !== 'all') {
            $query->where('level', (int) $level);
        }

        // กรองตามวันที่
        $dateFrom = $request->input('date_from');
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        $dateTo = $request->input('date_to');
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // ค้นหาชื่อ/email
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQ) use ($search) {
                    $userQ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    ->orWhereHas('fromUser', function ($userQ) use ($search) {
                        $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
}
