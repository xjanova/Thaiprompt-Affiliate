<?php

namespace App\Events\SnakeGame;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PlayerLeft Event
 *
 * แจ้งเตือนเมื่อผู้เล่นออกจากห้อง
 */
class PlayerLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public int $playerId;

    /**
     * สร้าง event instance
     *
     * @param int $roomId ห้องที่ผู้เล่นออกไป
     * @param int $playerId ID ผู้เล่น
     */
    public function __construct(int $roomId, int $playerId)
    {
        $this->roomId = $roomId;
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
        return 'player.left';
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
