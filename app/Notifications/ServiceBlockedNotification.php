<?php

namespace App\Notifications;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * แจ้งเตือนเมื่อบริการถูกบล็อกโดย Admin
 */
class ServiceBlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Service $service;

    /**
     * สร้าง notification instance
     *
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * ช่องทางการส่ง notification
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Email notification
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ บริการของคุณถูกบล็อกโดย Admin')
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line('บริการ "' . $this->service->name . '" ของคุณถูกบล็อกโดยผู้ดูแลระบบ')
            ->line('**เหตุผล**: ' . $this->service->block_reason)
            ->line('บริการจะไม่แสดงในระบบจนกว่าจะได้รับการปลดบล็อก')
            ->line('กรุณาติดต่อผู้ดูแลระบบเพื่อขอข้อมูลเพิ่มเติมหรือแก้ไขปัญหา')
            ->action('ดูรายละเอียดบริการ', route('admin.services.edit', $this->service))
            ->line('ขอบคุณที่ใช้บริการของเรา');
    }

    /**
     * Database notification
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'service_blocked',
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'block_reason' => $this->service->block_reason,
            'blocked_at' => $this->service->blocked_at,
            'blocked_by' => $this->service->blocked_by,
            'message' => 'บริการ "' . $this->service->name . '" ถูกบล็อกโดย Admin',
            'action_url' => route('admin.services.edit', $this->service),
        ];
    }
}
