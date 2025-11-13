<?php

namespace App\Events\FoodPassport;

use App\Models\CarbonCredit;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarbonCreditTradedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public CarbonCredit $credit,
        public User $seller,
        public User $buyer
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->seller->id),
            new PrivateChannel('user.' . $this->buyer->id),
            new Channel('carbon-marketplace'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'carbon-credit.traded';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'credit_id' => $this->credit->id,
            'seller' => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
            ],
            'buyer' => [
                'id' => $this->buyer->id,
                'name' => $this->buyer->name,
            ],
            'amount' => $this->credit->credit_amount,
            'price' => $this->credit->trade_price,
            'traded_at' => now()->toIso8601String(),
        ];
    }
}
