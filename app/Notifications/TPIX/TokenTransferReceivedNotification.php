<?php

namespace App\Notifications\TPIX;

use App\Models\TPIXTokenTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TokenTransferReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected TPIXTokenTransfer $transfer;

    public function __construct(TPIXTokenTransfer $transfer)
    {
        $this->transfer = $transfer;
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $token = $this->transfer->token;

        return (new MailMessage)
            ->subject('Token Received - ' . $token->symbol)
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line("คุณได้รับ Token!")
            ->line("Token: {$token->name} ({$token->symbol})")
            ->line("จำนวน: " . number_format($this->transfer->amount, 8) . " {$token->symbol}")
            ->line("จาก: {$this->transfer->from_address}")
            ->action('ดูรายการ', route('user.tokens.transactions', $token->id))
            ->line('ขอบคุณที่ใช้บริการ TPIX!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'token_received',
            'transfer_id' => $this->transfer->id,
            'token_id' => $this->transfer->token_id,
            'token_symbol' => $this->transfer->token->symbol,
            'amount' => $this->transfer->amount,
            'from_address' => $this->transfer->from_address,
            'tx_hash' => $this->transfer->tx_hash,
            'message' => "You received {$this->transfer->amount} {$this->transfer->token->symbol}",
        ];
    }
}
