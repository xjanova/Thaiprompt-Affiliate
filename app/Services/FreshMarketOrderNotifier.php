<?php

namespace App\Services;

use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * แจ้งเตือนออเดอร์ตลาดสด (ผู้ขาย / ผู้ซื้อ / แอดมิน)
 *
 * ช่องทาง: แจ้งเตือนในระบบ (notifications) → NotificationService ส่งต่อเป็น Expo push เข้าแอปมือถือให้เอง
 * 🚫 ห้าม LINE push (โควต้า 300/เดือน) — LINE ตอบได้เฉพาะ reply ภายใน event ที่ลูกค้าทักมาเท่านั้น
 *    (FreshMarketChannelManager เป็นคนตอบ reply เอง)
 *
 * ทุกการส่งรอให้ transaction commit ก่อน (DB::afterCommit) — ถ้าธุรกรรมถูก rollback จะไม่มีแจ้งเตือนหลุดออกไป
 * และทุกจุดห้าม throw: แจ้งเตือนพังต้องไม่ทำให้ธุรกรรมเงิน/สถานะล้ม
 */
class FreshMarketOrderNotifier
{
    /** ประเภท notification ของตลาดสด */
    public const TYPE = 'fresh_market_order';

    /**
     * ร้านได้รับออเดอร์ใหม่
     */
    public function sellerNewOrder(FreshMarketOrder $order): void
    {
        $order->loadMissing(['seller.user', 'listing']);
        $sellerUser = $order->seller?->user;

        if (! $sellerUser) {
            return;
        }

        $item = $order->riderItemsSummary();
        $payment = $order->payment_method === 'cod' ? 'เก็บเงินปลายทาง' : 'ชำระผ่าน Wallet แล้ว';
        $delivery = $order->delivery_type === 'rider' ? 'ส่งด้วยไรเดอร์' : 'ลูกค้ามารับเอง';

        $this->send(
            $sellerUser,
            'มีออเดอร์ใหม่ #'.$order->order_number,
            "{$item} • ฿".number_format((float) $order->total_amount, 2)." • {$payment} • {$delivery}\nกรุณากดรับออเดอร์ภายใน "
                .(int) \App\Models\Setting::get('fresh_market.pending_expiry_minutes', 30).' นาที',
            $this->sellerUrl($order),
            $this->payload($order, 'seller', 'new_order'),
            'high'
        );
    }

    /**
     * สถานะออเดอร์เปลี่ยนจากการกระทำของคน/ระบบ
     *
     * @param  string  $action  accept|prepare|ready|handover|confirm|complete|cancel|refund
     * @param  string  $byRole  buyer|seller|admin|system
     */
    public function statusChanged(FreshMarketOrder $order, string $action, string $byRole, array $extra = []): void
    {
        $order->loadMissing(['seller.user', 'buyer', 'listing']);
        $buyer = $order->buyer;
        $sellerUser = $order->seller?->user;
        $shop = $order->seller?->shop_name ?? 'ร้านค้า';
        $num = $order->order_number;

        switch ($action) {
            case 'accept':
                $this->toBuyer($order, $buyer, "ร้านรับออเดอร์ #{$num} แล้ว", "{$shop} กำลังเตรียมสินค้าให้คุณ");
                break;

            case 'prepare':
                $this->toBuyer($order, $buyer, "ออเดอร์ #{$num} กำลังจัดเตรียม", "{$shop} กำลังจัดเตรียมสินค้า");
                break;

            case 'ready':
                $msg = $order->delivery_type === 'rider'
                    ? 'สินค้าพร้อมแล้ว กำลังหาไรเดอร์ไปรับสินค้า'
                    : "สินค้าพร้อมให้มารับที่ {$shop} แล้ว";
                $this->toBuyer($order, $buyer, "ออเดอร์ #{$num} พร้อมแล้ว", $msg);
                break;

            case 'handover':
                $this->toBuyer($order, $buyer, "ส่งมอบสินค้า #{$num} แล้ว", 'ได้รับสินค้าครบแล้ว กรุณากดยืนยันรับสินค้าในหน้าออเดอร์');
                break;

            case 'confirm':
            case 'complete':
                $net = number_format((float) $order->seller_earning, 2);
                $sellerMsg = $order->payment_method === 'cod' && $order->escrow_status === null
                    ? 'ออเดอร์เสร็จสมบูรณ์ (เก็บเงินปลายทาง) ระบบหักค่า GP ตามอัตราที่กำหนด'
                    : "ออเดอร์เสร็จสมบูรณ์ ระบบโอนเงิน ฿{$net} (หลังหัก GP) เข้า Wallet ของคุณแล้ว";
                $this->toSeller($order, $sellerUser, "ออเดอร์ #{$num} เสร็จสมบูรณ์", $sellerMsg);

                if ($action === 'complete') {
                    $this->toBuyer($order, $buyer, "ออเดอร์ #{$num} เสร็จสมบูรณ์", 'ระบบปิดออเดอร์ให้อัตโนมัติ ขอบคุณที่อุดหนุนค่ะ');
                }

                if ((float) ($extra['cashback_paid'] ?? 0) > 0) {
                    $this->toBuyer(
                        $order,
                        $buyer,
                        'ได้รับแคชแบ็ค ฿'.number_format((float) $extra['cashback_paid'], 2),
                        "แคชแบ็คจากออเดอร์ #{$num} เข้า Wallet แล้ว"
                    );
                }
                break;

            case 'cancel':
                $reason = $order->cancel_reason ? " (เหตุผล: {$order->cancel_reason})" : '';
                $refund = (float) ($extra['refunded'] ?? 0);
                $refundText = $refund > 0 ? "\nคืนเงิน ฿".number_format($refund, 2).' เข้า Wallet แล้ว' : '';

                if ($byRole !== 'buyer') {
                    $title = $byRole === 'system' ? "ออเดอร์ #{$num} ถูกยกเลิกอัตโนมัติ" : "ออเดอร์ #{$num} ถูกยกเลิก";
                    $this->toBuyer($order, $buyer, $title, 'ออเดอร์ถูกยกเลิก'.$reason.$refundText);
                } elseif ($refund > 0) {
                    $this->toBuyer($order, $buyer, "ยกเลิกออเดอร์ #{$num} แล้ว", 'คืนเงิน ฿'.number_format($refund, 2).' เข้า Wallet แล้ว');
                }

                if ($byRole !== 'seller') {
                    $who = match ($byRole) {
                        'buyer' => 'ลูกค้ายกเลิกออเดอร์',
                        'system' => 'ออเดอร์หมดเวลารอร้านยืนยัน ระบบยกเลิกให้อัตโนมัติ',
                        default => 'แอดมินยกเลิกออเดอร์',
                    };
                    $this->toSeller($order, $sellerUser, "ออเดอร์ #{$num} ถูกยกเลิก", $who.$reason);
                }
                break;
        }
    }

