<?php

namespace App\Services\Shop;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Services\NotificationService;
use App\Support\Shop\PaymentMethod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * แจ้งเตือนของระบบร้านค้า (CC-08 / SHOP-12)
 *
 * - ผู้ขาย: มีออเดอร์ที่ต้องทำ (จ่ายแล้ว / COD) · ผู้ซื้อยกเลิก
 * - ผู้ซื้อ: สถานะออเดอร์เปลี่ยน (ร้านยืนยัน, จัดส่ง, ส่งถึง, ยกเลิก, คืนเงิน, ชำระเงินสำเร็จ)
 *
 * ทุกแจ้งเตือนเขียนกล่องแจ้งเตือนในระบบผ่าน NotificationService::create ซึ่งส่งต่อเข้าแอป + Expo push ให้เอง
 * (App\Jobs\SendNotificationPush) → ที่นี่ไม่ยิง push ซ้ำ ❌ ห้ามใช้ LINE push (โควต้า 300/เดือน)
 * ผู้ขายได้อีเมลเพิ่มผ่าน NewOrderNotification (เข้าคิว)
 *
 * ห้ามโยน exception ออกไป — แจ้งเตือนล้มต้องไม่ทำให้ออเดอร์/การจ่ายเงินล้ม
 */
class ShopOrderNotifier
{
    /** กันแจ้งผู้ขายซ้ำสำหรับออเดอร์+เหตุการณ์เดียวกัน (วินาที) */
    private const DEDUPE_TTL = 86400;

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * เรียกจาก Order::updated — ตัดสินใจเองว่าต้องแจ้งใคร
     */
    public function orderUpdated(Order $order): void
    {
        // ผู้เรียกจัดการแจ้งเตือนเอง (checkout, ไรเดอร์, ผู้ซื้อกดเอง)
        if ($order->suppressStatusNotification) {
            return;
        }

        // จ่ายเงินสำเร็จ (เช่น พร้อมเพย์ที่ระบบ SMS ยืนยัน) → ร้านมีงานต้องทำ + แจ้งผู้ซื้อ
        if ($order->wasChanged('payment_status') && $order->payment_status === 'paid' && ! $order->isCod()) {
            $this->notifySellers($order, 'paid');
            $this->notifyBuyer($order, 'paid');

            return;
        }

        if (! $order->wasChanged('status')) {
            return;
        }

        $event = match ($order->status) {
            'processing' => 'processing',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => null,
        };

        if ($event !== null) {
            $this->notifyBuyer($order, $event);
        }
    }

