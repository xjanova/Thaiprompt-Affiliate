<?php

namespace App\Services\Shop;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * แจ้งเตือนแชทออเดอร์ร้านค้า (order_messages) — เรียกจาก OrderMessage::send ทุกครั้งที่มีข้อความใหม่
 *
 * - ร้าน/แอดมินส่ง → แจ้งผู้ซื้อ   type=order_message        (role=buyer)  → แอปเปิด /order/{id}?tab=chat
 * - ลูกค้าส่ง     → แจ้งผู้ขายทุกคน type=seller_order_message (role=seller) → แอปเปิด /merchant/order/{id}?tab=chat
 *   ⚠️ ฝั่งร้านต้องเป็น type แยก: แอปรุ่นที่ติดตั้งอยู่ (ก่อน 2026-09-26) ไม่อ่าน role แล้วพา order_message
 *      ไปหน้าผู้ซื้อซึ่ง 404 สำหรับร้าน — type ใหม่ทำให้แอปรุ่นเก่าตกไปหน้าแจ้งเตือนแทน
 *
 * ช่องทาง: NotificationService::create = กล่องแจ้งเตือนเว็บ + กล่องแจ้งเตือนแอป + Expo push (ผ่าน SendNotificationPush)
 * ❌ ห้าม LINE push เด็ดขาด (โควต้า 300 ครั้ง/เดือน — แชทไม่ใช่ "ของสำคัญ" ตาม .claude/LINE_MESSAGING_RULES.md)
 *
 * กันเด้งรัว: ผู้รับคนเดียวกันในออเดอร์เดียวกันได้แจ้งเตือนแชทไม่เกิน 1 ครั้งต่อ THROTTLE_SECONDS
 * (ข้อความถัดๆ ไปยังเห็นได้จากป้ายยังไม่อ่านในแอป)
 * กำลังเปิดแชทอยู่: หน้าแชทในแอปดึงข้อความทุก 10 วินาที → markViewing() จำไว้ VIEWING_SECONDS
 *   ผู้รับที่กำลังดูแชทนั้นอยู่ไม่ต้องได้ push (แอปเด้งกล่องแจ้งเตือนทับหน้าแชทเอง) — ออกจากหน้า/พับแอปแล้วได้ push ตามปกติ
 *
 * ห้ามโยน exception ออกไป — แจ้งเตือนล้มต้องไม่ทำให้การส่งข้อความล้ม
 */
class OrderChatNotifier
{
    /** notifications.type (varchar) + data.type ที่แอปใช้เลือกหน้า — แจ้งผู้ซื้อ (ชื่อเดิม แอปรุ่นเก่าพาไปหน้าผู้ซื้อถูกต้อง) */
    public const TYPE = 'order_message';

    /** แจ้งผู้ขาย — type ใหม่ แอปรุ่นเก่าไม่รู้จัก = ไปหน้าแจ้งเตือน (ไม่พาไปหน้าผู้ซื้อที่ 404) */
    public const SELLER_TYPE = 'seller_order_message';

    /** ช่องแจ้งเตือน Android ที่แอปสร้างไว้ (services/notifications.ts) */
    public const CHANNEL = 'messages';

    /** หน้าต่างกันแจ้งเตือนซ้ำต่อ (ออเดอร์, ผู้รับ) — วินาที */
    public const THROTTLE_SECONDS = 60;

    /** ถือว่ายังเปิดหน้าแชทอยู่นานเท่านี้หลังดึงข้อความครั้งล่าสุด (แอปดึงทุก 10 วินาที) — วินาที */
    public const VIEWING_SECONDS = 25;

    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * ผู้ใช้กำลังเปิดหน้าแชทของออเดอร์นี้ (เรียกจาก API ดึงข้อความทั้งฝั่งผู้ซื้อและฝั่งร้าน)
     */
    public static function markViewing(int $orderId, int $userId): void
    {
        try {
            Cache::put(self::viewingKey($orderId, $userId), 1, self::VIEWING_SECONDS);
        } catch (\Throwable) {
            // cache ล่ม = แค่ได้ push เพิ่ม ไม่เป็นไร
        }
    }

