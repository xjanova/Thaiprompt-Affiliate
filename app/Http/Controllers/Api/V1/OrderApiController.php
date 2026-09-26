<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\ProductReview;
use App\Models\ShippingProvider;
use App\Services\Shop\OrderChatIdempotency;
use App\Services\Shop\OrderChatNotifier;
use App\Services\Shop\ShopPresenter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * OrderApiController — คำสั่งซื้อฝั่งผู้ซื้อในแอป
 *
 * 🛒 (2026-09-25) Workstream D:
 *   - SHOP-28: ลบ POST /orders (สร้างออเดอร์เส้นที่สองที่พังและไม่มีใครใช้) — สร้างออเดอร์ผ่าน
 *     POST /cart/checkout (App\Services\Shop\ShopCheckoutService) เส้นเดียว
 *   - SHOP-20: รายละเอียดออเดอร์อ่านคอลัมน์จริง (discount_amount, customer_notes, snapshot ที่อยู่, unit_price)
 *   - SHOP-14: ติดตามพัสดุใช้ relation ที่มีจริง
 *   - CC-20: ยืนยันรับสินค้า + รีวิวสินค้าจากแอป (ตรรกะเดียวกับเว็บ)
 */
class OrderApiController extends Controller
{
    private const STATUS_FILTERS = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'];

    // =====================================================
    // Order List & Details
    // =====================================================

