<?php

namespace App\Events\TPIX;

use App\Models\TPIXStake;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StakeCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TPIXStake $stake;

    public function __construct(TPIXStake $stake)
    {
        $this->stake = $stake;
    }
}
