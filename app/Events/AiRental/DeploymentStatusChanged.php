<?php

namespace App\Events\AiRental;

use App\Models\AiRentalDeployment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Deployment Status Changed
 *
 * Event นี้จะถูก dispatch เมื่อ status ของ deployment เปลี่ยนแปลง
 * ใช้สำหรับ:
 * - บันทึก audit log การเปลี่ยนสถานะ
 * - แจ้งเตือนผู้ใช้เมื่อ deployment พร้อมใช้งาน
 * - Update analytics และ metrics
 * - Trigger auto-actions (เช่น stop billing เมื่อ terminated)
 */
class DeploymentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Deployment ที่เปลี่ยนสถานะ
     */
    public AiRentalDeployment $deployment;

    /**
     * สถานะเก่า
     */
    public string $oldStatus;

    /**
     * สถานะใหม่
     */
    public string $newStatus;

    /**
     * เหตุผลการเปลี่ยนสถานะ
     */
    public ?string $reason;

    /**
     * ข้อมูลเพิ่มเติม
     */
    public array $metadata;

    /**
     * สร้าง event instance
     *
     * @param  string  $oldStatus  สถานะเก่า
     * @param  string  $newStatus  สถานะใหม่
     * @param  string|null  $reason  เหตุผล
     * @param  array  $metadata  ข้อมูลเพิ่มเติม
     * @return void
     *
     * @example
     * event(new DeploymentStatusChanged(
     *     $deployment,
     *     'pending',
     *     'running',
     *     'Deployment completed successfully',
     *     ['duration_seconds' => 120]
     * ));
     */
    public function __construct(
        AiRentalDeployment $deployment,
        string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        array $metadata = []
    ) {
        $this->deployment = $deployment;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->reason = $reason;
        $this->metadata = array_merge([
            'changed_at' => now()->toIso8601String(),
            'changed_by' => auth()->id() ?? 'system',
        ], $metadata);
    }

    /**
     * ตรวจสอบว่าเป็นการเปลี่ยนจาก pending → running
     */
    public function isDeploymentReady(): bool
    {
        return $this->oldStatus === 'pending' && $this->newStatus === 'running';
    }

    /**
     * ตรวจสอบว่าเป็นการ terminate
     */
    public function isTerminated(): bool
    {
        return $this->newStatus === 'terminated';
    }

    /**
     * ตรวจสอบว่าเป็นการ failed
     */
    public function isFailed(): bool
    {
        return $this->newStatus === 'failed';
    }

    /**
     * ตรวจสอบว่าเป็นการ stopped
     */
    public function isStopped(): bool
    {
        return $this->newStatus === 'stopped';
    }

    /**
     * ตรวจสอบว่าควรหยุด billing หรือไม่
     */
    public function shouldStopBilling(): bool
    {
        return in_array($this->newStatus, ['terminated', 'failed', 'stopped']);
    }

    /**
     * ตรวจสอบว่าควรส่ง notification หรือไม่
     */
    public function shouldNotifyUser(): bool
    {
        // แจ้งเตือนเมื่อพร้อมใช้งาน, ล้มเหลว, หรือหยุดทำงาน
        return in_array($this->newStatus, ['running', 'failed', 'terminated', 'stopped']);
    }

    /**
     * ดึง severity level
     *
     * @return string info|warning|error
     */
    public function getSeverity(): string
    {
        return match ($this->newStatus) {
            'running' => 'info',
            'stopped' => 'warning',
            'failed', 'terminated' => 'error',
            default => 'info',
        };
    }

    /**
     * แปลง event เป็น array
     */
    public function toArray(): array
    {
        return [
            'event' => 'deployment.status_changed',
            'deployment_id' => $this->deployment->id,
            'deployment_name' => $this->deployment->deployment_name,
            'user_id' => $this->deployment->user_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'reason' => $this->reason,
            'is_deployment_ready' => $this->isDeploymentReady(),
            'is_terminated' => $this->isTerminated(),
            'is_failed' => $this->isFailed(),
            'should_stop_billing' => $this->shouldStopBilling(),
            'should_notify_user' => $this->shouldNotifyUser(),
            'severity' => $this->getSeverity(),
            'metadata' => $this->metadata,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
