<?php

namespace App\Events\TPIX;

use App\Models\CoinControlAction;
use App\Models\TPIXToken;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenBurned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TPIXToken $token;
    public CoinControlAction $action;

    public function __construct(TPIXToken $token, CoinControlAction $action)
    {
        $this->token = $token;
        $this->action = $action;
    }
}
