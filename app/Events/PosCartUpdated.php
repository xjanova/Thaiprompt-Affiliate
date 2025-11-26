<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PosCartUpdated Event
 *
 * Event ที่ถูก broadcast เมื่อมีการอัพเดทตะกร้าสินค้าใน POS
 * ส่งผ่าน WebSocket ไปยัง Customer Display แบบ real-time
 *
 * @package App\Events
 */
class PosCartUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Device code ของ POS device
     *
     * @var string
     */
    public string $deviceCode;

    /**
     * รายการสินค้าในตะกร้า
     *
     * @var array
     */
    public array $items;

    /**
     * ยอดรวมก่อนหักส่วนลด
     *
     * @var float
     */
    public float $subtotal;

    /**
     * ส่วนลด
     *
     * @var float
     */
    public float $discount;

    /**
     * ภาษี
     *
     * @var float
     */
    public float $tax;

    /**
     * ค่าบริการ
     *
     * @var float
     */
    public float $serviceCharge;

    /**
     * ยอดรวมทั้งหมด
     *
     * @var float
     */
    public float $total;

    /**
     * สถานะการแสดงผล (cart, payment, completed, idle)
     *
     * @var string
     */
    public string $displayMode;

    /**
     * สร้าง event instance ใหม่
     *
     * @param string $deviceCode
     * @param array $data ข้อมูลตะกร้า
     */
    public function __construct(string $deviceCode, array $data)
    {
        $this->deviceCode = $deviceCode;
        $this->items = $data['items'] ?? [];
        $this->subtotal = $data['subtotal'] ?? 0;
        $this->discount = $data['discount'] ?? 0;
        $this->tax = $data['tax'] ?? 0;
        $this->serviceCharge = $data['serviceCharge'] ?? 0;
        $this->total = $data['total'] ?? 0;
        $this->displayMode = $data['displayMode'] ?? 'cart';
    }

    /**
     * กำหนด channel ที่จะ broadcast
     *
     * ใช้ public channel เพราะ customer display ไม่ต้องการ authentication
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
        return 'cart.updated';
    }

    /**
     * ข้อมูลที่จะส่งไปกับ broadcast
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'action' => 'update',
            'items' => $this->items,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'serviceCharge' => $this->serviceCharge,
            'total' => $this->total,
            'displayMode' => $this->displayMode,
            'timestamp' => now()->toISOString(),
        ];
    }
}
