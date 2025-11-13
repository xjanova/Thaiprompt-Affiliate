<?php

namespace App\Events\TPIX;

use App\Models\TPIXToken;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TPIXToken $token;

    public function __construct(TPIXToken $token)
    {
        $this->token = $token;
    }
}
