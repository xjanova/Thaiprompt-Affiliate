<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource สำหรับ WalletTransaction (Admin Mobile API)
 */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'type' => $this->type,
            'status' => $this->status,
            'currency' => $this->currency,

            // Amounts
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,

            // Description
            'description' => $this->description,

            // Reference (related model — order, commission, withdrawal etc.)
            'reference' => $this->when($this->reference_type, fn () => [
                'type' => $this->reference_type,
                'id' => $this->reference_id,
            ]),

            // Related wallet (สำหรับ transfer)
            'related_wallet_id' => $this->related_wallet_id,

            // Owner
            'user_id' => $this->user_id,
            'wallet_id' => $this->wallet_id,

            // Metadata + IP
            'metadata' => $this->metadata,

            // Timestamps
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
