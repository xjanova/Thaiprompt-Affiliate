<?php

namespace App\Notifications\TPIX;

use App\Models\TPIXToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TokenRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected TPIXToken $token;
    protected string $reason;

    public function __construct(TPIXToken $token, string $reason)
    {
        $this->token = $token;
        $this->reason = $reason;
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Token Rejected - ' . $this->token->symbol)
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line("Token {$this->token->name} ({$this->token->symbol}) ของคุณไม่ได้รับการอนุมัติ")
            ->line("เหตุผล: {$this->reason}")
            ->line("คุณสามารถแก้ไขและส่งขออนุมัติใหม่ได้")
            ->action('แก้ไข Token', route('user.tokens.edit', $this->token->id))
            ->line('หากมีคำถาม กรุณาติดต่อ Support');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'token_rejected',
            'token_id' => $this->token->id,
            'token_name' => $this->token->name,
            'token_symbol' => $this->token->symbol,
            'reason' => $this->reason,
        ];
    }
}
