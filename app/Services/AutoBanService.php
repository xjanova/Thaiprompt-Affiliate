<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Notifications\IpAutoBannedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoBanService
{
    /**
     * Check and auto-ban IP if threshold exceeded
     */
    public static function checkAndBan(string $ip, string $eventType): bool
    {
        // Check if auto-ban is enabled
        if (!config('autoban.enabled')) {
            return false;
        }

        // Check if IP is whitelisted
        if (in_array($ip, config('autoban.whitelist', []))) {
            return false;
        }

        // Check if IP is already in whitelist
        if (BlockedIp::isWhitelisted($ip)) {
            return false;
        }

        // Check if IP is already blocked
        if (BlockedIp::isBlocked($ip)) {
            return false;
        }

        // Get appropriate config based on event type
        $config = self::getConfigForEventType($eventType);

        if (!$config || !$config['enabled']) {
            return false;
        }

        // Count violations in time window
        $violations = self::countViolations($ip, $eventType, $config['time_window']);

        Log::info("Auto-ban check for IP {$ip}: {$violations}/{$config['threshold']} violations");

        // Ban if threshold exceeded
        if ($violations >= $config['threshold']) {
            return self::banIp($ip, $eventType, $violations, $config);
        }

        return false;
    }

    /**
     * Get config for event type
     */
    protected static function getConfigForEventType(string $eventType): ?array
    {
        $mapping = [
            'login_failed' => 'failed_login',
            'turnstile_failed' => 'turnstile_failure',
            'rate_limit_exceeded' => 'rate_limit',
        ];

        $configKey = $mapping[$eventType] ?? 'suspicious_activity';

        return config("autoban.{$configKey}");
    }

    /**
     * Count violations in time window
     */
    protected static function countViolations(string $ip, string $eventType, int $timeWindowMinutes): int
    {
        $since = Carbon::now()->subMinutes($timeWindowMinutes);

        return SecurityLog::where('ip_address', $ip)
            ->where('event_type', $eventType)
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * Ban IP
     */
    protected static function banIp(string $ip, string $eventType, int $violations, array $config): bool
    {
        try {
            $expiresAt = Carbon::now()->addMinutes($config['ban_duration']);

            // Get IP intelligence
            $ipInfo = IpIntelligenceService::getIpInfo($ip);

            $reason = sprintf(
                'Auto-banned: %d %s violations in %d minutes',
                $violations,
                $eventType,
                $config['time_window']
            );

            // Create blocked IP record
            $blockedIp = BlockedIp::create([
                'ip_address' => $ip,
                'type' => 'blacklist',
                'reason' => $reason,
                'expires_at' => $expiresAt,
                'blocked_by' => null, // System auto-ban
                'notes' => json_encode([
                    'auto_ban' => true,
                    'event_type' => $eventType,
                    'violations' => $violations,
                    'time_window' => $config['time_window'],
                    'ban_duration' => $config['ban_duration'],
                    'country' => $ipInfo['country_name'] ?? 'Unknown',
                ]),
                'is_active' => true,
            ]);

            // Log the auto-ban event
            SecurityLog::create([
                'event_type' => 'ip_auto_banned',
                'ip_address' => $ip,
                'severity' => 'critical',
                'description' => $reason,
                'user_agent' => request()->userAgent(),
                'country_code' => $ipInfo['country_code'],
                'country_name' => $ipInfo['country_name'],
                'city' => $ipInfo['city'],
                'metadata' => json_encode([
                    'violations' => $violations,
                    'expires_at' => $expiresAt->toDateTimeString(),
                ]),
            ]);

            // Send notifications
            self::sendNotifications($ip, $eventType, $violations, $ipInfo, $expiresAt);

            Log::warning("IP {$ip} has been auto-banned for {$config['ban_duration']} minutes due to {$violations} {$eventType} violations");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to auto-ban IP {$ip}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notifications about auto-ban
     */
    protected static function sendNotifications(string $ip, string $eventType, int $violations, array $ipInfo, Carbon $expiresAt): void
    {
        if (!config('autoban.notifications.enabled')) {
            return;
        }

        try {
            // Email notification
            if (config('autoban.notifications.email.enabled')) {
                $recipients = explode(',', config('autoban.notifications.email.recipients'));

                foreach ($recipients as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        Notification::route('mail', $email)
                            ->notify(new IpAutoBannedNotification(
                                $ip,
                                $eventType,
                                $violations,
                                $ipInfo,
                                $expiresAt
                            ));
                    }
                }
            }

            // Slack notification (if configured)
            if (config('autoban.notifications.slack.enabled') && config('autoban.notifications.slack.webhook_url')) {
                self::sendSlackNotification($ip, $eventType, $violations, $ipInfo, $expiresAt);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send auto-ban notifications: " . $e->getMessage());
        }
    }

    /**
     * Send Slack notification
     */
    protected static function sendSlackNotification(string $ip, string $eventType, int $violations, array $ipInfo, Carbon $expiresAt): void
    {
        $webhookUrl = config('autoban.notifications.slack.webhook_url');

        $flag = IpIntelligenceService::getCountryFlag($ipInfo['country_code']);
        $country = $ipInfo['country_name'] ?? 'Unknown';

        $message = [
            'text' => '🚨 IP Auto-Banned Alert',
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🚨 IP Auto-Banned',
                    ],
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        ['type' => 'mrkdwn', 'text' => "*IP Address:*\n{$ip}"],
                        ['type' => 'mrkdwn', 'text' => "*Location:*\n{$flag} {$country}"],
                        ['type' => 'mrkdwn', 'text' => "*Event Type:*\n{$eventType}"],
                        ['type' => 'mrkdwn', 'text' => "*Violations:*\n{$violations}"],
                        ['type' => 'mrkdwn', 'text' => "*Expires:*\n{$expiresAt->diffForHumans()}"],
                    ],
                ],
            ],
        ];

        try {
            \Illuminate\Support\Facades\Http::post($webhookUrl, $message);
        } catch (\Exception $e) {
            Log::error("Failed to send Slack notification: " . $e->getMessage());
        }
    }

    /**
     * Get auto-ban statistics
     */
    public static function getStatistics(): array
    {
        $today = Carbon::today();
        $lastWeek = Carbon::now()->subWeek();

        return [
            'total_auto_bans' => SecurityLog::where('event_type', 'ip_auto_banned')->count(),
            'auto_bans_today' => SecurityLog::where('event_type', 'ip_auto_banned')
                ->whereDate('created_at', $today)
                ->count(),
            'auto_bans_this_week' => SecurityLog::where('event_type', 'ip_auto_banned')
                ->where('created_at', '>=', $lastWeek)
                ->count(),
            'currently_banned' => BlockedIp::where('type', 'blacklist')
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
                })
                ->count(),
        ];
    }
}
