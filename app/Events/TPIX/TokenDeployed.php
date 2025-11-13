<?php

namespace App\Events\TPIX;

use App\Models\TPIXToken;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenDeployed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TPIXToken $token;

    public function __construct(TPIXToken $token)
    {
        $this->token = $token;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->token->creator_id);
    }

    public function broadcastWith(): array
    {
        return [
            'token_id' => $this->token->id,
            'token_name' => $this->token->name,
            'token_symbol' => $this->token->symbol,
            'contract_address' => $this->token->contract_address,
            'message' => "Token {$this->token->symbol} has been deployed successfully!",
        ];
    }
}
