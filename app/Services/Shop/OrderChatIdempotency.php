<?php

namespace App\Services\Shop;

use App\Models\OrderMessage;
use Illuminate\Support\Facades\Cache;

/**
 * กันส่งข้อความแชทออเดอร์ซ้ำ (client_message_id) — ใช้ทั้งฝั่งผู้ซื้อและฝั่งร้าน
 *
 * แอปส่งรหัสข้อความของตัวเองมาด้วย → เน็ตหลุดหลังข้อความถึง server แล้วแอปส่งใหม่ด้วยรหัสเดิม
 * = ได้ข้อความเดิมกลับไป ไม่สร้างซ้ำ
 *
 * ขั้นตอน: claim() ก่อนสร้าง → complete() หลังสร้างสำเร็จ → release() ถ้าสร้างไม่สำเร็จ (ให้ส่งใหม่ได้)
 */
final class OrderChatIdempotency
{
    /** อายุคีย์ — วินาที */
    public const TTL = 600;

    /** กฎตรวจ client_message_id (ใช้ใน Validator ของทั้งสองฝั่ง) */
    public const RULES = ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'];

    /**
     * @param  string  $side  customer | seller
     * @return string|null null = ไม่มีรหัส (ไม่กันซ้ำ)
     */
    public static function key(int $orderId, string $side, int $userId, mixed $clientId): ?string
    {
        if (! is_string($clientId) || $clientId === '') {
            return null;
        }

        return "shop:order-chat:{$orderId}:{$side}:{$userId}:{$clientId}";
    }

    /**
     * จองรหัส
     *
     * @return array{0: string, 1: OrderMessage|null} ['new', null] = สร้างได้ · ['done', ข้อความเดิม] · ['in_flight', null] = กำลังส่งอยู่
     */
    public static function claim(?string $key, int $orderId): array
    {
        if ($key === null || Cache::add($key, 0, self::TTL)) {
            return ['new', null];
        }

        $existingId = (int) Cache::get($key);
        $existing = $existingId > 0
            ? OrderMessage::where('order_id', $orderId)->with('sender')->find($existingId)
            : null;

        return $existing ? ['done', $existing] : ['in_flight', null];
    }

    public static function complete(?string $key, int $messageId): void
    {
        if ($key !== null) {
            Cache::put($key, $messageId, self::TTL);
        }
    }

    public static function release(?string $key): void
    {
        if ($key !== null) {
            Cache::forget($key);
        }
    }
}
