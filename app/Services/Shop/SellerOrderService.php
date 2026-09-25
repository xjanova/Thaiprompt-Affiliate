<?php

namespace App\Services\Shop;

use App\Exceptions\RiderJobException;
use App\Exceptions\ShopException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTrackingHistory;
use App\Models\RiderJob;
use App\Models\ShippingProvider;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\RiderDispatchService;
use Illuminate\Support\Facades\DB;

/**
 * งานฝั่งร้านค้ากับออเดอร์ (ใช้ร่วมกันทั้งเว็บ /seller/orders และ API แอป /api/v1/seller/orders)
 *
 * กติกา (SELLER-14 / SHOP-13):
 * - ร้านเห็น/แก้ได้เฉพาะออเดอร์ที่มีสินค้าของตัวเอง และแก้ได้เฉพาะรายการสินค้าของตัวเอง
 * - เริ่มเตรียม/ส่งของได้เมื่อออเดอร์จ่ายแล้ว หรือเป็น COD เท่านั้น (ออเดอร์ค้างจ่ายห้ามส่ง)
 * - ออเดอร์เก่าที่มีหลายร้าน: สถานะ/เลขพัสดุระดับออเดอร์ไม่ถูกเขียนทับโดยร้านใดร้านหนึ่ง
 *   (บันทึกเลขพัสดุของร้านไว้ในประวัติการจัดส่ง · ออเดอร์เปลี่ยนสถานะเมื่อทุกร้านถึงขั้นเดียวกัน)
 * - ส่งด้วยไรเดอร์: ร้านกด "เรียกไรเดอร์" → RiderDispatchService::createJobForSource($order, 'shop_delivery')
 */
class SellerOrderService
{
    public const ACTIONS = ['confirm', 'request_rider', 'ship', 'deliver', 'cancel'];

    public function __construct(private readonly RiderDispatchService $dispatch) {}

    /**
     * ปุ่มที่ร้านกดได้ตอนนี้
     *
     * @return array<int, string>
     */
    public function allowedActions(Order $order, int $sellerId): array
    {
        $actions = [];
        $items = $this->sellerItems($order, $sellerId);
        if ($items->isEmpty() || in_array($order->status, Order::TERMINAL_STATUSES, true)) {
            return $actions;
        }

        $ready = $order->isReadyForFulfilment();
        $itemStatuses = $items->pluck('status')->unique()->all();
        $activeJob = $order->isRiderDelivery() ? $order->activeRiderJob() : null;

        if ($ready && in_array('pending', $itemStatuses, true)) {
            $actions[] = 'confirm';
        }

        if ($ready && $order->isRiderDelivery() && ! $activeJob
            && in_array($order->status, ['pending', 'paid', 'processing'], true)
            && $this->storeCanDispatch($order)) {
            $actions[] = 'request_rider';
        }

        if ($ready && ! $order->isRiderDelivery()
            && array_intersect($itemStatuses, ['pending', 'processing']) !== []) {
            $actions[] = 'ship';
        }

        if (! $order->isRiderDelivery() && in_array('shipped', $itemStatuses, true)) {
            $actions[] = 'deliver';
        }

        if (! $order->isMultiSeller()
            && in_array($order->status, ['pending', 'paid', 'processing'], true)
            && $order->shipped_at === null
            && ! $order->hasRiderPastPickup()
            && ($ready || $order->status === 'pending')) {
            $actions[] = 'cancel';
        }

        return $actions;
    }

