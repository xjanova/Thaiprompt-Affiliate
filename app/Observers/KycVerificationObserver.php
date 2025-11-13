<?php

namespace App\Observers;

use App\Models\KycVerification;
use App\Services\NotificationService;

class KycVerificationObserver
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the KycVerification "created" event.
     */
    public function created(KycVerification $kycVerification): void
    {
        // Notify admins about new KYC verification request
        if ($kycVerification->status === 'pending') {
            try {
                $this->notificationService->notifyAdminNewKyc($kycVerification);
            } catch (\Exception $e) {
                \Log::error('Failed to notify admin about new KYC verification: ' . $e->getMessage(), [
                    'kyc_id' => $kycVerification->id,
                ]);
            }
        }
    }

    /**
     * Handle the KycVerification "updated" event.
     */
    public function updated(KycVerification $kycVerification): void
    {
        // Notify user when KYC is approved
        if ($kycVerification->isDirty('status') && $kycVerification->status === 'approved') {
            try {
                $this->notificationService->notifyKycApproved($kycVerification->user, $kycVerification);
            } catch (\Exception $e) {
                \Log::error('Failed to notify user about KYC approval: ' . $e->getMessage(), [
                    'kyc_id' => $kycVerification->id,
                ]);
            }
        }

        // Notify user when KYC is rejected
        if ($kycVerification->isDirty('status') && $kycVerification->status === 'rejected') {
            try {
                $reason = $kycVerification->rejection_reason ?? 'ไม่ระบุเหตุผล';
                $this->notificationService->notifyKycRejected($kycVerification->user, $kycVerification, $reason);
            } catch (\Exception $e) {
                \Log::error('Failed to notify user about KYC rejection: ' . $e->getMessage(), [
                    'kyc_id' => $kycVerification->id,
                ]);
            }
        }
    }

    /**
     * Handle the KycVerification "deleted" event.
     */
    public function deleted(KycVerification $kycVerification): void
    {
        //
    }

    /**
     * Handle the KycVerification "restored" event.
     */
    public function restored(KycVerification $kycVerification): void
    {
        //
    }

    /**
     * Handle the KycVerification "force deleted" event.
     */
    public function forceDeleted(KycVerification $kycVerification): void
    {
        //
    }
}
