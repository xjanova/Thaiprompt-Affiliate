<?php

namespace App\Events\TPIX;

use App\Models\TPIXTokenTransfer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenTransferred
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TPIXTokenTransfer $transfer;

    public function __construct(TPIXTokenTransfer $transfer)
    {
        $this->transfer = $transfer;
    }
}
