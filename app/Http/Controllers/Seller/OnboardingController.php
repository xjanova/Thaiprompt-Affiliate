<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Models\VendorSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seller Onboarding Controller
 *
 * จัดการขั้นตอนการเริ่มต้นใช้งานสำหรับ Seller ใหม่
 * รวมถึงการยืนยันตัวตน (KYC), เลือกแพ็คเกจ, และตั้งค่าร้านค้าเบื้องต้น
 */
class OnboardingController extends Controller
{
    /**
     * แสดงหน้า Onboarding หลัก
     *
     * ตรวจสอบขั้นตอนปัจจุบันและแสดง UI ที่เหมาะสม
     */
    public function index()
    {
        $user = Auth::user();
        $currentStep = $this->getCurrentStep($user);

        // ดึงข้อมูลที่ต้องใช้
        $kycStatus = $this->getKycStatus($user);
        $store = VendorStore::where('user_id', $user->id)->first();
        $packages = VendorPackage::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('seller.onboarding.index', compact(
            'user',
            'currentStep',
            'kycStatus',
            'store',
            'packages'
        ));
    }

    /**
     * หาขั้นตอนปัจจุบันของผู้ใช้
     *
     * @param  \App\Models\User  $user
     * @return int ขั้นตอน 1-3
     *             1 = ยังไม่ได้ KYC
     *             2 = KYC แล้ว แต่ยังไม่ได้ตั้งค่าร้านและเลือก package
     *             3 = เสร็จสิ้น พร้อมใช้งาน
     */
    private function getCurrentStep($user): int
    {
        // ขั้นตอน 1: ตรวจสอบ KYC
        if (! $this->isKycApproved($user)) {
            return 1;
        }

        // ขั้นตอน 2: ตรวจสอบว่ามี store และ subscription หรือยัง
        $store = VendorStore::where('user_id', $user->id)->first();
        if (! $store || ! $this->hasActiveSubscription($store)) {
            return 2;
        }

        // ขั้นตอน 3: เสร็จสิ้น
        return 3;
    }

    /**
     * ตรวจสอบสถานะ KYC ของผู้ใช้
     *
     * @param  \App\Models\User  $user
     */
    private function getKycStatus($user): array
    {
        $latestKyc = null;
        if (method_exists($user, 'kycVerifications')) {
            $latestKyc = $user->kycVerifications()->latest()->first();
        }

        return [
            'status' => $user->kyc_status ?? 'not_submitted',
            'latest_submission' => $latestKyc,
            'is_approved' => $user->kyc_status === 'approved',
            'is_pending' => $user->kyc_status === 'pending',
            'is_rejected' => $user->kyc_status === 'rejected',
        ];
    }

    /**
     * ตรวจสอบว่า KYC ได้รับการอนุมัติแล้วหรือไม่
     */
    private function isKycApproved($user): bool
    {
        return $user->kyc_status === 'approved';
    }

    /**
     * ตรวจสอบว่า store มี subscription ที่ active หรือยังอยู่ในช่วง trial
     */
    private function hasActiveSubscription(?VendorStore $store): bool
    {
        if (! $store) {
            return false;
        }

        // ตรวจสอบว่ายังอยู่ในช่วง trial
        if ($store->subscription_status === 'trial' && $store->trial_ends_at && $store->trial_ends_at > now()) {
            return true;
        }

        // ตรวจสอบว่ามี subscription ที่ active
        if ($store->subscription_status === 'active') {
            if (! $store->subscription_expires_at || $store->subscription_expires_at > now()) {
                return true;
            }
        }

        // ตรวจสอบว่ามี package ที่เป็น Free
        if ($store->package && $store->package->price == 0) {
            return true;
        }

        return false;
    }

