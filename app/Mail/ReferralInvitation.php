<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReferralInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $sponsor;
    public $referralLink;

    public function __construct(User $sponsor, string $referralLink)
    {
        $this->sponsor = $sponsor;
        $this->referralLink = $referralLink;
    }

    public function build()
    {
        return $this->subject($this->sponsor->name . ' เชิญคุณเข้าร่วม ' . config('app.name'))
                    ->view('emails.referrals.invitation')
                    ->with([
                        'sponsor' => $this->sponsor,
                        'referralLink' => $this->referralLink,
                        'appName' => config('app.name')
                    ]);
    }
}
