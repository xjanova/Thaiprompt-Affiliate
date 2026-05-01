<?php

namespace App\Http\Controllers\Api\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\WalletResource;
use App\Http\Resources\Admin\WalletTransactionResource;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Mobile API: Finance/WalletController
 *
 * จัดการ wallet ของผู้ใช้สำหรับ Admin Mobile App
 * Wrap ของ WalletService (ที่ web admin ใช้)
 */
class WalletController extends Controller
{
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

        return response()->json([
            'success' => true,
            'data' => WalletResource::collection($wallets->load('user'))->response()->getData(true),
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
     * Body: { amount: number (not 0), reason: string }
     */
    public function adjustBalance(Wallet $wallet, Request $request): JsonResponse
    {
        $admin = $request->user();

        // ตรวจสอบ permission
        if (! $this->canManageWallets($admin)) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'not_in:0'],
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
            $this->walletService->adjustBalance(
                wallet: $wallet,
                amount: (float) $request->amount,
                reason: $request->reason,
                admin: $admin
            );

            return response()->json([
                'success' => true,
                'data' => ['wallet' => new WalletResource($wallet->fresh('user'))],
                'message' => 'ปรับยอดสำเร็จ',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ปรับยอดไม่สำเร็จ: '.$e->getMessage(),
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

            $this->walletService->logAction(
                $wallet,
                'lock',
                'Admin locked wallet',
                ['admin_id' => $admin->id]
            );

            return response()->json([
                'success' => true,
                'data' => ['wallet' => new WalletResource($wallet->fresh('user'))],
                'message' => 'ล็อก wallet สำเร็จ',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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

        $this->walletService->logAction($wallet, 'unlock', 'Admin unlocked wallet', ['admin_id' => $admin->id]);

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
