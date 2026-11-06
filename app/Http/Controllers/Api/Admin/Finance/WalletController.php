<?php

namespace App\Http\Controllers\Api\Admin\Finance;

use App\Exceptions\WalletAdminException;
use App\Http\Controllers\Api\Admin\Concerns\FlatPaging;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\WalletResource;
use App\Http\Resources\Admin\WalletTransactionResource;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Mobile API: Finance/WalletController
 *
 * จัดการ wallet ของผู้ใช้สำหรับ Admin Mobile App
 * Wrap ของ WalletService (ที่ web admin ใช้)
 */
class WalletController extends Controller
{
    use FlatPaging;

    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * GET /api/admin/finance/wallets
     *
     * คืนรายชื่อ wallet พร้อม pagination
     * Query: ?search=&status=&min_balance=&max_balance=&page=
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'min_balance', 'max_balance', 'user_id']);
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $wallets = $this->walletService->getAllWallets($filters, $perPage);

        // 🩹 (2026-10-06) เดิมเรียก ->load() บน paginator → Laravel ส่งต่อไปที่ collection แล้วคืน collection
        //   ⇒ meta/links หาย แอปแบ่งหน้าไม่ได้ · ตอนนี้โหลด relation บน collection ข้างใน แล้วส่ง paginator เอง
        //   (เพิ่มอย่างเดียว: data เหมือนเดิม + links/meta + ฟิลด์ paging แบบแบน)
        $wallets->getCollection()->loadMissing('user');

        return response()->json([
            'success' => true,
            'data' => $this->withFlatPaging(
                WalletResource::collection($wallets)->response()->getData(true),
                $wallets
            ),
        ]);
    }

    /**
     * GET /api/admin/finance/wallets/system-stats
     *
     * คืนสถิติระบบ wallet รวม
     */
    public function systemStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->walletService->getSystemStatistics(),
        ]);
    }

    /**
     * GET /api/admin/finance/wallets/transactions
     *
     * คืน transaction ทั้งระบบ พร้อม filter
     */
    public function transactions(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'type', 'status', 'wallet_id', 'date_from', 'date_to']);
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $transactions = $this->walletService->getAllTransactions($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => WalletTransactionResource::collection($transactions)->response()->getData(true),
        ]);
    }

    /**
     * GET /api/admin/finance/wallets/{wallet}
     */
    public function show(Wallet $wallet): JsonResponse
    {
        $wallet->load('user');

        $stats = $this->walletService->getWalletStatistics($wallet);

        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => new WalletResource($wallet),
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * POST /api/admin/finance/wallets/{wallet}/adjust
     *
     * Body: { amount: number (not 0), reason: string, expected_balance?: number }
     * Header (บังคับ): Idempotency-Key: <uuid> (1–64 ตัวอักษร) — สร้างใหม่ต่อ "การกดยืนยัน 1 ครั้ง"
     *   แล้วใช้คีย์เดิมตอน retry · ส่งซ้ำด้วยคีย์เดิมจะไม่ปรับยอดซ้ำ (data.replayed = true)
     *   ไม่ส่ง = 422 error_code IDEMPOTENCY_KEY_REQUIRED (2026-11-06: เดิมไม่บังคับ แอปเน็ตหลุดแล้ว retry = ปรับยอด 2 รอบ)
     */
    public function adjustBalance(Wallet $wallet, Request $request): JsonResponse
    {
        $admin = $request->user();

        // ตรวจสอบ permission
        if (! $this->canManageWallets($admin)) {
            return $this->forbidden();
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '' || mb_strlen($idempotencyKey) > 64) {
            return response()->json([
                'success' => false,
                'message' => 'ต้องส่ง Idempotency-Key (UUID ไม่เกิน 64 ตัวอักษร) ใน header ทุกครั้งที่ปรับยอด — กันกดซ้ำ/ส่งซ้ำแล้วปรับยอด 2 รอบ',
                'error_code' => 'IDEMPOTENCY_KEY_REQUIRED',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'not_in:0', 'decimal:0,2', 'between:-'.WalletService::ADMIN_MAX_AMOUNT.','.WalletService::ADMIN_MAX_AMOUNT],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'expected_balance' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $transaction = $this->walletService->adjustBalance(
                wallet: $wallet,
                amount: (float) $request->amount,
                reason: $request->reason,
                admin: $admin,
                idempotencyKey: $idempotencyKey,
                expectedBalance: $request->filled('expected_balance') ? (float) $request->expected_balance : null
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'wallet' => new WalletResource($wallet->fresh('user')),
                    'transaction_id' => $transaction->transaction_id,
                    'replayed' => ! $transaction->wasRecentlyCreated,
                ],
                'message' => $transaction->wasRecentlyCreated ? 'ปรับยอดสำเร็จ' : 'รายการนี้ถูกบันทึกไปแล้ว — ไม่ได้ปรับซ้ำ',
            ]);
        } catch (WalletAdminException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Admin API wallet adjust failed', ['wallet_id' => $wallet->id, 'admin_id' => $admin->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ปรับยอดไม่สำเร็จ ระบบยังไม่ได้แตะยอดเงิน กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * POST /api/admin/finance/wallets/{wallet}/lock
     */
    public function lock(Wallet $wallet, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canManageWallets($admin)) {
            return $this->forbidden();
        }

        try {
            $wallet->update([
                'status' => 'locked',
                'locked_until' => now()->addYears(10),
            ]);

            // 🐛 (2026-11-04) เดิมส่ง action 'lock' (ไม่อยู่ใน enum) + ส่ง array ในช่อง severity → TypeError ทุกครั้ง
            $this->walletService->logAction(
                $wallet,
                'wallet_locked',
                'แอดมินล็อกกระเป๋า: '.$admin->name,
                'warning',
                ['admin_id' => $admin->id]
            );

            return response()->json([
                'success' => true,
                'data' => ['wallet' => new WalletResource($wallet->fresh('user'))],
                'message' => 'ล็อก wallet สำเร็จ',
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin API wallet lock failed', ['wallet_id' => $wallet->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'ล็อก wallet ไม่สำเร็จ กรุณาลองใหม่'], 500);
        }
    }

    /**
     * POST /api/admin/finance/wallets/{wallet}/unlock
     */
    public function unlock(Wallet $wallet, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canManageWallets($admin)) {
            return $this->forbidden();
        }

        $wallet->update([
            'status' => 'active',
            'locked_until' => null,
            'failed_attempts' => 0,
        ]);

        $this->walletService->logAction($wallet, 'wallet_unlocked', 'แอดมินปลดล็อกกระเป๋า: '.$admin->name, 'warning', ['admin_id' => $admin->id]);

        return response()->json([
            'success' => true,
            'data' => ['wallet' => new WalletResource($wallet->fresh('user'))],
            'message' => 'ปลดล็อก wallet สำเร็จ',
        ]);
    }

    /**
     * POST /api/admin/finance/wallets/{wallet}/suspend
     */
    public function suspend(Wallet $wallet, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canManageWallets($admin)) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->walletService->suspendWallet($wallet, $request->reason, $admin);

            return response()->json([
                'success' => true,
                'data' => ['wallet' => new WalletResource($wallet->fresh('user'))],
                'message' => 'ระงับ wallet สำเร็จ',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/finance/wallets/{wallet}/unsuspend
     */
    public function unsuspend(Wallet $wallet, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canManageWallets($admin)) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->walletService->unsuspendWallet($wallet, $request->reason, $admin);

            return response()->json([
                'success' => true,
                'data' => ['wallet' => new WalletResource($wallet->fresh('user'))],
                'message' => 'ยกเลิกระงับ wallet สำเร็จ',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function canManageWallets($admin): bool
    {
        // Super admin ได้หมด
        if ($admin->is_super_admin ?? false) {
            return true;
        }
        // ถ้ามี hasPermission method ตรวจ permission เฉพาะ
        if (method_exists($admin, 'hasPermission')) {
            return $admin->hasPermission('view_all_wallets')
                || $admin->hasPermission('manage_wallets');
        }

        // fallback: admin role
        return ($admin->role ?? null) === 'admin';
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ไม่มีสิทธิ์จัดการ wallet',
            'error_code' => 'PERMISSION_DENIED',
        ], 403);
    }
}
