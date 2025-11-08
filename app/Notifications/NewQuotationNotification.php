<?php

namespace App\Notifications;

use App\Models\SoftwareQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewQuotationNotification extends Notification
{
    use Queueable;

    protected SoftwareQuotation $quotation;

    /**
     * Create a new notification instance.
     */
    public function __construct(SoftwareQuotation $quotation)
    {
        $this->quotation = $quotation;
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
            ->subject('มีใบเสนอราคาใหม่: ' . $this->quotation->quotation_number)
            ->greeting('สวัสดีครับ Admin')
            ->line('มีใบเสนอราคาใหม่เข้ามา')
            ->line('หมายเลข: ' . $this->quotation->quotation_number)
            ->line('ลูกค้า: ' . $this->quotation->customer_name)
            ->line('สินค้า: ' . $this->quotation->softwareProduct->name)
            ->line('ยอดรวม: ' . number_format($this->quotation->total_amount, 2) . ' บาท')
            ->action('ดูรายละเอียด', url('/admin/quotations/' . $this->quotation->id))
            ->line('กรุณาตรวจสอบและดำเนินการต่อไป');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_quotation',
            'quotation_id' => $this->quotation->id,
            'quotation_number' => $this->quotation->quotation_number,
            'customer_name' => $this->quotation->customer_name,
            'customer_email' => $this->quotation->customer_email,
            'total_amount' => $this->quotation->total_amount,
            'product_name' => $this->quotation->softwareProduct->name,
        ];
    }
}
