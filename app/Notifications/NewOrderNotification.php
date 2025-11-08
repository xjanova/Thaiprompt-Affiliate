<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewOrderNotification extends Notification
{
    use Queueable;

    protected Order $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('มีคำสั่งซื้อใหม่: ' . $this->order->order_number)
            ->greeting('สวัสดีครับ Admin')
            ->line('มีคำสั่งซื้อใหม่เข้ามา')
            ->line('หมายเลขคำสั่งซื้อ: ' . $this->order->order_number)
            ->line('ลูกค้า: ' . $this->order->user->name)
            ->line('ยอดรวม: ' . number_format($this->order->total_amount, 2) . ' บาท')
            ->action('ดูรายละเอียด', url('/admin/orders/' . $this->order->id))
            ->line('กรุณาตรวจสอบและดำเนินการต่อไป');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->user->name,
            'total_amount' => $this->order->total_amount,
        ];
    }
}
