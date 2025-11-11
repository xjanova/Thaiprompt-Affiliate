<?php

namespace App\Services;

use App\Models\MlmProspect;
use App\Models\LineSignupFlow;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Conversation Timeout Service
 *
 * Manages conversation timeouts, auto-cleanup, and resume functionality
 * for LINE signup conversations.
 */
class ConversationTimeoutService
{
    /**
     * Conversation timeout in seconds (1 hour)
     */
    private const TIMEOUT_DURATION = 3600;

    /**
     * Warning threshold before timeout (10 minutes before)
     */
    private const WARNING_THRESHOLD = 600;

    /**
     * Maximum conversation duration (24 hours - absolute limit)
     */
    private const MAX_DURATION = 86400;

    public function __construct(
        private LineService $lineService
    ) {}

    /**
     * Start or restart conversation tracking
     */
    public function startTracking(MlmProspect $prospect): void
    {
        $totalSteps = LineSignupFlow::active()->count();
        $timeoutAt = now()->addSeconds(self::TIMEOUT_DURATION);

        $prospect->update([
            'conversation_started_at' => now(),
            'conversation_updated_at' => now(),
            'conversation_timeout_at' => $timeoutAt,
            'conversation_expired' => false,
            'conversation_total_steps' => $totalSteps,
            'conversation_completed_steps' => 0,
            'conversation_progress_percent' => 0,
        ]);

        Log::info('Conversation tracking started', [
            'prospect_id' => $prospect->id,
            'timeout_at' => $timeoutAt,
            'total_steps' => $totalSteps,
        ]);
    }

    /**
     * Update conversation activity (reset timeout)
     */
    public function updateActivity(MlmProspect $prospect): void
    {
        $timeoutAt = now()->addSeconds(self::TIMEOUT_DURATION);

        $prospect->update([
            'conversation_updated_at' => now(),
            'conversation_timeout_at' => $timeoutAt,
            'conversation_message_count' => $prospect->conversation_message_count + 1,
        ]);

        // Calculate progress
        $this->updateProgress($prospect);

        Log::debug('Conversation activity updated', [
            'prospect_id' => $prospect->id,
            'new_timeout_at' => $timeoutAt,
            'message_count' => $prospect->conversation_message_count,
        ]);
    }

    /**
     * Update conversation progress
     */
    public function updateProgress(MlmProspect $prospect): void
    {
        if (!$prospect->conversation_total_steps) {
            return;
        }

        $currentStep = LineSignupFlow::getByStepKey($prospect->conversation_step);
        if (!$currentStep) {
            return;
        }

        $completedSteps = $currentStep->step_order;
        $progressPercent = round(($completedSteps / $prospect->conversation_total_steps) * 100);

        $prospect->update([
            'conversation_completed_steps' => $completedSteps,
            'conversation_progress_percent' => $progressPercent,
        ]);
    }

    /**
     * Check if conversation is expired
     */
    public function isExpired(MlmProspect $prospect): bool
    {
        if (!$prospect->conversation_started_at) {
            return false;
        }

        // Check if marked as expired
        if ($prospect->conversation_expired) {
            return true;
        }

        // Check timeout
        if ($prospect->conversation_timeout_at && now()->isAfter($prospect->conversation_timeout_at)) {
            return true;
        }

        // Check absolute max duration
        $duration = now()->diffInSeconds($prospect->conversation_started_at);
        if ($duration > self::MAX_DURATION) {
            return true;
        }

        return false;
    }

    /**
     * Check if warning should be sent (close to timeout)
     */
    public function shouldSendWarning(MlmProspect $prospect): bool
    {
        if (!$prospect->conversation_timeout_at) {
            return false;
        }

        $remainingSeconds = now()->diffInSeconds($prospect->conversation_timeout_at, false);

        // Send warning if less than WARNING_THRESHOLD seconds remaining
        // and warning hasn't been sent yet
        if ($remainingSeconds > 0 && $remainingSeconds <= self::WARNING_THRESHOLD) {
            $warningKey = "conversation_warning_sent:{$prospect->id}";
            if (!Cache::has($warningKey)) {
                Cache::put($warningKey, true, now()->addSeconds(self::TIMEOUT_DURATION));
                return true;
            }
        }

        return false;
    }

    /**
     * Send timeout warning message
     */
    public function sendTimeoutWarning(MlmProspect $prospect): void
    {
        $remainingMinutes = ceil(now()->diffInSeconds($prospect->conversation_timeout_at, false) / 60);

        $message = "⏰ สวัสดีค่ะ!\n\n";
        $message .= "การสมัครสมาชิกของคุณจะหมดอายุในอีก {$remainingMinutes} นาที\n\n";
        $message .= "กรุณาตอบกลับให้เสร็จสิ้นภายในเวลาที่กำหนด หรือคุณสามารถเริ่มใหม่ได้ตลอดเวลา 😊";

        $this->lineService->sendPushMessage($prospect->line_user_id, $message);

        Log::info('Timeout warning sent', [
            'prospect_id' => $prospect->id,
            'remaining_minutes' => $remainingMinutes,
        ]);
    }

