<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QualityFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $data) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ การตรวจสอบคุณภาพไม่ผ่าน')
            ->greeting("สวัสดีครับ คุณ{$notifiable->name}")
            ->line("ผลิตภัณฑ์ของคุณไม่ผ่านการตรวจสอบคุณภาพ")
            ->line("ประเภทการตรวจ: {$this->data['checkpoint_type']}")
            ->line("คะแนน: {$this->data['score']}/100")
            ->line('กรุณาตรวจสอบและดำเนินการแก้ไข')
            ->action('ดูรายละเอียด', url("/quality/checkpoints/{$this->data['checkpoint_id']}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quality_failed',
            'title' => '⚠️ คุณภาพไม่ผ่านเกณฑ์',
            'message' => "การตรวจสอบคุณภาพไม่ผ่าน - ต้องดำเนินการแก้ไข",
            'data' => $this->data,
            'icon' => 'alert-triangle',
            'color' => 'danger',
        ];
    }
}
