<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarbonCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Owner
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            // Product
            'food_product' => $this->whenLoaded('foodProduct', fn() => [
                'id' => $this->foodProduct->id,
                'passport_id' => $this->foodProduct->food_passport_id,
                'variety' => $this->foodProduct->variety,
            ]),

            // Credit details
            'credit_amount' => $this->credit_amount,
            'credit_type' => $this->credit_type,

            // Emissions
            'emissions' => [
                'baseline' => $this->baseline_emission,
                'actual' => $this->actual_emission,
                'reduction_percentage' => $this->reduction_percentage,
            ],

            // Valuation
            'valuation' => [
                'value_per_ton' => $this->credit_value_per_ton,
                'total_value' => $this->total_value,
                'current_value' => $this->current_value,
            ],

            // Validity
            'issued_date' => $this->issued_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'days_until_expiry' => $this->days_until_expiry,
            'status' => $this->status,

            // Trading
            'tradeable' => $this->tradeable,
            'can_trade' => $this->canTrade(),
            'traded_to' => $this->when($this->traded_to_user_id, fn() => [
                'user_id' => $this->traded_to_user_id,
                'traded_at' => $this->traded_at?->toIso8601String(),
                'trade_price' => $this->trade_price,
            ]),

            // Verification
            'verification' => [
                'verified_by' => $this->verified_by,
                'standard' => $this->verification_standard,
                'certificate_number' => $this->certificate_number,
            ],

            // Blockchain
            'blockchain' => [
                'hash' => $this->blockchain_hash,
                'token_id' => $this->token_id,
            ],

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
