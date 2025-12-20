<?php

namespace App\Http\Middleware;

use App\Models\VendorStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware ตรวจสอบว่าผู้ใช้มี Vendor Store แล้วหรือยัง
 *
 * ใช้สำหรับ routes ที่ต้องการให้ผู้ใช้มีร้านค้าและเลือก package แล้ว
 * ถ้ายังไม่มี จะ redirect ไปหน้า onboarding
 */
class EnsureHasVendorStore
{
    /**
     * Handle an incoming request.
     *
     * ตรวจสอบว่าผู้ใช้มีร้านค้าและ subscription ที่ active แล้วหรือยัง
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ถ้าไม่มี user ให้ไปหน้า login
        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin และ Admin ข้ามการตรวจสอบ
        if ($user->is_super_admin || in_array($user->role, ['admin', 'super_admin'])) {
            return $next($request);
        }

        // ตรวจสอบว่ามี store หรือยัง
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return redirect()->route('seller.onboarding.index')
                ->with('info', 'กรุณาตั้งค่าร้านค้าของคุณก่อน');
        }

        // ตรวจสอบว่ามี active subscription หรือ trial หรือยัง
        if (!$this->hasActiveSubscription($store)) {
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาเลือกแพ็คเกจร้านค้าของคุณ');
        }

        return $next($request);
    }

    /**
     * ตรวจสอบว่า store มี subscription ที่ active หรือยังอยู่ในช่วง trial
     *
     * @param VendorStore $store
     * @return bool
     */
    private function hasActiveSubscription(VendorStore $store): bool
    {
        // ตรวจสอบว่ายังอยู่ในช่วง trial
        if ($store->subscription_status === 'trial' && $store->trial_ends_at && $store->trial_ends_at > now()) {
            return true;
        }

        // ตรวจสอบว่ามี subscription ที่ active
        if ($store->subscription_status === 'active') {
            // ตรวจสอบว่ายังไม่หมดอายุ
            if (!$store->subscription_expires_at || $store->subscription_expires_at > now()) {
                return true;
            }
        }

        // ตรวจสอบว่ามี package ที่เป็น Free
        if ($store->package && $store->package->price == 0) {
            return true;
        }

        return false;
    }
}
