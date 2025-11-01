<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;
use App\Services\IpIntelligenceService;

class IpAutoBannedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $ip;
    protected string $eventType;
    protected int $violations;
    protected array $ipInfo;
    protected Carbon $expiresAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $ip,
        string $eventType,
        int $violations,
        array $ipInfo,
        Carbon $expiresAt
    ) {
        $this->ip = $ip;
        $this->eventType = $eventType;
        $this->violations = $violations;
        $this->ipInfo = $ipInfo;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'TP-Affiliate');
        $flag = IpIntelligenceService::getCountryFlag($this->ipInfo['country_code']);
        $country = $this->ipInfo['country_name'] ?? 'Unknown';
        $city = $this->ipInfo['city'] ?? 'Unknown';

        return (new MailMessage)
            ->subject("🚨 [{$appName}] IP Auto-Banned Alert")
            ->greeting('Security Alert!')
            ->line("An IP address has been automatically banned due to suspicious activity.")
            ->line('')
            ->line('**Ban Details:**')
            ->line("**IP Address:** {$this->ip}")
            ->line("**Location:** {$flag} {$country}, {$city}")
            ->line("**Event Type:** {$this->eventType}")
            ->line("**Violations:** {$this->violations}")
            ->line("**Ban Duration:** {$this->expiresAt->diffForHumans(null, true)}")
            ->line("**Expires:** {$this->expiresAt->format('Y-m-d H:i:s T')}")
            ->line('')
            ->line('**What should you do?**')
            ->line('• Review the security logs for suspicious patterns')
            ->line('• Check if this IP belongs to a legitimate user')
            ->line('• Consider adding trusted IPs to the whitelist')
            ->line('• Adjust auto-ban thresholds if needed')
            ->action('View Security Dashboard', url('/admin/security'))
            ->line('')
            ->line('This IP will be automatically unbanned after the ban duration expires.')
            ->salutation("Stay secure,\n{$appName} Security System");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ip' => $this->ip,
            'event_type' => $this->eventType,
            'violations' => $this->violations,
            'country' => $this->ipInfo['country_name'] ?? 'Unknown',
            'expires_at' => $this->expiresAt->toDateTimeString(),
        ];
    }
}
