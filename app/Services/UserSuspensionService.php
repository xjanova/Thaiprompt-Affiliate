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
 * ผลลัพธ์: ok = ทำสำเร็จหรือไม่ต้องทำอะไร (สถานะตรงกับที่ขอแล้ว) · level = success | info | error (ใช้กับ flash ของเว็บ)
 */
class UserSuspensionService
{
    /**
     * ระงับบัญชี (ย้อนกลับได้) — เพิกถอน token แอปทั้งหมด + ปล่อยงานไรเดอร์ (User::suspend)
     *
     * @return array{ok: bool, level: string, code: string, message: string}
     */
    public function suspend(User $target, User $admin, ?string $reason = null): array
    {
        if ((int) $admin->id === (int) $target->id) {
            return ['ok' => false, 'level' => 'error', 'code' => 'CANNOT_SUSPEND_SELF', 'message' => 'ไม่สามารถระงับบัญชีของตัวเองได้'];
        }
        if ($target->is_super_admin) {
            return ['ok' => false, 'level' => 'error', 'code' => 'CANNOT_SUSPEND_SUPER_ADMIN', 'message' => 'ไม่สามารถระงับผู้ดูแลระบบสูงสุดได้'];
        }
        if ($target->isSuspended()) {
            return ['ok' => true, 'level' => 'info', 'code' => 'ALREADY_SUSPENDED', 'message' => 'บัญชีนี้ถูกระงับอยู่แล้ว'];
        }

        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
        $target->suspend($admin, $reason);

        Log::info('Admin suspended user', [
            'user_id' => $target->id,
            'admin_id' => $admin->id,
        ]);

        return ['ok' => true, 'level' => 'success', 'code' => 'SUSPENDED', 'message' => 'ระงับบัญชี '.$target->name.' เรียบร้อย'];
    }

    /**
     * ยกเลิกการระงับบัญชี
     *
     * @return array{ok: bool, level: string, code: string, message: string}
     */
    public function unsuspend(User $target, User $admin): array
    {
        if (! $target->isSuspended()) {
            return ['ok' => true, 'level' => 'info', 'code' => 'NOT_SUSPENDED', 'message' => 'บัญชีนี้ไม่ได้ถูกระงับ'];
        }

        $target->unsuspend();

        Log::info('Admin unsuspended user', [
            'user_id' => $target->id,
            'admin_id' => $admin->id,
        ]);

        return ['ok' => true, 'level' => 'success', 'code' => 'UNSUSPENDED', 'message' => 'ยกเลิกการระงับบัญชี '.$target->name.' เรียบร้อย'];
    }
}
