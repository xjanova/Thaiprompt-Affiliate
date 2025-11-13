<?php

namespace App\Notifications\TPIX;

use App\Models\CoinControlAction;
use App\Models\TPIXToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TokenMintedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected TPIXToken $token;
    protected CoinControlAction $action;

    public function __construct(TPIXToken $token, CoinControlAction $action)
    {
        $this->token = $token;
        $this->action = $action;
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tokens Minted - ' . $this->token->symbol)
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line("มีการสร้าง Token เพิ่มเติม")
            ->line("Token: {$this->token->symbol}")
            ->line("จำนวน: " . number_format($this->action->amount, 8))
            ->line("ไปยังที่อยู่: {$this->action->target_address}")
            ->line("เหตุผล: {$this->action->reason}")
            ->action('ดู Token', route('user.tokens.show', $this->token->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'token_minted',
            'token_id' => $this->token->id,
            'token_symbol' => $this->token->symbol,
            'amount' => $this->action->amount,
            'target_address' => $this->action->target_address,
            'reason' => $this->action->reason,
        ];
    }
}
