<?php

namespace App\Notifications;

use App\Models\CryptoWithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CryptoWithdrawalRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected CryptoWithdrawalRequest $withdrawal;
    protected string $reason;

    public function __construct(CryptoWithdrawalRequest $withdrawal, string $reason)
    {
        $this->withdrawal = $withdrawal;
        $this->reason = $reason;
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
        $amount = number_format($this->withdrawal->amount, 8);
        $currency = $this->withdrawal->currency->code;

        return (new MailMessage)
            ->subject('❌ คำขอถอนเงิน ' . $currency . ' ถูกปฏิเสธ')
            ->greeting('สวัสดี ' . $notifiable->name . '!')
            ->line("คำขอถอนเงินของคุณถูกปฏิเสธ")
            ->line('รายละเอียด:')
            ->line("จำนวน: {$amount} {$currency}")
            ->line("ปลายทาง: " . substr($this->withdrawal->to_address, 0, 20) . '...')
            ->line('')
            ->line('**เหตุผล:**')
            ->line($this->reason)
            ->line('')
            ->line("ยอดเงินได้ถูกคืนเข้ากระเป๋าของคุณแล้ว")
            ->action('ดูรายละเอียด', route('user.crypto-wallet.withdrawals'))
            ->line('หากมีข้อสงสัย กรุณาติดต่อฝ่ายสนับสนุน');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'crypto_withdrawal_rejected',
            'withdrawal_id' => $this->withdrawal->id,
            'currency_code' => $this->withdrawal->currency->code,
            'amount' => $this->withdrawal->amount,
            'to_address' => $this->withdrawal->to_address,
            'reason' => $this->reason,
            'status' => $this->withdrawal->status,
            'message' => "คำขอถอนเงิน {$this->withdrawal->amount} {$this->withdrawal->currency->code} ถูกปฏิเสธ",
        ];
    }
}