    /**
     * เหตุการณ์จากงานไรเดอร์ (เรียกจาก FreshMarketOrder::onRiderJobStatusChanged)
     *
     * @param  string  $event  rider_accepted|rider_released|delivering|delivered|delivery_failed|rider_job_cancelled
     */
    public function riderEvent(FreshMarketOrder $order, string $event): void
    {
        $order->loadMissing(['seller.user', 'buyer']);
        $buyer = $order->buyer;
        $sellerUser = $order->seller?->user;
        $num = $order->order_number;

        switch ($event) {
            case 'rider_accepted':
                $this->toBuyer($order, $buyer, "ไรเดอร์รับงาน #{$num} แล้ว", 'ไรเดอร์กำลังไปรับสินค้าที่ร้าน');
                $this->toSeller($order, $sellerUser, "ไรเดอร์กำลังมารับสินค้า #{$num}", 'กรุณาเตรียมสินค้าให้พร้อมส่งมอบไรเดอร์');
                break;

            case 'rider_released':
                $this->toSeller($order, $sellerUser, "ไรเดอร์คืนงาน #{$num}", 'ระบบกำลังหาไรเดอร์คนใหม่ให้อัตโนมัติ');
                break;

            case 'delivering':
                $this->toBuyer($order, $buyer, "ออเดอร์ #{$num} กำลังจัดส่ง", 'ไรเดอร์รับสินค้าแล้ว กำลังเดินทางไปหาคุณ');
                break;

            case 'delivered':
                $this->toBuyer($order, $buyer, "ส่งสินค้า #{$num} ถึงแล้ว", 'กรุณากดยืนยันรับสินค้าและให้คะแนนร้าน/ไรเดอร์');
                $this->toSeller($order, $sellerUser, "ส่งสินค้า #{$num} ถึงลูกค้าแล้ว", 'รอลูกค้ายืนยันรับสินค้า ระบบจะโอนเงินให้หลังยืนยัน');
                break;

            case 'delivery_failed':
                $this->toBuyer($order, $buyer, "จัดส่ง #{$num} ไม่สำเร็จ", 'ทีมงานจะติดต่อกลับเพื่อจัดการออเดอร์นี้');
                $this->toSeller($order, $sellerUser, "จัดส่ง #{$num} ไม่สำเร็จ", 'ไรเดอร์แจ้งส่งไม่สำเร็จ ทีมงานกำลังตรวจสอบ');
                $this->toAdmins($order, "ตลาดสด: จัดส่ง #{$num} ไม่สำเร็จ", 'ต้องตัดสินใจ: เรียกไรเดอร์ใหม่ หรือยกเลิกพร้อมคืนเงิน');
                break;

            case 'rider_job_cancelled':
                $this->toSeller($order, $sellerUser, "งานไรเดอร์ #{$num} ถูกยกเลิก", 'ทีมงานจะเรียกไรเดอร์ให้ใหม่');
                break;
        }
    }