    /**
     * Expire conversation and send notification
     */
    public function expireConversation(MlmProspect $prospect): void
    {
        $prospect->update([
            'conversation_expired' => true,
            'status' => 'expired',
        ]);

        // Send expiration message with resume option
        $resumeUrl = route('line.signup.invitation', ['token' => $prospect->referral_token]);

        $message = "⏱️ การสมัครสมาชิกของคุณหมดอายุแล้ว\n\n";
        $message .= "ไม่ต้องกังวลค่ะ! คุณสามารถเริ่มใหม่ได้ตลอดเวลา\n\n";
        $message .= "กดลิงก์นี้เพื่อเริ่มใหม่:\n{$resumeUrl}\n\n";
        $message .= "📊 ความคืบหน้าของคุณ: {$prospect->conversation_progress_percent}%\n";
        $message .= "✨ เราจะช่วยคุณทำต่อจากจุดที่ค้างไว้ได้เลย!";

        $this->lineService->sendPushMessage($prospect->line_user_id, $message);

        Log::info('Conversation expired', [
            'prospect_id' => $prospect->id,
            'progress' => $prospect->conversation_progress_percent,
            'duration_seconds' => now()->diffInSeconds($prospect->conversation_started_at),
        ]);
    }

    /**
     * Resume expired conversation
     */
    public function resumeConversation(MlmProspect $prospect): void
    {
        // Reset expiration
        $prospect->update([
            'conversation_expired' => false,
            'status' => 'in_progress',
            'conversation_retry_count' => $prospect->conversation_retry_count + 1,
        ]);

        // Restart tracking
        $this->startTracking($prospect);

        // Send resume message
        $currentStep = LineSignupFlow::getByStepKey($prospect->conversation_step);

        $message = "👋 ยินดีต้อนรับกลับค่ะ!\n\n";
        $message .= "📊 คุณทำไปแล้ว {$prospect->conversation_progress_percent}%\n";
        $message .= "ขั้นตอนที่ {$prospect->conversation_completed_steps}/{$prospect->conversation_total_steps}\n\n";
        $message .= "มาทำต่อกันเลยนะคะ 😊\n\n";

        if ($currentStep) {
            $message .= "---\n\n";
            $message .= $currentStep->message_text;
        } else {
            $message .= "กรุณารอสักครู่...";
        }

        $this->lineService->sendPushMessage($prospect->line_user_id, $message);

        Log::info('Conversation resumed', [
            'prospect_id' => $prospect->id,
            'retry_count' => $prospect->conversation_retry_count,
            'current_step' => $prospect->conversation_step,
        ]);
    }

    /**
     * Cleanup expired conversations (for scheduled task)
     *
     * @return array Statistics
     */
    public function cleanupExpiredConversations(): array
    {
        $stats = [
            'checked' => 0,
            'expired' => 0,
            'warnings_sent' => 0,
        ];

        // Get all prospects with active conversations
        $prospects = MlmProspect::whereIn('status', ['in_progress', 'pending'])
            ->whereNotNull('conversation_started_at')
            ->where('conversation_expired', false)
            ->get();

        $stats['checked'] = $prospects->count();

        foreach ($prospects as $prospect) {
            // Check if should expire
            if ($this->isExpired($prospect)) {
                $this->expireConversation($prospect);
                $stats['expired']++;
            }
            // Check if should send warning
            elseif ($this->shouldSendWarning($prospect)) {
                $this->sendTimeoutWarning($prospect);
                $stats['warnings_sent']++;
            }
        }

        Log::info('Conversation cleanup completed', $stats);

        return $stats;
    }

    /**
     * Get remaining time in human-readable format
     */
    public function getRemainingTime(MlmProspect $prospect): ?string
    {
        if (!$prospect->conversation_timeout_at) {
            return null;
        }

        $remainingSeconds = now()->diffInSeconds($prospect->conversation_timeout_at, false);

        if ($remainingSeconds <= 0) {
            return 'หมดอายุแล้ว';
        }

        $minutes = floor($remainingSeconds / 60);
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        if ($hours > 0) {
            return "{$hours} ชั่วโมง {$minutes} นาที";
        }

        return "{$minutes} นาที";
    }

    /**
     * Get conversation statistics for monitoring
     */
    public function getStatistics(): array
    {
        return [
            'active' => MlmProspect::whereIn('status', ['in_progress', 'pending'])
                ->where('conversation_expired', false)
                ->count(),
            'expired' => MlmProspect::where('conversation_expired', true)->count(),
            'avg_duration' => MlmProspect::where('status', 'completed')
                ->whereNotNull('conversation_started_at')
                ->whereNotNull('registered_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, conversation_started_at, registered_at)) as avg')
                ->value('avg'),
            'avg_progress' => MlmProspect::whereIn('status', ['in_progress', 'pending'])
                ->where('conversation_expired', false)
                ->avg('conversation_progress_percent'),
        ];
    }
}
