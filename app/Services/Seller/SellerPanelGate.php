<?php

namespace App\Services\Seller;

use App\Models\User;
use App\Models\VendorStore;
use App\Services\VendorSubscriptionService;

/**
 * ด่านเข้า "หลังร้าน" ของแอป — ต้องตรงกับด่านของเว็บ /seller/* ทุกข้อ
 *
 * เว็บ: middleware role:seller,super_admin → kyc.verified (EnsureKycVerified) → has.vendor.store (EnsureHasVendorStore)
 * แอป: ใช้คลาสนี้ในทุก endpoint จัดการสินค้า/ตั้งค่าร้าน (ถ้าแก้กติกาฝั่งเว็บ ต้องแก้ที่นี่ด้วย)
 *
 * ลำดับการตรวจ (คืน error แรกที่เจอ):
 *   1. ไม่ใช่ผู้ขาย → STORE_PENDING (คำขอรออนุมัติ) / NOT_A_SELLER
 *   2. ยังไม่ผ่าน KYC → SELLER_KYC_REQUIRED
 *   3. ยังไม่มีร้าน → NOT_A_SELLER (ตั้งร้านที่ /seller/onboarding)
 *   4. ร้านถูกระงับ/ปิด → STORE_SUSPENDED
 *   5. ไม่มีแพ็กเกจใช้งาน → PACKAGE_REQUIRED
 *
 * แอดมินผ่านข้อ 2–5 เหมือนเว็บ แต่ทุก endpoint ยังจำกัดข้อมูลเฉพาะของตัวเอง (seller_id / user_id = ผู้เรียก)
 */
class SellerPanelGate
{
    public function __construct(private readonly SellerApplicationService $applications) {}

    /**
     * ตรวจสิทธิ์เข้าแผงผู้ขาย
     *
     * @param  bool  $requireStore  true = ต้องมีร้านเสมอ (หน้าตั้งค่าร้าน — แอดมินที่ไม่มีร้านก็ใช้ไม่ได้)
     * @return array{ok: true, store: ?VendorStore}|array{ok: false, status: int, code: string, message: string, data: array<string, mixed>}
     */
    public function check(?User $user, bool $requireStore = false): array
    {
        if (! $user) {
            return $this->deny(401, 'UNAUTHENTICATED', 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง');
        }

        $isAdmin = (bool) $user->is_super_admin || in_array($user->role, ['admin', 'super_admin'], true);
        $store = VendorStore::where('user_id', $user->id)->orderBy('id')->first();

        // 1) ต้องเป็นผู้ขาย (เว็บ: role:seller,super_admin — แอดมินผ่านเสมอ)
        if (! $isAdmin && $user->role !== 'seller') {
            $latest = $this->applications->latestStore($user);
            $state = $this->applications->stateFor($user, $latest);

            if ($state === 'pending') {
                return $this->deny(403, 'STORE_PENDING', 'คำขอเปิดร้านของคุณกำลังรอทีมงานตรวจสอบ', ['application_state' => $state]);
            }

            return $this->deny(403, 'NOT_A_SELLER', 'บัญชีนี้ยังไม่ได้เปิดร้านค้า', ['application_state' => $state]);
        }

        if ($isAdmin) {
            if ($requireStore && ! $store) {
                return $this->deny(403, 'NOT_A_SELLER', 'บัญชีนี้ยังไม่มีร้านค้า', ['web_path' => '/seller/onboarding']);
            }

            return ['ok' => true, 'store' => $store];
        }

        // 2) KYC (เว็บ: EnsureKycVerified)
        if (! $this->kycApproved($user)) {
            return $this->deny(403, 'SELLER_KYC_REQUIRED', 'กรุณายืนยันตัวตน (KYC) ก่อนจัดการร้านค้า', ['web_path' => '/user/kyc']);
        }

        // 3) ต้องมีร้าน (เว็บ: EnsureHasVendorStore → onboarding)
        if (! $store) {
            return $this->deny(403, 'NOT_A_SELLER', 'ยังไม่ได้ตั้งค่าร้านค้า กรุณาตั้งค่าร้านบนเว็บไซต์ก่อน', ['web_path' => '/seller/onboarding']);
        }

        // 4) ร้านถูกระงับ/ปิด (SELLER-20)
        if (! $store->is_active || in_array($store->status, ['suspended', 'closed'], true)) {
            return $this->deny(403, 'STORE_SUSPENDED', 'ร้านค้าของคุณถูกระงับการใช้งาน กรุณาติดต่อเจ้าหน้าที่', [
                'reason' => $store->suspension_reason,
            ]);
        }

        // 5) แพ็กเกจร้าน (เว็บ: EnsureHasVendorStore::hasActiveSubscription)
        if (! $this->hasActiveSubscription($store)) {
            return $this->deny(403, 'PACKAGE_REQUIRED', 'กรุณาเลือกแพ็กเกจร้านค้าบนเว็บไซต์ก่อน', ['web_path' => '/seller/onboarding']);
        }

        return ['ok' => true, 'store' => $store];
    }

    /**
     * KYC ผ่านแล้วหรือยัง — ตรงกับ EnsureKycVerified::isKycApproved
     */
    public function kycApproved(User $user): bool
    {
        if ($user->kyc_status === 'approved') {
            return true;
        }

        if (method_exists($user, 'kycVerifications')) {
            $latest = $user->kycVerifications()->latest()->first();
            if ($latest && $latest->status === 'approved') {
                return true;
            }
        }

        return false;
    }

    /**
     * ร้านมีแพ็กเกจใช้งานอยู่ — ตรงกับ EnsureHasVendorStore::hasActiveSubscription
     */
    private function hasActiveSubscription(VendorStore $store): bool
    {
        if ($store->subscription_status === 'trial' && $store->trial_ends_at && $store->trial_ends_at > now()) {
            return true;
        }

        if ($store->subscription_status === 'active'
            && (! $store->subscription_expires_at || $store->subscription_expires_at > now())) {
            return true;
        }

        // แพ็กเกจฟรีจริงเท่านั้น (Enterprise ราคา 0 ไม่ใช่ฟรี — audit SELLER-06)
        return $store->package && app(VendorSubscriptionService::class)->isFree($store->package);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: false, status: int, code: string, message: string, data: array<string, mixed>}
     */
    private function deny(int $status, string $code, string $message, array $data = []): array
    {
        return ['ok' => false, 'status' => $status, 'code' => $code, 'message' => $message, 'data' => $data];
    }
}