    /**
     * เรียกไรเดอร์ไม่สำเร็จ → แจ้งร้าน + แอดมิน (ไม่ให้ออเดอร์ค้างเงียบ)
     */
    public function riderDispatchFailed(FreshMarketOrder $order, string $reason): void
    {
        $order->loadMissing(['seller.user']);
        $num = $order->order_number;

        $this->toSeller($order, $order->seller?->user, "เรียกไรเดอร์ #{$num} ไม่สำเร็จ", $reason.' ทีมงานได้รับแจ้งแล้ว');
        $this->toAdmins($order, "ตลาดสด: เรียกไรเดอร์ #{$num} ไม่สำเร็จ", $reason);
    }

    /**
     * แจ้งร้านเรื่องสถานะร้าน (ยืนยัน/ระงับ/เปิดใช้งาน)
     */
    public function sellerAccountEvent(FreshMarketSeller $seller, string $title, string $message): void
    {
        $user = $seller->user;

        if (! $user) {
            return;
        }

        $this->send($user, $title, $message, $this->safeRoute('taladsod.seller.dashboard'), [
            'type' => self::TYPE,
            'event' => 'seller_account',
            'seller_id' => (int) $seller->id,
            'channel' => 'orders',
        ]);
    }

    // ===== ภายใน =====

    protected function toBuyer(FreshMarketOrder $order, ?User $buyer, string $title, string $message): void
    {
        if ($buyer) {
            $this->send($buyer, $title, $message, $this->buyerUrl($order), $this->payload($order, 'buyer', $order->order_status));
        }
    }

    protected function toSeller(FreshMarketOrder $order, ?User $seller, string $title, string $message): void
    {
        if ($seller) {
            $this->send($seller, $title, $message, $this->sellerUrl($order), $this->payload($order, 'seller', $order->order_status));
        }
    }

    protected function toAdmins(FreshMarketOrder $order, string $title, string $message): void
    {
        try {
            $admins = User::whereIn('role', ['admin', 'super_admin'])->limit(20)->get();
        } catch (\Throwable $e) {
            Log::warning('FreshMarketNotifier: โหลดรายชื่อแอดมินล้มเหลว', ['error' => $e->getMessage()]);

            return;
        }

        foreach ($admins as $admin) {
            $this->send(
                $admin,
                $title,
                $message,
                $this->safeRoute('admin.fresh-market.orders.show', $order),
                $this->payload($order, 'admin', $order->order_status),
                'high'
            );
        }
    }

    /**
     * ข้อมูลแนบ push (แอปใช้เปิดหน้าออเดอร์)
     */
    protected function payload(FreshMarketOrder $order, string $role, ?string $event): array
    {
        return [
            'type' => self::TYPE,
            'event' => $event,
            'role' => $role,
            'order_id' => (int) $order->id,
            'order_number' => $order->order_number,
            'status' => $order->order_status,
            'channel' => 'orders',
        ];
    }

    protected function buyerUrl(FreshMarketOrder $order): ?string
    {
        return $this->safeRoute('taladsod.orders.show', $order);
    }

    protected function sellerUrl(FreshMarketOrder $order): ?string
    {
        return $this->safeRoute('taladsod.seller.orders.show', $order);
    }

    protected function safeRoute(string $name, mixed $params = []): ?string
    {
        try {
            return route($name, $params);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * ส่งแจ้งเตือนในระบบหลัง transaction commit
     *
     * Expo push เข้ามือถือ: NotificationService::create ส่งต่อให้ App\Jobs\SendNotificationPush เอง
     * (priority normal ขึ้นไป, กันส่งซ้ำด้วย push_sent) — ที่นี่จึงไม่ยิง ExpoPushService ซ้ำ
     * data['channel'] = 'orders' ใช้เลือกช่องแจ้งเตือน Android และให้แอปเปิดหน้าออเดอร์จาก order_id
     */
    protected function send(User $user, string $title, string $message, ?string $url, array $data, string $priority = 'normal'): void
    {
        $callback = function () use ($user, $title, $message, $url, $data, $priority) {
            try {
                app(NotificationService::class)->create(
                    $user,
                    self::TYPE,
                    $title,
                    $message,
                    $data,
                    $url,
                    'ดูออเดอร์',
                    $priority,
                    $priority === 'high',
                    false,
                    '🥬',
                    'green'
                );
            } catch (\Throwable $e) {
                Log::warning('FreshMarketNotifier: สร้างแจ้งเตือนล้มเหลว', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        };

        try {
            DB::afterCommit($callback);
        } catch (\Throwable $e) {
            // ไม่มี transaction manager (เช่น บาง context ของ console) → ส่งทันที
            $callback();
        }
    }
}
