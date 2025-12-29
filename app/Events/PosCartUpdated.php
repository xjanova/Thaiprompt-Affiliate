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
 */
class PosCartUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Device code ของ POS device
     */
    public string $deviceCode;

    /**
     * รายการสินค้าในตะกร้า
     */
    public array $items;

    /**
     * ยอดรวมก่อนหักส่วนลด
     */
    public float $subtotal;

    /**
     * ส่วนลด
     */
    public float $discount;

    /**
     * ภาษี
     */
    public float $tax;

    /**
     * ค่าบริการ
     */
    public float $serviceCharge;

    /**
     * ยอดรวมทั้งหมด
     */
    public float $total;

    /**
     * สถานะการแสดงผล (cart, payment, completed, idle)
     */
    public string $displayMode;

    /**
     * สร้าง event instance ใหม่
     *
     * @param  array  $data  ข้อมูลตะกร้า
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
     */
    public function broadcastOn(): Channel
    {
        return new Channel('pos-display.'.$this->deviceCode);
    }

    /**
     * กำหนดชื่อ event สำหรับ broadcast
     */
    public function broadcastAs(): string
    {
        return 'cart.updated';
    }

    /**
     * ข้อมูลที่จะส่งไปกับ broadcast
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
