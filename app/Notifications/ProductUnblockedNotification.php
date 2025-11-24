<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * แจ้งเตือนเมื่อสินค้าถูกปลดบล็อกโดย Admin
 */
class ProductUnblockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Product $product;

    /**
     * สร้าง notification instance
     *
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
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
            ->subject('✅ สินค้าของคุณถูกปลดบล็อกแล้ว')
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line('สินค้า "' . $this->product->name . '" ของคุณถูกปลดบล็อกโดยผู้ดูแลระบบแล้ว')
            ->line('คุณสามารถเปิดใช้งานสินค้าเพื่อแสดงในหน้าร้านได้ตามปกติ')
            ->line('หากมีข้อสงสัย กรุณาติดต่อผู้ดูแลระบบ')
            ->action('จัดการสินค้า', route('seller.products.edit', $this->product))
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
            'type' => 'product_unblocked',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unblocked_at' => $this->product->unblocked_at,
            'unblocked_by' => $this->product->unblocked_by,
            'message' => 'สินค้า "' . $this->product->name . '" ถูกปลดบล็อกแล้ว คุณสามารถเปิดใช้งานได้ตามปกติ',
            'action_url' => route('seller.products.edit', $this->product),
        ];
    }
}
