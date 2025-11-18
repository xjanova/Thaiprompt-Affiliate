<?php

namespace App\Services;

use App\Models\AICoreFeature;
use App\Models\AICoreTenant;
use App\Models\AICoreFeatureAccess;
use App\Models\AICoreUsageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AI Core Service
 *
 * Service หลักสำหรับจัดการ AI Core ทั้งหมด
 * ทำหน้าที่เป็นตัวกลางในการเข้าถึง AI features
 */
class AICoreService
{
    /**
     * ตรวจสอบว่า feature เปิดใช้งานหรือไม่
     *
     * @param string $featureKey รหัส feature
     * @param int|null $tenantId Tenant ID
     * @return bool
     */
    public function isFeatureEnabled(string $featureKey, ?int $tenantId = null): bool
    {
        // ตรวจสอบว่า feature เปิดใช้งานในระบบ
        $feature = AICoreFeature::where('feature_key', $featureKey)
            ->where('is_enabled', true)
            ->first();

        if (!$feature) {
            return false;
        }

        // ถ้าไม่มี tenant ID = ตรวจสอบแค่ feature level
        if ($tenantId === null) {
            return true;
        }

        // ตรวจสอบว่า tenant มีสิทธิ์ใช้ feature นี้หรือไม่
        $access = AICoreFeatureAccess::where('tenant_id', $tenantId)
            ->where('feature_id', $feature->id)
            ->where('is_enabled', true)
            ->where('status', 'approved')
            ->first();

        if (!$access) {
            return false;
        }

        // ตรวจสอบว่า access ยังไม่หมดอายุ
        return $access->isActive();
    }

    /**
     * ตรวจสอบ quota ว่าเหลืออยู่หรือไม่
     *
     * @param string $featureKey รหัส feature
     * @param int $tenantId Tenant ID
     * @param int $amount จำนวนที่ต้องการใช้
     * @return bool
     */
    public function checkQuota(string $featureKey, int $tenantId, int $amount = 1): bool
    {
        $feature = AICoreFeature::where('feature_key', $featureKey)->first();

        if (!$feature) {
            return false;
        }

        // ถ้าไม่มี quota = ไม่จำกัด
        if ($feature->quota_type === 'none') {
            return true;
        }

        $access = AICoreFeatureAccess::where('tenant_id', $tenantId)
            ->where('feature_id', $feature->id)
            ->where('is_enabled', true)
            ->where('status', 'approved')
            ->first();

        if (!$access) {
            return false;
        }

        return $access->hasQuotaRemaining($amount);
    }

