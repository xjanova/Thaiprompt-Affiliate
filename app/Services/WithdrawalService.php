<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Models\PaymentMethod;
use App\Models\WalletSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class WithdrawalService
{
    protected $walletService;
    protected $notificationService;

    public function __construct(WalletService $walletService, NotificationService $notificationService)
    {
        $this->walletService = $walletService;
        $this->notificationService = $notificationService;
    }

    /**
     * Create withdrawal request
     */
    public function createWithdrawalRequest(
        User $user,
        float $amount,
        ?int $paymentMethodId = null,
        ?string $userNote = null
    ): WithdrawalRequest {
        $wallet = $this->walletService->getOrCreateWallet($user);

        // Validate wallet
        if (!$wallet->isActive()) {
            throw new Exception('กระเป๋าเงินของคุณไม่สามารถใช้งานได้');
        }

        // Validate amount
        $errors = WalletSetting::validateWithdrawalAmount($amount);
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        // Calculate fees and tax
        $fee = WalletSetting::calculateWithdrawalFee($amount);
        $tax = WalletSetting::calculateWithdrawalTax($amount);
        $netAmount = $amount - $fee - $tax;

        // Check balance
        if ($wallet->balance < $amount) {
            throw new Exception('ยอดเงินในกระเป๋าไม่เพียงพอ');
        }

        // Get payment method
        $paymentMethod = null;
        $paymentType = null;
        $paymentDetails = null;

        if ($paymentMethodId) {
            $paymentMethod = PaymentMethod::where('id', $paymentMethodId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->firstOrFail();

            $paymentType = $paymentMethod->type;
            $paymentDetails = $this->getPaymentMethodDetails($paymentMethod);
        }

        return DB::transaction(function () use (
            $user,
            $wallet,
            $amount,
            $fee,
            $tax,
            $netAmount,
            $paymentMethod,
            $paymentType,
            $paymentDetails,
            $userNote
        ) {
            // Create withdrawal request
            $request = WithdrawalRequest::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'fee' => $fee,
                'tax' => $tax,
                'net_amount' => $netAmount,
                'currency' => $wallet->currency,
                'status' => 'pending',
                'payment_method_id' => $paymentMethod?->id,
                'payment_type' => $paymentType,
                'payment_details' => $paymentDetails,
                'user_note' => $userNote,
            ]);

            // Reserve balance (deduct from wallet)
            $this->walletService->withdraw(
                $wallet,
                $amount,
                '', // PIN not required for withdrawal request
                "คำขอถอนเงิน #{$request->request_id}",
                'withdrawal_request',
                $request->id
            );

            // Check if auto-approve is enabled
            if (!WalletSetting::withdrawalRequiresApproval($amount)) {
                $this->approveWithdrawal($request, null);
            } else {
                // Notify user
                $this->notificationService->notifyWithdrawalRequest($user, $request);

                // Notify admins
                $this->notificationService->notifyAdminNewWithdrawal($request);
            }

            // Log action
            $this->walletService->logAction(
                $wallet,
                'transaction_attempt',
                "Withdrawal request created: {$request->request_id}",
                'info',
                ['request_id' => $request->id]
            );

            return $request;
        });
    }

    /**
     * Approve withdrawal request
     */
    public function approveWithdrawal(WithdrawalRequest $request, ?User $admin): void
    {
        if (!$request->isPending() && !$request->isProcessing()) {
            throw new Exception('คำขอถอนเงินนี้ไม่สามารถอนุมัติได้');
        }

        DB::transaction(function () use ($request, $admin) {
            $request->update([
                'status' => 'approved',
                'approved_by' => $admin?->id,
                'approved_at' => now(),
            ]);

            // Notify user
            $this->notificationService->notifyWithdrawalApproved($request->user, $request);

            // Log action
            $this->walletService->logAction(
                $request->wallet,
                'transaction_success',
                "Withdrawal approved: {$request->request_id}",
                'info',
                ['request_id' => $request->id, 'admin_id' => $admin?->id]
            );
        });
    }

    /**
     * Reject withdrawal request
     */
    public function rejectWithdrawal(WithdrawalRequest $request, User $admin, string $reason): void
    {
        if (!$request->isPending() && !$request->isProcessing()) {
            throw new Exception('คำขอถอนเงินนี้ไม่สามารถปฏิเสธได้');
        }

        DB::transaction(function () use ($request, $admin, $reason) {
            $request->update([
                'status' => 'rejected',
                'rejected_by' => $admin->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // Refund balance to user
            $this->walletService->deposit(
                $request->wallet,
                $request->amount,
                "คืนเงินจากคำขอถอนเงินที่ถูกปฏิเสธ #{$request->request_id}",
                'withdrawal_refund',
                $request->id
            );

            // Notify user
            $this->notificationService->notifyWithdrawalRejected($request->user, $request, $reason);

            // Log action
            $this->walletService->logAction(
                $request->wallet,
                'transaction_failed',
                "Withdrawal rejected: {$request->request_id}",
                'warning',
                ['request_id' => $request->id, 'admin_id' => $admin->id, 'reason' => $reason]
            );
        });
    }

    /**
     * Complete withdrawal with transfer slip
     */
    public function completeWithdrawal(
        WithdrawalRequest $request,
        User $admin,
        $transferSlipFile,
        ?string $transferNote = null
    ): void {
        if (!$request->isApproved()) {
            throw new Exception('คำขอถอนเงินนี้ยังไม่ได้รับการอนุมัติ');
        }

        DB::transaction(function () use ($request, $admin, $transferSlipFile, $transferNote) {
            // Upload transfer slip
            $slipPath = null;
            if ($transferSlipFile) {
                $slipPath = $transferSlipFile->store('withdrawal-slips', 'public');
            }

            $request->update([
                'status' => 'completed',
                'transfer_slip' => $slipPath,
                'transfer_note' => $transferNote,
                'transfer_completed_at' => now(),
            ]);

            // Notify user
            $this->notificationService->notifyWithdrawalCompleted($request->user, $request);

            // Log action
            $this->walletService->logAction(
                $request->wallet,
                'transaction_success',
                "Withdrawal completed: {$request->request_id}",
                'info',
                ['request_id' => $request->id, 'admin_id' => $admin->id]
            );
        });
    }

    /**
     * Cancel withdrawal request (by user)
     */
    public function cancelWithdrawal(WithdrawalRequest $request, User $user): void
    {
        if (!$request->isPending()) {
            throw new Exception('ไม่สามารถยกเลิกคำขอถอนเงินนี้ได้');
        }

        if ($request->user_id !== $user->id) {
            throw new Exception('คุณไม่มีสิทธิ์ยกเลิกคำขอถอนเงินนี้');
        }

        DB::transaction(function () use ($request) {
            $request->update([
                'status' => 'cancelled',
            ]);

            // Refund balance to user
            $this->walletService->deposit(
                $request->wallet,
                $request->amount,
                "คืนเงินจากการยกเลิกคำขอถอนเงิน #{$request->request_id}",
                'withdrawal_cancelled',
                $request->id
            );

            // Log action
            $this->walletService->logAction(
                $request->wallet,
                'transaction_failed',
                "Withdrawal cancelled by user: {$request->request_id}",
                'info',
                ['request_id' => $request->id]
            );
        });
    }

    /**
     * Get payment method details for withdrawal
     */
    protected function getPaymentMethodDetails(PaymentMethod $method): array
    {
        $details = [
            'type' => $method->type,
            'name' => $method->name,
        ];

        if ($method->isPromptPay() || $method->isBankTransfer()) {
            $details['account_name'] = $method->account_name;
            $details['account_number'] = $method->account_number;
            if ($method->bank_name) {
                $details['bank_name'] = $method->bank_name;
            }
        } elseif ($method->isPayPal()) {
            $details['paypal_email'] = $method->paypal_email;
        }

        return $details;
    }

    /**
     * Get user's withdrawal requests
     */
    public function getUserWithdrawals(User $user, int $perPage = 20)
    {
        return WithdrawalRequest::where('user_id', $user->id)
            ->with(['paymentMethod', 'approvedBy', 'rejectedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get pending withdrawal requests for admin
     */
    public function getPendingWithdrawals(int $perPage = 20)
    {
        return WithdrawalRequest::pending()
            ->with(['user', 'wallet', 'paymentMethod'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get all withdrawal requests for admin
     */
    public function getAllWithdrawals(array $filters = [], int $perPage = 20)
    {
        $query = WithdrawalRequest::with(['user', 'wallet', 'paymentMethod', 'approvedBy', 'rejectedBy']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get withdrawal statistics
     */
    public function getWithdrawalStatistics(User $user): array
    {
        $requests = WithdrawalRequest::where('user_id', $user->id);

        return [
            'total_requested' => $requests->sum('amount'),
            'total_completed' => $requests->completed()->sum('net_amount'),
            'total_pending' => $requests->pending()->sum('amount'),
            'total_fees' => $requests->completed()->sum('fee'),
            'total_tax' => $requests->completed()->sum('tax'),
            'count_pending' => $requests->pending()->count(),
            'count_approved' => $requests->approved()->count(),
            'count_completed' => $requests->completed()->count(),
            'count_rejected' => $requests->rejected()->count(),
        ];
    }
}
