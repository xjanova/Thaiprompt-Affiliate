<?php

namespace App\Notifications;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * แจ้งเตือนเมื่อบริการถูกปลดบล็อกโดย Admin
 */
class ServiceUnblockedNotification extends Notification implements ShouldQueue
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
            ->subject('✅ บริการของคุณถูกปลดบล็อกแล้ว')
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line('บริการ "' . $this->service->name . '" ของคุณถูกปลดบล็อกโดยผู้ดูแลระบบแล้ว')
            ->line('คุณสามารถเปิดใช้งานบริการเพื่อแสดงในระบบได้ตามปกติ')
            ->line('หากมีข้อสงสัย กรุณาติดต่อผู้ดูแลระบบ')
            ->action('จัดการบริการ', route('admin.services.edit', $this->service))
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
            'type' => 'service_unblocked',
            'service_id' => $this->service->id,
            'service_name' => $this->service->name,
            'unblocked_at' => $this->service->unblocked_at,
            'unblocked_by' => $this->service->unblocked_by,
            'message' => 'บริการ "' . $this->service->name . '" ถูกปลดบล็อกแล้ว คุณสามารถเปิดใช้งานได้ตามปกติ',
            'action_url' => route('admin.services.edit', $this->service),
        ];
    }
}
