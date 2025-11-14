<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'food_passport_id' => $this->food_passport_id,
            'product_type' => $this->product_type,
            'variety' => $this->variety,
            'harvest_date' => $this->harvest_date?->format('Y-m-d'),
            'batch_number' => $this->batch_number,
            'quantity' => $this->estimated_quantity,
            'unit' => $this->unit,
            'current_stage' => $this->current_stage,
            'carbon_score' => $this->carbon_score,
            'total_carbon_footprint' => $this->total_carbon_footprint,
            'certifications' => $this->certifications,
            'qr_code_url' => $this->qr_code_url,
            'passport_url' => $this->passport_url,

            // Farmer info
            'farmer' => $this->whenLoaded('farmer', fn() => [
                'id' => $this->farmer->id,
                'name' => $this->farmer->name,
            ]),

            // Statistics
            'average_quality_score' => $this->average_quality_score,
            'total_scans' => $this->total_scans,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
