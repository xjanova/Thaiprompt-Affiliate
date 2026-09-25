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
        if (! $user) {
            return redirect()->route('login');
        }

        // Super Admin และ Admin ข้ามการตรวจสอบ
        if ($user->is_super_admin || in_array($user->role, ['admin', 'super_admin'])) {
            return $next($request);
        }

        // ตรวจสอบว่ามี store หรือยัง
        $store = VendorStore::where('user_id', $user->id)->first();

        if (! $store) {
            return redirect()->route('seller.onboarding.index')
                ->with('info', 'กรุณาตั้งค่าร้านค้าของคุณก่อน');
        }

        // 🔒 (2026-09-25) SELLER-20: ร้านที่ถูกระงับ/ปิด เข้าแผงผู้ขายไม่ได้ (เดิมเช็คแค่ว่ามีร้าน)
        if (! $store->is_active || in_array($store->status, ['suspended', 'closed'], true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'STORE_SUSPENDED',
                    'message' => 'ร้านค้าของคุณถูกระงับการใช้งาน',
                    'data' => ['reason' => $store->suspension_reason],
                ], 403);
            }

            // หน้า 403 มาตรฐานแสดงข้อความนี้ให้ผู้ขาย
            abort(403, trim('ร้าน '.$store->store_name.' ถูกระงับการใช้งานชั่วคราว'
                .($store->suspension_reason ? ' — '.$store->suspension_reason : '')
                .' · กรุณาติดต่อเจ้าหน้าที่'));
        }

        // 💳 (2026-09-25) audit SELLER-08: ร้านที่ยังไม่มีแพ็กเกจใช้งานต้องเข้าหน้าเลือก/ชำระค่าแพ็กเกจได้
        //    (เดิมหน้าชำระเงินอยู่หลัง middleware นี้ → เด้งกลับ onboarding วนไม่จบ)
        if ($request->routeIs('seller.packages', 'seller.packages.subscribe', 'seller.packages.payment', 'seller.packages.process-payment')) {
            return $next($request);
        }

        // ตรวจสอบว่ามี active subscription หรือ trial หรือยัง
        if (! $this->hasActiveSubscription($store)) {
            return redirect()->route('seller.onboarding.index')
                ->with('warning', 'กรุณาเลือกแพ็คเกจร้านค้าของคุณ');
        }

        return $next($request);
    }

    /**
     * ตรวจสอบว่า store มี subscription ที่ active หรือยังอยู่ในช่วง trial
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
            if (! $store->subscription_expires_at || $store->subscription_expires_at > now()) {
                return true;
            }
        }

        // ตรวจสอบว่ามี package ที่เป็น Free จริง — แพ็กเกจราคาพิเศษ (Enterprise ราคา 0) ไม่ใช่ฟรี (audit SELLER-06)
        if ($store->package && app(\App\Services\VendorSubscriptionService::class)->isFree($store->package)) {
            return true;
        }

        return false;
    }
}
