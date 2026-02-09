<?php

namespace App\Listeners;

use App\Events\NewTransactionCreated;
use App\Services\FcmNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener: ส่ง FCM push notification เมื่อมีบิลใหม่
 *
 * เมื่อ PaymentTransaction ถูกสร้าง (promptpay/bank_transfer)
 * จะส่ง push notification ไปยัง SmsChecker Android app
 * เพื่อให้แอพโหลดบิลทันทีโดยไม่ต้องรอ periodic sync
 */
class SendNewTransactionFcmNotification implements ShouldQueue
{
    public function __construct(
        private FcmNotificationService $fcmService
    ) {}

    public function handle(NewTransactionCreated $event): void
    {
        $transaction = $event->transaction;

        // ส่ง FCM เฉพาะบิลที่ต้องรอชำระเงิน (promptpay/bank_transfer)
        if (! in_array($transaction->payment_method, ['promptpay', 'bank_transfer'])) {
            return;
        }

        try {
            $this->fcmService->notifyNewTransaction($transaction);
            Log::info('FCM: Sent new transaction notification', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'method' => $transaction->payment_method,
            ]);
        } catch (\Exception $e) {
            Log::error('FCM: Failed to send new transaction notification', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(NewTransactionCreated $event, \Throwable $exception): void
    {
        Log::error('SendNewTransactionFcmNotification failed', [
            'transaction_id' => $event->transaction->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
