<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CarbonCreditPurchasedNotification extends Notification implements ShouldQueue
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
            ->subject('✅ ซื้อ Carbon Credit สำเร็จ!')
            ->greeting("สวัสดีครับ คุณ{$notifiable->name}")
            ->line("คุณซื้อ Carbon Credit สำเร็จแล้ว")
            ->line("ผู้ขาย: {$this->data['seller_name']}")
            ->line("จำนวน: {$this->data['amount']} TPCC")
            ->line("ราคา: ฿{$this->data['price']}")
            ->action('ดู Carbon Credits ของคุณ', url('/carbon-credits/my-credits'))
            ->line('ขอบคุณที่ร่วมสนับสนุนการรักษาสิ่งแวดล้อม!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'credit_purchased',
            'title' => '✅ ซื้อ Carbon Credit สำเร็จ',
            'message' => "ซื้อ {$this->data['amount']} TPCC ราคา ฿{$this->data['price']}",
            'data' => $this->data,
            'icon' => 'shopping-cart',
            'color' => 'primary',
        ];
    }
}
