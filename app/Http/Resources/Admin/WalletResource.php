<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource สำหรับ Wallet (Admin Mobile API)
 */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_address' => $this->wallet_address,
            'currency' => $this->currency,

            // Balance
            'balance' => (float) $this->balance,
            'total_income' => (float) $this->total_income,
            'total_expense' => (float) $this->total_expense,

            // Status
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'is_locked' => $this->isLocked(),
            'locked_until' => $this->locked_until?->toIso8601String(),
            'failed_attempts' => (int) $this->failed_attempts,

            // 2FA
            'two_factor_enabled' => (bool) $this->two_factor_enabled,

            // Owner
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar_url' => $this->user->avatar_url,
                'phone' => $this->user->phone,
            ]),

            // Activity
            'last_transaction_at' => $this->last_transaction_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
