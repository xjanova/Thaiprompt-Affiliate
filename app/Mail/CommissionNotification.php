<?php

namespace App\Mail;

use App\Models\Commission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $commission;

    public function __construct(Commission $commission)
    {
        $this->commission = $commission;
    }

    public function build()
    {
        return $this->subject('คุณได้รับค่าคอมมิชชั่น ฿' . number_format($this->commission->amount, 2))
                    ->view('emails.commissions.notification')
                    ->with([
                        'commission' => $this->commission,
                        'type' => ucfirst(str_replace('_', ' ', $this->commission->type)),
                        'amount' => $this->commission->amount
                    ]);
    }
}