    /**
     * สร้างร้านค้าและเลือกแพ็คเกจ
     *
     * เมื่อ user ผ่าน KYC แล้ว สามารถสร้างร้านและเลือก package ได้
     */
    public function createStore(Request $request)
    {
        $user = Auth::user();

        // ตรวจสอบ KYC ก่อน
        if (! $this->isKycApproved($user)) {
            return redirect()->route('seller.onboarding.index')
                ->with('error', 'กรุณายืนยันตัวตน (KYC) ก่อนสร้างร้านค้า');
        }

        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'package_id' => 'required|exists:vendor_packages,id',
            'subscription_type' => 'nullable|in:monthly,yearly',
        ], [
            'store_name.required' => 'กรุณากรอกชื่อร้านค้า',
            'store_name.max' => 'ชื่อร้านค้าต้องไม่เกิน 255 ตัวอักษร',
            'package_id.required' => 'กรุณาเลือกแพ็คเกจ',
            'package_id.exists' => 'แพ็คเกจที่เลือกไม่ถูกต้อง',
        ]);

        $package = VendorPackage::findOrFail($validated['package_id']);

        DB::beginTransaction();
        try {
            // สร้าง store
            $store = VendorStore::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'store_name' => $validated['store_name'],
                'store_slug' => Str::slug($validated['store_name'].'-'.$user->id),
                'is_active' => true,
                'status' => 'active',
                'store_email' => $user->email,
                'store_phone' => $user->phone,
                'commission_rate' => $package->commission_rate,
            ]);

            // ถ้าเป็น Free package หรือมี trial
            if ($package->price == 0) {
                // Free package - ไม่ต้อง subscription
                $store->update([
                    'subscription_status' => 'active',
                    'subscription_started_at' => now(),
                ]);
            } elseif ($package->trial_days > 0) {
                // มี trial period
                $store->update([
                    'subscription_status' => 'trial',
                    'trial_ends_at' => now()->addDays($package->trial_days),
                    'subscription_started_at' => now(),
                ]);

                // สร้าง trial subscription record
                VendorSubscription::create([
                    'store_id' => $store->id,
                    'package_id' => $package->id,
                    'subscription_type' => 'trial',
                    'amount' => 0,
                    'currency' => 'THB',
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => now()->addDays($package->trial_days),
                    'auto_renew' => false,
                ]);
            } else {
                // Paid package - redirect ไปหน้าชำระเงิน
                $subscriptionType = $validated['subscription_type'] ?? 'monthly';
                $amount = $subscriptionType === 'yearly' ? $package->yearly_price : $package->price;

                // สร้าง pending subscription
                $subscription = VendorSubscription::create([
                    'store_id' => $store->id,
                    'package_id' => $package->id,
                    'subscription_type' => $subscriptionType,
                    'amount' => $amount,
                    'currency' => 'THB',
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'started_at' => now(),
                    'auto_renew' => true,
                ]);

                DB::commit();

                // Redirect ไปหน้าชำระเงิน
                return redirect()->route('seller.packages.payment', $subscription->id)
                    ->with('success', 'สร้างร้านค้าสำเร็จ! กรุณาชำระเงินเพื่อเปิดใช้งานแพ็คเกจ');
            }

            DB::commit();

            return redirect()->route('seller.dashboard')
                ->with('success', 'ยินดีต้อนรับสู่ระบบร้านค้า! ร้านของคุณพร้อมใช้งานแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * เปลี่ยนแพ็คเกจ (สำหรับผู้ที่มี store แล้ว)
     */
    public function changePackage(Request $request)
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'package_id' => 'required|exists:vendor_packages,id',
            'subscription_type' => 'nullable|in:monthly,yearly',
        ]);

        $package = VendorPackage::findOrFail($validated['package_id']);
        $subscriptionType = $validated['subscription_type'] ?? 'monthly';
        $amount = $subscriptionType === 'yearly' ? $package->yearly_price : $package->price;

        // ถ้าเป็น Free package
        if ($package->price == 0) {
            $store->update([
                'package_id' => $package->id,
                'subscription_status' => 'active',
                'subscription_started_at' => now(),
                'commission_rate' => $package->commission_rate,
            ]);

            return redirect()->route('seller.dashboard')
                ->with('success', 'เปลี่ยนเป็นแพ็คเกจฟรีสำเร็จ!');
        }

        // สร้าง subscription ใหม่
        $subscription = VendorSubscription::create([
            'store_id' => $store->id,
            'package_id' => $package->id,
            'subscription_type' => $subscriptionType,
            'amount' => $amount,
            'currency' => 'THB',
            'status' => 'pending',
            'payment_status' => 'pending',
            'started_at' => now(),
            'auto_renew' => true,
        ]);

        return redirect()->route('seller.packages.payment', $subscription->id)
            ->with('info', 'กรุณาชำระเงินเพื่อเปลี่ยนแพ็คเกจ');
    }

    /**
     * ข้ามการเลือกแพ็คเกจ (ใช้ Free package)
     */
    public function skipPackage()
    {
        $user = Auth::user();

        // ตรวจสอบ KYC ก่อน
        if (! $this->isKycApproved($user)) {
            return redirect()->route('seller.onboarding.index')
                ->with('error', 'กรุณายืนยันตัวตน (KYC) ก่อน');
        }

        // หา Free package
        $freePackage = VendorPackage::where('package_slug', 'free')
            ->where('is_active', true)
            ->first();

        if (! $freePackage) {
            return redirect()->route('seller.onboarding.index')
                ->with('error', 'ไม่พบแพ็คเกจฟรี');
        }

        // ตรวจสอบว่ามี store อยู่แล้วหรือไม่
        $store = VendorStore::where('user_id', $user->id)->first();

        DB::beginTransaction();
        try {
            if (! $store) {
                // สร้าง store ใหม่
                $store = VendorStore::create([
                    'user_id' => $user->id,
                    'package_id' => $freePackage->id,
                    'store_name' => $user->name.' Store',
                    'store_slug' => Str::slug($user->name.'-store-'.$user->id),
                    'is_active' => true,
                    'status' => 'active',
                    'subscription_status' => 'active',
                    'subscription_started_at' => now(),
                    'store_email' => $user->email,
                    'commission_rate' => $freePackage->commission_rate,
                ]);
            } else {
                // อัปเดต store ที่มีอยู่
                $store->update([
                    'package_id' => $freePackage->id,
                    'subscription_status' => 'active',
                    'subscription_started_at' => now(),
                    'commission_rate' => $freePackage->commission_rate,
                ]);
            }

            DB::commit();

            return redirect()->route('seller.dashboard')
                ->with('success', 'ยินดีต้อนรับสู่ระบบร้านค้า! คุณใช้แพ็คเกจฟรี');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }
}
