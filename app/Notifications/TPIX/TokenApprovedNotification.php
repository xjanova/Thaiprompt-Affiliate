<?php

namespace App\Notifications\TPIX;

use App\Models\TPIXToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TokenApprovedNotification extends Notification implements ShouldQueue
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
            ->subject('Token Approved - ' . $this->token->symbol)
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line("Token {$this->token->name} ({$this->token->symbol}) ของคุณได้รับการอนุมัติแล้ว!")
            ->line("Token ของคุณจะปรากฏใน Marketplace เร็วๆนี้")
            ->action('ดู Token', route('user.tokens.show', $this->token->id))
            ->line('ขอบคุณที่ใช้บริการ TPIX!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'token_approved',
            'token_id' => $this->token->id,
            'token_name' => $this->token->name,
            'token_symbol' => $this->token->symbol,
            'message' => "Your token {$this->token->symbol} has been approved!",
        ];
    }
}
