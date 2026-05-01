<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource สำหรับ User list ใน Admin Mobile API
 *
 * เปิดเผยข้อมูลที่จำเป็น — ไม่เปิดเผย sensitive fields
 * (id_card_number, line_access_token, password, bank_account)
 */
class AdminUserListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,

            // Status
            'role' => $this->role,
            'is_super_admin' => (bool) ($this->is_super_admin ?? false),
            'is_blocked' => $this->blocked_at !== null,
            'blocked_at' => $this->blocked_at?->toIso8601String(),

            // Verification
            'phone_verified' => (bool) ($this->phone_verified ?? false),
            'line_verified' => (bool) ($this->line_verified ?? false),
            'facebook_verified' => (bool) ($this->facebook_verified ?? false),

            // Wallet
            'wallet' => $this->whenLoaded('wallet', fn () => [
                'balance' => (float) ($this->wallet?->balance ?? 0),
                'wallet_address' => $this->wallet?->wallet_address,
            ]),

            // Rank
            'rank' => $this->whenLoaded('currentRank', fn () => [
                'id' => $this->currentRank?->id,
                'name' => $this->currentRank?->name_th ?? $this->currentRank?->name,
                'level' => (int) ($this->currentRank?->level ?? 0),
                'color' => $this->currentRank?->color,
            ]),

            // Affiliate
            'referral_code' => $this->referral_code,

            // Address (city level only)
            'city' => $this->city,
            'country' => $this->country,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
