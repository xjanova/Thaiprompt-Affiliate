<?php

namespace App\Events\FoodPassport;

use App\Models\FoodProduct;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductJourneyCompletedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public FoodProduct $product
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('food-product.' . $this->product->id),
            new PrivateChannel('user.' . $this->product->farmer_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'journey.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->product->id,
            'passport_id' => $this->product->food_passport_id,
            'total_carbon_footprint' => $this->product->total_carbon_footprint,
            'carbon_score' => $this->product->carbon_score,
            'journey_stages_count' => $this->product->journeyStages()->count(),
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
