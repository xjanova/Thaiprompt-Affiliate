<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTrackingHistory;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\Shop\SellerOrderService;
use App\Services\Shop\ShopPresenter;
use App\Support\Shop\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API ฝั่งร้านค้าในแอป (SHOP-13 / SELLER-14) — แทน getSellerOrders/... เดิมใน MobileApiController
 * ที่ select คอลัมน์ที่ไม่มีจริง (products.image, users.avatar) จนพังทุกครั้ง
 *
 * สิทธิ์: ร้านเห็นเฉพาะออเดอร์ที่มีสินค้าของตัวเอง และเห็นเฉพาะรายการสินค้าของตัวเอง
 * (ออเดอร์ของร้านอื่น = 404 เหมือนไม่มีอยู่ — กัน IDOR)
 */
class SellerOrderApiController extends Controller
{
    /** ตัวกรองรายการที่แอปใช้ → เงื่อนไข */
    private const FILTERS = ['all', 'to_confirm', 'to_ship', 'shipping', 'delivered', 'completed', 'cancelled', 'awaiting_payment'];

    public function __construct(private readonly SellerOrderService $service) {}

    /**
     * GET /api/v1/seller/orders?status=all|to_confirm|to_ship|shipping|delivered|completed|cancelled|awaiting_payment&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $seller = $request->user();
        if ($denied = $this->denySeller($seller)) {
            return $denied;
        }

        $sellerId = (int) $seller->id;
        $status = (string) $request->input('status', 'all');
        if (! in_array($status, self::FILTERS, true)) {
            $status = 'all';
        }

        // โหลดสินค้าทั้งออเดอร์ (ใช้ตรวจว่าเป็นออเดอร์หลายร้าน) แต่แสดงเฉพาะของร้านนี้
        $query = Order::forSeller($sellerId)
            ->with(['items', 'user:id,name'])
            ->latest('id');

        $this->applyFilter($query, $status);

        $perPage = max(1, min(50, (int) $request->input('per_page', 20)));
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'ดึงคำสั่งซื้อของร้านสำเร็จ',
            'data' => [
                'orders' => $orders->getCollection()->map(fn (Order $o) => $this->presentListItem($o, $sellerId))->values(),
                'pagination' => ShopPresenter::pagination($orders),
                'counts' => $this->service->summary($seller)['counts'],
            ],
        ]);
    }

    /**
     * GET /api/v1/seller/orders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $seller = $request->user();
        if ($denied = $this->denySeller($seller)) {
            return $denied;
        }

        $order = $this->findForSeller((int) $seller->id, $id);
        if (! $order) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'ดึงรายละเอียดคำสั่งซื้อสำเร็จ',
            'data' => $this->presentDetail($order, (int) $seller->id),
        ]);
    }

    /**
     * POST /api/v1/seller/orders/{id}/action
     * body: {action: confirm|request_rider|ship|deliver|cancel, tracking_number?, shipping_provider_id?, provider?, estimated_delivery_at?, reason?}
     */
    public function action(Request $request, int $id): JsonResponse
    {
        $seller = $request->user();
        if ($denied = $this->denySeller($seller)) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:'.implode(',', SellerOrderService::ACTIONS),
            'tracking_number' => 'required_if:action,ship|nullable|string|max:100',
            'shipping_provider_id' => 'nullable|integer|exists:shipping_providers,id',
            'provider' => 'nullable|string|max:100',
            'estimated_delivery_at' => 'nullable|date|after_or_equal:today',
            'reason' => 'required_if:action,cancel|nullable|string|max:500',
        ], [
            'action.required' => 'กรุณาระบุคำสั่ง',
            'action.in' => 'คำสั่งไม่ถูกต้อง',
            'tracking_number.required_if' => 'กรุณากรอกหมายเลขพัสดุ',
            'shipping_provider_id.exists' => 'ไม่พบบริษัทขนส่งที่เลือก',
            'reason.required_if' => 'กรุณาระบุเหตุผลในการยกเลิก',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = $this->findForSeller((int) $seller->id, $id);
        if (! $order) {
            return $this->notFound();
        }

        try {
            $updated = $this->service->perform($order, $seller, (string) $request->input('action'), $request->only([
                'tracking_number', 'shipping_provider_id', 'provider', 'estimated_delivery_at', 'reason',
            ]));

            $fresh = $this->findForSeller((int) $seller->id, $updated->id) ?? $updated;

            return response()->json([
                'success' => true,
                'message' => match ((string) $request->input('action')) {
                    'confirm' => 'ยืนยันคำสั่งซื้อแล้ว',
                    'request_rider' => 'เรียกไรเดอร์แล้ว ระบบกำลังหาไรเดอร์ใกล้ร้าน',
                    'ship' => 'บันทึกการจัดส่งแล้ว',
                    'deliver' => 'ยืนยันส่งถึงแล้ว',
                    'cancel' => 'ยกเลิกคำสั่งซื้อแล้ว',
                    default => 'ดำเนินการแล้ว',
                },
                'data' => $this->presentDetail($fresh, (int) $seller->id),
            ]);
        } catch (ShopException $e) {
            return $e->toJsonResponse();
        } catch (\Throwable $e) {
            Log::error('Seller order action failed', [
                'order_id' => $id,
                'seller_id' => $seller->id,
                'action' => $request->input('action'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'ACTION_FAILED',
                'message' => 'ทำรายการไม่สำเร็จ กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * POST /api/v1/seller/orders/{id}/tracking {shipping_provider_id, tracking_number, estimated_delivery_at?}
     * (เส้นทางเดิมของแอป → ใช้ action ship ตัวเดียวกัน)
     */
    public function updateTracking(Request $request, int $id): JsonResponse
    {
        $request->merge(['action' => 'ship']);

        return $this->action($request, $id);
    }

    /**
     * POST /api/v1/seller/orders/{id}/tracking-history {status: in_transit|out_for_delivery, description, location?}
     * บันทึกความคืบหน้าของพัสดุ (ไม่เปลี่ยนสถานะออเดอร์ — ส่งถึงแล้วใช้ action deliver)
     */
    public function addTrackingHistory(Request $request, int $id): JsonResponse
    {
        $seller = $request->user();
        if ($denied = $this->denySeller($seller)) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:in_transit,out_for_delivery',
            'description' => 'required|string|max:500',
            'location' => 'nullable|string|max:255',
        ], [
            'status.required' => 'กรุณาเลือกสถานะ',
            'status.in' => 'สถานะไม่ถูกต้อง',
            'description.required' => 'กรุณากรอกรายละเอียด',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = $this->findForSeller((int) $seller->id, $id);
        if (! $order) {
            return $this->notFound();
        }

        $hasShipped = $order->items->contains(fn (OrderItem $i) => $i->status === 'shipped');
        if (! $hasShipped) {
            return ShopException::make(ShopException::ACTION_NOT_ALLOWED, 'ต้องบันทึกการจัดส่งก่อนจึงเพิ่มความคืบหน้าได้', 409)->toJsonResponse();
        }

        $labels = ['in_transit' => 'อยู่ระหว่างขนส่ง', 'out_for_delivery' => 'กำลังนำส่ง'];

        $history = OrderTrackingHistory::createEntry($order, (string) $request->input('status'), $labels[$request->input('status')], [
            'description' => $request->input('description'),
            'location' => $request->input('location'),
            'created_by' => $seller->id,
            'created_by_type' => 'seller',
            'meta_data' => ['seller_id' => (int) $seller->id],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกความคืบหน้าการจัดส่งแล้ว',
            'data' => [
                'id' => (int) $history->id,
                'status' => $history->status,
                'title' => $history->title,
                'description' => $history->description,
                'location' => $history->location,
                'tracked_at' => $history->tracked_at?->toISOString(),
            ],
        ]);
    }

    /**
     * GET /api/v1/seller/summary — ตัวเลขหน้าแรกของร้าน (งานค้าง + ยอดขาย + GP)
     */
    public function summary(Request $request): JsonResponse
    {
        $seller = $request->user();
        if ($denied = $this->denySeller($seller)) {
            return $denied;
        }

        return response()->json([
            'success' => true,
            'message' => 'ดึงสรุปร้านค้าสำเร็จ',
            'data' => $this->service->summary($seller),
        ]);
    }

    /**
     * GET /api/v1/seller/shipping-providers
     */
    public function shippingProviders(): JsonResponse
    {
        $providers = ShippingProvider::active()
            ->ordered()
            ->get()
            ->map(fn (ShippingProvider $p) => [
                'id' => (int) $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'name_en' => $p->name_en,
                'logo' => ShopPresenter::imageUrl($p->logo),
                'hotline' => $p->hotline,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $providers]);
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    private function isSeller(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return VendorStore::where('user_id', $user->id)->exists()
            || $user->hasRole(['seller', 'admin', 'super_admin']);
    }

    private function findForSeller(int $sellerId, int $orderId): ?Order
    {
        return Order::forSeller($sellerId)
            ->with(['items', 'user:id,name', 'store', 'trackingHistory', 'shippingProviderRelation'])
            ->find($orderId);
    }

    private function applyFilter($query, string $status): void
    {
        match ($status) {
            'to_confirm' => $query->whereIn('status', ['pending', 'paid'])
                ->where(fn ($q) => $q->where('payment_status', 'paid')->orWhere('payment_method', PaymentMethod::COD)),
            'awaiting_payment' => $query->where('status', 'pending')->where('payment_status', 'pending')
                ->where('payment_method', '!=', PaymentMethod::COD),
            'to_ship' => $query->where('status', 'processing'),
            'shipping' => $query->where('status', 'shipped'),
            'delivered' => $query->where('status', 'delivered'),
            'completed' => $query->where('status', 'completed'),
            'cancelled' => $query->whereIn('status', ['cancelled', 'refunded']),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function presentListItem(Order $order, int $sellerId): array
    {
        $items = $order->items->where('seller_id', $sellerId);
        $first = $items->first();

        return [
            'id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'status' => (string) $order->status,
            'status_label' => $order->status_label,
            'payment_status' => (string) $order->payment_status,
            'payment_method' => $order->payment_method,
            'payment_method_label' => PaymentMethod::labelTh($order->payment_method),
            'delivery_method' => $order->delivery_method ?? Order::DELIVERY_PARCEL,
            'customer_name' => $order->user?->name ?? 'ลูกค้า',
            'items_count' => (int) $items->sum('quantity'),
            'first_item' => $first ? [
                'product_name' => (string) $first->product_name,
                'product_image' => ShopPresenter::imageUrl($first->product_image),
            ] : null,
            'seller_total' => round((float) $items->sum('total'), 2),
            'seller_earning' => round((float) $items->sum('seller_earning'), 2),
            'is_multi_seller' => $order->isMultiSeller(),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentDetail(Order $order, int $sellerId): array
    {
        $items = $order->items->where('seller_id', $sellerId)->values();
        $shipping = ShopPresenter::shipping($order);
        $orderOver = in_array($order->status, ['completed', 'cancelled', 'refunded'], true)
            && $order->updated_at && $order->updated_at->lt(now()->subDays(7));

        // ข้อมูลติดต่อลูกค้าให้ร้านใช้ส่งของ — ซ่อนเบอร์เมื่อออเดอร์จบเกิน 7 วัน
        if ($shipping !== null && $orderOver) {
            $shipping['phone'] = null;
        }

        $single = ! $order->isMultiSeller();

        return [
            'order' => [
                'id' => (int) $order->id,
                'order_number' => (string) $order->order_number,
                'status' => (string) $order->status,
                'status_label' => $order->status_label,
                'payment_status' => (string) $order->payment_status,
                'payment_status_label' => ShopPresenter::paymentStatusLabel($order->payment_status),
                'payment_method' => $order->payment_method,
                'payment_method_label' => PaymentMethod::labelTh($order->payment_method),
                'delivery_method' => $order->delivery_method ?? Order::DELIVERY_PARCEL,
                'is_multi_seller' => ! $single,
                'note' => $order->customer_notes,
                'created_at' => $order->created_at?->toISOString(),
                'paid_at' => $order->paid_at?->toISOString(),
                'shipped_at' => $order->shipped_at?->toISOString(),
                'delivered_at' => $order->delivered_at?->toISOString(),
                'cancellation_reason' => $order->cancellation_reason,
            ],
            'customer' => [
                'name' => $order->user?->name ?? 'ลูกค้า',
            ],
            'shipping' => $shipping,
            'items' => $items->map(fn (OrderItem $item) => ShopPresenter::orderItem($item) + [
                'gp_rate' => round((float) $item->commission_rate, 2),
                'gp_amount' => round((float) $item->commission_amount, 2),
                'seller_earning' => round((float) $item->seller_earning, 2),
            ])->values(),
            'totals' => [
                'items_total' => round((float) $items->sum('total'), 2),
                'discount' => round((float) $items->sum('discount_amount'), 2),
                'gp_amount' => round((float) $items->sum('commission_amount'), 2),
                'seller_earning' => round((float) $items->sum('seller_earning'), 2),
                // ค่าส่งของออเดอร์ร้านเดียว (ลูกค้าจ่าย) — ออเดอร์หลายร้านไม่แสดง
                'shipping_fee' => $single ? round((float) $order->shipping_fee, 2) : null,
                'order_total' => $single ? round((float) $order->total_amount, 2) : null,
            ],
            'tracking' => [
                'tracking_number' => $single ? $order->tracking_number : null,
                'shipping_provider' => $single ? $order->shipping_provider : null,
                'tracking_url' => $single ? $order->tracking_url : null,
                'estimated_delivery_at' => $order->estimated_delivery_at?->toISOString(),
            ],
            'rider' => ShopPresenter::riderSummary($order),
            'tracking_history' => $order->trackingHistory
                ->filter(function (OrderTrackingHistory $h) use ($sellerId, $single) {
                    if ($single) {
                        return true;
                    }
                    $meta = is_array($h->meta_data) ? $h->meta_data : [];

                    return ! isset($meta['seller_id']) || (int) $meta['seller_id'] === $sellerId;
                })
                ->map(fn (OrderTrackingHistory $h) => [
                    'id' => (int) $h->id,
                    'status' => $h->status,
                    'title' => $h->title,
                    'description' => $h->description,
                    'location' => $h->location,
                    'tracking_number' => $h->tracking_number,
                    'shipping_provider' => $h->shipping_provider,
                    'tracked_at' => $h->tracked_at?->toISOString(),
                ])
                ->values(),
            'allowed_actions' => $this->service->allowedActions($order, $sellerId),
        ];
    }

    /**
     * ด่านสิทธิ์ของทุก endpoint ฝั่งร้าน: ต้องเป็นผู้ขาย และร้านต้องไม่ถูกระงับ/ปิด
     * (เงื่อนไขเดียวกับ EnsureHasVendorStore ของหน้าเว็บ /seller/* — เดิม API แอปเช็คแค่ว่ามีร้าน)
     *
     * @return JsonResponse|null null = ผ่าน
     */
    private function denySeller(?User $user): ?JsonResponse
    {
        if (! $this->isSeller($user)) {
            return $this->notSeller();
        }

        // แอดมินไม่ต้องมีร้าน
        if ($user->is_super_admin || in_array($user->role, ['admin', 'super_admin'], true)) {
            return null;
        }

        $store = VendorStore::where('user_id', $user->id)->orderBy('id')->first();
        if ($store && $store->isBlockedFromSelling()) {
            return response()->json([
                'success' => false,
                'code' => ShopException::STORE_SUSPENDED,
                'message' => 'ร้านค้าของคุณถูกระงับการใช้งาน กรุณาติดต่อเจ้าหน้าที่',
                'data' => ['reason' => $store->suspension_reason],
            ], 403);
        }

        return null;
    }

    private function notSeller(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => ShopException::NOT_A_SELLER,
            'message' => 'บัญชีนี้ยังไม่ได้เปิดร้านค้า',
        ], 403);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => ShopException::ORDER_NOT_FOUND,
            'message' => 'ไม่พบคำสั่งซื้อ',
        ], 404);
    }
}
