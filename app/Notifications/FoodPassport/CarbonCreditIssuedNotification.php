<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CarbonCreditIssuedNotification extends Notification implements ShouldQueue
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
            ->subject('🎉 คุณได้รับ Carbon Credits!')
            ->greeting("สวัสดีครับ คุณ{$notifiable->name}")
            ->line("ยินดีด้วย! คุณได้รับ Carbon Credits จากการลดการปล่อยคาร์บอน")
            ->line("จำนวน: {$this->data['amount']} TPCC")
            ->line("มูลค่า: ฿{$this->data['value']}")
            ->line("ระดับ: {$this->data['tier']}")
            ->action('ดู Carbon Credits ของคุณ', url('/carbon-credits/my-credits'))
            ->line('ขอบคุณที่ช่วยรักษาสิ่งแวดล้อม!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'credit_issued',
            'title' => '🎉 ได้รับ Carbon Credits',
            'message' => "คุณได้รับ {$this->data['amount']} TPCC มูลค่า ฿{$this->data['value']}",
            'data' => $this->data,
            'icon' => 'award',
            'color' => 'success',
        ];
    }
}
