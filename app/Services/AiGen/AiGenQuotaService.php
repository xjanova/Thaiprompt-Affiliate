<?php

namespace App\Services\AiGen;

use App\Models\User;
use App\Models\AiGenQuota;
use App\Models\AiGenUsageLog;
use Carbon\Carbon;

class AiGenQuotaService
{
    /**
     * Check if user can use free quota.
     */
    public function canUseFreeQuota(User $user, string $type): bool
    {
        // Admin always has unlimited access
        if ($user->is_admin || $user->is_super_admin) {
            return true;
        }

        $quota = AiGenQuota::getQuotaForUser($user);

        if (!$quota) {
            return false;
        }

        // Check daily quota
        $dailyUsage = $this->getDailyUsage($user, $type);
        $dailyLimit = $type === 'image' ? $quota->free_image_daily : $quota->free_video_daily;

        if ($dailyLimit > 0 && $dailyUsage >= $dailyLimit) {
            return false;
        }

        // Check monthly quota
        $monthlyUsage = $this->getMonthlyUsage($user, $type);
        $monthlyLimit = $type === 'image' ? $quota->free_image_monthly : $quota->free_video_monthly;

        if ($monthlyLimit > 0 && $monthlyUsage >= $monthlyLimit) {
            return false;
        }

        return true;
    }

    /**
     * Get daily usage count for a user.
     */
    public function getDailyUsage(User $user, string $type): int
    {
        return AiGenUsageLog::where('user_id', $user->id)
            ->where('generation_type', $type)
            ->where('is_free_quota', true)
            ->where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    /**
     * Get monthly usage count for a user.
     */
    public function getMonthlyUsage(User $user, string $type): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return AiGenUsageLog::where('user_id', $user->id)
            ->where('generation_type', $type)
            ->where('is_free_quota', true)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
    }

    /**
     * Get remaining free quota for a user.
     */
    public function getRemainingQuota(User $user, string $type): array
    {
        // Admin has unlimited
        if ($user->is_admin || $user->is_super_admin) {
            return [
                'daily' => -1, // -1 means unlimited
                'monthly' => -1,
            ];
        }

        $quota = AiGenQuota::getQuotaForUser($user);

        if (!$quota) {
            return [
                'daily' => 0,
                'monthly' => 0,
            ];
        }

        $dailyUsage = $this->getDailyUsage($user, $type);
        $monthlyUsage = $this->getMonthlyUsage($user, $type);

        $dailyLimit = $type === 'image' ? $quota->free_image_daily : $quota->free_video_daily;
        $monthlyLimit = $type === 'image' ? $quota->free_image_monthly : $quota->free_video_monthly;

        return [
            'daily' => max(0, $dailyLimit - $dailyUsage),
            'monthly' => max(0, $monthlyLimit - $monthlyUsage),
            'daily_limit' => $dailyLimit,
            'monthly_limit' => $monthlyLimit,
            'daily_used' => $dailyUsage,
            'monthly_used' => $monthlyUsage,
        ];
    }

    /**
     * Get quota summary for a user.
     */
    public function getQuotaSummary(User $user): array
    {
        return [
            'image' => $this->getRemainingQuota($user, 'image'),
            'video' => $this->getRemainingQuota($user, 'video'),
            'is_admin' => $user->is_admin || $user->is_super_admin,
        ];
    }
}
