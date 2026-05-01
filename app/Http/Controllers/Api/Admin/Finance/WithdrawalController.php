<?php

namespace App\Http\Controllers\Api\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\WithdrawalResource;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Mobile API: Finance/WithdrawalController
 *
 * จัดการคำขอถอนเงินสำหรับ Admin Mobile App
 * Wrap ของ WithdrawalService
 */
class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService
    ) {}

    /**
     * GET /api/admin/finance/withdrawals
     *
     * Query: ?status=&search=&date_from=&date_to=&page=
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search', 'user_id', 'date_from', 'date_to']);
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $withdrawals = $this->withdrawalService->getAllWithdrawals($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => WithdrawalResource::collection($withdrawals->load(['user', 'paymentMethod']))
                ->response()->getData(true),
        ]);
    }

    /**
     * GET /api/admin/finance/withdrawals/pending
     */
    public function pending(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $withdrawals = $this->withdrawalService->getPendingWithdrawals($perPage);

        return response()->json([
            'success' => true,
            'data' => WithdrawalResource::collection($withdrawals->load(['user', 'paymentMethod']))
                ->response()->getData(true),
        ]);
    }

    /**
     * GET /api/admin/finance/withdrawals/{withdrawal}
     */
    public function show(WithdrawalRequest $withdrawal): JsonResponse
    {
        $withdrawal->load(['user', 'paymentMethod', 'wallet']);

        return response()->json([
            'success' => true,
            'data' => ['withdrawal' => new WithdrawalResource($withdrawal)],
        ]);
    }

    /**
     * POST /api/admin/finance/withdrawals/{withdrawal}/approve
     */
    public function approve(WithdrawalRequest $withdrawal, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canApprove($admin)) {
            return $this->forbidden();
        }

        try {
            $this->withdrawalService->approveWithdrawal($withdrawal, $admin);

            return response()->json([
                'success' => true,
                'data' => ['withdrawal' => new WithdrawalResource($withdrawal->fresh(['user', 'paymentMethod']))],
                'message' => 'อนุมัติคำขอถอนเงินสำเร็จ',
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin API: approve withdrawal failed', [
                'withdrawal_id' => $withdrawal->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'อนุมัติไม่สำเร็จ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/admin/finance/withdrawals/{withdrawal}/reject
     *
     * Body: { reason: string }
     */
    public function reject(WithdrawalRequest $withdrawal, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canApprove($admin)) {
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
            $this->withdrawalService->rejectWithdrawal($withdrawal, $admin, $request->reason);

            return response()->json([
                'success' => true,
                'data' => ['withdrawal' => new WithdrawalResource($withdrawal->fresh(['user', 'paymentMethod']))],
                'message' => 'ปฏิเสธคำขอถอนเงินสำเร็จ',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/finance/withdrawals/{withdrawal}/complete
     *
     * multipart/form-data:
     *   transfer_slip (file, image): สลิปโอนเงิน (จะแปลงเป็น WebP)
     *   transfer_note (string): หมายเหตุ
     */
    public function complete(WithdrawalRequest $withdrawal, Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canApprove($admin)) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'transfer_slip' => ['nullable', 'file', 'image', 'max:5120'],   // 5MB
            'transfer_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->withdrawalService->completeWithdrawal(
                $withdrawal,
                $admin,
                $request->file('transfer_slip'),
                $request->input('transfer_note')
            );

            return response()->json([
                'success' => true,
                'data' => ['withdrawal' => new WithdrawalResource($withdrawal->fresh(['user', 'paymentMethod']))],
                'message' => 'บันทึกการโอนเงินสำเร็จ',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/finance/withdrawals/batch-approve
     *
     * Body: { ids: number[] }
     */
    public function batchApprove(Request $request): JsonResponse
    {
        $admin = $request->user();
        if (! $this->canApprove($admin)) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $approved = 0;
        $failed = [];

        foreach (WithdrawalRequest::whereIn('id', $request->ids)->where('status', 'pending')->get() as $w) {
            try {
                $this->withdrawalService->approveWithdrawal($w, $admin);
                $approved++;
            } catch (\Throwable $e) {
                $failed[] = ['id' => $w->id, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'approved_count' => $approved,
                'failed' => $failed,
            ],
            'message' => "อนุมัติ {$approved} รายการสำเร็จ",
        ]);
    }

    private function canApprove($admin): bool
    {
        if ($admin->is_super_admin ?? false) {
            return true;
        }
        if (method_exists($admin, 'hasPermission')) {
            return $admin->hasPermission('approve_withdrawals');
        }

        return ($admin->role ?? null) === 'admin';
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ไม่มีสิทธิ์จัดการคำขอถอนเงิน',
            'error_code' => 'PERMISSION_DENIED',
        ], 403);
    }
}