    /**
     * มีข้อความใหม่ในออเดอร์ → แจ้งอีกฝั่ง
     */
    public function messageSent(Order $order, OrderMessage $message): void
    {
        if ($message->is_system_message) {
            return;
        }

        try {
            match ((string) $message->sender_type) {
                'customer' => $this->notifySellers($order, $message),
                'seller', 'admin' => $this->notifyBuyer($order, $message),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning('OrderChatNotifier: notify failed', [
                'order_id' => $order->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ร้าน/แอดมินตอบ → แจ้งผู้ซื้อ
     */
    private function notifyBuyer(Order $order, OrderMessage $message): void
    {
        if (! $order->user_id || (int) $order->user_id === (int) $message->sender_id) {
            return;
        }

        $buyer = User::withTrashed()->find($order->user_id);
        if (! $this->canReceive($buyer) || ! $this->claimSlot($order, $buyer)) {
            return;
        }

        $from = $message->sender_type === 'admin'
            ? 'ทีมงาน '.config('app.name')
            : $this->storeNameFor($order, (int) $message->sender_id);

        $this->send(
            $buyer,
            "ข้อความใหม่จาก{$from}",
            $this->preview($message),
            self::TYPE,
            $this->payload($order, $message, self::TYPE, 'buyer'),
            url('/orders/'.$order->id)
        );
    }

    /**
     * ลูกค้าส่ง → แจ้งผู้ขายทุกคนที่มีสินค้าในออเดอร์ (ออเดอร์ร้านเดียว = ร้านเดียว)
     */
    private function notifySellers(Order $order, OrderMessage $message): void
    {
        $sellerIds = array_values(array_filter(
            $order->sellerIds(),
            fn (int $id) => $id !== (int) $message->sender_id
        ));
        if ($sellerIds === []) {
            return;
        }

        $body = "คำสั่งซื้อ #{$order->order_number}: ".$this->preview($message);

        foreach (User::whereIn('id', $sellerIds)->get() as $seller) {
            if (! $this->canReceive($seller) || ! $this->claimSlot($order, $seller)) {
                continue;
            }

            $this->send(
                $seller,
                'ลูกค้าส่งข้อความถึงร้าน',
                $body,
                self::SELLER_TYPE,
                $this->payload($order, $message, self::SELLER_TYPE, 'seller'),
                url('/seller/orders/'.$order->id)
            );
        }
    }

    private function send(User $user, string $title, string $body, string $type, array $data, string $url): void
    {
        try {
            $this->notifications->create($user, $type, $title, $body, $data, $url, 'เปิดแชท', 'normal');
        } catch (\Throwable $e) {
            Log::warning('OrderChatNotifier: create notification failed', [
                'user_id' => $user->id,
                'order_id' => $data['order_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * payload ของ push — แอปเลือกหน้าจาก type (+ role ของผู้รับ) ไม่เดาจากบริบท
     *
     * @return array<string, mixed>
     */
    private function payload(Order $order, OrderMessage $message, string $type, string $role): array
    {
        return [
            'type' => $type,
            'role' => $role,
            'order_id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'message_id' => (int) $message->id,
            'channel' => self::CHANNEL,
        ];
    }

    /**
     * จองสิทธิ์แจ้งเตือน (ออเดอร์, ผู้รับ) — false = กำลังเปิดแชทนี้อยู่ หรือเพิ่งแจ้งไปเมื่อครู่
     */
    private function claimSlot(Order $order, User $user): bool
    {
        if (Cache::has(self::viewingKey((int) $order->id, (int) $user->id))) {
            return false;
        }

        return Cache::add("shop:order-chat:{$order->id}:notify:{$user->id}", 1, self::THROTTLE_SECONDS);
    }

    private static function viewingKey(int $orderId, int $userId): string
    {
        return "shop:order-chat:{$orderId}:viewing:{$userId}";
    }

    private function canReceive(?User $user): bool
    {
        return $user !== null && ! (method_exists($user, 'trashed') && $user->trashed());
    }

    /**
     * ชื่อร้านของผู้ส่ง (ออเดอร์เก่าหลายร้านไม่มี store_id เดียว → ใช้ร้านของผู้ส่งเอง)
     */
    private function storeNameFor(Order $order, int $sellerId): string
    {
        $name = VendorStore::where('user_id', $sellerId)->orderBy('id')->value('store_name');
        if (! $name && $order->store_id) {
            $name = VendorStore::whereKey($order->store_id)->value('store_name');
        }

        $name = trim((string) $name);

        return $name !== '' ? 'ร้าน '.Str::limit($name, 40) : 'ร้านค้า';
    }

    private function preview(OrderMessage $message): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $message->message) ?? '');
        if ($text !== '') {
            return Str::limit($text, 100);
        }

        return $message->attachment_type === 'image' ? 'ส่งรูปภาพ' : 'ส่งไฟล์แนบ';
    }
}
