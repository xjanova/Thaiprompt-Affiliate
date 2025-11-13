<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CarbonCreditSoldNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $data) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $netAmount = $this->data['price'] - $this->data['commission'];

        return (new MailMessage)
            ->subject('💰 Carbon Credit ของคุณถูกขายแล้ว!')
            ->greeting("สวัสดีครับ คุณ{$notifiable->name}")
            ->line("Carbon Credit ของคุณถูกขายเรียบร้อยแล้ว")
            ->line("ผู้ซื้อ: {$this->data['buyer_name']}")
            ->line("ราคาขาย: ฿{$this->data['price']}")
            ->line("ค่าธรรมเนียม (5%): ฿{$this->data['commission']}")
            ->line("ยอดสุทธิที่คุณได้รับ: ฿{$netAmount}")
            ->action('ดูประวัติการซื้อขาย', url('/carbon-credits/transactions'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'credit_sold',
            'title' => '💰 ขาย Carbon Credit สำเร็จ',
            'message' => "ขาย Carbon Credit ราคา ฿{$this->data['price']} ให้ {$this->data['buyer_name']}",
            'data' => $this->data,
            'icon' => 'dollar-sign',
            'color' => 'success',
        ];
    }
}
