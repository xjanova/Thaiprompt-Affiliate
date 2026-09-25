<?php

namespace App\Http\Controllers\Seller\Concerns;

use App\Models\VendorStore;
use Illuminate\Http\Request;

/**
 * หาร้านค้าของผู้ใช้ที่ล็อกอินอยู่ (ใช้ร่วมใน controller ฝั่งผู้ขาย)
 *
 * (2026-09-25) เดิม Marketing/Coupon/StoreRating/Achievement controller ใช้ $request->user()->vendorStore
 * แต่โมเดล User ไม่มี relation ชื่อ vendorStore → ได้ null เสมอ
 * (หน้าการตลาดเด้งไป onboarding · คูปอง/คะแนนร้าน/รางวัล 500 "store_slug on null")
 * จึงค้นจาก vendor_stores.user_id ตรง ๆ แบบเดียวกับ EnsureHasVendorStore middleware
 */
trait ResolvesSellerStore
{
    /**
     * ร้านของผู้ใช้ปัจจุบัน (cache ไว้ตลอด request)
     */
    protected function currentStore(Request $request): ?VendorStore
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return $request->attributes->get('_seller_store')
            ?? tap(VendorStore::where('user_id', $user->id)->first(), function ($store) use ($request) {
                if ($store) {
                    $request->attributes->set('_seller_store', $store);
                }
            });
    }

    /**
     * ร้านของผู้ใช้ปัจจุบัน — ไม่มีร้าน = 403 (ปกติ middleware has.vendor.store กันไว้ก่อนแล้ว)
     */
    protected function requireStore(Request $request): VendorStore
    {
        $store = $this->currentStore($request);
        abort_if(! $store, 403, 'กรุณาสร้างร้านค้าก่อนใช้งานฟีเจอร์นี้');

        return $store;
    }
}
