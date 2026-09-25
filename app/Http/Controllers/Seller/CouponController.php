<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Seller CouponController
 *
 * จัดการคูปองของร้านค้า
 */
class CouponController extends Controller
{
    use \App\Http\Controllers\Seller\Concerns\ResolvesSellerStore;

    /**
     * แสดงรายการคูปองของร้าน
     */
    public function index(Request $request): View
    {
        $store = $this->requireStore($request);

        $coupons = Coupon::where('store_id', $store->id)
            ->latest()
            ->paginate(20);

        // สถิติ
        $stats = [
            'total' => Coupon::where('store_id', $store->id)->count(),
            'active' => Coupon::where('store_id', $store->id)->where('is_active', true)->count(),
            'used' => Coupon::where('store_id', $store->id)->sum('used_count'),
        ];

        return view('seller.coupons.index', [
            'coupons' => $coupons,
            'stats' => $stats,
            'pageTitle' => 'คูปองร้านค้า',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างคูปอง
     */
    public function create(): View
    {
        return view('seller.coupons.create', [
            'pageTitle' => 'สร้างคูปองใหม่',
        ]);
    }

    /**
     * บันทึกคูปองใหม่
     */
    public function store(Request $request): RedirectResponse
    {
        $store = $this->requireStore($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed,free_shipping',
            // ส่วนลดแบบเปอร์เซ็นต์ต้องไม่เกิน 100%
            'discount_value' => ['required', 'numeric', 'min:0', $request->input('discount_type') === 'percentage' ? 'max:100' : 'max:1000000'],
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_public' => 'boolean',
        ], [
            'discount_value.max' => 'ส่วนลดแบบเปอร์เซ็นต์ต้องไม่เกิน 100%',
            'expires_at.after' => 'วันหมดอายุต้องอยู่หลังวันเริ่มใช้',
        ]);

        // สร้างรหัสคูปองอัตโนมัติ — codes เป็น varchar(50) + ต้องไม่ซ้ำ
        // (เดิมใช้ slug ร้านเต็ม ๆ → ร้านที่ slug ยาวสร้างคูปองไม่ได้)
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $store->store_slug) ?: 'SHOP', 0, 16));
        do {
            $code = $prefix.'-'.Str::upper(Str::random(6));
        } while (Coupon::where('code', $code)->exists());

        Coupon::create([
            'code' => $code,
            'store_id' => $store->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_purchase' => $validated['min_purchase'] ?? 0,
            'max_discount' => $validated['max_discount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'is_active' => true,
            'badge_color' => '#10B981',
            'badge_icon' => 'fa-tag',
        ]);

        return redirect()
            ->route('seller.coupons.index')
            ->with('success', 'สร้างคูปองสำเร็จ! รหัส: '.$code);
    }

    /**
     * แสดงฟอร์มแก้ไขคูปอง
     */
    public function edit(Request $request, Coupon $coupon): View
    {
        $store = $this->requireStore($request);

        // ตรวจสอบสิทธิ์
        if ($coupon->store_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์แก้ไขคูปองนี้');
        }

        return view('seller.coupons.edit', [
            'coupon' => $coupon,
            'pageTitle' => 'แก้ไขคูปอง',
        ]);
    }

    /**
     * อัพเดทคูปอง
     */
    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $store = $this->requireStore($request);

        if ($coupon->store_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์แก้ไขคูปองนี้');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed,free_shipping',
            'discount_value' => ['required', 'numeric', 'min:0', $request->input('discount_type') === 'percentage' ? 'max:100' : 'max:1000000'],
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_public' => 'boolean',
        ], [
            'discount_value.max' => 'ส่วนลดแบบเปอร์เซ็นต์ต้องไม่เกิน 100%',
            'expires_at.after' => 'วันหมดอายุต้องอยู่หลังวันเริ่มใช้',
        ]);

        // min_purchase เป็น NOT NULL default 0 — ช่องว่างให้เป็น 0
        $validated['min_purchase'] = $validated['min_purchase'] ?? 0;

        $coupon->update($validated);

        return redirect()
            ->route('seller.coupons.index')
            ->with('success', 'อัพเดทคูปองสำเร็จ!');
    }

    /**
     * ลบคูปอง
     */
    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        $store = $this->requireStore($request);

        if ($coupon->store_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์ลบคูปองนี้');
        }

        $coupon->delete();

        return redirect()
            ->route('seller.coupons.index')
            ->with('success', 'ลบคูปองสำเร็จ!');
    }

    /**
     * Toggle สถานะคูปอง
     */
    public function toggleActive(Request $request, Coupon $coupon): JsonResponse
    {
        $store = $this->requireStore($request);

        if ($coupon->store_id !== $store->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์แก้ไขคูปองนี้',
            ], 403);
        }

        $coupon->is_active = ! $coupon->is_active;
        $coupon->save();

        return response()->json([
            'success' => true,
            'is_active' => $coupon->is_active,
            'message' => $coupon->is_active ? 'เปิดใช้งานคูปองแล้ว' : 'ปิดใช้งานคูปองแล้ว',
        ]);
    }
}
