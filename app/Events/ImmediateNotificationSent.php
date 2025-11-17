<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ImmediateNotificationSent Event
 *
 * Event สำหรับ immediate notifications (toast popup)
 * ส่งผ่าน WebSocket ไปแสดง popup ทันที
 */
class ImmediateNotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Notification data
     *
     * @var array
     */
    public $notifications;

    /**
     * User ID ที่จะรับ notification
     *
     * @var int
     */
    public $userId;

    /**
     * Create a new event instance.
     *
     * @param array $notifications
     * @param int $userId
     */
    public function __construct(array $notifications, int $userId)
    {
        $this->notifications = $notifications;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast ไปยัง private channel สำหรับ immediate notifications
        return new PrivateChannel('notifications.immediate.' . $this->userId);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'ImmediateNotificationSent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'notifications' => $this->notifications
        ];
    }
}
