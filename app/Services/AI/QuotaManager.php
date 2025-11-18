<?php

namespace App\Services\AI;

use App\Models\AICoreQuota;
use App\Models\AICoreFeature;
use App\Models\AICoreTenant;
use App\Models\AICoreAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Quota Manager Service
 *
 * จัดการ quota สำหรับ AI features
 * รองรับการสร้าง ตรวจสอบ ใช้ และรีเซ็ต quota
 */
class QuotaManager
{
    /**
     * สร้าง quota ใหม่สำหรับ tenant
     *
     * @param int $tenantId
     * @param int $featureId
     * @param string $periodType
     * @param int $quotaLimit
     * @return AICoreQuota|null
     */
    public function createQuota(
        int $tenantId,
        int $featureId,
        string $periodType,
        int $quotaLimit
    ): ?AICoreQuota {
        try {
            $dates = $this->calculatePeriodDates($periodType);

            return AICoreQuota::create([
                'tenant_id' => $tenantId,
                'feature_id' => $featureId,
                'period_type' => $periodType,
                'period_start' => $dates['start'],
                'period_end' => $dates['end'],
                'quota_limit' => $quotaLimit,
                'quota_used' => 0,
                'allow_overage' => false,
                'overage_used' => 0,
                'bonus_quota' => 0,
                'status' => 'active',
                'auto_reset' => true,
                'next_reset_at' => $this->calculateNextReset($periodType, $dates['end']),
            ]);
        } catch (\Exception $e) {
            Log::error('QuotaManager: สร้าง quota ล้มเหลว', [
                'tenant_id' => $tenantId,
                'feature_id' => $featureId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ตรวจสอบ quota ว่าเพียงพอหรือไม่
     *
     * @param int $tenantId
     * @param int $featureId
     * @param int $amount
     * @return bool
     */
    public function checkQuota(int $tenantId, int $featureId, int $amount = 1): bool
    {
        $quota = $this->getCurrentQuota($tenantId, $featureId);

        if (!$quota) {
            return false;
        }

        // ตรวจสอบว่า quota ยังไม่หมดอายุ
        if ($quota->isExpired() && $quota->shouldReset()) {
            $this->resetQuota($quota);
            $quota = $quota->fresh();
        }

        return $quota->hasRemaining($amount);
    }

    /**
     * ใช้ quota
     *
     * @param int $tenantId
     * @param int $featureId
     * @param int $amount
     * @return bool
     */
    public function consumeQuota(int $tenantId, int $featureId, int $amount = 1): bool
    {
        $quota = $this->getCurrentQuota($tenantId, $featureId);

        if (!$quota) {
            return false;
        }

        $success = $quota->consume($amount);

        // ตรวจสอบว่า quota ใกล้หมดหรือไม่ (เหลือ 10% หรือ 20%)
        if ($success) {
            $this->checkQuotaThresholds($quota);
        }

        return $success;
    }

    /**
     * รีเซ็ต quota
     *
     * @param AICoreQuota $quota
     * @return bool
     */
    public function resetQuota(AICoreQuota $quota): bool
    {
        return $quota->reset();
    }

    /**
     * เพิ่มโควต้าพิเศษ
     *
     * @param int $tenantId
     * @param int $featureId
     * @param int $amount
     * @param string|null $reason
     * @return bool
     */
    public function addBonusQuota(
        int $tenantId,
        int $featureId,
        int $amount,
        ?string $reason = null
    ): bool {
        $quota = $this->getCurrentQuota($tenantId, $featureId);

        if (!$quota) {
            return false;
        }

        return $quota->addBonus($amount, $reason);
    }

    /**
     * ดึง quota ปัจจุบันของ tenant
     *
     * @param int $tenantId
     * @param int $featureId
     * @return AICoreQuota|null
     */
    public function getCurrentQuota(int $tenantId, int $featureId): ?AICoreQuota
    {
        return AICoreQuota::where('tenant_id', $tenantId)
            ->where('feature_id', $featureId)
            ->where('status', 'active')
            ->where('period_end', '>=', now())
            ->first();
    }

    /**
     * คำนวณวันเริ่มต้นและสิ้นสุดของ period
     *
     * @param string $periodType
     * @return array
     */
    private function calculatePeriodDates(string $periodType): array
    {
        $now = now();

        return match ($periodType) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'yearly' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'lifetime' => [
                'start' => $now->copy(),
                'end' => $now->copy()->addYears(10),
            ],
            default => [
                'start' => $now->copy(),
                'end' => $now->copy()->addMonth(),
            ],
        };
    }

    /**
     * คำนวณเวลารีเซ็ตครั้งถัดไป
     *
     * @param string $periodType
     * @param Carbon $periodEnd
     * @return Carbon|null
     */
    private function calculateNextReset(string $periodType, Carbon $periodEnd): ?Carbon
    {
        if ($periodType === 'lifetime') {
            return null;
        }

        return match ($periodType) {
            'daily' => $periodEnd->copy()->addDay(),
            'weekly' => $periodEnd->copy()->addWeek(),
            'monthly' => $periodEnd->copy()->addMonth(),
            'yearly' => $periodEnd->copy()->addYear(),
            default => $periodEnd->copy()->addMonth(),
        };
    }

    /**
     * ตรวจสอบ threshold และส่ง alert
     *
     * @param AICoreQuota $quota
     * @return void
     */
    private function checkQuotaThresholds(AICoreQuota $quota): void
    {
        $totalLimit = $quota->quota_limit + $quota->bonus_quota;
        $remaining = $totalLimit - $quota->quota_used;
        $percentageUsed = ($quota->quota_used / $totalLimit) * 100;

        // แจ้งเตือนเมื่อใช้ไป 80%
        if ($percentageUsed >= 80 && $percentageUsed < 90) {
            $this->createQuotaAlert($quota, 'warning', 80);
        }

        // แจ้งเตือนเมื่อใช้ไป 90%
        if ($percentageUsed >= 90 && $percentageUsed < 100) {
            $this->createQuotaAlert($quota, 'warning', 90);
        }

        // แจ้งเตือนเมื่อเกิน 100%
        if ($percentageUsed >= 100) {
            $this->createQuotaAlert($quota, 'error', 100);
        }
    }

    /**
     * สร้าง alert สำหรับ quota
     *
     * @param AICoreQuota $quota
     * @param string $severity
     * @param int $percentage
     * @return void
     */
    private function createQuotaAlert(AICoreQuota $quota, string $severity, int $percentage): void
    {
        $alertKey = "quota_{$quota->tenant_id}_{$quota->feature_id}_{$percentage}";

        $message = match ($percentage) {
            80 => "Quota ของคุณใช้ไปแล้ว 80% กรุณาติดตามการใช้งาน",
            90 => "⚠️ Quota ของคุณใช้ไปแล้ว 90% ใกล้ถึงขีดจำกัดแล้ว",
            100 => "❌ Quota ของคุณหมดแล้ว กรุณาเพิ่ม quota หรือรอรอบถัดไป",
            default => "Quota ของคุณใช้ไปแล้ว {$percentage}%",
        };

        AICoreAlert::createOrIncrement([
            'tenant_id' => $quota->tenant_id,
            'feature_id' => $quota->feature_id,
            'alert_type' => $percentage < 100 ? 'quota_warning' : 'quota_exceeded',
            'severity' => $severity,
            'title' => 'แจ้งเตือน Quota',
            'message' => $message,
            'alert_key' => $alertKey,
            'requires_action' => $percentage >= 100,
            'action_url' => route('admin.ai-core.quotas.manage', [
                'tenant' => $quota->tenant_id,
                'feature' => $quota->feature_id,
            ]),
            'action_label' => $percentage >= 100 ? 'เพิ่ม Quota' : 'ดูรายละเอียด',
            'notification_channels' => ['email', 'line'],
            'expires_at' => now()->addDays(7),
            'auto_dismiss' => true,
        ]);
    }

    /**
     * รีเซ็ต quota ทั้งหมดที่ถึงเวลา
     *
     * @return int จำนวน quota ที่รีเซ็ต
     */
    public function resetExpiredQuotas(): int
    {
        $count = 0;

        $quotas = AICoreQuota::where('auto_reset', true)
            ->where('next_reset_at', '<=', now())
            ->where('status', '!=', 'suspended')
            ->get();

        foreach ($quotas as $quota) {
            if ($this->resetQuota($quota)) {
                $count++;
            }
        }

        return $count;
    }
}
