<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PosDisplayClear Event
 *
 * Event ที่ถูก broadcast เมื่อต้องการล้างหน้าจอ Customer Display
 * กลับไปแสดงหน้าจอต้อนรับหรือโฆษณา
 *
 * @package App\Events
 */
class PosDisplayClear implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Device code ของ POS device
     *
     * @var string
     */
    public string $deviceCode;

    /**
     * แสดงข้อความขอบคุณก่อนล้างหรือไม่
     *
     * @var bool
     */
    public bool $showThankYou;

    /**
     * ระยะเวลาแสดงข้อความขอบคุณ (วินาที)
     *
     * @var int
     */
    public int $thankYouDuration;

    /**
     * สร้าง event instance ใหม่
     *
     * @param string $deviceCode
     * @param bool $showThankYou
     * @param int $thankYouDuration
     */
    public function __construct(
        string $deviceCode,
        bool $showThankYou = true,
        int $thankYouDuration = 5
    ) {
        $this->deviceCode = $deviceCode;
        $this->showThankYou = $showThankYou;
        $this->thankYouDuration = $thankYouDuration;
    }

    /**
     * กำหนด channel ที่จะ broadcast
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('pos-display.' . $this->deviceCode);
    }

    /**
     * กำหนดชื่อ event สำหรับ broadcast
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'display.clear';
    }

    /**
     * ข้อมูลที่จะส่งไปกับ broadcast
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'action' => 'clear',
            'showThankYou' => $this->showThankYou,
            'thankYouDuration' => $this->thankYouDuration,
            'timestamp' => now()->toISOString(),
        ];
    }
}
