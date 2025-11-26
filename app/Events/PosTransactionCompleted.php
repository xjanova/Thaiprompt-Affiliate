<?php

namespace App\Events;

use App\Models\PosTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PosTransactionCompleted Event
 *
 * Event ที่ถูก broadcast เมื่อการทำรายการ POS เสร็จสิ้น
 * ส่งผ่าน WebSocket ไปยัง Customer Display เพื่อแสดงข้อความขอบคุณ
 *
 * @package App\Events
 */
class PosTransactionCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Device code ของ POS device
     *
     * @var string
     */
    public string $deviceCode;

    /**
     * Transaction ID
     *
     * @var int
     */
    public int $transactionId;

    /**
     * ยอดรวมทั้งหมด
     *
     * @var float
     */
    public float $totalAmount;

    /**
     * จำนวนเงินที่รับ
     *
     * @var float
     */
    public float $amountPaid;

    /**
     * เงินทอน
     *
     * @var float
     */
    public float $changeAmount;

    /**
     * วิธีชำระเงิน
     *
     * @var string
     */
    public string $paymentMethod;

    /**
     * จำนวนสินค้าทั้งหมด
     *
     * @var int
     */
    public int $totalItems;

    /**
     * ชื่อลูกค้า (ถ้ามี)
     *
     * @var string|null
     */
    public ?string $customerName;

    /**
     * สร้าง event instance ใหม่
     *
     * @param string $deviceCode
     * @param PosTransaction $transaction
     */
    public function __construct(string $deviceCode, PosTransaction $transaction)
    {
        $this->deviceCode = $deviceCode;
        $this->transactionId = $transaction->id;
        $this->totalAmount = (float) $transaction->total_amount;
        $this->amountPaid = (float) $transaction->amount_paid;
        $this->changeAmount = (float) $transaction->change_amount;
        $this->paymentMethod = $transaction->payment_method;
        $this->totalItems = $transaction->total_items;
        $this->customerName = $transaction->customer_name;
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
        return 'transaction.completed';
    }

    /**
     * ข้อมูลที่จะส่งไปกับ broadcast
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'action' => 'completed',
            'transactionId' => $this->transactionId,
            'totalAmount' => $this->totalAmount,
            'amountPaid' => $this->amountPaid,
            'changeAmount' => $this->changeAmount,
            'paymentMethod' => $this->paymentMethod,
            'totalItems' => $this->totalItems,
            'customerName' => $this->customerName,
            'timestamp' => now()->toISOString(),
        ];
    }
}