    /**
     * ใช้งาน AI feature และบันทึก usage log
     *
     * @param string $featureKey รหัส feature
     * @param int $tenantId Tenant ID
     * @param int|null $userId User ID
     * @param string $action การกระทำ
     * @param array $metadata ข้อมูลเพิ่มเติม
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function useFeature(
        string $featureKey,
        int $tenantId,
        ?int $userId = null,
        string $action = 'use',
        array $metadata = []
    ): array {
        try {
            DB::beginTransaction();

            // 1. ตรวจสอบว่า feature เปิดใช้งาน
            if (!$this->isFeatureEnabled($featureKey, $tenantId)) {
                return [
                    'success' => false,
                    'message' => 'Feature ไม่เปิดใช้งานหรือไม่มีสิทธิ์เข้าถึง',
                    'data' => null,
                ];
            }

            // 2. ตรวจสอบ quota
            $usageAmount = $metadata['usage_amount'] ?? 1;

            if (!$this->checkQuota($featureKey, $tenantId, $usageAmount)) {
                return [
                    'success' => false,
                    'message' => 'Quota ไม่เพียงพอ',
                    'data' => null,
                ];
            }

            $feature = AICoreFeature::where('feature_key', $featureKey)->first();
            $access = AICoreFeatureAccess::where('tenant_id', $tenantId)
                ->where('feature_id', $feature->id)
                ->first();

            // 3. ใช้ quota
            if ($feature->quota_type !== 'none') {
                $access->consumeQuota($usageAmount);
            }

            // 4. บันทึก usage log
            $this->trackUsage($featureKey, $tenantId, [
                'user_id' => $userId,
                'action' => $action,
                'usage_amount' => $usageAmount,
                'metadata' => $metadata,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'ใช้งาน feature สำเร็จ',
                'data' => [
                    'quota_remaining' => $access->quota_limit - $access->quota_used,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AI Core: ใช้งาน feature ล้มเหลว', [
                'feature_key' => $featureKey,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * บันทึก usage log
     *
     * @param string $featureKey รหัส feature
     * @param int $tenantId Tenant ID
     * @param array $data ข้อมูล log
     * @return void
     */
    public function trackUsage(string $featureKey, int $tenantId, array $data = []): void
    {
        try {
            $feature = AICoreFeature::where('feature_key', $featureKey)->first();

            if (!$feature) {
                return;
            }

            AICoreUsageLog::create([
                'tenant_id' => $tenantId,
                'feature_id' => $feature->id,
                'user_id' => $data['user_id'] ?? null,
                'action' => $data['action'] ?? 'use',
                'status' => $data['status'] ?? 'success',
                'usage_amount' => $data['usage_amount'] ?? 1,
                'usage_unit' => $data['usage_unit'] ?? $feature->quota_type,
                'response_time' => $data['response_time'] ?? null,
                'tokens_used' => $data['tokens_used'] ?? null,
                'cost' => $data['cost'] ?? null,
                'request_data' => $data['request_data'] ?? null,
                'response_data' => $data['response_data'] ?? null,
                'error_message' => $data['error_message'] ?? null,
                'ip_address' => $data['ip_address'] ?? request()->ip(),
                'user_agent' => $data['user_agent'] ?? request()->userAgent(),
                'metadata' => $data['metadata'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Core: บันทึก usage log ล้มเหลว', [
                'feature_key' => $featureKey,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * เปิดใช้งาน feature สำหรับ tenant
     *
     * @param string $featureKey รหัส feature
     * @param int $tenantId Tenant ID
     * @param array $config การตั้งค่า
     * @return array
     */
    public function enableFeatureForTenant(string $featureKey, int $tenantId, array $config = []): array
    {
        try {
            $feature = AICoreFeature::where('feature_key', $featureKey)->first();

            if (!$feature) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบ feature ที่ระบุ',
                ];
            }

            $tenant = AICoreTenant::find($tenantId);

            if (!$tenant) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบ tenant ที่ระบุ',
                ];
            }

            // สร้างหรืออัปเดต feature access
            $access = AICoreFeatureAccess::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'feature_id' => $feature->id,
                ],
                [
                    'is_enabled' => true,
                    'access_level' => $config['access_level'] ?? 'full',
                    'quota_limit' => $config['quota_limit'] ?? $feature->default_quota_limit,
                    'quota_reset_period' => $config['quota_reset_period'] ?? $feature->quota_reset_period,
                    'quota_used' => 0,
                    'is_trial' => $config['is_trial'] ?? false,
                    'trial_ends_at' => $config['trial_ends_at'] ?? null,
                    'access_starts_at' => now(),
                    'access_ends_at' => $config['access_ends_at'] ?? null,
                    'status' => $feature->requires_approval ? 'pending' : 'approved',
                    'custom_config' => $config['custom_config'] ?? [],
                ]
            );

            return [
                'success' => true,
                'message' => 'เปิดใช้งาน feature สำเร็จ',
                'data' => $access,
            ];
        } catch (\Exception $e) {
            Log::error('AI Core: เปิดใช้งาน feature ล้มเหลว', [
                'feature_key' => $featureKey,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ปิดใช้งาน feature สำหรับ tenant
     *
     * @param string $featureKey รหัส feature
     * @param int $tenantId Tenant ID
     * @return array
     */
    public function disableFeatureForTenant(string $featureKey, int $tenantId): array
    {
        try {
            $feature = AICoreFeature::where('feature_key', $featureKey)->first();

            if (!$feature) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบ feature ที่ระบุ',
                ];
            }

            $access = AICoreFeatureAccess::where('tenant_id', $tenantId)
                ->where('feature_id', $feature->id)
                ->first();

            if (!$access) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบการเข้าถึง feature นี้',
                ];
            }

            $access->update(['is_enabled' => false]);

            return [
                'success' => true,
                'message' => 'ปิดใช้งาน feature สำเร็จ',
            ];
        } catch (\Exception $e) {
            Log::error('AI Core: ปิดใช้งาน feature ล้มเหลว', [
                'feature_key' => $featureKey,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ดึงรายการ features ที่ tenant มีสิทธิ์ใช้
     *
     * @param int $tenantId Tenant ID
     * @param bool $onlyEnabled เฉพาะที่เปิดใช้งาน
     * @return \Illuminate\Support\Collection
     */
    public function getTenantFeatures(int $tenantId, bool $onlyEnabled = true)
    {
        $query = AICoreFeatureAccess::where('tenant_id', $tenantId)
            ->with('feature');

        if ($onlyEnabled) {
            $query->where('is_enabled', true)
                ->where('status', 'approved');
        }

        return $query->get()->map(function ($access) {
            return [
                'feature' => $access->feature,
                'access' => $access,
                'quota_remaining' => $access->quota_limit - $access->quota_used,
                'quota_percentage' => $access->quota_limit > 0
                    ? round(($access->quota_used / $access->quota_limit) * 100, 2)
                    : 0,
                'is_active' => $access->isActive(),
            ];
        });
    }

    /**
     * สร้าง tenant ใหม่
     *
     * @param int|null $userId User ID
     * @param array $data ข้อมูล tenant
     * @return array
     */
    public function createTenant(?int $userId = null, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $tenant = AICoreTenant::create([
                'user_id' => $userId,
                'tenant_key' => $data['tenant_key'] ?? 'tenant_' . uniqid(),
                'tenant_name' => $data['tenant_name'] ?? 'Tenant',
                'tenant_type' => $data['tenant_type'] ?? 'user',
                'status' => $data['status'] ?? 'active',
                'is_enabled' => $data['is_enabled'] ?? true,
                'subscription_tier' => $data['subscription_tier'] ?? 'free',
                'subscription_starts_at' => now(),
                'subscription_ends_at' => $data['subscription_ends_at'] ?? null,
                'auto_renew' => $data['auto_renew'] ?? true,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'สร้าง tenant สำเร็จ',
                'data' => $tenant,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AI Core: สร้าง tenant ล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }
}
