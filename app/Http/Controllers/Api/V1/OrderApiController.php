<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderMessage;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\UserAddress;
use App\Services\ShippingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

            // ค่าจัดส่ง - ใช้ ShippingService คำนวณตามสินค้า
            $shippingService = new ShippingService;
            // แปลง array items เป็น objects เพื่อให้ ShippingService อ่าน ->product ได้
            $cartItemObjects = collect($items)->map(fn ($item) => (object) $item);
            $shippingResult = $shippingService->calculateForCart($cartItemObjects);
            $shippingFee = $shippingResult['total_shipping'];

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
                    'seller_id' => $item['product']->seller_id ?? \App\Models\Product::getOfficialSellerId(),
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
            if (! $request->items) {
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
                    'orders' => $orders->map(fn ($order) => $this->formatOrderSummary($order)),
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
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)
                ->with(['items.product', 'paymentTransaction'])
                ->find($id);

            if (! $order) {
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
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)->find($id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบคำสั่งซื้อ',
                ], 404);
            }

            // ตรวจสอบสถานะที่ยกเลิกได้ (ใช้ canBeCancelled() ให้ตรงกับ Web Controller)
            if (! $order->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถยกเลิกคำสั่งซื้อในสถานะนี้ได้',
                ], 400);
            }

            // ใช้ cancel() method ของ Model — จะ restore stock + refund อัตโนมัติ (ถ้าจ่ายแล้ว)
            $wasPaid = in_array($order->status, ['paid', 'processing']);
            $reason = $request->input('reason', 'ยกเลิกโดยลูกค้า');
            $order->cancel($reason);

            return response()->json([
                'success' => true,
                'message' => $wasPaid
                    ? 'ยกเลิกคำสั่งซื้อสำเร็จ — ระบบดำเนินการคืนเงินเข้า Wallet ให้แล้ว'
                    : 'ยกเลิกคำสั่งซื้อสำเร็จ',
                'refunded' => $wasPaid,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to cancel order', ['order_id' => $id, 'error' => $e->getMessage()]);

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
     * คำนวณค่าจัดส่ง (Fallback สำหรับกรณีที่ไม่มี cart items)
     *
     * @param  float  $subtotal  ยอดรวมสินค้า
     * @param  array  $items  รายการสินค้า
     * @return float ค่าจัดส่ง
     */
    protected function calculateShippingFee(float $subtotal, array $items): float
    {
        // ใช้ค่าเริ่มต้นจาก ShippingService
        if ($subtotal >= ShippingService::DEFAULT_FREE_SHIPPING_THRESHOLD) {
            return 0;
        }

        return ShippingService::DEFAULT_SHIPPING_FEE;
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
            'items' => $order->items->map(fn ($item) => [
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
            'has_unread_messages' => (bool) $order->has_unread_messages,
            'last_message_at' => $order->last_message_at?->toISOString(),
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

    // =====================================================
    // Order Tracking
    // =====================================================

    /**
     * ดึงรายการบริษัทขนส่ง
     */
    public function getShippingProviders(): JsonResponse
    {
        try {
            $providers = ShippingProvider::active()
                ->ordered()
                ->get()
                ->map(fn ($provider) => [
                    'id' => $provider->id,
                    'code' => $provider->code,
                    'name' => $provider->name,
                    'name_en' => $provider->name_en,
                    'logo' => $provider->logo_url,
                    'hotline' => $provider->hotline,
                ]);

            return response()->json([
                'success' => true,
                'data' => $providers,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงรายการบริษัทขนส่งได้',
            ], 500);
        }
    }

    /**
     * ดึงข้อมูล Tracking ของคำสั่งซื้อ
     */
    public function getTracking(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)
                ->with(['shippingProvider', 'trackingHistory.user'])
                ->find($id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบคำสั่งซื้อ',
                ], 404);
            }

            // สร้าง tracking URL
            $trackingUrl = null;
            if ($order->tracking_number && $order->shippingProvider) {
                $trackingUrl = $order->shippingProvider->getTrackingLink($order->tracking_number);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => $this->getStatusLabel($order->status),
                    'tracking_number' => $order->tracking_number,
                    'tracking_url' => $trackingUrl,
                    'shipping_provider' => $order->shippingProvider ? [
                        'id' => $order->shippingProvider->id,
                        'code' => $order->shippingProvider->code,
                        'name' => $order->shippingProvider->name,
                        'logo' => $order->shippingProvider->logo_url,
                        'hotline' => $order->shippingProvider->hotline,
                    ] : null,
                    'estimated_delivery_at' => $order->estimated_delivery_at?->toISOString(),
                    'shipped_at' => $order->shipped_at?->toISOString(),
                    'delivered_at' => $order->delivered_at?->toISOString(),
                    'history' => $order->trackingHistory->map(fn ($history) => [
                        'id' => $history->id,
                        'status' => $history->status,
                        'status_label' => $this->getStatusLabel($history->status),
                        'description' => $history->description,
                        'location' => $history->location,
                        'created_at' => $history->created_at->toISOString(),
                    ]),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Get order tracking error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูล Tracking ได้',
            ], 500);
        }
    }

    // =====================================================
    // Order Chat / Messages
    // =====================================================

    /**
     * ดึงข้อความแชทของคำสั่งซื้อ
     */
    public function getMessages(int $id, Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)->find($id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบคำสั่งซื้อ',
                ], 404);
            }

            $perPage = $request->input('per_page', 50);

            // ดึงข้อความ
            $messages = OrderMessage::where('order_id', $order->id)
                ->with('sender')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Mark messages as read (ข้อความที่ไม่ได้ส่งโดยลูกค้า)
            OrderMessage::where('order_id', $order->id)
                ->where('sender_type', '!=', 'customer')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                    'read_by' => $user->id,
                ]);

            // อัพเดทสถานะ unread ของ order
            $hasUnread = OrderMessage::where('order_id', $order->id)
                ->where('is_read', false)
                ->exists();
            $order->update(['has_unread_messages' => $hasUnread]);

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages->map(fn ($msg) => [
                        'id' => $msg->id,
                        'sender_type' => $msg->sender_type,
                        'sender_name' => $msg->sender_name,
                        'sender_avatar' => $msg->sender_avatar,
                        'message' => $msg->message,
                        'attachment' => $msg->attachment_url,
                        'attachment_type' => $msg->attachment_type,
                        'is_system_message' => $msg->is_system_message,
                        'is_mine' => $msg->sender_type === 'customer' && $msg->sender_id === $user->id,
                        'created_at' => $msg->created_at->toISOString(),
                    ]),
                    'pagination' => [
                        'current_page' => $messages->currentPage(),
                        'last_page' => $messages->lastPage(),
                        'per_page' => $messages->perPage(),
                        'total' => $messages->total(),
                    ],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Get order messages error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อความได้',
            ], 500);
        }
    }

    /**
     * ส่งข้อความแชทใหม่
     */
    public function sendMessage(int $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required_without:attachment|string|max:2000',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ], [
            'message.required_without' => 'กรุณาพิมพ์ข้อความหรือแนบไฟล์',
            'message.max' => 'ข้อความต้องไม่เกิน 2000 ตัวอักษร',
            'attachment.max' => 'ไฟล์แนบต้องไม่เกิน 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)->find($id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบคำสั่งซื้อ',
                ], 404);
            }

            // ตรวจสอบว่า order สามารถแชทได้
            if (in_array($order->status, ['cancelled', 'refunded'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถส่งข้อความในคำสั่งซื้อที่ยกเลิกแล้ว',
                ], 400);
            }

            $attachmentPath = null;
            $attachmentType = null;

            // อัพโหลดไฟล์แนบ (ถ้ามี)
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentPath = $file->store('order-messages/'.$order->id, 'public');
                $attachmentType = $this->getAttachmentType($file->getMimeType());
            }

            // สร้างข้อความ
            $message = OrderMessage::send(
                $order,
                $user->id,
                'customer',
                $request->message ?? '',
                [
                    'attachment' => $attachmentPath,
                    'attachment_type' => $attachmentType,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'ส่งข้อความสำเร็จ',
                'data' => [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'sender_name' => $message->sender_name,
                    'message' => $message->message,
                    'attachment' => $message->attachment_url,
                    'attachment_type' => $message->attachment_type,
                    'is_mine' => true,
                    'created_at' => $message->created_at->toISOString(),
                ],
            ], 201);
        } catch (Exception $e) {
            Log::error('Send order message error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถส่งข้อความได้',
            ], 500);
        }
    }

    /**
     * ดึงจำนวนข้อความที่ยังไม่ได้อ่าน
     */
    public function getUnreadMessageCount(): JsonResponse
    {
        try {
            $user = Auth::user();

            // นับ orders ที่มีข้อความยังไม่ได้อ่าน
            $count = Order::where('user_id', $user->id)
                ->where('has_unread_messages', true)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลได้',
            ], 500);
        }
    }

    /**
     * ระบุประเภทไฟล์แนบจาก MIME type
     */
    protected function getAttachmentType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            $mimeType === 'application/pdf' => 'pdf',
            default => 'file',
        };
    }
}
