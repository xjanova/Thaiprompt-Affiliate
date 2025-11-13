<?php

namespace App\Notifications\TPIX;

use App\Models\TPIXReferralReward;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralRewardNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected TPIXReferralReward $reward;

    public function __construct(TPIXReferralReward $reward)
    {
        $this->reward = $reward;
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $token = $this->reward->token;

        return (new MailMessage)
            ->subject('Referral Reward Received!')
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line("คุณได้รับรางวัลจากการแนะนำ!")
            ->line("ประเภท: " . ($this->reward->reward_type === 'referrer' ? 'ผู้แนะนำ' : 'ผู้ถูกแนะนำ'))
            ->line("Token: {$token->symbol}")
            ->line("จำนวน: " . number_format($this->reward->amount, 8) . " {$token->symbol}")
            ->action('ดู Portfolio', route('user.tokens.portfolio'))
            ->line('ขอบคุณที่แนะนำเพื่อนมาใช้บริการ TPIX!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'referral_reward',
            'reward_id' => $this->reward->id,
            'reward_type' => $this->reward->reward_type,
            'token_id' => $this->reward->token_id,
            'token_symbol' => $this->reward->token->symbol,
            'amount' => $this->reward->amount,
            'message' => "You earned {$this->reward->amount} {$this->reward->token->symbol} from referral!",
        ];
    }
}
