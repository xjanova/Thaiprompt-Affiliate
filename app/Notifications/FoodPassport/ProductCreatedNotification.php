<?php

namespace App\Notifications\FoodPassport;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class ProductCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public array $data
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Food Passport Created Successfully')
            ->greeting("สวัสดีครับ คุณ{$notifiable->name}")
            ->line("Food Passport ของคุณถูกสร้างเรียบร้อยแล้ว!")
            ->line("รหัส Passport: {$this->data['passport_id']}")
            ->line("ประเภทผลิตภัณฑ์: {$this->data['product_type']}")
            ->action('ดูรายละเอียด', url("/food-passport/{$this->data['passport_id']}"))
            ->line('ขอบคุณที่ใช้บริการ Food Passport!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'product_created',
            'title' => 'Food Passport สร้างสำเร็จ',
            'message' => "Food Passport {$this->data['passport_id']} ถูกสร้างเรียบร้อยแล้ว",
            'product_id' => $this->data['product_id'],
            'passport_id' => $this->data['passport_id'],
            'product_type' => $this->data['product_type'],
            'action_url' => url("/food-passport/{$this->data['passport_id']}"),
            'icon' => 'check-circle',
            'color' => 'success',
        ];
    }
}
