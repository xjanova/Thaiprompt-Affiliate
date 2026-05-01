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
            'avatar_url' => $this->avatar_url,

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
     */
    private function collectAdminPermissions(): array
    {
        // Super admin = ทุก permission
        if ($this->is_super_admin ?? false) {
            return ['*'];
        }

        // ถ้ามี method hasPermission และ permissions table → ดึงตาม role
        if (method_exists($this->resource, 'permissions')) {
            try {
                return $this->permissions()->pluck('name')->toArray();
            } catch (\Throwable $e) {
                //
            }
        }

        return [];
    }
}
