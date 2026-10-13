<?php

namespace App\Support\Rider;

use App\Http\Middleware\EnsureProfilePhoto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * เลข build ของแอปที่ใช้ทำรายการ (header X-App-Build) — ไรเดอร์รอบ 2, รอบแก้หลังรีวิว
 *
 * - บันทึก build ที่ผู้ซื้อใช้สั่งลงออเดอร์ (client_app_build) ไว้ดูย้อนหลัง
 * - อ่าน build ของคำขอให้ด่าน API ไรเดอร์ (App\Http\Middleware\EnsureRiderAppBuild — รับงานต้องใช้แอปรุ่นล่าสุด)
 *
 * (2026-10-13) เลิกใช้ตัดสิน "ต้องสแกนส่งมอบ" แล้ว — งานที่มีออเดอร์ต้นทางต้องสแกนส่งมอบเสมอเมื่อเปิด rider.handover_enabled
 * (ดู RiderJob::handoverRequiredOnAccept) เพราะไรเดอร์รับงานได้ผ่านแอปเท่านั้น
 *
 * @example ClientAppBuild::fromRequest($request)  // 44 | null
 * @example ClientAppBuild::stamp($order, $input['client_app_build'] ?? null)
 */
final class ClientAppBuild
{
    /** build แรกของแอปที่มีหน้าส่งมอบ (สแกน QR ใส่กัน / รหัส 6 หลัก / รูป 2 รอบ) */
    public const HANDOVER_MIN_BUILD = 43;

    /** คอลัมน์ client_app_build เป็น unsigned smallint */
    private const MAX_STORABLE = 65535;

    /**
     * เลข build จาก header X-App-Build (ไม่ส่ง/ไม่ใช่ตัวเลข/0 = null)
     */
    public static function fromRequest(?Request $request): ?int
    {
        if (! $request) {
            return null;
        }

        return self::normalize(EnsureProfilePhoto::appBuild($request));
    }

    /**
     * แปลงค่าที่ส่งต่อมาใน input ของ service ให้เป็นเลข build ที่เก็บได้ (ค่าแปลก = null)
     */
    public static function normalize(mixed $value): ?int
    {
        if (is_string($value) && preg_match('/^\d{1,9}$/', trim($value))) {
            $value = (int) trim($value);
        }

        if (! is_int($value) || $value <= 0) {
            return null;
        }

        return min($value, self::MAX_STORABLE);
    }

    /**
     * แอป build นี้มีหน้าส่งมอบหรือไม่
     */
    public static function supportsHandover(mixed $build): bool
    {
        $build = self::normalize($build);

        return $build !== null && $build >= self::HANDOVER_MIN_BUILD;
    }

    /**
     * บันทึกเลข build ลงออเดอร์ที่เพิ่งสร้าง (ไม่มี build = ไม่แตะ · ไม่ยิง observer)
     */
    public static function stamp(Model $order, mixed $build): void
    {
        $build = self::normalize($build);

        if ($build === null || ! $order->exists) {
            return;
        }

        $order->forceFill(['client_app_build' => $build])->saveQuietly();
    }
}
