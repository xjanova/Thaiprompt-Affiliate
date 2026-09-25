<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Shop\PaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * อีเมลแจ้งคำสั่งซื้อใหม่
 *
 * 🐛 (2026-09-25) SHOP-12: เดิมส่งผ่านช่อง 'database' ของ Laravel ซึ่งเขียนตาราง notifications ไม่ได้
 *    (ตารางของระบบเป็นแบบกำหนดเอง: user_id/title/message บังคับ) → exception ทำให้อีเมลไม่ออกด้วย
 *    และข้อความทักทาย "Admin" + ลิงก์ /admin/orders ส่งไปหาร้าน/ลูกค้า
 *    ตอนนี้: อีเมลอย่างเดียว (กล่องแจ้งเตือนในระบบ + push ทำโดย App\Services\Shop\ShopOrderNotifier)
 *    เข้าคิวหลัง commit · ข้อความตามผู้รับ (seller | buyer)
 */
class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    protected string $audience;

    /**
     * @param  string  $audience  seller = แจ้งร้าน, buyer = ยืนยันกับผู้ซื้อ
     */
    public function __construct(Order $order, string $audience = 'seller')
    {
        $this->order = $order;
        $this->audience = $audience === 'buyer' ? 'buyer' : 'seller';

        // เข้าคิวหลัง transaction commit เท่านั้น (ออเดอร์ที่ rollback ไม่ส่งอีเมล)
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $order = $this->order;
        $total = number_format((float) $order->total_amount, 2);

        if ($this->audience === 'buyer') {
            return (new MailMessage)
                ->subject('ยืนยันคำสั่งซื้อ #'.$order->order_number)
                ->greeting('สวัสดีค่ะ คุณ'.($notifiable->name ?? 'ลูกค้า'))
                ->line('เราได้รับคำสั่งซื้อของคุณแล้ว')
                ->line('หมายเลขคำสั่งซื้อ: '.$order->order_number)
                ->line('ยอดรวม: '.$total.' บาท ('.PaymentMethod::labelTh($order->payment_method).')')
                ->action('ดูคำสั่งซื้อ', url('/orders/'.$order->id))
                ->line('ขอบคุณที่สั่งซื้อกับเรา');
        }

        return (new MailMessage)
            ->subject('มีคำสั่งซื้อใหม่: '.$order->order_number)
            ->greeting('สวัสดีค่ะ ร้าน'.($notifiable->name ?? ''))
            ->line('มีคำสั่งซื้อใหม่เข้ามาที่ร้านของคุณ')
            ->line('หมายเลขคำสั่งซื้อ: '.$order->order_number)
            ->line('ยอดรวม: '.$total.' บาท ('.PaymentMethod::labelTh($order->payment_method).')')
            ->line($order->isRiderDelivery() ? 'วิธีจัดส่ง: ไรเดอร์ของแพลตฟอร์ม (กดเรียกไรเดอร์เมื่อสินค้าพร้อม)' : 'วิธีจัดส่ง: ส่งพัสดุ')
            ->action('ดูรายละเอียด', url('/seller/orders/'.$order->id))
            ->line('กรุณายืนยันและเตรียมสินค้าให้ลูกค้า');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_order',
            'audience' => $this->audience,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => (float) $this->order->total_amount,
        ];
    }
}