    /**
     * ทำตาม action ที่ร้านกด
     *
     * @param  array<string, mixed>  $data  ship: tracking_number, shipping_provider_id|provider, estimated_delivery_at? · cancel: reason
     * @return Order ออเดอร์ล่าสุดหลังทำ
     *
     * @throws ShopException
     */
    public function perform(Order $order, User $seller, string $action, array $data = []): Order
    {
        $sellerId = (int) $seller->id;

        if ($this->sellerItems($order, $sellerId)->isEmpty()) {
            throw ShopException::make(ShopException::ORDER_NOT_FOUND, 'ไม่พบคำสั่งซื้อ', 404);
        }

        // ร้านที่ถูกระงับ/ปิด ทำรายการกับออเดอร์ไม่ได้ (ส่งของ/ยืนยันส่งถึง = เริ่มนับวันปล่อยเงิน)
        // ด่านเดียวกันทั้งเว็บและแอป — กันช่องทางที่ข้าม middleware
        $store = VendorStore::where('user_id', $sellerId)->orderBy('id')->first();
        if ($store && $store->isBlockedFromSelling()) {
            throw ShopException::make(ShopException::STORE_SUSPENDED, 'ร้านค้าของคุณถูกระงับการใช้งาน จึงจัดการคำสั่งซื้อไม่ได้ กรุณาติดต่อเจ้าหน้าที่', 403);
        }

        if (! in_array($action, $this->allowedActions($order, $sellerId), true)) {
            throw ShopException::make(
                ShopException::ACTION_NOT_ALLOWED,
                $this->notAllowedMessage($order, $action),
                409,
                ['allowed_actions' => $this->allowedActions($order, $sellerId)]
            );
        }

        return match ($action) {
            'confirm' => $this->confirm($order, $sellerId),
            'request_rider' => $this->requestRider($order, $sellerId),
            'ship' => $this->ship($order, $sellerId, $data),
            'deliver' => $this->deliver($order, $sellerId),
            'cancel' => $this->cancel($order, $sellerId, (string) ($data['reason'] ?? '')),
        };
    }

