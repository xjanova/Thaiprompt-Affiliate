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
    /**
     * แสดงรายการคูปองของร้าน
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $store = $request->user()->vendorStore;

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
     *
     * @return View
     */
    public function create(): View
    {
        return view('seller.coupons.create', [
            'pageTitle' => 'สร้างคูปองใหม่',
        ]);
    }

    /**
     * บันทึกคูปองใหม่
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $store = $request->user()->vendorStore;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed,free_shipping',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_public' => 'boolean',
        ]);

        // สร้างรหัสคูปองอัตโนมัติ
        $code = strtoupper($store->store_slug) . '-' . strtoupper(Str::random(6));

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
            ->with('success', 'สร้างคูปองสำเร็จ! รหัส: ' . $code);
    }

    /**
     * แสดงฟอร์มแก้ไขคูปอง
     *
     * @param Request $request
     * @param Coupon $coupon
     * @return View
     */
    public function edit(Request $request, Coupon $coupon): View
    {
        $store = $request->user()->vendorStore;

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
     *
     * @param Request $request
     * @param Coupon $coupon
     * @return RedirectResponse
     */
    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $store = $request->user()->vendorStore;

        if ($coupon->store_id !== $store->id) {
            abort(403, 'ไม่มีสิทธิ์แก้ไขคูปองนี้');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed,free_shipping',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'is_public' => 'boolean',
        ]);

        $coupon->update($validated);

        return redirect()
            ->route('seller.coupons.index')
            ->with('success', 'อัพเดทคูปองสำเร็จ!');
    }

    /**
     * ลบคูปอง
     *
     * @param Request $request
     * @param Coupon $coupon
     * @return RedirectResponse
     */
    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        $store = $request->user()->vendorStore;

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
     *
     * @param Request $request
     * @param Coupon $coupon
     * @return JsonResponse
     */
    public function toggleActive(Request $request, Coupon $coupon): JsonResponse
    {
        $store = $request->user()->vendorStore;

        if ($coupon->store_id !== $store->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์แก้ไขคูปองนี้',
            ], 403);
        }

        $coupon->is_active = !$coupon->is_active;
        $coupon->save();

        return response()->json([
            'success' => true,
            'is_active' => $coupon->is_active,
            'message' => $coupon->is_active ? 'เปิดใช้งานคูปองแล้ว' : 'ปิดใช้งานคูปองแล้ว',
        ]);
    }
}
