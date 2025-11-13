<?php

namespace App\Notifications\TPIX;

use App\Models\TPIXToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddressFrozenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected TPIXToken $token;
    protected string $address;
    protected string $reason;

    public function __construct(TPIXToken $token, string $address, string $reason)
    {
        $this->token = $token;
        $this->address = $address;
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
            ->subject('Address Frozen - ' . $this->token->symbol)
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line("ที่อยู่ของคุณถูกระงับการใช้งาน")
            ->line("Token: {$this->token->symbol}")
            ->line("ที่อยู่: {$this->address}")
            ->line("เหตุผล: {$this->reason}")
            ->line("กรุณาติดต่อ Support หากคุณคิดว่านี่เป็นความผิดพลาด")
            ->action('ติดต่อ Support', route('support'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'address_frozen',
            'token_id' => $this->token->id,
            'token_symbol' => $this->token->symbol,
            'address' => $this->address,
            'reason' => $this->reason,
        ];
    }
}
