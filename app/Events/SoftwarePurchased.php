<?php

namespace App\Events;

use App\Models\Order;
use App\Models\SoftwareProduct;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SoftwarePurchased Event
 *
 * Event ที่ถูก dispatch เมื่อมีการซื้อซอฟต์แวร์สำเร็จ
 */
class SoftwarePurchased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var SoftwareProduct
     */
    public $product;

    /**
     * @var User
     */
    public $buyer;

    /**
     * @var Order|null
     */
    public $order;

    /**
     * @var array
     */
    public $options;

    /**
     * สร้าง event instance ใหม่
     *
     * @param SoftwareProduct $product สินค้าซอฟต์แวร์ที่ซื้อ
     * @param User $buyer ผู้ซื้อ
     * @param Order|null $order คำสั่งซื้อ (optional)
     * @param array $options ตัวเลือกเพิ่มเติม
     */
    public function __construct(
        SoftwareProduct $product,
        User $buyer,
        ?Order $order = null,
        array $options = []
    ) {
        $this->product = $product;
        $this->buyer = $buyer;
        $this->order = $order;
        $this->options = $options;
    }
}
