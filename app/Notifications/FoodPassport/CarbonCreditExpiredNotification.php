<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CarbonCreditExpiredNotification extends Notification implements ShouldQueue
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
            ->subject('⏰ Carbon Credit หมดอายุแล้ว')
            ->greeting("สวัสดีครับ คุณ{$notifiable->name}")
            ->line("Carbon Credit ของคุณหมดอายุแล้ว")
            ->line("จำนวน: {$this->data['amount']} TPCC")
            ->line("วันหมดอายุ: {$this->data['expiry_date']}")
            ->line('Carbon Credit ที่หมดอายุจะไม่สามารถใช้งานหรือซื้อขายได้อีกต่อไป')
            ->action('ดูรายการ Carbon Credits', url('/carbon-credits/my-credits'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'credit_expired',
            'title' => '⏰ Carbon Credit หมดอายุ',
            'message' => "{$this->data['amount']} TPCC หมดอายุแล้ว",
            'data' => $this->data,
            'icon' => 'clock',
            'color' => 'warning',
        ];
    }
}
