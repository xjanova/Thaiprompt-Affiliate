<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('ยืนยันคำสั่งซื้อ #' . $this->order->order_number)
                    ->view('emails.orders.confirmation')
                    ->with([
                        'order' => $this->order,
                        'items' => $this->order->items,
                        'total' => $this->order->total_amount
                    ]);
    }
}
