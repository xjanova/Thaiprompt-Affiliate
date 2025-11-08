<?php

namespace App\Notifications;

use App\Models\CryptoTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CryptoDepositReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected CryptoTransaction $transaction;

    public function __construct(CryptoTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $amount = number_format($this->transaction->amount, 8);
        $currency = $this->transaction->currency->code;

        return (new MailMessage)
            ->subject('🎉 ได้รับเงินฝาก ' . $currency)
            ->greeting('สวัสดี ' . $notifiable->name . '!')
            ->line("คุณได้รับเงินฝาก {$amount} {$currency} เข้ากระเป๋าเรียบร้อยแล้ว")
            ->line('รายละเอียด:')
            ->line("จำนวน: {$amount} {$currency}")
            ->line("ยอดเป็นเงินบาท: ฿" . number_format($this->transaction->amount_thb ?? 0, 2))
            ->line("TX Hash: " . substr($this->transaction->tx_hash, 0, 20) . '...')
            ->action('ดูรายละเอียด', route('user.crypto-wallet.transaction.detail', $this->transaction->id))
            ->line('ขอบคุณที่ใช้บริการ!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'crypto_deposit',
            'transaction_id' => $this->transaction->id,
            'currency_code' => $this->transaction->currency->code,
            'amount' => $this->transaction->amount,
            'amount_thb' => $this->transaction->amount_thb,
            'tx_hash' => $this->transaction->tx_hash,
            'status' => $this->transaction->status,
            'message' => "ได้รับเงินฝาก {$this->transaction->amount} {$this->transaction->currency->code}",
        ];
    }
}