    /**
     * GET /api/v1/orders?status=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = max(1, min(50, (int) $request->input('per_page', 15)));
            $status = $request->input('status');

            $query = Order::where('user_id', $user->id)
                ->with(['items' => fn ($q) => $q->orderBy('id')])
                ->withCount('items')
                ->orderByDesc('created_at');

            if (is_string($status) && in_array($status, self::STATUS_FILTERS, true)) {
                $query->where('status', $status);
            }

            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->getCollection()->map(fn (Order $order) => ShopPresenter::orderSummary($order))->values(),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                    ],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Order API index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงรายการคำสั่งซื้อได้',
            ], 500);
        }
    }

    /**
     * GET /api/v1/orders/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $order = Order::where('user_id', Auth::id())
                ->with(['items.reviews', 'store', 'paymentTransaction'])
                ->find($id);

            if (! $order) {
                return $this->notFound();
            }

            $data = ShopPresenter::order($order);
            $transaction = $order->paymentTransaction;
            $data['payment'] = $transaction && in_array($transaction->status, ['pending', 'processing'], true) && ! $transaction->isExpired()
                ? ShopPresenter::payment($transaction)
                : null;

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('Order API show failed', ['order_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงรายละเอียดคำสั่งซื้อได้',
            ], 500);
        }
    }

    /**
     * POST /api/v1/orders/{id}/cancel {reason?}
     *
     * คืนเงินเฉพาะออเดอร์ที่จ่ายแล้ว (Order::cancel ตัดสินจาก payment_status) · คืนสต็อกเฉพาะที่ตัดไปแล้ว
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ], [
            'reason.max' => 'เหตุผลยาวเกิน 500 ตัวอักษร',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $order = Order::where('user_id', Auth::id())->find($id);

            if (! $order) {
                return $this->notFound();
            }

            if (! $order->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'code' => ShopException::ACTION_NOT_ALLOWED,
                    'message' => 'ไม่สามารถยกเลิกคำสั่งซื้อในสถานะนี้ได้',
                ], 409);
            }

            $result = $order->cancel($request->input('reason') ?: 'ยกเลิกโดยลูกค้า', null, 'buyer');

            return response()->json([
                'success' => true,
                'message' => $result['refunded']
                    ? 'ยกเลิกคำสั่งซื้อสำเร็จ — คืนเงินเข้ากระเป๋าเงินให้แล้ว'
                    : 'ยกเลิกคำสั่งซื้อสำเร็จ',
                'refunded' => (bool) $result['refunded'],
                'data' => ShopPresenter::order($order->fresh(['items', 'store'])),
            ]);
        } catch (ShopException $e) {
            return $e->toJsonResponse();
        } catch (Exception $e) {
            Log::error('Failed to cancel order', ['order_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยกเลิกคำสั่งซื้อได้ กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * POST /api/v1/orders/{id}/confirm-received — ผู้ซื้อยืนยันรับสินค้า (delivered → completed)
     */
    public function confirmReceived(int $id): JsonResponse
    {
        try {
            $completed = DB::transaction(function () use ($id) {
                $order = Order::where('user_id', Auth::id())->whereKey($id)->lockForUpdate()->first();

                if (! $order) {
                    return null;
                }

                if ($order->status === 'completed') {
                    return $order;
                }

                if ($order->status !== 'delivered') {
                    throw ShopException::make(ShopException::ACTION_NOT_ALLOWED, 'ยืนยันรับสินค้าได้เมื่อสินค้าส่งถึงแล้วเท่านั้น', 409);
                }

                // ผู้ซื้อกดเอง ไม่ต้องแจ้งเตือนตัวเอง
                $order->suppressStatusNotification = true;
                $order->markAsCompleted();

                return $order;
            });

            if (! $completed) {
                return $this->notFound();
            }

            return response()->json([
                'success' => true,
                'message' => 'ยืนยันการรับสินค้าเรียบร้อยแล้ว',
                'data' => ShopPresenter::order($completed->fresh(['items.reviews', 'store'])),
            ]);
        } catch (ShopException $e) {
            return $e->toJsonResponse();
        } catch (Exception $e) {
            Log::error('Order confirm received failed', ['order_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยืนยันการรับสินค้าได้ กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * POST /api/v1/orders/{orderId}/items/{itemId}/review (multipart)
     * body: {rating: 1-5, comment, title?, images[]?}
     */
    public function review(Request $request, int $orderId, int $itemId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|min:2|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:2048',
        ], [
            'rating.required' => 'กรุณาให้คะแนนสินค้า',
            'rating.min' => 'คะแนนต้องอยู่ระหว่าง 1-5',
            'rating.max' => 'คะแนนต้องอยู่ระหว่าง 1-5',
            'comment.required' => 'กรุณาเขียนรีวิว',
            'images.max' => 'แนบรูปได้ไม่เกิน 5 รูป',
            'images.*.image' => 'ไฟล์แนบต้องเป็นรูปภาพ',
            'images.*.max' => 'รูปภาพต้องไม่เกิน 2MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::where('user_id', Auth::id())
            ->whereIn('status', ['delivered', 'completed'])
            ->find($orderId);

        if (! $order) {
            return $this->notFound('ไม่พบคำสั่งซื้อ หรือยังรีวิวไม่ได้เพราะสินค้ายังไม่ถึง');
        }

        $item = $order->items()->find($itemId);
        if (! $item) {
            return $this->notFound('ไม่พบสินค้านี้ในคำสั่งซื้อ');
        }

        if ($item->hasReview()) {
            return response()->json([
                'success' => false,
                'code' => 'ALREADY_REVIEWED',
                'message' => 'คุณรีวิวสินค้านี้แล้ว',
            ], 409);
        }

        try {
            $paths = [];
            foreach ((array) $request->file('images', []) as $image) {
                $paths[] = $image->store('reviews', 'public');
            }

            $review = ProductReview::create([
                'product_id' => $item->product_id,
                'user_id' => Auth::id(),
                'order_item_id' => $item->id,
                'rating' => (int) $request->input('rating'),
                'title' => $request->input('title'),
                'comment' => $request->input('comment'),
                'images' => $paths,
                'is_verified_purchase' => true,
                'is_approved' => true,
            ]);

            $item->product?->updateRating();

            return response()->json([
                'success' => true,
                'message' => 'ขอบคุณสำหรับรีวิวของคุณ',
                'data' => [
                    'id' => (int) $review->id,
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                ],
            ], 201);
        } catch (Exception $e) {
            // กดส่งซ้ำพร้อมกัน → unique(user_id, order_item_id) ละเมิด (SQLSTATE 23000)
            if ($e instanceof \Illuminate\Database\QueryException && (string) $e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'code' => 'ALREADY_REVIEWED',
                    'message' => 'คุณรีวิวสินค้านี้แล้ว',
                ], 409);
            }

            Log::error('Order item review failed', ['order_id' => $orderId, 'item_id' => $itemId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ส่งรีวิวไม่สำเร็จ กรุณาลองใหม่',
            ], 500);
        }
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
     * GET /api/v1/orders/{id}/tracking (SHOP-14: ใช้ relation ที่มีจริง)
     */
    public function getTracking(int $id): JsonResponse
    {
        try {
            $order = Order::where('user_id', Auth::id())
                ->with(['shippingProviderRelation', 'trackingHistory'])
                ->find($id);

            if (! $order) {
                return $this->notFound();
            }

            $provider = $order->shippingProviderRelation;

            return response()->json([
                'success' => true,
                'data' => [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => $order->status_label,
                    'delivery_method' => $order->delivery_method ?? Order::DELIVERY_PARCEL,
                    'tracking_number' => $order->tracking_number,
                    'tracking_url' => $order->tracking_url,
                    'shipping_provider' => $provider ? [
                        'id' => $provider->id,
                        'code' => $provider->code,
                        'name' => $provider->name,
                        'logo' => $provider->logo_url,
                        'hotline' => $provider->hotline,
                    ] : ($order->shipping_provider ? ['id' => null, 'code' => null, 'name' => $order->shipping_provider, 'logo' => null, 'hotline' => null] : null),
                    'rider' => ShopPresenter::riderSummary($order),
                    'estimated_delivery_at' => $order->estimated_delivery_at?->toISOString(),
                    'shipped_at' => $order->shipped_at?->toISOString(),
                    'delivered_at' => $order->delivered_at?->toISOString(),
                    'history' => $order->trackingHistory->map(fn ($history) => [
                        'id' => $history->id,
                        'status' => $history->status,
                        'title' => $history->title,
                        'description' => $history->description,
                        'location' => $history->location,
                        'tracking_number' => $history->tracking_number,
                        'shipping_provider' => $history->shipping_provider,
                        'tracked_at' => $history->tracked_at?->toISOString(),
                    ])->values(),
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
                return $this->notFound();
            }

            $perPage = max(1, min(100, (int) $request->input('per_page', 50)));

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
            // 💬 (2026-09-26) query builder: ไม่แตะ updated_at (ใช้ซ่อนเบอร์ลูกค้าของออเดอร์ที่จบเกิน 7 วัน) และไม่ยิง observer
            Order::whereKey($order->id)->toBase()->update(['has_unread_messages' => $hasUnread]);

            // ลูกค้ากำลังเปิดแชทนี้ → ข้อความร้านช่วงนี้ไม่ต้อง push ซ้อนหน้าแชท
            OrderChatNotifier::markViewing((int) $order->id, (int) $user->id);

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
            // 🔒 (2026-09-26) รับเฉพาะรูป/PDF — เดิมรับไฟล์ทุกชนิดลง disk public (อัปโหลด .html/.php ให้เปิดผ่านเว็บได้)
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,pdf|max:10240', // 10MB max
            // 💬 (2026-09-26) รหัสข้อความจากแอป — ส่งใหม่หลังเน็ตหลุดด้วยรหัสเดิม = ได้ข้อความเดิม ไม่สร้างซ้ำ
            'client_message_id' => OrderChatIdempotency::RULES,
        ], [
            'message.required_without' => 'กรุณาพิมพ์ข้อความหรือแนบไฟล์',
            'message.max' => 'ข้อความต้องไม่เกิน 2000 ตัวอักษร',
            'attachment.mimes' => 'แนบได้เฉพาะรูปภาพหรือไฟล์ PDF',
            'attachment.max' => 'ไฟล์แนบต้องไม่เกิน 10MB',
            'client_message_id.*' => 'รหัสข้อความไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $idemKey = null;

        try {
            $user = Auth::user();

            $order = Order::where('user_id', $user->id)->find($id);

            if (! $order) {
                return $this->notFound();
            }

            // ตรวจสอบว่า order สามารถแชทได้
            if (in_array($order->status, ['cancelled', 'refunded'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถส่งข้อความในคำสั่งซื้อที่ยกเลิกแล้ว',
                ], 400);
            }

            // กันส่งซ้ำ (เหมือนฝั่งร้าน): รหัสเดิมที่ส่งสำเร็จแล้ว → คืนข้อความเดิม
            $idemKey = OrderChatIdempotency::key((int) $order->id, 'customer', (int) $user->id, $request->input('client_message_id'));
            [$claim, $existing] = OrderChatIdempotency::claim($idemKey, (int) $order->id);
            if ($claim !== 'new') {
                $idemKey = null; // ไม่ใช่คีย์ของ request นี้ — ห้ามปล่อยทิ้งใน catch

                return $existing
                    ? response()->json([
                        'success' => true,
                        'message' => 'ส่งข้อความสำเร็จ',
                        'data' => $this->presentSentMessage($existing),
                    ])
                    : response()->json([
                        'success' => false,
                        'code' => 'MESSAGE_IN_FLIGHT',
                        'message' => 'ข้อความนี้กำลังส่งอยู่ รอสักครู่นะ',
                    ], 409);
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

            OrderChatIdempotency::complete($idemKey, (int) $message->id);

            return response()->json([
                'success' => true,
                'message' => 'ส่งข้อความสำเร็จ',
                'data' => $this->presentSentMessage($message),
            ], 201);
        } catch (Exception $e) {
            OrderChatIdempotency::release($idemKey);
            Log::error('Send order message error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถส่งข้อความได้',
            ], 500);
        }
    }

    /**
     * ข้อความที่ลูกค้าเพิ่งส่ง (รูปแบบเดิมของ POST /orders/{id}/messages)
     *
     * @return array<string, mixed>
     */
    private function presentSentMessage(OrderMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender_name,
            'message' => $message->message,
            'attachment' => $message->attachment_url,
            'attachment_type' => $message->attachment_type,
            'is_mine' => true,
            'created_at' => $message->created_at->toISOString(),
        ];
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

    private function notFound(string $message = 'ไม่พบคำสั่งซื้อ'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => ShopException::ORDER_NOT_FOUND,
            'message' => $message,
        ], 404);
    }
}
