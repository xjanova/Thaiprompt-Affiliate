<?php

namespace App\Events;

use App\Models\PaymentTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: สร้างบิลชำระเงินใหม่ (PaymentTransaction)
 *
 * ถูก dispatch เมื่อสร้าง PaymentTransaction ใหม่สำหรับ promptpay/bank_transfer
 * ใช้สำหรับส่ง FCM push notification ไปยัง SmsChecker Android app
 */
class NewTransactionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PaymentTransaction $transaction
    ) {}
}
