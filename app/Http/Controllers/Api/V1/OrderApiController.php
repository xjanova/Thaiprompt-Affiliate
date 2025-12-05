<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * OrderApiController
 *
 * API endpoints สำหรับ Order ใน Mobile App
 * รองรับ: Create order, List orders, Order details
 */
class OrderApiController extends Controller
{
    // =====================================================
    // Order Creation
    // =====================================================

    /**
     * สร้าง Order ใหม่จากตะกร้าสินค้า
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipping_address_id' => 'nullable|integer|exists:user_addresses,id',
            'shipping_address' => 'required_without:shipping_address_id|array',
            'shipping_address.name' => 'required_with:shipping_address|string|max:255',
            'shipping_address.phone' => 'required_with:shipping_address|string|max:20',
            'shipping_address.address' => 'required_with:shipping_address|string',
            'shipping_address.province' => 'required_with:shipping_address|string',
            'shipping_address.district' => 'required_with:shipping_address|string',
            'shipping_address.subdistrict' => 'required_with:shipping_address|string',
            'shipping_address.postal_code' => 'required_with:shipping_address|string',
            'payment_method' => 'nullable|string',
            'note' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ], [
            'shipping_address.required_without' => 'กรุณาระบุที่อยู่จัดส่ง',
            'shipping_address.name.required_with' => 'กรุณาระบุชื่อผู้รับ',
            'shipping_address.phone.required_with' => 'กรุณาระบุเบอร์โทรศัพท์',
            'shipping_address.address.required_with' => 'กรุณาระบุที่อยู่',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // ดึงสินค้าจาก items หรือ cart
            $items = $this->getOrderItems($user, $request->items);

            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสินค้าในตะกร้า',
                ], 400);
            }

            // ตรวจสอบ stock
            foreach ($items as $item) {
                if ($item['product']->track_inventory && $item['product']->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "สินค้า {$item['product']->name} มีไม่เพียงพอ (คงเหลือ {$item['product']->stock_quantity})",
                    ], 400);
                }
            }

            // คำนวณราคา
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            // ค่าจัดส่ง (สามารถคำนวณตามเงื่อนไขได้)
            $shippingFee = $this->calculateShippingFee($subtotal, $items);

            // ส่วนลด (ถ้ามี coupon)
            $discount = 0;

            $totalAmount = $subtotal + $shippingFee - $discount;

            // ดึงหรือสร้างที่อยู่จัดส่ง
            $shippingAddress = $this->getShippingAddress($user, $request);

            // สร้าง Order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'currency' => 'THB',
                'shipping_name' => $shippingAddress['name'],
                'shipping_phone' => $shippingAddress['phone'],
                'shipping_address' => $shippingAddress['address'],
                'shipping_province' => $shippingAddress['province'],
                'shipping_district' => $shippingAddress['district'],
                'shipping_subdistrict' => $shippingAddress['subdistrict'],
                'shipping_postal_code' => $shippingAddress['postal_code'],
                'note' => $request->note,
                'metadata' => [
                    'source' => 'mobile_app',
                    'device' => $request->header('User-Agent'),
                ],
            ]);

            // สร้าง Order Items
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_sku' => $item['product']->sku,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'metadata' => [
                        'product_image' => $item['product']->image,
                    ],
                ]);
            }

            // ลบสินค้าออกจากตะกร้า (ถ้าใช้ตะกร้า)
            if (!$request->items) {
                $this->clearCart($user);
            }

            DB::commit();

            // โหลด order พร้อม items
            $order->load('items.product');

            return response()->json([
                'success' => true,
                'message' => 'สร้างคำสั่งซื้อสำเร็จ',
                'data' => $this->formatOrder($order),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ',
            ], 500);
        }
    }

    // =====================================================
    // Order List & Details
    // =====================================================

    /**
     * ดึงรายการคำสั่งซื้อ
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');

            $query = Order::where('user_id', $user->id)
                ->with(['items' => function ($q) {
                    $q->limit(3); // แสดงแค่ 3 items แรก
                }])
                ->orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->map(fn($order) => $this->formatOrderSummary($order)),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                    ],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงรายการคำสั่งซื้อได้',
            ], 500);
        }
    }

    /**
     * ดึงรายละเอียดคำสั่งซื้อ
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)
                ->with(['items.product', 'paymentTransactions'])
                ->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบคำสั่งซื้อ',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงรายละเอียดคำสั่งซื้อได้',
            ], 500);
        }
    }

    /**
     * ยกเลิกคำสั่งซื้อ
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบคำสั่งซื้อ',
                ], 404);
            }

            // ตรวจสอบสถานะที่ยกเลิกได้
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถยกเลิกคำสั่งซื้อในสถานะนี้ได้',
                ], 400);
            }

            // ตรวจสอบว่าชำระเงินแล้วหรือยัง
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'คำสั่งซื้อที่ชำระเงินแล้วไม่สามารถยกเลิกได้ กรุณาติดต่อ support',
                ], 400);
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_reason' => 'ยกเลิกโดยลูกค้า',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ยกเลิกคำสั่งซื้อสำเร็จ',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยกเลิกคำสั่งซื้อได้',
            ], 500);
        }
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    /**
     * ดึงสินค้าสำหรับสร้าง order
     */
    protected function getOrderItems($user, ?array $requestItems): array
    {
        $items = [];

        if ($requestItems) {
            // ใช้ items จาก request
            foreach ($requestItems as $item) {
                $product = Product::find($item['product_id']);
                if ($product && $product->is_active) {
                    $items[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'price' => $product->sale_price ?? $product->price,
                    ];
                }
            }
        } else {
            // ใช้ items จาก cart
            $cart = Cart::where('user_id', $user->id)->first();
            if ($cart) {
                $cartItems = CartItem::where('cart_id', $cart->id)
                    ->with('product')
                    ->get();

                foreach ($cartItems as $cartItem) {
                    if ($cartItem->product && $cartItem->product->is_active) {
                        $items[] = [
                            'product' => $cartItem->product,
                            'quantity' => $cartItem->quantity,
                            'price' => $cartItem->product->sale_price ?? $cartItem->product->price,
                        ];
                    }
                }
            }
        }

        return $items;
    }

