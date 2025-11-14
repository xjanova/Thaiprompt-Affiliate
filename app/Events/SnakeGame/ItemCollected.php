<?php

namespace App\Events\SnakeGame;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ItemCollected Event
 *
 * แจ้งเตือนเมื่อมีผู้เล่นเก็บไอเทม
 */
class ItemCollected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public int $itemId;
    public int $playerId;

    /**
     * สร้าง event instance
     *
     * @param int $roomId ห้องที่เกิดเหตุการณ์
     * @param int $itemId ID ไอเทมที่ถูกเก็บ
     * @param int $playerId ID ผู้เล่นที่เก็บ
     */
    public function __construct(int $roomId, int $itemId, int $playerId)
    {
        $this->roomId = $roomId;
        $this->itemId = $itemId;
        $this->playerId = $playerId;
    }

    /**
     * ข้อมูลที่จะถูก broadcast
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'item_id' => $this->itemId,
            'player_id' => $this->playerId,
        ];
    }

    /**
     * ชื่อ event ที่จะถูก broadcast
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'item.collected';
    }

    /**
     * ช่องทางที่จะ broadcast
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('snake-room.' . $this->roomId),
        ];
    }
}
