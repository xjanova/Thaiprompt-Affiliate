<?php

namespace App\Observers;

use App\Models\AiRentalDeployment;
use App\Events\AiRental\DeploymentCreated;
use App\Events\AiRental\DeploymentStatusChanged;
use App\Services\AiRental\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * AI Rental Deployment Observer
 *
 * Observer นี้จะ auto-log ทุกการเปลี่ยนแปลงของ AiRentalDeployment model
 * และ dispatch events เมื่อมีการเปลี่ยนแปลงสำคัญ
 */
class AiRentalDeploymentObserver
{
    /**
     * Audit Service
     *
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * สร้าง observer instance
     *
     * @param AuditService $auditService
     * @return void
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the "creating" event
     *
     * เรียกก่อนที่ deployment จะถูกสร้าง
     * ใช้สำหรับ set default values หรือ validation
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    public function creating(AiRentalDeployment $deployment): void
    {
        Log::info('[AI Rental Observer] Deployment creating', [
            'deployment_name' => $deployment->deployment_name,
            'user_id' => $deployment->user_id,
        ]);

        // Set default values ถ้ายังไม่มี
        if (empty($deployment->status)) {
            $deployment->status = 'pending';
        }

        if (empty($deployment->health_status)) {
            $deployment->health_status = 'unknown';
        }
    }

    /**
     * Handle the "created" event
     *
     * เรียกหลังจาก deployment ถูกสร้างแล้ว
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    public function created(AiRentalDeployment $deployment): void
    {
        Log::info('[AI Rental Observer] Deployment created', [
            'deployment_id' => $deployment->id,
            'deployment_name' => $deployment->deployment_name,
        ]);

        try {
            // 1. บันทึก audit log
            $this->auditService->log(
                user: $deployment->user,
                action: 'deployment.created',
                category: 'deployment_lifecycle',
                description: "สร้าง deployment ใหม่: {$deployment->deployment_name}",
                relatedModel: $deployment,
                newValues: $deployment->toArray(),
                metadata: [
                    'deployment_id' => $deployment->id,
                    'deployment_name' => $deployment->deployment_name,
                    'provider' => $deployment->provider,
                    'instance_type' => $deployment->instance_type,
                    'cost_per_hour' => $deployment->cost_per_hour,
                ],
                isSensitive: false,
                requiresReview: false,
                ipAddress: request()->ip() ?? null,
                userAgent: request()->userAgent() ?? null
            );

            // 2. Dispatch event
            event(new DeploymentCreated($deployment, [
                'source' => 'observer',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]));
        } catch (\Exception $e) {
            Log::error('[AI Rental Observer] Failed to log deployment creation', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the "updating" event
     *
     * เรียกก่อน deployment จะถูกอัพเดท
     * ใช้สำหรับตรวจจับ status change
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    public function updating(AiRentalDeployment $deployment): void
    {
        // ตรวจสอบว่า status เปลี่ยนหรือไม่
        if ($deployment->isDirty('status')) {
            $oldStatus = $deployment->getOriginal('status');
            $newStatus = $deployment->status;

            Log::info('[AI Rental Observer] Deployment status changing', [
                'deployment_id' => $deployment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // เก็บข้อมูลไว้สำหรับ updated() event
            $deployment->_oldStatus = $oldStatus;
        }
    }

    /**
     * Handle the "updated" event
     *
     * เรียกหลังจาก deployment ถูกอัพเดทแล้ว
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    public function updated(AiRentalDeployment $deployment): void
    {
        Log::info('[AI Rental Observer] Deployment updated', [
            'deployment_id' => $deployment->id,
        ]);

        try {
            // ดึงข้อมูลที่เปลี่ยนแปลง
            $changes = $deployment->getChanges();

            // บันทึก audit log
            $this->auditService->log(
                user: $deployment->user,
                action: 'deployment.updated',
                category: 'deployment_lifecycle',
                description: "อัพเดท deployment: {$deployment->deployment_name}",
                relatedModel: $deployment,
                oldValues: array_intersect_key(
                    $deployment->getOriginal(),
                    $changes
                ),
                newValues: $changes,
                metadata: [
                    'deployment_id' => $deployment->id,
                    'deployment_name' => $deployment->deployment_name,
                    'changed_fields' => array_keys($changes),
                ],
                isSensitive: false,
                requiresReview: isset($changes['status']) && in_array($changes['status'], ['failed', 'terminated']),
                ipAddress: request()->ip() ?? null,
                userAgent: request()->userAgent() ?? null
            );

            // ถ้า status เปลี่ยน → dispatch event
            if (isset($changes['status']) && isset($deployment->_oldStatus)) {
                event(new DeploymentStatusChanged(
                    $deployment,
                    $deployment->_oldStatus,
                    $changes['status'],
                    $deployment->status_message ?? null,
                    ['observer' => true]
                ));

                // ลบข้อมูลชั่วคราว
                unset($deployment->_oldStatus);
            }
        } catch (\Exception $e) {
            Log::error('[AI Rental Observer] Failed to log deployment update', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the "deleted" event
     *
     * เรียกหลังจาก deployment ถูกลบ (soft delete)
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    public function deleted(AiRentalDeployment $deployment): void
    {
        Log::warning('[AI Rental Observer] Deployment deleted (soft)', [
            'deployment_id' => $deployment->id,
            'deployment_name' => $deployment->deployment_name,
        ]);

        try {
            // บันทึก audit log
            $this->auditService->log(
                user: $deployment->user,
                action: 'deployment.deleted',
                category: 'deployment_lifecycle',
                description: "ลบ deployment: {$deployment->deployment_name}",
                relatedModel: $deployment,
                oldValues: $deployment->toArray(),
                metadata: [
                    'deployment_id' => $deployment->id,
                    'deployment_name' => $deployment->deployment_name,
                    'deletion_type' => 'soft_delete',
                ],
                isSensitive: false,
                requiresReview: true,
                ipAddress: request()->ip() ?? null,
                userAgent: request()->userAgent() ?? null
            );
        } catch (\Exception $e) {
            Log::error('[AI Rental Observer] Failed to log deployment deletion', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the "restored" event
     *
     * เรียกหลังจาก deployment ถูก restore
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    public function restored(AiRentalDeployment $deployment): void
    {
        Log::info('[AI Rental Observer] Deployment restored', [
            'deployment_id' => $deployment->id,
            'deployment_name' => $deployment->deployment_name,
        ]);

        try {
            // บันทึก audit log
            $this->auditService->log(
                user: $deployment->user,
                action: 'deployment.restored',
                category: 'deployment_lifecycle',
                description: "กู้คืน deployment: {$deployment->deployment_name}",
                relatedModel: $deployment,
                metadata: [
                    'deployment_id' => $deployment->id,
                    'deployment_name' => $deployment->deployment_name,
                ],
                isSensitive: false,
                requiresReview: false,
                ipAddress: request()->ip() ?? null,
                userAgent: request()->userAgent() ?? null
            );
        } catch (\Exception $e) {
            Log::error('[AI Rental Observer] Failed to log deployment restoration', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the "force deleted" event
     *
     * เรียกหลังจาก deployment ถูกลบถาวร
     *
     * @param AiRentalDeployment $deployment
     * @return void
     */
    public function forceDeleted(AiRentalDeployment $deployment): void
    {
        Log::critical('[AI Rental Observer] Deployment force deleted (permanent)', [
            'deployment_id' => $deployment->id,
            'deployment_name' => $deployment->deployment_name,
        ]);

        try {
            // บันทึก audit log
            $this->auditService->log(
                user: $deployment->user,
                action: 'deployment.force_deleted',
                category: 'deployment_lifecycle',
                description: "ลบ deployment ถาวร: {$deployment->deployment_name}",
                relatedModel: null, // Model ถูกลบแล้ว
                oldValues: $deployment->toArray(),
                metadata: [
                    'deployment_id' => $deployment->id,
                    'deployment_name' => $deployment->deployment_name,
                    'deletion_type' => 'force_delete',
                    'warning' => 'PERMANENT_DELETION',
                ],
                isSensitive: true,
                requiresReview: true,
                ipAddress: request()->ip() ?? null,
                userAgent: request()->userAgent() ?? null
            );
        } catch (\Exception $e) {
            Log::error('[AI Rental Observer] Failed to log force deletion', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
