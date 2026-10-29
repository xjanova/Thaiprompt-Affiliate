<?php

namespace App\Observers;

use App\Models\KycVerification;
use App\Services\Ekyc\EkycService;
use App\Services\Kyc\KycDocumentStorage;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // 🪪 แถว AI eKYC: EkycService แจ้งผลเอง (push type kyc_result) — ไม่ส่งแจ้งเตือนแบบเดิมซ้ำ
        if ($kycVerification->isEkyc()) {
            return;
        }

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
        // 🪪 แจ้งเตือนแอดมินที่ผูกกับคำขอนี้มีชื่อเจ้าของข้อมูล — ลบไปพร้อมแถว (PDPA: ลบบัญชีแล้วไม่เหลือร่องรอย)
        try {
            \App\Models\Notification::query()
                ->where('notifiable_type', KycVerification::class)
                ->where('notifiable_id', $kycVerification->id)
                ->forceDelete(); // Notification ใช้ SoftDeletes — ต้องลบจริง
        } catch (\Throwable $e) {
            Log::warning('Failed to delete KYC admin notifications', [
                'kyc_id' => $kycVerification->id,
                'error' => class_basename($e),
            ]);
        }

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
        // 🪪 แถว AI eKYC: รูปเข้ารหัสอยู่บน private disk — ลบหลัง commit (ธุรกรรมถูกย้อน = แถวยังอยู่ ไฟล์ต้องอยู่ด้วย)
        if ($kycVerification->isEkyc()) {
            $snapshot = clone $kycVerification;
            DB::afterCommit(fn () => app(EkycService::class)->deleteFilesFor($snapshot));

            return;
        }

        // 🔒 (2026-10-29) รูปแบบเดิมอยู่ได้ทั้ง private disk (ใหม่) และ public disk (ไฟล์เก่าที่ยังไม่ย้าย)
        //    → KycDocumentStorage หา disk ให้เอง · ลบหลัง commit เหมือน eKYC (ธุรกรรมถูกย้อน = แถวยังอยู่ ไฟล์ต้องอยู่ด้วย)
        $paths = array_values(array_filter([$kycVerification->id_card_image, $kycVerification->selfie_image]));
        if (empty($paths)) {
            return;
        }

        $kycId = $kycVerification->id;
        $userId = $kycVerification->user_id;

        DB::afterCommit(function () use ($paths, $kycId, $userId) {
            $documents = app(KycDocumentStorage::class);
            $deleted = 0;

            foreach ($paths as $path) {
                if ($documents->exists($path)) {
                    $documents->delete($path);
                    $deleted++;
                }
            }

            if ($deleted > 0) {
                // ไม่ log พาธไฟล์ — จำนวนพอ
                Log::info('Deleted KYC images for privacy protection', [
                    'kyc_id' => $kycId,
                    'user_id' => $userId,
                    'deleted_files' => $deleted,
                ]);
            }
        });
    }
}
