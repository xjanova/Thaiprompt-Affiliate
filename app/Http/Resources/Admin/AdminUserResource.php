<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource สำหรับ Admin user (ใช้ใน Mobile API)
 *
 * เปิดเผยเฉพาะข้อมูลที่ admin app ต้องใช้
 * ไม่เปิดเผย password, secret, sensitive fields
 */
class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $twoFactor = $this->twoFactorSettings;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            // 🩹 (2026-10-06) เดิมอ่าน $this->avatar_url ซึ่งไม่มี accessor ⇒ null เสมอ
            'avatar_url' => $this->resolveAvatarUrl(),

            'role' => $this->role,
            'is_super_admin' => (bool) ($this->is_super_admin ?? false),

            // Permissions (สำหรับ UI gate ฝั่ง app)
            'permissions' => $this->collectAdminPermissions(),

            // 2FA status
            'two_factor' => [
                'enabled' => (bool) ($twoFactor->enabled ?? false),
                'preferred_method' => $twoFactor->preferred_method ?? null,
            ],

            // Wallet shortcut (admin อาจมี wallet ของตัวเอง)
            'wallet' => $this->whenLoaded('wallet', fn () => [
                'id' => $this->wallet?->id,
                'balance' => (float) ($this->wallet?->balance ?? 0),
                'wallet_address' => $this->wallet?->wallet_address,
            ]),

            // Rank
            'rank' => [
                'id' => $this->current_rank_id,
                'name' => $this->currentRank?->name,
            ],

            // Timestamps
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * รวบรวม permission strings เพื่อให้ app กำหนด UI ได้
     *
     * 🩹 (2026-10-06) เดิมเช็ค method_exists($user, 'permissions') — User ไม่มีเมธอดนั้น
     *   (permissions เป็นคอลัมน์ JSON) ⇒ แอดมินที่ไม่ใช่ super admin ได้ [] เสมอ
     *   ตอนนี้รวมจากแหล่งเดียวกับ User::hasPermission(): role ใหม่ (roles → role_permissions) + คอลัมน์ permissions เดิม
     */
    private function collectAdminPermissions(): array
    {
        // Super admin = ทุก permission
        if ($this->is_super_admin ?? false) {
            return ['*'];
        }

        $names = [];

        // ระบบใหม่: สิทธิ์ของ role ที่ผูกผ่าน role_id
        try {
            $role = $this->resource->roleModel;
            if ($role) {
                $names = $role->permissions()->pluck('name')->all();
            }
        } catch (\Throwable $e) {
            // ตาราง role/permission อ่านไม่ได้ = ข้ามไปใช้คอลัมน์เดิม
        }

        // ระบบเดิม: คอลัมน์ users.permissions (JSON array)
        $legacy = $this->resource->permissions ?? [];
        if (is_array($legacy)) {
            $names = array_merge($names, array_filter($legacy, 'is_string'));
        }

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    /**
     * URL รูปโปรไฟล์จริง (รูป LINE หรือรูปที่อัปโหลด) — ไม่มีรูป = null (แอปวาดอักษรย่อเอง)
     */
    private function resolveAvatarUrl(): ?string
    {
        $user = $this->resource;

        if (empty($user->line_picture_url) && empty($user->avatar)) {
            return null;
        }

        try {
            return $user->profile_picture_url;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
