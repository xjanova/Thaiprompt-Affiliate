<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StoreRating;
use App\Models\VendorStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * StoreRatingController
 *
 * จัดการการให้คะแนนร้านค้าโดยผู้ใช้
 * - ให้คะแนนร้านค้า
 * - ดูประวัติการให้คะแนน
 * - แก้ไขคะแนนที่ให้ไป
 */
class StoreRatingController extends Controller
{
    /**
     * บันทึกคะแนนร้านค้า
     *
     * @return JsonResponse|RedirectResponse
     */
    public function store(Request $request, VendorStore $store)
    {
        $user = $request->user();

        // ตรวจสอบข้อมูล
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'service_rating' => 'nullable|integer|min:1|max:5',
            'shipping_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_anonymous' => 'boolean',
            'order_id' => 'nullable|exists:orders,id',
        ], [
            'rating.required' => 'กรุณาให้คะแนนร้านค้า',
            'rating.min' => 'คะแนนต้องอยู่ระหว่าง 1-5',
            'rating.max' => 'คะแนนต้องอยู่ระหว่าง 1-5',
            'comment.max' => 'ความคิดเห็นต้องไม่เกิน 1000 ตัวอักษร',
            'images.max' => 'อัพโหลดรูปได้สูงสุด 5 รูป',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        // ตรวจสอบ order (ถ้ามี)
        $orderId = $request->input('order_id');
        if ($orderId) {
            $order = Order::find($orderId);
            if (! $order || $order->user_id !== $user->id) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'คำสั่งซื้อไม่ถูกต้อง',
                    ], 403);
                }

                return back()->with('error', 'คำสั่งซื้อไม่ถูกต้อง');
            }
        }

        // อัพโหลดรูปภาพ
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('store-ratings', 'public');
                $images[] = $path;
            }
        }

        // สร้างหรืออัพเดทคะแนน
        $rating = StoreRating::rateStore($store, $user, [
            'order_id' => $orderId,
            'rating' => $request->input('rating'),
            'service_rating' => $request->input('service_rating'),
            'shipping_rating' => $request->input('shipping_rating'),
            'communication_rating' => $request->input('communication_rating'),
            'comment' => $request->input('comment'),
            'images' => ! empty($images) ? $images : null,
            'is_anonymous' => $request->boolean('is_anonymous'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ให้คะแนนร้านค้าสำเร็จ',
                'rating' => $rating,
            ]);
        }

        return back()->with('success', 'ให้คะแนนร้านค้าสำเร็จ');
    }

    /**
     * ลบคะแนนที่ตัวเองให้
     *
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, StoreRating $rating)
    {
        $user = $request->user();

        // ตรวจสอบว่าเป็นเจ้าของคะแนน
        if ($rating->user_id !== $user->id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสิทธิ์ลบคะแนนนี้',
                ], 403);
            }

            return back()->with('error', 'ไม่มีสิทธิ์ลบคะแนนนี้');
        }

        $store = $rating->store;
        $rating->delete();

        // อัพเดทคะแนนเฉลี่ยร้าน
        StoreRating::updateStoreAverages($store);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ลบคะแนนสำเร็จ',
            ]);
        }

        return back()->with('success', 'ลบคะแนนสำเร็จ');
    }

    /**
     * กด helpful ให้รีวิว
     */
    public function markHelpful(Request $request, StoreRating $rating): JsonResponse
    {
        // เพิ่ม helpful count
        $rating->incrementHelpful();

        return response()->json([
            'success' => true,
            'message' => 'ขอบคุณที่ให้ความเห็น',
            'helpful_count' => $rating->helpful_count,
        ]);
    }
}
