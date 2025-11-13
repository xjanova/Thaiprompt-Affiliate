<?php

namespace App\Events\TPIX;

use App\Models\CoinControlAction;
use App\Models\TPIXToken;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddressFrozen
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TPIXToken $token;
    public CoinControlAction $action;
    public string $address;

    public function __construct(TPIXToken $token, CoinControlAction $action, string $address)
    {
        $this->token = $token;
        $this->action = $action;
        $this->address = $address;
    }
}
