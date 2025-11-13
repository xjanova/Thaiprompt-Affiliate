<?php

namespace App\Events\TPIX;

use App\Models\TPIXReferralUse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralUsed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TPIXReferralUse $referralUse;

    public function __construct(TPIXReferralUse $referralUse)
    {
        $this->referralUse = $referralUse;
    }
}
