<?php

namespace App\Support\Seller;

/**
 * ตัวช่วยแสดงผลของหน้าผู้ขายธีม V4 (สีสถานะ / ป้ายภาษาไทย)
 *
 * สีทั้งหมดเป็น CSS variable พร้อมค่า fallback — ธีม V4 เปลี่ยนสีได้จากที่เดียว
 * ห้ามใส่สี hex ตรงๆ ในหน้า view ให้เรียกผ่านคลาสนี้แทน
 */
final class SellerUi
{
    public const OK = 'var(--tp-ok, #5aa07e)';

    public const BAD = 'var(--tp-bad, #d9534f)';

    public const WARN = 'var(--tp-warn, #e0a52e)';

    public const INFO = 'var(--tp-info, #5689b8)';

    public const VIOLET = 'var(--tp-violet, #8b6bb8)';

    public const MUTED = 'var(--ink2)';

    public const GOLD = 'var(--deep1)';

    /** สีเริ่มต้นของหน้าร้าน (ค่าในช่อง input type=color ต้องเป็น hex จริง — เก็บไว้ที่นี่ที่เดียว) */
    public const STORE_PRIMARY_DEFAULT = '#6366f1';

    public const STORE_SECONDARY_DEFAULT = '#8b5cf6';

    /**
     * ค่าสีหน้าร้านที่ปลอดภัยสำหรับ input type=color (#rrggbb เท่านั้น ไม่งั้นใช้ค่าเริ่มต้น)
     */
    public static function colorOr(?string $value, string $default): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : $default;
    }

    /**
     * สีตามสถานะออเดอร์/รายการสินค้า
     */
    public static function orderStatusColor(?string $status): string
    {
        return match ($status) {
            'pending' => self::WARN,
            'paid' => self::INFO,
            'processing' => self::INFO,
            'shipped' => self::VIOLET,
            'delivered', 'completed' => self::OK,
            'cancelled', 'refunded' => self::BAD,
            default => self::MUTED,
        };
    }

    /**
     * ป้ายสถานะของรายการสินค้าในออเดอร์ (order_items.status)
     */
    public static function itemStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'รอดำเนินการ',
            'processing' => 'กำลังเตรียมสินค้า',
            'shipped' => 'จัดส่งแล้ว',
            'delivered' => 'ส่งถึงแล้ว',
            'completed' => 'สำเร็จ',
            'cancelled' => 'ยกเลิก',
            'refunded' => 'คืนเงิน',
            default => (string) $status,
        };
    }

    /**
     * สีตามสถานะรายได้ (earnings_ledger.status)
     */
    public static function earningStatusColor(?string $status): string
    {
        return match ($status) {
            'pending' => self::WARN,
            'available', 'processing' => self::INFO,
            'paid' => self::OK,
            'held' => self::VIOLET,
            'cancelled' => self::BAD,
            default => self::MUTED,
        };
    }

    /**
     * สีตามสถานะงานไรเดอร์ (rider_jobs.status)
     */
    public static function riderStatusColor(?string $status): string
    {
        return match ($status) {
            'not_requested', 'pending' => self::WARN,
            'accepted', 'picking_up', 'picked_up', 'delivering' => self::INFO,
            'delivered', 'completed' => self::OK,
            'cancelled', 'failed' => self::BAD,
            default => self::MUTED,
        };
    }

    /**
     * สไตล์ inline ของ pill สีตามสถานะ (ข้อความสี + พื้นอ่อน)
     */
    public static function pill(string $color): string
    {
        return 'color:'.$color.'; background:color-mix(in srgb, '.$color.' 16%, transparent);';
    }
}
