<?php

namespace App\Mail;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WithdrawalApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $withdrawal;

    public function __construct(Withdrawal $withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    public function build()
    {
        return $this->subject('คำขอถอนเงินได้รับการอนุมัติ')
                    ->view('emails.withdrawals.approved')
                    ->with([
                        'withdrawal' => $this->withdrawal,
                        'amount' => $this->withdrawal->amount,
                        'fee' => $this->withdrawal->fee,
                        'net_amount' => $this->withdrawal->net_amount
                    ]);
    }
}
