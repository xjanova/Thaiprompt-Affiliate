<?php

namespace App\Events\SnakeGame;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ItemSpawned Event
 *
 * แจ้งเตือนเมื่อมีไอเทมใหม่เกิดในห้อง
 */
class ItemSpawned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public int $itemId;
    public string $itemType;
    public array $position;
    public int $value;

    /**
     * สร้าง event instance
     *
     * @param int $roomId ห้องที่ไอเทมเกิด
     * @param int $itemId ID ไอเทม
     * @param string $itemType ประเภทไอเทม
     * @param array $position ตำแหน่ง {x, y, z}
     * @param int $value ค่าไอเทม
     */
    public function __construct(int $roomId, int $itemId, string $itemType, array $position, int $value)
    {
        $this->roomId = $roomId;
        $this->itemId = $itemId;
        $this->itemType = $itemType;
        $this->position = $position;
        $this->value = $value;
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
            'type' => $this->itemType,
            'position' => $this->position,
            'value' => $this->value,
        ];
    }

    /**
     * ชื่อ event ที่จะถูก broadcast
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'item.spawned';
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
