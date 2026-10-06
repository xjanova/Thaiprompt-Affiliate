<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 🔒 ระงับ / ยกเลิกระงับบัญชีผู้ใช้โดยแอดมิน (users.blocked_at) — ตรรกะเดียวใช้ร่วมกัน
 * ระหว่างหลังบ้านเว็บ (Admin\UserController) และแอปแอดมิน (/api/admin/users/{id}/suspend|unsuspend)
 *
 * ⚠️ ผู้เรียกต้องตรวจ policy `block` (UserPolicy) ก่อนเรียก — service นี้ดูแลเฉพาะกติกาของการระงับ
 *
 * กติกา:
 *   - ห้ามระงับตัวเอง / ผู้ดูแลระบบสูงสุด
 *   - 🛡️ (2026-10-06) บัญชีทีมงาน (admin / super_admin / moderator) → ระงับหรือยกเลิกระงับได้เฉพาะ super admin
 *     (กัน token แอดมินธรรมดาที่หลุดไปปิดบัญชีทีมงานคนอื่น หรือปลดบัญชีที่ super admin สั่งระงับไว้)
 *
 * ผลลัพธ์: ok = ทำสำเร็จหรือไม่ต้องทำอะไร (สถานะตรงกับที่ขอแล้ว) · level = success | info | error (ใช้กับ flash ของเว็บ)
 *          status = HTTP status ที่แอปแอดมินควรตอบ (200 / 403 ไม่มีสิทธิ์ / 409 ทำไม่ได้ตามกติกา)
 */
class UserSuspensionService
{
    /** role ที่ถือว่าเป็นทีมงาน — ระงับ/ยกเลิกระงับได้เฉพาะ super admin */
    public const STAFF_ROLES = ['admin', 'super_admin', 'moderator'];

    /**
     * ระงับบัญชี (ย้อนกลับได้) — เพิกถอน token แอปทั้งหมด + ปล่อยงานไรเดอร์ (User::suspend)
     *
     * @return array{ok: bool, level: string, code: string, message: string, status: int}
     */
    public function suspend(User $target, User $admin, ?string $reason = null): array
    {
        if ((int) $admin->id === (int) $target->id) {
            return $this->result(false, 'error', 'CANNOT_SUSPEND_SELF', 'ไม่สามารถระงับบัญชีของตัวเองได้', 409);
        }
        if ($target->is_super_admin) {
            return $this->result(false, 'error', 'CANNOT_SUSPEND_SUPER_ADMIN', 'ไม่สามารถระงับผู้ดูแลระบบสูงสุดได้', 409);
        }
        if ($this->isStaff($target) && ! $admin->is_super_admin) {
            return $this->result(false, 'error', 'STAFF_REQUIRES_SUPER_ADMIN', 'ระงับบัญชีทีมงาน (แอดมิน/ผู้ดูแล) ได้เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น', 403);
        }
        if ($target->isSuspended()) {
            return $this->result(true, 'info', 'ALREADY_SUSPENDED', 'บัญชีนี้ถูกระงับอยู่แล้ว');
        }

        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
        $target->suspend($admin, $reason);

        Log::info('Admin suspended user', [
            'user_id' => $target->id,
            'admin_id' => $admin->id,
        ]);

        return $this->result(true, 'success', 'SUSPENDED', 'ระงับบัญชี '.$target->name.' เรียบร้อย');
    }

    /**
     * ยกเลิกการระงับบัญชี
     *
     * @return array{ok: bool, level: string, code: string, message: string, status: int}
     */
    public function unsuspend(User $target, User $admin): array
    {
        if ($this->isStaff($target) && ! $admin->is_super_admin) {
            return $this->result(false, 'error', 'STAFF_REQUIRES_SUPER_ADMIN', 'ยกเลิกการระงับบัญชีทีมงาน (แอดมิน/ผู้ดูแล) ได้เฉพาะผู้ดูแลระบบสูงสุดเท่านั้น', 403);
        }
        if (! $target->isSuspended()) {
            return $this->result(true, 'info', 'NOT_SUSPENDED', 'บัญชีนี้ไม่ได้ถูกระงับ');
        }

        $target->unsuspend();

        Log::info('Admin unsuspended user', [
            'user_id' => $target->id,
            'admin_id' => $admin->id,
        ]);

        return $this->result(true, 'success', 'UNSUSPENDED', 'ยกเลิกการระงับบัญชี '.$target->name.' เรียบร้อย');
    }

    /**
     * บัญชีทีมงาน (admin / super_admin / moderator หรือธง super admin)
     */
    public function isStaff(User $user): bool
    {
        return (bool) $user->is_super_admin || in_array($user->role, self::STAFF_ROLES, true);
    }

    /**
     * @return array{ok: bool, level: string, code: string, message: string, status: int}
     */
    private function result(bool $ok, string $level, string $code, string $message, int $status = 200): array
    {
        return ['ok' => $ok, 'level' => $level, 'code' => $code, 'message' => $message, 'status' => $status];
    }
}
