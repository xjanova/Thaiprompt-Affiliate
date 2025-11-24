<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * แจ้งเตือนเมื่อสินค้าถูกบล็อกโดย Admin
 */
class ProductBlockedNotification extends Notification implements ShouldQueue
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
            ->subject('⚠️ สินค้าของคุณถูกบล็อกโดย Admin')
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line('สินค้า "' . $this->product->name . '" ของคุณถูกบล็อกโดยผู้ดูแลระบบ')
            ->line('**เหตุผล**: ' . $this->product->block_reason)
            ->line('สินค้าจะไม่แสดงในหน้าร้านจนกว่าจะได้รับการปลดบล็อก')
            ->line('กรุณาติดต่อผู้ดูแลระบบเพื่อขอข้อมูลเพิ่มเติมหรือแก้ไขปัญหา')
            ->action('ดูรายละเอียดสินค้า', route('seller.products.edit', $this->product))
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
            'type' => 'product_blocked',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'block_reason' => $this->product->block_reason,
            'blocked_at' => $this->product->blocked_at,
            'blocked_by' => $this->product->blocked_by,
            'message' => 'สินค้า "' . $this->product->name . '" ถูกบล็อกโดย Admin',
            'action_url' => route('seller.products.edit', $this->product),
        ];
    }
}
