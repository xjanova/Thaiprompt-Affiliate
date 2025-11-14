<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarbonFootprintRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'emission_category' => $this->emission_category,
            'emission_source' => $this->emission_source,
            'activity_data' => $this->activity_data,

            // Emission factor
            'emission_factor' => $this->emission_factor,
            'emission_factor_source' => $this->emission_factor_source,

            // Results
            'emissions' => [
                'co2' => $this->co2_emission,
                'ch4' => $this->ch4_emission,
                'n2o' => $this->n2o_emission,
                'total_co2_equivalent' => $this->total_co2_equivalent,
            ],

            // Offsets
            'offsets' => $this->when($this->carbon_offset_applied, fn() => [
                'offset_applied' => $this->carbon_offset_applied,
                'offset_source' => $this->offset_source,
                'reduction_percentage' => $this->reduction_percentage,
            ]),

            'net_emission' => $this->net_emission,

            // Calculation
            'calculation' => [
                'method' => $this->calculation_method,
                'date' => $this->calculation_date?->toIso8601String(),
            ],

            // Verification
            'verification' => [
                'status' => $this->verification_status,
                'verified_by_id' => $this->verified_by_id,
                'is_verified' => $this->isVerified(),
            ],

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
