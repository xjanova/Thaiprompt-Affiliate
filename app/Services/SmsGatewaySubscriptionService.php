<?php

namespace App\Services;

use App\Models\PremiumStore;
use App\Models\SmsCheckerDevice;
use App\Models\SmsGatewayPricing;
use App\Models\SmsGatewaySubscription;
use App\Models\User;
use App\Models\VendorStore;

/**
 * SMS Gateway Subscription Service
 *
 * จัดการการสมัครสมาชิก SMS Payment Gateway สำหรับร้านค้า
 * - สมัคร/ยกเลิก/ต่ออายุ subscription
 * - เช็คสิทธิ์ (Premium/Official ใช้ฟรี)
 * - เช็คจำนวนอุปกรณ์ที่เพิ่มได้
 */
class SmsGatewaySubscriptionService
{
    /**
     * สมัครสมาชิก SMS Payment Gateway
     */
    public function subscribe(int $storeId, int $userId, int $pricingId): SmsGatewaySubscription
    {
        $pricing = SmsGatewayPricing::findOrFail($pricingId);

        // ยกเลิก subscription เก่าที่ยัง active อยู่
        SmsGatewaySubscription::forStore($storeId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $expiresAt = $pricing->plan_type === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        return SmsGatewaySubscription::create([
            'store_id' => $storeId,
            'user_id' => $userId,
            'pricing_id' => $pricingId,
            'status' => 'active',
            'plan_type' => $pricing->plan_type,
            'price' => $pricing->price,
            'currency' => $pricing->currency ?? 'THB',
            'started_at' => now(),
            'expires_at' => $expiresAt,
            'auto_renew' => true,
            'payment_status' => 'paid',
            'next_billing_date' => $expiresAt->toDateString(),
        ]);
    }

    /**
     * เริ่มทดลองใช้ฟรี (Trial)
     */
    public function startTrial(int $storeId, int $userId, int $pricingId): SmsGatewaySubscription
    {
        $pricing = SmsGatewayPricing::findOrFail($pricingId);

        // เช็คว่าเคยทดลองใช้แล้วหรือยัง
        $hadTrial = SmsGatewaySubscription::forStore($storeId)
            ->where('status', 'trial')
            ->exists();

        if ($hadTrial) {
            throw new \Exception('ร้านค้านี้เคยทดลองใช้ฟรีแล้ว');
        }

        $trialDays = $pricing->trial_days ?: 7;

        return SmsGatewaySubscription::create([
            'store_id' => $storeId,
            'user_id' => $userId,
            'pricing_id' => $pricingId,
            'status' => 'trial',
            'plan_type' => $pricing->plan_type,
            'price' => 0,
            'currency' => 'THB',
            'started_at' => now(),
            'expires_at' => now()->addDays($trialDays),
            'auto_renew' => false,
            'payment_status' => 'free_trial',
            'notes' => "ทดลองใช้ฟรี {$trialDays} วัน",
        ]);
    }

    /**
     * ยกเลิก subscription
     */
    public function cancel(int $subscriptionId, ?string $reason = null): bool
    {
        $subscription = SmsGatewaySubscription::findOrFail($subscriptionId);

        return $subscription->cancel($reason);
    }

    /**
     * เช็คว่าร้านค้าได้รับยกเว้นไม่ต้องจ่ายค่าเช่า
     * (Premium Store หรือ Official Shop)
     */
    public function isExemptFromPayment(int $storeId): bool
    {
        $store = VendorStore::find($storeId);
        if (! $store) {
            return false;
        }

        // เช็ค Official Shop
        $officialEmail = config('shop.official_shop.seller_email', 'official-shop@thaiprompt.com');
        $officialUser = User::where('email', $officialEmail)->first();
        if ($officialUser && $store->user_id === $officialUser->id) {
            return true;
        }

        // เช็ค Premium Store
        return PremiumStore::where('store_id', $storeId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * เช็คว่าร้านค้ามีสิทธิ์ใช้ SMS Gateway หรือไม่
     */
    public function hasAccess(?int $storeId): bool
    {
        return SmsGatewaySubscription::storeHasAccess($storeId);
    }

    /**
     * เช็คว่าร้านค้าเพิ่มอุปกรณ์ได้อีกหรือไม่ (ตามจำนวนใน plan)
     */
    public function canAddDevice(int $storeId): bool
    {
        $maxDevices = $this->getMaxDevices($storeId);
        if ($maxDevices === null) {
            return false; // ไม่มี subscription
        }

        $currentDevices = SmsCheckerDevice::where('store_id', $storeId)
            ->where('status', 'active')
            ->count();

        return $currentDevices < $maxDevices;
    }

    /**
     * ดึงจำนวนอุปกรณ์สูงสุดที่ร้านค้าเพิ่มได้
     * null = ไม่มี subscription, -1 = unlimited (admin/exempt)
     */
    public function getMaxDevices(int $storeId): ?int
    {
        // ร้านที่ได้รับยกเว้น → ไม่จำกัดอุปกรณ์
        if ($this->isExemptFromPayment($storeId)) {
            return -1; // unlimited
        }

        // หา active subscription
        $subscription = SmsGatewaySubscription::forStore($storeId)
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 'trial');
            })
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $subscription || ! $subscription->pricing) {
            return null;
        }

        return $subscription->pricing->max_devices;
    }

    /**
     * ดึงจำนวนอุปกรณ์ที่ใช้อยู่
     */
    public function getDeviceCount(int $storeId): int
    {
        return SmsCheckerDevice::where('store_id', $storeId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * ดึง active subscription ของร้านค้า
     */
    public function getActiveSubscription(int $storeId): ?SmsGatewaySubscription
    {
        return SmsGatewaySubscription::forStore($storeId)
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 'trial');
            })
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * ดึงแพ็กเกจราคาทั้งหมดที่ active
     */
    public function getAvailablePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return SmsGatewayPricing::active()->ordered()->get();
    }

    /**
     * ดึงสถานะรวมของร้านค้าสำหรับหน้า dashboard
     */
    public function getStoreDashboardStatus(int $storeId): array
    {
        $isExempt = $this->isExemptFromPayment($storeId);
        $subscription = $this->getActiveSubscription($storeId);
        $hasAccess = $isExempt || ($subscription !== null);
        $maxDevices = $this->getMaxDevices($storeId);
        $currentDevices = $this->getDeviceCount($storeId);

        return [
            'has_access' => $hasAccess,
            'is_exempt' => $isExempt,
            'subscription' => $subscription,
            'max_devices' => $maxDevices === -1 ? 'ไม่จำกัด' : $maxDevices,
            'current_devices' => $currentDevices,
            'can_add_device' => $maxDevices === -1 || ($maxDevices !== null && $currentDevices < $maxDevices),
            'days_until_expiry' => $subscription?->days_until_expiry ?? 0,
            'is_expiring_soon' => $subscription?->isExpiringSoon() ?? false,
        ];
    }
}
