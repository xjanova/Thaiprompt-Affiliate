<?php

namespace App\Services;

use App\Models\MlmProspect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * LINE Alert Service
 *
 * Sends notifications and alerts for LINE signup events:
 * - New signup completions
 * - High dropout rates
 * - Unusual activity patterns
 * - System issues
 */
class LineAlertService
{
    /**
     * Alert thresholds
     */
    private const DROPOUT_RATE_THRESHOLD = 50; // %
    private const ERROR_RATE_THRESHOLD = 10; // %
    private const SLOW_RESPONSE_THRESHOLD = 300; // seconds

    public function __construct(
        private LineService $lineService
    ) {}

    /**
     * Send new signup completion alert to sponsor
     *
     * @param MlmProspect $prospect
     * @return void
     */
    public function notifyNewSignup(MlmProspect $prospect): void
    {
        $sponsor = $prospect->sponsorUser;

        if (!$sponsor || !$sponsor->line_user_id) {
            return;
        }

        $user = $prospect->registeredUser;

        $message = "🎉 มีสมาชิกใหม่สมัครผ่านคุณแล้ว!\n\n";
        $message .= "👤 ชื่อ: {$user->name}\n";
        $message .= "📧 อีเมล: {$user->email}\n";
        $message .= "📅 เวลา: " . now()->format('d/m/Y H:i') . "\n\n";
        $message .= "ยินดีด้วยค่ะ! 🎊";

        try {
            $this->lineService->sendPushMessage($sponsor->line_user_id, $message);

            Log::info('New signup alert sent', [
                'prospect_id' => $prospect->id,
                'sponsor_id' => $sponsor->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send new signup alert', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check and alert on high dropout rate
     *
     * @return void
     */
    public function checkDropoutRate(): void
    {
        $cacheKey = 'alert:dropout_check';

        // Check only once per hour
        if (Cache::has($cacheKey)) {
            return;
        }

        $total = MlmProspect::where('conversation_started_at', '>=', now()->subDay())->count();
        $expired = MlmProspect::where('conversation_expired', true)
            ->where('conversation_updated_at', '>=', now()->subDay())
            ->count();

        if ($total === 0) {
            return;
        }

        $dropoutRate = ($expired / $total) * 100;

        if ($dropoutRate > self::DROPOUT_RATE_THRESHOLD) {
            $this->sendAdminAlert(
                '⚠️ HIGH DROPOUT RATE DETECTED',
                "Dropout rate in last 24h: {$dropoutRate}%\nTotal started: {$total}\nExpired: {$expired}"
            );

            Cache::put($cacheKey, true, now()->addHour());
        }
    }

    /**
     * Alert on unusual activity pattern
     *
     * @param string $pattern
     * @param array $details
     * @return void
     */
    public function alertUnusualActivity(string $pattern, array $details): void
    {
        $message = "🚨 UNUSUAL ACTIVITY DETECTED\n\n";
        $message .= "Pattern: {$pattern}\n\n";
        $message .= "Details:\n";

        foreach ($details as $key => $value) {
            $message .= "- {$key}: {$value}\n";
        }

        $this->sendAdminAlert('Unusual Activity', $message);
    }

    /**
     * Alert on system error
     *
     * @param string $error
     * @param array $context
     * @return void
     */
    public function alertSystemError(string $error, array $context = []): void
    {
        $message = "❌ SYSTEM ERROR\n\n";
        $message .= "Error: {$error}\n\n";

        if (!empty($context)) {
            $message .= "Context:\n";
            foreach ($context as $key => $value) {
                $message .= "- {$key}: {$value}\n";
            }
        }

        $this->sendAdminAlert('System Error', $message);
    }

    /**
     * Send daily summary to admins
     *
     * @param array $stats
     * @return void
     */
    public function sendDailySummary(array $stats): void
    {
        $message = "📊 LINE OA Daily Summary\n";
        $message .= "Date: " . now()->format('d/m/Y') . "\n\n";

        $message .= "✅ Completed: {$stats['completed']}\n";
        $message .= "📝 In Progress: {$stats['in_progress']}\n";
        $message .= "❌ Expired: {$stats['expired']}\n";
        $message .= "📈 Conversion Rate: {$stats['conversion_rate']}%\n\n";

        $message .= "🎯 Overall Performance:\n";
        $message .= $this->getPerformanceEmoji($stats['conversion_rate']);

        $this->sendAdminAlert('Daily Summary', $message);
    }

    /**
     * Send alert to admin(s)
     *
     * @param string $title
     * @param string $message
     * @return void
     */
    private function sendAdminAlert(string $title, string $message): void
    {
        // Get admin users with LINE connected
        $admins = \App\Models\User::where('is_admin', true)
            ->whereNotNull('line_user_id')
            ->get();

        foreach ($admins as $admin) {
            try {
                $fullMessage = "📢 {$title}\n\n{$message}";
                $this->lineService->sendPushMessage($admin->line_user_id, $fullMessage);
            } catch (\Exception $e) {
                Log::error('Failed to send admin alert', [
                    'admin_id' => $admin->id,
                    'title' => $title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Also log to system
        Log::channel('line')->warning($title, ['message' => $message]);
    }

    /**
     * Get performance emoji based on conversion rate
     *
     * @param float $rate
     * @return string
     */
    private function getPerformanceEmoji(float $rate): string
    {
        if ($rate >= 70) {
            return "🔥 Excellent! Keep it up!";
        } elseif ($rate >= 50) {
            return "👍 Good performance!";
        } elseif ($rate >= 30) {
            return "📊 Average performance";
        } else {
            return "⚠️ Needs attention";
        }
    }

    /**
     * Check and send milestone alerts
     *
     * @return void
     */
    public function checkMilestones(): void
    {
        $cacheKey = 'alert:milestone_check';

        if (Cache::has($cacheKey)) {
            return;
        }

        $totalCompleted = MlmProspect::where('status', 'completed')->count();

        $milestones = [100, 500, 1000, 5000, 10000];

        foreach ($milestones as $milestone) {
            $cacheKey = "milestone_reached:{$milestone}";

            if ($totalCompleted >= $milestone && !Cache::has($cacheKey)) {
                $this->sendAdminAlert(
                    "🎉 Milestone Reached!",
                    "Congratulations! You've reached {$milestone} completed signups via LINE OA!"
                );

                Cache::forever($cacheKey, true);
                break;
            }
        }

        Cache::put('alert:milestone_check', true, now()->addDay());
    }
}
