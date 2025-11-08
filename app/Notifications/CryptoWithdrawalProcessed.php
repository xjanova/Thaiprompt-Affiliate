<?php

namespace App\Notifications;

use App\Models\CryptoWithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CryptoWithdrawalProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    protected CryptoWithdrawalRequest $withdrawal;

    public function __construct(CryptoWithdrawalRequest $withdrawal)
    {
        $this->withdrawal = $withdrawal;
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
            ->subject('✅ การถอนเงิน ' . $currency . ' กำลังดำเนินการ')
            ->greeting('สวัสดี ' . $notifiable->name . '!')
            ->line("คำขอถอนเงินของคุณกำลังถูกประมวลผลบน Blockchain")
            ->line('รายละเอียด:')
            ->line("จำนวน: {$amount} {$currency}")
            ->line("ปลายทาง: " . substr($this->withdrawal->to_address, 0, 20) . '...')
            ->line("TX Hash: " . substr($this->withdrawal->tx_hash, 0, 20) . '...')
            ->line("Network: " . ucfirst($this->withdrawal->network))
            ->action('ติดตามสถานะ', route('user.crypto-wallet.withdrawals'))
            ->line('โปรดรอการยืนยันจาก Blockchain (อาจใช้เวลา 5-30 นาที)');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'crypto_withdrawal_processed',
            'withdrawal_id' => $this->withdrawal->id,
            'currency_code' => $this->withdrawal->currency->code,
            'amount' => $this->withdrawal->amount,
            'to_address' => $this->withdrawal->to_address,
            'tx_hash' => $this->withdrawal->tx_hash,
            'network' => $this->withdrawal->network,
            'status' => $this->withdrawal->status,
            'message' => "การถอนเงิน {$this->withdrawal->amount} {$this->withdrawal->currency->code} กำลังดำเนินการ",
        ];
    }
}