    /**
     * ร้านยืนยันรับออเดอร์ → กำลังเตรียมสินค้า
     */
    private function confirm(Order $order, int $sellerId): Order
    {
        return DB::transaction(function () use ($order, $sellerId) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            OrderItem::where('order_id', $locked->id)
                ->where('seller_id', $sellerId)
                ->where('status', 'pending')
                ->update(['status' => 'processing']);

            if (in_array($locked->status, ['pending', 'paid'], true)) {
                $locked->status = 'processing';
                $locked->save();
            }

            OrderTrackingHistory::createEntry($locked, 'processing', 'ร้านยืนยันคำสั่งซื้อแล้ว', [
                'description' => 'กำลังเตรียมสินค้า',
                'created_by' => $sellerId,
                'created_by_type' => 'seller',
                'meta_data' => ['seller_id' => $sellerId],
            ]);

            return $locked->fresh();
        });
    }

    /**
     * เรียกไรเดอร์ของแพลตฟอร์มมารับของ
     *
     * @throws ShopException เมื่อระบบไรเดอร์ปฏิเสธ (นอกพื้นที่, พิกัดไม่ครบ, COD เกินวงเงิน)
     */
    private function requestRider(Order $order, int $sellerId): Order
    {
        try {
            $job = $this->dispatch->createJobForSource($order, 'shop_delivery');
        } catch (RiderJobException $e) {
            throw ShopException::make(ShopException::ACTION_NOT_ALLOWED, $e->getMessage(), 422, ['rider_code' => $e->errorCode]);
        }

        return DB::transaction(function () use ($order, $sellerId, $job) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            OrderItem::where('order_id', $locked->id)
                ->where('seller_id', $sellerId)
                ->where('status', 'pending')
                ->update(['status' => 'processing']);

            if (in_array($locked->status, ['pending', 'paid'], true)) {
                $locked->status = 'processing';
                $locked->save();
            }

            OrderTrackingHistory::createEntry($locked, 'rider_requested', 'ร้านเรียกไรเดอร์แล้ว', [
                'description' => 'กำลังหาไรเดอร์ใกล้ร้าน',
                'created_by' => $sellerId,
                'created_by_type' => 'seller',
                'meta_data' => ['rider_job_id' => $job->id, 'seller_id' => $sellerId],
            ]);

            return $locked->fresh();
        });
    }

    /**
     * บันทึกเลขพัสดุ → สินค้าของร้านนี้ "จัดส่งแล้ว"
     *
     * @param  array<string, mixed>  $data
     */
    private function ship(Order $order, int $sellerId, array $data): Order
    {
        $tracking = trim((string) ($data['tracking_number'] ?? ''));
        if ($tracking === '') {
            throw ShopException::make(ShopException::ACTION_NOT_ALLOWED, 'กรุณากรอกหมายเลขพัสดุ', 422);
        }

        $provider = null;
        if (! empty($data['shipping_provider_id'])) {
            $provider = ShippingProvider::find((int) $data['shipping_provider_id']);
            if (! $provider) {
                throw ShopException::make(ShopException::ACTION_NOT_ALLOWED, 'ไม่พบบริษัทขนส่งที่เลือก', 422);
            }
        }
        $providerName = $provider?->name ?? trim((string) ($data['provider'] ?? ''));
        if ($providerName === '') {
            throw ShopException::make(ShopException::ACTION_NOT_ALLOWED, 'กรุณาเลือกบริษัทขนส่ง', 422);
        }

        return DB::transaction(function () use ($order, $sellerId, $tracking, $provider, $providerName, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            OrderItem::where('order_id', $locked->id)
                ->where('seller_id', $sellerId)
                ->whereIn('status', ['pending', 'processing'])
                ->update(['status' => 'shipped']);

            // ออเดอร์ร้านเดียว (หรือทุกร้านส่งครบแล้ว) → สถานะ/เลขพัสดุระดับออเดอร์
            $allShipped = ! OrderItem::where('order_id', $locked->id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            if (! $locked->isMultiSeller()) {
                $locked->tracking_number = $tracking;
                $locked->shipping_provider = $providerName;
                $locked->shipping_provider_id = $provider?->id;
                if (! empty($data['estimated_delivery_at'])) {
                    $locked->estimated_delivery_at = $data['estimated_delivery_at'];
                }
            }

            if ($allShipped && ! in_array($locked->status, ['shipped', 'delivered'], true)) {
                $locked->status = 'shipped';
                $locked->shipped_at = $locked->shipped_at ?? now();
            }

            if ($locked->isDirty()) {
                $locked->save();
            }

            OrderTrackingHistory::createEntry($locked, 'shipped', 'จัดส่งสินค้าแล้ว', [
                'description' => "หมายเลขพัสดุ: {$tracking} ({$providerName})",
                'tracking_number' => $tracking,
                'shipping_provider' => $providerName,
                'created_by' => $sellerId,
                'created_by_type' => 'seller',
                'meta_data' => ['seller_id' => $sellerId, 'shipping_provider_id' => $provider?->id],
            ]);

            return $locked->fresh();
        });
    }

    /**
     * ร้านยืนยันว่าพัสดุส่งถึงแล้ว (ส่งพัสดุเท่านั้น — ไรเดอร์อัปเดตเองอัตโนมัติ)
     */
    private function deliver(Order $order, int $sellerId): Order
    {
        return DB::transaction(function () use ($order, $sellerId) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            OrderItem::where('order_id', $locked->id)
                ->where('seller_id', $sellerId)
                ->where('status', 'shipped')
                ->update(['status' => 'delivered']);

            $allDelivered = ! OrderItem::where('order_id', $locked->id)
                ->whereIn('status', ['pending', 'processing', 'shipped'])
                ->exists();

            OrderTrackingHistory::createEntry($locked, 'delivered', 'ร้านยืนยันว่าสินค้าส่งถึงแล้ว', [
                'created_by' => $sellerId,
                'created_by_type' => 'seller',
                'meta_data' => ['seller_id' => $sellerId],
            ]);

            if ($allDelivered && $locked->status !== 'delivered') {
                $locked->markAsDelivered();
            }

            return $locked->fresh();
        });
    }

    /**
     * ร้านยกเลิก (สินค้าหมด/ส่งไม่ได้) → คืนเงินผู้ซื้อถ้าจ่ายแล้ว + คืนสต็อก
     */
    private function cancel(Order $order, int $sellerId, string $reason): Order
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 3) {
            throw ShopException::make(ShopException::ACTION_NOT_ALLOWED, 'กรุณาระบุเหตุผลในการยกเลิก', 422);
        }

        $order->cancel('ร้านยกเลิก: '.mb_substr($reason, 0, 450), null, 'seller');

        return $order->fresh();
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * รายการสินค้าของร้านนี้ในออเดอร์
     *
     * @return \Illuminate\Support\Collection<int, OrderItem>
     */
    public function sellerItems(Order $order, int $sellerId)
    {
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->get();

        return $items->filter(fn (OrderItem $item) => (int) $item->seller_id === $sellerId)->values();
    }

    private function storeCanDispatch(Order $order): bool
    {
        $store = $order->resolveStore();

        return $store !== null && $store->canUseRiderDelivery();
    }

    private function notAllowedMessage(Order $order, string $action): string
    {
        if (in_array($order->status, Order::TERMINAL_STATUSES, true)) {
            return 'คำสั่งซื้อนี้จบไปแล้ว ทำรายการนี้ไม่ได้';
        }

        if (in_array($action, ['confirm', 'ship', 'request_rider'], true) && ! $order->isReadyForFulfilment()) {
            return 'คำสั่งซื้อนี้ยังไม่ได้ชำระเงิน รอลูกค้าชำระก่อน';
        }

        return match ($action) {
            'request_rider' => ! $order->isRiderDelivery()
                ? 'ลูกค้าเลือกส่งพัสดุ ไม่ได้เลือกส่งด้วยไรเดอร์'
                : ($order->activeRiderJob() ? 'เรียกไรเดอร์ไปแล้ว' : 'ร้านยังไม่ได้เปิดส่งด้วยไรเดอร์หรือยังไม่ได้ตั้งจุดรับของ'),
            'ship' => $order->isRiderDelivery() ? 'ออเดอร์นี้ส่งด้วยไรเดอร์ กรุณากดเรียกไรเดอร์' : 'สินค้าของร้านถูกจัดส่งไปแล้ว',
            'deliver' => 'ยังไม่มีสินค้าที่จัดส่งแล้ว',
            'cancel' => $order->isMultiSeller()
                ? 'คำสั่งซื้อนี้มีสินค้าหลายร้าน กรุณาติดต่อแอดมินเพื่อยกเลิก'
                : 'ยกเลิกไม่ได้ เพราะสินค้าถูกจัดส่งแล้ว',
            'confirm' => 'ยืนยันคำสั่งซื้อไปแล้ว',
            default => 'ทำรายการนี้ไม่ได้',
        };
    }

    /**
     * สรุปงานของร้าน (หน้าแรกฝั่งร้านในแอป)
     *
     * @return array<string, mixed>
     */
    public function summary(User $seller): array
    {
        $sellerId = (int) $seller->id;
        $base = fn () => Order::forSeller($sellerId);

        $toConfirm = $base()->whereIn('status', ['pending', 'paid'])
            ->where(fn ($q) => $q->where('payment_status', 'paid')->orWhere('payment_method', 'cod'))
            ->count();
        $toShip = $base()->where('status', 'processing')->count();
        $shipping = $base()->where('status', 'shipped')->count();
        $awaitingPayment = $base()->where('status', 'pending')->where('payment_status', 'pending')
            ->where('payment_method', '!=', 'cod')->count();

        $paidItems = fn () => OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid')->whereNotIn('status', ['cancelled', 'refunded']));

        $store = \App\Models\VendorStore::where('user_id', $sellerId)->orderBy('id')->first();

        return [
            'store' => $store ? ShopPresenter::storeBrief($store) + [
                'status' => $store->status,
                'is_active' => (bool) $store->is_active,
                'has_pickup_location' => $store->hasPickupLocation(),
                'rider_delivery_enabled' => (bool) $store->rider_delivery_enabled,
            ] : null,
            'counts' => [
                'to_confirm' => $toConfirm,
                'to_ship' => $toShip,
                'shipping' => $shipping,
                'awaiting_payment' => $awaitingPayment,
                'rider_active' => RiderJob::query()
                    ->where('source_type', (new Order)->getMorphClass())
                    ->whereIn('source_id', $base()->select('id'))
                    ->whereIn('status', RiderJob::ACTIVE_STATUSES)
                    ->count(),
            ],
            'sales' => [
                'today' => round((float) $paidItems()->whereDate('created_at', today())->sum('total'), 2),
                'month' => round((float) $paidItems()->where('created_at', '>=', now()->startOfMonth())->sum('total'), 2),
                'month_net_earning' => round((float) $paidItems()->where('created_at', '>=', now()->startOfMonth())->sum('seller_earning'), 2),
                'month_gp' => round((float) $paidItems()->where('created_at', '>=', now()->startOfMonth())->sum('commission_amount'), 2),
            ],
        ];
    }
}
