<?php

namespace App\Notifications\TPIX;

use App\Models\TPIXStake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StakeUnlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected TPIXStake $stake;

    public function __construct(TPIXStake $stake)
    {
        $this->stake = $stake;
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $pool = $this->stake->pool;
        $rewards = $this->stake->getUnclaimedRewards();

        return (new MailMessage)
            ->subject('Stake Unlocked!')
            ->greeting('สวัสดี ' . $notifiable->name)
            ->line("Stake ของคุณปลดล็อคแล้ว!")
            ->line("Pool: {$pool->name}")
            ->line("จำนวน: " . number_format($this->stake->amount, 8))
            ->line("รางวัลรอรับ: " . number_format($rewards, 8))
            ->action('Unstake เลย', route('user.tokens.staking'))
            ->line('คุณสามารถ unstake และรับรางวัลได้แล้ว!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'stake_unlocked',
            'stake_id' => $this->stake->id,
            'pool_id' => $this->stake->pool_id,
            'pool_name' => $this->stake->pool->name,
            'amount' => $this->stake->amount,
            'rewards' => $this->stake->getUnclaimedRewards(),
            'message' => "Your stake is now unlocked! You can unstake and claim rewards.",
        ];
    }
}
