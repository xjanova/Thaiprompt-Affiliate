<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CouponController
 *
 * จัดการคูปองสำหรับผู้ใช้
 */
class CouponController extends Controller
{
    /**
     * แสดงรายการคูปองของฉัน
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $myCoupons = UserCoupon::where('user_id', $user->id)
            ->with(['coupon' => function ($query) {
                $query->where('is_active', true);
            }])
            ->latest()
            ->paginate(20);

        // สถิติคูปอง
        $stats = [
            'total' => UserCoupon::where('user_id', $user->id)->count(),
            'available' => UserCoupon::where('user_id', $user->id)
                ->where('is_used', false)
                ->whereHas('coupon', function ($q) {
                    $q->where('is_active', true)
                        ->where(function ($sq) {
                            $sq->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                })
                ->count(),
            'used' => UserCoupon::where('user_id', $user->id)->where('is_used', true)->count(),
        ];

        return view('user.coupons.index', [
            'myCoupons' => $myCoupons,
            'stats' => $stats,
            'pageTitle' => 'คูปองของฉัน',
        ]);
    }

    /**
     * แสดงคูปองที่สามารถเก็บได้
     *
     * @param Request $request
     * @return View
     */
    public function available(Request $request): View
    {
        $user = $request->user();

        // ดึงคูปองที่เปิดให้สาธารณะเก็บได้
        $availableCoupons = Coupon::where('is_public', true)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->whereDoesntHave('userCoupons', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('store')
            ->latest()
            ->paginate(20);

        return view('user.coupons.available', [
            'coupons' => $availableCoupons,
            'pageTitle' => 'เก็บคูปอง',
        ]);
    }

    /**
     * เก็บคูปอง
     *
     * @param Request $request
     * @param Coupon $coupon
     * @return JsonResponse
     */
    public function collect(Request $request, Coupon $coupon): JsonResponse
    {
        $user = $request->user();

        // ตรวจสอบว่าคูปองเปิดให้เก็บสาธารณะ
        if (!$coupon->is_public) {
            return response()->json([
                'success' => false,
                'message' => 'คูปองนี้ไม่สามารถเก็บได้',
            ], 403);
        }

        // ตรวจสอบว่าคูปองยังใช้ได้
        if (!$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'คูปองหมดอายุหรือไม่สามารถใช้งานได้',
            ], 400);
        }

        // เก็บคูปอง
        $userCoupon = UserCoupon::collectCoupon($user->id, $coupon->id);

        if (!$userCoupon) {
            return response()->json([
                'success' => false,
                'message' => 'คุณเก็บคูปองนี้แล้ว',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'เก็บคูปองสำเร็จ!',
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
        ]);
    }

    /**
     * ประวัติการใช้คูปอง
     *
     * @param Request $request
     * @return View
     */
    public function history(Request $request): View
    {
        $user = $request->user();

        $usedCoupons = UserCoupon::where('user_id', $user->id)
            ->where('is_used', true)
            ->with('coupon')
            ->orderByDesc('used_at')
            ->paginate(20);

        return view('user.coupons.history', [
            'usedCoupons' => $usedCoupons,
            'pageTitle' => 'ประวัติการใช้คูปอง',
        ]);
    }
}
