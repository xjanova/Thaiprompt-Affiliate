<?php

namespace App\Observers;

use App\Models\KycVerification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                \Log::error('Failed to notify admin about new KYC verification: '.$e->getMessage(), [
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
                \Log::error('Failed to notify user about KYC approval: '.$e->getMessage(), [
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
                \Log::error('Failed to notify user about KYC rejection: '.$e->getMessage(), [
                    'kyc_id' => $kycVerification->id,
                ]);
            }
        }
    }

    /**
     * Handle the KycVerification "deleting" event.
     *
     * ลบรูปภาพ KYC ก่อนที่จะลบ record ออกจากฐานข้อมูล
     * เพื่อปกป้องความเป็นส่วนตัวของผู้ใช้ตามนโยบายการจัดเก็บข้อมูล
     */
    public function deleting(KycVerification $kycVerification): void
    {
        try {
            $this->deleteKycImages($kycVerification);
        } catch (\Exception $e) {
            Log::error('Failed to delete KYC images during deleting event: '.$e->getMessage(), [
                'kyc_id' => $kycVerification->id,
                'user_id' => $kycVerification->user_id,
            ]);
        }
    }

    /**
     * Handle the KycVerification "deleted" event.
     */
    public function deleted(KycVerification $kycVerification): void
    {
        Log::info('KYC verification deleted along with images', [
            'kyc_id' => $kycVerification->id,
            'user_id' => $kycVerification->user_id,
        ]);
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
        // ลบรูปภาพอีกครั้งสำหรับ force delete (กรณี soft delete แล้ว restore แล้ว force delete)
        try {
            $this->deleteKycImages($kycVerification);
        } catch (\Exception $e) {
            Log::error('Failed to delete KYC images during force delete: '.$e->getMessage(), [
                'kyc_id' => $kycVerification->id,
            ]);
        }
    }

    /**
     * ลบรูปภาพ KYC ออกจาก storage
     */
    protected function deleteKycImages(KycVerification $kycVerification): void
    {
        $deletedFiles = [];

        // ลบรูปบัตรประชาชน/ใบขับขี่
        if ($kycVerification->id_card_image) {
            if (Storage::disk('public')->exists($kycVerification->id_card_image)) {
                Storage::disk('public')->delete($kycVerification->id_card_image);
                $deletedFiles[] = $kycVerification->id_card_image;
            }
        }

        // ลบรูป selfie
        if ($kycVerification->selfie_image) {
            if (Storage::disk('public')->exists($kycVerification->selfie_image)) {
                Storage::disk('public')->delete($kycVerification->selfie_image);
                $deletedFiles[] = $kycVerification->selfie_image;
            }
        }

        if (! empty($deletedFiles)) {
            Log::info('Deleted KYC images for privacy protection', [
                'kyc_id' => $kycVerification->id,
                'user_id' => $kycVerification->user_id,
                'deleted_files' => $deletedFiles,
            ]);
        }
    }
}
