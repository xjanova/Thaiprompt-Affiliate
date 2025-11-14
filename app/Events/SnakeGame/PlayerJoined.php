<?php

namespace App\Events\SnakeGame;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PlayerJoined Event
 *
 * แจ้งเตือนเมื่อมีผู้เล่นใหม่เข้าร่วมห้อง
 */
class PlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public int $playerId;
    public string $playerName;
    public string $skinSlug;
    public array $position;

    /**
     * สร้าง event instance
     *
     * @param int $roomId ห้องที่ผู้เล่นเข้าร่วม
     * @param int $playerId ID ผู้เล่น
     * @param string $playerName ชื่อผู้เล่น
     * @param string $skinSlug สกินที่เลือก
     * @param array $position ตำแหน่งเริ่มต้น
     */
    public function __construct(int $roomId, int $playerId, string $playerName, string $skinSlug, array $position)
    {
        $this->roomId = $roomId;
        $this->playerId = $playerId;
        $this->playerName = $playerName;
        $this->skinSlug = $skinSlug;
        $this->position = $position;
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
            'player_name' => $this->playerName,
            'skin' => $this->skinSlug,
            'position' => $this->position,
        ];
    }

    /**
     * ชื่อ event ที่จะถูก broadcast
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'player.joined';
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