    /**
     * คำนวณค่าจัดส่ง
     */
    protected function calculateShippingFee(float $subtotal, array $items): float
    {
        // ฟรีค่าส่งเมื่อซื้อครบ 500 บาท
        if ($subtotal >= 500) {
            return 0;
        }

        // ค่าส่งเริ่มต้น
        return 50;
    }

    /**
     * ดึงหรือสร้างที่อยู่จัดส่ง
     */
    protected function getShippingAddress($user, Request $request): array
    {
        if ($request->shipping_address_id) {
            $address = UserAddress::where('user_id', $user->id)
                ->find($request->shipping_address_id);

            if ($address) {
                return [
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'address' => $address->address,
                    'province' => $address->province,
                    'district' => $address->district,
                    'subdistrict' => $address->subdistrict,
                    'postal_code' => $address->postal_code,
                ];
            }
        }

        return $request->shipping_address;
    }

    /**
     * สร้าง order number
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}{$date}{$random}";
    }

    /**
     * ล้างตะกร้า
     */
    protected function clearCart($user): void
    {
        $cart = Cart::where('user_id', $user->id)->first();
        if ($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
        }
    }

    /**
     * Format order สำหรับ response
     */
    protected function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $this->getStatusLabel($order->status),
            'payment_status' => $order->payment_status,
            'payment_status_label' => $this->getPaymentStatusLabel($order->payment_status),
            'payment_method' => $order->payment_method,
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'discount' => $order->discount,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'shipping' => [
                'name' => $order->shipping_name,
                'phone' => $order->shipping_phone,
                'address' => $order->shipping_address,
                'province' => $order->shipping_province,
                'district' => $order->shipping_district,
                'subdistrict' => $order->shipping_subdistrict,
                'postal_code' => $order->shipping_postal_code,
            ],
            'items' => $order->items->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_image' => $item->metadata['product_image'] ?? null,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total,
            ]),
            'note' => $order->note,
            'created_at' => $order->created_at->toISOString(),
            'paid_at' => $order->paid_at?->toISOString(),
            'shipped_at' => $order->shipped_at?->toISOString(),
            'delivered_at' => $order->delivered_at?->toISOString(),
        ];
    }

    /**
     * Format order summary สำหรับ list
     */
    protected function formatOrderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $this->getStatusLabel($order->status),
            'payment_status' => $order->payment_status,
            'total_amount' => $order->total_amount,
            'items_count' => $order->items->count(),
            'first_item' => $order->items->first() ? [
                'product_name' => $order->items->first()->product_name,
                'product_image' => $order->items->first()->metadata['product_image'] ?? null,
            ] : null,
            'created_at' => $order->created_at->toISOString(),
        ];
    }

    /**
     * แปลง status เป็น label ภาษาไทย
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'รอดำเนินการ',
            'confirmed' => 'ยืนยันแล้ว',
            'processing' => 'กำลังเตรียมสินค้า',
            'shipped' => 'จัดส่งแล้ว',
            'delivered' => 'ส่งถึงแล้ว',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก',
            'refunded' => 'คืนเงินแล้ว',
            default => $status,
        };
    }

    /**
     * แปลง payment status เป็น label ภาษาไทย
     */
    protected function getPaymentStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'รอชำระเงิน',
            'paid' => 'ชำระเงินแล้ว',
            'failed' => 'ชำระเงินล้มเหลว',
            'refunded' => 'คืนเงินแล้ว',
            default => $status,
        };
    }
}
