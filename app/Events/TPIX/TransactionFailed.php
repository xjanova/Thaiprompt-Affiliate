<?php

namespace App\Events\TPIX;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $txHash;
    public array $txData;

    public function __construct(string $txHash, array $txData)
    {
        $this->txHash = $txHash;
        $this->txData = $txData;
    }
}