    /**
     * หลังสร้างออเดอร์ (commit แล้ว): แจ้งร้านเมื่อออเดอร์พร้อมให้ร้านทำ (จ่ายแล้วหรือ COD)
     * ผู้ซื้อได้กล่องแจ้งเตือนในแอป (ไม่ push เพราะกำลังอยู่ในแอป)
     */
    public function orderPlaced(Order $order): void
    {
        try {
            if ($order->isPaid() || $order->isCod()) {
                $this->notifySellers($order, 'new');
            }

            $buyer = $this->buyer($order);
            if ($buyer) {
                $pending = ! $order->isPaid() && ! $order->isCod();
                $this->notifications->create(
                    $buyer,
                    'shop_order',
                    $pending ? 'สร้างคำสั่งซื้อแล้ว รอชำระเงิน' : 'สั่งซื้อสำเร็จ',
                    $pending
                        ? "คำสั่งซื้อ #{$order->order_number} ยอด ".$this->money($order->total_amount).' บาท กรุณาชำระเงินภายในเวลาที่กำหนด'
                        : "คำสั่งซื้อ #{$order->order_number} ยอด ".$this->money($order->total_amount).' บาท ร้านได้รับออเดอร์แล้ว',
                    $this->buyerData($order, 'placed'),
                    url('/orders/'.$order->id),
                    'ดูคำสั่งซื้อ',
                    'low'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('ShopOrderNotifier: orderPlaced failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * ผู้ซื้อยกเลิกออเดอร์ที่ร้านต้องทำอยู่ → แจ้งร้านให้หยุดเตรียม/ส่ง
     */
    public function buyerCancelled(Order $order): void
    {
        $this->notifySellers($order, 'cancelled_by_buyer');
    }

    /**
     * แจ้งผู้ขายทุกคนในออเดอร์ (ครั้งเดียวต่อเหตุการณ์)
     *
     * @param  string  $kind  new | paid | cancelled_by_buyer
     */
    public function notifySellers(Order $order, string $kind): void
    {
        try {
            // new กับ paid คือเหตุการณ์เดียวกันสำหรับร้าน ("มีออเดอร์ให้ทำ") → ใช้คีย์เดียวกัน
            $dedupeKind = in_array($kind, ['new', 'paid'], true) ? 'actionable' : $kind;
            if (! Cache::add("shop:order:{$order->id}:sellers:{$dedupeKind}", 1, self::DEDUPE_TTL)) {
                return;
            }

            $sellerIds = $order->sellerIds();
            if ($sellerIds === []) {
                return;
            }

            [$title, $message] = match ($kind) {
                'cancelled_by_buyer' => [
                    'ลูกค้ายกเลิกคำสั่งซื้อ',
                    "คำสั่งซื้อ #{$order->order_number} ถูกลูกค้ายกเลิกแล้ว กรุณาหยุดเตรียม/จัดส่งสินค้า",
                ],
                default => [
                    'มีคำสั่งซื้อใหม่',
                    "คำสั่งซื้อ #{$order->order_number} ยอด ".$this->money($order->total_amount).' บาท'
                        .' · '.PaymentMethod::labelTh($order->payment_method)
                        .($order->isRiderDelivery() ? ' · ส่งด้วยไรเดอร์' : '')
                        .' กรุณายืนยันและเตรียมสินค้า',
                ],
            };

            foreach (User::whereIn('id', $sellerIds)->get() as $seller) {
                try {
                    $this->notifications->create(
                        $seller,
                        'shop_order',
                        $title,
                        $message,
                        [
                            'type' => 'shop_order',
                            'role' => 'seller',
                            'event' => $kind,
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                        ],
                        url('/seller/orders/'.$order->id),
                        'ดูคำสั่งซื้อ',
                        'high'
                    );

                    // อีเมลแจ้งร้าน (เข้าคิว) เฉพาะออเดอร์ใหม่
                    if ($kind !== 'cancelled_by_buyer' && $this->hasDeliverableEmail($seller)) {
                        $seller->notify(new NewOrderNotification($order, 'seller'));
                    }
                } catch (\Throwable $e) {
                    Log::warning('ShopOrderNotifier: notify seller failed', [
                        'order_id' => $order->id,
                        'seller_id' => $seller->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ShopOrderNotifier: notifySellers failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * แจ้งผู้ซื้อตามเหตุการณ์
     *
     * @param  string  $event  paid | processing | shipped | delivered | cancelled | refunded
     */
    public function notifyBuyer(Order $order, string $event): void
    {
        try {
            $buyer = $this->buyer($order);
            if (! $buyer) {
                return;
            }

            $no = $order->order_number;

            [$title, $message] = match ($event) {
                'paid' => ['ชำระเงินสำเร็จ', "เราได้รับชำระเงินคำสั่งซื้อ #{$no} แล้ว ร้านจะเตรียมสินค้าให้เร็วที่สุด"],
                'processing' => ['ร้านยืนยันคำสั่งซื้อแล้ว', "คำสั่งซื้อ #{$no} กำลังเตรียมสินค้า"],
                'shipped' => ['จัดส่งสินค้าแล้ว', $order->tracking_number
                    ? "คำสั่งซื้อ #{$no} จัดส่งแล้ว เลขพัสดุ {$order->tracking_number}"
                    : "คำสั่งซื้อ #{$no} ออกจากร้านแล้ว กำลังนำส่ง"],
                'delivered' => ['สินค้าส่งถึงแล้ว', "คำสั่งซื้อ #{$no} ส่งถึงแล้ว กดยืนยันรับสินค้าและรีวิวได้ในแอป"],
                'cancelled' => ['คำสั่งซื้อถูกยกเลิก', "คำสั่งซื้อ #{$no} ถูกยกเลิก".($order->cancellation_reason ? ": {$order->cancellation_reason}" : '')],
                'refunded' => ['คืนเงินแล้ว', "คำสั่งซื้อ #{$no} ถูกยกเลิกและคืนเงิน ".$this->money($order->total_amount).' บาทเข้ากระเป๋าเงินแล้ว'],
                default => [null, null],
            };

            if ($title === null) {
                return;
            }

            $this->notifications->create(
                $buyer,
                'shop_order',
                $title,
                $message,
                $this->buyerData($order, $event),
                url('/orders/'.$order->id),
                'ดูคำสั่งซื้อ',
                'normal'
            );
        } catch (\Throwable $e) {
            Log::warning('ShopOrderNotifier: notifyBuyer failed', [
                'order_id' => $order->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buyerData(Order $order, string $event): array
    {
        return [
            'type' => 'shop_order',
            'role' => 'buyer',
            'event' => $event,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
        ];
    }

    private function buyer(Order $order): ?User
    {
        $buyer = $order->relationLoaded('user') ? $order->user : User::withTrashed()->find($order->user_id);

        // บัญชีที่ลบไปแล้วไม่ต้องแจ้ง
        if (! $buyer || (method_exists($buyer, 'trashed') && $buyer->trashed())) {
            return null;
        }

        return $buyer;
    }

    private function hasDeliverableEmail(User $user): bool
    {
        $email = (string) ($user->email ?? '');

        return $email !== ''
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && ! str_ends_with(strtolower($email), '@invalid')
            && ! str_ends_with(strtolower($email), '.invalid');
    }

    private function money($amount): string
    {
        return number_format((float) $amount, 2);
    }
}
