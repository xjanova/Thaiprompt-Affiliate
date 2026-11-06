<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;

/**
 * 🛡️ (2026-11-03 รีวิว) เกณฑ์กลาง "บัญชีหลังบ้าน" สำหรับทางเข้าเว็บจากบอท (Magic Link / alias / ความจำแชท)
 *
 * เดิมแต่ละที่เหมารวม "มี role_id = ทีมงาน" ⇒ ลูกค้าที่เปิดร้าน (SellerApplicationService ตั้ง role_id = seller)
 * ใช้ Magic Link ไม่ได้ทั้ง LINE และ FB — ตอนนี้ปฏิเสธเฉพาะบทบาทหลังบ้านจริง:
 *   - super admin / hotel admin
 *   - users.role อยู่ใน BACK_OFFICE_ROLES
 *   - role_id ชี้ไป Role ที่ไม่ใช่บทบาทลูกค้า (allow-list CUSTOMER_ROLES) — หา Role ไม่เจอ = หลังบ้าน (fail-closed)
 */
class FortuneBackOfficeAccount
{
    /** บทบาทหลังบ้าน (ตรงกับ users.role หรือ roles.name) */
    public const BACK_OFFICE_ROLES = ['admin', 'super_admin', 'staff', 'moderator', 'manager', 'support', 'editor'];

    /** บทบาทระดับลูกค้า — role_id ที่ชี้มาที่บทบาทเหล่านี้ไม่ใช่ทีมงาน */
    public const CUSTOMER_ROLES = ['user', 'customer', 'member', 'affiliate', 'seller', 'buyer', 'shop_owner', 'vendor'];

    /**
     * เป็นบัญชีหลังบ้านหรือไม่ — เช็คไม่ได้ (exception) = ถือว่าใช่ (fail-closed)
     */
    public static function is(User $user): bool
    {
        try {
            $attrs = $user->getAttributes();

            if (! empty($attrs['is_super_admin']) || ! empty($attrs['is_hotel_admin'])) {
                return true;
            }
            if (in_array((string) ($attrs['role'] ?? ''), self::BACK_OFFICE_ROLES, true)) {
                return true;
            }
            if (! empty($attrs['role_id'])) {
                $name = (string) (Role::query()->whereKey($attrs['role_id'])->value('name') ?? '');

                return ! in_array(strtolower($name), self::CUSTOMER_ROLES, true);
            }

            return false;
        } catch (\Throwable) {
            return true;
        }
    }
}
