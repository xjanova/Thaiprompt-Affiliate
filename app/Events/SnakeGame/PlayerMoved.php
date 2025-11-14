<?php

namespace App\Events\SnakeGame;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PlayerMoved Event
 *
 * ส่งข้อมูลการเคลื่อนที่ของผู้เล่นไปยังผู้เล่นคนอื่นในห้องเดียวกัน
 */
class PlayerMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public int $playerId;
    public array $position;
    public array $direction;
    public int $score;
    public int $length;

    /**
     * สร้าง event instance
     *
     * @param int $roomId ห้องที่ผู้เล่นอยู่
     * @param int $playerId ID ผู้เล่น
     * @param array $position ตำแหน่ง {x, y, z}
     * @param array $direction ทิศทาง {x, y, z}
     * @param int $score คะแนน
     * @param int $length ความยาวงู
     */
    public function __construct(int $roomId, int $playerId, array $position, array $direction, int $score, int $length)
    {
        $this->roomId = $roomId;
        $this->playerId = $playerId;
        $this->position = $position;
        $this->direction = $direction;
        $this->score = $score;
        $this->length = $length;
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
            'position' => $this->position,
            'direction' => $this->direction,
            'score' => $this->score,
            'length' => $this->length,
        ];
    }

    /**
     * ชื่อ event ที่จะถูก broadcast
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'player.moved';
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
