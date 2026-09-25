<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Models\VendorSubscription;
use App\Services\VendorSubscriptionService;
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

        // ตรวจสอบว่ามี package ที่เป็น Free จริง (Enterprise ราคา 0 = ราคาพิเศษ ไม่ใช่ฟรี)
        if ($store->package && app(VendorSubscriptionService::class)->isFree($store->package)) {
            return true;
        }

        return false;
    }

    /**
     * สร้างร้านค้าและเลือกแพ็คเกจ
     *
     * เมื่อ user ผ่าน KYC แล้ว สามารถสร้างร้านและเลือก package ได้
     *
     * 🔒 (2026-09-25) audit SELLER-06/08:
     *  - แพ็กเกจราคาพิเศษ (Enterprise) สมัครเองไม่ได้
     *  - แพ็กเกจเสียเงินที่ไม่มีทดลองใช้: ร้านเปิดด้วยแพ็กเกจฟรีก่อน + subscription รอชำระ
     *    (เดิมผูกแพ็กเกจเสียเงินให้ร้านทันที = ได้อัตรา GP ต่ำโดยไม่จ่าย และติดหน้าชำระเงินที่เข้าไม่ได้)
     *  - กดซ้ำไม่สร้างร้านซ้ำ (lock แถวผู้ใช้ + ใช้ร้านเดิม)
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

        $package = VendorPackage::where('is_active', true)->find($validated['package_id']);
        if (! $package) {
            return redirect()->back()->with('error', 'แพ็คเกจที่เลือกไม่เปิดให้สมัครแล้ว')->withInput();
        }

        $subscriptions = app(VendorSubscriptionService::class);
        if ($subscriptions->isCustomPricing($package)) {
            return redirect()->back()
                ->with('error', 'แพ็คเกจนี้เป็นแพ็คเกจราคาพิเศษ กรุณาติดต่อทีมงานเพื่อสมัคร หรือเลือกแพ็คเกจอื่นก่อน')
                ->withInput();
        }

        $freePackage = VendorPackage::where('is_active', true)
            ->where('price', '<=', 0)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->get()
            ->first(fn (VendorPackage $p) => $subscriptions->isFree($p));

        try {
            $outcome = DB::transaction(function () use ($user, $validated, $package, $freePackage, $subscriptions) {
                // lock แถวผู้ใช้ → กดสร้างร้านซ้ำ/พร้อมกันได้ร้านเดียว
                User::whereKey($user->id)->lockForUpdate()->first();

                $store = VendorStore::where('user_id', $user->id)->orderBy('id')->first();
                $isPaid = ! $subscriptions->isFree($package);
                $withTrial = $isPaid && (int) $package->trial_days > 0;

                // แพ็กเกจที่ร้านใช้ได้ทันที: ฟรี/ทดลองใช้ = แพ็กเกจที่เลือก, เสียเงินไม่มีทดลอง = แพ็กเกจฟรี
                $activePackage = (! $isPaid || $withTrial) ? $package : $freePackage;

                $storeData = [
                    'package_id' => $activePackage?->id,
                    'commission_rate' => $activePackage ? $activePackage->commission_rate : $package->commission_rate,
                ];

                if (! $isPaid || ! $withTrial) {
                    $storeData += [
                        'subscription_status' => $activePackage ? 'active' : 'trial',
                        'subscription_started_at' => now(),
                        'subscription_expires_at' => null,
                        'trial_ends_at' => null,
                    ];
                } else {
                    $storeData += [
                        'subscription_status' => 'trial',
                        'trial_ends_at' => now()->addDays((int) $package->trial_days),
                        'subscription_started_at' => now(),
                    ];
                }

                if ($store) {
                    // มีร้านอยู่แล้ว (เช่น กดส่งฟอร์มซ้ำ) → อัปเดตร้านเดิม ไม่สร้างใหม่
                    $store->update($storeData);
                } else {
                    $store = VendorStore::create(array_merge([
                        'user_id' => $user->id,
                        'store_name' => $validated['store_name'],
                        'store_slug' => Str::slug($validated['store_name'].'-'.$user->id) ?: 'store-'.$user->id,
                        'is_active' => true,
                        'status' => 'active',
                        'store_email' => $user->email,
                        'store_phone' => $user->phone,
                    ], $storeData));
                }

                if ($withTrial) {
                    VendorSubscription::create([
                        'store_id' => $store->id,
                        'package_id' => $package->id,
                        'subscription_type' => 'trial',
                        'amount' => 0,
                        'currency' => $package->currency ?: 'THB',
                        'status' => 'active',
                        'payment_status' => 'pending',
                        'started_at' => now(),
                        'expires_at' => now()->addDays((int) $package->trial_days),
                        'auto_renew' => false,
                    ]);

                    return ['store' => $store, 'subscription' => null];
                }

                if ($isPaid) {
                    $subscription = $subscriptions->createPendingSubscription(
                        $store,
                        $package,
                        $validated['subscription_type'] ?? 'monthly'
                    );

                    return ['store' => $store, 'subscription' => $subscription];
                }

                return ['store' => $store, 'subscription' => null];
            });
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Seller onboarding create store failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'สร้างร้านค้าไม่สำเร็จ กรุณาลองใหม่อีกครั้ง')
                ->withInput();
        }

        if ($outcome['subscription']) {
            return redirect()->route('seller.packages.payment', $outcome['subscription']->id)
                ->with('success', 'สร้างร้านค้าสำเร็จ! กรุณาชำระเงินเพื่อเปิดใช้งานแพ็คเกจ '.$package->display_name);
        }

        return redirect()->route('seller.dashboard')
            ->with('success', 'ยินดีต้อนรับสู่ระบบร้านค้า! ร้านของคุณพร้อมใช้งานแล้ว');
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

        $package = VendorPackage::where('is_active', true)->find($validated['package_id']);
        if (! $package) {
            return redirect()->back()->with('error', 'แพ็คเกจที่เลือกไม่เปิดให้สมัครแล้ว');
        }

        $subscriptions = app(VendorSubscriptionService::class);

        // แพ็กเกจราคาพิเศษ (Enterprise) → ให้ทีมงานกำหนด ไม่ใช่ "ฟรี"
        if ($subscriptions->isCustomPricing($package)) {
            return redirect()->back()
                ->with('error', 'แพ็คเกจนี้เป็นแพ็คเกจราคาพิเศษ กรุณาติดต่อทีมงานเพื่อสมัคร');
        }

        try {
            if ($subscriptions->isFree($package)) {
                $subscriptions->activateFree($store, $package);

                return redirect()->route('seller.dashboard')
                    ->with('success', 'เปลี่ยนเป็นแพ็คเกจฟรีสำเร็จ!');
            }

            // แพ็กเกจเสียเงิน → ร้านยังใช้แพ็กเกจเดิมจนกว่าจะชำระเงินจริง
            $subscription = $subscriptions->createPendingSubscription(
                $store,
                $package,
                $validated['subscription_type'] ?? 'monthly'
            );
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Seller change package failed', [
                'store_id' => $store->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'เปลี่ยนแพ็คเกจไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

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

        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Seller onboarding skip package failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'เปิดร้านด้วยแพ็คเกจฟรีไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }
    }
}
