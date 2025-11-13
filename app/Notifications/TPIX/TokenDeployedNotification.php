<?php

namespace App\Notifications\TPIX;

use App\Models\TPIXToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TokenDeployedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected TPIXToken $token;

    public function __construct(TPIXToken $token)
    {
        $this->token = $token;
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Token Deployed Successfully - ' . $this->token->symbol)
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line("Token {$this->token->name} ({$this->token->symbol}) ของคุณถูก deploy สำเร็จแล้ว!")
            ->line("Contract Address: {$this->token->contract_address}")
            ->line("Total Supply: " . number_format($this->token->total_supply, 2) . " {$this->token->symbol}")
            ->action('ดู Token', route('user.tokens.show', $this->token->id))
            ->line('ขอบคุณที่ใช้บริการ TPIX Blockchain!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'token_deployed',
            'token_id' => $this->token->id,
            'token_name' => $this->token->name,
            'token_symbol' => $this->token->symbol,
            'contract_address' => $this->token->contract_address,
            'message' => "Token {$this->token->symbol} has been deployed successfully!",
        ];
    }
}
