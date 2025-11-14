<?php

namespace App\Events\FoodPassport;

use App\Models\QualityCheckpoint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QualityCheckFailedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public QualityCheckpoint $checkpoint
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $product = $this->checkpoint->foodProduct;

        return [
            new PrivateChannel('food-product.' . $product->id),
            new PrivateChannel('user.' . $product->farmer_id),
            new PrivateChannel('quality-alerts'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'quality-check.failed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'checkpoint_id' => $this->checkpoint->id,
            'product_id' => $this->checkpoint->food_product_id,
            'checkpoint_type' => $this->checkpoint->checkpoint_type,
            'result' => $this->checkpoint->overall_result,
            'score' => $this->checkpoint->pass_score,
            'retest_required' => $this->checkpoint->retest_required,
            'failed_at' => now()->toIso8601String(),
        ];
    }
}
