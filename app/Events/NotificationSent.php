<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * NotificationSent Event
 *
 * Event ที่ถูก broadcast เมื่อมี notification ใหม่
 * ส่งผ่าน WebSocket ไปยัง client แบบ real-time
 */
class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Notification instance
     *
     * @var Notification
     */
    public $notification;

    /**
     * User ID ที่จะรับ notification
     *
     * @var int
     */
    public $userId;

    /**
     * Create a new event instance.
     *
     * @param Notification $notification
     * @param int $userId
     */
    public function __construct(Notification $notification, int $userId)
    {
        $this->notification = $notification;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast ไปยัง private channel ของ user
        return new PrivateChannel('notifications.' . $this->userId);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'NotificationSent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'notification' => [
                'id' => $this->notification->id,
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'icon' => $this->notification->icon ?? '🔔',
                'color' => $this->notification->color ?? 'blue',
                'is_read' => $this->notification->is_read ?? false,
                'is_important' => $this->notification->is_important ?? false,
                'action_url' => $this->notification->action_url,
                'action_text' => $this->notification->action_text ?? 'ดูเพิ่มเติม',
                'created_at' => $this->notification->created_at->toISOString(),
            ]
        ];
    }
}
