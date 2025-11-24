<?php

namespace App\Events\AiRental;

use App\Models\AiRentalDeployment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: Deployment Created
 *
 * Event นี้จะถูก dispatch เมื่อมีการสร้าง deployment ใหม่
 * ใช้สำหรับ trigger actions อัตโนมัติ เช่น:
 * - ส่งแจ้งเตือนให้ผู้ใช้
 * - บันทึก audit log
 * - เริ่ม health check schedule
 * - Update analytics
 */
class DeploymentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Deployment ที่ถูกสร้าง
     *
     * @var AiRentalDeployment
     */
    public AiRentalDeployment $deployment;

    /**
     * ข้อมูลเพิ่มเติมเกี่ยวกับการสร้าง
     *
     * @var array
     */
    public array $metadata;

    /**
     * สร้าง event instance
     *
     * @param AiRentalDeployment $deployment Deployment ที่ถูกสร้าง
     * @param array $metadata ข้อมูลเพิ่มเติม (optional)
     * @return void
     *
     * @example
     * event(new DeploymentCreated($deployment, [
     *     'source' => 'api',
     *     'ip_address' => request()->ip(),
     *     'user_agent' => request()->userAgent(),
     * ]));
     */
    public function __construct(AiRentalDeployment $deployment, array $metadata = [])
    {
        $this->deployment = $deployment;
        $this->metadata = array_merge([
            'created_at' => now()->toIso8601String(),
            'source' => 'web',
            'trigger' => 'manual',
        ], $metadata);
    }

    /**
     * ดึงข้อมูล deployment
     *
     * @return AiRentalDeployment
     */
    public function getDeployment(): AiRentalDeployment
    {
        return $this->deployment;
    }

    /**
     * ดึง metadata
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * ดึง user ที่สร้าง deployment
     *
     * @return \App\Models\User
     */
    public function getUser()
    {
        return $this->deployment->user;
    }

    /**
     * แปลง event เป็น array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'event' => 'deployment.created',
            'deployment_id' => $this->deployment->id,
            'deployment_name' => $this->deployment->deployment_name,
            'user_id' => $this->deployment->user_id,
            'provider' => $this->deployment->provider,
            'instance_type' => $this->deployment->instance_type,
            'status' => $this->deployment->status,
            'cost_per_hour' => $this->deployment->cost_per_hour,
            'metadata' => $this->metadata,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
