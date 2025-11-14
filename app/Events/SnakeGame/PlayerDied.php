<?php

namespace App\Events\SnakeGame;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PlayerDied Event
 *
 * แจ้งเตือนเมื่อผู้เล่นตาย
 */
class PlayerDied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public int $playerId;
    public int $finalScore;
    public int $finalLength;

    /**
     * สร้าง event instance
     *
     * @param int $roomId ห้องที่เกิดเหตุการณ์
     * @param int $playerId ID ผู้เล่นที่ตาย
     * @param int $finalScore คะแนนสุดท้าย
     * @param int $finalLength ความยาวสุดท้าย
     */
    public function __construct(int $roomId, int $playerId, int $finalScore, int $finalLength)
    {
        $this->roomId = $roomId;
        $this->playerId = $playerId;
        $this->finalScore = $finalScore;
        $this->finalLength = $finalLength;
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
            'final_score' => $this->finalScore,
            'final_length' => $this->finalLength,
        ];
    }

    /**
     * ชื่อ event ที่จะถูก broadcast
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'player.died';
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
